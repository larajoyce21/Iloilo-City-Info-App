<?php
require 'conn.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
    
// Check authentication and admin role
// if (!isset($_SESSION['admin_logged_in']) || $_SESSION['role'] !== 'admin') {
//     header("Location: login.php");
//     exit();
// }

// Initialize variables
$error = '';
$success = '';
$current_page = 1;
$items_per_page = 20;

// Handle messages from redirects
if (isset($_GET['msg'])) {
    $success = htmlspecialchars($_GET['msg'], ENT_QUOTES, 'UTF-8');
}

// Get current page from URL
if (isset($_GET['page']) && is_numeric($_GET['page'])) {
    $current_page = max(1, min((int)$_GET['page'], 1000));
}

// Get filter parameters
$user_filter = isset($_GET['user']) ? intval($_GET['user']) : 0;
$action_filter = isset($_GET['action']) ? trim($_GET['action']) : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build base query
$query = "SELECT a.*, u.username 
         FROM activity_log a 
         LEFT JOIN users u ON a.user_id = u.id";
$count_query = "SELECT COUNT(*) as total FROM activity_log a";
$where_clauses = [];
$params = [];
$types = '';

// Add filters
if ($user_filter > 0) {
    $where_clauses[] = "a.user_id = ?";
    $params[] = $user_filter;
    $types .= 'i';
}

if (!empty($action_filter)) {
    $where_clauses[] = "a.action LIKE ?";
    $params[] = "%$action_filter%";
    $types .= 's';
}

if (!empty($date_from)) {
    $where_clauses[] = "a.created_at >= ?";
    $params[] = $date_from . ' 00:00:00';
    $types .= 's';
}

if (!empty($date_to)) {
    $where_clauses[] = "a.created_at <= ?";
    $params[] = $date_to . ' 23:59:59';
    $types .= 's';
}

// Combine where clauses
if (!empty($where_clauses)) {
    $where = " WHERE " . implode(" AND ", $where_clauses);
    $query .= $where;
    $count_query .= $where;
}

// Get total logs for pagination
$stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$total_result = $stmt->get_result();
$total_logs = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_logs / $items_per_page);

// Add sorting and pagination
$offset = ($current_page - 1) * $items_per_page;
$query .= " ORDER BY a.created_at DESC LIMIT ?, ?";
$params[] = $offset;
$params[] = $items_per_page;
$types .= 'ii';

// Execute query
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$logs_result = $stmt->get_result();

// Get all users for filter dropdown
$users = [];
$result = $conn->query("SELECT id, username FROM users ORDER BY username");
$users = $result->fetch_all(MYSQLI_ASSOC);

$conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Logs - Iloilo City Info App</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <?= $error ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <?= $success ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Activity Logs</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="exportLogs">
                                <i class="bi bi-download me-1"></i> Export
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="clearLogs">
                                <i class="bi bi-trash me-1"></i> Clear Logs
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label for="userFilter" class="form-label">User</label>
                                <select id="userFilter" name="user" class="form-select">
                                    <option value="0">All Users</option>
                                    <?php foreach ($users as $user): ?>
                                    <option value="<?= $user['id'] ?>" <?= $user_filter == $user['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="actionFilter" class="form-label">Action Contains</label>
                                <input type="text" class="form-control" id="actionFilter" name="action" 
                                       value="<?= htmlspecialchars($action_filter, ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="dateFrom" class="form-label">Date From</label>
                                <input type="date" class="form-control" id="dateFrom" name="date_from" 
                                       value="<?= htmlspecialchars($date_from, ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="dateTo" class="form-label">Date To</label>
                                <input type="date" class="form-control" id="dateTo" name="date_to" 
                                       value="<?= htmlspecialchars($date_to, ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-funnel me-1"></i> Filter
                                </button>
                                <a href="logs.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-x-lg me-1"></i> Clear
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Logs Table -->
                <div class="card">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Date & Time</th>
                                        <th>User</th>
                                        <th>Action</th>
                                        <th>IP Address</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($logs_result && $logs_result->num_rows > 0): ?>
                                        <?php while ($log = $logs_result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= date('M d, Y H:i:s', strtotime($log['created_at'])) ?></td>
                                            <td><?= htmlspecialchars($log['username'] ?? 'System', ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($log['action'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($log['ip_address'], ENT_QUOTES, 'UTF-8') ?></td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-5">
                                                <i class="bi bi-list-check display-5 text-muted mb-3"></i>
                                                <h5 class="text-muted">No activity logs found</h5>
                                                <p class="text-muted">Try adjusting your filters</p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                        <div class="p-3 border-top">
                            <nav aria-label="Page navigation">
                                <ul class="pagination justify-content-center mb-0">
                                    <?php if ($current_page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $current_page - 1 ?><?= $user_filter > 0 ? '&user='.$user_filter : '' ?><?= !empty($action_filter) ? '&action='.urlencode($action_filter) : '' ?><?= !empty($date_from) ? '&date_from='.$date_from : '' ?><?= !empty($date_to) ? '&date_to='.$date_to : '' ?>" aria-label="Previous">
                                            <span aria-hidden="true">&laquo;</span>
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?= $i == $current_page ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?><?= $user_filter > 0 ? '&user='.$user_filter : '' ?><?= !empty($action_filter) ? '&action='.urlencode($action_filter) : '' ?><?= !empty($date_from) ? '&date_from='.$date_from : '' ?><?= !empty($date_to) ? '&date_to='.$date_to : '' ?>"><?= $i ?></a>
                                    </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($current_page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $current_page + 1 ?><?= $user_filter > 0 ? '&user='.$user_filter : '' ?><?= !empty($action_filter) ? '&action='.urlencode($action_filter) : '' ?><?= !empty($date_from) ? '&date_from='.$date_from : '' ?><?= !empty($date_to) ? '&date_to='.$date_to : '' ?>" aria-label="Next">
                                            <span aria-hidden="true">&raquo;</span>
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                            <div class="text-center text-muted mt-2">
                                Showing <?= ($current_page - 1) * $items_per_page + 1 ?> to <?= min($current_page * $items_per_page, $total_logs) ?> of <?= number_format($total_logs) ?> logs
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Export logs
        document.getElementById('exportLogs').addEventListener('click', function() {
            // In a real implementation, this would generate a CSV file
            alert('Export functionality would be implemented here');
        });
        
        // Clear logs confirmation
        document.getElementById('clearLogs').addEventListener('click', function() {
            Swal.fire({
                title: 'Are you sure?',
                text: "This will permanently delete all activity logs!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, clear logs!'
            }).then((result) => {
                if (result.isConfirmed) {
                    // In a real implementation, this would clear the logs
                    alert('Log clearing functionality would be implemented here');
                }
            });
        });
    });
    </script>
</body>
</html>