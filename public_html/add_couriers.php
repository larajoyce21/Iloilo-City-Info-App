<?php
session_start();
require 'conn.php';

$allowed_types = ['jpg', 'jpeg', 'png', 'gif'];

if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    
    $stmt = $conn->prepare("SELECT image, image_detail FROM couriers WHERE id=?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $courier = $result->fetch_assoc();
    
    $stmt = $conn->prepare("DELETE FROM couriers WHERE id=?");
    $stmt->bind_param("i", $delete_id);
    
    if ($stmt->execute()) {
        if ($courier) {
            if (!empty($courier['image'])) {
                $image_path = 'uploads/' . basename($courier['image']);
                if (file_exists($image_path)) {
                    unlink($image_path);
                }
            }
            
            if (!empty($courier['image_detail'])) {
                $images = explode(',', $courier['image_detail']);
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
        
        $_SESSION['message'] = "Courier deleted successfully";
    } else {
        $_SESSION['error'] = "Error deleting courier";
    }
    
    header("Location: add_couriers.php");
    exit();
}

if (isset($_GET['delete_image']) && isset($_GET['courier_id'])) {
    $image_path = $_GET['delete_image'];
    $courier_id = $_GET['courier_id'];
    $is_card_image = isset($_GET['is_card_image']) ? true : false;
    
    $real_image_path = 'uploads/' . basename($image_path);
    
    if (file_exists($real_image_path)) {
        unlink($real_image_path);
    }
    
    if ($is_card_image) {
        $stmt = $conn->prepare("UPDATE couriers SET image='' WHERE id=?");
        $stmt->bind_param("i", $courier_id);
        $stmt->execute();
    } else {
        $stmt = $conn->prepare("SELECT image_detail FROM couriers WHERE id=?");
        $stmt->bind_param("i", $courier_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $courier = $result->fetch_assoc();
        
        if ($courier) {
            $images = array_filter(explode(',', $courier['image_detail']));
            $updated_images = array_diff($images, [$image_path]);
            $updated_images_str = implode(',', $updated_images);
            
            $stmt = $conn->prepare("UPDATE couriers SET image_detail=? WHERE id=?");
            $stmt->bind_param("si", $updated_images_str, $courier_id);
            $stmt->execute();
        }
    }
    
    $_SESSION['message'] = "Image deleted successfully";
    header("Location: add_couriers.php?edit=" . $courier_id);
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
    $mapEmbedURL = $_POST['map_embed'];
    $established = $_POST['established'] ?? '';
    $delivery_times = $_POST['delivery_times'] ?? '';
    $package_limits = $_POST['package_limits'] ?? '';
    $services = $_POST['services'] ?? '';
    $email = $_POST['email'] ?? '';
    $hotline = $_POST['hotline'] ?? '';
    $branches = $_POST['branches'] ?? '';
    $social_media = $_POST['social_media'] ?? '';
    $type = $_POST['type'] ?? '';
    $service_areas = $_POST['service_areas'] ?? '';
    $tracking_link = $_POST['tracking_link'] ?? '';
    $package_handling = $_POST['package_handling'] ?? '';
    $insurance = $_POST['insurance'] ?? '';
    $payment_options = $_POST['payment_options'] ?? '';
    $service_options = $_POST['service_options'] ?? '';
    $details_link = $_POST['details_link'] ?? '';

    $nearby_places = $_POST['nearby_places'] ?? '';
    $nearby_places_array = array_map('trim', explode(',', $nearby_places));
    $nearby_places_json = json_encode(array_filter($nearby_places_array)); 

    $transportation_routes = array();
    if (isset($_POST['route_type']) && is_array($_POST['route_type'])) {
        foreach ($_POST['route_type'] as $index => $type_route) {
            if (!empty($type_route) && !empty($_POST['route_name'][$index])) {
                $transportation_routes[] = array(
                    'type' => $type_route,
                    'name' => $_POST['route_name'][$index],
                    'link' => $_POST['route_link'][$index] ?? '',
                    'description' => $_POST['route_description'][$index] ?? ''
                );
            }
        }
    }
    $transportation_routes_json = json_encode($transportation_routes);
    
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
                header("Location: add_couriers.php" . ($id ? "?edit=$id" : ""));
                exit();
            }
        } else {
            $_SESSION['error'] = "Invalid file type. Only JPG, JPEG, PNG, GIF are allowed";
            header("Location: add_couriers.php" . ($id ? "?edit=$id" : ""));
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
            header("Location: add_couriers.php" . ($id ? "?edit=$id" : ""));
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
        $stmt = $conn->prepare("UPDATE couriers SET name=?, image=?, image_detail=?, description=?, location=?, maps_embed=?, maps_link=?, website=?, directions_link=?, hours=?, contact=?, type=?, established=?, delivery_times=?, package_limits=?, services=?, email=?, hotline=?, branches=?, social_media=?, service_areas=?, tracking_link=?, package_handling=?, insurance=?, payment_options=?, service_options=?, details_link=?, nearby_places=?, transportation_routes=? WHERE id=?");
        $stmt->bind_param("sssssssssssssssssssssssssssssi", $name, $target_file, $target_file_detail, $description, $location, $mapEmbedURL, $maps_link, $website, $directions_link, $hours, $contact, $type, $established, $delivery_times, $package_limits, $services, $email, $hotline, $branches, $social_media, $service_areas, $tracking_link, $package_handling, $insurance, $payment_options, $service_options, $details_link, $nearby_places_json, $transportation_routes_json, $id);
    } else {
        $stmt = $conn->prepare("INSERT INTO couriers (name, image, image_detail, description, location, maps_embed, maps_link, website, directions_link, hours, contact, type, established, delivery_times, package_limits, services, email, hotline, branches, social_media, service_areas, tracking_link, package_handling, insurance, payment_options, service_options, details_link, nearby_places, transportation_routes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssssssssssssssssssssssss", $name, $target_file, $target_file_detail, $description, $location, $mapEmbedURL, $maps_link, $website, $directions_link, $hours, $contact, $type, $established, $delivery_times, $package_limits, $services, $email, $hotline, $branches, $social_media, $service_areas, $tracking_link, $package_handling, $insurance, $payment_options, $service_options, $details_link, $nearby_places_json, $transportation_routes_json);
    }

    if ($stmt->execute()) {
        $_SESSION['message'] = "Courier " . ($id ? "updated" : "added") . " successfully";
    } else {
        $_SESSION['error'] = "Error saving courier: " . $stmt->error;
    }
    
    header("Location: add_couriers.php" . ($id ? "?edit=" . $id : ""));
    exit();
}

$query = "SELECT * FROM couriers ORDER BY name ASC";
$result = $conn->query($query);
$couriers = $result->fetch_all(MYSQLI_ASSOC);

$service_areas_str = '';
$nearby_places_str = '';
$transportation_routes = array();

if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $stmt = $conn->prepare("SELECT service_areas, nearby_places, transportation_routes FROM couriers WHERE id=?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $service_areas_str = $row['service_areas'];
        
        if (!empty($row['nearby_places'])) {
            $decoded_places = json_decode($row['nearby_places'], true);
            if (is_array($decoded_places)) {
                $nearby_places_str = implode(', ', $decoded_places);
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Couriers Management</title>
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
        .form-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid #dee2e6;
        }
        .form-section h5 {
            color: #495057;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #dee2e6;
        }
        .transport-routes-table {
            background: white;
            border-radius: 5px;
            overflow: hidden;
            border: 1px solid #dee2e6;
        }
        .transport-routes-table table {
            margin-bottom: 0;
        }
        .transport-routes-table th {
            background: #e9ecef;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
            color: #495057;
        }
        .transport-routes-table td {
            vertical-align: middle;
        }
        .existing-images {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }
        .image-container {
            position: relative;
            width: 100px;
            height: 100px;
            border-radius: 5px;
            overflow: hidden;
            border: 1px solid #dee2e6;
        }
        .image-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .image-container .delete-image {
            position: absolute;
            top: 5px;
            right: 5px;
            background: #dc3545;
            color: white;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-size: 12px;
            z-index: 10;
        }
        .image-container .delete-image:hover {
            background: #c82333;
        }
        .modal-backdrop.show {
            opacity: 0.8;
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
            <h1 class="text-light">Couriers Management</h1>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#courierModal">
                <i class="bi bi-plus-lg"></i> Add Courier
            </button>
        </div>
        
        <?php if (empty($couriers)): ?>
            <div class="alert alert-info">
                No couriers found. Click "Add Courier" to create one.
            </div>
        <?php else: ?>
            <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-4">
                <?php foreach ($couriers as $courier): ?>
                    <div class="col">
                        <div class="card h-100">
                            <?php if (!empty($courier['image'])): ?>
                                <img src="<?php echo $courier['image']; ?>" class="card-img-top" alt="<?php echo $courier['name']; ?>">
                            <?php else: ?>
                                <div class="card-img-top bg-secondary d-flex align-items-center justify-content-center">
                                    <i class="bi bi-truck text-white" style="font-size: 3rem;"></i>
                                </div>
                            <?php endif; ?>
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?php echo $courier['name']; ?></h5>
                                <p class="card-text flex-grow-1"><?php echo substr($courier['description'], 0, 100) . '...'; ?></p>
                                <div class="d-flex justify-content-between">
                                    <a href="add_couriers.php?edit=<?php echo $courier['id']; ?>" class="btn btn-primary btn-sm">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    <a href="?delete=<?php echo $courier['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this courier? All associated images will also be deleted.')">
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

    <div class="modal fade <?php echo isset($_GET['edit']) ? 'show d-block' : ''; ?>" id="courierModal" tabindex="-1" aria-labelledby="courierModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="courierModalLabel"><?php echo isset($_GET['edit']) ? 'Edit Courier' : 'Add Courier'; ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php 
                        $courierData = [
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
                            'delivery_times' => '',
                            'package_limits' => '',
                            'services' => '',
                            'email' => '',
                            'hotline' => '',
                            'branches' => '',
                            'social_media' => '',
                            'service_areas' => '',
                            'tracking_link' => '',
                            'package_handling' => '',
                            'insurance' => '',
                            'payment_options' => '',
                            'service_options' => '',
                            'details_link' => '',
                            'nearby_places' => ''
                        ];
                        
                        if (isset($_GET['edit'])) {
                            $edit_id = $_GET['edit'];
                            $stmt = $conn->prepare("SELECT * FROM couriers WHERE id=?");
                            $stmt->bind_param("i", $edit_id);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            if ($result->num_rows > 0) {
                                $courierData = $result->fetch_assoc();
                            }
                        }
                    ?>
                    <form method="POST" action="add_couriers.php" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="<?php echo $courierData['id']; ?>">
                        
                        <div class="form-section">
                            <h5>Basic Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Name *</label>
                                        <input type="text" class="form-control" name="name" value="<?php echo $courierData['name']; ?>" >
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Details Page Link:</label>
                                        <input type="text" class="form-control" name="details_link" value="<?php echo $courierData['details_link']; ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Description *</label>
                                        <textarea class="form-control" name="description" rows="4" ><?php echo $courierData['description']; ?></textarea>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Operating Hours</label>
                                        <textarea class="form-control" name="hours" rows="4" placeholder="Monday: 9AM - 5PM"><?php echo $courierData['hours']; ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Contact Information</label>
                                        <input type="text" class="form-control" name="contact" value="<?php echo $courierData['contact']; ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h5>Transportation Routes</h5>
                            <div class="transport-routes-table">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Transport Type</th>
                                            <th>Route Name/Number</th>
                                            <th>Route Link</th>
                                            <th>Description</th>
                                            <th style="width: 50px">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="transportRoutesTableBody">
                                        <?php if (!empty($transportation_routes)): ?>
                                            <?php foreach ($transportation_routes as $index => $route): ?>
                                                <tr>
                                                    <td>
                                                        <select class="form-control" name="route_type[]">
                                                            <option value="jeepney" <?php echo ($route['type'] == 'jeepney') ? 'selected' : ''; ?>>Jeepney</option>
                                                            <option value="bus" <?php echo ($route['type'] == 'bus') ? 'selected' : ''; ?>>Bus</option>
                                                            <option value="taxi" <?php echo ($route['type'] == 'taxi') ? 'selected' : ''; ?>>Taxi</option>
                                                            <option value="tricycle" <?php echo ($route['type'] == 'tricycle') ? 'selected' : ''; ?>>Tricycle</option>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control" name="route_name[]" value="<?php echo htmlspecialchars($route['name']); ?>">
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control" name="route_link[]" value="<?php echo htmlspecialchars($route['link'] ?? ''); ?>">
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control" name="route_description[]" value="<?php echo htmlspecialchars($route['description'] ?? ''); ?>">
                                                    </td>
                                                    <td>
                                                        <button type="button" class="btn btn-danger btn-sm" onclick="removeTransportRow(this)">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td>
                                                    <select class="form-control" name="route_type[]">
                                                        <option value="jeepney">Jeepney</option>
                                                        <option value="bus">Bus</option>
                                                        <option value="taxi">Taxi</option>
                                                        <option value="tricycle">Tricycle</option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control" name="route_name[]">
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control" name="route_link[]">
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control" name="route_description[]">
                                                </td>
                                                <td>
                                                    <button type="button" class="btn btn-danger btn-sm" onclick="removeTransportRow(this)">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <button type="button" class="btn btn-secondary btn-sm mt-2" onclick="addTransportRow()">
                                <i class="bi bi-plus"></i> Add Another Route
                            </button>
                        </div>
                        
                        <div class="form-section">
                            <h5>Images</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Card Image (Main Display Image)</label>
                                        <input type="file" class="form-control" name="image" accept="image/*">
                                        
                                        <?php if (!empty($courierData['image'])): ?>
                                            <div class="existing-images">
                                                <div class="image-container">
                                                    <img src="<?php echo $courierData['image']; ?>" alt="Card Image">
                                                    <a href="?delete_image=<?php echo urlencode($courierData['image']); ?>&courier_id=<?php echo $courierData['id']; ?>&is_card_image=1" 
                                                       class="delete-image" 
                                                       onclick="return confirm('Delete this image?')">×</a>
                                                </div>
                                            </div>
                                            <div class="form-check mt-2">
                                                <input class="form-check-input" type="checkbox" name="delete_image" value="1" id="deleteImageCheck">
                                                <label class="form-check-label" for="deleteImageCheck">Delete current image</label>
                                            </div>
                                            <input type="hidden" name="existing_image" value="<?php echo $courierData['image']; ?>">
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Detail Images (Max 5)</label>
                                        <input type="file" class="form-control" name="image_detail[]" multiple accept="image/*">
                                        <small class="text-muted">Upload additional images to display on the courier details page</small>
                                        
                                        <?php if (!empty($courierData['image_detail'])): ?>
                                            <div class="existing-images">
                                                <?php 
                                                $images = explode(',', $courierData['image_detail']);
                                                foreach ($images as $img): 
                                                    if (!empty($img)):
                                                ?>
                                                    <div class="image-container">
                                                        <img src="<?php echo $img; ?>" alt="Detail Image">
                                                        <a href="?delete_image=<?php echo urlencode($img); ?>&courier_id=<?php echo $courierData['id']; ?>" 
                                                           class="delete-image" 
                                                           onclick="return confirm('Delete this image?')">×</a>
                                                    </div>
                                                <?php 
                                                    endif;
                                                endforeach; 
                                                ?>
                                            </div>
                                            <input type="hidden" name="existing_image_detail" value="<?php echo $courierData['image_detail']; ?>">
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <h5>Service Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Service Type</label>
                                        <input type="text" class="form-control" name="type" value="<?php echo $courierData['type']; ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Established</label>
                                        <input type="text" class="form-control" name="established" value="<?php echo $courierData['established']; ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Delivery Times</label>
                                        <input type="text" class="form-control" name="delivery_times" value="<?php echo $courierData['delivery_times']; ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Package Limits</label>
                                        <input type="text" class="form-control" name="package_limits" value="<?php echo $courierData['package_limits']; ?>">
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Services</label>
                                        <textarea class="form-control" name="services" rows="3"><?php echo $courierData['services']; ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" name="email" value="<?php echo $courierData['email']; ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Hotline</label>
                                        <input type="text" class="form-control" name="hotline" value="<?php echo $courierData['hotline']; ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Service Areas</label>
                                <input name="service_areas" class="form-control" value="<?php echo $service_areas_str; ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Service Options (one per line)</label>
                                <textarea class="form-control" name="service_options" rows="3"><?php echo $courierData['service_options']; ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Branches (one per line)</label>
                                <textarea class="form-control" name="branches" rows="5"><?php echo $courierData['branches']; ?></textarea>
                            </div>
                        </div>

                        <div class="form-section">
                            <h5>Additional Features</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Package Tracking Link</label>
                                        <input type="url" class="form-control" name="tracking_link" value="<?php echo $courierData['tracking_link']; ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Package Handling</label>
                                        <input type="text" class="form-control" name="package_handling" value="<?php echo $courierData['package_handling']; ?>">
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Insurance</label>
                                        <input type="text" class="form-control" name="insurance" value="<?php echo $courierData['insurance']; ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Payment Options</label>
                                        <input type="text" class="form-control" name="payment_options" value="<?php echo $courierData['payment_options']; ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <h5>Social Media & Nearby Places</h5>
                            <div class="mb-3">
                                <label class="form-label">Social Media Links</label>
                                <textarea class="form-control" name="social_media" rows="5"><?php echo $courierData['social_media']; ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Nearby Places:</label>
                                <input name="nearby_places" class="form-control" value="<?php echo $nearby_places_str; ?>">
                                <small class="text-muted">Example: Park, Shopping Mall, Bike Trail</small>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h5>Location Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Address *</label>
                                        <input type="text" class="form-control" name="location" value="<?php echo $courierData['location']; ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Website URL</label>
                                        <input type="url" class="form-control" name="website" value="<?php echo $courierData['website']; ?>">
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Google Maps Directions Link</label>
                                        <input type="url" class="form-control" name="directions_link" value="<?php echo $courierData['directions_link']; ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Google Map Link</label>
                                        <input type="url" class="form-control" name="maps_link" value="<?php echo $courierData['maps_link']; ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Embed Google Map URL</label>
                                <input type="url" class="form-control" name="map_embed" value="<?php echo $courierData['maps_embed']; ?>">
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <?php echo isset($_GET['edit']) ? 'Update Courier' : 'Save Courier'; ?>
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
                var modal = new bootstrap.Modal(document.getElementById('courierModal'));
                modal.show();
                
                history.replaceState(null, null, window.location.pathname);
            }
            
            var courierModal = document.getElementById('courierModal');
            if (courierModal) {
                courierModal.addEventListener('hidden.bs.modal', function () {
                    var backdrops = document.getElementsByClassName('modal-backdrop');
                    for (var i = 0; i < backdrops.length; i++) {
                        backdrops[i].remove();
                    }
                });
            }
        });

        function addTransportRow() {
            const tableBody = document.getElementById('transportRoutesTableBody');
            const newRow = document.createElement('tr');
            
            newRow.innerHTML = `
                <td>
                    <select class="form-control" name="route_type[]">
                        <option value="jeepney">Jeepney</option>
                        <option value="bus">Bus</option>
                        <option value="taxi">Taxi</option>
                        <option value="tricycle">Tricycle</option>
                    </select>
                </td>
                <td>
                    <input type="text" class="form-control" name="route_name[]">
                </td>
                <td>
                    <input type="text" class="form-control" name="route_link[]">
                </td>
                <td>
                    <input type="text" class="form-control" name="route_description[]">
                </td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeTransportRow(this)">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            `;
            
            tableBody.appendChild(newRow);
        }

        function removeTransportRow(button) {
            const row = button.closest('tr');
            if (document.querySelectorAll('#transportRoutesTableBody tr').length > 1) {
                row.remove();
            } else {
                const inputs = row.querySelectorAll('input, select');
                inputs.forEach(input => {
                    if (input.tagName === 'INPUT') {
                        input.value = '';
                    } else if (input.tagName === 'SELECT') {
                        input.selectedIndex = 0;
                    }
                });
            }
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>