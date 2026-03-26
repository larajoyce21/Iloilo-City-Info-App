<?php
session_start();
require 'conn.php';

if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    
    $stmt = $conn->prepare("SELECT image, image_detail FROM clothing_stores WHERE id=?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $store = $result->fetch_assoc();
    
    $stmt = $conn->prepare("DELETE FROM clothing_stores WHERE id=?");
    $stmt->bind_param("i", $delete_id);
    
    if ($stmt->execute()) {
        if ($store) {
            if (!empty($store['image'])) {
                $image_path = 'uploads/' . basename($store['image']);
                if (file_exists($image_path)) {
                    unlink($image_path);
                }
            }
            
            if (!empty($store['image_detail'])) {
                $images = explode(',', $store['image_detail']);
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
        
        $_SESSION['message'] = "Clothing store deleted successfully";
    } else {
        $_SESSION['error'] = "Error deleting clothing store";
    }
    
    header("Location: add_clothing_stores.php");
    exit();
}

if (isset($_GET['delete_image']) && isset($_GET['store_id'])) {
    $image_path = $_GET['delete_image'];
    $store_id = intval($_GET['store_id']);
    $is_card_image = isset($_GET['is_card_image']) ? true : false;
    
    $real_image_path = 'uploads/' . basename($image_path);
    
    if (file_exists($real_image_path)) {
        unlink($real_image_path);
    }
    
    if ($is_card_image) {
        $stmt = $conn->prepare("UPDATE clothing_stores SET image='' WHERE id=?");
        $stmt->bind_param("i", $store_id);
        $stmt->execute();
    } else {
        $stmt = $conn->prepare("SELECT image_detail FROM clothing_stores WHERE id=?");
        $stmt->bind_param("i", $store_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $store = $result->fetch_assoc();
        
        if ($store) {
            $images = array_filter(explode(',', $store['image_detail']));
            $updated_images = array_diff($images, [$image_path]);
            $updated_images_str = implode(',', $updated_images);
            
            $stmt = $conn->prepare("UPDATE clothing_stores SET image_detail=? WHERE id=?");
            $stmt->bind_param("si", $updated_images_str, $store_id);
            $stmt->execute();
        }
    }
    
    $_SESSION['message'] = "Image deleted successfully";
    header("Location: add_clothing_stores.php?edit=" . $store_id);
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
    $brands = $_POST['brands'] ?? '';
    $categories = $_POST['categories'] ?? '';
    $price_range = $_POST['price_range'] ?? '';
    $size_availability = $_POST['size_availability'] ?? '';
    $size_chart = $_POST['size_chart'] ?? '';
    $payment_options = $_POST['payment_options'] ?? '';
    $return_policy = $_POST['return_policy'] ?? '';
    $delivery = $_POST['delivery'] ?? '';
    $accessibility = $_POST['accessibility'] ?? '';
    $services = $_POST['services'] ?? '';
    $email = $_POST['email'] ?? '';
    $special_services = $_POST['special_services'] ?? '';
    $branches = $_POST['branches'] ?? '';
    $social_media = $_POST['social_media'] ?? '';
    $type = $_POST['type'] ?? '';
    $nearby_places = $_POST['nearby_places'] ?? '';
    $mall = $_POST['mall'] ?? '';
    $online_store = $_POST['online_store'] ?? '';
    $details_link = $_POST['details_link'] ?? '';

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

    $target_file = $_POST['existing_image'] ?? '';
    
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
    
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
                header("Location: add_clothing_stores.php" . ($id ? "?edit=$id" : ""));
                exit();
            }
        } else {
            $_SESSION['error'] = "Invalid file type. Only JPG, JPEG, PNG, GIF are allowed";
            header("Location: add_clothing_stores.php" . ($id ? "?edit=$id" : ""));
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
            header("Location: add_clothing_stores.php" . ($id ? "?edit=$id" : ""));
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
        $stmt = $conn->prepare("UPDATE clothing_stores SET name=?, image=?, image_detail=?, description=?, location=?, maps_embed=?, maps_link=?, website=?, directions_link=?, hours=?, contact=?, type=?, established=?, brands=?, categories=?, price_range=?, size_availability=?, size_chart=?, payment_options=?, return_policy=?, delivery=?, accessibility=?, services=?, email=?, special_services=?, branches=?, social_media=?, nearby_places=?, mall=?, online_store=?, details_link=?, transportation_routes=? WHERE id=?");
        $stmt->bind_param("ssssssssssssssssssssssssssssssssi", $name, $target_file, $target_file_detail, $description, $location, $mapEmbedURL, $maps_link, $website, $directions_link, $hours, $contact, $type, $established, $brands, $categories, $price_range, $size_availability, $size_chart, $payment_options, $return_policy, $delivery, $accessibility, $services, $email, $special_services, $branches, $social_media, $nearby_places, $mall, $online_store, $details_link, $transportation_routes_json, $id);
    } else {
        $stmt = $conn->prepare("INSERT INTO clothing_stores (name, image, image_detail, description, location, maps_embed, maps_link, website, directions_link, hours, contact, type, established, brands, categories, price_range, size_availability, size_chart, payment_options, return_policy, delivery, accessibility, services, email, special_services, branches, social_media, nearby_places, mall, online_store, details_link, transportation_routes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssssssssssssssssssssssssssss", $name, $target_file, $target_file_detail, $description, $location, $mapEmbedURL, $maps_link, $website, $directions_link, $hours, $contact, $type, $established, $brands, $categories, $price_range, $size_availability, $size_chart, $payment_options, $return_policy, $delivery, $accessibility, $services, $email, $special_services, $branches, $social_media, $nearby_places, $mall, $online_store, $details_link, $transportation_routes_json);
    }

    if ($stmt->execute()) {
        $_SESSION['message'] = "Clothing store " . ($id ? "updated" : "added") . " successfully";
    } else {
        $_SESSION['error'] = "Error saving clothing store: " . $stmt->error;
    }
    
    header("Location: add_clothing_stores.php");
    exit();
}

$query = "SELECT * FROM clothing_stores ORDER BY name ASC";
$result = $conn->query($query);
$stores = $result->fetch_all(MYSQLI_ASSOC);

$nearby_places_str = '';
$transportation_routes = array();

if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM clothing_stores WHERE id=?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $storeData = $result->fetch_assoc();
        
        $nearby_places_str = $storeData['nearby_places'];
        if (!empty($storeData['transportation_routes'])) {
            $decoded_routes = json_decode($storeData['transportation_routes'], true);
            if (is_array($decoded_routes)) {
                $transportation_routes = $decoded_routes;
            }
        }
    }
}

