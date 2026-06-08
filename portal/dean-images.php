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

$pageTitle = 'Dean Images';
$success = $_SESSION['success'] ?? null;
$errors = $_SESSION['errors'] ?? [];
unset($_SESSION['success'], $_SESSION['errors']);

// Get dean user
$dean = db()->fetch("SELECT id FROM users WHERE role_id = (SELECT id FROM roles WHERE name = 'DEAN') LIMIT 1");
$deanId = $dean['id'] ?? null;

// Get existing images
$images = [];
if ($deanId) {
    $images = db()->fetchAll("
        SELECT di.id, di.image_type, di.is_active, di.display_order, m.file_path, m.alt_text, m.file_size
        FROM dean_images di
        JOIN media m ON di.media_id = m.id
        WHERE di.user_id = ?
        ORDER BY di.image_type, di.display_order
    ", [$deanId]);
}

// ── DELETE ──────────────────────────────────────────────────────────────────
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $img = db()->fetch("SELECT di.id, m.file_path FROM dean_images di JOIN media m ON di.media_id = m.id WHERE di.id = ?", [$_GET['id']]);
    if ($img) {
        $filePath = '../' . $img['file_path'];
        @unlink($filePath);
        db()->execute("DELETE FROM media WHERE id = (SELECT media_id FROM dean_images WHERE id = ?)", [$_GET['id']]);
        logActivity('delete_dean_image', $_SESSION['user_id'], ['image_id' => $_GET['id']]);
    }
    $_SESSION['success'] = 'Image deleted successfully';
    header('Location: dean-images.php');
    exit;
}

// ── TOGGLE ACTIVE ───────────────────────────────────────────────────────────
if (isset($_GET['action']) && $_GET['action'] === 'toggle' && isset($_GET['id'])) {
    db()->execute("UPDATE dean_images SET is_active = NOT is_active WHERE id = ?", [$_GET['id']]);
    $_SESSION['success'] = 'Image status updated';
    header('Location: dean-images.php');
    exit;
}

// ── EDIT (load data for modal) ──────────────────────────────────────────────
$editImage = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $editImage = db()->fetch("
        SELECT di.id, di.image_type, di.is_active, di.display_order, m.file_path, m.alt_text, m.file_size
        FROM dean_images di
        JOIN media m ON di.media_id = m.id
        WHERE di.id = ?
    ", [$_GET['id']]);
}

// ── UPDATE ──────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_id'])) {
    $updateId     = (int)$_POST['update_id'];
    $altText      = trim($_POST['alt_text'] ?? '');
    $displayOrder = (int)($_POST['display_order'] ?? 0);
    $isActive     = isset($_POST['is_active']) ? 1 : 0;
    $imageType    = $_POST['image_type'] ?? 'HERO';
    $deanTitle    = $_POST['dean_title'] ?? 'Dean of Students';
    $deanSubtitle = $_POST['dean_subtitle'] ?? 'Student Affairs';
    $deanName     = $_POST['dean_name'] ?? '';

    // Save text settings
    $db = db();
    $db->execute("INSERT INTO site_settings (col_key, col_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE col_value = VALUES(col_value)", ['dean_title', $deanTitle]);
    $db->execute("INSERT INTO site_settings (col_key, col_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE col_value = VALUES(col_value)", ['dean_subtitle', $deanSubtitle]);
    $db->execute("INSERT INTO site_settings (col_key, col_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE col_value = VALUES(col_value)", ['dean_name', $deanName]);

    if ($deanId) {
        // Update dean_images record
        db()->update('dean_images', [
            'image_type'    => $imageType,
            'is_active'     => $isActive,
            'display_order' => $displayOrder
        ], ['id' => $updateId]);

        // Update media record
        $mediaRow = db()->fetch("SELECT media_id FROM dean_images WHERE id = ?", [$updateId]);
        if ($mediaRow) {
            $mediaUpdate = ['alt_text' => $altText];
            if (isset($_FILES['edit_image']) && $_FILES['edit_image']['error'] === UPLOAD_ERR_OK) {
                $old = db()->fetch("SELECT file_path FROM media WHERE id = ?", [$mediaRow['media_id']]);
                @unlink('../' . ($old['file_path'] ?? ''));

                $uploadDir = '../uploads/dean/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $fileExt = pathinfo($_FILES['edit_image']['name'], PATHINFO_EXTENSION);
                $fileName = 'dean_' . $imageType . '_' . time() . '.' . $fileExt;
                $filePath = $uploadDir . $fileName;

                if (move_uploaded_file($_FILES['edit_image']['tmp_name'], $filePath)) {
                    $mediaUpdate['file_name'] = $_FILES['edit_image']['name'];
                    $mediaUpdate['file_path'] = 'uploads/dean/' . $fileName;
                    $mediaUpdate['mime_type'] = mime_content_type($filePath);
                    $mediaUpdate['file_size'] = $_FILES['edit_image']['size'];
                }
            }
            db()->update('media', $mediaUpdate, ['id' => $mediaRow['media_id']]);
        }

        logActivity('update_dean_image', $_SESSION['user_id'], ['image_id' => $updateId]);
        $_SESSION['success'] = 'Image updated successfully';
        header('Location: dean-images.php');
        exit;
    }
}

