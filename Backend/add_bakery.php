<?php
session_start();
include 'conn.php';

if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    
    $result = mysqli_query($conn, "SELECT image, image_detail FROM bakeries WHERE id=$delete_id");
    $bakery = mysqli_fetch_assoc($result);
    
    if (mysqli_query($conn, "DELETE FROM bakeries WHERE id=$delete_id")) {
        if ($bakery) {
            if (!empty($bakery['image']) && file_exists($bakery['image'])) {
                unlink($bakery['image']);
            }
            
            if (!empty($bakery['image_detail'])) {
                $images = explode(',', $bakery['image_detail']);
                foreach ($images as $img) {
                    if (!empty($img) && file_exists($img)) {
                        unlink($img);
                    }
                }
            }
        }
        
        $_SESSION['message'] = "Bakery deleted successfully";
    } else {
        $_SESSION['error'] = "Error deleting bakery: " . mysqli_error($conn);
    }
    
    header("Location: add_bakery.php");
    exit();
}

if (isset($_GET['delete_image']) && isset($_GET['bakery_id'])) {
    $image_path = urldecode($_GET['delete_image']);
    $bakery_id = intval($_GET['bakery_id']);
    $is_main_image = isset($_GET['is_main_image']) ? true : false;
    
    if (file_exists($image_path)) {
        unlink($image_path);
    }
    
    if ($is_main_image) {
        mysqli_query($conn, "UPDATE bakeries SET image='' WHERE id=$bakery_id");
    } else {
        $result = mysqli_query($conn, "SELECT image_detail FROM bakeries WHERE id=$bakery_id");
        $bakery = mysqli_fetch_assoc($result);
        
        if ($bakery) {
            $images = explode(',', $bakery['image_detail']);
            $updated_images = array();
            foreach ($images as $img) {
                if (trim($img) != trim($image_path)) {
                    $updated_images[] = $img;
                }
            }
            $updated_images_str = implode(',', $updated_images);
            
            mysqli_query($conn, "UPDATE bakeries SET image_detail='$updated_images_str' WHERE id=$bakery_id");
        }
    }
    
    $_SESSION['message'] = "Image deleted successfully";
    header("Location: add_bakery.php?edit=" . $bakery_id);
    exit();
}

