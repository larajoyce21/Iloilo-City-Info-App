<?php
session_start();
include 'conn.php'; 

function ensure_upload_dir($dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
}

function normalize_image_list($list_str) {
    $arr = array_filter(array_map('trim', explode(',', (string)$list_str)));
    return array_values($arr);
}

function join_image_list($arr) {
    $arr = array_filter(array_map('trim', $arr));
    return implode(',', $arr);
}

$target_dir = 'uploads/';
$allowed_types = ['jpg','jpeg','png','gif'];
ensure_upload_dir($target_dir);

if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];

    $res = $conn->query("SELECT image, image_detail FROM popular WHERE id=$delete_id");
    if ($res && $res->num_rows) {
        $row = $res->fetch_assoc();
        if (!empty($row['image']) && file_exists($row['image'])) @unlink($row['image']);
        $imgs = normalize_image_list($row['image_detail']);
        foreach ($imgs as $im) { if ($im && file_exists($im)) @unlink($im); }
    }

    $conn->query("DELETE FROM popular WHERE id=$delete_id");
    header('Location: add_populars.php');
    exit();
}

if (isset($_GET['delete_image']) && isset($_GET['popular_id'])) {
    $popular_id = (int)$_GET['popular_id'];
    $image_to_delete = $_GET['delete_image']; 

    $result = $conn->query("SELECT image_detail FROM popular WHERE id=$popular_id");
    if ($result && $result->num_rows) {
        $popular = $result->fetch_assoc();
        $images = normalize_image_list($popular['image_detail']);
        $images = array_values(array_filter($images, function($img) use ($image_to_delete) { return $img !== $image_to_delete; }));

        $conn->query("UPDATE popular SET image_detail='" . $conn->real_escape_string(join_image_list($images)) . "' WHERE id=$popular_id");
        if ($image_to_delete && file_exists($image_to_delete)) { @unlink($image_to_delete); }
    }

    header("Location: add_populars.php?edit=".$popular_id);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) && $_POST['id'] !== '' ? (int)$_POST['id'] : null;
    $name = isset($_POST['name']) ? $conn->real_escape_string($_POST['name']) : '';
    $details_link = isset($_POST['details_link']) ? $conn->real_escape_string($_POST['details_link']) : '';

    $main_image_path = isset($_POST['existing_image']) ? $_POST['existing_image'] : '';
    if (!empty($_FILES['image']['name'])) {
        $orig = basename($_FILES['image']['name']);
        $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
        if (in_array($ext, $allowed_types)) {
            $unique = uniqid('main_', true) . '.' . $ext;
            $dest = $target_dir . $unique;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
                $main_image_path = $dest;
            }
        } else {
            die('Error: Only JPG, JPEG, PNG, and GIF files are allowed for the main image.');
        }
    }

    $existing_images = [];
    if (!empty($_POST['existing_image_detail'])) {
        $existing_images = normalize_image_list($_POST['existing_image_detail']);
    }

    $new_detail_images = [];
    if (!empty($_FILES['image_detail']['name'][0])) {
        $total_new = count($_FILES['image_detail']['name']);
        $total_combined = count($existing_images) + $total_new;
        if ($total_combined > 5) {
            die('Error: You can upload a maximum of 5 detail images total.');
        }

        for ($i = 0; $i < $total_new; $i++) {
            if (empty($_FILES['image_detail']['name'][$i])) continue;
            $orig = basename($_FILES['image_detail']['name'][$i]);
            $tmp = $_FILES['image_detail']['tmp_name'][$i];
            $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
            if (in_array($ext, $allowed_types)) {
                $unique = uniqid('img_', true) . '.' . $ext;
                $dest = $target_dir . $unique;
                if (move_uploaded_file($tmp, $dest)) {
                    $new_detail_images[] = $dest;
                } else {
                    die('Error uploading one of the images.');
                }
            } else {
                die('Error: Only JPG, JPEG, PNG, and GIF files are allowed.');
            }
        }
    }

    $all_detail_images = array_values(array_merge($existing_images, $new_detail_images));
    $detail_images_str = $conn->real_escape_string(join_image_list($all_detail_images));

    if ($id) {
        $sql = "UPDATE popular SET name='$name', image='".$conn->real_escape_string($main_image_path)."', image_detail='$detail_images_str', details_link='$details_link' WHERE id=$id";
        $conn->query($sql);
    } else {
        $sql = "INSERT INTO popular (name, image, image_detail, details_link) VALUES ('$name','".$conn->real_escape_string($main_image_path)."','$detail_images_str','$details_link')";
        $conn->query($sql);
    }

    header('Location: add_populars.php');
    exit();
}


