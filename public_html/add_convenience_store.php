<?php
session_start();
include 'conn.php';

if (isset($_GET['delete_route']) && isset($_GET['store_id'])) {
    $route_index = intval($_GET['delete_route']);
    $store_id = intval($_GET['store_id']);
    
    $stmt = $conn->prepare("SELECT transportation_routes FROM convenience_stores WHERE id=?");
    $stmt->bind_param("i", $store_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $transportation_routes = json_decode($row['transportation_routes'], true);
        
        if (is_array($transportation_routes) && isset($transportation_routes[$route_index])) {
            array_splice($transportation_routes, $route_index, 1);
            $updated_routes_json = json_encode($transportation_routes);
            
            $stmt = $conn->prepare("UPDATE convenience_stores SET transportation_routes=? WHERE id=?");
            $stmt->bind_param("si", $updated_routes_json, $store_id);
            $stmt->execute();
            
            $_SESSION['message'] = "Transportation route deleted successfully";
        }
    }
    
    header("Location: add_convenience_store.php?edit=" . $store_id);
    exit();
}

if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    
    $stmt = $conn->prepare("SELECT image, image_detail FROM convenience_stores WHERE id=?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $store = $result->fetch_assoc();
    
    $stmt = $conn->prepare("DELETE FROM convenience_stores WHERE id=?");
    $stmt->bind_param("i", $delete_id);
    
    if ($stmt->execute()) {
        if ($store) {
            if (!empty($store['image']) && file_exists($store['image'])) {
                unlink($store['image']);
            }
            
            if (!empty($store['image_detail'])) {
                $images = explode(',', $store['image_detail']);
                foreach ($images as $img) {
                    if (!empty($img) && file_exists($img)) {
                        unlink($img);
                    }
                }
            }
        }
        
        $_SESSION['message'] = "Convenience store deleted successfully";
    } else {
        $_SESSION['error'] = "Error deleting convenience store: " . $conn->error;
    }
    
    header("Location: add_convenience_store.php");
    exit();
}

if (isset($_GET['delete_image']) && isset($_GET['store_id'])) {
    $image_path = urldecode($_GET['delete_image']);
    $store_id = intval($_GET['store_id']);
    $is_card_image = isset($_GET['is_card_image']) ? true : false;
    
    if (file_exists($image_path)) {
        unlink($image_path);
    }
    
    if ($is_card_image) {
        $stmt = $conn->prepare("UPDATE convenience_stores SET image='' WHERE id=?");
        $stmt->bind_param("i", $store_id);
        $stmt->execute();
    } else {
        $stmt = $conn->prepare("SELECT image_detail FROM convenience_stores WHERE id=?");
        $stmt->bind_param("i", $store_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $store = $result->fetch_assoc();
        
        if ($store) {
            $images = explode(',', $store['image_detail']);
            $updated_images = array();
            foreach ($images as $img) {
                if (trim($img) != trim($image_path)) {
                    $updated_images[] = $img;
                }
            }
            $updated_images_str = implode(',', $updated_images);
            
            $stmt = $conn->prepare("UPDATE convenience_stores SET image_detail=? WHERE id=?");
            $stmt->bind_param("si", $updated_images_str, $store_id);
            $stmt->execute();
        }
    }
    
    $_SESSION['message'] = "Image deleted successfully";
    header("Location: add_convenience_store.php?edit=" . $store_id);
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

$query = "SELECT id, name, image, description, location, maps_embed, directions_link, maps_link, website, hours, contact, email, nearby_places, social_media, details_link, products, services, branches, transportation_routes FROM convenience_stores";
$stmt = $conn->prepare($query);
$stmt->execute();
$stores = $stmt->get_result();

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
    $mapEmbedURL = htmlspecialchars(trim($_POST['map_embed'] ?? ''));
    $social_media = trim($_POST['social_media']);
    $details_link = trim($_POST['details_link']);
    $services = trim($_POST['services'] ?? '');
    $branches = $_POST['branches'] ?? '';
    $email = trim($_POST['email'] ?? '');

    $products = array();
    if (isset($_POST['product_categories']) && is_array($_POST['product_categories'])) {
        foreach ($_POST['product_categories'] as $index => $category) {
            if (!empty($category) && isset($_POST['product_items'][$index])) {
                $items = array_filter(array_map('trim', explode(',', $_POST['product_items'][$index])));
                if (!empty($items)) {
                    $products[$category] = $items;
                }
            }
        }
    }
    $products_json = json_encode($products);

    $nearby_places = $_POST['nearby_places'] ?? '';
    $nearby_places_array = array_map('trim', explode(',', $nearby_places));
    $nearby_places_json = json_encode(array_filter($nearby_places_array));

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
        $query = "UPDATE convenience_stores SET name=?, image=?, image_detail=?, description=?, location=?, maps_embed=?, maps_link=?, website=?, directions_link=?, hours=?, contact=?, email=?, nearby_places=?, social_media=?, details_link=?, products=?, services=?, branches=?, transportation_routes=? WHERE id=?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssssssssssssssssssi", $name, $target_file, $target_file_detail, $description, $location, $mapEmbedURL, $maps_link, $website, $directions_link, $hours, $contact, $email, $nearby_places_json, $social_media, $details_link, $products_json, $services, $branches, $transportation_routes_json, $id);
    } else {
        $query = "INSERT INTO convenience_stores (name, image, image_detail, description, location, maps_embed, maps_link, website, directions_link, hours, contact, email, nearby_places, social_media, details_link, products, services, branches, transportation_routes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssssssssssssssssss", $name, $target_file, $target_file_detail, $description, $location, $mapEmbedURL, $maps_link, $website, $directions_link, $hours, $contact, $email, $nearby_places_json, $social_media, $details_link, $products_json, $services, $branches, $transportation_routes_json);
    }
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Convenience store " . ($id ? "updated" : "added") . " successfully";
    } else {
        $_SESSION['error'] = "Error saving convenience store: " . $conn->error;
    }
    
    header("Location: add_convenience_store.php" . ($id ? "?edit=" . $id : ""));
    exit();
}

