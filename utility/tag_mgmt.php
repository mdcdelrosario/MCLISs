<?php
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    ob_start();
}

include '../js/fphp.php';
include '../conn.php';

header_remove('X-Powered-By');

function tag_json($payload) {
    if (ob_get_level() > 0) {
        ob_clean();
    }
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit;
}

function tag_safe_name($name) {
    $safe = preg_replace('/[^a-zA-Z0-9_\-]/', '_', trim($name));
    $safe = trim($safe, '_');
    return $safe !== '' ? $safe : 'tag_icon';
}

function tag_upload_png($field, $tagName, $existingPath = '') {
    if (empty($_FILES[$field]['tmp_name'])) {
        return $existingPath;
    }

    $file = $_FILES[$field];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Upload failed. Please try again.');
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if ($mime !== 'image/png') {
        throw new Exception('Only PNG files are allowed.');
    }

    if ($file['size'] > 512 * 1024) {
        throw new Exception('Image must be under 512 KB.');
    }

    $uploadDir = dirname(__DIR__) . '/iconsx/';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
        throw new Exception('Could not create icon folder.');
    }

    $existingPath = trim($existingPath);
    if ($existingPath !== '' && preg_match('#^iconsx/[a-zA-Z0-9_\-\.]+\.png$#', $existingPath)) {
        $filename = basename($existingPath);
    } else {
        $filename = tag_safe_name($tagName) . '.png';
        $i = 1;
        while (file_exists($uploadDir . $filename)) {
            $filename = tag_safe_name($tagName) . '_' . $i . '.png';
            $i++;
        }
    }

    $dest = $uploadDir . $filename;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        throw new Exception('Failed to save image file.');
    }

    return 'iconsx/' . $filename;
}

function tag_customer_count($conn, $btag) {
    if ($btag === null || $btag === '') return 0;
    $stmt = sqlsrv_query($conn, "SELECT COUNT(*) AS cnt FROM tbl_cust WHERE [BTAG] = ?", [$btag]);
    if ($stmt && $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        return (int)$row['cnt'];
    }
    return 0;
}

function tag_db_error() {
    return print_r(sqlsrv_errors(), true);
}

function tag_query_or_throw($conn, $sql, $params = []) {
    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        throw new Exception('DB error: ' . tag_db_error());
    }
    return $stmt;
}

function tag_ensure_master_table($conn) {
    $sql = "IF OBJECT_ID('dbo.tbl_tag', 'U') IS NULL
            BEGIN
                CREATE TABLE dbo.tbl_tag (
                    id int IDENTITY(1,1) NOT NULL PRIMARY KEY,
                    tag nvarchar(80) NOT NULL,
                    btag nvarchar(255) NULL
                );
            END";
    return sqlsrv_query($conn, $sql) !== false;
}

function tag_master_exists($conn) {
    $stmt = sqlsrv_query($conn, "SELECT OBJECT_ID('dbo.tbl_tag', 'U') AS object_id");
    if ($stmt && $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        return !empty($row['object_id']);
    }
    return false;
}

function tag_upsert_master($conn, $tag, $btag, $id = 0, $oldBtag = '') {
    if (!tag_ensure_master_table($conn)) {
        return false;
    }

    if ($id > 0) {
        return sqlsrv_query($conn, "UPDATE tbl_tag SET tag = ?, btag = ? WHERE id = ?", [$tag, $btag, $id]) !== false;
    }

    $find = null;
    if ($oldBtag !== '') {
        $find = sqlsrv_query($conn, "SELECT id FROM tbl_tag WHERE btag = ?", [$oldBtag]);
    }
    if ((!$find || !sqlsrv_has_rows($find)) && $btag !== '') {
        $find = sqlsrv_query($conn, "SELECT id FROM tbl_tag WHERE btag = ?", [$btag]);
    }

    if ($find && ($row = sqlsrv_fetch_array($find, SQLSRV_FETCH_ASSOC))) {
        return sqlsrv_query($conn, "UPDATE tbl_tag SET tag = ?, btag = ? WHERE id = ?", [$tag, $btag, (int)$row['id']]) !== false;
    }

    return sqlsrv_query($conn, "INSERT INTO tbl_tag (tag, btag) VALUES (?, ?)", [$tag, $btag]) !== false;
}

