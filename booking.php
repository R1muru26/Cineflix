<?php session_start(); ?>
<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

$conn = db_get_connection();
cineflix_bootstrap_session_from_cookie($conn);

require_once __DIR__ . '/includes/cineflix_nav_helpers.php';
$cineflixNavMerged = !empty($_SESSION['user_id'])
  ? array_merge(cineflix_nav_load_user($conn), cineflix_nav_load_inbox($conn))
  : ['navProfilePic' => '', 'navUsername' => '', 'userInboxItems' => [], 'userInboxUnread' => 0];
$userInboxItems = $cineflixNavMerged['userInboxItems'];
$userInboxUnread = (int)$cineflixNavMerged['userInboxUnread'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title>Book Tickets | CineFlix</title>
  <link rel="stylesheet" href="common.css">
  <link rel="stylesheet" href="css/header-nav.css">
  <link rel="stylesheet" href="booking.css">
  <link rel="stylesheet" href="food_system.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    /* ── Fixed top navbar — consistent with all pages ───────────── */
    .site-header {
      position: fixed !important;
      top: 0 !important;
      left: 0 !important;
      right: 0 !important;
      z-index: 10050 !important;
      padding: 0 1.5rem !important;
      background: rgba(10, 10, 10, 0.92) !important;
      backdrop-filter: blur(12px) !important;
      -webkit-backdrop-filter: blur(12px) !important;
      border-bottom: 1px solid rgba(199,159,94,0.15) !important;
      display: flex !important;
      align-items: center !important;
      justify-content: space-between !important;
      height: 64px !important;
    }

    .site-header .logo img {
      height: 260px !important;
      width: auto !important;
    }

    .top-nav ul,
    .site-header ul {
      display: flex !important;
      flex-direction: row !important;
      align-items: center !important;
      gap: 14px !important;
      list-style: none !important;
      margin: 0 !important;
      padding: 0 !important;
    }
    .top-nav li,
    .site-header li { list-style: none !important; }

    .nav-btn {
      padding: 0.7rem 1.2rem;
      background: rgba(255,255,255,0.08);
      color: #fff;
      text-decoration: none;
      border-radius: 30px;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      border: none;
      font-family: 'Poppins', sans-serif;
      -webkit-tap-highlight-color: transparent;
      transition: background 0.3s ease, transform 0.3s ease, box-shadow 0.3s ease;
    }
    .nav-btn:hover {
      background: rgb(39, 39, 39);
      color: #c79f5e;
      transform: translateY(-5px);
      box-shadow: 0 4px 12px rgba(199, 159, 94, 0.5);
    }

    /* ── Food & Drinks addon cards (Updated to use Food System) ─────────────────────────────── */
    #addons-list {
      padding: 20px 0;
      max-width: 1200px;
      margin: 0 auto;
    }
    
    .addons-section-title {
      font-size: var(--fs-font-size-xl);
      color: var(--fs-color-primary);
      margin-bottom: var(--fs-spacing-lg);
      border-bottom: 2px solid var(--fs-color-border);
      padding-bottom: 8px;
    }

    /* ── Calendar month select – dark theme fix ────────────────────── */
    .booking-date-month-select-wrap {
      position: relative;
    }
    .booking-date-month-select {
      background: #1c1c2e !important;
      color: #ffffff !important;
      border: 1px solid rgba(199,159,94,0.45) !important;
      border-radius: 8px !important;
      padding: 6px 32px 6px 14px !important;
      font-family: 'Poppins', sans-serif !important;
      font-size: 0.9rem !important;
      font-weight: 600 !important;
      appearance: none !important;
      -webkit-appearance: none !important;
      cursor: pointer;
      outline: none;
      min-width: 148px;
    }
    .booking-date-month-select-wrap::after {
      content: '▾';
      position: absolute;
      right: 10px;
      top: 50%;
      transform: translateY(-50%);
      color: #c79f5e;
      pointer-events: none;
      font-size: 0.85rem;
    }
    .booking-date-month-select:focus {
      border-color: #c79f5e !important;
      box-shadow: 0 0 0 2px rgba(199,159,94,0.2) !important;
    }
    .booking-date-month-select option {
      background: #1c1c2e !important;
      color: #ffffff !important;
    }

    /* ── Calendar day cells ────────────────────────────────────── */
    .booking-date-day {
      background: rgba(255, 255, 255, 0.03);
      border: 1px solid rgba(255, 255, 255, 0.05);
      border-radius: 8px;
      color: #fff;
      padding: 10px;
      cursor: pointer;
      font-weight: 600;
      transition: all 0.2s;
    }
    .booking-date-day:hover:not(.is-empty):not(.day-disabled) {
      background: rgba(199, 159, 94, 0.15);
      border-color: #c79f5e;
      color: #c79f5e;
    }
    .booking-date-day.selected {
      background: #c79f5e !important;
      color: #000 !important;
      border-color: #c79f5e !important;
    }
    .booking-date-day.is-empty {
      background: transparent;
      border: none;
      cursor: default;
    }

    /* ── Disabled (past / expired) calendar day cells ──────────────── */
    .booking-date-day.day-disabled,
    .booking-date-days-grid .day-disabled {
      opacity: 0.25 !important;
      cursor: not-allowed !important;
      pointer-events: none !important;
      text-decoration: line-through !important;
      color: rgba(255,255,255,0.3) !important;
      background: transparent !important;
      border-color: transparent !important;
    }
    /* Today highlight */
    .booking-date-day.day-today:not(.selected) {
      border-color: rgba(199,159,94,0.5) !important;
      color: #c79f5e !important;
      font-weight: 700 !important;
    }

    /* ── Theatre Selection Bar with radio pills ───────────────────── */
    .theatre-selection-bar {
      background: transparent;
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 50px;
      padding: 0.75rem clamp(1rem, 4vw, 2rem);
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 1rem;
      margin: 2rem 0 0.5rem 0;
      flex-wrap: wrap;
    }
    .theatre-label {
      font-weight: 700;
      color: #fff;
      font-size: 1rem;
      white-space: nowrap;
    }
    .theatre-pills {
      display: flex;
      align-items: center;
      gap: 0.6rem;
      flex-wrap: wrap;
    }
    .theatre-pill {
      display: flex;
      align-items: center;
      gap: 7px;
      background: rgba(255,255,255,0.05);
      border: 1px solid rgba(255,255,255,0.12);
      border-radius: 50px;
      padding: 0.45rem 1.1rem;
      cursor: pointer;
      transition: all 0.2s ease;
      font-size: 0.9rem;
      color: rgba(255,255,255,0.75);
      font-weight: 500;
      user-select: none;
    }
    .theatre-pill:hover {
      border-color: rgba(199,159,94,0.5);
      background: rgba(199,159,94,0.08);
      color: #fff;
    }
    .theatre-pill:has(input:checked) {
      border-color: #c79f5e;
      background: rgba(199,159,94,0.12);
      color: #fff;
    }
    .pill-dot {
      width: 9px;
      height: 9px;
      border-radius: 50%;
      border: 2px solid rgba(255,255,255,0.35);
      flex-shrink: 0;
      transition: all 0.2s;
    }
    .theatre-pill:has(input:checked) .pill-dot {
      background: #c79f5e;
      border-color: #c79f5e;
      box-shadow: 0 0 6px rgba(199,159,94,0.6);
    }
    .theatre-hint {
      text-align: center;
      color: rgba(199,159,94,0.85);
      font-size: 0.82rem;
      margin: 0.4rem 0 1.5rem 0;
      font-weight: 500;
    }

    /* ── Schedule Grid - 3 column layout like Image 1 ─────────────── */
    .schedule-grid-container {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 1.25rem;
      margin-bottom: 2.5rem;
    }
    
    @media (max-width: 768px) {
      .schedule-grid-container {
        grid-template-columns: 1fr;
      }
    }
    @media (min-width: 769px) and (max-width: 1024px) {
      .schedule-grid-container {
        grid-template-columns: repeat(2, 1fr);
      }
    }
    .new-schedule-card {
      background: rgba(30, 30, 30, 0.4);
      border: 1px solid rgba(255, 255, 255, 0.06);
      border-radius: 14px;
      padding: 1.75rem 1.5rem;
      cursor: pointer;
      transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
      display: flex;
      flex-direction: column;
      gap: 0.4rem;
      position: relative;
      overflow: hidden;
    }
    .new-schedule-card::before {
      content: '';
      position: absolute;
      top: 0; left: 0; right: 0; height: 3px;
      background: #c79f5e;
      opacity: 0;
      transition: opacity 0.3s;
    }
    .new-schedule-card:hover {
      background: rgba(50, 50, 50, 0.5);
      transform: translateY(-6px);
      border-color: rgba(199, 159, 94, 0.25);
    }
    .new-schedule-card.selected {
      background: rgba(199, 159, 94, 0.08);
      border-color: #c79f5e;
      box-shadow: 0 12px 30px rgba(0,0,0,0.4), 0 0 0 1px rgba(199, 159, 94, 0.2);
    }
    .new-schedule-card.selected::before {
      opacity: 1;
    }
    .card-time {
      font-size: 1.5rem;
      font-weight: 800;
      color: #c79f5e;
      letter-spacing: -0.02em;
    }
    .card-cinema {
      font-size: 0.95rem;
      color: rgba(255, 255, 255, 0.5);
      margin-bottom: 0.75rem;
      font-weight: 500;
    }
    .price-pill {
      background: #c79f5e;
      color: #111;
      padding: 0.35rem 1.1rem;
      border-radius: 50px;
      font-size: 0.8rem;
      font-weight: 700;
      display: inline-block;
      width: fit-content;
      box-shadow: 0 4px 10px rgba(199, 159, 94, 0.3);
    }
    .card-status {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      font-size: 0.85rem;
      color: rgba(255, 255, 255, 0.45);
      margin-top: 0.75rem;
      font-weight: 500;
    }
    .new-schedule-card.passed {
      opacity: 0.5;
      cursor: not-allowed;
      pointer-events: none;
      filter: grayscale(0.5);
    }
    .new-schedule-card.passed .card-time {
      color: rgba(255, 255, 255, 0.4);
    }
    .new-schedule-card.passed .price-pill {
      background: rgba(255,255,255,0.1);
      color: rgba(255,255,255,0.4);
      box-shadow: none;
    }

    /* ── Date Picker Styling ─────────────────────────────────── */
    .choose-date-section {
      margin: 2.5rem 0 1.5rem 0;
    }
    .section-title-yellow {
      color: #c79f5e;
      font-size: 1.3rem;
      margin-bottom: 1.25rem;
      font-weight: 700;
      letter-spacing: 0.02em;
    }
    .booking-date-trigger:hover {
      border-color: #c79f5e !important;
      background: rgba(60, 60, 60, 0.9) !important;
    }

    /* ── Smart Seat Suggester ─────────────────────────────────────── */
    .smart-suggester {
      background: linear-gradient(135deg, rgba(199,159,94,0.06) 0%, rgba(199,159,94,0.02) 100%);
      border: 1px solid rgba(199,159,94,0.25);
      border-radius: 16px;
      margin-bottom: 2rem;
      overflow: hidden;
      transition: border-color 0.3s;
    }
    .smart-suggester:hover { border-color: rgba(199,159,94,0.4); }

    .suggester-header {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 1rem 1.25rem;
    }
    .suggester-icon {
      font-size: 1.4rem;
      animation: sparkle 2.5s ease-in-out infinite;
    }
    @keyframes sparkle {
      0%,100% { transform: scale(1) rotate(0deg); opacity: 1; }
      50% { transform: scale(1.2) rotate(10deg); opacity: 0.85; }
    }
    .suggester-text {
      flex: 1;
      display: flex;
      flex-direction: column;
      gap: 2px;
    }
    .suggester-title {
      font-weight: 700;
      font-size: 0.95rem;
      color: #c79f5e;
      letter-spacing: 0.01em;
    }
    .suggester-sub {
      font-size: 0.78rem;
      color: rgba(255,255,255,0.45);
    }
    .suggester-toggle {
      display: flex;
      align-items: center;
      gap: 6px;
      background: rgba(199,159,94,0.12);
      border: 1px solid rgba(199,159,94,0.3);
      border-radius: 50px;
      color: #c79f5e;
      font-size: 0.8rem;
      font-weight: 600;
      padding: 0.45rem 1rem;
      cursor: pointer;
      transition: all 0.2s;
      font-family: 'Poppins', sans-serif;
      white-space: nowrap;
    }
    .suggester-toggle:hover {
      background: rgba(199,159,94,0.2);
      border-color: #c79f5e;
    }
    .suggester-toggle svg {
      transition: transform 0.3s;
    }
    .suggester-toggle.open svg { transform: rotate(180deg); }

    .suggester-panel {
      display: none;
      padding: 0 1.25rem 1.25rem;
      animation: slideDown 0.25s ease;
    }
    .suggester-panel.open { display: block; }
    @keyframes slideDown {
      from { opacity: 0; transform: translateY(-8px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    .suggester-question {
      font-size: 0.88rem;
      color: rgba(255,255,255,0.6);
      margin: 0 0 1rem 0;
      text-align: center;
    }

    .suggester-modes {
      display: flex;
      gap: 0.6rem;
      flex-wrap: wrap;
      justify-content: center;
      margin-bottom: 1rem;
    }
    .mode-btn {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 4px;
      background: rgba(255,255,255,0.04);
      border: 1px solid rgba(255,255,255,0.1);
      border-radius: 14px;
      padding: 0.8rem 1rem;
      cursor: pointer;
      transition: all 0.25s cubic-bezier(0.4,0,0.2,1);
      min-width: 90px;
      font-family: 'Poppins', sans-serif;
      color: rgba(255,255,255,0.7);
    }
    .mode-btn:hover {
      border-color: rgba(199,159,94,0.5);
      background: rgba(199,159,94,0.08);
      transform: translateY(-3px);
      color: #fff;
    }
    .mode-btn.active {
      border-color: #c79f5e;
      background: rgba(199,159,94,0.15);
      box-shadow: 0 0 20px rgba(199,159,94,0.2);
      color: #fff;
    }
    .mode-icon { font-size: 1.3rem; }
    .mode-label { font-size: 0.78rem; font-weight: 700; color: inherit; }
    .mode-desc  { font-size: 0.68rem; color: rgba(255,255,255,0.4); text-align: center; }
    .mode-btn.active .mode-desc { color: rgba(199,159,94,0.7); }

    .suggester-result {
      display: flex;
      align-items: center;
      gap: 10px;
      background: rgba(199,159,94,0.08);
      border: 1px solid rgba(199,159,94,0.3);
      border-radius: 12px;
      padding: 0.9rem 1.1rem;
      flex-wrap: wrap;
      animation: resultIn 0.35s ease;
    }
    @keyframes resultIn {
      from { opacity: 0; transform: scale(0.97); }
      to   { opacity: 1; transform: scale(1); }
    }
    .result-pulse {
      width: 8px; height: 8px;
      border-radius: 50%;
      background: #c79f5e;
      box-shadow: 0 0 0 0 rgba(199,159,94,0.6);
      animation: pulse 1.5s ease-in-out infinite;
      flex-shrink: 0;
    }
    @keyframes pulse {
      0%   { box-shadow: 0 0 0 0 rgba(199,159,94,0.6); }
      70%  { box-shadow: 0 0 0 8px rgba(199,159,94,0); }
      100% { box-shadow: 0 0 0 0 rgba(199,159,94,0); }
    }
    .result-text {
      flex: 1;
      font-size: 0.85rem;
      color: rgba(255,255,255,0.9);
      font-weight: 500;
    }
    .result-apply {
      background: #c79f5e;
      color: #111;
      border: none;
      border-radius: 50px;
      padding: 0.4rem 1rem;
      font-size: 0.78rem;
      font-weight: 700;
      cursor: pointer;
      font-family: 'Poppins', sans-serif;
      transition: all 0.2s;
    }
    .result-apply:hover { background: #e0b46d; transform: scale(1.03); }
    .result-dismiss {
      background: none;
      border: none;
      color: rgba(255,255,255,0.35);
      font-size: 0.75rem;
      cursor: pointer;
      font-family: 'Poppins', sans-serif;
      transition: color 0.2s;
      text-decoration: underline;
      padding: 0;
    }
    .result-dismiss:hover { color: rgba(255,255,255,0.7); }

    /* Suggested seat highlight */
    .seat.suggested {
      background: rgba(199,159,94,0.15) !important;
      border-color: #c79f5e !important;
      color: #c79f5e !important;
      cursor: pointer !important;
      pointer-events: auto !important;
      animation: seatGlow 1.2s ease-in-out infinite alternate;
    }
    @keyframes seatGlow {
      from { box-shadow: 0 0 6px rgba(199,159,94,0.3); }
      to   { box-shadow: 0 0 18px rgba(199,159,94,0.7), 0 0 4px rgba(199,159,94,0.4); }
    }
    .seat-demo.suggested {
      background: rgba(199,159,94,0.3);
      border: 2px solid #c79f5e;
    }

    /* ── Seat map overall improvements ─────────────────────────── */
    .screen-label {
      background: linear-gradient(90deg, transparent, rgba(199,159,94,0.35), rgba(199,159,94,0.5), rgba(199,159,94,0.35), transparent);
      color: rgba(255,255,255,0.7);
      font-size: 0.7rem;
      font-weight: 700;
      letter-spacing: 0.25em;
      text-align: center;
      padding: 0.5rem;
      border-radius: 4px 4px 0 0;
      margin-bottom: 1.5rem;
    }
    .seat-legend {
      display: flex;
      justify-content: center;
      gap: 1.5rem;
      margin-top: 1.5rem;
      flex-wrap: wrap;
    }
    .legend-item {
      display: flex;
      align-items: center;
      gap: 7px;
      font-size: 0.82rem;
      color: rgba(255,255,255,0.6);
    }
    .seat-demo {
      width: 18px; height: 18px;
      border-radius: 4px;
      border: 2px solid transparent;
    }
    .seat-demo.available  { background: rgba(255,255,255,0.1); border-color: rgba(255,255,255,0.2); }
    .seat-demo.reserved   { background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.1); opacity: 0.4; }
    .seat-demo.selected   { background: #c79f5e; border-color: #c79f5e; }
    .seat-demo.suggested  { background: rgba(199,159,94,0.25); border-color: #c79f5e; }
    @media (max-width: 600px) {
      .seats-container {
        overflow-x: auto;
        padding-bottom: 1rem;
        width: 100%;
      }
      .seats-grid {
        min-width: 300px; 
        transform: none;
        transform-origin: top center;
      }
      .screen-indicator {
        min-width: 300px;
      }
    }
    
    @media (max-width: 400px) {
      .seats-grid {
        transform: scale(0.8);
      }
    }

    /* ── Adjusting Step Container ────────────────────────────── */
    .booking-step#step-schedule {
      max-width: 1100px;
      margin: 0 auto;
      padding: 2rem 0;
    }
    
    .btn-primary {
      background: #c79f5e !important;
      color: #111 !important;
      font-weight: 700 !important;
      letter-spacing: 0.05em !important;
      box-shadow: 0 8px 25px rgba(199, 159, 94, 0.35) !important;
      transition: all 0.3s ease !important;
    }
    .btn-primary:hover:not(:disabled) {
      transform: translateY(-3px) !important;
      box-shadow: 0 12px 30px rgba(199, 159, 94, 0.45) !important;
      background: #e0b46d !important;
    }
    .btn-primary:disabled {
      background: rgba(255,255,255,0.1) !important;
      color: rgba(255,255,255,0.3) !important;
      box-shadow: none !important;
      cursor: not-allowed !important;
    }
  </style>
  <?php $isLoggedIn = !empty($_SESSION['user_id']); ?>
  <script>
    var isLoggedIn = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;
    // Set current user ID from PHP session
    var currentUserId = <?php echo $isLoggedIn ? json_encode($_SESSION['user_id']) : 'null'; ?>;
  </script>
  <?php require __DIR__ . '/includes/partials/site_header_scripts.php'; ?>
</head>

<body class="has-background">
  <?php
  $headerShowSearch = false;
  require __DIR__ . '/includes/partials/site_header.php';
  ?>

  <main>
    <div class="container">
      <?php if (!$isLoggedIn): ?>
        <div class="card" id="login-required-card" style="text-align:center;padding:48px 32px;">
          <h1 class="page-title" style="margin-bottom:16px;">Ready to Book?</h1>
          <p style="color:rgba(255,255,255,0.8);margin-bottom:32px;">Please log in or create an account to book tickets.</p>
          <div class="button-group" style="justify-content:center;gap:12px;">
            <a class="btn btn-primary" href="login.html">Log In</a>
            <a class="btn btn-secondary" href="signup.html">Create Account</a>
          </div>
        </div>
      <?php else: ?>
      <!-- Step 1: Schedule Selection -->
      <div class="booking-step active" id="step-schedule">
        <div class="card" style="padding: 2.5rem; background: rgba(20, 20, 20, 0.95);">
          <!-- Movie Header Info -->
          <div class="movie-info-header" style="display: flex; gap: 2rem; margin-bottom: 2.5rem; align-items: flex-start;">
            <div id="movie-poster" style="width: 120px; height: 180px; flex-shrink: 0; background: #333; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.5);"></div>
            <div class="movie-details">
              <h1 class="selected-movie" id="selected-movie" style="margin: 0 0 0.5rem 0; font-size: 2.2rem; color: #c79f5e;"></h1>
              <p class="movie-meta" id="movie-meta" style="color: rgba(255,255,255,0.6); font-size: 1rem; margin: 0;"></p>
            </div>
          </div>

          <div class="divider" style="height: 1px; background: rgba(255,255,255,0.05); margin-bottom: 2.5rem;"></div>

          <!-- Date Selection -->
          <div class="choose-date-section">
            <h3 class="section-title-yellow">Choose Date & Time</h3>
            <div class="booking-date-input-wrap">
              <input type="hidden" id="booking-date">
              <div class="booking-date-trigger" id="booking-date-trigger" aria-haspopup="dialog" aria-expanded="false" aria-controls="booking-date-modal-backdrop" style="background: rgba(40, 40, 40, 0.8); border: 1px solid rgba(255, 255, 255, 0.1); color: #fff; padding: 0.8rem 1.2rem; border-radius: 8px; cursor: pointer; display: flex; align-items: center; gap: 10px; width: fit-content;">
                <span class="calendar-icon">📅</span>
                <span id="booking-date-display" class="date-picker-display-text">03/25/2026</span>
              </div>
              
              <!-- Custom Date Modal -->
              <div class="booking-date-modal-backdrop" id="booking-date-modal-backdrop" aria-hidden="true" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 10000; align-items: center; justify-content: center;">
                <div class="booking-date-modal" role="dialog" aria-modal="true" aria-label="Choose Date & Time" style="background: #1a1a2e; border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; width: 90%; max-width: 400px; padding: 1.5rem; box-shadow: 0 20px 50px rgba(0,0,0,0.5);">
                  <div class="booking-date-modal-title" style="color: #c79f5e; font-size: 1.2rem; font-weight: 700; margin-bottom: 1.5rem; text-align: center;">Choose Date &amp; Time</div>
                  
                  <div class="booking-date-modal-body">
                    <div class="booking-date-input-display-wrap" style="margin-bottom: 1rem;">
                      <input type="text" id="booking-date-display-input" class="booking-date-input-display" readonly style="width: 100%; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); color: #fff; padding: 0.8rem; border-radius: 8px; text-align: center; font-weight: 600;">
                    </div>
                    
                    <div class="booking-date-month-nav" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                      <button type="button" class="booking-date-nav-btn" id="booking-date-prev" aria-label="Previous month" style="background: none; border: 1px solid rgba(255,255,255,0.1); color: #fff; width: 32px; height: 32px; border-radius: 50%; cursor: pointer;">‹</button>
                      <div class="booking-date-month-select-wrap">
                        <select id="booking-date-month-select" class="booking-date-month-select" aria-label="Month" style="background: transparent; border: none; color: #fff; font-weight: 600; font-size: 1rem; cursor: pointer;">
                          <option value="0">January</option>
                          <option value="1">February</option>
                          <option value="2">March</option>
                          <option value="3">April</option>
                          <option value="4">May</option>
                          <option value="5">June</option>
                          <option value="6">July</option>
                          <option value="7">August</option>
                          <option value="8">September</option>
                          <option value="9">October</option>
                          <option value="10">November</option>
                          <option value="11">December</option>
                        </select>
                      </div>
                      <button type="button" class="booking-date-nav-btn" id="booking-date-next" aria-label="Next month" style="background: none; border: 1px solid rgba(255,255,255,0.1); color: #fff; width: 32px; height: 32px; border-radius: 50%; cursor: pointer;">›</button>
                    </div>
                    
                    <div class="booking-date-dow" style="display: grid; grid-template-columns: repeat(7, 1fr); text-align: center; color: rgba(255,255,255,0.5); font-size: 0.8rem; font-weight: 700; margin-bottom: 0.5rem;">
                      <div>Su</div><div>Mo</div><div>Tu</div><div>We</div><div>Th</div><div>Fr</div><div>Sa</div>
                    </div>
                    
                    <div class="booking-date-days-grid" id="booking-date-days-grid" style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 4px;"></div>
                    
                    <div class="booking-date-footer" style="margin-top: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
                      <button type="button" class="booking-date-clear-btn" id="booking-date-clear" style="background: none; border: none; color: rgba(255,255,255,0.5); cursor: pointer; font-size: 0.9rem;">Clear</button>
                      <div class="booking-date-seats-hint" id="booking-date-seats-hint" style="color: #c79f5e; font-size: 0.8rem; font-weight: 600;">80 seats available</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Theatre Selection Bar with Radio Pills -->
          <div class="theatre-selection-bar" id="theatre-selection-bar">
            <span class="theatre-label">Theatre:</span>
            <div class="theatre-pills" id="theatre-pills">
              <label class="theatre-pill">
                <input type="radio" name="theatre-type" value="Standard" hidden>
                <span class="pill-dot"></span>
                <span class="pill-text">Standard</span>
              </label>
              <label class="theatre-pill">
                <input type="radio" name="theatre-type" value="3D" hidden>
                <span class="pill-dot"></span>
                <span class="pill-text">3D</span>
              </label>
              <label class="theatre-pill">
                <input type="radio" name="theatre-type" value="IMAX" hidden>
                <span class="pill-dot"></span>
                <span class="pill-text">IMAX</span>
              </label>
              <label class="theatre-pill">
                <input type="radio" name="theatre-type" value="Directors Club" hidden>
                <span class="pill-dot"></span>
                <span class="pill-text">Directors Club</span>
              </label>
            </div>
          </div>
          <p class="theatre-hint" id="theatre-hint">⚠ Please select a theatre type to see available schedules.</p>

          <!-- Schedule Grid -->
          <div class="schedule-grid-container" id="schedule-cards">
            <!-- Dynamically generated schedule cards -->
          </div>

          <!-- Action Button -->
          <div style="display: flex; justify-content: center; margin-top: 2rem;">
            <button class="btn btn-primary" id="continue-to-seats" style="width: 100%; max-width: 100%; padding: 1.2rem; font-size: 1.1rem; text-transform: none; border-radius: 12px;" disabled>Continue to Select Seats</button>
          </div>
        </div>
      </div>

      <!-- Step 2: Seat Selection -->
      <div class="booking-step" id="step-seats">
        <h1 class="page-title">Select Seats</h1>
        
        <div class="card">
          <div class="booking-summary-small">
            <p><strong>Movie:</strong> <span id="summary-movie-2"></span></p>
            <p><strong>Theatre:</strong> <span id="summary-theatre-type-2">Standard</span></p>
            <p><strong>Schedule:</strong> <span id="summary-schedule-2"></span></p>
          </div>

          <!-- ✨ Smart Seat Suggester -->
          <div class="smart-suggester" id="smart-suggester">
            <div class="suggester-header">
              <div class="suggester-icon">✨</div>
              <div class="suggester-text">
                <span class="suggester-title">Smart Seat Suggester</span>
                <span class="suggester-sub">Let us find the perfect seats for you</span>
              </div>
              <button class="suggester-toggle" id="suggester-toggle" aria-expanded="false">
                <span>Get Suggestion</span>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
              </button>
            </div>

            <div class="suggester-panel" id="suggester-panel">
              <p class="suggester-question">How are you watching today?</p>
              <div class="suggester-modes">
                <button class="mode-btn" data-mode="solo" data-seats="1">
                  <span class="mode-icon">🎬</span>
                  <span class="mode-label">Solo</span>
                  <span class="mode-desc">Best center view</span>
                </button>
                <button class="mode-btn" data-mode="date" data-seats="2">
                  <span class="mode-icon">💑</span>
                  <span class="mode-label">On a Date</span>
                  <span class="mode-desc">Cozy side-by-side</span>
                </button>
                <button class="mode-btn" data-mode="companion" data-seats="2">
                  <span class="mode-icon">👫</span>
                  <span class="mode-label">With Companion</span>
                  <span class="mode-desc">Great sightlines</span>
                </button>
                <button class="mode-btn" data-mode="group" data-seats="4">
                  <span class="mode-icon">👥</span>
                  <span class="mode-label">Group</span>
                  <span class="mode-desc">Row together</span>
                </button>
                <button class="mode-btn" data-mode="private" data-seats="2">
                  <span class="mode-icon">🌙</span>
                  <span class="mode-label">More Private</span>
                  <span class="mode-desc">Corner, away from crowd</span>
                </button>
              </div>

              <div class="suggester-result" id="suggester-result" style="display:none;">
                <div class="result-pulse"></div>
                <span class="result-text" id="suggester-result-text"></span>
                <button class="result-apply" id="suggester-apply">Apply These Seats</button>
                <button class="result-dismiss" id="suggester-dismiss">I'll choose myself</button>
              </div>
            </div>
          </div>

          <div class="seats-section">
            <div class="screen-label">SCREEN</div>
            <div class="seats-grid" id="seats-grid">
              <!-- Seats will be generated here -->
            </div>
            
            <div class="seat-legend">
              <div class="legend-item">
                <div class="seat-demo available"></div>
                <span>Available</span>
              </div>
              <div class="legend-item">
                <div class="seat-demo reserved"></div>
                <span>Reserved</span>
              </div>
              <div class="legend-item">
                <div class="seat-demo selected"></div>
                <span>Selected</span>
              </div>
              <div class="legend-item">
                <div class="seat-demo suggested"></div>
                <span>Suggested</span>
              </div>
            </div>

            <div class="selected-seats-info">
              <p><strong>Selected Seats:</strong> <span id="selected-seats-display">None</span></p>
              <p class="price-display"><strong>Total:</strong> ₱<span id="total-price">0</span></p>
            </div>
          </div>

          <div class="button-group">
            <button class="btn btn-secondary" id="back-to-schedule-seats">Back</button>
            <button class="btn btn-primary" id="continue-to-addons" disabled>Continue</button>
          </div>
        </div>
      </div>

      <!-- Step 3: Food & Drinks (Optional) -->
      <div class="booking-step" id="step-addons">
        <h1 class="page-title">Food & Drinks</h1>
        <div class="card">
          <div class="booking-summary-small">
            <p><strong>Movie:</strong> <span id="summary-movie-addons"></span></p>
            <p><strong>Schedule:</strong> <span id="summary-schedule-addons"></span></p>
            <p><strong>Seats:</strong> <span id="summary-seats-addons"></span></p>
          </div>

          <div class="quantity-section" style="padding-top:0;">
            <h3 class="section-subtitle">Would you like Food & Drinks?</h3>
            <p style="text-align:center;color:rgba(255,255,255,0.8);margin-top:-8px;">Add popcorn and drinks to enjoy your movie.</p>
          </div>

          <div id="addons-list">
            <!-- Addon items will be rendered here -->
          </div>

          <div class="selected-seats-info">
            <p><strong>Food & Drinks Total:</strong> ₱<span id="addons-total">0.00</span></p>
          </div>

          <div class="button-group">
            <button class="btn btn-secondary" id="back-to-seats-addons">Back</button>
            <button class="btn btn-primary" id="continue-to-vehicle">Continue</button>
          </div>
        </div>
      </div>

      <!-- Step 3b: Vehicle / Parking (Online only) -->
      <div class="booking-step" id="step-vehicle">
        <h1 class="page-title">Car Parking</h1>
        <div class="card">
          <div class="booking-summary-small">
            <p><strong>Movie:</strong> <span id="summary-movie-vehicle"></span></p>
            <p><strong>Schedule:</strong> <span id="summary-schedule-vehicle"></span></p>
            <p><strong>Seats:</strong> <span id="summary-seats-vehicle"></span></p>
          </div>
          <div class="quantity-section" style="padding-top:0;">
            <h3 class="section-subtitle">Do you have a vehicle?</h3>
            <p style="text-align:center;color:rgba(255,255,255,0.8);margin-top:-8px;">Select Yes to reserve a parking space for your booking.</p>
          </div>
          <div class="vehicle-options" style="display:flex;gap:16px;justify-content:center;flex-wrap:wrap;margin:24px 0;">
            <button type="button" class="btn btn-secondary vehicle-option" id="vehicle-yes" data-vehicle="yes">Yes</button>
            <button type="button" class="btn btn-secondary vehicle-option" id="vehicle-none" data-vehicle="none">None</button>
          </div>

          <!-- Vehicle detail fields, shown only when "Yes" is selected -->
          <div id="vehicle-details-fields" style="display:none;margin-top:8px;">
            <div class="form-group">
              <label for="vehicle-plate">Plate Number</label>
              <input type="text" id="vehicle-plate" class="form-input" maxlength="7" minlength="6" placeholder="e.g. ABC1234" style="text-transform:uppercase;">
              <small class="form-hint">Enter your plate number exactly as it appears on your vehicle.</small>
            </div>
            <div class="form-group">
              <label for="vehicle-type">Vehicle Type</label>
              <select id="vehicle-type" class="form-input">
                <option value="" disabled selected>Select vehicle type</option>
                <option value="Sedan">Sedan</option>
                <option value="SUV">SUV</option>
                <option value="Pickup Truck">Pickup Truck</option>
                <option value="Van">Van</option>
                <option value="Hatchback">Hatchback</option>
                <option value="Motorcycle">Motorcycle</option>
                <option value="Other">Other</option>
              </select>
            </div>
            <div class="form-group">
              <label for="vehicle-color">Vehicle Color</label>
              <div style="display:flex;align-items:center;gap:12px;">
                <input type="color" id="vehicle-color-picker" value="#cccccc" style="width:44px;height:44px;border:none;background:none;cursor:pointer;padding:0;border-radius:8px;">
                <input type="text" id="vehicle-color" class="form-input" maxlength="30" placeholder="e.g. Silver, Pearl White, Midnight Blue" style="flex:1;">
              </div>
              <small class="form-hint">Pick a color or type the color name of your vehicle.</small>
            </div>
          </div>

          <div class="button-group">
            <button class="btn btn-secondary" id="back-to-addons-vehicle">Back</button>
            <button class="btn btn-primary" id="continue-to-payment-from-vehicle">Continue to Payment</button>
          </div>
        </div>
      </div>

      <!-- Step 4: Payment -->
      <div class="booking-step" id="step-payment">
        <h1 class="page-title">Payment</h1>
        
        <div class="card">
          <div class="payment-layout">
            <div class="booking-summary">
            <h3>Booking Summary</h3>
            <div class="summary-details">
              <p><strong>Movie:</strong> <span id="summary-movie-3"></span></p>
              <p><strong>Theatre:</strong> <span id="summary-theatre-type-3">Standard</span></p>
              <p><strong>Schedule:</strong> <span id="summary-schedule-3"></span></p>
              <p><strong>Seats:</strong> <span id="summary-seats"></span></p>
              <p><strong>Tickets:</strong> <span id="summary-quantity-2"></span></p>
              <p><strong>Food & Drinks:</strong> <span id="summary-addons">None</span></p>
              <p id="summary-parking-row" style="display:none;"><strong>Parking:</strong> <span id="summary-parking"></span></p>
              <p class="summary-total"><strong>Total Amount:</strong> ₱<span id="summary-price"></span></p>
            </div>
          </div>

          <div class="payment-section">
            <h3 class="section-subtitle">Payment Information</h3>
            <form id="payment-form">
              <div class="form-group">
                <label>Discount (PWD / Senior)</label>
                <div style="display:flex;gap:10px;flex-wrap:wrap;font-size:0.85rem;color:rgba(255,255,255,0.85);">
                  <label><input type="radio" name="discount-type" value="" checked> None</label>
                  <label><input type="radio" name="discount-type" value="pwd"> PWD</label>
                  <label><input type="radio" name="discount-type" value="senior"> Senior</label>
                </div>
              </div>
              <div id="discount-extra-fields" class="payment-reveal">
                <div class="form-group" id="discount-proof-group" style="display:none;">
                  <label for="discount-proof">Upload PWD / Senior ID (JPG/PNG)</label>
                  <input type="file" id="discount-proof" class="form-input" accept="image/*">
                  <small class="form-hint">Required only when claiming a PWD or Senior discount for this booking.</small>
                </div>
                <div class="form-group" id="discount-id-group" style="display:none;">
                  <label for="discount-id-number">PWD / Senior ID Number(s)</label>
                  <input type="text" id="discount-id-number" class="form-input" maxlength="255" placeholder="Enter ID number(s) separated by commas">
                </div>
              </div>
              <div class="form-group">
                <label>Select payment method (E-Wallet only)</label>
                <div class="payment-methods-grid">
                  <button type="button" class="payment-provider-card" data-method="ewallet" data-provider="gcash">
                    <img src="paymentlogo/gcash.png" alt="GCash logo">
                  </button>
                  <button type="button" class="payment-provider-card" data-method="ewallet" data-provider="maya">
                    <img src="paymentlogo/maya2.png" alt="Maya logo">
                  </button>
                  <button type="button" class="payment-provider-card" data-method="ewallet" data-provider="paypal">
                    <img src="paymentlogo/paypal.png" alt="PayPal logo">
                  </button>
                  <button type="button" class="payment-provider-card" data-method="ewallet" data-provider="googlepay">
                    <img src="paymentlogo/google.png" alt="Google Pay logo">
                  </button>
                </div>
              </div>
              <div id="payment-details-panel" class="payment-details-panel">
                <input type="hidden" id="payment-method" value="ewallet">
                <input type="hidden" id="ewallet-provider" value="gcash">
                <div id="card-fields" style="display:none;"></div>
                <div id="ewallet-fields" class="payment-reveal">
                  <div class="form-group">
                    <label for="ewallet-number">Mobile Number</label>
                    <input type="tel" id="ewallet-number" class="form-input" placeholder="09XXXXXXXXX (11 digits, must start with 09)" maxlength="11">
                    <small class="form-hint">Must start with 09. Example: 09171234567</small>
                  </div>
                </div>
              </div>
            </form>
          </div>
          </div>

          <div class="button-group">
            <button class="btn btn-secondary" id="back-to-addons">Back</button>
            <button class="btn btn-primary" id="complete-payment">Pay ₱<span id="payment-amount">0</span></button>
          </div>
        </div>
      </div>

      <!-- Step 5: Confirmation -->
      <div class="booking-step" id="step-confirmation">
        <div class="confirmation-card" data-customer-name="<?php echo htmlspecialchars($_SESSION['user_name'] ?? $_SESSION['first_name'] ?? 'Guest'); ?>">
          <div class="success-animation">
            <div class="success-icon">✓</div>
          </div>
          
          <h1 class="confirmation-title">Congratulations!</h1>
          <p class="confirmation-subtitle">Your booking is now confirmed.</p>

          <div class="receipt-outer">
            <p class="receipt-banner-title">THIS IS YOUR TICKET</p>
            <p class="receipt-banner-sub">Please show it on your phone when you arrive at the venue.</p>

            <div class="ticket-receipt">
              <div class="ticket-receipt-top">
                <div class="ticket-receipt-logo">CineFlix</div>
                <div class="ticket-receipt-checkin">
                  <span class="ticket-label">CHECK-IN CODE</span>
                  <span class="ticket-value ticket-checkin-code" id="receipt-booking-id"></span>
                </div>
              </div>

              <div class="ticket-receipt-qr">
                <div id="qr-code"></div>
              </div>

              <div class="ticket-receipt-details">
                <div class="ticket-detail-row">
                  <span class="ticket-label">MOVIE NAME</span>
                  <span class="ticket-value" id="receipt-movie"></span>
                </div>
                <div class="ticket-detail-row">
                  <span class="ticket-label">THEATRE TYPE</span>
                  <span class="ticket-value" id="receipt-theatre-type">Standard</span>
                </div>
                <div class="ticket-detail-row">
                  <span class="ticket-label">DATE AND TIME</span>
                  <span class="ticket-value" id="receipt-schedule"></span>
                </div>
                <div class="ticket-detail-row">
                  <span class="ticket-label">SEATS</span>
                  <span class="ticket-value" id="receipt-seats"></span>
                </div>
                <div class="ticket-detail-row">
                  <span class="ticket-label">NUMBER OF TICKETS</span>
                  <span class="ticket-value" id="receipt-quantity"></span>
                </div>
                <div class="ticket-detail-row" id="receipt-parking-row" style="display:none;">
                  <span class="ticket-label">PARKING</span>
                  <span class="ticket-value" id="receipt-parking"></span>
                </div>
                <div class="ticket-detail-row">
                  <span class="ticket-label">PAYMENT METHOD</span>
                  <span class="ticket-value" id="receipt-payment-method"></span>
                </div>
                <div class="ticket-detail-row receipt-discount-block" id="receipt-discount-block" style="display:none;">
                  <span class="ticket-label">SUBTOTAL (BEFORE DISCOUNT)</span>
                  <span class="ticket-value">₱<span id="receipt-original-total"></span></span>
                </div>
                <div class="ticket-detail-row receipt-discount-block" id="receipt-discount-line-row" style="display:none;">
                  <span class="ticket-label">PWD / SENIOR DISCOUNT (-20%)</span>
                  <span class="ticket-value receipt-discount-amount">-₱<span id="receipt-discount-amount"></span></span>
                </div>
                <div id="receipt-discount-pending" style="display:none; padding: 12px 16px; margin: 8px 0; border-radius: 8px; background: rgba(199,159,94,0.12); border: 1px solid rgba(199,159,94,0.3);">
                  <p style="margin:0 0 4px 0; font-size: 0.82rem; font-weight: 700; color: #c79f5e;">⏳ DISCOUNT PENDING ADMIN APPROVAL</p>
                  <p style="margin:0; font-size: 0.75rem; color: rgba(0,0,0,0.65); line-height: 1.4;">Your PWD / Senior discount request has been submitted and is awaiting admin approval. You have been charged the full price for now. Once approved, the overpaid amount can be claimed at the cashier.</p>
                </div>
                <div class="ticket-detail-row" id="receipt-amount-paid-row" style="display:none;">
                  <span class="ticket-label">AMOUNT TENDERED</span>
                  <span class="ticket-value">₱<span id="receipt-amount-paid"></span></span>
                </div>
                <div class="ticket-detail-row" id="receipt-change-row" style="display:none;">
                  <span class="ticket-label">CHANGE</span>
                  <span class="ticket-value">₱<span id="receipt-change"></span></span>
                </div>
              </div>

              <div class="ticket-receipt-perforation"></div>

              <div class="ticket-receipt-stub">
                <div class="stub-left">
                  <div class="ticket-detail-row">
                    <span class="ticket-label">CUSTOMER NAME</span>
                    <span class="ticket-value stub-value" id="receipt-customer-name">—</span>
                  </div>
                  <div class="ticket-detail-row">
                    <span class="ticket-label">TICKET PRICE</span>
                    <span class="ticket-value stub-value">₱<span id="receipt-price"></span></span>
                  </div>
                </div>
                <div class="stub-right">
                  <div class="ticket-detail-row">
                    <span class="ticket-label">SEAT NUMBER</span>
                    <span class="ticket-value stub-value stub-seats" id="receipt-seats-stub"></span>
                  </div>
                </div>
              </div>

              <div class="ticket-receipt-footer">CINEFLIX</div>
            </div>
          </div>

          <div class="button-group">
            <button class="btn btn-primary" onclick="window.location.href='status.php'">View My Bookings</button>
            <button class="btn btn-secondary" onclick="window.location.href='homepage.php'">Back to Home</button>
          </div>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </main>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
  <link rel="stylesheet" href="chatbot.css">
  <script src="chatbot.js"></script>
  <script src="booking.js"></script>

  <script>
  /**
   * Calendar date-restriction patch
   * ─────────────────────────────────────────────────────────────────────────
   * Rules:
   *  1. Any date strictly before today → disabled (not clickable, greyed-out).
   *  2. Today → disabled IF all schedule end-times for today have already passed.
   *     Otherwise today remains selectable.
   *  3. The minimum selectable date is therefore "today" (if any shows remain)
   *     or "tomorrow" (if all of today's shows are done).
   * ─────────────────────────────────────────────────────────────────────────
   */
  (function () {
    'use strict';

    /* ── helpers ── */
    function todayMidnight() {
      var d = new Date();
      d.setHours(0, 0, 0, 0);
      return d;
    }

    /**
     * Given the schedule cards currently rendered in the DOM,
     * return the latest end-time as a Date object (today's date + that time).
     * Returns null if no schedules are visible.
     */
    function latestEndTimeToday() {
      var cards = document.querySelectorAll('#schedule-cards .schedule-card, #schedule-cards [class*="schedule"]');
      if (!cards.length) return null;

      var latest = null;
      cards.forEach(function (card) {
        /* Schedule cards typically show text like "2:30 PM – 4:25 PM" */
        var text = card.textContent || '';
        /* Match patterns: "HH:MM AM/PM – HH:MM AM/PM" or "HH:MM AM/PM - HH:MM AM/PM" */
        var match = text.match(/(\d{1,2}:\d{2}\s*[AP]M)\s*[–\-]\s*(\d{1,2}:\d{2}\s*[AP]M)/i);
        if (match) {
          var endStr = match[2].trim();
          var endDate = parseTimeString(endStr);
          if (!latest || endDate > latest) latest = endDate;
        }
      });
      return latest;
    }

    /** Parse "4:25 PM" → Date with today's date + that time */
    function parseTimeString(str) {
      var m = str.match(/(\d{1,2}):(\d{2})\s*([AP]M)/i);
      if (!m) return null;
      var h = parseInt(m[1], 10);
      var min = parseInt(m[2], 10);
      var ampm = m[3].toUpperCase();
      if (ampm === 'PM' && h !== 12) h += 12;
      if (ampm === 'AM' && h === 12) h = 0;
      var d = new Date();
      d.setHours(h, min, 0, 0);
      return d;
    }

    /**
     * Compute the earliest bookable date:
     *  - If any schedule for today ends in the future → today is fine.
     *  - Otherwise → tomorrow.
     */
    function computeMinBookableDate() {
      var today = new Date();
      var latestEnd = latestEndTimeToday();
      if (latestEnd && latestEnd > today) {
        /* At least one show today hasn't ended yet */
        var d = new Date();
        d.setHours(0, 0, 0, 0);
        return d;
      }
      /* All shows today have ended (or none loaded yet for today) → start from tomorrow */
      var tomorrow = new Date();
      tomorrow.setDate(tomorrow.getDate() + 1);
      tomorrow.setHours(0, 0, 0, 0);
      return tomorrow;
    }

    /**
     * Apply disabled class to every calendar day cell that falls before
     * the minimum bookable date, and remove it from those that are >= min.
     */
    function applyDateRestrictions() {
      var minDate = computeMinBookableDate();
      var grid = document.getElementById('booking-date-days-grid');
      if (!grid) return;

      var cells = grid.querySelectorAll('[data-date], .booking-date-day, [class*="day"]');
      cells.forEach(function (cell) {
        /* Try data-date attribute first (ISO: YYYY-MM-DD) */
        var dateStr = cell.getAttribute('data-date');
        var cellDate = null;

        if (dateStr) {
          cellDate = new Date(dateStr + 'T00:00:00');
        } else {
          /* Fall back: read the cell's text as a day number, combined with
             the currently visible month/year from the select dropdown.      */
          var dayNum = parseInt(cell.textContent, 10);
          if (!dayNum) return; /* empty filler cell */
          var sel = document.getElementById('booking-date-month-select');
          if (!sel) return;
          var monthVal = parseInt(sel.value, 10);
          /* Derive year from select option text, e.g. "March 2026" */
          var optText = sel.options[sel.selectedIndex] ? sel.options[sel.selectedIndex].text : '';
          var yearMatch = optText.match(/(\d{4})/);
          var year = yearMatch ? parseInt(yearMatch[1], 10) : new Date().getFullYear();
          cellDate = new Date(year, monthVal, dayNum);
        }

        if (!cellDate) return;

        if (cellDate < minDate) {
          cell.classList.add('day-disabled');
          cell.setAttribute('tabindex', '-1');
          cell.setAttribute('aria-disabled', 'true');
        } else {
          cell.classList.remove('day-disabled');
          cell.removeAttribute('aria-disabled');
        }
      });
    }

    /* ── Wire up observers ── */
    document.addEventListener('DOMContentLoaded', function () {

      /* Run once on load */
      applyDateRestrictions();

      /* Re-run whenever the calendar grid DOM changes (month navigation) */
      var grid = document.getElementById('booking-date-days-grid');
      if (grid && window.MutationObserver) {
        var obs = new MutationObserver(function () {
          applyDateRestrictions();
        });
        obs.observe(grid, { childList: true, subtree: true });
      }

      /* Re-run when month select changes */
      var monthSel = document.getElementById('booking-date-month-select');
      if (monthSel) {
        monthSel.addEventListener('change', function () {
          setTimeout(applyDateRestrictions, 50);
        });
      }

      /* Re-run when schedule cards update (today's schedules might load async) */
      var scheduleCards = document.getElementById('schedule-cards');
      if (scheduleCards && window.MutationObserver) {
        var schedObs = new MutationObserver(function () {
          applyDateRestrictions();
        });
        schedObs.observe(scheduleCards, { childList: true, subtree: true });
      }

      /* Intercept clicks on disabled cells just in case pointer-events leak */
      document.addEventListener('click', function (e) {
        var cell = e.target.closest('.day-disabled');
        if (cell) {
          e.preventDefault();
          e.stopImmediatePropagation();
        }
      }, true);
    });

  }());
  </script>
  <script>
    // Clear any stale theatre selection on fresh page load
    window.selectedTheatreType = null;

    // ── Theatre pill selection & gate logic (runs AFTER booking.js) ──
    document.addEventListener('DOMContentLoaded', function () {
      var radios = document.querySelectorAll('input[name="theatre-type"]');
      var continueBtn = document.getElementById('continue-to-seats');
      var hint = document.getElementById('theatre-hint');

      function onTheatreChange() {
        var selected = document.querySelector('input[name="theatre-type"]:checked');
        if (selected) {
          if (hint) hint.style.display = 'none';
          window.selectedTheatreType = selected.value;
          if (typeof bookingData !== 'undefined') bookingData.theatreType = selected.value;
          var t2 = document.getElementById('summary-theatre-type-2');
          if (t2) t2.textContent = selected.value;
          // Deselect schedule cards & disable continue until a slot is picked
          document.querySelectorAll('.new-schedule-card').forEach(function(c) { c.classList.remove('selected'); });
          if (continueBtn) continueBtn.disabled = true;
          // Re-render schedules for this theatre type
          if (typeof generateSchedules === 'function') generateSchedules();
        } else {
          window.selectedTheatreType = null;
          if (continueBtn) continueBtn.disabled = true;
          if (hint) hint.style.display = 'block';
          // Clear schedule cards
          var sc = document.getElementById('schedule-cards');
          if (sc) sc.innerHTML = '';
        }
      }

      radios.forEach(function(radio) {
        radio.addEventListener('change', onTheatreChange);
      });

      if (continueBtn) {
        continueBtn.addEventListener('click', function(e) {
          if (!document.querySelector('input[name="theatre-type"]:checked')) {
            e.preventDefault();
            e.stopImmediatePropagation();
            if (hint) hint.style.display = 'block';
          }
        }, true);
      }

      // Patch: re-render schedules when date changes (so theatre filter stays active)
      var dateHidden = document.getElementById('booking-date');
      if (dateHidden) {
        var _origDateVal = dateHidden.value;
        Object.defineProperty(dateHidden, 'value', {
          get: function() { return _origDateVal; },
          set: function(v) {
            _origDateVal = v;
            if (window.selectedTheatreType && typeof generateSchedules === 'function') {
              setTimeout(generateSchedules, 10);
            }
          }
        });
      }

      // Init — no pill selected yet, hint visible, cards empty
      onTheatreChange();
    });
  </script>
  <script>
    // ── Vehicle / Parking field logic ────────────────────────────────
    document.addEventListener('DOMContentLoaded', function () {

      var btnYes    = document.getElementById('vehicle-yes');
      var btnNone   = document.getElementById('vehicle-none');
      var detailBox = document.getElementById('vehicle-details-fields');
      var picker    = document.getElementById('vehicle-color-picker');
      var colorText = document.getElementById('vehicle-color');

      // Show / hide detail fields based on Yes / None selection
      function setVehicleOption(choice) {
        if (!btnYes || !btnNone || !detailBox) return;
        if (choice === 'yes') {
          detailBox.style.display = 'block';
          btnYes.classList.add('btn-primary');
          btnYes.classList.remove('btn-secondary');
          btnNone.classList.add('btn-secondary');
          btnNone.classList.remove('btn-primary');
        } else {
          detailBox.style.display = 'none';
          btnNone.classList.add('btn-primary');
          btnNone.classList.remove('btn-secondary');
          btnYes.classList.add('btn-secondary');
          btnYes.classList.remove('btn-primary');
          var plate = document.getElementById('vehicle-plate');
          var type  = document.getElementById('vehicle-type');
          if (plate) plate.value = '';
          if (type)  type.value  = '';
          if (picker) picker.value = '#cccccc';
          if (colorText) colorText.value = '';
        }
      }

      if (btnYes)  btnYes.addEventListener('click', function () { setVehicleOption('yes'); });
      if (btnNone) btnNone.addEventListener('click', function () { setVehicleOption('none'); });

      // Sync color picker -> text
      if (picker && colorText) {
        picker.addEventListener('input', function () {
          if (!colorText.value || colorText.value.startsWith('#')) {
            colorText.value = picker.value;
          }
        });
        // Sync text -> picker (CSS color or hex)
        colorText.addEventListener('input', function () {
          var tmp = document.createElement('div');
          tmp.style.color = colorText.value;
          document.body.appendChild(tmp);
          var computed = window.getComputedStyle(tmp).color;
          document.body.removeChild(tmp);
          var m = computed.match(/\d+/g);
          if (m && m.length >= 3) {
            picker.value = '#' +
              ('0' + parseInt(m[0]).toString(16)).slice(-2) +
              ('0' + parseInt(m[1]).toString(16)).slice(-2) +
              ('0' + parseInt(m[2]).toString(16)).slice(-2);
          }
        });
      }

      // Auto-uppercase plate number
      var plateInput = document.getElementById('vehicle-plate');
      if (plateInput) {
        plateInput.addEventListener('input', function () {
          var pos = this.selectionStart;
          this.value = this.value.toUpperCase();
          this.setSelectionRange(pos, pos);
        });
      }

      // Inject parking info into the summary + receipt when Continue is clicked
      var continueBtn = document.getElementById('continue-to-payment-from-vehicle');
      if (continueBtn) {
        continueBtn.addEventListener('click', function () {
          var hasVehicle = btnYes && btnYes.classList.contains('btn-primary');
          var summaryRow = document.getElementById('summary-parking-row');
          var summaryVal = document.getElementById('summary-parking');
          var receiptRow = document.getElementById('receipt-parking-row');
          var receiptVal = document.getElementById('receipt-parking');

          if (hasVehicle) {
            var plate = (document.getElementById('vehicle-plate') || {}).value || '—';
            var type  = (document.getElementById('vehicle-type')  || {}).value || '—';
            var color = (document.getElementById('vehicle-color') || {}).value || '—';
            var info  = plate + ' \u00b7 ' + type + ' \u00b7 ' + color;
            if (summaryRow) summaryRow.style.display = '';
            if (summaryVal) summaryVal.textContent = info;
            if (receiptRow) receiptRow.style.display = '';
            if (receiptVal) receiptVal.textContent = info;
          } else {
            if (summaryRow) summaryRow.style.display = 'none';
            if (receiptRow) receiptRow.style.display = 'none';
          }
        }, true);
      }
    });
  </script>
</body>
</html>