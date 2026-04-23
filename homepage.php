<?php session_start(); ?>
<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

$conn = db_get_connection();
cineflix_bootstrap_session_from_cookie($conn);

// ── Shortcut key AJAX handlers ────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $raw  = file_get_contents('php://input');
  $json = json_decode($raw, true);

  // Ctrl+Shift+A — Admin login (matches login.php: email=admin, password=1)
  if (($json['action'] ?? '') === 'admin_login') {
    header('Content-Type: application/json');
    $pass = $json['password'] ?? '';
    if ($pass === '1') {
      $_SESSION['is_admin']   = true;
      $_SESSION['admin_name'] = 'Administrator';
      echo json_encode(['success' => true]);
    } else {
      echo json_encode(['success' => false]);
    }
    exit();
  }

  // Ctrl+Shift+F — Staff login (matches login.php: email=staff, password=1)
  if (($json['action'] ?? '') === 'staff_login') {
    header('Content-Type: application/json');
    $pass = $json['password'] ?? '';
    if ($pass === '1') {
      $_SESSION['is_staff']   = true;
      $_SESSION['staff_name'] = 'Staff';
      echo json_encode(['success' => true]);
    } else {
      echo json_encode(['success' => false]);
    }
    exit();
  }
}

$userFullName = $_SESSION['user_name'] ?? 'Not set';
$userUsername = $_SESSION['username'] ?? 'Not set';
$userEmail = $_SESSION['user_email'] ?? 'Not set';

// Fetch profile picture and username for logged-in regular user
$navProfilePic = $_SESSION['profile_picture'] ?? ''; // use session instantly
$navUsername   = '';
if (!empty($_SESSION['user_id'])) {
  $uid = (int)$_SESSION['user_id'];
  // Auto-detect table name (case-insensitive)
  $userTableName = '';
  $allTablesRes2 = $conn->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE()");
  $allTables2 = [];
  if ($allTablesRes2) { while ($t = $allTablesRes2->fetch_assoc()) $allTables2[strtolower($t['TABLE_NAME'])] = $t['TABLE_NAME']; }
  foreach (['customeruser','users','user','customer','customers','accounts','member','members','tbl_users'] as $c) {
    if (isset($allTables2[$c])) { $userTableName = $allTables2[$c]; break; }
  }
  if ($userTableName) {
    // Get all column names
    $colsRes2 = $conn->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='" . $conn->real_escape_string($userTableName) . "'");
    $tCols2 = [];
    if ($colsRes2) { while ($c = $colsRes2->fetch_assoc()) $tCols2[] = strtolower($c['COLUMN_NAME']); }
    $idCol2 = 'id';
    foreach (['customerid','id','user_id','customer_id'] as $c) { if (in_array($c,$tCols2)) { $idCol2=$c; break; } }
    $unCol2 = 'username';
    foreach (['username','user_name'] as $c) { if (in_array($c,$tCols2)) { $unCol2=$c; break; } }
    $hasPp = in_array('profile_picture', $tCols2);

    // If column doesn't exist yet, create it now
    if (!$hasPp) {
      $conn->query("ALTER TABLE `$userTableName` ADD COLUMN `profile_picture` VARCHAR(500) NULL DEFAULT NULL");
      $hasPp = true; // optimistically assume it was created
    }

    $selCols = $hasPp ? "`profile_picture`, `$unCol2`" : "`$unCol2`";
    $dbRow = @$conn->query("SELECT $selCols FROM `$userTableName` WHERE `$idCol2` = $uid LIMIT 1");
    if ($dbRow && ($dr = $dbRow->fetch_assoc())) {
      $navUsername = $dr[$unCol2] ?? '';
      if ($hasPp && !empty($dr['profile_picture'])) {
        $navProfilePic = $dr['profile_picture'];
        $_SESSION['profile_picture'] = $navProfilePic;
      }
      if ($navUsername) $_SESSION['username'] = $navUsername;
    }
  }
  // Fall back to session if DB lookup failed
  if (!$navUsername) $navUsername = $_SESSION['username'] ?? '';
}

// Fetch movies added by admin from DB
$dbMovies = [];

// Safely add TrailerURL column if it doesn't exist yet
$colCheck = $conn->query("SELECT COUNT(*) AS cnt FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'Movie' AND COLUMN_NAME = 'TrailerURL'");
if ($colCheck) {
  $colRow = $colCheck->fetch_assoc();
  if ((int)$colRow['cnt'] === 0) {
    $conn->query("ALTER TABLE Movie ADD COLUMN TrailerURL VARCHAR(500) NULL DEFAULT NULL");
  }
}

// Safely add section column if it doesn't exist yet
$secColCheck = $conn->query("SELECT COUNT(*) AS cnt FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'Movie' AND COLUMN_NAME = 'section'");
if ($secColCheck && (int)$secColCheck->fetch_assoc()['cnt'] === 0) {
  $conn->query("ALTER TABLE Movie ADD COLUMN `section` VARCHAR(20) NOT NULL DEFAULT 'more_movies'");
}

// Safely add Description column (movie description)
$descColCheck = $conn->query("SELECT COUNT(*) AS cnt FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'Movie' AND COLUMN_NAME = 'Description'");
$hasDescription = $descColCheck ? ((int)$descColCheck->fetch_assoc()['cnt'] > 0) : false;

// Safely add PosterPath column check
$posterColCheck = $conn->query("SELECT COUNT(*) AS cnt FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'Movie' AND COLUMN_NAME = 'PosterPath'");
$hasPosterPath = $posterColCheck ? ((int)$posterColCheck->fetch_assoc()['cnt'] > 0) : false;

// Safely add TrailerURL column check
$trailerColCheck = $conn->query("SELECT COUNT(*) AS cnt FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'Movie' AND COLUMN_NAME = 'TrailerURL'");
$hasTrailerURL = $trailerColCheck ? ((int)$trailerColCheck->fetch_assoc()['cnt'] > 0) : false;

// Build query based on available columns
$selectCols = "Title, Genre, Duration, Rating";
if ($hasDescription) $selectCols .= ", Description";
if ($hasPosterPath) $selectCols .= ", PosterPath";
if ($hasTrailerURL) $selectCols .= ", TrailerURL";
$selectCols .= ", COALESCE(`section`,'more_movies') AS `section`";

$dbMoviesResult = $conn->query("SELECT $selectCols FROM Movie ORDER BY ReleaseDate DESC, Title ASC");
if ($dbMoviesResult) {
  while ($row = $dbMoviesResult->fetch_assoc()) {
    $trailerRaw = trim($row['TrailerURL'] ?? '');
    $embed = '';
    if ($trailerRaw) {
      if (preg_match('/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|shorts\/))([A-Za-z0-9_\-]{11})/', $trailerRaw, $vm)) {
        $embed = 'https://www.youtube.com/embed/' . $vm[1] . '?autoplay=1';
      } elseif (strpos($trailerRaw, 'youtube.com/embed/') !== false) {
        $embed = strpos($trailerRaw, 'autoplay') !== false ? $trailerRaw : $trailerRaw . '?autoplay=1';
      } else {
        $embed = $trailerRaw;
      }
    }
    $row['TrailerEmbed'] = $embed;
    $dbMovies[] = $row;
  }
}

// Split DB movies by their assigned section
$dbNowShowing  = array_filter($dbMovies, fn($m) => ($m['section'] ?? 'more_movies') === 'now_showing');
$dbComingSoon  = array_filter($dbMovies, fn($m) => ($m['section'] ?? 'more_movies') === 'coming_soon');
$dbMoreMovies  = array_filter($dbMovies, fn($m) => !in_array($m['section'] ?? '', ['now_showing', 'coming_soon', 'hidden']));

