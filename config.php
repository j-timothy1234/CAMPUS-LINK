<?php
/**
 * CAMPUS-LINK Configuration File
 * 
 * This file contains all configuration settings for the CAMPUS-LINK application.
 * It supports both single-server and dual-server (synchronized) setups.
 * 
 * ⚠ IMPORTANT: Both LAPTOP-A and LAPTOP-B must have identical MASTER_SERVER and SLAVE_SERVER values!
 */

// ============================================================================
// DATABASE CONFIGURATION
// ============================================================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'job1234joy#');
define('DB_NAME', 'campuslink');
define('DB_PORT', 3306);

// ============================================================================
// SERVER CONFIGURATION (for Two-Server Sync System)
// ============================================================================

/**
 * MASTER_SERVER: Primary server IP/URL
 * SLAVE_SERVER: Secondary server IP/URL
 * 
 * ⚠ CRITICAL: MUST BE IDENTICAL ON BOTH LAPTOPS!
 * 
 * Examples:
 * - Same WiFi: 'http://192.168.1.101' and 'http://192.168.1.102'
 * - Different Networks: Use ngrok URLs or domain names
 * 
 * UPDATE THESE WITH YOUR ACTUAL IPs:
 */

define('MASTER_SERVER', 'http://192.168.40.65');  // ← Update to LAPTOP-A IP
define('SLAVE_SERVER', 'http://192.168.40.196');   // ← Update to LAPTOP-B IP

/**
 * API Key for sync authentication
 * 
 * This key is required in all sync requests:
 * http://localhost/CAMPUS-LINK/api/sync_trigger.php?api_key=YOUR_KEY_HERE
 * 
 * Should be a random 50+ character string.
 * Keep this SECRET and identical on both servers!
 */
define('SYNC_API_KEY', 'XCpjKokX_UNccNrPWs60Ht%-JUQNru2n9D6i2K2o7U8M');

// ============================================================================
// APPLICATION CONFIGURATION
// ============================================================================

define('APP_NAME', 'CAMPUS-LINK');
define('APP_VERSION', '2.0.0');
define('APP_URL', 'http://localhost');

// Environment: development, staging, production
define('APP_ENV', 'development');

// Debug mode (show detailed errors)
define('DEBUG_MODE', true);

// ============================================================================
// SECURITY CONFIGURATION
// ============================================================================

// Session timeout in seconds (30 minutes)
define('SESSION_TIMEOUT', 1800);

// Session cookie settings
define('SESSION_COOKIE_HTTPONLY', true);
define('SESSION_COOKIE_SECURE', false);  // Set to true on production with HTTPS
define('SESSION_COOKIE_SAMESITE', 'Strict');

// Password hashing
define('BCRYPT_COST', 12);  // Bcrypt cost factor (10-12 recommended)

// Rate limiting: max login attempts per time window
define('MAX_LOGIN_ATTEMPTS', 5);
define('RATE_LIMIT_WINDOW', 900);  // 15 minutes in seconds

// ============================================================================
// FILE UPLOAD CONFIGURATION
// ============================================================================

// Maximum file upload size (in bytes)
define('MAX_UPLOAD_SIZE', 5242880);  // 5MB

// Allowed upload MIME types
define('ALLOWED_MIME_TYPES', ['image/jpeg', 'image/png', 'image/gif']);

// Upload directories (relative to document root)
define('RIDER_UPLOAD_DIR', 'upload_rider/');
define('DRIVER_UPLOAD_DIR', 'uploads_driver/');
define('CLIENT_UPLOAD_DIR', 'upload_client/');

// ============================================================================
// SYNC SYSTEM CONFIGURATION
// ============================================================================

// Sync queue processing interval (seconds)
define('SYNC_INTERVAL', 300);  // 5 minutes

// Maximum sync retry attempts
define('SYNC_MAX_RETRIES', 3);

// Sync timeout (seconds)
define('SYNC_TIMEOUT', 30);

// ============================================================================
// CORS CONFIGURATION
// ============================================================================

// Allowed CORS origins (comma-separated)
define('CORS_ORIGINS', 'http://localhost,http://192.168.40.65,http://192.168.40.196');

// ============================================================================
// LOGGING CONFIGURATION
// ============================================================================

// Log file paths (relative to project root)
define('SECURITY_LOG_FILE', '/logs/security.log');
define('ERROR_LOG_FILE', '/logs/error.log');
define('SYNC_LOG_FILE', '/logs/sync.log');

// Log level: DEBUG, INFO, WARNING, ERROR
define('LOG_LEVEL', 'INFO');

// ============================================================================
// EMAIL CONFIGURATION (if needed for password reset, etc.)
// ============================================================================

define('MAIL_FROM', 'noreply@campuslink.local');
define('MAIL_FROM_NAME', 'CAMPUS-LINK');
define('SMTP_HOST', 'localhost');
define('SMTP_PORT', 587);
define('SMTP_USER', '');
define('SMTP_PASS', '');

// ============================================================================
// TIMEZONE
// ============================================================================

define('TIMEZONE', 'Africa/Kampala');

// ============================================================================
// INITIALIZE APPLICATION
// ============================================================================

// Set timezone
date_default_timezone_set(TIMEZONE);

// Set error reporting
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', ERROR_LOG_FILE);
}

// Create required directories if they don't exist
$dirs = [
    RIDER_UPLOAD_DIR,
    DRIVER_UPLOAD_DIR,
    CLIENT_UPLOAD_DIR,
    '/logs/',
    '/tmp/',
];

foreach ($dirs as $dir) {
    $full_path = _DIR_ . '/' . $dir;
    if (!is_dir($full_path)) {
        @mkdir($full_path, 0755, true);
    }
}

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Get the current server URL
 * @return string
 */
function getServerUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $server = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $protocol . $server;
}

/**
 * Get the other server URL (for sync)
 * @return string
 */
function getOtherServerUrl() {
    $current = getServerUrl();
    if (strpos($current, '192.168.40.65') !== false || strpos($current, 'MASTER') !== false) {
        return SLAVE_SERVER;
    } else {
        return MASTER_SERVER;
    }
}

/**
 * Check if running on development environment
 * @return bool
 */
function isDevelopment() {
    return APP_ENV === 'development';
}

/**
 * Check if running on production environment
 * @return bool
 */
function isProduction() {
    return APP_ENV === 'production';
}

?>