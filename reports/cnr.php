<?php include '../js/fphp.php'; ?>
<?php include '../conn.php'; ?>
<link rel="stylesheet" href="css/g.css">
<link rel="stylesheet" href="css/font-awesome-4.7.0/css/font-awesome.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">

<?php
$p_fr_date = date('Y-m-d');
$p_to_date = date('Y-m-d');
$today     = date('Y-m-d');
?>

<style>
  .rpt-date-input {
    height: 34px;
    padding: 0 12px;
    border: 1px solid #e4e9e4;
    border-radius: 7px;
    font-family: 'Inter', sans-serif;
    font-size: 12px;
    color: #111;
    background: #fff;
    outline: none;
    transition: border-color .13s, box-shadow .13s;
    cursor: pointer;
  }
  .rpt-date-input:focus {
    border-color: #1e6b3c;
    box-shadow: 0 0 0 3px rgba(30,107,60,0.08);
  }
  .rpt-date-input::-webkit-calendar-picker-indicator {
    opacity: 0.5;
    cursor: pointer;
  }
  .rpt-data-wrap {
    background: #fff;
    border: 1px solid #e4e9e4;
    border-radius: 10px;
    overflow: hidden;
  }
  .rpt-data-scroll {
    overflow-x: auto;
    overflow-y: auto;
    max-height: 520px;
  }
  #report_data table {
    width: 100%;
    border-collapse: collapse;
    font-size: 11.5px;
    white-space: nowrap;
  }
  #report_data thead th {
    background: #1e6b3c;
    color: #fff;
    padding: 10px 12px;
    text-align: left;
    font-size: 10.5px;
    font-weight: 600;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    position: sticky;
    top: 0;
    z-index: 2;
    border-right: 1px solid rgba(255,255,255,0.1);
  }
  #report_data thead th:last-child { border-right: none; }
  #report_data tbody tr {
    border-bottom: 1px solid #f0f2f0;
    transition: background .1s;
  }
  #report_data tbody tr:last-child { border-bottom: none; }
  #report_data tbody tr:hover { background: #f7fbf8; }
  #report_data tbody td {
    padding: 8px 12px;
    color: #444;
    border-right: 1px solid #f4f4f4;
  }
  #report_data tbody td:last-child { border-right: none; }
  .rpt-empty {
    text-align: center;
    padding: 52px 20px;
    color: #ccc;
  }
  .rpt-empty svg  { display: block; margin: 0 auto 12px; color: #ddd; }
  .rpt-empty p    { font-size: 13px; }
  .rpt-empty span { display: block; font-size: 12px; color: #ddd; margin-top: 4px; }
  .um-btn-dl {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    height: 36px;
    padding: 0 16px;
    background: #1e6b3c;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-family: 'Inter', sans-serif;
    font-size: 12.5px;
    font-weight: 600;
    letter-spacing: 0.02em;
    cursor: pointer;
    white-space: nowrap;
    transition: background .15s, transform .1s, box-shadow .15s;
    box-shadow: 0 1px 3px rgba(30,107,60,0.18);
  }
  .um-btn-dl:hover {
    background: #175a32;
    box-shadow: 0 3px 10px rgba(30,107,60,0.28);
    transform: translateY(-1px);
  }
  .um-btn-dl:active {
    transform: translateY(0px) scale(0.97);
    box-shadow: 0 1px 3px rgba(30,107,60,0.15);
  }
</style>

<!-- ── Page Header ── -->
<div class="ur-header">
  <div class="ur-header-left">
    <h2>Complaints &amp; Request Data</h2>
    <p>Filter records by date range and export</p>
  </div>
</div>

<!-- ── Filter Bar ── -->
<form id="ffiltercnr" name="ffiltercnr" autocomplete="off" onsubmit="return false;">
  <div class="ur-add-bar" style="flex-wrap:wrap; gap:10px; align-items:flex-end;">
    <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">

      <div style="display:flex; flex-direction:column; gap:4px;">
        <label style="font-size:10px; font-weight:600; color:#888; letter-spacing:0.08em; text-transform:uppercase;">From</label>
        <input type="date" class="rpt-date-input" id="frcnrdate" name="frcnrdate"
          value="<?php echo $p_fr_date; ?>" max="<?php echo $today; ?>" required>
      </div>

      <div style="display:flex; flex-direction:column; gap:4px;">
        <label style="font-size:10px; font-weight:600; color:#888; letter-spacing:0.08em; text-transform:uppercase;">To</label>
        <input type="date" class="rpt-date-input" id="tocnrdate" name="tocnrdate"
          value="<?php echo $p_to_date; ?>" max="<?php echo $today; ?>">
      </div>

      <button type="button" class="um-btn-add" style="align-self:flex-end;"
        onclick="PageLink('reports/cnr_data.php','report_data','ffiltercnr');">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        Show Data
      </button>

      <!-- ── Download Dropdown ── -->
      <div style="position:relative; align-self:flex-end;" id="dl-wrap">
        <button type="button" class="um-btn-dl" onclick="toggleDLMenu();">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
            <polyline points="7 10 12 15 17 10"/>
            <line x1="12" y1="15" x2="12" y2="3"/>
          </svg>
          Download
          <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
            id="dl-chevron" style="transition:transform .2s;">
            <polyline points="6 9 12 15 18 9"/>
          </svg>
        </button>

        <div id="dl-menu" style="display:none; position:absolute; top:calc(100% + 6px); left:0;
          background:#fff; border:1px solid #e4e9e4; border-radius:8px;
          box-shadow:0 6px 20px rgba(0,0,0,0.10); min-width:150px; z-index:99; overflow:hidden;">

          <button onclick="downloadCNR('csv')" style="display:flex; align-items:center; gap:9px; width:100%;
            padding:10px 14px; background:none; border:none; font-family:'Inter',sans-serif;
            font-size:12.5px; color:#1f2937; cursor:pointer;"
            onmouseover="this.style.background='#f4faf6'" onmouseout="this.style.background='none'">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#1e6b3c" stroke-width="2.2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
            <span>Download CSV</span>
            <span style="margin-left:auto; font-size:9px; font-weight:700; color:#1e6b3c; background:#eaf5ee; padding:2px 5px; border-radius:3px;">CSV</span>
          </button>

          <div style="height:1px; background:#f0f2f0; margin:0 10px;"></div>

          <button onclick="downloadCNR('xls')" style="display:flex; align-items:center; gap:9px; width:100%;
            padding:10px 14px; background:none; border:none; font-family:'Inter',sans-serif;
            font-size:12.5px; color:#1f2937; cursor:pointer;"
            onmouseover="this.style.background='#f4faf6'" onmouseout="this.style.background='none'">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#1e6b3c" stroke-width="2.2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
            <span>Download XLS</span>
            <span style="margin-left:auto; font-size:9px; font-weight:700; color:#1e6b3c; background:#eaf5ee; padding:2px 5px; border-radius:3px;">XLS</span>
          </button>

        </div>
      </div>

    </div>
  </div>
</form>

<!-- ── Report Data ── -->
<div class="ur-body">
  <div class="rpt-data-wrap">
    <div class="rpt-data-scroll" id="report_data">
      <div class="rpt-empty">
        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        <p>No data to display</p>
        <span>Select a date range and click Show Data</span>
      </div>
    </div>
  </div>
</div>

<script>
  var today = '<?php echo $today; ?>';

  document.getElementById('frcnrdate').addEventListener('change', function () {
    var toEl = document.getElementById('tocnrdate');
    toEl.min = this.value;
    if (toEl.value < this.value) toEl.value = this.value;
  });

  document.getElementById('tocnrdate').addEventListener('change', function () {
    if (this.value > today) this.value = today;
  });

  function toggleDLMenu() {
    var menu    = document.getElementById('dl-menu');
    var chevron = document.getElementById('dl-chevron');
    var open    = menu.style.display === 'block';
    menu.style.display      = open ? 'none' : 'block';
    chevron.style.transform = open ? 'rotate(0deg)' : 'rotate(180deg)';
  }

  document.addEventListener('click', function (e) {
    if (!document.getElementById('dl-wrap').contains(e.target)) {
      document.getElementById('dl-menu').style.display = 'none';
      document.getElementById('dl-chevron').style.transform = 'rotate(0deg)';
    }
  });

  function downloadCNR(type) {
    var url = type === 'csv' ? 'reports/cnr_csv.php' : 'reports/cnr_xls.php';
    window.open(url + '?' + $('#ffiltercnr').serialize(), '_blank');
    toggleDLMenu();
  }
</script>