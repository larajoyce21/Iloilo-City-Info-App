<?php
require_once 'conn.php';

$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;

$query = "SELECT 
    page_url,
    COUNT(*) as visits,
    COUNT(DISTINCT ip_address) as unique_visitors,
    AVG(time_spent) as avg_time,
    (SUM(CASE WHEN is_bounce = 1 THEN 1 ELSE 0 END) * 100.0 / COUNT(*)) as bounce_rate
FROM page_visits
WHERE visit_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY page_url
ORDER BY visits DESC
LIMIT ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $limit);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

header('Content-Type: application/json');
echo json_encode($data);
?>