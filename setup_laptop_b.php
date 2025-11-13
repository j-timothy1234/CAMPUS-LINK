#!/usr/bin/env php
<?php

/**
 * Laptop B Setup Script
 * 
 * Run this script ONCE on LAPTOP-B to initialize everything
 * Command: php setup_laptop_b.php
 * 
 * This script:
 * 1. Creates sync_queue table
 * 2. Verifies database connection
 * 3. Tests connectivity to LAPTOP-A
 * 4. Checks all required files exist
 * 5. Provides setup status report
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  CAMPUS-LINK LAPTOP-B SETUP SCRIPT                         ║\n";
echo "║  This will initialize your second server for sync          ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

$errors = [];
$warnings = [];
$success = [];

// Step 1: Load configuration
echo "[1/5] Loading configuration...\n";
if (!file_exists(__DIR__ . '/config.php')) {
    $errors[] = "config.php not found!";
} else {
    require_once __DIR__ . '/config.php';
    $success[] = "config.php loaded";
}

// Step 2: Check database connection
echo "[2/5] Checking database connection...\n";
try {
    require_once __DIR__ . '/db_connect.php';
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    if ($conn->ping()) {
        $success[] = "Database connection successful";
    } else {
        $errors[] = "Database connection failed - ping returned false";
    }
} catch (Exception $e) {
    $errors[] = "Database error: " . $e->getMessage();
}

// Step 3: Create sync_queue table
echo "[3/5] Initializing sync queue table...\n";
try {
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
    
    if ($conn->query($sql)) {
        $success[] = "sync_queue table created/verified";
    } else {
        $errors[] = "Failed to create sync_queue table: " . $conn->error;
    }
} catch (Exception $e) {
    $errors[] = "sync_queue creation error: " . $e->getMessage();
}

// Step 4: Check required files exist
echo "[4/5] Checking required files...\n";
$required_files = [
    'sync/SyncManager.php',
    'sync/DatabaseWithSync.php',
    'api/sync_trigger.php',
    'api/sync_receive.php',
    'sync_monitor.php',
    'network_config.php'
];

foreach ($required_files as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        $success[] = "✓ $file";
    } else {
        $warnings[] = "⚠ $file not found (may not be critical)";
    }
}

// Step 5: Test connectivity to LAPTOP-A
echo "[5/5] Testing connectivity to LAPTOP-A...\n";
$test_url = MASTER_SERVER . '/config.php';
$ch = curl_init($test_url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 5,
    CURLOPT_CONNECTTIMEOUT => 3,
    CURLOPT_FAILONERROR => false
]);

$result = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    $warnings[] = "Cannot reach LAPTOP-A at " . MASTER_SERVER . ": " . $error;
} elseif ($http_code >= 200 && $http_code < 300) {
    $success[] = "Can reach LAPTOP-A at " . MASTER_SERVER;
} else {
    $warnings[] = "LAPTOP-A returned HTTP " . $http_code;
}

// Print results
echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  SETUP RESULTS                                             ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

if (!empty($success)) {
    echo "✅ SUCCESS (" . count($success) . "):\n";
    foreach ($success as $msg) {
        echo "   ✓ $msg\n";
    }
    echo "\n";
}

if (!empty($warnings)) {
    echo "⚠️  WARNINGS (" . count($warnings) . "):\n";
    foreach ($warnings as $msg) {
        echo "   ⚠ $msg\n";
    }
    echo "\n";
}

if (!empty($errors)) {
    echo "❌ ERRORS (" . count($errors) . "):\n";
    foreach ($errors as $msg) {
        echo "   ✗ $msg\n";
    }
    echo "\n";
}

// Configuration verification
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  CONFIGURATION VERIFICATION                               ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

echo "Current Configuration:\n";
echo "  This Server (LAPTOP-B): " . THIS_SERVER . "\n";
echo "  Master Server: " . MASTER_SERVER . "\n";
echo "  Slave Server: " . SLAVE_SERVER . "\n";
echo "  API Key: ***" . substr(SYNC_API_KEY, -6) . "\n";
echo "  Database: " . DB_NAME . "\n";
echo "\n";

// Next steps
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  NEXT STEPS                                                ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

if (count($errors) === 0) {
    echo "✅ Setup successful! Next steps:\n\n";
    echo "1. Verify config.php has IDENTICAL Master/Slave IPs:\n";
    echo "   - LAPTOP-A and LAPTOP-B must have same values\n";
    echo "   - Current: Master=" . MASTER_SERVER . ", Slave=" . SLAVE_SERVER . "\n\n";
    
    echo "2. Verify database connection works:\n";
    echo "   mysql -u root -p campuslink\n\n";
    
    echo "3. Set up Windows Task Scheduler:\n";
    echo "   - Run every 5 minutes: sync_trigger.php?api_key=...\n\n";
    
    echo "4. Test sync:\n";
    echo "   - Create rider on LAPTOP-A\n";
    echo "   - Check sync_monitor.php\n";
    echo "   - Verify appears on LAPTOP-B\n\n";
    
    echo "5. Monitor dashboard:\n";
    echo "   http://localhost/CAMPUS-LINK/sync_monitor.php?api_key=...\n\n";
    
    echo "See LAPTOP_B_SETUP.md for detailed instructions.\n";
} else {
    echo "❌ Setup has errors. Please fix them before continuing:\n\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
}

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  Setup script complete!                                   ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

// Return exit code based on errors
exit(count($errors) > 0 ? 1 : 0);

?>
