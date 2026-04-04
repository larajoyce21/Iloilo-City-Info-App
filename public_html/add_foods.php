<?php
session_start();
include "conn.php";

$target_dir = "uploads/foods/";
if (!file_exists($target_dir)) {
    mkdir($target_dir, 0777, true);
}

if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    
    $stmt = $conn->prepare("SELECT image, image_detail FROM foods WHERE id=?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $food = $result->fetch_assoc();
    
    $stmt = $conn->prepare("DELETE FROM foods WHERE id=?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    
    if ($food) {
        if (!empty($food['image']) && file_exists($food['image'])) {
            unlink($food['image']);
        }
        
        if (!empty($food['image_detail'])) {
            $images = explode(',', $food['image_detail']);
            foreach ($images as $img) {
                if (!empty($img) && file_exists($img)) {
                    unlink($img);
                }
            }
        }
    }
    
    $_SESSION['message'] = "Food establishment deleted successfully";
    header("Location: add_foods.php");
    exit();
}

if (isset($_GET['delete_image']) && isset($_GET['restaurant_id'])) {
    $image_path = $_GET['delete_image'];
    $restaurant_id = (int)$_GET['restaurant_id'];
    
    if (file_exists($image_path)) {
        unlink($image_path);
    }
    
    $stmt = $conn->prepare("SELECT image_detail FROM foods WHERE id=?");
    $stmt->bind_param("i", $restaurant_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $food = $result->fetch_assoc();
    
    if ($food) {
        $images = explode(',', $food['image_detail']);
        $updated_images = array_diff($images, [$image_path]);
        $updated_images_str = implode(',', $updated_images);
        
        $stmt = $conn->prepare("UPDATE foods SET image_detail=? WHERE id=?");
        $stmt->bind_param("si", $updated_images_str, $restaurant_id);
        $stmt->execute();
    }
    
    $_SESSION['message'] = "Image deleted successfully";
    header("Location: add_foods.php?edit=" . $restaurant_id);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $history = trim($_POST['history']);
    $menu = trim($_POST['menu']);
    $offers = trim($_POST['offers']);
    $hours = trim($_POST['hours']);
    $contact = trim($_POST['contact']);
    $location = trim($_POST['location']);
    $maps_link = trim($_POST['maps_link']);
    $maps_embed = trim($_POST['maps_embed']);
    $website = trim($_POST['website']);
    $directions = trim($_POST['directions']);
    $email = trim($_POST['email']);
    $social_media = trim($_POST['social_media']);
    $year_established = trim($_POST['year_established']);
    $cuisine = trim($_POST['cuisine']);
    $specialty = trim($_POST['specialty']);
    $price_range = trim($_POST['price_range']);
    $popular_items = trim($_POST['popular_items']);
    $best_seller = trim($_POST['best_seller']);
    $details_link = trim($_POST['details_link']);
    $nearby_places = trim($_POST['nearby_places']);
    $branches = trim($_POST['branches']);

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
            header("Location: add_foods.php" . ($id ? "?edit=$id" : ""));
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
        $query = "UPDATE foods SET 
                  name=?, description=?, history=?, menu=?, 
                  offers=?, hours=?, contact=?, location=?, 
                  maps_link=?, maps_embed=?, website=?, 
                  directions=?, email=?, social_media=?, 
                  year_established=?, cuisine=?, specialty=?, 
                  price_range=?, popular_items=?, best_seller=?, 
                  image=?, image_detail=?, nearby_places=?, 
                  details_link=?, branches=?, transportation_routes=?
                  WHERE id=?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssssssssssssssssssssssssi", 
            $name, $description, $history, $menu, 
            $offers, $hours, $contact, $location, 
            $maps_link, $maps_embed, $website, 
            $directions, $email, $social_media, 
            $year_established, $cuisine, $specialty, 
            $price_range, $popular_items, $best_seller, 
            $target_file, $target_file_detail, $nearby_places, 
            $details_link, $branches, $transportation_routes_json, $id);
    } else {
        $query = "INSERT INTO foods (
                  name, description, history, menu, offers, hours, contact, location, 
                  maps_link, maps_embed, website, directions, email, social_media, 
                  year_established, cuisine, specialty, price_range, popular_items, 
                  best_seller, image, image_detail, nearby_places, details_link, branches,
                  transportation_routes
                  ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssssssssssssssssssssssss", 
            $name, $description, $history, $menu, 
            $offers, $hours, $contact, $location, 
            $maps_link, $maps_embed, $website, 
            $directions, $email, $social_media, 
            $year_established, $cuisine, $specialty, 
            $price_range, $popular_items, $best_seller, 
            $target_file, $target_file_detail, $nearby_places, 
            $details_link, $branches, $transportation_routes_json);
    }
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Food establishment " . ($id ? "updated" : "added") . " successfully";
    } else {
        $_SESSION['error'] = "Error saving food establishment: " . $conn->error;
    }
    
    header("Location: add_foods.php");
    exit();
}

