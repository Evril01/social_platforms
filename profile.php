<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

if (!is_logged_in()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

// Check if columns exist in the database
$check_columns_sql = "SHOW COLUMNS FROM users LIKE 'bio'";
$result = $conn->query($check_columns_sql);
$bio_column_exists = $result->num_rows > 0;

$check_columns_sql = "SHOW COLUMNS FROM users LIKE 'website'";
$result = $conn->query($check_columns_sql);
$website_column_exists = $result->num_rows > 0;

$check_columns_sql = "SHOW COLUMNS FROM users LIKE 'location'";
$result = $conn->query($check_columns_sql);
$location_column_exists = $result->num_rows > 0;

// Handle profile updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $full_name = sanitize_input($_POST['full_name']);
        $bio = $bio_column_exists ? sanitize_input($_POST['bio']) : null;
        $website = $website_column_exists ? sanitize_input($_POST['website']) : null;
        $location = $location_column_exists ? sanitize_input($_POST['location']) : null;
        
        // Handle profile picture upload
        $profile_picture = null;
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === 0) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $file_type = $_FILES['profile_picture']['type'];
            
            if (in_array($file_type, $allowed_types)) {
                $profile_picture = upload_file($_FILES['profile_picture'], 'uploads/profile_pics/');
                
                // Update profile picture in database
                $stmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
                $stmt->bind_param("si", $profile_picture, $user_id);
                $stmt->execute();
            }
        }
        
        // Build update query based on available columns
        $update_fields = ["full_name = ?"];
        $param_types = "s";
        $param_values = [$full_name];
        
        if ($bio_column_exists) {
            $update_fields[] = "bio = ?";
            $param_types .= "s";
            $param_values[] = $bio;
        }
        
        if ($website_column_exists) {
            $update_fields[] = "website = ?";
            $param_types .= "s";
            $param_values[] = $website;
        }
        
        if ($location_column_exists) {
            $update_fields[] = "location = ?";
            $param_types .= "s";
            $param_values[] = $location;
        }
        
        $update_fields_sql = implode(", ", $update_fields);
        $param_values[] = $user_id;
        $param_types .= "i";
        
        $stmt = $conn->prepare("UPDATE users SET $update_fields_sql WHERE id = ?");
        $stmt->bind_param($param_types, ...$param_values);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Profile updated successfully!";
            // Update session variables
            $_SESSION['full_name'] = $full_name;
        } else {
            $_SESSION['error'] = "Error updating profile.";
        }
        
        redirect('profile.php');
    }
}

// Get user data - only select columns that exist
$select_fields = ["username", "email", "full_name", "profile_picture", "created_at"];
if ($bio_column_exists) $select_fields[] = "bio";
if ($website_column_exists) $select_fields[] = "website";
if ($location_column_exists) $select_fields[] = "location";

