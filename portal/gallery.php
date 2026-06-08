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

$pageTitle = 'Media Gallery';
$success = $_SESSION['success'] ?? null;
$errors = $_SESSION['errors'] ?? [];
unset($_SESSION['success'], $_SESSION['errors']);

// Get all media sections
$sections = db()->fetchAll("SELECT id, title, description, section_type, is_active, display_order FROM media_sections ORDER BY display_order ASC");

// Get media items for each section
$mediaItems = [];
foreach ($sections as $section) {
    $items = db()->fetchAll("
        SELECT mi.id, mi.caption, mi.is_active, mi.display_order, m.file_path, m.file_type, m.alt_text, m.file_size
        FROM media_items mi
        JOIN media m ON mi.media_id = m.id
        WHERE mi.section_id = ?
        ORDER BY mi.display_order ASC
    ", [$section['id']]);
    $mediaItems[$section['id']] = $items;
}

// Get existing media for dropdowns
$existingMedia = db()->fetchAll("SELECT id, file_path, file_type, alt_text FROM media ORDER BY created_at DESC");

// Handle section creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_section'])) {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $sectionType = $_POST['section_type'] ?? 'GALLERY';
    
    if ($title) {
        db()->insert('media_sections', [
            'title' => $title,
            'description' => $description,
            'section_type' => $sectionType,
            'is_active' => 1,
            'display_order' => 0
        ]);
        logActivity('create_media_section', $_SESSION['user_id'], ['title' => $title]);
        $_SESSION['success'] = 'Section created successfully';
        header('Location: gallery.php');
        exit;
    }
}

// Handle media upload and assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['media_file'])) {
    $sectionId = (int)($_POST['section_id'] ?? 0);
    $caption = trim($_POST['caption'] ?? '');
    
    if ($sectionId && $_FILES['media_file']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/media/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        
        $fileExt = pathinfo($_FILES['media_file']['name'], PATHINFO_EXTENSION);
        $fileType = mime_content_type($_FILES['media_file']['tmp_name']);
        $isImage = strpos($fileType, 'image/') === 0;
        $dbFileType = $isImage ? 'IMAGE' : 'VIDEO';
        
        $fileName = 'media_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $fileExt;
        $filePath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['media_file']['tmp_name'], $filePath)) {
            $mediaId = db()->insert('media', [
                'file_name' => $_FILES['media_file']['name'],
                'file_path' => 'uploads/media/' . $fileName,
                'file_type' => $dbFileType,
                'mime_type' => $fileType,
                'file_size' => $_FILES['media_file']['size'],
                'alt_text' => $caption,
                'uploaded_by' => $_SESSION['user_id']
            ]);
            
            db()->insert('media_items', [
                'section_id' => $sectionId,
                'media_id' => $mediaId,
                'caption' => $caption,
                'is_active' => 1,
                'display_order' => 0
            ]);
            
            logActivity('upload_media_item', $_SESSION['user_id'], ['section_id' => $sectionId]);
            $_SESSION['success'] = 'Media uploaded successfully';
            header('Location: gallery.php');
            exit;
        } else {
            $_SESSION['errors'] = ['Failed to upload media'];
        }
    }
}

// Handle edit media item submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_item'])) {
    $itemId = (int)($_POST['item_id'] ?? 0);
    $caption = trim($_POST['caption'] ?? '');
    
    if ($itemId) {
        // Get the current media item to get the media_id
        $currentItem = db()->fetch("SELECT mi.media_id FROM media_items mi WHERE mi.id = ?", [$itemId]);
        if ($currentItem) {
            // Update the media item caption
            db()->update('media_items', ['caption' => $caption], ['id' => $itemId]);
            
            // Also update the alt_text in the media table if needed
            db()->update('media', ['alt_text' => $caption], ['id' => $currentItem['media_id']]);
            
            logActivity('edit_media_item', $_SESSION['user_id'], ['id' => $itemId]);
            $_SESSION['success'] = 'Media item updated successfully';
            unset($_SESSION['edit_item']);
            header('Location: gallery.php');
            exit;
        }
    }
}

