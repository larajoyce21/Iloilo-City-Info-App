<?php
require_once 'conn.php';

if (!isset($_GET['id'])) {
    die(json_encode(['error' => 'No feedback ID provided']));
}

$feedbackId = intval($_GET['id']);

$stmt = $conn->prepare("SELECT * FROM feedback WHERE id = ?");
$stmt->bind_param("i", $feedbackId);
$stmt->execute();
$feedback = $stmt->get_result()->fetch_assoc();
$stmt->close();

header('Content-Type: application/json');
echo json_encode($feedback);
?>