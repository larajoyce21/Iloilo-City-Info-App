<?php
session_start();
require 'conn.php';

// First, let's check if the transportation_routes column exists and add it if it doesn't
$checkColumn = $conn->query("SHOW COLUMNS FROM optical_clinics LIKE 'transportation_routes'");
if ($checkColumn->num_rows == 0) {
    $conn->query("ALTER TABLE optical_clinics ADD COLUMN transportation_routes TEXT NULL AFTER details_link");
}

if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    
    $stmt = $conn->prepare("SELECT image, image_detail FROM optical_clinics WHERE id=?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $clinic = $result->fetch_assoc();
    
    $stmt = $conn->prepare("DELETE FROM optical_clinics WHERE id=?");
    $stmt->bind_param("i", $delete_id);
    
    if ($stmt->execute()) {
        if ($clinic) {
            if (!empty($clinic['image'])) {
                $image_path = 'uploads/' . basename($clinic['image']);
                if (file_exists($image_path)) {
                    unlink($image_path);
                }
            }
            
            if (!empty($clinic['image_detail'])) {
                $images = explode(',', $clinic['image_detail']);
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
        
        $_SESSION['message'] = "Optical clinic deleted successfully";
    } else {
        $_SESSION['error'] = "Error deleting optical clinic";
    }
    
    header("Location: add_optical_clinics.php");
    exit();
}

if (isset($_GET['delete_image']) && isset($_GET['clinic_id'])) {
    $image_path = $_GET['delete_image'];
    $clinic_id = intval($_GET['clinic_id']);
    $is_card_image = isset($_GET['is_card_image']) ? true : false;
    
    $real_image_path = 'uploads/' . basename($image_path);
    
    if (file_exists($real_image_path)) {
        unlink($real_image_path);
    }
    
    if ($is_card_image) {
        $stmt = $conn->prepare("UPDATE optical_clinics SET image='' WHERE id=?");
        $stmt->bind_param("i", $clinic_id);
        $stmt->execute();
    } else {
        $stmt = $conn->prepare("SELECT image_detail FROM optical_clinics WHERE id=?");
        $stmt->bind_param("i", $clinic_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $clinic = $result->fetch_assoc();
        
        if ($clinic) {
            $images = array_filter(explode(',', $clinic['image_detail']));
            $updated_images = array_diff($images, [$image_path]);
            $updated_images_str = implode(',', $updated_images);
            
            $stmt = $conn->prepare("UPDATE optical_clinics SET image_detail=? WHERE id=?");
            $stmt->bind_param("si", $updated_images_str, $clinic_id);
            $stmt->execute();
        }
    }
    
    $_SESSION['message'] = "Image deleted successfully";
    header("Location: add_optical_clinics.php?edit=" . $clinic_id);
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
    $services = $_POST['services'] ?? '';
    $email = $_POST['email'] ?? '';
    $specialties = $_POST['specialties'] ?? '';
    $doctors = $_POST['doctors'] ?? '';
    $insurance = $_POST['insurance'] ?? '';
    $payment_methods = $_POST['payment_methods'] ?? '';
    $appointment_info = $_POST['appointment_info'] ?? '';
    $appointment_contact = $_POST['appointment_contact'] ?? '';
    $emergency = $_POST['emergency'] ?? '';
    $emergency_contact = $_POST['emergency_contact'] ?? '';
    $equipment = $_POST['equipment'] ?? '';
    $type = $_POST['type'] ?? '';
    $social_media = $_POST['social_media'] ?? '';
    $nearby_places = $_POST['nearby_places'] ?? '';
    $online_booking = $_POST['online_booking'] ?? '';
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
                header("Location: add_optical_clinics.php" . ($id ? "?edit=$id" : ""));
                exit();
            }
        } else {
            $_SESSION['error'] = "Invalid file type. Only JPG, JPEG, PNG, GIF are allowed";
            header("Location: add_optical_clinics.php" . ($id ? "?edit=$id" : ""));
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
            header("Location: add_optical_clinics.php" . ($id ? "?edit=$id" : ""));
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
        $stmt = $conn->prepare("UPDATE optical_clinics SET name=?, image=?, image_detail=?, description=?, location=?, maps_embed=?, maps_link=?, website=?, directions_link=?, hours=?, contact=?, type=?, established=?, services=?, email=?, specialties=?, doctors=?, insurance=?, payment_methods=?, appointment_info=?, appointment_contact=?, emergency=?, emergency_contact=?, equipment=?, social_media=?, nearby_places=?, online_booking=?, details_link=?, transportation_routes=? WHERE id=?");
        $stmt->bind_param("sssssssssssssssssssssssssssssi", $name, $target_file, $target_file_detail, $description, $location, $mapEmbedURL, $maps_link, $website, $directions_link, $hours, $contact, $type, $established, $services, $email, $specialties, $doctors, $insurance, $payment_methods, $appointment_info, $appointment_contact, $emergency, $emergency_contact, $equipment, $social_media, $nearby_places, $online_booking, $details_link, $transportation_routes_json, $id);
    } else {
        $stmt = $conn->prepare("INSERT INTO optical_clinics (name, image, image_detail, description, location, maps_embed, maps_link, website, directions_link, hours, contact, type, established, services, email, specialties, doctors, insurance, payment_methods, appointment_info, appointment_contact, emergency, emergency_contact, equipment, social_media, nearby_places, online_booking, details_link, transportation_routes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssssssssssssssssssssssss", $name, $target_file, $target_file_detail, $description, $location, $mapEmbedURL, $maps_link, $website, $directions_link, $hours, $contact, $type, $established, $services, $email, $specialties, $doctors, $insurance, $payment_methods, $appointment_info, $appointment_contact, $emergency, $emergency_contact, $equipment, $social_media, $nearby_places, $online_booking, $details_link, $transportation_routes_json);
    }

    if ($stmt->execute()) {
        $_SESSION['message'] = "Optical clinic " . ($id ? "updated" : "added") . " successfully";
    } else {
        $_SESSION['error'] = "Error saving optical clinic: " . $stmt->error;
    }
    
    header("Location: add_optical_clinics.php");
    exit();
}

$query = "SELECT * FROM optical_clinics ORDER BY name ASC";
$result = $conn->query($query);
$clinics = $result->fetch_all(MYSQLI_ASSOC);

$clinicData = [
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
    'services' => '',
    'email' => '',
    'specialties' => '',
    'doctors' => '',
    'insurance' => '',
    'payment_methods' => '',
    'appointment_info' => '',
    'appointment_contact' => '',
    'emergency' => '',
    'emergency_contact' => '',
    'equipment' => '',
    'social_media' => '',
    'nearby_places' => '',
    'online_booking' => '',
    'details_link' => '',
    'transportation_routes' => ''
];

$transportation_routes = array();
$nearby_places_str = '';

if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM optical_clinics WHERE id=?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $clinicData = $result->fetch_assoc();
        if (!empty($clinicData['transportation_routes'])) {
            $decoded_routes = json_decode($clinicData['transportation_routes'], true);
            if (is_array($decoded_routes)) {
                $transportation_routes = $decoded_routes;
            }
        }
        $nearby_places_str = $clinicData['nearby_places'];
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
    <title>Optical Clinics Management</title>
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
            <h1 class="text-light">Optical Clinics Management</h1>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#clinicModal">
                <i class="bi bi-plus-lg"></i> Add Clinic
            </button>
        </div>
        
        <?php if (empty($clinics)): ?>
            <div class="alert alert-info">
                No optical clinics found. Click "Add Clinic" to create one.
            </div>
        <?php else: ?>
            <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-4">
                <?php foreach ($clinics as $clinic): ?>
                    <div class="col">
                        <div class="card h-100">
                            <?php if (!empty($clinic['image'])): ?>
                                <img src="<?php echo $clinic['image']; ?>" class="card-img-top" alt="<?php echo $clinic['name']; ?>">
                            <?php else: ?>
                                <div class="card-img-top bg-secondary d-flex align-items-center justify-content-center">
                                    <i class="bi bi-eye text-white" style="font-size: 3rem;"></i>
                                </div>
                            <?php endif; ?>
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?php echo $clinic['name']; ?></h5>
                                <p class="card-text flex-grow-1"><?php echo substr($clinic['description'], 0, 100) . '...'; ?></p>
                                <div class="d-flex justify-content-between">
                                    <a href="add_optical_clinics.php?edit=<?php echo $clinic['id']; ?>" class="btn btn-primary btn-sm">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    <a href="?delete=<?php echo $clinic['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this optical clinic? All associated images will also be deleted.')">
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

    <!-- Add/Edit Optical Clinic Modal -->
    <div class="modal fade <?php echo isset($_GET['edit']) ? 'show d-block' : ''; ?>" id="clinicModal" tabindex="-1" aria-labelledby="clinicModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="clinicModalLabel"><?php echo isset($_GET['edit']) ? 'Edit Optical Clinic' : 'Add Optical Clinic'; ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="add_optical_clinics.php" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="<?php echo $clinicData['id']; ?>">
                        
                        <div class="form-section">
                            <h5>Basic Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Clinic Name *</label>
                                        <input type="text" class="form-control" name="name" value="<?php echo $clinicData['name']; ?>" >
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Details Page Link:</label>
                                        <input type="text" class="form-control" name="details_link" value="<?php echo htmlspecialchars($clinicData['details_link']); ?>" placeholder="e.g., optical_clinic1.php?id=1">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Description *</label>
                                        <textarea class="form-control" name="description" rows="4" ><?php echo $clinicData['description']; ?></textarea>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Operating Hours</label>
                                        <textarea class="form-control" name="hours" rows="4" placeholder="e.g., Monday: 9AM - 5PM"><?php echo $clinicData['hours']; ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Contact Information</label>
                                        <input type="text" class="form-control" name="contact" value="<?php echo $clinicData['contact']; ?>" placeholder="Phone number" maxlength="100">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Email Address</label>
                                        <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($clinicData['email']); ?>" placeholder="email@example.com" maxlength="255">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h5>Images</h5>
                            <div class="mb-3">
                                <label class="form-label">Card Image (Main Display Image)</label>
                                <input type="file" class="form-control" name="image" accept="image/*">
                                
                                <?php if (!empty($clinicData['image'])): ?>
                                    <div class="mt-3">
                                        <p>Current Image:</p>
                                        <img src="<?php echo $clinicData['image']; ?>" class="img-thumbnail card-image-preview">
                                        <div class="form-check mt-2">
                                            <input class="form-check-input" type="checkbox" name="delete_image" value="1" id="deleteImageCheck">
                                            <label class="form-check-label" for="deleteImageCheck">Delete current image</label>
                                        </div>
                                        <input type="hidden" name="existing_image" value="<?php echo $clinicData['image']; ?>">
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Detail Images (Max 5)</label>
                                <input type="file" class="form-control" name="image_detail[]" multiple accept="image/*">
                                <small class="text-muted">Upload additional images to display on the clinic details page</small>
                                
                                <?php if (!empty($clinicData['image_detail'])): ?>
                                    <div class="mt-3">
                                        <p>Current Detail Images:</p>
                                        <div class="d-flex flex-wrap gap-2">
                                            <?php 
                                            $images = explode(',', $clinicData['image_detail']);
                                            foreach ($images as $img): 
                                                if (!empty($img)):
                                            ?>
                                                <div class="position-relative">
                                                    <img src="<?php echo $img; ?>" class="img-thumbnail" style="width: 100px; height: 100px; object-fit: cover;">
                                                    <a href="?delete_image=<?php echo urlencode($img); ?>&clinic_id=<?php echo $clinicData['id']; ?>" 
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
                                        <input type="hidden" name="existing_image_detail" value="<?php echo $clinicData['image_detail']; ?>">
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="form-section">
                            <h5>Medical Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Clinic Type</label>
                                        <input type="text" class="form-control" name="type" value="<?php echo $clinicData['type']; ?>" placeholder="e.g., Optical Clinic, Eye Center">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Established Year</label>
                                        <input type="text" class="form-control" name="established" value="<?php echo $clinicData['established']; ?>" placeholder="e.g., 2010">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Specialties</label>
                                        <input type="text" class="form-control" name="specialties" value="<?php echo $clinicData['specialties']; ?>" placeholder="e.g., Pediatric Optometry, Contact Lenses">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Equipment & Technology</label>
                                        <textarea class="form-control" name="equipment" rows="3"><?php echo $clinicData['equipment']; ?></textarea>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Services (one per line)</label>
                                        <textarea class="form-control" name="services" rows="5"><?php echo htmlspecialchars($clinicData['services']); ?></textarea>
                                        <small class="text-muted">Enter each service on a separate line</small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Medical Professionals</label>
                                        <textarea class="form-control" name="doctors" rows="3" placeholder="Doctors/Optometrists names"><?php echo $clinicData['doctors']; ?></textarea>
                                    </div>
                                </div>
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
                            <h5>Business Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Insurance Providers (comma separated)</label>
                                        <input type="text" class="form-control" name="insurance" value="<?php echo $clinicData['insurance']; ?>" placeholder="e.g., PhilHealth, Maxicare, Intellicare">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Payment Methods</label>
                                        <input type="text" class="form-control" name="payment_methods" value="<?php echo $clinicData['payment_methods']; ?>" placeholder="e.g., Cash, Credit Cards, HMO">
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Appointment Information</label>
                                        <textarea class="form-control" name="appointment_info" rows="2"><?php echo $clinicData['appointment_info']; ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Appointment Contact</label>
                                        <input type="text" class="form-control" name="appointment_contact" value="<?php echo $clinicData['appointment_contact']; ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Emergency Services</label>
                                        <input type="text" class="form-control" name="emergency" value="<?php echo $clinicData['emergency']; ?>" placeholder="e.g., Available during business hours">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Emergency Contact</label>
                                        <input type="text" class="form-control" name="emergency_contact" value="<?php echo $clinicData['emergency_contact']; ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Online Booking URL</label>
                                <input type="url" class="form-control" name="online_booking" value="<?php echo $clinicData['online_booking']; ?>" placeholder="https://example.com/booking">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Social Media Links</label>
                            <textarea class="form-control" name="social_media" rows="5" placeholder="Enter one URL per line"><?php echo $clinicData['social_media']; ?></textarea>
                        </div>
                        
                        <div class="form-section">
                            <h5>Location Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Address *</label>
                                        <input type="text" class="form-control" name="location" value="<?php echo $clinicData['location']; ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Website URL</label>
                                        <input type="url" class="form-control" name="website" value="<?php echo $clinicData['website']; ?>" placeholder="https://example.com" maxlength="255">
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Google Maps Directions Link</label>
                                        <input type="url" class="form-control" name="directions_link" value="<?php echo $clinicData['directions_link']; ?>" placeholder="https://maps.google.com/..." maxlength="255">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Google Map Link</label>
                                        <input type="url" class="form-control" name="maps_link" value="<?php echo $clinicData['maps_link']; ?>" placeholder="https://maps.google.com/..." maxlength="255">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Embed Google Map URL</label>
                                <input type="url" class="form-control" name="map_embed" value="<?php echo $clinicData['maps_embed']; ?>" placeholder="https://maps.google.com/embed..." maxlength="1000">
                                <small class="text-muted">Use the "Share" > "Embed a map" option from Google Maps</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Nearby Places</label>
                                <input name="nearby_places" class="form-control" value="<?php echo $nearby_places_str; ?>" placeholder="Add nearby places separated by commas">
                                <small class="text-muted">Example: Hospital, Pharmacy, Shopping Mall</small>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <?php echo isset($_GET['edit']) ? 'Update Clinic' : 'Save Clinic'; ?>
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
                var modal = new bootstrap.Modal(document.getElementById('clinicModal'));
                modal.show();
                
                history.replaceState(null, null, window.location.pathname);
            }
            
            var clinicModal = document.getElementById('clinicModal');
            if (clinicModal) {
                clinicModal.addEventListener('hidden.bs.modal', function () {
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