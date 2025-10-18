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


        // =======================================================
        // LEAFLET LIVE LOCATION TRACKER WITH BIKE ICON, SPEED & DIRECTION
        // =======================================================

        // Default map center (e.g., Jinja, Uganda)
        const defaultCoords = [0.3476, 32.5825];

        // Initialize Leaflet map
        const map = L.map("map").setView(defaultCoords, 13);

        // Add OpenStreetMap tiles
        L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
          attribution: "&copy; OpenStreetMap contributors"
        }).addTo(map);

        // Custom rotating bike icon

        const bikeIcon = L.icon({
          iconUrl: "images/car-icon.jpeg",
          iconSize: [40, 40],              // size of the icon
          iconAnchor: [20, 20],            // center the icon
          popupAnchor: [0, -20]            // popup position
        });

        // Marker for rider with rotation
        let riderMarker = L.marker(defaultCoords, {
          icon: bikeIcon,
          rotationAngle: 0,
          rotationOrigin: "center center"
        })
        .addTo(map)
        .bindPopup("Fetching your location...")
        .openPopup();

        // Route tracking trail (polyline)
        let routeCoords = [];
        let routeLine = L.polyline(routeCoords, {
          color: "#22a6b3",
          weight: 4,
          opacity: 0.8
        }).addTo(map);

        // Info Box for Speed & Direction

        const infoBox = L.control({ position: "bottomleft" });
        infoBox.onAdd = function () {
          this._div = L.DomUtil.create("div", "info-box");
          this.update(0, 0);
          return this._div;
        };
        infoBox.update = function (speed, heading) {
          this._div.innerHTML = `
            <b>Speed:</b> ${speed.toFixed(1)} km/h<br>
            <b>Direction:</b> ${heading.toFixed(0)}°`;
        };
        infoBox.addTo(map);

        // Helper Functions

        // Convert speed (m/s → km/h)
        function toKmh(speed) {
          return speed * 3.6;
        }

        // Update position, marker, and data
        function updatePosition(lat, lng, accuracy, speed, heading) {
          map.setView([lat, lng], 15);

          // Update marker position & rotation
          riderMarker.setLatLng([lat, lng]);
          riderMarker.setRotationAngle(heading || 0);

          // Update popup info
          const message = `
            📍 <b>Accuracy:</b> ±${Math.round(accuracy)}m<br>
            🏍️ <b>Speed:</b> ${speed.toFixed(1)} km/h<br>
            🧭 <b>Heading:</b> ${heading.toFixed(0)}°`;
            riderMarker.setPopupContent(message).openPopup();

            // Update route trail
            routeCoords.push([lat, lng]);
            routeLine.setLatLngs(routeCoords);
            if (routeCoords.length > 100) routeCoords.shift();

            // Update info box
            infoBox.update(speed, heading);
        }

        // Fallback: IP-based geolocation (for laptops)

        async function useIPGeolocation() {
          try {
            const res = await fetch("https://ipapi.co/json/");
            const data = await res.json();
            const lat = data.latitude;
            const lng = data.longitude;
            updatePosition(lat, lng, 5000, 0, 0);
            console.log("✅ Using IP-based location fallback");
        } catch (e) {
            console.error("❌ IP location failed:", e);
            alert("Unable to determine your location.");
        }
      }

      // Main Geolocation Logic

      const geoOptions = {
        enableHighAccuracy: true,
        timeout: 20000,
        maximumAge: 0
      };

      if (navigator.geolocation) {
        // Initial location
        navigator.geolocation.getCurrentPosition(
          (pos) => {
            const { latitude, longitude, accuracy, speed, heading } = pos.coords;
            updatePosition(latitude, longitude, accuracy, toKmh(speed || 0), heading || 0);
          },
          async (err) => {
            console.warn("⚠️ Geolocation failed:", err.message);
            await useIPGeolocation();
          },
          geoOptions
        );

        // Real-time tracking
        navigator.geolocation.watchPosition(
          (pos) => {
            const { latitude, longitude, accuracy, speed, heading } = pos.coords;
            updatePosition(latitude, longitude, accuracy, toKmh(speed || 0), heading || 0);
          },
          async (err) => {
            console.warn("⚠️ Realtime update error:", err.message);
            await useIPGeolocation();
          },
          geoOptions
        );
        } else {
            useIPGeolocation();
        }

        // Custom Styling for Info Box

        const style = document.createElement("style");
        style.innerHTML = `
          .info-box {
              background: rgba(0, 0, 0, 0.7);
              color: white;
              padding: 10px 15px;
              border-radius: 8px;
              font-size: 14px;
              line-height: 1.5;
              box-shadow: 0 0 8px rgba(0,0,0,0.4);
        }`;
        document.head.appendChild(style);

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
      data: [4, 5, 4.5, 3.0, 5, 4.5, 2.5],
      backgroundColor: 'rgba(13, 110, 253, 0.7)'
    }]
  }
});


