<?php
session_start();

include 'conn.php';

if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    mysqli_query($conn, "DELETE FROM bus WHERE id=$delete_id");
    $_SESSION['message'] = "Bus deleted successfully!";
    header("Location: add_bus.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $description = $_POST['description'];
    $routes = $_POST['routes'];
    $location = $_POST['location'];
    $maps_embed = $_POST['maps_embed'];
    $maps_link = $_POST['maps_link'];
    $peak_hours = $_POST['peak_hours'];
    $off_hours = $_POST['off_hours'];
    
    $operator_names = $_POST['operator_name'] ?? [];
    $operator_hours_start = $_POST['operator_hours_start'] ?? [];
    $operator_hours_end = $_POST['operator_hours_end'] ?? [];
    $operator_contacts = $_POST['operator_contact'] ?? [];
    $operator_websites = $_POST['operator_website'] ?? [];
    $operator_addresses = $_POST['operator_address'] ?? [];
    $operator_social_media = $_POST['operator_social_media'] ?? [];
    
    $bus_operators_lines = [];
    $operator_count = count($operator_names);
    
    for ($i = 0; $i < $operator_count; $i++) {
        if (!empty($operator_names[$i])) {
            $name_clean = str_replace(' ', '_', trim($operator_names[$i]));
            $hours_start = trim($operator_hours_start[$i] ?? '');
            $hours_end = trim($operator_hours_end[$i] ?? '');
            $contact = trim($operator_contacts[$i] ?? '');
            $website = trim($operator_websites[$i] ?? '');
            $address = str_replace(' ', '_', trim($operator_addresses[$i] ?? ''));
            
            $operating_hours = $hours_start . ' - ' . $hours_end;
            $social_input = trim($operator_social_media[$i] ?? '');
            $social_links = $social_input ? explode(' ', $social_input) : [];
            $line = "$name_clean|$operating_hours|$contact|$website|$address";
            
            foreach ($social_links as $social) {
                if (trim($social)) {
                    $line .= "|" . trim($social);
                }
            }
            
            $bus_operators_lines[] = $line;
        }
    }
    
    $bus_operators = implode("\n", $bus_operators_lines);
    
    $image = $_POST['existing_image'];
    if (!empty($_FILES['image']['name'])) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $image_name = time() . '_' . basename($_FILES['image']['name']);
        $image_path = $upload_dir . $image_name;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $image_path)) {
            $image = $image_path;
        }
    }
    
    $image_detail = $_POST['existing_image_detail'];
    $uploaded_images = $image_detail ? explode(',', $image_detail) : [];
    
    if (!empty($_FILES['image_detail']['name'][0])) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        foreach ($_FILES['image_detail']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['image_detail']['error'][$key] == 0) {
                $image_name = time() . '_' . $key . '_' . basename($_FILES['image_detail']['name'][$key]);
                $file_path = $upload_dir . $image_name;
                if (move_uploaded_file($tmp_name, $file_path)) {
                    $uploaded_images[] = $file_path;
                }
            }
        }
        
        $image_detail = implode(',', $uploaded_images);
    }
    
    $name = mysqli_real_escape_string($conn, $name);
    $description = mysqli_real_escape_string($conn, $description);
    $routes = mysqli_real_escape_string($conn, $routes);
    $location = mysqli_real_escape_string($conn, $location);
    $maps_embed = mysqli_real_escape_string($conn, $maps_embed);
    $maps_link = mysqli_real_escape_string($conn, $maps_link);
    $peak_hours = mysqli_real_escape_string($conn, $peak_hours);
    $off_hours = mysqli_real_escape_string($conn, $off_hours);
    $image = mysqli_real_escape_string($conn, $image);
    $image_detail = mysqli_real_escape_string($conn, $image_detail);
    $bus_operators = mysqli_real_escape_string($conn, $bus_operators);
    
    if ($id > 0) {
        $query = "UPDATE bus SET 
                 name='$name', 
                 description='$description',
                 image='$image', 
                 image_detail='$image_detail', 
                 routes='$routes',
                 location='$location',
                 maps_embed='$maps_embed',
                 maps_link='$maps_link',
                 peak_hours='$peak_hours',
                 off_hours='$off_hours',
                 bus_operators='$bus_operators' 
                 WHERE id=$id";
    } else {
        $query = "INSERT INTO bus 
                 (name, description, image, image_detail, routes, location, maps_embed, maps_link, peak_hours, off_hours, bus_operators) 
                 VALUES 
                 ('$name', '$description', '$image', '$image_detail', '$routes', '$location', '$maps_embed', '$maps_link', '$peak_hours', '$off_hours', '$bus_operators')";
    }
    
    if (mysqli_query($conn, $query)) {
        $_SESSION['message'] = $id > 0 ? "Bus updated successfully!" : "Bus added successfully!";
    } else {
        $_SESSION['message'] = "Error: " . mysqli_error($conn);
    }
    
    header("Location: add_bus.php");
    exit();
}

