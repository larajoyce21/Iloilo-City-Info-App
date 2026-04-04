<?php
session_start();
include 'conn.php';

if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    
    $stmt = $conn->prepare("SELECT image, image_detail FROM hardware WHERE id=?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $hardware = $result->fetch_assoc();
    
    $stmt = $conn->prepare("DELETE FROM hardware WHERE id=?");
    $stmt->bind_param("i", $delete_id);
    
    if ($stmt->execute()) {
        if ($hardware) {
            if (!empty($hardware['image']) && file_exists($hardware['image'])) {
                unlink($hardware['image']);
            }
            
            if (!empty($hardware['image_detail'])) {
                $images = explode(',', $hardware['image_detail']);
                foreach ($images as $img) {
                    if (!empty($img) && file_exists($img)) {
                        unlink($img);
                    }
                }
            }
        }
        
        $_SESSION['message'] = "Hardware store deleted successfully";
    } else {
        $_SESSION['error'] = "Error deleting hardware store: " . $conn->error;
    }
    
    header("Location: add_hardware.php");
    exit();
}

if (isset($_GET['delete_image']) && isset($_GET['hardware_id'])) {
    $image_path = urldecode($_GET['delete_image']);
    $hardware_id = intval($_GET['hardware_id']);
    $is_card_image = isset($_GET['is_card_image']) ? true : false;
    
    if (file_exists($image_path)) {
        unlink($image_path);
    }
    
    if ($is_card_image) {
        $stmt = $conn->prepare("UPDATE hardware SET image='' WHERE id=?");
        $stmt->bind_param("i", $hardware_id);
        $stmt->execute();
    } else {
        $stmt = $conn->prepare("SELECT image_detail FROM hardware WHERE id=?");
        $stmt->bind_param("i", $hardware_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $hardware = $result->fetch_assoc();
        
        if ($hardware) {
            $images = explode(',', $hardware['image_detail']);
            $updated_images = array();
            foreach ($images as $img) {
                if (trim($img) != trim($image_path)) {
                    $updated_images[] = $img;
                }
            }
            $updated_images_str = implode(',', $updated_images);
            
            $stmt = $conn->prepare("UPDATE hardware SET image_detail=? WHERE id=?");
            $stmt->bind_param("si", $updated_images_str, $hardware_id);
            $stmt->execute();
        }
    }
    
    $_SESSION['message'] = "Image deleted successfully";
    header("Location: add_hardware.php?edit=" . $hardware_id);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'] ?? null;
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $location = $_POST['location'] ?? '';
    $website = $_POST['website'] ?? '';
    $maps_link = $_POST['maps_link'] ?? '';
    $maps_embed = $_POST['map_embed'] ?? '';
    $hours = $_POST['hours'] ?? '';
    $contact = $_POST['contact'] ?? '';
    $email = $_POST['email'] ?? '';
    $product_line = $_POST['product_line'] ?? '';
    $warranty = $_POST['warranty'] ?? '';
    $services = $_POST['services'] ?? '';
    $accessibility = $_POST['accessibility'] ?? '';
    $social_media = $_POST['social_media'] ?? '';
    $directions = $_POST['directions'] ?? '';
    $nearby_places = $_POST['nearby_places'] ?? '';
    $details_link = $_POST['details_link'] ?? '';
    $specialties = $_POST['specialties'] ?? '';
    $brands = $_POST['brands'] ?? '';
    $established = $_POST['established'] ?? '';
    $categories = $_POST['categories'] ?? '';
    $price_range = $_POST['price_range'] ?? '';
    $payment_options = $_POST['payment_options'] ?? '';
    $delivery_options = $_POST['delivery_options'] ?? '';
    $branches = $_POST['branches'] ?? '';

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

    $nearby_places_array = array_map('trim', explode(',', $nearby_places));
    $nearby_places_json = json_encode(array_filter($nearby_places_array));

    $target_dir = "uploads/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $target_file = $_POST['existing_image'] ?? '';
    
    // Handle main image upload
    if (!empty($_FILES['image']['name'])) {
        $target_file = $target_dir . basename($_FILES["image"]["name"]);
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            if (!empty($_POST['existing_image']) && file_exists($_POST['existing_image'])) {
                unlink($_POST['existing_image']);
            }
        } else {
            $_SESSION['error'] = "Error uploading main image";
            header("Location: add_hardware.php" . ($id ? "?edit=$id" : ""));
            exit();
        }
    } elseif (isset($_POST['delete_image']) && $_POST['delete_image'] == '1') {
        if (!empty($_POST['existing_image']) && file_exists($_POST['existing_image'])) {
            unlink($_POST['existing_image']);
        }
        $target_file = '';
    }

    // Handle detail images
    $imagePaths = [];
    $existingImages = !empty($_POST['existing_image_detail']) ? explode(',', $_POST['existing_image_detail']) : [];
    
    if (!empty($_FILES['image_detail']['name'][0])) {
        foreach ($_FILES['image_detail']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['image_detail']['error'][$key] === UPLOAD_ERR_OK) {
                $file_ext = strtolower(pathinfo($_FILES['image_detail']['name'][$key], PATHINFO_EXTENSION));
                $uniqueName = uniqid('img_', true) . '.' . $file_ext;
                $targetPath = $target_dir . $uniqueName;
                
                if (move_uploaded_file($tmp_name, $targetPath)) {
                    $imagePaths[] = $targetPath;
                } else {
                    $_SESSION['error'] = "Error uploading detail images";
                    header("Location: add_hardware.php" . ($id ? "?edit=$id" : ""));
                    exit();
                }
            }
        }
    }
    
    $allImages = array_merge($existingImages, $imagePaths);
    $target_file_detail = implode(',', $allImages);

    if ($id) {
        $query = "UPDATE hardware SET 
            name=?, image=?, image_detail=?, description=?, location=?, 
            maps_link=?, maps_embed=?, website=?, hours=?, contact=?, 
            email=?, 
            product_line=?, warranty=?, services=?, accessibility=?, nearby_places=?, 
            social_media=?, directions=?, details_link=?, 
            specialties=?, brands=?, established=?, categories=?, 
            price_range=?, payment_options=?, 
            delivery_options=?, branches=?, transportation_routes=?
            WHERE id=?";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param(
            "ssssssssssssssssssssssssssssi", 
            $name, $target_file, $target_file_detail, $description, $location,
            $maps_link, $maps_embed, $website, $hours, $contact,
            $email,
            $product_line, $warranty, $services, $accessibility, $nearby_places_json,
            $social_media, $directions, $details_link,
            $specialties, $brands, $established, $categories,
            $price_range, $payment_options,
            $delivery_options, $branches, $transportation_routes_json, $id
        );
    } else {
        $query = "INSERT INTO hardware (
            name, image, image_detail, description, location, 
            maps_link, maps_embed, website, hours, contact, 
            email, 
            product_line, warranty, services, accessibility, nearby_places, 
            social_media, directions, details_link, 
            specialties, brands, established, categories, 
            price_range, payment_options, 
            delivery_options, branches, transportation_routes
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param(
            "ssssssssssssssssssssssssssss", 
            $name, $target_file, $target_file_detail, $description, $location,
            $maps_link, $maps_embed, $website, $hours, $contact,
            $email,
            $product_line, $warranty, $services, $accessibility, $nearby_places_json,
            $social_media, $directions, $details_link,
            $specialties, $brands, $established, $categories,
            $price_range, $payment_options,
            $delivery_options, $branches, $transportation_routes_json
        );
    }

    if ($stmt->execute()) {
        $_SESSION['message'] = "Hardware store " . ($id ? "updated" : "added") . " successfully";
    } else {
        $_SESSION['error'] = "Error saving hardware store: " . $stmt->error;
    }
    
    header("Location: add_hardware.php");
    exit();
}

