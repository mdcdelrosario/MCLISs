<?php include 'conn.php'; ?>
<?php include 'js/fphp.php'; ?>
<?php
    $d_sear_cust = '';
    $d_sear_elem = '';

    if (!isset($_SESSION['search_customer'])) {
        header('Location: login.php');
        exit();
    }
$d_sear_cust = $d_sear_elem = "";
$rights_sql = "SELECT download_cust_brgy, download_cust_town,
                      edit_elements_pole,
                      edit_poles_central, edit_poles_northern,
                      edit_poles_eastern, edit_poles_southeastern, edit_poles_southern
               FROM tbl_userrights 
               WHERE level = '{$_SESSION['user_level']}'";
$rights_stmt = sqlsrv_query($conn, $rights_sql);
$rights_row  = sqlsrv_fetch_array($rights_stmt, SQLSRV_FETCH_ASSOC);
$can_dl_brgy   = ($rights_row['download_cust_brgy']   ?? 0) ? 'true' : 'false';
$can_dl_town   = ($rights_row['download_cust_town']   ?? 0) ? 'true' : 'false';
$can_edit_pole = ($rights_row['edit_elements_pole']   ?? 0) ? 'true' : 'false';
// Derive user's area from which edit_poles_* column is enabled
$area_col_map = [
    'Central Area Office'      => 'edit_poles_central',
    'Northern Area Office'     => 'edit_poles_northern',
    'Eastern Area Office'      => 'edit_poles_eastern',
    'SouthEastern Area Office' => 'edit_poles_southeastern',
    'Southern Area Office'     => 'edit_poles_southern',
];

// Find which area this user's rights level is assigned to
$user_areas = [];
foreach ($area_col_map as $area_name => $col) {
    if (!empty($rights_row[$col])) {
        $user_areas[] = $area_name;
    }
}
$can_edit_pole_area = !empty($user_areas) ? 'true' : 'false';
$user_areas_js      = json_encode($user_areas);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta http-equiv="Cache-Control" content="no-cache">
  <link rel="stylesheet" href="css/w3.css">
  <link rel="stylesheet" href="css/font-awesome-4.7.0/css/font-awesome.css">
  <link rel="stylesheet" href="css/style.css">
  <link rel="icon" type="image/ico" href="favicon.ico">
  <script src="https://maps.googleapis.com/maps/api/js?key=" defer></script>
  <script src="https://unpkg.com/@google/markerclustererplus@4.0.1/dist/markerclustererplus.min.js"></script>
  <script src="js/fjs.js"></script>
  <script src="js/jquery.min.js"></script>
  <script src="map.js"></script>
  <script src="town_boundaries.js"></script>
  <script>
  const CAN_DL_TOWN        = <?= $can_dl_town ?>;
  const CAN_DL_BRGY        = <?= $can_dl_brgy ?>;
  const CAN_EDIT_POLE      = <?= $can_edit_pole ?>;
  const CAN_EDIT_POLE_AREA = <?= $can_edit_pole_area ?>;
  const USER_AREAS = <?= $user_areas_js ?>;
    window.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' && e.target.nodeName === 'INPUT' && e.target.type === 'text') {
        e.preventDefault();
      }
    }, true);
  </script>
  <title>Member Consumer Location Information System</title>
</head>
<style>

html, body {
  margin: 0;
  padding: 0;
  height: 100%;
  overflow: hidden;
  position: fixed;
  width: 100%;
}

/* Map container */
#map { 
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  width: calc(100vw - 64px);
  z-index: 1;
}
</style>
<body>

<!-- SIDEBAR -->
<div id="sidebar">
  <img id="tog1" src="logo.png" alt="MCLIS" class="logo" title="MCLIS">
  <div class="sidebar-sep"></div>

  <div id="tog2" class="sidebar-item" <?= $d_sear_cust ?>
       data-tip="Search Customers" data-panel="cbox2" data-dvpanel="1">
    <i class="fa fa-users"></i>
  </div>

  <div id="tog3" class="sidebar-item" <?= $d_sear_elem ?>
       data-tip="Elements" data-panel="cbox3" data-dvpanel="0">
    <i class="fa fa-bolt"></i>
  </div>

  <div id="tog4" class="sidebar-item" <?= $d_sear_cust ?>
       data-tip="Analytics" data-panel="cbox4" data-dvpanel="1">
    <i class="fa fa-bar-chart"></i>
  </div>

  <div class="sidebar-sep"></div>

  <div id="tog5" class="sidebar-item" <?= $d_sear_elem ?>
       data-tip="EMS Portal">
    <i class="fa fa-industry"></i>
  </div>

  <div style="flex:1;"></div>
  <div class="sidebar-sep"></div> 
    <?php 
    $currentUserLevel = strtolower($_SESSION['user_level'] ?? '');
    $isGuest = $currentUserLevel === 'guest';
    $isAdmin = $currentUserLevel === 'admin';
    ?>
    <?php if (!$isAdmin): ?>
    <a class="sidebar-item" data-tip="Menu" style="text-decoration:none;"
      onclick="showmsgbox('Message','Only administrator accounts can access this menu.');">
      <i class="fa fa-th-large"></i>
    </a>
    <?php else: ?>
    <a href="menu.php" target="_blank" class="sidebar-item" data-tip="Menu"
      style="text-decoration:none;">
      <i class="fa fa-th-large"></i>
    </a>
    <?php endif; ?>

  <div class="sidebar-item sidebar-logout" data-tip="Logout"
       onclick="showmsgboxYN('Confirm','Are you sure you want to logout?','./user_logout.php','')">
    <i class="fa fa-sign-out"></i>
  </div>
</div>


<!-- FORM + LEFT PANELS -->
<form id="fsearch" action="#" onsubmit="return false;">

  <!-- CBOX 2 — Customer Search -->
  <div id="cbox2" class="p-shell" style="display:none;">
    <div class="ph">
      <i class="fa fa-users"></i>
      <h3>Customer Search</h3>
      <span class="ph-x" data-close="cbox2"><i class="fa fa-times"></i></span>
    </div>
    <div class="pb" style="padding-bottom:12px;">
      <div class="search-row">
        <input type="text" id="vsearch" name="vsearch"
               placeholder="Account No. or Last First Name…" autocomplete="off">
        <button type="button" class="btn-i" id="searchBtn" title="Search">
          <i class="fa fa-search"></i>
        </button>
      </div>
      <div class="search-hint">
        <span><strong>Account No:</strong> &nbsp;TOWN-ROUTE-ACCOUNT</span>
        <span><strong>By Name:</strong> &nbsp;&nbsp;&nbsp;LASTNAME FIRSTNAME</span>
      </div>
    </div>
  </div>

  <!-- CBOX 3 — Elements -->
  <div id="cbox3" class="p-shell" style="display:none;">
    <div class="ph">
      <i class="fa fa-bolt"></i>
      <h3>Elements</h3>
      <span class="ph-x" data-close="cbox3"><i class="fa fa-times"></i></span>
    </div>
    <div class="pb">

      <!-- Lines -->
      <div class="field-row" style="margin-top:6px;">
        <span class="fl">Lines</span>
        <div class="fg" style="flex:1; gap:6px; flex-wrap:wrap;">
          <div class="fi" style="flex:1; min-width:0;">
            <select id="line_type" name="line_type" style="width:110%;height:34px;">
              <option value="all">All Lines</option>
              <option value="primary3">Primary3ph</option>
              <option value="primary2">Primary2ph</option>
              <option value="primary1">Primary1ph</option>
              <option value="secondary">Secondary</option>
              <option value="underbuilt">Underbuilt</option>
            </select>
          </div>
 
          <!-- Show feeder lines -->
          <button type="button" class="btn-i" style="justify-content:center;"
                  title="Show Feeder Lines" onclick="loadKMLByLineType()">
            <i class="fa fa-eye"></i>
          </button>
 
          <!-- Import KML file -->
          <button type="button" class="btn-i" style="justify-content:center;"
                  title="Import KML — name as feeder{ID}_{type}.kml&#10;e.g. feeder11_primary1.kml&#10;Types:primary3, primary2, primary1, secondary, underbuilt"
                  onclick="triggerKMLImport()">
            <i class="fa fa-upload"></i>
          </button>
 
          <!-- Clear lines & imported KML -->
          <button type="button" class="btn-i red" title="Clear lines &amp; imported KML"
                  onclick="clearKMLLines(); clearImportedKML();">
            <i class="fa fa-remove"></i>
          </button>
 
          <!-- Hidden file input -->
          <input type="file" id="kml-file-input" accept=".kml" multiple
                 style="display:none;" onchange="handleKMLImport(event)">
        </div>
      </div>

      <!-- Element Type Filter -->
      <div class="field-row">
        <span class="fl">Type</span>
        <div class="fi" style="flex:1;">
          <select id="elem_type" style="width:100%;height:34px;">
            <option value="">All Types</option>
            <option value="primary">Primary Poles</option>
            <option value="secondary">Secondary Poles</option>
            <option value="underbuilt">Underbuilt Poles</option>
            <option value="transformer">Transformers</option>
          </select>
        </div>
      </div>

      <!-- Feeder -->
      <div class="field-row">
        <span class="fl">Feeder</span>
        <div class="fi">
          <input type="text" id="vfeeder" name="vfeeder" placeholder="Feeder ID">
        </div>
        <div class="fg">
          <button type="button" class="btn-i" title="Show filtered elements on map"
                  onclick="showFilteredElements()">
            <i class="fa fa-eye"></i>
          </button>
          <button type="button" class="btn-i red" title="Clear elements"
                  onclick="clearElements()">
            <i class="fa fa-remove"></i>
          </button>
        </div>
      </div>

<!-- Add Pole -->
<div class="field-row">
  <span class="fl">Pole</span>
  <div class="fi" style="flex:1;">
    
<?php if ($can_edit_pole === 'true' || $can_edit_pole_area === 'true'): ?>
    <button type="button" class="btn-chip" onclick="addPole()"
            title="Add a new pole or transformer"
            style="width:50%;justify-content:center;">
      <i class="fa fa-plus-circle" style="color:#00a84a;"></i> Add Pole
    </button>
    <?php else: ?>
    <button type="button" class="btn-chip" disabled
            style="width:100%;justify-content:center;opacity:0.4;cursor:not-allowed;">
      <i class="fa fa-plus-circle"></i> Add Pole
    </button>
    <?php endif; ?>
  </div>
</div>
 
      <!-- Toggle Boundaries -->
      <div class="field-row">
        <span class="fl">Map</span>
        <div class="fi" style="flex:1;">
          <button type="button" class="btn-chip" id="btn-boundaries"
                  onclick="toggleBoundaries()"
                  title="Show / hide barangay boundary overlays"
                  style="width:100%;justify-content:center;">
            <i class="fa fa-map-o"></i> Toggle Boundaries
          </button>
        </div>
      </div>
 <!-- Town selector -->
<div class="field-row" style="margin-top:8px;">
  <span class="fl">Area</span>
  <div class="fi" style="flex:1;">
  <select id="vareaoffice" name="vareaoffice" style="width:100%;">
  <option value="ALL">All Area Offices</option>
  <option value="Central Area Office">Central Area Office</option>
  <option value="SouthEastern Area Office">Southeastern Area Office</option>
  <option value="Eastern Area Office">Eastern Area Office</option>
  <option value="Northern Area Office">Northern Area Office</option>
  <option value="Southern Area Office">Southern Area Office</option>
