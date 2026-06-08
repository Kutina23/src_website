<?php
session_start();
require_once "../config/database.php";
require_once "../config/functions.php";
require_once "../models/Constitution.php";

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

$pageTitle = "Constitution Management";
$error = $_SESSION["error"] ?? null;
$success = $_SESSION["success"] ?? null;
unset($_SESSION["error"], $_SESSION["success"]);

$model = new Constitution(db());
$constitutions = $model->getAll();
$activeConstitution = $model->getActive();

// ── Edit state ──────────────────────────────────────────────
$editItem     = null;
$isEditing    = false;
$action       = $_GET["action"] ?? "";
$editId       = $_GET["id"] ?? null;

if ($action === "edit" && $editId) {
    $editItem = $model->getById((int)$editId);
}

// ── POST handlers ───────────────────────────────────────────
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // ── CREATE (upload new) ─────────────────────────────────
    if ($_POST["form_action"] ?? "" === "upload_new") {
        if (!isset($_FILES["constitution_file"]) || $_FILES["constitution_file"]["error"] !== UPLOAD_ERR_OK) {
            $_SESSION["error"] = "Please select a valid PDF file.";
            header("Location: constitution-upload.php");
            exit;
        }

        $file     = $_FILES["constitution_file"];
        $finfo    = new finfo(FILEINFO_MIME_TYPE);
        $mime     = $finfo->file($file["tmp_name"]);

        if ($mime !== "application/pdf") {
            $_SESSION["error"] = "Only PDF files are allowed.";
            header("Location: constitution-upload.php");
            exit;
        }

        if ($file["size"] > 10 * 1024 * 1024) {
            $_SESSION["error"] = "File size exceeds 10MB limit.";
            header("Location: constitution-upload.php");
            exit;
        }

        $uploadDir      = "../assets/documents/";
        $originalName   = $file["name"];
        $fileSize       = $file["size"];
        $fileName       = "constitution_" . time() . "_" . preg_replace("/[^A-Za-z0-9.\-]/", "_", $originalName);
        $filePath       = $uploadDir . $fileName;

        if (!move_uploaded_file($file["tmp_name"], $filePath)) {
            $_SESSION["error"] = "Failed to upload file. Check directory permissions.";
            header("Location: constitution-upload.php");
            exit;
        }

        $data = [
            "title"             => trim($_POST["title"] ?? "SRC Constitution"),
            "file_path"         => "assets/documents/" . $fileName,
            "original_filename" => $originalName,
            "file_size"         => $fileSize,
            "uploaded_by"       => $_SESSION["user_id"],
            "version"           => trim($_POST["version"] ?? "1.0"),
            "is_active"         => true
        ];

        $model->create($data);
        logActivity("constitution_uploaded", $_SESSION["user_id"], ["title" => $data["title"]]);
        $_SESSION["success"] = "Constitution uploaded successfully.";
        header("Location: constitution-upload.php");
        exit;
    }

    // ── UPDATE metadata or replace file ─────────────────────
    if ($_POST["form_action"] ?? "" === "update_constitution") {
        $uid = (int)($_POST["id"] ?? 0);
        $item = $model->getById($uid);
        if (!$item) {
            $_SESSION["error"] = "Item not found.";
            header("Location: constitution-upload.php");
            exit;
        }

        $updateData = [
            "title"   => trim($_POST["title"] ?? $item["title"]),
            "version" => trim($_POST["version"] ?? $item["version"]),
        ];

        // Only update file if a new file was provided
        if (isset($_FILES["constitution_file"]) && $_FILES["constitution_file"]["error"] === UPLOAD_ERR_OK) {
            $file  = $_FILES["constitution_file"];
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime  = $finfo->file($file["tmp_name"]);

            if ($mime !== "application/pdf") {
                $_SESSION["error"] = "Only PDF files are allowed.";
                header("Location: constitution-upload.php?action=edit&id={$uid}");
                exit;
            }

            if ($file["size"] > 10 * 1024 * 1024) {
                $_SESSION["error"] = "File size exceeds 10MB limit.";
                header("Location: constitution-upload.php?action=edit&id={$uid}");
                exit;
            }

            $uploadDir    = "../assets/documents/";
            $originalName = $file["name"];
            $fileSize     = $file["size"];
            $fileName     = "constitution_" . $item["id"] . "_" . time() . "_" . preg_replace("/[^A-Za-z0-9.\-]/", "_", $originalName);
            $filePath     = $uploadDir . $fileName;

            if (!move_uploaded_file($file["tmp_name"], $filePath)) {
                $_SESSION["error"] = "Failed to upload replacement file.";
                header("Location: constitution-upload.php?action=edit&id={$uid}");
                exit;
            }

            // Delete old file
            $oldFull = "../" . $item["file_path"];
            if (file_exists($oldFull)) { unlink($oldFull); }

            $updateData["file_path"]         = "assets/documents/" . $fileName;
            $updateData["original_filename"] = $originalName;
            $updateData["file_size"]         = $fileSize;
        }

        $model->update($uid, $updateData);
        logActivity("constitution_updated", $_SESSION["user_id"], ["id" => $uid]);
        $_SESSION["success"] = "Constitution updated successfully.";
        header("Location: constitution-upload.php");
        exit;
    }

    // ── SET ACTIVE ──────────────────────────────────────────
    if ($_POST["form_action"] ?? "" === "set_active") {
        $id = (int)($_POST["constitution_id"] ?? 0);
        if ($id > 0) {
            $model->setActive($id);
            logActivity("constitution_activated", $_SESSION["user_id"], ["constitution_id" => $id]);
            $_SESSION["success"] = "Constitution activated successfully.";
        }
        header("Location: constitution-upload.php");
        exit;
    }

    // ── DELETE ──────────────────────────────────────────────
    if ($_POST["form_action"] ?? "" === "delete") {
        $id = (int)($_POST["constitution_id"] ?? 0);
        if ($id > 0) {
            $const = $model->getById($id);
            if ($const && (int)$const["is_active"]) {
                $_SESSION["error"] = "Cannot delete the active constitution. Please activate another version first.";
            } else {
                $fullPath = "../" . $const["file_path"];
                if (file_exists($fullPath)) { unlink($fullPath); }
                $model->delete($id);
                logActivity("constitution_deleted", $_SESSION["user_id"], ["constitution_id" => $id]);
                $_SESSION["success"] = "Constitution deleted successfully.";
            }
        }
        header("Location: constitution-upload.php");
        exit;
    }
}

