<?php
require_once __DIR__ . '/../config.php';

/**
 * Sends a data synchronization request to the other server.
 *
 * @param string $action 'insert', 'update', or 'delete'.
 * @param string $table The name of the table to modify.
 * @param array $data The data for the operation.
 * @param mixed|null $id The primary key for 'update' or 'delete' actions.
 * @return bool True on success, false on failure.
 */
function syncWithOtherServer($action, $table, $data, $id = null) {
    // Determine which server to sync with
    $target_server = (THIS_SERVER == MASTER_SERVER) ? SLAVE_SERVER : MASTER_SERVER;
    $url = $target_server . '/api/sync_receive.php';
    
    $postData = [
        'api_key' => SYNC_API_KEY, // Add the API key for authentication
        'action' => $action,
        'table' => $table,
        'data' => json_encode($data),
        'id' => $id
    ];
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($postData), // Use http_build_query for standard form encoding
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15, // Increased timeout for potentially slow networks
        CURLOPT_FAILONERROR => false // We want to get the response body even on 4xx/5xx errors
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    // Check for cURL errors (e.g., network issues)
    if ($error) {
        error_log("Sync cURL Error to $url: " . $error);
        return false;
    }

    // Log errors from the remote server for easier debugging
    if ($httpCode >= 400) {
        error_log("Sync HTTP Error to $url (Code: $httpCode): Response: $response");
        return false;
    }
    
    // Success is now considered any 2xx status code
    return $httpCode >= 200 && $httpCode < 300;
}
?>