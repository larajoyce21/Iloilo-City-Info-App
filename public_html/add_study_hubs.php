<?php
session_start();
include 'conn.php';

if (!file_exists('uploads/study_hubs/')) {
    mkdir('uploads/study_hubs/', 0777, true);
}

if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    
    $stmt = $conn->prepare("SELECT image, image_detail FROM study_hubs WHERE id=?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $hub = $result->fetch_assoc();
    
    $stmt = $conn->prepare("DELETE FROM study_hubs WHERE id=?");
    $stmt->bind_param("i", $delete_id);
    
    if ($stmt->execute()) {
        if ($hub) {
            if (!empty($hub['image']) && file_exists($hub['image'])) {
                unlink($hub['image']);
            }
            
            if (!empty($hub['image_detail'])) {
                $images = explode(',', $hub['image_detail']);
                foreach ($images as $img) {
                    if (!empty($img) && file_exists($img)) {
                        unlink($img);
                    }
                }
            }
        }
        
        $_SESSION['message'] = "Study Hub deleted successfully";
    } else {
        $_SESSION['error'] = "Error deleting study hub: " . $conn->error;
    }
    
    header("Location: add_study_hubs.php");
    exit();
}

if (isset($_GET['delete_image']) && isset($_GET['hub_id'])) {
    $image_path = urldecode($_GET['delete_image']);
    $hub_id = (int)$_GET['hub_id'];
    
    if (file_exists($image_path)) {
        unlink($image_path);
    }
    
    $stmt = $conn->prepare("SELECT image_detail FROM study_hubs WHERE id=?");
    $stmt->bind_param("i", $hub_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $hub = $result->fetch_assoc();
    
    if ($hub) {
        $images = explode(',', $hub['image_detail']);
        $updated_images = array();
        foreach ($images as $img) {
            if (trim($img) != trim($image_path)) {
                $updated_images[] = $img;
            }
        }
        $updated_images_str = implode(',', $updated_images);
        
        $stmt = $conn->prepare("UPDATE study_hubs SET image_detail=? WHERE id=?");
        $stmt->bind_param("si", $updated_images_str, $hub_id);
        $stmt->execute();
    }
    
    $_SESSION['message'] = "Image deleted successfully";
    header("Location: add_study_hubs.php?edit=" . $hub_id);
    exit();
}

$query = "SELECT id, name, image, description, location, type, capacity FROM study_hubs ORDER BY name";
$stmt = $conn->prepare($query);
$stmt->execute();
$hubs = $stmt->get_result();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $hours = trim($_POST['hours'] ?? '');
    $contact = trim($_POST['contact'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $website = trim($_POST['website'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $maps_link = trim($_POST['maps_link'] ?? '');
    $directions_link = trim($_POST['directions_link'] ?? '');
    $mapEmbedURL = trim($_POST['map_embed'] ?? '');
    $social_media = trim($_POST['social_media'] ?? '');
    $details_link = trim($_POST['details_link'] ?? '');
    $capacity = trim($_POST['capacity'] ?? '');
    $type = trim($_POST['type'] ?? '');

    // Handle transportation routes
    $transportation_routes = array();
    
    // Process table rows
    if (isset($_POST['route_type']) && is_array($_POST['route_type'])) {
        $route_count = count($_POST['route_type']);
        
        for ($i = 0; $i < $route_count; $i++) {
            $route_type = trim($_POST['route_type'][$i] ?? '');
            $name_route = trim($_POST['route_name'][$i] ?? '');
            
            if (!empty($route_type) && !empty($name_route)) {
                $transportation_routes[] = array(
                    'type' => $route_type,
                    'name' => $name_route,
                    'link' => trim($_POST['route_link'][$i] ?? ''),
                    'description' => trim($_POST['route_description'][$i] ?? '')
                );
            }
        }
    }
    
    $transportation_routes_json = json_encode($transportation_routes);

    $amenities = trim($_POST['amenities'] ?? '');
    $amenities_array = array_map('trim', explode(',', $amenities));
    $amenities_json = json_encode(array_filter($amenities_array));

    $nearby_places = trim($_POST['nearby_places'] ?? '');
    $nearby_places_array = array_map('trim', explode(',', $nearby_places));
    $nearby_places_json = json_encode(array_filter($nearby_places_array));

    $target_dir = "uploads/study_hubs/";
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
            header("Location: add_study_hubs.php" . ($id ? "?edit=$id" : ""));
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
        $query = "UPDATE study_hubs SET 
                  name=?, image=?, image_detail=?, description=?, location=?, 
                  maps_embed=?, maps_link=?, website=?, directions_link=?, hours=?, 
                  contact=?, amenities=?, nearby_places=?, social_media=?, 
                  details_link=?, capacity=?, type=?, email=?, transportation_routes=?
                  WHERE id=?";
        $stmt = $conn->prepare($query);
        // 20 parameters: 19 for SET + 1 for WHERE = 20 total, all 's' types except last 'i' for id
        $stmt->bind_param("sssssssssssssssssssi", 
            $name, $target_file, $target_file_detail, $description, $location, 
            $mapEmbedURL, $maps_link, $website, $directions_link, $hours, 
            $contact, $amenities_json, $nearby_places_json, $social_media, 
            $details_link, $capacity, $type, $email, $transportation_routes_json, $id);
    } else {
        $query = "INSERT INTO study_hubs 
                  (name, image, image_detail, description, location, maps_embed, 
                   maps_link, website, directions_link, hours, contact, amenities, 
                   nearby_places, social_media, details_link, capacity, type, email, 
                   transportation_routes) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        // 19 parameters, all 's' types
        $stmt->bind_param("sssssssssssssssssss", 
            $name, $target_file, $target_file_detail, $description, $location, 
            $mapEmbedURL, $maps_link, $website, $directions_link, $hours, 
            $contact, $amenities_json, $nearby_places_json, $social_media, 
            $details_link, $capacity, $type, $email, $transportation_routes_json);
    }
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Study Hub " . ($id ? "updated" : "added") . " successfully";
    } else {
        $_SESSION['error'] = "Error saving study hub: " . $conn->error;
    }
    
    header("Location: add_study_hubs.php");
    exit();
}

$amenities_str = '';
$nearby_places_str = '';
$transportation_routes = array();
$route_count = 1;

if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT amenities, nearby_places, transportation_routes FROM study_hubs WHERE id=?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (!empty($row['amenities'])) {
            $decoded_amenities = json_decode($row['amenities'], true);
            if (is_array($decoded_amenities)) {
                $amenities_str = implode(', ', $decoded_amenities);
            }
        }
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
                $route_count = count($transportation_routes);
            }
        }
    }
}

