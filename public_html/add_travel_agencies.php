<?php
session_start();
include "conn.php";

// First, let's check if the transportation_routes column exists and add it if it doesn't
$check_routes_column = $conn->query("SHOW COLUMNS FROM travel_agencies LIKE 'transportation_routes'");
if ($check_routes_column->num_rows == 0) {
    $conn->query("ALTER TABLE travel_agencies ADD COLUMN transportation_routes TEXT DEFAULT NULL");
}

$target_dir = "uploads/travel_agencies/";
if (!file_exists($target_dir)) {
    mkdir($target_dir, 0777, true);
}

if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    
    $stmt = $conn->prepare("SELECT image, image_detail FROM travel_agencies WHERE id=?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $agency = $result->fetch_assoc();
    
    $stmt = $conn->prepare("DELETE FROM travel_agencies WHERE id=?");
    $stmt->bind_param("i", $delete_id);
    
    if ($stmt->execute()) {
        if ($agency) {
            if (!empty($agency['image']) && file_exists($agency['image'])) {
                unlink($agency['image']);
            }
            
            if (!empty($agency['image_detail'])) {
                $images = explode(',', $agency['image_detail']);
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
    
    header("Location: add_travel_agencies.php");
    exit();
}

if (isset($_GET['delete_image']) && isset($_GET['agency_id'])) {
    $image_path = $_GET['delete_image'];
    $agency_id = (int)$_GET['agency_id'];
    
    if (file_exists($image_path)) {
        unlink($image_path);
    }
    
    $stmt = $conn->prepare("SELECT image_detail FROM travel_agencies WHERE id=?");
    $stmt->bind_param("i", $agency_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $agency = $result->fetch_assoc();
    
    if ($agency) {
        $images = explode(',', $agency['image_detail']);
        $updated_images = array_diff($images, [$image_path]);
        $updated_images_str = implode(',', $updated_images);
        
        $stmt = $conn->prepare("UPDATE travel_agencies SET image_detail=? WHERE id=?");
        $stmt->bind_param("si", $updated_images_str, $agency_id);
        $stmt->execute();
    }
    
    $_SESSION['message'] = "Image deleted successfully";
    header("Location: add_travel_agencies.php?edit=" . $agency_id);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $location = trim($_POST['location']);
    $maps_link = trim($_POST['maps_link']);
    $directions_link = trim($_POST['directions_link']);
    $maps_embed = trim($_POST['maps_embed']);
    $hours = trim($_POST['hours']);
    $contact = trim($_POST['contact']);
    $email = trim($_POST['email']);
    $social_media = trim($_POST['social_media']);
    $nearby_places = trim($_POST['nearby_places']);
    $services = trim($_POST['services']);
    $years = trim($_POST['years']);
    $website = trim($_POST['website']);
    $details_link = trim($_POST['details_link']);
    $cancellation_policy = trim($_POST['cancellation_policy']);
    $payment_options = trim($_POST['payment_options']);
    $safety_measures = trim($_POST['safety_measures']);
    $requirements = trim($_POST['requirements']);
    $packages = trim($_POST['packages']);

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
            header("Location: add_travel_agencies.php" . ($id ? "?edit=$id" : ""));
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

    // Process JSON data for other fields
    $nearby_places_array = array_map('trim', explode(',', $nearby_places));
    $nearby_places_json = json_encode(array_filter($nearby_places_array));
    
    $payment_options_array = array_map('trim', explode(',', $payment_options));
    $payment_options_json = json_encode(array_filter($payment_options_array));
    
    $safety_measures_array = array_map('trim', explode("\n", $safety_measures));
    $safety_measures_json = json_encode(array_filter($safety_measures_array));
    
    $requirements_array = array_map('trim', explode("\n", $requirements));
    $requirements_json = json_encode(array_filter($requirements_array));
    
    $hours_array = [];
    if (!empty($hours)) {
        $hours_lines = explode("\n", $hours);
        foreach ($hours_lines as $line) {
            if (strpos($line, ':') !== false) {
                list($day, $time) = explode(':', $line, 2);
                $hours_array[trim($day)] = trim($time);
            }
        }
    }
    $hours_json = json_encode($hours_array);
    
    $services_array = [];
    if (!empty($services)) {
        $lines = preg_split('/\r?\n/', $services);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') continue;
            if (strpos($line, '|') !== false && strpos($line, '\n') === false && count($lines) === 1) {
                $parts = array_map('trim', explode('|', $line));
                foreach ($parts as $p) {
                    if ($p !== '') $services_array[] = $p;
                }
            } else {
                $services_array[] = $line;
            }
        }
    }
    $services_json = json_encode(array_values(array_filter($services_array)));

    $packages_array = [];
    $packages_trim = trim($packages);
    if ($packages_trim !== '') {
        $decoded = json_decode($packages_trim, true);
        if (is_array($decoded)) {
            $packages_array = $decoded;
        } else {
            $lines = preg_split('/\r?\n/', $packages_trim);
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '') continue;
                $parts = array_map('trim', explode('|', $line));
                $pkg = [
                    'title' => $parts[0] ?? '',
                    'description' => $parts[1] ?? '',
                    'price' => $parts[2] ?? '',
                    'duration' => $parts[3] ?? ''
                ];
                if ($pkg['title'] === '') continue;
                $packages_array[] = $pkg;
            }
        }
    }
    $packages_json = json_encode($packages_array);

    if ($id) {
        $query = "UPDATE travel_agencies SET 
                  name=?, description=?, location=?, maps_link=?, 
                  directions_link=?, maps_embed=?, hours=?, contact=?, 
                  email=?, social_media=?, nearby_places=?, services=?, 
                  years=?, website=?, details_link=?, cancellation_policy=?, 
                  payment_options=?, safety_measures=?, requirements=?, packages=?, 
                  image=?, image_detail=?, transportation_routes=?
                  WHERE id=?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssssssssssssssssssssssi", 
            $name, $description, $location, $maps_link, 
            $directions_link, $maps_embed, $hours_json, $contact, 
            $email, $social_media, $nearby_places_json, $services_json, 
            $years, $website, $details_link, $cancellation_policy, 
            $payment_options_json, $safety_measures_json, $requirements_json, $packages_json,
            $target_file, $target_file_detail, $transportation_routes_json, $id);
    } else {
        $query = "INSERT INTO travel_agencies (
                  name, description, location, maps_link, directions_link, 
                  maps_embed, hours, contact, email, social_media, nearby_places, 
                  services, years, website, details_link, cancellation_policy, 
                  payment_options, safety_measures, requirements, packages, 
                  image, image_detail, transportation_routes
                  ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssssssssssssssssssssss", 
            $name, $description, $location, $maps_link, 
            $directions_link, $maps_embed, $hours_json, $contact, 
            $email, $social_media, $nearby_places_json, $services_json, 
            $years, $website, $details_link, $cancellation_policy, 
            $payment_options_json, $safety_measures_json, $requirements_json, $packages_json,
            $target_file, $target_file_detail, $transportation_routes_json);
    }
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Travel agency " . ($id ? "updated" : "added") . " successfully";
    } else {
        $_SESSION['error'] = "Error saving travel agency: " . $conn->error;
    }
    
    header("Location: add_travel_agencies.php");
    exit();
}

