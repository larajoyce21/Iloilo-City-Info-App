<?php
require_once 'conn.php';

if (!isset($_GET['id'])) {
    die(json_encode(['error' => 'No business ID provided']));
}

$businessId = intval($_GET['id']);

$stmt = $conn->prepare("SELECT id, image_path FROM business_images WHERE business_id = ?");
$stmt->bind_param("i", $businessId);
$stmt->execute();
$result = $stmt->get_result();

$images = [];
while ($row = $result->fetch_assoc()) {
    $images[] = $row;
}

header('Content-Type: application/json');
echo json_encode($images);
?>