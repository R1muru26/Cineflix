/* events.js — CineFlix Events Page */
(function () {
  'use strict';

  const modal       = document.getElementById('experienceModal');
  const modalTitle  = document.getElementById('modal-title');
  const modalDesc   = document.getElementById('modal-description');
  const modalCancel = document.getElementById('modalCancel');
  const optionBtns  = document.querySelectorAll('.option-btn');

  let activeEventId = null;

  /* ── helpers ── */
  function openModal(eventId) {
    activeEventId = eventId;

    // Find event data injected by PHP
    const events = window.__cineflixEvents || [];
    const event  = events.find(e => String(e.id) === String(eventId));

    if (event) {
      modalTitle.textContent = event.title;
      modalDesc.textContent  = 'How would you like to enjoy "' + event.title + '"?';
    } else {
      modalTitle.textContent = 'Choose Your Experience';
      modalDesc.textContent  = 'How would you like to enjoy this event?';
    }

    modal.style.display = 'flex';
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
  }

  function closeModal() {
    modal.style.display = 'none';
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
    activeEventId = null;
  }

  /* ── "Choose Experience" buttons on cards ── */
  document.addEventListener('click', function (e) {
    const btn = e.target.closest('[data-action="choose"]');
    if (!btn) return;

    // Require login
    if (!window.__isLoggedIn) {
      window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.href);
      return;
    }

    // Find event data and go directly to booking.php
    const events = window.__cineflixEvents || [];
    const eventId = btn.dataset.eventId;
    const event = events.find(function (ev) { return String(ev.id) === String(eventId); });

    if (event) {
      const params = new URLSearchParams({
        type:     'event',
        id:       event.id,
        title:    event.title,
        date:     event.date,
        time:     event.time,
        location: event.location,
        image:    event.image
      });
      window.location.href = 'booking.php?' + params.toString();
    }
  });

  /* ── Modal option buttons (Meet & Greet / Special Screening) ── */
  optionBtns.forEach(function (btn) {
    btn.addEventListener('click', function () {
      if (!activeEventId) return;

      const option = btn.dataset.option; // 'meet' or 'screening'
      const url    = 'event-booking.php?id=' + encodeURIComponent(activeEventId)
                   + '&type=' + encodeURIComponent(option);

      closeModal();
      window.location.href = url;
    });
  });

  /* ── Cancel button ── */
  if (modalCancel) {
    modalCancel.addEventListener('click', closeModal);
  }

  /* ── Click outside modal card to close ── */
  modal.addEventListener('click', function (e) {
    if (e.target === modal) closeModal();
  });

  /* ── Escape key to close ── */
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && modal.style.display === 'flex') closeModal();
  });

})();