// ── UPLOAD ──────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $imageType    = $_POST['image_type'] ?? 'HERO';
    $altText      = $_POST['alt_text'] ?? '';
    $displayOrder = (int)($_POST['display_order'] ?? 0);
    $isActive     = isset($_POST['is_active']) ? 1 : 0;
    $deanTitle    = $_POST['dean_title'] ?? 'Dean of Students';
    $deanSubtitle = $_POST['dean_subtitle'] ?? 'Student Affairs';
    $deanName     = $_POST['dean_name'] ?? ($deanId ? trim(db()->fetch("SELECT CONCAT(first_name,' ',last_name) AS name FROM users WHERE id = ?", [$deanId])['name'] ?? '') : '');

    // Save text settings
    $db = db();
    $db->execute("INSERT INTO site_settings (col_key, col_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE col_value = VALUES(col_value)", ['dean_title', $deanTitle]);
    $db->execute("INSERT INTO site_settings (col_key, col_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE col_value = VALUES(col_value)", ['dean_subtitle', $deanSubtitle]);
    $db->execute("INSERT INTO site_settings (col_key, col_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE col_value = VALUES(col_value)", ['dean_name', $deanName]);

    if ($deanId) {
        $uploadDir = '../uploads/dean/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        
        $fileExt  = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $fileName = 'dean_' . $imageType . '_' . time() . '.' . $fileExt;
        $filePath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $filePath)) {
            $mediaId = db()->insert('media', [
                'file_name'   => $_FILES['image']['name'],
                'file_path'   => 'uploads/dean/' . $fileName,
                'file_type'   => 'IMAGE',
                'mime_type'   => mime_content_type($filePath),
                'file_size'   => $_FILES['image']['size'],
                'alt_text'    => $_POST['alt_text'] ?? '',
                'uploaded_by' => $_SESSION['user_id']
            ]);
            
             db()->insert('dean_images', [
                 'user_id'       => $deanId,
                 'media_id'      => $mediaId,
                 'image_type'    => $imageType,
                 'is_active'     => $isActive,
                 'display_order' => $displayOrder
             ]);
            
            logActivity('upload_dean_image', $_SESSION['user_id'], ['type' => $imageType]);
            $_SESSION['success'] = 'Image uploaded successfully';
            header('Location: dean-images.php');
            exit;
        } else {
            $_SESSION['errors'] = ['Failed to upload image'];
        }
    }
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
                <span class="sidebar-title">DHLTU Dashboard</span>
            </div>
            <?php require_once '../include/nav-links.php'; $nav = new NavigationRBAC($currentRole); echo $nav->renderNavigation(); ?>
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
                <button class="mobile-toggle" id="mobileToggle"><i class="bi bi-list"></i></button>
                <h1 class="header-title">Dean Images</h1>
                <div class="header-actions"><a href="../logout.php" class="header-btn"><i class="bi bi-box-arrow-right"></i></a></div>
            </header>
            <main class="content-body">
                <div class="dashboard-container">
                    <div class="dashboard-header-section">
                        <h2 class="dashboard-title">Manage Dean Images</h2>
                        <p class="dashboard-subtitle">Upload and manage images for the Dean of Students</p>
                    </div>
                    <?php if ($success): ?>
                        <div style="background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.2);border-radius:8px;padding:16px;margin-bottom:20px;color:#22c55e;"><?php echo $success; ?></div>
                    <?php endif; ?>
                    <?php if (!empty($errors)): ?>
                        <?php foreach ($errors as $err): ?>
                            <div style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.2);border-radius:8px;padding:16px;margin-bottom:20px;color:#ef4444;"><?php echo htmlspecialchars($err); ?></div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <!-- Upload Form -->
                    <div class="table-container" style="padding:24px;">
                        <h3 style="padding:0 0 16px 0;margin:0 0 4px 0;">Upload New Image</h3>
                        <p class="form-text" style="margin-bottom:20px;">Add a new image for the Dean section</p>
                        <form method="POST" enctype="multipart/form-data">
                            <div class="form-group">
                                <label class="form-label">Image Type</label>
                                <select name="image_type" class="form-input">
                                    <option value="HERO">Hero Image</option>
                                    <option value="PROFILE">Profile Image</option>
                                    <option value="GALLERY">Gallery Image</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Image File</label>
                                <input type="file" name="image" class="form-input" accept="image/*" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Alt Text</label>
                                <input type="text" name="alt_text" class="form-input">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Display Order</label>
                                <input type="number" name="display_order" class="form-input" value="0" min="0">
                                <small class="form-text">Lower numbers appear first</small>
                            </div>
                            <div class="form-group form-check">
                                <input type="checkbox" name="is_active" class="form-check-input" value="1" checked>
                                <label class="form-check-label">Active</label>
                            </div>
                            <hr>
                            <h4>Dean Section Text Settings</h4>
                              <div class="form-group">
                                  <label class="form-label">Dean Title</label>
                                  <input type="text" name="dean_title" class="form-input"
                                    value="<?php
                                      $t = db()->fetch("SELECT col_value FROM site_settings WHERE `col_key` = 'dean_title'");
                                      echo htmlspecialchars($t ? $t['col_value'] : 'Dean of Students');
                                    ?>">
                              </div>
                               <div class="form-group">
                                   <label class="form-label">Dean Subtitle / Office</label>
                                   <input type="text" name="dean_subtitle" class="form-input"
                                     value="<?php
                                       $s = db()->fetch("SELECT col_value FROM site_settings WHERE `col_key` = 'dean_subtitle'");
                                       echo htmlspecialchars($s ? $s['col_value'] : 'Student Affairs');
                                     ?>">
                               </div>
                               <div class="form-group">
                                   <label class="form-label">Dean Name</label>
                                   <input type="text" name="dean_name" class="form-input" placeholder="Full name, e.g. Akosua Boatemaa Frimpong"
                                     value="<?php
                                       $n = db()->fetch("SELECT col_value FROM site_settings WHERE `col_key` = 'dean_name'");
                                       $nv = $n ? $n['col_value'] : ($deanId ? trim(db()->fetch("SELECT CONCAT(first_name,' ',last_name) AS name FROM users WHERE id = ?", [$deanId])['name'] ?? '') : '');
                                       echo htmlspecialchars($nv);
                                     ?>">
                              <button type="submit" class="btn btn-primary"><i class="bi bi-upload"></i> Upload</button>
                          </form>
                      </div>
                    <div class="table-container">
                        <div style="padding:16px 24px;border-bottom:1px solid rgba(138,155,184,0.1);display:flex;justify-content:space-between;align-items:center;">
                            <h3 style="padding:0;margin:0;">Existing Images</h3>
                            <span class="badge" style="background:rgba(201,168,76,0.1);color:#C9A84C;border:1px solid rgba(201,168,76,0.25);"><?php echo count($images); ?> total</span>
                        </div>
                        <?php if (empty($images)): ?>
                            <div style="padding:40px 24px;text-align:center;color:#8A9BB8;">
                                <i class="bi bi-image" style="font-size:32px;opacity:0.4;"></i>
                                <p style="margin:8px 0 0;">No images uploaded yet</p>
                            </div>
                        <?php else: ?>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Type</th>
                                        <th>Alt Text</th>
                                        <th>Preview</th>
                                        <th>Order</th>
                                        <th>Status</th>
                                        <th style="text-align:right;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($images as $img): ?>
                                    <tr>
                                        <td><code style="font-size:12px;color:#8A9BB8;">#<?php echo (int)$img['id']; ?></code></td>
                                        <td><span class="badge badge-role"><?php echo htmlspecialchars($img['image_type']); ?></span></td>
                                        <td style="max-width:160px;"><?php echo htmlspecialchars($img['alt_text'] ?: '—'); ?></td>
                                        <td><img src="../<?php echo htmlspecialchars($img['file_path']); ?>" style="height:48px;width:72px;object-fit:cover;border-radius:4px;border:1px solid rgba(138,155,184,0.15);"></td>
                                        <td><?php echo (int)$img['display_order']; ?></td>
                                        <td>
                                            <a href="?action=toggle&id=<?php echo $img['id']; ?>" title="<?php echo $img['is_active'] ? 'Deactivate' : 'Activate'; ?>" style="text-decoration:none;">
                                                <?php echo $img['is_active'] ? '<span class="badge badge-active">Active</span>' : '<span class="badge">Inactive</span>'; ?>
                                            </a>
                                        </td>
                                        <td>
                                            <div style="display:flex;gap:6px;justify-content:flex-end;">
                                                <a href="?action=edit&id=<?php echo $img['id']; ?>" class="btn btn-sm btn-outline" title="Edit"><i class="bi bi-pencil"></i></a>
                                                <a href="?action=delete&id=<?php echo $img['id']; ?>" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Delete this image permanently?')"><i class="bi bi-trash"></i></a>
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
    <script src="../assets/js/sidebar.js"></script>
    <!-- Edit Image Modal -->
    <div id="editImageModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center;">
        <div style="background:#fff;border-radius:12px;max-width:560px;width:90%;max-height:90vh;overflow-y:auto;">
            <div style="padding:24px;border-bottom:1px solid rgba(138,155,184,0.1);display:flex;justify-content:space-between;align-items:center;">
                <h3 style="margin:0;"><i class="bi bi-pencil"></i> Edit Image</h3>
                <button onclick="closeEditModal()" style="background:none;border:none;font-size:24px;cursor:pointer;line-height:1;">&times;</button>
            </div>
            <form method="POST" enctype="multipart/form-data" style="padding:24px;">
                <input type="hidden" name="update_id" value="<?php echo $editImage ? (int)$editImage['id'] : ''; ?>">
                <div class="form-group">
                    <label class="form-label">Image Type</label>
                    <select name="image_type" class="form-input">
                        <option value="HERO" <?php echo ($editImage && $editImage['image_type'] === 'HERO') ? 'selected' : ''; ?>>Hero Image</option>
                        <option value="PROFILE" <?php echo ($editImage && $editImage['image_type'] === 'PROFILE') ? 'selected' : ''; ?>>Profile Image</option>
                        <option value="GALLERY" <?php echo ($editImage && $editImage['image_type'] === 'GALLERY') ? 'selected' : ''; ?>>Gallery Image</option>
                    </select>
                </div>
                <?php if ($editImage): ?>
                <div class="form-group">
                    <label class="form-label">Current Image</label>
                    <div style="display:flex;gap:12px;align-items:center;">
                        <img src="../<?php echo htmlspecialchars($editImage['file_path']); ?>" style="height:72px;width:110px;object-fit:cover;border-radius:6px;border:1px solid rgba(138,155,184,0.15);">
                        <div>
                            <div style="font-size:14px;"><strong>Type:</strong> <?php echo htmlspecialchars($editImage['image_type']); ?></div>
                            <div style="font-size:14px;"><strong>Size:</strong> <?php echo number_format($editImage['file_size'] / 1024, 1); ?> KB</div>
                            <div style="font-size:14px;"><strong>Status:</strong> <?php echo $editImage['is_active'] ? 'Active' : 'Inactive'; ?></div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                <div class="form-group">
                    <label class="form-label">Replace Image <span style="color:#8A9BB8;font-weight:400;">(optional)</span></label>
                    <input type="file" name="edit_image" class="form-input" accept="image/*">
                    <small class="form-text">Leave empty to keep the current image</small>
                </div>
                <div class="form-group">
                    <label class="form-label">Alt Text</label>
                    <input type="text" name="alt_text" class="form-input" value="<?php echo $editImage ? htmlspecialchars($editImage['alt_text']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Display Order</label>
                    <input type="number" name="display_order" class="form-input" value="<?php echo $editImage ? (int)$editImage['display_order'] : '0'; ?>" min="0">
                    <small class="form-text">Lower numbers appear first</small>
                </div>
                <div class="form-group form-check">
                    <input type="checkbox" name="is_active" class="form-check-input" value="1" <?php echo (!$editImage || $editImage['is_active']) ? 'checked' : ''; ?>>
                    <label class="form-check-label">Active</label>
                </div>
                <hr>
                <h4>Dean Section Text Settings</h4>
                <?php
                $editDeanTitle = db()->fetch("SELECT col_value FROM site_settings WHERE `col_key` = 'dean_title'");
                $editDeanSubtitle = db()->fetch("SELECT col_value FROM site_settings WHERE `col_key` = 'dean_subtitle'");
                $editDeanName = db()->fetch("SELECT col_value FROM site_settings WHERE `col_key` = 'dean_name'");
                ?>
                <div class="form-group">
                    <label class="form-label">Dean Title</label>
                     <input type="text" name="dean_title" class="form-input" value="<?php echo $editDeanTitle ? htmlspecialchars($editDeanTitle['col_value']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Dean Subtitle / Office</label>
                     <input type="text" name="dean_subtitle" class="form-input" value="<?php echo $editDeanSubtitle ? htmlspecialchars($editDeanSubtitle['col_value']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Dean Name</label>
                     <input type="text" name="dean_name" class="form-input" value="<?php echo $editDeanName ? htmlspecialchars($editDeanName['col_value']) : ''; ?>">
                </div>
                <div style="display:flex;gap:12px;justify-content:flex-end;margin-top:20px;">
                    <button type="button" onclick="closeEditModal()" class="btn btn-outline">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check2"></i> Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function closeEditModal() {
            document.getElementById('editImageModal').style.display = 'none';
            if (window.location.search.includes('action=edit')) {
                history.replaceState(null, '', window.location.pathname);
            }
        }

        <?php if ($editImage): ?>
        document.addEventListener('DOMContentLoaded', function () {
            document.getElementById('editImageModal').style.display = 'flex';
        });
        <?php endif; ?>

        document.addEventListener('click', function (e) {
            var overlay = document.getElementById('editImageModal');
            if (e.target === overlay) closeEditModal();
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeEditModal();
        });
    </script>
</body>
</html>