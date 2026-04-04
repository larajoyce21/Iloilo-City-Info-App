<?php
include 'conn.php';

if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM ferries WHERE id=?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    header("Location: add_ferries.php");
    exit();
}

$query = "SELECT * FROM ferries";
$stmt = $conn->prepare($query);
$stmt->execute();
$ferries = $stmt->get_result();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'] ?? null;
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $type = $_POST['type'] ?? '';
    $origin = $_POST['origin'] ?? '';
    $destination = $_POST['destination'] ?? '';
    $stopovers = $_POST['stopovers'] ?? '';
    $departure_time = $_POST['departure_time'] ?? '';
    $arrival_time = $_POST['arrival_time'] ?? '';
    $frequency = $_POST['frequency'] ?? '';
    $fare_rates = $_POST['fare_rates'] ?? '';
    $booking_info = $_POST['booking_info'] ?? '';
    $amenities = $_POST['amenities'] ?? '';
    $requirements = $_POST['requirements'] ?? '';
    $location = $_POST['location'] ?? '';
    $google_map = $_POST['google_map'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $email = $_POST['email'] ?? '';
    $website = $_POST['website'] ?? '';
    $policies = $_POST['policies'] ?? '';
    $rating = $_POST['rating'] ?? '';
    $reviews = $_POST['reviews'] ?? '';
    $nearby = $_POST['nearby'] ?? '';
    $details_link = $_POST['details_link'] ?? '';
    $operator = $_POST['operator'] ?? '';
    $capacity = $_POST['capacity'] ?? '';
    $travel_time = $_POST['travel_time'] ?? '';
    $safety_rating = $_POST['safety_rating'] ?? '';
    $pet_policy = $_POST['pet_policy'] ?? '';
    $social_media = $_POST['social_media'] ?? '';

    $nearby_array = array_map('trim', explode(',', $nearby));
    $nearby_json = json_encode(array_filter($nearby_array));

    // Handle transportation routes
    $transportation_routes = array();
    
    // Process table rows
    if (isset($_POST['route_type']) && is_array($_POST['route_type'])) {
        $route_count = count($_POST['route_type']);
        
        for ($i = 0; $i < $route_count; $i++) {
            $type_route = trim($_POST['route_type'][$i] ?? '');
            $name_route = trim($_POST['route_name'][$i] ?? '');
            
            if (!empty($type_route) && !empty($name_route)) {
                $transportation_routes[] = array(
                    'type' => $type_route,
                    'name' => $name_route,
                    'link' => trim($_POST['route_link'][$i] ?? ''),
                    'description' => trim($_POST['route_description'][$i] ?? '')
                );
            }
        }
    }
    
    $transportation_routes_json = json_encode($transportation_routes);

    $target_dir = "uploads/";
    $gallery_dir = $target_dir . "gallery/";
    
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    if (!file_exists($gallery_dir)) {
        mkdir($gallery_dir, 0777, true);
    }

    $target_file = $_POST['existing_image'] ?? '';
    if (!empty($_FILES['image']['name'])) {
        $target_file = $target_dir . basename($_FILES["image"]["name"]);
        move_uploaded_file($_FILES["image"]["tmp_name"], $target_file);
    }

    $imagePaths = [];
    $existingImages = [];
    
    if (!empty($_POST['existing_gallery_images'])) {
        $existingImages = explode(',', $_POST['existing_gallery_images']);
    }
    
    if (!empty($_FILES['gallery_images']['name'][0])) {
        $totalNew = count($_FILES['gallery_images']['name']);
        $totalCombined = count($existingImages) + $totalNew;
    
        if ($totalCombined > 5) {
            die("Error: You can upload a maximum of 5 gallery images total.");
        }
    
        for ($i = 0; $i < $totalNew; $i++) {
            $fileName = basename($_FILES['gallery_images']['name'][$i]);
            $tmpName = $_FILES['gallery_images']['tmp_name'][$i];
            $uniqueName = uniqid('img_', true) . '.' . pathinfo($fileName, PATHINFO_EXTENSION);
            $targetPath = $gallery_dir . $uniqueName;
    
            if (move_uploaded_file($tmpName, $targetPath)) {
                $imagePaths[] = $targetPath;
            } else {
                die("Error uploading one of the images.");
            }
        }
    }
    
    $allImages = array_merge($existingImages, $imagePaths);
    $gallery_images = implode(',', $allImages);

    if ($id) {
        $query = "UPDATE ferries SET 
            name=?, image=?, description=?, type=?, origin=?, destination=?, 
            stopovers=?, departure_time=?, arrival_time=?, frequency=?, 
            fare_rates=?, booking_info=?, amenities=?, requirements=?, 
            location=?, google_map=?, phone=?, email=?, website=?, 
            policies=?, rating=?, reviews=?, nearby=?, gallery_images=?, details_link=?, 
            operator=?, capacity=?, travel_time=?, safety_rating=?, pet_policy=?, social_media=?,
            transportation_routes=?
            WHERE id=?";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param(
            "ssssssssssssssssssssssssssssssssi", 
            $name, $target_file, $description, $type, $origin, $destination, 
            $stopovers, $departure_time, $arrival_time, $frequency, 
            $fare_rates, $booking_info, $amenities, $requirements, 
            $location, $google_map, $phone, $email, $website, 
            $policies, $rating, $reviews, $nearby_json, $gallery_images, $details_link,
            $operator, $capacity, $travel_time, $safety_rating, $pet_policy, $social_media,
            $transportation_routes_json,
            $id
        );
    } else {
        $query = "INSERT INTO ferries (
            name, image, description, type, origin, destination, 
            stopovers, departure_time, arrival_time, frequency, 
            fare_rates, booking_info, amenities, requirements, 
            location, google_map, phone, email, website, 
            policies, rating, reviews, nearby, gallery_images, details_link,
            operator, capacity, travel_time, safety_rating, pet_policy, social_media,
            transportation_routes
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param(
            "ssssssssssssssssssssssssssssssss", 
            $name, $target_file, $description, $type, $origin, $destination, 
            $stopovers, $departure_time, $arrival_time, $frequency, 
            $fare_rates, $booking_info, $amenities, $requirements, 
            $location, $google_map, $phone, $email, $website, 
            $policies, $rating, $reviews, $nearby_json, $gallery_images, $details_link,
            $operator, $capacity, $travel_time, $safety_rating, $pet_policy, $social_media,
            $transportation_routes_json
        );
    }

    $stmt->execute();
    header("Location: add_ferries.php");
    exit();
}

