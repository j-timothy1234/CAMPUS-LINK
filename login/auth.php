<?php
/**
 * Unified Authentication Handler
 * 
 * Features:
 * - Rate limiting to prevent brute force attacks
 * - Optimized query to check only relevant tables
 * - Security logging
 * - Fast failure path for non-existent users
 * - Session fixation prevention
 */

require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/../sessions/session_config.php';
require_once __DIR__ . '/../security/SecurityManager.php';

// Set response header
header('Content-Type: application/json');

// Detect AJAX request
$isAjax = (
    (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
    || strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false
);

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

// Get and validate input
$identifier = trim($_POST['email'] ?? $_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($identifier) || empty($password)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Email/username and password are required']);
    exit;
}

// Rate limiting check
if (SecurityManager::isRateLimited($identifier)) {
    SecurityManager::logSecurityEvent('RATE_LIMITED', "Identifier: $identifier", 'WARNING');
    http_response_code(429);
    echo json_encode(['status' => 'error', 'message' => 'Too many login attempts. Please try again later.']);
    exit;
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    /**
     * Optimized unified authentication query
     * Uses UNION to check all three tables in a single database round-trip
     * Much faster than checking each table sequentially
     */
    $sql = "
        SELECT 'rider' AS user_type, Rider_ID AS user_id, Username, Email, Password, Profile_Photo FROM riders 
        WHERE Email = ? OR Username = ?
        UNION
        SELECT 'driver' AS user_type, Driver_ID AS user_id, Username, Email, Password, Profile_Photo FROM drivers 
        WHERE Email = ? OR Username = ?
        UNION
        SELECT 'client' AS user_type, Client_ID AS user_id, Username, Email, Password, Profile_Photo FROM clients 
        WHERE Email = ? OR Username = ?
        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Database query failed: ' . $conn->error);
    }

    // Bind parameters (same identifier for all email and username checks)
    $stmt->bind_param('ssssss', $identifier, $identifier, $identifier, $identifier, $identifier, $identifier);
    
    if (!$stmt->execute()) {
        throw new Exception('Query execution failed');
    }

    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    // Verify user exists and password is correct
    if ($user && password_verify($password, $user['Password'])) {
        // Clear rate limit on successful login
        SecurityManager::clearRateLimit($identifier);

        // Regenerate session ID to prevent session fixation
        session_regenerate_id(true);

        // Set session variables based on user type
        $_SESSION['user_type'] = $user['user_type'];
        $sessionKey = strtolower($user['user_type']) . '_id';
        $_SESSION[$sessionKey] = $user['user_id'];
        $_SESSION['username'] = $user['Username'];
        $_SESSION['email'] = $user['Email'];
        
        // Convert profile photo path if needed
        $photoPath = $user['Profile_Photo'] ?? '';
        if (!empty($photoPath) && strpos($photoPath, 'http') === false) {
            // It's a local path, ensure it's web-accessible
            if (strpos($photoPath, '../') === 0) {
                $_SESSION['profile_photo'] = $photoPath;
            } else {
                // Extract filename and convert to relative path
                $folder = ($user['user_type'] === 'rider') ? '../upload_rider/' : '../uploads_driver/';
                $_SESSION['profile_photo'] = $folder . basename($photoPath);
            }
        } else {
            $_SESSION['profile_photo'] = $photoPath ?: 'images/default_profile.png';
        }

        $_SESSION['loggedin'] = true;
        $_SESSION['login_time'] = time();

        // Log successful login
        SecurityManager::logSecurityEvent('LOGIN_SUCCESS', "User: {$user['Username']} ({$user['user_type']})", 'INFO');

        // Determine redirect based on user type
        $redirectMap = [
            'rider' => '../riderDashboard/riderDashboard.php',
            'driver' => '../driverDashboard/driverDashboard.php',
            'client' => '../clientDashboard/clientDashboard.php'
        ];
        $redirect = $redirectMap[$user['user_type']] ?? '../homepage/index.html';

        // Return JSON for AJAX, redirect for form submissions
        if ($isAjax) {
            http_response_code(200);
            echo json_encode([
                'status' => 'success',
                'message' => 'Login successful',
                'redirect' => $redirect
            ]);
        } else {
            header("Location: $redirect");
        }
        exit;
    }

    // Failed login attempt - invalid credentials
    SecurityManager::logSecurityEvent('LOGIN_FAILED', "Identifier: $identifier (user not found or invalid password)", 'WARNING');

    http_response_code(401);
    if ($isAjax) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid email/username or password',
            'register' => [
                'rider' => '../riders/rider.html',
                'driver' => '../drivers/driver.html',
                'client' => '../clients/client.html'
            ]
        ]);
    } else {
        header('Location: ../login/login.php?error=invalid_credentials');
    }
    exit;

} catch (Exception $e) {
    SecurityManager::logSecurityEvent('AUTH_ERROR', $e->getMessage(), 'ERROR');
    
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Internal server error. Please try again later.'
    ]);
    exit;
    
}
