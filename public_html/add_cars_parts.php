<?php
session_start();
include 'conn.php';

if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    
    $stmt = $conn->prepare("SELECT image, image_detail FROM car_parts WHERE id=?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $car_part = $result->fetch_assoc();
    
    $stmt = $conn->prepare("DELETE FROM car_parts WHERE id=?");
    $stmt->bind_param("i", $delete_id);
    
    if ($stmt->execute()) {
        if ($car_part) {
            if (!empty($car_part['image']) && file_exists($car_part['image'])) {
                unlink($car_part['image']);
            }
            
            if (!empty($car_part['image_detail'])) {
                $images = explode(',', $car_part['image_detail']);
                foreach ($images as $img) {
                    if (!empty($img) && file_exists($img)) {
                        unlink($img);
                    }
                }
            }
        }
        
        $_SESSION['message'] = "Car part deleted successfully";
    } else {
        $_SESSION['error'] = "Error deleting car part: " . $conn->error;
    }
    
    header("Location: add_cars_parts.php");
    exit();
}

if (isset($_GET['delete_image']) && isset($_GET['car_part_id'])) {
    $image_path = urldecode($_GET['delete_image']);
    $car_part_id = intval($_GET['car_part_id']);
    $is_card_image = isset($_GET['is_card_image']) ? true : false;
    
    if (file_exists($image_path)) {
        unlink($image_path);
    }
    
    if ($is_card_image) {
        $stmt = $conn->prepare("UPDATE car_parts SET image='' WHERE id=?");
        $stmt->bind_param("i", $car_part_id);
        $stmt->execute();
    } else {
        $stmt = $conn->prepare("SELECT image_detail FROM car_parts WHERE id=?");
        $stmt->bind_param("i", $car_part_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $car_part = $result->fetch_assoc();
        
        if ($car_part) {
            $images = explode(',', $car_part['image_detail']);
            $updated_images = array();
            foreach ($images as $img) {
                if (trim($img) != trim($image_path)) {
                    $updated_images[] = $img;
                }
            }
            $updated_images_str = implode(',', $updated_images);
            
            $stmt = $conn->prepare("UPDATE car_parts SET image_detail=? WHERE id=?");
            $stmt->bind_param("si", $updated_images_str, $car_part_id);
            $stmt->execute();
        }
    }
    
    $_SESSION['message'] = "Image deleted successfully";
    header("Location: add_cars_parts.php?edit=" . $car_part_id);
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

$query = "SELECT id, name, image, description, category, brand, part_number, price, availability, warranty, specifications, compatible_cars, supplier, location, maps_embed, directions_link, maps_link, website, hours, contact, social_media, details_link, nearby_places, email, transportation_routes FROM car_parts";
$stmt = $conn->prepare($query);
$stmt->execute();
$car_parts = $stmt->get_result();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : null;
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $category = trim($_POST['category']);
    $brand = trim($_POST['brand']);
    $part_number = trim($_POST['part_number']);
    $price = floatval($_POST['price']);
    $availability = trim($_POST['availability']);
    $warranty = trim($_POST['warranty']);
    $supplier = trim($_POST['supplier']);
    $location = trim($_POST['location']);
    $website = trim($_POST['website']);
    $maps_link = trim($_POST['maps_link']);
    $directions_link = trim($_POST['directions_link']);
    $mapEmbedURL = htmlspecialchars(trim($_POST['map_embed'] ?? ''));
    $hours = trim($_POST['hours'] ?? '');
    $contact = trim($_POST['contact'] ?? '');
    $social_media = trim($_POST['social_media']);
    $details_link = trim($_POST['details_link']);
    $email = trim($_POST['email'] ?? '');

    $nearby_places = $_POST['nearby_places'] ?? '';
    $nearby_places_array = array_map('trim', explode(',', $nearby_places));
    $nearby_places_json = json_encode(array_filter($nearby_places_array)); 
 
    $specifications = [];
    if (!empty($_POST['spec_key']) && !empty($_POST['spec_value'])) {
        $spec_keys = $_POST['spec_key'];
        $spec_values = $_POST['spec_value'];
        
        for ($i = 0; $i < count($spec_keys); $i++) {
            if (!empty($spec_keys[$i]) && !empty($spec_values[$i])) {
                $specifications[trim($spec_keys[$i])] = trim($spec_values[$i]);
            }
        }
    }
    $specifications_json = json_encode($specifications);

    $compatible_cars = $_POST['compatible_cars'] ?? '';
    $compatible_cars_array = array_map('trim', explode(',', $compatible_cars));
    $compatible_cars_json = json_encode(array_filter($compatible_cars_array));

    $transportation_routes = array();
    
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
        $query = "UPDATE car_parts SET name=?, image=?, image_detail=?, description=?, category=?, brand=?, part_number=?, price=?, availability=?, warranty=?, specifications=?, compatible_cars=?, supplier=?, location=?, maps_embed=?, maps_link=?, website=?, directions_link=?, hours=?, contact=?, social_media=?, details_link=?, nearby_places=?, email=?, transportation_routes=? WHERE id=?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssssssssssssssssssssssssi", $name, $target_file, $target_file_detail, $description, $category, $brand, $part_number, $price, $availability, $warranty, $specifications_json, $compatible_cars_json, $supplier, $location, $mapEmbedURL, $maps_link, $website, $directions_link, $hours, $contact, $social_media, $details_link, $nearby_places_json, $email, $transportation_routes_json, $id);
    } else {
        $query = "INSERT INTO car_parts (name, image, image_detail, description, category, brand, part_number, price, availability, warranty, specifications, compatible_cars, supplier, location, maps_embed, maps_link, website, directions_link, hours, contact, social_media, details_link, nearby_places, email, transportation_routes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssssssssssssssssssssssss", $name, $target_file, $target_file_detail, $description, $category, $brand, $part_number, $price, $availability, $warranty, $specifications_json, $compatible_cars_json, $supplier, $location, $mapEmbedURL, $maps_link, $website, $directions_link, $hours, $contact, $social_media, $details_link, $nearby_places_json, $email, $transportation_routes_json);
    }
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Car part " . ($id ? "updated" : "added") . " successfully";
    } else {
        $_SESSION['error'] = "Error saving car part: " . $conn->error;
    }
    
    header("Location: add_cars_parts.php");
    exit();
}

