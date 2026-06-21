<?php
    include 'js/fphp.php';
?>
<link rel="stylesheet" href="css/g.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
  /* ── Developer grid inside um-body ── */
  .cr-hero {
    background: #0f2417;
    border-radius: 12px;
    display: flex;
    align-items: center;
    padding: 18px 24px;
    gap: 16px;
    flex-shrink: 0;
    position: relative;
    overflow: hidden;
    margin-bottom: 14px;
  }
  .cr-hero::before {
    content: '';
    position: absolute;
    width: 200px; height: 200px; border-radius: 50%;
    background: rgba(255,255,255,0.04);
    top: -70px; right: 80px;
  }
  .cr-hero::after {
    content: '';
    position: absolute;
    width: 100px; height: 100px; border-radius: 50%;
    background: rgba(255,255,255,0.03);
    bottom: -40px; right: 180px;
  }
  .cr-hero-logo {
    width: 48px; height: 48px; border-radius: 12px;
    background: rgba(255,255,255,0.09);
    border: 1.5px solid rgba(255,255,255,0.15);
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; z-index: 1;
  }
  .cr-hero-logo img { width: 30px; height: 30px; object-fit: contain; }
  .cr-hero-info { z-index: 1; }
  .cr-hero-info h2 { font-size: 15px; font-weight: 700; color: #fff; letter-spacing: -0.2px; }
  .cr-hero-info p  { font-size: 10px; color: rgba(255,255,255,0.4); margin-top: 3px; text-transform: uppercase; letter-spacing: 0.09em; }
  .cr-hero-right {
    margin-left: auto; z-index: 1;
    display: flex; align-items: center; gap: 12px;
  }
  .cr-hero-divider { width: 1px; height: 28px; background: rgba(255,255,255,0.1); }
  .cr-ver-label { font-size: 9.5px; color: rgba(255,255,255,0.35); text-transform: uppercase; letter-spacing: 0.08em; text-align: right; }
  .cr-ver-num   { font-family: 'Courier New', monospace; font-size: 16px; font-weight: 700; color: #4cda8f; letter-spacing: -0.5px; text-align: right; }

  /* ── Section card ── */
  .cr-card {
    background: #fff;
    border: 1px solid #dde8df;
    border-radius: 12px;
    overflow: hidden;
    margin-bottom: 14px;
  }
  .cr-card-head {
    padding: 11px 20px;
    border-bottom: 1px solid #f0f4f0;
    display: flex; align-items: center; gap: 8px;
  }
  .cr-card-head .ch-icon {
    width: 24px; height: 24px; border-radius: 6px;
    background: #edf7f1;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
  }
  .cr-card-head span {
    font-size: 10.5px; font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.09em;
    color: #3d6b4a;
  }

  /* ── 3-column dev grid ── */
  .devs-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
  }
  .dev-cell {
    padding: 16px 20px;
    border-right: 1px solid #f0f4f0;
    display: flex; flex-direction: column; gap: 11px;
  }
  .dev-cell:last-child { border-right: none; }

  .dev-top { display: flex; align-items: center; gap: 11px; }
  .dev-avatar {
    width: 40px; height: 40px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 12px; font-weight: 700; color: #fff;
    flex-shrink: 0; letter-spacing: 0.5px;
  }
  .av1 { background: #0f2c1a; }
  .av2 { background: #163d24; }
  .av3 { background: #1a4d2e; }

  .dev-name  { font-size: 13px; font-weight: 700; color: #0f2417; }
  .dev-title { font-size: 11px; color: #7a8a7c; margin-top: 1px; }

  .dev-meta { display: flex; flex-direction: column; gap: 5px; }
  .dev-meta-row {
    display: flex; align-items: center; gap: 6px;
  }
  .dev-meta-row svg { flex-shrink: 0; opacity: 0.4; }
  .dev-meta-row span { font-size: 11px; color: #556b5c; }

  .dev-badge {
    display: inline-flex; align-items: center;
    font-size: 10px; font-weight: 600;
    padding: 2px 9px; border-radius: 20px;
    width: fit-content; margin-top: 2px;
  }
  .b-lead     { background: #edf7f1; color: #1a5c34; border: 1px solid #b8dfc8; }
  .b-backend  { background: #e8f0fd; color: #1a3a7c; border: 1px solid #b3c9f5; }
  .b-frontend { background: #fdf3e8; color: #7c4a1a; border: 1px solid #f5d4b3; }

  /* ── Stack ── */
  .stack-body { padding: 14px 20px; display: flex; flex-wrap: wrap; gap: 7px; }
  .stack-tag {
    display: flex; align-items: center; gap: 6px;
    padding: 4px 12px;
    background: #f4f7f5; border: 1px solid #dde8df;
    border-radius: 7px;
    font-size: 11px; font-weight: 600; color: #2d5a3d;
  }
  .stack-dot { width: 5px; height: 5px; border-radius: 50%; background: #2d9e5f; flex-shrink: 0; }

  /* ── Footer ── */
  .cr-footer {
    text-align: center;
    font-size: 11px; color: #a0afa2;
    padding: 4px 0 2px;
    flex-shrink: 0;
  }
</style>

<!-- Page header — matches .um-header pattern -->

<!-- Body — matches .um-body pattern -->
<div class="um-body" style="display:flex; flex-direction:column;">

  <!-- Hero -->
  <div class="cr-hero">
    <div class="cr-hero-logo">
      <img src="logo.png" alt="Panelco III">
    </div>
    <div class="cr-hero-info">
      <h2>PANELCO III</h2>
      <p>Consumer Data Mapping System</p>
    </div>
    <div class="cr-hero-right">
      <div class="cr-hero-divider"></div>
      <div>
        <div class="cr-ver-label">Version</div>
        <div class="cr-ver-num">v2.0</div>
      </div>
    </div>
  </div>

  <!-- Development Team -->
  <div class="cr-card">
    <div class="cr-card-head">
      <div class="ch-icon">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#1e6b3c" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
      </div>
      <span>Meet the Team</span>
    </div>
    <div class="devs-grid">

      <!-- Dev 1 -->
      <div class="dev-cell">
        <div class="dev-top">
          <div class="dev-avatar av1">MD</div>
          <div>
            <div class="dev-name">Marie Del Rosario</div>
            <div class="dev-title">Developer</div>
          </div>
        </div>
        <div class="dev-meta">
          <div class="dev-meta-row">
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
            <span>PSU Urdaneta</span>
          </div>
          <div class="dev-meta-row">
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            <span>IT</span>
          </div>
          <span class="dev-badge b-lead">Intern</span>
        </div>
      </div>

      <!-- Dev 2 -->
      <div class="dev-cell">
        <div class="dev-top">
          <div class="dev-avatar av2">CR</div>
          <div>
            <div class="dev-name">Charles Vincent Rombaoa</div>
            <div class="dev-title">Developer</div>
          </div>
        </div>
        <div class="dev-meta">
          <div class="dev-meta-row">
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
            <span>UCU</span>
          </div>
          <div class="dev-meta-row">
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            <span>IT </span>
          </div>
          <span class="dev-badge b-backend">Intern</span>
        </div>
      </div>

      <!-- Dev 3 -->
      <div class="dev-cell">
        <div class="dev-top">
          <div class="dev-avatar av3">PM</div>
          <div>
            <div class="dev-name">Prince Earl Martinez</div>
            <div class="dev-title">Developer</div>
          </div>
        </div>
        <div class="dev-meta">
          <div class="dev-meta-row">
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
            <span>UCU</span>
          </div>
          <div class="dev-meta-row">
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            <span>IT</span>
          </div>
          <span class="dev-badge b-frontend">Intern</span>
        </div>
      </div>
    </div>
  </div>

  <!-- Built With -->
  <div class="cr-card">
    <div class="cr-card-head">
      <div class="ch-icon">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#1e6b3c" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
      </div>
      <span>Built With</span>
    </div>
    <div class="stack-body">
      <span class="stack-tag"><span class="stack-dot"></span>PHP</span>
      <span class="stack-tag"><span class="stack-dot"></span>SQL Server</span>
      <span class="stack-tag"><span class="stack-dot"></span>JavaScript</span>
      <span class="stack-tag"><span class="stack-dot"></span>HTML / CSS</span>
      <span class="stack-tag"><span class="stack-dot"></span>WAMP64</span>
    </div>
  </div>

  <!-- Copyright -->
  <div class="cr-footer">
    &copy; <?php echo date('Y'); ?> Panelco III. All rights reserved.
  </div>

</div>