<?php include '../js/fphp.php'; ?>
<?php include '../conn.php'; ?>
<?php
  $currentLevel = $_SESSION['user_level'] ?? '';
  $isAdmin = strtolower($currentLevel) === 'admin';
?>

<link rel="stylesheet" href="css/g.css">
<link rel="stylesheet" href="css/font-awesome-4.7.0/css/font-awesome.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<script src="js/search.js"></script>

<script>
  /* ── Select all ── */
  function toggleSelectAll(source) {
    document.querySelectorAll('.ur-row-check').forEach(function(cb) {
      cb.checked = source.checked;
    });
    updateDeleteBtn();
  }

  /* ── Enable/disable delete btn ── */
  function updateDeleteBtn() {
    var count = document.querySelectorAll('.ur-row-check:checked').length;
    var btn   = document.getElementById('ur-btn-delete');
    document.getElementById('ur-delete-count').textContent = count;
    btn.disabled = count === 0;
  }

  /* ── Multi delete ── */
  function deleteSelected() {
    var checked = document.querySelectorAll('.ur-row-check:checked');
    if (checked.length === 0) return;

    var trns = [];
    checked.forEach(function(cb) { trns.push(cb.value); });

    showConfirm({
      title:   'Delete Account Levels',
      message: 'Are you sure you want to delete ' + checked.length + ' selected level(s)? This cannot be undone.',
      onConfirm: function() {
        SaveData('utility/userrights_delete.php', 'frdataadd', '&trn=' + trns.join(','), 'utility/userrights.php', 'main');
      }
    });
  }
</script>

<!-- ── Page Header ── -->
<div class="ur-header">
  <div class="ur-header-left">
    <h2>User Rights Management</h2>
    <p>Configure access permissions per account level</p>
  </div>
</div>

<!-- ── Add Record Bar ── -->
<form id="frdataadd" autocomplete="off">
  <div class="ur-add-bar">

    <?php if ($isAdmin): ?>
    <input type="text" class="ur-add-input" id="t_level" name="t_level" placeholder="New Account Level" required>
    <button type="button" class="um-btn-add"
      onclick="
        var val = document.getElementById('t_level').value.trim();
        if (!val) { document.getElementById('t_level').focus(); return; }
        SaveData('utility/userrights_add.php','frdataadd','','utility/userrights.php','main');
      ">Add Rights
    </button>
    <?php endif; ?>

    <div style="margin-left:auto; display:flex; align-items:center; gap:10px;">
      <div class="tbl-search-wrap">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        <input type="text" class="tbl-search" placeholder="Search levels..."
          oninput="tableSearch(this, 'ur-table-main', [1])">
      </div>

      <?php if ($isAdmin): ?>
      <button type="button" class="um-btn-del-multi" id="ur-btn-delete" onclick="deleteSelected()" disabled>
        <i class="fa fa-trash"></i>
        Delete Selected (<span id="ur-delete-count">0</span>)
      </button>

      <button type="button" class="uf-btn-save"
        onclick="SaveData('utility/userrights_update.php','frdata','','utility/userrights.php','main');">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
        Update All Records
      </button>
      <?php endif; ?>
    </div>

  </div>
</form>

