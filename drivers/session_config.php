<?php
// session_config.php
// Session configuration for CampusLink Driver System

// Start session if not yet started
// Start session if not yet started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    
    // Set basic session timeout (this can be changed after session start)
    ini_set('session.gc_maxlifetime', 1800); // 30 minutes
}

// Set session configuration for security
ini_set('session.gc_maxlifetime', 1800); // 30 mins session lifetime
ini_set('session.cookie_httponly', 1); // Prevent JavaScript access to cookies
ini_set('session.use_strict_mode', 1); // Use strict session mode

// Set session cookie parameters (30 minutes)
session_set_cookie_params([
    'lifetime' => 1800, // 30 minutes
    'path' => '/',
    'domain' => $_SERVER['HTTP_HOST'],
    'secure' => isset($_SERVER['HTTPS']), // Use HTTPS if available
    'httponly' => true, // Prevent JavaScript access
    'samesite' => 'Strict' // CSRF protection
]);
