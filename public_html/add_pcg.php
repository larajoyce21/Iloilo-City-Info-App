<?php
session_start();
require_once 'conn.php';

define('UPLOAD_DIR', 'uploads/pcg/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); 
define('ALLOWED_TYPES', ['jpg', 'jpeg', 'png', 'gif']);
define('MAX_DETAIL_IMAGES', 5);

if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    
    $stmt = $conn->prepare("SELECT image, image_detail FROM pcg_details WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        
        if (!empty($row['image']) && file_exists($row['image'])) {
            unlink($row['image']);
        }
        
        if (!empty($row['image_detail'])) {
            $images = explode(',', $row['image_detail']);
            foreach ($images as $img) {
                if (!empty($img) && file_exists($img)) {
                    unlink($img);
                }
            }
        }
    }
    
    $stmt = $conn->prepare("DELETE FROM pcg_details WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    
    $_SESSION['success'] = "PCG station deleted successfully";
    header("Location: add_pcg.php");
    exit();
}

if (isset($_GET['delete_image']) && isset($_GET['pcg_id'])) {
    $image_path = urldecode($_GET['delete_image']);
    $pcg_id = intval($_GET['pcg_id']);
    
    $stmt = $conn->prepare("SELECT image_detail FROM pcg_details WHERE id = ?");
    $stmt->bind_param("i", $pcg_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $images = explode(',', $row['image_detail']);
        
        if (in_array($image_path, $images)) {
            if (file_exists($image_path)) {
                unlink($image_path);
            }
            
            $updated_images = array_diff($images, [$image_path]);
            $updated_images_str = implode(',', $updated_images);
            
            $stmt = $conn->prepare("UPDATE pcg_details SET image_detail = ? WHERE id = ?");
            $stmt->bind_param("si", $updated_images_str, $pcg_id);
            $stmt->execute();
            
            $_SESSION['success'] = "Image deleted successfully";
        }
    }
    
    header("Location: add_pcg.php?edit=" . $pcg_id);
    exit();
}

function convertToEmbedURL($googleMapsLink) {
    if (empty($googleMapsLink)) return '';
    
    if (strpos($googleMapsLink, 'embed') !== false) {
        return $googleMapsLink;
    }
    
    if (strpos($googleMapsLink, 'goo.gl/maps') !== false || strpos($googleMapsLink, 'google.com/maps') !== false) {
        $urlParts = parse_url($googleMapsLink);
        if (isset($urlParts['query'])) {
            parse_str($urlParts['query'], $queryParams);
            if (isset($queryParams['q'])) {
                return 'https://www.google.com/maps/embed/v1/place?key=YOUR_API_KEY&q=' . urlencode($queryParams['q']);
            }
        }
        return $googleMapsLink;
    }
    return $googleMapsLink;
}

$query = "SELECT * FROM pcg_details ORDER BY name ASC";
$stmt = $conn->prepare($query);
if ($stmt) {
    $stmt->execute();
    $pcgs = $stmt->get_result();
} else {
    die("Error preparing statement: " . $conn->error);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $name = $conn->real_escape_string(trim($_POST['name'] ?? ''));
    $description = $conn->real_escape_string(trim($_POST['description'] ?? ''));
    $contact = $conn->real_escape_string(trim($_POST['contact'] ?? ''));
    $hotlines = $conn->real_escape_string(trim($_POST['hotlines'] ?? ''));
    $location = $conn->real_escape_string(trim($_POST['location'] ?? ''));
    $website = $conn->real_escape_string(trim($_POST['website'] ?? ''));
    $maps_link = $conn->real_escape_string(trim($_POST['maps_link'] ?? ''));
    $directions_link = $conn->real_escape_string(trim($_POST['directions_link'] ?? ''));
    $services = $conn->real_escape_string(trim($_POST['services'] ?? ''));
    $requirements = $conn->real_escape_string(trim($_POST['requirements'] ?? ''));
    $mapEmbedURL = $conn->real_escape_string(trim($_POST['map_embed'] ?? ''));
    $email = $conn->real_escape_string(trim($_POST['email'] ?? ''));
    $office_hours = $conn->real_escape_string(trim($_POST['office_hours'] ?? ''));
    $officer_in_charge = $conn->real_escape_string(trim($_POST['officer_in_charge'] ?? ''));
    $downloadables = $conn->real_escape_string(trim($_POST['downloadables'] ?? ''));
    $last_updated = $conn->real_escape_string(trim($_POST['last_updated'] ?? date('Y-m-d')));
    $nearby_places = $conn->real_escape_string(trim($_POST['nearby_places'] ?? ''));
    $facebook_link = $conn->real_escape_string(trim($_POST['facebook_link'] ?? ''));
    $fees = $conn->real_escape_string(trim($_POST['fees'] ?? ''));
    $social_media = $conn->real_escape_string(trim($_POST['social_media'] ?? ''));
    $details_link = $conn->real_escape_string(trim($_POST['details_link'] ?? ''));
    
    // Handle transportation routes
    $transportation_routes = array();
    
    if (isset($_POST['route_type']) && is_array($_POST['route_type'])) {
        $route_count = count($_POST['route_type']);
        
        for ($i = 0; $i < $route_count; $i++) {
            $type_route = trim($_POST['route_type'][$i] ?? '');
            $name_route = trim($_POST['route_name'][$i] ?? '');
            
            if (!empty($type_route) && !empty($name_route)) {
                $transportation_routes[] = array(
                    'type' => $type_route,
                    'name' => $name_route,
                    'link' => trim($_POST['route_link'][$i] ?? ''),
                    'description' => trim($_POST['route_description'][$i] ?? '')
                );
            }
        }
    }
    
    $transportation_routes_json = json_encode($transportation_routes);
    
    if (empty($name)) {
        $_SESSION['error'] = "Name is required";
        header("Location: add_pcg.php" . ($id ? "?edit=$id" : ""));
        exit();
    }

    $target_file = $_POST['existing_image'] ?? '';

    if (isset($_FILES['image']['name']) && $_FILES['image']['name']) {
        $file = $_FILES['image'];
        $validation = validateImage($file);

        if ($validation === true) {
            $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $new_filename = uniqid('img_', true) . '.' . $file_ext;
            $target_file = UPLOAD_DIR . $new_filename;

            if (move_uploaded_file($file['tmp_name'], $target_file)) {
                if (!empty($_POST['existing_image']) && file_exists($_POST['existing_image'])) {
                    unlink($_POST['existing_image']);
                }
            } else {
                $_SESSION['error'] = "Error uploading the main image";
                header("Location: add_pcg.php" . ($id ? "?edit=$id" : ""));
                exit();
            }
        } else {
            $_SESSION['error'] = $validation;
            header("Location: add_pcg.php" . ($id ? "?edit=$id" : ""));
            exit();
        }
    } elseif (isset($_POST['delete_image']) && $_POST['delete_image'] == '1') {
        if (!empty($_POST['existing_image']) && file_exists($_POST['existing_image'])) {
            unlink($_POST['existing_image']);
        }
        $target_file = '';
    }

    $imagePaths = [];
    $existingImages = [];

    if (!empty($_POST['existing_image_detail'])) {
        $existingImages = array_filter(explode(',', $_POST['existing_image_detail']));
    }

    if (!empty($_FILES['image_detail']['name'][0])) {
        $totalNew = count($_FILES['image_detail']['name']);
        $totalCombined = count($existingImages) + $totalNew;

        if ($totalCombined > MAX_DETAIL_IMAGES) {
            $_SESSION['error'] = "You can upload a maximum of " . MAX_DETAIL_IMAGES . " detail images total";
            header("Location: add_pcg.php" . ($id ? "?edit=$id" : ""));
            exit();
        }

        for ($i = 0; $i < $totalNew; $i++) {
            if ($_FILES['image_detail']['error'][$i] !== UPLOAD_ERR_OK) continue;

            $file = [
                'name' => $_FILES['image_detail']['name'][$i],
                'type' => $_FILES['image_detail']['type'][$i],
                'tmp_name' => $_FILES['image_detail']['tmp_name'][$i],
                'error' => $_FILES['image_detail']['error'][$i],
                'size' => $_FILES['image_detail']['size'][$i]
            ];

            $validation = validateImage($file);
            if ($validation === true) {
                $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $new_filename = uniqid('img_', true) . '.' . $file_ext;
                $targetPath = UPLOAD_DIR . $new_filename;

                if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                    $imagePaths[] = $targetPath;
                }
            }
        }
    }

    $allImages = array_merge($existingImages, $imagePaths);
    $target_file_detail = implode(',', $allImages);

    if (!empty($maps_link) && empty($mapEmbedURL)) {
        $mapEmbedURL = convertToEmbedURL($maps_link);
    }

    if ($id) {
        $query = "UPDATE pcg_details SET 
            name = '$name', 
            image = '$target_file', 
            image_detail = '$target_file_detail', 
            description = '$description', 
            hotlines = '$hotlines', 
            location = '$location', 
            maps_embed = '$mapEmbedURL', 
            website = '$website', 
            maps_link = '$maps_link', 
            directions_link = '$directions_link', 
            contact = '$contact', 
            services = '$services', 
            requirements = '$requirements', 
            email = '$email', 
            office_hours = '$office_hours', 
            officer_in_charge = '$officer_in_charge', 
            downloadables = '$downloadables', 
            last_updated = '$last_updated', 
            nearby_places = '$nearby_places', 
            facebook_link = '$facebook_link',
            fees = '$fees',
            social_media = '$social_media',
            details_link = '$details_link',
            transportation_routes = '$transportation_routes_json'
            WHERE id = $id";
    } else {
        $query = "INSERT INTO pcg_details (
            name, image, image_detail, description, hotlines, location, 
            maps_embed, website, maps_link, directions_link, contact, 
            services, requirements, email, office_hours, officer_in_charge, 
            downloadables, last_updated, nearby_places, facebook_link,
            fees, social_media, details_link, transportation_routes
        ) VALUES (
            '$name', '$target_file', '$target_file_detail', '$description', '$hotlines', 
            '$location', '$mapEmbedURL', '$website', '$maps_link', '$directions_link', 
            '$contact', '$services', '$requirements', '$email', 
            '$office_hours', '$officer_in_charge', '$downloadables', '$last_updated', 
            '$nearby_places', '$facebook_link',
            '$fees', '$social_media', '$details_link', '$transportation_routes_json'
        )";
    }

    if ($conn->query($query)) {
        $_SESSION['success'] = "PCG station " . ($id ? "updated" : "added") . " successfully";
    } else {
        $_SESSION['error'] = "Error: " . $conn->error;
    }

    header("Location: add_pcg.php");
    exit();
}

