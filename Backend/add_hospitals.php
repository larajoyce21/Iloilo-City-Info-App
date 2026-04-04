<?php
include 'conn.php';
session_start();

if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    
    $stmt = $conn->prepare("SELECT image, image_detail FROM hospitals WHERE id=?");
    if (!$stmt) {
        die("Query preparation failed: " . $conn->error);
    }
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if (!empty($row['image']) && file_exists($row['image'])) {
        @unlink($row['image']);
    }
    
    if (!empty($row['image_detail'])) {
        $images = explode(',', $row['image_detail']);
        foreach ($images as $image) {
            if (!empty($image) && file_exists($image)) {
                @unlink($image);
            }
        }
    }
    
    $stmt = $conn->prepare("DELETE FROM hospitals WHERE id=?");
    if (!$stmt) {
        die("Query preparation failed: " . $conn->error);
    }
    $stmt->bind_param("i", $delete_id);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Hospital deleted successfully";
    } else {
        $_SESSION['error'] = "Error deleting hospital: " . $conn->error;
    }
    
    header("Location: add_hospitals.php");
    exit();
}

if (isset($_GET['delete_image']) && isset($_GET['hospital_id'])) {
    $image_to_delete = $_GET['delete_image'];
    $hospital_id = (int)$_GET['hospital_id'];
    $is_card_image = isset($_GET['is_card_image']) ? true : false;
    
    if ($is_card_image) {
        $stmt = $conn->prepare("SELECT image FROM hospitals WHERE id=?");
        $stmt->bind_param("i", $hospital_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        if (!empty($row['image']) && file_exists($row['image'])) {
            unlink($row['image']);
        }
        
        $stmt = $conn->prepare("UPDATE hospitals SET image='' WHERE id=?");
        $stmt->bind_param("i", $hospital_id);
        $stmt->execute();
        
        $_SESSION['message'] = "Main image deleted successfully";
        header("Location: add_hospitals.php?edit=" . $hospital_id);
        exit();
    } else {
        $stmt = $conn->prepare("SELECT image_detail FROM hospitals WHERE id = ?");
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("i", $hospital_id);
        if (!$stmt->execute()) {
            die("Execute failed: " . $stmt->error);
        }
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        $images = explode(',', $row['image_detail']);
        $updated_images = array_filter(array_diff($images, [$image_to_delete]));
        
        if (file_exists($image_to_delete)) {
            unlink($image_to_delete);
        }
        
        $stmt = $conn->prepare("UPDATE hospitals SET image_detail = ? WHERE id = ?");
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }
        $updated_images_str = implode(',', $updated_images);
        $stmt->bind_param("si", $updated_images_str, $hospital_id);
        if (!$stmt->execute()) {
            die("Execute failed: " . $stmt->error);
        }
        
        $_SESSION['message'] = "Detail image deleted successfully";
        header("Location: add_hospitals.php?edit=" . $hospital_id);
        exit();
    }
}

