<?php
include 'conn.php';

if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    $conn->query("DELETE FROM new_tourist_spots WHERE id=$delete_id");
    header("Location: add_new_tourist_spots.php");
    exit();
}

if (isset($_GET['delete_image']) && isset($_GET['spot_id'])) {
    $image_to_delete = $_GET['delete_image'];
    $spot_id = $_GET['spot_id'];
    
    $result = $conn->query("SELECT image_detail FROM new_tourist_spots WHERE id=$spot_id");
    $row = $result->fetch_assoc();
    $images = explode(',', $row['image_detail']);
    
    $updated_images = array_filter($images, function($img) use ($image_to_delete) {
        return $img !== $image_to_delete;
    });
    
    $conn->query("UPDATE new_tourist_spots SET image_detail='".implode(',', $updated_images)."' WHERE id=$spot_id");
    header("Location: add_new_tourist_spots.php?edit=$spot_id");
    exit();
}

$spots = $conn->query("SELECT * FROM new_tourist_spots");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'] ?? null;
    $name = $_POST['name'];
    $description = $_POST['description'];
    $features = $_POST['features'];
    $activities = $_POST['activities'];
    $location = $_POST['location'];
    $operating_hours = $_POST['operating_hours'];
    $entrance_fee = $_POST['entrance_fee'];
    $best_time_to_visit = $_POST['best_time_to_visit'];
    $nearby_places = $_POST['nearby_places'];
    $travel_tips = $_POST['travel_tips'];
    $website = $_POST['website'];
    $maps_link = $_POST['maps_link'];
    $map_embed = $_POST['map_embed'];
    $details_link = $_POST['details_link'] ?? '';
    
    // Handle transportation routes
    $transportation_routes = array();
    
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
    $target_file = $_POST['existing_image'] ?? '';
    
    if (!empty($_FILES['image']['name'])) {
        $target_file = $target_dir . basename($_FILES["image"]["name"]);
        move_uploaded_file($_FILES["image"]["tmp_name"], $target_file);
    }
    
    $existing_images = [];
    if (!empty($_POST['existing_image_detail'])) {
        $existing_images = explode(',', $_POST['existing_image_detail']);
    }
    
    $new_images = [];
    if (!empty($_FILES['image_detail']['name'][0])) {
        foreach ($_FILES['image_detail']['tmp_name'] as $key => $tmp_name) {
            $file_name = $_FILES['image_detail']['name'][$key];
            $file_tmp = $_FILES['image_detail']['tmp_name'][$key];
            $new_file = $target_dir . $file_name;
            move_uploaded_file($file_tmp, $new_file);
            $new_images[] = $new_file;
        }
    }
    
    $all_images = array_merge($existing_images, $new_images);
    $image_detail = implode(',', $all_images);
    
    if ($id) {
        $stmt = $conn->prepare("UPDATE new_tourist_spots SET 
            name=?, image=?, image_detail=?, description=?, features=?, 
            activities=?, location=?, maps_embed=?, operating_hours=?, 
            entrance_fee=?, best_time_to_visit=?, nearby_places=?, 
            travel_tips=?, website=?, maps_link=?, details_link=?, transportation_routes=? WHERE id=?");
        
        $stmt->bind_param("sssssssssssssssssi", 
            $name, $target_file, $image_detail, $description, $features,
            $activities, $location, $map_embed, $operating_hours,
            $entrance_fee, $best_time_to_visit, $nearby_places,
            $travel_tips, $website, $maps_link, $details_link, $transportation_routes_json, $id);
    } else {
        $stmt = $conn->prepare("INSERT INTO new_tourist_spots (
            name, image, image_detail, description, features, 
            activities, location, maps_embed, operating_hours, 
            entrance_fee, best_time_to_visit, nearby_places, 
            travel_tips, website, maps_link, details_link, transportation_routes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->bind_param("sssssssssssssssss", 
            $name, $target_file, $image_detail, $description, $features,
            $activities, $location, $map_embed, $operating_hours,
            $entrance_fee, $best_time_to_visit, $nearby_places,
            $travel_tips, $website, $maps_link, $details_link, $transportation_routes_json);
    }
    
    $stmt->execute();
    header("Location: add_new_tourist_spots.php");
    exit();
}

$spotData = [
    'id' => '', 'name' => '', 'image' => '', 'image_detail' => '', 
    'description' => '', 'features' => '', 'activities' => '', 
    'location' => '', 'maps_embed' => '', 'operating_hours' => '', 
    'entrance_fee' => '', 'best_time_to_visit' => '', 'nearby_places' => '', 
    'travel_tips' => '', 'website' => '', 'maps_link' => '', 'details_link' => '',
    'transportation_routes' => ''
];

$transportation_routes = array();

if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $result = $conn->query("SELECT *, transportation_routes FROM new_tourist_spots WHERE id=$edit_id");
    if ($result->num_rows > 0) {
        $spotData = $result->fetch_assoc();
        if (!empty($spotData['transportation_routes'])) {
            $decoded_routes = json_decode($spotData['transportation_routes'], true);
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
    <title>Tourist Spots Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-4">
        <div class="container mt-3">
            <button type="button" class="btn btn-light text-white bg-success" onclick="window.location.href='dashboard.php'">Back to Dashboard</button>
        </div>
        <br>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Tourist Spots Management</h1>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#spotModal">
                Add New Spot
            </button>
        </div>
        
        <div class="row row-cols-1 row-cols-md-3 g-4">
            <?php while ($spot = $spots->fetch_assoc()): ?>
                <div class="col">
                    <div class="card h-100">
                        <img src="<?= $spot['image'] ?>" class="card-img-top" style="height:200px;object-fit:cover;">
                        <div class="card-body">
                            <h5 class="card-title"><?= $spot['name'] ?></h5>
                            <p class="card-text text-muted"><?= $spot['location'] ?></p>
                        </div>
                        <div class="card-footer bg-white">
                            <div class="d-flex justify-content-between">
                                <a href="?edit=<?= $spot['id'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                <a href="?delete=<?= $spot['id'] ?>" class="btn btn-sm btn-outline-danger" 
                                   onclick="return confirm('Are you sure you want to delete this spot?')">Delete</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div class="modal fade <?= isset($_GET['edit']) ? 'show d-block' : '' ?>" id="spotModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><?= $spotData['id'] ? 'Edit' : 'Add' ?> Tourist Spot</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="<?= $spotData['id'] ?>">
                        
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Spot Name</label>
                                    <input type="text" class="form-control" name="name" value="<?= $spotData['name'] ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Main Image</label>
                                    <input type="file" class="form-control" name="image">
                                    <?php if ($spotData['image']): ?>
                                        <img src="<?= $spotData['image'] ?>" class="img-thumbnail mt-2" width="100">
                                        <input type="hidden" name="existing_image" value="<?= $spotData['image'] ?>">
                                    <?php endif; ?>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Details Page Link:</label>
                                    <input type="text" class="form-control" name="details_link" value="<?= $spotData['details_link'] ?>" placeholder="e.g., market1.php?id=1">
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Location</label>
                                    <input type="text" class="form-control" name="location" value="<?= $spotData['location'] ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Operating Hours</label>
                                    <input type="text" class="form-control" name="operating_hours" value="<?= $spotData['operating_hours'] ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Entrance Fee</label>
                                    <input type="text" class="form-control" name="entrance_fee" value="<?= $spotData['entrance_fee'] ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Best Time to Visit</label>
                                    <input type="text" class="form-control" name="best_time_to_visit" value="<?= $spotData['best_time_to_visit'] ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="form-label">Description</label>
                                    <textarea class="form-control" name="description" rows="3" required><?= $spotData['description'] ?></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Features (separate with |)</label>
                                    <textarea class="form-control" name="features" rows="3"><?= $spotData['features'] ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Activities (separate with |)</label>
                                    <textarea class="form-control" name="activities" rows="3"><?= $spotData['activities'] ?></textarea>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Nearby Places (separate with commas)</label>
                                    <textarea class="form-control" name="nearby_places" rows="3"><?= $spotData['nearby_places'] ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Travel Tips</label>
                                    <textarea class="form-control" name="travel_tips" rows="3"><?= $spotData['travel_tips'] ?></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Transportation Routes Section -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="mb-3">Transportation Routes</h5>
                                <div class="table-responsive">
                                    <table class="table table-bordered">
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
                                                        <input type="text" class="form-control" name="route_name[]" value="<?php echo isset($transportation_routes[$i]['name']) ? $transportation_routes[$i]['name'] : ''; ?>" placeholder="e.g., Route 101, Transport Company">
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control" name="route_link[]" value="<?php echo isset($transportation_routes[$i]['link']) ? $transportation_routes[$i]['link'] : ''; ?>" placeholder="https://maps.google.com/...">
                                                    </td>
                                                    <td>
                                                        <textarea class="form-control" name="route_description[]" rows="2" placeholder="e.g., Main transportation to the area, nearby bus stops"><?php echo isset($transportation_routes[$i]['description']) ? $transportation_routes[$i]['description'] : ''; ?></textarea>
                                                    </td>
                                                    <td class="text-center">
                                                        <button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)">Remove</button>
                                                    </td>
                                                </tr>
                                            <?php endfor; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <button type="button" class="btn btn-secondary btn-sm" onclick="addRow()">Add Row</button>
                            </div>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Website URL</label>
                                    <input type="url" class="form-control" name="website" value="<?= $spotData['website'] ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Google Maps Link</label>
                                    <input type="url" class="form-control" name="maps_link" value="<?= $spotData['maps_link'] ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="form-label">Google Maps Embed URL</label>
                                    <input type="url" class="form-control" name="map_embed" value="<?= $spotData['maps_embed'] ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="form-label">Detail Images (2-5 images)</label>
                                    <input type="file" class="form-control" name="image_detail[]" multiple>
                                    
                                    <?php if (!empty($spotData['image_detail'])): ?>
                                        <div class="mt-2">
                                            <?php foreach (explode(',', $spotData['image_detail']) as $img): ?>
                                                <?php if (!empty($img)): ?>
                                                    <div class="position-relative d-inline-block me-2 mb-2">
                                                        <img src="<?= $img ?>" class="img-thumbnail" width="80">
                                                        <a href="?delete_image=<?= urlencode($img) ?>&spot_id=<?= $spotData['id'] ?>" 
                                                           class="position-absolute top-0 start-100 translate-middle badge bg-danger rounded-circle" 
                                                           onclick="return confirm('Delete this image?')">×</a>
                                                    </div>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                            <input type="hidden" name="existing_image_detail" value="<?= $spotData['image_detail'] ?>">
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        <?php if (isset($_GET['edit'])): ?>
            document.addEventListener('DOMContentLoaded', function() {
                var modal = new bootstrap.Modal(document.getElementById('spotModal'));
                modal.show();
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
                    <input type="text" class="form-control" name="route_name[]" placeholder="e.g., Route 101, Transport Company">
                </td>
                <td>
                    <input type="text" class="form-control" name="route_link[]" placeholder="https://maps.google.com/...">
                </td>
                <td>
                    <textarea class="form-control" name="route_description[]" rows="2" placeholder="e.g., Main transportation to the area, nearby bus stops"></textarea>
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)">Remove</button>
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