<?php
require_once __DIR__ . '/../sessions/session_config.php';

// Destroy session and redirect to global login page
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}
session_destroy();

header('Location: ../login/login.php');
exit();

?>
