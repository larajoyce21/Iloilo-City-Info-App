<?php
session_start();
require 'conn.php';

if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    
    $stmt = $conn->prepare("SELECT image, image_detail FROM construction_firms WHERE id=?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $firm = $result->fetch_assoc();
    
    $stmt = $conn->prepare("DELETE FROM construction_firms WHERE id=?");
    $stmt->bind_param("i", $delete_id);
    
    if ($stmt->execute()) {
        if ($firm) {
            if (!empty($firm['image'])) {
                $image_path = 'uploads/' . basename($firm['image']);
                if (file_exists($image_path)) {
                    unlink($image_path);
                }
            }
            
            if (!empty($firm['image_detail'])) {
                $images = explode(',', $firm['image_detail']);
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
        
        $_SESSION['message'] = "Construction firm deleted successfully";
    } else {
        $_SESSION['error'] = "Error deleting construction firm";
    }
    
    header("Location: add_construction_firms.php");
    exit();
}

if (isset($_GET['delete_image']) && isset($_GET['firm_id'])) {
    $image_path = $_GET['delete_image'];
    $firm_id = intval($_GET['firm_id']);
    $is_card_image = isset($_GET['is_card_image']) ? true : false;
    
    $real_image_path = 'uploads/' . basename($image_path);
    
    if (file_exists($real_image_path)) {
        unlink($real_image_path);
    }
    
    if ($is_card_image) {
        $stmt = $conn->prepare("UPDATE construction_firms SET image='' WHERE id=?");
        $stmt->bind_param("i", $firm_id);
        $stmt->execute();
    } else {
        $stmt = $conn->prepare("SELECT image_detail FROM construction_firms WHERE id=?");
        $stmt->bind_param("i", $firm_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $firm = $result->fetch_assoc();
        
        if ($firm) {
            $images = array_filter(explode(',', $firm['image_detail']));
            $updated_images = array_diff($images, [$image_path]);
            $updated_images_str = implode(',', $updated_images);
            
            $stmt = $conn->prepare("UPDATE construction_firms SET image_detail=? WHERE id=?");
            $stmt->bind_param("si", $updated_images_str, $firm_id);
            $stmt->execute();
        }
    }
    
    $_SESSION['message'] = "Image deleted successfully";
    header("Location: add_construction_firms.php?edit=" . $firm_id);
    exit();
}

$query = "SELECT id, name, image, description, location, maps_embed, directions_link, maps_link, website, hours, contact, type, established, specializations, equipment, services, email, emergency_contact, projects, social_media, nearby_places, details_link, license_number, portfolio_link, parking, delivery_access, transportation_routes FROM construction_firms ORDER BY name ASC";
$result = $conn->query($query);
$firms = $result->fetch_all(MYSQLI_ASSOC);

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
    $specializations = $_POST['specializations'] ?? '';
    $equipment = $_POST['equipment'] ?? '';
    $services = $_POST['services'] ?? '';
    $email = $_POST['email'] ?? '';
    $emergency_contact = $_POST['emergency_contact'] ?? '';
    $projects = $_POST['projects'] ?? '';
    $social_media = $_POST['social_media'] ?? '';
    $type = $_POST['type'] ?? '';
    $details_link = $_POST['details_link'] ?? '';
    $license_number = $_POST['license_number'] ?? '';
    $portfolio_link = $_POST['portfolio_link'] ?? '';
    $parking = $_POST['parking'] ?? '';
    $delivery_access = $_POST['delivery_access'] ?? '';

    $nearby_places = $_POST['nearby_places'] ?? '';
    $nearby_places_array = array_map('trim', explode(',', $nearby_places));
    $nearby_places_json = json_encode(array_filter($nearby_places_array));

    $services_array = array_map('trim', explode(',', $services));
    $services_json = json_encode(array_filter($services_array));

    $specializations_array = array_map('trim', explode(',', $specializations));
    $specializations_json = json_encode(array_filter($specializations_array));

    $equipment_array = array_map('trim', explode(',', $equipment));
    $equipment_json = json_encode(array_filter($equipment_array));

    // Handle transportation routes
    $transportation_routes = array();
    if (isset($_POST['route_type']) && is_array($_POST['route_type'])) {
        foreach ($_POST['route_type'] as $index => $route_type) {
            if (!empty($route_type) && !empty($_POST['route_name'][$index])) {
                $transportation_routes[] = array(
                    'type' => $route_type,
                    'name' => $_POST['route_name'][$index],
                    'link' => $_POST['route_link'][$index] ?? '',
                    'description' => $_POST['route_description'][$index] ?? ''
                );
            }
        }
    }
    $transportation_routes_json = json_encode($transportation_routes);

    $target_dir = "uploads/";
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
    $target_file = $_POST['existing_image'] ?? '';
    
    if (isset($_FILES['image']['name']) && $_FILES['image']['name']) {
        $file = $_FILES['image'];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (in_array($file_ext, $allowed_types)) {
            $new_filename = uniqid('img_', true) . '.' . $file_ext;
            $target_file = $target_dir . $new_filename;
            
            if (move_uploaded_file($file['tmp_name'], $target_file)) {
                if (!empty($_POST['existing_image']) && file_exists($_POST['existing_image'])) {
                    unlink($_POST['existing_image']);
                }
            } else {
                $_SESSION['error'] = "Error uploading the main image";
                header("Location: add_construction_firms.php" . ($id ? "?edit=$id" : ""));
                exit();
            }
        } else {
            $_SESSION['error'] = "Invalid file type. Only JPG, JPEG, PNG, GIF are allowed";
            header("Location: add_construction_firms.php" . ($id ? "?edit=$id" : ""));
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
            header("Location: add_construction_firms.php" . ($id ? "?edit=$id" : ""));
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
                $targetPath = $target_dir . $new_filename;
                
                if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                    $imagePaths[] = $targetPath;
                }
            }
        }
    }
    
    $allImages = array_merge($existingImages, $imagePaths);
    $target_file_detail = implode(',', $allImages);
    
    if ($id) {
        $stmt = $conn->prepare("UPDATE construction_firms SET name=?, image=?, image_detail=?, description=?, location=?, maps_embed=?, maps_link=?, website=?, directions_link=?, hours=?, contact=?, type=?, established=?, specializations=?, equipment=?, services=?, email=?, emergency_contact=?, projects=?, social_media=?, nearby_places=?, details_link=?, license_number=?, portfolio_link=?, parking=?, delivery_access=?, transportation_routes=? WHERE id=?");
        $stmt->bind_param("sssssssssssssssssssssssssssi", $name, $target_file, $target_file_detail, $description, $location, $mapEmbedURL, $maps_link, $website, $directions_link, $hours, $contact, $type, $established, $specializations_json, $equipment_json, $services_json, $email, $emergency_contact, $projects, $social_media, $nearby_places_json, $details_link, $license_number, $portfolio_link, $parking, $delivery_access, $transportation_routes_json, $id);
    } else {
        $stmt = $conn->prepare("INSERT INTO construction_firms (name, image, image_detail, description, location, maps_embed, maps_link, website, directions_link, hours, contact, type, established, specializations, equipment, services, email, emergency_contact, projects, social_media, nearby_places, details_link, license_number, portfolio_link, parking, delivery_access, transportation_routes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssssssssssssssssssssss", $name, $target_file, $target_file_detail, $description, $location, $mapEmbedURL, $maps_link, $website, $directions_link, $hours, $contact, $type, $established, $specializations_json, $equipment_json, $services_json, $email, $emergency_contact, $projects, $social_media, $nearby_places_json, $details_link, $license_number, $portfolio_link, $parking, $delivery_access, $transportation_routes_json);
    }

    if ($stmt->execute()) {
        $_SESSION['message'] = "Construction firm " . ($id ? "updated" : "added") . " successfully";
    } else {
        $_SESSION['error'] = "Error saving construction firm: " . $stmt->error;
    }
    
    header("Location: add_construction_firms.php");
    exit();
}

