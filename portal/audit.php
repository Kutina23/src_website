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

$pageTitle = 'Audit Logs';

// Get audit logs with pagination
$page = $_GET['page'] ?? 1;
$logs = db()->fetchAll("
    SELECT a.*, u.first_name, u.last_name, u.email
    FROM audit_logs a
    LEFT JOIN users u ON a.user_id = u.id
    ORDER BY a.created_at DESC
    LIMIT 50
", []);
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
                    <div class="user-role"><span class="role-badge admin"><?php echo $currentRole; ?></span></div>
                </div>
            </div>
        </aside>

        <div class="main-content">
            <header class="dashboard-header">
                <button class="mobile-toggle" id="mobileToggle" aria-label="Open menu">
                    <i class="bi bi-list"></i>
                </button>
                <h1 class="header-title">Audit Logs</h1>
                <div class="header-actions">
                    <a href="../logout.php" class="header-btn" aria-label="Logout">
                        <i class="bi bi-box-arrow-right"></i>
                    </a>
                </div>
            </header>

            <main class="content-body">
                <div class="dashboard-container">
                    <div class="dashboard-header-section">
                        <h2 class="dashboard-title">System Audit Logs</h2>
                        <p class="dashboard-subtitle">Track all system activities and changes</p>
                    </div>

                    <div class="table-container">
                        <?php if (empty($logs)): ?>
                            <div class="empty-state">
                                <div class="empty-icon">
                                    <i class="bi bi-shield-check"></i>
                                </div>
                                <h3 class="empty-title">No audit logs found</h3>
                                <p class="empty-text">System activities will be recorded here</p>
                            </div>
                        <?php else: ?>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>User</th>
                                        <th>Action</th>
                                        <th>Details</th>
                                        <th>IP Address</th>
                                        <th>Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($logs as $index => $log): ?>
                                        <tr>
                                            <td><?php echo $index + 1; ?></td>
                                            <td>
                                                <?php if ($log['first_name']): ?>
                                                    <?php echo htmlspecialchars($log['first_name'] . ' ' . $log['last_name']); ?>
                                                <?php else: ?>
                                                    <span style="color:var(--dashboard-text-muted);">System</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><span class="badge badge-role"><?php echo htmlspecialchars($log['action']); ?></span></td>
                                            <td><?php echo htmlspecialchars(substr($log['details'] ?? '', 0, 50)); ?>...</td>
                                            <td><?php echo htmlspecialchars($log['ip_address']); ?></td>
                                            <td><?php echo formatDateTime($log['created_at']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
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