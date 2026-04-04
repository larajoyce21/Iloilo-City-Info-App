<?php
include 'conn.php';

$check_column_query = "SHOW COLUMNS FROM colleges LIKE 'bike_parking'";
$result = $conn->query($check_column_query);
if ($result->num_rows == 0) {
    $alter_query = "ALTER TABLE colleges ADD COLUMN bike_parking VARCHAR(255) AFTER details_link";
    $conn->query($alter_query);
}

$check_column_query = "SHOW COLUMNS FROM colleges LIKE 'transportation_routes'";
$result = $conn->query($check_column_query);
if ($result->num_rows == 0) {
    $alter_query = "ALTER TABLE colleges ADD COLUMN transportation_routes TEXT AFTER transportation";
    $conn->query($alter_query);
}

if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM colleges WHERE id=?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    header("Location: add_colleges.php");
    exit();
}

if (isset($_GET['delete_image']) && isset($_GET['college_id'])) {
    $college_id = $_GET['college_id'];
    $image_to_delete = $_GET['delete_image'];
    
    $stmt = $conn->prepare("SELECT image_detail FROM colleges WHERE id=?");
    $stmt->bind_param("i", $college_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $images = explode(',', $row['image_detail']);
    
    $new_images = array_diff($images, [$image_to_delete]);
    $new_images_str = implode(',', $new_images);
    
    $stmt = $conn->prepare("UPDATE colleges SET image_detail=? WHERE id=?");
    $stmt->bind_param("si", $new_images_str, $college_id);
    $stmt->execute();
    
    if (file_exists($image_to_delete)) {
        unlink($image_to_delete);
    }
    
    header("Location: add_colleges.php?edit=$college_id");
    exit();
}

$query = "SELECT * FROM colleges";
$result = $conn->query($query);
$colleges = $result->fetch_all(MYSQLI_ASSOC);

$collegeData = [
    'id' => '', 'name' => '', 'description' => '', 'history' => '', 'accreditation' => '', 
    'programs' => '', 'hours' => '', 'fee' => '', 'contact' => '', 'email' => '', 
    'location' => '', 'website' => '', 'image' => '', 'maps_embed' => '', 'maps_link' => '', 
    'directions_link' => '', 'transportation' => '', 'nearby_places' => '', 'social_media' => '', 
    'source' => '', 'year_founded' => '', 'type' => '', 'students' => '', 
    'admission' => '', 'details_link' => '', 'bike_parking' => '', 'transportation_routes' => ''
];

$transportation_routes = array();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $description = $_POST['description'];
    $history = $_POST['history'];
    $accreditation = $_POST['accreditation'];
    $programs = $_POST['programs'];
    $hours = $_POST['hours'];
    $fee = $_POST['fee'];
    $contact = $_POST['contact'];
    $email = $_POST['email'];
    $location = $_POST['location'];
    $website = $_POST['website'];
    $maps_link = $_POST['maps_link'];
    $directions_link = $_POST['directions_link'];
    $transportation = $_POST['transportation'];
    $nearby_places = $_POST['nearby_places'];
    $social_media = $_POST['social_media'];
    $source = $_POST['source'];
    $year_founded = $_POST['year_founded'];
    $type = $_POST['type'];
    $students = $_POST['students'];
    $admission = $_POST['admission'];
    $details_link = $_POST['details_link'];
    $mapEmbedURL = $_POST['map_embed'];
    $bike_parking = $_POST['bike_parking'];

    $transportation_routes = array();
    if (isset($_POST['route_type']) && is_array($_POST['route_type'])) {
        foreach ($_POST['route_type'] as $index => $type) {
            if (!empty($type) && !empty($_POST['route_name'][$index])) {
                $transportation_routes[] = array(
                    'type' => $type,
                    'name' => $_POST['route_name'][$index],
                    'link' => $_POST['route_link'][$index],
                    'description' => $_POST['route_description'][$index]
                );
            }
        }
    }
    $transportation_routes_json = json_encode($transportation_routes);

    $target_dir = "uploads/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];

    $target_file = $_POST['existing_image'];
    if (!empty($_FILES['image']['name'])) {
        $target_file = $target_dir . basename($_FILES["image"]["name"]);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        if (in_array($imageFileType, $allowed_types)) {
            move_uploaded_file($_FILES["image"]["tmp_name"], $target_file);
        }
    }

    $imagePaths = [];
    $existingImages = !empty($_POST['existing_image_detail']) ? explode(',', $_POST['existing_image_detail']) : [];
    
    if (!empty($_FILES['image_detail']['name'][0])) {
        $totalNew = count($_FILES['image_detail']['name']);
        $totalCombined = count($existingImages) + $totalNew;
    
        if ($totalCombined > 5) {
            die("Error: You can upload a maximum of 5 detail images total.");
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
    
    $hours = str_replace(["\r\n", "\r"], "\n", $hours);
    $hours_lines = explode("\n", $hours);
    $formatted_hours = [];
    foreach ($hours_lines as $line) {
        $trimmed_line = trim($line);
        if (!empty($trimmed_line)) {
            $formatted_hours[] = $trimmed_line;
        }
    }
    $hours = implode("\n", $formatted_hours);
    
    if ($id) {
        $query = "UPDATE colleges SET name=?, image=?, image_detail=?, description=?, history=?, accreditation=?, programs=?, hours=?, fee=?, contact=?, email=?, location=?, website=?, maps_embed=?, maps_link=?, directions_link=?, transportation=?, nearby_places=?, social_media=?, source=?, year_founded=?, type=?, students=?, admission=?, details_link=?, bike_parking=?, transportation_routes=? WHERE id=?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssssssssssssssssssssssssssi", 
            $name, $target_file, $target_file_detail, $description, $history, $accreditation, 
            $programs, $hours, $fee, $contact, $email, $location, $website, $mapEmbedURL, 
            $maps_link, $directions_link, $transportation, $nearby_places, $social_media, 
            $source, $year_founded, $type, $students, $admission, $details_link, $bike_parking, $transportation_routes_json, $id);
    } else {
        $query = "INSERT INTO colleges (name, image, image_detail, description, history, accreditation, programs, hours, fee, contact, email, location, website, maps_embed, maps_link, directions_link, transportation, nearby_places, social_media, source, year_founded, type, students, admission, details_link, bike_parking, transportation_routes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssssssssssssssssssssssssss", 
            $name, $target_file, $target_file_detail, $description, $history, $accreditation, 
            $programs, $hours, $fee, $contact, $email, $location, $website, $mapEmbedURL, 
            $maps_link, $directions_link, $transportation, $nearby_places, $social_media, 
            $source, $year_founded, $type, $students, $admission, $details_link, $bike_parking, $transportation_routes_json);
    }

    $stmt->execute();
    header("Location: add_colleges.php");
    exit();
}

