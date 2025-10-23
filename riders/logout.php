<?php
// riders/logout.php
// Destroys rider session and redirects to central login page

require_once __DIR__ . '/../sessions/session_config.php';

// Clear all session variables
$_SESSION = [];

// Destroy session cookie if set
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'], $params['secure'], $params['httponly']
    );
}

// Finally destroy the session
session_unset();
session_destroy();

// Redirect to the centralized login page
header('Location: ../login/login.php');
exit();
