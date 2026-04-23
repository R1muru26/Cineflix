// Booking data storage
let bookingData = {
  movie: '',
  moviePoster: '',
  itemType: 'movie',
  eventOption: null,
  schedule: '',
  cinema: '',
  date: '',
  theatreType: 'Standard',
  quantity: 1,
  seats: [],
  price: 350, // Default price for Standard theatre
  ticketTotal: 0,
  addons: [], // [{id, name, price, qty}]
  addonsTotal: 0,
  total: 0,
  bookingId: '',
  source: 'online',
  discountType: null,
  discountPercent: 0,
  discountOriginalTicketTotal: null,
  discountedTicketTotal: null,
  discountAmount: null,
  discountIdNumber: null,
  discountFile: null,
  amountPaid: 0,
  change: 0,
  parkingNumber: null,
  hasVehicle: null,  // true = Yes, false = None
  vehiclePlate: null,
  vehicleType: null,
  vehicleColor: null
};

// Detect if we are in staff walk-in mode
const IS_STAFF_WALKIN = typeof window !== 'undefined' && window.isStaffWalkin === true;

// Seat layout constants
const SEAT_ROWS = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
const SEATS_PER_ROW = 10;
function getTotalSeats() { return SEAT_ROWS.length * SEATS_PER_ROW; }

// Parking fee in pesos (when user has vehicle)
const PARKING_FEE = 50;

function getEventOptionLabel(option) {
  if (!option) return 'Event Experience';
  const normalized = String(option).toLowerCase();
  const labels = {
    meet: 'Meet & Greet',
    'meet-and-greet': 'Meet & Greet',
    screening: 'Special Screening',
    'special-screening': 'Special Screening'
  };
  if (labels[normalized]) {
    return labels[normalized];
  }
  return normalized.split(/\s|-/).map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
}

function formatDisplayDate(dateString) {
  if (!dateString) return 'Date To Be Announced';
  const date = new Date(dateString);
  if (Number.isNaN(date.getTime())) {
    return dateString;
  }
  return date.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
}

function safeDecode(value) {
  if (value === null || value === undefined) return '';
  try {
    return decodeURIComponent(value);
  } catch (err) {
    return value;
  }
}

// Theatre Types and Prices
const THEATRE_TYPES = [
  { id: 'standard', name: 'Standard', price: 350 },
  { id: 'imax', name: 'IMAX', price: 520 },
  { id: '3d', name: '3D', price: 420 },
  { id: 'directors_club', name: 'Directors Club', price: 650 }
];

function generateTheatreTypes() {
  const container = document.getElementById('theatre-options-container');
  if (!container) return;

  container.innerHTML = THEATRE_TYPES.map(type => `
    <div class="theatre-option ${bookingData.theatreType === type.name ? 'active' : ''}" 
         data-type="${type.name}" data-price="${type.price}">
      <div class="theatre-radio"></div>
      <div class="theatre-name">${type.name}</div>
    </div>
  `).join('');

  document.querySelectorAll('.theatre-option').forEach(option => {
    option.addEventListener('click', function() {
      document.querySelectorAll('.theatre-option').forEach(o => o.classList.remove('active'));
      this.classList.add('active');
      
      bookingData.theatreType = this.dataset.type;
      bookingData.price = parseFloat(this.dataset.price);
      
      // Update summary fields if they exist
      const summaryTheatre2 = document.getElementById('summary-theatre-type-2');
      if (summaryTheatre2) summaryTheatre2.textContent = bookingData.theatreType;
      
      updateSeatsDisplay();
      console.log('Theatre type selected:', bookingData.theatreType, 'Price:', bookingData.price);
      
      generateSchedules();
    });
  });
}

