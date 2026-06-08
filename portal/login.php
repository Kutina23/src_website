<?php
session_start();
require_once '../config/database.php';
require_once '../config/functions.php';

if ($_POST) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $_SESSION['login_error'] = 'Please enter both email and password.';
    } else {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT u.id, u.email, u.password_hash as password, u.first_name, u.last_name, r.name as role FROM users u JOIN roles r ON u.role_id = r.id WHERE u.email = ? AND u.is_active = TRUE LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name'] = $user['last_name'];
                $_SESSION['role'] = $user['role'];
                
                $redirect = match($user['role']) {
                    'PRO', 'PRESIDENT', 'DIRECTOR ICT', 'DEAN' => 'index.php',
                    default => 'index.php'
                };
                header("Location: $redirect");
                exit;
            } else {
                $_SESSION['login_error'] = 'Invalid email or password.';
            }
        } catch (Exception $e) {
            $_SESSION['login_error'] = 'Login failed. Please try again.';
        }
    }
    header('Location: login.php');
    exit;
}

$error = $_SESSION['login_error'] ?? '';
unset($_SESSION['login_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SRC Portal Login | DHLTU Student Representative Council</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;0,700;1,300;1,400&family=Outfit:wght@200;300;400;500;600;700&family=Space+Mono:ital@0,400;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="icon" type="image/png" href="../assets/images/logo.png">
    
    <style>
        :root {
            --gold: #C9A84C;
            --gold-light: #E8C97A;
            --gold-dark: #8B6914;
            --navy: #0A1628;
            --navy-mid: #0F2040;
            --navy-light: #1A3060;
            --cream: #F5F0E8;
            --cream-dark: #E8E0CC;
            --white: #FFFFFF;
            --text-muted: #8A9BB8;
            --success: #22c55e;
            --error: #ef4444;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Outfit', sans-serif;
            background: linear-gradient(135deg, var(--navy) 0%, var(--navy-mid) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--cream);
        }
        
        .auth-container {
            display: flex;
            max-width: 900px;
            width: 100%;
            background: rgba(15, 32, 64, 0.8);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(201, 168, 76, 0.15);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 24px 60px rgba(0, 0, 0, 0.4);
        }
        
        .auth-brand {
            flex: 1;
            background: linear-gradient(135deg, var(--gold-dark) 0%, var(--gold) 50%, var(--gold-light) 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 60px 40px;
            position: relative;
            overflow: hidden;
        }
        
        .auth-brand::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at 30% 30%, rgba(255,255,255,0.2), transparent 60%);
        }
        
        .logo-wrapper img {
            width: 160px;
            height: auto;
            position: relative;
            z-index: 1;
        }
        
        .brand-title {
            font-family: 'Cormorant Garamond', serif;
            font-size: 28px;
            font-weight: 600;
            color: var(--navy);
            margin-bottom: 8px;
            position: relative;
            z-index: 1;
        }
        
        .brand-subtitle {
            font-size: 14px;
            color: rgba(10, 22, 40, 0.7);
            letter-spacing: 0.15em;
            text-transform: uppercase;
            position: relative;
            z-index: 1;
        }
        
        .auth-form {
            flex: 1;
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
        }
        
        .auth-header {
            margin-bottom: 40px;
        }
        
        .auth-title {
            font-family: 'Cormorant Garamond', serif;
            font-size: 32px;
            font-weight: 600;
            color: var(--cream);
            margin-bottom: 8px;
        }
        
        .auth-subtitle {
            font-size: 14px;
            color: var(--text-muted);
        }
        
        .form-group {
            margin-bottom: 24px;
        }
        
        .form-label {
            display: block;
            font-size: 12px;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--text-muted);
            margin-bottom: 8px;
        }
        
        .form-input {
            width: 100%;
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(201,168,76,0.2);
            color: var(--cream);
            padding: 14px 16px;
            font-family: 'Outfit', sans-serif;
            font-size: 15px;
            outline: none;
            transition: border-color 0.3s;
        }
        
        .form-input:focus {
            border-color: var(--gold);
            background: rgba(201,168,76,0.05);
        }
        
        .password-wrapper {
            position: relative;
        }
        
        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            font-size: 18px;
            transition: color 0.3s;
        }
        
        .toggle-password:hover {
            color: var(--gold);
        }
        
        .remember-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
        }
        
        .checkbox-wrapper {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .checkbox-wrapper input {
            width: 16px;
            height: 16px;
            accent-color: var(--gold);
        }
        
        .checkbox-label {
            font-size: 13px;
            color: var(--text-muted);
        }
        
        .forgot-link {
            font-size: 13px;
            color: var(--gold);
            text-decoration: none;
        }
        
        .forgot-link:hover {
            text-decoration: underline;
        }
        
        .btn-login {
            width: 100%;
            background: linear-gradient(135deg, var(--gold-light), var(--gold));
            color: var(--navy);
            border: none;
            padding: 16px;
            font-size: 14px;
            font-weight: 600;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.3s;
            font-family: 'Outfit', sans-serif;
        }
        
        .btn-login:hover {
            box-shadow: 0 0 20px rgba(201,168,76,0.4);
            transform: translateY(-2px);
        }
        
        .error-message {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: var(--error);
            padding: 12px 16px;
            font-size: 13px;
            margin-bottom: 24px;
        }
        
        .success-message {
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.3);
            color: var(--success);
            padding: 12px 16px;
            font-size: 13px;
            margin-bottom: 24px;
        }
        
        @media (max-width: 768px) {
            .auth-container {
                flex-direction: column;
                margin: 20px;
            }
            
            .auth-brand {
                padding: 40px 20px;
            }
            
            .auth-form {
                padding: 40px 30px;
            }
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-brand">
            <div class="logo-wrapper">
                <img src="../assets/images/logo.png" alt="SRC Logo">
            </div>
            <div class="brand-title">DHLTU SRC</div>
            <div class="brand-subtitle">Student Representative Council</div>
        </div>
        
        <div class="auth-form">
            <div class="auth-header">
                <h1 class="auth-title">Welcome Back</h1>
                <p class="auth-subtitle">Sign in to access your portal</p>
            </div>
            
            <?php if ($error): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label" for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-input" placeholder="you@hltu.edu.gh" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password" class="form-input" placeholder="Enter your password" required>
                        <button type="button" class="toggle-password" id="togglePassword" aria-label="Toggle password visibility">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="remember-row">
                    <div class="checkbox-wrapper">
                        <input type="checkbox" id="remember" name="remember">
                        <label class="checkbox-label" for="remember">Remember me</label>
                    </div>
                    <a href="forgot-password.php" class="forgot-link">Forgot password?</a>
                </div>
                
                <button type="submit" class="btn-login">Sign In</button>
            </form>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const togglePassword = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');
            
            togglePassword.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                
                const icon = this.querySelector('i');
                icon.classList.toggle('bi-eye');
                icon.classList.toggle('bi-eye-slash');
            });
        });
    </script>
</body>
</html>