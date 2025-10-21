<?php
// auth.php - centralized login for clients, drivers and riders
require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/../riders/session_config.php';

// Detect if client expects JSON (AJAX) or a normal form POST
$accept = $_SERVER['HTTP_ACCEPT'] ?? '';
$isAjax = (
    (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
    || strpos($accept, 'application/json') !== false
);
if ($isAjax) {
    header('Content-Type: application/json');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status"=>"error","message"=>"Invalid request method"]);
    exit();
}

$identifier = trim($_POST['email'] ?? $_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($identifier) || empty($password)) {
    echo json_encode(["status"=>"error","message"=>"Email/username and password are required"]);
    exit();
}

try {
    $db = (new Database())->getConnection();

    // Helper to query a table with email/username
    $checkUser = function($table, $emailField, $idField, $usernameField, $profileField) use ($db, $identifier, $password) {
        $sql = "SELECT * FROM $table WHERE $emailField = ? OR $usernameField = ? LIMIT 1";
        $stmt = $db->prepare($sql);
        if (!$stmt) return null;
        $stmt->bind_param('ss', $identifier, $identifier);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows === 1) {
            $row = $res->fetch_assoc();
            if (isset($row['Password']) && password_verify($password, $row['Password'])) {
                return $row;
            }
        }
        return null;
    };

    // Check riders
    $rider = $checkUser('riders', 'Email', 'Rider_ID', 'Username', 'Profile_Photo');
    if ($rider) {
        session_regenerate_id(true);
        $_SESSION['user_type'] = 'rider';
        $_SESSION['rider_id'] = $rider['Rider_ID'];
        $_SESSION['username'] = $rider['Username'];
        $_SESSION['email'] = $rider['Email'];
        $_SESSION['profile_photo'] = $rider['Profile_Photo'];
        $_SESSION['loggedin'] = true;
        $_SESSION['login_time'] = time();
        if ($isAjax) {
            echo json_encode(["status"=>"success","message"=>"Login successful","redirect"=>"../riderDashboard/riderDashboard.php"]);
            exit();
        } else {
            header('Location: ../riderDashboard/riderDashboard.php');
            exit();
        }
    }

    // Check drivers
    $driver = $checkUser('drivers', 'Email', 'Driver_ID', 'Username', 'Profile_Photo');
    if ($driver) {
        session_regenerate_id(true);
        $_SESSION['user_type'] = 'driver';
        $_SESSION['driver_id'] = $driver['Driver_ID'];
        $_SESSION['username'] = $driver['Username'];
        $_SESSION['email'] = $driver['Email'];
        $_SESSION['profile_photo'] = $driver['Profile_Photo'];
        $_SESSION['loggedin'] = true;
        $_SESSION['login_time'] = time();
        if ($isAjax) {
            echo json_encode(["status"=>"success","message"=>"Login successful","redirect"=>"../driverDashboard/driverDashboard.php"]);
            exit();
        } else {
            header('Location: ../driverDashboard/driverDashboard.php');
            exit();
        }
    }

    // Check clients
    $client = $checkUser('clients', 'Email', 'Client_ID', 'Username', 'Profile_Photo');
    if ($client) {
        session_regenerate_id(true);
        $_SESSION['user_type'] = 'client';
        $_SESSION['client_id'] = $client['Client_ID'];
        $_SESSION['username'] = $client['Username'];
        $_SESSION['email'] = $client['Email'];
        $_SESSION['profile_photo'] = $client['Profile_Photo'];
        $_SESSION['loggedin'] = true;
        $_SESSION['login_time'] = time();
        if ($isAjax) {
            echo json_encode(["status"=>"success","message"=>"Login successful","redirect"=>"../clientDashboard/clientDashboard.php"]);
            exit();
        } else {
            header('Location: ../clientDashboard/clientDashboard.php');
            exit();
        }
    }

    // No user found -- suggest registration paths
    if ($isAjax) {
        echo json_encode([
            "status"=>"error",
            "message"=>"User not found or invalid credentials",
            "register"=>[
                "driver"=>"../drivers/driver.html",
                "rider"=>"../riders/rider.html",
                "client"=>"../clients/client.html"
            ]
        ]);
        exit();
    } else {
        // For normal form POSTs, redirect back to login with an error code
        $qs = http_build_query(['error' => 'invalid_credentials']);
        header('Location: ../login/login.php?' . $qs);
        exit();
    }

} catch (Exception $e) {
    error_log('Auth error: ' . $e->getMessage());
    echo json_encode(["status"=>"error","message"=>"Internal server error"]);
    exit();
}
