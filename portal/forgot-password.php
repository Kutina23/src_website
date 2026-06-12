<?php
session_start();
require_once '../config/database.php';
require_once '../config/functions.php';

if ($_POST) {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $_SESSION['forgot_error'] = 'Please enter your email address.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['forgot_error'] = 'Please enter a valid email address.';
    } else {
        try {
            $db = Database::getInstance()->getConnection();
            
            // Check if email exists
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND is_active = TRUE LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Generate reset token
                $token = bin2hex(random_bytes(32));
                $tokenHash = hash('sha256', $token);
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                // Store token hash in database
                $updateStmt = $db->prepare("UPDATE users SET remember_token = ?, remember_token_expires = ? WHERE id = ?");
                $updateStmt->execute([$tokenHash, $expires, $user['id']]);
                
                // Send email with reset link
                $mailConfig = getMailConfig();
                $appUrl = getAppUrl();
                $resetLink = $appUrl . "/portal/reset-password.php?token=" . urlencode($token);
                
                $subject = "Password Reset Request - DHLTU SRC Portal";
                $body = "
                <html>
                <body>
                    <h2>Password Reset Request</h2>
                    <p>You have requested to reset your password for the DHLTU SRC Portal.</p>
                    <p>Click the link below to reset your password (this link will expire in 1 hour):</p>
                    <p><a href=\"{$resetLink}\">{$resetLink}</a></p>
                    <p>If you did not request this, please ignore this email.</p>
                    <p>This is an automated message, please do not reply.</p>
                </body>
                </html>
                ";
                
                $headers = [
                    'From' => $mailConfig['from_address'],
                    'Content-Type' => 'text/html; charset=UTF-8',
                    'Reply-To' => $mailConfig['reply_to']
                ];
                
                sendEmail($email, $subject, $body, $headers);
                
                $_SESSION['forgot_success'] = 'If your email is registered, you will receive a password reset link shortly.';
            } else {
                // Don't reveal that email doesn't exist for security
                $_SESSION['forgot_success'] = 'If your email is registered, you will receive a password reset link shortly.';
            }
        } catch (Exception $e) {
            $_SESSION['forgot_error'] = 'An error occurred. Please try again.';
        }
    }
    
    header('Location: forgot-password.php');
    exit;
}

$error = $_SESSION['forgot_error'] ?? '';
$success = $_SESSION['forgot_success'] ?? '';
unset($_SESSION['forgot_error'], $_SESSION['forgot_success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | DHLTU Student Representative Council</title>
    
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
        
        .btn-submit {
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
        
        .btn-submit:hover {
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
        
        .back-link {
            display: block;
            text-align: center;
            margin-top: 24px;
            color: var(--gold);
            text-decoration: none;
            font-size: 13px;
        }
        
        .back-link:hover {
            text-decoration: underline;
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
                <h1 class="auth-title">Forgot Password</h1>
                <p class="auth-subtitle">Enter your email to reset your password</p>
            </div>
            
            <?php if ($error): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
            <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label" for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-input" placeholder="you@hltu.edu.gh" required>
                </div>
                
                <button type="submit" class="btn-submit">Send Reset Link</button>
            </form>
            
            <a href="login.php" class="back-link">← Back to Login</a>
        </div>
    </div>
</body>
</html>