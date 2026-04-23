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
if (isset($_POST['signup'])) {
    // Get form data
    $name = $_POST['name'] ?? '';
    $username_input = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    // Sanitize inputs to prevent SQL injection
    $name = $conn->real_escape_string($name);
    $username_input = $conn->real_escape_string($username_input);
    $email = $conn->real_escape_string($email);
    $phone = $conn->real_escape_string($phone);
    $password = $conn->real_escape_string($password);
    $password_confirm = $conn->real_escape_string($password_confirm);

    if (trim($name) === '' || trim($username_input) === '' || trim($email) === '' || trim($password) === '') {
        echo "<script>alert('All fields are required.'); window.location.href = 'signup.html';</script>";
        exit();
    }

    if ($password !== $password_confirm) {
        echo "<script>alert('Passwords do not match.'); window.location.href = 'signup.html';</script>";
        exit();
    }

    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Check if email or username already exists in the CustomerUser table
    $sql_check = "SELECT 1 FROM CustomerUser WHERE Email = ? OR Username = ?";
    $stmt_check = $conn->prepare($sql_check);
    if ($stmt_check === false) {
        die("Error in preparing query: " . $conn->error);
    }
    $stmt_check->bind_param("ss", $email, $username_input);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check && $result_check->num_rows > 0) {
        echo "<script>alert('Email or username already exists.'); window.location.href = 'signup.html';</script>";
        $stmt_check->close();
        $conn->close();
        exit();
    }
    $stmt_check->close();

    // Insert new user into the CustomerUser table
    $sql_insert = "INSERT INTO CustomerUser (Name, Username, Email, Password, Phone) VALUES (?, ?, ?, ?, ?)";
    $stmt_insert = $conn->prepare($sql_insert);
    if ($stmt_insert === false) {
        die("Error in preparing query: " . $conn->error);
    }
    $stmt_insert->bind_param("sssss", $name, $username_input, $email, $hashed_password, $phone);

    if ($stmt_insert->execute()) {
        // After successful sign-up, redirect to styled Login page
        echo "<script>alert('Sign up successful. Please log in.'); window.location.href = 'login.html';</script>";
        $stmt_insert->close();
        $conn->close();
        exit();
    } else {
        echo "<script>alert('Error: " . $stmt_insert->error . "'); window.location.href = 'signup.html';</script>";
    }

    $stmt_insert->close();
}

// Close database connection
$conn->close();
?>
