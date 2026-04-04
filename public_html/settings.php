<?php
// settings.php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Include database connection
include 'conn.php';

// Get user details
$userId = $_SESSION['user_id'];
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Close connection
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings | Iloilo City Info</title>
    <!-- Include the same CSS as homepage -->
</head>
<body>
    <!-- Include the same navigation as homepage -->
    
    <main class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h2 class="h4 mb-0">Account Settings</h2>
                    </div>
                    <div class="card-body">
                        <form action="update-settings.php" method="POST" enctype="multipart/form-data">
                            <div class="mb-3 text-center">
                                <img src="<?php echo htmlspecialchars($user['avatar'] ?? 'img/default-avatar.jpg'); ?>" 
                                     alt="Profile Picture" 
                                     class="rounded-circle mb-2" 
                                     width="150" 
                                     height="150">
                                <div class="d-flex justify-content-center">
                                    <input type="file" class="form-control w-auto" id="avatar" name="avatar" accept="image/*">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?php echo htmlspecialchars($user['name']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Notification Preferences</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="notify-email" name="notify_email" checked>
                                    <label class="form-check-label" for="notify-email">Email notifications</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="notify-sms" name="notify_sms">
                                    <label class="form-check-label" for="notify-sms">SMS notifications</label>
                                </div>
                            </div>
                            
                            <div class="text-center mt-4">
                                <button type="submit" class="btn btn-primary px-4">
                                    <i class="fas fa-save me-1"></i> Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Include the same footer as homepage -->
</body>
</html>