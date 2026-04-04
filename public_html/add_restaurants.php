<?php
session_start();
include "conn.php";

$target_dir = "uploads/restaurants/";
if (!file_exists($target_dir)) {
    mkdir($target_dir, 0777, true);
}

if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    
    $stmt = $conn->prepare("SELECT image, image_detail FROM restaurants WHERE id=?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $restaurant = $result->fetch_assoc();
    
    $stmt = $conn->prepare("DELETE FROM restaurants WHERE id=?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    
    if ($restaurant) {
        if (!empty($restaurant['image']) && file_exists($restaurant['image'])) {
            unlink($restaurant['image']);
        }
        
        if (!empty($restaurant['image_detail'])) {
            $images = explode(',', $restaurant['image_detail']);
            foreach ($images as $img) {
                if (!empty($img) && file_exists($img)) {
                    unlink($img);
                }
            }
        }
    }
    
    $_SESSION['message'] = "Restaurant deleted successfully";
    header("Location: add_restaurants.php");
    exit();
}

if (isset($_GET['delete_image']) && isset($_GET['restaurant_id'])) {
    $image_path = $_GET['delete_image'];
    $restaurant_id = (int)$_GET['restaurant_id'];
    
    if (file_exists($image_path)) {
        unlink($image_path);
    }
    
    $stmt = $conn->prepare("SELECT image_detail FROM restaurants WHERE id=?");
    $stmt->bind_param("i", $restaurant_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $restaurant = $result->fetch_assoc();
    
    if ($restaurant) {
        $images = explode(',', $restaurant['image_detail']);
        $updated_images = array_diff($images, [$image_path]);
        $updated_images_str = implode(',', $updated_images);
        
        $stmt = $conn->prepare("UPDATE restaurants SET image_detail=? WHERE id=?");
        $stmt->bind_param("si", $updated_images_str, $restaurant_id);
        $stmt->execute();
    }
    
    $_SESSION['message'] = "Image deleted successfully";
    header("Location: add_restaurants.php?edit=" . $restaurant_id);
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
    $map_embed = trim($_POST['map_embed']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $social_media = trim($_POST['social_media']);
    $details_link = trim($_POST['details_link']);
    $cuisine = trim($_POST['cuisine']);
    $popular_dishes = trim($_POST['popular_dishes']);
    $dining_experience = trim($_POST['dining_experience']);
    $wifi = trim($_POST['wifi']);
    $accessibility = trim($_POST['accessibility']);
    $parking = trim($_POST['parking']);
    $payment = trim($_POST['payment']);
    $price_range = trim($_POST['price_range']);
    $average_price = trim($_POST['average_price']);
    $indoor_capacity = trim($_POST['indoor_capacity']);
    $outdoor_capacity = trim($_POST['outdoor_capacity']);
    $special_notes = trim($_POST['special_notes']);
    $review_excerpt = trim($_POST['review_excerpt']);
    $reservation_link = trim($_POST['reservation_link']);
    $nearby_places = trim($_POST['nearby_places']);

    // Handle transportation routes
    $transportation_routes = array();
    
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

    if ($id > 0) {
        $query = "UPDATE restaurants SET 
                  name=?, description=?, hours=?, contact=?, 
                  location=?, website=?, maps_link=?, directions_link=?, 
                  maps_embed=?, phone=?, email=?, social_media=?, 
                  details_link=?, cuisine=?, popular_dishes=?, 
                  dining_experience=?, wifi=?, accessibility=?, 
                  parking=?, payment=?, price_range=?, average_price=?, 
                  indoor_capacity=?, outdoor_capacity=?, special_notes=?, 
                  review_excerpt=?, reservation_link=?, 
                  nearby_places=?, image=?, image_detail=?, transportation_routes=?
                  WHERE id=?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssssssssssssssssssssssssssssssi", 
            $name, $description, $hours, $contact, 
            $location, $website, $maps_link, $directions_link, 
            $map_embed, $phone, $email, $social_media, 
            $details_link, $cuisine, $popular_dishes, 
            $dining_experience, $wifi, $accessibility, 
            $parking, $payment, $price_range, $average_price, 
            $indoor_capacity, $outdoor_capacity, $special_notes, 
            $review_excerpt, $reservation_link, 
            $nearby_places, $target_file, $target_file_detail, 
            $transportation_routes_json, $id);
    } else {
        $query = "INSERT INTO restaurants (
                  name, description, hours, contact, location, website, 
                  maps_link, directions_link, maps_embed, phone, email, 
                  social_media, details_link, cuisine, popular_dishes, 
                  dining_experience, wifi, accessibility, parking, payment, 
                  price_range, average_price, indoor_capacity, outdoor_capacity, 
                  special_notes, review_excerpt, reservation_link, 
                  nearby_places, image, image_detail, transportation_routes
                  ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssssssssssssssssssssssssssssss", 
            $name, $description, $hours, $contact, 
            $location, $website, $maps_link, $directions_link, 
            $map_embed, $phone, $email, $social_media, 
            $details_link, $cuisine, $popular_dishes, 
            $dining_experience, $wifi, $accessibility, 
            $parking, $payment, $price_range, $average_price, 
            $indoor_capacity, $outdoor_capacity, $special_notes, 
            $review_excerpt, $reservation_link, 
            $nearby_places, $target_file, $target_file_detail, 
            $transportation_routes_json);
    }
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Restaurant " . ($id ? "updated" : "added") . " successfully";
    } else {
        $_SESSION['error'] = "Error saving restaurant: " . $conn->error;
    }
    
    header("Location: add_restaurants.php");
    exit();
}

