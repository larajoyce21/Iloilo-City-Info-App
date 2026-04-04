<?php
require_once 'conn.php';

function trackVisitor($conn) {
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    $page_visited = $_SERVER['REQUEST_URI'];
    $referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
    
    $device_info = detectDeviceInfo($user_agent);
    $device_type = $device_info['device_type'];
    $browser = $device_info['browser'];
    $os = $device_info['os'];
    
    $today = date('Y-m-d');
    $current_time = date('H:i:s');
    
    if (!session_id()) {
        session_start();
    }
    
    if (!isset($_SESSION['visitor_session_id'])) {
        $_SESSION['visitor_session_id'] = session_id();
    }
    $session_id = $_SESSION['visitor_session_id'];
    
    $check_query = "SELECT id FROM visitors 
                    WHERE ip_address = '$ip_address' 
                    AND visit_date = '$today'";
    $result = $conn->query($check_query);
    
    if ($result->num_rows == 0) {
        $insert_query = "INSERT INTO visitors 
                        (ip_address, user_agent, device_type, browser, os, 
                         page_visited, referrer, visit_date, visit_time) 
                        VALUES 
                        ('$ip_address', '$user_agent', '$device_type', '$browser', 
                         '$os', '$page_visited', '$referrer', '$today', '$current_time')";
        $conn->query($insert_query);
        
        updateDailyStats($conn, $today);
    }
    
    $session_check = "SELECT id, page_views FROM visitor_sessions 
                      WHERE session_id = '$session_id'";
    $session_result = $conn->query($session_check);
    
    if ($session_result->num_rows > 0) {
        $row = $session_result->fetch_assoc();
        $new_page_views = $row['page_views'] + 1;
        $update_session = "UPDATE visitor_sessions 
                          SET last_visit = NOW(), page_views = $new_page_views 
                          WHERE session_id = '$session_id'";
        $conn->query($update_session);
    } else {
        $insert_session = "INSERT INTO visitor_sessions 
                          (session_id, ip_address, user_agent, first_visit, last_visit) 
                          VALUES 
                          ('$session_id', '$ip_address', '$user_agent', NOW(), NOW())";
        $conn->query($insert_session);
    }
}

function detectDeviceInfo($user_agent) {
    $device_type = 'Desktop';
    $browser = 'Unknown';
    $os = 'Unknown';
    
    if (preg_match('/(Android|webOS|iPhone|iPad|iPod|BlackBerry|Windows Phone)/i', $user_agent)) {
        if (preg_match('/(iPad)/i', $user_agent)) {
            $device_type = 'Tablet';
        } elseif (preg_match('/(iPhone|iPod)/i', $user_agent)) {
            $device_type = 'Mobile';
        } elseif (preg_match('/(Android)/i', $user_agent)) {
            if (strpos($user_agent, 'Mobile') !== false) {
                $device_type = 'Mobile';
            } else {
                $device_type = 'Tablet';
            }
        } else {
            $device_type = 'Mobile';
        }
    }
    
    if (preg_match('/MSIE|Internet Explorer/i', $user_agent)) {
        $browser = 'Internet Explorer';
    } elseif (preg_match('/Firefox/i', $user_agent)) {
        $browser = 'Firefox';
    } elseif (preg_match('/Chrome/i', $user_agent)) {
        $browser = 'Chrome';
    } elseif (preg_match('/Safari/i', $user_agent)) {
        $browser = 'Safari';
    } elseif (preg_match('/Opera|OPR/i', $user_agent)) {
        $browser = 'Opera';
    } elseif (preg_match('/Edge/i', $user_agent)) {
        $browser = 'Edge';
    }
    
    if (preg_match('/Windows/i', $user_agent)) {
        $os = 'Windows';
    } elseif (preg_match('/Macintosh|Mac OS X/i', $user_agent)) {
        $os = 'Mac OS';
    } elseif (preg_match('/Linux/i', $user_agent)) {
        $os = 'Linux';
    } elseif (preg_match('/Android/i', $user_agent)) {
        $os = 'Android';
    } elseif (preg_match('/iPhone|iPad|iPod/i', $user_agent)) {
        $os = 'iOS';
    }
    
    return [
        'device_type' => $device_type,
        'browser' => $browser,
        'os' => $os
    ];
}

function updateDailyStats($conn, $date) {
    $visitors_query = "SELECT COUNT(DISTINCT ip_address) as unique_visitors 
                       FROM visitors 
                       WHERE visit_date = '$date'";
    $result = $conn->query($visitors_query);
    $stats = $result->fetch_assoc();
    
    $unique_visitors = $stats['unique_visitors'];
    
    $update_query = "INSERT INTO daily_visitor_stats 
                    (visit_date, unique_visitors) 
                    VALUES 
                    ('$date', $unique_visitors)
                    ON DUPLICATE KEY UPDATE 
                    unique_visitors = $unique_visitors";
    $conn->query($update_query);
}

trackVisitor($conn);
?>