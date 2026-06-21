<?php
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    ob_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_tag') {
    include 'conn.php';

    function tf_json($payload) {
        if (ob_get_level() > 0) {
            ob_clean();
        }
        header('Content-Type: application/json');
        echo json_encode($payload);
        exit;
    }

    function tf_db_error() {
        return print_r(sqlsrv_errors(), true);
    }

    function tf_ensure_master_table($conn) {
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

    $tag = trim($_POST['tag_name'] ?? '');
    if ($tag === '') {
        tf_json(['success' => false, 'message' => 'Tag name is required.']);
    }

    if (!tf_ensure_master_table($conn)) {
        tf_json(['success' => false, 'message' => 'Could not prepare tag master table: ' . tf_db_error()]);
    }

    /* Duplicate check */
    $chk = sqlsrv_query($conn, "SELECT id FROM tbl_tag WHERE tag = ?", [$tag]);
    if ($chk === false) {
        tf_json(['success' => false, 'message' => 'DB error: ' . tf_db_error()]);
    }
    if (sqlsrv_has_rows($chk)) {
        tf_json(['success' => false, 'message' => 'A tag with that name already exists.']);
    }

    /* PNG upload to iconsx/ folder */
    $btag = '';

    if (!empty($_FILES['tag_icon']['tmp_name'])) {
        $file  = $_FILES['tag_icon'];
        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            tf_json(['success' => false, 'message' => 'Upload failed. Please try again.']);
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if ($mime !== 'image/png') {
            tf_json(['success' => false, 'message' => 'Only PNG allowed for tag icon.']);
        }
        if ($file['size'] > 512 * 1024) {
            tf_json(['success' => false, 'message' => 'Image must be under 512 KB.']);
        }

        $upload_dir = __DIR__ . '/iconsx/';
        if (!is_dir($upload_dir)) {
            if (!mkdir($upload_dir, 0755, true)) {
                tf_json(['success' => false, 'message' => 'Could not create icon folder.']);
            }
        }

        $safe_name = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $tag);
        $safe_name = trim($safe_name, '_');
        if ($safe_name === '') $safe_name = 'tag_icon';

        $filename = $safe_name . '.png';
        $dest = $upload_dir . $filename;
        $i = 1;
        while (file_exists($dest)) {
            $filename = $safe_name . '_' . $i . '.png';
            $dest = $upload_dir . $filename;
            $i++;
        }

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            tf_json(['success' => false, 'message' => 'Failed to save image file.']);
        }

        $btag = 'iconsx/' . $filename;
    } else {
        tf_json(['success' => false, 'message' => 'Please upload a PNG icon.']);
    
    }

    $chk = sqlsrv_query($conn, "SELECT id FROM tbl_tag WHERE btag = ?", [$btag]);
    if ($chk === false) {
        tf_json(['success' => false, 'message' => 'DB error: ' . tf_db_error()]);
    }
    if (sqlsrv_has_rows($chk)) {
        tf_json(['success' => false, 'message' => 'A tag with that icon already exists.']);
    }

    /* Insert */
    $stmt = sqlsrv_query($conn,
        "INSERT INTO tbl_tag (tag, btag) OUTPUT INSERTED.id VALUES (?, ?)",
        [$tag, $btag]
    );

    if ($stmt === false) {
        tf_json(['success' => false, 'message' => 'DB error: ' . tf_db_error()]);
    }

    /* Get inserted ID */
    $id_row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

    tf_json([
        'success' => true,
        'id'      => $id_row['id'] ?? 0,
        'message' => 'Tag saved successfully.',
        'tag'     => $tag,
        'btag'    => $btag
    ]);
}
?>

