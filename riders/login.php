<?php
// login.php
// Driver login handler with session creation

require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/session_config.php';

// Set response header to JSON
header('Content-Type: application/json');

// Check if request is POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        // Get and validate input data
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password']; // Password will be verified, not stored
        
        // Validate required fields
        if (empty($email) || empty($password)) {
            throw new Exception("Email and password are required.");
        }
        
        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format.");
        }

        // Get database connection
        $conn = (new Database())->getConnection();
        
        // Check connection
        if (!$conn) {
            throw new Exception("Database connection failed.");
        }

        // Prepare SQL to find driver by email
        $sql = "SELECT * FROM riders WHERE Email = ?";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("SQL preparation failed: " . $conn->error);
        }
        
        // Bind parameters and execute
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Check if driver exists
        if ($result->num_rows === 1) {
            $rider = $result->fetch_assoc();

            // Verify password against hashed password in database
            if (password_verify($password, $rider['Password'])) {

                // Regenerate session ID for security (prevent session fixation)
                session_regenerate_id(true);

                // Create session variables with rider data
                $_SESSION['rider_id'] = $rider['Rider_ID'];
                $_SESSION['username'] = $rider['Username'];
                $_SESSION['email'] = $rider['Email'];
                // Convert absolute path to web-relative path for profile photo
                $photoPath = $rider['Profile_Photo'];
                if (!empty($photoPath)) {
                    // Extract filename from path (works with both absolute and relative paths)
                    $photoFilename = basename($photoPath);
                    $_SESSION['profile_photo'] = '../upload_rider/' . $photoFilename;
                } else {
                    $_SESSION['profile_photo'] = 'images/default_profile.png';
                }
                $_SESSION['user_type'] = 'rider';
                $_SESSION['loggedin'] = true;
                $_SESSION['login_time'] = time(); // Track login time for timeout

                // Send success response
                echo json_encode([
                    "status" => "success",
                    "message" => "Login successful!",
                    "redirect" => "../riderDashboard/riderDashboard.php"
                ]);
                // Ensure no further output
                exit();

            } else {
                // Invalid password
                throw new Exception("Invalid email or password.");
            }
        } else {
            // Rider not found
            throw new Exception("Invalid email or password.");
        }
        
        // Close database connections
        $stmt->close();
        $conn->close();

    } catch (Exception $e) {
        // Log error and send error response
        error_log("Rider Login Error: " . $e->getMessage());
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
// End output buffering and send output