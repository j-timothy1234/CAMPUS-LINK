<?php

/**
 * Security Manager Class
 * 
 * Handles:
 * - Rate limiting
 * - CSRF token generation and validation
 * - Input sanitization
 * - SQL injection prevention
 * - XSS prevention
 */

class SecurityManager {
    private static $maxAttempts = 5;
    private static $attemptWindow = 900; // 15 minutes
    private static $sessionDir = '/tmp/campuslink_sessions';

    /**
     * Initialize security manager (create necessary directories)
     */
    public static function init() {
        if (!is_dir(self::$sessionDir)) {
            mkdir(self::$sessionDir, 0700, true);
        }
    }

    /**
     * Check if user is rate limited
     * @param string $identifier (email/username/IP)
     * @return bool true if rate limited, false otherwise
     */
    public static function isRateLimited($identifier) {
        $filename = self::$sessionDir . '/' . md5($identifier) . '.log';
        $maxAttempts = (int)(getenv('MAX_LOGIN_ATTEMPTS') ?: self::$maxAttempts);
        $window = (int)(getenv('LOGIN_ATTEMPT_WINDOW') ?: self::$attemptWindow);

        // Read attempt log
        if (file_exists($filename)) {
            $data = json_decode(file_get_contents($filename), true);
            $now = time();

            // Remove old attempts outside the window
            $data['attempts'] = array_filter($data['attempts'], function($time) use ($now, $window) {
                return ($now - $time) < $window;
            });

            // Check if rate limited
            if (count($data['attempts']) >= $maxAttempts) {
                return true;
            }

            // Record new attempt
            $data['attempts'][] = $now;
        } else {
            $data = ['attempts' => [time()]];
        }

        file_put_contents($filename, json_encode($data), LOCK_EX);
        return false;
    }

    /**
     * Clear rate limit for a user (on successful login)
     * @param string $identifier
     */
    public static function clearRateLimit($identifier) {
        $filename = self::$sessionDir . '/' . md5($identifier) . '.log';
        if (file_exists($filename)) {
            unlink($filename);
        }
    }

    /**
     * Generate CSRF token
     * @return string
     */
    public static function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Validate CSRF token
     * @param string $token
     * @return bool
     */
    public static function validateCSRFToken($token) {
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token ?? '');
    }

    /**
     * Sanitize user input
     * @param string $input
     * @return string
     */
    public static function sanitizeInput($input) {
        return trim(htmlspecialchars(stripslashes($input), ENT_QUOTES, 'UTF-8'));
    }

    /**
     * Validate email format
     * @param string $email
     * @return bool
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate password strength
     * @param string $password
     * @return array ['valid' => bool, 'errors' => array]
     */
    public static function validatePasswordStrength($password) {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        }
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        }
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number';
        }
        if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
            $errors[] = 'Password must contain at least one special character';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Hash password securely
     * @param string $password
     * @return string
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    /**
     * Verify password
     * @param string $password
     * @param string $hash
     * @return bool
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }

    /**
     * Log security event
     * @param string $event
     * @param string $details
     * @param string $level (INFO, WARNING, ERROR)
     */
    public static function logSecurityEvent($event, $details = '', $level = 'INFO') {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
        $timestamp = date('Y-m-d H:i:s');
        $message = "[$timestamp] [$level] [IP: $ip] Event: $event | Details: $details\n";
        
        $logFile = __DIR__ . '/../../logs/security.log';
        error_log($message, 3, $logFile);
    }

    /**
     * Validate file upload
     * @param array $file
     * @param array $allowedTypes
     * @param int $maxSize
     * @return array ['valid' => bool, 'error' => string]
     */
    public static function validateFileUpload($file, $allowedTypes = [], $maxSize = 5242880) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['valid' => false, 'error' => 'File upload failed'];
        }

        if ($file['size'] > $maxSize) {
            return ['valid' => false, 'error' => 'File size exceeds maximum allowed'];
        }

        if (!empty($allowedTypes)) {
            $mime = mime_content_type($file['tmp_name']);
            if (!in_array($mime, $allowedTypes)) {
                return ['valid' => false, 'error' => 'File type not allowed'];
            }
        }

        return ['valid' => true, 'error' => null];
    }

    /**
     * Escape SQL string (use prepared statements instead!)
     * @param string $string
     * @param mysqli $conn
     * @return string
     */
    public static function escapeSQLString($string, $conn) {
        return $conn->real_escape_string($string);
    }
}

// Initialize security manager
SecurityManager::init();
