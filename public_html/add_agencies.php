<?php
session_start();
require 'conn.php';

define('UPLOAD_DIR', 'uploads/');
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif']);
define('MAX_IMAGE_SIZE', 2 * 1024 * 1024); 
define('MAX_DETAIL_IMAGES', 5);

define('DOCUMENT_UPLOAD_DIR', 'uploads/documents/');
define('ALLOWED_DOC_TYPES', ['pdf', 'doc', 'docx']);
define('MAX_DOC_SIZE', 5 * 1024 * 1024); 

if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}
if (!file_exists(DOCUMENT_UPLOAD_DIR)) {
    mkdir(DOCUMENT_UPLOAD_DIR, 0755, true);
}

if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    
    $stmt = $conn->prepare("SELECT image, image_detail FROM agencies WHERE id=?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $agency = $result->fetch_assoc();
    
    $stmt = $conn->prepare("DELETE FROM agencies WHERE id=?");
    $stmt->bind_param("i", $delete_id);
    
    if ($stmt->execute()) {
        if ($agency) {
            if (!empty($agency['image'])) {
                $image_path = UPLOAD_DIR . basename($agency['image']);
                if (file_exists($image_path)) {
                    unlink($image_path);
                }
            }
            
            if (!empty($agency['image_detail'])) {
                $images = explode(',', $agency['image_detail']);
                foreach ($images as $img) {
                    if (!empty($img)) {
                        $img_path = UPLOAD_DIR . basename($img);
                        if (file_exists($img_path)) {
                            unlink($img_path);
                        }
                    }
                }
            }
        }
        
        $stmt = $conn->prepare("SELECT file_path FROM agency_documents WHERE agency_id=?");
        $stmt->bind_param("i", $delete_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($doc = $result->fetch_assoc()) {
            if (!empty($doc['file_path']) && file_exists($doc['file_path'])) {
                unlink($doc['file_path']);
            }
        }
        
        $stmt = $conn->prepare("DELETE FROM agency_documents WHERE agency_id=?");
        $stmt->bind_param("i", $delete_id);
        $stmt->execute();
        
        $_SESSION['message'] = "Agency and all associated files deleted successfully";
    } else {
        $_SESSION['error'] = "Error deleting agency";
    }
    
    header("Location: add_agencies.php");
    exit();
}

if (isset($_GET['delete_image']) && isset($_GET['agency_id'])) {
    $image_path = $_GET['delete_image'];
    $agency_id = $_GET['agency_id'];
    $is_card_image = isset($_GET['is_card_image']) ? true : false;
    
    if (file_exists($image_path)) {
        unlink($image_path);
    }
    
    if ($is_card_image) {
        $stmt = $conn->prepare("UPDATE agencies SET image='' WHERE id=?");
        $stmt->bind_param("i", $agency_id);
        $stmt->execute();
    } else {
        $stmt = $conn->prepare("SELECT image_detail FROM agencies WHERE id=?");
        $stmt->bind_param("i", $agency_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $agency = $result->fetch_assoc();
        
        if ($agency) {
            $images = array_filter(explode(',', $agency['image_detail']));
            $updated_images = array_diff($images, [$image_path]);
            $updated_images_str = implode(',', $updated_images);
            
            $stmt = $conn->prepare("UPDATE agencies SET image_detail=? WHERE id=?");
            $stmt->bind_param("si", $updated_images_str, $agency_id);
            $stmt->execute();
        }
    }
    
    $_SESSION['message'] = "Image deleted successfully";
    header("Location: add_agencies.php?edit=" . $agency_id);
    exit();
}

if (isset($_GET['delete_document'])) {
    $doc_id = $_GET['delete_document'];
    $agency_id = $_GET['agency_id'] ?? '';
    
    $stmt = $conn->prepare("SELECT file_path FROM agency_documents WHERE id=?");
    $stmt->bind_param("i", $doc_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $document = $result->fetch_assoc();
    
    if ($document) {
        if (file_exists($document['file_path'])) {
            unlink($document['file_path']);
        }
        
        $stmt = $conn->prepare("DELETE FROM agency_documents WHERE id=?");
        $stmt->bind_param("i", $doc_id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "Document deleted successfully";
        } else {
            $_SESSION['error'] = "Error deleting document";
        }
    }
    
    if ($agency_id) {
        header("Location: add_agencies.php?edit=" . $agency_id);
    } else {
        header("Location: add_agencies.php");
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_document'])) {
    $agency_id = $_POST['agency_id'];
    $document_name = $_POST['document_name'];
    
    if (isset($_FILES['document_file'])) {
        $file = $_FILES['document_file'];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (in_array($file_ext, ALLOWED_DOC_TYPES)) {
            if ($file['size'] <= MAX_DOC_SIZE) {
                $new_filename = uniqid('doc_', true) . '.' . $file_ext;
                $target_path = DOCUMENT_UPLOAD_DIR . $new_filename;
                
                if (move_uploaded_file($file['tmp_name'], $target_path)) {
                    $stmt = $conn->prepare("INSERT INTO agency_documents (agency_id, document_name, file_path) VALUES (?, ?, ?)");
                    $stmt->bind_param("iss", $agency_id, $document_name, $target_path);
                    
                    if ($stmt->execute()) {
                        $_SESSION['message'] = "Document uploaded successfully";
                    } else {
                        $_SESSION['error'] = "Error saving document to database";
                    }
                } else {
                    $_SESSION['error'] = "Error uploading file";
                }
            } else {
                $_SESSION['error'] = "File size exceeds maximum limit (5MB)";
            }
        } else {
            $_SESSION['error'] = "Only PDF and Word documents are allowed";
        }
    }
    
    header("Location: add_agencies.php?edit=" . $agency_id);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['upload_document'])) {
    $id = isset($_POST['id']) ? $_POST['id'] : null;
    $name = $_POST['name'];
    $description = $_POST['description'];
    $hours = $_POST['hours'] ?? '';
    $contact = $_POST['contact'] ?? '';
    $location = $_POST['location'];
    $website = $_POST['website'];
    $maps_link = $_POST['maps_link'];
    $directions_link = $_POST['directions_link'];
    $mapEmbedURL = $_POST['map_embed'];
    $services = $_POST['services'];
    $eligibility = $_POST['eligibility'];
    $required_docs = $_POST['required_docs'];
    $phone = $_POST['phone'] ?? '';
    $email = $_POST['email'] ?? '';
    $social_media = $_POST['social_media'] ?? '';
    $nearby_places = $_POST['nearby_places'] ?? '';
    $details_link = $_POST['details_link'] ?? '';

    $nearby_places_array = array_map('trim', explode(',', $nearby_places));
    $nearby_places_json = json_encode(array_filter($nearby_places_array));

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

    $target_file = $_POST['existing_image'] ?? '';
    
    if (isset($_FILES['image']['name']) && $_FILES['image']['name']) {
        $file = $_FILES['image'];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (in_array($file_ext, ALLOWED_IMAGE_TYPES)) {
            if ($file['size'] <= MAX_IMAGE_SIZE) {
                $new_filename = uniqid('img_', true) . '.' . $file_ext;
                $target_file = UPLOAD_DIR . $new_filename;
                
                if (move_uploaded_file($file['tmp_name'], $target_file)) {
                    if (!empty($_POST['existing_image']) && file_exists($_POST['existing_image'])) {
                        unlink($_POST['existing_image']);
                    }
                }
            }
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
        for ($i = 0; $i < count($_FILES['image_detail']['name']); $i++) {
            if ($_FILES['image_detail']['error'][$i] !== UPLOAD_ERR_OK) {
                continue;
            }
            
            $file = [
                'name' => $_FILES['image_detail']['name'][$i],
                'type' => $_FILES['image_detail']['type'][$i],
                'tmp_name' => $_FILES['image_detail']['tmp_name'][$i],
                'error' => $_FILES['image_detail']['error'][$i],
                'size' => $_FILES['image_detail']['size'][$i]
            ];
            
            $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            if (in_array($file_ext, ALLOWED_IMAGE_TYPES) && $file['size'] <= MAX_IMAGE_SIZE) {
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
    
    if ($id) {
        $query = "UPDATE agencies SET name=?, image=?, image_detail=?, description=?, location=?, maps_embed=?,
         maps_link=?, website=?, directions_link=?, hours=?, contact=?, services=?, eligibility=?,
          required_docs=?, phone=?, email=?, nearby_places=?, social_media=?, details_link=?, 
         transportation_routes=? WHERE id=?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssssssssssssssssssi", 
            $name, $target_file, $target_file_detail, $description, $location, $mapEmbedURL, $maps_link, 
            $website, $directions_link, $hours, $contact, $services, $eligibility, $required_docs, $phone,
             $email, $nearby_places_json, $social_media, $details_link, $transportation_routes_json, $id);
    } else {
        $query = "INSERT INTO agencies (name, image, image_detail, description, location, maps_embed, maps_link,
         website, directions_link, hours, contact, services, eligibility, 
          required_docs, phone, email, nearby_places, social_media, details_link,  transportation_routes) 
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssssssssssssssssss", 
            $name, $target_file, $target_file_detail, $description, $location, $mapEmbedURL, $maps_link, 
            $website, $directions_link, $hours, $contact, $services, $eligibility, $required_docs, $phone, 
            $email, $nearby_places_json, $social_media, $details_link, $transportation_routes_json);
    }
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Agency " . ($id ? "updated" : "added") . " successfully";
    } else {
        $_SESSION['error'] = "Error saving agency: " . $stmt->error;
    }
    
    header("Location: add_agencies.php");
    exit();
}

$query = "SELECT id, name, image, description, location, maps_embed, directions_link, maps_link, website, hours, contact, services, eligibility, required_docs, phone, email, nearby_places, social_media, details_link, transportation_routes FROM agencies ORDER BY name ASC";
$stmt = $conn->prepare($query);
$stmt->execute();
$agencies = $stmt->get_result();

$agency_documents = [];
$nearby_places_str = '';
$transportation_routes = array();

if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM agency_documents WHERE agency_id=?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $agency_documents = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $stmt = $conn->prepare("SELECT nearby_places, transportation_routes FROM agencies WHERE id=?");
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
    <title>Agencies Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        .card-image-preview {
            max-width: 200px;
            max-height: 200px;
            object-fit: contain;
        }
        .modal-backdrop.show {
            opacity: 0.8;
        }
        .document-card {
            transition: transform 0.3s;
            border-radius: 10px;
            overflow: hidden;
        }
        .document-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .document-icon {
            font-size: 3rem;
            color: #0d6efd;
        }
        .document-container {
            max-height: 300px;
            overflow-y: auto;
        }
        .transport-table-container {
            overflow-x: auto;
        }
        .route-row {
            vertical-align: middle;
        }
        .action-buttons {
            white-space: nowrap;
        }
        .transport-icon {
            width: 30px;
            height: 30px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            margin-right: 8px;
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
            <h1 class="text-light">Agencies Management</h1>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#agencyModal">
                <i class="bi bi-plus-lg"></i> Add Agency
            </button>
        </div>
        
        <?php if ($agencies->num_rows == 0): ?>
            <div class="alert alert-info">
                No agencies found. Click "Add Agency" to create one.
            </div>
        <?php else: ?>
            <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-4">
                <?php while ($agency = $agencies->fetch_assoc()): ?>
                    <div class="col">
                        <div class="card h-100">
                            <?php if (!empty($agency['image'])): ?>
                                <img src="<?php echo $agency['image']; ?>" class="card-img-top" alt="<?php echo $agency['name']; ?>">
                            <?php else: ?>
                                <div class="card-img-top bg-secondary d-flex align-items-center justify-content-center">
                                    <i class="bi bi-building text-white" style="font-size: 3rem;"></i>
                                </div>
                            <?php endif; ?>
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?php echo $agency['name']; ?></h5>
                                <p class="card-text flex-grow-1"><?php echo substr($agency['description'], 0, 100) . '...'; ?></p>
                                <div class="d-flex justify-content-between">
                                    <a href="add_agencies.php?edit=<?php echo $agency['id']; ?>" class="btn btn-primary btn-sm">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    <a href="?delete=<?php echo $agency['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this agency? All associated files will also be deleted.')">
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

    <div class="modal fade <?php echo isset($_GET['edit']) ? 'show d-block' : ''; ?>" id="agencyModal" tabindex="-1" aria-labelledby="agencyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="agencyModalLabel"><?php echo isset($_GET['edit']) ? 'Edit Agency' : 'Add Agency'; ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php 
                        $agencyData = [
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
                            'services' => '',
                            'eligibility' => '',
                            'required_docs' => '',
                            'phone' => '',
                            'email' => '',
                            'social_media' => '',
                            'nearby_places' => '',
                            'details_link' => '',
                            'social_media' => '',
                            'transportation_routes' => ''
                        ];
                        
                        if (isset($_GET['edit'])) {
                            $edit_id = $_GET['edit'];
                            $stmt = $conn->prepare("SELECT * FROM agencies WHERE id=?");
                            $stmt->bind_param("i", $edit_id);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            if ($result->num_rows > 0) {
                                $agencyData = $result->fetch_assoc();
                            }
                        }
                    ?>
                    <form method="POST" action="add_agencies.php" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="<?php echo $agencyData['id']; ?>">
                        
                        <div class="form-section">
                            <h5>Basic Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Name *</label>
                                        <input type="text" class="form-control" name="name" value="<?php echo $agencyData['name']; ?>" >
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Description *</label>
                                        <textarea class="form-control" name="description" rows="4"><?php echo $agencyData['description']; ?></textarea>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Details Page Link:</label>
                                        <input type="text" class="form-control" name="details_link" value="<?php echo htmlspecialchars($agencyData['details_link']); ?>" placeholder="e.g., historical_details.php?id=1">
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Phone</label>
                                        <input type="text" class="form-control" name="phone" value="<?php echo $agencyData['phone']; ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" name="email" value="<?php echo $agencyData['email']; ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Operating Hours:</label>
                                        <textarea class="form-control" name="hours" rows="5" placeholder="Monday: 9AM - 5PM"><?php echo htmlspecialchars($agencyData['hours']); ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h5>Images</h5>
                            <div class="mb-3">
                                <label class="form-label">Card Image (Main Display Image)</label>
                                <input type="file" class="form-control" name="image" accept="image/*">
                                
                                <?php if (!empty($agencyData['image'])): ?>
                                    <div class="mt-3">
                                        <p>Current Image:</p>
                                        <img src="<?php echo $agencyData['image']; ?>" class="img-thumbnail card-image-preview">
                                        <div class="form-check mt-2">
                                            <input class="form-check-input" type="checkbox" name="delete_image" value="1" id="deleteImageCheck">
                                            <label class="form-check-label" for="deleteImageCheck">Delete current image</label>
                                        </div>
                                        <input type="hidden" name="existing_image" value="<?php echo $agencyData['image']; ?>">
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Detail Images (Max <?php echo MAX_DETAIL_IMAGES; ?>)</label>
                                <input type="file" class="form-control" name="image_detail[]" multiple accept="image/*">
                                <small class="text-muted">Upload additional images to display on the agency details page</small>
                                
                                <?php if (!empty($agencyData['image_detail'])): ?>
                                    <div class="mt-3">
                                        <p>Current Detail Images:</p>
                                        <div class="d-flex flex-wrap gap-2">
                                            <?php 
                                            $images = explode(',', $agencyData['image_detail']);
                                            foreach ($images as $img): 
                                                if (!empty($img)):
                                            ?>
                                                <div class="position-relative">
                                                    <img src="<?php echo $img; ?>" class="img-thumbnail" style="width: 100px; height: 100px; object-fit: cover;">
                                                    <a href="?delete_image=<?php echo urlencode($img); ?>&agency_id=<?php echo $agencyData['id']; ?>" 
                                                       class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" 
                                                       onclick="return confirm('Are you sure you want to delete this image?')">
                                                       ×
                                                    </a>
                                                </div>
                                            <?php 
                                                endif;
                                            endforeach; 
                                            ?>
                                        </div>
                                        <input type="hidden" name="existing_image_detail" value="<?php echo $agencyData['image_detail']; ?>">
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="form-section">
                            <h5>Location Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Address *</label>
                                        <input type="text" class="form-control" name="location" value="<?php echo $agencyData['location']; ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Website URL</label>
                                        <input type="url" class="form-control" name="website" value="<?php echo $agencyData['website']; ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Social Media Links</label>
                                        <textarea class="form-control" name="social_media" rows="5"><?php echo isset($agencyData['social_media']) ? htmlspecialchars($agencyData['social_media']) : ''; ?></textarea>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Google Maps Directions Link</label>
                                        <input type="url" class="form-control" name="directions_link" value="<?php echo $agencyData['directions_link']; ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Google Map Link</label>
                                        <input type="url" class="form-control" name="maps_link" value="<?php echo $agencyData['maps_link']; ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Embed Google Map URL</label>
                                <input type="url" class="form-control" name="map_embed" value="<?php echo $agencyData['maps_embed']; ?>">
                                <small class="text-muted">Use the "Share" > "Embed a map" option from Google Maps</small>
                            </div>
                            
                            <div class="mb-3"> 
                                <label class="form-label">Nearby Places:</label>
                                <input name="nearby_places" class="form-control" value="<?= $nearby_places_str ?? ''; ?>" placeholder="Add nearby places separated by commas">
                                <small class="text-muted">Example: Museum, Park, Shopping Mall</small>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h5>Transportation Routes</h5>
                            <div class="transport-table-container">
                                <table class="table table-bordered" id="transportTable">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Transport Type</th>
                                            <th>Route Name/Number</th>
                                            <th>Route Link</th>
                                            <th>Description</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="transportTableBody">
                                        <?php if (!empty($transportation_routes)): ?>
                                            <?php foreach ($transportation_routes as $index => $route): ?>
                                                <tr class="route-row" data-index="<?php echo $index; ?>">
                                                    <td><?php echo $index + 1; ?></td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <span class="transport-icon <?php echo $route['type']; ?>-icon"></span>
                                                            <select class="form-control form-control-sm" name="route_type[]">
                                                                <option value="jeepney" <?php echo ($route['type'] == 'jeepney') ? 'selected' : ''; ?>>Jeepney</option>
                                                                <option value="bus" <?php echo ($route['type'] == 'bus') ? 'selected' : ''; ?>>Bus</option>
                                                                <option value="taxi" <?php echo ($route['type'] == 'taxi') ? 'selected' : ''; ?>>Taxi</option>
                                                                <option value="tricycle" <?php echo ($route['type'] == 'tricycle') ? 'selected' : ''; ?>>Tricycle</option>
                                                            </select>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control form-control-sm" name="route_name[]" value="<?php echo $route['name']; ?>">
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control form-control-sm" name="route_link[]" value="<?php echo $route['link'] ?? ''; ?>">
                                                    </td>
                                                    <td>
                                                        <textarea class="form-control form-control-sm" name="route_description[]" rows="1"><?php echo $route['description'] ?? ''; ?></textarea>
                                                    </td>
                                                    <td class="action-buttons">
                                                        <button type="button" class="btn btn-danger btn-sm" onclick="removeTransportRow(this)">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr class="route-row" data-index="0">
                                                <td>1</td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <span class="transport-icon jeepney-icon"></span>
                                                        <select class="form-control form-control-sm" name="route_type[]">
                                                            <option value="jeepney">Jeepney</option>
                                                            <option value="bus">Bus</option>
                                                            <option value="taxi">Taxi</option>
                                                            <option value="tricycle">Tricycle</option>
                                                        </select>
                                                    </div>
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control form-control-sm" name="route_name[]">
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control form-control-sm" name="route_link[]">
                                                </td>
                                                <td>
                                                    <textarea class="form-control form-control-sm" name="route_description[]" rows="1"></textarea>
                                                </td>
                                                <td class="action-buttons">
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
                            <h5>Service Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Services *</label>
                                        <textarea class="form-control" name="services" rows="4" ><?php echo $agencyData['services']; ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Eligibility Requirements *</label>
                                        <textarea class="form-control" name="eligibility" rows="4" ><?php echo $agencyData['eligibility']; ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Required Documents</label>
                                        <textarea class="form-control" name="required_docs" rows="3"><?php echo $agencyData['required_docs']; ?></textarea>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Contact Information</label>
                                        <input type="text" class="form-control" name="contact" value="<?php echo $agencyData['contact']; ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h5>Documents</h5>
                            <div class="document-container mb-3">
                                <?php if (!empty($agency_documents)): ?>
                                    <div class="row row-cols-1 row-cols-md-2 g-4">
                                        <?php foreach ($agency_documents as $doc): ?>
                                            <div class="col">
                                                <div class="card document-card h-100">
                                                    <div class="card-body text-center">
                                                        <?php 
                                                        $ext = pathinfo($doc['file_path'], PATHINFO_EXTENSION);
                                                        $icon = $ext == 'pdf' ? 'file-pdf' : 'file-word';
                                                        ?>
                                                        <i class="fas fa-<?php echo $icon; ?> document-icon mb-3"></i>
                                                        <h5 class="card-title"><?php echo htmlspecialchars($doc['document_name']); ?></h5>
                                                        <p class="text-muted small">
                                                            <?php echo strtoupper($ext) . " Document"; ?>
                                                        </p>
                                                    </div>
                                                    <div class="card-footer bg-transparent d-flex justify-content-between">
                                                        <a href="<?php echo $doc['file_path']; ?>" class="btn btn-primary btn-sm" download>
                                                            <i class="fas fa-download"></i> Download
                                                        </a>
                                                        <a href="?agency_id=<?php echo $_GET['edit']; ?>&delete_document=<?php echo $doc['id']; ?>" 
                                                           class="btn btn-danger btn-sm" 
                                                           onclick="return confirm('Are you sure you want to delete this document?')">
                                                            <i class="fas fa-trash"></i> Delete
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info">No documents available for this agency.</div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <?php echo isset($_GET['edit']) ? 'Update Agency' : 'Save Agency'; ?>
                            </button>
                        </div>
                    </form>
                    
                    <div class="form-section mt-4">
                        <div class="card">
                            <div class="card-header">
                                <h6>Upload New Document</h6>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="add_agencies.php" enctype="multipart/form-data">
                                    <input type="hidden" name="agency_id" value="<?php echo $agencyData['id']; ?>">
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Document Name</label>
                                        <input type="text" class="form-control" name="document_name" >
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Document File</label>
                                        <input type="file" class="form-control" name="document_file" accept=".pdf,.doc,.docx" >
                                    </div>
                                    
                                    <button type="submit" name="upload_document" class="btn btn-primary">
                                        <i class="fas fa-upload"></i> Upload Document
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (isset($_GET['edit'])): ?>
        <div class="modal-backdrop fade show"></div>
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
                var modal = new bootstrap.Modal(document.getElementById('agencyModal'));
                modal.show();
                
                history.replaceState(null, null, window.location.pathname);
            }

            const selects = document.querySelectorAll('select[name="route_type[]"]');
            selects.forEach(select => {
                select.addEventListener('change', function() {
                    updateTransportIcons();
                });
            });
        });

        function updateTransportIcons() {
            const rows = document.querySelectorAll('#transportTableBody .route-row');
            rows.forEach((row, index) => {
                const rowNumber = row.querySelector('td:first-child');
                rowNumber.textContent = index + 1;
                
                const select = row.querySelector('select[name="route_type[]"]');
                const iconSpan = row.querySelector('.transport-icon');
                
                const type = select.value;
                iconSpan.className = `transport-icon ${type}-icon`;
            });
        }

        function addTransportRow() {
            const tbody = document.getElementById('transportTableBody');
            const rows = tbody.querySelectorAll('.route-row');
            const newIndex = rows.length;
            
            const newRow = document.createElement('tr');
            newRow.className = 'route-row';
            newRow.dataset.index = newIndex;
            
            newRow.innerHTML = `
                <td>${newIndex + 1}</td>
                <td>
                    <div class="d-flex align-items-center">
                        <span class="transport-icon jeepney-icon"></span>
                        <select class="form-control form-control-sm" name="route_type[]">
                            <option value="jeepney">Jeepney</option>
                            <option value="bus">Bus</option>
                            <option value="taxi">Taxi</option>
                            <option value="tricycle">Tricycle</option>
                        </select>
                    </div>
                </td>
                <td>
                    <input type="text" class="form-control form-control-sm" name="route_name[]">
                </td>
                <td>
                    <input type="text" class="form-control form-control-sm" name="route_link[]">
                </td>
                <td>
                    <textarea class="form-control form-control-sm" name="route_description[]" rows="1"></textarea>
                </td>
                <td class="action-buttons">
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeTransportRow(this)">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            `;
            
            tbody.appendChild(newRow);
            
            const select = newRow.querySelector('select[name="route_type[]"]');
            select.addEventListener('change', function() {
                updateTransportIcons();
            });
        }

        function removeTransportRow(button) {
            const row = button.closest('.route-row');
            row.remove();
            updateTransportIcons();
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>