<!-- ── Rights Table ── -->
<form id="frdata" autocomplete="off">
  <div class="ur-body">
    <div class="ur-table-wrap">
      <table class="ur-table" id="ur-table-main">
        <thead>
          <tr class="ur-group">
            <th rowspan="2" style="width:40px; text-align:center;">
              <input type="checkbox" onchange="toggleSelectAll(this)"
                style="width:14px;height:14px;accent-color:#fff;cursor:pointer;">
            </th>
            <th rowspan="2" style="text-align:left; padding-left:10px;">Account Level</th>
            <th colspan="3">Search</th>
            <th colspan="6">Viewing of Vehicles</th>
            <th colspan="9">Editing</th>
            <th colspan="4">Utilities Management</th>
            <th colspan="4">Download Reports</th>
            
          </tr>
          <tr class="ur-sub">
            <th class="group-start">Cust</th>
            <th>Elem</th>
            <th>Is Crew</th>
            <th class="group-start">Main Office</th>
            <th>Central</th>
            <th>Northern</th>
            <th>Eastern</th>
            <th>SE Area</th>
            <th>Southern</th>
            <th class="group-start">Locations</th>
            <th>Metering</th>
            <th>CNR</th>
            <th>Poles (All)</th>
            <th>Poles Central</th>
            <th>Poles Northern</th>
            <th>Poles Eastern</th>
            <th>Poles SE</th>
            <th>Poles Southern</th>
            <th class="group-start">User</th>
            <th>Vehicle</th>
            <th>Personnel</th>
            <th>CNR</th>
            <th class="group-start">Master</th>
            <th>CNR</th>
            <th>Cust BRGY</th>
            <th>Cust TOWN</th>
          </tr>
        </thead>
        <tbody>
       <?php
  $currentLevel = $_SESSION['user_level'] ?? '';
  $isAdmin = strtolower($currentLevel) === 'admin';

  if ($isAdmin) {
      $sql  = "SELECT * FROM tbl_userrights ORDER BY trn DESC";
      $stmt = sqlsrv_query($conn, $sql);
  } else {
      $sql    = "SELECT * FROM tbl_userrights WHERE level = ? ORDER BY trn DESC";
      $params = [[$currentLevel, SQLSRV_PARAM_IN]];
      $stmt   = sqlsrv_query($conn, $sql, $params);
  }

  $x = 1;
  if ($stmt !== false) {
              while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                  echo "<tr>";

                  /* Checkbox cell */
                  echo "<td style='text-align:center;'>
                          <input type='checkbox' class='ur-row-check ur-check'
                            value='{$row['trn']}' onchange='updateDeleteBtn()'>
                        </td>";

                  /* Level cell */
                  echo "<td style='text-align:left; padding-left:10px;'>
                          <input type='hidden' name='trn[{$x}]' value='{$row['trn']}'>
                          <input type='text' class='ur-level-input' name='level[{$x}]'
                            value='" . htmlspecialchars($row['level'] ?? '') . "'>
                        </td>";

                  // NEW
// NEW
$fields = [
    'search_customer',           // 0
    'search_elements',           // 1
    'is_crew',                   // 2
    'main_area',                 // 3  ← group-start: Viewing of Vehicles
    'central_area',              // 4
    'northern_area',             // 5
    'eastern_area',              // 6
    'southeastern_area',         // 7
    'southern_area',             // 8
    'edit_locations',            // 9  ← group-start: Editing
    'edit_metering_data',        // 10
    'edit_complaints_request',   // 11
    'edit_elements_pole',        // 12  Poles All
    'edit_poles_central',        // 13
    'edit_poles_northern',       // 14
    'edit_poles_eastern',        // 15
    'edit_poles_southeastern',   // 16
    'edit_poles_southern',       // 17
    'utilities_user',            // 18 ← group-start: Utilities
    'utilities_vehicle',         // 19
    'utilities_personnel',       // 20
    'utilities_complaints_request', // 21
    'download_master_data',      // 22 ← group-start: Download
    'download_complaints_request_data', // 23
    'download_cust_brgy',        // 24
    'download_cust_town'         // 25
];

$groupStarts = [0, 3, 9, 18, 22];
                  

                  foreach ($fields as $idx => $field) {
                      $checked    = $row[$field] ? 'checked' : '';
                      $groupClass = in_array($idx, $groupStarts) ? ' class="group-start"' : '';
                      echo "<td{$groupClass}>
                              <input type='checkbox' class='ur-check'
                                name='{$field}[{$x}]' value='1' {$checked}>
                            </td>";
                  }

                  echo "</tr>";
                  $x++;
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