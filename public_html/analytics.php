<?php
// Start session and check authentication
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit();
}

// Database connection
require 'conn.php';

// Set default date range (last 30 days)
$end_date = date('Y-m-d');
$start_date = date('Y-m-d', strtotime('-30 days'));

// Get date range from URL if provided
if (isset($_GET['start_date']) && isset($_GET['end_date'])) {
    $start_date = $_GET['start_date'];
    $end_date = $_GET['end_date'];
}

// Validate dates
if (!strtotime($start_date) || !strtotime($end_date)) {
    $start_date = date('Y-m-d', strtotime('-30 days'));
    $end_date = date('Y-m-d');
}

// Ensure start date is before end date
if (strtotime($start_date) > strtotime($end_date)) {
    $temp = $start_date;
    $start_date = $end_date;
    $end_date = $temp;
}

// Get visitor statistics
$visitor_stats = [
    'total_visitors' => $conn->query("SELECT COUNT(*) FROM visitor_logs WHERE visit_date BETWEEN '$start_date' AND '$end_date 23:59:59'")->fetch_row()[0],
    'unique_visitors' => $conn->query("SELECT COUNT(DISTINCT ip_address) FROM visitor_logs WHERE visit_date BETWEEN '$start_date' AND '$end_date 23:59:59'")->fetch_row()[0],
    'page_views' => $conn->query("SELECT SUM(page_views) FROM visitor_logs WHERE visit_date BETWEEN '$start_date' AND '$end_date 23:59:59'")->fetch_row()[0],
    'avg_session' => $conn->query("SELECT SEC_TO_TIME(AVG(TIME_TO_SEC(session_duration))) FROM visitor_logs WHERE visit_date BETWEEN '$start_date' AND '$end_date 23:59:59'")->fetch_row()[0],
];

