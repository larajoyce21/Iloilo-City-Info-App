<?php
session_start();
require_once 'conn.php';

$time_period = isset($_GET['time_period']) ? $_GET['time_period'] : '30days';
$date_condition = '';

switch ($time_period) {
    case '7days':
        $date_condition = "WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        break;
    case '30days':
        $date_condition = "WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
        break;
    case '90days':
        $date_condition = "WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)";
        break;
    case 'year':
        $date_condition = "WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
        break;
    default:
        $date_condition = "";
}

// Add posts analytics data
$posts_stats = [
    'total_posts' => 0,
    'posts_today' => 0,
    'posts_this_week' => 0,
    'posts_this_month' => 0,
    'posts_with_images' => 0
];

// Get posts statistics
$result = $conn->query("SELECT COUNT(*) as count FROM posts");
if ($result) $posts_stats['total_posts'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM posts WHERE DATE(created_at) = CURDATE()");
if ($result) $posts_stats['posts_today'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM posts WHERE YEARWEEK(created_at) = YEARWEEK(NOW())");
if ($result) $posts_stats['posts_this_week'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM posts WHERE MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())");
if ($result) $posts_stats['posts_this_month'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM posts WHERE image_path IS NOT NULL AND image_path != ''");
if ($result) $posts_stats['posts_with_images'] = $result->fetch_assoc()['count'];

// Get recent posts for dashboard
$recent_posts = [];
$result = $conn->query("SELECT p.*, u.first_name, u.last_name 
                       FROM posts p 
                       JOIN users u ON p.user_id = u.id 
                       ORDER BY p.created_at DESC 
                       LIMIT 5");
if ($result) {
    $recent_posts = $result->fetch_all(MYSQLI_ASSOC);
}

// Handle POST requests for all operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle announcements
    if (isset($_POST['add_announcement'])) {
        $title = $_POST['title'];
        $description = $_POST['description'];
        
        $image_path = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/announcements/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_name = $_FILES['image']['name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (in_array($file_ext, $allowed_ext)) {
                $file_path = $upload_dir . uniqid() . '_' . $file_name;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $file_path)) {
                    $image_path = $file_path;
                }
            }
        }
        
        $query = "INSERT INTO announcements (title, description, image_path) 
                  VALUES ('$title', '$description', '$image_path')";
        $conn->query($query);
        $_SESSION['message'] = '<div class="alert alert-success">Announcement added successfully</div>';
        header("Location: dashboard.php");
        exit;
    }
    
    if (isset($_POST['update_announcement'])) {
        $id = intval($_POST['id']);
        $title = $_POST['title'];
        $description = $_POST['description'];
        $existing_image = $_POST['existing_image'];
        
        $image_path = $existing_image;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/announcements/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_name = $_FILES['image']['name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (in_array($file_ext, $allowed_ext)) {
                $file_path = $upload_dir . uniqid() . '_' . $file_name;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $file_path)) {
                    if (!empty($existing_image) && file_exists($existing_image)) {
                        unlink($existing_image);
                    }
                    $image_path = $file_path;
                }
            }
        }
        
        $query = "UPDATE announcements 
                  SET title='$title', description='$description', image_path='$image_path' 
                  WHERE id=$id";
        $conn->query($query);
        $_SESSION['message'] = '<div class="alert alert-success">Announcement updated successfully</div>';
        header("Location: dashboard.php");
        exit;
    }
    
    // Handle categories
    if (isset($_POST['category_action'])) {
        $action = $_POST['category_action'];
        
        if ($action === 'add_category') {
            $name = trim($_POST['name']);
            $admin_link = trim($_POST['admin_link']);
            $user_link = trim($_POST['user_link']);
            $icon = trim($_POST['icon']);
            $status = isset($_POST['status']) ? 1 : 0;
            
            $query = "INSERT INTO categories (name, admin_link, user_link, icon, status) 
                      VALUES ('$name', '$admin_link', '$user_link', '$icon', $status)";
            $conn->query($query);
            $_SESSION['message'] = '<div class="alert alert-success">Category added successfully</div>';
        } 
        elseif ($action === 'update_category') {
            $id = intval($_POST['id']);
            $name = trim($_POST['name']);
            $admin_link = trim($_POST['admin_link']);
            $user_link = trim($_POST['user_link']);
            $icon = trim($_POST['icon']);
            $status = isset($_POST['status']) ? 1 : 0;
            
            $query = "UPDATE categories SET 
                      name='$name', 
                      admin_link='$admin_link',
                      user_link='$user_link',
                      icon='$icon', 
                      status=$status 
                      WHERE id=$id";
            $conn->query($query);
            $_SESSION['message'] = '<div class="alert alert-success">Category updated successfully</div>';
        }
        
        header("Location: dashboard.php");
        exit;
    }
    
    // Handle businesses
    if (isset($_POST['business_action'])) {
        $action = $_POST['business_action'];
        
        if ($action === 'add_business') {
            $name = trim($_POST['name']);
            $icon = trim($_POST['icon']);
            $filename = trim($_POST['filename']);
            $is_featured = isset($_POST['is_featured']) ? 1 : 0;
            $status = isset($_POST['status']) ? 1 : 0;
            
            $query = "INSERT INTO business_listings (name, icon, filename, is_featured, status) 
                      VALUES ('$name', '$icon', '$filename', $is_featured, $status)";
            $conn->query($query);
            $business_id = $conn->insert_id;
            
            // Handle image uploads
            if (!empty($_FILES['images']['name'][0])) {
                $upload_dir = 'uploads/businesses/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                    $file_name = $_FILES['images']['name'][$key];
                    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                    $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
                    
                    if (in_array($file_ext, $allowed_ext)) {
                        $file_tmp = $_FILES['images']['tmp_name'][$key];
                        $unique_name = uniqid() . '_' . $file_name;
                        $destination = $upload_dir . $unique_name;
                        
                        if (move_uploaded_file($file_tmp, $destination)) {
                            $conn->query("INSERT INTO business_images (business_id, image_path) 
                                         VALUES ($business_id, '$destination')");
                        }
                    }
                }
            }
            
            $_SESSION['message'] = '<div class="alert alert-success">Business listing added successfully</div>';
        } 
        elseif ($action === 'update_business') {
            $id = intval($_POST['id']);
            $name = trim($_POST['name']);
            $icon = trim($_POST['icon']);
            $filename = trim($_POST['filename']);
            $is_featured = isset($_POST['is_featured']) ? 1 : 0;
            $status = isset($_POST['status']) ? 1 : 0;
            
            $query = "UPDATE business_listings SET 
                      name='$name', 
                      icon='$icon',
                      filename='$filename',
                      is_featured=$is_featured, 
                      status=$status 
                      WHERE id=$id";
            $conn->query($query);
            
            // Handle image uploads
            if (!empty($_FILES['images']['name'][0])) {
                $upload_dir = 'uploads/businesses/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                    $file_name = $_FILES['images']['name'][$key];
                    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                    $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
                    
                    if (in_array($file_ext, $allowed_ext)) {
                        $file_tmp = $_FILES['images']['tmp_name'][$key];
                        $unique_name = uniqid() . '_' . $file_name;
                        $destination = $upload_dir . $unique_name;
                        
                        if (move_uploaded_file($file_tmp, $destination)) {
                            $conn->query("INSERT INTO business_images (business_id, image_path) 
                                         VALUES ($id, '$destination')");
                        }
                    }
                }
            }
            
            $_SESSION['message'] = '<div class="alert alert-success">Business listing updated successfully</div>';
        }
        
        header("Location: dashboard.php");
        exit;
    }
    
    // Handle icon cards
    if (isset($_POST['icon_action'])) {
        $action = $_POST['icon_action'];
        
        if ($action === 'add_icon_card') {
            $title = trim($_POST['title']);
            $icon = trim($_POST['icon']);
            $link = trim($_POST['link']);
            $display_order = intval($_POST['display_order'] ?? 0);
            $is_featured = isset($_POST['is_featured']) ? 1 : 0;
            $status = isset($_POST['status']) ? 1 : 0;
            
            $query = "INSERT INTO icon_cards (title, icon, link, display_order, is_featured, status) 
                      VALUES ('$title', '$icon', '$link', $display_order, $is_featured, $status)";
            $conn->query($query);
            $_SESSION['message'] = '<div class="alert alert-success">Icon card added successfully</div>';
        } 
        elseif ($action === 'update_icon_card') {
            $id = intval($_POST['id']);
            $title = trim($_POST['title']);
            $icon = trim($_POST['icon']);
            $link = trim($_POST['link']);
            $display_order = intval($_POST['display_order'] ?? 0);
            $is_featured = isset($_POST['is_featured']) ? 1 : 0;
            $status = isset($_POST['status']) ? 1 : 0;
            
            $query = "UPDATE icon_cards SET 
                      title='$title', 
                      icon='$icon',
                      link='$link',
                      display_order=$display_order,
                      is_featured=$is_featured, 
                      status=$status 
                      WHERE id=$id";
            $conn->query($query);
            $_SESSION['message'] = '<div class="alert alert-success">Icon card updated successfully</div>';
        }
        
        header("Location: dashboard.php");
        exit;
    }
    
    // Handle services
    if (isset($_POST['save_service'])) {
        $service_id = isset($_POST['edit_id']) ? intval($_POST['edit_id']) : 0;
        $services_id = intval($_POST['services_id']);
        $title = $_POST['title'];
        $description = $_POST['description'];
        $services = $_POST['services'];
        $location = $_POST['location'];
        $contact = $_POST['contact'];
        $hours = $_POST['hours'];
        $icon = $_POST['icon'];
        $requirements = $_POST['requirements'];

        if ($service_id > 0) {
            // Update existing service
            $query = "UPDATE services SET 
                     services_id = '$services_id',
                     title = '$title',
                     description = '$description',
                     services_offered = '$services',
                     location = '$location',
                     contact = '$contact',
                     hours = '$hours',
                     icon = '$icon',
                     requirements = '$requirements'
                     WHERE id = '$service_id'";
        } else {
            // Insert new service
            $query = "INSERT INTO services (services_id, title, description, services_offered, 
                      location, contact, hours, icon, requirements)
                      VALUES ('$services_id', '$title', '$description', '$services', 
                      '$location', '$contact', '$hours', '$icon', '$requirements')";
        }

        if ($conn->query($query)) {
            $_SESSION['message'] = '<div class="alert alert-success">Service saved successfully</div>';
        } else {
            $_SESSION['error'] = '<div class="alert alert-danger">Error saving service: ' . $conn->error . '</div>';
        }
        header("Location: dashboard.php");
        exit();
    }
    
    // Handle service document uploads
    if (isset($_FILES['document_file'])) {
        $service_id = intval($_POST['service_id']);
        $upload_dir = 'uploads/documents/';
        
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_name = $_FILES['document_file']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        if ($file_ext === 'pdf') {
            $file_path = $upload_dir . uniqid() . '_' . $file_name;
            
            if (move_uploaded_file($_FILES['document_file']['tmp_name'], $file_path)) {
                $document_name = pathinfo($file_name, PATHINFO_FILENAME);
                $query = "INSERT INTO service_documents (service_id, document_name, file_path) 
                          VALUES ('$service_id', '$document_name', '$file_path')";
                if (!$conn->query($query)) {
                    $_SESSION['error'] = '<div class="alert alert-danger">Failed to save document to database</div>';
                }
            } else {
                $_SESSION['error'] = '<div class="alert alert-danger">Failed to upload file</div>';
            }
        } else {
            $_SESSION['error'] = '<div class="alert alert-danger">Only PDF files are allowed</div>';
        }
        
        header("Location: dashboard.php?edit=".$service_id);
        exit();
    }
    
    // Handle user operations (Add, Edit, Delete)
    if (isset($_POST['user_action'])) {
        $action = $_POST['user_action'];
        
        if ($action === 'add_user') {
            $username = trim($_POST['username']);
            $email = trim($_POST['email']);
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $first_name = trim($_POST['first_name']);
            $last_name = trim($_POST['last_name']);
            $role = $_POST['role'] ?? 'user';
            $status = isset($_POST['status']) ? 1 : 0;
            
            // Check if username or email already exists
            $check = $conn->query("SELECT id FROM users WHERE username = '$username' OR email = '$email'");
            if ($check->num_rows > 0) {
                $_SESSION['error'] = '<div class="alert alert-danger">Username or email already exists!</div>';
            } else {
                $query = "INSERT INTO users (username, email, password, first_name, last_name, role, status, created_at) 
                          VALUES ('$username', '$email', '$password', '$first_name', '$last_name', '$role', $status, NOW())";
                if ($conn->query($query)) {
                    $_SESSION['message'] = '<div class="alert alert-success">User added successfully</div>';
                } else {
                    $_SESSION['error'] = '<div class="alert alert-danger">Error adding user: ' . $conn->error . '</div>';
                }
            }
        } 
        elseif ($action === 'update_user') {
            $id = intval($_POST['id']);
            $username = trim($_POST['username']);
            $email = trim($_POST['email']);
            $first_name = trim($_POST['first_name']);
            $last_name = trim($_POST['last_name']);
            $role = $_POST['role'] ?? 'user';
            $status = isset($_POST['status']) ? 1 : 0;
            
            $query = "UPDATE users SET 
                      username='$username', 
                      email='$email',
                      first_name='$first_name',
                      last_name='$last_name',
                      role='$role',
                      status=$status
                      WHERE id=$id";
            
            // Update password if provided
            if (!empty($_POST['password'])) {
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $query = "UPDATE users SET 
                          username='$username', 
                          email='$email',
                          password='$password',
                          first_name='$first_name',
                          last_name='$last_name',
                          role='$role',
                          status=$status
                          WHERE id=$id";
            }
            
            if ($conn->query($query)) {
                $_SESSION['message'] = '<div class="alert alert-success">User updated successfully</div>';
            } else {
                $_SESSION['error'] = '<div class="alert alert-danger">Error updating user: ' . $conn->error . '</div>';
            }
        }
        
        header("Location: dashboard.php");
        exit;
    }
}

