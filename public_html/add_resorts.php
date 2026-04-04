<?php
include 'conn.php';

if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    
    $stmt = $conn->prepare("SELECT image, image_detail FROM resorts WHERE id=?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $resort = $result->fetch_assoc();
    
    $stmt = $conn->prepare("DELETE FROM resorts WHERE id=?");
    $stmt->bind_param("i", $delete_id);
    
    if ($stmt->execute()) {
        if ($resort) {
            if (!empty($resort['image']) && file_exists($resort['image'])) {
                unlink($resort['image']);
            }
            
            if (!empty($resort['image_detail'])) {
                $images = explode(',', $resort['image_detail']);
                foreach ($images as $img) {
                    if (!empty($img) && file_exists($img)) {
                        unlink($img);
                    }
                }
            }
        }
        
        header("Location: add_resorts.php");
        exit();
    }
}

if (isset($_GET['delete_image']) && isset($_GET['resort_id'])) {
    $image_to_delete = $_GET['delete_image'];
    $resort_id = $_GET['resort_id'];
    
    $stmt = $conn->prepare("SELECT image_detail FROM resorts WHERE id=?");
    $stmt->bind_param("i", $resort_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    $images = explode(',', $row['image_detail']);
    $updated_images = array_diff($images, [$image_to_delete]);
    
    if (file_exists($image_to_delete)) {
        unlink($image_to_delete);
    }
    
    $stmt = $conn->prepare("UPDATE resorts SET image_detail=? WHERE id=?");
    $updated_images_str = implode(',', $updated_images);
    $stmt->bind_param("si", $updated_images_str, $resort_id);
    $stmt->execute();
    
    header("Location: add_resorts.php?edit=".$resort_id);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'] ?? null;
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $description = $_POST['description'] ?? '';
    $location = $_POST['location'] ?? '';
    $website = $_POST['website'] ?? '';
    $maps_link = $_POST['maps_link'] ?? '';
    $directions_link = $_POST['directions_link'] ?? '';
    $map_embed = $_POST['map_embed'] ?? '';
    $hours = $_POST['hours'] ?? '';
    $contact = $_POST['contact'] ?? '';
    $nearby_places = $_POST['nearby_places'] ?? '';
    $entrance_fee = $_POST['entrance_fee'] ?? '';
    $social_media = $_POST['social_media'] ?? '';
    $details_link = $_POST['details_link'] ?? '';
    $pool_features = $_POST['pool_features'] ?? '';
    $dining_options = $_POST['dining_options'] ?? '';
    $amenities = $_POST['amenities'] ?? '';
    $parking = $_POST['parking'] ?? '';
    $bike_parking = $_POST['bike_parking'] ?? '';
    $room_rates = $_POST['room_rates'] ?? '';
    $transportation_options = $_POST['transportation_options'] ?? '';
    
    $nearby_places_array = array_map('trim', explode(',', $nearby_places));
    $nearby_places_json = json_encode(array_filter($nearby_places_array));
    
    // Handle transportation routes
    $transportation_routes = array();
    if (isset($_POST['route_type']) && is_array($_POST['route_type'])) {
        foreach ($_POST['route_type'] as $index => $type) {
            if (!empty($type) && !empty($_POST['route_name'][$index])) {
                $transportation_routes[] = array(
                    'type' => $type,
                    'name' => $_POST['route_name'][$index],
                    'link' => $_POST['route_link'][$index] ?? '',
                    'description' => $_POST['route_description'][$index] ?? ''
                );
            }
        }
    }
    $transportation_routes_json = json_encode($transportation_routes);
    
    $target_dir = "uploads/";
    
    // Create uploads directory if it doesn't exist
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $target_file = $_POST['existing_image'] ?? '';
    if (!empty($_FILES['image']['name'])) {
        $file_name = basename($_FILES["image"]["name"]);
        $target_file = $target_dir . uniqid() . '_' . $file_name;
        move_uploaded_file($_FILES["image"]["tmp_name"], $target_file);
    }
    
    $existing_images = [];
    if (!empty($_POST['existing_image_detail'])) {
        $existing_images = explode(',', $_POST['existing_image_detail']);
    }
    
    $new_images = [];
    if (!empty($_FILES['image_detail']['name'][0])) {
        foreach ($_FILES['image_detail']['name'] as $key => $name) {
            if ($_FILES['image_detail']['error'][$key] === UPLOAD_ERR_OK) {
                $file_name = basename($name);
                $target_path = $target_dir . uniqid() . '_' . $file_name;
                if (move_uploaded_file($_FILES['image_detail']['tmp_name'][$key], $target_path)) {
                    $new_images[] = $target_path;
                }
            }
        }
    }
    
    $all_images = array_merge($existing_images, $new_images);
    $target_file_detail = implode(',', $all_images);
    
    if ($id) {
        // UPDATE query
        $query = "UPDATE resorts SET 
                  name=?, image=?, image_detail=?, description=?, location=?, 
                  maps_link=?, directions_link=?, maps_embed=?, hours=?, 
                  contact=?, nearby_places=?, entrance_fee=?, social_media=?, details_link=?, website=?,
                  pool_features=?, dining_options=?, amenities=?, parking=?, bike_parking=?, room_rates=?, transportation_options=?, email=?, transportation_routes=?
                  WHERE id=?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssssssssssssssssssssssi", 
            $name, $target_file, $target_file_detail, $description, $location,
            $maps_link, $directions_link, $map_embed, $hours,
            $contact, $nearby_places_json, $entrance_fee, $social_media, $details_link, $website,
            $pool_features, $dining_options, $amenities, $parking, $bike_parking, $room_rates, $transportation_options, $email, $transportation_routes_json, $id);
    } else {
        // INSERT query
        $query = "INSERT INTO resorts 
                  (name, image, image_detail, description, location, 
                   maps_link, directions_link, maps_embed, hours, 
                   contact, nearby_places, entrance_fee, social_media, details_link, website,
                   pool_features, dining_options, amenities, parking, bike_parking, room_rates, transportation_options, email, transportation_routes)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssssssssssssssssssssss", 
            $name, $target_file, $target_file_detail, $description, $location,
            $maps_link, $directions_link, $map_embed, $hours,
            $contact, $nearby_places_json, $entrance_fee, $social_media, $details_link, $website,
            $pool_features, $dining_options, $amenities, $parking, $bike_parking, $room_rates, $transportation_options, $email, $transportation_routes_json);
    }
    
    if ($stmt->execute()) {
        header("Location: add_resorts.php");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }
}

