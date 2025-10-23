<?php
require_once __DIR__ . '/../sessions/session_config.php';
require_once __DIR__ . '/../db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['loggedin']) || !in_array($_SESSION['user_type'], ['driver','rider'])) {
    http_response_code(403);
    echo json_encode(['success'=>false,'message'=>'Unauthorized']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['notification_id']) || !isset($input['action'])) {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>'Invalid request']);
    exit();
}

$notification_id = $input['notification_id'];
$action = $input['action']; // accept | decline

$db = new Database();
$conn = $db->getConnection();

// fetch notification
$stmt = $conn->prepare('SELECT * FROM notifications WHERE id = ?');
$stmt->bind_param('i', $notification_id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) { http_response_code(404); echo json_encode(['success'=>false,'message'=>'Not found']); exit(); }
$notif = $res->fetch_assoc();

if ($action === 'accept') {
    // If notification was a broadcast (agent_id starts with 'broadcast:'), claim it by updating agent_id to this agent
    $currentAgentId = $_SESSION['driver_id'] ?? $_SESSION['rider_id'] ?? null;
    if ($notif['agent_id'] && strpos($notif['agent_id'], 'broadcast:') === 0 && $currentAgentId) {
        $claim = $conn->prepare('UPDATE notifications SET agent_id = ? WHERE id = ? AND agent_id = ?');
        $claim->bind_param('sis', $currentAgentId, $notification_id, $notif['agent_id']);
        $claim->execute();
        // reload notification to confirm we successfully claimed it
        $r2 = $conn->prepare('SELECT * FROM notifications WHERE id = ?');
        $r2->bind_param('i', $notification_id);
        $r2->execute();
        $nr = $r2->get_result()->fetch_assoc();
        if ($nr['agent_id'] !== $currentAgentId) {
            echo json_encode(['success'=>false,'message'=>'Notification already claimed by another agent']);
            exit();
        }
        // update local notif variable to reflect claimed agent
        $notif['agent_id'] = $currentAgentId;
    }

    // update booking status and notification status
    $up = $conn->prepare('UPDATE bookings SET status = ? WHERE id = ?');
    $status = 'accepted';
    $up->bind_param('si', $status, $notif['booking_id']);
    $up->execute();

    $u2 = $conn->prepare('UPDATE notifications SET status = ? WHERE id = ?');
    $u2->bind_param('si', $status, $notification_id);
    $u2->execute();

    echo json_encode(['success'=>true,'message'=>'Booking accepted']);
    exit();
} else {
    // decline
    $status = 'declined';
    $u2 = $conn->prepare('UPDATE notifications SET status = ? WHERE id = ?');
    $u2->bind_param('si', $status, $notification_id);
    $u2->execute();

    $up = $conn->prepare('UPDATE bookings SET status = ? WHERE id = ?');
    $up->bind_param('si', $status, $notif['booking_id']);
    $up->execute();

    echo json_encode(['success'=>true,'message'=>'Booking declined']);
    exit();
}

?>
