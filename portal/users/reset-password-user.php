<?php
session_start();
require_once '../../config/database.php';
require_once '../../config/functions.php';

if (!isLogged()) {
    header('Location: ../login.php');
    exit;
}

$currentRole = currentRole();
if ($currentRole !== 'PRO') {
    header('Location: ../index.php');
    exit;
}

$userId = $_GET['id'] ?? null;
if (!$userId) {
    header('Location: index.php');
    exit;
}

$user = db()->fetch("
    SELECT u.*, r.name as role
    FROM users u
    JOIN roles r ON u.role_id = r.id
    WHERE u.id = ?
", [$userId]);

if (!$user) {
    $_SESSION['errors'] = 'User not found';
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPassword = trim($_POST['new_password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');

    if (empty($newPassword) || empty($confirmPassword)) {
        $error = 'Please fill in both password fields.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } elseif (strlen($newPassword) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } else {
        $hashed = hashPassword($newPassword);
        db()->execute("UPDATE users SET password_hash = ? WHERE id = ?", [$hashed, $userId]);
        logActivity('reset_user_password', $_SESSION['user_id'], ['user_id' => $userId, 'email' => $user['email']]);
        $success = 'Password reset successfully. You may inform the user of their new password.';
    }
}

$pageTitle = 'Reset User Password';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> | DHLTU SRC</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;0,700;1,300,400&family=Outfit:wght@200;300;400;500;600;700&family=Space+Mono:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <link rel="stylesheet" href="../../assets/css/alerts.css">

    <script>
        window.currentUserRole = '<?php echo $currentRole; ?>';
    </script>
<link rel="icon" type="image/png" href="../../assets/images/logo.png">
</head>
<body>
    <div class="dashboard-layout">
        <div class="mobile-overlay" id="mobileOverlay"></div>

        <aside class="sidebar" id="sidebar">
            <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
                <i class="bi bi-chevron-left"></i>
            </button>

            <div class="sidebar-header">
                <div class="sidebar-logo">SRC</div>
                <span class="sidebar-title">DHLTU Dashboard</span>
            </div>

            <?php
            require_once '../../include/nav-links.php';
            $nav = new NavigationRBAC($currentRole);
            echo $nav->renderNavigation();
            ?>

            <div class="sidebar-user">
                <div class="user-avatar"><?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?></div>
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></div>
                    <div class="user-role"><span class="role-badge admin"><?php echo $user['role']; ?></span></div>
                </div>
            </div>
        </aside>

        <div class="main-content">
            <header class="dashboard-header">
                <button class="mobile-toggle" id="mobileToggle" aria-label="Open menu">
                    <i class="bi bi-list"></i>
                </button>
                <h1 class="header-title">Reset Password</h1>
                <div class="header-actions">
                    <a href="edit.php?id=<?php echo $userId; ?>" class="header-btn" title="Back to Edit">
                        <i class="bi bi-arrow-left"></i>
                    </a>
                    <a href="../logout.php" class="header-btn" aria-label="Logout">
                        <i class="bi bi-box-arrow-right"></i>
                    </a>
                </div>
            </header>

            <main class="content-body">
                <div class="dashboard-container">
                    <div class="dashboard-header-section">
                        <h2 class="dashboard-title">Reset Password for <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h2>
                        <p class="dashboard-subtitle">Set a new password for this user's account</p>
                    </div>

                    <div class="user-info-card" style="background:rgba(255,255,255,0.03);border:1px solid rgba(201,168,76,0.15);border-radius:12px;padding:20px 24px;margin-bottom:24px;display:flex;align-items:center;gap:16px;">
                        <div class="user-avatar" style="width:48px;height:48px;font-size:18px;display:flex;align-items:center;justify-content:center;"><?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?></div>
                        <div>
                            <div style="font-weight:600;color:var(--cream);font-size:16px;"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></div>
                            <div style="color:var(--text-muted);font-size:13px;"><?php echo htmlspecialchars($user['email']); ?> &nbsp;|&nbsp; <?php echo $user['role']; ?></div>
                        </div>
                    </div>

                    <?php if ($error): ?>
                    <div class="alert alert-danger" style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);color:#ef4444;padding:12px 16px;border-radius:8px;margin-bottom:20px;"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                    <div class="alert alert-success" style="background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.3);color:#22c55e;padding:12px 16px;border-radius:8px;margin-bottom:20px;"><?php echo htmlspecialchars($success); ?></div>
                    <?php endif; ?>

                    <div class="table-container" style="max-width:600px;">
                        <form method="POST" action="" style="padding:24px;">
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">New Password *</label>
                                    <input type="password" name="new_password" class="form-input" placeholder="Enter new password" required minlength="8">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Confirm Password *</label>
                                    <input type="password" name="confirm_password" class="form-input" placeholder="Confirm new password" required minlength="8">
                                </div>
                            </div>

                            <div class="modal-footer" style="margin:0;padding:0;justify-content:flex-start;margin-top:24px;">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-key"></i>
                                    Reset Password
                                </button>
                                <a href="edit.php?id=<?php echo $userId; ?>" class="btn btn-secondary">
                                    Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="../../assets/js/sidebar.js"></script>
    <?php if (function_exists('alert')): ?>
    <?php echo alert()->render(); ?>
    <?php endif; ?>
</body>
</html>
