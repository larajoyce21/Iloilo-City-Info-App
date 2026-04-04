<?php
require_once 'conn.php';

$listing_id = intval($_GET['id']);
$response = [];

// Get listing details
$stmt = $conn->prepare("SELECT l.*, c.name as category_name FROM listings l JOIN categories c ON l.category_id = c.id WHERE l.id = ?");
$stmt->bind_param("i", $listing_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $response = $result->fetch_assoc();
    
    // Get images
    $stmt = $conn->prepare("SELECT image_path FROM listing_images WHERE listing_id = ?");
    $stmt->bind_param("i", $listing_id);
    $stmt->execute();
    $images = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $response['images'] = $images;
    
    // Get view count
    $stmt = $conn->prepare("SELECT COUNT(*) as views FROM listing_visits WHERE listing_id = ?");
    $stmt->bind_param("i", $listing_id);
    $stmt->execute();
    $views = $stmt->get_result()->fetch_assoc();
    
    $response['views'] = $views['views'];
}

header('Content-Type: application/json');
echo json_encode($response);
?>