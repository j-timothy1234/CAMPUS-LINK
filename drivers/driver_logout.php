<?php
// logout.php
// Secure logout handler with session destruction

// Include session configuration
require_once __DIR__ . '/session_config.php';

// Unset all session variables
$_SESSION = array();

// Delete session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session completely
session_destroy();

// Redirect to login page with success message
header("Location: ../login/login.html?message=logout_success");
exit();
