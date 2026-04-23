const trailerButtons = document.querySelectorAll('.trailer-btn');
const modal = document.getElementById('videoModal');
const trailerFrame = document.getElementById('trailerFrame');
const closeBtn = document.querySelector('.close');

// Open modal and play video
trailerButtons.forEach(btn => {
  btn.addEventListener('click', e => {
    e.stopPropagation();
    const card = e.target.closest('.poster-card');
    const trailerUrl = card.getAttribute('data-trailer');
    trailerFrame.src = trailerUrl;
    modal.style.display = 'flex';
  });
});

// Close modal on "X"
closeBtn.addEventListener('click', () => {
  modal.style.display = 'none';
  trailerFrame.src = ''; // stop video
});

// Close modal when clicking outside the video
window.addEventListener('click', e => {
  if (e.target === modal) {
    modal.style.display = 'none';
    trailerFrame.src = '';
  }
});

// Close on ESC key
window.addEventListener('keydown', e => {
  if (e.key === 'Escape') {
    modal.style.display = 'none';
    trailerFrame.src = '';
  }
});








// === Spotlight Section ===
const spotlightSlides = document.querySelectorAll('.spotlight-slide');
const nextSpot = document.querySelector('.next');
const prevSpot = document.querySelector('.prev');
let spotlightIndex = 0;

function showSpotlightSlide(index) {
  spotlightSlides.forEach((slide, i) => {
    slide.classList.toggle('active', i === index);
  });
}

nextSpot.addEventListener('click', () => {
  spotlightIndex = (spotlightIndex + 1) % spotlightSlides.length;
  showSpotlightSlide(spotlightIndex);
});

prevSpot.addEventListener('click', () => {
  spotlightIndex = (spotlightIndex - 1 + spotlightSlides.length) % spotlightSlides.length;
  showSpotlightSlide(spotlightIndex);
});

// Auto-slide every 6 seconds
setInterval(() => {
  spotlightIndex = (spotlightIndex + 1) % spotlightSlides.length;
  showSpotlightSlide(spotlightIndex);
}, 6000);


document.addEventListener('DOMContentLoaded', () => {
  // modal elements
  const trailerModal = document.getElementById('videoModal');
  const trailerFrame = document.getElementById('trailerFrame');

  if (!trailerModal) {
    console.error('videoModal element not found (id="videoModal").');
    return;
  }
  if (!trailerFrame) {
    console.error('trailerFrame iframe not found (id="trailerFrame").');
    return;
  }

  // open trailer function
  function openTrailer(url) {
    // ensure url exists
    if (!url) {
      console.warn('openTrailer called with empty url');
      return;
    }
    // set src and show modal
    trailerFrame.src = url;
    trailerModal.style.display = 'flex';
  }

  // close trailer function
  function closeTrailer() {
    trailerModal.style.display = 'none';
    // stop video by clearing src
    trailerFrame.src = '';
  }

  // close button inside modal (safe lookup)
  const closeBtn = trailerModal.querySelector('.close');
  if (closeBtn) {
    closeBtn.addEventListener('click', closeTrailer);
  } else {
    console.warn('.close button inside modal not found');
  }

  // click outside iframe to close
  trailerModal.addEventListener('click', (e) => {
    if (e.target === trailerModal) closeTrailer();
  });

  // Event delegation for any trailer buttons:
  // - Spotlight watch buttons: .watch-btn with data-trailer
  // - Poster buttons: the poster-card has data-trailer and button has class .trailer-btn
  document.body.addEventListener('click', (e) => {
    const clicked = e.target;

    // 1) Direct watch-btn (spotlight) with data-trailer attribute
    const watchBtn = clicked.closest('.watch-btn[data-trailer]');
    if (watchBtn) {
      const url = watchBtn.getAttribute('data-trailer');
      openTrailer(url);
      return;
    }

    // 2) Poster buttons inside .poster-card (button has class .trailer-btn)
    const posterBtn = clicked.closest('.poster-btn.trailer-btn');
    if (posterBtn) {
      const posterCard = posterBtn.closest('.poster-card');
      const url = posterCard ? posterCard.dataset.trailer : null;
      if (url) openTrailer(url);
      else console.warn('Poster button clicked but poster-card has no data-trailer attribute.');
      return;
    }

    // 3) Any other element with data-trailer attribute (fallback)
    const fallback = clicked.closest('[data-trailer]');
    if (fallback) {
      const url = fallback.getAttribute('data-trailer');
      // avoid opening modal for container elements that aren't meant to open modal
      // only open if clicked element is a button or has an explicit class
      if (clicked.matches('button, a') || clicked.classList.contains('watch-btn')) {
        openTrailer(url);
      }
    }
  });

  // Optional: keyboard Esc to close
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && trailerModal.style.display === 'flex') {
      closeTrailer();
    }
  });
});