$route_count = count($transportation_routes);
if ($route_count == 0) {
    $route_count = 1; 
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clothing Stores Management</title>
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
        .modal.show.d-block {
            background-color: rgba(0,0,0,0.5);
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
            <h1 class="text-light">Clothing Stores Management</h1>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#storeModal">
                <i class="bi bi-plus-lg"></i> Add Store
            </button>
        </div>
        
        <?php if (empty($stores)): ?>
            <div class="alert alert-info">
                No clothing stores found. Click "Add Store" to create one.
            </div>
        <?php else: ?>
            <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-4">
                <?php foreach ($stores as $store): ?>
                    <div class="col">
                        <div class="card h-100">
                            <?php if (!empty($store['image'])): ?>
                                <img src="<?php echo $store['image']; ?>" class="card-img-top" alt="<?php echo $store['name']; ?>">
                            <?php else: ?>
                                <div class="card-img-top bg-secondary d-flex align-items-center justify-content-center">
                                    <i class="bi bi-shop text-white" style="font-size: 3rem;"></i>
                                </div>
                            <?php endif; ?>
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?php echo $store['name']; ?></h5>
                                <p class="card-text flex-grow-1"><?php echo substr($store['description'], 0, 100) . '...'; ?></p>
                                <div class="d-flex justify-content-between">
                                    <a href="add_clothing_stores.php?edit=<?php echo $store['id']; ?>" class="btn btn-primary btn-sm">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    <a href="?delete=<?php echo $store['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this clothing store? All associated images will also be deleted.')">
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

    <?php if (isset($_GET['edit'])): ?>
    <div class="modal fade show d-block" id="storeModal" tabindex="-1" aria-labelledby="storeModalLabel" aria-hidden="true" style="display: block;">
    <?php else: ?>
    <div class="modal fade" id="storeModal" tabindex="-1" aria-labelledby="storeModalLabel" aria-hidden="true">
    <?php endif; ?>
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="storeModalLabel"><?php echo isset($_GET['edit']) ? 'Edit Clothing Store' : 'Add Clothing Store'; ?></h5>
                    <?php if (isset($_GET['edit'])): ?>
                        <a href="add_clothing_stores.php" class="btn-close" aria-label="Close"></a>
                    <?php else: ?>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    <?php endif; ?>
                </div>
                <div class="modal-body">
                    <?php 
                        if (!isset($storeData)) {
                            $storeData = [
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
                                'brands' => '',
                                'categories' => '',
                                'price_range' => '',
                                'size_availability' => '',
                                'size_chart' => '',
                                'payment_options' => '',
                                'return_policy' => '',
                                'delivery' => '',
                                'accessibility' => '',
                                'services' => '',
                                'email' => '',
                                'special_services' => '',
                                'branches' => '',
                                'social_media' => '',
                                'nearby_places' => '',
                                'mall' => '',
                                'online_store' => '',
                                'details_link' => '',
                                'transportation_routes' => ''
                            ];
                        }
                    ?>
                    <form method="POST" action="add_clothing_stores.php" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="<?php echo $storeData['id']; ?>">
                        
                        <div class="form-section">
                            <h5>Basic Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Name *</label>
                                        <input type="text" class="form-control" name="name" value="<?php echo $storeData['name']; ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Details Page Link:</label>
                                        <input type="text" class="form-control" name="details_link" value="<?php echo htmlspecialchars($storeData['details_link']); ?>" placeholder="e.g., clothing_store1.php?id=1">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Description *</label>
                                        <textarea class="form-control" name="description" rows="4"><?php echo $storeData['description']; ?></textarea>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Operating Hours</label>
                                        <textarea class="form-control" name="hours" rows="4" placeholder="Monday: 9AM - 5PM"><?php echo $storeData['hours']; ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Contact Information</label>
                                        <input type="text" class="form-control" name="contact" value="<?php echo $storeData['contact']; ?>" maxlength="100">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h4>Transportation Routes</h4>
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
                                                    <input type="text" class="form-control" name="route_name[]" value="<?php echo isset($transportation_routes[$i]['name']) ? htmlspecialchars($transportation_routes[$i]['name']) : ''; ?>">
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control" name="route_link[]" value="<?php echo isset($transportation_routes[$i]['link']) ? htmlspecialchars($transportation_routes[$i]['link']) : ''; ?>">
                                                </td>
                                                <td>
                                                    <textarea class="form-control" name="route_description[]" rows="2"><?php echo isset($transportation_routes[$i]['description']) ? htmlspecialchars($transportation_routes[$i]['description']) : ''; ?></textarea>
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
                            <h5>Images</h5>
                            <div class="mb-3">
                                <label class="form-label">Card Image (Main Display Image)</label>
                                <input type="file" class="form-control" name="image" accept="image/*">
                                
                                <?php if (!empty($storeData['image'])): ?>
                                    <div class="mt-3">
                                        <p>Current Image:</p>
                                        <img src="<?php echo $storeData['image']; ?>" class="img-thumbnail card-image-preview">
                                        <div class="form-check mt-2">
                                            <input class="form-check-input" type="checkbox" name="delete_image" value="1" id="deleteImageCheck">
                                            <label class="form-check-label" for="deleteImageCheck">Delete current image</label>
                                        </div>
                                        <input type="hidden" name="existing_image" value="<?php echo $storeData['image']; ?>">
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Detail Images (Max 5)</label>
                                <input type="file" class="form-control" name="image_detail[]" multiple accept="image/*">
                                <small class="text-muted">Upload additional images to display on the store details page</small>
                                
                                <?php if (!empty($storeData['image_detail'])): ?>
                                    <div class="mt-3">
                                        <p>Current Detail Images:</p>
                                        <div class="d-flex flex-wrap gap-2">
                                            <?php 
                                            $images = explode(',', $storeData['image_detail']);
                                            foreach ($images as $img): 
                                                if (!empty($img)):
                                            ?>
                                                <div class="position-relative">
                                                    <img src="<?php echo $img; ?>" class="img-thumbnail" style="width: 100px; height: 100px; object-fit: cover;">
                                                    <a href="?delete_image=<?php echo urlencode($img); ?>&store_id=<?php echo $storeData['id']; ?>" 
                                                       class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger text-decoration-none" 
                                                       onclick="return confirm('Are you sure you want to delete this image?')">
                                                       ×
                                                    </a>
                                                </div>
                                            <?php 
                                                endif;
                                            endforeach; 
                                            ?>
                                        </div>
                                        <input type="hidden" name="existing_image_detail" value="<?php echo $storeData['image_detail']; ?>">
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h5>Product Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Brands (one per line)</label>
                                        <textarea class="form-control" name="brands" rows="3"><?php echo $storeData['brands']; ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Categories (one per line)</label>
                                        <textarea class="form-control" name="categories" rows="3"><?php echo $storeData['categories']; ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Price Range</label>
                                        <input type="text" class="form-control" name="price_range" value="<?php echo $storeData['price_range']; ?>">
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Size Availability</label>
                                        <input type="text" class="form-control" name="size_availability" value="<?php echo $storeData['size_availability']; ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Size Chart URL</label>
                                        <input type="url" class="form-control" name="size_chart" value="<?php echo $storeData['size_chart']; ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Online Store URL</label>
                                        <input type="url" class="form-control" name="online_store" value="<?php echo $storeData['online_store']; ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <h5>Store Policies & Services</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Payment Options</label>
                                        <input type="text" class="form-control" name="payment_options" value="<?php echo $storeData['payment_options']; ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Return Policy</label>
                                        <textarea class="form-control" name="return_policy" rows="3"><?php echo $storeData['return_policy']; ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Delivery Services</label>
                                        <input type="text" class="form-control" name="delivery" value="<?php echo $storeData['delivery']; ?>">
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Accessibility</label>
                                        <input type="text" class="form-control" name="accessibility" value="<?php echo $storeData['accessibility']; ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Special Services</label>
                                        <textarea class="form-control" name="special_services" rows="3"><?php echo $storeData['special_services']; ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Services</label>
                                        <textarea class="form-control" name="services" rows="3"><?php echo $storeData['services']; ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <h5>Additional Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Type</label>
                                        <input type="text" class="form-control" name="type" value="<?php echo $storeData['type']; ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Established</label>
                                        <input type="text" class="form-control" name="established" value="<?php echo $storeData['established']; ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" name="email" value="<?php echo $storeData['email']; ?>">
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Mall/Shopping Center</label>
                                        <input type="text" class="form-control" name="mall" value="<?php echo $storeData['mall']; ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Branches (one per line)</label>
                                        <textarea class="form-control" name="branches" rows="5"><?php echo htmlspecialchars($storeData['branches']); ?></textarea>
                                        <small class="text-muted">Enter each branch on a separate line</small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Nearby Places</label>
                                <input name="nearby_places" class="form-control" value="<?php echo $nearby_places_str; ?>">
                                <small class="text-muted">Example: Restaurant, Coffee Shop, Parking</small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Social Media Links</label>
                            <textarea class="form-control" name="social_media" rows="5"><?php echo $storeData['social_media']; ?></textarea>
                        </div>
                        
                        <div class="form-section">
                            <h5>Location Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Address *</label>
                                        <input type="text" class="form-control" name="location" value="<?php echo $storeData['location']; ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Website URL</label>
                                        <input type="url" class="form-control" name="website" value="<?php echo $storeData['website']; ?>" maxlength="255">
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Google Maps Directions Link</label>
                                        <input type="url" class="form-control" name="directions_link" value="<?php echo $storeData['directions_link']; ?>" maxlength="255">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Google Map Link</label>
                                        <input type="url" class="form-control" name="maps_link" value="<?php echo $storeData['maps_link']; ?>" maxlength="255">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Embed Google Map URL</label>
                                <input type="url" class="form-control" name="map_embed" value="<?php echo $storeData['maps_embed']; ?>" maxlength="1000">
                                <small class="text-muted">Use the "Share" > "Embed a map" option from Google Maps</small>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-end gap-2">
                            <?php if (isset($_GET['edit'])): ?>
                                <a href="add_clothing_stores.php" class="btn btn-secondary">Cancel</a>
                            <?php else: ?>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <?php endif; ?>
                            <button type="submit" class="btn btn-primary">
                                <?php echo isset($_GET['edit']) ? 'Update Store' : 'Save Store'; ?>
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
                    <input type="text" class="form-control" name="route_name[]">
                </td>
                <td>
                    <input type="text" class="form-control" name="route_link[]">
                </td>
                <td>
                    <textarea class="form-control" name="route_description[]" rows="2"></textarea>
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
            
            if (rows.length > 1) {
                row.remove();
            } else {
                alert("You need at least one transportation route. Add another row before removing this one.");
            }
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            const toasts = document.querySelectorAll('.toast');
            toasts.forEach(toast => {
                setTimeout(() => {
                    toast.classList.remove('show');
                }, 5000);
            });
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>