$nearby_places_str = '';
$products_data = array();
$transportation_routes = array();

if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT nearby_places, products, transportation_routes FROM convenience_stores WHERE id=?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (!empty($row['nearby_places'])) {
            $decoded_places = json_decode($row['nearby_places'], true);
            if (is_array($decoded_places)) {
                $nearby_places_str = implode(', ', $decoded_places);
            }
        }
        if (!empty($row['products'])) {
            $products_data = json_decode($row['products'], true);
            if (!is_array($products_data)) {
                $products_data = array();
            }
        }
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
    <title>Convenience Stores Management</title>
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
        .delete-image-btn:hover {
            background: #c82333;
            color: white;
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
        .product-category-row {
            margin-bottom: 15px;
            padding: 15px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
        }
        .transport-table-container {
            overflow-x: auto;
        }
        .route-row {
            vertical-align: middle;
        }
        .action-buttons {
            white-space: nowrap;
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
            <h1 class="text-light">Convenience Stores Management</h1>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#storeModal">
                <i class="bi bi-plus-lg"></i> Add Store
            </button>
        </div>
        
        <?php if ($stores->num_rows == 0): ?>
            <div class="alert alert-info">
                No convenience stores found. Click "Add Store" to create one.
            </div>
        <?php else: ?>
            <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-4">
                <?php while ($store = $stores->fetch_assoc()): ?>
                    <div class="col">
                        <div class="card h-100">
                            <img src="<?php echo htmlspecialchars($store['image']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($store['name']); ?>">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?php echo htmlspecialchars($store['name']); ?></h5>
                                <p class="card-text flex-grow-1"><?php echo substr(htmlspecialchars($store['description']), 0, 100) . '...'; ?></p>

                                <div class="d-flex justify-content-between">
                                    <a href="add_convenience_store.php?edit=<?php echo $store['id']; ?>" class="btn btn-primary btn-sm">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    <a href="?delete=<?php echo $store['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this convenience store?')">
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

    <div class="modal fade <?php echo isset($_GET['edit']) ? 'show d-block' : ''; ?>" id="storeModal" tabindex="-1" aria-labelledby="storeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="storeModalLabel"><?php echo isset($_GET['edit']) ? 'Edit Convenience Store' : 'Add Convenience Store'; ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php 
                        $storeData = [
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
                            'nearby_places' => '',
                            'social_media' => '',
                            'details_link' => '',
                            'products' => '',
                            'services' => '',
                            'branches' => '',
                            'email' => ''
                        ];
                        
                        if (isset($_GET['edit'])) {
                            $edit_id = intval($_GET['edit']);
                            $stmt = $conn->prepare("SELECT * FROM convenience_stores WHERE id=?");
                            $stmt->bind_param("i", $edit_id);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            if ($result->num_rows > 0) {
                                $storeData = $result->fetch_assoc();
                            }
                        }
                    ?>
                    <form method="POST" action="add_convenience_store.php" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($storeData['id']); ?>">
                        
                        <div class="form-section">
                            <h5>Basic Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Name *</label>
                                        <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($storeData['name']); ?>" >
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Details Link:</label>
                                        <input type="text" class="form-control" name="details_link" value="<?php echo htmlspecialchars($storeData['details_link']); ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Description *</label>
                                        <textarea class="form-control" name="description" rows="4"><?php echo htmlspecialchars($storeData['description']); ?></textarea>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Operating Hours</label>
                                        <textarea class="form-control" name="hours" rows="5" placeholder="Monday: 9AM - 5PM"><?php echo htmlspecialchars($storeData['hours']); ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Contact Information</label>
                                        <input type="text" class="form-control mb-2" name="contact" value="<?php echo htmlspecialchars($storeData['contact']); ?>">
                                        <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($storeData['email'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h5>Products & Services</h5>
                            <div class="mb-3">
                                <label class="form-label">Services (comma-separated)</label>
                                <input type="text" class="form-control" name="services" value="<?php echo htmlspecialchars($storeData['services']); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Products (by category)</label>
                                <div id="product-categories">
                                    <?php
                                    if (!empty($products_data) && count($products_data) > 0) {
                                        $index = 0;
                                        foreach ($products_data as $category => $items) {
                                            echo '<div class="product-category-row">';
                                            echo '<div class="row">';
                                            echo '<div class="col-md-5">';
                                            echo '<input type="text" class="form-control" name="product_categories[]" value="' . htmlspecialchars($category) . '">';
                                            echo '</div>';
                                            echo '<div class="col-md-6">';
                                            echo '<input type="text" class="form-control" name="product_items[]" value="' . htmlspecialchars(implode(', ', $items)) . '" >';
                                            echo '</div>';
                                            echo '</div>';
                                            echo '</div>';
                                            $index++;
                                        }
                                    } else {
                                        echo '<div class="product-category-row">';
                                        echo '<div class="row">';
                                        echo '<div class="col-md-5">';
                                        echo '<input type="text" class="form-control" name="product_categories[]">';
                                        echo '</div>';
                                        echo '<div class="col-md-6">';
                                        echo '<input type="text" class="form-control" name="product_items[]">';
                                        echo '</div>';
                                        echo '</div>';
                                        echo '</div>';
                                    }
                                    ?>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Branches (one per line)</label>
                                <textarea class="form-control" name="branches" rows="5"><?php echo htmlspecialchars($storeData['branches']); ?></textarea>
                                <small class="text-muted">Enter each branch on a separate line</small>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h5>Transportation Routes</h5>
                            <div class="transport-table-container">
                                <table class="table table-bordered" id="transportTable">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Transport Type</th>
                                            <th>Route Name/Number</th>
                                            <th>Route Link</th>
                                            <th>Description</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="transportTableBody">
                                        <?php if (!empty($transportation_routes)): ?>
                                            <?php foreach ($transportation_routes as $index => $route): ?>
                                                <tr class="route-row" data-index="<?php echo $index; ?>">
                                                    <td><?php echo $index + 1; ?></td>
                                                    <td>
                                                        <select class="form-control form-control-sm" name="route_type[]">
                                                            <option value="jeepney" <?php echo ($route['type'] == 'jeepney') ? 'selected' : ''; ?>>Jeepney</option>
                                                            <option value="bus" <?php echo ($route['type'] == 'bus') ? 'selected' : ''; ?>>Bus</option>
                                                            <option value="taxi" <?php echo ($route['type'] == 'taxi') ? 'selected' : ''; ?>>Taxi</option>
                                                            <option value="tricycle" <?php echo ($route['type'] == 'tricycle') ? 'selected' : ''; ?>>Tricycle</option>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control form-control-sm" name="route_name[]" value="<?php echo htmlspecialchars($route['name']); ?>">
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control form-control-sm" name="route_link[]" value="<?php echo htmlspecialchars($route['link'] ?? ''); ?>">
                                                    </td>
                                                    <td>
                                                        <textarea class="form-control form-control-sm" name="route_description[]" rows="1"><?php echo htmlspecialchars($route['description'] ?? ''); ?></textarea>
                                                    </td>
                                                    <td class="action-buttons">
                                                        <button type="button" class="btn btn-danger btn-sm" onclick="removeTransportRow(this)">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr class="route-row" data-index="0">
                                                <td>1</td>
                                                <td>
                                                    <select class="form-control form-control-sm" name="route_type[]">
                                                        <option value="jeepney">Jeepney</option>
                                                        <option value="bus">Bus</option>
                                                        <option value="taxi">Taxi</option>
                                                        <option value="tricycle">Tricycle</option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control form-control-sm" name="route_name[]">
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control form-control-sm" name="route_link[]">
                                                </td>
                                                <td>
                                                    <textarea class="form-control form-control-sm" name="route_description[]" rows="1"></textarea>
                                                </td>
                                                <td class="action-buttons">
                                                    <button type="button" class="btn btn-danger btn-sm" onclick="removeTransportRow(this)">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <button type="button" class="btn btn-secondary btn-sm mt-2" onclick="addTransportRow()">
                                <i class="bi bi-plus"></i> Add Another Route
                            </button>
                        </div>
                        
                        <div class="form-section">
                            <h5>Images</h5>
                            <div class="mb-3">
                                <label class="form-label">Upload Card Image:</label>
                                <input type="file" class="form-control" name="image">
                                
                                <?php if (!empty($storeData['image'])) { ?>
                                    <div class="mt-2">
                                        <img src="<?php echo htmlspecialchars($storeData['image']); ?>" class="img-thumbnail" width="100">
                                        <div class="form-check mt-1">
                                            <input class="form-check-input" type="checkbox" name="delete_image" value="1" id="deleteImageCheck">
                                            <label class="form-check-label" for="deleteImageCheck">Delete current image</label>
                                        </div>
                                    </div>
                                    <input type="hidden" name="existing_image" value="<?php echo htmlspecialchars($storeData['image']); ?>">
                                <?php } ?>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Upload Detail Images (2-5):</label>
                                <input type="file" class="form-control" name="image_detail[]" multiple accept="image/*">
                                <?php if (!empty($storeData['image_detail'])) {
                                    $images = explode(',', $storeData['image_detail']); 
                                    echo '<div class="image-preview-container mt-2">';
                                    foreach ($images as $img) {
                                        if (!empty($img)) { ?>
                                            <div class="image-preview">
                                                <img src="<?php echo htmlspecialchars($img); ?>" alt="Detail Image">
                                                <a href="?delete_image=<?php echo urlencode($img); ?>&store_id=<?php echo $storeData['id']; ?>" 
                                                   class="delete-image-btn" 
                                                   onclick="return confirm('Are you sure you want to delete this image?')">
                                                   ×
                                                </a>
                                            </div>
                                        <?php }
                                    }
                                    echo '</div>'; ?>
                                    <input type="hidden" name="existing_image_detail" value="<?php echo htmlspecialchars($storeData['image_detail']); ?>">
                                <?php } ?>
                            </div>
                        </div>

                        <div class="form-section">
                            <h5>Location Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Address *</label>
                                        <input type="text" class="form-control" name="location" value="<?php echo htmlspecialchars($storeData['location']); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Website URL</label>
                                        <input type="url" class="form-control" name="website" value="<?php echo htmlspecialchars($storeData['website']); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Google Maps Directions Link</label>
                                        <input type="url" class="form-control" name="directions_link" value="<?php echo htmlspecialchars($storeData['directions_link']); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Google Map Link</label>
                                        <input type="url" class="form-control" name="maps_link" value="<?php echo htmlspecialchars($storeData['maps_link']); ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Embed Google Map URL</label>
                                <input type="url" class="form-control" name="map_embed" value="<?php echo htmlspecialchars($storeData['maps_embed']); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Nearby Places:</label>
                                <input name="nearby_places" class="form-control" value="<?= htmlspecialchars($nearby_places_str); ?>">
                                <small class="text-muted">Example: Museum, Park, Shopping Mall</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Social Media Links</label>
                                <textarea class="form-control" name="social_media" rows="5"><?php echo isset($storeData['social_media']) ? htmlspecialchars($storeData['social_media']) : ''; ?></textarea>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <?php echo isset($_GET['edit']) ? 'Update Store' : 'Save Store'; ?>
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
                var modal = new bootstrap.Modal(document.getElementById('storeModal'));
                modal.show();
                
                history.replaceState(null, null, window.location.pathname);
            }
        });

        function updateTransportNumbers() {
            const rows = document.querySelectorAll('#transportTableBody .route-row');
            rows.forEach((row, index) => {
                const rowNumber = row.querySelector('td:first-child');
                rowNumber.textContent = index + 1;
            });
        }

        function addTransportRow() {
            const tbody = document.getElementById('transportTableBody');
            const rows = tbody.querySelectorAll('.route-row');
            const newIndex = rows.length;
            
            const newRow = document.createElement('tr');
            newRow.className = 'route-row';
            newRow.dataset.index = newIndex;
            
            newRow.innerHTML = `
                <td>${newIndex + 1}</td>
                <td>
                    <select class="form-control form-control-sm" name="route_type[]">
                        <option value="jeepney">Jeepney</option>
                        <option value="bus">Bus</option>
                        <option value="taxi">Taxi</option>
                        <option value="tricycle">Tricycle</option>
                    </select>
                </td>
                <td>
                    <input type="text" class="form-control form-control-sm" name="route_name[]">
                </td>
                <td>
                    <input type="text" class="form-control form-control-sm" name="route_link[]">
                </td>
                <td>
                    <textarea class="form-control form-control-sm" name="route_description[]" rows="1"></textarea>
                </td>
                <td class="action-buttons">
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeTransportRow(this)">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            `;
            
            tbody.appendChild(newRow);
            updateTransportNumbers();
        }

        function removeTransportRow(button) {
            const row = button.closest('.route-row');
            row.remove();
            updateTransportNumbers();
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>