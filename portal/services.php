<?php
session_start();
require_once "../config/database.php";
require_once "../config/functions.php";
require_once "../models/Services.php";

if (!isLogged()) {
    header("Location: login.php");
    exit;
}

$currentRole = currentRole();
$currentUser = currentUser();

if (!in_array($currentRole, ["PRO", "Admin"])) {
    header("Location: index.php");
    exit;
}

$pageTitle = "Services Management";
$success  = $_SESSION["success"]  ?? null;
$errors   = $_SESSION["errors"]   ?? [];
unset($_SESSION["success"], $_SESSION["errors"]);

$servicesModel  = new Services(db());
$services       = $servicesModel->getAll();
$commonIcons    = $servicesModel->getCommonIcons();

// ── Handle POST: create / update / toggle / delete ──────────────────────────
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";
    $csrf   = $_POST["csrf_token"] ?? "";

    if ($csrf !== ($_SESSION["csrf_token"] ?? "")) {
        $_SESSION["errors"] = ["Invalid request token."];
        header("Location: services.php");
        exit;
    }

    if ($action === "create" || $action === "update") {
        $title         = trim($_POST["title"]         ?? "");
        $description   = trim($_POST["description"]   ?? "");
        $icon          = trim($_POST["icon"]          ?? "bi-star");
        $display_order = (int)($_POST["display_order"] ?? 0);
        $is_active     = isset($_POST["is_active"]) ? 1 : 0;

        if ($action === "create") {
            if (empty($title)) { $_SESSION["errors"] = ["Service title is required."]; }
            else { $servicesModel->create(compact("title","description","icon","display_order","is_active")); $_SESSION["success"] = "Service created successfully."; }
        } else {
            $id = (int)($_POST["id"] ?? 0);
            if ($id <= 0 || empty($title)) { $_SESSION["errors"] = ["Invalid service ID or empty title."]; }
            else { $servicesModel->update($id, compact("title","description","icon","display_order","is_active")); $_SESSION["success"] = "Service updated successfully."; }
        }
    } elseif ($action === "delete") {
        $id = (int)($_POST["id"] ?? 0);
        if ($id > 0) { $servicesModel->delete($id); $_SESSION["success"] = "Service deleted permanently."; }
    } elseif ($action === "toggle") {
        $id = (int)($_POST["id"] ?? 0);
        if ($id > 0) { $servicesModel->toggleActive($id); $_SESSION["success"] = "Service status updated."; }
    }

    header("Location: services.php");
    exit;
}

$csrfToken = bin2hex(random_bytes(16));
$_SESSION["csrf_token"] = $csrfToken;
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
            <h1 class="header-title">Services Management</h1>
            <div class="header-actions"><a href="../logout.php" class="header-btn"><i class="bi bi-box-arrow-right"></i></a></div>
        </header>
        <main class="content-body">
            <div class="dashboard-container">
                <div class="dashboard-header-section">
                    <h2 class="dashboard-title">Services</h2>
                    <p class="dashboard-subtitle">Manage services displayed on the website</p>
                </div>

                <?php if ($success): ?>
                    <div class="alert alert-success"><i class="bi bi-check-circle"></i> <?php echo $success; ?></div>
                <?php endif; ?>
                <?php foreach ($errors ?? [] as $err): ?>
                    <div class="alert alert-error"><i class="bi bi-exclamation-triangle"></i> <?php echo $err; ?></div>
                <?php endforeach; ?>

                <div class="actions-bar">
                    <div class="search-box">
                        <i class="bi bi-search"></i>
                        <input type="text" id="svcSearch" placeholder="Search services..." onkeyup="filterServices()">
                    </div>
                    <div class="actions-right">
                        <span><?php echo count($services); ?> service<?php echo count($services) === 1 ? '' : 's'; ?> total</span>
                        <button type="button" class="btn btn-primary" onclick="openServiceModal()"><i class="bi bi-plus"></i> Add Service</button>
                    </div>
                </div>

                <div class="table-container">
                    <?php if (empty($services)): ?>
                        <div class="empty-state">
                            <div class="empty-icon"><i class="bi bi-collection-play"></i></div>
                            <h3 class="empty-title">No services found</h3>
                            <p class="empty-text">Click "Add Service" to create the first service.</p>
                        </div>
                    <?php else: ?>
                        <table class="table" id="svcTable">
                            <thead>
                                <tr>
                                    <th>Icon</th>
                                    <th>Title & Description</th>
                                    <th>Order</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($services as $index => $service): ?>
                                    <tr data-keyword="<?php echo strtolower(htmlspecialchars($service['title'] . ' ' . ($service['description'] ?? ''))); ?>">
                                        <td style="width:50px;text-align:center;font-size:22px;color:var(--dashboard-primary);"><i class="bi <?php echo htmlspecialchars($service['icon'] ?: 'bi-star'); ?>"></i></td>
                                        <td>
                                            <div style="font-weight:500;font-size:14px;"><?php echo htmlspecialchars($service['title']); ?></div>
                                            <div style="font-size:12px;color:var(--dashboard-text-muted);margin-top:2px;max-width:320px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo htmlspecialchars($service['description'] ?? '—'); ?></div>
                                        </td>
                                        <td style="font-family:'Space Mono',monospace;font-size:12px;color:var(--dashboard-text-muted);">#<?php echo (int)$service['display_order']; ?></td>
                                        <td><span class="badge <?php echo (int)$service['is_active'] ? 'badge-active' : 'badge-inactive'; ?>"><i class="bi <?php echo (int)$service['is_active'] ? 'bi-check' : 'bi-x'; ?>"></i> <?php echo (int)$service['is_active'] ? 'Active' : 'Hidden'; ?></span></td>
                                        <td>
                                            <div style="display:flex;gap:6px;">
                                                <button class="btn btn-secondary btn-sm" onclick="editService(<?php echo (int)$service['id']; ?>)" title="Edit"><i class="bi bi-pencil"></i></button>
                                                <form method="POST" style="display:inline;" onsubmit="return confirm('Toggle status for this service?');"><input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>"><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?php echo (int)$service['id']; ?>"><button type="submit" class="btn btn-sm" style="background:rgba(255,255,255,0.05);color:var(--dashboard-text);border:1px solid var(--dashboard-border);" title="Toggle active"><i class="bi bi-<?php echo (int)$service['is_active'] ? 'eye-slash' : 'eye'; ?>"></i></button></form>
                                                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this service permanently?');"><input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?php echo (int)$service['id']; ?>"><button type="submit" class="btn btn-danger btn-sm"><i class="bi bi-trash"></i></button></form>
                                            </div>
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

