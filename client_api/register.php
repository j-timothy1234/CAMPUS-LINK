<?php
/**
 * client_api/register.php
 *
 * POST: Register a new client. Accepts JSON or form data.
 *
 * Behaviour:
 * - Validates input fields (username, email, phone_number, gender, password)
 * - Checks database for existing username/email/phone and returns 409 if found
 * - Inserts new client row with hashed password
 * - Creates PHP session (same variables used by login/auth.php) and returns JSON with redirect
 *
 * Note: In production ensure HTTPS and restrict CORS origins.
 */

header('Access-Control-Allow-Origin: *'); // tighten this in production
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
header('Access-Control-Allow-Methods: OPTIONS, POST');

require_once __DIR__ . '/utils.php';
require_once __DIR__ . '/../sessions/session_config.php';

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Only POST allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_resp(['ok' => false, 'message' => 'Method not allowed'], 405);
}

// Parse input (JSON or form)
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (stripos($contentType, 'application/json') !== false) {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true) ?: [];
} else {
    $data = $_POST;
}

$username = trim($data['username'] ?? '');
$email = trim($data['email'] ?? '');
$phone_number = trim($data['phone_number'] ?? '');
$gender = trim($data['gender'] ?? '');
$password = $data['password'] ?? '';

// Validate inputs
if (!validate_username($username)) {
    json_resp(['ok' => false, 'message' => 'Invalid username'], 400);
}
if (!validate_email($email)) {
    json_resp(['ok' => false, 'message' => 'Invalid email (must be @gmail.com)'], 400);
}
if (!validate_phone($phone_number)) {
    json_resp(['ok' => false, 'message' => 'Invalid phone number'], 400);
}
if (!in_array($gender, ['Male', 'Female'])) {
    json_resp(['ok' => false, 'message' => 'Invalid gender'], 400);
}
if (!validate_password($password)) {
    json_resp(['ok' => false, 'message' => 'Password does not meet requirements'], 400);
}

// Connect to DB
$db = new Database();
$conn = $db->getConnection();

// Check for duplicates: email, username, phone
$dupStmt = $conn->prepare("SELECT Client_ID, Username, Email, Phone_Number FROM clients WHERE Email = ? OR Username = ? OR Phone_Number = ? LIMIT 1");
if (!$dupStmt) {
    json_resp(['ok' => false, 'message' => 'Database error (prepare)'], 500);
}
$dupStmt->bind_param('sss', $email, $username, $phone_number);
$dupStmt->execute();
$dupRes = $dupStmt->get_result();
if ($dupRes && $dupRes->num_rows > 0) {
    $row = $dupRes->fetch_assoc();
    // Determine which field conflicts
    $conflict = [];
    if (strtolower($row['Email']) === strtolower($email)) $conflict[] = 'email';
    if (strtolower($row['Username']) === strtolower($username)) $conflict[] = 'username';
    if ($row['Phone_Number'] === $phone_number) $conflict[] = 'phone_number';
    json_resp(['ok' => false, 'message' => 'Duplicate data', 'conflict' => $conflict], 409);
}

// Insert new client
$client_id = generateClientID($conn);
$hashed = password_hash($password, PASSWORD_DEFAULT);

$insertSql = "INSERT INTO clients (Client_ID, Username, Email, Phone_Number, Gender, Password) VALUES (?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($insertSql);
if (!$stmt) {
    json_resp(['ok' => false, 'message' => 'Database error (prepare insert)'], 500);
}
$stmt->bind_param('ssssss', $client_id, $username, $email, $phone_number, $gender, $hashed);

try {
    $stmt->execute();
} catch (mysqli_sql_exception $e) {
    json_resp(['ok' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
}

// Create session for the newly registered client (same keys as login/auth.php)
session_regenerate_id(true);
$_SESSION['user_type'] = 'client';
$_SESSION['client_id'] = $client_id;
$_SESSION['username'] = $username;
$_SESSION['email'] = $email;
$_SESSION['profile_photo'] = null;
$_SESSION['loggedin'] = true;
$_SESSION['login_time'] = time();

json_resp(['ok' => true, 'message' => 'Registration successful',
    'redirect' => '../clientDashboard/clientDashboard.php'], 201);
