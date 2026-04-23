// Tab switching functionality
document.addEventListener('DOMContentLoaded', function() {
  // Check if user is logged in (from PHP)
  if (typeof isLoggedIn === 'undefined') {
    var isLoggedIn = false;
  }
  
  const tabTriggers = document.querySelectorAll('.tab-trigger');
  const tabContents = document.querySelectorAll('.tab-content');

  tabTriggers.forEach(trigger => {
    trigger.addEventListener('click', function() {
      const targetTab = this.getAttribute('data-tab');

      // Remove active class from all triggers and contents
      tabTriggers.forEach(t => t.classList.remove('active'));
      tabContents.forEach(c => c.classList.remove('active'));

      // Add active class to clicked trigger and corresponding content
      this.classList.add('active');
      document.getElementById(targetTab).classList.add('active');
      
      // Load appropriate content when switching tabs
      if (targetTab === 'cancelled') {
        loadCancelledBookings();
      }
    });
  });
  
<<<<<<< HEAD
  // Only load bookings if user is logged in
  if (isLoggedIn) {
    loadBookings();
    loadCancelledBookings();
  } else {
    // Clear localStorage if not logged in (security measure)
    localStorage.removeItem('cineflix_bookings');
  }
=======
  // Load bookings from localStorage (works for both logged in and guest users)
  // Check if user is logged in (from PHP)
  if (typeof isLoggedIn === 'undefined') {
    var isLoggedIn = false;
  }
  
  // Always try to load bookings from localStorage
  loadBookings();
  loadCancelledBookings();
>>>>>>> 39461e1 (bago)

  // Modal controls
  const modal = document.getElementById('ticket-modal');
  const closeBtn2 = document.getElementById('ticket-modal-close-2');
  const backdrop = document.getElementById('ticket-modal-backdrop');
  const printBtn = document.getElementById('ticket-modal-print');
  function hideModal(){ if(modal) modal.style.display = 'none'; }
  if (closeBtn2) closeBtn2.addEventListener('click', hideModal);
  if (backdrop) backdrop.addEventListener('click', hideModal);
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') hideModal(); });
  if (printBtn) printBtn.addEventListener('click', () => window.print());
});

