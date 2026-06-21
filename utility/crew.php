<?php include '../js/fphp.php'; ?>
<?php include '../conn.php'; ?>
<link rel="stylesheet" href="css/g.css">
<link rel="stylesheet" href="css/font-awesome-4.7.0/css/font-awesome.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<script src="js/search.js"></script>

<script>
  function toggleSelectAll(source) {
    document.querySelectorAll('.cr-row-check').forEach(function(cb) {
      cb.checked = source.checked;
    });
    updateDeleteBtn();
  }

  function updateDeleteBtn() {
    var count = document.querySelectorAll('.cr-row-check:checked').length;
    document.getElementById('cr-delete-count').textContent = count;
    document.getElementById('cr-btn-delete').disabled = count === 0;
  }

  function deleteSelected() {
    var checked = document.querySelectorAll('.cr-row-check:checked');
    if (checked.length === 0) return;
    var trns = [];
    checked.forEach(function(cb) { trns.push(cb.value); });
    showConfirm({
      title:   'Delete Crew',
      message: 'Are you sure you want to delete ' + checked.length + ' selected crew member(s)? This cannot be undone.',
      onConfirm: function() {
        SaveData('utility/crew_delete.php', 'frdataadd', '&trn=' + trns.join(','), 'utility/crew.php', 'main');
      }
    });
  }
</script>

<!-- ── Page Header ── -->
<div class="ur-header">
  <div class="ur-header-left">
    <h2>Crew Utility</h2>
    <p>Manage crew members and area assignments</p>
  </div>
</div>

<!-- ── Add Record Bar ── -->
<form id="frdataadd" autocomplete="off">
  <div class="ur-add-bar">

    <div class="ur-add-pair">
      <label for="t_crew">Crew</label>
      <input type="text" class="ur-add-input" id="t_crew" name="t_crew" placeholder="e.g. Juan Dela Cruz">
    </div>

    <div class="ur-add-pair">
      <label for="t_plateno">Plate No.</label>
      <input type="text" class="ur-add-input" id="t_plateno" name="t_plateno" placeholder="e.g. AAA111">
    </div>

    <button type="button" class="um-btn-add"
      onclick="SaveData('utility/crew_add.php','frdataadd','','utility/crew.php','main');">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      Add Crew
    </button>

    <div class="ur-add-actions">
      <div class="tbl-search-wrap">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        <input type="text" class="tbl-search" placeholder="Search crew..." aria-label="Search crew"
          oninput="tableSearch(this, 'cr-table-main', [2, 3, 4])">
      </div>

      <button type="button" class="um-btn-del-multi" id="cr-btn-delete" onclick="deleteSelected()" disabled>
        <i class="fa fa-trash"></i>
        Delete Selected (<span id="cr-delete-count">0</span>)
      </button>

      <button type="button" class="uf-btn-save"
        onclick="SaveData('utility/crew_update.php','frdata','','utility/crew.php','main');">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
        Update All Records
      </button>
    </div>

  </div>
</form>

<!-- ── Crew Table ── -->
<form id="frdata" autocomplete="off">
  <div class="ur-body">
    <div class="ur-table-wrap">
      <table class="ur-table" id="cr-table-main">
        <thead>
          <tr class="ur-group">
            <th rowspan="2" style="width:40px; text-align:center;">
              <input type="checkbox" onchange="toggleSelectAll(this)"
                aria-label="Select all crew members"
                style="width:14px;height:14px;accent-color:#fff;cursor:pointer;">
            </th>
            <th rowspan="2" style="text-align:center; width:40px;">#</th>
            <th rowspan="2" style="text-align:left; padding-left:10px;">Crew</th>
            <th rowspan="2" style="text-align:left; padding-left:10px;">Plate No.</th>
            <th rowspan="2" style="text-align:left; padding-left:10px;">Tracker ID</th>
            <th colspan="6">Area Assignment</th>
          </tr>
          <tr class="ur-sub">
            <th class="group-start">Main</th>
            <th>Central</th>
            <th>Northern</th>
            <th>Eastern</th>
            <th>Southeastern</th>
            <th>Southern</th>
          </tr>
        </thead>
        <tbody>
        <?php
           $sql = "SELECT * FROM tbl_crew ORDER BY trn DESC";
          $stmt = sqlsrv_query($conn, $sql);
          $x    = 1;
          if ($stmt !== false) {
              $hasRows = false;
              while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                  $hasRows = true;
                  $crewName = htmlspecialchars($row['crew'] ?? '');
                  echo "<tr>";

                  /* Checkbox */
                  echo "<td style='text-align:center;'>
                          <input type='checkbox' class='cr-row-check ur-check'
                            value='{$row['trn']}' onchange='updateDeleteBtn()'
                            aria-label='Select " . ($crewName !== '' ? $crewName : 'crew member') . "'>
                        </td>";

                  /* Row number */
                  echo "<td style='text-align:center; color:#bbb; font-size:12px;'>{$x}</td>";

                  /* Crew name */
                  echo "<td style='text-align:left; padding-left:10px;'>
                          <input type='hidden' name='trn[{$x}]' value='{$row['trn']}'>
                          <input type='text' class='ur-level-input cr-input-name'
                            name='crew[{$x}]' value='{$crewName}'
                            aria-label='Crew name'>
                        </td>";

                  /* Plate number */
                  echo "<td style='text-align:left; padding-left:10px;'>
                          <input type='text' class='ur-level-input cr-input-plate'
                            name='plateno[{$x}]' value='" . htmlspecialchars($row['plateno'] ?? '') . "'
                            aria-label='Plate number'>
                        </td>";

                  /* Tracker ID */
                  echo "<td style='text-align:left; padding-left:10px;'>
                          <input type='text' class='ur-level-input cr-input-tracker'
                            name='tracker_id[{$x}]' value='" . htmlspecialchars($row['tracker_id'] ?? '') . "'
                            aria-label='Tracker ID'>
                        </td>";

                  /* Area checkboxes */
                  $fields = [
                      'main_area_office'         => 'Main Area Office',
                      'central_area_office'      => 'Central Area Office',
                      'northern_area_office'     => 'Northern Area Office',
                      'eastern_area_office'      => 'Eastern Area Office',
                      'southeastern_area_office' => 'Southeastern Area Office',
                      'southern_area_office'     => 'Southern Area Office',
                  ];

                  $idx = 0;
                  foreach ($fields as $field => $areaLabel) {
                      $checked    = $row[$field] ? 'checked' : '';
                      $groupClass = $idx === 0 ? ' class="group-start"' : '';
                      echo "<td{$groupClass}>
                              <input type='checkbox' class='ur-check'
                                name='{$field}[{$x}]' value='1' {$checked}
                                aria-label='" . ($crewName !== '' ? $crewName . ' — ' : '') . $areaLabel . "'>
                            </td>";
                      $idx++;
                  }

                  echo "</tr>";
                  $x++;
              }

              if (!$hasRows) {
                  echo '<tr><td colspan="11"><div class="um-empty">
                      <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                      <p>No crew members found</p>
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