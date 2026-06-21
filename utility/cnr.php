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
      title:   'Delete CNR Records',
      message: 'Are you sure you want to delete ' + checked.length + ' selected complaints/requests? This cannot be undone.',
      onConfirm: function() {
        SaveData('utility/cnr_delete.php', 'frdataadd', '&trn=' + trns.join(','), 'utility/cnr.php', 'main');
      }
    });
  }

  function viewDetails(trn) {
    popdiv('cnr-detail-modal', 'comnreq.php?trn=' + trn);
  }
</script>

<!-- ── Page Header ── -->
<div class="ur-header">
  <div class="ur-header-left">
    <h2>Complaints Type</h2>
  </div>
</div>

<!-- ── Add Record Bar ── -->
<form id="frdataadd" autocomplete="off">
  <div class="ur-add-bar">
    <input type="text" class="ur-add-input" style="width:280px;"
      id="t_request" name="t_request" placeholder="Complaint Type" required>
    <button type="button" class="um-btn-add"
      onclick="SaveData('utility/cnr_add.php','frdataadd','','utility/cnr.php','main');">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      Add Record
    </button>

    <div style="margin-left:auto; display:flex; align-items:center; gap:10px;">
      <div class="tbl-search-wrap">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        <input type="text" class="tbl-search" placeholder="Search records..."
          oninput="tableSearch(this, 'cnr-table-main', [2,3])">
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
      <table class="um-table" id="cnr-table-main">
        <thead>
          <tr>
            <th style="width:40px; text-align:center;">
              <input type="checkbox" onchange="toggleSelectAll(this)"
                style="width:14px;height:14px;accent-color:#fff;cursor:pointer;">
            </th>
            <th style="width:44px; text-align:center;">No.</th>
            <th>Complaint / Request</th>
          </tr>
        </thead>
        <tbody>
        <?php
          $sql  = "SELECT * FROM tbl_request ORDER BY trn DESC";
          $stmt = sqlsrv_query($conn, $sql);

          if ($stmt === false) {
              echo "<tr><td colspan='3'><div class='um-empty'>
                      <svg width='28' height='28' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='1.2'><path d='M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z'/></svg>
                      <p>Failed to load records</p>
                    </div></td></tr>";
          } else {
              $x       = 1;
              $hasRows = false;
              while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                  $hasRows = true;
                  echo "<tr>
                    <td class='center'>
                      <input type='checkbox' class='cnr-row-check um-row-check'
                        value='{$row['trn']}' onchange='updateDeleteBtn()'>
                    </td>
                    <td class='td-num'>{$x}</td>
                    <td style='padding-left:10px;'>
                      <input type='text' class='ur-level-input' style='width:100%; min-width:200px;'
                        name='treq{$x}' id='treq{$x}'
                        value='" . htmlspecialchars($row['request']) . "'>
                      <input type='hidden' name='trn{$x}' value='{$row['trn']}'>
                    </td>
                  </tr>";
                  $x++;
              }

              if (!$hasRows) {
                  echo "<tr><td colspan='3'><div class='um-empty'>
                          <svg width='28' height='28' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='1.2'><path d='M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z'/></svg>
                          <p>No records found</p>
                        </div></td></tr>";
              }

              sqlsrv_free_stmt($stmt);
          }

          sqlsrv_close($conn);
        ?>
        </tbody>
      </table>
    </div>
  </div>
</form>