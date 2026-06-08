<?php
session_start();
require_once '../config/database.php';
require_once '../config/functions.php';
require_once '../models/Projects.php';

if (!isLogged()) { header('Location: ../login.php'); exit; }

$currentRole = currentRole();
$currentUser = currentUser();

if ($currentRole === 'STUDENT') { header('Location: ../index.php'); exit; }

$pageTitle   = 'SRC Projects';
$db          = Database::getInstance();
$projModel   = new Projects($db);

// ── Handle GET delete (table action links use GET) ───────────────────────
$action = $_GET['action'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'delete' && isset($_GET['id'])) {
    $projModel->delete((int)$_GET['id']);
    $_SESSION['success'] = 'Project deleted.';
    header('Location: projects.php');
    exit;
}

// ── Handle POST (create / edit) ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id    = (int)($_POST['id'] ?? 0);

    if ($action === 'create' || $action === 'edit') {
        $data = [
            'title'         => trim($_POST['title'] ?? ''),
            'description'   => trim($_POST['description'] ?? ''),
            'category'      => $_POST['category'] ?? 'Other',
            'image_path'    => '',
            'media_id'      => null,
            'link_url'      => trim($_POST['link_url'] ?? ''),
            'status'        => $_POST['status'] ?? 'upcoming',
            'display_order' => (int)($_POST['display_order'] ?? 0),
            'is_active'     => isset($_POST['is_active']) ? 1 : 0,
        ];

        // Handle image upload
        if (isset($_FILES['project_image']) && $_FILES['project_image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = "../uploads/projects/";
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            $fileExt  = pathinfo($_FILES['project_image']['name'], PATHINFO_EXTENSION);
            $fileName = "proj_" . time() . "_" . uniqid() . "." . $fileExt;
            $filePath = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['project_image']['tmp_name'], $filePath)) {
                $mediaId = db()->insert('media', [
                    'file_name' => $_FILES['project_image']['name'],
                    'file_path' => "uploads/projects/" . $fileName,
                    'file_type' => 'IMAGE',
                    'mime_type' => mime_content_type($filePath),
                    'file_size' => $_FILES['project_image']['size'],
                    'alt_text'  => trim($_POST['title'] ?? ''),
                    'uploaded_by' => $_SESSION['user_id']
                ]);
                $data['media_id']   = (int)$mediaId;
                $data['image_path'] = "uploads/projects/" . $fileName;
            }
        }

        if ($action === 'create') {
            $projModel->create($data);
            $_SESSION['success'] = 'Project created.';
        } else {
            $projModel->update($id, $data);
            $_SESSION['success'] = 'Project updated.';
        }
        header('Location: projects.php');
        exit;
    }
}

// ── Fetch all projects for the listing table ─────────────────────────────
$allProjects = $projModel->getAll(['include_inactive' => true]);
$categories  = $projModel->getAllCategories();

// Single project for the edit modal
$editProject = null;
if (isset($_GET['edit_id'])) {
    $editProject = $projModel->getById((int)$_GET['edit_id']);
}

// Single project for the view-details modal
$viewProject = null;
if (isset($_GET['view_id'])) {
    $viewProject = $projModel->getById((int)$_GET['view_id']);
}

