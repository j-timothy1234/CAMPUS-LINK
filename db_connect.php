<?php

// Enable error reporting in dev (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$servername = "localhost";
$username = "root";
$password = "";
$database = "campusLink";

// Use MySQLi exceptions for cleaner error handling
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {

    $conn = new mysqli($servername, $username, $password, $database);
    $conn->set_charset("utf8mb4");

} catch (mysqli_sql_exception $e) {

    // Output JSON error for AJAX requests, or plain text for CLI
    if (php_sapi_name() !== 'cli') {

        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => '❌ Connection failed: ' . $e->getMessage()]);
        exit;

    } else {

        die("❌ Connection failed: " . $e->getMessage());

    }

}