$compatible_cars_str = '';
$specifications_arr = [];
$nearby_places_str = '';
$transportation_routes = array();

if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM car_parts WHERE id=?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $carPartData = $result->fetch_assoc();
        
        if (!empty($carPartData['compatible_cars'])) {
            $decoded_cars = json_decode($carPartData['compatible_cars'], true);
            if (is_array($decoded_cars)) {
                $compatible_cars_str = implode(', ', $decoded_cars);
            }
        }
        if (!empty($carPartData['specifications'])) {
            $specifications_arr = json_decode($carPartData['specifications'], true);
            if (!is_array($specifications_arr)) {
                $specifications_arr = [];
            }
        }
        if (!empty($carPartData['nearby_places'])) {
            $decoded_places = json_decode($carPartData['nearby_places'], true);
            if (is_array($decoded_places)) {
                $nearby_places_str = implode(', ', $decoded_places);
            }
        }
        if (!empty($carPartData['transportation_routes'])) {
            $decoded_routes = json_decode($carPartData['transportation_routes'], true);
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
    <title>Car Parts Management</title>
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
        .spec-row {
            margin-bottom: 10px;
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
        .modal.show.d-block {
            background-color: rgba(0,0,0,0.5);
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
            <h1 class="text-light">Car Parts Management</h1>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#carPartModal">
                <i class="bi bi-plus-lg"></i> Add Car Part
            </button>
        </div>
        
        <?php if ($car_parts->num_rows == 0): ?>
            <div class="alert alert-info">
                No car parts found. Click "Add Car Part" to create one.
            </div>
        <?php else: ?>
            <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-4">
                <?php while ($car_part = $car_parts->fetch_assoc()): ?>
                    <div class="col">
                        <div class="card h-100">
                            <img src="<?php echo htmlspecialchars($car_part['image']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($car_part['name']); ?>">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?php echo htmlspecialchars($car_part['name']); ?></h5>
                                <p class="card-text flex-grow-1"><?php echo substr(htmlspecialchars($car_part['description']), 0, 100) . '...'; ?></p>
                                <div class="d-flex justify-content-between">
                                    <a href="add_cars_parts.php?edit=<?php echo $car_part['id']; ?>" class="btn btn-primary btn-sm">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    <a href="?delete=<?php echo $car_part['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this car part?')">
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

    <?php if (isset($_GET['edit'])): ?>
    <div class="modal fade show d-block" id="carPartModal" tabindex="-1" aria-labelledby="carPartModalLabel" aria-hidden="true" style="display: block;">
    <?php else: ?>
    <div class="modal fade" id="carPartModal" tabindex="-1" aria-labelledby="carPartModalLabel" aria-hidden="true">
    <?php endif; ?>
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="carPartModalLabel"><?php echo isset($_GET['edit']) ? 'Edit Car Part' : 'Add Car Part'; ?></h5>
                    <?php if (isset($_GET['edit'])): ?>
                        <a href="add_cars_parts.php" class="btn-close" aria-label="Close"></a>
                    <?php else: ?>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    <?php endif; ?>
                </div>
                <div class="modal-body">
                    <?php 
                        if (!isset($carPartData)) {
                            $carPartData = [
                                'id' => '',
                                'name' => '',
                                'description' => '',
                                'category' => '',
                                'brand' => '',
                                'part_number' => '',
                                'price' => '',
                                'availability' => 'In Stock',
                                'warranty' => '',
                                'supplier' => '',
                                'location' => '',
                                'website' => '',
                                'image' => '',
                                'image_detail' => '',
                                'maps_embed' => '',
                                'maps_link' => '',
                                'directions_link' => '',
                                'hours' => '',
                                'contact' => '',
                                'social_media' => '',
                                'details_link' => '',
                                'nearby_places' => '',
                                'email' => '',
                                'transportation_routes' => ''
                            ];
                        }
                    ?>
                    <form method="POST" action="add_cars_parts.php" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($carPartData['id']); ?>">
                        
                        <div class="form-section">
                            <h5>Basic Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Name *</label>
                                        <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($carPartData['name']); ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Category *</label>
                                        <input type="text" class="form-control" name="category" value="<?php echo htmlspecialchars($carPartData['category']); ?>" >
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Brand *</label>
                                        <input type="text" class="form-control" name="brand" value="<?php echo htmlspecialchars($carPartData['brand']); ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Part Number</label>
                                        <input type="text" class="form-control" name="part_number" value="<?php echo htmlspecialchars($carPartData['part_number']); ?>">
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Price (₱) *</label>
                                        <input type="number" step="0.01" class="form-control" name="price" value="<?php echo htmlspecialchars($carPartData['price']); ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Availability *</label>
                                        <select class="form-control" name="availability">
                                            <option value="In Stock" <?php echo ($carPartData['availability'] == 'In Stock') ? 'selected' : ''; ?>>In Stock</option>
                                            <option value="Out of Stock" <?php echo ($carPartData['availability'] == 'Out of Stock') ? 'selected' : ''; ?>>Out of Stock</option>
                                            <option value="Limited Stock" <?php echo ($carPartData['availability'] == 'Limited Stock') ? 'selected' : ''; ?>>Limited Stock</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Warranty</label>
                                        <input type="text" class="form-control" name="warranty" value="<?php echo htmlspecialchars($carPartData['warranty']); ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Details Link</label>
                                        <input type="text" class="form-control" name="details_link" value="<?php echo htmlspecialchars($carPartData['details_link']); ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Description *</label>
                                <textarea class="form-control" name="description" rows="4"><?php echo htmlspecialchars($carPartData['description']); ?></textarea>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h5>Specifications</h5>
                            <div id="specifications-container">
                                <?php if (!empty($specifications_arr)): ?>
                                    <?php foreach ($specifications_arr as $key => $value): ?>
                                        <div class="row spec-row">
                                            <div class="col-md-5">
                                                <input type="text" class="form-control" name="spec_key[]" value="<?php echo htmlspecialchars($key); ?>">
                                            </div>
                                            <div class="col-md-5">
                                                <input type="text" class="form-control" name="spec_value[]" value="<?php echo htmlspecialchars($value); ?>">
                                            </div>
                                            <div class="col-md-2">
                                                <button type="button" class="btn btn-danger remove-spec">Remove</button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="row spec-row">
                                        <div class="col-md-5">
                                            <input type="text" class="form-control" name="spec_key[]">
                                        </div>
                                        <div class="col-md-5">
                                            <input type="text" class="form-control" name="spec_value[]">
                                        </div>
                                        <div class="col-md-2">
                                            <button type="button" class="btn btn-danger remove-spec">Remove</button>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <button type="button" class="btn btn-secondary mt-2" id="add-spec">Add Specification</button>
                        </div>
                        
                        <div class="form-section">
                            <h5>Compatible Cars</h5>
                            <div class="mb-3">
                                <label class="form-label">Compatible Car Models:</label>
                                <input name="compatible_cars" class="form-control" value="<?= htmlspecialchars($compatible_cars_str); ?>">
                                <small class="text-muted">Example: Toyota Vios, Honda Civic, Mitsubishi Mirage</small>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h5>Images</h5>
                            <div class="mb-3">
                                <label class="form-label">Upload Card Image:</label>
                                <input type="file" class="form-control" name="image">
                                
                                <?php if (!empty($carPartData['image'])) { ?>
                                    <div class="mt-2">
                                        <img src="<?php echo htmlspecialchars($carPartData['image']); ?>" class="img-thumbnail" width="100">
                                        <div class="form-check mt-1">
                                            <input class="form-check-input" type="checkbox" name="delete_image" value="1" id="deleteImageCheck">
                                            <label class="form-check-label" for="deleteImageCheck">Delete current image</label>
                                        </div>
                                    </div>
                                    <input type="hidden" name="existing_image" value="<?php echo htmlspecialchars($carPartData['image']); ?>">
                                <?php } ?>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Upload Detail Images (2-5):</label>
                                <input type="file" class="form-control" name="image_detail[]" multiple accept="image/*">
                                <?php if (!empty($carPartData['image_detail'])) {
                                    $images = explode(',', $carPartData['image_detail']); 
                                    echo '<div class="d-flex flex-wrap mt-2">';
                                    foreach ($images as $img) {
                                        if (!empty($img)) { ?>
                                            <div class="position-relative me-2 mb-2">
                                                <img src="<?php echo htmlspecialchars($img); ?>" class="img-thumbnail" width="100">
                                                <a href="?delete_image=<?php echo urlencode($img); ?>&car_part_id=<?php echo $carPartData['id']; ?>" 
                                                   class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger text-decoration-none" 
                                                   onclick="return confirm('Are you sure you want to delete this image?')">
                                                   ×
                                                </a>
                                            </div>
                                        <?php }
                                    }
                                    echo '</div>'; ?>
                                    <input type="hidden" name="existing_image_detail" value="<?php echo htmlspecialchars($carPartData['image_detail']); ?>">
                                <?php } ?>
                            </div>
                        </div>

                        <div class="form-section">
                            <h5>Supplier Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Supplier Name *</label>
                                        <input type="text" class="form-control" name="supplier" value="<?php echo htmlspecialchars($carPartData['supplier']); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Address *</label>
                                        <input type="text" class="form-control" name="location" value="<?php echo htmlspecialchars($carPartData['location']); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Website URL</label>
                                        <input type="url" class="form-control" name="website" value="<?php echo htmlspecialchars($carPartData['website']); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Operating Hours</label>
                                        <textarea class="form-control" name="hours" rows="5" placeholder="Monday: 9AM - 5PM"><?php echo htmlspecialchars($carPartData['hours']); ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Contact Information</label>
                                        <input type="text" class="form-control" name="contact" value="<?php echo htmlspecialchars($carPartData['contact']); ?>">
                                    </div>
                                     <div class="mb-3">
                                        <label class="form-label">Email Address</label>
                                        <input type="email" class="form-control" name="email" value="<?php echo $carPartData['email']; ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h4>Transportation Routes</h4>
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
                                                    <input type="text" class="form-control" name="route_name[]" value="<?php echo isset($transportation_routes[$i]['name']) ? htmlspecialchars($transportation_routes[$i]['name']) : ''; ?>">
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control" name="route_link[]" value="<?php echo isset($transportation_routes[$i]['link']) ? htmlspecialchars($transportation_routes[$i]['link']) : ''; ?>">
                                                </td>
                                                <td>
                                                    <textarea class="form-control" name="route_description[]" rows="2"><?php echo isset($transportation_routes[$i]['description']) ? htmlspecialchars($transportation_routes[$i]['description']) : ''; ?></textarea>
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
                            <h5>Location & Social Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Google Maps Directions Link</label>
                                        <input type="url" class="form-control" name="directions_link" value="<?php echo htmlspecialchars($carPartData['directions_link']); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Google Map Link</label>
                                        <input type="url" class="form-control" name="maps_link" value="<?php echo htmlspecialchars($carPartData['maps_link']); ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Embed Google Map URL</label>
                                <input type="url" class="form-control" name="map_embed" value="<?php echo htmlspecialchars($carPartData['maps_embed']); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Social Media Links</label>
                                <textarea class="form-control" name="social_media" rows="5"><?php echo isset($carPartData['social_media']) ? htmlspecialchars($carPartData['social_media']) : ''; ?></textarea>
                            </div>
                             <div class="mb-3">
                                <label class="form-label">Nearby Places:</label>
                                <input name="nearby_places" class="form-control" value="<?= htmlspecialchars($nearby_places_str); ?>">
                                <small class="text-muted">Example: Park, Shopping Mall, Bike Trail</small>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-end gap-2">
                            <?php if (isset($_GET['edit'])): ?>
                                <a href="add_cars_parts.php" class="btn btn-secondary">Cancel</a>
                            <?php else: ?>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <?php endif; ?>
                            <button type="submit" class="btn btn-primary">
                                <?php echo isset($_GET['edit']) ? 'Update Car Part' : 'Save Car Part'; ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('add-spec')?.addEventListener('click', function() {
            const container = document.getElementById('specifications-container');
            const newRow = document.createElement('div');
            newRow.className = 'row spec-row';
            newRow.innerHTML = `
                <div class="col-md-5">
                    <input type="text" class="form-control" name="spec_key[]">
                </div>
                <div class="col-md-5">
                    <input type="text" class="form-control" name="spec_value[]">
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-danger remove-spec">Remove</button>
                </div>
            `;
            container.appendChild(newRow);
            
            newRow.querySelector('.remove-spec').addEventListener('click', function() {
                newRow.remove();
            });
        });
        
        document.querySelectorAll('.remove-spec').forEach(button => {
            button.addEventListener('click', function() {
                this.closest('.spec-row').remove();
            });
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
                    <input type="text" class="form-control" name="route_name[]">
                </td>
                <td>
                    <input type="text" class="form-control" name="route_link[]">
                </td>
                <td>
                    <textarea class="form-control" name="route_description[]" rows="2"></textarea>
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
            } else {
                alert("You need at least one transportation route. Add another row before removing this one.");
            }
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            const toasts = document.querySelectorAll('.toast');
            toasts.forEach(toast => {
                setTimeout(() => {
                    toast.classList.remove('show');
                }, 5000);
            });
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>