$query = "SELECT id, name, image, description, location, maps_link, directions_link, maps_embed, hours, contact, nearby_places, entrance_fee, social_media, details_link, website, pool_features, dining_options, amenities, parking, bike_parking, room_rates, transportation_options, email, transportation_routes FROM resorts";
$result = $conn->query($query);

$resortData = [
    'id' => '',
    'name' => '',
    'image' => '',
    'image_detail' => '',
    'description' => '',
    'location' => '',
    'website' => '',
    'maps_link' => '',
    'directions_link' => '',
    'maps_embed' => '',
    'hours' => '',
    'contact' => '',
    'nearby_places' => '',
    'entrance_fee' => '',
    'social_media' => '',
    'details_link' => '',
    'pool_features' => '',
    'dining_options' => '',
    'amenities' => '',
    'parking' => '',
    'bike_parking' => '',
    'room_rates' => '',
    'transportation_options' => '',
    'email' => '',
    'transportation_routes' => ''
];

$nearby_places_str = '';
$transportation_routes = array();

if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM resorts WHERE id=?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result_edit = $stmt->get_result();
    
    if ($result_edit->num_rows > 0) {
        $resortData = $result_edit->fetch_assoc();
        
        if (!empty($resortData['nearby_places'])) {
            $decoded = json_decode($resortData['nearby_places'], true);
            if (is_array($decoded)) {
                $nearby_places_str = implode(', ', $decoded);
            }
        }
        
        if (!empty($resortData['transportation_routes'])) {
            $decoded_routes = json_decode($resortData['transportation_routes'], true);
            if (is_array($decoded_routes)) {
                $transportation_routes = $decoded_routes;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Resorts</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: url('img/bg.png') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
        }
        .card-img-admin {
            height: 150px;
            object-fit: cover;
        }
        .image-thumbnail {
            width: 100px;
            height: 100px;
            object-fit: cover;
            margin-right: 10px;
            margin-bottom: 10px;
            position: relative;
        }
        .delete-image-btn {
            position: absolute;
            top: 0;
            right: 0;
            background: red;
            color: white;
            border: none;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            text-decoration: none;
        }
        .delete-image-btn:hover {
            background: darkred;
            color: white;
        }
        .transport-route-item {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 10px;
            position: relative;
        }
        .transport-route-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .btn-remove-route {
            position: absolute;
            top: 10px;
            right: 10px;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="text-white">Manage Resorts</h1>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#resortModal">
                <i class="fas fa-plus me-2"></i>Add Resort
            </button>
        </div>
        
        <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-4">
            <?php 
            if ($result && $result->num_rows > 0) {
                while ($resort = $result->fetch_assoc()): 
            ?>
                <div class="col">
                    <div class="card h-100">
                        <img src="<?= htmlspecialchars($resort['image']) ?>" class="card-img-top card-img-admin" alt="<?= htmlspecialchars($resort['name']) ?>">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($resort['name']) ?></h5>
                        </div>
                        <div class="card-footer bg-white">
                            <div class="d-flex justify-content-between">
                                <a href="add_resorts.php?edit=<?= $resort['id'] ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="?delete=<?= $resort['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                                <?php if (!empty($resort['details_link'])): ?>
                                    <a href="<?= htmlspecialchars($resort['details_link']) ?>" class="btn btn-sm btn-info" target="_blank">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php 
                endwhile;
            } else {
                echo '<div class="col-12"><div class="alert alert-info">No resorts found. Click "Add Resort" to create one.</div></div>';
            }
            ?>
        </div>
    </div>

    <!-- Resort Modal -->
    <div class="modal fade <?= isset($_GET['edit']) ? 'show d-block' : '' ?>" id="resortModal" tabindex="-1" aria-hidden="true" style="<?= isset($_GET['edit']) ? 'background-color: rgba(0,0,0,0.5);' : '' ?>">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?= isset($_GET['edit']) ? 'Edit Resort' : 'Add Resort' ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" onclick="window.location.href='add_resorts.php'"></button>
                </div>
                <form method="POST" action="add_resorts.php" enctype="multipart/form-data">
                    <input type="hidden" name="id" value="<?= htmlspecialchars($resortData['id']) ?>">
                    
                    <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Resort Name</label>
                                <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($resortData['name']) ?>" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Details Page Link</label>
                                <input type="text" class="form-control" name="details_link" value="<?= htmlspecialchars($resortData['details_link']) ?>">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Main Image</label>
                                <input type="file" class="form-control" name="image" accept="image/*">
                                <?php if ($resortData['image']): ?>
                                    <div class="mt-2">
                                        <img src="<?= htmlspecialchars($resortData['image']) ?>" class="img-thumbnail" width="100">
                                        <input type="hidden" name="existing_image" value="<?= htmlspecialchars($resortData['image']) ?>">
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Detail Images (Multiple)</label>
                                <input type="file" class="form-control" name="image_detail[]" multiple accept="image/*">
                                <?php if (!empty($resortData['image_detail'])): ?>
                                    <div class="d-flex flex-wrap mt-2">
                                        <?php 
                                        $images = explode(',', $resortData['image_detail']);
                                        foreach ($images as $img): 
                                            if (!empty($img)): ?>
                                                <div class="position-relative me-2 mb-2">
                                                    <img src="<?= htmlspecialchars($img) ?>" class="image-thumbnail" style="width: 80px; height: 80px;">
                                                    <a href="?delete_image=<?= urlencode($img) ?>&resort_id=<?= $resortData['id'] ?>" 
                                                       class="delete-image-btn" 
                                                       onclick="return confirm('Are you sure you want to delete this image?')">
                                                       ×
                                                    </a>
                                                </div>
                                            <?php endif;
                                        endforeach; ?>
                                    </div>
                                    <input type="hidden" name="existing_image_detail" value="<?= htmlspecialchars($resortData['image_detail']) ?>">
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-12 mb-3">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" name="description" rows="3"><?= htmlspecialchars($resortData['description']) ?></textarea>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Location/Address</label>
                                <input type="text" class="form-control" name="location" value="<?= htmlspecialchars($resortData['location']) ?>">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Website</label>
                                <input type="text" class="form-control" name="website" value="<?= htmlspecialchars($resortData['website']) ?>">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Google Maps Link</label>
                                <input type="text" class="form-control" name="maps_link" value="<?= htmlspecialchars($resortData['maps_link']) ?>">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Directions Link</label>
                                <input type="text" class="form-control" name="directions_link" value="<?= htmlspecialchars($resortData['directions_link']) ?>">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Embed Map URL</label>
                                <input type="text" class="form-control" name="map_embed" value="<?= htmlspecialchars($resortData['maps_embed']) ?>">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Entrance Fee</label>
                                <input type="text" class="form-control" name="entrance_fee" value="<?= htmlspecialchars($resortData['entrance_fee']) ?>">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Room Rates</label>
                                <input type="text" class="form-control" name="room_rates" value="<?= htmlspecialchars($resortData['room_rates']) ?>">
                            </div>
                            
                            <div class="col-12 mb-3">
                                <label class="form-label">Operating Hours</label>
                                <textarea class="form-control" name="hours" rows="3"><?= htmlspecialchars($resortData['hours']) ?></textarea>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Contact Information</label>
                                <input type="text" class="form-control" name="contact" value="<?= htmlspecialchars($resortData['contact']) ?>">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($resortData['email'] ?? '') ?>">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nearby Places (comma separated)</label>
                                <input type="text" class="form-control" name="nearby_places" value="<?= htmlspecialchars($nearby_places_str) ?>">
                            </div>
                            
                            <div class="col-12 mb-3">
                                <label class="form-label">Social Media Links (one per line)</label>
                                <textarea class="form-control" name="social_media" rows="3"><?= htmlspecialchars($resortData['social_media']) ?></textarea>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Pool Features (comma separated)</label>
                                <input type="text" class="form-control" name="pool_features" value="<?= htmlspecialchars($resortData['pool_features']) ?>">
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Dining Options (comma separated)</label>
                                <input type="text" class="form-control" name="dining_options" value="<?= htmlspecialchars($resortData['dining_options']) ?>">
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Amenities (comma separated)</label>
                                <input type="text" class="form-control" name="amenities" value="<?= htmlspecialchars($resortData['amenities']) ?>">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Parking Information</label>
                                <input type="text" class="form-control" name="parking" value="<?= htmlspecialchars($resortData['parking']) ?>">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Bike Parking</label>
                                <input type="text" class="form-control" name="bike_parking" value="<?= htmlspecialchars($resortData['bike_parking']) ?>">
                            </div>
                            
                            <div class="col-12 mb-3">
                                <label class="form-label">Transportation Options (one per line)</label>
                                <textarea class="form-control" name="transportation_options" rows="3"><?= htmlspecialchars($resortData['transportation_options']) ?></textarea>
                            </div>
                            
                            <!-- Transportation Routes Section -->
                            <div class="col-12 mb-3">
                                <div class="card">
                                    <div class="card-header bg-primary text-white">
                                        <h5 class="mb-0">Transportation Routes</h5>
                                    </div>
                                    <div class="card-body">
                                        <div id="transport-routes-container">
                                            <?php if (!empty($transportation_routes)): ?>
                                                <?php foreach ($transportation_routes as $index => $route): ?>
                                                    <div class="transport-route-item mb-3" id="route-<?php echo $index; ?>">
                                                        <div class="transport-route-header">
                                                            <h6 class="mb-0">Route #<?php echo $index + 1; ?></h6>
                                                            <button type="button" class="btn btn-danger btn-sm" onclick="removeRoute(this)">
                                                                <i class="fas fa-trash"></i> Remove
                                                            </button>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <div class="mb-3">
                                                                    <label class="form-label">Transport Type</label>
                                                                    <select class="form-control" name="route_type[]">
                                                                        <option value="jeepney" <?php echo ($route['type'] == 'jeepney') ? 'selected' : ''; ?>>Jeepney</option>
                                                                        <option value="bus" <?php echo ($route['type'] == 'bus') ? 'selected' : ''; ?>>Bus</option>
                                                                        <option value="taxi" <?php echo ($route['type'] == 'taxi') ? 'selected' : ''; ?>>Taxi</option>
                                                                        <option value="tricycle" <?php echo ($route['type'] == 'tricycle') ? 'selected' : ''; ?>>Tricycle</option>
                                                                        <option value="habal-habal" <?php echo ($route['type'] == 'habal-habal') ? 'selected' : ''; ?>>Habal-Habal</option>
                                                                        <option value="uv-express" <?php echo ($route['type'] == 'uv-express') ? 'selected' : ''; ?>>UV Express</option>
                                                                    </select>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label">Route Name/Number</label>
                                                                    <input type="text" class="form-control" name="route_name[]" value="<?php echo htmlspecialchars($route['name']); ?>" placeholder="e.g., Route 10, UV Express">
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <div class="mb-3">
                                                                    <label class="form-label">Route Link (Optional)</label>
                                                                    <input type="text" class="form-control" name="route_link[]" value="<?php echo htmlspecialchars($route['link']); ?>" placeholder="https://maps.google.com/...">
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label">Description (Optional)</label>
                                                                    <textarea class="form-control" name="route_description[]" rows="2" placeholder="e.g., Stops at City Hall, SM City Iloilo"><?php echo htmlspecialchars($route['description']); ?></textarea>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <div class="transport-route-item mb-3" id="route-0">
                                                    <div class="transport-route-header">
                                                        <h6 class="mb-0">Route #1</h6>
                                                        <button type="button" class="btn btn-danger btn-sm" onclick="removeRoute(this)">
                                                            <i class="fas fa-trash"></i> Remove
                                                        </button>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label class="form-label">Transport Type</label>
                                                                <select class="form-control" name="route_type[]">
                                                                    <option value="jeepney">Jeepney</option>
                                                                    <option value="bus">Bus</option>
                                                                    <option value="taxi">Taxi</option>
                                                                    <option value="tricycle">Tricycle</option>
                                                                    <option value="habal-habal">Habal-Habal</option>
                                                                    <option value="uv-express">UV Express</option>
                                                                </select>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Route Name/Number</label>
                                                                <input type="text" class="form-control" name="route_name[]" placeholder="e.g., Route 10, UV Express">
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label class="form-label">Route Link (Optional)</label>
                                                                <input type="text" class="form-control" name="route_link[]" placeholder="https://maps.google.com/...">
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Description (Optional)</label>
                                                                <textarea class="form-control" name="route_description[]" rows="2" placeholder="e.g., Stops at City Hall, SM City Iloilo"></textarea>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <button type="button" class="btn btn-success mt-2" onclick="addNewRoute()">
                                            <i class="fas fa-plus"></i> Add Another Route
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <!-- End Transportation Routes Section -->
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="window.location.href='add_resorts.php'">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let routeCount = <?php echo count($transportation_routes) > 0 ? count($transportation_routes) : 1; ?>;
        
        function addNewRoute() {
            routeCount++;
            const container = document.getElementById('transport-routes-container');
            const newRoute = document.createElement('div');
            newRoute.className = 'transport-route-item mb-3';
            newRoute.id = 'route-' + (routeCount - 1);
            
            newRoute.innerHTML = `
                <div class="transport-route-header">
                    <h6 class="mb-0">Route #${routeCount}</h6>
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeRoute(this)">
                        <i class="fas fa-trash"></i> Remove
                    </button>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Transport Type</label>
                            <select class="form-control" name="route_type[]">
                                <option value="jeepney">Jeepney</option>
                                <option value="bus">Bus</option>
                                <option value="taxi">Taxi</option>
                                <option value="tricycle">Tricycle</option>
                                <option value="habal-habal">Habal-Habal</option>
                                <option value="uv-express">UV Express</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Route Name/Number</label>
                            <input type="text" class="form-control" name="route_name[]" placeholder="e.g., Route 10, UV Express">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Route Link (Optional)</label>
                            <input type="text" class="form-control" name="route_link[]" placeholder="https://maps.google.com/...">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description (Optional)</label>
                            <textarea class="form-control" name="route_description[]" rows="2" placeholder="e.g., Stops at City Hall, SM City Iloilo"></textarea>
                        </div>
                    </div>
                </div>
            `;
            
            container.appendChild(newRoute);
        }
        
        function removeRoute(button) {
            if (confirm('Are you sure you want to remove this route?')) {
                const routeItem = button.closest('.transport-route-item');
                const container = document.getElementById('transport-routes-container');
                
                // Don't remove if it's the only route
                if (container.children.length > 1) {
                    routeItem.remove();
                    
                    // Renumber the routes
                    const routes = container.children;
                    for (let i = 0; i < routes.length; i++) {
                        const header = routes[i].querySelector('.transport-route-header h6');
                        if (header) {
                            header.textContent = `Route #${i + 1}`;
                        }
                    }
                } else {
                    alert('You must have at least one route. Add a new route first if you want to remove this one.');
                }
            }
        }
        
        // If edit parameter is present, show modal automatically
        <?php if (isset($_GET['edit'])): ?>
            document.addEventListener('DOMContentLoaded', function() {
                var resortModal = new bootstrap.Modal(document.getElementById('resortModal'));
                resortModal.show();
            });
        <?php endif; ?>
    </script>
</body>
</html>
<?php $conn->close(); ?>