$errors   = $_SESSION['errors']   ?? [];
$success  = $_SESSION['success']  ?? '';
unset($_SESSION['errors'], $_SESSION['success']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $pageTitle; ?> | DHLTU SRC</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;0,700;1,300,400&family=Outfit:wght@200;300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
  <link rel="stylesheet" href="../assets/css/sidebar.css">
  <link rel="stylesheet" href="../assets/css/dashboard.css">
  <link rel="stylesheet" href="../assets/css/alerts.css">
  <script>window.currentUserRole = '<?php echo $currentRole; ?>';</script>
<link rel="icon" type="image/png" href="../assets/images/logo.png">
</head>
<body>
<div class="dashboard-layout">
  <div class="mobile-overlay" id="mobileOverlay"></div>
  <aside class="sidebar" id="sidebar">
    <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar"><i class="bi bi-chevron-left"></i></button>
    <div class="sidebar-header">
      <div class="sidebar-logo">SRC</div>
      <span class="sidebar-title">DHLTU Dashboard</span>
    </div>
    <?php require_once '../include/nav-links.php'; $nav = new NavigationRBAC($currentRole); echo $nav->renderNavigation(); ?>
    <div class="sidebar-user">
      <div class="user-avatar"><?php echo strtoupper(substr($currentUser['first_name'],0,1).substr($currentUser['last_name'],0,1)); ?></div>
      <div class="user-info">
        <div class="user-name"><?php echo htmlspecialchars($currentUser['first_name'].' '.$currentUser['last_name']); ?></div>
        <div class="user-role"><span class="role-badge admin"><?php echo $currentRole; ?></span></div>
      </div>
    </div>
  </aside>

  <div class="main-content">
    <header class="dashboard-header">
      <button class="mobile-toggle" id="mobileToggle" aria-label="Open menu"><i class="bi bi-list"></i></button>
      <h1 class="header-title">SRC Projects</h1>
      <div class="header-actions">
        <button class="header-btn" title="Add Project" onclick="openModal()" style="background:var(--dashboard-primary);color:#0a1628;">
          <i class="bi bi-plus"></i>
        </button>
        <a href="../logout.php" class="header-btn" aria-label="Logout"><i class="bi bi-box-arrow-right"></i></a>
      </div>
    </header>

    <main class="content-body">
      <div class="dashboard-container">
        <div class="dashboard-header-section">
          <h2 class="dashboard-title">Manage Projects &amp; Initiatives</h2>
          <p class="dashboard-subtitle">Create and manage ongoing and upcoming SRC projects</p>
        </div>

        <?php if (!empty($errors)): ?>
        <div style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.2);border-radius:8px;padding:16px;margin-bottom:20px;">
          <ul style="margin:0;padding-left:20px;color:#ef4444;">
            <?php foreach ((array)$errors as $err): ?><li><?php echo htmlspecialchars($err); ?></li><?php endforeach; ?>
          </ul>
        </div>
        <?php endif; ?>
        <?php if ($success): ?>
        <div style="background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.2);border-radius:8px;padding:16px;margin-bottom:20px;color:#22c55e;"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <div class="table-container">
          <table class="table">
            <thead>
              <tr>
                <th>#</th>
                <th>Project</th>
                <th>Category</th>
                <th>Status</th>
                <th>Order</th>
                <th>Active</th>
                <th style="text-align:right;">Actions</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($allProjects as $i => $p): ?>
              <tr>
                <td><?php echo $i + 1; ?></td>
                <td>
                  <div style="font-weight:600;color:var(--cream);"><?php echo htmlspecialchars($p['title']); ?></div>
                </td>
                <td><span class="badge"><?php echo htmlspecialchars($p['category']); ?></span></td>
                <td><span class="badge badge-<?php echo $p['status']==='ongoing'?'active':($p['status']==='upcoming'?'info':''); ?>"><?php echo ucfirst($p['status']); ?></span></td>
                <td><?php echo (int)($p['display_order'] ?? 0); ?></td>
                <td><?php echo $p['is_active'] ? '<span class="badge badge-active">Yes</span>' : '<span class="badge">No</span>'; ?></td>
                <td>
                  <div style="display:flex;gap:8px;justify-content:flex-end;">
                    <a href="projects.php?view_id=<?php echo $p['id']; ?>" class="header-btn" title="View Details" onclick="openViewModal(<?php echo (int)$p['id']; ?>);return false;"><i class="bi bi-eye"></i></a>
                    <a href="projects.php?edit_id=<?php echo $p['id']; ?>" class="header-btn" title="Edit"><i class="bi bi-pencil"></i></a>
                    <a href="projects.php?action=delete&id=<?php echo $p['id']; ?>" class="btn btn-danger" title="Delete" onclick="return confirm('Delete this project?')"><i class="bi bi-trash"></i></a>
                 </div>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($allProjects)): ?>
              <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--text-muted);">No projects yet. Click <strong>+</strong> to create the first one.</td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>
