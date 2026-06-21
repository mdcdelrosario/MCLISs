<?php include '../js/fphp.php'; ?>
<?php include '../conn.php'; ?>
<link rel="stylesheet" href="css/g.css">
<link rel="stylesheet" href="css/font-awesome-4.7.0/css/font-awesome.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<script src="js/search.js"></script>

<script>
  function toggleSelectAll(source) {
    document.querySelectorAll('.cnr-row-check').forEach(function(cb) {
      cb.checked = source.checked;
    });
    updateDeleteBtn();
  }

  function updateDeleteBtn() {
    var count = document.querySelectorAll('.cnr-row-check:checked').length;
    document.getElementById('cnr-delete-count').textContent = count;
    document.getElementById('cnr-btn-delete').disabled = count === 0;
  }

  function deleteSelected() {
    var checked = document.querySelectorAll('.cnr-row-check:checked');
    if (checked.length === 0) return;
    var trns = [];
    checked.forEach(function(cb) { trns.push(cb.value); });
    showConfirm({
      title:   'Delete Records',
      message: 'Are you sure you want to delete ' + checked.length + ' selected complaint/request record(s)? This cannot be undone.',
      onConfirm: function() {
        SaveData('utility/cnr_delete.php', 'frdataadd', '&trn=' + trns.join(','), 'utility/cnr.php', 'main');
      }
    });
  }
</script>

<!-- ── Page Header ── -->
<div class="ur-header">
  <div class="ur-header-left">
    <h2>Complaints / Requests</h2>
    <p>Manage complaint and request type records</p>
  </div>
</div>

<!-- ── Add Record Bar ── -->
<form id="frdataadd" autocomplete="off">
  <div class="ur-add-bar">
    <input type="text" class="ur-add-input" style="width:280px;"
      id="t_request" name="t_request" placeholder="Complaint or Request Type" required>
    <button type="button" class="um-btn-add"
      onclick="SaveData('utility/cnr_add.php','frdataadd','','utility/cnr.php','main');">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      Add Record
    </button>

    <div style="margin-left:auto; display:flex; align-items:center; gap:10px;">
      <div class="tbl-search-wrap">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        <input type="text" class="tbl-search" placeholder="Search records..."
          oninput="tableSearch(this, 'cnr-table-main', [1])">
      </div>

      <button type="button" class="um-btn-del-multi" id="cnr-btn-delete" onclick="deleteSelected()" disabled>
        <i class="fa fa-trash"></i>
        Delete Selected (<span id="cnr-delete-count">0</span>)
      </button>

      <button type="button" class="uf-btn-save"
        onclick="SaveData('utility/cnr_update.php','frdata','','utility/cnr.php','main');">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
        Update All Records
      </button>
    </div>
  </div>
</form>

<!-- ── Records Table ── -->
<form id="frdata" autocomplete="off">
  <div class="ur-body">
    <div class="ur-table-wrap">
      <table class="ur-table" id="cnr-table-main">
        <thead>
          <tr class="ur-group">
            <th rowspan="2" style="width:40px; text-align:center;">
              <input type="checkbox" onchange="toggleSelectAll(this)"
                style="width:14px;height:14px;accent-color:#fff;cursor:pointer;">
            </th>
            <th rowspan="2" style="text-align:center; width:40px;">#</th>
            <th rowspan="2" style="text-align:left; padding-left:10px;">Complaint / Request</th>
          </tr>
        </thead>
        <tbody>
        <?php
          $sql  = "SELECT * FROM tbl_request ORDER BY request";
          $stmt = sqlsrv_query($conn, $sql);
          $x    = 1;
          if ($stmt !== false) {
              while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                  echo "<tr>";

                  /* Checkbox */
                  echo "<td style='text-align:center;'>
                          <input type='checkbox' class='cnr-row-check ur-check'
                            value='{$row['trn']}' onchange='updateDeleteBtn()'>
                        </td>";

                  /* Row number */
                  echo "<td style='text-align:center; color:#bbb; font-size:12px;'>{$x}</td>";

                  /* Request name */
                  echo "<td style='text-align:left; padding-left:10px;'>
                          <input type='hidden' name='trn[{$x}]' value='{$row['trn']}'>