// Handle GET requests for deletions
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $type = $_GET['type'] ?? '';
    
    switch ($type) {
        case 'user':
            // Don't allow deleting your own account
            if ($id != $_SESSION['user_id'] ?? 0) {
                $conn->query("DELETE FROM users WHERE id = $id");
                $_SESSION['message'] = '<div class="alert alert-success">User deleted successfully</div>';
            } else {
                $_SESSION['error'] = '<div class="alert alert-danger">You cannot delete your own account!</div>';
            }
            break;
            
        case 'business':
            // Delete business images first
            $result = $conn->query("SELECT image_path FROM business_images WHERE business_id = $id");
            while ($row = $result->fetch_assoc()) {
                if (file_exists($row['image_path'])) {
                    unlink($row['image_path']);
                }
            }
            
            // Delete from database
            $conn->query("DELETE FROM business_images WHERE business_id = $id");
            $conn->query("DELETE FROM business_listings WHERE id = $id");
            $_SESSION['message'] = '<div class="alert alert-success">Business listing deleted successfully</div>';
            break;
            
        case 'business_image':
            $business_id = intval($_GET['business_id']);
            $result = $conn->query("SELECT image_path FROM business_images WHERE id = $id");
            if ($row = $result->fetch_assoc()) {
                if (file_exists($row['image_path'])) {
                    unlink($row['image_path']);
                }
                $conn->query("DELETE FROM business_images WHERE id = $id");
                $_SESSION['message'] = '<div class="alert alert-success">Business image deleted successfully</div>';
            }
            header("Location: dashboard.php?edit_business=$business_id");
            exit;
            
        case 'category':
            $conn->query("DELETE FROM categories WHERE id = $id");
            $_SESSION['message'] = '<div class="alert alert-success">Category deleted successfully</div>';
            break;
            
        case 'icon_card':
            $conn->query("DELETE FROM icon_cards WHERE id = $id");
            $_SESSION['message'] = '<div class="alert alert-success">Icon card deleted successfully</div>';
            break;
            
        case 'contact':
            $conn->query("DELETE FROM contact_us WHERE id = $id");
            $_SESSION['message'] = '<div class="alert alert-success">Contact submission deleted successfully</div>';
            break;
            
        case 'announcement':
            $result = $conn->query("SELECT image_path FROM announcements WHERE id = $id");
            if ($row = $result->fetch_assoc()) {
                if (!empty($row['image_path']) && file_exists($row['image_path'])) {
                    unlink($row['image_path']);
                }
            }
            $conn->query("DELETE FROM announcements WHERE id = $id");
            $_SESSION['message'] = '<div class="alert alert-success">Announcement deleted successfully</div>';
            break;
            
        case 'service':
            // First delete associated documents
            $result = $conn->query("SELECT file_path FROM service_documents WHERE service_id = $id");
            while ($row = $result->fetch_assoc()) {
                if (file_exists($row['file_path'])) {
                    unlink($row['file_path']);
                }
            }
            
            // Then delete the documents from database
            $conn->query("DELETE FROM service_documents WHERE service_id = $id");
            
            // Finally delete the service
            $conn->query("DELETE FROM services WHERE id = $id");
            
            $_SESSION['message'] = '<div class="alert alert-success">Service deleted successfully</div>';
            break;
            
        case 'document':
            $service_id = intval($_GET['service_id']);
            $result = $conn->query("SELECT file_path FROM service_documents WHERE id = $id");
            if ($row = $result->fetch_assoc()) {
                if (file_exists($row['file_path'])) {
                    unlink($row['file_path']);
                }
                $conn->query("DELETE FROM service_documents WHERE id = $id");
            }
            header("Location: dashboard.php?edit=$service_id");
            exit;
            
        case 'post':
            $result = $conn->query("SELECT image_path FROM posts WHERE id = $id");
            if ($row = $result->fetch_assoc()) {
                if (!empty($row['image_path']) && file_exists($row['image_path'])) {
                    unlink($row['image_path']);
                }
            }
            $conn->query("DELETE FROM posts WHERE id = $id");
            $_SESSION['message'] = '<div class="alert alert-success">Post deleted successfully</div>';
            break;
            
        default:
            $_SESSION['message'] = '<div class="alert alert-danger">Invalid deletion type</div>';
            break;
    }
    
    header("Location: dashboard.php");
    exit;
}

// Initialize search variables before any potential use
$search_query = '';
$search_results = [];
$search_type = '';

