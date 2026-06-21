<?php 
include '../js/fphp.php';
include '../conn.php';

$user_level   = $_SESSION['user_level'] ?? 'guest';
$user_id      = (int)($_SESSION['user_id'] ?? 0);

$rStmt = sqlsrv_query($conn,
    "SELECT * FROM tbl_userrights WHERE level = ?", [$user_level]);
$rights = ($rStmt && $rRow = sqlsrv_fetch_array($rStmt, SQLSRV_FETCH_ASSOC))
    ? $rRow : [];

$can_edit_cnr = !empty($rights['edit_complaints_request']);
$is_crew      = !empty($rights['is_crew']);
?>
<!DOCTYPE html>
<html>
<head>
<title>Complaints Dashboard</title>
<link rel="stylesheet" href="../css/g.css">
<link rel="stylesheet" href="../css/font-awesome-4.7.0/css/font-awesome.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
  /* ── Page-level overrides ── */
  body { background: #f0f2f0; font-family: 'Inter', sans-serif; }

  /* ── Page Header ── */
  .cmp-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    padding: 20px 24px 16px;
    background: #fff;
    border-bottom: 1px solid #e4e9e4;
  }
  .cmp-header h2 { font-size: 16px; font-weight: 600; color: #111; margin: 0 0 2px; }
  .cmp-header p  { font-size: 11.5px; color: #aaa; margin: 0; }

  /* ── Body padding ── */
  .cmp-body { padding: 20px 24px; }

  /* ── Area notice (crew-only banner) ── */
  .area-notice {
    display: flex; align-items: center; gap: 8px;
    background: #e8f5ee; border: 1px solid #c8e8d4;
    border-radius: 8px; padding: 10px 14px;
    font-size: 12px; color: #1e6b3c; font-weight: 500;
    margin-bottom: 16px;
  }
  .area-notice svg { flex-shrink: 0; opacity: 0.8; }

  /* ── Stats Bar ── */
  .cmp-stats {
    display: flex; gap: 14px; margin-bottom: 20px; flex-wrap: wrap;
  }
  .cmp-stat-card {
    flex: 1; min-width: 120px;
    background: #fff; border: 1px solid #e4e9e4;
    border-radius: 10px; padding: 14px 18px;
    display: flex; flex-direction: column; gap: 5px;
  }
  .stat-label {
    font-size: 10px; font-weight: 600;
    letter-spacing: 0.08em; text-transform: uppercase; color: #aaa;
  }
  .stat-value {
    font-size: 26px; font-weight: 600; color: #111; letter-spacing: -1px;
  }
  .stat-pending  .stat-value { color: #b07b00; }
  .stat-accepted .stat-value { color: #1e6b3c; }
  .stat-done     .stat-value { color: #9b2335; }

  /* ── Filter / toolbar bar ── */
  .cmp-filter-bar {
    display: flex; align-items: flex-end;
    gap: 10px; flex-wrap: wrap; margin-bottom: 16px;
  }
  .cmp-filter-group { display: flex; flex-direction: column; gap: 4px; }
  .cmp-filter-group label {
    font-size: 10px; font-weight: 600;
    letter-spacing: 0.08em; text-transform: uppercase; color: #888;
  }

  /* ── Status badges ── */
  .status-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 3px 9px; border-radius: 20px;
    font-size: 10px; font-weight: 600;
    letter-spacing: 0.05em; text-transform: uppercase;
  }
  .status-badge::before {
    content: ''; width: 5px; height: 5px;
    border-radius: 50%; flex-shrink: 0;
  }
  .status-pending  { background: #fef9ec; color: #8a6300; border: 1px solid #f5e4b0; }
  .status-pending::before  { background: #c48f00; }
  .status-accepted { background: #e8f5ee; color: #1e5c35; border: 1px solid #c8e8d4; }
  .status-accepted::before { background: #1e6b3c; }
  .status-done     { background: #fdf0f1; color: #8b1f2e; border: 1px solid #f2c0c6; }
  .status-done::before     { background: #b02535; }

  /* ── Action buttons in table ── */
  .action-group { display: flex; gap: 5px; align-items: center; flex-wrap: wrap; }
  .action-btn {
    height: 28px; padding: 0 10px;
    border: 1px solid transparent; border-radius: 6px;
    font-family: 'Inter', sans-serif; font-size: 11px; font-weight: 500;
    cursor: pointer; transition: all .13s;
    display: inline-flex; align-items: center; gap: 4px; white-space: nowrap;
  }
  .accept-btn { background: #e8f5ee; color: #1e5c35; border-color: #c8e8d4; }
  .accept-btn:hover { background: #1e6b3c; color: #fff; border-color: #1e6b3c; }
  .done-btn   { background: #fdf0f1; color: #8b1f2e; border-color: #f2c0c6; }
  .done-btn:hover   { background: #b02535; color: #fff; border-color: #b02535; }
  .detail-btn { background: #f5f5f5; color: #555; border-color: #e4e9e4; }
  .detail-btn:hover { background: #1e6b3c; color: #fff; border-color: #1e6b3c; }
  .edit-btn   { background: #e8f4fd; color: #1565a8; border-color: #aad4f5; }
  .edit-btn:hover   { background: #1565a8; color: #fff; border-color: #1565a8; }
  .action-btn:disabled { opacity: 0.35; cursor: not-allowed; pointer-events: none; }

  /* crew chips */
  .crew-chip-sm {
    display: inline-flex; align-items: center;
    background: #e8f5ee; border: 1px solid #c8e8d4;
    border-radius: 20px; padding: 2px 10px;
    font-size: 11px; color: #1e6b3c; font-weight: 500;
  }
  .unassigned-sm { font-size: 11px; color: #bbb; font-style: italic; }

  /* ── Empty state ── */
  .rpt-empty { text-align: center; padding: 60px 20px; color: #bbb; }
  .rpt-empty svg { margin-bottom: 10px; opacity: 0.4; }
  .rpt-empty p   { font-size: 13px; font-weight: 500; color: #999; margin-bottom: 4px; }
  .rpt-empty span { font-size: 12px; color: #bbb; }

  /* ── Modal base ── */
  .cmp-modal-overlay {
    display: none; position: fixed; z-index: 1000; inset: 0;
    background: rgba(0,0,0,0.4);
    align-items: center; justify-content: center; padding: 20px;
  }
  .cmp-modal-overlay.active { display: flex; }

  .cmp-modal {
    background: #fff; border-radius: 12px;
    width: 100%; max-width: 760px; max-height: 88vh;
    overflow: hidden; display: flex; flex-direction: column;
    box-shadow: 0 8px 32px rgba(0,0,0,0.14);
  }
  .cmp-modal.modal-sm { max-width: 480px; }

  .cmp-modal-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 16px 20px; border-bottom: 1px solid #e4e9e4; background: #fafcfa; flex-shrink: 0;
  }
  .cmp-modal-header h3 { font-size: 14px; font-weight: 600; color: #111; margin: 0; }
  .cmp-modal-close {
    width: 28px; height: 28px; border: 1px solid #e4e9e4; border-radius: 6px;
    background: #fff; cursor: pointer; display: flex; align-items: center;
    justify-content: center; color: #888; font-size: 16px; line-height: 1;
    transition: all .13s;
  }
  .cmp-modal-close:hover { background: #1e6b3c; color: #fff; border-color: #1e6b3c; }

  .cmp-modal-body  { overflow-y: auto; padding: 20px; flex: 1; }
  .cmp-modal-footer {
    padding: 12px 20px; border-top: 1px solid #e4e9e4;
    background: #fafcfa; display: flex; gap: 8px;
    justify-content: flex-end; flex-shrink: 0;
  }

  /* Detail view inside modal */
  .detail-grid {
    display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 16px;
  }
  .detail-field {
    background: #fafcfa; border: 1px solid #e4e9e4; border-radius: 8px; padding: 10px 12px;
  }
  .detail-field.full { grid-column: 1 / -1; }
  .detail-field .df-label {
    font-size: 9px; font-weight: 600; letter-spacing: 0.1em;
    text-transform: uppercase; color: #aaa; margin-bottom: 4px;
  }
  .detail-field .df-value { font-size: 13px; color: #111; font-weight: 500; word-break: break-word; white-space: normal; }

  .detail-section-title {
    font-size: 10px; font-weight: 600; letter-spacing: 0.09em;
    text-transform: uppercase; color: #aaa;
    margin-bottom: 10px; padding-bottom: 6px; border-bottom: 1px solid #e4e9e4;
  }
  .detail-section { margin-bottom: 18px; }

  .crew-chip {
    display: inline-flex; align-items: center; gap: 6px;
    background: #e8f5ee; border: 1px solid #c8e8d4;
    border-radius: 20px; padding: 3px 12px;
    font-size: 12px; color: #1e6b3c; font-weight: 500;
  }
  .unassigned-chip {
    display: inline-flex; align-items: center; gap: 6px;
    background: #f5f5f5; border: 1px solid #e0e0e0;
    border-radius: 20px; padding: 3px 12px;
    font-size: 12px; color: #999; font-style: italic;
  }

  /* Edit form fields */
  .form-group { display: flex; flex-direction: column; gap: 4px; margin-bottom: 12px; }
  .form-group label {
    font-size: 10px; font-weight: 600; letter-spacing: 0.08em;
    text-transform: uppercase; color: #aaa;
  }
  .form-input, .form-textarea, .form-select-full {
    border: 1px solid #e4e9e4; border-radius: 7px;
    font-family: 'Inter', sans-serif; font-size: 12.5px; color: #111;
    padding: 8px 12px; outline: none;
    transition: border-color .13s, box-shadow .13s; background: #fff;
  }
  .form-textarea { resize: vertical; min-height: 80px; }
  .form-select-full {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%23aaa' stroke-width='2' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
    background-repeat: no-repeat; background-position: right 10px center; padding-right: 30px;
  }
  .form-input:focus, .form-textarea:focus, .form-select-full:focus {
    border-color: #1e6b3c; box-shadow: 0 0 0 3px rgba(30,107,60,0.08);
  }

  /* Spinner */
  .cmp-loading {
    display: flex; flex-direction: column; align-items: center;
    justify-content: center; padding: 50px 20px; gap: 12px; color: #aaa; font-size: 13px;
  }
  .spinner {
    width: 28px; height: 28px; border: 3px solid #e4e9e4;
    border-top-color: #1e6b3c; border-radius: 50%; animation: spin .7s linear infinite;
  }
  @keyframes spin { to { transform: rotate(360deg); } }
</style>
</head>
<body>
<script>
  var CAN_EDIT_CNR = <?php echo $can_edit_cnr ? 'true' : 'false'; ?>;
  var IS_CREW      = <?php echo $is_crew      ? 'true' : 'false'; ?>;
  var SESSION_UID  = <?php echo $user_id; ?>;
</script>

<!-- ── Page Header ── -->
<div class="ur-header">
  <div class="ur-header-left">
    <h2>Complaints</h2>
    <p>Manage and track submitted complaints</p>
  </div>
</div>

<div class="ur-body">

  <?php if ($is_crew): ?>
  <div class="area-notice">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
    </svg>
    Showing complaints in your area only. You can accept and mark complaints as done.
  </div>
  <?php endif; ?>

  <!-- Stats -->
  <div class="cmp-stats">
    <div class="cmp-stat-card">
      <span class="stat-label">Total</span>
      <span class="stat-value" id="stat_total">—</span>
    </div>
    <div class="cmp-stat-card stat-pending">
      <span class="stat-label">Pending</span>
      <span class="stat-value" id="stat_pending">—</span>
    </div>
    <div class="cmp-stat-card stat-accepted">
      <span class="stat-label">Accepted</span>
      <span class="stat-value" id="stat_accepted">—</span>
    </div>
    <div class="cmp-stat-card stat-done">
      <span class="stat-label">Done</span>
      <span class="stat-value" id="stat_done">—</span>
    </div>
  </div>

  <!-- Filter Bar — uses g.css classes -->
  <div class="ur-add-bar" style="margin-bottom:16px;">
    <label>Status</label>
    <select id="status_filter" class="uf-select" style="width:160px;" onchange="loadComplaints()">
      <option value="">All Status</option>
      <option value="pending">Pending</option>
      <option value="accepted">Accepted</option>
      <option value="done">Done</option>
    </select>

    <button type="button" class="uf-btn-save" onclick="loadComplaints()">
      <i class="fa fa-refresh"></i> Refresh
    </button>

    <!-- Search — reuses g.css tbl-search-wrap -->
    <div class="tbl-search-wrap" style="margin-left:auto;">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
      </svg>
      <input type="text" class="tbl-search" placeholder="Search complaints..."
        oninput="tableSearch(this,'complaint_table',[1,2,3,4,5])">
    </div>
  </div>

  <!-- Table — uses g.css um-table-wrap + um-table -->
  <div class="um-table-wrap">
    <table class="um-table" id="complaint_table">
      <thead>
        <tr>
          <th style="width:44px;text-align:center;">#</th>
          <th>Date</th>
          <th>Customer</th>
          <th>Complaint</th>
          <th>Status</th>
          <th>Assigned To</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody id="complaint_tbody">
        <tr>
          <td colspan="7" class="rpt-empty">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2">
              <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
            </svg>
            <p>No complaints found</p>
            <span>Select a status or click Refresh</span>
          </td>
        </tr>
      </tbody>
    </table>
  </div>

</div>

<!-- ── Detail Modal ── -->
<div id="complaint_modal" class="cmp-modal-overlay" onclick="handleOverlayClick(event,'complaint_modal')">
  <div class="cmp-modal">
    <div class="cmp-modal-header">
      <h3>Complaint Details</h3>
      <button class="cmp-modal-close" onclick="closeModal('complaint_modal')">&#215;</button>
    </div>
    <div class="cmp-modal-body" id="complaint_modal_content">
      <div class="cmp-loading"><div class="spinner"></div>Loading…</div>
    </div>
  </div>
</div>

<!-- ── Edit Modal ── -->
<div id="edit_modal" class="cmp-modal-overlay" onclick="handleOverlayClick(event,'edit_modal')">
  <div class="cmp-modal">
    <div class="cmp-modal-header">
      <h3>Edit Complaint</h3>
      <button class="cmp-modal-close" onclick="closeModal('edit_modal')">&#215;</button>
    </div>
    <div class="cmp-modal-body">
      <input type="hidden" id="edit_trn">

      <div class="detail-grid">
        <div class="form-group">
          <label>Customer Name</label>
          <input type="text" id="edit_name" class="form-input" placeholder="Customer name">
        </div>
        <div class="form-group">
          <label>Account No</label>
          <input type="text" id="edit_accountno" class="form-input" placeholder="Account number">
        </div>
        <div class="form-group">
          <label>Area</label>
          <input type="text" id="edit_area" class="form-input" placeholder="Area">
        </div>
        <div class="form-group">
          <label>Address</label>
          <input type="text" id="edit_address" class="form-input" placeholder="Address">
        </div>
        <div class="form-group">
          <label>Contact No</label>
          <input type="text" id="edit_contactno" class="form-input" placeholder="Contact number">
        </div>
        <div class="form-group">
          <label>Date Filed</label>
          <input type="date" id="edit_req_dt" class="form-input">
        </div>
        <div class="form-group">
          <label>Request Type</label>
          <select id="edit_request" class="form-select-full">
            <option value="">-- Select Request Type --</option>
          </select>
        </div>
        <div class="form-group">
          <label>Assigned Crew</label>
          <select id="edit_crew" class="form-select-full">
            <option value="">-- Unassigned --</option>
          </select>
        </div>
        <div class="form-group full" style="grid-column:1/-1">
          <label>Crew Members</label>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
            <input type="text" id="edit_member1" class="form-input" placeholder="Member 1">
            <input type="text" id="edit_member2" class="form-input" placeholder="Member 2">
            <input type="text" id="edit_member3" class="form-input" placeholder="Member 3">
            <input type="text" id="edit_member4" class="form-input" placeholder="Member 4">
            <input type="text" id="edit_member5" class="form-input" placeholder="Member 5">
          </div>
        </div>
        <div class="form-group full" style="grid-column:1/-1">
          <label>Complaint Description</label>
          <textarea id="edit_req_desc" class="form-textarea" placeholder="Complaint details…"></textarea>
        </div>
        <div class="form-group full" style="grid-column:1/-1">
          <label>Findings</label>
          <textarea id="edit_findings" class="form-textarea" placeholder="Findings…"></textarea>
        </div>
        <div class="form-group full" style="grid-column:1/-1">
          <label>Action Taken</label>
          <textarea id="edit_action" class="form-textarea" placeholder="Action taken…"></textarea>
        </div>
      </div>
    </div>
    <div class="cmp-modal-footer">
      <button class="uf-btn-cancel" onclick="closeModal('edit_modal')">Cancel</button>
      <button class="uf-btn-save" onclick="saveEdit()">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
          <polyline points="17 21 17 13 7 13 7 21"/>
          <polyline points="7 3 7 8 15 8"/>
        </svg>
        Save Changes
      </button>
    </div>
  </div>
</div>

<script>
/* ── Helpers ── */
function closeModal(id) { document.getElementById(id).classList.remove('active'); }
function handleOverlayClick(e, id) { if (e.target === document.getElementById(id)) closeModal(id); }

document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') ['complaint_modal','edit_modal'].forEach(closeModal);
});

function tableSearch(input, tableId, cols) {
  var f = input.value.toLowerCase();
  document.querySelectorAll('#' + tableId + ' tbody tr').forEach(function(row) {
    var t = '';
    cols.forEach(function(i) { if (row.cells[i]) t += row.cells[i].textContent.toLowerCase() + ' '; });
    row.style.display = t.includes(f) ? '' : 'none';
  });
}

function updateStats(rows) {
  var total = 0, pending = 0, accepted = 0, done = 0;
  rows.forEach(function(row) {
    if (!row.cells || row.cells.length < 2) return;
    total++;
    var b = row.querySelector('.status-badge');
    if (!b) return;
    if      (b.classList.contains('status-pending'))  pending++;
    else if (b.classList.contains('status-accepted')) accepted++;
    else if (b.classList.contains('status-done'))     done++;
  });
  document.getElementById('stat_total').textContent    = total;
  document.getElementById('stat_pending').textContent  = pending;
  document.getElementById('stat_accepted').textContent = accepted;
  document.getElementById('stat_done').textContent     = done;
}

/* ── Load table ── */
function loadComplaints() {
  var tbody = document.getElementById('complaint_tbody');
  tbody.innerHTML = '<tr><td colspan="7" class="rpt-empty">'
    + '<div class="cmp-loading"><div class="spinner"></div>Loading…</div></td></tr>';

  fetch('utility/complaint_data.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'status_filter=' + encodeURIComponent(document.getElementById('status_filter').value)
      + '&can_edit=' + (CAN_EDIT_CNR ? '1' : '0')
      + '&is_crew='  + (IS_CREW      ? '1' : '0')
  })
  .then(r => r.text())
  .then(html => {
    tbody.innerHTML = html;
    updateStats(Array.from(tbody.querySelectorAll('tr')));
  })
  .catch(function() {
    tbody.innerHTML = '<tr><td colspan="7" class="rpt-empty">'
      + '<p>Failed to load data</p><span>Check your connection</span></td></tr>';
  });
}

/* ── POST helper ── */
function post(payload) {
  return fetch('utility/complaint_action.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: Object.entries(payload)
         .map(([k,v]) => encodeURIComponent(k) + '=' + encodeURIComponent(v))
         .join('&')
  }).then(r => r.json());
}

/* ── VIEW ── */
function loadComplaintDetails(trn) {
  var content = document.getElementById('complaint_modal_content');
  content.innerHTML = '<div class="cmp-loading"><div class="spinner"></div>Loading…</div>';
  document.getElementById('complaint_modal').classList.add('active');
  fetch('utility/complaint_action.php?action=view&trn=' + encodeURIComponent(trn))
    .then(r => r.text())
    .then(html => { content.innerHTML = html; })
    .catch(function() {
      content.innerHTML = '<div class="cmp-loading"><p style="color:#c0392b">Failed to load.</p></div>';
    });
}

/* ── ACCEPT ── */
function acceptComplaint(trn) {
  showmsgboxJSYN('Accept Complaint', 'Accept this complaint? It will be assigned to you.', function() {
    post({ action:'accept', trn: trn })
      .then(res => {
        if (res.ok) showmsgboxJS('Success', 'Complaint accepted.', function(){ loadComplaints(); });
        else showmsgbox('Error', res.msg);
      })
      .catch(() => showmsgbox('Error', 'Network error.'));
  });
}

/* ── DONE ── */
function doneComplaint(trn) {
  showmsgboxJSYN('Mark as Done', 'Mark this complaint as done?', function() {
    post({ action:'done', trn: trn })
      .then(res => {
        if (res.ok) showmsgboxJS('Success', 'Complaint marked as done.', function(){ loadComplaints(); });
        else showmsgbox('Error', res.msg);
      })
      .catch(() => showmsgbox('Error', 'Network error.'));
  });
}

/* ── Load request types ── */
function loadRequestTypes(selected) {
  fetch('utility/complaint_action.php?action=get_request_types')
    .then(r => r.json())
    .then(function(types) {
      var sel = document.getElementById('edit_request');
      sel.innerHTML = '<option value="">-- Select Request Type --</option>';
      types.forEach(function(t) {
        var opt = document.createElement('option');
        opt.value = t.request; opt.textContent = t.request;
        if (t.request === selected) opt.selected = true;
        sel.appendChild(opt);
      });
    });
}

/* ── EDIT modal open ── */
function openEditModal(trn) {
  document.getElementById('edit_trn').value = trn;
  document.getElementById('edit_modal').classList.add('active');

  /* Reset all fields + validation borders */
  ['edit_name','edit_accountno','edit_area','edit_address','edit_contactno',
   'edit_req_desc','edit_findings','edit_action','edit_crew',
   'edit_member1','edit_member2','edit_member3','edit_member4','edit_member5']
    .forEach(function(id) {
      var el = document.getElementById(id);
      el.value = '';
      el.style.borderColor = '';
    });
  document.getElementById('edit_req_dt').value = '';
  document.getElementById('edit_req_dt').style.borderColor = '';
  document.getElementById('edit_request').style.borderColor = '';

  fetch('utility/complaint_action.php?action=get_row&trn=' + encodeURIComponent(trn))
    .then(r => r.json())
    .then(function(d) {
      document.getElementById('edit_name').value      = d.name      || '';
      document.getElementById('edit_accountno').value = d.accountno || '';
      document.getElementById('edit_area').value      = d.area      || '';
      document.getElementById('edit_address').value   = d.address   || '';
      document.getElementById('edit_contactno').value = d.contactno || '';
      document.getElementById('edit_req_dt').value    = d.req_dt    || '';
      document.getElementById('edit_req_desc').value  = d.req_desc  || '';
      document.getElementById('edit_findings').value  = d.findings  || '';
      document.getElementById('edit_action').value    = d.action    || '';

      loadRequestTypes(d.request || '');
      loadCrewList(d.crew || '', function() {
        var hasMembers = d.member1 || d.member2 || d.member3 || d.member4 || d.member5;
        if (hasMembers) {
          document.getElementById('edit_member1').value = d.member1 || '';
          document.getElementById('edit_member2').value = d.member2 || '';
          document.getElementById('edit_member3').value = d.member3 || '';
          document.getElementById('edit_member4').value = d.member4 || '';
          document.getElementById('edit_member5').value = d.member5 || '';
        } else if (d.crew) {
          fetchCrewMembers(d.crew);
        }
      });
    })
    .catch(function() { showmsgbox('Error', 'Failed to load complaint data.'); });
}

/* ── EDIT save with validation ── */
function saveEdit() {
  var trn          = document.getElementById('edit_trn').value;
  var name         = document.getElementById('edit_name').value.trim();
  var accountno    = document.getElementById('edit_accountno').value.trim();
  var area         = document.getElementById('edit_area').value.trim();
  var address      = document.getElementById('edit_address').value.trim();
  var contactno    = document.getElementById('edit_contactno').value.trim();
  var req_dt       = document.getElementById('edit_req_dt').value.trim();
  var request      = document.getElementById('edit_request').value;
  var req_desc     = document.getElementById('edit_req_desc').value.trim();
  var findings     = document.getElementById('edit_findings').value.trim();
  var action_taken = document.getElementById('edit_action').value.trim();
  var crew         = document.getElementById('edit_crew').value.trim();

  /* Reset borders */
  ['edit_name','edit_accountno','edit_area','edit_address','edit_req_dt','edit_req_desc','edit_request']
    .forEach(function(id) { document.getElementById(id).style.borderColor = ''; });

  /* Validate */
  var errors = [];
  var required = {
    'edit_name':      [name,     'Customer Name is required.'],
    'edit_accountno': [accountno,'Account No is required.'],
    'edit_area':      [area,     'Area is required.'],
    'edit_address':   [address,  'Address is required.'],
    'edit_req_dt':    [req_dt,   'Date Filed is required.'],
    'edit_request':   [request,  'Request Type is required.'],
  };

  Object.entries(required).forEach(function([id, arr]) {
    if (!arr[0]) {
      errors.push(arr[1]);
      document.getElementById(id).style.borderColor = '#e74c3c';
    }
  });

  if (contactno && !/^[\d\s\+\-\(\)]+$/.test(contactno))
    errors.push('Contact No must contain numbers only.');

  if (req_dt) {
    var filed = new Date(req_dt), today = new Date();
    today.setHours(23,59,59,999);
    if (filed > today) errors.push('Date Filed cannot be in the future.');
  }

  if (errors.length > 0) {
    showmsgbox('Required Fields',
      '<ul style="margin:8px 0 0 16px;padding:0;text-align:left;">'
      + errors.map(function(e) {
          return '<li style="margin-bottom:4px;font-size:12px;">' + e + '</li>';
        }).join('') + '</ul>');
    return;
  }

  post({
    action: 'edit', trn, name, accountno, area, address, contactno,
    req_dt, request, req_desc, findings, action_taken, crew,
    member1: document.getElementById('edit_member1').value.trim(),
    member2: document.getElementById('edit_member2').value.trim(),
    member3: document.getElementById('edit_member3').value.trim(),
    member4: document.getElementById('edit_member4').value.trim(),
    member5: document.getElementById('edit_member5').value.trim()
  })
  .then(res => {
    if (res.ok) {
      closeModal('edit_modal');
      showmsgboxJS('Saved', 'Complaint updated successfully.', function(){ loadComplaints(); });
    } else {
      showmsgbox('Error', res.msg);
    }
  })
  .catch(() => showmsgbox('Error', 'Network error.'));
}

/* ── Crew list ── */
function loadCrewList(selected, callback) {
  fetch('utility/complaint_action.php?action=get_crew_list')
    .then(r => r.json())
    .then(function(list) {
      var sel = document.getElementById('edit_crew');
      sel.innerHTML = '<option value="">-- Unassigned --</option>';
      list.forEach(function(c) {
        var opt = document.createElement('option');
        opt.value = c.name; opt.textContent = c.name;
        if (c.name === selected) opt.selected = true;
        sel.appendChild(opt);
      });
      if (typeof callback === 'function') callback();
    })
    .catch(function() { showmsgbox('Error', 'Failed to load crew list.'); });
}

/* ── Fetch crew members ── */
function fetchCrewMembers(crewName) {
  if (!crewName) {
    ['edit_member1','edit_member2','edit_member3','edit_member4','edit_member5']
      .forEach(function(id) { document.getElementById(id).value = ''; });
    return;
  }
  fetch('utility/complaint_action.php?action=get_crew_members&crew=' + encodeURIComponent(crewName))
    .then(r => r.json())
    .then(function(m) {
      document.getElementById('edit_member1').value = m.member1 || '';
      document.getElementById('edit_member2').value = m.member2 || '';
      document.getElementById('edit_member3').value = m.member3 || '';
      document.getElementById('edit_member4').value = m.member4 || '';
      document.getElementById('edit_member5').value = m.member5 || '';
    })
    .catch(function() { showmsgbox('Error', 'Failed to load crew members.'); });
}

document.getElementById('edit_crew').addEventListener('change', function() {
  fetchCrewMembers(this.value);
});

/* ── DELETE ── */
function deleteComplaint(trn) {
  showmsgboxJSYN('Delete Complaint', 'Delete this complaint? This cannot be undone.', function() {
    post({ action:'delete', trn: trn })
      .then(res => {
        if (res.ok) showmsgboxJS('Deleted', 'Complaint deleted successfully.', function(){ loadComplaints(); });
        else showmsgbox('Error', res.msg || 'Delete failed.');
      })
      .catch(() => showmsgbox('Error', 'Network error.'));
  });
}

loadComplaints();
</script>
</body>
</html>