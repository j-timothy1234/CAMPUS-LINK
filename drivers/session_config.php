<?php
// session_config.php
// Session configuration for CampusLink Driver System

// Session configuration for security
// Set ini settings and cookie params BEFORE starting the session so they take effect
ini_set('session.gc_maxlifetime', 1800); // 30 mins session lifetime
ini_set('session.cookie_httponly', 1); // Prevent JavaScript access to cookies
ini_set('session.use_strict_mode', 1); // Use strict session mode

// Calculate secure flag for cookies (true when HTTPS is used)
$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

// Set session cookie parameters (30 minutes) BEFORE starting session
session_set_cookie_params([
    'lifetime' => 1800, // 30 minutes
    'path' => '/',
    // Do not force domain — let PHP default to avoid localhost/port issues
    'secure' => $secure,
    'httponly' => true, // Prevent JavaScript access
    'samesite' => 'Strict' // CSRF protection
]);

// Start session if not yet started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
