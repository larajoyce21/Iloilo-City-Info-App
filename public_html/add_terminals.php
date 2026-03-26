<?php
session_start();
include "conn.php";

$target_dir = "uploads/terminals/";
if (!file_exists($target_dir)) {
    mkdir($target_dir, 0777, true);
}

if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    
    $stmt = $conn->prepare("SELECT image, image_detail FROM terminals WHERE id=?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $terminal = $result->fetch_assoc();
    
    $stmt = $conn->prepare("DELETE FROM terminals WHERE id=?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    
    if ($terminal) {
        if (!empty($terminal['image']) && file_exists($terminal['image'])) {
            unlink($terminal['image']);
        }
        
        if (!empty($terminal['image_detail'])) {
            $images = explode(',', $terminal['image_detail']);
            foreach ($images as $img) {
                if (!empty($img) && file_exists($img)) {
                    unlink($img);
                }
            }
        }
    }
    
    $_SESSION['message'] = "Terminal deleted successfully";
    header("Location: add_terminals.php");
    exit();
}

if (isset($_GET['delete_image']) && isset($_GET['terminal_id'])) {
    $image_path = $_GET['delete_image'];
    $terminal_id = (int)$_GET['terminal_id'];
    
    if (file_exists($image_path)) {
        unlink($image_path);
    }
    
    $stmt = $conn->prepare("SELECT image_detail FROM terminals WHERE id=?");
    $stmt->bind_param("i", $terminal_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $terminal = $result->fetch_assoc();
    
    if ($terminal) {
        $images = explode(',', $terminal['image_detail']);
        $updated_images = array_diff($images, [$image_path]);
        $updated_images_str = implode(',', $updated_images);
        
        $stmt = $conn->prepare("UPDATE terminals SET image_detail=? WHERE id=?");
        $stmt->bind_param("si", $updated_images_str, $terminal_id);
        $stmt->execute();
    }
    
    $_SESSION['message'] = "Image deleted successfully";
    header("Location: add_terminals.php?edit=" . $terminal_id);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $location = trim($_POST['location']);
    $maps_link = trim($_POST['maps_link']);
    $routes = trim($_POST['routes']);
    $directions_link = trim($_POST['directions_link']);
    $map_embed = trim($_POST['map_embed']);
    $hours = trim($_POST['hours']);
    $contact = trim($_POST['contact']);
    $email = trim($_POST['email']);
    $social_media = trim($_POST['social_media']);
    $nearby_places = trim($_POST['nearby_places']);
    $getting_there = trim($_POST['getting_there']);
    $travel_tips = trim($_POST['travel_tips']);
    $services = trim($_POST['services']);
    $transport_companies = trim($_POST['transport_companies']);
    $type = trim($_POST['type']);
    $primary_services = trim($_POST['primary_services']);
    $destinations = trim($_POST['destinations']);
    $phone = trim($_POST['phone']);
    $details_link = trim($_POST['details_link']);

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
            header("Location: add_terminals.php" . ($id ? "?edit=$id" : ""));
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
        $query = "UPDATE terminals SET 
                  name=?, description=?, location=?, maps_link=?, 
                  routes=?, directions_link=?, map_embed=?, hours=?, 
                  contact=?, email=?, social_media=?, nearby_places=?, 
                  getting_there=?, travel_tips=?, services=?, transport_companies=?, 
                  type=?, primary_services=?, destinations=?, phone=?, 
                  details_link=?, image=?, image_detail=?, transportation_routes=?
                  WHERE id=?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssssssssssssssssssssssi", 
            $name, $description, $location, $maps_link, 
            $routes, $directions_link, $map_embed, $hours, 
            $contact, $email, $social_media, $nearby_places, 
            $getting_there, $travel_tips, $services, $transport_companies, 
            $type, $primary_services, $destinations, $phone, 
            $details_link, $target_file, $target_file_detail, $transportation_routes_json, $id);
    } else {
        $query = "INSERT INTO terminals (
                  name, description, location, maps_link, routes, directions_link, 
                  map_embed, hours, contact, email, social_media, nearby_places, 
                  getting_there, travel_tips, services, transport_companies, 
                  type, primary_services, destinations, phone, details_link, 
                  image, image_detail, transportation_routes
                  ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssssssssssssssssssssss", 
            $name, $description, $location, $maps_link, 
            $routes, $directions_link, $map_embed, $hours, 
            $contact, $email, $social_media, $nearby_places, 
            $getting_there, $travel_tips, $services, $transport_companies, 
            $type, $primary_services, $destinations, $phone, 
            $details_link, $target_file, $target_file_detail, $transportation_routes_json);
    }
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Terminal " . ($id ? "updated" : "added") . " successfully";
    } else {
        $_SESSION['error'] = "Error saving terminal: " . $conn->error;
    }
    
    header("Location: add_terminals.php");
    exit();
}

$query = "SELECT id, name, image, description, location, type FROM terminals ORDER BY name";
$terminals = $conn->query($query);

