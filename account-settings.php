<?php
session_start();

if (empty($_SESSION['user_id'])) {
    header('Location: login.html');
    exit();
}

require_once __DIR__ . '/includes/db.php';
$db = db_get_connection();

$userId       = (int)$_SESSION['user_id'];
$userName     = $_SESSION['user_name'] ?? 'User';
$userUsername = $_SESSION['username'] ?? $userName;
$userEmail    = $_SESSION['user_email'] ?? '';

$successMessage = '';
$errorMessage   = '';

// ── Auto-detect table & column names ─────────────────────────────────
$userTableName = '';
$allTablesRes = $db->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE()");
$allTables = [];
if ($allTablesRes) { while ($t = $allTablesRes->fetch_assoc()) $allTables[] = strtolower($t['TABLE_NAME']); }

$candidates = ['customeruser','users','user','customer','customers','accounts','member','members','tbl_users','tbl_user'];
foreach ($candidates as $c) {
    if (in_array(strtolower($c), $allTables)) {
        $realRes = $db->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = DATABASE() AND LOWER(TABLE_NAME) = '" . $db->real_escape_string($c) . "' LIMIT 1");
        if ($realRes && ($rr = $realRes->fetch_assoc())) { $userTableName = $rr['TABLE_NAME']; break; }
    }
}

$userIdCol = 'id';
$userUnCol = 'username';
if ($userTableName) {
    $colsRes = $db->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '" . $db->real_escape_string($userTableName) . "'");
    $tableCols = [];
    if ($colsRes) { while ($c = $colsRes->fetch_assoc()) $tableCols[] = strtolower($c['COLUMN_NAME']); }

    foreach (['customerid','id','user_id','customer_id'] as $c) {
        if (in_array($c, $tableCols)) { $userIdCol = $c; break; }
    }
    foreach (['username','user_name'] as $c) {
        if (in_array($c, $tableCols)) { $userUnCol = $c; break; }
    }

    if (!in_array('profile_picture', $tableCols)) {
        $db->query("ALTER TABLE `$userTableName` ADD COLUMN `profile_picture` VARCHAR(500) NULL DEFAULT NULL");
    }
}

// ── Fetch current profile picture & username ──────────────────────────
$currentAvatar = $_SESSION['profile_picture'] ?? '';
$dbUsername    = $userUsername;
if ($userTableName) {
    $safeId = (int)$userId;
    $fetchRes = $db->query("SELECT `profile_picture`, `$userUnCol` FROM `$userTableName` WHERE `$userIdCol` = $safeId LIMIT 1");
    if ($fetchRes && ($r = $fetchRes->fetch_assoc())) {
        $currentAvatar = $r['profile_picture'] ?? $currentAvatar;
        if (!empty($r[$userUnCol])) $dbUsername = $r[$userUnCol];
        $_SESSION['profile_picture'] = $currentAvatar;
        $_SESSION['username']        = $dbUsername;
    }
}

// ── Handle username availability check (AJAX) ────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['check_username'])) {
    header('Content-Type: application/json');
    $newUn = trim($_POST['check_username']);
    if ($newUn === $dbUsername) {
        echo json_encode(['available' => true, 'same' => true]);
        exit();
    }
    if (strlen($newUn) < 3 || strlen($newUn) > 30 || !preg_match('/^[a-zA-Z0-9_]+$/', $newUn)) {
        echo json_encode(['available' => false, 'error' => 'Username must be 3–30 characters (letters, numbers, underscores only).']);
        exit();
    }
    if ($userTableName) {
        $safeUn = $db->real_escape_string($newUn);
        $res = $db->query("SELECT `$userIdCol` FROM `$userTableName` WHERE `$userUnCol` = '$safeUn' AND `$userIdCol` != $userId LIMIT 1");
        if ($res && $res->num_rows > 0) {
            echo json_encode(['available' => false, 'error' => 'That username is already taken.']);
            exit();
        }
    }
    echo json_encode(['available' => true]);
    exit();
}

// ── Handle account info update (username + full name) ────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_account_info'])) {
    header('Content-Type: application/json');
    $newUn   = trim($_POST['new_username']  ?? '');
    $newName = trim($_POST['new_full_name'] ?? '');
    $errors  = [];

    // Validate username
    if ($newUn !== $dbUsername) {
        if (strlen($newUn) < 3 || strlen($newUn) > 30 || !preg_match('/^[a-zA-Z0-9_]+$/', $newUn)) {
            $errors[] = 'Username must be 3–30 characters (letters, numbers, underscores only).';
        } else if ($userTableName) {
            $safeUn = $db->real_escape_string($newUn);
            $res = $db->query("SELECT `$userIdCol` FROM `$userTableName` WHERE `$userUnCol` = '$safeUn' AND `$userIdCol` != $userId LIMIT 1");
            if ($res && $res->num_rows > 0) $errors[] = 'That username is already taken.';
        }
    }

    // Validate full name
    if (empty($newName)) $errors[] = 'Full name cannot be empty.';
    if (strlen($newName) > 80) $errors[] = 'Full name is too long (max 80 characters).';

    if ($errors) {
        echo json_encode(['success' => false, 'error' => implode(' ', $errors)]);
        exit();
    }

    // Detect full name column
    $nameCol = '';
    if ($userTableName) {
        $colsRes2 = $db->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '" . $db->real_escape_string($userTableName) . "'");
        $cols2 = [];
        if ($colsRes2) { while ($c = $colsRes2->fetch_assoc()) $cols2[] = strtolower($c['COLUMN_NAME']); }
        foreach (['full_name','fullname','name','first_name','user_name'] as $c) {
            if (in_array($c, $cols2)) { $nameCol = $c; break; }
        }
    }

    // Build and execute update
    if ($userTableName) {
        $setParts = [];
        $params   = [];
        $types    = '';

        if ($newUn !== $dbUsername) {
            $setParts[] = "`$userUnCol` = ?";
            $params[]   = $newUn;
            $types     .= 's';
        }
        if ($nameCol) {
            $setParts[] = "`$nameCol` = ?";
            $params[]   = $newName;
            $types     .= 's';
        }

        if ($setParts) {
            $sql  = "UPDATE `$userTableName` SET " . implode(', ', $setParts) . " WHERE `$userIdCol` = ?";
            $params[] = $userId;
            $types   .= 'i';
            $stmt = $db->prepare($sql);
            if ($stmt) {
                $stmt->bind_param($types, ...$params);
                $ok = $stmt->execute();
                $stmt->close();
                if (!$ok) {
                    echo json_encode(['success' => false, 'error' => 'Database error: ' . $db->error]);
                    exit();
                }
            }
        }
    }

    // Refresh session
    $_SESSION['username']  = $newUn;
    $_SESSION['user_name'] = $newName;

    echo json_encode(['success' => true, 'new_username' => $newUn, 'new_full_name' => $newName]);
    exit();
}

// ── Handle profile picture upload (AJAX → JSON) ───────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_picture'])) {
    header('Content-Type: application/json');
    $file = $_FILES['profile_picture'];
    $allowed = ['image/jpeg','image/jpg','image/png','image/gif','image/webp','image/x-png'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mime, $allowed)) {
        echo json_encode(['success'=>false,'error'=>'Only JPG, PNG, GIF, or WEBP images are allowed.']);
        exit();
    }
    if ($file['size'] > 5 * 1024 * 1024) {
        echo json_encode(['success'=>false,'error'=>'File must be under 5 MB.']);
        exit();
    }
    $dir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'avatars' . DIRECTORY_SEPARATOR;
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    if (!empty($currentAvatar)) {
        $oldFile = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $currentAvatar);
        if (file_exists($oldFile)) @unlink($oldFile);
    }

    $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $name = 'avatar_' . $userId . '_' . time() . '.' . $ext;
    $dest = $dir . $name;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        echo json_encode(['success'=>false,'error'=>'Failed to save image. Check folder permissions on uploads/avatars/']);
        exit();
    }
    $path = 'uploads/avatars/' . $name;

    $saved = false;
    if ($userTableName) {
        $stmt = $db->prepare("UPDATE `$userTableName` SET `profile_picture` = ? WHERE `$userIdCol` = ?");
        if ($stmt) {
            $stmt->bind_param('si', $path, $userId);
            $saved = $stmt->execute();
            $stmt->close();
        }
    }
    $_SESSION['profile_picture'] = $path;

    echo json_encode(['success'=>true, 'path'=>$path, 'db_saved'=>$saved]);
    exit();
}

