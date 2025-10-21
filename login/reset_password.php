<?php
require_once __DIR__ . '/../db_connect.php';

$db = (new Database())->getConnection();

$token = $_GET['token'] ?? $_POST['token'] ?? '';
if (!$token) { echo 'Invalid token'; exit(); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';
    if (empty($new) || $new !== $confirm) {
        $error = 'Passwords do not match or are empty';
    } else {
        // Lookup token
        $stmt = $db->prepare('SELECT * FROM password_resets WHERE token = ? AND expires_at >= NOW() LIMIT 1');
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $res = $stmt->get_result();
        if (!$res || $res->num_rows !== 1) { $error = 'Invalid or expired token'; }
        else {
            $row = $res->fetch_assoc();
            $user_table = $row['user_table'];
            $user_id = $row['user_id'];
            $hash = password_hash($new, PASSWORD_DEFAULT);
            // Update the appropriate user's password column
            if ($user_table === 'riders') {
                $q = $db->prepare('UPDATE riders SET Password = ? WHERE Rider_ID = ?');
            } elseif ($user_table === 'drivers') {
                $q = $db->prepare('UPDATE drivers SET Password = ? WHERE Driver_ID = ?');
            } else {
                $q = $db->prepare('UPDATE clients SET Password = ? WHERE Client_ID = ?');
            }
            $q->bind_param('ss', $hash, $user_id);
            if ($q->execute()) {
                // delete token
                $del = $db->prepare('DELETE FROM password_resets WHERE id = ?');
                $del->bind_param('i', $row['id']);
                $del->execute();
                echo '<p>Password updated. <a href="login.php">Login</a></p>'; exit();
            } else { $error = 'Failed to update password'; }
        }
    }
}

?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Reset password</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>.container{max-width:480px;margin:50px auto}</style>
</head>
<body>
  <div class="container">
    <h3>Reset password</h3>
    <?php if (!empty($error)) echo '<div class="alert alert-danger">'.htmlspecialchars($error).'</div>'; ?>
    <form method="post">
      <input type="hidden" name="token" value="<?=htmlspecialchars($token)?>">
      <div class="mb-3">
        <label>New password</label>
        <input name="password" type="password" class="form-control" required>
      </div>
      <div class="mb-3">
        <label>Confirm password</label>
        <input name="confirm" type="password" class="form-control" required>
      </div>
      <button class="btn btn-primary">Set password</button>
    </form>
  </div>
</body>
</html>
