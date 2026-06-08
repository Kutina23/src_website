<?php
session_start();
require_once '../config/database.php';
require_once '../config/functions.php';

if (!isLogged()) {
    header('Location: login.php');
    exit;
}

$currentRole = currentRole();
$currentUser = currentUser();

if ($currentRole !== 'PRO') {
    header('Location: index.php');
    exit;
}

$pageTitle = 'Settings';
$success = $_SESSION['success'] ?? null;
$errors = $_SESSION['errors'] ?? [];
unset($_SESSION['success'], $_SESSION['errors']);

// Get current settings
$settings = db()->fetchAll("SELECT * FROM site_settings", []);
$settingsMap = [];
foreach ($settings as $setting) {
    $settingsMap[$setting['col_key']] = $setting['col_value'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $siteName = sanitize($_POST['site_name'] ?? '');
    $siteEmail = sanitize($_POST['site_email'] ?? '');
    $academicYear = sanitize($_POST['academic_year'] ?? '');
    
    if ($siteName && $siteEmail) {
        db()->execute("INSERT INTO site_settings (col_key, col_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE col_value = VALUES(col_value)", ['site_name', $siteName]);
        db()->execute("INSERT INTO site_settings (col_key, col_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE col_value = VALUES(col_value)", ['site_email', $siteEmail]);
        db()->execute("INSERT INTO site_settings (col_key, col_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE col_value = VALUES(col_value)", ['academic_year', $academicYear]);
        
        logActivity('update_settings', $_SESSION['user_id'], []);
        $_SESSION['success'] = 'Settings updated successfully';
        header('Location: settings.php');
        exit;
    } else {
        $_SESSION['errors'] = ['Please fill in all required fields'];
        header('Location: settings.php');
        exit;
    }
}
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
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    
    <script>
        window.currentUserRole = '<?php echo $currentRole; ?>';
    </script>
<link rel="icon" type="image/png" href="../assets/images/logo.png">
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
                <span class="sidebar-title">HLTU Dashboard</span>
            </div>

            <?php
            require_once '../include/nav-links.php';
            $nav = new NavigationRBAC($currentRole);
            echo $nav->renderNavigation();
            ?>

            <div class="sidebar-user">
                <div class="user-avatar"><?php echo strtoupper(substr($currentUser['first_name'], 0, 1) . substr($currentUser['last_name'], 0, 1)); ?></div>
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']); ?></div>
                    <div class="user-role"><span class="role-badge admin"><?php echo $currentRole; ?></span></div>
                </div>
            </div>
        </aside>

        <div class="main-content">
            <header class="dashboard-header">
                <button class="mobile-toggle" id="mobileToggle" aria-label="Open menu">
                    <i class="bi bi-list"></i>
                </button>
                <h1 class="header-title">Settings</h1>
                <div class="header-actions">
                    <a href="../logout.php" class="header-btn" aria-label="Logout">
                        <i class="bi bi-box-arrow-right"></i>
                    </a>
                </div>
            </header>

            <main class="content-body">
                <div class="dashboard-container">
                    <div class="dashboard-header-section">
                        <h2 class="dashboard-title">System Settings</h2>
                        <p class="dashboard-subtitle">Configure application preferences</p>
                    </div>

                    <?php if ($success): ?>
                        <div style="background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.2);border-radius:8px;padding:16px;margin-bottom:20px;color:#22c55e;">
                            <?php echo $success; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($errors)): ?>
                        <div style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.2);border-radius:8px;padding:16px;margin-bottom:20px;color:#ef4444;">
                            <ul style="margin:0;padding-left:20px;">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <div class="table-container" style="max-width:600px;">
                        <form method="POST" style="padding:24px;">
                            <div class="form-group">
                                <label class="form-label">Site Name</label>
                                <input type="text" name="site_name" class="form-input" value="<?php echo htmlspecialchars($settingsMap['site_name'] ?? 'DHLTU SRC'); ?>">
                            </div>

                            <div class="form-group">
                                <label class="form-label">Site Email</label>
                                <input type="email" name="site_email" class="form-input" value="<?php echo htmlspecialchars($settingsMap['site_email'] ?? 'info@srcltu.edu.gh'); ?>">
                            </div>

                            <div class="form-group">
                                <label class="form-label">Academic Year</label>
                                <input type="text" name="academic_year" class="form-input" placeholder="2024/2025" value="<?php echo htmlspecialchars($settingsMap['academic_year'] ?? (date('Y') . '/' . (date('Y') + 1))); ?>">
                            </div>

                            <div class="modal-footer" style="margin:0;padding:0;justify-content:flex-start;margin-top:24px;">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check"></i>
                                    Save Settings
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="../assets/js/sidebar.js"></script>
    <script src="../assets/js/loader-service.js"></script>
    <?php if (function_exists('alert')): ?>
    <?php echo alert()->render(); ?>
    <?php endif; ?>
</body>
</html>