function tag_load_tags($conn) {
    $tags = [];

    if (tag_master_exists($conn)) {
        $stmt = sqlsrv_query($conn, "SELECT id AS tag_id, tag AS tag_name, btag AS tag_icon, CAST('master' AS varchar(10)) AS tag_source
                                     FROM tbl_tag
                                     ORDER BY tag ASC");
        if ($stmt !== false) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $btag = trim($row['tag_icon'] ?? '');
                $row['tagged_count'] = tag_customer_count($conn, $btag);
                if ($btag !== '') {
                    $tags[$btag] = $row;
                } else {
                    $tags['master:' . $row['tag_id']] = $row;
                }
            }
        }
    }

    $stmt = sqlsrv_query($conn, "SELECT
                                    CAST(0 AS int) AS tag_id,
                                    COALESCE(NULLIF(MIN(CAST([TAG] AS nvarchar(255))), ''), [BTAG]) AS tag_name,
                                    [BTAG] AS tag_icon,
                                    COUNT(*) AS tagged_count,
                                    CAST('customer' AS varchar(10)) AS tag_source
                                 FROM tbl_cust
                                 WHERE ISNULL([BTAG], '') <> ''
                                 GROUP BY [BTAG]
                                 ORDER BY tag_name ASC");
    if ($stmt !== false) {
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $btag = trim($row['tag_icon'] ?? '');
            if ($btag !== '' && !isset($tags[$btag])) {
                $tags[$btag] = $row;
            }
        }
    }

    $deduped = [];
    foreach (array_values($tags) as $tag) {
        $nameKey = strtolower(trim($tag['tag_name'] ?? ''));
        $key = $nameKey !== '' ? $nameKey : 'btag:' . strtolower(trim($tag['tag_icon'] ?? ''));
        if (!isset($deduped[$key]) || (int)($tag['tagged_count'] ?? 0) > (int)($deduped[$key]['tagged_count'] ?? 0)) {
            $deduped[$key] = $tag;
        }
    }

    return array_values($deduped);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'save') {
            $id   = (int)($_POST['tag_id'] ?? 0);
            $tag  = trim($_POST['tag_name'] ?? '');
            $oldBtag = trim($_POST['current_btag'] ?? '');

            if ($tag === '') {
                tag_json(['success' => false, 'message' => 'Tag name is required.']);
            }

            if ($id > 0) {
                if (!tag_ensure_master_table($conn)) {
                    tag_json(['success' => false, 'message' => 'Could not prepare tag master table.']);
                }
                $currentStmt = sqlsrv_query($conn, "SELECT tag, btag FROM tbl_tag WHERE id = ?", [$id]);
                $current = $currentStmt ? sqlsrv_fetch_array($currentStmt, SQLSRV_FETCH_ASSOC) : null;
                if (!$current) {
                    tag_json(['success' => false, 'message' => 'Tag not found.']);
                }

                $dup = sqlsrv_query($conn, "SELECT id FROM tbl_tag WHERE tag = ? AND id <> ?", [$tag, $id]);
                if ($dup && sqlsrv_has_rows($dup)) {
                    tag_json(['success' => false, 'message' => 'A tag with that name already exists.']);
                }

                $oldBtag = trim($current['btag'] ?? '');
                $newBtag = tag_upload_png('tag_icon', $tag, $oldBtag);

                sqlsrv_begin_transaction($conn);
                try {
                    if (!tag_upsert_master($conn, $tag, $newBtag, $id, $oldBtag)) {
                        throw new Exception('DB error: ' . tag_db_error());
                    }

                    if ($oldBtag !== '' && $newBtag !== $oldBtag) {
                        tag_query_or_throw($conn, "UPDATE tbl_cust SET [BTAG] = ? WHERE [BTAG] = ?", [$newBtag, $oldBtag]);
                    }

                    sqlsrv_commit($conn);
                } catch (Exception $e) {
                    sqlsrv_rollback($conn);
                    tag_json(['success' => false, 'message' => $e->getMessage()]);
                }

                tag_json(['success' => true, 'message' => 'Tag updated successfully.']);
            }

            if ($oldBtag !== '') {
                $newBtag = tag_upload_png('tag_icon', $tag, $oldBtag);

                sqlsrv_begin_transaction($conn);
                try {
                    if (!tag_upsert_master($conn, $tag, $newBtag, 0, $oldBtag)) {
                        throw new Exception('DB error: ' . tag_db_error());
                    }

                    if ($newBtag !== $oldBtag) {
                        tag_query_or_throw($conn, "UPDATE tbl_cust SET [BTAG] = ? WHERE [BTAG] = ?", [$newBtag, $oldBtag]);
                    }

                    sqlsrv_commit($conn);
                } catch (Exception $e) {
                    sqlsrv_rollback($conn);
                    tag_json(['success' => false, 'message' => $e->getMessage()]);
                }

                tag_json(['success' => true, 'message' => 'Tag updated successfully.']);
            }

            if (!tag_ensure_master_table($conn)) {
                tag_json(['success' => false, 'message' => 'Could not prepare tag master table.']);
            }

            $dup = sqlsrv_query($conn, "SELECT id FROM tbl_tag WHERE tag = ?", [$tag]);
            if ($dup && sqlsrv_has_rows($dup)) {
                tag_json(['success' => false, 'message' => 'A tag with that name already exists.']);
            }

            $btag = tag_upload_png('tag_icon', $tag, '');
            if ($btag === '') {
                tag_json(['success' => false, 'message' => 'Please upload a PNG icon.']);
            }

            if (!tag_upsert_master($conn, $tag, $btag)) {
                tag_json(['success' => false, 'message' => 'DB error: ' . print_r(sqlsrv_errors(), true)]);
            }

            tag_json(['success' => true, 'message' => 'Tag added successfully.']);
        }

        if ($action === 'delete') {
            $id = (int)($_POST['tag_id'] ?? 0);
            $stmt = sqlsrv_query($conn, "SELECT btag FROM tbl_tag WHERE id = ?", [$id]);
            $row = $stmt ? sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC) : null;

            if (!$row) {
                tag_json(['success' => false, 'message' => 'Tag not found.']);
            }

            $btag = trim($row['btag'] ?? '');
            $count = tag_customer_count($conn, $btag);
            if ($count > 0) {
                tag_json(['success' => false, 'message' => 'This tag is used by ' . $count . ' customer(s), so it cannot be deleted.']);
            }

            $del = sqlsrv_query($conn, "DELETE FROM tbl_tag WHERE id = ?", [$id]);
            if ($del === false) {
                tag_json(['success' => false, 'message' => 'DB error: ' . print_r(sqlsrv_errors(), true)]);
            }

            if ($btag !== '' && preg_match('#^iconsx/[a-zA-Z0-9_\-\.]+\.png$#', $btag)) {
                $file = dirname(__DIR__) . '/' . $btag;
                if (is_file($file)) @unlink($file);
            }

            tag_json(['success' => true, 'message' => 'Unused tag deleted.']);
        }

        tag_json(['success' => false, 'message' => 'Invalid action.']);
    } catch (Exception $e) {
        tag_json(['success' => false, 'message' => $e->getMessage()]);
    }
}

