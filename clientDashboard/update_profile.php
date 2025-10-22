<?php
require_once __DIR__ . '/../sessions/session_config.php';
require_once __DIR__ . '/../db_connect.php';

// Only allow logged in clients
if (!isset($_SESSION['loggedin']) || $_SESSION['user_type'] !== 'client') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');

$db = new Database();
$conn = $db->getConnection();

$client_id = isset($_POST['client_id']) ? trim($_POST['client_id']) : '';
// Ensure the client is updating their own profile (Client_ID like CL_0001)
$session_client_id = isset($_SESSION['client_id']) ? (string)$_SESSION['client_id'] : '';
if ($client_id === '' || $client_id !== $session_client_id) {
    echo json_encode(['error' => 'Invalid client id']);
    exit();
}

$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$password = $_POST['password'] ?? '';

// Basic validation
if (empty($username) || empty($email)) {
    echo json_encode(['error' => 'Username and email are required']);
    exit();
}

$profile_photo_path = $_SESSION['profile_photo'] ?? 'images/default_profile.png';

// Handle file upload if present
if (!empty($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = __DIR__ . '/../upload_client';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $tmpPath = $_FILES['profile_photo']['tmp_name'];
    $ext = pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION);
    $safeUsername = preg_replace('/[^A-Za-z0-9_\-]/', '_', $username);
    $newFilename = $safeUsername . '.' . $ext;
    $destPath = $uploadDir . DIRECTORY_SEPARATOR . $newFilename;

    if (move_uploaded_file($tmpPath, $destPath)) {
        // store web-accessible path
        $profile_photo_path = 'upload_client/' . $newFilename;
    }
}

// Build SQL update
$fields = [];
$params = [];

$fields[] = 'Username = ?'; $params[] = $username;
$fields[] = 'Email = ?'; $params[] = $email;
$fields[] = 'Phone_Number = ?'; $params[] = $phone;
$fields[] = 'Profile_Photo = ?'; $params[] = $profile_photo_path;

if (!empty($password)) {
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $fields[] = 'Password = ?'; $params[] = $hashed;
}

// Client_ID is a string (e.g., CL_0001)
$params[] = $client_id; // for WHERE

$sql = 'UPDATE clients SET ' . implode(', ', $fields) . ' WHERE Client_ID = ?';
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    echo json_encode(['error' => 'DB prepare error']);
    exit();
}

// bind params dynamically
// All params are strings (Client_ID is a string like CL_0001)
$types = str_repeat('s', count($params));
$bind_names = [];
$bind_names[] = $types;
for ($i = 0; $i < count($params); $i++) {
    $bind_name = 'param' . $i;
    $$bind_name = $params[$i];
    $bind_names[] = &$$bind_name;
}
call_user_func_array([$stmt, 'bind_param'], $bind_names);

if (!$stmt->execute()) {
    echo json_encode(['error' => 'DB execute error']);
    exit();
}

// Update session variables
$_SESSION['username'] = $username;
$_SESSION['email'] = $email;
$_SESSION['phone'] = $phone;
$_SESSION['profile_photo'] = $profile_photo_path;

echo json_encode(['success' => true, 'redirect' => 'clientDashboard.php']);
exit();

?>
