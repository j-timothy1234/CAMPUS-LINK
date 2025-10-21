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
        
        if (!fromLocation || !toLocation) {
            alert('Please enter both from and to locations.');
            return;
        }
        
        // Simulate route calculation and price estimation
        const distance = Math.random() * 20 + 5; // Random distance between 5-25 km
        const price = this.calculatePrice(distance, this.currentTransportMode);
        
        document.getElementById('order-price').textContent = `UGX ${price.toLocaleString()}`;
        
        // Simulate route drawing on map
        this.drawSimulatedRoute();
    }

    calculatePrice(distance, mode) {
        const baseRate = mode === 'motorcycle' ? 1500 : 3000;
        const perKmRate = mode === 'motorcycle' ? 800 : 1500;
        return Math.round(baseRate + (distance * perKmRate));
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
        }
    }

    // Trip History
    loadTripHistory() {
        // Simulated trip history data
        const tripHistory = [
            {
                type: 'Motorcycle',
                plate: 'UBD 123A',
                agent: 'John Rider',
                distance: '8.5 km',
                fare: 'UGX 12,000',
                date: '2024-01-15',
                comment: 'Excellent service!'
            },
            {
                type: 'Vehicle',
                plate: 'UBA 456B',
                agent: 'Jane Driver',
                distance: '15.2 km',
                fare: 'UGX 25,000',
                date: '2024-01-14',
                comment: 'Comfortable ride'
            }
        ];
        
        const historyContainer = document.getElementById('trip-history');
        historyContainer.innerHTML = tripHistory.map(trip => `
            <tr>
                <td>${trip.type}</td>
                <td>${trip.plate}</td>
                <td>${trip.agent}</td>
                <td>${trip.distance}</td>
                <td>${trip.fare}</td>
                <td>${trip.date}</td>
                <td>${trip.comment}</td>
            </tr>
        `).join('');
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
        // This would be implemented in the personal info layer
        console.log('Profile upload initialized');
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
        const container = document.getElementById('personal-info-layer');
        container.innerHTML = `
            <div class="text-center">
                <h4>Personal Info - Coming Soon</h4>
                <p>This feature will be available in the next update.</p>
            </div>
        `;
    }
}

// Initialize dashboard when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    new ClientDashboard();
});