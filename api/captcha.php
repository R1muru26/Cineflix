<?php
// Simple CAPTCHA endpoint that generates a math question
// and stores the answer in the PHP session.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
header('Content-Type: application/json');

}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit();
}

// Generate a very simple math captcha like "3 + 5 = ?"
$a = random_int(1, 9);
$b = random_int(1, 9);

$_SESSION['captcha_answer'] = $a + $b;

echo json_encode([
    'success' => true,
    'question' => "{$a} + {$b} = ?"
]);

