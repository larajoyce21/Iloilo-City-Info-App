
<?php
// Database configuration
$host = 'localhost';
$dbname = 'app';
$username = 'root';
$password = '';

// Create connection (MySQLi)
$conn = new mysqli($host, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if not exists
$conn->query("CREATE DATABASE IF NOT EXISTS `$dbname`");
$conn->select_db($dbname);

$conn->query("CREATE TABLE IF NOT EXISTS `historicals` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `location` VARCHAR(255) NOT NULL,
    `latitude` DECIMAL(10, 8) NOT NULL,
    `longitude` DECIMAL(11, 8) NOT NULL
)");

// Check if historicals table is empty and insert sample data
$result = $conn->query("SELECT COUNT(*) as count FROM historicals");
$row = $result->fetch_assoc();
if ($row['count'] == 0) {
    $sampleHistoricals = [
        ["Molo Mansion", "Yusay-Consing Mansion, Locsin St, Molo, Iloilo City,", 10.697193461274793, 122.54325364091498],
        ["Casa Real", " General Luna St, Iloilo City Proper, Iloilo City",10.702106128735638, 122.56914185475333],
        ["Molo Church ", " San Pedro St, Molo, Iloilo City", 10.697545748989047, 122.5444496527408]
    ];
    
    $stmt = $conn->prepare("INSERT INTO historicals (name, location, latitude, longitude) VALUES (?, ?, ?, ?)");
    foreach ($sampleHistoricals as $historical) {
        $stmt->bind_param("ssdd", $historical[0], $historical[1], $historical[2], $historical[3]);
        $stmt->execute();
    }
}

// AJAX request handling for historicals data
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');
    
    $result = $conn->query("SELECT id, name, location, latitude, longitude FROM historicals");
    $historicals = [];
    while ($row = $result->fetch_assoc()) {
        $historicals[] = $row;
    }
    
    echo json_encode($historicals);
    exit;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historicals Distance Calculator</title>
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css" />
    <style>
    :root {
        --primary-color: #4361ee;
        --secondary-color: #3f37c9;
        --accent-color: #4895ef;
        --success-color: #4cc9f0;
        --danger-color: #f72585;
        --warning-color: #f8961e;
        --light-color: #f8f9fa;
        --dark-color: #212529;
        --text-color: #333;
        --text-light: #6c757d;
        --border-color: #dee2e6;
        --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        --transition: all 0.3s ease;
    }

    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }

    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        line-height: 1.6;
        color: var(--text-color);
        background-color: #f5f7ff;
        margin: 0;
        padding: 20px;
    }

    .container {
        max-width: 1000px;
        margin: 0 auto;
        background: white;
        padding: 30px;
        border-radius: 15px;
        box-shadow: var(--shadow);
    }

    h1 {
        color: var(--primary-color);
        text-align: center;
        margin-bottom: 25px;
        font-weight: 700;
        font-size: 2.2rem;
    }

    .section {
        margin-bottom: 25px;
        padding: 20px;
        background-color: white;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }

    .section-title {
        font-size: 1.2rem;
        color: var(--primary-color);
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .section-title i {
        font-size: 1.4rem;
    }

    .input-group {
        margin-bottom: 20px;
    }

    label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: var(--text-color);
    }

    select, input, button {
        width: 100%;
        padding: 12px 15px;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        font-size: 1rem;
        transition: var(--transition);
    }

    select:focus, input:focus {
        outline: none;
        border-color: var(--accent-color);
        box-shadow: 0 0 0 3px rgba(72, 149, 239, 0.2);
    }

    button {
        background-color: var(--primary-color);
        color: white;
        border: none;
        cursor: pointer;
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    button:hover {
        background-color: var(--secondary-color);
        transform: translateY(-2px);
    }

    button:active {
        transform: translateY(0);
    }

    button:disabled {
        background-color: #adb5bd;
        cursor: not-allowed;
        transform: none;
    }

    .btn-icon {
        font-size: 1.2rem;
    }

    #map {
        height: 450px;
        width: 100%;
        border-radius: 10px;
        margin-top: 20px;
        border: 1px solid var(--border-color);
    }

    /* Location Status */
    .location-status {
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        display: flex;
        align-items: flex-start;
        gap: 12px;
    }

    .location-status.active {
        background-color: #e6f7ff;
        border-left: 4px solid var(--accent-color);
    }

    .location-status.error {
        background-color: #fff2f2;
        border-left: 4px solid var(--danger-color);
    }

    .status-icon {
        font-size: 1.5rem;
        color: var(--accent-color);
    }

    .error .status-icon {
        color: var(--danger-color);
    }

    .status-text {
        flex-grow: 1;
    }

    .status-text strong {
        display: block;
        margin-bottom: 5px;
    }

    .accuracy {
        font-size: 0.85rem;
        color: var(--text-light);
        margin-top: 5px;
    }

    /* Position History */
    .position-history {
        margin-top: 10px;
        font-size: 0.85rem;
        color: var(--text-light);
        background: rgba(0, 0, 0, 0.02);
        padding: 8px;
        border-radius: 5px;
    }

    /* Result Section */
    #result {
        margin-top: 25px;
        padding: 20px;
        border-radius: 10px;
        background-color: #f8f9fa;
        display: none;
        border-left: 4px solid var(--success-color);
    }

    .historical-info {
        margin-bottom: 15px;
        padding-bottom: 15px;
        border-bottom: 1px dashed var(--border-color);
    }

    .historical-name {
        font-weight: 700;
        font-size: 1.3rem;
        color: var(--dark-color);
        margin-bottom: 5px;
    }

    .historical-location {
        color: var(--text-light);
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .distance, .travel-time {
        margin: 15px 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .distance {
        font-size: 1.4rem;
        font-weight: 700;
        color: var(--primary-color);
    }

    .travel-time {
        font-size: 1.1rem;
        color: var(--accent-color);
    }

    .error-message {
        color: var(--danger-color);
        margin: 10px 0;
        padding: 10px;
        background-color: #fff2f2;
        border-radius: 5px;
        display: none;
    }

    /* Route Instructions */
    .route-instructions {
        margin-top: 20px;
        max-height: 200px;
        overflow-y: auto;
        padding-right: 10px;
    }

    .instruction {
        margin-bottom: 10px;
        padding-bottom: 10px;
        border-bottom: 1px solid #eee;
        display: flex;
        gap: 10px;
    }

    .instruction:last-child {
        border-bottom: none;
    }

    .instruction-icon {
        color: var(--accent-color);
        font-size: 1.1rem;
        margin-top: 2px;
    }

    /* Toggle Switch */
    .toggle-switch {
        display: flex;
        align-items: center;
        margin: 15px 0;
    }

    .toggle-switch label {
        margin-left: 10px;
        cursor: pointer;
        user-select: none;
        color: var(--text-color);
    }

    .toggle-switch input[type="checkbox"] {
        appearance: none;
        width: 50px;
        height: 26px;
        background: #ddd;
        border-radius: 13px;
        position: relative;
        cursor: pointer;
        transition: var(--transition);
    }

    .toggle-switch input[type="checkbox"]:checked {
        background: var(--accent-color);
    }

    .toggle-switch input[type="checkbox"]::after {
        content: '';
        position: absolute;
        width: 22px;
        height: 22px;
        border-radius: 50%;
        background: white;
        top: 2px;
        left: 2px;
        transition: var(--transition);
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
    }

    .toggle-switch input[type="checkbox"]:checked::after {
        transform: translateX(24px);
    }

    /* Tab Buttons */
    .tab-buttons {
        display: flex;
        margin-bottom: 20px;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: var(--shadow);
    }

    .tab-button {
        flex: 1;
        padding: 12px;
        text-align: center;
        background: white;
        border: none;
        cursor: pointer;
        font-weight: 600;
        transition: var(--transition);
        border-bottom: 3px solid transparent;
    }

    .tab-button:first-child {
        border-radius: 8px 0 0 8px;
    }

    .tab-button:last-child {
        border-radius: 0 8px 8px 0;
    }

    .tab-button.active {
        background: var(--primary-color);
        color: white;
        border-bottom-color: var(--secondary-color);
    }

    /* Loading Animation */
    .loading {
        display: inline-block;
        width: 20px;
        height: 20px;
        border: 3px solid rgba(255, 255, 255, 0.3);
        border-radius: 50%;
        border-top-color: white;
        animation: spin 1s ease-in-out infinite;
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .container {
            padding: 15px;
        }

        h1 {
            font-size: 1.8rem;
        }

        .section {
            padding: 15px;
        }

        .distance {
            font-size: 1.2rem;
        }

        .travel-time {
            font-size: 1rem;
        }
    }

    /* Custom Scrollbar */
    ::-webkit-scrollbar {
        width: 8px;
    }

    ::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 4px;
    }

    ::-webkit-scrollbar-thumb {
        background: #ccc;
        border-radius: 4px;
    }

    ::-webkit-scrollbar-thumb:hover {
        background: #aaa;
    }

    /* Utility Classes */
    .flex-row {
        display: flex;
        gap: 10px;
    }

    .flex-row > * {
        flex: 1;
    }

    .text-center {
        text-align: center;
    }

    .mt-2 { margin-top: 10px; }
    .mt-3 { margin-top: 15px; }
    .mt-4 { margin-top: 20px; }
    .mb-2 { margin-bottom: 10px; }
    .mb-3 { margin-bottom: 15px; }
    .mb-4 { margin-bottom: 20px; }
</style>
</head>
<body>
    <div class="container">
        <h1>Historicals Distance Calculator</h1>
        
        <div class="tab-buttons">
            <button class="tab-button active" id="autoLocationTab" onclick="switchLocationTab('auto')">Use My Location</button>
            <button class="tab-button" id="manualLocationTab" onclick="switchLocationTab('manual')">Enter Location</button>
        </div>
        
        <div id="autoLocationSection">
            <div class="location-status" id="locationStatus">
                <span class="status-icon">📍</span>
                <div class="status-text">
                    <strong>Your current location:</strong> <span id="currentLocation">Initializing...</span>
                    <div class="accuracy" id="accuracyInfo"></div>
                    <div class="position-history" id="positionHistory"></div>
                </div>
            </div>
            
            <div class="toggle-switch">
                <input type="checkbox" id="autoRecalculate" checked>
                <label for="autoRecalculate">Auto-update when moving</label>
            </div>
            
            <div class="controls">
                <button id="startTrackingBtn" onclick="startTracking()">Start Tracking</button>
                <button id="stopTrackingBtn" onclick="stopTracking()" style="display: none;">Stop Tracking</button>
            </div>
        </div>
        
        <div id="manualLocationSection" class="location-input">
            <label for="manualAddress">Enter Your Address:</label>
            <input type="text" id="manualAddress" placeholder="e.g. 123 Main St, City">
            
            <label for="manualCoords">Or Enter Coordinates:</label>
            <div style="display: flex; gap: 10px;">
                <input type="text" id="manualLat" placeholder="Latitude" style="flex: 1;">
                <input type="text" id="manualLng" placeholder="Longitude" style="flex: 1;">
            </div>
            
            <button id="setManualLocation" onclick="setManualLocation()">Set Location</button>
        </div>
        
        <div class="input-group">
            <label for="historical">Select Historicals:</label>
            <select id="historical">
                <option value="">-- Loading historicals... --</option>
            </select>
            
            <label for="routeType" style="margin-top: 15px; display: block;">Route Type:</label>
            <select id="routeType">
                <option value="drive">Driving</option>
                <option value="walk">Walking</option>
                <option value="bike">Bicycling</option>
            </select>
            
            <button id="calculateBtn" onclick="calculateDistance()" disabled>
                <span id="loadingIcon" class="loading" style="display: none;"></span>
                Calculate Distance
            </button>
        </div>
        
        <div id="result">
            <div class="historical-info">
                <div class="historical-name" id="historicalName"></div>
                <div class="historical-location">📍 <span id="historicalLocation"></span></div>
            </div>
            <div class="distance">
                Distance: <span id="distanceValue"></span>
            </div>
            <div class="travel-time">
                Estimated Travel Time: <span id="travelTime"></span>
            </div>
            <div id="error" class="error"></div>
            <div class="route-instructions" id="routeInstructions"></div>
        </div>
        
        <div id="map"></div>
    </div>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <!-- Leaflet Routing Machine -->
    <script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.js"></script>
    <!-- Geocoding library -->
    <script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>
    
    <script>
        // Map variables
        let map;
        let currentPositionMarker;
        let historicalMarker;
        let routingControl;
        let currentLocation = null;
        let watchId = null;
        let lastPosition = null;
        let positionHistory = [];
        const geocoder = L.Control.Geocoder.nominatim();
        let historicalsData = [];
        let autoRecalculate = true;
        let isTracking = false;
        let currentLocationMode = 'auto'; // 'auto' or 'manual'

        // Initialize the map
        function initMap() {
            // Default center: Somewhere in the middle of the map
            map = L.map('map').setView([14.5995, 120.9842], 13);
            
            // Add OpenStreetMap tiles
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);
            
            // Initialize UI elements
            document.getElementById('autoRecalculate').addEventListener('change', function() {
                autoRecalculate = this.checked;
                updatePositionHistoryUI();
            });
            
            // Fetch historicals from database
            fetchHistoricals();
            
            // Start with auto location by default
            switchLocationTab('auto');
        }

        // Switch between auto and manual location tabs
        function switchLocationTab(mode) {
            currentLocationMode = mode;
            
            // Update tab buttons
            document.getElementById('autoLocationTab').classList.toggle('active', mode === 'auto');
            document.getElementById('manualLocationTab').classList.toggle('active', mode === 'manual');
            
            // Show/hide sections
            document.getElementById('autoLocationSection').style.display = mode === 'auto' ? 'block' : 'none';
            document.getElementById('manualLocationSection').classList.toggle('active', mode === 'manual');
            
            // If switching to auto, start tracking
            if (mode === 'auto') {
                startTracking();
            } else {
                stopTracking();
            }
        }

        // Start tracking user's location
        function startTracking() {
            if (isTracking) return;
            
            const locationStatus = document.getElementById('locationStatus');
            locationStatus.className = 'location-status active';
            document.getElementById('currentLocation').textContent = "Detecting your location...";
            
            if (navigator.geolocation) {
                isTracking = true;
                document.getElementById('stopTrackingBtn').style.display = 'block';
                document.getElementById('startTrackingBtn').style.display = 'none';
                
                // First get current position quickly
                navigator.geolocation.getCurrentPosition(
                    position => handlePositionSuccess(position, true),
                    handlePositionError,
                    { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
                );
                
                // Then watch for position changes
                watchId = navigator.geolocation.watchPosition(
                    position => handlePositionSuccess(position, false),
                    handlePositionError,
                    { enableHighAccuracy: true, maximumAge: 10000 }
                );
            } else {
                handlePositionError({ code: 0, message: "Geolocation not supported" });
            }
        }
        
        // Stop tracking user's location
        function stopTracking() {
            if (watchId && navigator.geolocation) {
                navigator.geolocation.clearWatch(watchId);
                watchId = null;
            }
            isTracking = false;
            document.getElementById('stopTrackingBtn').style.display = 'none';
            document.getElementById('startTrackingBtn').style.display = 'block';
            document.getElementById('locationStatus').className = 'location-status';
            document.getElementById('currentLocation').textContent = "Location tracking stopped";
        }
        
        // Handle successful position detection
        function handlePositionSuccess(position, isInitial) {
            const pos = {
                lat: position.coords.latitude,
                lng: position.coords.longitude,
                accuracy: position.coords.accuracy,
                timestamp: position.timestamp
            };
            
            // Update position history (keep last 5 positions)
            positionHistory.unshift({
                lat: pos.lat,
                lng: pos.lng,
                time: new Date(pos.timestamp || Date.now()).toLocaleTimeString(),
                accuracy: pos.accuracy
            });
            if (positionHistory.length > 5) positionHistory.pop();
            
            // Only update if position has changed significantly or it's the initial position
            const significantChange = !lastPosition || 
                (Math.abs(pos.lat - lastPosition.lat) > 0.0001 || 
                Math.abs(pos.lng - lastPosition.lng) > 0.0001);
            
            if (significantChange || isInitial) {
                currentLocation = pos;
                lastPosition = pos;
                
                // Update UI
                updateLocationUI(pos);
                
                // Update map if this is a significant change
                if (significantChange) {
                    updateMapPosition(pos);
                    
                    // Recalculate route if auto-recalculate is enabled and a historicals is selected
                    if (autoRecalculate && document.getElementById('historical').value && document.getElementById('result').style.display !== 'none') {
                        calculateDistance();
                    }
                }
            }
            
            updatePositionHistoryUI();
        }
        
        // Set manual location from address or coordinates
        function setManualLocation() {
            const address = document.getElementById('manualAddress').value.trim();
            const lat = parseFloat(document.getElementById('manualLat').value);
            const lng = parseFloat(document.getElementById('manualLng').value);
            
            if (address) {
                // Geocode the address
                document.getElementById('currentLocation').textContent = "Geocoding address...";
                
                fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(address)}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data && data.length > 0) {
                            const pos = {
                                lat: parseFloat(data[0].lat),
                                lng: parseFloat(data[0].lon),
                                accuracy: 50, // Assume 50m accuracy for manual entry
                                timestamp: Date.now()
                            };
                            
                            currentLocation = pos;
                            lastPosition = pos;
                            
                            // Clear position history for manual entries
                            positionHistory = [{
                                lat: pos.lat,
                                lng: pos.lng,
                                time: new Date().toLocaleTimeString(),
                                accuracy: pos.accuracy
                            }];
                            
                            updateLocationUI(pos);
                            updateMapPosition(pos);
                            updatePositionHistoryUI();
                            
                            // Update address field with formatted address
                            document.getElementById('manualAddress').value = data[0].display_name;
                        } else {
                            document.getElementById('error').textContent = "Address not found. Please try a different address.";
                        }
                    })
                    .catch(error => {
                        document.getElementById('error').textContent = "Error geocoding address. Please try again.";
                        console.error('Geocoding error:', error);
                    });
            } else if (!isNaN(lat) && !isNaN(lng)) {
                // Use coordinates directly
                const pos = {
                    lat: lat,
                    lng: lng,
                    accuracy: 50, // Assume 50m accuracy for manual entry
                    timestamp: Date.now()
                };
                
                currentLocation = pos;
                lastPosition = pos;
                
                // Clear position history for manual entries
                positionHistory = [{
                    lat: pos.lat,
                    lng: pos.lng,
                    time: new Date().toLocaleTimeString(),
                    accuracy: pos.accuracy
                }];
                
                updateLocationUI(pos);
                updateMapPosition(pos);
                updatePositionHistoryUI();
                
                // Try to get address from coordinates
                getAddressFromCoords(lat, lng);
            } else {
                document.getElementById('error').textContent = "Please enter either an address or valid coordinates.";
            }
        }
        
        // Update location information in the UI
        function updateLocationUI(pos) {
            const locationStatus = document.getElementById('locationStatus');
            locationStatus.className = 'location-status active';
            
            document.getElementById('currentLocation').textContent = 
                `${pos.lat.toFixed(6)}, ${pos.lng.toFixed(6)}`;
            document.getElementById('accuracyInfo').textContent = 
                pos.accuracy ? `Accuracy: ±${Math.round(pos.accuracy)} meters` : '';
            
            // Try to get address from coordinates
            getAddressFromCoords(pos.lat, pos.lng);
        }
        
        // Update position history in the UI
        function updatePositionHistoryUI() {
            const historyElement = document.getElementById('positionHistory');
            if (positionHistory.length > 1) {
                historyElement.innerHTML = positionHistory.slice(0, 3).map((p, i) => 
                    `${i+1}. ${p.time}: ${p.lat.toFixed(6)}, ${p.lng.toFixed(6)} (±${Math.round(p.accuracy)}m)`
                ).join('<br>');
            } else {
                historyElement.textContent = '';
            }
        }
        
        // Update map with new position
        function updateMapPosition(pos) {
            // Update map view if this is a significant move
            if (!map.getBounds().contains([pos.lat, pos.lng])) {
                map.setView([pos.lat, pos.lng], 15);
            }
            
            // Update current position marker
            if (currentPositionMarker) {
                map.removeLayer(currentPositionMarker);
            }
            
            currentPositionMarker = L.marker([pos.lat, pos.lng], {
                title: "Your Location",
                icon: L.divIcon({
                    className: 'current-location-marker',
                    html: '<div style="background-color: #4285F4; width: 20px; height: 20px; border-radius: 50%; border: 2px solid white;"></div>',
                    iconSize: [24, 24]
                })
            }).addTo(map)
            .bindPopup("Your Current Location<br>Accuracy: ±" + Math.round(pos.accuracy) + " meters");
            
            // Add accuracy circle if in auto mode and accuracy is available
            if (currentLocationMode === 'auto' && pos.accuracy) {
                L.circle([pos.lat, pos.lng], {
                    radius: pos.accuracy,
                    color: '#4285F4',
                    fillColor: '#4285F4',
                    fillOpacity: 0.2,
                    weight: 1
                }).addTo(map);
            }
        }
        
        // Handle position errors
        function handlePositionError(error) {
            const locationStatus = document.getElementById('locationStatus');
            locationStatus.className = 'location-status error';
            
            let errorMessage = "Error: ";
            switch(error.code) {
                case error.PERMISSION_DENIED:
                    errorMessage += "Location access denied. Please enable location services.";
                    break;
                case error.POSITION_UNAVAILABLE:
                    errorMessage += "Location information unavailable.";
                    break;
                case error.TIMEOUT:
                    errorMessage += "Location request timed out.";
                    break;
                case error.UNKNOWN_ERROR:
                    errorMessage += "An unknown error occurred.";
                    break;
                default:
                    errorMessage += "Geolocation not supported.";
            }
            
            document.getElementById('currentLocation').textContent = errorMessage;
            document.getElementById('accuracyInfo').textContent = '';
            
            // Set default location if geolocation fails
            currentLocation = {
                lat: 14.5995,
                lng: 120.9842,
                accuracy: 100
            };
            
            // Update map view to default location
            map.setView([currentLocation.lat, currentLocation.lng], 13);
        }

        // Get address from coordinates
        function getAddressFromCoords(lat, lng) {
            fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`)
                .then(response => response.json())
                .then(data => {
                    if (data.display_name) {
                        document.getElementById('currentLocation').textContent = 
                            data.display_name + ` (${lat.toFixed(6)}, ${lng.toFixed(6)})`;
                            
                        // Update manual input fields if in manual mode
                        if (currentLocationMode === 'manual') {
                            document.getElementById('manualAddress').value = data.display_name;
                            document.getElementById('manualLat').value = lat.toFixed(6);
                            document.getElementById('manualLng').value = lng.toFixed(6);
                        }
                    }
                })
                .catch(error => console.log('Error getting address:', error));
        }

        // Fetch historicals from database
        function fetchHistoricals() {
            const historicalSelect = document.getElementById('historical');
            historicalSelect.innerHTML = '<option value="">Loading historicals...</option>';
            
            fetch(window.location.href, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.error) {
                    console.error('Error fetching historicals:', data.error);
                    showHistoricalsError(data.error);
                    return;
                }
                
                historicalsData = data;
                populateHistorcalDropdown();
            })
            .catch(error => {
                console.error('Error fetching historicals:', error);
                showHistoricalError('Failed to load historicals. Using sample data instead.');
                historicalsData = [
                     ["Molo Mansion", "Yusay-Consing Mansion, Locsin St, Molo, Iloilo City,", 10.697193461274793, 122.54325364091498],
                    ["Casa Real", " General Luna St, Iloilo City Proper, Iloilo City",10.702106128735638, 122.56914185475333],
                    ["Molo Church ", " San Pedro St, Molo, Iloilo City", 10.697545748989047, 122.5444496527408]
                ];
                populateHistoricalDropdown();
            });
        }

        // Show error message in historicals dropdown
        function showHistoricalError(message) {
            document.getElementById('historical').innerHTML = `
                <option value="">-- Error: ${message} --</option>
            `;
        }

        // Populate historicals dropdown with data from database
        function populateHistoricalDropdown() {
            const historicalSelect = document.getElementById('historical');
            historicalSelect.innerHTML = '<option value="">-- Select a historical --</option>';
            
            if (historicalsData.length === 0) {
                historicalSelect.innerHTML += '<option value="">-- No historicals found --</option>';
                return;
            }
            
            historicalsData.forEach(historical => {
                const option = document.createElement('option');
                option.value = historical.id;
                option.textContent = historical.name;
                option.setAttribute('data-location', historical.location);
                option.setAttribute('data-latitude', historical.latitude);
                option.setAttribute('data-longitude', historical.longitude);
                historicalSelect.appendChild(option);
            });
            
            if (historicalData.length > 0) {
                document.getElementById('calculateBtn').disabled = false;
            }
        }

        // Format time in minutes to readable format
        function formatTime(minutes) {
            if (minutes < 60) {
                return `${Math.round(minutes)} min`;
            } else {
                const hours = Math.floor(minutes / 60);
                const mins = Math.round(minutes % 60);
                return `${hours} hr ${mins} min`;
            }
        }

        // Format distance in meters to readable format
        function formatDistance(meters) {
            if (meters < 1000) {
                return `${Math.round(meters)} meters`;
            } else {
                return `${(meters / 1000).toFixed(1)} km`;
            }
        }

        // Calculate distance to selected historicals
        function calculateDistance() {
            const historicalSelect = document.getElementById('historical');
            const selectedOption = historicalSelect.options[historicalSelect.selectedIndex];
            const historicalId = historicalSelect.value;
            const routeType = document.getElementById('routeType').value;

            if (!historicalId) {
                alert("Please select a historicals from the list");
                return;
            }

            if (!currentLocation) {
                document.getElementById('error').textContent = "Please set your location first.";
                return;
            }

            document.getElementById('loadingIcon').style.display = 'inline-block';
            document.getElementById('calculateBtn').disabled = true;
            document.getElementById('result').style.display = 'none';
            document.getElementById('error').textContent = '';
            document.getElementById('routeInstructions').innerHTML = '';

            if (routingControl) {
                map.removeControl(routingControl);
                routingControl = null;
            }

            const historicalName = selectedOption.text;
            const historicalLocation = selectedOption.getAttribute('data-location');
            const historicalLat = parseFloat(selectedOption.getAttribute('data-latitude'));
            const historicalLng = parseFloat(selectedOption.getAttribute('data-longitude'));

            document.getElementById('historicalName').textContent = historicalName;
            document.getElementById('historicalLocation').textContent = historicalLocation;

            if (isNaN(historicalLat) || isNaN(historicalLng)) {
                document.getElementById('error').textContent = "Invalid historical coordinates";
                document.getElementById('loadingIcon').style.display = 'none';
                document.getElementById('calculateBtn').disabled = false;
                return;
            }

            showRoute(historicalLat, historicalLng, historicalName, routeType, function(route) {
                if (route) {
                    const distance = route.summary.totalDistance;
                    const time = route.summary.totalTime;

                    document.getElementById('distanceValue').textContent = formatDistance(distance);
                    document.getElementById('travelTime').textContent = formatTime(time / 60);
                    document.getElementById('result').style.display = 'block';

                    // Populate step-by-step instructions
                    const instructionsContainer = document.getElementById('routeInstructions');
                    instructionsContainer.innerHTML = ''; // Clear previous instructions
                    route.instructions.forEach(step => {
                        const div = document.createElement('div');
                        div.className = 'instruction';
                        div.innerHTML = step.text;
                        instructionsContainer.appendChild(div);
                    });

                } else {
                    document.getElementById('error').textContent = "Unable to calculate route. Please try again later.";
                }

                document.getElementById('loadingIcon').style.display = 'none';
                document.getElementById('calculateBtn').disabled = false;
            });
        }

        // Show route on map
        function showRoute(historicalLat, historicalLng, historicalName, routeType, callback) {
            // Clear previous historicals marker
            if (historicalMarker) {
                map.removeLayer(historicalMarker);
            }
            
            // Add historicals marker
            historicalMarker = L.marker([historicalLat, historicalLng], {
                title: historicalName,
                icon: L.divIcon({
                    className: 'historical-marker',
                    html: '<div style="background-color: #EA4335; width: 20px; height: 20px; border-radius: 50%; border: 2px solid white;"></div>',
                    iconSize: [24, 24]
                })
            }).addTo(map)
            .bindPopup(`<b>${historicalName}</b><br>${document.getElementById('historicalLocation').textContent}`);
            
            // Create routing control
            routingControl = L.Routing.control({
                waypoints: [
                    L.latLng(currentLocation.lat, currentLocation.lng),
                    L.latLng(historicalLat, historicalLng)
                ],
                routeWhileDragging: false,
                show: false,
                addWaypoints: false,
                draggableWaypoints: false,
                fitSelectedRoutes: true,
                lineOptions: {
                    styles: [{color: '#4285F4', opacity: 0.8, weight: 5}]
                },
                createMarker: function() { return null; },
                router: L.Routing.osrmv1({
                    serviceUrl: 'https://router.project-osrm.org/route/v1',
                    profile: routeType === 'walk' ? 'foot' : (routeType === 'bike' ? 'bike' : 'car')
                })
            }).addTo(map);
            
            // Listen for route calculation
            routingControl.on('routesfound', function(e) {
                const routes = e.routes;
                if (routes && routes.length > 0) {
                    callback(routes[0]);
                } else {
                    callback(null);
                }
            });
            
            // Handle route errors
            routingControl.on('routingerror', function(e) {
                console.error('Routing error:', e.error);
                callback(null);
            });
            
            // Fit map to show the entire route with padding
            const bounds = L.latLngBounds(
                [currentLocation.lat, currentLocation.lng],
                [historicalLat, historicalLng]
            ).pad(0.2); // Add 20% padding
            map.fitBounds(bounds);
        }

        // Initialize map when page loads
        document.addEventListener('DOMContentLoaded', initMap);
    </script>
</body>
</html>