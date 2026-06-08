<?php
session_start();
require_once '../config/database.php';
require_once '../config/functions.php';
require_once '../models/Departments.php';

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

$pageTitle = 'Departments Management';

$success = $_SESSION['success'] ?? null;
$errors = $_SESSION['errors'] ?? [];
unset($_SESSION['success'], $_SESSION['errors']);

$deptModel = new Departments(db());
$departments = $deptModel->getAll();
$allUsers = $deptModel->getAllUsers();

$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? $_GET['action'] ?? null;
    $id = $_POST['id'] ?? $_GET['id'] ?? null;

    $name = trim($_POST['name'] ?? '');
    $code = trim($_POST['code'] ?? '');
    $deanId = !empty($_POST['dean_id']) ? (int)$_POST['dean_id'] : null;

    $data = [
        'name' => $name,
        'code' => $code,
        'dean_id' => $deanId
    ];

    if ($action === 'create') {
        $deptModel->create($data);
        $_SESSION['success'] = 'Department created successfully';
    } elseif ($action === 'edit' && $id) {
        $deptModel->update((int)$id, $data);
        $_SESSION['success'] = 'Department updated successfully';
    }

    header('Location: departments.php');
    exit;
}

if ($action === 'delete' && $id) {
    $dept = $deptModel->getById($id);
    $userCount = $deptModel->countUsers($id);
    
    if ($userCount > 0) {
        $_SESSION['errors'] = ['Cannot delete department with existing users. Reassign users first.'];
    } else {
        $deptModel->delete((int)$id);
        $_SESSION['success'] = 'Department deleted successfully';
    }
    header('Location: departments.php');
    exit;
}

$editDept = null;
if ($action === 'edit' && $id) {
    $editDept = $deptModel->getById($id);
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
                <h1 class="header-title">Departments</h1>
                <div class="header-actions">
                    <a href="../logout.php" class="header-btn" aria-label="Logout">
                        <i class="bi bi-box-arrow-right"></i>
                    </a>
                </div>
            </header>

            <main class="content-body">
                <div class="dashboard-container">
                    <div class="dashboard-header-section">
                        <h2 class="dashboard-title">Departments Management</h2>
                        <p class="dashboard-subtitle">Manage academic departments</p>
                    </div>

                    <?php if ($success): ?>
                        <div style="background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.2);border-radius:8px;padding:16px;margin-bottom:20px;color:#22c55e;"><?php echo htmlspecialchars($success); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($errors)): ?>
                        <div style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.2);border-radius:8px;padding:16px;margin-bottom:20px;color:#ef4444;">
                            <?php foreach ($errors as $error): echo htmlspecialchars($error) . '<br>'; endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div class="table-container">
                        <div style="padding:16px 24px;border-bottom:1px solid rgba(138,155,184,0.1);display:flex;justify-content:space-between;align-items:center;">
                            <h3 style="margin:0;">Departments</h3>
                            <button type="button" class="btn btn-primary" onclick="openModal()"><i class="bi bi-plus"></i> Add Department</button>
                        </div>
                    </div>

                    <div class="table-container">
                        <?php if (empty($departments)): ?>
                            <div class="empty-state">
                                <div class="empty-icon">
                                    <i class="bi bi-building"></i>
                                </div>
                                <h3 class="empty-title">No departments found</h3>
                                <p class="empty-text">Click "Add Department" to register the first department</p>
                            </div>
                        <?php else: ?>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Department</th>
                                        <th>Code</th>
                                        <th>Users</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($departments as $index => $dept): ?>
                                        <tr>
                                            <td><?php echo $index + 1; ?></td>
                                            <td>
                                                <div class="user-info-cell">
                                                    <div class="user-avatar-table" style="background:linear-gradient(135deg, #3b82f6, #60a5fa);">
                                                        <?php echo strtoupper(substr($dept['name'], 0, 2)); ?>
                                                    </div>
                                                    <div>
                                                        <div class="user-name"><?php echo htmlspecialchars($dept['name']); ?></div>
                                                        <div class="user-email"><?php echo htmlspecialchars($dept['dean_first'] ? $dept['dean_first'] . ' ' . $dept['dean_last'] . ' (Dean)' : 'No dean assigned'); ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><span class="badge badge-role"><?php echo htmlspecialchars($dept['code']); ?></span></td>
                                            <td><?php echo $dept['user_count']; ?></td>
                                            <td><span class="badge badge-active">Active</span></td>
                                            <td>
                                                <a href="?action=edit&id=<?php echo $dept['id']; ?>" class="btn btn-sm btn-outline" style="padding:4px 8px;"><i class="bi bi-pencil"></i></a>
                                                <a href="?action=delete&id=<?php echo $dept['id']; ?>" class="btn btn-sm btn-danger" style="padding:4px 8px;" onclick="return confirm('Are you sure you want to delete this department?')"><i class="bi bi-trash"></i></a>
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

    <div id="deptModal" style="display:<?php echo ($action === 'edit' && $editDept) ? 'flex' : 'none'; ?>;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center;">
        <div style="background:#fff;border-radius:12px;max-width:500px;width:90%;max-height:90vh;overflow-y:auto;">
            <div style="padding:24px;border-bottom:1px solid #eee;display:flex;justify-content:space-between;align-items:center;">
                <h3 style="margin:0;"><?php echo $editDept ? 'Edit' : 'Add'; ?> Department</h3>
                <button onclick="closeModal()" style="background:none;border:none;font-size:24px;cursor:pointer;">&times;</button>
            </div>
            <form method="POST" style="padding:24px;">
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($editDept['id'] ?? ''); ?>">
                <input type="hidden" name="action" value="<?php echo $editDept ? 'edit' : 'create'; ?>">

                <div class="form-group">
                    <label class="form-label">Department Name *</label>
                    <input type="text" name="name" class="form-input" placeholder="e.g. Faculty of Science & Technology" required value="<?php echo htmlspecialchars($editDept['name'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Department Code *</label>
                    <input type="text" name="code" class="form-input" placeholder="e.g. FST" required value="<?php echo htmlspecialchars($editDept['code'] ?? ''); ?>" style="text-transform:uppercase;">
                </div>

                <div class="form-group">
                    <label class="form-label">Dean</label>
                    <select name="dean_id" class="form-select">
                        <option value="">— Unassigned —</option>
                        <?php foreach ($allUsers as $u): ?>
                            <option value="<?php echo $u['id']; ?>" <?php echo (isset($editDept['dean_id']) && $editDept['dean_id'] == $u['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($u['first_name'] . ' ' . $u['last_name'] . ' (' . $u['email'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="display:flex;gap:12px;justify-content:flex-end;margin-top:20px;">
                    <button type="button" onclick="closeModal()" class="btn btn-outline">Cancel</button>
                    <button type="submit" class="btn btn-primary"><?php echo $editDept ? 'Update' : 'Create'; ?> Department</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../assets/js/sidebar.js"></script>
    <script src="../assets/js/loader-service.js"></script>
    <script>
        function openModal() { document.getElementById('deptModal').style.display = 'flex'; }
        function closeModal() { document.getElementById('deptModal').style.display = 'none'; }
        document.getElementById('deptModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });
    </script>
    <?php if (function_exists('alert')): ?>
    <?php echo alert()->render(); ?>
    <?php endif; ?>
</body>
</html>