// Search functionality
if (isset($_GET['search']) && !empty($_GET['search_query'])) {
    $search_query = $conn->real_escape_string($_GET['search_query']);
    $search_type = $_GET['search_type'] ?? 'all';
    
    // Log the search
    $conn->query("INSERT INTO search_logs (query, search_type, results_count) VALUES ('$search_query', '$search_type', 0)");
    
    switch ($search_type) {
        case 'businesses':
            $search_results['businesses'] = $conn->query("
                SELECT * FROM business_listings 
                WHERE name LIKE '%$search_query%' 
                OR filename LIKE '%$search_query%'
                ORDER BY name
            ")->fetch_all(MYSQLI_ASSOC);
            break;
            
        case 'categories':
            $search_results['categories'] = $conn->query("
                SELECT * FROM categories 
                WHERE name LIKE '%$search_query%' 
                OR admin_link LIKE '%$search_query%'
                OR user_link LIKE '%$search_query%'
                ORDER BY name
            ")->fetch_all(MYSQLI_ASSOC);
            break;
            
        case 'users':
            $search_results['users'] = $conn->query("
                SELECT id, username, email, first_name, last_name, role, status, created_at, last_login 
                FROM users 
                WHERE username LIKE '%$search_query%' 
                OR email LIKE '%$search_query%'
                OR first_name LIKE '%$search_query%'
                OR last_name LIKE '%$search_query%'
                ORDER BY username
            ")->fetch_all(MYSQLI_ASSOC);
            break;
            
        case 'announcements':
            $search_results['announcements'] = $conn->query("
                SELECT * FROM announcements 
                WHERE title LIKE '%$search_query%' 
                OR description LIKE '%$search_query%'
                ORDER BY created_at DESC
            ")->fetch_all(MYSQLI_ASSOC);
            break;
            
        case 'services':
            $search_results['services'] = $conn->query("
                SELECT s.*, sc.name as category_name 
                FROM services s 
                LEFT JOIN service_categories sc ON s.services_id = sc.id
                WHERE s.title LIKE '%$search_query%' 
                OR s.description LIKE '%$search_query%'
                OR s.services_offered LIKE '%$search_query%'
                OR s.location LIKE '%$search_query%'
                ORDER BY s.title
            ")->fetch_all(MYSQLI_ASSOC);
            break;
            
        case 'posts':
            $search_results['posts'] = $conn->query("
                SELECT p.*, u.first_name, u.last_name 
                FROM posts p 
                JOIN users u ON p.user_id = u.id
                WHERE p.description LIKE '%$search_query%'
                ORDER BY p.created_at DESC
            ")->fetch_all(MYSQLI_ASSOC);
            break;
            
        default: // 'all'
            $search_results['businesses'] = $conn->query("
                SELECT * FROM business_listings 
                WHERE name LIKE '%$search_query%' 
                OR filename LIKE '%$search_query%'
                ORDER BY name
            ")->fetch_all(MYSQLI_ASSOC);
            
            $search_results['categories'] = $conn->query("
                SELECT * FROM categories 
                WHERE name LIKE '%$search_query%' 
                OR admin_link LIKE '%$search_query%'
                OR user_link LIKE '%$search_query%'
                ORDER BY name
            ")->fetch_all(MYSQLI_ASSOC);
            
            $search_results['users'] = $conn->query("
                SELECT id, username, email, first_name, last_name, role, status, created_at, last_login 
                FROM users 
                WHERE username LIKE '%$search_query%' 
                OR email LIKE '%$search_query%'
                OR first_name LIKE '%$search_query%'
                OR last_name LIKE '%$search_query%'
                ORDER BY username
            ")->fetch_all(MYSQLI_ASSOC);
            
            $search_results['announcements'] = $conn->query("
                SELECT * FROM announcements 
                WHERE title LIKE '%$search_query%' 
                OR description LIKE '%$search_query%'
                ORDER BY created_at DESC
            ")->fetch_all(MYSQLI_ASSOC);
            
            $search_results['services'] = $conn->query("
                SELECT s.*, sc.name as category_name 
                FROM services s 
                LEFT JOIN service_categories sc ON s.services_id = sc.id
                WHERE s.title LIKE '%$search_query%' 
                OR s.description LIKE '%$search_query%'
                OR s.services_offered LIKE '%$search_query%'
                OR s.location LIKE '%$search_query%'
                ORDER BY s.title
            ")->fetch_all(MYSQLI_ASSOC);
            
            $search_results['posts'] = $conn->query("
                SELECT p.*, u.first_name, u.last_name 
                FROM posts p 
                JOIN users u ON p.user_id = u.id
                WHERE p.description LIKE '%$search_query%'
                ORDER BY p.created_at DESC
            ")->fetch_all(MYSQLI_ASSOC);
            break;
    }
    
    // Update search log with results count
    $total_results = 0;
    foreach ($search_results as $results) {
        $total_results += count($results);
    }
    $conn->query("UPDATE search_logs SET results_count = $total_results ORDER BY id DESC LIMIT 1");
}

// Get data for dashboard - Updated visitor analytics
$stats = [
    'total_visitors' => 0,
    'total_searches' => 0,
    'active_categories' => 0,
    'active_listings' => 0,
    'total_users' => 0,
    'avg_rating' => 0,
    'featured_listings' => 0,
    'unique_visitors' => 0,
    'period_visitors' => 0,
    'new_users_30days' => 0,
    'total_page_views' => 0,
    'avg_session_duration' => 0
];

// Get updated visitor statistics based on time period
$period_visitors_query = "SELECT COUNT(DISTINCT ip_address) as count FROM visitors $date_condition";
$result = $conn->query($period_visitors_query);
if ($result) $stats['period_visitors'] = $result->fetch_assoc()['count'];

$unique_visitors_query = "SELECT COUNT(DISTINCT ip_address) as count FROM visitors";
$result = $conn->query($unique_visitors_query);
if ($result) $stats['unique_visitors'] = $result->fetch_assoc()['count'];

$total_visitors_query = "SELECT COUNT(*) as count FROM visitors $date_condition";
$result = $conn->query($total_visitors_query);
if ($result) $stats['total_visitors'] = $result->fetch_assoc()['count'];

$page_views_query = "SELECT SUM(page_views) as total FROM daily_visitor_stats $date_condition";
$result = $conn->query($page_views_query);
if ($result && $row = $result->fetch_assoc()) {
    $stats['total_page_views'] = $row['total'] ?? 0;
}

$result = $conn->query("SELECT COUNT(*) as count FROM search_logs");
if ($result) $stats['total_searches'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM categories WHERE status = 1");
if ($result) $stats['active_categories'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM business_listings WHERE status = 1");
if ($result) $stats['active_listings'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM business_listings WHERE status = 1 AND is_featured = 1");
if ($result) $stats['featured_listings'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM users");
if ($result) $stats['total_users'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
if ($result) $stats['new_users_30days'] = $result->fetch_assoc()['count'];

// Calculate average session duration
$session_query = "SELECT AVG(TIMESTAMPDIFF(SECOND, first_visit, last_visit)) as avg_duration 
                  FROM visitor_sessions 
                  WHERE last_visit >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
$result = $conn->query($session_query);
if ($result && $row = $result->fetch_assoc()) {
    $stats['avg_session_duration'] = round($row['avg_duration'] / 60, 1);
}

// Get data for charts
$chartData = [
    'user_growth' => [],
    'user_locations' => [],
    'categories' => [],
    'monthly' => [],
    'device_usage' => [],
    'searches' => [],
    'failed_searches' => [],
    'listings' => [],
    'posts_growth' => [],
    'visitors_daily' => [],
    'browser_usage' => [],
    'hourly_visits' => [],
    'top_pages' => [],
    'returning_visitors' => [],
    'conversion_rate' => []
];

// Get daily visitors for chart
$daily_visitors_query = "SELECT visit_date, total_visits, unique_visitors, page_views
                         FROM daily_visitor_stats 
                         $date_condition 
                         ORDER BY visit_date";
$result = $conn->query($daily_visitors_query);
if ($result) {
    $chartData['visitors_daily'] = $result->fetch_all(MYSQLI_ASSOC);
}

// Get hourly visit distribution
$hourly_query = "SELECT HOUR(visit_time) as hour, COUNT(*) as visits 
                 FROM visitors 
                 $date_condition 
                 GROUP BY HOUR(visit_time) 
                 ORDER BY hour";
$result = $conn->query($hourly_query);
if ($result) {
    $chartData['hourly_visits'] = $result->fetch_all(MYSQLI_ASSOC);
}

// Get top pages visited
// Get top pages visited with friendly navigation names
$pages_query = "SELECT page_visited, COUNT(*) as visits 
                FROM visitors 
                $date_condition 
                GROUP BY page_visited 
                ORDER BY visits DESC 
                LIMIT 10";
$result = $conn->query($pages_query);
if ($result) {
    $chartData['top_pages'] = [];
    while ($row = $result->fetch_assoc()) {
        // Map URLs to friendly navigation names
        $friendly_name = getFriendlyPageName($row['page_visited']);
        $chartData['top_pages'][] = [
            'page_visited' => $friendly_name,
            'original_url' => $row['page_visited'],
            'visits' => $row['visits']
        ];
    }
}

// Helper function to convert URLs to friendly names
function getFriendlyPageName($url) {
    // Remove query parameters
    $url = strtok($url, '?');
    
    // Define page mappings
    $mappings = [
        '/' => '🏠 Home',
        '/index.php' => '🏠 Home',
        '/tourism.php' => '🏝️ Tourism',
        '/transportation.php' => '🚗 Transportation',
        '/business.php' => '🏢 Business',
        '/services.php' => '🛠️ Services',
        '/events.php' => '🎉 Events',
        '/about.php' => 'ℹ️ About',
        '/contact.php' => '📞 Contact',
        '/emergency.php' => '🚨 Emergency',
        '/announcements.php' => '📢 Announcements',
        '/community.php' => '💬 Community',
        '/profile.php' => '👤 Profile',
        '/settings.php' => '⚙️ Settings',
        '/search.php' => '🔍 Search'
    ];
    
    // Check for exact matches
    if (isset($mappings[$url])) {
        return $mappings[$url];
    }
    
    // Check for partial matches (e.g., /tourism/detail.php)
    foreach ($mappings as $path => $name) {
        if (strpos($url, $path) === 0 && $path != '/') {
            return $name;
        }
    }
    
    // If no match, clean up the URL
    $clean_name = ucwords(str_replace(['_', '-'], ' ', basename($url, '.php')));
    return $clean_name ?: '📄 Other Page';
}

// Get returning visitors count
$returning_query = "SELECT COUNT(DISTINCT ip_address) as returning_visitors 
                    FROM visitors 
                    WHERE ip_address IN (
                        SELECT ip_address 
                        FROM visitors 
                        GROUP BY ip_address 
                        HAVING COUNT(DISTINCT visit_date) > 1
                    )";
$result = $conn->query($returning_query);
if ($result && $row = $result->fetch_assoc()) {
    $stats['returning_visitors'] = $row['returning_visitors'];
}

// Get conversion rate (users who registered vs total visitors)
$conversion_query = "SELECT COUNT(DISTINCT u.id) as registered_users 
                     FROM users u 
                     WHERE u.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
$result = $conn->query($conversion_query);
if ($result && $row = $result->fetch_assoc()) {
    $stats['registered_users_30days'] = $row['registered_users'];
    $stats['conversion_rate'] = $stats['unique_visitors'] > 0 ? 
        round(($stats['registered_users_30days'] / $stats['unique_visitors']) * 100, 2) : 0;
}

// Get browser usage
$browser_query = "SELECT browser, COUNT(*) as count 
                  FROM visitors 
                  $date_condition 
                  GROUP BY browser 
                  ORDER BY count DESC 
                  LIMIT 5";
$result = $conn->query($browser_query);
if ($result) {
    $chartData['browser_usage'] = $result->fetch_all(MYSQLI_ASSOC);
}

// Get OS distribution
$os_query = "SELECT os, COUNT(*) as count 
             FROM visitors 
             $date_condition 
             GROUP BY os 
             ORDER BY count DESC 
             LIMIT 5";
$result = $conn->query($os_query);
if ($result) {
    $chartData['os_usage'] = $result->fetch_all(MYSQLI_ASSOC);
}

// User growth data (last 12 months)
$result = $conn->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count 
    FROM users 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY month 
    ORDER BY month
");
if ($result) {
    $chartData['user_growth'] = $result->fetch_all(MYSQLI_ASSOC);
}

// Posts growth data (last 12 months)
$result = $conn->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count 
    FROM posts 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY month 
    ORDER BY month
");
if ($result) {
    $chartData['posts_growth'] = $result->fetch_all(MYSQLI_ASSOC);
}

// User locations data (top 10)
$result = $conn->query("
    SELECT location, COUNT(*) as count 
    FROM users 
    WHERE location IS NOT NULL AND location != ''
    GROUP BY location 
    ORDER BY count DESC 
    LIMIT 10
");
if ($result) {
    $chartData['user_locations'] = $result->fetch_all(MYSQLI_ASSOC);
}

// Monthly visitors data from daily stats
$monthly_visitors_query = "SELECT 
                            DATE_FORMAT(visit_date, '%Y-%m') as month, 
                            SUM(total_visits) as visits,
                            SUM(unique_visitors) as unique_visitors
                           FROM daily_visitor_stats 
                           WHERE visit_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                           GROUP BY month 
                           ORDER BY month";
$result = $conn->query($monthly_visitors_query);
if ($result) {
    $chartData['monthly'] = $result->fetch_all(MYSQLI_ASSOC);
}

// Device usage data
$device_query = "SELECT device_type, COUNT(*) as count 
                 FROM visitors 
                 $date_condition 
                 GROUP BY device_type 
                 ORDER BY count DESC";
$result = $conn->query($device_query);
if ($result) {
    $chartData['device_usage'] = $result->fetch_all(MYSQLI_ASSOC);
}

// Searches data
$result = $conn->query("
    SELECT query, COUNT(*) as count 
    FROM search_logs 
    WHERE results_count > 0
    GROUP BY query 
    ORDER BY count DESC 
    LIMIT 10
");
if ($result) {
    $chartData['searches'] = $result->fetch_all(MYSQLI_ASSOC);
}

// Failed searches
$result = $conn->query("
    SELECT query, COUNT(*) as count 
    FROM search_logs 
    WHERE results_count = 0
    GROUP BY query 
    ORDER BY count DESC 
    LIMIT 10
");
if ($result) {
    $chartData['failed_searches'] = $result->fetch_all(MYSQLI_ASSOC);
}

// Most viewed listings (top 10)
$result = $conn->query("
    SELECT l.name, COUNT(v.id) as views 
    FROM business_listings l
    LEFT JOIN listing_visits v ON v.listing_id = l.id
    GROUP BY l.id 
    ORDER BY views DESC 
    LIMIT 10
");
if ($result) {
    $chartData['listings'] = $result->fetch_all(MYSQLI_ASSOC);
}

// Get all categories for the table
$categories = $conn->query("SELECT * FROM categories ORDER BY name");

// Get all icon cards for the table
$icon_cards = $conn->query("SELECT * FROM icon_cards ORDER BY display_order");

// Get all business listings for the table
$business_listings = $conn->query("SELECT * FROM business_listings ORDER BY name");

// Get all contact submissions
$contacts = $conn->query("SELECT * FROM contact_us ORDER BY created_at DESC");

// Get all posts for management
$all_posts = $conn->query("SELECT p.*, u.first_name, u.last_name 
                          FROM posts p 
                          JOIN users u ON p.user_id = u.id 
                          ORDER BY p.created_at DESC");

// Get recent activity
$recent_activity = [];
$result = $conn->query("
    SELECT * FROM activity_log 
    ORDER BY date DESC 
    LIMIT 5
");
if ($result) {
    $recent_activity = $result->fetch_all(MYSQLI_ASSOC);
}

// Get data for tables
$users_list = $conn->query("SELECT id, username, email, first_name, last_name, role, status, created_at, last_login FROM users ORDER BY created_at DESC");

$tables = [
    'icon_cards' => $icon_cards,
    'categories' => $categories,
    'users' => $users_list,
    'business_listings' => $business_listings,
    'contacts' => $contacts,
    'posts' => $all_posts,
];

// Get data for editing
$edit_data = [];
$documents = [];
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $result = $conn->query("SELECT * FROM services WHERE id = $id");
    $edit_data = $result->fetch_assoc();
    
    $result = $conn->query("SELECT * FROM service_documents WHERE service_id = $id");
    $documents = $result->fetch_all(MYSQLI_ASSOC);
}

// Get categories and services
$categories_list = $conn->query("SELECT * FROM service_categories");
$services_by_services_category = [];
while ($cat = $categories_list->fetch_assoc()) {
    $cat_id = $cat['id'];
    $services = $conn->query("SELECT * FROM services WHERE services_id = $cat_id");
    $services_by_services_category[$cat['name']] = $services->fetch_all(MYSQLI_ASSOC);
}

// Get announcements
$announcements = [];
$result = $conn->query("SELECT * FROM announcements ORDER BY created_at DESC");
if ($result) {
    $announcements = $result->fetch_all(MYSQLI_ASSOC);
}

// Process any messages
$message = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

if (isset($_SESSION['error'])) {
    $message = $_SESSION['error'];
    unset($_SESSION['error']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Dashboard - Iloilo City Info App</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-color: #800000;
            --secondary-color: #198754;
            --info-color: #0d6efd;
            --purple-color: #6f42c1;
            --orange-color: #fd7e14;
            --pink-color: #d63384;
        }
        
        body {
            padding-top: 56px;
            background-color: #f8f9fa;
        }
        
        .navbar {
            box-shadow: 0 2px 10px rgba(0,0,0,.1);
        }
        
        .stat-card {
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            color: white;
            text-align: center;
            transition: transform 0.3s, box-shadow 0.3s;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.15);
        }
        
        .stat-card i {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }
        
        .chart-container {
            position: relative;
            height: 350px;
            margin-bottom: 20px;
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .chart-container canvas {
            max-height: 300px;
        }
        
        .sidebar {
            position: fixed;
            top: 56px;
            bottom: 0;
            left: 0;
            z-index: 100;
            padding: 20px 0;
            box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
            background-color: #fff;
            width: 220px;
        }
        
        .main-content {
            margin-left: 220px;
            padding: 20px;
        }
        
        .status-badge {
            font-size: 0.75rem;
            padding: 0.35em 0.65em;
        }
        
        .featured-badge {
            background-color: #ffc107;
            color: #212529;
        }
        
        .image-thumbnail {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 4px;
            margin-right: 10px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .image-thumbnail:hover {
            transform: scale(1.05);
        }
        
        .activity-item {
            border-left: 3px solid var(--primary-color);
            padding-left: 15px;
            margin-bottom: 15px;
        }
        
        .activity-time {
            font-size: 0.8rem;
            color: #6c757d;
        }
        
        .analytics-section {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .analytics-section h3 {
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .time-period-selector {
            margin-bottom: 20px;
        }
        
        .metric-card {
            border-left: 4px solid var(--primary-color);
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        
        .metric-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .metric-card .card-body {
            padding: 15px;
        }
        
        .metric-card h5 {
            font-size: 0.9rem;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .metric-card .metric-value {
            font-size: 1.8rem;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .metric-card .metric-change {
            font-size: 0.8rem;
        }
        
        .metric-card .metric-change.up {
            color: #198754;
        }
        
        .metric-card .metric-change.down {
            color: #dc3545;
        }
        
        .service-card {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .document-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .announcement-image {
            max-width: 100px;
            max-height: 100px;
            margin-right: 10px;
        }
        
        .search-section {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .search-highlight {
            background-color: #fff3cd;
            padding: 2px 4px;
            border-radius: 3px;
        }
        
        .post-management-image {
            max-width: 80px;
            max-height: 80px;
            object-fit: cover;
            border-radius: 4px;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #800000;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        .role-badge {
            font-size: 0.7rem;
            padding: 3px 8px;
        }
        
        .visitor-stats {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .chart-title {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 15px;
            color: #2c3e50;
            text-align: center;
        }
        
        .stat-icon {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        
        .trend-up {
            color: #28a745;
        }
        
        .trend-down {
            color: #dc3545;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                position: static;
                width: 100%;
                height: auto;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .chart-container {
                height: 250px;
            }
        }
    </style>
</head>
<body>
    <!-- Header Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="dashboard.php">
                <i class="fas fa-city me-2"></i>Iloilo City Info App Admin
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-2"></i>
                            <span class="d-none d-md-inline"><?= $_SESSION['admin_username'] ?? 'Admin' ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                            <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog me-2"></i>Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Sidebar -->
    <div class="sidebar col-md-3 col-lg-2 d-md-block">
        <div class="position-sticky pt-3">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link active" href="dashboard.php">
                        <i class="bi bi-speedometer2 me-2"></i>
                        Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#recent-users">
                        <i class="bi bi-people me-2"></i>
                        Users
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#business-listings">
                        <i class="bi bi-shop me-2"></i>
                        Business Listings
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#categories">
                        <i class="bi bi-tags me-2"></i>
                        Categories
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#posts-management">
                        <i class="bi bi-chat-dots me-2"></i>
                        Community Posts
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#contact-submissions">
                        <i class="bi bi-collection me-2"></i>
                        Contact Submissions
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="add_services.php">
                        <i class="bi bi-collection me-2"></i>
                        Services Information
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#announcements-section">
                        <i class="bi bi-megaphone me-2"></i>
                        News & Events
                    </a>
                </li>

                <!-- Tourist Spots Dropdown -->
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="collapse" href="#tourist-spots" role="button" aria-expanded="false" aria-controls="tourist-spots">
                        <i class="bi bi-map me-2"></i>
                        Tourist Spots
                        <i class="bi bi-caret-down-fill float-end"></i>
                    </a>
                    <div class="collapse ps-4" id="tourist-spots">
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a class="nav-link" href="add_populars.php">
                                    <i class="bi bi-star me-2"></i>
                                    Popular Tourist Attractions
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="add_new_tourist_spots.php">
                                    <i class="bi bi-plus-circle me-2"></i>
                                    New Tourist Spots
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
                <!-- End Tourist Spots Dropdown -->
                
                <li class="nav-item">
                    <a class="nav-link" href="add_emergency.php">
                        <i class="bi bi-telephone me-2"></i>
                        Emergency Hotlines
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="settings.php">
                        <i class="bi bi-gear me-2"></i>
                        Settings
                    </a>
                </li>
            </ul>

            <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                <span>Reports</span>
            </h6>
            <ul class="nav flex-column mb-2">
                <li class="nav-item">
                    <a class="nav-link" href="#analytics">
                        <i class="bi bi-graph-up me-2"></i>
                        Analytics
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="logs.php">
                        <i class="bi bi-list-check me-2"></i>
                        Activity Logs
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content" id="main-content">
        <div class="container-fluid">
            <!-- Page Heading -->
            <div class="d-sm-flex align-items-center justify-content-between mb-4">
                <h1 class="h3 mb-0 text-gray-800">Dashboard Overview</h1>
                <div>
                    <span class="badge bg-info text-white p-2">
                        <i class="fas fa-chart-line me-1"></i> 
                        Real-time Visitor Tracking Active
                    </span>
                </div>
            </div>
            
            <!-- Display messages -->
            <?= $message ?>
                               
            <!-- Search Section -->
            <div class="search-section">
                <h3><i class="fas fa-search me-2"></i> Search Dashboard</h3>
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-6">
                        <label for="search_query" class="form-label">Search Term</label>
                        <input type="text" class="form-control" id="search_query" name="search_query" 
                               value="<?= htmlspecialchars($search_query) ?>" placeholder="Enter search term..." required>
                    </div>
                    <div class="col-md-4">
                        <label for="search_type" class="form-label">Search In</label>
                        <select class="form-select" id="search_type" name="search_type">
                            <option value="all" <?= $search_type === 'all' ? 'selected' : '' ?>>All Content</option>
                            <option value="businesses" <?= $search_type === 'businesses' ? 'selected' : '' ?>>Business Listings</option>
                            <option value="categories" <?= $search_type === 'categories' ? 'selected' : '' ?>>Categories</option>
                            <option value="users" <?= $search_type === 'users' ? 'selected' : '' ?>>Users</option>
                            <option value="announcements" <?= $search_type === 'announcements' ? 'selected' : '' ?>>News & Events</option>
                            <option value="services" <?= $search_type === 'services' ? 'selected' : '' ?>>Services</option>
                            <option value="posts" <?= $search_type === 'posts' ? 'selected' : '' ?>>Community Posts</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" name="search" class="btn btn-primary w-100">
                            <i class="fas fa-search me-1"></i> Search
                        </button>
                    </div>
                </form>
                
                <?php if (!empty($search_query)): ?>
                    <div class="mt-3">
                        <h5>Search Results for "<?= htmlspecialchars($search_query) ?>"</h5>
                        <p class="text-muted">Found <?= array_sum(array_map('count', $search_results)) ?> results</p>
                        
                        <?php if (array_sum(array_map('count', $search_results)) > 0): ?>
                            <!-- Business Results -->
                            <?php if (!empty($search_results['businesses'])): ?>
                                <div class="card mb-3">
                                    <div class="card-header bg-primary text-white">
                                        <h6 class="mb-0"><i class="fas fa-store me-2"></i>Business Listings (<?= count($search_results['businesses']) ?>)</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>ID</th>
                                                        <th>Name</th>
                                                        <th>Status</th>
                                                        <th>Actions</th>
                                                     </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($search_results['businesses'] as $business): ?>
                                                     <tr>
                                                         <td><?= $business['id'] ?></td>
                                                         <td>
                                                            <?= preg_replace("/($search_query)/i", '<span class="search-highlight">$1</span>', $business['name']) ?>
                                                            <?php if ($business['is_featured']): ?>
                                                                <span class="badge featured-badge ms-2">Featured</span>
                                                            <?php endif; ?>
                                                          </td>
                                                          <td>
                                                            <span class="badge rounded-pill bg-<?= $business['status'] ? 'success' : 'secondary' ?>">
                                                                <?= $business['status'] ? 'Active' : 'Inactive' ?>
                                                            </span>
                                                          </td>
                                                          <td>
                                                            <a href="dashboard.php?edit_business=<?= $business['id'] ?>" class="btn btn-sm btn-warning">
                                                                <i class="fas fa-edit me-1"></i> Edit
                                                            </a>
                                                          </td>
                                                      </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                              </table>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Posts Results -->
                            <?php if (!empty($search_results['posts'])): ?>
                                <div class="card mb-3">
                                    <div class="card-header bg-info text-white">
                                        <h6 class="mb-0"><i class="fas fa-comments me-2"></i>Community Posts (<?= count($search_results['posts']) ?>)</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                     <tr>
                                                        <th>ID</th>
                                                        <th>User</th>
                                                        <th>Content</th>
                                                        <th>Date</th>
                                                        <th>Actions</th>
                                                     </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($search_results['posts'] as $post): ?>
                                                     <tr>
                                                         <td><?= $post['id'] ?></td>
                                                         <td><?= $post['first_name'] . ' ' . $post['last_name'] ?></td>
                                                         <td>
                                                            <?= preg_replace("/($search_query)/i", '<span class="search-highlight">$1</span>', substr($post['description'], 0, 100)) ?>
                                                            <?= strlen($post['description']) > 100 ? '...' : '' ?>
                                                          </td>
                                                          <td><?= date('M j, Y', strtotime($post['created_at'])) ?></td>
                                                          <td>
                                                            <a href="dashboard.php?delete=<?= $post['id'] ?>&type=post" 
                                                               class="btn btn-sm btn-danger" 
                                                               onclick="return confirm('Are you sure you want to delete this post?')">
                                                                <i class="fas fa-trash me-1"></i> Delete
                                                            </a>
                                                          </td>
                                                      </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                              </table>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Category Results -->
                            <?php if (!empty($search_results['categories'])): ?>
                                <div class="card mb-3">
                                    <div class="card-header bg-success text-white">
                                        <h6 class="mb-0"><i class="fas fa-tags me-2"></i>Categories (<?= count($search_results['categories']) ?>)</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                     <tr>
                                                        <th>ID</th>
                                                        <th>Name</th>
                                                        <th>Status</th>
                                                        <th>Actions</th>
                                                     </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($search_results['categories'] as $category): ?>
                                                     <tr>
                                                         <td><?= $category['id'] ?></td>
                                                         <td><?= preg_replace("/($search_query)/i", '<span class="search-highlight">$1</span>', $category['name']) ?></td>
                                                         <td>
                                                            <span class="badge rounded-pill bg-<?= $category['status'] ? 'success' : 'secondary' ?>">
                                                                <?= $category['status'] ? 'Active' : 'Inactive' ?>
                                                            </span>
                                                          </td>
                                                          <td>
                                                            <button class="btn btn-sm btn-warning edit-category-btn" 
                                                                    data-id="<?= $category['id'] ?>"
                                                                    data-name="<?= $category['name'] ?>"
                                                                    data-admin_link="<?= $category['admin_link'] ?? '' ?>"
                                                                    data-user_link="<?= $category['user_link'] ?? '' ?>"
                                                                    data-icon="<?= $category['icon'] ?>"
                                                                    data-status="<?= $category['status'] ?>"
                                                                    data-bs-toggle="modal" 
                                                                    data-bs-target="#editCategoryModal">
                                                                <i class="fas fa-edit me-1"></i> Edit
                                                            </button>
                                                          </td>
                                                      </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                              </table>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <!-- User Results -->
                            <?php if (!empty($search_results['users'])): ?>
                                <div class="card mb-3">
                                    <div class="card-header bg-info text-white">
                                        <h6 class="mb-0"><i class="fas fa-users me-2"></i>Users (<?= count($search_results['users']) ?>)</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                     <tr>
                                                        <th>ID</th>
                                                        <th>Username</th>
                                                        <th>Name</th>
                                                        <th>Email</th>
                                                        <th>Role</th>
                                                        <th>Status</th>
                                                        <th>Registered</th>
                                                     </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($search_results['users'] as $user): ?>
                                                     <tr>
                                                         <td><?= $user['id'] ?></td>
                                                         <td><?= preg_replace("/($search_query)/i", '<span class="search-highlight">$1</span>', $user['username']) ?></td>
                                                         <td><?= $user['first_name'] . ' ' . $user['last_name'] ?></td>
                                                         <td><?= preg_replace("/($search_query)/i", '<span class="search-highlight">$1</span>', $user['email']) ?></td>
                                                         <td>
                                                            <span class="badge bg-<?= $user['role'] === 'admin' ? 'danger' : 'info' ?> role-badge">
                                                                <?= ucfirst($user['role']) ?>
                                                            </span>
                                                          </td>
                                                          <td>
                                                            <span class="badge rounded-pill bg-<?= $user['status'] ? 'success' : 'secondary' ?>">
                                                                <?= $user['status'] ? 'Active' : 'Inactive' ?>
                                                            </span>
                                                          </td>
                                                          <td><?= date('M j, Y', strtotime($user['created_at'])) ?></td>
                                                      </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                              </table>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Announcement Results -->
                            <?php if (!empty($search_results['announcements'])): ?>
                                <div class="card mb-3">
                                    <div class="card-header bg-warning text-dark">
                                        <h6 class="mb-0"><i class="fas fa-bullhorn me-2"></i>News & Events (<?= count($search_results['announcements']) ?>)</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                     <tr>
                                                        <th>Title</th>
                                                        <th>Date</th>
                                                        <th>Actions</th>
                                                     </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($search_results['announcements'] as $announcement): ?>
                                                     <tr>
                                                         <td><?= preg_replace("/($search_query)/i", '<span class="search-highlight">$1</span>', $announcement['title']) ?></td>
                                                         <td><?= date('M d, Y', strtotime($announcement['created_at'])) ?></td>
                                                         <td>
                                                            <button class="btn btn-sm btn-warning edit-announcement-btn" 
                                                                    data-id="<?= $announcement['id'] ?>"
                                                                    data-title="<?= $announcement['title'] ?>"
                                                                    data-description="<?= $announcement['description'] ?>"
                                                                    data-image="<?= $announcement['image_path'] ?>"
                                                                    data-bs-toggle="modal" data-bs-target="#editAnnouncementModal">
                                                                <i class="fas fa-edit me-1"></i> Edit
                                                            </button>
                                                          </td>
                                                      </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                              </table>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Service Results -->
                            <?php if (!empty($search_results['services'])): ?>
                                <div class="card mb-3">
                                    <div class="card-header bg-secondary text-white">
                                        <h6 class="mb-0"><i class="fas fa-concierge-bell me-2"></i>Services (<?= count($search_results['services']) ?>)</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                     <tr>
                                                        <th>Title</th>
                                                        <th>Category</th>
                                                        <th>Location</th>
                                                        <th>Actions</th>
                                                     </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($search_results['services'] as $service): ?>
                                                     <tr>
                                                         <td><?= preg_replace("/($search_query)/i", '<span class="search-highlight">$1</span>', $service['title']) ?></td>
                                                         <td><?= $service['category_name'] ?></td>
                                                         <td><?= preg_replace("/($search_query)/i", '<span class="search-highlight">$1</span>', $service['location']) ?></td>
                                                         <td>
                                                            <a href="dashboard.php?edit=<?= $service['id'] ?>" class="btn btn-sm btn-warning">
                                                                <i class="fas fa-edit me-1"></i> Edit
                                                            </a>
                                                          </td>
                                                      </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                              </table>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>No results found for your search query.
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            <!-- Time Period Selector -->
            <div class="time-period-selector mb-4">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <select class="form-select" name="time_period" onchange="this.form.submit()">
                            <option value="7days" <?= $time_period == '7days' ? 'selected' : '' ?>>Last 7 Days</option>
                            <option value="30days" <?= $time_period == '30days' ? 'selected' : '' ?>>Last 30 Days</option>
                            <option value="90days" <?= $time_period == '90days' ? 'selected' : '' ?>>Last 90 Days</option>
                            <option value="year" <?= $time_period == 'year' ? 'selected' : '' ?>>Last Year</option>
                            <option value="all" <?= $time_period == 'all' ? 'selected' : '' ?>>All Time</option>
                        </select>
                    </div>
                    <div class="col-md-8">
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-outline-primary" onclick="setTimePeriod('7days')">Last 7 Days</button>
                            <button type="button" class="btn btn-outline-primary" onclick="setTimePeriod('30days')">Last 30 Days</button>
                            <button type="button" class="btn btn-outline-primary" onclick="setTimePeriod('90days')">Last 90 Days</button>
                            <button type="button" class="btn btn-outline-primary" onclick="setTimePeriod('year')">This Year</button>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Key Metrics Row -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        Community Posts</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?= number_format($posts_stats['total_posts']) ?>
                                    </div>
                                    <div class="mt-2 text-muted text-sm">
                                        <span class="text-success">
                                            <i class="fas fa-arrow-up"></i>
                                            <?= number_format($posts_stats['posts_today']) ?> today
                                        </span>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-comments fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-info shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                        Active Categories</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?= number_format($stats['active_categories']) ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-list fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-warning shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                        Business Listings</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?= number_format($stats['active_listings']) ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-store fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-purple shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-purple text-uppercase mb-1">
                                        Registered Users</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?= number_format($stats['total_users']) ?>
                                    </div>
                                    <div class="mt-2 text-muted text-sm">
                                        <span class="text-success">
                                            <i class="fas fa-arrow-up"></i>
                                            <?= number_format($stats['new_users_30days'] ?? 0) ?> new (30d)
                                        </span>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-user fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!--<div class="col-xl-3 col-md-6 mb-4">-->
                <!--    <div class="card border-left-orange shadow h-100 py-2">-->
                <!--        <div class="card-body">-->
                <!--            <div class="row no-gutters align-items-center">-->
                <!--                <div class="col mr-2">-->
                <!--                    <div class="text-xs font-weight-bold text-orange text-uppercase mb-1">-->
                <!--                        Posts with Images</div>-->
                <!--                    <div class="h5 mb-0 font-weight-bold text-gray-800">-->
                <!--                        <?= number_format($posts_stats['posts_with_images']) ?>-->
                <!--                    </div>-->
                <!--                    <div class="mt-2 text-muted text-sm">-->
                <!--                        <?= round(($posts_stats['posts_with_images'] / max(1, $posts_stats['total_posts'])) * 100) ?>% of total-->
                <!--                    </div>-->
                <!--                </div>-->
                <!--                <div class="col-auto">-->
                <!--                    <i class="fas fa-image fa-2x text-gray-300"></i>-->
                <!--                </div>-->
                <!--            </div>-->
                <!--        </div>-->
                <!--    </div>-->
                <!--</div>-->
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Total Visits</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?= number_format($stats['total_visitors']) ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        Unique Visitors</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?= number_format($stats['unique_visitors']) ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-users fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!--<div class="col-xl-3 col-md-6 mb-4">-->
                <!--    <div class="card border-left-info shadow h-100 py-2">-->
                <!--        <div class="card-body">-->
                <!--            <div class="row no-gutters align-items-center">-->
                <!--                <div class="col mr-2">-->
                <!--                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">-->
                <!--                        Page Views</div>-->
                <!--                    <div class="h5 mb-0 font-weight-bold text-gray-800">-->
                <!--                        <?= number_format($stats['total_page_views']) ?>-->
                <!--                    </div>-->
                <!--                </div>-->
                <!--                <div class="col-auto">-->
                <!--                    <i class="fas fa-eye fa-2x text-gray-300"></i>-->
                <!--                </div>-->
                <!--            </div>-->
                <!--        </div>-->
                <!--    </div>-->
                <!--</div>-->
            </div>

            <!-- Visitor Analytics Section -->
            <div class="analytics-section" id="visitor-analytics">
                <h3><i class="fas fa-chart-line me-2"></i> Visitor Analytics</h3>
                <div class="row">
                    <div class="col-md-6">
                        <div class="chart-container">
                            <div class="chart-title">Daily Visitor Traffic</div>
                            <canvas id="dailyVisitorsChart"></canvas>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="chart-container">
                            <div class="chart-title">Top Pages Visited</div>
                            <canvas id="topPagesChart"></canvas>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Device & Browser Analytics -->
            <div class="analytics-section">
                <h3><i class="fas fa-mobile-alt me-2"></i> Device & Browser Analytics</h3>
                <div class="row">
                    <div class="col-md-4">
                        <div class="chart-container">
                            <div class="chart-title">Device Distribution</div>
                            <canvas id="deviceUsageChart"></canvas>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="chart-container">
                            <div class="chart-title">Browser Distribution</div>
                            <canvas id="browserUsageChart"></canvas>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="chart-container">
                            <div class="chart-title">Operating Systems</div>
                            <canvas id="osUsageChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- User Analytics Section -->
            <div class="analytics-section" id="analytics">
                <h3><i class="fas fa-users me-2"></i> User Analytics</h3>
                <div class="row">
                    <div class="col-md-6">
                        <div class="chart-container">
                            <div class="chart-title">User Growth Over Time</div>
                            <canvas id="userGrowthChart"></canvas>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="chart-container">
                            <div class="chart-title">User Locations Distribution</div>
                            <canvas id="userLocationsChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-4">
                    <div class="col-md-3">
                        <div class="metric-card card">
                            <div class="card-body">
                                <h5>Total Users</h5>
                                <div class="metric-value"><?= number_format($stats['total_users']) ?></div>
                                <div class="metric-change up">
                                    <i class="fas fa-arrow-up"></i> Registered users
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-card card">
                            <div class="card-body">
                                <h5>New Users (30 days)</h5>
                                <div class="metric-value"><?= isset($stats['registered_users_30days']) ? number_format($stats['registered_users_30days']) : '0' ?></div>
                                <div class="metric-change up">
                                    <i class="fas fa-arrow-up"></i> New registrations
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-card card">
                            <div class="card-body">
                                <h5>Returning Visitors</h5>
                                <div class="metric-value"><?= number_format($stats['returning_visitors'] ?? 0) ?></div>
                                <div class="metric-change up">
                                    <i class="fas fa-arrow-up"></i> Returning users
                                </div>
                            </div>
                        </div>
                    </div>
                    <!--<div class="col-md-3">-->
                    <!--    <div class="metric-card card">-->
                    <!--        <div class="card-body">-->
                    <!--            <h5>Conversion Rate</h5>-->
                    <!--            <div class="metric-value"><?= number_format($stats['conversion_rate'] ?? 0, 2) ?>%</div>-->
                    <!--            <div class="metric-change">-->
                    <!--                Visitor to user conversion-->
                    <!--            </div>-->
                    <!--        </div>-->
                    <!--    </div>-->
                    <!--</div>-->
                </div>
            </div>

            <!-- Search Analytics Section -->
            <div class="analytics-section" id="search-analytics">
                <h3><i class="fas fa-search me-2"></i> Search Analytics</h3>
                <div class="row">
                    <div class="col-md-6">
                        <div class="chart-container">
                            <div class="chart-title">Search Success Rate</div>
                            <canvas id="searchSuccessChart"></canvas>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="chart-container">
                            <div class="chart-title">Top Search Terms</div>
                            <canvas id="searchTermsChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-md-4">
                        <div class="metric-card card">
                            <div class="card-body">
                                <h5>Total Searches</h5>
                                <div class="metric-value"><?= number_format($stats['total_searches']) ?></div>
                                <div class="metric-change up">
                                    <i class="fas fa-arrow-up"></i> All time
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="metric-card card">
                            <div class="card-body">
                                <h5>Successful Searches</h5>
                                <div class="metric-value"><?= number_format($stats['total_searches'] - array_sum(array_column($chartData['failed_searches'], 'count'))) ?></div>
                                <div class="metric-change up">
                                    <i class="fas fa-check-circle"></i> Found results
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="metric-card card">
                            <div class="card-body">
                                <h5>Failed Searches</h5>
                                <div class="metric-value"><?= number_format(array_sum(array_column($chartData['failed_searches'], 'count'))) ?></div>
                                <div class="metric-change down">
                                    <i class="fas fa-exclamation-circle"></i> No results
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Posts Analytics Section -->
            <div class="analytics-section" id="posts-analytics">
                <h3><i class="fas fa-comments me-2"></i> Community Posts Analytics</h3>
                <div class="row">
                    <div class="col-md-6">
                        <div class="chart-container">
                            <div class="chart-title">Posts Growth Over Time</div>
                            <canvas id="postsGrowthChart"></canvas>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="chart-container">
                            <div class="chart-title">Posts with Images Distribution</div>
                            <canvas id="postsStatsChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-4">
                    <div class="col-md-3">
                        <div class="metric-card card">
                            <div class="card-body">
                                <h5>Total Posts</h5>
                                <div class="metric-value"><?= number_format($posts_stats['total_posts']) ?></div>
                                <div class="metric-change up">
                                    <i class="fas fa-arrow-up"></i> All time
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-card card">
                            <div class="card-body">
                                <h5>Posts Today</h5>
                                <div class="metric-value"><?= number_format($posts_stats['posts_today']) ?></div>
                                <div class="metric-change <?= $posts_stats['posts_today'] > 0 ? 'up' : 'down' ?>">
                                    <i class="fas fa-arrow-<?= $posts_stats['posts_today'] > 0 ? 'up' : 'down' ?>"></i> 
                                    <?= $posts_stats['posts_today'] > 0 ? 'Active' : 'No posts' ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-card card">
                            <div class="card-body">
                                <h5>Posts This Week</h5>
                                <div class="metric-value"><?= number_format($posts_stats['posts_this_week']) ?></div>
                                <div class="metric-change up">
                                    <i class="fas fa-arrow-up"></i> Weekly activity
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-card card">
                            <div class="card-body">
                                <h5>Posts with Images</h5>
                                <div class="metric-value"><?= number_format($posts_stats['posts_with_images']) ?></div>
                                <div class="metric-change">
                                    <?= round(($posts_stats['posts_with_images'] / max(1, $posts_stats['total_posts'])) * 100) ?>% of total
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Business Listings Analytics -->
            <div class="analytics-section">
                <h3><i class="fas fa-store me-2"></i> Business Listings Analytics</h3>
                <div class="row">
                    <div class="col-md-12">
                        <div class="chart-container">
                            <div class="chart-title">Most Viewed Listings</div>
                            <canvas id="listingsChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-4">
                    <div class="col-md-4">
                        <div class="metric-card card">
                            <div class="card-body">
                                <h5>Total Listings</h5>
                                <div class="metric-value"><?= number_format($stats['active_listings']) ?></div>
                                <div class="metric-change up">
                                    <i class="fas fa-arrow-up"></i> Active businesses
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="metric-card card">
                            <div class="card-body">
                                <h5>Featured Listings</h5>
                                <div class="metric-value"><?= number_format($stats['featured_listings']) ?></div>
                                <div class="metric-change">
                                    <?= round(($stats['featured_listings'] / max(1, $stats['active_listings'])) * 100) ?>% of total
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="metric-card card">
                            <div class="card-body">
                                <h5>Active Categories</h5>
                                <div class="metric-value"><?= number_format($stats['active_categories']) ?></div>
                                <div class="metric-change">
                                    Business categories
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <!-- Posts Management Section -->
            <section class="mt-5" id="posts-management">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h2>Manage Community Posts</h2>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Image</th>
                                <th>Content</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $all_posts->data_seek(0); // Reset pointer
                            while ($post = $all_posts->fetch_assoc()): ?>
                            <tr>
                                <td><?= $post['id'] ?></td>
                                <td><?= $post['first_name'] . ' ' . $post['last_name'] ?></td>
                                <td>
                                    <?php if (!empty($post['image_path'])): ?>
                                        <img src="<?= $post['image_path'] ?>" class="post-management-image" alt="Post image">
                                    <?php else: ?>
                                        <span class="text-muted">No image</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="max-width: 300px;">
                                        <?= substr($post['description'], 0, 100) ?>
                                        <?= strlen($post['description']) > 100 ? '...' : '' ?>
                                    </div>
                                </td>
                                <td><?= date('M j, Y g:i A', strtotime($post['created_at'])) ?></td>
                                <td>
                                    <a href="dashboard.php?delete=<?= $post['id'] ?>&type=post" 
                                       class="btn btn-sm btn-danger" 
                                       onclick="return confirm('Are you sure you want to delete this post? This action cannot be undone.')">
                                        <i class="fas fa-trash me-1"></i> Delete
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Recent Posts Section -->
            <section class="mt-5" id="recent-posts">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h2>Recent Community Posts</h2>
                </div>

                <div class="row">
                    <?php if (empty($recent_posts)): ?>
                        <div class="col-12">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>No recent posts found.
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($recent_posts as $post): ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card h-100">
                                    <?php if (!empty($post['image_path'])): ?>
                                        <img src="<?= $post['image_path'] ?>" class="card-img-top" alt="Post image" style="height: 200px; object-fit: cover;">
                                    <?php endif; ?>
                                    <div class="card-body">
                                        <h6 class="card-title"><?= $post['first_name'] . ' ' . $post['last_name'] ?></h6>
                                        <p class="card-text"><?= substr($post['description'], 0, 100) ?><?= strlen($post['description']) > 100 ? '...' : '' ?></p>
                                        <small class="text-muted"><?= date('M j, Y g:i A', strtotime($post['created_at'])) ?></small>
                                    </div>
                                    <div class="card-footer">
                                        <a href="dashboard.php?delete=<?= $post['id'] ?>&type=post" 
                                           class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Are you sure you want to delete this post?')">
                                            <i class="fas fa-trash me-1"></i> Delete
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Recent Users Section -->
            <section class="mt-5" id="recent-users">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h2>Manage Users</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                        <i class="fas fa-plus-circle me-1"></i> Add New User
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Registered</th>
                                <th>Last Login</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $users_list->data_seek(0); // Reset pointer
                            while ($row = $users_list->fetch_assoc()): ?>
                            <tr>
                                <td><?= $row['id'] ?></td>
                                <td><?= htmlspecialchars($row['username']) ?></td>
                                <td><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
                                <td><?= htmlspecialchars($row['email']) ?></td>
                                <td>
                                    <span class="badge bg-<?= $row['role'] === 'admin' ? 'danger' : 'info' ?> role-badge">
                                        <?= ucfirst($row['role']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge rounded-pill bg-<?= $row['status'] ? 'success' : 'secondary' ?>">
                                        <?= $row['status'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td><?= date('M j, Y', strtotime($row['created_at'])) ?></td>
                                <td><?= $row['last_login'] ? date('M j, Y g:i A', strtotime($row['last_login'])) : 'Never' ?></td>
                                <td>
                                    <button class="btn btn-sm btn-warning edit-user-btn" 
                                            data-id="<?= $row['id'] ?>"
                                            data-username="<?= htmlspecialchars($row['username']) ?>"
                                            data-email="<?= htmlspecialchars($row['email']) ?>"
                                            data-first_name="<?= htmlspecialchars($row['first_name']) ?>"
                                            data-last_name="<?= htmlspecialchars($row['last_name']) ?>"
                                            data-role="<?= $row['role'] ?>"
                                            data-status="<?= $row['status'] ?>"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editUserModal">
                                        <i class="fas fa-edit me-1"></i> Edit
                                    </button>
                                    <?php if ($row['id'] != ($_SESSION['user_id'] ?? 0)): ?>
                                        <a href="dashboard.php?delete=<?= $row['id'] ?>&type=user" 
                                           class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Are you sure you want to delete this user?')">
                                            <i class="fas fa-trash me-1"></i> Delete
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Business Listings Section -->
            <section class="mt-5" id="business-listings">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h2>Business Listings</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBusinessModal">
                        <i class="fas fa-plus-circle me-1"></i> Add New
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $business_listings->data_seek(0); // Reset pointer
                            while ($business = $business_listings->fetch_assoc()): ?>
                            <tr>
                                <td><?= $business['id'] ?></td>
                                <td>
                                    <?= $business['name'] ?>
                                    <?php if ($business['is_featured']): ?>
                                        <span class="badge featured-badge ms-2">Featured</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge rounded-pill bg-<?= $business['status'] ? 'success' : 'secondary' ?>">
                                        <?= $business['status'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-info view-business-btn" 
                                            data-id="<?= $business['id'] ?>"
                                            data-bs-toggle="modal" data-bs-target="#viewBusinessModal">
                                        <i class="fas fa-eye me-1"></i> View
                                    </button>
                                    <button class="btn btn-sm btn-warning edit-business-btn" 
                                            data-id="<?= $business['id'] ?>" 
                                            data-name="<?= $business['name'] ?>" 
                                            data-icon="<?= $business['icon'] ?>" 
                                            data-filename="<?= $business['filename'] ?>" 
                                            data-status="<?= $business['status'] ?>"
                                            data-featured="<?= $business['is_featured'] ?>"
                                            data-bs-toggle="modal" data-bs-target="#editBusinessModal">
                                        <i class="fas fa-edit me-1"></i> Edit
                                    </button>
                                    <a href="dashboard.php?delete=<?= $business['id'] ?>&type=business" 
                                    class="btn btn-sm btn-danger" 
                                    onclick="return confirm('WARNING: Deleting this business will permanently remove:\n- The business listing\n- All associated images\n\nAre you sure you want to proceed?')">
                                        <i class="fas fa-trash me-1"></i> Delete
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Categories Section -->
            <section class="mt-5" id="categories">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h2>Manage Categories</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                        <i class="fas fa-plus-circle me-1"></i> Add New
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Admin Page</th>
                                <th>User Page</th>
                                <th>Icon</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            // Reset pointer and fetch categories again to ensure fresh data
                            $categories = $conn->query("SELECT * FROM categories ORDER BY name");
                            while ($row = $categories->fetch_assoc()): ?>
                            <tr>
                                <td><?= $row['id'] ?></td>
                                <td><?= $row['name'] ?></td>
                                <td>
                                    <?php if (!empty($row['admin_link'])): ?>
                                        <a href="<?= $row['admin_link'] ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-external-link-alt me-1"></i> View Admin Page
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">Not set</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($row['user_link'])): ?>
                                        <a href="<?= $row['user_link'] ?>" target="_blank" class="btn btn-sm btn-outline-success">
                                            <i class="fas fa-external-link-alt me-1"></i> View User Page
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">Not set</span>
                                    <?php endif; ?>
                                </td>
                                <td><i class="<?= $row['icon'] ?>"></i></td>
                                <td>
                                    <span class="badge rounded-pill bg-<?= $row['status'] ? 'success' : 'secondary' ?>">
                                        <?= $row['status'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-warning edit-category-btn" 
                                            data-id="<?= $row['id'] ?>"
                                            data-name="<?= $row['name'] ?>"
                                            data-admin_link="<?= $row['admin_link'] ?? '' ?>"
                                            data-user_link="<?= $row['user_link'] ?? '' ?>"
                                            data-icon="<?= $row['icon'] ?>"
                                            data-status="<?= $row['status'] ?>"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editCategoryModal">
                                        <i class="fas fa-edit me-1"></i> Edit
                                    </button>
                                    <a href="dashboard.php?delete=<?= $row['id'] ?>&type=category" 
                                        class="btn btn-sm btn-danger" 
                                        onclick="return confirm('WARNING: Deleting this category will remove it from the system.\n\nIf this category has businesses assigned to it, the deletion will fail.\n\nAre you sure you want to proceed?')">
                                        <i class="fas fa-trash me-1"></i> Delete
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Icon Cards Section -->
            <section class="mt-5" id="icon-cards">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h2>Manage Icon Cards</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addIconCardModal">
                        <i class="fas fa-plus-circle me-1"></i> Add New
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Icon</th>
                                <th>Link</th>
                                <th>Order</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $icon_cards->data_seek(0); // Reset pointer
                            while ($row = $icon_cards->fetch_assoc()): ?>
                            <tr>
                                <td><?= $row['id'] ?></td>
                                <td>
                                    <?= $row['title'] ?>
                                    <?php if ($row['is_featured']): ?>
                                        <span class="badge featured-badge ms-2">Featured</span>
                                    <?php endif; ?>
                                </td>
                                <td><i class="<?= $row['icon'] ?> fa-lg"></i></td>
                                <td>
                                    <?php if (!empty($row['link'])): ?>
                                        <a href="<?= $row['link'] ?>" target="_blank" class="btn btn-sm btn-outline-info">
                                            <i class="fas fa-external-link-alt me-1"></i> View Link
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">Not set</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= $row['display_order'] ?></td>
                                <td>
                                    <span class="badge rounded-pill bg-<?= $row['status'] ? 'success' : 'secondary' ?>">
                                        <?= $row['status'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-warning edit-icon-btn" 
                                            data-id="<?= $row['id'] ?>" 
                                            data-title="<?= $row['title'] ?>" 
                                            data-icon="<?= $row['icon'] ?>" 
                                            data-link="<?= $row['link'] ?>"
                                            data-order="<?= $row['display_order'] ?>"
                                            data-status="<?= $row['status'] ?>"
                                            data-featured="<?= $row['is_featured'] ?>"
                                            data-bs-toggle="modal" data-bs-target="#editIconCardModal">
                                        <i class="fas fa-edit me-1"></i> Edit
                                    </button>
                                    <a href="dashboard.php?delete=<?= $row['id'] ?>&type=icon_card" 
                                        class="btn btn-sm btn-danger" 
                                        onclick="return confirm('Are you sure you want to delete this icon card? This will remove it from all pages.')">
                                        <i class="fas fa-trash me-1"></i> Delete
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </section>
            
            
            <!-- Contact Submissions Section -->
            <section class="mt-5" id="contact-submissions">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h2>Contact Submissions</h2>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Type</th>
                                <th>Message</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $contacts->data_seek(0); // Reset pointer
                            while ($contact = $contacts->fetch_assoc()): ?>
                            <tr>
                                <td><?= $contact['id'] ?></td>
                                <td><?= $contact['first_name'] ?> <?= $contact['last_name'] ?></td>
                                <td><?= $contact['email'] ?></td>
                                <td><?= $contact['phone'] ?></td>
                                <td><?= $contact['type'] ?></td>
                                <td><?= substr($contact['message'], 0, 50) ?><?= strlen($contact['message']) > 50 ? '...' : '' ?></td>
                                <td><?= date('M j, Y g:i A', strtotime($contact['created_at'])) ?></td>
                                <td>
                                    <button class="btn btn-sm btn-info view-contact-btn" 
                                            data-id="<?= $contact['id'] ?>"
                                            data-first_name="<?= $contact['first_name'] ?>"
                                            data-last_name="<?= $contact['last_name'] ?>"
                                            data-email="<?= $contact['email'] ?>"
                                            data-phone="<?= $contact['phone'] ?>"
                                            data-type="<?= $contact['type'] ?>"
                                            data-message="<?= htmlspecialchars($contact['message']) ?>"
                                            data-created_at="<?= $contact['created_at'] ?>"
                                            data-bs-toggle="modal" data-bs-target="#viewContactModal">
                                        <i class="fas fa-eye me-1"></i> View
                                    </button>
                                    <a href="mailto:<?= $contact['email'] ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-reply me-1"></i> Reply
                                    </a>
                                    <a href="dashboard.php?delete=<?= $contact['id'] ?>&type=contact" 
                                    class="btn btn-sm btn-danger" 
                                    onclick="return confirm('Are you sure you want to delete this contact submission?')">
                                        <i class="fas fa-trash me-1"></i> Delete
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </section>
            
            <!-- News & Events Section -->
            <section class="mt-5" id="announcements-section">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h2>Manage News & Events</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAnnouncementModal">
                        <i class="fas fa-plus-circle me-1"></i> Add New
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Title</th>
                                <th>Image</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($announcements as $announcement): ?>
                            <tr>
                                <td><?= $announcement['title'] ?></td>
                                <td>
                                    <?php if (!empty($announcement['image_path'])): ?>
                                        <img src="<?= $announcement['image_path'] ?>" class="announcement-image" alt="Announcement Image">
                                    <?php else: ?>
                                        <span style="color: #aaa;">No image</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('M d, Y', strtotime($announcement['created_at'])) ?></td>
                                <td>
                                    <button class="btn btn-sm btn-warning edit-announcement-btn" 
                                            data-id="<?= $announcement['id'] ?>"
                                            data-title="<?= $announcement['title'] ?>"
                                            data-description="<?= $announcement['description'] ?>"
                                            data-image="<?= $announcement['image_path'] ?>"
                                            data-bs-toggle="modal" data-bs-target="#editAnnouncementModal">
                                        <i class="fas fa-edit me-1"></i> Edit
                                    </button>
                                    <a href="dashboard.php?delete=<?= $announcement['id'] ?>&type=announcement" 
                                       class="btn btn-sm btn-danger" 
                                       onclick="return confirm('Are you sure you want to delete this announcement?')">
                                        <i class="fas fa-trash me-1"></i> Delete
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Add New User</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="user_action" value="add_user">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="addUserFirstname" class="form-label">First Name*</label>
                                <input type="text" class="form-control" id="addUserFirstname" name="first_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="addUserLastname" class="form-label">Last Name*</label>
                                <input type="text" class="form-control" id="addUserLastname" name="last_name" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="addUserUsername" class="form-label">Username*</label>
                            <input type="text" class="form-control" id="addUserUsername" name="username" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="addUserEmail" class="form-label">Email*</label>
                            <input type="email" class="form-control" id="addUserEmail" name="email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="addUserPassword" class="form-label">Password*</label>
                            <input type="password" class="form-control" id="addUserPassword" name="password" required>
                            <small class="text-muted">Password will be hashed before storing</small>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="addUserRole" class="form-label">Role*</label>
                                <select class="form-select" id="addUserRole" name="role" required>
                                    <option value="user">User</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="form-check mt-4">
                                    <input class="form-check-input" type="checkbox" id="addUserStatus" name="status" checked>
                                    <label class="form-check-label" for="addUserStatus">
                                        Active
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="user_action" value="update_user">
                        <input type="hidden" name="id" id="editUserId">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="editUserFirstname" class="form-label">First Name*</label>
                                <input type="text" class="form-control" id="editUserFirstname" name="first_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editUserLastname" class="form-label">Last Name*</label>
                                <input type="text" class="form-control" id="editUserLastname" name="last_name" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editUserUsername" class="form-label">Username*</label>
                            <input type="text" class="form-control" id="editUserUsername" name="username" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editUserEmail" class="form-label">Email*</label>
                            <input type="email" class="form-control" id="editUserEmail" name="email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editUserPassword" class="form-label">New Password (leave blank to keep current)</label>
                            <input type="password" class="form-control" id="editUserPassword" name="password">
                            <small class="text-muted">Password will be hashed before storing</small>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="editUserRole" class="form-label">Role*</label>
                                <select class="form-select" id="editUserRole" name="role" required>
                                    <option value="user">User</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="form-check mt-4">
                                    <input class="form-check-input" type="checkbox" id="editUserStatus" name="status">
                                    <label class="form-check-label" for="editUserStatus">
                                        Active
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning">Update User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Category Modal -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Add New Category</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="category_action" value="add_category">
                        
                        <div class="mb-3">
                            <label for="categoryName" class="form-label">Category Name*</label>
                            <input type="text" class="form-control" id="categoryName" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="categoryAdminLink" class="form-label">Admin Page Link</label>
                            <input type="text" class="form-control" id="categoryAdminLink" name="admin_link">
                            <small class="text-muted">URL for admin interface (optional)</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="categoryUserLink" class="form-label">User Page Link</label>
                            <input type="text" class="form-control" id="categoryUserLink" name="user_link">
                            <small class="text-muted">URL for public interface (optional)</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="categoryIcon" class="form-label">Icon*</label>
                            <div class="input-group">
                                <span class="input-group-text"><i id="categoryIconPreview" class="fas fa-tag"></i></span>
                                <input type="text" class="form-control" id="categoryIcon" name="icon" value="fas fa-tag" required>
                            </div>
                            <small class="text-muted">Font Awesome icon class (e.g. fas fa-tag, fas fa-store)</small>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input class="form-check-input" type="checkbox" id="categoryStatus" name="status" checked>
                            <label class="form-check-label" for="categoryStatus">
                                Active
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Category Modal -->
    <div class="modal fade" id="editCategoryModal" tabindex="-1" aria-labelledby="editCategoryModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title">Edit Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="category_action" value="update_category">
                        <input type="hidden" name="id" id="editCategoryId">
                        
                        <div class="mb-3">
                            <label for="editCategoryName" class="form-label">Category Name*</label>
                            <input type="text" class="form-control" id="editCategoryName" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editCategoryAdminLink" class="form-label">Admin Page Link</label>
                            <input type="text" class="form-control" id="editCategoryAdminLink" name="admin_link">
                            <small class="text-muted">URL for admin interface (optional)</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editCategoryUserLink" class="form-label">User Page Link</label>
                            <input type="text" class="form-control" id="editCategoryUserLink" name="user_link">
                            <small class="text-muted">URL for public interface (optional)</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editCategoryIcon" class="form-label">Icon*</label>
                            <div class="input-group">
                                <span class="input-group-text"><i id="editCategoryIconPreview" class="fas fa-tag"></i></span>
                                <input type="text" class="form-control" id="editCategoryIcon" name="icon" required>
                            </div>
                            <small class="text-muted">Font Awesome icon class (e.g. fas fa-tag, fas fa-store)</small>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input class="form-check-input" type="checkbox" id="editCategoryStatus" name="status">
                            <label class="form-check-label" for="editCategoryStatus">
                                Active
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning">Update Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Icon Card Modal -->
    <div class="modal fade" id="addIconCardModal" tabindex="-1" aria-labelledby="addIconCardModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="addIconCardModalLabel">Add New Icon Card</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="icon_action" value="add_icon_card">
                        
                        <div class="mb-3">
                            <label for="iconCardTitle" class="form-label">Title*</label>
                            <input type="text" class="form-control" id="iconCardTitle" name="title" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="iconCardIcon" class="form-label">Icon*</label>
                            <div class="input-group">
                                <span class="input-group-text"><i id="iconPreview" class="fas fa-question"></i></span>
                                <select class="form-select" id="iconCardIcon" name="icon" required>
                                    <option value="fas fa-home">Home</option>
                                    <option value="fas fa-info">Info</option>
                                    <option value="fas fa-map-marker-alt">Location</option>
                                    <option value="fas fa-phone">Phone</option>
                                    <option value="fas fa-envelope">Email</option>
                                    <option value="fas fa-calendar-alt">Calendar</option>
                                    <option value="fas fa-clock">Clock</option>
                                    <option value="fas fa-users">Users</option>
                                    <option value="fas fa-building">Building</option>
                                    <option value="fas fa-utensils">Restaurant</option>
                                    <option value="fas fa-hotel">Hotel</option>
                                    <option value="fas fa-shopping-cart">Shopping</option>
                                    <option value="fas fa-landmark">Landmark</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="iconCardLink" class="form-label">Link*</label>
                            <input type="text" class="form-control" id="iconCardLink" name="link" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="iconCardOrder" class="form-label">Display Order</label>
                                <input type="number" class="form-control" id="iconCardOrder" name="display_order" value="0" min="0">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Options</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="iconCardFeatured" name="is_featured">
                                    <label class="form-check-label" for="iconCardFeatured">
                                        Featured
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="iconCardStatus" name="status" checked>
                                    <label class="form-check-label" for="iconCardStatus">
                                        Active
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Icon Card</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Icon Card Modal -->
    <div class="modal fade" id="editIconCardModal" tabindex="-1" aria-labelledby="editIconCardModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="editIconCardModalLabel">Edit Icon Card</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="icon_action" value="update_icon_card">
                        <input type="hidden" name="id" id="editIconCardId">
                        
                        <div class="mb-3">
                            <label for="editIconCardTitle" class="form-label">Title*</label>
                            <input type="text" class="form-control" id="editIconCardTitle" name="title" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editIconCardIcon" class="form-label">Icon*</label>
                            <div class="input-group">
                                <span class="input-group-text"><i id="editIconPreview" class="fas fa-question"></i></span>
                                <select class="form-select" id="editIconCardIcon" name="icon" required>
                                    <option value="fas fa-home">Home</option>
                                    <option value="fas fa-info">Info</option>
                                    <option value="fas fa-map-marker-alt">Location</option>
                                    <option value="fas fa-phone">Phone</option>
                                    <option value="fas fa-envelope">Email</option>
                                    <option value="fas fa-calendar-alt">Calendar</option>
                                    <option value="fas fa-clock">Clock</option>
                                    <option value="fas fa-users">Users</option>
                                    <option value="fas fa-building">Building</option>
                                    <option value="fas fa-utensils">Restaurant</option>
                                    <option value="fas fa-hotel">Hotel</option>
                                    <option value="fas fa-shopping-cart">Shopping</option>
                                    <option value="fas fa-landmark">Landmark</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editIconCardLink" class="form-label">Link*</label>
                            <input type="text" class="form-control" id="editIconCardLink" name="link" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="editIconCardOrder" class="form-label">Display Order</label>
                                <input type="number" class="form-control" id="editIconCardOrder" name="display_order" value="0" min="0">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Options</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="editIconCardFeatured" name="is_featured">
                                    <label class="form-check-label" for="editIconCardFeatured">
                                        Featured
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="editIconCardStatus" name="status">
                                    <label class="form-check-label" for="editIconCardStatus">
                                        Active
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning">Update Icon Card</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Business Modal -->
    <div class="modal fade" id="addBusinessModal" tabindex="-1" aria-labelledby="addBusinessModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Add Business Listing</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" name="business_action" value="add_business">
                    
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="businessName" class="form-label">Business Name*</label>
                                <input type="text" class="form-control" id="businessName" name="name" required>
                            </div>
                        </div>
                    
                        <div class="mb-3">
                            <label for="businessIcon" class="form-label">Icon</label>
                            <input type="text" class="form-control" id="businessIcon" name="icon" value="fa-store">
                            <small class="text-muted">Font Awesome icon class (e.g. fa-store, fa-restaurant)</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="businessFilename" class="form-label">Custom Filename (optional)</label>
                            <input type="text" class="form-control" id="businessFilename" name="filename" placeholder="custom-page.php">
                            <small class="text-muted">Leave blank for default</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="businessImages" class="form-label">Images</label>
                            <input type="file" class="form-control" id="businessImages" name="images[]" multiple accept="image/*">
                            <small class="text-muted">Upload multiple images (JPEG, PNG)</small>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="businessFeatured" name="is_featured">
                                    <label class="form-check-label" for="businessFeatured">
                                        Featured Business
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="businessStatus" name="status" checked>
                                    <label class="form-check-label" for="businessStatus">
                                        Active
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Business</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Business Modal -->
    <div class="modal fade" id="editBusinessModal" tabindex="-1" aria-labelledby="editBusinessModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title">Edit Business Listing</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" name="business_action" value="update_business">
                    <input type="hidden" name="id" id="editBusinessId">
                    
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="editBusinessName" class="form-label">Business Name*</label>
                                <input type="text" class="form-control" id="editBusinessName" name="name" required>
                            </div>
                        </div>
                    
                        <div class="mb-3">
                            <label for="editBusinessIcon" class="form-label">Icon</label>
                            <input type="text" class="form-control" id="editBusinessIcon" name="icon" value="fa-store">
                            <small class="text-muted">Font Awesome icon class (e.g. fa-store, fa-restaurant)</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editBusinessFilename" class="form-label">Custom Filename (optional)</label>
                            <input type="text" class="form-control" id="editBusinessFilename" name="filename" placeholder="custom-page.php">
                            <small class="text-muted">Leave blank for default</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editBusinessImages" class="form-label">Add More Images</label>
                            <input type="file" class="form-control" id="editBusinessImages" name="images[]" multiple accept="image/*">
                            <small class="text-muted">Upload additional images (JPEG, PNG)</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Existing Images</label>
                            <div id="existingBusinessImages" class="d-flex flex-wrap">
                                <!-- Images will be loaded here via JavaScript -->
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="editBusinessFeatured" name="is_featured">
                                    <label class="form-check-label" for="editBusinessFeatured">
                                        Featured Business
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="editBusinessStatus" name="status">
                                    <label class="form-check-label" for="editBusinessStatus">
                                        Active
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning">Update Business</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Business Modal -->
    <div class="modal fade" id="viewBusinessModal" tabindex="-1" aria-labelledby="viewBusinessModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">Business Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="viewBusinessContent">
                    <!-- Content will be loaded via JavaScript -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Announcement Modal -->
    <div class="modal fade" id="addAnnouncementModal" tabindex="-1" aria-labelledby="addAnnouncementModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Add New News & Events</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Title*</label>
                            <input type="text" name="title" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description*</label>
                            <textarea name="description" class="form-control" rows="3" required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Image (optional)</label>
                            <input type="file" name="image" class="form-control" accept="image/*">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_announcement" class="btn btn-primary">Add News & Events</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Announcement Modal -->
    <div class="modal fade" id="editAnnouncementModal" tabindex="-1" aria-labelledby="editAnnouncementModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title">Edit News & Events</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" name="id" id="editAnnouncementId">
                    <input type="hidden" name="existing_image" id="editAnnouncementImage">
                    
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Title*</label>
                            <input type="text" name="title" class="form-control" id="editAnnouncementTitle" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description*</label>
                            <textarea name="description" class="form-control" id="editAnnouncementDescription" rows="3" required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Current Image</label>
                            <div id="editAnnouncementImagePreview">
                                <!-- Image will be loaded here -->
                            </div>
                            <label class="form-label mt-2">Change Image (optional)</label>
                            <input type="file" name="image" class="form-control" accept="image/*">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_announcement" class="btn btn-warning">Update News & Events</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Contact Modal -->
    <div class="modal fade" id="viewContactModal" tabindex="-1" aria-labelledby="viewContactModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">Contact Submission Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="fw-bold">Name:</label>
                        <p id="viewContactName" class="mt-1"></p>
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold">Email:</label>
                        <p id="viewContactEmail" class="mt-1"></p>
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold">Phone:</label>
                        <p id="viewContactPhone" class="mt-1"></p>
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold">Type:</label>
                        <p id="viewContactType" class="mt-1"></p>
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold">Message:</label>
                        <div id="viewContactMessage" class="mt-1 p-3 bg-light rounded" style="white-space: pre-wrap;"></div>
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold">Date Submitted:</label>
                        <p id="viewContactDate" class="mt-1"></p>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" id="viewContactReplyLink" class="btn btn-primary me-auto">
                        <i class="fas fa-reply me-1"></i> Reply via Email
                    </a>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Select2 -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
    // Initialize all modals and handlers
    document.addEventListener('DOMContentLoaded', function() {
        // Icon preview for add modal
        const categoryIconInput = document.getElementById('categoryIcon');
        if (categoryIconInput) {
            categoryIconInput.addEventListener('input', function() {
                document.getElementById('categoryIconPreview').className = this.value;
            });
        }

        // Icon preview for edit modal
        const editCategoryIconInput = document.getElementById('editCategoryIcon');
        if (editCategoryIconInput) {
            editCategoryIconInput.addEventListener('input', function() {
                document.getElementById('editCategoryIconPreview').className = this.value;
            });
        }

        // Edit category button handlers
        const editCategoryBtns = document.querySelectorAll('.edit-category-btn');
        editCategoryBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                document.getElementById('editCategoryId').value = this.dataset.id;
                document.getElementById('editCategoryName').value = this.dataset.name;
                document.getElementById('editCategoryAdminLink').value = this.dataset.admin_link;
                document.getElementById('editCategoryUserLink').value = this.dataset.user_link;
                document.getElementById('editCategoryIcon').value = this.dataset.icon;
                
                // Update icon preview
                document.getElementById('editCategoryIconPreview').className = this.dataset.icon;
                
                // Set checkbox status
                document.getElementById('editCategoryStatus').checked = this.dataset.status === '1';
            });
        });

        // Edit user button handlers
        const editUserBtns = document.querySelectorAll('.edit-user-btn');
        editUserBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                document.getElementById('editUserId').value = this.dataset.id;
                document.getElementById('editUserFirstname').value = this.dataset.first_name;
                document.getElementById('editUserLastname').value = this.dataset.last_name;
                document.getElementById('editUserUsername').value = this.dataset.username;
                document.getElementById('editUserEmail').value = this.dataset.email;
                document.getElementById('editUserRole').value = this.dataset.role;
                document.getElementById('editUserStatus').checked = this.dataset.status === '1';
                // Clear password field
                document.getElementById('editUserPassword').value = '';
            });
        });

        // Edit business modal handler
        const editBusinessBtns = document.querySelectorAll('.edit-business-btn');
        editBusinessBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const businessId = this.dataset.id;
                document.getElementById('editBusinessId').value = businessId;
                document.getElementById('editBusinessName').value = this.dataset.name;
                document.getElementById('editBusinessIcon').value = this.dataset.icon;
                document.getElementById('editBusinessFilename').value = this.dataset.filename;
                document.getElementById('editBusinessFeatured').checked = this.dataset.featured === '1';
                document.getElementById('editBusinessStatus').checked = this.dataset.status === '1';
                
                // Load existing images via AJAX
                loadBusinessImages(businessId);
            });
        });

        // Edit icon card modal handler
        const editIconBtns = document.querySelectorAll('.edit-icon-btn');
        editIconBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                document.getElementById('editIconCardId').value = this.dataset.id;
                document.getElementById('editIconCardTitle').value = this.dataset.title;
                document.getElementById('editIconCardIcon').value = this.dataset.icon;
                document.getElementById('editIconCardLink').value = this.dataset.link;
                document.getElementById('editIconCardOrder').value = this.dataset.order;
                document.getElementById('editIconCardStatus').checked = this.dataset.status === '1';
                document.getElementById('editIconCardFeatured').checked = this.dataset.featured === '1';
                
                // Update preview
                document.getElementById('editIconPreview').className = this.dataset.icon;
            });
        });

        // Edit announcement modal handler
        const editAnnouncementBtns = document.querySelectorAll('.edit-announcement-btn');
        editAnnouncementBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                document.getElementById('editAnnouncementId').value = this.dataset.id;
                document.getElementById('editAnnouncementTitle').value = this.dataset.title;
                document.getElementById('editAnnouncementDescription').value = this.dataset.description;
                document.getElementById('editAnnouncementImage').value = this.dataset.image;
                
                const imagePreview = document.getElementById('editAnnouncementImagePreview');
                if (this.dataset.image) {
                    imagePreview.innerHTML = `<img src="${this.dataset.image}" class="announcement-image" alt="Announcement Image">`;
                } else {
                    imagePreview.innerHTML = '<p><i>No image uploaded</i></p>';
                }
            });
        });

        // View business modal handler
        const viewBusinessBtns = document.querySelectorAll('.view-business-btn');
        viewBusinessBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const businessId = this.dataset.id;
                
                fetch('get_business_details.php?id=' + businessId)
                    .then(response => response.json())
                    .then(business => {
                        const modalContent = document.getElementById('viewBusinessContent');
                        if (!modalContent) return;
                        
                        let html = `
                            <div class="row">
                                <div class="col-md-8">
                                    <h4>${business.name}</h4>
                                    <p class="text-muted">${business.category_name}</p>
                                    <p><strong>Address:</strong> ${business.address}</p>
                                    ${business.contact ? `<p><strong>Contact:</strong> ${business.contact}</p>` : ''}
                                    ${business.website ? `<p><strong>Website:</strong> <a href="${business.website}" target="_blank">${business.website}</a></p>` : ''}
                                    ${business.opening_hours ? `<p><strong>Opening Hours:</strong> ${business.opening_hours}</p>` : ''}
                                    <p><strong>Description:</strong></p>
                                    <p>${business.description}</p>
                                </div>
                                <div class="col-md-4">
                                    <div class="card">
                                        <div class="card-body">
                                            <h5 class="card-title">Details</h5>
                                            <p><strong>Status:</strong> 
                                                <span class="badge rounded-pill bg-${business.status ? 'success' : 'secondary'}">
                                                    ${business.status ? 'Active' : 'Inactive'}
                                                </span>
                                            </p>
                                            ${business.is_featured ? `<span class="badge featured-badge">Featured</span>` : ''}
                                            <p><strong>Icon:</strong> <i class="${business.icon}"></i> ${business.icon}</p>
                                            ${business.filename ? `<p><strong>Custom Page:</strong> ${business.filename}</p>` : ''}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                        
                        if (business.images && business.images.length > 0) {
                            html += `<div class="mt-3">
                                <h5>Images</h5>
                                <div class="d-flex flex-wrap">`;
                            
                            business.images.forEach(image => {
                                html += `<img src="${image.image_path}" class="image-thumbnail me-2 mb-2" alt="Business Image">`;
                            });
                            
                            html += `</div></div>`;
                        }
                        
                        modalContent.innerHTML = html;
                    });
            });
        });

        // View contact modal handler
        const viewContactBtns = document.querySelectorAll('.view-contact-btn');
        viewContactBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                document.getElementById('viewContactName').innerHTML = `${this.dataset.first_name} ${this.dataset.last_name}`;
                document.getElementById('viewContactEmail').innerHTML = this.dataset.email;
                document.getElementById('viewContactPhone').innerHTML = this.dataset.phone || '<em>Not provided</em>';
                document.getElementById('viewContactType').innerHTML = this.dataset.type;
                document.getElementById('viewContactMessage').innerHTML = this.dataset.message;
                document.getElementById('viewContactDate').innerHTML = new Date(this.dataset.created_at).toLocaleString();
                
                // Set the reply link
                const replyLink = document.getElementById('viewContactReplyLink');
                if (replyLink) {
                    replyLink.href = `mailto:${this.dataset.email}?subject=Regarding your contact submission`;
                }
            });
        });

        // Initialize Select2
        $('.select2').select2({
            theme: 'bootstrap-5'
        });
        
        // Time period selector buttons
        window.setTimePeriod = function(period) {
            window.location.href = 'dashboard.php?time_period=' + period;
        }
        
        // Initialize all charts
        initializeCharts();
    });

    // Load business images via AJAX
    function loadBusinessImages(businessId) {
        fetch('get_business_images.php?id=' + businessId)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(images => {
                const container = document.getElementById('existingBusinessImages');
                if (!container) return;
                
                container.innerHTML = '';
                
                if (!images || images.length === 0) {
                    container.innerHTML = '<p class="text-muted">No images uploaded yet.</p>';
                    return;
                }
                
                images.forEach(image => {
                    const imgDiv = document.createElement('div');
                    imgDiv.className = 'position-relative';
                    imgDiv.style.display = 'inline-block';
                    imgDiv.style.marginRight = '10px';
                    imgDiv.style.marginBottom = '10px';
                    
                    const img = document.createElement('img');
                    img.src = image.image_path;
                    img.className = 'image-thumbnail';
                    img.alt = 'Business Image';
                    
                    const deleteBtn = document.createElement('a');
                    deleteBtn.href = `dashboard.php?delete=${image.id}&type=business_image&business_id=${businessId}`;
                    deleteBtn.className = 'btn btn-danger btn-sm position-absolute top-0 end-0';
                    deleteBtn.style.transform = 'translate(50%, -50%)';
                    deleteBtn.innerHTML = '<i class="fas fa-times"></i>';
                    deleteBtn.onclick = function(e) {
                        e.preventDefault();
                        if (confirm('Are you sure you want to delete this image?')) {
                            window.location.href = this.href;
                        }
                    };
                    
                    imgDiv.appendChild(img);
                    imgDiv.appendChild(deleteBtn);
                    container.appendChild(imgDiv);
                });
            })
            .catch(error => {
                console.error('Error loading images:', error);
                const container = document.getElementById('existingBusinessImages');
                if (container) {
                    container.innerHTML = '<p class="text-danger">Error loading images</p>';
                }
            });
    }

       document.addEventListener('DOMContentLoaded', function() {
        initializeAllCharts();
    });

    function initializeAllCharts() {
        // Daily Visitors Chart
        const dailyVisitorsData = <?= json_encode($chartData['visitors_daily'] ?? []) ?>;
        if (dailyVisitorsData.length > 0 && document.getElementById('dailyVisitorsChart')) {
            new Chart(document.getElementById('dailyVisitorsChart'), {
                type: 'line',
                data: {
                    labels: dailyVisitorsData.map(item => item.visit_date),
                    datasets: [
                        {
                            label: 'Total Visits',
                            data: dailyVisitorsData.map(item => parseInt(item.total_visits)),
                            borderColor: '#4e73df',
                            backgroundColor: 'rgba(78, 115, 223, 0.05)',
                            borderWidth: 2,
                            pointBackgroundColor: '#4e73df',
                            pointRadius: 3,
                            pointHoverRadius: 5,
                            tension: 0.3,
                            fill: true
                        },
                        {
                            label: 'Unique Visitors',
                            data: dailyVisitorsData.map(item => parseInt(item.unique_visitors)),
                            borderColor: '#1cc88a',
                            backgroundColor: 'rgba(28, 200, 138, 0.05)',
                            borderWidth: 2,
                            pointBackgroundColor: '#1cc88a',
                            pointRadius: 3,
                            pointHoverRadius: 5,
                            tension: 0.3,
                            fill: true
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
        }

        // Hourly Visits Chart
        const hourlyData = <?= json_encode($chartData['hourly_visits'] ?? []) ?>;
        if (hourlyData.length > 0 && document.getElementById('hourlyVisitsChart')) {
            const hours = hourlyData.map(item => `${item.hour}:00`);
            new Chart(document.getElementById('hourlyVisitsChart'), {
                type: 'bar',
                data: {
                    labels: hours,
                    datasets: [{
                        label: 'Visits',
                        data: hourlyData.map(item => parseInt(item.visits)),
                        backgroundColor: '#36b9cc',
                        borderColor: '#36b9cc',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
        }

        // Top Pages Chart
// Top Pages Chart
const topPagesData = <?= json_encode($chartData['top_pages'] ?? []) ?>;
if (topPagesData.length > 0 && document.getElementById('topPagesChart')) {
    new Chart(document.getElementById('topPagesChart'), {
        type: 'bar',
        data: {
            labels: topPagesData.map(item => item.page_visited), // Now shows friendly names
            datasets: [{
                label: 'Page Views',
                data: topPagesData.map(item => parseInt(item.visits)),
                backgroundColor: '#f6c23e',
                borderColor: '#f6c23e',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            indexAxis: 'y',
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        title: function(context) {
                            const data = topPagesData[context[0].dataIndex];
                            return data.page_visited;
                        },
                        label: function(context) {
                            const visits = context.raw;
                            return `Visits: ${visits} times`;
                        }
                    }
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    },
                    title: {
                        display: true,
                        text: 'Number of Visits'
                    }
                }
            }
        }
    });
}

        // Device Usage Chart
        const deviceData = <?= json_encode($chartData['device_usage'] ?? []) ?>;
        if (deviceData.length > 0 && document.getElementById('deviceUsageChart')) {
            new Chart(document.getElementById('deviceUsageChart'), {
                type: 'pie',
                data: {
                    labels: deviceData.map(item => item.device_type),
                    datasets: [{
                        data: deviceData.map(item => parseInt(item.count)),
                        backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b'],
                        hoverBackgroundColor: ['#2e59d9', '#17a673', '#2c9faf', '#dda20a', '#be2617']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }

        // Browser Usage Chart
        const browserData = <?= json_encode($chartData['browser_usage'] ?? []) ?>;
        if (browserData.length > 0 && document.getElementById('browserUsageChart')) {
            new Chart(document.getElementById('browserUsageChart'), {
                type: 'doughnut',
                data: {
                    labels: browserData.map(item => item.browser),
                    datasets: [{
                        data: browserData.map(item => parseInt(item.count)),
                        backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b'],
                        hoverBackgroundColor: ['#2e59d9', '#17a673', '#2c9faf', '#dda20a', '#be2617']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    },
                    cutout: '50%'
                }
            });
        }

        // OS Usage Chart
        const osData = <?= json_encode($chartData['os_usage'] ?? []) ?>;
        if (osData.length > 0 && document.getElementById('osUsageChart')) {
            new Chart(document.getElementById('osUsageChart'), {
                type: 'pie',
                data: {
                    labels: osData.map(item => item.os),
                    datasets: [{
                        data: osData.map(item => parseInt(item.count)),
                        backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b'],
                        hoverBackgroundColor: ['#2e59d9', '#17a673', '#2c9faf', '#dda20a', '#be2617']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }

        // User Growth Chart
        const userGrowthData = <?= json_encode($chartData['user_growth'] ?? []) ?>;
        if (userGrowthData.length > 0 && document.getElementById('userGrowthChart')) {
            new Chart(document.getElementById('userGrowthChart'), {
                type: 'line',
                data: {
                    labels: userGrowthData.map(item => item.month),
                    datasets: [{
                        label: 'New Users',
                        data: userGrowthData.map(item => parseInt(item.count)),
                        borderColor: '#4e73df',
                        backgroundColor: 'rgba(78, 115, 223, 0.05)',
                        borderWidth: 2,
                        pointBackgroundColor: '#4e73df',
                        pointRadius: 3,
                        pointHoverRadius: 5,
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
        }

        // User Locations Chart
        const userLocationsData = <?= json_encode($chartData['user_locations'] ?? []) ?>;
        if (userLocationsData.length > 0 && document.getElementById('userLocationsChart')) {
            new Chart(document.getElementById('userLocationsChart'), {
                type: 'bar',
                data: {
                    labels: userLocationsData.map(item => item.location),
                    datasets: [{
                        label: 'Users',
                        data: userLocationsData.map(item => parseInt(item.count)),
                        backgroundColor: '#1cc88a',
                        borderColor: '#1cc88a',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
        }

        // Search Success Chart
        const successCount = <?= $stats['total_searches'] - array_sum(array_column($chartData['failed_searches'], 'count')) ?>;
        const failCount = <?= array_sum(array_column($chartData['failed_searches'], 'count')) ?>;
        if ((successCount > 0 || failCount > 0) && document.getElementById('searchSuccessChart')) {
            new Chart(document.getElementById('searchSuccessChart'), {
                type: 'doughnut',
                data: {
                    labels: ['Successful Searches', 'Failed Searches'],
                    datasets: [{
                        data: [successCount, failCount],
                        backgroundColor: ['#1cc88a', '#e74a3b'],
                        hoverBackgroundColor: ['#17a673', '#be2617']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    },
                    cutout: '60%'
                }
            });
        }

        // Search Terms Chart
        const searchTermsData = <?= json_encode($chartData['searches'] ?? []) ?>;
        if (searchTermsData.length > 0 && document.getElementById('searchTermsChart')) {
            const sortedData = searchTermsData.sort((a, b) => b.count - a.count).slice(0, 10);
            new Chart(document.getElementById('searchTermsChart'), {
                type: 'bar',
                data: {
                    labels: sortedData.map(item => item.query.length > 20 ? item.query.substring(0, 17) + '...' : item.query),
                    datasets: [{
                        label: 'Search Count',
                        data: sortedData.map(item => parseInt(item.count)),
                        backgroundColor: '#4e73df',
                        borderColor: '#4e73df',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    indexAxis: 'y',
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
        }

        // Posts Growth Chart
        const postsGrowthData = <?= json_encode($chartData['posts_growth'] ?? []) ?>;
        if (postsGrowthData.length > 0 && document.getElementById('postsGrowthChart')) {
            new Chart(document.getElementById('postsGrowthChart'), {
                type: 'line',
                data: {
                    labels: postsGrowthData.map(item => item.month),
                    datasets: [{
                        label: 'New Posts',
                        data: postsGrowthData.map(item => parseInt(item.count)),
                        borderColor: '#6c757d',
                        backgroundColor: 'rgba(108, 117, 125, 0.05)',
                        borderWidth: 2,
                        pointBackgroundColor: '#6c757d',
                        pointRadius: 3,
                        pointHoverRadius: 5,
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
        }

        // Posts Stats Chart
        const postsWithImages = <?= $posts_stats['posts_with_images'] ?>;
        const postsWithoutImages = <?= $posts_stats['total_posts'] - $posts_stats['posts_with_images'] ?>;
        if ((postsWithImages > 0 || postsWithoutImages > 0) && document.getElementById('postsStatsChart')) {
            new Chart(document.getElementById('postsStatsChart'), {
                type: 'doughnut',
                data: {
                    labels: ['Posts with Images', 'Posts without Images'],
                    datasets: [{
                        data: [postsWithImages, postsWithoutImages],
                        backgroundColor: ['#4e73df', '#6c757d'],
                        hoverBackgroundColor: ['#2e59d9', '#545b62']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    },
                    cutout: '70%'
                }
            });
        }

        // Listings Chart
        const listingsData = <?= json_encode($chartData['listings'] ?? []) ?>;
        if (listingsData.length > 0 && document.getElementById('listingsChart')) {
            new Chart(document.getElementById('listingsChart'), {
                type: 'bar',
                data: {
                    labels: listingsData.map(item => item.name.length > 20 ? item.name.substring(0, 17) + '...' : item.name),
                    datasets: [{
                        label: 'Views',
                        data: listingsData.map(item => parseInt(item.views)),
                        backgroundColor: '#1cc88a',
                        borderColor: '#1cc88a',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
        }
    }

    // Time period selector function
    function setTimePeriod(period) {
        window.location.href = 'dashboard.php?time_period=' + period;
    }

    // Initialize Select2
    $(document).ready(function() {
        $('.select2').select2({
            theme: 'bootstrap-5'
        });
    });

    // Handle all delete confirmations
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('a[href*="delete="]').forEach(link => {
            link.addEventListener('click', function(e) {
                const url = new URL(this.href);
                const type = url.searchParams.get('type');
                let message = 'Are you sure you want to delete this item?';
                
                const messages = {
                    'user': 'Are you sure you want to delete this user? This action cannot be undone.',
                    'business': 'WARNING: Deleting this business will permanently remove:\n- The business listing\n- All associated images\n\nAre you sure you want to proceed?',
                    'category': 'WARNING: Deleting this category will remove it from the system.\n\nIf this category has businesses assigned to it, the deletion will fail.\n\nAre you sure you want to proceed?',
                    'post': 'Are you sure you want to delete this post? This action cannot be undone.'
                };
                
                if (messages[type]) {
                    message = messages[type];
                }
                
                if (!confirm(message)) {
                    e.preventDefault();
                }
            });
        });
    });
    </script>
</body>
</html>