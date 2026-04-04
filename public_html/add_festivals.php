<?php
session_start();
include "conn.php";

$target_dir = "uploads/festivals/";
if (!file_exists($target_dir)) {
    mkdir($target_dir, 0777, true);
}

if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    
    $stmt = $conn->prepare("SELECT image, image_detail FROM festivals WHERE id=?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $festival = $result->fetch_assoc();
    
    $stmt = $conn->prepare("DELETE FROM festivals WHERE id=?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    
    if ($festival) {
        if (!empty($festival['image']) && file_exists($festival['image'])) {
            unlink($festival['image']);
        }
        
        if (!empty($festival['image_detail'])) {
            $images = explode(',', $festival['image_detail']);
            foreach ($images as $img) {
                if (!empty($img) && file_exists($img)) {
                    unlink($img);
                }
            }
        }
    }
    
    $_SESSION['message'] = "Festival deleted successfully";
    header("Location: add_festivals.php");
    exit();
}

if (isset($_GET['delete_image']) && isset($_GET['festival_id'])) {
    $image_path = $_GET['delete_image'];
    $festival_id = (int)$_GET['festival_id'];
    
    if (file_exists($image_path)) {
        unlink($image_path);
    }
    
    $stmt = $conn->prepare("SELECT image_detail FROM festivals WHERE id=?");
    $stmt->bind_param("i", $festival_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $festival = $result->fetch_assoc();
    
    if ($festival) {
        $images = explode(',', $festival['image_detail']);
        $updated_images = array_diff($images, [$image_path]);
        $updated_images_str = implode(',', $updated_images);
        
        $stmt = $conn->prepare("UPDATE festivals SET image_detail=? WHERE id=?");
        $stmt->bind_param("si", $updated_images_str, $festival_id);
        $stmt->execute();
    }
    
    $_SESSION['message'] = "Image deleted successfully";
    header("Location: add_festivals.php?edit=" . $festival_id);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $name = trim($_POST['name']);
    $location = trim($_POST['location']);
    $description = trim($_POST['description']);
    $lineup = trim($_POST['lineup']);
    $schedule = trim($_POST['schedule']);
    $features = trim($_POST['features']);
    $visitor_tips = trim($_POST['visitor_tips']);
    $directions = trim($_POST['directions']);
    $event_date = trim($_POST['event_date']);
    $duration = trim($_POST['duration']);
    $attendance = trim($_POST['attendance']);
    $established = trim($_POST['established']);
    $significance = trim($_POST['significance']);
    $contact_phone = trim($_POST['contact_phone']);
    $contact_email = trim($_POST['contact_email']);
    $website = trim($_POST['website']);
    $maps_link = trim($_POST['maps_link']);
    $maps_embed = trim($_POST['maps_embed']);
    $transportation_options = trim($_POST['transportation_options']);
    $details_link = trim($_POST['details_link']);
    $announcement = trim($_POST['announcement']);
    $social_media = trim($_POST['social_media']);

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

    $target_file = $_POST['existing_image'] ?? '';
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
        } else {
            $_SESSION['error'] = "Error uploading the main image";
            header("Location: add_festivals.php" . ($id ? "?edit=$id" : ""));
            exit();
        }
    }

    $imagePaths = [];
    $existingImages = !empty($_POST['existing_image_detail']) ? explode(',', $_POST['existing_image_detail']) : [];
    
    if (!empty($_FILES['image_detail']['name'][0])) {
        foreach ($_FILES['image_detail']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['image_detail']['error'][$key] !== UPLOAD_ERR_OK) continue;
            
            $file_ext = strtolower(pathinfo($_FILES['image_detail']['name'][$key], PATHINFO_EXTENSION));
            $new_filename = $target_dir . uniqid('detail_') . '.' . $file_ext;
            
            if (move_uploaded_file($tmp_name, $new_filename)) {
                $imagePaths[] = $new_filename;
            }
        }
    }
    
    $allImages = array_merge($existingImages, $imagePaths);
    $target_file_detail = implode(',', $allImages);

    if ($id) {
        $query = "UPDATE festivals SET 
                  name=?, location=?, description=?, image=?, image_detail=?, 
                  lineup=?, schedule=?, features=?, visitor_tips=?, directions=?, 
                  event_date=?, duration=?, attendance=?, established=?, significance=?, 
                  contact_phone=?, contact_email=?, website=?, maps_link=?, maps_embed=?, 
                  transportation_options=?, details_link=?, announcement=?, social_media=?,
                  transportation_routes=?
                  WHERE id=?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssssssssssssssssssssssssi", 
            $name, $location, $description, $target_file, $target_file_detail,
            $lineup, $schedule, $features, $visitor_tips, $directions,
            $event_date, $duration, $attendance, $established, $significance,
            $contact_phone, $contact_email, $website, $maps_link, $maps_embed, 
            $transportation_options, $details_link, $announcement, $social_media,
            $transportation_routes_json, $id);
    } else {
        $query = "INSERT INTO festivals (
                  name, location, description, image, image_detail, 
                  lineup, schedule, features, visitor_tips, directions, 
                  event_date, duration, attendance, established, significance, 
                  contact_phone, contact_email, website, maps_link, maps_embed, 
                  transportation_options, details_link, announcement, social_media,
                  transportation_routes) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssssssssssssssssssssssss", 
            $name, $location, $description, $target_file, $target_file_detail,
            $lineup, $schedule, $features, $visitor_tips, $directions,
            $event_date, $duration, $attendance, $established, $significance,
            $contact_phone, $contact_email, $website, $maps_link, $maps_embed, 
            $transportation_options, $details_link, $announcement, $social_media,
            $transportation_routes_json);
    }
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Festival " . ($id ? "updated" : "added") . " successfully";
    } else {
        $_SESSION['error'] = "Error saving festival: " . $conn->error;
    }
    
    header("Location: add_festivals.php");
    exit();
}

