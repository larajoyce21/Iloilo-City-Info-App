<?php
include 'conn.php';

if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    mysqli_query($conn, "DELETE FROM districts WHERE id=$delete_id");
    header("Location: add_districts.php");
    exit();
}

if (isset($_GET['delete_image'])) {
    $image_path = $_GET['delete_image'];
    $district_id = $_GET['district_id'];
    
    $result = mysqli_query($conn, "SELECT image_detail FROM districts WHERE id=$district_id");
    if (mysqli_num_rows($result) > 0) {
        $district = mysqli_fetch_assoc($result);
        $images = explode(',', $district['image_detail']);
        
        $new_images = array_filter($images, function($img) use ($image_path) {
            return $img !== $image_path;
        });
        
        $new_image_detail = implode(',', $new_images);
        mysqli_query($conn, "UPDATE districts SET image_detail='$new_image_detail' WHERE id=$district_id");
        
        if (file_exists($image_path)) {
            unlink($image_path);
        }
    }
    header("Location: add_districts.php?edit=$district_id");
    exit();
}

$query = "SELECT * FROM districts";
$result = mysqli_query($conn, $query);
$districts = mysqli_fetch_all($result, MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'] ?? null;
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $feast = $_POST['feast'] ?? '';
    $tribe = $_POST['tribe'] ?? '';
    $location = $_POST['location'] ?? '';
    $hotlines = $_POST['hotlines'] ?? '';
    $maps_link = $_POST['maps_link'] ?? '';
    $directions_link = $_POST['directions_link'] ?? '';
    $mapEmbedURL = $_POST['map_embed'] ?? '';
    $history = $_POST['history'] ?? '';
    $fun_fact = $_POST['fun_fact'] ?? '';
    $source = $_POST['source'] ?? '';
    $population = $_POST['population'] ?? '';
    $area = $_POST['area'] ?? '';
    $barangays = $_POST['barangays'] ?? '';
    $zip_code = $_POST['zip_code'] ?? '';
    $established = $_POST['established'] ?? '';
    $contact = $_POST['contact'] ?? '';
    $email = $_POST['email'] ?? '';
    $website = $_POST['website'] ?? '';
    $hours = $_POST['hours'] ?? '';
    $fee = $_POST['fee'] ?? '';
    $parking = $_POST['parking'] ?? '';
    $bike_parking = $_POST['bike_parking'] ?? '';
    $transportation_options = $_POST['transportation_options'] ?? '';
    $nearby_places = $_POST['nearby_places'] ?? '';
    $social_media = $_POST['social_media'] ?? '';
    $details_link = $_POST['details_link'] ?? '';

    // Handle transportation routes
    $transportation_routes = array();
    
    // Process table rows
    if (isset($_POST['route_type']) && is_array($_POST['route_type'])) {
        $route_count = count($_POST['route_type']);
        
        for ($i = 0; $i < $route_count; $i++) {
            $type = trim($_POST['route_type'][$i] ?? '');
            $name_route = trim($_POST['route_name'][$i] ?? '');
            
            if (!empty($type) && !empty($name_route)) {
                $transportation_routes[] = array(
                    'type' => $type,
                    'name' => $name_route,
                    'link' => trim($_POST['route_link'][$i] ?? ''),
                    'description' => trim($_POST['route_description'][$i] ?? '')
                );
            }
        }
    }
    
    $transportation_routes_json = json_encode($transportation_routes);

    $target_dir = "uploads/";
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];

    $target_file = $_POST['existing_image'] ?? ''; 
    if (!empty($_FILES['image']['name'])) {
        $target_file = $target_dir . basename($_FILES["image"]["name"]);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        if (in_array($imageFileType, $allowed_types)) {
            move_uploaded_file($_FILES["image"]["tmp_name"], $target_file);
        } else {
            echo "Error: Only JPG, JPEG, PNG, and GIF files are allowed.";
        }
    }

    $imagePaths = [];

    $existingImages = [];
    if (!empty($_POST['existing_image_detail'])) {
        $existingImages = explode(',', $_POST['existing_image_detail']);
    }

    if (!empty($_FILES['image_detail']['name'][0])) {
        $totalNew = count($_FILES['image_detail']['name']);
        $totalCombined = count($existingImages) + $totalNew;

        if ($totalCombined > 5) {
            die("Error: You can upload a maximum of 5 detail images total.");
        }

        for ($i = 0; $i < $totalNew; $i++) {
            $fileName = basename($_FILES['image_detail']['name'][$i]);
            $tmpName = $_FILES['image_detail']['tmp_name'][$i];
            $imageFileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            if (in_array($imageFileType, $allowed_types)) {
                $uniqueName = uniqid('img_', true) . '.' . $imageFileType;
                $targetPath = $target_dir . $uniqueName;

                if (move_uploaded_file($tmpName, $targetPath)) {
                    $imagePaths[] = $targetPath;
                } else {
                    die("Error uploading one of the images.");
                }
            } else {
                die("Error: Only JPG, JPEG, PNG, and GIF files are allowed.");
            }
        }
    }
    
    $allImages = array_merge($existingImages, $imagePaths);
    $target_file_detail = implode(',', $allImages);
    $target_file_detail = mysqli_real_escape_string($conn, $target_file_detail);
    $transportation_routes_json = mysqli_real_escape_string($conn, $transportation_routes_json);
  
    if ($id) {
        $query = "UPDATE districts SET 
            name='$name', 
            image='$target_file', 
            image_detail='$target_file_detail', 
            description='$description', 
            feast='$feast', 
            tribe='$tribe', 
            location='$location', 
            maps_embed='$mapEmbedURL', 
            hotlines='$hotlines', 
            maps_link='$maps_link', 
            directions_link='$directions_link', 
            history='$history', 
            fun_fact='$fun_fact', 
            source='$source', 
            population='$population', 
            area='$area', 
            barangays='$barangays', 
            zip_code='$zip_code', 
            established='$established', 
            contact='$contact', 
            email='$email', 
            website='$website', 
            hours='$hours', 
            fee='$fee', 
            parking='$parking', 
            bike_parking='$bike_parking', 
            transportation_options='$transportation_options', 
            nearby_places='$nearby_places', 
            social_media='$social_media', 
            details_link='$details_link',
            transportation_routes='$transportation_routes_json' 
            WHERE id=$id";
            
        mysqli_query($conn, $query);
    } else {
        $query = "INSERT INTO districts 
            (name, image, image_detail, description, feast, tribe, location, maps_embed, hotlines, maps_link, directions_link, history, fun_fact, source, population, area, barangays, zip_code, established, contact, email, website, hours, fee, parking, bike_parking, transportation_options, nearby_places, social_media, details_link, transportation_routes) 
            VALUES 
            ('$name', '$target_file', '$target_file_detail', '$description', '$feast', '$tribe', '$location', '$mapEmbedURL', '$hotlines', '$maps_link', '$directions_link', '$history', '$fun_fact', '$source', '$population', '$area', '$barangays', '$zip_code', '$established', '$contact', '$email', '$website', '$hours', '$fee', '$parking', '$bike_parking', '$transportation_options', '$nearby_places', '$social_media', '$details_link', '$transportation_routes_json')";
            
        mysqli_query($conn, $query);
    }
    
    if (mysqli_affected_rows($conn) > 0) {
        header("Location: add_districts.php");
        exit();
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}

