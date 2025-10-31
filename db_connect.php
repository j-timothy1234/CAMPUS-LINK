<?php

// Enable error reporting in development (remove or comment out in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/**
 * Database connection class using MySQLi (OOP)
 * 
 * git commit -m "Updated project files"
 * git push origin main
 * 
 * git commit -m "Initial commit - upload all project files"
 * 
 *
 */

class Database {
    //Database Class Definition
    private $servername = "localhost";
    private $username = "root";
    private $password = "job1234joy#";
    private $database = "campusLink";
    public $conn; // Holds the MySQLi connection object

    /**code -r .
     * Developer: Reload Window
     */

    /**
     * Constructor: Establishes the database connection
     */

    public function __construct() {
        // Configure MySQLi to throw exceptions on errors, Constructor - Connection Setup
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        try {
            // Attempt to create a new MySQLi connection
            $this->conn = new mysqli(
                $this->servername,
                $this->username,
                $this->password,
                $this->database
            );
            // Set character set to UTF-8 for proper encoding
            $this->conn->set_charset("utf8mb4");
        } catch (mysqli_sql_exception $e) {
            // Handle connection errors
            if (php_sapi_name() !== 'cli') {
                // If not running from CLI, return a JSON error (for AJAX/API)
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => '❌ Connection failed: ' . $e->getMessage()
                ]);
                exit;
            } else {
                // If running from CLI, output plain text error
                die("❌ Connection failed: " . $e->getMessage());
            }
        }
    }

    /**
     * Returns the MySQLi connection object
     * @return mysqli
     */
    public function getConnection() {
        return $this->conn;
    }

    /**
     * Closes the database connection
     */
    public function close() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}

// Usage example:
// $db = new Database();
// $conn = $db->getConnection();