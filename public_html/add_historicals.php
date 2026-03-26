<?php
include 'conn.php';
session_start();

$allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
$target_dir = 'img/historicals/';

if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    
    // Get images before deleting
    $stmt = $conn->prepare("SELECT image, image_detail FROM historicals WHERE id=?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $historical = $result->fetch_assoc();
    
    // Delete from database
    $stmt = $conn->prepare("DELETE FROM historicals WHERE id=?");
    $stmt->bind_param("i", $delete_id);
    
    if ($stmt->execute()) {
        // Delete images from server
        if ($historical) {
            if (!empty($historical['image']) && file_exists($historical['image'])) {
                unlink($historical['image']);
            }
            
            if (!empty($historical['image_detail'])) {
                $images = explode(',', $historical['image_detail']);
                foreach ($images as $img) {
                    if (!empty($img) && file_exists($img)) {
                        unlink($img);
                    }
                }
            }
        }
        
        $_SESSION['message'] = "Historical site deleted successfully";
    } else {
        $_SESSION['error'] = "Error deleting historical site: " . $conn->error;
    }
    
    header("Location: add_historicals.php");
    exit();
}

if (isset($_GET['delete_image']) && isset($_GET['historical_id'])) {
    $image_path = urldecode($_GET['delete_image']);
    $historical_id = intval($_GET['historical_id']);
    $is_card_image = isset($_GET['is_card_image']) ? true : false;
    
    if (file_exists($image_path)) {
        unlink($image_path);
    }
    
    if ($is_card_image) {
        $stmt = $conn->prepare("UPDATE historicals SET image='' WHERE id=?");
        $stmt->bind_param("i", $historical_id);
        $stmt->execute();
    } else {
        $stmt = $conn->prepare("SELECT image_detail FROM historicals WHERE id=?");
        $stmt->bind_param("i", $historical_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $historical = $result->fetch_assoc();
        
        if ($historical) {
            $images = explode(',', $historical['image_detail']);
            $updated_images = array();
            foreach ($images as $img) {
                if (trim($img) != trim($image_path)) {
                    $updated_images[] = $img;
                }
            }
            $updated_images_str = implode(',', $updated_images);
            
            $stmt = $conn->prepare("UPDATE historicals SET image_detail=? WHERE id=?");
            $stmt->bind_param("si", $updated_images_str, $historical_id);
            $stmt->execute();
        }
    }
    
    $_SESSION['message'] = "Image deleted successfully";
    header("Location: add_historicals.php?edit=" . $historical_id);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $history = trim($_POST['history'] ?? '');
    $architecture = trim($_POST['architecture'] ?? '');
    $significance = trim($_POST['significance'] ?? '');
    $fun_fact = trim($_POST['fun_fact'] ?? '');
    $source = trim($_POST['source'] ?? '');
    $fee = trim($_POST['fee'] ?? '');
    $hours = trim($_POST['hours'] ?? '');
    $contact = trim($_POST['contact'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $location = trim($_POST['location']);
    $directions_link = trim($_POST['directions_link']);
    $website = trim($_POST['website']);
    $maps_link = trim($_POST['maps_link']);
    $mapEmbedURL = htmlspecialchars(trim($_POST['map_embed'] ?? ''));
    $social_media = trim($_POST['social_media'] ?? '');
    $year_built = trim($_POST['year_built'] ?? '');
    $architectural_style = trim($_POST['architectural_style'] ?? '');
    $mass_schedule = trim($_POST['mass_schedule'] ?? '');
    $status = trim($_POST['status'] ?? 'National Historical Landmark');
    $patron_saint = trim($_POST['patron_saint'] ?? 'Saint Anne');
    $details_link = trim($_POST['details_link'] ?? '');

    // Handle nearby places as JSON
    $nearby_places = $_POST['nearby_places'] ?? '';
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

    $target_file = isset($_POST['existing_image']) ? $_POST['existing_image'] : '';
    
    if (isset($_POST['delete_image']) && $_POST['delete_image'] == '1') {
        if (!empty($target_file) && file_exists($target_file)) {
            unlink($target_file);
        }
        $target_file = '';
    }

    if (!empty($_FILES['image']['name'])) {
        $file_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $new_filename = $target_dir . uniqid('card_') . '.' . $file_ext;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $new_filename)) {
            if (!empty($_POST['existing_image']) && file_exists($_POST['existing_image'])) {
                unlink($_POST['existing_image']);
            }
            $target_file = $new_filename;
        }
    }

    $imagePaths = [];
    $existingImages = [];
    
    if (!empty($_POST['existing_image_detail'])) {
        $existingImages = array_filter(explode(',', $_POST['existing_image_detail']));
    }

    if (!empty($_FILES['image_detail']['name'][0])) {
        for ($i = 0; $i < count($_FILES['image_detail']['name']); $i++) {
            if ($_FILES['image_detail']['error'][$i] === UPLOAD_ERR_OK) {
                $file_ext = strtolower(pathinfo($_FILES['image_detail']['name'][$i], PATHINFO_EXTENSION));
                $uniqueName = uniqid('img_', true) . '.' . $file_ext;
                $targetPath = $target_dir . $uniqueName;
                
                if (move_uploaded_file($_FILES['image_detail']['tmp_name'][$i], $targetPath)) {
                    $imagePaths[] = $targetPath;
                }
            }
        }
    }

    $allImages = array_merge($existingImages, $imagePaths);
    $target_file_detail = implode(',', array_filter($allImages));

    // Prepare SQL statement
    if ($id > 0) {
        $query = "UPDATE historicals SET 
            name=?, image=?, image_detail=?, description=?, history=?, 
            architecture=?, significance=?, fun_fact=?, source=?, fee=?, 
            location=?, maps_embed=?, website=?, maps_link=?, directions_link=?, 
            hours=?, contact=?, email=?, nearby_places=?, social_media=?, 
            year_built=?, architectural_style=?, mass_schedule=?, status=?, 
            patron_saint=?, details_link=?, transportation_routes=?
            WHERE id=?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssssssssssssssssssssssssssi", 
            $name, $target_file, $target_file_detail, $description, $history, 
            $architecture, $significance, $fun_fact, $source, $fee, 
            $location, $mapEmbedURL, $website, $maps_link, $directions_link, 
            $hours, $contact, $email, $nearby_places_json, $social_media, 
            $year_built, $architectural_style, $mass_schedule, $status, 
            $patron_saint, $details_link, $transportation_routes_json, $id);
    } else {
        $query = "INSERT INTO historicals (
            name, image, image_detail, description, history, 
            architecture, significance, fun_fact, source, fee, 
            location, maps_embed, website, maps_link, directions_link, 
            hours, contact, email, nearby_places, social_media, 
            year_built, architectural_style, mass_schedule, status, 
            patron_saint, details_link, transportation_routes
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssssssssssssssssssssssss", 
            $name, $target_file, $target_file_detail, $description, $history, 
            $architecture, $significance, $fun_fact, $source, $fee, 
            $location, $mapEmbedURL, $website, $maps_link, $directions_link, 
            $hours, $contact, $email, $nearby_places_json, $social_media, 
            $year_built, $architectural_style, $mass_schedule, $status, 
            $patron_saint, $details_link, $transportation_routes_json);
    }

    if ($stmt->execute()) {
        $_SESSION['message'] = "Historical site " . ($id ? "updated" : "added") . " successfully";
    } else {
        $_SESSION['error'] = "Error saving historical site: " . $conn->error;
    }
    
    header("Location: add_historicals.php");
    exit();
}

