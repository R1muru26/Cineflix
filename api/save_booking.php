<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit();
}

// Support JSON payloads (existing flow)
$rawBody = file_get_contents('php://input');
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';

if (stripos($contentType, 'application/json') !== false) {
    $input = json_decode($rawBody, true);
} else {
    // Fallback: build payload from POST (for future multipart support)
    $input = $_POST;
}

if (!$input || !is_array($input)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid payload']);
    exit();
}

$requiredFields = ['bookingId', 'itemName', 'showDate', 'showTime', 'seats', 'quantity', 'totalAmount', 'paymentMethod', 'itemType'];
foreach ($requiredFields as $field) {
    if (!isset($input[$field]) || $input[$field] === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => "Missing required field: {$field}"]);
        exit();
    }
}

$conn = db_get_connection();
db_ensure_bookings_table($conn);
db_ensure_erd_tables($conn);

// Extract source and hasVehicle before parking logic
$source      = isset($input['source']) ? strtolower(trim((string)$input['source'])) : 'online';
$hasVehicle  = isset($input['hasVehicle']) && ($input['hasVehicle'] === true || $input['hasVehicle'] === 'true' || $input['hasVehicle'] === 'yes');

// Assign parking for online bookings only when user has a vehicle
$parkingNumber = null;
if ($source === 'online' && $hasVehicle) {
    $parkingNumber = db_assign_parking($conn, $input['bookingId'] ?? '');
}

$bookingId   = $conn->real_escape_string($input['bookingId']);
$itemName    = $conn->real_escape_string($input['itemName']);
$itemType    = $conn->real_escape_string($input['itemType']);
$eventOption = isset($input['eventOption']) ? $conn->real_escape_string($input['eventOption']) : null;
$showDate    = $conn->real_escape_string($input['showDate']);
$showTime    = $conn->real_escape_string($input['showTime']);
$venue       = isset($input['venue']) ? $conn->real_escape_string($input['venue']) : null;
$seats       = $conn->real_escape_string($input['seats']);
$quantity    = (int)$input['quantity'];
$totalAmount = (float)$input['totalAmount'];
$paymentMethod = $conn->real_escape_string($input['paymentMethod']);
$addons      = isset($input['addons']) ? $conn->real_escape_string(json_encode($input['addons'])) : null;
$status      = isset($input['status']) ? $conn->real_escape_string($input['status']) : 'Paid';

// Optional discount fields (PWD / Senior)
$discountType   = isset($input['discountType']) ? strtolower(trim((string)$input['discountType'])) : null;
$discountPct    = isset($input['discountPercent']) ? (int)$input['discountPercent'] : 0;
$discountOrig   = isset($input['discountOriginalTotal']) ? (float)$input['discountOriginalTotal'] : null;
$discountFinal  = isset($input['discountedTotal']) ? (float)$input['discountedTotal'] : null;
$discountStatus = null;
$discountIdNum  = isset($input['discountIdNumber']) ? trim((string)$input['discountIdNumber']) : null;
$discountIdPath = null;

// Handle optional uploaded discount ID file (base64 JSON payload from booking.js)
if ($discountType === 'pwd' || $discountType === 'senior') {
    // ID number is always required when claiming a discount.
    if ($discountIdNum === null || $discountIdNum === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'ID number is required for PWD/Senior discounts.']);
        exit();
    }

    // Online bookings must include an uploaded document; staff walk-ins only require the ID number
    $isWalkin = ($source === 'walkin');

    if (!$isWalkin) {
        if (empty($input['discountFile']) || !is_array($input['discountFile'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'An ID document must be uploaded to continue with a PWD/Senior discount.']);
            exit();
        }

        $fileMeta = $input['discountFile'];
        $rawData  = $fileMeta['data'] ?? '';
        $origName = $fileMeta['name'] ?? 'idcard';
        $mimeType = $fileMeta['type'] ?? 'application/octet-stream';

        if (!is_string($rawData) || $rawData === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid ID document payload.']);
            exit();
        }

        $binary = base64_decode($rawData, true);
        if ($binary === false) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Failed to decode ID document.']);
            exit();
        }

        $uploadDir = realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'discount_ids';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0775, true);
        }

        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        if ($ext === '') {
            // Try to infer extension from mime type
            if (stripos($mimeType, 'png') !== false) {
                $ext = 'png';
            } elseif (stripos($mimeType, 'jpeg') !== false || stripos($mimeType, 'jpg') !== false) {
                $ext = 'jpg';
            } elseif (stripos($mimeType, 'pdf') !== false) {
                $ext = 'pdf';
            } else {
                $ext = 'bin';
            }
        }

        $safeExt = preg_replace('/[^a-z0-9]+/i', '', $ext) ?: 'bin';
        $uniqueName = 'disc_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $safeExt;
        $fullPath = $uploadDir . DIRECTORY_SEPARATOR . $uniqueName;

        if (file_put_contents($fullPath, $binary) === false) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Failed to save ID document on server.']);
            exit();
        }

        // Store relative path for later viewing in admin dashboard
        $discountIdPath = 'uploads/discount_ids/' . $uniqueName;
    }

    // Staff walk-in: discount approved immediately (staff verifies ID face-to-face)
    // Online: discount enters admin queue as Pending
    $discountStatus = $isWalkin ? 'Approved' : 'Pending';
}