$query = "SELECT id, name, image, description, location, cuisine FROM foods ORDER BY name";
$foods = $conn->query($query);

$foodData = [
    'id' => '', 'name' => '', 'description' => '', 'history' => '', 'menu' => '', 
    'offers' => '', 'hours' => '', 'contact' => '', 'location' => '', 'maps_link' => '', 
    'maps_embed' => '', 'website' => '', 'directions' => '', 'email' => '', 'social_media' => '',
    'year_established' => '', 'cuisine' => '', 'specialty' => '', 'price_range' => '',
    'popular_items' => '', 'best_seller' => '', 'image' => '', 'image_detail' => '',
    'nearby_places' => '', 'details_link' => '', 'branches' => '', 'transportation_routes' => ''
];

$transportation_routes = array();

if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT *, transportation_routes FROM foods WHERE id=?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $foodData = $result->fetch_assoc();
        if (!empty($foodData['transportation_routes'])) {
            $decoded_routes = json_decode($foodData['transportation_routes'], true);
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
    <title>Food Establishment Management</title>
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
            <h1 class="page-title">Food Establishment Management</h1>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#foodModal">
                <i class="bi bi-plus-lg"></i> Add Establishment
            </button>
        </div>
        
        <?php if ($foods->num_rows == 0): ?>
            <div class="alert alert-info">
                No food establishments found. Click "Add Establishment" to create one.
            </div>
        <?php else: ?>
            <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-4">
                <?php while ($food = $foods->fetch_assoc()): ?>
                    <div class="col">
                        <div class="card h-100">
                            <?php if (!empty($food['image'])): ?>
                                <img src="<?= htmlspecialchars($food['image']) ?>" class="card-img-top" alt="<?= htmlspecialchars($food['name']) ?>">
                            <?php else: ?>
                                <div class="card-img-top bg-light d-flex align-items-center justify-content-center">
                                    <i class="bi bi-egg-fried fs-1 text-muted"></i>
                                </div>
                            <?php endif; ?>
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?= htmlspecialchars($food['name']) ?></h5>
                                <p class="card-text flex-grow-1"><?= substr(htmlspecialchars($food['description']), 0, 100) . '...' ?></p>
                                <?php if (!empty($food['cuisine'])): ?>
                                    <p class="text-primary fw-bold"><?= htmlspecialchars($food['cuisine']) ?></p>
                                <?php endif; ?>
                                <p class="card-text"><small class="text-muted"><?= htmlspecialchars($food['location']) ?></small></p>
                                <div class="d-flex justify-content-between">
                                    <a href="add_foods.php?edit=<?= $food['id'] ?>" class="btn btn-primary btn-sm">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    <a href="?delete=<?= $food['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this establishment?')">
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

    <!-- Add/Edit Food Modal -->
    <div class="modal fade <?= isset($_GET['edit']) ? 'show d-block' : '' ?>" id="foodModal" tabindex="-1" aria-labelledby="foodModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="foodModalLabel"><?= $foodData['id'] ? 'Edit Food Establishment' : 'Add Food Establishment' ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="add_foods.php" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="<?= htmlspecialchars($foodData['id']) ?>">
                        
                        <div class="form-section">
                            <h5>Basic Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Name *</label>
                                        <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($foodData['name']) ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Location *</label>
                                        <textarea class="form-control" name="location" rows="3" required><?= htmlspecialchars($foodData['location']) ?></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Year Established</label>
                                        <input type="text" class="form-control" name="year_established" value="<?= htmlspecialchars($foodData['year_established']) ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Cuisine</label>
                                        <input type="text" class="form-control" name="cuisine" value="<?= htmlspecialchars($foodData['cuisine']) ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Upload Card Image:</label>
                                        <input type="file" class="form-control" name="image" accept="image/*">
                                        
                                        <?php if (!empty($foodData['image'])): ?>
                                            <div class="mt-2">
                                                <img src="<?= htmlspecialchars($foodData['image']) ?>" class="img-thumbnail" width="100">
                                                <div class="form-check mt-1">
                                                    <input class="form-check-input" type="checkbox" name="delete_image" value="1" id="deleteImageCheck">
                                                    <label class="form-check-label" for="deleteImageCheck">Delete current image</label>
                                                </div>
                                            </div>
                                            <input type="hidden" name="existing_image" value="<?= htmlspecialchars($foodData['image']) ?>">
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Description *</label>
                                <textarea class="form-control" name="description" rows="4" required><?= htmlspecialchars($foodData['description']) ?></textarea>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h5>Images</h5>
                            <div class="mb-3">
                                <label class="form-label">Detail Images (2-5 recommended)</label>
                                <input type="file" class="form-control" name="image_detail[]" multiple accept="image/*">
                                
                                <?php if (!empty($foodData['image_detail'])): ?>
                                    <div class="image-preview-container mt-2">
                                        <?php 
                                            $images = explode(',', $foodData['image_detail']);
                                            foreach ($images as $img): 
                                                if (!empty($img)):
                                        ?>
                                            <div class="image-preview">
                                                <img src="<?= htmlspecialchars($img) ?>" alt="Detail image">
                                                <div class="delete-image-btn" 
                                                     onclick="if(confirm('Delete this image?')) window.location.href='?delete_image=<?= urlencode($img) ?>&restaurant_id=<?= $foodData['id'] ?>'">
                                                    ×
                                                </div>
                                            </div>
                                        <?php 
                                                endif;
                                            endforeach; 
                                        ?>
                                        <input type="hidden" name="existing_image_detail" value="<?= htmlspecialchars($foodData['image_detail']) ?>">
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h5>Food Establishment Details</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">History</label>
                                        <textarea class="form-control" name="history" rows="4"><?= htmlspecialchars($foodData['history']) ?></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Specialty</label>
                                        <input type="text" class="form-control" name="specialty" value="<?= htmlspecialchars($foodData['specialty']) ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Price Range</label>
                                        <input type="text" class="form-control" name="price_range" value="<?= htmlspecialchars($foodData['price_range']) ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Popular Items</label>
                                        <textarea class="form-control" name="popular_items" rows="3"><?= htmlspecialchars($foodData['popular_items']) ?></textarea>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Best Seller</label>
                                        <input type="text" class="form-control" name="best_seller" value="<?= htmlspecialchars($foodData['best_seller']) ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Menu</label>
                                        <textarea class="form-control" name="menu" rows="4"><?= htmlspecialchars($foodData['menu']) ?></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Offers</label>
                                        <textarea class="form-control" name="offers" rows="3"><?= htmlspecialchars($foodData['offers']) ?></textarea>
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
                                                    <input type="text" class="form-control" name="route_name[]" value="<?php echo isset($transportation_routes[$i]['name']) ? htmlspecialchars($transportation_routes[$i]['name']) : ''; ?>" placeholder="e.g., Route 101, Food Panda, GrabFood">
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control" name="route_link[]" value="<?php echo isset($transportation_routes[$i]['link']) ? htmlspecialchars($transportation_routes[$i]['link']) : ''; ?>" placeholder="https://maps.google.com/...">
                                                </td>
                                                <td>
                                                    <textarea class="form-control" name="route_description[]" rows="2" placeholder="e.g., Main transportation to the area, nearby bus stops, delivery options"><?php echo isset($transportation_routes[$i]['description']) ? htmlspecialchars($transportation_routes[$i]['description']) : ''; ?></textarea>
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
                                        <input type="url" class="form-control" name="maps_link" value="<?= htmlspecialchars($foodData['maps_link']) ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Embed Google Map URL</label>
                                        <textarea class="form-control" name="maps_embed" rows="3"><?= htmlspecialchars($foodData['maps_embed']) ?></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Directions</label>
                                        <textarea class="form-control" name="directions" rows="3"><?= htmlspecialchars($foodData['directions']) ?></textarea>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Details Page Link:</label>
                                        <input type="text" class="form-control" name="details_link" value="<?= htmlspecialchars($foodData['details_link']) ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Nearby Places:</label>
                                        <input type="text" class="form-control" name="nearby_places" value="<?= htmlspecialchars($foodData['nearby_places']) ?>" placeholder="e.g., Mall, Supermarket, Park, School">
                                        <small class="text-muted">Separate by commas</small>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Branches (one per line)</label>
                                        <textarea class="form-control" name="branches" rows="5"><?= htmlspecialchars($foodData['branches']) ?></textarea>
                                        <small class="text-muted">Enter each branch on a separate line</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h5>Contact Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Operating Hours</label>
                                        <textarea class="form-control" name="hours" rows="5"><?= htmlspecialchars($foodData['hours']) ?></textarea>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Contact Details</label>
                                        <input type="text" class="form-control mb-2" name="contact" value="<?= htmlspecialchars($foodData['contact']) ?>" placeholder="Phone number">
                                        <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($foodData['email']) ?>" placeholder="Email address">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Website</label>
                                        <input type="url" class="form-control" name="website" value="<?= htmlspecialchars($foodData['website']) ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Social Media Links (one per line)</label>
                                <textarea class="form-control" name="social_media" rows="5"><?= htmlspecialchars($foodData['social_media']) ?></textarea>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <?= $foodData['id'] ? 'Update Establishment' : 'Save Establishment' ?>
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
                var modal = new bootstrap.Modal(document.getElementById('foodModal'));
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
                        <option value="jeepney">Jeepney</option>
                        <option value="bus">Bus</option>
                        <option value="tricycle">Tricycle</option>
                        <option value="taxi">Taxi</option>
                    </select>
                </td>
                <td>
                    <input type="text" class="form-control" name="route_name[]" placeholder="e.g., Route 101, Food Panda, GrabFood">
                </td>
                <td>
                    <input type="text" class="form-control" name="route_link[]" placeholder="https://maps.google.com/...">
                </td>
                <td>
                    <textarea class="form-control" name="route_description[]" rows="2" placeholder="e.g., Main transportation to the area, nearby bus stops, delivery options"></textarea>
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