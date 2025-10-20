document.addEventListener("DOMContentLoaded", () => {
  // Initialize Leaflet Map
  const map = L.map('map').setView([0.3476, 32.5825], 13); // Default Kampala coords

  // Add OpenStreetMap tiles
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors'
  }).addTo(map);

  // Marker for rider
  let riderMarker = L.marker([0.3476, 32.5825]).addTo(map)
    .bindPopup("You are here")
    .openPopup();

  // Try to get real location
  if (navigator.geolocation) {
    navigator.geolocation.watchPosition(
      (pos) => {
        const lat = pos.coords.latitude;
        const lng = pos.coords.longitude;

        // Update map view & marker
        map.setView([lat, lng], 15);
        riderMarker.setLatLng([lat, lng]);
      },
      (err) => {
        console.error("Geolocation error:", err);
        alert("Unable to fetch location. Please enable GPS.");
      },
      { enableHighAccuracy: true }
    );
  } else {
    alert("Geolocation not supported by this browser.");
  }

  // Simulated dynamic order
  let currentOrder = null;

  function updateOrderStatus() {
    const orderSection = document.querySelector(".card:nth-of-type(3) p");
    if (currentOrder) {
      orderSection.textContent = `Pickup: ${currentOrder.client} | Destination: ${currentOrder.destination}`;
    } else {
      orderSection.textContent = "No active orders at the moment.";
    }
  }

  // Example: simulate new order after 3s
  setTimeout(() => {
    currentOrder = { client: "Alice B.", destination: "Campus Gate" };
    updateOrderStatus();
  }, 3000);

  updateOrderStatus();
});

// Sidebar toggle for small screens
document.getElementById("menu-toggle").addEventListener("click", function () {
  document.getElementById("wrapper").classList.toggle("toggled");
});

// Ratings Chart
const ctx = document.getElementById('ratingsChart').getContext('2d');
new Chart(ctx, {
  type: 'bar',
  data: {
    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
    datasets: [{
      label: 'Ratings',
      data: [4, 5, 4.5, 3.8, 5, 4.2, 4.7],
      backgroundColor: 'rgba(13, 110, 253, 0.7)'
    }]
  }
});

