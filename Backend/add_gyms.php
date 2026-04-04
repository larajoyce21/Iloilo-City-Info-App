<?php
session_start();
include 'conn.php';

if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    
    $stmt = $conn->prepare("SELECT image, image_detail FROM gyms WHERE id=?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $gym = $result->fetch_assoc();
    
    $stmt = $conn->prepare("DELETE FROM gyms WHERE id=?");
    $stmt->bind_param("i", $delete_id);
    
    if ($stmt->execute()) {
        if ($gym) {
            if (!empty($gym['image']) && file_exists($gym['image'])) {
                unlink($gym['image']);
            }
            
            if (!empty($gym['image_detail'])) {
                $images = explode(',', $gym['image_detail']);
                foreach ($images as $img) {
                    if (!empty($img) && file_exists($img)) {
                        unlink($img);
                    }
                }
            }
        }
        
        $_SESSION['message'] = "Gym deleted successfully";
    } else {
        $_SESSION['error'] = "Error deleting gym: " . $conn->error;
    }
    
    header("Location: add_gyms.php");
    exit();
}

if (isset($_GET['delete_image']) && isset($_GET['gym_id'])) {
    $image_path = urldecode($_GET['delete_image']);
    $gym_id = intval($_GET['gym_id']);
    $is_card_image = isset($_GET['is_card_image']) ? true : false;
    
    if (file_exists($image_path)) {
        unlink($image_path);
    }
    
    if ($is_card_image) {
        $stmt = $conn->prepare("UPDATE gyms SET image='' WHERE id=?");
        $stmt->bind_param("i", $gym_id);
        $stmt->execute();
    } else {
        $stmt = $conn->prepare("SELECT image_detail FROM gyms WHERE id=?");
        $stmt->bind_param("i", $gym_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $gym = $result->fetch_assoc();
        
        if ($gym) {
            $images = explode(',', $gym['image_detail']);
            $updated_images = array();
            foreach ($images as $img) {
                if (trim($img) != trim($image_path)) {
                    $updated_images[] = $img;
                }
            }
            $updated_images_str = implode(',', $updated_images);
            
            $stmt = $conn->prepare("UPDATE gyms SET image_detail=? WHERE id=?");
            $stmt->bind_param("si", $updated_images_str, $gym_id);
            $stmt->execute();
        }
    }
    
    $_SESSION['message'] = "Image deleted successfully";
    header("Location: add_gyms.php?edit=" . $gym_id);
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

$query = "SELECT id, name, image, description, location, maps_embed, directions_link, maps_link, website, hours, contact, email, amenities, trainers, membership_plans, nearby_places, social_media, details_link, facility_type FROM gyms";
$stmt = $conn->prepare($query);
$stmt->execute();
$gyms = $stmt->get_result();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : null;
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $hours = trim($_POST['hours'] ?? '');
    $contact = trim($_POST['contact'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $location = trim($_POST['location']);
    $website = trim($_POST['website']);
    $maps_link = trim($_POST['maps_link']);
    $directions_link = trim($_POST['directions_link']);
    $mapEmbedURL = htmlspecialchars(trim($_POST['map_embed'] ?? ''));
    $social_media = trim($_POST['social_media']);
    $details_link = trim($_POST['details_link']);
    $facility_type = trim($_POST['facility_type']);

    // Process amenities
    $amenities = $_POST['amenities'] ?? '';
    $amenities_array = array_map('trim', explode(',', $amenities));
    $amenities_json = json_encode(array_filter($amenities_array));

    // Process nearby places
    $nearby_places = $_POST['nearby_places'] ?? '';
    $nearby_places_array = array_map('trim', explode(',', $nearby_places));
    $nearby_places_json = json_encode(array_filter($nearby_places_array));

    // Process trainers
    $trainers_json = '[]';
    if (isset($_POST['trainer_name']) && is_array($_POST['trainer_name'])) {
        $trainers = [];
        for ($i = 0; $i < count($_POST['trainer_name']); $i++) {
            if (!empty($_POST['trainer_name'][$i])) {
                $trainer = [
                    'name' => $_POST['trainer_name'][$i],
                    'specialty' => $_POST['trainer_specialty'][$i] ?? '',
                    'bio' => $_POST['trainer_bio'][$i] ?? '',
                    'image' => $_POST['trainer_existing_image'][$i] ?? ''
                ];
                $trainers[] = $trainer;
            }
        }
        $trainers_json = json_encode($trainers);
    }

    // Process membership plans
    $membership_plans_json = '[]';
    if (isset($_POST['plan_name']) && is_array($_POST['plan_name'])) {
        $plans = [];
        for ($i = 0; $i < count($_POST['plan_name']); $i++) {
            if (!empty($_POST['plan_name'][$i])) {
                $plan = [
                    'name' => $_POST['plan_name'][$i],
                    'price' => floatval($_POST['plan_price'][$i] ?? 0),
                    'period' => $_POST['plan_period'][$i] ?? '',
                    'features' => array_map('trim', explode(',', $_POST['plan_features'][$i] ?? ''))
                ];
                $plans[] = $plan;
            }
        }
        $membership_plans_json = json_encode($plans);
    }

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
       
    // Update or Insert
    if ($id) {
        $query = "UPDATE gyms SET name=?, image=?, image_detail=?, description=?, location=?, maps_embed=?, maps_link=?, website=?, directions_link=?, hours=?, contact=?, email=?, amenities=?, trainers=?, membership_plans=?, nearby_places=?, social_media=?, details_link=?, facility_type=?, transportation_routes=? WHERE id=?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssssssssssssssssssi", $name, $target_file, $target_file_detail, $description, $location, $mapEmbedURL, $maps_link, $website, $directions_link, $hours, $contact, $email, $amenities_json, $trainers_json, $membership_plans_json, $nearby_places_json, $social_media, $details_link, $facility_type, $transportation_routes_json, $id);
    } else {
        $query = "INSERT INTO gyms (name, image, image_detail, description, location, maps_embed, maps_link, website, directions_link, hours, contact, email, amenities, trainers, membership_plans, nearby_places, social_media, details_link, facility_type, transportation_routes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssssssssssssssssss", $name, $target_file, $target_file_detail, $description, $location, $mapEmbedURL, $maps_link, $website, $directions_link, $hours, $contact, $email, $amenities_json, $trainers_json, $membership_plans_json, $nearby_places_json, $social_media, $details_link, $facility_type, $transportation_routes_json);
    }
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Gym " . ($id ? "updated" : "added") . " successfully";
    } else {
        $_SESSION['error'] = "Error saving gym: " . $conn->error;
    }
    
    header("Location: add_gyms.php");
    exit();
}

