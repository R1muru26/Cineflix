// Booking data storage
let bookingData = {
  movie: '',
  moviePoster: '',
  schedule: '',
  cinema: '',
  date: '',
  quantity: 1,
  seats: [],
  price: 425, // Price per ticket in PHP
  ticketTotal: 0,
  addons: [], // [{id, name, price, qty}]
  addonsTotal: 0,
  total: 0,
  bookingId: ''
};

// Seat layout constants
const SEAT_ROWS = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
const SEATS_PER_ROW = 10;
function getTotalSeats() { return SEAT_ROWS.length * SEATS_PER_ROW; }

// Initialize booking page
document.addEventListener('DOMContentLoaded', function() {
  // Get movie data from URL parameters
  const urlParams = new URLSearchParams(window.location.search);
  const movieTitle = urlParams.get('movie') || 'Spider-Man: No Way Home';
  const moviePoster = urlParams.get('poster') || '';
  
  bookingData.movie = movieTitle;
  bookingData.moviePoster = moviePoster;
  
  // Update movie info
  updateMovieInfo();
  
  // Set default date to today
  const today = new Date().toISOString().split('T')[0];
  document.getElementById('booking-date').value = today;
  document.getElementById('booking-date').min = today;
  
  // Generate schedule cards
  generateSchedules();
  
  // Generate seats
  generateSeats();
  
  // Setup event listeners
  setupEventListeners();
});

function updateMovieInfo() {
  // Update all movie title references
  document.getElementById('selected-movie').textContent = bookingData.movie;
  document.querySelectorAll('[id^="summary-movie"]').forEach(el => {
    el.textContent = bookingData.movie;
  });
  
  // Update poster if available
  if (bookingData.moviePoster) {
    const posterUrl = decodeURIComponent(bookingData.moviePoster);
    const posterEl = document.getElementById('movie-poster');
    if (posterEl) {
      posterEl.style.backgroundImage = `url("${posterUrl}")`;
      if (!posterEl.querySelector('img')) {
        const img = document.createElement('img');
        img.src = posterUrl;
        img.alt = bookingData.movie + ' poster';
        img.style.width = '100%';
        img.style.height = '100%';
        img.style.objectFit = 'cover';
        img.style.borderRadius = '8px';
        img.setAttribute('aria-hidden', 'true');
        posterEl.appendChild(img);
      }
    }
  }
  
  // Set meta information
  document.getElementById('movie-meta').textContent = 'Action • 2h 30m';
}

function generateSchedules() {
  const schedulesContainer = document.getElementById('schedule-cards');
  const schedules = [
    { time: '2:30 PM - 4:25 PM', cinema: 'Cinema: 1D' },
    { time: '2:50 PM - 4:45 PM', cinema: 'Cinema: 2D' },
    { time: '5:15 PM - 7:10 PM', cinema: 'Cinema: 1D' },
    { time: '6:25 PM - 8:30 PM', cinema: 'Cinema: 3D' },
    { time: '8:00 PM - 9:55 PM', cinema: 'Cinema: IMAX' },
    { time: '9:30 PM - 11:25 PM', cinema: 'Cinema: 2D' }
  ];

  // Replace any "1D" text with "2D" in all schedules before processing
  schedules.forEach(schedule => {
    if (schedule.cinema && schedule.cinema.includes('1D')) {
      schedule.cinema = schedule.cinema.replace(/1D/g, '2D');
    }
  });

  const date = document.getElementById('booking-date').value;
  const totalSeats = getTotalSeats();
  
  // Helper function to check if showtime is within 30 minutes
  function isShowtimeWithin30Minutes(dateString, timeString) {
    const now = new Date();
    const [startTimeStr] = timeString.split(' - ');

    // Parse the date and time
    const showtimeDateTime = new Date(`${dateString} ${startTimeStr}`);

    // Calculate the difference in minutes
    const diffMinutes = (showtimeDateTime.getTime() - now.getTime()) / (1000 * 60);

    // Check if the showtime is within 30 minutes (or already passed)
    return diffMinutes < 30;
  }
  
  let html = '';
  schedules.forEach((schedule) => {
    const reservedCount = getReservedCountForShow(bookingData.movie, date, schedule.time);
    const available = Math.max(0, totalSeats - reservedCount);
    
    // Double-check: Replace any "1D" text with "2D" in cinema value (safety check)
    let cinemaText = schedule.cinema.replace(/1D/gi, '2D');
    
    // Check if this showtime is within 30 minutes
    const isDisabled = isShowtimeWithin30Minutes(date, schedule.time);
    const disabledClass = isDisabled ? 'schedule-card-disabled' : '';

    html += `
    <div class="schedule-card ${disabledClass}" data-schedule="${schedule.time}" data-cinema="${cinemaText}">
      <div class="schedule-time">${schedule.time}</div>
      <div class="schedule-cinema">${cinemaText}</div>
      <div class="schedule-seats">
        <span>${isDisabled ? '🚫' : '👤'}</span>
        <span class="availability-label">${isDisabled ? 'Booking closed (less than 30 min)' : `${available} seats available`}</span>
      </div>
    </div>`;
  });

  schedulesContainer.innerHTML = html;
  
  // Add click listeners to schedule cards
  document.querySelectorAll('.schedule-card').forEach(card => {
    card.addEventListener('click', function() {
      // Prevent selection of disabled cards
      if (card.classList.contains('schedule-card-disabled')) {
        return;
      }
      
      document.querySelectorAll('.schedule-card').forEach(c => c.classList.remove('selected'));
      this.classList.add('selected');
      
      bookingData.schedule = this.dataset.schedule;
      bookingData.cinema = this.dataset.cinema;
      bookingData.date = document.getElementById('booking-date').value;
      
      document.getElementById('continue-to-quantity').disabled = false;
    });
  });
}

