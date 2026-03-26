<?php
session_start();

include 'conn.php';
// CRUD Operations
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_period'])) {
        $title = $conn->real_escape_string($_POST['title']);
        $era = $conn->real_escape_string($_POST['era']);
        $start_year = (int)$_POST['start_year'];
        $end_year = (int)$_POST['end_year'];
        $description = $conn->real_escape_string($_POST['description']);
        
        $sql = "INSERT INTO historical_periods (title, era, start_year, end_year, description) 
                VALUES ('$title', '$era', $start_year, $end_year, '$description')";
        
        if ($conn->query($sql)) {
            $_SESSION['message'] = "Period added successfully!";
        } else {
            $_SESSION['error'] = "Error adding period: " . $conn->error;
        }
        header("Location: ".$_SERVER['PHP_SELF']);
        exit;
    }
    elseif (isset($_POST['update_period'])) {
        $id = (int)$_POST['id'];
        $title = $conn->real_escape_string($_POST['title']);
        $era = $conn->real_escape_string($_POST['era']);
        $start_year = (int)$_POST['start_year'];
        $end_year = (int)$_POST['end_year'];
        $description = $conn->real_escape_string($_POST['description']);
        
        $sql = "UPDATE historical_periods SET 
                title = '$title', 
                era = '$era', 
                start_year = $start_year, 
                end_year = $end_year, 
                description = '$description' 
                WHERE id = $id";
        
        if ($conn->query($sql)) {
            $_SESSION['message'] = "Period updated successfully!";
        } else {
            $_SESSION['error'] = "Error updating period: " . $conn->error;
        }
        header("Location: ".$_SERVER['PHP_SELF']);
        exit;
    }
    elseif (isset($_POST['delete_period'])) {
        $id = (int)$_POST['id'];
        $sql = "DELETE FROM historical_periods WHERE id = $id";
        if ($conn->query($sql)) {
            $_SESSION['message'] = "Period deleted successfully!";
        } else {
            $_SESSION['error'] = "Error deleting period: " . $conn->error;
        }
        header("Location: ".$_SERVER['PHP_SELF']);
        exit;
    }
    elseif (isset($_POST['add_writer'])) {
        $name = $conn->real_escape_string($_POST['name']);
        $type = $conn->real_escape_string($_POST['type']);
        $bio = $conn->real_escape_string($_POST['bio']);
        
        $sql = "INSERT INTO writers (name, type, bio) VALUES ('$name', '$type', '$bio')";
        
        if ($conn->query($sql)) {
            $writer_id = $conn->insert_id;
            
            if (!empty($_POST['works'])) {
                $works = explode("\n", $_POST['works']);
                foreach ($works as $work) {
                    $work = trim($conn->real_escape_string($work));
                    if (!empty($work)) {
                        $conn->query("INSERT INTO writer_works (writer_id, title) VALUES ($writer_id, '$work')");
                    }
                }
            }
            $_SESSION['message'] = "Writer added successfully!";
        } else {
            $_SESSION['error'] = "Error adding writer: " . $conn->error;
        }
        header("Location: ".$_SERVER['PHP_SELF']);
        exit;
    }
    elseif (isset($_POST['update_writer'])) {
        $id = (int)$_POST['id'];
        $name = $conn->real_escape_string($_POST['name']);
        $type = $conn->real_escape_string($_POST['type']);
        $bio = $conn->real_escape_string($_POST['bio']);
        
        $sql = "UPDATE writers SET 
                name = '$name', 
                type = '$type', 
                bio = '$bio' 
                WHERE id = $id";
        
        if ($conn->query($sql)) {
            // Delete  works
            $conn->query("DELETE FROM writer_works WHERE writer_id = $id");
            
            // Add new works
            if (!empty($_POST['works'])) {
                $works = explode("\n", $_POST['works']);
                foreach ($works as $work) {
                    $work = trim($conn->real_escape_string($work));
                    if (!empty($work)) {
                        $conn->query("INSERT INTO writer_works (writer_id, title) VALUES ($id, '$work')");
                    }
                }
            }
            $_SESSION['message'] = "Writer updated successfully!";
        } else {
            $_SESSION['error'] = "Error updating writer: " . $conn->error;
        }
        header("Location: ".$_SERVER['PHP_SELF']);
        exit;
    }
    elseif (isset($_POST['delete_writer'])) {
        $id = (int)$_POST['id'];
        $conn->query("DELETE FROM writer_works WHERE writer_id = $id");
        $sql = "DELETE FROM writers WHERE id = $id";
        if ($conn->query($sql)) {
            $_SESSION['message'] = "Writer deleted successfully!";
        } else {
            $_SESSION['error'] = "Error deleting writer: " . $conn->error;
        }
        header("Location: ".$_SERVER['PHP_SELF']);
        exit;
    }
}

