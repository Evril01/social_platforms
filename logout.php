<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logout - EvrilMedia</title>
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
        
        .logout-container {
            display: flex;
            width: 100%;
            max-width: 1000px;
            background-color: white;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            min-height: 620px;
        }
        
        .logout-left {
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
        
        .logout-left::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 70%);
        }
        
        .logout-logo {
            display: flex;
            align-items: center;
            margin-bottom: 35px;
            position: relative;
            z-index: 2;
        }
        
        .logout-logo i {
            font-size: 44px;
            margin-right: 15px;
            color: white;
        }
        
        .logout-logo h1 {
            font-size: 34px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }
        
        .logout-left h2 {
            font-size: 30px;
            margin-bottom: 20px;
            font-weight: 700;
            position: relative;
            z-index: 2;
        }
        
        .logout-left p {
            font-size: 17px;
            line-height: 1.7;
            opacity: 0.95;
            margin-bottom: 35px;
            position: relative;
            z-index: 2;
        }
        
        .user-info {
            background: rgba(255, 255, 255, 0.15);
            border-radius: 12px;
            padding: 20px;
            margin-top: 25px;
            position: relative;
            z-index: 2;
            backdrop-filter: blur(10px);
        }
        
        .user-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #a78bfa 0%, #8b5cf6 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            font-size: 24px;
            color: white;
            font-weight: bold;
        }
        
        .user-name {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .user-email {
            font-size: 15px;
            opacity: 0.9;
        }
        
        .logout-right {
            flex: 1;
            padding: 50px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background-color: #ffffff;
        }
        
        .logout-form-container {
            width: 100%;
            max-width: 400px;
            margin: 0 auto;
            text-align: center;
        }
        
        .logout-icon {
            font-size: 80px;
            color: #8b5cf6;
            margin-bottom: 25px;
        }
        
        .logout-form-container h2 {
            font-size: 28px;
            margin-bottom: 15px;
            color: #1e293b;
            font-weight: 700;
        }
        
        .logout-message {
            font-size: 17px;
            color: #64748b;
            margin-bottom: 35px;
            line-height: 1.6;
        }
        
        .btn-logout {
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
            margin-bottom: 20px;
            box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);
        }
        
        .btn-logout:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(139, 92, 246, 0.4);
        }
        
        .btn-logout:active {
            transform: translateY(0);
        }
        
        .btn-cancel {
            width: 100%;
            padding: 16px;
            background: transparent;
            color: #64748b;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-cancel:hover {
            background: #f8fafc;
            border-color: #8b5cf6;
            color: #8b5cf6;
        }
        
        .session-info {
            margin-top: 30px;
            padding: 18px;
            background-color: #f8fafc;
            border-radius: 10px;
            border-left: 4px solid #8b5cf6;
            text-align: left;
        }
        
        .session-info h3 {
            font-size: 16px;
            margin-bottom: 12px;
            color: #1e293b;
            font-weight: 600;
        }
        
        .session-info p {
            font-size: 14px;
            color: #64748b;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
        }
        
        .session-info i {
            margin-right: 8px;
            color: #8b5cf6;
            width: 16px;
        }
        
        @media (max-width: 768px) {
            .logout-container {
                flex-direction: column;
                max-width: 450px;
                border-radius: 12px;
            }
            
            .logout-left {
                padding: 40px 30px;
            }
            
            .logout-right {
                padding: 40px 30px;
            }
        }
    </style>
</head>
<body>
    <div class="logout-container">
        <div class="logout-left">
            <div class="logout-logo">
                <i class="fas fa-comments"></i>
                <h1>EvrilMedia</h1>
            </div>
            
            <h2>Thanks for using EvrilMedia</h2>
            <p>We're sad to see you go, but we understand. Remember, your connections and conversations will be waiting for you when you return.</p>
            
            <div class="user-info">
                <div class="user-avatar">ME</div>
                <div class="user-name">Murendeni Evril</div>
                <div class="user-email">murendeni@evrilmedia.com</div>
            </div>
        </div>
        
        <div class="logout-right">
            <div class="logout-form-container">
                <div class="logout-icon">
                    <i class="fas fa-sign-out-alt"></i>
                </div>
                
                <h2>Log out of EvrilMedia</h2>
                <p class="logout-message">Are you sure you want to log out? You'll need to sign in again to access your account.</p>
                
                <form method="POST" id="logout-form">
                    <button type="submit" class="btn-logout">Yes, Log Me Out</button>
                    <button type="button" class="btn-cancel" id="cancel-btn">Cancel</button>
                </form>
                
                <div class="session-info">
                    <h3>Current Session</h3>
                    <p><i class="fas fa-clock"></i> Logged in: 2 hours ago</p>
                    <p><i class="fas fa-laptop"></i> Device: Chrome on Windows</p>
                    <p><i class="fas fa-map-marker-alt"></i> Location: Johannesburg, ZA</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Add animation to the logout container
        document.addEventListener('DOMContentLoaded', function() {
            const logoutContainer = document.querySelector('.logout-container');
            logoutContainer.style.opacity = '0';
            logoutContainer.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                logoutContainer.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                logoutContainer.style.opacity = '1';
                logoutContainer.style.transform = 'translateY(0)';
            }, 100);
        });
        
        // Handle logout form submission
        document.getElementById('logout-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Show loading state
            const logoutBtn = document.querySelector('.btn-logout');
            const originalText = logoutBtn.textContent;
            logoutBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Logging out...';
            logoutBtn.disabled = true;
            
            // Simulate logout process
            setTimeout(() => {
                alert('You have been successfully logged out. Redirecting to login page...');
                // In a real application, you would redirect to login page
                window.location.href = 'login.php';
            }, 1500);
        });
        
        // Handle cancel button
        document.getElementById('cancel-btn').addEventListener('click', function() {
            // In a real application, you would redirect back to dashboard
            alert('Returning to dashboard...');
            window.location.href = 'dashboard.php';
        });
        
        // Add hover effect to user info card
        const userInfo = document.querySelector('.user-info');
        userInfo.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
            this.style.transition = 'transform 0.3s ease';
        });
        
        userInfo.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    </script>
</body>
</html>