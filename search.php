<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$search_results = [];
$search_query = '';

// Handle search
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['search'])) {
    $search_query = sanitize_input($_GET['search']);
    
    if (!empty($search_query)) {
        $search_term = "%$search_query%";
        $stmt = $conn->prepare("
            SELECT id, username, full_name, profile_picture, bio, location, created_at 
            FROM users 
            WHERE (username LIKE ? OR full_name LIKE ? OR email LIKE ?) AND id != ?
            ORDER BY 
                CASE 
                    WHEN username LIKE ? THEN 1
                    WHEN full_name LIKE ? THEN 2
                    ELSE 3
                END,
                created_at DESC
        ");
        $stmt->bind_param("sssiss", $search_term, $search_term, $search_term, $user_id, $search_term, $search_term);
        $stmt->execute();
        $result = $stmt->get_result();
        $search_results = $result->fetch_all(MYSQLI_ASSOC);
    }
}

// Get suggested users (users not in search results)
$suggested_users = [];
if (empty($search_results)) {
    $stmt = $conn->prepare("
        SELECT id, username, full_name, profile_picture, bio, location 
        FROM users 
        WHERE id != ? 
        ORDER BY RAND() 
        LIMIT 8
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $suggested_users = $result->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Users - EvrilMedia</title>
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
        
        .search-container {
            margin-top: 80px;
            padding: 2rem 20px;
        }
        
        .search-content {
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .search-header {
            background: rgba(255, 255, 255, 0.95);
            padding: 2rem;
            border-radius: 20px;
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .search-header h1 {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .search-form {
            max-width: 600px;
            margin: 0 auto;
        }
        
        .search-input-group {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .search-input {
            flex: 1;
            padding: 1rem 1.5rem;
            border: 2px solid #e2e8f0;
            border-radius: 25px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: var(--bg-light);
        }
        
        .search-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
            background: var(--bg-white);
        }
        
        .search-btn {
            padding: 1rem 2rem;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            border-radius: 25px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .search-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(139, 92, 246, 0.4);
        }
        
        .results-section {
            background: rgba(255, 255, 255, 0.95);
            padding: 2rem;
            border-radius: 20px;
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
        }
        
        .section-header {
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .section-header h2 {
            font-size: 1.5rem;
            color: var(--text-dark);
            font-weight: 700;
        }
        
        .results-count {
            color: var(--text-light);
            font-size: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .users-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .user-card {
            background: var(--bg-white);
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 1.5rem;
            transition: all 0.3s ease;
            text-align: center;
        }
        
        .user-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary-color);
        }
        
        .user-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin: 0 auto 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            position: relative;
            overflow: hidden;
            border: 3px solid var(--bg-white);
            box-shadow: var(--shadow);
        }
        
        .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }
        
        .user-info {
            margin-bottom: 1.5rem;
        }
        
        .user-name {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }
        
        .user-username {
            color: var(--text-light);
            font-size: 0.9rem;
            margin-bottom: 0.8rem;
        }
        
        .user-bio {
            color: var(--text-dark);
            font-size: 0.9rem;
            line-height: 1.5;
            margin-bottom: 1rem;
        }
        
        .user-location {
            color: var(--text-light);
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }
        
        .user-actions {
            display: flex;
            gap: 0.8rem;
            justify-content: center;
        }
        
        .btn {
            padding: 0.7rem 1.2rem;
            border: none;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 5px;
            text-decoration: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
        }
        
        .btn-secondary {
            background: var(--bg-light);
            color: var(--text-dark);
            border: 1px solid #e2e8f0;
        }
        
        .no-results {
            text-align: center;
            padding: 3rem 2rem;
            color: var(--text-light);
        }
        
        .suggested-section {
            background: rgba(255, 255, 255, 0.95);
            padding: 2rem;
            border-radius: 20px;
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            margin-top: 2rem;
        }
        
        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                gap: 1rem;
            }
            
            .search-container {
                margin-top: 140px;
            }
            
            .search-input-group {
                flex-direction: column;
            }
            
            .users-grid {
                grid-template-columns: 1fr;
            }
            
            .user-actions {
                flex-direction: column;
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
                <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                <li><a href="search.php" class="active"><i class="fas fa-search"></i> Search</a></li>
                <li><a href="messages.php"><i class="fas fa-comment"></i> Messages</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout (<?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>)</a></li>
            </ul>
        </div>
    </header>
    
    <div class="search-container">
        <div class="search-content">
            <div class="search-header">
                <h1><i class="fas fa-search"></i> Search Users</h1>
                <form method="GET" class="search-form">
                    <div class="search-input-group">
                        <input type="text" name="search" class="search-input" 
                               placeholder="Search by name, username, or email..." 
                               value="<?php echo htmlspecialchars($search_query); ?>"
                               required>
                        <button type="submit" class="search-btn">
                            <i class="fas fa-search"></i> Search
                        </button>
                    </div>
                    <p style="color: var(--text-light); font-size: 0.9rem;">Find friends and connect with people on EvrilMedia</p>
                </form>
            </div>

            <div class="results-section">
                <div class="section-header">
                    <h2><i class="fas fa-list"></i> Search Results</h2>
                </div>
                
                <?php if (!empty($search_query)): ?>
                    <div class="results-count">
                        Found <?php echo count($search_results); ?> result(s) for "<?php echo htmlspecialchars($search_query); ?>"
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($search_results)): ?>
                    <div class="users-grid">
                        <?php foreach ($search_results as $user): ?>
                            <?php 
                            $initials = '';
                            if (!empty($user['full_name'])) {
                                $name_parts = explode(' ', $user['full_name']);
                                foreach ($name_parts as $part) {
                                    $initials .= strtoupper(substr($part, 0, 1));
                                    if (strlen($initials) >= 2) break;
                                }
                            } else {
                                $initials = strtoupper(substr($user['username'], 0, 2));
                            }
                            ?>
                            <div class="user-card">
                                <div class="user-avatar">
                                    <?php if (!empty($user['profile_picture'])): ?>
                                        <img src="uploads/profile_pics/<?php echo htmlspecialchars($user['profile_picture']); ?>" 
                                             alt="<?php echo htmlspecialchars($user['full_name'] ?: $user['username']); ?>">
                                    <?php else: ?>
                                        <?php echo htmlspecialchars($initials); ?>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="user-info">
                                    <div class="user-name"><?php echo htmlspecialchars($user['full_name'] ?: $user['username']); ?></div>
                                    <div class="user-username">@<?php echo htmlspecialchars($user['username']); ?></div>
                                    
                                    <?php if (!empty($user['bio'])): ?>
                                        <div class="user-bio"><?php echo htmlspecialchars($user['bio']); ?></div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($user['location'])): ?>
                                        <div class="user-location">
                                            <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($user['location']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="user-actions">
                                    <a href="profile.php?user_id=<?php echo $user['id']; ?>" class="btn btn-primary">
                                        <i class="fas fa-eye"></i> View Profile
                                    </a>
                                    <a href="messages.php?user_id=<?php echo $user['id']; ?>" class="btn btn-secondary">
                                        <i class="fas fa-comment"></i> Message
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php elseif (!empty($search_query)): ?>
                    <div class="no-results">
                        <div style="font-size: 4rem; margin-bottom: 1rem; opacity: 0.5;"><i class="fas fa-search"></i></div>
                        <h3 style="font-size: 1.5rem; margin-bottom: 1rem; color: var(--text-dark);">No users found</h3>
                        <p>No users found matching "<?php echo htmlspecialchars($search_query); ?>"</p>
                        <p>Try searching with different keywords or browse suggested users below.</p>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (empty($search_query) || (empty($search_results) && !empty($suggested_users))): ?>
            <div class="suggested-section">
                <div class="section-header">
                    <h2><i class="fas fa-users"></i> Suggested Users</h2>
                </div>
                
                <div class="users-grid">
                    <?php foreach ($suggested_users as $user): ?>
                        <?php 
                        $initials = '';
                        if (!empty($user['full_name'])) {
                            $name_parts = explode(' ', $user['full_name']);
                            foreach ($name_parts as $part) {
                                $initials .= strtoupper(substr($part, 0, 1));
                                if (strlen($initials) >= 2) break;
                            }
                        } else {
                            $initials = strtoupper(substr($user['username'], 0, 2));
                        }
                        ?>
                        <div class="user-card">
                            <div class="user-avatar">
                                <?php if (!empty($user['profile_picture'])): ?>
                                    <img src="uploads/profile_pics/<?php echo htmlspecialchars($user['profile_picture']); ?>" 
                                         alt="<?php echo htmlspecialchars($user['full_name'] ?: $user['username']); ?>">
                                <?php else: ?>
                                    <?php echo htmlspecialchars($initials); ?>
                                <?php endif; ?>
                            </div>
                            
                            <div class="user-info">
                                <div class="user-name"><?php echo htmlspecialchars($user['full_name'] ?: $user['username']); ?></div>
                                <div class="user-username">@<?php echo htmlspecialchars($user['username']); ?></div>
                                
                                <?php if (!empty($user['bio'])): ?>
                                    <div class="user-bio"><?php echo htmlspecialchars($user['bio']); ?></div>
                                <?php endif; ?>
                                
                                <?php if (!empty($user['location'])): ?>
                                    <div class="user-location">
                                        <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($user['location']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="user-actions">
                                <a href="profile.php?user_id=<?php echo $user['id']; ?>" class="btn btn-primary">
                                    <i class="fas fa-eye"></i> View Profile
                                </a>
                                <a href="messages.php?user_id=<?php echo $user['id']; ?>" class="btn btn-secondary">
                                    <i class="fas fa-comment"></i> Message
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>