<div id="serviceModal" class="modal-overlay">
    <div class="modal-content gold-theme">
        <div class="modal-header">
            <h3 class="modal-title" id="modalTitle">Add New Service</h3>
            <button type="button" class="modal-close" onclick="closeServiceModal()"><i class="bi bi-x-lg"></i></button>
        </div>
        <form id="svcForm" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            <input type="hidden" name="action" id="formAction" value="create">
            <input type="hidden" name="id" id="formId" value="">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Service Title *</label>
                    <input type="text" name="title" id="fTitle" class="form-input" required placeholder="e.g. Academic Affairs">
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" id="fDesc" class="form-input" rows="3" placeholder="Brief description of the service..."></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Bootstrap Icon</label>
                    <input type="text" name="icon" id="fIcon" class="form-input" placeholder="e.g. bi-mortarboard" list="iconList" value="bi-star">
                    <datalist id="iconList">
                        <?php foreach ($commonIcons as $iconClass => $label): ?>
                            <option value="<?php echo htmlspecialchars($iconClass); ?>"><?php echo htmlspecialchars($label); ?></option>
                        <?php endforeach; ?>
                    </datalist>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Display Order</label>
                        <input type="number" name="display_order" id="fOrder" class="form-input" value="0" min="0">
                    </div>
                    <div class="form-group">
                        <label style="visibility:hidden;">&nbsp;</label>
                        <div class="form-check" style="align-items:center;gap:8px;">
                            <input type="checkbox" name="is_active" id="fActive" value="1" checked>
                            <label for="fActive" style="color:var(--dashboard-text);font-size:14px;font-weight:400;">Visible on site</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeServiceModal()">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> <span id="modalSubmitLabel">Create Service</span></button>
            </div>
        </form>
    </div>
</div>

<script src="../assets/js/sidebar.js"></script>
<script src="../assets/js/loader-service.js"></script>
<script>
    function openServiceModal() { document.getElementById('serviceModal').classList.add('open'); document.body.style.overflow = 'hidden'; }
    function closeServiceModal() { document.getElementById('serviceModal').classList.remove('open'); document.body.style.overflow = ''; }
    function filterServices() { var q = document.getElementById('svcSearch').value.toLowerCase(); document.querySelectorAll('#svcTable tbody tr').forEach(function(r){ r.style.display = r.dataset.keyword.indexOf(q) !== -1 ? '' : 'none'; }); }
    function editService(id) {
        <?php foreach ($services as $s): ?>
        if (id === <?php echo (int)$s['id']; ?>) {
            document.getElementById('modalTitle').innerHTML = 'Edit Service';
            document.getElementById('formAction').value = 'update';
            document.getElementById('formId').value = <?php echo (int)$s['id']; ?>;
            document.getElementById('fTitle').value = <?php echo json_encode($s['title']); ?>;
            document.getElementById('fDesc').value = <?php echo json_encode($s['description'] ?? ''); ?>;
            document.getElementById('fIcon').value = <?php echo json_encode($s['icon'] ?: 'bi-star'); ?>;
            document.getElementById('fOrder').value = <?php echo (int)$s['display_order']; ?>;
            document.getElementById('fActive').checked = <?php echo (int)$s['is_active']; ?>;
            document.getElementById('modalSubmitLabel').textContent = 'Update Service';
            openServiceModal();
        }
        <?php endforeach; ?>
    }
    document.getElementById('serviceModal').addEventListener('click', function(e){ if (e.target === this) closeServiceModal(); });
    document.addEventListener('keydown', function(e){ if (e.key === 'Escape') closeServiceModal(); });
</script>
</body>
</html>
