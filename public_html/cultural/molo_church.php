<?php
$db = new mysqli('localhost', 'root', '', 'app');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

$result = $db->query("SELECT * FROM molo_church ORDER BY id DESC LIMIT 1");
$data = $result ? $result->fetch_assoc() : null;
?>
<!DOCTYPE html>
<html>
<head>
    <title><?= $data ? htmlspecialchars($data['name']) : 'Molo Church' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <?php if ($data) { ?>
        <div class="card shadow-lg">
            <?php if (!empty($data['image'])) { ?>
                <img src="uploads/<?= htmlspecialchars($data['image']) ?>" class="card-img-top" alt="<?= htmlspecialchars($data['name']) ?>">
            <?php } ?>
            <div class="card-body">
                <h1 class="card-title"><?= htmlspecialchars($data['name']) ?></h1>
                <p class="text-muted"><?= htmlspecialchars($data['location']) ?></p>
                <h4>Description</h4>
                <p><?= nl2br(htmlspecialchars($data['description'])) ?></p>
                <h4>History</h4>
                <p><?= nl2br(htmlspecialchars($data['history'])) ?></p>
                <?php if (!empty($data['maps_link'])) { ?>
                    <a href="<?= htmlspecialchars($data['maps_link']) ?>" target="_blank" class="btn btn-primary mt-3">View on Google Maps</a>
                <?php } ?>
            </div>
        </div>
    <?php } else { ?>
        <div class="alert alert-warning text-center">
            No details available for Molo Church yet. Please contact the site administrator.
        </div>
    <?php } ?>
</div>
</body>
</html>
