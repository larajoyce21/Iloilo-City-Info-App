<?php 
include 'conn.php';  

if (isset($_GET['delete'])) {     
    $delete_id = $_GET['delete'];     
    $stmt = $conn->prepare("DELETE FROM plaza WHERE id=?");     
    $stmt->bind_param("i", $delete_id);     
    $stmt->execute();     
    header("Location: add_plaza.php");     
    exit(); 
}  

function convertToEmbedURL($googleMapsLink) {     
    if (strpos($googleMapsLink, 'goo.gl/maps') !== false || strpos($googleMapsLink, 'google.com/maps') !== false) {         
        return str_replace("maps/place/", "maps/embed?pb=", $googleMapsLink);     
    }     
    return $googleMapsLink; 
}  

$query = "SELECT * FROM plaza"; 
$stmt = $conn->prepare($query); 
$stmt->execute(); 
$plazas = $stmt->get_result();  

if ($_SERVER['REQUEST_METHOD'] == 'POST') {     
    $id = $_POST['id'] ?? null;     
    $name = $_POST['name'] ?? '';     
    $description = $_POST['description'] ?? '';     
    $location = $_POST['location'] ?? '';     
    $maps_link = $_POST['maps_link'] ?? '';     
    $website = $_POST['website'] ?? '';     
    $directions_link = $_POST['directions_link'] ?? '';     
    $details_link = $_POST['details_link'] ?? '';     
    $history = $_POST['history'] ?? '';     
    $features = $_POST['features'] ?? '';     
    $significance = $_POST['significance'] ?? '';     
    $hours = $_POST['hours'] ?? '';     
    $fee = $_POST['fee'] ?? '';     
    $contact = $_POST['contact'] ?? '';     
    $year_established = $_POST['year_established'] ?? '';     
    $size = $_POST['size'] ?? '';     
    $main_feature = $_POST['main_feature'] ?? '';     
    $status = $_POST['status'] ?? '';     
    $events = $_POST['events'] ?? '';     
    $accessibility = $_POST['accessibility'] ?? '';     
    $transportation_options = $_POST['transportation_options'] ?? '';     
    $nearby_places = $_POST['nearby_places'] ?? '';     
    $source = $_POST['source'] ?? '';     
    $map_embed = $_POST['map_embed'] ?? '';
    
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
    $target_file_detail = implode(',', array_merge($existingImages, $imagePaths));          

    if ($id) {         
        $query = "UPDATE plaza SET              
        name=?, image=?, image_detail=?, description=?, location=?,              
        maps_link=?, maps_embed=?, website=?, directions_link=?, details_link=?,             
        history=?, features=?, significance=?, hours=?, fee=?, contact=?,             
        year_established=?, size=?, main_feature=?, status=?, events=?,             
        accessibility=?, transportation_options=?, nearby_places=?, source=?, transportation_routes=?             
        WHERE id=?";         
        $stmt = $conn->prepare($query);         
        $stmt->bind_param(             
            "ssssssssssssssssssssssssssi",              
            $name, $target_file, $target_file_detail, $description, $location,              
            $maps_link, $map_embed, $website, $directions_link, $details_link,             
            $history, $features, $significance, $hours, $fee, $contact,             
            $year_established, $size, $main_feature, $status, $events,             
            $accessibility, $transportation_options, $nearby_places, $source, $transportation_routes_json,             
            $id         
        );     
    } else {         
        $query = "INSERT INTO plaza (             
            name, image, image_detail, description, location,              
            maps_link, maps_embed, website, directions_link, details_link,             
            history, features, significance, hours, fee, contact,             
            year_established, size, main_feature, status, events,             
            accessibility, transportation_options, nearby_places, source, transportation_routes         
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";         
        $stmt = $conn->prepare($query);         
        $stmt->bind_param(             
            "sssssssssssssssssssssssss",              
            $name, $target_file, $target_file_detail, $description, $location,              
            $maps_link, $map_embed, $website, $directions_link, $details_link,             
            $history, $features, $significance, $hours, $fee, $contact,             
            $year_established, $size, $main_feature, $status, $events,             
            $accessibility, $transportation_options, $nearby_places, $source, $transportation_routes_json         
        );     
    }      

    $stmt->execute();     
    header("Location: add_plaza.php");     
    exit(); 
}