$populars = $conn->query("SELECT * FROM popular ORDER BY id ASC");

$popularData = [
    'id' => '',
    'name' => '',
    'image' => '',
    'details_link' => '',
    'image_detail' => ''
];
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $res = $conn->query("SELECT * FROM popular WHERE id=$edit_id");
    if ($res && $res->num_rows) { $popularData = $res->fetch_assoc(); }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Popular Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: url('img/bg.png') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .card-img-top { height: 150px; width: 100%; object-fit: cover; }
        .modal-content { background-color: #f8f9fa; }
        .img-thumbnail { max-width: 100px; max-height: 100px; }
        .back-bar { background: rgba(255,255,255,0.2); }
    </style>
</head>
<body>
    <div class="container mt-3">
        <button type="button" class="btn btn-light text-dark" onclick="window.location.href='dashboard.php'">Back to Dashboard</button>
    </div>

    <div class="container mt-4 mb-5">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h1 class="text-light m-0">Popular Management</h1>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#popularModal">Add Popular</button>
        </div>

        <div class="row row-cols-1 row-cols-md-3 row-cols-lg-5 g-3">
            <?php if ($populars && $populars->num_rows): while ($popular = $populars->fetch_assoc()) { ?>
                <div class="col">
                    <div class="card h-100 shadow-sm">
                        <img src="<?php echo $popular['image']; ?>" class="card-img-top" alt="Popular Image">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo $popular['name']; ?></h5>
                        </div>
                        <div class="card-footer bg-transparent">
                            <div class="d-flex justify-content-between">
                                <a href="add_populars.php?edit=<?php echo $popular['id']; ?>" class="btn btn-primary btn-sm">Update</a>
                                <a href="?delete=<?php echo $popular['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">Delete</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php } endif; ?>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div class="modal fade" id="popularModal" tabindex="-1" aria-labelledby="popularModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="popularModalLabel"><?php echo isset($_GET['edit']) ? 'Edit Popular' : 'Add Popular'; ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="add_populars.php" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="<?php echo $popularData['id']; ?>">

                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" class="form-control" name="name" value="<?php echo $popularData['name']; ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Details Page Link</label>
                            <input type="text" class="form-control" name="details_link" value="<?php echo $popularData['details_link']; ?>">
                            <small class="text-muted">Enter the URL for the details page</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Main Image</label>
                            <input type="file" class="form-control" name="image" accept="image/*">
                            <?php if (!empty($popularData['image'])) { ?>
                                <div class="mt-2">
                                    <img src="<?php echo $popularData['image']; ?>" class="img-thumbnail" alt="Main Image">
                                    <input type="hidden" name="existing_image" value="<?php echo $popularData['image']; ?>">
                                </div>
                            <?php } ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Detail Images (Max 5)</label>
                            <input type="file" class="form-control" name="image_detail[]" multiple accept="image/*">
                            <small class="text-muted">You can upload multiple images (2-5 recommended)</small>

                            <?php if (!empty($popularData['image_detail'])) { 
                                $images = normalize_image_list($popularData['image_detail']); ?>
                                <div class="d-flex flex-wrap mt-2">
                                    <?php foreach ($images as $img) { if (!empty($img)) { ?>
                                        <div class="position-relative me-2 mb-2">
                                            <img src="<?php echo $img; ?>" class="img-thumbnail" alt="Detail Image">
                                            <a href="?delete_image=<?php echo urlencode($img); ?>&popular_id=<?php echo $popularData['id']; ?>"
                                               class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger text-decoration-none"
                                               onclick="return confirm('Are you sure you want to delete this image?')">&times;</a>
                                        </div>
                                    <?php }} ?>
                                </div>
                                <input type="hidden" name="existing_image_detail" value="<?php echo join_image_list($images); ?>">
                            <?php } ?>
                        </div>

                        <div class="modal-footer">
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
        <?php if (isset($_GET['edit'])) { ?>
        document.addEventListener('DOMContentLoaded', function () {
            var modal = new bootstrap.Modal(document.getElementById('popularModal'));
            modal.show();
        });
        <?php } ?>
    </script>
</body>
</html>
<?php $conn->close(); ?>
