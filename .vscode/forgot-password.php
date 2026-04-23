<?php
// Start session
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cineflix";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Process the form when submitted
if (isset($_POST['reset'])) {
    $email = $_POST['email'];

    // Sanitize the email input to prevent SQL injection
    $email = $conn->real_escape_string($email);

    // Check if the email exists in the database
    $sql_check = "SELECT CustomerID FROM CustomerUser WHERE Email = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("s", $email);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
        // Email exists, generate a password reset token
        $token = bin2hex(random_bytes(50));  // Generate a random token

        // Set the token's expiration time (1 hour from now)
        $expiry_time = date("Y-m-d H:i:s", strtotime('+1 hour'));

        // Store the token and its expiration time in the database
        $sql_update = "UPDATE CustomerUser SET ResetToken = ?, ResetTokenExpiry = ? WHERE Email = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("sss", $token, $expiry_time, $email);
        $stmt_update->execute();

        // Send password reset email with the reset link (This is just an example, ensure mail() is configured)
        $reset_link = "http://localhost/CineFlix/reset-password.php?token=" . $token;

        $subject = "CineFlix Password Reset";
        $message = "Click the following link to reset your password: \n" . $reset_link;
        $headers = "From: no-reply@cineflix.com";

        // Use the PHP mail function to send the email (make sure your server is configured to send emails)
        // Attempt to send mail; also echo the link for local testing
        @mail($email, $subject, $message, $headers);
        echo "<script>alert('If email is configured, a reset link was sent. For local testing, use this link: $reset_link'); window.location.href = 'login.php';</script>";

    } else {
        // If email doesn't exist, show an error
        echo "<script>alert('No account found with that email address.'); window.location.href = 'forgot-password.html';</script>";
    }

    // Close prepared statements
    $stmt_check->close();
    $stmt_update->close();
}

// Close the database connection
$conn->close();
?>
