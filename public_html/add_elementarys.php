<?php
include 'conn.php';

if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM elementarys WHERE id=?");
    if (!$stmt) {
        die("Query preparation failed: " . $conn->error);
    }
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    header("Location: add_elementarys.php");
    exit();
}

// Handle image deletion
if (isset($_GET['delete_image']) && isset($_GET['elementary_id'])) {
    $image_path = $_GET['delete_image'];
    $elementary_id = $_GET['elementary_id'];
    
    $stmt = $conn->prepare("SELECT image_detail FROM elementarys WHERE id=?");
    if ($stmt) {
        $stmt->bind_param("i", $elementary_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $elementary = $result->fetch_assoc();
            $images = explode(',', $elementary['image_detail']);
            
            $new_images = array_filter($images, function($img) use ($image_path) {
                return $img !== $image_path;
            });
            
            $new_image_detail = implode(',', $new_images);
            $update_stmt = $conn->prepare("UPDATE elementarys SET image_detail=? WHERE id=?");
            $update_stmt->bind_param("si", $new_image_detail, $elementary_id);
            $update_stmt->execute();
            
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }
    }
    header("Location: add_elementarys.php?edit=" . $elementary_id);
    exit();
}

function convertToEmbedURL($googleMapsLink) {
    if (strpos($googleMapsLink, 'goo.gl/maps') !== false || strpos($googleMapsLink, 'google.com/maps') !== false) {
        return str_replace("maps/place/", "maps/embed?pb=", $googleMapsLink);
    }
    return $googleMapsLink;
}

$query = "SELECT id, name, image, image_detail, description, location, maps_embed, website, maps_link, directions_link, details_link FROM elementarys";
$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Query preparation failed: " . $conn->error);
}
$stmt->execute();
$elementarys = $stmt->get_result();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'] ?? null;
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $location = $_POST['location'] ?? '';
    $website = $_POST['website'] ?? '';
    $maps_link = $_POST['maps_link'] ?? '';
    $directions_link = $_POST['directions_link'] ?? ''; 
    $mapEmbedURL = htmlspecialchars($_POST['map_embed'] ?? '');
    $details_link = $_POST['details_link'] ?? ''; 
    $facilities = $_POST['facilities'] ?? '';
    $programs = $_POST['programs'] ?? '';
    $hours = $_POST['hours'] ?? '';
    $fee = $_POST['fee'] ?? '';
    $contact = $_POST['contact'] ?? '';
    $email = $_POST['email'] ?? '';
    $year_established = $_POST['year_established'] ?? '';
    $school_type = $_POST['school_type'] ?? '';
    $students = $_POST['students'] ?? '';
    $teachers = $_POST['teachers'] ?? '';
    $transportation_options = $_POST['transportation_options'] ?? '';
    $fun_fact = $_POST['fun_fact'] ?? '';
    $nearby_places = $_POST['nearby_places'] ?? '';
    $social_media = $_POST['social_media'] ?? '';
    $history = $_POST['history'] ?? '';

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
    if (!empty($_FILES['image']['name'])) {
        $target_file = $target_dir . basename($_FILES["image"]["name"]);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        if (in_array($imageFileType, $allowed_types)) {
            move_uploaded_file($_FILES["image"]["tmp_name"], $target_file);
        } else {
            die("Error: Only JPG, JPEG, PNG, and GIF files are allowed.");
        }
    }

    $imagePaths = [];
    $existingImages = [];
    if (!empty($_POST['existing_image_detail'])) {
        $existingImages = explode(',', $_POST['existing_image_detail']);
    }
    
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
                } else {
                    die("Error uploading one of the images.");
                }
            } else {
                die("Error: Only JPG, JPEG, PNG, and GIF files are allowed.");
            }
        }
    }
    
    $allImages = array_merge($existingImages, $imagePaths);
    $target_file_detail = implode(',', $allImages);
    
    if ($id) {
        $query = "UPDATE elementarys SET 
                    name=?, image=?, image_detail=?, description=?, location=?, 
                    maps_embed=?, maps_link=?, website=?, directions_link=?, details_link=?,
                    facilities=?, programs=?, hours=?, fee=?, contact=?, email=?,
                    year_established=?, school_type=?, students=?,
                    teachers=?, transportation_options=?, fun_fact=?, 
                    nearby_places=?, social_media=?, history=?, transportation_routes=?
                  WHERE id=?";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            die("Query preparation failed: " . $conn->error);
        }
        $stmt->bind_param("ssssssssssssssssssssssssssi", 
            $name, $target_file, $target_file_detail, $description, $location, 
            $mapEmbedURL, $maps_link, $website, $directions_link, $details_link,
            $facilities, $programs, $hours, $fee, $contact, $email,
            $year_established, $school_type, $students,
            $teachers, $transportation_options, $fun_fact, 
            $nearby_places, $social_media, $history, $transportation_routes_json, $id);
    } else {
        $query = "INSERT INTO elementarys (
                    name, image, image_detail, description, location, 
                    maps_embed, website, directions_link, details_link,
                    facilities, programs, hours, fee, contact, email,
                    year_established, school_type, students,
                    teachers, transportation_options, fun_fact, 
                    nearby_places, social_media, history, transportation_routes
                  ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            die("Query preparation failed: " . $conn->error);
        }
        $stmt->bind_param("sssssssssssssssssssssssss", 
            $name, $target_file, $target_file_detail, $description, $location, 
            $mapEmbedURL, $website, $directions_link, $details_link,
            $facilities, $programs, $hours, $fee, $contact, $email,
            $year_established, $school_type, $students,
            $teachers, $transportation_options, $fun_fact, 
            $nearby_places, $social_media, $history, $transportation_routes_json);
    }

    $stmt->execute();
    header("Location: add_elementarys.php");
    exit();
}

