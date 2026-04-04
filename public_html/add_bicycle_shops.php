<?php
session_start();
include 'conn.php';

if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    
    $stmt = $conn->prepare("SELECT image, image_detail FROM bicycle_shops WHERE id=?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $shop = $result->fetch_assoc();
    
    $stmt = $conn->prepare("DELETE FROM bicycle_shops WHERE id=?");
    $stmt->bind_param("i", $delete_id);
    
    if ($stmt->execute()) {
        if ($shop) {
            if (!empty($shop['image']) && file_exists($shop['image'])) {
                unlink($shop['image']);
            }
            
            if (!empty($shop['image_detail'])) {
                $images = explode(',', $shop['image_detail']);
                foreach ($images as $img) {
                    if (!empty($img) && file_exists($img)) {
                        unlink($img);
                    }
                }
            }
        }
        
        $_SESSION['message'] = "Bicycle shop deleted successfully";
    } else {
        $_SESSION['error'] = "Error deleting bicycle shop";
    }
    
    header("Location: add_bicycle_shops.php");
    exit();
}

if (isset($_GET['delete_image']) && isset($_GET['shop_id'])) {
    $image_path = $_GET['delete_image'];
    $shop_id = $_GET['shop_id'];
    $is_card_image = isset($_GET['is_card_image']) ? true : false;
    
    if (file_exists($image_path)) {
        unlink($image_path);
    }
    
    if ($is_card_image) {
        $stmt = $conn->prepare("UPDATE bicycle_shops SET image='' WHERE id=?");
        $stmt->bind_param("i", $shop_id);
        $stmt->execute();
    } else {
        $stmt = $conn->prepare("SELECT image_detail FROM bicycle_shops WHERE id=?");
        $stmt->bind_param("i", $shop_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $shop = $result->fetch_assoc();
        
        if ($shop) {
            $images = explode(',', $shop['image_detail']);
            $updated_images = array();
            foreach ($images as $img) {
                if (trim($img) != trim($image_path)) {
                    $updated_images[] = $img;
                }
            }
            $updated_images_str = implode(',', $updated_images);
            
            $stmt = $conn->prepare("UPDATE bicycle_shops SET image_detail=? WHERE id=?");
            $stmt->bind_param("si", $updated_images_str, $shop_id);
            $stmt->execute();
        }
    }
    
    $_SESSION['message'] = "Image deleted successfully";
    header("Location: add_bicycle_shops.php?edit=" . $shop_id);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = isset($_POST['id']) ? $_POST['id'] : null;
    $name = $_POST['name'];
    $description = $_POST['description'];
    $hours = $_POST['hours'] ?? '';
    $contact = $_POST['contact'] ?? '';
    $location = $_POST['location'];
    $website = $_POST['website'];
    $maps_link = $_POST['maps_link'];
    $directions_link = $_POST['directions_link'];
    $mapEmbedURL = $_POST['map_embed'] ?? '';
    $social_media = $_POST['social_media'];
    $details_link = $_POST['details_link'];
    $services = $_POST['services'] ?? '';
    $bicycle_types = $_POST['bicycle_types'] ?? '';
    $email = $_POST['email'] ?? '';

    $nearby_places = $_POST['nearby_places'] ?? '';
    $nearby_places_array = array_map('trim', explode(',', $nearby_places));
    $nearby_places_json = json_encode(array_filter($nearby_places_array));

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
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $target_file = $_POST['existing_image'] ?? '';

    if (isset($_POST['delete_image']) && $_POST['delete_image'] == '1') {
        if (file_exists($target_file)) {
            unlink($target_file);
        }
        $target_file = '';
    }

    if (!empty($_FILES['image']['name'])) {
        $target_file = $target_dir . time() . '_' . basename($_FILES["image"]["name"]);
        move_uploaded_file($_FILES["image"]["tmp_name"], $target_file);
        
        if (!empty($_POST['existing_image']) && file_exists($_POST['existing_image'])) {
            unlink($_POST['existing_image']);
        }
    }

    $imagePaths = [];
    $existingImages = [];

    if (!empty($_POST['existing_image_detail'])) {
        $existingImages = explode(',', $_POST['existing_image_detail']);
        $existingImages = array_filter($existingImages);
    }
    
    if (!empty($_FILES['image_detail']['name'][0])) {
        $totalNew = count($_FILES['image_detail']['name']);
        
        for ($i = 0; $i < $totalNew; $i++) {
            if ($_FILES['image_detail']['error'][$i] !== UPLOAD_ERR_OK) {
                continue;
            }
            
            $fileName = basename($_FILES['image_detail']['name'][$i]);
            $tmpName = $_FILES['image_detail']['tmp_name'][$i];
            $uniqueName = time() . '_' . $i . '_' . $fileName;
            $targetPath = $target_dir . $uniqueName;

            if (move_uploaded_file($tmpName, $targetPath)) {
                $imagePaths[] = $targetPath;
            }
        }
    }
    
    $allImages = array_merge($existingImages, $imagePaths);
    $target_file_detail = implode(',', $allImages);
       
    if ($id) {
        $query = "UPDATE bicycle_shops SET 
                  name=?, 
                  image=?, 
                  image_detail=?, 
                  description=?, 
                  location=?, 
                  maps_embed=?, 
                  maps_link=?, 
                  website=?, 
                  directions_link=?, 
                  hours=?, 
                  contact=?, 
                  nearby_places=?, 
                  social_media=?, 
                  details_link=?, 
                  services=?, 
                  bicycle_types=?, 
                  email=?, 
                  transportation_routes=? 
                  WHERE id=?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssssssssssssssssi", 
            $name, 
            $target_file, 
            $target_file_detail, 
            $description, 
            $location, 
            $mapEmbedURL, 
            $maps_link, 
            $website, 
            $directions_link, 
            $hours, 
            $contact, 
            $nearby_places_json, 
            $social_media, 
            $details_link, 
            $services, 
            $bicycle_types, 
            $email, 
            $transportation_routes_json, 
            $id
        );
    } else {
        $query = "INSERT INTO bicycle_shops 
                  (name, image, image_detail, description, location, maps_embed, maps_link, website, directions_link, hours, contact, nearby_places, social_media, details_link, services, bicycle_types, email, transportation_routes) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssssssssssssssss", 
            $name, 
            $target_file, 
            $target_file_detail, 
            $description, 
            $location, 
            $mapEmbedURL, 
            $maps_link, 
            $website, 
            $directions_link, 
            $hours, 
            $contact, 
            $nearby_places_json, 
            $social_media, 
            $details_link, 
            $services, 
            $bicycle_types, 
            $email, 
            $transportation_routes_json
        );
    }
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Bicycle shop " . ($id ? "updated" : "added") . " successfully";
    } else {
        $_SESSION['error'] = "Error saving bicycle shop: " . $conn->error;
    }
    
    header("Location: add_bicycle_shops.php");
    exit();
}

