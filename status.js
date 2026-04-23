const bookingStore = new Map();
let paidBookings = [];
let cancelledBookings = [];

document.addEventListener('DOMContentLoaded', () => {
  if (typeof isLoggedIn === 'undefined' || !isLoggedIn) {
    return;
  }

  setupTabs();
  setupModal();
  refreshBookings();
});

function setupTabs() {
  const tabTriggers = document.querySelectorAll('.tab-trigger');
  const tabContents = document.querySelectorAll('.tab-content');

  tabTriggers.forEach(trigger => {
    trigger.addEventListener('click', () => {
      const targetTab = trigger.getAttribute('data-tab');
      tabTriggers.forEach(t => t.classList.remove('active'));
      tabContents.forEach(c => c.classList.remove('active'));
      trigger.classList.add('active');
      document.getElementById(targetTab)?.classList.add('active');
    });
  });
}

let currentTicketBookingId = null;

function setupModal() {
  const modal = document.getElementById('ticket-modal');
  const closeBtn = document.getElementById('ticket-modal-close-2');
  const backdrop = document.getElementById('ticket-modal-backdrop');
  const downloadBtn = document.getElementById('ticket-modal-download');
  const hideModal = () => { if (modal) modal.style.display = 'none'; currentTicketBookingId = null; };
  if (closeBtn) closeBtn.addEventListener('click', hideModal);
  if (backdrop) backdrop.addEventListener('click', hideModal);
  if (downloadBtn) downloadBtn.addEventListener('click', () => { if (currentTicketBookingId) downloadReceipt(currentTicketBookingId); });
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') hideModal(); });
}

async function refreshBookings() {
  const paidContainer = document.getElementById('paid-tickets-container');
  const paidEmpty = document.getElementById('paid-empty');

  if (paidContainer) paidContainer.innerHTML = '<p style="color:#bbb;">Loading your bookings...</p>';
  if (paidEmpty) paidEmpty.style.display = 'none';

  try {
    const response = await fetch('api/get_bookings.php');
    if (!response.ok) {
      throw new Error('Failed to load bookings.');
    }

    const data = await response.json();
    if (!data.success) {
      throw new Error(data.error || 'Unable to load bookings.');
    }

    paidBookings = Array.isArray(data.paid) ? data.paid : [];
    cancelledBookings = Array.isArray(data.cancelled) ? data.cancelled : [];
    rebuildBookingStore();
    renderPaidBookings();
    renderCancelledBookings();
  } catch (err) {
    console.error(err);
    if (paidContainer) {
      paidContainer.innerHTML = '<p style="color:#f66;">We could not retrieve your bookings right now. Please refresh the page.</p>';
    }
  }
}

function rebuildBookingStore() {
  bookingStore.clear();
  [...paidBookings, ...cancelledBookings].forEach(booking => {
    bookingStore.set(booking.booking_id, booking);
  });
}

function renderPaidBookings() {
  const container = document.getElementById('paid-tickets-container');
  const emptyState = document.getElementById('paid-empty');
  if (!container) return;

  if (!paidBookings.length) {
    container.innerHTML = '';
    if (emptyState) emptyState.style.display = 'block';
    return;
  }

  if (emptyState) emptyState.style.display = 'none';
  container.innerHTML = paidBookings.map(booking => renderBookingCard(booking, 'paid')).join('');
  attachCardListeners(container);
}

function renderCancelledBookings() {
  const container = document.getElementById('cancelled-tickets-container');
  const emptyState = document.getElementById('cancelled-empty');
  if (!container) return;

  if (!cancelledBookings.length) {
    container.innerHTML = '';
    if (emptyState) emptyState.style.display = 'block';
    return;
  }

  if (emptyState) emptyState.style.display = 'none';
  container.innerHTML = cancelledBookings.map(booking => renderBookingCard(booking, 'cancelled')).join('');
  attachCardListeners(container);
}