function generateSeats() {
  const seatsGrid = document.getElementById('seats-grid');
  const rows = SEAT_ROWS;
  const seatsPerRow = SEATS_PER_ROW;

  // Load reserved seats for current show
  const reservedSeats = loadReservedSeatsForCurrentShow();
  
  let seatsHTML = '';
  rows.forEach(row => {
    for (let i = 1; i <= seatsPerRow; i++) {
      const seatId = `${row}${i}`;
      const isReserved = reservedSeats.has(seatId);
      seatsHTML += `
        <div class="seat ${isReserved ? 'reserved' : 'available'}" 
             data-seat="${seatId}" 
             ${isReserved ? '' : 'onclick="toggleSeat(this)"'}>
          ${seatId}
        </div>
      `;
    }
  });
  
  seatsGrid.innerHTML = seatsHTML;
}

// Reserved seats persistence helpers
function getReservedSeatsStorage() {
  try {
    return JSON.parse(localStorage.getItem('cineflix_reserved_seats') || '{}');
  } catch (_) {
    return {};
  }
}
function setReservedSeatsStorage(map) {
  localStorage.setItem('cineflix_reserved_seats', JSON.stringify(map));
}
function getCurrentShowKey() {
  const movie = bookingData.movie || '';
  const date = bookingData.date || document.getElementById('booking-date')?.value || '';
  const schedule = bookingData.schedule || '';
  return `${movie}__${date}__${schedule}`;
}
function loadReservedSeatsForCurrentShow() {
  const map = getReservedSeatsStorage();
  const key = getCurrentShowKey();
  const list = Array.isArray(map[key]) ? map[key] : [];
  return new Set(list);
}
function saveReservedSeatsForCurrentShow(newlyReservedSeats) {
  if (!Array.isArray(newlyReservedSeats) || newlyReservedSeats.length === 0) return;
  const map = getReservedSeatsStorage();
  const key = getCurrentShowKey();
  const existing = new Set(Array.isArray(map[key]) ? map[key] : []);
  newlyReservedSeats.forEach(s => existing.add(s));
  map[key] = Array.from(existing);
  setReservedSeatsStorage(map);
}
function getReservedCountForShow(movie, date, schedule) {
  const map = getReservedSeatsStorage();
  const key = `${movie || ''}__${date || ''}__${schedule || ''}`;
  const list = Array.isArray(map[key]) ? map[key] : [];
  return list.length;
}