$query = "SELECT id, name, image, description, location, maps_embed, directions_link, maps_link, website, hours, contact, nearby_places, social_media, details_link, services, bicycle_types, email, transportation_routes FROM bicycle_shops";
$stmt = $conn->prepare($query);
$stmt->execute();
$shops = $stmt->get_result();

$nearby_places_str = '';
$transportation_routes = array();

if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM bicycle_shops WHERE id=?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $shopData = $result->fetch_assoc();
        
        if (!empty($shopData['nearby_places'])) {
            $decoded_places = json_decode($shopData['nearby_places'], true);
            if (is_array($decoded_places)) {
                $nearby_places_str = implode(', ', $decoded_places);
            }
        }
        
        if (!empty($shopData['transportation_routes'])) {
            $decoded_routes = json_decode($shopData['transportation_routes'], true);
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
    <title>Bicycle Shops Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
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
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border-radius: 15px;
            overflow: hidden;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        .card-img-top {
            height: 150px;
            object-fit: cover;
            width: 100%;
        }
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1100;
        }
        .image-preview-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }
        .image-preview {
            position: relative;
            width: 100px;
            height: 100px;
        }
        .image-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 5px;
        }
        .delete-image-btn {
            position: absolute;
            top: -5px;
            right: -5px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #dc3545;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 12px;
        }
        .form-section {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .transport-route-item {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 10px;
        }
        .transport-route-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="toast-container">
        <?php if (isset($_SESSION['message'])): ?>
            <div class="toast show" role="alert">
                <div class="toast-header bg-success text-white">
                    <strong class="me-auto">Success</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
                </div>
                <div class="toast-body">
                    <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="toast show" role="alert">
                <div class="toast-header bg-danger text-white">
                    <strong class="me-auto">Error</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
                </div>
                <div class="toast-body">
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="container mt-4">
        <button type="button" class="btn btn-light text-dark mb-3" onclick="window.location.href='dashboard.php'">
            <i class="bi bi-arrow-left"></i> Back to Dashboard
        </button>
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="text-light"><i class="bi bi-bicycle"></i> Bicycle Shops Management</h1>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#shopModal">
                <i class="bi bi-plus-lg"></i> Add Bicycle Shop
            </button>
        </div>
        
        <?php if ($shops->num_rows == 0): ?>
            <div class="alert alert-info">
                No bicycle shops found. Click "Add Bicycle Shop" to create one.
            </div>
        <?php else: ?>
            <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-4">
                <?php while ($shop = $shops->fetch_assoc()): ?>
                    <div class="col">
                        <div class="card h-100">
                            <img src="<?php echo $shop['image']; ?>" class="card-img-top" alt="<?php echo $shop['name']; ?>">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?php echo $shop['name']; ?></h5>
                                <p class="card-text flex-grow-1"><?php echo substr($shop['description'], 0, 100) . '...'; ?></p>
                                <div class="d-flex justify-content-between">
                                    <a href="add_bicycle_shops.php?edit=<?php echo $shop['id']; ?>" class="btn btn-primary btn-sm">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    <a href="?delete=<?php echo $shop['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this bicycle shop?')">
                                        <i class="bi bi-trash"></i> Delete
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="modal fade <?php echo isset($_GET['edit']) ? 'show d-block' : ''; ?>" id="shopModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo isset($_GET['edit']) ? 'Edit Bicycle Shop' : 'Add Bicycle Shop'; ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?php 
                        $shopData = [
                            'id' => '',
                            'name' => '',
                            'description' => '',
                            'location' => '',
                            'website' => '',
                            'image' => '',
                            'image_detail' => '',
                            'maps_embed' => '',
                            'maps_link' => '',
                            'directions_link' => '',
                            'hours' => '',
                            'contact' => '',
                            'social_media' => '',
                            'details_link' => '',
                            'services' => '',
                            'bicycle_types' => '',
                            'email' => ''
                        ];
                        
                        if (isset($_GET['edit'])) {
                            $edit_id = $_GET['edit'];
                            $stmt = $conn->prepare("SELECT * FROM bicycle_shops WHERE id=?");
                            $stmt->bind_param("i", $edit_id);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            if ($result->num_rows > 0) {
                                $shopData = $result->fetch_assoc();
                            }
                        }
                    ?>
                    
                    <form method="POST" action="add_bicycle_shops.php" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="<?php echo $shopData['id']; ?>">
                        
                        <div class="form-section">
                            <h5><i class="bi bi-info-circle"></i> Basic Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Name *</label>
                                        <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($shopData['name']); ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Details Link</label>
                                        <input type="text" class="form-control" name="details_link" value="<?php echo htmlspecialchars($shopData['details_link']); ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Description *</label>
                                        <textarea class="form-control" name="description" rows="4"><?php echo htmlspecialchars($shopData['description']); ?></textarea>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Operating Hours</label>
                                        <textarea class="form-control" name="hours" rows="5" placeholder="e.g., Monday: 9AM - 5PM"><?php echo htmlspecialchars($shopData['hours']); ?></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Contact Information</label>
                                        <input type="text" class="form-control" name="contact" value="<?php echo htmlspecialchars($shopData['contact']); ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($shopData['email']); ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Services Offered</label>
                                        <input type="text" class="form-control" name="services" value="<?php echo htmlspecialchars($shopData['services']); ?>">
                                        <small class="text-muted">Separate services with commas</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Bicycle Types Available</label>
                                        <input type="text" class="form-control" name="bicycle_types" value="<?php echo htmlspecialchars($shopData['bicycle_types']); ?>">
                                        <small class="text-muted">Separate types with commas</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Images Section -->
                        <div class="form-section">
                            <h5><i class="bi bi-images"></i> Images</h5>
                            <div class="mb-3">
                                <label class="form-label">Card Image</label>
                                <input type="file" class="form-control" name="image" accept="image/*">
                                
                                <?php if (!empty($shopData['image'])): ?>
                                    <div class="mt-2">
                                        <img src="<?php echo $shopData['image']; ?>" class="img-thumbnail" style="max-height: 100px;">
                                        <div class="form-check mt-1">
                                            <input class="form-check-input" type="checkbox" name="delete_image" value="1" id="deleteImageCheck">
                                            <label class="form-check-label" for="deleteImageCheck">Delete current image</label>
                                        </div>
                                    </div>
                                    <input type="hidden" name="existing_image" value="<?php echo $shopData['image']; ?>">
                                <?php endif; ?>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Detail Images (You can select multiple)</label>
                                <input type="file" class="form-control" name="image_detail[]" multiple accept="image/*">
                                
                                <?php if (!empty($shopData['image_detail'])): 
                                    $images = explode(',', $shopData['image_detail']); ?>
                                    <div class="d-flex flex-wrap mt-2">
                                        <?php foreach ($images as $img): 
                                            if (!empty($img)): ?>
                                                <div class="position-relative me-2 mb-2">
                                                    <img src="<?php echo $img; ?>" class="img-thumbnail" style="max-height: 100px;">
                                                    <a href="?delete_image=<?php echo urlencode($img); ?>&shop_id=<?php echo $shopData['id']; ?>" 
                                                       class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger text-decoration-none"
                                                       onclick="return confirm('Delete this image?')">
                                                        ×
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                    <input type="hidden" name="existing_image_detail" value="<?php echo $shopData['image_detail']; ?>">
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Location Information Section -->
                        <div class="form-section">
                            <h5><i class="bi bi-geo-alt"></i> Location Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Address *</label>
                                        <input type="text" class="form-control" name="location" value="<?php echo htmlspecialchars($shopData['location']); ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Website URL</label>
                                        <input type="url" class="form-control" name="website" value="<?php echo htmlspecialchars($shopData['website']); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Google Maps Directions Link</label>
                                        <input type="url" class="form-control" name="directions_link" value="<?php echo htmlspecialchars($shopData['directions_link']); ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Google Map Link</label>
                                        <input type="url" class="form-control" name="maps_link" value="<?php echo htmlspecialchars($shopData['maps_link']); ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Embed Google Map URL</label>
                                <input type="url" class="form-control" name="map_embed" value="<?php echo htmlspecialchars($shopData['maps_embed']); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Nearby Places</label>
                                <input name="nearby_places" class="form-control" value="<?php echo htmlspecialchars($nearby_places_str); ?>">
                                <small class="text-muted">Example: Park, Shopping Mall, Bike Trail</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Social Media Links</label>
                                <textarea class="form-control" name="social_media" rows="3" placeholder="Enter one URL per line"><?php echo htmlspecialchars($shopData['social_media']); ?></textarea>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h5><i class="bi bi-bus-front"></i> Transportation Routes</h5>
                            <div id="transport-routes-container">
                                <?php if (!empty($transportation_routes)): ?>
                                    <?php foreach ($transportation_routes as $index => $route): ?>
                                        <div class="transport-route-item">
                                            <div class="transport-route-header">
                                                <h6 class="mb-0">Route #<?php echo $index + 1; ?></h6>
                                                <button type="button" class="btn btn-danger btn-sm" onclick="removeRoute(this)">
                                                    <i class="bi bi-trash"></i> Remove
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
                                                        </select>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Route Name/Number</label>
                                                        <input type="text" class="form-control" name="route_name[]" value="<?php echo htmlspecialchars($route['name']); ?>">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Route Link (Optional)</label>
                                                        <input type="text" class="form-control" name="route_link[]" value="<?php echo htmlspecialchars($route['link']); ?>">
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Description (Optional)</label>
                                                        <textarea class="form-control" name="route_description[]" rows="2"><?php echo htmlspecialchars($route['description']); ?></textarea>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="transport-route-item">
                                        <div class="transport-route-header">
                                            <h6 class="mb-0">Route #1</h6>
                                            <button type="button" class="btn btn-danger btn-sm" onclick="removeRoute(this)">
                                                <i class="bi bi-trash"></i> Remove
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
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Route Name/Number</label>
                                                    <input type="text" class="form-control" name="route_name[]">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Route Link (Optional)</label>
                                                    <input type="text" class="form-control" name="route_link[]">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Description (Optional)</label>
                                                    <textarea class="form-control" name="route_description[]" rows="2"></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <button type="button" class="btn btn-secondary btn-sm mt-2" onclick="addRoute()">
                                <i class="bi bi-plus"></i> Add Another Route
                            </button>
                        </div>
                        
                        <!-- Form Actions -->
                        <div class="d-flex justify-content-end gap-2 mt-3">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <?php echo isset($_GET['edit']) ? 'Update Bicycle Shop' : 'Save Bicycle Shop'; ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Function to add new route
        function addRoute() {
            const container = document.getElementById('transport-routes-container');
            const routeCount = container.children.length + 1;
            
            const newRoute = document.createElement('div');
            newRoute.className = 'transport-route-item';
            newRoute.innerHTML = `
                <div class="transport-route-header">
                    <h6 class="mb-0">Route #${routeCount}</h6>
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeRoute(this)">
                        <i class="bi bi-trash"></i> Remove
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
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Route Name/Number</label>
                            <input type="text" class="form-control" name="route_name[]">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Route Link (Optional)</label>
                            <input type="text" class="form-control" name="route_link[]">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description (Optional)</label>
                            <textarea class="form-control" name="route_description[]" rows="2"></textarea>
                        </div>
                    </div>
                </div>
            `;
            
            container.appendChild(newRoute);
        }
        
        function removeRoute(button) {
            if (document.querySelectorAll('.transport-route-item').length > 1) {
                button.closest('.transport-route-item').remove();
                
                const routes = document.querySelectorAll('.transport-route-item');
                routes.forEach((route, index) => {
                    const header = route.querySelector('.transport-route-header h6');
                    if (header) {
                        header.textContent = `Route #${index + 1}`;
                    }
                });
            } else {
                alert('You need at least one route. Add more routes before removing this one.');
            }
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            const toasts = document.querySelectorAll('.toast');
            toasts.forEach(function(toast) {
                setTimeout(function() {
                    const bsToast = new bootstrap.Toast(toast);
                    bsToast.hide();
                }, 5000);
            });
            
            if (window.location.search.includes('edit')) {
                const modal = new bootstrap.Modal(document.getElementById('shopModal'));
                modal.show();
                
                history.replaceState(null, null, window.location.pathname);
            }
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>