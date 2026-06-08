<?php
session_start();
require_once "../config/database.php";
require_once "../config/functions.php";
require_once "../models/Downloads.php";

if (!isLogged()) { header("Location: ../login.php"); exit; }

$currentRole = currentRole();
$currentUser = currentUser();

if ($currentRole !== "PRO") { header("Location: ../index.php"); exit; }

$pageTitle = "Manage Downloads";
$db = Database::getInstance();
$downloadsModel = new Downloads($db);

// Handle GET delete
if (isset($_GET["delete"]) && is_numeric($_GET["delete"])) {
    $db->delete("downloads", ["id" => (int)$_GET["delete"]]);
    $_SESSION["success"] = "Download deleted successfully.";
    header("Location: downloads.php");
    exit;
}

// Handle POST actions
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";
    
    if ($action === "create_category") {
        $db->insert("download_categories", [
            "name" => trim($_POST["name"] ?? ""),
            "icon" => trim($_POST["icon"] ?? "bi-folder"),
            "display_order" => (int)($_POST["display_order"] ?? 0)
        ]);
        $_SESSION["success"] = "Category created successfully.";
        header("Location: downloads.php");
        exit;
    }
    
    if ($action === "create_download") {
        if (isset($_FILES["file"]) && $_FILES["file"]["error"] === UPLOAD_ERR_OK) {
            $uploadDir = "../uploads/downloads/";
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            
            $fileExt = pathinfo($_FILES["file"]["name"], PATHINFO_EXTENSION);
            $fileName = "dl_" . time() . "_" . uniqid() . "." . $fileExt;
            $filePath = "uploads/downloads/" . $fileName;
            
            if (move_uploaded_file($_FILES["file"]["tmp_name"], $uploadDir . $fileName)) {
                $db->insert("downloads", [
                    "category_id" => (int)($_POST["category_id"] ?? 1),
                    "title" => trim($_POST["title"] ?? ""),
                    "file_path" => $filePath,
                    "file_size" => $_FILES["file"]["size"],
                    "is_active" => 1
                ]);
                $_SESSION["success"] = "Download added successfully.";
            } else {
                $_SESSION["error"] = "Failed to upload file.";
            }
        } else {
            $_SESSION["error"] = "Please select a valid file.";
        }
        header("Location: downloads.php");
        exit;
    }
}

