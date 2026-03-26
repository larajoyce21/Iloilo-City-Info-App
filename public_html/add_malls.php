<?php
session_start();
include 'conn.php';

if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    
    $stmt = $conn->prepare("SELECT image, image_detail FROM malls WHERE id=?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $mall = $result->fetch_assoc();
    
    $stmt = $conn->prepare("DELETE FROM malls WHERE id=?");
    $stmt->bind_param("i", $delete_id);
    
    if ($stmt->execute()) {
        if ($mall) {
            if (!empty($mall['image']) && file_exists($mall['image'])) {
                unlink($mall['image']);
            }
            
            if (!empty($mall['image_detail'])) {
                $images = explode(',', $mall['image_detail']);
                foreach ($images as $img) {
                    if (!empty($img) && file_exists($img)) {
                        unlink($img);
                    }
                }
            }
        }
        
        $_SESSION['message'] = "Mall deleted successfully";
    } else {
        $_SESSION['error'] = "Error deleting mall: " . $conn->error;
    }
    
    header("Location: add_malls.php");
    exit();
}

if (isset($_GET['delete_image']) && isset($_GET['mall_id'])) {
    $image_path = urldecode($_GET['delete_image']);
    $mall_id = $_GET['mall_id'];
    $is_card_image = isset($_GET['is_card_image']) ? true : false;
    
    if ($is_card_image) {
        $stmt = $conn->prepare("SELECT image FROM malls WHERE id=?");
        $stmt->bind_param("i", $mall_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $mall = $result->fetch_assoc();
        
        if (!empty($mall['image']) && file_exists($mall['image'])) {
            unlink($mall['image']);
        }
        
        $stmt = $conn->prepare("UPDATE malls SET image='' WHERE id=?");
        $stmt->bind_param("i", $mall_id);
        $stmt->execute();
        
        header("Location: add_malls.php?edit=" . $mall_id);
        exit();
    } else {
        $stmt = $conn->prepare("SELECT image_detail FROM malls WHERE id=?");
        $stmt->bind_param("i", $mall_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $mall = $result->fetch_assoc();
        
        $images = explode(',', $mall['image_detail']);
        $new_images = array_diff($images, [$image_path]);
        
        $stmt = $conn->prepare("UPDATE malls SET image_detail=? WHERE id=?");
        $new_images_str = implode(',', $new_images);
        $stmt->bind_param("si", $new_images_str, $mall_id);
        $stmt->execute();
        
        if (file_exists($image_path)) {
            unlink($image_path);
        }
        
        header("Location: add_malls.php?edit=" . $mall_id);
        exit();
    }
}

$query = "SELECT id, name, image, description, location, hours, contact, public_transport FROM malls";
$stmt = $conn->prepare($query);
$stmt->execute();
$malls = $stmt->get_result();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = isset($_POST['id']) ? $_POST['id'] : null;
    $name = $_POST['name'];
    $description = $_POST['description'];
    $hours = $_POST['hours'] ?? '';
    $contact = $_POST['contact'] ?? '';
    $email = $_POST['email'] ?? '';
    $location = $_POST['location'];
    $directions_link = $_POST['directions_link'];
    $maps_link = $_POST['maps_link'];
    $website = $_POST['website'];
    $mapEmbedURL = $_POST['map_embed'] ?? '';
    $social_media = $_POST['social_media'] ?? '';
    $details_link = $_POST['details_link'] ?? '';

    // Handle nearby places as JSON
    $nearby_places = $_POST['nearby_places'] ?? '';
    $nearby_places_array = array_map('trim', explode(',', $nearby_places));
    $nearby_places_json = json_encode(array_filter($nearby_places_array));

    // Handle transportation routes
    $transportation_routes = array();
    if (isset($_POST['route_type']) && is_array($_POST['route_type'])) {
        foreach ($_POST['route_type'] as $index => $type) {
            if (!empty($type)) {
                $route_name = isset($_POST['route_name'][$index]) ? $_POST['route_name'][$index] : '';
                $route_link = isset($_POST['route_link'][$index]) ? $_POST['route_link'][$index] : '';
                $route_description = isset($_POST['route_description'][$index]) ? $_POST['route_description'][$index] : '';
                
                // Only add if at least route name is provided
                if (!empty($route_name)) {
                    $transportation_routes[] = array(
                        'type' => $type,
                        'name' => $route_name,
                        'link' => $route_link,
                        'description' => $route_description
                    );
                }
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
    
    // Handle main image deletion
    if (isset($_POST['delete_image']) && $_POST['delete_image'] == '1') {
        if (!empty($target_file) && file_exists($target_file)) {
            unlink($target_file);
        }
        $target_file = '';
    }
    
    // Handle new main image upload
    if (!empty($_FILES['image']['name'])) {
        $target_file = $target_dir . basename($_FILES["image"]["name"]);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        if (in_array($imageFileType, $allowed_types)) {
            move_uploaded_file($_FILES["image"]["tmp_name"], $target_file);
            if (!empty($_POST['existing_image']) && file_exists($_POST['existing_image'])) {
                unlink($_POST['existing_image']);
            }
        }
    }

    $imagePaths = [];
    $existingImages = [];
    
    if (!empty($_POST['existing_image_detail'])) {
        $existingImages = explode(',', $_POST['existing_image_detail']);
    }

    if (!empty($_FILES['image_detail']['name'][0])) {
        $totalNew = count($_FILES['image_detail']['name']);
        $totalCombined = count($existingImages) + $totalNew;

        if ($totalCombined > 10) {
            die("Error: You can upload a maximum of 10 detail images total.");
        }

        for ($i = 0; $i < $totalNew; $i++) {
            $fileName = basename($_FILES['image_detail']['name'][$i]);
            $tmpName = $_FILES['image_detail']['tmp_name'][$i];
            $imageFileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            if (in_array($imageFileType, $allowed_types)) {
                $uniqueName = uniqid('img_', true) . '.' . $imageFileType;
                $targetPath = $target_dir . $uniqueName;

                if (move_uploaded_file($tmpName, $targetPath)) {
                    $imagePaths[] = $targetPath;
                }
            }
        }
    }

    $allImages = array_merge($existingImages, $imagePaths);
    $target_file_detail = implode(',', $allImages);

    // Update or Insert
    if ($id) {
        $query = "UPDATE malls SET name=?, image=?, image_detail=?, description=?, location=?, maps_embed=?, maps_link=?, website=?, directions_link=?, hours=?, contact=?, email=?, nearby_places=?, social_media=?, details_link=?, transportation_routes=? WHERE id=?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssssssssssssssi", $name, $target_file, $target_file_detail, $description, $location, $mapEmbedURL, $maps_link, $website, $directions_link, $hours, $contact, $email, $nearby_places_json, $social_media, $details_link, $transportation_routes_json, $id);
    } else {
        $query = "INSERT INTO malls (name, image, image_detail, description, location, maps_embed, maps_link, website, directions_link, hours, contact, email, nearby_places, social_media, details_link, transportation_routes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssssssssssssss", $name, $target_file, $target_file_detail, $description, $location, $mapEmbedURL, $maps_link, $website, $directions_link, $hours, $contact, $email, $nearby_places_json, $social_media, $details_link, $transportation_routes_json);
    }
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Mall " . ($id ? "updated" : "added") . " successfully";
    } else {
        $_SESSION['error'] = "Error saving mall: " . $stmt->error;
    }
    
    header("Location: add_malls.php");
    exit();
}

// Initialize variables for edit mode
$nearby_places_str = '';
$transportation_routes = array();

if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM malls WHERE id=?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $mallData = $result->fetch_assoc();
        if (!empty($mallData['nearby_places'])) {
            $decoded_places = json_decode($mallData['nearby_places'], true);
            if (is_array($decoded_places)) {
                $nearby_places_str = implode(', ', $decoded_places);
            }
        }
        if (!empty($mallData['transportation_routes'])) {
            $decoded_routes = json_decode($mallData['transportation_routes'], true);
            if (is_array($decoded_routes)) {
                $transportation_routes = $decoded_routes;
            }
        }
    }
} else {
    $mallData = [
        'id' => '', 
        'name' => '', 
        'description' => '', 
        'location' => '', 
        'website' => '', 
        'image' => '',
        'maps_embed' => '', 
        'maps_link' => '',
        'directions_link' => '', 
        'hours' => '',
        'contact' => '', 
        'email' => '',
        'nearby_places' => '', 
        'image_detail' => '',
        'social_media' => '',
        'details_link' => ''
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mall Management</title>
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
        .form-section {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .transport-routes-table {
            background: white;
            border-radius: 5px;
            overflow: hidden;
        }
        .transport-routes-table table {
            margin-bottom: 0;
        }
        .transport-routes-table th {
            background: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
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
            <h1 class="text-light">Mall Management</h1>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#mallModal">
                <i class="bi bi-plus-lg"></i> Add Mall
            </button>
        </div>
        
        <?php if ($malls->num_rows == 0): ?>
            <div class="alert alert-info">
                No malls found. Click "Add Mall" to create one.
            </div>
        <?php else: ?>
            <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-4">
                <?php while ($mall = $malls->fetch_assoc()): ?>
                    <div class="col">
                        <div class="card h-100">
                            <img src="<?php echo $mall['image']; ?>" class="card-img-top" alt="<?php echo $mall['name']; ?>">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?php echo $mall['name']; ?></h5>
                                <p class="card-text flex-grow-1"><?php echo substr($mall['description'], 0, 100) . '...'; ?></p>
                                <?php if (!empty($mall['public_transport'])): ?>
                                    <p class="text-primary fw-bold"><?php echo $mall['public_transport']; ?></p>
                                <?php endif; ?>
                                <div class="d-flex justify-content-between">
                                    <a href="add_malls.php?edit=<?php echo $mall['id']; ?>" class="btn btn-primary btn-sm">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    <a href="?delete=<?php echo $mall['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this mall?')">
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

    <!-- Add/Edit Mall Modal -->
    <div class="modal fade <?php echo isset($_GET['edit']) ? 'show d-block' : ''; ?>" id="mallModal" tabindex="-1" aria-labelledby="mallModalLabel" aria-hidden="<?php echo !isset($_GET['edit']) ? 'true' : 'false'; ?>">
        <div class="modal-dialog modal-lg modal-fullscreen-lg-down">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="mallModalLabel"><?php echo isset($_GET['edit']) ? 'Edit Mall' : 'Add Mall'; ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" onclick="window.location.href='add_malls.php'"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="add_malls.php" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="<?php echo $mallData['id']; ?>">
                        
                        <div class="form-section">
                            <h5>Basic Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Name *</label>
                                        <input type="text" class="form-control" name="name" value="<?php echo $mallData['name']; ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Details Page Link</label>
                                        <input type="text" class="form-control" name="details_link" value="<?php echo $mallData['details_link']; ?>" placeholder="e.g., mall_details.php?id=1">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Description *</label>
                                        <textarea class="form-control" name="description" rows="4" required><?php echo $mallData['description']; ?></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Address *</label>
                                        <input type="text" class="form-control" name="location" value="<?php echo $mallData['location']; ?>" required>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Operating Hours</label>
                                        <textarea class="form-control" name="hours" rows="5" placeholder="e.g., Monday-Sunday: 10AM - 9PM"><?php echo $mallData['hours']; ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Contact Information</label>
                                        <input type="text" class="form-control" name="contact" value="<?php echo $mallData['contact']; ?>" placeholder="Phone number">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Email Address</label>
                                        <input type="email" class="form-control" name="email" value="<?php echo $mallData['email']; ?>" placeholder="info@mall.com">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Nearby Places</label>
                                        <input type="text" class="form-control" name="nearby_places" value="<?php echo $nearby_places_str; ?>" placeholder="Separate with commas">
                                        <small class="text-muted">Example: Hotel, Restaurant, Cinema, Parking</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h5>Images</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Upload Card Image:</label>
                                        <input type="file" class="form-control" name="image" accept="image/*">
                                        <?php if (!empty($mallData['image'])) { ?>
                                            <div class="mt-2">
                                                <img src="<?php echo $mallData['image']; ?>" class="img-thumbnail" width="150">
                                                <div class="form-check mt-1">
                                                    <input class="form-check-input" type="checkbox" name="delete_image" value="1" id="deleteImageCheck">
                                                    <label class="form-check-label" for="deleteImageCheck">Delete current image</label>
                                                </div>
                                            </div>
                                            <input type="hidden" name="existing_image" value="<?php echo $mallData['image']; ?>">
                                        <?php } ?>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Upload Detail Images (Max 10):</label>
                                        <input type="file" class="form-control" name="image_detail[]" multiple accept="image/*">
                                        
                                        <?php if (!empty($mallData['image_detail'])) {
                                            $images = explode(',', $mallData['image_detail']); 
                                            if (!empty($images[0])) {
                                                echo '<div class="d-flex flex-wrap mt-2">';
                                                foreach ($images as $img) { 
                                                    if (!empty($img)) { ?>
                                                        <div class="position-relative me-2 mb-2">
                                                            <img src="<?php echo $img; ?>" class="img-thumbnail" width="100">
                                                            <a href="?delete_image=<?php echo urlencode($img); ?>&mall_id=<?php echo $mallData['id']; ?>" 
                                                               class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" 
                                                               onclick="return confirm('Are you sure you want to delete this image?')">
                                                               ×
                                                            </a>
                                                        </div>
                                                    <?php }
                                                }
                                                echo '</div>';
                                            }
                                            ?>
                                            <input type="hidden" name="existing_image_detail" value="<?php echo $mallData['image_detail']; ?>">
                                        <?php } ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <h5>Location Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Website URL</label>
                                        <input type="url" class="form-control" name="website" value="<?php echo $mallData['website']; ?>" placeholder="https://mall.com">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Google Maps Link</label>
                                        <input type="url" class="form-control" name="maps_link" value="<?php echo $mallData['maps_link']; ?>" placeholder="https://maps.google.com/...">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Directions Link</label>
                                        <input type="url" class="form-control" name="directions_link" value="<?php echo $mallData['directions_link']; ?>" placeholder="https://maps.google.com/directions...">
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Embed Map URL</label>
                                        <input type="url" class="form-control" name="map_embed" value="<?php echo $mallData['maps_embed']; ?>" placeholder="https://maps.google.com/embed...">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Social Media Links</label>
                                        <textarea class="form-control" name="social_media" rows="5" placeholder="Enter one URL per line"><?php echo $mallData['social_media']; ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h5>Transportation Routes</h5>
                            <div class="transport-routes-table">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Transport Type</th>
                                            <th>Route Name/Number *</th>
                                            <th>Route Link</th>
                                            <th>Description</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="transportRoutesTableBody">
                                        <?php if (!empty($transportation_routes)): ?>
                                            <?php foreach ($transportation_routes as $index => $route): ?>
                                                <tr>
                                                    <td>
                                                        <select class="form-control" name="route_type[]">
                                                            <option value="mall_shuttle" <?php echo ($route['type'] == 'mall_shuttle') ? 'selected' : ''; ?>>Mall Shuttle</option>
                                                            <option value="jeepney" <?php echo ($route['type'] == 'jeepney') ? 'selected' : ''; ?>>Jeepney</option>
                                                            <option value="bus" <?php echo ($route['type'] == 'bus') ? 'selected' : ''; ?>>Bus</option>
                                                            <option value="taxi" <?php echo ($route['type'] == 'taxi') ? 'selected' : ''; ?>>Taxi</option>
                                                            <option value="tricycle" <?php echo ($route['type'] == 'tricycle') ? 'selected' : ''; ?>>Tricycle</option>
                                                            <option value="parking_shuttle" <?php echo ($route['type'] == 'parking_shuttle') ? 'selected' : ''; ?>>Parking Shuttle</option>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control" name="route_name[]" value="<?php echo htmlspecialchars($route['name']); ?>" placeholder="e.g., Mall Shuttle, Route 10" required>
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control" name="route_link[]" value="<?php echo htmlspecialchars($route['link']); ?>" placeholder="https://maps.google.com/...">
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control" name="route_description[]" value="<?php echo htmlspecialchars($route['description']); ?>" placeholder="e.g., Free shuttle service, Every 15 minutes">
                                                    </td>
                                                    <td>
                                                        <button type="button" class="btn btn-danger btn-sm" onclick="removeTransportRow(this)">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td>
                                                    <select class="form-control" name="route_type[]">
                                                        <option value="mall_shuttle">Mall Shuttle</option>
                                                        <option value="jeepney">Jeepney</option>
                                                        <option value="bus">Bus</option>
                                                        <option value="taxi">Taxi</option>
                                                        <option value="tricycle">Tricycle</option>
                                                        <option value="parking_shuttle">Parking Shuttle</option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control" name="route_name[]" placeholder="e.g., Mall Shuttle, Route 10" required>
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control" name="route_link[]" placeholder="https://maps.google.com/...">
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control" name="route_description[]" placeholder="e.g., Free shuttle service, Every 15 minutes">
                                                </td>
                                                <td>
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
                        
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="window.location.href='add_malls.php'">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <?php echo isset($_GET['edit']) ? 'Update Mall' : 'Save Mall'; ?>
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
            // Auto-hide toast messages
            var toasts = document.querySelectorAll('.toast');
            toasts.forEach(function(toast) {
                setTimeout(function() {
                    var bsToast = new bootstrap.Toast(toast);
                    bsToast.hide();
                }, 5000);
            });
            
            // Show modal when editing
            if (window.location.search.includes('edit')) {
                var modal = new bootstrap.Modal(document.getElementById('mallModal'));
                modal.show();
            }
        });

        function addTransportRow() {
            const tableBody = document.getElementById('transportRoutesTableBody');
            const newRow = document.createElement('tr');
            
            newRow.innerHTML = `
                <td>
                    <select class="form-control" name="route_type[]">
                        <option value="mall_shuttle">Mall Shuttle</option>
                        <option value="jeepney">Jeepney</option>
                        <option value="bus">Bus</option>
                        <option value="taxi">Taxi</option>
                        <option value="tricycle">Tricycle</option>
                        <option value="parking_shuttle">Parking Shuttle</option>
                    </select>
                </td>
                <td>
                    <input type="text" class="form-control" name="route_name[]" placeholder="e.g., Mall Shuttle, Route 10" required>
                </td>
                <td>
                    <input type="text" class="form-control" name="route_link[]" placeholder="https://maps.google.com/...">
                </td>
                <td>
                    <input type="text" class="form-control" name="route_description[]" placeholder="e.g., Free shuttle service, Every 15 minutes">
                </td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeTransportRow(this)">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            `;
            
            tableBody.appendChild(newRow);
        }

        function removeTransportRow(button) {
            const row = button.closest('tr');
            const tbody = document.getElementById('transportRoutesTableBody');
            
            if (tbody.children.length > 1) {
                row.remove();
            } else {
                // If it's the last row, clear all inputs
                const inputs = row.querySelectorAll('input, select');
                inputs.forEach(input => {
                    if (input.tagName === 'INPUT') {
                        input.value = '';
                    } else if (input.tagName === 'SELECT') {
                        input.selectedIndex = 0;
                    }
                });
            }
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>