<?php include 'conn.php'; ?>
<?php include 'js/fphp.php'; ?>
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isAdmin = strtolower($_SESSION['user_level'] ?? '') === 'admin';

if (!$isAdmin) {
    die('
        <!DOCTYPE html>
        <html lang="en">
        <head>
          <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
          <title>Access Denied</title>
          <style>
            body { margin:0; font-family: Inter, sans-serif; background:#f5f7f5;
                   display:flex; align-items:center; justify-content:center; height:100vh; }
            .box { background:#fff; border-radius:12px; padding:40px 48px; text-align:center;
                   box-shadow:0 4px 24px rgba(0,0,0,0.08); max-width:360px; }
            .icon { width:56px; height:56px; background:#fef2f2; border-radius:50%;
                    display:flex; align-items:center; justify-content:center; margin:0 auto 18px; }
            h2 { margin:0 0 8px; font-size:18px; color:#111; }
            p  { margin:0 0 24px; font-size:13px; color:#888; line-height:1.6; }
            a  { display:inline-block; padding:9px 22px; background:#0f2417; color:#fff;
                 border-radius:8px; font-size:13px; text-decoration:none; font-weight:500; }
            a:hover { background:#1e3d2b; }
          </style>
        </head>
        <body>
          <div class="box">
            <div class="icon">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#c0392b" stroke-width="2">
                <rect x="3" y="11" width="18" height="11" rx="2"/>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
              </svg>
            </div>
            <h2>Invalid User Rights</h2>
            <p>Only administrator accounts have permission to access this page. Please contact an administrator.</p>
            <a href="index.php">Go Back</a>
          </div>
        </body>
        </html>
    ');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <title>Consumer Data Mapping Management</title>
  <META HTTP-EQUIV="CACHE-CONTROL" CONTENT="NO-CACHE">
  <link rel="stylesheet" href="css/font-awesome-4.7.0/css/font-awesome.css">
  <link rel="stylesheet" href="css/g.css">
  <link rel="icon" type="image/ico" href="favicon.ico">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
  <script src="js/fjs.js"></script>
  <script src="js/jquery.min.js"></script>
  <script src="js/search.js"></script>
  <style>
    .sidebar {
      background: #0f2417 !important;
      border-right: 1px solid #1a3d26 !important;
    }
    .sb-logo {
      border-bottom: 1px solid rgba(255,255,255,0.07) !important;
    }
    .logo-text { color: #fff !important; }

    .mod-header {
      color: rgba(255,255,255,0.80) !important;
    }
    .mod-header:hover {
      background: rgba(255,255,255,0.06) !important;
      color: rgba(255,255,255,0.95) !important;
    }


    .mod-child { color: rgba(255,255,255,0.85) !important; }
    .mod-child:hover {
      background: rgba(255,255,255,0.07) !important;
      color: #fff !important;
    }
    .mod-child.active {
      background: rgba(76,218,143,0.15) !important;
      color: #4cda8f !important;
      border-left: 3px solid #4cda8f !important;
    }
    .mod-child.active svg { stroke: #4cda8f !important; }

    .sb-footer {
      border-top: 1px solid rgba(255,255,255,0.07) !important;
    }
    .logout-btn {
      color: rgba(255,255,255,0.75) !important;
      background: transparent !important;
      border-color: transparent !important;
    }
    .logout-btn:hover {
      background: rgba(255,255,255,0.07) !important;
      color: #fff !important;
    }
  </style>
</head>
<body>

<div class="shell" id="shell">

  <!-- ── SIDEBAR ── -->
  <aside class="sidebar" id="sidebar">

    <!-- Logo -->
    <div class="sb-logo">
      <img class="logo-full" src="logo.png" alt="Panelco III">
      <img class="logo-mini" src="logo.png" alt="P3">
      <span class="logo-text">Panelco III</span>
    </div>

    <!-- Nav -->
    <div class="sb-nav">

      <div class="mod-group">
        <div class="mod-header open" onclick="toggleMod(this,'util-drop')">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M4.93 4.93a10 10 0 0 0 0 14.14"/></svg>
          <span class="mod-label">Utilities</span>
          <svg class="mod-caret" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
        </div>
        <div class="mod-children open" id="util-drop">
          <div class="mod-child" onclick="setActive(this); PageLink('utility/user_mgmt.php','main')">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            <span>User Management</span>
          </div>
          <div class="mod-child" onclick="setActive(this); PageLink('utility/userrights.php','main')">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            <span>User Rights</span>
          </div>
          <div class="mod-child" onclick="setActive(this); PageLink('utility/crew.php','main')">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            <span>Crew</span>
          </div>
          <div class="mod-child" onclick="setActive(this); PageLink('utility/member.php','main')">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
            <span>Personnel</span>
          </div>
          <div class="mod-child" onclick="setActive(this); PageLink('utility/cnr.php','main')">
  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
    <line x1="12" y1="9" x2="12" y2="13"/>
    <line x1="12" y1="17" x2="12.01" y2="17"/>
  </svg>
  <span>Complaint Types</span>
</div>

<div class="mod-child" onclick="setActive(this); PageLink('utility/complaint.php','main')">
  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
    <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
    <rect x="8" y="2" width="8" height="4" rx="1" ry="1"/>
    <line x1="9" y1="12" x2="15" y2="12"/>
    <line x1="9" y1="16" x2="13" y2="16"/>
  </svg>
  <span>Complaints Request</span>
</div>

<div class="mod-child" onclick="setActive(this); PageLink('utility/tag_mgmt.php','main')">
  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
    <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/>
    <line x1="7" y1="7" x2="7.01" y2="7"/>
  </svg>
  <span>Tags</span>
</div>
  </div>
      </div>

      <div class="mod-group">
        <div class="mod-header" onclick="toggleMod(this,'rep-drop')">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 3v18h18"/><path d="M18 17V9M13 17V5M8 17v-3"/></svg>
          <span class="mod-label">Reports</span>
          <svg class="mod-caret" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
        </div>
        <div class="mod-children" id="rep-drop">
          <div class="mod-child" onclick="setActive(this); PageLink('reports/master.php','main')">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            <span>Master Data</span>
          </div>
          <div class="mod-child" onclick="setActive(this); PageLink('reports/cnr.php','main')">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 3v18h18"/><path d="M18 17V9M13 17V5M8 17v-3"/></svg>
            <span>CNR</span>
          </div>
        </div>
      </div>
    </div>
    <div class="sb-footer">
      <button class="logout-btn" onclick="showmsgboxYN('Confirm','Are you sure you want to logout?','./user_logout.php','')">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        <span class="logout-label">Logout</span>
      </button>
      <button class="logout-btn" onclick="PageLink('credits.php','main')">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
        <span class="logout-label">Credits</span>
      </button>
    </div>

  </aside>

  <!-- ── MAIN ── -->
  <div class="main" id="mainArea">

    <!-- Topbar -->
    <div class="topbar">
      <button class="sb-toggle" onclick="toggleSidebar()" title="Toggle sidebar">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
      </button>
      <span class="tb-sep-v"></span>
      <span class="tb-crumb">Consumer Data Management</span>
      
    </div>

    <div class="content" id="main">
      <div class="empty-state">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 9h6M9 12h6M9 15h4"/></svg>
        <p>Select a module to get started</p>
        <span>Choose from Utilities or Reports on the left</span>
      </div>
    </div>

  </div>

</div>

<div id="div_msgbox" class="msgboxdiv"></div>
<div id="div_loader" class="msgboxdiv"></div>

<!-- ── Reusable Confirm Modal ── -->
<div id="cm-overlay">
  <div id="cm-box">
    <div id="cm-header">
      <div id="cm-icon">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
      </div>
      <p id="cm-title">Confirm</p>
    </div>
    <div id="cm-body">
      <p id="cm-message">Are you sure?</p>
    </div>
    <div id="cm-footer">
      <button id="cm-cancel-btn" onclick="hideConfirm()">Cancel</button>
      <button id="cm-confirm-btn">Confirm</button>
    </div>
  </div>
</div>

<script src="js/confirm_modal.js"></script>

<script>

  
  var collapsed = false;

  function toggleSidebar() {
    collapsed = !collapsed;
    document.getElementById('sidebar').classList.toggle('collapsed', collapsed);
  }

  function toggleMod(header, dropId) {
    var drop   = document.getElementById(dropId);
    var isOpen = drop.classList.contains('open');
    if (isOpen) {
      drop.classList.remove('open');
      header.classList.remove('open');
    } else {
      drop.classList.add('open');
      header.classList.add('open');
    }
  }

  function setActive(el) {
    document.querySelectorAll('.mod-child').forEach(function(c) { c.classList.remove('active'); });
    el.classList.add('active');
  }


</script>

</body>
</html>
