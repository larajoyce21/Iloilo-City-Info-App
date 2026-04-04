<?php
include 'conn.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['action'] == 'add') {
    $name = trim($_POST['name']);
    $admin_link = trim($_POST['admin_link']);
    $user_link = trim($_POST['user_link']);

    if (!empty($name) && !empty($admin_link) && !empty($user_link)) {
        $stmt = $conn->prepare("INSERT INTO categories (name, admin_link, user_link) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $admin_link, $user_link);
        $stmt->execute();
        $stmt->close();

        header("Location: dashboard.php?success=");
    } else {
        header("Location: dashboard.php?error=Please fill all fields");
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['action'] == 'update') {
    $id = $_POST['id'];
    $name = trim($_POST['name']);
    $admin_link = trim($_POST['admin_link']);
    $user_link = trim($_POST['user_link']);

    if (!empty($id) && !empty($name) && !empty($admin_link) && !empty($user_link)) {
        $stmt = $conn->prepare("UPDATE categories SET name=?, admin_link=?, user_link=? WHERE id=?");
        $stmt->bind_param("sssi", $name, $admin_link, $user_link, $id);
        $stmt->execute();
        $stmt->close();

        header("Location: dashboard.php?success=");
    } else {
        header("Location: dashboard.php?error=Please fill all fields");
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];

    $stmt = $conn->prepare("DELETE FROM categories WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    header("Location: dashboard.php?success=");
    exit();
}

$conn->close();
?>
