<?php
session_start();
include 'conn.php';

if (!file_exists('uploads/souvenirs/')) {
    mkdir('uploads/souvenirs/', 0777, true);
}

if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    
    $stmt = $conn->prepare("SELECT image, image_detail FROM souvenirs WHERE id=?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $souvenir = $result->fetch_assoc();
    
    $stmt = $conn->prepare("DELETE FROM souvenirs WHERE id=?");
    $stmt->bind_param("i", $delete_id);
    
    if ($stmt->execute()) {
        if ($souvenir) {
            if (!empty($souvenir['image']) && file_exists($souvenir['image'])) {
                unlink($souvenir['image']);
            }
            
            if (!empty($souvenir['image_detail'])) {
                $images = explode(',', $souvenir['image_detail']);
                foreach ($images as $img) {
                    if (!empty($img) && file_exists($img)) {
                        unlink($img);
                    }
                }
            }
        }
        
        $_SESSION['message'] = "Souvenir deleted successfully";
    } else {
        $_SESSION['error'] = "Error deleting souvenir: " . $conn->error;
    }
    
    header("Location: add_souvenirs.php");
    exit();
}

if (isset($_GET['delete_image']) && isset($_GET['souvenir_id'])) {
    $image_path = urldecode($_GET['delete_image']);
    $souvenir_id = (int)$_GET['souvenir_id'];
    
    if (file_exists($image_path)) {
        unlink($image_path);
    }
    
    $stmt = $conn->prepare("SELECT image_detail FROM souvenirs WHERE id=?");
    $stmt->bind_param("i", $souvenir_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $souvenir = $result->fetch_assoc();
    
    if ($souvenir) {
        $images = explode(',', $souvenir['image_detail']);
        $updated_images = array();
        foreach ($images as $img) {
            if (trim($img) != trim($image_path)) {
                $updated_images[] = $img;
            }
        }
        $updated_images_str = implode(',', $updated_images);
        
        $stmt = $conn->prepare("UPDATE souvenirs SET image_detail=? WHERE id=?");
        $stmt->bind_param("si", $updated_images_str, $souvenir_id);
        $stmt->execute();
    }
    
    $_SESSION['message'] = "Image deleted successfully";
    header("Location: add_souvenirs.php?edit=" . $souvenir_id);
    exit();
}

$query = "SELECT id, name, image, description, store_name, price_range FROM souvenirs ORDER BY name";
$stmt = $conn->prepare($query);
$stmt->execute();
$souvenirs = $stmt->get_result();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $store_name = trim($_POST['store_name'] ?? '');
    $store_address = trim($_POST['store_address'] ?? '');
    $market_name = trim($_POST['market_name'] ?? '');
    $hours = trim($_POST['hours'] ?? '');
    $contact = trim($_POST['contact'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $social_media = trim($_POST['social_media'] ?? '');
    $directions_link = trim($_POST['directions_link'] ?? '');
    $maps_link = trim($_POST['maps_link'] ?? '');
    $website = trim($_POST['website'] ?? '');
    $map_embed = trim($_POST['map_embed'] ?? '');
    $nearby_places = trim($_POST['nearby_places'] ?? '');
    $details_link = trim($_POST['details_link'] ?? '');
    $significance = trim($_POST['significance'] ?? '');
    $materials = trim($_POST['materials'] ?? '');
    $price_range = trim($_POST['price_range'] ?? '');
    $transportation_options = trim($_POST['transportation_options'] ?? '');

    // Handle transportation routes
    $transportation_routes = array();
    
    // Process table rows
    if (isset($_POST['route_type']) && is_array($_POST['route_type'])) {
        $route_count = count($_POST['route_type']);
        
        for ($i = 0; $i < $route_count; $i++) {
            $type = trim($_POST['route_type'][$i] ?? '');
            $name_route = trim($_POST['route_name'][$i] ?? '');
            
            if (!empty($type) && !empty($name_route)) {
                $transportation_routes[] = array(
                    'type' => $type,
                    'name' => $name_route,
                    'link' => trim($_POST['route_link'][$i] ?? ''),
                    'description' => trim($_POST['route_description'][$i] ?? '')
                );
            }
        }
    }
    
    $transportation_routes_json = json_encode($transportation_routes);

    $target_dir = "uploads/souvenirs/";

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
            header("Location: add_souvenirs.php" . ($id ? "?edit=$id" : ""));
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

    // Use prepared statements
    if ($id > 0) {
        $query = "UPDATE souvenirs SET 
                  name=?, image=?, image_detail=?, description=?, store_name=?, 
                  store_address=?, market_name=?, maps_embed=?, maps_link=?, 
                  website=?, directions_link=?, hours=?, contact=?, email=?, 
                  social_media=?, nearby_places=?, details_link=?, significance=?, 
                  materials=?, price_range=?, transportation_options=?, transportation_routes=?
                  WHERE id=?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssssssssssssssssssssi", 
            $name, $target_file, $target_file_detail, $description, $store_name,
            $store_address, $market_name, $map_embed, $maps_link,
            $website, $directions_link, $hours, $contact, $email,
            $social_media, $nearby_places, $details_link, $significance,
            $materials, $price_range, $transportation_options, $transportation_routes_json, $id);
    } else {
        $query = "INSERT INTO souvenirs 
                  (name, image, image_detail, description, store_name, store_address, 
                   market_name, maps_embed, maps_link, website, directions_link, hours, 
                   contact, email, social_media, nearby_places, details_link, significance, 
                   materials, price_range, transportation_options, transportation_routes) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssssssssssssssssssss", 
            $name, $target_file, $target_file_detail, $description, $store_name,
            $store_address, $market_name, $map_embed, $maps_link,
            $website, $directions_link, $hours, $contact, $email,
            $social_media, $nearby_places, $details_link, $significance,
            $materials, $price_range, $transportation_options, $transportation_routes_json);
    }
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Souvenir " . ($id ? "updated" : "added") . " successfully";
    } else {
        $_SESSION['error'] = "Error saving souvenir: " . $conn->error;
    }
    
    header("Location: add_souvenirs.php");
    exit();
}

