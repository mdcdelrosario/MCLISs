<?php include '../js/fphp.php'; ?>
<?php include '../conn.php'; ?>
<link rel="stylesheet" href="css/g.css">
<link rel="stylesheet" href="css/font-awesome-4.7.0/css/font-awesome.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">

<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="js/search.js"></script>

<script>
  function toggleSelectAll(source) {
    document.querySelectorAll('.mb-row-check').forEach(function(cb) {
      cb.checked = source.checked;
    });
    updateDeleteBtn();
  }

  function updateDeleteBtn() {
    var count = document.querySelectorAll('.mb-row-check:checked').length;
    document.getElementById('mb-delete-count').textContent = count;
    document.getElementById('mb-btn-delete').disabled = count === 0;
  }

  function deleteSelected() {
    var checked = document.querySelectorAll('.mb-row-check:checked');
    if (checked.length === 0) return;
    var trns = [];
    checked.forEach(function(cb) { trns.push(cb.value); });
    showConfirm({
      title:   'Delete Personnel',
      message: 'Are you sure you want to delete ' + checked.length + ' selected personnel record(s)? This cannot be undone.',
      onConfirm: function() {
        SaveData('utility/member_delete.php', 'frdataadd', '&trn=' + trns.join(','), 'utility/member.php', 'main');
      }
    });
  }

  function openUpdateModal(btn) {
    var tr = btn.closest('tr');
    document.getElementById('mb-trn').value   = tr.dataset.trn;
    document.getElementById('mb-name').value  = tr.dataset.name;
    document.getElementById('mb-crew').value  = tr.dataset.crew;
    document.getElementById('mb-update-modal').style.display = 'flex';
  }

  function closeUpdateModal() {
    document.getElementById('mb-update-modal').style.display = 'none';
  }</script>

<!-- ── Page Header ── -->
<div class="ur-header">
  <div class="ur-header-left">
    <h2>Personnel Utility</h2>
    <p>Manage personnel records and crew assignments</p>
  </div>
</div>

<!-- ── Add Record Bar ── -->
<form id="frdataadd" autocomplete="off">
  <div class="ur-add-bar" style="flex-wrap:wrap; gap:10px;">

    <!-- Left: inputs -->
    <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
      <label>Personnel Name</label>
      <input type="text" class="ur-add-input" id="tname" name="tname" placeholder="e.g. Juan Dela Cruz">

      <label>Crew</label>
      <select name="tcrew" id="tcrew" class="uf-select" style="width:180px;">
        <option value="">— Select Crew —</option>
        <?php
          $crewsql  = "SELECT DISTINCT crew FROM tbl_crew ORDER BY crew";
          $crewstmt = sqlsrv_query($conn, $crewsql);
          if ($crewstmt !== false) {
              while ($crow = sqlsrv_fetch_array($crewstmt, SQLSRV_FETCH_ASSOC)) {
                  $c = htmlspecialchars($crow['crew']);
                  echo "<option value='$c'>$c</option>";
              }
          }
        ?>
      </select>

      <button type="button" class="um-btn-add"
        onclick="SaveData('utility/member_add.php','frdataadd','','utility/member.php','main');">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Add Record
      </button>

      <button type="button" class="um-btn-add" onclick="document.getElementById('importExcelInput').click();"
  style="background:#16a34a;">
  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="8 17 12 21 16 17"/><line x1="12" y1="12" x2="12" y2="21"/><path d="M20.88 18.09A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.29"/></svg>
  Import Excel
</button>
<input type="file" id="importExcelInput" accept=".xlsx,.xls" style="display:none"
  onchange="handleExcelImport(this)">
    </div>

    <!-- Right: search + delete -->
    <div style="margin-left:auto; display:flex; align-items:center; gap:10px;">
      <div class="tbl-search-wrap">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        <input type="text" class="tbl-search" placeholder="Search personnel..."
          oninput="tableSearch(this, 'mb-table-main', [2, 3])">
      </div>

      <button type="button" class="um-btn-del-multi" id="mb-btn-delete" onclick="deleteSelected()" disabled>
        <i class="fa fa-trash"></i>
        Delete Selected (<span id="mb-delete-count">0</span>)
      </button>

      <!-- MAKE SURE it says frdata, not frdataadd -->
<button type="button" class="uf-btn-save"
    onclick="SaveData('utility/member_update.php','frdata','','utility/member.php','main');">
    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
    Update All Records
</button>
    </div>

  </div>
</form>

