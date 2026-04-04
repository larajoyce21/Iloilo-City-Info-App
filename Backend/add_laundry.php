<?php
session_start();
include 'conn.php';

if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    
    $stmt = $conn->prepare("SELECT image, image_detail FROM laundry_services WHERE id=?");
    if (!$stmt) {
        die("Query preparation failed: " . $conn->error);
    }
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $laundry = $result->fetch_assoc();
    
    $stmt = $conn->prepare("DELETE FROM laundry_services WHERE id=?");
    $stmt->bind_param("i", $delete_id);
    
    if ($stmt->execute()) {
        if ($laundry) {
            if (!empty($laundry['image']) && file_exists($laundry['image'])) {
                unlink($laundry['image']);
            }
            
            if (!empty($laundry['image_detail'])) {
                $images = explode(',', $laundry['image_detail']);
                foreach ($images as $img) {
                    if (!empty($img) && file_exists($img)) {
                        unlink($img);
                    }
                }
            }
        }
        
        $_SESSION['message'] = "Laundry service deleted successfully";
    } else {
        $_SESSION['error'] = "Error deleting laundry service: " . $conn->error;
    }
    
    header("Location: add_laundry.php");
    exit();
}

if (isset($_GET['delete_image']) && isset($_GET['laundry_id'])) {
    $image_path = urldecode($_GET['delete_image']);
    $laundry_id = intval($_GET['laundry_id']);
    $is_card_image = isset($_GET['is_card_image']) ? true : false;
    
    if (file_exists($image_path)) {
        unlink($image_path);
    }
    
    if ($is_card_image) {
        $stmt = $conn->prepare("UPDATE laundry_services SET image='' WHERE id=?");
        $stmt->bind_param("i", $laundry_id);
        $stmt->execute();
    } else {
        $stmt = $conn->prepare("SELECT image_detail FROM laundry_services WHERE id=?");
        $stmt->bind_param("i", $laundry_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $laundry = $result->fetch_assoc();
        
        if ($laundry) {
            $images = explode(',', $laundry['image_detail']);
            $updated_images = array();
            foreach ($images as $img) {
                if (trim($img) != trim($image_path)) {
                    $updated_images[] = $img;
                }
            }
            $updated_images_str = implode(',', $updated_images);
            
            $stmt = $conn->prepare("UPDATE laundry_services SET image_detail=? WHERE id=?");
            $stmt->bind_param("si", $updated_images_str, $laundry_id);
            $stmt->execute();
        }
    }
    
    $_SESSION['message'] = "Image deleted successfully";
    header("Location: add_laundry.php?edit=" . $laundry_id);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'] ?? null;
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $website = trim($_POST['website'] ?? '');
    $maps_link = trim($_POST['maps_link'] ?? '');
    $maps_embed = htmlspecialchars(trim($_POST['map_embed'] ?? ''));
    $hours = trim($_POST['hours'] ?? '');
    $contact = trim($_POST['contact'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $services = trim($_POST['services'] ?? '');
    $pricing = trim($_POST['pricing'] ?? '');
    $pickup_delivery = trim($_POST['pickup_delivery'] ?? '');
    $payment_options = trim($_POST['payment_options'] ?? '');
    $turnaround_time = trim($_POST['turnaround_time'] ?? '');
    $special_services = trim($_POST['special_services'] ?? '');
    $social_media = trim($_POST['social_media'] ?? '');
    $directions = trim($_POST['directions'] ?? '');
    $nearby_places = trim($_POST['nearby_places'] ?? '');
    $details_link = trim($_POST['details_link'] ?? '');
    $specialties = trim($_POST['specialties'] ?? '');
    $established = trim($_POST['established'] ?? '');
    $eco_friendly = trim($_POST['eco_friendly'] ?? '');
    $machines_available = trim($_POST['machines_available'] ?? '');
    $staff_size = trim($_POST['staff_size'] ?? '');
    $service_area = trim($_POST['service_area'] ?? '');

    // Handle nearby places as JSON
    $nearby_places_array = array_map('trim', explode(',', $nearby_places));
    $nearby_places_json = json_encode(array_filter($nearby_places_array));

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
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

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
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            if (!empty($_POST['existing_image']) && file_exists($_POST['existing_image'])) {
                unlink($_POST['existing_image']);
            }
        } else {
            die("Error uploading the main image.");
        }
    }

    // Handle detail images
    $imagePaths = [];
    $existingImages = !empty($_POST['existing_image_detail']) ? explode(',', $_POST['existing_image_detail']) : [];
    
    if (!empty($_FILES['image_detail']['name'][0])) {
        $totalNew = count($_FILES['image_detail']['name']);
        $totalCombined = count($existingImages) + $totalNew;
        
        if ($totalCombined > 10) {
            die("Error: You can upload a maximum of 10 detail images total.");
        }
        
        foreach ($_FILES['image_detail']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['image_detail']['error'][$key] === UPLOAD_ERR_OK) {
                $fileName = basename($_FILES['image_detail']['name'][$key]);
                $targetPath = $target_dir . uniqid() . '_' . $fileName;
                move_uploaded_file($tmp_name, $targetPath);
                $imagePaths[] = $targetPath;
            }
        }
    }
    
    $allImages = array_merge($existingImages, $imagePaths);
    $target_file_detail = implode(',', $allImages);

    // Update or Insert
    if ($id) {
        $query = "UPDATE laundry_services SET 
            name=?, image=?, image_detail=?, description=?, location=?, 
            maps_link=?, maps_embed=?, website=?, hours=?, contact=?, 
            email=?, services=?, pricing=?, pickup_delivery=?, payment_options=?, 
            turnaround_time=?, special_services=?, nearby_places=?, 
            social_media=?, directions=?, details_link=?, 
            specialties=?, established=?, eco_friendly=?, 
            machines_available=?, staff_size=?, service_area=?, transportation_routes=?
            WHERE id=?";
        
        $stmt = $conn->prepare($query);
        $types = str_repeat('s', 28) . 'i'; // 28 string fields + 1 integer id
        $stmt->bind_param(
            $types,
            $name, $target_file, $target_file_detail, $description, $location,
            $maps_link, $maps_embed, $website, $hours, $contact,
            $email, $services, $pricing, $pickup_delivery, $payment_options,
            $turnaround_time, $special_services, $nearby_places_json,
            $social_media, $directions, $details_link,
            $specialties, $established, $eco_friendly,
            $machines_available, $staff_size, $service_area, $transportation_routes_json, $id
        );
    } else {
        $query = "INSERT INTO laundry_services (
            name, image, image_detail, description, location, 
            maps_link, maps_embed, website, hours, contact, 
            email, services, pricing, pickup_delivery, payment_options, 
            turnaround_time, special_services, nearby_places, 
            social_media, directions, details_link, 
            specialties, established, eco_friendly, 
            machines_available, staff_size, service_area, transportation_routes
        ) VALUES ( ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($query);
        $types = str_repeat('s', 28); // 28 string fields for insert
        $stmt->bind_param(
            $types,
            $name, $target_file, $target_file_detail, $description, $location,
            $maps_link, $maps_embed, $website, $hours, $contact,
            $email, $services, $pricing, $pickup_delivery, $payment_options,
            $turnaround_time, $special_services, $nearby_places_json,
            $social_media, $directions, $details_link,
            $specialties, $established, $eco_friendly,
            $machines_available, $staff_size, $service_area, $transportation_routes_json
        );
    }

    if ($stmt->execute()) {
        $_SESSION['message'] = "Laundry service " . ($id ? "updated" : "added") . " successfully";
    } else {
        $_SESSION['error'] = "Error saving laundry service: " . $conn->error;
    }
    
    header("Location: add_laundry.php");
    exit();
}

