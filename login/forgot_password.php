<?php
// Simple forgot password form
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Forgot password</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>.container{max-width:480px;margin:50px auto}</style>
</head>
<body>
  <div class="container">
    <h3>Forgot your password?</h3>
    <p>Enter your email or username and we'll send a link to reset your password.</p>
    <form method="post" action="request_reset.php">
      <div class="mb-3">
        <label for="identifier">Email or Username</label>
        <input id="identifier" name="identifier" class="form-control" required>
      </div>
      <button class="btn btn-primary">Send reset link</button>
      <a href="login.php" class="btn btn-link">Back to login</a>
    </form>
  </div>
</body>
</html>
