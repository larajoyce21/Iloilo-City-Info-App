<?php
session_start();
require 'conn.php';

if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    
    $stmt = $conn->prepare("SELECT image, image_detail FROM pawnshops WHERE id=?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $pawnshop = $result->fetch_assoc();
    
    $stmt = $conn->prepare("DELETE FROM pawnshops WHERE id=?");
    $stmt->bind_param("i", $delete_id);
    
    if ($stmt->execute()) {
        if ($pawnshop) {
            if (!empty($pawnshop['image'])) {
                $image_path = 'uploads/' . basename($pawnshop['image']);
                if (file_exists($image_path)) {
                    unlink($image_path);
                }
            }
            
            if (!empty($pawnshop['image_detail'])) {
                $images = explode(',', $pawnshop['image_detail']);
                foreach ($images as $img) {
                    if (!empty($img)) {
                        $img_path = 'uploads/' . basename($img);
                        if (file_exists($img_path)) {
                            unlink($img_path);
                        }
                    }
                }
            }
        }
        
        $_SESSION['message'] = "Pawnshop deleted successfully";
    } else {
        $_SESSION['error'] = "Error deleting pawnshop";
    }
    
    header("Location: add_pawnshops.php");
    exit();
}

if (isset($_GET['delete_image']) && isset($_GET['pawnshop_id'])) {
    $image_path = $_GET['delete_image'];
    $pawnshop_id = intval($_GET['pawnshop_id']);
    $is_card_image = isset($_GET['is_card_image']) ? true : false;
    
    $real_image_path = 'uploads/' . basename($image_path);
    
    if (file_exists($real_image_path)) {
        unlink($real_image_path);
    }
    
    if ($is_card_image) {
        $stmt = $conn->prepare("UPDATE pawnshops SET image='' WHERE id=?");
        $stmt->bind_param("i", $pawnshop_id);
        $stmt->execute();
    } else {
        $stmt = $conn->prepare("SELECT image_detail FROM pawnshops WHERE id=?");
        $stmt->bind_param("i", $pawnshop_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $pawnshop = $result->fetch_assoc();
        
        if ($pawnshop) {
            $images = array_filter(explode(',', $pawnshop['image_detail']));
            $updated_images = array_diff($images, [$image_path]);
            $updated_images_str = implode(',', $updated_images);
            
            $stmt = $conn->prepare("UPDATE pawnshops SET image_detail=? WHERE id=?");
            $stmt->bind_param("si", $updated_images_str, $pawnshop_id);
            $stmt->execute();
        }
    }
    
    $_SESSION['message'] = "Image deleted successfully";
    header("Location: add_pawnshops.php?edit=" . $pawnshop_id);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : null;
    $name = $_POST['name'];
    $description = $_POST['description'];
    $hours = $_POST['hours'] ?? '';
    $contact = $_POST['contact'] ?? '';
    $location = $_POST['location'];
    $website = $_POST['website'];
    $maps_link = $_POST['maps_link'];
    $directions_link = $_POST['directions_link'];
    $mapEmbedURL = $_POST['map_embed'];
    $established = $_POST['established'] ?? '';
    $items_accepted = $_POST['items_accepted'] ?? '';
    $interest_rates = $_POST['interest_rates'] ?? '';
    $security_features = $_POST['security_features'] ?? '';
    $services = $_POST['services'] ?? '';
    $email = $_POST['email'] ?? '';
    $hotline = $_POST['hotline'] ?? '';
    $branches = $_POST['branches'] ?? '';
    $social_media = $_POST['social_media'] ?? '';
    $type = $_POST['type'] ?? '';
    $nearby_places = $_POST['nearby_places'] ?? '';
    $details_link = $_POST['details_link'] ?? '';
    
    // Handle transportation routes
    $transportation_routes = array();
    
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

    if (empty($name) || empty($description) || empty($location)) {
        $_SESSION['error'] = "Please fill in all required fields";
        header("Location: add_pawnshops.php" . ($id ? "?edit=$id" : ""));
        exit();
    }
    
    // Define allowed file types
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
    
    $target_file = $_POST['existing_image'] ?? '';
    
    if (isset($_FILES['image']['name']) && $_FILES['image']['name']) {
        $file = $_FILES['image'];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (in_array($file_ext, $allowed_types)) {
            $new_filename = uniqid('img_', true) . '.' . $file_ext;
            $target_file = 'uploads/' . $new_filename;
            
            if (move_uploaded_file($file['tmp_name'], $target_file)) {
                if (!empty($_POST['existing_image']) && file_exists($_POST['existing_image'])) {
                    unlink($_POST['existing_image']);
                }
            } else {
                $_SESSION['error'] = "Error uploading the main image";
                header("Location: add_pawnshops.php" . ($id ? "?edit=$id" : ""));
                exit();
            }
        } else {
            $_SESSION['error'] = "Invalid file type. Only JPG, JPEG, PNG, GIF are allowed";
            header("Location: add_pawnshops.php" . ($id ? "?edit=$id" : ""));
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
        $max_detail_images = 5;
        
        if ($totalCombined > $max_detail_images) {
            $_SESSION['error'] = "You can upload a maximum of $max_detail_images detail images total";
            header("Location: add_pawnshops.php" . ($id ? "?edit=$id" : ""));
            exit();
        }
        
        for ($i = 0; $i < $totalNew; $i++) {
            if ($_FILES['image_detail']['error'][$i] !== UPLOAD_ERR_OK) {
                continue;
            }
            
            $file = [
                'name' => $_FILES['image_detail']['name'][$i],
                'tmp_name' => $_FILES['image_detail']['tmp_name'][$i]
            ];
            
            $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (in_array($file_ext, $allowed_types)) {
                $new_filename = uniqid('img_', true) . '.' . $file_ext;
                $targetPath = 'uploads/' . $new_filename;
                
                if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                    $imagePaths[] = $targetPath;
                }
            }
        }
    }
    
    $allImages = array_merge($existingImages, $imagePaths);
    $target_file_detail = implode(',', $allImages);
    
    if ($id) {
        $stmt = $conn->prepare("UPDATE pawnshops SET name=?, image=?, image_detail=?, description=?, location=?, maps_embed=?, maps_link=?, website=?, directions_link=?, hours=?, contact=?, type=?, established=?, items_accepted=?, interest_rates=?, security_features=?, services=?, email=?, hotline=?, branches=?, social_media=?, nearby_places=?, details_link=?, transportation_routes=? WHERE id=?");
        $stmt->bind_param("ssssssssssssssssssssssssi", $name, $target_file, $target_file_detail, $description, $location, $mapEmbedURL, $maps_link, $website, $directions_link, $hours, $contact, $type, $established, $items_accepted, $interest_rates, $security_features, $services, $email, $hotline, $branches, $social_media, $nearby_places, $details_link, $transportation_routes_json, $id);
    } else {
        $stmt = $conn->prepare("INSERT INTO pawnshops (name, image, image_detail, description, location, maps_embed, maps_link, website, directions_link, hours, contact, type, established, items_accepted, interest_rates, security_features, services, email, hotline, branches, social_media, nearby_places, details_link, transportation_routes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssssssssssssssssssss", $name, $target_file, $target_file_detail, $description, $location, $mapEmbedURL, $maps_link, $website, $directions_link, $hours, $contact, $type, $established, $items_accepted, $interest_rates, $security_features, $services, $email, $hotline, $branches, $social_media, $nearby_places, $details_link, $transportation_routes_json);
    }

    if ($stmt->execute()) {
        $_SESSION['message'] = "Pawnshop " . ($id ? "updated" : "added") . " successfully";
    } else {
        $_SESSION['error'] = "Error saving pawnshop: " . $stmt->error;
    }
    
    header("Location: add_pawnshops.php");
    exit();
}

$query = "SELECT * FROM pawnshops ORDER BY name ASC";
$result = $conn->query($query);
$pawnshops = $result->fetch_all(MYSQLI_ASSOC);

$pawnshopData = [
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
    'type' => '',
    'established' => '',
    'items_accepted' => '',
    'interest_rates' => '',
    'security_features' => '',
    'services' => '',
    'email' => '',
    'hotline' => '',
    'branches' => '',
    'social_media' => '',
    'nearby_places' => '',
    'details_link' => '',
    'transportation_routes' => ''
];

$transportation_routes = array();
$nearby_places_str = '';

if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM pawnshops WHERE id=?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $pawnshopData = $result->fetch_assoc();
        if (!empty($pawnshopData['transportation_routes'])) {
            $decoded_routes = json_decode($pawnshopData['transportation_routes'], true);
            if (is_array($decoded_routes)) {
                $transportation_routes = $decoded_routes;
            }
        }
        $nearby_places_str = $pawnshopData['nearby_places'];
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
    <title>Pawnshops Management</title>
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
            <h1 class="text-light">Pawnshops Management</h1>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#pawnshopModal">
                <i class="bi bi-plus-lg"></i> Add Pawnshop
            </button>
        </div>
        
        <?php if (empty($pawnshops)): ?>
            <div class="alert alert-info">
                No pawnshops found. Click "Add Pawnshop" to create one.
            </div>
        <?php else: ?>
            <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-4">
                <?php foreach ($pawnshops as $pawnshop): ?>
                    <div class="col">
                        <div class="card h-100">
                            <?php if (!empty($pawnshop['image'])): ?>
                                <img src="<?php echo $pawnshop['image']; ?>" class="card-img-top" alt="<?php echo $pawnshop['name']; ?>">
                            <?php else: ?>
                                <div class="card-img-top bg-secondary d-flex align-items-center justify-content-center">
                                    <i class="bi bi-gem text-white" style="font-size: 3rem;"></i>
                                </div>
                            <?php endif; ?>
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?php echo $pawnshop['name']; ?></h5>
                                <p class="card-text flex-grow-1"><?php echo substr($pawnshop['description'], 0, 100) . '...'; ?></p>
                                <div class="d-flex justify-content-between">
                                    <a href="add_pawnshops.php?edit=<?php echo $pawnshop['id']; ?>" class="btn btn-primary btn-sm">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    <a href="?delete=<?php echo $pawnshop['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this pawnshop? All associated images will also be deleted.')">
                                        <i class="bi bi-trash"></i> Delete
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Add/Edit Pawnshop Modal -->
    <div class="modal fade <?php echo isset($_GET['edit']) ? 'show d-block' : ''; ?>" id="pawnshopModal" tabindex="-1" aria-labelledby="pawnshopModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="pawnshopModalLabel"><?php echo isset($_GET['edit']) ? 'Edit Pawnshop' : 'Add Pawnshop'; ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="add_pawnshops.php" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="<?php echo $pawnshopData['id']; ?>">
                        
                        <div class="form-section">
                            <h5>Basic Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Name *</label>
                                        <input type="text" class="form-control" name="name" value="<?php echo $pawnshopData['name']; ?>" required maxlength="100">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Details Page Link:</label>
                                        <input type="text" class="form-control" name="details_link" value="<?php echo htmlspecialchars($pawnshopData['details_link']); ?>" placeholder="e.g., pawnshop1.php?id=1">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Description *</label>
                                        <textarea class="form-control" name="description" rows="4" required maxlength="2000"><?php echo $pawnshopData['description']; ?></textarea>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Operating Hours</label>
                                        <textarea class="form-control" name="hours" rows="4" placeholder="e.g., Monday: 9AM - 5PM"><?php echo $pawnshopData['hours']; ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Contact Information</label>
                                        <input type="text" class="form-control" name="contact" value="<?php echo $pawnshopData['contact']; ?>" placeholder="Phone, email, social media" maxlength="100">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h5>Images</h5>
                            <div class="mb-3">
                                <label class="form-label">Card Image (Main Display Image)</label>
                                <input type="file" class="form-control" name="image" accept="image/*">
                                
                                <?php if (!empty($pawnshopData['image'])): ?>
                                    <div class="mt-3">
                                        <p>Current Image:</p>
                                        <img src="<?php echo $pawnshopData['image']; ?>" class="img-thumbnail card-image-preview">
                                        <div class="form-check mt-2">
                                            <input class="form-check-input" type="checkbox" name="delete_image" value="1" id="deleteImageCheck">
                                            <label class="form-check-label" for="deleteImageCheck">Delete current image</label>
                                        </div>
                                        <input type="hidden" name="existing_image" value="<?php echo $pawnshopData['image']; ?>">
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Detail Images (Max 5)</label>
                                <input type="file" class="form-control" name="image_detail[]" multiple accept="image/*">
                                <small class="text-muted">Upload additional images to display on the pawnshop details page</small>
                                
                                <?php if (!empty($pawnshopData['image_detail'])): ?>
                                    <div class="mt-3">
                                        <p>Current Detail Images:</p>
                                        <div class="d-flex flex-wrap gap-2">
                                            <?php 
                                            $images = explode(',', $pawnshopData['image_detail']);
                                            foreach ($images as $img): 
                                                if (!empty($img)):
                                            ?>
                                                <div class="position-relative">
                                                    <img src="<?php echo $img; ?>" class="img-thumbnail" style="width: 100px; height: 100px; object-fit: cover;">
                                                    <a href="?delete_image=<?php echo urlencode($img); ?>&pawnshop_id=<?php echo $pawnshopData['id']; ?>" 
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
                                        <input type="hidden" name="existing_image_detail" value="<?php echo $pawnshopData['image_detail']; ?>">
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
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
                                                        <option value="tricycle" <?php echo (isset($transportation_routes[$i]['type']) && $transportation_routes[$i]['type'] == 'tricycle') ? 'selected' : ''; ?>>Tricycle</option>
                                                        <option value="taxi" <?php echo (isset($transportation_routes[$i]['type']) && $transportation_routes[$i]['type'] == 'taxi') ? 'selected' : ''; ?>>Taxi</option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control" name="route_name[]" value="<?php echo isset($transportation_routes[$i]['name']) ? htmlspecialchars($transportation_routes[$i]['name']) : ''; ?>" placeholder="e.g., Route 101, Transport Company">
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control" name="route_link[]" value="<?php echo isset($transportation_routes[$i]['link']) ? htmlspecialchars($transportation_routes[$i]['link']) : ''; ?>" placeholder="https://maps.google.com/...">
                                                </td>
                                                <td>
                                                    <textarea class="form-control" name="route_description[]" rows="2" placeholder="e.g., Main transportation to the area, nearby bus stops"><?php echo isset($transportation_routes[$i]['description']) ? htmlspecialchars($transportation_routes[$i]['description']) : ''; ?></textarea>
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

                        <div class="form-section">
                            <h5>Pawnshop Specific Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Type</label>
                                        <input type="text" class="form-control" name="type" value="<?php echo $pawnshopData['type']; ?>" placeholder="e.g., Jewelry, General, etc.">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Established</label>
                                        <input type="text" class="form-control" name="established" value="<?php echo $pawnshopData['established']; ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Items Accepted</label>
                                        <textarea class="form-control" name="items_accepted" rows="3" placeholder="Jewelry, electronics, gadgets, etc."><?php echo $pawnshopData['items_accepted']; ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Interest Rates</label>
                                        <input type="text" class="form-control" name="interest_rates" value="<?php echo $pawnshopData['interest_rates']; ?>" placeholder="e.g., 3% per month">
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Security Features</label>
                                        <textarea class="form-control" name="security_features" rows="3" placeholder="Security cameras, alarm system, etc."><?php echo $pawnshopData['security_features']; ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Services</label>
                                        <textarea class="form-control" name="services" rows="3" placeholder="Pawn, sell, buyback, etc."><?php echo $pawnshopData['services']; ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" name="email" value="<?php echo $pawnshopData['email']; ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Hotline</label>
                                        <input type="text" class="form-control" name="hotline" value="<?php echo $pawnshopData['hotline']; ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Branches (one per line)</label>
                                <textarea class="form-control" name="branches" rows="5"><?php echo htmlspecialchars($pawnshopData['branches']); ?></textarea>
                                <small class="text-muted">Enter each branch on a separate line</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Nearby Places</label>
                                <input name="nearby_places" class="form-control" value="<?php echo $nearby_places_str; ?>" placeholder="Add nearby places separated by commas">
                                <small class="text-muted">Example: Mall, Bank, Market</small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Social Media Links</label>
                            <textarea class="form-control" name="social_media" rows="5" placeholder="Enter one URL per line, e.g.https://facebook.com/pawnshop"><?php echo $pawnshopData['social_media']; ?></textarea>
                        </div>
                        
                        <div class="form-section">
                            <h5>Location Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Address *</label>
                                        <input type="text" class="form-control" name="location" value="<?php echo $pawnshopData['location']; ?>" required maxlength="255">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Website URL</label>
                                        <input type="url" class="form-control" name="website" value="<?php echo $pawnshopData['website']; ?>" placeholder="https://example.com" maxlength="255">
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Google Maps Directions Link</label>
                                        <input type="url" class="form-control" name="directions_link" value="<?php echo $pawnshopData['directions_link']; ?>" placeholder="https://maps.google.com/..." maxlength="255">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Google Map Link</label>
                                        <input type="url" class="form-control" name="maps_link" value="<?php echo $pawnshopData['maps_link']; ?>" placeholder="https://maps.google.com/..." maxlength="255">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Embed Google Map URL</label>
                                <input type="url" class="form-control" name="map_embed" value="<?php echo $pawnshopData['maps_embed']; ?>" placeholder="https://maps.google.com/embed..." maxlength="1000">
                                <small class="text-muted">Use the "Share" > "Embed a map" option from Google Maps</small>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <?php echo isset($_GET['edit']) ? 'Update Pawnshop' : 'Save Pawnshop'; ?>
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
                var modal = new bootstrap.Modal(document.getElementById('pawnshopModal'));
                modal.show();
                
                history.replaceState(null, null, window.location.pathname);
            }
            
            var pawnshopModal = document.getElementById('pawnshopModal');
            if (pawnshopModal) {
                pawnshopModal.addEventListener('hidden.bs.modal', function () {
                    var backdrops = document.getElementsByClassName('modal-backdrop');
                    for (var i = 0; i < backdrops.length; i++) {
                        backdrops[i].remove();
                    }
                });
            }
        });
        
        function addRow() {
            var tbody = document.getElementById('transport-routes-tbody');
            var newRow = document.createElement('tr');
            newRow.innerHTML = `
                <td>
                    <select class="form-control" name="route_type[]">
                        <option value="">Select Type</option>
                        <option value="jeepney">Jeepney</option>
                        <option value="bus">Bus</option>
                        <option value="tricycle">Tricycle</option>
                        <option value="taxi">Taxi</option>
                    </select>
                </td>
                <td>
                    <input type="text" class="form-control" name="route_name[]" placeholder="e.g., Route 101, Transport Company">
                </td>
                <td>
                    <input type="text" class="form-control" name="route_link[]" placeholder="https://maps.google.com/...">
                </td>
                <td>
                    <textarea class="form-control" name="route_description[]" rows="2" placeholder="e.g., Main transportation to the area, nearby bus stops"></textarea>
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