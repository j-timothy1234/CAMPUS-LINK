<?php
/**
 * api/v1/auth.php
 *
 * A small RESTful authentication API for CampusLink that handles
 * login (POST), session status check (GET), logout (DELETE) and
 * CORS preflight (OPTIONS).
 *
 * This endpoint is intentionally compatible with the existing
 * `login/auth.php` behavior (it will create the same PHP session
 * variables) so existing dashboard redirects and session checks
 * continue to work unchanged.
 *
 * Security notes (keep in mind):
 * - This file uses the Database class from `db_connect.php`.
 * - Passwords are verified using password_verify() (same as existing code).
 * - In production always use HTTPS and consider switching to token/JWT flows
 *   for API clients that cannot use browser sessions.
 *
 * Methods supported:
 * - OPTIONS: CORS preflight (returns allowed methods)
 * - POST: Authenticate user. Accepts JSON body or form POST.
 * - GET: Return current session information (if logged in).
 * - DELETE: Logout (destroy session)
 */

// Allow requests from same origin or configure below for your frontends
header('Access-Control-Allow-Origin: *'); // Change '*' to specific origin in production
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With, X-API-KEY');
header('Access-Control-Allow-Methods: OPTIONS, GET, POST, DELETE');
header('Content-Type: application/json; charset=utf-8');

// Bootstrap: database + session handling
require_once __DIR__ . '/../../db_connect.php';
require_once __DIR__ . '/../../sessions/session_config.php';

$method = $_SERVER['REQUEST_METHOD'];

// Handle CORS preflight
if ($method === 'OPTIONS') {
    // No body for OPTIONS; the Access-Control headers above are enough
    http_response_code(204);
    exit;
}

// Helper: JSON response helper
function json_resp($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

// Helper: read JSON body or fall back to form data
function get_request_data() {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($contentType, 'application/json') !== false) {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    // Fallback for form POSTs (application/x-www-form-urlencoded or multipart)
    return $_POST;
}

try {
    // Connect to DB
    $db = (new Database())->getConnection();

    if ($method === 'POST') {
        // Authenticate user
        $req = get_request_data();

        // Accept 'identifier' (email or username) and 'password'
        $identifier = trim($req['identifier'] ?? $req['email'] ?? $req['username'] ?? '');
        $password = $req['password'] ?? '';

        if ($identifier === '' || $password === '') {
            json_resp(['ok' => false, 'message' => 'Identifier and password are required'], 400);
        }

        // Reusable check function: table, emailCol, idCol, usernameCol
        $checkUser = function($table, $emailCol, $idCol, $usernameCol) use ($db, $identifier, $password) {
            $sql = "SELECT * FROM $table WHERE $emailCol = ? OR $usernameCol = ? LIMIT 1";
            $stmt = $db->prepare($sql);
            if (!$stmt) return null;
            $stmt->bind_param('ss', $identifier, $identifier);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res && $res->num_rows === 1) {
                $row = $res->fetch_assoc();
                // Password field in this project is 'Password'
                if (isset($row['Password']) && password_verify($password, $row['Password'])) {
                    return $row;
                }
            }
            return null;
        };

        // Try riders
        $rider = $checkUser('riders', 'Email', 'Rider_ID', 'Username');
        if ($rider) {
            // Initialize session values (same as login/auth.php)
            session_regenerate_id(true);
            $_SESSION['user_type'] = 'rider';
            $_SESSION['rider_id'] = $rider['Rider_ID'];
            $_SESSION['username'] = $rider['Username'];
            $_SESSION['email'] = $rider['Email'];
            $_SESSION['profile_photo'] = $rider['Profile_Photo'] ?? null;
            $_SESSION['loggedin'] = true;
            $_SESSION['login_time'] = time();

            json_resp([
                'ok' => true,
                'message' => 'Login successful',
                'user_type' => 'rider',
                'redirect' => '../riderDashboard/riderDashboard.php'
            ], 200);
        }

        // Try drivers
        $driver = $checkUser('drivers', 'Email', 'Driver_ID', 'Username');
        if ($driver) {
            session_regenerate_id(true);
            $_SESSION['user_type'] = 'driver';
            $_SESSION['driver_id'] = $driver['Driver_ID'];
            $_SESSION['username'] = $driver['Username'];
            $_SESSION['email'] = $driver['Email'];
            $_SESSION['profile_photo'] = $driver['Profile_Photo'] ?? null;
            $_SESSION['loggedin'] = true;
            $_SESSION['login_time'] = time();

            json_resp([
                'ok' => true,
                'message' => 'Login successful',
                'user_type' => 'driver',
                'redirect' => '../driverDashboard/driverDashboard.php'
            ], 200);
        }

        // Try clients
        $client = $checkUser('clients', 'Email', 'Client_ID', 'Username');
        if ($client) {
            session_regenerate_id(true);
            $_SESSION['user_type'] = 'client';
            $_SESSION['client_id'] = $client['Client_ID'];
            $_SESSION['username'] = $client['Username'];
            $_SESSION['email'] = $client['Email'];
            $_SESSION['profile_photo'] = $client['Profile_Photo'] ?? null;
            $_SESSION['loggedin'] = true;
            $_SESSION['login_time'] = time();

            json_resp([
                'ok' => true,
                'message' => 'Login successful',
                'user_type' => 'client',
                'redirect' => '../clientDashboard/clientDashboard.php'
            ], 200);
        }

        // Not found or invalid credentials
        json_resp([
            'ok' => false,
            'message' => 'User not found or invalid credentials',
            'register' => [
                'driver' => '../drivers/driver.html',
                'rider' => '../riders/rider.html',
                'client' => '../clients/client.html'
            ]
        ], 401);

    } elseif ($method === 'GET') {
        // Return current session info (if logged in). This is useful for clients
        // to check whether the user is already authenticated.
        if (!empty($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
            json_resp([
                'ok' => true,
                'user_type' => $_SESSION['user_type'] ?? null,
                'username' => $_SESSION['username'] ?? null,
                'email' => $_SESSION['email'] ?? null
            ], 200);
        }
        json_resp(['ok' => false, 'message' => 'Not authenticated'], 401);

    } elseif ($method === 'DELETE') {
        // Logout: destroy the session
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'], $params['secure'], $params['httponly']
            );
        }
        session_destroy();
        json_resp(['ok' => true, 'message' => 'Logged out'], 200);

    } else {
        json_resp(['ok' => false, 'message' => 'Method not allowed'], 405);
    }

} catch (Exception $e) {
    // Log the error server-side and return a generic message to client
    error_log('api/v1/auth.php error: ' . $e->getMessage());
    json_resp(['ok' => false, 'message' => 'Internal server error'], 500);
}

?>
