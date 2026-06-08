<?php
session_start();
require_once "../config/database.php";
require_once "../config/functions.php";
require_once "../models/EmailSubscription.php";

if (!isLogged()) {
    header("Location: login.php");
    exit;
}

$currentRole = currentRole();
$currentUser = currentUser();

if (!currentUserCan('can_manage_news')) {
    header("Location: index.php");
    exit;
}

$pageTitle = "Email Subscriptions";
$success = $_SESSION["success"] ?? null;
$error = $_SESSION["error"] ?? null;
unset($_SESSION["success"], $_SESSION["error"]);

$emailModel = new EmailSubscription(db());
$filter = $_GET["filter"] ?? "active";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['action'] ?? '';
    
    if ($postAction === 'send-newsletter') {
        $subject = trim($_POST['subject'] ?? '');
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        
        if (empty($subject) || empty($content)) {
            $error = "Subject and content are required";
        } else {
            require_once "../models/EmailSubscription.php";
            $result = $emailModel->sendNewsletter($subject, $content, 'html');
            
            if ($result['success']) {
                $_SESSION["success"] = "Newsletter sent successfully! Sent: {$result['sent']}, Failed: {$result['failed']}";
            } else {
                $error = "Failed to send newsletter";
            }
            
            header("Location: email-subscriptions.php");
            exit;
        }
    } elseif ($postAction === 'delete-subscriber') {
        $subscriberId = intval($_POST['subscriber_id'] ?? 0);
        if ($subscriberId > 0) {
            $emailModel->delete($subscriberId);
            $_SESSION["success"] = "Subscriber deleted successfully";
            header("Location: email-subscriptions.php");
            exit;
        }
    }
}

$allSubscribers = $emailModel->getAllActive();
$inactiveSubscribers = db()->fetchAll("SELECT * FROM email_subscribers WHERE is_active = FALSE ORDER BY unsubscribed_at DESC");