function toggleSeat(seatElement) {
  const seatId = seatElement.dataset.seat;
  
  if (seatElement.classList.contains('selected')) {
    seatElement.classList.remove('selected');
    seatElement.classList.add('available');
    bookingData.seats = bookingData.seats.filter(s => s !== seatId);
  } else {
    if (bookingData.seats.length >= bookingData.quantity) {
      alert(`You can only select ${bookingData.quantity} seat(s)`);
      return;
    }
    seatElement.classList.remove('available');
    seatElement.classList.add('selected');
    bookingData.seats.push(seatId);
  }
  
  updateSeatsDisplay();
}

function updateSeatsDisplay() {
  const display = document.getElementById('selected-seats-display');
  const continueBtn = document.getElementById('continue-to-addons');
  
  if (bookingData.seats.length === 0) {
    display.textContent = 'None';
    continueBtn.disabled = true;
  } else {
    display.textContent = bookingData.seats.join(', ');
    continueBtn.disabled = bookingData.seats.length !== bookingData.quantity;
  }
  
  bookingData.ticketTotal = bookingData.quantity * bookingData.price;
  bookingData.total = bookingData.ticketTotal + (bookingData.addonsTotal || 0);
  document.getElementById('total-price').textContent = bookingData.ticketTotal.toFixed(2);
}

function setupEventListeners() {
  // Date change
  document.getElementById('booking-date').addEventListener('change', function() {
    bookingData.date = this.value;
    generateSchedules();
  });
  
  // Step 1: Continue to quantity
  document.getElementById('continue-to-quantity').addEventListener('click', function() {
    updateSummary(1);
    goToStep('step-quantity');
  });
  
  // Step 2: Quantity controls
  document.getElementById('decrease-qty').addEventListener('click', function() {
    if (bookingData.quantity > 1) {
      bookingData.quantity--;
      updateQuantity();
    }
  });
  
  document.getElementById('increase-qty').addEventListener('click', function() {
    if (bookingData.quantity < 10) {
      bookingData.quantity++;
      updateQuantity();
    }
  });
  
  document.getElementById('back-to-schedule').addEventListener('click', function() {
    generateSchedules();
    goToStep('step-schedule');
  });
  
  document.getElementById('continue-to-seats').addEventListener('click', function() {
    updateSummary(2);
    generateSeats();
    goToStep('step-seats');
  });
  
  // Step 3: Seats
  document.getElementById('back-to-quantity').addEventListener('click', function() {
    goToStep('step-quantity');
  });

  // After seats -> Addons
  document.getElementById('continue-to-addons').addEventListener('click', function() {
    // Prep addons step summary
    document.getElementById('summary-movie-addons').textContent = bookingData.movie;
    document.getElementById('summary-schedule-addons').textContent = `${bookingData.date} | ${bookingData.schedule}`;
    document.getElementById('summary-seats-addons').textContent = bookingData.seats.join(', ');
    renderAddons();
    goToStep('step-addons');
  });

  // Addons controls
  document.getElementById('skip-addons').addEventListener('click', function() {
    updateSummary(3);
    goToStep('step-payment');
  });
  document.getElementById('continue-to-payment').addEventListener('click', function() {
    updateSummary(3);
    goToStep('step-payment');
  });
  
  // Step 4: Payment
  document.getElementById('back-to-seats').addEventListener('click', function() {
    goToStep('step-seats');
  });
  
  // Format card number
  document.getElementById('card-number').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\s/g, '');
    let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
    e.target.value = formattedValue;
  });
  
  // Format expiry date
  document.getElementById('expiry-date').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length >= 2) {
      value = value.slice(0, 2) + '/' + value.slice(2, 4);
    }
    e.target.value = value;
  });
  
  // CVV numbers only
  document.getElementById('cvv').addEventListener('input', function(e) {
    e.target.value = e.target.value.replace(/\D/g, '');
  });
  
  document.getElementById('complete-payment').addEventListener('click', function() {
    const form = document.getElementById('payment-form');
    if (form.checkValidity()) {
      processPayment();
    } else {
      form.reportValidity();
    }
  });
}

