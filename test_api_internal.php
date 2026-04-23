<?php
require 'includes/db.php';
$conn = db_get_connection();

$input = [
    'bookingId' => 'CF' . rand(1000, 9999),
    'customerId' => 1,
    'customerName' => 'Test User',
    'customerEmail' => 'test@test.com',
    'itemType' => 'movie',
    'itemName' => 'Test Movie',
    'eventOption' => '',
    'showDate' => '2026-04-07',
    'showTime' => '15:00',
    'venue' => 'Cinema 1',
    'seats' => 'B2,B3',
    'quantity' => 2,
    'totalAmount' => 700.0,
    'paymentMethod' => 'card',
    'addons' => [],
    'parkingNumber' => 'P12'
];

$bookingId      = $conn->real_escape_string($input['bookingId']);
$customerId     = isset($input['customerId']) ? (int)$input['customerId'] : null;
$customerName   = isset($input['customerName']) ? trim($input['customerName']) : '';
$customerEmail  = isset($input['customerEmail']) ? trim($input['customerEmail']) : '';
$itemType       = isset($input['itemType']) ? trim($input['itemType']) : 'movie';
$itemName       = isset($input['itemName']) ? trim($input['itemName']) : '';
$eventOption    = isset($input['eventOption']) ? trim($input['eventOption']) : '';
$showDate       = isset($input['showDate']) ? trim($input['showDate']) : null;
$showTime       = isset($input['showTime']) ? trim($input['showTime']) : null;
$venue          = isset($input['venue']) ? trim($input['venue']) : '';
$seats          = isset($input['seats']) ? trim($input['seats']) : '';
$quantity       = isset($input['quantity']) ? (int)$input['quantity'] : 0;
$totalAmount    = isset($input['totalAmount']) ? (float)$input['totalAmount'] : 0;
$paymentMethod  = isset($input['paymentMethod']) ? trim($input['paymentMethod']) : 'card';
$addons         = isset($input['addons']) ? json_encode($input['addons']) : null;
$status         = 'Paid';
$discountType   = null;
$discountOrig   = null;
$discountFinal  = null;
$discountStatus = null;
$discountIdNum  = null;
$discountIdPath = null;
$parkingNumber  = isset($input['parkingNumber']) ? trim($input['parkingNumber']) : null;

$stmt = $conn->prepare("INSERT INTO bookings 
    (booking_id, customer_id, customer_name, customer_email, item_type, item_name, event_option, show_date, show_time, venue, seats, quantity, total_amount, payment_method, addons, status, discount_type, discount_original_total, discounted_total, discount_status, discount_id_number, discount_id_path, parking_number) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

$stmt->bind_param(
    'sisssssssssidssssddssss',
    $bookingId, $customerId, $customerName, $customerEmail, $itemType, $itemName, 
    $eventOption, $showDate, $showTime, $venue, $seats, $quantity, $totalAmount, 
    $paymentMethod, $addons, $status, $discountType, $discountOrig, $discountFinal, 
    $discountStatus, $discountIdNum, $discountIdPath, $parkingNumber
);

if (!$stmt->execute()) {
    echo "ERROR bookings: " . $stmt->error . "\n";
}
$stmt->close();

try {
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
        $tId = 0;
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

        if ($tId > 0) {
            $sId = 0;
            $startDateTime = $showDate . ' ' . $showTime;
            $st = date('Y-m-d H:i:s', strtotime($startDateTime));
            $et = date('Y-m-d H:i:s', strtotime($startDateTime . ' + 2 hours'));
            
            $sSql = "SELECT ShowtimeID FROM showtime WHERE MovieID = $mId AND TheaterID = $tId AND StartTime = '$st' LIMIT 1";
            $sRes = $conn->query($sSql);
            if ($sRes && $row = $sRes->fetch_assoc()) {
                $sId = $row['ShowtimeID'];
            } else {
                $conn->query("INSERT IGNORE INTO showtime (MovieID, TheaterID, StartTime, EndTime) VALUES ($mId, $tId, '$st', '$et')");
                $sSql = "SELECT ShowtimeID FROM showtime WHERE MovieID = $mId AND TheaterID = $tId AND StartTime = '$st' LIMIT 1";
                if ($sRes2 = $conn->query($sSql)) {
                    if ($row2 = $sRes2->fetch_assoc()) {
                        $sId = $row2['ShowtimeID'];
                    }
                }
            }

            if ($sId > 0) {
                $conn->query("UPDATE bookings SET ShowtimeID = $sId WHERE booking_id = '" . $conn->real_escape_string($bookingId) . "'");
                
                $custEmailSafe = $customerEmail ? $conn->real_escape_string($customerEmail) : 'walkin@cineflix.local';
                $custNameSafe = $customerName ? $conn->real_escape_string($customerName) : 'Walkin Customer';
                $cId = $customerId ? (int)$customerId : rand(100000, 999999);
                $conn->query("INSERT IGNORE INTO customeruser_erd (CustomerID, CustomerName, Email) VALUES ($cId, '$custNameSafe', '$custEmailSafe')");

                $bNum = $conn->real_escape_string($bookingId);
                $q = (int)$quantity;
                $bDate = date('Y-m-d', strtotime($showDate ?: 'now'));
                $bStat = $conn->real_escape_string($status);
                if (!$conn->query("INSERT IGNORE INTO bookings_erd (BookingID, CustomerID, ShowTimeID, Quantity, BookingDate, BookingStatus) VALUES ('$bNum', $cId, $sId, $q, '$bDate', '$bStat')")) {
                    echo "ERROR bookings_erd: " . $conn->error . "\n";
                }
            }
        }

        if (!$conn->query("INSERT IGNORE INTO payment (BookingID, Amount, PaymentMethod, PaymentDate, PaymentStatus) VALUES ('" . $conn->real_escape_string($bookingId) . "', $totalAmount, '" . $conn->real_escape_string($paymentMethod) . "', NOW(), '" . $conn->real_escape_string($status) . "')")) {
            echo "ERROR payment: " . $conn->error . "\n";
        }

        $seatList = array_filter(array_map('trim', explode(',', (string)$seats)));
        foreach ($seatList as $sl) {
            $slSafe = $conn->real_escape_string($sl);
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
            if (!$conn->query("INSERT IGNORE INTO ticket (BookingID, SeatID, TicketNumber, Status) VALUES ('" . $conn->real_escape_string($bookingId) . "', $seatId, '$tktNum', 'Valid')")) {
                echo "ERROR ticket: " . $conn->error . "\n";
            }
        }
        
    }
} catch (Throwable $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
}

echo "Done $bookingId\n";
?>
