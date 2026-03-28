<?php
require_once __DIR__ . '/../backend/db.php';
require_once __DIR__ . '/../backend/auth.php';
require_once __DIR__ . '/../backend/security.php';
// Load central config
$POLYGUARD_CONFIG = require_once __DIR__ . '/../backend/config.php';
require_once __DIR__ . '/../backend/advanced_analytics.php';
requireRole('control');
initSecurityTables($pdo);

$locations = $pdo->query('SELECT lt.personnel_id, u.name, u.rank, lt.latitude, lt.longitude, lt.status, lt.timestamp FROM location_tracking lt JOIN users u ON lt.personnel_id=u.user_id WHERE u.role="police" AND lt.id IN (SELECT MAX(id) FROM location_tracking GROUP BY personnel_id)')->fetchAll();
$alerts = $pdo->query('SELECT a.*,u.name,u.rank FROM alerts a JOIN users u ON a.personnel_id=u.user_id ORDER BY alert_time DESC LIMIT 10')->fetchAll();

$activeCount  = $pdo->query("SELECT COUNT(*) FROM duty_assignments WHERE status='active'")->fetchColumn();
$alertCount   = $pdo->query("SELECT COUNT(*) FROM alerts WHERE alert_time >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetchColumn();
$breachCount  = $pdo->query("SELECT COUNT(*) FROM alerts WHERE alert_time >= CURDATE() AND alert_type='exit'")->fetchColumn();
$compAvg      = round($pdo->query("SELECT AVG(compliance_score) FROM compliance")->fetchColumn() ?? 0, 1);
$onDutyNow    = count($locations);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Control Room — POLYGUARD AI</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600;9..40,700&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>
      // Insert embed iframes for maps (no Maps JS API required)
      function insertEmbedMap(id, lat=14.6816, lng=77.6000, zoom=12) {
        var el = document.getElementById(id);
        if (!el) return;
        var src = 'https://www.google.com/maps?q=' + encodeURIComponent(lat + ',' + lng) + '&z=' + encodeURIComponent(zoom) + '&output=embed';
        el.innerHTML = '<iframe width="100%" height="100%" frameborder="0" style="border:0;border-radius:12px;" src="' + src + '" allowfullscreen></iframe>';
      }
      document.addEventListener('DOMContentLoaded', function(){ ['live-map','officers-map','map'].forEach(function(id){ insertEmbedMap(id); }); });
    </script>
  <style>
    /* ═══════════════════════════════════════════
       TOKENS — inherits same system as admin
    ═══════════════════════════════════════════ */
    :root {
      --navy-950:  #050d1a;
      --navy-900:  #0b1628;
      --navy-800:  #0f1f3d;
      --navy-700:  #112240;
      --navy-600:  #1a3358;
      --navy-500:  #234070;
      --gold-500:  #c9a84c;
      --gold-400:  #d4b466;
      --gold-300:  #e8c97a;
      --gold-200:  #f5e0a0;
      --gold-100:  #fdf3d7;
      --green-500: #10b981;
      --green-400: #34d399;
      --red-500:   #ef4444;
      --red-400:   #f87171;
      --blue-600:  #2563eb;
      --blue-500:  #3b82f6;
      --blue-300:  #93c5fd;
      --blue-200:  #bfdbfe;
      --amber-500: #f59e0b;
      --purple-500:#8b5cf6;
      --white:     #ffffff;
      --gray-400:  #94a3b8;
      --gray-500:  #64748b;
      --r-sm: 8px;  --r-md: 14px;
      --r-lg: 20px; --r-xl: 26px;
      --sh-lg:  0 12px 48px rgba(11,22,40,.18);
      --sh-gold: 0 8px 28px rgba(201,168,76,.22);
      --sh-blue: 0 8px 28px rgba(37,99,235,.22);
    }

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  // Fallback to iframe if Maps JS blocked
  function showMapFallbackControl(elementId, lat=14.6816, lng=77.6000, zoom=12) {
    const el = document.getElementById(elementId);
    if (!el) return;
    const src = 'https://www.google.com/maps?q=' + encodeURIComponent(lat + ',' + lng) + '&z=' + encodeURIComponent(zoom) + '&output=embed';
    el.innerHTML = `<iframe width="100%" height="100%" frameborder="0" style="border:0;border-radius:10px;" src="${src}" allowfullscreen></iframe>`;
  }

  setTimeout(() => {
    if (typeof google === 'undefined' || !google.maps) {
      showMapFallbackControl('map');
    }
  }, 2500);
    html { scroll-behavior: smooth; }
    body {
      font-family: 'DM Sans', sans-serif;
      background: var(--navy-950);
      color: var(--white);
      min-height: 100vh;
      overflow-x: hidden;
    }
    body::before {
      content: '';
      position: fixed; inset: 0; z-index: 0; pointer-events: none;
      background-image:
        radial-gradient(ellipse 70% 50% at 5% 15%, rgba(37,99,235,.04) 0%, transparent 60%),
        radial-gradient(ellipse 60% 70% at 95% 85%, rgba(201,168,76,.04) 0%, transparent 60%);
    }

    /* ─── NAVBAR ─── */
    .navbar {
      position: sticky; top: 0; z-index: 500;
      height: 66px; padding: 0 40px;
      background: rgba(5,13,26,.92);
      backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
      display: flex; align-items: center; justify-content: space-between;
      border-bottom: 1px solid rgba(59,130,246,.12);
    }
    .navbar::after {
      content: '';
      position: absolute; bottom: 0; left: 0; right: 0; height: 1px;
      background: linear-gradient(90deg,
        transparent 0%, var(--blue-500) 30%,
        var(--blue-300) 50%, var(--blue-500) 70%, transparent 100%);
    }
    .nav-brand { display: flex; align-items: center; gap: 13px; }
    .nav-logo {
      width: 40px; height: 40px; border-radius: 10px;
      background: linear-gradient(135deg, var(--blue-600), var(--blue-500));
      display: grid; place-items: center;
      font-family: 'Bebas Neue'; font-size: 16px; color: var(--white);
      box-shadow: var(--sh-blue); flex-shrink: 0;
    }
    .nav-wordmark { font-family: 'Bebas Neue'; font-size: 22px; letter-spacing: 3px; color: var(--white); }
    .nav-wordmark em { color: var(--blue-300); font-style: normal; }
    .nav-tag { font-family: 'JetBrains Mono'; font-size: 10px; letter-spacing: 2px; color: var(--gray-400); }
    .nav-right { display: flex; align-items: center; gap: 14px; }
    .nav-clock { font-family: 'JetBrains Mono'; font-size: 13px; color: var(--gray-400); }
    .nav-live { display: flex; align-items: center; gap: 7px; font-size: 12px; color: var(--gray-400); }
    .pulse { width: 8px; height: 8px; border-radius: 50%; background: var(--green-500); position: relative; }
    .pulse::after {
      content: ''; position: absolute; top: -3px; left: -3px;
      width: 14px; height: 14px; border-radius: 50%;
      background: rgba(16,185,129,.3); animation: ripple 2s ease infinite;
    }
    .pulse-blue { background: var(--blue-500); }
    .pulse-blue::after { background: rgba(59,130,246,.3); }
    .nav-user-pill {
      display: flex; align-items: center; gap: 9px;
      background: rgba(255,255,255,.05);
      border: 1px solid rgba(59,130,246,.2);
      padding: 5px 14px 5px 7px; border-radius: 30px;
    }
    .nav-avatar {
      width: 30px; height: 30px; border-radius: 50%;
      background: linear-gradient(135deg, var(--blue-600), var(--blue-500));
      display: grid; place-items: center;
      font-family: 'Bebas Neue'; font-size: 13px; color: #fff;
      border: 1.5px solid rgba(59,130,246,.5);
    }
    .nav-user-name { font-size: 13px; font-weight: 600; color: #e2e8f0; }
    .nav-user-role { font-size: 10px; color: var(--blue-300); font-family: 'JetBrains Mono'; }
    .btn-nav {
      display: inline-flex; align-items: center; gap: 7px;
      padding: 9px 18px; border-radius: 30px;
      font-size: 13px; font-weight: 600;
      text-decoration: none; border: none; cursor: pointer;
      font-family: 'DM Sans'; transition: all .2s;
    }
    .btn-home {
      background: rgba(201,168,76,.1); border: 1px solid rgba(201,168,76,.3); color: var(--gold-300);
    }
    .btn-home:hover { background: var(--gold-500); color: var(--navy-900); transform: translateY(-1px); }
    .btn-logout {
      background: rgba(239,68,68,.1); border: 1px solid rgba(239,68,68,.25); color: #fca5a5;
    }
    .btn-logout:hover { background: var(--red-500); color: #fff; transform: translateY(-1px); }

    /* ─── LAYOUT ─── */
    .page { max-width: 1440px; margin: 0 auto; padding: 36px 28px 72px; position: relative; z-index: 1; }

    /* ─── HERO BAR ─── */
    .hero-bar {
      background: linear-gradient(135deg, var(--navy-900) 0%, #0e1f45 55%, var(--navy-700) 100%);
      border: 1px solid rgba(59,130,246,.15);
      border-radius: var(--r-xl);
      padding: 30px 36px;
      margin-bottom: 28px;
      display: flex; align-items: center; justify-content: space-between; gap: 24px;
      position: relative; overflow: hidden;
      box-shadow: 0 12px 48px rgba(37,99,235,.12);
      animation: fadeDown .5s ease both;
    }
    .hero-bar::before {
      content: '';
      position: absolute; top: -60px; right: -60px;
      width: 260px; height: 260px; border-radius: 50%;
      background: radial-gradient(circle, rgba(59,130,246,.1) 0%, transparent 65%);
    }
    .hero-bar::after {
      content: '';
      position: absolute; top: 0; left: 0; right: 0; height: 2px;
      background: linear-gradient(90deg, transparent, var(--blue-500), var(--blue-300), var(--blue-500), transparent);
    }
    .hero-left { position: relative; z-index: 1; }
    .hero-breadcrumb {
      font-family: 'JetBrains Mono'; font-size: 10px; letter-spacing: 2px;
      color: rgba(255,255,255,.35); margin-bottom: 8px; display: flex; align-items: center; gap: 8px;
    }
    .hero-breadcrumb span { color: var(--blue-300); }
    .hero-title { font-family: 'Bebas Neue'; font-size: 42px; letter-spacing: 4px; color: var(--white); line-height: 1; }
    .hero-sub { font-size: 13.5px; color: rgba(255,255,255,.45); margin-top: 6px; }
    .hero-right { display: flex; align-items: center; gap: 12px; flex-shrink: 0; position: relative; z-index: 1; }
    .role-badge {
      padding: 10px 22px; border-radius: 30px;
      font-family: 'Bebas Neue'; font-size: 14px; letter-spacing: 2px;
      background: rgba(59,130,246,.12);
      border: 1.5px solid rgba(59,130,246,.35);
      color: var(--blue-300);
    }

    /* ─── STAT CARDS ─── */
    .stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 18px; margin-bottom: 28px; }
    .stat-card {
      background: rgba(255,255,255,.04);
      border: 1px solid rgba(255,255,255,.08);
      border-radius: var(--r-lg);
      padding: 24px 22px;
      position: relative; overflow: hidden;
      transition: transform .25s, border-color .25s, box-shadow .25s;
      animation: fadeUp .55s ease both;
      cursor: default;
    }
    .stat-card:hover { transform: translateY(-5px); border-color: rgba(59,130,246,.25); box-shadow: 0 16px 48px rgba(0,0,0,.3); }
    .stat-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 2px; }
    .sc-blue::before   { background: linear-gradient(90deg, transparent, var(--blue-500), transparent); }
    .sc-green::before  { background: linear-gradient(90deg, transparent, var(--green-500), transparent); }
    .sc-red::before    { background: linear-gradient(90deg, transparent, var(--red-500), transparent); }
    .sc-gold::before   { background: linear-gradient(90deg, transparent, var(--gold-500), transparent); }
    .sc-purple::before { background: linear-gradient(90deg, transparent, var(--purple-500), transparent); }
    .stat-icon {
      width: 50px; height: 50px; border-radius: var(--r-md);
      display: grid; place-items: center; font-size: 24px;
      margin-bottom: 16px;
    }
    .si-blue   { background: rgba(59,130,246,.12); border: 1px solid rgba(59,130,246,.2); }
    .si-green  { background: rgba(16,185,129,.12); border: 1px solid rgba(16,185,129,.2); }
    .si-red    { background: rgba(239,68,68,.12);  border: 1px solid rgba(239,68,68,.2); }
    .si-gold   { background: rgba(201,168,76,.12); border: 1px solid rgba(201,168,76,.2); }
    .si-purple { background: rgba(139,92,246,.12); border: 1px solid rgba(139,92,246,.2); }
    .stat-label { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 1.2px; color: var(--gray-400); margin-bottom: 4px; }
    .stat-value { font-family: 'Bebas Neue'; font-size: 48px; letter-spacing: 1px; line-height: 1; }
    .sv-blue   { color: var(--blue-300); }
    .sv-green  { color: var(--green-400); }
    .sv-red    { color: var(--red-400); }
    .sv-gold   { color: var(--gold-400); }
    .sv-purple { color: #c4b5fd; }
    .stat-trend { font-size: 12px; color: rgba(255,255,255,.35); margin-top: 6px; font-family: 'JetBrains Mono'; }

    /* ─── MAIN GRID ─── */
    .main-grid {
      display: grid;
      grid-template-columns: 1fr 380px;
      gap: 22px;
      margin-bottom: 24px;
      align-items: start;
    }
    @media (max-width: 1100px) { .main-grid { grid-template-columns: 1fr; } }

    /* ─── SECTION CARD ─── */
    .section-card {
      background: rgba(255,255,255,.03);
      border: 1px solid rgba(255,255,255,.07);
      border-radius: var(--r-xl);
      overflow: hidden;
      box-shadow: var(--sh-lg);
      animation: fadeUp .55s ease both;
    }
    .sc-head {
      display: flex; align-items: center; gap: 14px;
      padding: 20px 26px;
      border-bottom: 1px solid rgba(255,255,255,.06);
      background: rgba(5,13,26,.4);
    }
    .sc-head-icon {
      width: 38px; height: 38px; border-radius: 9px;
      display: grid; place-items: center; font-size: 17px;
      background: rgba(59,130,246,.12); border: 1px solid rgba(59,130,246,.2);
      flex-shrink: 0;
    }
    .sc-head-title { font-size: 15px; font-weight: 600; color: var(--white); }
    .sc-head-sub   { font-size: 12px; color: rgba(255,255,255,.4); margin-top: 2px; }
    .sc-head-badge {
      margin-left: auto;
      display: flex; align-items: center; gap: 7px;
      font-family: 'JetBrains Mono'; font-size: 11px; color: rgba(255,255,255,.4);
    }
    .sc-body { padding: 22px; }
    .sc-body-0 { padding: 0; }

    /* ─── MAP ─── */
    #live-map {
      height: 480px; width: 100%;
      border-radius: 0;
    }
    .map-footer {
      padding: 14px 24px;
      background: rgba(5,13,26,.5);
      border-top: 1px solid rgba(255,255,255,.05);
      display: flex; gap: 20px; flex-wrap: wrap;
    }
    .mf-stat { display: flex; align-items: center; gap: 8px; font-size: 12px; color: rgba(255,255,255,.5); }
    .mf-dot { width: 8px; height: 8px; border-radius: 50%; }

    /* ─── SIDE PANEL ─── */
    .side-stack { display: flex; flex-direction: column; gap: 18px; }

    /* ─── ALERT FEED ─── */
    .alert-feed { display: flex; flex-direction: column; }
    .af-item {
      display: flex; align-items: flex-start; gap: 12px;
      padding: 14px 22px; border-bottom: 1px solid rgba(255,255,255,.05);
      transition: background .15s;
    }
    .af-item:last-child { border-bottom: none; }
    .af-item:hover { background: rgba(255,255,255,.03); }
    .af-icon { width: 34px; height: 34px; border-radius: 9px; display: grid; place-items: center; font-size: 15px; flex-shrink: 0; }
    .ai-red    { background: rgba(239,68,68,.12); border: 1px solid rgba(239,68,68,.2); }
    .ai-amber  { background: rgba(245,158,11,.12); border: 1px solid rgba(245,158,11,.2); }
    .ai-blue   { background: rgba(59,130,246,.12); border: 1px solid rgba(59,130,246,.2); }
    .af-body { flex: 1; }
    .af-name { font-size: 13px; font-weight: 500; color: var(--white); }
    .af-rank { font-size: 11px; color: rgba(255,255,255,.4); }
    .af-meta { margin-top: 5px; }
    .af-time { margin-left: auto; font-family: 'JetBrains Mono'; font-size: 11px; color: rgba(255,255,255,.35); white-space: nowrap; flex-shrink: 0; }

    /* ─── OFFICER LIST ─── */
    .officer-list { display: flex; flex-direction: column; }
    .ol-item {
      display: flex; align-items: center; gap: 12px;
      padding: 13px 22px; border-bottom: 1px solid rgba(255,255,255,.05);
      transition: background .15s;
    }
    .ol-item:last-child { border-bottom: none; }
    .ol-item:hover { background: rgba(255,255,255,.03); }
    .ol-avatar {
      width: 36px; height: 36px; border-radius: 10px;
      display: grid; place-items: center;
      font-family: 'Bebas Neue'; font-size: 14px; color: var(--white);
      flex-shrink: 0;
    }
    .oa-inside  { background: rgba(16,185,129,.2); border: 1px solid rgba(16,185,129,.3); }
    .oa-outside { background: rgba(239,68,68,.2);  border: 1px solid rgba(239,68,68,.3); }
    .ol-info { flex: 1; }
    .ol-name { font-size: 13px; font-weight: 500; color: var(--white); }
    .ol-rank { font-size: 11px; color: rgba(255,255,255,.4); }
    .ol-status { flex-shrink: 0; }

    /* ─── ALERTS TABLE FULL WIDTH ─── */
    .tbl-wrap { overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; font-size: 13.5px; }
    thead tr { background: rgba(59,130,246,.06); border-bottom: 1px solid rgba(59,130,246,.15); }
    thead th {
      padding: 13px 18px; text-align: left;
      font-size: 10.5px; font-weight: 700;
      color: var(--blue-300); text-transform: uppercase; letter-spacing: 1.2px; white-space: nowrap;
    }
    tbody tr { border-bottom: 1px solid rgba(255,255,255,.05); transition: background .15s; }
    tbody tr:last-child { border-bottom: none; }
    tbody tr:hover { background: rgba(255,255,255,.03); }
    tbody td { padding: 13px 18px; color: rgba(255,255,255,.65); vertical-align: middle; }
    .td-name { color: var(--white) !important; font-weight: 500; }
    .td-mono { font-family: 'JetBrains Mono'; font-size: 11.5px; color: var(--gray-400); }

    /* ─── BADGES ─── */
    .badge {
      display: inline-flex; align-items: center; gap: 5px;
      padding: 4px 10px; border-radius: 20px;
      font-size: 11px; font-weight: 600; letter-spacing: .3px; white-space: nowrap;
    }
    .badge::before { content: ''; width: 5px; height: 5px; border-radius: 50%; }
    .b-active  { background: rgba(16,185,129,.15); color: #6ee7b7; border: 1px solid rgba(16,185,129,.25); }
    .b-active::before  { background: var(--green-500); }
    .b-pending { background: rgba(245,158,11,.15); color: #fcd34d; border: 1px solid rgba(245,158,11,.25); }
    .b-pending::before { background: var(--amber-500); }
    .b-breach  { background: rgba(239,68,68,.15);  color: #fca5a5; border: 1px solid rgba(239,68,68,.25); }
    .b-breach::before  { background: var(--red-500); }
    .alert-pill {
      font-family: 'JetBrains Mono'; font-size: 11px; font-weight: 700; padding: 3px 9px; border-radius: 5px;
    }
    .ap-exit    { background: rgba(239,68,68,.15);  color: #fca5a5; border: 1px solid rgba(239,68,68,.2); }
    .ap-late    { background: rgba(245,158,11,.15); color: #fcd34d; border: 1px solid rgba(245,158,11,.2); }
    .ap-absence { background: rgba(59,130,246,.15); color: #93c5fd; border: 1px solid rgba(59,130,246,.2); }

    /* ─── EMPTY ─── */
    .empty { text-align: center; padding: 44px 24px; color: rgba(255,255,255,.25); }
    .empty-icon { font-size: 38px; opacity: .3; margin-bottom: 10px; }

    /* ─── COMPLIANCE BAR ─── */
    .comp-list { display: flex; flex-direction: column; gap: 12px; }
    .cl-item {}
    .cl-head { display: flex; justify-content: space-between; margin-bottom: 6px; }
    .cl-name  { font-size: 12.5px; color: rgba(255,255,255,.6); }
    .cl-score { font-family: 'JetBrains Mono'; font-size: 12px; font-weight: 700; color: var(--blue-300); }
    .cl-bar   { height: 5px; background: rgba(255,255,255,.08); border-radius: 3px; overflow: hidden; }
    .cl-fill  { height: 100%; border-radius: 3px; transition: width 1.5s cubic-bezier(.25,1,.5,1); }

    /* ─── ANIMATIONS ─── */
    @keyframes fadeDown { from { opacity:0; transform:translateY(-14px); } to { opacity:1; transform:translateY(0); } }
    @keyframes fadeUp   { from { opacity:0; transform:translateY(18px); } to { opacity:1; transform:translateY(0); } }
    @keyframes ripple   { 0%{transform:scale(1);opacity:.6;} 70%{transform:scale(2.2);opacity:0;} 100%{opacity:0;} }

    ::-webkit-scrollbar { width: 5px; height: 5px; }
    ::-webkit-scrollbar-track { background: var(--navy-950); }
    ::-webkit-scrollbar-thumb { background: rgba(59,130,246,.25); border-radius: 3px; }

    @media (max-width: 640px) {
      .navbar { padding: 0 16px; }
      .page   { padding: 20px 14px 48px; }
      .hero-bar { padding: 22px 20px; }
      .hero-title { font-size: 30px; }
      .hero-right { display: none; }
      .nav-clock { display: none; }
    }
  </style>
</head>
<body>

<!-- ════ NAVBAR ════ -->
<nav class="navbar">
  <div class="nav-brand">
    <div class="nav-logo">PG</div>
    <div>
      <div class="nav-wordmark">POLY<em>GUARD</em> AI</div>
      <div class="nav-tag">CONTROL ROOM DASHBOARD</div>
    </div>
  </div>
  <div class="nav-right">
    <div class="nav-clock" id="clock">--:--:--</div>
    <div class="nav-live"><span class="pulse pulse-blue"></span> Ops Live</div>
    <div class="nav-user-pill">
      <div class="nav-avatar">C</div>
      <div>
        <div class="nav-user-name">Control Room</div>
        <div class="nav-user-role">CONTROL</div>
      </div>
    </div>
    <a href="../index.php" class="btn-nav btn-home">🏠 Home</a>
    <a href="logout.php"   class="btn-nav btn-logout">⏻ Logout</a>
  </div>
</nav>

<!-- ════ PAGE ════ -->
<div class="page">

  <!-- HERO BAR -->
  <div class="hero-bar">
    <div class="hero-left">
      <div class="hero-breadcrumb">
        POLYGUARD AI <span style="opacity:.3;margin:0 4px">/</span>
        CONTROL <span style="opacity:.3;margin:0 4px">/</span>
        <span>OPERATIONS CENTER</span>
      </div>
      <div class="hero-title">Operations Center</div>
      <div class="hero-sub">Live officer tracking · Real-time alerts · Compliance monitoring · <?= date('l, d M Y') ?></div>
    </div>
    <div class="hero-right">
      <div class="role-badge">🎯 CONTROL ROOM</div>
    </div>
  </div>

  <!-- STAT CARDS -->
  <div class="stats-row">
    <div class="stat-card sc-blue" style="animation-delay:.04s">
      <div class="stat-icon si-blue">📡</div>
      <div class="stat-label">Tracking Live</div>
      <div class="stat-value sv-blue" data-count="<?= $onDutyNow ?>"><?= $onDutyNow ?></div>
      <div class="stat-trend">Officers visible on map</div>
    </div>
    <div class="stat-card sc-green" style="animation-delay:.08s">
      <div class="stat-icon si-green">📍</div>
      <div class="stat-label">Active Duties</div>
      <div class="stat-value sv-green" data-count="<?= $activeCount ?>"><?= $activeCount ?></div>
      <div class="stat-trend">Deployed right now</div>
    </div>
    <div class="stat-card sc-red" style="animation-delay:.12s">
      <div class="stat-icon si-red">🚨</div>
      <div class="stat-label">Alerts (24h)</div>
      <div class="stat-value sv-red" data-count="<?= $alertCount ?>"><?= $alertCount ?></div>
      <div class="stat-trend">Last 24 hours</div>
    </div>
    <div class="stat-card sc-red" style="animation-delay:.16s">
      <div class="stat-icon si-red">⚠️</div>
      <div class="stat-label">Breaches Today</div>
      <div class="stat-value sv-red" data-count="<?= $breachCount ?>"><?= $breachCount ?></div>
      <div class="stat-trend">Zone exit violations</div>
    </div>
    <div class="stat-card sc-gold" style="animation-delay:.20s">
      <div class="stat-icon si-gold">📊</div>
      <div class="stat-label">Compliance Avg</div>
      <div class="stat-value sv-gold"><?= $compAvg ?><span style="font-size:20px">%</span></div>
      <div class="stat-trend">System-wide score</div>
    </div>
  </div>

  <!-- MAIN GRID: MAP + SIDE PANELS -->
  <div class="main-grid">

    <!-- MAP CARD -->
    <div class="section-card" style="animation-delay:.1s">
      <div class="sc-head">
        <div class="sc-head-icon" style="background:rgba(16,185,129,.12);border-color:rgba(16,185,129,.2)">📍</div>
        <div>
          <div class="sc-head-title">Live Officer Map</div>
          <div class="sc-head-sub">Real-time GPS tracking — auto-refreshes every 30s</div>
        </div>
        <div class="sc-head-badge"><span class="pulse"></span> GPS Live</div>
      </div>
      <div id="live-map"></div>
      <div class="map-footer">
        <div class="mf-stat"><div class="mf-dot" style="background:var(--green-500)"></div><?= $onDutyNow ?> tracked</div>
        <div class="mf-stat"><div class="mf-dot" style="background:var(--red-500)"></div><?= $breachCount ?> breach<?= $breachCount != 1 ? 'es' : '' ?></div>
        <div class="mf-stat"><div class="mf-dot" style="background:var(--amber-500)"></div>Updated <?= date('H:i') ?></div>
      </div>
    </div>

    <!-- SIDE PANELS -->
    <div class="side-stack">

      <!-- Officers online -->
      <div class="section-card" style="animation-delay:.14s">
        <div class="sc-head">
          <div class="sc-head-icon">👮</div>
          <div>
            <div class="sc-head-title">Officers Online</div>
            <div class="sc-head-sub"><?= count($locations) ?> tracked</div>
          </div>
        </div>
        <div class="sc-body-0 officer-list">
          <?php if(empty($locations)): ?>
            <div class="empty"><div class="empty-icon">📡</div><div style="font-size:13px">No officers tracked</div></div>
          <?php else: foreach(array_slice($locations,0,6) as $loc):
            $inside = $loc['status'] === 'inside';
          ?>
            <div class="ol-item">
              <div class="ol-avatar <?= $inside ? 'oa-inside' : 'oa-outside' ?>">
                <?= strtoupper(substr($loc['name'],0,1)) ?>
              </div>
              <div class="ol-info">
                <div class="ol-name"><?= htmlspecialchars($loc['name']) ?></div>
                <div class="ol-rank"><?= htmlspecialchars($loc['rank']) ?></div>
              </div>
              <div class="ol-status">
                <span class="badge <?= $inside ? 'b-active' : 'b-breach' ?>"><?= $inside ? 'Inside' : 'Breach' ?></span>
              </div>
            </div>
          <?php endforeach; endif; ?>
        </div>
      </div>

      <!-- Compliance snapshot -->
      <?php
        $compData = $pdo->query("SELECT u.name, AVG(c.compliance_score) as avg_score FROM compliance c JOIN users u ON c.personnel_id=u.user_id GROUP BY c.personnel_id ORDER BY avg_score DESC LIMIT 5")->fetchAll();
      ?>
      <div class="section-card" style="animation-delay:.18s">
        <div class="sc-head">
          <div class="sc-head-icon" style="background:rgba(201,168,76,.12);border-color:rgba(201,168,76,.2)">📊</div>
          <div>
            <div class="sc-head-title">Compliance Leaders</div>
            <div class="sc-head-sub">Top 5 officers</div>
          </div>
        </div>
        <div class="sc-body">
          <?php if(empty($compData)): ?>
            <div class="empty" style="padding:24px"><div class="empty-icon">📊</div></div>
          <?php else: ?>
          <div class="comp-list">
            <?php foreach($compData as $c):
              $score = round($c['avg_score'], 1);
              $color = $score >= 80 ? 'var(--green-500)' : ($score >= 50 ? 'var(--amber-500)' : 'var(--red-500)');
            ?>
            <div class="cl-item">
              <div class="cl-head">
                <span class="cl-name"><?= htmlspecialchars($c['name']) ?></span>
                <span class="cl-score"><?= $score ?>%</span>
              </div>
              <div class="cl-bar">
                <div class="cl-fill" style="width:<?= $score ?>%;background:<?= $color ?>"></div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </div>

  <!-- RECENT ALERTS FULL TABLE -->
  <div class="section-card" style="animation-delay:.26s;margin-bottom:0">
    <div class="sc-head">
      <div class="sc-head-icon" style="background:rgba(239,68,68,.12);border-color:rgba(239,68,68,.2)">🚨</div>
      <div>
        <div class="sc-head-title">Recent Alerts</div>
        <div class="sc-head-sub">Latest <?= count($alerts) ?> notifications</div>
      </div>
      <div class="sc-head-badge"><span class="pulse"></span> Live Feed</div>
    </div>
    <div class="sc-body-0">
      <div class="tbl-wrap">
        <?php if(count($alerts) > 0): ?>
        <table>
          <thead>
            <tr>
              <th>Time</th>
              <th>Officer</th>
              <th>Rank</th>
              <th>Alert Type</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach($alerts as $a):
            $astatus = strtolower($a['status'] ?? '');
            $ab = str_contains($astatus,'resolve') ? 'b-active' : (str_contains($astatus,'pending') ? 'b-pending' : 'b-breach');
            $atype = strtolower($a['alert_type']);
            $apill = str_contains($atype,'exit') ? 'ap-exit' : (str_contains($atype,'late') ? 'ap-late' : 'ap-absence');
          ?>
            <tr>
              <td class="td-mono"><?= htmlspecialchars($a['alert_time']) ?></td>
              <td class="td-name"><?= htmlspecialchars($a['name']) ?></td>
              <td class="td-mono"><?= htmlspecialchars($a['rank'] ?? '—') ?></td>
              <td><span class="alert-pill <?= $apill ?>"><?= strtoupper($a['alert_type']) ?></span></td>
              <td><span class="badge <?= $ab ?>"><?= ucfirst($a['status']) ?></span></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        <?php else: ?>
          <div class="empty"><div class="empty-icon">🔕</div><div style="font-size:13px">No recent alerts — all officers compliant</div></div>
        <?php endif; ?>
      </div>
    </div>
  </div>

</div><!-- /page -->

<script>
  /* Clock */
  function tick() {
    document.getElementById('clock').textContent =
      new Date().toLocaleTimeString('en-IN',{hour12:false});
  }
  tick(); setInterval(tick,1000);

  /* Stat counter animation */
  document.querySelectorAll('.stat-value[data-count]').forEach(el=>{
    const target=parseInt(el.dataset.count,10);
    if(!target) return;
    const dur=900, t0=performance.now();
    (function frame(now){
      const p=Math.min((now-t0)/dur,1), e=1-Math.pow(1-p,3);
      el.textContent=Math.round(e*target);
      if(p<1) requestAnimationFrame(frame);
    })(t0);
  });

  /* Table row stagger */
  document.querySelectorAll('tbody tr').forEach((tr,i)=>{
    tr.style.cssText=`opacity:0;transform:translateY(8px);transition:opacity .3s ease ${i*.04}s,transform .3s ease ${i*.04}s`;
    requestAnimationFrame(()=>{ tr.style.opacity='1'; tr.style.transform='translateY(0)'; });
  });

  /* Compliance bars */
  setTimeout(()=>{
    document.querySelectorAll('.cl-fill').forEach(bar=>{
      const w=bar.style.width;
      bar.style.width='0%';
      setTimeout(()=>{ bar.style.width=w; },100);
    });
  },300);

  /* Google Maps — dark style */
  let map, markers=[], infoWindows=[];
  function initMap(){
    map=new google.maps.Map(document.getElementById('live-map'),{
      center:{lat:17.3850,lng:78.4867}, zoom:12,
      styles:[
        {elementType:'geometry',stylers:[{color:'#1e2a3a'}]},
        {elementType:'labels.text.fill',stylers:[{color:'#8a9ab0'}]},
        {elementType:'labels.text.stroke',stylers:[{color:'#1e2a3a'}]},
        {featureType:'road',elementType:'geometry',stylers:[{color:'#263546'}]},
        {featureType:'road',elementType:'geometry.stroke',stylers:[{color:'#1a2535'}]},
        {featureType:'water',elementType:'geometry',stylers:[{color:'#0d1b2a'}]},
        {featureType:'poi',elementType:'geometry',stylers:[{color:'#1e2a3a'}]},
        {featureType:'poi.park',elementType:'geometry',stylers:[{color:'#17263c'}]},
        {featureType:'transit',elementType:'geometry',stylers:[{color:'#2c3e50'}]},
        {featureType:'administrative',elementType:'geometry.stroke',stylers:[{color:'#2c4a6e'}]},
      ],
      mapTypeControl:false, streetViewControl:false, fullscreenControl:true,
      backgroundColor:'#0d1b2a'
    });
    loadLocations();
    setInterval(loadLocations,30000);
  }

  function loadLocations(){
    const key='<?= SecurityMiddleware::generateAPIKey($_SESSION["user"]["user_id"], $pdo) ?>';
    fetch('../backend/api/locations?api_key='+key)
      .then(r=>r.json())
      .then(d=>{ if(d.success) updateMarkers(d.data); })
      .catch(e=>console.log('GPS fetch failed:',e));
  }

  function updateMarkers(locations){
    markers.forEach(m=>m.setMap(null));
    infoWindows.forEach(iw=>iw.close());
    markers=[]; infoWindows=[];
    locations.forEach(o=>{
      const color=o.status==='inside'?'#10b981':'#ef4444';
      const ring=o.status==='inside'?'rgba(16,185,129,.3)':'rgba(239,68,68,.3)';
      const svg=`<svg width="44" height="44" viewBox="0 0 44 44" xmlns="http://www.w3.org/2000/svg">
        <circle cx="22" cy="22" r="20" fill="${ring}"/>
        <circle cx="22" cy="22" r="14" fill="${color}" stroke="white" stroke-width="2"/>
        <text x="22" y="27" text-anchor="middle" fill="white" font-size="13">👮</text>
      </svg>`;
      const marker=new google.maps.Marker({
        position:{lat:o.latitude,lng:o.longitude}, map, title:o.name,
        icon:{url:'data:image/svg+xml;charset=UTF-8,'+encodeURIComponent(svg), scaledSize:new google.maps.Size(44,44)}
      });
      const iw=new google.maps.InfoWindow({content:`
        <div style="font-family:'DM Sans',sans-serif;padding:4px;max-width:220px;color:#1e293b">
          <div style="font-weight:700;font-size:14px;margin-bottom:8px;color:#0f172a">${o.name}</div>
          <div style="font-size:12px;color:#475569;line-height:1.9">
            <b>Rank:</b> ${o.rank}<br>
            <b>Status:</b> <span style="color:${color};font-weight:700">${o.status.toUpperCase()}</span><br>
            ${o.duty?`<b>Zone:</b> ${o.duty.location_name}<br><b>Shift:</b> ${o.duty.start_time} – ${o.duty.end_time}`:'<b>Duty:</b> No active assignment'}
          </div>
          <div style="font-size:10px;color:#94a3b8;margin-top:8px;font-family:monospace">⏱ ${o.timestamp}</div>
        </div>`
      });
      marker.addListener('click',()=>{ infoWindows.forEach(w=>w.close()); iw.open(map,marker); });
      markers.push(marker); infoWindows.push(iw);
    });
    if(locations.length>0){
      const b=new google.maps.LatLngBounds();
      locations.forEach(o=>b.extend({lat:o.latitude,lng:o.longitude}));
      map.fitBounds(b);
    }
  }
</script>
</body>
</html>