$categories = $db->fetchAll("SELECT * FROM download_categories ORDER BY display_order ASC");
$downloads = $downloadsModel->getAllActive();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $pageTitle; ?> | DHLTU SRC Admin</title>
  <link rel="stylesheet" href="../assets/css/sidebar.css">
  <link rel="stylesheet" href="../assets/css/dashboard.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
  <script>window.currentUserRole = "<?php echo $currentRole; ?>";</script>
  <style>
    .modal-overlay { display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(10,22,40,0.85);z-index:1000;align-items:center;justify-content:center; }
    .modal-overlay.active { display:flex; }
    .modal-wrap { background:#fff;border:1px solid rgba(201,168,76,0.25);border-radius:12px;max-width:500px;width:90%;max-height:90vh;overflow-y:auto; }
    .modal-header-custom { padding:24px;border-bottom:1px solid rgba(201,168,76,0.12);display:flex;justify-content:space-between;align-items:center; }
    .modal-header-custom h3 { margin:0;color:#0A1628; }
    .modal-header-close { background:none;border:none;font-size:24px;cursor:pointer;color:#8A9BB8; }
    .modal-header-close:hover { background:rgba(10,22,40,0.05);color:#0A1628; }
    .modal-form-wrap { padding:24px; }
    .modal-actions { display:flex;gap:12px;justify-content:flex-end;margin-top:20px; }
    .modal-hint { font-size:11px;color:#8A9BB8;margin-top:4px; }
    .section-header { margin-bottom: 24px; }
    .section-title { font-size: 18px; font-weight: 600; color: var(--cream); margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
    .empty-state { text-align: center; padding: 40px; color: var(--text-muted); }
    .download-icon { font-size: 32px; color: var(--gold); margin-bottom: 12px; }
  </style>
</head>
<body>
<div class="dashboard-layout">
  <div class="mobile-overlay" id="mobileOverlay"></div>
  <aside class="sidebar" id="sidebar">
    <button class="sidebar-toggle" id="sidebarToggle"><i class="bi bi-chevron-left"></i></button>
    <div class="sidebar-header"><div class="sidebar-logo">SRC</div><span class="sidebar-title">Dashboard</span></div>
    <?php require_once "../include/nav-links.php"; $nav = new NavigationRBAC($currentRole); echo $nav->renderNavigation(); ?>
    <div class="sidebar-user">
      <div class="user-avatar"><?php echo strtoupper(substr($currentUser["first_name"],0,1).substr($currentUser["last_name"],0,1)); ?></div>
      <div class="user-info"><div class="user-name"><?php echo htmlspecialchars($currentUser["first_name"]." ".$currentUser["last_name"]); ?></div><div class="user-role"><span class="role-badge admin"><?php echo $currentRole; ?></span></div></div>
    </div>
  </aside>
  <div class="main-content">
    <header class="dashboard-header">
      <button class="mobile-toggle" id="mobileToggle"><i class="bi bi-list"></i></button>
      <h1 class="header-title">Downloads Management</h1>
      <div class="header-actions">
        <button class="header-btn" onclick="openModal()" style="background:var(--dashboard-primary);color:#0a1628;" title="Add Download"><i class="bi bi-plus-lg"></i></button>
        <button class="header-btn" onclick="openCategoryModal()" style="background:var(--dashboard-primary);color:#0a1628;" title="Add Category"><i class="bi bi-folder-plus"></i></button>
        <a href="../logout.php" class="header-btn" title="Logout"><i class="bi bi-box-arrow-right"></i></a>
      </div>
    </header>
    <main class="content-body">
      <div class="dashboard-container">
        <?php if (!empty($_SESSION["success"])): ?>
        <div style="background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.2);border-radius:8px;padding:16px;margin-bottom:20px;color:#22c55e;"><?php echo $_SESSION["success"]; unset($_SESSION["success"]); ?></div>
        <?php endif; ?>
        <?php if (!empty($_SESSION["error"])): ?>
        <div style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.2);border-radius:8px;padding:16px;margin-bottom:20px;color:#ef4444;"><?php echo $_SESSION["error"]; unset($_SESSION["error"]); ?></div>
        <?php endif; ?>
        
        <div class="section-header">
          <h2 class="section-title"><i class="bi bi-download"></i> Download Files</h2>
          <p style="color:var(--text-muted);margin:0;">Manage downloadable documents and resources</p>
        </div>
        
        <div class="table-container">
          <table class="table">
            <thead>
              <tr>
                <th>#</th>
                <th>Title</th>
                <th>Category</th>
                <th>Type</th>
                <th>Size</th>
                <th>Downloads</th>
                <th style="text-align:right;">Actions</th>
              </tr>
            </thead>
            <tbody>
            <?php if (empty($downloads)): ?>
              <tr>
                <td colspan="7" class="empty-state">
                  <i class="bi bi-folder-x download-icon"></i>
                  <p>No downloads available. Click "+" to add files.</p>
                </td>
              </tr>
            <?php else: ?>
            <?php foreach ($downloads as $i => $d): ?>
              <tr>
                <td><?php echo $i + 1; ?></td>
                <td><?php echo htmlspecialchars($d["title"]); ?></td>
                <td><?php echo htmlspecialchars($d["category_name"] ?? "Uncategorized"); ?></td>
                <td><span class="badge"><?php echo strtoupper(pathinfo($d["file_path"] ?? "", PATHINFO_EXTENSION)); ?></span></td>
                <td><?php echo $downloadsModel->getFileSize($d["file_size"] ?? 0); ?></td>
                <td><?php echo $d["download_count"] ?? 0; ?></td>
<td style="text-align:right;">
                   <a href="?delete=<?php echo $d["id"]; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this download?')" title="Delete"><i class="bi bi-trash"></i></a>
                 </td>
              </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>
</div>

<!-- Add Download Modal -->
<div id="downloadModal" class="modal-overlay">
  <div class="modal-wrap">
    <div class="modal-header-custom">
      <h3>Add New Download</h3>
      <button type="button" class="modal-header-close" onclick="closeModal()">&times;</button>
    </div>
    <form method="POST" enctype="multipart/form-data" class="modal-form-wrap">
      <input type="hidden" name="action" value="create_download">
      <div class="form-group">
        <label class="form-label">Title *</label>
        <input type="text" name="title" class="form-input" required placeholder="e.g., SRC Constitution 2024/25">
      </div>
      <div class="form-group">
        <label class="form-label">Category *</label>
        <select name="category_id" class="form-input" required>
          <option value="">Select a category</option>
          <?php foreach ($categories as $cat): ?>
          <option value="<?php echo $cat["id"]; ?>"><?php echo htmlspecialchars($cat["name"]); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">File *</label>
        <input type="file" name="file" class="form-input" required accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.zip,.rar,.jpg,.jpeg,.png,.gif">
        <div class="modal-hint">Supported: PDF, Word, Excel, PowerPoint, Text, Images, ZIP</div>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="bi bi-upload"></i> Upload File</button>
      </div>
    </form>
  </div>
</div>

<!-- Add Category Modal -->
<div id="categoryModal" class="modal-overlay">
  <div class="modal-wrap">
    <div class="modal-header-custom">
      <h3>Add New Category</h3>
      <button type="button" class="modal-header-close" onclick="closeCategoryModal()">&times;</button>
    </div>
    <form method="POST" class="modal-form-wrap">
      <input type="hidden" name="action" value="create_category">
      <div class="form-group">
        <label class="form-label">Category Name *</label>
        <input type="text" name="name" class="form-input" required placeholder="e.g., Annual Reports">
      </div>
      <div class="form-group">
        <label class="form-label">Icon Class</label>
        <input type="text" name="icon" class="form-input" value="bi-folder" placeholder="e.g., bi-file-pdf">
        <div class="modal-hint">Bootstrap Icons: bi-folder, bi-file-pdf, bi-file-word, etc.</div>
      </div>
      <div class="form-group">
        <label class="form-label">Display Order</label>
        <input type="number" name="display_order" class="form-input" value="0" min="0">
      </div>
      <div class="modal-actions">
        <button type="button" class="btn btn-outline" onclick="closeCategoryModal()">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="bi bi-plus"></i> Add Category</button>
      </div>
    </form>
  </div>
</div>

  <script>
function openModal()   { var el = document.getElementById("downloadModal");   el.style.display="flex"; document.body.style.overflow = "hidden"; }
function closeModal()  { document.getElementById("downloadModal").style.display  = "none"; document.body.style.overflow = ""; }
function openCategoryModal()  { document.getElementById("categoryModal").style.display  = "flex"; document.body.style.overflow = "hidden"; }
function closeCategoryModal() { document.getElementById("categoryModal").style.display = "none"; }

document.getElementById("downloadModal").addEventListener("click", function(e) { if (e.target === this) closeModal(); });
document.getElementById("categoryModal").addEventListener("click", function(e) { if (e.target === this) closeCategoryModal(); });
document.addEventListener("keydown", function(e) { if (e.key === "Escape") { closeModal(); closeCategoryModal(); } });
</script>
<script src="../assets/js/sidebar.js"></script>
</body>
</html>