if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM colleges WHERE id=?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $collegeData = $result->fetch_assoc();
        if (!isset($collegeData['bike_parking'])) {
            $collegeData['bike_parking'] = '';
        }
        
        if (!empty($collegeData['transportation_routes'])) {
            $decoded_routes = json_decode($collegeData['transportation_routes'], true);
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
    <title>Colleges and Universities Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
            transition: transform 0.3s;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .card-img-top {
            height: 150px;
            object-fit: cover;
        }
        .modal-content {
            border-radius: 15px;
        }
        .btn-light {
            background-color: #f8f9fa;
            border-color: #f8f9fa;
        }
        .form-section {
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: #f8f9fa;
            border-radius: 10px;
        }
        .hours-example {
            font-size: 0.875rem;
            color: #6c757d;
            margin-top: 0.25rem;
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
    </style>
</head>
<body>
    <div class="container mt-4">
        <button type="button" class="btn btn-light text-dark mb-3" onclick="window.location.href='dashboard.php'">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </button>
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="text-light">Colleges and Universities Management</h1>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#collegeModal">
                <i class="fas fa-plus"></i> Add College/University
            </button>
        </div>

        <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-4">
            <?php foreach ($colleges as $college): ?>
                <div class="col">
                    <div class="card h-100">
                        <img src="<?php echo $college['image']; ?>" class="card-img-top" alt="<?php echo $college['name']; ?>">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo $college['name']; ?></h5>
                        </div>
                        <div class="card-footer bg-transparent">
                            <div class="d-flex justify-content-between">
                                <a href="add_colleges.php?edit=<?php echo $college['id']; ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="?delete=<?php echo $college['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="modal fade <?php echo isset($_GET['edit']) ? 'show d-block' : ''; ?>" id="collegeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo isset($_GET['edit']) ? 'Edit College/University' : 'Add College/University'; ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="add_colleges.php" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="<?php echo $collegeData['id']; ?>">
                        
                        <div class="form-section">
                            <h4 class="section-title">Basic Information</h4>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Name *</label>
                                        <input type="text" class="form-control" name="name" value="<?php echo $collegeData['name']; ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Description</label>
                                        <textarea class="form-control" name="description" rows="3"><?php echo $collegeData['description']; ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">History</label>
                                        <textarea class="form-control" name="history" rows="3"><?php echo $collegeData['history']; ?></textarea>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Card Image *</label>
                                        <input type="file" class="form-control" name="image">
                                        <?php if ($collegeData['image']): ?>
                                            <img src="<?php echo $collegeData['image']; ?>" class="img-thumbnail mt-2" width="150">
                                            <input type="hidden" name="existing_image" value="<?php echo $collegeData['image']; ?>">
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Detail Images (2-5 images)</label>
                                        <input type="file" class="form-control" name="image_detail[]" multiple accept="image/*">
                                        
                                        <?php if (!empty($collegeData['image_detail'])): ?>
                                            <div class="d-flex flex-wrap mt-2">
                                                <?php 
                                                $images = explode(',', $collegeData['image_detail']); 
                                                foreach ($images as $img): 
                                                    if (!empty($img)): ?>
                                                        <div class="position-relative me-2 mb-2">
                                                            <img src="<?php echo $img; ?>" class="img-thumbnail" width="100">
                                                            <a href="?delete_image=<?php echo urlencode($img); ?>&college_id=<?php echo $collegeData['id']; ?>" 
                                                               class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" 
                                                               onclick="return confirm('Are you sure you want to delete this image?')">
                                                               ×
                                                            </a>
                                                        </div>
                                                    <?php endif;
                                                endforeach; ?>
                                            </div>
                                            <input type="hidden" name="existing_image_detail" value="<?php echo $collegeData['image_detail']; ?>">
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h4 class="section-title">Academic Information</h4>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Year Founded</label>
                                        <input type="text" class="form-control" name="year_founded" value="<?php echo $collegeData['year_founded']; ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Type</label>
                                        <input type="text" class="form-control" name="type" value="<?php echo $collegeData['type']; ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Student Population</label>
                                        <input type="text" class="form-control" name="students" value="<?php echo $collegeData['students']; ?>">
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Accreditation</label>
                                        <textarea class="form-control" name="accreditation" rows="3"><?php echo $collegeData['accreditation']; ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Academic Programs</label>
                                        <textarea class="form-control" name="programs" rows="3"><?php echo $collegeData['programs']; ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Admission Information</label>
                                        <textarea class="form-control" name="admission" rows="3"><?php echo $collegeData['admission']; ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h4 class="section-title">Contact Information</h4>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Address</label>
                                        <input type="text" class="form-control" name="location" value="<?php echo $collegeData['location']; ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Contact Number</label>
                                        <input type="text" class="form-control" name="contact" value="<?php echo $collegeData['contact']; ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="text" class="form-control" name="email" value="<?php echo $collegeData['email']; ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Website</label>
                                        <input type="text" class="form-control" name="website" value="<?php echo $collegeData['website']; ?>">
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Operating Hours (one per line)</label>
                                        <textarea class="form-control" name="hours" rows="4" placeholder="Monday: 9AM - 5PM"><?php echo $collegeData['hours']; ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Tuition Fees</label>
                                        <textarea class="form-control" name="fee" rows="3"><?php echo $collegeData['fee']; ?></textarea>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Bike Parking</label>
                                        <input type="text" class="form-control" name="bike_parking" value="<?php echo $collegeData['bike_parking']; ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h4 class="section-title">Location & Maps</h4>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Google Maps Link</label>
                                        <input type="text" class="form-control" name="maps_link" value="<?php echo $collegeData['maps_link']; ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Embed Google Map URL</label>
                                        <input type="text" class="form-control" name="map_embed" value="<?php echo $collegeData['maps_embed']; ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Directions Link</label>
                                        <input type="text" class="form-control" name="directions_link" value="<?php echo $collegeData['directions_link']; ?>">
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Transportation Options (one per line)</label>
                                        <textarea class="form-control" name="transportation" rows="3"><?php echo $collegeData['transportation']; ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Nearby Places (comma separated)</label>
                                        <input type="text" class="form-control" name="nearby_places" value="<?php echo $collegeData['nearby_places']; ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h4 class="section-title">Transportation Routes</h4>
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
                                                <tr class="route-row">
                                                    <td><?php echo $index + 1; ?></td>
                                                    <td>
                                                        <select class="form-control form-control-sm" name="route_type[]">
                                                            <option value="jeepney" <?php echo ($route['type'] == 'jeepney') ? 'selected' : ''; ?>>Jeepney</option>
                                                            <option value="bus" <?php echo ($route['type'] == 'bus') ? 'selected' : ''; ?>>Bus</option>
                                                            <option value="taxi" <?php echo ($route['type'] == 'taxi') ? 'selected' : ''; ?>>Taxi</option>
                                                            <option value="tricycle" <?php echo ($route['type'] == 'tricycle') ? 'selected' : ''; ?>>Tricycle</option>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control form-control-sm" name="route_name[]" value="<?php echo htmlspecialchars($route['name']); ?>">
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control form-control-sm" name="route_link[]" value="<?php echo htmlspecialchars($route['link'] ?? ''); ?>">
                                                    </td>
                                                    <td>
                                                        <textarea class="form-control form-control-sm" name="route_description[]" rows="1"><?php echo htmlspecialchars($route['description'] ?? ''); ?></textarea>
                                                    </td>
                                                    <td class="action-buttons">
                                                        <button type="button" class="btn btn-danger btn-sm" onclick="this.closest('tr').remove(); updateRouteNumbers();">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr class="route-row">
                                                <td>1</td>
                                                <td>
                                                    <select class="form-control form-control-sm" name="route_type[]">
                                                        <option value="jeepney">Jeepney</option>
                                                        <option value="bus">Bus</option>
                                                        <option value="taxi">Taxi</option>
                                                        <option value="tricycle">Tricycle</option>
                                                    </select>
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
                                                    <button type="button" class="btn btn-danger btn-sm" onclick="this.closest('tr').remove(); updateRouteNumbers();">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <button type="button" class="btn btn-secondary btn-sm mt-2" onclick="addTransportRoute()">
                                <i class="fas fa-plus"></i> Add Another Route
                            </button>
                        </div>
                        
                        <div class="form-section">
                            <h4 class="section-title">Additional Information</h4>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Social Media Links (one per line)</label>
                                        <textarea class="form-control" name="social_media" rows="3"><?php echo $collegeData['social_media']; ?></textarea>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Source/Reference</label>
                                        <input type="text" class="form-control" name="source" value="<?php echo $collegeData['source']; ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Details Page Link</label>
                                        <input type="text" class="form-control" name="details_link" value="<?php echo $collegeData['details_link']; ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-success"><?php echo isset($_GET['edit']) ? 'Update' : 'Save'; ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function addTransportRoute() {
            const tbody = document.getElementById('transportTableBody');
            const rows = tbody.querySelectorAll('.route-row');
            const newIndex = rows.length + 1;
            
            const newRow = document.createElement('tr');
            newRow.className = 'route-row';
            
            newRow.innerHTML = `
                <td>${newIndex}</td>
                <td>
                    <select class="form-control form-control-sm" name="route_type[]">
                        <option value="jeepney">Jeepney</option>
                        <option value="bus">Bus</option>
                        <option value="taxi">Taxi</option>
                        <option value="tricycle">Tricycle</option>
                    </select>
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
                    <button type="button" class="btn btn-danger btn-sm" onclick="this.closest('tr').remove(); updateRouteNumbers();">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            `;
            
            tbody.appendChild(newRow);
        }
        
        function updateRouteNumbers() {
            const rows = document.querySelectorAll('#transportTableBody .route-row');
            rows.forEach((row, index) => {
                const firstCell = row.querySelector('td:first-child');
                if (firstCell) {
                    firstCell.textContent = index + 1;
                }
            });
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>