function renderBookingCard(booking, category) {
  const statusLabel = formatStatus(booking.status);
  const price = Number(booking.total_amount || 0).toFixed(2);
  const itemLabel = booking.item_type === 'event' && booking.event_option
    ? `${booking.item_name} • ${formatStatus(booking.event_option)}`
    : booking.item_name;

  const subInfo = category === 'paid'
    ? `Date: ${formatDateLabel(booking.show_date)} | Time: ${booking.show_time || 'N/A'}`
    : `Original Date: ${formatDateLabel(booking.show_date)}${booking.cancelled_date ? ` | Cancelled on: ${formatDateLabel(booking.cancelled_date)}` : ''}`;

  const seats = booking.seats || 'N/A';
  const theatre = booking.venue || booking.cinema || 'N/A';
  const canRefund = category === 'paid' && statusLabel === 'Paid';
  const refundInfo = booking.refund_status ? `<p class="ticket-info"><strong>Refund Status:</strong> ${formatStatus(booking.refund_status)}</p>` : '';
  const refundReason = booking.refund_reason ? `<p class="ticket-info"><strong>Reason:</strong> ${booking.refund_reason}</p>` : '';

  let discountSummary = '';
  const discountStatus = String(booking.discount_status || '').toLowerCase();
  const hasApprovedDiscount = booking.discount_type && discountStatus === 'approved';
  const hasPendingDiscount = booking.discount_type && discountStatus === 'pending';
  if (hasApprovedDiscount && booking.discount_original_total && booking.discounted_total) {
    const orig = Number(booking.discount_original_total || 0);
    const disc = Number(booking.discounted_total || 0);
    const diff = orig - disc;
    discountSummary = `<p class="ticket-info"><strong>Discount:</strong> ${formatStatus(booking.discount_type)} &mdash; Approved ✅</p>
      <p class="ticket-info" style="font-size:0.82em;color:#0a7c42;">Overpaid amount of ₱${diff.toFixed(2)} can be claimed at the cashier.</p>`;
  } else if (hasPendingDiscount) {
    discountSummary = `<p class="ticket-info" style="color:#c79f5e;"><strong>⏳ Discount:</strong> ${formatStatus(booking.discount_type)} &mdash; Pending Admin Approval</p>`;  
  }

  const parkingSubtitle = (booking.parking_number && category === 'paid')
    ? `<p class="ticket-parking-subtitle">Parking Number: ${String(booking.parking_number)}</p>`
    : '';

  // Add-ons display with prices
  let addonsHtml = '';
  if (booking.addons && booking.addons.length) {
    const addonLines = booking.addons.map(a => `${a.name} x${a.qty} (₱${(a.price * a.qty).toFixed(2)})`);
    addonsHtml = `<p class="ticket-info"><strong>Add-ons:</strong> ${addonLines.join(', ')}</p>`;
  }

  return `
    <div class="ticket-card" data-booking-id="${booking.booking_id}">
      <div class="ticket-header">
        <div>
          <h3 class="ticket-title">${itemLabel}</h3>
          <p class="ticket-subtitle">${subInfo}</p>
        </div>
        <div class="ticket-status-block">
          <span class="badge ${category === 'paid' ? 'badge-success' : 'badge-danger'}">${statusLabel}</span>
          ${parkingSubtitle}
        </div>
      </div>
      <div class="ticket-body">
        <p class="ticket-info"><strong>Booking ID:</strong> ${booking.booking_id}</p>
        <p class="ticket-info"><strong>Seats:</strong> ${seats}</p>
        <p class="ticket-info"><strong>Venue:</strong> ${theatre}</p>
        <p class="ticket-info"><strong>Tickets:</strong> ${booking.quantity}</p>
        ${addonsHtml}
        ${refundReason}
        ${refundInfo}
        ${discountSummary}
        <p class="ticket-price">₱${price}</p>
      </div>
      <div class="ticket-footer">
        <button class="btn btn-primary" data-action="view-ticket">View Ticket</button>
        <button class="btn btn-secondary" data-action="download-receipt">Download Receipt</button>
        ${canRefund ? `<button class="btn btn-danger" data-action="request-refund">Request Refund</button>` : ''}
      </div>
    </div>
  `;
}

