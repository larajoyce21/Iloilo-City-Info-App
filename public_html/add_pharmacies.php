<?php
include 'conn.php';

if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    
    $stmt = $conn->prepare("SELECT image, image_detail FROM pharmacies WHERE id=?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    $stmt = $conn->prepare("DELETE FROM pharmacies WHERE id=?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    
    if ($row) {
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
    }
    
    header("Location: add_pharmacies.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $description = $_POST['description'];
    $hours = $_POST['hours'];
    $contact = $_POST['contact'];
    $location = $_POST['location'];
    $maps_link = $_POST['maps_link'];
    $website = $_POST['website'];
    $directions_link = $_POST['directions_link'];
    $mapEmbedURL = $_POST['map_embed'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $services = $_POST['services'];
    $branches = $_POST['branches'];
    $details_link = $_POST['details_link'];
    $transportation_options = $_POST['transportation_options'];
    $social_media = $_POST['social_media'];
    
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

    $nearby_places = $_POST['nearby_places'];
    $nearby_places_array = array_map('trim', explode(',', $nearby_places));
    $nearby_places_json = json_encode(array_filter($nearby_places_array));

    $target_dir = "uploads/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $target_file = $_POST['existing_image'];
    if (!empty($_FILES['image']['name'])) {
        if (!empty($target_file) && file_exists($target_file)) {
            unlink($target_file);
        }
        
        $target_file = $target_dir . uniqid() . '_' . basename($_FILES["image"]["name"]);
        move_uploaded_file($_FILES["image"]["tmp_name"], $target_file);
    }

    $imagePaths = [];

    $existingImages = [];
    if (!empty($_POST['existing_image_detail'])) {
        $existingImages = explode(',', $_POST['existing_image_detail']);
    }
    
    if (!empty($_POST['deleted_images'])) {
        $deletedImages = explode(',', $_POST['deleted_images']);
        foreach ($deletedImages as $deletedImage) {
            if (!empty($deletedImage) && file_exists($deletedImage)) {
                unlink($deletedImage);
            }
        }
        $existingImages = array_diff($existingImages, $deletedImages);
    }
    
    if (!empty($_FILES['image_detail']['name'][0])) {
        for ($i = 0; $i < count($_FILES['image_detail']['name']); $i++) {
            $fileName = basename($_FILES['image_detail']['name'][$i]);
            $tmpName = $_FILES['image_detail']['tmp_name'][$i];
            
            $uniqueName = uniqid('img_', true) . '.' . pathinfo($fileName, PATHINFO_EXTENSION);
            $targetPath = $target_dir . $uniqueName;
            
            move_uploaded_file($tmpName, $targetPath);
            $imagePaths[] = $targetPath;
        }
    }
    
    $allImages = array_merge($existingImages, $imagePaths);
    $target_file_detail = implode(',', array_filter($allImages));
     
    if ($id) {
        $query = "UPDATE pharmacies SET name=?, image=?, image_detail=?, description=?, location=?, maps_link=?, maps_embed=?,
         website=?, directions_link=?, hours=?, contact=?, phone=?, email=?, services=?, nearby_places=?, branches=?, details_link=?,
          transportation_options=?, social_media=?, transportation_routes=? WHERE id=?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssssssssssssssssssi", $name, $target_file, $target_file_detail, $description, $location, $maps_link, 
        $mapEmbedURL, $website, $directions_link, $hours, $contact, $phone, $email, $services, $nearby_places_json, $branches,
         $details_link, $transportation_options, $social_media, $transportation_routes_json, $id);
    } else {
        $query = "INSERT INTO pharmacies (name, image, image_detail, description, location, maps_link, maps_embed, website, 
        directions_link, hours, contact, phone, email, services, nearby_places, branches, details_link,
        transportation_options, social_media, transportation_routes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssssssssssssssssss", $name, $target_file, $target_file_detail, $description, $location, $maps_link,
         $mapEmbedURL, $website, $directions_link, $hours, $contact, $phone, $email, $services, $nearby_places_json, $branches, 
         $details_link, $transportation_options, $social_media, $transportation_routes_json);
    }

    $stmt->execute();
    header("Location: add_pharmacies.php");
    exit();
}

$query = "SELECT * FROM pharmacies";
$stmt = $conn->prepare($query);
$stmt->execute();
$pharmacies = $stmt->get_result();

