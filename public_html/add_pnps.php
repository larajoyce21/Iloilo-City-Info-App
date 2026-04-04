<?php
include 'conn.php';

if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    
    $result = $conn->query("SELECT image, image_detail FROM pnps WHERE id = $delete_id");
    
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
    
    $conn->query("DELETE FROM pnps WHERE id = $delete_id");
    
    header("Location: add_pnps.php");
    exit();
}

if (isset($_GET['delete_image']) && isset($_GET['pnp_id'])) {
    $image_path = urldecode($_GET['delete_image']);
    $pnp_id = (int)$_GET['pnp_id'];
    
    $result = $conn->query("SELECT image_detail FROM pnps WHERE id = $pnp_id");
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $images = explode(',', $row['image_detail']);
        
        if (in_array($image_path, $images)) {
            if (file_exists($image_path)) {
                unlink($image_path);
            }
            
            $updated_images = array_diff($images, [$image_path]);
            $updated_images_str = implode(',', $updated_images);
            
            $conn->query("UPDATE pnps SET image_detail = '$updated_images_str' WHERE id = $pnp_id");
        }
    }
    
    header("Location: add_pnps.php?edit=$pnp_id");
    exit();
}

$pnps = $conn->query("SELECT * FROM pnps ORDER BY name ASC");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $name = $conn->real_escape_string($_POST['name']);
    $description = $conn->real_escape_string($_POST['description']);
    $location = $conn->real_escape_string($_POST['location']);
    $services = $conn->real_escape_string($_POST['services']);
    $hotlines = $conn->real_escape_string($_POST['hotlines']);
    $office_hours = $conn->real_escape_string($_POST['office_hours']);
    $email = $conn->real_escape_string($_POST['email']);
    $fees = $conn->real_escape_string($_POST['fees']);
    $established = $conn->real_escape_string($_POST['established']);
    $website = $conn->real_escape_string($_POST['website']);
    $maps_link = $conn->real_escape_string($_POST['maps_link']);
    $directions_link = $conn->real_escape_string($_POST['directions_link']);
    $maps_embed = $conn->real_escape_string($_POST['map_embed']);
    $social_media = $conn->real_escape_string($_POST['social_media']);
    $nearby_places = $conn->real_escape_string($_POST['nearby_places']);
    $details_link = $conn->real_escape_string($_POST['details_link']);

    $nearby_places_array = array_map('trim', explode(',', $nearby_places));
    $nearby_places_json = json_encode(array_filter($nearby_places_array));

    // Handle old transportation options (kept for backward compatibility)
    $transport_options = [];
    if (isset($_POST['transport_type'])) {
        $transport_types = $_POST['transport_type'];
        $transport_details = $_POST['transport_details'];
        
        foreach ($transport_types as $index => $type) {
            if (!empty($type) && !empty($transport_details[$index])) {
                $transport_options[] = [
                    'type' => $type,
                    'details' => $transport_details[$index]
                ];
            }
        }
    }
    $transport_options_json = json_encode($transport_options);
    
    // Handle new transportation routes
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
    $target_file = $_POST['existing_image'] ?? '';
    
    if (!empty($_FILES['image']['name'])) {
        $file = $_FILES['image'];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $new_filename = uniqid('img_', true) . '.' . $file_ext;
        $target_file = $target_dir . $new_filename;

        if (move_uploaded_file($file['tmp_name'], $target_file)) {
            if (!empty($_POST['existing_image']) && file_exists($_POST['existing_image'])) {
                unlink($_POST['existing_image']);
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
        foreach ($_FILES['image_detail']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['image_detail']['error'][$key] === UPLOAD_ERR_OK) {
                $file_ext = strtolower(pathinfo($_FILES['image_detail']['name'][$key], PATHINFO_EXTENSION));
                $new_filename = uniqid('img_', true) . '.' . $file_ext;
                $targetPath = $target_dir . $new_filename;

                if (move_uploaded_file($tmp_name, $targetPath)) {
                    $imagePaths[] = $targetPath;
                }
            }
        }
    }

    $allImages = array_merge($existingImages, $imagePaths);
    $target_file_detail = implode(',', $allImages);

    if ($id > 0) {
        $query = "UPDATE pnps SET 
            name = '$name',
            image = '$target_file',
            image_detail = '$target_file_detail',
            description = '$description',
            location = '$location',
            services = '$services',
            hotlines = '$hotlines',
            office_hours = '$office_hours',
            email = '$email',
            fees = '$fees',
            established = '$established',
            website = '$website',
            maps_link = '$maps_link',
            directions_link = '$directions_link',
            maps_embed = '$maps_embed',
            social_media = '$social_media',
            nearby_places = '$nearby_places_json',
            transport_options = '$transport_options_json',
            transportation_routes = '$transportation_routes_json',
            details_link = '$details_link'
            WHERE id = $id";
    } else {
        $query = "INSERT INTO pnps (
            name, image, image_detail, description, location, 
            services, hotlines, office_hours, email, 
            fees, established, website, 
            maps_link, directions_link, maps_embed, social_media, 
            nearby_places, transport_options, transportation_routes, details_link
        ) VALUES (
            '$name', '$target_file', '$target_file_detail', '$description', '$location',
            '$services', '$hotlines', '$office_hours', '$email',
            '$fees', '$established', '$website',
            '$maps_link', '$directions_link', '$maps_embed', '$social_media',
            '$nearby_places_json', '$transport_options_json', '$transportation_routes_json', '$details_link'
        )";
    }

    if ($conn->query($query)) {
        header("Location: add_pnps.php");
        exit();
    } else {
        echo "Error: " . $conn->error;
    }
}

