<?php
session_start();
require_once '../config/database.php';
require_once '../config/functions.php';
require_once '../models/ReportsAnalytics.php';

if (!isLogged()) {
    header('Location: login.php');
    exit;
}

$currentRole = currentRole();
$currentUser = currentUser();

if (!in_array($currentRole, ['PRO', 'PRESIDENT', 'DIRECTOR ICT', 'DEAN'])) {
    header('Location: index.php');
    exit;
}

$pageTitle = 'Analytics & Reports';

$model = new ReportsAnalytics(db());
$stats = $model->getAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> | DHLTU SRC</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;0,700;1,300;1,400;1,600;1,700&family=Outfit:wght@200;300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link rel="icon" type="image/png" href="../assets/images/logo.png">
</head>
<body>
    <div class="dashboard-layout">
        <div class="mobile-overlay" id="mobileOverlay"></div>
        <aside class="sidebar" id="sidebar">
            <button class="sidebar-toggle" id="sidebarToggle"><i class="bi bi-chevron-left"></i></button>
            <div class="sidebar-header">
                <div class="sidebar-logo">SRC</div>
                <span class="sidebar-title">DHLTU Dashboard</span>
            </div>
            <?php require_once '../include/nav-links.php'; $nav = new NavigationRBAC($currentRole); echo $nav->renderNavigation(); ?>
            <div class="sidebar-user">
                <div class="user-avatar"><?php echo strtoupper(substr($currentUser['first_name'], 0, 1) . substr($currentUser['last_name'], 0, 1)); ?></div>
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']); ?></div>
                    <div class="user-role"><span class="role-badge <?php echo $currentRole === 'PRO' ? 'admin' : 'monitor'; ?>"><?php echo $currentRole; ?></span></div>
                </div>
            </div>
        </aside>

        <div class="main-content">
            <header class="dashboard-header">
                <button class="mobile-toggle" id="mobileToggle"><i class="bi bi-list"></i></button>
                <h1 class="header-title">Analytics & Reports</h1>
                <div class="header-actions"><a href="logout.php" class="header-btn"><i class="bi bi-box-arrow-right"></i></a></div>
            </header>
            <main class="content-body">
                <div class="dashboard-container">
                    <div class="dashboard-header-section">
                        <h2 class="dashboard-title">System Analytics</h2>
                        <p class="dashboard-subtitle">Comprehensive overview of all system data and trends</p>
                    </div>

                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-card-header">
                                <span class="stat-card-label">Total Users</span>
                                <div class="stat-card-icon primary"><i class="bi bi-people"></i></div>
                            </div>
                            <div class="stat-card-value"><?php echo $stats['users']['total']; ?></div>
                            <div class="stat-card-label">Active users across all roles</div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-card-header">
                                <span class="stat-card-label">Total Complaints</span>
                                <div class="stat-card-icon info"><i class="bi bi-clipboard"></i></div>
                            </div>
                            <div class="stat-card-value"><?php echo $stats['complaints']['total']; ?></div>
                            <div class="stat-card-label"><?php echo $stats['complaints']['open']; ?> open, <?php echo $stats['complaints']['resolved']; ?> resolved</div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-card-header">
                                <span class="stat-card-label">Clubs & Societies</span>
                                <div class="stat-card-icon success"><i class="bi bi-collection"></i></div>
                            </div>
                            <div class="stat-card-value"><?php echo $stats['clubs']['total']; ?></div>
                            <div class="stat-card-label"><?php echo $stats['clubs']['active']; ?> active</div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-card-header">
                                <span class="stat-card-label">GA Sessions</span>
                                <div class="stat-card-icon info"><i class="bi bi-calendar-event"></i></div>
                            </div>
                            <div class="stat-card-value"><?php echo $stats['ga']['total']; ?></div>
                            <div class="stat-card-label"><?php echo $stats['ga']['completed']; ?> completed</div>
                        </div>
                    </div>

                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(350px,1fr));gap:20px;margin-top:24px;">
                        <div style="background:var(--card-bg);border:1px solid rgba(138,155,184,0.1);border-radius:12px;padding:20px;">
                            <h3 style="margin:0 0 16px 0;font-size:16px;font-weight:600;">Users by Role</h3>
                            <canvas id="usersByRoleChart"></canvas>
                        </div>

                        <div style="background:var(--card-bg);border:1px solid rgba(138,155,184,0.1);border-radius:12px;padding:20px;">
                            <h3 style="margin:0 0 16px 0;font-size:16px;font-weight:600;">Complaints by Category</h3>
                            <canvas id="complaintsByCatChart"></canvas>
                        </div>

                        <div style="background:var(--card-bg);border:1px solid rgba(138,155,184,0.1);border-radius:12px;padding:20px;">
                            <h3 style="margin:0 0 16px 0;font-size:16px;font-weight:600;">GA Sessions by Type</h3>
                            <canvas id="gaByTypeChart"></canvas>
                        </div>

                        <div style="background:var(--card-bg);border:1px solid rgba(138,155,184,0.1);border-radius:12px;padding:20px;">
                            <h3 style="margin:0 0 16px 0;font-size:16px;font-weight:600;">Complaint Trends (Last 12 Months)</h3>
                            <canvas id="complaintTrendChart"></canvas>
                        </div>
                    </div>

                    <div style="margin-top:24px;background:var(--card-bg);border:1px solid rgba(138,155,184,0.1);border-radius:12px;padding:20px;">
                        <h3 style="margin:0 0 16px 0;font-size:16px;font-weight:600;">Detailed Statistics</h3>
                        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;">
                            <div>
                                <div style="font-size:12px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px;">Documents</div>
                                <div style="font-size:20px;font-weight:700;color:var(--gold);"><?php echo $stats['documents']['total']; ?></div>
                                <div style="font-size:12px;color:var(--text-muted);"><?php echo $stats['documents']['pending']; ?> pending</div>
                            </div>
                            <div>
                                <div style="font-size:12px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px;">Voting Records</div>
                                <div style="font-size:20px;font-weight:700;color:var(--gold);"><?php echo $stats['voting']['total']; ?></div>
                                <div style="font-size:12px;color:var(--text-muted);"><?php echo $stats['voting']['passed']; ?> passed</div>
                            </div>
                            <div>
                                <div style="font-size:12px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px;">Resolutions</div>
                                <div style="font-size:20px;font-weight:700;color:var(--gold);"><?php echo $stats['resolutions']['total']; ?></div>
                                <div style="font-size:12px;color:var(--text-muted);">Total</div>
                            </div>
                            <div>
                                <div style="font-size:12px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px;">Audit Logs</div>
                                <div style="font-size:20px;font-weight:700;color:var(--gold);"><?php echo $stats['audit']['total']; ?></div>
                                <div style="font-size:12px;color:var(--text-muted);"><?php echo $stats['audit']['today']; ?> today</div>
                            </div>
                            <div>
                                <div style="font-size:12px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px;">Committees</div>
                                <div style="font-size:20px;font-weight:700;color:var(--gold);"><?php echo $stats['committees']['total']; ?></div>
                                <div style="font-size:12px;color:var(--text-muted);"><?php echo $stats['committees']['members']; ?> members</div>
                            </div>
                            <div>
                                <div style="font-size:12px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px;">Elections</div>
                                <div style="font-size:20px;font-weight:700;color:var(--gold);"><?php echo $stats['elections']['total']; ?></div>
                                <div style="font-size:12px;color:var(--text-muted);"><?php echo $stats['elections']['ongoing']; ?> ongoing</div>
                            </div>
                            <div>
                                <div style="font-size:12px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px;">News & Announcements</div>
                                <div style="font-size:20px;font-weight:700;color:var(--gold);"><?php echo $stats['news']['total']; ?></div>
                                <div style="font-size:12px;color:var(--text-muted);"><?php echo $stats['news']['published']; ?> published</div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
    const usersByRole = <?php echo json_encode($stats['users_by_role']); ?>;
    const complaintsByCat = <?php echo json_encode($stats['complaints_by_cat']); ?>;
    const gaByType = <?php echo json_encode($stats['ga_by_type']); ?>;
    const complaintTrend = <?php echo json_encode($stats['complaints_trend']); ?>;

    new Chart(document.getElementById('usersByRoleChart'), {
        type: 'bar',
        data: {
            labels: usersByRole.map(r => r.role),
            datasets: [{
                label: 'Users',
                data: usersByRole.map(r => r.cnt),
                backgroundColor: 'rgba(201, 168, 76, 0.8)',
                borderColor: 'rgba(201, 168, 76, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
            plugins: { legend: { display: false } }
        }
    });

    new Chart(document.getElementById('complaintsByCatChart'), {
        type: 'doughnut',
        data: {
            labels: complaintsByCat.map(c => c.category),
            datasets: [{
                data: complaintsByCat.map(c => c.cnt),
                backgroundColor: ['#c9a84c', '#4f6e9a', '#22c55e', '#f59e0b', '#6b7280']
            }]
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
    });

    new Chart(document.getElementById('gaByTypeChart'), {
        type: 'pie',
        data: {
            labels: gaByType.map(g => g.session_type),
            datasets: [{
                data: gaByType.map(g => g.cnt),
                backgroundColor: ['#c9a84c', '#4f6e9a', '#22c55e']
            }]
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
    });

    new Chart(document.getElementById('complaintTrendChart'), {
        type: 'line',
        data: {
            labels: complaintTrend.map(t => t.label),
            datasets: [{
                label: 'Complaints',
                data: complaintTrend.map(t => t.cnt),
                borderColor: 'rgba(201, 168, 76, 1)',
                backgroundColor: 'rgba(201, 168, 76, 0.1)',
                tension: 0.3,
                fill: true
            }]
        },
        options: {
            responsive: true,
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
        }
    });
    </script>
    <script src="../assets/js/sidebar.js"></script>
</body>
</html>