<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// If user is already logged in, redirect to dashboard
if (is_logged_in()) {
    redirect('dashboard.php');
}

$error = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    error_log("Login attempt for email: " . $email);
    
    if (!empty($email) && !empty($password)) {
        // First, let's check what columns exist in the users table
        $check_columns = $conn->query("SHOW COLUMNS FROM users");
        $columns = [];
        while ($row = $check_columns->fetch_assoc()) {
            $columns[] = $row['Field'];
        }
        
        // Build query based on available columns
        $select_fields = [];
        if (in_array('id', $columns)) $select_fields[] = 'id';
        if (in_array('user_id', $columns)) $select_fields[] = 'user_id';
        if (in_array('username', $columns)) $select_fields[] = 'username';
        if (in_array('full_name', $columns)) $select_fields[] = 'full_name';
        if (in_array('profile_picture', $columns)) $select_fields[] = 'profile_picture';
        if (in_array('password', $columns)) $select_fields[] = 'password';
        if (in_array('email', $columns)) $select_fields[] = 'email';
        
        if (empty($select_fields)) {
            $error = "Database configuration error. Please contact administrator.";
        } else {
            $field_list = implode(', ', $select_fields);
            $stmt = $conn->prepare("SELECT $field_list FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                // Determine the user ID field name
                $user_id_field = in_array('id', $columns) ? 'id' : 'user_id';
                
                // Verify password
                if (password_verify($password, $user['password'])) {
                    // Set session variables
                    $_SESSION['user_id'] = $user[$user_id_field];
                    $_SESSION['username'] = $user['username'] ?? '';
                    $_SESSION['full_name'] = $user['full_name'] ?? '';
                    $_SESSION['profile_picture'] = $user['profile_picture'] ?? '';
                    $_SESSION['loggedin'] = true;
                    
                    error_log("Login successful for user: " . ($user['username'] ?? 'Unknown'));
                    
                    // Redirect to dashboard
                    redirect('dashboard.php');
                } else {
                    $error = "Invalid email or password.";
                }
            } else {
                $error = "Invalid email or password.";
            }
        }
    } else {
        $error = "Please fill in all fields.";
    }
}
?>




