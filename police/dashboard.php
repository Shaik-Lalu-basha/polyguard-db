<?php
require_once __DIR__ . '/../backend/db.php';
require_once __DIR__ . '/../backend/auth.php';
require_once __DIR__ . '/../backend/security.php';
// Load central config
$POLYGUARD_CONFIG = require_once __DIR__ . '/../backend/config.php';
require_once __DIR__ . '/../backend/advanced_analytics.php';
requireRole('police');
initSecurityTables($pdo);

$user = $_SESSION['user'];

$stmt = $pdo->prepare('SELECT * FROM duty_assignments WHERE personnel_id=? AND status="active" ORDER BY created_at DESC LIMIT 1');
$stmt->execute([$user['user_id']]);
$duty = $stmt->fetch();

$att = null;
if ($duty) {
    $current = $pdo->prepare('SELECT * FROM attendance WHERE duty_id=? AND personnel_id=? LIMIT 1');
    $current->execute([$duty['duty_id'], $user['user_id']]);
    $att = $current->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'checkin' && $duty) {
        $pdo->prepare('UPDATE attendance SET checkin_time=NOW() WHERE id=?')->execute([$att['id']]);
        header('Location: ./dashboard.php'); exit;
    } elseif (isset($_POST['action']) && $_POST['action'] === 'checkout' && $duty) {
        $pdo->prepare('UPDATE attendance SET checkout_time=NOW(), total_seconds=TIMESTAMPDIFF(SECOND, checkin_time, NOW()) WHERE id=?')->execute([$att['id']]);
        $pdo->prepare('UPDATE duty_assignments SET status="completed" WHERE duty_id=?')->execute([$duty['duty_id']]);
        header('Location: ./dashboard.php'); exit;
    }
}

$assigned  = (bool)$duty;
$checkedIn = $assigned && $att && $att['checkin_time'];
$checkedOut= $assigned && $att && $att['checkout_time'];

// Compliance history
$compStmt = $pdo->prepare('SELECT compliance_score, updated_at FROM compliance WHERE personnel_id=? ORDER BY updated_at DESC LIMIT 1');
$compStmt->execute([$user['user_id']]);
$compliance = $compStmt->fetch();
$compScore  = $compliance ? round($compliance['compliance_score'], 1) : 0;

