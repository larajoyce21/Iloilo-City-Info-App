<?php
require_once 'conn.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    $query = "SELECT b.*, c.name as category_name 
              FROM business_listings b 
              LEFT JOIN categories c ON b.category_id = c.id 
              WHERE b.id = $id";
    $result = $conn->query($query);
    $business = $result->fetch_assoc();
    
    $images_query = "SELECT * FROM business_images WHERE business_id = $id";
    $images_result = $conn->query($images_query);
    $business['images'] = $images_result->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode($business);
}
?>