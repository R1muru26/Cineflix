<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

$conn = db_get_connection();
cineflix_bootstrap_session_from_cookie($conn);

require_once __DIR__ . '/includes/cineflix_nav_helpers.php';
$isLoggedIn = !empty($_SESSION['user_id']);
$userId = $isLoggedIn ? $_SESSION['user_id'] : null;

$cineflixNavMerged = $isLoggedIn
  ? array_merge(cineflix_nav_load_user($conn), cineflix_nav_load_inbox($conn))
  : ['navProfilePic' => '', 'navUsername' => '', 'userInboxItems' => [], 'userInboxUnread' => 0];
$userInboxItems = $cineflixNavMerged['userInboxItems'];
$userInboxUnread = (int)$cineflixNavMerged['userInboxUnread'];

// Mock data for nutritional info and reviews (in a real app, these would come from the database)
function getNutritionalInfo($itemId) {
    return [
        ['val' => '250', 'label' => 'Kcal'],
        ['val' => '12g', 'label' => 'Fat'],
        ['val' => '35g', 'label' => 'Carbs'],
        ['val' => '5g', 'label' => 'Protein']
    ];
}

function getReviews($itemId) {
    return [
        ['user' => 'Juan Dela Cruz', 'rating' => 5, 'comment' => 'Best popcorn in town! Always fresh and hot.', 'date' => '2024-03-20'],
        ['user' => 'Maria Santos', 'rating' => 4, 'comment' => 'Love the butter flavor, but the large size is really huge!', 'date' => '2024-03-18']
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title>Food & Drinks | CineFlix</title>
  
  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  
  <!-- Styles -->
  <link rel="stylesheet" href="common.css">
  <link rel="stylesheet" href="css/header-nav.css">
  <link rel="stylesheet" href="food_system.css">
  
  <style>
    /* ── Fixed site header — consistent with all pages ── */
    .site-header {
      position: fixed !important;
      top: 0 !important;
      left: 0 !important;
      right: 0 !important;
      z-index: 10050 !important;
      padding: 0 1.5rem !important;
      background: rgba(10, 10, 10, 0.92) !important;
      backdrop-filter: blur(12px) !important;
      -webkit-backdrop-filter: blur(12px) !important;
      border-bottom: 1px solid rgba(199,159,94,0.15) !important;
      display: flex !important;
      align-items: center !important;
      justify-content: space-between !important;
      height: 64px !important;
    }
    .site-header .logo img {
      height: 260px !important;
      width: auto !important;
    }
    .site-header .top-nav ul,
    .site-header ul {
      display: flex !important;
      flex-direction: row !important;
      align-items: center !important;
      gap: 14px !important;
      list-style: none !important;
      margin: 0 !important;
      padding: 0 !important;
    }
    .site-header .top-nav li,
    .site-header li { list-style: none !important; }

    .nav-btn {
      padding: 0.7rem 1.2rem;
      background: rgba(255,255,255,0.08);
      color: #fff;
      text-decoration: none;
      border-radius: 30px;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      border: none;
      font-family: 'Poppins', sans-serif;
      -webkit-tap-highlight-color: transparent;
      transition: background 0.3s ease, transform 0.3s ease, box-shadow 0.3s ease;
    }
    .nav-btn:hover {
      background: rgb(39, 39, 39);
      color: #c79f5e;
      transform: translateY(-5px);
      box-shadow: 0 4px 12px rgba(199, 159, 94, 0.5);
    }

    /* Hide Food & Drinks nav link on its own page — multiple selectors for reliability */
    .site-header a.nav-btn[href="food.php"],
    .site-header a.nav-btn[href="food.php"]:hover,
    .site-header li:has(a[href="food.php"]),
    .site-header .nav-btn.active[href="food.php"],
    .site-header li.active:has(a[href="food.php"]) { display: none !important; }

    main { padding-top: 80px; }

    /* ── Background matching all other pages ── */
    .background-blur {
      position: fixed;
      top: 0; left: 0;
      width: 100%; height: 100%;
      background: linear-gradient(rgba(0,0,0,0.45), rgba(0,0,0,0.45)),
                  url('background/newbackground.png') center/cover fixed no-repeat;
      filter: blur(10px);
      z-index: -1;
    }
    .has-background {
      background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)),
                  url('background/newbackground.png') center/cover fixed no-repeat;
      min-height: 100vh;
    }

    /* Page specific overrides */
    body {
      background: var(--fs-color-bg-dark);
      color: var(--fs-color-text-main);
      min-height: 100vh;
    }
    
    .food-page-layout {
      display: grid;
      grid-template-columns: 280px 1fr;
      gap: var(--fs-spacing-xl);
      max-width: 1400px;
      margin: 0 auto;
      padding: var(--fs-spacing-xl);
    }
    
    @media (max-width: 1023px) {
      .food-page-layout {
        grid-template-columns: 1fr;
      }
      
      .fs-sidebar {
        display: none; /* In a real app, this would be a mobile drawer */
      }
    }
    
    .food-header {
      padding: var(--fs-spacing-2xl) 0;
      text-align: center;
      background: linear-gradient(to bottom, rgba(0,0,0,0.4), transparent);
    }
    
    .food-category-title {
      font-size: var(--fs-font-size-2xl);
      color: var(--fs-color-primary);
      margin-bottom: var(--fs-spacing-lg);
      display: flex;
      align-items: center;
      gap: var(--fs-spacing-md);
    }
    
    .food-category-title::after {
      content: "";
      flex: 1;
      height: 1px;
      background: var(--fs-color-border);
    }
    
    /* Food Detail Modal */
    .fs-modal {
      position: fixed;
      inset: 0;
      z-index: 3000;
      background: rgba(0,0,0,0.8);
      backdrop-filter: blur(8px);
      display: none;
      align-items: center;
      justify-content: center;
      padding: var(--fs-spacing-lg);
    }
    
    .fs-modal.open {
      display: flex;
    }
    
    .fs-modal-content {
      background: var(--fs-color-bg-dark);
      border: 1px solid var(--fs-color-border);
      border-radius: var(--fs-radius-xl);
      width: 100%;
      max-width: 1000px;
      max-height: 90vh;
      overflow-y: auto;
      position: relative;
    }
    
    .fs-modal-close {
      position: absolute;
      top: var(--fs-spacing-md);
      right: var(--fs-spacing-md);
      z-index: 10;
      background: rgba(0,0,0,0.5);
      border: none;
      color: #fff;
      width: 40px;
      height: 40px;
      border-radius: 50%;
      cursor: pointer;
      font-size: 24px;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: background var(--fs-transition-fast);
    }
    
    .fs-modal-close:hover {
      background: var(--fs-color-error);
    }
    
    .review-card {
      background: rgba(255, 255, 255, 0.03);
      padding: var(--fs-spacing-md);
      border-radius: var(--fs-radius-md);
      margin-bottom: var(--fs-spacing-md);
    }
    
    .review-header {
      display: flex;
      justify-content: space-between;
      margin-bottom: var(--fs-spacing-xs);
    }
    
    .review-user {
      font-weight: var(--fs-font-weight-bold);
      font-size: var(--fs-font-size-sm);
    }
    
    .review-rating {
      color: #ffd700;
    }
    
    .review-date {
      font-size: var(--fs-font-size-xs);
      color: var(--fs-color-text-muted);
    }
  </style>
  <?php require __DIR__ . '/includes/partials/site_header_scripts.php'; ?>