$souvenirData = [
    'id' => '', 
    'name' => '', 
    'description' => '', 
    'store_name' => '', 
    'store_address' => '', 
    'market_name' => '',
    'maps_embed' => '', 
    'maps_link' => '',
    'directions_link' => '', 
    'website' => '',
    'hours' => '', 
    'contact' => '', 
    'email' => '', 
    'image' => '',
    'image_detail' => '',
    'social_media' => '',
    'nearby_places' => '',
    'details_link' => '',
    'significance' => '',
    'materials' => '',
    'price_range' => '',
    'transportation_options' => '',
    'transportation_routes' => ''
];

$transportation_routes = array();
$nearby_places_str = '';
$route_count = 1;

if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM souvenirs WHERE id=?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $souvenirData = $result->fetch_assoc();
        $nearby_places_str = $souvenirData['nearby_places'];
        
        if (!empty($souvenirData['transportation_routes'])) {
            $decoded_routes = json_decode($souvenirData['transportation_routes'], true);
            if (is_array($decoded_routes)) {
                $transportation_routes = $decoded_routes;
                $route_count = count($transportation_routes);
            }
        }
    }
}

if ($route_count == 0) {
    $route_count = 1;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Souvenir Management</title>
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
            <h1 class="page-title">Souvenir Management</h1>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#souvenirModal">
                <i class="bi bi-plus-lg"></i> Add Souvenir
            </button>
        </div>
        
        <?php if ($souvenirs->num_rows == 0): ?>
            <div class="alert alert-info">
                No souvenirs found. Click "Add Souvenir" to create one.
            </div>
        <?php else: ?>
            <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-4">
                <?php while ($souvenir = $souvenirs->fetch_assoc()): ?>
                    <div class="col">
                        <div class="card h-100">
                            <?php if (!empty($souvenir['image'])): ?>
                                <img src="<?= htmlspecialchars($souvenir['image']) ?>" class="card-img-top" alt="<?= htmlspecialchars($souvenir['name']) ?>">
                            <?php else: ?>
                                <div class="card-img-top bg-light d-flex align-items-center justify-content-center">
                                    <i class="bi bi-gift fs-1 text-muted"></i>
                                </div>
                            <?php endif; ?>
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?= htmlspecialchars($souvenir['name']) ?></h5>
                                <p class="card-text flex-grow-1"><?= substr(htmlspecialchars($souvenir['description']), 0, 100) . '...' ?></p>
                                <?php if (!empty($souvenir['store_name'])): ?>
                                    <p class="text-primary fw-bold"><?= htmlspecialchars($souvenir['store_name']) ?></p>
                                <?php endif; ?>
                                <?php if (!empty($souvenir['price_range'])): ?>
                                    <p class="text-success"><?= htmlspecialchars($souvenir['price_range']) ?></p>
                                <?php endif; ?>
                                <div class="d-flex justify-content-between">
                                    <a href="add_souvenirs.php?edit=<?= $souvenir['id'] ?>" class="btn btn-primary btn-sm">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    <a href="?delete=<?= $souvenir['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this souvenir?')">
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

    <!-- Add/Edit Souvenir Modal -->
    <div class="modal fade <?= isset($_GET['edit']) ? 'show d-block' : '' ?>" id="souvenirModal" tabindex="-1" aria-labelledby="souvenirModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="souvenirModalLabel"><?= $souvenirData['id'] ? 'Edit Souvenir' : 'Add Souvenir' ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="add_souvenirs.php" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="<?= htmlspecialchars($souvenirData['id']) ?>">
                        
                        <div class="form-section">
                            <h5>Basic Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Name *</label>
                                        <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($souvenirData['name']) ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Details Page Link:</label>
                                        <input type="text" class="form-control" name="details_link" value="<?= htmlspecialchars($souvenirData['details_link']) ?>" placeholder="e.g., souvenir_details.php?id=1">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Store Name</label>
                                        <input type="text" class="form-control" name="store_name" value="<?= htmlspecialchars($souvenirData['store_name']) ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Price Range</label>
                                        <input type="text" class="form-control" name="price_range" value="<?= htmlspecialchars($souvenirData['price_range']) ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Upload Card Image:</label>
                                        <input type="file" class="form-control" name="image" accept="image/*">
                                        
                                        <?php if (!empty($souvenirData['image'])): ?>
                                            <div class="mt-2">
                                                <img src="<?= htmlspecialchars($souvenirData['image']) ?>" class="img-thumbnail" width="100">
                                                <div class="form-check mt-1">
                                                    <input class="form-check-input" type="checkbox" name="delete_image" value="1" id="deleteImageCheck">
                                                    <label class="form-check-label" for="deleteImageCheck">Delete current image</label>
                                                </div>
                                            </div>
                                            <input type="hidden" name="existing_image" value="<?= htmlspecialchars($souvenirData['image']) ?>">
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Description *</label>
                                <textarea class="form-control" name="description" rows="4" required><?= htmlspecialchars($souvenirData['description']) ?></textarea>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h5>Images</h5>
                            <div class="mb-3">
                                <label class="form-label">Detail Images (2-5 recommended)</label>
                                <input type="file" class="form-control" name="image_detail[]" multiple accept="image/*">
                                
                                <?php if (!empty($souvenirData['image_detail'])): ?>
                                    <div class="image-preview-container mt-2">
                                        <?php 
                                            $images = explode(',', $souvenirData['image_detail']);
                                            foreach ($images as $img): 
                                                if (!empty($img)):
                                        ?>
                                            <div class="image-preview">
                                                <img src="<?= htmlspecialchars($img) ?>" alt="Detail image">
                                                <div class="delete-image-btn" 
                                                     onclick="if(confirm('Delete this image?')) window.location.href='?delete_image=<?= urlencode($img) ?>&souvenir_id=<?= $souvenirData['id'] ?>'">
                                                    ×
                                                </div>
                                            </div>
                                        <?php 
                                                endif;
                                            endforeach; 
                                        ?>
                                        <input type="hidden" name="existing_image_detail" value="<?= htmlspecialchars($souvenirData['image_detail']) ?>">
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h5>Souvenir Details</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Cultural Significance</label>
                                        <textarea class="form-control" name="significance" rows="4"><?= htmlspecialchars($souvenirData['significance']) ?></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Materials</label>
                                        <textarea class="form-control" name="materials" rows="4"><?= htmlspecialchars($souvenirData['materials']) ?></textarea>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Store Address</label>
                                        <textarea class="form-control" name="store_address" rows="4"><?= htmlspecialchars($souvenirData['store_address']) ?></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Market Name</label>
                                        <input type="text" class="form-control" name="market_name" value="<?= htmlspecialchars($souvenirData['market_name']) ?>">
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
                                                    <input type="text" class="form-control" name="route_name[]" value="<?php echo isset($transportation_routes[$i]['name']) ? htmlspecialchars($transportation_routes[$i]['name']) : ''; ?>" placeholder="e.g., Route 101, LRT-1, MRT-3">
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control" name="route_link[]" value="<?php echo isset($transportation_routes[$i]['link']) ? htmlspecialchars($transportation_routes[$i]['link']) : ''; ?>" placeholder="https://maps.google.com/...">
                                                </td>
                                                <td>
                                                    <textarea class="form-control" name="route_description[]" rows="2" placeholder="e.g., Main transportation to the market, nearest stop"><?php echo isset($transportation_routes[$i]['description']) ? htmlspecialchars($transportation_routes[$i]['description']) : ''; ?></textarea>
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
                            <h5>Location Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Google Map Link</label>
                                        <input type="url" class="form-control" name="maps_link" value="<?= htmlspecialchars($souvenirData['maps_link']) ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Google Directions Link</label>
                                        <input type="text" class="form-control" name="directions_link" value="<?= htmlspecialchars($souvenirData['directions_link']) ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Nearby Places:</label>
                                        <input type="text" class="form-control" name="nearby_places" value="<?= htmlspecialchars($nearby_places_str) ?>" placeholder="e.g., Mall, Market, Park, School">
                                        <small class="text-muted">Separate by commas</small>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Website URL</label>
                                        <input type="url" class="form-control" name="website" value="<?= htmlspecialchars($souvenirData['website']) ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Embed Google Map URL</label>
                                <input type="url" class="form-control" name="map_embed" value="<?= htmlspecialchars($souvenirData['maps_embed']) ?>" placeholder="https://maps.google.com/embed...">
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h5>Contact Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Operating Hours</label>
                                        <textarea class="form-control" name="hours" rows="5"><?= htmlspecialchars($souvenirData['hours']) ?></textarea>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Contact Details</label>
                                        <input type="text" class="form-control mb-2" name="contact" value="<?= htmlspecialchars($souvenirData['contact']) ?>" placeholder="Phone number">
                                        <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($souvenirData['email']) ?>" placeholder="Email address">
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Social Media Links (one per line)</label>
                                <textarea class="form-control" name="social_media" rows="5"><?= htmlspecialchars($souvenirData['social_media']) ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Transportation Options (Text Description)</label>
                                <textarea class="form-control" name="transportation_options" rows="3"><?= htmlspecialchars($souvenirData['transportation_options']) ?></textarea>
                                <small class="text-muted">Enter each option on a new line with format: Type: Description</small>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <?= $souvenirData['id'] ? 'Update Souvenir' : 'Save Souvenir' ?>
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
                var modal = new bootstrap.Modal(document.getElementById('souvenirModal'));
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
                    <input type="text" class="form-control" name="route_name[]" placeholder="e.g., Route 101, LRT-1, MRT-3">
                </td>
                <td>
                    <input type="text" class="form-control" name="route_link[]" placeholder="https://maps.google.com/...">
                </td>
                <td>
                    <textarea class="form-control" name="route_description[]" rows="2" placeholder="e.g., Main transportation to the market, nearest stop"></textarea>
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
            }
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>