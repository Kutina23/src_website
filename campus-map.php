<?php
require_once 'include/header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campus Map - Dr. Hilla Limann Technical University</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <style>
        /* Map-specific overrides */
        #map {
            height: calc(100vh - 80px); /* Account for header */
            width: 100%;
            margin: 0;
            padding: 0;
        }
        body {
            margin: 0;
            padding-top: 80px; /* Height of header */
        }
        /* Custom header for map page */
        .map-header {
            background: var(--navy);
            color: white;
            padding: 1rem;
            text-align: center;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            height: 80px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        .map-header h1 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
        }
        .map-header p {
            margin: 0.5rem 0 0;
            font-size: 0.9rem;
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <!-- Map Header -->
    <div class="map-header">
        <h1>Dr. Hilla Limann Technical University Campus Map</h1>
        <p>Interactive Satellite View</p>
    </div>
    
    <!-- Map Container -->
    <div id="map"></div>

    <!-- Google Maps JavaScript API -->
    <!-- Replace 'YOUR_API_KEY' with your actual Google Maps JavaScript API key -->
    <script>
        // Initialize the map when the API loads
        function initMap() {
            // Coordinates for Dr. Hilla Limann Technical University, Wa, Ghana
            const universityLocation = { lat: 10.0605, lng: -2.5045 };
            
            // Create the map with satellite view
            const map = new google.maps.Map(document.getElementById("map"), {
                center: universityLocation,
                zoom: 18,
                mapTypeId: 'satellite'
            });
            
            // Add a marker for the university
            new google.maps.Marker({
                position: universityLocation,
                map: map,
                title: "Dr. Hilla Limann Technical University"
            });
        }
    </script>
    <!-- Load the Google Maps API with your key -->
    <script src="https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY&callback=initMap" async defer></script>
</body>
</html>
<?php
require_once 'include/footer.php';
?>