// ==== Addons (Food & Drinks) ====
const ADDONS_MENU = [
  { id: 'popcorn', name: 'Popcorn', price: 150, img: 'food&drinks/Gemini_Generated_Image_4w0j74w0j74w0j74.png' },
  { id: 'drink', name: 'Drink', price: 120, img: 'food&drinks/Gemini_Generated_Image_4w0j74w0j74w0j74(1).png' }
];

function renderAddons() {
  const container = document.getElementById('addons-list');
  if (!container) return;
  container.innerHTML = ADDONS_MENU.map(item => `
    <div class="schedule-card" data-addon-id="${item.id}">
      <div style="overflow:hidden;border-radius:8px;margin-bottom:10px;">
        <img src="${item.img}" alt="${item.name}" class="addon-thumb" />
      </div>
      <div class="schedule-time">${item.name}</div>
      <div class="schedule-cinema">₱${item.price.toFixed(2)}</div>
      <div class="schedule-seats" style="justify-content:space-between;">
        <span>Qty:</span>
        <div style="display:flex;gap:8px;align-items:center;">
          <button class="quantity-btn" data-act="dec" data-id="${item.id}">−</button>
          <span class="quantity-display" id="qty-${item.id}">0</span>
          <button class="quantity-btn" data-act="inc" data-id="${item.id}">+</button>
        </div>
      </div>
    </div>
  `).join('');

  // Attach listeners
  container.querySelectorAll('button.quantity-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const id = btn.getAttribute('data-id');
      const act = btn.getAttribute('data-act');
      const disp = document.getElementById(`qty-${id}`);
      let qty = parseInt(disp.textContent || '0', 10);
      qty = isNaN(qty) ? 0 : qty;
      if (act === 'inc') qty = Math.min(99, qty + 1); else qty = Math.max(0, qty - 1);
      disp.textContent = String(qty);
      updateAddonsFromUI();
    });
  });
}

function updateAddonsFromUI() {
  const selected = [];
  let total = 0;
  ADDONS_MENU.forEach(item => {
    const qtyEl = document.getElementById(`qty-${item.id}`);
    if (!qtyEl) return;
    const qty = parseInt(qtyEl.textContent || '0', 10) || 0;
    if (qty > 0) {
      selected.push({ id: item.id, name: item.name, price: item.price, qty });
      total += item.price * qty;
    }
  });
  bookingData.addons = selected;
  bookingData.addonsTotal = total;
  bookingData.total = (bookingData.ticketTotal || 0) + total;
  const totalEl = document.getElementById('addons-total');
  if (totalEl) totalEl.textContent = total.toFixed(2);
}

function updateQuantity() {
  document.getElementById('quantity').textContent = bookingData.quantity;
  
  // Update buttons
  document.getElementById('decrease-qty').disabled = bookingData.quantity <= 1;
  document.getElementById('increase-qty').disabled = bookingData.quantity >= 10;
  
  // Reset seat selection when quantity changes
  bookingData.seats = [];
  document.querySelectorAll('.seat.selected').forEach(seat => {
    seat.classList.remove('selected');
    seat.classList.add('available');
  });
  updateSeatsDisplay();
}

function updateSummary(step) {
  const scheduleText = `${bookingData.date} | ${bookingData.schedule}`;
  
  if (step >= 1) {
    document.getElementById('summary-schedule-1').textContent = scheduleText;
  }
  if (step >= 2) {
    document.getElementById('summary-schedule-2').textContent = scheduleText;
    document.getElementById('summary-quantity').textContent = `${bookingData.quantity} ticket(s)`;
  }
  if (step >= 3) {
    document.getElementById('summary-schedule-3').textContent = scheduleText;
    document.getElementById('summary-seats').textContent = bookingData.seats.join(', ');
    document.getElementById('summary-quantity-2').textContent = `${bookingData.quantity} ticket(s)`;
    const addonsLine = (bookingData.addons && bookingData.addons.length)
      ? bookingData.addons.map(a => `${a.name} x${a.qty}`).join(', ')
      : 'None';
    document.getElementById('summary-addons').textContent = addonsLine;
    const grand = (bookingData.ticketTotal || 0) + (bookingData.addonsTotal || 0);
    bookingData.total = grand;
    document.getElementById('summary-price').textContent = grand.toFixed(2);
    document.getElementById('payment-amount').textContent = grand.toFixed(2);
  }
}