// Today's alerts for this officer
$alertStmt = $pdo->prepare('SELECT COUNT(*) FROM alerts WHERE personnel_id=? AND alert_time >= CURDATE()');
$alertStmt->execute([$user['user_id']]);
$todayAlerts = $alertStmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Officer Portal — POLYGUARD AI</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
  <script>
    // Embed-only Google Maps (no Maps JS API)
    function insertEmbedMap(id, lat=14.6816, lng=77.6000, zoom=12) {
      var el = document.getElementById(id);
      if (!el) return;
      var src = 'https://www.google.com/maps?q=' + encodeURIComponent(lat + ',' + lng) + '&z=' + encodeURIComponent(zoom) + '&output=embed';
      el.innerHTML = '<iframe width="100%" height="100%" frameborder="0" style="border:0;border-radius:12px;" src="' + src + '" allowfullscreen></iframe>';
    }
    document.addEventListener('DOMContentLoaded', function(){ ['live-map','officers-map','map'].forEach(function(id){ insertEmbedMap(id); }); });
  </script>
  <style>
    :root {
      --navy:        #0b1628;
      --navy-mid:    #112240;
      --navy-soft:   #1a3358;
      --gold:        #c9a84c;
      --gold-light:  #e8c97a;
      --gold-pale:   #fdf3d7;
      --white:       #ffffff;
      --off-white:   #f7f8fb;
      --border:      #e2e8f0;
      --border-dark: #cbd5e1;
      --slate:       #64748b;
      --slate-light: #94a3b8;
      --text-main:   #0f172a;
      --text-sub:    #475569;
      --green:       #10b981;
      --red:         #ef4444;
      --amber:       #f59e0b;
      --blue:        #3b82f6;
      --shadow-sm:   0 1px 3px rgba(0,0,0,.06);
      --shadow-md:   0 4px 16px rgba(11,22,40,.08);
      --shadow-lg:   0 12px 40px rgba(11,22,40,.14);
      --radius:      16px;
      --radius-sm:   10px;
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    html { scroll-behavior: smooth; }
    body { font-family: 'DM Sans', sans-serif; background: var(--off-white); color: var(--text-main); min-height: 100vh; }

    /* ── NAVBAR ── */
    .navbar {
      position: sticky; top: 0; z-index: 300;
      height: 66px; padding: 0 36px;
      background: var(--navy);
      display: flex; align-items: center; justify-content: space-between;
      box-shadow: 0 2px 24px rgba(0,0,0,.3);
    }
    .navbar::after {
      content: ''; position: absolute; bottom: 0; left: 0; right: 0; height: 2px;
      background: linear-gradient(90deg, transparent, var(--gold) 30%, var(--gold-light) 50%, var(--gold) 70%, transparent);
    }
    .nav-left { display: flex; align-items: center; gap: 13px; }
    .nav-logo {
      width: 38px; height: 38px; border-radius: 9px;
      background: linear-gradient(135deg, var(--gold), var(--gold-light));
      display: grid; place-items: center;
      font-family: 'Bebas Neue'; font-size: 16px; color: var(--navy);
      box-shadow: 0 2px 10px rgba(201,168,76,.4);
    }
    .nav-wordmark { font-family: 'Bebas Neue'; font-size: 22px; letter-spacing: 3px; color: #fff; }
    .nav-wordmark span { color: var(--gold); }
    .nav-divider { width: 1px; height: 22px; background: rgba(255,255,255,.12); }
    .nav-section { font-family: 'JetBrains Mono'; font-size: 11px; letter-spacing: 2px; color: var(--slate-light); }
    .nav-right { display: flex; align-items: center; gap: 14px; }
    .nav-clock { font-family: 'JetBrains Mono'; font-size: 13px; color: var(--slate-light); }
    .nav-officer { display: flex; align-items: center; gap: 10px; }
    .nav-avatar {
      width: 36px; height: 36px; border-radius: 50%;
      background: linear-gradient(135deg, #065f46, #10b981);
      display: grid; place-items: center;
      font-family: 'Bebas Neue'; font-size: 15px; color: #fff;
      border: 2px solid rgba(201,168,76,.4);
    }
    .nav-name  { font-size: 13px; font-weight: 600; color: #e2e8f0; }
    .nav-role  { font-size: 11px; color: #6ee7b7; font-family: 'JetBrains Mono'; letter-spacing: .5px; }
    .btn-nav {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 8px 16px; border-radius: 8px; font-family: 'DM Sans';
      font-size: 13px; font-weight: 500; text-decoration: none;
      border: none; cursor: pointer; transition: all .2s ease;
    }
    .btn-home   { background: rgba(255,255,255,.08); color: rgba(255,255,255,.8); border: 1px solid rgba(255,255,255,.12); }
    .btn-home:hover { background: rgba(255,255,255,.15); color: #fff; }
    .btn-logout { background: transparent; color: var(--gold-light); border: 1.5px solid rgba(201,168,76,.35); }
    .btn-logout:hover { background: var(--gold); color: var(--navy); border-color: var(--gold); transform: translateY(-1px); }

    /* ── PAGE ── */
    .page { max-width: 1380px; margin: 0 auto; padding: 34px 24px 64px; }

    /* ── HERO ── */
    .hero {
      background: linear-gradient(135deg, var(--navy) 0%, var(--navy-mid) 55%, #0f3460 100%);
      border-radius: var(--radius); padding: 30px 40px;
      margin-bottom: 26px; position: relative; overflow: hidden;
      box-shadow: var(--shadow-lg);
      display: flex; align-items: center; justify-content: space-between; gap: 20px;
      animation: fadeDown .5s ease both;
    }
    .hero::before {
      content: ''; position: absolute; top: -70px; right: -70px;
      width: 300px; height: 300px; border-radius: 50%;
      background: radial-gradient(circle, rgba(16,185,129,.1) 0%, transparent 65%);
    }
    .hero::after {
      content: ''; position: absolute; bottom: -50px; left: 30%;
      width: 200px; height: 200px; border-radius: 50%;
      background: radial-gradient(circle, rgba(201,168,76,.07) 0%, transparent 65%);
    }
    .hero-left { position: relative; z-index: 1; }
    .hero-breadcrumb {
      font-family: 'JetBrains Mono'; font-size: 11px;
      color: rgba(255,255,255,.35); letter-spacing: .5px;
      margin-bottom: 9px; display: flex; align-items: center; gap: 6px;
    }
    .hero-breadcrumb em { color: #6ee7b7; font-style: normal; }
    .hero-title { font-family: 'Bebas Neue'; font-size: 38px; letter-spacing: 3px; color: #fff; line-height: 1; }
    .hero-sub   { font-size: 13.5px; color: rgba(255,255,255,.5); margin-top: 6px; }
    .hero-right { display: flex; gap: 10px; position: relative; z-index: 1; flex-wrap: wrap; justify-content: flex-end; align-items: center; }
    .hero-badge {
      padding: 9px 18px; border-radius: 30px;
      font-family: 'Bebas Neue'; font-size: 13px; letter-spacing: 2px;
      display: flex; align-items: center; gap: 7px;
    }
    .hb-officer { background: rgba(16,185,129,.12); color: #6ee7b7; border: 1.5px solid rgba(16,185,129,.3); }
    .hb-duty    { background: rgba(201,168,76,.12); color: var(--gold-light); border: 1.5px solid rgba(201,168,76,.3); }
    .hb-nodty   { background: rgba(239,68,68,.1);  color: #fca5a5; border: 1.5px solid rgba(239,68,68,.25); }

    /* ── STATS ── */
    .stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px,1fr)); gap: 18px; margin-bottom: 26px; }
    .stat-card {
      background: var(--white); border-radius: var(--radius);
      padding: 22px 20px; border: 1px solid var(--border);
      box-shadow: var(--shadow-md); position: relative; overflow: hidden;
      transition: transform .2s ease, box-shadow .2s ease;
      animation: fadeUp .5s ease both;
    }
    .stat-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-lg); }
    .stat-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; }
    .sc-green::before  { background: linear-gradient(90deg,#10b981,#34d399); }
    .sc-blue::before   { background: linear-gradient(90deg,#3b82f6,#60a5fa); }
    .sc-amber::before  { background: linear-gradient(90deg,#f59e0b,#fbbf24); }
    .sc-red::before    { background: linear-gradient(90deg,#ef4444,#f87171); }
    .stat-icon {
      width: 48px; height: 48px; border-radius: 13px;
      display: grid; place-items: center; font-size: 24px;
      margin-bottom: 14px; position: relative;
    }
    .stat-icon::after { content: ''; position: absolute; bottom: -3px; left: 8px; right: 8px; height: 4px; border-radius: 0 0 6px 6px; filter: blur(3px); opacity: .35; }
    .si-green  { background: linear-gradient(145deg,#ecfdf5,#a7f3d0); }
    .si-green::after  { background: #10b981; }
    .si-blue   { background: linear-gradient(145deg,#eff6ff,#bfdbfe); }
    .si-blue::after   { background: #3b82f6; }
    .si-amber  { background: linear-gradient(145deg,#fffbeb,#fde68a); }
    .si-amber::after  { background: #f59e0b; }
    .si-red    { background: linear-gradient(145deg,#fef2f2,#fecaca); }
    .si-red::after    { background: #ef4444; }
    .stat-label { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; color: var(--slate); }
    .stat-value { font-family: 'Bebas Neue'; font-size: 44px; line-height: 1; margin-top: 2px; }
    .sv-green { color: #065f46; }
    .sv-blue  { color: #1e40af; }
    .sv-amber { color: #78350f; }
    .sv-red   { color: #991b1b; }
    .stat-sub { font-size: 12px; color: var(--slate-light); margin-top: 5px; }

    /* ── ACTION BUTTONS ── */
    .action-btn {
      flex: 1; display: flex; flex-direction: column; align-items: center; gap: 4px;
      background: linear-gradient(135deg, var(--navy-600), var(--navy-700));
      border: 1.5px solid var(--border-dark); color: var(--white); cursor: pointer;
      padding: 14px 16px; border-radius: 12px; font-weight: 500;
      transition: all .2s ease; font-size: 12px;
    }
    .action-btn:hover:not(:disabled) {
      background: linear-gradient(135deg, var(--navy-500), var(--navy-600));
      border-color: var(--gold-500); transform: translateY(-2px);
    }
    .action-btn:disabled {
      opacity: .4; cursor: not-allowed;
    }
    .ab-emoji { font-size: 20px; }
    .ab-label { font-weight: 600; }
    .ab-sub { font-size: 10px; opacity: .7; }
    .ab-checkin { border-color: #10b981; }
    .ab-checkout { border-color: #ef4444; }
    .btn-primary { background: #3b82f6; color: white; padding: 10px 16px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; }
    .btn-secondary { background: #64748b; color: white; padding: 10px 16px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; }

    /* ── AI INSIGHTS ── */
    .insight-card {
      background: var(--off-white); border: 1px solid var(--border);
      border-radius: var(--radius-sm); padding: 16px;
      display: flex; align-items: center; gap: 12px;
      transition: background .2s ease;
    }
    .insight-card:hover { background: var(--white); }
    .ic-icon { font-size: 24px; }
    .ic-label { font-size: 12px; font-weight: 600; color: var(--slate); text-transform: uppercase; letter-spacing: 1px; }
    .ic-value { font-size: 16px; font-weight: 500; color: var(--text-main); margin-top: 2px; }

    /* ── MAIN GRID ── */
    .main-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 22px; margin-bottom: 22px; }
    @media (max-width: 1024px) { .main-grid { grid-template-columns: 1fr; } }

    /* ── SECTION CARD ── */
    .section-card {
      background: var(--white); border-radius: var(--radius);
      border: 1px solid var(--border); box-shadow: var(--shadow-md);
      overflow: hidden; animation: fadeUp .5s ease .12s both;
    }
    .sc-header {
      display: flex; align-items: center; justify-content: space-between;
      padding: 20px 24px; border-bottom: 1px solid var(--border);
    }
    .sc-hl { display: flex; align-items: center; gap: 12px; }
    .sc-icon { width: 38px; height: 38px; border-radius: 10px; background: var(--navy); display: grid; place-items: center; font-size: 18px; flex-shrink: 0; }
    .sc-title { font-size: 15px; font-weight: 600; color: var(--navy); }
    .sc-sub   { font-size: 12px; color: var(--slate); margin-top: 1px; }
    .sc-body  { padding: 24px; }

    /* ── DUTY CARD ── */
    .duty-hero {
      background: linear-gradient(135deg, var(--navy), var(--navy-soft));
      border-radius: var(--radius-sm);
      padding: 22px 24px; margin-bottom: 20px;
      position: relative; overflow: hidden;
    }
    .duty-hero::before {
      content: ''; position: absolute; top: -30px; right: -30px;
      width: 120px; height: 120px; border-radius: 50%;
      background: radial-gradient(circle, rgba(201,168,76,.12), transparent 65%);
    }
    .duty-loc { font-family: 'Bebas Neue'; font-size: 22px; letter-spacing: 1px; color: #fff; margin-bottom: 6px; display: flex; align-items: center; gap: 8px; }
    .duty-meta-row { display: flex; gap: 14px; flex-wrap: wrap; }
    .duty-chip {
      display: inline-flex; align-items: center; gap: 6px;
      font-family: 'JetBrains Mono'; font-size: 11.5px;
      color: rgba(255,255,255,.65);
      background: rgba(255,255,255,.07);
      padding: 5px 11px; border-radius: 6px;
      border: 1px solid rgba(255,255,255,.1);
    }
    .no-duty-state {
      text-align: center; padding: 40px 20px;
    }
    .nd-icon { font-size: 48px; opacity: .4; margin-bottom: 12px; }
    .nd-text { font-size: 15px; font-weight: 500; color: var(--slate); }
    .nd-sub  { font-size: 13px; color: var(--slate-light); margin-top: 5px; }

    /* ── ACTION BUTTONS ── */
    .action-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    .action-btn {
      display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px;
      padding: 18px 16px; border-radius: var(--radius-sm);
      border: none; cursor: pointer; font-family: 'DM Sans';
      position: relative; overflow: hidden;
      transition: transform .2s ease, box-shadow .2s ease, opacity .2s ease;
    }
    .action-btn::before {
      content: ''; position: absolute; top: 0; left: -100%; width: 100%; height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,.15), transparent);
      transition: left .4s ease;
    }
    .action-btn:not(:disabled):hover { transform: translateY(-3px); box-shadow: 0 10px 28px rgba(0,0,0,.2); }
    .action-btn:not(:disabled):hover::before { left: 100%; }
    .action-btn:disabled { opacity: .4; cursor: not-allowed; transform: none !important; }
    .ab-checkin  { background: linear-gradient(135deg, #065f46, #10b981); color: #fff; }
    .ab-checkout { background: linear-gradient(135deg, #991b1b, #ef4444); color: #fff; }
    .ab-emoji { font-size: 28px; }
    .ab-label { font-size: 14px; font-weight: 700; }
    .ab-sub   { font-size: 11px; opacity: .8; }

    /* Checkin status indicator */
    .status-bar {
      display: flex; align-items: center; gap: 12px;
      padding: 14px 18px; border-radius: var(--radius-sm);
      margin-bottom: 18px; font-size: 13.5px; font-weight: 500;
    }
    .sb-checkedin  { background: #ecfdf5; border: 1px solid #6ee7b7; color: #065f46; }
    .sb-checkedout { background: #eff6ff; border: 1px solid #93c5fd; color: #1e40af; }
    .sb-waiting    { background: var(--off-white); border: 1px solid var(--border); color: var(--slate); }
    .sb-icon { font-size: 20px; }
    .sb-time { font-family: 'JetBrains Mono'; font-size: 12px; opacity: .8; }

    /* ── MAP ── */
    .map-card {
      background: var(--white); border-radius: var(--radius);
      border: 1px solid var(--border); box-shadow: var(--shadow-md);
      overflow: hidden; margin-bottom: 24px;
      animation: fadeUp .55s ease .18s both;
    }
    .map-header {
      display: flex; align-items: center; justify-content: space-between;
      padding: 20px 26px; border-bottom: 1px solid var(--border);
    }
    .map-hl { display: flex; align-items: center; gap: 12px; }
    .map-icon { width: 38px; height: 38px; border-radius: 10px; background: var(--navy); display: grid; place-items: center; font-size: 18px; flex-shrink: 0; }
    .map-title { font-size: 15px; font-weight: 600; color: var(--navy); }
    .map-sub   { font-size: 12px; color: var(--slate); margin-top: 1px; }
    .map-legend { display: flex; align-items: center; gap: 16px; }
    .leg-item { display: flex; align-items: center; gap: 6px; font-size: 12px; color: var(--slate); font-weight: 500; }
    .leg-dot  { width: 10px; height: 10px; border-radius: 50%; }
    .leg-blue  { background: var(--blue); }
    .leg-green { background: var(--green); }
    #map { height: 380px; width: 100%; }

    /* ── LOG CARD ── */
    .log-card {
      background: var(--white); border-radius: var(--radius);
      border: 1px solid var(--border); box-shadow: var(--shadow-md);
      overflow: hidden; animation: fadeUp .5s ease .24s both;
    }
    .log-body { padding: 0; }
    .log-console {
      background: var(--navy);
      font-family: 'JetBrains Mono'; font-size: 12px;
      color: #a5f3fc; line-height: 1.7;
      padding: 20px 24px;
      max-height: 240px; overflow-y: auto;
      margin: 0;
    }
    .log-console::-webkit-scrollbar { width: 4px; }
    .log-console::-webkit-scrollbar-thumb { background: rgba(255,255,255,.15); border-radius: 2px; }
    .log-entry { display: flex; gap: 12px; padding: 3px 0; border-bottom: 1px solid rgba(255,255,255,.04); }
    .log-entry:last-child { border-bottom: none; }
    .log-time   { color: rgba(255,255,255,.3); flex-shrink: 0; }
    .log-status-in  { color: #6ee7b7; }
    .log-status-out { color: #fca5a5; }
    .log-init   { color: rgba(255,255,255,.4); font-style: italic; }
    .log-toolbar {
      display: flex; align-items: center; justify-content: space-between;
      padding: 10px 18px; background: #0d1f3c; border-top: 1px solid rgba(255,255,255,.07);
    }
    .log-dot-row { display: flex; gap: 6px; }
    .log-dot { width: 10px; height: 10px; border-radius: 50%; }
    .log-label { font-family: 'JetBrains Mono'; font-size: 10px; color: rgba(255,255,255,.25); letter-spacing: 1px; }
    .log-clear {
      font-family: 'JetBrains Mono'; font-size: 10px; color: rgba(255,255,255,.3);
      background: none; border: none; cursor: pointer;
      padding: 3px 8px; border-radius: 4px;
      transition: color .15s ease;
    }
    .log-clear:hover { color: rgba(255,255,255,.7); }

    /* ── COMPLIANCE BAR ── */
    .comp-bar-wrap { margin-top: 16px; }
    .comp-bar-label { display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; }
    .comp-bar-title { font-size: 12px; font-weight: 600; color: var(--slate); text-transform: uppercase; letter-spacing: .8px; }
    .comp-bar-pct   { font-family: 'Bebas Neue'; font-size: 20px; color: var(--navy); }
    .comp-track { height: 8px; background: var(--border); border-radius: 4px; overflow: hidden; }
    .comp-fill  { height: 100%; border-radius: 4px; transition: width 1.2s cubic-bezier(.4,0,.2,1); }

    /* ── PULSE ── */
    .pulse { width: 8px; height: 8px; border-radius: 50%; background: var(--green); display: inline-block; position: relative; }
    .pulse::after { content: ''; position: absolute; top: -3px; left: -3px; width: 14px; height: 14px; border-radius: 50%; background: rgba(16,185,129,.25); animation: ripple 1.8s ease infinite; }

    /* ── BADGE ── */
    .badge { display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
    .badge::before { content: ''; width: 5px; height: 5px; border-radius: 50%; }
    .b-active::before  { background: #10b981; }
    .b-active  { background: #ecfdf5; color: #065f46; }
    .b-pending { background: #fffbeb; color: #92400e; }
    .b-pending::before { background: #f59e0b; }
    .b-done    { background: #eff6ff; color: #1e40af; }
    .b-done::before    { background: #3b82f6; }

    /* ── ANIMATIONS ── */
    @keyframes fadeDown { from{opacity:0;transform:translateY(-14px)}to{opacity:1;transform:translateY(0)} }
    @keyframes fadeUp   { from{opacity:0;transform:translateY(18px)}to{opacity:1;transform:translateY(0)} }
    @keyframes ripple   { 0%{transform:scale(1);opacity:.6}70%{transform:scale(2.3);opacity:0}100%{transform:scale(1);opacity:0} }
    @keyframes fillBar  { from{width:0}to{} }

    ::-webkit-scrollbar { width: 5px; }
    ::-webkit-scrollbar-track { background: var(--off-white); }
    ::-webkit-scrollbar-thumb { background: var(--border-dark); border-radius: 3px; }

    @media (max-width: 640px) {
      .navbar { padding: 0 16px; }
      .page { padding: 20px 12px 48px; }
      .hero { flex-direction: column; padding: 22px; }
      .hero-title { font-size: 28px; }
      .nav-clock { display: none; }
      #map { height: 280px; }
      .action-row { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>

<!-- ── NAVBAR ── -->
<nav class="navbar">
  <div class="nav-left">
    <div class="nav-logo">PG</div>
    <div class="nav-wordmark">POLY<span>GUARD</span> AI</div>
    <div class="nav-divider"></div>
    <div class="nav-section">OFFICER PORTAL</div>
  </div>
  <div class="nav-right">
    <div class="nav-clock" id="clock">--:--:--</div>
    <div class="nav-officer">
      <div>
        <div class="nav-name"><?= htmlspecialchars($user['name']) ?></div>
        <div class="nav-role">🛡 POLICE OFFICER</div>
      </div>
      <div class="nav-avatar"><?= strtoupper(substr($user['name'],0,1)) ?></div>
    </div>
    <a class="btn-nav btn-home" href="../index.php">🏠 Home</a>
    <a class="btn-nav btn-logout" href="./logout.php">⏻ Logout</a>
  </div>
</nav>

<!-- ── PAGE ── -->
<div class="page">

  <!-- HERO -->
  <div class="hero">
    <div class="hero-left">
      <div class="hero-breadcrumb">
        POLYGUARD AI <span style="opacity:.3;margin:0 5px">/</span>
        POLICE <span style="opacity:.3;margin:0 5px">/</span>
        <em>OFFICER PORTAL</em>
      </div>
      <div class="hero-title">Officer Dashboard</div>
      <div class="hero-sub"><?= htmlspecialchars($user['name']) ?> · <?= htmlspecialchars($user['rank'] ?? 'Police Officer') ?> · <?= date('l, d M Y') ?></div>
    </div>
    <div class="hero-right">
      <div class="hero-badge hb-officer">🛡 POLICE OFFICER</div>
      <?php if ($assigned): ?>
        <div class="hero-badge hb-duty"><span class="pulse" style="background:var(--gold-light);width:7px;height:7px"></span> DUTY ACTIVE</div>
      <?php else: ?>
        <div class="hero-badge hb-nodty">⚪ NO ACTIVE DUTY</div>
      <?php endif; ?>
    </div>
  </div>

  <!-- STATS -->
  <div class="stats-row">
    <div class="stat-card sc-green" style="animation-delay:.04s">
      <div class="stat-icon si-green">📍</div>
      <div class="stat-label">Duty Status</div>
      <div class="stat-value sv-green" style="font-size:28px;line-height:1.3;margin-top:6px">
        <?= $assigned ? ($checkedIn ? ($checkedOut ? 'Done' : 'Active') : 'Pending') : 'None' ?>
      </div>
      <div class="stat-sub"><?= $assigned ? 'Assignment loaded' : 'Awaiting assignment' ?></div>
    </div>
    <div class="stat-card sc-blue" style="animation-delay:.08s">
      <div class="stat-icon si-blue">📊</div>
      <div class="stat-label">Compliance Score</div>
      <div class="stat-value sv-blue"><?= $compScore ?><span style="font-size:20px">%</span></div>
      <div class="stat-sub">Overall performance</div>
    </div>
    <div class="stat-card sc-amber" style="animation-delay:.12s">
      <div class="stat-icon si-amber">🔔</div>
      <div class="stat-label">Today's Alerts</div>
      <div class="stat-value sv-amber" data-count="<?= $todayAlerts ?>"><?= $todayAlerts ?></div>
      <div class="stat-sub">Triggered today</div>
    </div>
    <div class="stat-card sc-green" style="animation-delay:.16s">
      <div class="stat-icon si-green">⏱</div>
      <div class="stat-label">Shift Time</div>
      <div class="stat-value sv-green" style="font-size:28px;line-height:1.3;margin-top:6px" id="shiftTimer">
        <?= $assigned ? $duty['start_time'].' – '.$duty['end_time'] : '—' ?>
      </div>
      <div class="stat-sub">Assigned window</div>
    </div>
  </div>

  <!-- AI INSIGHTS -->
  <div class="section-card" style="margin-bottom:26px">
    <div class="sc-header">
      <div class="sc-hl">
        <div class="sc-icon">🤖</div>
        <div>
          <div class="sc-title">AI Insights</div>
          <div class="sc-sub">Real-time analysis and predictions</div>
        </div>
      </div>
    </div>
    <div class="sc-body">
      <div id="ai-insights" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px">
        <div class="insight-card">
          <div class="ic-icon">📈</div>
          <div class="ic-label">Compliance Pattern</div>
          <div class="ic-value" id="compliance-pattern">Loading...</div>
        </div>
        <div class="insight-card">
          <div class="ic-icon">🔮</div>
          <div class="ic-label">Violation Risk</div>
          <div class="ic-value" id="violation-risk">Loading...</div>
        </div>
        <div class="insight-card">
          <div class="ic-icon">⚠️</div>
          <div class="ic-label">Anomaly Detection</div>
          <div class="ic-value" id="anomaly-detection">Loading...</div>
        </div>
      </div>
    </div>
  </div>

  <!-- LIVE OFFICERS MAP -->
  <div class="section-card" style="margin-bottom:26px">
    <div class="sc-header">
      <div class="sc-hl">
        <div class="sc-icon">🌍</div>
        <div>
          <div class="sc-title">Live Officers Map</div>
          <div class="sc-sub">Real-time location tracking of all police personnel</div>
        </div>
      </div>
      <div style="margin-left:auto;display:flex;align-items:center;gap:8px;font-size:12px;color:var(--slate)">
        <span class="pulse-dot"></span> Live tracking
      </div>
    </div>
    <div class="sc-body">
      <div id="officers-map" style="height:300px;width:100%;border-radius:12px;"></div>
    </div>
  </div>

  <!-- MAIN GRID: Duty + Map -->
  <div class="main-grid">

    <!-- DUTY ASSIGNMENT -->
    <div class="section-card">
      <div class="sc-header">
        <div class="sc-hl">
          <div class="sc-icon">📋</div>
          <div>
            <div class="sc-title">Duty Assignment</div>
            <div class="sc-sub"><?= $assigned ? 'Active deployment details' : 'No duty assigned' ?></div>
          </div>
        </div>
        <?php if ($assigned): ?>
          <span class="badge b-active">Active</span>
        <?php endif; ?>
      </div>
      <div class="sc-body">

        <?php if (!$assigned): ?>
        <div class="no-duty-state">
          <div class="nd-icon">📭</div>
          <div class="nd-text">No Active Duty Assigned</div>
          <div class="nd-sub">Your commanding officer will assign a duty location. Please stand by.</div>
        </div>

        <?php else: ?>
        <!-- Duty details -->
        <div class="duty-hero">
          <div class="duty-loc">📍 <?= htmlspecialchars($duty['location_name']) ?></div>
          <div class="duty-meta-row">
            <span class="duty-chip">⏰ <?= $duty['start_time'] ?> – <?= $duty['end_time'] ?></span>
            <span class="duty-chip">📏 <?= $duty['radius'] ?>m radius</span>
            <span class="duty-chip">🌐 <?= number_format($duty['latitude'],5) ?>, <?= number_format($duty['longitude'],5) ?></span>
          </div>
        </div>

        <!-- Status bar -->
        <?php if ($checkedOut): ?>
          <div class="status-bar sb-checkedout">
            <span class="sb-icon">✅</span>
            <div><div>Duty Completed</div><div class="sb-time">Checked out: <?= date('H:i', strtotime($att['checkout_time'])) ?></div></div>
          </div>
        <?php elseif ($checkedIn): ?>
          <div class="status-bar sb-checkedin">
            <span class="sb-icon">🟢</span>
            <div><div>Currently On Duty</div><div class="sb-time">Checked in: <?= date('H:i', strtotime($att['checkin_time'])) ?></div></div>
          </div>
        <?php else: ?>
          <div class="status-bar sb-waiting">
            <span class="sb-icon">⏳</span>
            <div><div>Awaiting Check-in</div><div class="sb-time">Duty starts <?= $duty['start_time'] ?></div></div>
          </div>
        <?php endif; ?>

        <!-- Action buttons -->
        <form method="post">
          <div class="action-row">
            <button class="action-btn ab-checkin" name="action" value="checkin"
              <?= ($checkedIn) ? 'disabled' : '' ?>>
              <span class="ab-emoji">🟢</span>
              <span class="ab-label">Check In</span>
              <span class="ab-sub"><?= $checkedIn ? 'Already checked in' : 'Mark arrival at post' ?></span>
            </button>
            <button class="action-btn ab-checkout" name="action" value="checkout"
              <?= (!$checkedIn || $checkedOut) ? 'disabled' : '' ?>>
              <span class="ab-emoji">🔴</span>
              <span class="ab-label">Check Out</span>
              <span class="ab-sub"><?= $checkedOut ? 'Duty complete' : 'End duty shift' ?></span>
            </button>
          </div>
        </form>

        <!-- Compliance mini bar -->
        <div class="comp-bar-wrap">
          <div class="comp-bar-label">
            <span class="comp-bar-title">My Compliance Score</span>
            <span class="comp-bar-pct"><?= $compScore ?>%</span>
          </div>
          <div class="comp-track">
            <div class="comp-fill" id="compFill"
              style="width:<?= $compScore ?>%;
                     background:<?= $compScore >= 80 ? 'linear-gradient(90deg,#10b981,#34d399)' : ($compScore >= 50 ? 'linear-gradient(90deg,#f59e0b,#fbbf24)' : 'linear-gradient(90deg,#ef4444,#f87171)') ?>">
            </div>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- GPS CAMERA & REPORTS -->
    <div class="section-card">
      <div class="sc-header">
        <div class="sc-hl">
          <div class="sc-icon" style="background:#7c3aed">📷</div>
          <div>
            <div class="sc-title">GPS Camera & Reports</div>
            <div class="sc-sub">Capture photo with GPS location</div>
          </div>
        </div>
      </div>
      <div class="sc-body">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
          <button type="button" class="action-btn ab-checkin" id="captureArrival" style="grid-column:1">
            <span class="ab-emoji">📸</span>
            <span class="ab-label">Capture Arrival</span>
            <span class="ab-sub">Submit arrival report</span>
          </button>
          <button type="button" class="action-btn ab-checkout" id="captureDeparture" style="grid-column:2" <?= !$assigned || $checkedOut ? 'disabled' : '' ?>>
            <span class="ab-emoji">📸</span>
            <span class="ab-label">Capture Departure</span>
            <span class="ab-sub">Submit departure report</span>
          </button>
        </div>
        <div id="cameraPreview" style="display:none;margin:16px 0;">
          <video id="cameraVideo" autoplay playsinline muted style="width:100%;border-radius:10px;max-height:300px;background:#000"></video>
          <div id="cameraStatus" style="margin-top:8px;font-size:13px;color:#64748b">Camera idle</div>
          <div style="display:flex;gap:10px;margin-top:10px;">
            <button id="takePicture" class="btn-primary" style="flex:1">Snap Photo</button>
            <button id="cancelCamera" class="btn-secondary" style="flex:1">Cancel</button>
          </div>
        </div>
        <div id="fileInputContainer" style="display:none;margin:16px 0;">
          <input type="file" id="fileInputPhoto" accept="image/*" capture="environment" style="display:none" />
          <div style="display:flex;gap:10px;">
            <button id="fileChooseBtn" class="btn-primary" style="flex:1">Choose Photo</button>
            <button id="fileCancelBtn" class="btn-secondary" style="flex:1">Cancel</button>
          </div>
        </div>
        <canvas id="cameraCanvas" style="display:none"></canvas>
        <div id="photoPreview" style="display:none;margin:16px 0 text-align:center">
          <img id="capturedPhoto" style="max-width:100%;border-radius:10px;max-height:300px">
          <div style="display:flex;gap:10px;margin-top:10px;">
            <button id="submitReport" class="btn-primary" style="flex:1">Submit Report</button>
            <button id="retakePhoto" class="btn-secondary" style="flex:1">Retake</button>
          </div>
        </div>
      </div>
    </div>

    <!-- LOG -->
    <div class="log-card" style="display:flex;flex-direction:column;">
      <div class="sc-header">
        <div class="sc-hl">
          <div class="sc-icon" style="background:#0d1f3c">💻</div>
          <div>
            <div class="sc-title">Location & Tracking Log</div>
            <div class="sc-sub">Real-time GPS updates every 8 seconds</div>
          </div>
        </div>
        <div style="display:flex;align-items:center;gap:7px;font-size:12px;color:var(--slate-light)">
          <span class="pulse" style="width:7px;height:7px"></span> Tracking
        </div>
      </div>
      <div class="log-body" style="flex:1;display:flex;flex-direction:column;">
        <div class="log-toolbar">
          <div class="log-dot-row">
            <div class="log-dot" style="background:#ef4444"></div>
            <div class="log-dot" style="background:#f59e0b"></div>
            <div class="log-dot" style="background:#10b981"></div>
          </div>
          <span class="log-label">POLYGUARD · GPS TRACKER</span>
          <button class="log-clear" onclick="clearLog()">✕ Clear</button>
        </div>
        <pre class="log-console" id="log"><span class="log-init">» System initializing... waiting for GPS signal.</span>
</pre>
      </div>
    </div>
  </div>

  <!-- LIVE MAP (full width) -->
  <div class="map-card">
    <div class="map-header">
      <div class="map-hl">
        <div class="map-icon">🗺</div>
        <div>
          <div class="map-title">Live Position Map</div>
          <div class="map-sub">
            <?= $assigned ? 'Green circle = geo-fence zone · Blue pin = duty post · Red pin = your location' : 'GPS tracking active — no duty zone assigned' ?>
          </div>
        </div>
      </div>
      <div class="map-legend">
        <?php if ($assigned): ?>
        <div class="leg-item"><div class="leg-dot" style="background:#3b82f6"></div>Duty Post</div>
        <div class="leg-item"><div class="leg-dot" style="background:#10b981"></div>Geo-fence</div>
        <?php endif; ?>
        <div class="leg-item"><div class="leg-dot" style="background:#ef4444"></div>Your Position</div>
      </div>
    </div>
    <div id="map"></div>
  </div>

</div><!-- /page -->

<script>
  // ── Clock
  function tick() {
    const now = new Date();
    document.getElementById('clock').textContent =
      String(now.getHours()).padStart(2,'0') + ':' +
      String(now.getMinutes()).padStart(2,'0') + ':' +
      String(now.getSeconds()).padStart(2,'0');
  }
  tick(); setInterval(tick, 1000);

  // ── Counter animation
  document.querySelectorAll('.stat-value[data-count]').forEach(el => {
    const target = parseInt(el.dataset.count, 10);
    if (isNaN(target) || target === 0) return;
    const t0 = performance.now();
    (function frame(now) {
      const p = Math.min((now - t0) / 900, 1);
      el.textContent = Math.round((1 - Math.pow(1-p,3)) * target);
      if (p < 1) requestAnimationFrame(frame);
    })(t0);
  });

  // ── Compliance bar animation
  window.addEventListener('load', () => {
    const fill = document.getElementById('compFill');
    if (fill) {
      const pct = fill.style.width;
      fill.style.width = '0';
      setTimeout(() => { fill.style.width = pct; }, 300);
    }
  });

  // ── Log helpers
  let logCount = 0;
  function appendLog(html) {
    const log = document.getElementById('log');
    if (logCount === 0) log.innerHTML = '';
    log.innerHTML += html + '\n';
    log.scrollTop = log.scrollHeight;
    logCount++;
  }
  function clearLog() {
    document.getElementById('log').innerHTML = '<span class="log-init">» Log cleared.\n</span>';
    logCount = 0;
  }

  // ── Google Map
  let _map, _marker, _circle;
  const _dutyLat  = <?= $assigned ? $duty['latitude']  : 'null' ?>;
  const _dutyLng  = <?= $assigned ? $duty['longitude'] : 'null' ?>;
  const _radius   = <?= $assigned ? $duty['radius']    : 30 ?>;
  const _userId   = <?= (int)$user['user_id'] ?>;
  const _dutyId   = <?= $assigned ? (int)$duty['duty_id'] : 0 ?>;

  function initMap(lat = <?= $assigned ? (float)$duty['latitude'] : 13.0827 ?>,
                   lng = <?= $assigned ? (float)$duty['longitude'] : 80.2707 ?>) {
    const center = { lat, lng };

    _map = new google.maps.Map(document.getElementById('map'), {
      center, zoom: 16,
      styles: [
        { elementType: 'geometry',           stylers: [{ color: '#f0f4f8' }] },
        { elementType: 'labels.text.fill',   stylers: [{ color: '#475569' }] },
        { elementType: 'labels.text.stroke', stylers: [{ color: '#ffffff' }] },
        { featureType: 'road',               elementType: 'geometry', stylers: [{ color: '#ffffff' }] },
        { featureType: 'road',               elementType: 'geometry.stroke', stylers: [{ color: '#e2e8f0' }] },
        { featureType: 'water',              elementType: 'geometry', stylers: [{ color: '#bfdbfe' }] },
        { featureType: 'poi.park',           elementType: 'geometry', stylers: [{ color: '#d1fae5' }] },
      ],
      mapTypeControl: false, streetViewControl: false,
      fullscreenControlOptions: { position: google.maps.ControlPosition.TOP_RIGHT }
    });

    // Officer position marker (red)
    _marker = new google.maps.Marker({
      position: center, map: _map, title: 'Your Position',
      icon: {
        path: google.maps.SymbolPath.CIRCLE,
        fillColor: '#ef4444', fillOpacity: 1,
        strokeColor: '#ffffff', strokeWeight: 2.5, scale: 11
      },
      zIndex: 10
    });

    <?php if ($assigned): ?>
    // Geo-fence circle
    _circle = new google.maps.Circle({
      map: _map,
      center: { lat: _dutyLat, lng: _dutyLng },
      radius: _radius,
      strokeColor: '#10b981', strokeOpacity: .7, strokeWeight: 2,
      fillColor: '#10b981', fillOpacity: .1
    });

    // Duty post marker (blue)
    const dutyMarker = new google.maps.Marker({
      position: { lat: _dutyLat, lng: _dutyLng }, map: _map,
      title: '<?= addslashes($duty['location_name']) ?>',
      icon: {
        path: google.maps.SymbolPath.CIRCLE,
        fillColor: '#3b82f6', fillOpacity: 1,
        strokeColor: '#ffffff', strokeWeight: 2.5, scale: 10
      },
      zIndex: 5, animation: google.maps.Animation.DROP
    });

    const dutyInfo = new google.maps.InfoWindow({
      content: `<div style="font-family:'DM Sans',sans-serif;padding:4px">
        <div style="font-weight:700;color:#0b1628;margin-bottom:3px">📍 <?= addslashes(htmlspecialchars($duty['location_name'])) ?></div>
        <div style="font-size:12px;color:#64748b">Radius: ${_radius}m</div>
        <div style="font-size:11px;font-family:'JetBrains Mono',monospace;color:#94a3b8">${_dutyLat}, ${_dutyLng}</div>
      </div>`
    });
    dutyMarker.addListener('click', () => dutyInfo.open(_map, dutyMarker));
    <?php endif; ?>
  }

  function updateLocation(position) {
    const lat = position.coords.latitude;
    const lng = position.coords.longitude;
    const ts  = new Date().toLocaleTimeString('en-IN', { hour12: false });

    if (_marker) _marker.setPosition({ lat, lng });
    if (_map)    _map.panTo({ lat, lng });

    <?php if ($assigned): ?>
    const payload = new FormData();
    payload.append('personnel_id', _userId);
    payload.append('duty_id', _dutyId);
    payload.append('latitude', lat);
    payload.append('longitude', lng);

    fetch('../backend/track.php', { method: 'POST', body: payload })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          const isIn  = data.status === 'inside';
          const color = isIn ? '#6ee7b7' : '#fca5a5';
          const icon  = isIn ? '✅' : '⚠️';
          appendLog(
            `<span class="log-time">[${ts}]</span> ` +
            `${icon} <span style="color:${color}">${data.status.toUpperCase()}</span> · ` +
            `<span style="color:#93c5fd">Dist: ${data.distance.toFixed(1)}m</span> · ` +
            `<span style="color:#fde68a">Score: ${data.compliance_score}%</span> · ` +
            `<span style="color:rgba(255,255,255,.35)">${lat.toFixed(5)}, ${lng.toFixed(5)}</span>`
          );
        } else {
          appendLog(`<span class="log-time">[${ts}]</span> <span style="color:#fca5a5">Error: ${data.message}</span>`);
        }
      })
      .catch(() => appendLog(`<span class="log-time">[${ts}]</span> <span style="color:#fca5a5">Network error</span>`));
    <?php else: ?>
    appendLog(
      `<span class="log-time">[${ts}]</span> ` +
      `<span style="color:rgba(255,255,255,.4)">GPS: ${lat.toFixed(5)}, ${lng.toFixed(5)} · No duty zone</span>`
    );
    <?php endif; ?>
  }

  function geoFail(err) {
    appendLog(`<span style="color:#fca5a5">» GPS Error: ${err.message}</span>`);
  }

  // Officers Map
  let officersMap;
  let officersMarkers = [];
  let officersInfoWindows = [];

  function initOfficersMap() {
    const center = { lat: 13.0827, lng: 80.2707 };
    officersMap = new google.maps.Map(document.getElementById('officers-map'), {
      center: center,
      zoom: 11,
      styles: [
        { elementType: 'geometry', stylers: [{ color: '#f0f4f8' }] },
        { elementType: 'labels.text.fill', stylers: [{ color: '#475569' }] },
        { elementType: 'labels.text.stroke', stylers: [{ color: '#ffffff' }] },
        { featureType: 'road', elementType: 'geometry', stylers: [{ color: '#ffffff' }] },
        { featureType: 'water', elementType: 'geometry', stylers: [{ color: '#bfdbfe' }] },
        { featureType: 'poi.park', elementType: 'geometry', stylers: [{ color: '#d1fae5' }] },
      ],
      mapTypeControl: false,
      streetViewControl: false,
      fullscreenControl: false
    });

    updateOfficersMap();
  }

  function updateOfficersMap() {
    fetch('../backend/api/locations?api_key=<?= SecurityMiddleware::generateAPIKey($user['user_id'], $pdo) ?>')
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          updateOfficersMarkers(data.data);
        }
      })
      .catch(err => console.log('Officers map update failed:', err));
  }

  function updateOfficersMarkers(locations) {
    officersMarkers.forEach(marker => marker.setMap(null));
    officersInfoWindows.forEach(iw => iw.close());
    officersMarkers = [];
    officersInfoWindows = [];

    locations.forEach(officer => {
      const position = { lat: officer.latitude, lng: officer.longitude };

      let iconUrl = 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(`
        <svg width="30" height="30" viewBox="0 0 30 30" xmlns="http://www.w3.org/2000/svg">
          <circle cx="15" cy="15" r="14" fill="${officer.status === 'inside' ? '#10b981' : '#ef4444'}" stroke="white" stroke-width="2"/>
          <text x="15" y="19" text-anchor="middle" fill="white" font-size="10" font-weight="bold">👮</text>
        </svg>
      `);

      const marker = new google.maps.Marker({
        position: position,
        map: officersMap,
        title: officer.name,
        icon: {
          url: iconUrl,
          scaledSize: new google.maps.Size(30, 30)
        }
      });

      const infoWindow = new google.maps.InfoWindow({
        content: `
          <div style="font-family:Arial,sans-serif;max-width:180px;">
            <h4 style="margin:0 0 6px 0;color:#1f2937;">${officer.name}</h4>
            <p style="margin:3px 0;color:#6b7280;"><strong>Rank:</strong> ${officer.rank}</p>
            <p style="margin:3px 0;color:#6b7280;"><strong>Status:</strong> 
              <span style="color:${officer.status === 'inside' ? '#10b981' : '#ef4444'}">${officer.status}</span>
            </p>
            ${officer.duty ? `<p style="margin:3px 0;color:#6b7280;"><strong>Duty:</strong> ${officer.duty.location_name}</p>` : ''}
          </div>
        `
      });

      marker.addListener('click', () => {
        officersInfoWindows.forEach(iw => iw.close());
        infoWindow.open(officersMap, marker);
      });

      officersMarkers.push(marker);
      officersInfoWindows.push(infoWindow);
    });

    if (locations.length > 0) {
      const bounds = new google.maps.LatLngBounds();
      locations.forEach(officer => {
        bounds.extend({ lat: officer.latitude, lng: officer.longitude });
      });
      officersMap.fitBounds(bounds);
    }
  }

  // Fallback iframe if Google Maps failed to load
  function showMapFallbackOfficers(elementId, lat=13.0827, lng=80.2707, zoom=11) {
    const el = document.getElementById(elementId);
    if (!el) return;
    const src = 'https://www.google.com/maps?q=' + encodeURIComponent(lat + ',' + lng) + '&z=' + encodeURIComponent(zoom) + '&output=embed';
    el.innerHTML = `<iframe width="100%" height="100%" frameborder="0" style="border:0;border-radius:12px;" src="${src}" allowfullscreen></iframe>`;
  }

  setTimeout(() => {
    if (typeof google === 'undefined' || !google.maps) {
      showMapFallbackOfficers('officers-map');
    }
  }, 2500);

  // Realtime updates
  function updateRealtimeStats() {
    fetch('../backend/api/realtime?api_key=<?= SecurityMiddleware::generateAPIKey($user['user_id'], $pdo) ?>')
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          // Update alerts count
          const alertEl = document.querySelector('[data-count]');
          if (alertEl) {
            alertEl.textContent = data.data.today_alerts;
            alertEl.setAttribute('data-count', data.data.today_alerts);
          }
          // Could update other stats here
        }
      })
      .catch(err => console.log('Realtime update failed:', err));
  }

  function updateAIInsights() {
    const apiKey = '<?= SecurityMiddleware::generateAPIKey($user['user_id'], $pdo) ?>';

    // Compliance
    fetch('../backend/api/ai?type=compliance&api_key=' + apiKey)
      .then(r => r.json())
      .then(data => {
        if (data.success && data.data.pattern) {
          document.getElementById('compliance-pattern').textContent = data.data.pattern;
        }
      });

    // Prediction
    fetch('../backend/api/ai?type=prediction&api_key=' + apiKey)
      .then(r => r.json())
      .then(data => {
        if (data.success && data.data.risk_level) {
          document.getElementById('violation-risk').textContent = data.data.risk_level;
        }
      });

    // Anomaly
    fetch('../backend/api/ai?type=anomaly&api_key=' + apiKey)
      .then(r => r.json())
      .then(data => {
        if (data.success && data.data.anomalies) {
          document.getElementById('anomaly-detection').textContent = data.data.anomalies + ' detected';
        }
      });
  }

  window.addEventListener('load', function () {
    if (navigator.geolocation) {
      navigator.geolocation.getCurrentPosition(
        p => initMap(p.coords.latitude, p.coords.longitude),
        geoFail
      );
      setInterval(() => navigator.geolocation.getCurrentPosition(updateLocation, geoFail), 8000);
    } else {
      appendLog('<span style="color:#fca5a5">» Geolocation not supported by this device.</span>');
    }

    // Start realtime updates every 30 seconds
    updateRealtimeStats();
    setInterval(updateRealtimeStats, 30000);

    // Update AI insights every 60 seconds
    updateAIInsights();
    setInterval(updateAIInsights, 60000);

    // Initialize officers map
    initOfficersMap();
    setInterval(updateOfficersMap, 30000);

    // Camera functionality
    initCameraControls();
  });

  // GPS Camera System
  let currentReportType = null;
  let currentDutyId = <?= $assigned ? (int)$duty['duty_id'] : 0 ?>;
  let capturedPhotoBase64 = null;
  let stream = null;

  function initCameraControls() {
    const arrivalBtn = document.getElementById('captureArrival');
    const departureBtn = document.getElementById('captureDeparture');
    const takePhotoBtn = document.getElementById('takePicture');
    const cancelBtn = document.getElementById('cancelCamera');
    const retakeBtn = document.getElementById('retakePhoto');
    const submitBtn = document.getElementById('submitReport');

    const cameraPreview = document.getElementById('cameraPreview');
    const fileContainer = document.getElementById('fileInputContainer');
    const photoPreview = document.getElementById('photoPreview');

    function setCameraVisible(visible) {
      cameraPreview.style.display = visible ? 'block' : 'none';
      if (!visible) {
        if (takePhotoBtn) takePhotoBtn.disabled = true;
        if (cancelBtn) cancelBtn.disabled = true;
      }
    }

    function setPreviewVisible(visible) {
      photoPreview.style.display = visible ? 'block' : 'none';
    }

    function setFileVisible(visible) {
      fileContainer.style.display = visible ? 'block' : 'none';
    }

    function prepareCameraMode(type) {
      currentReportType = type;
      capturedPhotoBase64 = null;
      setPreviewVisible(false);
      setFileVisible(false);
      setCameraVisible(true);
      requestCameraPermissionAndStart();
    }

    if (arrivalBtn) {
      arrivalBtn.addEventListener('click', () => prepareCameraMode('arrival'));
    }
    if (departureBtn) {
      departureBtn.addEventListener('click', () => prepareCameraMode('departure'));
    }

    if (takePhotoBtn) {
      takePhotoBtn.addEventListener('click', capturePhoto);
      takePhotoBtn.disabled = true;
    }

    if (cancelBtn) {
      cancelBtn.addEventListener('click', stopCamera);
      cancelBtn.disabled = true;
    }

    if (retakeBtn) {
      retakeBtn.addEventListener('click', () => {
        setPreviewVisible(false);
        setCameraVisible(true);
        capturedPhotoBase64 = null;
        requestCameraPermissionAndStart();
      });
    }

    if (submitBtn) {
      submitBtn.addEventListener('click', submitReport);
    }

    const fileChooseBtn = document.getElementById('fileChooseBtn');
    const fileCancelBtn = document.getElementById('fileCancelBtn');
    const fileInputPhoto = document.getElementById('fileInputPhoto');

    if (fileChooseBtn && fileInputPhoto) {
      fileChooseBtn.addEventListener('click', () => fileInputPhoto.click());
    }

    if (fileCancelBtn) {
      fileCancelBtn.addEventListener('click', () => setFileVisible(false));
    }

    if (fileInputPhoto) {
      fileInputPhoto.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = function(ev) {
          capturedPhotoBase64 = ev.target.result;
          setCameraVisible(false);
          setFileVisible(false);
          setPreviewVisible(true);
          document.getElementById('capturedPhoto').src = capturedPhotoBase64;
        };
        reader.readAsDataURL(file);
      });
    }
  }

  function startCamera() {
    const cameraPreview = document.getElementById('cameraPreview');
    const videoElem = document.getElementById('cameraVideo');
    const takePhotoBtn = document.getElementById('takePicture');
    const cancelBtn = document.getElementById('cancelCamera');
    const statusEl = document.getElementById('cameraStatus');

    if (!cameraPreview || !videoElem || !statusEl) return;
    cameraPreview.style.display = 'block';

    const secure = window.location.protocol === 'https:' || window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
    if (!secure) {
      statusEl.textContent = 'Camera requires HTTPS or localhost. Use file upload fallback.';
      document.getElementById('fileInputContainer').style.display = 'block';
      return;
    }

    statusEl.textContent = 'Requesting camera permission...';
    const constraints = {
      video: {
        width: { ideal: 1280 },
        height: { ideal: 720 },
        facingMode: { ideal: 'environment' }
      },
      audio: false
    };

    function enableUI() {
      if (takePhotoBtn) takePhotoBtn.disabled = false;
      if (cancelBtn) cancelBtn.disabled = false;
      if (statusEl) statusEl.textContent = 'Camera ready. Snap photo now.';
    }

    function handleFailure(reason) {
      console.warn('Camera init failed:', reason);
      statusEl.textContent = 'Camera access denied or unavailable. Use file upload fallback.';
      document.getElementById('cameraPreview').style.display = 'none';
      document.getElementById('fileInputContainer').style.display = 'block';
    }

    const tryGetUserMedia = constraints => navigator.mediaDevices.getUserMedia(constraints);

    tryGetUserMedia(constraints)
      .then(s => {
        stream = s;
        videoElem.srcObject = stream;
        videoElem.muted = true;
        videoElem.setAttribute('playsinline', '');
        return videoElem.play().catch(() => {});
      })
      .then(() => enableUI())
      .catch(() => {
        return tryGetUserMedia({ video: true, audio: false })
          .then(s2 => {
            stream = s2;
            videoElem.srcObject = stream;
            videoElem.muted = true;
            videoElem.setAttribute('playsinline', '');
            return videoElem.play().catch(() => {});
          })
          .then(() => enableUI())
          .catch(handleFailure);
      });
  }

  function capturePhoto() {
    const video = document.getElementById('cameraVideo');
    const canvas = document.getElementById('cameraCanvas');
    if (!video || !canvas) return;

    const ctx = canvas.getContext('2d');
    const width = video.videoWidth || 1280;
    const height = video.videoHeight || Math.round(width * 3 / 4);
    canvas.width = width;
    canvas.height = height;

    try {
      ctx.drawImage(video, 0, 0, width, height);
    } catch (e) {
      setTimeout(capturePhoto, 150);
      return;
    }

    capturedPhotoBase64 = canvas.toDataURL('image/jpeg', 0.8);
    document.getElementById('cameraPreview').style.display = 'none';
    document.getElementById('photoPreview').style.display = 'block';
    document.getElementById('capturedPhoto').src = capturedPhotoBase64;
    stopCamera();
  }

  function stopCamera() {
    if (stream) {
      stream.getTracks().forEach(track => track.stop());
      stream = null;
      const videoElem = document.getElementById('cameraVideo');
      if (videoElem) videoElem.srcObject = null;
    }
    const statusEl = document.getElementById('cameraStatus');
    if (statusEl) statusEl.textContent = 'Camera stopped.';
    document.getElementById('cameraPreview').style.display = 'none';
  }

  function submitReport() {
    if (!currentReportType || !currentDutyId || !capturedPhotoBase64) {
      alert('Please capture a valid photo and try again.');
      return;
    }

    if (!navigator.geolocation) {
      alert('Geolocation is required for report submission.');
      return;
    }

    navigator.geolocation.getCurrentPosition(pos => {
      const lat = pos.coords.latitude;
      const lng = pos.coords.longitude;

      const payload = {
        action: 'submit_' + currentReportType,
        duty_id: currentDutyId,
        latitude: lat,
        longitude: lng,
        image: capturedPhotoBase64
      };

      const submitBtn = document.getElementById('submitReport');
      submitBtn.disabled = true;
      submitBtn.textContent = 'Uploading...';

      fetch('../backend/api_reports.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      })
      .then(r => r.json())
      .then(data => {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Submit Report';
        if (!data.success) {
          throw new Error(data.error || 'Report failed');
        }

        alert(data.message + (data.distance ? '\nDistance: ' + data.distance + 'm' : ''));
        document.getElementById('photoPreview').style.display = 'none';
        capturedPhotoBase64 = null;

        // Auto checkin/checkout update
        if (currentReportType === 'arrival') {
          fetch('./dashboard.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=checkin'
          }).finally(() => setTimeout(() => location.reload(), 900));
        } else if (currentReportType === 'departure') {
          fetch('./dashboard.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=checkout'
          }).finally(() => setTimeout(() => location.reload(), 900));
        } else {
          setTimeout(() => location.reload(), 900);
        }
      })
      .catch(err => {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Submit Report';
        alert('Submission failed: ' + err.message);
      });
    }, err => {
      alert('Geolocation error: ' + err.message);
    }, {
      enableHighAccuracy:true,
      maximumAge:30000,
      timeout:20000
    });
  }

  function requestCameraPermissionAndStart() {
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
      alert('Camera access not supported. Use file upload fallback.');
      document.getElementById('fileInputContainer').style.display = 'block';
      return;
    }

    if (navigator.permissions && navigator.permissions.query) {
      navigator.permissions.query({ name: 'camera' }).then(function(permissionStatus) {
        if (permissionStatus.state === 'denied') {
          alert('Camera permission denied. Please enable it in your browser settings.');
          document.getElementById('fileInputContainer').style.display = 'block';
          return;
        }
        startCamera();
      }).catch(function(e) {
        console.warn('Permissions API query unavailable:', e);
        startCamera();
      });
    } else {
      startCamera();
    }
  }
</script>
</body>
</html>