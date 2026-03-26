<?php
session_start();
require 'conn.php';

define('UPLOAD_DIR', 'uploads/');
define('ALLOWED_TYPES', ['jpg', 'jpeg', 'png', 'gif']);
define('MAX_FILE_SIZE', 2 * 1024 * 1024); 
define('MAX_DETAIL_IMAGES', 5);

if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    
    $stmt = $conn->prepare("SELECT image, image_detail FROM barangays WHERE id=?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $barangay = $result->fetch_assoc();
    
    $stmt = $conn->prepare("DELETE FROM barangays WHERE id=?");
    $stmt->bind_param("i", $delete_id);
    
    if ($stmt->execute()) {
        if ($barangay) {
            if (!empty($barangay['image'])) {
                $image_path = realpath(UPLOAD_DIR . basename($barangay['image']));
                if (file_exists($image_path)) {
                    unlink($image_path);
                }
            }
            
            if (!empty($barangay['image_detail'])) {
                $images = explode(',', $barangay['image_detail']);
                foreach ($images as $img) {
                    if (!empty($img)) {
                        $img_path = realpath(UPLOAD_DIR . basename($img));
                        if (file_exists($img_path)) {
                            unlink($img_path);
                        }
                    }
                }
            }
        }
        
        $_SESSION['message'] = "Barangay deleted successfully";
    } else {
        $_SESSION['error'] = "Error deleting barangay";
    }
    
    header("Location: add_barangays.php");
    exit();
}