$nearby_str = '';
$transportation_routes = array();

if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $stmt = $conn->prepare("SELECT nearby, transportation_routes FROM ferries WHERE id=?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (!empty($row['nearby'])) {
            $decoded_places = json_decode($row['nearby'], true);
            if (is_array($decoded_places)) {
                $nearby_str = implode(', ', $decoded_places);
            }
        }
        if (!empty($row['transportation_routes'])) {
            $decoded_routes = json_decode($row['transportation_routes'], true);
            if (is_array($decoded_routes)) {
                $transportation_routes = $decoded_routes;
            }
        }
    }
}

// Set route count for display
$route_count = count($transportation_routes);
if ($route_count == 0) {
    $route_count = 1; // Start with one empty row
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shipping and Ferries Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: url('img/bg.png') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .card-img-container {
            height: 150px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .card-img-container img {
            max-height: 100%;
            max-width: 100%;
            object-fit: contain;
        }
        .form-section {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .transport-table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        .transport-table .form-control {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
        }
        .transport-table select.form-control {
            height: calc(1.5em + 0.75rem + 2px);
        }
        .btn-add-row {
            margin-top: 10px;
        }
        .modal-body {
            max-height: 80vh;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <button type="button" class="btn btn-light text-dark mb-3" onclick="window.location.href='dashboard.php'">Back to Dashboard</button>
        <h1 class="text-light">Shipping and Ferries Management</h1>
        <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#ferryModal">Add Ferry</button>
        
        <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-3">
            <?php while ($ferry = $ferries->fetch_assoc()) { ?>
                <div class="col">
                    <div class="card h-100">
                        <div class="card-img-container p-3">
                            <img src="<?php echo $ferry['image']; ?>" class="card-img-top" alt="Ferry Image">
                        </div>
                        <div class="card-body">
                            <h5 class="card-title"><?php echo $ferry['name']; ?></h5>
                            <p class="card-text"><?php echo $ferry['origin']; ?> to <?php echo $ferry['destination']; ?></p>
                        </div>
                        <div class="card-footer bg-transparent">
                            <div class="d-flex justify-content-between">
                                <a href="add_ferries.php?edit=<?php echo $ferry['id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                                <a href="?delete=<?php echo $ferry['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">Delete</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>

    <!-- Add/Edit Ferry Modal -->
    <div class="modal fade <?php echo isset($_GET['edit']) ? 'show d-block' : ''; ?>" id="ferryModal" tabindex="-1" aria-labelledby="ferryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="ferryModalLabel"><?php echo isset($_GET['edit']) ? 'Edit Ferry' : 'Add Ferry'; ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php 
                        $ferryData = [
                            'id' => '', 'name' => '', 'image' => '', 'description' => '', 'type' => '', 
                            'origin' => '', 'destination' => '', 'stopovers' => '', 'departure_time' => '', 
                            'arrival_time' => '', 'frequency' => '', 'fare_rates' => '', 'booking_info' => '', 
                            'amenities' => '', 'requirements' => '', 'location' => '', 'google_map' => '', 
                            'phone' => '', 'email' => '', 'website' => '', 'policies' => '', 
                            'rating' => '', 'reviews' => '', 'nearby' => '', 'gallery_images' => '', 'details_link' => '',
                            'operator' => '', 'capacity' => '', 'travel_time' => '', 
                            'safety_rating' => '', 'pet_policy' => '', 'social_media' => ''
                        ];
                        
                        if (isset($_GET['edit'])) {
                            $edit_id = $_GET['edit'];
                            $result = $conn->query("SELECT * FROM ferries WHERE id=$edit_id");
                            if ($result->num_rows > 0) {
                                $ferryData = $result->fetch_assoc();
                            }
                        }
                    ?>
                    <form method="POST" action="add_ferries.php" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="<?php echo $ferryData['id']; ?>">
                        
                        <!-- Basic Information -->
                        <div class="form-section">
                            <h5>Basic Information</h5>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Name:</label>
                                    <input type="text" class="form-control" name="name" value="<?php echo $ferryData['name']; ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Type of Vessel:</label>
                                    <input type="text" class="form-control" name="type" value="<?php echo $ferryData['type']; ?>">
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Operator:</label>
                                    <input type="text" class="form-control" name="operator" value="<?php echo $ferryData['operator']; ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Capacity:</label>
                                    <input type="text" class="form-control" name="capacity" value="<?php echo $ferryData['capacity']; ?>">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Description:</label>
                                <textarea class="form-control" name="description" rows="3"><?php echo $ferryData['description']; ?></textarea>
                            </div>
                        </div>
                        
                        <!-- Route Information -->
                        <div class="form-section">
                            <h5>Route Information</h5>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Origin:</label>
                                    <input type="text" class="form-control" name="origin" value="<?php echo $ferryData['origin']; ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Destination:</label>
                                    <input type="text" class="form-control" name="destination" value="<?php echo $ferryData['destination']; ?>">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Stopovers:</label>
                                <input type="text" class="form-control" name="stopovers" value="<?php echo $ferryData['stopovers']; ?>" placeholder="Separate with commas">
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label">Travel Time:</label>
                                    <input type="text" class="form-control" name="travel_time" value="<?php echo $ferryData['travel_time']; ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Safety Rating:</label>
                                    <input type="text" class="form-control" name="safety_rating" value="<?php echo $ferryData['safety_rating']; ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Pet Policy:</label>
                                    <input type="text" class="form-control" name="pet_policy" value="<?php echo $ferryData['pet_policy']; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Schedule Information -->
                        <div class="form-section">
                            <h5>Schedule Information</h5>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Departure Time:</label>
                                    <input type="text" class="form-control" name="departure_time" value="<?php echo $ferryData['departure_time']; ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Arrival Time:</label>
                                    <input type="text" class="form-control" name="arrival_time" value="<?php echo $ferryData['arrival_time']; ?>">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Frequency:</label>
                                <input type="text" class="form-control" name="frequency" value="<?php echo $ferryData['frequency']; ?>">
                            </div>
                        </div>
                        
                        <!-- Ticket Information -->
                        <div class="form-section">
                            <h5>Ticket Information</h5>
                            <div class="mb-3">
                                <label class="form-label">Fare Rates (one per line):</label>
                                <textarea class="form-control" name="fare_rates" rows="3"><?php echo $ferryData['fare_rates']; ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Booking Information:</label>
                                <textarea class="form-control" name="booking_info" rows="3"><?php echo $ferryData['booking_info']; ?></textarea>
                            </div>
                        </div>
                        
                        <!-- Ratings and Reviews -->
                        <div class="form-section">
                            <h5>Ratings and Reviews</h5>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Rating:</label>
                                    <input type="text" class="form-control" name="rating" value="<?php echo $ferryData['rating']; ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Reviews:</label>
                                    <textarea class="form-control" name="reviews" rows="3"><?php echo $ferryData['reviews']; ?></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Amenities and Requirements -->
                        <div class="form-section">
                            <h5>Amenities and Requirements</h5>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Amenities:</label>
                                    <textarea class="form-control" name="amenities" rows="3"><?php echo $ferryData['amenities']; ?></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Requirements:</label>
                                    <textarea class="form-control" name="requirements" rows="3"><?php echo $ferryData['requirements']; ?></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Transportation Routes -->
                        <div class="form-section">
                            <h5>Transportation Routes</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered transport-table">
                                    <thead>
                                        <tr>
                                            <th width="15%">Transport Type</th>
                                            <th width="20%">Route Name/Number</th>
                                            <th width="25%">Route Link (Optional)</th>
                                            <th width="30%">Description (Optional)</th>
                                            <th width="10%">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="transport-routes-tbody">
                                        <?php for ($i = 0; $i < $route_count; $i++): ?>
                                            <tr>
                                                <td>
                                                    <select class="form-control" name="route_type[]">
                                                        <option value="">Select Type</option>
                                                        <option value="bus" <?php echo (isset($transportation_routes[$i]['type']) && $transportation_routes[$i]['type'] == 'bus') ? 'selected' : ''; ?>>Bus</option>
                                                        <option value="jeepney" <?php echo (isset($transportation_routes[$i]['type']) && $transportation_routes[$i]['type'] == 'jeepney') ? 'selected' : ''; ?>>Jeepney</option>
                                                        <option value="taxi" <?php echo (isset($transportation_routes[$i]['type']) && $transportation_routes[$i]['type'] == 'taxi') ? 'selected' : ''; ?>>Taxi</option>
                                                        <option value="tricycle" <?php echo (isset($transportation_routes[$i]['type']) && $transportation_routes[$i]['type'] == 'tricycle') ? 'selected' : ''; ?>>Tricycle</option>
                                                        <option value="shuttle" <?php echo (isset($transportation_routes[$i]['type']) && $transportation_routes[$i]['type'] == 'shuttle') ? 'selected' : ''; ?>>Shuttle Service</option>
                                                        <option value="private_car" <?php echo (isset($transportation_routes[$i]['type']) && $transportation_routes[$i]['type'] == 'private_car') ? 'selected' : ''; ?>>Private Car</option>
                                                        <option value="motorcycle" <?php echo (isset($transportation_routes[$i]['type']) && $transportation_routes[$i]['type'] == 'motorcycle') ? 'selected' : ''; ?>>Motorcycle</option>
                                                        <option value="walking" <?php echo (isset($transportation_routes[$i]['type']) && $transportation_routes[$i]['type'] == 'walking') ? 'selected' : ''; ?>>Walking Route</option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control" name="route_name[]" value="<?php echo isset($transportation_routes[$i]['name']) ? htmlspecialchars($transportation_routes[$i]['name']) : ''; ?>" placeholder="e.g., Port Shuttle, Route 101, Taxi Stand">
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control" name="route_link[]" value="<?php echo isset($transportation_routes[$i]['link']) ? htmlspecialchars($transportation_routes[$i]['link']) : ''; ?>" placeholder="https://maps.google.com/...">
                                                </td>
                                                <td>
                                                    <textarea class="form-control" name="route_description[]" rows="2" placeholder="e.g., Direct shuttle from city center to port, operates every 30 minutes"><?php echo isset($transportation_routes[$i]['description']) ? htmlspecialchars($transportation_routes[$i]['description']) : ''; ?></textarea>
                                                </td>
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)">
                                                        Remove
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endfor; ?>
                                    </tbody>
                                </table>
                            </div>
                            <button type="button" class="btn btn-secondary btn-sm btn-add-row" onclick="addRow()">
                                Add Row
                            </button>
                        </div>
                        
                        <!-- Location Information -->
                        <div class="form-section">
                            <h5>Location Information</h5>
                            <div class="mb-3">
                                <label class="form-label">Port Location:</label>
                                <input type="text" class="form-control" name="location" value="<?php echo $ferryData['location']; ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Google Map Embed URL:</label>
                                <input type="text" class="form-control" name="google_map" value="<?php echo $ferryData['google_map']; ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Nearby Places (separate with commas):</label>
                                <input type="text" class="form-control" name="nearby" value="<?php echo htmlspecialchars($nearby_str); ?>">
                            </div>
                        </div>
                        
                        <!-- Contact Information -->
                        <div class="form-section">
                            <h5>Contact Information</h5>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Phone:</label>
                                    <input type="text" class="form-control" name="phone" value="<?php echo $ferryData['phone']; ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email:</label>
                                    <input type="email" class="form-control" name="email" value="<?php echo $ferryData['email']; ?>">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Website:</label>
                                <input type="url" class="form-control" name="website" value="<?php echo $ferryData['website']; ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Social Media Links (one per line):</label>
                                <textarea class="form-control" name="social_media" rows="5"><?php echo isset($ferryData['social_media']) ? htmlspecialchars($ferryData['social_media']) : ''; ?></textarea>
                            </div>
                        </div>
                        
                        <!-- Policies -->
                        <div class="form-section">
                            <h5>Policies</h5>
                            <div class="mb-3">
                                <label class="form-label">Safety Policies:</label>
                                <textarea class="form-control" name="policies" rows="3"><?php echo $ferryData['policies']; ?></textarea>
                            </div>
                        </div>
                        
                        <!-- Images -->
                        <div class="form-section">
                            <h5>Images</h5>
                            <div class="mb-3">
                                <label class="form-label">Upload Main Image:</label>
                                <input type="file" class="form-control" name="image">
                                <?php if ($ferryData['image']) { ?>
                                    <img src="<?php echo $ferryData['image']; ?>" class="img-thumbnail mt-2" width="100">
                                    <input type="hidden" name="existing_image" value="<?php echo $ferryData['image']; ?>">
                                <?php } ?>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Upload Gallery Images (max 5):</label>
                                <input type="file" class="form-control" name="gallery_images[]" multiple accept="image/*">
                                
                                <?php if (!empty($ferryData['gallery_images'])) {
                                    $images = explode(',', $ferryData['gallery_images']); 
                                    echo '<div class="d-flex flex-wrap mt-2">';
                                    foreach ($images as $img) { 
                                        if (!empty($img)) { ?>
                                            <div class="position-relative me-2 mb-2">
                                                <img src="<?php echo htmlspecialchars($img); ?>" class="img-thumbnail" width="100">
                                            </div>
                                        <?php }
                                    }
                                    echo '</div>';
                                    ?>
                                    <input type="hidden" name="existing_gallery_images" value="<?php echo htmlspecialchars($ferryData['gallery_images']); ?>">
                                <?php } ?>
                            </div>
                        </div>
                        
                        <!-- Details Link -->
                        <div class="form-section">
                            <div class="mb-3">
                                <label class="form-label">Details Page Link:</label>
                                <input type="text" class="form-control" name="details_link" value="<?php echo htmlspecialchars($ferryData['details_link']); ?>" placeholder="e.g., ferry1.php?id=1">
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-success"><?php echo isset($_GET['edit']) ? 'Update' : 'Save'; ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Show modal if editing
    <?php if (isset($_GET['edit'])): ?>
        document.addEventListener('DOMContentLoaded', function() {
            var myModal = new bootstrap.Modal(document.getElementById('ferryModal'));
            myModal.show();
        });
    <?php endif; ?>
    
    function addRow() {
        var tbody = document.getElementById('transport-routes-tbody');
        var newRow = document.createElement('tr');
        newRow.innerHTML = `
            <td>
                <select class="form-control" name="route_type[]">
                    <option value="">Select Type</option>
                    <option value="bus">Bus</option>
                    <option value="jeepney">Jeepney</option>
                    <option value="taxi">Taxi</option>
                    <option value="tricycle">Tricycle</option>
                    <option value="shuttle">Shuttle Service</option>
                    <option value="private_car">Private Car</option>
                    <option value="motorcycle">Motorcycle</option>
                    <option value="walking">Walking Route</option>
                </select>
            </td>
            <td>
                <input type="text" class="form-control" name="route_name[]" placeholder="e.g., Port Shuttle, Route 101, Taxi Stand">
            </td>
            <td>
                <input type="text" class="form-control" name="route_link[]" placeholder="https://maps.google.com/...">
            </td>
            <td>
                <textarea class="form-control" name="route_description[]" rows="2" placeholder="e.g., Direct shuttle from city center to port, operates every 30 minutes"></textarea>
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)">
                    Remove
                </button>
            </td>
        `;
        tbody.appendChild(newRow);
    }
    
    function removeRow(button) {
        var row = button.closest('tr');
        var tbody = document.getElementById('transport-routes-tbody');
        var rows = tbody.querySelectorAll('tr');
        
        // Don't remove if it's the last row
        if (rows.length > 1) {
            row.remove();
        }
    }
    </script>
</body>
</html>
<?php $conn->close(); ?>