// Determine customer context
$customerId    = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
$isStaff       = !empty($_SESSION['is_staff']);
$customerName  = $_SESSION['user_name'] ?? ($_SESSION['staff_name'] ?? 'Staff');
$customerEmail = $_SESSION['user_email'] ?? ($_SESSION['staff_email'] ?? null);

// Staff walk-in: record staff name only (no customer overrides).

// For non-staff flows, require an authenticated user
if (!$customerId && !($isStaff && $source === 'walkin')) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentication required.']);
    exit();
}

// --- Cut-off rules ---
// Movies: online bookings close 1 hour before show start
//         walk-in bookings close 10 minutes before show start
// Events: stop selling 1 week (7 days) before event date
try {
    $now = new DateTime('now');
    $showDateTime = null;

    if (!empty($showDate)) {
        // For movies, parse the first segment of the time range as the start
        if ($itemType === 'movie' && !empty($showTime)) {
            $parts = explode('-', $showTime);
            $startTimeStr = trim($parts[0]);
            $showDateTime = DateTime::createFromFormat('Y-m-d g:i A', $showDate . ' ' . $startTimeStr);
        } else {
            $showDateTime = new DateTime($showDate);
        }
    }

    if ($itemType === 'movie' && $showDateTime instanceof DateTime) {
        $diffSeconds = $showDateTime->getTimestamp() - $now->getTimestamp();
        if ($source === 'online' && $diffSeconds <= 3600) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Online ticket sales are closed for this showtime (cut-off is 1 hour before start).']);
            exit();
        }
        if ($source === 'walkin' && $diffSeconds <= 600) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Walk-in ticket sales are closed for this showtime (cut-off is 10 minutes before start).']);
            exit();
        }
    }

    if ($itemType === 'event' && !empty($showDate)) {
        $eventDate = new DateTime($showDate);
        $diffDays = (int)$eventDate->diff($now)->format('%r%a');
        // If event is in the future but fewer than 7 days away, block new bookings
        if ($eventDate > $now && $diffDays < 7) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Ticket selling for this event has closed (1-week lead time).']);
            exit();
        }
    }
} catch (Throwable $e) {
    // If date parsing fails, do not block.
}

// --- Double-booking prevention for seats ---
$seatList = array_filter(array_map('trim', explode(',', (string)$seats)));
if (!empty($seatList) && !empty($showDate) && !empty($showTime)) {
    $alreadyTaken = [];
    $checkSql = "
        SELECT seats FROM bookings
        WHERE item_type = '" . $conn->real_escape_string($itemType) . "'
          AND item_name = '" . $conn->real_escape_string($itemName) . "'
          AND show_date = '" . $conn->real_escape_string($showDate) . "'
          AND show_time = '" . $conn->real_escape_string($showTime) . "'
          AND " . ($venue ? "venue = '" . $venue . "'" : "venue IS NULL") . "
          AND status NOT IN ('Cancelled','Refunded')
    ";
    $result = $conn->query($checkSql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $existingSeats = array_filter(array_map('trim', explode(',', (string)$row['seats'])));
            $alreadyTaken = array_merge($alreadyTaken, $existingSeats);
        }
        $alreadyTaken = array_unique($alreadyTaken);
        $conflicts = array_values(array_intersect($seatList, $alreadyTaken));
        if (!empty($conflicts)) {
            http_response_code(409);
            echo json_encode([
                'success' => false,
                'error' => 'One or more selected seats have just been taken. Please choose different seats.',
                'conflictingSeats' => $conflicts
            ]);
            exit();
        }
    }
}