$query = "SELECT id, name, image, description, location, email, services, doctors, maps_link, maps_embed, website, directions_link, hours, contact, phone, nearby_places, social_media, hotlines, type, details_link, beds, rooms FROM hospitals";
$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Query preparation failed: " . $conn->error);
}
$stmt->execute();
$hospitals = $stmt->get_result();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'] ?? null;
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $hours = trim($_POST['hours'] ?? '');
    $contact = trim($_POST['contact'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $services = trim($_POST['services'] ?? '');
    $doctors = trim($_POST['doctors'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $maps_link = trim($_POST['maps_link'] ?? '');
    $website = trim($_POST['website'] ?? '');
    $directions_link = trim($_POST['directions_link'] ?? '');
    $mapEmbedURL = htmlspecialchars(trim($_POST['map_embed'] ?? ''));
    $phone = trim($_POST['phone'] ?? '');
    $hotlines = trim($_POST['hotlines'] ?? '');
    $social_media = trim($_POST['social_media'] ?? '');
    $type = trim($_POST['type'] ?? '');
    $details_link = trim($_POST['details_link'] ?? '');
    $beds = trim($_POST['beds'] ?? '');
    $rooms = trim($_POST['rooms'] ?? '');

    // Handle nearby places as JSON
    $nearby_places = $_POST['nearby_places'] ?? '';
    $nearby_places_array = array_map('trim', explode(',', $nearby_places));
    $nearby_places_json = json_encode(array_filter($nearby_places_array));

    // Handle transportation routes
    $transportation_routes = array();
    if (isset($_POST['route_type']) && is_array($_POST['route_type'])) {
        foreach ($_POST['route_type'] as $index => $type_route) {
            if (!empty($type_route) && !empty($_POST['route_name'][$index])) {
                $transportation_routes[] = array(
                    'type' => $type_route,
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
    
    // Handle main image deletion
    if (isset($_POST['delete_image']) && $_POST['delete_image'] == '1') {
        if (!empty($target_file) && file_exists($target_file)) {
            unlink($target_file);
        }
        $target_file = '';
    }
    
    // Handle new main image upload
    if (!empty($_FILES['image']['name'])) {
        $file_name = basename($_FILES["image"]["name"]);
        $target_file = $target_dir . uniqid() . '_' . $file_name;
        $imageFileType = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if (in_array($imageFileType, $allowed_types)) {
            if (!move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                die("Error uploading the main image.");
            }
            if (!empty($_POST['existing_image']) && file_exists($_POST['existing_image'])) {
                unlink($_POST['existing_image']);
            }
        } else {
            die("Error: Only JPG, JPEG, PNG, and GIF files are allowed.");
        }
    }

    // Handle detail images
    $existingImages = [];
    if (!empty($_POST['existing_image_detail'])) {
        $existingImages = explode(',', $_POST['existing_image_detail']);
        $existingImages = array_filter($existingImages);
    }
    
    $imagePaths = [];
    
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
            
            $file_name = basename($_FILES['image_detail']['name'][$i]);
            $tmp_name = $_FILES['image_detail']['tmp_name'][$i];
            $imageFileType = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            if (in_array($imageFileType, $allowed_types)) {
                $unique_name = $target_dir . uniqid() . '_' . $file_name;
                if (move_uploaded_file($tmp_name, $unique_name)) {
                    $imagePaths[] = $unique_name;
                }
            } else {
                die("Error: Only JPG, JPEG, PNG, and GIF files are allowed.");
            }
        }
    }
    
    $allImages = array_merge($existingImages, $imagePaths);
    $target_file_detail = implode(',', $allImages);
    
    // Update or Insert
    if ($id) {
        $query = "UPDATE hospitals SET name=?, image=?, image_detail=?, description=?, location=?, email=?, services=?, doctors=?, maps_link=?, maps_embed=?, website=?, directions_link=?, hours=?, contact=?, phone=?, nearby_places=?, social_media=?, hotlines=?, type=?, details_link=?, beds=?, rooms=?, transportation_routes=? WHERE id=?";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            die("Query preparation failed: " . $conn->error);
        }
        $stmt->bind_param("sssssssssssssssssssssssi", $name, $target_file, $target_file_detail, $description, $location, $email, $services, $doctors, $maps_link, $mapEmbedURL, $website, $directions_link, $hours, $contact, $phone, $nearby_places_json, $social_media, $hotlines, $type, $details_link, $beds, $rooms, $transportation_routes_json, $id);
    } else {
        $query = "INSERT INTO hospitals (name, image, image_detail, description, location, email, services, doctors, maps_link, maps_embed, website, directions_link, hours, contact, phone, nearby_places, social_media, hotlines, type, details_link, beds, rooms, transportation_routes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            die("Query preparation failed: " . $conn->error);
        }
        $stmt->bind_param("sssssssssssssssssssssss", $name, $target_file, $target_file_detail, $description, $location, $email, $services, $doctors, $maps_link, $mapEmbedURL, $website, $directions_link, $hours, $contact, $phone, $nearby_places_json, $social_media, $hotlines, $type, $details_link, $beds, $rooms, $transportation_routes_json);
    }
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Hospital " . ($id ? "updated" : "added") . " successfully";
    } else {
        $_SESSION['error'] = "Error saving hospital: " . $stmt->error;
    }
    
    header("Location: add_hospitals.php");
    exit();
}

// Initialize variables for edit mode
$nearby_places_str = '';
$transportation_routes = array();

if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT nearby_places, transportation_routes FROM hospitals WHERE id=?");
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
    <title>Hospitals Management</title>
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
            <h1 class="text-light">Hospitals Management</h1>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#hospitalModal">
                <i class="bi bi-plus-lg"></i> Add Hospital
            </button>
        </div>
        
        <?php if ($hospitals->num_rows == 0): ?>
            <div class="alert alert-info">
                No hospitals found. Click "Add Hospital" to create one.
            </div>
        <?php else: ?>
            <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-4">
                <?php while ($hospital = $hospitals->fetch_assoc()): ?>
                    <div class="col">
                        <div class="card h-100">
                            <img src="<?php echo htmlspecialchars($hospital['image']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($hospital['name']); ?>">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?php echo htmlspecialchars($hospital['name']); ?></h5>
                                <p class="card-text flex-grow-1"><?php echo substr(htmlspecialchars($hospital['description']), 0, 100) . '...'; ?></p>
                                <?php if (!empty($hospital['type'])): ?>
                                    <p class="text-primary fw-bold"><?php echo htmlspecialchars($hospital['type']); ?></p>
                                <?php endif; ?>
                                <div class="d-flex justify-content-between">
                                    <a href="add_hospitals.php?edit=<?php echo $hospital['id']; ?>" class="btn btn-primary btn-sm">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    <a href="?delete=<?php echo $hospital['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this hospital?')">
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

    <!-- Modal -->
    <div class="modal fade <?php echo isset($_GET['edit']) ? 'show d-block' : ''; ?>" id="hospitalModal" tabindex="-1" aria-labelledby="hospitalModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="hospitalModalLabel"><?php echo isset($_GET['edit']) ? 'Edit Hospital' : 'Add Hospital'; ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php 
                        $hospitalData = [
                            'id' => '', 
                            'name' => '', 
                            'description' => '', 
                            'location' => '', 
                            'maps_link' => '', 
                            'website' => '', 
                            'image' => '',
                            'maps_embed' => '', 
                            'directions_link' => '',
                            'hours' => '', 
                            'contact' => '',  
                            'phone' => '',
                            'hotlines' => '', 
                            'nearby_places' => '',
                            'social_media' => '',
                            'email' => '',
                            'services' => '',
                            'doctors' => '',
                            'image_detail' => '',
                            'type' => '',
                            'details_link' => '',
                            'beds' => '',
                            'rooms' => ''
                        ];
                        
                        if (isset($_GET['edit'])) {
                            $edit_id = (int)$_GET['edit'];
                            $stmt = $conn->prepare("SELECT * FROM hospitals WHERE id = ?");
                            $stmt->bind_param("i", $edit_id);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            if ($result->num_rows > 0) {
                                $hospitalData = $result->fetch_assoc();
                            }
                        }
                    ?>
                    <form method="POST" action="add_hospitals.php" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($hospitalData['id']); ?>">

                        <div class="form-section">
                            <h5>Basic Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Name *</label>
                                        <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($hospitalData['name']); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Details Page Link</label>
                                        <input type="text" class="form-control" name="details_link" value="<?php echo htmlspecialchars($hospitalData['details_link']); ?>" placeholder="e.g., hospital_details.php?id=1">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Type of Hospital</label>
                                        <select class="form-control" name="type">
                                            <option value="">Select Hospital Type</option>
                                            <option value="Government" <?php echo ($hospitalData['type'] == 'Government') ? 'selected' : ''; ?>>Government</option>
                                            <option value="Private" <?php echo ($hospitalData['type'] == 'Private') ? 'selected' : ''; ?>>Private</option>
                                            <option value="Specialty" <?php echo ($hospitalData['type'] == 'Specialty') ? 'selected' : ''; ?>>Specialty</option>
                                            <option value="Teaching" <?php echo ($hospitalData['type'] == 'Teaching') ? 'selected' : ''; ?>>Teaching</option>
                                            <option value="General" <?php echo ($hospitalData['type'] == 'General') ? 'selected' : ''; ?>>General</option>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Number of Beds</label>
                                        <input type="number" class="form-control" name="beds" value="<?php echo htmlspecialchars($hospitalData['beds'] ?? ''); ?>">
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Contact Number</label>
                                        <input type="text" class="form-control" name="contact" value="<?php echo htmlspecialchars($hospitalData['contact']); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Emergency Hotlines</label>
                                        <input type="text" class="form-control" name="hotlines" value="<?php echo htmlspecialchars($hospitalData['hotlines']); ?>" placeholder="e.g., 911, 117">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Email Address</label>
                                        <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($hospitalData['email']); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Room Information</label>
                                        <textarea class="form-control" name="rooms" rows="3"><?php echo htmlspecialchars($hospitalData['rooms'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Description *</label>
                                <textarea class="form-control" name="description" rows="3" required><?php echo htmlspecialchars($hospitalData['description']); ?></textarea>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h5>Medical Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Services Offered</label>
                                        <textarea class="form-control" name="services" rows="4"><?php echo htmlspecialchars($hospitalData['services'] ?? ''); ?></textarea>
                                        <small class="text-muted">Separate services with commas</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Doctors / Specialists</label>
                                        <textarea class="form-control" name="doctors" rows="4"><?php echo htmlspecialchars($hospitalData['doctors'] ?? ''); ?></textarea>
                                        <small class="text-muted">Separate doctors with commas</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h5>Schedule & Contact</h5>
                            <div class="mb-3">
                                <label class="form-label">Operating Hours</label>
                                <textarea class="form-control" name="hours" rows="5" placeholder="e.g., Emergency: 24/7, OPD: 8AM-5PM"><?php echo htmlspecialchars($hospitalData['hours']); ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Phone Numbers</label>
                                <textarea class="form-control" name="phone" rows="3"><?php echo htmlspecialchars($hospitalData['phone'] ?? ''); ?></textarea>
                                <small class="text-muted">Enter one phone number per line</small>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h5>Images</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Upload Card Image:</label>
                                        <input type="file" class="form-control" name="image">
                                        
                                        <?php if (!empty($hospitalData['image'])) { ?>
                                            <div class="mt-2">
                                                <img src="<?php echo htmlspecialchars($hospitalData['image']); ?>" class="img-thumbnail" width="100">
                                                <div class="form-check mt-1">
                                                    <input class="form-check-input" type="checkbox" name="delete_image" value="1" id="deleteImageCheck">
                                                    <label class="form-check-label" for="deleteImageCheck">Delete current image</label>
                                                </div>
                                            </div>
                                            <input type="hidden" name="existing_image" value="<?php echo htmlspecialchars($hospitalData['image']); ?>">
                                        <?php } ?>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Upload Detail Images (Max 10):</label>
                                        <input type="file" class="form-control" name="image_detail[]" multiple accept="image/*">
                                        <?php 
                                        if (!empty($hospitalData['image_detail'])) {
                                            $detailImages = explode(',', $hospitalData['image_detail']);
                                            echo '<div class="d-flex flex-wrap mt-2">';
                                            foreach ($detailImages as $img) {
                                                if (!empty($img)) {
                                                    echo '<div class="position-relative me-2 mb-2">';
                                                    echo '<img src="' . htmlspecialchars($img) . '" class="img-thumbnail" width="100">';
                                                    echo '<a href="?delete_image=' . urlencode($img) . '&hospital_id=' . $hospitalData['id'] . '" 
                                                            class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" 
                                                            onclick="return confirm(\'Delete this image?\')">×</a>';
                                                    echo '</div>';
                                                }
                                            }
                                            echo '</div>';
                                            echo '<input type="hidden" name="existing_image_detail" value="' . htmlspecialchars($hospitalData['image_detail']) . '">';
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h5>Location Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Address *</label>
                                        <input type="text" class="form-control" name="location" value="<?php echo htmlspecialchars($hospitalData['location']); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Nearby Places</label>
                                        <input name="nearby_places" class="form-control" value="<?php echo htmlspecialchars($nearby_places_str); ?>" placeholder="Add nearby places separated by commas">
                                        <small class="text-muted">Example: Pharmacy, Mall, Restaurant</small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Website URL</label>
                                        <input type="url" class="form-control" name="website" value="<?php echo htmlspecialchars($hospitalData['website']); ?>">
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Google Maps Directions Link</label>
                                        <input type="url" class="form-control" name="directions_link" value="<?php echo htmlspecialchars($hospitalData['directions_link']); ?>" placeholder="https://maps.google.com/...">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Google Map Link</label>
                                        <input type="url" class="form-control" name="maps_link" value="<?php echo htmlspecialchars($hospitalData['maps_link']); ?>" placeholder="https://maps.google.com/...">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Embed Google Map URL</label>
                                        <input type="url" class="form-control" name="map_embed" value="<?php echo htmlspecialchars($hospitalData['maps_embed']); ?>" placeholder="https://maps.google.com/embed...">
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
                                            <th>Route Name/Number</th>
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
                                                            <option value="jeepney" <?php echo ($route['type'] == 'jeepney') ? 'selected' : ''; ?>>Jeepney</option>
                                                            <option value="bus" <?php echo ($route['type'] == 'bus') ? 'selected' : ''; ?>>Bus</option>
                                                            <option value="taxi" <?php echo ($route['type'] == 'taxi') ? 'selected' : ''; ?>>Taxi</option>
                                                            <option value="tricycle" <?php echo ($route['type'] == 'tricycle') ? 'selected' : ''; ?>>Tricycle</option>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control" name="route_name[]" value="<?php echo htmlspecialchars($route['name']); ?>" placeholder="e.g., Hospital Shuttle, Route 10">
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control" name="route_link[]" value="<?php echo htmlspecialchars($route['link'] ?? ''); ?>" placeholder="https://maps.google.com/...">
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control" name="route_description[]" value="<?php echo htmlspecialchars($route['description'] ?? ''); ?>" placeholder="e.g., Free shuttle service, Stops at main entrance">
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
                                                        <option value="jeepney">Jeepney</option>
                                                        <option value="bus">Bus</option>
                                                        <option value="taxi">Taxi</option>
                                                        <option value="tricycle">Tricycle</option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control" name="route_name[]" placeholder="e.g., Hospital Shuttle, Route 10">
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control" name="route_link[]" placeholder="https://maps.google.com/...">
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control" name="route_description[]" placeholder="e.g., Free shuttle service, Stops at main entrance">
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
                        
                        <div class="form-section">
                            <h5>Social Media</h5>
                            <div class="mb-3">
                                <label class="form-label">Social Media Links</label>
                                <textarea class="form-control" name="social_media" rows="5" placeholder="Enter one URL per line, e.g. https://facebook.com/hospital"><?php echo isset($hospitalData['social_media']) ? htmlspecialchars($hospitalData['social_media']) : ''; ?></textarea>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <?php echo isset($_GET['edit']) ? 'Update Hospital' : 'Save Hospital'; ?>
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
                var modal = new bootstrap.Modal(document.getElementById('hospitalModal'));
                modal.show();
                
                // Remove edit parameter from URL
                history.replaceState(null, null, window.location.pathname);
            }
        });

        function addTransportRow() {
            const tableBody = document.getElementById('transportRoutesTableBody');
            const newRow = document.createElement('tr');
            
            newRow.innerHTML = `
                <td>
                    <select class="form-control" name="route_type[]">
                        <option value="jeepney">Jeepney</option>
                        <option value="bus">Bus</option>
                        <option value="taxi">Taxi</option>
                        <option value="tricycle">Tricycle</option>
                    </select>
                </td>
                <td>
                    <input type="text" class="form-control" name="route_name[]" placeholder="e.g., Hospital Shuttle, Route 10">
                </td>
                <td>
                    <input type="text" class="form-control" name="route_link[]" placeholder="https://maps.google.com/...">
                </td>
                <td>
                    <input type="text" class="form-control" name="route_description[]" placeholder="e.g., Free shuttle service, Stops at main entrance">
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
            if (document.querySelectorAll('#transportRoutesTableBody tr').length > 1) {
                row.remove();
            } else {
                // If it's the last row, just clear the inputs
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