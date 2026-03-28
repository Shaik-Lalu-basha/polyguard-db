<?php
require_once __DIR__ . '/../backend/db.php';
require_once __DIR__ . '/../backend/auth.php';
require_once __DIR__ . '/../backend/security.php';
// Load central config
$POLYGUARD_CONFIG = require_once __DIR__ . '/../backend/config.php';
require_once __DIR__ . '/../backend/advanced_analytics.php';
require_once __DIR__ . '/../backend/blockchain_advanced.php';
requireRole('admin');
initSecurityTables($pdo);

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'add_personnel') {
        $name = trim($_POST['name']);
        $username = trim($_POST['username']);
        $rank = trim($_POST['rank']);
        $mobile = trim($_POST['mobile']);
        $password = $_POST['password'];

        if ($name && $username && $password) {
            // Check if username already exists
            $checkStmt = $pdo->prepare('SELECT user_id FROM users WHERE username = ? LIMIT 1');
            $checkStmt->execute([$username]);
            if ($checkStmt->fetch()) {
                $error = 'Username already exists. Please choose a different username.';
            } else {
                $stmt = $pdo->prepare('INSERT INTO users (username,password,name,rank,mobile,role) VALUES(?,?,?,?,?,?)');
                $stmt->execute([$username, hash('sha256',$password), $name, $rank, $mobile, 'police']);
                $success = 'Personnel added successfully.';
            }
        } else {
            $error = 'All fields are required.';
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'assign_duty') {
        $personnel_id = (int)$_POST['personnel_id'];
        $location_name = trim($_POST['location_name']);
        $latitude = (float)$_POST['latitude'];
        $longitude = (float)$_POST['longitude'];
        $radius = (int)$_POST['radius'];
        $start_time = $_POST['start_time'];
        $end_time = $_POST['end_time'];

        $stmt = $pdo->prepare('INSERT INTO duty_assignments (personnel_id, location_name, latitude, longitude, radius, start_time,end_time) VALUES (?,?,?,?,?,?,?)');
        $stmt->execute([$personnel_id,$location_name,$latitude,$longitude,$radius,$start_time,$end_time]);
        $duty_id = $pdo->lastInsertId();
        $pdo->prepare('INSERT INTO attendance (personnel_id,duty_id) VALUES(?,?)')->execute([$personnel_id,$duty_id]);
        $pdo->prepare('INSERT INTO compliance (personnel_id,duty_id) VALUES(?,?)')->execute([$personnel_id,$duty_id]);
        $success = 'Duty assigned successfully.';
    }
}

