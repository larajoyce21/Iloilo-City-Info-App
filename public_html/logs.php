<?php
require 'conn.php';
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Initialize variables
$error = '';
$success = '';
$report_type = isset($_GET['report']) ? $_GET['report'] : 'user_activity';
$date_range = isset($_GET['date_range']) ? $_GET['date_range'] : 'last_7_days';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Validate report type
$valid_reports = ['user_activity', 'user_registration', 'content_activity', 'website_analytics'];
if (!in_array($report_type, $valid_reports)) {
    $report_type = 'user_activity';
    $error = 'Invalid report type selected.';
}

// Validate date inputs for custom range
if ($date_range === 'custom') {
    if (empty($start_date) || empty($end_date)) {
        $error = 'Please provide both start and end dates for custom range.';
        $date_range = 'last_7_days'; // fallback
    } elseif (strtotime($start_date) > strtotime($end_date)) {
        $error = 'Start date cannot be later than end date.';
        $date_range = 'last_7_days'; // fallback
    } elseif (!strtotime($start_date) || !strtotime($end_date)) {
        $error = 'Invalid date format provided.';
        $date_range = 'last_7_days'; // fallback
    }
}

// Calculate date ranges
$date_ranges = [
    'last_7_days' => [
        'start' => date('Y-m-d', strtotime('-7 days')),
        'end' => date('Y-m-d')
    ],
    'last_30_days' => [
        'start' => date('Y-m-d', strtotime('-30 days')),
        'end' => date('Y-m-d')
    ],
    'this_month' => [
        'start' => date('Y-m-01'),
        'end' => date('Y-m-d')
    ],
    'last_month' => [
        'start' => date('Y-m-01', strtotime('-1 month')),
        'end' => date('Y-m-t', strtotime('-1 month'))
    ],
    'this_year' => [
        'start' => date('Y-01-01'),
        'end' => date('Y-m-d')
    ],
    'custom' => [
        'start' => $start_date,
        'end' => $end_date
    ]
];

$current_range = $date_ranges[$date_range];

// Prepare WHERE clause
$where_start = $current_range['start'] . ' 00:00:00';
$where_end = $current_range['end'] . ' 23:59:59';

// Generate reports data
$report_data = [];
$chart_labels = [];
$chart_data = [];
$title = '';
$description = '';

