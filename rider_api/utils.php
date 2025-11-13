<?php
/**
 * rider_api/utils.php
 *
 * Helper functions used by rider_api endpoints: validation, Rider_ID generation, json response.
 */

require_once __DIR__ . '/../db_connect.php';

function json_resp($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

function validate_username($username) {
    return preg_match("/^[A-Za-z][A-Za-z\\s\\-']*[A-Za-z0-9]$/", $username);
}

function validate_email($email) {
    return preg_match('/^[a-zA-Z0-9._%+-]+@gmail\.com$/', $email);
}

function validate_phone($phone) {
    return preg_match('/^\d{9,15}$/', $phone);
}

function validate_plate($plate) {
    // Accepts formats like UAA 000M, UA 000AA
    return preg_match('/^U[A-Z]{1,2}\s\d{3}[A-Z]{1,2}$/', strtoupper($plate));
}

function validate_password($password) {
    return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $password);
}

function generateRiderID($conn) {
    try {
        $res = $conn->query("SELECT MAX(CAST(SUBSTRING(Rider_ID, 2) AS UNSIGNED)) as max_id FROM riders");
        if ($res) {
            $row = $res->fetch_assoc();
            $next = isset($row['max_id']) ? intval($row['max_id']) + 1 : 1;
            return 'R' . str_pad($next, 6, '0', STR_PAD_LEFT);
        }
    } catch (Exception $e) {
        // fallback
    }
    return 'R' . date('YmdHis');
}