</div>

<!-- ─── Modal: Add / Edit Project ─── -->
<div id="projectModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center;">
  <div style="background:#fff;border-radius:12px;max-width:600px;width:90%;max-height:90vh;overflow-y:auto;">
    <div style="padding:24px;border-bottom:1px solid #eee;display:flex;justify-content:space-between;align-items:center;">
      <h3 style="margin:0;"><?php echo $editProject ? 'Edit Project' : 'New Project'; ?></h3>
      <button onclick="closeModal()" style="background:none;border:none;font-size:24px;cursor:pointer;">&times;</button>
    </div>
    <form method="POST" enctype="multipart/form-data" style="padding:24px;">
      <input type="hidden" name="action" value="<?php echo $editProject ? 'edit' : 'create'; ?>">
      <?php if ($editProject): ?>
        <input type="hidden" name="id" value="<?php echo (int)$editProject['id']; ?>">
      <?php endif; ?>

      <div class="form-group">
        <label class="form-label">Title *</label>
        <input type="text" name="title" class="form-input" required value="<?php echo htmlspecialchars($editProject['title'] ?? ''); ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Description</label>
        <textarea name="description" class="form-input" rows="3"><?php echo htmlspecialchars($editProject['description'] ?? ''); ?></textarea>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Category</label>
          <select name="category" class="form-input">
            <?php foreach ($categories as $cat): ?>
              <option value="<?php echo $cat; ?>" <?php echo ($editProject['category'] ?? '') === $cat ? 'selected' : ''; ?>><?php echo $cat; ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Status</label>
          <select name="status" class="form-input">
            <option value="upcoming"  <?php echo ($editProject['status'] ?? '') === 'upcoming'  ? 'selected' : ''; ?>>Upcoming</option>
            <option value="ongoing"   <?php echo ($editProject['status'] ?? '') === 'ongoing'   ? 'selected' : ''; ?>>Ongoing</option>
            <option value="completed" <?php echo ($editProject['status'] ?? '') === 'completed' ? 'selected' : ''; ?>>Completed</option>
          </select>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Project Image</label>
        <input type="file" name="project_image" class="form-input" accept="image/*">
        <?php if (!empty($editProject['image_path'])): ?>
          <div style="margin-top:8px;">
            <img src="<?php echo (strpos($editProject['image_path'], 'http') === 0) ? htmlspecialchars($editProject['image_path']) : '../' . htmlspecialchars($editProject['image_path']); ?>" style="height:80px;object-fit:cover;border-radius:4px;border:1px solid rgba(201,168,76,0.2);">
            <div style="font-size:11px;color:var(--text-muted);margin-top:4px;">Current image — upload a new file to replace it.</div>
          </div>
        <?php endif; ?>
      </div>

      <div class="form-group">
        <label class="form-label">Project Link URL <span style="color:var(--text-muted);font-size:11px;">(optional)</span></label>
        <input type="url" name="link_url" class="form-input" placeholder="https://…" value="<?php echo htmlspecialchars($editProject['link_url'] ?? ''); ?>">
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Display Order</label>
          <input type="number" name="display_order" class="form-input" min="0" value="<?php echo (int)($editProject['display_order'] ?? 0); ?>">
        </div>
        <div class="form-group" style="display:flex;align-items:flex-end;padding-bottom:12px;">
          <label style="display:flex;align-items:center;gap:8px;cursor:pointer;color:var(--text-muted);font-size:13px;">
            <input type="checkbox" name="is_active" value="1" <?php echo !($editProject['is_active'] ?? 1) ? '' : 'checked'; ?>> Active
          </label>
        </div>
       </div>
 
 <div style="display:flex;gap:12px;justify-content:flex-end;margin-top:20px;">
   <button type="button" onclick="closeModal()" class="btn btn-outline">Cancel</button>
   <button type="submit" class="btn btn-primary">
     <i class="bi bi-check"></i> <?php echo $editProject ? 'Update' : 'Create'; ?> Project
   </button>
 </div>
     </form>
   </div>
 </div>

