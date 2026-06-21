<?php
    include '../js/fphp.php';
    include '../conn.php';

    // ─── HANDLE SAVE (POST) ───────────────────────────────────────────────────
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $userid  = isset($_POST['userid'])  ? trim($_POST['userid'])  : '';
        $fname   = isset($_POST['fname'])   ? trim(strtoupper($_POST['fname']))   : '';
        $lname   = isset($_POST['lname'])   ? trim(strtoupper($_POST['lname']))   : '';
        $user    = isset($_POST['user'])    ? trim($_POST['user'])    : '';
        $ini     = isset($_POST['ini'])     ? trim(strtoupper($_POST['ini']))     : '';
        $acctlvl = isset($_POST['acctlvl']) ? trim($_POST['acctlvl']) : '';

        // ── Validation ────────────────────────────────────────────────────────
        if (empty($userid) || !is_numeric($userid)) {
            echo json_encode(['success' => false, 'message' => 'Invalid user ID.']);
            exit;
        }
        if (empty($fname)) {
            echo json_encode(['success' => false, 'message' => 'First name is required.']);
            exit;
        }
        if (empty($lname)) {
            echo json_encode(['success' => false, 'message' => 'Last name is required.']);
            exit;
        }
        if (empty($user)) {
            echo json_encode(['success' => false, 'message' => 'Username is required.']);
            exit;
        }
        if (empty($ini)) {
            echo json_encode(['success' => false, 'message' => 'Initial is required.']);
            exit;
        }
        if (empty($acctlvl)) {
            echo json_encode(['success' => false, 'message' => 'Account level is required.']);
            exit;
        }

        // ── Check if username already taken (exclude current user) ────────────
        $sql_check  = "SELECT COUNT(*) AS cnt FROM [dbo].[tbl_users] WHERE [user] = ? AND [id] != ?";
        $params_chk = array(
            array($user,   SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_NVARCHAR(255)),
            array($userid, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_INT),
        );
        $stmt_check = sqlsrv_prepare($conn, $sql_check, $params_chk);

        if (!$stmt_check || sqlsrv_execute($stmt_check) === false) {
            echo json_encode(['success' => false, 'message' => 'Check query failed: ' . print_r(sqlsrv_errors(), true)]);
            exit;
        }

        $row_check = sqlsrv_fetch_array($stmt_check, SQLSRV_FETCH_ASSOC);
        if ($row_check['cnt'] > 0) {
            echo json_encode(['success' => false, 'message' => "Username '{$user}' is already taken. Please choose another."]);
            exit;
        }

        // ── UPDATE ────────────────────────────────────────────────────────────
        $sql_update = "UPDATE [dbo].[tbl_users]
                       SET [fname]   = ?,
                           [lname]   = ?,
                           [user]    = ?,
                           [ini]     = ?,
                           [acctlvl] = ?
                       WHERE [id] = ?";

        $params_upd = array(
            array($fname,   SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_NVARCHAR(255)),
            array($lname,   SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_NVARCHAR(255)),
            array($user,    SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_NVARCHAR(255)),
            array($ini,     SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_NVARCHAR(5)),
            array($acctlvl, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_NVARCHAR(100)),
            array($userid,  SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_INT),
        );

        $stmt_update = sqlsrv_prepare($conn, $sql_update, $params_upd);

        if (!$stmt_update || sqlsrv_execute($stmt_update) === false) {
            echo json_encode(['success' => false, 'message' => 'Update failed: ' . print_r(sqlsrv_errors(), true)]);
            exit;
        }

        echo json_encode(['success' => true, 'message' => "updated successfully."]);

        sqlsrv_free_stmt($stmt_check);
        sqlsrv_free_stmt($stmt_update);
        sqlsrv_close($conn);
        exit;
    }

    // ─── HANDLE FETCH (GET) ───────────────────────────────────────────────────
    $userid = isset($_GET['userid']) ? trim($_GET['userid']) : '';
    $row = null;

    if (!empty($userid) && is_numeric($userid)) {
        $sql  = "SELECT TOP 1 id, fname, lname, [user], acctlvl, ini FROM [dbo].[tbl_users] WHERE id = ?";
        $params = array(
            array($userid, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_INT)
        );
        $stmt = sqlsrv_prepare($conn, $sql, $params);
        if ($stmt && sqlsrv_execute($stmt)) {
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        }
    }
?>
<link rel="stylesheet" href="css/g.css">
<link rel="stylesheet" href="css/font-awesome-4.7.0/css/font-awesome.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">

