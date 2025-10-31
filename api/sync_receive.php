-- Active: 1761331557800@@127.0.0.1@3306@campuslink
<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db_connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- Security Check: Validate the API Key ---
    $api_key = $_POST['api_key'] ?? '';
    if (empty($api_key) || !hash_equals(SYNC_API_KEY, $api_key)) {
        http_response_code(403); // Forbidden
        echo json_encode(['status' => 'error', 'message' => 'Forbidden: Invalid API Key.']);
        exit;
    }

    $action = $_POST['action'] ?? '';
    $table = $_POST['table'] ?? '';
    $data = is_string($_POST['data'] ?? null) ? json_decode($_POST['data'], true) : [];
    $id = $_POST['id'] ?? null;

    // Whitelist tables to prevent arbitrary table access
    $allowed_tables = ['bookings', 'clients', 'drivers', 'riders', 'notifications', 'password_resets'];
    if (empty($table) || !in_array($table, $allowed_tables)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid or missing table name.']);
        exit;
    }

    if (empty($data) && $action !== 'delete') {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Data is required for this action.']);
        exit;
    }

    try {
        $db = new Database();
        $conn = $db->getConnection();

        switch ($action) {
            case 'insert':
                $columns = array_keys($data);
                $placeholders = implode(', ', array_fill(0, count($columns), '?'));
                $column_list = '`' . implode('`, `', $columns) . '`';

                // --- "Upsert" Logic: Update if the primary key already exists ---
                $updates = [];
                foreach ($columns as $col) {
                    // Don't update the primary key itself on conflict
                    if (strtolower($col) !== 'id') {
                        $updates[] = "`$col` = VALUES(`$col`)";
                    }
                }
                $update_clause = implode(', ', $updates);

                $sql = "INSERT INTO `$table` ($column_list) VALUES ($placeholders) ON DUPLICATE KEY UPDATE $update_clause";

                $stmt = $conn->prepare($sql);
                if (!$stmt) throw new Exception("SQL prepare failed: " . $conn->error);

                $values = array_values($data);
                $types = str_repeat('s', count($values));
                $stmt->bind_param($types, ...$values);

                if ($stmt->execute()) {
                    echo json_encode(['status' => 'success', 'message' => 'Data inserted', 'id' => $conn->insert_id]);
                } else {
                    throw new Exception("Execute failed: " . $stmt->error);
                }
                break;

            case 'update':
                if (!$id) throw new Exception("Missing ID for update action.");
                $updates = [];
                $values = [];
                $types = '';
                foreach ($data as $key => $value) {
                    $updates[] = "`$key` = ?";
                    $values[] = $value;
                    $types .= 's'; // Assume string
                }
                $setClause = implode(', ', $updates);
                $sql = "UPDATE `$table` SET $setClause WHERE id = ?";
                
                $values[] = $id; // Add id to the end for the WHERE clause
                $types .= 'i'; // Assume id is integer

                $stmt = $conn->prepare($sql);
                if (!$stmt) throw new Exception("SQL prepare failed: " . $conn->error);

                $stmt->bind_param($types, ...$values);

                if ($stmt->execute()) {
                    echo json_encode(['status' => 'success', 'message' => 'Data updated']);
                } else {
                    throw new Exception("Execute failed: " . $stmt->error);
                }
                break;

            case 'delete':
                if (!$id) throw new Exception("Missing ID for delete action.");
                $sql = "DELETE FROM `$table` WHERE id = ?";
                $stmt = $conn->prepare($sql);
                if (!$stmt) throw new Exception("SQL prepare failed: " . $conn->error);
                $stmt->bind_param('i', $id); // Assume id is integer

                if ($stmt->execute()) {
                    echo json_encode(['status' => 'success', 'message' => 'Data deleted']);
                } else {
                    throw new Exception("Execute failed: " . $stmt->error);
                }
                break;

            default:
                throw new Exception('Invalid action specified.');
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?>