</select>
  </div>
  <div class="fg">
    <button type="button" class="btn-i" title="Count poles &amp; transformers in this area"
            onclick="countAreaElements()">
      <i class="fa fa-list-ol"></i>
    </button>
  </div>
</div>

      <!-- Town selector -->
<div class="field-row" style="margin-top:8px;">
  <span class="fl">Town</span>
  <div class="fi">
    <select id="vtown" name="vtown" style="width:100%;">
      <option value="">Select Town</option>
    </select>
  </div>
  <div class="fg">
    <button type="button" class="btn-i" title="Town customers only"
            onclick="loadTownCust()">
      <i class="fa fa-home"></i>
    </button>
    <button type="button" class="btn-i" title="Count poles &amp; transformers in this town"
            onclick="countTownElements()">
      <i class="fa fa-list-ol"></i>
    </button>
    <?php if ($can_dl_town === 'true'): ?>
    <button type="button" class="btn-i" title="Download per town"
            onclick="downloadTownCust()">
      <i class="fa fa-download"></i>
    </button>
    <?php endif; ?>
  </div>
</div>

      <!-- Barangay selector -->
      <div class="field-row" style="margin-top:6px;">
        <span class="fl">Brgy</span>
        <div class="fi">
          <select id="vbrgy" name="vbrgy" style="width:100%;">
            <option value="">Barangays</option>
          </select>
        </div>
        <div class="fg">
          <button type="button" class="btn-i" title="Zoom to selection"
                  onclick="searchBarangay()">
            <i class="fa fa-crosshairs"></i>
          </button>
          
          <button type="button" class="btn-i" title="Brgy customers only (selected town)"
                  onclick="loadBrgyCust()">
            <i class="fa fa-home"></i>
          </button>
          <button type="button" class="btn-i" title="Count poles &amp; transformers in this barangay"
                  onclick="countBrgyElements()">
            <i class="fa fa-list-ol"></i>
          </button>
         <?php if ($can_dl_brgy === 'true'): ?>
          <button type="button" class="btn-i" title="Download file per brgy"
                  onclick="downloadBrgyCust()">
            <i class="fa fa-download"></i>
          </button>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
  </div>
</div>

<!-- ADD POLE MODAL -->
<div id="modal-add-pole" style="
  display:none; position:fixed; inset:0; z-index:9999;
  background:rgba(0,0,0,0.45); align-items:center; justify-content:center;">
  <div style="
    background:#fff; border-radius:14px; width:360px; max-width:95vw;
    box-shadow:0 20px 60px rgba(0,0,0,0.3); overflow:hidden;">

    <!-- Header -->
    <div style="
      background:#012b13; padding:14px 18px;
      display:flex; align-items:center; justify-content:space-between;">
      <div style="display:flex;align-items:center;gap:10px;">
        <i class="fa fa-plus-circle" style="color:#00a84a;font-size:16px;"></i>
        <span style="color:#fff;font-weight:600;font-size:14px;">Add Pole / Transformer</span>
      </div>
      <span onclick="closeAddPoleModal()"
        style="color:rgba(255,255,255,0.6);cursor:pointer;font-size:18px;line-height:1;">
        &times;
      </span>
    </div>
    <!-- Body -->
    <div id="modal-add-pole-body" style="padding:18px;">
      <div style="text-align:center;color:#999;padding:20px 0;">
        <i class="fa fa-spinner fa-spin" style="font-size:24px;"></i>
        <div style="margin-top:8px;font-size:13px;">Loading form…</div>
      </div>
    </div>
  </div>
</div>

  <!-- CBOX 4 — Analytics -->
  <div id="cbox4" class="p-shell" style="display:none;">
    <div class="ph">
      <i class="fa fa-bar-chart"></i>
      <h3>Analytics</h3>
      <span class="ph-x" data-close="cbox4"><i class="fa fa-times"></i></span>
    </div>
    <div class="pb">

      <div class="field-row" style="margin-bottom:12px;">
        <span class="fl">Area</span>
        <div class="fi">
          <select name="varea" id="varea">
            <option value="">All Areas</option>
            <option value="01">Area 01</option>
            <option value="02">Area 02</option>
            <option value="03">Area 03</option>
            <option value="04">Area 04</option>
          </select>
        </div>
      </div>

      <div class="chips">
        <button type="button" class="btn-chip" title="Accounts w/ Abrupt Consumption" onclick="doSearch('HL')">
          <i class="fa fa-random"></i> Abrupt
        </button>
        <button type="button" class="btn-chip" title="Disconnected Accounts" onclick="doSearch('DC')">
          <i class="fa fa-plug"></i> Disconnected
        </button>
        <button type="button" class="btn-chip" title="Top 100 KWHr" onclick="doSearch('TK')">
          <i class="fa fa-arrow-up"></i> Top KWH
        </button>
        <button type="button" class="btn-chip" title="Top 100 Demand" onclick="doSearch('TD')">
          <i class="fa fa-arrows-h"></i> Top Demand
        </button>
        <button type="button" class="btn-chip" title="Accounts w/o Location" onclick="doSearch('NL')">
          <i class="fa fa-map-marker"></i> No Location
        </button>
        <button type="button" class="btn-chip" title="Complaints & Requests"
                onclick="PageLink('search_cnr.php','dvpanel-body','fsearch','&vtag=');">
          <i class="fa fa-phone"></i> Complaints
        </button>
      </div>

      <hr>

      <!-- Vehicle Tracking -->
      <div class="field-row" style="margin-bottom:0;">
        <span class="fl">Vehicle</span>
        <div class="fg" style="flex:1;gap:5px;">
          <button type="button" class="btn-chip" id="showVehicle">
            <i class="fa fa-car"></i> Show
          </button>
          <button type="button" class="btn-chip" id="stopVehicle">
            <i class="fa fa-ban"></i> Stop
          </button>
          <button type="button" class="btn-chip"
                  onclick="window.open('vehicle.apk','_blank');">
            <i class="fa fa-download"></i> APK
          </button>
        </div>
      </div>

      <hr>
      <!-- Tags -->
      <div class="field-row">
        <span class="fl">Tags</span>
        <div class="fi">
          <select id="btag" name="btag" style="width:100%;height:34px;">
            <option value="">All Tags</option>
            <?php
              $tag_sql  = "SELECT id, tag, btag FROM tbl_tag ORDER BY tag ASC";
              $tag_stmt = sqlsrv_query($conn, $tag_sql);
              if ($tag_stmt !== false) {
                while ($tag_row = sqlsrv_fetch_array($tag_stmt, SQLSRV_FETCH_ASSOC)) {
                  $tag_val   = htmlspecialchars($tag_row["btag"], ENT_QUOTES, 'UTF-8');
                  $tag_label = htmlspecialchars($tag_row["tag"],  ENT_QUOTES, 'UTF-8');
                  echo "<option value='{$tag_val}'>{$tag_label}</option>";
                }
              }
            ?>
          </select>
        </div>
        <div class="fg">
          <button type="button" class="btn-i" title="Search specific tag" onclick="doSearch('TG')">
            <i class="fa fa-search"></i>
          </button>
          <button type="button" class="btn-i" title="Show all tagged customers" onclick="doSearch('ATG')">
            <i class="fa fa-tags"></i>
          </button>
            <?php if (!$isGuest): ?>
            <button type="button" class="btn-i" title="Add Tag" onclick="addTag()">
              <i class="fa fa-plus"></i>
            </button>
            <?php else: ?>
            <button type="button" class="btn-i" title="Not allowed" disabled
                    style="opacity:0.35; cursor:not-allowed;">
              <i class="fa fa-plus"></i>
            </button>
            <?php endif; ?>
        </div>
      </div>
      <hr>
      <!-- Field Findings -->
      <div class="field-row" style="margin-bottom:0;">
        <span class="fl">Findings</span>
        <div class="fi">
          <?php echo select_table('tbl_cust','ffdesc','ffdesc','sff','','group by ffdesc',''); ?>
        </div>
        <div class="fg">
          <button type="button" class="btn-i" title="Show findings" onclick="doSearch('FF')">
            <i class="fa fa-eye"></i>
          </button>
        </div>
      </div>

    </div>
  </div>

  <!-- DVPANEL — Search Results -->
  <div id="dvpanel" class="p-shell" style="display:none;">
    <div class="pb" id="dvpanel-body">
      <div class="empty-state">
        <i class="fa fa-search"></i>
        Search to see results here
      </div>
    </div>
  </div>

</form>

<!-- MAP -->
<div id="map"></div>

<!-- TOP-CENTER TOOLBAR -->
<div id="upperr">
  <button id="btn_ConsLoc" title="Consumer Location">
    <i class="fa fa-map-marker"></i><span>Location</span>
  </button>
  <button id="btn_Meter" title="Metering">
    <i class="fa fa-tachometer"></i><span>Metering</span>
  </button>
  <button id="btn_BillData" title="Billing Data">
    <i class="fa fa-file-text-o"></i><span>Billing</span>
  </button>
  <button id="btn_ReqCom" title="Requests & Complaints">
    <i class="fa fa-phone"></i><span>Requests</span>
  </button>
  <button id="btn_Clear" title="Close all panels">
    <i class="fa fa-times"></i><span>Clear</span>
  </button>
</div>

<!-- RIGHT PANELS -->
<div id="id01" class="p-shell" style="display:none;">
  <div class="ph">
    <i class="fa fa-user"></i>
    <h3>Customer Info</h3>
    <span class="ph-x" data-close-right><i class="fa fa-times"></i></span>
  </div>
  <div class="pb" id="dvcustinfo">
    <div class="empty-state">
      <i class="fa fa-user-o"></i>
      Select a customer from the search results
    </div>
  </div>
</div>

<div id="id02" class="p-shell" style="display:none;">
  <div class="ph">
    <i class="fa fa-th-list"></i>
    <h3 id="id02-title">Details</h3>
  </div>
  <div class="pb" id="dvcustinfo2"></div>
</div>

<div id="id03" class="p-shell" style="display:none;">
  <div class="ph">
    <i class="fa fa-phone"></i>
    <h3>Requests & Complaints</h3>
    <span class="ph-x" data-close-right><i class="fa fa-times"></i></span>
  </div>
  <div class="pb" id="dvcustinfo3"></div>
</div>

<!-- TAG EDITOR #id06 -->
<div id="id06" class="p-shell right-panel" style="display:none; transform: translateX(100%); transition: transform 0.3s ease-out;">
  <div class="ph">
    <i class="fa fa-tag"></i>
    <h3>Add New Tag</h3>
    <span class="ph-x" data-close="id06" style="cursor:pointer;"><i class="fa fa-times"></i></span>
  </div>
  <div class="pb" id="id06-body">
    <div style="padding:20px;text-align:center;color:#999;">
      <i class="fa fa-image" style="font-size:48px;margin-bottom:12px;opacity:0.5;"></i>
      <div>Fill form below to add new tag icon</div>
    </div>
  </div>
</div>

<!-- UTILITY DIVS -->
<div id="div_msgbox" class="msgboxdiv"></div>
<div id="div_loader" class="msgboxdiv"></div>


<!-- SCRIPTS -->
<script>
const POLE_ICONS = [
  { value: 'primary',     label: 'Primary pole',     src: 'iconsx/ppole.png'   },
  { value: 'secondary',   label: 'Secondary pole',   src: 'iconsx/spole.png'   },
  { value: 'underbuilt',  label: 'Under-built',      src: 'iconsx/upole.png'   },
  { value: 'transformer', label: 'Transformer', src: 'iconsx/xformer.png' },
];