$pnpData = [
    'id' => '', 'name' => '', 'description' => '', 'location' => '', 
    'services' => '', 'hotlines' => '', 
    'office_hours' => '', 'email' => '', 'fees' => '','established' => '', 'website' => '', 
    'maps_link' => '', 'directions_link' => '', 'maps_embed' => '', 
    'social_media' => '', 'image' => '', 'image_detail' => '', 
    'nearby_places' => '', 'transport_options' => '', 'transportation_routes' => '', 'details_link' => ''
];
$nearby_places_str = '';
$transport_options = [];
$transportation_routes = [];

if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $result = $conn->query("SELECT * FROM pnps WHERE id = $edit_id");
    if ($result->num_rows > 0) {
        $pnpData = $result->fetch_assoc();
        if (!empty($pnpData['nearby_places'])) {
            $decoded_places = json_decode($pnpData['nearby_places'], true);
            if (is_array($decoded_places)) {
                $nearby_places_str = implode(', ', $decoded_places);
            }
        }
        if (!empty($pnpData['transport_options'])) {
            $transport_options = json_decode($pnpData['transport_options'], true) ?: [];
        }
        if (!empty($pnpData['transportation_routes'])) {
            $transportation_routes = json_decode($pnpData['transportation_routes'], true) ?: [];
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
    <title>PNP Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    <style>
        body{
         background: url('img/bg.png') no-repeat center center fixed;
      background-size: cover;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }
    .card {
      height: 100%;
      display: flex;
      flex-direction: column;
    }
    .card-img-top {
      height: 200px;
      object-fit: cover;
    }
    .transport-option {
      background: #f8f9fa;
      padding: 15px;
      margin-bottom: 15px;
      border-radius: 8px;
    }
    .image-preview-container {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
    }
    .image-preview-wrapper {
      position: relative;
      width: 150px;
      height: 150px;
    }
    .image-preview {
      width: 100%;
      height: 100%;
      object-fit: cover;
      border-radius: 5px;
    }
    .delete-image-btn {
      position: absolute;
      top: 5px;
      right: 5px;
      background: red;
      color: white;
      width: 25px;
      height: 25px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      text-decoration: none;
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
    <div class="container">
        <button type="button" class="btn btn-light text-dark mt-3" onclick="window.location.href='dashboard.php'">
            <i class="bi bi-arrow-left"></i> Back to Dashboard
        </button>
    </div>

    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="text-light">PNP Stations Management</h1>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#pnpModal">
                <i class="bi bi-plus-circle"></i> Add Station
            </button>
        </div>
        
        <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-4">
            <?php while ($pnp = $pnps->fetch_assoc()): ?>
                <div class="col">
                    <div class="card h-100">
                        <img src="<?php echo $pnp['image'] ?: 'img/default-police-station.jpg'; ?>" 
                             class="card-img-top" 
                             alt="<?php echo $pnp['name']; ?>"
                            >
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><?php echo $pnp['name']; ?></h5>
                            <p class="card-text text-muted small">
                                <?php echo substr($pnp['location'], 0, 50) . (strlen($pnp['location']) > 50 ? '...' : ''); ?>
                            </p>
                            <div class="mt-auto d-flex justify-content-between">
                                <a href="add_pnps.php?edit=<?php echo $pnp['id']; ?>" 
                                   class="btn btn-primary btn-sm">
                                   <i class="bi bi-pencil"></i> Edit
                                </a>
                                <a href="?delete=<?php echo $pnp['id']; ?>" 
                                   class="btn btn-danger btn-sm" 
                                   onclick="return confirm('Are you sure you want to delete this PNP station?')">
                                   <i class="bi bi-trash"></i> Delete
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

<!-- Add/Edit PNP Modal -->
<div class="modal fade <?php echo isset($_GET['edit']) ? 'show d-block' : ''; ?>" id="pnpModal" tabindex="-1" aria-labelledby="pnpModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="pnpModalLabel">
                    <?php echo isset($_GET['edit']) ? 'Edit PNP Station' : 'Add PNP Station'; ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="add_pnps.php" enctype="multipart/form-data">
                    <input type="hidden" name="id" value="<?php echo $pnpData['id']; ?>">

                    <div class="form-section">
                        <h5>Basic Information</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Station Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="name" value="<?php echo $pnpData['name']; ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Details Page Link</label>
                                    <input type="text" class="form-control" name="details_link" value="<?php echo $pnpData['details_link']; ?>" placeholder="e.g., pnp_details.php?id=1">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Description</label>
                                    <textarea class="form-control" name="description" rows="3"><?php echo $pnpData['description']; ?></textarea>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Emergency Hotlines</label>
                                    <textarea class="form-control" name="hotlines" rows="2"><?php echo $pnpData['hotlines']; ?></textarea>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Services Offered</label>
                                    <textarea class="form-control" name="services" rows="2"><?php echo $pnpData['services']; ?></textarea>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Operating Hours</label>
                                    <textarea class="form-control" name="office_hours" rows="2"><?php echo $pnpData['office_hours']; ?></textarea>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" name="email" value="<?php echo $pnpData['email']; ?>">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Fees</label>
                                    <textarea class="form-control" name="fees" rows="2"><?php echo $pnpData['fees']; ?></textarea>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Established</label>
                                    <input type="text" class="form-control" name="established" value="<?php echo $pnpData['established']; ?>">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Website</label>
                                    <input type="url" class="form-control" name="website" value="<?php echo $pnpData['website']; ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h5>Images</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-4">
                                    <h6>Main Image</h6>
                                    <input type="file" class="form-control mb-2" name="image" accept="image/*">
                                    <?php if (!empty($pnpData['image'])): ?>
                                        <div class="d-flex align-items-center gap-3 mt-2">
                                            <img src="<?php echo $pnpData['image']; ?>" class="img-thumbnail" style="width:150px;height:150px;object-fit:cover;" onerror="this.src='img/default-police-station.jpg'">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="delete_image" value="1" id="deleteImageCheck">
                                                <label class="form-check-label" for="deleteImageCheck">Delete current image</label>
                                            </div>
                                        </div>
                                        <input type="hidden" name="existing_image" value="<?php echo $pnpData['image']; ?>">
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-4">
                                    <h6>Detail Images</h6>
                                    <input type="file" class="form-control mb-2" name="image_detail[]" multiple accept="image/*">
                                    <?php if (!empty($pnpData['image_detail'])): ?>
                                        <div class="image-preview-container mt-2">
                                            <?php
                                            $images = explode(',', $pnpData['image_detail']);
                                            foreach ($images as $img):
                                                if (!empty($img)):
                                            ?>
                                                <div class="image-preview-wrapper">
                                                    <img src="<?php echo $img; ?>" class="image-preview" onerror="this.src='img/default-police-station.jpg'">
                                                    <a href="?delete_image=<?php echo urlencode($img); ?>&pnp_id=<?php echo $pnpData['id']; ?>" class="delete-image-btn" onclick="return confirm('Are you sure you want to delete this image?')">&times;</a>
                                                </div>
                                            <?php
                                                endif;
                                            endforeach;
                                            ?>
                                        </div>
                                        <input type="hidden" name="existing_image_detail" value="<?php echo $pnpData['image_detail']; ?>">
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h5>Transportation Routes (Detailed)</h5>
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
                        <h5>Old Transportation Options (For Backward Compatibility)</h5>
                        <label class="form-label">Transportation Options (Legacy System)</label>
                        <div id="transport-container">
                            <?php foreach ($transport_options as $index => $option): ?>
                                <div class="transport-option">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <label>Type</label>
                                            <select name="transport_type[]" class="form-control">
                                                <option value="Jeepney" <?= $option['type']=='Jeepney'?'selected':''?>>Jeepney</option>
                                                <option value="Taxi" <?= $option['type']=='Taxi'?'selected':''?>>Taxi</option>
                                                <option value="Tricycle" <?= $option['type']=='Tricycle'?'selected':''?>>Tricycle</option>
                                                <option value="Walking" <?= $option['type']=='Walking'?'selected':''?>>Walking</option>
                                                <option value="Private Car" <?= $option['type']=='Private Car'?'selected':''?>>Private Car</option>
                                                <option value="Ride-hailing" <?= $option['type']=='Ride-hailing'?'selected':''?>>Ride-hailing</option>
                                            </select>
                                        </div>
                                        <div class="col-md-7">
                                            <label>Details</label>
                                            <input type="text" name="transport_details[]" class="form-control" value="<?= $option['details'] ?>">
                                        </div>
                                        <div class="col-md-1 d-flex align-items-end">
                                            <button type="button" class="btn btn-danger btn-sm remove-transport" style="margin-bottom:5px;"><i class="bi bi-x"></i></button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" id="add-transport" class="btn btn-secondary btn-sm mt-2"><i class="bi bi-plus"></i> Add Transportation Option (Legacy)</button>
                    </div>

                    <div class="form-section">
                        <h5>Location & Social Information</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Address</label>
                                    <textarea class="form-control" name="location" rows="3"><?php echo $pnpData['location']; ?></textarea>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Google Maps Link</label>
                                    <input type="url" class="form-control" name="maps_link" value="<?php echo $pnpData['maps_link']; ?>">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Google Maps Directions Link</label>
                                    <input type="url" class="form-control" name="directions_link" value="<?php echo $pnpData['directions_link']; ?>">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Embed Google Map URL</label>
                                    <input type="url" class="form-control" name="map_embed" value="<?php echo $pnpData['maps_embed']; ?>">
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Social Media Links</label>
                                    <textarea class="form-control" name="social_media" rows="3"><?php echo $pnpData['social_media']; ?></textarea>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Nearby Places</label>
                                    <input type="text" class="form-control" name="nearby_places" value="<?php echo $nearby_places_str; ?>" placeholder="Enter comma separated places">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">
                            <?php echo isset($_GET['edit']) ? 'Update Station' : 'Add Station'; ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-show modal if editing
        <?php if (isset($_GET['edit'])): ?>
            document.addEventListener('DOMContentLoaded', function() {
                var modal = new bootstrap.Modal(document.getElementById('pnpModal'));
                modal.show();
            });
        <?php endif; ?>
        
        // Add new transport option (legacy system)
        document.getElementById('add-transport').addEventListener('click', function() {
            const container = document.getElementById('transport-container');
            const newOption = document.createElement('div');
            newOption.className = 'transport-option';
            newOption.innerHTML = `
                <div class="row">
                    <div class="col-md-4">
                        <label>Type</label>
                        <select name="transport_type[]" class="form-control">
                            <option value="Jeepney">Jeepney</option>
                            <option value="Taxi">Taxi</option>
                            <option value="Tricycle">Tricycle</option>
                            <option value="Walking">Walking</option>
                            <option value="Private Car">Private Car</option>
                            <option value="Ride-hailing">Ride-hailing</option>
                        </select>
                    </div>
                    <div class="col-md-7">
                        <label>Details</label>
                        <input type="text" name="transport_details[]" class="form-control">
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="button" class="btn btn-danger btn-sm remove-transport" style="margin-bottom: 5px;">
                            <i class="bi bi-x"></i>
                        </button>
                    </div>
                </div>
            `;
            container.appendChild(newOption);
        });

        // Remove transport option (legacy system)
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-transport')) {
                e.target.closest('.transport-option').remove();
            }
        });
        
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