// User inbox: refund & discount notifications for the logged-in user
$userInboxItems = [];
$userInboxUnread = 0;
if (!empty($_SESSION['user_id'])) {
  // Check if bookings table exists
  $bookingsTableCheck = $conn->query("SHOW TABLES LIKE 'bookings'");
  $hasBookingsTable = $bookingsTableCheck && $bookingsTableCheck->num_rows > 0;
  
  if ($hasBookingsTable) {
    $uid = (int)$_SESSION['user_id'];
    // Refund notifications: any booking with a refund decision
    $inboxRefundResult = $conn->query("SELECT booking_id, item_name, total_amount, refund_status, status, cancelled_date, created_at
      FROM bookings WHERE customer_id = $uid AND refund_status IN ('Approved','Rejected','Pending','Refund Requested')
      ORDER BY created_at DESC LIMIT 50");
  if ($inboxRefundResult) {
    while ($row = $inboxRefundResult->fetch_assoc()) {
      $row['_type'] = 'refund';
      $userInboxItems[] = $row;
      if (in_array($row['refund_status'], ['Approved','Rejected'])) $userInboxUnread++;
    }
  }
  // Discount notifications: bookings with a discount decision
  $inboxDiscountResult = $conn->query("SELECT booking_id, item_name, total_amount, discount_type, discounted_total, discount_status, created_at
    FROM bookings WHERE customer_id = $uid AND discount_type IS NOT NULL
    ORDER BY created_at DESC LIMIT 50");
  if ($inboxDiscountResult) {
    while ($row = $inboxDiscountResult->fetch_assoc()) {
      $row['_type'] = 'discount';
      $userInboxItems[] = $row;
      if (in_array($row['discount_status'] ?? '', ['Approved','Rejected'])) $userInboxUnread++;
    }
  }
  } // Close bookings table check
}

$cineflixNavMerged = [
  'navProfilePic' => $navProfilePic,
  'navUsername' => $navUsername,
  'userInboxItems' => $userInboxItems,
  'userInboxUnread' => $userInboxUnread,
];

// Helper to render a DB movie poster card
function renderDbMovieCard(array $m): string {
  $trailer = htmlspecialchars($m['TrailerEmbed'] ?? '');
  $title   = htmlspecialchars($m['Title']);
  $genre   = htmlspecialchars($m['Genre'] ?? '');
  $descRaw = strip_tags((string)($m['Description'] ?? ''));
  $descRaw = preg_replace('/\s+/', ' ', trim($descRaw));
  $descShort = htmlspecialchars(substr($descRaw, 0, 110));
  $poster  = htmlspecialchars($m['PosterPath'] ?? '');
  $genreLine = $genre !== '' ? "<span class=\"poster-genre\">$genre</span>" : '';
  $descLine  = $descShort !== '' ? "<span class=\"poster-desc\">$descShort</span>" : '';
  $img = $poster
    ? "<img src=\"$poster\" alt=\"$title\" onerror=\"this.style.display='none'\">"
    : '<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:3rem;background:rgba(255,255,255,0.04);">🎬</div>';
  return <<<HTML
      <div class="poster-card" data-trailer="$trailer" data-movie="$title" data-poster="$poster">
        $img
        <div class="overlay">
          <div class="overlay-content">
            <span class="poster-title">$title</span>
            $genreLine
            $descLine
            <button class="poster-btn trailer-btn">Trailer</button>
            <button class="poster-btn get-tickets-btn">Get Tickets</button>
          </div>
        </div>
      </div>
HTML;
}
?>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="theme-color" content="#1a1a2e">
  <link rel="manifest" href="manifest.json">
  <link rel="apple-touch-icon" href="icon/google-icon.png">
  <title>CineFlix</title>
  <link rel="stylesheet" href="common.css">
  <link rel="stylesheet" href="homepage.css">
  <link rel="stylesheet" href="css/header-nav.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <style>
    /* Reset some potential overflows */
    * { box-sizing: border-box; }
    html, body { overflow-x: hidden; width: 100%; position: relative; }
    
    @keyframes searchPulse {
      0%   { box-shadow: 0 0 0 0 rgba(199,159,94,0.6); }
      50%  { box-shadow: 0 0 0 12px rgba(199,159,94,0); }
      100% { box-shadow: 0 0 0 0 rgba(199,159,94,0); }
    }
    .search-highlight {
      animation: searchPulse 0.7s ease 2;
      outline: 2px solid rgba(199,159,94,0.6);
      border-radius: 12px;
    }

    /* ── User Inbox ── */
    .inbox-wrap { position:relative; display:inline-flex; align-items:center; z-index: 2001; }
    .inbox-bell {
      background: transparent; border: none; padding: 0;
      width: 36px; height: 36px; border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      font-size: 1rem; cursor: pointer; color: #f6f6f6; position: relative;
      transition: background 0.2s;
      -webkit-tap-highlight-color: transparent;
      z-index: 2001;
      pointer-events: auto;
    }
    .inbox-bell:hover { background: rgba(255,255,255,0.08); }
    .inbox-badge {
      position: absolute; top: -2px; right: -2px;
      background: #c79f5e; color: #fff; border-radius: 999px;
      font-size: 0.6rem; font-weight: 700; min-width: 15px; height: 15px;
      display: flex; align-items: center; justify-content: center; padding: 0 3px;
      pointer-events: none;
    }
    
    /* Mobile inbox improvements */
    @media (max-width: 767px) {
      .inbox-bell {
        width: 32px; height: 32px;
        font-size: 0.9rem;
      }
      .inbox-badge {
        font-size: 0.55rem;
        min-width: 13px; height: 13px;
        top: -1px; right: -1px;
      }
    }
    
    @media (max-width: 480px) {
      .inbox-bell {
        width: 28px; height: 28px;
        font-size: 0.8rem;
      }
      .inbox-badge {
        font-size: 0.5rem;
        min-width: 12px; height: 12px;
      }
    }
    .inbox-panel {
      display: none; position: absolute; right: 0; top: calc(100% + 10px);
      width: min(360px, 90vw); max-height: 500px;
      background: #13141a; border: 1px solid rgba(255,255,255,0.1);
      border-radius: 14px; box-shadow: 0 16px 36px rgba(0,0,0,0.55); z-index: 9999;
      flex-direction: column; overflow: hidden;
    }
    .inbox-panel.open { display: flex !important; }

    /* Header */
    .inbox-ph {
      padding: 13px 16px; border-bottom: 1px solid rgba(255,255,255,0.07);
      display: flex; justify-content: space-between; align-items: center;
      flex-shrink: 0;
    }
    .inbox-ph h4 { margin: 0; font-size: 0.88rem; color: #f6f6f6; }
    .inbox-ph-actions { display: flex; align-items: center; gap: 8px; }

    /* Filter tabs */
    .inbox-tabs {
      display: flex; gap: 0; border-bottom: 1px solid rgba(255,255,255,0.07);
      flex-shrink: 0;
    }
    .inbox-tab {
      flex: 1; background: none; border: none; padding: 9px 4px;
      font-size: 0.72rem; font-weight: 600; color: rgba(255,255,255,0.4);
      cursor: pointer; border-bottom: 2px solid transparent;
      transition: color 0.15s, border-color 0.15s; letter-spacing: .3px;
    }
    .inbox-tab:hover { color: rgba(255,255,255,0.7); }
    .inbox-tab.active { color: #c79f5e; border-bottom-color: #c79f5e; }

    /* Scrollable list */
    .inbox-list { overflow-y: auto; flex: 1; }

    /* Items */
    .inbox-item {
      padding: 11px 16px; border-bottom: 1px solid rgba(255,255,255,0.05);
      display: flex; flex-direction: column; gap: 4px;
      cursor: pointer; transition: background 0.15s; position: relative;
    }
    .inbox-item:last-child { border-bottom: none; }
    .inbox-item:hover { background: rgba(255,255,255,0.03); }
    .inbox-item.unread { background: rgba(199,159,94,0.05); }
    .inbox-item.unread::before {
      content: ''; position: absolute; left: 6px; top: 50%; transform: translateY(-50%);
      width: 5px; height: 5px; border-radius: 50%; background: #c79f5e;
    }
    .inbox-item-row { display: flex; justify-content: space-between; align-items: flex-start; gap: 8px; }
    .inbox-item-title { font-size: 0.84rem; font-weight: 600; color: #f6f6f6; }
    .inbox-item-time { font-size: 0.68rem; color: rgba(255,255,255,0.3); white-space: nowrap; flex-shrink: 0; margin-top: 2px; }
    .inbox-item-sub { font-size: 0.75rem; color: rgba(255,255,255,0.48); }
    .inbox-item-footer { display: flex; align-items: center; justify-content: space-between; margin-top: 2px; }
    .inbox-pill { display: inline-block; padding: 2px 7px; border-radius: 999px; font-size: 0.68rem; font-weight: 700; }
    .pill-approved { background: rgba(76,222,173,0.14); color: #4cdead; }
    .pill-rejected { background: rgba(255,107,107,0.14); color: #ff6b6b; }
    .pill-pending { background: rgba(255,255,255,0.08); color: rgba(255,255,255,0.6); }
    
    /* Smart alerts actions */
    .notification-action {
      background: #c79f5e; color: #000; border: none; padding: 4px 10px;
      border-radius: 4px; font-size: 0.7rem; font-weight: 700; cursor: pointer;
      margin-top: 4px; transition: background 0.2s;
    }
    .notification-action:hover { background: #e0b46d; }
    .inbox-dismiss {
      background: none; border: none; color: rgba(255,255,255,0.3);
      font-size: 0.8rem; cursor: pointer; padding: 4px;
    }
    .inbox-dismiss:hover { color: #ff6b6b; }
    
    .inbox-clear-btn {
      background: none; border: none; color: rgba(255,255,255,0.4);
      font-size: 0.7rem; cursor: pointer; padding: 4px 8px;
      border-radius: 4px; transition: all 0.15s;
    }
    .inbox-clear-btn:hover { background: rgba(199,159,94,0.12); color: #c79f5e; }
    .inbox-cleared-msg { padding: 28px 16px; text-align: center; color: rgba(255,255,255,0.3); font-size: 0.82rem; display: none; }

    /* ── Enhanced Search Bar ── */
    .search-wrapper {
      position: relative; display: flex; align-items: center;
    }
    .search-icon {
      position: absolute; left: 14px; color: rgba(255,255,255,0.4);
      font-size: 0.85rem; pointer-events: none; z-index: 1;
    }
    #movieSearch {
      padding: 9px 16px 9px 36px !important;
      border-radius: 22px !important;
      border: 1px solid rgba(255,255,255,0.15) !important;
      background: rgba(255,255,255,0.04) !important;
      color: #f6f6f6 !important;
      font-size: 0.85rem !important;
      width: 220px !important;
      transition: border-color 0.2s, box-shadow 0.2s, width 0.3s, background 0.2s !important;
      backdrop-filter: blur(8px);
      -webkit-tap-highlight-color: transparent;
    }
    #movieSearch:focus {
      border-color: #c79f5e !important;
      box-shadow: 0 0 0 3px rgba(199,159,94,0.15) !important;
      background: rgba(255,255,255,0.1) !important;
      width: 290px !important;
    }
    #movieSearch::placeholder { color: rgba(255,255,255,0.35); }
    
    /* Mobile search improvements */
    @media (max-width: 767px) {
      #movieSearch {
        width: 160px !important;
        font-size: 14px !important;
        padding: 8px 12px 8px 32px !important;
      }
      #movieSearch:focus {
        width: 200px !important;
      }
      .search-icon {
        left: 12px;
        font-size: 0.8rem;
      }
    }
    
    @media (max-width: 480px) {
      #movieSearch {
        width: 140px !important;
        font-size: 13px !important;
        padding: 7px 10px 7px 28px !important;
      }
      #movieSearch:focus {
        width: 180px !important;
      }
    }
    #searchResults {
      position: absolute; top: calc(100% + 8px); left: 0;
      width: 340px; max-height: 420px; overflow-y: auto;
      background: rgba(13,14,20,0.97);
      border: 1px solid rgba(255,255,255,0.1);
      border-radius: 14px; box-shadow: 0 16px 40px rgba(0,0,0,0.55);
      border-radius: 14px;
      box-shadow: 0 16px 40px rgba(0,0,0,0.55);
      z-index: 2000; display: none;
      backdrop-filter: blur(12px);
    }
    #searchResults::-webkit-scrollbar { width: 4px; }
    #searchResults::-webkit-scrollbar-track { background: transparent; }
    #searchResults::-webkit-scrollbar-thumb { background: rgba(199,159,94,0.3); border-radius: 4px; }

    /* ── Enhanced Movie Category Buttons ── */
    .movie-categories {
      display: flex; gap: 10px; justify-content: center; align-items: center;
      padding: 18px 0 10px; flex-wrap: wrap;
      width: 100%; text-align: center;
    }
    .movie-categories button {
      padding: 9px 24px; border-radius: 999px;
      border: 1px solid rgba(255,255,255,0.15);
      background: rgba(255,255,255,0.06);
      color: rgba(255,255,255,0.8); font-size: 0.85rem; font-weight: 600;
      cursor: pointer; transition: all 0.2s; letter-spacing: 0.3px;
      backdrop-filter: blur(6px);
      -webkit-tap-highlight-color: transparent;
    }
    .movie-categories button:hover {
      background: #c79f5e; border-color: #c79f5e; color: #111;
      box-shadow: 0 4px 16px rgba(199,159,94,0.35);
      transform: translateY(-1px);
    }
    .movie-categories button.active {
      background: #c79f5e; border-color: #c79f5e; color: #111;
    }
    
    /* Mobile category buttons */
    @media (max-width: 767px) {
      .movie-categories {
        gap: 8px;
        padding: 15px 10px 8px;
      }
      .movie-categories button {
        padding: 8px 18px;
        font-size: 0.8rem;
        min-height: 40px;
      }
    }
    
    @media (max-width: 480px) {
      .movie-categories {
        gap: 6px;
        padding: 12px 8px 6px;
      }
      .movie-categories button {
        padding: 7px 14px;
        font-size: 0.75rem;
        min-height: 36px;
      }
    }

    /* ── Enhanced Section Titles ── */
    .section-title {
      position: absolute;
      top: 20px;
      left: 50%;
      transform: translateX(-50%);
      font-size: 25px;
      font-weight: 800;
      color: #c79f5e;
      width: 100%;
      text-align: center;
      letter-spacing: 0.05em;
      text-transform: uppercase;
      white-space: nowrap;
    }
    .section-title::before {
      content: none;
    }

    /* ── Enhanced Poster Cards ── */
    .poster-card {
      transition: transform 0.25s cubic-bezier(0.34,1.56,0.64,1), box-shadow 0.25s ease !important;
    }
    .poster-card:hover {
      transform: translateY(-6px) scale(1.03) !important;
      box-shadow: 0 18px 40px rgba(0,0,0,0.6), 0 0 0 1px rgba(199,159,94,0.2) !important;
      z-index: 2;
    }

    /* ── Search highlight pulse ── */
    @keyframes searchPulse {
      0%   { box-shadow: 0 0 0 0 rgba(199,159,94,0.7); }
      50%  { box-shadow: 0 0 0 14px rgba(199,159,94,0); }
      100% { box-shadow: 0 0 0 0 rgba(199,159,94,0); }
    }
    .search-highlight {
      animation: searchPulse 0.65s ease 2 !important;
      outline: 2px solid rgba(199,159,94,0.5) !important;
      border-radius: 12px !important;
    }

    /* === Notification System === */
    .seat-notification {
      position: fixed;
      top: 20px;
      right: 20px;
      background: rgba(40, 40, 40, 0.98);
      color: #fff;
      padding: 16px 24px;
      border-radius: 12px;
      border-left: 5px solid #c79f5e;
      font-family: 'Poppins', sans-serif;
      font-size: 1rem;
      font-weight: 600;
      line-height: 1.4;
      z-index: 10000;
      opacity: 0;
      transform: translateX(100%);
      transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
      max-width: 400px;
      min-width: 300px;
      word-wrap: break-word;
      backdrop-filter: blur(10px);
    }

    .seat-notification.show {
      opacity: 1;
      transform: translateX(0);
    }

    .seat-notification.success {
      background: linear-gradient(135deg, rgba(76, 175, 80, 0.98), rgba(56, 142, 60, 0.98));
      border-left-color: #4CAF50;
      box-shadow: 0 8px 32px rgba(76, 175, 80, 0.4);
    }

    .seat-notification.info {
      background: linear-gradient(135deg, rgba(33, 150, 243, 0.98), rgba(25, 118, 210, 0.98));
      border-left-color: #2196F3;
      box-shadow: 0 8px 32px rgba(33, 150, 243, 0.4);
    }

    .seat-notification.warning {
      background: linear-gradient(135deg, rgba(255, 152, 0, 0.98), rgba(230, 81, 0, 0.98));
      border-left-color: #FF9800;
      box-shadow: 0 8px 32px rgba(255, 152, 0, 0.4);
    }

    .seat-notification.error {
      background: linear-gradient(135deg, rgba(244, 67, 54, 0.98), rgba(211, 47, 47, 0.98));
      border-left-color: #F44336;
      box-shadow: 0 8px 32px rgba(244, 67, 54, 0.4);
    }

    .notification-inbox {
      position: fixed;
      top: 80px;
      right: 20px;
      width: 380px;
      max-height: 500px;
      background: rgba(20, 20, 20, 0.98);
      border: 1px solid rgba(199, 159, 94, 0.3);
      border-radius: 16px;
      backdrop-filter: blur(20px);
      box-shadow: 0 16px 48px rgba(0, 0, 0, 0.6);
      z-index: 9999;
      opacity: 0;
      transform: translateX(120%) scale(0.9);
      transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
      overflow: hidden;
    }

    .notification-inbox.show {
      opacity: 1;
      transform: translateX(0) scale(1);
    }

    .notification-inbox-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 20px 24px;
      background: rgba(199, 159, 94, 0.15);
      border-bottom: 1px solid rgba(199, 159, 94, 0.3);
      color: #fff;
      font-family: 'Poppins', sans-serif;
    }

    .notification-inbox-title {
      font-size: 1.2rem;
      font-weight: 700;
      margin: 0;
    }

    .notification-inbox-close {
      background: none;
      border: none;
      color: rgba(255, 255, 255, 0.7);
      font-size: 1.5rem;
      cursor: pointer;
      padding: 4px 8px;
      border-radius: 4px;
      transition: all 0.2s ease;
    }

    .notification-inbox-close:hover {
      background: rgba(255, 255, 255, 0.1);
      color: #fff;
    }

    .notification-inbox-content {
      max-height: 380px;
      overflow-y: auto;
      padding: 12px;
    }

    .notification-inbox-empty {
      text-align: center;
      padding: 40px 20px;
      color: rgba(255, 255, 255, 0.6);
      font-family: 'Poppins', sans-serif;
      font-size: 1rem;
    }

    .notification-item {
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 12px;
      padding: 16px 20px;
      margin-bottom: 12px;
      color: #fff;
      font-family: 'Poppins', sans-serif;
      font-size: 0.95rem;
      line-height: 1.4;
      transition: all 0.2s ease;
      cursor: pointer;
      position: relative;
      padding-right: 40px; /* Extra space for close button */
    }

    .notification-item:hover {
      background: rgba(255, 255, 255, 0.08);
      transform: translateY(-1px);
    }

    .notification-item.success {
      border-left: 4px solid #4CAF50;
    }

    .notification-item.info {
      border-left: 4px solid #2196F3;
    }

    .notification-item.warning {
      border-left: 4px solid #FF9800;
    }

    .notification-item.error {
      border-left: 4px solid #F44336;
    }

    .notification-item-message {
      margin-bottom: 8px;
      padding-right: 20px; /* Space to avoid overlap with close button */
    }

    .notification-item-time {
      font-size: 0.8rem;
      color: rgba(255, 255, 255, 0.5);
      position: absolute;
      bottom: 12px;
      right: 16px;
    }

    .notification-item-close {
      position: absolute;
      top: 8px;
      right: 8px;
      background: none;
      border: none;
      color: rgba(255, 255, 255, 0.4);
      font-size: 1.2rem;
      cursor: pointer;
      padding: 4px;
      border-radius: 4px;
      transition: all 0.2s ease;
      width: 24px;
      height: 24px;
      display: flex;
      align-items: center;
      justify-content: center;
      opacity: 0;
      transform: scale(0.8);
    }

    .notification-item:hover .notification-item-close {
      opacity: 1;
      transform: scale(1);
    }

    .notification-item-close:hover {
      background: rgba(255, 255, 255, 0.1);
      color: rgba(255, 255, 255, 0.8);
    }

    .notification-inbox-footer {
      padding: 16px 24px;
      background: rgba(199, 159, 94, 0.1);
      border-top: 1px solid rgba(199, 159, 94, 0.3);
      text-align: center;
    }

    .notification-clear-btn {
      background: linear-gradient(135deg, #c79f5e, #a67c42);
      color: #fff;
      border: none;
      padding: 10px 24px;
      border-radius: 8px;
      font-family: 'Poppins', sans-serif;
      font-size: 0.9rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
    }

    .notification-clear-btn:hover {
      background: linear-gradient(135deg, #d4ae6f, #b88b52);
      transform: translateY(-1px);
      box-shadow: 0 4px 16px rgba(199, 159, 94, 0.4);
    }

    .notification-clear-btn:disabled {
      opacity: 0.5;
      cursor: not-allowed;
      transform: none;
    }
  </style>
  <script>
    var isAdminSession = <?php echo !empty($_SESSION['is_admin']) ? 'true' : 'false'; ?>;
    var isStaffSession = <?php echo !empty($_SESSION['is_staff']) ? 'true' : 'false'; ?>;
  </script>
  <style>
    /* ── Fixed site header ── */
    .site-header {
      position: fixed !important;
      top: 0 !important; left: 0 !important; right: 0 !important;
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
    .site-header .logo img, .logo img {
      height: 260px !important;
      width: auto !important;
    }
    .site-header .top-nav ul, .site-header ul {
      display: flex !important;
      flex-direction: row !important;
      align-items: center !important;
      gap: 14px !important;
      list-style: none !important;
      margin: 0 !important;
      padding: 0 !important;
    }
    .site-header .top-nav li, .site-header li { list-style: none !important; }
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
  </style>
  <?php require __DIR__ . '/includes/partials/site_header_scripts.php'; ?>
</head>

<body class="has-background">
  <?php
  $headerShowSearch = true;
  require __DIR__ . '/includes/partials/site_header.php';
  ?>

  <main>
    <!-- === Spotlight Section === -->
    <section class="spotlight">
      <div class="spotlight-slide active" style="background-image: url('spolight/chainsawmanV2.jpg');">
        <div class="spotlight-content">
          <h4>#1 Spotlight</h4>
          <h1>Chainsaw Man</h1>
          <p>
           Denji is literally love bombed by the Bomb Devil Reze, a Soviet human-devil hybrid contracted by the Gun Devil to steal Denji's heart
          </p>
          <div class="spotlight-buttons">
            <button class="watch-btn" data-trailer="https://www.youtube.com/embed/GJ1jrCnm-t8?autoplay=1">▶ Watch Trailer</button>
          </div>
        </div>
      </div>

      <div class="spotlight-slide" style="background-image: url('spolight/demonslayerV2.jpg');">
        <div class="spotlight-content">
          <h4>#2 Spotlight</h4>
          <h1>Demon Slayer : Infinity Castle</h1>
          <p>
            The Demon Slayer Corps are drawn into the Infinity Castle, where Tanjiro and the Hashira face 
            terrifying Upper Rank demons in a desperate fight as the final battle against Muzan Kibutsuji begins.
          </p>
          <div class="spotlight-buttons">
            <button class="watch-btn" data-trailer="https://www.youtube.com/embed/x7uLutVRBfI?autoplay=1">▶ Watch Trailer</button>
          </div>
        </div>
      </div>

      <div class="spotlight-slide"  style="background-image: url('spolight/black phone 2.jpg');">
        <div class="spotlight-content">
          <h4>#3 Spotlight</h4>
          <h1>Black Phone 2</h1>
          <p>
            As Finn, now 17, struggles with life after his captivity, his sister begins receiving calls in her 
            dreams from the black phone and seeing disturbing visions of three boys being stalked at a winter camp known as Alpine Lake.
          </p>
          <div class="spotlight-buttons">
            <button class="watch-btn" data-trailer="https://www.youtube.com/embed/DdR-gzFZoDk?autoplay=1">▶ Watch Trailer</button>
          </div>
        </div>
      </div>

      <!-- Navigation Arrows -->
      <button class="spotlight-btn prev">&#10094;</button>
      <button class="spotlight-btn next">&#10095;</button>
    </section>

    <!-- Movie Category Navigation -->
    <div style="width:100%;display:flex;justify-content:center;">
    <div class="movie-categories">
      <button onclick="scrollToSection('now-showing')">Now Showing</button>
      <button onclick="scrollToSection('coming-soon')">Coming Soon</button>
      <button onclick="window.location.href='events.php'">Events</button>
    </div>
    </div>

    <div class="movie-section" id="now-showing">
      <h2 class="section-title">Now Showing</h2>
      <div class="poster-card" data-trailer="https://www.youtube.com/embed/GJ1jrCnm-t8?autoplay=1" data-movie="Chainsaw Man" data-poster="movies posters/chainsawman.jpg">
        <img src="movies posters/chainsawman.jpg" alt="Chainsaw Man">
        <div class="overlay">
          <div class="overlay-content">
            <span class="poster-title">Chainsaw Man</span>
            <button class="poster-btn trailer-btn">Trailer</button>
            <button class="poster-btn get-tickets-btn">Get Tickets</button>
          </div>
        </div>
      </div>

      <div class="poster-card" data-trailer="https://www.youtube.com/embed/x7uLutVRBfI?autoplay=1" data-movie="Demon Slayer" data-poster="movies posters/demonslayer.jpg">
        <img src="movies posters/demonslayer.jpg" alt="Demon Slayer">
        <div class="overlay">
          <div class="overlay-content">
            <span class="poster-title">Demon Slayer</span>
            <button class="poster-btn trailer-btn">Trailer</button>
            <button class="poster-btn get-tickets-btn">Get Tickets</button>
          </div>
        </div>
      </div>

      <div class="poster-card" data-trailer="https://www.youtube.com/embed/wPosLpgMtTY?autoplay=1" data-movie="Spider-Man 3" data-poster="movies posters/Spider-Man 3 (2007).jpg">
        <img src="movies posters/Spider-Man 3 (2007).jpg" alt="Spider-Man 3">
        <div class="overlay">
          <div class="overlay-content">
            <span class="poster-title">Spider-Man 3</span>
            <button class="poster-btn trailer-btn">Trailer</button>
            <button class="poster-btn get-tickets-btn">Get Tickets</button>
          </div>
        </div>
      </div>

      <div class="poster-card" data-trailer="https://www.youtube.com/embed/DdR-gzFZoDk?autoplay=1" data-movie="The Black Phone 2" data-poster="movies posters/The Black Phone 2.jpg">
        <img src="movies posters/The Black Phone 2.jpg" alt="The Black Phone 2">
        <div class="overlay">
          <div class="overlay-content">
            <span class="poster-title">The Black Phone 2</span>
            <button class="poster-btn trailer-btn">Trailer</button>
            <button class="poster-btn get-tickets-btn">Get Tickets</button>
          </div>
        </div>
      </div>

      <div class="poster-card" data-trailer="https://www.youtube.com/embed/-sAOWhvheK8?autoplay=1" data-movie="Thunderbolts" data-poster="movies posters/Thunderbolts.jpg">
        <img src="movies posters/Thunderbolts.jpg" alt="Thunderbolts">
        <div class="overlay">
          <div class="overlay-content">
            <span class="poster-title">Thunder bolts</span>
            <button class="poster-btn trailer-btn">Trailer</button>
            <button class="poster-btn get-tickets-btn">Get Tickets</button>
          </div>
        </div>
      </div>

      <div class="poster-card" data-trailer="https://www.youtube.com/embed/P9s-FBvB2y4?autoplay=1" data-movie="A Minecraft Movie" data-poster="movies posters/A Minecraft Movie.jpg">
        <img src="movies posters/A Minecraft Movie.jpg" alt="A Minecraft Movie">
        <div class="overlay">
          <div class="overlay-content">
            <span class="poster-title">A Minecraft Movie</span>
            <button class="poster-btn trailer-btn">Trailer</button>
            <button class="poster-btn get-tickets-btn">Get Tickets</button>
          </div>
        </div>
      </div>

      <div class="poster-card" data-trailer="https://www.youtube.com/embed/Mb9CCnM-B4Q?autoplay=1" data-movie="IT Chapter 2" data-poster="movies posters/IT chapter 2.jpg">
        <img src="movies posters/IT chapter 2.jpg" alt="IT chapter 2">
        <div class="overlay">
          <div class="overlay-content">
            <span class="poster-title">IT Chapter 2</span>
            <button class="poster-btn trailer-btn">Trailer</button>
            <button class="poster-btn get-tickets-btn">Get Tickets</button>
          </div>
        </div>
      </div>

      <div class="poster-card" data-trailer="https://www.youtube.com/embed/Q5yWqmYU0Hs?autoplay=1" data-movie="The Long Walk" data-poster="movies posters/The Long Walk.jpg">
        <img src="movies posters/The Long Walk.jpg" alt="The Long Walk">
        <div class="overlay">
          <div class="overlay-content">
            <span class="poster-title">The Long Walk</span>
            <button class="poster-btn trailer-btn">Trailer</button>
            <button class="poster-btn get-tickets-btn">Get Tickets</button>
          </div>
        </div>
      </div>

      <div class="poster-card" data-trailer="https://www.youtube.com/embed/YShVEXb7-ic?autoplay=1" data-movie="Tron Ares" data-poster="movies posters/Tron Ares.jpg">
        <img src="movies posters/Tron Ares.jpg" alt="Tron Ares">
        <div class="overlay">
          <div class="overlay-content">
            <span class="poster-title">Tron Ares</span>
            <button class="poster-btn trailer-btn">Trailer</button>
            <button class="poster-btn get-tickets-btn">Get Tickets</button>
          </div>
        </div>
      </div>

      <div class="poster-card" data-trailer="https://www.youtube.com/embed/1pHDWnXmK7Y?autoplay=1" data-movie="Brave New World" data-poster="movies posters/Captain America.jpg">
        <img src="movies posters/Captain America.jpg" alt="Brave New World">
        <div class="overlay">
          <div class="overlay-content">
            <span class="poster-title">Brave New World</span>
            <button class="poster-btn trailer-btn">Trailer</button>
            <button class="poster-btn get-tickets-btn">Get Tickets</button>
          </div>
        </div>
      </div>
      <?php foreach ($dbNowShowing as $m): echo renderDbMovieCard($m); endforeach; ?>
    </div>

    <div class="movie-section" id="coming-soon">
      <h2 class="section-title">Coming Soon</h2>
      
      <div class="poster-card" data-trailer="https://www.youtube.com/embed/48CtX6OgU3s?autoplay=1" data-movie="The Housemaid" data-poster="coming soon movies/the housemaid 2025.jpg">
        <img src="coming soon movies/the housemaid 2025.jpg" alt="The Housemaid">
        <div class="overlay">
          <div class="overlay-content">
            <span class="poster-title">The Housemaid</span>
            <button class="poster-btn trailer-btn">Trailer</button>
            <button class="poster-btn get-tickets-btn">Get Tickets</button>
          </div>
        </div>
      </div>

      <div class="poster-card" data-trailer="https://www.youtube.com/embed/BjkIOU5PhyQ?autoplay=1" data-movie="Zootopia 2" data-poster="coming soon movies/Zootopia 2.jpg">
        <img src="coming soon movies/Zootopia 2.jpg" alt="Zootopia 2">
        <div class="overlay">
          <div class="overlay-content">
            <span class="poster-title">Zootopia 2</span>
            <button class="poster-btn trailer-btn">Trailer</button>
            <button class="poster-btn get-tickets-btn">Get Tickets</button>
          </div>
        </div>
      </div>

      <div class="poster-card" data-trailer="https://www.youtube.com/embed/Ma1x7ikpid8?autoplay=1" data-movie="Avatar : Fire & Ash" data-poster="coming soon movies/avatar fire & ash.jpg">
        <img src="coming soon movies/avatar fire & ash.jpg" alt="Avatar : Fire & Ash">
        <div class="overlay">
          <div class="overlay-content">
            <span class="poster-title">Avatar : Fire & Ash</span>
            <button class="poster-btn trailer-btn">Trailer</button>
            <button class="poster-btn get-tickets-btn">Get Tickets</button>
          </div>
        </div>
      </div>

      <div class="poster-card" data-trailer="https://www.youtube.com/embed/uogrgIasYcA?autoplay=1" data-movie="Now You See Me : Now You Don`t" data-poster="coming soon movies/now you see me.jpg">
        <img src="coming soon movies/now you see me.jpg" alt="Now You See Me : Now You Don`t">
        <div class="overlay">
          <div class="overlay-content">
            <span class="poster-title">Now You See Me : Now You Don`t</span>
            <button class="poster-btn trailer-btn">Trailer</button>
            <button class="poster-btn get-tickets-btn">Get Tickets</button>
          </div>
        </div>
      </div>

      <div class="poster-card" data-trailer="https://www.youtube.com/embed/dSDpoobO6yM?autoplay=1" data-movie="Five Nights at Freddy's 2" data-poster="coming soon movies/FNAF2.jpg">
        <img src="coming soon movies/FNAF2.jpg" alt="Five Nights at Freddy's 2 ">
        <div class="overlay">
          <div class="overlay-content">
            <span class="poster-title">Five Nights at Freddy's 2 </span>
            <button class="poster-btn trailer-btn">Trailer</button>
            <button class="poster-btn get-tickets-btn">Get Tickets</button>
          </div>
        </div>
      </div>

      <div class="poster-card" data-trailer="https://www.youtube.com/embed/w7t2gyIwvDo?autoplay=1" data-movie="Search for Square Pants" data-poster="coming soon movies/spongebob.jpg">
        <img src="coming soon movies/spongebob.jpg" alt="Search for Square Pants">
        <div class="overlay">
          <div class="overlay-content">
            <span class="poster-title">Search for Square Pants</span>
            <button class="poster-btn trailer-btn">Trailer</button>
            <button class="poster-btn get-tickets-btn">Get Tickets</button>
          </div>
        </div>
      </div>
      <?php foreach ($dbComingSoon as $m): echo renderDbMovieCard($m); endforeach; ?>
    </div>

    <?php if (!empty($dbMoreMovies)): ?>
    <div class="movie-section" id="admin-movies">
      <h2 class="section-title">More Movies</h2>
      <?php foreach ($dbMoreMovies as $m): echo renderDbMovieCard($m); endforeach; ?>
    </div>
    <?php endif; ?>

  <?php if (!empty($_SESSION['user_id'])): ?>
  <!-- Profile modal removed; use dedicated profile page instead -->
  <?php endif; ?>

  <!-- Trailer Modal -->
  <div class="video-modal" id="videoModal">
    <div class="video-container">
      <iframe id="trailerFrame" src="" frameborder="0" allowfullscreen></iframe>
      <span class="close">&times;</span>
    </div>
  </div>

  <script>
    function scrollToSection(sectionId) {
      const section = document.getElementById(sectionId);
      section.scrollIntoView({ behavior: 'smooth' });
    }

    // Movie search functionality
    (function() {
      const searchInput   = document.getElementById('movieSearch');
      const searchResults = document.getElementById('searchResults');

      if (!searchInput || !searchResults) return;

      // Elements to hide while searching
      const movieCategories = document.querySelector('.movie-categories');
      const movieSections   = document.querySelectorAll('.movie-section');
      const sectionTitles   = document.querySelectorAll('.section-title');

      // Collect all movie cards once
      const movieCards = document.querySelectorAll('.poster-card');
      const movies = Array.from(movieCards).map(card => ({
        element: card,
        title:   card.getAttribute('data-movie') || card.querySelector('.poster-title')?.textContent || '',
        poster:  card.getAttribute('data-poster') || '',
        section: card.closest('.movie-section')
      }));

      // Add CSS transitions for smooth fade
      movieSections.forEach(s => {
        s.style.transition = 'opacity 0.25s ease';
      });
      movieCards.forEach(c => {
        c.style.transition = 'opacity 0.2s ease, transform 0.2s ease';
      });

      function highlightText(text, query) {
        if (!query) return text;
        const regex = new RegExp(`(${query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
        return text.replace(regex, '<mark style="background:#c79f5e;color:#111;padding:1px 3px;border-radius:3px;">$1</mark>');
      }

      let searchActive = false;

      function enterSearchMode() {
        if (searchActive) return;
        searchActive = true;
        // Hide category nav and section titles smoothly
        if (movieCategories) { movieCategories.style.transition='opacity 0.2s'; movieCategories.style.opacity='0'; setTimeout(()=>{ movieCategories.style.display='none'; },200); }
        sectionTitles.forEach(t => { t.style.transition='opacity 0.2s'; t.style.opacity='0'; setTimeout(()=>{ t.style.display='none'; },200); });
      }

      function exitSearchMode() {
        if (!searchActive) return;
        searchActive = false;
        // Restore all cards
        movies.forEach(m => {
          m.element.style.opacity = '1';
          m.element.style.display = '';
          m.element.style.transform = '';
        });
        // Restore section titles and category nav
        sectionTitles.forEach(t => { t.style.display=''; requestAnimationFrame(()=>{ t.style.opacity='1'; }); });
        if (movieCategories) { movieCategories.style.display=''; requestAnimationFrame(()=>{ movieCategories.style.opacity='1'; }); }
        // Hide dropdown
        searchResults.style.opacity = '0';
        setTimeout(() => { searchResults.style.display = 'none'; searchResults.style.opacity = ''; }, 150);
      }

      function applyFilter(query) {
        const q = query.trim().toLowerCase();
        movies.forEach(m => {
          const matches = !q || m.title.toLowerCase().includes(q);
          if (matches) {
            m.element.style.opacity = '1';
            m.element.style.display = '';
            m.element.style.transform = 'scale(1)';
          } else {
            m.element.style.opacity = '0';
            m.element.style.transform = 'scale(0.97)';
            setTimeout(() => { if (!m.title.toLowerCase().includes(searchInput.value.trim().toLowerCase())) m.element.style.display = 'none'; }, 200);
          }
        });
      }

      let debounceTimer;
      searchInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        const query = this.value.trim();

        if (!query) {
          exitSearchMode();
          return;
        }

        enterSearchMode();

        debounceTimer = setTimeout(() => {
          applyFilter(query);

          const filtered = movies.filter(m => m.title.toLowerCase().includes(query.toLowerCase()));

          if (!filtered.length) {
            searchResults.innerHTML = '<div style="padding:20px;text-align:center;color:rgba(255,255,255,0.55);font-size:0.88rem;">No movies found for "<strong style=\'color:#c79f5e\'>' + query.replace(/</g,'&lt;') + '</strong>"</div>';
          } else {
            searchResults.innerHTML = filtered.map((m, i) => {
              const hl = highlightText(m.title, query);
              const imgTag = m.poster
                ? `<img src="${m.poster.replace(/"/g,'&quot;')}" style="width:44px;height:62px;object-fit:cover;border-radius:5px;flex-shrink:0;" onerror="this.style.display='none'">`
                : '<div style="width:44px;height:62px;border-radius:5px;background:rgba(255,255,255,0.06);flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:1.2rem;">🎬</div>';
              // Store original index into movies array via data attribute
              const origIdx = movies.indexOf(m);
              return `<div class="sr-item" data-movie-idx="${origIdx}" style="padding:10px 14px;cursor:pointer;border-bottom:1px solid rgba(255,255,255,0.05);display:flex;align-items:center;gap:12px;transition:background 0.15s;">
                ${imgTag}
                <span style="color:rgba(255,255,255,0.9);font-size:0.88rem;">${hl}</span>
              </div>`;
            }).join('');

            // Use event delegation — no inline onclick
            searchResults.querySelectorAll('.sr-item').forEach(item => {
              item.addEventListener('mouseenter', () => item.style.background = 'rgba(199,159,94,0.1)');
              item.addEventListener('mouseleave', () => item.style.background = 'transparent');
              item.addEventListener('click', (e) => {
                e.stopPropagation();
                const idx = parseInt(item.getAttribute('data-movie-idx'), 10);
                const m = movies[idx];
                if (!m) return;
                searchInput.value = '';
                exitSearchMode();
                searchResults.style.display = 'none';
                setTimeout(() => {
                  m.element.scrollIntoView({ behavior: 'smooth', block: 'center' });
                  setTimeout(() => {
                    m.element.classList.add('search-highlight');
                    setTimeout(() => m.element.classList.remove('search-highlight'), 1600);
                  }, 350);
                }, 50);
              });
            });
          }

          searchResults.style.display = 'block';
          searchResults.style.opacity = '0';
          requestAnimationFrame(() => { searchResults.style.transition='opacity 0.15s'; searchResults.style.opacity='1'; });
        }, 120);
      });

      // Close dropdown on outside click but keep filter active
      document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
          searchResults.style.display = 'none';
        }
      });

      // Clear search on Escape
      searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') { this.value = ''; exitSearchMode(); this.blur(); }
      });

      // Expose exitSearchMode globally for inline onclick use
      window.exitSearchMode = exitSearchMode;
    })();

    // Chatbot assistant logic — AI-powered via Anthropic API
    (function() {
      const toggle   = document.getElementById('cf-chat-toggle');
      const panel    = document.getElementById('cf-chat-panel');
      const closeBtn = document.getElementById('cf-chat-close');
      const messages = document.getElementById('cf-chat-messages');
      const inputEl  = document.getElementById('cf-chat-input');
      const sendBtn  = document.getElementById('cf-chat-send');
      const quickButtons = document.querySelectorAll('.cf-quick-suggest');

      if (!toggle || !panel || !messages) return;

      // Collect all movie titles from the page for context
      function getPageMovies() {
        return Array.from(document.querySelectorAll('.poster-card'))
          .map(c => c.getAttribute('data-movie') || c.querySelector('.poster-title')?.textContent || '')
          .filter(Boolean);
      }

      let conversationHistory = [];

      function appendMessage(html, fromAssistant = true) {
        const wrapper = document.createElement('div');
        wrapper.style.cssText = `margin-bottom:10px;text-align:${fromAssistant ? 'left' : 'right'};`;
        const bubble = document.createElement('div');
        bubble.innerHTML = html;
        bubble.style.cssText = `
          padding:10px 13px;border-radius:${fromAssistant ? '12px 12px 12px 4px' : '12px 12px 4px 12px'};
          display:inline-block;max-width:90%;
          background:${fromAssistant ? 'rgba(255,255,255,0.07)' : 'rgba(199,159,94,0.22)'};
          color:#f9fafb;font-size:0.88rem;line-height:1.5;text-align:left;
        `;
        wrapper.appendChild(bubble);
        messages.appendChild(wrapper);
        messages.scrollTop = messages.scrollHeight;
        return bubble;
      }

      function setLoading(bubble, on) {
        if (on) bubble.innerHTML = '<span style="opacity:0.5">CineFlix is thinking…</span>';
      }

      async function sendMessage(text) {
        if (!text.trim()) return;
        appendMessage(escapeHtml(text), false);

        const movieList = getPageMovies().join(', ');
        conversationHistory.push({ role: 'user', content: text });

        const loadingBubble = appendMessage('', true);
        setLoading(loadingBubble, true);

        try {
          const res = await fetch('https://api.anthropic.com/v1/messages', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              model: 'claude-sonnet-4-20250514',
              max_tokens: 1000,
              system: `You are CineBot, a friendly cinema assistant for CineFlix Theatre. 
Keep replies concise (2-4 sentences). Help users find movies, answer questions about booking, showtimes, food, parking, and discounts (PWD/Senior = 20% off tickets).
Current movies on the platform: ${movieList || 'various titles'}.
Ticket price is ₱425 each. For bookings say: "Click Get Tickets on any movie poster or visit booking.php".
Don't make up showtimes — say times are shown during booking. Be warm, helpful, and cinema-themed.`,
              messages: conversationHistory
            })
          });

          const data = await res.json();
          const reply = data.content?.find(b => b.type === 'text')?.text || "Sorry, I couldn't get a response. Please try again!";
          conversationHistory.push({ role: 'assistant', content: reply });
          loadingBubble.innerHTML = escapeHtml(reply).replace(/\n/g, '<br>');
        } catch (err) {
          loadingBubble.innerHTML = '<span style="color:rgba(255,100,100,0.9)">Connection error. Please try again.</span>';
        }

        messages.scrollTop = messages.scrollHeight;
      }

      function escapeHtml(t) {
        return t.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
      }

      toggle.addEventListener('click', () => {
        const isOpen = panel.style.display === 'flex';
        panel.style.display = isOpen ? 'none' : 'flex';
        if (!isOpen && messages.children.length === 0) {
          appendMessage('👋 Hey! I\'m <strong>CineBot</strong>, your CineFlix assistant.<br>Ask me about movies, bookings, discounts, or anything cinema! 🎬', true);
        }
      });

      closeBtn?.addEventListener('click', () => { panel.style.display = 'none'; });

      sendBtn?.addEventListener('click', () => {
        const text = inputEl?.value?.trim();
        if (text) { inputEl.value = ''; sendMessage(text); }
      });

      inputEl?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendBtn?.click(); }
      });

      quickButtons.forEach(btn => {
        btn.addEventListener('click', () => {
          const genre = btn.getAttribute('data-genre') || 'movie';
          const text = `What ${genre} movies do you have?`;
          sendMessage(text);
        });
      });
    })();
  </script>
  <script src="script.js"></script>

  <!-- ── Ctrl+B Quick Book Modal ──────────────────────────────────── -->
  <style>
    .qb-backdrop {
      position: fixed; inset: 0; z-index: 9500;
      background: rgba(0,0,0,0.75); backdrop-filter: blur(6px);
      display: flex; align-items: center; justify-content: center;
      opacity: 0; visibility: hidden; pointer-events: none;
      transition: opacity 0.18s, visibility 0.18s;
    }
    .qb-backdrop.open { opacity: 1; visibility: visible; pointer-events: auto; }
    .qb-box {
      background: #13141a; border: 1px solid rgba(255,255,255,0.12);
      border-radius: 18px; width: min(460px, 94vw);
      box-shadow: 0 28px 64px rgba(0,0,0,0.7);
      transform: scale(0.95); transition: transform 0.18s;
      overflow: hidden;
    }
    .qb-backdrop.open .qb-box { transform: scale(1); }
    .qb-header {
      padding: 18px 20px 14px;
      border-bottom: 1px solid rgba(255,255,255,0.07);
      display: flex; align-items: center; justify-content: space-between;
    }
    .qb-header h3 { margin: 0; font-size: 1rem; color: #f6f6f6; }
    .qb-hint { font-size: 0.72rem; color: rgba(255,255,255,0.3); margin-top: 2px; }
    .qb-close {
      background: none; border: none; color: rgba(255,255,255,0.4);
      font-size: 1.1rem; cursor: pointer; padding: 0; line-height: 1;
    }
    .qb-close:hover { color: #fff; }
    .qb-search-wrap { padding: 14px 20px; border-bottom: 1px solid rgba(255,255,255,0.06); }
    .qb-search {
      width: 100%; box-sizing: border-box;
      padding: 10px 14px; border-radius: 10px;
      border: 1px solid rgba(255,255,255,0.14);
      background: rgba(255,255,255,0.05); color: #fff;
      font-size: 0.92rem; outline: none;
      transition: border-color 0.15s;
    }
    .qb-search:focus { border-color: #c79f5e; }
    .qb-search::placeholder { color: rgba(255,255,255,0.3); }
    .qb-list { max-height: 320px; overflow-y: auto; }
    .qb-item {
      display: flex; align-items: center; gap: 12px;
      padding: 10px 20px; cursor: pointer;
      transition: background 0.13s; border-bottom: 1px solid rgba(255,255,255,0.04);
    }
    .qb-item:last-child { border-bottom: none; }
    .qb-item:hover, .qb-item.active { background: rgba(199,159,94,0.12); }
    .qb-item img {
      width: 36px; height: 50px; object-fit: cover;
      border-radius: 5px; flex-shrink: 0;
      background: rgba(255,255,255,0.06);
    }
    .qb-item-info { display: flex; flex-direction: column; gap: 2px; }
    .qb-item-title { font-size: 0.88rem; font-weight: 600; color: #f6f6f6; }
    .qb-item-sub { font-size: 0.72rem; color: rgba(255,255,255,0.4); }
    .qb-empty { padding: 28px 20px; text-align: center; color: rgba(255,255,255,0.3); font-size: 0.85rem; }
    .qb-footer { padding: 10px 20px; border-top: 1px solid rgba(255,255,255,0.06); display: flex; justify-content: flex-end; }
    .qb-book-btn {
      padding: 9px 22px; border-radius: 999px; border: none;
      background: #c79f5e; color: #111; font-weight: 700; font-size: 0.88rem;
      cursor: pointer; transition: opacity 0.15s;
    }
    .qb-book-btn:disabled { opacity: 0.35; cursor: not-allowed; }
    .qb-book-btn:not(:disabled):hover { opacity: 0.85; }
    .qb-kbd {
      display: inline-block; padding: 2px 6px; border-radius: 5px;
      background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15);
      font-size: 0.7rem; color: rgba(255,255,255,0.5); font-family: monospace;
    }
  </style>

  <div class="qb-backdrop" id="qbBackdrop" role="dialog" aria-modal="true" aria-label="Quick Book">
    <div class="qb-box">
      <div class="qb-header">
        <div>
          <h3>🎟 Quick Book</h3>
          <div class="qb-hint">Type a movie name · <span class="qb-kbd">↑↓</span> navigate · <span class="qb-kbd">Enter</span> book · <span class="qb-kbd">Esc</span> close</div>
        </div>
        <button class="qb-close" id="qbClose">✕</button>
      </div>
      <div class="qb-search-wrap">
        <input type="text" class="qb-search" id="qbSearch" placeholder="Search movies to book…" autocomplete="off">
      </div>
      <div class="qb-list" id="qbList"></div>
      <div class="qb-footer">
        <button class="qb-book-btn" id="qbBookBtn" disabled>Get Tickets →</button>
      </div>
    </div>
  </div>

  <script>
  (function () {
    var backdrop  = document.getElementById('qbBackdrop');
    var searchEl  = document.getElementById('qbSearch');
    var listEl    = document.getElementById('qbList');
    var bookBtn   = document.getElementById('qbBookBtn');
    var closeBtn  = document.getElementById('qbClose');
    var selected  = null; // { title, poster }
    var activeIdx = -1;

    // ── Collect all movies from poster cards on the page ──────────────
    function getMovies() {
      var seen = new Set();
      var movies = [];
      document.querySelectorAll('.poster-card[data-movie]').forEach(function(card) {
        var title  = card.getAttribute('data-movie') || '';
        var poster = card.getAttribute('data-poster') || '';
        if (title && !seen.has(title.toLowerCase())) {
          seen.add(title.toLowerCase());
          movies.push({ title: title, poster: poster });
        }
      });
      return movies;
    }

    // ── Render filtered list ─────────────────────────────────────────
    function renderList(query) {
      var movies = getMovies();
      var q = query.trim().toLowerCase();
      var filtered = q ? movies.filter(function(m) { return m.title.toLowerCase().includes(q); }) : movies;
      activeIdx = -1;
      selected  = null;
      bookBtn.disabled = true;

      if (!filtered.length) {
        listEl.innerHTML = '<div class="qb-empty">No movies found for "' + escHtml(query) + '"</div>';
        return;
      }

      listEl.innerHTML = filtered.map(function(m, i) {
        var safeTitle  = escHtml(m.title);
        var safePoster = escHtml(m.poster);
        var imgTag = m.poster
          ? '<img src="' + safePoster + '" alt="" onerror="this.style.opacity=0.2">'
          : '<img src="" alt="" style="opacity:0.1">';
        return '<div class="qb-item" data-idx="' + i + '" data-title="' + safeTitle + '" data-poster="' + safePoster + '">' +
                 imgTag +
                 '<div class="qb-item-info">' +
                   '<span class="qb-item-title">' + safeTitle + '</span>' +
                   '<span class="qb-item-sub">Click to select</span>' +
                 '</div>' +
               '</div>';
      }).join('');

      // Click to select
      listEl.querySelectorAll('.qb-item').forEach(function(item) {
        item.addEventListener('click', function() {
          selectItem(this);
        });
      });
    }

    function selectItem(el) {
      listEl.querySelectorAll('.qb-item').forEach(function(i) { i.classList.remove('active'); });
      el.classList.add('active');
      selected = { title: el.getAttribute('data-title'), poster: el.getAttribute('data-poster') };
      activeIdx = parseInt(el.getAttribute('data-idx'), 10);
      bookBtn.disabled = false;
    }

    function navigate(dir) {
      var items = listEl.querySelectorAll('.qb-item');
      if (!items.length) return;
      activeIdx = Math.max(0, Math.min(items.length - 1, activeIdx + dir));
      selectItem(items[activeIdx]);
      items[activeIdx].scrollIntoView({ block: 'nearest' });
    }

    function doBook() {
      if (!selected) return;
      var url = 'booking.php?movie=' + encodeURIComponent(selected.title);
      if (selected.poster) url += '&poster=' + encodeURIComponent(selected.poster);
      window.location.href = url;
    }

    function open() {
      backdrop.classList.add('open');
      document.body.style.overflow = 'hidden';
      searchEl.value = '';
      renderList('');
      setTimeout(function() { searchEl.focus(); }, 60);
    }

    function close() {
      backdrop.classList.remove('open');
      document.body.style.overflow = '';
    }

    function escHtml(s) {
      return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    // ── Event listeners ───────────────────────────────────────────────
    searchEl.addEventListener('input', function() { renderList(this.value); });

    searchEl.addEventListener('keydown', function(e) {
      if (e.key === 'ArrowDown') { e.preventDefault(); navigate(1); }
      else if (e.key === 'ArrowUp') { e.preventDefault(); navigate(-1); }
      else if (e.key === 'Enter') { e.preventDefault(); if (selected) doBook(); }
      else if (e.key === 'Escape') { close(); }
    });

    bookBtn.addEventListener('click', doBook);
    closeBtn.addEventListener('click', close);
    backdrop.addEventListener('click', function(e) { if (e.target === backdrop) close(); });

    // ── Ctrl + B shortcut ─────────────────────────────────────────────
    document.addEventListener('keydown', function(e) {
      // Don't fire if user is typing in a regular input/textarea
      var tag = document.activeElement ? document.activeElement.tagName : '';
      var isInput = (tag === 'INPUT' || tag === 'TEXTAREA' || document.activeElement.isContentEditable);
      if (e.ctrlKey && e.key === 'b' && !e.shiftKey && !e.altKey) {
        if (isInput && document.activeElement.id !== 'qbSearch') return;
        e.preventDefault();
        if (backdrop.classList.contains('open')) { close(); } else { open(); }
      }
    });
  })();
  </script>
  <!-- ── Ctrl+A Admin Access Modal ───────────────────────────────── -->
  <style>
    .admin-backdrop {
      position: fixed; inset: 0; z-index: 9600;
      background: rgba(0,0,0,0.82); backdrop-filter: blur(8px);
      display: flex; align-items: center; justify-content: center;
      opacity: 0; visibility: hidden; pointer-events: none;
      transition: opacity 0.18s, visibility 0.18s;
    }
    .admin-backdrop.open { opacity: 1; visibility: visible; pointer-events: auto; }
    .admin-box {
      background: #13141a; border: 1px solid rgba(255,255,255,0.12);
      border-radius: 18px; width: min(380px, 94vw);
      box-shadow: 0 28px 64px rgba(0,0,0,0.75);
      transform: scale(0.95); transition: transform 0.18s;
      overflow: hidden;
    }
    .admin-backdrop.open .admin-box { transform: scale(1); }
    .admin-box-header {
      padding: 20px 22px 16px;
      border-bottom: 1px solid rgba(255,255,255,0.07);
      display: flex; align-items: flex-start; justify-content: space-between;
    }
    .admin-box-header h3 { margin: 0; font-size: 1rem; color: #f6f6f6; }
    .admin-box-header p { margin: 4px 0 0; font-size: 0.75rem; color: rgba(255,255,255,0.35); }
    .admin-close-btn {
      background: none; border: none; color: rgba(255,255,255,0.4);
      font-size: 1.1rem; cursor: pointer; padding: 0; line-height: 1; flex-shrink: 0;
    }
    .admin-close-btn:hover { color: #fff; }
    .admin-box-body { padding: 20px 22px; display: flex; flex-direction: column; gap: 12px; }
    .admin-pass-wrap { position: relative; }
    .admin-pass-input {
      width: 100%; box-sizing: border-box;
      padding: 11px 44px 11px 14px; border-radius: 10px;
      border: 1px solid rgba(255,255,255,0.14);
      background: rgba(255,255,255,0.05); color: #fff;
      font-size: 0.92rem; outline: none;
      transition: border-color 0.15s;
    }
    .admin-pass-input:focus { border-color: #c79f5e; }
    .admin-pass-input.shake {
      animation: adminShake 0.38s ease;
      border-color: #f97373;
    }
    @keyframes adminShake {
      0%,100% { transform: translateX(0); }
      20%      { transform: translateX(-7px); }
      40%      { transform: translateX(7px); }
      60%      { transform: translateX(-5px); }
      80%      { transform: translateX(5px); }
    }
    .admin-pass-toggle {
      position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
      background: none; border: none; cursor: pointer;
      color: rgba(255,255,255,0.35); font-size: 1rem; padding: 0; line-height: 1;
    }
    .admin-pass-toggle:hover { color: rgba(255,255,255,0.7); }
    .admin-error {
      font-size: 0.78rem; color: #f97373;
      display: none; margin-top: -4px;
    }
    .admin-submit-btn {
      width: 100%; padding: 11px; border-radius: 999px; border: none;
      background: #c79f5e; color: #111; font-weight: 700; font-size: 0.9rem;
      cursor: pointer; transition: opacity 0.15s;
    }
    .admin-submit-btn:hover { opacity: 0.85; }
    .admin-submit-btn:disabled { opacity: 0.4; cursor: not-allowed; }
  </style>

  <div class="admin-backdrop" id="adminBackdrop" role="dialog" aria-modal="true" aria-label="Admin Access">
    <div class="admin-box">
      <div class="admin-box-header">
        <div>
          <h3>🔐 Admin Access</h3>
          <p>Enter your admin password to continue</p>
        </div>
        <button class="admin-close-btn" id="adminModalClose">✕</button>
      </div>
      <div class="admin-box-body">
        <div class="admin-pass-wrap">
          <input type="password" class="admin-pass-input" id="adminPassInput"
                 placeholder="Admin password" autocomplete="current-password">
          <button class="admin-pass-toggle" id="adminPassToggle" tabindex="-1" title="Show/hide password">👁</button>
        </div>
        <div class="admin-error" id="adminError">Incorrect password. Please try again.</div>
        <button class="admin-submit-btn" id="adminSubmitBtn">Go to Admin Dashboard →</button>
      </div>
    </div>
  </div>

  <script>
  (function () {
    var backdrop   = document.getElementById('adminBackdrop');
    var passInput  = document.getElementById('adminPassInput');
    var submitBtn  = document.getElementById('adminSubmitBtn');
    var closeBtn   = document.getElementById('adminModalClose');
    var toggleBtn  = document.getElementById('adminPassToggle');
    var errorEl    = document.getElementById('adminError');

    function openModal() {
      backdrop.classList.add('open');
      document.body.style.overflow = 'hidden';
      passInput.value = '';
      errorEl.style.display = 'none';
      passInput.classList.remove('shake');
      setTimeout(function() { passInput.focus(); }, 60);
    }

    function closeModal() {
      backdrop.classList.remove('open');
      document.body.style.overflow = '';
      passInput.value = '';
      errorEl.style.display = 'none';
      passInput.classList.remove('shake');
      submitBtn.disabled = false;
    }

    async function doCheck() {
      var pass = passInput.value.trim();
      if (!pass) { passInput.focus(); return; }

      submitBtn.disabled = true;
      submitBtn.textContent = 'Checking…';
      errorEl.style.display = 'none';
      passInput.classList.remove('shake');

      try {
        var res  = await fetch('homepage.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ action: 'admin_login', username: 'admin', password: pass })
        });
        var data = await res.json();
        if (data.success) {
          submitBtn.textContent = '✓ Redirecting…';
          window.location.href = 'admin-dashboard.php';
        } else {
          errorEl.style.display = 'block';
          passInput.value = '';
          passInput.classList.remove('shake');
          // Force reflow so animation re-triggers
          void passInput.offsetWidth;
          passInput.classList.add('shake');
          passInput.focus();
          submitBtn.disabled = false;
          submitBtn.textContent = 'Go to Admin Dashboard →';
        }
      } catch (err) {
        errorEl.textContent = 'Connection error. Please try again.';
        errorEl.style.display = 'block';
        submitBtn.disabled = false;
        submitBtn.textContent = 'Go to Admin Dashboard →';
      }
    }

    // Show/hide password
    toggleBtn.addEventListener('click', function() {
      var isPass = passInput.type === 'password';
      passInput.type = isPass ? 'text' : 'password';
      toggleBtn.textContent = isPass ? '🙈' : '👁';
    });

    submitBtn.addEventListener('click', doCheck);
    closeBtn.addEventListener('click', closeModal);
    backdrop.addEventListener('click', function(e) { if (e.target === backdrop) closeModal(); });

    passInput.addEventListener('keydown', function(e) {
      if (e.key === 'Enter') { e.preventDefault(); doCheck(); }
      if (e.key === 'Escape') { closeModal(); }
    });

    // ── Ctrl+Shift+A shortcut ─────────────────────────────────────────
    document.addEventListener('keydown', function(e) {
      var tag = document.activeElement ? document.activeElement.tagName : '';
      var isTyping = (tag === 'INPUT' || tag === 'TEXTAREA' || (document.activeElement && document.activeElement.isContentEditable));
      if (isTyping && document.activeElement.id !== 'adminPassInput') return;
      if (e.ctrlKey && e.shiftKey && (e.key === 'A' || e.key === 'a') && !e.altKey) {
        e.preventDefault();
        // If already logged in as admin, go straight to dashboard — no password needed
        if (typeof isAdminSession !== 'undefined' && isAdminSession) {
          window.location.href = 'admin-dashboard.php';
          return;
        }
        // Close staff modal if open
        var staffBd = document.getElementById('staffBackdrop');
        if (staffBd && staffBd.classList.contains('open')) {
          staffBd.classList.remove('open');
          document.body.style.overflow = '';
        }
        if (backdrop.classList.contains('open')) { closeModal(); }
        else { openModal(); }
      }
    });
  })();
  </script>  <!-- ── Ctrl+Shift+F Staff Access Modal ─────────────────────────── -->
  <style>
    .staff-backdrop {
      position: fixed; inset: 0; z-index: 9600;
      background: rgba(0,0,0,0.82); backdrop-filter: blur(8px);
      display: flex; align-items: center; justify-content: center;
      opacity: 0; visibility: hidden; pointer-events: none;
      transition: opacity 0.18s, visibility 0.18s;
    }
    .staff-backdrop.open { opacity: 1; visibility: visible; pointer-events: auto; }
    .staff-box {
      background: #13141a; border: 1px solid rgba(255,255,255,0.12);
      border-radius: 18px; width: min(380px, 94vw);
      box-shadow: 0 28px 64px rgba(0,0,0,0.75);
      transform: scale(0.95); transition: transform 0.18s;
      overflow: hidden;
    }
    .staff-backdrop.open .staff-box { transform: scale(1); }
    .staff-box-header {
      padding: 20px 22px 16px;
      border-bottom: 1px solid rgba(255,255,255,0.07);
      display: flex; align-items: flex-start; justify-content: space-between;
    }
    .staff-box-header h3 { margin: 0; font-size: 1rem; color: #f6f6f6; }
    .staff-box-header p  { margin: 4px 0 0; font-size: 0.75rem; color: rgba(255,255,255,0.35); }
    .staff-close-btn {
      background: none; border: none; color: rgba(255,255,255,0.4);
      font-size: 1.1rem; cursor: pointer; padding: 0; line-height: 1; flex-shrink: 0;
    }
    .staff-close-btn:hover { color: #fff; }
    .staff-box-body { padding: 20px 22px; display: flex; flex-direction: column; gap: 12px; }
    .staff-pass-wrap { position: relative; }
    .staff-pass-input {
      width: 100%; box-sizing: border-box;
      padding: 11px 44px 11px 14px; border-radius: 10px;
      border: 1px solid rgba(255,255,255,0.14);
      background: rgba(255,255,255,0.05); color: #fff;
      font-size: 0.92rem; outline: none; transition: border-color 0.15s;
    }
    .staff-pass-input:focus { border-color: #6ea8fe; }
    .staff-pass-input.shake {
      animation: adminShake 0.38s ease;
      border-color: #f97373;
    }
    .staff-pass-toggle {
      position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
      background: none; border: none; cursor: pointer;
      color: rgba(255,255,255,0.35); font-size: 1rem; padding: 0; line-height: 1;
    }
    .staff-pass-toggle:hover { color: rgba(255,255,255,0.7); }
    .staff-error { font-size: 0.78rem; color: #f97373; display: none; margin-top: -4px; }
    .staff-submit-btn {
      width: 100%; padding: 11px; border-radius: 999px; border: none;
      background: #5b9cf6; color: #fff; font-weight: 700; font-size: 0.9rem;
      cursor: pointer; transition: opacity 0.15s;
    }
    .staff-submit-btn:hover { opacity: 0.85; }
    .staff-submit-btn:disabled { opacity: 0.4; cursor: not-allowed; }
  </style>

  <div class="staff-backdrop" id="staffBackdrop" role="dialog" aria-modal="true" aria-label="Staff Access">
    <div class="staff-box">
      <div class="staff-box-header">
        <div>
          <h3>🧑‍💼 Staff Access</h3>
          <p>Enter your staff password to continue</p>
        </div>
        <button class="staff-close-btn" id="staffModalClose">✕</button>
      </div>
      <div class="staff-box-body">
        <div class="staff-pass-wrap">
          <input type="password" class="staff-pass-input" id="staffPassInput"
                 placeholder="Staff password" autocomplete="current-password">
          <button class="staff-pass-toggle" id="staffPassToggle" tabindex="-1" title="Show/hide password">👁</button>
        </div>
        <div class="staff-error" id="staffError">Incorrect password. Please try again.</div>
        <button class="staff-submit-btn" id="staffSubmitBtn">Go to Staff Dashboard →</button>
      </div>
    </div>
  </div>

  <script>
  (function () {
    var backdrop  = document.getElementById('staffBackdrop');
    var passInput = document.getElementById('staffPassInput');
    var submitBtn = document.getElementById('staffSubmitBtn');
    var closeBtn  = document.getElementById('staffModalClose');
    var toggleBtn = document.getElementById('staffPassToggle');
    var errorEl   = document.getElementById('staffError');

    function openModal() {
      // Close admin modal if open
      var adminBd = document.getElementById('adminBackdrop');
      if (adminBd && adminBd.classList.contains('open')) {
        adminBd.classList.remove('open');
      }
      backdrop.classList.add('open');
      document.body.style.overflow = 'hidden';
      passInput.value = '';
      errorEl.style.display = 'none';
      passInput.classList.remove('shake');
      submitBtn.disabled = false;
      submitBtn.textContent = 'Go to Staff Dashboard →';
      setTimeout(function() { passInput.focus(); }, 60);
    }

    function closeModal() {
      backdrop.classList.remove('open');
      document.body.style.overflow = '';
      passInput.value = '';
      errorEl.style.display = 'none';
      passInput.classList.remove('shake');
      submitBtn.disabled = false;
      submitBtn.textContent = 'Go to Staff Dashboard →';
    }

    async function doCheck() {
      var pass = passInput.value.trim();
      if (!pass) { passInput.focus(); return; }

      submitBtn.disabled = true;
      submitBtn.textContent = 'Checking…';
      errorEl.style.display = 'none';
      passInput.classList.remove('shake');

      try {
        var res  = await fetch('homepage.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ action: 'staff_login', username: 'staff', password: pass })
        });
        var data = await res.json();
        if (data.success) {
          submitBtn.textContent = '✓ Redirecting…';
          window.location.href = 'staff-walkin.php';
        } else {
          errorEl.style.display = 'block';
          passInput.value = '';
          passInput.classList.remove('shake');
          void passInput.offsetWidth;
          passInput.classList.add('shake');
          passInput.focus();
          submitBtn.disabled = false;
          submitBtn.textContent = 'Go to Staff Dashboard →';
        }
      } catch (err) {
        errorEl.textContent = 'Connection error. Please try again.';
        errorEl.style.display = 'block';
        submitBtn.disabled = false;
        submitBtn.textContent = 'Go to Staff Dashboard →';
      }
    }

    toggleBtn.addEventListener('click', function() {
      var isPass = passInput.type === 'password';
      passInput.type = isPass ? 'text' : 'password';
      toggleBtn.textContent = isPass ? '🙈' : '👁';
    });

    submitBtn.addEventListener('click', doCheck);
    closeBtn.addEventListener('click', closeModal);
    backdrop.addEventListener('click', function(e) { if (e.target === backdrop) closeModal(); });

    passInput.addEventListener('keydown', function(e) {
      if (e.key === 'Enter') { e.preventDefault(); doCheck(); }
      if (e.key === 'Escape') { closeModal(); }
    });

    // ── Ctrl+Shift+F shortcut ─────────────────────────────────────────
    document.addEventListener('keydown', function(e) {
      var tag = document.activeElement ? document.activeElement.tagName : '';
      var isTyping = (tag === 'INPUT' || tag === 'TEXTAREA' || (document.activeElement && document.activeElement.isContentEditable));
      if (isTyping && document.activeElement.id !== 'staffPassInput') return;
      if (e.ctrlKey && e.shiftKey && (e.key === 'F' || e.key === 'f') && !e.altKey) {
        e.preventDefault();
        // If already logged in as staff, go straight — no password needed
        if (typeof isStaffSession !== 'undefined' && isStaffSession) {
          window.location.href = 'staff-walkin.php';
          return;
        }
        if (backdrop.classList.contains('open')) { closeModal(); }
        else { openModal(); }
      }
    });
  })();
  </script>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
  <link rel="stylesheet" href="chatbot.css">
  <script src="chatbot.js"></script>
  
  <!-- Notification Inbox System -->
  <script>
  // Notification Inbox System for Homepage
  class NotificationInbox {
    constructor() {
      this.notifications = [];
      this.maxNotifications = 50;
      this.inboxElement = null;
      this.isOpen = false;
      this.init();
    }

    init() {
      this.loadNotifications();
      // We use the existing panel and bell from PHP
      // The bell listener is already handled in the main DOMContentLoaded block
    }

    loadNotifications() {
      try {
        const stored = localStorage.getItem('cineflix_notifications');
        if (stored) {
          this.notifications = JSON.parse(stored);
          if (this.notifications.length > this.maxNotifications) {
            this.notifications = this.notifications.slice(-this.maxNotifications);
          }
        }
      } catch (e) {
        console.error('Failed to load notifications:', e);
        this.notifications = [];
      }
    }

    saveNotifications() {
      try {
        localStorage.setItem('cineflix_notifications', JSON.stringify(this.notifications));
      } catch (e) {
        console.error('Failed to save notifications:', e);
      }
    }

    addNotification(message, type = 'info') {
      const notification = {
        id: Date.now() + Math.random(),
        message,
        type,
        timestamp: new Date().toISOString(),
        read: false
      };

      this.notifications.unshift(notification);
      
      if (this.notifications.length > this.maxNotifications) {
        this.notifications = this.notifications.slice(0, this.maxNotifications);
      }

      this.saveNotifications();
      this.updateInboxContent();
      this.updateNotificationBell();
      this.showToastNotification(message, type);
    }

    showToastNotification(message, type = 'info') {
      const notification = document.createElement('div');
      notification.className = `seat-notification ${type}`;
      notification.textContent = message;
      
      document.body.appendChild(notification);
      
      requestAnimationFrame(() => {
        notification.classList.add('show');
      });
      
      setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
      }, 3000);
    }

    createInboxUI() {
      this.inboxElement = document.createElement('div');
      this.inboxElement.className = 'notification-inbox';
      this.inboxElement.innerHTML = `
        <div class="notification-inbox-header">
          <h3 class="notification-inbox-title">Notifications</h3>
          <button class="notification-inbox-close">&times;</button>
        </div>
        <div class="notification-inbox-content">
          <div class="notification-inbox-empty">No notifications yet</div>
        </div>
        <div class="notification-inbox-footer">
          <button class="notification-clear-btn" disabled>Clear All</button>
        </div>
      `;

      document.body.appendChild(this.inboxElement);

      const closeBtn = this.inboxElement.querySelector('.notification-inbox-close');
      const clearBtn = this.inboxElement.querySelector('.notification-clear-btn');

      closeBtn.addEventListener('click', () => this.closeInbox());
      clearBtn.addEventListener('click', () => this.clearAllNotifications());

      document.addEventListener('click', (e) => {
        if (this.isOpen && !this.inboxElement.contains(e.target) && !e.target.closest('.notification-bell')) {
          this.closeInbox();
        }
      });

      this.updateInboxContent();
    }

    addNotificationBell() {
      const topNav = document.querySelector('.top-nav > ul');
      if (!topNav) return;

      const bellContainer = document.createElement('li');
      bellContainer.innerHTML = `
        <div class="notification-bell" title="Notifications">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
            <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
          </svg>
          <span class="notification-badge" style="display: none;">0</span>
        </div>
      `;

      const style = document.createElement('style');
      style.textContent = `
        .notification-bell {
          position: relative;
          cursor: pointer;
          padding: 0.7rem 1.2rem;
          background: rgba(255,255,255,0.08);
          color: #fff;
          border-radius: 30px;
          font-size: 1rem;
          font-weight: 600;
          transition: all 0.3s ease;
          display: flex;
          align-items: center;
          justify-content: center;
        }
        .notification-bell:hover {
          background: rgb(39, 39, 39);
          color: #c79f5e;
          transform: translateY(-5px);
          box-shadow: 0 4px 12px rgba(199, 159, 94, 0.5);
        }
        .notification-badge {
          position: absolute;
          top: -5px;
          right: -5px;
          background: #ff4444;
          color: white;
          border-radius: 50%;
          width: 18px;
          height: 18px;
          font-size: 0.7rem;
          font-weight: bold;
          display: flex;
          align-items: center;
          justify-content: center;
          border: 2px solid rgba(0,0,0,0.3);
        }
      `;
      document.head.appendChild(style);

      topNav.appendChild(bellContainer);

      const bell = bellContainer.querySelector('.notification-bell');
      bell.addEventListener('click', () => this.toggleInbox());

      this.updateNotificationBell();
    }

    toggleInbox() {
      if (this.isOpen) {
        this.closeInbox();
      } else {
        this.openInbox();
      }
    }

    openInbox() {
      if (!this.inboxElement) return;
      
      this.inboxElement.classList.add('open');
      this.isOpen = true;
      
      this.notifications.forEach(n => n.read = true);
      this.saveNotifications();
      this.updateNotificationBell();
      this.updateInboxContent();
    }

    closeInbox() {
      if (!this.inboxElement) return;
      
      this.inboxElement.classList.remove('open');
      this.isOpen = false;
    }

    updateInboxContent() {
      if (!this.inboxElement) return;

      const content = this.inboxElement.querySelector('.notification-inbox-content');
      const clearBtn = this.inboxElement.querySelector('.notification-clear-btn');

      if (this.notifications.length === 0) {
        content.innerHTML = '<div class="notification-inbox-empty">No notifications yet</div>';
        clearBtn.disabled = true;
      } else {
        const notificationsHTML = this.notifications.map(notification => {
          const time = this.formatTime(notification.timestamp);
          return `
            <div class="notification-item ${notification.type}" data-id="${notification.id}">
              <button class="notification-item-close" onclick="event.stopPropagation(); notificationInbox.removeNotification(${notification.id})" title="Close notification">×</button>
              <div class="notification-item-message">${notification.message}</div>
              <div class="notification-item-time">${time}</div>
            </div>
          `;
        }).join('');

        content.innerHTML = notificationsHTML;
        clearBtn.disabled = false;

        // Add click handlers for notification items (not including close button clicks)
        content.querySelectorAll('.notification-item').forEach(item => {
          item.addEventListener('click', (e) => {
            // Don't remove notification if close button was clicked
            if (!e.target.classList.contains('notification-item-close')) {
              const id = parseFloat(item.dataset.id);
              this.removeNotification(id);
            }
          });
        });
      }
    }

    updateNotificationBell() {
      const badge = document.querySelector('.notification-badge');
      if (!badge) return;

      const unreadCount = this.notifications.filter(n => !n.read).length;
      
      if (unreadCount > 0) {
        badge.textContent = unreadCount > 99 ? '99+' : unreadCount;
        badge.style.display = 'flex';
      } else {
        badge.style.display = 'none';
      }
    }

    removeNotification(id) {
      this.notifications = this.notifications.filter(n => n.id !== id);
      this.saveNotifications();
      this.updateInboxContent();
      this.updateNotificationBell();
    }

    clearAllNotifications() {
      this.notifications = [];
      this.saveNotifications();
      this.updateInboxContent();
      this.updateNotificationBell();
    }

    formatTime(timestamp) {
      const date = new Date(timestamp);
      const now = new Date();
      const diffMs = now - date;
      const diffMins = Math.floor(diffMs / 60000);
      const diffHours = Math.floor(diffMs / 3600000);
      const diffDays = Math.floor(diffMs / 86400000);

      if (diffMins < 1) return 'Just now';
      if (diffMins < 60) return `${diffMins}m ago`;
      if (diffHours < 24) return `${diffHours}h ago`;
      if (diffDays < 7) return `${diffDays}d ago`;
      
      return date.toLocaleDateString();
    }
  }

  // Initialize notification inbox
  const notificationInbox = new NotificationInbox();
  
  // Homepage-specific notification function (excludes seat selection notifications)
  function showHomepageNotification(message, type = 'info') {
    if (typeof notificationInbox !== 'undefined') {
      notificationInbox.addNotification(message, type);
    }
  }
  
  // Global function for general notifications (used by other pages)
  function showNotification(message, type = 'info') {
    // Only show non-seat-selection notifications on homepage
    const seatSelectionKeywords = ['seat selected', 'seat deselected', 'best seats selected'];
    const isSeatNotification = seatSelectionKeywords.some(keyword => 
      message.toLowerCase().includes(keyword)
    );
    
    if (!isSeatNotification) {
      showHomepageNotification(message, type);
    }
  }
  
  // Function to add movie notifications (can be called by admin)
  function addMovieNotification(movieTitle, action = 'added') {
    const messages = {
      'added': `🎬 New movie "${movieTitle}" is now showing!`,
      'removed': `📽️ Movie "${movieTitle}" is no longer available`,
      'updated': `🔄 Showtimes for "${movieTitle}" have been updated`
    };
    showHomepageNotification(messages[action] || `🎬 ${movieTitle}: ${action}`, 'info');
  }
  
  // Function to add booking notifications
  function addBookingNotification(bookingDetails, action = 'confirmed') {
    const messages = {
      'confirmed': `🎟️ Booking confirmed for "${bookingDetails.movie}"`,
      'cancelled': `❌ Booking cancelled for "${bookingDetails.movie}"`,
      'reminder': `⏰ Reminder: "${bookingDetails.movie}" starts in 2 hours`
    };
    showHomepageNotification(messages[action] || `🎟️ ${bookingDetails.movie}: ${action}`, 
      action === 'confirmed' ? 'success' : action === 'cancelled' ? 'error' : 'warning');
  }
  
  // Function to add user account notifications
  function addUserAccountNotification(action, details = {}) {
    const messages = {
      'password_changed': '🔐 Your password has been successfully updated',
      'username_changed': `👤 Your username has been changed to "${details.newUsername}"`,
      'email_changed': `📧 Your email address has been updated to "${details.newEmail}"`,
      'profile_updated': '🖼️ Your profile has been updated successfully',
      'profile_picture_updated': '🖼️ Profile picture updated successfully',
      'phone_changed': `📱 Your phone number has been updated`,
      'account_verified': '✅ Your account has been verified',
      'security_enabled': '🛡️ Two-factor authentication has been enabled',
      'security_disabled': '🔓 Two-factor authentication has been disabled',
      'login_detected': `🔔 New login detected from ${details.location || 'unknown device'}`,
      'password_reset': '🔄 Password reset request sent to your email',
      'account_created': '🎉 Welcome! Your CineFlix account has been created successfully'
    };
    
    const message = messages[action] || `👤 Account: ${action}`;
    const type = action.includes('failed') || action.includes('disabled') ? 'error' : 
                action.includes('warning') ? 'warning' : 'success';
    
    showHomepageNotification(message, type);
  }
  
  // Function to add system notifications
  function addSystemNotification(message, type = 'info') {
    showHomepageNotification(message, type);
  }
  
  // Example usage - these would be called by actual user actions:
  // 
  // When user completes a booking:
  // addBookingNotification({movie: 'A Minecraft Movie'}, 'confirmed');
  // 
  // When user changes password:
  // addUserAccountNotification('password_changed');
  // 
  // When user updates profile:
  // addUserAccountNotification('profile_picture_updated');
  // 
  // When admin adds new movie:
  // addMovieNotification('The Black Phone 2', 'added');
  // 
  // When system sends important updates:
  // addSystemNotification('Cinema maintenance complete - all screens operational', 'success');
  
  // Make functions globally available for admin/staff to call
  window.addMovieNotification = addMovieNotification;
  window.addBookingNotification = addBookingNotification;
  window.addUserAccountNotification = addUserAccountNotification;
  window.addSystemNotification = addSystemNotification;
  </script>
  <!-- Enhanced Features Integration -->
  <script src="cineflix_enhanced.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Initialize enhanced features if logged in
      if (typeof CineflixEnhanced !== 'undefined') {
        window.cineflix = new CineflixEnhanced();
      }
    });
  </script>
</body>
</html>