if (isset($_GET['delete_image']) && isset($_GET['barangay_id'])) {
    $image_path = urldecode($_GET['delete_image']);
    $barangay_id = intval($_GET['barangay_id']);
    $is_card_image = isset($_GET['is_card_image']) ? true : false;
    
    $real_image_path = realpath(UPLOAD_DIR . basename($image_path));
    $real_upload_dir = realpath(UPLOAD_DIR);
    
    if (strpos($real_image_path, $real_upload_dir) === 0 && file_exists($real_image_path)) {
        unlink($real_image_path);
    }
    
    if ($is_card_image) {
        $stmt = $conn->prepare("UPDATE barangays SET image='' WHERE id=?");
        $stmt->bind_param("i", $barangay_id);
        $stmt->execute();
    } else {
        $stmt = $conn->prepare("SELECT image_detail FROM barangays WHERE id=?");
        $stmt->bind_param("i", $barangay_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $barangay = $result->fetch_assoc();
        
        if ($barangay) {
            $images = array_filter(explode(',', $barangay['image_detail']));
            $updated_images = array_diff($images, [$image_path]);
            $updated_images_str = implode(',', $updated_images);
            
            $stmt = $conn->prepare("UPDATE barangays SET image_detail=? WHERE id=?");
            $stmt->bind_param("si", $updated_images_str, $barangay_id);
            $stmt->execute();
        }
    }
    
    $_SESSION['message'] = "Image deleted successfully";
    header("Location: add_barangays.php?edit=" . $barangay_id);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : null;
    $name = trim($_POST['name']);
    $location = trim($_POST['location']);
    $website = trim($_POST['website'] ?? '');
    $facebook = trim($_POST['facebook'] ?? '');
    $maps_link = trim($_POST['maps_link'] ?? '');
    $directions_link = trim($_POST['directions_link'] ?? '');
    $mapEmbedURL = trim($_POST['maps_embed'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $history = trim($_POST['history'] ?? '');
    $population = trim($_POST['population'] ?? '');
    $area = trim($_POST['area'] ?? '');
    $zip_code = trim($_POST['zip_code'] ?? '');
    $type = trim($_POST['type'] ?? '');
    $established = trim($_POST['established'] ?? '');
    $officials = trim($_POST['officials'] ?? '');
    $facilities = trim($_POST['facilities'] ?? '');
    $contact = trim($_POST['contact'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $hours = trim($_POST['hours'] ?? '');
    $details_link = trim($_POST['details_link'] ?? '');

    $transportation_routes = array();
    
    if (isset($_POST['route_type']) && is_array($_POST['route_type'])) {
        $route_count = count($_POST['route_type']);
        
        for ($i = 0; $i < $route_count; $i++) {
            $route_type = trim($_POST['route_type'][$i] ?? '');
            $route_name = trim($_POST['route_name'][$i] ?? '');
            
            if (!empty($route_type) && !empty($route_name)) {
                $transportation_routes[] = array(
                    'type' => $route_type,
                    'name' => $route_name,
                    'link' => trim($_POST['route_link'][$i] ?? ''),
                    'description' => trim($_POST['route_description'][$i] ?? '')
                );
            }
        }
    }
    
    $transportation_routes_json = !empty($transportation_routes) ? json_encode($transportation_routes) : '';
    
    if (empty($name) || empty($location)) {
        $_SESSION['error'] = "Please fill in all required fields";
        header("Location: add_barangays.php" . ($id ? "?edit=$id" : ""));
        exit();
    }
    
    $target_file = $_POST['existing_image'] ?? '';
    
    if (isset($_FILES['image']['name']) && $_FILES['image']['name']) {
        $file = $_FILES['image'];
        $validation = validateImage($file);
        
        if ($validation === true) {
            $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $new_filename = uniqid('img_', true) . '.' . $file_ext;
            $target_file = UPLOAD_DIR . $new_filename;
            
            if (move_uploaded_file($file['tmp_name'], $target_file)) {
                if (!empty($_POST['existing_image']) && file_exists($_POST['existing_image'])) {
                    unlink($_POST['existing_image']);
                }
            } else {
                $_SESSION['error'] = "Error uploading the main image";
                header("Location: add_barangays.php" . ($id ? "?edit=$id" : ""));
                exit();
            }
        } else {
            $_SESSION['error'] = $validation;
            header("Location: add_barangays.php" . ($id ? "?edit=$id" : ""));
            exit();
        }
    } elseif (isset($_POST['delete_image']) && $_POST['delete_image'] == '1') {
        if (!empty($_POST['existing_image']) && file_exists($_POST['existing_image'])) {
            unlink($_POST['existing_image']);
        }
        $target_file = '';
    }
    
    $imagePaths = [];
    $existingImages = [];
    
    if (!empty($_POST['existing_image_detail'])) {
        $existingImages = array_filter(explode(',', $_POST['existing_image_detail']));
    }
    
    if (!empty($_FILES['image_detail']['name'][0])) {
        $totalNew = count($_FILES['image_detail']['name']);
        $totalCombined = count($existingImages) + $totalNew;
        
        if ($totalCombined > MAX_DETAIL_IMAGES) {
            $_SESSION['error'] = "You can upload a maximum of " . MAX_DETAIL_IMAGES . " detail images total";
            header("Location: add_barangays.php" . ($id ? "?edit=$id" : ""));
            exit();
        }
        
        for ($i = 0; $i < $totalNew; $i++) {
            if ($_FILES['image_detail']['error'][$i] !== UPLOAD_ERR_OK) {
                continue;
            }
            
            $file = [
                'name' => $_FILES['image_detail']['name'][$i],
                'type' => $_FILES['image_detail']['type'][$i],
                'tmp_name' => $_FILES['image_detail']['tmp_name'][$i],
                'error' => $_FILES['image_detail']['error'][$i],
                'size' => $_FILES['image_detail']['size'][$i]
            ];
            
            $validation = validateImage($file);
            
            if ($validation === true) {
                $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $new_filename = uniqid('img_', true) . '.' . $file_ext;
                $targetPath = UPLOAD_DIR . $new_filename;
                
                if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                    $imagePaths[] = $targetPath;
                }
            }
        }
    }
    
    $allImages = array_merge($existingImages, $imagePaths);
    $target_file_detail = implode(',', $allImages);
    
    if ($id) {
        $query = "UPDATE barangays SET 
            name=?, image=?, image_detail=?, location=?, description=?, history=?, 
            population=?, area=?, zip_code=?, type=?, established=?, officials=?, 
            facilities=?, contact=?, email=?, hours=?, maps_embed=?, maps_link=?, 
            directions_link=?, website=?, facebook=?, details_link=?, transportation_routes=?
            WHERE id=?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param(
            "sssssssssssssssssssssssi", 
            $name, $target_file, $target_file_detail, $location, $description, $history,
            $population, $area, $zip_code, $type, $established, $officials,
            $facilities, $contact, $email, $hours, $mapEmbedURL, $maps_link,
            $directions_link, $website, $facebook, $details_link, $transportation_routes_json, $id
        );
    } else {
        $query = "INSERT INTO barangays (
            name, image, image_detail, location, description, history, 
            population, area, zip_code, type, established, officials, 
            facilities, contact, email, hours, maps_embed, maps_link, 
            directions_link, website, facebook, details_link, transportation_routes
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param(
            "sssssssssssssssssssssss", 
            $name, $target_file, $target_file_detail, $location, $description, $history,
            $population, $area, $zip_code, $type, $established, $officials,
            $facilities, $contact, $email, $hours, $mapEmbedURL, $maps_link,
            $directions_link, $website, $facebook, $details_link, $transportation_routes_json
        );
    }
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Barangay " . ($id ? "updated" : "added") . " successfully";
    } else {
        $_SESSION['error'] = "Error saving barangay";
    }
    
    header("Location: add_barangays.php");
    exit();
}

function validateImage($file) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return "Error uploading file. Code: " . $file['error'];
    }
    
    if ($file['size'] > MAX_FILE_SIZE) {
        return "File is too large. Maximum size is " . (MAX_FILE_SIZE / 1024 / 1024) . "MB";
    }
    
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($file_ext, ALLOWED_TYPES)) {
        return "Invalid file type. Only " . implode(', ', ALLOWED_TYPES) . " are allowed";
    }
    
    return true;
}

