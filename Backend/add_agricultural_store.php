<?php
session_start();
include 'conn.php';

if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    
    $stmt = $conn->prepare("SELECT image, image_detail FROM agricultural_stores WHERE id=?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $store = $result->fetch_assoc();
    
    $stmt = $conn->prepare("DELETE FROM agricultural_stores WHERE id=?");
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
        
        $_SESSION['message'] = "Agricultural store deleted successfully";
    } else {
        $_SESSION['error'] = "Error deleting store: " . $conn->error;
    }
    
    header("Location: add_agricultural_store.php");
    exit();
}

if (isset($_GET['delete_image']) && isset($_GET['store_id'])) {
    $image_path = urldecode($_GET['delete_image']);
    $store_id = $_GET['store_id'];
    $is_card_image = isset($_GET['is_card_image']) ? true : false;
    
    if (file_exists($image_path)) {
        unlink($image_path);
    }
    
    if ($is_card_image) {
        $stmt = $conn->prepare("UPDATE agricultural_stores SET image='' WHERE id=?");
        $stmt->bind_param("i", $store_id);
        $stmt->execute();
    } else {
        $stmt = $conn->prepare("SELECT image_detail FROM agricultural_stores WHERE id=?");
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
            
            $stmt = $conn->prepare("UPDATE agricultural_stores SET image_detail=? WHERE id=?");
            $stmt->bind_param("si", $updated_images_str, $store_id);
            $stmt->execute();
        }
    }
    
    $_SESSION['message'] = "Image deleted successfully";
    header("Location: add_agricultural_store.php?edit=" . $store_id);
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

$query = "SELECT id, name, image, description, location, maps_embed, directions_link, maps_link, website, hours, contact, nearby_places, social_media, details_link, product_categories, services_offered, established_year, email, branches, transportation_routes FROM agricultural_stores";
$stmt = $conn->prepare($query);
$stmt->execute();
$stores = $stmt->get_result();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = isset($_POST['id']) ? $_POST['id'] : null;
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $hours = trim($_POST['hours'] ?? '');
    $contact = trim($_POST['contact'] ?? '');
    $location = trim($_POST['location']);
    $website = trim($_POST['website']);
    $maps_link = trim($_POST['maps_link']);
    $directions_link = trim($_POST['directions_link']);
    $mapEmbedURL = trim($_POST['map_embed'] ?? '');
    $social_media = trim($_POST['social_media']);
    $details_link = trim($_POST['details_link']);
    $established_year = trim($_POST['established_year'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $branches = trim($_POST['branches'] ?? '');

    $product_categories = $_POST['product_categories'] ?? '';
    $product_categories_array = array_map('trim', explode(',', $product_categories));
    $product_categories_json = json_encode(array_filter($product_categories_array));

    $services_offered = $_POST['services_offered'] ?? '';
    $services_offered_array = array_map('trim', explode(',', $services_offered));
    $services_offered_json = json_encode(array_filter($services_offered_array));

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
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
    $target_file = $_POST['existing_image'] ?? '';

    if (isset($_POST['delete_image']) && $_POST['delete_image'] == '1') {
        if (!empty($target_file) && file_exists($target_file)) {
            unlink($target_file);
        }
        $target_file = '';
    }

    if (!empty($_FILES['image']['name'])) {
        $imageFileType = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
        $uniqueName = uniqid('store_', true) . '.' . $imageFileType;
        $target_file = $target_dir . $uniqueName;

        if (in_array($imageFileType, $allowed_types)) {
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                if (!empty($_POST['existing_image']) && file_exists($_POST['existing_image'])) {
                    unlink($_POST['existing_image']);
                }
            }
        }
    }

    $imagePaths = [];
    $existingImages = [];
    
    if (!empty($_POST['existing_image_detail'])) {
        $existingImages = explode(',', $_POST['existing_image_detail']);
        $existingImages = array_filter($existingImages);
    }
    
    $imagesToDelete = isset($_POST['delete_detail_images']) ? $_POST['delete_detail_images'] : [];
    
    $keptImages = [];
    foreach ($existingImages as $img) {
        if (!in_array($img, $imagesToDelete)) {
            $keptImages[] = $img;
        } else {
            if (file_exists($img)) {
                unlink($img);
            }
        }
    }
    
    if (!empty($_FILES['image_detail']['name'][0])) {
        $totalNew = count($_FILES['image_detail']['name']);
        $totalCombined = count($keptImages) + $totalNew;
    
        if ($totalCombined > 10) {
            $_SESSION['error'] = "Error: You can upload a maximum of 10 detail images total.";
            header("Location: add_agricultural_store.php" . ($id ? "?edit=" . $id : ""));
            exit();
        }
    
        for ($i = 0; $i < $totalNew; $i++) {
            if ($_FILES['image_detail']['error'][$i] !== UPLOAD_ERR_OK) {
                continue;
            }
            
            $fileName = $_FILES['image_detail']['name'][$i];
            $tmpName = $_FILES['image_detail']['tmp_name'][$i];
            $imageFileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
            if (in_array($imageFileType, $allowed_types)) {
                $uniqueName = uniqid('detail_', true) . '.' . $imageFileType;
                $targetPath = $target_dir . $uniqueName;
    
                if (move_uploaded_file($tmpName, $targetPath)) {
                    $imagePaths[] = $targetPath;
                }
            }
        }
    }
    
    $allImages = array_merge($keptImages, $imagePaths);
    $target_file_detail = implode(',', $allImages);
       
    if ($id) {
        $query = "UPDATE agricultural_stores SET name=?, image=?, image_detail=?, description=?, location=?, maps_embed=?, maps_link=?, website=?, directions_link=?, hours=?, contact=?, nearby_places=?, social_media=?, details_link=?, product_categories=?, services_offered=?, established_year=?, email=?, branches=?, transportation_routes=? WHERE id=?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssssssssssssssssssi", $name, $target_file, $target_file_detail, $description, $location, $mapEmbedURL, $maps_link, $website, $directions_link, $hours, $contact, $nearby_places_json, $social_media, $details_link, $product_categories_json, $services_offered_json, $established_year, $email, $branches, $transportation_routes_json, $id);
    } else {
        $query = "INSERT INTO agricultural_stores (name, image, image_detail, description, location, maps_embed, maps_link, website, directions_link, hours, contact, nearby_places, social_media, details_link, product_categories, services_offered, established_year, email, branches, transportation_routes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssssssssssssssssss", $name, $target_file, $target_file_detail, $description, $location, $mapEmbedURL, $maps_link, $website, $directions_link, $hours, $contact, $nearby_places_json, $social_media, $details_link, $product_categories_json, $services_offered_json, $established_year, $email, $branches, $transportation_routes_json);
    }
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Agricultural store " . ($id ? "updated" : "added") . " successfully";
    } else {
        $_SESSION['error'] = "Error saving store: " . $conn->error;
    }
    
    header("Location: add_agricultural_store.php");
    exit();
}

