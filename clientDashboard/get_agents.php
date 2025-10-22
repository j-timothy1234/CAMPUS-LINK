<?php
require_once __DIR__ . '/../sessions/session_config.php';
require_once __DIR__ . '/../db_connect.php';

header('Content-Type: application/json');

$mode = $_GET['mode'] ?? 'bike'; // 'bike' -> riders, 'uber' -> drivers

$db = new Database();
$conn = $db->getConnection();

try {
    if ($mode === 'uber') {
        $sql = 'SELECT Driver_ID AS id, Username, Profile_Photo, Car_Plate_Number AS plate, Residence AS work FROM drivers';
    } else {
        $sql = 'SELECT Rider_ID AS id, Username, Profile_Photo, Motorcycle_Plate_Number AS plate, Residence AS work FROM riders';
    }

    $res = $conn->query($sql);
    $agents = [];
    while ($row = $res->fetch_assoc()) {
        // Provide placeholder trips and ratings for now
        $row['trips'] = rand(20, 200);
        $row['rating'] = round(rand(35, 50) / 10, 1);
        $agents[] = $row;
    }

    echo json_encode(['success' => true, 'agents' => $agents]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

?>