<style>
.tf-label {
    display: block;
    font-size: 11px;
    font-weight: 600;
    letter-spacing: .05em;
    text-transform: uppercase;
    color: var(--color-text-secondary, #666);
    margin-bottom: 5px;
}
.tf-input {
    width: 100%;
    height: 36px;
    border: 0.5px solid var(--color-border-secondary, #ccc);
    border-radius: 8px;
    padding: 0 10px;
    font-size: 13px;
    background: var(--color-background-secondary, #f5f5f5);
    color: var(--color-text-primary, #111);
    box-sizing: border-box;
    transition: border-color .15s;
}
.tf-input:focus {
    outline: none;
    border-color: #00a84a;
}
#tf-dropzone {
    border: 1.5px dashed var(--color-border-secondary, #ccc);
    border-radius: 10px;
    padding: 20px 12px;
    text-align: center;
    cursor: pointer;
    transition: border-color .2s, background .2s;
    background: var(--color-background-secondary, #f9f9f9);
    position: relative;
    overflow: hidden;
}
#tf-dropzone.drag-over {
    border-color: #00a84a;
    background: #f0fdf4;
}
#tf-dropzone input[type=file] {
    position: absolute;
    inset: 0;
    opacity: 0;
    cursor: pointer;
    width: 100%;
    height: 100%;
}
#tf-preview-wrap {
    display: none;
    align-items: center;
    gap: 10px;
    margin-top: 10px;
    padding: 8px 10px;
    background: var(--color-background-secondary, #f5f5f5);
    border-radius: 8px;
    border: 0.5px solid var(--color-border-tertiary, #e5e5e5);
}
#tf-preview-wrap img {
    width: 40px;
    height: 40px;
    object-fit: contain;
    border-radius: 6px;
    background: #fff;
    border: 0.5px solid #ddd;
    padding: 2px;
}
#tf-preview-info {
    flex: 1;
    font-size: 11.5px;
    color: var(--color-text-secondary, #666);
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
}
#tf-remove-btn {
    background: none;
    border: none;
    cursor: pointer;
    color: #c62828;
    font-size: 15px;
    padding: 0 4px;
    line-height: 1;
}
#tf-save-btn {
    width: 100%;
    padding: 9px;
    background: #012b13;
    color: #fff;
    border: none;
    border-radius: 9px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    transition: background .15s;
    margin-top: 4px;
}
#tf-save-btn:hover    { background: #014d22; }
#tf-save-btn:disabled { opacity: .55; cursor: not-allowed; }
#tf-msg {
    font-size: 12px;
    padding: 7px 10px;
    border-radius: 7px;
    margin-top: 10px;
    display: none;
}
#tf-msg.tf-success { background: #e6f7ee; color: #00692e; }
#tf-msg.tf-error   { background: #fdecea; color: #c62828; }
</style>

<div style="padding:14px 16px 10px;">

    <!-- Tag Name -->
    <div style="margin-bottom:14px;">
        <label class="tf-label" for="tf-tag-name">Tag Name</label>
        <input class="tf-input" type="text" id="tf-tag-name"
               placeholder="e.g. Defective Meter, For Disconnection…"
               maxlength="80" autocomplete="off">
    </div>

    <!-- Icon Upload -->
    <div style="margin-bottom:14px;">
        <label class="tf-label">
            Tag Icon
            <span style="font-weight:400;text-transform:none;font-size:10px;margin-left:4px;">
                PNG only &middot; max 512 KB
            </span>
        </label>

        <div id="tf-dropzone">
            <input type="file" id="tf-file-input" accept=".png">
            <i class="fa fa-cloud-upload" style="font-size:24px;color:#bbb;display:block;margin-bottom:6px;"></i>
            <div style="font-size:12px;color:#aaa;">Click or drag &amp; drop PNG here</div>
        </div>

        <div id="tf-preview-wrap">
            <img id="tf-preview-img" src="" alt="preview">
            <span id="tf-preview-info">—</span>
            <button id="tf-remove-btn" type="button" title="Remove">
                <i class="fa fa-times"></i>
            </button>
        </div>
    </div>

    <!-- Message -->
    <div id="tf-msg"></div>

    <!-- Save -->
    <button id="tf-save-btn" type="button">
        <i class="fa fa-tag"></i> Save Tag
    </button>

</div>

