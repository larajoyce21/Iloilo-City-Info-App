<?php
session_start();
include "conn.php";

// Delete highschool
if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    
    // First get images to delete them from server
    $stmt = $conn->prepare("SELECT image, image_detail FROM highschools WHERE id=?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $highschool = $result->fetch_assoc();
    
    // Delete highschool from database
    $stmt = $conn->prepare("DELETE FROM highschools WHERE id=?");
    $stmt->bind_param("i", $delete_id);
    
    if ($stmt->execute()) {
        // Delete images from server
        if ($highschool) {
            if (!empty($highschool['image']) && file_exists($highschool['image'])) {
                unlink($highschool['image']);
            }
            
            if (!empty($highschool['image_detail'])) {
                $images = explode(',', $highschool['image_detail']);
                foreach ($images as $img) {
                    if (!empty($img) && file_exists($img)) {
                        unlink($img);
                    }
                }
            }
        }
        
        $_SESSION['message'] = "Highschool deleted successfully";
    } else {
        $_SESSION['error'] = "Error deleting highschool: " . $conn->error;
    }
    
    header("Location: add_highschools.php");
    exit();
}

// Delete individual image
if (isset($_GET['delete_image']) && isset($_GET['highschool_id'])) {
    $image_path = urldecode($_GET['delete_image']);
    $highschool_id = intval($_GET['highschool_id']);
    $is_card_image = isset($_GET['is_card_image']) ? true : false;
    
    // Delete image from server
    if (file_exists($image_path)) {
        unlink($image_path);
    }
    
    // Update database
    if ($is_card_image) {
        $stmt = $conn->prepare("UPDATE highschools SET image='' WHERE id=?");
        $stmt->bind_param("i", $highschool_id);
        $stmt->execute();
    } else {
        $stmt = $conn->prepare("SELECT image_detail FROM highschools WHERE id=?");
        $stmt->bind_param("i", $highschool_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $highschool = $result->fetch_assoc();
        
        if ($highschool) {
            $images = explode(',', $highschool['image_detail']);
            $updated_images = array();
            foreach ($images as $img) {
                if (trim($img) != trim($image_path)) {
                    $updated_images[] = $img;
                }
            }
            $updated_images_str = implode(',', $updated_images);
            
            $stmt = $conn->prepare("UPDATE highschools SET image_detail=? WHERE id=?");
            $stmt->bind_param("si", $updated_images_str, $highschool_id);
            $stmt->execute();
        }
    }
    
    $_SESSION['message'] = "Image deleted successfully";
    header("Location: add_highschools.php?edit=" . $highschool_id);
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

// Fetch all highschools
$query = "SELECT id, name, image, description, location, maps_embed, directions_link, maps_link, website, hours, contact, facilities, social_media, details_link, fees, school_type, year_established, population, principal, motto, admission_info, email, academics, achievements, class_size, transportation_options, source FROM highschools";
$stmt = $conn->prepare($query);
$stmt->execute();
$highschools = $stmt->get_result();

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
    $fees = trim($_POST['fees'] ?? '');
    $school_type = trim($_POST['school_type'] ?? '');
    $year_established = trim($_POST['year_established'] ?? '');
    $population = trim($_POST['population'] ?? '');
    $principal = trim($_POST['principal'] ?? '');
    $motto = trim($_POST['motto'] ?? '');
    $admission_info = trim($_POST['admission_info'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $academics = trim($_POST['academics'] ?? '');
    $achievements = trim($_POST['achievements'] ?? '');
    $class_size = trim($_POST['class_size'] ?? '');
    $source = trim($_POST['source'] ?? '');

    $facilities = $_POST['facilities'] ?? '';
    $facilities_array = array_map('trim', explode(',', $facilities));
    $facilities_json = json_encode(array_filter($facilities_array));

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
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
    $target_file = $_POST['existing_image'] ?? '';

    // Handle main image deletion
    if (isset($_POST['delete_image']) && $_POST['delete_image'] == '1') {
        if (file_exists($target_file)) {
            unlink($target_file);
        }
        $target_file = '';
    }

    // Handle new main image upload
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

    // Handle detail images
    $imagePaths = [];
    $existingImages = [];
    if (!empty($_POST['existing_image_detail'])) {
        $existingImages = explode(',', $_POST['existing_image_detail']);
        $existingImages = array_filter($existingImages);
    }
    
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
        $query = "UPDATE highschools SET name=?, image=?, image_detail=?, description=?, location=?, maps_embed=?, maps_link=?, website=?, directions_link=?, hours=?, contact=?, facilities=?, social_media=?, details_link=?, fees=?, school_type=?, year_established=?, population=?, principal=?, motto=?, admission_info=?, email=?, academics=?, achievements=?, class_size=?, source=?, transportation_routes=? WHERE id=?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssssssssssssssssssssssssssi", $name, $target_file, $target_file_detail, $description, $location, $mapEmbedURL, $maps_link, $website, $directions_link, $hours, $contact, $facilities_json, $social_media, $details_link, $fees, $school_type, $year_established, $population, $principal, $motto, $admission_info, $email, $academics, $achievements, $class_size, $source, $transportation_routes_json, $id);
    } else {
        $query = "INSERT INTO highschools (name, image, image_detail, description, location, maps_embed, maps_link, website, directions_link, hours, contact, facilities, social_media, details_link, fees, school_type, year_established, population, principal, motto, admission_info, email, academics, achievements, class_size, source, transportation_routes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssssssssssssssssssssssssss", $name, $target_file, $target_file_detail, $description, $location, $mapEmbedURL, $maps_link, $website, $directions_link, $hours, $contact, $facilities_json, $social_media, $details_link, $fees, $school_type, $year_established, $population, $principal, $motto, $admission_info, $email, $academics, $achievements, $class_size, $source, $transportation_routes_json);
    }
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Highschool " . ($id ? "updated" : "added") . " successfully";
    } else {
        $_SESSION['error'] = "Error saving highschool: " . $conn->error;
    }
    
    header("Location: add_highschools.php");
    exit();
}