function attachCardListeners(container) {
  container.querySelectorAll('.ticket-card').forEach(card => {
    const bookingId = card.getAttribute('data-booking-id');
    card.querySelectorAll('[data-action]').forEach(button => {
      const action = button.getAttribute('data-action');
      if (action === 'view-ticket') {
        button.addEventListener('click', () => viewTicket(bookingId));
      } else if (action === 'download-receipt') {
        button.addEventListener('click', () => downloadReceipt(bookingId));
      } else if (action === 'request-refund') {
        button.addEventListener('click', () => requestRefund(bookingId));
      }
    });
  });
}

const PARKING_FEE = 50;

function viewTicket(bookingId) {
  const booking = bookingStore.get(bookingId);
  if (!booking) {
    alert('Ticket not found.');
    return;
  }

  currentTicketBookingId = bookingId;
  const modal = document.getElementById('ticket-modal');
  if (!modal) return;

  const itemLabel = booking.item_type === 'event' && booking.event_option
    ? `${booking.item_name} • ${formatStatus(booking.event_option)}`
    : (booking.item_name || 'N/A');
  const dateTimeStr = `${formatDateLabel(booking.show_date)} | ${booking.show_time || 'N/A'}`;
  const totalAmount = Number(booking.total_amount || 0);
  const seatsStr = booking.seats || 'N/A';
  const seatList = typeof booking.seats === 'string' ? booking.seats.split(',').map(s => s.trim()) : [];
  const lastSeat = seatList.length ? seatList[seatList.length - 1] : seatsStr;

  document.getElementById('tm-booking-id').textContent = booking.booking_id;
  document.getElementById('tm-movie').textContent = itemLabel.toUpperCase();
  document.getElementById('tm-datetime').textContent = dateTimeStr.toUpperCase();
  document.getElementById('tm-seats').textContent = seatsStr.toUpperCase();
  document.getElementById('tm-qty').textContent = `${booking.quantity} ticket(s)`;
  document.getElementById('tm-payment-method').textContent = formatStatus(booking.payment_method || 'Credit/Debit Card');
  document.getElementById('tm-total').textContent = totalAmount.toFixed(2);
  document.getElementById('tm-total-stub').textContent = totalAmount.toFixed(2);
  document.getElementById('tm-customer-name').textContent = (booking.customer_name || '—').toUpperCase();
  document.getElementById('tm-seat-stub').textContent = lastSeat;

  const parkingRow = document.getElementById('tm-parking-row');
  const parkingEl = document.getElementById('tm-parking');
  if (parkingRow && parkingEl && booking.parking_number) {
    parkingRow.style.display = 'flex';
    parkingEl.textContent = `${String(booking.parking_number)} (P${PARKING_FEE})`;
  } else if (parkingRow) {
    parkingRow.style.display = 'none';
  }

  const addonsRow = document.getElementById('tm-addons-row');
  const addonsEl = document.getElementById('tm-addons');
  if (addonsRow && addonsEl) {
    if (booking.addons && booking.addons.length) {
      addonsEl.innerHTML = booking.addons.map(a => 
        `${a.name} x${a.qty} <span style="opacity:0.7;">(₱${a.price.toFixed(2)} each = ₱${(a.price * a.qty).toFixed(2)})</span>`
      ).join('<br>');
      addonsRow.style.display = 'flex';
    } else {
      addonsRow.style.display = 'none';
    }
  }

  const discountStatusVal = String(booking.discount_status || '').toLowerCase();
  const hasApprovedDiscount = booking.discount_type && discountStatusVal === 'approved';
  const hasPendingDiscount = booking.discount_type && discountStatusVal === 'pending';
  const discountBlock = document.getElementById('tm-discount-block');
  const discountLine = document.getElementById('tm-discount-line');
  const tmOriginalTotal = document.getElementById('tm-original-total');
  const tmDiscountAmount = document.getElementById('tm-discount-amount');
  const tmPendingNotice = document.getElementById('tm-discount-pending');
  const tmApprovedNotice = document.getElementById('tm-discount-approved-notice');
  const tmOverpaidAmount = document.getElementById('tm-overpaid-amount');
  // New breakdown rows
  const tmTicketPriceRow = document.getElementById('tm-ticket-price-row');
  const tmTicketPrice = document.getElementById('tm-ticket-price');
  const tmAddonsTotalRow = document.getElementById('tm-addons-total-row');
  const tmAddonsTotal = document.getElementById('tm-addons-total');
  const tmParkingFeeRow = document.getElementById('tm-parking-fee-row');
  const tmParkingFee = document.getElementById('tm-parking-fee');

  // Reset all discount/breakdown displays
  if (discountBlock) discountBlock.style.display = 'none';
  if (discountLine) discountLine.style.display = 'none';
  if (tmPendingNotice) tmPendingNotice.style.display = 'none';
  if (tmApprovedNotice) tmApprovedNotice.style.display = 'none';
  if (tmTicketPriceRow) tmTicketPriceRow.style.display = 'none';
  if (tmAddonsTotalRow) tmAddonsTotalRow.style.display = 'none';
  if (tmParkingFeeRow) tmParkingFeeRow.style.display = 'none';

  if (hasApprovedDiscount && booking.discount_original_total != null && booking.discounted_total != null) {
    const orig = Number(booking.discount_original_total);
    const disc = Number(booking.discounted_total);
    const diff = orig - disc;
    let addonsTotal = (booking.addons || []).reduce((sum, a) => sum + a.price * a.qty, 0);
    const parkingTotal = booking.parking_number ? PARKING_FEE : 0;
    // Subtotal = total_amount + discount_amount (reverse-calculate to guarantee accuracy)
    const subtotalBeforeDiscount = totalAmount + diff;
    // If addons array is empty but there's extra amount in the subtotal, calculate from difference
    if (addonsTotal === 0) {
      const impliedAddons = subtotalBeforeDiscount - orig - parkingTotal;
      if (impliedAddons > 0) addonsTotal = impliedAddons;
    }

    // Show itemized breakdown so the math is clear
    if (tmTicketPriceRow && tmTicketPrice) {
      tmTicketPrice.textContent = orig.toFixed(2);
      tmTicketPriceRow.style.display = 'flex';
    }
    if (addonsTotal > 0 && tmAddonsTotalRow && tmAddonsTotal) {
      tmAddonsTotal.textContent = addonsTotal.toFixed(2);
      tmAddonsTotalRow.style.display = 'flex';
    }
    if (parkingTotal > 0 && tmParkingFeeRow && tmParkingFee) {
      tmParkingFee.textContent = parkingTotal.toFixed(2);
      tmParkingFeeRow.style.display = 'flex';
    }
    if (discountBlock && discountLine && tmOriginalTotal && tmDiscountAmount) {
      tmOriginalTotal.textContent = subtotalBeforeDiscount.toFixed(2);
      tmDiscountAmount.textContent = diff.toFixed(2);
      discountBlock.style.display = 'flex';
      discountLine.style.display = 'flex';
    }
    // Show approved notice with cashier claim message
    if (tmApprovedNotice && tmOverpaidAmount) {
      tmOverpaidAmount.textContent = diff.toFixed(2);
      tmApprovedNotice.style.display = 'block';
    }
  } else if (hasPendingDiscount) {
    // Show pending notice
    if (tmPendingNotice) tmPendingNotice.style.display = 'block';
  }

  const qrWrap = document.getElementById('ticket-modal-qrcode');
  if (qrWrap) {
    qrWrap.innerHTML = '';
    const qrSeed = JSON.stringify({
      id: booking.booking_id,
      item: booking.item_name,
      date: booking.show_date,
      schedule: booking.show_time,
      venue: booking.venue,
      seats: booking.seats,
      quantity: booking.quantity,
      total: booking.total_amount
    });
    new QRCode(qrWrap, { text: qrSeed, width: 180, height: 180, colorDark: '#111111', colorLight: '#ffffff', correctLevel: QRCode.CorrectLevel.M });
  }

  modal.style.display = 'flex';
}