</head>
<body class="has-background">
  <div class="background-blur"></div>

  <?php
  $siteHeaderMidNavHtml = '<li><a class="nav-btn" href="homepage.php">Home</a></li><li><a class="nav-btn" href="nowshowing.php">Movies</a></li><li><a class="nav-btn" href="status.php">Status</a></li>';
  $headerShowSearch = false;
  require __DIR__ . '/includes/partials/site_header.php';
  ?>
  <script>
    // Remove any nav link pointing to food.php (current page) from the header
    document.addEventListener('DOMContentLoaded', function() {
      document.querySelectorAll('.site-header a[href="food.php"], .site-header a[href*="food"]').forEach(function(el) {
        var li = el.closest('li');
        if (li) li.remove(); else el.remove();
      });
    });
  </script>

  <main>
    <div class="food-header">
      <div class="container">
        <h1 class="fs-text-fluid-h1">Fresh Food & Chilled Drinks</h1>
        <p class="fs-text-fluid-p" style="color: var(--fs-color-text-muted); max-width: 600px; margin: var(--fs-spacing-md) auto;">
          Enhance your movie experience with our selection of snacks, meals, and beverages.
          Delivered straight to your seat.
        </p>
      </div>
    </div>

    <div class="food-page-layout">
      <!-- Sidebar / Filters -->
      <aside class="fs-sidebar">
        <div class="fs-filter-group">
          <span class="fs-filter-label">Categories</span>
          <div class="fs-filter-option active" data-category="all">
            <div class="fs-filter-checkbox"></div>
            <span>All Items</span>
          </div>
          <div class="fs-filter-option" data-category="popcorn">
            <div class="fs-filter-checkbox"></div>
            <span>Popcorn</span>
          </div>
          <div class="fs-filter-option" data-category="drinks">
            <div class="fs-filter-checkbox"></div>
            <span>Drinks</span>
          </div>
          <div class="fs-filter-option" data-category="combos">
            <div class="fs-filter-checkbox"></div>
            <span>Combos</span>
          </div>
        </div>

        <div class="fs-filter-group">
          <span class="fs-filter-label">Sort By</span>
          <select class="fs-chat-input" id="food-sort" style="width: 100%; appearance: auto;">
            <option value="popular">Most Popular</option>
            <option value="price-low">Price: Low to High</option>
            <option value="price-high">Price: High to Low</option>
          </select>
        </div>

        <div class="fs-filter-group">
          <span class="fs-filter-label">Quick Actions</span>
          <button class="fs-btn fs-btn-primary" style="width: 100%;" onclick="cineflix.toggleNotifications()">
            <span>📦 Track Active Order</span>
          </button>
        </div>
      </aside>

      <!-- Food Listing Grid -->
      <div id="food-listing">
        <div id="food-sections-container">
          <!-- Populated by JavaScript -->
          <div class="fs-grid" id="food-grid">
            <!-- Loading skeleton would go here -->
          </div>
        </div>
      </div>
    </div>
  </main>

  <!-- Food Detail Modal -->
  <div class="fs-modal" id="food-detail-modal">
    <div class="fs-modal-content">
      <button class="fs-modal-close" onclick="closeFoodDetail()">&times;</button>
      <div id="food-detail-body">
        <!-- Populated by JavaScript -->
      </div>
    </div>
  </div>

  <!-- Chatbot Integration -->
  <script src="chatbot.js"></script>

  <script>
    // Food Management System Logic
    let menuData = null;

    async function loadMenu() {
        try {
            const response = await fetch('api/food_ordering.php?action=get_menu');
            menuData = await response.json();
            renderMenu('all');
        } catch (error) {
            console.error('Failed to load menu:', error);
            document.getElementById('food-grid').innerHTML = '<p>Error loading menu. Please try again later.</p>';
        }
    }

    function renderMenu(filterCategory) {
        const grid = document.getElementById('food-grid');
        grid.innerHTML = '';
        
        const sortType = document.getElementById('food-sort').value;
        
        menuData.categories.forEach(category => {
            if (filterCategory !== 'all' && category.id !== filterCategory) return;
            
            // Filter and Sort items within category if needed
            let items = [...category.items];
            
            if (sortType === 'price-low') {
                items.sort((a, b) => a.price - b.price);
            } else if (sortType === 'price-high') {
                items.sort((a, b) => b.price - a.price);
            }
            
            // Add category title
            const catTitle = document.createElement('h2');
            catTitle.className = 'food-category-title';
            catTitle.style.gridColumn = '1 / -1';
            catTitle.textContent = category.name;
            grid.appendChild(catTitle);
            
            items.forEach(item => {
                const card = createFoodCard(item);
                grid.appendChild(card);
            });
        });
    }

    // Sort Logic
    document.getElementById('food-sort').addEventListener('change', function() {
        const activeCategory = document.querySelector('.fs-filter-option.active').dataset.category;
        renderMenu(activeCategory);
    });

    function createFoodCard(item) {
        const div = document.createElement('div');
        div.className = 'fs-food-card';
        div.onclick = (e) => {
            if (e.target.closest('.fs-btn')) return;
            openFoodDetail(item);
        };
        
        div.innerHTML = `
            <div class="fs-food-card-img-wrap">
                <img src="${item.image}" alt="${item.name}" loading="lazy">
            </div>
            <div class="fs-food-card-content">
                <h3 class="fs-food-card-title">${item.name}</h3>
                <p class="fs-food-card-desc">${item.description}</p>
                <div class="fs-food-card-footer">
                    <span class="fs-food-card-price">₱${item.price}</span>
                    ${item.savings ? `<span class="fs-food-card-badge">Save ₱${item.savings}</span>` : ''}
                </div>
                <button class="fs-btn fs-btn-primary" style="margin-top: var(--fs-spacing-md);" onclick="addToCartFromPage('${item.id}', '${item.name}', ${item.price})">
                    <span>🛒 Add to Cart</span>
                </button>
            </div>
        `;
        return div;
    }

    function openFoodDetail(item) {
        const modal = document.getElementById('food-detail-modal');
        const body = document.getElementById('food-detail-body');
        
        // Mock data for nutritional info and reviews
        const nutrients = [
            {val: '250', label: 'Kcal'},
            {val: '12g', label: 'Fat'},
            {val: '35g', label: 'Carbs'},
            {val: '5g', label: 'Protein'}
        ];
        
        body.innerHTML = `
            <div class="fs-detail-container">
                <div class="fs-detail-hero">
                    <img src="${item.image}" alt="${item.name}">
                </div>
                <div class="fs-detail-info">
                    <h1 class="fs-text-fluid-h1" style="color: var(--fs-color-primary);">${item.name}</h1>
                    <p class="fs-text-fluid-p">${item.description}</p>
                    
                    <div class="fs-food-card-price" style="font-size: var(--fs-font-size-3xl);">₱${item.price}</div>
                    
                    <div>
                        <h4 class="fs-detail-section-title">Nutritional Information</h4>
                        <div class="fs-nutritional-info">
                            ${nutrients.map(n => `
                                <div class="fs-nutri-item">
                                    <span class="fs-nutri-val">${n.val}</span>
                                    <span class="fs-nutri-label">${n.label}</span>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                    
                    <div>
                        <h4 class="fs-detail-section-title">Customer Reviews</h4>
                        <div class="review-card">
                            <div class="review-header">
                                <span class="review-user">Juan Dela Cruz</span>
                                <span class="review-rating">★★★★★</span>
                            </div>
                            <p class="fs-text-fluid-p" style="font-size: var(--fs-font-size-sm);">Best popcorn in town! Always fresh and hot.</p>
                            <span class="review-date">March 20, 2024</span>
                        </div>
                        <div class="review-card">
                            <div class="review-header">
                                <span class="review-user">Maria Santos</span>
                                <span class="review-rating">★★★★☆</span>
                            </div>
                            <p class="fs-text-fluid-p" style="font-size: var(--fs-font-size-sm);">Love the butter flavor!</p>
                            <span class="review-date">March 18, 2024</span>
                        </div>
                    </div>
                    
                    <div style="margin-top: auto; padding-top: var(--fs-spacing-lg);">
                        <button class="fs-btn fs-btn-primary" style="width: 100%; padding: var(--fs-spacing-md);" onclick="addToCartFromPage('${item.id}', '${item.name}', ${item.price})">
                            <span style="font-size: var(--fs-font-size-lg);">🛒 Add to Order</span>
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        modal.classList.add('open');
        document.body.style.overflow = 'hidden';
    }

    function closeFoodDetail() {
        const modal = document.getElementById('food-detail-modal');
        modal.classList.remove('open');
        document.body.style.overflow = '';
    }

    function addToCartFromPage(id, name, price) {
        // Integrate with existing chatbot cart system
        if (window.cineflix && typeof window.cineflix.addToCart === 'function') {
            window.cineflix.addToCart(id, name, price, 1);
            // Open chatbot to show added item
            const chatbotPanel = document.getElementById('cfPanel');
            if (chatbotPanel) chatbotPanel.style.display = 'flex';
        } else {
            // Fallback for standalone use
            console.log('Adding to cart:', {id, name, price});
            alert('Item added to your order!');
        }
    }

    // Sidebar Category Filter Logic
    document.querySelectorAll('.fs-filter-option').forEach(option => {
        option.addEventListener('click', function() {
            document.querySelectorAll('.fs-filter-option').forEach(opt => opt.classList.remove('active'));
            this.classList.add('active');
            renderMenu(this.dataset.category);
        });
    });

    // Initial Load
    document.addEventListener('DOMContentLoaded', loadMenu);
  </script>

</body>
</html>