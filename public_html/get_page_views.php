<?php
require_once 'conn.php';

$days = isset($_GET['days']) ? intval($_GET['days']) : 30;

$query = "SELECT 
    DATE(visit_date) as date,
    COUNT(*) as visits,
    COUNT(DISTINCT ip_address) as unique_visitors
FROM page_visits
WHERE visit_date >= DATE_SUB(NOW(), INTERVAL ? DAY)
GROUP BY DATE(visit_date)
ORDER BY date";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $days);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

header('Content-Type: application/json');
echo json_encode($data);
?>