$pharmacyData = [
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
    'phone' => '', 
    'email' => '',
    'services' => '', 
    'nearby_places' => '',
    'image_detail' => '',
    'branches' => '',
    'details_link' => '',
    'transportation_options' => '',
    'social_media' => '',
    'transportation_routes' => ''
];

$transportation_routes = array();
$nearby_places_str = '';

if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM pharmacies WHERE id=?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $pharmacyData = $result->fetch_assoc();
        if (!empty($pharmacyData['transportation_routes'])) {
            $decoded_routes = json_decode($pharmacyData['transportation_routes'], true);
            if (is_array($decoded_routes)) {
                $transportation_routes = $decoded_routes;
            }
        }
        if (!empty($pharmacyData['nearby_places'])) {
            $decoded_places = json_decode($pharmacyData['nearby_places'], true);
            if (is_array($decoded_places)) {
                $nearby_places_str = implode(', ', $decoded_places);
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
    <title>Pharmacy Management</title>
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
            border-radius: 20px;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        .card-img-top {
            height: 150px;
            object-fit: cover;
        }
        .delete-image-btn {
            position: absolute;
            top: 5px;
            right: 5px;
            background: rgba(255,0,0,0.7);
            color: white;
            border: none;
            border-radius: 50%;
            width: 25px;
            height: 25px;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
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
        .form-section {
            background: rgba(255, 255, 255, 0.95);
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
    <div class="container">
        <button type="button" class="btn btn-light text-dark mt-3" onclick="window.location.href='dashboard.php'">Back to Dashboard</button>
    </div>
    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="text-light">Pharmacy Management</h1>
            <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#pharmacyModal">Add Pharmacy</button>
        </div>
        <div class="row row-cols-1 row-cols-md-3 row-cols-lg-5 g-3">
            <?php while ($pharmacy = $pharmacies->fetch_assoc()) { ?>
                <div class="col">
                    <div class="card h-100">
                        <img src="<?php echo $pharmacy['image']; ?>" class="card-img-top" alt="Pharmacy Image">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><?php echo $pharmacy['name']; ?></h5>
                            <p class="card-text text-muted small">
                                <?php echo substr($pharmacy['description'], 0, 60) . (strlen($pharmacy['description']) > 60 ? '...' : ''); ?>
                            </p>
                        </div>
                        <div class="card-footer bg-transparent">
                            <div class="d-flex justify-content-center gap-2">
                                <a href="add_pharmacies.php?edit=<?php echo $pharmacy['id']; ?>" class="btn btn-primary btn-sm">Update</a>
                                <a href="?delete=<?php echo $pharmacy['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">Delete</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>

    <div class="modal fade <?php echo isset($_GET['edit']) ? 'show d-block' : ''; ?>" id="pharmacyModal" tabindex="-1" aria-labelledby="pharmacyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="pharmacyModalLabel"><?php echo isset($_GET['edit']) ? 'Edit Pharmacy' : 'Add Pharmacy'; ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="add_pharmacies.php" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="<?php echo $pharmacyData['id']; ?>">
                        <input type="hidden" name="existing_image" value="<?php echo $pharmacyData['image']; ?>">
                        <input type="hidden" name="existing_image_detail" id="existing_image_detail" value="<?php echo $pharmacyData['image_detail']; ?>">
                        <input type="hidden" name="deleted_images" id="deleted_images" value="">
                        
                        <div class="form-section">
                            <h5>Basic Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Name:</label>
                                        <input type="text" class="form-control" name="name" value="<?php echo $pharmacyData['name']; ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Details Page Link:</label>
                                        <input type="text" class="form-control" name="details_link" value="<?php echo $pharmacyData['details_link']; ?>" placeholder="e.g., pharmacy1.php">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Description:</label>
                                        <textarea class="form-control" name="description" rows="3"><?php echo $pharmacyData['description']; ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Address:</label>
                                        <input type="text" class="form-control" name="location" value="<?php echo $pharmacyData['location']; ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Website:</label>
                                        <input type="text" class="form-control" name="website" value="<?php echo $pharmacyData['website']; ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Phone:</label>
                                        <input type="text" class="form-control" name="phone" value="<?php echo $pharmacyData['phone']; ?>">
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Email:</label>
                                        <input type="email" class="form-control" name="email" value="<?php echo $pharmacyData['email']; ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Contact Person:</label>
                                        <input type="text" class="form-control" name="contact" value="<?php echo $pharmacyData['contact']; ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Operating Hours</label>
                                        <textarea class="form-control" name="hours" rows="3" placeholder="e.g., Monday: 9AM - 5PM"><?php echo $pharmacyData['hours']; ?></textarea>
                                    </div>
                                        
                                    <div class="mb-3">
                                        <label class="form-label">Branches</label>
                                        <textarea class="form-control" name="branches" rows="2" placeholder="Enter one branch per line"><?php echo $pharmacyData['branches']; ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Social Media Links</label>
                                        <textarea class="form-control" name="social_media" rows="3"><?php echo $pharmacyData['social_media']; ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h5>Services & Features</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Services:</label>
                                        <textarea class="form-control" name="services" rows="3" placeholder="Enter one service per line"><?php echo $pharmacyData['services']; ?></textarea>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Transportation Options (General):</label>
                                        <textarea class="form-control" name="transportation_options" rows="3"><?php echo $pharmacyData['transportation_options']; ?></textarea>
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
                            <h5>Images</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Upload Card Image:</label>
                                        <input type="file" class="form-control" name="image">
                                        <?php if ($pharmacyData['image']) { ?>
                                            <div class="mt-2">
                                                <img src="<?php echo $pharmacyData['image']; ?>" class="img-thumbnail" width="100">
                                            </div>
                                        <?php } ?>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Upload Detail Images (Max 5):</label>
                                        <input type="file" class="form-control" name="image_detail[]" multiple accept="image/*">
                                        
                                        <div class="image-preview-container" id="imagePreviewContainer">
                                            <?php
                                            if (!empty($pharmacyData['image_detail'])) {
                                                $existingImages = explode(',', $pharmacyData['image_detail']);
                                                foreach ($existingImages as $image) {
                                                    if (!empty($image)) {
                                                        echo '<div class="image-preview">';
                                                        echo '<img src="' . $image . '" alt="Detail Image">';
                                                        echo '<button type="button" class="delete-image-btn" data-image="' . $image . '">×</button>';
                                                        echo '</div>';
                                                    }
                                                }
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <h5>Location Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Google Map Link:</label>
                                        <input type="text" class="form-control" name="maps_link" value="<?php echo $pharmacyData['maps_link']; ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Google Map Embed URL:</label>
                                        <input type="text" class="form-control" name="map_embed" value="<?php echo $pharmacyData['maps_embed']; ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Directions Link:</label>
                                        <input type="text" class="form-control" name="directions_link" value="<?php echo $pharmacyData['directions_link']; ?>">
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Nearby Places (comma separated):</label>
                                        <input type="text" class="form-control" name="nearby_places" value="<?php echo $nearby_places_str; ?>" placeholder="e.g., Hospital, Mall, School">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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
            document.addEventListener('DOMContentLoaded', function() {
                var modal = new bootstrap.Modal(document.getElementById('pharmacyModal'));
                modal.show();
            });
        <?php endif; ?>
        
        // Handle image deletion
        document.addEventListener('DOMContentLoaded', function() {
            const deletedImages = [];
            const deletedImagesInput = document.getElementById('deleted_images');
            const existingImagesInput = document.getElementById('existing_image_detail');
            
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('delete-image-btn')) {
                    const imagePath = e.target.getAttribute('data-image');
                    deletedImages.push(imagePath);
                    deletedImagesInput.value = deletedImages.join(',');
                    
                    // Update existing images
                    let existingImages = existingImagesInput.value.split(',');
                    existingImages = existingImages.filter(img => img !== imagePath);
                    existingImagesInput.value = existingImages.join(',');
                    
                    // Remove the image container
                    e.target.parentElement.remove();
                }
            });
            
            // Preview new images before upload
            const fileInput = document.querySelector('input[name="image_detail[]"]');
            const previewContainer = document.getElementById('imagePreviewContainer');
            
            fileInput.addEventListener('change', function() {
                const files = this.files;
                for (let i = 0; i < files.length; i++) {
                    const file = files[i];
                    if (file.type.startsWith('image/')) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            const preview = document.createElement('div');
                            preview.className = 'image-preview';
                            preview.innerHTML = `
                                <img src="${e.target.result}" alt="Preview">
                            `;
                            previewContainer.appendChild(preview);
                        }
                        reader.readAsDataURL(file);
                    }
                }
            });
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