function downloadReceipt(bookingId) {
  const booking = bookingStore.get(bookingId);
  if (!booking) {
    alert('Receipt not found.');
    return;
  }

  const temp = document.createElement('div');
  temp.style.position = 'fixed';
  temp.style.left = '-9999px';
  document.body.appendChild(temp);

  const qrSeed = JSON.stringify({
    id: booking.booking_id,
    item: booking.item_name,
    date: booking.show_date,
    schedule: booking.show_time,
    venue: booking.venue,
    seats: booking.seats,
    total: booking.total_amount
  });

  new QRCode(temp, { text: qrSeed, width: 280, height: 280, colorDark: '#111111', colorLight: '#ffffff', correctLevel: QRCode.CorrectLevel.M });

  setTimeout(() => {
    let qrCanvas = temp.querySelector('canvas');
    let qrDataUrl = '';
    if (qrCanvas) {
      qrDataUrl = qrCanvas.toDataURL('image/png');
    } else {
      const img = temp.querySelector('img');
      qrDataUrl = img ? img.src : '';
    }

    temp.remove();
    buildReceiptImage(booking, qrDataUrl);
  }, 80);
}

function buildReceiptImage(booking, qrDataUrl) {
  const width = 480;
  const totalH = 1100;
  const pad = 24;
  const ticketW = width - pad * 2;
  const ticketH = 920;
  let y = pad;

  const dlDiscountStatus = String(booking.discount_status || '').toLowerCase();
  const hasApprovedDiscount = booking.discount_type && dlDiscountStatus === 'approved';
  const hasPendingDiscount = booking.discount_type && dlDiscountStatus === 'pending';
  const hasParking = !!booking.parking_number;
  let addonsTotal = (booking.addons || []).reduce((s, a) => s + a.price * a.qty, 0);
  const origTickets = Number(booking.discount_original_total || 0);
  const discountAmount = origTickets - Number(booking.discounted_total || 0);
  const totalAmount = Number(booking.total_amount || 0);
  // Subtotal = total + discount (reverse-calculate to guarantee accuracy)
  const subtotalBefore = totalAmount + discountAmount;
  // If addons array is empty but there's extra amount, calculate from difference
  if (addonsTotal === 0) {
    const parkingVal = hasParking ? PARKING_FEE : 0;
    const impliedAddons = subtotalBefore - origTickets - parkingVal;
    if (impliedAddons > 0) addonsTotal = impliedAddons;
  }

  const itemLabel = (booking.item_type === 'event' && booking.event_option)
    ? `${booking.item_name} • ${formatStatus(booking.event_option)}`
    : (booking.item_name || 'N/A');
  const dateTimeStr = `${formatDateLabel(booking.show_date)} | ${booking.show_time || 'N/A'}`;
  const seatsStr = booking.seats || 'N/A';
  const seatList = typeof booking.seats === 'string' ? booking.seats.split(',').map(s => s.trim()) : [];
  const lastSeat = seatList.length ? seatList[seatList.length - 1] : seatsStr;

  const canvas = document.createElement('canvas');
  canvas.width = width;
  canvas.height = totalH;
  const ctx = canvas.getContext('2d');

  ctx.fillStyle = '#0a0a0a';
  ctx.fillRect(0, 0, width, totalH);

  ctx.fillStyle = '#ffffff';
  ctx.font = '700 20px Poppins, Arial, sans-serif';
  ctx.textAlign = 'center';
  ctx.fillText('THIS IS YOUR TICKET', width / 2, y + 18);
  y += 38;
  ctx.font = '400 14px Poppins, Arial, sans-serif';
  ctx.fillStyle = 'rgba(255,255,255,0.85)';
  ctx.fillText('Please show it on your phone when you arrive at the venue.', width / 2, y + 12);
  y += 36;

  const ticketX = pad;
  const ticketY = y;
  const r = 12;
  ctx.fillStyle = '#ffffff';
  ctx.beginPath();
  ctx.moveTo(ticketX + r, ticketY);
  ctx.lineTo(ticketX + ticketW - r, ticketY);
  ctx.quadraticCurveTo(ticketX + ticketW, ticketY, ticketX + ticketW, ticketY + r);
  ctx.lineTo(ticketX + ticketW, ticketY + ticketH - r);
  ctx.quadraticCurveTo(ticketX + ticketW, ticketY + ticketH, ticketX + ticketW - r, ticketY + ticketH);
  ctx.lineTo(ticketX + r, ticketY + ticketH);
  ctx.quadraticCurveTo(ticketX, ticketY + ticketH, ticketX, ticketY + ticketH - r);
  ctx.lineTo(ticketX, ticketY + r);
  ctx.quadraticCurveTo(ticketX, ticketY, ticketX + r, ticketY);
  ctx.closePath();
  ctx.fill();

  let ty = ticketY + 28;
  const tx = ticketX + 20;
  const labelGray = '#888';
  ctx.textAlign = 'left';
  ctx.fillStyle = '#111';
  ctx.font = '700 14px Poppins, Arial, sans-serif';
  ctx.fillText('CineFlix', tx, ty);
  ctx.font = '600 10px Poppins, Arial, sans-serif';
  ctx.fillStyle = labelGray;
  ctx.textAlign = 'right';
  ctx.fillText('CHECK-IN CODE', ticketX + ticketW - 20, ty - 10);
  ctx.fillStyle = '#111';
  ctx.font = '700 12px Poppins, Arial, sans-serif';
  ctx.fillText(String(booking.booking_id), ticketX + ticketW - 20, ty + 6);
  ctx.textAlign = 'left';
  ty += 36;

  const qrImg = new Image();
  qrImg.onload = () => {
    const qrSize = 160;
    ctx.drawImage(qrImg, ticketX + (ticketW - qrSize) / 2, ty, qrSize, qrSize);
    ty += qrSize + 20;

    function row(label, value, valueColor) {
      ctx.fillStyle = labelGray;
      ctx.font = '600 10px Poppins, Arial, sans-serif';
      ctx.fillText(label, tx, ty);
      ctx.fillStyle = valueColor || '#111';
      ctx.font = '700 11px Poppins, Arial, sans-serif';
      ctx.fillText(value, tx, ty + 16);
      ty += 28;
    }

    row('MOVIE NAME', String(itemLabel).toUpperCase());
    row('DATE AND TIME', dateTimeStr.toUpperCase());
    row('SEATS', String(seatsStr).toUpperCase());
    row('NUMBER OF TICKETS', `${booking.quantity} ticket(s)`);
    if (hasParking) row('PARKING', `${booking.parking_number} (P${PARKING_FEE})`);
    if (booking.addons && booking.addons.length) {
      const addText = booking.addons.map(a => `${a.name} x${a.qty} (P${(a.price * a.qty).toFixed(2)})`).join(', ');
      row('FOOD & DRINKS', addText);
    }
    row('PAYMENT METHOD', formatStatus(booking.payment_method || 'Card'));
    if (hasApprovedDiscount) {
      row('TICKET PRICE', 'P' + origTickets.toFixed(2));
      if (addonsTotal > 0) row('ADD-ONS TOTAL', 'P' + addonsTotal.toFixed(2));
      if (hasParking) row('PARKING FEE', 'P' + PARKING_FEE.toFixed(2));
      row('SUBTOTAL (BEFORE DISCOUNT)', 'P' + subtotalBefore.toFixed(2));
      row('PWD / SENIOR DISCOUNT (-20%)', '-P' + discountAmount.toFixed(2), '#0a7c42');
    }
    row('TOTAL PAID', 'P' + totalAmount.toFixed(2));

    // Discount status notice on downloadable receipt
    if (hasApprovedDiscount && discountAmount > 0) {
      ty += 4;
      ctx.fillStyle = '#0a7c42';
      ctx.font = '700 10px Poppins, Arial, sans-serif';
      ctx.fillText('✅ DISCOUNT APPROVED', tx, ty);
      ty += 14;
      ctx.fillStyle = '#444';
      ctx.font = '400 9px Poppins, Arial, sans-serif';
      ctx.fillText('Overpaid amount of P' + discountAmount.toFixed(2) + ' can be', tx, ty);
      ty += 12;
      ctx.fillText('claimed at the cashier.', tx, ty);
      ty += 16;
    } else if (hasPendingDiscount) {
      ty += 4;
      ctx.fillStyle = '#b8860b';
      ctx.font = '700 10px Poppins, Arial, sans-serif';
      ctx.fillText('⏳ DISCOUNT PENDING ADMIN APPROVAL', tx, ty);
      ty += 14;
      ctx.fillStyle = '#444';
      ctx.font = '400 9px Poppins, Arial, sans-serif';
      ctx.fillText('You paid full price. Once approved, overpaid', tx, ty);
      ty += 12;
      ctx.fillText('amount can be claimed at the cashier.', tx, ty);
      ty += 16;
    }

    ty += 8;
    ctx.strokeStyle = '#ccc';
    ctx.setLineDash([4, 4]);
    ctx.beginPath();
    ctx.moveTo(tx, ty);
    ctx.lineTo(ticketX + ticketW - 20, ty);
    ctx.stroke();
    ctx.setLineDash([]);
    ty += 24;

    ctx.fillStyle = labelGray;
    ctx.font = '600 10px Poppins, Arial, sans-serif';
    ctx.fillText('CUSTOMER NAME', tx, ty);
    ctx.fillStyle = '#111';
    ctx.font = '700 11px Poppins, Arial, sans-serif';
    ctx.fillText(String(booking.customer_name || '—').toUpperCase(), tx, ty + 16);
    ctx.fillStyle = labelGray;
    ctx.fillText('TICKET PRICE', tx, ty + 40);
    ctx.fillStyle = '#111';
    ctx.fillText('P' + totalAmount.toFixed(2), tx, ty + 56);
    ctx.textAlign = 'right';
    ctx.fillStyle = labelGray;
    ctx.fillText('SEAT NUMBER', ticketX + ticketW - 20, ty);
    ctx.fillStyle = '#111';
    ctx.font = '700 16px Poppins, Arial, sans-serif';
    ctx.fillText(lastSeat, ticketX + ticketW - 20, ty + 36);
    ctx.textAlign = 'left';
    ty += 72;

    ctx.fillStyle = '#111';
    ctx.font = '700 12px Poppins, Arial, sans-serif';
    ctx.textAlign = 'center';
    ctx.fillText('CINEFLIX', ticketX + ticketW / 2, ty + 14);

    const dataUrl = canvas.toDataURL('image/png');
    const link = document.createElement('a');
    link.href = dataUrl;
    link.download = `Ticket_${booking.booking_id}.png`;
    document.body.appendChild(link);
    link.click();
    link.remove();
  };
  qrImg.onerror = () => alert('Unable to generate ticket QR code.');
  qrImg.src = qrDataUrl;
}