$query = "SELECT id, name, image, description, location, years FROM travel_agencies ORDER BY name";
$agencies = $conn->query($query);

$agencyData = [
    'id' => '', 'name' => '', 'description' => '', 'location' => '', 'maps_link' => '', 
    'directions_link' => '', 'maps_embed' => '', 'hours' => '', 'contact' => '', 
    'email' => '', 'social_media' => '', 'nearby_places' => '', 'services' => '',
    'years' => '', 'website' => '', 'details_link' => '', 'cancellation_policy' => '', 
    'payment_options' => '', 'safety_measures' => '', 'requirements' => '', 'packages' => '',
    'image' => '', 'image_detail' => '', 'transportation_routes' => ''
];

$transportation_routes = array();

if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM travel_agencies WHERE id=?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $agencyData = $result->fetch_assoc();
        
        // Decode existing JSON data for display
        if (!empty($agencyData['hours'])) {
            $hours_array = json_decode($agencyData['hours'], true);
            if (is_array($hours_array)) {
                $hours_str = '';
                foreach ($hours_array as $day => $time) {
                    $hours_str .= "$day: $time\n";
                }
                $agencyData['hours'] = $hours_str;
            }
        }
        
        if (!empty($agencyData['safety_measures'])) {
            $safety_array = json_decode($agencyData['safety_measures'], true);
            if (is_array($safety_array)) {
                $agencyData['safety_measures'] = implode("\n", $safety_array);
            }
        }
        
        if (!empty($agencyData['requirements'])) {
            $req_array = json_decode($agencyData['requirements'], true);
            if (is_array($req_array)) {
                $agencyData['requirements'] = implode("\n", $req_array);
            }
        }
        
        if (!empty($agencyData['services'])) {
            $decoded_services = json_decode($agencyData['services'], true);
            if (is_array($decoded_services)) {
                $agencyData['services'] = implode("\n", $decoded_services);
            }
        }
        
        if (!empty($agencyData['packages'])) {
            $decoded_pkgs = json_decode($agencyData['packages'], true);
            if (is_array($decoded_pkgs)) {
                $pkg_lines = [];
                foreach ($decoded_pkgs as $p) {
                    if (is_array($p)) {
                        $title = $p['title'] ?? '';
                        $desc = $p['description'] ?? '';
                        $price = $p['price'] ?? '';
                        $dur = $p['duration'] ?? '';
                        $pkg_lines[] = trim($title . ' | ' . $desc . ' | ' . $price . ' | ' . $dur);
                    } else {
                        $pkg_lines[] = (string)$p;
                    }
                }
                $agencyData['packages'] = implode("\n", $pkg_lines);
            }
        }
        
        if (!empty($agencyData['transportation_routes'])) {
            $decoded_routes = json_decode($agencyData['transportation_routes'], true);
            if (is_array($decoded_routes)) {
                $transportation_routes = $decoded_routes;
            }
        }
    }
}