// Handle delete media item
if (isset($_GET['delete_item'])) {
    $itemId = (int)$_GET['delete_item'];
    db()->delete('media_items', ['id' => $itemId]);
    logActivity('delete_media_item', $_SESSION['user_id'], ['id' => $itemId]);
    $_SESSION['success'] = 'Media item deleted';
    header('Location: gallery.php');
    exit;
}

// Handle edit media item
if (isset($_GET['edit_item'])) {
    $itemId = (int)$_GET['edit_item'];
    // Get the media item details
    $item = db()->fetch("SELECT mi.*, m.file_path, m.file_type FROM media_items mi JOIN media m ON mi.media_id = m.id WHERE mi.id = ?", [$itemId]);
    if ($item) {
        // Store item details in session for the edit form
        $_SESSION['edit_item'] = $item;
        header('Location: gallery.php');
        exit;
    }
}

// Handle toggle section status
if (isset($_GET['toggle_section'])) {
    $sectionId = (int)$_GET['toggle_section'];
    db()->execute("UPDATE media_sections SET is_active = 1 - is_active WHERE id = ?", [$sectionId]);
    $_SESSION['success'] = 'Section status updated';
    header('Location: gallery.php');
    exit;
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
                <h1 class="header-title">Media Gallery</h1>
                <div class="header-actions"><a href="../logout.php" class="header-btn"><i class="bi bi-box-arrow-right"></i></a></div>
            </header>
            <main class="content-body">
                <div class="dashboard-container">
                    <div class="dashboard-header-section">
                        <h2 class="dashboard-title">Manage Media Gallery</h2>
                        <p class="dashboard-subtitle">Create sections and upload images/videos for the public gallery</p>
                    </div>

<?php if ($success): ?>
                         <div style="background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.2);border-radius:8px;padding:16px;margin-bottom:20px;color:#22c55e;"><?php echo $success; ?></div>
                     <?php endif; ?>
                     <?php if ($errors): ?>
                         <div style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.2);border-radius:8px;padding:16px;margin-bottom:20px;color:#ef4444;">
                             <?php foreach ($errors as $error): ?><?php echo $error; ?><br><?php endforeach; ?>
                         </div>
                     <?php endif; ?>

                     <!-- Edit Media Item Form -->
                     <?php if (isset($_SESSION['edit_item'])): ?>
                     <div class="table-container" style="max-width:600px;margin-bottom:24px;">
                         <h3 style="padding:16px 24px 0;margin:0 0 16px;font-size:16px;">Edit Media Item</h3>
                         <form method="POST" style="padding:0 24px 24px;">
                             <input type="hidden" name="edit_item" value="1">
                             <input type="hidden" name="item_id" value="<?php echo $_SESSION['edit_item']['id']; ?>">
                             <div class="form-group">
                                 <label class="form-label">Caption</label>
                                 <input type="text" name="caption" class="form-input" value="<?php echo htmlspecialchars($_SESSION['edit_item']['caption']); ?>" required>
                             </div>
                             <button type="submit" class="btn btn-primary"><i class="bi bi-check"></i> Save Changes</button>
                             <a href="gallery.php" class="btn btn-secondary" style="margin-left:10px;">Cancel</a>
                         </form>
                     </div>
                     <?php endif; ?>

                     <!-- Create Section Form -->
                    <div class="table-container" style="max-width:600px;margin-bottom:24px;">
                        <h3 style="padding:16px 24px 0;margin:0 0 16px;font-size:16px;">Create New Section</h3>
                        <form method="POST" style="padding:0 24px 24px;">
                            <input type="hidden" name="create_section" value="1">
                            <div class="form-group">
                                <label class="form-label">Section Title</label>
                                <input type="text" name="title" class="form-input" placeholder="e.g., Campus Events, Sports, Ceremonies" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-input" rows="2"></textarea>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Section Type</label>
                                <select name="section_type" class="form-input">
                                    <option value="GALLERY">Gallery (Images)</option>
                                    <option value="VIDEOS">Videos</option>
                                    <option value="NEWS">News</option>
                                    <option value="EVENTS">Events</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary"><i class="bi bi-plus"></i> Create Section</button>
                        </form>
                    </div>

                    <!-- Upload Media Form -->
                    <div class="table-container" style="max-width:600px;margin-bottom:24px;">
                        <h3 style="padding:16px 24px 0;margin:0 0 16px;font-size:16px;">Upload Media</h3>
                        <form method="POST" enctype="multipart/form-data" style="padding:0 24px 24px;">
                            <div class="form-group">
                                <label class="form-label">Target Section</label>
                                <select name="section_id" class="form-input" required>
                                    <option value="">Select a section...</option>
                                    <?php foreach ($sections as $section): ?>
                                        <option value="<?php echo $section['id']; ?>"><?php echo htmlspecialchars($section['title']); ?> (<?php echo $section['section_type']; ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Media File (Image/Video)</label>
                                <input type="file" name="media_file" class="form-input" accept="image/*,video/*" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Caption</label>
                                <input type="text" name="caption" class="form-input" placeholder="Optional caption">
                            </div>
                            <button type="submit" class="btn btn-primary"><i class="bi bi-upload"></i> Upload Media</button>
                        </form>
                    </div>

                    <!-- Sections and Media Items -->
                    <div class="table-container">
                        <h3 style="padding:16px 24px 0;margin:0;">Media Sections & Items</h3>
                        <?php if (empty($sections)): ?>
                            <div style="padding:24px;text-align:center;color:#8A9BB8;">No sections created yet</div>
                        <?php else: ?>
                            <?php foreach ($sections as $section): ?>
                                <div style="border-bottom:1px solid rgba(255,255,255,0.05);padding:16px 24px;">
                                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
                                        <div>
                                            <strong><?php echo htmlspecialchars($section['title']); ?></strong>
                                            <span style="font-size:11px;color:#8A9BB8;margin-left:8px;"><?php echo $section['section_type']; ?></span>
                                            <?php if (!$section['is_active']): ?>
                                                <span style="font-size:11px;color:#ef4444;margin-left:8px;">(Inactive)</span>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                             <a href="?toggle_section=<?php echo $section['id']; ?>" class="header-btn" style="padding:4px 12px;font-size:12px;color:black;">
                                                <?php echo $section['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                            </a>
                                        </div>
                                    </div>
                                    <?php if (!empty($mediaItems[$section['id']])): ?>
                                        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:12px;">
                                            <?php foreach ($mediaItems[$section['id']] as $item): ?>
                                                <div style="position:relative;border:1px solid rgba(255,255,255,0.05);border-radius:8px;overflow:hidden;">
                                                     <?php if ($item['file_type'] === 'IMAGE'): ?>
                                                         <img src="../<?php echo htmlspecialchars($item['file_path']); ?>" style="width:100%;height:80px;object-fit:cover;">
                                                     <?php else: ?>
                                                         <video style="width:100%;height:80px;object-fit:cover;" controls>
                                                             <source src="../<?php echo htmlspecialchars($item['file_path']); ?>" type="video/mp4">
                                                         </video>
                                                         <div style="position:absolute;top:4px;right:4px;background:rgba(0,0,0,0.7);border-radius:4px;padding:2px 6px;font-size:10px;color:#fff;"><i class="bi bi-play-fill"></i></div>
                                                     <?php endif; ?>
                                                     <div style="padding:6px 8px;font-size:11px;">
                                                         <?php echo htmlspecialchars($item['caption'] ?: $item['alt_text']); ?>
                                                         <?php if (!$item['is_active']): ?>
                                                             <span style="color:#ef4444;">(inactive)</span>
                                                         <?php endif; ?>
                                                     </div>
                                                     <a href="?edit_item=<?php echo $item['id']; ?>" onclick="return confirm('Edit this media item?')" style="position:absolute;top:4px;left:24px;background:rgba(59,130,246,0.9);color:#fff;border-radius:4px;padding:2px 6px;font-size:10px;text-decoration:none;">✏️</a>
                                                     <a href="?delete_item=<?php echo $item['id']; ?>" onclick="return confirm('Delete this media item?')" style="position:absolute;top:4px;left:4px;background:rgba(239,68,68,0.9);color:#fff;border-radius:4px;padding:2px 6px;font-size:10px;text-decoration:none;">×</a>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <div style="font-size:12px;color:#8A9BB8;padding:8px 0;">No media items in this section</div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>
<script src="../assets/js/sidebar.js"></script>
    <script src="../assets/js/loader-service.js"></script>
</body>
</html>