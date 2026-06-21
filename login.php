<?php include 'conn.php'; ?>
<?php include 'js/fphp.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
  <title>MCLIS — Member Consumer Location Information System</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <META HTTP-EQUIV="CACHE-CONTROL" CONTENT="NO-CACHE">
  <link rel="stylesheet" href="css/font-awesome-4.7.0/css/font-awesome.css">
  <link rel="icon" type="image/ico" href="favicon.ico">
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
  <script src="js/jquery.min.js"></script>
  <script src="js/fjs.js"></script>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    html, body {
      height: 100%;
      font-family: 'Sora', sans-serif;
      overflow: hidden;
      -webkit-font-smoothing: antialiased;
    }

    /* ── Two-column layout ── */
    .page {
      display: flex;
      height: 100vh;
      width: 100vw;
    }

    /* ══════════════════════════════════════
       LEFT PANEL
    ══════════════════════════════════════ */
    .left-panel {
      flex: 1;
      min-width: 0;
      background: #010201;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      padding: 44px 52px;
      position: relative;
      overflow: hidden;
    }

    /* Large faded logo watermark */
    .left-panel::before {
      content: '';
      position: absolute;
      inset: 0;
      background: url('back.jpg') no-repeat center center / 130% 130%;
      opacity: 0.26;
      z-index: 0;
    }

    /* Subtle corner gradients for depth */
    .left-panel::after {
      content: '';
      position: absolute;
      inset: 0;
      background:
        radial-gradient(ellipse 70% 55% at 15% 90%, rgba(0, 100, 40, 0.30) 0%, transparent 60%),
        radial-gradient(ellipse 50% 45% at 90% 10%, rgba(0, 70, 28, 0.20) 0%, transparent 55%);
      z-index: 0;
    }

    .left-panel > * { position: relative; z-index: 1; }

    /* Brand mark */
    .brand {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .brand-logo-wrap {
      width: 42px; height: 42px;
      border-radius: 11px;
      background: linear-gradient(135deg, #005a23 0%, #00a849 100%);
      border: 1px solid rgba(0, 210, 80, 0.22);
      display: flex; align-items: center; justify-content: center;
      overflow: hidden;
      flex-shrink: 0;
    }

    .brand-logo-wrap img {
      width: 30px; height: 30px;
      object-fit: contain;
    }

    .brand-text { display: flex; flex-direction: column; gap: 1px; }

    .brand-name {
      font-size: 13px;
      font-weight: 700;
      color: rgba(255, 255, 255, 0.92);
      letter-spacing: 0.03em;
    }

    .brand-sub {
      font-size: 10px;
      color: rgba(255, 255, 255, 0.30);
      letter-spacing: 0.06em;
      font-weight: 400;
    }

    /* Hero copy */
    .hero {
  margin-top: auto;
  margin-bottom: 40px;
}

    .hero-badge {
      display: inline-flex;
      align-items: center;
      gap: 7px;
      rgba(1, 28, 16, 1)   /* dark green */
      rgba(0, 40, 22, 1)   /* mid dark glow areas */
          /* near-black sections */
      border: 1px solid rgba(0, 210, 80, 0.20);
      border-radius: 100px;
      padding: 5px 13px;
      margin-bottom: 24px;
      font-size: 10.5px;
      font-weight: 700;
      color: rgba(46, 210, 100, 0.85);
      letter-spacing: 0.12em;
      text-transform: uppercase;
    }

    .hero-badge-dot {
      width: 6px; height: 6px;
      border-radius: 50%;
      background: #2ed972;
      box-shadow: 0 0 6px rgba(46, 210, 100, 0.70);
      animation: blink 2.6s ease-in-out infinite;
    }

    @keyframes blink {
      0%, 100% { opacity: 1; }
      50%       { opacity: 0.45; }
    }

    .hero-title {
      font-size: clamp(28px, 2.8vw, 25px);
      font-weight: 800;
      color: #ffffff;
      line-height: 1.14;

    }

    .hero-title em {      
      font-style: normal;
      color: #2ecf6a;
    }

    .hero-desc {
      font-size: 13.5px;
      font-weight: 400;
      color: rgba(255, 255, 255, 0.40);
      line-height: 1.80;
      max-width: 310px;
    }

    /* Stats */
    .stats {
      display: flex;
      gap: 0;
      margin-top: 40px;
      border: 1px solid rgba(255, 255, 255, 0.07);
      border-radius: 12px;
      overflow: hidden;
      max-width: 340px;
    }

    .stat {
      flex: 1;
      padding: 16px 18px;
      background: rgba(255, 255, 255, 0.04);
    }

    .stat + .stat {
      border-left: 1px solid rgba(255, 255, 255, 0.07);
    }

    .stat-num {
      font-family: 'JetBrains Mono', monospace;
      font-size: 19px;
      font-weight: 600;
      color: #ffffff;
      margin-bottom: 4px;
      line-height: 1;
    }

    .stat-label {
      font-size: 10px;
      font-weight: 600;
      color: rgba(255, 255, 255, 0.28);
      text-transform: uppercase;
      letter-spacing: 0.07em;
    }

    /* Left footer */
    .left-footer {
      font-family: 'JetBrains Mono', monospace;
      font-size: 10px;
      color: rgba(255, 255, 255, 0.16);
      letter-spacing: 0.08em;
    }

    /* ══════════════════════════════════════
       RIGHT PANEL
    ══════════════════════════════════════ */
    .right-panel {
      width: 468px;
      flex-shrink: 0;
      background: #ffffff;
      display: flex;
      flex-direction: column;
      justify-content: center;
      padding: 52px 56px;
      position: relative;
      border-left: 1px solid rgba(0, 130, 55, 0.09);
    }

    /* Thin top accent */
    .right-panel::before {
      content: '';
      position: absolute;
      top: 0; left: 56px; right: 56px;
      height: 2px;
      background: linear-gradient(90deg, transparent, #00a849, transparent);
      opacity: 0.55;
    }

    /* Section heading */
    .rp-eyebrow {
      font-size: 10.5px;
      font-weight: 700;
      color: #00a849;
      letter-spacing: 0.14em;
      text-transform: uppercase;
      margin-bottom: 8px;
    }

    .rp-title {
      font-size: 26px;
      font-weight: 800;
      color: #091810;
      letter-spacing: -0.03em;
      margin-bottom: 6px;
    }

    .rp-sub {
      font-size: 13px;
      color: #7a9e8a;
      margin-bottom: 34px;
      line-height: 1.5;
    }

    /* ── Error ── */
    .login-error {
      display: flex;
      align-items: flex-start;
      gap: 10px;
      padding: 11px 14px;
      background: #fff6f5;
      border: 1px solid rgba(200, 50, 30, 0.18);
      border-left: 3px solid #d93d25;
      border-radius: 8px;
      font-size: 12.5px;
      color: #9e2010;
      font-weight: 500;
      line-height: 1.45;
      margin-bottom: 22px;
      animation: errIn 0.22s ease;
    }

    @keyframes errIn {
      from { opacity: 0; transform: translateY(-4px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    .login-error i { font-size: 13px; flex-shrink: 0; margin-top: 1px; color: #d93d25; }

    /* ── Fields ── */
    .field { margin-bottom: 16px; }

    .field label {
      display: block;
      font-size: 11px;
      font-weight: 700;
      color: #2d5040;
      letter-spacing: 0.09em;
      text-transform: uppercase;
      margin-bottom: 7px;
    }

    .field-wrap {
      position: relative;
      display: flex;
      align-items: center;
    }

    .field-icon {
      position: absolute;
      left: 14px;
      color: #b0ccba;
      font-size: 13px;
      pointer-events: none;
      transition: color 0.15s;
      z-index: 1;
    }

    .field-wrap:focus-within .field-icon { color: #008837; }

    .field input {
      width: 100%;
      height: 46px;
      padding: 0 46px 0 40px;
      background: #f7fbf8;
      border: 1.5px solid #d4e8db;
      border-radius: 10px;
      font-family: 'Sora', sans-serif;
      font-size: 13.5px;
      color: #091810;
      outline: none;
      transition: border-color 0.15s, box-shadow 0.15s, background 0.15s;
    }

    .field input::placeholder { color: #b8d4c4; }

    .field input:focus {
      background: #ffffff;
      border-color: #00a849;
      box-shadow: 0 0 0 3px rgba(0, 168, 73, 0.10);
    }

    /* Eye toggle */
    .eye-btn {
      position: absolute; right: 12px;
      background: none; border: none; cursor: pointer;
      color: #b0ccba; font-size: 14px;
      width: 28px; height: 28px;
      display: flex; align-items: center; justify-content: center;
      border-radius: 6px;
      transition: color 0.13s, background 0.13s;
    }
    .eye-btn:hover { color: #008837; background: rgba(0, 136, 55, 0.07); }

    /* Row: remember + forgot */
    .form-meta {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-top: 4px;
      margin-bottom: 26px;
    }

    .remember {
      display: flex;
      align-items: center;
      gap: 8px;
      cursor: pointer;
    }

    .remember input[type="checkbox"] {
      width: 15px; height: 15px;
      accent-color: #008837;
      cursor: pointer;
      border-radius: 4px;
    }

    .remember-label {
      font-size: 12.5px;
      font-weight: 500;
      color: #6b9278;
      cursor: pointer;
      user-select: none;
    }

    .forgot-link {
      font-size: 12.5px;
      font-weight: 600;
      color: #008837;
      text-decoration: none;
      transition: opacity 0.13s;
    }
    .forgot-link:hover { opacity: 0.65; }

    /* Submit */
    .btn-submit {
      width: 100%;
      height: 48px;
      background: #008837;
      color: #ffffff;
      border: none;
      border-radius: 10px;
      font-family: 'Sora', sans-serif;
      font-size: 14px;
      font-weight: 700;
      letter-spacing: 0.01em;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 9px;
      transition: background 0.15s, box-shadow 0.15s, transform 0.10s;
      box-shadow: 0 2px 14px rgba(0, 120, 50, 0.26);
      position: relative;
      overflow: hidden;
    }

    /* Top shine */
    .btn-submit::after {
      content: '';
      position: absolute;
      inset: 0;
      background: linear-gradient(180deg, rgba(255,255,255,0.09) 0%, transparent 55%);
      pointer-events: none;
    }

    .btn-submit:hover {
      background: #006b2c;
      box-shadow: 0 4px 22px rgba(0, 110, 44, 0.38);
      transform: translateY(-1px);
    }

    .btn-submit:active {
      transform: none;
      box-shadow: 0 1px 6px rgba(0, 110, 44, 0.22);
    }

    .btn-submit i { font-size: 13px; opacity: 0.85; }

    /* Footer note */
    .rp-footer {
      display: flex;
      align-items: center;
      gap: 9px;
      margin-top: 28px;
      padding-top: 22px;
      border-top: 1px solid #edf5f0;
    }

    .rp-footer-dot {
      width: 7px; height: 7px;
      border-radius: 50%;
      background: #2ecf6a;
      flex-shrink: 0;
      box-shadow: 0 0 6px rgba(46, 207, 106, 0.50);
      animation: blink 2.6s ease-in-out infinite;
    }

    .rp-footer-text {
      font-size: 11.5px;
      font-weight: 500;
      color: #a8c7b5;
      line-height: 1.5;
    }

    /* ── Responsive ── */
    @media (max-width: 820px) {
      .left-panel { display: none; }
      .right-panel { width: 100%; border-left: none; padding: 44px 36px; }
      .right-panel::before { left: 36px; right: 36px; }
    }

    @media (max-width: 440px) {
      .right-panel { padding: 36px 24px; }
    }
  </style>
</head>
<body onload="$('#txtuser').focus();">

<div class="page">

  <!-- ── LEFT PANEL ── -->
  <div class="left-panel">

    <div class="brand">
      <div class="brand-logo-wrap">
        <img src="logo.png" alt="MCLIS">
      </div>
      <div class="brand-text">
        <div class="brand-name">MCLIS</div>
        <div class="brand-sub">PANELCO III</div>
      </div>
    </div>

    <div class="hero">
      <div class="hero-title">
        PANELCO III<br>
        <em>Your Delight, Our Success!</em>
      </div>
      <div class="hero-desc">
      <!--put your description here-->


      </div>


    </div>

    <div class="left-footer">MCLIS v2.0 &nbsp;·&nbsp; PANELCO III &nbsp;·&nbsp; <?php echo date('Y'); ?></div>

  </div>

  <!-- ── RIGHT PANEL ── -->
  <div class="right-panel">

    <div class="rp-eyebrow">Sign in</div>
    <div class="rp-title">Welcome back</div>
    <div class="rp-sub">Enter your credentials to continue.</div>

    <!-- Error -->
    <?php if (isset($_SESSION['ERR_LOGIN_PASS'])): ?>
      <div class="login-error">
        <i class="fa fa-exclamation-circle"></i>
        <span><?php echo htmlspecialchars($_SESSION['ERR_LOGIN_PASS']); unset($_SESSION['ERR_LOGIN_PASS']); ?></span>
      </div>
    <?php endif; ?>

    <form action="login_conf.php" method="POST" id="frm_login" name="frm_login">

      <!-- Username -->
      <div class="field">
        <label for="txtuser">Username</label>
        <div class="field-wrap">
          <i class="field-icon fa fa-user"></i>
          <input type="text" id="txtuser" name="uname"
            placeholder="Your username"
            required autocomplete="username">
        </div>
      </div>

      <!-- Password -->
      <div class="field">
        <label for="txtpass">Password</label>
        <div class="field-wrap">
          <i class="field-icon fa fa-lock"></i>
          <input type="password" id="txtpass" name="upass"
            placeholder="Your password"
            required autocomplete="current-password">
          <button type="button" class="eye-btn" onclick="togglePass()">
            <i class="fa fa-eye" id="eye-icon"></i>
          </button>
        </div>
      </div>

      <!-- Remember + Forgot -->
      <div class="form-meta">
        <label class="remember">
          <input type="checkbox" name="remember" id="remember">
          <span class="remember-label">Remember me</span>
        </label>
        <a href="#" class="forgot-link">Forgot password?</a>
      </div>

      <!-- Submit -->
      <button type="submit" class="btn-submit">
        <i class="fa fa-sign-in"></i>
        Sign In
      </button>

    </form>

    <div class="rp-footer">
      <div class="rp-footer-dot"></div>
      <div class="rp-footer-text">
        Authorized PANELCO III personnel only.
      </div>
    </div>

  </div>

</div>

<script>
  $("#frm_login").submit(function() { /* allow default POST */ });

  function togglePass() {
    var inp  = document.getElementById('txtpass');
    var icon = document.getElementById('eye-icon');
    if (inp.type === 'password') {
      inp.type = 'text';
      icon.className = 'fa fa-eye-slash';
    } else {
      inp.type = 'password';
      icon.className = 'fa fa-eye';
    }
  }
</script>

</body>
</html>