// Insert booking (include parking_number for online)
$stmt = $conn->prepare("INSERT INTO bookings 
    (booking_id, customer_id, customer_name, customer_email, item_type, item_name, event_option, show_date, show_time, venue, seats, quantity, total_amount, payment_method, addons, status, discount_type, discount_original_total, discounted_total, discount_status, discount_id_number, discount_id_path, parking_number) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to prepare statement: ' . $conn->error]);
    exit();
}

$stmt->bind_param(
    // booking_id, customer_id, customer_name, customer_email, item_type, item_name, event_option,
    // show_date, show_time, venue, seats, quantity, total_amount, payment_method, addons, status,
    // discount_type, discount_original_total, discounted_total, discount_status, discount_id_number, discount_id_path, parking_number
    'sisssssssssidssssddssss',
    $bookingId,
    $customerId,
    $customerName,
    $customerEmail,
    $itemType,
    $itemName,
    $eventOption,
    $showDate,
    $showTime,
    $venue,
    $seats,
    $quantity,
    $totalAmount,
    $paymentMethod,
    $addons,
    $status,
    $discountType,
    $discountOrig,
    $discountFinal,
    $discountStatus,
    $discountIdNum,
    $discountIdPath,
    $parkingNumber
);

if (!$stmt->execute()) {
    if ($conn->errno === 1062) {
        echo json_encode(['success' => true, 'message' => 'Booking already saved.']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to save booking: ' . $stmt->error]);
    }
    $stmt->close();
    exit();
}

$nativeBookingIdInt = $conn->insert_id;
$stmt->close();

// --- ERD DUAL-WRITE PIPELINE ---
try {
        // 1. & 5. Customer and Booking are already handled natively by the bookings table (lines 248-298).
        // 2. Sync Movie — use existing catalog rows only.
        $mId = null;
        $titleEsc = $conn->real_escape_string($itemName);
        $mRes = $conn->query("SELECT MovieID FROM movie WHERE Title = '$titleEsc' LIMIT 1");
        if ($mRes && $row = $mRes->fetch_assoc()) {
            $mId = (int)$row['MovieID'];
        } else {
            $trimEsc = $conn->real_escape_string(trim($itemName));
            $mRes2 = $conn->query("SELECT MovieID FROM movie WHERE LOWER(TRIM(Title)) = LOWER('$trimEsc') LIMIT 1");
            if ($mRes2 && $row2 = $mRes2->fetch_assoc()) {
                $mId = (int)$row2['MovieID'];
            }
        }
        if ($mId === null || $mId <= 0) {
            $conn->query("INSERT INTO movie (Title, Duration, Genre, ReleaseDate, Rating, section) VALUES ('$titleEsc', 120, 'Action', NOW(), 8.0, 'hidden')");
            $mId = $conn->insert_id;
        }

        if ($mId !== null && $mId > 0) {
            // 3. Sync Theater
            $tId = 0;
            try {
                $venueSafe = $venue ? $conn->real_escape_string($venue) : 'Unknown Theater';
                $theaterType = $eventOption ? $conn->real_escape_string($eventOption) : 'standard';
                $tSql = "SELECT TheaterID FROM theater WHERE TheatherName = '$venueSafe' LIMIT 1";
                $tRes = $conn->query($tSql);
                if ($tRes && $row = $tRes->fetch_assoc()) {
                    $tId = $row['TheaterID'];
                } else {
                    $conn->query("INSERT IGNORE INTO theater (TheatherName, TheaterType) VALUES ('$venueSafe', '$theaterType')");
                    $checkT = $conn->query("SELECT TheaterID FROM theater WHERE TheatherName = '$venueSafe' LIMIT 1");
                    if ($checkT && $rowT = $checkT->fetch_assoc()) {
                        $tId = $rowT['TheaterID'];
                    }
                }
            } catch (Throwable $e) { error_log("Theater Sync Error: " . $e->getMessage()); }

            if ($tId > 0) {
                // 4. Sync Showtime
                $sId = 0;
            try {
                $startDateTime = $showDate;
                if ($showTime) {
                    if (strpos($showTime, '-') !== false) {
                       $startDateTime = $showDate . ' ' . trim(explode('-', $showTime)[0]);
                    } else {
                       $startDateTime = $showDate . ' ' . $showTime;
                    }
                }
                $st = date('Y-m-d H:i:s', strtotime($startDateTime));
                $et = date('Y-m-d H:i:s', strtotime($startDateTime . ' + 2 hours'));
                
                $sSql = "SELECT ShowtimeID FROM showtime WHERE MovieID = $mId AND TheaterID = $tId AND StartTime = '$st' LIMIT 1";
                $sRes = $conn->query($sSql);
                if ($sRes && $row = $sRes->fetch_assoc()) {
                    $sId = $row['ShowtimeID'];
                } else {
                    $conn->query("INSERT IGNORE INTO showtime (MovieID, TheaterID, StartTime, EndTime) VALUES ($mId, $tId, '$st', '$et')");
                    // Fetch directly safely in case insert ignore passed
                    $sSql = "SELECT ShowtimeID FROM showtime WHERE MovieID = $mId AND TheaterID = $tId AND StartTime = '$st' LIMIT 1";
                    if ($sRes2 = $conn->query($sSql)) {
                        if ($row2 = $sRes2->fetch_assoc()) {
                            $sId = $row2['ShowtimeID'];
                        }
                    }
                }
                
                // Connect the ERD Showtime back to the legacy bookings table
                if ($sId > 0) {
                    $conn->query("UPDATE bookings SET ShowtimeID = $sId WHERE id = $nativeBookingIdInt");
                }
            } catch (Throwable $e) { error_log("Showtime Sync Error: " . $e->getMessage()); }
            } // End if tId > 0

            // 6. Sync Payment
            try {
                $conn->query("INSERT IGNORE INTO payment (BookingID, Amount, PaymentMethod, PaymentDate, PaymentStatus) VALUES ($nativeBookingIdInt, $totalAmount, '" . $conn->real_escape_string($paymentMethod) . "', NOW(), '" . $conn->real_escape_string($status) . "')");
            } catch (Throwable $e) { error_log("Payment Sync Error: " . $e->getMessage()); }

            // 7. Sync Tickets and Seats
            try {
                $seatList = array_filter(array_map('trim', explode(',', (string)$seats)));
                foreach ($seatList as $sl) {
                    $slSafe = $conn->real_escape_string($sl);
                    // TheaterID might be 0 here if it previously failed, fallback gracefully rather than failing the whole loop
                    $ttId = isset($tId) && $tId > 0 ? $tId : 1;
                    $seatSql = "SELECT SeatID FROM Seat WHERE TheaterID = $ttId AND SeatNumber = '$slSafe'";
                    $seatRes = $conn->query($seatSql);
                    $seatId = 1;
                    if ($seatRes && $sRow = $seatRes->fetch_assoc()) {
                        $seatId = $sRow['SeatID'];
                    } else {
                        $mNameSafe = $conn->real_escape_string($itemName);
                        $theaterTypeSafe = $eventOption ? $conn->real_escape_string($eventOption) : 'standard';
                        $conn->query("INSERT IGNORE INTO Seat (TheaterID, SeatNumber, AvailabilityStatus, MovieName, TheaterType) VALUES ($ttId, '$slSafe', 'Booked', '$mNameSafe', '$theaterTypeSafe')");
                        $csq = $conn->query("SELECT SeatID FROM Seat WHERE TheaterID = $ttId AND SeatNumber = '$slSafe'");
                        if ($csq && $cr = $csq->fetch_assoc()) {
                            $seatId = $cr['SeatID'];
                        }
                    }
                    $tktNum = 'TKT-' . strtoupper(substr(md5($bookingId . $slSafe), 0, 8));
                    $conn->query("INSERT IGNORE INTO ticket (BookingID, SeatID, TicketNumber, Status) VALUES ($nativeBookingIdInt, $seatId, '$tktNum', 'Valid')");
                }
            } catch (Throwable $e) { error_log("Tickets/Seats Sync Error: " . $e->getMessage()); }

            // 8. Sync Parking
            try {
                if ($parkingNumber) {
                    $conn->query("INSERT IGNORE INTO parking (BookingID, SlotNumber, EntryTime, ParkingStatus) VALUES ($nativeBookingIdInt, '" . $conn->real_escape_string($parkingNumber) . "', NOW(), 'Reserved')");
                }
            } catch (Throwable $e) { error_log("Parking Sync Error: " . $e->getMessage()); }

        }
} catch (Throwable $e) {
    error_log("ERD Sync Error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
}
// --- END ERD DUAL-WRITE PIPELINE ---

// Force an explicit commit to prevent persistent connection uncommitted states
$conn->commit();
// Also turn autocommit back on explicitly just in case it was disabled
$conn->autocommit(TRUE);

echo json_encode([
    'success' => true,
    'bookingId' => $bookingId,
    'parkingNumber' => $parkingNumber
]);

