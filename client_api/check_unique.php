<?php
/**
 * client_api/check_unique.php
 *
 * GET parameters supported:
 * - email
 * - username
 * - phone_number
 *
 * Returns JSON { ok: true, available: true/false, field: 'email' }
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/utils.php';

// Only GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_resp(['ok' => false, 'message' => 'Method not allowed'], 405);
}

$email = isset($_GET['email']) ? trim($_GET['email']) : null;
$username = isset($_GET['username']) ? trim($_GET['username']) : null;
$phone = isset($_GET['phone_number']) ? trim($_GET['phone_number']) : null;

if (!$email && !$username && !$phone) {
    json_resp(['ok' => false, 'message' => 'Provide email or username or phone_number'], 400);
}

$db = new Database();
$conn = $db->getConnection();

// Build query dynamically
$clauses = [];
$types = '';
$values = [];
if ($email) { $clauses[] = 'LOWER(Email) = LOWER(?)'; $types .= 's'; $values[] = $email; }
if ($username) { $clauses[] = 'LOWER(Username) = LOWER(?)'; $types .= 's'; $values[] = $username; }
if ($phone) { $clauses[] = 'Phone_Number = ?'; $types .= 's'; $values[] = $phone; }

$sql = 'SELECT Email, Username, Phone_Number FROM clients WHERE ' . implode(' OR ', $clauses) . ' LIMIT 1';
$stmt = $conn->prepare($sql);
if (!$stmt) json_resp(['ok'=>false,'message'=>'Database error (prepare)'],500);

// Bind params dynamically
if (!empty($values)) {
    $stmt->bind_param($types, ...$values);
}

$stmt->execute();
$res = $stmt->get_result();
if ($res && $res->num_rows > 0) {
    $row = $res->fetch_assoc();
    // Determine which field matched
    $matched = [];
    if ($email && strtolower($row['Email']) === strtolower($email)) $matched[] = 'email';
    if ($username && strtolower($row['Username']) === strtolower($username)) $matched[] = 'username';
    if ($phone && $row['Phone_Number'] === $phone) $matched[] = 'phone_number';
    json_resp(['ok' => true, 'available' => false, 'matched' => $matched], 200);
}

json_resp(['ok' => true, 'available' => true], 200);
