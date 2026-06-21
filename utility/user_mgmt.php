<?php
    include '../js/fphp.php';
    include '../conn.php';

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $currentAcctLvl = strtolower($_SESSION['user_level'] ?? '');
    $isGuest = $currentAcctLvl === 'guest';
?>
<link rel="stylesheet" href="css/g.css">
<link rel="stylesheet" href="css/font-awesome-4.7.0/css/font-awesome.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<script src="js/search.js"></script>

<script>
    var IS_GUEST = <?php echo $isGuest ? 'true' : 'false'; ?>;
</script>

<script>
    $('#fdata').on('submit', function(e) {
        e.preventDefault();
    });

    function toggleSelectAll(source) {
        var checkboxes = document.querySelectorAll('.um-row-check');
        checkboxes.forEach(function(cb) { cb.checked = source.checked; });
        updateDeleteBtn();
    }

    function updateDeleteBtn() {
        var checked = document.querySelectorAll('.um-row-check:checked').length;
        var btn = document.getElementById('btn-delete-selected');
        var countEl = document.getElementById('delete-count');
        countEl.textContent = checked;
        btn.disabled = checked === 0;
    }

    function deleteSelected() {
        var checked = document.querySelectorAll('.um-row-check:checked');
        if (checked.length === 0) return;

        var ids = [];
        checked.forEach(function(cb) { ids.push(cb.value); });

        showConfirm({
            title:   'Delete Users',
            message: 'Are you sure you want to delete ' + checked.length + ' selected user(s)? This cannot be undone.',
            onConfirm: function() {
                SaveData('utility/user_delete.php', 'fdata', '&userid=' + ids.join(','), 'utility/user_mgmt.php', 'main');
            }
        });
    }

    function openUpdateModal(btn) {
        if (IS_GUEST) {
            alert('Access denied. Guest accounts cannot edit users.');
            return;
        }
        var tr = btn.closest('tr');
        document.getElementById('uf-userid').value   = tr.dataset.id;
        document.getElementById('uf-fname').value    = tr.dataset.fname;
        document.getElementById('uf-lname').value    = tr.dataset.lname;
        document.getElementById('uf-user').value     = tr.dataset.user;
        document.getElementById('uf-ini').value      = tr.dataset.ini;
        document.getElementById('uf-acctlvl').value  = tr.dataset.acctlvl;
        document.getElementById('um-update-modal').style.display = 'flex';
    }

    function closeUpdateModal() {
        document.getElementById('um-update-modal').style.display = 'none';
    }
</script>

<form id="fdata" autocomplete="off" style="height:100%; display:flex; flex-direction:column;">

  <!-- Page header -->
  <div class="um-header">
    <div class="um-header-left">
      <h2>User Management</h2>
      <p>Manage system accounts and access levels</p>
    </div>
    <div class="um-header-right">

      <div class="tbl-search-wrap">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        <input type="text" class="tbl-search" placeholder="Search users..."
          oninput="tableSearch(this, 'um-table-main')">
      </div>

      <button type="button" class="um-btn-add" onclick="popdiv('div_reg_user', 'utility/user_reg.php')">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Register User
      </button>

      <button type="button" class="um-btn-del-multi" id="btn-delete-selected" onclick="deleteSelected()" disabled>
        <i class="fa fa-trash"></i>
        Delete Selected (<span id="delete-count">0</span>)
      </button>

    </div>
  </div>

  <!-- Table -->
  <div class="um-body">
    <div class="um-table-wrap">
      <table class="um-table" id="um-table-main">
        <thead>
          <tr>
            <th style="width:40px; text-align:center;">
              <input type="checkbox" class="um-row-check" onchange="toggleSelectAll(this)" title="Select all" style="width:15px;height:15px;accent-color:#fff;">
            </th>
            <th style="width:44px; text-align:center;">No.</th>
            <th>First Name</th>
            <th>Last Name</th>
            <th>Username</th>
            <th style="text-align:center;">Account Level</th>
            <th style="text-align:center;">Initial</th>
            <th>Last Logged</th>
            <th style="width:90px; text-align:center;">Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php
           // NEW
              $currentUser    = $_SESSION['cred_user'] ?? '';
$isCurrentAdmin = $currentAcctLvl === 'admin';