if ($filter === 'active') {
    $subscribers = $allSubscribers;
    $pageSubtitle = "Active Subscribers";
} elseif ($filter === 'inactive') {
    $subscribers = $inactiveSubscribers;
    $pageSubtitle = "Inactive Subscribers";
} else {
    $allInactive = db()->fetchAll("SELECT * FROM email_subscribers WHERE is_active = FALSE ORDER BY unsubscribed_at DESC");
    $subscribers = array_merge($allSubscribers, $allInactive);
    $pageSubtitle = "All Subscribers";
}
$totalSubscribers = count($allSubscribers) + count($inactiveSubscribers);
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
            <?php require_once "../include/nav-links.php"; $nav = new NavigationRBAC($currentRole); echo $nav->renderNavigation(); ?>
            <div class="sidebar-user">
                <div class="user-avatar"><?php echo strtoupper(substr($currentUser["first_name"], 0, 1) . substr($currentUser["last_name"], 0, 1)); ?></div>
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($currentUser["first_name"] . " " . $currentUser["last_name"]); ?></div>
                    <div class="user-role"><span class="role-badge admin"><?php echo $currentRole; ?></span></div>
                </div>
            </div>
        </aside>

        <div class="main-content">
            <header class="dashboard-header">
                <button class="mobile-toggle" id="mobileToggle"><i class="bi bi-list"></i></button>
                <h1 class="header-title">Email Subscriptions</h1>
                <div class="header-actions"><a href="../logout.php" class="header-btn"><i class="bi bi-box-arrow-right"></i></a></div>
            </header>

            <main class="content-body">
                <div class="dashboard-container">
                    <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle"></i>
                        <span><?php echo htmlspecialchars($success); ?></span>
                    </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="bi bi-exclamation-circle"></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                    <?php endif; ?>

                    <div class="dashboard-header-section">
                        <h2 class="dashboard-title"><?php echo $pageSubtitle; ?></h2>
                        <p class="dashboard-subtitle">Manage email subscribers and send newsletters</p>
                    </div>

                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-card-header">
                                <span class="stat-card-label">Active</span>
                                <div class="stat-card-icon success"><i class="bi bi-check-circle"></i></div>
                            </div>
                            <div class="stat-card-value"><?php echo count($allSubscribers); ?></div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-card-header">
                                <span class="stat-card-label">Inactive</span>
                                <div class="stat-card-icon warning"><i class="bi bi-x-circle"></i></div>
                            </div>
                            <div class="stat-card-value"><?php echo count($inactiveSubscribers); ?></div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-card-header">
                                <span class="stat-card-label">Total</span>
                                <div class="stat-card-icon info"><i class="bi bi-people"></i></div>
                            </div>
                            <div class="stat-card-value"><?php echo $totalSubscribers; ?></div>
                        </div>
                    </div>

                    <div class="tabs">
                        <div class="tab" onclick="openNewsletterModal()">
                            <i class="bi bi-envelope"></i> Send Newsletter
                        </div>
                        <div class="tab active" onclick="closeNewsletterModal()">
                            <i class="bi bi-people"></i> Manage Subscribers
                        </div>
                    </div>

                    <div id="manage-subscribers" class="tab-content active">
                        <div class="filter-tabs">
                            <a href="?filter=active" class="filter-tab <?php echo $filter === 'active' ? 'active' : ''; ?>">
                                <i class="bi bi-check-circle"></i> Active (<?php echo count($allSubscribers); ?>)
                            </a>
                            <a href="?filter=inactive" class="filter-tab <?php echo $filter === 'inactive' ? 'active' : ''; ?>">
                                <i class="bi bi-x-circle"></i> Inactive (<?php echo count($inactiveSubscribers); ?>)
                            </a>
                            <a href="?filter=all" class="filter-tab <?php echo $filter === 'all' ? 'active' : ''; ?>">
                                <i class="bi bi-funnel"></i> All Subscribers
                            </a>
                        </div>

                        <?php if (count($subscribers) > 0): ?>
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Email</th>
                                        <th>Name</th>
                                        <th>Status</th>
                                        <th>Subscribed Date</th>
                                        <th>Last Email Sent</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($subscribers as $sub): ?>
                                    <tr>
                                        <td data-label="Email"><span><?php echo htmlspecialchars($sub['email']); ?></span></td>
                                        <td data-label="Name"><?php echo htmlspecialchars($sub['full_name'] ?? 'N/A'); ?></td>
                                        <td data-label="Status">
                                            <span class="badge <?php echo $sub['is_active'] ? 'badge-active' : 'badge-inactive'; ?>">
                                                <?php echo $sub['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td data-label="Subscribed"><span><?php echo date('M d, Y', strtotime($sub['subscribed_at'])); ?></span></td>
                                        <td data-label="Last Sent">
                                            <span>
                                                <?php echo $sub['last_sent'] ? date('M d, Y', strtotime($sub['last_sent'])) : 'Never'; ?>
                                            </span>
                                        </td>
                                        <td data-label="Actions">
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="delete-subscriber">
                                                <input type="hidden" name="subscriber_id" value="<?php echo $sub['id']; ?>">
                                                <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure? This subscriber will be permanently deleted.');">
                                                    <i class="bi bi-trash"></i> Delete
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-icon"><i class="bi bi-inbox"></i></div>
                            <h3 class="empty-title">No Subscribers Found</h3>
                            <p class="empty-text">There are no <?php echo $filter; ?> subscribers at this time.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <div class="modal-overlay" id="newsletterModal">
        <div class="modal-content gold-theme">
            <div class="modal-header">
                <h3 class="modal-title">Send Newsletter</h3>
                <button class="modal-close" onclick="closeNewsletterModal()"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="action" value="send-newsletter">

                    <div class="form-group">
                        <label for="subject" class="form-label">Subject *</label>
                        <input type="text" id="subject" name="subject" required placeholder="E.g., Important Announcement" class="form-input">
                    </div>

                    <div class="form-group">
                        <label for="title" class="form-label">Title (Optional)</label>
                        <input type="text" id="title" name="title" placeholder="Email title" class="form-input">
                    </div>

                    <div class="form-group">
                        <label for="content" class="form-label">Content (HTML) *</label>
                        <textarea id="content" name="content" required placeholder="Enter HTML content here..."></textarea>
                        <small style="display: block; margin-top: 8px; color: var(--dashboard-text-muted);">
                            <i class="bi bi-lightbulb"></i> Tip: You can use HTML formatting like &lt;p&gt;, &lt;h2&gt;, &lt;strong&gt;, etc.
                        </small>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeNewsletterModal()">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-send"></i> Send to <?php echo $totalSubscribers; ?> Subscribers
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../assets/js/sidebar.js"></script>
    <script>
        function openNewsletterModal() {
            document.getElementById('newsletterModal').classList.add('open');
        }

        function closeNewsletterModal() {
            document.getElementById('newsletterModal').classList.remove('open');
        }

        document.addEventListener('DOMContentLoaded', () => {
            const modal = document.getElementById('newsletterModal');
            if (modal) {
                modal.addEventListener('click', (e) => {
                    if (e.target === modal) {
                        closeNewsletterModal();
                    }
                });
            }
        });
    </script>
</body>
</html>