if ($route_count == 0) {
    $route_count = 1; // Start with one empty row
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Study Hubs Management</title>
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
                        <?= htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?>
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
                        <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <button type="button" class="btn btn-light text-dark mb-3" onclick="window.location.href='dashboard.php'">
            <i class="bi bi-arrow-left"></i> Back to Dashboard
        </button>
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="page-title">Study Hubs & Co-working Spaces Management</h1>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#hubModal">
                <i class="bi bi-plus-lg"></i> Add Study Hub
            </button>
        </div>
        
        <?php if ($hubs->num_rows == 0): ?>
            <div class="alert alert-info">
                No study hubs found. Click "Add Study Hub" to create one.
            </div>
        <?php else: ?>
            <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-4">
                <?php while ($hub = $hubs->fetch_assoc()): ?>
                    <div class="col">
                        <div class="card h-100">
                            <?php if (!empty($hub['image'])): ?>
                                <img src="<?= htmlspecialchars($hub['image']) ?>" class="card-img-top" alt="<?= htmlspecialchars($hub['name']) ?>">
                            <?php else: ?>
                                <div class="card-img-top bg-light d-flex align-items-center justify-content-center">
                                    <i class="bi bi-book fs-1 text-muted"></i>
                                </div>
                            <?php endif; ?>
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?= htmlspecialchars($hub['name']) ?></h5>
                                <p class="card-text flex-grow-1"><?= htmlspecialchars(substr($hub['description'], 0, 100)) . '...' ?></p>
                                <?php if (!empty($hub['type'])): ?>
                                    <p class="text-primary fw-bold"><?= htmlspecialchars($hub['type']) ?></p>
                                <?php endif; ?>
                                <?php if (!empty($hub['capacity'])): ?>
                                    <p class="text-muted">Capacity: <?= htmlspecialchars($hub['capacity']) ?> people</p>
                                <?php endif; ?>
                                <p class="card-text"><small class="text-muted"><?= htmlspecialchars($hub['location']) ?></small></p>
                                <div class="d-flex justify-content-between">
                                    <a href="add_study_hubs.php?edit=<?= $hub['id'] ?>" class="btn btn-primary btn-sm">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    <a href="?delete=<?= $hub['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this study hub?')">
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

    <div class="modal fade <?= isset($_GET['edit']) ? 'show d-block' : '' ?>" id="hubModal" tabindex="-1" aria-labelledby="hubModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="hubModalLabel"><?= isset($_GET['edit']) ? 'Edit Study Hub' : 'Add Study Hub' ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php 
                        $hubData = [
                            'id' => '',
                            'name' => '',
                            'description' => '',
                            'location' => '',
                            'website' => '',
                            'email' => '',
                            'image' => '',
                            'image_detail' => '',
                            'maps_embed' => '',
                            'maps_link' => '',
                            'directions_link' => '',
                            'hours' => '',
                            'contact' => '',
                            'amenities' => '',
                            'nearby_places' => '',
                            'social_media' => '',
                            'details_link' => '',
                            'capacity' => '',
                            'type' => '',
                            'transportation_routes' => ''
                        ];
                        
                        if (isset($_GET['edit'])) {
                            $edit_id = (int)$_GET['edit'];
                            $stmt = $conn->prepare("SELECT * FROM study_hubs WHERE id=?");
                            $stmt->bind_param("i", $edit_id);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            if ($result->num_rows > 0) {
                                $hubData = $result->fetch_assoc();
                            }
                        }
                    ?>
                    <form method="POST" action="add_study_hubs.php" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="<?= htmlspecialchars($hubData['id']) ?>">
                        
                        <div class="form-section">
                            <h5>Basic Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Name *</label>
                                        <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($hubData['name']) ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Type</label>
                                        <select class="form-control" name="type">
                                            <option value="">Select Type</option>
                                            <option value="Study Hub" <?= ($hubData['type'] == 'Study Hub') ? 'selected' : '' ?>>Study Hub</option>
                                            <option value="Co-working Space" <?= ($hubData['type'] == 'Co-working Space') ? 'selected' : '' ?>>Co-working Space</option>
                                            <option value="Both" <?= ($hubData['type'] == 'Both') ? 'selected' : '' ?>>Both</option>
                                            <option value="Library" <?= ($hubData['type'] == 'Library') ? 'selected' : '' ?>>Library</option>
                                            <option value="Cafe with Study Area" <?= ($hubData['type'] == 'Cafe with Study Area') ? 'selected' : '' ?>>Cafe with Study Area</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Capacity</label>
                                        <input type="number" class="form-control" name="capacity" value="<?= htmlspecialchars($hubData['capacity']) ?>" placeholder="Maximum number of people">
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Description *</label>
                                        <textarea class="form-control" name="description" rows="4" required><?= htmlspecialchars($hubData['description']) ?></textarea>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Operating Hours</label>
                                        <textarea class="form-control" name="hours" rows="5" placeholder="e.g., Monday: 9AM - 5PM"><?= htmlspecialchars($hubData['hours']) ?></textarea>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Contact Information</label>
                                        <input type="text" class="form-control" name="contact" value="<?= htmlspecialchars($hubData['contact']) ?>" placeholder="Phone number">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Email Address</label>
                                        <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($hubData['email']) ?>" placeholder="Email address">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Details Link:</label>
                                        <input type="text" class="form-control" name="details_link" value="<?= htmlspecialchars($hubData['details_link']) ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h5>Images</h5>
                            <div class="mb-3">
                                <label class="form-label">Upload Card Image:</label>
                                <input type="file" class="form-control" name="image">
                                
                                <?php if (!empty($hubData['image'])): ?>
                                    <div class="mt-2">
                                        <img src="<?= htmlspecialchars($hubData['image']) ?>" class="img-thumbnail" width="100">
                                        <div class="form-check mt-1">
                                            <input class="form-check-input" type="checkbox" name="delete_image" value="1" id="deleteImageCheck">
                                            <label class="form-check-label" for="deleteImageCheck">Delete current image</label>
                                        </div>
                                    </div>
                                    <input type="hidden" name="existing_image" value="<?= htmlspecialchars($hubData['image']) ?>">
                                <?php endif; ?>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Upload Detail Images (2-5):</label>
                                <input type="file" class="form-control" name="image_detail[]" multiple accept="image/*">
                                <?php if (!empty($hubData['image_detail'])) {
                                    $images = explode(',', $hubData['image_detail']); 
                                    echo '<div class="image-preview-container mt-2">';
                                    foreach ($images as $img) {
                                        if (!empty($img)) { ?>
                                            <div class="image-preview">
                                                <img src="<?= htmlspecialchars($img) ?>" alt="Detail image">
                                                <div class="delete-image-btn" 
                                                     onclick="if(confirm('Delete this image?')) window.location.href='?delete_image=<?= urlencode($img) ?>&hub_id=<?= $hubData['id'] ?>'">
                                                    ×
                                                </div>
                                            </div>
                                        <?php }
                                    }
                                    echo '</div>'; ?>
                                    <input type="hidden" name="existing_image_detail" value="<?= htmlspecialchars($hubData['image_detail']) ?>">
                                <?php } ?>
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
                                                    <input type="text" class="form-control" name="route_name[]" value="<?php echo isset($transportation_routes[$i]['name']) ? htmlspecialchars($transportation_routes[$i]['name']) : ''; ?>" placeholder="e.g., Route 101, LRT-1, MRT-3, Grab">
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control" name="route_link[]" value="<?php echo isset($transportation_routes[$i]['link']) ? htmlspecialchars($transportation_routes[$i]['link']) : ''; ?>" placeholder="https://maps.google.com/...">
                                                </td>
                                                <td>
                                                    <textarea class="form-control" name="route_description[]" rows="2" placeholder="e.g., Nearest transport stop, walking distance from station"><?php echo isset($transportation_routes[$i]['description']) ? htmlspecialchars($transportation_routes[$i]['description']) : ''; ?></textarea>
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
                            <h5>Amenities & Features</h5>
                            <div class="mb-3">
                                <label class="form-label">Amenities:</label>
                                <input name="amenities" class="form-control" value="<?= htmlspecialchars($amenities_str) ?>" placeholder="Add amenities separated by commas">
                                <small class="text-muted">Example: WiFi, Printing, Coffee, Meeting Rooms, Air Conditioning, Power Outlets, Quiet Zones</small>
                            </div>
                        </div>

                        <div class="form-section">
                            <h5>Location Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Address *</label>
                                        <textarea class="form-control" name="location" rows="3" required><?= htmlspecialchars($hubData['location']) ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Website URL</label>
                                        <input type="url" class="form-control" name="website" value="<?= htmlspecialchars($hubData['website']) ?>" placeholder="https://example.com">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Nearby Places:</label>
                                        <input name="nearby_places" class="form-control" value="<?= htmlspecialchars($nearby_places_str) ?>" placeholder="Add nearby places separated by commas">
                                        <small class="text-muted">Example: Coffee Shop, Library, Restaurant, Convenience Store</small>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Google Maps Directions Link</label>
                                        <input type="url" class="form-control" name="directions_link" value="<?= htmlspecialchars($hubData['directions_link']) ?>" placeholder="https://maps.google.com/...">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Google Map Link</label>
                                        <input type="url" class="form-control" name="maps_link" value="<?= htmlspecialchars($hubData['maps_link']) ?>" placeholder="https://maps.google.com/...">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Embed Google Map URL</label>
                                <input type="url" class="form-control" name="map_embed" value="<?= htmlspecialchars($hubData['maps_embed']) ?>" placeholder="https://maps.google.com/embed...">
                            </div>
                        </div>

                        <div class="form-section">
                            <div class="mb-3">
                                <label class="form-label">Social Media Links</label>
                                <textarea class="form-control" name="social_media" rows="5" placeholder="Enter one URL per line, e.g. https://facebook.com/hub"><?= htmlspecialchars($hubData['social_media']) ?></textarea>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <?= isset($_GET['edit']) ? 'Update Study Hub' : 'Save Study Hub' ?>
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
            var toasts = document.querySelectorAll('.toast');
            toasts.forEach(function(toast) {
                setTimeout(function() {
                    var bsToast = new bootstrap.Toast(toast);
                    bsToast.hide();
                }, 5000);
            });
            
            if (window.location.search.includes('edit')) {
                var modal = new bootstrap.Modal(document.getElementById('hubModal'));
                modal.show();
                
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
                        <option value="jeepney">Jeepney</option>
                        <option value="bus">Bus</option>
                        <option value="tricycle">Tricycle</option>
                        <option value="taxi">Taxi</option>
                    </select>
                </td>
                <td>
                    <input type="text" class="form-control" name="route_name[]" placeholder="e.g., Route 101, LRT-1, MRT-3, Grab">
                </td>
                <td>
                    <input type="text" class="form-control" name="route_link[]" placeholder="https://maps.google.com/...">
                </td>
                <td>
                    <textarea class="form-control" name="route_description[]" rows="2" placeholder="e.g., Nearest transport stop, walking distance from station"></textarea>
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