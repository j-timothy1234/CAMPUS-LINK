<?php
require_once __DIR__ . '/../sessions/session_config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['loggedin']) || !in_array($_SESSION['user_type'], ['driver','rider','client'])) {
    http_response_code(403);
    echo json_encode(['success'=>false,'message'=>'Unauthorized']);
    exit();
}

// Determine agent id based on role
$agent_id = null;
if ($_SESSION['user_type'] === 'driver') $agent_id = $_SESSION['driver_id'] ?? null;
if ($_SESSION['user_type'] === 'rider') $agent_id = $_SESSION['rider_id'] ?? null;
if ($_SESSION['user_type'] === 'client') $agent_id = $_SESSION['client_id'] ?? null;

$token = bin2hex(random_bytes(16));
// store in session so PHP can validate later if needed
$_SESSION['ws_token'] = $token;
$_SESSION['ws_token_created'] = time();

echo json_encode(['success'=>true,'token'=>$token,'agent_id'=>$agent_id,'role'=>$_SESSION['user_type']]);

?>
