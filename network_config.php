<?php

/**
 * Network Configuration Helper
 * Visit: /CAMPUS-LINK/network_config.php
 * 
 * Helps you find your IP address and test connectivity to other server
 */

header('Content-Type: application/json');

$response = [
    'this_server' => [
        'host' => $_SERVER['HTTP_HOST'] ?? 'unknown',
        'ip_address' => $_SERVER['SERVER_ADDR'] ?? 'unknown',
        'protocol' => (isset($_SERVER['HTTPS']) ? 'https' : 'http'),
        'full_url' => (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? '')
    ],
    'configured_servers' => [
        'MASTER_SERVER' => MASTER_SERVER,
        'SLAVE_SERVER' => SLAVE_SERVER,
        'THIS_SERVER' => THIS_SERVER
    ],
    'connectivity' => []
];

// Test connectivity to other server
$test_server = (THIS_SERVER === rtrim(MASTER_SERVER, '/')) ? SLAVE_SERVER : MASTER_SERVER;
$test_url = $test_server . '/config.php';

$ch = curl_init($test_url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 5,
    CURLOPT_FAILONERROR => false,
    CURLOPT_CONNECTTIMEOUT => 3
]);

$result = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

$response['connectivity'] = [
    'target_server' => $test_server,
    'test_url' => $test_url,
    'status' => $error ? 'FAILED: ' . $error : ($http_code >= 200 && $http_code < 300 ? 'SUCCESS' : "HTTP $http_code"),
    'reachable' => !$error && $http_code >= 200 && $http_code < 300
];

// Test sync endpoint
$sync_url = $test_server . '/api/sync_trigger.php?api_key=' . urlencode(SYNC_API_KEY);
$ch = curl_init($sync_url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 5,
    CURLOPT_FAILONERROR => false,
    CURLOPT_CONNECTTIMEOUT => 3
]);

$result = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

$response['sync_endpoint'] = [
    'url' => $sync_url,
    'status' => $error ? 'FAILED: ' . $error : ($http_code >= 200 && $http_code < 300 ? 'SUCCESS' : "HTTP $http_code"),
    'response' => $http_code >= 200 && $http_code < 300 ? json_decode($result, true) : $result
];

// Add setup instructions
$response['next_steps'] = [
    'step_1' => 'Verify IPs above match your network',
    'step_2' => 'If connectivity shows FAILED, check firewall/network',
    'step_3' => 'If successful, update config.php on other server with same values',
    'step_4' => 'Initialize sync_queue by visiting sync_trigger.php on both',
    'step_5' => 'Start using DatabaseWithSync in your application'
];

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

?>
