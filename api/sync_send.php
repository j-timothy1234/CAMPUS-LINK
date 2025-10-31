<?php
require_once __DIR__ . '/../config.php';

function syncWithOtherServer($action, $table, $data, $id = null) {
    // Determine which server to sync with
    $target_server = (THIS_SERVER == MASTER_SERVER) ? SLAVE_SERVER : MASTER_SERVER;
    $url = $target_server . '/api/sync_receive.php';
    
    $postData = [
        'action' => $action,
        'table' => $table,
        'data' => json_encode($data),
        'id' => $id
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $httpCode == 200;
}
?>