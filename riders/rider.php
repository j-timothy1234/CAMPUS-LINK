<?php
// Include database connection
require_once __DIR__ . '/../db_connect.php';

// Enable maximum error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set response header to JSON
header('Content-Type: application/json');

// Create detailed debug log
file_put_contents('rider_debug.log', "=== RIDER REGISTRATION STARTED ===\n", FILE_APPEND);
file_put_contents('rider_debug.log', "Timestamp: " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);

// ----------------------------
// RIDER REGISTRATION HANDLER
// ----------------------------

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
    file_put_contents('rider_debug.log', "POST request received\n", FILE_APPEND);

    try {
        // Get database connection
        $conn = (new Database())->getConnection();
        
        // Check connection
        if (!$conn) {
            throw new Exception("Database connection failed");
        }
        file_put_contents('rider_debug.log', "Database connection successful\n", FILE_APPEND);

        // Enable auto-commit explicitly
        $conn->autocommit(TRUE);
        file_put_contents('rider_debug.log', "Auto-commit enabled\n", FILE_APPEND);

        // Log all received data
        file_put_contents('rider_debug.log', "POST data: " . print_r($_POST, true) . "\n", FILE_APPEND);
        file_put_contents('rider_debug.log', "FILES data: " . print_r($_FILES, true) . "\n", FILE_APPEND);

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
            throw new Exception("Please upload a valid profile photo. Error code: " . $_FILES['photo']['error']);
        }

        // Collect and sanitize user inputs
        $username = $conn->real_escape_string($_POST['username']);
        $email = $conn->real_escape_string($_POST['email']);
        $phone_number = $conn->real_escape_string($_POST['phone']);
        $gender = $conn->real_escape_string($_POST['gender']);
        $motorcycle_plate = $conn->real_escape_string($_POST['plate']);
        $residence = $conn->real_escape_string($_POST['residence']);
        $password = $_POST['password'];
        $profile_photo = $_FILES['photo'];

        file_put_contents('rider_debug.log', "Sanitized data:\n", FILE_APPEND);
        file_put_contents('rider_debug.log', "- Username: $username\n", FILE_APPEND);
        file_put_contents('rider_debug.log', "- Email: $email\n", FILE_APPEND);
        file_put_contents('rider_debug.log', "- Phone: $phone_number\n", FILE_APPEND);
        file_put_contents('rider_debug.log', "- Gender: $gender\n", FILE_APPEND);
        file_put_contents('rider_debug.log', "- Plate: $motorcycle_plate\n", FILE_APPEND);
        file_put_contents('rider_debug.log', "- Residence: $residence\n", FILE_APPEND);

        // Generate Rider_ID
        file_put_contents('rider_debug.log', "Generating Rider_ID...\n", FILE_APPEND);
        $rider_id_result = $conn->query("SELECT MAX(CAST(SUBSTRING(Rider_ID, 2) AS UNSIGNED)) as max_id FROM riders");
        if (!$rider_id_result) {
            file_put_contents('rider_debug.log', "Rider_ID query failed: " . $conn->error . "\n", FILE_APPEND);
            // Use default starting ID
            $next_id = 1;
        } else {
            $rider_id_row = $rider_id_result->fetch_assoc();
            $next_id = ($rider_id_row['max_id'] ?? 0) + 1;
        }
        $rider_id = "R" . str_pad($next_id, 6, "0", STR_PAD_LEFT);
        file_put_contents('rider_debug.log', "Generated Rider_ID: $rider_id\n", FILE_APPEND);

        // Convert phone to integer
        $phone_number = (int)$phone_number;
        file_put_contents('rider_debug.log', "Phone as integer: $phone_number\n", FILE_APPEND);

        // Handle Photo Upload
        file_put_contents('rider_debug.log', "Processing photo upload...\n", FILE_APPEND);
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
        $uploadDir = "../upload_rider";
        if (!file_exists($uploadDir)) {
            file_put_contents('rider_debug.log', "Creating upload directory: $uploadDir\n", FILE_APPEND);
            if (!mkdir($uploadDir, 0777, true)) {
                throw new Exception("Failed to create upload directory.");
            }
        }

        // Get file extension
        $file_extension = pathinfo($profile_photo["name"], PATHINFO_EXTENSION);
        
        // Create filename using username
        $clean_username = preg_replace('/[^a-zA-Z0-9_-]/', '_', $username);
        $photoName = $clean_username . "_profile." . $file_extension;
        $photoPath = $uploadDir . "/" . $photoName;

        file_put_contents('rider_debug.log', "Photo path: $photoPath\n", FILE_APPEND);

        // Move file to uploads folder
        if (!move_uploaded_file($profile_photo["tmp_name"], $photoPath)) {
            throw new Exception("Failed to save uploaded photo.");
        }
        file_put_contents('rider_debug.log', "Photo successfully saved\n", FILE_APPEND);

        // Hash Password for Security
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        file_put_contents('rider_debug.log', "Password hashed successfully\n", FILE_APPEND);

        // Insert Data into Database
        $sql = "INSERT INTO riders 
                (Rider_ID, Username, Email, Phone_Number, Gender, Profile_Photo, Motorcycle_Plate_Number, Residence, Password) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        file_put_contents('rider_debug.log', "SQL: $sql\n", FILE_APPEND);
        file_put_contents('rider_debug.log', "Values to insert:\n", FILE_APPEND);
        file_put_contents('rider_debug.log', "- Rider_ID: $rider_id\n", FILE_APPEND);
        file_put_contents('rider_debug.log', "- Username: $username\n", FILE_APPEND);
        file_put_contents('rider_debug.log', "- Email: $email\n", FILE_APPEND);
        file_put_contents('rider_debug.log', "- Phone_Number: $phone_number\n", FILE_APPEND);
        file_put_contents('rider_debug.log', "- Gender: $gender\n", FILE_APPEND);
        file_put_contents('rider_debug.log', "- Profile_Photo: $photoPath\n", FILE_APPEND);
        file_put_contents('rider_debug.log', "- Motorcycle_Plate_Number: $motorcycle_plate\n", FILE_APPEND);
        file_put_contents('rider_debug.log', "- Residence: $residence\n", FILE_APPEND);
        file_put_contents('rider_debug.log', "- Password: [hashed]\n", FILE_APPEND);

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $error = $conn->error;
            file_put_contents('rider_debug.log', "SQL preparation failed: $error\n", FILE_APPEND);
            throw new Exception("SQL preparation failed: " . $error);
        }
        file_put_contents('rider_debug.log', "SQL prepared successfully\n", FILE_APPEND);

        // Bind parameters
        $bind_result = $stmt->bind_param("sssisssss", $rider_id, $username, $email, $phone_number, $gender, $photoPath, $motorcycle_plate, $residence, $hashedPassword);
        if (!$bind_result) {
            $error = $stmt->error;
            file_put_contents('rider_debug.log', "Parameter binding failed: $error\n", FILE_APPEND);
            throw new Exception("Parameter binding failed: " . $error);
        }
        file_put_contents('rider_debug.log', "Parameters bound successfully\n", FILE_APPEND);

        // Execute the statement
        file_put_contents('rider_debug.log', "Executing SQL statement...\n", FILE_APPEND);
        $execute_result = $stmt->execute();
        
        if ($execute_result) {
            $affected_rows = $stmt->affected_rows;
            file_put_contents('rider_debug.log', "SQL executed successfully. Affected rows: $affected_rows\n", FILE_APPEND);
            
            // Verify the data was inserted
            $verify_sql = "SELECT * FROM riders WHERE Rider_ID = ?";
            $verify_stmt = $conn->prepare($verify_sql);
            $verify_stmt->bind_param("s", $rider_id);
            $verify_stmt->execute();
            $verify_result = $verify_stmt->get_result();
            $row_count = $verify_result->num_rows;
            
            file_put_contents('rider_debug.log', "Verification query found $row_count rows with Rider_ID: $rider_id\n", FILE_APPEND);
            
            echo json_encode([
                "status" => "success", 
                "message" => "Rider registered successfully. Your Rider ID: " . $rider_id,
                "debug" => [
                    "affected_rows" => $affected_rows,
                    "verified_rows" => $row_count,
                    "rider_id" => $rider_id
                ]
            ]);
        } else {
            $error = $stmt->error;
            file_put_contents('rider_debug.log', "SQL execution failed: $error\n", FILE_APPEND);
            
            // Check for duplicate entry errors
            if (strpos($error, 'Duplicate entry') !== false) {
                if (strpos($error, 'Email') !== false) {
                    throw new Exception("Email already exists. Please use a different email.");
                } elseif (strpos($error, 'Phone_Number') !== false) {
                    throw new Exception("Phone number already exists. Please use a different phone number.");
                } elseif (strpos($error, 'Motorcycle_Plate_Number') !== false) {
                    throw new Exception("Motorcycle plate number already exists. Please use a different plate number.");
                } elseif (strpos($error, 'Password') !== false) {
                    throw new Exception("Password conflict. Please use a different password.");
                } else {
                    throw new Exception("Duplicate entry found. Please check your information.");
                }
            }
            throw new Exception("Database error: " . $error);
        }

        $stmt->close();
        $conn->close();
        file_put_contents('rider_debug.log', "Database connection closed\n", FILE_APPEND);

    } catch (Exception $e) {
        // Log error for debugging
        $error_message = $e->getMessage();
        file_put_contents('rider_debug.log', "EXCEPTION: $error_message\n", FILE_APPEND);
        
        // Send error response
        echo json_encode([
            "status" => "error", 
            "message" => $error_message
        ]);
    }

} else {
    file_put_contents('rider_debug.log', "Invalid request method: " . $_SERVER["REQUEST_METHOD"] . "\n", FILE_APPEND);
    echo json_encode([
        "status" => "error", 
        "message" => "Invalid request method."
    ]);
}

file_put_contents('rider_debug.log', "=== RIDER REGISTRATION COMPLETED ===\n\n", FILE_APPEND);
