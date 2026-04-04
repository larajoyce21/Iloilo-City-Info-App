<?php
require_once 'conn.php';

$query = "
    SELECT c.name as category_name, COUNT(b.id) as count 
    FROM business_categories c
    LEFT JOIN business_listings b ON b.category_id = c.id
    GROUP BY c.id 
    ORDER BY count DESC
";

$result = $conn->query($query);
$data = $result->fetch_all(MYSQLI_ASSOC);

header('Content-Type: application/json');
echo json_encode($data);
?>