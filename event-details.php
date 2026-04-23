<?php
session_start();
$events = require __DIR__ . '/data/events.php';
$eventId = $_GET['id'] ?? null;
$selectedOption = $_GET['option'] ?? null;
$isLoggedIn = !empty($_SESSION['user_id']);
$userName = $_SESSION['user_name'] ?? null;
$username = $_SESSION['username'] ?? null;
$userEmail = $_SESSION['user_email'] ?? null;

$event = null;
foreach ($events as $item) {
    if ($item['id'] === $eventId) {
        $event = $item;
        break;
    }
}

if (!$event) {
    http_response_code(404);
}

$optionLabels = [
    'meet' => 'Meet & Greet',
    'screening' => 'Special Screening'
];
$optionLabel = $selectedOption && isset($optionLabels[$selectedOption]) ? $optionLabels[$selectedOption] : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title><?php echo $event ? htmlspecialchars($event['title']) . ' | CineFlix Event' : 'Event Not Found'; ?></title>
  <link rel="stylesheet" href="common.css">
  <link rel="stylesheet" href="events.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <style>
    main { padding: 120px 20px 60px; max-width: 1100px; margin: 0 auto; display: grid; gap: 28px; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); }
    .card { background: rgba(0,0,0,0.7); border-radius: 20px; border: 1px solid rgba(255,255,255,0.08); box-shadow: 0 20px 44px rgba(0,0,0,0.4); overflow: hidden; backdrop-filter: blur(6px); }
    .card img { width: 100%; height: 260px; object-fit: cover; }
    .card-body { padding: 26px 28px; color: #fff; }
    .card-body h1 { margin: 0 0 12px; font-size: 2rem; }
    .meta { display: grid; gap: 10px; margin: 18px 0; color: rgba(255,255,255,0.75); font-size: 0.95rem; }
    .cta { display: flex; gap: 14px; flex-wrap: wrap; margin-top: 26px; }
    .btn { border-radius: 999px; border: none; padding: 12px 20px; font-weight: 600; background: linear-gradient(90deg,#c79f5e,#a67c42); color: #fff; cursor: pointer; transition: transform 0.2s ease; }
    .btn:hover { transform: translateY(-2px); }
    .btn-secondary { background: rgba(255,255,255,0.18); }
    .profile-card { min-height: 320px; }
    .placeholder { padding: 120px 20px; text-align: center; color: rgba(255,255,255,0.8); }
  </style>
</head>
<body class="has-background">
  <div class="background-blur"></div>

  <header class="site-header">
    <a class="logo" href="homepage.php">
      <img src="logo/newlogo1.png" alt="CineFlix Logo">
    </a>
    <nav class="top-nav">
      <ul>
        </li>
        <?php if ($isLoggedIn): ?>
        <?php else: ?>
          <li><a class="nav-btn" href="login.html">Login</a></li>
        <?php endif; ?>
      </ul>
    </nav>
  </header>

  <?php if (!$event): ?>
    <div class="placeholder">
      <h1>Event Not Found</h1>
      <p>The event you are looking for might have been removed or is temporarily unavailable.</p>
      <a class="btn" href="events.php">Back to Events</a>
    </div>
  <?php else: ?>
    <main>
      <section class="card">
        <img src="<?php echo htmlspecialchars($event['image']); ?>" alt="<?php echo htmlspecialchars($event['title']); ?> poster">
        <div class="card-body">
          <h1><?php echo htmlspecialchars($event['title']); ?></h1>
          <?php if ($optionLabel): ?>
            <p style="margin:0 0 16px;color:#c79f5e;font-weight:600;text-transform:uppercase;letter-spacing:0.06em;">Selected Experience: <?php echo htmlspecialchars($optionLabel); ?></p>
          <?php endif; ?>
          <p style="color:rgba(255,255,255,0.78);font-size:1rem;line-height:1.6;"><?php echo htmlspecialchars($event['description']); ?></p>
          <div class="meta">
            <span>📅 <?php echo date('l, F j, Y', strtotime($event['date'])); ?></span>
            <span>🕒 <?php echo htmlspecialchars($event['time']); ?></span>
            <span>📍 <?php echo htmlspecialchars($event['location']); ?></span>
          </div>
          <div class="cta">
            <a class="btn" href="events.php">Explore Other Events</a>
            <?php if ($isLoggedIn): ?>
              <a class="btn btn-secondary" href="<?php echo 'booking.php?type=event&id=' . urlencode($event['id']) . ($selectedOption ? '&option=' . urlencode($selectedOption) : '') . '&title=' . urlencode($event['title']) . '&date=' . urlencode($event['date']) . '&time=' . urlencode($event['time']) . '&location=' . urlencode($event['location']) . '&image=' . urlencode($event['image']); ?>">Book This Experience</a>
            <?php else: ?>
              <a class="btn btn-secondary" href="login.html">Log in to Book</a>
            <?php endif; ?>
          </div>
        </div>
      </section>

      <section class="card profile-card">
        <div class="card-body">
          <h2 style="margin-top:0;margin-bottom:18px;">Your CineFlix Profile</h2>
          <?php if ($isLoggedIn): ?>
            <p><strong>Full Name:</strong> <?php echo htmlspecialchars($userName ?? 'Not set'); ?></p>
            <p><strong>Username:</strong> <?php echo htmlspecialchars($username ?? 'Not set'); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($userEmail ?? 'Not set'); ?></p>
            <p style="margin-top:20px;color:rgba(255,255,255,0.7);">Booking an event will automatically use your profile details at checkout.</p>
          <?php else: ?>
            <p style="color:rgba(255,255,255,0.75);">Log in or create an account to see personalised details and secure priority access to exclusive events.</p>
            <div class="cta" style="margin-top:24px;">
              <a class="btn" href="login.html">Log In</a>
              <a class="btn btn-secondary" href="signup.html">Create Account</a>
            </div>
          <?php endif; ?>
        </div>
      </section>
    </main>
  <?php endif; ?>
</body>
</html>