$buses = mysqli_query($conn, "SELECT * FROM bus ORDER BY id");

$busData = [
    'id' => '',
    'name' => '',
    'description' => '',
    'routes' => '',
    'location' => '',
    'image' => '',
    'image_detail' => '',
    'maps_embed' => '',
    'maps_link' => '',
    'peak_hours' => '',
    'off_hours' => '',
    'bus_operators' => ''
];

$operator_data = [];
$peak_hours_start = '';
$peak_hours_end = '';
$off_hours_start = '';
$off_hours_end = '';

if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $result = mysqli_query($conn, "SELECT * FROM bus WHERE id=$edit_id");
    if (mysqli_num_rows($result) > 0) {
        $busData = mysqli_fetch_assoc($result);
        
        if (!empty($busData['peak_hours'])) {
            $peak_parts = explode(' - ', $busData['peak_hours']);
            if (count($peak_parts) == 2) {
                $peak_hours_start = $peak_parts[0];
                $peak_hours_end = $peak_parts[1];
            }
        }
        
        if (!empty($busData['off_hours'])) {
            $off_parts = explode(' - ', $busData['off_hours']);
            if (count($off_parts) == 2) {
                $off_hours_start = $off_parts[0];
                $off_hours_end = $off_parts[1];
            }
        }
        
        if (!empty($busData['bus_operators'])) {
            $lines = explode("\n", $busData['bus_operators']);
            foreach ($lines as $line) {
                $line = trim($line);
                if (!$line) continue;
                
                if (strpos($line, '|') !== false) {
                    $parts = explode('|', $line);
                    if (count($parts) >= 5) {
                        $name = str_replace('_', ' ', $parts[0]);
                        $operating_hours = $parts[1];
                        $contact = $parts[2];
                        $website = $parts[3];
                        $address = str_replace('_', ' ', $parts[4]);
                        
                        $social = [];
                        for ($i = 5; $i < count($parts); $i++) {
                            if (trim($parts[$i])) {
                                $social[] = trim($parts[$i]);
                            }
                        }
                        
                        $hours_parts = explode(' - ', $operating_hours);
                        $hours_start = $hours_parts[0] ?? '';
                        $hours_end = $hours_parts[1] ?? '';
                        
                        $operator_data[] = [
                            'name' => $name,
                            'hours_start' => $hours_start,
                            'hours_end' => $hours_end,
                            'contact' => $contact,
                            'website' => $website,
                            'address' => $address,
                            'social' => implode(' ', $social)
                        ];
                    }
                }
            }
        }
    }
}

if (empty($operator_data)) {
    $operator_data[] = [
        'name' => '',
        'hours_start' => '',
        'hours_end' => '',
        'contact' => '',
        'website' => '',
        'address' => '',
        'social' => ''
    ];
}

