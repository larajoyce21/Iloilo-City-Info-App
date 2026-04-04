<?php
session_start();
include 'conn.php';

if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    
    $stmt = $conn->prepare("SELECT image, image_detail FROM car_dealerships WHERE id=?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $dealership = $result->fetch_assoc();
    
    $stmt = $conn->prepare("DELETE FROM car_dealerships WHERE id=?");
    $stmt->bind_param("i", $delete_id);
    
    if ($stmt->execute()) {
        if ($dealership) {
            if (!empty($dealership['image']) && file_exists($dealership['image'])) {
                unlink($dealership['image']);
            }
            
            if (!empty($dealership['image_detail'])) {
                $images = explode(',', $dealership['image_detail']);
                foreach ($images as $img) {
                    if (!empty($img) && file_exists($img)) {
                        unlink($img);
                    }
                }
            }
        }
        
        $_SESSION['message'] = "Car dealership deleted successfully";
    } else {
        $_SESSION['error'] = "Error deleting car dealership: " . $conn->error;
    }
    
    header("Location: add_car_dealerships.php");
    exit();
}

if (isset($_GET['delete_image']) && isset($_GET['dealership_id'])) {
    $image_path = urldecode($_GET['delete_image']);
    $dealership_id = intval($_GET['dealership_id']);
    $is_card_image = isset($_GET['is_card_image']) ? true : false;
    
    if (file_exists($image_path)) {
        unlink($image_path);
    }
    
    if ($is_card_image) {
        $stmt = $conn->prepare("UPDATE car_dealerships SET image='' WHERE id=?");
        $stmt->bind_param("i", $dealership_id);
        $stmt->execute();
    } else {
        $stmt = $conn->prepare("SELECT image_detail FROM car_dealerships WHERE id=?");
        $stmt->bind_param("i", $dealership_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $dealership = $result->fetch_assoc();
        
        if ($dealership) {
            $images = explode(',', $dealership['image_detail']);
            $updated_images = array();
            foreach ($images as $img) {
                if (trim($img) != trim($image_path)) {
                    $updated_images[] = $img;
                }
            }
            $updated_images_str = implode(',', $updated_images);
            
            $stmt = $conn->prepare("UPDATE car_dealerships SET image_detail=? WHERE id=?");
            $stmt->bind_param("si", $updated_images_str, $dealership_id);
            $stmt->execute();
        }
    }
    
    $_SESSION['message'] = "Image deleted successfully";
    header("Location: add_car_dealerships.php?edit=" . $dealership_id);
    exit();
}

function convertToEmbedURL($googleMapsLink) {
    if (strpos($googleMapsLink, 'goo.gl/maps') !== false || strpos($googleMapsLink, 'google.com/maps') !== false) {
        return str_replace("maps/place/", "maps/embed?pb=", $googleMapsLink);
    }
    return $googleMapsLink;
}

function validateImage($file) {
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
    $max_size = 2 * 1024 * 1024; 
    
    $filename = $file['name'];
    $filesize = $file['size'];
    $filetype = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    if (!in_array($filetype, $allowed_types)) {
        return "Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.";
    }
    
    if ($filesize > $max_size) {
        return "File is too large. Maximum size is 2MB.";
    }
    
    return true;
}

$query = "SELECT id, name, image, description, location, maps_embed, directions_link, maps_link, website, hours, contact, brands_available, services_offered, social_media, details_link, email, transportation_routes, nearby_places FROM car_dealerships";
$stmt = $conn->prepare($query);
$stmt->execute();
$dealerships = $stmt->get_result();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : null;
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $hours = trim($_POST['hours'] ?? '');
    $contact = trim($_POST['contact'] ?? '');
    $location = trim($_POST['location']);
    $website = trim($_POST['website']);
    $maps_link = trim($_POST['maps_link']);
    $directions_link = trim($_POST['directions_link']);
    $mapEmbedURL = htmlspecialchars(trim($_POST['maps_embed'] ?? ''));
    $social_media = trim($_POST['social_media']);
    $details_link = trim($_POST['details_link']);
    $email = trim($_POST['email'] ?? '');

    $nearby_places = $_POST['nearby_places'] ?? '';
    $nearby_places_array = array_map('trim', explode(',', $nearby_places));
    $nearby_places_json = json_encode(array_filter($nearby_places_array));

    $brands_available = $_POST['brands_available'] ?? '';
    $brands_available_array = array_map('trim', explode(',', $brands_available));
    $brands_available_json = json_encode(array_filter($brands_available_array));

    $services_offered = $_POST['services_offered'] ?? '';
    $services_offered_array = array_map('trim', explode(',', $services_offered));
    $services_offered_json = json_encode(array_filter($services_offered_array));

    $transportation_routes = array();
    if (isset($_POST['route_type']) && is_array($_POST['route_type'])) {
        foreach ($_POST['route_type'] as $index => $type) {
            if (!empty($type) && !empty($_POST['route_name'][$index])) {
                $route = array(
                    'type' => $type,
                    'name' => $_POST['route_name'][$index]
                );
                
                if (isset($_POST['route_link'][$index]) && !empty($_POST['route_link'][$index])) {
                    $route['link'] = $_POST['route_link'][$index];
                }
                
                if (isset($_POST['route_description'][$index]) && !empty($_POST['route_description'][$index])) {
                    $route['description'] = $_POST['route_description'][$index];
                }
                
                $transportation_routes[] = $route;
            }
        }
    }
    $transportation_routes_json = json_encode($transportation_routes);

    $target_dir = "uploads/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
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
    
        if ($totalCombined > 5) {
            die("Error: You can upload a maximum of 5 detail images total.");
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
        $query = "UPDATE car_dealerships SET name=?, image=?, image_detail=?, description=?, location=?, maps_embed=?, maps_link=?, website=?, directions_link=?, hours=?, contact=?, brands_available=?, services_offered=?, social_media=?, details_link=?, nearby_places=?, email=?, transportation_routes=? WHERE id=?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssssssssssssssssi", $name, $target_file, $target_file_detail, $description, $location, $mapEmbedURL, $maps_link, $website, $directions_link, $hours, $contact, $brands_available_json, $services_offered_json, $social_media, $details_link, $nearby_places_json, $email, $transportation_routes_json, $id);
    } else {
        $query = "INSERT INTO car_dealerships (name, image, image_detail, description, location, maps_embed, maps_link, website, directions_link, hours, contact, brands_available, services_offered, social_media, details_link, nearby_places, email, transportation_routes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssssssssssssssss", $name, $target_file, $target_file_detail, $description, $location, $mapEmbedURL, $maps_link, $website, $directions_link, $hours, $contact, $brands_available_json, $services_offered_json, $social_media, $details_link, $nearby_places_json, $email, $transportation_routes_json);
    }
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Car dealership " . ($id ? "updated" : "added") . " successfully";
    } else {
        $_SESSION['error'] = "Error saving car dealership: " . $conn->error;
    }
    
    header("Location: add_car_dealerships.php");
    exit();
}