$edit_period = null;
$edit_writer = null;

if (isset($_GET['edit_period'])) {
    $id = (int)$_GET['edit_period'];
    $result = $conn->query("SELECT * FROM historical_periods WHERE id = $id");
    if ($result->num_rows > 0) {
        $edit_period = $result->fetch_assoc();
    }
}

if (isset($_GET['edit_writer'])) {
    $id = (int)$_GET['edit_writer'];
    $result = $conn->query("SELECT * FROM writers WHERE id = $id");
    if ($result->num_rows > 0) {
        $edit_writer = $result->fetch_assoc();
        
        // Get works
        $works_result = $conn->query("SELECT title FROM writer_works WHERE writer_id = $id");
        $works = array();
        if ($works_result->num_rows > 0) {
            while($row = $works_result->fetch_assoc()) {
                $works[] = $row['title'];
            }
        }
        $edit_writer['works'] = implode("\n", $works);
    }
}

// Get all data 
$periods = array();
$result = $conn->query("SELECT * FROM historical_periods ORDER BY start_year");
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $periods[] = $row;
    }
}

$writers = array();
$result = $conn->query("SELECT * FROM writers ORDER BY name");
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $works_result = $conn->query("SELECT title FROM writer_works WHERE writer_id = ".$row['id']);
        $works = array();
        if ($works_result->num_rows > 0) {
            while($work_row = $works_result->fetch_assoc()) {
                $works[] = $work_row['title'];
            }
        }
        $row['works'] = $works;
        $writers[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Iloilo History </title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding-top: 20px; background-color: #f8f9fa; }
        .card { margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .card-header { font-weight: bold; }
        .table-responsive { margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">Iloilo History</h1>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success"><?= $_SESSION['message'] ?></div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h2>Historical Periods</h2>
                    </div>
                    <div class="card-body">
                        <?php if ($edit_period): ?>
                            <form method="POST">
                                <input type="hidden" name="id" value="<?= $edit_period['id'] ?>">
                                <div class="mb-3">
                                    <label for="title" class="form-label">Title</label>
                                    <input type="text" class="form-control" id="title" name="title" value="<?= htmlspecialchars($edit_period['title']) ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="era" class="form-label">Era</label>
                                    <select class="form-select" id="era" name="era" required>
                                        <option value="precolonial" <?= $edit_period['era'] == 'precolonial' ? 'selected' : '' ?>>Pre-Colonial</option>
                                        <option value="spanish" <?= $edit_period['era'] == 'spanish' ? 'selected' : '' ?>>Spanish Era</option>
                                        <option value="american" <?= $edit_period['era'] == 'american' ? 'selected' : '' ?>>American Era</option>
                                        <option value="modern" <?= $edit_period['era'] == 'modern' ? 'selected' : '' ?>>Modern</option>
                                    </select>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="start_year" class="form-label">Start Year</label>
                                        <input type="number" class="form-control" id="start_year" name="start_year" value="<?= $edit_period['start_year'] ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="end_year" class="form-label">End Year</label>
                                        <input type="number" class="form-control" id="end_year" name="end_year" value="<?= $edit_period['end_year'] ?>" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="5" required><?= htmlspecialchars($edit_period['description']) ?></textarea>
                                </div>
                                <button type="submit" name="update_period" class="btn btn-primary">Update Period</button>
                                <a href="?" class="btn btn-secondary">Cancel</a>
                            </form>
                        <?php else: ?>
                            <!-- Add Period Form -->
                            <form method="POST">
                                <div class="mb-3">
                                    <label for="title" class="form-label">Title</label>
                                    <input type="text" class="form-control" id="title" name="title" required>
                                </div>
                                <div class="mb-3">
                                    <label for="era" class="form-label">Era</label>
                                    <select class="form-select" id="era" name="era" required>
                                        <option value="precolonial">Pre-Colonial</option>
                                        <option value="spanish">Spanish Era</option>
                                        <option value="american">American Era</option>
                                        <option value="modern">Modern</option>
                                    </select>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="start_year" class="form-label">Start Year</label>
                                        <input type="number" class="form-control" id="start_year" name="start_year" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="end_year" class="form-label">End Year</label>
                                        <input type="number" class="form-control" id="end_year" name="end_year" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="5" required></textarea>
                                </div>
                                <button type="submit" name="add_period" class="btn btn-primary">Add Period</button>
                            </form>
                        <?php endif; ?>

                        <hr>

                        <h3>Existing Periods</h3>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Era</th>
                                        <th>Years</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($periods as $period): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($period['title']) ?></td>
                                        <td><?= ucfirst($period['era']) ?></td>
                                        <td><?= $period['start_year'] ?>-<?= $period['end_year'] ?></td>
                                        <td>
                                            <a href="?edit_period=<?= $period['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="id" value="<?= $period['id'] ?>">
                                                <button type="submit" name="delete_period" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h2>Literary Writers</h2>
                    </div>
                    <div class="card-body">
                        <?php if ($edit_writer): ?>
                            <form method="POST">
                                <input type="hidden" name="id" value="<?= $edit_writer['id'] ?>">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Writer Name</label>
                                    <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($edit_writer['name']) ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="type" class="form-label">Type</label>
                                    <select class="form-select" id="type" name="type" required>
                                        <option value="poetry" <?= $edit_writer['type'] == 'poetry' ? 'selected' : '' ?>>Poetry</option>
                                        <option value="fiction" <?= $edit_writer['type'] == 'fiction' ? 'selected' : '' ?>>Fiction</option>
                                        <option value="drama" <?= $edit_writer['type'] == 'drama' ? 'selected' : '' ?>>Drama</option>
                                        <option value="nonfiction" <?= $edit_writer['type'] == 'nonfiction' ? 'selected' : '' ?>>Non-Fiction</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="bio" class="form-label">Biography</label>
                                    <textarea class="form-control" id="bio" name="bio" rows="3" required><?= htmlspecialchars($edit_writer['bio']) ?></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="works" class="form-label">Notable Works (one per line)</label>
                                    <textarea class="form-control" id="works" name="works" rows="3"><?= htmlspecialchars($edit_writer['works']) ?></textarea>
                                </div>
                                <button type="submit" name="update_writer" class="btn btn-primary">Update Writer</button>
                                <a href="?" class="btn btn-secondary">Cancel</a>
                            </form>
                        <?php else: ?>
                            <!-- Add Writer Form -->
                            <form method="POST">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Writer Name</label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>
                                <div class="mb-3">
                                    <label for="type" class="form-label">Type</label>
                                    <select class="form-select" id="type" name="type" required>
                                        <option value="poetry">Poetry</option>
                                        <option value="fiction">Fiction</option>
                                        <option value="drama">Drama</option>
                                        <option value="nonfiction">Non-Fiction</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="bio" class="form-label">Biography</label>
                                    <textarea class="form-control" id="bio" name="bio" rows="3" required></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="works" class="form-label">Notable Works (one per line)</label>
                                    <textarea class="form-control" id="works" name="works" rows="3"></textarea>
                                </div>
                                <button type="submit" name="add_writer" class="btn btn-primary">Add Writer</button>
                            </form>
                        <?php endif; ?>

                        <hr>

                        <h3>Existing Writers</h3>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Type</th>
                                        <th>Works</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($writers as $writer): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($writer['name']) ?></td>
                                        <td><?= ucfirst($writer['type']) ?></td>
                                        <td>
                                            <ul>
                                                <?php foreach ($writer['works'] as $work): ?>
                                                    <li><?= htmlspecialchars($work) ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </td>
                                        <td>
                                            <a href="?edit_writer=<?= $writer['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="id" value="<?= $writer['id'] ?>">
                                                <button type="submit" name="delete_writer" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>