if ($isCurrentAdmin) {
    $sql  = "SELECT id, fname, lname, [user], acctlvl, ini, logdate FROM [dbo].[tbl_users] ORDER BY id DESC";
    $stmt = sqlsrv_query($conn, $sql);
} else {
    $sql    = "SELECT id, fname, lname, [user], acctlvl, ini, logdate FROM [dbo].[tbl_users] WHERE [user] = ? ORDER BY id DESC";
    $params = array($currentUser);
    $stmt   = sqlsrv_query($conn, $sql, $params);
}
              if ($stmt === false) {
                echo "<tr><td colspan='9'><div class='um-empty'>SQL Error: " . print_r(sqlsrv_errors(), true) . "</div></td></tr>";
            } else {
                $i = 1;
                $hasRows = false;
                while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                    $hasRows = true;

                    $fname   = htmlspecialchars($row['fname']   ?? '');
                    $lname   = htmlspecialchars($row['lname']   ?? '');
                    $user    = htmlspecialchars($row['user']    ?? '');
                    $acctlvl = htmlspecialchars($row['acctlvl'] ?? '');
                    $ini     = htmlspecialchars($row['ini']     ?? '');

                    $date = '';
                    if (!empty($row['logdate'])) {
                        $dateObj = $row['logdate'];
                        if ($dateObj instanceof DateTime) {
                            $date = $dateObj->format('Y-m-d h:i A');
                        }
                    }

                    $isAdmin    = strtolower($row['acctlvl'] ?? '') === 'admin';
                    $badgeClass = $isAdmin ? 'acct-badge' : 'acct-badge guest';

                    // ✅ Build edit button based on session role
                    $editBtn = $isGuest
                        ? '<button type="button" class="um-btn-icon warning" title="Not allowed" disabled style="opacity:0.35; cursor:not-allowed;"><i class="fa fa-pencil"></i></button>'
                        : '<button type="button" class="um-btn-icon warning" title="Update User" onclick="openUpdateModal(this)"><i class="fa fa-pencil"></i></button>';

                    echo '
                    <tr id="tr_' . $row['id'] . '"
                        data-id="'      . $row['id'] . '"
                        data-fname="'   . $fname     . '"
                        data-lname="'   . $lname     . '"
                        data-user="'    . $user      . '"
                        data-acctlvl="' . $acctlvl   . '"
                        data-ini="'     . $ini       . '">
                        <td class="center">
                          <input type="checkbox" class="um-row-check" value="' . $row['id'] . '" onchange="updateDeleteBtn()">
                        </td>
                        <td class="td-num">' . $i . '</td>
                        <td>' . $fname   . '</td>
                        <td>' . $lname   . '</td>
                        <td style="color:#555;">' . $user . '</td>
                        <td class="center"><span class="' . $badgeClass . '">' . $acctlvl . '</span></td>
                        <td class="center" style="color:#555; font-weight:500;">' . $ini . '</td>
                        <td class="td-date">' . $date . '</td>
                        <td>
                          <div class="um-actions">
                            ' . $editBtn . '
                            <button type="button" class="um-btn-icon" title="Change Password"
                              onclick="PageLink(\'utility/user_change_password.php\', \'div_reg_user\', \'fdata\', \'&userid=' . $row['id'] . '&acctlvl=' . urlencode($row['acctlvl'] ?? '') . '\'); $(\'#div_dentry\').show();">
                              <i class="fa fa-key"></i>
                            </button>
                          </div>
                        </td>
                    </tr>';
                    $i++;
                }

                if (!$hasRows) {
                    echo '<tr><td colspan="9"><div class="um-empty">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        <p>No users found</p>
                    </div></td></tr>';
                }
            }
            sqlsrv_free_stmt($stmt);
            sqlsrv_close($conn);
        ?>
        </tbody>
      </table>
    </div>
  </div>

</form>

<div id="div_dentry" class="popdiv">
  <div id="div_reg_user" class="card-center"></div>
</div>

<div id="divAuth" class="popdiv2"></div>