// ── Helpers ─────────────────────────────────────────────────
function fmtSize($bytes) {
    if ($bytes >= 1048576) return round($bytes / 1048576, 2) . " MB";
    if ($bytes >= 1024)     return round($bytes / 1024, 1)     . " KB";
    return $bytes . " B";
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
                <span class="sidebar-title">DHLTU Admin</span>
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
                <h1 class="header-title">Constitution Management</h1>
                <div class="header-actions"><a href="../logout.php" class="header-btn"><i class="bi bi-box-arrow-right"></i></a></div>
            </header>
            <main class="content-body">
                <div class="dashboard-container">
                    <div class="dashboard-header-section">
                        <h2 class="dashboard-title">Constitution Upload</h2>
                        <p class="dashboard-subtitle">Upload and manage the SRC Constitution PDF document</p>
                    </div>

                    <?php if ($error): ?>
                        <div style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.2);border-radius:8px;padding:14px 18px;margin-bottom:20px;color:#ef4444;">
                            <i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div style="background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.2);border-radius:8px;padding:14px 18px;margin-bottom:20px;color:#22c55e;">
                            <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                        </div>
                    <?php endif; ?>

                    <!-- ── UPLOAD + EDIT PANEL ─────────────────────── -->
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:24px;">

                        <!-- UPLOAD NEW -->
                        <div style="background:var(--card-bg);border:1px solid rgba(138,155,184,0.1);border-radius:12px;padding:24px;">
                            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
                                <h3 style="margin:0;font-family:var(--font-display);"><i class="bi bi-upload" style="color:var(--gold);margin-right:8px;"></i>Upload New Constitution</h3>
                                <?php if ($editItem): ?>
                                <a href="constitution-upload.php" style="font-size:11px;letter-spacing:0.08em;text-transform:uppercase;color:var(--text-muted);text-decoration:none;">Cancel Edit ✕</a>
                                <?php endif; ?>
                            </div>

                            <?php if ($editItem): ?>
                                <!-- ─── EDIT FORM ────────────────────────────────── -->
                                <form method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="form_action" value="update_constitution">
                                    <input type="hidden" name="id" value="<?= $editItem['id'] ?>">
                                    <div class="form-group">
                                        <label class="form-label">Title</label>
                                        <input type="text" name="title" class="form-input" value="<?= htmlspecialchars($editItem['title']) ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Version</label>
                                        <input type="text" name="version" class="form-input" value="<?= htmlspecialchars($editItem['version']) ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Replace PDF File <span style="font-weight:400;font-size:11px;color:var(--text-muted);">(leave blank to keep current file)</span></label>
                                        <input type="file" name="constitution_file" accept=".pdf" class="form-input">
                                        <div style="margin-top:8px;padding:10px 14px;background:rgba(201,168,76,0.05);border:1px solid rgba(201,168,76,0.15);border-radius:6px;display:flex;align-items:center;gap:12px;">
                                            <i class="bi bi-file-earmark-pdf" style="font-size:22px;color:var(--gold);"></i>
                                            <div style="flex:1;">
                                                <div style="font-size:13px;font-weight:500;"><?= htmlspecialchars($editItem['original_filename']) ?></div>
                                                <div style="font-size:11px;color:var(--text-muted);">Version <?= htmlspecialchars($editItem['version']) ?> · <?= fmtSize($editItem['file_size']) ?></div>
                                            </div>
                                        </div>
                                        <small style="color:var(--text-muted);margin-top:4px;display:block;">Max 10 MB. Current file reverted if no replacement provided.</small>
                                    </div>
                                    <div style="display:flex;gap:10px;margin-top:8px;justify-content:flex-end;">
                                        <a href="constitution-upload.php" class="btn btn-outline">Cancel</a>
                                        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Save Changes</button>
                                    </div>
                                </form>
                                <!-- ─── END EDIT FORM ─────────────────────────────── -->
                            <?php else: ?>
                                <!-- ─── CREATE / UPLOAD FORM ──────────────────────── -->
                                <form method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="form_action" value="upload_new">
                                    <div class="form-group">
                                        <label class="form-label">Title</label>
                                        <input type="text" name="title" class="form-input" value="SRC Constitution" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Version</label>
                                        <input type="text" name="version" class="form-input" value="1.0" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">PDF File</label>
                                        <input type="file" name="constitution_file" accept=".pdf" class="form-input" required>
                                        <small style="color:var(--text-muted);margin-top:4px;display:block;">Only PDF files are allowed (max 10MB)</small>
                                    </div>
                                    <button type="submit" class="btn btn-primary"><i class="bi bi-upload"></i> Upload Constitution</button>
                                </form>
                                <!-- ─── END CREATE FORM ────────────────────────────── -->
                            <?php endif; ?>
                        </div>

                        <!-- EDIT EXISTING (inline shortcut) -->
                        <div style="background:var(--card-bg);border:1px solid rgba(138,155,184,0.1);border-radius:12px;padding:24px;">
                            <h3 style="margin-top:0;font-family:var(--font-display);color:var(--text-muted);">
                                <i class="bi bi-tools" style="color:var(--text-muted);margin-right:8px;"></i>Quick Actions
                            </h3>
                            <div style="display:flex;flex-direction:column;gap:12px;">
                                <div style="padding:20px;border:1px solid rgba(201,168,76,0.15);border-radius:8px;background:rgba(201,168,76,0.03);">
                                    <div style="font-size:11px;text-transform:uppercase;letter-spacing:0.1em;color:var(--gold);margin-bottom:8px;">Edit Existing Record</div>
                                    <div style="font-size:13px;color:var(--text-muted);margin-bottom:12px;">Select a constitution from the history table below and click the pencil icon to edit its title, version, or replace its PDF file.</div>
                                    <div style="font-size:12px;color:var(--text-muted);"><i class="bi bi-arrow-left"></i> Scroll down to History → click <i class="bi bi-pencil" style="font-size:10px;"></i></div>
                                </div>
                                <?php if ($activeConstitution): ?>
                                <div style="padding:16px;border:1px solid rgba(138,155,184,0.1);border-radius:8px;display:flex;gap:12px;align-items:center;">
                                    <i class="bi bi-file-earmark-pdf" style="font-size:24px;color:var(--gold);"></i>
                                    <div>
                                        <div style="font-weight:500;font-size:14px;"><?php echo htmlspecialchars($activeConstitution["title"]); ?></div>
                                        <small style="color:var(--text-muted);">Active · v<?php echo htmlspecialchars($activeConstitution["version"]); ?> · <?php echo fmtSize($activeConstitution["file_size"]); ?></small>
                                    </div>
                                </div>
                                <div style="display:flex;gap:8px;">
                                    <a href="../<?php echo $activeConstitution["file_path"]; ?>" class="btn btn-sm btn-outline" target="_blank"><i class="bi bi-eye"></i> Preview</a>
                                    <a href="../<?php echo $activeConstitution["file_path"]; ?>" class="btn btn-sm btn-primary" download><i class="bi bi-download"></i> Download</a>
                                </div>
                                <?php else: ?>
                                <div style="padding:20px;border:1px dashed rgba(138,155,184,0.2);border-radius:8px;text-align:center;color:var(--text-muted);font-size:13px;">No constitution uploaded yet.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- ── CONSTITUTION HISTORY TABLE ────────────────── -->
                    <?php if (!empty($constitutions)): ?>
                    <div style="background:var(--card-bg);border:1px solid rgba(138,155,184,0.1);border-radius:12px;overflow:hidden;">
                        <div style="padding:16px 24px;border-bottom:1px solid rgba(138,155,184,0.1);">
                            <h3 style="margin:0;"><i class="bi bi-clock-history" style="color:var(--gold);margin-right:8px;"></i>Constitution History</h3>
                        </div>
                        <div class="table-container" style="border:none;">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Title</th>
                                        <th>Filename</th>
                                        <th>Version</th>
                                        <th>Size</th>
                                        <th>Uploaded By</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th style="text-align:right;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($constitutions as $index => $c): ?>
                                    <tr style="<?= $c['is_active'] ? 'background:rgba(201,168,76,0.04);' : '' ?>">
                                        <td style="color:var(--text-muted);font-size:12px;"><?= $index + 1 ?></td>
                                        <td><strong><?= htmlspecialchars($c["title"]) ?></strong></td>
                                        <td style="font-size:12px;color:var(--text-muted);max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= htmlspecialchars($c["original_filename"]) ?>"><?= htmlspecialchars($c["original_filename"]) ?></td>
                                        <td><span class="badge badge-role" style="font-size:10px;">v<?= htmlspecialchars($c["version"]) ?></span></td>
                                        <td style="color:var(--text-muted);font-size:12px;"><?= fmtSize($c["file_size"]) ?></td>
                                        <td style="color:var(--text-muted);font-size:12px;">User #<?= (int)$c["uploaded_by"] ?></td>
                                        <td style="color:var(--text-muted);font-size:12px;"><?= date("M d, Y", strtotime($c["created_at"])) ?></td>
                                        <td>
                                            <?php if ($c["is_active"]): ?>
                                                <span class="badge badge-active" style="background:rgba(34,197,94,0.15);color:#22c55e;border:1px solid rgba(34,197,94,0.2);">Active</span>
                                            <?php else: ?>
                                                <span class="badge" style="background:rgba(138,155,184,0.1);color:var(--text-muted);border:1px solid rgba(138,155,184,0.15);">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align:right;">
                                            <div style="display:flex;gap:6px;justify-content:flex-end;align-items:center;">
                                                <!-- Preview -->
                                                <a href="../<?php echo $c["file_path"]; ?>" class="btn btn-sm btn-outline" style="padding:5px 10px;font-size:12px;" target="_blank" title="Preview">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <!-- Download -->
                                                <a href="../<?php echo $c["file_path"]; ?>" class="btn btn-sm btn-outline" style="padding:5px 10px;font-size:12px;" download title="Download">
                                                    <i class="bi bi-download"></i>
                                                </a>
                                                <!-- Edit -->
                                                <a href="?action=edit&id=<?= $c["id"] ?>" class="btn btn-sm btn-outline" style="padding:5px 10px;font-size:12px;" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <!-- Activate -->
                                                <?php if (!$c["is_active"]): ?>
                                                <form method="POST" style="display:inline;" onsubmit="return confirm('Activate this constitution version?');">
                                                    <input type="hidden" name="form_action" value="set_active">
                                                    <input type="hidden" name="constitution_id" value="<?= $c["id"] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline" style="padding:5px 10px;font-size:12px;color:#22c55e;border-color:rgba(34,197,94,0.3);" title="Set Active">
                                                        <i class="bi bi-check-circle"></i>
                                                    </button>
                                                </form>
                                                <?php else: ?>
                                                <span style="font-size:11px;color:var(--text-muted);" title="Cannot deactivate the only active record"><i class="bi bi-shield-check"></i></span>
                                                <?php endif; ?>
                                                <!-- Delete -->
                                                <?php if (!$c["is_active"]): ?>
                                                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this constitution permanently?');">
                                                    <input type="hidden" name="form_action" value="delete">
                                                    <input type="hidden" name="constitution_id" value="<?= $c["id"] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline" style="padding:5px 10px;font-size:12px;color:#ef4444;border-color:rgba(239,68,68,0.2);" title="Delete">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                                <?php else: ?>
                                                <span style="font-size:11px;color:var(--text-muted);display:inline-block;" title="Cannot delete while active"><i class="bi bi-lock"></i></span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <script src="../assets/js/sidebar.js"></script>
</body>
</html>
