<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

$conn = db_get_connection();
cineflix_bootstrap_session_from_cookie($conn);

$events = require __DIR__ . '/data/events.php';
$isLoggedIn = !empty($_SESSION['user_id']);

require_once __DIR__ . '/includes/cineflix_nav_helpers.php';
$cineflixNavMerged = $isLoggedIn
  ? array_merge(cineflix_nav_load_user($conn), cineflix_nav_load_inbox($conn))
  : ['navProfilePic' => '', 'navUsername' => '', 'userInboxItems' => [], 'userInboxUnread' => 0];
$userInboxItems = $cineflixNavMerged['userInboxItems'];
$userInboxUnread = (int)$cineflixNavMerged['userInboxUnread'];

$userInitial = $isLoggedIn ? strtoupper(substr((string)($_SESSION['username'] ?? $_SESSION['user_name'] ?? 'U'), 0, 1)) : 'U';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title>Events | CineFlix</title>
  <link rel="stylesheet" href="common.css">
  <link rel="stylesheet" href="css/header-nav.css">
  <link rel="stylesheet" href="events.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <style>
    /* ── Fixed top navbar ── */
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
    main { padding: 120px 24px 60px; max-width: 1200px; margin: 0 auto; }
    .events-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 24px; }
    .event-card { background: rgba(0,0,0,0.68); border-radius: 18px; overflow: hidden; border: 1px solid rgba(255,255,255,0.08); box-shadow: 0 18px 40px rgba(0,0,0,0.35); display: flex; flex-direction: column; backdrop-filter: blur(4px); }
    .event-card img { width: 100%; height: 200px; object-fit: cover; }
    .event-card-body { padding: 20px 22px 24px; display: flex; flex-direction: column; gap: 12px; }
    .event-meta { font-size: 0.9rem; color: rgba(255,255,255,0.75); display: flex; flex-direction: column; gap: 4px; }
    .event-actions { margin-top: auto; display: flex; gap: 12px; flex-wrap: wrap; }
    .event-actions .btn { flex: 1; min-width: 140px; }
    .modal-backdrop { position: fixed; inset: 0; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px); display: none; justify-content: center; align-items: center; z-index: 2000; }
    .modal-card { width: min(480px, 92%); background: #111418; border-radius: 18px; border: 1px solid rgba(255,255,255,0.08); padding: 28px; box-shadow: 0 20px 40px rgba(0,0,0,0.45); }
    .modal-card h2 { margin-top: 0; margin-bottom: 12px; font-size: 1.5rem; }
    .modal-options { display: flex; flex-direction: column; gap: 14px; margin: 20px 0; }
    .option-btn { padding: 14px 18px; border-radius: 14px; border: 1px solid rgba(255,255,255,0.12); background: rgba(255,255,255,0.04); color: #fff; font-weight: 600; cursor: pointer; transition: all 0.18s ease; text-align: left; }
    .option-btn:hover { background: rgba(199,159,94,0.18); border-color: rgba(199,159,94,0.45); }
    .modal-footer { display: flex; justify-content: flex-end; gap: 12px; }
    .btn { border: none; border-radius: 999px; padding: 12px 18px; font-weight: 600; background: linear-gradient(90deg,#c79f5e,#a67c42); color: #fff; cursor: pointer; transition: transform 0.2s ease; display: inline-flex; align-items: center; justify-content: center; line-height: 1; }
    .btn:hover { transform: translateY(-2px); }
    .btn-secondary { background: rgba(255,255,255,0.18); }
    @media (max-width: 600px) {
      main { padding-top: 140px; }
      .event-actions { flex-direction: column; }
    }
  </style>
  <?php require __DIR__ . '/includes/partials/site_header_scripts.php'; ?>
</head>
<body class="has-background">
  <div class="background-blur"></div>

  <?php
  $siteHeaderMidNavHtml = '';
  $headerShowSearch = false;
  require __DIR__ . '/includes/partials/site_header.php';
  ?>

  <main>
    <h1 style="margin-bottom:28px;font-size:2.2rem;">Experience-Driven Events</h1>
    <p style="margin-bottom:40px;color:rgba(255,255,255,0.78);max-width:720px;">Choose from immersive fan nights, celeb meet-ups, and special screenings exclusive to CineFlix members. Select an experience to see more details or reserve your slot.</p>

    <div class="events-grid">
      <?php foreach ($events as $event): ?>
        <article class="event-card" data-event-id="<?php echo htmlspecialchars($event['id']); ?>">
          <img src="<?php echo htmlspecialchars($event['image']); ?>" alt="<?php echo htmlspecialchars($event['title']); ?> poster">
          <div class="event-card-body">
            <h2 style="margin:0;font-size:1.35rem;line-height:1.35;"><?php echo htmlspecialchars($event['title']); ?></h2>
            <div class="event-meta">
              <span>📅 <?php echo date('F j, Y', strtotime($event['date'])); ?></span>
              <span>🕒 <?php echo htmlspecialchars($event['time']); ?></span>
              <span>📍 <?php echo htmlspecialchars($event['location']); ?></span>
            </div>
            <p style="color:rgba(255,255,255,0.7);font-size:0.95rem;line-height:1.5;"><?php echo htmlspecialchars($event['description']); ?></p>
            <div class="event-actions">
              <button class="btn" data-action="choose" data-event-id="<?php echo htmlspecialchars($event['id']); ?>">Choose Experience</button>
              <a class="btn btn-secondary" href="event-details.php?id=<?php echo urlencode($event['id']); ?>">View Details</a>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </main>

  <div class="modal-backdrop" id="experienceModal" aria-hidden="true">
    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="modal-title">
      <h2 id="modal-title">Choose Your Experience</h2>
      <p id="modal-description" style="color:rgba(255,255,255,0.7);margin:0 0 12px;">How would you like to enjoy this event?</p>
      <div class="modal-options">
        <button class="option-btn" data-option="meet">Meet &amp; Greet</button>
        <button class="option-btn" data-option="screening">Special Screening</button>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" id="modalCancel">Cancel</button>
      </div>
    </div>
  </div>

  <script>
    window.__cineflixEvents = <?php echo json_encode($events, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    window.__isLoggedIn = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;
  </script>
  <script src="events.js"></script>
</body>
</html>