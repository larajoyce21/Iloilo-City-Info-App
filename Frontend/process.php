<?php
session_start();
include "conn.php"; 

// SIGNUP
if (isset($_POST['signup'])) {
    $name = trim($_POST['na']);
    $email = trim($_POST['em']);
    $password = password_hash($_POST['pass'], PASSWORD_DEFAULT); // Hash the password
    $role = ($_POST['role'] === 'admin') ? 'admin' : 'user'; //  role

    $stmt = $conn->prepare("INSERT INTO registration (name, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $email, $password, $role);

    if ($stmt->execute()) {
        $_SESSION['user'] = $name;
        $_SESSION['user_id'] = $conn->insert_id;
        $_SESSION['role'] = $role;
        
        //  based on role
        $redirect_page = ($role === 'admin') ? 'dashboard.php' : 'homepage.php';
        echo "<script>window.location='$redirect_page';</script>";
        exit;
    } else {
        echo "<script>alert('Signup failed! Try again.'); window.location='sign-up.php';</script>";
    }
    $stmt->close();
}

// LOGIN
if (isset($_POST['login'])) {
    $email = trim($_POST['em']);
    $password = $_POST['pass'];

    //  user from the database 
    $stmt = $conn->prepare("SELECT id, name, password, role FROM registration WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $name, $hashed_password, $role);
        $stmt->fetch();

        // Verify hashed password
        if (password_verify($password, $hashed_password)) {
            session_regenerate_id(true); // Security measure
            $_SESSION['user'] = $name;
            $_SESSION['user_id'] = $id;
            $_SESSION['role'] = $role;

            // Redirect based on role
            $redirect_page = ($role === 'admin') ? 'dashboard.php' : 'homepage.php';
            echo "<script>window.location='$redirect_page';</script>";
            exit;
        }
    }

    echo "<script>alert('Invalid email or password!'); window.location='login.php';</script>";
    $stmt->close();
}
?>
