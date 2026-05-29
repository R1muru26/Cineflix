<?php
/**
 * Shared nav data: profile picture + username from DB (keeps session in sync),
 * and booking inbox counts for the notification panel.
 */
declare(strict_types=1);

/**
 * @return array{navProfilePic: string, navUsername: string}
 */
function cineflix_nav_load_user($conn): array
{
    $navProfilePic = (string)($_SESSION['profile_picture'] ?? '');
    $navUsername   = '';

    if (empty($_SESSION['user_id'])) {
        return ['navProfilePic' => $navProfilePic, 'navUsername' => $navUsername];
    }

    $uid = (int)$_SESSION['user_id'];
    $userTableName = '';
    $allTablesRes2 = $conn->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE()");
    $allTables2 = [];
    if ($allTablesRes2) {
        while ($t = $allTablesRes2->fetch_assoc()) {
            $allTables2[strtolower($t['TABLE_NAME'])] = $t['TABLE_NAME'];
        }
    }
    foreach (['customeruser','users','user','customer','customers','accounts','member','members','tbl_users'] as $c) {
        if (isset($allTables2[$c])) {
            $userTableName = $allTables2[$c];
            break;
        }
    }
    if (!$userTableName) {
        $navUsername = (string)($_SESSION['username'] ?? '');
        return ['navProfilePic' => $navProfilePic, 'navUsername' => $navUsername];
    }

    $colsRes2 = $conn->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='" . $conn->real_escape_string($userTableName) . "'");
    $tCols2 = [];
    if ($colsRes2) {
        while ($c = $colsRes2->fetch_assoc()) {
            $tCols2[] = strtolower($c['COLUMN_NAME']);
        }
    }
    $idCol2 = 'id';
    foreach (['customerid','id','user_id','customer_id'] as $c) {
        if (in_array($c, $tCols2, true)) {
            $idCol2 = $c;
            break;
        }
    }
    $unCol2 = 'username';
    foreach (['username','user_name'] as $c) {
        if (in_array($c, $tCols2, true)) {
            $unCol2 = $c;
            break;
        }
    }
    $hasPp = in_array('profile_picture', $tCols2, true);
    if (!$hasPp) {
        $conn->query("ALTER TABLE `$userTableName` ADD COLUMN `profile_picture` VARCHAR(500) NULL DEFAULT NULL");
        $hasPp = true;
    }

    $selCols = $hasPp ? "`profile_picture`, `$unCol2`" : "`$unCol2`";
    $dbRow = @$conn->query("SELECT $selCols FROM `$userTableName` WHERE `$idCol2` = $uid LIMIT 1");
    if ($dbRow && ($dr = $dbRow->fetch_assoc())) {
        $navUsername = (string)($dr[$unCol2] ?? '');
        if ($hasPp && !empty($dr['profile_picture'])) {
            $navProfilePic = (string)$dr['profile_picture'];
            $_SESSION['profile_picture'] = $navProfilePic;
        }
        if ($navUsername !== '') {
            $_SESSION['username'] = $navUsername;
        }
    }
    if ($navUsername === '') {
        $navUsername = (string)($_SESSION['username'] ?? '');
    }
    return ['navProfilePic' => $navProfilePic, 'navUsername' => $navUsername];
}

/**
 * @return array{userInboxItems: list<array<string,mixed>>, userInboxUnread: int}
 */
function cineflix_nav_load_inbox($conn): array
{
    $userInboxItems = [];
    $userInboxUnread = 0;

    if (empty($_SESSION['user_id'])) {
        return ['userInboxItems' => $userInboxItems, 'userInboxUnread' => $userInboxUnread];
    }

    $bookingsTableCheck = $conn->query("SHOW TABLES LIKE 'bookings'");
    $hasBookingsTable = $bookingsTableCheck && $bookingsTableCheck->num_rows > 0;
    if (!$hasBookingsTable) {
        return ['userInboxItems' => $userInboxItems, 'userInboxUnread' => $userInboxUnread];
    }

    $uid = (int)$_SESSION['user_id'];

    $inboxRefundResult = $conn->query("SELECT booking_id, item_name, total_amount, refund_status, status, cancelled_date, created_at
      FROM bookings WHERE customer_id = $uid AND refund_status IN ('Approved','Rejected','Pending','Refund Requested')
      ORDER BY created_at DESC LIMIT 50");
    if ($inboxRefundResult) {
        while ($row = $inboxRefundResult->fetch_assoc()) {
            $row['_type'] = 'refund';
            $userInboxItems[] = $row;
            if (in_array($row['refund_status'], ['Approved', 'Rejected'], true)) {
                $userInboxUnread++;
            }
        }
    }

    $inboxDiscountResult = $conn->query("SELECT booking_id, item_name, total_amount, discount_type, discounted_total, discount_status, created_at
      FROM bookings WHERE customer_id = $uid AND discount_type IS NOT NULL
      ORDER BY created_at DESC LIMIT 50");
    if ($inboxDiscountResult) {
        while ($row = $inboxDiscountResult->fetch_assoc()) {
            $row['_type'] = 'discount';
            $userInboxItems[] = $row;
            if (in_array($row['discount_status'] ?? '', ['Approved', 'Rejected'], true)) {
                $userInboxUnread++;
            }
        }
    }

    return ['userInboxItems' => $userInboxItems, 'userInboxUnread' => $userInboxUnread];
}