<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - EvrilMedia</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f7fa;
            color: #2d3748;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-container {
            display: flex;
            width: 100%;
            max-width: 1000px;
            background-color: white;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            min-height: 620px;
        }
        
        .login-left {
            flex: 1;
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
            color: white;
            padding: 50px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        
        .login-left::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 70%);
        }
        
        .login-logo {
            display: flex;
            align-items: center;
            margin-bottom: 35px;
            position: relative;
            z-index: 2;
        }
        
        .login-logo i {
            font-size: 44px;
            margin-right: 15px;
            color: white;
        }
        
        .login-logo h1 {
            font-size: 34px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }
        
        .login-left h2 {
            font-size: 30px;
            margin-bottom: 20px;
            font-weight: 700;
            position: relative;
            z-index: 2;
        }
        
        .login-left p {
            font-size: 17px;
            line-height: 1.7;
            opacity: 0.95;
            margin-bottom: 35px;
            position: relative;
            z-index: 2;
        }
        
        .features-list {
            list-style-type: none;
            margin-top: 25px;
            position: relative;
            z-index: 2;
        }
        
        .features-list li {
            display: flex;
            align-items: center;
            margin-bottom: 18px;
            font-size: 16px;
            font-weight: 500;
        }
        
        .features-list i {
            margin-right: 12px;
            font-size: 18px;
            width: 24px;
            text-align: center;
        }
        
        .feature-checked {
            color: #a5f3fc;
        }
        
        .feature-unchecked {
            color: rgba(255, 255, 255, 0.8);
        }
        
        .login-right {
            flex: 1;
            padding: 50px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background-color: #ffffff;
        }
        
        .login-form-container {
            width: 100%;
            max-width: 400px;
            margin: 0 auto;
        }
        
        .login-form-container h2 {
            font-size: 28px;
            margin-bottom: 30px;
            color: #1e293b;
            text-align: center;
            font-weight: 700;
        }
        
        .form-group {
            margin-bottom: 24px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: #374151;
            font-size: 15px;
        }
        
        .form-group input {
            width: 100%;
            padding: 16px 18px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
            background-color: #f9fafb;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #8b5cf6;
            box-shadow: 0 0 0 4px rgba(139, 92, 246, 0.15);
            background-color: white;
        }
        
        .btn-primary {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 18px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 15px;
            box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(139, 92, 246, 0.4);
        }
        
        .btn-primary:active {
            transform: translateY(0);
        }
        
        .error-messages {
            background: #fef2f2;
            color: #dc2626;
            padding: 16px 18px;
            border-radius: 10px;
            margin-bottom: 24px;
            border: 2px solid #fecaca;
            font-weight: 500;
            font-size: 15px;
        }
        
        .auth-links {
            text-align: center;
            margin-top: 30px;
            padding-top: 25px;
            border-top: 1px solid #f1f5f9;
        }
        
        .auth-links a {
            color: #8b5cf6;
            text-decoration: none;
            font-weight: 600;
            font-size: 15px;
            margin: 0 12px;
            transition: color 0.2s;
        }
        
        .auth-links a:hover {
            color: #7c3aed;
            text-decoration: underline;
        }
        
        .demo-accounts {
            margin-top: 30px;
            padding: 18px;
            background-color: #f8fafc;
            border-radius: 10px;
            border-left: 4px solid #8b5cf6;
        }
        
        .demo-accounts h3 {
            font-size: 16px;
            margin-bottom: 12px;
            color: #1e293b;
            font-weight: 600;
        }
        
        .demo-accounts p {
            font-size: 14px;
            color: #64748b;
            margin-bottom: 6px;
        }
        
        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
                max-width: 450px;
                border-radius: 12px;
            }
            
            .login-left {
                padding: 40px 30px;
            }
            
            .login-right {
                padding: 40px 30px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-left">
            <div class="login-logo">
                <i class="fas fa-comments"></i>
                <h1>EvrilMedia</h1>
            </div>
            
            <h2>Welcome to EvrilMedia</h2>
            <p>Connect and chat with your friends in real-time. Share your thoughts, photos, and stay connected with the people who matter most.</p>
            
            <ul class="features-list">
                <li><i class="fas fa-comment-dots feature-unchecked"></i> Real-time messaging with friends</li>
                <li><i class="fas fa-users feature-checked"></i> Connect with people around the world</li>
                <li><i class="fas fa-share-square feature-unchecked"></i> Share photos and updates</li>
                <li><i class="fas fa-bell feature-unchecked"></i> Get notified about important activities</li>
            </ul>
        </div>
        
        <div class="login-right">
            <div class="login-form-container">
                <h2>Log in to EvrilMedia</h2>
                
                <?php if (!empty($error)): ?>
                    <div class="error-messages">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" id="login-form">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" placeholder="Enter your email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" placeholder="Enter your password" required>
                    </div>
                    
                    <button type="submit" class="btn-primary">Log In</button>
                </form>
                
                <div class="auth-links">
                    <a href="register.php">Sign up for EvrilMedia</a>
                    <a href="forgot_password.php">Forgot Password?</a>
                </div>
                
                <!-- Demo accounts info -->
                <div class="demo-accounts">
                    <h3>Demo Accounts</h3>
                    <p><strong>Email:</strong> test@example.com</p>
                    <p><strong>Password:</strong> password123</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Add focus effects to form inputs
        const inputs = document.querySelectorAll('input');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'translateY(-2px)';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'translateY(0)';
            });
        });
        
        // Simple form validation
        document.getElementById('login-form').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            
            if (!email || !password) {
                e.preventDefault();
                alert('Please fill in all fields');
                return false;
            }
        });
        
        // Add subtle animation to the login container
        document.addEventListener('DOMContentLoaded', function() {
            const loginContainer = document.querySelector('.login-container');
            loginContainer.style.opacity = '0';
            loginContainer.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                loginContainer.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                loginContainer.style.opacity = '1';
                loginContainer.style.transform = 'translateY(0)';
            }, 100);
        });
    </script>
</body>
</html>