$query = "SELECT id, name, image, description, location, hours, contact, pricing, pickup_delivery FROM laundry_services";
$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Query preparation failed: " . $conn->error);
}
$stmt->execute();
$laundry_services = $stmt->get_result();

$laundryData = [
    'id' => '', 'name' => '', 'image' => '', 'image_detail' => '', 'description' => '', 
    'location' => '', 'maps_link' => '', 'maps_embed' => '', 'website' => '', 
    'hours' => '', 'contact' => '', 'email' => '', 'services' => '', 'pricing' => '', 'pickup_delivery' => '', 
    'payment_options' => '', 'turnaround_time' => '', 'special_services' => '', 
    'nearby_places' => '', 'social_media' => '', 'directions' => '', 'details_link' => '', 
    'specialties' => '', 'established' => '', 'eco_friendly' => '', 'machines_available' => '', 
    'staff_size' => '', 'service_area' => ''
];

$nearby_places_str = '';
$transportation_routes = array();

if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM laundry_services WHERE id=?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $laundryData = $result->fetch_assoc();
        
        if (!empty($laundryData['nearby_places'])) {
            $decoded_places = json_decode($laundryData['nearby_places'], true);
            if (is_array($decoded_places)) {
                $nearby_places_str = implode(', ', $decoded_places);
            }
        }
        
        if (!empty($laundryData['transportation_routes'])) {
            $decoded_routes = json_decode($laundryData['transportation_routes'], true);
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
    <title>Laundry Services Management</title>
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
        .modal {
            <?php echo isset($_GET['edit']) ? 'display: block; background: rgba(0,0,0,0.5);' : ''; ?>
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
            <h1 class="text-light">Laundry Services Management</h1>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#laundryModal">
                <i class="bi bi-plus-lg"></i> Add Laundry Service
            </button>
        </div>
        
        <?php if ($laundry_services->num_rows == 0): ?>
            <div class="alert alert-info">
                No laundry services found. Click "Add Laundry Service" to create one.
            </div>
        <?php else: ?>
            <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-4">
                <?php while ($laundry = $laundry_services->fetch_assoc()): ?>
                    <div class="col">
                        <div class="card h-100">
                            <img src="<?php echo htmlspecialchars($laundry['image']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($laundry['name']); ?>">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?php echo htmlspecialchars($laundry['name']); ?></h5>
                                <p class="card-text flex-grow-1"><?php echo substr(htmlspecialchars($laundry['description']), 0, 100) . '...'; ?></p>
                                <?php if (!empty($laundry['pricing'])): ?>
                                    <p class="text-primary fw-bold"><?php echo htmlspecialchars($laundry['pricing']); ?></p>
                                <?php endif; ?>
                                <div class="d-flex justify-content-between">
                                    <a href="add_laundry.php?edit=<?php echo $laundry['id']; ?>" class="btn btn-primary btn-sm">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    <a href="?delete=<?php echo $laundry['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this laundry service?')">
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
    <div class="modal fade <?php echo isset($_GET['edit']) ? 'show d-block' : ''; ?>" id="laundryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo isset($_GET['edit']) ? 'Edit Laundry Service' : 'Add Laundry Service'; ?></h5>
                    <button type="button" class="btn-close" onclick="window.location.href='add_laundry.php'"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($laundryData['id']); ?>">
                        
                        <div class="form-section">
                            <h5>Basic Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Name *</label>
                                        <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($laundryData['name']); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Description</label>
                                        <textarea class="form-control" name="description" rows="3"><?php echo htmlspecialchars($laundryData['description']); ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Specialties</label>
                                        <textarea class="form-control" name="specialties" rows="3"><?php echo htmlspecialchars($laundryData['specialties']); ?></textarea>
                                        <small class="text-muted">e.g., Dry Cleaning, Wash & Fold, Alterations</small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Services Offered (one per line)</label>
                                        <textarea class="form-control" name="services" rows="3"><?php echo htmlspecialchars($laundryData['services']); ?></textarea>
                                        <small class="text-muted">e.g., Laundry, Ironing, Stain Removal</small>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Operating Hours</label>
                                        <textarea class="form-control" name="hours" rows="3" placeholder="e.g., Monday-Friday: 8AM-6PM, Saturday: 9AM-4PM"><?php echo htmlspecialchars($laundryData['hours']); ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Contact Info</label>
                                        <input type="text" class="form-control" name="contact" value="<?php echo htmlspecialchars($laundryData['contact']); ?>" placeholder="Phone number">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Email Address</label>
                                        <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($laundryData['email'] ?? ''); ?>" placeholder="contact@laundry.com">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Pricing Information</label>
                                        <textarea class="form-control" name="pricing" rows="3" placeholder="e.g., Wash & Fold: ₱50/kg, Dry Cleaning: ₱100/item"><?php echo htmlspecialchars($laundryData['pricing']); ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h5>Service Details</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Pickup & Delivery</label>
                                        <input type="text" class="form-control" name="pickup_delivery" value="<?php echo htmlspecialchars($laundryData['pickup_delivery']); ?>" placeholder="e.g., Free delivery, ₱50 pickup fee">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Turnaround Time</label>
                                        <input type="text" class="form-control" name="turnaround_time" value="<?php echo htmlspecialchars($laundryData['turnaround_time']); ?>" placeholder="e.g., 24 hours, Same day service">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Payment Options</label>
                                        <input type="text" class="form-control" name="payment_options" value="<?php echo htmlspecialchars($laundryData['payment_options']); ?>" placeholder="e.g., Cash, GCash, Credit Card">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Special Services (one per line)</label>
                                        <textarea class="form-control" name="special_services" rows="3"><?php echo htmlspecialchars($laundryData['special_services']); ?></textarea>
                                        <small class="text-muted">e.g., Curtain cleaning, Bedding cleaning, Uniform cleaning</small>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Established Year</label>
                                        <input type="text" class="form-control" name="established" value="<?php echo htmlspecialchars($laundryData['established']); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Eco-Friendly Practices</label>
                                        <input type="text" class="form-control" name="eco_friendly" value="<?php echo htmlspecialchars($laundryData['eco_friendly']); ?>" placeholder="e.g., Biodegradable detergents, Water recycling">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Machines Available</label>
                                        <input type="text" class="form-control" name="machines_available" value="<?php echo htmlspecialchars($laundryData['machines_available']); ?>" placeholder="e.g., 10 washing machines, 5 dryers">
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Staff Size</label>
                                                <input type="text" class="form-control" name="staff_size" value="<?php echo htmlspecialchars($laundryData['staff_size']); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Service Area</label>
                                                <input type="text" class="form-control" name="service_area" value="<?php echo htmlspecialchars($laundryData['service_area']); ?>" placeholder="e.g., Within 5km radius">
                                            </div>
                                        </div>
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
                                        <input type="file" class="form-control" name="image">
                                        <?php if ($laundryData['image']) { ?>
                                            <div class="mt-2">
                                                <img src="<?php echo htmlspecialchars($laundryData['image']); ?>" class="img-thumbnail" width="100">
                                                <div class="form-check mt-1">
                                                    <input class="form-check-input" type="checkbox" name="delete_image" value="1" id="deleteImageCheck">
                                                    <label class="form-check-label" for="deleteImageCheck">Delete current image</label>
                                                </div>
                                            </div>
                                            <input type="hidden" name="existing_image" value="<?php echo htmlspecialchars($laundryData['image']); ?>">
                                        <?php } ?>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Detail Images (Max 10)</label>
                                        <input type="file" class="form-control" name="image_detail[]" multiple>
                                        
                                        <?php if (!empty($laundryData['image_detail'])) {
                                            $images = explode(',', $laundryData['image_detail']);
                                            echo '<div class="d-flex flex-wrap gap-2 mt-2">';
                                            foreach ($images as $img) {
                                                if (!empty($img)) { ?>
                                                    <div class="position-relative">
                                                        <img src="<?php echo htmlspecialchars($img); ?>" class="img-thumbnail" width="100">
                                                        <a href="?delete_image=<?php echo urlencode($img); ?>&laundry_id=<?php echo $laundryData['id']; ?>" 
                                                        class="position-absolute top-0 end-0 bg-danger text-white px-1" 
                                                        onclick="return confirm('Delete this image?')">×</a>
                                                    </div>
                                                <?php }
                                            }
                                            echo '</div>';
                                            ?>
                                            <input type="hidden" name="existing_image_detail" value="<?php echo htmlspecialchars($laundryData['image_detail']); ?>">
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
                                        <label class="form-label">Location/Address *</label>
                                        <input type="text" class="form-control" name="location" value="<?php echo htmlspecialchars($laundryData['location']); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Directions</label>
                                        <input type="text" class="form-control" name="directions" value="<?php echo htmlspecialchars($laundryData['directions']); ?>" placeholder="e.g., Near SM City, beside 7-Eleven">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Nearby Places (comma separated)</label>
                                        <input type="text" class="form-control" name="nearby_places" value="<?php echo htmlspecialchars($nearby_places_str); ?>" placeholder="e.g., Supermarket, Pharmacy, School">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Website</label>
                                        <input type="text" class="form-control" name="website" value="<?php echo htmlspecialchars($laundryData['website']); ?>">
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Google Maps Link</label>
                                                <input type="text" class="form-control" name="maps_link" value="<?php echo htmlspecialchars($laundryData['maps_link']); ?>" placeholder="https://maps.google.com/...">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Embed Map URL</label>
                                                <input type="text" class="form-control" name="map_embed" value="<?php echo htmlspecialchars($laundryData['maps_embed']); ?>" placeholder="https://maps.google.com/embed...">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Details Page Link</label>
                                        <input type="text" class="form-control" name="details_link" value="<?php echo htmlspecialchars($laundryData['details_link']); ?>" placeholder="e.g., laundry_details.php?id=1">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Social Media Links (one per line)</label>
                                        <textarea class="form-control" name="social_media" rows="3"><?php echo htmlspecialchars($laundryData['social_media']); ?></textarea>
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
                                                            <option value="delivery_van" <?php echo ($route['type'] == 'delivery_van') ? 'selected' : ''; ?>>Delivery Van</option>
                                                            <option value="jeepney" <?php echo ($route['type'] == 'jeepney') ? 'selected' : ''; ?>>Jeepney</option>
                                                            <option value="tricycle" <?php echo ($route['type'] == 'tricycle') ? 'selected' : ''; ?>>Tricycle</option>
                                                            <option value="motorcycle" <?php echo ($route['type'] == 'motorcycle') ? 'selected' : ''; ?>>Motorcycle Service</option>
                                                            <option value="bus" <?php echo ($route['type'] == 'bus') ? 'selected' : ''; ?>>Bus</option>
                                                            <option value="taxi" <?php echo ($route['type'] == 'taxi') ? 'selected' : ''; ?>>Taxi</option>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control" name="route_name[]" value="<?php echo htmlspecialchars($route['name']); ?>" placeholder="e.g., Delivery Van, Route 10">
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control" name="route_link[]" value="<?php echo htmlspecialchars($route['link'] ?? ''); ?>" placeholder="https://maps.google.com/...">
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control" name="route_description[]" value="<?php echo htmlspecialchars($route['description'] ?? ''); ?>" placeholder="e.g., Free pickup service, Delivery hours: 9AM-5PM">
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
                                                        <option value="delivery_van">Delivery Van</option>
                                                        <option value="jeepney">Jeepney</option>
                                                        <option value="tricycle">Tricycle</option>
                                                        <option value="motorcycle">Motorcycle Service</option>
                                                        <option value="bus">Bus</option>
                                                        <option value="taxi">Taxi</option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control" name="route_name[]" placeholder="e.g., Delivery Van, Route 10">
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control" name="route_link[]" placeholder="https://maps.google.com/...">
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control" name="route_description[]" placeholder="e.g., Free pickup service, Delivery hours: 9AM-5PM">
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
                        
                        <div class="d-flex justify-content-between mt-3">
                            <button type="submit" class="btn btn-success">
                                <?php echo isset($_GET['edit']) ? 'Update Laundry Service' : 'Save Laundry Service'; ?>
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="window.location.href='add_laundry.php'">Cancel</button>
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
                var myModal = new bootstrap.Modal(document.getElementById('laundryModal'));
                myModal.show();
            }
        });

        function addTransportRow() {
            const tableBody = document.getElementById('transportRoutesTableBody');
            const newRow = document.createElement('tr');
            
            newRow.innerHTML = `
                <td>
                    <select class="form-control" name="route_type[]">
                        <option value="delivery_van">Delivery Van</option>
                        <option value="jeepney">Jeepney</option>
                        <option value="tricycle">Tricycle</option>
                        <option value="motorcycle">Motorcycle Service</option>
                        <option value="bus">Bus</option>
                        <option value="taxi">Taxi</option>
                    </select>
                </td>
                <td>
                    <input type="text" class="form-control" name="route_name[]" placeholder="e.g., Delivery Van, Route 10">
                </td>
                <td>
                    <input type="text" class="form-control" name="route_link[]" placeholder="https://maps.google.com/...">
                </td>
                <td>
                    <input type="text" class="form-control" name="route_description[]" placeholder="e.g., Free pickup service, Delivery hours: 9AM-5PM">
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