<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Login Debug Test</h2>";

// Test 1: Check if session is working
echo "<h3>1. Session Status</h3>";
echo "Session ID: " . session_id() . "<br>";
echo "Session status: " . session_status() . "<br>";
echo "Session data: <pre>" . print_r($_SESSION, true) . "</pre>";

// Test 2: Check database connection
echo "<h3>2. Database Connection</h3>";
$conn = new mysqli("localhost", "root", "", "cineflix");
if ($conn->connect_error) {
    echo "Database connection failed: " . $conn->connect_error . "<br>";
} else {
    echo "Database connection successful<br>";
    
    // Test 3: Check if CustomerUser table exists and has records
    echo "<h3>3. CustomerUser Table Check</h3>";
    $result = $conn->query("SELECT COUNT(*) as count FROM CustomerUser");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "CustomerUser table has " . $row['count'] . " records<br>";
        
        // Show sample records (without passwords)
        $result2 = $conn->query("SELECT CustomerID, Name, Username, Email FROM CustomerUser LIMIT 3");
        if ($result2) {
            echo "Sample users:<br>";
            while ($row = $result2->fetch_assoc()) {
                echo "ID: " . $row['CustomerID'] . ", Name: " . $row['Name'] . ", Username: " . $row['Username'] . ", Email: " . $row['Email'] . "<br>";
            }
        }
    } else {
        echo "Error querying CustomerUser table: " . $conn->error . "<br>";
    }
    
    // Test 4: Check if IsVerified column exists
    echo "<h3>4. Table Structure</h3>";
    $colCheck = $conn->query("SHOW COLUMNS FROM CustomerUser LIKE 'IsVerified'");
    if ($colCheck && $colCheck->num_rows > 0) {
        echo "IsVerified column exists<br>";
    } else {
        echo "IsVerified column does NOT exist<br>";
    }
    
    $conn->close();
}

// Test 5: Simulate login process
echo "<h3>5. Simulated Login Test</h3>";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_email']) && isset($_POST['test_password'])) {
    $email = $_POST['test_email'];
    $password = $_POST['test_password'];
    
    echo "Testing login with email: $email<br>";
    
    $conn = new mysqli("localhost", "root", "", "cineflix");
    $sql = "SELECT CustomerID, Name, Username, Email, Password FROM CustomerUser WHERE Email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        echo "User found in database<br>";
        echo "Stored password hash: " . $user['Password'] . "<br>";
        
        if (password_verify($password, $user['Password'])) {
            echo "Password verification SUCCESS<br>";
            
            // Set session variables like in login.php
            $_SESSION['user_id'] = $user['CustomerID'];
            $_SESSION['user_email'] = $user['Email'];
            $_SESSION['user_name'] = $user['Name'];
            $_SESSION['username'] = $user['Username'];
            
            echo "Session variables set. Current session:<br>";
            echo "<pre>" . print_r($_SESSION, true) . "</pre>";
            
            echo "<a href='homepage.php'>Go to Homepage</a><br>";
        } else {
            echo "Password verification FAILED<br>";
        }
    } else {
        echo "User NOT found in database<br>";
    }
    
    $stmt->close();
    $conn->close();
} else {
    echo "<form method='post'>
        Email: <input type='email' name='test_email' required><br>
        Password: <input type='password' name='test_password' required><br>
        <input type='submit' value='Test Login'>
    </form>";
}

// Test 6: Check if we can access session from homepage
echo "<h3>6. Session Link Test</h3>";
echo "<a href='homepage.php'>Go to Homepage</a> (Check if user menu appears)<br>";
echo "<a href='clear_session.php'>Clear Session</a><br>";
?>