$transportation_routes = array();
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT transportation_routes FROM barangays WHERE id=?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (!empty($row['transportation_routes'])) {
            $decoded_routes = json_decode($row['transportation_routes'], true);
            if (is_array($decoded_routes)) {
                $transportation_routes = $decoded_routes;
            }
        }
    }
}

$query = "SELECT * FROM barangays ORDER BY name ASC";
$stmt = $conn->prepare($query);
$stmt->execute();
$barangays = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangays Management</title>
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
        .card-image-preview {
            max-width: 200px;
            max-height: 200px;
            object-fit: contain;
        }
        .modal-backdrop.show {
            opacity: 0.8;
        }
        textarea.form-control {
            min-height: 100px;
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
        .remove-route-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 3px;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        .add-route-btn {
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="toast-container">
        <?php if (isset($_SESSION['message'])): ?>
            <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header bg-success text-white">
                    <strong class="me-auto">Success</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body">
                    <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header bg-danger text-white">
                    <strong class="me-auto">Error</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
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
            <h1 class="text-light">Barangays Management</h1>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#barangayModal">
                <i class="bi bi-plus-lg"></i> Add Barangay
            </button>
        </div>
        
        <?php if ($barangays->num_rows == 0): ?>
            <div class="alert alert-info">
                No barangays found. Click "Add Barangay" to create one.
            </div>
        <?php else: ?>
            <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-4">
                <?php while ($barangay = $barangays->fetch_assoc()): ?>
                    <div class="col">
                        <div class="card h-100">
                            <?php if (!empty($barangay['image'])): ?>
                                <img src="<?php echo $barangay['image']; ?>" class="card-img-top" alt="<?php echo $barangay['name']; ?>">
                            <?php else: ?>
                                <div class="card-img-top bg-secondary d-flex align-items-center justify-content-center">
                                    <i class="bi bi-building text-white" style="font-size: 3rem;"></i>
                                </div>
                            <?php endif; ?>
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?php echo $barangay['name']; ?></h5>
                                <p class="card-text"><?php echo $barangay['location']; ?></p>
                                <div class="d-flex justify-content-between mt-auto">
                                    <a href="add_barangays.php?edit=<?php echo $barangay['id']; ?>" class="btn btn-primary btn-sm">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    <a href="?delete=<?php echo $barangay['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this barangay? All associated images will also be deleted.')">
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

    <div class="modal fade <?php echo isset($_GET['edit']) ? 'show d-block' : ''; ?>" id="barangayModal" tabindex="-1" aria-labelledby="barangayModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="barangayModalLabel"><?php echo isset($_GET['edit']) ? 'Edit Barangay' : 'Add Barangay'; ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php 
                        $barangayData = [
                            'id' => '',
                            'name' => '',
                            'location' => '',
                            'website' => '',
                            'facebook' => '',
                            'image' => '',
                            'image_detail' => '',
                            'maps_embed' => '',
                            'maps_link' => '',
                            'directions_link' => '',
                            'description' => '',
                            'history' => '',
                            'population' => '',
                            'area' => '',
                            'zip_code' => '',
                            'type' => '',
                            'established' => '',
                            'officials' => '',
                            'facilities' => '',
                            'contact' => '',
                            'email' => '',
                            'hours' => '',
                            'details_link' => ''
                        ];
                        
                        if (isset($_GET['edit'])) {
                            $edit_id = intval($_GET['edit']);
                            $stmt = $conn->prepare("SELECT * FROM barangays WHERE id=?");
                            $stmt->bind_param("i", $edit_id);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            if ($result->num_rows > 0) {
                                $barangayData = $result->fetch_assoc();
                            }
                        }
                    ?>
                    <form method="POST" action="add_barangays.php" enctype="multipart/form-data" id="barangayForm">
                        <input type="hidden" name="id" value="<?php echo $barangayData['id']; ?>">
                        
                        <div class="form-section">
                            <h5>Basic Information</h5>
                            <div class="mb-3">
                                <label class="form-label">Name *</label>
                                <input type="text" class="form-control" name="name" value="<?php echo $barangayData['name']; ?>" maxlength="100">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Details Page Link:</label>
                                <input type="text" class="form-control" name="details_link" value="<?php echo htmlspecialchars($barangayData['details_link']); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Address *</label>
                                <input type="text" class="form-control" name="location" value="<?php echo $barangayData['location']; ?>" maxlength="255">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" name="description" maxlength="2000"><?php echo $barangayData['description']; ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Website URL</label>
                                <input type="url" class="form-control" name="website" value="<?php echo $barangayData['website']; ?>" maxlength="255">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Facebook Page URL</label>
                                <input type="url" class="form-control" name="facebook" value="<?php echo $barangayData['facebook']; ?>" maxlength="255">
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h5>Images</h5>
                            <div class="mb-3">
                                <label class="form-label">Card Image (Main Display Image)</label>
                                <input type="file" class="form-control" name="image" accept="image/*">
                                
                                <?php if (!empty($barangayData['image'])): ?>
                                    <div class="mt-3">
                                        <p>Current Image:</p>
                                        <img src="<?php echo $barangayData['image']; ?>" class="img-thumbnail card-image-preview">
                                        <div class="form-check mt-2">
                                            <input class="form-check-input" type="checkbox" name="delete_image" value="1" id="deleteImageCheck">
                                            <label class="form-check-label" for="deleteImageCheck">Delete current image</label>
                                        </div>
                                        <input type="hidden" name="existing_image" value="<?php echo $barangayData['image']; ?>">
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Detail Images (Max <?php echo MAX_DETAIL_IMAGES; ?>)</label>
                                <input type="file" class="form-control" name="image_detail[]" multiple accept="image/*">
                                <small class="text-muted">Upload additional images to display on the barangay details page</small>
                                
                                <?php if (!empty($barangayData['image_detail'])): ?>
                                    <div class="mt-3">
                                        <p>Current Detail Images:</p>
                                        <div class="d-flex flex-wrap gap-2">
                                            <?php 
                                            $images = explode(',', $barangayData['image_detail']);
                                            foreach ($images as $img): 
                                                if (!empty($img)):
                                            ?>
                                                <div class="position-relative">
                                                    <img src="<?php echo $img; ?>" class="img-thumbnail" style="width: 100px; height: 100px; object-fit: cover;">
                                                    <a href="?delete_image=<?php echo urlencode($img); ?>&barangay_id=<?php echo $barangayData['id']; ?>" 
                                                       class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" 
                                                       onclick="return confirm('Are you sure you want to delete this image?')">
                                                       ×
                                                    </a>
                                                </div>
                                            <?php 
                                                endif;
                                            endforeach; 
                                            ?>
                                        </div>
                                        <input type="hidden" name="existing_image_detail" value="<?php echo $barangayData['image_detail']; ?>">
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="form-section">
                            <h5>Location Information</h5>
                            <div class="mb-3">
                                <label class="form-label">Google Maps Directions Link</label>
                                <input type="url" class="form-control" name="directions_link" value="<?php echo $barangayData['directions_link']; ?>" maxlength="255">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Google Map Link</label>
                                <input type="url" class="form-control" name="maps_link" value="<?php echo $barangayData['maps_link']; ?>" maxlength="255">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Embed Google Map URL</label>
                                <input type="url" class="form-control" name="maps_embed" value="<?php echo $barangayData['maps_embed']; ?>" maxlength="1000">
                                <small class="text-muted">Use the "Share" > "Embed a map" option from Google Maps</small>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h5>Transportation Routes</h5>
                            <div id="transport-routes-container">
                                <?php 
                                if (!empty($transportation_routes)) {
                                    foreach ($transportation_routes as $index => $route): 
                                ?>
                                    <div class="transport-route-item" data-index="<?php echo $index; ?>">
                                        <button type="button" class="remove-route-btn" onclick="removeRoute(this)">×</button>
                                        <div class="transport-route-header">
                                            <h6 class="mb-0">Route #<?php echo $index + 1; ?></h6>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Transport Type *</label>
                                                    <select class="form-control" name="route_type[]" required>
                                                        <option value="">Select type</option>
                                                        <option value="jeepney" <?php echo ($route['type'] == 'jeepney') ? 'selected' : ''; ?>>Jeepney</option>
                                                        <option value="bus" <?php echo ($route['type'] == 'bus') ? 'selected' : ''; ?>>Bus</option>
                                                        <option value="taxi" <?php echo ($route['type'] == 'taxi') ? 'selected' : ''; ?>>Taxi</option>
                                                        <option value="tricycle" <?php echo ($route['type'] == 'tricycle') ? 'selected' : ''; ?>>Tricycle</option>
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Route Name/Number *</label>
                                                    <input type="text" class="form-control" name="route_name[]" value="<?php echo htmlspecialchars($route['name']); ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Route Link (Optional)</label>
                                                    <input type="text" class="form-control" name="route_link[]" value="<?php echo htmlspecialchars($route['link'] ?? ''); ?>">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Description (Optional)</label>
                                                    <textarea class="form-control" name="route_description[]" rows="2"><?php echo htmlspecialchars($route['description'] ?? ''); ?></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php 
                                    endforeach; 
                                } else {
                                ?>
                                    <div class="transport-route-item" data-index="0">
                                        <button type="button" class="remove-route-btn" onclick="removeRoute(this)">×</button>
                                        <div class="transport-route-header">
                                            <h6 class="mb-0">Route #1</h6>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Transport Type *</label>
                                                    <select class="form-control" name="route_type[]" required>
                                                        <option value="">Select type</option>
                                                        <option value="jeepney">Jeepney</option>
                                                        <option value="bus">Bus</option>
                                                        <option value="taxi">Taxi</option>
                                                        <option value="tricycle">Tricycle</option>
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Route Name/Number *</label>
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
                                <?php
                                }
                                ?>
                            </div>
                            <button type="button" class="btn btn-secondary btn-sm add-route-btn" onclick="addRoute()">
                                <i class="bi bi-plus"></i> Add Another Route
                            </button>
                            <div class="mt-2">
                                <small class="text-muted">Note: All routes marked with * are required. Click the × button to remove a route.</small>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h5>Historical Information</h5>
                            <div class="mb-3">
                                <label class="form-label">History</label>
                                <textarea class="form-control" name="history" maxlength="2000"><?php echo $barangayData['history']; ?></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Type</label>
                                    <input type="text" class="form-control" name="type" value="<?php echo $barangayData['type']; ?>" maxlength="50">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Established</label>
                                    <input type="text" class="form-control" name="established" value="<?php echo $barangayData['established']; ?>" maxlength="50">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h5>Demographic Information</h5>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Population</label>
                                    <input type="text" class="form-control" name="population" value="<?php echo $barangayData['population']; ?>" maxlength="20">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Area (sq km)</label>
                                    <input type="text" class="form-control" name="area" value="<?php echo $barangayData['area']; ?>" maxlength="20">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">ZIP Code</label>
                                    <input type="text" class="form-control" name="zip_code" value="<?php echo $barangayData['zip_code']; ?>" maxlength="10">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h5>Barangay Officials</h5>
                            <div class="mb-3">
                                <label class="form-label">Officials List</label>
                                <textarea class="form-control" name="officials" placeholder="Enter one official per line" maxlength="1000"><?php echo $barangayData['officials']; ?></textarea>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h5>Facilities</h5>
                            <div class="mb-3">
                                <label class="form-label">Facilities List</label>
                                <textarea class="form-control" name="facilities" placeholder="Enter one facility per line" maxlength="1000"><?php echo $barangayData['facilities']; ?></textarea>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h5>Contact Information</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Contact Number</label>
                                    <input type="text" class="form-control" name="contact" value="<?php echo $barangayData['contact']; ?>" maxlength="50">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email Address</label>
                                    <input type="email" class="form-control" name="email" value="<?php echo $barangayData['email']; ?>" maxlength="100">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Operating Hours</label>
                                <input type="text" class="form-control" name="hours" value="<?php echo $barangayData['hours']; ?>" maxlength="100">
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <?php echo isset($_GET['edit']) ? 'Update Barangay' : 'Save Barangay'; ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php if (isset($_GET['edit'])): ?>
        <div class="modal-backdrop fade show"></div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var toasts = document.querySelectorAll('.toast');
            toasts.forEach(function(toast) {
                setTimeout(function() {
                    var bsToast = new bootstrap.Toast(toast);
                    bsToast.hide();
                }, 5000);
            });
            
            if (window.location.search.includes('edit')) {
                var modal = new bootstrap.Modal(document.getElementById('barangayModal'));
                modal.show();
                
                history.replaceState(null, null, window.location.pathname);
            }
            
            var barangayModal = document.getElementById('barangayModal');
            if (barangayModal) {
                barangayModal.addEventListener('hidden.bs.modal', function () {
                    var backdrops = document.getElementsByClassName('modal-backdrop');
                    for (var i = 0; i < backdrops.length; i++) {
                        backdrops[i].remove();
                    }
                });
            }
            
            updateRouteNumbers();
        });
        
        function addRoute() {
            const container = document.getElementById('transport-routes-container');
            const routeItems = container.querySelectorAll('.transport-route-item');
            const newIndex = routeItems.length;
            
            const newRoute = document.createElement('div');
            newRoute.className = 'transport-route-item';
            newRoute.setAttribute('data-index', newIndex);
            
            newRoute.innerHTML = `
                <button type="button" class="remove-route-btn" onclick="removeRoute(this)">×</button>
                <div class="transport-route-header">
                    <h6 class="mb-0">Route #${newIndex + 1}</h6>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Transport Type *</label>
                            <select class="form-control" name="route_type[]" required>
                                <option value="">Select type</option>
                                <option value="jeepney">Jeepney</option>
                                <option value="bus">Bus</option>
                                <option value="taxi">Taxi</option>
                                <option value="tricycle">Tricycle</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Route Name/Number *</label>
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
            updateRouteNumbers();
        }
        
        function removeRoute(button) {
            const routeItem = button.closest('.transport-route-item');
            routeItem.remove();
            updateRouteNumbers();
        }
        
        function updateRouteNumbers() {
            const container = document.getElementById('transport-routes-container');
            const routeItems = container.querySelectorAll('.transport-route-item');
            
            routeItems.forEach((item, index) => {
                const header = item.querySelector('.transport-route-header h6');
                header.textContent = `Route #${index + 1}`;
                item.setAttribute('data-index', index);
            });
        }
        
        document.getElementById('barangayForm')?.addEventListener('submit', function(e) {
            const routeItems = document.querySelectorAll('.transport-route-item');
            routeItems.forEach(item => {
                const typeSelect = item.querySelector('select[name="route_type[]"]');
                const nameInput = item.querySelector('input[name="route_name[]"]');
                
                if (!typeSelect.value && !nameInput.value) {
                    typeSelect.disabled = true;
                    nameInput.disabled = true;
                    const linkInput = item.querySelector('input[name="route_link[]"]');
                    const descInput = item.querySelector('textarea[name="route_description[]"]');
                    if (linkInput) linkInput.disabled = true;
                    if (descInput) descInput.disabled = true;
                }
            });
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>