// Get nearby places and payment options for form display
$nearby_places_str = '';
$payment_options_str = '';
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT nearby_places, payment_options FROM travel_agencies WHERE id=?");
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
        if (!empty($row['payment_options'])) {
            $decoded_payments = json_decode($row['payment_options'], true);
            if (is_array($decoded_payments)) {
                $payment_options_str = implode(', ', $decoded_payments);
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
    <title>Travel Agencies Management</title>
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
            <h1 class="page-title">Travel Agencies Management</h1>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#agencyModal">
                <i class="bi bi-plus-lg"></i> Add Travel Agency
            </button>
        </div>
        
        <?php if ($agencies->num_rows == 0): ?>
            <div class="alert alert-info">
                No travel agencies found. Click "Add Travel Agency" to create one.
            </div>
        <?php else: ?>
            <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-4">
                <?php while ($agency = $agencies->fetch_assoc()): ?>
                    <div class="col">
                        <div class="card h-100">
                            <?php if (!empty($agency['image'])): ?>
                                <img src="<?= htmlspecialchars($agency['image']) ?>" class="card-img-top" alt="<?= htmlspecialchars($agency['name']) ?>">
                            <?php else: ?>
                                <div class="card-img-top bg-light d-flex align-items-center justify-content-center">
                                    <i class="bi bi-airplane fs-1 text-muted"></i>
                                </div>
                            <?php endif; ?>
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?= htmlspecialchars($agency['name']) ?></h5>
                                <p class="card-text flex-grow-1"><?= substr(htmlspecialchars($agency['description']), 0, 100) . '...' ?></p>
                                <?php if (!empty($agency['years'])): ?>
                                    <p class="text-primary fw-bold"><?= htmlspecialchars($agency['years']) ?> years in service</p>
                                <?php endif; ?>
                                <p class="card-text"><small class="text-muted"><?= htmlspecialchars($agency['location']) ?></small></p>
                                <div class="d-flex justify-content-between">
                                    <a href="add_travel_agencies.php?edit=<?= $agency['id'] ?>" class="btn btn-primary btn-sm">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    <a href="?delete=<?= $agency['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this travel agency?')">
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
    <div class="modal fade <?= isset($_GET['edit']) ? 'show d-block' : '' ?>" id="agencyModal" tabindex="-1" aria-labelledby="agencyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="agencyModalLabel"><?= $agencyData['id'] ? 'Edit Travel Agency' : 'Add Travel Agency' ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="add_travel_agencies.php" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="<?= htmlspecialchars($agencyData['id']) ?>">
                        
                        <div class="form-section">
                            <h5>Basic Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Agency Name *</label>
                                        <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($agencyData['name']) ?>" >
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Years in Service</label>
                                        <input type="text" class="form-control" name="years" value="<?= htmlspecialchars($agencyData['years']) ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Location *</label>
                                        <textarea class="form-control" name="location" rows="3"><?= htmlspecialchars($agencyData['location']) ?></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Details Page Link:</label>
                                        <input type="text" class="form-control" name="details_link" value="<?= htmlspecialchars($agencyData['details_link']) ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Upload Card Image:</label>
                                        <input type="file" class="form-control" name="image" accept="image/*">
                                        
                                        <?php if (!empty($agencyData['image'])): ?>
                                            <div class="mt-2">
                                                <img src="<?= htmlspecialchars($agencyData['image']) ?>" class="img-thumbnail" width="100">
                                                <div class="form-check mt-1">
                                                    <input class="form-check-input" type="checkbox" name="delete_image" value="1" id="deleteImageCheck">
                                                    <label class="form-check-label" for="deleteImageCheck">Delete current image</label>
                                                </div>
                                            </div>
                                            <input type="hidden" name="existing_image" value="<?= htmlspecialchars($agencyData['image']) ?>">
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Description *</label>
                                <textarea class="form-control" name="description" rows="4"><?= htmlspecialchars($agencyData['description']) ?></textarea>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h5>Images</h5>
                            <div class="mb-3">
                                <label class="form-label">Detail Images (2-5 recommended)</label>
                                <input type="file" class="form-control" name="image_detail[]" multiple accept="image/*">
                                
                                <?php if (!empty($agencyData['image_detail'])): ?>
                                    <div class="image-preview-container mt-2">
                                        <?php 
                                            $images = explode(',', $agencyData['image_detail']);
                                            foreach ($images as $img): 
                                                if (!empty($img)):
                                        ?>
                                            <div class="image-preview">
                                                <img src="<?= htmlspecialchars($img) ?>" alt="Detail image">
                                                <div class="delete-image-btn" 
                                                     onclick="if(confirm('Delete this image?')) window.location.href='?delete_image=<?= urlencode($img) ?>&agency_id=<?= $agencyData['id'] ?>'">
                                                    ×
                                                </div>
                                            </div>
                                        <?php 
                                                endif;
                                            endforeach; 
                                        ?>
                                        <input type="hidden" name="existing_image_detail" value="<?= htmlspecialchars($agencyData['image_detail']) ?>">
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
                                                    <input type="text" class="form-control" name="route_name[]" value="<?php echo isset($transportation_routes[$i]['name']) ? htmlspecialchars($transportation_routes[$i]['name']) : ''; ?>" placeholder="e.g., Route 10, Terminal A to B Beach">
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control" name="route_link[]" value="<?php echo isset($transportation_routes[$i]['link']) ? htmlspecialchars($transportation_routes[$i]['link']) : ''; ?>" placeholder="https://maps.google.com/...">
                                                </td>
                                                <td>
                                                    <textarea class="form-control" name="route_description[]" rows="2" placeholder="e.g., Daily trips, Air-conditioned, Terminal fee included"><?php echo isset($transportation_routes[$i]['description']) ? htmlspecialchars($transportation_routes[$i]['description']) : ''; ?></textarea>
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
                            <h5>Agency Services</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Services Offered (one per line):</label>
                                        <textarea class="form-control" name="services" rows="5" placeholder="Tour Packages
Hotel Booking
Transportation
Guided Tours"><?= htmlspecialchars($agencyData['services']) ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Travel Packages (one per line or JSON):</label>
                                        <textarea class="form-control" name="packages" rows="5" placeholder="Beach Tour | Enjoy beautiful beaches | $100 | 1 day
City Tour | Explore the city | $50 | 4 hours"><?= htmlspecialchars($agencyData['packages']) ?></textarea>
                                        <small class="text-muted">Enter one package per line using pipe separators: title | description | price | duration.</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Safety Measures (one per line):</label>
                                        <textarea class="form-control" name="safety_measures" rows="5" placeholder="Sanitized vehicles
Trained guides
Emergency protocols"><?= htmlspecialchars($agencyData['safety_measures']) ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Booking Requirements (one per line):</label>
                                        <textarea class="form-control" name="requirements" rows="5" placeholder="Valid ID
Deposit payment
Signed waiver"><?= htmlspecialchars($agencyData['requirements']) ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h5>Contact & Business Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Operating Hours (Format: Day: Time):</label>
                                        <textarea class="form-control" name="hours" rows="5" placeholder="Monday: 9:00 AM - 5:00 PM
Tuesday: 9:00 AM - 5:00 PM"><?= htmlspecialchars($agencyData['hours']) ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Contact Information</label>
                                        <textarea class="form-control" name="contact" rows="3"><?= htmlspecialchars($agencyData['contact']) ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($agencyData['email']) ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Website</label>
                                        <input type="url" class="form-control" name="website" value="<?= htmlspecialchars($agencyData['website']) ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Cancellation Policy</label>
                                        <textarea class="form-control" name="cancellation_policy" rows="3"><?= htmlspecialchars($agencyData['cancellation_policy']) ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Payment Options (comma separated):</label>
                                        <input name="payment_options" class="form-control" value="<?= $payment_options_str ?>" placeholder="Cash, Credit Card, PayPal, Bank Transfer, GCash">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Social Media Links (one per line):</label>
                                        <textarea class="form-control" name="social_media" rows="3" placeholder="https://facebook.com/youragency
https://instagram.com/youragency"><?= htmlspecialchars($agencyData['social_media']) ?></textarea>
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
                                        <input type="url" class="form-control" name="maps_link" value="<?= htmlspecialchars($agencyData['maps_link']) ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Google Maps Directions Link</label>
                                        <input type="text" class="form-control" name="directions_link" value="<?= htmlspecialchars($agencyData['directions_link']) ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Embed Google Map URL</label>
                                        <input type="url" class="form-control" name="maps_embed" value="<?= htmlspecialchars($agencyData['maps_embed']) ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Nearby Places (comma separated):</label>
                                        <input name="nearby_places" class="form-control" value="<?= $nearby_places_str ?>" placeholder="Molo Church, SM City Iloilo, Esplanade, Terminal">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <?= $agencyData['id'] ? 'Update Travel Agency' : 'Save Travel Agency' ?>
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
                var modal = new bootstrap.Modal(document.getElementById('agencyModal'));
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
                    <input type="text" class="form-control" name="route_name[]" placeholder="e.g., Route 10, Terminal A to B Beach">
                </td>
                <td>
                    <input type="text" class="form-control" name="route_link[]" placeholder="https://maps.google.com/...">
                </td>
                <td>
                    <textarea class="form-control" name="route_description[]" rows="2" placeholder="e.g., Daily trips, Air-conditioned, Terminal fee included"></textarea>
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