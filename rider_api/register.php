<?php
/**
 * rider_api/register.php
 *
 * Handles rider registration via multipart/form-data. Validates input, checks duplicates,
 * saves uploaded photo, inserts rider record, creates session and returns JSON with redirect.
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
header('Access-Control-Allow-Methods: OPTIONS, POST');

require_once __DIR__ . '/utils.php';
require_once __DIR__ . '/../sessions/session_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_resp(['ok'=>false,'message'=>'Method not allowed'],405);

$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$gender = trim($_POST['gender'] ?? '');
$plate = trim($_POST['plate'] ?? '');
$residence = trim($_POST['residence'] ?? '');
$password = $_POST['password'] ?? '';
$photo = $_FILES['photo'] ?? null;

// Validate
if (!validate_username($username)) json_resp(['ok'=>false,'message'=>'Invalid username'],400);
if (!validate_email($email)) json_resp(['ok'=>false,'message'=>'Invalid email (must be @gmail.com)'],400);
if (!validate_phone($phone)) json_resp(['ok'=>false,'message'=>'Invalid phone number'],400);
if (!in_array(ucfirst(strtolower($gender)), ['Male','Female'])) json_resp(['ok'=>false,'message'=>'Invalid gender'],400);
if (!validate_plate($plate)) json_resp(['ok'=>false,'message'=>'Invalid plate number'],400);
if (empty($residence)) json_resp(['ok'=>false,'message'=>'Residence is required'],400);
if (!validate_password($password)) json_resp(['ok'=>false,'message'=>'Password does not meet requirements'],400);
if (!$photo || $photo['error'] !== UPLOAD_ERR_OK) json_resp(['ok'=>false,'message'=>'Photo upload required'],400);

if ($photo['size'] > 5 * 1024 * 1024) json_resp(['ok'=>false,'message'=>'Photo too large'],400);
$allowed = ['image/jpeg','image/png','image/gif','image/jpg'];
$ftype = mime_content_type($photo['tmp_name']);
if (!in_array($ftype, $allowed)) json_resp(['ok'=>false,'message'=>'Unsupported photo type'],400);

$db = new Database();
$conn = $db->getConnection();

// Duplicates
$dupSql = "SELECT Rider_ID, Username, Email, Phone_Number, Motorcycle_Plate_Number FROM riders WHERE LOWER(Username)=LOWER(?) OR LOWER(Email)=LOWER(?) OR Phone_Number=? OR Motorcycle_Plate_Number=? LIMIT 1";
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
    if ($row['Motorcycle_Plate_Number'] == $plate) $conflict[] = 'plate';
    json_resp(['ok'=>false,'message'=>'Duplicate data','conflict'=>$conflict],409);
}

// Save photo
$uploadDir = __DIR__ . '/../upload_rider';
if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
$clean_username = preg_replace('/[^a-zA-Z0-9_-]/', '_', $username);
$ext = pathinfo($photo['name'], PATHINFO_EXTENSION);
$photoName = $clean_username . '_profile.' . $ext;
$photoPath = $uploadDir . '/' . $photoName;
if (!move_uploaded_file($photo['tmp_name'], $photoPath)) json_resp(['ok'=>false,'message'=>'Failed to save photo'],500);

// Store relative web path for session (so browser can fetch it)
$webPhotoPath = '../upload_rider/' . $photoName;

// Insert
$rider_id = generateRiderID($conn);
$hashed = password_hash($password, PASSWORD_DEFAULT);
$insertSql = "INSERT INTO riders (Rider_ID, Username, Email, Phone_Number, Gender, Profile_Photo, Motorcycle_Plate_Number, Residence, Password) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
$ins = $conn->prepare($insertSql);
if (!$ins) json_resp(['ok'=>false,'message'=>'Database error (prepare insert)'],500);
$ins->bind_param('sssisssss', $rider_id, $username, $email, $phone, $gender, $webPhotoPath, $plate, $residence, $hashed);

try { $ins->execute(); } catch (mysqli_sql_exception $e) { json_resp(['ok'=>false,'message'=>'Database error: '.$e->getMessage()],500); }

// Create session
session_regenerate_id(true);
$_SESSION['user_type'] = 'rider';
$_SESSION['rider_id'] = $rider_id;
$_SESSION['username'] = $username;
$_SESSION['email'] = $email;
$_SESSION['profile_photo'] = $webPhotoPath;
$_SESSION['loggedin'] = true;
$_SESSION['login_time'] = time();

json_resp(['ok'=>true,'message'=>'Rider registered','redirect'=>'../riderDashboard/riderDashboard.php'],201);