$query = "SELECT id, name, image, description FROM festivals ORDER BY name";
$festivals = $conn->query($query);

$festivalData = [
    'id' => '', 'name' => '', 'location' => '', 'description' => '', 'image' => '', 'image_detail' => '',
    'lineup' => '', 'schedule' => '', 'features' => '', 'visitor_tips' => '', 'directions' => '',
    'event_date' => '', 'duration' => '', 'attendance' => '', 'established' => '', 'significance' => '',
    'contact_phone' => '', 'contact_email' => '', 'website' => '', 'maps_link' => '', 'maps_embed' => '',
     'transportation_options' => '', 'announcement' => '', 'details_link' => '', 'social_media' => '',
     'transportation_routes' => ''
];

$transportation_routes = array();

if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT *, transportation_routes FROM festivals WHERE id=?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $festivalData = $result->fetch_assoc();
        if (!empty($festivalData['transportation_routes'])) {
            $decoded_routes = json_decode($festivalData['transportation_routes'], true);
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
    <title>Festivals Management</title>
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
    <div class="container mt-4">
        <!-- Toast Notifications -->
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

        <button type="button" class="btn btn-light text-dark mb-3" onclick="window.location.href='dashboard.php'">
            <i class="bi bi-arrow-left"></i> Back to Dashboard
        </button>
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="text-light">Festivals Management</h1>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#festivalModal">
                <i class="bi bi-plus-lg"></i> Add Festival
            </button>
        </div>
        
        <?php if ($festivals->num_rows == 0): ?>
            <div class="alert alert-info">
                No festivals found. Click "Add Festival" to create one.
            </div>
        <?php else: ?>
            <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-4">
                <?php while ($festival = $festivals->fetch_assoc()): ?>
                    <div class="col">
                        <div class="card h-100">
                            <?php if (!empty($festival['image'])): ?>
                                <img src="<?= htmlspecialchars($festival['image']) ?>" class="card-img-top" alt="<?= htmlspecialchars($festival['name']) ?>">
                            <?php endif; ?>
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?= htmlspecialchars($festival['name']) ?></h5>
                                <p class="card-text flex-grow-1"><?= substr(htmlspecialchars($festival['description']), 0, 100) . '...' ?></p>
                                <div class="d-flex justify-content-between">
                                    <a href="add_festivals.php?edit=<?= $festival['id'] ?>" class="btn btn-primary btn-sm">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    <a href="?delete=<?= $festival['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this festival?')">
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

    <!-- Add/Edit Festival Modal -->
    <div class="modal fade <?= isset($_GET['edit']) ? 'show d-block' : '' ?>" id="festivalModal" tabindex="-1" aria-labelledby="festivalModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="festivalModalLabel"><?= $festivalData['id'] ? 'Edit Festival' : 'Add Festival' ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="add_festivals.php" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="<?= htmlspecialchars($festivalData['id']) ?>">
                        
                        <div class="form-section">
                            <h5>Basic Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Name *</label>
                                        <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($festivalData['name']) ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Location *</label>
                                        <input type="text" class="form-control" name="location" value="<?= htmlspecialchars($festivalData['location']) ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Event Date</label>
                                        <input type="text" class="form-control" name="event_date" value="<?= htmlspecialchars($festivalData['event_date']) ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Duration</label>
                                        <input type="text" class="form-control" name="duration" value="<?= htmlspecialchars($festivalData['duration']) ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Upload Card Image:</label>
                                        <input type="file" class="form-control" name="image" accept="image/*">
                                        
                                        <?php if (!empty($festivalData['image'])): ?>
                                            <div class="mt-2">
                                                <img src="<?= htmlspecialchars($festivalData['image']) ?>" class="img-thumbnail" width="100">
                                                <div class="form-check mt-1">
                                                    <input class="form-check-input" type="checkbox" name="delete_image" value="1" id="deleteImageCheck">
                                                    <label class="form-check-label" for="deleteImageCheck">Delete current image</label>
                                                </div>
                                            </div>
                                            <input type="hidden" name="existing_image" value="<?= htmlspecialchars($festivalData['image']) ?>">
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Description *</label>
                                <textarea class="form-control" name="description" rows="4" required><?= htmlspecialchars($festivalData['description']) ?></textarea>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h5>Images</h5>
                            <div class="mb-3">
                                <label class="form-label">Detail Images (2-5 recommended)</label>
                                <input type="file" class="form-control" name="image_detail[]" multiple accept="image/*">
                                
                                <?php if (!empty($festivalData['image_detail'])): ?>
                                    <div class="image-preview-container mt-2">
                                        <?php 
                                            $images = explode(',', $festivalData['image_detail']);
                                            foreach ($images as $img): 
                                                if (!empty($img)):
                                        ?>
                                            <div class="image-preview">
                                                <img src="<?= htmlspecialchars($img) ?>" alt="Detail image">
                                                <div class="delete-image-btn" 
                                                     onclick="if(confirm('Delete this image?')) window.location.href='?delete_image=<?= urlencode($img) ?>&festival_id=<?= $festivalData['id'] ?>'">
                                                    ×
                                                </div>
                                            </div>
                                        <?php 
                                                endif;
                                            endforeach; 
                                        ?>
                                        <input type="hidden" name="existing_image_detail" value="<?= htmlspecialchars($festivalData['image_detail']) ?>">
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h5>Festival Details</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Activities Lineup</label>
                                        <textarea class="form-control" name="lineup" rows="4"><?= htmlspecialchars($festivalData['lineup']) ?></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Event Schedule</label>
                                        <textarea class="form-control" name="schedule" rows="4"><?= htmlspecialchars($festivalData['schedule']) ?></textarea>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Unique Features</label>
                                        <textarea class="form-control" name="features" rows="4"><?= htmlspecialchars($festivalData['features']) ?></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Visitor Tips</label>
                                        <textarea class="form-control" name="visitor_tips" rows="4"><?= htmlspecialchars($festivalData['visitor_tips']) ?></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Announcement Lineup</label>
                                        <textarea class="form-control" name="announcement" rows="4"><?= htmlspecialchars($festivalData['announcement']) ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h5>Quick Facts</h5>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Attendance</label>
                                        <input type="text" class="form-control" name="attendance" value="<?= htmlspecialchars($festivalData['attendance']) ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Established</label>
                                        <input type="text" class="form-control" name="established" value="<?= htmlspecialchars($festivalData['established']) ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Significance</label>
                                        <input type="text" class="form-control" name="significance" value="<?= htmlspecialchars($festivalData['significance']) ?>">
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
                                                        <option value="shuttle" <?php echo (isset($transportation_routes[$i]['type']) && $transportation_routes[$i]['type'] == 'shuttle') ? 'selected' : ''; ?>>Festival Shuttle</option>
                                                        <option value="bus" <?php echo (isset($transportation_routes[$i]['type']) && $transportation_routes[$i]['type'] == 'bus') ? 'selected' : ''; ?>>Bus</option>
                                                        <option value="jeepney" <?php echo (isset($transportation_routes[$i]['type']) && $transportation_routes[$i]['type'] == 'jeepney') ? 'selected' : ''; ?>>Jeepney</option>
                                                        <option value="tricycle" <?php echo (isset($transportation_routes[$i]['type']) && $transportation_routes[$i]['type'] == 'tricycle') ? 'selected' : ''; ?>>Tricycle</option>
                                                        <option value="taxi" <?php echo (isset($transportation_routes[$i]['type']) && $transportation_routes[$i]['type'] == 'taxi') ? 'selected' : ''; ?>>Taxi</option>
                                                        <option value="motorcycle" <?php echo (isset($transportation_routes[$i]['type']) && $transportation_routes[$i]['type'] == 'motorcycle') ? 'selected' : ''; ?>>Motorcycle</option>
                                                        <option value="bicycle" <?php echo (isset($transportation_routes[$i]['type']) && $transportation_routes[$i]['type'] == 'bicycle') ? 'selected' : ''; ?>>Bicycle</option>
                                                        <option value="walking" <?php echo (isset($transportation_routes[$i]['type']) && $transportation_routes[$i]['type'] == 'walking') ? 'selected' : ''; ?>>Walking Route</option>
                                                        <option value="parking" <?php echo (isset($transportation_routes[$i]['type']) && $transportation_routes[$i]['type'] == 'parking') ? 'selected' : ''; ?>>Parking Area</option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control" name="route_name[]" value="<?php echo isset($transportation_routes[$i]['name']) ? htmlspecialchars($transportation_routes[$i]['name']) : ''; ?>" placeholder="e.g., Festival Express, Route 101, Main Parking">
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control" name="route_link[]" value="<?php echo isset($transportation_routes[$i]['link']) ? htmlspecialchars($transportation_routes[$i]['link']) : ''; ?>" placeholder="https://maps.google.com/...">
                                                </td>
                                                <td>
                                                    <textarea class="form-control" name="route_description[]" rows="2" placeholder="e.g., Free shuttle from city center, operates 8AM-12AM"><?php echo isset($transportation_routes[$i]['description']) ? htmlspecialchars($transportation_routes[$i]['description']) : ''; ?></textarea>
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
                            <h5>Location Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Transportation Options (One per line):</label>
                                        <textarea class="form-control" name="transportation_options" rows="5"><?php echo htmlspecialchars($festivalData['transportation_options']); ?></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Directions</label>
                                        <textarea class="form-control" name="directions" rows="4"><?= htmlspecialchars($festivalData['directions']) ?></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Google Map Link</label>
                                        <input type="url" class="form-control" name="maps_link" value="<?= htmlspecialchars($festivalData['maps_link']) ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Embed Google Map URL</label>
                                        <input type="url" class="form-control" name="maps_embed" value="<?= htmlspecialchars($festivalData['maps_embed']) ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Details Page Link:</label>
                                        <input type="text" class="form-control" name="details_link" value="<?php echo $festivalData['details_link']; ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h5>Contact Information</h5>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Phone</label>
                                        <input type="text" class="form-control" name="contact_phone" value="<?= htmlspecialchars($festivalData['contact_phone']) ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" name="contact_email" value="<?= htmlspecialchars($festivalData['contact_email']) ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Website</label>
                                        <input type="url" class="form-control" name="website" value="<?= htmlspecialchars($festivalData['website']) ?>">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Social Media Links (one per line)</label>
                                    <textarea class="form-control" name="social_media" rows="5"><?php echo isset($festivalData['social_media']) ? htmlspecialchars($festivalData['social_media']) : ''; ?></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <?= $festivalData['id'] ? 'Update Festival' : 'Save Festival' ?>
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
            // Auto-hide toasts after 5 seconds
            var toasts = document.querySelectorAll('.toast');
            toasts.forEach(function(toast) {
                setTimeout(function() {
                    var bsToast = new bootstrap.Toast(toast);
                    bsToast.hide();
                }, 5000);
            });
            
            // Show modal if editing
            if (window.location.search.includes('edit')) {
                var modal = new bootstrap.Modal(document.getElementById('festivalModal'));
                modal.show();
                
                // Clean URL
                history.replaceState(null, null, window.location.pathname);
            }
        });
        
        function addRow() {
            var tbody = document.getElementById('transport-routes-tbody');
            var newRow = document.createElement('tr');
            newRow.innerHTML = `
                <td>
                    <select class="form-control" name="route_type[]">
                        <option value="">Select Type</option>
                        <option value="shuttle">Festival Shuttle</option>
                        <option value="bus">Bus</option>
                        <option value="jeepney">Jeepney</option>
                        <option value="tricycle">Tricycle</option>
                        <option value="taxi">Taxi</option>
                        <option value="motorcycle">Motorcycle</option>
                        <option value="bicycle">Bicycle</option>
                        <option value="walking">Walking Route</option>
                        <option value="parking">Parking Area</option>
                    </select>
                </td>
                <td>
                    <input type="text" class="form-control" name="route_name[]" placeholder="e.g., Festival Express, Route 101, Main Parking">
                </td>
                <td>
                    <input type="text" class="form-control" name="route_link[]" placeholder="https://maps.google.com/...">
                </td>
                <td>
                    <textarea class="form-control" name="route_description[]" rows="2" placeholder="e.g., Free shuttle from city center, operates 8AM-12AM"></textarea>
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