<!-- ── Update User Modal ── -->
<div id="um-update-modal" style="display:none; position:fixed; inset:0; z-index:99998; background:rgba(0,0,0,0.35); align-items:center; justify-content:center;">
  <div style="background:#fff; border-radius:12px; width:460px; max-width:92vw; overflow:hidden; font-family:'Inter',sans-serif; box-shadow:0 8px 32px rgba(0,0,0,0.14);">

    <!-- Header -->
    <div style="display:flex; align-items:center; justify-content:space-between; padding:16px 20px 14px; border-bottom:1px solid #e4e9e4;">
      <div style="display:flex; align-items:center; gap:10px;">
        <div style="width:34px;height:34px;border-radius:8px;background:#edf7f1;display:flex;align-items:center;justify-content:center;">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#1e6b3c" stroke-width="1.8"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        </div>
        <div>
          <div style="font-size:14px;font-weight:600;color:#111;">Update User</div>
          <div style="font-size:11px;color:#aaa;">Edit account details below</div>
        </div>
      </div>
      <button onclick="closeUpdateModal()" style="width:28px;height:28px;border-radius:6px;border:1px solid #e4e9e4;background:#fff;display:flex;align-items:center;justify-content:center;cursor:pointer;color:#aaa;transition:background .12s;" onmouseover="this.style.background='#fff5f5';this.style.color='#c0392b'" onmouseout="this.style.background='#fff';this.style.color='#aaa'">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>

    <!-- Form body -->
    <form id="fupdate" autocomplete="off">
      <input type="hidden" id="uf-userid" name="userid">

      <div style="padding:18px 20px; display:flex; flex-direction:column; gap:14px;">

        <!-- First & Last name -->
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
          <div style="display:flex;flex-direction:column;gap:5px;">
            <label style="font-size:11px;font-weight:600;color:#666;letter-spacing:0.06em;text-transform:uppercase;">First Name</label>
            <input type="text" id="uf-fname" name="fname" class="uf-input input-capitalize" required>
          </div>
          <div style="display:flex;flex-direction:column;gap:5px;">
            <label style="font-size:11px;font-weight:600;color:#666;letter-spacing:0.06em;text-transform:uppercase;">Last Name</label>
            <input type="text" id="uf-lname" name="lname" class="uf-input input-capitalize" required>
          </div>
        </div>

        <!-- Username & Initial -->
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
          <div style="display:flex;flex-direction:column;gap:5px;">
            <label style="font-size:11px;font-weight:600;color:#666;letter-spacing:0.06em;text-transform:uppercase;">Username</label>
            <input type="text" id="uf-user" name="user" class="uf-input" required>
          </div>
          <div style="display:flex;flex-direction:column;gap:5px;">
            <label style="font-size:11px;font-weight:600;color:#666;letter-spacing:0.06em;text-transform:uppercase;">Initial</label>
            <input type="text" id="uf-ini" name="ini" class="uf-input" maxlength="10" style="text-transform:uppercase;" required>
          </div>
        </div>

        <!-- Account Level -->
        <div style="display:flex;flex-direction:column;gap:5px;">
          <label style="font-size:11px;font-weight:600;color:#666;letter-spacing:0.06em;text-transform:uppercase;">Account Level</label>
          <select id="uf-acctlvl" name="acctlvl" class="uf-select">
            <?php
              $levels = ['Admin','Guest','Engineering','Metering',
                         'CAO Technical','NAO Technical','EAO Technical',
                         'SEAO Technical','SAO Technical','CAO Crew',
                         'NAO Crew','EAO Crew','SEAO Crew','SAO Crew',
                         'CAO CWA','NAO CWA','EAO CWA','SEAO CWA',
                         'SAO CWA','GM','BOD','MOTORPOOL','For Testing',
                         'CAO TD ENGR','NAO TD ENGR','EAO TD ENGR','SEAO TD ENGR','SAO TD ENGR',
                         'Billing','Main Office Vehicle','CAO PUI','NAO PUI','EAO PUI','SEAO PUI',
                         'SAO PUI','Net Metering'];
              foreach ($levels as $lvl) {
                  echo '<option value="' . $lvl . '">' . $lvl . '</option>';
              }
            ?>
          </select>
        </div>

      </div>

      <!-- Footer -->
      <div style="display:flex;align-items:center;justify-content:flex-end;gap:8px;padding:14px 20px 18px;border-top:1px solid #f0f2f0;">
        <button type="button" class="uf-btn-cancel" onclick="closeUpdateModal()">Cancel</button>
        <button type="button" class="uf-btn-save"
          onclick="SaveData('utility/user_update.php','fupdate','','utility/user_mgmt.php','main'); closeUpdateModal();">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
          Save Changes
        </button>
      </div>

    </form>
  </div>
</div>