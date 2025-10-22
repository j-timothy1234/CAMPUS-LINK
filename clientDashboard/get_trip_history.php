<?php
require_once __DIR__ . '/../sessions/session_config.php';
require_once __DIR__ . '/../db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['loggedin']) || $_SESSION['user_type'] !== 'client') {
    http_response_code(403);
    echo json_encode(['success'=>false,'message'=>'Unauthorized']);
    exit();
}

$client_id = $_SESSION['client_id'];
$db = new Database();
$conn = $db->getConnection();

$stmt = $conn->prepare('SELECT * FROM bookings WHERE client_id = ? ORDER BY created_at DESC LIMIT 50');
$stmt->bind_param('s', $client_id);
$stmt->execute();
$res = $stmt->get_result();
$rows = [];
while ($r = $res->fetch_assoc()) $rows[] = $r;

echo json_encode(['success'=>true,'trips'=>$rows]);

?>