const POLE_TYPES   = POLE_ICONS.map(i => i.label);
const TYPE_TO_ICON = Object.fromEntries(POLE_ICONS.map(i => [i.label, i.value]));
let _addMode     = null;
let _addListener = null;
let _tempMarker  = null;
let _activePoleMarker = null;

//shared helpers

function _stopAddMode() {
  if (_addListener) { google.maps.event.removeListener(_addListener); _addListener = null; }
  if (_tempMarker)  { _tempMarker.setMap(null); _tempMarker = null; }
  _addMode = null;
  map.setOptions({ draggableCursor: '' });
  document.getElementById('add-mode-toast')?.remove();
}

function _showToast(msg) {
  let t = document.getElementById('add-mode-toast');
  if (!t) {
    t = Object.assign(document.createElement('div'), { id: 'add-mode-toast' });
    Object.assign(t.style, {
      position: 'fixed', bottom: '24px', left: '50%', transform: 'translateX(-50%)',
      background: '#012b13', color: '#fff', fontFamily: 'inherit', fontSize: '13px',
      padding: '8px 18px', borderRadius: '20px', zIndex: '9999', pointerEvents: 'none',
    });
    document.body.appendChild(t);
  }
  t.textContent = msg;
}

function _poleIconUrl(iconValue) {
  return POLE_ICONS.find(i => i.value === iconValue)?.src ?? 'iconsx/ppole.png';
}

// Normalise whatever the DB stored to the short key we use for icons
function _normaliseIcon(raw) {
    const v = (raw ?? '').toLowerCase().trim();
    if (v === 'primary'     || v === 'ppole.png')   return 'primary';
    if (v === 'secondary'   || v === 'spole.png')   return 'secondary';
    if (v === 'underbuilt'  || v === 'upole.png')   return 'underbuilt';
    if (v === 'transformer' || v === 'xformer.png') return 'transformer';
    // type-string fallbacks
    if (v.includes('primary'))   return 'primary';
    if (v.includes('secondary')) return 'secondary';
    if (v.includes('under'))     return 'underbuilt';
    if (v.includes('transform')) return 'transformer';
    return 'primary'; // safe default
}

function _poleIconUrl(iconValue) {
    const key = _normaliseIcon(iconValue);
    return POLE_ICONS.find(i => i.value === key)?.src ?? 'iconsx/ppole.png';
}

function _makePoleMarkerIcon(iconValue) {
    return { url: _poleIconUrl(iconValue), scaledSize: new google.maps.Size(16, 16) };
}

function _placeGhostMarker(lat, lng) {
  if (_tempMarker) _tempMarker.setMap(null);
  _tempMarker = new google.maps.Marker({
    position: { lat, lng }, map, zIndex: 20,
    icon: {
      path: google.maps.SymbolPath.CIRCLE,
      scale: 8, fillColor: '#00a84a', fillOpacity: 0.9,
      strokeColor: '#fff', strokeWeight: 2,
    },
  });
  return _tempMarker;
}


function _buildUid(lat, lng) {
  return `${parseFloat(lat).toFixed(10)}${parseFloat(lng).toFixed(7)}`;
}


function _buildModal({ title, badge, bodyHTML, onSave, onCancel }) {
  document.getElementById('_elem-modal-overlay')?.remove();

  const overlay = document.createElement('div');
  overlay.id = '_elem-modal-overlay';
  Object.assign(overlay.style, {
    position: 'fixed', inset: '0', background: 'rgba(0,0,0,.38)',
    display: 'flex', alignItems: 'center', justifyContent: 'center', zIndex: '10000',
  });

  overlay.innerHTML = `
    <div style="background:var(--color-background-primary,#fff);border:0.5px solid var(--color-border-tertiary);
         border-radius:12px;width:340px;overflow:hidden;font-family:inherit;font-size:13px;">
      <div style="background:#012b13;padding:12px 16px;display:flex;align-items:center;justify-content:space-between;">
        <span style="font-size:14px;font-weight:500;color:#fff;">${title}</span>
        <span style="font-size:11px;background:rgba(0,168,74,.2);border:1px solid rgba(0,168,74,.35);
              color:#4ade80;border-radius:20px;padding:2px 10px;">${badge}</span>
      </div>
      <div id="_em-body" style="padding:16px;">${bodyHTML}</div>
      <div id="_em-footer" style="padding:12px 16px;border-top:0.5px solid var(--color-border-tertiary);
           display:flex;gap:8px;justify-content:flex-end;">
        <button id="_em-cancel" style="padding:7px 16px;border:0.5px solid var(--color-border-secondary);
          border-radius:8px;background:transparent;color:var(--color-text-secondary);font-size:13px;cursor:pointer;">
          Cancel
        </button>
        <button id="_em-save" style="padding:7px 18px;border:none;border-radius:8px;
          background:#012b13;color:#fff;font-size:13px;font-weight:500;cursor:pointer;">
          Save
        </button>
      </div>
    </div>`;

  document.body.appendChild(overlay);
  document.getElementById('_em-cancel').onclick = () => { overlay.remove(); onCancel?.(); };
  document.getElementById('_em-save').onclick   = () => onSave(overlay);
  return overlay;
}

const INPUT_STYLE = `width:100%;height:34px;border:0.5px solid var(--color-border-secondary);
  border-radius:8px;padding:0 10px;font-size:13px;
  background:var(--color-background-secondary);color:var(--color-text-primary);`;

const SELECT_STYLE = INPUT_STYLE;

function _label(text) {
  return `<label style="display:block;font-size:11px;color:var(--color-text-secondary);
    font-weight:500;letter-spacing:.04em;text-transform:uppercase;margin-bottom:5px;">${text}</label>`;
}

/* ── Shared form HTML builder ── */
function _buildPoleForm({ poleId = '', lat = '', lng = '', feeder = '', type = '', readonlyId = false } = {}) {
  const normType = _normaliseIcon(type);
  const typeOpts = POLE_ICONS
    .map(i => `<option value="${i.value}" ${i.value === normType ? 'selected' : ''}>${i.label}</option>`)
    .join('');

  return `
    <div style="margin-bottom:14px;">
      ${_label('Pole ID')}
      <input id="_pf-id" value="${poleId}" placeholder="e.g. P-001"
             style="${INPUT_STYLE}" ${readonlyId ? 'readonly' : ''}>
    </div>
    <div style="display:flex;gap:8px;margin-bottom:14px;">
      <div style="flex:1;">
        ${_label('Latitude')}
        <input id="_pf-lat" value="${lat}" style="${INPUT_STYLE}">
      </div>
      <div style="flex:1;">
        ${_label('Longitude')}
        <input id="_pf-lng" value="${lng}" style="${INPUT_STYLE}">
      </div>
    </div>
    <div style="margin-bottom:14px;">
      ${_label('Feeder ID')}
      <input id="_pf-feeder" value="${feeder}" placeholder="e.g. F01-A" style="${INPUT_STYLE}">
    </div>
    <div style="margin-bottom:14px;">
      ${_label('Pole type')}
      <select id="_pf-type" style="${SELECT_STYLE}">
        <option value="">— Select type —</option>${typeOpts}
      </select>
    </div>`;
}

function _readPoleForm() {
  return {
    poleId: document.getElementById('_pf-id')?.value.trim()    ?? null,
    lat:    document.getElementById('_pf-lat')?.value.trim()    ?? '',
    lng:    document.getElementById('_pf-lng')?.value.trim()    ?? '',
    feeder: document.getElementById('_pf-feeder')?.value.trim() ?? '',
    type:   document.getElementById('_pf-type')?.value.trim()   ?? '',
  };
}

function _validatePoleForm({ poleId, lat, lng, feeder, type }, requireId = true) {
  if (requireId && !poleId)           { showmsgbox('Validation', 'Please enter a Pole ID.');        return false; }
  if (!lat || isNaN(parseFloat(lat))) { showmsgbox('Validation', 'Please enter a valid latitude.');  return false; }
  if (!lng || isNaN(parseFloat(lng))) { showmsgbox('Validation', 'Please enter a valid longitude.'); return false; }
  if (!feeder)                        { showmsgbox('Validation', 'Please enter a Feeder ID.');       return false; }
  if (!type)                          { showmsgbox('Validation', 'Please select a pole type.');      return false; }
  return true;
}

// add pole
function addPole() {
  if (!CAN_EDIT_POLE && !CAN_EDIT_POLE_AREA) {
    showmsgbox('Access Denied', 'You do not have permission to add poles.');
    return;
  }
  if (_addMode) _stopAddMode();
  _addMode = 'POLE';
  map.setOptions({ draggableCursor: 'crosshair' });
  _showToast('Click the map to place a pole');

  _addListener = map.addListener('click', async (e) => {
    const lat = e.latLng.lat().toFixed(10);
    const lng  = e.latLng.lng().toFixed(7);

    // Remove the click listener immediately so double-clicks don't fire twice,
    // but keep the crosshair + toast until access check resolves
    google.maps.event.removeListener(_addListener);
    _addListener = null;
    _addMode = null;

    _placeGhostMarker(parseFloat(lat), parseFloat(lng));
    _showToast('Checking area boundary…');

    // Access check BEFORE stopping cursor/toast
    const access = await _checkAreaAccess(parseFloat(lat), parseFloat(lng));

    // Now clean up cursor and toast
    map.setOptions({ draggableCursor: '' });
    document.getElementById('add-mode-toast')?.remove();

    if (!access.allowed) {
      _tempMarker?.setMap(null);
      _tempMarker = null;
      showmsgbox('Access Denied', access.reason);
      return;
    }

    const prefillFeeder = document.getElementById('vfeeder')?.value.trim() ?? '';
    _buildModal({
      title:    'Add pole',
      badge:    `${parseFloat(lat).toFixed(4)}, ${parseFloat(lng).toFixed(4)}`,
      bodyHTML: _buildPoleForm({ lat, lng, feeder: prefillFeeder }),
      onCancel: () => { _tempMarker?.setMap(null); _tempMarker = null; },
      onSave:   (ov) => _savePole(ov),
    });
  });
} 

async function _savePole(overlay) {
  const fields = _readPoleForm();
  if (!_validatePoleForm(fields)) return;

  // fields.type is now the icon key ('primary', 'secondary', etc.)
  const icon      = fields.type || 'primary';
  const typeLabel = POLE_ICONS.find(i => i.value === icon)?.label ?? icon;
  const uid       = _buildUid(fields.lat, fields.lng);
  const btn       = document.getElementById('_em-save');
  btn.disabled = true; btn.textContent = 'Saving…';

  try {
    const fd = new FormData();
    fd.append('type',     typeLabel); // full label → DB type column
    fd.append('feeder',   fields.feeder);
    fd.append('pole_id',  fields.poleId);
    fd.append('gps_lat',  fields.lat);
    fd.append('gps_long', fields.lng);
    fd.append('uid',      uid);
    fd.append('icon',     icon);      // short key → DB icon column

    const data = await _post('save_element.php', fd);

    if (data.success) {
      overlay.remove();
      _tempMarker?.setMap(null); _tempMarker = null;
      _addPoleMarkerToMap({
        trn:    data.trn,
        lat:    parseFloat(fields.lat),
        lng:    parseFloat(fields.lng),
        type:   typeLabel,
        feeder: fields.feeder,
        icon,
      });
      showmsgbox('Success', 'Pole saved! Pole ID: <strong>' + fields.poleId + '</strong>');
    } else {
      _resetBtn(btn, 'Save');
      showmsgbox('Error', 'Save failed: ' + (data.message ?? 'unknown error'));
    }
  } catch (err) {
    _resetBtn(btn, 'Save');
    console.error('[_savePole]', err);
    showmsgbox('Error', 'An error occurred: ' + err.message);
  }
}

