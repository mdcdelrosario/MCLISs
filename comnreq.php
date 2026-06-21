<?php
include 'conn.php';
include 'js/fphp.php';

$p_trn = $p_area = $p_account = $p_name = $p_address = $p_contactno = "";
$p_lat = $p_lng = $p_reqdt = $p_request = $p_req_desc = $p_crew = "";
$p_findings = $p_action = $p_aco_dt = $p_encoder = "";
$p_person1 = $p_person2 = $p_person3 = $p_person4 = $p_person5 = $p_restrict = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["t_accountno"])) {
        $p_account = $_POST['t_accountno'];
        $p_name    = $_POST['t_name'];
        $p_address = $_POST['t_address'];
        $p_lat     = $_POST['t_lat'];
        $p_lng     = $_POST['t_lng'];
    }
    $p_trn = $_POST['trn'] ?? '';
}

if ($p_trn != '') {
    $stmt = sqlsrv_query($conn, "SELECT * FROM tbl_cnr WHERE trn = ?", [$p_trn]);
    if ($stmt === false) die(print_r(sqlsrv_errors(), true));

    if ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $p_area      = $row["area"];
        $p_account   = $row["accountno"];
        $p_name      = $row["name"];
        $p_address   = $row["address"];
        $p_contactno = $row["contactno"];
        $p_lat       = $row["gps_lat"];
        $p_lng       = $row["gps_long"];
        $p_reqdt     = ($row["req_dt"] instanceof DateTime) ? $row["req_dt"]->format('Y-m-d\TH:i') : "";
        $p_request   = $row["request"];
        $p_req_desc  = $row["req_desc"];
        $p_crew      = $row["crew"];
        $p_findings  = $row["findings"];
        $p_action    = $row["action"];
        $p_aco_dt    = ($row["aco_dt"] instanceof DateTime) ? $row["aco_dt"]->format('Y-m-d\TH:i') : "";
        $p_encoder   = $row["encoder"];
        $p_person1   = $row["member1"];
        $p_person2   = $row["member2"];
        $p_person3   = $row["member3"];
        $p_person4   = $row["member4"];
        $p_person5   = $row["member5"];
        $p_restrict  = $row["restrict"];
    }
}

$p_account = str_pad($p_account, 10, " ");
$facct = substr($p_account,0,2)."-".substr($p_account,2,2)."-".substr($p_account,4,4)."-".substr($p_account,8,4);

// True only if aco_dt has been properly set (year >= 2020)
$is_accomplished = !empty($p_aco_dt) && (int)substr($p_aco_dt, 0, 4) >= 2020;

// If not accomplished, clear the date so it doesn't show a junk value
if (!$is_accomplished) {
    $p_aco_dt = '';
}

$savedMembers = array_values(array_filter(array_map(function($v) {
    return trim($v ?? '');
}, [$p_person1, $p_person2, $p_person3, $p_person4, $p_person5])));

