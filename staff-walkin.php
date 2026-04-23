<?php
session_start();
if (empty($_SESSION['is_staff'])) {
    header('Location: login.html');
    exit();
}
$staffName = $_SESSION['staff_name'] ?? 'Staff';

require_once __DIR__ . '/includes/db.php';
$conn = db_get_connection();

$hardcodedMovies = [
  ['Title' => 'Chainsaw Man',                    'Poster' => 'movies posters/chainsawman.jpg'],
  ['Title' => 'Demon Slayer',                    'Poster' => 'movies posters/demonslayer.jpg'],
  ['Title' => 'Spider-Man 3',                    'Poster' => 'movies posters/Spider-Man 3 (2007).jpg'],
  ['Title' => 'The Black Phone 2',               'Poster' => 'movies posters/The Black Phone 2.jpg'],
  ['Title' => 'Thunderbolts',                    'Poster' => 'movies posters/Thunderbolts.jpg'],
  ['Title' => 'A Minecraft Movie',               'Poster' => 'movies posters/A Minecraft Movie.jpg'],
  ['Title' => 'IT Chapter 2',                    'Poster' => 'movies posters/IT chapter 2.jpg'],
  ['Title' => 'The Long Walk',                   'Poster' => 'movies posters/The Long Walk.jpg'],
  ['Title' => 'Tron Ares',                       'Poster' => 'movies posters/Tron Ares.jpg'],
  ['Title' => 'Brave New World',                 'Poster' => 'movies posters/Captain America.jpg'],
  ['Title' => 'The Housemaid',                   'Poster' => 'coming soon movies/the housemaid 2025.jpg'],
  ['Title' => 'Zootopia 2',                      'Poster' => 'coming soon movies/Zootopia 2.jpg'],
  ['Title' => 'Avatar : Fire & Ash',             'Poster' => 'coming soon movies/avatar fire & ash.jpg'],
  ['Title' => "Now You See Me : Now You Don't",  'Poster' => 'coming soon movies/now you see me.jpg'],
  ['Title' => "Five Nights at Freddy's 2",       'Poster' => 'coming soon movies/FNAF2.jpg'],
  ['Title' => 'Search for Square Pants',         'Poster' => 'coming soon movies/spongebob.jpg'],
];

$movies = $hardcodedMovies;
$existingTitles = array_column($hardcodedMovies, 'Title');
// Detect the correct poster column name dynamically
$posterCol = null;
$colResult = $conn->query("SHOW COLUMNS FROM Movie");
if ($colResult) {
  while ($col = $colResult->fetch_assoc()) {
    $name = $col['Field'];
    if (in_array(strtolower($name), ['posterpath', 'poster', 'poster_path', 'image', 'image_path', 'imagepath', 'img', 'cover', 'thumbnail'])) {
      $posterCol = $name;
      break;
    }
  }
}

if ($posterCol) {
  $dbMoviesResult = $conn->query("SELECT Title, `$posterCol` AS PosterPath FROM Movie ORDER BY ReleaseDate DESC, Title ASC");
} else {
  // No poster column found — fetch Title only
  $dbMoviesResult = $conn->query("SELECT Title FROM Movie ORDER BY ReleaseDate DESC, Title ASC");
}

