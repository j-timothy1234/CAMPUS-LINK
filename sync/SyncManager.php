<?php

/**
 * SyncManager - Two-Way Database Synchronization System
 * 
 * Features:
 * - Automatic sync after DB operations (insert, update, delete)
 * - Offline-first with sync queue
 * - Conflict resolution
 * - Network resilience
 * - Timestamp-based conflict detection
 * - Bidirectional sync without infinite loops
 */

class SyncManager {
    private $db;
    private $sync_queue_table = 'sync_queue';
    private $is_syncing = false;

    public function __construct($database = null) {
        $this->db = $database ?? Database::getInstance();
        $this->initializeSyncTable();
    }

    /**
     * Initialize sync queue table if it doesn't exist
     */
    private function initializeSyncTable() {
        $conn = $this->db->getConnection();
        $sql = "
        CREATE TABLE IF NOT EXISTS sync_queue (
            id INT PRIMARY KEY AUTO_INCREMENT,
            table_name VARCHAR(100) NOT NULL,
            action ENUM('insert', 'update', 'delete') NOT NULL,
            record_id VARCHAR(100) NOT NULL,
            data JSON,
            status ENUM('pending', 'synced', 'failed') DEFAULT 'pending',
            attempt_count INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            synced_at TIMESTAMP NULL,
            error_message TEXT NULL,
            INDEX idx_status (status),
            INDEX idx_table (table_name),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ";
        
        try {
            $conn->query($sql);
            error_log("Sync queue table initialized");
        } catch (Exception $e) {
            error_log("Sync table creation: " . $e->getMessage());
        }
    }

    /**
     * Queue a sync operation (for offline-first capability)
     */
    public function queueSync($table, $action, $record_id, $data = null) {
        if ($this->is_syncing) {
            return true;
        }

        $conn = $this->db->getConnection();
        $data_json = $data ? json_encode($data) : null;
        
        $stmt = $conn->prepare(
            "INSERT INTO sync_queue 
            (table_name, action, record_id, data, status) 
            VALUES (?, ?, ?, ?, 'pending')"
        );
        
        $stmt->bind_param('ssss', $table, $action, $record_id, $data_json);
        
        try {
            $stmt->execute();
            error_log("Sync queued: $table.$action($record_id)");
            return true;
        } catch (Exception $e) {
            error_log("Failed to queue sync: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Execute pending syncs
     */
    public function processPendingQueue() {
        if ($this->is_syncing) {
            error_log("Sync already in progress");
            return 0;
        }

        $this->is_syncing = true;
        $conn = $this->db->getConnection();
        
        try {
            $stmt = $conn->prepare(
                "SELECT id, table_name, action, record_id, data 
                FROM sync_queue 
                WHERE status = 'pending' AND attempt_count < 3 
                ORDER BY created_at ASC LIMIT 50"
            );
            
            $stmt->execute();
            $result = $stmt->get_result();
            $synced_count = 0;

            while ($row = $result->fetch_assoc()) {
                $data = $row['data'] ? json_decode($row['data'], true) : [];
                
                if ($this->sendSync(
                    $row['action'],
                    $row['table_name'],
                    $data,
                    $row['record_id']
                )) {
                    $this->updateQueueStatus($row['id'], 'synced');
                    $synced_count++;
                } else {
                    $this->incrementAttempt($row['id']);
                }
            }

            error_log("Processed $synced_count sync operations");
            return $synced_count;
        } catch (Exception $e) {
            error_log("Error processing sync queue: " . $e->getMessage());
            return 0;
        } finally {
            $this->is_syncing = false;
        }
    }

    /**
     * Send sync to remote server
     */
    private function sendSync($action, $table, $data, $id = null) {
        $target_server = $this->getTargetServer();
        if (!$target_server) {
            error_log("No target server configured");
            return false;
        }

        $url = $target_server . '/api/sync_receive.php';
        
        $postData = [
            'api_key' => SYNC_API_KEY,
            'action' => $action,
            'table' => $table,
            'data' => json_encode($data),
            'id' => $id,
            'timestamp' => time()
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($postData),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_FAILONERROR => false,
            CURLOPT_CONNECTTIMEOUT => 5
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            error_log("Sync cURL error to $url: $error");
            return false;
        }

        if ($httpCode >= 200 && $httpCode < 300) {
            error_log("Sync success: $table.$action($id) to $target_server");
            return true;
        }

        error_log("Sync HTTP $httpCode: $table.$action($id) to $target_server");
        return false;
    }

    /**
     * Determine target server
     */
    private function getTargetServer() {
        $current = trim(THIS_SERVER, '/');
        $master = trim(MASTER_SERVER, '/');
        $slave = trim(SLAVE_SERVER, '/');

        if ($current === $master) {
            return $slave;
        } elseif ($current === $slave) {
            return $master;
        }

        error_log("Current server does not match config");
        return null;
    }

    /**
     * Update queue item status
     */
    private function updateQueueStatus($id, $status) {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare(
            "UPDATE sync_queue SET status = ?, synced_at = NOW() WHERE id = ?"
        );
        $stmt->bind_param('si', $status, $id);
        return $stmt->execute();
    }

    /**
     * Increment attempt count
     */
    private function incrementAttempt($id) {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare(
            "UPDATE sync_queue SET attempt_count = attempt_count + 1 WHERE id = ?"
        );
        $stmt->bind_param('i', $id);
        return $stmt->execute();
    }

    /**
     * Get sync queue status
     */
    public function getQueueStatus() {
        $conn = $this->db->getConnection();
        
        $result = $conn->query(
            "SELECT status, COUNT(*) as count
            FROM sync_queue GROUP BY status"
        );

        $status = [];
        while ($row = $result->fetch_assoc()) {
            $status[$row['status']] = $row['count'];
        }

        return $status;
    }

    /**
     * Clean up old synced records
     */
    public function cleanupOldSyncs($days = 30) {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare(
            "DELETE FROM sync_queue 
            WHERE status = 'synced' AND synced_at < DATE_SUB(NOW(), INTERVAL ? DAY)"
        );
        $stmt->bind_param('i', $days);
        return $stmt->execute();
    }

    /**
     * Manual trigger for sync
     */
    public function triggerSync() {
        return $this->processPendingQueue();
    }
}

?>
