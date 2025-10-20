<?php
if (!function_exists('is_logged_in')) {
    function is_logged_in() {
        // Debug logging
        error_log("AUTH CHECK: User ID in session: " . ($_SESSION['user_id'] ?? 'NOT SET'));
        $logged_in = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
        error_log("AUTH CHECK: Result: " . ($logged_in ? 'TRUE' : 'FALSE'));
        
        return $logged_in;
    }
}

if (!function_exists('redirect')) {
    function redirect($url) {
        error_log("REDIRECT: Going to: " . $url);
        header("Location: $url");
        exit();
    }
}

if (!function_exists('sanitize_input')) {
    function sanitize_input($data) {
        if (empty($data)) return $data;
        return htmlspecialchars(strip_tags(trim($data)));
    }
}

if (!function_exists('time_ago')) {
    function time_ago($datetime) {
        $time = strtotime($datetime);
        $now = time();
        $diff = $now - $time;
        
        if ($diff < 60) {
            return 'just now';
        } elseif ($diff < 3600) {
            return floor($diff / 60) . ' minutes ago';
        } elseif ($diff < 86400) {
            return floor($diff / 3600) . ' hours ago';
        } else {
            return floor($diff / 86400) . ' days ago';
        }
    }
}
?>