$query = "SELECT id, name, image, description, location, hours, contact, specialty FROM bakeries";
$bakeries = mysqli_query($conn, $query);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : null;
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $history = trim($_POST['history'] ?? '');
    $menu = trim($_POST['menu'] ?? '');
    $offers = trim($_POST['offers'] ?? '');
    $hours = trim($_POST['hours'] ?? '');
    $contact = trim($_POST['contact'] ?? '');
    $location = trim($_POST['location']);
    $maps_link = trim($_POST['maps_link'] ?? '');
    $maps_embed = trim($_POST['maps_embed'] ?? '');
    $website = trim($_POST['website'] ?? '');
    $directions = trim($_POST['directions'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $social_media = trim($_POST['social_media'] ?? '');
    $year_established = trim($_POST['year_established'] ?? '');
    $specialty = trim($_POST['specialty'] ?? '');
    $details_link = trim($_POST['details_link'] ?? '');
    $nearby_places = trim($_POST['nearby_places'] ?? '');
    $branches = trim($_POST['branches'] ?? '');

    // Handle transportation routes
    $transportation_routes = array();
    if (isset($_POST['route_type']) && is_array($_POST['route_type'])) {
        foreach ($_POST['route_type'] as $index => $type) {
            if (!empty($type) && !empty($_POST['route_name'][$index])) {
                $transportation_routes[] = array(
                    'type' => $type,
                    'name' => $_POST['route_name'][$index],
                    'link' => $_POST['route_link'][$index] ?? '',
                    'description' => $_POST['route_description'][$index] ?? ''
                );
            }
        }
    }
    $transportation_routes_json = json_encode($transportation_routes);

    $target_dir = "uploads/";
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
    $target_file = $_POST['existing_image'] ?? '';

    if (isset($_POST['delete_image']) && $_POST['delete_image'] == '1') {
        if (file_exists($target_file)) {
            unlink($target_file);
        }
        $target_file = ''; 
    }

    if (!empty($_FILES['image']['name'])) {
        $target_file = $target_dir . basename($_FILES["image"]["name"]);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        if (in_array($imageFileType, $allowed_types)) {
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                if (!empty($_POST['existing_image']) && file_exists($_POST['existing_image'])) {
                    unlink($_POST['existing_image']);
                }
            } else {
                die("Error uploading the main image.");
            }
        } else {
            die("Error: Only JPG, JPEG, PNG, and GIF files are allowed.");
        }
    }

    $imagePaths = [];

    $existingImages = [];
    if (!empty($_POST['existing_image_detail'])) {
        $existingImages = explode(',', $_POST['existing_image_detail']);
        $existingImages = array_filter($existingImages); 
    }
    
    if (!empty($_FILES['image_detail']['name'][0])) {
        $totalNew = count($_FILES['image_detail']['name']);
        $totalCombined = count($existingImages) + $totalNew;
    
        if ($totalCombined > 10) {
            die("Error: You can upload a maximum of 10 detail images total.");
        }
    
        for ($i = 0; $i < $totalNew; $i++) {
            if ($_FILES['image_detail']['error'][$i] !== UPLOAD_ERR_OK) {
                continue; 
            }
            
            $fileName = basename($_FILES['image_detail']['name'][$i]);
            $tmpName = $_FILES['image_detail']['tmp_name'][$i];
            $imageFileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
            if (in_array($imageFileType, $allowed_types)) {
                $uniqueName = uniqid('img_', true) . '.' . $imageFileType;
                $targetPath = $target_dir . $uniqueName;
    
                if (!move_uploaded_file($tmpName, $targetPath)) {
                    die("Error uploading one of the images.");
                }
                $imagePaths[] = $targetPath;
            } else {
                die("Error: Only JPG, JPEG, PNG, and GIF files are allowed.");
            }
        }
    }
    
    $allImages = array_merge($existingImages, $imagePaths);
    $target_file_detail = implode(',', $allImages);
       
    if ($id) {
        $query = "UPDATE bakeries SET 
            name='$name', 
            image='$target_file', 
            image_detail='$target_file_detail', 
            description='$description', 
            history='$history', 
            menu='$menu', 
            offers='$offers', 
            hours='$hours', 
            contact='$contact', 
            location='$location', 
            maps_link='$maps_link', 
            maps_embed='$maps_embed', 
            website='$website', 
            directions='$directions', 
            email='$email', 
            social_media='$social_media', 
            year_established='$year_established', 
            specialty='$specialty', 
            branches='$branches', 
            nearby_places='$nearby_places', 
            details_link='$details_link', 
            transportation_routes='$transportation_routes_json' 
            WHERE id=$id";
    } else {
        $query = "INSERT INTO bakeries (
            name, image, image_detail, description, history, menu, offers, hours, contact, 
            location, maps_link, maps_embed, website, directions, email, social_media, 
            year_established, specialty, branches, nearby_places, details_link, transportation_routes
        ) VALUES (
            '$name', '$target_file', '$target_file_detail', '$description', '$history', 
            '$menu', '$offers', '$hours', '$contact', '$location', '$maps_link', 
            '$maps_embed', '$website', '$directions', '$email', '$social_media', 
            '$year_established', '$specialty', '$branches', '$nearby_places', 
            '$details_link', '$transportation_routes_json'
        )";
    }
    
    if (mysqli_query($conn, $query)) {
        $_SESSION['message'] = "Bakery " . ($id ? "updated" : "added") . " successfully";
    } else {
        $_SESSION['error'] = "Error saving bakery: " . mysqli_error($conn);
    }
    
    header("Location: add_bakery.php");
    exit();
}

$transportation_routes = array();