function goToStep(stepId) {
  document.querySelectorAll('.booking-step').forEach(step => {
    step.classList.remove('active');
  });
  document.getElementById(stepId).classList.add('active');
  
  // Scroll to top
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

function processPayment() {
<<<<<<< HEAD
  // Capture payment information
  const cardholderName = document.getElementById('cardholder-name').value;
  const cardNumber = document.getElementById('card-number').value;
  const expiryDate = document.getElementById('expiry-date').value;
  const cvv = document.getElementById('cvv').value;
  
  // Store payment info (masked for security)
  bookingData.paymentInfo = {
    cardholderName: cardholderName,
    cardNumber: cardNumber.replace(/\s/g, '').replace(/\d(?=\d{4})/g, '*'), // Mask all but last 4 digits
    expiryDate: expiryDate,
    paymentMethod: 'Credit/Debit Card',
    paymentDate: new Date().toISOString()
  };
  
=======
>>>>>>> 39461e1 (bago)
  // Generate booking ID
  bookingData.bookingId = 'CF' + Date.now().toString().slice(-8);
  
  // Save to localStorage
  saveBooking();
  // Persist reserved seats for this show
  saveReservedSeatsForCurrentShow(bookingData.seats);
  
  // Show confirmation
  displayConfirmation();
  goToStep('step-confirmation');
}

function saveBooking() {
<<<<<<< HEAD
  const booking = {
    id: bookingData.bookingId,
=======
  // Get current user ID (from PHP session or generate guest ID)
  const userId = typeof currentUserId !== 'undefined' && currentUserId !== null 
    ? currentUserId 
    : 'guest_' + (localStorage.getItem('guest_id') || (() => {
        const guestId = 'guest_' + Date.now();
        localStorage.setItem('guest_id', guestId);
        return guestId;
      })());
  
  const booking = {
    id: bookingData.bookingId,
    userId: userId, // Add user ID to booking
>>>>>>> 39461e1 (bago)
    movie: bookingData.movie,
    date: bookingData.date,
    schedule: bookingData.schedule,
    cinema: bookingData.cinema,
    seats: bookingData.seats.join(', '),
    quantity: bookingData.quantity,
    price: bookingData.total,
    addons: bookingData.addons,
    addonsTotal: bookingData.addonsTotal,
<<<<<<< HEAD
    paymentInfo: bookingData.paymentInfo,
=======
>>>>>>> 39461e1 (bago)
    status: 'Paid',
    bookedAt: new Date().toISOString()
  };
  
  // Get existing bookings
  let bookings = JSON.parse(localStorage.getItem('cineflix_bookings') || '[]');
  bookings.push(booking);
  localStorage.setItem('cineflix_bookings', JSON.stringify(bookings));
}

function displayConfirmation() {
  document.getElementById('receipt-booking-id').textContent = bookingData.bookingId;
  document.getElementById('receipt-movie').textContent = bookingData.movie;
  document.getElementById('receipt-schedule').textContent = `${bookingData.date} | ${bookingData.schedule}`;
  document.getElementById('receipt-seats').textContent = bookingData.seats.join(', ');
  document.getElementById('receipt-quantity').textContent = `${bookingData.quantity} ticket(s)`;
  document.getElementById('receipt-price').textContent = bookingData.total.toFixed(2);
  
  // Optionally, could append addons info to receipt if desired
  
  // Generate QR code using Denso Wave library
  const qrSeed = JSON.stringify({
    id: bookingData.bookingId,
    movie: bookingData.movie,
    date: bookingData.date,
    schedule: bookingData.schedule,
    cinema: bookingData.cinema,
    seats: bookingData.seats,
    total: bookingData.total
  });
  generateQRCodeDW(qrSeed);
}

function generateQRCodeDW(text) {
  const container = document.getElementById('qr-code');
  if (!container) return;
  container.innerHTML = '';
  new QRCode(container, {
    text: text,
    width: 240,
    height: 240,
    colorDark: '#111111',
    colorLight: '#ffffff',
    correctLevel: QRCode.CorrectLevel.M
  });
}
