<?php
// Vercel Single Entrypoint Router
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Clean path
$path = ltrim($uri, '/');
if ($path === '' || $path === '/') {
    $path = 'homepage.php';
}

// Check if a directory was requested without a file (e.g. /api/)
if (is_dir(__DIR__ . '/../' . $path)) {
    $path = rtrim($path, '/') . '/index.php';
}

$file = realpath(__DIR__ . '/../' . $path);
$baseDir = realpath(__DIR__ . '/../');

if ($file && strpos($file, $baseDir) === 0 && is_file($file)) {
    if ($file === __FILE__) {
        http_response_code(400);
        die("Direct access to router is not allowed.");
    }
    
    if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
        // Change working directory to the script's directory
        chdir(dirname($file));
        require $file;
    } else {
        readfile($file);
    }
} else {
    http_response_code(404);
    echo "404 Not Found: " . htmlspecialchars($path);
}