//view edit  pole
async function showPoleInfo(trn, marker) {
  try {
    const result = await _get('fetch_element.php?trn=' + encodeURIComponent(trn));
    if (!result.success) {
      showmsgbox('Error', 'Failed to load pole: ' + (result.message ?? 'unknown error'));
      return;
    }

    const p = result.data;
    _activePoleMarker = marker;

    const overlay = _buildModal({
      title:    'Pole information',
      badge:    p.feeder ?? 'N/A',
      bodyHTML: _buildPoleForm({
        poleId:     p.id       ?? '',
        lat:        p.gps_lat  ?? '',
        lng:        p.gps_long ?? '',
        feeder:     p.feeder   ?? '',
        type:       p.icon     ?? p.type ?? '',
        readonlyId: true,
      }) + `
      <div style="padding:10px;background:var(--color-background-secondary);border-radius:8px;
                  font-size:11px;color:var(--color-text-secondary);margin-top:2px;">
        <strong>ID:</strong> ${p.id ?? '—'} &nbsp;|&nbsp;
        <strong>TRN:</strong> ${p.trn ?? '—'} &nbsp;|&nbsp;
        <strong>UID:</strong> ${p.uid ?? '—'}
      </div>`,
      onCancel: () => overlay.remove(),
      onSave:   (ov) => _updatePole(trn, ov),
    });

    const saveBtn = document.getElementById('_em-save');
    const footer  = document.getElementById('_em-footer');

    let canEdit = CAN_EDIT_POLE;

    if (!canEdit && CAN_EDIT_POLE_AREA && USER_AREAS.length > 0) {
      try {
        const locResult = await _get(
          `api_boundaries.php?action=locate&lat=${encodeURIComponent(p.gps_lat)}&lng=${encodeURIComponent(p.gps_long)}`
        );
        const poleArea = (locResult?.AreaOffice ?? '').trim().toLowerCase();
        canEdit = poleArea !== '' && USER_AREAS.map(a => a.trim().toLowerCase()).includes(poleArea);
      } catch (err) {
        console.error('[showPoleInfo locate]', err);
        canEdit = false;
      }
    }

    if (canEdit) {
      saveBtn.textContent = 'Update';
      const deleteBtn = Object.assign(document.createElement('button'), {
        id: '_em-delete', textContent: 'Delete',
      });
      Object.assign(deleteBtn.style, {
        padding: '7px 18px', border: 'none', borderRadius: '8px',
        background: '#c62828', color: '#fff', fontSize: '13px',
        fontWeight: '500', cursor: 'pointer', marginRight: 'auto',
      });
      deleteBtn.onclick = () => _confirmDeletePole(trn, overlay);
      footer.insertBefore(deleteBtn, footer.firstChild);
    } else {
      saveBtn.style.display = 'none';
      const cancelBtn = document.getElementById('_em-cancel');
      if (cancelBtn) cancelBtn.textContent = 'Close';

      overlay.querySelectorAll('input, select').forEach(f => {
        f.disabled = true;
        f.style.opacity = '0.7';
      });

      if (CAN_EDIT_POLE_AREA && USER_AREAS.length > 0) {
        const note = document.createElement('div');
        note.style.cssText = 'padding:8px 10px;background:#fff8e1;color:#e65100;' +
          'border-radius:6px;font-size:11px;margin-top:8px;';
        note.innerHTML = '<i class="fa fa-lock"></i> This pole is outside your assigned area(s): ' +
          '<strong>' + USER_AREAS.join(', ') + '</strong>';
        document.getElementById('_em-body').appendChild(note);
      }
    }

  } catch (err) {
    console.error('[showPoleInfo]', err);
    showmsgbox('Error', 'Error loading pole: ' + err.message);
  }
}

async function _updatePole(trn, overlay) {
  if (!CAN_EDIT_POLE && !CAN_EDIT_POLE_AREA) {
    showmsgbox('Access Denied', 'You do not have permission to edit poles.');
    return;
  }

  const fields = _readPoleForm();
  if (!_validatePoleForm(fields, false)) return;

  if (!CAN_EDIT_POLE && CAN_EDIT_POLE_AREA) {
    const access = await _checkAreaAccess(parseFloat(fields.lat), parseFloat(fields.lng));
    if (!access.allowed) {
      showmsgbox('Access Denied', access.reason);
      return;
    }
  }

  const icon      = fields.type || 'primary';
  const typeLabel = POLE_ICONS.find(i => i.value === icon)?.label ?? icon;
  const uid       = _buildUid(fields.lat, fields.lng);
  const btn       = document.getElementById('_em-save');
  btn.disabled = true; btn.textContent = 'Updating…';

  try {
    const fd = new FormData();
    fd.append('trn',      trn);
    fd.append('uid',      uid);
    fd.append('type',     typeLabel);
    fd.append('feeder',   fields.feeder);
    fd.append('icon',     icon);
    fd.append('gps_lat',  fields.lat);
    fd.append('gps_long', fields.lng);

    const data = await _post('update_element.php', fd);

    if (data.success) {
      overlay.remove();
      if (_activePoleMarker) {
        _activePoleMarker.setPosition({ lat: parseFloat(fields.lat), lng: parseFloat(fields.lng) });
        _activePoleMarker.setTitle(`${typeLabel} — ${fields.feeder}`);
        _activePoleMarker.setIcon(_makePoleMarkerIcon(icon));
      }
      showmsgbox('Success', 'Pole updated successfully.');
    } else {
      _resetBtn(btn, 'Update');
      showmsgbox('Error', 'Update failed: ' + (data.message ?? 'unknown error'));
    }
  } catch (err) {
    _resetBtn(btn, 'Update');
    console.error('[_updatePole]', err);
    showmsgbox('Error', 'An error occurred: ' + err.message);
  }
}

// delete pole

/* Stores the pending delete so showmsgboxYN can invoke it via a js: URL */
let _pendingDeleteTrn     = null;
let _pendingDeleteOverlay = null;

function _confirmDeletePole(trn, overlay) {
  _pendingDeleteTrn     = trn;
  _pendingDeleteOverlay = overlay;
  showmsgboxYN(
    'Confirm Delete',
    'Delete this pole? <strong>This cannot be undone.</strong>',
    'javascript:_executePoleDelete()',
    ''
  );
}

/* Called by showmsgboxYN when the user clicks Yes */
async function _executePoleDelete() {
  const trn     = _pendingDeleteTrn;
  const overlay = _pendingDeleteOverlay;
  _pendingDeleteTrn     = null;
  _pendingDeleteOverlay = null;

  if (!trn) return;

  // Area-specific users: re-verify before deleting
  if (!CAN_EDIT_POLE && CAN_EDIT_POLE_AREA && _activePoleMarker) {
    const pos    = _activePoleMarker.getPosition();
    const access = await _checkAreaAccess(pos.lat(), pos.lng());
    if (!access.allowed) {
      showmsgbox('Access Denied', access.reason);
      return;
    }
  }

  const btn = document.getElementById('_em-delete');
  if (btn) { btn.disabled = true; btn.textContent = 'Deleting…'; }

  try {
    const fd = new FormData();
    fd.append('trn', trn);
    const data = await _post('delete_element.php', fd);

    if (data.success) {
      _activePoleMarker?.setMap(null);
      _activePoleMarker = null;
      overlay?.remove();
      showmsgbox('Success', 'Pole deleted successfully.');
    } else {
      if (btn) _resetBtn(btn, 'Delete');
      showmsgbox('Error', 'Delete failed: ' + (data.message ?? 'unknown error'));
    }
  } catch (err) {
    if (btn) _resetBtn(btn, 'Delete');
    console.error('[_executePoleDelete]', err);
    showmsgbox('Error', 'An error occurred: ' + err.message);
  }
}

async function _post(url, formData) {
  const res = await fetch(url, { method: 'POST', body: formData });
  if (!res.ok) throw new Error('HTTP ' + res.status);
  const text = await res.text();
  try {
    return JSON.parse(text);
  } catch (e) {
    console.error('[_post] Non-JSON from ' + url + ':', text);
    throw new Error('Server returned non-JSON. Check console for details.');
  }
}

async function _get(url) {
  const res = await fetch(url);
  if (!res.ok) throw new Error('HTTP ' + res.status);
  return res.json();
}

function _resetBtn(btn, label) {
  btn.disabled    = false;
  btn.textContent = label;
}

async function _checkAreaAccess(lat, lng) {
  if (CAN_EDIT_POLE) return { allowed: true };

  if (!USER_AREAS.length) return { allowed: false, reason: 'Your account has no area office assigned.' };

  try {
    const result = await _get(
      `api_boundaries.php?action=locate&lat=${encodeURIComponent(lat)}&lng=${encodeURIComponent(lng)}`
    );

    if (!result || !result.AreaOffice) {
      return { allowed: false, reason: 'This location is outside any known area boundary.' };
    }

    const poleArea = result.AreaOffice.trim().toLowerCase();

    if (!USER_AREAS.map(a => a.trim().toLowerCase()).includes(poleArea)) {
      return {
        allowed: false,
        reason: `This location belongs to <strong>${result.AreaOffice}</strong>.<br>
                 You can only edit poles in <strong>${USER_AREAS.join(', ')}</strong>.`
      };
    }

    return { allowed: true };

  } catch (err) {
    console.error('[_checkAreaAccess]', err);
    return { allowed: false, reason: 'Could not verify area boundary: ' + err.message };
  }
}

/* LAYOUT CONSTANTS */
const SIDEBAR_W = 64;
const PANEL_GAP = 10;
const TOP_BAR_H = 62;

/* HELPERS */
function el(id) { return document.getElementById(id); }
function show(e) { if (e) e.style.display = 'flex'; }
function hide(e) { if (e) e.style.display = 'none'; }
function isVisible(e) { return e && e.style.display !== 'none' && e.style.display !== ''; }

function positionDvPanel() {
  const dv = el('dvpanel');
  if (!isVisible(dv)) return;
  let anchor = TOP_BAR_H;
  for (const id of ['cbox2','cbox3','cbox4']) {
    const c = el(id);
    if (isVisible(c)) { anchor = c.getBoundingClientRect().bottom + PANEL_GAP; break; }
  }
  dv.style.top = anchor + 'px';
}

function positionRightPanels() {
  let top = TOP_BAR_H;
  ['id01', 'id02', 'id03', 'id06', 'id07'].forEach(id => {
    const panel = el(id);
    if (isVisible(panel)) {
      panel.style.top = top + 'px';
      top = panel.getBoundingClientRect().bottom + PANEL_GAP;
    }
  });
}

function positionId02() {
  positionRightPanels();
}

new ResizeObserver(positionId02).observe(el('id01'));

/* LEFT PANEL TOGGLE */
let activePanel = null;

function hideAllLeft() {
  ['cbox2','cbox3','cbox4','dvpanel'].forEach(id => hide(el(id)));
  document.querySelectorAll('.sidebar-item').forEach(s => s.classList.remove('is-active'));
  activePanel = null;
}

function openLeft(panelId, sidebarEl, showDv) {
  hideAllLeft();
  show(el(panelId));
  if (showDv) show(el('dvpanel'));
  if (sidebarEl) sidebarEl.classList.add('is-active');
  activePanel = panelId;
  requestAnimationFrame(positionDvPanel);
}