foreach ($busData as $key => $value) {
    $busData[$key] = htmlspecialchars($value);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bus Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            padding: 20px;
        }
        
        .card {
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .card-header {
            background-color: #004a8d;
            color: white;
        }
        
        .btn-primary {
            background-color: #e30613;
            border-color: #e30613;
        }
        
        .btn-primary:hover {
            background-color: #c10510;
            border-color: #c10510;
        }
        
        .preview-image {
            width: 100px;
            height: 70px;
            object-fit: cover;
            border-radius: 5px;
            margin: 5px;
            border: 1px solid #ddd;
        }
        
        .operator-form {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
        }
        
        .operator-form-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .operator-number {
            background: #004a8d;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        .hours-example {
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: 5px;
        }
        
        .social-example {
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: 5px;
        }
        
        .time-input-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .time-input-group input {
            flex: 1;
        }
        
        .time-separator {
            font-weight: bold;
            color: #495057;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1><i class="fas fa-bus me-2"></i>Bus Management</h1>
                <p class="text-muted">Add and manage bus routes</p>
            </div>
            <a href="dashboard.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
            </a>
        </div>
        
        <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i>
            <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <div class="text-end mb-4">
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#busModal">
                <i class="fas fa-plus me-2"></i>Add New Bus
            </button>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-list me-2"></i>Bus Routes</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Location</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($bus = mysqli_fetch_assoc($buses)): ?>
                            <tr>
                                <td><?php echo $bus['id']; ?></td>
                                <td><?php echo $bus['name']; ?></td>
                                <td><?php echo $bus['location']; ?></td>
                                <td>
                                    <a href="?edit=<?php echo $bus['id']; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="?delete=<?php echo $bus['id']; ?>" class="btn btn-sm btn-outline-danger" 
                                       onclick="return confirm('Delete this bus?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                    <a href="bus1.php?id=<?php echo $bus['id']; ?>" target="_blank" class="btn btn-sm btn-outline-success">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="busModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-bus me-2"></i>
                        <?php echo isset($_GET['edit']) ? 'Edit Bus' : 'Add New Bus'; ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" onclick="clearEditMode()"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" enctype="multipart/form-data" id="busForm">
                        <input type="hidden" name="id" value="<?php echo $busData['id']; ?>">
                        <input type="hidden" name="existing_image" value="<?php echo $busData['image']; ?>">
                        <input type="hidden" name="existing_image_detail" value="<?php echo $busData['image_detail']; ?>">
                        <input type="hidden" name="peak_hours" id="peak_hours_input" value="<?php echo $busData['peak_hours']; ?>">
                        <input type="hidden" name="off_hours" id="off_hours_input" value="<?php echo $busData['off_hours']; ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">Bus Name *</label>
                            <input type="text" class="form-control" name="name" value="<?php echo $busData['name']; ?>">
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Location *</label>
                                <input type="text" class="form-control" name="location" value="<?php echo $busData['location']; ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Main Image</label>
                                <input type="file" class="form-control" name="image" accept="image/*">
                                <?php if ($busData['image']): ?>
                                <small class="text-muted">Current: <?php echo basename($busData['image']); ?></small>
                                <br>
                                <img src="<?php echo $busData['image']; ?>" class="preview-image mt-2" alt="Current Image">
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description *</label>
                            <textarea class="form-control" name="description" rows="3"><?php echo $busData['description']; ?></textarea>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Peak Hours *</label>
                                <div class="time-input-group">
                                    <input type="text" class="form-control peak_hours_start" 
                                           placeholder="6:00AM"
                                           value="<?php echo $peak_hours_start; ?>">
                                    <span class="time-separator">-</span>
                                    <input type="text" class="form-control peak_hours_end" 
                                           placeholder="9:00AM"
                                           value="<?php echo $peak_hours_end; ?>">
                                </div>
                                <div class="hours-example">Busiest times: 6:00AM - 9:00AM</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Off Hours *</label>
                                <div class="time-input-group">
                                    <input type="text" class="form-control off_hours_start" 
                                           placeholder="10:00AM"
                                           value="<?php echo $off_hours_start; ?>">
                                    <span class="time-separator">-</span>
                                    <input type="text" class="form-control off_hours_end" 
                                           placeholder="3:00PM"
                                           value="<?php echo $off_hours_end; ?>">
                                </div>
                                <div class="hours-example">Least busy times: 10:00AM - 3:00PM</div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Routes (one per line) *</label>
                            <textarea class="form-control" name="routes" rows="4"><?php echo $busData['routes']; ?></textarea>
                            <small class="text-muted">Example: Terminal 1 → Molo Plaza → City Proper</small>
                        </div>
                        
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5>Bus Operators</h5>
                                <button type="button" class="btn btn-sm btn-success" id="addOperatorBtn">
                                    <i class="fas fa-plus me-1"></i>Add Operator
                                </button>
                            </div>
                            
                            <div id="operatorsContainer">
                                <?php foreach ($operator_data as $index => $operator): ?>
                                <div class="operator-form" id="operatorForm<?php echo $index; ?>">
                                    <div class="operator-form-header">
                                        <div class="d-flex align-items-center">
                                            <div class="operator-number"><?php echo $index + 1; ?></div>
                                            <h6 class="mb-0 ms-2">Operator <?php echo $index + 1; ?></h6>
                                        </div>
                                        <?php if ($index > 0): ?>
                                        <button type="button" class="btn btn-sm btn-danger remove-operator-btn" data-index="<?php echo $index; ?>">
                                            <i class="fas fa-times"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Company Name *</label>
                                            <input type="text" class="form-control" 
                                                   name="operator_name[]" 
                                                   value="<?php echo $operator['name']; ?>">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Operating Hours *</label>
                                            <div class="time-input-group">
                                                <input type="text" class="form-control" 
                                                       name="operator_hours_start[]" 
                                                       placeholder="6:00AM"
                                                       value="<?php echo $operator['hours_start']; ?>">
                                                <span class="time-separator">-</span>
                                                <input type="text" class="form-control" 
                                                       name="operator_hours_end[]" 
                                                       placeholder="9:00PM"
                                                       value="<?php echo $operator['hours_end']; ?>">
                                            </div>
                                            <div class="hours-example">Format: 6:00AM - 9:00PM (Start time - End time)</div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Contact Number *</label>
                                            <input type="text" class="form-control" 
                                                   name="operator_contact[]" 
                                                   value="<?php echo $operator['contact']; ?>"
                                                   placeholder="e.g., (033) 123-4567">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Website</label>
                                            <input type="text" class="form-control" 
                                                   name="operator_website[]" 
                                                   value="<?php echo $operator['website']; ?>"
                                                   placeholder="e.g., https://example.com">
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Address *</label>
                                        <input type="text" class="form-control" 
                                               name="operator_address[]" 
                                               value="<?php echo $operator['address']; ?>"
                                               placeholder="e.g., Iloilo Terminal">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Social Media Links (space separated)</label>
                                        <input type="text" class="form-control" 
                                               name="operator_social_media[]" 
                                               value="<?php echo $operator['social']; ?>"
                                               placeholder="e.g., facebook.com/company instagram.com/company">
                                        <div class="social-example">Enter social media URLs separated by spaces</div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Google Maps Embed</label>
                                <textarea class="form-control" name="maps_embed" rows="3"><?php echo $busData['maps_embed']; ?></textarea>
                                <small class="text-muted">Paste the iframe src URL</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Google Maps Link</label>
                                <input type="text" class="form-control" name="maps_link" value="<?php echo $busData['maps_link']; ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Detail Images (Multiple)</label>
                            <input type="file" class="form-control" name="image_detail[]" multiple accept="image/*">
                            <?php if ($busData['image_detail']): ?>
                            <div class="mt-2">
                                <small class="text-muted">Current images:</small>
                                <?php 
                                $images = explode(',', $busData['image_detail']);
                                foreach ($images as $img):
                                    if (trim($img)):
                                ?>
                                <img src="<?php echo trim($img); ?>" class="preview-image" alt="Detail">
                                <?php 
                                    endif;
                                endforeach; 
                                ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="text-end">
                            <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal" onclick="clearEditMode()">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Save Bus
                            </button>
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
            var modal = new bootstrap.Modal(document.getElementById('busModal'));
            modal.show();
        });
        <?php endif; ?>
        
        function clearEditMode() {
            if(window.location.href.includes('edit=')) {
                window.location.href = window.location.href.split('?')[0];
            }
        }
        
        document.getElementById('busForm').addEventListener('submit', function(e) {
            const peakStart = document.querySelector('.peak_hours_start').value;
            const peakEnd = document.querySelector('.peak_hours_end').value;
            const offStart = document.querySelector('.off_hours_start').value;
            const offEnd = document.querySelector('.off_hours_end').value;
            
            document.getElementById('peak_hours_input').value = peakStart + ' - ' + peakEnd;
            document.getElementById('off_hours_input').value = offStart + ' - ' + offEnd;
        });
        
        let operatorCount = <?php echo count($operator_data); ?>;
        
        document.getElementById('addOperatorBtn').addEventListener('click', function() {
            const container = document.getElementById('operatorsContainer');
            const newForm = document.createElement('div');
            newForm.className = 'operator-form';
            newForm.id = 'operatorForm' + operatorCount;
            
            newForm.innerHTML = `
                <div class="operator-form-header">
                    <div class="d-flex align-items-center">
                        <div class="operator-number">${operatorCount + 1}</div>
                        <h6 class="mb-0 ms-2">Operator ${operatorCount + 1}</h6>
                    </div>
                    <button type="button" class="btn btn-sm btn-danger remove-operator-btn" data-index="${operatorCount}">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Company Name *</label>
                        <input type="text" class="form-control" 
                               name="operator_name[]" 
                               placeholder="e.g., WVTC Transport">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Operating Hours *</label>
                        <div class="time-input-group">
                            <input type="text" class="form-control" 
                                   name="operator_hours_start[]" 
                                   placeholder="6:00AM">
                            <span class="time-separator">-</span>
                            <input type="text" class="form-control" 
                                   name="operator_hours_end[]" 
                                   placeholder="9:00PM">
                        </div>
                        <div class="hours-example">Format: 6:00AM - 9:00PM (Start time - End time)</div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Contact Number *</label>
                        <input type="text" class="form-control" 
                               name="operator_contact[]" 
                               placeholder="e.g., (033) 123-4567">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Website</label>
                        <input type="text" class="form-control" 
                               name="operator_website[]" 
                               placeholder="e.g., https://example.com">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Address *</label>
                    <input type="text" class="form-control" 
                           name="operator_address[]" 
                           placeholder="e.g., Iloilo Terminal">
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Social Media Links (space separated)</label>
                    <input type="text" class="form-control" 
                           name="operator_social_media[]" 
                           placeholder="e.g., facebook.com/company instagram.com/company">
                    <div class="social-example">Enter social media URLs separated by spaces</div>
                </div>
            `;
            
            container.appendChild(newForm);
            operatorCount++;
            
            const removeBtn = newForm.querySelector('.remove-operator-btn');
            removeBtn.addEventListener('click', function() {
                removeOperatorForm(this.getAttribute('data-index'));
            });
        });
        
        document.querySelectorAll('.remove-operator-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                removeOperatorForm(this.getAttribute('data-index'));
            });
        });
        
        function removeOperatorForm(index) {
            const form = document.getElementById('operatorForm' + index);
            if (form) {
                form.remove();
                updateOperatorNumbers();
            }
        }
        
        function updateOperatorNumbers() {
            const forms = document.querySelectorAll('.operator-form');
            forms.forEach((form, index) => {
                const numberDiv = form.querySelector('.operator-number');
                const title = form.querySelector('h6');
                const removeBtn = form.querySelector('.remove-operator-btn');
                
                if (numberDiv) numberDiv.textContent = index + 1;
                if (title) title.textContent = 'Operator ' + (index + 1);
                form.id = 'operatorForm' + index;
                
                if (removeBtn) {
                    removeBtn.setAttribute('data-index', index);
                    if (index === 0) {
                        removeBtn.style.display = 'none';
                    } else {
                        removeBtn.style.display = 'block';
                    }
                }
            });
            operatorCount = forms.length;
        }
    </script>
</body>
</html>
<?php mysqli_close($conn); ?>