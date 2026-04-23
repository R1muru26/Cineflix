<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Book Tickets | CineFlix</title>
  <link rel="stylesheet" href="booking.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <style>
    .top-nav ul { display:flex; align-items:center; gap:14px; }
    .user-menu { position: relative; display:flex; align-items:center; }
    .avatar { width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg,#242424,#3a3a3a); border:1px solid rgba(255,255,255,.15); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:12px; text-transform:uppercase; box-shadow:0 4px 12px rgba(0,0,0,.25); cursor:pointer; }
    .dropdown { position:absolute; right:0; top:46px; background:#111; color:#f2f2f2; border:1px solid #2a2a2a; border-radius:10px; box-shadow:0 10px 24px rgba(0,0,0,.35); display:none; min-width:200px; z-index:1000; overflow:hidden; }
    .dropdown a { display:block; padding:12px 14px; color:#eaeaea; text-decoration:none; }
    .dropdown a:hover { background:#1b1b1b; }
  </style>
  <script>
<<<<<<< HEAD
=======
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
        <?php endif; ?>
      </ul>
    </nav>
  </header>

  <main>
    <div class="container">
      <!-- Step 1: Schedule Selection -->
      <div class="booking-step active" id="step-schedule">
        <h1 class="page-title" id="movie-title">Select Schedule</h1>
        
        <div class="card">
          <div class="movie-info">
            <div class="movie-poster-small" id="movie-poster"></div>
            <div class="movie-details">
              <h2 id="selected-movie">Movie Title</h2>
              <p id="movie-meta">Genre • Duration</p>
            </div>
          </div>

          <div class="schedule-section">
            <h3 class="section-subtitle">Choose Date & Time</h3>
            <div class="date-selector">
              <input type="date" id="booking-date" class="date-input">
            </div>

            <div class="schedule-cards" id="schedule-cards">
              <!-- Schedule cards will be generated here -->
            </div>
          </div>

          <button class="btn btn-primary btn-large" id="continue-to-quantity" disabled>Continue to Select Quantity</button>
        </div>
      </div>

      <!-- Step 2: Quantity Selection -->
      <div class="booking-step" id="step-quantity">
        <h1 class="page-title">Select Quantity</h1>
        
        <div class="card">
          <div class="booking-summary-small">
            <p><strong>Movie:</strong> <span id="summary-movie-1"></span></p>
            <p><strong>Schedule:</strong> <span id="summary-schedule-1"></span></p>
          </div>

          <div class="quantity-section">
            <h3 class="section-subtitle">Number of Tickets</h3>
            <div class="quantity-selector">
              <button class="quantity-btn" id="decrease-qty">−</button>
              <span class="quantity-display" id="quantity">1</span>
              <button class="quantity-btn" id="increase-qty">+</button>
            </div>
            <p class="quantity-note">Maximum 10 tickets per booking</p>
          </div>

          <div class="button-group">
            <button class="btn btn-secondary" id="back-to-schedule">Back</button>
            <button class="btn btn-primary" id="continue-to-seats">Continue to Select Seats</button>
          </div>
        </div>
      </div>

      <!-- Step 3: Seat Selection -->
      <div class="booking-step" id="step-seats">
        <h1 class="page-title">Select Seats</h1>
        
        <div class="card">
          <div class="booking-summary-small">
            <p><strong>Movie:</strong> <span id="summary-movie-2"></span></p>
            <p><strong>Schedule:</strong> <span id="summary-schedule-2"></span></p>
            <p><strong>Tickets:</strong> <span id="summary-quantity"></span></p>
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
            </div>

            <div class="selected-seats-info">
              <p><strong>Selected Seats:</strong> <span id="selected-seats-display">None</span></p>
              <p class="price-display"><strong>Total:</strong> ₱<span id="total-price">0</span></p>
            </div>
          </div>

          <div class="button-group">
            <button class="btn btn-secondary" id="back-to-quantity">Back</button>
            <button class="btn btn-primary" id="continue-to-addons" disabled>Continue</button>
          </div>
        </div>
      </div>

      <!-- Step 4: Food & Drinks (Optional) -->
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

          <div id="addons-list" class="schedule-cards">
            <!-- Addon items will be rendered here -->
          </div>

          <div class="selected-seats-info">
            <p><strong>Food & Drinks Total:</strong> ₱<span id="addons-total">0.00</span></p>
          </div>

          <div class="button-group">
            <button class="btn btn-secondary" id="skip-addons">No, continue to Payment</button>
            <button class="btn btn-primary" id="continue-to-payment">Continue to Payment</button>
          </div>
        </div>
      </div>

      <!-- Step 4: Payment -->
      <div class="booking-step" id="step-payment">
        <h1 class="page-title">Payment</h1>
        
        <div class="card">
          <div class="booking-summary">
            <h3>Booking Summary</h3>
            <div class="summary-details">
              <p><strong>Movie:</strong> <span id="summary-movie-3"></span></p>
              <p><strong>Schedule:</strong> <span id="summary-schedule-3"></span></p>
              <p><strong>Seats:</strong> <span id="summary-seats"></span></p>
              <p><strong>Tickets:</strong> <span id="summary-quantity-2"></span></p>
              <p><strong>Food & Drinks:</strong> <span id="summary-addons">None</span></p>
              <p class="summary-total"><strong>Total Amount:</strong> ₱<span id="summary-price"></span></p>
            </div>
          </div>

          <div class="payment-section">
            <h3 class="section-subtitle">Payment Information</h3>
            <form id="payment-form">
              <div class="form-group">
                <label for="cardholder-name">Cardholder Name</label>
                <input type="text" id="cardholder-name" class="form-input" required>
              </div>
              
              <div class="form-group">
                <label for="card-number">Card Number</label>
                <input type="text" id="card-number" class="form-input" placeholder="1234 5678 9012 3456" maxlength="19" required>
              </div>
              
              <div class="form-row">
                <div class="form-group">
                  <label for="expiry-date">Expiry Date</label>
                  <input type="text" id="expiry-date" class="form-input" placeholder="MM/YY" maxlength="5" required>
                </div>
                
                <div class="form-group">
                  <label for="cvv">CVV</label>
                  <input type="text" id="cvv" class="form-input" placeholder="123" maxlength="3" required>
                </div>
              </div>
            </form>
          </div>

          <div class="button-group">
            <button class="btn btn-secondary" id="back-to-seats">Back</button>
            <button class="btn btn-primary" id="complete-payment">Pay ₱<span id="payment-amount">0</span></button>
          </div>
        </div>
      </div>

      <!-- Step 5: Confirmation -->
      <div class="booking-step" id="step-confirmation">
        <div class="confirmation-card">
          <div class="success-animation">
            <div class="success-icon">✓</div>
          </div>
          
          <h1 class="confirmation-title">Congratulations!</h1>
          <p class="confirmation-subtitle">Your booking is now confirmed.</p>

          <div class="ticket-receipt">
            <div class="receipt-header">
              <h3>CineFlix</h3>
              <p class="receipt-number">Booking #<span id="receipt-booking-id"></span></p>
            </div>

            <div class="receipt-qr">
              <div id="qr-code"></div>
            </div>

            <div class="receipt-details">
              <div class="receipt-row">
                <span>Movie:</span>
                <span id="receipt-movie"></span>
              </div>
              <div class="receipt-row">
                <span>Date & Time:</span>
                <span id="receipt-schedule"></span>
              </div>
              <div class="receipt-row">
                <span>Seats:</span>
                <span id="receipt-seats"></span>
              </div>
              <div class="receipt-row">
                <span>Tickets:</span>
                <span id="receipt-quantity"></span>
              </div>
              <div class="receipt-row total">
                <span>Total Paid:</span>
                <span>₱<span id="receipt-price"></span></span>
              </div>
            </div>
          </div>

          <div class="button-group">
            <button class="btn btn-primary" onclick="window.location.href='status.php'">View My Bookings</button>
            <button class="btn btn-secondary" onclick="window.location.href='homepage.php'">Back to Home</button>
          </div>
        </div>
      </div>
    </div>
  </main>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<<<<<<< HEAD
=======
  
>>>>>>> 39461e1 (bago)
  <script src="booking.js"></script>
</body>
</html>


