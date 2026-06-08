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

$pageTitle = 'Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> | DHLTU SRC</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;0,700;1,300;1,400&family=Outfit:wght@200;300;400;500;600;700&family=Space+Mono:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
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
                <span class="sidebar-title">DHLTU Dashboard</span>
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
                    <div class="user-role"><span class="role-badge <?php echo $currentRole === 'PRO' ? 'admin' : ($currentRole === 'STUDENT' ? 'student' : 'monitor'); ?>"><?php echo $currentRole; ?></span></div>
                </div>
            </div>
        </aside>

        <div class="main-content">
            <header class="dashboard-header">
                <button class="mobile-toggle" id="mobileToggle" aria-label="Open menu">
                    <i class="bi bi-list"></i>
                </button>
                <h1 class="header-title">Dashboard Overview</h1>
                <div class="header-actions">
                    <a href="logout.php" class="header-btn" aria-label="Logout">
                        <i class="bi bi-box-arrow-right"></i>
                    </a>
                </div>
            </header>

            <main class="content-body">
                <div class="dashboard-container">
                    <div class="dashboard-header-section">
                        <h2 class="dashboard-title">Welcome back, <?php echo htmlspecialchars($currentUser['first_name']); ?>!</h2>
                        <p class="dashboard-subtitle">Here's an overview of the system</p>
                    </div>

                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-card-header">
                                <span class="stat-card-label">Welcome</span>
                                <div class="stat-card-icon primary">
                                    <i class="bi bi-person"></i>
                                </div>
                            </div>
                            <div class="stat-card-value"><?php echo htmlspecialchars($currentUser['first_name']); ?></div>
                            <div class="user-email"><?php echo $currentRole; ?> Portal</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-card-header">
                                <span class="stat-card-label">System Status</span>
                                <div class="stat-card-icon success">
                                    <i class="bi bi-check-circle"></i>
                                </div>
                            </div>
                            <div class="stat-card-value">Active</div>
                            <div class="stat-card-label">All systems operational</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-card-header">
                                <span class="stat-card-label">Academic Year</span>
                                <div class="stat-card-icon info">
                                    <i class="bi bi-calendar"></i>
                                </div>
                            </div>
                            <div class="stat-card-value"><?php echo date('Y'); ?>/<?php echo date('Y') + 1; ?></div>
                            <div class="stat-card-label">Current session</div>
                        </div>
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