<?php
session_start();
require 'conn.php';

$allowed_types = ['jpg', 'jpeg', 'png', 'gif'];

if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    
    $stmt = $conn->prepare("SELECT image, image_detail FROM banks WHERE id=?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $bank = $result->fetch_assoc();
    
    $stmt = $conn->prepare("DELETE FROM banks WHERE id=?");
    $stmt->bind_param("i", $delete_id);
    
    if ($stmt->execute()) {
        if ($bank) {
            if (!empty($bank['image'])) {
                $image_path = 'uploads/' . basename($bank['image']);
                if (file_exists($image_path)) {
                    unlink($image_path);
                }
            }
            
            if (!empty($bank['image_detail'])) {
                $images = explode(',', $bank['image_detail']);
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
        
        $_SESSION['message'] = "Bank deleted successfully";
    } else {
        $_SESSION['error'] = "Error deleting bank";
    }
    
    header("Location: add_banks.php");
    exit();
}

if (isset($_GET['delete_image']) && isset($_GET['bank_id'])) {
    $image_path = $_GET['delete_image'];
    $bank_id = intval($_GET['bank_id']);
    $is_card_image = isset($_GET['is_card_image']) ? true : false;
    
    $real_image_path = 'uploads/' . basename($image_path);
    
    if (file_exists($real_image_path)) {
        unlink($real_image_path);
    }
    
    if ($is_card_image) {
        $stmt = $conn->prepare("UPDATE banks SET image='' WHERE id=?");
        $stmt->bind_param("i", $bank_id);
        $stmt->execute();
    } else {
        $stmt = $conn->prepare("SELECT image_detail FROM banks WHERE id=?");
        $stmt->bind_param("i", $bank_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $bank = $result->fetch_assoc();
        
        if ($bank) {
            $images = array_filter(explode(',', $bank['image_detail']));
            $updated_images = array_diff($images, [$image_path]);
            $updated_images_str = implode(',', $updated_images);
            
            $stmt = $conn->prepare("UPDATE banks SET image_detail=? WHERE id=?");
            $stmt->bind_param("si", $updated_images_str, $bank_id);
            $stmt->execute();
        }
    }
    
    $_SESSION['message'] = "Image deleted successfully";
    header("Location: add_banks.php?edit=" . $bank_id);
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
    $atm = $_POST['atm'] ?? '';
    $accessibility = $_POST['accessibility'] ?? '';
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

    if (empty($name) || empty($description) || empty($location)) {
        $_SESSION['error'] = "Please fill in all required fields";
        header("Location: add_banks.php" . ($id ? "?edit=$id" : ""));
        exit();
    }
    
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
                header("Location: add_banks.php" . ($id ? "?edit=$id" : ""));
                exit();
            }
        } else {
            $_SESSION['error'] = "Invalid file type. Only JPG, JPEG, PNG, GIF are allowed";
            header("Location: add_banks.php" . ($id ? "?edit=$id" : ""));
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
        $max_detail_images = 10;
        
        if ($totalCombined > $max_detail_images) {
            $_SESSION['error'] = "You can upload a maximum of $max_detail_images detail images total";
            header("Location: add_banks.php" . ($id ? "?edit=$id" : ""));
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
        $stmt = $conn->prepare("UPDATE banks SET name=?, image=?, image_detail=?, description=?, location=?, maps_embed=?, maps_link=?, website=?, directions_link=?, hours=?, contact=?, type=?, established=?, atm=?, accessibility=?, services=?, email=?, hotline=?, branches=?, social_media=?, nearby_places=?, details_link=?, transportation_routes=? WHERE id=?");
        $stmt->bind_param("sssssssssssssssssssssssi", $name, $target_file, $target_file_detail, $description, $location, $mapEmbedURL, $maps_link, $website, $directions_link, $hours, $contact, $type, $established, $atm, $accessibility, $services, $email, $hotline, $branches, $social_media, $nearby_places, $details_link, $transportation_routes_json, $id);
    } else {
        $stmt = $conn->prepare("INSERT INTO banks (name, image, image_detail, description, location, maps_embed, maps_link, website, directions_link, hours, contact, type, established, atm, accessibility, services, email, hotline, branches, social_media, nearby_places, details_link, transportation_routes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssssssssssssssssss", $name, $target_file, $target_file_detail, $description, $location, $mapEmbedURL, $maps_link, $website, $directions_link, $hours, $contact, $type, $established, $atm, $accessibility, $services, $email, $hotline, $branches, $social_media, $nearby_places, $details_link, $transportation_routes_json);
    }

    if ($stmt->execute()) {
        $_SESSION['message'] = "Bank " . ($id ? "updated" : "added") . " successfully";
    } else {
        $_SESSION['error'] = "Error saving bank: " . $stmt->error;
    }
    
    header("Location: add_banks.php");
    exit();
}

$query = "SELECT * FROM banks ORDER BY name ASC";
$result = $conn->query($query);
$banks = $result->fetch_all(MYSQLI_ASSOC);

$bankData = [
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
    'atm' => '',
    'accessibility' => '',
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

if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM banks WHERE id=?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $bankData = $result->fetch_assoc();
        
        if (!empty($bankData['transportation_routes'])) {
            $decoded_routes = json_decode($bankData['transportation_routes'], true);
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
    <title>Banks Management</title>
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
            <h1 class="text-light">Banks Management</h1>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#bankModal">
                <i class="bi bi-plus-lg"></i> Add Bank
            </button>
        </div>
        
        <?php if (empty($banks)): ?>
            <div class="alert alert-info">
                No banks found. Click "Add Bank" to create one.
            </div>
        <?php else: ?>
            <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-4">
                <?php foreach ($banks as $bank): ?>
                    <div class="col">
                        <div class="card h-100">
                            <?php if (!empty($bank['image'])): ?>
                                <img src="<?php echo $bank['image']; ?>" class="card-img-top" alt="<?php echo $bank['name']; ?>">
                            <?php else: ?>
                                <div class="card-img-top bg-secondary d-flex align-items-center justify-content-center">
                                    <i class="bi bi-bank text-white" style="font-size: 3rem;"></i>
                                </div>
                            <?php endif; ?>
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?php echo $bank['name']; ?></h5>
                                <p class="card-text flex-grow-1"><?php echo substr($bank['description'], 0, 100) . '...'; ?></p>
                                <div class="d-flex justify-content-between">
                                    <a href="add_banks.php?edit=<?php echo $bank['id']; ?>" class="btn btn-primary btn-sm">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    <a href="?delete=<?php echo $bank['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this bank? All associated images will also be deleted.')">
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

    <div class="modal fade <?php echo isset($_GET['edit']) ? 'show d-block' : ''; ?>" id="bankModal" tabindex="-1" aria-labelledby="bankModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="bankModalLabel"><?php echo isset($_GET['edit']) ? 'Edit Bank' : 'Add Bank'; ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="add_banks.php" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="<?php echo $bankData['id']; ?>">
                        
                        <ul class="nav nav-tabs mb-3" id="bankTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="basic-tab" data-bs-toggle="tab" data-bs-target="#basic" type="button" role="tab">Basic Information</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="images-tab" data-bs-toggle="tab" data-bs-target="#images" type="button" role="tab">Images</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="additional-tab" data-bs-toggle="tab" data-bs-target="#additional" type="button" role="tab">Additional Info</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="transport-tab" data-bs-toggle="tab" data-bs-target="#transport" type="button" role="tab">Transportation</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="location-tab" data-bs-toggle="tab" data-bs-target="#location" type="button" role="tab">Location</button>
                            </li>
                        </ul>
                        
                        <div class="tab-content" id="bankTabsContent">
                            <div class="tab-pane fade show active" id="basic" role="tabpanel">
                                <div class="form-section">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Name *</label>
                                                <input type="text" class="form-control" name="name" value="<?php echo $bankData['name']; ?>" required maxlength="100">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Details Page Link:</label>
                                                <input type="text" class="form-control" name="details_link" value="<?php echo htmlspecialchars($bankData['details_link']); ?>" placeholder="e.g., historical_details.php?id=1">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Operating Hours</label>
                                                <textarea class="form-control" name="hours" rows="4" placeholder="e.g., Monday: 9AM - 5PM"><?php echo $bankData['hours']; ?></textarea>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Contact Information</label>
                                                <input type="text" class="form-control" name="contact" value="<?php echo $bankData['contact']; ?>" placeholder="Phone, email, social media" maxlength="100">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Description *</label>
                                        <textarea class="form-control" name="description" rows="6" required maxlength="2000"><?php echo $bankData['description']; ?></textarea>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Images Tab -->
                            <div class="tab-pane fade" id="images" role="tabpanel">
                                <div class="form-section">
                                    <div class="mb-3">
                                        <label class="form-label">Card Image (Main Display Image)</label>
                                        <input type="file" class="form-control" name="image" accept="image/*">
                                        
                                        <?php if (!empty($bankData['image'])): ?>
                                            <div class="mt-3">
                                                <p>Current Image:</p>
                                                <img src="<?php echo $bankData['image']; ?>" class="img-thumbnail card-image-preview">
                                                <div class="form-check mt-2">
                                                    <input class="form-check-input" type="checkbox" name="delete_image" value="1" id="deleteImageCheck">
                                                    <label class="form-check-label" for="deleteImageCheck">Delete current image</label>
                                                </div>
                                                <input type="hidden" name="existing_image" value="<?php echo $bankData['image']; ?>">
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Detail Images (Max 10)</label>
                                        <input type="file" class="form-control" name="image_detail[]" multiple accept="image/*">
                                        <small class="text-muted">Upload additional images to display on the bank details page</small>
                                        
                                        <?php if (!empty($bankData['image_detail'])): ?>
                                            <div class="mt-3">
                                                <p>Current Detail Images:</p>
                                                <div class="d-flex flex-wrap gap-2">
                                                    <?php 
                                                    $images = explode(',', $bankData['image_detail']);
                                                    foreach ($images as $img): 
                                                        if (!empty($img)):
                                                    ?>
                                                        <div class="position-relative">
                                                            <img src="<?php echo $img; ?>" class="img-thumbnail" style="width: 100px; height: 100px; object-fit: cover;">
                                                            <a href="?delete_image=<?php echo urlencode($img); ?>&bank_id=<?php echo $bankData['id']; ?>" 
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
                                                <input type="hidden" name="existing_image_detail" value="<?php echo $bankData['image_detail']; ?>">
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Additional Information Tab -->
                            <div class="tab-pane fade" id="additional" role="tabpanel">
                                <div class="form-section">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Type</label>
                                                <input type="text" class="form-control" name="type" value="<?php echo $bankData['type']; ?>">
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Established</label>
                                                <input type="text" class="form-control" name="established" value="<?php echo $bankData['established']; ?>">
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">ATM Availability</label>
                                                <input type="text" class="form-control" name="atm" value="<?php echo $bankData['atm']; ?>">
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Accessibility</label>
                                                <input type="text" class="form-control" name="accessibility" value="<?php echo $bankData['accessibility']; ?>">
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Email</label>
                                                <input type="email" class="form-control" name="email" value="<?php echo $bankData['email']; ?>">
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Services</label>
                                                <textarea class="form-control" name="services" rows="3"><?php echo $bankData['services']; ?></textarea>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Hotline</label>
                                                <input type="text" class="form-control" name="hotline" value="<?php echo $bankData['hotline']; ?>">
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Branches (one per line)</label>
                                                <textarea class="form-control" name="branches" rows="5"><?php echo htmlspecialchars($bankData['branches']); ?></textarea>
                                                <small class="text-muted">Enter each branch on a separate line</small>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Nearby Places</label>
                                        <input name="nearby_places" class="form-control" value="<?php echo $bankData['nearby_places']; ?>">
                                        <small class="text-muted">Example: Museum, Park, Shopping Mall</small>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Social Media Links</label>
                                        <textarea class="form-control" name="social_media" rows="5"><?php echo $bankData['social_media']; ?></textarea>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Transportation Tab -->
                            <div class="tab-pane fade" id="transport" role="tabpanel">
                                <div class="form-section">
                                    <h5>Transportation Routes</h5>
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
                                                                <input type="text" class="form-control" name="route_link[]" value="<?php echo htmlspecialchars($route['link'] ?? ''); ?>">
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Description (Optional)</label>
                                                                <textarea class="form-control" name="route_description[]" rows="2"><?php echo htmlspecialchars($route['description'] ?? ''); ?></textarea>
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
                                    <button type="button" class="btn btn-secondary btn-sm mt-2" onclick="addTransportRoute()">
                                        <i class="bi bi-plus"></i> Add Another Route
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Location Tab -->
                            <div class="tab-pane fade" id="location" role="tabpanel">
                                <div class="form-section">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Address *</label>
                                                <input type="text" class="form-control" name="location" value="<?php echo $bankData['location']; ?>" maxlength="255">
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Website URL</label>
                                                <input type="url" class="form-control" name="website" value="<?php echo $bankData['website']; ?>"  maxlength="255">
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Google Maps Directions Link</label>
                                                <input type="url" class="form-control" name="directions_link" value="<?php echo $bankData['directions_link']; ?>" maxlength="255">
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Google Map Link</label>
                                                <input type="url" class="form-control" name="maps_link" value="<?php echo $bankData['maps_link']; ?>" maxlength="255">
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Embed Google Map URL</label>
                                                <input type="url" class="form-control" name="map_embed" value="<?php echo $bankData['maps_embed']; ?>" maxlength="1000">
                                                <small class="text-muted">Use the "Share" > "Embed a map" option from Google Maps</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-end gap-2 mt-3">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <?php echo isset($_GET['edit']) ? 'Update Bank' : 'Save Bank'; ?>
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
        let routeCount = <?php echo max(count($transportation_routes), 1); ?>;
        
        function addTransportRoute() {
            routeCount++;
            const container = document.getElementById('transport-routes-container');
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
            const routeItem = button.closest('.transport-route-item');
            const container = document.getElementById('transport-routes-container');
            
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
                routeCount = routes.length;
            } else {
                alert('You must have at least one transportation route. If you want to remove all routes, please clear the fields instead.');
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Handle toasts
            var toasts = document.querySelectorAll('.toast');
            toasts.forEach(function(toast) {
                setTimeout(function() {
                    var bsToast = new bootstrap.Toast(toast);
                    bsToast.hide();
                }, 5000);
            });
            
            // Handle modal display for edit
            if (window.location.search.includes('edit')) {
                var modal = new bootstrap.Modal(document.getElementById('bankModal'));
                modal.show();
                
                history.replaceState(null, null, window.location.pathname);
            }
            
            // Handle modal backdrop cleanup
            var bankModal = document.getElementById('bankModal');
            if (bankModal) {
                bankModal.addEventListener('hidden.bs.modal', function () {
                    var backdrops = document.getElementsByClassName('modal-backdrop');
                    for (var i = 0; i < backdrops.length; i++) {
                        backdrops[i].remove();
                    }
                });
            }
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>