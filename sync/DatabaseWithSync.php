<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/../sync/SyncManager.php';

/**
 * Enhanced DB_Connect with automatic sync hooks
 * 
 * Usage: Same as before, but now automatically queues syncs
 * Example:
 *   $db = new DatabaseWithSync();
 *   $db->insert('users', ['name' => 'John', 'email' => 'john@example.com']);
 *   // Automatically queues sync to other server
 */

class DatabaseWithSync extends Database {
    private $sync;
    private $skip_sync = false;

    public function __construct() {
        parent::__construct();
        $this->sync = new SyncManager($this);
    }

    /**
     * Insert with automatic sync
     */
    public function insert($table, $data) {
        $conn = $this->getConnection();
        
        // Build INSERT query
        $columns = array_keys($data);
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $column_list = '`' . implode('`, `', $columns) . '`';
        
        $sql = "INSERT INTO `$table` ($column_list) VALUES ($placeholders)";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Insert prepare failed: " . $conn->error);
        }

        $values = array_values($data);
        $types = str_repeat('s', count($values));
        $stmt->bind_param($types, ...$values);
        $stmt->execute();
        
        $record_id = $conn->insert_id;

        // Queue sync if not syncing already
        if (!$this->skip_sync) {
            $this->sync->queueSync($table, 'insert', $record_id, $data);
        }

        return $record_id;
    }

    /**
     * Update with automatic sync
     */
    public function update($table, $data, $where, $where_values = []) {
        $conn = $this->getConnection();
        
        $updates = [];
        foreach (array_keys($data) as $col) {
            $updates[] = "`$col` = ?";
        }
        $set_clause = implode(', ', $updates);
        
        $sql = "UPDATE `$table` SET $set_clause WHERE $where";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Update prepare failed: " . $conn->error);
        }

        $values = array_values($data);
        $types = str_repeat('s', count($values));
        
        if ($where_values) {
            $types .= str_repeat('s', count($where_values));
            $values = array_merge($values, $where_values);
        }

        $stmt->bind_param($types, ...$values);
        $stmt->execute();

        // Queue sync if not syncing already
        if (!$this->skip_sync) {
            $this->sync->queueSync($table, 'update', $where, $data);
        }

        return $stmt->affected_rows;
    }

    /**
     * Delete with automatic sync
     */
    public function delete($table, $where, $where_values = []) {
        $conn = $this->getConnection();
        
        $sql = "DELETE FROM `$table` WHERE $where";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Delete prepare failed: " . $conn->error);
        }

        if ($where_values) {
            $types = str_repeat('s', count($where_values));
            $stmt->bind_param($types, ...$where_values);
        }

        $stmt->execute();

        // Queue sync if not syncing already
        if (!$this->skip_sync) {
            $this->sync->queueSync($table, 'delete', $where, null);
        }

        return $stmt->affected_rows;
    }

    /**
     * Skip sync for this operation (prevents loops during sync)
     */
    public function skipSync($skip = true) {
        $this->skip_sync = $skip;
        return $this;
    }

    /**
     * Get sync manager
     */
    public function getSync() {
        return $this->sync;
    }
}
?>
