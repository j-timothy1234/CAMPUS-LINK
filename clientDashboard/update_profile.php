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

$csrf = $_POST['csrf_token'] ?? '';

// CSRF verification
if (empty($csrf) || !hash_equals($_SESSION['csrf_token'] ?? '', $csrf)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid session token (CSRF) - please reload the page and try again.']);
    exit();
}

// Basic validation
if (empty($username) || empty($email)) {
    echo json_encode(['error' => 'Username and email are required']);
    exit();
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid email address format']);
    exit();
}

// Check email uniqueness (no other client should have this email)
$checkStmt = $conn->prepare('SELECT Client_ID FROM clients WHERE Email = ? AND Client_ID <> ? LIMIT 1');
if ($checkStmt) {
    $checkStmt->bind_param('ss', $email, $client_id);
    $checkStmt->execute();
    $res = $checkStmt->get_result();
    if ($res && $res->num_rows > 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Email already in use by another account']);
        exit();
    }
}

$profile_photo_path = $_SESSION['profile_photo'] ?? 'images/default_profile.png';
$old_photo = $profile_photo_path;

// Handle file upload if present with validation and thumbnailing
if (!empty($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = __DIR__ . '/../upload_client';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            http_response_code(500);
            echo json_encode(['error' => 'Unable to create upload directory.']);
            exit();
        }
    }

    $tmpPath = $_FILES['profile_photo']['tmp_name'];
    $originalName = $_FILES['profile_photo']['name'];
    $size = $_FILES['profile_photo']['size'];

    // Validate file size (<= 5MB)
    $maxSize = 5 * 1024 * 1024;
    if ($size > $maxSize) {
        http_response_code(400);
        echo json_encode(['error' => 'File too large. Max allowed size is 5 MB.']);
        exit();
    }

    // Validate MIME type
    if (!function_exists('finfo_open')) {
        http_response_code(500);
        echo json_encode(['error' => 'Server missing required fileinfo extension.']);
        exit();
    }
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $tmpPath);
    finfo_close($finfo);
    $allowed = ['image/jpeg' => 'jpg', 'image/jpg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif'];
    if (!isset($allowed[$mime])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid file type. Only JPG, PNG and GIF allowed.']);
        exit();
    }

    $ext = $allowed[$mime];
    $safeUsername = preg_replace('/[^A-Za-z0-9_\-]/', '_', $username);
    $newFilename = $safeUsername . '.' . $ext;
    $destPath = $uploadDir . DIRECTORY_SEPARATOR . $newFilename;

    if (!move_uploaded_file($tmpPath, $destPath)) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to save uploaded file.']);
        exit();
    }

    // Create thumbnail (max 200x200) using GD
    $thumbFilename = $safeUsername . '_thumb.' . $ext;
    $thumbPath = $uploadDir . DIRECTORY_SEPARATOR . $thumbFilename;
    $sizeInfo = @getimagesize($destPath);
    if ($sizeInfo === false) {
        // invalid image
        @unlink($destPath);
        http_response_code(400);
        echo json_encode(['error' => 'Uploaded file is not a valid image.']);
        exit();
    }
    list($width, $height) = $sizeInfo;
    $maxDim = 200;
    $ratio = min($maxDim / $width, $maxDim / $height, 1);
    $newW = (int)($width * $ratio);
    $newH = (int)($height * $ratio);

    // Create image resource from file
    switch ($ext) {
        case 'jpg':
            $srcImg = @imagecreatefromjpeg($destPath);
            break;
        case 'png':
            $srcImg = @imagecreatefrompng($destPath);
            break;
        case 'gif':
            $srcImg = @imagecreatefromgif($destPath);
            break;
        default:
            $srcImg = null;
    }

    if ($srcImg) {
        $thumbImg = imagecreatetruecolor($newW, $newH);
        // Preserve transparency for PNG/GIF
        if (in_array($ext, ['png','gif'])) {
            imagecolortransparent($thumbImg, imagecolorallocatealpha($thumbImg, 0, 0, 0, 127));
            imagealphablending($thumbImg, false);
            imagesavealpha($thumbImg, true);
        }
        imagecopyresampled($thumbImg, $srcImg, 0, 0, 0, 0, $newW, $newH, $width, $height);

        // Save thumbnail
        switch ($ext) {
            case 'jpg': imagejpeg($thumbImg, $thumbPath, 82); break;
            case 'png': imagepng($thumbImg, $thumbPath); break;
            case 'gif': imagegif($thumbImg, $thumbPath); break;
        }

        imagedestroy($srcImg);
        imagedestroy($thumbImg);
    }

    // store web-accessible path
    $profile_photo_path = 'upload_client/' . $newFilename;
    $thumb_web = 'upload_client/' . $thumbFilename;

    // Cleanup old files if they exist and are not the default
    if (!empty($old_photo) && strpos($old_photo, 'upload_client/') === 0) {
        $oldFull = __DIR__ . '/../' . $old_photo;
        if (file_exists($oldFull)) {
            @unlink($oldFull);
        }
        // try removing thumb with _thumb
        $oldThumbFull = preg_replace('/(\.[^.]+)$/', '_thumb$1', $oldFull);
        if (file_exists($oldThumbFull)) {
            @unlink($oldThumbFull);
        }
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
if (isset($thumb_web)) {
    $_SESSION['header_photo'] = $thumb_web;
}

echo json_encode(['success' => true, 'message' => 'Profile updated successfully!', 'newPhotoPath' => $profile_photo_path]);
exit();

?>
