<?php
// Include database connection
require_once __DIR__ . '/../db_connect.php';

// Enable maximum error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set response header to JSON
header('Content-Type: application/json');

// ----------------------------
// DRIVER REGISTRATION HANDLER
// ----------------------------

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
    try {
        // Get database connection
        $conn = (new Database())->getConnection();
        
        // Check connection
        if (!$conn) {
            throw new Exception("Database connection failed");
        }

        // Validate required fields
        $required_fields = ['username', 'email', 'phone', 'gender', 'plate', 'residence', 'password'];
        $missing_fields = [];
        
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                $missing_fields[] = $field;
            }
        }
        
        if (!empty($missing_fields)) {
            throw new Exception("Missing required fields: " . implode(', ', $missing_fields));
        }

        // Check if file was uploaded
        if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Please upload a valid profile photo");
        }

        // Collect and sanitize user inputs
        $username = $conn->real_escape_string($_POST['username']);
        $email = $conn->real_escape_string($_POST['email']);
        $phone_number = $conn->real_escape_string($_POST['phone']);
        $gender = $conn->real_escape_string($_POST['gender']);
        $car_plate_number = $conn->real_escape_string($_POST['plate']);
        $residence = $conn->real_escape_string($_POST['residence']);
        $password = $_POST['password'];
        $profile_photo = $_FILES['photo'];

        // Convert gender to uppercase to match ENUM
        $gender = strtoupper($gender);

        // Convert phone to integer
        $phone_number = (int)$phone_number;

        // Handle Photo Upload
        // Check file size (max 5MB)
        if ($profile_photo['size'] > 5 * 1024 * 1024) {
            throw new Exception("Photo exceeds 5MB limit.");
        }

        // Validate file type
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $file_type = mime_content_type($profile_photo['tmp_name']);
        
        if (!in_array($file_type, $allowed_types)) {
            throw new Exception("Only JPG, JPEG, PNG & GIF files are allowed.");
        }

        // Define upload directory and unique filename
        $uploadDir = "../uploads";
        if (!file_exists($uploadDir)) {
            if (!mkdir($uploadDir, 0777, true)) {
                throw new Exception("Failed to create upload directory.");
            }
        }

        // Create a unique name for the uploaded photo
        $photoName = uniqid("driver_") . "_" . basename($profile_photo["name"]);
        $photoPath = $uploadDir . "/" . $photoName;

        // Move file to uploads folder
        if (!move_uploaded_file($profile_photo["tmp_name"], $photoPath)) {
            throw new Exception("Failed to save uploaded photo.");
        }

        // Hash Password for Security
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Insert Data into Database
        $sql = "INSERT INTO drivers 
                (Username, Email, Phone_Number, Gender, Profile_Photo, Car_Plate_Number, Residence, Password) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("SQL preparation failed: " . $conn->error);
        }

        // Bind parameters - note: phone_number is integer (i), others are string (s)
        $stmt->bind_param("ssisssss", $username, $email, $phone_number, $gender, $photoPath, $car_plate_number, $residence, $hashedPassword);

        if ($stmt->execute()) {
            echo json_encode([
                "status" => "success", 
                "message" => "Driver registered successfully."
            ]);
        } else {
            throw new Exception("Database error: " . $stmt->error);
        }

        $stmt->close();
        $conn->close();

    } catch (Exception $e) {
        // Log error for debugging
        error_log("Driver Registration Error: " . $e->getMessage());
        
        // Send error response
        echo json_encode([
            "status" => "error", 
            "message" => $e->getMessage()
        ]);
    }

} else {
    echo json_encode([
        "status" => "error", 
        "message" => "Invalid request method."
    ]);
}