$brands_available_str = '';
$services_offered_str = '';
$nearby_places_str = '';
$transportation_routes = array();

if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM car_dealerships WHERE id=?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $dealershipData = $result->fetch_assoc();
        
        if (!empty($dealershipData['brands_available'])) {
            $decoded_brands = json_decode($dealershipData['brands_available'], true);
            if (is_array($decoded_brands)) {
                $brands_available_str = implode(', ', $decoded_brands);
            }
        }
        
        if (!empty($dealershipData['services_offered'])) {
            $decoded_services = json_decode($dealershipData['services_offered'], true);
            if (is_array($decoded_services)) {
                $services_offered_str = implode(', ', $decoded_services);
            }
        }
        
        if (!empty($dealershipData['nearby_places'])) {
            $decoded_places = json_decode($dealershipData['nearby_places'], true);
            if (is_array($decoded_places)) {
                $nearby_places_str = implode(', ', $decoded_places);
            }
        }
        
        if (!empty($dealershipData['transportation_routes'])) {
            $decoded_routes = json_decode($dealershipData['transportation_routes'], true);
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
    <title>Car Dealerships Management</title>
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
            <h1 class="text-light">Car Dealerships Management</h1>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#dealershipModal">
                <i class="bi bi-plus-lg"></i> Add Dealership
            </button>
        </div>
        
        <?php if ($dealerships->num_rows == 0): ?>
            <div class="alert alert-info">
                No car dealerships found. Click "Add Dealership" to create one.
            </div>
        <?php else: ?>
            <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-4">
                <?php while ($dealership = $dealerships->fetch_assoc()): ?>
                    <div class="col">
                        <div class="card h-100">
                            <img src="<?php echo htmlspecialchars($dealership['image']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($dealership['name']); ?>">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?php echo htmlspecialchars($dealership['name']); ?></h5>
                                <p class="card-text flex-grow-1"><?php echo substr(htmlspecialchars($dealership['description']), 0, 100) . '...'; ?></p>
                                <div class="d-flex justify-content-between">
                                    <a href="add_car_dealerships.php?edit=<?php echo $dealership['id']; ?>" class="btn btn-primary btn-sm">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    <a href="?delete=<?php echo $dealership['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this car dealership?')">
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

    <div class="modal fade <?php echo isset($_GET['edit']) ? 'show d-block' : ''; ?>" id="dealershipModal" tabindex="-1" aria-labelledby="dealershipModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="dealershipModalLabel"><?php echo isset($_GET['edit']) ? 'Edit Car Dealership' : 'Add Car Dealership'; ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php 
                        $dealershipData = [
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
                            'brands_available' => '',
                            'services_offered' => '',
                            'social_media' => '',
                            'details_link' => '',
                            'nearby_places' => '',
                            'email' => '',
                            'transportation_routes' => ''
                        ];
                        
                        if (isset($_GET['edit'])) {
                            $edit_id = intval($_GET['edit']);
                            $stmt = $conn->prepare("SELECT * FROM car_dealerships WHERE id=?");
                            $stmt->bind_param("i", $edit_id);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            if ($result->num_rows > 0) {
                                $dealershipData = $result->fetch_assoc();
                            }
                        }
                    ?>
                    <form method="POST" action="add_car_dealerships.php" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($dealershipData['id']); ?>">
                        
                        <div class="form-section">
                            <h5>Basic Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Name *</label>
                                        <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($dealershipData['name']); ?>" >
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Details Link:</label>
                                        <input type="text" class="form-control" name="details_link" value="<?php echo htmlspecialchars($dealershipData['details_link']); ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Description *</label>
                                        <textarea class="form-control" name="description" rows="4"><?php echo htmlspecialchars($dealershipData['description']); ?></textarea>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Operating Hours</label>
                                        <textarea class="form-control" name="hours" rows="5" placeholder="Monday: 9AM - 5PM"><?php echo htmlspecialchars($dealershipData['hours']); ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Contact Information</label>
                                        <input type="text" class="form-control" name="contact" value="<?php echo htmlspecialchars($dealershipData['contact']); ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" name="email" value="<?php echo $dealershipData['email']; ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h5>Car Dealership Specifics</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Brands Available:</label>
                                        <input name="brands_available" class="form-control" value="<?= htmlspecialchars($brands_available_str); ?>">
                                        <small class="text-muted">Example: Toyota, Honda, Ford, BMW</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Services Offered:</label>
                                        <input name="services_offered" class="form-control" value="<?= htmlspecialchars($services_offered_str); ?>">
                                        <small class="text-muted">Example: Sales, Service, Parts, Financing</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h5>Images</h5>
                            <div class="mb-3">
                                <label class="form-label">Upload Card Image:</label>
                                <input type="file" class="form-control" name="image">
                                
                                <?php if (!empty($dealershipData['image'])) { ?>
                                    <div class="mt-2">
                                        <img src="<?php echo htmlspecialchars($dealershipData['image']); ?>" class="img-thumbnail" width="100">
                                        <div class="form-check mt-1">
                                            <input class="form-check-input" type="checkbox" name="delete_image" value="1" id="deleteImageCheck">
                                            <label class="form-check-label" for="deleteImageCheck">Delete current image</label>
                                        </div>
                                    </div>
                                    <input type="hidden" name="existing_image" value="<?php echo htmlspecialchars($dealershipData['image']); ?>">
                                <?php } ?>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Upload Detail Images (2-5):</label>
                                <input type="file" class="form-control" name="image_detail[]" multiple accept="image/*">
                                <?php if (!empty($dealershipData['image_detail'])) {
                                    $images = explode(',', $dealershipData['image_detail']); 
                                    echo '<div class="d-flex flex-wrap mt-2">';
                                    foreach ($images as $img) {
                                        if (!empty($img)) { ?>
                                            <div class="position-relative me-2 mb-2">
                                                <img src="<?php echo htmlspecialchars($img); ?>" class="img-thumbnail" width="100">
                                                <a href="?delete_image=<?php echo urlencode($img); ?>&dealership_id=<?php echo $dealershipData['id']; ?>" 
                                                   class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger text-decoration-none" 
                                                   onclick="return confirm('Are you sure you want to delete this image?')">
                                                   ×
                                                </a>
                                            </div>
                                        <?php }
                                    }
                                    echo '</div>'; ?>
                                    <input type="hidden" name="existing_image_detail" value="<?php echo htmlspecialchars($dealershipData['image_detail']); ?>">
                                <?php } ?>
                            </div>
                        </div>

                        <div class="form-section">
                            <h5>Location Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Address *</label>
                                        <input type="text" class="form-control" name="location" value="<?php echo htmlspecialchars($dealershipData['location']); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Website URL</label>
                                        <input type="url" class="form-control" name="website" value="<?php echo htmlspecialchars($dealershipData['website']); ?>">
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Google Maps Directions Link</label>
                                        <input type="url" class="form-control" name="directions_link" value="<?php echo htmlspecialchars($dealershipData['directions_link']); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Google Map Link</label>
                                        <input type="url" class="form-control" name="maps_link" value="<?php echo htmlspecialchars($dealershipData['maps_link']); ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Embed Google Map URL</label>
                                <input type="url" class="form-control" name="maps_embed" value="<?php echo htmlspecialchars($dealershipData['maps_embed']); ?>">
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
                                                <button type="button" class="btn btn-danger btn-sm" onclick="removeRoute(this)">
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
                                            <button type="button" class="btn btn-danger btn-sm" onclick="removeRoute(this)">
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
                        
                        <div class="form-section">
                            <h5>Amenities & Nearby Places</h5>
                            <div class="mb-3">
                                <label class="form-label">Nearby Places (comma separated)</label>
                                <input type="text" class="form-control" name="nearby_places" value="<?php echo htmlspecialchars($nearby_places_str); ?>">
                                <small class="text-muted">Example: Shopping Mall, Gas Station, Restaurant</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Social Media Links</label>
                                <textarea class="form-control" name="social_media" rows="5" ><?php echo isset($dealershipData['social_media']) ? htmlspecialchars($dealershipData['social_media']) : ''; ?></textarea>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <?php echo isset($_GET['edit']) ? 'Update Dealership' : 'Save Dealership'; ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
    function addTransportRoute() {
        const container = document.getElementById('transport-routes-container');
        const routeCount = container.children.length + 1;
        
        const newRoute = document.createElement('div');
        newRoute.className = 'transport-route-item';
        newRoute.innerHTML = `
            <div class="transport-route-header">
                <h6 class="mb-0">Route #${routeCount}</h6>
                <button type="button" class="btn btn-danger btn-sm" onclick="removeRoute(this)">
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
    }

    function removeRoute(button) {
        if (confirm('Are you sure you want to remove this route?')) {
            const routeItem = button.closest('.transport-route-item');
            const container = document.getElementById('transport-routes-container');
            
            if (container.children.length > 1) {
                routeItem.remove();
                
                const routes = container.children;
                for (let i = 0; i < routes.length; i++) {
                    const header = routes[i].querySelector('h6');
                    if (header) {
                        header.textContent = `Route #${i + 1}`;
                    }
                }
            } else {
                alert('You must have at least one route.');
            }
        }
    }

    <?php if (isset($_GET['edit'])): ?>
    document.addEventListener('DOMContentLoaded', function() {
        var modal = new bootstrap.Modal(document.getElementById('dealershipModal'));
        modal.show();
    });
    <?php endif; ?>

    setTimeout(function() {
        document.querySelectorAll('.toast').forEach(function(toast) {
            toast.classList.remove('show');
        });
    }, 3000);
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>