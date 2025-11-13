<?php
/**
 * driver_api/register.php
 *
 * Handles driver registration via multipart/form-data (to accept photo uploads).
 * Validates fields, checks duplicates, stores uploaded photo, inserts driver record,
 * creates session and returns JSON with redirect to driver dashboard.
 */

header('Access-Control-Allow-Origin: *'); // tighten in production
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
header('Access-Control-Allow-Methods: OPTIONS, POST');

require_once __DIR__ . '/utils.php';
require_once __DIR__ . '/../sessions/session_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_resp(['ok' => false, 'message' => 'Method not allowed'], 405);
}

// Expect multipart/form-data with fields and file 'photo'
$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$gender = trim($_POST['gender'] ?? '');
$plate = trim($_POST['plate'] ?? '');
$residence = trim($_POST['residence'] ?? '');
$password = $_POST['password'] ?? '';
$photo = $_FILES['photo'] ?? null;

// Validate inputs
if (!validate_username($username)) json_resp(['ok'=>false,'message'=>'Invalid username'],400);
if (!validate_email($email)) json_resp(['ok'=>false,'message'=>'Invalid email (must be @gmail.com)'],400);
if (!validate_phone($phone)) json_resp(['ok'=>false,'message'=>'Invalid phone number'],400);
if (!in_array(strtoupper($gender), ['MALE','FEMALE'])) json_resp(['ok'=>false,'message'=>'Invalid gender'],400);
if (!validate_plate($plate)) json_resp(['ok'=>false,'message'=>'Invalid plate number'],400);
if (empty($residence)) json_resp(['ok'=>false,'message'=>'Residence is required'],400);
if (!validate_password($password)) json_resp(['ok'=>false,'message'=>'Password does not meet requirements'],400);
if (!$photo || $photo['error'] !== UPLOAD_ERR_OK) json_resp(['ok'=>false,'message'=>'Photo upload required'],400);

// Photo checks
if ($photo['size'] > 5 * 1024 * 1024) json_resp(['ok'=>false,'message'=>'Photo too large'],400);
$allowed = ['image/jpeg','image/png','image/gif','image/jpg'];
$ftype = mime_content_type($photo['tmp_name']);
if (!in_array($ftype, $allowed)) json_resp(['ok'=>false,'message'=>'Unsupported photo type'],400);

// Connect DB
$db = new Database();
$conn = $db->getConnection();

// Check duplicates (email, username, phone, plate)
$dupSql = "SELECT Driver_ID, Username, Email, Phone_Number, Car_Plate_Number FROM drivers WHERE LOWER(Username)=LOWER(?) OR LOWER(Email)=LOWER(?) OR Phone_Number=? OR Car_Plate_Number=? LIMIT 1";
$dup = $conn->prepare($dupSql);
if (!$dup) json_resp(['ok'=>false,'message'=>'Database error (prepare)'],500);
$dup->bind_param('ssss', $username, $email, $phone, $plate);
$dup->execute();
$r = $dup->get_result();
if ($r && $r->num_rows > 0) {
    $row = $r->fetch_assoc();
    $conflict = [];
    if (strtolower($row['Username']) === strtolower($username)) $conflict[] = 'username';
    if (strtolower($row['Email']) === strtolower($email)) $conflict[] = 'email';
    if ($row['Phone_Number'] == $phone) $conflict[] = 'phone';
    if ($row['Car_Plate_Number'] == $plate) $conflict[] = 'plate';
    json_resp(['ok'=>false,'message'=>'Duplicate data','conflict'=>$conflict],409);
}

// Save the photo
$uploadDir = __DIR__ . '/../uploads_driver';
if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);

// Save photo with driver's username (extract file extension from original)
$fileExtension = pathinfo($photo['name'], PATHINFO_EXTENSION);
$photoName = $username . '_profile.' . $fileExtension;
$photoPath = $uploadDir . '/' . $photoName;

// If file exists (duplicate username), overwrite it or add timestamp
if (file_exists($photoPath)) {
    $photoName = $username . '_profile_' . time() . '.' . $fileExtension;
    $photoPath = $uploadDir . '/' . $photoName;
}

if (!move_uploaded_file($photo['tmp_name'], $photoPath)) {
    json_resp(['ok'=>false,'message'=>'Failed to save photo'],500);
}

// Store relative web path (for both database and session)
$webPhotoPath = '../uploads_driver/' . $photoName;

// Insert
$driver_id = generateDriverID($conn);
$hashed = password_hash($password, PASSWORD_DEFAULT);
$insertSql = "INSERT INTO drivers (Driver_ID, Username, Email, Phone_Number, Gender, Profile_Photo, Car_Plate_Number, Residence, Password) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
$ins = $conn->prepare($insertSql);
if (!$ins) json_resp(['ok'=>false,'message'=>'Database error (prepare insert)'],500);
$ins->bind_param('sssssssss', $driver_id, $username, $email, $phone, $gender, $webPhotoPath, $plate, $residence, $hashed);

try {
    $ins->execute();
} catch (mysqli_sql_exception $e) {
    json_resp(['ok'=>false,'message'=>'Database error: '.$e->getMessage()],500);
}

// Create session similar to existing login flow
session_regenerate_id(true);
$_SESSION['user_type'] = 'driver';
$_SESSION['driver_id'] = $driver_id;
$_SESSION['username'] = $username;
$_SESSION['email'] = $email;
$_SESSION['profile_photo'] = $webPhotoPath;
$_SESSION['loggedin'] = true;
$_SESSION['login_time'] = time();

json_resp(['ok'=>true,'message'=>'Driver registered','redirect'=>'../driverDashboard/driverDashboard.php'],201);