if ($dbMoviesResult) {
  while ($row = $dbMoviesResult->fetch_assoc()) {
    if (!in_array($row['Title'], $existingTitles)) {
      $movies[] = ['Title' => $row['Title'], 'Poster' => $row['PosterPath'] ?? ''];
      $existingTitles[] = $row['Title'];
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title>Walk-in Booking | CineFlix</title>
  <link rel="stylesheet" href="common.css">
  <link rel="stylesheet" href="booking.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">

  <style>
    /* ═══════════════════════════════════════════
       CSS VARIABLES & RESET
    ═══════════════════════════════════════════ */
    :root {
      --gold: #c79f5e;
      --gold-light: #f5d49a;
      --gold-dim: rgba(199,159,94,0.18);
      --ink: #080808;
      --ink2: #101010;
      --text: #f2ede7;
      --text-dim: rgba(242,237,231,0.65);
      --radius: 14px;
      --header-h: 64px;
      --hero-h: 100vh;
      --strip-h: 190px;
    }

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    html { scroll-behavior: smooth; }

    body {
      font-family: 'Inter', sans-serif;
      background: var(--ink);
      color: var(--text);
      min-height: 100vh;
      overflow-x: hidden;
    }

    /* ═══════════════════════════════════════════
       SITE HEADER
    ═══════════════════════════════════════════ */
    .site-header {
      position: fixed !important;
      top: 0 !important; left: 0 !important; right: 0 !important;
      z-index: 10050 !important;
      height: var(--header-h) !important;
      padding: 0 2rem !important;
      display: flex !important;
      align-items: center !important;
      justify-content: space-between !important;
      background: rgba(8,8,8,0.92) !important;
      backdrop-filter: blur(14px) !important;
      -webkit-backdrop-filter: blur(14px) !important;
      border-bottom: 1px solid var(--gold-dim) !important;
      transition: background 0.4s, backdrop-filter 0.4s;
    }
    .site-header.scrolled {
      background: rgba(8,8,8,0.92) !important;
      backdrop-filter: blur(14px) !important;
      -webkit-backdrop-filter: blur(14px) !important;
      border-bottom: 1px solid var(--gold-dim) !important;
    }

    .site-header .logo img { height: 260px !important; width: auto !important; }

    .top-nav ul { display:flex; align-items:center; gap:16px; list-style:none; }

    .staff-label {
      font-family: 'Inter', sans-serif; font-weight: 500;
      font-size: 0.85rem;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      color: var(--text-dim);
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .badge-staff {
      padding: 3px 10px;
      border-radius: 999px;
      font-size: 0.65rem;
      letter-spacing: 0.14em;
      text-transform: uppercase;
      background: var(--gold-dim);
      color: var(--gold-light);
      border: 1px solid rgba(199,159,94,0.3);
    }

    .avatar {
      width: 36px; height: 36px; border-radius: 50%;
      background: linear-gradient(135deg, #1a1a1a, #2e2e2e);
      border: 1px solid rgba(199,159,94,0.35);
      color: var(--gold-light);
      display: flex; align-items: center; justify-content: center;
      font-weight: 700; font-size: 13px; text-transform: uppercase;
      cursor: pointer;
      transition: box-shadow 0.25s;
    }
    .avatar:hover { box-shadow: 0 0 0 2px var(--gold); }

    .user-menu { position: relative; }
    .dropdown {
      position: absolute; right: 0; top: 50px;
      background: #111; border: 1px solid #222;
      border-radius: 10px; box-shadow: 0 14px 30px rgba(0,0,0,.5);
      display: none; min-width: 180px; z-index: 9000; overflow: hidden;
    }
    .dropdown a {
      display: block; padding: 12px 16px;
      color: rgba(255,255,255,0.85); text-decoration: none;
      font-size: 0.9rem; transition: background 0.2s, color 0.2s;
    }
    .dropdown a:hover { background: #1a1a1a; color: var(--gold-light); }

    /* ═══════════════════════════════════════════
       HERO SECTION  (GLOBE EXPRESS style)
    ═══════════════════════════════════════════ */
    #walkin-hero {
      position: relative;
      width: 100%;
      height: var(--hero-h);
      overflow: hidden;
      background: var(--ink);
    }

    /* full-bleed background slides */
    .hero-bg-slider {
      position: absolute;
      inset: 0;
    }
    .hero-bg-slide {
      position: absolute;
      inset: 0;
      background-size: cover;
      background-position: center center;
      opacity: 0;
      transform: scale(1.04);
      transition: opacity 1.1s ease, transform 8s ease;
      will-change: opacity, transform;
    }
    .hero-bg-slide.active {
      opacity: 1;
      transform: scale(1);
    }

    /* dark gradient overlays */
    .hero-overlay {
      position: absolute; inset: 0; z-index: 2;
      background:
        linear-gradient(to right, rgba(8,8,8,0.82) 38%, rgba(8,8,8,0.1) 75%),
        linear-gradient(to top, rgba(8,8,8,0.75) 0%, transparent 45%);
      pointer-events: none;
    }

    /* no topbar */

    /* ── Hero movie info panel (left side, shown on card hover/select) ── */
    .hero-content {
      position: absolute;
      bottom: calc(var(--strip-h) + 40px);
      left: 0;
      right: 0;
      z-index: 10;
      display: flex;
      align-items: flex-end;
      padding: 0 5vw;
      pointer-events: none;
    }

    /* The actual info card */
    .hero-info-card {
      display: flex;
      align-items: flex-end;
      gap: 24px;
      opacity: 0;
      transform: translateY(18px);
      transition: opacity 0.5s ease, transform 0.5s ease;
      pointer-events: none;
      max-width: 600px;
    }
    .hero-info-card.visible {
      opacity: 1;
      transform: translateY(0);
    }

    /* Poster thumbnail */
    .hero-info-poster {
      width: 90px;
      height: 134px;
      border-radius: 10px;
      overflow: hidden;
      flex-shrink: 0;
      border: 2px solid rgba(199,159,94,0.45);
      box-shadow: 0 12px 32px rgba(0,0,0,0.7);
      background: #111;
    }
    .hero-info-poster img {
      width: 100%; height: 100%; object-fit: cover; display: block;
    }

    /* Text block beside poster */
    .hero-info-text {
      flex: 1;
      padding-bottom: 4px;
    }
    .hero-info-eyebrow {
      font-family: 'Inter', sans-serif; font-weight: 500;
      font-size: 0.65rem;
      letter-spacing: 0.22em;
      text-transform: uppercase;
      color: var(--gold);
      margin-bottom: 6px;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .hero-info-eyebrow::before {
      content: '';
      display: inline-block;
      width: 16px; height: 2px;
      background: var(--gold); border-radius: 2px;
    }
    .hero-info-title {
      font-family: 'Poppins', sans-serif; font-weight: 600; letter-spacing: 0.5px;
      font-size: clamp(2rem, 4vw, 3.4rem);
      line-height: 0.95;
      letter-spacing: 0.02em;
      color: #fff;
      margin-bottom: 10px;
      text-shadow: 0 2px 20px rgba(0,0,0,0.6);
    }
    .hero-info-meta {
      font-size: 0.82rem;
      color: rgba(255,255,255,0.55);
      font-weight: 300;
      line-height: 1.5;
      margin-bottom: 14px;
    }
    .hero-info-cta {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 9px 22px;
      background: var(--gold);
      color: var(--ink);
      font-family: 'Inter', sans-serif; font-weight: 500;
      font-size: 0.85rem;
      font-weight: 700;
      letter-spacing: 0.14em;
      text-transform: uppercase;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      pointer-events: auto;
      transition: background 0.2s, transform 0.2s;
    }
    .hero-info-cta:hover { background: var(--gold-light); transform: translateY(-2px); }

    /* Animate in hero text elements — via JS-added class instead of sibling CSS */
    .hero-eyebrow, .hero-title, .hero-desc, .hero-cta {
      opacity: 0; transform: translateY(14px);
      transition: opacity 0.6s, transform 0.6s;
    }

    /* ═══════════════════════════════════════════
       HERO STRIP — REDESIGNED
       Two-column: left title panel + right scrollable cards
    ═══════════════════════════════════════════ */

    /* strip-h is now taller for better presence */
    :root { --strip-h: 210px; }

    /* Full-width strip container pinned to bottom of hero */
    .hero-strip-wrap {
      position: absolute;
      bottom: 0; left: 0; right: 0;
      height: var(--strip-h);
      z-index: 12;
      display: flex;
      align-items: stretch;
      background: linear-gradient(to top, rgba(4,4,4,0.97) 0%, rgba(4,4,4,0.80) 100%);
      border-top: 1px solid rgba(199,159,94,0.15);
      backdrop-filter: blur(4px);
    }

    /* LEFT PANEL — title + arrows */
    .strip-left-panel {
      flex: 0 0 220px;
      display: flex;
      flex-direction: column;
      justify-content: center;
      padding: 0 1.5rem 0 2.5rem;
      border-right: 1px solid rgba(255,255,255,0.06);
      gap: 14px;
      flex-shrink: 0;
    }
    .strip-panel-tag {
      font-family: 'Inter', sans-serif; font-weight: 500;
      font-size: 0.62rem;
      letter-spacing: 0.22em;
      text-transform: uppercase;
      color: var(--gold);
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .strip-panel-tag::before {
      content: '';
      display: block;
      width: 18px; height: 2px;
      background: var(--gold);
      border-radius: 2px;
    }
    .strip-panel-heading {
      font-family: 'Poppins', sans-serif; font-weight: 600; letter-spacing: 0.5px;
      font-size: 1.6rem;
      letter-spacing: 0.04em;
      line-height: 1.05;
      color: #fff;
    }
    .strip-panel-heading span { color: var(--gold); }
    .strip-panel-sub {
      font-size: 0.75rem;
      color: rgba(255,255,255,0.38);
      line-height: 1.4;
      font-weight: 300;
    }
    .strip-nav-arrows {
      display: flex;
      gap: 8px;
      margin-top: 4px;
    }
    .strip-nav-btn {
      width: 32px; height: 32px;
      border-radius: 50%;
      border: 1px solid rgba(199,159,94,0.3);
      background: rgba(199,159,94,0.07);
      color: var(--gold);
      font-size: 1rem;
      display: flex; align-items: center; justify-content: center;
      cursor: pointer;
      transition: background 0.2s, border-color 0.2s, transform 0.15s;
      line-height: 1;
    }
    .strip-nav-btn:hover { background: rgba(199,159,94,0.18); border-color: var(--gold); transform: scale(1.08); }
    .strip-nav-btn:disabled { opacity: 0.25; cursor: default; transform: none; }

    /* RIGHT — scrollable card track */
    .hero-strip {
      flex: 1;
      display: flex;
      align-items: stretch;
      overflow-x: auto;
      overflow-y: hidden;
      scrollbar-width: none;
      -ms-overflow-style: none;
      scroll-behavior: smooth;
      padding: 16px 20px 16px 16px;
      gap: 10px;
    }
    .hero-strip::-webkit-scrollbar { display: none; }

    /* Individual card */
    .strip-card {
      flex: 0 0 118px;
      position: relative;
      border-radius: 10px;
      overflow: hidden;
      cursor: pointer;
      border: 1.5px solid rgba(255,255,255,0.06);
      transition: flex 0.4s cubic-bezier(0.4,0,0.2,1),
                  border-color 0.3s, transform 0.3s, box-shadow 0.3s;
    }
    .strip-card:hover {
      flex: 0 0 170px;
      border-color: rgba(199,159,94,0.45);
      transform: translateY(-4px);
      box-shadow: 0 16px 32px rgba(0,0,0,0.55), 0 0 0 1px rgba(199,159,94,0.15);
    }
    .strip-card.active-slide {
      border-color: var(--gold);
      box-shadow: 0 0 0 2px rgba(199,159,94,0.35), 0 12px 28px rgba(0,0,0,0.5);
    }

    .strip-card-bg {
      position: absolute; inset: 0;
      background-size: cover;
      background-position: center top;
      filter: brightness(0.55) saturate(0.85);
      transform: scale(1.06);
      transition: transform 0.5s ease, filter 0.4s;
    }
    .strip-card:hover .strip-card-bg {
      filter: brightness(0.78) saturate(1.15);
      transform: scale(1);
    }

    .strip-card-overlay {
      position: absolute; inset: 0;
      background: linear-gradient(to top,
        rgba(4,4,4,0.92) 0%,
        rgba(4,4,4,0.35) 50%,
        transparent 100%);
    }

    .strip-card-label {
      position: absolute;
      bottom: 0; left: 0; right: 0;
      padding: 10px 10px 12px;
    }
    .strip-card-eyebrow {
      font-family: 'Inter', sans-serif; font-weight: 500;
      font-size: 0.55rem;
      letter-spacing: 0.18em;
      text-transform: uppercase;
      color: var(--gold);
      margin-bottom: 3px;
      opacity: 0.9;
    }
    .strip-card-title {
      font-family: 'Poppins', sans-serif; font-weight: 600; letter-spacing: 0.5px;
      font-size: 1rem;
      line-height: 1.1;
      color: #fff;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    /* active glow dot */
    .strip-card.active-slide .strip-card-label::before {
      content: '';
      display: block;
      width: 6px; height: 6px;
      border-radius: 50%;
      background: var(--gold);
      box-shadow: 0 0 8px var(--gold);
      margin-bottom: 5px;
    }

    /* slide counter dots — reposition above left panel */
    .hero-counter {
      position: absolute;
      bottom: calc(var(--strip-h) + 14px);
      left: 5vw;
      z-index: 12;
      display: flex;
      align-items: center;
      gap: 6px;
    }
    .hero-dot {
      width: 22px; height: 2px;
      background: rgba(255,255,255,0.28);
      border-radius: 2px;
      transition: background 0.3s, width 0.3s;
    }
    .hero-dot.active { background: var(--gold); width: 38px; }
    .hero-dot.active { background: var(--gold); width: 38px; }

    /* ═══════════════════════════════════════════
       BOOKING FLOW (below hero)
    ═══════════════════════════════════════════ */
    main { padding-top: 0; }

    /* Steps wrapper */
    .booking-steps-wrap {
      background: var(--ink2);
      min-height: 100vh;
      padding: 3.5rem 1.5rem 5rem;
    }

    .container { max-width: 920px; margin: 0 auto; }

    /* Step visibility */
    .booking-step { display: none; }
    .booking-step.active { display: block; animation: stepIn 0.45s ease both; }

    @keyframes stepIn {
      from { opacity: 0; transform: translateY(22px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    /* ── Page title ── */
    .page-title {
      font-family: 'Poppins', sans-serif; font-weight: 600; letter-spacing: 0.5px;
      font-size: clamp(2rem, 4vw, 3rem);
      letter-spacing: 0.04em;
      color: var(--text);
      margin-bottom: 1.4rem;
    }
    .page-title span { color: var(--gold); }

    /* ── Section card ── */
    .card {
      background: #111;
      border: 1px solid rgba(255,255,255,0.07);
      border-radius: var(--radius);
      padding: 2rem;
      margin-bottom: 1.5rem;
    }

    /* ── Movie info banner ── */
    .movie-info {
      display: flex;
      gap: 1.2rem;
      align-items: center;
      margin-bottom: 1.6rem;
      padding-bottom: 1.4rem;
      border-bottom: 1px solid rgba(255,255,255,0.07);
    }
    .movie-poster-small {
      width: 72px; height: 106px;
      border-radius: 8px;
      overflow: hidden;
      background: rgba(255,255,255,0.05);
      border: 1px solid rgba(255,255,255,0.1);
      flex-shrink: 0;
    }
    .movie-poster-small img { width:100%; height:100%; object-fit:cover; display:block; }
    .movie-details h2 {
      font-family: 'Inter', sans-serif; font-weight: 500;
      font-size: 1.35rem;
      font-weight: 700;
      letter-spacing: 0.04em;
      text-transform: uppercase;
      margin-bottom: 4px;
    }
    .movie-details p { font-size: 0.88rem; color: var(--text-dim); line-height: 1.5; }
    .walkin-note { font-size: 0.8rem; color: rgba(199,159,94,0.75); margin-top: 6px; }

    /* ── Staff info box ── */
    .staff-fields {
      margin-top: 1.4rem; padding: 1rem 1.2rem;
      border-radius: 10px;
      border: 1px dashed rgba(199,159,94,0.25);
      background: rgba(199,159,94,0.04);
    }
    .staff-fields h3 {
      font-family: 'Inter', sans-serif; font-weight: 500;
      font-size: 0.75rem;
      letter-spacing: 0.18em;
      text-transform: uppercase;
      color: var(--gold);
      margin-bottom: 10px;
    }
    .staff-fields .form-row { display: flex; gap: 10px; flex-wrap: wrap; }
    .staff-fields input {
      flex: 1; min-width: 150px;
      padding: 9px 12px; border-radius: 8px;
      border: 1px solid rgba(255,255,255,0.14);
      background: rgba(0,0,0,0.35); color: #fff; font-size: 0.9rem;
      font-family: 'Inter', sans-serif;
    }

    /* ══════════════════════════════════════════
       MOVIE CAROUSEL PICKER
    ══════════════════════════════════════════ */
    .movie-select {
      margin-top: 1.6rem;
    }
    .movie-select > label {
      display: block; margin-bottom: 14px;
      font-family: 'Inter', sans-serif; font-weight: 500;
      font-size: 0.75rem; letter-spacing: 0.2em;
      text-transform: uppercase; color: var(--gold);
    }

    /* Carousel wrapper */
    .movie-carousel-wrap {
      position: relative;
    }
    .movie-carousel {
      display: flex;
      gap: 12px;
      overflow-x: auto;
      scroll-behavior: smooth;
      scrollbar-width: none;
      padding: 6px 4px 14px;
      cursor: grab;
      user-select: none;
    }
    .movie-carousel::-webkit-scrollbar { display: none; }
    .movie-carousel.dragging { cursor: grabbing; scroll-behavior: auto; }

    /* Arrow nav buttons */
    .carousel-arrow {
      position: absolute; top: 50%; transform: translateY(-60%);
      width: 38px; height: 38px; border-radius: 50%;
      background: rgba(8,8,8,0.85);
      border: 1px solid rgba(199,159,94,0.35);
      color: var(--gold); font-size: 1.1rem;
      display: flex; align-items: center; justify-content: center;
      cursor: pointer; z-index: 10;
      transition: background 0.2s, border-color 0.2s, opacity 0.2s;
      backdrop-filter: blur(6px);
    }
    .carousel-arrow:hover { background: rgba(199,159,94,0.15); border-color: var(--gold); }
    .carousel-arrow.hidden { opacity: 0; pointer-events: none; }
    .carousel-arrow-left  { left: -18px; }
    .carousel-arrow-right { right: -18px; }

    /* Individual movie card in carousel */
    .movie-carousel-card {
      flex: 0 0 110px;
      display: flex; flex-direction: column; align-items: center;
      gap: 8px; cursor: pointer;
      border-radius: 12px;
      padding: 8px 6px 10px;
      border: 2px solid transparent;
      background: rgba(255,255,255,0.03);
      transition: border-color 0.25s, background 0.25s, transform 0.25s;
      position: relative;
    }
    .movie-carousel-card:hover {
      background: rgba(199,159,94,0.07);
      border-color: rgba(199,159,94,0.3);
      transform: translateY(-4px);
    }
    .movie-carousel-card.selected {
      border-color: var(--gold);
      background: rgba(199,159,94,0.1);
      transform: translateY(-6px);
    }
    .movie-carousel-card.selected::after {
      content: '✓';
      position: absolute; top: -8px; right: -8px;
      width: 22px; height: 22px; border-radius: 50%;
      background: var(--gold); color: #111;
      font-size: 0.7rem; font-weight: 800;
      display: flex; align-items: center; justify-content: center;
    }
    .movie-carousel-poster {
      width: 90px; height: 134px;
      border-radius: 8px; overflow: hidden;
      background: rgba(255,255,255,0.05);
      border: 1px solid rgba(255,255,255,0.08);
      flex-shrink: 0;
    }
    .movie-carousel-poster img {
      width: 100%; height: 100%; object-fit: cover; display: block;
    }
    .movie-carousel-poster-placeholder {
      width: 100%; height: 100%;
      display: flex; align-items: center; justify-content: center;
      font-size: 2rem; color: rgba(255,255,255,0.2);
    }
    .movie-carousel-title {
      font-family: 'Inter', sans-serif; font-weight: 500;
      font-size: 0.72rem; font-weight: 700;
      text-align: center; line-height: 1.25;
      color: rgba(255,255,255,0.85);
      text-transform: uppercase; letter-spacing: 0.04em;
      max-width: 90px;
    }
    .movie-carousel-card.selected .movie-carousel-title { color: var(--gold); }

    /* Selected display bar */
    .selected-movie-bar {
      display: flex; align-items: center; gap: 14px;
      padding: 12px 16px; margin-top: 14px;
      border-radius: 10px;
      border: 1px solid rgba(199,159,94,0.25);
      background: rgba(199,159,94,0.05);
      min-height: 54px;
      transition: all 0.3s;
    }
    .selected-movie-bar.empty { border-color: rgba(255,255,255,0.06); background: rgba(0,0,0,0.2); }
    .selected-movie-bar-thumb {
      width: 38px; height: 38px; border-radius: 6px; overflow: hidden;
      background: rgba(255,255,255,0.05); flex-shrink: 0;
      border: 1px solid rgba(255,255,255,0.1);
      display: flex; align-items: center; justify-content: center;
      font-size: 1.2rem; color: rgba(255,255,255,0.25);
    }
    .selected-movie-bar-thumb img { width:100%; height:100%; object-fit:cover; display:block; }
    .selected-movie-bar-name {
      flex: 1; font-weight: 600; font-size: 0.95rem;
      color: rgba(255,255,255,0.9);
    }
    .selected-movie-bar-name.placeholder { color: rgba(255,255,255,0.3); font-weight: 400; }
    .selected-movie-bar-clear {
      background: none; border: none; color: rgba(255,255,255,0.3);
      cursor: pointer; font-size: 1rem; padding: 2px 6px; border-radius: 4px;
      transition: color 0.2s; display: none;
    }
    .selected-movie-bar-clear:hover { color: rgba(255,255,255,0.7); }
    .selected-movie-bar-clear.visible { display: block; }

    /* poster-select (legacy — hidden) */
    .poster-select { display: none !important; }

    /* ── Section subtitle ── */
    .section-subtitle {
      font-family: 'Inter', sans-serif; font-weight: 500;
      font-size: 0.75rem;
      letter-spacing: 0.18em;
      text-transform: uppercase;
      color: var(--gold);
      margin-bottom: 1rem;
    }

    /* ── Buttons ── */
    .btn {
      padding: 12px 28px;
      border-radius: 6px;
      font-family: 'Inter', sans-serif; font-weight: 500;
      font-size: 0.92rem;
      font-weight: 700;
      letter-spacing: 0.14em;
      text-transform: uppercase;
      border: none;
      cursor: pointer;
      transition: background 0.25s, transform 0.2s, opacity 0.25s;
    }
    .btn-primary {
      background: var(--gold);
      color: var(--ink);
    }
    .btn-primary:hover:not(:disabled) { background: var(--gold-light); transform: translateY(-2px); }
    .btn-primary:disabled { opacity: 0.35; cursor: not-allowed; }
    .btn-secondary {
      background: rgba(255,255,255,0.08);
      color: var(--text);
    }
    .btn-secondary:hover { background: rgba(255,255,255,0.14); }
    .btn-large { width: 100%; padding: 15px; margin-top: 1.4rem; font-size: 1rem; }
    .button-group { display: flex; gap: 12px; margin-top: 1.4rem; flex-wrap: wrap; }

    /* ── Form input resets ── */
    .form-input {
      width: 100%; padding: 10px 14px; border-radius: 8px;
      border: 1px solid rgba(255,255,255,0.16);
      background: rgba(0,0,0,0.45); color: #fff;
      font-family: 'Inter', sans-serif; font-size: 0.92rem;
      transition: border-color 0.25s;
    }
    .form-input:focus { outline: none; border-color: var(--gold); }

    .form-group { margin-bottom: 1.1rem; }
    .form-group label {
      display: block; margin-bottom: 6px;
      font-family: 'Inter', sans-serif; font-weight: 500;
      font-size: 0.72rem; letter-spacing: 0.16em;
      text-transform: uppercase; color: var(--text-dim);
    }
    .form-hint { font-size: 0.78rem; color: rgba(255,255,255,0.45); margin-top: 4px; }

    /* ── Calendar month select dark theme ── */
    .booking-date-month-select-wrap { position: relative; }
    .booking-date-month-select {
      background: #1c1c2e !important; color: #fff !important;
      border: 1px solid rgba(199,159,94,0.45) !important;
      border-radius: 8px !important; padding: 6px 32px 6px 14px !important;
      font-family: 'Inter', sans-serif !important; font-size: 0.9rem !important;
      font-weight: 600 !important; appearance: none !important;
      -webkit-appearance: none !important; cursor: pointer; outline: none; min-width: 148px;
    }
    .booking-date-month-select-wrap::after {
      content: '▾'; position: absolute; right: 10px; top: 50%;
      transform: translateY(-50%); color: #c79f5e; pointer-events: none; font-size: 0.85rem;
    }
    .booking-date-month-select option { background: #1c1c2e !important; color: #fff !important; }

    /* ── Calendar day cells ── */
    .booking-date-day {
      background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.05);
      border-radius: 8px; color: #fff; padding: 10px; cursor: pointer;
      font-weight: 600; transition: all 0.2s; text-align: center;
    }
    .booking-date-day:hover:not(.is-empty):not(.day-disabled) {
      background: rgba(199,159,94,0.15); border-color: #c79f5e; color: #c79f5e;
    }
    .booking-date-day.selected { background: #c79f5e !important; color: #000 !important; border-color: #c79f5e !important; }
    .booking-date-day.is-empty { background: transparent; border: none; cursor: default; }
    .booking-date-day.day-disabled {
      opacity: 0.25 !important; cursor: not-allowed !important;
      pointer-events: none !important; text-decoration: line-through !important;
    }
    .booking-date-day.day-today:not(.selected) {
      border-color: rgba(199,159,94,0.5) !important; color: #c79f5e !important; font-weight: 700 !important;
    }
    .booking-date-trigger:hover { border-color: #c79f5e !important; background: rgba(60,60,60,0.9) !important; }

    /* ── Section title yellow ── */
    .section-title-yellow {
      color: var(--gold); font-size: 1.1rem; margin-bottom: 1rem;
      font-family: 'Inter', sans-serif; font-weight: 500;
      letter-spacing: 0.06em; text-transform: uppercase; font-weight: 700;
    }
    .choose-date-section { margin: 0 0 1.4rem; }

    /* ── Theatre selection bar ── */
    .theatre-selection-bar {
      background: transparent; border: 1px solid rgba(255,255,255,0.1);
      border-radius: 50px; padding: 0.75rem clamp(1rem,4vw,2rem);
      display: flex; align-items: center; justify-content: center;
      gap: 1rem; margin: 1.5rem 0 0.5rem; flex-wrap: wrap;
    }
    .theatre-label { font-weight: 700; color: #fff; font-size: 1rem; white-space: nowrap; }
    .theatre-pills { display: flex; align-items: center; gap: 0.6rem; flex-wrap: wrap; }
    .theatre-pill {
      display: flex; align-items: center; gap: 7px;
      background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.12);
      border-radius: 50px; padding: 0.45rem 1.1rem; cursor: pointer;
      transition: all 0.2s ease; font-size: 0.9rem;
      color: rgba(255,255,255,0.75); font-weight: 500; user-select: none;
    }
    .theatre-pill:hover { border-color: rgba(199,159,94,0.5); background: rgba(199,159,94,0.08); color: #fff; }
    .theatre-pill:has(input:checked) { border-color: #c79f5e; background: rgba(199,159,94,0.12); color: #fff; }
    .pill-dot {
      width: 9px; height: 9px; border-radius: 50%;
      border: 2px solid rgba(255,255,255,0.35); flex-shrink: 0; transition: all 0.2s;
    }
    .theatre-pill:has(input:checked) .pill-dot { background: #c79f5e; border-color: #c79f5e; box-shadow: 0 0 6px rgba(199,159,94,0.6); }
    .theatre-hint { text-align: center; color: rgba(199,159,94,0.85); font-size: 0.82rem; margin: 0.4rem 0 1.5rem; font-weight: 500; }

    /* ── Schedule grid 3-column ── */
    .schedule-grid-container {
      display: grid; grid-template-columns: repeat(3,1fr); gap: 1.25rem; margin-bottom: 2.5rem;
    }
    @media (max-width: 768px)  { .schedule-grid-container { grid-template-columns: 1fr; } }
    @media (min-width:769px) and (max-width:1024px) { .schedule-grid-container { grid-template-columns: repeat(2,1fr); } }

    .new-schedule-card {
      background: rgba(30,30,30,0.4); border: 1px solid rgba(255,255,255,0.06);
      border-radius: 14px; padding: 1.75rem 1.5rem; cursor: pointer;
      transition: all 0.35s cubic-bezier(0.4,0,0.2,1);
      display: flex; flex-direction: column; gap: 0.4rem;
      position: relative; overflow: hidden;
    }
    .new-schedule-card::before {
      content:''; position:absolute; top:0;left:0;right:0;height:3px;
      background:#c79f5e; opacity:0; transition:opacity 0.3s;
    }
    .new-schedule-card:hover { background:rgba(50,50,50,0.5); transform:translateY(-6px); border-color:rgba(199,159,94,0.25); }
    .new-schedule-card.selected { background:rgba(199,159,94,0.08); border-color:#c79f5e; box-shadow:0 12px 30px rgba(0,0,0,0.4),0 0 0 1px rgba(199,159,94,0.2); }
    .new-schedule-card.selected::before { opacity:1; }
    .new-schedule-card.passed { opacity:0.5; cursor:not-allowed; pointer-events:none; filter:grayscale(0.5); }
    .new-schedule-card.passed .card-time { color:rgba(255,255,255,0.4); }
    .new-schedule-card.passed .price-pill { background:rgba(255,255,255,0.1); color:rgba(255,255,255,0.4); box-shadow:none; }
    .card-time { font-size:1.5rem; font-weight:800; color:#c79f5e; letter-spacing:-0.02em; }
    .card-cinema { font-size:0.95rem; color:rgba(255,255,255,0.5); margin-bottom:0.75rem; font-weight:500; }
    .price-pill {
      background:#c79f5e; color:#111; padding:0.35rem 1.1rem;
      border-radius:50px; font-size:0.8rem; font-weight:700;
      display:inline-block; width:fit-content; box-shadow:0 4px 10px rgba(199,159,94,0.3);
    }
    .card-status { display:flex;align-items:center;gap:0.5rem;font-size:0.85rem;color:rgba(255,255,255,0.45);margin-top:0.75rem;font-weight:500; }

    /* ── Smart Seat Suggester ── */
    .smart-suggester {
      background: linear-gradient(135deg,rgba(199,159,94,0.06) 0%,rgba(199,159,94,0.02) 100%);
      border:1px solid rgba(199,159,94,0.25); border-radius:16px;
      margin-bottom:2rem; overflow:hidden; transition:border-color 0.3s;
    }
    .smart-suggester:hover { border-color:rgba(199,159,94,0.4); }
    .suggester-header { display:flex;align-items:center;gap:12px;padding:1rem 1.25rem; }
    .suggester-icon { font-size:1.4rem; animation:sparkle 2.5s ease-in-out infinite; }
    @keyframes sparkle { 0%,100%{transform:scale(1) rotate(0deg);opacity:1;} 50%{transform:scale(1.2) rotate(10deg);opacity:0.85;} }
    .suggester-text { flex:1;display:flex;flex-direction:column;gap:2px; }
    .suggester-title { font-weight:700;font-size:0.95rem;color:#c79f5e;letter-spacing:0.01em; }
    .suggester-sub { font-size:0.78rem;color:rgba(255,255,255,0.45); }
    .suggester-toggle {
      display:flex;align-items:center;gap:6px;
      background:rgba(199,159,94,0.12);border:1px solid rgba(199,159,94,0.3);
      border-radius:50px;color:#c79f5e;font-size:0.8rem;font-weight:600;
      padding:0.45rem 1rem;cursor:pointer;transition:all 0.2s;
      font-family:'Inter',sans-serif;white-space:nowrap;
    }
    .suggester-toggle:hover { background:rgba(199,159,94,0.2);border-color:#c79f5e; }
    .suggester-toggle svg { transition:transform 0.3s; }
    .suggester-toggle.open svg { transform:rotate(180deg); }
    .suggester-panel { display:none;padding:0 1.25rem 1.25rem;animation:slideDown 0.25s ease; }
    .suggester-panel.open { display:block; }
    @keyframes slideDown { from{opacity:0;transform:translateY(-8px);}to{opacity:1;transform:translateY(0);} }
    .suggester-question { font-size:0.88rem;color:rgba(255,255,255,0.6);margin:0 0 1rem;text-align:center; }
    .suggester-modes { display:flex;gap:0.6rem;flex-wrap:wrap;justify-content:center;margin-bottom:1rem; }
    .mode-btn {
      display:flex;flex-direction:column;align-items:center;gap:4px;
      background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.1);
      border-radius:14px;padding:0.8rem 1rem;cursor:pointer;
      transition:all 0.25s cubic-bezier(0.4,0,0.2,1);min-width:90px;
      font-family:'Inter',sans-serif;color:rgba(255,255,255,0.7);
    }
    .mode-btn:hover { border-color:rgba(199,159,94,0.5);background:rgba(199,159,94,0.08);transform:translateY(-3px);color:#fff; }
    .mode-btn.active { border-color:#c79f5e;background:rgba(199,159,94,0.15);box-shadow:0 0 20px rgba(199,159,94,0.2);color:#fff; }
    .mode-icon { font-size:1.3rem; }
    .mode-label { font-size:0.78rem;font-weight:700;color:inherit; }
    .mode-desc { font-size:0.68rem;color:rgba(255,255,255,0.4);text-align:center; }
    .mode-btn.active .mode-desc { color:rgba(199,159,94,0.7); }
    .suggester-result {
      display:flex;align-items:center;gap:10px;
      background:rgba(199,159,94,0.08);border:1px solid rgba(199,159,94,0.3);
      border-radius:12px;padding:0.9rem 1.1rem;flex-wrap:wrap;animation:resultIn 0.35s ease;
    }
    @keyframes resultIn { from{opacity:0;transform:scale(0.97);}to{opacity:1;transform:scale(1);} }
    .result-pulse { width:8px;height:8px;border-radius:50%;background:#c79f5e;box-shadow:0 0 0 0 rgba(199,159,94,0.6);animation:pulse 1.5s ease-in-out infinite;flex-shrink:0; }
    @keyframes pulse { 0%{box-shadow:0 0 0 0 rgba(199,159,94,0.6);}70%{box-shadow:0 0 0 8px rgba(199,159,94,0);}100%{box-shadow:0 0 0 0 rgba(199,159,94,0);} }
    .result-text { flex:1;font-size:0.85rem;color:rgba(255,255,255,0.9);font-weight:500; }
    .result-apply { background:#c79f5e;color:#111;border:none;border-radius:50px;padding:0.4rem 1rem;font-size:0.78rem;font-weight:700;cursor:pointer;font-family:'Inter',sans-serif;transition:all 0.2s; }
    .result-apply:hover { background:#e0b46d;transform:scale(1.03); }
    .result-dismiss { background:none;border:none;color:rgba(255,255,255,0.35);font-size:0.75rem;cursor:pointer;font-family:'Inter',sans-serif;transition:color 0.2s;text-decoration:underline;padding:0; }
    .result-dismiss:hover { color:rgba(255,255,255,0.7); }

    /* ── Seat map (booking.php style) ── */
    .screen-label {
      background:linear-gradient(90deg,transparent,rgba(199,159,94,0.35),rgba(199,159,94,0.5),rgba(199,159,94,0.35),transparent);
      color:rgba(255,255,255,0.7);font-size:0.7rem;font-weight:700;
      letter-spacing:0.25em;text-align:center;padding:0.5rem;
      border-radius:4px 4px 0 0;margin-bottom:1.5rem;
    }
    .seat-legend { display:flex;justify-content:center;gap:1.5rem;margin-top:1.5rem;flex-wrap:wrap; }
    .legend-item { display:flex;align-items:center;gap:7px;font-size:0.82rem;color:rgba(255,255,255,0.6); }
    .seat-demo { width:18px;height:18px;border-radius:4px;border:2px solid transparent; }
    .seat-demo.available  { background:rgba(255,255,255,0.1);border-color:rgba(255,255,255,0.2); }
    .seat-demo.reserved   { background:rgba(255,255,255,0.05);border-color:rgba(255,255,255,0.1);opacity:0.4; }
    .seat-demo.selected   { background:#c79f5e;border-color:#c79f5e; }
    .seat-demo.suggested  { background:rgba(199,159,94,0.25);border-color:#c79f5e; }
    .seat.suggested {
      background:rgba(199,159,94,0.15)!important;border-color:#c79f5e!important;
      color:#c79f5e!important;cursor:pointer!important;pointer-events:auto!important;
      animation:seatGlow 1.2s ease-in-out infinite alternate;
    }
    @keyframes seatGlow { from{box-shadow:0 0 6px rgba(199,159,94,0.3);}to{box-shadow:0 0 18px rgba(199,159,94,0.7),0 0 4px rgba(199,159,94,0.4);} }
    .price-display strong { color:var(--text); }
    @media (max-width:600px) { .seats-container{overflow-x:auto;padding-bottom:1rem;width:100%;} .seats-grid{min-width:300px;transform:none;} }
    @media (max-width:400px) { .seats-grid{transform:scale(0.8);} }

    /* booking summary small */
    .booking-summary-small {
      padding: 1rem 1.2rem;
      border-radius: 8px;
      background: rgba(255,255,255,0.04);
      border: 1px solid rgba(255,255,255,0.08);
      margin-bottom: 1.4rem;
      font-size: 0.88rem;
      color: var(--text-dim);
    }
    .booking-summary-small p { margin-bottom: 4px; }
    .booking-summary-small strong { color: var(--text); }

    /* payment layout */
    .payment-layout { display: flex; gap: 1.5rem; flex-wrap: wrap; }
    .booking-summary { flex: 1; min-width: 220px; }
    .booking-summary h3 {
      font-family: 'Inter', sans-serif; font-weight: 500;
      font-size: 0.75rem; letter-spacing: 0.18em;
      text-transform: uppercase; color: var(--gold); margin-bottom: 12px;
    }
    .summary-details { font-size: 0.88rem; color: var(--text-dim); }
    .summary-details p { margin-bottom: 6px; }
    .summary-details strong { color: var(--text); }
    .summary-total { color: var(--gold-light) !important; font-size: 1rem !important; margin-top: 12px !important; }
    .payment-section { flex: 1.4; min-width: 260px; }

    /* payment methods */
    .payment-methods-grid {
      display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 10px;
    }
    .payment-provider-card {
      padding: 14px 10px; border-radius: 10px;
      border: 1px solid rgba(255,255,255,0.1);
      background: rgba(255,255,255,0.04);
      color: #fff; cursor: pointer;
      display: flex; align-items: center; justify-content: center;
      font-family: 'Inter', sans-serif; font-weight: 500;
      font-size: 0.9rem; font-weight: 600;
      transition: border-color 0.25s, background 0.25s;
    }
    .payment-provider-card img { max-height: 32px; max-width: 80px; object-fit: contain; }
    .payment-provider-card:hover,
    .payment-provider-card.selected {
      border-color: var(--gold);
      background: rgba(199,159,94,0.1);
    }
    .payment-details-panel { margin-top: 1rem; }

    /* confirmation */
    .confirmation-card { max-width: 680px; margin: 0 auto; text-align: center; }
    .success-animation { margin-bottom: 1.5rem; }
    .success-icon {
      width: 72px; height: 72px; border-radius: 50%;
      background: var(--gold-dim); border: 2px solid var(--gold);
      color: var(--gold); font-size: 2rem;
      display: flex; align-items: center; justify-content: center;
      margin: 0 auto;
      animation: popIn 0.5s cubic-bezier(0.175,0.885,0.32,1.275) both;
    }
    @keyframes popIn { from { transform: scale(0); opacity: 0; } to { transform: scale(1); opacity: 1; } }
    .confirmation-title {
      font-family: 'Poppins', sans-serif; font-weight: 600; letter-spacing: 0.5px;
      font-size: 2.4rem; letter-spacing: 0.04em; color: var(--text); margin-bottom: 6px;
    }
    .confirmation-subtitle { font-size: 0.9rem; color: var(--text-dim); margin-bottom: 2rem; }

    /* ticket receipt */
    .receipt-outer { text-align: left; }
    .receipt-banner-title {
      font-family: 'Inter', sans-serif; font-weight: 500;
      font-size: 0.7rem; letter-spacing: 0.2em;
      text-transform: uppercase; color: var(--gold);
      margin-bottom: 4px;
    }
    .receipt-banner-sub { font-size: 0.8rem; color: var(--text-dim); margin-bottom: 1rem; }

    .ticket-receipt {
      background: #111; border: 1px solid rgba(255,255,255,0.08);
      border-radius: 12px; overflow: hidden; margin-bottom: 2rem;
      color: var(--text);
    }
    /* booking.css targets a white ticket; walk-in uses a dark card — beat .ticket-detail-row .ticket-value { color: #111 } */
    #step-confirmation .ticket-receipt .ticket-detail-row .ticket-value {
      color: var(--text);
    }
    #step-confirmation .ticket-receipt-stub .stub-value,
    #step-confirmation .ticket-receipt-stub .stub-seats {
      color: var(--text);
    }
    #step-confirmation .ticket-receipt-footer {
      color: rgba(255,255,255,0.2);
    }
    .ticket-receipt-top {
      display: flex; justify-content: space-between; align-items: center;
      padding: 1.2rem 1.5rem;
      border-bottom: 1px solid rgba(255,255,255,0.07);
    }
    .ticket-receipt-logo {
      font-family: 'Poppins', sans-serif; font-weight: 600; letter-spacing: 0.5px;
      font-size: 1.6rem; color: var(--gold); letter-spacing: 0.08em;
    }
    .ticket-receipt-checkin { text-align: right; }
    .ticket-label {
      display: block; font-size: 0.62rem;
      letter-spacing: 0.2em; text-transform: uppercase;
      color: var(--text-dim); margin-bottom: 2px;
    }
    .ticket-value { font-weight: 600; font-size: 0.9rem; color: var(--text); }
    .ticket-checkin-code { color: var(--gold-light); font-size: 1.1rem; }

    .ticket-receipt-qr {
      display: flex; justify-content: center; padding: 1.2rem;
      border-bottom: 1px solid rgba(255,255,255,0.07);
    }
    .ticket-receipt-details { padding: 1.2rem 1.5rem; }
    .ticket-detail-row { display: flex; justify-content: space-between; margin-bottom: 10px; }
    .ticket-receipt-perforation {
      height: 1px;
      background: repeating-linear-gradient(to right, rgba(255,255,255,0.15) 0px, rgba(255,255,255,0.15) 8px, transparent 8px, transparent 16px);
      margin: 0 1.5rem;
    }
    .ticket-receipt-stub {
      display: flex; gap: 1rem; padding: 1.2rem 1.5rem;
    }
    .stub-left, .stub-right { flex: 1; }
    .stub-value { font-size: 0.85rem; }
    .stub-seats { color: var(--gold-light); }
    .receipt-discount-amount { color: #f87171; }

    .ticket-receipt-footer {
      padding: 10px 1.5rem;
      text-align: center;
      font-family: 'Poppins', sans-serif; font-weight: 600; letter-spacing: 0.5px;
      font-size: 0.85rem;
      letter-spacing: 0.25em;
      color: rgba(255,255,255,0.15);
      border-top: 1px solid rgba(255,255,255,0.05);
    }

    /* seats */
    .screen-label {
      text-align: center;
      font-family: 'Inter', sans-serif; font-weight: 500;
      font-size: 0.72rem;
      letter-spacing: 0.25em;
      text-transform: uppercase;
      color: var(--text-dim);
      padding: 8px;
      border-top: 2px solid rgba(255,255,255,0.15);
      margin-bottom: 1.5rem;
    }
    .seats-section { margin-bottom: 1.4rem; }
    .seat-legend { display: flex; gap: 1.4rem; margin: 1rem 0; flex-wrap: wrap; }
    .legend-item { display: flex; align-items: center; gap: 8px; font-size: 0.83rem; color: var(--text-dim); }
    .seat-demo {
      width: 22px; height: 22px; border-radius: 4px;
    }
    .seat-demo.available { background: rgba(255,255,255,0.12); border: 1px solid rgba(255,255,255,0.2); }
    .seat-demo.reserved  { background: rgba(255,80,80,0.25); border: 1px solid rgba(255,80,80,0.4); }
    .seat-demo.selected  { background: var(--gold-dim); border: 1px solid var(--gold); }
    .selected-seats-info { font-size: 0.9rem; color: var(--text-dim); margin-top: 0.8rem; }
    .selected-seats-info p { margin-bottom: 4px; }
    .selected-seats-info strong { color: var(--text); }

    /* date picker styles */
    .date-selector { margin-bottom: 1.2rem; }
    .date-picker-trigger {
      display: inline-flex; align-items: center; gap: 10px;
      padding: 10px 16px; border-radius: 8px;
      border: 1px solid rgba(255,255,255,0.16);
      background: rgba(0,0,0,0.45); color: var(--text);
      font-family: 'Inter', sans-serif; font-size: 0.92rem;
      cursor: pointer; transition: border-color 0.25s;
    }
    .date-picker-trigger:hover { border-color: var(--gold); }

    .booking-date-modal-backdrop {
      display: none; position: fixed; inset: 0;
      background: rgba(0,0,0,0.7); z-index: 5000;
      align-items: center; justify-content: center;
    }
    .booking-date-modal-backdrop.open { display: flex; }
    .booking-date-modal {
      background: #111; border: 1px solid rgba(255,255,255,0.1);
      border-radius: 14px; padding: 1.5rem; width: min(380px, 90vw);
      box-shadow: 0 24px 60px rgba(0,0,0,0.7);
    }
    .booking-date-modal-title {
      font-family: 'Inter', sans-serif; font-weight: 500;
      font-size: 0.72rem; letter-spacing: 0.2em;
      text-transform: uppercase; color: var(--gold); margin-bottom: 1rem;
    }
    .booking-date-input-display {
      width: 100%; padding: 9px 12px; border-radius: 8px;
      border: 1px solid rgba(255,255,255,0.14);
      background: rgba(0,0,0,0.4); color: #fff;
      font-family: 'Inter', sans-serif; font-size: 0.9rem;
      margin-bottom: 1rem;
    }
    .booking-date-month-nav {
      display: flex; align-items: center; gap: 8px; margin-bottom: 1rem;
    }
    .booking-date-nav-btn {
      padding: 5px 12px; border-radius: 6px;
      border: 1px solid rgba(255,255,255,0.14);
      background: rgba(255,255,255,0.06); color: #fff;
      cursor: pointer; font-size: 1.1rem;
    }
    .booking-date-month-select-wrap { flex: 1; }
    .booking-date-month-select {
      width: 100%; padding: 8px; border-radius: 7px;
      border: 1px solid rgba(255,255,255,0.14);
      background: rgba(0,0,0,0.5); color: #fff;
      font-family: 'Inter', sans-serif; font-size: 0.88rem;
    }
    .booking-date-dow {
      display: grid; grid-template-columns: repeat(7, 1fr);
      gap: 3px; margin-bottom: 6px;
    }
    .booking-date-dow span {
      text-align: center; font-size: 0.72rem;
      color: var(--text-dim); padding: 3px 0;
    }
    .booking-date-days-grid {
      display: grid; grid-template-columns: repeat(7, 1fr); gap: 3px;
    }
    .booking-date-footer {
      display: flex; justify-content: space-between;
      align-items: center; margin-top: 1rem;
    }
    .booking-date-clear-btn {
      padding: 6px 14px; border-radius: 6px;
      border: 1px solid rgba(255,255,255,0.14);
      background: transparent; color: var(--text-dim);
      font-family: 'Inter', sans-serif; font-size: 0.85rem; cursor: pointer;
    }
    .booking-date-seats-hint { font-size: 0.8rem; color: var(--text-dim); }

    /* addons */
    .schedule-cards { display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 1rem; }

    /* responsive */
    @media (max-width: 700px) {
      .hero-content { left: 1.2rem; right: 1.2rem; max-width: none; bottom: calc(var(--strip-h) + 40px); }
      .hero-strip { width: 100%; }
      .payment-layout { flex-direction: column; }
    }
  </style>

  <script>
    var isLoggedIn = true;
    var currentUserId = null;
    var isStaffWalkin = true;

    document.addEventListener('DOMContentLoaded', function(){
      /* ── avatar dropdown ── */
      var avatar = document.getElementById('avatarBtn');
      var menu = document.getElementById('userDropdown');
      if (avatar && menu) {
        avatar.addEventListener('click', function(){
          menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
        });
        document.addEventListener('click', function(e){
          if (!avatar.contains(e.target) && !menu.contains(e.target)) menu.style.display = 'none';
        });
      }

      /* ── sticky header ── */
      var hdr = document.querySelector('.site-header');
      window.addEventListener('scroll', function(){
        hdr.classList.toggle('scrolled', window.scrollY > 20);
      }, {passive:true});

      /* ═══════════════════════════
         HERO SLIDER
      ═══════════════════════════ */
      var slides      = document.querySelectorAll('.hero-bg-slide');
      var dots        = document.querySelectorAll('.hero-dot');
      var current = 0;
      var autoTimer;

      function goToSlide(idx) {
        slides[current].classList.remove('active');
        if (dots[current]) dots[current].classList.remove('active');
        current = (idx + slides.length) % slides.length;
        slides[current].classList.add('active');
        if (dots[current]) dots[current].classList.add('active');

        /* highlight the matching strip card */
        document.querySelectorAll('.strip-card').forEach(function(c){ c.classList.remove('active-slide'); });
        var matchCard = document.querySelector('.strip-card[data-index="'+current+'"]');
        if (matchCard) matchCard.classList.add('active-slide');
      }

      // init first slide
      if (slides.length) {
        slides[0].classList.add('active');
        if (dots[0]) dots[0].classList.add('active');
        var firstCard = document.querySelector('.strip-card[data-index="0"]');
        if (firstCard) firstCard.classList.add('active-slide');
      }

      function startAuto() {
        autoTimer = setInterval(function(){ goToSlide(current + 1); }, 5500);
      }
      startAuto();

      /* ═══════════════════════════════════════════
         MOVIE CAROUSEL PICKER
      ═══════════════════════════════════════════ */
      var hiddenSel   = document.getElementById('walkin-movie-select');
      var moviePoster = document.getElementById('movie-poster');
      var movieTitle2 = document.getElementById('selected-movie');
      var smbBar      = document.getElementById('selected-movie-bar');
      var smbThumb    = document.getElementById('smb-thumb');
      var smbName     = document.getElementById('smb-name');
      var smbClear    = document.getElementById('smb-clear');
      var carousel    = document.getElementById('movie-carousel');
      var arrowL      = document.getElementById('carousel-left');
      var arrowR      = document.getElementById('carousel-right');

      function selectMovie(title, poster) {
        /* deselect all cards */
        document.querySelectorAll('.movie-carousel-card').forEach(function(c){ c.classList.remove('selected'); });
        /* mark selected card */
        var sel = carousel.querySelector('[data-value="'+CSS.escape(title)+'"]');
        if (sel) sel.classList.add('selected');

        /* update hidden native select (booking.js reads this) */
        if (hiddenSel) {
          hiddenSel.value = title || '';
          hiddenSel.dispatchEvent(new Event('change'));
        }

        /* update movie-info header */
        if (movieTitle2) movieTitle2.textContent = title || 'Select a Movie';
        if (moviePoster) {
          moviePoster.innerHTML = poster
            ? '<img src="'+poster+'" alt="'+title+'" style="width:100%;height:100%;object-fit:cover;display:block;border-radius:10px;">'
            : '';
        }

        /* update selected bar */
        if (title) {
          smbBar.classList.remove('empty');
          smbName.classList.remove('placeholder');
          smbName.textContent = title;
          if (poster) {
            smbThumb.innerHTML = '<img src="'+poster+'" alt="'+title+'" style="width:100%;height:100%;object-fit:cover;border-radius:4px;">';
          } else {
            smbThumb.textContent = '🎬';
          }
          smbClear.classList.add('visible');
        } else {
          smbBar.classList.add('empty');
          smbName.classList.add('placeholder');
          smbName.textContent = 'No movie selected — click a poster above';
          smbThumb.textContent = '🎬';
          smbClear.classList.remove('visible');
        }
      }

      /* carousel card clicks */
      document.querySelectorAll('.movie-carousel-card').forEach(function(card){
        card.addEventListener('click', function(){
          selectMovie(card.dataset.value, card.dataset.poster);
        });
        card.addEventListener('keydown', function(e){
          if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); selectMovie(card.dataset.value, card.dataset.poster); }
        });
      });

      /* clear button */
      if (smbClear) smbClear.addEventListener('click', function(){ selectMovie('', ''); });

      /* arrow navigation */
      function updateArrows() {
        if (!carousel) return;
        arrowL.classList.toggle('hidden', carousel.scrollLeft <= 4);
        arrowR.classList.toggle('hidden', carousel.scrollLeft >= carousel.scrollWidth - carousel.clientWidth - 4);
      }
      if (arrowL) arrowL.addEventListener('click', function(){ carousel.scrollBy({left: -320, behavior:'smooth'}); });
      if (arrowR) arrowR.addEventListener('click', function(){ carousel.scrollBy({left: 320, behavior:'smooth'}); });
      if (carousel) { carousel.addEventListener('scroll', updateArrows, {passive:true}); updateArrows(); }

      /* drag-to-scroll on carousel */
      (function(){
        if (!carousel) return;
        var isDragging = false, startX, scrollStart;
        carousel.addEventListener('mousedown', function(e){
          isDragging = true; startX = e.pageX; scrollStart = carousel.scrollLeft;
          carousel.classList.add('dragging');
        });
        document.addEventListener('mousemove', function(e){
          if (!isDragging) return;
          carousel.scrollLeft = scrollStart - (e.pageX - startX);
        });
        document.addEventListener('mouseup', function(){ isDragging = false; carousel.classList.remove('dragging'); });
        /* touch */
        carousel.addEventListener('touchstart', function(e){ startX = e.touches[0].pageX; scrollStart = carousel.scrollLeft; }, {passive:true});
        carousel.addEventListener('touchmove', function(e){ carousel.scrollLeft = scrollStart - (e.touches[0].pageX - startX); }, {passive:true});
      })();

      /* ── hero info panel ── */
      var heroInfoCard   = document.getElementById('hero-info-card');
      var heroInfoPoster = document.getElementById('hero-info-poster');
      var heroInfoTitle  = document.getElementById('hero-info-title');
      var heroInfoMeta   = document.getElementById('hero-info-meta');
      var heroInfoCta    = document.getElementById('hero-info-cta');
      var heroPanelTimer;

      function showHeroInfo(movie, poster) {
        clearTimeout(heroPanelTimer);
        if (heroInfoTitle)  heroInfoTitle.textContent  = movie;
        if (heroInfoPoster) heroInfoPoster.innerHTML   = poster
          ? '<img src="'+poster+'" alt="'+movie+'" style="width:100%;height:100%;object-fit:cover;display:block;">'
          : '<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:2rem;">🎬</div>';
        if (heroInfoCard)   heroInfoCard.classList.add('visible');
        /* "Book This Movie" — select it and scroll */
        if (heroInfoCta) {
          heroInfoCta.onclick = function() {
            selectMovie(movie, poster);
            var sec = document.getElementById('booking-section');
            if (sec) sec.scrollIntoView({behavior:'smooth'});
          };
        }
      }
      function hideHeroInfo(delay) {
        heroPanelTimer = setTimeout(function(){
          if (heroInfoCard) heroInfoCard.classList.remove('visible');
        }, delay || 0);
      }

      /* ── strip nav arrows ── */
      var stripTrack = document.getElementById('hero-strip-track');
      var stripPrev  = document.getElementById('strip-prev');
      var stripNext  = document.getElementById('strip-next');
      function updateStripArrows() {
        if (!stripTrack) return;
        if (stripPrev) stripPrev.disabled = stripTrack.scrollLeft <= 4;
        if (stripNext) stripNext.disabled = stripTrack.scrollLeft >= stripTrack.scrollWidth - stripTrack.clientWidth - 4;
      }
      if (stripPrev) stripPrev.addEventListener('click', function(){ stripTrack.scrollBy({left:-360,behavior:'smooth'}); });
      if (stripNext) stripNext.addEventListener('click', function(){ stripTrack.scrollBy({left:360,behavior:'smooth'}); });
      if (stripTrack) { stripTrack.addEventListener('scroll', updateStripArrows, {passive:true}); updateStripArrows(); }

      /* ── strip-card interactions ── */
      var allMovies = <?php echo json_encode(array_map(function($m){ return ['title'=>$m['Title'],'poster'=>$m['Poster']]; }, $movies)); ?>;
      document.querySelectorAll('.strip-card').forEach(function(card){
        /* hover → show info panel, change background */
        card.addEventListener('mouseenter', function(){
          var idx = parseInt(card.dataset.index);
          if (allMovies[idx]) {
            showHeroInfo(allMovies[idx].title, allMovies[idx].poster);
            /* temporarily show that movie's poster as bg if it's within hero slides */
            if (idx < slides.length) { clearInterval(autoTimer); goToSlide(idx); }
          }
        });
        card.addEventListener('mouseleave', function(){
          hideHeroInfo(400);
          startAuto();
        });
        /* click → select movie only, stay on page */
        card.addEventListener('click', function(){
          var idx = parseInt(card.dataset.index);
          document.querySelectorAll('.strip-card').forEach(function(c){ c.classList.remove('active-slide'); });
          card.classList.add('active-slide');
          if (idx < slides.length) { clearInterval(autoTimer); goToSlide(idx); startAuto(); }
          if (allMovies[idx]) {
            selectMovie(allMovies[idx].title, allMovies[idx].poster);
            showHeroInfo(allMovies[idx].title, allMovies[idx].poster);
          }
          /* NO scroll — stays on hero */
        });
      });

      /* keep info visible while hovering the info card itself */
      if (heroInfoCard) {
        heroInfoCard.addEventListener('mouseenter', function(){ clearTimeout(heroPanelTimer); });
        heroInfoCard.addEventListener('mouseleave', function(){ hideHeroInfo(300); startAuto(); });
      }

      /* ── scroll to booking button (Book Now CTA — kept for keyboard access) ── */
      var bookBtn = document.getElementById('hero-book-btn');
      if (bookBtn) {
        bookBtn.addEventListener('click', function(){
          var bookingEl = document.getElementById('booking-section');
          if (bookingEl) bookingEl.scrollIntoView({behavior:'smooth'});
        });
      }
    });
  </script>
</head>

<body class="has-background">

  <!-- ═══════════════════════ HEADER ═══════════════════════ -->
  <header class="site-header" id="site-header">
    <a class="logo" href="homepage.php">
      <img src="logo/newlogo1.png" alt="CineFlix Logo">
    </a>
    <nav class="top-nav">
      <ul>
        <li>
          <span class="staff-label">
            <?php echo htmlspecialchars($staffName); ?>
            <span class="badge-staff">Staff</span>
          </span>
        </li>
        <li class="user-menu">
          <?php $initial = strtoupper(substr((string)$staffName, 0, 1)); ?>
          <div id="avatarBtn" class="avatar"><?php echo $initial; ?></div>
          <div id="userDropdown" class="dropdown">
            <a href="status.php">Status</a>
            <a href="homepage.php">Back to Home</a>
            <a href="logout.php">Logout</a>
          </div>
        </li>
      </ul>
    </nav>
  </header>

  <!-- ═══════════════════════ HERO ═══════════════════════ -->
  <section id="walkin-hero">

    <!-- Background slides — one per movie (up to 5 featured) -->
    <div class="hero-bg-slider">
      <?php foreach (array_slice($movies, 0, 5) as $i => $m): ?>
      <div class="hero-bg-slide"
           style="background-image: url('<?php echo htmlspecialchars($m['Poster']); ?>')"
           data-title="<?php echo htmlspecialchars($m['Title']); ?>">
      </div>
      <?php endforeach; ?>
    </div>

    <div class="hero-overlay"></div>

    <!-- Dynamic movie info panel — shown when a strip card is hovered/selected -->
    <div class="hero-content">
      <div class="hero-info-card" id="hero-info-card">
        <div class="hero-info-poster" id="hero-info-poster">
          <!-- filled by JS -->
        </div>
        <div class="hero-info-text">
          <div class="hero-info-eyebrow" id="hero-info-eyebrow">Now Showing &nbsp;·&nbsp; CineFlix</div>
          <div class="hero-info-title" id="hero-info-title"></div>
          <div class="hero-info-meta" id="hero-info-meta">Walk-in booking &nbsp;·&nbsp; Seats reserved at the counter</div>
          <button type="button" class="hero-info-cta" id="hero-info-cta">
            Book This Movie &nbsp;→
          </button>
        </div>
      </div>
    </div>

    <!-- Card strip at bottom of hero — redesigned two-column layout -->
    <div class="hero-strip-wrap">

      <!-- LEFT: title panel with nav arrows -->
      <div class="strip-left-panel">
        <div>
          <div class="strip-panel-tag">Now Playing</div>
          <div class="strip-panel-heading">Select a<br><span>Movie</span></div>
          <div class="strip-panel-sub">Click any poster to<br>pre-fill your booking</div>
        </div>
        <div class="strip-nav-arrows">
          <button type="button" class="strip-nav-btn" id="strip-prev" aria-label="Scroll left">&#8249;</button>
          <button type="button" class="strip-nav-btn" id="strip-next" aria-label="Scroll right">&#8250;</button>
        </div>
      </div>

      <!-- RIGHT: scrollable movie cards -->
      <div class="hero-strip" id="hero-strip-track">
        <?php foreach ($movies as $i => $m): ?>
        <div class="strip-card" data-index="<?php echo $i; ?>">
          <div class="strip-card-bg"
               style="background-image: url('<?php echo htmlspecialchars($m['Poster']); ?>')"></div>
          <div class="strip-card-overlay"></div>
          <div class="strip-card-label">
            <div class="strip-card-eyebrow">Now Showing</div>
            <div class="strip-card-title"><?php echo htmlspecialchars($m['Title']); ?></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

    </div><!-- /hero-strip-wrap -->

    <!-- Dot indicators -->
    <div class="hero-counter">
      <?php foreach (array_slice($movies, 0, 5) as $i => $m): ?>
      <div class="hero-dot<?php echo $i === 0 ? ' active' : ''; ?>"></div>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- ═══════════════════════ BOOKING FLOW ═══════════════════════ -->
  <main id="booking-section">
    <div class="booking-steps-wrap">
      <div class="container">

        <!-- STEP 1: Schedule -->
        <div class="booking-step active" id="step-schedule">
          <h1 class="page-title">Walk-in <span>Schedule</span></h1>

          <div class="card" style="padding:2.5rem;background:rgba(20,20,20,0.95);max-width:1100px;margin:0 auto;">
            <!-- Movie Info Header (like booking.php) -->
            <div style="display:flex;gap:2rem;margin-bottom:2.5rem;align-items:flex-start;">
              <div id="movie-poster" style="width:120px;height:180px;flex-shrink:0;background:#1a1a1a;border-radius:12px;overflow:hidden;box-shadow:0 10px 30px rgba(0,0,0,0.5);border:1px solid rgba(255,255,255,0.08);"></div>
              <div style="flex:1;">
                <h2 id="selected-movie" style="margin:0 0 0.5rem;font-family:'Poppins',sans-serif;font-size:2rem;letter-spacing:0.04em;color:var(--gold);">Select a Movie</h2>
                <p id="movie-meta" style="color:rgba(255,255,255,0.6);font-size:1rem;margin:0 0 6px;"></p>
                <p class="walkin-note">Seats reserved here share the same map as online bookings. Cut-off: 10 minutes before showtime.</p>
              </div>
            </div>

            <div style="height:1px;background:rgba(255,255,255,0.05);margin-bottom:2rem;"></div>

            <!-- Single movie select — interactive carousel (ONE selection at a time) -->
            <div class="movie-select">
              <label>Select Movie *</label>

              <!-- Hidden native select used by booking.js -->
              <select id="walkin-movie-select" style="display:none !important;position:absolute;pointer-events:none;visibility:hidden;">
                <option value="">-- Choose a movie --</option>
                <?php foreach ($movies as $m): ?>
                  <option value="<?php echo htmlspecialchars($m['Title']); ?>"
                          data-poster="<?php echo htmlspecialchars($m['Poster'] ?? ''); ?>">
                    <?php echo htmlspecialchars($m['Title']); ?>
                  </option>
                <?php endforeach; ?>
              </select>

              <!-- Interactive carousel — drag to scroll, click to select -->
              <div class="movie-carousel-wrap">
                <button type="button" class="carousel-arrow carousel-arrow-left hidden" id="carousel-left" aria-label="Scroll left">&#8249;</button>
                <div class="movie-carousel" id="movie-carousel">
                  <?php foreach ($movies as $i => $m): ?>
                  <div class="movie-carousel-card" data-value="<?php echo htmlspecialchars($m['Title']); ?>" data-poster="<?php echo htmlspecialchars($m['Poster'] ?? ''); ?>" tabindex="0" role="button" aria-label="<?php echo htmlspecialchars($m['Title']); ?>">
                    <div class="movie-carousel-poster">
                      <?php if (!empty($m['Poster'])): ?>
                        <img src="<?php echo htmlspecialchars($m['Poster']); ?>" alt="<?php echo htmlspecialchars($m['Title']); ?>" loading="lazy" onerror="this.parentElement.innerHTML='<div class=movie-carousel-poster-placeholder>🎬</div>'">
                      <?php else: ?>
                        <div class="movie-carousel-poster-placeholder">🎬</div>
                      <?php endif; ?>
                    </div>
                    <span class="movie-carousel-title"><?php echo htmlspecialchars($m['Title']); ?></span>
                  </div>
                  <?php endforeach; ?>
                </div>
                <button type="button" class="carousel-arrow carousel-arrow-right" id="carousel-right" aria-label="Scroll right">&#8250;</button>
              </div>

              <!-- Selected movie display bar -->
              <div class="selected-movie-bar empty" id="selected-movie-bar">
                <div class="selected-movie-bar-thumb" id="smb-thumb">🎬</div>
                <span class="selected-movie-bar-name placeholder" id="smb-name">No movie selected — click a poster above</span>
                <button type="button" class="selected-movie-bar-clear" id="smb-clear" title="Clear selection">✕</button>
              </div>
            </div>

            <div class="staff-fields">
              <h3>Booked by Staff</h3>
              <div class="form-row">
                <input type="text" value="<?php echo htmlspecialchars($staffName); ?>" disabled aria-label="Staff name">
                <input type="email" value="" disabled aria-label="Customer email" style="display:none;">
              </div>
              <p class="walkin-note" style="margin:8px 0 0;">Staff bookings record the staff name only (no customer details).</p>
            </div>

            <!-- ── Date Selection (matching booking.php) ── -->
            <div class="choose-date-section" style="margin-top:1.6rem;">
              <h3 class="section-title-yellow">Choose Date &amp; Time</h3>
              <div class="booking-date-input-wrap">
                <input type="hidden" id="booking-date">
                <div class="booking-date-trigger" id="booking-date-trigger"
                     aria-haspopup="dialog" aria-expanded="false" aria-controls="booking-date-modal-backdrop"
                     style="background:rgba(40,40,40,0.8);border:1px solid rgba(255,255,255,0.1);color:#fff;padding:0.8rem 1.2rem;border-radius:8px;cursor:pointer;display:flex;align-items:center;gap:10px;width:fit-content;">
                  <span class="calendar-icon">📅</span>
                  <span id="booking-date-display" class="date-picker-display-text">03/25/2026</span>
                </div>

                <!-- Custom Date Modal -->
                <div class="booking-date-modal-backdrop" id="booking-date-modal-backdrop" aria-hidden="true"
                     style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.8);z-index:10000;align-items:center;justify-content:center;">
                  <div class="booking-date-modal" role="dialog" aria-modal="true" aria-label="Choose Date & Time"
                       style="background:#1a1a2e;border:1px solid rgba(255,255,255,0.1);border-radius:16px;width:90%;max-width:400px;padding:1.5rem;box-shadow:0 20px 50px rgba(0,0,0,0.5);">
                    <div class="booking-date-modal-title" style="color:#c79f5e;font-size:1.2rem;font-weight:700;margin-bottom:1.5rem;text-align:center;">Choose Date &amp; Time</div>
                    <div class="booking-date-modal-body">
                      <div style="margin-bottom:1rem;">
                        <input type="text" id="booking-date-display-input" class="booking-date-input-display" readonly
                               style="width:100%;background:rgba(0,0,0,0.3);border:1px solid rgba(255,255,255,0.1);color:#fff;padding:0.8rem;border-radius:8px;text-align:center;font-weight:600;">
                      </div>
                      <div class="booking-date-month-nav" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
                        <button type="button" class="booking-date-nav-btn" id="booking-date-prev" aria-label="Previous month"
                                style="background:none;border:1px solid rgba(255,255,255,0.1);color:#fff;width:32px;height:32px;border-radius:50%;cursor:pointer;">‹</button>
                        <div class="booking-date-month-select-wrap">
                          <select id="booking-date-month-select" class="booking-date-month-select" aria-label="Month">
                            <option value="0">January</option>
                            <option value="1">February</option>
                            <option value="2">March</option>
                            <option value="3">April</option>
                            <option value="4">May</option>
                            <option value="5">June</option>
                            <option value="6">July</option>
                            <option value="7">August</option>
                            <option value="8">September</option>
                            <option value="9">October</option>
                            <option value="10">November</option>
                            <option value="11">December</option>
                          </select>
                        </div>
                        <button type="button" class="booking-date-nav-btn" id="booking-date-next" aria-label="Next month"
                                style="background:none;border:1px solid rgba(255,255,255,0.1);color:#fff;width:32px;height:32px;border-radius:50%;cursor:pointer;">›</button>
                      </div>
                      <div class="booking-date-dow" style="display:grid;grid-template-columns:repeat(7,1fr);text-align:center;color:rgba(255,255,255,0.5);font-size:0.8rem;font-weight:700;margin-bottom:0.5rem;">
                        <div>Su</div><div>Mo</div><div>Tu</div><div>We</div><div>Th</div><div>Fr</div><div>Sa</div>
                      </div>
                      <div class="booking-date-days-grid" id="booking-date-days-grid" style="display:grid;grid-template-columns:repeat(7,1fr);gap:4px;"></div>
                      <div class="booking-date-footer" style="margin-top:1.5rem;display:flex;justify-content:space-between;align-items:center;">
                        <button type="button" class="booking-date-clear-btn" id="booking-date-clear"
                                style="background:none;border:none;color:rgba(255,255,255,0.5);cursor:pointer;font-size:0.9rem;">Clear</button>
                        <div class="booking-date-seats-hint" id="booking-date-seats-hint"
                             style="color:#c79f5e;font-size:0.8rem;font-weight:600;">80 seats available</div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- ── Theatre Selection Bar with Radio Pills (matching booking.php) ── -->
            <div class="theatre-selection-bar" id="theatre-selection-bar">
              <span class="theatre-label">Theatre:</span>
              <div class="theatre-pills" id="theatre-pills">
                <label class="theatre-pill">
                  <input type="radio" name="theatre-type" value="Standard" hidden>
                  <span class="pill-dot"></span>
                  <span class="pill-text">Standard</span>
                </label>
                <label class="theatre-pill">
                  <input type="radio" name="theatre-type" value="3D" hidden>
                  <span class="pill-dot"></span>
                  <span class="pill-text">3D</span>
                </label>
                <label class="theatre-pill">
                  <input type="radio" name="theatre-type" value="IMAX" hidden>
                  <span class="pill-dot"></span>
                  <span class="pill-text">IMAX</span>
                </label>
                <label class="theatre-pill">
                  <input type="radio" name="theatre-type" value="Directors Club" hidden>
                  <span class="pill-dot"></span>
                  <span class="pill-text">Directors Club</span>
                </label>
              </div>
            </div>
            <p class="theatre-hint" id="theatre-hint">⚠ Please select a theatre type to see available schedules.</p>

            <!-- ── Schedule Grid (3-column, matching booking.php) ── -->
            <div class="schedule-grid-container" id="schedule-cards">
              <!-- Dynamically generated by booking.js -->
            </div>

            <button type="button" class="btn btn-primary btn-large" id="continue-to-seats" disabled>
              Continue to Select Seats
            </button>
          </div>
        </div>

        <!-- STEP 2: Seat Selection -->
        <div class="booking-step" id="step-seats">
          <h1 class="page-title">Select <span style="color:var(--gold)">Seats</span></h1>
          <div class="card">
            <div class="booking-summary-small">
              <p><strong>Movie:</strong> <span id="summary-movie-2"></span></p>
              <p><strong>Theatre:</strong> <span id="summary-theatre-type-2">Standard</span></p>
              <p><strong>Schedule:</strong> <span id="summary-schedule-2"></span></p>
            </div>

            <!-- ✨ Smart Seat Suggester (from booking.php) -->
            <div class="smart-suggester" id="smart-suggester">
              <div class="suggester-header">
                <div class="suggester-icon">✨</div>
                <div class="suggester-text">
                  <span class="suggester-title">Smart Seat Suggester</span>
                  <span class="suggester-sub">Let us find the perfect seats for you</span>
                </div>
                <button class="suggester-toggle" id="suggester-toggle" aria-expanded="false">
                  <span>Get Suggestion</span>
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                </button>
              </div>
              <div class="suggester-panel" id="suggester-panel">
                <p class="suggester-question">How are you watching today?</p>
                <div class="suggester-modes">
                  <button class="mode-btn" data-mode="solo" data-seats="1">
                    <span class="mode-icon">🎬</span>
                    <span class="mode-label">Solo</span>
                    <span class="mode-desc">Best center view</span>
                  </button>
                  <button class="mode-btn" data-mode="date" data-seats="2">
                    <span class="mode-icon">💑</span>
                    <span class="mode-label">On a Date</span>
                    <span class="mode-desc">Cozy side-by-side</span>
                  </button>
                  <button class="mode-btn" data-mode="companion" data-seats="2">
                    <span class="mode-icon">👫</span>
                    <span class="mode-label">With Companion</span>
                    <span class="mode-desc">Great sightlines</span>
                  </button>
                  <button class="mode-btn" data-mode="group" data-seats="4">
                    <span class="mode-icon">👥</span>
                    <span class="mode-label">Group</span>
                    <span class="mode-desc">Row together</span>
                  </button>
                  <button class="mode-btn" data-mode="private" data-seats="2">
                    <span class="mode-icon">🌙</span>
                    <span class="mode-label">More Private</span>
                    <span class="mode-desc">Corner, away from crowd</span>
                  </button>
                </div>
                <div class="suggester-result" id="suggester-result" style="display:none;">
                  <div class="result-pulse"></div>
                  <span class="result-text" id="suggester-result-text"></span>
                  <button class="result-apply" id="suggester-apply">Apply These Seats</button>
                  <button class="result-dismiss" id="suggester-dismiss">I'll choose myself</button>
                </div>
              </div>
            </div>

            <div class="seats-section">
              <div class="screen-label">SCREEN</div>
              <div class="seats-grid" id="seats-grid"></div>
              <div class="seat-legend">
                <div class="legend-item"><div class="seat-demo available"></div><span>Available</span></div>
                <div class="legend-item"><div class="seat-demo reserved"></div><span>Reserved</span></div>
                <div class="legend-item"><div class="seat-demo selected"></div><span>Selected</span></div>
                <div class="legend-item"><div class="seat-demo suggested"></div><span>Suggested</span></div>
              </div>
              <div class="selected-seats-info">
                <p><strong>Selected Seats:</strong> <span id="selected-seats-display">None</span></p>
                <p class="price-display"><strong>Total:</strong> ₱<span id="total-price">0</span></p>
              </div>
            </div>
            <div class="button-group">
              <button class="btn btn-secondary" id="back-to-schedule-seats">Back</button>
              <button class="btn btn-primary" id="continue-to-addons" disabled>Continue to Payment</button>
            </div>
          </div>
        </div>

        <!-- STEP 3: Hidden stub so booking.js can activate it without showing anything -->
        <div class="booking-step" id="step-addons" style="display:none !important;visibility:hidden;height:0;overflow:hidden;pointer-events:none;" aria-hidden="true"></div>

        <!-- STEP 4: Payment -->
        <div class="booking-step" id="step-payment">
          <h1 class="page-title"><span style="color:var(--gold)">Payment</span></h1>
          <div class="card">
            <div class="payment-layout">
              <div class="booking-summary">
                <h3>Booking Summary</h3>
                <div class="summary-details">
                  <p><strong>Movie:</strong> <span id="summary-movie-3"></span></p>
                  <p><strong>Schedule:</strong> <span id="summary-schedule-3"></span></p>
                  <p><strong>Seats:</strong> <span id="summary-seats"></span></p>
                  <p><strong>Tickets:</strong> <span id="summary-quantity-2"></span></p>
                  <p class="summary-total"><strong>Total Amount:</strong> ₱<span id="summary-price"></span></p>
                </div>
              </div>

              <div class="payment-section">
                <h3 class="section-subtitle">Payment Information</h3>
                <form id="payment-form">
                  <div class="form-group">
                    <label>Discount (PWD / Senior)</label>
                    <div style="display:flex;gap:10px;flex-wrap:wrap;font-size:0.85rem;color:rgba(255,255,255,0.85);">
                      <label><input type="radio" name="discount-type" value="" checked> None</label>
                      <label><input type="radio" name="discount-type" value="pwd"> PWD</label>
                      <label><input type="radio" name="discount-type" value="senior"> Senior</label>
                    </div>
                  </div>
                  <div class="form-group" id="discount-id-group" style="display:none;">
                    <label for="discount-id-number">PWD / Senior ID Number(s)</label>
                    <input type="text" id="discount-id-number" class="form-input" maxlength="255" placeholder="Enter ID number(s) separated by commas">
                    <small class="form-hint">Required when applying a PWD or Senior discount at the counter.</small>
                  </div>

                  <div class="form-group">
                    <label>Select a payment option</label>
                    <div class="payment-methods-grid">
                      <button type="button" class="payment-provider-card" data-method="cash" data-provider="cash">
                        <span style="font-weight:600;">Cash</span>
                      </button>
                      <button type="button" class="payment-provider-card" data-method="ewallet" data-provider="gcash">
                        <img src="paymentlogo/gcash.png" alt="GCash logo">
                      </button>
                      <button type="button" class="payment-provider-card" data-method="ewallet" data-provider="maya">
                        <img src="paymentlogo/maya2.png" alt="Maya logo">
                      </button>
                      <button type="button" class="payment-provider-card" data-method="ewallet" data-provider="paypal">
                        <img src="paymentlogo/paypal.png" alt="PayPal logo">
                      </button>
                      <button type="button" class="payment-provider-card" data-method="ewallet" data-provider="googlepay">
                        <img src="paymentlogo/google.png" alt="Google Pay logo">
                      </button>
                    </div>
                  </div>

                  <div id="payment-details-panel" class="payment-details-panel">
                    <div class="form-group" style="display:none;">
                      <label for="payment-method">Payment Method</label>
                      <select id="payment-method" class="form-input">
                        <option value="cash" selected>Cash</option>
                        <option value="ewallet">E-Wallet</option>
                      </select>
                    </div>

                    <div id="cash-fields">
                      <div class="form-group">
                        <label for="cash-amount">Amount received from customer</label>
                        <input type="number" id="cash-amount" class="form-input" min="0" step="0.01" placeholder="Enter amount given (e.g., 2500)">
                      </div>
                      <div class="form-group">
                        <label>Change to give back</label>
                        <div style="padding:0.75rem 1rem;background:rgba(40,40,40,0.8);border-radius:8px;border:1px solid rgba(199,159,94,0.3);">
                          ₱<span id="cash-change">0.00</span>
                        </div>
                      </div>
                    </div>

                    <div id="card-fields" style="display:none;"></div>

                    <div id="ewallet-fields" style="display:none;">
                      <div class="form-group">
                        <label for="ewallet-provider">E-Wallet Provider</label>
                        <select id="ewallet-provider" class="form-input">
                          <option value="">Select provider</option>
                          <option value="gcash">GCash</option>
                          <option value="maya">Maya</option>
                          <option value="paypal">PayPal</option>
                          <option value="googlepay">Google Pay</option>
                        </select>
                      </div>
                      <div class="form-group">
                        <label for="ewallet-number">Mobile Number</label>
                        <input type="tel" id="ewallet-number" class="form-input" placeholder="09XXXXXXXXX (11 digits, start with 09)" maxlength="11">
                        <small class="form-hint">Must start with 09. Example: 09171234567</small>
                      </div>
                    </div>
                  </div>
                </form>
              </div>
            </div>

            <div class="button-group">
              <button class="btn btn-secondary" id="back-to-addons">Back to Seats</button>
              <button class="btn btn-primary" id="complete-payment">Pay ₱<span id="payment-amount">0</span></button>
            </div>
          </div>
        </div>

        <!-- STEP 5: Confirmation -->
        <div class="booking-step" id="step-confirmation">
          <div class="confirmation-card" data-customer-name="">
            <div class="success-animation">
              <div class="success-icon">✓</div>
            </div>
            <h1 class="confirmation-title">Walk-in Booking Completed</h1>
            <p class="confirmation-subtitle">Seats have been reserved for the customer.</p>

            <div class="receipt-outer">
              <p class="receipt-banner-title">THIS IS YOUR TICKET</p>
              <p class="receipt-banner-sub">Please show it on your phone when you arrive at the venue.</p>

              <div class="ticket-receipt">
                <div class="ticket-receipt-top">
                  <div class="ticket-receipt-logo">CineFlix</div>
                  <div class="ticket-receipt-checkin">
                    <span class="ticket-label">CHECK-IN CODE</span>
                    <span class="ticket-value ticket-checkin-code" id="receipt-booking-id"></span>
                  </div>
                </div>

                <div class="ticket-receipt-qr">
                  <div id="qr-code"></div>
                </div>

                <div class="ticket-receipt-details">
                  <div class="ticket-detail-row">
                    <span class="ticket-label">MOVIE NAME</span>
                    <span class="ticket-value" id="receipt-movie"></span>
                  </div>
                  <div class="ticket-detail-row">
                    <span class="ticket-label">THEATRE TYPE</span>
                    <span class="ticket-value" id="receipt-theatre-type">Standard</span>
                  </div>
                  <div class="ticket-detail-row">
                    <span class="ticket-label">DATE AND TIME</span>
                    <span class="ticket-value" id="receipt-schedule"></span>
                  </div>
                  <div class="ticket-detail-row">
                    <span class="ticket-label">SEATS</span>
                    <span class="ticket-value" id="receipt-seats"></span>
                  </div>
                  <div class="ticket-detail-row">
                    <span class="ticket-label">NUMBER OF TICKETS</span>
                    <span class="ticket-value" id="receipt-quantity"></span>
                  </div>
                  <div class="ticket-detail-row" id="receipt-parking-row" style="display:none;">
                    <span class="ticket-label">PARKING</span>
                    <span class="ticket-value" id="receipt-parking"></span>
                  </div>
                  <div class="ticket-detail-row">
                    <span class="ticket-label">PAYMENT METHOD</span>
                    <span class="ticket-value" id="receipt-payment-method"></span>
                  </div>
                  <div class="ticket-detail-row receipt-discount-block" id="receipt-discount-block" style="display:none;">
                    <span class="ticket-label">SUBTOTAL (BEFORE DISCOUNT)</span>
                    <span class="ticket-value">₱<span id="receipt-original-total"></span></span>
                  </div>
                  <div class="ticket-detail-row receipt-discount-block" id="receipt-discount-line-row" style="display:none;">
                    <span class="ticket-label">PWD / SENIOR DISCOUNT (-20%)</span>
                    <span class="ticket-value receipt-discount-amount">-₱<span id="receipt-discount-amount"></span></span>
                  </div>
                  <div id="receipt-discount-pending" style="display:none; padding: 12px 16px; margin: 8px 0; border-radius: 8px; background: rgba(199,159,94,0.12); border: 1px solid rgba(199,159,94,0.3);">
                    <p style="margin:0 0 4px 0; font-size: 0.82rem; font-weight: 700; color: #c79f5e;">⏳ DISCOUNT PENDING ADMIN APPROVAL</p>
                    <p style="margin:0; font-size: 0.75rem; color: rgba(0,0,0,0.65); line-height: 1.4;">The PWD / Senior discount request has been submitted and is awaiting admin approval. The customer has been charged the full price for now. Once approved, the overpaid amount can be claimed at the cashier.</p>
                  </div>
                  <div class="ticket-detail-row" id="receipt-amount-paid-row" style="display:none;">
                    <span class="ticket-label">AMOUNT TENDERED</span>
                    <span class="ticket-value">₱<span id="receipt-amount-paid"></span></span>
                  </div>
                  <div class="ticket-detail-row" id="receipt-change-row" style="display:none;">
                    <span class="ticket-label">CHANGE</span>
                    <span class="ticket-value">₱<span id="receipt-change"></span></span>
                  </div>
                </div>

                <div class="ticket-receipt-perforation"></div>

                <div class="ticket-receipt-stub">
                  <div class="stub-left">
                    <div class="ticket-detail-row">
                      <span class="ticket-label">CUSTOMER NAME</span>
                      <span class="ticket-value stub-value" id="receipt-customer-name">—</span>
                    </div>
                    <div class="ticket-detail-row">
                      <span class="ticket-label">TICKET PRICE</span>
                      <span class="ticket-value stub-value">₱<span id="receipt-price"></span></span>
                    </div>
                  </div>
                  <div class="stub-right">
                    <div class="ticket-detail-row">
                      <span class="ticket-label">SEAT NUMBER</span>
                      <span class="ticket-value stub-value stub-seats" id="receipt-seats-stub"></span>
                    </div>
                  </div>
                </div>

                <div class="ticket-receipt-footer">CINEFLIX</div>
              </div>
            </div>

            <div class="button-group" style="justify-content:center;">
              <button class="btn btn-primary" onclick="window.location.href='staff-walkin.php'">New Walk-in Booking</button>
              <button class="btn btn-secondary" onclick="window.location.href='homepage.php'">Back to Home</button>
            </div>
          </div>
        </div>

      </div><!-- /container -->
    </div><!-- /booking-steps-wrap -->
  </main>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
  <script src="booking.js?v=2"></script>
  <script>
    window.isStaffWalkin = true;

    // ── Fix: auto-skip the removed Food & Drinks step ──
    // booking.js still handles "continue-to-addons" (which properly sets bookingData),
    // but it activates step-addons which we've hidden. We watch for that activation
    // and immediately forward to step-payment so booking.js state is intact.
    document.addEventListener('DOMContentLoaded', function () {
      var addonsStep = document.getElementById('step-addons');
      var paymentStep = document.getElementById('step-payment');

      function syncPaymentSummary() {
        // Copy summary values from seats step into payment step
        var map = [
          ['summary-movie-2',    'summary-movie-3'],
          ['summary-schedule-2', 'summary-schedule-3'],
        ];
        map.forEach(function(pair) {
          var src = document.getElementById(pair[0]);
          var dst = document.getElementById(pair[1]);
          if (src && dst) dst.textContent = src.textContent;
        });
        var seatsDisp = document.getElementById('selected-seats-display');
        var sumSeats  = document.getElementById('summary-seats');
        var totalPri  = document.getElementById('total-price');
        var sumPrice  = document.getElementById('summary-price');
        var payAmt    = document.getElementById('payment-amount');
        var sumQty    = document.getElementById('summary-quantity-2');
        if (sumSeats && seatsDisp) sumSeats.textContent = seatsDisp.textContent;
        if (sumPrice && totalPri)  sumPrice.textContent = totalPri.textContent;
        if (payAmt   && totalPri)  payAmt.textContent   = totalPri.textContent;
        if (sumQty   && seatsDisp) {
          var seatList = seatsDisp.textContent.replace(/None/i,'').trim();
          sumQty.textContent = seatList ? seatList.split(',').filter(function(x){return x.trim();}).length : 0;
        }
      }

      // Watch step-addons: when booking.js activates it, immediately jump to payment
      if (addonsStep && window.MutationObserver) {
        new MutationObserver(function(mutations) {
          mutations.forEach(function(m) {
            if (m.type === 'attributes' && m.attributeName === 'class') {
              if (addonsStep.classList.contains('active')) {
                // booking.js just activated addons step — skip straight to payment
                addonsStep.classList.remove('active');
                syncPaymentSummary();
                if (paymentStep) {
                  paymentStep.classList.add('active');
                  var booksec = document.getElementById('booking-section');
                  window.scrollTo({top: booksec ? booksec.offsetTop : 0, behavior:'smooth'});
                }
              }
            }
          });
        }).observe(addonsStep, {attributes: true, attributeFilter: ['class']});
      }

      // Fix back button on payment: go back to seats
      var backToAddons = document.getElementById('back-to-addons');
      if (backToAddons) {
        backToAddons.addEventListener('click', function(e) {
          e.stopImmediatePropagation();
          document.querySelectorAll('.booking-step').forEach(function(s){ s.classList.remove('active'); });
          var seatsStep = document.getElementById('step-seats');
          if (seatsStep) {
            seatsStep.classList.add('active');
            var booksec = document.getElementById('booking-section');
            window.scrollTo({top: booksec ? booksec.offsetTop : 0, behavior:'smooth'});
          }
        }, true);
      }
    });

    // ── Theatre pill selection & gate logic (mirrors booking.php) ──
    document.addEventListener('DOMContentLoaded', function () {
      var radios = document.querySelectorAll('input[name="theatre-type"]');
      var continueBtn = document.getElementById('continue-to-seats');
      var hint = document.getElementById('theatre-hint');

      function onTheatreChange() {
        var selected = document.querySelector('input[name="theatre-type"]:checked');
        if (selected) {
          if (hint) hint.style.display = 'none';
          window.selectedTheatreType = selected.value;
          if (typeof bookingData !== 'undefined') bookingData.theatreType = selected.value;
          var t2 = document.getElementById('summary-theatre-type-2');
          if (t2) t2.textContent = selected.value;
          document.querySelectorAll('.new-schedule-card').forEach(function(c){c.classList.remove('selected');});
          if (continueBtn) continueBtn.disabled = true;
          if (typeof generateSchedules === 'function') generateSchedules();
        } else {
          window.selectedTheatreType = null;
          if (continueBtn) continueBtn.disabled = true;
          if (hint) hint.style.display = 'block';
          var sc = document.getElementById('schedule-cards');
          if (sc) sc.innerHTML = '';
        }
      }

      radios.forEach(function(radio){ radio.addEventListener('change', onTheatreChange); });

      if (continueBtn) {
        continueBtn.addEventListener('click', function(e){
          if (!document.querySelector('input[name="theatre-type"]:checked')) {
            e.preventDefault(); e.stopImmediatePropagation();
            if (hint) hint.style.display = 'block';
          }
        }, true);
      }

      // Patch: re-render schedules when date changes
      var dateHidden = document.getElementById('booking-date');
      if (dateHidden) {
        var _origDateVal = dateHidden.value;
        Object.defineProperty(dateHidden, 'value', {
          get: function(){ return _origDateVal; },
          set: function(v){
            _origDateVal = v;
            if (window.selectedTheatreType && typeof generateSchedules === 'function') setTimeout(generateSchedules, 10);
          }
        });
      }

      // Date restriction observer
      (function(){
        function applyDateRestrictions() {
          var now = new Date();
          var minDate = new Date(now.getFullYear(), now.getMonth(), now.getDate());
          document.querySelectorAll('.booking-date-day').forEach(function(cell){
            var d = cell.getAttribute('data-date'); if (!d) return;
            var parts = d.split('-');
            var cellDate = new Date(parseInt(parts[0]), parseInt(parts[1])-1, parseInt(parts[2]));
            if (cellDate < minDate) {
              cell.classList.add('day-disabled'); cell.setAttribute('tabindex','-1'); cell.setAttribute('aria-disabled','true');
            } else {
              cell.classList.remove('day-disabled'); cell.removeAttribute('aria-disabled');
            }
          });
        }
        applyDateRestrictions();
        var grid = document.getElementById('booking-date-days-grid');
        if (grid && window.MutationObserver) new MutationObserver(applyDateRestrictions).observe(grid,{childList:true,subtree:true});
        var monthSel = document.getElementById('booking-date-month-select');
        if (monthSel) monthSel.addEventListener('change', function(){ setTimeout(applyDateRestrictions,50); });
        document.addEventListener('click', function(e){
          var cell = e.target.closest('.day-disabled');
          if (cell){ e.preventDefault(); e.stopImmediatePropagation(); }
        }, true);
      })();

      onTheatreChange();
    });

    // ── Hero slide text updater ──────────────────────────────────────
    (function(){
      var heroMovies = <?php echo json_encode(array_map(function($m){ return ['title'=>$m['Title'],'poster'=>$m['Poster']]; }, array_slice($movies,0,5))); ?>;
      var titleEl = document.querySelector('.hero-title');

      var observer = new MutationObserver(function(){
        var activeSlide = document.querySelector('.hero-bg-slide.active');
        if (!activeSlide) return;
        var slides = Array.from(document.querySelectorAll('.hero-bg-slide'));
        var idx = slides.indexOf(activeSlide);
        if (idx >= 0 && heroMovies[idx] && titleEl) titleEl.textContent = heroMovies[idx].title;
      });
      var sliderEl = document.querySelector('.hero-bg-slider');
      if (sliderEl) observer.observe(sliderEl, {subtree:true, attributes:true, attributeFilter:['class']});
    })();

    // ── Ctrl+Shift+L → Logout ───────────────────────────────────────
    document.addEventListener('keydown', function(e) {
      if (e.ctrlKey && e.shiftKey && (e.key==='L'||e.key==='l') && !e.altKey) {
        e.preventDefault();
        window.location.href = 'logout.php';
      }
    });
  </script>
</body>
</html>