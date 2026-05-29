<?php
session_start();
$_SESSION['is_admin'] = true;
$_SESSION['admin_name'] = 'Portfolio Admin';
if (false && empty($_SESSION['is_admin'])) {
    header('Location: login.html');
    exit();
}

require_once __DIR__ . '/includes/db.php';
$conn = db_get_connection();

// ── Handle trailer URL update (no separate API file needed) ───────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw  = file_get_contents('php://input');
    $json = json_decode($raw, true);
    if (($json['action'] ?? '') === 'update_trailer') {
        header('Content-Type: application/json');
        // Ensure column exists
        $cc = $conn->query("SELECT COUNT(*) AS cnt FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='Movie' AND COLUMN_NAME='TrailerURL'");
        if ($cc && (int)$cc->fetch_assoc()['cnt'] === 0) {
            $conn->query("ALTER TABLE Movie ADD COLUMN TrailerURL VARCHAR(500) NULL DEFAULT NULL");
        }
        $movieId    = (int)($json['movie_id'] ?? 0);
        $trailerUrl = trim($json['trailer_url'] ?? '');
        if ($movieId < 1) { echo json_encode(['success'=>false,'error'=>'Invalid ID']); exit(); }
        $val = $trailerUrl !== '' ? "'".$conn->real_escape_string($trailerUrl)."'" : 'NULL';
        if ($conn->query("UPDATE Movie SET TrailerURL=$val WHERE MovieID=$movieId")) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => $conn->error]);
        }
        exit();
    }
    // ── Handle section update ──────────────────────────────────────────
    if (($json['action'] ?? '') === 'update_section') {
        header('Content-Type: application/json');
        $movieId = (int)($json['movie_id'] ?? 0);
        $allowed = ['now_showing', 'coming_soon', 'more_movies'];
        $section = in_array($json['section'] ?? '', $allowed) ? $json['section'] : 'more_movies';
        if ($movieId < 1) { echo json_encode(['success'=>false,'error'=>'Invalid ID']); exit(); }
        $safeSection = $conn->real_escape_string($section);
        if ($conn->query("UPDATE Movie SET section='$safeSection' WHERE MovieID=$movieId")) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => $conn->error]);
        }
        exit();
    }
}

// Safely add TrailerURL column if it doesn't exist
$colCheck = $conn->query("SELECT COUNT(*) AS cnt FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='Movie' AND COLUMN_NAME='TrailerURL'");
if ($colCheck) {
    $colRow = $colCheck->fetch_assoc();
    if ((int)$colRow['cnt'] === 0) {
        $conn->query("ALTER TABLE Movie ADD COLUMN TrailerURL VARCHAR(500) NULL DEFAULT NULL");
    }
}

// Safely add section column if it doesn't exist
$secCheck = $conn->query("SELECT COUNT(*) AS cnt FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='Movie' AND COLUMN_NAME='section'");
if ($secCheck && (int)$secCheck->fetch_assoc()['cnt'] === 0) {
    $conn->query("ALTER TABLE Movie ADD COLUMN `section` ENUM('now_showing','coming_soon','more_movies') NOT NULL DEFAULT 'more_movies'");
}


function fetch_single_value(mysqli $conn, string $sql, string $alias)
{
    $result = $conn->query($sql);
    if (!$result) {
        return 0;
    }
    $row = $result->fetch_assoc();
    return $row[$alias] ?? 0;
}

$totalTickets = fetch_single_value($conn, "SELECT COALESCE(SUM(quantity),0) AS total FROM bookings", 'total');
$totalRevenue = fetch_single_value($conn, "SELECT COALESCE(SUM(total_amount),0) AS revenue FROM bookings WHERE status NOT IN ('Refund Requested', 'Refunded')", 'revenue');
$totalRefunds = fetch_single_value($conn, "SELECT COALESCE(COUNT(*),0) AS refunds FROM bookings WHERE status IN ('Refund Requested','Refunded')", 'refunds');

$topItemResult = $conn->query("SELECT item_name, SUM(quantity) AS total FROM bookings GROUP BY item_name ORDER BY total DESC LIMIT 1");
$topItem = $topItemResult ? $topItemResult->fetch_assoc() : null;

$leastItemResult = $conn->query("SELECT item_name, SUM(quantity) AS total FROM bookings GROUP BY item_name HAVING total > 0 ORDER BY total ASC LIMIT 1");
$leastItem = $leastItemResult ? $leastItemResult->fetch_assoc() : null;

$breakdownResult = $conn->query("SELECT item_name, item_type, SUM(quantity) AS total_quantity, SUM(total_amount) AS gross FROM bookings GROUP BY item_name, item_type ORDER BY total_quantity DESC");
$breakdown = [];
if ($breakdownResult) {
    while ($row = $breakdownResult->fetch_assoc()) {
        $breakdown[] = $row;
    }
}

$recentBookingsResult = $conn->query("SELECT b.booking_id, b.item_name, b.item_type, b.quantity, b.total_amount, b.status, b.created_at, COALESCE(c.Name, b.customer_name, 'Guest') AS customer_name, COALESCE(c.Email, b.customer_email, 'N/A') AS customer_email FROM bookings b LEFT JOIN CustomerUser c ON c.CustomerID = b.customer_id ORDER BY b.created_at DESC LIMIT 50");
$recentBookings = [];
if ($recentBookingsResult) {
    while ($row = $recentBookingsResult->fetch_assoc()) {
        $recentBookings[] = $row;
    }
}

$events = require __DIR__ . '/data/events.php';

