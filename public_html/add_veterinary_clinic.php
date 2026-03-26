<?php
session_start();
include "conn.php";

// First, let's check if the transportation_options column exists and add it if it doesn't
$check_column = $conn->query("SHOW COLUMNS FROM veterinary_clinics LIKE 'transportation_options'");
if ($check_column->num_rows == 0) {
    $conn->query("ALTER TABLE veterinary_clinics ADD COLUMN transportation_options TEXT DEFAULT NULL AFTER insurance_accepted");
}

// Also check for transportation_routes column
$check_routes_column = $conn->query("SHOW COLUMNS FROM veterinary_clinics LIKE 'transportation_routes'");
if ($check_routes_column->num_rows == 0) {
    $conn->query("ALTER TABLE veterinary_clinics ADD COLUMN transportation_routes TEXT DEFAULT NULL AFTER transportation_options");
}

$target_dir = "uploads/veterinary_clinics/";
if (!file_exists($target_dir)) {
    mkdir($target_dir, 0777, true);
}

if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    
    $stmt = $conn->prepare("SELECT image, image_detail FROM veterinary_clinics WHERE id=?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $clinic = $result->fetch_assoc();
    
    $stmt = $conn->prepare("DELETE FROM veterinary_clinics WHERE id=?");
    $stmt->bind_param("i", $delete_id);
    
    if ($stmt->execute()) {
        if ($clinic) {
            if (!empty($clinic['image']) && file_exists($clinic['image'])) {
                unlink($clinic['image']);
            }
            
            if (!empty($clinic['image_detail'])) {
                $images = explode(',', $clinic['image_detail']);
                foreach ($images as $img) {
                    if (!empty($img) && file_exists($img)) {
                        unlink($img);
                    }
                }
            }
        }
        
        $_SESSION['message'] = "Veterinary clinic deleted successfully";
    } else {
        $_SESSION['error'] = "Error deleting clinic: " . $conn->error;
    }
    
    header("Location: add_veterinary_clinic.php");
    exit();
}

