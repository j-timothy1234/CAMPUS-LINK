<?php
require_once __DIR__ . '/../sessions/session_config.php';
require_once __DIR__ . '/../db_connect.php';

header('Content-Type: application/json');

// Only drivers or riders should access notifications
if (!isset($_SESSION['loggedin']) || !in_array($_SESSION['user_type'], ['driver','rider'])) {
    http_response_code(403);
    echo json_encode(['success'=>false,'message'=>'Unauthorized']);
    exit();
}

$db = new Database();
$conn = $db->getConnection();

$agent_id = $_SESSION['driver_id'] ?? $_SESSION['rider_id'] ?? null;
if (!$agent_id) { echo json_encode(['success'=>true,'notifications'=>[]]); exit(); }

$sql = "SELECT n.id as notification_id, n.booking_id, n.status as notify_status, b.client_id, b.pickup, b.destination, b.pickup_lat, b.pickup_lng, b.dest_lat, b.dest_lng, c.Username as client_name, c.Phone_Number as client_phone
        FROM notifications n
        JOIN bookings b ON b.id = n.booking_id
        LEFT JOIN clients c ON c.Client_ID = b.client_id
        WHERE n.agent_id = ? AND n.status = 'pending' ORDER BY n.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $agent_id);
$stmt->execute();
$res = $stmt->get_result();
$rows = [];
while ($r = $res->fetch_assoc()) $rows[] = $r;

echo json_encode(['success'=>true,'notifications'=>$rows]);

?>
