<?php
// clientDashboard.php
// Include session configuration and check authentication
require_once __DIR__ . '/../sessions/session_config.php';

// Check if client is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['user_type'] !== 'client') {
    // Redirect to centralized login page
    header("Location: ../login/login.php");
    exit();
}

// Get client data from session
$username = htmlspecialchars($_SESSION['username']);
$client_id = $_SESSION['client_id'];
$profile_photo = $_SESSION['profile_photo'] ?? 'images/default_profile.png';
$email = $_SESSION['email'] ?? '';
$phone = $_SESSION['phone'] ?? '';
$gender = $_SESSION['gender'] ?? '';

// Ensure CSRF token exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
}
$csrf_token = $_SESSION['csrf_token'];
// Prefer thumbnail for header if it exists
$header_photo = $profile_photo;
// build possible thumb path (insert _thumb before extension)
$thumbCandidate = preg_replace('/(\.[^.]+)$/', '_thumb$1', $profile_photo);
if ($thumbCandidate && file_exists(__DIR__ . '/../' . $thumbCandidate)) {
    $header_photo = $thumbCandidate;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Dashboard - CampusLink</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="clientDashboard.css">
</head>
<body>
    <!-- Top Navigation Bar -->
    <nav class="navbar navbar-dark bg-primary navbar-expand-lg fixed-top">
        <div class="container-fluid">
            <!-- Profile Picture - Left -->
            <div class="navbar-brand d-flex align-items-center">
                <img src="<?php echo $header_photo; ?>" alt="Profile" class="profile-pic rounded-circle me-2">
            </div>
            
            <!-- Welcome Message & Time - Center -->
            <div class="navbar-text mx-auto text-center">
                <span class="welcome-message fw-bold text-white">
                    Welcome, <?php echo $username; ?>! | 
                    <span id="liveTime" class="fw-normal"></span>
                </span>
            </div>
            
            <!-- Empty div for balance -->
            <div></div>
        </div>
    </nav>

    <div class="container-fluid" style="margin-top: 80px;">
        <div class="row">
            <!-- Sidebar Navigation -->
            <div class="col-lg-2 col-md-3 sidebar bg-light">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="#" data-layer="order-now">
                            <i class="fas fa-motorcycle me-2"></i>ORDER NOW
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-layer="book-travel">
                            <i class="fas fa-calendar me-2"></i>BOOK TRAVEL
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-layer="delivery-services">
                            <i class="fas fa-shipping-fast me-2"></i>DELIVERY SERVICES
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-layer="personal-info">
                            <i class="fas fa-user me-2"></i>PERSONAL INFO
                        </a>
                    </li>
                    <li class="nav-item mt-auto">
                        <a class="nav-link text-danger" href="logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>LOGOUT
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Main Content Area with Layered Panels -->
            <div class="col-lg-10 col-md-9 main-content">
                
                <!-- LAYER 1: ORDER NOW (Default Visible Layer) -->
                <div id="order-now-layer" class="layer-panel active">
                    <!-- Transport Mode Selection -->
                    <div class="row mb-4">
                        <div class="col-md-6 text-center">
                            <button class="btn btn-outline-primary btn-lg transport-mode" data-mode="motorcycle">
                                <i class="fas fa-motorcycle me-2"></i>MotorCycle
                            </button>
                        </div>
                        <div class="col-md-6 text-center">
                            <button class="btn btn-outline-primary btn-lg transport-mode" data-mode="vehicle">
                                <i class="fas fa-car me-2"></i>Vehicle (Uber)
                            </button>
                        </div>
                    </div>

                    <!-- Order Form (Dynamic based on transport mode) -->
                    <div id="order-form" class="card mb-4" style="display: none;">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label">From</label>
                                    <input type="text" class="form-control" id="from-location" placeholder="Current location">
                                    <button class="btn btn-sm btn-outline-secondary mt-2" id="detect-location">
                                        <i class="fas fa-location-arrow"></i> Detect My Location
                                    </button>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">To</label>
                                    <input type="text" class="form-control" id="to-location" placeholder="Destination">
                                </div>
                            </div>
                            <div class="text-center mt-3">
                                <button class="btn btn-success" id="order-rider">
                                    <i class="fas fa-bolt"></i> Order Rider
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Map Section -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5>Live Location & Journey Map</h5>
                        </div>
                        <div class="card-body">
                            <div id="map" style="height: 400px;"></div>
                        </div>
                    </div>

                    <!-- Transport Animation -->
                    <div id="transport-animation" class="text-center mb-4" style="display: none;">
                        <div id="bike-animation" style="display: none;">
                            <i class="fas fa-motorcycle fa-3x text-primary animate-bike"></i>
                        </div>
                        <div id="car-animation" style="display: none;">
                            <i class="fas fa-car fa-3x text-success animate-car"></i>
                        </div>
                    </div>

                    <!-- Confirm Order Button -->
                    <div class="text-center mb-4">
                        <button id="confirm-order" class="btn btn-primary btn-lg" style="display: none;">
                            Confirm Order - <span id="order-price">UGX 0</span>
                        </button>
                    </div>

                    <!-- Trip History -->
                    <div class="card">
                        <div class="card-header">
                            <h5>Trip History</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Rider Type</th>
                                            <th>Plate Number</th>
                                            <th>Transport Agent</th>
                                            <th>Distance (Km)</th>
                                            <th>Fare (UGX)</th>
                                            <th>Date</th>
                                            <th>Comment</th>
                                        </tr>
                                    </thead>
                                    <tbody id="trip-history">
                                        <!-- Dynamic content from JavaScript -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Graphs Section -->
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6>Motorcycle Trips</h6>
                                </div>
                                <div class="card-body">
                                    <canvas id="bikeTripsChart"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6>Vehicle Trips</h6>
                                </div>
                                <div class="card-body">
                                    <canvas id="carTripsChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- LAYER 2: BOOK TRAVEL (Hidden by default) -->
                <div id="book-travel-layer" class="layer-panel">
                    <!-- Book Travel content will be loaded here dynamically -->
                </div>

                <!-- LAYER 3: DELIVERY SERVICES (Hidden by default) -->
                <div id="delivery-services-layer" class="layer-panel">
                    <!-- Delivery Services content will be loaded here dynamically -->
                </div>

                <!-- LAYER 4: PERSONAL INFO (Hidden by default) -->
                <div id="personal-info-layer" class="layer-panel">
                    <div class="card">
                        <div class="card-header">
                            <h5>Personal Information</h5>
                        </div>
                        <div class="card-body">
                            <form id="personal-info-form" enctype="multipart/form-data">
                                <input type="hidden" name="client_id" value="<?php echo htmlspecialchars($client_id); ?>">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

                                <div class="row mb-3">
                                    <div class="col-md-3 text-center">
                                        <img id="profilePreview" src="<?php echo $profile_photo; ?>" alt="Profile" class="profile-pic large rounded-circle mb-2">
                                        <div>
                                            <label for="profile_photo" class="form-label btn btn-sm btn-outline-secondary">Update profile photo</label>
                                            <input type="file" id="profile_photo" name="profile_photo" accept="image/*" style="display:none">
                                        </div>
                                    </div>
                                    <div class="col-md-9">
                                        <div class="mb-3">
                                            <label class="form-label">Username</label>
                                            <input type="text" class="form-control" name="username" id="username" value="<?php echo htmlspecialchars($username); ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Email</label>
                                            <input type="email" class="form-control" name="email" id="email" value="<?php echo htmlspecialchars($email); ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Phone Number</label>
                                            <input type="text" class="form-control" name="phone" id="phone" value="<?php echo htmlspecialchars($phone); ?>">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">New Password (leave blank to keep current)</label>
                                            <input type="password" class="form-control" name="password" id="password" placeholder="Enter new password">
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <button type="submit" class="btn btn-primary" id="saveProfile">Save changes</button>
                                            <a href="logout.php" id="logoutLink" class="btn btn-outline-danger">Logout</a>
                                        </div>
                                    </div>
                                </div>
                            </form>
                            <div id="personalInfoMessage" class="mt-3"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer bg-dark text-light mt-5 py-4">
        <div class="container">
            <div class="row">
                <!-- Quick Links -->
                <div class="col-md-3 mb-3">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="#" class="footer-link">About Us</a></li>
                        <li><a href="#" class="footer-link">Privacy</a></li>
                        <li><a href="#" class="footer-link">Terms & Conditions</a></li>
                    </ul>
                </div>

                <!-- Newsletter -->
                <div class="col-md-3 mb-3">
                    <h5>Newsletter</h5>
                    <form id="newsletter-form">
                        <div class="mb-2">
                            <input type="email" class="form-control form-control-sm" placeholder="Your email" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">Subscribe</button>
                    </form>
                </div>

                <!-- App Downloads -->
                <div class="col-md-3 mb-3">
                    <h5>Download App</h5>
                    <a href="#" class="d-block mb-2">
                        <img src="images/apple_store.png" alt="Apple Store" class="store-img" style="max-width: 120px;">
                    </a>
                    <a href="#" class="d-block">
                        <img src="images/playstore.png" alt="Google Play Store" class="store-img" style="max-width: 120px;">
                    </a>
                </div>

                <!-- Contact Info -->
                <div class="col-md-3 mb-3">
                    <h5>Contact Us</h5>
                    <ul class="list-unstyled">
                        <li><a href="tel:+256763343453" class="footer-link">+256 763 343 453</a></li>
                        <li><a href="mailto:jobingetimothyosubert@gmail.com" class="footer-link">jobingetimothyosubert@gmail.com</a></li>
                        <li><a href="#" class="footer-link">Mbarara City, Uganda</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="row mt-4">
                <div class="col text-center">
                    <p class="mb-0">&copy; 2024 CampusLink. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- JavaScript Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    
    <!-- Custom JavaScript -->
    <script src="clientDashboard.js"></script>
</body>
</html>