async function requestRefund(bookingId) {
  const booking = bookingStore.get(bookingId);
  if (!booking) {
    alert('Booking not found.');
    return;
  }

  // Verify this booking belongs to the current user
  if (typeof currentUserId !== 'undefined' && currentUserId !== null) {
    if (booking.customer_id && parseInt(booking.customer_id) !== parseInt(currentUserId)) {
      alert('You can only request refunds for your own bookings.');
      return;
    }
  }

  if (String(booking.status).toLowerCase() !== 'paid') {
    alert('Refunds are only available for paid bookings.');
    return;
  }

  const confirmRefund = confirm(
    `Are you sure you want to request a refund for this booking?\n\n` +
    `${booking.item_name}\n` +
    `Date: ${formatDateLabel(booking.show_date)} | Time: ${booking.show_time || 'N/A'}\n` +
    `Amount: ₱${Number(booking.total_amount || 0).toFixed(2)}`
  );

  if (!confirmRefund) return;

  const reason = prompt('Please provide a reason for cancellation (optional):') || 'No reason provided';

  try {
    const response = await fetch('api/request_refund.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ bookingId, reason })
    });

    const data = await response.json();
    if (!response.ok || !data.success) {
      throw new Error(data.error || 'Unable to request refund.');
    }

    alert('Refund request submitted successfully! Your refund will be processed within 3-5 business days.');
    await refreshBookings();
    const cancelledTab = document.querySelector('[data-tab="cancelled"]');
    if (cancelledTab) {
      cancelledTab.click();
    }
  } catch (err) {
    console.error(err);
    alert('We could not submit your refund request. Please try again.');
  }
}

function formatDateLabel(value) {
  if (!value) return 'N/A';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) {
    return value;
  }
  return date.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
}

function formatDateTimeLabel(value) {
  if (!value) return 'N/A';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) {
    return value;
  }
  return date.toLocaleString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: 'numeric',
    minute: '2-digit'
  });
}

function formatStatus(status) {
  if (!status) return 'N/A';
  return String(status)
    .toLowerCase()
    .split(' ')
    .map(word => word.charAt(0).toUpperCase() + word.slice(1))
    .join(' ');
}