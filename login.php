<?php
session_start();
require_once 'classes/User.php';

$user = new User();

// Redirect if already logged in
if ($user->isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['login'])) {
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        
        if (empty($username) || empty($password)) {
            $error = 'Please fill in all fields';
        } else {
            $result = $user->login($username, $password);
            if ($result['success']) {
                header('Location: index.php');
                exit;
            } else {
                $error = $result['message'];
            }
        }
    } elseif (isset($_POST['register'])) {
        $username = trim($_POST['reg_username']);
        $email = trim($_POST['reg_email']);
        $password = $_POST['reg_password'];
        $confirmPassword = $_POST['reg_confirm_password'];
        
        if (empty($username) || empty($email) || empty($password) || empty($confirmPassword)) {
            $error = 'Please fill in all fields';
        } elseif ($password !== $confirmPassword) {
            $error = 'Passwords do not match';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters long';
        } else {
            $result = $user->register($username, $email, $password);
            if ($result['success']) {
                $success = 'Registration successful! You can now login.';
            } else {
                $error = $result['message'];
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CloudeDrive - Professional Login</title>
    <meta name="description" content="Sign in to your CloudeDrive account - Secure file management">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2563eb;
            --primary-hover: #1d4ed8;
            --bg-primary: #0f172a;
            --bg-secondary: #1e293b;
            --bg-tertiary: #334155;
            --text-primary: #f8fafc;
            --text-secondary: #cbd5e1;
            --text-muted: #94a3b8;
            --border-color: #374151;
            --danger-color: #ef4444;
            --success-color: #10b981;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            border-radius: 0 !important;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #1e293b 0%, #334155 50%, #475569 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-primary);
        }
        
        .login-container {
            background: var(--bg-primary);
            border: 1px solid var(--border-color);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 400px;
            padding: 2rem;
            position: relative;
            overflow: hidden;
        }
        
        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), #06b6d4);
        }
        
        .brand-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .brand-logo {
            color: var(--primary-color);
            font-size: 3rem;
            margin-bottom: 0.5rem;
            display: block;
        }
        
        .brand-title {
            color: var(--text-primary);
            font-weight: 700;
            font-size: 1.75rem;
            margin-bottom: 0.25rem;
        }
        
        .brand-subtitle {
            color: var(--text-secondary);
            font-size: 0.875rem;
        }
        
        .auth-tabs {
            display: flex;
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            margin-bottom: 2rem;
            overflow: hidden;
        }
        
        .auth-tab {
            flex: 1;
            padding: 14px 20px;
            background: transparent;
            border: none;
            color: var(--text-secondary);
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            border-right: 1px solid var(--border-color);
            font-size: 0.9rem;
        }
        
        .auth-tab:last-child {
            border-right: none;
        }
        
        .auth-tab.active {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
        }
        
        .auth-tab:hover:not(.active) {
            background: var(--bg-tertiary);
            color: var(--text-primary);
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            color: var(--text-primary);
            font-weight: 500;
            margin-bottom: 0.5rem;
            display: block;
            font-size: 0.875rem;
        }
        
        .input-container {
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            z-index: 2;
        }
        
        .form-input {
            width: 100%;
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            padding: 14px 16px 14px 48px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            outline: none;
        }
        
        .form-input:focus {
            background: var(--bg-tertiary);
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
            transform: translateY(-1px);
        }
        
        .form-input::placeholder {
            color: var(--text-muted);
        }
        
        .btn-login {
            width: 100%;
            padding: 16px;
            font-weight: 600;
            font-size: 1rem;
            border: none;
            background: var(--primary-color);
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 1rem;
        }
        
        .btn-login:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.3);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .alert {
            padding: 12px 16px;
            margin-bottom: 1.5rem;
            border-left: 4px solid;
            font-size: 0.875rem;
        }
        
        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            border-color: var(--danger-color);
            color: #fca5a5;
        }
        
        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            border-color: var(--success-color);
            color: #86efac;
        }
        
        .tab-content {
            position: relative;
        }
        
        .tab-pane {
            display: none;
            animation: fadeIn 0.3s ease;
        }
        
        .tab-pane.active {
            display: block;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .login-footer {
            text-align: center;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border-color);
            color: var(--text-muted);
            font-size: 0.8rem;
        }
        
        .admin-credentials {
            background: var(--bg-secondary);
            padding: 12px;
            margin-bottom: 1rem;
            border: 1px solid var(--border-color);
        }
        
        .admin-credentials strong {
            color: var(--text-primary);
        }
        
        code {
            background: var(--bg-tertiary);
            color: var(--primary-color);
            padding: 2px 6px;
            font-family: 'Courier New', monospace;
            font-size: 0.85rem;
        }
        
        .floating-shapes {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
        }
        
        .shape {
            position: absolute;
            border: 2px solid rgba(37, 99, 235, 0.1);
            animation: float 8s ease-in-out infinite;
        }
        
        .shape-1 {
            width: 150px;
            height: 150px;
            top: 10%;
            left: 10%;
            animation-delay: 0s;
        }
        
        .shape-2 {
            width: 100px;
            height: 100px;
            top: 70%;
            right: 15%;
            border-radius: 50%;
            animation-delay: 2s;
        }
        
        .shape-3 {
            width: 120px;
            height: 120px;
            bottom: 20%;
            left: 20%;
            transform: rotate(45deg);
            animation-delay: 4s;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            33% { transform: translateY(-15px) rotate(5deg); }
            66% { transform: translateY(10px) rotate(-5deg); }
        }
        
        @media (max-width: 480px) {
            .login-container {
                margin: 1rem;
                padding: 1.5rem;
            }
            
            .brand-logo {
                font-size: 2.5rem;
            }
            
            .brand-title {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body class="d-flex align-items-center min-vh-100">
    <!-- Floating Geometric Shapes -->
    <div class="floating-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
    </div>
    
    <div class="login-container">
        <!-- Brand Header -->
        <div class="brand-header">
            <i class="fas fa-cloud brand-logo"></i>
            <h1 class="brand-title">CloudeDrive</h1>
            <p class="brand-subtitle">Professional File Management Platform</p>
        </div>

        <!-- Error/Success Messages -->
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle" style="margin-right: 8px;"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle" style="margin-right: 8px;"></i>
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <!-- Auth Tabs -->
        <div class="auth-tabs">
            <button class="auth-tab active" onclick="switchTab('login')" id="loginTab">
                <i class="fas fa-sign-in-alt" style="margin-right: 8px;"></i>
                Login
            </button>
            <button class="auth-tab" onclick="switchTab('register')" id="registerTab">
                <i class="fas fa-user-plus" style="margin-right: 8px;"></i>
                Register
            </button>
        </div>

        <!-- Tab Content -->
        <div class="tab-content">
            <!-- Login Form -->
            <div class="tab-pane active" id="loginPane">
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">Username or Email</label>
                        <div class="input-container">
                            <i class="fas fa-user input-icon"></i>
                            <input type="text" class="form-input" name="username" placeholder="Enter your username or email" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <div class="input-container">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" class="form-input" name="password" placeholder="Enter your password" required>
                        </div>
                    </div>
                    
                    <button type="submit" name="login" class="btn-login">
                        <i class="fas fa-sign-in-alt" style="margin-right: 8px;"></i>
                        Sign In
                    </button>
                </form>
            </div>

            <!-- Register Form -->
            <div class="tab-pane" id="registerPane">
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">Username</label>
                        <div class="input-container">
                            <i class="fas fa-user input-icon"></i>
                            <input type="text" class="form-input" name="reg_username" placeholder="Choose a username" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <div class="input-container">
                            <i class="fas fa-envelope input-icon"></i>
                            <input type="email" class="form-input" name="reg_email" placeholder="Enter your email address" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <div class="input-container">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" class="form-input" name="reg_password" placeholder="Create a password" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Confirm Password</label>
                        <div class="input-container">
                            <i class="fas fa-check input-icon"></i>
                            <input type="password" class="form-input" name="reg_confirm_password" placeholder="Confirm your password" required>
                        </div>
                    </div>
                    
                    <button type="submit" name="register" class="btn-login">
                        <i class="fas fa-user-plus" style="margin-right: 8px;"></i>
                        Create Account
                    </button>
                </form>
            </div>
        </div>

        <!-- Footer -->
        <div class="login-footer">
            <div class="admin-credentials">
                <strong>Default Admin Access:</strong><br>
                Username: <code>admin</code> | Password: <code>password</code>
            </div>
            <div style="color: #64748b;">
                © 2025 CloudeDrive. Professional File Management.
            </div>
        </div>
    </div>

    <script>
        function switchTab(tab) {
            // Update tab buttons
            document.getElementById('loginTab').classList.remove('active');
            document.getElementById('registerTab').classList.remove('active');
            document.getElementById(tab + 'Tab').classList.add('active');
            
            // Update tab panes with animation
            document.getElementById('loginPane').classList.remove('active');
            document.getElementById('registerPane').classList.remove('active');
            
            setTimeout(() => {
                document.getElementById(tab + 'Pane').classList.add('active');
            }, 150);
        }

        // Add form animation effects
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('.form-input');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.style.transform = 'scale(1.02)';
                });
                
                input.addEventListener('blur', function() {
                    this.parentElement.style.transform = 'scale(1)';
                });
            });
        });
    </script>
</body>
</html>
