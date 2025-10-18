<?php
// check_driver.php
require_once __DIR__ . '/../db_connect.php';

header('Content-Type: application/json');

try {
    // Get the field and value sent via AJAX
    if (!isset($_POST['field']) || !isset($_POST['value'])) {
        throw new Exception("Missing field or value");
    }

    $field = $_POST['field'];
    $value = $_POST['value'];

    // Validate field type
    $allowed_fields = ['Email', 'Car_Plate_Number', 'Phone_Number'];
    if (!in_array($field, $allowed_fields)) {
        throw new Exception("Invalid field type");
    }

    $conn = (new Database())->getConnection();

    // Build SQL based on which field is being checked
    $sql = "SELECT COUNT(*) as count FROM drivers WHERE $field = ?";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("SQL preparation failed");
    }

    $stmt->bind_param("s", $value);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if ($row['count'] > 0) {
        echo "exists";
    } else {
        echo "available";
    }

    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    error_log("Check Driver Error: " . $e->getMessage());
    echo "error";
}