// Initialize variables for edit mode
$facilities_str = '';
$transportation_routes = array();

if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT facilities, transportation_routes FROM highschools WHERE id=?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (!empty($row['facilities'])) {
            $decoded_facilities = json_decode($row['facilities'], true);
            if (is_array($decoded_facilities)) {
                $facilities_str = implode(', ', $decoded_facilities);
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
    <title>High School Management</title>
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
            <h1 class="text-light">High School Management</h1>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#highschoolModal">
                <i class="bi bi-plus-lg"></i> Add High School
            </button>
        </div>
        
        <?php if ($highschools->num_rows == 0): ?>
            <div class="alert alert-info">
                No high schools found. Click "Add High School" to create one.
            </div>
        <?php else: ?>
            <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-4">
                <?php while ($highschool = $highschools->fetch_assoc()): ?>
                    <div class="col">
                        <div class="card h-100">
                            <img src="<?php echo htmlspecialchars($highschool['image']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($highschool['name']); ?>">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?php echo htmlspecialchars($highschool['name']); ?></h5>
                                <p class="card-text flex-grow-1"><?php echo substr(htmlspecialchars($highschool['description']), 0, 100) . '...'; ?></p>
                                <?php if (!empty($highschool['school_type'])): ?>
                                    <p class="text-primary fw-bold"><?php echo htmlspecialchars($highschool['school_type']); ?></p>
                                <?php endif; ?>
                                <div class="d-flex justify-content-between">
                                    <a href="add_highschools.php?edit=<?php echo $highschool['id']; ?>" class="btn btn-primary btn-sm">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    <a href="?delete=<?php echo $highschool['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this high school?')">
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
    <div class="modal fade <?php echo isset($_GET['edit']) ? 'show d-block' : ''; ?>" id="highschoolModal" tabindex="-1" aria-labelledby="highschoolModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="highschoolModalLabel"><?php echo isset($_GET['edit']) ? 'Edit High School' : 'Add High School'; ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php 
                        $highschoolData = [
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
                            'facilities' => '',
                            'social_media' => '',
                            'details_link' => '',
                            'fees' => '',
                            'school_type' => '',
                            'year_established' => '',
                            'population' => '',
                            'principal' => '',
                            'motto' => '',
                            'admission_info' => '',
                            'email' => '',
                            'academics' => '',
                            'achievements' => '',
                            'class_size' => '',
                            'source' => ''
                        ];
                        
                        if (isset($_GET['edit'])) {
                            $edit_id = intval($_GET['edit']);
                            $stmt = $conn->prepare("SELECT * FROM highschools WHERE id=?");
                            $stmt->bind_param("i", $edit_id);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            if ($result->num_rows > 0) {
                                $highschoolData = $result->fetch_assoc();
                            }
                        }
                    ?>
                    <form method="POST" action="add_highschools.php" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($highschoolData['id']); ?>">
                        
                        <div class="form-section">
                            <h5>Basic Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Name *</label>
                                        <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($highschoolData['name']); ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Details Link:</label>
                                        <input type="text" class="form-control" name="details_link" value="<?php echo htmlspecialchars($highschoolData['details_link']); ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Description *</label>
                                        <textarea class="form-control" name="description" rows="4"><?php echo htmlspecialchars($highschoolData['description']); ?></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">School Type</label>
                                        <select class="form-control" name="school_type">
                                            <option value="">Select School Type</option>
                                            <option value="Public" <?php echo ($highschoolData['school_type'] == 'Public') ? 'selected' : ''; ?>>Public</option>
                                            <option value="Private" <?php echo ($highschoolData['school_type'] == 'Private') ? 'selected' : ''; ?>>Private</option>
                                            <option value="Charter" <?php echo ($highschoolData['school_type'] == 'Charter') ? 'selected' : ''; ?>>Charter</option>
                                            <option value="International" <?php echo ($highschoolData['school_type'] == 'International') ? 'selected' : ''; ?>>International</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Tuition Fees</label>
                                        <input type="text" class="form-control" name="fees" value="<?php echo htmlspecialchars($highschoolData['fees']); ?>" placeholder="e.g., ₱15,000 - ₱50,000/semester">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Year Established</label>
                                        <input type="text" class="form-control" name="year_established" value="<?php echo htmlspecialchars($highschoolData['year_established']); ?>" placeholder="e.g., 1950">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Student Population</label>
                                        <input type="text" class="form-control" name="population" value="<?php echo htmlspecialchars($highschoolData['population']); ?>" placeholder="e.g., 1,500 students">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Class Size</label>
                                        <input type="text" class="form-control" name="class_size" value="<?php echo htmlspecialchars($highschoolData['class_size']); ?>" placeholder="e.g., 30-40 students per class">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h5>Contact Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Principal/Head</label>
                                        <input type="text" class="form-control" name="principal" value="<?php echo htmlspecialchars($highschoolData['principal']); ?>" placeholder="Name of principal">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Contact Information</label>
                                        <input type="text" class="form-control" name="contact" value="<?php echo htmlspecialchars($highschoolData['contact']); ?>" placeholder="Phone number">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Email Address</label>
                                        <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($highschoolData['email']); ?>" placeholder="contact@school.edu.ph">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">School Hours</label>
                                        <textarea class="form-control" name="hours" rows="5" placeholder="e.g., Monday-Friday: 7:30AM - 4:00PM"><?php echo htmlspecialchars($highschoolData['hours']); ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h5>Images</h5>
                            <div class="mb-3">
                                <label class="form-label">Upload Card Image:</label>
                                <input type="file" class="form-control" name="image">
                                
                                <?php if (!empty($highschoolData['image'])) { ?>
                                    <div class="mt-2">
                                        <img src="<?php echo htmlspecialchars($highschoolData['image']); ?>" class="img-thumbnail" width="100">
                                        <div class="form-check mt-1">
                                            <input class="form-check-input" type="checkbox" name="delete_image" value="1" id="deleteImageCheck">
                                            <label class="form-check-label" for="deleteImageCheck">Delete current image</label>
                                        </div>
                                    </div>
                                    <input type="hidden" name="existing_image" value="<?php echo htmlspecialchars($highschoolData['image']); ?>">
                                <?php } ?>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Upload Detail Images (2-5):</label>
                                <input type="file" class="form-control" name="image_detail[]" multiple accept="image/*">
                                <?php if (!empty($highschoolData['image_detail'])) {
                                    $images = explode(',', $highschoolData['image_detail']); 
                                    echo '<div class="d-flex flex-wrap mt-2">';
                                    foreach ($images as $img) {
                                        if (!empty($img)) { ?>
                                            <div class="position-relative me-2 mb-2">
                                                <img src="<?php echo htmlspecialchars($img); ?>" class="img-thumbnail" width="100">
                                                <a href="?delete_image=<?php echo urlencode($img); ?>&highschool_id=<?php echo $highschoolData['id']; ?>" 
                                                   class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" 
                                                   onclick="return confirm('Are you sure you want to delete this image?')">
                                                   ×
                                                </a>
                                            </div>
                                        <?php }
                                    }
                                    echo '</div>'; ?>
                                    <input type="hidden" name="existing_image_detail" value="<?php echo htmlspecialchars($highschoolData['image_detail']); ?>">
                                <?php } ?>
                            </div>
                        </div>

                        <div class="form-section">
                            <h5>Location Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Address *</label>
                                        <input type="text" class="form-control" name="location" value="<?php echo htmlspecialchars($highschoolData['location']); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Website URL</label>
                                        <input type="url" class="form-control" name="website" value="<?php echo htmlspecialchars($highschoolData['website']); ?>" placeholder="https://school.edu.ph">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Google Maps Directions Link</label>
                                        <input type="url" class="form-control" name="directions_link" value="<?php echo htmlspecialchars($highschoolData['directions_link']); ?>" placeholder="https://maps.google.com/...">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Google Map Link</label>
                                        <input type="url" class="form-control" name="maps_link" value="<?php echo htmlspecialchars($highschoolData['maps_link']); ?>" placeholder="https://maps.google.com/...">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Embed Google Map URL</label>
                                <input type="url" class="form-control" name="map_embed" value="<?php echo htmlspecialchars($highschoolData['maps_embed']); ?>" placeholder="https://maps.google.com/embed...">
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
                                                            <option value="school_bus" <?php echo ($route['type'] == 'school_bus') ? 'selected' : ''; ?>>School Bus</option>
                                                            <option value="tricycle" <?php echo ($route['type'] == 'tricycle') ? 'selected' : ''; ?>>Tricycle</option>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control" name="route_name[]" value="<?php echo htmlspecialchars($route['name']); ?>" placeholder="e.g., School Shuttle, Route 10">
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control" name="route_link[]" value="<?php echo htmlspecialchars($route['link'] ?? ''); ?>" placeholder="https://maps.google.com/...">
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control" name="route_description[]" value="<?php echo htmlspecialchars($route['description'] ?? ''); ?>" placeholder="e.g., Free shuttle service, Stops at main entrance">
                                                    </td>
                                                    <td>
                                                        <button type="button" class="btn btn-danger btn-sm" onclick="this.closest('tr').remove()">
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
                                                        <option value="school_bus">School Bus</option>
                                                        <option value="tricycle">Tricycle</option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control" name="route_name[]" placeholder="e.g., School Shuttle, Route 10">
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control" name="route_link[]" placeholder="https://maps.google.com/...">
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control" name="route_description[]" placeholder="e.g., Free shuttle service, Stops at main entrance">
                                                </td>
                                                <td>
                                                    <button type="button" class="btn btn-danger btn-sm" onclick="this.closest('tr').remove()">
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
                            <h5>Academic Information</h5>
                            <div class="mb-3">
                                <label class="form-label">Academic Programs:</label>
                                <textarea class="form-control" name="academics" rows="4"><?php echo htmlspecialchars($highschoolData['academics']); ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">School Achievements:</label>
                                <textarea class="form-control" name="achievements" rows="4"><?php echo htmlspecialchars($highschoolData['achievements']); ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">School Motto</label>
                                <input type="text" class="form-control" name="motto" value="<?php echo htmlspecialchars($highschoolData['motto']); ?>" placeholder="e.g., Excellence in Education">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Admission Information</label>
                                <textarea class="form-control" name="admission_info" rows="3"><?php echo htmlspecialchars($highschoolData['admission_info']); ?></textarea>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h5>Facilities & Social Media</h5>
                            <div class="mb-3">
                                <label class="form-label">Facilities:</label>
                                <input name="facilities" class="form-control" value="<?= htmlspecialchars($facilities_str); ?>" placeholder="Add facilities separated by commas">
                                <small class="text-muted">Example: Library, Science Lab, Computer Lab, Gym, Auditorium</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Social Media Links</label>
                                <textarea class="form-control" name="social_media" rows="5" placeholder="Enter one URL per line, e.g. https://facebook.com/school"><?php echo isset($highschoolData['social_media']) ? htmlspecialchars($highschoolData['social_media']) : ''; ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Source/Reference</label>
                                <input type="text" class="form-control" name="source" value="<?php echo htmlspecialchars($highschoolData['source']); ?>" placeholder="e.g., Official website, DepEd">
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <?php echo isset($_GET['edit']) ? 'Update High School' : 'Save High School'; ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function addTransportRow() {
            const tableBody = document.getElementById('transportRoutesTableBody');
            const newRow = document.createElement('tr');
            
            newRow.innerHTML = `
                <td>
                    <select class="form-control" name="route_type[]">
                        <option value="jeepney">Jeepney</option>
                        <option value="bus">Bus</option>
                        <option value="school_bus">School Bus</option>
                        <option value="tricycle">Tricycle</option>
                    </select>
                </td>
                <td>
                    <input type="text" class="form-control" name="route_name[]" placeholder="e.g., School Shuttle, Route 10">
                </td>
                <td>
                    <input type="text" class="form-control" name="route_link[]" placeholder="https://maps.google.com/...">
                </td>
                <td>
                    <input type="text" class="form-control" name="route_description[]" placeholder="e.g., Free shuttle service, Stops at main entrance">
                </td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm" onclick="this.closest('tr').remove()">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            `;
            
            tableBody.appendChild(newRow);
        }

        document.addEventListener('DOMContentLoaded', function() {
            var toasts = document.querySelectorAll('.toast');
            toasts.forEach(function(toast) {
                setTimeout(function() {
                    var bsToast = new bootstrap.Toast(toast);
                    bsToast.hide();
                }, 5000);
            });
            
            if (window.location.search.includes('edit')) {
                var modal = new bootstrap.Modal(document.getElementById('highschoolModal'));
                modal.show();
                
                history.replaceState(null, null, window.location.pathname);
            }
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>