$select_sql = implode(", ", $select_fields);
$stmt = $conn->prepare("SELECT $select_sql FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Get user's posts count
$stmt = $conn->prepare("SELECT COUNT(*) as post_count FROM posts WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$post_count_result = $stmt->get_result();
$post_count = $post_count_result->fetch_assoc()['post_count'];

// Get user's recent posts
$stmt = $conn->prepare("
    SELECT p.*, u.username, u.full_name, u.profile_picture 
    FROM posts p 
    JOIN users u ON p.user_id = u.id 
    WHERE u.id = ? 
    ORDER BY p.created_at DESC 
    LIMIT 5
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$posts_result = $stmt->get_result();
$recent_posts = $posts_result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Social Media</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --accent-color: #ff6b6b;
            --success-color: #4ecdc4;
            --warning-color: #ffa726;
            --text-dark: #2d3748;
            --text-light: #718096;
            --bg-light: #f7fafc;
            --bg-white: #ffffff;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.1);
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: var(--text-dark);
            line-height: 1.6;
            min-height: 100vh;
            transition: background 0.5s ease;
        }

        /* Theme Selector */
        .theme-selector {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            background: rgba(255, 255, 255, 0.95);
            padding: 15px;
            border-radius: 15px;
            box-shadow: var(--shadow-lg);
            backdrop-filter: blur(10px);
        }
        
        .theme-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .theme-btn {
            width: 30px;
            height: 30px;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        
        .theme-btn:hover {
            transform: scale(1.1);
        }
        
        .theme-btn.active {
            border-color: var(--bg-white);
            box-shadow: 0 0 10px rgba(0,0,0,0.3);
            transform: scale(1.05);
        }
        
        .header {
            background: rgba(255, 255, 255, 0.95);
            box-shadow: var(--shadow);
            padding: 0.8rem 0;
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 999;
            border-bottom: 1px solid rgba(221, 223, 226, 0.8);
            backdrop-filter: blur(10px);
        }
        
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .navbar-brand {
            font-size: 1.8rem;
            font-weight: bold;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .navbar-nav {
            display: flex;
            list-style: none;
            gap: 1rem;
        }
        
        .navbar-nav a {
            text-decoration: none;
            color: var(--text-dark);
            padding: 0.6rem 1.2rem;
            border-radius: 25px;
            transition: all 0.3s ease;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .navbar-nav a:hover {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            transform: translateY(-2px);
        }
        
        .navbar-nav a.active {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
        }
        
        .profile-container {
            margin-top: 80px;
            padding: 2rem 20px;
        }
        
        .profile {
            max-width: 1000px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 2rem;
        }
        
        /* Profile Sidebar */
        .profile-sidebar {
            background: rgba(255, 255, 255, 0.95);
            padding: 2rem;
            border-radius: 20px;
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            height: fit-content;
            position: sticky;
            top: 100px;
        }
        
        .profile-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            margin: 0 auto 1.5rem;
            position: relative;
            border: 4px solid var(--bg-white);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        }
        
        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }
        
        .avatar-initials {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2.5rem;
            font-weight: bold;
        }
        
        .change-avatar-btn {
            position: absolute;
            bottom: 5px;
            right: 5px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 50%;
            width: 36px;
            height: 36px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        
        .change-avatar-btn:hover {
            background: var(--secondary-color);
            transform: scale(1.1);
        }
        
        .profile-name {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }
        
        .profile-username {
            color: var(--text-light);
            font-size: 1rem;
            margin-bottom: 1rem;
        }
        
        .profile-bio {
            color: var(--text-dark);
            line-height: 1.6;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        
        .profile-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-item {
            text-align: center;
            padding: 1rem;
            background: var(--bg-light);
            border-radius: 12px;
            transition: all 0.3s ease;
        }
        
        .stat-item:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow);
        }
        
        .stat-number {
            display: block;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .stat-label {
            font-size: 0.9rem;
            color: var(--text-light);
        }
        
        .profile-info {
            margin-bottom: 2rem;
        }
        
        .info-item {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            margin-bottom: 1rem;
            padding: 0.8rem;
            background: var(--bg-light);
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        
        .info-item:hover {
            background: var(--bg-white);
            transform: translateX(5px);
        }
        
        .info-icon {
            font-size: 1.2rem;
            width: 24px;
            text-align: center;
        }
        
        /* Profile Content */
        .profile-content {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }
        
        .profile-card {
            background: rgba(255, 255, 255, 0.95);
            padding: 2rem;
            border-radius: 20px;
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            transition: transform 0.3s ease;
        }
        
        .profile-card:hover {
            transform: translateY(-5px);
        }
        
        .profile-card h2 {
            margin-bottom: 1.5rem;
            color: var(--text-dark);
            font-size: 1.5rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 2px solid var(--bg-light);
            padding-bottom: 0.8rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text-dark);
        }
        
        .form-control {
            width: 100%;
            padding: 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 1rem;
            font-family: inherit;
            transition: all 0.3s ease;
            background: var(--bg-light);
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            background: var(--bg-white);
        }
        
        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }
        
        .btn-primary {
            padding: 1rem 2rem;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }
        
        /* Posts Section */
        .posts-container {
            background: rgba(255, 255, 255, 0.95);
            padding: 2rem;
            border-radius: 20px;
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
        }
        
        .post {
            background: var(--bg-white);
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
        }
        
        .post:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary-color);
        }
        
        .post-header {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            gap: 1rem;
        }
        
        .avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.2rem;
            color: white;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            position: relative;
            overflow: hidden;
            border: 3px solid var(--bg-white);
            box-shadow: var(--shadow);
        }
        
        .avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }
        
        .avatar-initials {
            font-size: 1.1rem;
            font-weight: 700;
        }
        
        .post-user {
            flex: 1;
        }
        
        .post-user strong {
            display: block;
            color: var(--text-dark);
            font-weight: 700;
            font-size: 1.1rem;
        }
        
        .post-time {
            color: var(--text-light);
            font-size: 0.85rem;
        }
        
        .post-content p {
            line-height: 1.7;
            color: var(--text-dark);
            margin-bottom: 1rem;
        }
        
        .post-image {
            max-width: 100%;
            max-height: 300px;
            border-radius: 12px;
            box-shadow: var(--shadow);
            transition: transform 0.3s ease;
            cursor: pointer;
        }
        
        .post-image:hover {
            transform: scale(1.02);
        }
        
        .post-actions {
            display: flex;
            gap: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #e2e8f0;
        }
        
        .action-btn {
            background: none;
            border: none;
            color: var(--text-light);
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
        }
        
        .action-btn:hover {
            background: var(--bg-light);
            color: var(--primary-color);
            transform: translateY(-1px);
        }
        
        .no-posts {
            text-align: center;
            color: var(--text-light);
            padding: 3rem 2rem;
            font-style: italic;
            background: var(--bg-light);
            border-radius: 12px;
            border: 2px dashed #cbd5e0;
        }
        
        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .fade-in-up {
            animation: fadeInUp 0.6s ease;
        }
        
        /* Responsive Design */
        @media (max-width: 968px) {
            .profile {
                grid-template-columns: 1fr;
            }
            
            .profile-sidebar {
                position: static;
            }
        }
        
        @media (max-width: 768px) {
            .theme-selector {
                position: static;
                margin-bottom: 1rem;
            }
            
            .theme-buttons {
                justify-content: center;
            }
            
            .navbar {
                flex-direction: column;
                gap: 1rem;
            }
            
            .navbar-nav {
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .profile-container {
                margin-top: 140px;
            }
            
            .profile-stats {
                grid-template-columns: 1fr;
            }
        }
        
        /* Success/Error Messages */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            font-weight: 500;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        /* Database Update Alert */
        .db-update-alert {
            background: linear-gradient(135deg, #ffa726, #ff6b6b);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            text-align: center;
            box-shadow: var(--shadow);
        }
        
        .db-update-alert a {
            color: white;
            text-decoration: underline;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <!-- Theme Selector -->
    <div class="theme-selector">
        <div class="theme-buttons">
            <button class="theme-btn active" style="background: linear-gradient(135deg, #667eea, #764ba2);" onclick="changeTheme('purple')" title="Purple Theme"></button>
            <button class="theme-btn" style="background: linear-gradient(135deg, #ff6b6b, #ffa726);" onclick="changeTheme('orange')" title="Orange Theme"></button>
            <button class="theme-btn" style="background: linear-gradient(135deg, #4ecdc4, #44a08d);" onclick="changeTheme('teal')" title="Teal Theme"></button>
            <button class="theme-btn" style="background: linear-gradient(135deg, #a8ff78, #78ffd6);" onclick="changeTheme('green')" title="Green Theme"></button>
        </div>
    </div>

    <header class="header">
        <div class="navbar">
            <a href="dashboard.php" class="navbar-brand">
                <span>📱</span> SocialMedia
            </a>
            <ul class="navbar-nav">
                <li><a href="dashboard.php">🏠 Dashboard</a></li>
                <li><a href="profile.php" class="active">👤 Profile</a></li>
                <li><a href="search.php">🔍 Search</a></li>
                <li><a href="messages.php">💬 Messages</a></li>
                <li><a href="logout.php">🚪 Logout (<?php echo $_SESSION['username']; ?>)</a></li>
            </ul>
        </div>
    </header>
    
    <div class="profile-container">
        <div class="profile">
            <!-- Database Update Alert -->
            <?php if (!$bio_column_exists || !$website_column_exists || !$location_column_exists): ?>
            <div class="db-update-alert fade-in-up">
                <strong>⚠️ Database Update Required</strong><br>
                Some profile features are disabled because your database is missing columns. 
                <a href="update_database.php" style="color: white; text-decoration: underline;">Click here to update your database</a>
            </div>
            <?php endif; ?>

            <!-- Profile Sidebar -->
            <div class="profile-sidebar fade-in-up">
                <div class="profile-header">
                    <div class="profile-avatar">
                        <?php 
                        // Generate avatar initials
                        $initials = '';
                        if (!empty($user['full_name'])) {
                            $name_parts = explode(' ', $user['full_name']);
                            $initials = '';
                            foreach ($name_parts as $part) {
                                $initials .= strtoupper(substr($part, 0, 1));
                                if (strlen($initials) >= 2) break;
                            }
                        } else {
                            $initials = strtoupper(substr($user['username'], 0, 2));
                        }
                        ?>
                        <?php if (!empty($user['profile_picture'])): ?>
                            <img src="uploads/profile_pics/<?php echo $user['profile_picture']; ?>" alt="<?php echo $user['full_name'] ?: $user['username']; ?>">
                        <?php else: ?>
                            <div class="avatar-initials"><?php echo $initials; ?></div>
                        <?php endif; ?>
                        <button class="change-avatar-btn" onclick="document.getElementById('profile_picture').click()">📷</button>
                    </div>
                    
                    <h1 class="profile-name"><?php echo htmlspecialchars($user['full_name'] ?: $user['username']); ?></h1>
                    <div class="profile-username">@<?php echo htmlspecialchars($user['username']); ?></div>
                    
                    <?php if ($bio_column_exists && !empty($user['bio'])): ?>
                        <p class="profile-bio"><?php echo htmlspecialchars($user['bio']); ?></p>
                    <?php elseif ($bio_column_exists): ?>
                        <p class="profile-bio" style="color: var(--text-light); font-style: italic;">No bio yet. Tell people about yourself!</p>
                    <?php endif; ?>
                </div>

                <div class="profile-stats">
                    <div class="stat-item">
                        <span class="stat-number"><?php echo $post_count; ?></span>
                        <span class="stat-label">Posts</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">0</span>
                        <span class="stat-label">Following</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">0</span>
                        <span class="stat-label">Followers</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">0</span>
                        <span class="stat-label">Likes</span>
                    </div>
                </div>

                <div class="profile-info">
                    <?php if ($location_column_exists && !empty($user['location'])): ?>
                        <div class="info-item">
                            <span class="info-icon">📍</span>
                            <span><?php echo htmlspecialchars($user['location']); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($website_column_exists && !empty($user['website'])): ?>
                        <div class="info-item">
                            <span class="info-icon">🌐</span>
                            <a href="<?php echo htmlspecialchars($user['website']); ?>" target="_blank" style="color: var(--primary-color); text-decoration: none;">
                                Website
                            </a>
                        </div>
                    <?php endif; ?>
                    
                    <div class="info-item">
                        <span class="info-icon">📅</span>
                        <span>Joined <?php echo date('F Y', strtotime($user['created_at'])); ?></span>
                    </div>
                </div>
            </div>

            <!-- Profile Content -->
            <div class="profile-content">
                <!-- Success/Error Messages -->
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success fade-in-up">
                        ✅ <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-error fade-in-up">
                        ❌ <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>

                <!-- Edit Profile Card -->
                <div class="profile-card fade-in-up">
                    <h2>✏️ Edit Profile</h2>
                    <form method="POST" enctype="multipart/form-data" id="profile-form">
                        <input type="file" id="profile_picture" name="profile_picture" accept="image/*" style="display: none;" onchange="previewProfilePicture(this)">
                        
                        <div class="form-group">
                            <label for="full_name">Full Name</label>
                            <input type="text" id="full_name" name="full_name" class="form-control" 
                                   value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" 
                                   placeholder="Enter your full name">
                        </div>
                        
                        <?php if ($bio_column_exists): ?>
                        <div class="form-group">
                            <label for="bio">Bio</label>
                            <textarea id="bio" name="bio" class="form-control" 
                                      placeholder="Tell people about yourself..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($website_column_exists): ?>
                        <div class="form-group">
                            <label for="website">Website</label>
                            <input type="url" id="website" name="website" class="form-control" 
                                   value="<?php echo htmlspecialchars($user['website'] ?? ''); ?>" 
                                   placeholder="https://example.com">
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($location_column_exists): ?>
                        <div class="form-group">
                            <label for="location">Location</label>
                            <input type="text" id="location" name="location" class="form-control" 
                                   value="<?php echo htmlspecialchars($user['location'] ?? ''); ?>" 
                                   placeholder="Where are you from?">
                        </div>
                        <?php endif; ?>
                        
                        <button type="submit" name="update_profile" class="btn-primary">
                            💾 Save Changes
                        </button>
                    </form>
                </div>

                <!-- Recent Posts Card -->
                <div class="profile-card fade-in-up">
                    <h2>📝 Recent Posts</h2>
                    <?php if (empty($recent_posts)): ?>
                        <div class="no-posts">
                            <p>No posts yet. Share your first post! ✨</p>
                        </div>
                    <?php else: ?>
                        <div id="posts-container">
                            <?php foreach ($recent_posts as $post): ?>
                                <?php 
                                $post_initials = '';
                                if (!empty($post['full_name'])) {
                                    $name_parts = explode(' ', $post['full_name']);
                                    $post_initials = '';
                                    foreach ($name_parts as $part) {
                                        $post_initials .= strtoupper(substr($part, 0, 1));
                                        if (strlen($post_initials) >= 2) break;
                                    }
                                } else {
                                    $post_initials = strtoupper(substr($post['username'], 0, 2));
                                }
                                ?>
                                <div class="post" data-post-id="<?php echo $post['id']; ?>">
                                    <div class="post-header">
                                        <div class="avatar">
                                            <?php if (!empty($post['profile_picture'])): ?>
                                                <img src="uploads/profile_pics/<?php echo $post['profile_picture']; ?>" alt="<?php echo $post['full_name']; ?>">
                                            <?php else: ?>
                                                <div class="avatar-initials"><?php echo $post_initials; ?></div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="post-user">
                                            <strong><?php echo htmlspecialchars($post['full_name'] ?: $post['username']); ?></strong>
                                            <span class="post-time"><?php echo time_ago($post['created_at']); ?></span>
                                        </div>
                                    </div>
                                    <div class="post-content">
                                        <p><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                                        <?php if ($post['image']): ?>
                                            <img src="uploads/post_images/<?php echo $post['image']; ?>" alt="Post Image" class="post-image" onclick="openImageModal(this.src)">
                                        <?php endif; ?>
                                    </div>
                                    <div class="post-actions">
                                        <button class="action-btn" onclick="likePost(<?php echo $post['id']; ?>)">
                                            <span>❤️</span> Like
                                        </button>
                                        <button class="action-btn" onclick="commentOnPost(<?php echo $post['id']; ?>)">
                                            <span>💬</span> Comment
                                        </button>
                                        <button class="action-btn" onclick="sharePost(<?php echo $post['id']; ?>)">
                                            <span>🔄</span> Share
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Image Modal -->
    <div id="imageModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); z-index: 2000; justify-content: center; align-items: center;">
        <img id="modalImage" src="" style="max-width: 90%; max-height: 90%; border-radius: 10px;">
        <button onclick="closeImageModal()" style="position: absolute; top: 20px; right: 20px; background: #ff6b6b; color: white; border: none; border-radius: 50%; width: 40px; height: 40px; font-size: 1.2rem; cursor: pointer;">×</button>
    </div>

    <script>
        // Theme Management
        const themes = {
            purple: ['#667eea', '#764ba2'],
            orange: ['#ff6b6b', '#ffa726'],
            teal: ['#4ecdc4', '#44a08d'],
            green: ['#a8ff78', '#78ffd6']
        };

        function changeTheme(theme) {
            const [color1, color2] = themes[theme];
            document.documentElement.style.setProperty('--primary-color', color1);
            document.documentElement.style.setProperty('--secondary-color', color2);
            document.body.style.background = `linear-gradient(135deg, ${color1} 0%, ${color2} 100%)`;
            
            document.querySelectorAll('.theme-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');
            
            localStorage.setItem('selectedTheme', theme);
        }

        // Load saved theme
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('selectedTheme') || 'purple';
            changeTheme(savedTheme);
            
            // Add animation delays
            document.querySelectorAll('.fade-in-up').forEach((element, index) => {
                element.style.animationDelay = `${index * 0.2}s`;
            });
        });

        // Interactive Functions
        function previewProfilePicture(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const avatar = document.querySelector('.profile-avatar');
                    avatar.innerHTML = `<img src="${e.target.result}" alt="Profile Picture">`;
                };
                reader.readAsDataURL(input.files[0]);
                
                // Auto-submit the form when a new profile picture is selected
                document.getElementById('profile-form').submit();
            }
        }

        function openImageModal(src) {
            const modal = document.getElementById('imageModal');
            const modalImage = document.getElementById('modalImage');
            modalImage.src = src;
            modal.style.display = 'flex';
        }

        function closeImageModal() {
            const modal = document.getElementById('imageModal');
            modal.style.display = 'none';
        }

        function likePost(postId) {
            const post = document.querySelector(`[data-post-id="${postId}"]`);
            const likeBtn = post.querySelector('.action-btn');
            likeBtn.classList.toggle('liked');
            alert(`Liked post #${postId}!`);
        }

        function commentOnPost(postId) {
            const comment = prompt('Enter your comment:');
            if (comment) {
                alert(`Comment added to post #${postId}: "${comment}"`);
            }
        }

        function sharePost(postId) {
            alert(`Shared post #${postId}!`);
        }

        // Close modal when clicking outside image
        document.getElementById('imageModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeImageModal();
            }
        });
    </script>
</body>
</html>