// Prepare transportation routes for editing
$transportation_routes = array();
if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $stmt = $conn->prepare("SELECT transportation_routes FROM elementarys WHERE id=?");
    if ($stmt) {
        $stmt->bind_param("i", $edit_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if (!empty($row['transportation_routes'])) {
                $decoded_routes = json_decode($row['transportation_routes'], true);
                if (is_array($decoded_routes)) {
                    $transportation_routes = $decoded_routes;
                }
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
    <title>Elementary School Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
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
            border-radius: 20px;
            overflow: hidden;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .modal-content {
            max-height: 80vh;
            overflow-y: auto;
        }
        .form-section {
            background: rgba(248, 249, 250, 0.9);
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
<br>
    <div class="container">
        <button type="button" class="btn btn-light text-dark" onclick="window.location.href='dashboard.php'">Back to Dashboard</button>
    </div>
    <div class="container mt-5">
        <h1 class="text-light">Elementary School Management</h1>
        <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#elementaryModal">Add Elementary School</button>
        <div class="row row-cols-1 row-cols-md-3 row-cols-lg-5 g-3">
            <?php while ($elementary = $elementarys->fetch_assoc()) { ?>
                <div class="col">
                    <div class="card h-100 d-flex flex-column" style="height: 330px; display: flex; flex-direction: column; justify-content: space-between; border-radius: 20px;">
                        <img src="<?php echo $elementary['image']; ?>" class="card-img-top" alt="agency Image" style="height: 150px; width: 150px; object-fit: cover; display: block; margin: auto;">
                        <div class="card-body d-flex flex-column flex-grow-1">
                            <h5 class="card-title"><?php echo $elementary['name']; ?></h5>
                            <div class="mt-auto d-flex justify-content-center gap-2">
                                <a href="add_elementarys.php?edit=<?php echo $elementary['id']; ?>" class="btn btn-primary">Update</a>
                                <a href="?delete=<?php echo $elementary['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>

    <!-- Add/Edit elementary Modal -->
    <div class="modal fade <?php echo isset($_GET['edit']) ? 'show d-block' : ''; ?>" id="elementaryModal" tabindex="-1" aria-labelledby="elementaryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="elementaryModalLabel"><?php echo isset($_GET['edit']) ? 'Edit Elementary School' : 'Add Elementary School'; ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php 
                        $elementaryData = [
                            'id' => '', 'name' => '', 'description' => '', 'location' => '', 
                            'website' => '', 'image' => '', 'maps_embed' => '', 'maps_link' => '', 
                            'directions_link' => '', 'details_link' => '', 'facilities' => '',
                            'programs' => '', 'hours' => '', 'fee' => '', 'contact' => '',
                            'email' => '', 'year_established' => '', 'school_type' => '', 
                             'students' => '', 'teachers' => '', 
                            'transportation_options' => '', 'fun_fact' => '',
                            'nearby_places' => '', 'social_media' => '', 'history' => ''
                        ];
                        
                        if (isset($_GET['edit'])) {
                            $edit_id = $_GET['edit'];
                            $result = $conn->query("SELECT * FROM elementarys WHERE id=$edit_id");
                            if ($result->num_rows > 0) {
                                $elementaryData = $result->fetch_assoc();
                            }
                        }
                    ?>
                    <form method="POST" action="add_elementarys.php" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="<?php echo $elementaryData['id']; ?>">
                        
                        <!-- Basic Information Section -->
                        <div class="form-section">
                            <h5>Basic Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Name:</label>
                                        <input type="text" class="form-control" name="name" value="<?php echo $elementaryData['name']; ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Details Page Link:</label>
                                        <input type="text" class="form-control" name="details_link" value="<?php echo htmlspecialchars($elementaryData['details_link']); ?>" placeholder="e.g., elementary_details.php?id=1">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Description:</label>
                                        <textarea class="form-control" name="description" rows="3"><?php echo $elementaryData['description']; ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Year Established:</label>
                                        <input type="text" class="form-control" name="year_established" value="<?php echo $elementaryData['year_established']; ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">School Type:</label>
                                        <input type="text" class="form-control" name="school_type" value="<?php echo $elementaryData['school_type']; ?>">
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Number of Students:</label>
                                        <input type="text" class="form-control" name="students" value="<?php echo $elementaryData['students']; ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Number of Teachers:</label>
                                        <input type="text" class="form-control" name="teachers" value="<?php echo $elementaryData['teachers']; ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Contact Number:</label>
                                        <input type="text" class="form-control" name="contact" value="<?php echo $elementaryData['contact']; ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Email:</label>
                                        <input type="email" class="form-control" name="email" value="<?php echo $elementaryData['email']; ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Fun Fact:</label>
                                        <textarea class="form-control" name="fun_fact" rows="2"><?php echo $elementaryData['fun_fact']; ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Images Section -->
                        <div class="form-section">
                            <h5>Images</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Upload Card Image:</label>
                                        <input type="file" class="form-control" name="image">
                                        <?php if ($elementaryData['image']) { ?>
                                            <img src="<?php echo $elementaryData['image']; ?>" class="img-thumbnail mt-2" width="100">
                                            <input type="hidden" name="existing_image" value="<?php echo $elementaryData['image']; ?>">
                                        <?php } ?>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Upload Detail Images (2-5):</label>
                                        <input type="file" class="form-control" name="image_detail[]" multiple accept="image/*">
                                        
                                        <?php if (!empty($elementaryData['image_detail'])) {
                                            $images = explode(',', $elementaryData['image_detail']); 
                                            echo '<div class="d-flex flex-wrap mt-2">';
                                            foreach ($images as $img) { 
                                                if (!empty($img)) { ?>
                                                    <div class="position-relative me-2 mb-2">
                                                        <img src="<?php echo htmlspecialchars($img); ?>" class="img-thumbnail" width="100">
                                                        <a href="?delete_image=<?php echo urlencode($img); ?>&elementary_id=<?php echo $elementaryData['id']; ?>" 
                                                           class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" 
                                                           onclick="return confirm('Are you sure you want to delete this image?')">
                                                           ×
                                                        </a>
                                                    </div>
                                                <?php }
                                            }
                                            echo '</div>';
                                            ?>
                                            <input type="hidden" name="existing_image_detail" value="<?php echo htmlspecialchars($elementaryData['image_detail']); ?>">
                                        <?php } ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Facilities & Programs Section -->
                        <div class="form-section">
                            <h5>Facilities & Programs</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Facilities:</label>
                                        <textarea class="form-control" name="facilities" rows="3"><?php echo $elementaryData['facilities']; ?></textarea>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Academic Programs:</label>
                                        <textarea class="form-control" name="programs" rows="3"><?php echo $elementaryData['programs']; ?></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">History:</label>
                                <textarea class="form-control" name="history" rows="3"><?php echo $elementaryData['history']; ?></textarea>
                            </div>
                        </div>
                        
                        <!-- Visit Information Section -->
                        <div class="form-section">
                            <h5>Visit Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">School Hours:</label>
                                        <textarea class="form-control" name="hours" rows="3"><?php echo $elementaryData['hours']; ?></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Fees:</label>
                                        <input type="text" class="form-control" name="fee" value="<?php echo $elementaryData['fee']; ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Transportation Options (one per line):</label>
                                        <textarea class="form-control" name="transportation_options" rows="3"><?php echo $elementaryData['transportation_options']; ?></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Social Media Links (one per line):</label>
                                        <textarea class="form-control" name="social_media" rows="3"><?php echo $elementaryData['social_media']; ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Transportation Routes Section -->
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
                                                    </select>
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control" name="route_name[]" value="<?php echo isset($transportation_routes[$i]['name']) ? htmlspecialchars($transportation_routes[$i]['name']) : ''; ?>" placeholder="e.g., School Bus Route 1, Jeepney Route 10">
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control" name="route_link[]" value="<?php echo isset($transportation_routes[$i]['link']) ? htmlspecialchars($transportation_routes[$i]['link']) : ''; ?>" placeholder="https://maps.google.com/...">
                                                </td>
                                                <td>
                                                    <textarea class="form-control" name="route_description[]" rows="2" placeholder="e.g., Morning pickup at 7AM, stops near residential areas"><?php echo isset($transportation_routes[$i]['description']) ? htmlspecialchars($transportation_routes[$i]['description']) : ''; ?></textarea>
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
                        
                        <!-- Location Information Section -->
                        <div class="form-section">
                            <h5>Location Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Address:</label>
                                        <input type="text" class="form-control" name="location" value="<?php echo $elementaryData['location']; ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Website:</label>
                                        <input type="text" class="form-control" name="website" value="<?php echo $elementaryData['website']; ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="directions_link">Google Maps Directions Link:</label>
                                        <input type="text" name="directions_link" id="directions_link" class="form-control" value="<?php echo $elementaryData['directions_link']; ?>">
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Google Map Link:</label>
                                        <input type="text" class="form-control" name="maps_link" value="<?php echo $elementaryData['maps_link']; ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Embed Google Map URL:</label>
                                        <input type="text" class="form-control" name="map_embed" value="<?php echo $elementaryData['maps_embed']; ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Nearby Places (comma separated):</label>
                                        <textarea class="form-control" name="nearby_places" rows="2"><?php echo $elementaryData['nearby_places']; ?></textarea>
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
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Show the modal if editing
        <?php if (isset($_GET['edit'])): ?>
            document.addEventListener('DOMContentLoaded', function() {
                var myModal = new bootstrap.Modal(document.getElementById('elementaryModal'));
                myModal.show();
            });
        <?php endif; ?>
        
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
                    </select>
                </td>
                <td>
                    <input type="text" class="form-control" name="route_name[]" placeholder="e.g., School Bus Route 1, Jeepney Route 10">
                </td>
                <td>
                    <input type="text" class="form-control" name="route_link[]" placeholder="https://maps.google.com/...">
                </td>
                <td>
                    <textarea class="form-control" name="route_description[]" rows="2" placeholder="e.g., Morning pickup at 7AM, stops near residential areas"></textarea>
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