function validateImage($file) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return "Upload error. Code: " . $file['error'];
    }
    
    if ($file['size'] > MAX_FILE_SIZE) {
        return "File too large. Max: " . (MAX_FILE_SIZE / 1024 / 1024) . "MB";
    }
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_TYPES)) {
        return "Invalid type. Allowed: " . implode(', ', ALLOWED_TYPES);
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    $allowed_mimes = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($mime, $allowed_mimes)) {
        return "Invalid MIME type: $mime";
    }

    $image_info = getimagesize($file['tmp_name']);
    if (!$image_info) {
        return "File is not a valid image";
    }

    return true;
}

$pcgData = [
    'id' => '', 
    'name' => '', 
    'description' => '', 
    'hotlines' => '', 
    'location' => '', 
    'website' => '', 
    'image' => '',
    'maps_embed' => '', 
    'maps_link' => '',
    'directions_link' => '', 
    'contact' => '', 
    'services' => '',
    'requirements' => '',
    'image_detail' => '',
    'email' => '',  
    'office_hours' => '', 
    'officer_in_charge' => '', 
    'downloadables' => '', 
    'last_updated' => date('Y-m-d'), 
    'nearby_places' => '', 
    'facebook_link' => '',
    'fees' => '',
    'social_media' => '',
    'details_link' => '',
    'transportation_routes' => ''
];