$js_crew    = json_encode((string)$p_crew);
$js_members = json_encode($savedMembers);
?>
<style>
/* ── Crew Card ───────────────────────────────────────────── */
.crew-card {
    background: #f8f9fb;
    border: 1px solid #dde1e9;
    border-radius: 10px;
    padding: 16px 18px 20px;
    margin: 4px 0;
}
.crew-card h4 { margin: 0 0 14px; font-size: .93rem; font-weight: 700; color: #2c3e50; }
.crew-label {
    display: flex; align-items: center; gap: 6px;
    font-size: .71rem; font-weight: 700; color: #5c6b7a;
    text-transform: uppercase; letter-spacing: .07em;
    margin: 14px 0 6px;
}

/* ── Custom Select ───────────────────────────────────────── */
.crew-select-wrap { position: relative; }
.crew-select-btn {
    width: 100%; display: flex; align-items: center;
    justify-content: space-between;
    border: 1.5px solid #c8d0db; border-radius: 7px;
    padding: 9px 12px; font-size: .88rem; background: #fff;
    cursor: pointer; text-align: left; color: #1a2533;
    box-sizing: border-box; transition: border-color .15s, box-shadow .15s;
}
.crew-select-btn:focus, .crew-select-btn.open {
    outline: none; border-color: #4285f4;
    box-shadow: 0 0 0 3px rgba(66,133,244,.12);
}
.crew-select-btn .csl-placeholder { color: #9aa5b1; }
.crew-select-arrow {
    font-size: .65rem; color: #9aa5b1;
    transition: transform .2s; flex-shrink: 0; margin-left: 8px;
}
.crew-select-btn.open .crew-select-arrow { transform: rotate(180deg); }
.crew-select-dropdown {
    display: none; position: absolute;
    top: calc(100% + 3px); left: 0; right: 0;
    background: #fff; border: 1.5px solid #c8d0db;
    border-radius: 8px; box-shadow: 0 8px 24px rgba(0,0,0,.13);
    z-index: 9999; overflow: hidden;
}
.crew-select-dropdown.open { display: block; }
.crew-select-search {
    width: 100%; border: none; border-bottom: 1.5px solid #eef0f4;
    padding: 9px 13px; font-size: .85rem; outline: none;
    box-sizing: border-box; color: #1a2533; background: #fafbfc;
}
.crew-select-list { max-height: 200px; overflow-y: auto; }
.crew-select-item {
    padding: 9px 14px; font-size: .86rem; cursor: pointer;
    border-bottom: 1px solid #f0f2f5; color: #1a2533; transition: background .1s;
}
.crew-select-item:last-child { border-bottom: none; }
.crew-select-item:hover  { background: #eef3ff; }
.crew-select-item.sel    { background: #e3eaff; font-weight: 700; color: #2a4cb8; }
.crew-select-item.empty  { color: #9aa5b1; cursor: default; }
.crew-select-item.empty:hover { background: transparent; }

/* ── Tags ────────────────────────────────────────────────── */
.tag-row { display: flex; flex-wrap: wrap; gap: 6px; min-height: 26px; margin-top: 8px; }
.ctag {
    display: inline-flex; align-items: center; gap: 5px;
    border-radius: 20px; padding: 4px 10px 4px 13px;
    font-size: .81rem; font-weight: 600;
    animation: tagpop .13s ease;
}
.ctag.crew   { background: #e7f7ee; color: #1a6b3c; border: 1px solid #9edcb8; }
.ctag.member { background: #e3eaff; color: #2a4cb8; border: 1px solid #b3c4f7; }
@keyframes tagpop {
    from { transform: scale(.82); opacity: 0; }
    to   { transform: scale(1);   opacity: 1; }
}
.ctag button {
    background: none; border: none; cursor: pointer; color: inherit;
    opacity: .45; font-size: .75rem; padding: 0; line-height: 1; transition: opacity .1s;
}
.ctag button:hover { opacity: 1; }
.mbadge {
    background: #e8edf5; border-radius: 20px;
    padding: 1px 8px; font-size: .68rem; font-weight: 600; color: #456;
}

/* ── Area Pills ── */
.area-filters { display: flex; flex-direction: column; gap: 5px; }
.area-pill {
    display: flex; align-items: center; gap: 10px;
    background: #fff; border: 1.5px solid #c8d0db;
    border-radius: 8px; padding: 8px 12px;
    cursor: pointer; user-select: none;
    transition: background .13s, border-color .13s;
}
.area-pill input[type=checkbox] {
    width: 15px; height: 15px; accent-color: #4285f4;
    flex-shrink: 0; margin: 0; cursor: pointer;
}
.area-abbr {
    background: #e8edf5; color: #334155; border-radius: 4px;
    padding: 2px 7px; font-size: .69rem; font-weight: 800;
    letter-spacing: .05em; flex-shrink: 0;
    transition: background .13s, color .13s;
}
.area-full { font-size: .83rem; font-weight: 500; color: #3d4a5c; flex: 1; }
.area-pill.on { background: #eef3ff; border-color: #4285f4; }
.area-pill.on .area-abbr { background: #4285f4; color: #fff; }
.area-pill.on .area-full { color: #1a3fc4; font-weight: 600; }

/* ── Area Results ── */
#areaResultsWrap {
    margin-top: 10px; background: #fff;
    border: 1.5px solid #dde1e9; border-radius: 8px;
    padding: 10px 12px 12px;
}
.area-hdr {
    font-size: .71rem; font-weight: 700; color: #5c6b7a;
    text-transform: uppercase; letter-spacing: .07em; margin-bottom: 8px;
}
.area-list { display: flex; flex-wrap: wrap; gap: 6px; min-height: 28px; }
.area-add-btn {
    display: inline-flex; align-items: center; gap: 5px;
    background: #f0f4ff; border: 1.5px solid #b3c4f7;
    border-radius: 20px; padding: 5px 13px;
    font-size: .8rem; font-weight: 600; color: #2a4cb8;
    cursor: pointer; transition: background .12s, transform .1s;
}
.area-add-btn:hover { background: #dce6ff; transform: scale(1.03); }
.area-msg { color: #9aa5b1; font-size: .82rem; padding: 2px 0; }

/* ── Member Search ── */
.msearch-wrap { position: relative; }
.msearch-wrap input[type=text] {
    width: 100%; border: 1.5px solid #c8d0db; border-radius: 7px;
    padding: 8px 12px; font-size: .88rem; outline: none;
    box-sizing: border-box; transition: border-color .15s;
}
.msearch-wrap input[type=text]:focus {
    border-color: #4285f4; box-shadow: 0 0 0 3px rgba(66,133,244,.12);
}
.msearch-dd {
    display: none; position: absolute;
    top: calc(100% + 3px); left: 0; right: 0;
    background: #fff; border: 1.5px solid #c8d0db;
    border-radius: 8px; box-shadow: 0 6px 20px rgba(0,0,0,.12);
    z-index: 9999; max-height: 180px; overflow-y: auto;
}
.msearch-dd .ddi {
    padding: 9px 13px; font-size: .86rem; cursor: pointer;
    border-bottom: 1px solid #f0f2f5; transition: background .1s;
}
.msearch-dd .ddi:last-child { border-bottom: none; }
.msearch-dd .ddi:hover { background: #eef3ff; }
.msearch-dd .ddi.empty { color: #9aa5b1; cursor: default; }
.msearch-dd .ddi.empty:hover { background: transparent; }

/* ── Misc ── */
.hidden-selects { display: none !important; }

/* ── Buttons ─────────────────────────────────────────────── */
.btn-save {
    display: block; width: 100%; margin-top: 4px; /* keep this small */
    padding: 10px; border: none; border-radius: 7px;
    font-size: .9rem; font-weight: 700; cursor: pointer;
    transition: opacity .15s;
}
.btn-save:hover { opacity: .88; }

.btn-save-blue   { background: #2563eb; color: #fff; }
.btn-save-teal   { background: #2563eb; color: #fff; }
.btn-save-amber  { background: #2563eb; color: #fff; }
.btn-save-red    { background: #dc2626; color: #fff; margin-right: auto; }

/* maps link */
.maps-link {
    color: #2563eb; font-size: .83rem; font-weight: 600;
    text-decoration: underline;
}

/* ── Complaint select ── */
select#t_cnr {
    width: 100%;
    padding: 9px 34px 9px 11px;
    border: 2px solid #1a1a1a;
    border-radius: 7px;
    background-color: #fff;
    color: #1a1a1a;
    font-size: .9rem;
    font-weight: 600;
    appearance: none;
    -webkit-appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%231a1a1a' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 11px center;
    cursor: pointer;
    outline: none;
    transition: border-color .15s, box-shadow .15s;
    box-sizing: border-box;
}
select#t_cnr:focus {
    border-color: #333;
    box-shadow: 0 0 0 3px rgba(0,0,0,.08);
}
.cnr-label-col { 
    width: 35%; 
    vertical-align: top; 
    padding-top: 8px;
    font-size: .82rem;
    word-break: normal;
    white-space: normal;
}
.cnr-input-col { 
    width: 65%; 
}

.cnr-wrap table.w3-table-all {
    table-layout: fixed;
}
</style>

<div class="cnr-wrap">
<form id='frmcInfoDataCNR' name='frmcInfoDataCNR' method='post' action=''>
    <input type='hidden' id='t_trn'  name='t_trn'  value='<?= htmlspecialchars($p_trn) ?>'>
    <input type='hidden' id='t_crew' name='t_crew' value='<?= htmlspecialchars($p_crew) ?>'>

    <div class="cnr-header">
        <h1 class="cnr-title">Complaint / Request</h1>
        <span class="cnr-pill"><?= htmlspecialchars($p_trn ?: 'New') ?></span>
    </div>

    <div class="section">
        <div class="section-head"><span class="section-title">Consumer Details</span></div>
       <table class='w3-table-all tbpad2' style='width:100%;'>
    <tr>
        <td class='cnr-label-col'>ACCOUNT NO</td>
        <td class='cnr-input-col'><input style='width:100%;box-sizing:border-box;' type='text' id='t_accountno' name='t_accountno' value='<?= htmlspecialchars($facct) ?>'></td>
    </tr>
    <tr>
        <td class='cnr-label-col'>NAME</td>
        <td class='cnr-input-col'><input style='width:100%;box-sizing:border-box;' type='text' id='t_name' name='t_name' value='<?= htmlspecialchars($p_name) ?>'></td>
    </tr>
    <tr>
        <td class='cnr-label-col'>ADDRESS</td>
        <td class='cnr-input-col'><input style='width:100%;box-sizing:border-box;' type='text' id='t_address' name='t_address' value='<?= htmlspecialchars($p_address) ?>'></td>
    </tr>
    <tr>
        <td class='cnr-label-col'>CONTACT NO</td>
        <td class='cnr-input-col'><input style='width:100%;box-sizing:border-box;' type='text' id='t_phone' name='t_phone' value='<?= htmlspecialchars($p_contactno) ?>'></td>
    </tr>
</table>
    </div>

    <div style='display:flex;justify-content:flex-end;align-items:center;gap:10px;margin:6px 0;'>
        <?php if ($_SESSION['edit_complaints_request'] == 1 && $p_trn != ""): ?>
            <button type='button'
                class='btn-save btn-save-red'
                style='width:auto;margin-top:0;'
                onclick="SaveData('cnr_delete.php','frmcInfoDataCNR');clearboxcnr();">
                Delete Record
            </button>
        <?php endif; ?>
        <a class="maps-link" target="_blank"
           href="https://www.google.com/maps/search/?api=1&query=<?= htmlspecialchars($p_lat) ?>,<?= htmlspecialchars($p_lng) ?>">
            View on Google Maps
        </a>
    </div>

    <hr>
<table class='w3-table-all tbpad2' style='width:100%;'>
    <tr>
        <td class='cnr-label-col'>REQUEST DATE</td>
        <td class='cnr-input-col'><input style='width:100%;box-sizing:border-box;' type='datetime-local' id='t_rdate' name='t_rdate' value='<?= htmlspecialchars($p_reqdt) ?>'></td>
    </tr>
    <tr>
        <td class='cnr-label-col'>COMPLAINT</td>
        <td class='cnr-input-col'><?= select_table("tbl_request","request","request","t_cnr",$p_request) ?></td>
    </tr>
    <tr>
        <td class='cnr-label-col'>DESCRIPTION</td>
        <td class='cnr-input-col'><textarea style='width:100%;box-sizing:border-box;' id='t_cnr_desc' name='t_cnr_desc'><?= htmlspecialchars($p_req_desc) ?></textarea></td>
    </tr>
    <tr>
        <td colspan="2" style="padding:4px 0;">
          <div class="crew-card">
            <h4>Crew &amp; Members</h4>

            <!-- Crew picker -->
            <div class="crew-label">Crew</div>
            <div class="crew-select-wrap" id="crewWrap">
                <button type="button" class="crew-select-btn" id="crewBtn">
                    <span id="crewLabel" class="csl-placeholder">— Select a crew —</span>
                    <span class="crew-select-arrow">&#9660;</span>
                </button>

                <div class="crew-select-dropdown" id="crewDD">
                    <div class="crew-select-list" id="crewList"></div>
                </div>
            </div>
            <div id="crewTag" class="tag-row"></div>

            <!-- Area office filters -->
            <div class="crew-label">Filter Members by Office</div>
            <div class="area-filters" id="areaFilters">
                <div class="area-pill" data-area="cao">
                    <input type="checkbox" class="area-cb" value="cao" id="cb-cao">
                    <span class="area-abbr">CAO</span>
                    <span class="area-full">Central Area Office</span>
                </div>
                <div class="area-pill" data-area="nao">
                    <input type="checkbox" class="area-cb" value="nao" id="cb-nao">
                    <span class="area-abbr">NAO</span>
                    <span class="area-full">Northern Area Office</span>
                </div>
                <div class="area-pill" data-area="eao">
                    <input type="checkbox" class="area-cb" value="eao" id="cb-eao">
                    <span class="area-abbr">EAO</span>
                    <span class="area-full">Eastern Area Office</span>
                </div>
                <div class="area-pill" data-area="sao">
                    <input type="checkbox" class="area-cb" value="sao" id="cb-sao">
                    <span class="area-abbr">SAO</span>
                    <span class="area-full">Southern Area Office</span>
                </div>
                <div class="area-pill" data-area="seao">
                    <input type="checkbox" class="area-cb" value="seao" id="cb-seao">
                    <span class="area-abbr">SEAO</span>
                    <span class="area-full">South Eastern Area Office</span>
                </div>
            </div>

            <!-- Area results panel -->
            <div id="areaResultsWrap" style="display:none;">
                <div class="area-hdr">Available from Selected Office(s)</div>
                <div id="areaList" class="area-list"></div>
            </div>

            <!-- Member search + tags -->
            <div class="crew-label">
                Selected Members
                <span class="mbadge" id="mCount">0</span>
            </div>
            <div class="msearch-wrap">
                <input type="text" id="mInput"
                       placeholder="Search and add a member by name…" autocomplete="off">
                <div id="mDD" class="msearch-dd"></div>
            </div>
            <div id="mTags" class="tag-row"></div>

            <!-- Hidden selects -->
            <div class="hidden-selects">
                <?= select_table("tbl_person","name","name","t_person1",$p_person1) ?>
                <?= select_table("tbl_person","name","name","t_person2",$p_person2) ?>
                <?= select_table("tbl_person","name","name","t_person3",$p_person3) ?>
                <?= select_table("tbl_person","name","name","t_person4",$p_person4) ?>
                <?= select_table("tbl_person","name","name","t_person5",$p_person5) ?>
            </div>

           <?php if ($_SESSION['edit_complaints_request'] == 1): ?>
    <div style="text-align:center;">
        <button type="button" class="btn-save btn-save-teal"
                onclick="SaveData('crew_save.php','frmcInfoDataCNR');">
            Update Crew &amp; Members
        </button>
    </div>
<?php endif; ?>
          </div>
          </td>
        </tr>
    <tr>
        <td class='cnr-label-col'>FINDINGS</td>
        <td class='cnr-input-col'><textarea style='width:100%;box-sizing:border-box;' id='t_find' name='t_find'><?= htmlspecialchars($p_findings) ?></textarea></td>
    </tr>
    <tr>
        <td class='cnr-label-col'>ACTION TAKEN</td>
        <td class='cnr-input-col'><textarea style='width:100%;box-sizing:border-box;' id='t_action' name='t_action'><?= htmlspecialchars($p_action) ?></textarea></td>
    </tr>
    <tr>
        <td class='cnr-label-col'>ACCOMPLISHED</td>
        <td class='cnr-input-col'>
            <label style="display:inline-block;margin-bottom:6px;">
                <input type="checkbox" id="t_accomplished" name="t_accomplished" value="1"
                    <?= $is_accomplished ? 'checked' : '' ?>>
                Completed
            </label>
            <input style='width:100%;box-sizing:border-box;' type='datetime-local' id='t_adate' name='t_adate'
                value='<?= htmlspecialchars($p_aco_dt) ?>'
                <?= !$is_accomplished ? 'disabled' : '' ?>>
        </td>
    </tr>
</table>

<hr>
    <?php if ($_SESSION['edit_complaints_request'] == 1): ?>
    <button type='button' class='btn-save btn-save-blue'
    onclick="if(validateCNR()) SaveData(
        'cnr_save.php',
        'frmcInfoDataCNR',
        '',
        'search_cnr.php',
        'dvpanel-body',
        'fsearch'
    );">
    Add / Update Complaint
</button>
    <?php endif; ?>
    <hr>

 <table class='w3-table-all tbpad2' style='width:100%;'>
    <tr>
        <td class='cnr-label-col'>LATITUDE</td>
        <td class='cnr-input-col'><input style='width:100%;box-sizing:border-box;' type='text' id='t_latCNR' name='t_latCNR' value='<?= htmlspecialchars($p_lat) ?>'></td>
    </tr>
    <tr>
        <td class='cnr-label-col'>LONGITUDE</td>
        <td class='cnr-input-col'><input style='width:100%;box-sizing:border-box;' type='text' id='t_lngCNR' name='t_lngCNR' value='<?= htmlspecialchars($p_lng) ?>'></td>
    </tr>
</table>
    <?php if ($_SESSION['edit_complaints_request'] == 1): ?>
        <button type='button' class='btn-save btn-save-amber'
                onclick="ChangeLocCNR();">
            Update Location
        </button>
    <?php endif; ?>
</form>
</div>

<script>

    
(function () {
'use strict';

var selCrew    = <?= $js_crew ?>;
var selMembers = <?= $js_members ?>;
var activeAreas = [];

var crewBtn    = document.getElementById('crewBtn');
var crewLabel  = document.getElementById('crewLabel');
var crewDD     = document.getElementById('crewDD');
var crewList   = document.getElementById('crewList');
var crewTagEl  = document.getElementById('crewTag');
var hiddenCrew = document.getElementById('t_crew');

var areaWrap   = document.getElementById('areaResultsWrap');
var areaListEl = document.getElementById('areaList');

var mInput     = document.getElementById('mInput');
var mDD        = document.getElementById('mDD');
var mTags      = document.getElementById('mTags');
var mCount     = document.getElementById('mCount');

var acCb       = document.getElementById('t_accomplished');
var acDate     = document.getElementById('t_adate');

var PIDS = ['t_person1','t_person2','t_person3','t_person4','t_person5'];

var mPickingFromDD = false;
var mSearchTimer   = null;

/* ── utilities ── */
function esc(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;')
                    .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

/* ── accomplished checkbox ── */
if (acCb && acDate) {
    acCb.addEventListener('change', function() {
        acDate.disabled = !this.checked;
        if (this.checked && !acDate.value)
            acDate.value = new Date().toISOString().slice(0,16);
    });
}

/* ── sync hidden selects ── */
function syncSelects() {
    PIDS.forEach(function(id, i) {
        var sel = document.getElementById(id);
        if (!sel) return;
        var want = (selMembers[i] || '').trim();
        sel.selectedIndex = 0;
        for (var j = 0; j < sel.options.length; j++) {
            if (sel.options[j].value.trim() === want) { sel.selectedIndex = j; break; }
        }
    });
}

/* ════════════════════════════════════════════════════
   CREW DROPDOWN — no search, loads all on open
════════════════════════════════════════════════════ */
function openCrewDD() {
    crewDD.classList.add('open');
    crewBtn.classList.add('open');
    crewList.innerHTML = '<div class="crew-select-item empty">Loading…</div>';

    fetch('get_crews.php')
        .then(function(r) { return r.json(); })
        .then(function(items) {
            crewList.innerHTML = '';
            if (!Array.isArray(items) || !items.length) {
                crewList.innerHTML = '<div class="crew-select-item empty">No crews found</div>';
                return;
            }
            items.forEach(function(c) {
                var d = document.createElement('div');
                d.className = 'crew-select-item' + (c === selCrew ? ' sel' : '');
                d.textContent = c;

                // Prevent the dropdown button from losing focus/closing before the tap registers
                d.addEventListener('mousedown', function(e) {
                    e.preventDefault();
                });

                // Do the actual selection on click — fires reliably for mouse AND touch
                d.addEventListener('click', function() {
                    chooseCrew(c);
                    closeCrewDD();
                });

                crewList.appendChild(d);
            });
        })
        .catch(function() {
            crewList.innerHTML = '<div class="crew-select-item empty">Error loading crews</div>';
        });
}

function closeCrewDD() {
    crewDD.classList.remove('open');
    crewBtn.classList.remove('open');
}

function chooseCrew(crew) {
    selCrew = crew;
    hiddenCrew.value = crew;
    crewLabel.textContent = crew;
    crewLabel.classList.remove('csl-placeholder');
    renderCrewTag();
    loadCrewMembers(crew);
}

function renderCrewTag() {
    crewTagEl.innerHTML = '';
    if (!selCrew) return;
    var s = document.createElement('span');
    s.className = 'ctag crew';
    s.innerHTML = esc(selCrew) + '<button type="button">&#x2715;</button>';
    s.querySelector('button').onclick = function() {
        selCrew = ''; hiddenCrew.value = '';
        crewLabel.textContent = '— Select a crew —';
        crewLabel.classList.add('csl-placeholder');
        crewTagEl.innerHTML = '';
        selMembers = [];
        renderMembers();
        refreshArea();
    };
    crewTagEl.appendChild(s);
}

crewBtn.addEventListener('click', function(e) {
    e.stopPropagation();
    crewDD.classList.contains('open') ? closeCrewDD() : openCrewDD();
});

document.addEventListener('click', function(e) {
    if (typeof e.target.closest === 'function') {
        if (!e.target.closest('#crewWrap')) closeCrewDD();
        if (!e.target.closest('.msearch-wrap') && !mPickingFromDD) {
            mDD.style.display = 'none';
        }
    }
});

/* ════════════════════════════════════════════════════
   LOAD CREW MEMBERS
════════════════════════════════════════════════════ */
function loadCrewMembers(crew) {
    mTags.innerHTML = '<span style="color:#9aa5b1;font-size:.82rem">Loading members…</span>';
    fetch('get_crew_members.php?mode=crew&crew=' + encodeURIComponent(crew))
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!Array.isArray(data)) { selMembers = []; }
            else { selMembers = data.map(function(n){ return String(n).trim(); }).filter(Boolean); }
            renderMembers();
            if (activeAreas.length) refreshArea();
        })
        .catch(function() {
            mTags.innerHTML = '<span style="color:#c00;font-size:.8rem">Error loading members</span>';
        });
}

/* ════════════════════════════════════════════════════
   RENDER MEMBER TAGS
════════════════════════════════════════════════════ */
function renderMembers() {
    mTags.innerHTML = '';
    selMembers.forEach(function(name, i) {
        var s = document.createElement('span');
        s.className = 'ctag member';
        s.innerHTML = esc(name) + '<button type="button">&#x2715;</button>';
        (function(idx){
            s.querySelector('button').onclick = function() {
                selMembers.splice(idx, 1);
                renderMembers();
                if (activeAreas.length) refreshArea();
            };
        })(i);
        mTags.appendChild(s);
    });
    mCount.textContent = selMembers.length;
    syncSelects();
}

/* ════════════════════════════════════════════════════
   AREA FILTER PILLS
════════════════════════════════════════════════════ */
document.querySelectorAll('#areaFilters .area-pill').forEach(function(pill) {
    var cb = pill.querySelector('input.area-cb');
    pill.addEventListener('click', function(e) {
        if (e.target !== cb) cb.checked = !cb.checked;
        pill.classList.toggle('on', cb.checked);
        rebuildActiveAreas();
        refreshArea();
    });
    cb.addEventListener('click', function(e) {
        e.stopPropagation();
        pill.classList.toggle('on', cb.checked);
        rebuildActiveAreas();
        refreshArea();
    });
});

function rebuildActiveAreas() {
    activeAreas = [];
    document.querySelectorAll('#areaFilters .area-cb:checked').forEach(function(c) {
        activeAreas.push(c.value);
    });
}

/* ════════════════════════════════════════════════════
   REFRESH AREA RESULTS
════════════════════════════════════════════════════ */
function refreshArea() {
    if (!activeAreas.length) {
        areaWrap.style.display = 'none';
        areaListEl.innerHTML = '';
        return;
    }
    areaWrap.style.display = 'block';
    areaListEl.innerHTML = '<span class="area-msg">Loading…</span>';

    fetch('get_crew_members.php?mode=area&areas=' + encodeURIComponent(activeAreas.join(',')))
        .then(function(r) { return r.json(); })
        .then(function(list) {
            areaListEl.innerHTML = '';
            var addable = list.filter(function(n){ return selMembers.indexOf(n) === -1; });
            if (!addable.length) {
                areaListEl.innerHTML = '<span class="area-msg">No additional members available.</span>';
                return;
            }
            addable.forEach(function(name) {
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'area-add-btn';
                btn.innerHTML = '&#43; ' + esc(name);
                btn.addEventListener('click', function() {
                    if (selMembers.indexOf(name) !== -1) return;
                    selMembers.push(name);
                    renderMembers();
                    btn.remove();
                    if (!areaListEl.querySelector('.area-add-btn'))
                        areaListEl.innerHTML = '<span class="area-msg">All available members added.</span>';
                });
                areaListEl.appendChild(btn);
            });
        })
        .catch(function() {
            areaListEl.innerHTML = '<span class="area-msg" style="color:#c00">Error loading members</span>';
        });
}

/* ════════════════════════════════════════════════════
   MEMBER SEARCH — shows suggestions as you type
════════════════════════════════════════════════════ */
function showMDD(items) {
    mDD.innerHTML = '';
    if (!items.length) {
        mDD.innerHTML = '<div class="ddi empty">No members found</div>';
        mDD.style.display = 'block';
        return;
    }
    items.forEach(function(name) {
        var d = document.createElement('div');
        d.className = 'ddi';
        d.textContent = name;

        // Stop the input's blur from hiding the dropdown before the tap/click registers
        d.addEventListener('mousedown', function(e) {
            e.preventDefault();
            mPickingFromDD = true;
        });

        // Do the actual selection on click — fires reliably for both mouse and touch,
        // unlike the old mouseup-based handler which could be skipped on tablets.
        d.addEventListener('click', function() {
            mPickingFromDD = false;
            if (selMembers.indexOf(name) === -1) selMembers.push(name);
            mInput.value = '';
            mDD.style.display = 'none';
            mDD.innerHTML = '';
            renderMembers();
            if (activeAreas.length) refreshArea();
        });

        mDD.appendChild(d);
    });
    mDD.style.display = 'block';
}

mInput.addEventListener('input', function() {
    var q = mInput.value.trim();
    clearTimeout(mSearchTimer);
    if (q.length < 1) { mDD.style.display = 'none'; return; }

    mDD.innerHTML = '<div class="ddi empty">Searching…</div>';
    mDD.style.display = 'block';

    mSearchTimer = setTimeout(function() {
        fetch('get_crew_members.php?mode=search&q=' + encodeURIComponent(q))
            .then(function(r) { return r.json(); })
            .then(function(list) {
                if (!Array.isArray(list)) { showMDD([]); return; }
                var addable = list.filter(function(n) {
                    return selMembers.indexOf(n) === -1;
                });
                showMDD(addable);
            })
            .catch(function() {
                mDD.innerHTML = '<div class="ddi empty" style="color:#c00">Error searching</div>';
                mDD.style.display = 'block';
            });
    }, 280);
});

mInput.addEventListener('blur', function() {
    if (mPickingFromDD) return;
    setTimeout(function() {
        if (!mPickingFromDD) mDD.style.display = 'none';
    }, 150);
});

/* ════════════════════════════════════════════════════
   INIT
════════════════════════════════════════════════════ */
if (selCrew) {
    crewLabel.textContent = selCrew;
    crewLabel.classList.remove('csl-placeholder');
    renderCrewTag();
}
renderMembers();

})();

function validateCNR() {
    var errors = [];

    var rdate   = document.getElementById('t_rdate');
    var complaint = document.getElementById('t_cnr');
    var crew    = document.getElementById('t_crew');

    /* Reset borders */
    [rdate, complaint].forEach(function(el) {
        if (el) el.style.border = '';
    });
    var crewBtn = document.getElementById('crewBtn');
    if (crewBtn) crewBtn.style.border = '';

    /* Validate Request Date */
    if (!rdate || !rdate.value.trim()) {
        errors.push('Request Date is required.');
        if (rdate) rdate.style.border = '2px solid #dc2626';
    }

    /* Validate Complaint type */
    if (!complaint || !complaint.value.trim()) {
        errors.push('Complaint / Request type is required.');
        if (complaint) complaint.style.border = '2px solid #dc2626';
    }

    /* Validate Crew — uses hidden input t_crew */
    if (!crew || !crew.value.trim()) {
        errors.push('Please select a Crew.');
        if (crewBtn) crewBtn.style.border = '2px solid #dc2626';
    }

    if (errors.length > 0) {
        showmsgbox('Required Fields',
            '<ul style="margin:8px 0 0 16px;padding:0;text-align:left;">'
            + errors.map(function(e) {
                return '<li style="margin-bottom:5px;font-size:12.5px;">' + e + '</li>';
            }).join('')
            + '</ul>'
        );
        return false;
    }

    return true;
}

function clearboxcnr() {
    ['id01','id02','id03'].forEach(function(id){
        var el = document.getElementById(id);
        if (el) el.style.display = 'none';
    });
}
</script>