$query = "SELECT id, name, image, description, location, cuisine FROM restaurants ORDER BY name";
$restaurants = $conn->query($query);

$restaurantData = [
    'id' => '', 'name' => '', 'description' => '', 'location' => '', 
    'website' => '', 'image' => '', 'maps_embed' => '', 'maps_link' => '', 
    'directions_link' => '', 'hours' => '', 'contact' => '', 'phone' => '', 
    'email' => '', 'social_media' => '', 'nearby_places' => '', 
    'image_detail' => '', 'details_link' => '', 'cuisine' => '', 
    'popular_dishes' => '', 'dining_experience' => '', 'wifi' => '', 
    'accessibility' => '', 'parking' => '', 'payment' => '', 
    'price_range' => '', 'average_price' => '', 'indoor_capacity' => '', 
    'outdoor_capacity' => '', 'special_notes' => '', 
    'review_excerpt' => '', 'reservation_link' => '', 'transportation_routes' => ''
];

$transportation_routes = array();

if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM restaurants WHERE id=?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $restaurantData = $result->fetch_assoc();
        if (!empty($restaurantData['transportation_routes'])) {
            $decoded_routes = json_decode($restaurantData['transportation_routes'], true);
            if (is_array($decoded_routes)) {
                $transportation_routes = $decoded_routes;
            }
        }
    }
}