function loadBookings() {
<<<<<<< HEAD
  // Check if user is logged in
  if (typeof isLoggedIn !== 'undefined' && !isLoggedIn) {
    return;
  }
  
=======
  // Load bookings from localStorage regardless of login status
>>>>>>> 39461e1 (bago)
  const bookings = JSON.parse(localStorage.getItem('cineflix_bookings') || '[]');
  const paidContainer = document.getElementById('paid-tickets-container');
  const paidEmpty = document.getElementById('paid-empty');
  
  if (!paidContainer) return;
  
<<<<<<< HEAD
  // Filter only paid tickets (not cancelled/refunded)
  const paidBookings = bookings.filter(booking => {
    const status = booking.status || 'Paid';
    return status !== 'Cancelled' && status !== 'Refunded';
=======
  // Get current user ID (from PHP session or guest ID)
  const userId = typeof currentUserId !== 'undefined' && currentUserId !== null 
    ? currentUserId 
    : localStorage.getItem('guest_id');
  
  // Filter bookings by user ID and only paid tickets (not cancelled/refunded)
  const paidBookings = bookings.filter(booking => {
    // Match user ID (for logged in users) or guest ID (for guests)
    const bookingUserId = booking.userId || booking.user_id;
    const matchesUser = userId ? (bookingUserId === userId || bookingUserId === String(userId)) : true;
    
    const status = booking.status || 'Paid';
    return matchesUser && status !== 'Cancelled' && status !== 'Refunded';
>>>>>>> 39461e1 (bago)
  });
  
  if (paidBookings.length === 0) {
    if (paidEmpty) paidEmpty.style.display = 'block';
    if (paidContainer) paidContainer.innerHTML = '';
    return;
  }
  
  if (paidEmpty) paidEmpty.style.display = 'none';
  
  // Display bookings (most recent first)
  paidContainer.innerHTML = paidBookings.reverse().map(booking => `
    <div class="ticket-card">
      <div class="ticket-header">
        <div>
          <h3 class="ticket-title">${booking.movie}</h3>
          <p class="ticket-subtitle">Date: ${formatDate(booking.date)} | Time: ${booking.schedule}</p>
        </div>
        <span class="badge badge-success">${booking.status || 'Paid'}</span>
      </div>
      <div class="ticket-body">
        <p class="ticket-info"><strong>Booking ID:</strong> ${booking.id}</p>
        <p class="ticket-info"><strong>Seats:</strong> ${booking.seats}</p>
        <p class="ticket-info"><strong>Theater:</strong> ${booking.cinema}</p>
        <p class="ticket-price">₱${Number(booking.price).toFixed(2)}</p>
      </div>
      <div class="ticket-footer">
        <button class="btn btn-primary" onclick="viewTicket('${booking.id}')">View Ticket</button>
        <button class="btn btn-secondary" onclick="downloadReceipt('${booking.id}')">Download Receipt</button>
        <button class="btn btn-danger" onclick="requestRefund('${booking.id}')" style="margin-left: 8px;">Request Refund</button>
      </div>
    </div>
  `).join('');
}

function loadCancelledBookings() {
<<<<<<< HEAD
  // Check if user is logged in
  if (typeof isLoggedIn !== 'undefined' && !isLoggedIn) {
    return;
  }
  
=======
  // Load bookings from localStorage regardless of login status
>>>>>>> 39461e1 (bago)
  const bookings = JSON.parse(localStorage.getItem('cineflix_bookings') || '[]');
  const cancelledContainer = document.getElementById('cancelled-tickets-container');
  const cancelledEmpty = document.getElementById('cancelled-empty');
  
  if (!cancelledContainer) return;
  
<<<<<<< HEAD
  // Filter only cancelled/refunded tickets
  const cancelledBookings = bookings.filter(booking => booking.status === 'Cancelled' || booking.status === 'Refunded');
  
  if (cancelledBookings.length === 0) {
    if (cancelledEmpty) cancelledEmpty.style.display = 'block';
    cancelledContainer.innerHTML = '';
=======
  // Get current user ID (from PHP session or guest ID)
  const userId = typeof currentUserId !== 'undefined' && currentUserId !== null 
    ? currentUserId 
    : localStorage.getItem('guest_id');
  
  // Filter bookings by user ID and only cancelled/refunded tickets
  const cancelledBookings = bookings.filter(booking => {
    // Match user ID (for logged in users) or guest ID (for guests)
    const bookingUserId = booking.userId || booking.user_id;
    const matchesUser = userId ? (bookingUserId === userId || bookingUserId === String(userId)) : true;
    
    return matchesUser && (booking.status === 'Cancelled' || booking.status === 'Refunded');
  });
  
  if (cancelledBookings.length === 0) {
    if (cancelledEmpty) cancelledEmpty.style.display = 'block';
    if (cancelledContainer) cancelledContainer.innerHTML = '';
>>>>>>> 39461e1 (bago)
    return;
  }
  
  if (cancelledEmpty) cancelledEmpty.style.display = 'none';
  
  // Display cancelled bookings (most recent first)
  cancelledContainer.innerHTML = cancelledBookings.reverse().map(booking => `
    <div class="ticket-card">
      <div class="ticket-header">
        <div>
          <h3 class="ticket-title">${booking.movie}</h3>
          <p class="ticket-subtitle">Original Date: ${formatDate(booking.date)} | ${booking.cancelledDate ? 'Cancelled on: ' + formatDate(booking.cancelledDate) : ''}</p>
        </div>
        <span class="badge badge-danger">${booking.status}</span>
      </div>
      <div class="ticket-body">
        <p class="ticket-info"><strong>Booking ID:</strong> ${booking.id}</p>
        ${booking.refundReason ? `<p class="ticket-info"><strong>Reason:</strong> ${booking.refundReason}</p>` : ''}
        <p class="ticket-info"><strong>Refund Status:</strong> ${booking.refundStatus || 'Processed'}</p>
        <p class="ticket-price">₱${Number(booking.price).toFixed(2)} (Refunded)</p>
      </div>
      <div class="ticket-footer">
        <button class="btn btn-secondary" onclick="viewTicket('${booking.id}')">View Details</button>
      </div>
    </div>
  `).join('');
}

function formatDate(dateString) {
  const date = new Date(dateString);
  const options = { year: 'numeric', month: 'long', day: 'numeric' };
  return date.toLocaleDateString('en-US', options);
}

function viewTicket(bookingId) {
  const booking = findBookingById(bookingId);
  if (!booking) return alert('Ticket not found.');

  const modal = document.getElementById('ticket-modal');
  if (!modal) return;
  
  // Fill basic content
  document.getElementById('ticket-modal-id').textContent = booking.id;
  document.getElementById('tm-movie').textContent = booking.movie;
  document.getElementById('tm-datetime').textContent = `${booking.date} | ${booking.schedule}`;
  document.getElementById('tm-cinema').textContent = booking.cinema;
  document.getElementById('tm-seats').textContent = booking.seats;
  document.getElementById('tm-qty').textContent = `${booking.quantity} ticket(s)`;
  document.getElementById('tm-total').textContent = `₱${Number(booking.price).toFixed(2)}`;

  // Addons/Food & Drinks
  const addonsRow = document.getElementById('tm-addons-row');
  const addonsEl = document.getElementById('tm-addons');
  if (booking.addons && Array.isArray(booking.addons) && booking.addons.length > 0) {
    const addonsText = booking.addons.map(a => `${a.name} x${a.qty} (₱${(a.price * a.qty).toFixed(2)})`).join(', ');
    addonsEl.textContent = addonsText;
<<<<<<< HEAD
    addonsRow.style.display = 'flex';
  } else {
    addonsEl.textContent = 'None';
    addonsRow.style.display = 'flex';
=======
    if (addonsRow) addonsRow.style.display = 'flex';
  } else {
    if (addonsRow) addonsRow.style.display = 'none';
>>>>>>> 39461e1 (bago)
  }

  // Payment Information
  const paymentSection = document.getElementById('tm-payment-section');
  if (booking.paymentInfo) {
    document.getElementById('tm-cardholder').textContent = booking.paymentInfo.cardholderName || 'N/A';
    document.getElementById('tm-cardnumber').textContent = booking.paymentInfo.cardNumber || 'N/A';
    document.getElementById('tm-expiry').textContent = booking.paymentInfo.expiryDate || 'N/A';
    document.getElementById('tm-paymentmethod').textContent = booking.paymentInfo.paymentMethod || 'Credit/Debit Card';
    const payDate = booking.paymentInfo.paymentDate ? new Date(booking.paymentInfo.paymentDate).toLocaleString() : new Date(booking.bookedAt).toLocaleString();
    document.getElementById('tm-paymentdate').textContent = payDate;
    paymentSection.style.display = 'block';
  } else {
    paymentSection.style.display = 'none';
  }

  // QR code
  const qrWrap = document.getElementById('ticket-modal-qrcode');
  if (qrWrap) {
    qrWrap.innerHTML = '';
    const qrSeed = JSON.stringify({
      id: booking.id,
      movie: booking.movie,
      date: booking.date,
      schedule: booking.schedule,
      cinema: booking.cinema,
      seats: booking.seats,
      quantity: booking.quantity,
      total: booking.price,
      paymentDate: booking.paymentInfo ? booking.paymentInfo.paymentDate : booking.bookedAt
    });
    new QRCode(qrWrap, { text: qrSeed, width: 200, height: 200, colorDark: '#111111', colorLight: '#ffffff', correctLevel: QRCode.CorrectLevel.M });
  }

  modal.style.display = 'block';
}

function downloadReceipt(bookingId) {
  const booking = findBookingById(bookingId);
  if (!booking) return alert('Receipt not found.');

  // Create a temporary QR element to generate QR canvas
  const temp = document.createElement('div');
  temp.style.position = 'fixed';
  temp.style.left = '-9999px';
  document.body.appendChild(temp);
  const qrSeed = JSON.stringify({
    id: booking.id,
    movie: booking.movie,
    date: booking.date,
    schedule: booking.schedule,
    cinema: booking.cinema,
    seats: booking.seats,
    total: booking.price
  });
  const qr = new QRCode(temp, { text: qrSeed, width: 280, height: 280, colorDark: '#111111', colorLight: '#ffffff', correctLevel: QRCode.CorrectLevel.M });

  // Wait a tick for QR to render
  setTimeout(() => {
    let qrCanvas = temp.querySelector('canvas');
    let qrDataUrl = '';
    if (qrCanvas) {
      qrDataUrl = qrCanvas.toDataURL('image/png');
    } else {
      const img = temp.querySelector('img');
      qrDataUrl = img ? img.src : '';
    }

    // Build a vertical ticket on a canvas for download
    const width = 900;
    let height = 1600;
    // Increase height if payment info exists
    if (booking.paymentInfo) {
      height = 2000;
    }
    const canvas = document.createElement('canvas');
    canvas.width = width;
    canvas.height = height;
    const ctx = canvas.getContext('2d');

    function roundedRect(x, y, w, h, r) {
      const radius = Math.min(r, w / 2, h / 2);
      ctx.beginPath();
      ctx.moveTo(x + radius, y);
      ctx.arcTo(x + w, y, x + w, y + h, radius);
      ctx.arcTo(x + w, y + h, x, y + h, radius);
      ctx.arcTo(x, y + h, x, y, radius);
      ctx.arcTo(x, y, x + w, y, radius);
      ctx.closePath();
    }
    function drawShadow(cb) {
      ctx.save();
      ctx.shadowColor = 'rgba(0,0,0,0.25)';
      ctx.shadowBlur = 24;
      ctx.shadowOffsetY = 14;
      cb();
      ctx.restore();
    }

    const bg = ctx.createLinearGradient(0, 0, 0, height);
    bg.addColorStop(0, '#fbfaf7');
    bg.addColorStop(1, '#f3efe9');
    ctx.fillStyle = bg;
    ctx.fillRect(0, 0, width, height);

    const cardX = 50, cardY = 80, cardW = width - 100, cardH = height - 160, cardR = 28;
    drawShadow(() => { roundedRect(cardX, cardY, cardW, cardH, cardR); ctx.fillStyle = '#ffffff'; ctx.fill(); });
    roundedRect(cardX, cardY, cardW, cardH, cardR); ctx.strokeStyle = 'rgba(199,159,94,0.35)'; ctx.lineWidth = 2; ctx.stroke();

    const hdrX = cardX; const hdrY = cardY; const hdrW = cardW; const hdrH = 150;
    const g = ctx.createLinearGradient(hdrX, hdrY, hdrX + hdrW, hdrY);
    g.addColorStop(0, '#c79f5e'); g.addColorStop(1, '#a67c42');
    roundedRect(hdrX, hdrY, hdrW, hdrH, cardR); ctx.save(); ctx.clip(); ctx.fillStyle = g; ctx.fillRect(hdrX, hdrY, hdrW, hdrH); ctx.restore();

    ctx.fillStyle = '#111111'; ctx.font = '700 46px Poppins, Arial, sans-serif'; ctx.fillText('CineFlix', cardX + 32, hdrY + 95);
    ctx.font = '600 24px Poppins, Arial, sans-serif'; const idText = 'Booking #' + booking.id; ctx.fillText(idText, cardX + hdrW - 32 - ctx.measureText(idText).width, hdrY + 95);

    const perfY = hdrY + hdrH + 40; ctx.setLineDash([8, 12]); ctx.strokeStyle = '#e5e7eb'; ctx.lineWidth = 2; ctx.beginPath(); ctx.moveTo(cardX + 40, perfY); ctx.lineTo(cardX + cardW - 40, perfY); ctx.stroke(); ctx.setLineDash([]);
    ctx.fillStyle = '#f3efe9'; ctx.beginPath(); ctx.arc(cardX, perfY, 16, Math.PI/2, -Math.PI/2, true); ctx.fill(); ctx.beginPath(); ctx.arc(cardX + cardW, perfY, 16, -Math.PI/2, Math.PI/2, true); ctx.fill();

    let y = perfY + 70; const labelColor = '#8b8f98';
    function drawRow(label, value) { ctx.fillStyle = labelColor; ctx.font = '600 26px Poppins, Arial, sans-serif'; ctx.fillText(label, cardX + 40, y); ctx.fillStyle = '#111111'; ctx.font = '400 32px Poppins, Arial, sans-serif'; ctx.fillText(value, cardX + 240, y); y += 64; }
    drawRow('Movie', String(booking.movie || ''));
    drawRow('Date & Time', `${booking.date} | ${booking.schedule}`);
    drawRow('Cinema', String(booking.cinema || ''));
    drawRow('Seats', String(booking.seats || ''));
    drawRow('Tickets', String(booking.quantity || 0));
    if (booking.addons && Array.isArray(booking.addons) && booking.addons.length > 0) {
      const addonsText = booking.addons.map(a => `${a.name} x${a.qty}`).join(', ');
      drawRow('Food & Drinks', addonsText);
    }

    y += 10; ctx.strokeStyle = '#efe9df'; ctx.lineWidth = 2; ctx.beginPath(); ctx.moveTo(cardX + 40, y); ctx.lineTo(cardX + cardW - 40, y); ctx.stroke(); y += 70;
    ctx.fillStyle = labelColor; ctx.font = '700 30px Poppins, Arial, sans-serif'; ctx.fillText('Total Paid', cardX + 40, y);
    const totalText = '₱' + Number(booking.price).toFixed(2); ctx.fillStyle = '#111111'; ctx.font = '700 38px Poppins, Arial, sans-serif'; ctx.fillText(totalText, cardX + cardW - 40 - ctx.measureText(totalText).width, y);
    
    // Payment Information Section
    if (booking.paymentInfo) {
      y += 100;
      ctx.strokeStyle = '#efe9df'; ctx.lineWidth = 2; ctx.beginPath(); ctx.moveTo(cardX + 40, y); ctx.lineTo(cardX + cardW - 40, y); ctx.stroke(); y += 50;
      ctx.fillStyle = '#c79f5e'; ctx.font = '700 28px Poppins, Arial, sans-serif'; ctx.fillText('Payment Information', cardX + 40, y); y += 50;
      drawRow('Cardholder', String(booking.paymentInfo.cardholderName || 'N/A'));
      drawRow('Card Number', String(booking.paymentInfo.cardNumber || 'N/A'));
      drawRow('Expiry Date', String(booking.paymentInfo.expiryDate || 'N/A'));
      drawRow('Payment Method', String(booking.paymentInfo.paymentMethod || 'Credit/Debit Card'));
      const payDate = booking.paymentInfo.paymentDate ? new Date(booking.paymentInfo.paymentDate).toLocaleString() : (booking.bookedAt ? new Date(booking.bookedAt).toLocaleString() : 'N/A');
      drawRow('Payment Date', payDate);
    }

    const qrBoxSize = 420; const qrBoxX = cardX + Math.floor((cardW - qrBoxSize) / 2); const qrBoxY = cardY + cardH - 120 - (qrBoxSize + 70);
    drawShadow(() => { roundedRect(qrBoxX, qrBoxY, qrBoxSize, qrBoxSize + 70, 16); ctx.fillStyle = '#ffffff'; ctx.fill(); });
    ctx.strokeStyle = 'rgba(199,159,94,0.45)'; ctx.lineWidth = 2; roundedRect(qrBoxX, qrBoxY, qrBoxSize, qrBoxSize + 70, 16); ctx.stroke();
    ctx.fillStyle = '#6b7280'; ctx.font = '600 20px Poppins, Arial, sans-serif'; ctx.fillText('Scan to verify', qrBoxX + (qrBoxSize/2) - ctx.measureText('Scan to verify').width/2, qrBoxY + qrBoxSize + 48);

    const qrImg = new Image();
    qrImg.onload = () => {
      const qrInner = qrBoxSize - 40; const qrX = qrBoxX + 20; const qrY = qrBoxY + 20; ctx.drawImage(qrImg, qrX, qrY, qrInner, qrInner);
      ctx.save(); ctx.translate(cardX + cardW/2, cardY + cardH/2); ctx.rotate(-Math.PI / 8); ctx.fillStyle = 'rgba(199,159,94,0.06)'; ctx.font = '900 120px Poppins, Arial, sans-serif'; ctx.textAlign = 'center'; ctx.fillText('CINEFLIX', 0, 0); ctx.restore();
      ctx.fillStyle = '#8b8f98'; ctx.font = '400 20px Poppins, Arial, sans-serif'; ctx.fillText('Generated ' + new Date().toLocaleString(), cardX + 40, cardY + cardH - 30);

      const dataUrl = canvas.toDataURL('image/png'); const a = document.createElement('a'); a.href = dataUrl; a.download = `Receipt_${booking.id}.png`; document.body.appendChild(a); a.click(); a.remove(); temp.remove();
    };
    qrImg.onerror = () => { temp.remove(); alert('Failed to generate QR image.'); };
    qrImg.src = qrDataUrl;
  }, 50);
}

function findBookingById(id) {
  const bookings = JSON.parse(localStorage.getItem('cineflix_bookings') || '[]');
<<<<<<< HEAD
  return bookings.find(b => b.id === id);
=======
  
  // Get current user ID (from PHP session or guest ID)
  const userId = typeof currentUserId !== 'undefined' && currentUserId !== null 
    ? currentUserId 
    : localStorage.getItem('guest_id');
  
  // Find booking by ID and user ID
  return bookings.find(b => {
    const bookingUserId = b.userId || b.user_id;
    const matchesUser = userId ? (bookingUserId === userId || bookingUserId === String(userId)) : true;
    return b.id === id && matchesUser;
  });
>>>>>>> 39461e1 (bago)
}

function requestRefund(bookingId) {
  // Check if user is logged in
  if (typeof isLoggedIn !== 'undefined' && !isLoggedIn) {
    alert('Please log in to request a refund.');
    window.location.href = 'login.html';
    return;
  }
  
  const booking = findBookingById(bookingId);
  if (!booking) {
    alert('Booking not found.');
    return;
  }
  
  // Check if already cancelled/refunded
  if (booking.status === 'Cancelled' || booking.status === 'Refunded') {
    alert('This booking has already been cancelled/refunded.');
    return;
  }
  
  // Ask for confirmation
  const confirmRefund = confirm(
    `Are you sure you want to request a refund for this booking?\n\n` +
    `Movie: ${booking.movie}\n` +
    `Date: ${formatDate(booking.date)} | Time: ${booking.schedule}\n` +
    `Amount: ₱${Number(booking.price).toFixed(2)}\n\n` +
    `Refunds are typically processed within 3-5 business days.`
  );
  
  if (!confirmRefund) {
    return;
  }
  
  // Ask for reason
  const reason = prompt('Please provide a reason for cancellation (optional):');
  
<<<<<<< HEAD
  // Update booking status
  const bookings = JSON.parse(localStorage.getItem('cineflix_bookings') || '[]');
  const bookingIndex = bookings.findIndex(b => b.id === bookingId);
=======
  // Get current user ID (from PHP session or guest ID)
  const userId = typeof currentUserId !== 'undefined' && currentUserId !== null 
    ? currentUserId 
    : localStorage.getItem('guest_id');
  
  // Update booking status
  const bookings = JSON.parse(localStorage.getItem('cineflix_bookings') || '[]');
  const bookingIndex = bookings.findIndex(b => {
    const bookingUserId = b.userId || b.user_id;
    const matchesUser = userId ? (bookingUserId === userId || bookingUserId === String(userId)) : true;
    return b.id === bookingId && matchesUser;
  });
>>>>>>> 39461e1 (bago)
  
  if (bookingIndex !== -1) {
    bookings[bookingIndex].status = 'Refunded';
    bookings[bookingIndex].refundReason = reason || 'No reason provided';
    bookings[bookingIndex].refundStatus = 'Processing';
    bookings[bookingIndex].cancelledDate = new Date().toISOString();
    
    localStorage.setItem('cineflix_bookings', JSON.stringify(bookings));
    
    alert('Refund request submitted successfully! Your refund will be processed within 3-5 business days.');
    
    // Reload bookings to show updated status
    loadBookings();
    loadCancelledBookings();
    
    // Switch to cancelled tab to show the refunded ticket
    const cancelledTab = document.querySelector('[data-tab="cancelled"]');
    if (cancelledTab) {
      cancelledTab.click();
    }
  } else {
    alert('Error: Booking not found.');
  }
<<<<<<< HEAD
}
=======
}
>>>>>>> 39461e1 (bago)
