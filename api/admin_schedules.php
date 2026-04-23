<?php
header('Content-Type: application/json');

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

$conn = db_get_connection();

$rawBody = file_get_contents('php://input');
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';

if (stripos($contentType, 'application/json') !== false) {
    $input = json_decode($rawBody, true);
} else {
    $input = $_POST;
}

if (!$input || empty($input['action'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit();
}

$action = $input['action'];

// Create movie_schedules table if it doesn't exist
$createTable = "CREATE TABLE IF NOT EXISTS movie_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    movie_id INT NOT NULL,
    movie_title VARCHAR(255) NOT NULL,
    theatre_type ENUM('Standard', 'IMAX', '3D', 'Directors Club') NOT NULL DEFAULT 'Standard',
    cinema_hall VARCHAR(50) NOT NULL,
    show_date DATE NOT NULL,
    show_time VARCHAR(50) NOT NULL,
    end_time VARCHAR(50) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    available_seats INT NOT NULL DEFAULT 80,
    total_seats INT NOT NULL DEFAULT 80,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_movie_theatre (movie_id, theatre_type),
    INDEX idx_date_time (show_date, show_time),
    INDEX idx_theatre_hall (theatre_type, cinema_hall),
    FOREIGN KEY (movie_id) REFERENCES Movie(MovieID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

$conn->query($createTable);

// Update ENUM if table already exists (safety check for existing tables)
$conn->query("ALTER TABLE movie_schedules MODIFY COLUMN theatre_type ENUM('Standard', 'IMAX', '3D', 'Directors Club') NOT NULL DEFAULT 'Standard'");

if ($action === 'add_schedule') {
    $movieId = isset($input['movie_id']) ? (int)$input['movie_id'] : 0;
    $movieTitle = trim($input['movie_title'] ?? '');
    $theatreType = trim($input['theatre_type'] ?? '');
    $cinemaHall = trim($input['cinema_hall'] ?? '');
    $showDate = trim($input['show_date'] ?? '');
    $showTime = trim($input['show_time'] ?? '');
    $endTime = trim($input['end_time'] ?? '');
    $price = isset($input['price']) ? (float)$input['price'] : 0;
    
    if ($movieId <= 0 || empty($movieTitle) || empty($theatreType) || empty($cinemaHall) || empty($showDate) || empty($showTime) || empty($endTime) || $price <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'All fields are required']);
        exit();
    }
    
    // Validate theatre type
    $validTheatreTypes = ['Standard', 'IMAX', '3D', 'Directors Club'];
    if (!in_array($theatreType, $validTheatreTypes)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid theatre type: ' . $theatreType]);
        exit();
    }
    
    $sql = "INSERT INTO movie_schedules (movie_id, movie_title, theatre_type, cinema_hall, show_date, show_time, end_time, price) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to prepare statement: ' . $conn->error]);
        exit();
    }
    
    $stmt->bind_param("isssssd", $movieId, $movieTitle, $theatreType, $cinemaHall, $showDate, $showTime, $endTime, $price);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Schedule added successfully', 'schedule_id' => $conn->insert_id]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to add schedule: ' . $stmt->error]);
    }
    $stmt->close();

} elseif ($action === 'get_schedules') {
    $movieId = isset($input['movie_id']) ? (int)$input['movie_id'] : 0;
    
    if ($movieId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid movie ID']);
        exit();
    }
    
    $sql = "SELECT * FROM movie_schedules WHERE movie_id = ? AND is_active = TRUE ORDER BY show_date, show_time";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to prepare statement: ' . $conn->error]);
        exit();
    }
    
    $stmt->bind_param("i", $movieId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $schedules = [];
    while ($row = $result->fetch_assoc()) {
        $schedules[] = $row;
    }
    
    echo json_encode(['success' => true, 'schedules' => $schedules]);
    $stmt->close();

} elseif ($action === 'remove_schedule') {
    $scheduleId = isset($input['schedule_id']) ? (int)$input['schedule_id'] : 0;
    
    if ($scheduleId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid schedule ID']);
        exit();
    }
    
    $sql = "DELETE FROM movie_schedules WHERE id = ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to prepare statement: ' . $conn->error]);
        exit();
    }
    
    $stmt->bind_param("i", $scheduleId);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Schedule removed successfully']);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Schedule not found']);
        }
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to remove schedule: ' . $stmt->error]);
    }
    $stmt->close();

} elseif ($action === 'get_all_schedules') {
    $sql = "SELECT ms.*, m.Title as movie_title FROM movie_schedules ms LEFT JOIN Movie m ON ms.movie_id = m.MovieID WHERE ms.is_active = TRUE ORDER BY ms.show_date, ms.show_time";
    $result = $conn->query($sql);
    
    if (!$result) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to fetch schedules: ' . $conn->error]);
        exit();
    }
    
    $schedules = [];
    while ($row = $result->fetch_assoc()) {
        $schedules[] = $row;
    }
    
    echo json_encode(['success' => true, 'schedules' => $schedules]);

} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
}

$conn->close();
?>
