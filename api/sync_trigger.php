<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/SyncManager.php';

header('Content-Type: application/json');

/**
 * Sync Trigger API Endpoint
 * 
 * Call this periodically via cron or manually to process pending syncs
 * GET /api/sync_trigger.php?api_key=YOUR_KEY
 */

$api_key = $_GET['api_key'] ?? $_POST['api_key'] ?? '';

if (!hash_equals(SYNC_API_KEY, $api_key)) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $sync = new SyncManager();
    $count = $sync->processPendingQueue();
    $status = $sync->getQueueStatus();
    
    echo json_encode([
        'success' => true,
        'processed' => $count,
        'queue_status' => $status
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
