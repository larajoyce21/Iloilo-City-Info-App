<?php
require_once 'conn.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    $query = "SELECT id, image_path FROM business_images WHERE business_id = $id ORDER BY id ASC";
    $result = $conn->query($query);
    
    if ($result) {
        $images = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode($images);
    } else {
        echo json_encode([]);
    }
}
?>