// Refund requests for admin review
$refundRequestsResult = $conn->query("SELECT booking_id, customer_name, customer_email, item_name, item_type, show_date, show_time, venue, total_amount, refund_reason, refund_status, status, created_at
                                      FROM bookings
                                      WHERE status = 'Refund Requested'
                                      ORDER BY created_at DESC");
$refundRequests = [];
if ($refundRequestsResult) {
    while ($row = $refundRequestsResult->fetch_assoc()) {
        $refundRequests[] = $row;
    }
}

// Refund history (approved and rejected)
$refundHistoryResult = $conn->query("SELECT booking_id, customer_name, customer_email, item_name, item_type, show_date, show_time, venue, total_amount, refund_reason, refund_status, status, cancelled_date, created_at
                                     FROM bookings
                                     WHERE refund_status IN ('Approved','Rejected')
                                     ORDER BY cancelled_date DESC, created_at DESC
                                     LIMIT 100");
$refundHistory = [];
if ($refundHistoryResult) {
    while ($row = $refundHistoryResult->fetch_assoc()) {
        $refundHistory[] = $row;
    }
}

// PWD / Senior discount verification queue
$discountQueue = [];
$discountResult = $conn->query("SELECT booking_id, customer_name, customer_email, item_name, item_type,
                                       show_date, show_time, venue, total_amount,
                                       discount_type, discount_original_total, discounted_total,
                                       discount_status, discount_id_number, discount_id_path, created_at
                                FROM bookings
                                WHERE discount_type IS NOT NULL
                                ORDER BY created_at DESC
                                LIMIT 100");
if ($discountResult) {
    while ($row = $discountResult->fetch_assoc()) {
        $discountQueue[] = $row;
    }
}

$refundNavCount = count($refundRequests);
$discountPendingCount = 0;
foreach ($discountQueue as $dRow) {
    $ds = strtolower(trim((string)($dRow['discount_status'] ?? '')));
    if ($ds === '' || $ds === 'pending') {
        $discountPendingCount++;
    }
}

$adminDisplayName = $_SESSION['admin_name'] ?? 'Administrator';
$adminInitial = strtoupper(substr($adminDisplayName, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title>CineFlix Admin Dashboard</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="admin-dashboard.css">
</head>
<body class="admin-app">
  <div class="admin-sidebar-backdrop" id="adminSidebarBackdrop" aria-hidden="true"></div>

  <div class="admin-layout">
  <aside class="admin-sidebar" id="adminSidebar" aria-label="Admin navigation">
    <div class="admin-sidebar-brand">
      <div class="admin-sidebar-logo">C</div>
      <div>
        <h1>CineFlix</h1>
        <span>Admin Console</span>
      </div>
    </div>
    <nav class="admin-nav">
      <a class="admin-nav-link is-active" href="#overview"><span class="admin-nav-icon">📊</span> Dashboard</a>
      <a class="admin-nav-link" href="#recent-bookings"><span class="admin-nav-icon">🎟</span> Recent bookings</a>
      <a class="admin-nav-link" href="#movies"><span class="admin-nav-icon">🎬</span> Movies</a>
      <a class="admin-nav-link" href="#schedules"><span class="admin-nav-icon">🎟</span> Schedules</a>
      <a class="admin-nav-link" href="#events"><span class="admin-nav-icon">🎪</span> Events</a>
      <a class="admin-nav-link" href="#refund-requests"><span class="admin-nav-icon">↩️</span> Refund requests<?php if ($refundNavCount > 0): ?><span class="admin-nav-badge"><?php echo (int)$refundNavCount; ?></span><?php endif; ?></a>
      <a class="admin-nav-link" href="#refund-history"><span class="admin-nav-icon">📜</span> Refund history</a>
      <a class="admin-nav-link" href="#discount-verification"><span class="admin-nav-icon">🪪</span> PWD / Senior<?php if ($discountPendingCount > 0): ?><span class="admin-nav-badge"><?php echo (int)$discountPendingCount; ?></span><?php endif; ?></a>
    </nav>
    <div class="admin-sidebar-footer">
    </div>
  </aside>

  <div class="admin-shell">
    <header class="admin-topbar">
      <button type="button" class="admin-menu-btn" id="adminMenuBtn" aria-label="Open menu">☰</button>
      <div class="admin-search-wrap">
        <span class="admin-search-icon">🔍</span>
        <input type="search" id="adminSectionSearch" placeholder="Search sections (bookings, movies, refunds…)" autocomplete="off" aria-label="Filter dashboard sections">
      </div>
      <div class="admin-topbar-actions">
        <div class="admin-notification-wrap" style="position: relative; margin-right: 15px;">
          <div class="admin-notif-panel" id="adminNotifPanel" style="display: none; position: absolute; right: 0; top: 40px; width: 320px; background: #1a1a2e; border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); z-index: 1000; max-height: 400px; overflow-y: auto;">
            <div style="padding: 12px; border-bottom: 1px solid rgba(255,255,255,0.1); font-weight: 600; display: flex; justify-content: space-between; align-items: center;">
              <span>Notifications</span>
              <button onclick="toggleAdminNotifications()" style="background: none; border: none; color: #fff; cursor: pointer;">×</button>
            </div>
            <div id="adminNotifList" style="padding: 0;">
              <!-- Notifications will be loaded here -->
            </div>
          </div>
        </div>
        <div class="admin-user-chip">
          <span class="admin-user-avatar"><?php echo htmlspecialchars($adminInitial); ?></span>
          <div>
            <strong style="font-size:0.82rem;"><?php echo htmlspecialchars($adminDisplayName); ?></strong>
            <small>Administrator</small>
          </div>
        </div>
        <a class="admin-btn admin-btn--ghost" href="homepage.php"><span>Homepage</span></a>
        <a class="admin-btn admin-btn--primary" href="logout.php"><span>Logout</span></a>
      </div>
    </header>

    <main class="admin-main">
    <section id="overview" class="admin-section" data-admin-search="dashboard overview metrics tickets revenue refunds">
    <div class="admin-metrics">
      <div class="admin-card admin-card--glass admin-metric admin-metric--tickets">
        <div class="admin-metric-head">
          <div>
            <p class="admin-metric-label">Total tickets sold</p>
            <div class="admin-metric-value"><?php echo (int)$totalTickets; ?></div>
            <p class="admin-metric-foot">All confirmed movie and event tickets.</p>
          </div>
          <div class="admin-metric-icon" aria-hidden="true">🎟</div>
        </div>
        <span class="admin-pill admin-pill--up">Lifetime</span>
      </div>
      <div class="admin-card admin-card--glass admin-metric admin-metric--revenue">
        <div class="admin-metric-head">
          <div>
            <p class="admin-metric-label">Gross revenue</p>
            <div class="admin-metric-value">₱<?php echo number_format((float)$totalRevenue, 2); ?></div>
            <p class="admin-metric-foot">Excludes pending refunds.</p>
          </div>
          <div class="admin-metric-icon" aria-hidden="true">💰</div>
        </div>
        <span class="admin-pill admin-pill--neutral">Net sales</span>
      </div>
      <div class="admin-card admin-card--glass admin-metric admin-metric--refunds">
        <div class="admin-metric-head">
          <div>
            <p class="admin-metric-label">Refunds in progress</p>
            <div class="admin-metric-value"><?php echo (int)$totalRefunds; ?></div>
            <p class="admin-metric-foot">Open refund requests awaiting processing.</p>
          </div>
          <div class="admin-metric-icon" aria-hidden="true">↩️</div>
        </div>
        <span class="admin-pill admin-pill--neutral">Queue</span>
      </div>
    </div>

    <div class="admin-grid-2" style="margin-top:22px;">
      <div class="admin-card admin-card--glass admin-chart-wrap">
        <h2 class="admin-section-title">Ticket sales overview</h2>
        <p class="admin-section-desc" style="margin-bottom:12px;">Tickets sold per title (bar chart).</p>
        <canvas id="salesChart" aria-label="Tickets sold per title"></canvas>
      </div>
      <div>
        <div class="admin-highlight-stack">
          <div class="admin-hl-card admin-hl-card--a">
            <h3>Top seller</h3>
            <p><?php echo $topItem ? htmlspecialchars($topItem['item_name']) : '—'; ?></p>
            <p class="admin-hl-sub"><?php echo $topItem ? (int)$topItem['total'] . ' tickets' : 'No data yet'; ?></p>
          </div>
          <div class="admin-hl-card admin-hl-card--b">
            <h3>Needs attention</h3>
            <p><?php echo $leastItem ? htmlspecialchars($leastItem['item_name']) : '—'; ?></p>
            <p class="admin-hl-sub"><?php echo $leastItem ? (int)$leastItem['total'] . ' tickets (lowest)' : 'Add bookings to see trends'; ?></p>
          </div>
        </div>
        <p class="muted" style="margin-top:14px;font-size:0.85rem;line-height:1.5;">Balance marketing and showtimes using these highlights — low sellers may need better slots or promos.</p>
      </div>
    </div>
    </section>

    <section id="recent-bookings" class="admin-section admin-card admin-card--glass" data-admin-search="recent bookings transactions table customers">
      <h2 class="admin-section-title">Recent bookings</h2>
      <?php if (!$recentBookings): ?>
        <p class="muted">No bookings have been recorded yet.</p>
      <?php else: ?>
        <div class="admin-table-wrap">
          <table class="admin-table">
            <thead>
              <tr>
                <th>Booking ID</th>
                <th>Customer</th>
                <th>Email</th>
                <th>Title</th>
                <th>Type</th>
                <th>Tickets</th>
                <th>Total</th>
                <th>Status</th>
                <th>Created</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recentBookings as $booking): ?>
                <?php
                  $statusClass = 'status-paid';
                  $status = strtolower($booking['status']);
                  if ($status === 'refund requested') { $statusClass = 'status-refund'; }
                  if ($status === 'refunded' || $status === 'cancelled') { $statusClass = 'status-cancelled'; }
                ?>
                <tr>
                  <td><?php echo htmlspecialchars($booking['booking_id']); ?></td>
                  <td><?php echo htmlspecialchars($booking['customer_name']); ?></td>
                  <td><?php echo htmlspecialchars($booking['customer_email']); ?></td>
                  <td><?php echo htmlspecialchars($booking['item_name']); ?></td>
                  <td><?php echo htmlspecialchars(ucfirst($booking['item_type'])); ?></td>
                  <td><?php echo (int)$booking['quantity']; ?></td>
                  <td>₱<?php echo number_format((float)$booking['total_amount'], 2); ?></td>
                  <td><span class="status-pill <?php echo $statusClass; ?>"><?php echo htmlspecialchars($booking['status']); ?></span></td>
                  <td><?php echo date('M j, Y g:i A', strtotime($booking['created_at'])); ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </section>

    <section id="movies" class="admin-section admin-card admin-card--glass" data-admin-search="movies manage add remove trailer section poster">
      <h2 class="admin-section-title">Manage movies</h2>
      <p class="admin-section-desc">Add or remove movies from the platform.</p>
      
      <div class="admin-form-panel">
        <h3>Add new movie</h3>
        <form id="addMovieForm" enctype="multipart/form-data" class="admin-form-grid">
          <div class="admin-field">
            <label>Movie title *</label>
            <input type="text" name="title" placeholder="Movie Title" required>
          </div>
          <div class="admin-field">
            <label>Duration (min) *</label>
            <input type="number" name="duration" placeholder="Duration" required min="1">
          </div>
          <div class="admin-field">
            <label>Genre *</label>
            <input type="text" name="genre" placeholder="Genre (e.g. Action, Comedy)" required>
          </div>
          <div class="admin-field" style="grid-column: 1 / -1;">
            <label>Movie description *</label>
            <textarea name="description" placeholder="Short movie description (shown to users)" required rows="4" style="width:100%;padding:10px 12px;border-radius:10px;border:1px solid rgba(255,255,255,0.15);background:rgba(0,0,0,0.4);color:#fff;font-size:0.95rem;resize:vertical;"></textarea>
            <small class="muted" style="display:block;margin-top:6px;font-size:0.78rem;">Add a clear description and genre so the movie can be released.</small>
          </div>
          <div class="admin-field">
            <label>Release date</label>
            <input type="date" name="release_date">
          </div>
          <div class="admin-field">
            <label>Rating (0–10)</label>
            <input type="number" name="rating" step="0.1" min="0" max="10" placeholder="Rating">
          </div>
          <div class="admin-field">
            <label>Poster image</label>
            <input type="file" name="poster_file" accept="image/*">
            <small class="muted" style="display:block;margin-top:4px;font-size:0.78rem;">Upload a poster image for the listing.</small>
          </div>
          <div class="admin-field" style="grid-column: 1 / -1;">
            <label>Trailer (YouTube URL)</label>
            <input type="url" name="trailer_url" placeholder="https://www.youtube.com/watch?v=...">
            <small class="muted" style="display:block;margin-top:4px;font-size:0.78rem;">Optional YouTube link for the trailer button.</small>
          </div>
          <div class="admin-field">
            <label>Homepage section *</label>
            <select name="section" required>
              <option value="now_showing">🎬 Now Showing</option>
              <option value="coming_soon">🔜 Coming Soon</option>
              <option value="more_movies" selected>➕ More Movies</option>
            </select>
            <small class="muted" style="display:block;margin-top:4px;font-size:0.78rem;">Where this title appears on the homepage.</small>
          </div>
          <div class="admin-field">
            <label>&nbsp;</label>
            <button type="submit" class="admin-btn admin-btn--primary" style="width:100%;">Add movie</button>
          </div>
        </form>
      </div>

      <div class="admin-table-wrap" style="margin-top: 8px;">
        <table class="admin-table admin-table--compact admin-table--wide">
          <thead>
            <tr>
              <th style="padding: 12px; text-align: left; font-weight: 600; color: rgba(255,255,255,0.8);">Poster</th>
              <th style="padding: 12px; text-align: left; font-weight: 600; color: rgba(255,255,255,0.8);">Title</th>
              <th style="padding: 12px; text-align: left; font-weight: 600; color: rgba(255,255,255,0.8);">Duration</th>
              <th style="padding: 12px; text-align: left; font-weight: 600; color: rgba(255,255,255,0.8);">Genre</th>
              <th style="padding: 12px; text-align: left; font-weight: 600; color: rgba(255,255,255,0.8);">Release Date</th>
              <th style="padding: 12px; text-align: left; font-weight: 600; color: rgba(255,255,255,0.8);">Rating</th>
              <th style="padding: 12px; text-align: left; font-weight: 600; color: rgba(255,255,255,0.8);">Trailer URL</th>
              <th style="padding: 12px; text-align: left; font-weight: 600; color: rgba(255,255,255,0.8);">Section</th>
              <th style="padding: 12px; text-align: center; font-weight: 600; color: rgba(255,255,255,0.8);">Actions</th>
            </tr>
          </thead>
          <tbody id="moviesList">
            <?php
            $moviesResult = $conn->query("SELECT * FROM Movie ORDER BY ReleaseDate DESC, Title ASC");
            if ($moviesResult && $moviesResult->num_rows > 0):
              while ($movie = $moviesResult->fetch_assoc()):
                $trailerRaw = htmlspecialchars($movie['TrailerURL'] ?? '');
            ?>
              <tr data-movie-id="<?php echo $movie['MovieID']; ?>" style="border-bottom: 1px solid rgba(255,255,255,0.05); transition: background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.03)'" onmouseout="this.style.background='transparent'">
                <td style="padding: 10px 12px;">
                  <?php if (!empty($movie['PosterPath'])): ?>
                    <img src="<?php echo htmlspecialchars($movie['PosterPath']); ?>" alt="<?php echo htmlspecialchars($movie['Title']); ?>" style="width:52px;height:74px;object-fit:cover;border-radius:6px;border:1px solid rgba(255,255,255,0.08);" onerror="this.style.display='none';this.nextSibling.style.display='flex'">
                    <div style="display:none;width:52px;height:74px;background:rgba(255,255,255,0.05);border-radius:6px;align-items:center;justify-content:center;font-size:1.4rem;">🎬</div>
                  <?php else: ?>
                    <div style="width:52px;height:74px;background:rgba(255,255,255,0.05);border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;">🎬</div>
                  <?php endif; ?>
                </td>
                <td style="padding: 12px; color: rgba(255,255,255,0.9); font-weight:500;"><?php echo htmlspecialchars($movie['Title']); ?></td>
                <td style="padding: 12px; color: rgba(255,255,255,0.7);"><?php echo (int)$movie['Duration']; ?> min</td>
                <td style="padding: 12px; color: rgba(255,255,255,0.7);"><?php echo htmlspecialchars($movie['Genre'] ?? 'N/A'); ?></td>
                <td style="padding: 12px; color: rgba(255,255,255,0.7);"><?php echo $movie['ReleaseDate'] ? date('M j, Y', strtotime($movie['ReleaseDate'])) : 'N/A'; ?></td>
                <td style="padding: 12px; color: rgba(255,255,255,0.7);"><?php echo $movie['Rating'] ? number_format((float)$movie['Rating'], 1) : 'N/A'; ?></td>
                <td style="padding: 12px; min-width:260px;">
                  <div style="display:flex;gap:6px;align-items:center;">
                    <input type="url" id="trailer-input-<?php echo $movie['MovieID']; ?>"
                           value="<?php echo $trailerRaw; ?>"
                           placeholder="https://www.youtube.com/watch?v=..."
                           style="flex:1;padding:6px 8px;border-radius:6px;border:1px solid rgba(255,255,255,0.15);background:rgba(0,0,0,0.4);color:#fff;font-size:0.78rem;">
                    <button type="button" class="admin-btn admin-btn--ghost" onclick="saveTrailerUrl(<?php echo $movie['MovieID']; ?>)"
                            style="padding:5px 10px;font-size:0.78rem;white-space:nowrap;">💾 Save</button>
                  </div>
                  <?php if (!empty($movie['TrailerURL'])): ?>
                    <a href="<?php echo htmlspecialchars($movie['TrailerURL']); ?>" target="_blank"
                       style="display:inline-block;margin-top:4px;font-size:0.75rem;color:#c79f5e;">▶ Preview</a>
                  <?php endif; ?>
                </td>
                <td style="padding: 12px; min-width:160px;">
                  <?php $curSection = $movie['section'] ?? 'more_movies'; ?>
                  <select id="section-select-<?php echo $movie['MovieID']; ?>"
                          onchange="updateSection(<?php echo $movie['MovieID']; ?>, this.value)"
                          style="width:100%;padding:6px 8px;border-radius:6px;border:1px solid rgba(255,255,255,0.15);background:rgba(0,0,0,0.5);color:#fff;font-size:0.82rem;cursor:pointer;">
                    <option value="now_showing"  <?php echo $curSection==='now_showing'  ? 'selected' : ''; ?>>🎬 Now Showing</option>
                    <option value="coming_soon"  <?php echo $curSection==='coming_soon'  ? 'selected' : ''; ?>>🔜 Coming Soon</option>
                    <option value="more_movies"  <?php echo $curSection==='more_movies'  ? 'selected' : ''; ?>>➕ More Movies</option>
                  </select>
                  <span id="section-status-<?php echo $movie['MovieID']; ?>" style="font-size:0.72rem;color:#4cdead;display:none;margin-top:3px;display:block;"></span>
                </td>
                <td style="padding: 12px; text-align: center; white-space:nowrap;">
                  <a class="admin-btn admin-btn--primary" href="booking.php?movie=<?php echo urlencode($movie['Title']); ?>&poster=<?php echo urlencode($movie['PosterPath'] ?? ''); ?>" style="padding:6px 12px;font-size:0.85rem;text-decoration:none;margin-right:6px;display:inline-flex;">🎟 Book</a>
                  <button type="button" class="admin-btn admin-btn--ghost" onclick="removeMovie(<?php echo $movie['MovieID']; ?>, '<?php echo htmlspecialchars(addslashes($movie['Title'])); ?>')" style="padding: 6px 12px; font-size: 0.85rem;">Remove</button>
                </td>
              </tr>
            <?php
              endwhile;
            else:
            ?>
              <tr>
                <td colspan="9" class="admin-empty">
                  <p style="margin: 0;">No movies in database</p>
                  <p style="margin: 8px 0 0; font-size: 0.9rem; color: rgba(255,255,255,0.4);">Add your first movie using the form above</p>
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>

    <!-- Movie Schedules Management -->
    <section id="schedules" class="admin-section admin-card admin-card--glass" data-admin-search="schedules manage movie showtimes">
      <h2 class="admin-section-title">Manage Movie Schedules</h2>
      <p class="admin-section-desc">Add and manage movie showtimes with theatre type selection.</p>
      
      <!-- Add Schedule Form -->
      <div class="admin-schedule-form" style="margin-bottom: 30px; padding: 20px; background: rgba(255,255,255,0.03); border-radius: 12px; border: 1px solid rgba(255,255,255,0.08);">
        <h3 style="margin-bottom: 15px; color: #fff;">Add New Schedule</h3>
        <form id="add-schedule-form" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
          <div>
            <label style="display: block; margin-bottom: 5px; color: rgba(255,255,255,0.9); font-size: 0.9rem;">Movie</label>
            <select id="schedule-movie-id" required style="width: 100%; padding: 8px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.15); background: rgba(0,0,0,0.4); color: #fff;">
              <option value="">Select Movie</option>
              <?php 
              $moviesResult = $conn->query("SELECT MovieID, Title FROM Movie ORDER BY Title ASC");
              if ($moviesResult) {
                  while ($movie = $moviesResult->fetch_assoc()) {
                      echo '<option value="' . $movie['MovieID'] . '">' . htmlspecialchars($movie['Title']) . '</option>';
                  }
              }
              ?>
            </select>
          </div>
          
          <div>
            <label style="display: block; margin-bottom: 5px; color: rgba(255,255,255,0.9); font-size: 0.9rem;">Theatre Type</label>
            <select id="schedule-theatre-type" required style="width: 100%; padding: 8px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.15); background: rgba(0,0,0,0.4); color: #fff;" onchange="updateDefaultPrice(this)">
              <option value="">Select Theatre Type</option>
              <option value="Standard" data-price="350">Standard (₱350)</option>
              <option value="IMAX" data-price="520">IMAX (₱520)</option>
              <option value="3D" data-price="420">3D (₱420)</option>
              <option value="Directors Club" data-price="650">Directors Club (₱650)</option>
            </select>
          </div>
          
          <div>
            <label style="display: block; margin-bottom: 5px; color: rgba(255,255,255,0.9); font-size: 0.9rem;">Cinema Hall</label>
            <input type="text" id="schedule-cinema-hall" placeholder="e.g., Cinema 1, IMAX Hall" required style="width: 100%; padding: 8px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.15); background: rgba(0,0,0,0.4); color: #fff;">
          </div>
          
          <div>
            <label style="display: block; margin-bottom: 5px; color: rgba(255,255,255,0.9); font-size: 0.9rem;">Show Date</label>
            <input type="date" id="schedule-show-date" required style="width: 100%; padding: 8px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.15); background: rgba(0,0,0,0.4); color: #fff;">
          </div>
          
          <div>
            <label style="display: block; margin-bottom: 5px; color: rgba(255,255,255,0.9); font-size: 0.9rem;">Show Time</label>
            <input type="time" id="schedule-show-time" required style="width: 100%; padding: 8px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.15); background: rgba(0,0,0,0.4); color: #fff;">
          </div>
          
          <div>
            <label style="display: block; margin-bottom: 5px; color: rgba(255,255,255,0.9); font-size: 0.9rem;">End Time</label>
            <input type="time" id="schedule-end-time" required style="width: 100%; padding: 8px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.15); background: rgba(0,0,0,0.4); color: #fff;">
          </div>
          
          <div>
            <label style="display: block; margin-bottom: 5px; color: rgba(255,255,255,0.9); font-size: 0.9rem;">Price (₱)</label>
            <input type="number" id="schedule-price" step="0.01" min="0" placeholder="350.00" required style="width: 100%; padding: 8px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.15); background: rgba(0,0,0,0.4); color: #fff;">
          </div>
          
          <div style="grid-column: 1 / -1; display: flex; gap: 10px; margin-top: 10px;">
            <button type="submit" class="admin-btn admin-btn--primary" style="padding: 10px 20px;">Add Schedule</button>
            <button type="button" class="admin-btn admin-btn--ghost" onclick="clearScheduleForm()" style="padding: 10px 20px;">Clear</button>
          </div>
        </form>
      </div>
      
      <!-- Schedules List -->
      <div class="admin-schedules-list">
        <h3 style="margin-bottom: 15px; color: #fff;">Current Schedules</h3>
        <div class="admin-table-wrap">
          <table class="admin-table">
            <thead>
              <tr>
                <th>Movie</th>
                <th>Theatre</th>
                <th>Cinema Hall</th>
                <th>Date</th>
                <th>Time</th>
                <th>Price</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="schedules-list-body">
              <!-- Schedules will be loaded here via JavaScript -->
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <section id="events" class="admin-section admin-card admin-card--glass" data-admin-search="events manage special experiences">
      <h2 class="admin-section-title">Manage events</h2>
      <p class="admin-section-desc">Toggle availability or review upcoming special experiences.</p>
      <div class="admin-events-grid" id="adminEvents">
        <?php foreach ($events as $event): ?>
          <article class="admin-event-card event-card" data-event-id="<?php echo htmlspecialchars($event['id']); ?>">
            <h3><?php echo htmlspecialchars($event['title']); ?></h3>
            <p class="muted"><?php echo date('F j, Y', strtotime($event['date'])); ?> • <?php echo htmlspecialchars($event['time']); ?></p>
            <p class="muted">Location: <?php echo htmlspecialchars($event['location']); ?></p>
            <div class="admin-event-actions">
              <button type="button" class="admin-btn admin-btn--ghost" data-action="toggle">Deactivate</button>
              <a class="admin-btn admin-btn--primary" href="event-details.php?id=<?php echo urlencode($event['id']); ?>" style="text-align:center;text-decoration:none;">View</a>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </section>

    <section id="refund-requests" class="admin-section admin-card admin-card--glass" data-admin-search="refund requests approve reject">
      <h2 class="admin-section-title">Refund requests</h2>
      <p class="admin-section-desc">Review and approve or reject refund requests submitted by users.</p>
      <?php if (!$refundRequests): ?>
        <p class="muted">There are currently no refund requests.</p>
      <?php else: ?>
        <div class="admin-table-wrap">
          <table class="admin-table admin-table--compact">
            <thead>
              <tr>
                <th>Booking ID</th>
                <th>Customer</th>
                <th>Title</th>
                <th>Showtime</th>
                <th>Amount</th>
                <th>Reason</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($refundRequests as $req): ?>
                <tr data-refund-booking-id="<?php echo htmlspecialchars($req['booking_id']); ?>">
                  <td><?php echo htmlspecialchars($req['booking_id']); ?></td>
                  <td>
                    <?php echo htmlspecialchars($req['customer_name'] ?: 'N/A'); ?><br>
                    <span class="muted" style="font-size:0.8rem;"><?php echo htmlspecialchars($req['customer_email'] ?: ''); ?></span>
                  </td>
                  <td><?php echo htmlspecialchars($req['item_name']); ?></td>
                  <td>
                    <?php echo htmlspecialchars($req['show_date'] ?: ''); ?><br>
                    <span class="muted" style="font-size:0.8rem;"><?php echo htmlspecialchars($req['show_time'] ?: ''); ?></span>
                  </td>
                  <td>₱<?php echo number_format((float)$req['total_amount'], 2); ?></td>
                  <td style="max-width:260px;"><?php echo nl2br(htmlspecialchars($req['refund_reason'] ?: 'No reason provided')); ?></td>
                  <td><?php echo htmlspecialchars($req['refund_status'] ?: $req['status']); ?></td>
                  <td style="white-space:nowrap;">
                    <button type="button" class="admin-btn admin-btn--ghost" style="padding:6px 10px;font-size:0.8rem;margin-right:4px;"
                            onclick="handleRefundDecision('<?php echo htmlspecialchars($req['booking_id']); ?>','approve')">
                      Approve
                    </button>
                    <button type="button" class="admin-btn admin-btn--ghost" style="padding:6px 10px;font-size:0.8rem;"
                            onclick="handleRefundDecision('<?php echo htmlspecialchars($req['booking_id']); ?>','reject')">
                      Reject
                    </button>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </section>

    <section id="refund-history" class="admin-section admin-card admin-card--glass" data-admin-search="refund history approved rejected">
      <h2 class="admin-section-title">Refund history</h2>
      <p class="admin-section-desc">Approved and rejected refund decisions.</p>
      <?php if (!$refundHistory): ?>
        <p class="muted">No refund history yet.</p>
      <?php else: ?>
        <div class="admin-table-wrap admin-scroll-y">
          <table class="admin-table admin-table--compact">
            <thead>
              <tr>
                <th>Booking ID</th>
                <th>Customer</th>
                <th>Title</th>
                <th>Showtime</th>
                <th>Amount</th>
                <th>Decision</th>
                <th>Updated</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($refundHistory as $row): ?>
                <tr>
                  <td><?php echo htmlspecialchars($row['booking_id']); ?></td>
                  <td>
                    <?php echo htmlspecialchars($row['customer_name'] ?: 'N/A'); ?><br>
                    <span class="muted" style="font-size:0.8rem;"><?php echo htmlspecialchars($row['customer_email'] ?: ''); ?></span>
                  </td>
                  <td><?php echo htmlspecialchars($row['item_name']); ?></td>
                  <td>
                    <?php echo htmlspecialchars($row['show_date'] ?: ''); ?><br>
                    <span class="muted" style="font-size:0.8rem;"><?php echo htmlspecialchars($row['show_time'] ?: ''); ?></span>
                  </td>
                  <td>₱<?php echo number_format((float)$row['total_amount'], 2); ?></td>
                  <td>
                    <?php echo htmlspecialchars($row['refund_status'] ?: $row['status']); ?>
                  </td>
                  <td>
                    <?php
                      $d = $row['cancelled_date'] ?: $row['created_at'];
                      echo $d ? date('M j, Y g:i A', strtotime($d)) : '—';
                    ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </section>

    <section id="discount-verification" class="admin-section admin-card admin-card--glass" data-admin-search="pwd senior discount verification id approve">
      <h2 class="admin-section-title">PWD / Senior discount verification</h2>
      <p class="admin-section-desc">Review uploaded IDs before confirming discounts on bookings.</p>
      <?php if (!$discountQueue): ?>
        <p class="muted">No discount requests have been recorded yet.</p>
      <?php else: ?>
        <div class="admin-table-wrap">
          <table class="admin-table admin-table--compact">
            <thead>
              <tr>
                <th>Booking ID</th>
                <th>Customer</th>
                <th>Title</th>
                <th>Showtime</th>
                <th>Base Tickets</th>
                <th>Discounted</th>
                <th>Type</th>
                <th>ID Number</th>
                <th>ID Document</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($discountQueue as $row): ?>
                <?php
                  $discType = $row['discount_type'] ? strtoupper($row['discount_type']) : '—';
                  $orig = isset($row['discount_original_total']) ? (float)$row['discount_original_total'] : null;
                  $disc = isset($row['discounted_total']) ? (float)$row['discounted_total'] : null;
                  $statusLabel = $row['discount_status'] ?: 'Pending';
                ?>
                <tr data-discount-booking-id="<?php echo htmlspecialchars($row['booking_id']); ?>">
                  <td><?php echo htmlspecialchars($row['booking_id']); ?></td>
                  <td>
                    <?php echo htmlspecialchars($row['customer_name'] ?: 'N/A'); ?><br>
                    <span class="muted" style="font-size:0.8rem;"><?php echo htmlspecialchars($row['customer_email'] ?: ''); ?></span>
                  </td>
                  <td><?php echo htmlspecialchars($row['item_name']); ?></td>
                  <td>
                    <?php echo htmlspecialchars($row['show_date'] ?: ''); ?><br>
                    <span class="muted" style="font-size:0.8rem;"><?php echo htmlspecialchars($row['show_time'] ?: ''); ?></span>
                  </td>
                  <td>
                    <?php echo $orig !== null ? '₱' . number_format($orig, 2) : '—'; ?>
                  </td>
                  <td>
                    <?php echo $disc !== null ? '₱' . number_format($disc, 2) : '—'; ?>
                  </td>
                  <td><?php echo htmlspecialchars($discType); ?></td>
                  <td><?php echo htmlspecialchars($row['discount_id_number'] ?: '—'); ?></td>
                  <td>
                    <?php if (!empty($row['discount_id_path'])): ?>
                      <a href="<?php echo htmlspecialchars($row['discount_id_path']); ?>" target="_blank" class="admin-btn admin-btn--ghost" style="padding:4px 8px;font-size:0.8rem;">View ID</a>
                    <?php else: ?>
                      <span class="muted">No file</span>
                    <?php endif; ?>
                  </td>
                  <td><?php echo htmlspecialchars($statusLabel); ?></td>
                  <td style="white-space:nowrap;">
                    <button type="button" class="admin-btn admin-btn--ghost" style="padding:6px 10px;font-size:0.8rem;margin-right:4px;"
                            onclick="handleDiscountDecision('<?php echo htmlspecialchars($row['booking_id']); ?>','approve')">
                      Approve
                    </button>
                    <button type="button" class="admin-btn admin-btn--ghost" style="padding:6px 10px;font-size:0.8rem;"
                            onclick="handleDiscountDecision('<?php echo htmlspecialchars($row['booking_id']); ?>','reject')">
                      Reject
                    </button>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </section>
    </main>
  </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
  <script>
    const breakdownData = <?php echo json_encode($breakdown, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    const ctx = document.getElementById('salesChart');
    const barColors = ['#c79f5e', '#60a5fa', '#34d399', '#a78bfa', '#fb923c', '#f472b6', '#2dd4bf', '#fbbf24'];
    if (ctx && breakdownData.length) {
      const labels = breakdownData.map(row => row.item_name);
      const dataset = breakdownData.map(row => Number(row.total_quantity));
      const bg = breakdownData.map((_, i) => barColors[i % barColors.length]);
      new Chart(ctx, {
        type: 'bar',
        data: {
          labels,
          datasets: [{
            label: 'Tickets sold',
            data: dataset,
            backgroundColor: bg,
            borderRadius: 10,
            borderSkipped: false
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            y: {
              beginAtZero: true,
              grid: { color: 'rgba(255,255,255,0.06)' },
              ticks: { color: 'rgba(255,255,255,0.45)', font: { size: 11 } }
            },
            x: {
              grid: { display: false },
              ticks: { color: 'rgba(255,255,255,0.55)', maxRotation: 45, minRotation: 0, font: { size: 10 } }
            }
          },
          plugins: {
            legend: { display: false },
            tooltip: {
              backgroundColor: 'rgba(22,22,31,0.95)',
              titleColor: '#f4f4f8',
              bodyColor: 'rgba(255,255,255,0.85)',
              borderColor: 'rgba(255,255,255,0.08)',
              borderWidth: 1,
              padding: 12,
              cornerRadius: 12
            }
          }
        }
      });
    } else if (ctx) {
      const parent = ctx.parentElement;
      if (parent) {
        const empty = document.createElement('p');
        empty.className = 'muted';
        empty.style.padding = '2rem 0';
        empty.style.textAlign = 'center';
        empty.textContent = 'No ticket sales data yet — bookings will appear here.';
        parent.appendChild(empty);
      }
    }

    document.querySelectorAll('#adminEvents [data-action="toggle"]').forEach(button => {
      button.addEventListener('click', () => {
        const card = button.closest('.event-card');
        if (!card) return;
        const isInactive = card.classList.toggle('inactive');
        button.textContent = isInactive ? 'Activate' : 'Deactivate';
        card.style.opacity = isInactive ? '0.45' : '1';
      });
    });

    (function adminChrome() {
      const sidebar = document.getElementById('adminSidebar');
      const backdrop = document.getElementById('adminSidebarBackdrop');
      const menuBtn = document.getElementById('adminMenuBtn');
      function closeSidebar() {
        sidebar?.classList.remove('is-open');
        backdrop?.classList.remove('is-open');
        backdrop?.setAttribute('aria-hidden', 'true');
      }
      function openSidebar() {
        sidebar?.classList.add('is-open');
        backdrop?.classList.add('is-open');
        backdrop?.setAttribute('aria-hidden', 'false');
      }
      menuBtn?.addEventListener('click', () => {
        if (sidebar?.classList.contains('is-open')) closeSidebar();
        else openSidebar();
      });
      backdrop?.addEventListener('click', closeSidebar);
      document.querySelectorAll('.admin-nav a[href^="#"]').forEach(a => {
        a.addEventListener('click', () => {
          if (window.innerWidth <= 1023) closeSidebar();
        });
      });
      window.addEventListener('resize', () => {
        if (window.innerWidth > 1023) closeSidebar();
      });

      const search = document.getElementById('adminSectionSearch');
      const sections = document.querySelectorAll('.admin-main .admin-section');
      search?.addEventListener('input', function() {
        const q = this.value.trim().toLowerCase();
        sections.forEach(sec => {
          const blob = ((sec.getAttribute('data-admin-search') || '') + ' ' + (sec.querySelector('.admin-section-title')?.textContent || '')).toLowerCase();
          sec.classList.toggle('is-hidden', q.length > 0 && !blob.includes(q));
        });
      });

      const navLinks = document.querySelectorAll('.admin-nav a[href^="#"]');
      function setActive() {
        let current = 'overview';
        sections.forEach(sec => {
          const id = sec.id;
          if (!id) return;
          const r = sec.getBoundingClientRect();
          if (r.top <= 140) current = id;
        });
        navLinks.forEach(a => {
          const id = a.getAttribute('href')?.slice(1);
          a.classList.toggle('is-active', id === current);
        });
      }
      window.addEventListener('scroll', setActive, { passive: true });
      setActive();
    })();

    // Add Movie Form Handler
    document.getElementById('addMovieForm')?.addEventListener('submit', async function(e) {
      e.preventDefault();
      const formData = new FormData(this);
      formData.append('action', 'add');
      
      try {
        const response = await fetch('api/admin_movies.php', {
          method: 'POST',
          body: formData
        });
        
        const result = await response.json();
        if (result.success) {
          alert('Movie added successfully!');
          location.reload();
        } else {
          alert('Error: ' + (result.error || 'Failed to add movie'));
        }
      } catch (error) {
        alert('Error: ' + error.message);
      }
    });

    // Remove Movie Function
    function removeMovie(movieId, title) {
      if (!confirm(`Are you sure you want to remove "${title}"?`)) return;
      
      fetch('api/admin_movies.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'remove', movie_id: movieId })
      })
      .then(res => res.json())
      .then(result => {
        if (result.success) {
          document.querySelector(`[data-movie-id="${movieId}"]`)?.remove();
          alert('Movie removed successfully!');
        } else {
          alert('Error: ' + (result.error || 'Failed to remove movie'));
        }
      })
      .catch(error => alert('Error: ' + error.message));
    }

    // Schedule Management Functions
    document.getElementById('add-schedule-form')?.addEventListener('submit', async function(e) {
      e.preventDefault();
      
      const formData = new FormData();
      formData.append('action', 'add_schedule');
      formData.append('movie_id', document.getElementById('schedule-movie-id').value);
      formData.append('movie_title', document.getElementById('schedule-movie-id').options[document.getElementById('schedule-movie-id').selectedIndex].text);
      formData.append('theatre_type', document.getElementById('schedule-theatre-type').value);
      formData.append('cinema_hall', document.getElementById('schedule-cinema-hall').value);
      formData.append('show_date', document.getElementById('schedule-show-date').value);
      formData.append('show_time', document.getElementById('schedule-show-time').value);
      formData.append('end_time', document.getElementById('schedule-end-time').value);
      formData.append('price', document.getElementById('schedule-price').value);
      
      try {
        const response = await fetch('api/admin_schedules.php', {
          method: 'POST',
          body: formData
        });
        
        const result = await response.json();
        if (result.success) {
          alert('Schedule added successfully!');
          clearScheduleForm();
          loadSchedules();
        } else {
          alert('Error: ' + (result.error || 'Failed to add schedule'));
        }
      } catch (error) {
        alert('Error: ' + error.message);
      }
    });

    function clearScheduleForm() {
      document.getElementById('schedule-movie-id').value = '';
      document.getElementById('schedule-theatre-type').value = '';
      document.getElementById('schedule-cinema-hall').value = '';
      document.getElementById('schedule-show-date').value = '';
      document.getElementById('schedule-show-time').value = '';
      document.getElementById('schedule-end-time').value = '';
      document.getElementById('schedule-price').value = '';
    }

    function updateDefaultPrice(select) {
      const selectedOption = select.options[select.selectedIndex];
      const price = selectedOption.getAttribute('data-price');
      if (price) {
        document.getElementById('schedule-price').value = price;
      }
    }

    async function loadSchedules() {
      try {
        const response = await fetch('api/admin_schedules.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ action: 'get_all_schedules' })
        });
        
        const result = await response.json();
        if (result.success) {
          const tbody = document.getElementById('schedules-list-body');
          tbody.innerHTML = '';
          
          result.schedules.forEach(schedule => {
            const row = document.createElement('tr');
            row.innerHTML = `
              <td>${schedule.movie_title}</td>
              <td><span style="padding: 2px 8px; border-radius: 4px; font-size: 0.8rem; text-transform: uppercase;">${schedule.theatre_type}</span></td>
              <td>${schedule.cinema_hall}</td>
              <td>${schedule.show_date}</td>
              <td>${schedule.show_time}</td>
              <td>₱${parseFloat(schedule.price).toFixed(2)}</td>
              <td>
                <button class="admin-btn admin-btn--ghost" onclick="removeSchedule(${schedule.id}, '${schedule.movie_title}')" style="padding: 4px 8px; font-size: 0.8rem;">Remove</button>
              </td>
            `;
            tbody.appendChild(row);
          });
        }
      } catch (error) {
        console.error('Failed to load schedules:', error);
      }
    }

    async function removeSchedule(scheduleId, movieTitle) {
      if (!confirm(`Are you sure you want to remove the schedule for "${movieTitle}"?`)) return;
      
      try {
        const response = await fetch('api/admin_schedules.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ action: 'remove_schedule', schedule_id: scheduleId })
        });
        
        const result = await response.json();
        if (result.success) {
          alert('Schedule removed successfully!');
          loadSchedules();
        } else {
          alert('Error: ' + (result.error || 'Failed to remove schedule'));
        }
      } catch (error) {
        alert('Error: ' + error.message);
      }
    }

    // Load schedules when page loads
    document.addEventListener('DOMContentLoaded', loadSchedules);

    // Refund decision handler
    async function handleRefundDecision(bookingId, decision) {
      const label = decision === 'approve' ? 'approve this refund' : 'reject this refund';
      if (!confirm(`Are you sure you want to ${label}?\n\nBooking ID: ${bookingId}`)) return;
      try {
        const res = await fetch('api/admin_refunds.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ bookingId, decision })
        });
        const data = await res.json();
        if (!res.ok || !data.success) {
          alert('Error: ' + (data.error || 'Unable to process refund decision.'));
          return;
        }
        alert('Refund decision saved.');
        location.reload();
      } catch (err) {
        alert('Error: ' + err.message);
      }
    }

    // Discount decision handler
    async function handleDiscountDecision(bookingId, decision) {
      const actionLabel = decision === 'approve' ? 'approve this discount' : 'reject this discount';
      if (!confirm(`Are you sure you want to ${actionLabel}?\n\nBooking ID: ${bookingId}`)) return;
      try {
        const res = await fetch('api/admin_discounts.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ bookingId, decision })
        });
        const data = await res.json();
        if (!res.ok || !data.success) {
          alert('Error: ' + (data.error || 'Unable to update discount status.'));
          return;
        }
        alert('Discount decision saved.');
        location.reload();
      } catch (err) {
        alert('Error: ' + err.message);
      }
    }

    // Save Trailer URL — posts directly to this page's own PHP handler
    async function saveTrailerUrl(movieId) {
      const input = document.getElementById('trailer-input-' + movieId);
      if (!input) return;
      const url = input.value.trim();
      try {
        const res = await fetch('admin-dashboard.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ action: 'update_trailer', movie_id: movieId, trailer_url: url })
        });
        const data = await res.json();
        if (data.success) {
          alert('Trailer URL saved! Refresh the homepage to see it working.');
        } else {
          alert('Error: ' + (data.error || 'Failed to save.'));
        }
      } catch (err) {
        alert('Error: ' + err.message);
      }
    }

    // Update Movie Section
    async function updateSection(movieId, section) {
      const statusEl = document.getElementById('section-status-' + movieId);
      try {
        const res = await fetch('admin-dashboard.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ action: 'update_section', movie_id: movieId, section: section })
        });
        const data = await res.json();
        if (statusEl) {
          statusEl.style.display = 'block';
          if (data.success) {
            statusEl.textContent = '✓ Saved';
            statusEl.style.color = '#4cdead';
          } else {
            statusEl.textContent = '✗ ' + (data.error || 'Failed');
            statusEl.style.color = '#f97373';
          }
          setTimeout(() => { statusEl.style.display = 'none'; }, 2500);
        }
      } catch (err) {
        if (statusEl) { statusEl.textContent = '✗ Error'; statusEl.style.color = '#f97373'; statusEl.style.display = 'block'; }
      }
    }

  </script>

  <script>
    // ── Ctrl+Shift+L → Logout ─────────────────────────────────────────
    document.addEventListener('keydown', function(e) {
      if (e.ctrlKey && e.shiftKey && (e.key === 'L' || e.key === 'l') && !e.altKey) {
        e.preventDefault();
        window.location.href = 'logout.php';
      }
    });

    // ── Admin Notifications System ────────────────────────────────────
    function toggleAdminNotifications() {
      const panel = document.getElementById('adminNotifPanel');
      if (panel.style.display === 'none') {
        panel.style.display = 'block';
        loadAdminNotifications();
      } else {
        panel.style.display = 'none';
      }
    }

    async function loadAdminNotifications() {
      const list = document.getElementById('adminNotifList');
      const badge = document.getElementById('adminBellBadge');
      
      try {
        const res = await fetch('api/notifications.php?action=get_notifications');
        const notifications = await res.json();
        
        if (notifications.length > 0) {
          badge.textContent = notifications.length;
          badge.style.display = 'flex';
          
          list.innerHTML = notifications.map(n => `
            <div style="padding: 12px; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 0.85rem; position: relative;">
              <div style="font-weight: 600; color: #c79f5e; margin-bottom: 4px;">${n.title}</div>
              <div style="color: rgba(255,255,255,0.8); line-height: 1.4;">${n.message}</div>
              ${n.action_text ? `<button onclick="window.location.href='${n.action_url}'" style="margin-top: 8px; padding: 4px 10px; border-radius: 4px; border: 1px solid #c79f5e; background: transparent; color: #c79f5e; font-size: 0.75rem; cursor: pointer;">${n.action_text}</button>` : ''}
              <button onclick="dismissAdminNotification('${n.id}')" style="position: absolute; top: 12px; right: 12px; background: none; border: none; color: rgba(255,255,255,0.3); cursor: pointer;">×</button>
            </div>
          `).join('');
        } else {
          badge.style.display = 'none';
          list.innerHTML = '<div style="padding: 20px; text-align: center; color: rgba(255,255,255,0.3); font-size: 0.85rem;">No new notifications</div>';
        }
      } catch (err) {
        console.error('Failed to load notifications:', err);
        list.innerHTML = '<div style="padding: 20px; text-align: center; color: #f97373; font-size: 0.85rem;">Failed to load notifications</div>';
      }
    }

    async function dismissAdminNotification(id) {
      try {
        const formData = new FormData();
        formData.append('action', 'mark_read');
        formData.append('notification_id', id);
        
        await fetch('api/notifications.php', {
          method: 'POST',
          body: formData
        });
        loadAdminNotifications();
      } catch (err) {
        console.error('Failed to dismiss notification:', err);
      }
    }

    // Initial load and periodic refresh
    loadAdminNotifications();
    setInterval(loadAdminNotifications, 60000);
  </script>
</body>
</html>