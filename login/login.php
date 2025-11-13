<?php
// Include session configuration and check authentication
require_once __DIR__ . '/../sessions/session_config.php';

// If already logged in, redirect to the appropriate dashboard
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    $type = $_SESSION['user_type'] ?? null;
    if ($type === 'rider') {
        header('Location: ../riderDashboard/riderDashboard.php'); exit();
    } elseif ($type === 'driver') {
        header('Location: ../driverDashboard/driverDashboard.php'); exit();
    } elseif ($type === 'client') {
        header('Location: ../clientDashboard/clientDashboard.php'); exit();
    }
}

$error = $_GET['error'] ?? '';

?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login - CampusLink</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="login.css">
</head>
<body>
  <!-- Back to homepage arrow -->
  <a href="../homepage/index.html" aria-label="Back to homepage" id="back-home-arrow" title="Back to homepage"
     style="position:fixed;top:12px;left:12px;z-index:1050;background:#fff;border-radius:6px;padding:8px 10px;box-shadow:0 2px 6px rgba(0,0,0,0.12);text-decoration:none;color:#333;border:1px solid #ddd;">
    <!-- simple left arrow using HTML entity -->
    &larr; Home
  </a>
  <div class="login-container">
    <div class="text-center mb-3">
      <img src="images/logo.png" alt="CampusLink Logo" class="logo">
    </div>

    <h3 class="text-center mb-4">Login Into Your Account</h3>

    <?php if ($error === 'invalid_credentials'): ?>
      <div class="alert alert-danger">Invalid credentials. Please register or try again.</div>
    <?php endif; ?>

    <form method="post" action="auth.php">
      <div class="mb-3">
        <label for="identifier" class="form-label">Email or Username</label>
        <input type="text" class="form-control" id="identifier" name="email" placeholder="Email or username" required>
      </div>
      <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <div class="password-wrapper">
          <input type="password" class="form-control with-toggle" id="password" name="password" placeholder="Password" required>
          <button type="button" class="password-toggle" aria-label="Toggle password visibility"></button>
        </div>
      </div>
      <div class="d-flex justify-content-between align-items-center mb-3">
        <a href="forgot_password.php" class="forgot-link">Forgot password?</a>
        <button type="submit" class="btn btn-primary">Login</button>
      </div>
      <div class="text-center">
        <p>Don't have an Account?</p>
        <a href="../riders/rider.html">Register as Rider</a> |
        <a href="../drivers/driver.html">Register as Driver</a> |
        <a href="../clients/client.html">Register as Client</a>
      </div>
    </form>
  </div>

  <script src="login.js"></script>
</body>
</html>
