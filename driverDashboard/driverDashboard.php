<?php
// driverDashboard.php
// Protected driver dashboard with session authentication

// Include session configuration
require_once __DIR__ . '/../drivers/session_config.php';

// Check if user is logged in and is a driver
if (!isset($_SESSION['loggedin']) || $_SESSION['user_type'] !== 'driver') {
    header("Location: ../drivers/driver_login.html"); // Adjust if needed
    exit();
}

// Check session timeout (30 minutes)
if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > 1800)) {
    // Session expired, destroy it and redirect to login
    session_unset();
    session_destroy();
    header("Location: ../login/login.html");
    exit();
}

// Update session time to extend timeout with each activity
$_SESSION['login_time'] = time();

// Get driver data from session
$username = htmlspecialchars($_SESSION['username']);
$driver_id = $_SESSION['driver_id'];
$profile_photo = $_SESSION['profile_photo'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Driver Dashboard - Campus Link</title>

  <!-- Leaflet CSS -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />

  <!-- AOS CSS -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" rel="stylesheet">

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <link rel="icon" type="image/x-icon" href="images/logo.png">

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- FontAwesome Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <script src="driverDashboard.js" defer></script>

  <!-- Custom CSS -->
  <link rel="stylesheet" href="driverDashboard.css">
</head>

<body>
<div class="d-flex" id="wrapper">
  <!-- Sidebar -->
  <aside class="bg-dark text-white sidebar p-3" id="sidebar">
    <div class="mb-4 d-flex align-items-center flex-column text-center">
      <img src="images/logo.png" class="logo" alt="CampusLink Logo">
      <h5 class="company-name fw-bold mt-2">Campus Link</h5>
    </div>

    <ul class="nav flex-column">
      <li class="nav-item"><a href="#" class="nav-link text-white active">
        <i class="fas fa-home me-2"></i> Home</a></li>
      <li class="nav-item"><a href="#maps" class="nav-link text-white">
        <i class="fas fa-map me-2"></i> Maps</a></li>
      <li class="nav-item"><a href="#trips" class="nav-link text-white">
        <i class="fas fa-history me-2"></i> Trips</a></li>
      <li class="nav-item"><a href="#notifications" class="nav-link text-white">
        <i class="fas fa-bell me-2"></i> Notifications</a></li>
      <li class="nav-item"><a href="#ratings" class="nav-link text-white">
        <i class="fas fa-star me-2"></i> Ratings</a></li>
      <li class="nav-item"><a href="logout.php" class="nav-link text-white">
        <i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
    </ul>
  </aside>

  <!-- Page Content -->
  <main class="flex-grow-1">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm px-3">
      <button class="btn btn-outline-primary d-lg-none" id="menu-toggle">
        <i class="fas fa-bars"></i></button>

      <!-- Driver Greeting Section -->
      <header class="driver-header text-center py-4">
        <!-- Dynamic welcome message with driver's name from session -->
        <h2 id="welcome-text">Welcome back, <?php echo $username; ?> 👋</h2>
        <p id="current-date" class="text-muted"></p>
      </header>
    </nav>

    <div class="container-fluid p-4">
      <!-- Stats Cards -->
      <div class="row mb-4">
        <!-- Display Picture Card with actual uploaded photo -->
        <div class="col-12 col-md-4 mb-4">
          <div class="card text-center shadow-sm display-card">
            <!-- Dynamic profile photo from session (uploaded during registration) -->
            <img src="<?php echo $profile_photo; ?>" class="display-img img-fluid rounded-circle mt-3"
              alt="Driver Profile Photo" style="width: 150px; height: 150px; object-fit: cover;">

            <div class="card-body">
              <p class="card-text text-white fw-semibold">Display Picture</p>
              <!-- Display driver ID from session -->
              <p class="card-text text-white">Driver ID: <?php echo $driver_id; ?></p>
            </div>
          </div>
        </div>

        <div class="col-md-4">
          <!-- Ratings Card -->
          <div class="col-md-10 col-sm-8 mb-3">
            <div class="card shadow-sm ratings-card">
              <div class="card-body">
                <p class="card-text fs-4">4.8 ★</p>
                <h5 class="card-title fw-bold">Ratings</h5>
              </div>
            </div>
          </div>
        </div>

        <div class="col-md-4">
          <!-- Total Trips Card -->
          <div class="col-md-10 col-sm-8 mb-3">
            <div class="card shadow-sm trips-card">
              <div class="card-body">
                <p class="card-text fs-4">152</p>
                <h5 class="card-title fw-bold">Total Trips</h5>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Rest of the dashboard content remains the same -->
      <!-- Map Section -->
      <section id="maps" class="mb-4">
        <h2 data-aos="zoom-in">Live Location</h2>
        <div id="map" class="mb-3"></div>
        <div class="d-flex justify-content-between">
          <button class="btn btn-danger" title="Decline Request"> Decline </button>
          <button class="btn btn-success" title="Accept Request"> Accept </button>
          <button class="btn btn-primary" title="Call Client"> Call </button>
        </div>
      </section>

      <!-- Trips -->
      <section id="trips" class="mb-4">
        <h2 data-aos="zoom-in">Trips History</h2>
        <div class="card mb-2 p-3 shadow-sm d-flex flex-row align-items-center">
          <img src="client.jpg" class="rounded-circle me-3" width="60" height="60" alt="Client">
          <div>
            <p class="mb-0">Trip #001 - Timothy Osubert</p>
            <p class="mb-0">Distance: 5km | Fare: UGX 8,000 | ⭐⭐⭐⭐</p>
          </div>
        </div>
      </section>

      <!-- Notifications -->
      <section id="notifications" class="mb-4">
        <h2 data-aos="zoom-in">Notifications</h2>
        <ul class="list-group">
          <li class="list-group-item">📢 New Trip Request</li>
          <li class="list-group-item">🌦️ Weather: Sunny 27°C</li>
          <li class="list-group-item">💬 Message from Client</li>
        </ul>
      </section>

      <!-- Ratings -->
      <section id="ratings" class="mb-4">
        <h2 data-aos="zoom-in">My Ratings</h2>
        <canvas id="ratingsChart"></canvas>
      </section>
    </div>

    <!-- Footer -->
    <footer class="footer bg-dark text-light pt-5">
      <!-- Footer content remains the same -->
      <div class="container">
        <div class="row">
          <!-- Quick Links -->
          <div class="col-md-3 mb-3">
            <h5 data-aos="zoom-in">Quick Links</h5>
            <ul class="list-unstyled">
              <li><a href="#" class="footer-link" title="About">About</a></li>
              <li><a href="#" class="footer-link" title="Privacy">Privacy</a></li>
              <li><a href="#" class="footer-link" title="Terms & Conditions">Terms & Conditions</a></li>
            </ul>
          </div>

          <!-- Newsletter -->
          <div class="col-md-3 mb-3">
            <h5 data-aos="zoom-in">Newsletter</h5>
            <form>
              <div class="mb-2">
                <input type="email" class="form-control form-control-sm" placeholder="Your email">
              </div>
              <button type="submit" class="btn btn-primary btn-sm">Subscribe</button>
            </form>
          </div>

          <!-- App Downloads -->
          <div class="col-md-3 mb-3">
            <h5 data-aos="zoom-in">Download App</h5>
            <a href="#"><img src="images/apple_store.png" alt="Apple Store" title="Download on Apple store" class="store-img mb-2"></a>
            <br>
            <a href="#"><img src="images/playstore.png" alt="Google Play Store" title="Download on Play Store" class="store-img"></a>
          </div>

          <!-- Contact Info -->
          <div class="col-md-3 mb-3">
            <h5 data-aos="zoom-in">Contact Us</h5>
            <ul>
              <li><a href="tel:+256763343453" class="footer-link" title="Call Us"> +256 763 343 453</a></li>
              <li><a href="mailto:jobingetimothyosubert@gmail.com" class="footer-link" title="Email Us"> jobingetimothyosubert@gmail.com</a></li>
              <li><a href="https://mbararacity.go.ug/page/about-us" class="footer-link" title="Find Us"> Mbarara-City, Uganda </a></li>
            </ul>
          </div>
        </div>

        <div class="row mt-4">
          <div class="col text-center">
            <p class="mb-0">© 2025 CampusLink. All rights reserved.</p>
          </div>
        </div>
      </div>
    </footer>
  </main>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- AOS JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
<script>
  AOS.init();
</script>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script src="driverDashboard.js"></script>
</body>
</html>