try {
    switch ($report_type) {
        case 'user_activity':
            $title = "User Activity Report";
            $description = "Shows user activities in the system";
            
            // Check if activity_log table exists, if not create it
            $table_check = $conn->query("SHOW TABLES LIKE 'activity_log'");
            if ($table_check->num_rows == 0) {
                $conn->query("CREATE TABLE IF NOT EXISTS activity_log (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT,
                    action VARCHAR(255) NOT NULL,
                    ip_address VARCHAR(45) NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX (created_at)
                )");
                
                // Insert sample data
                $sample_actions = ['login', 'logout', 'view_page', 'create_content', 'update_profile'];
                for ($i = 0; $i < 10; $i++) {
                    $action = $sample_actions[array_rand($sample_actions)];
                    $ip = '192.168.1.' . rand(1, 254);
                    $date = date('Y-m-d H:i:s', strtotime('-' . rand(0, 7) . ' days'));
                    $user_id = rand(1, 5);
                    $conn->query("INSERT INTO activity_log (user_id, action, ip_address, created_at) 
                                 VALUES ($user_id, '$action', '$ip', '$date')");
                }
            }
            
            // Get activity data
            $result = $conn->query("SELECT a.*, COALESCE(u.username, 'System') as username 
                                  FROM activity_log a 
                                  LEFT JOIN users u ON a.user_id = u.id 
                                  WHERE a.created_at BETWEEN '$where_start' AND '$where_end'
                                  ORDER BY a.created_at DESC LIMIT 100");
            $report_data = $result->fetch_all(MYSQLI_ASSOC);
            
            // Get chart data
            $chart_result = $conn->query("SELECT DATE(created_at) as date, COUNT(*) as count 
                                         FROM activity_log 
                                         WHERE created_at BETWEEN '$where_start' AND '$where_end'
                                         GROUP BY DATE(created_at) 
                                         ORDER BY date");
            while ($row = $chart_result->fetch_assoc()) {
                $chart_labels[] = $row['date'];
                $chart_data[] = (int)$row['count'];
            }
            break;
            
        case 'user_registration':
            $title = "User Registration Report";
            $description = "Shows new user registrations";
            
            // Get registration data
            $result = $conn->query("SELECT id, username, email, role, status, created_at FROM users 
                                   WHERE created_at BETWEEN '$where_start' AND '$where_end'
                                   ORDER BY created_at DESC LIMIT 100");
            $report_data = $result->fetch_all(MYSQLI_ASSOC);
            
            // Get chart data
            $chart_result = $conn->query("SELECT DATE(created_at) as date, COUNT(*) as count 
                                         FROM users 
                                         WHERE created_at BETWEEN '$where_start' AND '$where_end'
                                         GROUP BY DATE(created_at) 
                                         ORDER BY date");
            while ($row = $chart_result->fetch_assoc()) {
                $chart_labels[] = $row['date'];
                $chart_data[] = (int)$row['count'];
            }
            break;
            
        case 'content_activity':
            $title = "Content Activity Report";
            $description = "Shows content creation and updates";
            
            // Check if content table exists
            $table_check = $conn->query("SHOW TABLES LIKE 'content'");
            if ($table_check->num_rows == 0) {
                $conn->query("CREATE TABLE IF NOT EXISTS content (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    title VARCHAR(255) NOT NULL,
                    author_id INT NOT NULL,
                    status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX (created_at)
                )");
                
                // Insert sample data
                $sample_titles = ['Welcome to Iloilo', 'City Updates', 'Tourism Guide', 'Local Events', 'News Update'];
                for ($i = 0; $i < 8; $i++) {
                    $title_sample = $sample_titles[array_rand($sample_titles)] . ' ' . ($i + 1);
                    $status = ['draft', 'published', 'archived'][array_rand(['draft', 'published', 'archived'])];
                    $date = date('Y-m-d H:i:s', strtotime('-' . rand(0, 30) . ' days'));
                    $author_id = rand(1, 5);
                    $conn->query("INSERT INTO content (title, author_id, status, created_at) 
                                 VALUES ('$title_sample', $author_id, '$status', '$date')");
                }
            }
            
            // Get content data
            $result = $conn->query("SELECT c.*, COALESCE(u.username, 'Unknown') as author_name 
                                  FROM content c 
                                  LEFT JOIN users u ON c.author_id = u.id 
                                  WHERE c.created_at BETWEEN '$where_start' AND '$where_end'
                                  ORDER BY c.created_at DESC LIMIT 100");
            $report_data = $result->fetch_all(MYSQLI_ASSOC);
            
            // Get chart data
            $chart_result = $conn->query("SELECT DATE(created_at) as date, COUNT(*) as count 
                                         FROM content 
                                         WHERE created_at BETWEEN '$where_start' AND '$where_end'
                                         GROUP BY DATE(created_at) 
                                         ORDER BY date");
            while ($row = $chart_result->fetch_assoc()) {
                $chart_labels[] = $row['date'];
                $chart_data[] = (int)$row['count'];
            }
            break;
            
        case 'website_analytics':
            $title = "Website Analytics Report";
            $description = "Shows website visitor statistics";
            
            // Check if analytics table exists
            $table_check = $conn->query("SHOW TABLES LIKE 'analytics'");
            if ($table_check->num_rows == 0) {
                $conn->query("CREATE TABLE IF NOT EXISTS analytics (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    page_url VARCHAR(255) NOT NULL,
                    visitor_ip VARCHAR(45) NOT NULL,
                    referrer VARCHAR(255),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX (created_at)
                )");
                
                // Insert sample data
                $sample_pages = ['/index.php', '/about.php', '/services.php', '/contact.php', '/reports.php'];
                $sample_referrers = ['https://google.com', 'https://facebook.com', '', 'https://twitter.com'];
                for ($i = 0; $i < 15; $i++) {
                    $page = $sample_pages[array_rand($sample_pages)];
                    $ip = '192.168.1.' . rand(1, 254);
                    $referrer = $sample_referrers[array_rand($sample_referrers)];
                    $date = date('Y-m-d H:i:s', strtotime('-' . rand(0, 7) . ' days'));
                    $conn->query("INSERT INTO analytics (page_url, visitor_ip, referrer, created_at) 
                                 VALUES ('$page', '$ip', '$referrer', '$date')");
                }
            }
            
            // Get analytics data
            $result = $conn->query("SELECT * FROM analytics 
                                  WHERE created_at BETWEEN '$where_start' AND '$where_end'
                                  ORDER BY created_at DESC LIMIT 100");
            $report_data = $result->fetch_all(MYSQLI_ASSOC);
            
            // Get chart data
            $chart_result = $conn->query("SELECT DATE(created_at) as date, COUNT(*) as count 
                                         FROM analytics 
                                         WHERE created_at BETWEEN '$where_start' AND '$where_end'
                                         GROUP BY DATE(created_at) 
                                         ORDER BY date");
            while ($row = $chart_result->fetch_assoc()) {
                $chart_labels[] = $row['date'];
                $chart_data[] = (int)$row['count'];
            }
            break;
    }
} catch (Exception $e) {
    $error = 'Error generating report: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Iloilo City Info App</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .report-card {
            transition: all 0.3s;
        }
        .report-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .chart-container {
            position: relative;
            height: 300px;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            .container-fluid {
                max-width: 100% !important;
            }
        }
        .sidebar {
            min-height: 100vh;
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Iloilo City Info</a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text">Admin Panel</span>
            </div>
        </div>
    </nav>
    
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="bi bi-house"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="reports.php">
                                <i class="bi bi-graph-up"></i> Reports
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="users.php">
                                <i class="bi bi-people"></i> Users
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Reports & Analytics</h1>
                    <div class="btn-toolbar mb-2 mb-md-0 no-print">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="exportReport">
                                <i class="bi bi-download me-1"></i> Export
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="printReport">
                                <i class="bi bi-printer me-1"></i> Print
                            </button>
                        </div>
                    </div>
                </div>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <?= $error ?>
                    </div>
                <?php endif; ?>
                
                <!-- Report Filters -->
                <div class="card mb-4 no-print">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label for="reportType" class="form-label">Report Type</label>
                                <select id="reportType" name="report" class="form-select" required>
                                    <option value="user_activity" <?= $report_type === 'user_activity' ? 'selected' : '' ?>>User Activity</option>
                                    <option value="user_registration" <?= $report_type === 'user_registration' ? 'selected' : '' ?>>User Registration</option>
                                    <option value="content_activity" <?= $report_type === 'content_activity' ? 'selected' : '' ?>>Content Activity</option>
                                    <option value="website_analytics" <?= $report_type === 'website_analytics' ? 'selected' : '' ?>>Website Analytics</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="dateRange" class="form-label">Date Range</label>
                                <select id="dateRange" name="date_range" class="form-select" required>
                                    <option value="last_7_days" <?= $date_range === 'last_7_days' ? 'selected' : '' ?>>Last 7 Days</option>
                                    <option value="last_30_days" <?= $date_range === 'last_30_days' ? 'selected' : '' ?>>Last 30 Days</option>
                                    <option value="this_month" <?= $date_range === 'this_month' ? 'selected' : '' ?>>This Month</option>
                                    <option value="last_month" <?= $date_range === 'last_month' ? 'selected' : '' ?>>Last Month</option>
                                    <option value="this_year" <?= $date_range === 'this_year' ? 'selected' : '' ?>>This Year</option>
                                    <option value="custom" <?= $date_range === 'custom' ? 'selected' : '' ?>>Custom Range</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <div id="customDateRange" class="<?= $date_range !== 'custom' ? 'd-none' : '' ?>">
                                    <label class="form-label">Custom Date Range</label>
                                    <div class="input-group">
                                        <input type="date" class="form-control" name="start_date" value="<?= $start_date ?>" max="<?= date('Y-m-d') ?>">
                                        <span class="input-group-text">to</span>
                                        <input type="date" class="form-control" name="end_date" value="<?= $end_date ?>" max="<?= date('Y-m-d') ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-funnel me-1"></i> Generate Report
                                </button>
                                <a href="reports.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-x-lg me-1"></i> Reset
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Report Summary -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card report-card h-100">
                            <div class="card-body">
                                <h6 class="card-title">Report Summary</h6>
                                <div class="d-flex align-items-center mb-2">
                                    <i class="bi bi-file-earmark-text fs-3 me-3 text-primary"></i>
                                    <div>
                                        <h5 class="mb-0"><?= $title ?></h5>
                                        <small class="text-muted"><?= $description ?></small>
                                    </div>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="mb-1">Date Range</h6>
                                        <p class="mb-0"><?= date('M d, Y', strtotime($current_range['start'])) ?> to <?= date('M d, Y', strtotime($current_range['end'])) ?></p>
                                    </div>
                                    <div class="text-end">
                                        <h6 class="mb-1">Total Records</h6>
                                        <p class="mb-0"><?= count($report_data) ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="card report-card h-100">
                            <div class="card-body">
                                <h6 class="card-title">Trend Analysis</h6>
                                <div class="chart-container">
                                    <canvas id="reportChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Report Data -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Report Data</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <?php if ($report_type === 'user_activity'): ?>
                                            <th>Date</th>
                                            <th>User</th>
                                            <th>Action</th>
                                            <th>IP Address</th>
                                        <?php elseif ($report_type === 'user_registration'): ?>
                                            <th>Date</th>
                                            <th>Username</th>
                                            <th>Email</th>
                                            <th>Role</th>
                                            <th>Status</th>
                                        <?php elseif ($report_type === 'content_activity'): ?>
                                            <th>Date</th>
                                            <th>Title</th>
                                            <th>Author</th>
                                            <th>Status</th>
                                        <?php elseif ($report_type === 'website_analytics'): ?>
                                            <th>Date</th>
                                            <th>Page URL</th>
                                            <th>IP Address</th>
                                            <th>Referrer</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($report_data)): ?>
                                        <?php foreach ($report_data as $row): ?>
                                        <tr>
                                            <?php if ($report_type === 'user_activity'): ?>
                                                <td><?= date('M d, Y H:i', strtotime($row['created_at'])) ?></td>
                                                <td><?= $row['username'] ?? 'System' ?></td>
                                                <td><?= $row['action'] ?? '' ?></td>
                                                <td><?= $row['ip_address'] ?? '' ?></td>
                                            <?php elseif ($report_type === 'user_registration'): ?>
                                                <td><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                                                <td><?= $row['username'] ?? '' ?></td>
                                                <td><?= $row['email'] ?? '' ?></td>
                                                <td><?= ucfirst($row['role'] ?? '') ?></td>
                                                <td>
                                                    <span class="badge bg-<?= ($row['status'] ?? '') === 'active' ? 'success' : 'danger' ?>">
                                                        <?= ucfirst($row['status'] ?? 'active') ?>
                                                    </span>
                                                </td>
                                            <?php elseif ($report_type === 'content_activity'): ?>
                                                <td><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                                                <td><?= $row['title'] ?? '' ?></td>
                                                <td><?= $row['author_name'] ?? '' ?></td>
                                                <td>
                                                    <span class="badge bg-<?= 
                                                        ($row['status'] ?? '') === 'published' ? 'success' : 
                                                        (($row['status'] ?? '') === 'draft' ? 'warning text-dark' : 'secondary')
                                                    ?>">
                                                        <?= ucfirst($row['status'] ?? '') ?>
                                                    </span>
                                                </td>
                                            <?php elseif ($report_type === 'website_analytics'): ?>
                                                <td><?= date('M d, Y H:i', strtotime($row['created_at'])) ?></td>
                                                <td><?= $row['page_url'] ?? '' ?></td>
                                                <td><?= $row['visitor_ip'] ?? '' ?></td>
                                                <td><?= !empty($row['referrer']) ? $row['referrer'] : 'Direct' ?></td>
                                            <?php endif; ?>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="<?= $report_type === 'user_registration' ? '5' : '4' ?>" class="text-center py-5">
                                                <i class="bi bi-file-earmark-excel display-5 text-muted mb-3"></i>
                                                <h5 class="text-muted">No data found for this report</h5>
                                                <p class="text-muted">Try adjusting your filters or date range</p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Toggle custom date range
        const dateRangeSelect = document.getElementById('dateRange');
        const customDateRange = document.getElementById('customDateRange');
        
        dateRangeSelect.addEventListener('change', function() {
            if (this.value === 'custom') {
                customDateRange.classList.remove('d-none');
                const dateInputs = customDateRange.querySelectorAll('input[type="date"]');
                dateInputs.forEach(input => input.required = true);
            } else {
                customDateRange.classList.add('d-none');
                const dateInputs = customDateRange.querySelectorAll('input[type="date"]');
                dateInputs.forEach(input => input.required = false);
            }
        });
        
        // Initialize chart
        const ctx = document.getElementById('reportChart').getContext('2d');
        const chartLabels = <?= json_encode($chart_labels) ?>;
        const chartData = <?= json_encode($chart_data) ?>;
        
        const reportChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartLabels,
                datasets: [{
                    label: <?= json_encode($title) ?>,
                    data: chartData,
                    backgroundColor: 'rgba(0, 123, 255, 0.2)',
                    borderColor: 'rgba(0, 123, 255, 1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0,
                            callback: function(value) {
                                return Number.isInteger(value) ? value : '';
                            }
                        }
                    },
                    x: {
                        ticks: {
                            maxTicksLimit: 10
                        }
                    }
                },
                elements: {
                    point: {
                        radius: 3,
                        hoverRadius: 6
                    }
                }
            }
        });
        
        // Print report
        document.getElementById('printReport').addEventListener('click', function() {
            window.print();
        });
        
        // Export report
        document.getElementById('exportReport').addEventListener('click', function() {
            const table = document.querySelector('.table');
            const reportTitle = <?= json_encode($title) ?>;
            const dateRange = '<?= date('M d, Y', strtotime($current_range['start'])) ?> to <?= date('M d, Y', strtotime($current_range['end'])) ?>';
            
            let csvContent = `${reportTitle}\n`;
            csvContent += `Date Range: ${dateRange}\n`;
            csvContent += `Generated: ${new Date().toLocaleDateString()}\n\n`;
            
            const headers = Array.from(table.querySelectorAll('th')).map(th => th.textContent.trim());
            csvContent += headers.join(',') + '\n';
            
            const rows = table.querySelectorAll('tbody tr');
            rows.forEach(row => {
                const cells = Array.from(row.querySelectorAll('td')).map(td => {
                    let text = td.textContent.trim().replace(/\n\s+/g, ' ');
                    return text.includes(',') ? `"${text}"` : text;
                });
                if (cells.length > 1) {
                    csvContent += cells.join(',') + '\n';
                }
            });
            
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', `${reportTitle.replace(/\s+/g, '_')}_${new Date().toISOString().split('T')[0]}.csv`);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        });
        
        // Form validation
        const form = document.querySelector('form');
        form.addEventListener('submit', function(e) {
            const dateRange = document.getElementById('dateRange').value;
            if (dateRange === 'custom') {
                const startDate = document.querySelector('input[name="start_date"]').value;
                const endDate = document.querySelector('input[name="end_date"]').value;
                
                if (!startDate || !endDate) {
                    e.preventDefault();
                    alert('Please select both start and end dates for custom range.');
                    return false;
                }
                
                if (new Date(startDate) > new Date(endDate)) {
                    e.preventDefault();
                    alert('Start date cannot be later than end date.');
                    return false;
                }
            }
        });
    });
    </script>
</body>
</html>