if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $result = mysqli_query($conn, "SELECT transportation_routes FROM bakeries WHERE id=$edit_id");
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        if (!empty($row['transportation_routes'])) {
            $decoded_routes = json_decode($row['transportation_routes'], true);
            if (is_array($decoded_routes)) {
                $transportation_routes = $decoded_routes;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bakeries Management</title>
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
            text-decoration: none;
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
        .transport-route-item {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 10px;
        }
        .transport-route-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
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
            <h1 class="text-light">Bakeries Management</h1>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#bakeryModal">
                <i class="bi bi-plus-lg"></i> Add Bakery
            </button>
        </div>
        
        <?php if (mysqli_num_rows($bakeries) == 0): ?>
            <div class="alert alert-info">
                No bakeries found. Click "Add Bakery" to create one.
            </div>
        <?php else: ?>
            <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-4">
                <?php while ($bakery = mysqli_fetch_assoc($bakeries)): ?>
                    <div class="col">
                        <div class="card h-100">
                            <img src="<?php echo htmlspecialchars($bakery['image']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($bakery['name']); ?>">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?php echo htmlspecialchars($bakery['name']); ?></h5>
                                <p class="card-text flex-grow-1"><?php echo substr(htmlspecialchars($bakery['description']), 0, 100) . '...'; ?></p>
                                <div class="d-flex justify-content-between">
                                    <a href="add_bakery.php?edit=<?php echo $bakery['id']; ?>" class="btn btn-primary btn-sm">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    <a href="?delete=<?php echo $bakery['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this bakery?')">
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

    <div class="modal fade <?php echo isset($_GET['edit']) ? 'show d-block' : ''; ?>" id="bakeryModal" tabindex="-1" aria-labelledby="bakeryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="bakeryModalLabel"><?php echo isset($_GET['edit']) ? 'Edit Bakery' : 'Add Bakery'; ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php 
                        $bakeryData = [
                            'id' => '',
                            'name' => '',
                            'description' => '',
                            'history' => '',
                            'menu' => '',
                            'offers' => '',
                            'hours' => '',
                            'contact' => '',
                            'location' => '',
                            'maps_link' => '',
                            'maps_embed' => '',
                            'website' => '',
                            'directions' => '',
                            'email' => '',
                            'social_media' => '',
                            'year_established' => '',
                            'specialty' => '',
                            'branches' => '',
                            'image' => '',
                            'image_detail' => '',
                            'nearby_places' => '',
                            'details_link' => '',
                            'transportation_routes' => ''
                        ];
                        
                        if (isset($_GET['edit'])) {
                            $edit_id = intval($_GET['edit']);
                            $result = mysqli_query($conn, "SELECT * FROM bakeries WHERE id=$edit_id");
                            if (mysqli_num_rows($result) > 0) {
                                $bakeryData = mysqli_fetch_assoc($result);
                            }
                        }
                    ?>
                    <form method="POST" action="add_bakery.php" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($bakeryData['id']); ?>">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-section">
                                    <h5>Basic Information</h5>
                                    <div class="mb-3">
                                        <label class="form-label">Name *</label>
                                        <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($bakeryData['name']); ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Description</label>
                                        <textarea class="form-control" name="description" rows="4"><?php echo htmlspecialchars($bakeryData['description']); ?></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">History</label>
                                        <textarea class="form-control" name="history" rows="4"><?php echo htmlspecialchars($bakeryData['history']); ?></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Year Established</label>
                                        <input type="text" class="form-control" name="year_established" value="<?php echo htmlspecialchars($bakeryData['year_established']); ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Specialty</label>
                                        <input type="text" class="form-control" name="specialty" value="<?php echo htmlspecialchars($bakeryData['specialty']); ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Branches (one per line)</label>
                                        <textarea class="form-control" name="branches" rows="5"><?php echo htmlspecialchars($bakeryData['branches']); ?></textarea>
                                    </div>
                                </div>

                                <div class="form-section">
                                    <h5>Contact Information</h5>
                                    <div class="mb-3">
                                        <label class="form-label">Contact Number</label>
                                        <input type="text" class="form-control" name="contact" value="<?php echo htmlspecialchars($bakeryData['contact']); ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Email Address</label>
                                        <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($bakeryData['email']); ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Website</label>
                                        <input type="url" class="form-control" name="website" value="<?php echo htmlspecialchars($bakeryData['website']); ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Social Media Links</label>
                                        <textarea class="form-control" name="social_media" rows="5"><?php echo htmlspecialchars($bakeryData['social_media']); ?></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Details Link</label>
                                        <input type="text" class="form-control" name="details_link" value="<?php echo htmlspecialchars($bakeryData['details_link']); ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-section">
                                    <h5>Location Information</h5>
                                    <div class="mb-3">
                                        <label class="form-label">Address *</label>
                                        <textarea class="form-control" name="location" rows="3" required><?php echo htmlspecialchars($bakeryData['location']); ?></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Google Maps Link</label>
                                        <input type="url" class="form-control" name="maps_link" value="<?php echo htmlspecialchars($bakeryData['maps_link']); ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Google Maps Embed URL</label>
                                        <input type="url" class="form-control" name="maps_embed" value="<?php echo htmlspecialchars($bakeryData['maps_embed']); ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Directions</label>
                                        <textarea class="form-control" name="directions" rows="3"><?php echo htmlspecialchars($bakeryData['directions']); ?></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Nearby Places</label>
                                        <input type="text" class="form-control" name="nearby_places" value="<?php echo htmlspecialchars($bakeryData['nearby_places']); ?>">
                                    </div>
                                </div>

                                <div class="form-section">
                                    <h5>Hours & Menu</h5>
                                    <div class="mb-3">
                                        <label class="form-label">Operating Hours</label>
                                        <textarea class="form-control" name="hours" rows="5" placeholder="Monday-Friday: 9AM - 5PM"><?php echo htmlspecialchars($bakeryData['hours']); ?></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Menu</label>
                                        <textarea class="form-control" name="menu" rows="5"><?php echo htmlspecialchars($bakeryData['menu']); ?></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Special Offers</label>
                                        <textarea class="form-control" name="offers" rows="5"><?php echo htmlspecialchars($bakeryData['offers']); ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <h5>Images</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Main Image</label>
                                        <input type="file" class="form-control" name="image" accept="image/*">
                                        
                                        <?php if (!empty($bakeryData['image'])) { ?>
                                            <div class="mt-2">
                                                <div class="position-relative d-inline-block">
                                                    <img src="<?php echo htmlspecialchars($bakeryData['image']); ?>" class="img-thumbnail" width="150">
                                                    <a href="?delete_image=<?php echo urlencode($bakeryData['image']); ?>&bakery_id=<?php echo $bakeryData['id']; ?>&is_main_image=1" 
                                                       class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" 
                                                       onclick="return confirm('Are you sure you want to delete this image?')">
                                                       ×
                                                    </a>
                                                </div>
                                                <div class="form-check mt-2">
                                                    <input class="form-check-input" type="checkbox" name="delete_image" value="1" id="deleteImageCheck">
                                                    <label class="form-check-label" for="deleteImageCheck">Delete current image</label>
                                                </div>
                                            </div>
                                            <input type="hidden" name="existing_image" value="<?php echo htmlspecialchars($bakeryData['image']); ?>">
                                        <?php } ?>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Detail Images (Max 10)</label>
                                        <input type="file" class="form-control" name="image_detail[]" multiple accept="image/*">
                                        <?php if (!empty($bakeryData['image_detail'])) {
                                            $images = explode(',', $bakeryData['image_detail']); 
                                            echo '<div class="d-flex flex-wrap mt-2">';
                                            foreach ($images as $img) {
                                                if (!empty($img)) { ?>
                                                    <div class="position-relative me-2 mb-2">
                                                        <img src="<?php echo htmlspecialchars($img); ?>" class="img-thumbnail" width="100">
                                                        <a href="?delete_image=<?php echo urlencode($img); ?>&bakery_id=<?php echo $bakeryData['id']; ?>" 
                                                           class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" 
                                                           onclick="return confirm('Are you sure you want to delete this image?')">
                                                           ×
                                                        </a>
                                                    </div>
                                                <?php }
                                            }
                                            echo '</div>'; ?>
                                            <input type="hidden" name="existing_image_detail" value="<?php echo htmlspecialchars($bakeryData['image_detail']); ?>">
                                        <?php } ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <h5>Transportation Routes</h5>
                            <div id="transport-routes-container">
                                <?php if (!empty($transportation_routes)): ?>
                                    <?php foreach ($transportation_routes as $index => $route): ?>
                                        <div class="transport-route-item">
                                            <div class="transport-route-header">
                                                <h6 class="mb-0">Route #<?php echo $index + 1; ?></h6>
                                                <button type="button" class="btn btn-danger btn-sm remove-route-btn" onclick="removeRoute(this)">
                                                    <i class="bi bi-trash"></i> Remove
                                                </button>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Transport Type</label>
                                                        <select class="form-control" name="route_type[]">
                                                            <option value="jeepney" <?php echo ($route['type'] == 'jeepney') ? 'selected' : ''; ?>>Jeepney</option>
                                                            <option value="bus" <?php echo ($route['type'] == 'bus') ? 'selected' : ''; ?>>Bus</option>
                                                            <option value="taxi" <?php echo ($route['type'] == 'taxi') ? 'selected' : ''; ?>>Taxi</option>
                                                            <option value="tricycle" <?php echo ($route['type'] == 'tricycle') ? 'selected' : ''; ?>>Tricycle</option>
                                                        </select>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Route Name/Number</label>
                                                        <input type="text" class="form-control" name="route_name[]" value="<?php echo htmlspecialchars($route['name']); ?>">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Route Link (Optional)</label>
                                                        <input type="text" class="form-control" name="route_link[]" value="<?php echo htmlspecialchars($route['link'] ?? ''); ?>">
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Description (Optional)</label>
                                                        <textarea class="form-control" name="route_description[]" rows="2"><?php echo htmlspecialchars($route['description'] ?? ''); ?></textarea>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="transport-route-item">
                                        <div class="transport-route-header">
                                            <h6 class="mb-0">Route #1</h6>
                                            <button type="button" class="btn btn-danger btn-sm remove-route-btn" onclick="removeRoute(this)">
                                                <i class="bi bi-trash"></i> Remove
                                            </button>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Transport Type</label>
                                                    <select class="form-control" name="route_type[]">
                                                        <option value="jeepney">Jeepney</option>
                                                        <option value="bus">Bus</option>
                                                        <option value="taxi">Taxi</option>
                                                        <option value="tricycle">Tricycle</option>
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Route Name/Number</label>
                                                    <input type="text" class="form-control" name="route_name[]">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Route Link (Optional)</label>
                                                    <input type="text" class="form-control" name="route_link[]">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Description (Optional)</label>
                                                    <textarea class="form-control" name="route_description[]" rows="2"></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <button type="button" class="btn btn-secondary btn-sm mt-2" onclick="addTransportRoute()">
                                <i class="bi bi-plus"></i> Add Another Route
                            </button>
                        </div>
                        
                        <div class="d-flex justify-content-end gap-2 mt-3">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <?php echo isset($_GET['edit']) ? 'Update Bakery' : 'Save Bakery'; ?>
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
                var modal = new bootstrap.Modal(document.getElementById('bakeryModal'));
                modal.show();
                
                history.replaceState(null, null, window.location.pathname);
            }
        });

        let routeCounter = <?php echo count($transportation_routes); ?>;
        
        function addTransportRoute() {
            routeCounter++;
            const container = document.getElementById('transport-routes-container');
            const newRoute = document.createElement('div');
            newRoute.className = 'transport-route-item';
            
            newRoute.innerHTML = `
                <div class="transport-route-header">
                    <h6 class="mb-0">Route #${routeCounter}</h6>
                    <button type="button" class="btn btn-danger btn-sm remove-route-btn" onclick="removeRoute(this)">
                        <i class="bi bi-trash"></i> Remove
                    </button>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Transport Type</label>
                            <select class="form-control" name="route_type[]">
                                <option value="jeepney">Jeepney</option>
                                <option value="bus">Bus</option>
                                <option value="taxi">Taxi</option>
                                <option value="tricycle">Tricycle</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Route Name/Number</label>
                            <input type="text" class="form-control" name="route_name[]">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Route Link (Optional)</label>
                            <input type="text" class="form-control" name="route_link[]">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description (Optional)</label>
                            <textarea class="form-control" name="route_description[]" rows="2"></textarea>
                        </div>
                    </div>
                </div>
            `;
            
            container.appendChild(newRoute);
            
            // Update route numbers
            updateRouteNumbers();
        }
        
        function removeRoute(button) {
            if (confirm('Are you sure you want to remove this route?')) {
                const routeItem = button.closest('.transport-route-item');
                routeItem.remove();
                updateRouteNumbers();
            }
        }
        
        function updateRouteNumbers() {
            const routeItems = document.querySelectorAll('.transport-route-item');
            routeItems.forEach((item, index) => {
                const header = item.querySelector('.transport-route-header h6');
                if (header) {
                    header.textContent = `Route #${index + 1}`;
                }
            });
            routeCounter = routeItems.length;
        }
    </script>
</body>
</html>
<?php mysqli_close($conn); ?>