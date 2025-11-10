<?php
// driverDashboard.php
// Protected driver dashboard with session authentication

// Include session configuration and check authentication
require_once __DIR__ . '/../sessions/session_config.php';

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

// Get driver data from session safely (avoid undefined variable warnings)
$username = isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Guest';
$driver_id = $_SESSION['driver_id'] ?? 'N/A';
$profile_photo = $_SESSION['profile_photo'] ?? 'images/default_profile.png';

?>

<!DOCTYPE html>

<html lang="en">

<head>

  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <title> <?php echo $username; ?> Dashboard</title>

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

      <li class="nav-item"><a href="#" data-layer="home" class="nav-link text-white active">
        <i class="fas fa-home me-2"></i> Home</a></li>

      <li class="nav-item"><a href="#" data-layer="maps" class="nav-link text-white">
        <i class="fas fa-map me-2"></i> Maps</a></li>

      <li class="nav-item"><a href="#" data-layer="trips" class="nav-link text-white">
        <i class="fas fa-history me-2"></i> Trips</a></li>

      <li class="nav-item"><a href="#" data-layer="notifications" class="nav-link text-white">
        <i class="fas fa-bell me-2"></i> Notifications</a></li>

      <li class="nav-item"><a href="#" data-layer="ratings" class="nav-link text-white">
        <i class="fas fa-star me-2"></i> Ratings</a></li>

      <li class="nav-item"><a href="../drivers/logout.php" class="nav-link text-white">
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
      <!-- MAP LAYER -->
      <div id="maps-layer" class="layer-panel mb-4">
        <div class="card mb-4">
          <div class="card-header">
            <h5>Live Location & Journey Map</h5>
          </div>
          <div class="card-body">
            <div id="map" style="height: 500px;"></div>
            <div class="d-flex justify-content-between mt-3">
              <button class="btn btn-danger" title="Decline Request"> Decline </button>
              <button class="btn btn-success" title="Accept Request"> Accept </button>
              <button class="btn btn-primary" title="Call Client"> Call </button>
            </div>
          </div>
        </div>
      </div>

      <!-- TRIPS LAYER -->
      <div id="trips-layer" class="layer-panel mb-4">
        <h2 data-aos="zoom-in">Trips History</h2>
        <?php
        // Fetch recent bookings for this driver
        require_once __DIR__ . '/../db_connect.php';
        $db = new Database(); $conn = $db->getConnection();
        $stmt = $conn->prepare('SELECT b.id, b.client_id, b.pickup, b.destination, b.estimate, b.status, b.created_at, c.Username as client_name FROM bookings b LEFT JOIN clients c ON c.Client_ID = b.client_id WHERE b.agent_id = ? AND b.status IN ("accepted","completed") ORDER BY b.created_at DESC LIMIT 10');
        $stmt->bind_param('s', $driver_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                $tripId = htmlspecialchars($row['id']);
                $clientName = htmlspecialchars($row['client_name'] ?? $row['client_id']);
                $pickup = htmlspecialchars($row['pickup'] ?? '');
                $destination = htmlspecialchars($row['destination'] ?? '');
                $estimate = htmlspecialchars($row['estimate'] ?? '');
                $status = htmlspecialchars($row['status'] ?? '');
                $created = htmlspecialchars($row['created_at'] ?? '');
                echo "<div class=\"card mb-2 p-3 shadow-sm d-flex flex-row align-items-center\">";
                echo "<img src=\"images/client.jpg\" class=\"rounded-circle me-3\" width=\"60\" height=\"60\" alt=\"Client\">";
                echo "<div><p class=\"mb-0\">Trip #{$tripId} - {$clientName}</p>";
                echo "<p class=\"mb-0\">{$pickup} → {$destination} | Fare: {$estimate} | Status: {$status} | {$created}</p></div>";
                echo "</div>";
            }
        } else {
            echo '<div class="card mb-2 p-3 shadow-sm"><div>No recent trips yet</div></div>';
        }
        ?>

      </div>

      <!-- NOTIFICATIONS LAYER -->
      <div id="notifications-layer" class="layer-panel mb-4">
        <h2 data-aos="zoom-in">Notifications</h2>
        <ul class="list-group">
          <li class="list-group-item">📢 New Trip Request</li>
          <li class="list-group-item">🌦️ Weather: Sunny 27°C</li>
          <li class="list-group-item">💬 Message from Client</li>
        </ul>
      </div>

      <!-- RATINGS LAYER -->
      <div id="ratings-layer" class="layer-panel mb-4">
        <h2 data-aos="zoom-in">My Ratings</h2>
        <canvas id="ratingsChart"></canvas>
      </div>

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
            <li class="footer-link-item">
              <a href="#" class="footer-link" title="About" data-content="about">About Us</a>
                <div class="popup-content" id="about-popup">
                  <div class="popup-header">
                    <h6>About CampusLink</h6>
                    <span class="popup-close">&times;</span>
                  </div>

                  <div class="popup-body">
                    <p><strong>Our Mission:</strong> Connecting students & the community with reliable
                    transport agents for safe & affordable mobility solutions.</p>
                    <p><strong>Services:</strong> Instant rides, scheduled travel, and delivery services for students and community.</p>
                    <p><strong>Safety First:</strong> All our transport agents are thoroughly verified and rated by users.</p>
                  </div>
                </div>
            </li>
    
            <li class="footer-link-item">
              <a href="#" class="footer-link" title="Privacy" data-content="privacy">Privacy</a>
                <div class="popup-content" id="privacy-popup">
                  <div class="popup-header">
                    <h6>Privacy Policy</h6>
                    <span class="popup-close">&times;</span>
                  </div>

                  <div class="popup-body">
                    <p><strong>Data Collection:</strong> We collect only necessary information for service delivery.</p>
                    <p><strong>Location Data:</strong> Used only when you request rides for accurate service.</p>
                    <p><strong>Data Protection:</strong> All your information is encrypted and securely stored.</p>
                    <p><strong>Your Rights:</strong> You can access, correct, or delete your data anytime.</p>
                  </div>
                </div>
            </li>
    
            <li class="footer-link-item">
              <a href="#" class="footer-link" title="Terms & Conditions" data-content="terms">Terms & Conditions</a>
                <div class="popup-content" id="terms-popup">
                  <div class="popup-header">
                    <h6>Terms & Conditions</h6>
                    <span class="popup-close">&times;</span>
                  </div>

                  <div class="popup-body">
                    <p><strong>User Responsibilities:</strong> Provide accurate info and respect transport agents.</p>
                    <p><strong>Booking Policy:</strong> Cancel within 3 minutes for instant ride refunds.</p>
                    <p><strong>Safety:</strong> Verify agent details and share ride info with trusted contacts.</p>
                    <p><strong>Payments:</strong> All rides must be prepaid or cash confirmed before service.</p>
                  </div>
                </div>
            </li>
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

            <a href="#"><img src="images/apple_store.png" alt="Apple Store" title="Download on Apple store"
              class="store-img mb-2"></a>

            <br>

            <a href="#"><img src="images/playstore.png" alt="Google Play Store" title="Download on Play Store"
              class="store-img"></a>

          </div>

          <!-- Contact Info -->
          <div class="col-md-3 mb-3">

            <h5 data-aos="zoom-in">Contact Us</h5>

            <ul>

              <li><a href="tel:+256763343453" class="footer-link" title="Call Us"> +256 763 343 453</a></li>
              <li><a href="mailto:jobingetimothyosubert@gmail.com" class="footer-link" title="Email Us">
                jobingetimothyosubert@gmail.com</a></li>

              <li><a href="https://www.google.com/maps/search/kihumuro+campus/@-0.5955312,30.599584,18z?entry=ttu&g_ep=EgoyMDI1MTAyMC4wIKXMDSoASAFQAw%3D%3D" 
                class="footer-link" title="Find Us on Maps" target="_blank" rel="noopener noreferrer">
                Mbarara-City, Uganda
              </a></li>

            </ul>

          </div>

        </div>

        <div class="row mt-4">

          <div class="col text-center">

            <p class="mb-0">&copy; 2025 CampusLink. All rights reserved.</p>

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