$tags = tag_load_tags($conn);
?>

<link rel="stylesheet" href="css/g.css">
<link rel="stylesheet" href="css/font-awesome-4.7.0/css/font-awesome.css">

<style>
  .tm-page { height:100%; display:flex; flex-direction:column; background:#f5f6f4; color:#162018; }
  .tm-header { display:flex; align-items:flex-start; justify-content:space-between; gap:18px; padding:24px 30px 18px; background:#fff; border-bottom:1px solid #e5ebe6; }
  .tm-header h2 { font-size:20px; line-height:1.2; color:#0f2417; margin:0 0 4px; }
  .tm-header p { margin:0; font-size:12.5px; color:#738176; }
  .tm-actions { display:flex; align-items:center; gap:10px; }
  .tm-search { height:36px; width:230px; border:1px solid #dfe7e1; border-radius:7px; padding:0 11px; outline:none; font-size:12.5px; }
  .tm-search:focus { border-color:#1e6b3c; box-shadow:0 0 0 3px rgba(30,107,60,.08); }
  .tm-btn { height:36px; border:0; border-radius:7px; padding:0 13px; display:inline-flex; align-items:center; justify-content:center; gap:7px; cursor:pointer; font-weight:600; font-size:12.5px; }
  .tm-btn-primary { background:#123d22; color:#fff; }
  .tm-btn-primary:hover { background:#1e6b3c; }
  .tm-btn-soft { background:#eef6f1; color:#1e6b3c; border:1px solid #cfe4d6; }
  .tm-btn-danger { background:#fff1f1; color:#b42a2a; border:1px solid #f1c2c2; }
  .tm-btn:disabled { opacity:.45; cursor:not-allowed; }
  .tm-body { padding:22px 30px 30px; overflow:auto; }
  .tm-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(260px, 1fr)); gap:12px; }
  .tm-card { background:#fff; border:1px solid #e3e9e4; border-radius:8px; padding:13px; display:grid; grid-template-columns:50px minmax(0,1fr); gap:12px; align-items:center; }
  .tm-icon { width:50px; height:50px; border:1px solid #dfe7e1; border-radius:8px; background:#f8faf8; display:flex; align-items:center; justify-content:center; overflow:hidden; }
  .tm-icon img { width:38px; height:38px; object-fit:contain; }
  .tm-name { font-size:13.5px; font-weight:700; color:#152117; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
  .tm-path { margin-top:3px; font-size:11px; color:#8b988e; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
  .tm-count { margin-top:7px; display:inline-flex; align-items:center; gap:5px; font-size:11px; color:#1e6b3c; background:#eef8f1; border:1px solid #d3eadb; border-radius:999px; padding:3px 8px; }
  .tm-card-actions { grid-column:1 / -1; display:flex; gap:8px; padding-top:5px; }
  .tm-card-actions .tm-btn { flex:1; height:32px; }
  .tm-empty { text-align:center; padding:44px 18px; color:#7a877d; background:#fff; border:1px dashed #ccd7cf; border-radius:8px; }
  .tm-modal { display:none; position:fixed; inset:0; z-index:9999; background:rgba(0,0,0,.42); align-items:center; justify-content:center; padding:18px; }
  .tm-dialog { width:min(460px, 100%); background:#fff; border-radius:8px; box-shadow:0 18px 45px rgba(0,0,0,.22); overflow:hidden; }
  .tm-dialog-head { display:flex; justify-content:space-between; align-items:center; padding:15px 17px; border-bottom:1px solid #e5ebe6; }
  .tm-dialog-head strong { font-size:15px; color:#0f2417; }
  .tm-x { border:0; background:transparent; cursor:pointer; color:#67736a; font-size:16px; }
  .tm-form { padding:17px; display:flex; flex-direction:column; gap:13px; }
  .tm-label { display:block; font-size:11px; font-weight:700; text-transform:uppercase; color:#748177; margin-bottom:5px; letter-spacing:.04em; }
  .tm-input { width:100%; height:36px; border:1px solid #dfe7e1; border-radius:7px; padding:0 10px; outline:none; font-size:13px; }
  .tm-input:focus { border-color:#1e6b3c; box-shadow:0 0 0 3px rgba(30,107,60,.08); }
  .tm-file-row { display:flex; align-items:center; gap:12px; }
  .tm-preview { width:58px; height:58px; border:1px solid #dfe7e1; border-radius:8px; background:#f8faf8; display:flex; align-items:center; justify-content:center; overflow:hidden; flex:0 0 auto; }
  .tm-preview img { width:44px; height:44px; object-fit:contain; }
  .tm-file-note { font-size:11.5px; color:#7a877d; line-height:1.4; }
  .tm-msg { display:none; padding:8px 10px; border-radius:7px; font-size:12px; }
  .tm-msg.ok { display:block; background:#e8f7ee; color:#0f6b35; }
  .tm-msg.err { display:block; background:#fdecec; color:#b52b2b; }
  .tm-dialog-foot { display:flex; justify-content:flex-end; gap:9px; padding:13px 17px; background:#f8faf8; border-top:1px solid #e5ebe6; }
</style>

<div class="tm-page">
  <div class="tm-header">
    <div>
      <h2>Tag Management</h2>
      <p>Add, edit, preview, and remove unused customer tag icons. Refresh the map search after changes to reload tag dropdowns and markers.</p>
    </div>
    <div class="tm-actions">
      <input class="tm-search" type="text" placeholder="Search tags..." oninput="tmFilter(this.value)">
      <button class="tm-btn tm-btn-primary" type="button" onclick="tmOpenAdd()">
        <i class="fa fa-plus"></i> Add Tag
      </button>
    </div>
  </div>

  <div class="tm-body">
    <?php if (empty($tags)): ?>
      <div class="tm-empty">No tags yet. Add the first tag to make it available in the map tagging dropdown.</div>
    <?php else: ?>
      <div class="tm-grid" id="tm-grid">
        <?php foreach ($tags as $tag): ?>
          <?php
            $id = (int)$tag['tag_id'];
            $name = htmlspecialchars($tag['tag_name'] ?? '', ENT_QUOTES, 'UTF-8');
            $btag = htmlspecialchars($tag['tag_icon'] ?? '', ENT_QUOTES, 'UTF-8');
            $count = (int)($tag['tagged_count'] ?? 0);
            $source = $tag['tag_source'] ?? 'master';
            $isCustomerSource = $source === 'customer';
            $cacheBust = $btag !== '' ? $btag . '?v=' . time() : '';
          ?>
          <div class="tm-card" data-name="<?= strtolower($name) ?>" data-id="<?= $id ?>" data-source="<?= htmlspecialchars($source, ENT_QUOTES, 'UTF-8') ?>" data-tag="<?= $name ?>" data-btag="<?= $btag ?>">
            <div class="tm-icon">
              <?php if ($btag !== ''): ?>
                <img src="<?= htmlspecialchars($cacheBust, ENT_QUOTES, 'UTF-8') ?>" alt="<?= $name ?>">
              <?php else: ?>
                <i class="fa fa-tag" style="color:#9aa89d;"></i>
              <?php endif; ?>
            </div>
            <div style="min-width:0;">
              <div class="tm-name"><?= $name ?></div>
              <div class="tm-path"><?= $btag !== '' ? $btag : 'No icon path' ?></div>
              <span class="tm-count"><i class="fa fa-users"></i> <?= $count ?> tagged</span>
            </div>
            <div class="tm-card-actions">
              <button class="tm-btn tm-btn-soft" type="button" onclick="tmOpenEdit(this)">
                <i class="fa fa-pencil"></i> Edit
              </button>
              <button class="tm-btn tm-btn-danger" type="button" onclick="tmDelete(this)" <?= $count > 0 ? 'disabled title="Cannot delete tags currently used by customers"' : '' ?>>
                <i class="fa fa-trash"></i> Delete Unused
              </button>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<div class="tm-modal" id="tm-modal">
  <div class="tm-dialog">
    <div class="tm-dialog-head">
      <strong id="tm-title">Add Tag</strong>
      <button class="tm-x" type="button" onclick="tmClose()"><i class="fa fa-times"></i></button>
    </div>
    <form class="tm-form" id="tm-form" enctype="multipart/form-data" method="post" action="utility/tag_mgmt.php" onsubmit="tmSave(); return false;">
      <input type="hidden" name="action" value="save">
      <input type="hidden" id="tm-tag-id" name="tag_id" value="0">
      <input type="hidden" id="tm-current-btag" name="current_btag" value="">

      <div>
        <label class="tm-label" for="tm-tag-name">Tag Name</label>
        <input class="tm-input" type="text" id="tm-tag-name" name="tag_name" maxlength="80" autocomplete="off">
      </div>

      <div>
        <label class="tm-label" for="tm-tag-icon">Tag Icon</label>
        <div class="tm-file-row">
          <div class="tm-preview" id="tm-preview"><i class="fa fa-tag" style="color:#9aa89d;"></i></div>
          <div style="flex:1;">
            <input type="file" id="tm-tag-icon" name="tag_icon" accept=".png,image/png">
            <div class="tm-file-note">PNG only, max 512 KB. Editing an existing tag replaces the current icon file path used by customers.</div>
          </div>
        </div>
      </div>

      <div class="tm-msg" id="tm-msg"></div>
    </form>
    <div class="tm-dialog-foot">
      <button class="tm-btn tm-btn-soft" type="button" onclick="tmClose()">Cancel</button>
      <button class="tm-btn tm-btn-primary" id="tm-save" type="button" onclick="tmSave()">
        <i class="fa fa-floppy-o"></i> Save Tag
      </button>
    </div>
  </div>
</div>

<script>
function tmFilter(value) {
  value = String(value || '').toLowerCase();
  document.querySelectorAll('#tm-grid .tm-card').forEach(function(card) {
    card.style.display = card.dataset.name.indexOf(value) >= 0 ? '' : 'none';
  });
}

function tmSetPreview(src) {
  var preview = document.getElementById('tm-preview');
  if (src) {
    preview.innerHTML = '<img src="' + src.replace(/"/g, '&quot;') + '" alt="preview">';
  } else {
    preview.innerHTML = '<i class="fa fa-tag" style="color:#9aa89d;"></i>';
  }
}

function tmOpenAdd() {
  document.getElementById('tm-title').textContent = 'Add Tag';
  document.getElementById('tm-form').reset();
  document.getElementById('tm-tag-id').value = '0';
  document.getElementById('tm-current-btag').value = '';
  tmSetPreview('');
  tmMsg('');
  document.getElementById('tm-modal').style.display = 'flex';
  setTimeout(function() { document.getElementById('tm-tag-name').focus(); }, 50);
}

function tmOpenEdit(btn) {
  var card = btn.closest('.tm-card');
  document.getElementById('tm-title').textContent = 'Edit Tag';
  document.getElementById('tm-form').reset();
  document.getElementById('tm-tag-id').value = card.dataset.id;
  document.getElementById('tm-current-btag').value = card.dataset.btag || '';
  document.getElementById('tm-tag-name').value = card.dataset.tag || '';
  tmSetPreview(card.dataset.btag ? card.dataset.btag + '?v=' + Date.now() : '');
  tmMsg('');
  document.getElementById('tm-modal').style.display = 'flex';
  setTimeout(function() { document.getElementById('tm-tag-name').focus(); }, 50);
}

function tmClose() {
  document.getElementById('tm-modal').style.display = 'none';
}

function tmMsg(message, ok) {
  var el = document.getElementById('tm-msg');
  el.className = 'tm-msg' + (message ? (ok ? ' ok' : ' err') : '');
  el.innerHTML = message || '';
}

document.getElementById('tm-tag-icon').addEventListener('change', function() {
  var file = this.files && this.files[0];
  if (!file) return;
  if (file.type !== 'image/png') {
    tmMsg('Only PNG files are allowed.', false);
    this.value = '';
    return;
  }
  if (file.size > 512 * 1024) {
    tmMsg('Image must be under 512 KB.', false);
    this.value = '';
    return;
  }
  var reader = new FileReader();
  reader.onload = function(e) { tmSetPreview(e.target.result); };
  reader.readAsDataURL(file);
  tmMsg('');
});

document.getElementById('tm-form').addEventListener('submit', function(e) {
  e.preventDefault();
  tmSave();
});

async function tmSave() {
  var form = document.getElementById('tm-form');
  var id = document.getElementById('tm-tag-id').value;
  var name = document.getElementById('tm-tag-name').value.trim();
  var file = document.getElementById('tm-tag-icon').files[0];
  var btn = document.getElementById('tm-save');

  if (!name) {
    tmMsg('Tag name is required.', false);
    document.getElementById('tm-tag-name').focus();
    return;
  }
  if (id === '0' && !file) {
    tmMsg('Please upload a PNG icon.', false);
    return;
  }

  btn.disabled = true;
  btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Saving';

  try {
    var res = await fetch('utility/tag_mgmt.php', { method: 'POST', body: new FormData(form) });
    var text = await res.text();
    var data;
    try {
      data = JSON.parse(text);
    } catch (parseErr) {
      tmMsg('Save failed. Server returned HTML instead of JSON. Please refresh and try again.', false);
      return;
    }
    if (!data.success) {
      tmMsg(data.message || 'Save failed.', false);
      return;
    }
    tmMsg(data.message, true);
    PageLink('utility/tag_mgmt.php', 'main');
  } catch (err) {
    tmMsg('Network error: ' + err.message, false);
  } finally {
    btn.disabled = false;
    btn.innerHTML = '<i class="fa fa-floppy-o"></i> Save Tag';
  }
}

function tmDelete(btn) {
  var card = btn.closest('.tm-card');
  var name = card.dataset.tag || 'this tag';
  var runDelete = async function() {
    var fd = new FormData();
    fd.append('action', 'delete');
    fd.append('tag_id', card.dataset.id);
    btn.disabled = true;
    try {
      var res = await fetch('utility/tag_mgmt.php', { method: 'POST', body: fd });
      var text = await res.text();
      var data;
      try {
        data = JSON.parse(text);
      } catch (parseErr) {
        showmsgbox('Tag Management', 'Delete failed. Server returned HTML instead of JSON.');
        btn.disabled = false;
        return;
      }
      if (!data.success) {
        showmsgbox('Tag Management', data.message || 'Delete failed.');
        btn.disabled = false;
        return;
      }
      PageLink('utility/tag_mgmt.php', 'main');
    } catch (err) {
      showmsgbox('Tag Management', 'Network error: ' + err.message);
      btn.disabled = false;
    }
  };

  if (typeof showConfirm === 'function') {
    showConfirm({
      title: 'Delete Unused Tag',
      message: 'Delete "' + name + '"? This is only allowed when no customers use it.',
      onConfirm: runDelete
    });
  } else if (confirm('Delete "' + name + '"?')) {
    runDelete();
  }
}
</script>
