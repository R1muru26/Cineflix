<?php
session_start();

$servername = "localhost";
$username = "root"; 
$password = "";
$dbname = "cineflix";

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Define error variable
$error_message = "";

// Handle form submission from both login.html and this page
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Sanitize inputs (to prevent SQL injection)
    $email = $conn->real_escape_string($email);
    $password = $conn->real_escape_string($password);

    // Query to check if the email exists in the CustomerUser table
    $sql = "SELECT CustomerID, Name, Username, Email, Password FROM CustomerUser WHERE Email = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("Query failed: " . $conn->error);
    }
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['Password'])) {
            $_SESSION['user_id'] = $user['CustomerID'];
            $_SESSION['user_email'] = $user['Email'];
            $_SESSION['user_name'] = $user['Name'];
            $_SESSION['username'] = $user['Username'];

            header("Location: homepage.php");
            exit();
        } else {
            // Wrong password
            if (!headers_sent()) {
                header("Location: login.html?error=" . urlencode('Incorrect password or username.'));
                exit();
            }
            $error_message = "Incorrect password or username.";
        }
    } else {
        // Account does not exist
        if (!headers_sent()) {
            header("Location: login.html?error=" . urlencode("Account doesn't exist. Please sign up."));
            exit();
        }
        $error_message = "Account doesn't exist. Please sign up.";
    }
    if (isset($stmt)) { $stmt->close(); }
}

$conn->close();
?>

