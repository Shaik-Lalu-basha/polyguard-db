<?php
require_once __DIR__ . '/backend/db.php';
require_once __DIR__ . '/backend/auth.php';

$isLoggedIn = isLoggedIn();
$user_info  = null;
$role       = null;

if ($isLoggedIn) {
    $userId = $_SESSION['user']['user_id'];
    $role   = $_SESSION['user']['role'];
    $stmt   = $pdo->prepare("SELECT * FROM users WHERE user_id=?");
    $stmt->execute([$userId]);
    $user_info = $stmt->fetch();
}

if (isset($_POST['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>POLYGUARD AI — Smart Bandobusth System</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=JetBrains+Mono:wght@400;600;700&family=Playfair+Display:ital,wght@0,700;1,400&display=swap" rel="stylesheet">

  <style>
    /* ════════════════════════════════════════════════════
       CSS VARIABLES — Color System
    ════════════════════════════════════════════════════ */
    :root {
      /* Core Navy Palette */
      --navy-950:  #050d1a;
      --navy-900:  #0b1628;
      --navy-800:  #0f1f3d;
      --navy-700:  #112240;
      --navy-600:  #1a3358;
      --navy-500:  #234070;
      --navy-400:  #2d5494;

      /* Gold Accent Palette */
      --gold-900:  #78350f;
      --gold-700:  #92400e;
      --gold-500:  #c9a84c;
      --gold-400:  #d4b466;
      --gold-300:  #e8c97a;
      --gold-200:  #f5e0a0;
      --gold-100:  #fdf3d7;

      /* Semantic Colors */
      --green-500: #10b981;
      --green-400: #34d399;
      --green-100: #ecfdf5;
      --red-500:   #ef4444;
      --red-400:   #f87171;
      --red-100:   #fef2f2;
      --blue-600:  #2563eb;
      --blue-500:  #3b82f6;
      --blue-300:  #93c5fd;
      --blue-100:  #eff6ff;
      --amber-500: #f59e0b;
      --purple-500:#8b5cf6;

      /* Neutral */
      --white:     #ffffff;
      --off-white: #f7f8fb;
      --gray-100:  #f1f5f9;
      --gray-200:  #e2e8f0;
      --gray-300:  #cbd5e1;
      --gray-400:  #94a3b8;
      --gray-500:  #64748b;
      --gray-700:  #374151;
      --gray-900:  #0f172a;

      /* Radii */
      --r-sm:  8px;
      --r-md:  14px;
      --r-lg:  20px;
      --r-xl:  28px;

      /* Shadows */
      --sh-sm:  0 1px 3px rgba(0,0,0,.07);
      --sh-md:  0 4px 20px rgba(11,22,40,.10);
      --sh-lg:  0 12px 48px rgba(11,22,40,.18);
      --sh-xl:  0 24px 80px rgba(11,22,40,.28);
      --sh-gold:0 8px 32px rgba(201,168,76,.25);
    }

    /* ════════════════════════════════════════════════════
       RESET & BASE
    ════════════════════════════════════════════════════ */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    html { scroll-behavior: smooth; font-size: 16px; }
    body {
      font-family: 'DM Sans', sans-serif;
      background: var(--navy-950);
      color: var(--white);
      overflow-x: hidden;
      min-height: 100vh;
    }

    /* ════════════════════════════════════════════════════
       ANIMATED BACKGROUND CANVAS
    ════════════════════════════════════════════════════ */
    #bg-canvas {
      position: fixed; top: 0; left: 0;
      width: 100%; height: 100%;
      z-index: 0; pointer-events: none;
      opacity: .55;
    }

    /* ════════════════════════════════════════════════════
       NAVBAR
    ════════════════════════════════════════════════════ */
    .navbar {
      position: fixed; top: 0; left: 0; right: 0;
      z-index: 500;
      height: 68px;
      padding: 0 40px;
      background: rgba(5,13,26,.85);
      backdrop-filter: blur(18px) saturate(1.4);
      -webkit-backdrop-filter: blur(18px) saturate(1.4);
      border-bottom: 1px solid rgba(201,168,76,.12);
      display: flex; align-items: center; justify-content: space-between;
    }
    .navbar::after {
      content: '';
      position: absolute; bottom: 0; left: 0; right: 0; height: 1px;
      background: linear-gradient(90deg,
        transparent 0%,
        rgba(201,168,76,.0) 10%,
        var(--gold-500) 30%,
        var(--gold-300) 50%,
        var(--gold-500) 70%,
        rgba(201,168,76,.0) 90%,
        transparent 100%);
    }

    .nav-brand { display: flex; align-items: center; gap: 14px; }
    .nav-logo-mark {
      width: 40px; height: 40px; border-radius: 10px;
      background: linear-gradient(135deg, var(--gold-500), var(--gold-300));
      display: grid; place-items: center;
      font-family: 'Bebas Neue'; font-size: 15px; letter-spacing: 1px;
      color: var(--navy-900);
      box-shadow: var(--sh-gold);
      flex-shrink: 0;
    }
    .nav-words { display: flex; flex-direction: column; }
    .nav-wordmark {
      font-family: 'Bebas Neue'; font-size: 22px; letter-spacing: 3px;
      color: var(--white); line-height: 1;
    }
    .nav-wordmark em { color: var(--gold-500); font-style: normal; }
    .nav-tagline {
      font-family: 'JetBrains Mono'; font-size: 10px; letter-spacing: 1.5px;
      color: var(--gray-400); line-height: 1;
    }

    .nav-links { display: flex; align-items: center; gap: 4px; }
    .nav-link {
      font-size: 13px; font-weight: 500; color: var(--gray-400);
      text-decoration: none; padding: 8px 14px; border-radius: var(--r-sm);
      transition: color .2s, background .2s;
    }
    .nav-link:hover { color: var(--white); background: rgba(255,255,255,.06); }

    .nav-right { display: flex; align-items: center; gap: 12px; }

    /* User info in nav (when logged in) */
    .nav-user-pill {
      display: flex; align-items: center; gap: 10px;
      background: rgba(255,255,255,.05);
      border: 1px solid rgba(201,168,76,.2);
      padding: 6px 14px 6px 8px;
      border-radius: 30px;
    }
    .nav-avatar {
      width: 30px; height: 30px; border-radius: 50%;
      background: linear-gradient(135deg, var(--navy-500), var(--blue-600));
      display: grid; place-items: center;
      font-family: 'Bebas Neue'; font-size: 13px; color: #fff;
      border: 1.5px solid rgba(201,168,76,.4);
      flex-shrink: 0;
    }
    .nav-user-text { display: flex; flex-direction: column; }
    .nav-user-name { font-size: 12px; font-weight: 600; color: #e2e8f0; line-height: 1.2; }
    .nav-user-role { font-size: 10px; color: var(--gold-300); font-family: 'JetBrains Mono'; letter-spacing: .5px; }

    .btn-primary {
      display: inline-flex; align-items: center; gap: 8px;
      background: linear-gradient(135deg, var(--gold-500), var(--gold-300));
      color: var(--navy-900); padding: 10px 22px; border-radius: 30px;
      font-weight: 700; font-size: 13px; text-decoration: none;
      border: none; cursor: pointer; font-family: 'DM Sans';
      box-shadow: var(--sh-gold);
      transition: transform .2s, box-shadow .2s, filter .2s;
      letter-spacing: .3px;
    }
    .btn-primary:hover { transform: translateY(-2px); filter: brightness(1.08); box-shadow: 0 12px 36px rgba(201,168,76,.35); }

    .btn-outline {
      display: inline-flex; align-items: center; gap: 8px;
      background: transparent;
      border: 1.5px solid rgba(201,168,76,.35);
      color: var(--gold-300); padding: 9px 20px; border-radius: 30px;
      font-size: 13px; font-weight: 500; text-decoration: none;
      cursor: pointer; font-family: 'DM Sans';
      transition: all .2s;
    }
    .btn-outline:hover { background: var(--gold-500); color: var(--navy-900); border-color: var(--gold-500); transform: translateY(-1px); }

    .btn-ghost {
      background: transparent; border: none;
      color: var(--gray-400); padding: 8px 14px;
      border-radius: var(--r-sm); font-size: 13px;
      cursor: pointer; font-family: 'DM Sans';
      transition: color .2s, background .2s;
    }
    .btn-ghost:hover { color: var(--white); background: rgba(255,255,255,.06); }

    /* ════════════════════════════════════════════════════
       HERO SECTION
    ════════════════════════════════════════════════════ */
    .hero-section {
      min-height: 100vh;
      display: flex; align-items: center; justify-content: center;
      padding: 120px 40px 80px;
      position: relative; z-index: 1;
      overflow: hidden;
    }

    .hero-glow-1 {
      position: absolute; top: 10%; left: 5%;
      width: 500px; height: 500px; border-radius: 50%;
      background: radial-gradient(circle, rgba(201,168,76,.08) 0%, transparent 65%);
      filter: blur(40px); animation: driftA 12s ease-in-out infinite;
    }
    .hero-glow-2 {
      position: absolute; bottom: 5%; right: 5%;
      width: 400px; height: 400px; border-radius: 50%;
      background: radial-gradient(circle, rgba(59,130,246,.07) 0%, transparent 65%);
      filter: blur(50px); animation: driftB 15s ease-in-out infinite;
    }
    .hero-glow-3 {
      position: absolute; top: 40%; left: 50%;
      width: 600px; height: 300px; border-radius: 50%;
      background: radial-gradient(circle, rgba(16,185,129,.04) 0%, transparent 65%);
      filter: blur(60px); transform: translateX(-50%);
      animation: driftC 18s ease-in-out infinite;
    }

    .hero-inner { max-width: 1200px; width: 100%; display: grid; grid-template-columns: 1fr 1fr; gap: 80px; align-items: center; position: relative; z-index: 1; }

    .hero-content {}
    .hero-eyebrow {
      display: inline-flex; align-items: center; gap: 8px;
      background: rgba(201,168,76,.1);
      border: 1px solid rgba(201,168,76,.25);
      padding: 6px 16px; border-radius: 30px;
      font-family: 'JetBrains Mono'; font-size: 11px; letter-spacing: 2px;
      color: var(--gold-300); margin-bottom: 24px;
      animation: fadeSlideUp .8s ease .1s both;
    }
    .pulse-dot {
      width: 6px; height: 6px; border-radius: 50%;
      background: var(--green-500);
      position: relative; display: inline-block;
    }
    .pulse-dot::after {
      content: ''; position: absolute; top: -4px; left: -4px;
      width: 14px; height: 14px; border-radius: 50%;
      background: rgba(16,185,129,.25);
      animation: ripple 2s ease infinite;
    }

    .hero-title {
      font-family: 'Bebas Neue'; font-size: clamp(52px, 7vw, 86px);
      letter-spacing: 4px; line-height: .95;
      margin-bottom: 24px;
      animation: fadeSlideUp .8s ease .2s both;
    }
    .hero-title .hl-gold { color: var(--gold-500); }
    .hero-title .hl-outline {
      -webkit-text-stroke: 2px var(--gold-500);
      color: transparent;
    }

    .hero-desc {
      font-size: 16px; line-height: 1.75; color: rgba(255,255,255,.6);
      max-width: 480px; margin-bottom: 36px;
      animation: fadeSlideUp .8s ease .3s both;
    }

    .hero-cta { display: flex; gap: 14px; flex-wrap: wrap; animation: fadeSlideUp .8s ease .4s both; }

    .hero-stats {
      display: flex; gap: 32px; margin-top: 48px;
      animation: fadeSlideUp .8s ease .5s both;
    }
    .hs-item {}
    .hs-value { font-family: 'Bebas Neue'; font-size: 36px; letter-spacing: 2px; color: var(--gold-500); line-height: 1; }
    .hs-label { font-size: 12px; color: var(--gray-400); margin-top: 2px; }

    /* Hero Visual — Live Tracking Widget */
    .hero-visual { position: relative; animation: fadeSlideRight .9s ease .3s both; }
    .tracking-frame {
      background: rgba(11,22,40,.8);
      border: 1px solid rgba(201,168,76,.15);
      border-radius: var(--r-xl);
      overflow: hidden;
      box-shadow: var(--sh-xl), inset 0 1px 0 rgba(255,255,255,.05);
    }
    .tf-header {
      background: rgba(5,13,26,.6);
      padding: 14px 20px;
      display: flex; align-items: center; justify-content: space-between;
      border-bottom: 1px solid rgba(201,168,76,.1);
    }
    .tf-title { font-family: 'JetBrains Mono'; font-size: 11px; letter-spacing: 2px; color: var(--gold-300); }
    .tf-dots { display: flex; gap: 6px; }
    .tf-dot { width: 10px; height: 10px; border-radius: 50%; }

    .map-container {
      position: relative; height: 300px;
      background: linear-gradient(145deg, #0a1929 0%, #0d2137 40%, #071420 100%);
      overflow: hidden;
    }
    /* Grid lines */
    .map-container::before {
      content: '';
      position: absolute; inset: 0;
      background-image:
        linear-gradient(rgba(201,168,76,.04) 1px, transparent 1px),
        linear-gradient(90deg, rgba(201,168,76,.04) 1px, transparent 1px);
      background-size: 40px 40px;
    }

    /* Map circles / zones */
    .zone {
      position: absolute; border-radius: 50%;
      border: 1.5px solid; animation: zonePulse 3s ease-in-out infinite;
    }
    .zone-1 { width: 120px; height: 120px; top: 60px; left: 60px; border-color: rgba(16,185,129,.4); background: rgba(16,185,129,.06); animation-delay: 0s; }
    .zone-2 { width: 90px;  height: 90px;  top: 90px; right: 80px; border-color: rgba(59,130,246,.4); background: rgba(59,130,246,.06); animation-delay: .8s; }
    .zone-3 { width: 70px;  height: 70px;  bottom: 40px; left: 50%; border-color: rgba(245,158,11,.4); background: rgba(245,158,11,.06); animation-delay: 1.6s; }

    /* Officer dots */
    .officer-dot {
      position: absolute; width: 12px; height: 12px; border-radius: 50%;
      border: 2px solid rgba(255,255,255,.5);
      animation: officerMove 6s ease-in-out infinite;
    }
    .od-green  { background: var(--green-500); box-shadow: 0 0 8px var(--green-500); top: 95px;  left: 110px; animation-delay: 0s; }
    .od-blue   { background: var(--blue-500);  box-shadow: 0 0 8px var(--blue-500);  top: 120px; right: 110px; animation-delay: 1s; }
    .od-gold   { background: var(--gold-500);  box-shadow: 0 0 8px var(--gold-500);  bottom: 60px; left: 53%;  animation-delay: 2s; }
    .od-red    { background: var(--red-500);   box-shadow: 0 0 8px var(--red-500);   top: 50px;  right: 50px; animation-delay: .5s; }

    /* Connection lines */
    .map-svg { position: absolute; inset: 0; width: 100%; height: 100%; }

    .tf-body { padding: 16px 20px; }
    .tf-row { display: flex; gap: 10px; margin-bottom: 10px; }
    .tf-stat {
      flex: 1; background: rgba(255,255,255,.04);
      border: 1px solid rgba(255,255,255,.08);
      border-radius: var(--r-sm); padding: 10px 12px;
    }
    .tf-stat-label { font-family: 'JetBrains Mono'; font-size: 9px; letter-spacing: 1.5px; color: var(--gray-400); margin-bottom: 4px; }
    .tf-stat-value { font-family: 'Bebas Neue'; font-size: 22px; letter-spacing: 1px; }
    .tv-green { color: var(--green-400); }
    .tv-gold  { color: var(--gold-400); }
    .tv-blue  { color: var(--blue-300); }
    .tv-red   { color: var(--red-400); }

    .live-feed {
      background: rgba(5,13,26,.4); border-radius: var(--r-sm);
      padding: 10px 14px; max-height: 88px; overflow: hidden;
    }
    .lf-item { display: flex; align-items: center; gap: 8px; margin-bottom: 6px; font-size: 11px; color: var(--gray-400); font-family: 'JetBrains Mono'; }
    .lf-item:last-child { margin-bottom: 0; }
    .lf-dot { width: 5px; height: 5px; border-radius: 50%; flex-shrink: 0; }
    .lf-time { color: var(--gold-400); margin-left: auto; }

    /* ════════════════════════════════════════════════════
       COLOR PALETTE SECTION
    ════════════════════════════════════════════════════ */
    .section {
      position: relative; z-index: 1;
      padding: 100px 40px;
    }
    .section-inner { max-width: 1200px; margin: 0 auto; }

    .section-tag {
      display: inline-flex; align-items: center; gap: 8px;
      font-family: 'JetBrains Mono'; font-size: 11px; letter-spacing: 2px;
      color: var(--gold-400); text-transform: uppercase;
      margin-bottom: 16px;
    }
    .section-tag::before { content: ''; display: block; width: 28px; height: 1px; background: var(--gold-500); }

    .section-title {
      font-family: 'Bebas Neue'; font-size: clamp(36px, 5vw, 58px);
      letter-spacing: 3px; line-height: 1;
      margin-bottom: 16px;
    }
    .section-desc { font-size: 15px; color: rgba(255,255,255,.55); max-width: 560px; line-height: 1.7; }

    /* Divider */
    .section-divider {
      height: 1px; max-width: 1200px; margin: 0 auto;
      background: linear-gradient(90deg,
        transparent 0%,
        rgba(201,168,76,.08) 15%,
        rgba(201,168,76,.25) 50%,
        rgba(201,168,76,.08) 85%,
        transparent 100%);
    }

    /* ════════════════════════════════════════════════════
       ABOUT SECTION
    ════════════════════════════════════════════════════ */
    .about-grid {
      display: grid; grid-template-columns: 1fr 1fr;
      gap: 80px; align-items: center; margin-top: 56px;
    }

    .about-card {
      background: rgba(255,255,255,.03);
      border: 1px solid rgba(255,255,255,.07);
      border-radius: var(--r-xl);
      padding: 40px;
      position: relative; overflow: hidden;
    }
    .about-card::before {
      content: '';
      position: absolute; top: -1px; left: 40px; right: 40px; height: 2px;
      background: linear-gradient(90deg, transparent, var(--gold-500), transparent);
    }

    .about-icon-stack {
      display: flex; gap: 12px; margin-bottom: 28px;
    }
    .ai-icon {
      width: 50px; height: 50px; border-radius: var(--r-md);
      display: grid; place-items: center; font-size: 22px;
      background: rgba(201,168,76,.1); border: 1px solid rgba(201,168,76,.2);
    }

    .about-feature-list { display: flex; flex-direction: column; gap: 16px; }
    .afl-item { display: flex; align-items: flex-start; gap: 14px; }
    .afl-bullet {
      width: 28px; height: 28px; border-radius: 50%;
      background: rgba(201,168,76,.12); border: 1px solid rgba(201,168,76,.25);
      display: grid; place-items: center; font-size: 13px; flex-shrink: 0;
      margin-top: 2px;
    }
    .afl-text {}
    .afl-title { font-size: 14px; font-weight: 600; color: var(--white); margin-bottom: 3px; }
    .afl-desc  { font-size: 13px; color: rgba(255,255,255,.45); line-height: 1.5; }

    /* ════════════════════════════════════════════════════
       POLICE DEPARTMENT SECTION
    ════════════════════════════════════════════════════ */
    .dept-section { background: rgba(5,13,26,.6); }
    .dept-section::before {
      content: '';
      position: absolute; inset: 0;
      background: radial-gradient(ellipse 60% 80% at 80% 50%, rgba(59,130,246,.04) 0%, transparent 70%);
    }

    .dept-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 40px; margin-bottom: 56px; }
    .dept-badge-group {
      display: flex; flex-direction: column; gap: 10px; flex-shrink: 0; padding-top: 8px;
    }
    .dept-badge {
      display: inline-flex; align-items: center; gap: 8px;
      font-size: 12px; font-weight: 600; padding: 7px 16px; border-radius: 30px;
      border: 1px solid;
    }
    .db-authority { background: rgba(201,168,76,.1); border-color: rgba(201,168,76,.3); color: var(--gold-300); }
    .db-verified  { background: rgba(16,185,129,.1); border-color: rgba(16,185,129,.3); color: #6ee7b7; }

    .dept-roles {
      display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;
    }
    .role-card {
      background: rgba(255,255,255,.04);
      border: 1px solid rgba(255,255,255,.08);
      border-radius: var(--r-lg);
      padding: 28px 24px;
      position: relative; overflow: hidden;
      transition: transform .3s ease, border-color .3s ease, box-shadow .3s ease;
      cursor: default;
    }
    .role-card:hover {
      transform: translateY(-6px);
      box-shadow: 0 20px 48px rgba(0,0,0,.3);
    }
    .role-card::after {
      content: '';
      position: absolute; bottom: 0; left: 0; right: 0; height: 3px;
      transition: opacity .3s ease;
    }
    .rc-admin { border-color: rgba(201,168,76,.15); }
    .rc-admin::after   { background: linear-gradient(90deg, var(--gold-500), var(--gold-300)); }
    .rc-admin:hover    { border-color: rgba(201,168,76,.4); }
    .rc-control { border-color: rgba(59,130,246,.15); }
    .rc-control::after { background: linear-gradient(90deg, #2563eb, #60a5fa); }
    .rc-control:hover  { border-color: rgba(59,130,246,.4); }
    .rc-police  { border-color: rgba(16,185,129,.15); }
    .rc-police::after  { background: linear-gradient(90deg, #059669, #34d399); }
    .rc-police:hover   { border-color: rgba(16,185,129,.4); }

    .rc-icon {
      font-size: 32px; margin-bottom: 16px;
      display: block; filter: drop-shadow(0 4px 8px rgba(0,0,0,.3));
    }
    .rc-title { font-family: 'Bebas Neue'; font-size: 22px; letter-spacing: 2px; margin-bottom: 8px; }
    .rc-sub { font-size: 12.5px; color: rgba(255,255,255,.45); margin-bottom: 18px; line-height: 1.5; }
    .rc-perms { display: flex; flex-direction: column; gap: 7px; }
    .perm-item { display: flex; align-items: center; gap: 8px; font-size: 12px; color: rgba(255,255,255,.6); }
    .perm-item::before { content: '▸'; font-size: 10px; opacity: .5; }

    /* ════════════════════════════════════════════════════
       PLATFORM DIVISIONS / FEATURES
    ════════════════════════════════════════════════════ */
    .platform-grid {
      display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; margin-top: 56px;
    }
    .plat-card {
      background: rgba(255,255,255,.03);
      border: 1px solid rgba(255,255,255,.07);
      border-radius: var(--r-xl);
      padding: 36px;
      position: relative; overflow: hidden;
      transition: transform .3s, border-color .3s, box-shadow .3s;
    }
    .plat-card:hover { transform: translateY(-5px); box-shadow: var(--sh-lg); }
    .plat-card.pc-wide { grid-column: 1 / -1; }

    .pc-accent {
      position: absolute; top: 0; left: 0; right: 0; height: 2px;
      border-radius: var(--r-xl) var(--r-xl) 0 0;
    }
    .pa-gold   { background: linear-gradient(90deg, transparent, var(--gold-500), transparent); }
    .pa-blue   { background: linear-gradient(90deg, transparent, var(--blue-500), transparent); }
    .pa-green  { background: linear-gradient(90deg, transparent, var(--green-500), transparent); }
    .pa-purple { background: linear-gradient(90deg, transparent, var(--purple-500), transparent); }
    .pa-red    { background: linear-gradient(90deg, transparent, var(--red-500), transparent); }

    .pc-icon-wrap {
      width: 56px; height: 56px; border-radius: var(--r-md);
      display: grid; place-items: center; font-size: 26px;
      margin-bottom: 20px;
      position: relative;
    }
    .pc-icon-wrap::after {
      content: ''; position: absolute;
      bottom: -6px; left: 6px; right: 6px; height: 6px;
      border-radius: 0 0 8px 8px; filter: blur(6px); opacity: .5;
    }
    .pi-gold   { background: linear-gradient(135deg, #fef3c7, #fde68a); }
    .pi-gold::after   { background: var(--gold-500); }
    .pi-blue   { background: linear-gradient(135deg, #eff6ff, #bfdbfe); }
    .pi-blue::after   { background: var(--blue-500); }
    .pi-green  { background: linear-gradient(135deg, #ecfdf5, #a7f3d0); }
    .pi-green::after  { background: var(--green-500); }
    .pi-purple { background: linear-gradient(135deg, #f5f3ff, #ddd6fe); }
    .pi-purple::after { background: var(--purple-500); }
    .pi-red    { background: linear-gradient(135deg, #fef2f2, #fecaca); }
    .pi-red::after    { background: var(--red-500); }

    .pc-title { font-size: 18px; font-weight: 700; color: var(--white); margin-bottom: 8px; }
    .pc-desc  { font-size: 13.5px; color: rgba(255,255,255,.45); line-height: 1.65; margin-bottom: 20px; }
    .pc-tags  { display: flex; gap: 8px; flex-wrap: wrap; }
    .pc-tag {
      font-family: 'JetBrains Mono'; font-size: 10px; letter-spacing: 1px;
      padding: 4px 10px; border-radius: 5px;
      background: rgba(255,255,255,.06);
      border: 1px solid rgba(255,255,255,.1);
      color: rgba(255,255,255,.5);
    }

    /* Wide card layout */
    .pc-wide-inner { display: grid; grid-template-columns: 1fr 2fr; gap: 40px; align-items: center; }
    .pc-wide-right {}
    .pc-module-list { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .pml-item {
      display: flex; align-items: center; gap: 10px;
      padding: 12px 14px;
      background: rgba(255,255,255,.04);
      border: 1px solid rgba(255,255,255,.07);
      border-radius: var(--r-sm);
      font-size: 12.5px; color: rgba(255,255,255,.65);
    }
    .pml-icon { font-size: 15px; }

    /* ════════════════════════════════════════════════════
       COLOR PALETTE SHOWCASE
    ════════════════════════════════════════════════════ */
    .palette-section {
      background: rgba(9,17,32,.5);
    }

    .palette-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-top: 56px; }
    .palette-group {}
    .pg-name {
      font-family: 'JetBrains Mono'; font-size: 11px; letter-spacing: 2px;
      color: var(--gray-400); text-transform: uppercase;
      margin-bottom: 14px;
    }
    .pg-swatches { display: flex; flex-direction: column; gap: 6px; }
    .swatch {
      height: 44px; border-radius: var(--r-sm);
      display: flex; align-items: center; justify-content: space-between;
      padding: 0 14px;
      transition: transform .15s ease;
      cursor: default;
    }
    .swatch:hover { transform: translateX(4px); }
    .sw-name { font-size: 12px; font-weight: 600; }
    .sw-hex  { font-family: 'JetBrains Mono'; font-size: 11px; opacity: .65; }

    /* Typography Demo */
    .typo-demo {
      margin-top: 56px;
      background: rgba(255,255,255,.02);
      border: 1px solid rgba(255,255,255,.06);
      border-radius: var(--r-xl); padding: 40px;
    }
    .td-title { font-size: 13px; font-weight: 600; color: var(--gray-400); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 28px; }
    .td-samples { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 32px; }
    .td-sample {}
    .ts-label { font-family: 'JetBrains Mono'; font-size: 10px; letter-spacing: 1.5px; color: var(--gray-500); margin-bottom: 8px; }
    .ts-f1 { font-family: 'Bebas Neue'; font-size: 36px; letter-spacing: 3px; color: var(--gold-500); }
    .ts-f2 { font-family: 'DM Sans'; font-size: 18px; font-weight: 600; color: var(--white); }
    .ts-f3 { font-family: 'JetBrains Mono'; font-size: 14px; color: var(--green-400); }

    /* ════════════════════════════════════════════════════
       LIVE TRACKING SECTION
    ════════════════════════════════════════════════════ */
    .tracking-section { background: rgba(5,13,26,.7); }
    .tracking-section::before {
      content: '';
      position: absolute; inset: 0;
      background: radial-gradient(ellipse 50% 60% at 20% 60%, rgba(201,168,76,.04) 0%, transparent 70%);
    }

    .tracking-layout { display: grid; grid-template-columns: 1fr 380px; gap: 32px; margin-top: 56px; align-items: start; }

    /* Big map */
    .main-map {
      background: rgba(10,25,41,.9);
      border: 1px solid rgba(201,168,76,.12);
      border-radius: var(--r-xl); overflow: hidden;
      box-shadow: var(--sh-lg);
    }
    .mm-header {
      padding: 16px 22px;
      background: rgba(5,13,26,.8);
      border-bottom: 1px solid rgba(201,168,76,.1);
      display: flex; align-items: center; justify-content: space-between;
    }
    .mm-title { font-family: 'Bebas Neue'; font-size: 16px; letter-spacing: 2px; color: var(--gold-300); }
    .mm-controls { display: flex; gap: 8px; }
    .mm-btn {
      font-family: 'JetBrains Mono'; font-size: 10px; letter-spacing: 1px;
      padding: 5px 10px; border-radius: 5px;
      background: rgba(255,255,255,.06); border: 1px solid rgba(255,255,255,.1);
      color: rgba(255,255,255,.5); cursor: pointer; transition: all .2s;
    }
    .mm-btn.active { background: rgba(201,168,76,.15); border-color: rgba(201,168,76,.4); color: var(--gold-300); }

    .big-map {
      height: 400px; position: relative;
      background: linear-gradient(160deg, #0a1929 0%, #0d2540 50%, #050e1a 100%);
      overflow: hidden;
    }
    .big-map::before {
      content: '';
      position: absolute; inset: 0;
      background-image:
        linear-gradient(rgba(201,168,76,.05) 1px, transparent 1px),
        linear-gradient(90deg, rgba(201,168,76,.05) 1px, transparent 1px);
      background-size: 50px 50px;
    }

    /* Large zones */
    .big-zone {
      position: absolute; border-radius: 50%;
      border: 1px solid; animation: zonePulse 4s ease-in-out infinite;
    }
    .bz-1 { width: 200px; height: 200px; top: 30px; left: 40px;  border-color: rgba(16,185,129,.25); background: rgba(16,185,129,.04); }
    .bz-2 { width: 160px; height: 160px; top: 60px; right: 60px; border-color: rgba(59,130,246,.25); background: rgba(59,130,246,.04); animation-delay: 1s; }
    .bz-3 { width: 130px; height: 130px; bottom: 60px; left: 50%; border-color: rgba(245,158,11,.25); background: rgba(245,158,11,.04); animation-delay: 2s; }
    .bz-4 { width: 100px; height: 100px; bottom: 80px; left: 30px; border-color: rgba(239,68,68,.25); background: rgba(239,68,68,.04); animation-delay: .5s; }

    /* Sector labels */
    .sector-label {
      position: absolute; font-family: 'JetBrains Mono'; font-size: 9px;
      letter-spacing: 1.5px; padding: 3px 8px; border-radius: 4px;
    }
    .sl-green  { top: 90px; left: 75px; background: rgba(16,185,129,.15); color: #6ee7b7; border: 1px solid rgba(16,185,129,.2); }
    .sl-blue   { top: 110px; right: 95px; background: rgba(59,130,246,.15); color: #93c5fd; border: 1px solid rgba(59,130,246,.2); }
    .sl-amber  { bottom: 88px; left: 54%; background: rgba(245,158,11,.15); color: #fcd34d; border: 1px solid rgba(245,158,11,.2); }
    .sl-red    { bottom: 108px; left: 43px; background: rgba(239,68,68,.15); color: #fca5a5; border: 1px solid rgba(239,68,68,.2); }

    /* Big officer dots */
    .big-officer {
      position: absolute; width: 14px; height: 14px; border-radius: 50%;
      border: 2px solid rgba(255,255,255,.6);
      cursor: pointer;
    }
    .big-officer::after {
      content: ''; position: absolute; top: -6px; left: -6px;
      width: 26px; height: 26px; border-radius: 50%;
      animation: officerRing 2s ease-out infinite;
    }
    .bo-1 { background: var(--green-500); top: 105px; left: 120px; animation: float1 5s ease-in-out infinite; }
    .bo-1::after { background: rgba(16,185,129,.2); }
    .bo-2 { background: var(--blue-500);  top: 120px; right: 140px; animation: float2 6s ease-in-out infinite; }
    .bo-2::after { background: rgba(59,130,246,.2); }
    .bo-3 { background: var(--gold-500);  bottom: 90px; left: 52%; animation: float3 7s ease-in-out infinite; }
    .bo-3::after { background: rgba(201,168,76,.2); }
    .bo-4 { background: var(--red-500);   top: 60px; right: 100px; animation: float1 4.5s ease-in-out infinite; }
    .bo-4::after { background: rgba(239,68,68,.2); }
    .bo-5 { background: var(--purple-500); bottom: 100px; left: 80px; animation: float2 5.5s ease-in-out infinite; }
    .bo-5::after { background: rgba(139,92,246,.2); }

    /* Officer tooltip */
    .bo-tooltip {
      position: absolute; bottom: 22px; left: 50%;
      transform: translateX(-50%);
      background: rgba(5,13,26,.95); border: 1px solid rgba(201,168,76,.25);
      padding: 6px 10px; border-radius: 6px; white-space: nowrap;
      font-family: 'JetBrains Mono'; font-size: 9px; color: var(--gold-300);
      pointer-events: none; display: none;
    }
    .big-officer:hover .bo-tooltip { display: block; }

    /* Map bottom bar */
    .mm-footer {
      padding: 12px 22px;
      background: rgba(5,13,26,.6);
      border-top: 1px solid rgba(255,255,255,.05);
      display: flex; gap: 20px;
    }
    .mmf-stat { display: flex; align-items: center; gap: 8px; font-size: 12px; color: var(--gray-400); }
    .mmf-dot { width: 8px; height: 8px; border-radius: 50%; }

    /* Side panel */
    .tracking-side { display: flex; flex-direction: column; gap: 16px; }

    .side-panel {
      background: rgba(11,22,40,.8);
      border: 1px solid rgba(201,168,76,.12);
      border-radius: var(--r-lg);
      overflow: hidden;
    }
    .sp-header {
      padding: 14px 18px;
      background: rgba(5,13,26,.5);
      border-bottom: 1px solid rgba(255,255,255,.05);
      font-family: 'JetBrains Mono'; font-size: 10px;
      letter-spacing: 1.5px; color: var(--gold-400); text-transform: uppercase;
    }
    .sp-body { padding: 14px 18px; }

    .sp-stat-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
    .ssg-item {
      background: rgba(255,255,255,.04);
      border: 1px solid rgba(255,255,255,.07);
      border-radius: var(--r-sm);
      padding: 12px;
    }
    .ssg-label { font-size: 9px; font-family: 'JetBrains Mono'; letter-spacing: 1px; color: var(--gray-500); text-transform: uppercase; margin-bottom: 4px; }
    .ssg-value { font-family: 'Bebas Neue'; font-size: 28px; letter-spacing: 1px; }

    .alert-feed { display: flex; flex-direction: column; gap: 8px; }
    .af-item {
      display: flex; align-items: flex-start; gap: 10px;
      padding: 10px 12px; border-radius: var(--r-sm);
      background: rgba(255,255,255,.03);
      border: 1px solid rgba(255,255,255,.06);
    }
    .af-icon { font-size: 14px; flex-shrink: 0; margin-top: 1px; }
    .af-content {}
    .af-title { font-size: 12px; font-weight: 500; color: var(--white); margin-bottom: 2px; }
    .af-sub { font-size: 11px; color: rgba(255,255,255,.4); font-family: 'JetBrains Mono'; }
    .af-time { font-size: 10px; color: var(--gray-500); font-family: 'JetBrains Mono'; margin-left: auto; white-space: nowrap; }

    /* Compliance bars */
    .comp-list { display: flex; flex-direction: column; gap: 12px; }
    .cl-item {}
    .cl-head { display: flex; justify-content: space-between; margin-bottom: 5px; }
    .cl-name { font-size: 12px; color: rgba(255,255,255,.6); }
    .cl-score { font-family: 'JetBrains Mono'; font-size: 12px; font-weight: 600; color: var(--gold-400); }
    .cl-bar { height: 5px; background: rgba(255,255,255,.08); border-radius: 3px; overflow: hidden; }
    .cl-fill { height: 100%; border-radius: 3px; transition: width 1.5s ease; }

    /* ════════════════════════════════════════════════════
       FRAMES / ANIMATION SHOWCASE
    ════════════════════════════════════════════════════ */
    .frames-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-top: 56px; }
    .frame-card {
      background: rgba(255,255,255,.03);
      border: 1px solid rgba(255,255,255,.07);
      border-radius: var(--r-lg);
      padding: 28px 24px;
      text-align: center;
      position: relative; overflow: hidden;
      transition: transform .3s, border-color .3s;
    }
    .frame-card:hover { transform: translateY(-4px); border-color: rgba(201,168,76,.25); }

    .anim-demo {
      width: 80px; height: 80px; margin: 0 auto 20px;
      display: grid; place-items: center;
    }
    .ad-spin {
      width: 60px; height: 60px; border-radius: 50%;
      border: 3px solid rgba(201,168,76,.2);
      border-top-color: var(--gold-500);
      animation: spin 1.5s linear infinite;
    }
    .ad-pulse {
      width: 50px; height: 50px; border-radius: 50%;
      background: rgba(16,185,129,.3);
      animation: bigPulse 2s ease-in-out infinite;
    }
    .ad-wave { width: 60px; height: 40px; display: flex; align-items: center; gap: 4px; }
    .wave-bar {
      flex: 1; background: var(--blue-500); border-radius: 2px;
      animation: waveAnim 1.2s ease-in-out infinite;
    }
    .wave-bar:nth-child(2) { animation-delay: .15s; }
    .wave-bar:nth-child(3) { animation-delay: .30s; }
    .wave-bar:nth-child(4) { animation-delay: .45s; }
    .wave-bar:nth-child(5) { animation-delay: .60s; }
    .ad-bounce {
      width: 20px; height: 20px; border-radius: 50%;
      background: var(--gold-500);
      animation: bounceAnim 1s ease-in-out infinite;
    }
    .ad-radar {
      width: 60px; height: 60px; border-radius: 50%;
      border: 2px solid rgba(16,185,129,.3);
      position: relative;
      overflow: hidden;
    }
    .ad-radar::after {
      content: '';
      position: absolute; top: 0; left: 50%; width: 50%; height: 100%;
      background: linear-gradient(90deg, transparent, rgba(16,185,129,.4));
      transform-origin: left;
      animation: radarScan 2s linear infinite;
    }
    .ad-morph {
      width: 50px; height: 50px;
      background: linear-gradient(135deg, var(--purple-500), var(--blue-500));
      border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%;
      animation: morphAnim 4s ease-in-out infinite;
    }

    .frame-title { font-size: 14px; font-weight: 600; color: var(--white); margin-bottom: 6px; }
    .frame-desc  { font-size: 12px; color: rgba(255,255,255,.4); line-height: 1.5; }

    /* ════════════════════════════════════════════════════
       CTA SECTION
    ════════════════════════════════════════════════════ */
    .cta-section {
      background: linear-gradient(135deg, var(--navy-900) 0%, var(--navy-700) 50%, var(--navy-600) 100%);
      position: relative; overflow: hidden;
    }
    .cta-section::before {
      content: '';
      position: absolute; top: -100px; right: -100px;
      width: 500px; height: 500px; border-radius: 50%;
      background: radial-gradient(circle, rgba(201,168,76,.08) 0%, transparent 65%);
    }
    .cta-inner { text-align: center; position: relative; z-index: 1; }
    .cta-title { font-family: 'Bebas Neue'; font-size: clamp(40px, 6vw, 72px); letter-spacing: 4px; margin-bottom: 20px; }
    .cta-title em { color: var(--gold-500); font-style: normal; }
    .cta-desc { font-size: 15px; color: rgba(255,255,255,.55); max-width: 520px; margin: 0 auto 40px; line-height: 1.7; }
    .cta-btns { display: flex; gap: 16px; justify-content: center; flex-wrap: wrap; }

    /* ════════════════════════════════════════════════════
       FOOTER
    ════════════════════════════════════════════════════ */
    .site-footer {
      background: var(--navy-950);
      border-top: 1px solid rgba(201,168,76,.1);
      padding: 60px 40px 40px;
      position: relative; z-index: 1;
    }
    .footer-inner { max-width: 1200px; margin: 0 auto; }
    .footer-top {
      display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 60px;
      padding-bottom: 48px;
      border-bottom: 1px solid rgba(255,255,255,.05);
    }
    .ft-brand {}
    .ft-logo {
      display: flex; align-items: center; gap: 12px; margin-bottom: 18px;
    }
    .ft-logo-mark {
      width: 38px; height: 38px; border-radius: 9px;
      background: linear-gradient(135deg, var(--gold-500), var(--gold-300));
      display: grid; place-items: center;
      font-family: 'Bebas Neue'; font-size: 14px; color: var(--navy-900);
    }
    .ft-wordmark { font-family: 'Bebas Neue'; font-size: 20px; letter-spacing: 2px; }
    .ft-wordmark em { color: var(--gold-500); font-style: normal; }
    .ft-desc { font-size: 13px; color: rgba(255,255,255,.35); line-height: 1.7; max-width: 280px; margin-bottom: 20px; }
    .ft-secure-badges { display: flex; gap: 8px; }
    .ft-badge {
      display: inline-flex; align-items: center; gap: 5px;
      font-size: 10px; font-weight: 600; padding: 5px 10px; border-radius: 5px;
      background: rgba(255,255,255,.05); border: 1px solid rgba(255,255,255,.1);
      color: rgba(255,255,255,.4);
    }

    .ft-col {}
    .ft-col-title { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1.5px; color: var(--gray-400); margin-bottom: 18px; }
    .ft-links { display: flex; flex-direction: column; gap: 10px; }
    .ft-link { font-size: 13px; color: rgba(255,255,255,.35); text-decoration: none; transition: color .2s; }
    .ft-link:hover { color: var(--gold-300); }

    .footer-bottom {
      display: flex; align-items: center; justify-content: space-between;
      padding-top: 28px; flex-wrap: wrap; gap: 12px;
    }
    .fb-copy { font-size: 12px; color: rgba(255,255,255,.25); font-family: 'JetBrains Mono'; }
    .fb-meta { display: flex; align-items: center; gap: 16px; }
    .fb-v { font-family: 'JetBrains Mono'; font-size: 11px; color: rgba(255,255,255,.25); }

    /* ════════════════════════════════════════════════════
       KEYFRAMES
    ════════════════════════════════════════════════════ */
    @keyframes fadeSlideUp   { from { opacity: 0; transform: translateY(24px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes fadeSlideRight{ from { opacity: 0; transform: translateX(30px); } to { opacity: 1; transform: translateX(0); } }
    @keyframes ripple        { 0% { transform: scale(1); opacity: .7; } 70% { transform: scale(2.5); opacity: 0; } 100% { transform: scale(1); opacity: 0; } }
    @keyframes officerRing   { 0% { transform: scale(1); opacity: .7; } 70% { transform: scale(2); opacity: 0; } 100% { opacity: 0; } }
    @keyframes driftA        { 0%,100% { transform: translate(0,0); } 50% { transform: translate(30px, -20px); } }
    @keyframes driftB        { 0%,100% { transform: translate(0,0); } 50% { transform: translate(-20px, 30px); } }
    @keyframes driftC        { 0%,100% { transform: translateX(-50%) scale(1); } 50% { transform: translateX(-50%) scale(1.1); } }
    @keyframes zonePulse     { 0%,100% { opacity: .6; transform: scale(1); } 50% { opacity: 1; transform: scale(1.03); } }
    @keyframes officerMove   { 0%,100% { transform: translate(0,0); } 33% { transform: translate(5px,-4px); } 66% { transform: translate(-4px,5px); } }
    @keyframes float1        { 0%,100% { transform: translate(0,0); } 50% { transform: translate(8px,-10px); } }
    @keyframes float2        { 0%,100% { transform: translate(0,0); } 50% { transform: translate(-10px,8px); } }
    @keyframes float3        { 0%,100% { transform: translate(0,0); } 50% { transform: translate(6px,10px); } }
    @keyframes spin          { to { transform: rotate(360deg); } }
    @keyframes bigPulse      { 0%,100% { transform: scale(.9); opacity: .7; } 50% { transform: scale(1.1); opacity: 1; } }
    @keyframes waveAnim      { 0%,100% { transform: scaleY(.4); } 50% { transform: scaleY(1.2); } }
    @keyframes bounceAnim    { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-20px); } }
    @keyframes radarScan     { to { transform: rotate(360deg); } }
    @keyframes morphAnim     {
      0%,100% { border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%; }
      25%  { border-radius: 58% 42% 75% 25% / 76% 46% 54% 24%; }
      50%  { border-radius: 50% 50% 33% 67% / 55% 27% 73% 45%; }
      75%  { border-radius: 33% 67% 58% 42% / 63% 68% 32% 37%; }
    }
    @keyframes countUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

    /* ════════════════════════════════════════════════════
       REVEAL ANIMATIONS (Intersection Observer)
    ════════════════════════════════════════════════════ */
    .reveal {
      opacity: 0; transform: translateY(30px);
      transition: opacity .7s ease, transform .7s ease;
    }
    .reveal.visible { opacity: 1; transform: translateY(0); }
    .reveal-left  { opacity: 0; transform: translateX(-30px); transition: opacity .7s ease, transform .7s ease; }
    .reveal-right { opacity: 0; transform: translateX(30px);  transition: opacity .7s ease, transform .7s ease; }
    .reveal-left.visible, .reveal-right.visible { opacity: 1; transform: translateX(0); }

    /* ════════════════════════════════════════════════════
       RESPONSIVE
    ════════════════════════════════════════════════════ */
    @media (max-width: 1024px) {
      .hero-inner    { grid-template-columns: 1fr; gap: 48px; }
      .about-grid    { grid-template-columns: 1fr; gap: 40px; }
      .dept-roles    { grid-template-columns: 1fr; }
      .platform-grid { grid-template-columns: 1fr; }
      .pc-wide .pc-wide-inner { grid-template-columns: 1fr; }
      .tracking-layout { grid-template-columns: 1fr; }
      .footer-top    { grid-template-columns: 1fr 1fr; }
    }
    @media (max-width: 768px) {
      .navbar { padding: 0 18px; }
      .nav-links { display: none; }
      .section { padding: 60px 20px; }
      .hero-section { padding: 100px 20px 60px; }
      .palette-grid { grid-template-columns: 1fr; }
      .frames-grid  { grid-template-columns: 1fr 1fr; }
      .hero-stats   { gap: 24px; }
      .footer-top   { grid-template-columns: 1fr; gap: 36px; }
      .td-samples   { grid-template-columns: 1fr; }
      .dept-header  { flex-direction: column; }
    }
    @media (max-width: 480px) {
      .frames-grid { grid-template-columns: 1fr; }
      .hero-title  { font-size: 44px; }
      .nav-user-pill { display: none; }
    }

    ::-webkit-scrollbar { width: 5px; }
    ::-webkit-scrollbar-track { background: var(--navy-950); }
    ::-webkit-scrollbar-thumb { background: rgba(201,168,76,.3); border-radius: 3px; }
  </style>
</head>
<body>

<!-- ──── BACKGROUND CANVAS ──── -->
<canvas id="bg-canvas"></canvas>

<!-- ════════════════════════════════════════════════════
     NAVBAR
════════════════════════════════════════════════════ -->
<nav class="navbar">
  <div class="nav-brand">
    <div class="nav-logo-mark">PG</div>
    <div class="nav-words">
      <div class="nav-wordmark">POLY<em>GUARD</em> AI</div>
      <div class="nav-tagline">SMART BANDOBUSTH SYSTEM</div>
    </div>
  </div>

  <div class="nav-links">
    <a href="#about"    class="nav-link">About</a>
    <a href="#dept"     class="nav-link">Department</a>
    <a href="#platform" class="nav-link">Platform</a>
    <a href="#tracking" class="nav-link">Live Tracking</a>
    <a href="#palette"  class="nav-link">Design</a>
  </div>

  <div class="nav-right">
    <?php if ($isLoggedIn && $user_info): ?>
      <!-- Logged-in: show user pill + dashboard button -->
      <div class="nav-user-pill">
        <div class="nav-avatar"><?= strtoupper(substr($user_info['name'],0,1)) ?></div>
        <div class="nav-user-text">
          <span class="nav-user-name"><?= htmlspecialchars(explode(' ', $user_info['name'])[0]) ?></span>
          <span class="nav-user-role"><?= strtoupper($role) ?></span>
        </div>
      </div>
      <a href="<?= $role === 'admin' ? 'admin/dashboard.php' : ($role === 'control' ? 'control/dashboard.php' : 'police/dashboard.php') ?>"
         class="btn-primary">
        ⚡ Dashboard
      </a>
      <form method="post" style="margin:0">
        <button type="submit" name="logout" class="btn-ghost">⏻ Logout</button>
      </form>
    <?php else: ?>
      <!-- Guest: login/signup buttons -->
      <a href="login.php"    class="btn-outline">Login</a>
      <a href="register.php" class="btn-primary">Get Started →</a>
    <?php endif; ?>
  </div>
</nav>

<!-- ════════════════════════════════════════════════════
     HERO
════════════════════════════════════════════════════ -->
<section class="hero-section">
  <div class="hero-glow-1"></div>
  <div class="hero-glow-2"></div>
  <div class="hero-glow-3"></div>

  <div class="hero-inner">
    <div class="hero-content">
      <div class="hero-eyebrow">
        <span class="pulse-dot"></span>
        LIVE SYSTEM OPERATIONAL
      </div>
      <h1 class="hero-title">
        SMART<br>
        <span class="hl-gold">BANDO</span><span class="hl-outline">BUSTH</span><br>
        SYSTEM
      </h1>
      <p class="hero-desc">
        POLYGUARD AI is an advanced police duty monitoring platform — featuring real-time GPS tracking,
        AI-powered compliance monitoring, geofence enforcement, and multi-tier command management
        for modern law enforcement operations.
      </p>
      <div class="hero-cta">
        <?php if ($isLoggedIn): ?>
          <a href="<?= $role === 'admin' ? 'admin/dashboard.php' : ($role === 'control' ? 'control/dashboard.php' : 'police/dashboard.php') ?>"
             class="btn-primary" style="font-size:15px;padding:13px 28px">
            ⚡ Go to Dashboard
          </a>
        <?php else: ?>
          <a href="login.php"    class="btn-primary"  style="font-size:15px;padding:13px 28px">Login to System →</a>
          <a href="#about"       class="btn-outline"   style="font-size:15px;padding:12px 28px">Learn More</a>
        <?php endif; ?>
      </div>
      <div class="hero-stats">
        <div class="hs-item"><div class="hs-value" data-count="247">0</div><div class="hs-label">Officers Active</div></div>
        <div class="hs-item"><div class="hs-value" data-count="18">0</div><div class="hs-label">Duty Zones</div></div>
        <div class="hs-item"><div class="hs-value" data-count="99">0</div><div class="hs-label">% Uptime</div></div>
      </div>
    </div>

    <!-- Hero Visual -->
    <div class="hero-visual">
      <div class="tracking-frame">
        <div class="tf-header">
          <span class="tf-title">LIVE TRACKING CONSOLE</span>
          <div class="tf-dots">
            <div class="tf-dot" style="background:#ff5f57"></div>
            <div class="tf-dot" style="background:#febc2e"></div>
            <div class="tf-dot" style="background:#28c840"></div>
          </div>
        </div>

        <div class="map-container">
          <div class="zone zone-1"></div>
          <div class="zone zone-2"></div>
          <div class="zone zone-3"></div>
          <div class="officer-dot od-green"></div>
          <div class="officer-dot od-blue"></div>
          <div class="officer-dot od-gold"></div>
          <div class="officer-dot od-red"></div>
          <svg class="map-svg" viewBox="0 0 400 300" xmlns="http://www.w3.org/2000/svg">
            <line x1="116" y1="101" x2="286" y2="124" stroke="rgba(201,168,76,.2)" stroke-width="1" stroke-dasharray="4,4"/>
            <line x1="116" y1="101" x2="200" y2="240" stroke="rgba(16,185,129,.2)" stroke-width="1" stroke-dasharray="4,4"/>
            <line x1="286" y1="124" x2="200" y2="240" stroke="rgba(59,130,246,.2)" stroke-width="1" stroke-dasharray="4,4"/>
          </svg>
        </div>

        <div class="tf-body">
          <div class="tf-row">
            <div class="tf-stat">
              <div class="tf-stat-label">ACTIVE</div>
              <div class="tf-stat-value tv-green">12</div>
            </div>
            <div class="tf-stat">
              <div class="tf-stat-label">ALERTS</div>
              <div class="tf-stat-value tv-red">3</div>
            </div>
            <div class="tf-stat">
              <div class="tf-stat-label">COMPLY%</div>
              <div class="tf-stat-value tv-gold">94</div>
            </div>
          </div>
          <div class="live-feed">
            <div class="lf-item"><span class="lf-dot" style="background:#10b981"></span> SI Raju · Zone A · ON DUTY<span class="lf-time">09:12</span></div>
            <div class="lf-item"><span class="lf-dot" style="background:#ef4444"></span> HC Priya · EXIT BREACH<span class="lf-time">09:08</span></div>
            <div class="lf-item"><span class="lf-dot" style="background:#f59e0b"></span> ASI Kumar · Late checkin<span class="lf-time">09:01</span></div>
            <div class="lf-item"><span class="lf-dot" style="background:#3b82f6"></span> PC Suresh · Zone B · OK<span class="lf-time">08:55</span></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<div class="section-divider"></div>

<!-- ════════════════════════════════════════════════════
     ABOUT SECTION
════════════════════════════════════════════════════ -->
<section class="section" id="about">
  <div class="section-inner">
    <div class="reveal">
      <div class="section-tag">About the Project</div>
      <h2 class="section-title">WHAT IS<br><span style="color:var(--gold-500)">POLYGUARD AI?</span></h2>
      <p class="section-desc">A comprehensive smart duty management ecosystem designed to modernize police bandobusth operations through intelligent technology.</p>
    </div>

    <div class="about-grid">
      <div class="about-card reveal-left">
        <div class="about-icon-stack">
          <div class="ai-icon">🛡</div>
          <div class="ai-icon">📍</div>
          <div class="ai-icon">🤖</div>
        </div>
        <h3 style="font-family:'Bebas Neue';font-size:28px;letter-spacing:2px;margin-bottom:14px;color:var(--gold-300)">THE VISION</h3>
        <p style="font-size:14px;line-height:1.75;color:rgba(255,255,255,.5);margin-bottom:24px">
          POLYGUARD AI was built to address the critical challenge of monitoring police officers deployed across
          wide geographic duty zones during major events, bandhs, VIP visits, and law & order situations.
          Traditional paper-based or radio-check methods leave gaps in accountability and real-time awareness.
        </p>
        <p style="font-size:14px;line-height:1.75;color:rgba(255,255,255,.5)">
          Our platform integrates GPS geofencing, attendance automation, AI compliance scoring,
          and a live command dashboard — giving supervisors complete operational awareness at a glance.
        </p>
      </div>

      <div class="about-feature-list reveal-right" style="padding-top:8px">
        <div class="afl-item">
          <div class="afl-bullet">📡</div>
          <div class="afl-text">
            <div class="afl-title">Real-Time GPS Geofencing</div>
            <div class="afl-desc">Officers are tracked within their assigned duty radius. Automatic exit alerts fire the moment a boundary is crossed.</div>
          </div>
        </div>
        <div class="afl-item">
          <div class="afl-bullet">⏱</div>
          <div class="afl-text">
            <div class="afl-title">Automated Attendance & Check-in</div>
            <div class="afl-desc">QR-code and GPS-verified check-ins eliminate manual registers. Timestamps are tamper-proof and audit-logged.</div>
          </div>
        </div>
        <div class="afl-item">
          <div class="afl-bullet">📊</div>
          <div class="afl-text">
            <div class="afl-title">AI Compliance Scoring</div>
            <div class="afl-desc">Each officer receives a dynamic compliance score based on punctuality, zone adherence, and duty completion rate.</div>
          </div>
        </div>
        <div class="afl-item">
          <div class="afl-bullet">🚨</div>
          <div class="afl-text">
            <div class="afl-title">Instant Alert Escalation</div>
            <div class="afl-desc">Multi-level alert routing — from field officer to control room to administration — ensures no incident goes unnoticed.</div>
          </div>
        </div>
        <div class="afl-item">
          <div class="afl-bullet">🔒</div>
          <div class="afl-text">
            <div class="afl-title">Role-Based Secure Access</div>
            <div class="afl-desc">Three-tier authentication with encrypted sessions, audit trails, and least-privilege access control.</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<div class="section-divider"></div>

<!-- ════════════════════════════════════════════════════
     POLICE DEPARTMENT SECTION
════════════════════════════════════════════════════ -->
<section class="section dept-section" id="dept">
  <div class="section-inner">
    <div class="dept-header">
      <div class="reveal">
        <div class="section-tag">Police Department</div>
        <h2 class="section-title">ROLES &amp;<br><span style="color:var(--blue-300)">HIERARCHY</span></h2>
        <p class="section-desc">POLYGUARD AI serves three distinct roles within the police department's command structure, each with precisely scoped access and capabilities.</p>
      </div>
      <div class="dept-badge-group reveal">
        <div class="dept-badge db-authority">⭐ Andhra Pradesh Police</div>
        <div class="dept-badge db-verified">✓ Officially Authorized System</div>
      </div>
    </div>

    <div class="dept-roles">
      <!-- Admin -->
      <div class="role-card rc-admin reveal" style="transition-delay:.05s">
        <span class="rc-icon">⭐</span>
        <div class="rc-title" style="color:var(--gold-400)">Administrator</div>
        <div class="rc-sub">Full system control. Manages personnel roster, duty assignments, system configuration, and generates all reports.</div>
        <div class="rc-perms">
          <div class="perm-item">Manage all police officers & accounts</div>
          <div class="perm-item">Create & assign duty zones</div>
          <div class="perm-item">View all compliance & alert data</div>
          <div class="perm-item">Configure geofence parameters</div>
          <div class="perm-item">Export reports & audit logs</div>
          <div class="perm-item">System-wide dashboard access</div>
        </div>
      </div>

      <!-- Control Room -->
      <div class="role-card rc-control reveal" style="transition-delay:.1s">
        <span class="rc-icon">🎯</span>
        <div class="rc-title" style="color:var(--blue-300)">Control Room</div>
        <div class="rc-sub">Real-time operations center. Monitors live duty status, receives alerts, and coordinates field responses.</div>
        <div class="rc-perms">
          <div class="perm-item">Live officer tracking dashboard</div>
          <div class="perm-item">Receive &amp; acknowledge alerts</div>
          <div class="perm-item">Monitor all active duty zones</div>
          <div class="perm-item">View compliance scores</div>
          <div class="perm-item">Incident log management</div>
          <div class="perm-item">24-hour alert feed access</div>
        </div>
      </div>

      <!-- Police Officer -->
      <div class="role-card rc-police reveal" style="transition-delay:.15s">
        <span class="rc-icon">🛡</span>
        <div class="rc-title" style="color:#6ee7b7">Police Officer</div>
        <div class="rc-sub">Field personnel portal. Check-in/out, view assigned duty, track personal compliance, and receive instructions.</div>
        <div class="rc-perms">
          <div class="perm-item">GPS-verified duty check-in/out</div>
          <div class="perm-item">View assigned duty zone & timing</div>
          <div class="perm-item">Personal compliance score</div>
          <div class="perm-item">Own attendance history</div>
          <div class="perm-item">Duty instructions & notes</div>
          <div class="perm-item">Profile management</div>
        </div>
      </div>
    </div>

    <!-- Org chart visual -->
    <div class="reveal" style="margin-top:40px">
      <div style="background:rgba(255,255,255,.02);border:1px solid rgba(255,255,255,.06);border-radius:var(--r-xl);padding:32px;text-align:center">
        <div style="font-family:'JetBrains Mono';font-size:10px;letter-spacing:2px;color:var(--gray-500);margin-bottom:24px;text-transform:uppercase">Command Chain</div>
        <div style="display:flex;align-items:center;justify-content:center;gap:0;flex-wrap:wrap">
          <div style="background:rgba(201,168,76,.12);border:1px solid rgba(201,168,76,.3);border-radius:12px;padding:14px 24px;text-align:center">
            <div style="font-family:'Bebas Neue';font-size:18px;letter-spacing:2px;color:var(--gold-400)">ADMIN</div>
            <div style="font-size:11px;color:rgba(255,255,255,.4)">System Authority</div>
          </div>
          <div style="width:60px;height:2px;background:linear-gradient(90deg,rgba(201,168,76,.4),rgba(59,130,246,.4))"></div>
          <div style="background:rgba(59,130,246,.12);border:1px solid rgba(59,130,246,.3);border-radius:12px;padding:14px 24px;text-align:center">
            <div style="font-family:'Bebas Neue';font-size:18px;letter-spacing:2px;color:var(--blue-300)">CONTROL</div>
            <div style="font-size:11px;color:rgba(255,255,255,.4)">Operations Hub</div>
          </div>
          <div style="width:60px;height:2px;background:linear-gradient(90deg,rgba(59,130,246,.4),rgba(16,185,129,.4))"></div>
          <div style="background:rgba(16,185,129,.12);border:1px solid rgba(16,185,129,.3);border-radius:12px;padding:14px 24px;text-align:center">
            <div style="font-family:'Bebas Neue';font-size:18px;letter-spacing:2px;color:#6ee7b7">OFFICER</div>
            <div style="font-size:11px;color:rgba(255,255,255,.4)">Field Personnel</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<div class="section-divider"></div>

<!-- ════════════════════════════════════════════════════
     PLATFORM DIVISIONS
════════════════════════════════════════════════════ -->
<section class="section" id="platform">
  <div class="section-inner">
    <div class="reveal">
      <div class="section-tag">Platform</div>
      <h2 class="section-title">PLATFORM<br><span style="color:var(--green-400)">DIVISIONS</span></h2>
      <p class="section-desc">Seven integrated modules form the complete POLYGUARD AI ecosystem, working in concert to deliver end-to-end duty management.</p>
    </div>

    <div class="platform-grid">
      <!-- Wide card: Core System -->
      <div class="plat-card pc-wide reveal">
        <div class="pc-accent pa-gold"></div>
        <div class="pc-wide-inner">
          <div>
            <div class="pc-icon-wrap pi-gold"><span>🏛</span></div>
            <div class="pc-title" style="font-size:22px">Core System Architecture</div>
            <div class="pc-desc">The foundational engine powering all POLYGUARD modules — secure PHP backend, MySQL relational data layer, and REST API for real-time communication.</div>
            <div class="pc-tags">
              <span class="pc-tag">PHP 8.2</span>
              <span class="pc-tag">MySQL</span>
              <span class="pc-tag">REST API</span>
              <span class="pc-tag">Session Auth</span>
              <span class="pc-tag">AES Encryption</span>
            </div>
          </div>
          <div class="pc-wide-right">
            <div class="pc-module-list">
              <div class="pml-item"><span class="pml-icon">🔐</span> Role-based authentication</div>
              <div class="pml-item"><span class="pml-icon">📋</span> Duty assignment engine</div>
              <div class="pml-item"><span class="pml-icon">📍</span> GPS geofencing core</div>
              <div class="pml-item"><span class="pml-icon">🔔</span> Real-time alert dispatcher</div>
              <div class="pml-item"><span class="pml-icon">📊</span> Analytics aggregator</div>
              <div class="pml-item"><span class="pml-icon">🧮</span> Compliance scoring AI</div>
              <div class="pml-item"><span class="pml-icon">📱</span> Attendance QR module</div>
              <div class="pml-item"><span class="pml-icon">🔒</span> Security audit logger</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Live Tracking -->
      <div class="plat-card reveal" style="transition-delay:.05s">
        <div class="pc-accent pa-green"></div>
        <div class="pc-icon-wrap pi-green"><span>📡</span></div>
        <div class="pc-title">Live GPS Tracking</div>
        <div class="pc-desc">Real-time officer location monitoring with configurable geofence zones. Automatic boundary crossing detection fires instant alerts to command.</div>
        <div class="pc-tags"><span class="pc-tag">Geofencing</span><span class="pc-tag">WebSockets</span><span class="pc-tag">Map API</span></div>
      </div>

      <!-- Admin Panel -->
      <div class="plat-card reveal" style="transition-delay:.1s">
        <div class="pc-accent pa-gold"></div>
        <div class="pc-icon-wrap pi-gold"><span>⚙️</span></div>
        <div class="pc-title">Admin Control Panel</div>
        <div class="pc-desc">Full administrative suite — personnel management, duty scheduling, report generation, and system configuration in one unified interface.</div>
        <div class="pc-tags"><span class="pc-tag">CRUD</span><span class="pc-tag">Reports</span><span class="pc-tag">Audit</span></div>
      </div>

      <!-- Control Room -->
      <div class="plat-card reveal" style="transition-delay:.12s">
        <div class="pc-accent pa-blue"></div>
        <div class="pc-icon-wrap pi-blue"><span>🎯</span></div>
        <div class="pc-title">Command Center Dashboard</div>
        <div class="pc-desc">Operations dashboard for control room staff — live duty feed, active alerts, geofence breach monitoring, and 24-hour incident log.</div>
        <div class="pc-tags"><span class="pc-tag">Live Feed</span><span class="pc-tag">Alerts</span><span class="pc-tag">Monitor</span></div>
      </div>

      <!-- Officer Portal -->
      <div class="plat-card reveal" style="transition-delay:.14s">
        <div class="pc-accent pa-green"></div>
        <div class="pc-icon-wrap pi-green"><span>🛡</span></div>
        <div class="pc-title">Officer Field Portal</div>
        <div class="pc-desc">Mobile-first interface for field officers — GPS check-in, duty instructions, personal compliance tracker, and attendance history.</div>
        <div class="pc-tags"><span class="pc-tag">Mobile</span><span class="pc-tag">GPS</span><span class="pc-tag">Check-in</span></div>
      </div>

      <!-- Analytics -->
      <div class="plat-card reveal" style="transition-delay:.16s">
        <div class="pc-accent pa-purple"></div>
        <div class="pc-icon-wrap pi-purple"><span>📈</span></div>
        <div class="pc-title">Advanced Analytics</div>
        <div class="pc-desc">AI-powered compliance scoring, performance trends, heatmaps of duty coverage, violation pattern analysis, and exportable reports.</div>
        <div class="pc-tags"><span class="pc-tag">AI Scoring</span><span class="pc-tag">Heatmap</span><span class="pc-tag">Export</span></div>
      </div>

      <!-- Security -->
      <div class="plat-card reveal" style="transition-delay:.18s">
        <div class="pc-accent pa-red"></div>
        <div class="pc-icon-wrap pi-red"><span>🔒</span></div>
        <div class="pc-title">Security & Blockchain Audit</div>
        <div class="pc-desc">Immutable audit trails using blockchain hash chaining. Every login, action, and data change is cryptographically logged and tamper-proof.</div>
        <div class="pc-tags"><span class="pc-tag">Blockchain</span><span class="pc-tag">Audit</span><span class="pc-tag">Hash Chain</span></div>
      </div>
    </div>
  </div>
</section>

<div class="section-divider"></div>

<!-- ════════════════════════════════════════════════════
     LIVE TRACKING SECTION
════════════════════════════════════════════════════ -->
<section class="section tracking-section" id="tracking">
  <div class="section-inner">
    <div class="reveal">
      <div class="section-tag">Live Tracking System</div>
      <h2 class="section-title">REAL-TIME<br><span style="color:var(--green-400)">MONITORING</span></h2>
      <p class="section-desc">Every deployed officer is visible in real-time. Zone compliance, attendance status, and breach events update live across all command levels.</p>
    </div>

    <div class="tracking-layout">
      <!-- Big Map -->
      <div class="main-map reveal-left">
        <div class="mm-header">
          <div class="mm-title">DUTY ZONE MAP — LIVE</div>
          <div class="mm-controls">
            <button class="mm-btn active">ALL ZONES</button>
            <button class="mm-btn">BREACHES</button>
            <button class="mm-btn">COVERAGE</button>
          </div>
        </div>

        <div class="big-map">
          <!-- Zones -->
          <div class="big-zone bz-1"></div>
          <div class="big-zone bz-2"></div>
          <div class="big-zone bz-3"></div>
          <div class="big-zone bz-4"></div>

          <!-- Sector labels -->
          <div class="sector-label sl-green">SECTOR A</div>
          <div class="sector-label sl-blue">SECTOR B</div>
          <div class="sector-label sl-amber">SECTOR C</div>
          <div class="sector-label sl-red">BREACH ZONE</div>

          <!-- Officers -->
          <div class="big-officer bo-1" style="position:absolute;top:105px;left:120px">
            <div class="bo-tooltip">SI Raju · On Duty</div>
          </div>
          <div class="big-officer bo-2" style="position:absolute;top:120px;right:140px">
            <div class="bo-tooltip">HC Priya · On Duty</div>
          </div>
          <div class="big-officer bo-3" style="position:absolute;bottom:90px;left:52%">
            <div class="bo-tooltip">ASI Kumar · Active</div>
          </div>
          <div class="big-officer bo-4" style="position:absolute;top:55px;right:105px">
            <div class="bo-tooltip">PC Suresh · Breach!</div>
          </div>
          <div class="big-officer bo-5" style="position:absolute;bottom:100px;left:75px">
            <div class="bo-tooltip">HC Meena · On Duty</div>
          </div>

          <!-- SVG connections -->
          <svg style="position:absolute;inset:0;width:100%;height:100%;pointer-events:none" viewBox="0 0 800 400">
            <line x1="120" y1="112" x2="660" y2="128" stroke="rgba(201,168,76,.12)" stroke-width="1" stroke-dasharray="6,6"/>
            <line x1="120" y1="112" x2="400" y2="310" stroke="rgba(16,185,129,.12)" stroke-width="1" stroke-dasharray="6,6"/>
            <line x1="660" y1="128" x2="400" y2="310" stroke="rgba(59,130,246,.12)" stroke-width="1" stroke-dasharray="6,6"/>
            <line x1="120" y1="112" x2="75"  y2="300" stroke="rgba(139,92,246,.12)" stroke-width="1" stroke-dasharray="6,6"/>
          </svg>
        </div>

        <div class="mm-footer">
          <div class="mmf-stat"><div class="mmf-dot" style="background:var(--green-500)"></div> 4 Active Officers</div>
          <div class="mmf-stat"><div class="mmf-dot" style="background:var(--red-500)"></div> 1 Breach Alert</div>
          <div class="mmf-stat"><div class="mmf-dot" style="background:var(--amber-500)"></div> 3 Zones Monitored</div>
          <div class="mmf-stat"><div class="mmf-dot" style="background:var(--blue-500)"></div> Live · <?= date('H:i') ?></div>
        </div>
      </div>

      <!-- Side Panels -->
      <div class="tracking-side reveal-right">

        <!-- Stats panel -->
        <div class="side-panel">
          <div class="sp-header">System Status</div>
          <div class="sp-body">
            <div class="sp-stat-grid">
              <div class="ssg-item">
                <div class="ssg-label">Active</div>
                <div class="ssg-value tv-green">12</div>
              </div>
              <div class="ssg-item">
                <div class="ssg-label">Alerts</div>
                <div class="ssg-value tv-red">3</div>
              </div>
              <div class="ssg-item">
                <div class="ssg-label">Comply</div>
                <div class="ssg-value tv-gold">94%</div>
              </div>
              <div class="ssg-item">
                <div class="ssg-label">Zones</div>
                <div class="ssg-value tv-blue">18</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Alert feed -->
        <div class="side-panel">
          <div class="sp-header">Live Alert Feed</div>
          <div class="sp-body">
            <div class="alert-feed">
              <div class="af-item">
                <span class="af-icon">🚫</span>
                <div class="af-content">
                  <div class="af-title">HC Priya — EXIT BREACH</div>
                  <div class="af-sub">Zone A · 200m outside</div>
                </div>
                <span class="af-time">09:08</span>
              </div>
              <div class="af-item">
                <span class="af-icon">⏱</span>
                <div class="af-content">
                  <div class="af-title">ASI Kumar — LATE CHECK-IN</div>
                  <div class="af-sub">Zone C · 12 min late</div>
                </div>
                <span class="af-time">09:01</span>
              </div>
              <div class="af-item">
                <span class="af-icon">📋</span>
                <div class="af-content">
                  <div class="af-title">PC Ravi — ABSENCE</div>
                  <div class="af-sub">Zone B · No check-in</div>
                </div>
                <span class="af-time">08:30</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Compliance -->
        <div class="side-panel">
          <div class="sp-header">Compliance Overview</div>
          <div class="sp-body">
            <div class="comp-list">
              <div class="cl-item">
                <div class="cl-head"><span class="cl-name">SI Raju</span><span class="cl-score">98%</span></div>
                <div class="cl-bar"><div class="cl-fill" style="width:98%;background:var(--green-500)"></div></div>
              </div>
              <div class="cl-item">
                <div class="cl-head"><span class="cl-name">ASI Kumar</span><span class="cl-score">82%</span></div>
                <div class="cl-bar"><div class="cl-fill" style="width:82%;background:var(--amber-500)"></div></div>
              </div>
              <div class="cl-item">
                <div class="cl-head"><span class="cl-name">HC Priya</span><span class="cl-score">61%</span></div>
                <div class="cl-bar"><div class="cl-fill" style="width:61%;background:var(--gold-500)"></div></div>
              </div>
              <div class="cl-item">
                <div class="cl-head"><span class="cl-name">PC Suresh</span><span class="cl-score">44%</span></div>
                <div class="cl-bar"><div class="cl-fill" style="width:44%;background:var(--red-500)"></div></div>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</section>

<div class="section-divider"></div>

<!-- ════════════════════════════════════════════════════
     COLOR PALETTE & DESIGN SYSTEM
════════════════════════════════════════════════════ -->
<section class="section palette-section" id="palette">
  <div class="section-inner">
    <div class="reveal">
      <div class="section-tag">Design System</div>
      <h2 class="section-title">COLOR<br><span style="color:var(--gold-500)">PALETTE</span></h2>
      <p class="section-desc">A carefully engineered design language balancing authority, clarity, and urgency — built for high-stakes operational environments.</p>
    </div>

    <div class="palette-grid">
      <!-- Navy -->
      <div class="reveal" style="transition-delay:.05s">
        <div class="pg-name">Navy Core</div>
        <div class="pg-swatches">
          <div class="swatch" style="background:#050d1a"><span class="sw-name" style="color:#fff">Navy 950</span><span class="sw-hex" style="color:rgba(255,255,255,.5)">#050d1a</span></div>
          <div class="swatch" style="background:#0b1628"><span class="sw-name" style="color:#fff">Navy 900</span><span class="sw-hex" style="color:rgba(255,255,255,.5)">#0b1628</span></div>
          <div class="swatch" style="background:#112240"><span class="sw-name" style="color:#fff">Navy 700</span><span class="sw-hex" style="color:rgba(255,255,255,.5)">#112240</span></div>
          <div class="swatch" style="background:#1a3358"><span class="sw-name" style="color:#fff">Navy 600</span><span class="sw-hex" style="color:rgba(255,255,255,.5)">#1a3358</span></div>
          <div class="swatch" style="background:#234070"><span class="sw-name" style="color:#fff">Navy 500</span><span class="sw-hex" style="color:rgba(255,255,255,.5)">#234070</span></div>
        </div>
      </div>

      <!-- Gold -->
      <div class="reveal" style="transition-delay:.1s">
        <div class="pg-name">Gold Accent</div>
        <div class="pg-swatches">
          <div class="swatch" style="background:#78350f"><span class="sw-name" style="color:#fde68a">Gold 900</span><span class="sw-hex" style="color:rgba(253,230,138,.5)">#78350f</span></div>
          <div class="swatch" style="background:#c9a84c"><span class="sw-name" style="color:#fff8dc">Gold 500</span><span class="sw-hex" style="color:rgba(255,248,220,.5)">#c9a84c</span></div>
          <div class="swatch" style="background:#e8c97a"><span class="sw-name" style="color:#78350f">Gold 300</span><span class="sw-hex" style="color:rgba(120,53,15,.5)">#e8c97a</span></div>
          <div class="swatch" style="background:#f5e0a0"><span class="sw-name" style="color:#78350f">Gold 200</span><span class="sw-hex" style="color:rgba(120,53,15,.5)">#f5e0a0</span></div>
          <div class="swatch" style="background:#fdf3d7"><span class="sw-name" style="color:#78350f">Gold 100</span><span class="sw-hex" style="color:rgba(120,53,15,.5)">#fdf3d7</span></div>
        </div>
      </div>

      <!-- Status -->
      <div class="reveal" style="transition-delay:.15s">
        <div class="pg-name">Status Colors</div>
        <div class="pg-swatches">
          <div class="swatch" style="background:#10b981"><span class="sw-name" style="color:#fff">Green — Active</span><span class="sw-hex" style="color:rgba(255,255,255,.6)">#10b981</span></div>
          <div class="swatch" style="background:#ef4444"><span class="sw-name" style="color:#fff">Red — Alert</span><span class="sw-hex" style="color:rgba(255,255,255,.6)">#ef4444</span></div>
          <div class="swatch" style="background:#f59e0b"><span class="sw-name" style="color:#fff">Amber — Warning</span><span class="sw-hex" style="color:rgba(255,255,255,.6)">#f59e0b</span></div>
          <div class="swatch" style="background:#3b82f6"><span class="sw-name" style="color:#fff">Blue — Info</span><span class="sw-hex" style="color:rgba(255,255,255,.6)">#3b82f6</span></div>
          <div class="swatch" style="background:#8b5cf6"><span class="sw-name" style="color:#fff">Purple — Special</span><span class="sw-hex" style="color:rgba(255,255,255,.6)">#8b5cf6</span></div>
        </div>
      </div>
    </div>

    <!-- Typography -->
    <div class="typo-demo reveal" style="margin-top:40px">
      <div class="td-title">Typography System</div>
      <div class="td-samples">
        <div class="td-sample">
          <div class="ts-label">DISPLAY — Bebas Neue</div>
          <div class="ts-f1">POLYGUARD AI</div>
        </div>
        <div class="td-sample">
          <div class="ts-label">BODY — DM Sans</div>
          <div class="ts-f2">Smart Bandobusth System</div>
        </div>
        <div class="td-sample">
          <div class="ts-label">MONO — JetBrains Mono</div>
          <div class="ts-f3">SYS.STATUS: ONLINE</div>
        </div>
      </div>
    </div>
  </div>
</section>

<div class="section-divider"></div>

<!-- ════════════════════════════════════════════════════
     ANIMATION FRAMES SHOWCASE
════════════════════════════════════════════════════ -->
<section class="section" id="frames">
  <div class="section-inner">
    <div class="reveal">
      <div class="section-tag">Animations & Frames</div>
      <h2 class="section-title">MOTION<br><span style="color:var(--purple-500)">LANGUAGE</span></h2>
      <p class="section-desc">Every animation in POLYGUARD AI conveys real system state — from the live radar sweep to compliance bar fills.</p>
    </div>

    <div class="frames-grid">
      <div class="frame-card reveal" style="transition-delay:.05s">
        <div class="anim-demo"><div class="ad-spin"></div></div>
        <div class="frame-title">System Loader</div>
        <div class="frame-desc">Spinning gold ring — used during data fetch & module loading states.</div>
      </div>
      <div class="frame-card reveal" style="transition-delay:.08s">
        <div class="anim-demo"><div class="ad-pulse"></div></div>
        <div class="frame-title">Live Pulse</div>
        <div class="frame-desc">Breathing green orb — indicates an active officer check-in or live status signal.</div>
      </div>
      <div class="frame-card reveal" style="transition-delay:.11s">
        <div class="anim-demo"><div class="ad-wave"><div class="wave-bar" style="height:20px"></div><div class="wave-bar" style="height:28px"></div><div class="wave-bar" style="height:36px"></div><div class="wave-bar" style="height:28px"></div><div class="wave-bar" style="height:20px"></div></div></div>
        <div class="frame-title">Audio / Signal Wave</div>
        <div class="frame-desc">Wave bars animate during radio check broadcasts and alert dispatches.</div>
      </div>
      <div class="frame-card reveal" style="transition-delay:.14s">
        <div class="anim-demo" style="justify-content:center;align-items:flex-end;padding-bottom:10px"><div class="ad-bounce"></div></div>
        <div class="frame-title">Alert Bounce</div>
        <div class="frame-desc">Bouncing dot draws attention to new unread alert notifications.</div>
      </div>
      <div class="frame-card reveal" style="transition-delay:.17s">
        <div class="anim-demo"><div class="ad-radar"></div></div>
        <div class="frame-title">Radar Sweep</div>
        <div class="frame-desc">Rotating sweep arm — visualizes active GPS zone scanning on the tracking map.</div>
      </div>
      <div class="frame-card reveal" style="transition-delay:.20s">
        <div class="anim-demo"><div class="ad-morph"></div></div>
        <div class="frame-title">State Morph</div>
        <div class="frame-desc">Shape morphing effect used in officer status card transitions.</div>
      </div>
    </div>
  </div>
</section>

<div class="section-divider"></div>

<!-- ════════════════════════════════════════════════════
     CTA
════════════════════════════════════════════════════ -->
<section class="section cta-section">
  <div class="section-inner">
    <div class="cta-inner reveal">
      <h2 class="cta-title">READY TO <em>DEPLOY?</em></h2>
      <p class="cta-desc">
        Log in to access your role-based dashboard and start monitoring duty operations
        in real-time with POLYGUARD AI.
      </p>
      <div class="cta-btns">
        <?php if ($isLoggedIn): ?>
          <a href="<?= $role === 'admin' ? 'admin/dashboard.php' : ($role === 'control' ? 'control/dashboard.php' : 'police/dashboard.php') ?>"
             class="btn-primary" style="font-size:15px;padding:14px 32px">
            ⚡ Open My Dashboard
          </a>
        <?php else: ?>
          <a href="login.php"    class="btn-primary"  style="font-size:15px;padding:14px 32px">Login to System</a>
          <a href="register.php" class="btn-outline"   style="font-size:15px;padding:13px 32px">Create Account</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<!-- ════════════════════════════════════════════════════
     FOOTER
════════════════════════════════════════════════════ -->
<footer class="site-footer">
  <div class="footer-inner">
    <div class="footer-top">
      <div class="ft-brand">
        <div class="ft-logo">
          <div class="ft-logo-mark">PG</div>
          <div class="ft-wordmark">POLY<em>GUARD</em> AI</div>
        </div>
        <div class="ft-desc">
          Smart Bandobusth Duty Monitoring System — empowering law enforcement with real-time intelligence for safer, more accountable field operations.
        </div>
        <div class="ft-secure-badges">
          <div class="ft-badge">🔒 Encrypted</div>
          <div class="ft-badge">⛓ Blockchain Audit</div>
          <div class="ft-badge">✓ RBAC</div>
        </div>
      </div>

      <div class="ft-col">
        <div class="ft-col-title">Platform</div>
        <div class="ft-links">
          <a href="#platform" class="ft-link">Live Tracking</a>
          <a href="#platform" class="ft-link">Admin Panel</a>
          <a href="#platform" class="ft-link">Control Room</a>
          <a href="#platform" class="ft-link">Officer Portal</a>
          <a href="#platform" class="ft-link">Analytics</a>
        </div>
      </div>

      <div class="ft-col">
        <div class="ft-col-title">Department</div>
        <div class="ft-links">
          <a href="#dept" class="ft-link">Administrator</a>
          <a href="#dept" class="ft-link">Control Room</a>
          <a href="#dept" class="ft-link">Police Officers</a>
          <a href="#dept" class="ft-link">Command Chain</a>
        </div>
      </div>

      <div class="ft-col">
        <div class="ft-col-title">System</div>
        <div class="ft-links">
          <a href="login.php"    class="ft-link">Login</a>
          <?php if (!$isLoggedIn): ?>
          <a href="register.php" class="ft-link">Register</a>
          <?php endif; ?>
          <?php if ($isLoggedIn): ?>
          <a href="<?= $role === 'admin' ? 'admin/dashboard.php' : ($role === 'control' ? 'control/dashboard.php' : 'police/dashboard.php') ?>" class="ft-link">Dashboard</a>
          <?php endif; ?>
          <a href="#about"   class="ft-link">About</a>
          <a href="#palette" class="ft-link">Design System</a>
        </div>
      </div>
    </div>

    <div class="footer-bottom">
      <div class="fb-copy">© <?= date('Y') ?> POLYGUARD AI · Smart Bandobusth System · All rights reserved</div>
      <div class="fb-meta">
        <div class="fb-v">v1.0.0</div>
        <div class="fb-v" id="footer-clock">--:--:--</div>
      </div>
    </div>
  </div>
</footer>

<!-- ════════════════════════════════════════════════════
     JAVASCRIPT
════════════════════════════════════════════════════ -->
<script>
/* ── Background Particle Canvas ── */
(function() {
  const canvas = document.getElementById('bg-canvas');
  const ctx    = canvas.getContext('2d');
  let W, H, particles = [];

  function resize() {
    W = canvas.width  = window.innerWidth;
    H = canvas.height = window.innerHeight;
  }
  window.addEventListener('resize', resize);
  resize();

  class Particle {
    constructor() { this.reset(); }
    reset() {
      this.x  = Math.random() * W;
      this.y  = Math.random() * H;
      this.r  = Math.random() * 1.2 + .3;
      this.vx = (Math.random() - .5) * .18;
      this.vy = (Math.random() - .5) * .18;
      this.a  = Math.random() * .4 + .1;
      this.gold = Math.random() > .65;
    }
    update() {
      this.x += this.vx;
      this.y += this.vy;
      if (this.x < 0 || this.x > W || this.y < 0 || this.y > H) this.reset();
    }
    draw() {
      ctx.beginPath();
      ctx.arc(this.x, this.y, this.r, 0, Math.PI * 2);
      ctx.fillStyle = this.gold
        ? `rgba(201,168,76,${this.a})`
        : `rgba(59,130,246,${this.a * .6})`;
      ctx.fill();
    }
  }

  for (let i = 0; i < 120; i++) particles.push(new Particle());

  // Draw connections
  function drawLines() {
    for (let i = 0; i < particles.length; i++) {
      for (let j = i + 1; j < particles.length; j++) {
        const dx   = particles[i].x - particles[j].x;
        const dy   = particles[i].y - particles[j].y;
        const dist = Math.sqrt(dx*dx + dy*dy);
        if (dist < 100) {
          ctx.beginPath();
          ctx.strokeStyle = `rgba(201,168,76,${.06 * (1 - dist/100)})`;
          ctx.lineWidth   = .5;
          ctx.moveTo(particles[i].x, particles[i].y);
          ctx.lineTo(particles[j].x, particles[j].y);
          ctx.stroke();
        }
      }
    }
  }

  function loop() {
    ctx.clearRect(0, 0, W, H);
    particles.forEach(p => { p.update(); p.draw(); });
    drawLines();
    requestAnimationFrame(loop);
  }
  loop();
})();

/* ── Clock ── */
function updateClock() {
  const t = new Date().toLocaleTimeString('en-IN', {hour12: false});
  const el = document.getElementById('footer-clock');
  if (el) el.textContent = t;
}
updateClock();
setInterval(updateClock, 1000);

/* ── Hero counter animation ── */
function animateCount(el, target, suffix) {
  const dur = 1800;
  const t0  = performance.now();
  function frame(now) {
    const p = Math.min((now - t0) / dur, 1);
    const e = 1 - Math.pow(1 - p, 3);
    el.textContent = Math.round(e * target) + (suffix || '');
    if (p < 1) requestAnimationFrame(frame);
  }
  requestAnimationFrame(frame);
}
const countEls = document.querySelectorAll('.hs-value[data-count]');
const observer = new IntersectionObserver(entries => {
  entries.forEach(e => {
    if (e.isIntersecting) {
      const el  = e.target;
      const val = parseInt(el.dataset.count);
      const sfx = el.textContent.includes('%') ? '%' : '';
      animateCount(el, val, sfx);
      observer.unobserve(el);
    }
  });
}, { threshold: .5 });
countEls.forEach(el => observer.observe(el));

/* ── Scroll reveal ── */
const revealObs = new IntersectionObserver(entries => {
  entries.forEach(e => {
    if (e.isIntersecting) {
      e.target.classList.add('visible');
      revealObs.unobserve(e.target);
    }
  });
}, { threshold: .12, rootMargin: '0px 0px -40px 0px' });

document.querySelectorAll('.reveal, .reveal-left, .reveal-right').forEach(el => {
  revealObs.observe(el);
});

/* ── Compliance bar animation ── */
const compObs = new IntersectionObserver(entries => {
  entries.forEach(e => {
    if (e.isIntersecting) {
      e.target.querySelectorAll('.cl-fill').forEach(bar => {
        const w = bar.style.width;
        bar.style.width = '0%';
        setTimeout(() => { bar.style.width = w; }, 200);
      });
      compObs.unobserve(e.target);
    }
  });
}, { threshold: .3 });
document.querySelectorAll('.comp-list').forEach(el => compObs.observe(el));

/* ── Map button toggle ── */
document.querySelectorAll('.mm-btn').forEach(btn => {
  btn.addEventListener('click', function() {
    document.querySelectorAll('.mm-btn').forEach(b => b.classList.remove('active'));
    this.classList.add('active');
  });
});

/* ── Smooth nav link highlight ── */
const sections = document.querySelectorAll('section[id]');
const navLinks = document.querySelectorAll('.nav-link');
window.addEventListener('scroll', () => {
  let current = '';
  sections.forEach(s => {
    if (window.scrollY >= s.offsetTop - 80) current = s.id;
  });
  navLinks.forEach(l => {
    l.style.color = l.getAttribute('href') === '#' + current
      ? 'var(--gold-300)'
      : '';
  });
}, { passive: true });
</script>
</body>
</html>
