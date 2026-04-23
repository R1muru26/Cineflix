<?php
session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: login.html');
    exit();
}

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
$conn = db_get_connection();
cineflix_bootstrap_session_from_cookie($conn);
require_once __DIR__ . '/includes/cineflix_nav_helpers.php';

$userId   = (int)$_SESSION['user_id'];
$userName = $_SESSION['user_name']  ?? 'User';
$userEmail= $_SESSION['user_email'] ?? '';

// Fetch profile data from CustomerUser table
$profilePic = $_SESSION['profile_picture'] ?? '';
$username   = $_SESSION['username'] ?? $userName;
$memberSince = '';
$totalBookings = 0;

// Auto-detect table
$allTablesRes = $conn->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE()");
$allTables = [];
if ($allTablesRes) { while ($t = $allTablesRes->fetch_assoc()) $allTables[strtolower($t['TABLE_NAME'])] = $t['TABLE_NAME']; }
$userTable = '';
foreach (['customeruser','users','user','customer','customers','member','members'] as $c) {
    if (isset($allTables[$c])) { $userTable = $allTables[$c]; break; }
}

if ($userTable) {
    $colsRes = $conn->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='" . $conn->real_escape_string($userTable) . "'");
    $cols = [];
    if ($colsRes) { while ($c = $colsRes->fetch_assoc()) $cols[] = strtolower($c['COLUMN_NAME']); }
    $idCol = 'id';
    foreach (['customerid','id','user_id'] as $c) { if (in_array($c,$cols)) { $idCol=$c; break; } }
    $unCol = 'username';
    foreach (['username','user_name'] as $c) { if (in_array($c,$cols)) { $unCol=$c; break; } }
    $hasPp = in_array('profile_picture', $cols);
    $hasCreated = in_array('created_at', $cols);

    $selCols = '`' . $unCol . '`';
    if ($hasPp) $selCols .= ', `profile_picture`';
    if ($hasCreated) $selCols .= ', `created_at`';

    $row = $conn->query("SELECT $selCols FROM `$userTable` WHERE `$idCol` = $userId LIMIT 1");
    if ($row && ($r = $row->fetch_assoc())) {
        if (!empty($r[$unCol])) $username = $r[$unCol];
        if ($hasPp && !empty($r['profile_picture'])) { $profilePic = $r['profile_picture']; $_SESSION['profile_picture'] = $profilePic; }
        if ($hasCreated && !empty($r['created_at'])) $memberSince = date('F Y', strtotime($r['created_at']));
    }
}

// Get booking stats
$bookRes = $conn->query("SELECT COUNT(*) AS total FROM bookings WHERE customer_id = $userId");
if ($bookRes && ($br = $bookRes->fetch_assoc())) $totalBookings = (int)$br['total'];

$initial = strtoupper(substr($username, 0, 1));

