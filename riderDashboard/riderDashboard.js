document.addEventListener("DOMContentLoaded", () => {
      // Sidebar toggle for small screens
      const menuToggle = document.getElementById("menu-toggle");
      const wrapper = document.getElementById("wrapper");
      menuToggle.addEventListener("click", function () {
      wrapper.classList.toggle("toggled"); // <-- use 'toggled' not 'active'
    });

      // Automatically update current date and live time based on device locale
        const currentDate = document.getElementById("current-date");

        function updateDateTime() {
          const now = new Date();

          // Localized date and time formatting
          const date = now.toLocaleDateString(undefined, {
            weekday: "long",
            year: "numeric",
            month: "long",
            day: "numeric",
          });

          // Automatically adapts to device’s 12/24-hour format
          const time = now.toLocaleTimeString(undefined, {
            hour: "2-digit",
            minute: "2-digit",
            second: "2-digit",
          });

          currentDate.textContent = `${date} | ${time}`;
        }

        // Run immediately and update every second
        updateDateTime();
        setInterval(updateDateTime, 1000);

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

// git config --global user.name "j-timothy1234"
// git config --global user.email "jobingetimothyosubert.com"


