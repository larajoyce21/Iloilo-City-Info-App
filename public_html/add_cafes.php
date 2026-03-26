<?php
include 'conn.php';

if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM cafes WHERE id=?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    header("Location: add_cafes.php");
    exit();
}

if (isset($_GET['delete_image']) && isset($_GET['cafe_id'])) {
    $image_to_delete = $_GET['delete_image'];
    $cafe_id = $_GET['cafe_id'];
    
    $stmt = $conn->prepare("SELECT image_detail FROM cafes WHERE id=?");
    $stmt->bind_param("i", $cafe_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    $images = explode(',', $row['image_detail']);
    $updated_images = array_diff($images, [$image_to_delete]);
    $updated_images_str = implode(',', $updated_images);
    
    if (file_exists($image_to_delete)) {
        unlink($image_to_delete);
    }
    
    $stmt = $conn->prepare("UPDATE cafes SET image_detail=? WHERE id=?");
    $stmt->bind_param("si", $updated_images_str, $cafe_id);
    $stmt->execute();
    
    header("Location: add_cafes.php?edit=" . $cafe_id);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'] ?? null;
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $location = $_POST['location'] ?? '';
    $website = $_POST['website'] ?? '';
    $maps_link = $_POST['maps_link'] ?? '';
    $maps_embed = $_POST['map_embed'] ?? '';
    $hours = $_POST['hours'] ?? '';
    $contact = $_POST['contact'] ?? '';
    $menu = $_POST['menu'] ?? '';
    $wifi = $_POST['wifi'] ?? '';
    $seating = $_POST['seating'] ?? '';
    $accessibility = $_POST['accessibility'] ?? '';
    $social_media = $_POST['social_media'] ?? '';
    $directions = $_POST['directions'] ?? '';
    $nearby_places = $_POST['nearby_places'] ?? '';
    $details_link = $_POST['details_link'] ?? '';
    $specialties = $_POST['specialties'] ?? '';
    $culture = $_POST['culture'] ?? '';
    $established = $_POST['established'] ?? '';
    $cuisine = $_POST['cuisine'] ?? '';
    $atmosphere = $_POST['atmosphere'] ?? '';
    $price_range = $_POST['price_range'] ?? '';
    $payment_options = $_POST['payment_options'] ?? '';
    $pet_policy = $_POST['pet_policy'] ?? '';
    $branches = $_POST['branches'] ?? '';
    $email = $_POST['email'] ?? '';

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

    $target_dir = "uploads/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $target_file = $_POST['existing_image'] ?? '';
    if (!empty($_FILES['image']['name'])) {
        $target_file = $target_dir . basename($_FILES["image"]["name"]);
        move_uploaded_file($_FILES["image"]["tmp_name"], $target_file);
        
        if (!empty($_POST['existing_image']) && file_exists($_POST['existing_image'])) {
            unlink($_POST['existing_image']);
        }
    }

    $imagePaths = [];
    $existingImages = !empty($_POST['existing_image_detail']) ? explode(',', $_POST['existing_image_detail']) : [];
    
    if (!empty($_FILES['image_detail']['name'][0])) {
        foreach ($_FILES['image_detail']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['image_detail']['error'][$key] === UPLOAD_ERR_OK) {
                $fileName = basename($_FILES['image_detail']['name'][$key]);
                $targetPath = $target_dir . uniqid() . '_' . $fileName;
                move_uploaded_file($tmp_name, $targetPath);
                $imagePaths[] = $targetPath;
            }
        }
    }
    
    $allImages = array_merge($existingImages, $imagePaths);
    $target_file_detail = implode(',', $allImages);

    if ($id) {
        $query = "UPDATE cafes SET 
            name=?, image=?, image_detail=?, description=?, location=?, 
            maps_link=?, maps_embed=?, website=?, hours=?, contact=?, 
            menu=?, wifi=?, seating=?, accessibility=?, nearby_places=?, 
            social_media=?, directions=?, details_link=?, 
            specialties=?, culture=?, established=?, cuisine=?, 
            atmosphere=?, price_range=?, payment_options=?, 
            pet_policy=?, transportation_routes=?, branches=?, email=?
            WHERE id=?";
        
        $stmt = $conn->prepare($query);
        $types = str_repeat('s', 29) . 'i';
        $stmt->bind_param(
            $types, 
            $name, $target_file, $target_file_detail, $description, $location,
            $maps_link, $maps_embed, $website, $hours, $contact,
            $menu, $wifi, $seating, $accessibility, $nearby_places_json,
            $social_media, $directions, $details_link,
            $specialties, $culture, $established, $cuisine,
            $atmosphere, $price_range, $payment_options,
            $pet_policy, $transportation_routes_json, $branches, $email, $id
        );
    } else {
        $query = "INSERT INTO cafes (
            name, image, image_detail, description, location, 
            maps_link, maps_embed, website, hours, contact, 
            menu, wifi, seating, accessibility, nearby_places, 
            social_media, directions, details_link, 
            specialties, culture, established, cuisine, 
            atmosphere, price_range, payment_options, 
            pet_policy, transportation_routes, branches, email
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($query);
        $types = str_repeat('s', 29);
        $stmt->bind_param(
            $types, 
            $name, $target_file, $target_file_detail, $description, $location,
            $maps_link, $maps_embed, $website, $hours, $contact,
            $menu, $wifi, $seating, $accessibility, $nearby_places_json,
            $social_media, $directions, $details_link,
            $specialties, $culture, $established, $cuisine,
            $atmosphere, $price_range, $payment_options,
            $pet_policy, $transportation_routes_json, $branches, $email
        );
    }

    if ($stmt->execute()) {
        header("Location: add_cafes.php");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }
}

