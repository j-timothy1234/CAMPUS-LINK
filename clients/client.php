<?php
// Include database connection
require_once __DIR__ . '/../db_connect.php';

// Enable error reporting for development (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set response header to JSON
header('Content-Type: application/json');

// Helper function to generate Client_ID in format CL_0001, CL_0002, ...
function generateClientID($conn) {
    $result = $conn->query("SELECT MAX(id) AS max_id FROM clients");
    $row = $result->fetch_assoc();
    $next_id = isset($row['max_id']) ? intval($row['max_id']) + 1 : 1;
    return 'CL_' . str_pad($next_id, 4, '0', STR_PAD_LEFT);
}

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and sanitize inputs
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $password = $_POST['password'] ?? '';

    // Server-side validation (for security)
    if (!preg_match("/^[A-Za-z][A-Za-z\s\-']*[A-Za-z]$/", $username)) {
        echo json_encode(['success' => false, 'message' => 'Invalid username.']);
        exit;
    }

    if (!preg_match('/^[a-zA-Z0-9._%+-]+@gmail\.com$/', $email)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email.']);
        exit;
    }

    if (!preg_match('/^\d{10,}$/', $phone_number)) {
        echo json_encode(['success' => false, 'message' => 'Invalid Phone number.']);
        exit;
    }

    if (!in_array($gender, ['Male', 'Female'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid gender.']);
        exit;
    }

    if (!preg_match('/^.*(?=.{8,})(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).*$/', $password)) {
        echo json_encode(['success' => false, 'message' => 'Password does not meet requirements.']);
        exit;
    }

    // Hash the password securely
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Connect to database
    $db = new Database();
    $conn = $db->conn;

    // Generate unique Client_ID
    $client_id = generateClientID($conn);

    // Prepare and execute insert statement
    try {

        $stmt = $conn->prepare("INSERT INTO clients (Client_ID, Username, Email,
        Phone_Number, Gender, Password) VALUES (?, ?, ?, ?, ?, ?)");

        $stmt->bind_param("ssssss", $client_id,
        $username, $email, $phone_number, $gender, $hashedPassword);

        $stmt->execute();

        echo json_encode(['success' => true]);

    } catch (mysqli_sql_exception $e) {
        // Handle duplicate email or other DB errors
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    } finally {
        $db->close();
    }
} else {
    // Only allow POST
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}