// Initialize booking page
document.addEventListener('DOMContentLoaded', function() {
  if (!IS_STAFF_WALKIN && typeof isLoggedIn !== 'undefined' && !isLoggedIn) {
    return;
  }
  
  // Get booking data from URL parameters
  const urlParams = new URLSearchParams(window.location.search);
  const typeParam = urlParams.get('type');
  if (typeParam === 'event') {
    bookingData.itemType = 'event';
  }

  const movieTitle = urlParams.get('movie');
  const moviePoster = urlParams.get('poster');
  const eventTitleParam = urlParams.get('title');
  const eventImageParam = urlParams.get('image');
  const eventDateParam = urlParams.get('date');
  const eventTimeParam = urlParams.get('time');
  const eventLocationParam = urlParams.get('location');
  const eventOptionParam = urlParams.get('option');

  // In staff walk-in mode, allow choosing movie from a dropdown instead of URL
  if (IS_STAFF_WALKIN) {
    const selectEl = document.getElementById('walkin-movie-select');
    if (selectEl) {
      // Upgrade the native select into a custom dropdown with posters.
      // (Native <select><option> cannot reliably render images across browsers.)
      (function initWalkinPosterDropdown() {
        if (selectEl.dataset.enhanced === '1') return;
        selectEl.dataset.enhanced = '1';

        const wrapper = document.createElement('div');
        wrapper.className = 'poster-select';

        const trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.className = 'poster-select-trigger';
        trigger.innerHTML = `
          <span class="poster-select-thumb"></span>
          <span class="poster-select-label">-- Choose a movie --</span>
          <span class="poster-select-caret">▾</span>
        `;

        const menu = document.createElement('div');
        menu.className = 'poster-select-menu';

        const options = Array.from(selectEl.options || []).filter(o => (o.value || '').trim() !== '');
        menu.innerHTML = options.map(o => {
          const title = o.value;
          const poster = o.getAttribute('data-poster') || '';
          const safeTitle = title.replace(/"/g, '&quot;');
          const safePoster = poster.replace(/"/g, '&quot;');
          return `
            <button type="button" class="poster-select-item" data-value="${safeTitle}" data-poster="${safePoster}">
              <img src="${safePoster}" alt="" loading="lazy" />
              <span>${safeTitle}</span>
            </button>
          `;
        }).join('');

        selectEl.style.display = 'none';
        selectEl.parentElement?.insertBefore(wrapper, selectEl);
        wrapper.appendChild(trigger);
        wrapper.appendChild(menu);
        wrapper.appendChild(selectEl);

        function closeMenu() {
          wrapper.classList.remove('open');
        }
        function openMenu() {
          wrapper.classList.add('open');
        }
        function setTrigger(title, poster) {
          const label = trigger.querySelector('.poster-select-label');
          const thumb = trigger.querySelector('.poster-select-thumb');
          if (label) label.textContent = title || '-- Choose a movie --';
          if (thumb) {
            if (poster) {
              thumb.innerHTML = `<img src="${poster}" alt="" />`;
            } else {
              thumb.innerHTML = '';
            }
          }
        }

        trigger.addEventListener('click', () => {
          wrapper.classList.contains('open') ? closeMenu() : openMenu();
        });
        menu.addEventListener('click', (e) => {
          const btn = e.target.closest('.poster-select-item');
          if (!btn) return;
          const value = btn.getAttribute('data-value') || '';
          const poster = btn.getAttribute('data-poster') || '';
          selectEl.value = value;
          setTrigger(value, poster);
          // Dispatch a change so the existing booking flow updates title + poster.
          selectEl.dispatchEvent(new Event('change', { bubbles: true }));
          closeMenu();
        });
        document.addEventListener('click', (e) => {
          if (!wrapper.contains(e.target)) closeMenu();
        });

        // Initial state (if something is already selected)
        const initialOpt = selectEl.selectedOptions && selectEl.selectedOptions[0];
        if (initialOpt && initialOpt.value) {
          setTrigger(initialOpt.value, initialOpt.getAttribute('data-poster') || '');
        }
      })();

      selectEl.addEventListener('change', function () {
        const title = this.value;
        const selected = this.selectedOptions && this.selectedOptions[0];
        const poster = selected ? (selected.getAttribute('data-poster') || '') : '';
        if (!title) {
          bookingData.movie = 'Select a movie';
          setContinueButtonEnabled(false);
        } else {
          bookingData.movie = title;
          setContinueButtonEnabled(true);
        }
        bookingData.moviePoster = poster || bookingData.moviePoster;
        bookingData.itemType = 'movie';
        updateMovieInfo();
      });
      if (selectEl.value) {
        bookingData.movie = selectEl.value;
        const selected = selectEl.selectedOptions && selectEl.selectedOptions[0];
        if (selected) {
          bookingData.moviePoster = selected.getAttribute('data-poster') || bookingData.moviePoster;
        }
      }
    }
  }

  if (bookingData.itemType === 'event') {
    const titleSeed = eventTitleParam || movieTitle || 'CineFlix Event';
    bookingData.movie = safeDecode(titleSeed);
    bookingData.moviePoster = eventImageParam || moviePoster || '';
    bookingData.eventOption = eventOptionParam || bookingData.eventOption;
    bookingData.schedule = eventTimeParam || bookingData.schedule;
    bookingData.cinema = eventLocationParam || bookingData.cinema;
  } else {
    bookingData.movie = safeDecode(movieTitle || 'Spider-Man: No Way Home');
    bookingData.moviePoster = safeDecode(moviePoster || '');
  }

  // Update movie info
  updateMovieInfo();

  // ── Custom Date Picker (fixed year 2026) ─────────────────────────────
  const FIXED_YEAR = new Date().getFullYear();
  const MONTH_NAMES = ['January','February','March','April','May','June','July','August','September','October','November','December'];
  function pad2(n) { return String(n).padStart(2, '0'); }
  function parseYMD(ymd) {
    if (!ymd) return null;
    const m = String(ymd).match(/^(\d{4})-(\d{2})-(\d{2})$/);
    if (!m) return null;
    return { year: Number(m[1]), month: Number(m[2]), day: Number(m[3]) };
  }
  function daysInMonth(monthIndex0) { return new Date(FIXED_YEAR, monthIndex0 + 1, 0).getDate(); }
  function clampDay(monthIndex0, day) {
    const dim = daysInMonth(monthIndex0);
    const d = Number(day);
    if (!Number.isFinite(d) || d < 1) return 1;
    return Math.min(d, dim);
  }
  function isoFromParts(monthIndex0, day) {
    return `${FIXED_YEAR}-${pad2(monthIndex0 + 1)}-${pad2(day)}`;
  }
  function formatMMDDYYYY(monthIndex0, day) {
    return `${pad2(monthIndex0 + 1)}/${pad2(day)}/${FIXED_YEAR}`;
  }

  // ── Date Selection Logic ──────────────────────────────────────────
  const dateInput = document.getElementById('booking-date');
  const dateTrigger = document.getElementById('booking-date-trigger');
  const modalBackdrop = document.getElementById('booking-date-modal-backdrop');
  const daysGrid = document.getElementById('booking-date-days-grid');
  const monthSelect = document.getElementById('booking-date-month-select');
  const prevBtn = document.getElementById('booking-date-prev');
  const nextBtn = document.getElementById('booking-date-next');
  const clearBtn = document.getElementById('booking-date-clear');
  const displayText = document.getElementById('booking-date-display');
  const displayInput = document.getElementById('booking-date-display-input');
  const seatsHint = document.getElementById('booking-date-seats-hint');

  // Default selection: today's date
  const _today = new Date();
  let selectedMonthIndex0 = _today.getMonth();
  let selectedDay = _today.getDate();
  let hasSelection = true;
  let viewMonthIndex0 = selectedMonthIndex0;

  const initialISO = isoFromParts(selectedMonthIndex0, selectedDay);
  bookingData.date = initialISO;
  if (dateInput) dateInput.value = initialISO;
  if (monthSelect) monthSelect.value = String(selectedMonthIndex0);
  if (seatsHint) seatsHint.textContent = `${getTotalSeats()} seats available`;

  const initialDisplay = formatMMDDYYYY(selectedMonthIndex0, selectedDay);
  if (displayText) displayText.textContent = initialDisplay;
  if (displayInput) displayInput.value = initialDisplay;

  function renderCalendar() {
    if (!daysGrid) return;
    const firstDayIdx = new Date(FIXED_YEAR, viewMonthIndex0, 1).getDay(); // 0=Sun
    const dim = daysInMonth(viewMonthIndex0);
    const totalCells = 42; // 6 weeks

    // Compute today's date for comparison
    const now = new Date();
    const todayY = now.getFullYear();
    const todayM = now.getMonth(); // 0-indexed
    const todayD = now.getDate();

    daysGrid.innerHTML = '';
    for (let i = 0; i < totalCells; i++) {
      const dayNum = i - firstDayIdx + 1;
      if (dayNum < 1 || dayNum > dim) {
        const empty = document.createElement('div');
        empty.className = 'booking-date-day is-empty';
        empty.setAttribute('aria-hidden', 'true');
        daysGrid.appendChild(empty);
        continue;
      }

      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'booking-date-day';
      btn.textContent = String(dayNum);
      btn.dataset.day = String(dayNum);

      // Determine if this cell is in the past
      const cellDate = new Date(FIXED_YEAR, viewMonthIndex0, dayNum);
      const isToday = (FIXED_YEAR === todayY && viewMonthIndex0 === todayM && dayNum === todayD);
      const isPast  = cellDate < new Date(todayY, todayM, todayD);

      if (isPast) {
        btn.classList.add('day-disabled');
        btn.disabled = true;
        btn.setAttribute('aria-disabled', 'true');
      } else if (isToday) {
        btn.classList.add('day-today');
      }

      if (hasSelection && selectedMonthIndex0 === viewMonthIndex0 && selectedDay === dayNum) {
        btn.classList.add('selected');
      }

      if (!isPast) {
        btn.addEventListener('click', function () {
          setSelectedDate(viewMonthIndex0, dayNum, { dispatch: true, close: true });
        });
      }

      daysGrid.appendChild(btn);
    }
  }

  function openModal() {
    if (!modalBackdrop) return;
    modalBackdrop.style.display = 'flex';
    modalBackdrop.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
  }

  function closeModal() {
    if (!modalBackdrop) return;
    modalBackdrop.style.display = 'none';
    modalBackdrop.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
  }

  function setDisplayDate(monthIndex0, day) {
    const v = formatMMDDYYYY(monthIndex0, day);
    if (displayText) displayText.textContent = v;
    if (displayInput) displayInput.value = v;
  }

  function clearSelection() {
    hasSelection = false;
    bookingData.date = '';
    if (dateInput) dateInput.value = '';
    if (displayText) displayText.textContent = 'Select date';
    if (displayInput) displayInput.value = '';
    renderCalendar();
    if (window.selectedTheatreType && typeof generateSchedules === 'function') {
      generateSchedules();
    }
  }

  function setSelectedDate(monthIndex0, day, opts) {
    opts = opts || {};
    hasSelection = true;
    selectedMonthIndex0 = monthIndex0;
    selectedDay = clampDay(selectedMonthIndex0, day);
    viewMonthIndex0 = selectedMonthIndex0;

    if (monthSelect) monthSelect.value = String(selectedMonthIndex0);
    if (dateInput) dateInput.value = isoFromParts(selectedMonthIndex0, selectedDay);
    bookingData.date = dateInput ? dateInput.value : isoFromParts(selectedMonthIndex0, selectedDay);

    setDisplayDate(selectedMonthIndex0, selectedDay);
    renderCalendar();

    if (opts.dispatch) {
      console.log('Date selection updated:', bookingData.date);
      // Only re-render schedules if a theatre type is already selected
      if (window.selectedTheatreType && typeof generateSchedules === 'function') {
        generateSchedules();
      }
    }
    if (opts.close) closeModal();
  }

  // Hook modal interactions.
  if (dateTrigger) {
    dateTrigger.addEventListener('click', function () { openModal(); });
  }
  if (modalBackdrop) {
    modalBackdrop.addEventListener('click', function (e) {
      if (e.target === modalBackdrop) closeModal();
    });
  }
  if (clearBtn) {
    clearBtn.addEventListener('click', function () {
      clearSelection();
    });
  }
  if (prevBtn) {
    prevBtn.addEventListener('click', function () {
      const newMonth = (viewMonthIndex0 + 11) % 12;
      if (hasSelection) setSelectedDate(newMonth, selectedDay, { dispatch: true, close: false });
      else { viewMonthIndex0 = newMonth; renderCalendar(); }
    });
  }
  if (nextBtn) {
    nextBtn.addEventListener('click', function () {
      const newMonth = (viewMonthIndex0 + 1) % 12;
      if (hasSelection) setSelectedDate(newMonth, selectedDay, { dispatch: true, close: false });
      else { viewMonthIndex0 = newMonth; renderCalendar(); }
    });
  }
  if (monthSelect) {
    monthSelect.addEventListener('change', function () {
      const newMonth = Number(this.value);
      if (!Number.isFinite(newMonth)) return;
      if (hasSelection) setSelectedDate(newMonth, selectedDay, { dispatch: true, close: false });
      else { viewMonthIndex0 = newMonth; renderCalendar(); }
    });
  }
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeModal();
  });

  renderCalendar();
  // Don't call generateSchedules() on init — schedules only render after a theatre type pill is selected
  setupEventListeners();
});