$amenities_str = '';
$nearby_places_str = '';
$trainers_data = [];
$membership_plans_data = [];
$transportation_routes = [];

if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT amenities, nearby_places, trainers, membership_plans, transportation_routes FROM gyms WHERE id=?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        
        if (!empty($row['amenities'])) {
            $decoded_amenities = json_decode($row['amenities'], true);
            if (is_array($decoded_amenities)) {
                $amenities_str = implode(', ', $decoded_amenities);
            }
        }
        
        if (!empty($row['nearby_places'])) {
            $decoded_places = json_decode($row['nearby_places'], true);
            if (is_array($decoded_places)) {
                $nearby_places_str = implode(', ', $decoded_places);
            }
        }
        
        if (!empty($row['trainers'])) {
            $trainers_data = json_decode($row['trainers'], true);
            if (!is_array($trainers_data)) {
                $trainers_data = [];
            }
        }
        
        if (!empty($row['membership_plans'])) {
            $membership_plans_data = json_decode($row['membership_plans'], true);
            if (!is_array($membership_plans_data)) {
                $membership_plans_data = [];
            }
        }
        
        // Get transportation routes
        if (!empty($row['transportation_routes'])) {
            $decoded_routes = json_decode($row['transportation_routes'], true);
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
    <title>Gyms Management</title>
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
        .trainer-section, .plan-section {
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 15px;
            background-color: #f8f9fa;
        }
        .remove-btn {
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
            <h1 class="text-light">Gyms & Fitness Facilities Management</h1>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#gymModal">
                <i class="bi bi-plus-lg"></i> Add Gym
            </button>
        </div>
        
        <?php if ($gyms->num_rows == 0): ?>
            <div class="alert alert-info">
                No gyms found. Click "Add Gym" to create one.
            </div>
        <?php else: ?>
            <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-4">
                <?php while ($gym = $gyms->fetch_assoc()): ?>
                    <div class="col">
                        <div class="card h-100">
                            <img src="<?php echo htmlspecialchars($gym['image']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($gym['name']); ?>">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?php echo htmlspecialchars($gym['name']); ?></h5>
                                <p class="card-text flex-grow-1"><?php echo substr(htmlspecialchars($gym['description']), 0, 100) . '...'; ?></p>
                                <div class="d-flex justify-content-between">
                                    <a href="add_gyms.php?edit=<?php echo $gym['id']; ?>" class="btn btn-primary btn-sm">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    <a href="?delete=<?php echo $gym['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this gym?')">
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

    <div class="modal fade <?php echo isset($_GET['edit']) ? 'show d-block' : ''; ?>" id="gymModal" tabindex="-1" aria-labelledby="gymModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="gymModalLabel"><?php echo isset($_GET['edit']) ? 'Edit Gym' : 'Add Gym'; ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php 
                        $gymData = [
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
                            'email' => '',
                            'amenities' => '',
                            'trainers' => '',
                            'membership_plans' => '',
                            'nearby_places' => '',
                            'social_media' => '',
                            'details_link' => '',
                            'facility_type' => ''
                        ];
                        
                        if (isset($_GET['edit'])) {
                            $edit_id = intval($_GET['edit']);
                            $stmt = $conn->prepare("SELECT * FROM gyms WHERE id=?");
                            $stmt->bind_param("i", $edit_id);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            if ($result->num_rows > 0) {
                                $gymData = $result->fetch_assoc();
                            }
                        }
                    ?>
                    <form method="POST" action="add_gyms.php" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($gymData['id']); ?>">
                        
                        <div class="form-section">
                            <h5>Basic Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Name *</label>
                                        <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($gymData['name']); ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Facility Type</label>
                                        <select class="form-control" name="facility_type">
                                            <option value="">Select Type</option>
                                            <option value="Gym" <?php echo ($gymData['facility_type'] == 'Gym') ? 'selected' : ''; ?>>Gym</option>
                                            <option value="Fitness Center" <?php echo ($gymData['facility_type'] == 'Fitness Center') ? 'selected' : ''; ?>>Fitness Center</option>
                                            <option value="Yoga Studio" <?php echo ($gymData['facility_type'] == 'Yoga Studio') ? 'selected' : ''; ?>>Yoga Studio</option>
                                            <option value="Martial Arts Studio" <?php echo ($gymData['facility_type'] == 'Martial Arts Studio') ? 'selected' : ''; ?>>Martial Arts Studio</option>
                                            <option value="Sports Complex" <?php echo ($gymData['facility_type'] == 'Sports Complex') ? 'selected' : ''; ?>>Sports Complex</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Details Link:</label>
                                        <input type="text" class="form-control" name="details_link" value="<?php echo htmlspecialchars($gymData['details_link']); ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Description *</label>
                                        <textarea class="form-control" name="description" rows="4" required><?php echo htmlspecialchars($gymData['description']); ?></textarea>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Operating Hours</label>
                                        <textarea class="form-control" name="hours" rows="5" placeholder="e.g., Monday: 5AM - 10PM"><?php echo htmlspecialchars($gymData['hours']); ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Contact Information</label>
                                        <input type="text" class="form-control" name="contact" value="<?php echo htmlspecialchars($gymData['contact']); ?>" placeholder="Phone, social media">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Email Address</label>
                                        <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($gymData['email']); ?>" placeholder="example@domain.com">
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
                                                        <option value="bus" <?php echo (isset($transportation_routes[$i]['type']) && $transportation_routes[$i]['type'] == 'bus') ? 'selected' : ''; ?>>Bus</option>
                                                        <option value="jeepney" <?php echo (isset($transportation_routes[$i]['type']) && $transportation_routes[$i]['type'] == 'jeepney') ? 'selected' : ''; ?>>Jeepney</option>
                                                        <option value="tricycle" <?php echo (isset($transportation_routes[$i]['type']) && $transportation_routes[$i]['type'] == 'tricycle') ? 'selected' : ''; ?>>Tricycle</option>
                                                        <option value="taxi" <?php echo (isset($transportation_routes[$i]['type']) && $transportation_routes[$i]['type'] == 'taxi') ? 'selected' : ''; ?>>Taxi</option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control" name="route_name[]" value="<?php echo isset($transportation_routes[$i]['name']) ? htmlspecialchars($transportation_routes[$i]['name']) : ''; ?>" placeholder="e.g., Route 101, Gym Shuttle, Parking Area">
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control" name="route_link[]" value="<?php echo isset($transportation_routes[$i]['link']) ? htmlspecialchars($transportation_routes[$i]['link']) : ''; ?>" placeholder="https://maps.google.com/...">
                                                </td>
                                                <td>
                                                    <textarea class="form-control" name="route_description[]" rows="2" placeholder="e.g., Direct route to gym, ample parking for members, bike racks available"><?php echo isset($transportation_routes[$i]['description']) ? htmlspecialchars($transportation_routes[$i]['description']) : ''; ?></textarea>
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
                            <h5>Images</h5>
                            <div class="mb-3">
                                <label class="form-label">Upload Card Image:</label>
                                <input type="file" class="form-control" name="image">
                                
                                <?php if (!empty($gymData['image'])) { ?>
                                    <div class="mt-2">
                                        <img src="<?php echo htmlspecialchars($gymData['image']); ?>" class="img-thumbnail" width="100">
                                        <div class="form-check mt-1">
                                            <input class="form-check-input" type="checkbox" name="delete_image" value="1" id="deleteImageCheck">
                                            <label class="form-check-label" for="deleteImageCheck">Delete current image</label>
                                        </div>
                                    </div>
                                    <input type="hidden" name="existing_image" value="<?php echo htmlspecialchars($gymData['image']); ?>">
                                <?php } ?>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Upload Detail Images (2-5):</label>
                                <input type="file" class="form-control" name="image_detail[]" multiple accept="image/*">
                                <?php if (!empty($gymData['image_detail'])) {
                                    $images = explode(',', $gymData['image_detail']); 
                                    echo '<div class="d-flex flex-wrap mt-2">';
                                    foreach ($images as $img) {
                                        if (!empty($img)) { ?>
                                            <div class="position-relative me-2 mb-2">
                                                <img src="<?php echo htmlspecialchars($img); ?>" class="img-thumbnail" width="100">
                                                <a href="?delete_image=<?php echo urlencode($img); ?>&gym_id=<?php echo $gymData['id']; ?>" 
                                                   class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" 
                                                   onclick="return confirm('Are you sure you want to delete this image?')">
                                                   ×
                                                </a>
                                            </div>
                                        <?php }
                                    }
                                    echo '</div>'; ?>
                                    <input type="hidden" name="existing_image_detail" value="<?php echo htmlspecialchars($gymData['image_detail']); ?>">
                                <?php } ?>
                            </div>
                        </div>

                        <div class="form-section">
                            <h5>Location Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Address *</label>
                                        <input type="text" class="form-control" name="location" value="<?php echo htmlspecialchars($gymData['location']); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Website URL</label>
                                        <input type="url" class="form-control" name="website" value="<?php echo htmlspecialchars($gymData['website']); ?>" placeholder="https://example.com">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Google Maps Directions Link</label>
                                        <input type="url" class="form-control" name="directions_link" value="<?php echo htmlspecialchars($gymData['directions_link']); ?>" placeholder="https://maps.google.com/...">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Google Map Link</label>
                                        <input type="url" class="form-control" name="maps_link" value="<?php echo htmlspecialchars($gymData['maps_link']); ?>" placeholder="https://maps.google.com/...">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Embed Google Map URL</label>
                                <input type="url" class="form-control" name="map_embed" value="<?php echo htmlspecialchars($gymData['maps_embed']); ?>" placeholder="https://maps.google.com/embed...">
                            </div>
                        </div>

                        <div class="form-section">
                            <h5>Amenities & Features</h5>
                            <div class="mb-3">
                                <label class="form-label">Amenities:</label>
                                <input name="amenities" class="form-control" value="<?= htmlspecialchars($amenities_str); ?>" placeholder="Add amenities separated by commas">
                                <small class="text-muted">Example: Cardio Equipment, Weight Training, Pool, Sauna, Locker Rooms</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Nearby Places:</label>
                                <input name="nearby_places" class="form-control" value="<?= htmlspecialchars($nearby_places_str); ?>" placeholder="Add nearby places separated by commas">
                                <small class="text-muted">Example: Mall, Park, Restaurant, Sports Store</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Social Media Links</label>
                                <textarea class="form-control" name="social_media" rows="5" placeholder="Enter one URL per line, e.g.https://facebook.com/gym"><?php echo isset($gymData['social_media']) ? htmlspecialchars($gymData['social_media']) : ''; ?></textarea>
                            </div>
                        </div>

                        <div class="form-section">
                            <h5>Trainers</h5>
                            <div id="trainers-container">
                                <?php if (empty($trainers_data)): ?>
                                    <div class="trainer-section">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label class="form-label">Trainer Name</label>
                                                    <input type="text" class="form-control" name="trainer_name[]">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label class="form-label">Specialty</label>
                                                    <input type="text" class="form-control" name="trainer_specialty[]">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label class="form-label">Bio</label>
                                                    <input type="text" class="form-control" name="trainer_bio[]">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($trainers_data as $index => $trainer): ?>
                                        <div class="trainer-section">
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label class="form-label">Trainer Name</label>
                                                        <input type="text" class="form-control" name="trainer_name[]" value="<?= htmlspecialchars($trainer['name'] ?? '') ?>">
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label class="form-label">Specialty</label>
                                                        <input type="text" class="form-control" name="trainer_specialty[]" value="<?= htmlspecialchars($trainer['specialty'] ?? '') ?>">
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label class="form-label">Bio</label>
                                                        <input type="text" class="form-control" name="trainer_bio[]" value="<?= htmlspecialchars($trainer['bio'] ?? '') ?>">
                                                        <input type="hidden" name="trainer_existing_image[]" value="<?= htmlspecialchars($trainer['image'] ?? '') ?>">
                                                    </div>
                                                </div>
                                            </div>
                                            <button type="button" class="btn btn-danger btn-sm remove-btn remove-trainer">Remove Trainer</button>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <button type="button" class="btn btn-secondary btn-sm" id="add-trainer">Add Another Trainer</button>
                        </div>

                        <div class="form-section">
                            <h5>Membership Plans</h5>
                            <div id="plans-container">
                                <?php if (empty($membership_plans_data)): ?>
                                    <div class="plan-section">
                                        <div class="row">
                                            <div class="col-md-3">
                                                <div class="mb-3">
                                                    <label class="form-label">Plan Name</label>
                                                    <input type="text" class="form-control" name="plan_name[]">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="mb-3">
                                                    <label class="form-label">Price (₱)</label>
                                                    <input type="number" step="0.01" class="form-control" name="plan_price[]">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="mb-3">
                                                    <label class="form-label">Period</label>
                                                    <input type="text" class="form-control" name="plan_period[]" placeholder="e.g., Monthly, Annual">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="mb-3">
                                                    <label class="form-label">Features</label>
                                                    <input type="text" class="form-control" name="plan_features[]" placeholder="Separate with commas">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($membership_plans_data as $index => $plan): ?>
                                        <div class="plan-section">
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <div class="mb-3">
                                                        <label class="form-label">Plan Name</label>
                                                        <input type="text" class="form-control" name="plan_name[]" value="<?= htmlspecialchars($plan['name'] ?? '') ?>">
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="mb-3">
                                                        <label class="form-label">Price (₱)</label>
                                                        <input type="number" step="0.01" class="form-control" name="plan_price[]" value="<?= htmlspecialchars($plan['price'] ?? '') ?>">
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="mb-3">
                                                        <label class="form-label">Period</label>
                                                        <input type="text" class="form-control" name="plan_period[]" value="<?= htmlspecialchars($plan['period'] ?? '') ?>">
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="mb-3">
                                                        <label class="form-label">Features</label>
                                                        <input type="text" class="form-control" name="plan_features[]" value="<?= htmlspecialchars(implode(', ', $plan['features'] ?? [])) ?>">
                                                    </div>
                                                </div>
                                            </div>
                                            <button type="button" class="btn btn-danger btn-sm remove-btn remove-plan">Remove Plan</button>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <button type="button" class="btn btn-secondary btn-sm" id="add-plan">Add Another Plan</button>
                        </div>
                        
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <?php echo isset($_GET['edit']) ? 'Update Gym' : 'Save Gym'; ?>
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
                var modal = new bootstrap.Modal(document.getElementById('gymModal'));
                modal.show();
                
                history.replaceState(null, null, window.location.pathname);
            }

            // Add trainer functionality
            document.getElementById('add-trainer').addEventListener('click', function() {
                const container = document.getElementById('trainers-container');
                const newSection = document.createElement('div');
                newSection.className = 'trainer-section';
                newSection.innerHTML = `
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Trainer Name</label>
                                <input type="text" class="form-control" name="trainer_name[]">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Specialty</label>
                                <input type="text" class="form-control" name="trainer_specialty[]">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Bio</label>
                                <input type="text" class="form-control" name="trainer_bio[]">
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-danger btn-sm remove-btn remove-trainer">Remove Trainer</button>
                `;
                container.appendChild(newSection);
            });

            // Add plan functionality
            document.getElementById('add-plan').addEventListener('click', function() {
                const container = document.getElementById('plans-container');
                const newSection = document.createElement('div');
                newSection.className = 'plan-section';
                newSection.innerHTML = `
                    <div class="row">
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Plan Name</label>
                                <input type="text" class="form-control" name="plan_name[]">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Price (₱)</label>
                                <input type="number" step="0.01" class="form-control" name="plan_price[]">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Period</label>
                                <input type="text" class="form-control" name="plan_period[]" placeholder="e.g., Monthly, Annual">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Features</label>
                                <input type="text" class="form-control" name="plan_features[]" placeholder="Separate with commas">
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-danger btn-sm remove-btn remove-plan">Remove Plan</button>
                `;
                container.appendChild(newSection);
            });

            // Remove functionality
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('remove-trainer')) {
                    e.target.closest('.trainer-section').remove();
                }
                if (e.target.classList.contains('remove-plan')) {
                    e.target.closest('.plan-section').remove();
                }
            });
        });
        
        function addRow() {
            var tbody = document.getElementById('transport-routes-tbody');
            var newRow = document.createElement('tr');
            newRow.innerHTML = `
                <td>
                    <select class="form-control" name="route_type[]">
                        <option value="">Select Type</option>
                        <option value="bus">Bus</option>
                        <option value="jeepney">Jeepney</option>
                        <option value="tricycle">Tricycle</option>
                        <option value="taxi">Taxi</option>
                    </select>
                </td>
                <td>
                    <input type="text" class="form-control" name="route_name[]" placeholder="e.g., Route 101, Gym Shuttle, Parking Area">
                </td>
                <td>
                    <input type="text" class="form-control" name="route_link[]" placeholder="https://maps.google.com/...">
                </td>
                <td>
                    <textarea class="form-control" name="route_description[]" rows="2" placeholder="e.g., Direct route to gym, ample parking for members, bike racks available"></textarea>
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