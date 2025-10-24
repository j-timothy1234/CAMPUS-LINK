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

        // -----------------------------
        // JLayered sidebar behavior (rider)
        // -----------------------------
        try {
          const layerLinks = document.querySelectorAll('#sidebar a[data-layer]');

          function showLayer(name) {
            const names = ['maps', 'trips', 'notifications', 'ratings'];
            if (name === 'home') {
              names.forEach(n => { const el = document.getElementById(`${n}-layer`); if (el) el.style.display = 'none'; });
              window.scrollTo({ top: 0, behavior: 'smooth' });
            } else {
              names.forEach(n => {
                const el = document.getElementById(`${n}-layer`);
                if (!el) return;
                el.style.display = (n === name) ? '' : 'none';
              });
            }

            layerLinks.forEach(l => {
              if (l.getAttribute('data-layer') === name) l.classList.add('active'); else l.classList.remove('active');
            });
          }

          layerLinks.forEach(link => {
            link.addEventListener('click', function (e) {
              const layer = this.getAttribute('data-layer');
              if (!layer) return; // leave normal navigation for links without data-layer
              e.preventDefault();
              showLayer(layer);
            });
          });

          const initial = document.querySelector('#sidebar a[data-layer].active');
          if (initial) showLayer(initial.getAttribute('data-layer')); else showLayer('home');
        } catch (e) { console.warn('Layered sidebar init failed', e); }


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
          iconUrl: "images/bike-icon.png", // 🏍️ place your bike icon image in the 'images' folder
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

  // ====== Notifications via WebSocket with polling fallback ======
  let activeNotification = null;
  let wsConnected = false;
  let wsFallbackInterval = null;

  async function initSocket() {
    try {
      const tokenRes = await fetch('../clientDashboard/ws_token.php');
      const tokenJson = await tokenRes.json();
      if (!tokenJson.success) throw new Error('token failed');
      const token = tokenJson.token;
      const agentId = tokenJson.agent_id;
      const role = tokenJson.role;
      const wsUrl = `ws://127.0.0.1:8081/?token=${token}&role=${role}&agent_id=${agentId}`;
      const ws = new WebSocket(wsUrl);
      ws.addEventListener('open', () => { wsConnected = true; if (wsFallbackInterval) { clearInterval(wsFallbackInterval); wsFallbackInterval = null; } });
      ws.addEventListener('message', (ev) => {
        try { const msg = JSON.parse(ev.data); if (msg.type === 'booking_request') { if (!activeNotification) { activeNotification = msg.data; showNotificationOverlay(activeNotification); } } } catch(e){console.error(e)}
      });
      ws.addEventListener('close', () => { wsConnected = false; startPolling(); });
      ws.addEventListener('error', () => { wsConnected = false; startPolling(); });
    } catch (e) {
      console.warn('WS init failed, falling back to polling', e);
      startPolling();
    }
  }

  function startPolling() {
    if (wsFallbackInterval) return;
    wsFallbackInterval = setInterval(fetchNotifications, 5000);
    fetchNotifications();
  }

  async function fetchNotifications() {
    try {
      const res = await fetch('../clientDashboard/get_notifications.php');
      const data = await res.json();
      if (data.success && data.notifications && data.notifications.length > 0) {
        if (!activeNotification) {
          activeNotification = data.notifications[0];
          showNotificationOverlay(activeNotification);
        }
      }
    } catch (e) {
      console.error('Failed to fetch notifications', e);
    }
  }
  function showNotificationOverlay(n) {
    let overlay = document.getElementById('request-overlay');
    if (!overlay) {
      overlay = document.createElement('div');
      overlay.id = 'request-overlay';
      overlay.style.position = 'absolute';
      overlay.style.top = '10px';
      overlay.style.right = '10px';
      overlay.style.zIndex = 9999;
      overlay.style.background = 'white';
      overlay.style.padding = '12px';
      overlay.style.borderRadius = '8px';
      overlay.style.boxShadow = '0 2px 8px rgba(0,0,0,0.15)';
      document.body.appendChild(overlay);
    }

    overlay.innerHTML = `
      <div><strong>New Request</strong></div>
      <div>Client: ${n.client_name || n.client_id}</div>
      <div>Pickup: ${n.pickup}</div>
      <div>Destination: ${n.destination}</div>
      <div style="margin-top:8px;">
        <button id="decline-btn" class="btn btn-danger btn-sm">Decline</button>
        <button id="accept-btn" class="btn btn-success btn-sm ms-2">Accept</button>
        <a href="tel:${n.client_phone || '#'}" id="call-btn" class="btn btn-primary btn-sm ms-2">Call</a>
      </div>
    `;

    if (n.pickup_lat && n.pickup_lng) {
      map.setView([parseFloat(n.pickup_lat), parseFloat(n.pickup_lng)], 15);
      L.marker([parseFloat(n.pickup_lat), parseFloat(n.pickup_lng)]).addTo(map).bindPopup('Client pickup').openPopup();
    }

    document.getElementById('decline-btn').addEventListener('click', () => respondNotification(n.notification_id, 'decline'));
    document.getElementById('accept-btn').addEventListener('click', () => respondNotification(n.notification_id, 'accept'));
  }

  async function respondNotification(notification_id, action) {
    try {
      const res = await fetch('../clientDashboard/respond_booking.php', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ notification_id, action })
      });
      const data = await res.json();
      if (data.success) {
        const ov = document.getElementById('request-overlay'); if (ov) ov.remove();
        activeNotification = null;
        alert(data.message || 'Updated');
      } else {
        alert(data.message || 'Failed');
      }
    } catch (e) { console.error(e); alert('Request failed'); }
  }

  // start polling immediately to ensure notifications appear
  startPolling();
  // keep an interval fallback as well
  setInterval(fetchNotifications, 5000);
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

// Enhanced popup functionality
document.addEventListener('DOMContentLoaded', function() {
    // Close popup when clicking close button
    document.querySelectorAll('.popup-close').forEach(closeBtn => {
        closeBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            const popup = this.closest('.popup-content');
            popup.style.opacity = '0';
            popup.style.visibility = 'hidden';
        });
    });

    // Close popup when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.footer-link-item')) {
            document.querySelectorAll('.popup-content').forEach(popup => {
                popup.style.opacity = '0';
                popup.style.visibility = 'hidden';
            });
        }
    });

    // Mobile touch support
    let touchTimer;
    document.querySelectorAll('.footer-link').forEach(link => {
        link.addEventListener('touchstart', function(e) {
            e.preventDefault();
            const popup = this.nextElementSibling;
            
            // Close other popups
            document.querySelectorAll('.popup-content').forEach(p => {
                if (p !== popup) {
                    p.style.opacity = '0';
                    p.style.visibility = 'hidden';
                }
            });
            
            // Toggle current popup
            if (popup.style.visibility === 'visible') {
                popup.style.opacity = '0';
                popup.style.visibility = 'hidden';
            } else {
                popup.style.opacity = '1';
                popup.style.visibility = 'visible';
            }
        });
    });

    // Keyboard accessibility
    document.querySelectorAll('.footer-link').forEach(link => {
        link.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                const popup = this.nextElementSibling;
                
                document.querySelectorAll('.popup-content').forEach(p => {
                    if (p !== popup) {
                        p.style.opacity = '0';
                        p.style.visibility = 'hidden';
                    }
                });
                
                popup.style.opacity = '1';
                popup.style.visibility = 'visible';
            }
        });
    });
});


