<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>CineFlix</title>
  <link rel="stylesheet" href="homepage.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <style>
    .top-nav ul { display:flex; align-items:center; gap:14px; }
    .user-menu { position: relative; display:flex; align-items:center; }
    .avatar { 
      width: 48px; height: 48px; border-radius: 50%;
      background: linear-gradient(135deg, #242424, #3a3a3a);
      border: 1px solid rgba(255,255,255,.15);
      color:#fff; display:flex; align-items:center; justify-content:center;
      font-weight:700; font-size: 12px; letter-spacing: .5px;
      text-transform: uppercase; box-shadow: 0 4px 12px rgba(0,0,0,.25);
      cursor:pointer; user-select:none;
    }
    .avatar:hover { box-shadow: 0 6px 16px rgba(0,0,0,.35); }
    .dropdown { position:absolute; right:0; top:56px; background:#111; color:#f2f2f2; border:1px solid #2a2a2a; border-radius:10px; box-shadow:0 10px 24px rgba(0,0,0,.35); display:none; min-width:200px; z-index:1000; overflow:hidden; }
    .dropdown a { display:block; padding:12px 14px; color:#eaeaea; text-decoration:none; }
    .dropdown a:hover { background:#1b1b1b; }
  </style>
  <script>
    document.addEventListener('DOMContentLoaded', function(){
      var avatar = document.getElementById('avatarBtn');
      var menu = document.getElementById('userDropdown');
      if (avatar && menu) {
        avatar.addEventListener('click', function(){
          menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
        });
        document.addEventListener('click', function(e){
          if (!avatar.contains(e.target) && !menu.contains(e.target)) {
            menu.style.display = 'none';
          }
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
<<<<<<< HEAD
        <li><a class="nav-btn" href="status.php">Status</a></li>
=======
          
>>>>>>> 39461e1 (bago)
        <?php if (!empty($_SESSION['user_id'])): ?>
          <li class="user-menu">
            <?php $initial = strtoupper(substr((string)($_SESSION['username'] ?? $_SESSION['user_name'] ?? 'U'), 0, 1)); ?>
            <div id="avatarBtn" class="avatar"><?php echo htmlspecialchars($initial); ?></div>
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
    <!-- === Spotlight Section === -->
    <section class="spotlight">
      <div class="spotlight-slide active" style="background-image: url('spolight/chainsawmanV2.jpg');">
        <div class="spotlight-content">
          <h4>#1 Spotlight</h4>
          <h1>Chainsaw Man</h1>
          <p>
           Denji is literally love bombed by the Bomb Devil Reze, a Soviet human-devil hybrid contracted by the Gun Devil to steal Denji's heart
          </p>
          <div class="spotlight-buttons">
            <button class="watch-btn" data-trailer="https://www.youtube.com/embed/GJ1jrCnm-t8?autoplay=1">▶ Watch Trailer</button>
          </div>
        </div>
      </div>

      <div class="spotlight-slide" style="background-image: url('spolight/demonslayerV2.jpg');">
        <div class="spotlight-content">
          <h4>#2 Spotlight</h4>
          <h1>Demon Slayer : Infinity Castle</h1>
          <p>
            The Demon Slayer Corps are drawn into the Infinity Castle, where Tanjiro and the Hashira face 
            terrifying Upper Rank demons in a desperate fight as the final battle against Muzan Kibutsuji begins.
          </p>
          <div class="spotlight-buttons">
            <button class="watch-btn" data-trailer="https://www.youtube.com/embed/x7uLutVRBfI?autoplay=1">▶ Watch Trailer</button>
          </div>
        </div>
      </div>

      <div class="spotlight-slide"  style="background-image: url('spolight/black phone 2.jpg');">
        <div class="spotlight-content">
          <h4>#3 Spotlight</h4>
          <h1>Black Phone 2</h1>
          <p>
            As Finn, now 17, struggles with life after his captivity, his sister begins receiving calls in her 
            dreams from the black phone and seeing disturbing visions of three boys being stalked at a winter camp known as Alpine Lake.
          </p>
          <div class="spotlight-buttons">
            <button class="watch-btn" data-trailer="https://www.youtube.com/embed/DdR-gzFZoDk?autoplay=1">▶ Watch Trailer</button>
          </div>
        </div>
      </div>

      <!-- Navigation Arrows -->
      <button class="spotlight-btn prev">&#10094;</button>
      <button class="spotlight-btn next">&#10095;</button>
    </section>

    <!-- Movie Category Navigation -->
    <div class="movie-categories">
      <button onclick="scrollToSection('now-showing')">Now Showing</button>
      <button onclick="scrollToSection('coming-soon')">Coming Soon</button>
      <button onclick="window.location.href='events.html'">Events</button>
    </div>

    <div class="movie-section" id="now-showing">
      <h2 class="section-title">Now Showing</h2>
      <?php /* the rest of the content remains from original homepage.html */ ?>
      <div class="poster-card" data-trailer="https://www.youtube.com/embed/GJ1jrCnm-t8?autoplay=1" data-movie="Chainsaw Man" data-poster="movies posters/chainsawman.jpg">
        <img src="movies posters/chainsawman.jpg" alt="Chainsaw Man">
        <div class="overlay">
          <div class="overlay-content">
            <span class="poster-title">Chainsaw Man</span>
            <button class="poster-btn trailer-btn">Trailer</button>
            <button class="poster-btn get-tickets-btn">Get Tickets</button>
          </div>
        </div>
      </div>

      <div class="poster-card" data-trailer="https://www.youtube.com/embed/x7uLutVRBfI?autoplay=1" data-movie="Demon Slayer" data-poster="movies posters/demonslayer.jpg">
        <img src="movies posters/demonslayer.jpg" alt="Demon Slayer">
        <div class="overlay">
          <div class="overlay-content">
            <span class="poster-title">Demon Slayer</span>
            <button class="poster-btn trailer-btn">Trailer</button>
            <button class="poster-btn get-tickets-btn">Get Tickets</button>
          </div>
        </div>
      </div>

      <div class="poster-card" data-trailer="https://www.youtube.com/embed/wPosLpgMtTY?autoplay=1" data-movie="Spider-Man 3" data-poster="movies posters/Spider-Man 3 (2007).jpg">
        <img src="movies posters/Spider-Man 3 (2007).jpg" alt="Spider-Man 3">
        <div class="overlay">
          <div class="overlay-content">
            <span class="poster-title">Spider-Man 3</span>
            <button class="poster-btn trailer-btn">Trailer</button>
            <button class="poster-btn get-tickets-btn">Get Tickets</button>
          </div>
        </div>
      </div>

      <div class="poster-card" data-trailer="https://www.youtube.com/embed/DdR-gzFZoDk?autoplay=1" data-movie="The Black Phone 2" data-poster="movies posters/The Black Phone 2.jpg">
        <img src="movies posters/The Black Phone 2.jpg" alt="The Black Phone 2">
        <div class="overlay">
          <div class="overlay-content">
            <span class="poster-title">The Black Phone 2</span>
            <button class="poster-btn trailer-btn">Trailer</button>
            <button class="poster-btn get-tickets-btn">Get Tickets</button>
          </div>
        </div>
      </div>

      <div class="poster-card" data-trailer="https://www.youtube.com/embed/-sAOWhvheK8?autoplay=1" data-movie="Thunderbolts" data-poster="movies posters/Thunderbolts.jpg">
        <img src="movies posters/Thunderbolts.jpg" alt="Thunderbolts">
        <div class="overlay">
          <div class="overlay-content">
            <span class="poster-title">Thunder bolts</span>
            <button class="poster-btn trailer-btn">Trailer</button>
            <button class="poster-btn get-tickets-btn">Get Tickets</button>
          </div>
        </div>
      </div>

      <div class="poster-card" data-trailer="https://www.youtube.com/embed/P9s-FBvB2y4?autoplay=1" data-movie="A Minecraft Movie" data-poster="movies posters/A Minecraft Movie.jpg">
        <img src="movies posters/A Minecraft Movie.jpg" alt="A Minecraft Movie">
        <div class="overlay">
          <div class="overlay-content">
            <span class="poster-title">A Minecraft Movie</span>
            <button class="poster-btn trailer-btn">Trailer</button>
            <button class="poster-btn get-tickets-btn">Get Tickets</button>
          </div>
        </div>
      </div>

      <div class="poster-card" data-trailer="https://www.youtube.com/embed/Mb9CCnM-B4Q?autoplay=1" data-movie="IT Chapter 2" data-poster="movies posters/IT chapter 2.jpg">
        <img src="movies posters/IT chapter 2.jpg" alt="IT chapter 2">
        <div class="overlay">
          <div class="overlay-content">
            <span class="poster-title">IT Chapter 2</span>
            <button class="poster-btn trailer-btn">Trailer</button>
            <button class="poster-btn get-tickets-btn">Get Tickets</button>
          </div>
        </div>
      </div>

      <div class="poster-card" data-trailer="https://www.youtube.com/embed/Q5yWqmYU0Hs?autoplay=1" data-movie="The Long Walk" data-poster="movies posters/The Long Walk.jpg">
        <img src="movies posters/The Long Walk.jpg" alt="The Long Walk">
        <div class="overlay">
          <div class="overlay-content">
            <span class="poster-title">The Long Walk</span>
            <button class="poster-btn trailer-btn">Trailer</button>
            <button class="poster-btn get-tickets-btn">Get Tickets</button>
          </div>
        </div>
      </div>

      <div class="poster-card" data-trailer="https://www.youtube.com/embed/YShVEXb7-ic?autoplay=1" data-movie="Tron Ares" data-poster="movies posters/Tron Ares.jpg">
        <img src="movies posters/Tron Ares.jpg" alt="Tron Ares">
        <div class="overlay">
          <div class="overlay-content">
            <span class="poster-title">Tron Ares</span>
            <button class="poster-btn trailer-btn">Trailer</button>
            <button class="poster-btn get-tickets-btn">Get Tickets</button>
          </div>
        </div>
      </div>

      <div class="poster-card" data-trailer="https://www.youtube.com/embed/1pHDWnXmK7Y?autoplay=1" data-movie="Brave New World" data-poster="movies posters/Captain America.jpg">
        <img src="movies posters/Captain America.jpg" alt="Brave New World">
        <div class="overlay">
          <div class="overlay-content">
            <span class="poster-title">Brave New World</span>
            <button class="poster-btn trailer-btn">Trailer</button>
            <button class="poster-btn get-tickets-btn">Get Tickets</button>
          </div>
        </div>
      </div>
    </div>

    <div class="movie-section" id="coming-soon">
      <h2 class="section-title">Coming Soon</h2>
      
      <div class="poster-card" data-trailer="https://www.youtube.com/embed/48CtX6OgU3s?autoplay=1" data-movie="The Housemaid" data-poster="coming soon movies/the housemaid 2025.jpg">
        <img src="coming soon movies/the housemaid 2025.jpg" alt="The Housemaid">
        <div class="overlay">
          <div class="overlay-content">
            <span class="poster-title">The Housemaid</span>
            <button class="poster-btn trailer-btn">Trailer</button>
            <button class="poster-btn get-tickets-btn">Get Tickets</button>
          </div>
        </div>
      </div>

      <div class="poster-card" data-trailer="https://www.youtube.com/embed/BjkIOU5PhyQ?autoplay=1" data-movie="Zootopia 2" data-poster="coming soon movies/Zootopia 2.jpg">
        <img src="coming soon movies/Zootopia 2.jpg" alt="Zootopia 2">
        <div class="overlay">
          <div class="overlay-content">
            <span class="poster-title">Zootopia 2</span>
            <button class="poster-btn trailer-btn">Trailer</button>
            <button class="poster-btn get-tickets-btn">Get Tickets</button>
          </div>
        </div>
      </div>

      <div class="poster-card" data-trailer="https://www.youtube.com/embed/Ma1x7ikpid8?autoplay=1" data-movie="Avatar : Fire & Ash" data-poster="coming soon movies/avatar fire & ash.jpg">
        <img src="coming soon movies/avatar fire & ash.jpg" alt="Avatar : Fire & Ash">
        <div class="overlay">
          <div class="overlay-content">
            <span class="poster-title">Avatar : Fire & Ash</span>
            <button class="poster-btn trailer-btn">Trailer</button>
            <button class="poster-btn get-tickets-btn">Get Tickets</button>
          </div>
        </div>
      </div>

      <div class="poster-card" data-trailer="https://www.youtube.com/embed/uogrgIasYcA?autoplay=1" data-movie="Now You See Me : Now You Don`t" data-poster="coming soon movies/now you see me.jpg">
        <img src="coming soon movies/now you see me.jpg" alt="Now You See Me : Now You Don`t">
        <div class="overlay">
          <div class="overlay-content">
            <span class="poster-title">Now You See Me : Now You Don`t</span>
            <button class="poster-btn trailer-btn">Trailer</button>
            <button class="poster-btn get-tickets-btn">Get Tickets</button>
          </div>
        </div>
      </div>

      <div class="poster-card" data-trailer="https://www.youtube.com/embed/dSDpoobO6yM?autoplay=1" data-movie="Five Nights at Freddy's 2" data-poster="coming soon movies/FNAF2.jpg">
        <img src="coming soon movies/FNAF2.jpg" alt="Five Nights at Freddy's 2 ">
        <div class="overlay">
          <div class="overlay-content">
            <span class="poster-title">Five Nights at Freddy's 2 </span>
            <button class="poster-btn trailer-btn">Trailer</button>
            <button class="poster-btn get-tickets-btn">Get Tickets</button>
          </div>
        </div>
      </div>

      <div class="poster-card" data-trailer="https://www.youtube.com/embed/w7t2gyIwvDo?autoplay=1" data-movie="Search for Square Pants" data-poster="coming soon movies/spongebob.jpg">
        <img src="coming soon movies/spongebob.jpg" alt="Search for Square Pants">
        <div class="overlay">
          <div class="overlay-content">
            <span class="poster-title">Search for Square Pants</span>
            <button class="poster-btn trailer-btn">Trailer</button>
            <button class="poster-btn get-tickets-btn">Get Tickets</button>
          </div>
        </div>
      </div>
    </div>
  </main>

  <!-- Trailer Modal -->
  <div class="video-modal" id="videoModal">
    <div class="video-container">
      <iframe id="trailerFrame" src="" frameborder="0" allowfullscreen></iframe>
      <span class="close">&times;</span>
    </div>
  </div>

  <script>
    function scrollToSection(sectionId) {
      const section = document.getElementById(sectionId);
      section.scrollIntoView({ behavior: 'smooth' });
    }
  </script>
  <script src="script.js"></script>
</body>
</html>