$nearby_places_str = '';
$product_categories_str = '';
$services_offered_str = '';
$transportation_routes = array();
$storeData = null;

if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM agricultural_stores WHERE id=?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $storeData = $result->fetch_assoc();
        
        if (!empty($storeData['nearby_places'])) {
            $decoded_places = json_decode($storeData['nearby_places'], true);
            if (is_array($decoded_places)) {
                $nearby_places_str = implode(', ', $decoded_places);
            }
        }
        if (!empty($storeData['product_categories'])) {
            $decoded_categories = json_decode($storeData['product_categories'], true);
            if (is_array($decoded_categories)) {
                $product_categories_str = implode(', ', $decoded_categories);
            }
        }
        if (!empty($storeData['services_offered'])) {
            $decoded_services = json_decode($storeData['services_offered'], true);
            if (is_array($decoded_services)) {
                $services_offered_str = implode(', ', $decoded_services);
            }
        }
        if (!empty($storeData['transportation_routes'])) {
            $decoded_routes = json_decode($storeData['transportation_routes'], true);
            if (is_array($decoded_routes)) {
                $transportation_routes = $decoded_routes;
            }
        }
    }
}

if (isset($_POST['action']) && $_POST['action'] == 'add_route' && isset($_POST['store_id'])) {
    $store_id = $_POST['store_id'];
    
    $stmt = $conn->prepare("SELECT transportation_routes FROM agricultural_stores WHERE id=?");
    $stmt->bind_param("i", $store_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $transportation_routes = [];
        
        if (!empty($row['transportation_routes'])) {
            $transportation_routes = json_decode($row['transportation_routes'], true);
            if (!is_array($transportation_routes)) {
                $transportation_routes = [];
            }
        }
        
        $transportation_routes[] = array(
            'type' => 'jeepney',
            'name' => '',
            'link' => '',
            'description' => ''
        );
        
        $transportation_routes_json = json_encode($transportation_routes);
        
        $stmt = $conn->prepare("UPDATE agricultural_stores SET transportation_routes=? WHERE id=?");
        $stmt->bind_param("si", $transportation_routes_json, $store_id);
        $stmt->execute();
        
        echo json_encode(['success' => true, 'index' => count($transportation_routes) - 1]);
        exit();
    }
}

