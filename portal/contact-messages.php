<?php
session_start();
require_once "../config/database.php";
require_once "../config/functions.php";
require_once "../models/ContactMessages.php";
require_once "../include/nav-links.php";

if (!isLogged()) {
    header("Location: login.php");
    exit;
}

$currentRole = currentRole();
$currentUser = currentUser();

if ($currentRole !== "PRO") {
    header("Location: index.php");
    exit;
}

$pageTitle = "Contact Messages";
$success = $_SESSION["success"] ?? null;
$error = $_SESSION["error"] ?? null;
unset($_SESSION["success"], $_SESSION["error"]);

$db = Database::getInstance();
$contactModel = new ContactMessages($db);

$action = $_GET["action"] ?? "list";
$id = $_GET["id"] ?? null;

// Handle actions
if ($action === "delete" && $id) {
    $contactModel->delete($id);
    $_SESSION["success"] = "Message deleted successfully";
    header("Location: contact-messages.php");
    exit;
}

if ($action === "assign" && $id && isset($_POST["assigned_to"])) {
    $contactModel->assign($id, $_POST["assigned_to"]);
    $_SESSION["success"] = "Message assigned successfully";
    header("Location: contact-messages.php");
    exit;
}

if ($action === "respond" && $id) {
    $contactModel->addResponse($id, $_POST["response"] ?? '');
    $contactModel->updateStatus($id, 'RESPONDED');
    $_SESSION["success"] = "Response added successfully";
    header("Location: contact-messages.php");
    exit;
}

if ($action === "close" && $id) {
    $contactModel->updateStatus($id, 'CLOSED');
    $_SESSION["success"] = "Message closed";
    header("Location: contact-messages.php");
    exit;
}

// Get filters
$filters = [
    'status' => $_GET['status'] ?? '',
    'category' => $_GET['category'] ?? '',
    'search' => $_GET['search'] ?? ''
];

// Remove empty filters
$filters = array_filter($filters, fn($v) => $v !== '');