$route_count = count($transportation_routes);
if ($route_count == 0) {
    $route_count = 1;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurants Management</title>
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
            height: 200px;
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
            <h1 class="page-title">Restaurant Management</h1>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#restaurantModal">
                <i class="bi bi-plus-lg"></i> Add Restaurant
            </button>
        </div>
        
        <?php if ($restaurants->num_rows == 0): ?>
            <div class="alert alert-info">
                No restaurants found. Click "Add Restaurant" to create one.
            </div>
        <?php else: ?>
            <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-4">
                <?php while ($restaurant = $restaurants->fetch_assoc()): ?>
                    <div class="col">
                        <div class="card h-100">
                            <?php if (!empty($restaurant['image'])): ?>
                                <img src="<?= $restaurant['image'] ?>" class="card-img-top" alt="<?= $restaurant['name'] ?>">
                            <?php else: ?>
                                <div class="card-img-top bg-light d-flex align-items-center justify-content-center">
                                    <i class="bi bi-egg-fried fs-1 text-muted"></i>
                                </div>
                            <?php endif; ?>
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?= $restaurant['name'] ?></h5>
                                <p class="card-text flex-grow-1"><?= substr($restaurant['description'], 0, 100) . '...' ?></p>
                                <?php if (!empty($restaurant['cuisine'])): ?>
                                    <p class="text-primary fw-bold"><?= $restaurant['cuisine'] ?></p>
                                <?php endif; ?>
                                <p class="card-text"><small class="text-muted"><?= $restaurant['location'] ?></small></p>
                                <div class="d-flex justify-content-between">
                                    <a href="add_restaurants.php?edit=<?= $restaurant['id'] ?>" class="btn btn-primary btn-sm">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    <a href="?delete=<?= $restaurant['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this restaurant?')">
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

    <div class="modal fade <?= isset($_GET['edit']) ? 'show d-block' : '' ?>" id="restaurantModal" tabindex="-1" aria-labelledby="restaurantModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="restaurantModalLabel"><?= $restaurantData['id'] ? 'Edit Restaurant' : 'Add Restaurant' ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="add_restaurants.php" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="<?= $restaurantData['id'] ?>">
                        
                        <div class="form-section">
                            <h5>Basic Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Restaurant Name *</label>
                                        <input type="text" class="form-control" name="name" value="<?= $restaurantData['name'] ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Address</label>
                                        <textarea class="form-control" name="location" rows="3"><?= $restaurantData['location'] ?></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Cuisine Type</label>
                                        <input type="text" class="form-control" name="cuisine" value="<?= $restaurantData['cuisine'] ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Price Range</label>
                                        <input type="text" class="form-control" name="price_range" value="<?= $restaurantData['price_range'] ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Upload Card Image:</label>
                                        <input type="file" class="form-control" name="image" accept="image/*">
                                        
                                        <?php if (!empty($restaurantData['image'])): ?>
                                            <div class="mt-2">
                                                <img src="<?= $restaurantData['image'] ?>" style="max-width: 100px; max-height: 100px;" class="img-thumbnail">
                                                <div class="form-check mt-1">
                                                    <input class="form-check-input" type="checkbox" name="delete_image" value="1" id="deleteImageCheck">
                                                    <label class="form-check-label" for="deleteImageCheck">Delete current image</label>
                                                </div>
                                            </div>
                                            <input type="hidden" name="existing_image" value="<?= $restaurantData['image'] ?>">
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" name="description" rows="4"><?= $restaurantData['description'] ?></textarea>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h5>Images</h5>
                            <div class="mb-3">
                                <label class="form-label">Detail Images (Multiple)</label>
                                <input type="file" class="form-control" name="image_detail[]" multiple accept="image/*">
                                
                                <?php if (!empty($restaurantData['image_detail'])): ?>
                                    <div class="image-preview-container mt-2">
                                        <?php 
                                            $images = explode(',', $restaurantData['image_detail']);
                                            foreach ($images as $img): 
                                                if (!empty($img)):
                                        ?>
                                            <div class="image-preview">
                                                <img src="<?= $img ?>" alt="Detail image">
                                                <div class="delete-image-btn" 
                                                     onclick="if(confirm('Delete this image?')) window.location.href='?delete_image=<?= urlencode($img) ?>&restaurant_id=<?= $restaurantData['id'] ?>'">
                                                    ×
                                                </div>
                                            </div>
                                        <?php 
                                                endif;
                                            endforeach; 
                                        ?>
                                        <input type="hidden" name="existing_image_detail" value="<?= $restaurantData['image_detail'] ?>">
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h5>Restaurant Details</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Popular Dishes</label>
                                        <textarea class="form-control" name="popular_dishes" rows="3"><?= $restaurantData['popular_dishes'] ?></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Dining Experience</label>
                                        <textarea class="form-control" name="dining_experience" rows="3"><?= $restaurantData['dining_experience'] ?></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Average Price (per person)</label>
                                        <input type="text" class="form-control" name="average_price" value="<?= $restaurantData['average_price'] ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Indoor Capacity</label>
                                        <input type="text" class="form-control" name="indoor_capacity" value="<?= $restaurantData['indoor_capacity'] ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Outdoor Capacity</label>
                                        <input type="text" class="form-control" name="outdoor_capacity" value="<?= $restaurantData['outdoor_capacity'] ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Wi-Fi Availability</label>
                                        <input type="text" class="form-control" name="wifi" value="<?= $restaurantData['wifi'] ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Accessibility Features</label>
                                        <input type="text" class="form-control" name="accessibility" value="<?= $restaurantData['accessibility'] ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Parking Information</label>
                                        <input type="text" class="form-control" name="parking" value="<?= $restaurantData['parking'] ?>">
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
                                            <th>Transport Type</th>
                                            <th>Route Name/Number</th>
                                            <th>Route Link (Optional)</th>
                                            <th>Description (Optional)</th>
                                            <th>Action</th>
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
                                                        <option value="delivery" <?php echo (isset($transportation_routes[$i]['type']) && $transportation_routes[$i]['type'] == 'delivery') ? 'selected' : ''; ?>>Food Delivery</option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control" name="route_name[]" value="<?php echo isset($transportation_routes[$i]['name']) ? $transportation_routes[$i]['name'] : ''; ?>" placeholder="e.g., Route 101, Food Panda">
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control" name="route_link[]" value="<?php echo isset($transportation_routes[$i]['link']) ? $transportation_routes[$i]['link'] : ''; ?>" placeholder="https://maps.google.com/...">
                                                </td>
                                                <td>
                                                    <textarea class="form-control" name="route_description[]" rows="2" placeholder="Additional information"><?php echo isset($transportation_routes[$i]['description']) ? $transportation_routes[$i]['description'] : ''; ?></textarea>
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
                                        <input type="text" class="form-control" name="maps_link" value="<?= $restaurantData['maps_link'] ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Google Directions Link</label>
                                        <input type="text" class="form-control" name="directions_link" value="<?= $restaurantData['directions_link'] ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Map Embed Code</label>
                                        <textarea class="form-control" name="map_embed" rows="3"><?= $restaurantData['maps_embed'] ?></textarea>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Details Page Link:</label>
                                        <input type="text" class="form-control" name="details_link" value="<?= $restaurantData['details_link'] ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Nearby Places:</label>
                                        <input type="text" class="form-control" name="nearby_places" value="<?= $restaurantData['nearby_places'] ?>" placeholder="e.g., Mall, Supermarket">
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
                                        <label class="form-label">Operating Hours</label>
                                        <textarea class="form-control" name="hours" rows="5"><?= $restaurantData['hours'] ?></textarea>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Contact Details</label>
                                        <input type="text" class="form-control mb-2" name="contact" value="<?= $restaurantData['contact'] ?>" placeholder="Contact Person">
                                        <input type="text" class="form-control mb-2" name="phone" value="<?= $restaurantData['phone'] ?>" placeholder="Phone number">
                                        <input type="email" class="form-control" name="email" value="<?= $restaurantData['email'] ?>" placeholder="Email address">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Website</label>
                                        <input type="text" class="form-control" name="website" value="<?= $restaurantData['website'] ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Reservation Link</label>
                                        <input type="text" class="form-control" name="reservation_link" value="<?= $restaurantData['reservation_link'] ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Social Media Links (one per line)</label>
                                <textarea class="form-control" name="social_media" rows="5"><?= $restaurantData['social_media'] ?></textarea>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h5>Additional Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Payment Methods</label>
                                        <input type="text" class="form-control" name="payment" value="<?= $restaurantData['payment'] ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Special Notes</label>
                                        <textarea class="form-control" name="special_notes" rows="3"><?= $restaurantData['special_notes'] ?></textarea>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Review Excerpt</label>
                                        <textarea class="form-control" name="review_excerpt" rows="3"><?= $restaurantData['review_excerpt'] ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <?= $restaurantData['id'] ? 'Update Restaurant' : 'Save Restaurant' ?>
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
                var modal = new bootstrap.Modal(document.getElementById('restaurantModal'));
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
                        <option value="delivery">Food Delivery</option>
                    </select>
                </td>
                <td>
                    <input type="text" class="form-control" name="route_name[]" placeholder="e.g., Route 101">
                </td>
                <td>
                    <input type="text" class="form-control" name="route_link[]" placeholder="https://maps.google.com/...">
                </td>
                <td>
                    <textarea class="form-control" name="route_description[]" rows="2" placeholder="Additional information"></textarea>
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