function updateMovieInfo() {
  const displayTitle = bookingData.itemType === 'event'
    ? `${bookingData.movie} (${getEventOptionLabel(bookingData.eventOption)})`
    : bookingData.movie;

  const selectedMovieEl = document.getElementById('selected-movie');
  if (selectedMovieEl) {
    selectedMovieEl.textContent = displayTitle;
  }

  document.querySelectorAll('[id^="summary-movie"]').forEach(el => {
    el.textContent = displayTitle;
  });
  
  if (bookingData.moviePoster) {
    const posterUrl = safeDecode(bookingData.moviePoster);
    const posterEl = document.getElementById('movie-poster');
    if (posterEl) {
      posterEl.style.backgroundImage = `url("${posterUrl}")`;
      const existing = posterEl.querySelector('img');
      if (existing) {
        existing.src = posterUrl;
        existing.alt = bookingData.movie + ' poster';
      } else {
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
  
  const metaEl = document.getElementById('movie-meta');
  if (!metaEl) return;
  if (bookingData.itemType === 'event') {
    const formattedDate = formatDisplayDate(bookingData.date);
    const venueLabel = bookingData.cinema || 'Venue To Be Announced';
    metaEl.textContent = `${getEventOptionLabel(bookingData.eventOption)} • ${formattedDate} • ${venueLabel}`;
  } else {
    metaEl.textContent = 'Action • 2h 30m';
  }
}

// Helper function to enable/disable the continue button
function setContinueButtonEnabled(enabled) {
  const btn = document.getElementById('continue-to-seats');
  if (!btn) {
    console.error('Continue button not found!');
    return;
  }
  
  if (enabled) {
    // Remove disabled attribute
    btn.removeAttribute('disabled');
    // Set disabled property to false
    btn.disabled = false;
    // Ensure button is visible and clickable
    btn.style.opacity = '1';
    btn.style.cursor = 'pointer';
    btn.style.pointerEvents = 'auto';
    console.log('Button enabled');
  } else {
    // Disable button
    btn.disabled = true;
    btn.setAttribute('disabled', 'disabled');
    btn.style.opacity = '0.5';
    btn.style.cursor = 'not-allowed';
    console.log('Button disabled');
  }
}

// Real-time schedule refresh interval handle
let _scheduleRefreshInterval = null;

function isSchedulePassed(startTime24, selectedDate) {
  // Returns true if current time is >= startTime - 30 minutes (lock 30min before show)
  const now = new Date();
  const isToday = new Date(selectedDate + 'T00:00:00').toDateString() === now.toDateString();
  if (!isToday) return false;
  const [hours, minutes] = startTime24.split(':').map(Number);
  const showtime = new Date();
  showtime.setHours(hours, minutes, 0, 0);
  // Disable 30 minutes before showtime
  const cutoff = new Date(showtime.getTime() - 30 * 60 * 1000);
  return now >= cutoff;
}

// Schedules per theatre type
const SCHEDULES_BY_TYPE = {
  'Standard': [
    { time: '10:00 AM - 12:05 PM', cinema: 'Cinema: Standard', start: '10:00', price: 350 },
    { time: '12:30 PM - 2:35 PM',  cinema: 'Cinema: Standard', start: '12:30', price: 350 },
    { time: '3:00 PM - 5:05 PM',   cinema: 'Cinema: Standard', start: '15:00', price: 350 },
    { time: '5:30 PM - 7:35 PM',   cinema: 'Cinema: Standard', start: '17:30', price: 350 },
  ],
  '3D': [
    { time: '10:30 AM - 12:35 PM', cinema: 'Cinema: 3D', start: '10:30', price: 420 },
    { time: '1:00 PM - 3:05 PM',   cinema: 'Cinema: 3D', start: '13:00', price: 420 },
    { time: '3:30 PM - 5:35 PM',   cinema: 'Cinema: 3D', start: '15:30', price: 420 },
    { time: '6:00 PM - 8:05 PM',   cinema: 'Cinema: 3D', start: '18:00', price: 420 },
  ],
  'IMAX': [
    { time: '11:00 AM - 1:05 PM',  cinema: 'Cinema: IMAX', start: '11:00', price: 520 },
    { time: '1:30 PM - 3:35 PM',   cinema: 'Cinema: IMAX', start: '13:30', price: 520 },
    { time: '4:00 PM - 6:05 PM',   cinema: 'Cinema: IMAX', start: '16:00', price: 520 },
    { time: '7:30 PM - 9:35 PM',   cinema: 'Cinema: IMAX', start: '19:30', price: 520 },
  ],
  'Directors Club': [
    { time: '12:00 PM - 2:05 PM',  cinema: 'Cinema: Directors Club', start: '12:00', price: 650 },
    { time: '2:30 PM - 4:35 PM',   cinema: 'Cinema: Directors Club', start: '14:30', price: 650 },
    { time: '5:00 PM - 7:05 PM',   cinema: 'Cinema: Directors Club', start: '17:00', price: 650 },
    { time: '8:00 PM - 10:05 PM',  cinema: 'Cinema: Directors Club', start: '20:00', price: 650 },
  ],
};

function generateSchedules() {
  const schedulesContainer = document.getElementById('schedule-cards');
  if (!schedulesContainer) return;

  const dateValue = document.getElementById('booking-date') ? document.getElementById('booking-date').value : '';

  // Determine active theatre type ONLY from the checked pill — never fall back to bookingData defaults
  const checkedPill = document.querySelector('input[name="theatre-type"]:checked');
  const activeType = checkedPill ? checkedPill.value : (window.selectedTheatreType || null);

  if (!dateValue) {
    schedulesContainer.innerHTML = `
      <div class="new-schedule-card" style="grid-column: 1 / -1; cursor: default; opacity: .95; text-align: center; padding: 2rem;">
        <div class="card-time" style="color: #fff; font-size: 1.2rem;">Select a date</div>
        <div class="card-cinema">Choose a date above to see available schedules.</div>
      </div>`;
    setContinueButtonEnabled(false);
    return;
  }

  if (!activeType) {
    schedulesContainer.innerHTML = '';
    setContinueButtonEnabled(false);
    return;
  }

  const schedules = SCHEDULES_BY_TYPE[activeType] || [];
  const seatsIcon = `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="opacity:0.6"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>`;
  const passedIcon = `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>`;

  schedulesContainer.innerHTML = schedules.map((schedule) => {
    const isPassed = isSchedulePassed(schedule.start, dateValue);
    return `
    <div class="new-schedule-card ${isPassed ? 'passed' : ''}" 
         data-schedule="${schedule.time}" 
         data-cinema="${schedule.cinema}"
         data-price="${schedule.price}"
         data-type="${activeType}"
         data-start="${schedule.start}">
      <div class="card-time">${schedule.time}</div>
      <div class="card-cinema">${schedule.cinema}</div>
      <div class="price-pill">₱${schedule.price} / ticket</div>
      <div class="card-status">
        ${isPassed
          ? `${passedIcon} Showtime already passed`
          : `${seatsIcon} <span class="availability-label">${getTotalSeats()} seats available</span>`
        }
      </div>
    </div>`;
  }).join('');

  // Attach click handlers to non-passed cards
  document.querySelectorAll('.new-schedule-card:not(.passed)').forEach(card => {
    card.addEventListener('click', function() {
      document.querySelectorAll('.new-schedule-card').forEach(c => c.classList.remove('selected'));
      this.classList.add('selected');
      bookingData.schedule = this.dataset.schedule;
      bookingData.cinema = this.dataset.cinema;
      bookingData.price = parseFloat(this.dataset.price) || 350;
      bookingData.theatreType = this.dataset.type || bookingData.theatreType;
      bookingData.date = dateValue;
      bookingData.seats = [];
      setContinueButtonEnabled(true);
    });
  });

  // Fetch reserved seat counts asynchronously
  document.querySelectorAll('.new-schedule-card:not(.passed)').forEach(async card => {
    const time = card.dataset.schedule;
    const cinemaText = card.dataset.cinema;
    try {
      const reserved = await fetchReservedSeats(bookingData.movie, dateValue, time, cinemaText);
      const totalSeats = getTotalSeats();
      const available = Math.max(0, totalSeats - reserved.size);
      const label = card.querySelector('.availability-label');
      if (label) label.textContent = `${available} seats available`;
    } catch(e) { /* ignore */ }
  });

  // Real-time refresh every 60s to auto-expire passed cards
  if (_scheduleRefreshInterval) clearInterval(_scheduleRefreshInterval);
  _scheduleRefreshInterval = setInterval(function() {
    document.querySelectorAll('.new-schedule-card').forEach(card => {
      const start = card.dataset.start;
      if (!start || !dateValue) return;
      const passed = isSchedulePassed(start, dateValue);
      if (passed && !card.classList.contains('passed')) {
        card.classList.add('passed');
        card.classList.remove('selected');
        const statusEl = card.querySelector('.card-status');
        if (statusEl) statusEl.innerHTML = `${passedIcon} Showtime already passed`;
        const timePill = card.querySelector('.card-time');
        if (timePill) timePill.style.color = 'rgba(255,255,255,0.4)';
        const pricePill = card.querySelector('.price-pill');
        if (pricePill) { pricePill.style.background = 'rgba(255,255,255,0.1)'; pricePill.style.color = 'rgba(255,255,255,0.4)'; pricePill.style.boxShadow = 'none'; }
        if (bookingData.schedule === card.dataset.schedule) {
          bookingData.schedule = '';
          setContinueButtonEnabled(false);
        }
      }
    });
  }, 60000);
}

let _seatGridListenerAttached = false;

async function generateSeats() {
  const seatsGrid = document.getElementById('seats-grid');
  if (!seatsGrid) {
    console.error('Seats grid element not found!');
    return;
  }
  
  const rows = SEAT_ROWS;
  const seatsPerRow = SEATS_PER_ROW;

  const date = bookingData.date || document.getElementById('booking-date')?.value || '';
  const reservedSeats = await fetchReservedSeats(bookingData.movie, date, bookingData.schedule, bookingData.cinema);
  _lastReservedSeatsCache = reservedSeats;
  
  let seatsHTML = '';
  rows.forEach((row, rowIdx) => {
    for (let i = 1; i <= seatsPerRow; i++) {
      const seatId = `${row}${i}`;
      const isReserved = reservedSeats.has(seatId);
      seatsHTML += `<div class="seat ${isReserved ? 'reserved' : 'available'}" data-seat="${seatId}" data-reserved="${isReserved ? '1' : '0'}">${seatId}</div>`;
      if (i === Math.floor(seatsPerRow / 2)) {
        seatsHTML += `<div class="aisle-vertical"></div>`;
      }
    }
    if (rowIdx === Math.floor(rows.length / 2) - 1) {
      seatsHTML += `<div class="aisle-horizontal"></div>`;
    }
  });

  seatsGrid.innerHTML = seatsHTML;

  // Attach delegation listener only ONCE — replacing innerHTML doesn't remove it if on parent
  if (!_seatGridListenerAttached) {
    _seatGridListenerAttached = true;
    seatsGrid.addEventListener('click', function(e) {
      const seat = e.target.closest('.seat');
      if (!seat) return;
      if (seat.dataset.reserved === '1' || seat.classList.contains('reserved')) return;
      toggleSeat(seat);
    });
  }
}

// Reserved seats persistence helpers
// Reserved seats helpers (server-backed)
async function fetchReservedSeats(movie, date, schedule, venue) {
  try {
    const params = new URLSearchParams({
      itemName: movie || '',
      itemType: bookingData.itemType || 'movie',
      showDate: date || '',
      showTime: schedule || ''
    });
    if (venue) params.append('venue', venue);
    const res = await fetch('api/reserved_seats.php?' + params.toString());
    if (!res.ok) return new Set();
    const data = await res.json();
    if (!data.success || !Array.isArray(data.seats)) return new Set();
    return new Set(data.seats);
  } catch (e) {
    console.error('Failed to fetch reserved seats', e);
    return new Set();
  }
}

// Synchronous wrapper used by existing code; note that seat generation
// is triggered *after* schedule selection, so we can temporarily block
// using a simple async XHR pattern converted to sync via async/await
// in the caller functions.
let _lastReservedSeatsCache = new Set();
function getReservedSeatsCache() {
  return _lastReservedSeatsCache;
}

function getReservedCountForShow(movie, date, schedule) {
  // This is only used for display; use last-known cache if available.
  return getReservedSeatsCache().size;
}

function toggleSeat(seatElement) {
  const seatId = seatElement.dataset.seat;
  if (!seatId) return;

  // If seat is suggested, treat click as "select it"
  if (seatElement.classList.contains('suggested')) {
    seatElement.classList.remove('suggested', 'available');
    seatElement.classList.add('selected');
    if (!bookingData.seats.includes(seatId)) bookingData.seats.push(seatId);
    updateSeatsDisplay();
    return;
  }

  if (seatElement.classList.contains('selected')) {
    seatElement.classList.remove('selected');
    seatElement.classList.add('available');
    bookingData.seats = bookingData.seats.filter(s => s !== seatId);
  } else if (seatElement.classList.contains('available')) {
    seatElement.classList.remove('available');
    seatElement.classList.add('selected');
    bookingData.seats.push(seatId);
  }

  // Clear any remaining suggestions when user manually picks
  clearSuggestedSeats();
  updateSeatsDisplay();
}

// ── Smart Seat Suggester ─────────────────────────────────────────────
const SEAT_ROWS_ALL = ['A','B','C','D','E','F','G','H'];
const SEATS_PER_ROW_ALL = 10;

function clearSuggestedSeats() {
  document.querySelectorAll('.seat.suggested').forEach(s => {
    s.classList.remove('suggested');
  });
}

/**
 * Returns a list of available seat IDs based on mode.
 * Seat grid: rows A-H (A = front, H = back), cols 1-10
 * "Best center" = rows D-E, cols 4-7 (mid theatre, center)
 */
function getSuggestedSeats(mode, count, reservedSet) {
  const rows = SEAT_ROWS_ALL;
  const cols = SEATS_PER_ROW_ALL;

  function isAvail(r, c) {
    const id = `${r}${c}`;
    return !reservedSet.has(id);
  }

  // Find contiguous available seats in a row
  function findContiguous(row, colStart, colEnd, n) {
    const candidates = [];
    for (let c = colStart; c <= colEnd - n + 1; c++) {
      const group = [];
      for (let i = 0; i < n; i++) {
        if (isAvail(row, c + i)) group.push(`${row}${c + i}`);
        else break;
      }
      if (group.length === n) candidates.push(group);
    }
    return candidates;
  }

  if (mode === 'solo') {
    // Best single center seat: rows D-F, col 5 or 6
    const preferRows = ['E','D','F','C','G'];
    const preferCols = [5,6,4,7,3,8];
    for (const r of preferRows) {
      for (const c of preferCols) {
        if (isAvail(r, c)) return [`${r}${c}`];
      }
    }
  }

  if (mode === 'date') {
    // Romantic: mid-back rows, center pair
    const preferRows = ['F','G','E','D'];
    for (const r of preferRows) {
      const groups = findContiguous(r, 4, 7, 2);
      if (groups.length) return groups[Math.floor(groups.length / 2)]; // center-most pair
    }
  }

  if (mode === 'companion') {
    // Good view: center rows, center area
    const preferRows = ['D','E','C','F'];
    for (const r of preferRows) {
      const groups = findContiguous(r, 3, 8, 2);
      if (groups.length) return groups[Math.floor(groups.length / 2)];
    }
  }

  if (mode === 'group') {
    // 4 together, center rows
    const preferRows = ['D','E','C','F','G'];
    for (const r of preferRows) {
      const groups = findContiguous(r, 2, 9, 4);
      if (groups.length) return groups[Math.floor(groups.length / 2)];
    }
  }

  if (mode === 'private') {
    // Back corner away from crowd: row G or H, cols 1-2 or 9-10
    const corners = [
      ['H', 1], ['H', 2], ['G', 1], ['G', 2],
      ['H', 9], ['H', 10], ['G', 9], ['G', 10],
    ];
    const result = [];
    for (const [r, c] of corners) {
      if (isAvail(r, c)) { result.push(`${r}${c}`); if (result.length === count) return result; }
    }
    if (result.length) return result;
  }

  // Fallback: any available contiguous pair from best rows
  const fallbackRows = ['D','E','C','F','B','G','A','H'];
  for (const r of fallbackRows) {
    const groups = findContiguous(r, 1, cols, count);
    if (groups.length) return groups[0];
  }
  return [];
}

const MODE_MESSAGES = {
  solo:      (seats) => `🎬 Best solo spot: <strong>${seats.join(', ')}</strong> — dead center, perfect sightline.`,
  date:      (seats) => `💑 Perfect date seats: <strong>${seats.join(' & ')}</strong> — cozy, mid-back, just you two.`,
  companion: (seats) => `👫 Great companion seats: <strong>${seats.join(' & ')}</strong> — center row, clear view for both.`,
  group:     (seats) => `👥 Best group row: <strong>${seats.join(', ')}</strong> — all together, nobody craning their neck.`,
  private:   (seats) => `🌙 Most private seats: <strong>${seats.join(' & ')}</strong> — tucked away, just how you like it.`,
};

let _suggesterController = null;

function initSmartSuggester() {
  if (_suggesterController) { _suggesterController.abort(); }
  _suggesterController = new AbortController();
  const sig = { signal: _suggesterController.signal };

  const toggle     = document.getElementById('suggester-toggle');
  const panel      = document.getElementById('suggester-panel');
  const result     = document.getElementById('suggester-result');
  const resultText = document.getElementById('suggester-result-text');
  const applyBtn   = document.getElementById('suggester-apply');
  const dismissBtn = document.getElementById('suggester-dismiss');
  const modeBtns   = Array.from(document.querySelectorAll('.mode-btn'));

  if (!toggle || !panel) return;

  let currentSuggestion = [];

  toggle.addEventListener('click', function() {
    const isOpen = panel.classList.toggle('open');
    toggle.classList.toggle('open', isOpen);
    toggle.setAttribute('aria-expanded', String(isOpen));
    var span = toggle.querySelector('span');
    if (span) span.textContent = isOpen ? 'Hide' : 'Get Suggestion';
    if (!isOpen) {
      clearSuggestedSeats();
      if (result) result.style.display = 'none';
      modeBtns.forEach(function(b) { b.classList.remove('active'); });
    }
  }, sig);

  modeBtns.forEach(function(btn) {
    btn.addEventListener('click', function() {
      modeBtns.forEach(function(b) { b.classList.remove('active'); });
      btn.classList.add('active');

      var mode     = btn.dataset.mode;
      var count    = parseInt(btn.dataset.seats, 10) || 1;
      var reserved = (_lastReservedSeatsCache instanceof Set) ? _lastReservedSeatsCache : new Set();

      clearSuggestedSeats();

      var suggested = getSuggestedSeats(mode, count, reserved);
      currentSuggestion = suggested;

      if (!suggested.length) {
        if (resultText) resultText.innerHTML = 'No available seats found for this preference. Try another.';
        if (applyBtn)   applyBtn.style.display = 'none';
        if (result)     result.style.display = 'flex';
        return;
      }

      suggested.forEach(function(seatId) {
        var el = document.querySelector('.seat[data-seat="' + seatId + '"]');
        if (el && !el.classList.contains('reserved') && !el.classList.contains('selected')) {
          el.classList.add('suggested');
        }
      });

      var first = document.querySelector('.seat[data-seat="' + suggested[0] + '"]');
      if (first) first.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

      if (resultText) resultText.innerHTML = MODE_MESSAGES[mode](suggested);
      if (applyBtn)   applyBtn.style.display = '';
      if (result)     result.style.display = 'flex';
    }, sig);
  });

  if (applyBtn) {
    applyBtn.addEventListener('click', function() {
      bookingData.seats.forEach(function(seatId) {
        var el = document.querySelector('.seat[data-seat="' + seatId + '"]');
        if (el) { el.classList.remove('selected'); el.classList.add('available'); }
      });
      bookingData.seats = [];

      currentSuggestion.forEach(function(seatId) {
        var el = document.querySelector('.seat[data-seat="' + seatId + '"]');
        if (el && !el.classList.contains('reserved')) {
          el.classList.remove('available', 'suggested');
          el.classList.add('selected');
          bookingData.seats.push(seatId);
        }
      });

      clearSuggestedSeats();
      updateSeatsDisplay();

      panel.classList.remove('open');
      toggle.classList.remove('open');
      toggle.setAttribute('aria-expanded', 'false');
      if (result) result.style.display = 'none';
      modeBtns.forEach(function(b) { b.classList.remove('active'); });

      var span = toggle.querySelector('span');
      if (span) {
        span.textContent = '✅ Applied!';
        setTimeout(function() { span.textContent = 'Get Suggestion'; }, 2000);
      }
    }, sig);
  }

  if (dismissBtn) {
    dismissBtn.addEventListener('click', function() {
      clearSuggestedSeats();
      if (result) result.style.display = 'none';
      modeBtns.forEach(function(b) { b.classList.remove('active'); });
    }, sig);
  }
}

function updateSeatsDisplay() {
  const display = document.getElementById('selected-seats-display');
  const continueBtn = document.getElementById('continue-to-addons');
  const totalPriceEl = document.getElementById('total-price');
  
  if (display) {
    if (bookingData.seats.length === 0) {
      display.textContent = 'None';
    } else {
      display.textContent = bookingData.seats.join(', ');
    }
  }
  
  if (continueBtn) {
    continueBtn.disabled = bookingData.seats.length === 0;
  }
  
  // Calculate total based on number of seats selected
  bookingData.quantity = bookingData.seats.length;
  bookingData.ticketTotal = bookingData.seats.length * bookingData.price;
  bookingData.total = bookingData.ticketTotal + (bookingData.addonsTotal || 0);
  
  if (totalPriceEl) {
    totalPriceEl.textContent = bookingData.ticketTotal.toFixed(2);
  }
}

function setupEventListeners() {
  // Date change
  const bookingDateInput = document.getElementById('booking-date');
  if (bookingDateInput) {
    bookingDateInput.addEventListener('change', function() {
      bookingData.date = this.value;
      // Reset schedule and seats when date changes
      bookingData.schedule = '';
      bookingData.seats = [];
      setContinueButtonEnabled(false);
      generateSchedules();
    });
  }
  
  // Step 1: Continue to seats button click handler
  const continueToSeatsBtn = document.getElementById('continue-to-seats');
  if (continueToSeatsBtn) {
    // Use a named function for easier debugging
    function handleContinueToSeats(e) {
      e.preventDefault();
      e.stopPropagation();
      
      console.log('Continue button clicked');
      console.log('Schedule:', bookingData.schedule, 'Date:', bookingData.date);
      
      // Validate that schedule is selected
      if (!bookingData.schedule || !bookingData.date) {
        alert('Please select a schedule first');
        return;
      }
      
      // Update summary
      const scheduleText = `${bookingData.date} | ${bookingData.schedule}`;
      const summarySchedule2 = document.getElementById('summary-schedule-2');
      const summaryMovie2 = document.getElementById('summary-movie-2');
      const summaryTheatre2 = document.getElementById('summary-theatre-type-2');
      
      if (summarySchedule2) summarySchedule2.textContent = scheduleText;
      if (summaryMovie2) summaryMovie2.textContent = bookingData.movie;
      if (summaryTheatre2) summaryTheatre2.textContent = bookingData.theatreType;
      
      // Generate seats (await server seat reservations)
      generateSeats().then(() => {
        console.log('Seats generated with latest reservations');
        // Init suggester after step is visible so getElementById works
        initSmartSuggester();
      });
      
      // Navigate to seats step - use direct DOM manipulation for reliability
      document.querySelectorAll('.booking-step').forEach(step => {
        step.classList.remove('active');
      });
      const seatsStep = document.getElementById('step-seats');
      if (seatsStep) {
        seatsStep.classList.add('active');
        window.scrollTo({ top: 0, behavior: 'smooth' });
        console.log('Navigated to seat selection');
      } else {
        console.error('step-seats element not found!');
        alert('Error: Could not find seat selection step. Please refresh the page.');
      }
    }
    
    continueToSeatsBtn.addEventListener('click', handleContinueToSeats);
  } else {
    console.error('Continue to seats button not found during setup!');
  }
  
  // Step 2: Seats - Back to schedule
  const backToScheduleBtn = document.getElementById('back-to-schedule-seats');
  if (backToScheduleBtn) {
    backToScheduleBtn.addEventListener('click', function() {
      // Reset seat selection when going back
      bookingData.seats = [];
      generateSchedules();
      
      // Navigate back to schedule step using direct DOM manipulation
      document.querySelectorAll('.booking-step').forEach(step => {
        step.classList.remove('active');
      });
      const scheduleStep = document.getElementById('step-schedule');
      if (scheduleStep) {
        scheduleStep.classList.add('active');
        window.scrollTo({ top: 0, behavior: 'smooth' });
      }
    });
  }

  // After seats -> Addons
  const continueToAddonsBtn = document.getElementById('continue-to-addons');
  if (continueToAddonsBtn) {
    continueToAddonsBtn.addEventListener('click', function() {
      // Prep addons step summary
      const summaryMovieAddons = document.getElementById('summary-movie-addons');
      const summaryScheduleAddons = document.getElementById('summary-schedule-addons');
      const summarySeatsAddons = document.getElementById('summary-seats-addons');
      if (summaryMovieAddons) summaryMovieAddons.textContent = bookingData.movie;
      if (summaryScheduleAddons) summaryScheduleAddons.textContent = `${bookingData.date} | ${bookingData.schedule}`;
      if (summarySeatsAddons) summarySeatsAddons.textContent = bookingData.seats.join(', ');
      renderAddons();
      goToStep('step-addons');
    });
  }

  // Addons - Back to seats
  const backToSeatsAddonsBtn = document.getElementById('back-to-seats-addons');
  if (backToSeatsAddonsBtn) {
    backToSeatsAddonsBtn.addEventListener('click', function() {
      goToStep('step-seats');
    });
  }

  // Addons controls - online: go to vehicle step; staff: go to payment (no vehicle step)
  const skipAddonsBtn = document.getElementById('skip-addons');
  if (skipAddonsBtn) {
    skipAddonsBtn.addEventListener('click', function() {
      updateSummary(3);
      if (IS_STAFF_WALKIN) {
        goToStep('step-payment');
      } else {
        updateVehicleStepSummary();
        goToStep('step-vehicle');
      }
    });
  }

  const continueToAddonsPaymentBtn = document.getElementById('continue-to-vehicle');
  if (continueToAddonsPaymentBtn) {
    continueToAddonsPaymentBtn.addEventListener('click', function() {
      updateSummary(3);
      updateVehicleStepSummary();
      goToStep('step-vehicle');
    });
  }
  const continueToPaymentFromAddonsBtn = document.getElementById('continue-to-payment');
  if (continueToPaymentFromAddonsBtn) {
    continueToPaymentFromAddonsBtn.addEventListener('click', function() {
      updateSummary(3);
      goToStep('step-payment');
    });
  }

  // Vehicle step - Back to addons
  const backToAddonsVehicleBtn = document.getElementById('back-to-addons-vehicle');
  if (backToAddonsVehicleBtn) {
    backToAddonsVehicleBtn.addEventListener('click', function() {
      goToStep('step-addons');
    });
  }

  // Vehicle step - Yes / None selection (None = default if user proceeds without selecting)
  document.querySelectorAll('.vehicle-option').forEach(btn => {
    btn.addEventListener('click', function() {
      document.querySelectorAll('.vehicle-option').forEach(b => b.classList.remove('selected'));
      this.classList.add('selected');
      bookingData.hasVehicle = this.getAttribute('data-vehicle') === 'yes';
    });
  });

  // Vehicle step - Continue to Payment (enabled by default; if no selection, treat as None)
  const continueToPaymentFromVehicleBtn = document.getElementById('continue-to-payment-from-vehicle');
  if (continueToPaymentFromVehicleBtn) {
    continueToPaymentFromVehicleBtn.addEventListener('click', function() {
      if (bookingData.hasVehicle === null) bookingData.hasVehicle = false;

      if (bookingData.hasVehicle) {
        bookingData.vehiclePlate = (document.getElementById('vehicle-plate') || {}).value || null;
        bookingData.vehicleType  = (document.getElementById('vehicle-type')  || {}).value || null;
        bookingData.vehicleColor = (document.getElementById('vehicle-color') || {}).value || null;

        // Validate required fields
        if (!bookingData.vehiclePlate || !bookingData.vehicleType || !bookingData.vehicleColor) {
          alert('Please fill in your Plate Number, Vehicle Type, and Vehicle Color before continuing.');
          return;
        }
      } else {
        bookingData.vehiclePlate = null;
        bookingData.vehicleType  = null;
        bookingData.vehicleColor = null;
      }

      updateSummary(3);
      goToStep('step-payment');
    });
  }
  
  // Step 4: Payment - Back goes to vehicle (online) or addons (staff)
  const backToAddonsBtn = document.getElementById('back-to-addons');
  if (backToAddonsBtn) {
    backToAddonsBtn.addEventListener('click', function() {
      if (IS_STAFF_WALKIN) {
        goToStep('step-addons');
      } else {
        goToStep('step-vehicle');
      }
    });
  }
  
  // Payment method toggle
  const discountRadios = document.querySelectorAll('input[name="discount-type"]');
  const discountExtraFields = document.getElementById('discount-extra-fields');
  const discountProofGroup = document.getElementById('discount-proof-group');
  const discountProofInput = document.getElementById('discount-proof');
  const discountIdGroup = document.getElementById('discount-id-group');
  const discountIdInput = document.getElementById('discount-id-number');
  const paymentMethodSelect = document.getElementById('payment-method');
  const paymentDetailsPanel = document.getElementById('payment-details-panel');
  const providerCards = document.querySelectorAll('.payment-provider-card');
  const cashAmountInput = document.getElementById('cash-amount');
  const cashChangeDisplay = document.getElementById('cash-change');

  function updateDiscountFromUI() {
    let type = null;
    discountRadios.forEach(r => {
      if (r.checked) type = r.value;
    });
    bookingData.discountType = type;
    if (type === 'pwd' || type === 'senior') {
      bookingData.discountPercent = 20;
      if (discountExtraFields) discountExtraFields.classList.add('reveal-open');
      if (!IS_STAFF_WALKIN && discountProofGroup && discountProofInput) {
        discountProofGroup.style.display = 'block';
        discountProofInput.setAttribute('required', 'required');
      }
      if (discountIdGroup && discountIdInput) {
        discountIdGroup.style.display = 'block';
        discountIdInput.setAttribute('required', 'required');
      }
    } else {
      bookingData.discountPercent = 0;
      if (discountExtraFields) discountExtraFields.classList.remove('reveal-open');
      if (discountProofGroup && discountProofInput) {
        discountProofGroup.style.display = 'none';
        discountProofInput.removeAttribute('required');
        discountProofInput.value = '';
      }
      if (discountIdGroup && discountIdInput) {
        discountIdGroup.style.display = 'none';
        discountIdInput.removeAttribute('required');
        discountIdInput.value = '';
      }
      bookingData.discountIdNumber = null;
      bookingData.discountFile = null;
    }
    updateSummary(3);
  }

  if (discountRadios && discountRadios.length) {
    discountRadios.forEach(r => {
      r.addEventListener('change', updateDiscountFromUI);
    });
  }

  if (discountIdInput) {
    discountIdInput.addEventListener('input', function() {
      bookingData.discountIdNumber = this.value;
      if (bookingData.discountType === 'pwd' || bookingData.discountType === 'senior') {
        updateSummary(3);
      }
    });
  }

  function setPaymentMethod(method) {
    bookingData.paymentMethod = method;
    const cardFields = document.getElementById('card-fields');
    const ewalletFields = document.getElementById('ewallet-fields');
    const cashFields = document.getElementById('cash-fields');
    if (cardFields) cardFields.style.display = method === 'card' ? 'block' : 'none';
    if (ewalletFields) {
      if (method === 'ewallet') ewalletFields.classList.add('reveal-open'); else ewalletFields.classList.remove('reveal-open');
    }
    if (cashFields) cashFields.style.display = method === 'cash' ? 'block' : 'none';

    const setReq = (el, req) => { if (el) { if (req) el.setAttribute('required', 'required'); else el.removeAttribute('required'); } };
    const isCard = method === 'card' && !IS_STAFF_WALKIN;
    setReq(document.getElementById('cardholder-name'), isCard);
    setReq(document.getElementById('card-number'), isCard);
    setReq(document.getElementById('expiry-date'), isCard);
    setReq(document.getElementById('cvv'), isCard);
    setReq(document.getElementById('ewallet-number'), method === 'ewallet');
    const isCash = method === 'cash' && IS_STAFF_WALKIN;
    setReq(cashAmountInput, isCash);

    if (method !== 'cash') {
      bookingData.amountPaid = 0;
      bookingData.change = 0;
      if (cashAmountInput) cashAmountInput.value = '';
      if (cashChangeDisplay) cashChangeDisplay.textContent = '0.00';
    }
  }
  if (paymentMethodSelect) {
    if (paymentMethodSelect.tagName === 'SELECT') {
      paymentMethodSelect.addEventListener('change', function() { setPaymentMethod(this.value); });
    }
    const initial = IS_STAFF_WALKIN ? 'cash' : 'ewallet';
    if (paymentMethodSelect.tagName === 'SELECT') paymentMethodSelect.value = initial;
    else if (paymentMethodSelect.tagName === 'INPUT') paymentMethodSelect.value = initial;
    setPaymentMethod(initial);
    if (paymentDetailsPanel) paymentDetailsPanel.classList.add('visible');
    // Online: do NOT show mobile number until user clicks an e-wallet button
    if (!IS_STAFF_WALKIN) {
      const ewalletFields = document.getElementById('ewallet-fields');
      if (ewalletFields) ewalletFields.classList.remove('reveal-open');
    }
  }

  function handleProviderSelection(card) {
    if (!card) return;
    const method = card.getAttribute('data-method') || 'ewallet';
    const provider = card.getAttribute('data-provider') || 'gcash';

    if (paymentMethodSelect) paymentMethodSelect.value = method;
    const providerHidden = document.getElementById('ewallet-provider');
    if (providerHidden) providerHidden.value = provider;
    setPaymentMethod(method);
    if (method === 'ewallet') {
      const ewalletFields = document.getElementById('ewallet-fields');
      if (ewalletFields) ewalletFields.classList.add('reveal-open');
    }

    providerCards.forEach(btn => btn.classList.remove('selected'));
    card.classList.add('selected');
    if (paymentDetailsPanel) paymentDetailsPanel.classList.add('visible');
  }

  if (providerCards && providerCards.length) {
    providerCards.forEach(card => {
      card.addEventListener('click', function() {
        handleProviderSelection(card);
      });
    });
  }

  function updateCashChange() {
    if (!IS_STAFF_WALKIN || !cashAmountInput || !cashChangeDisplay) return;
    const raw = parseFloat(cashAmountInput.value || '0');
    if (!Number.isFinite(raw)) {
      cashChangeDisplay.textContent = '0.00';
      bookingData.amountPaid = 0;
      bookingData.change = 0;
      return;
    }
    bookingData.amountPaid = raw;
    const diff = raw - (bookingData.total || 0);
    const change = diff > 0 ? diff : 0;
    bookingData.change = change;
    cashChangeDisplay.textContent = change.toFixed(2);
  }

  if (cashAmountInput) {
    cashAmountInput.addEventListener('input', updateCashChange);
  }
  
  // Card number: digits only, max 16
  const cardNumberInput = document.getElementById('card-number');
  if (cardNumberInput) {
    cardNumberInput.addEventListener('input', function(e) {
      let value = (e.target.value || '').replace(/\D/g, '').slice(0, 16);
      e.target.value = value;
    });
  }
  
  // Format expiry date
  const expiryDateInput = document.getElementById('expiry-date');
  if (expiryDateInput) {
    expiryDateInput.addEventListener('input', function(e) {
      let value = e.target.value.replace(/\D/g, '');
      if (value.length >= 2) {
        value = value.slice(0, 2) + '/' + value.slice(2, 4);
      }
      e.target.value = value;
    });
  }
  
  // CVV numbers only
  const cvvInput = document.getElementById('cvv');
  if (cvvInput) {
    cvvInput.addEventListener('input', function(e) {
      e.target.value = e.target.value.replace(/\D/g, '');
    });
  }

  // E-wallet number: digits only, must start with 09, max 11 digits
  const ewalletNumber = document.getElementById('ewallet-number');
  function validateEwalletNumber(num) {
    const digits = (num || '').replace(/\D/g, '');
    if (digits.length === 0) return { valid: false, msg: 'Enter your mobile number.' };
    if (digits.length < 11) return { valid: false, msg: 'Mobile number must be 11 digits (e.g. 09171234567).' };
    if (digits.length > 11) return { valid: false, msg: 'Mobile number must be exactly 11 digits.' };
    if (!digits.startsWith('09')) return { valid: false, msg: 'Mobile number must start with 09.' };
    return { valid: true };
  }
  if (ewalletNumber) {
    ewalletNumber.addEventListener('input', function(e) {
      let value = (e.target.value || '').replace(/\D/g, '');
      if (value.length > 2 && !value.startsWith('09')) value = '09' + value.replace(/^0+/, '').slice(0, 9);
      value = value.slice(0, 11);
      e.target.value = value;
    });
    ewalletNumber.addEventListener('blur', function() {
      const v = this.value.replace(/\D/g, '');
      if (v.length > 0 && v.length < 11) this.setCustomValidity('Must be 11 digits starting with 09');
      else if (v.length === 11 && !v.startsWith('09')) this.setCustomValidity('Must start with 09');
      else this.setCustomValidity('');
    });
  }
  
  const completePaymentBtn = document.getElementById('complete-payment');
  if (completePaymentBtn) {
    completePaymentBtn.addEventListener('click', async function() {
      const form = document.getElementById('payment-form');
      const method = (document.getElementById('payment-method')?.value) || 'card';

      // If claiming a PWD/Senior discount, enforce ID number and document upload
      if (bookingData.discountType === 'pwd' || bookingData.discountType === 'senior') {
        if (!discountIdInput || !discountIdInput.value.trim()) {
          alert('Please enter the PWD / Senior ID number to continue.');
          discountIdInput?.focus();
          return;
        }
        bookingData.discountIdNumber = discountIdInput.value.trim();

        // Online flow: require an uploaded ID image.
        if (!IS_STAFF_WALKIN) {
          if (!discountProofInput || !discountProofInput.files || !discountProofInput.files[0]) {
            alert('Please upload a clear photo of the PWD / Senior ID to continue.');
            discountProofInput?.focus();
            return;
          }
          try {
            bookingData.discountFile = await fileToPayload(discountProofInput.files[0]);
          } catch (e) {
            console.error(e);
            alert('We could not read the ID document. Please try again or use a smaller JPG/PNG file.');
            return;
          }
        } else {
          // Staff walk-in: no file upload, just keep discountFile null.
          bookingData.discountFile = null;
        }
      }
      
      // Basic per-method validation
      let valid = true;
      if (method === 'card' && !IS_STAFF_WALKIN) {
        const name = document.getElementById('cardholder-name')?.value?.trim();
        const number = document.getElementById('card-number')?.value?.replace(/\D/g, '');
        const exp = document.getElementById('expiry-date')?.value?.trim();
        const cvv = document.getElementById('cvv')?.value?.trim();
        valid = !!(name && number && number.length === 16 && exp && cvv && cvv.length >= 3);
        if (!valid) alert('Please complete valid card details (16-digit number).');
      } else       if (method === 'ewallet') {
        const prov = document.getElementById('ewallet-provider')?.value;
        const numRaw = document.getElementById('ewallet-number')?.value?.trim() || '';
        const num = numRaw.replace(/\D/g, '');
        const ewalletValidation = validateEwalletNumber(num);
        if (!ewalletValidation.valid) {
          alert(ewalletValidation.msg);
          document.getElementById('ewallet-number')?.focus();
          return;
        }
        if (!prov) {
          alert('Please select an E-Wallet payment method above.');
          return;
        }
        valid = true; // Already validated above (09 + 11 digits)
      } else if (method === 'cash' && IS_STAFF_WALKIN) {
        if (!cashAmountInput) {
          alert('Please enter the cash amount received from the customer.');
          return;
        }
        const raw = parseFloat(cashAmountInput.value || '0');
        if (!Number.isFinite(raw) || raw <= 0) {
          alert('Please enter a valid cash amount received from the customer.');
          cashAmountInput.focus();
          return;
        }
        bookingData.amountPaid = raw;
        const diff = raw - (bookingData.total || 0);
        if (diff < 0) {
          alert('Cash received is less than the total amount due.');
          cashAmountInput.focus();
          return;
        }
        bookingData.change = diff;
        if (cashChangeDisplay) {
          cashChangeDisplay.textContent = diff.toFixed(2);
        }
        valid = true;
      } else {
        valid = false;
        alert('Please select a valid payment method.');
      }

      if (!valid) {
        if (form) form.reportValidity?.();
        return;
      }

      bookingData.paymentMethod = method;
      // For non-cash methods, record amount paid equal to total and zero change
      if (!(method === 'cash' && IS_STAFF_WALKIN)) {
        bookingData.amountPaid = bookingData.total || 0;
        bookingData.change = 0;
      }
      try {
        await processPayment();
      } catch (err) {
        console.error(err);
        alert('Unable to complete your booking at the moment. Please try again.');
      }
    });
  }
}

// ==== Addons (Food & Drinks) ====
const ADDONS_MENU = [
  { id: 'popcorn-l', name: 'Large Popcorn', price: 150, img: 'food&drinks/popcorn.png', category: 'food', desc: 'Freshly popped buttery corn.' },
  { id: 'nachos', name: 'Nachos & Dips', price: 140, img: 'food&drinks/nachos & dips.png', category: 'food', desc: 'Crispy chips with cheese & salsa.' },
  { id: 'fries', name: 'Fries', price: 100, img: 'food&drinks/fries.png', category: 'food', desc: 'Golden crispy potato fries.' },
  { id: 'hotdogs', name: 'Classic Hotdog', price: 130, img: 'food&drinks/hotdogs.png', category: 'food', desc: 'Juicy sausage in a soft bun.' },
  { id: 'pepsi', name: 'Pepsi', price: 60, img: 'food&drinks/pepsi.png', category: 'drink', desc: 'Ice cold refreshing soda.' },
  { id: 'coca-cola', name: 'Coca-Cola', price: 60, img: 'food&drinks/coca-cola.png', category: 'drink', desc: 'The classic refreshing drink.' },
  { id: 'coffee', name: 'Hot Coffee', price: 80, img: 'food&drinks/coffee.png', category: 'drink', desc: 'Premium brewed coffee.' },
  { id: 'complimentary', name: 'Water', price: 0, img: 'food&drinks/complimentary drink.png', category: 'drink', desc: 'Pure mineral water.' }
];

function renderAddons() {
  const container = document.getElementById('addons-list');
  if (!container) return;
  
  const foods = ADDONS_MENU.filter(i => i.category === 'food');
  const drinks = ADDONS_MENU.filter(i => i.category === 'drink');

  function cardHtml(item) {
    const qty = bookingData.addons.find(a => a.id === item.id)?.qty || 0;
    return `
      <div class="fs-food-card" data-addon-id="${item.id}" style="cursor: default;">
        <div class="fs-food-card-img-wrap">
          <img src="${item.img}" alt="${item.name}" loading="lazy">
        </div>
        <div class="fs-food-card-content">
          <h3 class="fs-food-card-title">${item.name}</h3>
          <p class="fs-food-card-desc">${item.desc}</p>
          <div class="fs-food-card-footer">
            <span class="fs-food-card-price">₱${item.price.toFixed(2)}</span>
          </div>
          <div style="margin-top: var(--fs-spacing-md); display: flex; align-items: center; justify-content: space-between; background: rgba(255,255,255,0.05); padding: 8px; border-radius: var(--fs-radius-md);">
            <button class="fs-btn fs-btn-icon" data-act="dec" data-id="${item.id}" style="background: rgba(255,255,255,0.1); color: #fff;">−</button>
            <span class="fs-font-weight-bold" id="qty-${item.id}" style="font-size: var(--fs-font-size-lg);">${qty}</span>
            <button class="fs-btn fs-btn-icon" data-act="inc" data-id="${item.id}" style="background: var(--fs-color-primary); color: #fff;">+</button>
          </div>
        </div>
      </div>
    `;
  }

  container.innerHTML = `
    <div style="margin-bottom: var(--fs-spacing-2xl);">
      <h4 class="fs-detail-section-title" style="margin-bottom: var(--fs-spacing-lg);">Snacks</h4>
      <div class="fs-grid">${foods.map(cardHtml).join('')}</div>
    </div>
    <div style="margin-bottom: var(--fs-spacing-2xl);">
      <h4 class="fs-detail-section-title" style="margin-bottom: var(--fs-spacing-lg);">Beverages</h4>
      <div class="fs-grid">${drinks.map(cardHtml).join('')}</div>
    </div>
  `;

  container.querySelectorAll('button[data-id]').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
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


function updateVehicleStepSummary() {
  const scheduleText = `${bookingData.date} | ${bookingData.schedule}`;
  const displayTitle = bookingData.itemType === 'event'
    ? `${bookingData.movie} (${getEventOptionLabel(bookingData.eventOption)})`
    : bookingData.movie;
  const el = (id) => document.getElementById(id);
  if (el('summary-movie-vehicle')) el('summary-movie-vehicle').textContent = displayTitle;
  if (el('summary-schedule-vehicle')) el('summary-schedule-vehicle').textContent = scheduleText;
  if (el('summary-seats-vehicle')) el('summary-seats-vehicle').textContent = bookingData.seats.join(', ');
}

function updateSummary(step) {
  const scheduleText = `${bookingData.date} | ${bookingData.schedule}`;
  const displayTitle = bookingData.itemType === 'event'
    ? `${bookingData.movie} (${getEventOptionLabel(bookingData.eventOption)})`
    : bookingData.movie;
  
  if (step >= 2) {
    const summarySchedule2 = document.getElementById('summary-schedule-2');
    const summaryMovie2 = document.getElementById('summary-movie-2');
    const summaryTheatre2 = document.getElementById('summary-theatre-type-2');
    if (summarySchedule2) summarySchedule2.textContent = scheduleText;
    if (summaryMovie2) summaryMovie2.textContent = displayTitle;
    if (summaryTheatre2) summaryTheatre2.textContent = bookingData.theatreType;
  }
  if (step >= 3) {
    const summarySchedule3 = document.getElementById('summary-schedule-3');
    const summarySeats = document.getElementById('summary-seats');
    const summaryQuantity2 = document.getElementById('summary-quantity-2');
    const summaryAddons = document.getElementById('summary-addons');
    const summaryPrice = document.getElementById('summary-price');
    const paymentAmount = document.getElementById('payment-amount');
    const summaryMovie3 = document.getElementById('summary-movie-3');
    const summaryTheatre3 = document.getElementById('summary-theatre-type-3');
    
    if (summarySchedule3) summarySchedule3.textContent = scheduleText;
    if (summaryMovie3) summaryMovie3.textContent = displayTitle;
    if (summaryTheatre3) summaryTheatre3.textContent = bookingData.theatreType;
    if (summarySeats) summarySeats.textContent = bookingData.seats.join(', ');
    
    // Calculate quantity from number of seats
    const quantity = bookingData.seats.length;
    if (summaryQuantity2) summaryQuantity2.textContent = `${quantity} ticket(s)`;
    
    const addonsLine = (bookingData.addons && bookingData.addons.length)
      ? bookingData.addons.map(a => `${a.name} x${a.qty}`).join(', ')
      : 'None';
    if (summaryAddons) summaryAddons.textContent = addonsLine;
    
    // Calculate totals — discount handling depends on booking source:
    // Staff walk-in: discount applied immediately (staff verifies ID face-to-face)
    // Online: customer pays full price; discount requires admin approval
    const baseTickets = bookingData.ticketTotal || 0;
    const addonsTotal = bookingData.addonsTotal || 0;
    let discountAmount = 0;
    if (bookingData.discountPercent > 0) {
      let numIds = 1;
      if (bookingData.discountIdNumber) {
        const ids = bookingData.discountIdNumber.split(',').map(s => s.trim()).filter(s => s.length > 0);
        if (ids.length > 0) numIds = ids.length;
      }
      const numDiscountedTickets = Math.min(quantity, numIds);
      discountAmount = numDiscountedTickets * bookingData.price * (bookingData.discountPercent / 100);
    }
    const afterDiscountTickets = baseTickets - discountAmount;
    const parkingFee = (bookingData.hasVehicle === true) ? PARKING_FEE : 0;
    bookingData.parkingFee = parkingFee;
    bookingData.discountOriginalTicketTotal = baseTickets;
    bookingData.discountedTicketTotal = afterDiscountTickets;
    bookingData.discountAmount = discountAmount;
    // Staff walk-in: apply discount immediately; Online: charge full price
    const ticketCharge = IS_STAFF_WALKIN ? afterDiscountTickets : baseTickets;
    const grand = ticketCharge + addonsTotal + parkingFee;
    bookingData.total = grand;
    if (summaryPrice) summaryPrice.textContent = grand.toFixed(2);
    if (paymentAmount) paymentAmount.textContent = grand.toFixed(2);
  }
}

function goToStep(stepId) {
  try {
    // Hide all steps
    document.querySelectorAll('.booking-step').forEach(step => {
      step.classList.remove('active');
    });
    
    // Show the target step
    const targetStep = document.getElementById(stepId);
    if (!targetStep) {
      console.error('Step not found:', stepId);
      return;
    }
    
    targetStep.classList.add('active');
    console.log('Navigated to step:', stepId);
    
    // Scroll to top
    window.scrollTo({ top: 0, behavior: 'smooth' });
  } catch (err) {
    console.error('Error in goToStep:', err);
  }
}

async function processPayment() {
  // Generate booking ID
  bookingData.bookingId = 'CF' + Date.now().toString().slice(-8);
  
  const payload = buildBookingPayload();
    const saved = await saveBookingToServer(payload);
  if (!saved) {
    throw new Error('Failed to save booking on server.');
  }
  if (saved.parkingNumber) {
    bookingData.parkingNumber = saved.parkingNumber;
  }
  displayConfirmation();
  goToStep('step-confirmation');
}

function buildBookingPayload() {
  const seatsJoined = bookingData.seats.join(', ');
  // Ensure source is set
  bookingData.source = IS_STAFF_WALKIN ? 'walkin' : 'online';

  // Optional customer override for staff walk-in
  let customerNameOverride = null;
  let customerEmailOverride = null;
  if (IS_STAFF_WALKIN) {
    const nameEl = document.getElementById('walkinCustomerName');
    const emailEl = document.getElementById('walkinCustomerEmail');
    customerNameOverride = nameEl && nameEl.value ? nameEl.value.trim() : null;
    customerEmailOverride = emailEl && emailEl.value ? emailEl.value.trim() : null;
    if (customerNameOverride) bookingData.customerName = customerNameOverride;
  }

  return {
    bookingId: bookingData.bookingId,
    itemType: bookingData.itemType || 'movie',
    itemName: bookingData.movie,
    eventOption: bookingData.eventOption || null,
    showDate: bookingData.date,
    showTime: bookingData.schedule,
    venue: bookingData.cinema,
    seats: seatsJoined,
    quantity: bookingData.seats.length,
    totalAmount: bookingData.total,
    paymentMethod: bookingData.paymentMethod || 'card',
    addons: bookingData.addons || [],
    status: 'Paid',
    source: bookingData.source,
    discountType: bookingData.discountType || null,
    discountPercent: bookingData.discountPercent || 0,
    discountOriginalTotal: bookingData.discountOriginalTicketTotal,
    discountedTotal: bookingData.discountedTicketTotal,
    discountIdNumber: bookingData.discountIdNumber,
    discountFile: bookingData.discountFile,
    amountPaid: bookingData.amountPaid,
    change: bookingData.change,
    customerNameOverride,
    customerEmailOverride,
    hasVehicle: bookingData.hasVehicle === true,
    vehiclePlate: bookingData.vehiclePlate || null,
    vehicleType:  bookingData.vehicleType  || null,
    vehicleColor: bookingData.vehicleColor || null
  };
}

async function fileToPayload(file) {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onload = function (e) {
      const result = e.target && typeof e.target.result === 'string' ? e.target.result : '';
      const parts = result.split(',');
      const base64 = parts.length > 1 ? parts[1] : parts[0];
      resolve({
        name: file.name,
        type: file.type,
        data: base64
      });
    };
    reader.onerror = function (e) {
      reject(e);
    };
    reader.readAsDataURL(file);
  });
}

async function saveBookingToServer(payload) {
  try {
    const response = await fetch('api/save_booking.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(payload)
    });

    if (!response.ok) {
      console.error('Save booking failed with status', response.status);
      let msg = 'Server returned an error while saving your booking.';
      try {
        const data = await response.json();
        if (data && data.error) {
          msg = data.error;
        }
      } catch (_) {}
      alert(msg);
      return false;
    }

    const data = await response.json();
    if (!data.success) {
      console.error('Save booking error', data);
      alert(data.error || 'Unable to save your booking. Please try again.');
      return false;
    }
    return data;
  } catch (err) {
    console.error('Save booking exception', err);
    alert('Network error while saving your booking. Please check your connection and try again.');
    return false;
  }
}

function displayConfirmation() {
  const displayTitle = bookingData.itemType === 'event'
    ? `${bookingData.movie} (${getEventOptionLabel(bookingData.eventOption)})`
    : bookingData.movie;
  const bookingIdEl = document.getElementById('receipt-booking-id');
  if (bookingIdEl) bookingIdEl.textContent = bookingData.bookingId;
  document.getElementById('receipt-movie').textContent = displayTitle;
  document.getElementById('receipt-theatre-type').textContent = bookingData.theatreType;
  document.getElementById('receipt-schedule').textContent = `${bookingData.date} | ${bookingData.schedule}`;
  const seatsStr = bookingData.seats.join(', ');
  document.getElementById('receipt-seats').textContent = seatsStr;
  const parkingRow = document.getElementById('receipt-parking-row');
  const parkingSpan = document.getElementById('receipt-parking');
  if (bookingData.hasVehicle === true && parkingRow && parkingSpan) {
    parkingRow.style.display = 'flex';
    const slot = bookingData.parkingNumber ? String(bookingData.parkingNumber) : 'Reserved';
    const plate = bookingData.vehiclePlate || '—';
    const type  = bookingData.vehicleType  || '—';
    const color = bookingData.vehicleColor || '—';
    parkingSpan.innerHTML =
      slot + ' (₱' + PARKING_FEE + ')<br>' +
      '<span style="font-size:0.82em;opacity:0.85;">' +
        plate + ' &middot; ' + type + ' &middot; ' + color +
      '</span>';
  } else if (parkingRow) {
    parkingRow.style.display = 'none';
  }
  const quantity = bookingData.seats.length;
  document.getElementById('receipt-quantity').textContent = quantity + (quantity === 1 ? ' ticket' : ' tickets');
  const method = bookingData.paymentMethod || 'card';
  let methodLabel = 'Credit/Debit Card';
  if (method === 'ewallet') {
    const prov = document.getElementById('ewallet-provider')?.value || 'E-Wallet';
    methodLabel = `E-Wallet (${prov})`;
  } else if (method === 'cash') {
    methodLabel = 'Cash';
  }
  const methodEl = document.getElementById('receipt-payment-method');
  if (methodEl) methodEl.textContent = methodLabel;
  document.getElementById('receipt-price').textContent = bookingData.total.toFixed(2);

  const discountBlock = document.getElementById('receipt-discount-block');
  const discountLineRow = document.getElementById('receipt-discount-line-row');
  const receiptOriginalTotal = document.getElementById('receipt-original-total');
  const receiptDiscountAmount = document.getElementById('receipt-discount-amount');
  const discountPendingNotice = document.getElementById('receipt-discount-pending');
  const hasDiscount = (bookingData.discountType === 'pwd' || bookingData.discountType === 'senior') && bookingData.discountPercent > 0;

  if (hasDiscount && IS_STAFF_WALKIN) {
    // Staff walk-in: discount verified and applied immediately — show breakdown
    const origTickets = bookingData.discountOriginalTicketTotal != null ? Number(bookingData.discountOriginalTicketTotal) : 0;
    const addonsTotal = Number(bookingData.addonsTotal) || 0;
    const parkingFee = Number(bookingData.parkingFee) || 0;
    const subtotalBeforeDiscount = origTickets + addonsTotal + parkingFee;
    const discAmount = bookingData.discountAmount != null ? Number(bookingData.discountAmount) : origTickets * 0.2;
    if (discountBlock && receiptOriginalTotal) {
      receiptOriginalTotal.textContent = subtotalBeforeDiscount.toFixed(2);
      discountBlock.style.display = 'flex';
    }
    if (discountLineRow && receiptDiscountAmount) {
      receiptDiscountAmount.textContent = discAmount.toFixed(2);
      discountLineRow.style.display = 'flex';
    }
    if (discountPendingNotice) discountPendingNotice.style.display = 'none';
  } else if (hasDiscount) {
    // Online: discount pending admin approval — show pending notice, hide breakdown
    if (discountBlock) discountBlock.style.display = 'none';
    if (discountLineRow) discountLineRow.style.display = 'none';
    if (discountPendingNotice) discountPendingNotice.style.display = 'block';
  } else {
    if (discountBlock) discountBlock.style.display = 'none';
    if (discountLineRow) discountLineRow.style.display = 'none';
    if (discountPendingNotice) discountPendingNotice.style.display = 'none';
  }

  const customerNameEl = document.getElementById('receipt-customer-name');
  if (customerNameEl) {
    const name = bookingData.customerName || document.querySelector('.confirmation-card')?.dataset?.customerName || 'Guest';
    customerNameEl.textContent = String(name).toUpperCase();
  }
  const seatsStubEl = document.getElementById('receipt-seats-stub');
  if (seatsStubEl) seatsStubEl.textContent = bookingData.seats.length ? bookingData.seats[bookingData.seats.length - 1] : '—';

  const amountPaidEl = document.getElementById('receipt-amount-paid');
  const changeEl = document.getElementById('receipt-change');
  const amountPaidRow = document.getElementById('receipt-amount-paid-row');
  const changeRow = document.getElementById('receipt-change-row');
  const paid = typeof bookingData.amountPaid === 'number' ? bookingData.amountPaid : bookingData.total;
  const change = typeof bookingData.change === 'number' ? bookingData.change : 0;
  if (amountPaidEl) amountPaidEl.textContent = paid.toFixed(2);
  if (changeEl) changeEl.textContent = change.toFixed(2);
  if (IS_STAFF_WALKIN && amountPaidRow && changeRow) {
    amountPaidRow.style.display = 'flex';
    changeRow.style.display = 'flex';
  } else if (amountPaidRow && changeRow) {
    amountPaidRow.style.display = 'none';
    changeRow.style.display = 'none';
  }
  
  // Generate QR code
  const qrSeed = JSON.stringify({
    id: bookingData.bookingId,
    movie: bookingData.movie,
    date: bookingData.date,
    schedule: bookingData.schedule,
    cinema: bookingData.cinema,
    seats: bookingData.seats,
    total: bookingData.total,
    paymentMethod: bookingData.paymentMethod || methodLabel
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