$specializations_str = '';
$equipment_str = '';
$services_str = '';
$nearby_places_str = '';
$transportation_routes = array();

if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT specializations, equipment, services, nearby_places, transportation_routes FROM construction_firms WHERE id=?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (!empty($row['specializations'])) {
            $decoded = json_decode($row['specializations'], true);
            if (is_array($decoded)) {
                $specializations_str = implode(', ', $decoded);
            }
        }
        if (!empty($row['equipment'])) {
            $decoded = json_decode($row['equipment'], true);
            if (is_array($decoded)) {
                $equipment_str = implode(', ', $decoded);
            }
        }
        if (!empty($row['services'])) {
            $decoded = json_decode($row['services'], true);
            if (is_array($decoded)) {
                $services_str = implode(', ', $decoded);
            }
        }
        if (!empty($row['nearby_places'])) {
            $decoded = json_decode($row['nearby_places'], true);
            if (is_array($decoded)) {
                $nearby_places_str = implode(', ', $decoded);
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
    <title>Construction Firms Management</title>
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
        .transport-table-container {
            overflow-x: auto;
        }
        .route-row {
            vertical-align: middle;
        }
        .action-buttons {
            white-space: nowrap;
        }
        .transport-icon {
            width: 30px;
            height: 30px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            margin-right: 8px;
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
            <h1 class="text-light">Construction Firms Management</h1>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#firmModal">
                <i class="bi bi-plus-lg"></i> Add Construction Firm
            </button>
        </div>
        
        <?php if (empty($firms)): ?>
            <div class="alert alert-info">
                No construction firms found. Click "Add Construction Firm" to create one.
            </div>
        <?php else: ?>
            <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-4">
                <?php foreach ($firms as $firm): ?>
                    <div class="col">
                        <div class="card h-100">
                            <?php if (!empty($firm['image'])): ?>
                                <img src="<?php echo $firm['image']; ?>" class="card-img-top" alt="<?php echo $firm['name']; ?>">
                            <?php else: ?>
                                <div class="card-img-top bg-secondary d-flex align-items-center justify-content-center">
                                    <i class="bi bi-building text-white" style="font-size: 3rem;"></i>
                                </div>
                            <?php endif; ?>
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?php echo $firm['name']; ?></h5>
                                <p class="card-text flex-grow-1"><?php echo substr($firm['description'], 0, 100) . '...'; ?></p>
                                <div class="d-flex justify-content-between">
                                    <a href="add_construction_firms.php?edit=<?php echo $firm['id']; ?>" class="btn btn-primary btn-sm">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    <a href="?delete=<?php echo $firm['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this construction firm? All associated images will also be deleted.')">
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

    <div class="modal fade <?php echo isset($_GET['edit']) ? 'show d-block' : ''; ?>" id="firmModal" tabindex="-1" aria-labelledby="firmModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="firmModalLabel"><?php echo isset($_GET['edit']) ? 'Edit Construction Firm' : 'Add Construction Firm'; ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php 
                        $firmData = [
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
                            'specializations' => '',
                            'equipment' => '',
                            'services' => '',
                            'email' => '',
                            'emergency_contact' => '',
                            'projects' => '',
                            'social_media' => '',
                            'nearby_places' => '',
                            'details_link' => '',
                            'license_number' => '',
                            'portfolio_link' => '',
                            'parking' => '',
                            'delivery_access' => '',
                            'transportation_routes' => ''
                        ];
                        
                        if (isset($_GET['edit'])) {
                            $edit_id = (int)$_GET['edit'];
                            $stmt = $conn->prepare("SELECT * FROM construction_firms WHERE id=?");
                            $stmt->bind_param("i", $edit_id);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            if ($result->num_rows > 0) {
                                $firmData = $result->fetch_assoc();
                            }
                        }
                    ?>
                    <form method="POST" action="add_construction_firms.php" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="<?php echo $firmData['id']; ?>">
                        
                        <div class="form-section">
                            <h5>Basic Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Name *</label>
                                        <input type="text" class="form-control" name="name" value="<?php echo $firmData['name']; ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Details Page Link:</label>
                                        <input type="text" class="form-control" name="details_link" value="<?php echo htmlspecialchars($firmData['details_link']); ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Description *</label>
                                        <textarea class="form-control" name="description" rows="4"><?php echo $firmData['description']; ?></textarea>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Operating Hours</label>
                                        <textarea class="form-control" name="hours" rows="4" placeholder="Monday: 9AM - 5PM"><?php echo $firmData['hours']; ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Contact Information</label>
                                        <input type="text" class="form-control" name="contact" value="<?php echo $firmData['contact']; ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h5>Construction Specific Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Firm Type</label>
                                        <input type="text" class="form-control" name="type" value="<?php echo $firmData['type']; ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Established</label>
                                        <input type="text" class="form-control" name="established" value="<?php echo $firmData['established']; ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">License Number</label>
                                        <input type="text" class="form-control" name="license_number" value="<?php echo $firmData['license_number']; ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Specializations</label>
                                        <input name="specializations" class="form-control" value="<?= $specializations_str; ?>">
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Equipment</label>
                                        <input name="equipment" class="form-control" value="<?= $equipment_str; ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Services</label>
                                        <input name="services" class="form-control" value="<?= $services_str; ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Portfolio Link</label>
                                        <input type="url" class="form-control" name="portfolio_link" value="<?php echo $firmData['portfolio_link']; ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Projects (one per line)</label>
                                <textarea class="form-control" name="projects" rows="5"><?php echo htmlspecialchars($firmData['projects']); ?></textarea>
                                <small class="text-muted">Enter each project on a separate line</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Nearby Places:</label>
                                <input name="nearby_places" class="form-control" value="<?= $nearby_places_str; ?>">
                                <small class="text-muted">Example: Hardware Store, Material Supplier, Restaurant, Bank</small>
                            </div>
                        </div>

                        <div class="form-section">
                            <h5>Transportation Routes</h5>
                            <div class="transport-table-container">
                                <table class="table table-bordered" id="transportTable">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Transport Type</th>
                                            <th>Route Name/Number</th>
                                            <th>Route Link</th>
                                            <th>Description</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="transportTableBody">
                                        <?php if (!empty($transportation_routes)): ?>
                                            <?php foreach ($transportation_routes as $index => $route): ?>
                                                <tr class="route-row" data-index="<?php echo $index; ?>">
                                                    <td><?php echo $index + 1; ?></td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <span class="transport-icon <?php echo $route['type']; ?>-icon">
                                                                <?php 
                                                                    $icon_map = [
                                                                        'jeepney' => '',
                                                                        'bus' => '',
                                                                        'taxi' => '',
                                                                        'tricycle' => ''
                                                                    ];
                                                                    echo $icon_map[$route['type']] ?? '';
                                                                ?>
                                                            </span>
                                                            <select class="form-control form-control-sm" name="route_type[]">
                                                                <option value="jeepney" <?php echo ($route['type'] == 'jeepney') ? 'selected' : ''; ?>>Jeepney</option>
                                                                <option value="bus" <?php echo ($route['type'] == 'bus') ? 'selected' : ''; ?>>Bus</option>
                                                                <option value="taxi" <?php echo ($route['type'] == 'taxi') ? 'selected' : ''; ?>>Taxi</option>
                                                                <option value="tricycle" <?php echo ($route['type'] == 'tricycle') ? 'selected' : ''; ?>>Tricycle</option>
                                                            </select>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control form-control-sm" name="route_name[]" value="<?php echo $route['name']; ?>">
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control form-control-sm" name="route_link[]" value="<?php echo $route['link'] ?? ''; ?>">
                                                    </td>
                                                    <td>
                                                        <textarea class="form-control form-control-sm" name="route_description[]" rows="1"><?php echo $route['description'] ?? ''; ?></textarea>
                                                    </td>
                                                    <td class="action-buttons">
                                                        <button type="button" class="btn btn-danger btn-sm" onclick="removeTransportRow(this)">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr class="route-row" data-index="0">
                                                <td>1</td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <span class="transport-icon jeepney-icon"></span>
                                                        <select class="form-control form-control-sm" name="route_type[]">
                                                            <option value="jeepney">Jeepney</option>
                                                            <option value="bus">Bus</option>
                                                            <option value="taxi">Taxi</option>
                                                            <option value="tricycle">Tricycle</option>
                                                        </select>
                                                    </div>
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control form-control-sm" name="route_name[]">
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control form-control-sm" name="route_link[]">
                                                </td>
                                                <td>
                                                    <textarea class="form-control form-control-sm" name="route_description[]" rows="1"></textarea>
                                                </td>
                                                <td class="action-buttons">
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
                            <div class="mb-3">
                                <label class="form-label">Card Image (Main Display Image)</label>
                                <input type="file" class="form-control" name="image" accept="image/*">
                                
                                <?php if (!empty($firmData['image'])): ?>
                                    <div class="mt-3">
                                        <p>Current Image:</p>
                                        <img src="<?php echo $firmData['image']; ?>" class="img-thumbnail card-image-preview">
                                        <div class="form-check mt-2">
                                            <input class="form-check-input" type="checkbox" name="delete_image" value="1" id="deleteImageCheck">
                                            <label class="form-check-label" for="deleteImageCheck">Delete current image</label>
                                        </div>
                                        <input type="hidden" name="existing_image" value="<?php echo $firmData['image']; ?>">
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Detail Images (Max 5)</label>
                                <input type="file" class="form-control" name="image_detail[]" multiple accept="image/*">
                                <small class="text-muted">Upload additional images to display on the construction firm details page</small>
                                
                                <?php if (!empty($firmData['image_detail'])): ?>
                                    <div class="mt-3">
                                        <p>Current Detail Images:</p>
                                        <div class="d-flex flex-wrap gap-2">
                                            <?php 
                                            $images = explode(',', $firmData['image_detail']);
                                            foreach ($images as $img): 
                                                if (!empty($img)):
                                            ?>
                                                <div class="position-relative">
                                                    <img src="<?php echo $img; ?>" class="img-thumbnail" style="width: 100px; height: 100px; object-fit: cover;">
                                                    <a href="?delete_image=<?php echo urlencode($img); ?>&firm_id=<?php echo $firmData['id']; ?>" 
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
                                        <input type="hidden" name="existing_image_detail" value="<?php echo $firmData['image_detail']; ?>">
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="form-section">
                            <h5>Contact & Location Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" name="email" value="<?php echo $firmData['email']; ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Emergency Contact</label>
                                        <input type="text" class="form-control" name="emergency_contact" value="<?php echo $firmData['emergency_contact']; ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Parking Information</label>
                                        <input type="text" class="form-control" name="parking" value="<?php echo $firmData['parking']; ?>">
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Delivery Access</label>
                                        <input type="text" class="form-control" name="delivery_access" value="<?php echo $firmData['delivery_access']; ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Address *</label>
                                        <input type="text" class="form-control" name="location" value="<?php echo $firmData['location']; ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Website URL</label>
                                        <input type="url" class="form-control" name="website" value="<?php echo $firmData['website']; ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Google Maps Directions Link</label>
                                        <input type="url" class="form-control" name="directions_link" value="<?php echo $firmData['directions_link']; ?>" maxlength="255">
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Google Map Link</label>
                                        <input type="url" class="form-control" name="maps_link" value="<?php echo $firmData['maps_link']; ?>" maxlength="255">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Embed Google Map URL</label>
                                <input type="url" class="form-control" name="map_embed" value="<?php echo $firmData['maps_embed']; ?>" maxlength="1000">
                                <small class="text-muted">Use the "Share" > "Embed a map" option from Google Maps</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Social Media Links</label>
                                <textarea class="form-control" name="social_media" rows="5"><?php echo $firmData['social_media']; ?></textarea>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <?php echo isset($_GET['edit']) ? 'Update Construction Firm' : 'Save Construction Firm'; ?>
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
                var modal = new bootstrap.Modal(document.getElementById('firmModal'));
                modal.show();
                
                history.replaceState(null, null, window.location.pathname);
            }
            
            var firmModal = document.getElementById('firmModal');
            if (firmModal) {
                firmModal.addEventListener('hidden.bs.modal', function () {
                    var backdrops = document.getElementsByClassName('modal-backdrop');
                    for (var i = 0; i < backdrops.length; i++) {
                        backdrops[i].remove();
                    }
                });
            }
        });

        function updateTransportIcons() {
            const rows = document.querySelectorAll('#transportTableBody .route-row');
            rows.forEach((row, index) => {
                const rowNumber = row.querySelector('td:first-child');
                rowNumber.textContent = index + 1;
                
                const select = row.querySelector('select[name="route_type[]"]');
                const iconSpan = row.querySelector('.transport-icon');
                
                const iconMap = {
                    'jeepney': '',
                    'bus': '',
                    'taxi': '',
                    'tricycle': ''
                };
                
                const type = select.value;
                iconSpan.className = `transport-icon ${type}-icon`;
                iconSpan.textContent = iconMap[type] || '';
            });
        }

        function addTransportRow() {
            const tbody = document.getElementById('transportTableBody');
            const rows = tbody.querySelectorAll('.route-row');
            const newIndex = rows.length;
            
            const newRow = document.createElement('tr');
            newRow.className = 'route-row';
            newRow.dataset.index = newIndex;
            
            newRow.innerHTML = `
                <td>${newIndex + 1}</td>
                <td>
                    <div class="d-flex align-items-center">
                        <span class="transport-icon jeepney-icon"></span>
                        <select class="form-control form-control-sm" name="route_type[]">
                            <option value="jeepney">Jeepney</option>
                            <option value="bus">Bus</option>
                            <option value="taxi">Taxi</option>
                            <option value="tricycle">Tricycle</option>
                        </select>
                    </div>
                </td>
                <td>
                    <input type="text" class="form-control form-control-sm" name="route_name[]">
                </td>
                <td>
                    <input type="text" class="form-control form-control-sm" name="route_link[]">
                </td>
                <td>
                    <textarea class="form-control form-control-sm" name="route_description[]" rows="1"></textarea>
                </td>
                <td class="action-buttons">
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeTransportRow(this)">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            `;
            
            tbody.appendChild(newRow);
            
            const select = newRow.querySelector('select[name="route_type[]"]');
            select.addEventListener('change', function() {
                updateTransportIcons();
            });
        }

        function removeTransportRow(button) {
            const row = button.closest('.route-row');
            row.remove();
            updateTransportIcons();
        }

        document.addEventListener('DOMContentLoaded', function() {
            const selects = document.querySelectorAll('select[name="route_type[]"]');
            selects.forEach(select => {
                select.addEventListener('change', function() {
                    updateTransportIcons();
                });
            });
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>