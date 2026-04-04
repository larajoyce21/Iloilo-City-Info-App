<?php
session_start();
include "conn.php";

// First, let's check if the transportation_routes column exists and add it if it doesn't
$check_routes_column = $conn->query("SHOW COLUMNS FROM travel_ticketings LIKE 'transportation_routes'");
if ($check_routes_column->num_rows == 0) {
    $conn->query("ALTER TABLE travel_ticketings ADD COLUMN transportation_routes TEXT DEFAULT NULL");
}

$target_dir = "uploads/travel_ticketings/";
if (!file_exists($target_dir)) {
    mkdir($target_dir, 0777, true);
}

if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    
    $stmt = $conn->prepare("SELECT image, image_detail FROM travel_ticketings WHERE id=?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $travel = $result->fetch_assoc();
    
    $stmt = $conn->prepare("DELETE FROM travel_ticketings WHERE id=?");
    $stmt->bind_param("i", $delete_id);
    
    if ($stmt->execute()) {
        if ($travel) {
            if (!empty($travel['image']) && file_exists($travel['image'])) {
                unlink($travel['image']);
            }
            
            if (!empty($travel['image_detail'])) {
                $images = explode(',', $travel['image_detail']);
                foreach ($images as $img) {
                    if (!empty($img) && file_exists($img)) {
                        unlink($img);
                    }
                }
            }
        }
        
        $_SESSION['message'] = "Travel agency deleted successfully";
    } else {
        $_SESSION['error'] = "Error deleting travel agency: " . $conn->error;
    }
    
    header("Location: add_travel_ticketings.php");
    exit();
}

if (isset($_GET['delete_image']) && isset($_GET['travel_id'])) {
    $image_path = $_GET['delete_image'];
    $travel_id = (int)$_GET['travel_id'];
    
    if (file_exists($image_path)) {
        unlink($image_path);
    }
    
    $stmt = $conn->prepare("SELECT image_detail FROM travel_ticketings WHERE id=?");
    $stmt->bind_param("i", $travel_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $travel = $result->fetch_assoc();
    
    if ($travel) {
        $images = explode(',', $travel['image_detail']);
        $updated_images = array_diff($images, [$image_path]);
        $updated_images_str = implode(',', $updated_images);
        
        $stmt = $conn->prepare("UPDATE travel_ticketings SET image_detail=? WHERE id=?");
        $stmt->bind_param("si", $updated_images_str, $travel_id);
        $stmt->execute();
    }
    
    $_SESSION['message'] = "Image deleted successfully";
    header("Location: add_travel_ticketings.php?edit=" . $travel_id);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $hours = trim($_POST['hours']);
    $contact = trim($_POST['contact']);
    $location = trim($_POST['location']);
    $website = trim($_POST['website']);
    $maps_link = trim($_POST['maps_link']);
    $directions_link = trim($_POST['directions_link']);
    $maps_embed = trim($_POST['maps_embed']);
    $type = trim($_POST['type']);
    $established = trim($_POST['established']);
    $tour_packages = trim($_POST['tour_packages']);
    $visa_assistance = trim($_POST['visa_assistance']);
    $accommodation = trim($_POST['accommodation']);
    $services = trim($_POST['services']);
    $email = trim($_POST['email']);
    $hotline = trim($_POST['hotline']);
    $destinations = trim($_POST['destinations']);
    $social_media = trim($_POST['social_media']);
    $nearby_places = trim($_POST['nearby_places']);
    $details_link = trim($_POST['details_link']);
    $certifications = trim($_POST['certifications']);
    $insurance = trim($_POST['insurance']);
    $safety_measures = trim($_POST['safety_measures']);
    $payment_options = trim($_POST['payment_options']);

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
            header("Location: add_travel_ticketings.php" . ($id ? "?edit=$id" : ""));
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

    if ($id) {
        $query = "UPDATE travel_ticketings SET 
                  name=?, description=?, location=?, website=?, maps_link=?, 
                  directions_link=?, maps_embed=?, hours=?, contact=?, type=?, 
                  established=?, tour_packages=?, visa_assistance=?, accommodation=?, 
                  services=?, email=?, hotline=?, destinations=?, social_media=?, 
                  nearby_places=?, details_link=?, certifications=?, insurance=?, 
                  safety_measures=?, payment_options=?, image=?, image_detail=?, 
                  transportation_routes=?
                  WHERE id=?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssssssssssssssssssssssssssi", 
            $name, $description, $location, $website, $maps_link, 
            $directions_link, $maps_embed, $hours, $contact, $type, 
            $established, $tour_packages, $visa_assistance, $accommodation, 
            $services, $email, $hotline, $destinations, $social_media, 
            $nearby_places, $details_link, $certifications, $insurance, 
            $safety_measures, $payment_options, $target_file, $target_file_detail, 
            $transportation_routes_json, $id);
    } else {
        $query = "INSERT INTO travel_ticketings (
                  name, description, location, website, maps_link, directions_link, 
                  maps_embed, hours, contact, type, established, tour_packages, 
                  visa_assistance, accommodation, services, email, hotline, 
                  destinations, social_media, nearby_places, details_link, 
                  certifications, insurance, safety_measures, payment_options, 
                  image, image_detail, transportation_routes
                  ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssssssssssssssssssssssssss", 
            $name, $description, $location, $website, $maps_link, 
            $directions_link, $maps_embed, $hours, $contact, $type, 
            $established, $tour_packages, $visa_assistance, $accommodation, 
            $services, $email, $hotline, $destinations, $social_media, 
            $nearby_places, $details_link, $certifications, $insurance, 
            $safety_measures, $payment_options, $target_file, $target_file_detail, 
            $transportation_routes_json);
    }
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Travel agency " . ($id ? "updated" : "added") . " successfully";
    } else {
        $_SESSION['error'] = "Error saving travel agency: " . $conn->error;
    }
    
    header("Location: add_travel_ticketings.php");
    exit();
}

