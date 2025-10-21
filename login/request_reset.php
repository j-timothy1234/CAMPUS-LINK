<?php
require_once __DIR__ . '/../db_connect.php';

header('Content-Type: text/html; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Method not allowed';
    exit();
}

$identifier = trim($_POST['identifier'] ?? '');
if (empty($identifier)) {
    echo 'Identifier required'; exit();
}

$db = (new Database())->getConnection();

// Find user in riders/drivers/clients
$findUser = function($table, $emailField, $usernameField) use ($db, $identifier) {
    $sql = "SELECT * FROM $table WHERE $emailField = ? OR $usernameField = ? LIMIT 1";
    $stmt = $db->prepare($sql);
    if (!$stmt) return null;
    $stmt->bind_param('ss', $identifier, $identifier);
    $stmt->execute();
    $res = $stmt->get_result();
    return $res && $res->num_rows === 1 ? array_merge(['table'=>$table], $res->fetch_assoc()) : null;
};

$user = $findUser('riders','Email','Username') ?: $findUser('drivers','Email','Username') ?: $findUser('clients','Email','Username');

if (!$user) {
    echo 'No account found for that identifier. <a href="forgot_password.php">Try again</a>';
    exit();
}

// Ensure password_resets table exists (quick create if missing)
$db->query("CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_table VARCHAR(50) NOT NULL,
    user_id VARCHAR(100) NOT NULL,
    token VARCHAR(128) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$token = bin2hex(random_bytes(24));
$expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour

$insert = $db->prepare('INSERT INTO password_resets (user_table,user_id,token,expires_at) VALUES (?,?,?,?)');
$user_id = $user['Rider_ID'] ?? $user['Driver_ID'] ?? $user['Client_ID'];
$insert->bind_param('ssss', $user['table'], $user_id, $token, $expires);
$insert->execute();

$resetUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/reset_password.php?token=' . $token;

// In production you would email $resetUrl to the user. For dev show it on screen and log it.
echo '<p>Reset link (development): <a href="' . htmlspecialchars($resetUrl) . '">' . htmlspecialchars($resetUrl) . '</a></p>';
error_log('Password reset link: ' . $resetUrl);

echo '<p>If you did not request this, ignore this message.</p>';