<script>
(function () {
    var nameInp   = document.getElementById('tf-tag-name');
    var fileInp   = document.getElementById('tf-file-input');
    var dropzone  = document.getElementById('tf-dropzone');
    var prevWrap  = document.getElementById('tf-preview-wrap');
    var prevImg   = document.getElementById('tf-preview-img');
    var prevInfo  = document.getElementById('tf-preview-info');
    var removeBtn = document.getElementById('tf-remove-btn');
    var saveBtn   = document.getElementById('tf-save-btn');
    var msgDiv    = document.getElementById('tf-msg');

    var selectedFile = null;

    if (!nameInp || !saveBtn) {
        console.error('[tag_form] DOM not ready, bailing');
        return;
    }

    /* Drag & drop */
    dropzone.addEventListener('dragover',  function(e) { e.preventDefault(); dropzone.classList.add('drag-over'); });
    dropzone.addEventListener('dragleave', function()  { dropzone.classList.remove('drag-over'); });
    dropzone.addEventListener('drop', function(e) {
        e.preventDefault();
        dropzone.classList.remove('drag-over');
        if (e.dataTransfer.files[0]) handleFile(e.dataTransfer.files[0]);
    });

    fileInp.addEventListener('change', function() {
        if (fileInp.files[0]) handleFile(fileInp.files[0]);
    });

    function handleFile(file) {
        if (file.type !== 'image/png') { showMsg('Only PNG files are allowed.', 'tf-error'); return; }
        if (file.size > 512 * 1024)    { showMsg('Image must be under 512 KB.',  'tf-error'); return; }
        selectedFile = file;
        var reader = new FileReader();
        reader.onload = function(ev) {
            prevImg.src = ev.target.result;
            prevInfo.textContent = file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
            prevWrap.style.display = 'flex';
        };
        reader.readAsDataURL(file);
        hideMsg();
    }

    removeBtn.addEventListener('click', function() {
        selectedFile           = null;
        fileInp.value          = '';
        prevImg.src            = '';
        prevWrap.style.display = 'none';
    });

    saveBtn.addEventListener('click', async function() {
    var tagName = nameInp.value.trim();

    if (!tagName) { 
        showMsg('Please enter a tag name.', 'tf-error'); 
        nameInp.focus(); 
        return; 
    }
    if (!selectedFile) { 
        showMsg('Please upload a PNG icon for this tag.', 'tf-error'); 
        return; 
    }

    saveBtn.disabled  = true;
    saveBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Saving…';
    hideMsg();

    var fd = new FormData();
    fd.append('action',   'save_tag');
    fd.append('tag_name', tagName);
    fd.append('tag_icon', selectedFile, selectedFile.name);

    try {
        var res  = await fetch('tag_form.php', { method: 'POST', body: fd });
        var text = await res.text();
        var data;
        try {
            data = JSON.parse(text);
        } catch (parseErr) {
            showMsg('Save failed. Server returned HTML instead of JSON. Please refresh and try again.', 'tf-error');
            return;
        }
        if (data.success) {
            showMsg('<i class="fa fa-check-circle"></i> ' + data.message, 'tf-success');
            var tagSelect = document.getElementById('btag');
            if (tagSelect && data.btag) {
                var opt = document.createElement('option');
                opt.value = data.btag;
                opt.textContent = data.tag || tagName;
                opt.dataset.id = data.id || '0';
                tagSelect.appendChild(opt);
                tagSelect.value = data.btag;
            }
            nameInp.value = '';
            removeBtn.click();
        } else {
            showMsg(data.message || 'Save failed.', 'tf-error');
        }
    } catch (err) {
        showMsg('Network error: ' + err.message, 'tf-error');
    }

    saveBtn.innerHTML = '<i class="fa fa-tag"></i> Save Tag';
    saveBtn.disabled  = false;
});


    function showMsg(html, cls) {
        msgDiv.innerHTML     = html;
        msgDiv.className     = cls;
        msgDiv.style.display = 'block';
    }
    function hideMsg() { msgDiv.style.display = 'none'; }

    nameInp.focus();
})();
</script>
