<?php
/**
 * Unified site header. Expects $conn (mysqli).
 * Optional before include:
 *   $cineflixNavMerged — if set, must have keys: navProfilePic, navUsername, userInboxItems, userInboxUnread
 *   $headerShowSearch — bool, default false
 *   $siteHeaderMidNavHtml — raw HTML for extra <li> items (trusted, from your pages)
 */
declare(strict_types=1);

if (!isset($conn)) {
    throw new RuntimeException('site_header.php requires $conn');
}

$headerShowSearch = !empty($headerShowSearch);
$siteHeaderMidNavHtml = $siteHeaderMidNavHtml ?? '';

if (!isset($cineflixNavMerged)) {
    require_once __DIR__ . '/../cineflix_nav_helpers.php';
    if (!empty($_SESSION['user_id'])) {
        $cineflixNavMerged = array_merge(cineflix_nav_load_user($conn), cineflix_nav_load_inbox($conn));
    } else {
        $cineflixNavMerged = [
            'navProfilePic' => '',
            'navUsername' => '',
            'userInboxItems' => [],
            'userInboxUnread' => 0,
        ];
    }
}

$navProfilePic = $cineflixNavMerged['navProfilePic'];
$navUsername = $cineflixNavMerged['navUsername'];
$userInboxItems = $cineflixNavMerged['userInboxItems'];
$userInboxUnread = (int)$cineflixNavMerged['userInboxUnread'];
?>
<header class="site-header">
  <a class="logo" href="homepage.php">
    <img src="logo/newlogo1.png" alt="CineFlix Logo" width="200" height="260">
  </a>
  <nav class="top-nav">
    <ul>
      <?php if ($headerShowSearch): ?>
        <li style="position: relative;">
          <div class="search-wrapper">
            <span class="search-icon" aria-hidden="true">🔍</span>
            <input type="text" id="movieSearch" class="search-input" placeholder="Search movies..." autocomplete="off" />
            <div id="searchResults"></div>
          </div>
        </li>
      <?php endif; ?>
      <?php
      if ($siteHeaderMidNavHtml !== '') {
          echo $siteHeaderMidNavHtml;
      }
      ?>
      <?php if (!empty($_SESSION['is_admin'])): ?>
        <li class="header-cluster-item">
          <div class="header-right-cluster">
            <a class="nav-btn" href="status.php">Status</a>
            <?php
            $displayName = $_SESSION['admin_name'] ?? 'Administrator';
            $initial = strtoupper(substr((string)$displayName, 0, 1));
            ?>
            <span class="nav-username-label"><?php echo htmlspecialchars($displayName); ?></span>
            <div class="user-menu">
              <div id="avatarBtn" class="avatar"><?php echo htmlspecialchars($initial); ?></div>
              <div id="userDropdown" class="dropdown">
                <a href="admin-dashboard.php">Admin Dashboard</a>
                <a href="logout.php">Logout</a>
              </div>
            </div>
          </div>
        </li>
      <?php elseif (!empty($_SESSION['is_staff'])): ?>
        <li class="header-cluster-item">
          <div class="header-right-cluster">
            <a class="nav-btn" href="status.php">Status</a>
            <?php
            $displayName = $_SESSION['staff_name'] ?? 'Staff';
            $initial = strtoupper(substr((string)$displayName, 0, 1));
            ?>
            <span class="nav-username-label"><?php echo htmlspecialchars($displayName); ?></span>
            <div class="user-menu">
              <div id="avatarBtn" class="avatar"><?php echo htmlspecialchars($initial); ?></div>
              <div id="userDropdown" class="dropdown">
                <a href="staff-walkin.php">Walk-in Bookings</a>
                <a href="logout.php">Logout</a>
              </div>
            </div>
          </div>
        </li>
      <?php elseif (!empty($_SESSION['user_id'])): ?>
        <?php
        $displayName = $navUsername !== '' ? $navUsername : (string)($_SESSION['username'] ?? $_SESSION['user_name'] ?? 'User');
        $initial = strtoupper(substr($displayName, 0, 1));
        ?>
        <li class="header-cluster-item">
          <div class="header-right-cluster">
            <a class="nav-btn" href="status.php">Status</a>
            <span class="nav-username-label"><?php echo htmlspecialchars($displayName); ?></span>
            <div class="user-menu">
              <div id="avatarBtn" class="avatar"<?php echo $navProfilePic !== '' ? ' style="padding:0;overflow:hidden;"' : ''; ?>>
                <?php if ($navProfilePic !== ''): ?>
                  <img src="<?php echo htmlspecialchars($navProfilePic); ?>" alt="" width="44" height="44" style="width:100%;height:100%;object-fit:cover;display:block;border-radius:50%;">
                <?php else: ?>
                  <?php echo htmlspecialchars($initial); ?>
                <?php endif; ?>
              </div>
              <div id="userDropdown" class="dropdown">
                <a href="user-profile.php">My Account</a>
                <a href="account-settings.php">Settings</a>
                <a href="logout.php">Logout</a>
              </div>
            </div>
            <div class="inbox-wrap">
              <button type="button" class="inbox-bell" id="userInboxBtn" title="Notifications" aria-expanded="false">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                  <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                </svg>
                <span class="inbox-badge" id="inboxBadge"<?php echo $userInboxUnread > 0 ? ' style="display:flex"' : ''; ?>><?php echo $userInboxUnread > 0 ? (string)$userInboxUnread : ''; ?></span>
              </button>
              <div class="inbox-panel" id="userInboxPanel" aria-hidden="true">
                <div class="inbox-ph">
                  <h4>🔔 Notifications</h4>
                  <div class="inbox-ph-actions">
                    <button type="button" class="inbox-clear-btn" id="inboxClearBtn" title="Clear all notifications">Clear all</button>
                    <button type="button" class="inbox-panel-close" onclick="document.getElementById('userInboxPanel').classList.remove('open')" aria-label="Close">✕</button>
                  </div>
                </div>
                <div class="inbox-tabs">
                  <button type="button" class="inbox-tab active" data-tab="all">All</button>
                  <button type="button" class="inbox-tab" data-tab="refund">Refunds</button>
                  <button type="button" class="inbox-tab" data-tab="discount">Discounts</button>
                  <button type="button" class="inbox-tab" data-tab="smart">Smart Alerts</button>
                </div>
                <div class="inbox-list">
                  <div id="inboxItemsWrap">
                    <div id="smartAlertsItems"></div>
                    <?php if (!empty($userInboxItems)): ?>
                      <?php foreach ($userInboxItems as $n): ?>
                        <?php if (($n['_type'] ?? '') === 'refund'): ?>
                          <?php
                          $rs = $n['refund_status'] ?? $n['status'] ?? '';
                          $pillClass = match (strtolower((string)$rs)) {
                              'approved' => 'pill-approved',
                              'rejected' => 'pill-rejected',
                              default => 'pill-pending',
                          };
                          $notifId = $n['booking_id'] . '_refund';
                          ?>
                          <div class="inbox-item"
                               data-notif-type="refund"
                               data-notif-id="<?php echo htmlspecialchars((string)$notifId); ?>"
                               data-booking-id="<?php echo htmlspecialchars((string)$n['booking_id']); ?>"
                               data-status="<?php echo htmlspecialchars((string)$rs); ?>"
                               title="Click to view booking">
                            <div class="inbox-item-row">
                              <div class="inbox-item-title">🔄 Refund — <?php echo htmlspecialchars((string)$n['item_name']); ?></div>
                              <span class="inbox-item-time"><?php
                                $ts = strtotime($n['created_at'] ?? '');
                                $diff = $ts ? time() - $ts : 0;
                                if ($diff < 60) {
                                    echo 'just now';
                                } elseif ($diff < 3600) {
                                    echo floor($diff / 60) . 'm ago';
                                } elseif ($diff < 86400) {
                                    echo floor($diff / 3600) . 'h ago';
                                } elseif ($diff < 604800) {
                                    echo floor($diff / 86400) . 'd ago';
                                } else {
                                    echo $ts ? date('M j', $ts) : '';
                                }
                              ?></span>
                            </div>
                            <div class="inbox-item-sub">₱<?php echo number_format((float)($n['total_amount'] ?? 0), 2); ?> · Booking #<?php echo htmlspecialchars((string)$n['booking_id']); ?></div>
                            <div class="inbox-item-footer">
                              <span class="inbox-pill <?php echo $pillClass; ?>"><?php echo htmlspecialchars(ucfirst((string)($rs ?: 'Pending'))); ?></span>
                              <?php if (in_array($rs, ['Approved', 'Rejected'], true)): ?>
                                <button type="button" class="inbox-read-toggle" title="Mark as read">● read</button>
                              <?php endif; ?>
                            </div>
                          </div>
                        <?php else: ?>
                          <?php
                          $ds = $n['discount_status'] ?? 'Pending';
                          $pillClass = match (strtolower((string)$ds)) {
                              'approved' => 'pill-approved',
                              'rejected' => 'pill-rejected',
                              default => 'pill-pending',
                          };
                          $notifId = $n['booking_id'] . '_discount';
                          ?>
                          <div class="inbox-item"
                               data-notif-type="discount"
                               data-notif-id="<?php echo htmlspecialchars((string)$notifId); ?>"
                               data-booking-id="<?php echo htmlspecialchars((string)$n['booking_id']); ?>"
                               data-status="<?php echo htmlspecialchars((string)$ds); ?>"
                               title="Click to view booking">
                            <div class="inbox-item-row">
                              <div class="inbox-item-title">🏷️ <?php echo strtoupper((string)($n['discount_type'] ?? '')); ?> Discount — <?php echo htmlspecialchars((string)$n['item_name']); ?></div>
                              <span class="inbox-item-time"><?php
                                $ts = strtotime($n['created_at'] ?? '');
                                $diff = $ts ? time() - $ts : 0;
                                if ($diff < 60) {
                                    echo 'just now';
                                } elseif ($diff < 3600) {
                                    echo floor($diff / 60) . 'm ago';
                                } elseif ($diff < 86400) {
                                    echo floor($diff / 3600) . 'h ago';
                                } elseif ($diff < 604800) {
                                    echo floor($diff / 86400) . 'd ago';
                                } else {
                                    echo $ts ? date('M j', $ts) : '';
                                }
                              ?></span>
                            </div>
                            <div class="inbox-item-sub">
                              <?php if (!empty($n['discounted_total'])): ?>Discounted: ₱<?php echo number_format((float)$n['discounted_total'], 2); ?> · <?php endif; ?>Booking #<?php echo htmlspecialchars((string)$n['booking_id']); ?>
                            </div>
                            <div class="inbox-item-footer">
                              <span class="inbox-pill <?php echo $pillClass; ?>"><?php echo htmlspecialchars(ucfirst((string)$ds)); ?></span>
                              <?php if (in_array($ds, ['Approved', 'Rejected'], true)): ?>
                                <button type="button" class="inbox-read-toggle" title="Mark as read">● read</button>
                              <?php endif; ?>
                            </div>
                          </div>
                        <?php endif; ?>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </div>
                  <div class="inbox-cleared-msg" id="inboxClearedMsg">✓ All notifications cleared</div>
                </div>
              </div>
            </div>
          </div>
        </li>
      <?php else: ?>
        <li><a class="nav-btn" href="login.html">Login</a></li>
      <?php endif; ?>
    </ul>
  </nav>
</header>
