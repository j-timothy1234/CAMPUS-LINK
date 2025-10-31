<?php
// Network Configuration
// Replace with the PUBLIC IP addresses or domain names of your servers.
// Example: 'http://203.0.113.10' or 'http://master.yourdomain.com'
define('MASTER_SERVER', 'http://172.19.25.101'); // <-- CHANGE TO YOUR MASTER'S PUBLIC ADDRESS
define('SLAVE_SERVER', 'http://172.19.25.102');  // <-- CHANGE TO YOUR SLAVE'S PUBLIC ADDRESS

// --- Dynamic Server Identification ---
// This automatically determines if the current server is the master or slave.
// It compares the server's HTTP_HOST with the MASTER_SERVER constant.
// Note: This requires MASTER_SERVER to be the correct domain/IP for this to work.
$this_server_host = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? '');
define('THIS_SERVER', rtrim($this_server_host, '/'));

// --- Security Configuration ---
define('SYNC_API_KEY', 'your-very-secret-and-long-api-key-here'); // Change this to a long random string

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'job1234joy#');
define('DB_NAME', 'campuslink');
?>