$historicals = $conn->query("SELECT * FROM historicals ORDER BY name ASC");

// Initialize variables for edit mode
$historicalData = [
    'id' => '', 'name' => '', 'description' => '', 'history' => '',
    'architecture' => '', 'significance' => '', 'fun_fact' => '',
    'source' => '', 'fee' => '', 'location' => '', 'website' => '', 
    'maps_embed' => '', 'maps_link' => '', 'directions_link' => '', 
    'hours' => '', 'contact' => '', 'email' => '', 'image' => '',
    'image_detail' => '', 'nearby_places' => '[]', 'social_media' => '',
    'year_built' => '', 'architectural_style' => '', 'mass_schedule' => '',
    'status' => '', 'patron_saint' => '', 'details_link' => ''
];

$nearby_places_str = '';
$transportation_routes = array();

if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM historicals WHERE id=?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $historicalData = $result->fetch_assoc();
        
        // Set defaults for empty fields
        $historicalData['mass_schedule'] = $historicalData['mass_schedule'] ?? '';
        $historicalData['status'] = $historicalData['status'] ?? '';
        $historicalData['patron_saint'] = $historicalData['patron_saint'] ?? '';
        $historicalData['details_link'] = $historicalData['details_link'] ?? '';
        $historicalData['transportation_routes'] = $historicalData['transportation_routes'] ?? '[]';
        
        // Decode nearby places
        if (!empty($historicalData['nearby_places'])) {
            $decoded_places = json_decode($historicalData['nearby_places'], true);
            if (is_array($decoded_places)) {
                $nearby_places_str = implode(', ', $decoded_places);
            }
        }
        
        // Decode transportation routes
        if (!empty($historicalData['transportation_routes'])) {
            $decoded_routes = json_decode($historicalData['transportation_routes'], true);
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
    <title>Historicals Management</title>
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
            <h1 class="text-light">Historical Sites Management</h1>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#historicalModal">
                <i class="bi bi-plus-lg"></i> Add Historical Site
            </button>
        </div>
        
        <?php if ($historicals->num_rows == 0): ?>
            <div class="alert alert-info">
                No historical sites found. Click "Add Historical Site" to create one.
            </div>
        <?php else: ?>
            <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-4">
                <?php while ($historical = $historicals->fetch_assoc()): ?>
                    <div class="col">
                        <div class="card h-100">
                            <img src="<?php echo htmlspecialchars($historical['image']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($historical['name']); ?>">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?php echo htmlspecialchars($historical['name']); ?></h5>
                                <p class="card-text flex-grow-1"><?php echo substr(htmlspecialchars($historical['description']), 0, 100) . '...'; ?></p>
                                <?php if (!empty($historical['year_built'])): ?>
                                    <p class="text-primary fw-bold">Built: <?php echo htmlspecialchars($historical['year_built']); ?></p>
                                <?php endif; ?>
                                <div class="d-flex justify-content-between">
                                    <a href="add_historicals.php?edit=<?php echo $historical['id']; ?>" class="btn btn-primary btn-sm">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    <a href="?delete=<?php echo $historical['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this historical site?')">
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
    <div class="modal fade <?php echo isset($_GET['edit']) ? 'show d-block' : ''; ?>" id="historicalModal" tabindex="-1" aria-labelledby="historicalModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="historicalModalLabel"><?php echo isset($_GET['edit']) ? 'Edit Historical Site' : 'Add Historical Site'; ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="add_historicals.php" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($historicalData['id']); ?>">
                        
                        <div class="form-section">
                            <h5>Basic Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Name *</label>
                                        <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($historicalData['name']); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Details Page Link</label>
                                        <input type="text" class="form-control" name="details_link" value="<?php echo htmlspecialchars($historicalData['details_link']); ?>" placeholder="e.g., historical_details.php?id=1">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Year Built</label>
                                        <input type="text" class="form-control" name="year_built" value="<?php echo htmlspecialchars($historicalData['year_built']); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Architectural Style</label>
                                        <input type="text" class="form-control" name="architectural_style" value="<?php echo htmlspecialchars($historicalData['architectural_style']); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Status</label>
                                        <input type="text" class="form-control" name="status" value="<?php echo htmlspecialchars($historicalData['status']); ?>" placeholder="e.g., National Historical Landmark">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Patron Saint</label>
                                        <input type="text" class="form-control" name="patron_saint" value="<?php echo htmlspecialchars($historicalData['patron_saint']); ?>">
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Entrance Fee</label>
                                        <input type="text" class="form-control" name="fee" value="<?php echo htmlspecialchars($historicalData['fee']); ?>" placeholder="e.g., ₱50, Free for students">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Contact Information</label>
                                        <input type="text" class="form-control" name="contact" value="<?php echo htmlspecialchars($historicalData['contact']); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Email Address</label>
                                        <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($historicalData['email']); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Address *</label>
                                        <input type="text" class="form-control" name="location" value="<?php echo htmlspecialchars($historicalData['location']); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Nearby Places</label>
                                        <input name="nearby_places" class="form-control" value="<?php echo htmlspecialchars($nearby_places_str); ?>" placeholder="Add places separated by commas">
                                        <small class="text-muted">Example: Museum, Park, Shopping Mall</small>
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
                                        
                                        <?php if (!empty($historicalData['image'])): ?>
                                            <div class="mt-2">
                                                <img src="<?php echo $historicalData['image']; ?>" class="img-thumbnail" width="100">
                                                <div class="form-check mt-1">
                                                    <input class="form-check-input" type="checkbox" name="delete_image" value="1" id="deleteImageCheck">
                                                    <label class="form-check-label" for="deleteImageCheck">Delete current image</label>
                                                </div>
                                            </div>
                                            <input type="hidden" name="existing_image" value="<?php echo $historicalData['image']; ?>">
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Upload Detail Images (Max 10 total):</label>
                                        <input type="file" class="form-control" name="image_detail[]" multiple accept="image/*">
                                        
                                        <?php if (!empty($historicalData['image_detail'])) {
                                            $images = explode(',', $historicalData['image_detail']); 
                                            echo '<div class="d-flex flex-wrap mt-2">';
                                            foreach ($images as $img) { 
                                                if (!empty($img)) { ?>
                                                    <div class="position-relative me-2 mb-2">
                                                        <img src="<?php echo $img; ?>" class="img-thumbnail" width="100">
                                                        <a href="?delete_image=<?php echo urlencode($img); ?>&historical_id=<?php echo $historicalData['id']; ?>" 
                                                        class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" 
                                                        onclick="return confirm('Are you sure you want to delete this image?')">
                                                        ×
                                                        </a>
                                                    </div>
                                                <?php }
                                            }
                                            echo '</div>';
                                            ?>
                                            <input type="hidden" name="existing_image_detail" value="<?php echo $historicalData['image_detail']; ?>">
                                        <?php } ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h5>Descriptions</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Description *</label>
                                        <textarea class="form-control" name="description" rows="3" required><?php echo htmlspecialchars($historicalData['description']); ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Historical Background</label>
                                        <textarea class="form-control" name="history" rows="3"><?php echo htmlspecialchars($historicalData['history']); ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Architectural Details</label>
                                        <textarea class="form-control" name="architecture" rows="3"><?php echo htmlspecialchars($historicalData['architecture']); ?></textarea>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Cultural Significance</label>
                                        <textarea class="form-control" name="significance" rows="3"><?php echo htmlspecialchars($historicalData['significance']); ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Fun Fact</label>
                                        <input type="text" class="form-control" name="fun_fact" value="<?php echo htmlspecialchars($historicalData['fun_fact']); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Source URL</label>
                                        <input type="text" class="form-control" name="source" value="<?php echo htmlspecialchars($historicalData['source']); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h5>Schedule & Hours</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Mass/Event Schedule</label>
                                        <textarea class="form-control" name="mass_schedule" rows="5" placeholder="Format: Day: Time, Time"><?php echo htmlspecialchars($historicalData['mass_schedule']); ?></textarea>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Operating Hours</label>
                                        <textarea class="form-control" name="hours" rows="5" placeholder="e.g., Monday: 9AM - 5PM"><?php echo htmlspecialchars($historicalData['hours']); ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h5>Location Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Google Maps Directions Link</label>
                                        <input type="text" name="directions_link" class="form-control" value="<?php echo htmlspecialchars($historicalData['directions_link']); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Website</label>
                                        <input type="text" class="form-control" name="website" value="<?php echo htmlspecialchars($historicalData['website']); ?>">
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Google Map Link</label>
                                        <input type="text" class="form-control" name="maps_link" value="<?php echo htmlspecialchars($historicalData['maps_link']); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Embed Google Map URL</label>
                                        <input type="text" class="form-control" name="map_embed" value="<?php echo htmlspecialchars($historicalData['maps_embed']); ?>">
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
                                                            <option value="ferry" <?php echo ($route['type'] == 'ferry') ? 'selected' : ''; ?>>Ferry</option>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control" name="route_name[]" value="<?php echo htmlspecialchars($route['name']); ?>" placeholder="e.g., Route 10, UV Express">
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control" name="route_link[]" value="<?php echo htmlspecialchars($route['link'] ?? ''); ?>" placeholder="https://maps.google.com/...">
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control" name="route_description[]" value="<?php echo htmlspecialchars($route['description'] ?? ''); ?>" placeholder="e.g., Stops at City Hall">
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
                                                        <option value="ferry">Ferry</option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control" name="route_name[]" placeholder="e.g., Route 10, UV Express">
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control" name="route_link[]" placeholder="https://maps.google.com/...">
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control" name="route_description[]" placeholder="e.g., Stops at City Hall">
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
                                <textarea class="form-control" name="social_media" rows="5" placeholder="Enter one URL per line"><?php echo htmlspecialchars($historicalData['social_media']); ?></textarea>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-success">
                                <?php echo isset($_GET['edit']) ? 'Update Historical Site' : 'Save Historical Site'; ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Show modal when editing
        document.addEventListener('DOMContentLoaded', function() {
            if (window.location.search.includes('edit')) {
                var modal = new bootstrap.Modal(document.getElementById('historicalModal'));
                modal.show();
            }
            
            // Auto-hide toast messages
            var toasts = document.querySelectorAll('.toast');
            toasts.forEach(function(toast) {
                setTimeout(function() {
                    var bsToast = new bootstrap.Toast(toast);
                    bsToast.hide();
                }, 5000);
            });
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
                        <option value="ferry">Ferry</option>
                    </select>
                </td>
                <td>
                    <input type="text" class="form-control" name="route_name[]" placeholder="e.g., Route 10, UV Express">
                </td>
                <td>
                    <input type="text" class="form-control" name="route_link[]" placeholder="https://maps.google.com/...">
                </td>
                <td>
                    <input type="text" class="form-control" name="route_description[]" placeholder="e.g., Stops at City Hall">
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