$plazaData = [
    'id' => '', 'name' => '', 'description' => '', 'location' => '',                              
    'maps_link' => '', 'website' => '', 'image' => '', 'maps_embed' => '',                              
    'directions_link' => '', 'details_link' => '', 'history' => '',                              
    'features' => '', 'significance' => '', 'hours' => '', 'fee' => '',                              
    'contact' => '', 'year_established' => '', 'size' => '',                              
    'main_feature' => '', 'status' => '', 'events' => '',                              
    'accessibility' => '', 'transportation_options' => '',                              
    'nearby_places' => '', 'source' => '', 'transportation_routes' => ''
];

$transportation_routes = array();

if (isset($_GET['edit'])) {                             
    $edit_id = $_GET['edit'];                             
    $result = $conn->query("SELECT * FROM plaza WHERE id=$edit_id");                             
    if ($result->num_rows > 0) {                                 
        $plazaData = $result->fetch_assoc();
        if (!empty($plazaData['transportation_routes'])) {
            $decoded_routes = json_decode($plazaData['transportation_routes'], true);
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
    <title>Plaza Management</title>     
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">     
    <style>         
        body {             
            background: url('img/bg.png') no-repeat center center fixed;             
            background-size: cover;             
            min-height: 100vh;         
        }         
        .card-img-top {             
            height: 150px;             
            object-fit: cover;         
        }         
        .form-section {             
            background: rgba(255, 255, 255, 0.9);             
            padding: 20px;             
            border-radius: 10px;             
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
    <div class="container mt-5">         
        <button type="button" class="btn btn-light text-dark mb-3" onclick="window.location.href='dashboard.php'">Back to Dashboard</button>         
        <h1 class="text-light">Plaza Management</h1>         
        <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#plazaModal">Add Plaza</button>                  
        <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-3">             
            <?php while ($plaza = $plazas->fetch_assoc()) { ?>                 
                <div class="col">                     
                    <div class="card h-100">                         
                        <img src="<?php echo $plaza['image']; ?>" class="card-img-top" alt="Plaza Image">                         
                        <div class="card-body">                             
                            <h5 class="card-title"><?php echo $plaza['name']; ?></h5>                             
                            <p class="card-text"><?php echo substr($plaza['description'], 0, 100) . '...'; ?></p>                         
                        </div>                         
                        <div class="card-footer d-flex justify-content-between">                             
                            <a href="add_plaza.php?edit=<?php echo $plaza['id']; ?>" class="btn btn-primary">Edit</a>                             
                            <a href="?delete=<?php echo $plaza['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure?')">Delete</a>                         
                        </div>                     
                    </div>                 
                </div>             
            <?php } ?>         
        </div>     
    </div>      

    <!-- Add/Edit Plaza Modal -->     
    <div class="modal fade <?php echo isset($_GET['edit']) ? 'show d-block' : ''; ?>" id="plazaModal" tabindex="-1" aria-labelledby="plazaModalLabel" aria-hidden="true">         
        <div class="modal-dialog modal-xl">             
            <div class="modal-content">                 
                <div class="modal-header">                     
                    <h5 class="modal-title" id="plazaModalLabel"><?php echo isset($_GET['edit']) ? 'Edit Plaza' : 'Add Plaza'; ?></h5>                     
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>                 
                </div>                 
                <div class="modal-body">                     
                    <form method="POST" action="add_plaza.php" enctype="multipart/form-data">                         
                        <input type="hidden" name="id" value="<?php echo $plazaData['id']; ?>">                                                  
                        <div class="form-section">                             
                            <div class="row">                                 
                                <div class="col-md-6">                                     
                                    <h4>Basic Information</h4>                                     
                                    <div class="mb-3">                                         
                                        <label class="form-label">Name:</label>                                         
                                        <input type="text" class="form-control" name="name" value="<?php echo $plazaData['name']; ?>" required>                                     
                                    </div>                                     
                                    <div class="mb-3">                                         
                                        <label class="form-label">Details Page Link:</label>                                         
                                        <input type="text" class="form-control" name="details_link" value="<?php echo $plazaData['details_link']; ?>">                                     
                                    </div>                                     
                                    <div class="mb-3">                                         
                                        <label class="form-label">Description:</label>                                         
                                        <textarea class="form-control" name="description" rows="3"><?php echo $plazaData['description']; ?></textarea>                                     
                                    </div>                                     
                                    <div class="mb-3">                                         
                                        <label class="form-label">Upload Card Image:</label>                                         
                                        <input type="file" class="form-control" name="image">                                         
                                        <?php if ($plazaData['image']) { ?>                                             
                                            <img src="<?php echo $plazaData['image']; ?>" class="img-thumbnail mt-2" width="100">                                             
                                            <input type="hidden" name="existing_image" value="<?php echo $plazaData['image']; ?>">                                         
                                        <?php } ?>                                     
                                    </div>                                     
                                    <div class="mb-3">                                         
                                        <label class="form-label">Upload Detail Images (2-5):</label>                                         
                                        <input type="file" class="form-control" name="image_detail[]" multiple accept="image/*">                                         
                                        <?php if (!empty($plazaData['image_detail'])) {                                             
                                            $images = explode(',', $plazaData['image_detail']);                                              
                                            echo '<div class="d-flex flex-wrap mt-2">';                                             
                                            foreach ($images as $img) {                                                  
                                                if (!empty($img)) { ?>                                                     
                                                    <div class="position-relative me-2 mb-2">                                                         
                                                        <img src="<?php echo $img; ?>" class="img-thumbnail" width="100">                                                         
                                                        <a href="?delete_image=<?php echo urlencode($img); ?>&plaza_id=<?php echo $plazaData['id']; ?>" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" onclick="return confirm('Are you sure you want to delete this image?')">×</a>                                                     
                                                    </div>                                                 
                                                <?php }                                             
                                            }                                             
                                            echo '</div>'; ?>                                             
                                            <input type="hidden" name="existing_image_detail" value="<?php echo $plazaData['image_detail']; ?>">                                         
                                        <?php } ?>                                     
                                    </div>                                 
                                </div>                                 
                                <div class="col-md-6">                                     
                                    <h4>Location Information</h4>                                     
                                    <div class="mb-3">                                         
                                        <label class="form-label">Address:</label>                                         
                                        <input type="text" class="form-control" name="location" value="<?php echo $plazaData['location']; ?>">                                     
                                    </div>                                     
                                    <div class="mb-3">                                         
                                        <label class="form-label">Google Maps Link:</label>                                         
                                        <input type="text" class="form-control" name="maps_link" value="<?php echo $plazaData['maps_link']; ?>">                                     
                                    </div>                                     
                                    <div class="mb-3">                                         
                                        <label class="form-label">Google Maps Directions Link:</label>                                         
                                        <input type="text" class="form-control" name="directions_link" value="<?php echo $plazaData['directions_link']; ?>">                                     
                                    </div>                                     
                                    <div class="mb-3">                                         
                                        <label class="form-label">Embed Google Map URL:</label>                                         
                                        <input type="text" class="form-control" name="map_embed" value="<?php echo $plazaData['maps_embed']; ?>">                                     
                                    </div>                                     
                                    <div class="mb-3">                                         
                                        <label class="form-label">Website:</label>                                         
                                        <input type="text" class="form-control" name="website" value="<?php echo $plazaData['website']; ?>">                                     
                                    </div>    
                                  
                                    <div class="mb-3">                                         
                                        <label class="form-label">Source/Reference:</label>                                         
                                        <input type="text" class="form-control" name="source" value="<?php echo $plazaData['source']; ?>">                                     
                                    </div>                                
                                </div>                             
                            </div>                         
                        </div>
                        
                        <div class="form-section">                             
                            <h4>Transportation Routes</h4>                            
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
                                                    <input type="text" class="form-control" name="route_name[]" value="<?php echo isset($transportation_routes[$i]['name']) ? htmlspecialchars($transportation_routes[$i]['name']) : ''; ?>" placeholder="e.g., Route 101, Transport Company">
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control" name="route_link[]" value="<?php echo isset($transportation_routes[$i]['link']) ? htmlspecialchars($transportation_routes[$i]['link']) : ''; ?>" placeholder="https://maps.google.com/...">
                                                </td>
                                                <td>
                                                    <textarea class="form-control" name="route_description[]" rows="2" placeholder="e.g., Main transportation to the area, nearby bus stops"><?php echo isset($transportation_routes[$i]['description']) ? htmlspecialchars($transportation_routes[$i]['description']) : ''; ?></textarea>
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
                            <div class="row">                                 
                                <div class="col-md-6">                                     
                                    <h4>Detailed Information</h4>                                     
                                    <div class="mb-3">                                         
                                        <label class="form-label">Historical Background:</label>                                         
                                        <textarea class="form-control" name="history" rows="3"><?php echo $plazaData['history']; ?></textarea>                                     
                                    </div>                                     
                                    <div class="mb-3">                                         
                                        <label class="form-label">Plaza Features:</label>                                         
                                        <textarea class="form-control" name="features" rows="3"><?php echo $plazaData['features']; ?></textarea>                                     
                                    </div>                                     
                                    <div class="mb-3">                                         
                                        <label class="form-label">Cultural Significance:</label>                                         
                                        <textarea class="form-control" name="significance" rows="3"><?php echo $plazaData['significance']; ?></textarea>                                     
                                    </div>  
                                    <div class="mb-3">                                         
                                        <label class="form-label">Year Established:</label>                                         
                                        <input type="text" class="form-control" name="year_established" value="<?php echo $plazaData['year_established']; ?>">                                     
                                    </div>                                                                         
                                    <div class="mb-3">                                         
                                        <label class="form-label">Events:</label>                                         
                                        <input type="text" class="form-control" name="events" value="<?php echo $plazaData['events']; ?>">                                     
                                    </div>                               
                                </div>                                 
                                <div class="col-md-6">                                     
                                    <h4>Visitor Information</h4>
                                    <div class="mb-3">                                         
                                        <label class="form-label">Operating Hours:</label>                                         
                                        <textarea class="form-control" name="hours" rows="3" placeholder="e.g., Monday: 9AM - 5PM"><?php echo $plazaData['hours']; ?></textarea>                                     
                                    </div>                                     
                                    <div class="mb-3">                                         
                                        <label class="form-label">Entrance Fee:</label>                                         
                                        <input type="text" class="form-control" name="fee" value="<?php echo $plazaData['fee']; ?>">                                     
                                    </div>
                                    <div class="mb-3">                                         
                                        <label class="form-label">Accessibility:</label>                                         
                                        <textarea class="form-control" name="accessibility" rows="3"><?php echo $plazaData['accessibility']; ?></textarea>                                     
                                    </div>                                     
                                    <div class="mb-3">                                         
                                        <label class="form-label">Contact Information:</label>                                         
                                        <input type="text" class="form-control" name="contact" value="<?php echo $plazaData['contact']; ?>">                                     
                                    </div>                                     
                                    <div class="mb-3">                                         
                                        <label class="form-label">Transportation Options (General, one per line):</label>                                         
                                        <textarea class="form-control" name="transportation_options" rows="4"><?php echo $plazaData['transportation_options']; ?></textarea>                                     
                                    </div>                                     
                                    <div class="mb-3">                                         
                                        <label class="form-label">Nearby Places (comma separated):</label>                                         
                                        <textarea class="form-control" name="nearby_places" rows="4"><?php echo $plazaData['nearby_places']; ?></textarea>                                     
                                    </div>                                 
                                </div>                             
                            </div>                         
                        </div>

                        <div class="form-section">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Size:</label>
                                        <input type="text" class="form-control" name="size" value="<?php echo $plazaData['size']; ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Main Feature:</label>
                                        <input type="text" class="form-control" name="main_feature" value="<?php echo $plazaData['main_feature']; ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Status:</label>
                                        <input type="text" class="form-control" name="status" value="<?php echo $plazaData['status']; ?>">
                                    </div>
                                </div>
                            </div>
                        </div>                                                 

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">                             
                            <button type="button" class="btn btn-secondary me-md-2" data-bs-dismiss="modal">Close</button>                             
                            <button type="submit" class="btn btn-primary"><?php echo isset($_GET['edit']) ? 'Update' : 'Save'; ?></button>                         
                        </div>                     
                    </form>                 
                </div>             
            </div>         
        </div>     
    </div>      

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>     
    <script>         
        <?php if (isset($_GET['edit'])): ?>             
            var plazaModal = new bootstrap.Modal(document.getElementById('plazaModal'));             
            plazaModal.show();         
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
                    <input type="text" class="form-control" name="route_name[]" placeholder="e.g., Route 101, Transport Company">
                </td>
                <td>
                    <input type="text" class="form-control" name="route_link[]" placeholder="https://maps.google.com/...">
                </td>
                <td>
                    <textarea class="form-control" name="route_description[]" rows="2" placeholder="e.g., Main transportation to the area, nearby bus stops"></textarea>
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