document.querySelectorAll('.sidebar-item[data-panel]').forEach(item => {
  item.addEventListener('click', function () {
    const pid = this.dataset.panel;
    const sdv = this.dataset.dvpanel === '1';
    if (activePanel === pid) { hideAllLeft(); return; }
    openLeft(pid, this, sdv);
  });
});

el('tog5')?.addEventListener('click', () => window.open('ems/index.html', '_blank'));

document.querySelectorAll('.ph-x[data-close]').forEach(btn => {
  btn.addEventListener('click', function () {
    const id = this.dataset.close;
    hide(el(id));
    if (id !== 'dvpanel') {
      hide(el('dvpanel'));
      document.querySelectorAll('.sidebar-item').forEach(s => s.classList.remove('is-active'));
      activePanel = null;
    }
  });
});

/* SEARCH */
function setHidden(name, value) {
  const form = el('fsearch');
  let inp = form.querySelector('input[name="' + name + '"]');
  if (!inp) {
    inp = document.createElement('input');
    inp.type = 'hidden';
    inp.name = name;
    form.appendChild(inp);
  }
  inp.value = value;
}

function doSearch(vtag) {
  setHidden('vtag', vtag);
  if (vtag === 'TG') {
    const btagVal = document.getElementById('btag')?.value || '';
    if (!btagVal) { alert('Please select a tag first'); return; }
    setHidden('btag', btagVal);
  }
  setHidden('varea', document.getElementById('varea')?.value || '');
  show(el('dvpanel'));
  requestAnimationFrame(positionDvPanel);
  PageLink('search.php', 'dvpanel-body', 'fsearch', '');
}

el('searchBtn').addEventListener('click', () => doSearch('NS'));
el('vsearch').addEventListener('keydown', e => {
  if (e.key === 'Enter') { e.preventDefault(); doSearch('NS'); }
});

/* TAG PANEL */
function addTag() {
  const id06 = el('id06');
  if (!id06) return;
  id06.style.top = TOP_BAR_H + 'px';
  show(id06);
  id06.style.transform = 'translateX(0)';
  positionRightPanels();
  fetch('tag_form.php')
    .then(r => r.text())
    .then(html => {
      el('id06-body').innerHTML = html;
      el('id06-body').querySelectorAll('script').forEach(s => {
        const sc = document.createElement('script');
        sc.textContent = s.textContent;
        document.body.appendChild(sc);
        document.body.removeChild(sc);
      });
    })
    .catch(err => {
      el('id06-body').innerHTML = `
        <div style="color:#c62828;padding:14px;font-size:13px;">
          <i class="fa fa-exclamation-triangle"></i> ${err.message}
        </div>`;
    });
}

function closeTagPanel() {
  const id06 = el('id06');
  if (!id06) return;
  id06.style.transform = 'translateX(100%)';
  setTimeout(() => hide(id06), 300);
}

document.addEventListener('click', (e) => {
  if (e.target.dataset.close === 'id06') {
    closeTagPanel();
  }
});

/* RIGHT PANEL BUTTONS */
function getAccountNo() {
  const f = el('frmcInfoData');
  return f ? (f.querySelector('#t_accountno')?.value ?? null) : null;
}

function showMain(title, url) {
  show(el('id01')); show(el('id02')); hide(el('id03'));
  el('id01').style.top = TOP_BAR_H + 'px';
  el('id02-title').textContent = title;
  const acct = getAccountNo();
  if (acct && url) PageLink(url, 'dvcustinfo2', 'fsearch', '&vaccount=' + acct);
  requestAnimationFrame(positionId02);
}

function showReq() {
  hide(el('id01')); hide(el('id02')); show(el('id03'));
  el('id03').style.top = TOP_BAR_H + 'px';
  const inp = el('frmcInfoData')?.querySelector('#t_accountno');
  if (inp) {
    inp.value = inp.value.replace(/-/g, '');
    PageLink('comnreq.php', 'dvcustinfo3', 'frmcInfoData', '&trn=');
  }
}

el('btn_ConsLoc' ).addEventListener('click', () => showMain('Location', 'custinfo_consloc.php'));
el('btn_Meter'   ).addEventListener('click', () => showMain('Metering', 'custinfo_meter.php'));
el('btn_BillData').addEventListener('click', () => showMain('Billing',  'custinfo_billdata.php'));
el('btn_ReqCom'  ).addEventListener('click', () => showReq());
el('btn_Clear'   ).addEventListener('click', () => {
  hide(el('id01')); hide(el('id02')); hide(el('id03'));
});
document.querySelectorAll('[data-close-right]').forEach(b => {
  b.addEventListener('click', () => {
    hide(el('id01')); hide(el('id02'));
    hide(el('id03')); hide(el('id07'));
  });
});