$personnel = $pdo->query('SELECT user_id,name,rank,mobile FROM users WHERE role="police"')->fetchAll();
$duties = $pdo->query('SELECT da.*, u.name as officer FROM duty_assignments da JOIN users u ON da.personnel_id=u.user_id ORDER BY da.created_at DESC')->fetchAll();
$alerts = $pdo->query('SELECT a.*, u.name FROM alerts a JOIN users u ON a.personnel_id=u.user_id ORDER BY alert_time DESC LIMIT 8')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin — POLYGUARD AI</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
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
      --slate:      #64748b;
      --slate-light:#94a3b8;
      --border:     #e2e8f0;
      --border-dark:#cbd5e1;
      --success:    #10b981;
      --danger:     #ef4444;
      --warning:    #f59e0b;
      --text-main:  #0f172a;
      --text-sub:   #475569;
      --shadow-sm:  0 1px 3px rgba(0,0,0,.06), 0 1px 2px rgba(0,0,0,.04);
      --shadow-md:  0 4px 16px rgba(11,22,40,.08), 0 1px 4px rgba(11,22,40,.04);
      --shadow-lg:  0 12px 40px rgba(11,22,40,.12), 0 4px 12px rgba(11,22,40,.06);
      --radius:     14px;
      --radius-sm:  8px;
    }

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    html { scroll-behavior: smooth; }

    body {
      font-family: 'DM Sans', sans-serif;
      background: var(--off-white);
      color: var(--text-main);
      min-height: 100vh;
      overflow-x: hidden;
    }

    /* ── NAVBAR ── */
    .navbar {
      position: sticky; top: 0; z-index: 100;
      background: var(--navy);
      display: flex; align-items: center; justify-content: space-between;
      padding: 0 36px;
      height: 64px;
      box-shadow: 0 2px 20px rgba(0,0,0,.25);
    }
    .navbar::after {
      content: '';
      position: absolute; bottom: 0; left: 0; right: 0; height: 2px;
      background: linear-gradient(90deg, var(--gold) 0%, var(--gold-light) 50%, var(--gold) 100%);
    }
    .nav-brand {
      display: flex; align-items: center; gap: 14px;
    }
    .nav-badge {
      width: 36px; height: 36px;
      background: linear-gradient(135deg, var(--gold), var(--gold-light));
      border-radius: 8px;
      display: grid; place-items: center;
      font-family: 'Bebas Neue', sans-serif;
      font-size: 15px; color: var(--navy); letter-spacing: 1px;
    }
    .nav-title {
      font-family: 'Bebas Neue', sans-serif;
      font-size: 22px; letter-spacing: 3px;
      color: var(--white);
    }
    .nav-title span { color: var(--gold); }
    .nav-right { display: flex; align-items: center; gap: 16px; }
    .nav-time {
      font-family: 'JetBrains Mono', monospace;
      font-size: 13px; color: var(--slate-light);
    }
    .btn-logout {
      display: inline-flex; align-items: center; gap: 7px;
      background: transparent;
      border: 1.5px solid rgba(201,168,76,.4);
      color: var(--gold-light);
      padding: 8px 18px; border-radius: 7px;
      font-family: 'DM Sans', sans-serif; font-size: 13px; font-weight: 500;
      letter-spacing: .3px;
      text-decoration: none; cursor: pointer;
      transition: all .22s ease;
    }
    .btn-logout:hover {
      background: var(--gold); color: var(--navy); border-color: var(--gold);
      transform: translateY(-1px);
    }

    /* ── LAYOUT ── */
    .container {
      max-width: 1320px;
      margin: 0 auto;
      padding: 36px 24px 60px;
    }

    /* ── PAGE HEADER ── */
    .page-header {
      margin-bottom: 32px;
      animation: fadeDown .5s ease both;
    }
    .page-header h1 {
      font-family: 'Bebas Neue', sans-serif;
      font-size: 38px; letter-spacing: 3px; color: var(--navy);
      line-height: 1;
    }
    .page-header p {
      color: var(--slate); font-size: 14px; margin-top: 6px; font-weight: 400;
    }
    .breadcrumb {
      display: flex; align-items: center; gap: 8px;
      font-size: 12px; color: var(--slate-light);
      font-family: 'JetBrains Mono', monospace;
      margin-bottom: 10px;
    }
    .breadcrumb-sep { opacity: .4; }

    /* ── ALERTS ── */
    .alert {
      display: flex; align-items: center; gap: 12px;
      padding: 14px 20px; border-radius: var(--radius-sm);
      font-size: 14px; font-weight: 500;
      margin-bottom: 24px;
      animation: slideIn .4s ease both;
    }
    .alert-success { background: #ecfdf5; border: 1px solid #6ee7b7; color: #065f46; }
    .alert-error   { background: #fef2f2; border: 1px solid #fca5a5; color: #991b1b; }
    .alert-icon { font-size: 18px; }

    /* ── STAT CARDS ── */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 20px; margin-bottom: 32px;
    }
    .stat-card {
      background: var(--white);
      border-radius: var(--radius);
      padding: 24px 26px;
      box-shadow: var(--shadow-md);
      border: 1px solid var(--border);
      position: relative; overflow: hidden;
      transition: transform .2s ease, box-shadow .2s ease;
      animation: fadeUp .5s ease both;
    }
    .stat-card:nth-child(1) { animation-delay: .05s; }
    .stat-card:nth-child(2) { animation-delay: .1s; }
    .stat-card:nth-child(3) { animation-delay: .15s; }
    .stat-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-lg); }
    .stat-card::before {
      content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px;
      background: linear-gradient(90deg, var(--gold), var(--gold-light));
    }
    .stat-card-icon {
      width: 46px; height: 46px; border-radius: 12px;
      display: grid; place-items: center; font-size: 22px;
      margin-bottom: 16px;
    }
    .icon-blue  { background: #eff6ff; }
    .icon-green { background: #ecfdf5; }
    .icon-amber { background: #fffbeb; }
    .stat-label { font-size: 12px; font-weight: 500; color: var(--slate); text-transform: uppercase; letter-spacing: 1px; }
    .stat-value {
      font-family: 'Bebas Neue', sans-serif;
      font-size: 42px; color: var(--navy); letter-spacing: 1px; line-height: 1; margin-top: 4px;
    }
    .stat-sub { font-size: 12px; color: var(--slate-light); margin-top: 6px; }

    /* ── SECTION CARDS ── */
    .section-card {
      background: var(--white);
      border-radius: var(--radius);
      box-shadow: var(--shadow-md);
      border: 1px solid var(--border);
      margin-bottom: 28px;
      overflow: hidden;
      animation: fadeUp .55s ease both;
    }
    .section-card:nth-child(1) { animation-delay: .1s; }
    .section-card:nth-child(2) { animation-delay: .18s; }
    .section-card:nth-child(3) { animation-delay: .26s; }
    .section-card:nth-child(4) { animation-delay: .34s; }

    .section-header {
      display: flex; align-items: center; gap: 14px;
      padding: 22px 28px;
      border-bottom: 1px solid var(--border);
      background: var(--white);
    }
    .section-icon {
      width: 38px; height: 38px; border-radius: 9px;
      display: grid; place-items: center; font-size: 18px;
      background: var(--navy); flex-shrink: 0;
    }
    .section-title { font-size: 16px; font-weight: 600; color: var(--navy); letter-spacing: .2px; }
    .section-subtitle { font-size: 12px; color: var(--slate); margin-top: 1px; }
    .section-body { padding: 28px; }

    /* ── TWO COLUMN GRID ── */
    .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
    @media (max-width: 900px) { .two-col { grid-template-columns: 1fr; } }

    /* ── FORM ── */
    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px 20px; }
    .form-grid .full { grid-column: 1 / -1; }
    @media (max-width: 640px) { .form-grid { grid-template-columns: 1fr; } }

    .field { display: flex; flex-direction: column; gap: 6px; }
    .field label {
      font-size: 12px; font-weight: 600; color: var(--navy);
      text-transform: uppercase; letter-spacing: .8px;
    }
    .field input, .field select {
      height: 44px;
      padding: 0 14px;
      border: 1.5px solid var(--border-dark);
      border-radius: var(--radius-sm);
      font-family: 'DM Sans', sans-serif; font-size: 14px; color: var(--text-main);
      background: var(--off-white);
      outline: none;
      transition: border-color .18s ease, box-shadow .18s ease, background .18s ease;
    }
    .field input:focus, .field select:focus {
      border-color: var(--gold);
      background: var(--white);
      box-shadow: 0 0 0 3px rgba(201,168,76,.12);
    }
    .field select { cursor: pointer; }

    .btn-submit {
      margin-top: 8px;
      display: inline-flex; align-items: center; gap: 9px;
      background: linear-gradient(135deg, var(--navy), var(--navy-soft));
      color: var(--white);
      border: none; cursor: pointer;
      padding: 13px 28px; border-radius: var(--radius-sm);
      font-family: 'DM Sans', sans-serif; font-size: 14px; font-weight: 600;
      letter-spacing: .3px;
      position: relative; overflow: hidden;
      transition: transform .18s ease, box-shadow .18s ease;
    }
    .btn-submit::before {
      content: ''; position: absolute; top: 0; left: -100%; width: 100%; height: 100%;
      background: linear-gradient(90deg, transparent, rgba(201,168,76,.2), transparent);
      transition: left .4s ease;
    }
    .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(11,22,40,.25); }
    .btn-submit:hover::before { left: 100%; }
    .btn-submit:active { transform: translateY(0); }

    /* ── TABLE ── */
    .table-wrapper { overflow-x: auto; border-radius: var(--radius-sm); }
    table {
      width: 100%; border-collapse: collapse;
      font-size: 13.5px;
    }
    thead tr {
      background: var(--navy);
    }
    thead th {
      padding: 13px 16px; text-align: left;
      font-size: 11px; font-weight: 600;
      color: var(--gold-light);
      text-transform: uppercase; letter-spacing: 1px;
      white-space: nowrap;
    }
    thead th:first-child { border-radius: 8px 0 0 0; }
    thead th:last-child  { border-radius: 0 8px 0 0; }
    tbody tr {
      border-bottom: 1px solid var(--border);
      transition: background .15s ease;
    }
    tbody tr:last-child { border-bottom: none; }
    tbody tr:hover { background: var(--off-white); }
    tbody td { padding: 13px 16px; color: var(--text-sub); vertical-align: middle; }
    tbody td:first-child { font-family: 'JetBrains Mono', monospace; font-size: 12px; color: var(--slate); }

    /* Badges */
    .badge {
      display: inline-flex; align-items: center; gap: 5px;
      padding: 4px 10px; border-radius: 20px;
      font-size: 11px; font-weight: 600; letter-spacing: .4px;
      white-space: nowrap;
    }
    .badge::before { content: ''; width: 6px; height: 6px; border-radius: 50%; }
    .badge-active  { background: #ecfdf5; color: #065f46; }
    .badge-active::before { background: #10b981; }
    .badge-pending { background: #fffbeb; color: #92400e; }
    .badge-pending::before { background: #f59e0b; }
    .badge-breach  { background: #fef2f2; color: #991b1b; }
    .badge-breach::before { background: #ef4444; }

    /* Alert type pill */
    .alert-type {
      font-family: 'JetBrains Mono', monospace;
      font-size: 11px; padding: 3px 8px; border-radius: 4px;
      background: var(--gold-pale); color: #78350f; font-weight: 600;
    }

    /* ── DIVIDER ── */
    .section-divider {
      display: flex; align-items: center; gap: 16px;
      margin: 40px 0 28px;
    }
    .section-divider-line { flex: 1; height: 1px; background: var(--border); }
    .section-divider-label {
      font-family: 'Bebas Neue', sans-serif;
      font-size: 13px; letter-spacing: 3px; color: var(--slate-light);
    }

    /* ── PULSE DOT ── */
    .pulse-dot {
      width: 8px; height: 8px; border-radius: 50%; background: var(--success);
      position: relative; display: inline-block;
    }
    .pulse-dot::after {
      content: ''; position: absolute; top: -3px; left: -3px;
      width: 14px; height: 14px; border-radius: 50%;
      background: rgba(16,185,129,.25);
      animation: pulse 1.8s ease infinite;
    }

    /* ── EMPTY STATE ── */
    .empty-state {
      text-align: center; padding: 48px 24px;
      color: var(--slate-light); font-size: 14px;
    }
    .empty-state-icon { font-size: 36px; opacity: .3; margin-bottom: 10px; }

    /* ── ANIMATIONS ── */
    @keyframes fadeDown {
      from { opacity: 0; transform: translateY(-16px); }
      to   { opacity: 1; transform: translateY(0); }
    }
    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(20px); }
      to   { opacity: 1; transform: translateY(0); }
    }
    @keyframes slideIn {
      from { opacity: 0; transform: translateX(-12px); }
      to   { opacity: 1; transform: translateX(0); }
    }
    @keyframes pulse {
      0%   { transform: scale(1); opacity: .6; }
      70%  { transform: scale(2); opacity: 0; }
      100% { transform: scale(1); opacity: 0; }
    }
    @keyframes shimmer {
      from { background-position: -200% center; }
      to   { background-position: 200% center; }
    }

    /* ── SCROLLBAR ── */
    ::-webkit-scrollbar { width: 6px; height: 6px; }
    ::-webkit-scrollbar-track { background: var(--off-white); }
    ::-webkit-scrollbar-thumb { background: var(--border-dark); border-radius: 3px; }
    ::-webkit-scrollbar-thumb:hover { background: var(--slate-light); }

    /* ── TOOLTIP ── */
    [data-tip] { position: relative; cursor: default; }
    [data-tip]:hover::after {
      content: attr(data-tip);
      position: absolute; bottom: calc(100% + 6px); left: 50%; transform: translateX(-50%);
      background: var(--navy); color: var(--white);
      font-size: 11px; padding: 5px 10px; border-radius: 5px; white-space: nowrap;
      pointer-events: none; z-index: 200;
    }

    /* ── RESPONSIVE ── */
    @media (max-width: 600px) {
      .navbar { padding: 0 18px; }
      .container { padding: 24px 14px 48px; }
      .section-body { padding: 20px; }
      .section-header { padding: 18px 20px; }
      .page-header h1 { font-size: 28px; }
      .nav-time { display: none; }
    }
  </style>
  <script>
    // Embed-only Google Maps fallback (no JS API required)
    function insertEmbedMap(id, lat=14.6816, lng=77.6000, zoom=12) {
      var el = document.getElementById(id);
      if (!el) return;
      var src = 'https://www.google.com/maps?q=' + encodeURIComponent(lat + ',' + lng) + '&z=' + encodeURIComponent(zoom) + '&output=embed';
      el.innerHTML = '<iframe width="100%" height="100%" frameborder="0" style="border:0;border-radius:12px;" src="' + src + '" allowfullscreen></iframe>';
    }
    document.addEventListener('DOMContentLoaded', function(){
      ['live-map','officers-map','map'].forEach(function(id){ insertEmbedMap(id); });
    });
  </script>
</head>
<body>

<!-- ── NAVBAR ── -->
<nav class="navbar">
  <div class="nav-brand">
    <div class="nav-badge">PG</div>
    <div class="nav-title">POLY<span>GUARD</span> AI</div>
  </div>
  <div class="nav-right">
    <div class="nav-time" id="clock">--:--:--</div>
    <div style="display:flex;align-items:center;gap:8px;color:#94a3b8;font-size:13px;">
      <span class="pulse-dot"></span> System Active
    </div>
    <a class="btn-logout" href="../index.php" style="background:linear-gradient(135deg, #4ba1fd, #2268ff);">🏠 Home</a>
    <a class="btn-logout" href="logout.php">⏻ Logout</a>
  </div>
</nav>

<!-- ── MAIN ── -->
<div class="container">

  <!-- Page Header -->
  <div class="page-header">
    <div class="breadcrumb">
      <span>POLYGUARD AI</span>
      <span class="breadcrumb-sep">/</span>
      <span>ADMIN</span>
      <span class="breadcrumb-sep">/</span>
      <span style="color:var(--gold)">COMMAND CENTER</span>
    </div>
    <h1>Command Center</h1>
    <p>Police personnel management, duty assignments and real-time alert monitoring</p>
  </div>

  <!-- Flash Messages -->
  <?php if($success): ?>
  <div class="alert alert-success">
    <span class="alert-icon">✓</span>
    <span><?= htmlspecialchars($success) ?></span>
  </div>
  <?php endif; ?>
  <?php if($error): ?>
  <div class="alert alert-error">
    <span class="alert-icon">✕</span>
    <span><?= htmlspecialchars($error) ?></span>
  </div>
  <?php endif; ?>

  <!-- Stat Cards -->
  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-card-icon icon-blue">👮</div>
      <div class="stat-label">Total Officers</div>
      <div class="stat-value"><?= count($personnel) ?></div>
      <div class="stat-sub">Active personnel roster</div>
    </div>
    <div class="stat-card">
      <div class="stat-card-icon icon-green">📍</div>
      <div class="stat-label">Active Duties</div>
      <div class="stat-value"><?= count($duties) ?></div>
      <div class="stat-sub">Ongoing assignments</div>
    </div>
    <div class="stat-card">
      <div class="stat-card-icon icon-amber">🔔</div>
      <div class="stat-label">Recent Alerts</div>
      <div class="stat-value"><?= count($alerts) ?></div>
      <div class="stat-sub">Last 8 notifications</div>
    </div>
  </div>

  <!-- ADD PERSONNEL + ASSIGN DUTY (side by side) -->
  <div class="two-col">

    <!-- Add Personnel -->
    <div class="section-card">
      <div class="section-header">
        <div class="section-icon">👤</div>
        <div>
          <div class="section-title">Add Police Personnel</div>
          <div class="section-subtitle">Register a new officer to the system</div>
        </div>
      </div>
      <div class="section-body">
        <form method="post">
          <input type="hidden" name="action" value="add_personnel">
          <div class="form-grid">
            <div class="field">
              <label>Full Name</label>
              <input name="name" placeholder="e.g. Inspector Sharma" required>
            </div>
            <div class="field">
              <label>Username</label>
              <input name="username" placeholder="e.g. sharma_k" required>
            </div>
            <div class="field">
              <label>Rank / Designation</label>
              <input name="rank" placeholder="e.g. Sub-Inspector" required>
            </div>
            <div class="field">
              <label>Mobile Number</label>
              <input name="mobile" placeholder="+91 98765 43210" required>
            </div>
            <div class="field full">
              <label>Password</label>
              <input name="password" type="password" placeholder="Set a secure password" required>
            </div>
          </div>
          <button class="btn-submit" type="submit">＋ Register Officer</button>
        </form>
      </div>
    </div>

    <!-- Assign Duty -->
    <div class="section-card">
      <div class="section-header">
        <div class="section-icon">🗺</div>
        <div>
          <div class="section-title">Assign Duty</div>
          <div class="section-subtitle">Deploy officer with geo-fence parameters</div>
        </div>
      </div>
      <div class="section-body">
        <form method="post">
          <input type="hidden" name="action" value="assign_duty">
          <div class="form-grid">
            <div class="field full">
              <label>Officer</label>
              <select name="personnel_id" required>
                <option value="">— Select Officer —</option>
                <?php foreach($personnel as $p): ?>
                <option value="<?= $p['user_id'] ?>"><?= htmlspecialchars($p['name']) ?> · <?= htmlspecialchars($p['rank']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="field full">
              <label>Location Name</label>
              <input name="location_name" placeholder="e.g. Gate 7 — North Perimeter" required>
            </div>
            <div class="field">
              <label>Latitude</label>
              <input name="latitude" type="number" step="0.0000001" placeholder="17.3850" required>
            </div>
            <div class="field">
              <label>Longitude</label>
              <input name="longitude" type="number" step="0.0000001" placeholder="78.4867" required>
            </div>
            <div class="field">
              <label>Geo-fence Radius (m)</label>
              <input name="radius" type="number" min="20" max="50" value="30" required>
            </div>
            <div class="field">
              <!-- spacer for alignment -->
            </div>
            <div class="field">
              <label>Start Time</label>
              <input name="start_time" type="time" required>
            </div>
            <div class="field">
              <label>End Time</label>
              <input name="end_time" type="time" required>
            </div>
          </div>
          <button class="btn-submit" type="submit">⚑ Assign Duty</button>
        </form>
      </div>
    </div>
  </div>

  <!-- Active Duties Table -->
  <div class="section-card" style="animation-delay:.3s">
    <div class="section-header">
      <div class="section-icon">📋</div>
      <div>
        <div class="section-title">Active Duty Assignments</div>
        <div class="section-subtitle">All deployed officers and their geo-fence zones</div>
      </div>
    </div>
    <div class="section-body" style="padding:0;">
      <div class="table-wrapper">
        <?php if(count($duties) > 0): ?>
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Officer</th>
              <th>Location</th>
              <th>Coordinates</th>
              <th>Radius</th>
              <th>Shift</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach($duties as $d):
            $status = strtolower($d['status'] ?? 'active');
            $badge = match(true) {
              str_contains($status,'breach') => 'badge-breach',
              str_contains($status,'pending') => 'badge-pending',
              default => 'badge-active'
            };
            $label = ucfirst($d['status'] ?? 'Active');
          ?>
            <tr>
              <td>#<?= str_pad($d['duty_id'], 4, '0', STR_PAD_LEFT) ?></td>
              <td style="color:var(--text-main);font-weight:500"><?= htmlspecialchars($d['officer']) ?></td>
              <td style="color:var(--text-main)"><?= htmlspecialchars($d['location_name']) ?></td>
              <td>
                <span style="font-family:'JetBrains Mono',monospace;font-size:11.5px;color:var(--slate)">
                  <?= number_format($d['latitude'],6) ?>, <?= number_format($d['longitude'],6) ?>
                </span>
              </td>
              <td data-tip="Geo-fence boundary"><span style="font-weight:600;color:var(--navy)"><?= $d['radius'] ?>m</span></td>
              <td style="font-family:'JetBrains Mono',monospace;font-size:12px"><?= $d['start_time'] ?> → <?= $d['end_time'] ?></td>
              <td><span class="badge <?= $badge ?>"><?= htmlspecialchars($label) ?></span></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state">
          <div class="empty-state-icon">📭</div>
          No duty assignments found. Deploy officers using the form above.
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Recent Alerts Table -->
  <div class="section-card" style="animation-delay:.4s">
    <div class="section-header">
      <div class="section-icon" style="background:#7f1d1d">🚨</div>
      <div>
        <div class="section-title">Recent Alerts</div>
        <div class="section-subtitle">Latest 8 system-generated officer alerts</div>
      </div>
      <div style="margin-left:auto;display:flex;align-items:center;gap:8px;font-size:12px;color:var(--slate)">
        <span class="pulse-dot"></span> Live feed
      </div>
    </div>
    <div class="section-body" style="padding:0;">
      <div class="table-wrapper">
        <?php if(count($alerts) > 0): ?>
        <table>
          <thead>
            <tr>
              <th>Timestamp</th>
              <th>Officer</th>
              <th>Alert Type</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach($alerts as $r):
            $astatus = strtolower($r['status'] ?? '');
            $abadge  = str_contains($astatus,'resolve') ? 'badge-active' : (str_contains($astatus,'pending') ? 'badge-pending' : 'badge-breach');
          ?>
            <tr>
              <td><?= htmlspecialchars($r['alert_time']) ?></td>
              <td style="color:var(--text-main);font-weight:500"><?= htmlspecialchars($r['name']) ?></td>
              <td><span class="alert-type"><?= htmlspecialchars($r['alert_type']) ?></span></td>
              <td><span class="badge <?= $abadge ?>"><?= htmlspecialchars(ucfirst($r['status'])) ?></span></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state">
          <div class="empty-state-icon">🔕</div>
          No recent alerts. All officers are compliant.
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Live Location Map -->
  <div class="section-card" style="animation-delay:.5s">
    <div class="section-header">
      <div class="section-icon" style="background:#059669">📍</div>
      <div>
        <div class="section-title">Live Officer Locations</div>
        <div class="section-subtitle">Real-time GPS tracking of all active police personnel</div>
      </div>
      <div style="margin-left:auto;display:flex;align-items:center;gap:8px;font-size:12px;color:var(--slate)">
        <span class="pulse-dot"></span> Live tracking
      </div>
    </div>
    <div class="section-body">
      <div id="live-map" style="height:500px;width:100%;border-radius:12px;"></div>
    </div>
  </div>

</div><!-- /container -->

<script>
  // Live clock
  function tick() {
    const now = new Date();
    const h = String(now.getHours()).padStart(2,'0');
    const m = String(now.getMinutes()).padStart(2,'0');
    const s = String(now.getSeconds()).padStart(2,'0');
    document.getElementById('clock').textContent = `${h}:${m}:${s}`;
  }
  tick(); setInterval(tick, 1000);

  // Row entrance animation
  document.querySelectorAll('tbody tr').forEach((tr, i) => {
    tr.style.opacity = '0';
    tr.style.transform = 'translateY(8px)';
    tr.style.transition = `opacity .3s ease ${i * 0.04}s, transform .3s ease ${i * 0.04}s`;
    requestAnimationFrame(() => {
      tr.style.opacity = '1';
      tr.style.transform = 'translateY(0)';
    });
  });

  // Animated stat counters
  document.querySelectorAll('.stat-value').forEach(el => {
    const target = parseInt(el.textContent, 10);
    if (isNaN(target) || target === 0) return;
    let start = 0;
    const duration = 900;
    const startTime = performance.now();
    function frame(now) {
      const elapsed = now - startTime;
      const progress = Math.min(elapsed / duration, 1);
      const eased = 1 - Math.pow(1 - progress, 3);
      el.textContent = Math.round(eased * target);
      if (progress < 1) requestAnimationFrame(frame);
    }
    requestAnimationFrame(frame);
  });

  // Auto-dismiss flash alerts
  document.querySelectorAll('.alert').forEach(a => {
    setTimeout(() => {
      a.style.transition = 'opacity .5s ease, transform .5s ease, max-height .5s ease';
      a.style.opacity = '0'; a.style.transform = 'translateX(-10px)';
      setTimeout(() => a.remove(), 500);
    }, 4000);
  });

  // Live Location Map
  let map;
  let markers = [];
  let infoWindows = [];

  function initMap() {
    // Default center (can be changed to city center)
    const center = { lat: 13.0827, lng: 80.2707 }; // Chennai coordinates

    map = new google.maps.Map(document.getElementById('live-map'), {
      center: center,
      zoom: 12,
      styles: [
        { elementType: 'geometry', stylers: [{ color: '#f5f5f5' }] },
        { elementType: 'labels.text.fill', stylers: [{ color: '#616161' }] },
        { elementType: 'labels.text.stroke', stylers: [{ color: '#f5f5f5' }] },
        { featureType: 'road', elementType: 'geometry', stylers: [{ color: '#ffffff' }] },
        { featureType: 'water', elementType: 'geometry', stylers: [{ color: '#c9c9c9' }] },
        { featureType: 'poi.park', elementType: 'geometry', stylers: [{ color: '#e5e5e5' }] },
      ],
      mapTypeControl: false,
      streetViewControl: false,
      fullscreenControl: true
    });

    loadLocations();
    // Update every 30 seconds
    setInterval(loadLocations, 30000);
  }

  function loadLocations() {
    const apiKey = '<?= SecurityMiddleware::generateAPIKey($_SESSION["user"]["user_id"], $pdo) ?>';

    fetch('../backend/api/locations?api_key=' + apiKey)
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          updateMapMarkers(data.data);
        }
      })
      .catch(err => console.log('Location update failed:', err));
  }

  function updateMapMarkers(locations) {
    // Clear existing markers
    markers.forEach(marker => marker.setMap(null));
    infoWindows.forEach(iw => iw.close());
    markers = [];
    infoWindows = [];

    locations.forEach(officer => {
      const position = { lat: officer.latitude, lng: officer.longitude };

      // Choose icon based on status
      let iconUrl = 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(`
        <svg width="40" height="40" viewBox="0 0 40 40" xmlns="http://www.w3.org/2000/svg">
          <circle cx="20" cy="20" r="18" fill="${officer.status === 'inside' ? '#10b981' : '#ef4444'}" stroke="white" stroke-width="3"/>
          <text x="20" y="25" text-anchor="middle" fill="white" font-size="12" font-weight="bold">👮</text>
        </svg>
      `);

      const marker = new google.maps.Marker({
        position: position,
        map: map,
        title: officer.name,
        icon: {
          url: iconUrl,
          scaledSize: new google.maps.Size(40, 40)
        }
      });

      const infoWindow = new google.maps.InfoWindow({
        content: `
          <div style="font-family:Arial,sans-serif;max-width:200px;">
            <h4 style="margin:0 0 8px 0;color:#1f2937;">${officer.name}</h4>
            <p style="margin:4px 0;color:#6b7280;"><strong>Rank:</strong> ${officer.rank}</p>
            <p style="margin:4px 0;color:#6b7280;"><strong>Mobile:</strong> ${officer.mobile}</p>
            <p style="margin:4px 0;color:#6b7280;"><strong>Status:</strong> 
              <span style="color:${officer.status === 'inside' ? '#10b981' : '#ef4444'}">${officer.status}</span>
            </p>
            ${officer.duty ? `
              <p style="margin:4px 0;color:#6b7280;"><strong>Duty:</strong> ${officer.duty.location_name}</p>
              <p style="margin:4px 0;color:#6b7280;"><strong>Time:</strong> ${officer.duty.start_time} - ${officer.duty.end_time}</p>
            ` : '<p style="margin:4px 0;color:#6b7280;"><strong>Duty:</strong> No active duty</p>'}
            <p style="margin:4px 0;font-size:12px;color:#9ca3af;">Updated: ${officer.timestamp}</p>
          </div>
        `
      });

      marker.addListener('click', () => {
        infoWindows.forEach(iw => iw.close());
        infoWindow.open(map, marker);
      });

      markers.push(marker);
      infoWindows.push(infoWindow);
    });

    // Fit map to show all markers
    if (locations.length > 0) {
      const bounds = new google.maps.LatLngBounds();
      locations.forEach(officer => {
        bounds.extend({ lat: officer.latitude, lng: officer.longitude });
      });
      map.fitBounds(bounds);
    }
  }

    // If Google Maps fails to load (auth/CSP issues), show iframe fallback
    function showMapFallback(elementId, lat, lng, zoom=12) {
      const el = document.getElementById(elementId);
      if (!el) return;
      const src = 'https://www.google.com/maps?q=' + encodeURIComponent(lat + ',' + lng) + '&z=' + encodeURIComponent(zoom) + '&output=embed';
      el.innerHTML = `<iframe width="100%" height="100%" frameborder="0" style="border:0;border-radius:12px;" src="${src}" allowfullscreen></iframe>`;
    }

    // Runtime check: if google.maps isn't available after load, use fallback
    setTimeout(() => {
      if (typeof google === 'undefined' || !google.maps) {
        // default center (city)
        showMapFallback('live-map', 13.0827, 80.2707, 11);
      }
    }, 2500);
</script>
</body>
</html>