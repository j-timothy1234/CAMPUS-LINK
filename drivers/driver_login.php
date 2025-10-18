<?php
// login.php
require_once __DIR__ . '/../db_connect.php';

// Enable maximum error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session
session_start();

header('Content-Type: application/json');

// Log for debugging
file_put_contents('login_debug.log', "=== LOGIN ATTEMPT ===\n", FILE_APPEND);
file_put_contents('login_debug.log', "POST data: " . print_r($_POST, true) . "\n", FILE_APPEND);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        
        file_put_contents('login_debug.log', "Email: $email, Password provided: " . (!empty($password) ? "YES" : "NO") . "\n", FILE_APPEND);

        if (empty($email) || empty($password)) {
            throw new Exception("Email and password are required.");
        }

        $conn = (new Database())->getConnection();
        
        if (!$conn) {
            throw new Exception("Database connection failed.");
        }

        // Check if driver exists
        $sql = "SELECT * FROM drivers WHERE Email = ?";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("SQL preparation failed: " . $conn->error);
        }
        
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        file_put_contents('login_debug.log', "Found drivers: " . $result->num_rows . "\n", FILE_APPEND);

        if ($result->num_rows === 1) {
            $driver = $result->fetch_assoc();
            file_put_contents('login_debug.log', "Driver found: " . $driver['Username'] . "\n", FILE_APPEND);
            
            // Verify password
            if (password_verify($password, $driver['Password'])) {
                file_put_contents('login_debug.log', "Password verified successfully\n", FILE_APPEND);
                
                // Create session
                $_SESSION['driver_id'] = $driver['Driver_ID'];
                $_SESSION['username'] = $driver['Username'];
                $_SESSION['email'] = $driver['Email'];
                $_SESSION['profile_photo'] = $driver['Profile_Photo'];
                $_SESSION['user_type'] = 'driver';
                $_SESSION['loggedin'] = true;
                $_SESSION['login_time'] = time();
                
                file_put_contents('login_debug.log', "Session created for: " . $driver['Username'] . "\n", FILE_APPEND);
                
                echo json_encode([
                    "status" => "success", 
                    "message" => "Login successful!",
                    "redirect" => "../driverDashboard/driverDashboard.php"
                ]);
                
            } else {
                file_put_contents('login_debug.log', "Password verification FAILED\n", FILE_APPEND);
                throw new Exception("Invalid email or password.");
            }
        } else {
            file_put_contents('login_debug.log', "No driver found with email: $email\n", FILE_APPEND);
            throw new Exception("Invalid email or password.");
        }
        
        $stmt->close();
        $conn->close();

    } catch (Exception $e) {
        file_put_contents('login_debug.log', "ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
        echo json_encode([
            "status" => "error", 
            "message" => $e->getMessage()
        ]);
    }
} else {
    file_put_contents('login_debug.log', "Invalid request method: " . $_SERVER["REQUEST_METHOD"] . "\n", FILE_APPEND);
    echo json_encode([
        "status" => "error", 
        "message" => "Invalid request method."
    ]);
}

file_put_contents('login_debug.log', "=== LOGIN COMPLETED ===\n\n", FILE_APPEND);