<!-- ─── Modal: View Project Details ─── -->
<div id="viewProjectModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center;">
  <div style="background:#fff;border-radius:12px;max-width:600px;width:90%;max-height:90vh;overflow-y:auto;">
    <div style="padding:24px;border-bottom:1px solid #eee;display:flex;justify-content:space-between;align-items:center;">
      <h3 style="margin:0;">Project Details</h3>
      <button onclick="closeViewModal()" style="background:none;border:none;font-size:24px;cursor:pointer;">&times;</button>
    </div>
    <div id="viewProjectContent" class="modal-body" style="padding:24px;"></div>
  </div>
</div>

<script>
// Pre-load all project data for the view modal
const _allProjects = <?php echo json_encode(array_values($allProjects), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>;

function openModal() {
  document.getElementById('projectModal').style.display = 'flex';
  document.body.style.overflow = 'hidden';
}
function closeModal() {
  document.getElementById('projectModal').style.display = 'none';
  document.body.style.overflow = '';
}
function openViewModal(id) {
  var p = _allProjects.find(function(x) { return x.id === id; });
  if (!p) return;

  var isAbs  = function(u){ return u.indexOf('http://') === 0 || u.indexOf('https://') === 0; };
  var imgHtml = p.image_path
    ? '<img src="' + (isAbs(p.image_path) ? p.image_path : '../' + p.image_path) + '" alt="' + p.title + '" style="width:100%;max-height:220px;object-fit:cover;border-radius:8px;margin-bottom:20px;">'
    : '';
  var badgeCls  = p.status === 'ongoing' ? 'badge-active' : (p.status === 'upcoming' ? 'badge-info' : 'badge-completed');

  document.getElementById('viewProjectContent').innerHTML =
    imgHtml +
    '<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">' +
      '<span style="color:var(--text-muted);font-size:10px;letter-spacing:.12em;text-transform:uppercase;">' + p.category + '</span>' +
      '<span class="badge ' + badgeCls + '">' + (p.status ? p.status.charAt(0).toUpperCase() + p.status.slice(1) : '') + '</span>' +
    '</div>' +
    '<h3 style="font-family:Cormorant Garamond,serif;font-size:22px;color:var(--cream);margin-bottom:12px;">' + p.title + '</h3>' +
    '<p style="font-size:13px;color:var(--text-muted);line-height:1.8;margin-bottom:20px;">' + (p.description || '<em style="color:rgba(245,240,232,.35);">No description provided.</em>') + '</p>' +
    (p.link_url ? '<a href="' + p.link_url + '" target="_blank" class="btn btn-primary" style="font-size:11px;"><i class="bi bi-box-arrow-up-right"></i> Visit Link</a>' : '');

  document.getElementById('viewProjectModal').style.display = 'flex';
  document.body.style.overflow = 'hidden';
}
function closeViewModal() {
  document.getElementById('viewProjectModal').style.display = 'none';
  document.body.style.overflow = '';
}
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    var pm = document.getElementById('projectModal');
    var vm = document.getElementById('viewProjectModal');
    if (pm.style.display === 'flex') closeModal();
    else if (vm.style.display === 'flex') closeViewModal();
  }
});
document.getElementById('projectModal').addEventListener('click', function(e) { if (e.target === this) closeModal(); });
document.getElementById('viewProjectModal').addEventListener('click', function(e) { if (e.target === this) closeViewModal(); });

// Auto-open modal if edit_id is in the URL
<?php if ($editProject): ?>
document.addEventListener('DOMContentLoaded', function() { openModal(); });
<?php endif; ?>

// Auto-open view modal if view_id is in the URL
<?php if ($viewProject): ?>
document.addEventListener('DOMContentLoaded', function() { openViewModal(<?php echo (int)$viewProject['id']; ?>); });
<?php endif; ?>
</script>
<script src="../assets/js/sidebar.js"></script>
<?php if (function_exists('alert')): ?><?php echo alert()->render(); ?><?php endif; ?>
</body>
</html>
