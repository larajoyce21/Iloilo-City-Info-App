<?php
require_once 'conn.php';

if (!isset($_GET['id'])) {
    die(json_encode(['error' => 'No business ID provided']));
}

$businessId = intval($_GET['id']);

// Get business details
$stmt = $conn->prepare("
    SELECT b.*, c.name as category_name 
    FROM business_listings b
    LEFT JOIN business_categories c ON b.category_id = c.id
    WHERE b.id = ?
");
$stmt->bind_param("i", $businessId);
$stmt->execute();
$business = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get business images
$stmt = $conn->prepare("SELECT id, image_path FROM business_images WHERE business_id = ?");
$stmt->bind_param("i", $businessId);
$stmt->execute();
$images = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$business['images'] = $images;

header('Content-Type: application/json');
echo json_encode($business);
?>