$query = "SELECT id, name, image, description, location, type FROM travel_ticketings ORDER BY name";
$travels = $conn->query($query);

$travelData = [
    'id' => '', 'name' => '', 'description' => '', 'location' => '', 'website' => '', 
    'maps_link' => '', 'directions_link' => '', 'maps_embed' => '', 'hours' => '', 
    'contact' => '', 'type' => '', 'established' => '', 'tour_packages' => '', 
    'visa_assistance' => '', 'accommodation' => '', 'services' => '', 'email' => '', 
    'hotline' => '', 'destinations' => '', 'social_media' => '', 'nearby_places' => '', 
    'details_link' => '', 'certifications' => '', 'insurance' => '', 'safety_measures' => '', 
    'payment_options' => '', 'image' => '', 'image_detail' => '', 'transportation_routes' => ''
];

$transportation_routes = array();

if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM travel_ticketings WHERE id=?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $travelData = $result->fetch_assoc();
        
        if (!empty($travelData['transportation_routes'])) {
            $decoded_routes = json_decode($travelData['transportation_routes'], true);
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
    $stmt = $conn->prepare("SELECT nearby_places FROM travel_ticketings WHERE id=?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $nearby_places_str = $row['nearby_places'];
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
    <title>Travel Ticketings Management</title>
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
            <h1 class="page-title">Travel Ticketings Management</h1>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#travelModal">
                <i class="bi bi-plus-lg"></i> Add Travel Agency
            </button>
        </div>
        
        <?php if ($travels->num_rows == 0): ?>
            <div class="alert alert-info">
                No travel agencies found. Click "Add Travel Agency" to create one.
            </div>
        <?php else: ?>
            <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-4">
                <?php while ($travel = $travels->fetch_assoc()): ?>
                    <div class="col">
                        <div class="card h-100">
                            <?php if (!empty($travel['image'])): ?>
                                <img src="<?= htmlspecialchars($travel['image']) ?>" class="card-img-top" alt="<?= htmlspecialchars($travel['name']) ?>">
                            <?php else: ?>
                                <div class="card-img-top bg-light d-flex align-items-center justify-content-center">
                                    <i class="bi bi-ticket-perforated fs-1 text-muted"></i>
                                </div>
                            <?php endif; ?>
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?= htmlspecialchars($travel['name']) ?></h5>
                                <p class="card-text flex-grow-1"><?= substr(htmlspecialchars($travel['description']), 0, 100) . '...' ?></p>
                                <?php if (!empty($travel['type'])): ?>
                                    <p class="text-primary fw-bold"><?= htmlspecialchars($travel['type']) ?></p>
                                <?php endif; ?>
                                <p class="card-text"><small class="text-muted"><?= htmlspecialchars($travel['location']) ?></small></p>
                                <div class="d-flex justify-content-between">
                                    <a href="add_travel_ticketings.php?edit=<?= $travel['id'] ?>" class="btn btn-primary btn-sm">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    <a href="?delete=<?= $travel['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this travel agency?')">
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

    <!-- Add/Edit Travel Agency Modal -->
    <div class="modal fade <?= isset($_GET['edit']) ? 'show d-block' : '' ?>" id="travelModal" tabindex="-1" aria-labelledby="travelModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="travelModalLabel"><?= $travelData['id'] ? 'Edit Travel Agency' : 'Add Travel Agency' ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="add_travel_ticketings.php" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="<?= htmlspecialchars($travelData['id']) ?>">
                        
                        <div class="form-section">
                            <h5>Basic Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Name *</label>
                                        <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($travelData['name']) ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Location *</label>
                                        <textarea class="form-control" name="location" rows="3"><?= htmlspecialchars($travelData['location']) ?></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Agency Type</label>
                                        <input type="text" class="form-control" name="type" value="<?= htmlspecialchars($travelData['type']) ?>" placeholder="e.g., Tour Operator, Ticketing Agency">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Established</label>
                                        <input type="text" class="form-control" name="established" value="<?= htmlspecialchars($travelData['established']) ?>" placeholder="e.g., 2005">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Upload Card Image:</label>
                                        <input type="file" class="form-control" name="image" accept="image/*">
                                        
                                        <?php if (!empty($travelData['image'])): ?>
                                            <div class="mt-2">
                                                <img src="<?= htmlspecialchars($travelData['image']) ?>" class="img-thumbnail" width="100">
                                                <div class="form-check mt-1">
                                                    <input class="form-check-input" type="checkbox" name="delete_image" value="1" id="deleteImageCheck">
                                                    <label class="form-check-label" for="deleteImageCheck">Delete current image</label>
                                                </div>
                                            </div>
                                            <input type="hidden" name="existing_image" value="<?= htmlspecialchars($travelData['image']) ?>">
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Description *</label>
                                <textarea class="form-control" name="description" rows="4"><?= htmlspecialchars($travelData['description']) ?></textarea>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h5>Images</h5>
                            <div class="mb-3">
                                <label class="form-label">Detail Images (2-5 recommended)</label>
                                <input type="file" class="form-control" name="image_detail[]" multiple accept="image/*">
                                
                                <?php if (!empty($travelData['image_detail'])): ?>
                                    <div class="image-preview-container mt-2">
                                        <?php 
                                            $images = explode(',', $travelData['image_detail']);
                                            foreach ($images as $img): 
                                                if (!empty($img)):
                                        ?>
                                            <div class="image-preview">
                                                <img src="<?= htmlspecialchars($img) ?>" alt="Detail image">
                                                <div class="delete-image-btn" 
                                                     onclick="if(confirm('Delete this image?')) window.location.href='?delete_image=<?= urlencode($img) ?>&travel_id=<?= $travelData['id'] ?>'">
                                                    ×
                                                </div>
                                            </div>
                                        <?php 
                                                endif;
                                            endforeach; 
                                        ?>
                                        <input type="hidden" name="existing_image_detail" value="<?= htmlspecialchars($travelData['image_detail']) ?>">
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
                                                    <input type="text" class="form-control" name="route_name[]" value="<?php echo isset($transportation_routes[$i]['name']) ? htmlspecialchars($transportation_routes[$i]['name']) : ''; ?>" placeholder="e.g., Bus Route 10, Terminal A to B Beach">
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control" name="route_link[]" value="<?php echo isset($transportation_routes[$i]['link']) ? htmlspecialchars($transportation_routes[$i]['link']) : ''; ?>" placeholder="https://booking.website.com/...">
                                                </td>
                                                <td>
                                                    <textarea class="form-control" name="route_description[]" rows="2" placeholder="e.g., Daily trips from Manila, Air-conditioned, Terminal fee included"><?php echo isset($transportation_routes[$i]['description']) ? htmlspecialchars($transportation_routes[$i]['description']) : ''; ?></textarea>
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
                            <h5>Travel Services</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Tour Packages</label>
                                        <textarea class="form-control" name="tour_packages" rows="4"><?= htmlspecialchars($travelData['tour_packages']) ?></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Visa Assistance</label>
                                        <input type="text" class="form-control" name="visa_assistance" value="<?= htmlspecialchars($travelData['visa_assistance']) ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Accommodation Services</label>
                                        <textarea class="form-control" name="accommodation" rows="3"><?= htmlspecialchars($travelData['accommodation']) ?></textarea>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Services</label>
                                        <textarea class="form-control" name="services" rows="4" placeholder="List services offered"><?= htmlspecialchars($travelData['services']) ?></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Destinations (Format: Destination Name|Price|Duration)</label>
                                        <textarea class="form-control" name="destinations" rows="5" placeholder="e.g., Boracay|₱15,000|3D2N"><?= htmlspecialchars($travelData['destinations']) ?></textarea>
                                        <small class="text-muted">Enter each destination on a separate line. Use | to separate name, price, and duration.</small>
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
                                        <textarea class="form-control" name="hours" rows="4" placeholder="e.g., Monday: 9AM - 5PM"><?= htmlspecialchars($travelData['hours']) ?></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Contact Information</label>
                                        <input type="text" class="form-control" name="contact" value="<?= htmlspecialchars($travelData['contact']) ?>" placeholder="Phone, email, social media">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($travelData['email']) ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Emergency Hotline</label>
                                        <input type="text" class="form-control" name="hotline" value="<?= htmlspecialchars($travelData['hotline']) ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Website</label>
                                        <input type="url" class="form-control" name="website" value="<?= htmlspecialchars($travelData['website']) ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Details Page Link:</label>
                                        <input type="text" class="form-control" name="details_link" value="<?= htmlspecialchars($travelData['details_link']) ?>" placeholder="e.g., travel_ticketing1.php?id=1">
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Social Media Links (one per line)</label>
                                <textarea class="form-control" name="social_media" rows="3" placeholder="https://facebook.com/agency"><?= htmlspecialchars($travelData['social_media']) ?></textarea>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h5>Safety & Certification</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Certifications</label>
                                        <textarea class="form-control" name="certifications" rows="4" placeholder="One certification per line"><?= htmlspecialchars($travelData['certifications']) ?></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Travel Insurance</label>
                                        <input type="text" class="form-control" name="insurance" value="<?= htmlspecialchars($travelData['insurance']) ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Safety Measures</label>
                                        <textarea class="form-control" name="safety_measures" rows="4"><?= htmlspecialchars($travelData['safety_measures']) ?></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Payment Options</label>
                                        <input type="text" class="form-control" name="payment_options" value="<?= htmlspecialchars($travelData['payment_options']) ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h5>Location Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Google Map Link</label>
                                        <input type="url" class="form-control" name="maps_link" value="<?= htmlspecialchars($travelData['maps_link']) ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Google Maps Directions Link</label>
                                        <input type="url" class="form-control" name="directions_link" value="<?= htmlspecialchars($travelData['directions_link']) ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Embed Google Map URL</label>
                                        <input type="url" class="form-control" name="maps_embed" value="<?= htmlspecialchars($travelData['maps_embed']) ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Nearby Places:</label>
                                        <input name="nearby_places" class="form-control" value="<?= $nearby_places_str ?>" placeholder="e.g., Mall, Restaurant, Hotel, Airport">
                                        <small class="text-muted">Separate by commas</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <?= $travelData['id'] ? 'Update Travel Agency' : 'Save Travel Agency' ?>
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
                var modal = new bootstrap.Modal(document.getElementById('travelModal'));
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
                    <input type="text" class="form-control" name="route_name[]" placeholder="e.g., Bus Route 10, Terminal A to B Beach">
                </td>
                <td>
                    <input type="text" class="form-control" name="route_link[]" placeholder="https://booking.website.com/...">
                </td>
                <td>
                    <textarea class="form-control" name="route_description[]" rows="2" placeholder="e.g., Daily trips from Manila, Air-conditioned, Terminal fee included"></textarea>
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