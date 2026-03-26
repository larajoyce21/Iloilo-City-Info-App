<?php
session_start();
require 'conn.php';

if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    
    $stmt = $conn->prepare("SELECT image, image_detail FROM ilonggo_restaurants WHERE id=?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $restaurant = $result->fetch_assoc();
    
    $stmt = $conn->prepare("DELETE FROM ilonggo_restaurants WHERE id=?");
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
    
    header("Location: add_ilonggo_restaurants.php");
    exit();
}

if (isset($_GET['delete_image']) && isset($_GET['restaurant_id'])) {
    $image_path = $_GET['delete_image'];
    $restaurant_id = intval($_GET['restaurant_id']);
    $is_card_image = isset($_GET['is_card_image']) ? true : false;
    
    $real_image_path = 'uploads/' . basename($image_path);
    
    if (file_exists($real_image_path)) {
        unlink($real_image_path);
    }
    
    if ($is_card_image) {
        $stmt = $conn->prepare("UPDATE ilonggo_restaurants SET image='' WHERE id=?");
        $stmt->bind_param("i", $restaurant_id);
        $stmt->execute();
    } else {
        $stmt = $conn->prepare("SELECT image_detail FROM ilonggo_restaurants WHERE id=?");
        $stmt->bind_param("i", $restaurant_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $restaurant = $result->fetch_assoc();
        
        if ($restaurant) {
            $images = array_filter(explode(',', $restaurant['image_detail']));
            $updated_images = array_diff($images, [$image_path]);
            $updated_images_str = implode(',', $updated_images);
            
            $stmt = $conn->prepare("UPDATE ilonggo_restaurants SET image_detail=? WHERE id=?");
            $stmt->bind_param("si", $updated_images_str, $restaurant_id);
            $stmt->execute();
        }
    }
    
    $_SESSION['message'] = "Image deleted successfully";
    header("Location: add_ilonggo_restaurants.php?edit=" . $restaurant_id);
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
    $cuisine_type = $_POST['cuisine_type'] ?? '';
    $price_range = $_POST['price_range'] ?? '';
    $accessibility = $_POST['accessibility'] ?? '';
    $signature_dishes = $_POST['signature_dishes'] ?? '';
    $email = $_POST['email'] ?? '';
    $hotline = $_POST['hotline'] ?? '';
    $branches = $_POST['branches'] ?? '';
    $social_media = $_POST['social_media'] ?? '';
    $capacity = $_POST['capacity'] ?? '';
    $delivery_app = $_POST['delivery_app'] ?? '';
    $nearby_places = $_POST['nearby_places'] ?? '';
    $details_link = $_POST['details_link'] ?? '';

    if (empty($name) || empty($description) || empty($location)) {
        $_SESSION['error'] = "Please fill in all required fields";
        header("Location: add_ilonggo_restaurants.php" . ($id ? "?edit=$id" : ""));
        exit();
    }
    
    $target_file = $_POST['existing_image'] ?? '';
    
    if (isset($_FILES['image']['name']) && $_FILES['image']['name']) {
        $file = $_FILES['image'];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($file_ext, $allowed_types)) {
            $new_filename = uniqid('img_', true) . '.' . $file_ext;
            $target_file = 'uploads/' . $new_filename;
            
            if (move_uploaded_file($file['tmp_name'], $target_file)) {
                if (!empty($_POST['existing_image']) && file_exists($_POST['existing_image'])) {
                    unlink($_POST['existing_image']);
                }
            } else {
                $_SESSION['error'] = "Error uploading the main image";
                header("Location: add_ilonggo_restaurants.php" . ($id ? "?edit=$id" : ""));
                exit();
            }
        } else {
            $_SESSION['error'] = "Invalid file type. Only JPG, JPEG, PNG, GIF are allowed";
            header("Location: add_ilonggo_restaurants.php" . ($id ? "?edit=$id" : ""));
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
            header("Location: add_ilonggo_restaurants.php" . ($id ? "?edit=$id" : ""));
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
        $stmt = $conn->prepare("UPDATE ilonggo_restaurants SET name=?, image=?, image_detail=?, description=?, location=?, maps_embed=?, maps_link=?, website=?, directions_link=?, hours=?, contact=?, cuisine_type=?, established=?, price_range=?, accessibility=?, signature_dishes=?, email=?, hotline=?, branches=?, social_media=?, capacity=?, delivery_app=?, nearby_places=?, details_link=? WHERE id=?");
        $stmt->bind_param("ssssssssssssssssssssssssi", $name, $target_file, $target_file_detail, $description, $location, $mapEmbedURL, $maps_link, $website, $directions_link, $hours, $contact, $cuisine_type, $established, $price_range, $accessibility, $signature_dishes, $email, $hotline, $branches, $social_media, $capacity, $delivery_app, $nearby_places, $details_link, $id);
    } else {
        $stmt = $conn->prepare("INSERT INTO ilonggo_restaurants (name, image, image_detail, description, location, maps_embed, maps_link, website, directions_link, hours, contact, cuisine_type, established, price_range, accessibility, signature_dishes, email, hotline, branches, social_media, capacity, delivery_app, nearby_places, details_link) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssssssssssssssssssss", $name, $target_file, $target_file_detail, $description, $location, $mapEmbedURL, $maps_link, $website, $directions_link, $hours, $contact, $cuisine_type, $established, $price_range, $accessibility, $signature_dishes, $email, $hotline, $branches, $social_media, $capacity, $delivery_app, $nearby_places, $details_link);
    }

    if ($stmt->execute()) {
        $_SESSION['message'] = "Restaurant " . ($id ? "updated" : "added") . " successfully";
    } else {
        $_SESSION['error'] = "Error saving restaurant: " . $stmt->error;
    }
    
    header("Location: add_ilonggo_restaurants.php");
    exit();
}

$query = "SELECT * FROM ilonggo_restaurants ORDER BY name ASC";
$result = $conn->query($query);
$restaurants = $result->fetch_all(MYSQLI_ASSOC);

$nearby_places_str = '';
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT nearby_places FROM ilonggo_restaurants WHERE id=?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $nearby_places_str = $row['nearby_places'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ilonggo Restaurants Management</title>
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
            <h1 class="text-light">Ilonggo Restaurants Management</h1>
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
                                    <a href="add_ilonggo_restaurants.php?edit=<?php echo $restaurant['id']; ?>" class="btn btn-primary btn-sm">
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
        <div class="modal-dialog modal-lg">
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
                            'cuisine_type' => '',
                            'established' => '',
                            'price_range' => '',
                            'accessibility' => '',
                            'signature_dishes' => '',
                            'email' => '',
                            'hotline' => '',
                            'branches' => '',
                            'social_media' => '',
                            'capacity' => '',
                            'delivery_app' => '',
                            'nearby_places' => '',
                            'details_link' => ''
                        ];
                        
                        if (isset($_GET['edit'])) {
                            $edit_id = (int)$_GET['edit'];
                            $stmt = $conn->prepare("SELECT * FROM ilonggo_restaurants WHERE id=?");
                            $stmt->bind_param("i", $edit_id);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            if ($result->num_rows > 0) {
                                $restaurantData = $result->fetch_assoc();
                            }
                        }
                    ?>
                    <form method="POST" action="add_ilonggo_restaurants.php" enctype="multipart/form-data">
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
                                        <input type="text" class="form-control" name="details_link" value="<?php echo htmlspecialchars($restaurantData['details_link']); ?>" placeholder="e.g., ilonggo_restaurant1.php?id=1">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Description *</label>
                                        <textarea class="form-control" name="description" rows="4" required maxlength="2000"><?php echo $restaurantData['description']; ?></textarea>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Operating Hours</label>
                                        <textarea class="form-control" name="hours" rows="4" placeholder="e.g., Monday: 9AM - 9PM"><?php echo $restaurantData['hours']; ?></textarea>
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
                                                    <a href="?delete_image=<?php echo urlencode($img); ?>&restaurant_id=<?php echo $restaurantData['id']; ?>" 
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
                            <h5>Restaurant Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Cuisine Type</label>
                                        <input type="text" class="form-control" name="cuisine_type" value="<?php echo $restaurantData['cuisine_type']; ?>" placeholder="e.g., Ilonggo, Filipino, Seafood">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Established</label>
                                        <input type="text" class="form-control" name="established" value="<?php echo $restaurantData['established']; ?>" placeholder="e.g., 1995">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Price Range</label>
                                        <input type="text" class="form-control" name="price_range" value="<?php echo $restaurantData['price_range']; ?>" placeholder="e.g., ₱200-₱500 per person">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Capacity</label>
                                        <input type="text" class="form-control" name="capacity" value="<?php echo $restaurantData['capacity']; ?>" placeholder="e.g., 50 persons">
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Signature Dishes</label>
                                        <textarea class="form-control" name="signature_dishes" rows="3" placeholder="One dish per line"><?php echo $restaurantData['signature_dishes']; ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" name="email" value="<?php echo $restaurantData['email']; ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Reservation Hotline</label>
                                        <input type="text" class="form-control" name="hotline" value="<?php echo $restaurantData['hotline']; ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Delivery App Link</label>
                                        <input type="url" class="form-control" name="delivery_app" value="<?php echo $restaurantData['delivery_app']; ?>" placeholder="e.g., FoodPanda, GrabFood link">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Accessibility Features</label>
                                <input type="text" class="form-control" name="accessibility" value="<?php echo $restaurantData['accessibility']; ?>" placeholder="e.g., Wheelchair accessible, Family-friendly">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Branches (one per line)</label>
                                <textarea class="form-control" name="branches" rows="5"><?php echo htmlspecialchars($restaurantData['branches']); ?></textarea>
                                <small class="text-muted">Enter each branch on a separate line</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Nearby Places</label>
                                <input name="nearby_places" class="form-control" value="<?php echo $nearby_places_str; ?>" placeholder="Add nearby places separated by commas">
                                <small class="text-muted">Example: Museum, Park, Shopping Mall</small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Social Media Links</label>
                            <textarea class="form-control" name="social_media" rows="5" placeholder="Enter one URL per line"><?php echo $restaurantData['social_media']; ?></textarea>
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
    </script>
</body>
</html>
<?php $conn->close(); ?>