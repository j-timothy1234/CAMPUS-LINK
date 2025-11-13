<?php

/**
 * Sync Monitor Dashboard
 * View: /CAMPUS-LINK/sync_monitor.php
 * 
 * Requires: API key in query parameter
 * Usage: http://localhost/CAMPUS-LINK/sync_monitor.php?api_key=YOUR_KEY
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/sync/DatabaseWithSync.php';

// Verify API key
$api_key = $_GET['api_key'] ?? $_POST['api_key'] ?? '';
$valid_key = !empty($api_key) && hash_equals(SYNC_API_KEY, $api_key);

if (!$valid_key && $_SERVER['REQUEST_METHOD'] === 'POST') {
    http_response_code(403);
    header('Content-Type: application/json');
    exit(json_encode(['error' => 'Invalid API key']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid_key) {
    // Handle AJAX actions
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    $db = new DatabaseWithSync();
    
    switch ($action) {
        case 'trigger_sync':
            $sync = $db->getSync();
            $count = $sync->triggerSync();
            echo json_encode(['success' => true, 'processed' => $count]);
            exit;
            
        case 'get_queue_status':
            $sync = $db->getSync();
            $status = $sync->getQueueStatus();
            echo json_encode(['success' => true, 'status' => $status]);
            exit;
            
        case 'clear_failed':
            $db->skipSync(true);
            $conn = $db->getConnection();
            $result = $conn->query("DELETE FROM sync_queue WHERE status = 'failed'");
            echo json_encode(['success' => true, 'message' => 'Failed syncs cleared']);
            exit;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CampusLink Sync Monitor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f5f5f5; padding: 20px; }
        .monitor-card { background: white; border-radius: 10px; padding: 20px; margin: 10px 0; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .status-pending { color: #ff9800; font-weight: bold; }
        .status-synced { color: #4caf50; font-weight: bold; }
        .status-failed { color: #f44336; font-weight: bold; }
        .queue-table { font-size: 0.9rem; }
        .badge-pending { background: #ff9800; }
        .badge-synced { background: #4caf50; }
        .badge-failed { background: #f44336; }
    </style>
</head>
<body>

<div class="container">
    <h1 class="mb-4">📊 CampusLink Sync Monitor</h1>
    
    <?php if (!$valid_key): ?>
        <div class="alert alert-warning">
            <strong>Authentication Required</strong><br>
            This page requires a valid API key. Access it like: 
            <code>?api_key=YOUR_KEY</code>
        </div>
    <?php else: ?>
        <div class="row">
            <div class="col-md-8">
                <!-- Queue Status -->
                <div class="monitor-card">
                    <h3>Sync Queue Status</h3>
                    <div id="queue-status" style="display: none;">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="text-center p-3 bg-light rounded">
                                    <h2 id="pending-count">0</h2>
                                    <p class="status-pending">Pending</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center p-3 bg-light rounded">
                                    <h2 id="synced-count">0</h2>
                                    <p class="status-synced">Synced</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center p-3 bg-light rounded">
                                    <h2 id="failed-count">0</h2>
                                    <p class="status-failed">Failed</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center p-3 bg-light rounded">
                                    <h2 id="total-count">0</h2>
                                    <p>Total</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="queue-loading" class="text-center">
                        <div class="spinner-border"></div>
                        <p>Loading...</p>
                    </div>
                </div>

                <!-- Server Info -->
                <div class="monitor-card">
                    <h3>Server Configuration</h3>
                    <table class="table">
                        <tr>
                            <td><strong>This Server:</strong></td>
                            <td><code><?php echo THIS_SERVER; ?></code></td>
                        </tr>
                        <tr>
                            <td><strong>Master Server:</strong></td>
                            <td><code><?php echo MASTER_SERVER; ?></code></td>
                        </tr>
                        <tr>
                            <td><strong>Slave Server:</strong></td>
                            <td><code><?php echo SLAVE_SERVER; ?></code></td>
                        </tr>
                        <tr>
                            <td><strong>API Key:</strong></td>
                            <td><code>***<?php echo substr(SYNC_API_KEY, -6); ?></code></td>
                        </tr>
                    </table>
                </div>

                <!-- Recent Sync History -->
                <div class="monitor-card">
                    <h3>Recent Sync Activity</h3>
                    <div id="sync-history" class="queue-table">
                        <div class="spinner-border"></div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <!-- Controls -->
                <div class="monitor-card">
                    <h3>Controls</h3>
                    <button class="btn btn-primary w-100 mb-2" id="btn-trigger-sync">
                        ▶️ Trigger Sync Now
                    </button>
                    <button class="btn btn-danger w-100 mb-2" id="btn-clear-failed">
                        🗑️ Clear Failed Syncs
                    </button>
                    <button class="btn btn-secondary w-100" id="btn-refresh">
                        🔄 Refresh
                    </button>
                </div>

                <!-- Auto-Refresh Toggle -->
                <div class="monitor-card">
                    <h3>Auto-Refresh</h3>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="auto-refresh" checked>
                        <label class="form-check-label" for="auto-refresh">
                            Every 10 seconds
                        </label>
                    </div>
                </div>

                <!-- Help -->
                <div class="monitor-card bg-light">
                    <h3>Help</h3>
                    <ul style="font-size: 0.9rem;">
                        <li><strong>Pending:</strong> Waiting to sync</li>
                        <li><strong>Synced:</strong> Successfully sent</li>
                        <li><strong>Failed:</strong> 3+ attempts failed</li>
                        <li><code>sync_trigger.php</code> runs auto-sync</li>
                        <li>Check PHP error logs for details</li>
                    </ul>
                </div>
            </div>
        </div>

    <?php endif; ?>
</div>

<script>
const API_KEY = new URLSearchParams(window.location.search).get('api_key');
let autoRefreshInterval = null;

async function loadQueueStatus() {
    try {
        const response = await fetch(window.location.href, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=get_queue_status&api_key=' + API_KEY
        });
        const data = await response.json();
        
        if (data.success) {
            const status = data.status;
            document.getElementById('pending-count').textContent = status.pending?.count || 0;
            document.getElementById('synced-count').textContent = status.synced?.count || 0;
            document.getElementById('failed-count').textContent = status.failed?.count || 0;
            
            const total = (status.pending?.count || 0) + (status.synced?.count || 0) + (status.failed?.count || 0);
            document.getElementById('total-count').textContent = total;
            
            document.getElementById('queue-loading').style.display = 'none';
            document.getElementById('queue-status').style.display = 'block';
        }
    } catch (e) {
        console.error('Failed to load status:', e);
    }
}

document.getElementById('btn-trigger-sync')?.addEventListener('click', async () => {
    const btn = event.target;
    btn.disabled = true;
    btn.textContent = 'Syncing...';
    
    try {
        const response = await fetch(window.location.href, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=trigger_sync&api_key=' + API_KEY
        });
        const data = await response.json();
        alert('Synced ' + data.processed + ' operations');
        loadQueueStatus();
    } catch (e) {
        alert('Error: ' + e.message);
    } finally {
        btn.disabled = false;
        btn.textContent = '▶️ Trigger Sync Now';
    }
});

document.getElementById('btn-clear-failed')?.addEventListener('click', async () => {
    if (!confirm('Clear all failed syncs?')) return;
    
    try {
        await fetch(window.location.href, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=clear_failed&api_key=' + API_KEY
        });
        alert('Failed syncs cleared');
        loadQueueStatus();
    } catch (e) {
        alert('Error: ' + e.message);
    }
});

document.getElementById('btn-refresh')?.addEventListener('click', loadQueueStatus);

document.getElementById('auto-refresh')?.addEventListener('change', (e) => {
    if (e.target.checked) {
        autoRefreshInterval = setInterval(loadQueueStatus, 10000);
    } else {
        clearInterval(autoRefreshInterval);
    }
});

// Initial load
if (API_KEY) {
    loadQueueStatus();
    autoRefreshInterval = setInterval(loadQueueStatus, 10000);
}
</script>

</body>
</html>
