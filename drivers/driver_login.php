<?php
// driver_login.php - CLEANED VERSION
// Turn off ALL output buffering and ensure no whitespace before PHP
ob_clean();

// Include files FIRST
require_once __DIR__ . '/../db_connect.php';
require_once 'session_config.php';

// Enable error reporting but don't display to users
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Set JSON header IMMEDIATELY
header('Content-Type: application/json');

// Ensure no output before this point
if (ob_get_level()) ob_clean();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        // Get input data
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        // Basic validation
        if (empty($email) || empty($password)) {
            throw new Exception("Email and password are required.");
        }

        // Get database connection
        $conn = (new Database())->getConnection();
        
        if (!$conn) {
            throw new Exception("Database connection failed.");
        }

        // Check if driver exists
        $sql = "SELECT * FROM drivers WHERE Email = ?";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("SQL preparation failed.");
        }
        
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $driver = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $driver['Password'])) {
                
                // Regenerate session for security
                session_regenerate_id(true);
                
                // Create session
                $_SESSION['driver_id'] = $driver['Driver_ID'];
                $_SESSION['username'] = $driver['Username'];
                $_SESSION['email'] = $driver['Email'];
                $_SESSION['profile_photo'] = $driver['Profile_Photo'];
                $_SESSION['user_type'] = 'driver';
                $_SESSION['loggedin'] = true;
                $_SESSION['login_time'] = time();
                
                // Return success response
                echo json_encode([
                    "status" => "success", 
                    "message" => "Login successful!",
                    "redirect" => "../driverDashboard/driverDashboard.php"
                ]);
                
            } else {
                throw new Exception("Invalid email or password.");
            }
        } else {
            throw new Exception("Invalid email or password.");
        }
        
        $stmt->close();
        $conn->close();

    } catch (Exception $e) {
        // Log error but don't output to user
        error_log("Driver Login Error: " . $e->getMessage());
        
        // Return clean error response
        echo json_encode([
            "status" => "error", 
            "message" => $e->getMessage()
        ]);
    }
} else {
    // Invalid request method
    echo json_encode([
        "status" => "error", 
        "message" => "Invalid request method."
    ]);
}

// Ensure no output after JSON
exit();
