<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

// Handle profile picture upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_picture'])) {
    $upload_success = false;
    
    if ($_FILES['profile_picture']['error'] === 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = $_FILES['profile_picture']['type'];
        $file_size = $_FILES['profile_picture']['size'];
        $max_size = 2 * 1024 * 1024; // 2MB
        
        if (in_array($file_type, $allowed_types)) {
            if ($file_size <= $max_size) {
                // Create uploads directory if it doesn't exist
                $upload_dir = 'uploads/profile_pics/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                // Generate unique filename
                $file_extension = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
                $new_filename = 'profile_' . $user_id . '_' . time() . '.' . $file_extension;
                $target_file = $upload_dir . $new_filename;
                
                // Check if user already has a profile picture
                $stmt = $conn->prepare("SELECT profile_picture FROM users WHERE id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();
                
                // Delete old profile picture if exists
                if (!empty($user['profile_picture']) && file_exists($upload_dir . $user['profile_picture'])) {
                    unlink($upload_dir . $user['profile_picture']);
                }
                
                // Upload new profile picture
                if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target_file)) {
                    // Update database
                    $stmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
                    $stmt->bind_param("si", $new_filename, $user_id);
                    
                    if ($stmt->execute()) {
                        $_SESSION['profile_picture'] = $new_filename;
                        $upload_success = true;
                        $success_message = "Profile picture updated successfully!";
                    } else {
                        $error_message = "Failed to update profile picture in database.";
                    }
                } else {
                    $error_message = "Failed to upload profile picture.";
                }
            } else {
                $error_message = "File size too large. Maximum size is 2MB.";
            }
        } else {
            $error_message = "Invalid file type. Only JPG, PNG, and GIF files are allowed.";
        }
    } else {
        $error_message = "Error uploading file. Please try again.";
    }
}

// Handle new post
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['content'])) {
    $content = sanitize_input($_POST['content']);
    $user_id = $_SESSION['user_id'];
    $image = null;
    
    if (isset($_FILES['post_image']) && $_FILES['post_image']['error'] === 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = $_FILES['post_image']['type'];
        
        if (in_array($file_type, $allowed_types)) {
            // Create uploads directory if it doesn't exist
            $upload_dir = 'uploads/post_images/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Generate unique filename
            $file_extension = pathinfo($_FILES['post_image']['name'], PATHINFO_EXTENSION);
            $new_filename = 'post_' . $user_id . '_' . time() . '.' . $file_extension;
            $target_file = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['post_image']['tmp_name'], $target_file)) {
                $image = $new_filename;
            }
        }
    }
    
    $stmt = $conn->prepare("INSERT INTO posts (user_id, content, image) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user_id, $content, $image);
    
    if ($stmt->execute()) {
        $post_success = "Post published successfully!";
    } else {
        $post_error = "Failed to publish post. Please try again.";
    }
}

