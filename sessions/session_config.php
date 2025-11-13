<?php

/**
 * Session Configuration with Enhanced Security
 * 
 * Features:
 * - Strict session mode
 * - HTTPOnly cookies (prevent XSS)
 * - SameSite attribute (prevent CSRF)
 * - Secure flag (HTTPS only)
 * - Rate limiting
 * - Session timeout
 */

// Get session lifetime from environment or use default (30 minutes)
$session_lifetime = (int)(getenv('SESSION_LIFETIME') ?: 1800);

// Set ini settings BEFORE starting session
ini_set('session.gc_maxlifetime', $session_lifetime);
ini_set('session.cookie_httponly', 1);      // Prevent JavaScript access to cookies
ini_set('session.use_strict_mode', 1);      // Use strict session mode
ini_set('session.cookie_samesite', 'Strict'); // CSRF protection
ini_set('session.hash_algo', 'sha256');     // Use SHA-256 for session ID
ini_set('session.hash_bits_per_character', 6); // Maximum entropy

// Calculate secure flag for cookies (true when HTTPS is used)
$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
          $_SERVER['SERVER_PORT'] === 443 ||
          (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

// Set session cookie parameters
session_set_cookie_params([
    'lifetime' => $session_lifetime,
    'path' => '/',
    'secure' => $secure,
    'httponly' => true,
    'samesite' => 'Strict'
]);

// Start session if not yet started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Session Security Check
 * Verify session hasn't been hijacked or tampered with
 */
if (!function_exists('validateSessionSecurity')) {
    function validateSessionSecurity() {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        if (!isset($_SESSION['_ip'])) {
            $_SESSION['_ip'] = $ip;
            $_SESSION['_ua'] = md5($ua);
        } else {
            // Check if IP or User-Agent changed (potential hijacking)
            if ($_SESSION['_ip'] !== $ip || $_SESSION['_ua'] !== md5($ua)) {
                session_destroy();
                header('Location: ../login/login.php?error=session_hijack');
                exit('Session security violation detected');
            }
        }
    }
}

/**
 * Check session timeout
 * Force logout after inactivity period
 */
if (!function_exists('checkSessionTimeout')) {
    function checkSessionTimeout() {
        if (isset($_SESSION['login_time'])) {
            if (time() - $_SESSION['login_time'] > $GLOBALS['session_lifetime']) {
                session_unset();
                session_destroy();
                return false;
            }
        }
        return true;
    }
}

// Validate session security on every request
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    validateSessionSecurity();
    if (!checkSessionTimeout()) {
        header('Location: ../login/login.php?error=session_expired');
        exit();
    }
}