// Prepare transportation routes for editing
$transportation_routes = array();
if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $result = mysqli_query($conn, "SELECT transportation_routes FROM districts WHERE id=$edit_id");
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
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
    <title>District Management</title>
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
        
        .card {
            transition: transform 0.3s;
            border-radius: 20px;
            overflow: hidden;
        }
        
        .card:hover {
            transform: translateY(-5px);
        }
        
        .modal-content {
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .form-section {
            background: rgba(248, 249, 250, 0.9);
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

    </style>
</head>
<body>
    <br>
    <div class="container">
        <button type="button" class="btn btn-light text-dark" onclick="window.location.href='dashboard.php'">Back to Dashboard</button>
    </div>
    <div class="container mt-5">
        <h1 class="text-light">Districts Management</h1>
        <br>
        <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#districtModal">Add District</button>
        <div class="row row-cols-1 row-cols-md-3 row-cols-lg-5 g-3">
        <?php foreach ($districts as $district) { ?>
            <div class="col">
            <div class="card h-100 d-flex flex-column" style="height: 330px; display: flex; flex-direction: column; justify-content: space-between;">
            <img src="<?php echo $district['image']; ?>" class="card-img-top" alt="district Image" style="height: 150px; width: 150px; object-fit: cover; display: block; margin: auto;">
            <div class="card-body d-flex flex-column flex-grow-1">
                    <h6 class="card-title"><?php echo $district['name']; ?></h6>
                    <div class="mt-auto d-flex justify-content-center gap-2">
                    <a href="add_districts.php?edit=<?php echo $district['id']; ?>" class="btn btn-primary">Update</a>
                    <a href="?delete=<?php echo $district['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                    </div>
                </div>
            </div>
        </div>
        <?php } ?>
        </div>
    </div>

    <!-- Add/Edit district Modal -->
    <div class="modal fade <?php echo isset($_GET['edit']) ? 'show d-block' : ''; ?>" id="districtModal" tabindex="-1" aria-labelledby="districtModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="districtModalLabel"><?php echo isset($_GET['edit']) ? 'Edit District' : 'Add District'; ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php 
                        $districtData = [
                            'id' => '', 'name' => '', 'description' => '', 'feast' => '',  'tribe' => '', 
                            'location' => '', 'hotlines' => '', 'image' => '','maps_embed' => '', 
                            'maps_link' => '','directions_link' => '', 'history' => '', 'fun_fact' => '',
                            'source' => '', 'population' => '', 'area' => '', 'barangays' => '',
                            'zip_code' => '', 'established' => '', 'contact' => '', 'email' => '',
                            'website' => '', 'hours' => '', 'fee' => '', 'parking' => '',
                            'bike_parking' => '', 'transportation_options' => '', 'nearby_places' => '',
                            'social_media' => '', 'details_link' => '', 'transportation_routes' => ''
                        ];
                        
                        if (isset($_GET['edit'])) {
                            $edit_id = $_GET['edit'];
                            $result = mysqli_query($conn, "SELECT * FROM districts WHERE id=$edit_id");
                            if (mysqli_num_rows($result) > 0) {
                                $districtData = mysqli_fetch_assoc($result);
                            }
                        }
                    ?>
                    <form method="POST" action="add_districts.php" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="<?php echo $districtData['id']; ?>">
                        
                        <!-- Basic Information Section -->
                        <div class="form-section">
                            <h5>Basic Information</h5>
                            <div class="mb-3">
                                <label class="form-label">Name:</label>
                                <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($districtData['name']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Details Page Link</label>
                                <input type="text" class="form-control" name="details_link" value="<?php echo htmlspecialchars($districtData['details_link']); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Upload Card Image:</label>
                                <input type="file" class="form-control" name="image">
                                <?php if ($districtData['image']) { ?>
                                    <img src="<?php echo $districtData['image']; ?>" class="img-thumbnail mt-2" width="100">
                                    <input type="hidden" name="existing_image" value="<?php echo $districtData['image']; ?>">
                                <?php } ?>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Upload Detail Images (2-5):</label>
                                <input type="file" class="form-control" name="image_detail[]" multiple accept="image/*">
                                
                                <?php if (!empty($districtData['image_detail'])) {
                                    $images = explode(',', $districtData['image_detail']); 
                                    echo '<div class="d-flex flex-wrap mt-2">';
                                    foreach ($images as $img) { 
                                        if (!empty($img)) { ?>
                                            <div class="position-relative me-2 mb-2">
                                                <img src="<?php echo htmlspecialchars($img); ?>" class="img-thumbnail" width="100">
                                                <a href="?delete_image=<?php echo urlencode($img); ?>&district_id=<?php echo $districtData['id']; ?>" 
                                                   class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" 
                                                   onclick="return confirm('Are you sure you want to delete this image?')">
                                                   ×
                                                </a>
                                            </div>
                                        <?php }
                                    }
                                    echo '</div>';
                                    ?>
                                    <input type="hidden" name="existing_image_detail" value="<?php echo htmlspecialchars($districtData['image_detail']); ?>">
                                <?php } ?>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Description:</label>
                                <textarea class="form-control" name="description" rows="4"><?php echo htmlspecialchars($districtData['description']); ?></textarea>
                            </div>
                        </div>
                        
                        <!-- History & Culture Section -->
                        <div class="form-section">
                            <h5>History & Culture</h5>
                            <div class="mb-3">
                                <label class="form-label">History:</label>
                                <textarea class="form-control" name="history" rows="4"><?php echo htmlspecialchars($districtData['history']); ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Fun Fact:</label>
                                <textarea class="form-control" name="fun_fact" rows="2"><?php echo htmlspecialchars($districtData['fun_fact']); ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Source:</label>
                                <input type="text" class="form-control" name="source" value="<?php echo htmlspecialchars($districtData['source']); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Feast Day:</label>
                                <textarea class="form-control" name="feast" rows="2"><?php echo htmlspecialchars($districtData['feast']); ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Tribe:</label>
                                <textarea class="form-control" name="tribe" rows="2"><?php echo htmlspecialchars($districtData['tribe']); ?></textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Population:</label>
                                    <input type="text" class="form-control" name="population" value="<?php echo htmlspecialchars($districtData['population']); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Area:</label>
                                    <input type="text" class="form-control" name="area" value="<?php echo htmlspecialchars($districtData['area']); ?>">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Barangays:</label>
                                    <input type="text" class="form-control" name="barangays" value="<?php echo htmlspecialchars($districtData['barangays']); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Zip Code:</label>
                                    <input type="text" class="form-control" name="zip_code" value="<?php echo htmlspecialchars($districtData['zip_code']); ?>">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Established:</label>
                                <input type="text" class="form-control" name="established" value="<?php echo htmlspecialchars($districtData['established']); ?>">
                            </div>
                        </div>
                        
                        <!-- Visit Information Section -->
                        <div class="form-section">
                            <h5>Visit Information</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Emergency Hotlines:</label>
                                    <textarea class="form-control" name="hotlines" rows="2"><?php echo htmlspecialchars($districtData['hotlines']); ?></textarea>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Contact:</label>
                                    <input type="text" class="form-control" name="contact" value="<?php echo htmlspecialchars($districtData['contact']); ?>">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email:</label>
                                    <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($districtData['email']); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Website:</label>
                                    <input type="url" class="form-control" name="website" value="<?php echo htmlspecialchars($districtData['website']); ?>">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Operating Hours:</label>
                                <textarea class="form-control" name="hours" rows="2"><?php echo htmlspecialchars($districtData['hours']); ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Entrance Fee:</label>
                                <input type="text" class="form-control" name="fee" value="<?php echo htmlspecialchars($districtData['fee']); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Parking Information:</label>
                                <textarea class="form-control" name="parking" rows="2"><?php echo htmlspecialchars($districtData['parking']); ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Bike Parking:</label>
                                <textarea class="form-control" name="bike_parking" rows="2"><?php echo htmlspecialchars($districtData['bike_parking']); ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Transportation Options (one per line):</label>
                                <textarea class="form-control" name="transportation_options" rows="4"><?php echo htmlspecialchars($districtData['transportation_options']); ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Social Media Links (one per line):</label>
                                <textarea class="form-control" name="social_media" rows="4"><?php echo htmlspecialchars($districtData['social_media']); ?></textarea>
                            </div>
                        </div>
                        
                        <!-- Transportation Routes Section -->
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
                                                        <option value="jeepney" <?php echo (isset($transportation_routes[$i]['type']) && $transportation_routes[$i]['type'] == 'jeepney') ? 'selected' : ''; ?>>Jeepney</option>
                                                        <option value="bus" <?php echo (isset($transportation_routes[$i]['type']) && $transportation_routes[$i]['type'] == 'bus') ? 'selected' : ''; ?>>Bus</option>
                                                        <option value="taxi" <?php echo (isset($transportation_routes[$i]['type']) && $transportation_routes[$i]['type'] == 'taxi') ? 'selected' : ''; ?>>Taxi</option>
                                                        <option value="tricycle" <?php echo (isset($transportation_routes[$i]['type']) && $transportation_routes[$i]['type'] == 'tricycle') ? 'selected' : ''; ?>>Tricycle</option>
                                                        <option value="motorcycle" <?php echo (isset($transportation_routes[$i]['type']) && $transportation_routes[$i]['type'] == 'motorcycle') ? 'selected' : ''; ?>>Motorcycle</option>
                                                        <option value="bicycle" <?php echo (isset($transportation_routes[$i]['type']) && $transportation_routes[$i]['type'] == 'bicycle') ? 'selected' : ''; ?>>Bicycle</option>
                                                        <option value="ferry" <?php echo (isset($transportation_routes[$i]['type']) && $transportation_routes[$i]['type'] == 'ferry') ? 'selected' : ''; ?>>Ferry</option>
                                                        <option value="boat" <?php echo (isset($transportation_routes[$i]['type']) && $transportation_routes[$i]['type'] == 'boat') ? 'selected' : ''; ?>>Boat</option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control" name="route_name[]" value="<?php echo isset($transportation_routes[$i]['name']) ? htmlspecialchars($transportation_routes[$i]['name']) : ''; ?>" placeholder="e.g., Route 10, UV Express">
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control" name="route_link[]" value="<?php echo isset($transportation_routes[$i]['link']) ? htmlspecialchars($transportation_routes[$i]['link']) : ''; ?>" placeholder="https://maps.google.com/...">
                                                </td>
                                                <td>
                                                    <textarea class="form-control" name="route_description[]" rows="2" placeholder="e.g., Stops at City Hall, SM City Iloilo"><?php echo isset($transportation_routes[$i]['description']) ? htmlspecialchars($transportation_routes[$i]['description']) : ''; ?></textarea>
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
                        
                        <!-- Location Information Section -->
                        <div class="form-section">
                            <h5>Location Information</h5>
                            <div class="mb-3">
                                <label class="form-label">Address:</label>
                                <input type="text" class="form-control" name="location" value="<?php echo htmlspecialchars($districtData['location']); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Google Maps Link:</label>
                                <input type="url" class="form-control" name="maps_link" value="<?php echo htmlspecialchars($districtData['maps_link']); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Google Maps Directions Link:</label>
                                <input type="url" class="form-control" name="directions_link" value="<?php echo htmlspecialchars($districtData['directions_link']); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Embed Google Map URL:</label>
                                <input type="url" class="form-control" name="map_embed" value="<?php echo htmlspecialchars($districtData['maps_embed']); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Nearby Places (comma separated):</label>
                                <textarea class="form-control" name="nearby_places" rows="3"><?php echo htmlspecialchars($districtData['nearby_places']); ?></textarea>
                            </div>
                        </div>
                        
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-success"><?php echo isset($_GET['edit']) ? 'Update' : 'Save'; ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Show the modal if editing
        <?php if (isset($_GET['edit'])): ?>
            document.addEventListener('DOMContentLoaded', function() {
                var myModal = new bootstrap.Modal(document.getElementById('districtModal'));
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
                        <option value="jeepney">Jeepney</option>
                        <option value="bus">Bus</option>
                        <option value="taxi">Taxi</option>
                        <option value="tricycle">Tricycle</option>
                        <option value="motorcycle">Motorcycle</option>
                        <option value="bicycle">Bicycle</option>
                        <option value="ferry">Ferry</option>
                        <option value="boat">Boat</option>
                    </select>
                </td>
                <td>
                    <input type="text" class="form-control" name="route_name[]" placeholder="e.g., Route 10, UV Express">
                </td>
                <td>
                    <input type="text" class="form-control" name="route_link[]" placeholder="https://maps.google.com/...">
                </td>
                <td>
                    <textarea class="form-control" name="route_description[]" rows="2" placeholder="e.g., Stops at City Hall, SM City Iloilo"></textarea>
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
<?php mysqli_close($conn); ?>