$transportation_routes = array();
$nearby_places_str = '';

if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM pcg_details WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $pcgData = $result->fetch_assoc();
        if (!empty($pcgData['transportation_routes'])) {
            $decoded_routes = json_decode($pcgData['transportation_routes'], true);
            if (is_array($decoded_routes)) {
                $transportation_routes = $decoded_routes;
            }
        }
        if (!empty($pcgData['nearby_places'])) {
            $nearby_places_str = $pcgData['nearby_places'];
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
    <title>PCG Management</title>
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
        .card-img-top {
            height: 200px;
            object-fit: cover;
        }
        .image-preview-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }
        .image-preview-wrapper {
            position: relative;
        }
        .image-preview {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .delete-image-btn {
            position: absolute;
            top: 5px;
            right: 5px;
            background: red;
            color: white;
            border-radius: 50%;
            width: 25px;
            height: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-weight: bold;
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
        .form-section {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container mt-3">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
    </div>

    <div class="container">
        <button type="button" class="btn btn-light text-dark mt-3" onclick="window.location.href='dashboard.php'">
            <i class="bi bi-arrow-left"></i> Back to Dashboard
        </button>
    </div>

    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="text-light">PCG Stations Management</h1>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#pcgModal">
                <i class="bi bi-plus-circle"></i> Add Station
            </button>
        </div>
        
        <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-4">
            <?php while ($pcg = $pcgs->fetch_assoc()): ?>
                <div class="col">
                    <div class="card h-100">
                        <img src="<?php echo htmlspecialchars($pcg['image'] ?: 'img/default-coast-guard.jpg'); ?>" 
                             class="card-img-top" 
                             alt="<?php echo htmlspecialchars($pcg['name']); ?>"
                            >
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><?php echo htmlspecialchars($pcg['name']); ?></h5>
                            <p class="card-text text-muted small">
                                <?php echo htmlspecialchars(substr($pcg['location'], 0, 50) . (strlen($pcg['location']) > 50 ? '...' : '')); ?>
                            </p>
                            <div class="mt-auto d-flex justify-content-between">
                                <a href="add_pcg.php?edit=<?php echo $pcg['id']; ?>" 
                                   class="btn btn-primary btn-sm">
                                   <i class="bi bi-pencil"></i> Edit
                                </a>
                                <a href="?delete=<?php echo $pcg['id']; ?>" 
                                   class="btn btn-danger btn-sm" 
                                   onclick="return confirm('Are you sure you want to delete this PCG station?')">
                                   <i class="bi bi-trash"></i> Delete
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <!-- Add/Edit PCG Modal -->
    <div class="modal fade <?php echo isset($_GET['edit']) ? 'show d-block' : ''; ?>" id="pcgModal" tabindex="-1" aria-labelledby="pcgModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="pcgModalLabel"><?php echo isset($_GET['edit']) ? 'Edit PCG Station' : 'Add PCG Station'; ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="add_pcg.php" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="<?php echo $pcgData['id']; ?>">

                        <div class="form-section">
                            <h5>Basic Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Station Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($pcgData['name']); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Details Page Link:</label>
                                        <input type="text" class="form-control" name="details_link" value="<?php echo htmlspecialchars($pcgData['details_link']); ?>" placeholder="e.g., pcg_details.php?id=1">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Description</label>
                                        <textarea class="form-control" name="description" rows="3"><?php echo htmlspecialchars($pcgData['description']); ?></textarea>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Emergency Hotlines</label>
                                        <textarea class="form-control" name="hotlines" rows="2"><?php echo htmlspecialchars($pcgData['hotlines']); ?></textarea>
                                        <small class="text-muted">Separate multiple hotlines with commas</small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Operating Hours</label>
                                        <textarea class="form-control" name="office_hours" rows="3" placeholder="e.g., Monday: 9AM - 5PM"><?php echo htmlspecialchars($pcgData['office_hours']); ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Last Updated</label>
                                        <input type="date" class="form-control" name="last_updated" value="<?php echo htmlspecialchars($pcgData['last_updated']); ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Contact Information</label>
                                        <input type="text" class="form-control" name="contact" value="<?php echo htmlspecialchars($pcgData['contact']); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($pcgData['email']); ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Services Offered</label>
                                        <textarea class="form-control" name="services" rows="2"><?php echo htmlspecialchars($pcgData['services']); ?></textarea>
                                        <small class="text-muted">Separate services with commas</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Requirements</label>
                                        <textarea class="form-control" name="requirements" rows="2"><?php echo htmlspecialchars($pcgData['requirements']); ?></textarea>
                                        <small class="text-muted">Separate requirements with commas</small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Officer in Charge</label>
                                        <input type="text" class="form-control" name="officer_in_charge" value="<?php echo htmlspecialchars($pcgData['officer_in_charge']); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Fees</label>
                                        <textarea class="form-control" name="fees" rows="2"><?php echo htmlspecialchars($pcgData['fees']); ?></textarea>
                                        <small class="text-muted">List of fees for services, if any</small>
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
                                        <input type="file" class="form-control" name="image" accept="image/*">
                                        <small class="text-muted">Max size: 5MB. Allowed: JPG, PNG, GIF</small>
                                        <?php if (!empty($pcgData['image'])): ?>
                                            <div class="mt-2">
                                                <img src="<?php echo htmlspecialchars($pcgData['image']); ?>" class="img-thumbnail" style="width:150px;height:150px;object-fit:cover;" onerror="this.src='img/default-coast-guard.jpg'">
                                                <input type="hidden" name="existing_image" value="<?php echo htmlspecialchars($pcgData['image']); ?>">
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Detail Images (Max <?php echo MAX_DETAIL_IMAGES; ?>)</label>
                                        <input type="file" class="form-control" name="image_detail[]" multiple accept="image/*">
                                        <?php if (!empty($pcgData['image_detail'])): ?>
                                            <div class="image-preview-container mt-2">
                                                <?php 
                                                    $images = explode(',', $pcgData['image_detail']);
                                                    foreach ($images as $img): 
                                                        if (!empty($img)):
                                                ?>
                                                <div class="image-preview-wrapper">
                                                    <img src="<?php echo htmlspecialchars($img); ?>" class="image-preview" onerror="this.src='img/default-coast-guard.jpg'">
                                                    <a href="?delete_image=<?php echo urlencode($img); ?>&pcg_id=<?php echo $pcgData['id']; ?>" class="delete-image-btn" onclick="return confirm('Are you sure you want to delete this image?')">&times;</a>
                                                </div>
                                                <?php 
                                                        endif;
                                                    endforeach; 
                                                ?>
                                                <input type="hidden" name="existing_image_detail" value="<?php echo htmlspecialchars($pcgData['image_detail']); ?>">
                                            </div>
                                        <?php endif; ?>
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
                                                        <option value="boat" <?php echo (isset($transportation_routes[$i]['type']) && $transportation_routes[$i]['type'] == 'boat') ? 'selected' : ''; ?>>Boat</option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control" name="route_name[]" value="<?php echo isset($transportation_routes[$i]['name']) ? htmlspecialchars($transportation_routes[$i]['name']) : ''; ?>" placeholder="e.g., Route 101, Boat Service">
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control" name="route_link[]" value="<?php echo isset($transportation_routes[$i]['link']) ? htmlspecialchars($transportation_routes[$i]['link']) : ''; ?>" placeholder="https://maps.google.com/...">
                                                </td>
                                                <td>
                                                    <textarea class="form-control" name="route_description[]" rows="2" placeholder="e.g., Coastal transportation, port access"><?php echo isset($transportation_routes[$i]['description']) ? htmlspecialchars($transportation_routes[$i]['description']) : ''; ?></textarea>
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
                                        <label class="form-label">Address</label>
                                        <textarea class="form-control" name="location" rows="3"><?php echo htmlspecialchars($pcgData['location']); ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Nearby Places</label>
                                        <input name="nearby_places" class="form-control" value="<?php echo htmlspecialchars($nearby_places_str); ?>" placeholder="Add nearby places separated by commas">
                                        <small class="text-muted">Example: Port, Marina, Lighthouse</small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Website</label>
                                        <input type="url" class="form-control" name="website" value="<?php echo htmlspecialchars($pcgData['website']); ?>">
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Google Maps Link</label>
                                        <input type="url" class="form-control" name="maps_link" value="<?php echo htmlspecialchars($pcgData['maps_link']); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Google Maps Directions Link</label>
                                        <input type="url" class="form-control" name="directions_link" value="<?php echo htmlspecialchars($pcgData['directions_link']); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Embed Google Map URL</label>
                                        <input type="url" class="form-control" name="map_embed" value="<?php echo htmlspecialchars($pcgData['maps_embed']); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <h5>Additional Resources</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Downloadable Forms/Resources</label>
                                        <textarea class="form-control" name="downloadables" rows="3"><?php echo htmlspecialchars($pcgData['downloadables']); ?></textarea>
                                        <small class="text-muted">List of downloadable resources or forms</small>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Facebook Link</label>
                                        <input type="url" class="form-control" name="facebook_link" value="<?php echo htmlspecialchars($pcgData['facebook_link']); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Other Social Media Links</label>
                                        <textarea class="form-control" name="social_media" rows="3"><?php echo htmlspecialchars($pcgData['social_media']); ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary"><?php echo isset($_GET['edit']) ? 'Update Station' : 'Add Station'; ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php if (isset($_GET['edit'])): ?>
        <div class="modal-backdrop fade show"></div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-show modal if editing
        <?php if (isset($_GET['edit'])): ?>
            document.addEventListener('DOMContentLoaded', function() {
                var modal = new bootstrap.Modal(document.getElementById('pcgModal'));
                modal.show();
            });
        <?php endif; ?>
        
        // Auto-close alerts after 5 seconds
        setTimeout(function() {
            var alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                var bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
        
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
                        <option value="boat">Boat</option>
                    </select>
                </td>
                <td>
                    <input type="text" class="form-control" name="route_name[]" placeholder="e.g., Route 101, Boat Service">
                </td>
                <td>
                    <input type="text" class="form-control" name="route_link[]" placeholder="https://maps.google.com/...">
                </td>
                <td>
                    <textarea class="form-control" name="route_description[]" rows="2" placeholder="e.g., Coastal transportation, port access"></textarea>
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