// Get user's posts
$posts = [];
$stmt = $conn->prepare("
    SELECT p.*, u.username, u.full_name, u.profile_picture 
    FROM posts p 
    JOIN users u ON p.user_id = u.id 
    WHERE u.id = ? 
    ORDER BY p.created_at DESC
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
if ($result) {
    $posts = $result->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - EvrilMedia</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --primary-color: #8b5cf6;
            --secondary-color: #7c3aed;
            --accent-color: #f59e0b;
            --success-color: #10b981;
            --warning-color: #ef4444;
            --text-dark: #1f2937;
            --text-light: #6b7280;
            --bg-light: #f9fafb;
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
        
        .dashboard-container {
            margin-top: 80px;
            padding: 2rem 20px;
        }
        
        .dashboard {
            max-width: 680px;
            margin: 0 auto;
        }
        
        .user-welcome {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            text-align: center;
            box-shadow: var(--shadow);
        }
        
        .user-welcome h3 {
            margin-bottom: 0.5rem;
            font-size: 1.3rem;
        }
        
        .profile-picture-section {
            background: rgba(255, 255, 255, 0.95);
            padding: 2rem;
            border-radius: 20px;
            box-shadow: var(--shadow-lg);
            margin-bottom: 2rem;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
        }
        
        .current-profile-picture {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--primary-color);
            margin: 0 auto 1rem;
            box-shadow: var(--shadow-lg);
        }
        
        .create-post {
            background: rgba(255, 255, 255, 0.95);
            padding: 2rem;
            border-radius: 20px;
            box-shadow: var(--shadow-lg);
            margin-bottom: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group textarea {
            width: 100%;
            padding: 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 1rem;
            font-family: inherit;
            resize: vertical;
            min-height: 120px;
            background: var(--bg-light);
        }
        
        .btn-primary {
            width: 100%;
            padding: 1rem 2rem;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
        }
        
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
        }
        
        .success-messages {
            background: #d4edda;
            color: #155724;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .error-messages {
            background: #fee;
            color: #c33;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                gap: 1rem;
            }
            
            .dashboard-container {
                margin-top: 140px;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="navbar">
            <a href="dashboard.php" class="navbar-brand">
                <i class="fas fa-comments"></i> EvrilMedia
            </a>
            <ul class="navbar-nav">
                <li><a href="dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                <li><a href="search.php"><i class="fas fa-search"></i> Search</a></li>
                <li><a href="messages.php"><i class="fas fa-comment"></i> Messages</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout (<?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>)</a></li>
            </ul>
        </div>
    </header>
    
    <div class="dashboard-container">
        <div class="dashboard">
            <div class="user-welcome">
                <h3>Welcome back, <?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User'); ?>!</h3>
                <p>Ready to share something amazing today?</p>
            </div>

            <div class="profile-picture-section">
                <h2><i class="fas fa-camera"></i> Your Profile Picture</h2>
                
                <?php if (isset($success_message)): ?>
                    <div class="success-messages"><?php echo htmlspecialchars($success_message); ?></div>
                <?php endif; ?>
                
                <?php if (isset($error_message)): ?>
                    <div class="error-messages"><?php echo htmlspecialchars($error_message); ?></div>
                <?php endif; ?>
                
                <div style="text-align: center;">
                    <?php if (!empty($_SESSION['profile_picture'])): ?>
                        <img src="uploads/profile_pics/<?php echo htmlspecialchars($_SESSION['profile_picture']); ?>" 
                             alt="Profile Picture" 
                             class="current-profile-picture">
                    <?php else: ?>
                        <?php
                        $initials = '';
                        if (!empty($_SESSION['full_name'])) {
                            $name_parts = explode(' ', $_SESSION['full_name']);
                            foreach ($name_parts as $part) {
                                $initials .= strtoupper(substr($part, 0, 1));
                                if (strlen($initials) >= 2) break;
                            }
                        } else {
                            $initials = strtoupper(substr($_SESSION['username'] ?? 'US', 0, 2));
                        }
                        ?>
                        <div class="current-profile-picture" style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); display: flex; align-items: center; justify-content: center; color: white; font-size: 2rem; font-weight: bold;">
                            <?php echo htmlspecialchars($initials); ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <form method="POST" enctype="multipart/form-data" style="margin-top: 1rem;">
                    <div class="form-group">
                        <label for="profile_picture"><i class="fas fa-upload"></i> Update Profile Picture</label>
                        <input type="file" id="profile_picture" name="profile_picture" accept="image/jpeg,image/png,image/gif" required>
                    </div>
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i> Update Picture
                    </button>
                </form>
            </div>

            <div class="create-post">
                <h2><i class="fas fa-edit"></i> Share Something...</h2>
                
                <?php if (isset($post_success)): ?>
                    <div class="success-messages"><?php echo htmlspecialchars($post_success); ?></div>
                <?php endif; ?>
                
                <?php if (isset($post_error)): ?>
                    <div class="error-messages"><?php echo htmlspecialchars($post_error); ?></div>
                <?php endif; ?>
                
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <textarea name="content" placeholder="What's on your mind? Share your thoughts..." required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="post_image"><i class="fas fa-image"></i> Add Image (Optional)</label>
                        <input type="file" id="post_image" name="post_image" accept="image/*">
                    </div>
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-paper-plane"></i> Post to Timeline
                    </button>
                </form>
            </div>
            
            <div class="posts-container">
                <h2><i class="fas fa-list"></i> Your Posts</h2>
                <?php if (empty($posts)): ?>
                    <div style="text-align: center; padding: 3rem 2rem; color: var(--text-light);">
                        <p><i class="fas fa-inbox"></i> No posts yet. Share your first post!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($posts as $post): ?>
                        <?php 
                        $initials = '';
                        if (!empty($post['full_name'])) {
                            $name_parts = explode(' ', $post['full_name']);
                            foreach ($name_parts as $part) {
                                $initials .= strtoupper(substr($part, 0, 1));
                                if (strlen($initials) >= 2) break;
                            }
                        } else {
                            $initials = strtoupper(substr($post['username'], 0, 2));
                        }
                        ?>
                        <div class="post">
                            <div class="post-header">
                                <div class="avatar">
                                    <?php if (!empty($post['profile_picture'])): ?>
                                        <img src="uploads/profile_pics/<?php echo htmlspecialchars($post['profile_picture']); ?>" alt="<?php echo htmlspecialchars($post['full_name'] ?: $post['username']); ?>" style="width: 100%; height: 100%; border-radius: 50%;">
                                    <?php else: ?>
                                        <?php echo htmlspecialchars($initials); ?>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <strong><?php echo htmlspecialchars($post['full_name'] ?: $post['username']); ?></strong>
                                    <div style="color: var(--text-light); font-size: 0.85rem;">
                                        <?php echo time_ago($post['created_at']); ?>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <p><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                                <?php if ($post['image']): ?>
                                    <img src="uploads/post_images/<?php echo htmlspecialchars($post['image']); ?>" alt="Post Image" style="max-width: 100%; border-radius: 12px; margin-top: 1rem;">
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Simple theme changer
        function changeTheme(theme) {
            const themes = {
                purple: ['#8b5cf6', '#7c3aed'],
                orange: ['#ff6b6b', '#ffa726'],
                teal: ['#4ecdc4', '#44a08d'],
                green: ['#a8ff78', '#78ffd6']
            };
            
            const [color1, color2] = themes[theme];
            document.documentElement.style.setProperty('--primary-color', color1);
            document.documentElement.style.setProperty('--secondary-color', color2);
            document.body.style.background = `linear-gradient(135deg, ${color1} 0%, ${color2} 100%)`;
        }
    </script>
</body>
</html>