/* FEEDER ELEMENTS */
async function countFeederElements() {
  const feeder = el('vfeeder')?.value.trim() || '';
  const body = el('dvpanel-body');
  if (body) body.innerHTML = '<div style="padding:12px;color:#555;"><i class="fa fa-spinner fa-spin"></i> Loading feeder counts…</div>';
  show(el('dvpanel'));
  requestAnimationFrame(positionDvPanel);

  try {
    const fd = new FormData();
    if (feeder) fd.append('vfeeder', feeder);
    const res  = await fetch('count_feeder_elements.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.error) throw new Error(data.error);
    if(!data.data || data.data.length === 0) {
      body.innerHTML = '<div style="color:#c62828;padding:8px;"><i class="fa fa-exclamation-triangle"></i> No data found.</div>';
      return;
    }
    const title = feeder
      ? `Feeder <strong>${feeder}</strong> — element counts`
      : `All Feeders — element counts`;
    const rows = data.data.map(r => `
      <tr style="border-bottom:1px solid rgba(0,0,0,.05);">
        <td style="padding:6px 10px;font-weight:500;">${r.feeder || '—'}</td>
        <td style="padding:6px 10px;text-align:right;">${r.primary_count}</td>
        <td style="padding:6px 10px;text-align:right;">${r.secondary_count}</td>
        <td style="padding:6px 10px;text-align:right;">${r.underbuilt_count}</td>
        <td style="padding:6px 10px;text-align:right;">${r.transformer_count}</td>
        <td style="padding:6px 10px;text-align:right;font-weight:600;">${r.total}</td>
      </tr>`).join('');
    const grand = data.data.reduce((acc, r) => {
      acc.primary     += parseInt(r.primary_count)     || 0;
      acc.secondary   += parseInt(r.secondary_count)   || 0;
      acc.underbuilt  += parseInt(r.underbuilt_count)  || 0;
      acc.transformer += parseInt(r.transformer_count) || 0;
      acc.total       += parseInt(r.total)             || 0;
      return acc;
    }, { primary: 0, secondary: 0, underbuilt: 0, transformer: 0, total: 0 });
    body.innerHTML = `
      <div style="padding:10px 12px 6px;font-size:12px;color:#555;">${title}</div>
      <div style="overflow-x:auto;">
      <table style="width:100%;border-collapse:collapse;font-size:12px;">
        <thead>
          <tr style="background:rgba(0,0,0,.04);border-bottom:1px solid rgba(0,0,0,.08);">
            <th style="padding:6px 10px;text-align:left;font-weight:500;">Feeder</th>
            <th style="padding:6px 10px;text-align:right;font-weight:500;" title="Primary">Pri</th>
            <th style="padding:6px 10px;text-align:right;font-weight:500;" title="Secondary">Sec</th>
            <th style="padding:6px 10px;text-align:right;font-weight:500;" title="Underbuilt">UB</th>
            <th style="padding:6px 10px;text-align:right;font-weight:500;" title="Transformer">Xfmr</th>
            <th style="padding:6px 10px;text-align:right;font-weight:500;">Total</th>
          </tr>
        </thead>
        <tbody>${rows}</tbody>
        <tfoot>
          <tr style="border-top:2px solid rgba(0,0,0,.12);background:rgba(0,0,0,.02);">
            <td style="padding:6px 10px;font-weight:600;">Total</td>
            <td style="padding:6px 10px;text-align:right;font-weight:600;">${grand.primary}</td>
            <td style="padding:6px 10px;text-align:right;font-weight:600;">${grand.secondary}</td>
            <td style="padding:6px 10px;text-align:right;font-weight:600;">${grand.underbuilt}</td>
            <td style="padding:6px 10px;text-align:right;font-weight:600;">${grand.transformer}</td>
            <td style="padding:6px 10px;text-align:right;font-weight:600;">${grand.total}</td>
          </tr>
        </tfoot>
      </table>
      </div>`;
  } catch (err) {
    console.error('[countFeederElements]', err);
    if (body) body.innerHTML =
      '<div style="color:#c62828;padding:8px;"><i class="fa fa-exclamation-triangle"></i> Failed: ' + err.message + '</div>';
  }
}

async function countBrgyElements() {
  const town = el('vtown')?.value?.trim() || '';
  const brgy = el('vbrgy')?.value?.trim() || '';
  if (!town) { alert('Please select a Town first.');     return; }
  if (!brgy) { alert('Please select a Barangay first.'); return; }

  const elemType = el('elem_type')?.value || '';
  const body = el('dvpanel-body');
  if (body) body.innerHTML = '<div style="padding:12px;color:#555;"><i class="fa fa-spinner fa-spin"></i> Loading elements…</div>';
  show(el('dvpanel'));
  requestAnimationFrame(positionDvPanel);

  try {
    const ring = await tbEnsureBrgyPolygon(town, brgy);
    if (!ring || ring.length === 0) {
      body.innerHTML = '<div style="color:#c62828;padding:8px;"><i class="fa fa-exclamation-triangle"></i> No boundary data found for <b>' + brgy + '</b>.</div>';
      return;
    }
    const fd2 = new FormData();
    fd2.append('vtown',    town);
    fd2.append('vbrgy',    brgy);
    fd2.append('vpolygon', JSON.stringify([ring]));
    if (elemType) fd2.append('filter_type', elemType);
    const countRes = await fetch('count_elements.php', { method: 'POST', body: fd2 });
    const data = await countRes.json();
    if (data.error) throw new Error(data.error);

    const fd3 = new FormData();
    fd3.append('vfeeder',  '');
    fd3.append('vtown',    town);
    fd3.append('vbrgy',    brgy);
    fd3.append('vpolygon', JSON.stringify([ring]));
    if (elemType) fd3.append('filter_type', elemType);
    const elemRes = await fetch('view_elements.php', { method: 'POST', body: fd3 });
    const html = await elemRes.text();
    const tmp = document.createElement('div');
    tmp.innerHTML = html;
    tmp.querySelectorAll('script').forEach(s => {
      const sc = document.createElement('script');
      sc.textContent = s.textContent;
      document.body.appendChild(sc);
    });

    const rows = (data.counts || []).map(r =>
      `<tr>
         <td style="padding:6px 10px;text-transform:capitalize;">${r.icon || r.type || '—'}</td>
         <td style="padding:6px 10px;text-align:right;font-weight:500;">${r.total}</td>
       </tr>`).join('');
    body.innerHTML = `
      <div style="padding:10px 12px 4px;font-size:12px;color:#555;">
        <strong>${brgy}</strong> — poles & transformers
        <div style="margin-top:4px;padding:4px 8px;background:#e6f7ee;color:#00a84a;
                    border-radius:6px;font-size:11px;display:inline-block;">
          <i class="fa fa-map-marker"></i> Displayed on map
        </div>
      </div>
      <table style="width:100%;border-collapse:collapse;font-size:13px;">
        <thead>
          <tr style="background:rgba(0,0,0,.04);border-bottom:1px solid rgba(0,0,0,.08);">
            <th style="padding:6px 10px;text-align:left;font-weight:500;">Type</th>
            <th style="padding:6px 10px;text-align:right;font-weight:500;">Count</th>
          </tr>
        </thead>
        <tbody>${rows || '<tr><td colspan="2" style="padding:8px 10px;color:#999;">No elements found.</td></tr>'}</tbody>
      </table>`;
  } catch (err) {
    console.error('[countBrgyElements]', err);
    if (body) body.innerHTML =
      '<div style="color:#c62828;padding:8px;"><i class="fa fa-exclamation-triangle"></i> Failed to load elements: ' + err.message + '</div>';
  }
}

async function countTownElements() {
  const town = el('vtown')?.value?.trim() || '';
  if (!town) { alert('Please select a Town first.'); return; }

  const body = el('dvpanel-body');
  if (body) body.innerHTML = '<div style="padding:12px;color:#555;"><i class="fa fa-spinner fa-spin"></i> Loading elements…</div>';
  show(el('dvpanel'));
  requestAnimationFrame(positionDvPanel);

  try {
    /* 1. Get town boundary rings */
    const rings = await tbEnsureTownPolygons(town);
    if (!rings || rings.length === 0) {
      body.innerHTML = '<div style="color:#c62828;padding:8px;"><i class="fa fa-exclamation-triangle"></i> No boundary data found for <b>' + town + '</b>.</div>';
      return;
    }

    const elemType = el('elem_type')?.value || '';

    /* 2. Display poles on map */
    const fdMap = new FormData();
    fdMap.append('vfeeder',  '');
    fdMap.append('vtown',    town);
    fdMap.append('vbrgy',    '');
    fdMap.append('vpolygon', JSON.stringify(rings));
    if (elemType) fdMap.append('filter_type', elemType);

    const mapRes = await fetch('view_elements.php', { method: 'POST', body: fdMap });
    const html   = await mapRes.text();
    const tmp    = document.createElement('div');
    tmp.innerHTML = html;
    tmp.querySelectorAll('script').forEach(s => {
      const sc = document.createElement('script');
      sc.textContent = s.textContent;
      document.body.appendChild(sc);
      document.body.removeChild(sc);
    });

    /* 3. Get counts */
    const fdCount = new FormData();
    fdCount.append('vtown',    town);
    fdCount.append('vbrgy',    '');
    fdCount.append('vpolygon', JSON.stringify(rings));
    if (elemType) fdCount.append('filter_type', elemType);

    const countRes = await fetch('count_elements.php', { method: 'POST', body: fdCount });
    const data     = await countRes.json();
    if (data.error) throw new Error(data.error);

    /* 4. Summary rows (Primary, Secondary, Underbuilt, Transformers, Total) */
    const counts = data.counts || [];
    const summaryRows = counts.map(r =>
      `<tr style="border-bottom:1px solid rgba(0,0,0,.05);">
         <td style="padding:6px 10px;">${r.icon}</td>
         <td style="padding:6px 10px;text-align:right;font-weight:500;">${r.total}</td>
       </tr>`
    ).join('');

    /* 5. Per-barangay breakdown rows */
    const brgyData = data.poles_per_town || [];
    const brgyRows = brgyData.map(r =>
      `<tr style="border-bottom:1px solid rgba(0,0,0,.05);">
         <td style="padding:5px 10px;">${r.town}</td>
         <td style="padding:5px 10px;text-align:right;">${r.primary}</td>
         <td style="padding:5px 10px;text-align:right;">${r.secondary}</td>
         <td style="padding:5px 10px;text-align:right;">${r.underbuilt}</td>
         <td style="padding:5px 10px;text-align:right;">${r.transformer}</td>
         <td style="padding:5px 10px;text-align:right;font-weight:600;">${r.total}</td>
       </tr>`
    ).join('');

          
    body.innerHTML = `
      <div style="padding:10px 12px 4px;font-size:12px;color:#555;">
        <strong>${town}</strong> — poles &amp; transformers
        <div style="margin-top:4px;padding:4px 8px;background:#e6f7ee;color:#00a84a;
                    border-radius:6px;font-size:11px;display:inline-block;">
          <i class="fa fa-map-marker"></i> Displayed on map
        </div>
      </div>

      <table style="width:100%;border-collapse:collapse;font-size:13px;">
        <thead>
          <tr style="background:rgba(0,0,0,.04);border-bottom:1px solid rgba(0,0,0,.08);">
            <th style="padding:6px 10px;text-align:left;font-weight:500;">Type</th>
            <th style="padding:6px 10px;text-align:right;font-weight:500;">Count</th>
          </tr>
        </thead>
        <tbody>${summaryRows || '<tr><td colspan="2" style="padding:8px 10px;color:#999;">No elements found.</td></tr>'}</tbody>
      </table>`;

    document.getElementById('btn-clear-town-elements')?.addEventListener('click', () => {
      if (typeof clearElements === 'function') clearElements();
      body.innerHTML = '<div style="padding:12px;color:#2e7d32;"><i class="fa fa-check"></i> Poles cleared from map</div>';
    });

  } catch (err) {
    console.error('[countTownElements]', err);
    if (body) body.innerHTML =
      '<div style="color:#c62828;padding:8px;"><i class="fa fa-exclamation-triangle"></i> Failed to load elements: ' + err.message + '</div>';
  }
}

let _areaElemAbort = null;

async function countAreaElements() {
  const areaSelect = el('vareaoffice');
  const areaRaw    = areaSelect?.value?.trim() || '';
  const isAll      = (areaRaw === 'ALL' || areaRaw === '');
  const areaVal    = isAll ? '' : areaRaw;
  const areaLabel  = isAll
    ? 'All Area Offices'
    : (areaSelect?.options[areaSelect.selectedIndex]?.text || areaRaw);
  const elemType = el('elem_type')?.value || '';
  const body     = el('dvpanel-body');

  _areaElemAbort?.abort();
  _areaElemAbort = new AbortController();
  const sig = _areaElemAbort.signal;

  if (body) body.innerHTML =
    '<div style="padding:12px;color:#555;">' +
    '<i class="fa fa-spinner fa-spin"></i> Loading <strong>' + areaLabel + '</strong>…</div>';
  show(el('dvpanel'));
  requestAnimationFrame(positionDvPanel);

  try {
    const fd = new FormData();
    if (areaVal)  fd.append('area_office', areaVal);
    if (elemType) fd.append('filter_type', elemType);

    const res = await fetch('count_area_elements.php', { method: 'POST', body: fd, signal: sig });
    const raw = await res.text();
    let data;
    try { data = JSON.parse(raw); }
    catch(e) { throw new Error('PHP error — see console for raw output'); }
    if (data.error) throw new Error(data.error);

    const counts = Array.isArray(data.counts) ? data.counts : [];
    if (!counts.length) {
      body.innerHTML = '<div style="color:#c62828;padding:8px;">' +
        '<i class="fa fa-exclamation-triangle"></i> No elements found for <b>' + areaLabel + '</b>.</div>';
      return;
    }

    const ICON_LABEL = {
      primary:     'Primary poles',
      secondary:   'Secondary poles',
      underbuilt:  'Underbuilt poles',
      transformer: 'Transformers',
      Total:       '<strong>Total</strong>',
    };

    const summaryRows = counts.map(r => {
      const isTotal = r.icon === 'Total';
      return `<tr style="border-bottom:1px solid rgba(0,0,0,.05)
              ${isTotal ? ';background:rgba(0,0,0,.03);border-top:2px solid rgba(0,0,0,.10)' : ''}">
        <td style="padding:7px 10px;${isTotal ? 'font-weight:600;' : ''}">
          ${ICON_LABEL[r.icon] || r.icon}
        </td>
        <td style="padding:7px 10px;text-align:right;font-weight:${isTotal ? '700' : '500'};">
          ${Number(r.total).toLocaleString()}
        </td>
      </tr>`;
    }).join('');

    body.innerHTML = `
      <div style="padding:10px 12px 4px;font-size:12px;color:#555;">
        <strong>${areaLabel}</strong> — poles &amp; transformers
        <div id="_area-map-badge" style="margin-top:4px;padding:4px 8px;
             background:#fff8e1;color:#e65100;border-radius:6px;
             font-size:11px;display:inline-block;">
          <i class="fa fa-spinner fa-spin"></i> Placing markers…
        </div>
      </div>
      <table style="width:100%;border-collapse:collapse;font-size:13px;">
        <thead>
          <tr style="background:rgba(0,0,0,.04);border-bottom:1px solid rgba(0,0,0,.08);">
            <th style="padding:7px 10px;text-align:left;font-weight:500;">Type</th>
            <th style="padding:7px 10px;text-align:right;font-weight:500;">Count</th>
          </tr>
        </thead>
        <tbody>${summaryRows}</tbody>
      </table>`;

    _placeAreaPolesOnMap(Array.isArray(data.poles) ? data.poles : []);

  } catch (err) {
    if (err.name === 'AbortError') return;
    console.error('[countAreaElements]', err);
    if (body) body.innerHTML = '<div style="color:#c62828;padding:8px;">' +
      '<i class="fa fa-exclamation-triangle"></i> ' + err.message + '</div>';
  }
}

function _placeAreaPolesOnMap(poles) {
    if (typeof clearElements === 'function') clearElements();

    setTimeout(() => {
        const badge = document.getElementById('_area-map-badge');

        if (!Array.isArray(poles) || !poles.length) {
            if (badge) {
                badge.style.background = '#fff3e0';
                badge.style.color      = '#e65100';
                badge.innerHTML = '<i class="fa fa-info-circle"></i> No GPS data to display';
            }
            return;
        }

        let placed = 0;
        poles.forEach(p => {
            const lat = parseFloat(p.lat ?? p.gps_lat);
            const lng = parseFloat(p.lng ?? p.gps_long);
            if (isNaN(lat) || isNaN(lng) || (lat === 0 && lng === 0)) return;

            // Normalise icon — handles 'ppole.png', 'primary', 'Primary pole' etc.
            const iconVal = _normaliseIcon(p.icon || p.type || 'primary');

            const marker = new google.maps.Marker({
                position : { lat, lng },
                map,
                icon     : _makePoleMarkerIcon(iconVal),
                title    : (p.type || iconVal) + (p.feeder ? ' — ' + p.feeder : ''),
                zIndex   : 5,
            });
            marker.addListener('click', () => showPoleInfo(p.trn, marker));

            // Push to markere so clearElements() cleans them up
            if (typeof markere !== 'undefined') markere.push(marker);

            placed++;
        });

        if (badge) {
            badge.style.background = placed ? '#e6f7ee' : '#fff3e0';
            badge.style.color      = placed ? '#00a84a' : '#e65100';
            badge.innerHTML = placed
                ? '<i class="fa fa-map-marker"></i> ' + placed.toLocaleString() + ' markers displayed'
                : '<i class="fa fa-info-circle"></i> No GPS data to display';
        }
    }, 50);
}


/* FEEDER BOUNDARY */
let feederBoundaryLine = null;
let feederBoundaryOn   = false;

function computeConvexHull(points) {
  if (points.length < 3) return points;
  const pts = points.slice().sort((a, b) => a.lng !== b.lng ? a.lng - b.lng : a.lat - b.lat);
  const cross = (O, A, B) =>
    (A.lng - O.lng) * (B.lat - O.lat) - (A.lat - O.lat) * (B.lng - O.lng);
  const lower = [];
  for (const p of pts) {
    while (lower.length >= 2 && cross(lower[lower.length-2], lower[lower.length-1], p) <= 0) lower.pop();
    lower.push(p);
  }
  const upper = [];
  for (let i = pts.length - 1; i >= 0; i--) {
    const p = pts[i];
    while (upper.length >= 2 && cross(upper[upper.length-2], upper[upper.length-1], p) <= 0) upper.pop();
    upper.push(p);
  }
  lower.pop(); upper.pop();
  return lower.concat(upper);
}

function drawFeederBoundary(coords) {
  if (!coords || coords.length === 0) return;
  if (feederBoundaryLine) { feederBoundaryLine.setMap(null); feederBoundaryLine = null; }
  const hull = computeConvexHull(coords);
  const path = [...hull, hull[0]].map(p => ({ lat: p.lat, lng: p.lng }));
  feederBoundaryLine = new google.maps.Polygon({
    paths: path, map,
    strokeColor: '#FF6600', strokeOpacity: 0.9, strokeWeight: 2.5,
    fillColor: '#FF6600', fillOpacity: 0.08, zIndex: 2,
  });
  feederBoundaryOn = true;
  el('btn-feeder-boundary')?.classList.add('is-active');
}

async function showFilteredElements() {
  const feeder   = el('vfeeder').value.trim();
  const elemType = el('elem_type').value;
  show(el('dvpanel'));
  requestAnimationFrame(positionDvPanel);
  el('dvpanel-body').innerHTML = '<div style="padding:12px;color:#555;"><i class="fa fa-spinner fa-spin"></i> Loading elements…</div>';

  try {
    const fd = new FormData();
    if (feeder)   fd.append('vfeeder',     feeder);
    if (elemType) fd.append('filter_type', elemType);
    const mapRes = await fetch('view_elements.php', { method: 'POST', body: fd });
    const html   = await mapRes.text();
    const tmp = document.createElement('div');
    tmp.innerHTML = html;
    tmp.querySelectorAll('script').forEach(s => {
      const sc = document.createElement('script');
      sc.textContent = s.textContent;
      document.body.appendChild(sc);
      document.body.removeChild(sc);
    });

    const fd2 = new FormData();
    if (feeder)   fd2.append('vfeeder',     feeder);
    if (elemType) fd2.append('filter_type', elemType);
    const countRes  = await fetch('count_feeder_elements.php', { method: 'POST', body: fd2 });
    const countData = await countRes.json();
    if (countData.error) throw new Error(countData.error);

    const feeders = countData.feeders || [];
    if (feeders.length === 0) {
      el('dvpanel-body').innerHTML = `
        <div style="padding:10px 12px 4px;font-size:12px;color:#555;">
          ${feeder ? `Feeder <strong>${feeder}</strong>` : 'All Feeders'} — no elements found.
        </div>`;
      return;
    }

    const grand = feeders.reduce((acc, r) => {
      acc.primary     += r.primary     || 0;
      acc.secondary   += r.secondary   || 0;
      acc.underbuilt  += r.underbuilt  || 0;
      acc.transformer += r.transformer || 0;
      acc.total       += r.total       || 0;
      return acc;
    }, { primary: 0, secondary: 0, underbuilt: 0, transformer: 0, total: 0 });

    if (feeder) {
      const f = feeders[0];
      const typeRows = [
        { label: 'Primary Poles',    val: f.primary     },
        { label: 'Secondary Poles',  val: f.secondary   },
        { label: 'Underbuilt Poles', val: f.underbuilt  },
        { label: 'Transformers',     val: f.transformer },
      ].filter(r => r.val > 0);
      const rows = typeRows.map(r => `
        <tr style="border-bottom:1px solid rgba(0,0,0,.05);">
          <td style="padding:6px 10px;text-transform:capitalize;">${r.label}</td>
          <td style="padding:6px 10px;text-align:right;font-weight:500;">${r.val}</td>
        </tr>`).join('');
      el('dvpanel-body').innerHTML = `
        <div style="padding:10px 12px 4px;font-size:12px;color:#555;">
          <strong>${feeder}</strong> — element counts
          <div style="margin-top:4px;padding:4px 8px;background:#e6f7ee;color:#00a84a;
                      border-radius:6px;font-size:11px;display:inline-block;">
            <i class="fa fa-map-marker"></i> Displayed on map
          </div>
        </div>
        <table style="width:100%;border-collapse:collapse;font-size:13px;">
          <thead>
            <tr style="background:rgba(0,0,0,.04);border-bottom:1px solid rgba(0,0,0,.08);">
              <th style="padding:6px 10px;text-align:left;font-weight:500;">Type</th>
              <th style="padding:6px 10px;text-align:right;font-weight:500;">Count</th>
            </tr>
          </thead>
          <tbody>${rows || '<tr><td colspan="2" style="padding:8px 10px;color:#999;">No elements found.</td></tr>'}</tbody>
          <tfoot>
            <tr style="border-top:1px solid rgba(0,0,0,.10);">
              <td style="padding:6px 10px;font-weight:500;">Total</td>
              <td style="padding:6px 10px;text-align:right;font-weight:600;">${f.total}</td>
            </tr>
          </tfoot>
        </table>`;
    } else {
      const rows = feeders.map(r => `
        <tr style="border-bottom:1px solid rgba(0,0,0,.05);">
          <td style="padding:6px 10px;font-weight:500;">${r.feeder || '—'}</td>
          <td style="padding:6px 10px;text-align:right;">${r.primary     || 0}</td>
          <td style="padding:6px 10px;text-align:right;">${r.secondary   || 0}</td>
          <td style="padding:6px 10px;text-align:right;">${r.underbuilt  || 0}</td>
          <td style="padding:6px 10px;text-align:right;">${r.transformer || 0}</td>
          <td style="padding:6px 10px;text-align:right;font-weight:600;">${r.total || 0}</td>
        </tr>`).join('');
      el('dvpanel-body').innerHTML = `
        <div style="padding:10px 12px 4px;font-size:12px;color:#555;">
          All Feeders — element counts
          <div style="margin-top:4px;padding:4px 8px;background:#e6f7ee;color:#00a84a;
                      border-radius:6px;font-size:11px;display:inline-block;">
            <i class="fa fa-map-marker"></i> Displayed on map
          </div>
        </div>
        <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:12px;">
          <thead>
            <tr style="background:rgba(0,0,0,.04);border-bottom:1px solid rgba(0,0,0,.08);">
              <th style="padding:6px 10px;text-align:left;font-weight:500;">Feeder</th>
              <th style="padding:6px 10px;text-align:right;font-weight:500;" title="Primary">Pri</th>
              <th style="padding:6px 10px;text-align:right;font-weight:500;" title="Secondary">Sec</th>
              <th style="padding:6px 10px;text-align:right;font-weight:500;" title="Underbuilt">UB</th>
              <th style="padding:6px 10px;text-align:right;font-weight:500;" title="Transformer">Xfmr</th>
              <th style="padding:6px 10px;text-align:right;font-weight:500;">Total</th>
            </tr>
          </thead>
          <tbody>${rows || '<tr><td colspan="6" style="padding:8px 10px;color:#999;">No elements found.</td></tr>'}</tbody>
          <tfoot>
            <tr style="border-top:2px solid rgba(0,0,0,.12);background:rgba(0,0,0,.02);">
              <td style="padding:6px 10px;font-weight:600;">Total</td>
              <td style="padding:6px 10px;text-align:right;font-weight:600;">${grand.primary}</td>
              <td style="padding:6px 10px;text-align:right;font-weight:600;">${grand.secondary}</td>
              <td style="padding:6px 10px;text-align:right;font-weight:600;">${grand.underbuilt}</td>
              <td style="padding:6px 10px;text-align:right;font-weight:600;">${grand.transformer}</td>
              <td style="padding:6px 10px;text-align:right;font-weight:600;">${grand.total}</td>
            </tr>
          </tfoot>
        </table>
        </div>`;
    }
  } catch (err) {
    console.error('[showFilteredElements]', err);
    el('dvpanel-body').innerHTML =
      '<div style="color:#c62828;padding:8px;"><i class="fa fa-exclamation-triangle"></i> Failed: ' + err.message + '</div>';
  }
}

function showFeederElements() {
  el('vfeeder').value = el('vfeeder').value.trim();
  el('elem_type').value = '';
  showFilteredElements();
}
function showFeederWithCounts() { showFeederElements(); }

/* TOWN / BRGY CUSTOMERS */
async function loadTownCust() {
  const town = el('vtown')?.value?.trim() || '';
  if (!town) { alert('Please select a Town first.'); return; }
  const body = el('dvpanel-body');
  if (body) body.innerHTML = '<div style="padding:12px;color:#555;"><i class="fa fa-spinner fa-spin"></i> Loading boundary…</div>';
  show(el('dvpanel'));
  requestAnimationFrame(positionDvPanel);
  try {
    const rings = await tbEnsureTownPolygons(town);
    if (!rings || rings.length === 0) {
      body.innerHTML = '<div style="color:#c62828;padding:8px;"><i class="fa fa-exclamation-triangle"></i> No boundary data found for <b>' + town + '</b>.</div>';
      return;
    }
    setHidden('vpolygon', JSON.stringify(rings));
    setHidden('vtown',    town);
    setHidden('vbrgy',    '');
    PageLink('search.php', 'dvpanel-body', 'fsearch', '&vtag=BG');
  } catch (err) {
    console.error('[loadTownCust]', err);
    body.innerHTML = '<div style="color:#c62828;padding:8px;"><i class="fa fa-exclamation-triangle"></i> Failed to load boundary data. Check console.</div>';
  }
}

async function loadBrgyCust() {
  const town = el('vtown')?.value?.trim() || '';
  const brgy = el('vbrgy')?.value?.trim() || '';
  if (!town) { alert('Please select a Town first.');     return; }
  if (!brgy) { alert('Please select a Barangay first.'); return; }
  const body = el('dvpanel-body');
  if (body) body.innerHTML = '<div style="padding:12px;color:#555;"><i class="fa fa-spinner fa-spin"></i> Loading boundary…</div>';
  show(el('dvpanel'));
  requestAnimationFrame(positionDvPanel);
  try {
    const ring = await tbEnsureBrgyPolygon(town, brgy);
    if (!ring || ring.length === 0) {
      body.innerHTML = '<div style="color:#c62828;padding:8px;"><i class="fa fa-exclamation-triangle"></i> No boundary data found for <b>' + brgy + '</b>.</div>';
      return;
    }
    setHidden('vpolygon', JSON.stringify([ring]));
    setHidden('vtown',    town);
    setHidden('vbrgy',    brgy);
    PageLink('search.php', 'dvpanel-body', 'fsearch', '&vtag=BG');
  } catch (err) {
    console.error('[loadBrgyCust]', err);
    body.innerHTML = '<div style="color:#c62828;padding:8px;"><i class="fa fa-exclamation-triangle"></i> Failed to load boundary data. Check console.</div>';
  }
}

async function downloadTownCust() {
  if (!CAN_DL_TOWN) {
    showmsgbox('Access Denied', 'You do not have permission to download town customer data.');
    return;
  }
  const town = el('vtown')?.value?.trim() || '';
  if (!town) { alert('Please select a Town first.'); return; }
  const body = el('dvpanel-body');
  try {
    const rings = await tbEnsureTownPolygons(town);
    if (!rings || rings.length === 0) { alert('No boundary data found for ' + town + '.'); return; }
    if (body) body.innerHTML = '<div style="padding:12px;color:#555;"><i class="fa fa-spinner fa-spin"></i> Preparing download…</div>';
    show(el('dvpanel'));
    requestAnimationFrame(positionDvPanel);
    const formData = new FormData();
    formData.append('vtown',    town);
    formData.append('vbrgy',    '');
    formData.append('vpolygon', JSON.stringify(rings));
    const response = await fetch('download_cust.php', { method: 'POST', body: formData });
    if (!response.ok) throw new Error('Server returned HTTP ' + response.status);
    const contentType = response.headers.get('Content-Type') || '';
    if (!contentType.includes('text/csv') && !contentType.includes('application/octet-stream')) {
      const errText = await response.text();
      console.error('[downloadTownCust] Server error:', errText);
      if (body) body.innerHTML = '<div style="color:#c62828;padding:8px;"><i class="fa fa-exclamation-triangle"></i> Server error — check browser console for details.</div>';
      return;
    }
    const blob = await response.blob();
    const url  = URL.createObjectURL(blob);
    const a    = document.createElement('a');
    a.href = url; a.download = 'customers_' + town + '.csv';
    document.body.appendChild(a); a.click(); document.body.removeChild(a);
    setTimeout(() => URL.revokeObjectURL(url), 5000);
    if (body) body.innerHTML = '<div style="padding:8px;color:#2e7d32;"><i class="fa fa-check-circle"></i> Download started: customers_' + town + '.csv</div>';
  } catch (err) {
    console.error('[downloadTownCust]', err);
    if (body) body.innerHTML = '<div style="color:#c62828;padding:8px;"><i class="fa fa-exclamation-triangle"></i> Download failed: ' + err.message + '</div>';
  }
}

async function downloadBrgyCust() {
  if (!CAN_DL_BRGY) {
    showmsgbox('Access Denied', 'You do not have permission to download barangay customer data.');
    return;
  }
  const town = el('vtown')?.value?.trim() || '';
  const brgy = el('vbrgy')?.value?.trim() || '';
  if (!town) { alert('Please select a Town first.');      return; }
  if (!brgy) { alert('Please select a Barangay first.'); return; }
  const body = el('dvpanel-body');
  try {
    const ring = await tbEnsureBrgyPolygon(town, brgy);
    if (!ring || ring.length === 0) { alert('No boundary data found for ' + brgy + '.'); return; }
    if (body) body.innerHTML = '<div style="padding:12px;color:#555;"><i class="fa fa-spinner fa-spin"></i> Preparing download…</div>';
    show(el('dvpanel'));
    requestAnimationFrame(positionDvPanel);
    const formData = new FormData();
    formData.append('vtown',    town);
    formData.append('vbrgy',    brgy);
    formData.append('vpolygon', JSON.stringify([ring]));
    const response = await fetch('download_cust.php', { method: 'POST', body: formData });
    if (!response.ok) throw new Error('Server returned HTTP ' + response.status);
    const contentType = response.headers.get('Content-Type') || '';
    if (!contentType.includes('text/csv') && !contentType.includes('application/octet-stream')) {
      const errText = await response.text();
      console.error('[downloadBrgyCust] Server error response:', errText);
      if (body) body.innerHTML = '<div style="color:#c62828;padding:8px;"><i class="fa fa-exclamation-triangle"></i> Server error — check browser console for details.</div>';
      return;
    }
    const blob = await response.blob();
    const url  = URL.createObjectURL(blob);
    const a    = document.createElement('a');
    a.href = url; a.download = 'customers_' + town + '_' + brgy + '.csv';
    document.body.appendChild(a); a.click(); document.body.removeChild(a);
    setTimeout(() => URL.revokeObjectURL(url), 5000);
    if (body) body.innerHTML = '<div style="padding:8px;color:#2e7d32;"><i class="fa fa-check-circle"></i> Download started: customers_' + town + '_' + brgy + '.csv</div>';
  } catch (err) {
    console.error('[downloadBrgyCust]', err);
    if (body) body.innerHTML = '<div style="color:#c62828;padding:8px;"><i class="fa fa-exclamation-triangle"></i> Download failed: ' + err.message + '</div>';
  }
}

/* VEHICLE TRACKING */
let vehicleInterval = null;
let vehicleMarkers  = [];
let vehicleInfoWin  = null;

const CAR_IMG = new Image();
CAR_IMG.src   = 'iconsx/car.png';

const AVATAR_POOL = [
  { bg:'#dbeafe', fg:'#1e40af' }, { bg:'#dcfce7', fg:'#14532d' },
  { bg:'#fef3c7', fg:'#92400e' }, { bg:'#fce7f3', fg:'#9d174d' },
  { bg:'#ede9fe', fg:'#4c1d95' }, { bg:'#ffedd5', fg:'#9a3412' },
  { bg:'#cffafe', fg:'#155e75' },
];

function crewInitials(name) {
  const p = name.trim().split(/\s+/);
  return (p.length >= 2 ? p[0][0] + p[p.length-1][0] : name.slice(0,2)).toUpperCase();
}

function buildCarInfoHTML(v) {
  const plate = v.plate||'—', area = v.area||'—', time = v.time||'—';
  const crew  = Array.isArray(v.crew) ? v.crew : [];
  const crewHTML = crew.length
    ? crew.map((name,i) => {
        const {bg,fg} = AVATAR_POOL[i % AVATAR_POOL.length];
        return `<div style="display:flex;align-items:center;gap:8px;padding:6px 0;border-bottom:1px solid rgba(0,122,52,0.08);">
          <div style="width:26px;height:26px;border-radius:50%;flex-shrink:0;background:${bg};color:${fg};font-size:9.5px;font-weight:700;display:flex;align-items:center;justify-content:center;">${crewInitials(name)}</div>
          <span style="font-size:12px;color:#0d1f14;line-height:1.3;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:150px;">${name}</span>
        </div>`;}).join('')
    : `<p style="font-size:11.5px;color:#7a9e8a;padding:6px 0;">No crew assigned</p>`;
  return `<div style="font-family:'DM Sans',ui-sans-serif,sans-serif;width:232px;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,25,10,0.18);border:1px solid rgba(0,122,52,0.13);">
    <div style="background:#012b13;padding:11px 14px 10px;display:flex;align-items:center;justify-content:space-between;">
      <div><div style="font-size:8.5px;font-weight:700;letter-spacing:0.11em;color:rgba(255,255,255,0.38);margin-bottom:3px;">VEHICLE NO.</div>
      <div style="font-family:'Space Mono',monospace;font-size:16px;font-weight:700;color:#fff;letter-spacing:0.14em;">${plate}</div></div>
      <div style="background:rgba(0,168,74,0.18);border:1px solid rgba(0,168,74,0.35);border-radius:50px;padding:3px 10px;text-align:center;">
        <div style="font-size:16px;font-weight:700;color:#4ade80;line-height:1;">${crew.length}</div>
        <div style="font-size:8px;color:rgba(74,222,128,0.7);letter-spacing:0.06em;">CREW</div>
      </div>
    </div>
    <div style="background:#f0f7f3;padding:6px 14px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid rgba(0,122,52,0.10);">
      <div style="display:flex;align-items:center;gap:5px;"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#007a34" stroke-width="2.5" stroke-linecap="round"><path d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg><span style="font-size:11px;font-weight:600;color:#005a27;">${area}</span></div>
      <div style="display:flex;align-items:center;gap:5px;"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#7a9e8a" stroke-width="2.5" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg><span style="font-size:10.5px;color:#7a9e8a;">${time}</span></div>
    </div>
    <div style="background:#fff;padding:10px 14px 12px;">
      <div style="font-size:8.5px;font-weight:700;letter-spacing:0.10em;color:#7a9e8a;margin-bottom:4px;">ASSIGNED CREW</div>
      <div style="max-height:150px;overflow-y:auto;">${crewHTML}</div>
    </div>
  </div>`;
}

function buildCarMarker(v) {
  const label = (v.plate||'VEH').slice(0,12);
  const scale = window.devicePixelRatio||1, W = 90, H = 72;
  const canvas = document.createElement('canvas');
  canvas.width = W*scale; canvas.height = H*scale;
  const ctx = canvas.getContext('2d'); ctx.scale(scale, scale);
  const fs = 10; ctx.font = `700 ${fs}px 'Space Mono',monospace`;
  const tw = ctx.measureText(label).width, pH = 16, pW = Math.max(tw+14, 44);
  const pX = (W-pW)/2, pY = 1, pR = 8;
  ctx.shadowColor = 'rgba(0,0,0,0.22)'; ctx.shadowBlur = 5; ctx.shadowOffsetY = 2;
  ctx.beginPath(); ctx.roundRect(pX, pY, pW, pH, pR); ctx.fillStyle = '#012b13'; ctx.fill();
  ctx.shadowBlur = 0;
  ctx.beginPath(); ctx.roundRect(pX, pY, 4, pH, [pR,0,0,pR]); ctx.fillStyle = '#00a84a'; ctx.fill();
  ctx.font = `700 ${fs}px 'Space Mono',monospace`; ctx.fillStyle = '#ffffff';
  ctx.textAlign = 'center'; ctx.textBaseline = 'middle'; ctx.fillText(label, W/2, pY+pH/2);
  const iW = 38, iH = 38, iX = (W-iW)/2, iY = pY+pH+3;
  ctx.shadowColor = 'rgba(0,0,0,0.18)'; ctx.shadowBlur = 6; ctx.shadowOffsetY = 2;
  const drawCar = () => ctx.drawImage(CAR_IMG, iX, iY, iW, iH);
  if (CAR_IMG.complete && CAR_IMG.naturalWidth) { drawCar(); } else { CAR_IMG.onload = drawCar; }
  return { url: canvas.toDataURL(), scaledSize: new google.maps.Size(W, H), anchor: new google.maps.Point(W/2, H) };
}

function viewCars(data) {
  if (!Array.isArray(data) || !data.length) return;  
  if (!vehicleInfoWin) vehicleInfoWin = new google.maps.InfoWindow({ disableAutoPan: true, maxWidth: 250 });
  vehicleMarkers.forEach(m => m.setMap(null)); vehicleMarkers = [];
  data.forEach(v => {    
    const lat = parseFloat(v.lat), lng = parseFloat(v.lng);
    if (isNaN(lat) || isNaN(lng) || (lat === 0 && lng === 0)) return;
    const marker = new google.maps.Marker({ position: { lat, lng }, map, icon: buildCarMarker(v), title: v.plate||'Vehicle', zIndex: 10 });
    const openInfo = () => { vehicleInfoWin.setContent(buildCarInfoHTML(v)); vehicleInfoWin.open(map, marker); };
    marker.addListener('mouseover', openInfo);
    marker.addListener('click', openInfo);
    marker.addListener('mouseout', () => setTimeout(() => vehicleInfoWin.close(), 7000));
    vehicleMarkers.push(marker);
  });
}

function loadVehicles() {
  fetch('view_car.php')
    .then(r => { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
    .then(data => { if (data?.error) { console.error('[Vehicle]', data.error); return; } viewCars(data); })
    .catch(err => console.error('[Vehicle] Fetch:', err));
}

el('showVehicle').addEventListener('click', function() {
  loadVehicles();
  if (!vehicleInterval) vehicleInterval = setInterval(loadVehicles, 10000);
  this.classList.add('is-active'); el('stopVehicle').classList.remove('is-active');
});
el('stopVehicle').addEventListener('click', function() {
  clearInterval(vehicleInterval); vehicleInterval = null;
  vehicleMarkers.forEach(m => m.setMap(null)); vehicleMarkers = [];
  vehicleInfoWin?.close();
  this.classList.remove('is-active'); el('showVehicle').classList.remove('is-active');
});


</script>

</body>
</html>
