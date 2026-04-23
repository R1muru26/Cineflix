<?php
/**
 * Header interactions: avatar dropdown + notification inbox.
 * Expects $userInboxItems and $userInboxUnread when customer is logged in.
 */
declare(strict_types=1);

$userInboxItems = $userInboxItems ?? [];
$userInboxUnread = (int)($userInboxUnread ?? 0);
?>
<script>
(function () {
  document.addEventListener('DOMContentLoaded', function () {
    var avatar = document.getElementById('avatarBtn');
    var menu = document.getElementById('userDropdown');
    if (avatar && menu) {
      avatar.addEventListener('click', function (e) {
        e.stopPropagation();
        menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
      });
      document.addEventListener('click', function (e) {
        if (!avatar.contains(e.target) && !menu.contains(e.target)) {
          menu.style.display = 'none';
        }
      });
    }

    var inboxBtn = document.getElementById('userInboxBtn');
    var inboxPanel = document.getElementById('userInboxPanel');
    if (inboxBtn && inboxPanel) {

    var UID = '<?php echo (int)($_SESSION['user_id'] ?? 0); ?>';
    var INBOX_SEEN = 'cineflix_inbox_seen_' + UID;
    var INBOX_READ = 'cineflix_inbox_read_' + UID;
    var INBOX_CLEARED = 'cineflix_inbox_cleared_' + UID;

    var ALL_NOTIFS = <?php
      $notifArr = [];
      foreach ($userInboxItems as $n) {
          $notifArr[] = [
              'id' => $n['booking_id'] ?? '',
              'type' => $n['_type'] ?? '',
              'status' => ($n['_type'] ?? '') === 'refund' ? ($n['refund_status'] ?? '') : ($n['discount_status'] ?? ''),
              'created' => $n['created_at'] ?? '',
          ];
      }
      echo json_encode($notifArr);
    ?>;

    var badge = document.getElementById('inboxBadge');
    var clearBtn = document.getElementById('inboxClearBtn');
    var itemsWrap = document.getElementById('inboxItemsWrap');
    var clearedMsg = document.getElementById('inboxClearedMsg');

    function getReadIds() {
      try { return JSON.parse(localStorage.getItem(INBOX_READ) || '[]'); } catch (e) { return []; }
    }
    function markRead(id) {
      var ids = getReadIds();
      if (!ids.includes(id)) { ids.push(id); localStorage.setItem(INBOX_READ, JSON.stringify(ids)); }
    }
    function markUnread(id) {
      var ids = getReadIds().filter(function (x) { return x !== id; });
      localStorage.setItem(INBOX_READ, JSON.stringify(ids));
    }
    function isRead(id) { return getReadIds().includes(id); }

    function getClearedAt() {
      return parseInt(localStorage.getItem(INBOX_CLEARED) || '0', 10);
    }

    function computeUnread() {
      var clearedAt = getClearedAt();
      var count = 0;
      ALL_NOTIFS.forEach(function (n) {
        if (n.status !== 'Approved' && n.status !== 'Rejected') return;
        var notifTime = n.created ? new Date(n.created.replace(' ', 'T')).getTime() : 0;
        if (notifTime > clearedAt && !isRead(n.id + '_' + n.type)) count++;
      });
      return count;
    }

    function refreshBadge() {
      if (!badge) return;
      var count = computeUnread();
      if (count > 0) {
        badge.textContent = count;
        badge.style.display = 'flex';
      } else {
        badge.style.display = 'none';
      }
    }
    refreshBadge();

    var activeTab = 'all';
    document.querySelectorAll('.inbox-tab').forEach(function (tab) {
      tab.addEventListener('click', function (e) {
        e.stopPropagation();
        activeTab = this.dataset.tab;
        document.querySelectorAll('.inbox-tab').forEach(function (t) { t.classList.remove('active'); });
        this.classList.add('active');
        applyTabFilter();
      });
    });

    function applyTabFilter() {
      if (!itemsWrap || itemsWrap.style.display === 'none') return;
      itemsWrap.querySelectorAll('.inbox-item').forEach(function (item) {
        var t = item.dataset.notifType;
        item.style.display = (activeTab === 'all' || activeTab === t) ? '' : 'none';
      });
    }

    window.toggleUserInbox = function (e) {
      if (e) e.stopPropagation();
      var panel = document.getElementById('userInboxPanel');
      var btn = document.getElementById('userInboxBtn');
      if (!panel) return;
      var isOpen = panel.classList.toggle('open');
      if (menu) menu.style.display = 'none';
      if (btn) btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
      panel.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
      if (isOpen) {
        ALL_NOTIFS.forEach(function (n) {
          if (n.status === 'Approved' || n.status === 'Rejected') {
            markRead(n.id + '_' + n.type);
          }
        });
        refreshBadge();
        applyTabFilter();
      }
    };

    inboxBtn.addEventListener('click', toggleUserInbox);

    document.addEventListener('click', function (e) {
      if (!inboxPanel.contains(e.target) && !e.target.closest('#userInboxBtn')) {
        inboxPanel.classList.remove('open');
        inboxBtn.setAttribute('aria-expanded', 'false');
        inboxPanel.setAttribute('aria-hidden', 'true');
      }
    });

    if (itemsWrap) {
      itemsWrap.addEventListener('click', function (e) {
        var toggleBtn = e.target.closest('.inbox-read-toggle');
        if (toggleBtn) {
          e.stopPropagation();
          var item = toggleBtn.closest('.inbox-item');
          var nid = item ? item.dataset.notifId : null;
          if (!nid) return;
          if (item.classList.contains('unread')) {
            markRead(nid);
            item.classList.remove('unread');
            toggleBtn.title = 'Mark as unread';
            toggleBtn.textContent = '● read';
          } else {
            markUnread(nid);
            item.classList.add('unread');
            toggleBtn.title = 'Mark as read';
            toggleBtn.textContent = '○ unread';
          }
          refreshBadge();
          return;
        }
        var row = e.target.closest('.inbox-item');
        if (row && row.dataset.bookingId) {
          window.location.href = 'status.php#booking-' + row.dataset.bookingId;
        }
      });
    }

    if (clearBtn) {
      clearBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        localStorage.setItem(INBOX_CLEARED, Date.now().toString());
        ALL_NOTIFS.forEach(function (n) { markRead(n.id + '_' + n.type); });
        if (badge) badge.style.display = 'none';
        if (itemsWrap) itemsWrap.style.display = 'none';
        if (clearedMsg) clearedMsg.style.display = 'block';
        clearBtn.style.display = 'none';
      });
    }

    (function () {
      var clearedAt = getClearedAt();
      if (!clearedAt) return;
      var hasNew = ALL_NOTIFS.some(function (n) {
        if (n.status !== 'Approved' && n.status !== 'Rejected') return false;
        var t = n.created ? new Date(n.created.replace(' ', 'T')).getTime() : 0;
        return t > clearedAt;
      });
      if (!hasNew && itemsWrap) {
        itemsWrap.style.display = 'none';
        if (clearedMsg) clearedMsg.style.display = 'block';
        if (clearBtn) clearBtn.style.display = 'none';
      }
    })();

    if (itemsWrap) {
      itemsWrap.querySelectorAll('.inbox-item').forEach(function (item) {
        var nid = item.dataset.notifId;
        var status = item.dataset.status;
        if ((status === 'Approved' || status === 'Rejected') && !isRead(nid)) {
          item.classList.add('unread');
          var btnr = item.querySelector('.inbox-read-toggle');
          if (btnr) { btnr.textContent = '○ unread'; btnr.title = 'Mark as read'; }
        }
      });
    }

    }
  });
})();
</script>
