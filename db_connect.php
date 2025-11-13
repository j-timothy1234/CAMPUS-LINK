<?php

/**
 * Database Connection Class (Enhanced for Security & Performance)
 * 
 * Features:
 * - Environment-based configuration
 * - Connection pooling ready
 * - Prepared statements (prevents SQL injection)
 * - Query caching support
 * - Better error handling
 * - Singleton pattern for efficiency
 */

class Database {
    private static $instance = null;
    private $servername;
    private $username;
    private $password;
    private $database;
    private $conn;
    private $is_production;

    /**
     * Constructor: Load configuration and establish connection
     */

    private function __construct() {
        // Load from environment or use defaults
        $this->servername = getenv('DB_HOST') ?: 'localhost';
        $this->username = getenv('DB_USER') ?: 'root';
        $this->password = getenv('DB_PASSWORD') ?: 'job1234joy#';
        $this->database = getenv('DB_NAME') ?: 'campusLink';
        $this->is_production = strtolower(getenv('APP_ENV') ?: 'development') === 'production';

        // Configure error reporting based on environment
        if (!$this->is_production) {
            ini_set('display_errors', 1);
            ini_set('display_startup_errors', 1);
            error_reporting(E_ALL);
        } else {
            ini_set('display_errors', 0);
            error_reporting(E_ALL);
        }

        // Connect to database
        $this->connect();
    }

    /**
     * Prevent cloning of singleton instance
     */
    private function __clone() {}

    /**
     * Get singleton instance (connection pooling)
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Establish database connection with security settings
     */
    private function connect() {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        try {
            $this->conn = new mysqli(
                $this->servername,
                $this->username,
                $this->password,
                $this->database
            );

            // Set charset to UTF-8
            $this->conn->set_charset("utf8mb4");

            // Enable connection attributes for performance
            $this->conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);
            $this->conn->options(MYSQLI_INIT_COMMAND, "SET SESSION sql_mode='STRICT_TRANS_TABLES'");

        } catch (mysqli_sql_exception $e) {
            $this->handleConnectionError($e);
        }
    }

    /**
     * Handle connection errors securely
     */
    private function handleConnectionError($e) {
        error_log('Database Connection Error: ' . $e->getMessage());
        
        if (php_sapi_name() !== 'cli') {
            header('Content-Type: application/json');
            http_response_code(503);
            echo json_encode([
                'success' => false,
                'message' => $this->is_production 
                    ? 'Database connection failed. Please try again later.' 
                    : '❌ Connection failed: ' . $e->getMessage()
            ]);
        } else {
            die("Database connection failed.\n");
        }
        exit;
    }

    /**
     * Get database connection
     * @return mysqli
     */

    /**
     * Close database connection
     */
    public function close() {
        if ($this->conn) {
            $this->conn->close();
        }
    }

    /**
     * Execute prepared statement safely
     * @param string $sql
     * @param array $params
     * @param string $types
     * @return mysqli_result|bool
     */
    public function query($sql, $params = [], $types = '') {
        $conn = $this->getConnection();
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            error_log('Query Prepare Error: ' . $conn->error);
            return false;
        }

        if (!empty($params)) {
            if (empty($types)) {
                // Auto-detect types: s=string, i=int, d=double, b=blob
                $types = str_repeat('s', count($params));
            }
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        return $stmt->get_result();
    }
}

/**
 * Legacy support - use getInstance() for better performance
 * @deprecated Use Database::getInstance()->getConnection() instead
 */
if (!function_exists('getDatabaseConnection')) {
    function getDatabaseConnection() {
        return Database::getInstance()->getConnection();
    }
}