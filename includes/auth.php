<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Lightweight "remember me" helper.
 *
 * When a user ticks Remember Me during login, we issue a signed cookie containing
 * their user ID and email. On subsequent visits (when the PHP session is gone),
 * we validate that cookie and silently restore the session.
 */

const CINEFLIX_REMEMBER_COOKIE = 'cineflix_remember';
const CINEFLIX_REMEMBER_SECRET = 'cineflix-remember-secret-2026';

function cineflix_issue_remember_cookie(int $userId, string $email): void
{
    $userId = (int)$userId;
    $email  = trim($email);
    if ($userId <= 0 || $email === '') {
        return;
    }

    $payload = $userId . '|' . $email;
    $signature = hash_hmac('sha256', $payload, CINEFLIX_REMEMBER_SECRET);
    $cookieValue = base64_encode($payload . '|' . $signature);

    // 30 days
    $expiry = time() + (30 * 24 * 60 * 60);
    setcookie(
        CINEFLIX_REMEMBER_COOKIE,
        $cookieValue,
        $expiry,
        '/',
        '',
        isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        true
    );
}

function cineflix_clear_remember_cookie(): void
{
    if (!isset($_COOKIE[CINEFLIX_REMEMBER_COOKIE])) {
        return;
    }
    setcookie(
        CINEFLIX_REMEMBER_COOKIE,
        '',
        time() - 3600,
        '/',
        '',
        isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        true
    );
    unset($_COOKIE[CINEFLIX_REMEMBER_COOKIE]);
}

/**
 * If there is no logged-in user in the current session but a valid remember-me
 * cookie is present, restore the basic customer session.
 *
 * This is intentionally conservative and only restores normal customer users
 * (never admin or staff).
 */
function cineflix_bootstrap_session_from_cookie($conn): void
{
    if (!empty($_SESSION['user_id'])) {
        return;
    }
    if (empty($_COOKIE[CINEFLIX_REMEMBER_COOKIE])) {
        return;
    }

    $raw = base64_decode((string)$_COOKIE[CINEFLIX_REMEMBER_COOKIE], true);
    if ($raw === false) {
        cineflix_clear_remember_cookie();
        return;
    }

    $parts = explode('|', $raw);
    if (count($parts) !== 3) {
        cineflix_clear_remember_cookie();
        return;
    }

    [$idStr, $email, $signature] = $parts;
    $payload = $idStr . '|' . $email;
    $expectedSig = hash_hmac('sha256', $payload, CINEFLIX_REMEMBER_SECRET);
    if (!hash_equals($expectedSig, $signature)) {
        cineflix_clear_remember_cookie();
        return;
    }

    $userId = (int)$idStr;
    if ($userId <= 0 || $email === '') {
        cineflix_clear_remember_cookie();
        return;
    }

    $stmt = $conn->prepare("SELECT CustomerID, Name, Username, Email FROM CustomerUser WHERE CustomerID = ? AND Email = ?");
    if (!$stmt) {
        return;
    }
    $stmt->bind_param('is', $userId, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $_SESSION['user_id']    = (int)$user['CustomerID'];
        $_SESSION['user_email'] = $user['Email'];
        $_SESSION['user_name']  = $user['Name'];
        $_SESSION['username']   = $user['Username'];
    } else {
        cineflix_clear_remember_cookie();
    }
    $stmt->close();
}