$inboxNav = cineflix_nav_load_inbox($conn);
$cineflixNavMerged = [
    'navProfilePic' => $profilePic,
    'navUsername' => $username,
    'userInboxItems' => $inboxNav['userInboxItems'],
    'userInboxUnread' => $inboxNav['userInboxUnread'],
];
$userInboxItems = $cineflixNavMerged['userInboxItems'];
$userInboxUnread = (int)$cineflixNavMerged['userInboxUnread'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>My Profile | CineFlix</title>
  <link rel="stylesheet" href="common.css">
  <link rel="stylesheet" href="css/header-nav.css">
  <link rel="manifest" href="manifest.json">
  <link rel="apple-touch-icon" href="icon/google-icon.png">
  <link rel="stylesheet" href="homepage.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    /* ── Fixed site header — consistent with all pages ── */
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
    .site-header .top-nav ul,
    .site-header ul {
      display: flex !important;
      flex-direction: row !important;
      align-items: center !important;
      gap: 14px !important;
      list-style: none !important;
      margin: 0 !important;
      padding: 0 !important;
    }
    .site-header .top-nav li,
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

    body.has-background { overflow-y: auto; }
    main { padding: 40px 20px 80px; }

    .profile-wrapper {
      max-width: 980px;
      margin: 0 auto;
    }

    .profile-layout {
      margin-top: 120px;
      display: grid;
      grid-template-columns: 320px 1fr;
      gap: 22px;
      align-items: start;
    }

    .profile-sidebar {
      display: flex;
      flex-direction: column;
      gap: 16px;
      min-width: 0;
    }

    .profile-main {
      display: flex;
      flex-direction: column;
      gap: 20px;
      min-width: 0;
    }

    .profile-side-menu {
      background: linear-gradient(160deg, rgba(22,23,32,0.95), rgba(14,15,20,0.98));
      border: 1px solid rgba(255,255,255,0.08);
      border-radius: 20px;
      padding: 14px;
      box-shadow: 0 8px 28px rgba(0,0,0,0.35);
      display: flex;
      flex-direction: column;
      gap: 10px;
    }

    .profile-side-menu a {
      display: flex;
      align-items: center;
      justify-content: flex-start;
      gap: 10px;
      padding: 12px 12px;
      border-radius: 14px;
      color: rgba(255,255,255,0.85);
      text-decoration: none;
      border: 1px solid transparent;
      background: rgba(255,255,255,0.03);
      transition: transform 0.15s, background 0.15s, border-color 0.15s;
      font-weight: 600;
    }

    .profile-side-menu a:hover {
      transform: translateY(-1px);
      border-color: rgba(199,159,94,0.22);
      background: rgba(199,159,94,0.08);
      color: rgba(255,255,255,0.98);
    }

    .profile-side-menu a.is-active {
      border-color: rgba(199,159,94,0.35);
      background: rgba(199,159,94,0.12);
      color: #fff;
    }

    /* ── Hero Banner Card ── */
    .profile-hero-card {
      position: relative; border-radius: 24px; overflow: hidden;
      background: linear-gradient(135deg, rgba(22,23,32,0.98), rgba(14,15,20,0.99));
      border: 1px solid rgba(255,255,255,0.08);
      box-shadow: 0 12px 40px rgba(0,0,0,0.5);
    }
    .profile-hero-banner {
      height: 110px;
      background: linear-gradient(135deg, #1a1208, #2d1f08, #1a1208);
      position: relative; overflow: hidden;
    }
    .profile-hero-banner::before {
      content: '';
      position: absolute; inset: 0;
      background: repeating-linear-gradient(
        45deg,
        transparent, transparent 20px,
        rgba(199,159,94,0.04) 20px, rgba(199,159,94,0.04) 21px
      );
    }
    .profile-hero-banner::after {
      content: '🎬';
      position: absolute; right: 24px; top: 50%; transform: translateY(-50%);
      font-size: 3.5rem; opacity: 0.12;
    }
    .profile-hero-body {
      padding: 0 28px 28px;
    }
    .profile-avatar-wrap {
      margin-top: -48px; margin-bottom: 14px; position: relative; display: inline-block;
    }
    .profile-avatar {
      width: 96px; height: 96px; border-radius: 50%;
      background: linear-gradient(135deg, #2a2b38, #1a1b24);
      border: 4px solid #0e0f14;
      display: flex; align-items: center; justify-content: center;
      font-size: 2rem; font-weight: 700; color: #c79f5e;
      overflow: hidden; position: relative;
      box-shadow: 0 0 0 3px rgba(199,159,94,0.25), 0 8px 24px rgba(0,0,0,0.5);
    }
    .profile-avatar img {
      position: absolute; inset: 0; width: 100%; height: 100%;
      object-fit: cover; border-radius: 50%;
    }
    .profile-name {
      font-size: 1.4rem; font-weight: 700; color: #f6f6f6; margin: 0 0 3px;
    }
    .profile-username {
      font-size: 0.85rem; color: rgba(255,255,255,0.45); margin: 0 0 12px;
    }
    .profile-tags { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 16px; }
    .profile-tag {
      display: inline-flex; align-items: center; gap: 5px;
      padding: 4px 12px; border-radius: 999px; font-size: 0.7rem; font-weight: 600;
    }
    .tag-member {
      background: rgba(199,159,94,0.12); border: 1px solid rgba(199,159,94,0.25); color: #c79f5e;
    }
    .tag-verified {
      background: rgba(74,222,128,0.1); border: 1px solid rgba(74,222,128,0.25); color: #4ade80;
    }
    .profile-edit-btn {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 9px 22px; border-radius: 999px; border: none;
      background: linear-gradient(135deg, #c79f5e, #a67c42);
      color: #111; font-weight: 700; font-size: 0.85rem;
      cursor: pointer; text-decoration: none;
      box-shadow: 0 4px 14px rgba(199,159,94,0.3);
      transition: opacity 0.15s, transform 0.15s; font-family: inherit;
    }
    .profile-edit-btn:hover { opacity: 0.88; transform: translateY(-1px); }

    /* ── Stats Row ── */
    .stats-row {
      display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px;
    }
    .stat-card {
      background: linear-gradient(160deg, rgba(22,23,32,0.95), rgba(14,15,20,0.98));
      border: 1px solid rgba(255,255,255,0.07);
      border-radius: 16px; padding: 18px 16px; text-align: center;
      box-shadow: 0 4px 16px rgba(0,0,0,0.3);
      transition: transform 0.2s, border-color 0.2s;
    }
    .stat-card:hover { transform: translateY(-2px); border-color: rgba(199,159,94,0.2); }
    .stat-icon { font-size: 1.4rem; margin-bottom: 8px; }
    .stat-value { font-size: 1.6rem; font-weight: 700; color: #c79f5e; line-height: 1; }
    .stat-label { font-size: 0.7rem; color: rgba(255,255,255,0.4); margin-top: 4px; text-transform: uppercase; letter-spacing: 0.08em; }

    /* ── Info Card ── */
    .info-card {
      background: linear-gradient(160deg, rgba(22,23,32,0.95), rgba(14,15,20,0.98));
      border: 1px solid rgba(255,255,255,0.08);
      border-radius: 20px; padding: 24px 28px;
      box-shadow: 0 8px 28px rgba(0,0,0,0.35);
    }
    .info-card-title {
      font-size: 0.7rem; font-weight: 700; letter-spacing: 0.12em;
      text-transform: uppercase; color: #c79f5e;
      margin: 0 0 18px; display: flex; align-items: center; gap: 8px;
    }
    .info-card-title::after {
      content: ''; flex: 1; height: 1px;
      background: linear-gradient(to right, rgba(199,159,94,0.3), transparent);
    }
    .info-rows { display: flex; flex-direction: column; gap: 12px; }
    .info-row {
      display: flex; align-items: center; gap: 14px;
      padding: 12px 14px; border-radius: 12px;
      background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06);
    }
    .info-row-icon {
      width: 36px; height: 36px; border-radius: 10px;
      background: rgba(199,159,94,0.1); border: 1px solid rgba(199,159,94,0.15);
      display: flex; align-items: center; justify-content: center;
      font-size: 0.95rem; flex-shrink: 0;
    }
    .info-row-body { flex: 1; }
    .info-row-label { font-size: 0.68rem; color: rgba(255,255,255,0.35); text-transform: uppercase; letter-spacing: 0.08em; }
    .info-row-value { font-size: 0.92rem; color: rgba(255,255,255,0.88); margin-top: 2px; font-weight: 500; }

    /* ── Actions Card ── */
    .actions-card {
      background: linear-gradient(160deg, rgba(22,23,32,0.95), rgba(14,15,20,0.98));
      border: 1px solid rgba(255,255,255,0.08);
      border-radius: 20px; padding: 20px 24px;
      display: flex; gap: 12px; flex-wrap: wrap; align-items: center;
      box-shadow: 0 4px 20px rgba(0,0,0,0.3);
    }
    .btn-ghost {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 10px 22px; border-radius: 999px;
      border: 1px solid rgba(255,255,255,0.18); background: rgba(255,255,255,0.04);
      color: rgba(255,255,255,0.8); font-size: 0.85rem; font-weight: 500;
      cursor: pointer; text-decoration: none;
      transition: background 0.15s, border-color 0.15s; font-family: inherit;
    }
    .btn-ghost:hover { background: rgba(255,255,255,0.08); border-color: rgba(255,255,255,0.3); }

    @media (max-width: 860px) {
      .profile-layout { grid-template-columns: 1fr; }
      .profile-side-menu { order: 2; }
    }
  </style>
  <?php require __DIR__ . '/includes/partials/site_header_scripts.php'; ?>
</head>
<body class="has-background">
  <?php
  $siteHeaderMidNavHtml = '<li><a class="nav-btn" href="homepage.php">Home</a></li>';
  $headerShowSearch = false;
  require __DIR__ . '/includes/partials/site_header.php';
  ?>

  <main>
    <div class="profile-wrapper">
      <div class="profile-layout">
        <aside class="profile-sidebar">
          <!-- Hero Card -->
          <div class="profile-hero-card">
            <div class="profile-hero-banner"></div>
            <div class="profile-hero-body">
              <div class="profile-avatar-wrap">
                <div class="profile-avatar">
                  <?php if (!empty($profilePic)): ?>
                    <img src="<?php echo htmlspecialchars($profilePic); ?>" alt="Profile picture">
                  <?php else: ?>
                    <?php echo $initial; ?>
                  <?php endif; ?>
                </div>
              </div>
              <div class="profile-name"><?php echo htmlspecialchars($userName); ?></div>
              <div class="profile-username">@<?php echo htmlspecialchars($username); ?></div>
              <div class="profile-tags">
                <span class="profile-tag tag-member">🎬 CineFlix Member</span>
                <span class="profile-tag tag-verified">✓ Verified</span>
                <?php if ($memberSince): ?>
                  <span class="profile-tag" style="background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);color:rgba(255,255,255,0.5);">📅 Since <?php echo $memberSince; ?></span>
                <?php endif; ?>
              </div>
              <a href="account-settings.php" class="profile-edit-btn">⚙️ Edit Profile</a>
            </div>
          </div>

          <!-- Side Menu (reference from your 3rd picture) -->
          <nav class="profile-side-menu" aria-label="Profile navigation">
            <a class="is-active" href="user-profile.php">👤 My Profile</a>
            <a href="logout.php">🚪 Log out</a>
          </nav>
        </aside>

        <section class="profile-main">
          <!-- Stats Row -->
          <div class="stats-row">
            <div class="stat-card">
              <div class="stat-icon">🎟️</div>
              <div class="stat-value"><?php echo $totalBookings; ?></div>
              <div class="stat-label">Bookings</div>
            </div>
            <div class="stat-card">
              <div class="stat-icon">🎬</div>
              <div class="stat-value">—</div>
              <div class="stat-label">Movies Watched</div>
            </div>
            <div class="stat-card">
              <div class="stat-icon">⭐</div>
              <div class="stat-value">—</div>
              <div class="stat-label">Rewards Pts</div>
            </div>
          </div>

          <!-- Account Info Card -->
          <div class="info-card">
            <div class="info-card-title">Account Details</div>
            <div class="info-rows">
              <div class="info-row">
                <div class="info-row-icon">👤</div>
                <div class="info-row-body">
                  <div class="info-row-label">Username</div>
                  <div class="info-row-value"><?php echo htmlspecialchars($username); ?></div>
                </div>
              </div>
              <div class="info-row">
                <div class="info-row-icon">🪪</div>
                <div class="info-row-body">
                  <div class="info-row-label">Full Name</div>
                  <div class="info-row-value"><?php echo htmlspecialchars($userName); ?></div>
                </div>
              </div>
              <div class="info-row">
                <div class="info-row-icon">✉️</div>
                <div class="info-row-body">
                  <div class="info-row-label">Email Address</div>
                  <div class="info-row-value"><?php echo htmlspecialchars($userEmail); ?></div>
                </div>
              </div>
              <?php if ($memberSince): ?>
              <div class="info-row">
                <div class="info-row-icon">📅</div>
                <div class="info-row-body">
                  <div class="info-row-label">Member Since</div>
                  <div class="info-row-value"><?php echo $memberSince; ?></div>
                </div>
              </div>
              <?php endif; ?>
            </div>
          </div>


        </section>
      </div>
    </div>
  </main>
</body>
</html>