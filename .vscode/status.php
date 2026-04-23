<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Status | CineFlix</title>
  <link rel="stylesheet" href="status.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <style>
    .top-nav ul { display:flex; align-items:center; gap:14px; }
    .user-menu { position: relative; display:flex; align-items:center; }
    .avatar { width: 48px; height: 48px; border-radius: 50%; background: linear-gradient(135deg,#242424,#3a3a3a); border:1px solid rgba(255,255,255,.15); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:14px; text-transform:uppercase; box-shadow:0 4px 12px rgba(0,0,0,.25); cursor:pointer; }
    .dropdown { position:absolute; right:0; top:56px; background:#111; color:#f2f2f2; border:1px solid #2a2a2a; border-radius:10px; box-shadow:0 10px 24px rgba(0,0,0,.35); display:none; min-width:200px; z-index:1000; overflow:hidden; }
    .dropdown a { display:block; padding:12px 14px; color:#eaeaea; text-decoration:none; }
    .dropdown a:hover { background:#1b1b1b; }
  </style>
  <script>
<<<<<<< HEAD
=======
    // Set isLoggedIn based on PHP session
    var isLoggedIn = <?php echo !empty($_SESSION['user_id']) ? 'true' : 'false'; ?>;
    // Set current user ID from PHP session
    var currentUserId = <?php echo !empty($_SESSION['user_id']) ? json_encode($_SESSION['user_id']) : 'null'; ?>;
    
>>>>>>> 39461e1 (bago)
    document.addEventListener('DOMContentLoaded', function(){
      var avatar = document.getElementById('avatarBtn');
      var menu = document.getElementById('userDropdown');
      if (avatar && menu) {
        avatar.addEventListener('click', function(){
          menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
        });
        document.addEventListener('click', function(e){
          if (!avatar.contains(e.target) && !menu.contains(e.target)) { menu.style.display = 'none'; }
        });
      }
    });
  </script>
</head>

<body class="has-background">
  <div class="background-blur"></div>
  
  <header class="site-header">
    <a class="logo" href="homepage.php">
      <img src="logo/newlogo1.png" alt="CineFlix Logo">
    </a>
    <nav class="top-nav">
      <ul>
        <li><a class="nav-btn" href="status.php">Status</a></li>
        <?php if (!empty($_SESSION['user_id'])): ?>
          <li class="user-menu">
            <?php $initial = strtoupper(substr((string)($_SESSION['username'] ?? $_SESSION['user_name'] ?? 'U'), 0, 1)); ?>
            <div id="avatarBtn" class="avatar"><?php echo $initial; ?></div>
            <div id="userDropdown" class="dropdown">
              <a href="#">My Details</a>
              <a href="logout.php">Logout</a>
            </div>
          </li>
        <?php else: ?>
          <li><a class="nav-btn" href="login.html">Login</a></li>
        <?php endif; ?>
      </ul>
    </nav>
  </header>

  <main>
    <div class="container">
      <h1 class="page-title">Booking Status</h1>
      <div class="card">
        <div class="tabs">
          <div class="tabs-list">
            <button class="tab-trigger active" data-tab="paid">Paid Tickets</button>
            <button class="tab-trigger" data-tab="cancelled">Cancelled/Refunded</button>
          </div>

          <div class="tab-content active" id="paid">
<<<<<<< HEAD
            <?php if (empty($_SESSION['user_id'])): ?>
              <div class="empty-state" style="display: block;">
                <p>Please log in to view your ticket history.</p>
                <a href="login.html" class="btn btn-primary">Login</a>
              </div>
            <?php else: ?>
              <div id="paid-tickets-container"></div>
              <div class="empty-state" id="paid-empty" style="display: none;">
                <p>No paid tickets found.</p>
                <a href="homepage.php" class="btn btn-primary">Browse Movies</a>
              </div>
            <?php endif; ?>
          </div>

          <div class="tab-content" id="cancelled">
            <?php if (empty($_SESSION['user_id'])): ?>
              <div class="empty-state" style="display: block;">
                <p>Please log in to view your cancelled/refunded tickets.</p>
                <a href="login.html" class="btn btn-primary">Login</a>
              </div>
            <?php else: ?>
              <div id="cancelled-tickets-container"></div>
              <div class="empty-state" id="cancelled-empty" style="display: none;">
                <p>No cancelled or refunded tickets found.</p>
              </div>
            <?php endif; ?>
=======
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
>>>>>>> 39461e1 (bago)
          </div>
        </div>
      </div>
    </div>
  </main>

  <div class="ticket-modal" id="ticket-modal" style="display:none;">
    <div class="ticket-modal-backdrop" id="ticket-modal-backdrop"></div>
    <div class="ticket-modal-card" role="dialog" aria-modal="true" aria-labelledby="ticket-modal-title">
      <div class="ticket-modal-header">
        <h3 id="ticket-modal-title">Your Ticket</h3>
        <span class="ticket-modal-id" id="ticket-modal-id"></span>
      </div>
      <div class="ticket-modal-body">
        <div class="ticket-modal-info">
          <div><span class="label">Movie</span><span id="tm-movie"></span></div>
          <div><span class="label">Date & Time</span><span id="tm-datetime"></span></div>
          <div><span class="label">Cinema</span><span id="tm-cinema"></span></div>
          <div><span class="label">Seats</span><span id="tm-seats"></span></div>
<<<<<<< HEAD
          <div><span class="label">Tickets</span><span id="tm-qty"></span></div>
          <div id="tm-addons-row" style="display:none;"><span class="label">Food & Drinks</span><span id="tm-addons"></span></div>
          <div class="total"><span class="label">Total</span><span id="tm-total"></span></div>
        </div>
        <div class="ticket-modal-payment" id="tm-payment-section" style="display:none; margin-top:20px; padding-top:20px; border-top:1px solid rgba(255,255,255,0.1);">
          <h4 style="color:#c79f5e; margin-bottom:12px; font-size:16px;">Payment Information</h4>
          <div><span class="label">Cardholder</span><span id="tm-cardholder"></span></div>
          <div><span class="label">Card Number</span><span id="tm-cardnumber"></span></div>
          <div><span class="label">Expiry Date</span><span id="tm-expiry"></span></div>
          <div><span class="label">Payment Method</span><span id="tm-paymentmethod"></span></div>
          <div><span class="label">Payment Date</span><span id="tm-paymentdate"></span></div>
        </div>
        <div class="ticket-modal-qr"><div id="ticket-modal-qrcode"></div></div>
=======
          <div id="tm-addons-row" style="display: none;"><span class="label">Food & Drinks</span><span id="tm-addons"></span></div>
          <div><span class="label">Tickets</span><span id="tm-qty"></span></div>
          <div class="total"><span class="label">Total</span><span id="tm-total"></span></div>
          
          <!-- Payment Information Section -->
          <div id="tm-payment-section" class="ticket-modal-payment" style="display: none;">
            <h4>Payment Information</h4>
            <div><span class="label">Cardholder Name</span><span id="tm-cardholder"></span></div>
            <div><span class="label">Card Number</span><span id="tm-cardnumber"></span></div>
            <div><span class="label">Expiry Date</span><span id="tm-expiry"></span></div>
            <div><span class="label">Payment Method</span><span id="tm-paymentmethod"></span></div>
            <div><span class="label">Payment Date</span><span id="tm-paymentdate"></span></div>
          </div>
        </div>
        <div class="ticket-modal-qr">
          <div id="ticket-modal-qrcode"></div>
          <p style="color: rgba(255,255,255,0.7); font-size: 0.85rem; margin-top: 8px; text-align: center;">Scan QR Code for Entry</p>
        </div>
>>>>>>> 39461e1 (bago)
      </div>
      <div class="ticket-modal-actions">
        <button class="btn btn-secondary" id="ticket-modal-print">Print</button>
        <button class="btn btn-primary" id="ticket-modal-close-2">Close</button>
      </div>
    </div>
  </div>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<<<<<<< HEAD
  <script>
    // Pass login status to JavaScript
    const isLoggedIn = <?php echo !empty($_SESSION['user_id']) ? 'true' : 'false'; ?>;
    const userId = <?php echo !empty($_SESSION['user_id']) ? json_encode($_SESSION['user_id']) : 'null'; ?>;
  </script>
=======
>>>>>>> 39461e1 (bago)
  <script src="status.js"></script>
</body>
</html>


