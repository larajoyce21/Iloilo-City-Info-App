<?php
include 'conn.php';
session_start();

if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    
    $stmt = $conn->prepare("SELECT image, image_detail FROM culturals WHERE id=?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if (!empty($row['image']) && file_exists($row['image'])) {
        unlink($row['image']);
    }
    
    if (!empty($row['image_detail'])) {
        $images = explode(',', $row['image_detail']);
        foreach ($images as $image) {
            if (!empty($image) && file_exists($image)) {
                unlink($image);
            }
        }
    }
    
    $stmt = $conn->prepare("DELETE FROM culturals WHERE id=?");
    $stmt->bind_param("i", $delete_id);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Cultural site deleted successfully";
    } else {
        $_SESSION['error'] = "Error deleting cultural site: " . $conn->error;
    }
    
    header("Location: add_culturals.php");
    exit();
}

if (isset($_GET['delete_image']) && isset($_GET['cultural_id'])) {
    $image_to_delete = $_GET['delete_image'];
    $cultural_id = (int)$_GET['cultural_id'];
    $is_card_image = isset($_GET['is_card_image']) ? true : false;
    
    if ($is_card_image) {
        $stmt = $conn->prepare("SELECT image FROM culturals WHERE id=?");
        $stmt->bind_param("i", $cultural_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        if (!empty($row['image']) && file_exists($row['image'])) {
            unlink($row['image']);
        }
        
        $stmt = $conn->prepare("UPDATE culturals SET image='' WHERE id=?");
        $stmt->bind_param("i", $cultural_id);
        $stmt->execute();
        
        $_SESSION['message'] = "Main image deleted successfully";
        header("Location: add_culturals.php?edit=" . $cultural_id);
        exit();
    } else {
        $stmt = $conn->prepare("SELECT image_detail FROM culturals WHERE id = ?");
        $stmt->bind_param("i", $cultural_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        $images = explode(',', $row['image_detail']);
        $updated_images = array_filter(array_diff($images, [$image_to_delete]));
        
        if (file_exists($image_to_delete)) {
            unlink($image_to_delete);
        }
        
        $stmt = $conn->prepare("UPDATE culturals SET image_detail = ? WHERE id = ?");
        $updated_images_str = implode(',', $updated_images);
        $stmt->bind_param("si", $updated_images_str, $cultural_id);
        $stmt->execute();
        
        $_SESSION['message'] = "Detail image deleted successfully";
        header("Location: add_culturals.php?edit=" . $cultural_id);
        exit();
    }
}

$query = "SELECT id, name, image, image_detail, description, location, maps_embed, maps_link, website, directions_link, hours, contact, email, fees, nearby_places, social_media, details_link, cultural_background, history, collections, notable_exhibits, significance, established, type, transportation_routes FROM culturals";
$stmt = $conn->prepare($query);
$stmt->execute();
$culturals = $stmt->get_result();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'] ?? null;
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $hours = trim($_POST['hours'] ?? '');
    $contact = trim($_POST['contact'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $maps_link = trim($_POST['maps_link'] ?? '');
    $website = trim($_POST['website'] ?? '');
    $directions_link = trim($_POST['directions_link'] ?? '');
    $mapEmbedURL = trim($_POST['map_embed'] ?? '');
    $fees = trim($_POST['fees'] ?? '');
    $social_media = trim($_POST['social_media'] ?? '');
    $details_link = trim($_POST['details_link'] ?? '');
    $cultural_background = trim($_POST['cultural_background'] ?? '');
    $history = trim($_POST['history'] ?? '');
    $collections = trim($_POST['collections'] ?? '');
    $notable_exhibits = trim($_POST['notable_exhibits'] ?? '');
    $significance = trim($_POST['significance'] ?? '');
    $established = trim($_POST['established'] ?? '');
    $type = trim($_POST['type'] ?? '');

    $nearby_places = $_POST['nearby_places'] ?? '';
    $nearby_places_array = array_map('trim', explode(',', $nearby_places));
    $nearby_places_json = json_encode(array_filter($nearby_places_array));

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
    
    if (isset($_POST['delete_image']) && $_POST['delete_image'] == '1') {
        if (!empty($target_file) && file_exists($target_file)) {
            unlink($target_file);
        }
        $target_file = '';
    }
    
    if (!empty($_FILES['image']['name'])) {
        $file_name = basename($_FILES["image"]["name"]);
        $target_file = $target_dir . uniqid() . '_' . $file_name;
        $imageFileType = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if (in_array($imageFileType, $allowed_types)) {
            move_uploaded_file($_FILES["image"]["tmp_name"], $target_file);
            if (!empty($_POST['existing_image']) && file_exists($_POST['existing_image'])) {
                unlink($_POST['existing_image']);
            }
        }
    }

    $existingImages = [];
    if (!empty($_POST['existing_image_detail'])) {
        $existingImages = explode(',', $_POST['existing_image_detail']);
        $existingImages = array_filter($existingImages);
    }
    
    $imagePaths = [];
    
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
            
            $file_name = basename($_FILES['image_detail']['name'][$i]);
            $tmp_name = $_FILES['image_detail']['tmp_name'][$i];
            $imageFileType = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            if (in_array($imageFileType, $allowed_types)) {
                $unique_name = $target_dir . uniqid() . '_' . $file_name;
                if (move_uploaded_file($tmp_name, $unique_name)) {
                    $imagePaths[] = $unique_name;
                }
            }
        }
    }
    
    $allImages = array_merge($existingImages, $imagePaths);
    $target_file_detail = implode(',', $allImages);
    
    if ($id) {
        $query = "UPDATE culturals SET 
                    name=?, image=?, image_detail=?, description=?, location=?, 
                    maps_embed=?, website=?, maps_link=?, directions_link=?, 
                    hours=?, contact=?, email=?, fees=?, nearby_places=?, 
                    social_media=?, details_link=?, cultural_background=?, 
                    history=?, collections=?, notable_exhibits=?, significance=?, 
                    established=?, type=?, transportation_routes=? 
                  WHERE id=?";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssssssssssssssssssssssi", 
            $name, $target_file, $target_file_detail, $description, $location,
            $mapEmbedURL, $website, $maps_link, $directions_link,
            $hours, $contact, $email, $fees, $nearby_places_json,
            $social_media, $details_link, $cultural_background,
            $history, $collections, $notable_exhibits, $significance,
            $established, $type, $transportation_routes_json, $id
        );
    } else {
        $query = "INSERT INTO culturals (
                    name, image, image_detail, description, location, 
                    maps_embed, website, maps_link, directions_link, 
                    hours, contact, email, fees, nearby_places, 
                    social_media, details_link, cultural_background, 
                    history, collections, notable_exhibits, significance, 
                    established, type, transportation_routes
                  ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssssssssssssssssssssss", 
            $name, $target_file, $target_file_detail, $description, $location,
            $mapEmbedURL, $website, $maps_link, $directions_link,
            $hours, $contact, $email, $fees, $nearby_places_json,
            $social_media, $details_link, $cultural_background,
            $history, $collections, $notable_exhibits, $significance,
            $established, $type, $transportation_routes_json
        );
    }
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Cultural site " . ($id ? "updated" : "added") . " successfully";
    } else {
        $_SESSION['error'] = "Error saving cultural site: " . $stmt->error;
    }
    
    header("Location: add_culturals.php");
    exit();
}