$messages = $contactModel->getAll($filters);
$categories = $contactModel->getCategories();
$staffMembers = $contactModel->getStaffMembers();
$stats = [
    'total' => $contactModel->countAll(),
    'new' => $contactModel->countByStatus('NEW'),
    'in_progress' => $contactModel->countByStatus('IN_PROGRESS'),
    'responded' => $contactModel->countByStatus('RESPONDED'),
    'resolved' => $contactModel->countByStatus('RESOLVED')
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> | DHLTU SRC Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;0,700;1,300;1,400;1,600;1,700&family=Outfit:wght@200;300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-NEW { background: rgba(201,168,76,0.15); color: #c9a84c; }
        .status-IN_PROGRESS { background: rgba(99,102,241,0.15); color: #6366f1; }
        .status-RESPONDED { background: rgba(34,197,94,0.15); color: #22c55e; }
        .status-RESOLVED { background: rgba(138,155,184,0.15); color: #8a9bb8; }
        .status-CLOSED { background: rgba(100,116,139,0.15); color: #64748b; }
        .message-preview {
            max-width: 300px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .response-text {
            max-width: 250px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            font-size: 12px;
            color: #8a9bb8;
        }
    </style>
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
             <?php $nav = new NavigationRBAC($currentRole); echo $nav->renderNavigation(); ?>
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
                <h1 class="header-title">Contact Messages</h1>
                <div class="header-actions"><a href="../logout.php" class="header-btn"><i class="bi bi-box-arrow-right"></i></a></div>
            </header>
            <main class="content-body">
                <div class="dashboard-container">
                    <div class="dashboard-header-section">
                        <h2 class="dashboard-title">Message Management</h2>
                        <p class="dashboard-subtitle">View and respond to messages from the "Get In Touch" form on the homepage</p>
                    </div>

                    <?php if ($success): ?>
                        <div style="background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.2);border-radius:8px;padding:16px;margin-bottom:20px;color:#22c55e;"><?php echo $success; ?></div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.2);border-radius:8px;padding:16px;margin-bottom:20px;color:#ef4444;"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <!-- Stats Cards -->
                    <div class="stats-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:16px;margin-bottom:24px;">
                        <div class="stat-card"><div class="stat-value"><?php echo $stats['total']; ?></div><div class="stat-label">Total</div></div>
                        <div class="stat-card"><div class="stat-value" style="color:#c9a84c;"><?php echo $stats['new']; ?></div><div class="stat-label">New</div></div>
                        <div class="stat-card"><div class="stat-value" style="color:#6366f1;"><?php echo $stats['in_progress']; ?></div><div class="stat-label">In Progress</div></div>
                        <div class="stat-card"><div class="stat-value" style="color:#22c55e;"><?php echo $stats['responded']; ?></div><div class="stat-label">Responded</div></div>
                        <div class="stat-card"><div class="stat-value" style="color:#8a9bb8;"><?php echo $stats['resolved']; ?></div><div class="stat-label">Resolved</div></div>
                    </div>

                    <!-- Filters -->
                    <div class="filters-bar" style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:20px;padding:16px;background:rgba(138,155,184,0.03);border-radius:8px;">
                        <select onchange="filterChange('status', this.value)" style="padding:8px 12px;border:1px solid rgba(138,155,184,0.2);border-radius:4px;background:#fff;">
                            <option value="">All Statuses</option>
                            <option value="NEW" <?php echo ($filters['status'] ?? '') === 'NEW' ? 'selected' : ''; ?>>New</option>
                            <option value="IN_PROGRESS" <?php echo ($filters['status'] ?? '') === 'IN_PROGRESS' ? 'selected' : ''; ?>>In Progress</option>
                            <option value="RESPONDED" <?php echo ($filters['status'] ?? '') === 'RESPONDED' ? 'selected' : ''; ?>>Responded</option>
                            <option value="RESOLVED" <?php echo ($filters['status'] ?? '') === 'RESOLVED' ? 'selected' : ''; ?>>Resolved</option>
                            <option value="CLOSED" <?php echo ($filters['status'] ?? '') === 'CLOSED' ? 'selected' : ''; ?>>Closed</option>
                        </select>
                        <select onchange="filterChange('category', this.value)" style="padding:8px 12px;border:1px solid rgba(138,155,184,0.2);border-radius:4px;background:#fff;">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo ($filters['category'] ?? '') === $cat ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="text" id="searchInput" placeholder="Search messages..." value="<?php echo htmlspecialchars($filters['search'] ?? ''); ?>" onkeyup="debouncedSearch(this.value)" style="flex:1;min-width:200px;padding:8px 12px;border:1px solid rgba(138,155,184,0.2);border-radius:4px;">
                    </div>

                    <div class="table-container">
                        <?php if (empty($messages)): ?>
                            <div class="empty-state">
                                <div class="empty-icon"><i class="bi bi-envelope-open"></i></div>
                                <h3 class="empty-title">No messages found</h3>
                                <p class="empty-text">Messages from the contact form will appear here</p>
                            </div>
                        <?php else: ?>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Category</th>
                                        <th>Message</th>
                                        <th>Status</th>
                                        <th>Assigned To</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($messages as $index => $msg): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td><strong><?php echo htmlspecialchars($msg['full_name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($msg['email']); ?></td>
                                        <td><?php echo htmlspecialchars($msg['category'] ?? 'General'); ?></td>
                                        <td class="message-preview" title="<?php echo htmlspecialchars($msg['message']); ?>"><?php echo htmlspecialchars($msg['message']); ?></td>
                                        <td><span class="status-badge status-<?php echo $msg['status']; ?>"><?php echo $msg['status']; ?></span></td>
                                        <td><?php echo $msg['assigned_first'] ? htmlspecialchars($msg['assigned_first'] . ' ' . $msg['assigned_last']) : '<span style="color:#8a9bb8;">Unassigned</span>'; ?></td>
                                        <td><?php echo date('d M Y', strtotime($msg['created_at'])); ?></td>
                                        <td>
                                            <?php if ($msg['status'] === 'NEW'): ?>
                                                <button onclick="openAssignModal(<?php echo $msg['id']; ?>)" class="btn btn-sm btn-outline" style="padding:4px 8px;"><i class="bi bi-person-plus"></i></button>
                                            <?php endif; ?>
                                            <?php if ($msg['status'] !== 'CLOSED' && $msg['status'] !== 'RESPONDED'): ?>
                                                <button onclick="openRespondModal(<?php echo $msg['id']; ?>)" class="btn btn-sm btn-outline" style="padding:4px 8px;"><i class="bi bi-reply"></i></button>
                                            <?php endif; ?>
                                            <?php if ($msg['status'] !== 'CLOSED'): ?>
                                                <a href="?action=close&id=<?php echo $msg['id']; ?>" class="btn btn-sm btn-danger" style="padding:4px 8px;" onclick="return confirm('Close this message?')"><i class="bi bi-x-circle"></i></a>
                                            <?php endif; ?>
                                            <a href="?action=delete&id=<?php echo $msg['id']; ?>" class="btn btn-sm btn-danger" style="padding:4px 8px;" onclick="return confirm('Delete this message?')"><i class="bi bi-trash"></i></a>
                                        </td>
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

    <!-- Response Modal -->
    <div id="respondModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center;">
        <div style="background:#fff;padding:24px;border-radius:8px;width:90%;max-width:500px;">
            <h3 style="margin-top:0;">Add Response</h3>
            <form method="POST" action="?action=respond" id="respondForm">
                <input type="hidden" name="id" id="respondId">
                <div class="form-field" style="margin-bottom:16px;">
                    <label>Response Message</label>
                    <textarea name="response" rows="4" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;" required></textarea>
                </div>
                <div style="display:flex;gap:12px;justify-content:flex-end;">
                    <button type="button" onclick="closeRespondModal()" class="btn btn-outline">Cancel</button>
                    <button type="submit" class="btn btn-primary">Send Response</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Assign Modal -->
    <div id="assignModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:1001;align-items:center;justify-content:center;">
        <div style="background:#fff;padding:24px;border-radius:8px;width:90%;max-width:400px;">
            <h3 style="margin-top:0;">Assign Message</h3>
            <form method="POST" action="?action=assign" id="assignForm">
                <input type="hidden" name="id" id="assignId">
                <div class="form-field" style="margin-bottom:16px;">
                    <label>Assign To</label>
                    <select name="assigned_to" id="assignSelect" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;" required>
                        <option value="">Select staff member...</option>
                        <?php foreach ($staffMembers as $staff): ?>
                            <option value="<?php echo $staff['id']; ?>"><?php echo htmlspecialchars($staff['first_name'] . ' ' . $staff['last_name'] . ' (' . $staff['role'] . ')'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="display:flex;gap:12px;justify-content:flex-end;">
                    <button type="button" onclick="closeAssignModal()" class="btn btn-outline">Cancel</button>
                    <button type="submit" class="btn btn-primary">Assign</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function filterChange(key, value) {
        const url = new URL(window.location);
        if (value) url.searchParams.set(key, value);
        else url.searchParams.delete(key);
        window.location = url;
    }

    let searchTimeout;
    function debouncedSearch(value) {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            const url = new URL(window.location);
            if (value) url.searchParams.set('search', value);
            else url.searchParams.delete('search');
            window.location = url;
        }, 500);
    }

    function openRespondModal(id) {
        document.getElementById('respondId').value = id;
        document.getElementById('respondModal').style.display = 'flex';
    }

    function closeRespondModal() {
        document.getElementById('respondModal').style.display = 'none';
    }

    function openAssignModal(id) {
        document.getElementById('assignId').value = id;
        document.getElementById('assignModal').style.display = 'flex';
    }

    function closeAssignModal() {
        document.getElementById('assignModal').style.display = 'none';
    }
    </script>
    <script src="../assets/js/sidebar.js"></script>
</body>
</html>