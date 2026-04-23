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
  <title>Status | CineFlix</title>
  <link rel="stylesheet" href="common.css">
  <link rel="stylesheet" href="css/header-nav.css">
  <link rel="stylesheet" href="status.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <style>
    /* ── Fixed top navbar — matches booking.php ───────────────────── */
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
    main { padding-top: 100px !important; }
    /* Hide Status nav link */
    a[href="status.php"],
    .nav-link[href="status.php"] {
      display: none !important;
    }
  </style>
  <script>
    // Set isLoggedIn based on PHP session
    var isLoggedIn = <?php echo !empty($_SESSION['user_id']) ? 'true' : 'false'; ?>;
    // Set current user ID from PHP session
    var currentUserId = <?php echo !empty($_SESSION['user_id']) ? json_encode($_SESSION['user_id']) : 'null'; ?>;
    
  </script>
  <?php require __DIR__ . '/includes/partials/site_header_scripts.php'; ?>
</head>

<body class="has-background">
  <div class="background-blur"></div>
  
  <?php
  $headerShowSearch = false;
  require __DIR__ . '/includes/partials/site_header.php';
  ?>

  <main>
    <div class="container">
      <h1 class="page-title">Booking Status</h1>
      <?php if (empty($_SESSION['user_id'])): ?>
        <div class="card">
          <div class="empty-state" id="login-required-message">
            <p>Please log in.</p>
            <a href="login.html" class="btn btn-primary">Login</a>
          </div>
        </div>
      <?php else: ?>
        <div class="card">
          <div class="tabs">
            <div class="tabs-list">
              <button class="tab-trigger active" data-tab="paid">Paid Tickets</button>
              <button class="tab-trigger" data-tab="cancelled">Cancelled/Refunded</button>
            </div>

            <div class="tab-content active" id="paid">
              <div id="paid-tickets-container"></div>
              <div class="empty-state" id="paid-empty" style="display: none;">
                <p>No paid tickets found.</p>
                <a href="homepage.php" class="btn btn-primary">Browse Movies</a>
              </div>
            </div>

            <div class="tab-content" id="cancelled">
              <div id="cancelled-tickets-container">
                <!-- Cancelled tickets will be loaded from localStorage -->
              </div>
              
              <div class="empty-state" id="cancelled-empty" style="display: none;">
                <p>No cancelled or refunded tickets found.</p>
              </div>
              
              <div class="cta-card">
                <h3 class="cta-title">Need to Cancel a Booking?</h3>
                <p class="cta-description">If you need to cancel your booking, you can request a refund up to 24 hours before the showtime. Refunds are typically processed within 3-5 business days.</p>
                <button class="btn btn-danger">Request Cancellation</button>
              </div>
            </div>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </main>

  <div class="ticket-modal" id="ticket-modal" style="display:none;">
    <div class="ticket-modal-backdrop" id="ticket-modal-backdrop"></div>
    <div class="ticket-modal-card ticket-modal-receipt" role="dialog" aria-modal="true" aria-labelledby="ticket-modal-title">
      <div class="ticket-modal-header">
        <h3 id="ticket-modal-title">Your Ticket</h3>
        <div class="ticket-modal-actions">
          <button class="btn btn-secondary" id="ticket-modal-download">Download Ticket</button>
          <button class="btn btn-primary" id="ticket-modal-close-2">Close</button>
        </div>
      </div>
      <div class="ticket-modal-body" id="ticket-modal-receipt-body">
        <div class="receipt-outer">
          <p class="receipt-banner-title">THIS IS YOUR TICKET</p>
          <p class="receipt-banner-sub">Please show it on your phone when you arrive at the venue.</p>
          <div class="ticket-receipt">
            <div class="ticket-receipt-top">
              <div class="ticket-receipt-logo">CineFlix</div>
              <div class="ticket-receipt-checkin">
                <span class="ticket-label">CHECK-IN CODE</span>
                <span class="ticket-value ticket-checkin-code" id="tm-booking-id"></span>
              </div>
            </div>
            <div class="ticket-receipt-qr">
              <div id="ticket-modal-qrcode"></div>
            </div>
            <div class="ticket-receipt-details">
              <div class="ticket-detail-row">
                <span class="ticket-label">MOVIE NAME</span>
                <span class="ticket-value" id="tm-movie"></span>
              </div>
              <div class="ticket-detail-row">
                <span class="ticket-label">DATE AND TIME</span>
                <span class="ticket-value" id="tm-datetime"></span>
              </div>
              <div class="ticket-detail-row">
                <span class="ticket-label">SEATS</span>
                <span class="ticket-value" id="tm-seats"></span>
              </div>
              <div class="ticket-detail-row">
                <span class="ticket-label">NUMBER OF TICKETS</span>
                <span class="ticket-value" id="tm-qty"></span>
              </div>
              <div class="ticket-detail-row" id="tm-parking-row" style="display:none;">
                <span class="ticket-label">PARKING</span>
                <span class="ticket-value" id="tm-parking"></span>
              </div>
              <div class="ticket-detail-row" id="tm-addons-row" style="display:none;">
                <span class="ticket-label">FOOD & DRINKS</span>
                <span class="ticket-value" id="tm-addons"></span>
              </div>
              <div class="ticket-detail-row">
                <span class="ticket-label">PAYMENT METHOD</span>
                <span class="ticket-value" id="tm-payment-method"></span>
              </div>
              <div class="ticket-detail-row" id="tm-ticket-price-row" style="display:none;">
                <span class="ticket-label">TICKET PRICE</span>
                <span class="ticket-value">P<span id="tm-ticket-price"></span></span>
              </div>
              <div class="ticket-detail-row" id="tm-addons-total-row" style="display:none;">
                <span class="ticket-label">ADD-ONS TOTAL</span>
                <span class="ticket-value">P<span id="tm-addons-total"></span></span>
              </div>
              <div class="ticket-detail-row" id="tm-parking-fee-row" style="display:none;">
                <span class="ticket-label">PARKING FEE</span>
                <span class="ticket-value">P<span id="tm-parking-fee"></span></span>
              </div>
              <div class="ticket-detail-row" id="tm-discount-block" style="display:none;">
                <span class="ticket-label">SUBTOTAL (BEFORE DISCOUNT)</span>
                <span class="ticket-value">P<span id="tm-original-total"></span></span>
              </div>
              <div class="ticket-detail-row" id="tm-discount-line" style="display:none;">
                <span class="ticket-label">PWD / SENIOR DISCOUNT (-20%)</span>
                <span class="ticket-value receipt-discount-amount">-P<span id="tm-discount-amount"></span></span>
              </div>
              <div id="tm-discount-pending" style="display:none; padding: 12px 16px; margin: 8px 0; border-radius: 8px; background: rgba(199,159,94,0.12); border: 1px solid rgba(199,159,94,0.3);">
                <p style="margin:0 0 4px 0; font-size: 0.82rem; font-weight: 700; color: #c79f5e;">⏳ DISCOUNT PENDING ADMIN APPROVAL</p>
                <p style="margin:0; font-size: 0.75rem; color: rgba(0,0,0,0.65); line-height: 1.4;">Your PWD / Senior discount request has been submitted and is awaiting admin approval. You have been charged the full price for now. Once approved, the overpaid amount can be claimed at the cashier.</p>
              </div>
              <div id="tm-discount-approved-notice" style="display:none; padding: 12px 16px; margin: 8px 0; border-radius: 8px; background: rgba(10,124,66,0.1); border: 1px solid rgba(10,124,66,0.3);">
                <p style="margin:0 0 4px 0; font-size: 0.82rem; font-weight: 700; color: #0a7c42;">✅ DISCOUNT APPROVED</p>
                <p style="margin:0; font-size: 0.75rem; color: rgba(0,0,0,0.65); line-height: 1.4;">Your PWD / Senior discount has been approved! Since you paid the full price, the overpaid amount of ₱<span id="tm-overpaid-amount"></span> can be claimed at the cashier.</p>
              </div>
              <div class="ticket-detail-row">
                <span class="ticket-label">TOTAL PAID</span>
                <span class="ticket-value">P<span id="tm-total"></span></span>
              </div>
            </div>
            <div class="ticket-receipt-perforation"></div>
            <div class="ticket-receipt-stub">
              <div class="stub-left">
                <div class="ticket-detail-row">
                  <span class="ticket-label">CUSTOMER NAME</span>
                  <span class="ticket-value stub-value" id="tm-customer-name">—</span>
                </div>
                <div class="ticket-detail-row">
                  <span class="ticket-label">TOTAL PRICE</span>
                  <span class="ticket-value stub-value">P<span id="tm-total-stub"></span></span>
                </div>
              </div>
              <div class="stub-right">
                <div class="ticket-detail-row">
                  <span class="ticket-label">SEAT NUMBER</span>
                  <span class="ticket-value stub-value stub-seats" id="tm-seat-stub"></span>
                </div>
              </div>
            </div>
            <div class="ticket-receipt-footer">CINEFLIX</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
  <script src="status.js"></script>
</body>
</html>