<style>
  .uf-wrap {
    padding: 0;
    font-family: 'Inter', sans-serif;
  }

  .uf-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px 14px;
    border-bottom: 1px solid #e4e9e4;
  }

  .uf-header-left {
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .uf-header-icon {
    width: 34px; height: 34px;
    border-radius: 8px;
    background: #edf7f1;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
  }
  .uf-header-icon svg { color: #1e6b3c; }

  .uf-header h3 {
    font-size: 14px;
    font-weight: 600;
    color: #111;
    margin: 0 0 2px 0;
  }
  .uf-header p {
    font-size: 11px;
    color: #aaa;
    margin: 0;
  }

  .uf-close {
    width: 28px; height: 28px;
    border-radius: 6px;
    border: 1px solid #e4e9e4;
    background: #fff;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer;
    color: #aaa;
    transition: background .12s, color .12s;
    flex-shrink: 0;
  }
  .uf-close:hover { background: #fff5f5; border-color: #f0c0c0; color: #c0392b; }

  .uf-body {
    padding: 18px 20px;
    display: flex;
    flex-direction: column;
    gap: 14px;
  }

  .uf-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
  }

  .uf-field {
    display: flex;
    flex-direction: column;
    gap: 5px;
  }

  .uf-field.full {
    grid-column: 1 / -1;
  }

  .uf-label {
    font-size: 11px;
    font-weight: 600;
    color: #666;
    letter-spacing: 0.06em;
    text-transform: uppercase;
  }

  .uf-input {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #e4e9e4;
    border-radius: 7px;
    font-family: 'Inter', sans-serif;
    font-size: 13px;
    color: #111;
    background: #fff;
    transition: border-color .13s, box-shadow .13s;
    outline: none;
    box-sizing: border-box;
  }
  .uf-input:focus {
    border-color: #1e6b3c;
    box-shadow: 0 0 0 3px rgba(30,107,60,0.08);
  }

  .uf-select {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #e4e9e4;
    border-radius: 7px;
    font-family: 'Inter', sans-serif;
    font-size: 13px;
    color: #111;
    background: #fff;
    cursor: pointer;
    outline: none;
    transition: border-color .13s, box-shadow .13s;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%23aaa' stroke-width='2' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 10px center;
    padding-right: 30px;
    box-sizing: border-box;
  }
  .uf-select:focus {
    border-color: #1e6b3c;
    box-shadow: 0 0 0 3px rgba(30,107,60,0.08);
  }

  .uf-footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 8px;
    padding: 14px 20px 18px;
    border-top: 1px solid #f0f2f0;
  }

  .uf-btn-cancel {
    padding: 8px 18px;
    border-radius: 7px;
    border: 1px solid #e4e9e4;
    background: #fff;
    font-family: 'Inter', sans-serif;
    font-size: 12.5px;
    font-weight: 500;
    color: #666;
    cursor: pointer;
    transition: background .12s, border-color .12s;
  }
  .uf-btn-cancel:hover { background: #f5f5f5; border-color: #ccc; }

  .uf-btn-save {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 8px 18px;
    border-radius: 7px;
    border: none;
    background: #1e6b3c;
    font-family: 'Inter', sans-serif;
    font-size: 12.5px;
    font-weight: 500;
    color: #fff;
    cursor: pointer;
    transition: background .12s;
  }
  .uf-btn-save:hover { background: #185530; }
</style>

<div class="uf-wrap">

  <!-- Header -->
  <div class="uf-header">
    <div class="uf-header-left">
      <div class="uf-header-icon">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      </div>
      <div>
        <h3>Update User</h3>
        <p>Edit account details below</p>
      </div>
    </div>
    <button type="button" class="uf-close" onclick="$('#div_dentry').hide();" title="Close">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>
  </div>

  <!-- Form -->
  <form id="fupdate" autocomplete="off">
    <input type="hidden" name="userid" value="<?php echo htmlspecialchars($row['id'] ?? ''); ?>">

    <div class="uf-body">

      <!-- First & Last Name -->
      <div class="uf-row">
        <div class="uf-field">
          <label class="uf-label">First Name</label>
          <input type="text" name="fname" class="uf-input input-capitalize"
            value="<?php echo htmlspecialchars($row['fname'] ?? ''); ?>" required>
        </div>
        <div class="uf-field">
          <label class="uf-label">Last Name</label>
          <input type="text" name="lname" class="uf-input input-capitalize"
            value="<?php echo htmlspecialchars($row['lname'] ?? ''); ?>" required>
        </div>
      </div>

      <!-- Username & Initial -->
      <div class="uf-row">
        <div class="uf-field">
          <label class="uf-label">Username</label>
          <input type="text" name="user" class="uf-input"
            value="<?php echo htmlspecialchars($row['user'] ?? ''); ?>" required>
        </div>
        <div class="uf-field">
          <label class="uf-label">Initial</label>
          <input type="text" name="ini" class="uf-input"
            value="<?php echo htmlspecialchars($row['ini'] ?? ''); ?>"
            maxlength="10" style="text-transform:uppercase;" required>
        </div>
      </div>

      <!-- Account Level -->
      <div class="uf-row">
        <div class="uf-field full">
          <label class="uf-label">Account Level</label>
          <select name="acctlvl" class="uf-select">
            <?php
              $levels = ['Admin', 'Guest', 'Engineering', 'Metering',
                         'CAO Technics', 'NAO Technics', 'EAO Technics',
                         'SEAO Technics', 'SAO Technics', 'CAO Crew',
                         'NAO Crew', 'EAO Crew', 'SEAO Crew', 'SAO Crew',
                         'CAO CWA', 'NAO CWA', 'EAO CWA', 'SEAO CWA',
                         'SAO CWA', 'GM', 'BOD'];
              foreach ($levels as $lvl) {
                  $selected = (isset($row['acctlvl']) && $row['acctlvl'] === $lvl) ? 'selected' : '';
                  echo '<option value="' . $lvl . '" ' . $selected . '>' . $lvl . '</option>';
              }
            ?>
          </select>
        </div>
      </div>

    </div>

    <!-- Footer -->
    <div class="uf-footer">
      <button type="button" class="uf-btn-cancel" onclick="$('#div_dentry').hide();">Cancel</button>
      <!-- ✅ Points to itself — handles both GET (fetch) and POST (save) -->
      <button type="button" class="uf-btn-save"
        onclick="SaveData('utility/user_update_process.php', 'fupdate', '', 'utility/user_mgmt.php', 'main'); $('#div_dentry').hide();">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
        Save Changes
      </button>
    </div>

  </form>

</div>