if (isset($_GET['delete_image']) && isset($_GET['clinic_id'])) {
    $image_path = $_GET['delete_image'];
    $clinic_id = (int)$_GET['clinic_id'];
    
    if (file_exists($image_path)) {
        unlink($image_path);
    }
    
    $stmt = $conn->prepare("SELECT image_detail FROM veterinary_clinics WHERE id=?");
    $stmt->bind_param("i", $clinic_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $clinic = $result->fetch_assoc();
    
    if ($clinic) {
        $images = explode(',', $clinic['image_detail']);
        $updated_images = array_diff($images, [$image_path]);
        $updated_images_str = implode(',', $updated_images);
        
        $stmt = $conn->prepare("UPDATE veterinary_clinics SET image_detail=? WHERE id=?");
        $stmt->bind_param("si", $updated_images_str, $clinic_id);
        $stmt->execute();
    }
    
    $_SESSION['message'] = "Image deleted successfully";
    header("Location: add_veterinary_clinic.php?edit=" . $clinic_id);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $location = trim($_POST['location']);
    $website = trim($_POST['website']);
    $maps_link = trim($_POST['maps_link']);
    $maps_embed = trim($_POST['maps_embed']);
    $hours = trim($_POST['hours']);
    $contact = trim($_POST['contact']);
    $email = trim($_POST['email']);
    $services = trim($_POST['services']);
    $emergency_services = trim($_POST['emergency_services']);
    $specialties = trim($_POST['specialties']);
    $veterinarians = trim($_POST['veterinarians']);
    $accessibility = trim($_POST['accessibility']);
    $social_media = trim($_POST['social_media']);
    $directions = trim($_POST['directions']);
    $nearby_places = trim($_POST['nearby_places']);
    $details_link = trim($_POST['details_link']);
    $pet_types = trim($_POST['pet_types']);
    $established = trim($_POST['established']);
    $payment_options = trim($_POST['payment_options']);
    $insurance_accepted = trim($_POST['insurance_accepted']);
    $transportation_options = trim($_POST['transportation_options']);
    $branches = trim($_POST['branches']);

    // Handle transportation routes
    $transportation_routes = array();
    
    // Process table rows
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
    if (isset($_POST['delete_image']) && $_POST['delete_image'] == '1') {
        if (!empty($target_file) && file_exists($target_file)) {
            unlink($target_file);
        }
        $target_file = '';
    }

    if (!empty($_FILES['image']['name'])) {
        $file_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $new_filename = $target_dir . uniqid('card_') . '.' . $file_ext;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $new_filename)) {
            if (!empty($_POST['existing_image']) && file_exists($_POST['existing_image'])) {
                unlink($_POST['existing_image']);
            }
            $target_file = $new_filename;
        } else {
            $_SESSION['error'] = "Error uploading the main image";
            header("Location: add_veterinary_clinic.php" . ($id ? "?edit=$id" : ""));
            exit();
        }
    }

    $imagePaths = [];
    $existingImages = !empty($_POST['existing_image_detail']) ? explode(',', $_POST['existing_image_detail']) : [];
    
    if (!empty($_FILES['image_detail']['name'][0])) {
        foreach ($_FILES['image_detail']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['image_detail']['error'][$key] !== UPLOAD_ERR_OK) continue;
            
            $file_ext = strtolower(pathinfo($_FILES['image_detail']['name'][$key], PATHINFO_EXTENSION));
            $new_filename = $target_dir . uniqid('detail_') . '.' . $file_ext;
            
            if (move_uploaded_file($tmp_name, $new_filename)) {
                $imagePaths[] = $new_filename;
            }
        }
    }
    
    $allImages = array_merge($existingImages, $imagePaths);
    $target_file_detail = implode(',', $allImages);

    // Process JSON data for nearby places
    $nearby_places_array = array_map('trim', explode(',', $nearby_places));
    $nearby_places_json = json_encode(array_filter($nearby_places_array));

    if ($id) {
        $query = "UPDATE veterinary_clinics SET 
                  name=?, image=?, image_detail=?, description=?, location=?, 
                  website=?, maps_link=?, maps_embed=?, hours=?, contact=?, 
                  email=?, services=?, emergency_services=?, specialties=?, 
                  veterinarians=?, accessibility=?, social_media=?, directions=?, 
                  nearby_places=?, details_link=?, pet_types=?, established=?, 
                  payment_options=?, insurance_accepted=?, transportation_options=?, 
                  branches=?, transportation_routes=?
                  WHERE id=?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssssssssssssssssssssssssssi", 
            $name, $target_file, $target_file_detail, $description, $location, 
            $website, $maps_link, $maps_embed, $hours, $contact, 
            $email, $services, $emergency_services, $specialties, 
            $veterinarians, $accessibility, $social_media, $directions, 
            $nearby_places_json, $details_link, $pet_types, $established, 
            $payment_options, $insurance_accepted, $transportation_options, 
            $branches, $transportation_routes_json, $id);
    } else {
        $query = "INSERT INTO veterinary_clinics (
                  name, image, image_detail, description, location, 
                  website, maps_link, maps_embed, hours, contact, 
                  email, services, emergency_services, specialties, 
                  veterinarians, accessibility, social_media, directions, 
                  nearby_places, details_link, pet_types, established, 
                  payment_options, insurance_accepted, transportation_options, 
                  branches, transportation_routes
                  ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssssssssssssssssssssssssss", 
            $name, $target_file, $target_file_detail, $description, $location, 
            $website, $maps_link, $maps_embed, $hours, $contact, 
            $email, $services, $emergency_services, $specialties, 
            $veterinarians, $accessibility, $social_media, $directions, 
            $nearby_places_json, $details_link, $pet_types, $established, 
            $payment_options, $insurance_accepted, $transportation_options, 
            $branches, $transportation_routes_json);
    }
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Veterinary clinic " . ($id ? "updated" : "added") . " successfully";
    } else {
        $_SESSION['error'] = "Error saving veterinary clinic: " . $conn->error;
    }
    
    header("Location: add_veterinary_clinic.php");
    exit();
}

$query = "SELECT id, name, image, description, location, specialties FROM veterinary_clinics ORDER BY name";
$clinics = $conn->query($query);

$clinicData = [
    'id' => '', 'name' => '', 'image' => '', 'image_detail' => '', 'description' => '', 
    'location' => '', 'website' => '', 'maps_link' => '', 'maps_embed' => '', 
    'hours' => '', 'contact' => '', 'email' => '', 'services' => '', 'emergency_services' => '', 
    'specialties' => '', 'veterinarians' => '', 'accessibility' => '', 
    'social_media' => '', 'directions' => '', 'nearby_places' => '', 
    'details_link' => '', 'pet_types' => '', 'established' => '', 
    'payment_options' => '', 'insurance_accepted' => '', 
    'transportation_options' => '', 'branches' => ''
];

