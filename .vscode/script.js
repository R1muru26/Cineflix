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

if (nextSpot) {
  nextSpot.addEventListener('click', () => {
    spotlightIndex = (spotlightIndex + 1) % spotlightSlides.length;
    showSpotlightSlide(spotlightIndex);
  });
}

if (prevSpot) {
  prevSpot.addEventListener('click', () => {
    spotlightIndex = (spotlightIndex - 1 + spotlightSlides.length) % spotlightSlides.length;
    showSpotlightSlide(spotlightIndex);
  });
}

// Auto-slide every 6 seconds
setInterval(() => {
  spotlightIndex = (spotlightIndex + 1) % spotlightSlides.length;
  showSpotlightSlide(spotlightIndex);
}, 6000);

// === Trailer Modal ===
document.addEventListener('DOMContentLoaded', () => {
  const trailerModal = document.getElementById('videoModal');
  const trailerFrame = document.getElementById('trailerFrame');

  if (!trailerModal || !trailerFrame) {
    console.error('Modal elements not found');
    return;
  }

  function openTrailer(url) {
    if (!url) return;
    trailerFrame.src = url;
    trailerModal.style.display = 'flex';
  }

  function closeTrailer() {
    trailerModal.style.display = 'none';
    trailerFrame.src = '';
  }

  // Close button
  const closeBtn = trailerModal.querySelector('.close');
  if (closeBtn) {
    closeBtn.addEventListener('click', closeTrailer);
  }

  // Click outside to close
  trailerModal.addEventListener('click', (e) => {
    if (e.target === trailerModal) closeTrailer();
  });

  // Event delegation for buttons
  document.body.addEventListener('click', (e) => {
    const clicked = e.target;

    // Watch button in spotlight
    const watchBtn = clicked.closest('.watch-btn[data-trailer]');
    if (watchBtn) {
      const url = watchBtn.getAttribute('data-trailer');
      openTrailer(url);
      return;
    }

    // Trailer button in poster cards
    const trailerBtn = clicked.closest('.poster-btn.trailer-btn');
    if (trailerBtn) {
      const posterCard = trailerBtn.closest('.poster-card');
      const url = posterCard ? posterCard.dataset.trailer : null;
      if (url) openTrailer(url);
      return;
    }

    // Get Tickets button
    const getTicketsBtn = clicked.closest('.poster-btn.get-tickets-btn');
    if (getTicketsBtn) {
      e.preventDefault();
      const posterCard = getTicketsBtn.closest('.poster-card');
      const movieTitle = posterCard.dataset.movie;
      const moviePoster = posterCard.dataset.poster;
      
      // Redirect to booking page with movie data
      window.location.href = `booking.php?movie=${encodeURIComponent(movieTitle)}&poster=${encodeURIComponent(moviePoster)}`;
      return;
    }
  });

  // ESC key to close
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && trailerModal.style.display === 'flex') {
      closeTrailer();
    }
  });
});
