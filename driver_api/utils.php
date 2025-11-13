<?php
/**
 * driver_api/utils.php
 *
 * Helper functions used by driver_api endpoints: validation, Driver_ID generation, json response.
 */

require_once __DIR__ . '/../db_connect.php';

// JSON response helper
function json_resp($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

// Validate username: letters, spaces, hyphens, apostrophes
function validate_username($username) {
    // Use double quotes so the apostrophe inside the character class doesn't break the string
    return preg_match("/^[A-Za-z][A-Za-z\\s\\-']*[A-Za-z0-9]$/", $username);
}

// Validate email (simple; project expects gmail)
function validate_email($email) {
    return preg_match('/^[a-zA-Z0-9._%+-]+@gmail\.com$/', $email);
}

// Validate phone: digits, length >=10
function validate_phone($phone) {
    return preg_match('/^\d{9,15}$/', $phone);
}

// Validate plate number (simple uppercase check)
function validate_plate($plate) {
    return preg_match('/^[A-Z0-9\s]{3,15}$/', strtoupper($plate));
}

// Validate password strength
function validate_password($password) {
    return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $password);
}

// Generate Driver_ID similar to existing logic: D000001
function generateDriverID($conn) {
    try {
        $res = $conn->query("SELECT MAX(CAST(SUBSTRING(Driver_ID, 2) AS UNSIGNED)) AS max_id FROM drivers");
        if ($res) {
            $row = $res->fetch_assoc();
            $next = isset($row['max_id']) ? intval($row['max_id']) + 1 : 1;
            return 'D' . str_pad($next, 6, '0', STR_PAD_LEFT);
        }
    } catch (Exception $e) {
        // fallback to timestamp-based ID
    }
    return 'D' . date('YmdHis');
}
