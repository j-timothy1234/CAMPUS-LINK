<?php
require_once __DIR__ . '/../sessions/session_config.php';
require_once __DIR__ . '/../db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['loggedin']) || $_SESSION['user_type'] !== 'client') {
    http_response_code(403);
    echo json_encode(['success'=>false,'message'=>'Unauthorized']);
    exit();
}

$db = new Database();
$conn = $db->getConnection();

$payload = json_decode(file_get_contents('php://input'), true);
if (!$payload) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'Invalid payload']); exit(); }

$client_id = $_SESSION['client_id'];
$agent_id = $payload['agent_id'] ?? '';
$service = $payload['service'] ?? 'book'; // book or delivery
$mode = $payload['mode'] ?? 'bike';
$datetime = $payload['datetime'] ?? null;
$pickup = $payload['pickup'] ?? '';
$destination = $payload['destination'] ?? '';
$estimate = $payload['estimate'] ?? '';
$pickup_lat = $payload['pickup_lat'] ?? null;
$pickup_lng = $payload['pickup_lng'] ?? null;
$dest_lat = $payload['dest_lat'] ?? null;
$dest_lng = $payload['dest_lng'] ?? null;

// Ensure bookings table exists
$create = "CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id VARCHAR(50),
    agent_id VARCHAR(50),
    service VARCHAR(20),
    mode VARCHAR(20),
    datetime DATETIME,
    pickup TEXT,
    destination TEXT,
    estimate VARCHAR(50),
    pickup_lat DOUBLE DEFAULT NULL,
    pickup_lng DOUBLE DEFAULT NULL,
    dest_lat DOUBLE DEFAULT NULL,
    dest_lng DOUBLE DEFAULT NULL,
    status VARCHAR(20) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
$conn->query($create);

$stmt = $conn->prepare('INSERT INTO bookings (client_id, agent_id, service, mode, datetime, pickup, destination, estimate) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
if (!$stmt) { http_response_code(500); echo json_encode(['success'=>false,'message'=>'DB prepare failed']); exit(); }
// include coords when available
if ($pickup_lat !== null && $pickup_lng !== null && $dest_lat !== null && $dest_lng !== null) {
    $stmt = $conn->prepare('INSERT INTO bookings (client_id, agent_id, service, mode, datetime, pickup, destination, estimate, pickup_lat, pickup_lng, dest_lat, dest_lng) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('ssssssssdddd', $client_id, $agent_id, $service, $mode, $datetime, $pickup, $destination, $estimate, $pickup_lat, $pickup_lng, $dest_lat, $dest_lng);
} else {
    $stmt->bind_param('ssssssss', $client_id, $agent_id, $service, $mode, $datetime, $pickup, $destination, $estimate);
}
if ($stmt->execute()) {
    $booking_id = $conn->insert_id;
    // create notifications table if it doesn't exist
    $notif_create_sql = "CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        booking_id INT,
        agent_id VARCHAR(50),
        client_id VARCHAR(50),
        status VARCHAR(20) DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $conn->query($notif_create_sql);

    // Insert into notifications table only if agent specified
    if (!empty($agent_id)) {
        $ins = $conn->prepare('INSERT INTO notifications (booking_id, agent_id, client_id, status) VALUES (?, ?, ?, ?)');
        if ($ins) {
            $stat = 'pending';
            $ins->bind_param('isss', $booking_id, $agent_id, $client_id, $stat);
            $ins->execute();
        }
    }
    echo json_encode(['success'=>true,'booking_id'=>$booking_id]);
} else {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Failed to create booking']);
}

?>
