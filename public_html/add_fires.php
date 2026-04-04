<?php
include 'conn.php';

if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    
    $stmt = $conn->prepare("SELECT image, image_detail FROM fires WHERE id=?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $fire = $result->fetch_assoc();
    
    $conn->query("DELETE FROM fires WHERE id=$delete_id");
    
    if ($fire) {
        if (!empty($fire['image']) && file_exists($fire['image'])) {
            unlink($fire['image']);
        }
        
        if (!empty($fire['image_detail'])) {
            $images = explode(',', $fire['image_detail']);
            foreach ($images as $img) {
                if (!empty($img) && file_exists($img)) {
                    unlink($img);
                }
            }
        }
    }
    
    header("Location: add_fires.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : null;
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $services = $_POST['services'] ?? '';
    $hotlines = $_POST['hotlines'] ?? '';
    $location = $_POST['location'] ?? '';
    $website = $_POST['website'] ?? '';
    $maps_link = $_POST['maps_link'] ?? '';
    $directions_link = $_POST['directions_link'] ?? '';
    $map_embed = $_POST['map_embed'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $email = $_POST['email'] ?? '';
    $hours = $_POST['hours'] ?? '';
    $social_media = $_POST['social_media'] ?? '';
    $nearby_places = $_POST['nearby_places'] ?? '';
    $transportation = $_POST['transportation'] ?? '';
    $prevention = $_POST['prevention'] ?? '';
    $programs = $_POST['programs'] ?? '';
    $established = $_POST['established'] ?? '';
    $district = $_POST['district'] ?? '';
    $equipment = $_POST['equipment'] ?? '';
    $personnel = $_POST['personnel'] ?? '';
    $chief = $_POST['chief'] ?? '';
    $coverage = $_POST['coverage'] ?? '';
    $details_link = $_POST['details_link'] ?? '';
    
    $nearby_places_array = array_map('trim', explode(',', $nearby_places));
    $nearby_places_json = json_encode(array_filter($nearby_places_array));
    
    // Handle transportation routes
    $transportation_routes = array();
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

    // Handle main image upload
    $target_file = $_POST['existing_image'] ?? '';
    if (isset($_FILES['image']['name']) && $_FILES['image']['name']) {
        $file = $_FILES['image'];
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $unique_name = uniqid() . '_' . basename($file['name']);
        $target_file = $target_dir . $unique_name;
        
        if (move_uploaded_file($file['tmp_name'], $target_file)) {
            // Delete old image if exists
            if (!empty($_POST['existing_image']) && file_exists($_POST['existing_image'])) {
                unlink($_POST['existing_image']);
            }
        }
    } elseif (isset($_POST['delete_image']) && $_POST['delete_image'] == '1') {
        // Delete current image
        if (!empty($_POST['existing_image']) && file_exists($_POST['existing_image'])) {
            unlink($_POST['existing_image']);
        }
        $target_file = '';
    }

    // Handle detail images upload
    $image_detail = $_POST['existing_image_detail'] ?? '';
    if (!empty($_FILES['image_detail']['name'][0])) {
        $detail_images = [];
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        foreach ($_FILES['image_detail']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['image_detail']['error'][$key] === UPLOAD_ERR_OK) {
                $file_name = uniqid() . '_' . basename($_FILES['image_detail']['name'][$key]);
                $target_path = $target_dir . $file_name;
                
                if (move_uploaded_file($tmp_name, $target_path)) {
                    $detail_images[] = $target_path;
                }
            }
        }
        
        if (!empty($image_detail)) {
            $image_detail .= ',' . implode(',', $detail_images);
        } else {
            $image_detail = implode(',', $detail_images);
        }
    }

    // Handle individual image deletion
    if (isset($_POST['delete_detail_images']) && is_array($_POST['delete_detail_images'])) {
        $existing_images = !empty($image_detail) ? explode(',', $image_detail) : [];
        $images_to_keep = array_diff($existing_images, $_POST['delete_detail_images']);
        
        // Delete the files from server
        foreach ($_POST['delete_detail_images'] as $image_to_delete) {
            if (file_exists($image_to_delete)) {
                unlink($image_to_delete);
            }
        }
        
        $image_detail = implode(',', $images_to_keep);
    }

    if ($id) {
        // UPDATE statement - 28 parameters including id at the end
        $query = "UPDATE fires SET 
                  name=?, image=?, image_detail=?, description=?, services=?, hotlines=?, 
                  location=?, website=?, maps_link=?, directions_link=?, maps_embed=?, 
                  phone=?, email=?, hours=?, social_media=?, nearby_places=?, transportation=?,
                  prevention=?, programs=?, established=?, district=?, equipment=?,
                  personnel=?, chief=?, coverage=?, details_link=?, transportation_routes=?
                  WHERE id=?";
        $stmt = $conn->prepare($query);
        // 27 's' for the fields + 1 'i' for id = 28 characters total
        $stmt->bind_param("sssssssssssssssssssssssssssi", 
            $name, $target_file, $image_detail, $description, $services, $hotlines, 
            $location, $website, $maps_link, $directions_link, $map_embed, 
            $phone, $email, $hours, $social_media, $nearby_places_json, $transportation,
            $prevention, $programs, $established, $district, $equipment,
            $personnel, $chief, $coverage, $details_link, $transportation_routes_json, $id);
    } else {
        // INSERT statement - 27 parameters
        $query = "INSERT INTO fires (
                  name, image, image_detail, description, services, hotlines, 
                  location, website, maps_link, directions_link, maps_embed, 
                  phone, email, hours, social_media, nearby_places, transportation,
                  prevention, programs, established, district, equipment,
                  personnel, chief, coverage, details_link, transportation_routes
                  ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        // 27 's' characters for 27 parameters
        $stmt->bind_param("sssssssssssssssssssssssssss", 
            $name, $target_file, $image_detail, $description, $services, $hotlines, 
            $location, $website, $maps_link, $directions_link, $map_embed, 
            $phone, $email, $hours, $social_media, $nearby_places_json, $transportation,
            $prevention, $programs, $established, $district, $equipment,
            $personnel, $chief, $coverage, $details_link, $transportation_routes_json);
    }
    
    $stmt->execute();
    header("Location: add_fires.php");
    exit();
}

$fires = $conn->query("SELECT * FROM fires")->fetch_all(MYSQLI_ASSOC);

$edit_data = null;
$transportation_routes = array();
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM fires WHERE id=?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_data = $result->fetch_assoc();
    
    if ($edit_data && !empty($edit_data['transportation_routes'])) {
        $decoded_routes = json_decode($edit_data['transportation_routes'], true);
        if (is_array($decoded_routes)) {
            $transportation_routes = $decoded_routes;
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
    <title>Fire Stations Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .card-img {
            height: 150px;
            object-fit: cover;
        }
        .form-section {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .img-thumbnail {
            max-width: 100px;
            max-height: 100px;
        }
        .image-preview-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }
        .image-preview-item {
            position: relative;
            display: inline-block;
        }
        .image-preview-item img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border: 2px solid #dee2e6;
            border-radius: 5px;
        }
        .delete-image-btn {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            font-size: 12px;
            cursor: pointer;
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
    <div class="container py-4">
        <h1 class="mb-4">Fire Stations Management</h1>
        
        <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#fireModal">
            <i class="fas fa-plus me-2"></i>Add New Station
        </button>
        
        <div class="row row-cols-1 row-cols-md-3 g-4">
            <?php foreach ($fires as $fire): ?>
                <div class="col">
                    <div class="card h-100">
                        <?php if ($fire['image']): ?>
                            <img src="<?= $fire['image'] ?>" class="card-img-top card-img" alt="<?= $fire['name'] ?>">
                        <?php else: ?>
                            <div class="card-img-top bg-secondary d-flex align-items-center justify-content-center" style="height: 150px;">
                                <i class="fas fa-fire-extinguisher fa-3x text-white"></i>
                            </div>
                        <?php endif; ?>
                        <div class="card-body">
                            <h5 class="card-title"><?= $fire['name'] ?></h5>
                            <p class="card-text text-muted"><?= $fire['location'] ?></p>
                        </div>
                        <div class="card-footer bg-white d-flex justify-content-between">
                            <a href="add_fires.php?edit=<?= $fire['id'] ?>" class="btn btn-sm btn-warning">
                                <i class="fas fa-edit"></i>Edit
                            </a>
                            <a href="?delete=<?= $fire['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">
                                <i class="fas fa-trash"></i> Delete
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="modal fade <?= isset($_GET['edit']) ? 'show d-block' : '' ?>" id="fireModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?= $edit_data ? 'Edit Fire Station' : 'Add New Fire Station' ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="<?= $edit_data ? $edit_data['id'] : '' ?>">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-section">
                                    <h5><i class="fas fa-info-circle me-2"></i> Basic Information</h5>
                                    <hr>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Station Name</label>
                                        <input type="text" class="form-control" name="name" value="<?= $edit_data ? $edit_data['name'] : '' ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Details Page Link</label>
                                        <input type="text" class="form-control" name="details_link" value="<?= $edit_data ? $edit_data['details_link'] : '' ?>" placeholder="e.g., fire1.php?id=1">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Description</label>
                                        <textarea class="form-control" name="description" rows="3"><?= $edit_data ? $edit_data['description'] : '' ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Location/Address</label>
                                        <input type="text" class="form-control" name="location" value="<?= $edit_data ? $edit_data['location'] : '' ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Main Image</label>
                                        <input type="file" class="form-control" name="image">
                                        <?php if ($edit_data && $edit_data['image']): ?>
                                            <div class="mt-2">
                                                <img src="<?= $edit_data['image'] ?>" class="img-thumbnail">
                                                <input type="hidden" name="existing_image" value="<?= $edit_data['image'] ?>">
                                                <div class="form-check mt-2">
                                                    <input class="form-check-input" type="checkbox" name="delete_image" value="1" id="deleteImage">
                                                    <label class="form-check-label" for="deleteImage">Delete current image</label>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="form-section">
                                    <h5><i class="fas fa-images me-2"></i> Detail Images</h5>
                                    <hr>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Upload Detail Images (Multiple)</label>
                                        <input type="file" class="form-control" name="image_detail[]" multiple>
                                        
                                        <?php if ($edit_data && $edit_data['image_detail']): ?>
                                            <div class="image-preview-container mt-3">
                                                <?php 
                                                    $images = explode(',', $edit_data['image_detail']);
                                                    foreach ($images as $img): 
                                                        if (!empty($img)):
                                                ?>
                                                    <div class="image-preview-item">
                                                        <img src="<?= $img ?>" class="img-thumbnail">
                                                        <button type="button" class="delete-image-btn" onclick="deleteDetailImage(this, '<?= $img ?>')">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                        <input type="hidden" name="delete_detail_images[]" value="">
                                                    </div>
                                                <?php 
                                                        endif;
                                                    endforeach; 
                                                ?>
                                            </div>
                                            <input type="hidden" name="existing_image_detail" value="<?= $edit_data['image_detail'] ?>">
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="form-section">
                                    <h5><i class="fas fa-phone me-2"></i> Contact Information</h5>
                                    <hr>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Emergency Hotlines</label>
                                        <textarea class="form-control" name="hotlines" rows="2" ><?= $edit_data ? $edit_data['hotlines'] : '' ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Phone Number</label>
                                        <input type="text" class="form-control" name="phone" value="<?= $edit_data ? $edit_data['phone'] : '' ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Email Address</label>
                                        <input type="email" class="form-control" name="email" value="<?= $edit_data ? $edit_data['email'] : '' ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Website URL</label>
                                        <input type="url" class="form-control" name="website" value="<?= $edit_data ? $edit_data['website'] : '' ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Social Media Links (one per line)</label>
                                        <textarea class="form-control" name="social_media" rows="3"><?= $edit_data ? $edit_data['social_media'] : '' ?></textarea>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-section">
                                    <h5><i class="fas fa-map-marked-alt me-2"></i> Location Information</h5>
                                    <hr>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Google Maps Link</label>
                                        <input type="url" class="form-control" name="maps_link" value="<?= $edit_data ? $edit_data['maps_link'] : '' ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Directions Link</label>
                                        <input type="url" class="form-control" name="directions_link" value="<?= $edit_data ? $edit_data['directions_link'] : '' ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Embed Map URL</label>
                                        <input type="url" class="form-control" name="map_embed" value="<?= $edit_data ? $edit_data['maps_embed'] : '' ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Transportation Information</label>
                                        <textarea class="form-control" name="transportation" rows="3"><?= $edit_data ? $edit_data['transportation'] : '' ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Nearby Places (comma separated)</label>
                                        <input type="text" class="form-control" name="nearby_places" value="<?= $edit_data ? $edit_data['nearby_places'] : '' ?>">
                                    </div>
                                </div>
                                
                                <div class="form-section">
                                    <h5><i class="fas fa-fire me-2"></i> Services Information</h5>
                                    <hr>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Services Offered</label>
                                        <textarea class="form-control" name="services" rows="3" ><?= $edit_data ? $edit_data['services'] : '' ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Fire Prevention Programs</label>
                                        <textarea class="form-control" name="prevention" rows="3"><?= $edit_data ? $edit_data['prevention'] : '' ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Community Programs</label>
                                        <textarea class="form-control" name="programs" rows="3"><?= $edit_data ? $edit_data['programs'] : '' ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Operating Hours</label>
                                        <textarea class="form-control" name="hours" rows="3"><?= $edit_data ? $edit_data['hours'] : '' ?></textarea>
                                    </div>
                                </div>
                                
                                <div class="form-section">
                                    <h5><i class="fas fa-info-circle me-2"></i> Additional Information</h5>
                                    <hr>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Established Year</label>
                                            <input type="text" class="form-control" name="established" value="<?= $edit_data ? $edit_data['established'] : '' ?>">
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">District</label>
                                            <input type="text" class="form-control" name="district" value="<?= $edit_data ? $edit_data['district'] : '' ?>">
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Equipment</label>
                                            <input type="text" class="form-control" name="equipment" value="<?= $edit_data ? $edit_data['equipment'] : '' ?>">
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Personnel</label>
                                            <input type="text" class="form-control" name="personnel" value="<?= $edit_data ? $edit_data['personnel'] : '' ?>">
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Fire Chief</label>
                                            <input type="text" class="form-control" name="chief" value="<?= $edit_data ? $edit_data['chief'] : '' ?>">
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Coverage Area</label>
                                            <input type="text" class="form-control" name="coverage" value="<?= $edit_data ? $edit_data['coverage'] : '' ?>">
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Transportation Routes Section -->
                                <div class="form-section">
                                    <h5><i class="fas fa-route me-2"></i> Transportation Routes</h5>
                                    <hr>
                                    
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
                                                                <option value="fire_truck" <?php echo (isset($transportation_routes[$i]['type']) && $transportation_routes[$i]['type'] == 'fire_truck') ? 'selected' : ''; ?>>Fire Truck Route</option>
                                                                <option value="emergency" <?php echo (isset($transportation_routes[$i]['type']) && $transportation_routes[$i]['type'] == 'emergency') ? 'selected' : ''; ?>>Emergency Route</option>
                                                                <option value="motorcycle" <?php echo (isset($transportation_routes[$i]['type']) && $transportation_routes[$i]['type'] == 'motorcycle') ? 'selected' : ''; ?>>Motorcycle</option>
                                                                <option value="bicycle" <?php echo (isset($transportation_routes[$i]['type']) && $transportation_routes[$i]['type'] == 'bicycle') ? 'selected' : ''; ?>>Bicycle</option>
                                                                <option value="walking" <?php echo (isset($transportation_routes[$i]['type']) && $transportation_routes[$i]['type'] == 'walking') ? 'selected' : ''; ?>>Walking Route</option>
                                                            </select>
                                                        </td>
                                                        <td>
                                                            <input type="text" class="form-control" name="route_name[]" value="<?php echo isset($transportation_routes[$i]['name']) ? htmlspecialchars($transportation_routes[$i]['name']) : ''; ?>" placeholder="e.g., Emergency Route 1, Fire Access Road">
                                                        </td>
                                                        <td>
                                                            <input type="text" class="form-control" name="route_link[]" value="<?php echo isset($transportation_routes[$i]['link']) ? htmlspecialchars($transportation_routes[$i]['link']) : ''; ?>" placeholder="https://maps.google.com/...">
                                                        </td>
                                                        <td>
                                                            <textarea class="form-control" name="route_description[]" rows="2" placeholder="e.g., Emergency vehicle access only, clear path for fire trucks"><?php echo isset($transportation_routes[$i]['description']) ? htmlspecialchars($transportation_routes[$i]['description']) : ''; ?></textarea>
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
                                        <i class="fas fa-plus"></i> Add Row
                                    </button>
                                </div>
                                <!-- End Transportation Routes Section -->
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 mt-3">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save me-2"></i>Save
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-show modal when editing
        <?php if (isset($_GET['edit'])): ?>
            window.onload = function() {
                var modal = new bootstrap.Modal(document.getElementById('fireModal'));
                modal.show();
            };
        <?php endif; ?>

        // Function to handle detail image deletion
        function deleteDetailImage(button, imagePath) {
            // Hide the image preview
            button.parentElement.style.display = 'none';
            
            // Find the hidden input and set the value to mark for deletion
            const hiddenInput = button.nextElementSibling;
            hiddenInput.value = imagePath;
            hiddenInput.name = 'delete_detail_images[]';
        }
        
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
                        <option value="fire_truck">Fire Truck Route</option>
                        <option value="emergency">Emergency Route</option>
                        <option value="motorcycle">Motorcycle</option>
                        <option value="bicycle">Bicycle</option>
                        <option value="walking">Walking Route</option>
                    </select>
                </td>
                <td>
                    <input type="text" class="form-control" name="route_name[]" placeholder="e.g., Emergency Route 1, Fire Access Road">
                </td>
                <td>
                    <input type="text" class="form-control" name="route_link[]" placeholder="https://maps.google.com/...">
                </td>
                <td>
                    <textarea class="form-control" name="route_description[]" rows="2" placeholder="e.g., Emergency vehicle access only, clear path for fire trucks"></textarea>
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