$nearby_places_str = '';
$transportation_routes = array();

if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT nearby_places, transportation_routes FROM culturals WHERE id=?");
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
    <title>Cultural Management</title>
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
        .form-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid #dee2e6;
        }
        .form-section h5 {
            color: #495057;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #dee2e6;
        }
        .transport-routes-table {
            background: white;
            border-radius: 5px;
            overflow: hidden;
            border: 1px solid #dee2e6;
        }
        .transport-routes-table table {
            margin-bottom: 0;
        }
        .transport-routes-table th {
            background: #e9ecef;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
            color: #495057;
        }
        .transport-routes-table td {
            vertical-align: middle;
        }
        .existing-images {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }
        .image-container {
            position: relative;
            width: 100px;
            height: 100px;
            border-radius: 5px;
            overflow: hidden;
            border: 1px solid #dee2e6;
        }
        .image-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .image-container .delete-image {
            position: absolute;
            top: 5px;
            right: 5px;
            background: #dc3545;
            color: white;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-size: 12px;
            z-index: 10;
        }
        .image-container .delete-image:hover {
            background: #c82333;
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
            <h1 class="text-light">Cultural Sites Management</h1>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#culturalModal">
                <i class="bi bi-plus-lg"></i> Add Cultural Site
            </button>
        </div>
        
        <?php if ($culturals->num_rows == 0): ?>
            <div class="alert alert-info">
                No cultural sites found. Click "Add Cultural Site" to create one.
            </div>
        <?php else: ?>
            <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-4">
                <?php while ($cultural = $culturals->fetch_assoc()): ?>
                    <div class="col">
                        <div class="card h-100">
                            <?php if (!empty($cultural['image'])): ?>
                                <img src="<?php echo htmlspecialchars($cultural['image']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($cultural['name']); ?>">
                            <?php else: ?>
                                <div class="card-img-top bg-secondary d-flex align-items-center justify-content-center">
                                    <span class="text-white">No Image</span>
                                </div>
                            <?php endif; ?>
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?php echo htmlspecialchars($cultural['name']); ?></h5>
                                <p class="card-text flex-grow-1"><?php echo substr(htmlspecialchars($cultural['description']), 0, 100) . '...'; ?></p>
                                <?php if (!empty($cultural['type'])): ?>
                                    <p class="text-primary fw-bold"><?php echo htmlspecialchars($cultural['type']); ?></p>
                                <?php endif; ?>
                                <div class="d-flex justify-content-between">
                                    <a href="add_culturals.php?edit=<?php echo $cultural['id']; ?>" class="btn btn-primary btn-sm">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    <a href="?delete=<?php echo $cultural['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this cultural site?')">
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

    <div class="modal fade <?php echo isset($_GET['edit']) ? 'show d-block' : ''; ?>" id="culturalModal" tabindex="-1" aria-labelledby="culturalModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="culturalModalLabel"><?php echo isset($_GET['edit']) ? 'Edit Cultural Site' : 'Add Cultural Site'; ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php 
                        $culturalData = [
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
                            'email' => '',
                            'fees' => '', 
                            'nearby_places' => '',
                            'social_media' => '',
                            'image_detail' => '',
                            'details_link' => '',
                            'cultural_background' => '',
                            'history' => '',
                            'collections' => '',
                            'notable_exhibits' => '',
                            'significance' => '',
                            'established' => '',
                            'type' => ''
                        ];
                        
                        if (isset($_GET['edit'])) {
                            $edit_id = (int)$_GET['edit'];
                            $stmt = $conn->prepare("SELECT * FROM culturals WHERE id = ?");
                            $stmt->bind_param("i", $edit_id);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            if ($result->num_rows > 0) {
                                $culturalData = $result->fetch_assoc();
                            }
                        }
                    ?>
                    <form method="POST" action="add_culturals.php" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($culturalData['id']); ?>">

                        <div class="form-section">
                            <h5>Basic Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Name *</label>
                                        <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($culturalData['name']); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Type of Cultural Site</label>
                                        <select class="form-control" name="type">
                                            <option value="">Select Type</option>
                                            <option value="Museum" <?php echo ($culturalData['type'] == 'Museum') ? 'selected' : ''; ?>>Museum</option>
                                            <option value="Historical Site" <?php echo ($culturalData['type'] == 'Historical Site') ? 'selected' : ''; ?>>Historical Site</option>
                                            <option value="Heritage House" <?php echo ($culturalData['type'] == 'Heritage House') ? 'selected' : ''; ?>>Heritage House</option>
                                            <option value="Cultural Center" <?php echo ($culturalData['type'] == 'Cultural Center') ? 'selected' : ''; ?>>Cultural Center</option>
                                            <option value="Art Gallery" <?php echo ($culturalData['type'] == 'Art Gallery') ? 'selected' : ''; ?>>Art Gallery</option>
                                            <option value="Library" <?php echo ($culturalData['type'] == 'Library') ? 'selected' : ''; ?>>Library</option>
                                            <option value="Landmark" <?php echo ($culturalData['type'] == 'Landmark') ? 'selected' : ''; ?>>Landmark</option>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Established Year</label>
                                        <input type="text" class="form-control" name="established" value="<?php echo htmlspecialchars($culturalData['established']); ?>" placeholder="e.g., 1971">
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Contact Number</label>
                                        <input type="text" class="form-control" name="contact" value="<?php echo htmlspecialchars($culturalData['contact']); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Email Address</label>
                                        <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($culturalData['email']); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Details Page Link</label>
                                        <input type="text" class="form-control" name="details_link" value="<?php echo htmlspecialchars($culturalData['details_link']); ?>" placeholder="e.g., cultural_details.php?id=1">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Description *</label>
                                <textarea class="form-control" name="description" rows="3"><?php echo htmlspecialchars($culturalData['description']); ?></textarea>
                            </div>
                        </div>

                        <div class="form-section">
                            <h5>Cultural Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Cultural Background</label>
                                        <textarea class="form-control" name="cultural_background" rows="4"><?php echo htmlspecialchars($culturalData['cultural_background']); ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">History</label>
                                        <textarea class="form-control" name="history" rows="4"><?php echo htmlspecialchars($culturalData['history']); ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Cultural Significance</label>
                                        <textarea class="form-control" name="significance" rows="4"><?php echo htmlspecialchars($culturalData['significance']); ?></textarea>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Collections</label>
                                        <textarea class="form-control" name="collections" rows="4"><?php echo htmlspecialchars($culturalData['collections']); ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Notable Exhibits</label>
                                        <textarea class="form-control" name="notable_exhibits" rows="4"><?php echo htmlspecialchars($culturalData['notable_exhibits']); ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h5>Schedule & Fees</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Operating Hours</label>
                                        <textarea class="form-control" name="hours" rows="5"><?php echo htmlspecialchars($culturalData['hours']); ?></textarea>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Admission Fees</label>
                                        <textarea class="form-control" name="fees" rows="5"><?php echo htmlspecialchars($culturalData['fees']); ?></textarea>
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
                                        
                                        <?php if (!empty($culturalData['image'])) { ?>
                                            <div class="existing-images">
                                                <div class="image-container">
                                                    <img src="<?php echo htmlspecialchars($culturalData['image']); ?>" alt="Card Image">
                                                    <a href="?delete_image=<?php echo urlencode($culturalData['image']); ?>&cultural_id=<?php echo $culturalData['id']; ?>&is_card_image=1" 
                                                       class="delete-image" 
                                                       onclick="return confirm('Delete this image?')">×</a>
                                                </div>
                                            </div>
                                            <input type="hidden" name="existing_image" value="<?php echo htmlspecialchars($culturalData['image']); ?>">
                                        <?php } ?>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Upload Detail Images (Max 5):</label>
                                        <input type="file" class="form-control" name="image_detail[]" multiple accept="image/*">
                                        <?php 
                                        if (!empty($culturalData['image_detail'])) {
                                            $detailImages = explode(',', $culturalData['image_detail']);
                                            echo '<div class="existing-images">';
                                            foreach ($detailImages as $img) {
                                                if (!empty($img)) {
                                                    echo '<div class="image-container">';
                                                    echo '<img src="' . htmlspecialchars($img) . '" alt="Detail Image">';
                                                    echo '<a href="?delete_image=' . urlencode($img) . '&cultural_id=' . $culturalData['id'] . '" 
                                                            class="delete-image" 
                                                            onclick="return confirm(\'Delete this image?\')">×</a>';
                                                    echo '</div>';
                                                }
                                            }
                                            echo '</div>';
                                            echo '<input type="hidden" name="existing_image_detail" value="' . htmlspecialchars($culturalData['image_detail']) . '">';
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
                                        <input type="text" class="form-control" name="location" value="<?php echo htmlspecialchars($culturalData['location']); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Nearby Places</label>
                                        <input name="nearby_places" class="form-control" value="<?php echo htmlspecialchars($nearby_places_str); ?>">
                                        <small class="text-muted">Example: Museum, Park, Shopping Mall</small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Website URL</label>
                                        <input type="url" class="form-control" name="website" value="<?php echo htmlspecialchars($culturalData['website']); ?>">
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Google Maps Directions Link</label>
                                        <input type="url" class="form-control" name="directions_link" value="<?php echo htmlspecialchars($culturalData['directions_link']); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Google Map Link</label>
                                        <input type="url" class="form-control" name="maps_link" value="<?php echo htmlspecialchars($culturalData['maps_link']); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Embed Google Map URL</label>
                                        <input type="url" class="form-control" name="map_embed" value="<?php echo htmlspecialchars($culturalData['maps_embed']); ?>">
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
                                            <th style="width: 50px">Action</th>
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
                                                        <input type="text" class="form-control" name="route_name[]" value="<?php echo htmlspecialchars($route['name']); ?>">
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control" name="route_link[]" value="<?php echo htmlspecialchars($route['link'] ?? ''); ?>">
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control" name="route_description[]" value="<?php echo htmlspecialchars($route['description'] ?? ''); ?>">
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
                                                    <input type="text" class="form-control" name="route_name[]">
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control" name="route_link[]">
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control" name="route_description[]">
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
                                <textarea class="form-control" name="social_media" rows="5"><?php echo isset($culturalData['social_media']) ? htmlspecialchars($culturalData['social_media']) : ''; ?></textarea>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <?php echo isset($_GET['edit']) ? 'Update Cultural Site' : 'Save Cultural Site'; ?>
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
                var modal = new bootstrap.Modal(document.getElementById('culturalModal'));
                modal.show();
                
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
                    <input type="text" class="form-control" name="route_name[]">
                </td>
                <td>
                    <input type="text" class="form-control" name="route_link[]">
                </td>
                <td>
                    <input type="text" class="form-control" name="route_description[]">
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