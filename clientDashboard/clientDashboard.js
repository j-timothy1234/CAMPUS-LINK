// clientDashboard.js
class ClientDashboard {
    constructor() {
        this.currentLayer = 'order-now';
        this.currentTransportMode = null;
        this.map = null;
        this.userMarker = null;
        this.routeLayer = null;
        this.selectedAgent = null;
        
        this.init();
    }

    init() {
        this.initializeLiveTime();
        this.initializeEventListeners();
        this.initializeMap();
        this.loadTripHistory();
        this.initializeCharts();
        this.initializeBookingAndDelivery();
    }

    // Live Time Update
    initializeLiveTime() {
        const updateTime = () => {
            const now = new Date();
            const options = { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            };
            document.getElementById('liveTime').textContent = now.toLocaleDateString('en-US', options);
        };
        
        updateTime();
        setInterval(updateTime, 1000);
    }

    // Event Listeners
    initializeEventListeners() {
        // Sidebar Navigation
        document.querySelectorAll('.sidebar .nav-link').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const targetLayer = link.getAttribute('data-layer');
                this.switchLayer(targetLayer);
            });
        });

        // Transport Mode Selection
        document.querySelectorAll('.transport-mode').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const mode = e.currentTarget.getAttribute('data-mode');
                this.selectTransportMode(mode);
            });
        });

        // Location Detection
        document.getElementById('detect-location').addEventListener('click', () => {
            this.detectUserLocation();
        });

        // Order Rider
        document.getElementById('order-rider').addEventListener('click', () => {
            this.calculateRoute();
        });

        // Confirm Order
        document.getElementById('confirm-order').addEventListener('click', () => {
            this.confirmOrder();
        });

        // Profile Picture Upload
        this.initializeProfileUpload();
    }

    // JLayeredPane-inspired Layer Switching
    switchLayer(layerName) {
        // Hide all layers
        document.querySelectorAll('.layer-panel').forEach(layer => {
            layer.classList.remove('active');
        });

        // Remove active class from all nav links
        document.querySelectorAll('.sidebar .nav-link').forEach(link => {
            link.classList.remove('active');
        });

        // Show target layer
        const targetLayer = document.getElementById(`${layerName}-layer`);
        if (targetLayer) {
            targetLayer.classList.add('active');
            
            // Add active class to clicked nav link
            document.querySelector(`[data-layer="${layerName}"]`).classList.add('active');
            
            this.currentLayer = layerName;
            
            // Load layer-specific content
            this.loadLayerContent(layerName);
        }
    }

    // Load dynamic content for each layer
    loadLayerContent(layerName) {
        switch(layerName) {
            case 'book-travel':
                this.loadBookTravelContent();
                break;
            case 'delivery-services':
                this.loadDeliveryServicesContent();
                break;
            case 'personal-info':
                this.loadPersonalInfoContent();
                break;
        }
    }

    // Transport Mode Selection
    selectTransportMode(mode) {
        this.currentTransportMode = mode;
        
        // Update button states
        document.querySelectorAll('.transport-mode').forEach(btn => {
            btn.classList.remove('active', 'btn-primary');
            btn.classList.add('btn-outline-primary');
        });
        
        const activeBtn = document.querySelector(`[data-mode="${mode}"]`);
        activeBtn.classList.remove('btn-outline-primary');
        activeBtn.classList.add('active', 'btn-primary');
        
        // Show order form
        document.getElementById('order-form').style.display = 'block';
        
        // Update order button text
        document.getElementById('order-rider').innerHTML = 
            `<i class="fas fa-bolt"></i> Order ${mode === 'motorcycle' ? 'Rider' : 'Driver'}`;
        
        // Show appropriate animation
        this.showTransportAnimation(mode);
    }

    // Transport Animation
    showTransportAnimation(mode) {
        const animationContainer = document.getElementById('transport-animation');
        const bikeAnimation = document.getElementById('bike-animation');
        const carAnimation = document.getElementById('car-animation');
        
        animationContainer.style.display = 'block';
        bikeAnimation.style.display = mode === 'motorcycle' ? 'block' : 'none';
        carAnimation.style.display = mode === 'vehicle' ? 'block' : 'none';
        
        // Show confirm order button
        document.getElementById('confirm-order').style.display = 'block';
    }

    // Map Initialization
    initializeMap() {
        this.map = L.map('map').setView([0.3476, 32.5825], 13); // Default to Kampala
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(this.map);
    }

    // Location Detection
    detectUserLocation() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    
                    // Update map view
                    this.map.setView([lat, lng], 15);
                    
                    // Add/update user marker
                    if (this.userMarker) {
                        this.map.removeLayer(this.userMarker);
                    }
                    
                    this.userMarker = L.marker([lat, lng])
                        .addTo(this.map)
                        .bindPopup('Your current location')
                        .openPopup();
                    
                    // Update from location field
                    this.reverseGeocode(lat, lng);
                },
                (error) => {
                    alert('Unable to detect your location. Please enter manually.');
                    console.error('Geolocation error:', error);
                }
            );
        } else {
            alert('Geolocation is not supported by your browser.');
        }
    }

    // Reverse Geocoding
    reverseGeocode(lat, lng) {
        // Using OpenStreetMap Nominatim for reverse geocoding
        fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`)
            .then(response => response.json())
            .then(data => {
                if (data.display_name) {
                    document.getElementById('from-location').value = data.display_name;
                }
            })
            .catch(error => {
                console.error('Reverse geocoding error:', error);
            });
    }

    // Route Calculation
    calculateRoute() {
        const fromLocation = document.getElementById('from-location').value;
        const toLocation = document.getElementById('to-location').value;

        if (!toLocation) {
            alert('Please enter destination.');
            return;
        }

        // Determine start coordinates: prefer detected position if available
        const startPromise = new Promise((resolve, reject) => {
            if (this.userMarker && this.userMarker.getLatLng) {
                resolve(this.userMarker.getLatLng());
            } else if (fromLocation) {
                this.geocodeAddress(fromLocation).then(coords => resolve(coords)).catch(reject);
            } else {
                // fallback to map center
                resolve(this.map.getCenter());
            }
        });

        // Geocode destination then route
        Promise.all([startPromise, this.geocodeAddress(toLocation)])
            .then(([startLatLng, destLatLng]) => {
                this.calculateAndDrawRoute(startLatLng, destLatLng, this.currentTransportMode || 'motorcycle');
            }).catch(err => {
                console.error('Routing error', err);
                alert('Unable to calculate route. Please refine the addresses and try again.');
            });
    }

    // Geocode an address string to a Leaflet latlng using Nominatim
    geocodeAddress(query) {
        return new Promise((resolve, reject) => {
            const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}`;
            fetch(url)
                .then(r => r.json())
                .then(results => {
                    if (!results || results.length === 0) return reject('No results');
                    const first = results[0];
                    const lat = parseFloat(first.lat);
                    const lon = parseFloat(first.lon);
                    resolve(L.latLng(lat, lon));
                }).catch(err => reject(err));
        });
    }

    // Calculate route using OSRM and draw it on the map
    calculateAndDrawRoute(startLatLng, destLatLng, mode) {
        // Remove previous route layers
        if (this.routeLayer) { this.map.removeLayer(this.routeLayer); this.routeLayer = null; }
        if (this.routeMarker) { this.map.removeLayer(this.routeMarker); this.routeMarker = null; }

        const start = `${startLatLng.lng},${startLatLng.lat}`;
        const end = `${destLatLng.lng},${destLatLng.lat}`;
        // OSRM uses driving profile. For motorcycle we'll use driving profile as fallback.
        const osrmUrl = `https://router.project-osrm.org/route/v1/driving/${start};${end}?overview=full&geometries=geojson&steps=false`;

        fetch(osrmUrl).then(r => r.json()).then(data => {
            if (!data || !data.routes || data.routes.length === 0) throw new Error('No route');
            const route = data.routes[0];
            const geojson = route.geometry;

            // Draw route polyline
            this.routeLayer = L.geoJSON(geojson, {style: {color: '#007bff', weight: 5, opacity: 0.8}}).addTo(this.map);

            // Add start and end markers with icons depending on mode
            const startIcon = L.divIcon({className: 'start-icon', html: `<i class="fas fa-map-marker-alt" style="color:green;font-size:18px"></i>`});
            const endIcon = L.divIcon({className: 'end-icon', html: `<i class="fas ${mode === 'vehicle' ? 'fa-car' : 'fa-motorcycle'}" style="color:#d9534f;font-size:18px"></i>`});
            L.marker([startLatLng.lat, startLatLng.lng], {icon: startIcon}).addTo(this.map);
            this.routeMarker = L.marker([destLatLng.lat, destLatLng.lng], {icon: endIcon}).addTo(this.map);

            // Fit map to route
            const bounds = this.routeLayer.getBounds();
            this.map.fitBounds(bounds.pad(0.2));

            // Distance in meters
            const distanceMeters = route.distance || 0;
            const distanceKm = (distanceMeters / 1000).toFixed(2);

            // Bearing from start to end
            const bearing = this.calculateBearing(startLatLng.lat, startLatLng.lng, destLatLng.lat, destLatLng.lng);

            // Update info control
            if (!this.routeInfoControl) this.addRouteInfoControl();
            this.updateRouteInfo({distanceKm, bearing});

            // store last route coords for booking payloads
            this.lastStartLatLng = startLatLng;
            this.lastDestLatLng = destLatLng;

            // Update order price display (distanceKm is string)
            const numericKm = parseFloat(distanceKm);
            const price = this.calculatePrice(numericKm, mode === 'motorcycle' ? 'motorcycle' : 'vehicle');
            const priceEl = document.getElementById('order-price');
            if (priceEl) priceEl.textContent = `UGX ${price.toLocaleString()}`;

        }).catch(err => {
            console.error(err);
            alert('Routing failed.');
        });
    }

    // Compute bearing in degrees from start to end
    calculateBearing(lat1, lon1, lat2, lon2) {
        const toRad = Math.PI / 180;
        const toDeg = 180 / Math.PI;
        const dLon = (lon2 - lon1) * toRad;
        const y = Math.sin(dLon) * Math.cos(lat2 * toRad);
        const x = Math.cos(lat1 * toRad) * Math.sin(lat2 * toRad) - Math.sin(lat1 * toRad) * Math.cos(lat2 * toRad) * Math.cos(dLon);
        let brng = Math.atan2(y, x) * toDeg;
        brng = (brng + 360) % 360;
        return brng.toFixed(1);
    }

    // Add a small Leaflet control for route info in bottom-left
    addRouteInfoControl() {
        const InfoControl = L.Control.extend({
            onAdd: function(map) {
                const div = L.DomUtil.create('div', 'route-info p-2 bg-white');
                div.style.boxShadow = '0 1px 4px rgba(0,0,0,0.3)';
                div.style.borderRadius = '6px';
                div.style.fontSize = '13px';
                div.innerHTML = '<div id="routeInfoContent">Distance: -- km<br> Bearing: --°</div>';
                return div;
            }
        });
        this.routeInfoControl = new InfoControl({position: 'bottomleft'});
        this.routeInfoControl.addTo(this.map);
    }

    updateRouteInfo({distanceKm, bearing}) {
        const el = document.getElementById('routeInfoContent');
        if (el) el.innerHTML = `Distance: <strong>${distanceKm} km</strong><br/>Bearing: <strong>${bearing}°</strong>`;
    }

    calculatePrice(distance, mode) {
        // distance can be given in km (number) or if not numeric, fallback random
        const distKm = (typeof distance === 'number' && !isNaN(distance)) ? distance : (Math.random() * 20 + 5);
        const baseRate = mode === 'motorcycle' ? 1500 : 3000;
        const perKmRate = mode === 'motorcycle' ? 800 : 1500;
        return Math.round(baseRate + (distKm * perKmRate));
    }

    drawSimulatedRoute() {
        // Clear existing route
        if (this.routeLayer) {
            this.map.removeLayer(this.routeLayer);
        }
        
        // Get current map center
        const center = this.map.getCenter();
        
        // Create a simulated route (in real app, use proper routing service)
        const routeCoordinates = [
            [center.lat, center.lng],
            [center.lat + 0.01, center.lng + 0.01],
            [center.lat + 0.02, center.lng + 0.02]
        ];
        
        this.routeLayer = L.polyline(routeCoordinates, {color: 'blue'}).addTo(this.map);
        this.map.fitBounds(this.routeLayer.getBounds());
    }

    // Confirm Order
    confirmOrder() {
        const fromLocation = document.getElementById('from-location').value;
        const toLocation = document.getElementById('to-location').value;
        const price = document.getElementById('order-price').textContent;
        
        if (!fromLocation || !toLocation) {
            alert('Please calculate route first.');
            return;
        }
        
        const confirmation = confirm(
            `Confirm ${this.currentTransportMode} order?\n\n` +
            `From: ${fromLocation}\n` +
            `To: ${toLocation}\n` +
            `Price: ${price}\n\n` +
            `Click OK to confirm.`
        );
        
        if (confirmation) {
            alert('Order confirmed! Your rider/driver will arrive shortly.');
            this.saveTripToHistory();
            // send booking to server including coords if available
            const coordsPayload = {};
            if (this.lastStartLatLng) {
                coordsPayload.pickup_lat = this.lastStartLatLng.lat;
                coordsPayload.pickup_lng = this.lastStartLatLng.lng;
            } else if (this.userMarker && this.userMarker.getLatLng) {
                const u = this.userMarker.getLatLng(); coordsPayload.pickup_lat = u.lat; coordsPayload.pickup_lng = u.lng;
            }
            if (this.lastDestLatLng) { coordsPayload.dest_lat = this.lastDestLatLng.lat; coordsPayload.dest_lng = this.lastDestLatLng.lng; }
            const payload = Object.assign({
                agent_id: this.selectedAgent || '',
                service: 'book',
                mode: this.selectedAgentMode || 'bike',
                datetime: document.getElementById('bookDateTime').value,
                pickup: document.getElementById('bookPickup').value,
                destination: document.getElementById('bookDestination').value,
                estimate: document.getElementById('bookEstimate').value
            }, coordsPayload);

            fetch('create_booking.php', {method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(payload)})
                .then(r => r.json())
                .then(json => {
                    if (json.success) {
                        console.log('Booking created with id', json.booking_id);
                        this.loadTripHistory();
                    } else console.error('Booking failed', json);
                }).catch(err => console.error(err));
        }
    }

    // Trip History
    loadTripHistory() {
        fetch('get_trip_history.php')
            .then(r => r.json())
            .then(json => {
                const historyContainer = document.getElementById('trip-history');
                if (json.success && Array.isArray(json.trips)) {
                    historyContainer.innerHTML = json.trips.map(t => `
                        <tr>
                            <td>${t.mode || t.service || ''}</td>
                            <td>${t.agent_id || ''}</td>
                            <td>${t.agent_id || ''}</td>
                            <td>${t.estimate || ''}</td>
                            <td>${t.datetime || t.created_at || ''}</td>
                            <td>${t.status || ''}</td>
                            <td></td>
                        </tr>
                    `).join('');
                } else {
                    historyContainer.innerHTML = '<tr><td colspan="7">No trip history yet</td></tr>';
                }
            }).catch(err => console.error(err));
    }

    saveTripToHistory() {
        // In real application, save to database
        console.log('Trip saved to history');
    }

    // Charts
    initializeCharts() {
        // Motorcycle Trips Chart
        const bikeCtx = document.getElementById('bikeTripsChart').getContext('2d');
        new Chart(bikeCtx, {
            type: 'bar',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{
                    label: 'Motorcycle Trips',
                    data: [12, 19, 8, 15, 12, 20, 18],
                    backgroundColor: 'rgba(54, 162, 235, 0.8)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Number of Trips'
                        }
                    }
                }
            }
        });

        // Vehicle Trips Chart
        const carCtx = document.getElementById('carTripsChart').getContext('2d');
        new Chart(carCtx, {
            type: 'bar',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{
                    label: 'Vehicle Trips',
                    data: [8, 12, 6, 10, 15, 12, 9],
                    backgroundColor: 'rgba(255, 99, 132, 0.8)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Number of Trips'
                        }
                    }
                }
            }
        });
    }

    // Profile Picture Upload
    initializeProfileUpload() {
        // Attach events when personal info layer elements are present
        document.addEventListener('click', (e) => {
            // if the personal info layer is active, wire up the file input
            if (this.currentLayer === 'personal-info') {
                const fileInput = document.getElementById('profile_photo');
                const preview = document.getElementById('profilePreview');
                const form = document.getElementById('personal-info-form');
                const message = document.getElementById('personalInfoMessage');

                if (fileInput && !fileInput._bound) {
                    fileInput.addEventListener('change', (evt) => {
                        const file = evt.target.files[0];
                        if (!file) return;
                        const reader = new FileReader();
                        reader.onload = function(ev) {
                            preview.src = ev.target.result;
                        };
                        reader.readAsDataURL(file);
                    });
                    fileInput._bound = true;
                }

                if (form && !form._bound) {
                    form.addEventListener('submit', (evt) => {
                        evt.preventDefault();
                        message.textContent = '';
                        const submitBtn = document.getElementById('saveProfile');
                        submitBtn.disabled = true;

                        const data = new FormData(form);

                        fetch('update_profile.php', {
                            method: 'POST',
                            body: data,
                        }).then(r => r.json())
                        .then(json => {
                            if (json.success) {
                                message.innerHTML = '<div class="alert alert-success">Profile updated.</div>';
                                // Optionally reload page to reflect changes
                                setTimeout(() => location.reload(), 800);
                            } else if (json.error) {
                                message.innerHTML = `<div class="alert alert-danger">${json.error}</div>`;
                            }
                        }).catch(err => {
                            console.error(err);
                            message.innerHTML = '<div class="alert alert-danger">Update failed.</div>';
                        }).finally(() => { submitBtn.disabled = false; });
                    });
                    form._bound = true;
                }
            }
        });
    }

    // Layer-specific content loaders (to be implemented)
    loadBookTravelContent() {
        const container = document.getElementById('book-travel-layer');
        container.innerHTML = `
            <div class="text-center">
                <h4>Book Travel - Coming Soon</h4>
                <p>This feature will be available in the next update.</p>
            </div>
        `;
    }

    loadDeliveryServicesContent() {
        const container = document.getElementById('delivery-services-layer');
        container.innerHTML = `
            <div class="text-center">
                <h4>Delivery Services - Coming Soon</h4>
                <p>This feature will be available in the next update.</p>
            </div>
        `;
    }

    loadPersonalInfoContent() {
        // Personal info content already rendered server-side in PHP; just ensure any dynamic wiring runs
        const container = document.getElementById('personal-info-layer');
        // If needed, we could fetch fresh data via AJAX here. For now, the form fields are prefilled by PHP.
    }

    // Book Travel & Delivery Services wiring (basic client-side behavior)
    initializeBookingAndDelivery() {
        // Travel mode buttons
        document.querySelectorAll('.travel-mode').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const mode = e.currentTarget.getAttribute('data-mode');
                const tbody = document.getElementById('agentTable').querySelector('tbody');
                tbody.innerHTML = '';
                fetch(`get_agents.php?mode=${mode === 'uber' ? 'uber' : 'bike'}`)
                    .then(r => r.json())
                    .then(json => {
                        if (json.success && Array.isArray(json.agents)) {
                            json.agents.forEach((a, idx) => {
                                const tr = document.createElement('tr');
                                tr.innerHTML = `
                                    <td><img src="${a.Profile_Photo || 'images/default_profile.png'}" class="agent-photo"/></td>
                                    <td>${a.plate || ''}</td>
                                    <td>${a.Username || ''}</td>
                                    <td>${a.work || ''}</td>
                                    <td>${a.trips}</td>
                                    <td>${a.rating}</td>
                                    <td><button class="btn btn-sm btn-primary select-agent" data-id="${a.id}" data-mode="${mode}">Select</button></td>
                                `;
                                tbody.appendChild(tr);
                            });

                            tbody.querySelectorAll('.select-agent').forEach(b => {
                                b.addEventListener('click', (ev) => {
                                    const agentId = ev.currentTarget.getAttribute('data-id');
                                    const chosenMode = ev.currentTarget.getAttribute('data-mode');
                                    this.selectedAgent = agentId;
                                    this.selectedAgentMode = chosenMode;
                                    document.getElementById('book-travel-details').style.display = 'block';
                                    document.getElementById('bookEstimate').value = 'Calculating...';
                                    // TODO: replace with real estimate calculation
                                    setTimeout(() => document.getElementById('bookEstimate').value = 'UGX 10,000', 300);
                                });
                            });
                        }
                    }).catch(err => console.error(err));
            });
        });

        // Delivery mode buttons
        document.querySelectorAll('.delivery-mode').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const mode = e.currentTarget.getAttribute('data-mode');
                const tbody = document.getElementById('deliveryAgentTable').querySelector('tbody');
                tbody.innerHTML = '';
                // For delivery, use riders or drivers similarly
                fetch(`get_agents.php?mode=${mode === 'driver' ? 'uber' : 'bike'}`)
                    .then(r => r.json())
                    .then(json => {
                        if (json.success && Array.isArray(json.agents)) {
                            json.agents.forEach(a => {
                                const tr = document.createElement('tr');
                                tr.innerHTML = `
                                    <td><img src="${a.Profile_Photo || 'images/default_profile.png'}" class="agent-photo"/></td>
                                    <td>${a.plate || ''}</td>
                                    <td>${a.Username || ''}</td>
                                    <td>${a.work || ''}</td>
                                    <td>${a.trips || a.orders || 0}</td>
                                    <td>${a.rating}</td>
                                    <td><button class="btn btn-sm btn-primary select-delivery-agent" data-id="${a.id}" data-mode="${mode}">Select</button></td>
                                `;
                                tbody.appendChild(tr);
                            });

                            tbody.querySelectorAll('.select-delivery-agent').forEach(b => {
                                b.addEventListener('click', (ev) => {
                                    const agentId = ev.currentTarget.getAttribute('data-id');
                                    const chosenMode = ev.currentTarget.getAttribute('data-mode');
                                    this.selectedAgent = agentId;
                                    this.selectedAgentMode = chosenMode;
                                    document.getElementById('delivery-details').style.display = 'block';
                                    document.getElementById('deliveryEstimate').value = 'Calculating...';
                                    setTimeout(() => document.getElementById('deliveryEstimate').value = 'UGX 8,000', 300);
                                });
                            });
                        }
                    }).catch(err => console.error(err));
            });
        });

        // Password toggle
        const toggleBtn = document.getElementById('togglePassword');
        if (toggleBtn) {
            toggleBtn.addEventListener('click', () => {
                const pass = document.getElementById('password');
                if (pass.type === 'password') { pass.type = 'text'; toggleBtn.innerHTML = '<i class="fas fa-eye-slash"></i>'; }
                else { pass.type = 'password'; toggleBtn.innerHTML = '<i class="fas fa-eye"></i>'; }
            });
        }
        // Booking creation handlers
        const confirmBookingBtn = document.getElementById('confirmBooking');
        if (confirmBookingBtn) {
            confirmBookingBtn.addEventListener('click', () => {
                const payload = {
                    agent_id: this.selectedAgent || '',
                    service: 'book',
                    mode: this.selectedAgentMode || 'bike',
                    datetime: document.getElementById('bookDateTime').value,
                    pickup: document.getElementById('bookPickup').value,
                    destination: document.getElementById('bookDestination').value,
                    estimate: document.getElementById('bookEstimate').value
                };
                fetch('create_booking.php', {method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(payload)})
                    .then(r => r.json())
                    .then(json => {
                        if (json.success) {
                            alert('Booking created');
                            this.loadTripHistory();
                        } else alert('Booking failed');
                    }).catch(err => console.error(err));
            });
        }

        const confirmDeliveryBtn = document.getElementById('confirmDelivery');
        if (confirmDeliveryBtn) {
            confirmDeliveryBtn.addEventListener('click', () => {
                const payload = {
                    agent_id: this.selectedAgent || '',
                    service: 'delivery',
                    mode: this.selectedAgentMode || 'rider',
                    datetime: document.getElementById('deliveryDateTime').value,
                    pickup: document.getElementById('deliveryPickup').value,
                    destination: document.getElementById('deliveryDropoff').value,
                    estimate: document.getElementById('deliveryEstimate').value
                };
                fetch('create_booking.php', {method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(payload)})
                    .then(r => r.json())
                    .then(json => {
                        if (json.success) {
                            alert('Delivery request created');
                            this.loadTripHistory();
                        } else alert('Request failed');
                    }).catch(err => console.error(err));
            });
        }
    }
}

// Initialize dashboard when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    new ClientDashboard();
});