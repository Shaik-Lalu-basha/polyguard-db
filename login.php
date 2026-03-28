<?php
require_once __DIR__ . '/backend/db.php';
require_once __DIR__ . '/backend/auth.php';

if (isLoggedIn()) {
    $role = $_SESSION['user']['role'];
    switch ($role) {
        case 'admin':   header('Location: admin/dashboard.php');   exit;
        case 'control': header('Location: control/dashboard.php'); exit;
        case 'police':  header('Location: police/dashboard.php');  exit;
    }
}

$message = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && hash_equals($user['password'], hash('sha256', $password))) {
        $_SESSION['user'] = $user;
        if ($user['role'] === 'admin')         header('Location: admin/dashboard.php');
        elseif ($user['role'] === 'control')   header('Location: control/dashboard.php');
        else                                    header('Location: police/dashboard.php');
        exit;
    } else {
        $message = 'Invalid credentials. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>POLYGUARD AI — Secure Login</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;1,400&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
  <style>
    :root {
      --navy:       #0b1628;
      --navy-mid:   #112240;
      --navy-soft:  #1a3358;
      --gold:       #c9a84c;
      --gold-light: #e8c97a;
      --gold-pale:  #fdf3d7;
      --white:      #ffffff;
      --off-white:  #f7f8fb;
      --border:     #e2e8f0;
      --slate:      #64748b;
      --slate-light:#94a3b8;
      --text-main:  #0f172a;
      --text-sub:   #475569;
      --green:      #10b981;
      --red:        #ef4444;
    }

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    html, body { height: 100%; }

    body {
      font-family: 'DM Sans', sans-serif;
      display: flex;
      min-height: 100vh;
      overflow: hidden;
    }

    /* ── LEFT PANEL ── */
    .left-panel {
      width: 46%;
      background: var(--navy);
      position: relative;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      padding: 40px 50px 44px;
      overflow: hidden;
      flex-shrink: 0;
    }

    /* Decorative orbs */
    .left-panel::before {
      content: '';
      position: absolute; top: -100px; right: -100px;
      width: 420px; height: 420px; border-radius: 50%;
      background: radial-gradient(circle, rgba(201,168,76,.13) 0%, transparent 65%);
      pointer-events: none;
    }
    .left-panel::after {
      content: '';
      position: absolute; bottom: -80px; left: -60px;
      width: 320px; height: 320px; border-radius: 50%;
      background: radial-gradient(circle, rgba(59,130,246,.07) 0%, transparent 65%);
      pointer-events: none;
    }

    /* Animated grid lines */
    .grid-lines {
      position: absolute; inset: 0; pointer-events: none;
      background-image:
        linear-gradient(rgba(201,168,76,.04) 1px, transparent 1px),
        linear-gradient(90deg, rgba(201,168,76,.04) 1px, transparent 1px);
      background-size: 48px 48px;
    }

    /* Big watermark text */
    .watermark {
      position: absolute;
      bottom: 80px; left: -12px;
      font-family: 'Bebas Neue';
      font-size: 110px;
      letter-spacing: 6px;
      color: rgba(255,255,255,.04);
      line-height: 1;
      pointer-events: none;
      user-select: none;
      white-space: nowrap;
    }

    /* Top brand */
    .left-brand {
      display: flex; align-items: center; gap: 14px;
      position: relative; z-index: 1;
      animation: fadeDown .6s ease both;
    }
    .brand-logo {
      width: 44px; height: 44px; border-radius: 11px;
      background: linear-gradient(135deg, var(--gold), var(--gold-light));
      display: grid; place-items: center;
      font-family: 'Bebas Neue'; font-size: 17px; color: var(--navy);
      box-shadow: 0 4px 16px rgba(201,168,76,.35);
      flex-shrink: 0;
    }
    .brand-text {}
    .brand-name { font-family: 'Bebas Neue'; font-size: 22px; letter-spacing: 3px; color: #fff; display: block; }
    .brand-name em { color: var(--gold); font-style: normal; }
    .brand-tagline { font-family: 'JetBrains Mono'; font-size: 10px; color: rgba(255,255,255,.35); letter-spacing: 1.5px; display: block; margin-top: 1px; }

    /* Hero text block */
    .left-hero {
      position: relative; z-index: 1;
      animation: fadeUp .65s ease .08s both;
    }
    .left-eyebrow {
      display: flex; align-items: center; gap: 10px;
      margin-bottom: 16px;
    }
    .eyebrow-line { width: 32px; height: 2px; background: var(--gold); }
    .eyebrow-text { font-family: 'JetBrains Mono'; font-size: 11px; letter-spacing: 2.5px; color: var(--gold); font-weight: 600; }

    .left-title {
      font-family: 'Bebas Neue';
      font-size: 64px; letter-spacing: 2px;
      color: #fff; line-height: .95;
      margin-bottom: 8px;
    }
    .left-title span { color: var(--gold); }
    .left-sub {
      font-size: 14px; color: rgba(255,255,255,.5);
      font-weight: 400; line-height: 1.6;
      max-width: 320px; margin-top: 14px;
    }

    /* Feature list */
    .left-features {
      display: flex; flex-direction: column; gap: 11px;
      margin-top: 30px;
      position: relative; z-index: 1;
    }
    .feature-item {
      display: flex; align-items: center; gap: 12px;
      animation: slideRight .5s ease both;
    }
    .feature-item:nth-child(1) { animation-delay: .15s; }
    .feature-item:nth-child(2) { animation-delay: .22s; }
    .feature-item:nth-child(3) { animation-delay: .29s; }
    .feature-item:nth-child(4) { animation-delay: .36s; }
    .feature-dot {
      width: 7px; height: 7px; border-radius: 50%;
      background: var(--gold); flex-shrink: 0;
      box-shadow: 0 0 6px rgba(201,168,76,.5);
    }
    .feature-text { font-size: 13.5px; color: rgba(255,255,255,.6); }

    /* Progress dots */
    .left-footer {
      display: flex; align-items: center; gap: 8px;
      position: relative; z-index: 1;
      animation: fadeUp .5s ease .4s both;
    }
    .dot { width: 28px; height: 3px; border-radius: 2px; }
    .dot-active { background: var(--gold); }
    .dot-inactive { background: rgba(255,255,255,.15); width: 18px; }

    /* ── RIGHT PANEL ── */
    .right-panel {
      flex: 1;
      background: var(--white);
      display: flex;
      flex-direction: column;
      position: relative;
      overflow-y: auto;
    }

    /* Top bar */
    .right-topbar {
      display: flex; align-items: center; justify-content: space-between;
      padding: 26px 48px;
      border-bottom: 1px solid var(--border);
      animation: fadeDown .5s ease both;
    }
    .back-link {
      display: flex; align-items: center; gap: 6px;
      font-size: 12px; font-weight: 600; letter-spacing: 1px;
      color: var(--slate); text-decoration: none;
      text-transform: uppercase; font-family: 'JetBrains Mono';
      transition: color .18s ease;
    }
    .back-link:hover { color: var(--navy); }
    .back-arrow { font-size: 14px; }
    .topbar-secure {
      display: flex; align-items: center; gap: 7px;
      font-size: 12px; color: var(--slate-light);
      font-family: 'JetBrains Mono';
    }
    .secure-dot { width: 7px; height: 7px; border-radius: 50%; background: var(--green); position: relative; }
    .secure-dot::after { content: ''; position: absolute; top: -3px; left: -3px; width: 13px; height: 13px; border-radius: 50%; background: rgba(16,185,129,.2); animation: ripple 1.8s infinite; }

    /* Form area */
    .right-form-wrap {
      flex: 1; display: flex; flex-direction: column;
      justify-content: center; align-items: center;
      padding: 48px 48px 40px;
    }
    .form-container {
      width: 100%; max-width: 420px;
      animation: fadeUp .55s ease .1s both;
    }

    .form-eyebrow {
      font-family: 'JetBrains Mono'; font-size: 11px; letter-spacing: 2.5px;
      color: var(--gold); font-weight: 600; text-transform: uppercase;
      margin-bottom: 10px;
    }
    .form-heading {
      font-family: 'Bebas Neue';
      font-size: 44px; letter-spacing: 2px; color: var(--navy); line-height: 1;
      margin-bottom: 6px;
    }
    .form-heading em { color: var(--gold); font-style: italic; }
    .form-sub { font-size: 13.5px; color: var(--slate); margin-bottom: 34px; line-height: 1.5; }

    /* Error */
    .form-error {
      display: flex; align-items: center; gap: 10px;
      background: #fef2f2; border: 1px solid #fca5a5;
      color: #991b1b; font-size: 13px; font-weight: 500;
      padding: 13px 16px; border-radius: 10px;
      margin-bottom: 20px;
      animation: shake .4s ease;
    }

    /* Fields */
    .field { display: flex; flex-direction: column; gap: 7px; margin-bottom: 18px; }
    .field label {
      font-size: 11px; font-weight: 700;
      text-transform: uppercase; letter-spacing: 1px; color: var(--navy);
    }
    .field-wrap { position: relative; }
    .field input {
      width: 100%; height: 50px;
      padding: 0 44px 0 16px;
      border: 1.5px solid var(--border);
      border-radius: 10px;
      font-family: 'DM Sans'; font-size: 14px; color: var(--text-main);
      background: var(--off-white);
      outline: none;
      transition: border-color .2s ease, box-shadow .2s ease, background .2s ease;
    }
    .field input::placeholder { color: var(--slate-light); }
    .field input:focus {
      border-color: var(--gold);
      background: var(--white);
      box-shadow: 0 0 0 3px rgba(201,168,76,.13);
    }
    .field-icon {
      position: absolute; right: 14px; top: 50%; transform: translateY(-50%);
      color: var(--slate-light); font-size: 16px; pointer-events: none;
    }
    .toggle-pw {
      position: absolute; right: 14px; top: 50%; transform: translateY(-50%);
      cursor: pointer; color: var(--slate-light); font-size: 16px;
      background: none; border: none; padding: 0; line-height: 1;
      transition: color .18s ease;
    }
    .toggle-pw:hover { color: var(--navy); }

    /* Role selector */
    .role-selector {
      display: grid; grid-template-columns: 1fr 1fr 1fr;
      gap: 10px; margin-bottom: 26px;
    }
    .role-option { display: none; }
    .role-label {
      display: flex; flex-direction: column; align-items: center; gap: 6px;
      padding: 12px 10px; border-radius: 10px;
      border: 1.5px solid var(--border); cursor: pointer;
      background: var(--off-white);
      transition: all .2s ease;
      text-align: center;
    }
    .role-label:hover { border-color: var(--gold); background: var(--gold-pale); }
    .role-option:checked + .role-label {
      border-color: var(--gold); background: var(--gold-pale);
      box-shadow: 0 0 0 3px rgba(201,168,76,.15);
    }
    .role-emoji { font-size: 22px; }
    .role-name  { font-size: 11px; font-weight: 700; color: var(--navy); letter-spacing: .5px; text-transform: uppercase; }

    /* Submit */
    .btn-login {
      width: 100%; height: 52px;
      background: linear-gradient(135deg, var(--navy) 0%, var(--navy-soft) 100%);
      color: var(--white); border: none; border-radius: 10px;
      font-family: 'DM Sans'; font-size: 15px; font-weight: 700;
      letter-spacing: .5px; cursor: pointer;
      display: flex; align-items: center; justify-content: center; gap: 9px;
      position: relative; overflow: hidden;
      transition: transform .2s ease, box-shadow .2s ease;
      margin-bottom: 14px;
    }
    .btn-login::before {
      content: ''; position: absolute; top: 0; left: -100%; width: 100%; height: 100%;
      background: linear-gradient(90deg, transparent, rgba(201,168,76,.2), transparent);
      transition: left .45s ease;
    }
    .btn-login:hover { transform: translateY(-2px); box-shadow: 0 10px 28px rgba(11,22,40,.28); }
    .btn-login:hover::before { left: 100%; }
    .btn-login:active { transform: translateY(0); }
    .btn-arrow { font-size: 18px; }

    /* Gold accent divider */
    .form-divider {
      display: flex; align-items: center; gap: 14px;
      margin: 4px 0 14px;
    }
    .divider-line { flex: 1; height: 1px; background: var(--border); }
    .divider-text { font-size: 11px; color: var(--slate-light); font-family: 'JetBrains Mono'; letter-spacing: .5px; }

    /* Bottom hint */
    .form-hint {
      text-align: center; font-size: 12px; color: var(--slate-light);
      font-family: 'JetBrains Mono'; margin-top: 8px;
    }

    /* Footer */
    .right-footer {
      padding: 18px 48px;
      border-top: 1px solid var(--border);
      display: flex; align-items: center; justify-content: space-between;
      animation: fadeUp .5s ease .35s both;
    }
    .rf-brand { font-family: 'Bebas Neue'; font-size: 14px; letter-spacing: 2px; color: var(--slate-light); }
    .rf-brand span { color: var(--gold); }
    .rf-copy  { font-size: 11px; color: var(--slate-light); font-family: 'JetBrains Mono'; }

    /* ── ANIMATIONS ── */
    @keyframes fadeDown  { from { opacity:0; transform:translateY(-14px); } to { opacity:1; transform:translateY(0); } }
    @keyframes fadeUp    { from { opacity:0; transform:translateY(18px); }  to { opacity:1; transform:translateY(0); } }
    @keyframes slideRight{ from { opacity:0; transform:translateX(-12px); } to { opacity:1; transform:translateX(0); } }
    @keyframes ripple    { 0%{ transform:scale(1);opacity:.6; } 70%{ transform:scale(2.3);opacity:0; } 100%{ transform:scale(1);opacity:0; } }
    @keyframes shake     { 0%,100%{ transform:translateX(0); } 20%{ transform:translateX(-6px); } 40%{ transform:translateX(6px); } 60%{ transform:translateX(-4px); } 80%{ transform:translateX(4px); } }
    @keyframes float     { 0%,100%{ transform:translateY(0); } 50%{ transform:translateY(-10px); } }

    /* ── RESPONSIVE ── */
    @media (max-width: 900px) {
      body { flex-direction: column; overflow: auto; }
      .left-panel { width: 100%; min-height: 260px; padding: 30px 28px 28px; }
      .left-title { font-size: 44px; }
      .watermark { font-size: 72px; }
      .right-panel { min-height: 100vh; }
      .right-topbar, .right-footer { padding: 18px 24px; }
      .right-form-wrap { padding: 32px 24px; }
    }
  </style>
</head>
<body>

<!-- ── LEFT PANEL ── -->
<div class="left-panel">
  <div class="grid-lines"></div>
  <div class="watermark">PG AI</div>

  <!-- Brand -->
  <div class="left-brand">
    <div class="brand-logo">PG</div>
    <div class="brand-text">
      <span class="brand-name">POLY<em>GUARD</em> AI</span>
      <span class="brand-tagline">SMART BANDOBUSTH SYSTEM</span>
    </div>
  </div>

  <!-- Hero -->
  <div class="left-hero">
    <div class="left-eyebrow">
      <div class="eyebrow-line"></div>
      <div class="eyebrow-text">SECURE PORTAL</div>
    </div>
    <div class="left-title">
      Welcome<br>Back,<br><span>Sign In</span>
    </div>
    <div class="left-sub">
      Login to access duty assignments, real-time tracking, compliance monitoring and alerts.
    </div>
    <div class="left-features">
      <div class="feature-item"><div class="feature-dot"></div><div class="feature-text">Real-time geo-fence duty tracking</div></div>
      <div class="feature-item"><div class="feature-dot"></div><div class="feature-text">AI-powered compliance monitoring</div></div>
      <div class="feature-item"><div class="feature-dot"></div><div class="feature-text">Instant breach & alert notifications</div></div>
      <div class="feature-item"><div class="feature-dot"></div><div class="feature-text">Role-based access for all personnel</div></div>
    </div>
  </div>

  <!-- Progress dots -->
  <div class="left-footer">
    <div class="dot dot-active"></div>
    <div class="dot dot-inactive"></div>
    <div class="dot dot-inactive"></div>
  </div>
</div>

<!-- ── RIGHT PANEL ── -->
<div class="right-panel">

  <!-- Top bar -->
  <div class="right-topbar">
    <a class="back-link" href="#">
      <span class="back-arrow">←</span> Back to Site
    </a>
    <div class="topbar-secure">
      <div class="secure-dot"></div>
      Secure Connection
    </div>
  </div>

  <!-- Form -->
  <div class="right-form-wrap">
    <div class="form-container">

      <div class="form-eyebrow">OFFICER LOGIN</div>
      <div class="form-heading">Sign in to <em>Your Account</em></div>
      <div class="form-sub">Enter your credentials to access the POLYGUARD AI monitoring portal.</div>

      <?php if ($message): ?>
      <div class="form-error">
        <span>⚠</span>
        <span><?= htmlspecialchars($message) ?></span>
      </div>
      <?php endif; ?>

      <form method="post" autocomplete="off">

        <!-- Username -->
        <div class="field">
          <label for="username">Username <span style="color:var(--red)">*</span></label>
          <div class="field-wrap">
            <input type="text" id="username" name="username"
                   placeholder="Enter your username"
                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                   required autocomplete="username">
            <span class="field-icon">👤</span>
          </div>
        </div>

        <!-- Password -->
        <div class="field">
          <label for="password">Password <span style="color:var(--red)">*</span></label>
          <div class="field-wrap">
            <input type="password" id="password" name="password"
                   placeholder="Enter your password" required autocomplete="current-password">
            <button type="button" class="toggle-pw" onclick="togglePw()" id="pwToggle" title="Show/hide password">👁</button>
          </div>
        </div>

        <!-- Submit -->
        <button class="btn-login" type="submit">
          <span class="btn-arrow">→</span> Login to Portal
        </button>

        <div class="form-divider">
          <div class="divider-line"></div>
          <div class="divider-text">ROLE ACCESS</div>
          <div class="divider-line"></div>
        </div>

        <!-- Role hints -->
        <div class="role-selector">
          <div style="display:flex;flex-direction:column;align-items:center;gap:6px;padding:12px 10px;border-radius:10px;border:1.5px solid var(--border);background:var(--off-white);">
            <span style="font-size:22px">⭐</span>
            <span style="font-size:11px;font-weight:700;color:var(--navy);letter-spacing:.5px;text-transform:uppercase">Admin</span>
          </div>
          <div style="display:flex;flex-direction:column;align-items:center;gap:6px;padding:12px 10px;border-radius:10px;border:1.5px solid var(--border);background:var(--off-white);">
            <span style="font-size:22px">🎯</span>
            <span style="font-size:11px;font-weight:700;color:var(--navy);letter-spacing:.5px;text-transform:uppercase">Control</span>
          </div>
          <div style="display:flex;flex-direction:column;align-items:center;gap:6px;padding:12px 10px;border-radius:10px;border:1.5px solid var(--border);background:var(--off-white);">
            <span style="font-size:22px">🛡</span>
            <span style="font-size:11px;font-weight:700;color:var(--navy);letter-spacing:.5px;text-transform:uppercase">Officer</span>
          </div>
        </div>

        <div class="form-hint">🔒 Protected by 256-bit encryption · POLYGUARD AI v1.0</div>
      </form>
    </div>
  </div>

  <!-- Footer -->
  <div class="right-footer">
    <div class="rf-brand">POLY<span>GUARD</span> AI</div>
    <div class="rf-copy">© <?= date('Y') ?> Smart Bandobusth Duty Monitoring System</div>
  </div>

</div><!-- /right-panel -->

<script>
  function togglePw() {
    const inp = document.getElementById('password');
    const btn = document.getElementById('pwToggle');
    if (inp.type === 'password') { inp.type = 'text';     btn.textContent = '🙈'; }
    else                         { inp.type = 'password'; btn.textContent = '👁'; }
  }

  // Input gold focus ring visual
  document.querySelectorAll('.field input').forEach(inp => {
    inp.addEventListener('focus', () => { inp.closest('.field-wrap').style.transform = 'scale(1.005)'; });
    inp.addEventListener('blur',  () => { inp.closest('.field-wrap').style.transform = ''; });
  });
</script>
</body>
</html>