$query = "SELECT id, name, image FROM cafes";
$stmt = $conn->prepare($query);
$stmt->execute();
$cafes = $stmt->get_result();

$cafeData = [
    'id' => '', 'name' => '', 'image' => '', 'image_detail' => '', 'description' => '', 
    'location' => '', 'maps_link' => '', 'maps_embed' => '', 'website' => '', 
    'hours' => '', 'contact' => '', 'menu' => '', 'wifi' => '', 'seating' => '', 
    'accessibility' => '', 'nearby_places' => '', 'social_media' => '', 
    'directions' => '', 'details_link' => '', 'specialties' => '', 
    'culture' => '', 'established' => '', 'cuisine' => '', 'atmosphere' => '', 
    'price_range' => '', 'payment_options' => '', 'pet_policy' => '', 
    'transportation_routes' => '', 'branches' => '', 'email'=> ''
];

$nearby_places_str = '';
$transportation_routes = array();

if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM cafes WHERE id=?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $cafeData = $result->fetch_assoc();
        
        if (!empty($cafeData['nearby_places'])) {
            $decoded_places = json_decode($cafeData['nearby_places'], true);
            if (is_array($decoded_places)) {
                $nearby_places_str = implode(', ', $decoded_places);
            }
        }
        
        if (!empty($cafeData['transportation_routes'])) {
            $decoded_routes = json_decode($cafeData['transportation_routes'], true);
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
    <title>Cafes Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background: url('img/bg.png') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
        }
        .modal {
            <?php echo isset($_GET['edit']) ? 'display: block; background: rgba(0,0,0,0.5);' : ''; ?>
        }
        .card-img-top {
            height: 150px;
            object-fit: cover;
        }
        .transport-route-item {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 10px;
        }
        .transport-route-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-light">Cafes Management</h1>
        <button type="button" class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#cafeModal">Add Cafe</button>
        
        <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-4">
            <?php while ($cafe = $cafes->fetch_assoc()) { ?>
                <div class="col">
                    <div class="card h-100">
                        <img src="<?php echo htmlspecialchars($cafe['image']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($cafe['name']); ?>">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($cafe['name']); ?></h5>
                        </div>
                        <div class="card-footer">
                            <a href="?edit=<?php echo $cafe['id']; ?>" class="btn btn-primary">Edit</a>
                            <a href="?delete=<?php echo $cafe['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this cafe?')">Delete</a>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div class="modal fade <?php echo isset($_GET['edit']) ? 'show d-block' : ''; ?>" id="cafeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo isset($_GET['edit']) ? 'Edit Cafe' : 'Add New Cafe'; ?></h5>
                    <button type="button" class="btn-close" onclick="window.location.href='add_cafes.php'" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($cafeData['id']); ?>">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Name *</label>
                                    <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($cafeData['name']); ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Main Image</label>
                                    <input type="file" class="form-control" name="image" accept="image/*">
                                    <?php if (!empty($cafeData['image'])) { ?>
                                        <div class="mt-2">
                                            <img src="<?php echo htmlspecialchars($cafeData['image']); ?>" class="img-thumbnail" width="100">
                                            <input type="hidden" name="existing_image" value="<?php echo htmlspecialchars($cafeData['image']); ?>">
                                        </div>
                                    <?php } ?>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Detail Images (Max 5)</label>
                                    <input type="file" class="form-control" name="image_detail[]" multiple accept="image/*">
                                    
                                    <?php if (!empty($cafeData['image_detail'])) {
                                        $images = explode(',', $cafeData['image_detail']);
                                        echo '<div class="d-flex flex-wrap gap-2 mt-2">';
                                        foreach ($images as $img) {
                                            if (!empty($img)) { ?>
                                                <div class="position-relative">
                                                    <img src="<?php echo htmlspecialchars($img); ?>" class="img-thumbnail" width="100">
                                                    <a href="?delete_image=<?php echo urlencode($img); ?>&cafe_id=<?php echo $cafeData['id']; ?>" 
                                                       class="position-absolute top-0 end-0 bg-danger text-white px-2 text-decoration-none" 
                                                       onclick="return confirm('Delete this image?')">×</a>
                                                </div>
                                            <?php }
                                        }
                                        echo '</div>';
                                        ?>
                                        <input type="hidden" name="existing_image_detail" value="<?php echo htmlspecialchars($cafeData['image_detail']); ?>">
                                    <?php } ?>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Description</label>
                                    <textarea class="form-control" name="description" rows="3"><?php echo htmlspecialchars($cafeData['description']); ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Branches</label>
                                    <textarea class="form-control" name="branches" rows="3"><?php echo htmlspecialchars($cafeData['branches']); ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Specialties</label>
                                    <textarea class="form-control" name="specialties" rows="3"><?php echo htmlspecialchars($cafeData['specialties']); ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Cafe Culture</label>
                                    <textarea class="form-control" name="culture" rows="3"><?php echo htmlspecialchars($cafeData['culture']); ?></textarea>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Location/Address</label>
                                    <input type="text" class="form-control" name="location" value="<?php echo htmlspecialchars($cafeData['location']); ?>">
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Google Maps Link</label>
                                            <input type="text" class="form-control" name="maps_link" value="<?php echo htmlspecialchars($cafeData['maps_link']); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Embed Map URL</label>
                                            <input type="text" class="form-control" name="map_embed" value="<?php echo htmlspecialchars($cafeData['maps_embed']); ?>">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Website</label>
                                    <input type="text" class="form-control" name="website" value="<?php echo htmlspecialchars($cafeData['website']); ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Operating Hours</label>
                                    <textarea class="form-control" name="hours" rows="3" placeholder="Monday-Friday: 8AM-8PM"><?php echo htmlspecialchars($cafeData['hours']); ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Contact Info</label>
                                    <input type="text" class="form-control" name="contact" value="<?php echo htmlspecialchars($cafeData['contact']); ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($cafeData['email']); ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Menu</label>
                                    <textarea class="form-control" name="menu" rows="3" placeholder="One item per line"><?php echo htmlspecialchars($cafeData['menu']); ?></textarea>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">WiFi</label>
                                            <input type="text" class="form-control" name="wifi" value="<?php echo htmlspecialchars($cafeData['wifi']); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">Seating</label>
                                            <input type="text" class="form-control" name="seating" value="<?php echo htmlspecialchars($cafeData['seating']); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">Accessibility</label>
                                            <input type="text" class="form-control" name="accessibility" value="<?php echo htmlspecialchars($cafeData['accessibility']); ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Transportation Routes Section -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <h5>Transportation Routes</h5>
                                <div id="transport-routes-container">
                                    <?php if (!empty($transportation_routes)): ?>
                                        <?php foreach ($transportation_routes as $index => $route): ?>
                                            <div class="transport-route-item">
                                                <div class="transport-route-header">
                                                    <h6 class="mb-0">Route #<?php echo $index + 1; ?></h6>
                                                    <button type="button" class="btn btn-danger btn-sm" onclick="removeRoute(this)">
                                                        <i class="bi bi-trash"></i> Remove
                                                    </button>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label">Transport Type</label>
                                                            <select class="form-control" name="route_type[]">
                                                                <option value="jeepney" <?php echo ($route['type'] == 'jeepney') ? 'selected' : ''; ?>>Jeepney</option>
                                                                <option value="bus" <?php echo ($route['type'] == 'bus') ? 'selected' : ''; ?>>Bus</option>
                                                                <option value="taxi" <?php echo ($route['type'] == 'taxi') ? 'selected' : ''; ?>>Taxi</option>
                                                                <option value="tricycle" <?php echo ($route['type'] == 'tricycle') ? 'selected' : ''; ?>>Tricycle</option>
                                                            </select>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Route Name/Number</label>
                                                            <input type="text" class="form-control" name="route_name[]" value="<?php echo htmlspecialchars($route['name']); ?>">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label">Route Link (Optional)</label>
                                                            <input type="text" class="form-control" name="route_link[]" value="<?php echo htmlspecialchars($route['link'] ?? ''); ?>">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Description (Optional)</label>
                                                            <textarea class="form-control" name="route_description[]" rows="2"><?php echo htmlspecialchars($route['description'] ?? ''); ?></textarea>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="transport-route-item">
                                            <div class="transport-route-header">
                                                <h6 class="mb-0">Route #1</h6>
                                                <button type="button" class="btn btn-danger btn-sm" onclick="removeRoute(this)">
                                                    <i class="bi bi-trash"></i> Remove
                                                </button>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Transport Type</label>
                                                        <select class="form-control" name="route_type[]">
                                                            <option value="jeepney">Jeepney</option>
                                                            <option value="bus">Bus</option>
                                                            <option value="taxi">Taxi</option>
                                                            <option value="tricycle">Tricycle</option>
                                                        </select>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Route Name/Number</label>
                                                        <input type="text" class="form-control" name="route_name[]">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Route Link (Optional)</label>
                                                        <input type="text" class="form-control" name="route_link[]">
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Description (Optional)</label>
                                                        <textarea class="form-control" name="route_description[]" rows="2"></textarea>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <button type="button" class="btn btn-secondary btn-sm mt-2" onclick="addTransportRoute()">
                                    <i class="bi bi-plus"></i> Add Another Route
                                </button>
                            </div>
                        </div>
                        
                        <div class="row mt-4">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Nearby Places (comma separated)</label>
                                    <input type="text" class="form-control" name="nearby_places" value="<?php echo htmlspecialchars($nearby_places_str); ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Social Media Links</label>
                                    <textarea class="form-control" name="social_media" rows="3"><?php echo htmlspecialchars($cafeData['social_media']); ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Directions</label>
                                    <input type="text" class="form-control" name="directions" value="<?php echo htmlspecialchars($cafeData['directions']); ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Details Page Link</label>
                                    <input type="text" class="form-control" name="details_link" value="<?php echo htmlspecialchars($cafeData['details_link']); ?>">
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Established Year</label>
                                    <input type="text" class="form-control" name="established" value="<?php echo htmlspecialchars($cafeData['established']); ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Cuisine Type</label>
                                    <input type="text" class="form-control" name="cuisine" value="<?php echo htmlspecialchars($cafeData['cuisine']); ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Atmosphere</label>
                                    <input type="text" class="form-control" name="atmosphere" value="<?php echo htmlspecialchars($cafeData['atmosphere']); ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Price Range</label>
                                    <input type="text" class="form-control" name="price_range" value="<?php echo htmlspecialchars($cafeData['price_range']); ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Payment Options</label>
                                    <input type="text" class="form-control" name="payment_options" value="<?php echo htmlspecialchars($cafeData['payment_options']); ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Pet Policy</label>
                                    <input type="text" class="form-control" name="pet_policy" value="<?php echo htmlspecialchars($cafeData['pet_policy']); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between mt-4">
                            <button type="submit" class="btn btn-success px-4">Save</button>
                            <button type="button" class="btn btn-secondary px-4" onclick="window.location.href='add_cafes.php'">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let routeCounter = <?php echo count($transportation_routes) ?: 1; ?>;
        
        function addTransportRoute() {
            routeCounter++;
            const container = document.getElementById('transport-routes-container');
            const newRoute = document.createElement('div');
            newRoute.className = 'transport-route-item';
            newRoute.innerHTML = `
                <div class="transport-route-header">
                    <h6 class="mb-0">Route #${routeCounter}</h6>
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeRoute(this)">
                        <i class="bi bi-trash"></i> Remove
                    </button>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Transport Type</label>
                            <select class="form-control" name="route_type[]">
                                <option value="jeepney">Jeepney</option>
                                <option value="bus">Bus</option>
                                <option value="taxi">Taxi</option>
                                <option value="tricycle">Tricycle</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Route Name/Number</label>
                            <input type="text" class="form-control" name="route_name[]">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Route Link (Optional)</label>
                            <input type="text" class="form-control" name="route_link[]">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description (Optional)</label>
                            <textarea class="form-control" name="route_description[]" rows="2"></textarea>
                        </div>
                    </div>
                </div>
            `;
            container.appendChild(newRoute);
            updateRouteNumbers();
        }
        
        function removeRoute(button) {
            if (document.querySelectorAll('.transport-route-item').length > 1) {
                const routeItem = button.closest('.transport-route-item');
                routeItem.remove();
                updateRouteNumbers();
            } else {
                alert('You must have at least one route item.');
            }
        }
        
        function updateRouteNumbers() {
            const routeItems = document.querySelectorAll('.transport-route-item');
            routeItems.forEach((item, index) => {
                const header = item.querySelector('.transport-route-header h6');
                if (header) {
                    header.textContent = `Route #${index + 1}`;
                }
            });
            routeCounter = routeItems.length;
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>