$terminalData = [
    'id' => '', 'name' => '', 'description' => '', 'location' => '', 'maps_link' => '', 
    'routes' => '', 'directions_link' => '', 'map_embed' => '', 'hours' => '', 
    'contact' => '', 'email' => '', 'social_media' => '', 'nearby_places' => '', 
    'getting_there' => '', 'travel_tips' => '', 'services' => '', 'transport_companies' => '',
    'type' => '', 'primary_services' => '', 'destinations' => '', 'phone' => '',
    'details_link' => '', 'image' => '', 'image_detail' => '', 'transportation_routes' => ''
];

$transportation_routes = array();

if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT *, transportation_routes FROM terminals WHERE id=?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $terminalData = $result->fetch_assoc();
        if (!empty($terminalData['transportation_routes'])) {
            $decoded_routes = json_decode($terminalData['transportation_routes'], true);
            if (is_array($decoded_routes)) {
                $transportation_routes = $decoded_routes;
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
    <title>Terminals Management</title>
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
            <h1 class="page-title">Terminals Management</h1>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#terminalModal">
                <i class="bi bi-plus-lg"></i> Add Terminal
            </button>
        </div>
        
        <?php if ($terminals->num_rows == 0): ?>
            <div class="alert alert-info">
                No terminals found. Click "Add Terminal" to create one.
            </div>
        <?php else: ?>
            <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-4">
                <?php while ($terminal = $terminals->fetch_assoc()): ?>
                    <div class="col">
                        <div class="card h-100">
                            <?php if (!empty($terminal['image'])): ?>
                                <img src="<?= htmlspecialchars($terminal['image']) ?>" class="card-img-top" alt="<?= htmlspecialchars($terminal['name']) ?>">
                            <?php else: ?>
                                <div class="card-img-top bg-light d-flex align-items-center justify-content-center">
                                    <i class="bi bi-bus-front fs-1 text-muted"></i>
                                </div>
                            <?php endif; ?>
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?= htmlspecialchars($terminal['name']) ?></h5>
                                <p class="card-text flex-grow-1"><?= substr(htmlspecialchars($terminal['description']), 0, 100) . '...' ?></p>
                                <?php if (!empty($terminal['type'])): ?>
                                    <p class="text-primary fw-bold"><?= htmlspecialchars($terminal['type']) ?></p>
                                <?php endif; ?>
                                <p class="card-text"><small class="text-muted"><?= htmlspecialchars($terminal['location']) ?></small></p>
                                <div class="d-flex justify-content-between">
                                    <a href="add_terminals.php?edit=<?= $terminal['id'] ?>" class="btn btn-primary btn-sm">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    <a href="?delete=<?= $terminal['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this terminal?')">
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

    <!-- Add/Edit Terminal Modal -->
    <div class="modal fade <?= isset($_GET['edit']) ? 'show d-block' : '' ?>" id="terminalModal" tabindex="-1" aria-labelledby="terminalModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="terminalModalLabel"><?= $terminalData['id'] ? 'Edit Terminal' : 'Add Terminal' ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="add_terminals.php" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="<?= htmlspecialchars($terminalData['id']) ?>">
                        
                        <div class="form-section">
                            <h5>Basic Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Name *</label>
                                        <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($terminalData['name']) ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Location *</label>
                                        <textarea class="form-control" name="location" rows="3" required><?= htmlspecialchars($terminalData['location']) ?></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Terminal Type</label>
                                        <input type="text" class="form-control" name="type" value="<?= htmlspecialchars($terminalData['type']) ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Primary Services</label>
                                        <input type="text" class="form-control" name="primary_services" value="<?= htmlspecialchars($terminalData['primary_services']) ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Upload Card Image:</label>
                                        <input type="file" class="form-control" name="image" accept="image/*">
                                        
                                        <?php if (!empty($terminalData['image'])): ?>
                                            <div class="mt-2">
                                                <img src="<?= htmlspecialchars($terminalData['image']) ?>" class="img-thumbnail" width="100">
                                                <div class="form-check mt-1">
                                                    <input class="form-check-input" type="checkbox" name="delete_image" value="1" id="deleteImageCheck">
                                                    <label class="form-check-label" for="deleteImageCheck">Delete current image</label>
                                                </div>
                                            </div>
                                            <input type="hidden" name="existing_image" value="<?= htmlspecialchars($terminalData['image']) ?>">
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Description *</label>
                                <textarea class="form-control" name="description" rows="4" required><?= htmlspecialchars($terminalData['description']) ?></textarea>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h5>Images</h5>
                            <div class="mb-3">
                                <label class="form-label">Detail Images (2-5 recommended)</label>
                                <input type="file" class="form-control" name="image_detail[]" multiple accept="image/*">
                                
                                <?php if (!empty($terminalData['image_detail'])): ?>
                                    <div class="image-preview-container mt-2">
                                        <?php 
                                            $images = explode(',', $terminalData['image_detail']);
                                            foreach ($images as $img): 
                                                if (!empty($img)):
                                        ?>
                                            <div class="image-preview">
                                                <img src="<?= htmlspecialchars($img) ?>" alt="Detail image">
                                                <div class="delete-image-btn" 
                                                     onclick="if(confirm('Delete this image?')) window.location.href='?delete_image=<?= urlencode($img) ?>&terminal_id=<?= $terminalData['id'] ?>'">
                                                    ×
                                                </div>
                                            </div>
                                        <?php 
                                                endif;
                                            endforeach; 
                                        ?>
                                        <input type="hidden" name="existing_image_detail" value="<?= htmlspecialchars($terminalData['image_detail']) ?>">
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
                                                        <option value="van" <?php echo (isset($transportation_routes[$i]['type']) && $transportation_routes[$i]['type'] == 'van') ? 'selected' : ''; ?>>Van</option>
                                                        <option value="tricycle" <?php echo (isset($transportation_routes[$i]['type']) && $transportation_routes[$i]['type'] == 'tricycle') ? 'selected' : ''; ?>>Tricycle</option>
                                                        <option value="taxi" <?php echo (isset($transportation_routes[$i]['type']) && $transportation_routes[$i]['type'] == 'taxi') ? 'selected' : ''; ?>>Taxi</option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control" name="route_name[]" value="<?php echo isset($transportation_routes[$i]['name']) ? htmlspecialchars($transportation_routes[$i]['name']) : ''; ?>" placeholder="e.g., Bus Route 101, Jeepney Route 5">
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control" name="route_link[]" value="<?php echo isset($transportation_routes[$i]['link']) ? htmlspecialchars($transportation_routes[$i]['link']) : ''; ?>" placeholder="https://maps.google.com/...">
                                                </td>
                                                <td>
                                                    <textarea class="form-control" name="route_description[]" rows="2" placeholder="e.g., Main route to downtown, frequency: every 30 minutes"><?php echo isset($transportation_routes[$i]['description']) ? htmlspecialchars($transportation_routes[$i]['description']) : ''; ?></textarea>
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
                            <h5>Terminal Services & Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Operating Hours (one per line)</label>
                                        <textarea class="form-control" name="hours" rows="5"><?= htmlspecialchars($terminalData['hours']) ?></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Services (one per line)</label>
                                        <textarea class="form-control" name="services" rows="5"><?= htmlspecialchars($terminalData['services']) ?></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Routes (one per line)</label>
                                        <textarea class="form-control" name="routes" rows="5"><?= htmlspecialchars($terminalData['routes']) ?></textarea>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Transport Companies (one per line)</label>
                                        <textarea class="form-control" name="transport_companies" rows="5"><?= htmlspecialchars($terminalData['transport_companies']) ?></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Destinations</label>
                                        <textarea class="form-control" name="destinations" rows="4"><?= htmlspecialchars($terminalData['destinations']) ?></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Getting There Information</label>
                                        <textarea class="form-control" name="getting_there" rows="3"><?= htmlspecialchars($terminalData['getting_there']) ?></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Travel Tips</label>
                                <textarea class="form-control" name="travel_tips" rows="3"><?= htmlspecialchars($terminalData['travel_tips']) ?></textarea>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h5>Location Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Google Map Link</label>
                                        <input type="url" class="form-control" name="maps_link" value="<?= htmlspecialchars($terminalData['maps_link']) ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Embed Google Map URL</label>
                                        <textarea class="form-control" name="map_embed" rows="3"><?= htmlspecialchars($terminalData['map_embed']) ?></textarea>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Directions Link</label>
                                        <input type="url" class="form-control" name="directions_link" value="<?= htmlspecialchars($terminalData['directions_link']) ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Details Page Link:</label>
                                        <input type="text" class="form-control" name="details_link" value="<?= htmlspecialchars($terminalData['details_link']) ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Nearby Places:</label>
                                        <input type="text" class="form-control" name="nearby_places" value="<?= htmlspecialchars($terminalData['nearby_places']) ?>" placeholder="e.g., Mall, Restaurant, Hotel, Bank">
                                        <small class="text-muted">Separate by commas</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h5>Contact Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Contact Details</label>
                                        <input type="text" class="form-control mb-2" name="contact" value="<?= htmlspecialchars($terminalData['contact']) ?>" placeholder="Contact information">
                                        <input type="text" class="form-control" name="phone" value="<?= htmlspecialchars($terminalData['phone']) ?>" placeholder="Phone number">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($terminalData['email']) ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Social Media Links (one per line)</label>
                                <textarea class="form-control" name="social_media" rows="3"><?= htmlspecialchars($terminalData['social_media']) ?></textarea>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <?= $terminalData['id'] ? 'Update Terminal' : 'Save Terminal' ?>
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
                var modal = new bootstrap.Modal(document.getElementById('terminalModal'));
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
                        <option value="van">Van</option>
                        <option value="tricycle">Tricycle</option>
                        <option value="taxi">Taxi</option>
                    </select>
                </td>
                <td>
                    <input type="text" class="form-control" name="route_name[]" placeholder="e.g., Bus Route 101, Jeepney Route 5">
                </td>
                <td>
                    <input type="text" class="form-control" name="route_link[]" placeholder="https://maps.google.com/...">
                </td>
                <td>
                    <textarea class="form-control" name="route_description[]" rows="2" placeholder="e.g., Main route to downtown, frequency: every 30 minutes"></textarea>
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