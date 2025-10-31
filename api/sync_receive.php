-- Active: 1761331557800@@127.0.0.1@3306@campuslink
<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db_connection.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    $table = $_POST['table'] ?? '';
    $data = json_decode($_POST['data'] ?? '[]', true);
    $id = $_POST['id'] ?? null;
    
    $conn = getMasterConnection();
    
    if ($action == 'insert') {
        $columns = implode(', ', array_keys($data));
        $placeholders = "'" . implode("', '", array_values($data)) . "'";
        $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
        
        if ($conn->query($sql)) {
            echo json_encode(['status' => 'success', 'message' => 'Data inserted']);
        } else {
            echo json_encode(['status' => 'error', 'message' => $conn->error]);
        }
    }
    elseif ($action == 'update' && $id) {
        $updates = [];
        foreach ($data as $key => $value) {
            $updates[] = "$key = '$value'";
        }
        $setClause = implode(', ', $updates);
        $sql = "UPDATE $table SET $setClause WHERE id = $id";
        
        if ($conn->query($sql)) {
            echo json_encode(['status' => 'success', 'message' => 'Data updated']);
        } else {
            echo json_encode(['status' => 'error', 'message' => $conn->error]);
        }
    }
    elseif ($action == 'delete' && $id) {
        $sql = "DELETE FROM $table WHERE id = $id";
        
        if ($conn->query($sql)) {
            echo json_encode(['status' => 'success', 'message' => 'Data deleted']);
        } else {
            echo json_encode(['status' => 'error', 'message' => $conn->error]);
        }
    }
    else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid action or missing ID']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?>