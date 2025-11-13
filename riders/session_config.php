<?php
// session_config.php
// Session configuration for CampusLink Driver System

// Session configuration for security
// Set some ini settings before starting the session so they take effect
ini_set('session.gc_maxlifetime', 1800); // 30 mins session lifetime
ini_set('session.cookie_httponly', 1); // Prevent JavaScript access to cookies
ini_set('session.use_strict_mode', 1); // Use strict session mode

// Calculate secure flag for cookies
$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

// Set session cookie parameters (30 minutes) BEFORE starting session
// Use an array form (PHP 7.3+) so attributes like samesite are applied
session_set_cookie_params([
    'lifetime' => 1800, // 30 minutes
    'path' => '/',
    // Do not force domain (let PHP default) to avoid issues with ports/localhost
    'secure' => $secure,
    'httponly' => true, // Prevent JavaScript access
    'samesite' => 'Strict' // CSRF protection
]);

// Start session if not yet started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