// ── Handle profile picture removal ───────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_picture'])) {
    header('Content-Type: application/json');
    if (!empty($currentAvatar)) {
        $oldFile = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $currentAvatar);
        if (file_exists($oldFile)) @unlink($oldFile);
    }
    if ($userTableName) {
        $db->query("UPDATE `$userTableName` SET `profile_picture` = NULL WHERE `$userIdCol` = $userId");
    }
    $_SESSION['profile_picture'] = '';
    echo json_encode(['success'=>true]);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Account Settings | CineFlix</title>
  <link rel="stylesheet" href="common.css">
  <link rel="manifest" href="manifest.json">
  <link rel="apple-touch-icon" href="icon/google-icon.png">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css">
  <style>
    /* ══════════════════════════════════════════
       CINEFLIX DASHBOARD — IMDb-inspired Layout
    ══════════════════════════════════════════ */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --gold:rgb(255, 255, 255);
      --bg-deep:    #121212;
      --bg-sidebar: #1a1a1a;
      --bg-card:    #1f1f1f;
      --bg-card2:   #252525;
      --border:     rgba(255,255,255,0.08);
      --text:       #e8e8e8;
      --text-muted: rgba(255,255,255,0.45);
      --red:        #f97373;
      --green:      #4ade80;
    }

    html, body {
      height: 100%;
      background: var(--bg-deep);
      color: var(--text);
      font-family: 'Poppins', sans-serif;
      font-size: 18px;
    }

    /* ── Top Bar ── */
    .topbar {
      position: fixed; top: 0; left: 0; right: 0; z-index: 100;
      height: 64px;
      background: #0d0d0d;
      border-bottom: 1px solid rgba(109,40,217,0.15);
      display: flex; align-items: center; gap: 16px;
      padding: 0 28px;
    }
    .topbar-logo {
      display: flex; align-items: center; gap: 10px;
      text-decoration: none; flex-shrink: 0;
    }
    .topbar-logo img {
      height: 260px; width: auto; max-width: none; object-fit: contain;
    }
    .topbar-logo-text {
      font-size: 1rem; font-weight: 700;
      background: linear-gradient(135deg, var(--gold), var(--gold-dim));
      -webkit-background-clip: text; -webkit-text-fill-color: transparent;
      letter-spacing: 0.04em;
    }
    .topbar-divider {
      width: 1px; height: 24px; background: var(--border); flex-shrink: 0;
    }
    .topbar-nav {
      display: flex; align-items: center; gap: 4px; flex: 1;
    }
    .topbar-nav a {
      padding: 10px 18px; border-radius: 10px; font-size: 0.9rem;
      color: var(--text-muted); text-decoration: none; font-weight: 500;
      transition: color 0.15s, background 0.15s;
    }
    .topbar-nav a:hover { color: var(--text); background: rgba(255,255,255,0.06); }
    .topbar-user {
      display: flex; align-items: center; gap: 10px; margin-left: auto;
    }
    .topbar-avatar {
      width: 42px; height: 42px; border-radius: 50%;
      background: linear-gradient(135deg, var(--gold-dim), var(--gold-dark));
      border: 2px solid #c79f5e;
      display: flex; align-items: center; justify-content: center;
      font-size: 1rem; font-weight: 700; color: #111;
      overflow: hidden; flex-shrink: 0;
    }
    .topbar-avatar img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; }
    .topbar-username { font-size: 0.92rem; font-weight: 600; color: var(--text); }

    /* ── Dashboard Shell ── */
    .dashboard {
      display: flex;
      padding-top: 56px;
      margin-left: 280px;
      min-height: 100vh;
    }

    /* ── Sidebar ── */
    .sidebar {
      width: 350px;
      flex-shrink: 0;
      background: var(--bg-sidebar);
      border-right: 1px solid var(--border);
      position: fixed;
      top: 56px; left: 0; bottom: 0;
      overflow-y: auto;
      padding: 24px 0;
    }

    /* Sidebar profile block */
    .sidebar-profile {
      padding: 0 16px 24px;
      border-bottom: 1px solid var(--border);
      text-align: center;
    }
    .sidebar-avatar {
      width: 72px; height: 72px; border-radius: 50%;
      background: linear-gradient(135deg, #2a2b38, #1a1b24);
      border: 3px solid #c79f5e;
      display: flex; align-items: center; justify-content: center;
      font-size: 1.6rem; font-weight: 700; color: var(--gold);
      overflow: hidden; margin: 0 auto 12px;
      box-shadow: 0 0 0 5pxrgb(170, 144, 103);
      position: relative;
    }
    .sidebar-avatar img {
      position: absolute; inset: 0; width: 100%; height: 100%;
      object-fit: cover; border-radius: 50%;
    }
    .sidebar-name {
      font-size: 0.9rem; font-weight: 600; color: var(--text); margin-bottom: 2px;
    }
    .sidebar-email {
      font-size: 0.72rem; color: var(--text-muted); margin-bottom: 10px;
      word-break: break-all;
    }
    .sidebar-badge {
      display: inline-flex; align-items: center; gap: 4px;
      padding: 3px 10px; border-radius: 999px;
      background: #c79f5e; border: 1px solidrgb(241, 211, 161);
      font-size: 0.65rem; font-weight: 700; color: var(--gold);
      letter-spacing: 0.06em; text-transform: uppercase;
    }

    /* Sidebar nav */
    .sidebar-nav { padding: 16px 10px 0; }
    .sidebar-nav-label {
      font-size: 0.62rem; font-weight: 700; letter-spacing: 0.12em;
      text-transform: uppercase; color: rgba(255,255,255,0.2);
      padding: 0 8px 8px;
    }
    .sidebar-nav a {
      display: flex; align-items: center; gap: 10px;
      padding: 9px 12px; border-radius: 8px;
      font-size: 0.82rem; font-weight: 500; color: var(--text-muted);
      text-decoration: none; margin-bottom: 2px;
      transition: color 0.15s, background 0.15s;
    }
    .sidebar-nav a:hover { color: var(--text); background: rgba(255,255,255,0.05); }
    .sidebar-nav a.active {
      color: var(--gold); background:rgb(158, 151, 140);
      border-left: 2px solid var(--gold); padding-left: 10px;
    }
    .sidebar-nav a .nav-icon { font-size: 0.9rem; width: 18px; text-align: center; }

    /* ── Main Content ── */
    .main-content {
      flex: 1;
      margin-left: 220px;
      padding: 32px 48px 60px;
      max-width: 1200px;
      width: 100%;
    }

    /* ── Page Header ── */
    .page-header {
      margin-bottom: 28px;
      display: flex; align-items: flex-start; justify-content: space-between;
    }
    .page-title {
      font-size: 1.5rem; font-weight: 700; color: var(--text); margin-bottom: 4px;
    }
    .page-subtitle {
      font-size: 0.8rem; color: var(--text-muted);
    }
    .page-header-back {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 7px 16px; border-radius: 8px;
      border: 1px solid var(--border); background: rgba(255,255,255,0.03);
      color: var(--text-muted); font-size: 0.78rem; text-decoration: none;
      transition: all 0.15s;
    }
    .page-header-back:hover { color: var(--text); background: rgba(255,255,255,0.06); }

    /* ── Section Blocks ── */
    .section-block {
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: 12px;
      margin-bottom: 20px;
      overflow: hidden;
      box-shadow: 0 14px 28px rgba(0,0,0,0.25);
    }
    .section-header {
      padding: 16px 24px;
      border-bottom: 1px solid var(--border);
      display: flex; align-items: center; gap: 10px;
    }
    .section-header-icon {
      width: 30px; height: 30px; border-radius: 8px;
      background: #c79f5e;
      display: flex; align-items: center; justify-content: center;
      font-size: 0.85rem;
    }
    .section-title {
      font-size: 0.82rem; font-weight: 700; color: var(--text);
      letter-spacing: 0.02em;
    }
    .section-body { padding: 24px; }

    /* ── Profile Hero inside section ── */
    .profile-hero {
      display: flex; align-items: center; gap: 20px;
    }
    .avatar-ring { position: relative; flex-shrink: 0; }
    .avatar-circle {
      width: 80px; height: 80px; border-radius: 50%;
      background: linear-gradient(135deg, #c79f5e, #c79f5e);
      border: 3px solid #c79f5e;
      display: flex; align-items: center; justify-content: center;
      font-size: 1.8rem; font-weight: 700; color: var(--gold);
      overflow: hidden; position: relative;
      box-shadow: 0 0 0 5pxrgb(153, 142, 124);
      transition: border-color 0.2s;
    }
    .avatar-circle:hover { border-color: #c79f5e; }
    .avatar-circle img {
      position: absolute; inset: 0; width: 100%; height: 100%;
      object-fit: cover; border-radius: 50%;
    }
    .avatar-edit-badge {
      position: absolute; bottom: 1px; right: 1px;
      width: 22px; height: 22px; border-radius: 50%;
      background: var(--gold); border: 2px solid var(--bg-deep);
      display: flex; align-items: center; justify-content: center;
      font-size: 0.6rem; cursor: pointer;
      box-shadow: 0 2px 6px rgba(0,0,0,0.5);
      transition: transform 0.15s, background 0.15s;
    }
    .avatar-edit-badge:hover { transform: scale(1.15); }
    .profile-hero-info { flex: 1; }
    .hero-name { font-size: 1.1rem; font-weight: 700; color: var(--text); margin-bottom: 2px; }
    .hero-email { font-size: 0.78rem; color: var(--text-muted); margin-bottom: 12px; }
    .avatar-action-row { display: flex; gap: 8px; flex-wrap: wrap; }
    .btn-change-photo {
      display: inline-flex; align-items: center; gap: 5px;
      padding: 7px 16px; border-radius: 6px; border: none;
      background: var(--gold); color: #111;
      font-weight: 600; font-size: 0.78rem; cursor: pointer;
      transition: opacity 0.15s, transform 0.15s;
      font-family: inherit;
    }
    .btn-change-photo:hover { opacity: 0.88; transform: translateY(-1px); }
    .btn-remove-photo {
      display: inline-flex; align-items: center; gap: 5px;
      padding: 7px 14px; border-radius: 6px;
      border: 1px solid rgba(255,80,80,0.3); background: rgba(255,80,80,0.05);
      color: rgba(255,120,120,0.85); font-size: 0.78rem; cursor: pointer;
      transition: all 0.15s; font-family: inherit;
    }
    .btn-remove-photo:hover { background: rgba(255,80,80,0.1); }
    .avatar-status { font-size: 0.74rem; margin-top: 8px; }
    .avatar-status.ok  { color: var(--green); }
    .avatar-status.err { color: var(--red); }

    /* ── Info Grid ── */
    .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    @media (max-width: 580px) { .info-grid { grid-template-columns: 1fr; } }
    .info-field label {
      display: block; font-size: 0.65rem; font-weight: 700; letter-spacing: 0.1em;
      text-transform: uppercase; color: var(--text-muted); margin-bottom: 6px;
    }
    .info-field-value {
      padding: 10px 14px; border-radius: 8px;
      background: rgba(255,255,255,0.03); border: 1px solid var(--border);
      color: rgba(255,255,255,0.8); font-size: 0.88rem;
      display: flex; align-items: center; gap: 8px;
    }
    .info-field-value .field-icon { opacity: 0.4; font-size: 0.82rem; }
    .info-field.full-width { grid-column: 1 / -1; }

    /* ── Password Form ── */
    .pw-form { display: flex; flex-direction: column; gap: 16px; }
    .pw-form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    @media (max-width: 580px) { .pw-form-row { grid-template-columns: 1fr; } }
    .pw-field label {
      display: block; font-size: 0.65rem; font-weight: 700; letter-spacing: 0.1em;
      text-transform: uppercase; color: var(--text-muted); margin-bottom: 6px;
    }
    .pw-input-wrap { position: relative; }
    .pw-input-wrap input {
      width: 100%; padding: 10px 40px 10px 14px; border-radius: 8px;
      border: 1px solid rgba(255,255,255,0.1);
      background: rgba(255,255,255,0.03); color: #fff;
      font-size: 0.88rem; outline: none; font-family: inherit;
      transition: border-color 0.2s, box-shadow 0.2s;
    }
    .pw-input-wrap input:focus {
      border-color: #c79f5e;
      box-shadow: 0 0 0 3px #c79f5e;
    }
    .pw-toggle {
      position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
      background: none; border: none; cursor: pointer;
      color: rgba(255,255,255,0.25); font-size: 0.85rem; padding: 0;
      transition: color 0.15s;
    }
    .pw-toggle:hover { color: rgba(255,255,255,0.6); }
    .pw-strength-bar {
      height: 3px; border-radius: 999px; margin-top: 6px;
      background: rgba(255,255,255,0.07); overflow: hidden;
    }
    .pw-strength-fill { height: 100%; border-radius: 999px; width: 0%; transition: width 0.3s, background 0.3s; }
    .pw-strength-label { font-size: 0.65rem; color: var(--text-muted); margin-top: 3px; }
    .btn-update-pw {
      align-self: flex-start;
      padding: 10px 24px; border-radius: 8px; border: none;
      background: var(--gold); color: #111;
      font-weight: 700; font-size: 0.85rem; cursor: pointer;
      transition: opacity 0.15s, transform 0.15s;
      font-family: inherit;
    }
    .btn-update-pw:hover { opacity: 0.88; transform: translateY(-1px); }

    /* ── OTP Panel ── */
    .otp-panel {
      margin-top: 20px; padding: 20px; border-radius: 10px;
      border: 1px solid rgba(245,197,24,0.2);
      background: rgba(245,197,24,0.04); display: none;
    }
    .otp-panel h3 { margin: 0 0 6px; font-size: 0.92rem; color: var(--text); }
    .otp-panel p { margin: 0 0 16px; font-size: 0.78rem; color: var(--text-muted); }
    .otp-input-row { display: flex; gap: 8px; margin: 0 0 16px; flex-wrap: wrap; }
    .otp-input {
      width: 42px; height: 48px; border-radius: 8px;
      border: 1px solid rgba(255,255,255,0.15);
      background: rgba(0,0,0,0.35); color: #fff;
      text-align: center; font-size: 1.1rem; font-weight: 600;
      outline: none; transition: border-color 0.15s, box-shadow 0.15s;
    }
    .otp-input:focus { border-color: var(--gold); box-shadow: 0 0 0 2px rgba(109,40,217,0.15); }
    .otp-actions { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
    .otp-status { margin-top: 10px; font-size: 0.78rem; }
    .otp-status.error { color: var(--red); }
    .otp-status.success { color: var(--green); }
    .otp-secondary-link {
      border: none; background: transparent;
      color: var(--text-muted); font-size: 0.78rem;
      text-decoration: underline; cursor: pointer; padding: 0;
      transition: color 0.15s; font-family: inherit;
    }
    .otp-secondary-link:hover { color: rgba(255,255,255,0.8); }

    /* ── Alert banners ── */
    .alert-success, .alert-error {
      padding: 10px 16px; border-radius: 8px; font-size: 0.82rem;
      margin-bottom: 20px; display: flex; align-items: center; gap: 8px;
    }
    .alert-success { background: rgba(74,222,128,0.08); border: 1px solid rgba(74,222,128,0.25); color: var(--green); }
    .alert-error   { background: rgba(249,115,115,0.08); border: 1px solid rgba(249,115,115,0.25); color: var(--red); }

    /* ── Stat Row ── */
    .stats-row {
      display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px;
      margin-bottom: 20px;
    }
    .stat-card {
      background: var(--bg-card);
      border: 1px solid var(--border); border-radius: 10px;
      padding: 16px 18px;
      display: flex; align-items: center; gap: 14px;
    }
    .stat-icon {
      width: 36px; height: 36px; border-radius: 9px;
      background: #c79f5e;
      display: flex; align-items: center; justify-content: center;
      font-size: 1rem; flex-shrink: 0;
    }
    .stat-label { font-size: 0.68rem; color: var(--text-muted); margin-bottom: 2px; }
    .stat-value { font-size: 1.05rem; font-weight: 700; color: var(--text); }

    /* ── Crop Modal ── */
    .crop-modal-backdrop {
      position: fixed; inset: 0; z-index: 9000;
      background: rgba(0,0,0,0.85); backdrop-filter: blur(6px);
      display: flex; align-items: center; justify-content: center;
      opacity: 0; visibility: hidden; pointer-events: none;
      transition: opacity 0.18s, visibility 0.18s;
    }
    .crop-modal-backdrop.open { opacity: 1; visibility: visible; pointer-events: auto; }
    .crop-modal {
      background: #18181b; border: 1px solid rgba(255,255,255,0.1);
      border-radius: 16px; width: min(500px, 96vw);
      box-shadow: 0 28px 64px rgba(0,0,0,0.75);
      display: flex; flex-direction: column; overflow: hidden;
      transform: scale(0.95); transition: transform 0.18s;
    }
    .crop-modal-backdrop.open .crop-modal { transform: scale(1); }
    .crop-modal-header {
      padding: 16px 20px; border-bottom: 1px solid rgba(255,255,255,0.08);
      display: flex; align-items: center; justify-content: space-between;
    }
    .crop-modal-header h3 { margin: 0; font-size: 0.95rem; color: var(--text); }
    .crop-modal-close {
      background: none; border: none; color: rgba(255,255,255,0.4);
      font-size: 1rem; cursor: pointer; padding: 0;
    }
    .crop-modal-close:hover { color: #fff; }
    .crop-canvas-wrap { position: relative; width: 100%; height: 300px; background: #0a0a0f; }
    .crop-canvas-wrap img { display: block; max-width: 100%; }
    .crop-modal-hint {
      padding: 8px 20px; font-size: 0.7rem; color: rgba(255,255,255,0.3);
      text-align: center; border-top: 1px solid rgba(255,255,255,0.05);
    }
    .crop-modal-footer {
      padding: 14px 20px; display: flex; gap: 10px; justify-content: flex-end;
      border-top: 1px solid rgba(255,255,255,0.07);
    }
    .crop-use-btn {
      padding: 9px 22px; border-radius: 8px; border: none;
      background: var(--gold); color: #111;
      font-weight: 700; font-size: 0.85rem; cursor: pointer; transition: opacity 0.15s;
    }
    .crop-use-btn:hover { opacity: 0.85; }
    .crop-cancel-btn {
      padding: 9px 16px; border-radius: 8px;
      border: 1px solid rgba(255,255,255,0.15); background: transparent;
      color: rgba(255,255,255,0.6); font-size: 0.85rem; cursor: pointer;
      transition: background 0.15s;
    }
    .crop-cancel-btn:hover { background: rgba(255,255,255,0.05); }

    /* ── Editable account info fields ── */
    .edit-field-wrap {
      position: relative;
      display: flex;
      align-items: center;
      gap: 0;
      background: rgba(255,255,255,0.03);
      border: 1px solid rgba(255,255,255,0.12);
      border-radius: 8px;
      transition: border-color 0.2s, box-shadow 0.2s;
    }
    .edit-field-wrap:focus-within {
      border-color: #c79f5e;
      box-shadow: 0 0 0 3px rgba(199,159,94,0.18);
    }
    .edit-field-wrap.field-error {
      border-color: #f97373 !important;
      box-shadow: 0 0 0 3px rgba(249,115,115,0.15) !important;
    }
    .edit-field-wrap.field-ok {
      border-color: #4ade80 !important;
      box-shadow: 0 0 0 3px rgba(74,222,128,0.12) !important;
    }
    .edit-field-icon {
      padding: 0 10px;
      font-size: 0.82rem;
      opacity: 0.45;
      flex-shrink: 0;
      pointer-events: none;
    }
    .edit-field-input {
      flex: 1;
      padding: 10px 10px 10px 0;
      background: transparent;
      border: none;
      outline: none;
      color: rgba(255,255,255,0.88);
      font-size: 0.88rem;
      font-family: 'Poppins', sans-serif;
      min-width: 0;
    }
    .edit-field-status {
      font-size: 0.78rem;
      padding: 0 10px;
      flex-shrink: 0;
      white-space: nowrap;
    }
    .edit-field-status.ok    { color: #4ade80; }
    .edit-field-status.err   { color: #f97373; }
    .edit-field-status.check { color: rgba(255,255,255,0.4); }
    .edit-field-hint {
      font-size: 0.65rem;
      color: rgba(255,255,255,0.3);
      margin-top: 4px;
    }
    .btn-save-info {
      padding: 10px 24px; border-radius: 8px; border: none;
      background: #c79f5e; color: #111;
      font-weight: 700; font-size: 0.85rem; cursor: pointer;
      transition: opacity 0.15s, transform 0.15s;
      font-family: 'Poppins', sans-serif;
    }
    .btn-save-info:hover { opacity: 0.88; transform: translateY(-1px); }
    .btn-save-info:disabled { opacity: 0.45; cursor: not-allowed; transform: none; }
    .btn-discard-info {
      padding: 10px 18px; border-radius: 8px;
      border: 1px solid rgba(255,255,255,0.12);
      background: transparent; color: rgba(255,255,255,0.5);
      font-size: 0.85rem; cursor: pointer;
      transition: background 0.15s, color 0.15s;
      font-family: 'Poppins', sans-serif;
    }
    .btn-discard-info:hover { background: rgba(255,255,255,0.05); color: rgba(255,255,255,0.8); }
    .save-info-spinner { font-size: 0.8rem; color: rgba(255,255,255,0.4); }
    .account-alert-ok  { background: rgba(74,222,128,0.08); border:1px solid rgba(74,222,128,0.25); color:#4ade80; padding:10px 16px; border-radius:8px; font-size:0.82rem; display:flex; align-items:center; gap:8px; }
    .account-alert-err { background: rgba(249,115,115,0.08); border:1px solid rgba(249,115,115,0.25); color:#f97373; padding:10px 16px; border-radius:8px; font-size:0.82rem; display:flex; align-items:center; gap:8px; }

    /* ── Responsive ── */
    @media (max-width: 768px) {
      .sidebar { transform: translateX(-100%); }
      .main-content { margin-left: 0; padding: 24px 16px 60px; }
      .stats-row { grid-template-columns: 1fr 1fr; }
    }

    /* === Notification System === */
    .seat-notification {
      position: fixed;
      top: 20px;
      right: 20px;
      background: rgba(40, 40, 40, 0.98);
      color: #fff;
      padding: 16px 24px;
      border-radius: 12px;
      border-left: 5px solid #c79f5e;
      font-family: 'Poppins', sans-serif;
      font-size: 1rem;
      font-weight: 600;
      line-height: 1.4;
      z-index: 10000;
      opacity: 0;
      transform: translateX(100%);
      transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
      max-width: 400px;
      min-width: 300px;
      word-wrap: break-word;
      backdrop-filter: blur(10px);
    }

    .seat-notification.show {
      opacity: 1;
      transform: translateX(0);
    }

    .seat-notification.success {
      background: linear-gradient(135deg, rgba(76, 175, 80, 0.98), rgba(56, 142, 60, 0.98));
      border-left-color: #4CAF50;
      box-shadow: 0 8px 32px rgba(76, 175, 80, 0.4);
    }

    .seat-notification.info {
      background: linear-gradient(135deg, rgba(33, 150, 243, 0.98), rgba(25, 118, 210, 0.98));
      border-left-color: #2196F3;
      box-shadow: 0 8px 32px rgba(33, 150, 243, 0.4);
    }

    .seat-notification.warning {
      background: linear-gradient(135deg, rgba(255, 152, 0, 0.98), rgba(230, 81, 0, 0.98));
      border-left-color: #FF9800;
      box-shadow: 0 8px 32px rgba(255, 152, 0, 0.4);
    }

    .seat-notification.error {
      background: linear-gradient(135deg, rgba(244, 67, 54, 0.98), rgba(211, 47, 47, 0.98));
      border-left-color: #F44336;
      box-shadow: 0 8px 32px rgba(244, 67, 54, 0.4);
    }

    /* === Notification Inbox === */
    .notification-inbox {
      position: fixed;
      top: 80px;
      right: 20px;
      width: 380px;
      max-height: 500px;
      background: rgba(20, 20, 20, 0.98);
      border: 1px solid rgba(199, 159, 94, 0.3);
      border-radius: 16px;
      backdrop-filter: blur(20px);
      box-shadow: 0 16px 48px rgba(0, 0, 0, 0.6);
      z-index: 9999;
      opacity: 0;
      transform: translateX(120%) scale(0.9);
      transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
      overflow: hidden;
    }

    .notification-inbox.show {
      opacity: 1;
      transform: translateX(0) scale(1);
    }

    .notification-inbox-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 20px 24px;
      background: rgba(199, 159, 94, 0.15);
      border-bottom: 1px solid rgba(199, 159, 94, 0.3);
      color: #fff;
      font-family: 'Poppins', sans-serif;
    }

    .notification-inbox-title {
      font-size: 1.2rem;
      font-weight: 700;
      margin: 0;
    }

    .notification-inbox-close {
      background: none;
      border: none;
      color: rgba(255, 255, 255, 0.7);
      font-size: 1.5rem;
      cursor: pointer;
      padding: 4px 8px;
      border-radius: 4px;
      transition: all 0.2s ease;
    }

    .notification-inbox-close:hover {
      background: rgba(255, 255, 255, 0.1);
      color: #fff;
    }

    .notification-inbox-content {
      max-height: 380px;
      overflow-y: auto;
      padding: 12px;
    }

    .notification-inbox-empty {
      text-align: center;
      padding: 40px 20px;
      color: rgba(255, 255, 255, 0.6);
      font-family: 'Poppins', sans-serif;
      font-size: 1rem;
    }

    .notification-item {
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 12px;
      padding: 16px 20px;
      margin-bottom: 12px;
      color: #fff;
      font-family: 'Poppins', sans-serif;
      font-size: 0.95rem;
      line-height: 1.4;
      transition: all 0.2s ease;
      cursor: pointer;
      position: relative;
      padding-right: 40px;
    }

    .notification-item:hover {
      background: rgba(255, 255, 255, 0.08);
      transform: translateY(-1px);
    }

    .notification-item.success {
      border-left: 4px solid #4CAF50;
    }

    .notification-item.info {
      border-left: 4px solid #2196F3;
    }

    .notification-item.warning {
      border-left: 4px solid #FF9800;
    }

    .notification-item.error {
      border-left: 4px solid #F44336;
    }

    .notification-item-message {
      margin-bottom: 8px;
      padding-right: 20px;
    }

    .notification-item-time {
      font-size: 0.8rem;
      color: rgba(255, 255, 255, 0.5);
      position: absolute;
      bottom: 12px;
      right: 16px;
    }

    .notification-item-close {
      position: absolute;
      top: 8px;
      right: 8px;
      background: none;
      border: none;
      color: rgba(255, 255, 255, 0.4);
      font-size: 1.2rem;
      cursor: pointer;
      padding: 4px;
      border-radius: 4px;
      transition: all 0.2s ease;
      width: 24px;
      height: 24px;
      display: flex;
      align-items: center;
      justify-content: center;
      opacity: 0;
      transform: scale(0.8);
    }

    .notification-item:hover .notification-item-close {
      opacity: 1;
      transform: scale(1);
    }

    .notification-item-close:hover {
      background: rgba(255, 255, 255, 0.1);
      color: rgba(255, 255, 255, 0.8);
    }

    .notification-inbox-footer {
      padding: 16px 24px;
      background: rgba(199, 159, 94, 0.1);
      border-top: 1px solid rgba(199, 159, 94, 0.3);
      text-align: center;
    }

    .notification-clear-btn {
      background: linear-gradient(135deg, #c79f5e, #a67c42);
      color: #fff;
      border: none;
      padding: 10px 24px;
      border-radius: 8px;
      font-family: 'Poppins', sans-serif;
      font-size: 0.9rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
    }

    .notification-clear-btn:hover {
      background: linear-gradient(135deg, #d4ae6f, #b88b52);
      transform: translateY(-1px);
      box-shadow: 0 4px 16px rgba(199, 159, 94, 0.4);
    }

    .notification-clear-btn:disabled {
      opacity: 0.5;
      cursor: not-allowed;
      transform: none;
    }
  </style>
</head>
<body>

  <!-- ── Top Bar ── -->
  <header class="topbar">
    <a class="topbar-logo" href="homepage.php">
      <img src="logo/newlogo1.png" alt="CineFlix">
      <span class="topbar-logo-text">CineFlix</span>
    </a>
    <div class="topbar-divider"></div>
    </nav>
    <div class="topbar-user">
      <div class="topbar-avatar" id="topbarAvatar">
        <?php if (!empty($currentAvatar)): ?>
          <img src="<?php echo htmlspecialchars($currentAvatar); ?>" alt="">
        <?php else: ?>
          <?php echo strtoupper(substr($dbUsername, 0, 1)); ?>
        <?php endif; ?>
      </div>
      <span class="topbar-username"><?php echo htmlspecialchars($dbUsername); ?></span>
    </div>
  </header>

  <!-- ── Dashboard Shell ── -->
  <div class="dashboard">

    <!-- ── Sidebar ── -->
    <aside class="sidebar">
      <div class="sidebar-profile">
        <div class="sidebar-avatar" id="sidebarAvatar">
          <?php if (!empty($currentAvatar)): ?>
            <img src="<?php echo htmlspecialchars($currentAvatar); ?>" alt="">
          <?php else: ?>
            <?php echo strtoupper(substr($dbUsername, 0, 1)); ?>
          <?php endif; ?>
        </div>
        <div class="sidebar-name"><?php echo htmlspecialchars($dbUsername); ?></div>
        <div class="sidebar-email"><?php echo htmlspecialchars($userEmail); ?></div>
        <div class="sidebar-badge">🎬 CineFlix Member</div>
      </div>

      <nav class="sidebar-nav">
        <div class="sidebar-nav-label">Menu</div>
        <a href="user-profile.php"><span class="nav-icon">👤</span> My Profile</a>
        <a href="#" class="active"><span class="nav-icon">⚙️</span> Settings</a>
        <a href="status.php"><span class="nav-icon">📊</span> Status</a>
      </nav>
    </aside>

    <!-- ── Main Content ── -->
    <main class="main-content">

      <!-- Page Header -->
      <div class="page-header">
        <div>
          <div class="page-title">PROFILE</div>
          <div class="page-subtitle">Update your CineFlix profile and security</div>
        </div>
        <a href="homepage.php" class="page-header-back">← Back to Home</a>
      </div>

      <?php if (!empty($successMessage)): ?>
        <div class="alert-success">✓ <?php echo htmlspecialchars($successMessage); ?></div>
      <?php endif; ?>
      <?php if (!empty($errorMessage)): ?>
        <div class="alert-error">✕ <?php echo htmlspecialchars($errorMessage); ?></div>
      <?php endif; ?>

      <!-- Stats Row -->
      <div class="stats-row">
        <div class="stat-card">
          <div class="stat-icon">🎬</div>
          <div>
            <div class="stat-label">Member Since</div>
            <div class="stat-value">CineFlix</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">👤</div>
          <div>
            <div class="stat-label">Username</div>
            <div class="stat-value"><?php echo htmlspecialchars($dbUsername); ?></div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">🔒</div>
          <div>
            <div class="stat-label">Account</div>
            <div class="stat-value">Secure</div>
          </div>
        </div>
      </div>

      <!-- Profile Section -->
      <div class="section-block">
        <div class="section-header">
          <div class="section-header-icon">📷</div>
          <div class="section-title">Profile Picture</div>
        </div>
        <div class="section-body">
          <div class="profile-hero">
            <div class="avatar-ring">
              <div class="avatar-circle" id="avatarPreviewLarge">
                <?php if (!empty($currentAvatar)): ?>
                  <img src="<?php echo htmlspecialchars($currentAvatar); ?>" alt="Profile picture">
                <?php else: ?>
                  <span><?php echo strtoupper(substr($dbUsername, 0, 1)); ?></span>
                <?php endif; ?>
              </div>
              <label class="avatar-edit-badge" for="avatarFileInput" title="Change photo">📷
                <input type="file" id="avatarFileInput" accept="image/*" style="display:none;">
              </label>
            </div>
            <div class="profile-hero-info">
              <div class="hero-name"><?php echo htmlspecialchars($dbUsername); ?></div>
              <div class="hero-email"><?php echo htmlspecialchars($userEmail); ?></div>
              <div class="avatar-action-row">
                <label class="btn-change-photo" for="avatarFileInput">📷 Change Photo</label>
                <?php if (!empty($currentAvatar)): ?>
                <button type="button" class="btn-remove-photo" id="avatarRemoveBtn">🗑 Remove</button>
                <?php endif; ?>
              </div>
              <div class="avatar-status" id="avatarStatus" style="display:none;"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Account Info Section -->
      <div class="section-block">
        <div class="section-header">
          <div class="section-header-icon">🪪</div>
          <div class="section-title">Account Information</div>
        </div>
        <div class="section-body">
          <div id="account-info-alert" style="display:none;margin-bottom:14px;"></div>
          <form id="account-info-form" autocomplete="off">
            <div class="info-grid" style="margin-bottom:18px;">

              <!-- Editable Username -->
              <div class="info-field">
                <label for="edit-username">Username</label>
                <div class="edit-field-wrap">
                  <span class="edit-field-icon">👤</span>
                  <input
                    type="text"
                    id="edit-username"
                    name="new_username"
                    class="edit-field-input"
                    value="<?php echo htmlspecialchars($dbUsername); ?>"
                    maxlength="30"
                    autocomplete="off"
                    spellcheck="false"
                  >
                  <span class="edit-field-status" id="username-status" aria-live="polite"></span>
                </div>
                <div class="edit-field-hint" id="username-hint">3–30 characters, letters/numbers/underscores only.</div>
              </div>

              <!-- Editable Full Name -->
              <div class="info-field">
                <label for="edit-fullname">Full Name</label>
                <div class="edit-field-wrap">
                  <span class="edit-field-icon">🪪</span>
                  <input
                    type="text"
                    id="edit-fullname"
                    name="new_full_name"
                    class="edit-field-input"
                    value="<?php echo htmlspecialchars($userName); ?>"
                    maxlength="80"
                    autocomplete="off"
                  >
                </div>
              </div>

              <!-- Read-only Email -->
              <div class="info-field full-width">
                <label>Email Address</label>
                <div class="info-field-value"><span class="field-icon">✉️</span><?php echo htmlspecialchars($userEmail); ?></div>
              </div>
            </div>

            <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
              <button type="submit" class="btn-save-info" id="btn-save-account">
                <span id="btn-save-label">💾 Save Changes</span>
              </button>
              <button type="button" class="btn-discard-info" id="btn-discard-account">Discard</button>
              <span class="save-info-spinner" id="save-spinner" style="display:none;">Saving…</span>
            </div>
          </form>
        </div>
      </div>

      <!-- Security Section -->
      <div class="section-block">
        <div class="section-header">
          <div class="section-header-icon">🔒</div>
          <div class="section-title">Security — Change Password</div>
        </div>
        <div class="section-body">
          <form id="password-change-form" class="pw-form">
            <div class="pw-field">
              <label for="current_password">Current Password</label>
              <div class="pw-input-wrap">
                <input type="password" id="current_password" name="current_password" required placeholder="Enter current password">
                <button type="button" class="pw-toggle" data-target="current_password">👁</button>
              </div>
            </div>
            <div class="pw-form-row">
              <div class="pw-field">
                <label for="new_password">New Password</label>
                <div class="pw-input-wrap">
                  <input type="password" id="new_password" name="new_password" required minlength="8" placeholder="Min. 8 characters">
                  <button type="button" class="pw-toggle" data-target="new_password">👁</button>
                </div>
                <div class="pw-strength-bar"><div class="pw-strength-fill" id="pwStrengthFill"></div></div>
                <div class="pw-strength-label" id="pwStrengthLabel"></div>
              </div>
              <div class="pw-field">
                <label for="confirm_password">Confirm New Password</label>
                <div class="pw-input-wrap">
                  <input type="password" id="confirm_password" name="confirm_password" required minlength="8" placeholder="Repeat new password">
                  <button type="button" class="pw-toggle" data-target="confirm_password">👁</button>
                </div>
              </div>
            </div>
            <button type="submit" class="btn-update-pw"> Update Password</button>
          </form>

          <div id="password-otp-panel" class="otp-panel" aria-hidden="true">
            <h3>🔑 Verify with 6-Digit Code</h3>
            <p>A verification code was sent to your email. Enter it below to confirm.</p>
            <div class="otp-input-row">
              <input type="text" class="otp-input" maxlength="1" inputmode="numeric" pattern="[0-9]" autocomplete="one-time-code">
              <input type="text" class="otp-input" maxlength="1" inputmode="numeric" pattern="[0-9]">
              <input type="text" class="otp-input" maxlength="1" inputmode="numeric" pattern="[0-9]">
              <input type="text" class="otp-input" maxlength="1" inputmode="numeric" pattern="[0-9]">
              <input type="text" class="otp-input" maxlength="1" inputmode="numeric" pattern="[0-9]">
              <input type="text" class="otp-input" maxlength="1" inputmode="numeric" pattern="[0-9]">
            </div>
            <div class="otp-actions">
              <button type="button" id="password-otp-confirm" class="btn-update-pw" style="padding:8px 18px;font-size:0.82rem;">Confirm Code</button>
              <button type="button" id="password-otp-cancel" class="otp-secondary-link">Cancel</button>
            </div>
            <div id="password-otp-status" class="otp-status" style="display:none;"></div>
          </div>
        </div>
      </div>

    </main>
  </div><!-- /.dashboard -->

  <!-- Crop Modal -->
  <div class="crop-modal-backdrop" id="cropModalBackdrop" role="dialog" aria-modal="true" aria-label="Crop your photo">
    <div class="crop-modal">
      <div class="crop-modal-header">
        <h3>✂️ Crop Your Photo</h3>
        <button class="crop-modal-close" id="cropModalClose">✕</button>
      </div>
      <div class="crop-canvas-wrap">
        <img id="cropImage" src="" alt="Crop preview">
      </div>
      <p class="crop-modal-hint">Drag to reposition · Scroll to zoom · Circle = saved area</p>
      <div class="crop-modal-footer">
        <button class="crop-cancel-btn" id="cropCancelBtn">Cancel</button>
        <button class="crop-use-btn" id="cropUseBtn">Use Photo</button>
      </div>
    </div>
  </div>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>
  <script>
    // ── Password show/hide toggles ────────────────────────────────────
    document.querySelectorAll('.pw-toggle').forEach(btn => {
      btn.addEventListener('click', function() {
        const targetId = this.getAttribute('data-target');
        const input = document.getElementById(targetId);
        if (!input) return;
        const isPass = input.type === 'password';
        input.type = isPass ? 'text' : 'password';
        this.textContent = isPass ? '🙈' : '👁';
      });
    });

    // ── Password strength meter ───────────────────────────────────────
    const newPwInput = document.getElementById('new_password');
    const strengthFill = document.getElementById('pwStrengthFill');
    const strengthLabel = document.getElementById('pwStrengthLabel');
    if (newPwInput && strengthFill) {
      newPwInput.addEventListener('input', function() {
        const v = this.value;
        let score = 0;
        if (v.length >= 8)  score++;
        if (v.length >= 12) score++;
        if (/[A-Z]/.test(v)) score++;
        if (/[0-9]/.test(v)) score++;
        if (/[^A-Za-z0-9]/.test(v)) score++;
        const levels = [
          { pct: '0%',   color: 'transparent', label: '' },
          { pct: '25%',  color: '#f87171',      label: '🔴 Weak' },
          { pct: '50%',  color: '#fbbf24',      label: '🟡 Fair' },
          { pct: '75%',  color: '#60a5fa',      label: '🔵 Good' },
          { pct: '100%', color: '#4ade80',      label: '🟢 Strong' },
          { pct: '100%', color: '#4ade80',      label: '🟢 Strong' },
        ];
        const l = levels[score] || levels[0];
        strengthFill.style.width = l.pct;
        strengthFill.style.background = l.color;
        if (strengthLabel) strengthLabel.textContent = v.length ? l.label : '';
      });
    }

    // ── Password OTP Flow ─────────────────────────────────────────────
    (function () {
      const form = document.getElementById('password-change-form');
      const otpPanel = document.getElementById('password-otp-panel');
      const otpInputs = otpPanel ? otpPanel.querySelectorAll('.otp-input') : [];
      const otpConfirmBtn = document.getElementById('password-otp-confirm');
      const otpCancelBtn = document.getElementById('password-otp-cancel');
      const otpStatus = document.getElementById('password-otp-status');

      if (!form) return;

      function showOtpPanel() {
        if (!otpPanel) return;
        otpPanel.style.display = 'block';
        otpPanel.setAttribute('aria-hidden', 'false');
        if (otpInputs && otpInputs[0]) {
          otpInputs.forEach(input => { input.value = ''; });
          otpInputs[0].focus();
        }
        if (otpStatus) {
          otpStatus.style.display = 'none';
          otpStatus.textContent = '';
          otpStatus.classList.remove('error', 'success');
        }
      }

      function hideOtpPanel() {
        if (!otpPanel) return;
        otpPanel.style.display = 'none';
        otpPanel.setAttribute('aria-hidden', 'true');
      }

      function getOtpValue() {
        if (!otpInputs || !otpInputs.length) return '';
        return Array.from(otpInputs).map(i => i.value.replace(/[^0-9]/g, '')).join('');
      }

      function attachOtpBehaviors() {
        if (!otpInputs || !otpInputs.length) return;
        otpInputs.forEach((input, idx) => {
          input.addEventListener('input', e => {
            const val = e.target.value.replace(/[^0-9]/g, '');
            e.target.value = val;
            if (val && idx < otpInputs.length - 1) otpInputs[idx + 1].focus();
          });
          input.addEventListener('keydown', e => {
            if (e.key === 'Backspace' && !e.target.value && idx > 0) otpInputs[idx - 1].focus();
          });
          input.addEventListener('paste', e => {
            e.preventDefault();
            const paste = (e.clipboardData || window.clipboardData).getData('text') || '';
            const digits = paste.replace(/[^0-9]/g, '').slice(0, otpInputs.length);
            digits.split('').forEach((d, i) => { if (otpInputs[i]) otpInputs[i].value = d; });
            if (digits.length && otpInputs[Math.min(digits.length - 1, otpInputs.length - 1)]) {
              otpInputs[Math.min(digits.length - 1, otpInputs.length - 1)].focus();
            }
          });
        });
      }

      attachOtpBehaviors();

      form.addEventListener('submit', async function (e) {
        e.preventDefault();
        const current = document.getElementById('current_password')?.value || '';
        const next = document.getElementById('new_password')?.value || '';
        const confirm = document.getElementById('confirm_password')?.value || '';

        if (!current || !next || !confirm) { alert('Please fill in all password fields.'); return; }

        try {
          const res = await fetch('api/request_password_change.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ current_password: current, new_password: next, confirm_password: confirm })
          });
          const data = await res.json();
          if (!res.ok || !data.success) { alert(data.error || 'Unable to start password change.'); return; }
          showOtpPanel();
        } catch (err) {
          console.error(err);
          alert('We could not update your password right now. Please try again.');
        }
      });

      if (otpConfirmBtn) {
        otpConfirmBtn.addEventListener('click', async function () {
          const code = getOtpValue();
          if (!code || code.length !== 6) {
            if (otpStatus) {
              otpStatus.textContent = 'Please enter the complete 6‑digit code.';
              otpStatus.classList.add('error'); otpStatus.style.display = 'block';
            } else alert('Please enter the complete 6‑digit code.');
            return;
          }
          try {
            const res2 = await fetch('api/confirm_password_change.php', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({ code: code.trim() })
            });
            const data2 = await res2.json();
            if (!res2.ok || !data2.success) {
              if (otpStatus) {
                otpStatus.textContent = data2.error || 'Verification failed. Please try again.';
                otpStatus.classList.add('error'); otpStatus.style.display = 'block';
              } else alert(data2.error || 'Verification failed.');
              return;
            }
            if (otpStatus) {
              otpStatus.textContent = 'Your password has been updated.';
              otpStatus.classList.add('success'); otpStatus.style.display = 'block';
            }
            setTimeout(() => { window.location.href = 'account-settings.php'; }, 1200);
          } catch (err) {
            console.error(err);
            if (otpStatus) {
              otpStatus.textContent = 'We could not verify the code right now. Please try again.';
              otpStatus.classList.add('error'); otpStatus.style.display = 'block';
            }
          }
        });
      }

      if (otpCancelBtn) otpCancelBtn.addEventListener('click', hideOtpPanel);
    })();

    // ── Account Info (Username + Full Name) Edit ─────────────────────
    (function () {
      const form        = document.getElementById('account-info-form');
      const unInput     = document.getElementById('edit-username');
      const nameInput   = document.getElementById('edit-fullname');
      const unStatus    = document.getElementById('username-status');
      const unHint      = document.getElementById('username-hint');
      const alertBox    = document.getElementById('account-info-alert');
      const saveBtn     = document.getElementById('btn-save-account');
      const discardBtn  = document.getElementById('btn-discard-account');
      const spinner     = document.getElementById('save-spinner');
      const unWrap      = unInput ? unInput.closest('.edit-field-wrap') : null;

      // Store originals for discard
      const origUsername = unInput  ? unInput.value  : '';
      const origFullName = nameInput ? nameInput.value : '';

      let checkTimer   = null;
      let usernameOk   = true;   // starts as true (unchanged = valid)
      let isChecking   = false;

      function showAlert(msg, isOk) {
        if (!alertBox) return;
        alertBox.className = isOk ? 'account-alert-ok' : 'account-alert-err';
        alertBox.textContent = (isOk ? '✓ ' : '✕ ') + msg;
        alertBox.style.display = 'flex';
        if (isOk) setTimeout(() => { alertBox.style.display = 'none'; }, 4000);
      }

      function setUsernameState(state, msg) {
        // state: 'ok' | 'err' | 'check' | ''
        if (!unStatus || !unWrap) return;
        unStatus.className = 'edit-field-status ' + state;
        unStatus.textContent = msg || '';
        unWrap.classList.remove('field-ok', 'field-error');
        if (state === 'ok')  unWrap.classList.add('field-ok');
        if (state === 'err') unWrap.classList.add('field-error');
        usernameOk = (state === 'ok' || state === '');
      }

      async function checkUsernameAvailability(val) {
        if (val === origUsername) { setUsernameState('', ''); return; }
        if (val.length < 3) { setUsernameState('err', '✕ Too short'); return; }
        if (!/^[a-zA-Z0-9_]+$/.test(val)) { setUsernameState('err', '✕ Invalid characters'); return; }

        isChecking = true;
        setUsernameState('check', '⏳ Checking…');
        try {
          const fd = new FormData();
          fd.append('check_username', val);
          const res  = await fetch('account-settings.php', { method: 'POST', body: fd });
          const data = await res.json();
          if (data.available) {
            setUsernameState('ok', data.same ? '' : '✓ Available');
          } else {
            setUsernameState('err', '✕ ' + (data.error || 'Taken'));
          }
        } catch (e) {
          setUsernameState('', '');
        }
        isChecking = false;
      }

      if (unInput) {
        unInput.addEventListener('input', function () {
          clearTimeout(checkTimer);
          const val = this.value.trim();
          checkTimer = setTimeout(() => checkUsernameAvailability(val), 500);
        });
      }

      if (discardBtn) {
        discardBtn.addEventListener('click', function () {
          if (unInput)   unInput.value   = origUsername;
          if (nameInput) nameInput.value = origFullName;
          setUsernameState('', '');
          if (alertBox) alertBox.style.display = 'none';
        });
      }

      if (form) {
        form.addEventListener('submit', async function (e) {
          e.preventDefault();
          if (isChecking) { showAlert('Please wait for the username check to complete.', false); return; }
          if (!usernameOk) { showAlert('Please fix the username before saving.', false); return; }

          const newUn   = unInput   ? unInput.value.trim()   : origUsername;
          const newName = nameInput ? nameInput.value.trim()  : origFullName;

          if (newUn === origUsername && newName === origFullName) {
            showAlert('No changes to save.', false); return;
          }

          saveBtn.disabled = true;
          spinner.style.display = 'inline';

          try {
            const fd = new FormData();
            fd.append('update_account_info', '1');
            fd.append('new_username',  newUn);
            fd.append('new_full_name', newName);
            const res  = await fetch('account-settings.php', { method: 'POST', body: fd });
            const data = await res.json();

            if (data.success) {
              showAlert('Changes saved successfully!', true);

              const un = data.new_username;
              const fn = data.new_full_name;

              // ── Send notifications for the changes ──────────────
              if (typeof addUserAccountNotification === 'function') {
                // Check what actually changed and send appropriate notifications
                if (un !== origUsername) {
                  addUserAccountNotification('username_changed', {newUsername: un});
                }
                if (fn !== origFullName) {
                  addUserAccountNotification('profile_updated');
                }
              }

              // ── Reflect changes everywhere on this page ──────────────

              // Topbar username text
              const topbarUn = document.querySelector('.topbar-username');
              if (topbarUn) topbarUn.textContent = un;

              // Topbar avatar initial (only if no photo)
              const topbarAv = document.getElementById('topbarAvatar');
              if (topbarAv && !topbarAv.querySelector('img')) topbarAv.textContent = un.charAt(0).toUpperCase();

              // Sidebar name
              const sidebarName = document.querySelector('.sidebar-name');
              if (sidebarName) sidebarName.textContent = un;

              // Sidebar avatar initial (only if no photo)
              const sidebarAv = document.getElementById('sidebarAvatar');
              if (sidebarAv && !sidebarAv.querySelector('img')) sidebarAv.textContent = un.charAt(0).toUpperCase();

              // Hero name in profile picture section
              const heroName = document.querySelector('.hero-name');
              if (heroName) heroName.textContent = un;

              // Avatar circle initial
              const avCircle = document.getElementById('avatarPreviewLarge');
              if (avCircle && !avCircle.querySelector('img')) {
                const sp = avCircle.querySelector('span');
                if (sp) sp.textContent = un.charAt(0).toUpperCase();
              }

              // Stats row – username stat card
              const statCards = document.querySelectorAll('.stat-card');
              statCards.forEach(card => {
                const lbl = card.querySelector('.stat-label');
                if (lbl && lbl.textContent.trim() === 'Username') {
                  const val = card.querySelector('.stat-value');
                  if (val) val.textContent = un;
                }
              });

              // Update original values so discard resets correctly
              // (reassign via closure-held vars isn't possible for const, so we track via data attributes)
              unInput.dataset.original  = un;
              if (nameInput) nameInput.dataset.original = fn;

              setUsernameState('', '');

            } else {
              showAlert(data.error || 'Failed to save changes.', false);
              if (data.error && data.error.toLowerCase().includes('username')) {
                setUsernameState('err', '✕ Taken');
              }
            }
          } catch (err) {
            showAlert('Network error — please try again.', false);
          }

          saveBtn.disabled = false;
          spinner.style.display = 'none';
        });
      }
    })();

    // ── Profile Picture Upload + Crop ────────────────────────────────
    (function () {
      var fileInput   = document.getElementById('avatarFileInput');
      var removeBtn   = document.getElementById('avatarRemoveBtn');
      var previewWrap = document.getElementById('avatarPreviewLarge');
      var statusEl    = document.getElementById('avatarStatus');
      var backdrop    = document.getElementById('cropModalBackdrop');
      var cropImg     = document.getElementById('cropImage');
      var cropUseBtn  = document.getElementById('cropUseBtn');
      var cropCancel  = document.getElementById('cropCancelBtn');
      var cropClose   = document.getElementById('cropModalClose');
      var cropper     = null;

      function setStatus(msg, isOk) {
        if (!statusEl) return;
        statusEl.textContent = msg;
        statusEl.className   = 'avatar-status ' + (isOk ? 'ok' : 'err');
        statusEl.style.display = 'block';
      }

      function setPreview(src) {
        // Update main avatar preview
        if (previewWrap) {
          if (src) {
            previewWrap.innerHTML = '<img src="' + src + '" alt="Profile picture" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;object-position:center;display:block;border-radius:50%;">';
          } else {
            previewWrap.innerHTML = '<span><?php echo strtoupper(substr($dbUsername, 0, 1)); ?></span>';
          }
        }
        // Update sidebar avatar
        var sa = document.getElementById('sidebarAvatar');
        if (sa) {
          if (src) {
            sa.innerHTML = '<img src="' + src + '" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;border-radius:50%;">';
          } else {
            sa.innerHTML = '<?php echo strtoupper(substr($dbUsername, 0, 1)); ?>';
          }
        }
        // Update topbar avatar
        var ta = document.getElementById('topbarAvatar');
        if (ta) {
          if (src) {
            ta.innerHTML = '<img src="' + src + '" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">';
          } else {
            ta.innerHTML = '<?php echo strtoupper(substr($dbUsername, 0, 1)); ?>';
          }
        }
      }

      function openCropModal(imageSrc) {
        backdrop.classList.add('open');
        document.body.style.overflow = 'hidden';
        if (cropper) { cropper.destroy(); cropper = null; }
        cropImg.src = '';

        function initCropper() {
          if (cropper) { cropper.destroy(); cropper = null; }
          cropper = new Cropper(cropImg, {
            aspectRatio: 1, viewMode: 1, dragMode: 'move',
            autoCropArea: 0.85, restore: false, guides: true, center: true,
            highlight: false, cropBoxMovable: true, cropBoxResizable: true,
            toggleDragModeOnDblclick: false,
            ready: function() { if (cropUseBtn) cropUseBtn.disabled = false; }
          });
        }

        cropImg.onload = function() {
          requestAnimationFrame(function() { requestAnimationFrame(function() { initCropper(); }); });
        };
        cropImg.src = imageSrc;
        if (cropUseBtn) cropUseBtn.disabled = true;
      }

      function closeCropModal() {
        backdrop.classList.remove('open');
        document.body.style.overflow = '';
        if (cropper) { cropper.destroy(); cropper = null; }
        setTimeout(function() { cropImg.src = ''; }, 200);
        if (fileInput) fileInput.value = '';
        if (cropUseBtn) cropUseBtn.disabled = false;
      }

      async function uploadBlob(blob, filename, mime) {
        filename = filename || 'avatar.jpg';
        setStatus('Uploading…', true);
        var fd = new FormData();
        fd.append('profile_picture', blob, filename);
        try {
          var res  = await fetch('account-settings.php', { method:'POST', body:fd });
          var data = await res.json();
          if (data.success) {
            setStatus('✓ Profile picture updated!', true);
            
            // Send notification for profile picture update
            if (typeof addUserAccountNotification === 'function') {
              addUserAccountNotification('profile_picture_updated');
            }
            
            setPreview(data.path + '?t=' + Date.now());
            if (!document.getElementById('avatarRemoveBtn')) {
              var row = document.querySelector('.avatar-action-row');
              var btn = document.createElement('button');
              btn.type = 'button'; btn.id = 'avatarRemoveBtn';
              btn.className = 'btn-remove-photo';
              btn.textContent = '🗑 Remove';
              row.appendChild(btn);
              btn.addEventListener('click', doRemove);
            }
          } else {
            setStatus('✗ ' + (data.error || 'Upload failed.'), false);
          }
        } catch(err) {
          setStatus('✗ Network error. Please try again.', false);
        }
      }

      if (cropUseBtn) {
        cropUseBtn.addEventListener('click', function () {
          if (!cropper) { setStatus('✗ Cropper not ready. Please try again.', false); return; }
          var canvas = null;
          try {
            canvas = cropper.getCroppedCanvas({ width:400, height:400, fillColor:'#fff', imageSmoothingEnabled:true, imageSmoothingQuality:'high' });
          } catch(e) { console.error('getCroppedCanvas error:', e); }

          if (!canvas || canvas.width === 0) {
            setStatus('✗ Could not crop image. Please try selecting the file again.', false);
            closeCropModal(); return;
          }

          var previewUrl = canvas.toDataURL('image/png');
          setPreview(previewUrl);
          closeCropModal();

          canvas.toBlob(function (blob) {
            if (blob) uploadBlob(blob, 'avatar.png', 'image/png');
            else canvas.toBlob(function(b2) {
              if (b2) uploadBlob(b2, 'avatar.jpg', 'image/jpeg');
              else setStatus('✗ Could not process image.', false);
            }, 'image/jpeg', 0.92);
          }, 'image/png');
        });
      }

      [cropCancel, cropClose].forEach(function(btn) { if (btn) btn.addEventListener('click', closeCropModal); });
      if (backdrop) {
        backdrop.addEventListener('click', function(e) { if (e.target === backdrop) closeCropModal(); });
      }
      document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && backdrop && backdrop.classList.contains('open')) closeCropModal();
      });

      if (fileInput) {
        fileInput.addEventListener('change', function () {
          var file = this.files[0];
          if (!file) return;
          var allowed = ['image/jpeg','image/png','image/gif','image/webp'];
          if (!allowed.includes(file.type)) { setStatus('✗ Only JPG, PNG, GIF or WEBP images allowed.', false); this.value = ''; return; }
          if (file.size > 5 * 1024 * 1024) { setStatus('✗ Image must be under 5 MB.', false); this.value = ''; return; }
          var reader = new FileReader();
          reader.onload = function(e) { openCropModal(e.target.result); };
          reader.readAsDataURL(file);
        });
      }

      async function doRemove() {
        setStatus('Removing…', true);
        var fd = new FormData();
        fd.append('remove_picture', '1');
        try {
          var res  = await fetch('account-settings.php', { method:'POST', body:fd });
          var data = await res.json();
          if (data.success) {
            setPreview('');
            setStatus('✓ Profile picture removed.', true);
            var rb = document.getElementById('avatarRemoveBtn');
            if (rb) rb.remove();
          } else setStatus('✗ Could not remove picture.', false);
        } catch(err) { setStatus('✗ Network error.', false); }
      }

      if (removeBtn) removeBtn.addEventListener('click', doRemove);
    })();
  </script>
  <link rel="stylesheet" href="chatbot.css">
  <script src="chatbot.js"></script>
  
  <!-- Notification Inbox System for Account Settings -->
  <script>
  // Notification Inbox System
  class NotificationInbox {
    constructor() {
      this.notifications = [];
      this.maxNotifications = 50;
      this.inboxElement = null;
      this.isOpen = false;
      this.init();
    }

    init() {
      this.loadNotifications();
      this.createInboxUI();
      this.addNotificationBell();
    }

    loadNotifications() {
      try {
        const stored = localStorage.getItem('cineflix_notifications');
        if (stored) {
          this.notifications = JSON.parse(stored);
          if (this.notifications.length > this.maxNotifications) {
            this.notifications = this.notifications.slice(-this.maxNotifications);
          }
        }
      } catch (e) {
        console.error('Failed to load notifications:', e);
        this.notifications = [];
      }
    }

    saveNotifications() {
      try {
        localStorage.setItem('cineflix_notifications', JSON.stringify(this.notifications));
      } catch (e) {
        console.error('Failed to save notifications:', e);
      }
    }

    addNotification(message, type = 'info') {
      const notification = {
        id: Date.now() + Math.random(),
        message,
        type,
        timestamp: new Date().toISOString(),
        read: false
      };

      this.notifications.unshift(notification);
      
      if (this.notifications.length > this.maxNotifications) {
        this.notifications = this.notifications.slice(0, this.maxNotifications);
      }

      this.saveNotifications();
      this.updateInboxContent();
      this.updateNotificationBell();
      this.showToastNotification(message, type);
    }

    showToastNotification(message, type = 'info') {
      const notification = document.createElement('div');
      notification.className = `seat-notification ${type}`;
      notification.textContent = message;
      
      document.body.appendChild(notification);
      
      requestAnimationFrame(() => {
        notification.classList.add('show');
      });
      
      setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
      }, 3000);
    }

    createInboxUI() {
      this.inboxElement = document.createElement('div');
      this.inboxElement.className = 'notification-inbox';
      this.inboxElement.innerHTML = `
        <div class="notification-inbox-header">
          <h3 class="notification-inbox-title">Notifications</h3>
          <button class="notification-inbox-close">&times;</button>
        </div>
        <div class="notification-inbox-content">
          <div class="notification-inbox-empty">No notifications yet</div>
        </div>
        <div class="notification-inbox-footer">
          <button class="notification-clear-btn" disabled>Clear All</button>
        </div>
      `;

      document.body.appendChild(this.inboxElement);

      const closeBtn = this.inboxElement.querySelector('.notification-inbox-close');
      const clearBtn = this.inboxElement.querySelector('.notification-clear-btn');

      closeBtn.addEventListener('click', () => this.closeInbox());
      clearBtn.addEventListener('click', () => this.clearAllNotifications());

      document.addEventListener('click', (e) => {
        if (this.isOpen && !this.inboxElement.contains(e.target) && !e.target.closest('.notification-bell')) {
          this.closeInbox();
        }
      });

      this.updateInboxContent();
    }

    addNotificationBell() {
      // Find a suitable location for the bell - try topbar first
      const topbar = document.querySelector('.topbar');
      if (!topbar) return;

      const bellContainer = document.createElement('div');
      bellContainer.style.cssText = `
        position: absolute;
        top: 50%;
        right: 20px;
        transform: translateY(-50%);
        cursor: pointer;
        padding: 8px 12px;
        background: rgba(255,255,255,0.1);
        color: #fff;
        border-radius: 20px;
        font-size: 0.9rem;
        font-weight: 600;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
      `;

      bellContainer.innerHTML = `
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
          <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
        </svg>
        <span class="notification-badge" style="display: none; background: #ff4444; color: white; border-radius: 50%; width: 16px; height: 16px; font-size: 0.7rem; font-weight: bold; display: flex; align-items: center; justify-content: center; border: 2px solid rgba(0,0,0,0.3);">0</span>
      `;

      bellContainer.addEventListener('mouseenter', () => {
        bellContainer.style.background = 'rgba(199, 159, 94, 0.3)';
        bellContainer.style.color = '#c79f5e';
      });

      bellContainer.addEventListener('mouseleave', () => {
        bellContainer.style.background = 'rgba(255,255,255,0.1)';
        bellContainer.style.color = '#fff';
      });

      bellContainer.addEventListener('click', () => this.toggleInbox());

      topbar.appendChild(bellContainer);
      this.updateNotificationBell();
    }

    toggleInbox() {
      if (this.isOpen) {
        this.closeInbox();
      } else {
        this.openInbox();
      }
    }

    openInbox() {
      if (!this.inboxElement) return;
      
      this.inboxElement.classList.add('show');
      this.isOpen = true;
      
      this.notifications.forEach(n => n.read = true);
      this.saveNotifications();
      this.updateNotificationBell();
      this.updateInboxContent();
    }

    closeInbox() {
      if (!this.inboxElement) return;
      
      this.inboxElement.classList.remove('show');
      this.isOpen = false;
    }

    updateInboxContent() {
      if (!this.inboxElement) return;

      const content = this.inboxElement.querySelector('.notification-inbox-content');
      const clearBtn = this.inboxElement.querySelector('.notification-clear-btn');

      if (this.notifications.length === 0) {
        content.innerHTML = '<div class="notification-inbox-empty">No notifications yet</div>';
        clearBtn.disabled = true;
      } else {
        const notificationsHTML = this.notifications.map(notification => {
          const time = this.formatTime(notification.timestamp);
          return `
            <div class="notification-item ${notification.type}" data-id="${notification.id}">
              <button class="notification-item-close" onclick="event.stopPropagation(); notificationInbox.removeNotification(${notification.id})" title="Close notification">×</button>
              <div class="notification-item-message">${notification.message}</div>
              <div class="notification-item-time">${time}</div>
            </div>
          `;
        }).join('');

        content.innerHTML = notificationsHTML;
        clearBtn.disabled = false;

        content.querySelectorAll('.notification-item').forEach(item => {
          item.addEventListener('click', (e) => {
            if (!e.target.classList.contains('notification-item-close')) {
              const id = parseFloat(item.dataset.id);
              this.removeNotification(id);
            }
          });
        });
      }
    }

    updateNotificationBell() {
      const badge = document.querySelector('.notification-badge');
      if (!badge) return;

      const unreadCount = this.notifications.filter(n => !n.read).length;
      
      if (unreadCount > 0) {
        badge.textContent = unreadCount > 99 ? '99+' : unreadCount;
        badge.style.display = 'flex';
      } else {
        badge.style.display = 'none';
      }
    }

    removeNotification(id) {
      this.notifications = this.notifications.filter(n => n.id !== id);
      this.saveNotifications();
      this.updateInboxContent();
      this.updateNotificationBell();
    }

    clearAllNotifications() {
      this.notifications = [];
      this.saveNotifications();
      this.updateInboxContent();
      this.updateNotificationBell();
    }

    formatTime(timestamp) {
      const date = new Date(timestamp);
      const now = new Date();
      const diffMs = now - date;
      const diffMins = Math.floor(diffMs / 60000);
      const diffHours = Math.floor(diffMs / 3600000);
      const diffDays = Math.floor(diffMs / 86400000);

      if (diffMins < 1) return 'Just now';
      if (diffMins < 60) return `${diffMins}m ago`;
      if (diffHours < 24) return `${diffHours}h ago`;
      if (diffDays < 7) return `${diffDays}d ago`;
      
      return date.toLocaleDateString();
    }
  }

  // Initialize notification inbox
  const notificationInbox = new NotificationInbox();
  
  // Account notification function
  function addUserAccountNotification(action, details = {}) {
    const messages = {
      'username_changed': `👤 Your username has been changed to "${details.newUsername}"`,
      'profile_updated': '🖼️ Your profile has been updated successfully',
      'profile_picture_updated': '🖼️ Profile picture updated successfully',
      'password_changed': '🔐 Your password has been successfully updated',
      'email_changed': `📧 Your email address has been updated to "${details.newEmail}"`
    };
    
    const message = messages[action] || `👤 Account: ${action}`;
    const type = action.includes('failed') ? 'error' : 'success';
    
    notificationInbox.addNotification(message, type);
  }
  </script>
</body>
</html>