$query = "SELECT id, name, image FROM hardware";
$stmt = $conn->prepare($query);
$stmt->execute();
$hardware_stores = $stmt->get_result();

$hardwareData = [
    'id' => '', 'name' => '', 'image' => '', 'image_detail' => '', 'description' => '', 
    'location' => '', 'maps_link' => '', 'maps_embed' => '', 'website' => '', 
    'hours' => '', 'contact' => '', 'email' => '', 'product_line' => '', 'warranty' => '', 'services' => '', 
    'accessibility' => '', 'nearby_places' => '', 'social_media' => '', 
    'directions' => '', 'details_link' => '', 'specialties' => '', 
    'brands' => '', 'established' => '', 'categories' => '', 'price_range' => '', 
    'payment_options' => '', 'delivery_options' => '', 'branches' => ''
];

$nearby_places_str = '';
$transportation_routes = array();

if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT *, transportation_routes FROM hardware WHERE id=?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $hardwareData = $result->fetch_assoc();
        
        if (!empty($hardwareData['nearby_places'])) {
            $decoded_places = json_decode($hardwareData['nearby_places'], true);
            if (is_array($decoded_places)) {
                $nearby_places_str = implode(', ', $decoded_places);
            }
        }
        
        // Get transportation routes
        if (!empty($hardwareData['transportation_routes'])) {
            $decoded_routes = json_decode($hardwareData['transportation_routes'], true);
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
    <title>Hardware Stores Management</title>
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

    <div class="container mt-5">
        <button type="button" class="btn btn-light text-dark mb-3" onclick="window.location.href='dashboard.php'">
            <i class="bi bi-arrow-left"></i> Back to Dashboard
        </button>
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="text-light">Hardware Stores Management</h1>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#hardwareModal">
                <i class="bi bi-plus-lg"></i> Add Hardware Store
            </button>
        </div>
        
        <?php if ($hardware_stores->num_rows == 0): ?>
            <div class="alert alert-info">
                No hardware stores found. Click "Add Hardware Store" to create one.
            </div>
        <?php else: ?>
            <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-4">
                <?php while ($hardware = $hardware_stores->fetch_assoc()): ?>
                    <div class="col">
                        <div class="card h-100">
                            <?php if (!empty($hardware['image'])): ?>
                                <img src="<?= htmlspecialchars($hardware['image']) ?>" class="card-img-top" alt="<?= htmlspecialchars($hardware['name']) ?>">
                            <?php else: ?>
                                <div class="card-img-top bg-secondary d-flex align-items-center justify-content-center">
                                    <i class="bi bi-tools text-white" style="font-size: 3rem;"></i>
                                </div>
                            <?php endif; ?>
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?= htmlspecialchars($hardware['name']) ?></h5>
                                <div class="mt-auto d-flex justify-content-between">
                                    <a href="add_hardware.php?edit=<?= $hardware['id'] ?>" class="btn btn-primary btn-sm">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    <a href="?delete=<?= $hardware['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this hardware store?')">
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

    <!-- Add/Edit Modal -->
    <div class="modal fade <?php echo isset($_GET['edit']) ? 'show d-block' : ''; ?>" id="hardwareModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo isset($_GET['edit']) ? 'Edit Hardware Store' : 'Add Hardware Store'; ?></h5>
                    <button type="button" class="btn-close" onclick="window.location.href='add_hardware.php'"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="<?php echo $hardwareData['id']; ?>">
                        
                        <div class="form-section">
                            <h5>Basic Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Name *</label>
                                        <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($hardwareData['name']); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Main Image</label>
                                        <input type="file" class="form-control" name="image">
                                        <?php if ($hardwareData['image']) { ?>
                                            <div class="mt-2">
                                                <img src="<?php echo htmlspecialchars($hardwareData['image']); ?>" class="img-thumbnail" width="100">
                                                <div class="form-check mt-1">
                                                    <input class="form-check-input" type="checkbox" name="delete_image" value="1" id="deleteImageCheck">
                                                    <label class="form-check-label" for="deleteImageCheck">Delete current image</label>
                                                </div>
                                            </div>
                                            <input type="hidden" name="existing_image" value="<?php echo htmlspecialchars($hardwareData['image']); ?>">
                                        <?php } ?>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Description</label>
                                        <textarea class="form-control" name="description" rows="3"><?php echo htmlspecialchars($hardwareData['description']); ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Specialties</label>
                                        <textarea class="form-control" name="specialties" rows="3"><?php echo htmlspecialchars($hardwareData['specialties']); ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Brands Carried</label>
                                        <textarea class="form-control" name="brands" rows="3"><?php echo htmlspecialchars($hardwareData['brands']); ?></textarea>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Location/Address *</label>
                                        <input type="text" class="form-control" name="location" value="<?php echo htmlspecialchars($hardwareData['location']); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Website</label>
                                        <input type="text" class="form-control" name="website" value="<?php echo htmlspecialchars($hardwareData['website']); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Operating Hours</label>
                                        <textarea class="form-control" name="hours" rows="3"><?php echo htmlspecialchars($hardwareData['hours']); ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Contact Info</label>
                                        <input type="text" class="form-control" name="contact" value="<?php echo htmlspecialchars($hardwareData['contact']); ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($hardwareData['email'] ?? ''); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Product Lines</label>
                                        <textarea class="form-control" name="product_line" rows="3"><?php echo htmlspecialchars($hardwareData['product_line']); ?></textarea>
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
                                                    <input type="text" class="form-control" name="route_name[]" value="<?php echo isset($transportation_routes[$i]['name']) ? htmlspecialchars($transportation_routes[$i]['name']) : ''; ?>" placeholder="e.g., Route 101, Loading Zone A, Truck Route">
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control" name="route_link[]" value="<?php echo isset($transportation_routes[$i]['link']) ? htmlspecialchars($transportation_routes[$i]['link']) : ''; ?>" placeholder="https://maps.google.com/...">
                                                </td>
                                                <td>
                                                    <textarea class="form-control" name="route_description[]" rows="2" placeholder="e.g., Large vehicle friendly, loading zone for materials, ample parking"><?php echo isset($transportation_routes[$i]['description']) ? htmlspecialchars($transportation_routes[$i]['description']) : ''; ?></textarea>
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
                            <h5>Additional Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Detail Images (Max 5)</label>
                                        <input type="file" class="form-control" name="image_detail[]" multiple>
                                        
                                        <?php if (!empty($hardwareData['image_detail'])) {
                                            $images = explode(',', $hardwareData['image_detail']);
                                            echo '<div class="d-flex flex-wrap gap-2 mt-2">';
                                            foreach ($images as $img) {
                                                if (!empty($img)) { ?>
                                                    <div class="position-relative">
                                                        <img src="<?php echo htmlspecialchars($img); ?>" class="img-thumbnail" width="100">
                                                        <a href="?delete_image=<?php echo urlencode($img); ?>&hardware_id=<?php echo $hardwareData['id']; ?>" 
                                                           class="position-absolute top-0 end-0 bg-danger text-white px-1" 
                                                           onclick="return confirm('Delete this image?')">×</a>
                                                    </div>
                                                <?php }
                                            }
                                            echo '</div>';
                                            ?>
                                            <input type="hidden" name="existing_image_detail" value="<?php echo htmlspecialchars($hardwareData['image_detail']); ?>">
                                        <?php } ?>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Branches (one per line):</label>
                                        <textarea class="form-control" name="branches" rows="3"><?php echo htmlspecialchars($hardwareData['branches']); ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Nearby Places (comma separated)</label>
                                        <input type="text" class="form-control" name="nearby_places" value="<?php echo htmlspecialchars($nearby_places_str); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Social Media Links (one per line)</label>
                                        <textarea class="form-control" name="social_media" rows="3"><?php echo htmlspecialchars($hardwareData['social_media']); ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Directions</label>
                                        <input type="text" class="form-control" name="directions" value="<?php echo htmlspecialchars($hardwareData['directions']); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Details Page Link</label>
                                        <input type="text" class="form-control" name="details_link" value="<?php echo htmlspecialchars($hardwareData['details_link']); ?>">
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Warranty Info</label>
                                                <input type="text" class="form-control" name="warranty" value="<?php echo htmlspecialchars($hardwareData['warranty']); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Services Offered</label>
                                                <input type="text" class="form-control" name="services" value="<?php echo htmlspecialchars($hardwareData['services']); ?>">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Accessibility</label>
                                                <input type="text" class="form-control" name="accessibility" value="<?php echo htmlspecialchars($hardwareData['accessibility']); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Established Year</label>
                                                <input type="text" class="form-control" name="established" value="<?php echo htmlspecialchars($hardwareData['established']); ?>">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Product Categories</label>
                                        <input type="text" class="form-control" name="categories" value="<?php echo htmlspecialchars($hardwareData['categories']); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Price Range</label>
                                        <input type="text" class="form-control" name="price_range" value="<?php echo htmlspecialchars($hardwareData['price_range']); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Payment Options</label>
                                        <input type="text" class="form-control" name="payment_options" value="<?php echo htmlspecialchars($hardwareData['payment_options']); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Delivery Options</label>
                                        <input type="text" class="form-control" name="delivery_options" value="<?php echo htmlspecialchars($hardwareData['delivery_options']); ?>">
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Google Maps Link</label>
                                                <input type="text" class="form-control" name="maps_link" value="<?php echo htmlspecialchars($hardwareData['maps_link']); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Embed Map URL</label>
                                                <input type="text" class="form-control" name="map_embed" value="<?php echo htmlspecialchars($hardwareData['maps_embed']); ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between mt-3">
                            <button type="submit" class="btn btn-success">Save</button>
                            <button type="button" class="btn btn-secondary" onclick="window.location.href='add_hardware.php'">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <?php if (isset($_GET['edit'])): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var myModal = new bootstrap.Modal(document.getElementById('hardwareModal'));
            myModal.show();
        });
    </script>
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
                var modal = new bootstrap.Modal(document.getElementById('hardwareModal'));
                modal.show();
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
                    <input type="text" class="form-control" name="route_name[]" placeholder="e.g., Route 101, Loading Zone A, Truck Route">
                </td>
                <td>
                    <input type="text" class="form-control" name="route_link[]" placeholder="https://maps.google.com/...">
                </td>
                <td>
                    <textarea class="form-control" name="route_description[]" rows="2" placeholder="e.g., Large vehicle friendly, loading zone for materials, ample parking"></textarea>
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