<!-- ── Personnel Table ── -->
<form id="frdata" autocomplete="off">
  <div class="ur-body">
    <div class="ur-table-wrap">
      <table class="um-table" id="mb-table-main">
        <thead>
          <tr>
            <th style="width:40px; text-align:center;">
              <input type="checkbox" onchange="toggleSelectAll(this)"
                style="width:14px;height:14px;accent-color:#fff;cursor:pointer;">
            </th>
            <th style="width:44px; text-align:center;">No.</th>
            <th>Personnel</th>
            <th>Crew</th>
          </tr>
        </thead>
        <tbody>
        <?php
           $sql = "SELECT * FROM tbl_person ORDER BY trn DESC";
          $stmt = sqlsrv_query($conn, $sql);
          $x    = 1;

          /* Fetch crew list for dropdowns */
          $crewOptions = '<option value="">— No Crew —</option>';
          $crewsql2    = "SELECT DISTINCT crew FROM tbl_crew ORDER BY crew";
          $crewstmt2   = sqlsrv_query($conn, $crewsql2);
          if ($crewstmt2 !== false) {
              while ($crow = sqlsrv_fetch_array($crewstmt2, SQLSRV_FETCH_ASSOC)) {
                  $c = htmlspecialchars($crow['crew']);
                  $crewOptions .= "<option value='$c'>$c</option>";
              }
          }

          if ($stmt !== false) {
              while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                  $name = htmlspecialchars($row['name']);
                  $crew = htmlspecialchars($row['crew']);

                  /* Build crew dropdown with current value selected */
                  $crewDropdown = str_replace(
                      "value='{$crew}'",
                      "value='{$crew}' selected",
                      $crewOptions
                  );

                  echo "<tr>
                    <td class='center'>
                      <input type='checkbox' class='mb-row-check um-row-check'
                        value='{$row['trn']}' onchange='updateDeleteBtn()'>
                    </td>
                    <td class='td-num'>{$x}</td>
                    <td style='padding-left:10px;'>
                      <input type='hidden' name='trn[{$x}]' value='{$row['trn']}'>
                      <input type='text' class='ur-level-input input-capitalize' style='width:100%;min-width:160px;'
                        name='name[{$x}]' value='{$name}'>
                    </td>
                    <td style='padding-left:10px;'>
                      <select name='crew[{$x}]' class='uf-select' style='width:100%;min-width:140px;height:30px;font-size:12px;'>
                        {$crewDropdown}
                      </select>
                    </td>
                  </tr>";

                  $x++;
              }
          } else {
              echo "<tr><td colspan='5'><div class='um-empty'>
                      <svg width='28' height='28' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='1.2'><path d='M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2'/><circle cx='12' cy='7' r='4'/></svg>
                      <p>No personnel records found</p>
                    </div></td></tr>";
          }

          sqlsrv_free_stmt($stmt);
          sqlsrv_close($conn);
        ?>
        </tbody>
      </table>
    </div>
  </div>
</form>

<script>
  function handleExcelImport(input) {
    var file = input.files[0];
    if (!file) return;
    input.value = ''; // reset so same file can be re-imported

    var reader = new FileReader();
    reader.onload = function(e) {
        var data     = new Uint8Array(e.target.result);
        var workbook = XLSX.read(data, { type: 'array' });
        var sheet    = workbook.Sheets[workbook.SheetNames[0]];
        var rows     = XLSX.utils.sheet_to_json(sheet, { defval: '' });

        if (!rows.length) {
            alert('No data found in the Excel file.');
            return;
        }

        // Normalize headers — accept "Name"/"name"/"NAME", "Crew"/"crew"/"CREW"
        var records = [];
        rows.forEach(function(row) {
            var name = '';
            var crew = '';
            Object.keys(row).forEach(function(k) {
                var key = k.trim().toLowerCase();
                if (key === 'name') name = String(row[k]).trim();
                if (key === 'crew') crew = String(row[k]).trim();
            });
            if (name !== '') records.push({ name: name, crew: crew });
        });

        if (!records.length) {
            alert('Could not find "Name" or "Crew" columns.\nMake sure your Excel headers are exactly: Name, Crew');
            return;
        }

        // Send to backend
        fetch('utility/member_import.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(records)
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            alert(
                'Import complete!\n' +
                'Inserted: ' + res.inserted + '\n' +
                'Skipped (duplicates): ' + res.skipped
            );
            // Reload the table
            if (typeof loadPage === 'function') {
                loadPage('utility/member.php', 'main');
            } else {
                location.reload();
            }
        })
        .catch(function(err) {
            alert('Import failed: ' + err);
        });
    };
    reader.readAsArrayBuffer(file);
}
</script>