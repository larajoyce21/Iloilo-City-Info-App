<?php
require_once 'conn.php';

$listing_id = intval($_GET['id']);
$images = [];

$stmt = $conn->prepare("SELECT id, image_path FROM listing_images WHERE listing_id = ?");
$stmt->bind_param("i", $listing_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $images[] = $row;
}

header('Content-Type: application/json');
echo json_encode($images);
?>