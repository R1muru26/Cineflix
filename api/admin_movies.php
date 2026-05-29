<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit();
}

session_start();

if (empty($_SESSION['is_admin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Admin access required']);
    exit();
}

require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');



$conn = db_get_connection();

$rawBody = file_get_contents('php://input');
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';

if (stripos($contentType, 'application/json') !== false) {
    $input = json_decode($rawBody, true);
} else {
    // Support form submissions (e.g., with file uploads)
    $input = $_POST;
}

if (!$input || empty($input['action'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit();
}

$action = $input['action'];

if ($action === 'add') {
    $title = trim($input['title'] ?? '');
    $duration = isset($input['duration']) ? (int)$input['duration'] : 0;
    $genre = trim($input['genre'] ?? '');
    $description = trim($input['description'] ?? '');
    $releaseDate = trim($input['release_date'] ?? '');
    $rating = isset($input['rating']) ? (float)$input['rating'] : null;
    $poster = trim($input['poster'] ?? '');
    $trailerUrl = trim($input['trailer_url'] ?? '');

    if (empty($title) || $duration <= 0 || empty($genre) || empty($description)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Title, duration, genre, and description are required']);
        exit();
    }

    // Check if Movie table exists, create if not (with poster column)
    $checkTable = $conn->query("SHOW TABLES LIKE 'Movie'");
    if (!$checkTable || $checkTable->num_rows == 0) {
        $createTable = "CREATE TABLE IF NOT EXISTS Movie (
            MovieID INT AUTO_INCREMENT PRIMARY KEY,
            Title VARCHAR(255) NOT NULL,
            Duration INT NOT NULL,
            Genre VARCHAR(100) NULL,
            Description TEXT NULL,
            ReleaseDate DATE NULL,
            Rating DECIMAL(3,1) NULL,
            PosterPath VARCHAR(255) NULL,
            CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $conn->query($createTable);
    } else {
        // Ensure PosterPath column exists on existing tables
        $colCheck = $conn->query("SHOW COLUMNS FROM Movie LIKE 'PosterPath'");
        if (!$colCheck || $colCheck->num_rows === 0) {
            $conn->query("ALTER TABLE Movie ADD COLUMN PosterPath VARCHAR(255) NULL AFTER Rating");
        }

        // Ensure Description column exists on existing tables
        $descCheck = $conn->query("SHOW COLUMNS FROM Movie LIKE 'Description'");
        if (!$descCheck || $descCheck->num_rows === 0) {
            $conn->query("ALTER TABLE Movie ADD COLUMN Description TEXT NULL AFTER Genre");
        }
    }

    // If an image file is uploaded, store it and override the poster path
    if (!empty($_FILES['poster_file']) && is_array($_FILES['poster_file']) && $_FILES['poster_file']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'posters';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0775, true);
        }
        $origName = $_FILES['poster_file']['name'] ?? 'poster';
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        if ($ext === '') {
            $ext = 'jpg';
        }
        $safeExt = preg_replace('/[^a-z0-9]+/i', '', $ext) ?: 'jpg';
        $uniqueName = 'poster_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $safeExt;
        $fullPath = $uploadDir . DIRECTORY_SEPARATOR . $uniqueName;
        if (!move_uploaded_file($_FILES['poster_file']['tmp_name'], $fullPath)) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Failed to save poster image on server.']);
            exit();
        }
        // Relative path stored in DB
        $poster = 'uploads/posters/' . $uniqueName;
    }

    $sql = "INSERT INTO Movie (Title, Duration, Genre, Description, ReleaseDate, Rating, PosterPath, TrailerURL) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to prepare statement: ' . $conn->error]);
        exit();
    }

    $releaseDateValue = !empty($releaseDate) ? $releaseDate : null;
    $ratingValue = $rating !== null && $rating >= 0 && $rating <= 10 ? $rating : null;
    $posterValue = $poster !== '' ? $poster : null;
    $trailerValue = !empty($trailerUrl) ? $trailerUrl : null;

    // Types: title(s), duration(i), genre(s), description(s), releaseDate(s), rating(s), poster(s), trailer(s)
    $stmt->bind_param("sissssss", $title, $duration, $genre, $description, $releaseDateValue, $ratingValue, $posterValue, $trailerValue);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Movie added successfully', 'movie_id' => $conn->insert_id]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to add movie: ' . $stmt->error]);
    }
    $stmt->close();

} elseif ($action === 'remove') {
    $movieId = isset($input['movie_id']) ? (int)$input['movie_id'] : 0;

    if ($movieId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid movie ID']);
        exit();
    }

    $sql = "DELETE FROM Movie WHERE MovieID = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to prepare statement: ' . $conn->error]);
        exit();
    }

    $stmt->bind_param("i", $movieId);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Movie removed successfully']);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Movie not found']);
        }
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to remove movie: ' . $stmt->error]);
    }
    $stmt->close();

} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
}

$conn->close();
?>