$transportation_routes = array();

if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM veterinary_clinics WHERE id=?");
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
    }
}

// Get nearby places for form display
$nearby_places_str = '';
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT nearby_places FROM veterinary_clinics WHERE id=?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (!empty($row['nearby_places'])) {
            $decoded_places = json_decode($row['nearby_places'], true);
            if (is_array($decoded_places)) {
                $nearby_places_str = implode(', ', $decoded_places);
            }
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
    <title>Veterinary Clinics Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    <style>
        body {
            background: url('img/bg.png') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
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
        .page-title {
            color: white;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <!-- Toast Notifications -->
        <div class="toast-container">
            <?php if (isset($_SESSION['message'])): ?>
                <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="toast-header bg-success text-white">
                        <strong class="me-auto">Success</strong>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                    <div class="toast-body">
                        <?= $_SESSION['message']; unset($_SESSION['message']); ?>
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
                        <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <button type="button" class="btn btn-light text-dark mb-3" onclick="window.location.href='dashboard.php'">
            <i class="bi bi-arrow-left"></i> Back to Dashboard
        </button>
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="page-title">Veterinary Clinics Management</h1>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#clinicModal">
                <i class="bi bi-plus-lg"></i> Add Veterinary Clinic
            </button>
        </div>
        
        <?php if ($clinics->num_rows == 0): ?>
            <div class="alert alert-info">
                No veterinary clinics found. Click "Add Veterinary Clinic" to create one.
            </div>
        <?php else: ?>
            <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-4">
                <?php while ($clinic = $clinics->fetch_assoc()): ?>
                    <div class="col">
                        <div class="card h-100">
                            <?php if (!empty($clinic['image'])): ?>
                                <img src="<?= htmlspecialchars($clinic['image']) ?>" class="card-img-top" alt="<?= htmlspecialchars($clinic['name']) ?>">
                            <?php else: ?>
                                <div class="card-img-top bg-light d-flex align-items-center justify-content-center">
                                    <i class="bi bi-heart-pulse fs-1 text-muted"></i>
                                </div>
                            <?php endif; ?>
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?= htmlspecialchars($clinic['name']) ?></h5>
                                <p class="card-text flex-grow-1"><?= substr(htmlspecialchars($clinic['description']), 0, 100) . '...' ?></p>
                                <?php if (!empty($clinic['specialties'])): ?>
                                    <p class="text-primary fw-bold"><?= htmlspecialchars($clinic['specialties']) ?></p>
                                <?php endif; ?>
                                <p class="card-text"><small class="text-muted"><?= htmlspecialchars($clinic['location']) ?></small></p>
                                <div class="d-flex justify-content-between">
                                    <a href="add_veterinary_clinic.php?edit=<?= $clinic['id'] ?>" class="btn btn-primary btn-sm">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    <a href="?delete=<?= $clinic['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this veterinary clinic?')">
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

    <!-- Add/Edit Veterinary Clinic Modal -->
    <div class="modal fade <?= isset($_GET['edit']) ? 'show d-block' : '' ?>" id="clinicModal" tabindex="-1" aria-labelledby="clinicModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="clinicModalLabel"><?= $clinicData['id'] ? 'Edit Veterinary Clinic' : 'Add Veterinary Clinic' ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="add_veterinary_clinic.php" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="<?= htmlspecialchars($clinicData['id']) ?>">
                        
                        <div class="form-section">
                            <h5>Basic Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Name *</label>
                                        <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($clinicData['name']) ?>" >
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Location *</label>
                                        <textarea class="form-control" name="location" rows="3" ><?= htmlspecialchars($clinicData['location']) ?></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Established Year</label>
                                        <input type="text" class="form-control" name="established" value="<?= htmlspecialchars($clinicData['established']) ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Specialties</label>
                                        <textarea class="form-control" name="specialties" rows="3"><?= htmlspecialchars($clinicData['specialties']) ?></textarea>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Upload Card Image:</label>
                                        <input type="file" class="form-control" name="image" accept="image/*">
                                        
                                        <?php if (!empty($clinicData['image'])): ?>
                                            <div class="mt-2">
                                                <img src="<?= htmlspecialchars($clinicData['image']) ?>" class="img-thumbnail" width="100">
                                                <div class="form-check mt-1">
                                                    <input class="form-check-input" type="checkbox" name="delete_image" value="1" id="deleteImageCheck">
                                                    <label class="form-check-label" for="deleteImageCheck">Delete current image</label>
                                                </div>
                                            </div>
                                            <input type="hidden" name="existing_image" value="<?= htmlspecialchars($clinicData['image']) ?>">
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Description *</label>
                                <textarea class="form-control" name="description" rows="4"><?= htmlspecialchars($clinicData['description']) ?></textarea>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h5>Images</h5>
                            <div class="mb-3">
                                <label class="form-label">Detail Images (2-5 recommended)</label>
                                <input type="file" class="form-control" name="image_detail[]" multiple accept="image/*">
                                
                                <?php if (!empty($clinicData['image_detail'])): ?>
                                    <div class="image-preview-container mt-2">
                                        <?php 
                                            $images = explode(',', $clinicData['image_detail']);
                                            foreach ($images as $img): 
                                                if (!empty($img)):
                                        ?>
                                            <div class="image-preview">
                                                <img src="<?= htmlspecialchars($img) ?>" alt="Detail image">
                                                <div class="delete-image-btn" 
                                                     onclick="if(confirm('Delete this image?')) window.location.href='?delete_image=<?= urlencode($img) ?>&clinic_id=<?= $clinicData['id'] ?>'">
                                                    ×
                                                </div>
                                            </div>
                                        <?php 
                                                endif;
                                            endforeach; 
                                        ?>
                                        <input type="hidden" name="existing_image_detail" value="<?= htmlspecialchars($clinicData['image_detail']) ?>">
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
                                                        <option value="bus" <?php echo (isset($transportation_routes[$i]['type']) && $transportation_routes[$i]['type'] == 'bus') ? 'selected' : ''; ?>>Bus</option>
                                                        <option value="jeepney" <?php echo (isset($transportation_routes[$i]['type']) && $transportation_routes[$i]['type'] == 'jeepney') ? 'selected' : ''; ?>>Jeepney</option>
                                                        <option value="tricycle" <?php echo (isset($transportation_routes[$i]['type']) && $transportation_routes[$i]['type'] == 'tricycle') ? 'selected' : ''; ?>>Tricycle</option>
                                                        <option value="taxi" <?php echo (isset($transportation_routes[$i]['type']) && $transportation_routes[$i]['type'] == 'taxi') ? 'selected' : ''; ?>>Taxi</option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control" name="route_name[]" value="<?php echo isset($transportation_routes[$i]['name']) ? htmlspecialchars($transportation_routes[$i]['name']) : ''; ?>" placeholder="e.g., Bus Route 10, Emergency Hotline, Parking Lot A">
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control" name="route_link[]" value="<?php echo isset($transportation_routes[$i]['link']) ? htmlspecialchars($transportation_routes[$i]['link']) : ''; ?>" placeholder="https://maps.google.com/...">
                                                </td>
                                                <td>
                                                    <textarea class="form-control" name="route_description[]" rows="2" placeholder="e.g., Nearest bus stop, emergency pet transport, parking availability"><?php echo isset($transportation_routes[$i]['description']) ? htmlspecialchars($transportation_routes[$i]['description']) : ''; ?></textarea>
                                                </td>
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endfor; ?>
                                    </tbody>
                                </table>
                            </div>
                            <button type="button" class="btn btn-secondary btn-sm btn-add-row" onclick="addRow()">
                                <i class="bi bi-plus-lg"></i> Add Row
                            </button>
                        </div>
                        
                        <div class="form-section">
                            <h5>Medical Services</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Services (one per line)</label>
                                        <textarea class="form-control" name="services" rows="5" placeholder="Vaccinations&#10;Surgery&#10;Dental Care&#10;Check-ups"><?= htmlspecialchars($clinicData['services']) ?></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Emergency Services</label>
                                        <input type="text" class="form-control" name="emergency_services" value="<?= htmlspecialchars($clinicData['emergency_services']) ?>" placeholder="24/7 emergency care, after-hours service">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Pet Types Treated</label>
                                        <input type="text" class="form-control" name="pet_types" value="<?= htmlspecialchars($clinicData['pet_types']) ?>" placeholder="e.g., Dogs, Cats, Birds, Exotic Pets">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Veterinarians (one per line)</label>
                                        <textarea class="form-control" name="veterinarians" rows="5" placeholder="Dr. John Smith - Surgeon&#10;Dr. Jane Doe - Internal Medicine"><?= htmlspecialchars($clinicData['veterinarians']) ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h5>Contact & Business Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Operating Hours</label>
                                        <textarea class="form-control" name="hours" rows="5" placeholder="Monday: 8AM - 6PM&#10;Tuesday: 8AM - 6PM&#10;Emergency: 24/7"><?= htmlspecialchars($clinicData['hours']) ?></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Contact Information</label>
                                        <input type="text" class="form-control" name="contact" value="<?= htmlspecialchars($clinicData['contact']) ?>" placeholder="Phone numbers, mobile">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($clinicData['email']) ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Website</label>
                                        <input type="url" class="form-control" name="website" value="<?= htmlspecialchars($clinicData['website']) ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Payment Options</label>
                                        <input type="text" class="form-control" name="payment_options" value="<?= htmlspecialchars($clinicData['payment_options']) ?>" placeholder="Cash, Credit Card, Insurance, Installment">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Insurance Accepted</label>
                                        <input type="text" class="form-control" name="insurance_accepted" value="<?= htmlspecialchars($clinicData['insurance_accepted']) ?>" placeholder="Pet insurance companies accepted">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Details Page Link:</label>
                                        <input type="text" class="form-control" name="details_link" value="<?= htmlspecialchars($clinicData['details_link']) ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h5>Accessibility & Facilities</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Accessibility</label>
                                        <input type="text" class="form-control" name="accessibility" value="<?= htmlspecialchars($clinicData['accessibility']) ?>" placeholder="Wheelchair accessible, pet-friendly waiting area">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Transportation Options (one per line)</label>
                                        <textarea class="form-control" name="transportation_options" rows="3" placeholder="Public transportation nearby&#10;Parking available&#10;Ambulance service"><?= htmlspecialchars($clinicData['transportation_options']) ?></textarea>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Branches (one per line):</label>
                                        <textarea class="form-control" name="branches" rows="5" placeholder="Main Branch: 123 Main St&#10;Branch 2: 456 Oak Ave"><?= htmlspecialchars($clinicData['branches']) ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h5>Location & Social Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Google Maps Link</label>
                                        <input type="url" class="form-control" name="maps_link" value="<?= htmlspecialchars($clinicData['maps_link']) ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Embed Google Map URL</label>
                                        <input type="url" class="form-control" name="maps_embed" value="<?= htmlspecialchars($clinicData['maps_embed']) ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Directions</label>
                                        <input type="text" class="form-control" name="directions" value="<?= htmlspecialchars($clinicData['directions']) ?>" placeholder="Landmarks, specific instructions">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Nearby Places (comma separated)</label>
                                        <input type="text" class="form-control" name="nearby_places" value="<?= $nearby_places_str ?>" placeholder="Pet store, park, pharmacy, supermarket">
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Social Media Links (one per line)</label>
                                <textarea class="form-control" name="social_media" rows="3" placeholder="https://facebook.com/clinic&#10;https://instagram.com/clinic"><?= htmlspecialchars($clinicData['social_media']) ?></textarea>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <?= $clinicData['id'] ? 'Update Veterinary Clinic' : 'Save Veterinary Clinic' ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-hide toasts after 5 seconds
            var toasts = document.querySelectorAll('.toast');
            toasts.forEach(function(toast) {
                setTimeout(function() {
                    var bsToast = new bootstrap.Toast(toast);
                    bsToast.hide();
                }, 5000);
            });
            
            // Show modal if editing
            if (window.location.search.includes('edit')) {
                var modal = new bootstrap.Modal(document.getElementById('clinicModal'));
                modal.show();
                
                // Clean URL
                history.replaceState(null, null, window.location.pathname);
            }
        });
        
        function addRow() {
            var tbody = document.getElementById('transport-routes-tbody');
            var newRow = document.createElement('tr');
            newRow.innerHTML = `
                <td>
                    <select class="form-control" name="route_type[]">
                        <option value="">Select Type</option>
                        <option value="bus">Bus</option>
                        <option value="jeepney">Jeepney</option>
                        <option value="tricycle">Tricycle</option>
                        <option value="taxi">Taxi</option>
                    </select>
                </td>
                <td>
                    <input type="text" class="form-control" name="route_name[]" placeholder="e.g., Bus Route 10, Emergency Hotline, Parking Lot A">
                </td>
                <td>
                    <input type="text" class="form-control" name="route_link[]" placeholder="https://maps.google.com/...">
                </td>
                <td>
                    <textarea class="form-control" name="route_description[]" rows="2" placeholder="e.g., Nearest bus stop, emergency pet transport, parking availability"></textarea>
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)">
                        <i class="bi bi-trash"></i>
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