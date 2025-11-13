<?php

/**
 * Example: Migrating rider_api/register.php to use sync
 * 
 * This shows how to update your registration forms to use
 * DatabaseWithSync instead of Database for automatic sync
 */

// Original (single server):
// require_once __DIR__ . '/../db_connect.php';
// $db = new Database();

// Updated (two-server sync):
require_once __DIR__ . '/../sync/DatabaseWithSync.php';
$db = new DatabaseWithSync();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['success' => false, 'message' => 'Only POST allowed']));
}

try {
    // Extract form data
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $gender = $_POST['gender'] ?? '';
    
    // Validation
    if (!$username || !$email || !$phone || !$password) {
        throw new Exception('All fields are required');
    }

    if ($password !== $password_confirm) {
        throw new Exception('Passwords do not match');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email address');
    }

    // Hash password
    $password_hash = password_hash($password, PASSWORD_BCRYPT);

    // Generate Rider ID
    $rider_id = 'RD_' . str_pad(time() % 100000, 5, '0', STR_PAD_LEFT);

    // Prepare data
    $data = [
        'Rider_ID' => $rider_id,
        'Username' => $username,
        'Email' => $email,
        'Phone_Number' => $phone,
        'Gender' => $gender,
        'Password' => $password_hash,
        'Profile_Photo' => 'default_profile.png'
    ];

    // Insert into database
    // This now AUTOMATICALLY queues sync to the other server!
    $result = $db->insert('riders', $data);

    // Success response
    echo json_encode([
        'success' => true,
        'message' => 'Registration successful!',
        'rider_id' => $rider_id,
        'note' => 'This data will automatically sync to the other server'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

?>
