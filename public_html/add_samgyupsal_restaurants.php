<?php
session_start();
require 'conn.php';

// Define allowed file types at the top
$allowed_types = ['jpg', 'jpeg', 'png', 'gif'];

if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    
    $stmt = $conn->prepare("SELECT image, image_detail FROM samgyupsal_restaurants WHERE id=?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $restaurant = $result->fetch_assoc();
    
    $stmt = $conn->prepare("DELETE FROM samgyupsal_restaurants WHERE id=?");
    $stmt->bind_param("i", $delete_id);
    
    if ($stmt->execute()) {
        if ($restaurant) {
            if (!empty($restaurant['image'])) {
                $image_path = 'uploads/' . basename($restaurant['image']);
                if (file_exists($image_path)) {
                    unlink($image_path);
                }
            }
            
            if (!empty($restaurant['image_detail'])) {
                $images = explode(',', $restaurant['image_detail']);
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
        
        $_SESSION['message'] = "Restaurant deleted successfully";
    } else {
        $_SESSION['error'] = "Error deleting restaurant";
    }
    
    header("Location: add_samgyupsal_restaurants.php");
    exit();
}

if (isset($_GET['delete_image']) && isset($_GET['restaurant_id'])) {
    $image_path = $_GET['delete_image'];
    $restaurant_id = $_GET['restaurant_id'];
    $is_card_image = isset($_GET['is_card_image']) ? true : false;
    
    $real_image_path = 'uploads/' . basename($image_path);
    
    if (file_exists($real_image_path)) {
        unlink($real_image_path);
    }
    
    if ($is_card_image) {
        $stmt = $conn->prepare("UPDATE samgyupsal_restaurants SET image='' WHERE id=?");
        $stmt->bind_param("i", $restaurant_id);
        $stmt->execute();
    } else {
        $stmt = $conn->prepare("SELECT image_detail FROM samgyupsal_restaurants WHERE id=?");
        $stmt->bind_param("i", $restaurant_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $restaurant = $result->fetch_assoc();
        
        if ($restaurant) {
            $images = array_filter(explode(',', $restaurant['image_detail']));
            $updated_images = array_diff($images, [$image_path]);
            $updated_images_str = implode(',', $updated_images);
            
            $stmt = $conn->prepare("UPDATE samgyupsal_restaurants SET image_detail=? WHERE id=?");
            $stmt->bind_param("si", $updated_images_str, $restaurant_id);
            $stmt->execute();
        }
    }
    
    $_SESSION['message'] = "Image deleted successfully";
    header("Location: add_samgyupsal_restaurants.php?edit=" . $restaurant_id);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = isset($_POST['id']) ? $_POST['id'] : null;
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
    $type = $_POST['type'] ?? '';
    $menu_sets = $_POST['menu_sets'] ?? '';
    $meat_selection = $_POST['meat_selection'] ?? '';
    $side_dishes = $_POST['side_dishes'] ?? '';
    $group_packages = $_POST['group_packages'] ?? '';
    $cooking_style = $_POST['cooking_style'] ?? '';
    $service_type = $_POST['service_type'] ?? '';
    $price_range = $_POST['price_range'] ?? '';
    $rating = $_POST['rating'] ?? '';
    $capacity = $_POST['capacity'] ?? '';
    $email = $_POST['email'] ?? '';
    $branches = $_POST['branches'] ?? '';
    $social_media = $_POST['social_media'] ?? '';
    $nearby_places = $_POST['nearby_places'] ?? '';
    $details_link = $_POST['details_link'] ?? '';
    $food_delivery = $_POST['food_delivery'] ?? '';
    $parking = $_POST['parking'] ?? '';
    $bike_parking = $_POST['bike_parking'] ?? '';
    $accessibility = $_POST['accessibility'] ?? '';
    $transportation_options = $_POST['transportation_options'] ?? '';

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

    if (empty($name) || empty($description) || empty($location)) {
        $_SESSION['error'] = "Please fill in all required fields";
        header("Location: add_samgyupsal_restaurants.php" . ($id ? "?edit=$id" : ""));
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
                header("Location: add_samgyupsal_restaurants.php" . ($id ? "?edit=$id" : ""));
                exit();
            }
        } else {
            $_SESSION['error'] = "Invalid file type. Only JPG, JPEG, PNG, GIF are allowed";
            header("Location: add_samgyupsal_restaurants.php" . ($id ? "?edit=$id" : ""));
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
            header("Location: add_samgyupsal_restaurants.php" . ($id ? "?edit=$id" : ""));
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
        $stmt = $conn->prepare("UPDATE samgyupsal_restaurants SET name=?, image=?, image_detail=?, description=?, location=?, maps_embed=?, maps_link=?, website=?, directions_link=?, hours=?, contact=?, type=?, established=?, menu_sets=?, meat_selection=?, side_dishes=?, group_packages=?, cooking_style=?, service_type=?, price_range=?, rating=?, capacity=?, email=?, branches=?, social_media=?, nearby_places=?, details_link=?, food_delivery=?, parking=?, bike_parking=?, accessibility=?, transportation_options=?, transportation_routes=? WHERE id=?");
        $stmt->bind_param("sssssssssssssssssssssssssssssssssi", $name, $target_file, $target_file_detail, $description, $location, $mapEmbedURL, $maps_link, $website, $directions_link, $hours, $contact, $type, $established, $menu_sets, $meat_selection, $side_dishes, $group_packages, $cooking_style, $service_type, $price_range, $rating, $capacity, $email, $branches, $social_media, $nearby_places, $details_link, $food_delivery, $parking, $bike_parking, $accessibility, $transportation_options, $transportation_routes_json, $id);
    } else {
        $stmt = $conn->prepare("INSERT INTO samgyupsal_restaurants (name, image, image_detail, description, location, maps_embed, maps_link, website, directions_link, hours, contact, type, established, menu_sets, meat_selection, side_dishes, group_packages, cooking_style, service_type, price_range, rating, capacity, email, branches, social_media, nearby_places, details_link, food_delivery, parking, bike_parking, accessibility, transportation_options, transportation_routes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssssssssssssssssssssssssssss", $name, $target_file, $target_file_detail, $description, $location, $mapEmbedURL, $maps_link, $website, $directions_link, $hours, $contact, $type, $established, $menu_sets, $meat_selection, $side_dishes, $group_packages, $cooking_style, $service_type, $price_range, $rating, $capacity, $email, $branches, $social_media, $nearby_places, $details_link, $food_delivery, $parking, $bike_parking, $accessibility, $transportation_options, $transportation_routes_json);
    }

    if ($stmt->execute()) {
        $_SESSION['message'] = "Restaurant " . ($id ? "updated" : "added") . " successfully";
    } else {
        $_SESSION['error'] = "Error saving restaurant: " . $stmt->error;
    }
    
    header("Location: add_samgyupsal_restaurants.php");
    exit();
}

$query = "SELECT * FROM samgyupsal_restaurants ORDER BY name ASC";
$result = $conn->query($query);
$restaurants = $result->fetch_all(MYSQLI_ASSOC);

$nearby_places_str = '';
$transportation_routes = array();
$route_count = 1;

if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $stmt = $conn->prepare("SELECT nearby_places, transportation_routes FROM samgyupsal_restaurants WHERE id=?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $nearby_places_str = $row['nearby_places'];
        
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
    <title>Samgyupsal Restaurants Management</title>
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
            <h1 class="text-light">Samgyupsal Restaurants Management</h1>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#restaurantModal">
                <i class="bi bi-plus-lg"></i> Add Restaurant
            </button>
        </div>
        
        <?php if (empty($restaurants)): ?>
            <div class="alert alert-info">
                No restaurants found. Click "Add Restaurant" to create one.
            </div>
        <?php else: ?>
            <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-4">
                <?php foreach ($restaurants as $restaurant): ?>
                    <div class="col">
                        <div class="card h-100">
                            <?php if (!empty($restaurant['image'])): ?>
                                <img src="<?php echo $restaurant['image']; ?>" class="card-img-top" alt="<?php echo $restaurant['name']; ?>">
                            <?php else: ?>
                                <div class="card-img-top bg-secondary d-flex align-items-center justify-content-center">
                                    <i class="bi bi-egg-fried text-white" style="font-size: 3rem;"></i>
                                </div>
                            <?php endif; ?>
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?php echo $restaurant['name']; ?></h5>
                                <p class="card-text flex-grow-1"><?php echo substr($restaurant['description'], 0, 100) . '...'; ?></p>
                                <div class="d-flex justify-content-between">
                                    <a href="add_samgyupsal_restaurants.php?edit=<?php echo $restaurant['id']; ?>" class="btn btn-primary btn-sm">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    <a href="?delete=<?php echo $restaurant['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this restaurant? All associated images will also be deleted.')">
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

    <!-- Add/Edit Restaurant Modal -->
    <div class="modal fade <?php echo isset($_GET['edit']) ? 'show d-block' : ''; ?>" id="restaurantModal" tabindex="-1" aria-labelledby="restaurantModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="restaurantModalLabel"><?php echo isset($_GET['edit']) ? 'Edit Restaurant' : 'Add Restaurant'; ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php 
                        $restaurantData = [
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
                            'menu_sets' => '',
                            'meat_selection' => '',
                            'side_dishes' => '',
                            'group_packages' => '',
                            'cooking_style' => '',
                            'service_type' => '',
                            'price_range' => '',
                            'rating' => '',
                            'capacity' => '',
                            'email' => '',
                            'branches' => '',
                            'social_media' => '',
                            'nearby_places' => '',
                            'details_link' => '',
                            'food_delivery' => '',
                            'parking' => '',
                            'bike_parking' => '',
                            'accessibility' => '',
                            'transportation_options' => '',
                            'transportation_routes' => ''
                        ];
                        
                        if (isset($_GET['edit'])) {
                            $edit_id = $_GET['edit'];
                            $stmt = $conn->prepare("SELECT * FROM samgyupsal_restaurants WHERE id=?");
                            $stmt->bind_param("i", $edit_id);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            if ($result->num_rows > 0) {
                                $restaurantData = $result->fetch_assoc();
                            }
                        }
                    ?>
                    <form method="POST" action="add_samgyupsal_restaurants.php" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="<?php echo $restaurantData['id']; ?>">
                        
                        <div class="form-section">
                            <h5>Basic Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Name *</label>
                                        <input type="text" class="form-control" name="name" value="<?php echo $restaurantData['name']; ?>" required maxlength="100">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Details Page Link:</label>
                                        <input type="text" class="form-control" name="details_link" value="<?php echo $restaurantData['details_link']; ?>" placeholder="e.g., samgyupsal_restaurant1.php?id=1">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Description *</label>
                                        <textarea class="form-control" name="description" rows="4" required maxlength="2000"><?php echo $restaurantData['description']; ?></textarea>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Operating Hours</label>
                                        <textarea class="form-control" name="hours" rows="4" placeholder="e.g., Monday: 11AM - 10PM"><?php echo $restaurantData['hours']; ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Contact Information</label>
                                        <input type="text" class="form-control" name="contact" value="<?php echo $restaurantData['contact']; ?>" placeholder="Phone, email, social media" maxlength="100">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h5>Images</h5>
                            <div class="mb-3">
                                <label class="form-label">Card Image (Main Display Image)</label>
                                <input type="file" class="form-control" name="image" accept="image/*">
                                
                                <?php if (!empty($restaurantData['image'])): ?>
                                    <div class="mt-3">
                                        <p>Current Image:</p>
                                        <img src="<?php echo $restaurantData['image']; ?>" class="img-thumbnail card-image-preview">
                                        <div class="form-check mt-2">
                                            <input class="form-check-input" type="checkbox" name="delete_image" value="1" id="deleteImageCheck">
                                            <label class="form-check-label" for="deleteImageCheck">Delete current image</label>
                                        </div>
                                        <input type="hidden" name="existing_image" value="<?php echo $restaurantData['image']; ?>">
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Detail Images (Max 5)</label>
                                <input type="file" class="form-control" name="image_detail[]" multiple accept="image/*">
                                <small class="text-muted">Upload additional images to display on the restaurant details page</small>
                                
                                <?php if (!empty($restaurantData['image_detail'])): ?>
                                    <div class="mt-3">
                                        <p>Current Detail Images:</p>
                                        <div class="d-flex flex-wrap gap-2">
                                            <?php 
                                            $images = explode(',', $restaurantData['image_detail']);
                                            foreach ($images as $img): 
                                                if (!empty($img)):
                                            ?>
                                                <div class="position-relative">
                                                    <img src="<?php echo $img; ?>" class="img-thumbnail" style="width: 100px; height: 100px; object-fit: cover;">
                                                    <a href="?delete_image=<?php echo $img; ?>&restaurant_id=<?php echo $restaurantData['id']; ?>" 
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
                                        <input type="hidden" name="existing_image_detail" value="<?php echo $restaurantData['image_detail']; ?>">
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
                                                        <option value="jeepney" <?php echo (isset($transportation_routes[$i]['type']) && $transportation_routes[$i]['type'] == 'jeepney') ? 'selected' : ''; ?>>Jeepney</option>
                                                        <option value="bus" <?php echo (isset($transportation_routes[$i]['type']) && $transportation_routes[$i]['type'] == 'bus') ? 'selected' : ''; ?>>Bus</option>
                                                        <option value="tricycle" <?php echo (isset($transportation_routes[$i]['type']) && $transportation_routes[$i]['type'] == 'tricycle') ? 'selected' : ''; ?>>Tricycle</option>
                                                        <option value="taxi" <?php echo (isset($transportation_routes[$i]['type']) && $transportation_routes[$i]['type'] == 'taxi') ? 'selected' : ''; ?>>Taxi</option>
                                                        <option value="food_delivery" <?php echo (isset($transportation_routes[$i]['type']) && $transportation_routes[$i]['type'] == 'food_delivery') ? 'selected' : ''; ?>>Food Delivery</option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control" name="route_name[]" value="<?php echo isset($transportation_routes[$i]['name']) ? htmlspecialchars($transportation_routes[$i]['name']) : ''; ?>" placeholder="e.g., Route 101, GrabFood, FoodPanda">
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control" name="route_link[]" value="<?php echo isset($transportation_routes[$i]['link']) ? htmlspecialchars($transportation_routes[$i]['link']) : ''; ?>" placeholder="https://maps.google.com/...">
                                                </td>
                                                <td>
                                                    <textarea class="form-control" name="route_description[]" rows="2" placeholder="e.g., Main transportation to the area, delivery options"><?php echo isset($transportation_routes[$i]['description']) ? htmlspecialchars($transportation_routes[$i]['description']) : ''; ?></textarea>
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
                            <h5>Menu Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Menu Sets (Format: Set Name|Price)</label>
                                        <textarea class="form-control" name="menu_sets" rows="5" placeholder="e.g., Basic Set|₱299
Premium Set|₱499
Unlimited Set|₱699"><?php echo $restaurantData['menu_sets']; ?></textarea>
                                        <small class="text-muted">Enter each set on a new line with format: Set Name|Price</small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Meat Selection</label>
                                        <input type="text" class="form-control" name="meat_selection" value="<?php echo $restaurantData['meat_selection']; ?>" placeholder="e.g., Pork belly, beef, chicken, marinated options">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Side Dishes</label>
                                        <input type="text" class="form-control" name="side_dishes" value="<?php echo $restaurantData['side_dishes']; ?>" placeholder="e.g., Kimchi, pickled radish, lettuce, garlic, ssamjang">
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Group Packages</label>
                                        <input type="text" class="form-control" name="group_packages" value="<?php echo $restaurantData['group_packages']; ?>" placeholder="e.g., Available for 2-6 persons">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Cooking Style</label>
                                        <input type="text" class="form-control" name="cooking_style" value="<?php echo $restaurantData['cooking_style']; ?>" placeholder="e.g., Tabletop grilling with ventilation">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Service Type</label>
                                        <input type="text" class="form-control" name="service_type" value="<?php echo $restaurantData['service_type']; ?>" placeholder="e.g., Unlimited meat and side dishes">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Price Range</label>
                                        <input type="text" class="form-control" name="price_range" value="<?php echo $restaurantData['price_range']; ?>" placeholder="e.g., ₱299 - ₱699 per person">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <h5>Additional Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Type</label>
                                        <input type="text" class="form-control" name="type" value="<?php echo $restaurantData['type']; ?>" placeholder="e.g., Korean BBQ - Samgyupsal">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Established</label>
                                        <input type="text" class="form-control" name="established" value="<?php echo $restaurantData['established']; ?>" placeholder="e.g., 2020">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Rating</label>
                                        <input type="text" class="form-control" name="rating" value="<?php echo $restaurantData['rating']; ?>" placeholder="e.g., 4.5/5">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Capacity</label>
                                        <input type="text" class="form-control" name="capacity" value="<?php echo $restaurantData['capacity']; ?>" placeholder="e.g., 50 persons">
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" name="email" value="<?php echo $restaurantData['email']; ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Food Delivery Link</label>
                                        <input type="url" class="form-control" name="food_delivery" value="<?php echo $restaurantData['food_delivery']; ?>" placeholder="https://foodpanda.com/...">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Parking Information</label>
                                        <input type="text" class="form-control" name="parking" value="<?php echo $restaurantData['parking']; ?>" placeholder="e.g., Street parking available">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Bike Parking</label>
                                        <input type="text" class="form-control" name="bike_parking" value="<?php echo $restaurantData['bike_parking']; ?>" placeholder="e.g., Bike racks available">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Branches (one per line)</label>
                                <textarea class="form-control" name="branches" rows="5"><?php echo $restaurantData['branches']; ?></textarea>
                                <small class="text-muted">Enter each branch on a separate line</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Accessibility Features</label>
                                <input type="text" class="form-control" name="accessibility" value="<?php echo $restaurantData['accessibility']; ?>" placeholder="e.g., Wheelchair accessible, family-friendly">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Transportation Options (Text Description)</label>
                                <textarea class="form-control" name="transportation_options" rows="3" placeholder="e.g., Jeepney: Route 15
Taxi: Available 24/7
Ride-hailing: Grab available"><?php echo $restaurantData['transportation_options']; ?></textarea>
                                <small class="text-muted">Enter each option on a new line with format: Type: Description</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Nearby Places</label>
                                <input name="nearby_places" class="form-control" value="<?php echo $nearby_places_str; ?>" placeholder="Add nearby places separated by commas">
                                <small class="text-muted">Example: Mall, Park, University</small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Social Media Links</label>
                            <textarea class="form-control" name="social_media" rows="5" placeholder="Enter one URL per line, e.g.https://facebook.com/restaurant"><?php echo $restaurantData['social_media']; ?></textarea>
                        </div>
                        
                        <div class="form-section">
                            <h5>Location Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Address *</label>
                                        <input type="text" class="form-control" name="location" value="<?php echo $restaurantData['location']; ?>" required maxlength="255">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Website URL</label>
                                        <input type="url" class="form-control" name="website" value="<?php echo $restaurantData['website']; ?>" placeholder="https://example.com" maxlength="255">
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Google Maps Directions Link</label>
                                        <input type="url" class="form-control" name="directions_link" value="<?php echo $restaurantData['directions_link']; ?>" placeholder="https://maps.google.com/..." maxlength="255">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Google Map Link</label>
                                        <input type="url" class="form-control" name="maps_link" value="<?php echo $restaurantData['maps_link']; ?>" placeholder="https://maps.google.com/..." maxlength="255">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Embed Google Map URL</label>
                                <input type="url" class="form-control" name="map_embed" value="<?php echo $restaurantData['maps_embed']; ?>" placeholder="https://maps.google.com/embed..." maxlength="1000">
                                <small class="text-muted">Use the "Share" > "Embed a map" option from Google Maps</small>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <?php echo isset($_GET['edit']) ? 'Update Restaurant' : 'Save Restaurant'; ?>
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
                var modal = new bootstrap.Modal(document.getElementById('restaurantModal'));
                modal.show();
                
                history.replaceState(null, null, window.location.pathname);
            }
            
            var restaurantModal = document.getElementById('restaurantModal');
            if (restaurantModal) {
                restaurantModal.addEventListener('hidden.bs.modal', function () {
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
                        <option value="food_delivery">Food Delivery</option>
                    </select>
                </td>
                <td>
                    <input type="text" class="form-control" name="route_name[]" placeholder="e.g., Route 101, GrabFood, FoodPanda">
                </td>
                <td>
                    <input type="text" class="form-control" name="route_link[]" placeholder="https://maps.google.com/...">
                </td>
                <td>
                    <textarea class="form-control" name="route_description[]" rows="2" placeholder="e.g., Main transportation to the area, delivery options"></textarea>
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