// Get traffic sources
$traffic_sources = $conn->query("
    SELECT 
        CASE 
            WHEN referrer IS NULL THEN 'Direct'
            WHEN referrer LIKE '%google.%' THEN 'Google'
            WHEN referrer LIKE '%bing.%' THEN 'Bing'
            WHEN referrer LIKE '%yahoo.%' THEN 'Yahoo'
            WHEN referrer LIKE '%facebook.%' THEN 'Facebook'
            WHEN referrer LIKE '%twitter.%' THEN 'Twitter'
            WHEN referrer LIKE '%instagram.%' THEN 'Instagram'
            ELSE 'Other Referral'
        END as source,
        COUNT(*) as count,
        COUNT(DISTINCT ip_address) as unique_count
    FROM visitor_logs
    WHERE visit_date BETWEEN '$start_date' AND '$end_date 23:59:59'
    GROUP BY source
    ORDER BY count DESC
")->fetch_all(MYSQLI_ASSOC);

// Get popular pages
$popular_pages = $conn->query("
    SELECT page_url, COUNT(*) as views, COUNT(DISTINCT ip_address) as unique_views
    FROM page_views
    WHERE view_date BETWEEN '$start_date' AND '$end_date 23:59:59'
    GROUP BY page_url
    ORDER BY views DESC
    LIMIT 10
")->fetch_all(MYSQLI_ASSOC);

// Get visitor trends (daily)
$visitor_trends = $conn->query("
    SELECT 
        DATE(visit_date) as date,
        COUNT(*) as visits,
        COUNT(DISTINCT ip_address) as unique_visits
    FROM visitor_logs
    WHERE visit_date BETWEEN '$start_date' AND '$end_date 23:59:59'
    GROUP BY DATE(visit_date)
    ORDER BY date ASC
")->fetch_all(MYSQLI_ASSOC);

// Prepare data for charts
$dates = [];
$visits_data = [];
$unique_visits_data = [];

foreach ($visitor_trends as $trend) {
    $dates[] = date('M j', strtotime($trend['date']));
    $visits_data[] = $trend['visits'];
    $unique_visits_data[] = $trend['unique_visits'];
}

$source_labels = [];
$source_data = [];
$unique_source_data = [];

foreach ($traffic_sources as $source) {
    $source_labels[] = $source['source'];
    $source_data[] = $source['count'];
    $unique_source_data[] = $source['unique_count'];
}

$page_labels = [];
$page_views_data = [];
$unique_page_views_data = [];

foreach ($popular_pages as $page) {
    $page_labels[] = substr($page['page_url'], 0, 30) . (strlen($page['page_url']) > 30 ? '...' : '');
    $page_views_data[] = $page['views'];
    $unique_page_views_data[] = $page['unique_views'];
}

$conn->close();
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Analytics - Iloilo City Info App</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .stat-card {
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            color: white;
            text-align: center;
        }
        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 20px;
        }
        .date-range-picker {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .traffic-source {
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 5px;
            background-color: #f8f9fa;
        }
        .source-name {
            font-weight: bold;
        }
        .source-stats {
            font-size: 0.9rem;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include 'sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Analytics Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="exportData">
                                <i class="bi bi-download"></i> Export
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Date Range Picker -->
                <div class="date-range-picker">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label for="startDate" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="startDate" name="start_date" value="<?= $start_date ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="endDate" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="endDate" name="end_date" value="<?= $end_date ?>">
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-filter"></i> Apply Filter
                            </button>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <a href="analytics.php" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-counterclockwise"></i> Reset
                            </a>
                        </div>
                    </form>
                </div>
                
                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stat-card" style="background-color: #800000;">
                            <i class="bi bi-people-fill fa-2x"></i>
                            <h5>Total Visitors</h5>
                            <h3><?= number_format($visitor_stats['total_visitors']) ?></h3>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card" style="background-color: #0d6efd;">
                            <i class="bi bi-person-badge-fill fa-2x"></i>
                            <h5>Unique Visitors</h5>
                            <h3><?= number_format($visitor_stats['unique_visitors']) ?></h3>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card" style="background-color: #198754;">
                            <i class="bi bi-file-earmark-text-fill fa-2x"></i>
                            <h5>Page Views</h5>
                            <h3><?= number_format($visitor_stats['page_views']) ?></h3>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card" style="background-color: #6f42c1;">
                            <i class="bi bi-clock-fill fa-2x"></i>
                            <h5>Avg. Session</h5>
                            <h3><?= $visitor_stats['avg_session'] ?: '0:00' ?></h3>
                        </div>
                    </div>
                </div>
                
                <!-- Visitor Trends Chart -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Visitor Trends</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="visitorTrendsChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <!-- Traffic Sources -->
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5>Traffic Sources</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="trafficSourcesChart"></canvas>
                                </div>
                                
                                <div class="mt-4">
                                    <?php foreach ($traffic_sources as $source): ?>
                                    <div class="traffic-source">
                                        <div class="d-flex justify-content-between">
                                            <span class="source-name"><?= $source['source'] ?></span>
                                            <span><?= number_format($source['count']) ?> visits</span>
                                        </div>
                                        <div class="source-stats">
                                            <?= number_format($source['unique_count']) ?> unique visitors
                                            (<?= round(($source['unique_count'] / max(1, $visitor_stats['unique_visitors'])) * 100) ?>%)
                                        </div>
                                        <div class="progress mt-1" style="height: 5px;">
                                            <div class="progress-bar" role="progressbar" 
                                                 style="width: <?= round(($source['count'] / max(1, $visitor_stats['total_visitors'])) * 100) ?>%" 
                                                 aria-valuenow="<?= round(($source['count'] / max(1, $visitor_stats['total_visitors'])) * 100) ?>" 
                                                 aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Popular Pages -->
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5>Popular Pages</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="popularPagesChart"></canvas>
                                </div>
                                
                                <div class="table-responsive mt-4">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Page</th>
                                                <th>Views</th>
                                                <th>Unique</th>
                                                <th>% of Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($popular_pages as $page): ?>
                                            <tr>
                                                <td><?= htmlspecialchars(substr($page['page_url'], 0, 50)) ?><?= strlen($page['page_url']) > 50 ? '...' : '' ?></td>
                                                <td><?= number_format($page['views']) ?></td>
                                                <td><?= number_format($page['unique_views']) ?></td>
                                                <td><?= round(($page['views'] / max(1, $visitor_stats['page_views'])) * 100) ?>%</td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Device Breakdown -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Device Breakdown</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="chart-container">
                                    <canvas id="deviceTypeChart"></canvas>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="chart-container">
                                    <canvas id="browserChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Visitor Trends Chart
        const trendsCtx = document.getElementById('visitorTrendsChart').getContext('2d');
        new Chart(trendsCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode($dates) ?>,
                datasets: [
                    {
                        label: 'Total Visits',
                        data: <?= json_encode($visits_data) ?>,
                        borderColor: '#800000',
                        backgroundColor: 'rgba(128, 0, 0, 0.1)',
                        tension: 0.3,
                        fill: true
                    },
                    {
                        label: 'Unique Visits',
                        data: <?= json_encode($unique_visits_data) ?>,
                        borderColor: '#0d6efd',
                        backgroundColor: 'rgba(13, 110, 253, 0.1)',
                        tension: 0.3,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Daily Visitor Trends'
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
        
        // Traffic Sources Chart
        const sourcesCtx = document.getElementById('trafficSourcesChart').getContext('2d');
        new Chart(sourcesCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($source_labels) ?>,
                datasets: [{
                    data: <?= json_encode($source_data) ?>,
                    backgroundColor: [
                        '#800000', '#0d6efd', '#198754', '#fd7e14', '#6f42c1',
                        '#20c997', '#d63384', '#6610f2', '#6c757d', '#0dcaf0'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                    },
                    title: {
                        display: true,
                        text: 'Traffic Sources'
                    }
                }
            }
        });
        
        // Popular Pages Chart
        const pagesCtx = document.getElementById('popularPagesChart').getContext('2d');
        new Chart(pagesCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($page_labels) ?>,
                datasets: [
                    {
                        label: 'Total Views',
                        data: <?= json_encode($page_views_data) ?>,
                        backgroundColor: 'rgba(128, 0, 0, 0.7)'
                    },
                    {
                        label: 'Unique Views',
                        data: <?= json_encode($unique_page_views_data) ?>,
                        backgroundColor: 'rgba(13, 110, 253, 0.7)'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Top Pages by Views'
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
        
        // Device Type Chart (example with static data)
        const deviceCtx = document.getElementById('deviceTypeChart').getContext('2d');
        new Chart(deviceCtx, {
            type: 'pie',
            data: {
                labels: ['Desktop', 'Mobile', 'Tablet'],
                datasets: [{
                    data: [65, 30, 5],
                    backgroundColor: [
                        '#800000',
                        '#0d6efd',
                        '#198754'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Device Types'
                    }
                }
            }
        });
        
        // Browser Chart (example with static data)
        const browserCtx = document.getElementById('browserChart').getContext('2d');
        new Chart(browserCtx, {
            type: 'pie',
            data: {
                labels: ['Chrome', 'Safari', 'Firefox', 'Edge', 'Other'],
                datasets: [{
                    data: [60, 15, 10, 10, 5],
                    backgroundColor: [
                        '#800000',
                        '#0d6efd',
                        '#198754',
                        '#fd7e14',
                        '#6f42c1'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Browser Usage'
                    }
                }
            }
        });
        
        // Export data button
        document.getElementById('exportData').addEventListener('click', function() {
            // In a real implementation, this would generate a CSV or Excel file
            alert('Export functionality would generate a report here');
        });
    });
    </script>
</body>
</html>