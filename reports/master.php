<?php include '../js/fphp.php'; ?>
<?php include '../conn.php'; ?>
<link rel="stylesheet" href="css/g.css">
<link rel="stylesheet" href="css/font-awesome-4.7.0/css/font-awesome.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<script src="js/search.js"></script>

<?php
$p_route   = trim($_REQUEST['sroute']  ?? '');
$p_town    = trim($_REQUEST['stown']   ?? '');
$p_year    = trim($_REQUEST['syear']   ?? '');
$p_month   = trim($_REQUEST['smonth']  ?? '');
$p_day     = trim($_REQUEST['sday']    ?? '');
$do_search = isset($_REQUEST['do_search']) && $_REQUEST['do_search'] === '1';

// Fetch distinct AREA values
$area_options = [];
$area_sql  = "SELECT DISTINCT AREA FROM tbl_cust WHERE AREA IS NOT NULL AND AREA <> '' ORDER BY AREA";
$area_stmt = sqlsrv_query($conn, $area_sql);
if ($area_stmt) {
    while ($arow = sqlsrv_fetch_array($area_stmt, SQLSRV_FETCH_ASSOC)) {
        $area_options[] = $arow['AREA'];
    }
    sqlsrv_free_stmt($area_stmt);
}
?>

<style>
  /* ── existing styles ── */
  .ms-dl-grid { display: flex; flex-wrap: wrap; gap: 8px; }
  .ms-dl-btn {
    display: inline-flex; align-items: center; gap: 6px; height: 34px;
    padding: 0 14px; border-radius: 7px; border: 1px solid #e4e9e4;
    background: #fff; font-family: 'Inter', sans-serif; font-size: 12px;
    font-weight: 500; color: #444; cursor: pointer; white-space: nowrap;
    transition: background .12s, border-color .12s, color .12s; text-decoration: none;
  }
  .ms-dl-btn:hover { background: #f0f7f2; border-color: #b2d9be; color: #1e6b3c; }
  .ms-dl-btn svg   { color: #aaa; flex-shrink: 0; }
  .ms-dl-btn:hover svg { color: #1e6b3c; }
  .ms-table-wrap { background: #fff; border: 1px solid #e4e9e4; border-radius: 10px; overflow: hidden; }
  .ms-table-scroll { overflow-x: auto; overflow-y: auto; max-height: 520px; width: 100%; }
  .ms-table { width: 100%; border-collapse: collapse; font-size: 11.5px; white-space: nowrap; }
  .ms-table thead th {
    background: #1e6b3c; color: #fff; padding: 10px 12px; text-align: left;
    font-size: 10.5px; font-weight: 600; letter-spacing: 0.06em;
    text-transform: uppercase; position: sticky; top: 0; z-index: 2;
    border-right: 1px solid rgba(255,255,255,0.1);
  }
  .ms-table thead th:last-child { border-right: none; }
  .ms-table tbody tr { border-bottom: 1px solid #f0f2f0; transition: background .1s; }
  .ms-table tbody tr:last-child { border-bottom: none; }
  .ms-table tbody tr:hover { background: #f7fbf8; }
  .ms-table tbody td {
    padding: 8px 12px; color: #444; border-right: 1px solid #f4f4f4;
    max-width: 200px; overflow: hidden; text-overflow: ellipsis;
  }
  .ms-table tbody td:last-child { border-right: none; }
  .ms-empty { text-align: center; padding: 48px 20px; color: #aaa; }
  .ms-empty svg { margin-bottom: 10px; color: #ccc; display:block; margin-left:auto; margin-right:auto; }
  .ms-empty p   { font-size: 13px; font-weight: 500; color: #888; margin: 0 0 4px; }
  .ms-empty span { display:block; font-size:12px; color:#bbb; margin-top:4px; }
  .ur-add-select, .ur-add-input {
    height: 36px; padding: 0 10px; border: 1px solid #dde3dd; border-radius: 7px;
    font-family: 'Inter', sans-serif; font-size: 12.5px; color: #333;
    background: #fff; outline: none; transition: border-color .15s, box-shadow .15s;
    appearance: none; -webkit-appearance: none;
  }
  .ur-add-select:focus, .ur-add-input:focus {
    border-color: #1e6b3c; box-shadow: 0 0 0 3px rgba(30,107,60,.10);
  }
  .select-wrap { position: relative; display: inline-block; }
  .select-wrap::after {
    content: ''; pointer-events: none; position: absolute; right: 10px; top: 50%;
    transform: translateY(-50%); width: 0; height: 0;
    border-left: 4px solid transparent; border-right: 4px solid transparent;
    border-top: 5px solid #888;
  }
  .ms-filter-bar { display: flex; flex-direction: column; gap: 12px; }
  .ms-filter-row { display: flex; align-items: flex-end; flex-wrap: wrap; gap: 10px; }
  .ms-download-row { display: flex; flex-direction: column; gap: 6px; align-items: flex-start; }
  .ms-download-label { font-size: 10px; font-weight: 600; color: #888; letter-spacing: 0.08em; text-transform: uppercase; }
  .field-label { font-size:10px; font-weight:600; color:#888; letter-spacing:0.08em; text-transform:uppercase; margin-bottom:4px; display:block; }
  .date-group { display: flex; align-items: stretch; }
  .date-group > * { flex-shrink: 0; }
  .date-group input, .date-group select { border-radius: 0 !important; border-right-width: 0 !important; }
  .date-group > *:first-child input,
  .date-group > *:first-child select,
  .date-group > input:first-child { border-radius: 7px 0 0 7px !important; }
  .date-group > *:last-child input,
  .date-group > *:last-child select,
  .date-group > input:last-child  { border-radius: 0 7px 7px 0 !important; border-right-width: 1px !important; }
  .date-group input:focus, .date-group select:focus { position: relative; z-index: 1; border-right-width: 1px !important; }
  .date-sep { display:flex; align-items:center; padding: 0 3px; color:#bbb; font-size:13px; background:#fff; border-top:1px solid #dde3dd; border-bottom:1px solid #dde3dd; }

  /* ── split download button ── */
  .ms-dl-wrap { position: relative; display: inline-flex; }
  .ms-dl-main {
    display: inline-flex; align-items: center; gap: 6px; height: 34px;
    padding: 0 12px; border-radius: 7px 0 0 7px;
    border: 1px solid #e4e9e4; border-right: none;
    background: #fff; font-family: 'Inter', sans-serif; font-size: 12px;
    font-weight: 500; color: #444; cursor: pointer; white-space: nowrap;
    transition: background .12s, border-color .12s, color .12s;
  }
  .ms-dl-main:hover { background: #f0f7f2; border-color: #b2d9be; color: #1e6b3c; }
  .ms-dl-main svg { color: #aaa; flex-shrink: 0; }
  .ms-dl-main:hover svg { color: #1e6b3c; }
  .ms-dl-arrow {
    display: inline-flex; align-items: center; justify-content: center;
    width: 26px; height: 34px; border-radius: 0 7px 7px 0;
    border: 1px solid #e4e9e4; background: #f9faf9; cursor: pointer;
    transition: background .12s, border-color .12s;
  }
  .ms-dl-arrow:hover { background: #f0f7f2; border-color: #b2d9be; }
  .ms-dl-arrow svg { color: #888; transition: color .12s; }
  .ms-dl-arrow:hover svg { color: #1e6b3c; }
  .ms-dl-dropdown {
    display: none; position: absolute; top: 38px; left: 0; z-index: 100;
    background: #fff; border: 1px solid #e4e9e4; border-radius: 8px;
    box-shadow: 0 6px 20px rgba(0,0,0,0.09); min-width: 170px; overflow: hidden;
  }
  .ms-dl-wrap.open .ms-dl-dropdown { display: block; }
  .ms-dl-opt {
    display: flex; align-items: center; gap: 9px; padding: 9px 14px;
    font-family: 'Inter', sans-serif; font-size: 12px; font-weight: 500;
    color: #444; cursor: pointer; transition: background .1s;
    border-bottom: 1px solid #f4f4f4; white-space: nowrap;
  }
  .ms-dl-opt:last-child { border-bottom: none; }
  .ms-dl-opt:hover { background: #f0f7f2; color: #1e6b3c; }
  .opt-badge {
    font-size: 9px; font-weight: 700; padding: 2px 6px; border-radius: 4px;
    letter-spacing: 0.05em; text-transform: uppercase; flex-shrink: 0;
  }
  .badge-csv { background: #e8f5ec; color: #1e6b3c; }
  .badge-xls { background: #e3edff; color: #1a56db; }
</style>
<!-- Page Header -->
<div class="ur-header">
  <div class="ur-header-left">
    <h2>Consumer Master Data</h2>
    <p>Filter and export consumer records</p>
  </div>
</div>
<!-- Filter + Actions Bar -->
<form id="ffilter" autocomplete="off">
  <input type="hidden" id="do_search" name="do_search" value="0">
  <div class="ur-add-bar">
    <div class="ms-filter-bar">
      <div class="ms-filter-row">
        <!-- Area -->
        <div style="display:flex;flex-direction:column;gap:4px;">
          <label class="field-label">Area Filter</label>
          <div class="select-wrap" style="width:150px;">
            <select id="stown" name="stown" class="ur-add-select" style="width:150px;">
              <option value="">— All Areas —</option>
              <?php foreach ($area_options as $area): ?>
                <option value="<?= htmlspecialchars($area) ?>" <?= ($p_town === $area) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($area) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <!-- Account No. -->
        <div style="display:flex;flex-direction:column;gap:4px;">
          <label class="field-label">Account No.</label>
          <input type="text" class="ur-add-input" style="width:150px;"
            id="sroute" name="sroute" placeholder="e.g. 01-001"
            value="<?= htmlspecialchars($p_route) ?>">
        </div>
        <!-- Connection Date -->
        <div style="display:flex;flex-direction:column;gap:4px;">
          <label class="field-label">Connection Date</label>
          <div class="date-group">
            <input type="text" id="syear" name="syear"
              class="ur-add-input" style="width:68px;"
              placeholder="YYYY" maxlength="4"
              oninput="this.value=this.value.replace(/\D/,'')"
              value="<?= htmlspecialchars($p_year) ?>">
            <span class="date-sep">-</span>
            <div class="select-wrap" style="position:relative;">
              <select id="smonth" name="smonth" class="ur-add-select" style="width:76px;padding-right:22px;">
                <option value="">MM</option>
                <?php
                $months = ['01'=>'Jan','02'=>'Feb','03'=>'Mar','04'=>'Apr',
                           '05'=>'May','06'=>'Jun','07'=>'Jul','08'=>'Aug',
                           '09'=>'Sep','10'=>'Oct','11'=>'Nov','12'=>'Dec'];
                foreach ($months as $num => $name):
                ?>
                  <option value="<?= $num ?>" <?= ($p_month === $num) ? 'selected' : '' ?>><?= $name ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <span class="date-sep">-</span>
            <input type="text" id="sday" name="sday"
              class="ur-add-input" style="width:52px;"
              placeholder="DD" maxlength="2"
              oninput="this.value=this.value.replace(/\D/,'')"
              value="<?= htmlspecialchars($p_day) ?>">
          </div>
        </div>

        <button type="button" class="um-btn-add" style="align-self:flex-end;"
          onclick="
            document.getElementById('do_search').value='1';
            PageLink('reports/master_data.php','report_data','ffilter');
          ">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
          </svg>
          Show Data
        </button>

      </div>
      <!-- Download buttons -->
      <div class="ms-download-row">
        <div class="ms-download-label">Download</div>
        <div class="ms-dl-grid">
          <!-- JAED.TXT — no format split needed -->
          <button type="button" class="ms-dl-btn" onclick="submitDownload('reports/jaedtxt.php');">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            JAED.TXT
          </button>
          <!-- Split buttons: CSV default, dropdown for XLS -->
          <?php
          $dl_buttons = [
            ['label' => 'All Records',    'csv' => 'reports/allcsv.php',              'xls' => 'reports/allxls.php',              'id' => 'dl_all'],
            ['label' => 'No GPS',         'csv' => 'reports/allcsv.php?qtag=nogps',   'xls' => 'reports/allxls.php?qtag=nogps',   'id' => 'dl_nogps'],
            ['label' => 'Disconnected',   'csv' => 'reports/allcsv.php?qtag=disco',   'xls' => 'reports/allxls.php?qtag=disco',   'id' => 'dl_disco'],
            ['label' => 'w/ Pulled',      'csv' => 'reports/allcsv.php?qtag=allwpo',  'xls' => 'reports/allxls.php?qtag=allwpo',  'id' => 'dl_allwpo'],
            ['label' => 'w/ Tag',         'csv' => 'reports/allcsv.php?qtag=btag',    'xls' => 'reports/allxls.php?qtag=btag',    'id' => 'dl_btag'],
          ];
          foreach ($dl_buttons as $btn):
          ?>
          <div class="ms-dl-wrap" id="<?= $btn['id'] ?>">
            <button type="button" class="ms-dl-main" onclick="submitDownload('<?= $btn['csv'] ?>')">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
              <?= htmlspecialchars($btn['label']) ?>
            </button>
            <button type="button" class="ms-dl-arrow" onclick="toggleDl('<?= $btn['id'] ?>', event)">
              <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
            </button>
            <div class="ms-dl-dropdown">
              <div class="ms-dl-opt" onclick="submitDownload('<?= $btn['csv'] ?>'); closeDl('<?= $btn['id'] ?>')">
                <span class="opt-badge badge-csv">CSV</span> Download as CSV
              </div>
              <div class="ms-dl-opt" onclick="submitDownload('<?= $btn['xls'] ?>'); closeDl('<?= $btn['id'] ?>')">
                <span class="opt-badge badge-xls">XLS</span> Download as Excel
              </div>
            </div>
          </div>
          <?php endforeach; ?>

        </div>
      </div>

    </div>
  </div>
</form>

<!-- Hidden download form -->
<form id="fdl" method="post" target="_blank" style="display:none;">
  <input type="hidden" name="sroute"  id="dl_route">
  <input type="hidden" name="stown"   id="dl_town">
  <input type="hidden" name="syear"   id="dl_year">
  <input type="hidden" name="smonth"  id="dl_month">
  <input type="hidden" name="sday"    id="dl_day">
</form>

<script>
function submitDownload(action) {
  var f = document.getElementById('fdl');
  f.action = action;
  document.getElementById('dl_route').value  = document.getElementById('sroute').value;
  document.getElementById('dl_town').value   = document.getElementById('stown').value;
  document.getElementById('dl_year').value   = document.getElementById('syear').value;
  document.getElementById('dl_month').value  = document.getElementById('smonth').value;
  document.getElementById('dl_day').value    = document.getElementById('sday').value;
  f.submit();
}

function toggleDl(id, e) {
  e.stopPropagation();
  document.querySelectorAll('.ms-dl-wrap.open').forEach(function(el) {
    if (el.id !== id) el.classList.remove('open');
  });
  document.getElementById(id).classList.toggle('open');
}

function closeDl(id) {
  document.getElementById(id).classList.remove('open');
}

document.addEventListener('click', function() {
  document.querySelectorAll('.ms-dl-wrap.open').forEach(function(el) {
    el.classList.remove('open');
  });
});
</script>

<!-- Data Table -->
<div class="ur-body">
  <div class="ms-table-wrap">
    <div class="ms-table-scroll" id="report_data">
      <table class="ms-table">
        <thead>
          <tr>
          <?php
            $col_arr = [];
            $sql     = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'tbl_cust' ORDER BY ORDINAL_POSITION";
            $stmt    = sqlsrv_query($conn, $sql);
            $colno   = 0;
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $col_arr[] = $row["COLUMN_NAME"];
                echo "<th>" . htmlspecialchars($row["COLUMN_NAME"]) . "</th>";
                $colno++;
            }
            sqlsrv_free_stmt($stmt);
          ?>
          </tr>
        </thead>
        <tbody>
        <?php
          $hasData = false;

          if ($do_search) {
              $params = [];
              $where  = "";

              if ($p_route != '') {
                  $where   .= " AND ACCOUNTNO LIKE ?";
                  $params[] = $p_route . '%';
              }

              if ($p_town != '') {
                  $where   .= " AND AREA = ?";
                  $params[] = $p_town;
              }

              $p_day_padded   = ($p_day   != '') ? str_pad($p_day,   2, '0', STR_PAD_LEFT) : '';
              $p_month_padded = ($p_month != '') ? str_pad($p_month, 2, '0', STR_PAD_LEFT) : '';

              if ($p_year != '' && $p_month_padded != '' && $p_day_padded != '') {
                  $where   .= " AND SUBSTRING(CONVERT(VARCHAR,CONNDATE,120),1,4) = ?"
                           .  " AND SUBSTRING(CONVERT(VARCHAR,CONNDATE,120),6,2) = ?"
                           .  " AND SUBSTRING(CONVERT(VARCHAR,CONNDATE,120),9,2) = ?";
                  $params[] = $p_year;
                  $params[] = $p_month_padded;
                  $params[] = $p_day_padded;
              } elseif ($p_year != '' && $p_month_padded != '') {
                  $where   .= " AND SUBSTRING(CONVERT(VARCHAR,CONNDATE,120),1,4) = ?"
                           .  " AND SUBSTRING(CONVERT(VARCHAR,CONNDATE,120),6,2) = ?";
                  $params[] = $p_year;
                  $params[] = $p_month_padded;
              } elseif ($p_year != '') {
                  $where   .= " AND SUBSTRING(CONVERT(VARCHAR,CONNDATE,120),1,4) = ?";
                  $params[] = $p_year;
              } elseif ($p_month_padded != '' && $p_day_padded != '') {
                  $where   .= " AND SUBSTRING(CONVERT(VARCHAR,CONNDATE,120),6,2) = ?"
                           .  " AND SUBSTRING(CONVERT(VARCHAR,CONNDATE,120),9,2) = ?";
                  $params[] = $p_month_padded;
                  $params[] = $p_day_padded;
              } elseif ($p_month_padded != '') {
                  $where   .= " AND SUBSTRING(CONVERT(VARCHAR,CONNDATE,120),6,2) = ?";
                  $params[] = $p_month_padded;
              } elseif ($p_day_padded != '') {
                  $where   .= " AND SUBSTRING(CONVERT(VARCHAR,CONNDATE,120),9,2) = ?";
                  $params[] = $p_day_padded;
              }

              $sql  = "SELECT TOP 1000 * FROM tbl_cust WHERE 1=1 $where ORDER BY ACCOUNTNO";
              $stmt = sqlsrv_query($conn, $sql, $params);

              if ($stmt === false) {
                  echo '<tr><td colspan="' . $colno . '"><b>Query error:</b><pre>';
                  print_r(sqlsrv_errors());
                  echo '</pre></td></tr>';
              } else {
                  while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                      $hasData = true;
                      echo '<tr>';
                      for ($x = 0; $x < $colno; $x++) {
                          $val = $row[$col_arr[$x]] ?? '';
                          if ($val instanceof DateTime) {
                              echo '<td>' . $val->format('Y-m-d H:i:s') . '</td>';
                          } else {
                              echo '<td>' . htmlspecialchars((string)$val) . '</td>';
                          }
                      }
                      echo '</tr>';
                  }
                  sqlsrv_free_stmt($stmt);
              }
          }
          if (!$hasData) {
              $msg  = $do_search
                  ? 'No records matched your filters'
                  : 'No data to display';
              $sub  = $do_search
                  ? 'Try adjusting the Area, Account No., or Connection Date filters'
                  : 'Set a filter above and click Show Data';
              echo '<tr><td colspan="' . $colno . '">
                      <div class="ms-empty">
                        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2">
                          <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
                        </svg>
                        <p>' . $msg . '</p>
                        <span>' . $sub . '</span>
                      </div>
                    </td></tr>';
          }

          sqlsrv_close($conn);
        ?>
        </tbody>
      </table>
    </div>
  </div>
</div>