<?php
/**
 * Profile Picture Upload Handler for Client Dashboard
 * File: upload_profile_client.php
 */

// Include session configuration
require_once __DIR__ . '/../sessions/session_config.php';

// Set JSON response header
header('Content-Type: application/json');

// Disable error display in JSON response (enable logging instead)
ini_set('display_errors', 0);
error_reporting(E_ALL);

/**
 * Send JSON response and exit
 */
function sendResponse($success, $message, $data = []) {
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message
    ], $data));
    exit;
}

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['user_type'] !== 'client') {
    sendResponse(false, 'Unauthorized access. Please log in.');
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, 'Invalid request method.');
}

$client_id = $_SESSION['client_id'];
$upload_dir = __DIR__ . '/../upload_client/';

// Create upload directory if it doesn't exist
if (!file_exists($upload_dir)) {
    if (!mkdir($upload_dir, 0755, true)) {
        sendResponse(false, 'Failed to create upload directory.');
    }
}

// Check if file was uploaded
if (!isset($_FILES['profile_photo']) || empty($_FILES['profile_photo']['name'])) {
    sendResponse(false, 'No file uploaded. Please select an image.');
}

$file = $_FILES['profile_photo'];

// Check for upload errors
if ($file['error'] !== UPLOAD_ERR_OK) {
    $errorMessages = [
        UPLOAD_ERR_INI_SIZE => 'File exceeds maximum upload size.',
        UPLOAD_ERR_FORM_SIZE => 'File exceeds form maximum size.',
        UPLOAD_ERR_PARTIAL => 'File was only partially uploaded.',
        UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder.',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
        UPLOAD_ERR_EXTENSION => 'File upload stopped by extension.'
    ];
    
    $errorMsg = isset($errorMessages[$file['error']]) 
        ? $errorMessages[$file['error']] 
        : 'Unknown upload error occurred.';
    
    sendResponse(false, $errorMsg);
}

// Validate file type using MIME type
$allowed_mime_types = [
    'image/jpeg',
    'image/jpg', 
    'image/pjpeg',
    'image/png',
    'image/gif',
    'image/webp'
];

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mime_type, $allowed_mime_types)) {
    sendResponse(false, 'Invalid file type. Only JPG, PNG, GIF, and WebP images
    are allowed.');
}

// Validate file extension
$allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

if (!in_array($file_extension, $allowed_extensions)) {
    sendResponse(false, 'Invalid file extension.');
}

// Validate file size (5MB maximum)
$max_file_size = 5 * 1024 * 1024; // 5MB in bytes
if ($file['size'] > $max_file_size) {
    sendResponse(false, 'Photo size exceeds 5MB limit. Please upload a smaller
    image.');
}

// Validate image dimensions (ensures it's actually an image)
$image_info = @getimagesize($file['tmp_name']);
if ($image_info === false) {
    sendResponse(false, 'Invalid image file.');
}

// Generate unique filename
$new_filename = 'client_' . $client_id . '_' . uniqid() . '_' . time() . '.' . $file_extension;
$upload_path = $upload_dir . $new_filename;

// Relative path for database storage
$relative_path = '/../upload_client/' . $new_filename;

// Database connection (adjust path to your database connection file)
require_once __DIR__ . '/../db_connection.php';

// Get old profile picture path before update
$stmt = $conn->prepare("SELECT profile_photo FROM clients WHERE client_id = ?");
if (!$stmt) {
    sendResponse(false, 'Database error: ' . $conn->error);
}

$stmt->bind_param("i", $client_id);
$stmt->execute();
$result = $stmt->get_result();
$old_data = $result->fetch_assoc();
$old_profile_photo = $old_data['profile_photo'] ?? null;
$stmt->close();

// Move uploaded file to destination
if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
    sendResponse(false, 'Failed to save uploaded file. Please try again.');
}

// Optional: Create thumbnail (uncomment if you want to generate thumbnails)
/*
try {
    createThumbnail($upload_path, $file_extension);
} catch (Exception $e) {
    // Log error but don't fail the upload
    error_log("Thumbnail creation failed: " . $e->getMessage());
}
*/

// Update database with new profile picture path
$stmt = $conn->prepare("UPDATE clients SET profile_photo = ? WHERE client_id = ?");
if (!$stmt) {
    @unlink($upload_path); // Delete uploaded file
    sendResponse(false, 'Database error: ' . $conn->error);
}

$stmt->bind_param("si", $relative_path, $client_id);

if ($stmt->execute()) {
    // Update session variable
    $_SESSION['profile_photo'] = $relative_path;
    
    // Delete old profile picture file (if exists and not default)
    if ($old_profile_photo && 
        $old_profile_photo !== $relative_path && 
        !empty($old_profile_photo) &&
        strpos($old_profile_photo, '/../upload_client/') === 0 &&
        strpos($old_profile_photo, 'default') === false) {
        
        $old_file_path = __DIR__ . '/../upload_client/' . $old_profile_photo;
        if (file_exists($old_file_path)) {
            @unlink($old_file_path);
            
            // Also delete old thumbnail if exists
            $old_thumb = preg_replace('/(\.[^.]+)$/', '_thumb$1', $old_file_path);
            if (file_exists($old_thumb)) {
                @unlink($old_thumb);
            }
        }
    }
    
    $stmt->close();
    $conn->close();
    
    sendResponse(true, 'Profile picture updated successfully!', [
        'file_path' => $relative_path,
        'timestamp' => time()
    ]);
} else {
    // Database update failed, remove uploaded file
    @unlink($upload_path);
    
    $stmt->close();
    $conn->close();
    
    sendResponse(false, 'Failed to update database. Please try again.');
}

/**
 * Optional: Create thumbnail for profile picture
 * Uncomment the function call above to use this
 */
function createThumbnail($source_path, $extension) {
    $thumb_width = 150;
    $thumb_height = 150;
    
    // Create image resource based on type
    switch ($extension) {
        case 'jpg':
        case 'jpeg':
            $source = imagecreatefromjpeg($source_path);
            break;
        case 'png':
            $source = imagecreatefrompng($source_path);
            break;
        case 'gif':
            $source = imagecreatefromgif($source_path);
            break;
        case 'webp':
            $source = imagecreatefromwebp($source_path);
            break;
        default:
            return false;
    }
    
    if (!$source) {
        return false;
    }
    
    // Get original dimensions
    $orig_width = imagesx($source);
    $orig_height = imagesy($source);
    
    // Create thumbnail
    $thumb = imagecreatetruecolor($thumb_width, $thumb_height);
    
    // Preserve transparency for PNG and GIF
    if ($extension === 'png' || $extension === 'gif') {
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);
        $transparent = imagecolorallocatealpha($thumb, 255, 255, 255, 127);
        imagefilledrectangle($thumb, 0, 0, $thumb_width, $thumb_height, $transparent);
    }
    
    // Resize
    imagecopyresampled($thumb, $source, 0, 0, 0, 0, $thumb_width, $thumb_height, $orig_width, $orig_height);
    
    // Save thumbnail
    $thumb_path = preg_replace('/(\.[^.]+)$/', '_thumb$1', $source_path);
    
    switch ($extension) {
        case 'jpg':
        case 'jpeg':
            imagejpeg($thumb, $thumb_path, 85);
            break;
        case 'png':
            imagepng($thumb, $thumb_path, 9);
            break;
        case 'gif':
            imagegif($thumb, $thumb_path);
            break;
        case 'webp':
            imagewebp($thumb, $thumb_path, 85);
            break;
    }
    
    // Free memory
    imagedestroy($source);
    imagedestroy($thumb);
    
    return true;
}