if (isset($_POST['action']) && $_POST['action'] == 'remove_route' && isset($_POST['store_id']) && isset($_POST['route_index'])) {
    $store_id = $_POST['store_id'];
    $route_index = $_POST['route_index'];
    
    $stmt = $conn->prepare("SELECT transportation_routes FROM agricultural_stores WHERE id=?");
    $stmt->bind_param("i", $store_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $transportation_routes = [];
        
        if (!empty($row['transportation_routes'])) {
            $transportation_routes = json_decode($row['transportation_routes'], true);
            if (is_array($transportation_routes)) {
                if (isset($transportation_routes[$route_index])) {
                    unset($transportation_routes[$route_index]);
                    $transportation_routes = array_values($transportation_routes);
                    
                    $transportation_routes_json = json_encode($transportation_routes);
                    
                    $stmt = $conn->prepare("UPDATE agricultural_stores SET transportation_routes=? WHERE id=?");
                    $stmt->bind_param("si", $transportation_routes_json, $store_id);
                    $stmt->execute();
                    
                    echo json_encode(['success' => true]);
                    exit();
                }
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
    <title>Agricultural Stores Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    <style>
        body {
            background: url('img/bg.png') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            padding-bottom: 30px;
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
        .image-checkbox-container {
            position: absolute;
            top: 5px;
            left: 5px;
            background: white;
            padding: 2px 5px;
            border-radius: 3px;
            z-index: 1;
        }
        .modal-xl {
            max-width: 90%;
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
            <h1 class="text-light">Agricultural Stores Management</h1>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#storeModal">
                <i class="bi bi-plus-lg"></i> Add Store
            </button>
        </div>
        
        <?php if ($stores->num_rows == 0): ?>
            <div class="alert alert-info">
                No agricultural stores found. Click "Add Store" to create one.
            </div>
        <?php else: ?>
            <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-4">
                <?php while ($store = $stores->fetch_assoc()): ?>
                    <div class="col">
                        <div class="card h-100">
                            <?php if (!empty($store['image'])): ?>
                                <img src="<?php echo $store['image']; ?>" class="card-img-top" alt="<?php echo $store['name']; ?>">
                            <?php else: ?>
                                <div class="card-img-top bg-secondary d-flex align-items-center justify-content-center text-white">
                                    <span>No Image</span>
                                </div>
                            <?php endif; ?>
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?php echo $store['name']; ?></h5>
                                <p class="card-text flex-grow-1"><?php echo substr($store['description'], 0, 100) . '...'; ?></p>
                                <div class="d-flex justify-content-between">
                                    <a href="add_agricultural_store.php?edit=<?php echo $store['id']; ?>" class="btn btn-primary btn-sm">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    <a href="?delete=<?php echo $store['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this agricultural store?')">
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
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="storeModalLabel"><?php echo isset($_GET['edit']) ? 'Edit Agricultural Store' : 'Add Agricultural Store'; ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php 
                        if (!isset($storeData) || !$storeData) {
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
                                'product_categories' => '',
                                'services_offered' => '',
                                'established_year' => '',
                                'email' => '',
                                'branches' => '',
                                'transportation_routes' => ''
                            ];
                        }
                    ?>
                    <form method="POST" action="add_agricultural_store.php" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="<?php echo $storeData['id']; ?>">
                        
                        <div class="form-section">
                            <h5 class="mb-4">Basic Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Store Name *</label>
                                        <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($storeData['name']); ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Established Year</label>
                                        <input type="number" class="form-control" name="established_year" value="<?php echo htmlspecialchars($storeData['established_year']); ?>" min="1900" max="2030">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Email Address</label>
                                        <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($storeData['email']); ?>">
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Description *</label>
                                        <textarea class="form-control" name="description" rows="4" required><?php echo htmlspecialchars($storeData['description']); ?></textarea>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Details Link</label>
                                <input type="text" class="form-control" name="details_link" value="<?php echo htmlspecialchars($storeData['details_link']); ?>">
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h5 class="mb-4">Agricultural Products & Services</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Product Categories</label>
                                        <input type="text" class="form-control" name="product_categories" value="<?php echo htmlspecialchars($product_categories_str); ?>">
                                        <small class="text-muted">Separate categories with commas</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Services Offered</label>
                                        <input type="text" class="form-control" name="services_offered" value="<?php echo htmlspecialchars($services_offered_str); ?>">
                                        <small class="text-muted">Separate services with commas</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <h5 class="mb-4">Branches Information</h5>
                            <div class="mb-3">
                                <label class="form-label">Branches (one per line)</label>
                                <textarea class="form-control" name="branches" rows="5"><?php echo htmlspecialchars($storeData['branches']); ?></textarea>
                                <small class="text-muted">Enter each branch on a separate line</small>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h5 class="mb-4">Business Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Operating Hours</label>
                                        <textarea class="form-control" name="hours" rows="5" placeholder="Monday: 8AM - 6PM"><?php echo htmlspecialchars($storeData['hours']); ?></textarea>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Contact Information</label>
                                        <input type="text" class="form-control" name="contact" value="<?php echo htmlspecialchars($storeData['contact']); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Social Media Links</label>
                                        <textarea class="form-control" name="social_media" rows="5"><?php echo htmlspecialchars($storeData['social_media'] ?? ''); ?></textarea>
                                        <small class="text-muted">Enter one social media URL per line</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h5 class="mb-4">Images</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Upload Store Image:</label>
                                        <input type="file" class="form-control" name="image" accept="image/*">
                                        
                                        <?php if (!empty($storeData['image'])) { ?>
                                            <div class="mt-3">
                                                <p class="mb-1"><strong>Current Image:</strong></p>
                                                <img src="<?php echo $storeData['image']; ?>" class="img-thumbnail card-image-preview">
                                                <div class="form-check mt-2">
                                                    <input class="form-check-input" type="checkbox" name="delete_image" value="1" id="deleteImageCheck">
                                                    <label class="form-check-label" for="deleteImageCheck">Delete current image</label>
                                                </div>
                                            </div>
                                            <input type="hidden" name="existing_image" value="<?php echo $storeData['image']; ?>">
                                        <?php } ?>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Upload Detail Images (up to 10):</label>
                                        <input type="file" class="form-control" name="image_detail[]" multiple accept="image/*">
                                        <small class="text-muted">You can select multiple images. Maximum 10 images total.</small>
                                        
                                        <?php if (!empty($storeData['image_detail'])) {
                                            $images = explode(',', $storeData['image_detail']); 
                                            ?>
                                            <div class="mt-3">
                                                <p class="mb-2"><strong>Current Detail Images (check to delete):</strong></p>
                                                <div class="d-flex flex-wrap gap-3">
                                                <?php foreach ($images as $img) {
                                                    if (!empty($img)) { ?>
                                                        <div class="position-relative" style="width: 100px;">
                                                            <div class="image-checkbox-container">
                                                                <input type="checkbox" name="delete_detail_images[]" value="<?php echo $img; ?>" class="form-check-input">
                                                            </div>
                                                            <img src="<?php echo $img; ?>" class="img-thumbnail" width="100" height="100">
                                                        </div>
                                                    <?php }
                                                } ?>
                                                </div>
                                            </div>
                                            <input type="hidden" name="existing_image_detail" value="<?php echo $storeData['image_detail']; ?>">
                                        <?php } ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <h5 class="mb-4">Location Information</h5>
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
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Google Maps Directions Link</label>
                                        <input type="url" class="form-control" name="directions_link" value="<?php echo htmlspecialchars($storeData['directions_link']); ?>">
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Google Map Link</label>
                                        <input type="url" class="form-control" name="maps_link" value="<?php echo htmlspecialchars($storeData['maps_link']); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Embed Google Map URL</label>
                                        <input type="url" class="form-control" name="map_embed" value="<?php echo htmlspecialchars($storeData['maps_embed']); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Nearby Places</label>
                                        <input name="nearby_places" class="form-control" value="<?php echo htmlspecialchars($nearby_places_str); ?>">
                                        <small class="text-muted">Separate nearby places with commas</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <h5 class="mb-3">Transportation Routes</h5>
                            <div id="transport-routes-container">
                                <?php if (!empty($transportation_routes)): ?>
                                    <?php foreach ($transportation_routes as $index => $route): ?>
                                        <div class="transport-route-item" data-index="<?php echo $index; ?>">
                                            <div class="transport-route-header">
                                                <h6 class="mb-0">Route #<?php echo $index + 1; ?></h6>
                                                <button type="button" class="btn btn-danger btn-sm remove-route-btn" data-route-index="<?php echo $index; ?>">
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
                                    <div class="transport-route-item" data-index="0">
                                        <div class="transport-route-header">
                                            <h6 class="mb-0">Route #1</h6>
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
                            <button type="button" id="add-route-btn" class="btn btn-secondary btn-sm mt-2">
                                <i class="bi bi-plus"></i> Add Another Route
                            </button>
                        </div>
                        
                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-check-lg"></i> <?php echo isset($_GET['edit']) ? 'Update Store' : 'Save Store'; ?>
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

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            setTimeout(function() {
                $('.toast').toast('hide');
            }, 5000);
            
            if (window.location.search.includes('edit')) {
                var modal = new bootstrap.Modal(document.getElementById('storeModal'));
                modal.show();
                history.replaceState(null, null, window.location.pathname);
            }
            
            $('#add-route-btn').click(function(e) {
                e.preventDefault();
                
                <?php if (isset($_GET['edit'])): ?>
                    var storeId = '<?php echo $_GET['edit']; ?>';
                    var container = $('#transport-routes-container');
                    var routeCount = container.children('.transport-route-item').length;
                    var newIndex = routeCount;
                    
                    $.ajax({
                        url: 'add_agricultural_store.php',
                        method: 'POST',
                        data: {
                            action: 'add_route',
                            store_id: storeId
                        },
                        success: function(response) {
                            var newRouteHtml = `
                                <div class="transport-route-item" data-index="` + newIndex + `">
                                    <div class="transport-route-header">
                                        <h6 class="mb-0">Route #` + (newIndex + 1) + `</h6>
                                        <button type="button" class="btn btn-danger btn-sm remove-route-btn" data-route-index="` + newIndex + `">
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
                            `;
                            container.append(newRouteHtml);
                        }
                    });
                <?php else: ?>
                    var container = $('#transport-routes-container');
                    var routeCount = container.children('.transport-route-item').length;
                    var newIndex = routeCount;
                    
                    var newRouteHtml = `
                        <div class="transport-route-item" data-index="` + newIndex + `">
                            <div class="transport-route-header">
                                <h6 class="mb-0">Route #` + (newIndex + 1) + `</h6>
                                <button type="button" class="btn btn-danger btn-sm remove-route-btn" data-route-index="` + newIndex + `">
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
                    `;
                    container.append(newRouteHtml);
                <?php endif; ?>
            });
            
            $(document).on('click', '.remove-route-btn', function(e) {
                e.preventDefault();
                
                var routeItem = $(this).closest('.transport-route-item');
                var routeIndex = $(this).data('route-index');
                
                <?php if (isset($_GET['edit'])): ?>
                    var storeId = '<?php echo $_GET['edit']; ?>';
                    
                    if (confirm('Are you sure you want to remove this route?')) {
                        $.ajax({
                            url: 'add_agricultural_store.php',
                            method: 'POST',
                            data: {
                                action: 'remove_route',
                                store_id: storeId,
                                route_index: routeIndex
                            },
                            success: function(response) {
                                routeItem.remove();
                                $('#transport-routes-container .transport-route-item').each(function(index) {
                                    $(this).find('h6.mb-0').text('Route #' + (index + 1));
                                });
                            }
                        });
                    }
                <?php else: ?>
                    if (confirm('Are you sure you want to remove this route?')) {
                        routeItem.remove();
                        $('#transport-routes-container .transport-route-item').each(function(index) {
                            $(this).find('h6.mb-0').text('Route #' + (index + 1));
                        });
                    }
                <?php endif; ?>
            });
            
            $('#storeModal').on('hidden.bs.modal', function() {
                window.location.href = 'add_agricultural_store.php';
            });
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>