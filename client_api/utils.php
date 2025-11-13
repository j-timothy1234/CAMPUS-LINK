<?php
/**
 * client_api/utils.php
 *
 * Helper functions for client_api endpoints: validation, ID generation, JSON responses.
 */

require_once __DIR__ . '/../db_connect.php';

// JSON response helper
function json_resp($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

// Validate username: letters, spaces, hyphens, apostrophes, must start with letter
function validate_username($username) {
    return preg_match("/^[A-Za-z][A-Za-z\s\-']*[A-Za-z0-9]$/", $username);
}

// Validate email (project expected @gmail.com) — adjust as needed
function validate_email($email) {
    return preg_match('/^[a-z0-9._%+-]+@gmail\.com$/', $email);
}

// Validate phone: digits only, at least 10
function validate_phone($phone) {
    return preg_match('/^\d{10,}$/', $phone);
}

// Validate password: min 8 chars, upper, lower, number, special
function validate_password($password) {
    return preg_match('/^.*(?=.{8,})(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).*$/', $password);
}

// Generate Client_ID in format CL_0001 using existing integer id if present
function generateClientID($conn) {
    // Try to use the numeric auto-increment column if exists
    try {
        $result = $conn->query("SELECT MAX(id) AS max_id FROM clients");
        if ($result) {
            $row = $result->fetch_assoc();
            $next_id = isset($row['max_id']) ? intval($row['max_id']) + 1 : 1;
            return 'CL_' . str_pad($next_id, 4, '0', STR_PAD_LEFT);
        }
    } catch (Exception $e) {
        // Fallback: use timestamp-based id (less pretty but unique)
    }
    return 'CL_' . date('YmdHis');
}
