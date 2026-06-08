<?php 
 
session_start();
require_once "../config/database.php";
require_once "../config/functions.php";
require_once "../models/News.php";

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

$pageTitle = "News Editor";
$success = $_SESSION["success"] ?? null;
$errors = $_SESSION["errors"] ?? [];
unset($_SESSION["success"], $_SESSION["errors"]);

$newsModel = new News(db());

$action = $_GET["action"] ?? "create";
$id = $_GET["id"] ?? null;

$editItem = null;
if ($id) {
    $editItem = $newsModel->getById($id);
    if (!$editItem) {
        $_SESSION["errors"] = ["Article not found"];
        header("Location: news-admin.php");
        exit;
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    require_once "../config/validations.php";
    $status = $_POST["status"] ?? "DRAFT";
    $featuredImage = $editItem["featured_image"] ?? null;
    
    // Ensure uploads directory exists
    $uploadDir = "../uploads/news/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Handle image removal
    if (isset($_POST["remove_image"]) && $_POST["remove_image"]) {
        if ($featuredImage && file_exists("../" . $featuredImage)) {
            unlink("../" . $featuredImage);
        }
        $featuredImage = null;
    }
    
    // Handle image upload
    if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['featured_image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            $filename = 'news_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($_FILES['featured_image']['tmp_name'], $uploadDir . $filename)) {
                // Delete old image if exists
                if ($featuredImage && file_exists("../" . $featuredImage)) {
                    unlink("../" . $featuredImage);
                }
                $featuredImage = "uploads/news/" . $filename;
            }
        }
    }
    
    $data = [
        "title" => trim($_POST["title"] ?? ""),
        "excerpt" => trim($_POST["excerpt"] ?? ""),
        "content" => $_POST["content"] ?? "",
        "category" => $_POST["category"] ?? "NEWS",
        "tags" => ($tagsInput = trim($_POST["tags"] ?? "")) ? json_encode(array_map("trim", explode(",", $tagsInput))) : null,
        "status" => $status,
        "is_featured" => isset($_POST["is_featured"]) ? 1 : 0,
        "featured_image" => $featuredImage,
        "author_id" => $currentUser["id"] ?? null,
        "published_at" => $_POST["published_at"] ?: date("Y-m-d H:i:s")
    ];

    if (empty($data["title"])) {
        $errors[] = "Title is required";
    }
    if (empty($data["content"])) {
        $errors[] = "Content is required";
    }

    // Validate event scheduling conflicts for EVENT category
    if ($data["category"] === "EVENT" && $data["status"] === "PUBLISHED") {
        $excerpt = $data["excerpt"] ?? "";
        $location = "";
        $startTime = "";

        // Extract location after " · " separator (format: "time · location")
        if (strpos($excerpt, " · ") !== false) {
            $parts = explode(" · ", $excerpt);
            if (count($parts) >= 2) {
                $locPart = $parts[1];
                $location = trim(str_replace("📍", "", $locPart));
                $location = preg_replace("/\s*[\n\r].*$/u", "", $location);
            }
        }
        // Match time in format "6:13 AM"
        if (preg_match("/(\d{1,2}:\d{2}\s*(?:[AP]M))/i", $excerpt, $timeMatch)) {
            $startTime = trim($timeMatch[1]);
        }

        // Extract date from published_at
        $eventDate = date("Y-m-d", strtotime($data["published_at"]));

        $conflictErrors = validateEventScheduling([
            "event_location" => $location,
            "event_date" => $eventDate,
            "event_start_time" => $startTime,
            "exclude_event_id" => $id ? (int)$id : null
        ]);

        $errors = array_merge($errors, $conflictErrors);
    }

    if (empty($errors)) {
        if ($id) {
            $newsModel->update($id, $data);
            $_SESSION["success"] = "Article updated successfully";
            header("Location: news-admin.php");
            exit;
        } else {
            $newsModel->create($data);
            $_SESSION["success"] = "Article created successfully";
            header("Location: news-update.php?action=create");
            exit;
        }
    }
}

$availableCategories = ["NEWS" => "Latest News", "ANNOUNCEMENT" => "Announcements", "PRESS_RELEASE" => "Press Releases", "ACADEMIC" => "Academic", "CAMPUS_LIFE" => "Campus Life", "SPORTS" => "Sports", "WELFARE" => "Welfare", "GOVERNANCE" => "Governance", "EVENT" => "Events"];
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
    <style>
        :root {
            --editor-bg: #ffffff;
            --editor-border: #e2e8f0;
            --editor-toolbar-bg: #f8fafc;
            --editor-accent: #0a1628;
            --gold: #c9a84c;
        }
        .editor-container { max-width: 900px; margin: 0 auto; }
        .editor-card {
            background: var(--editor-bg);
            border: 1px solid var(--editor-border);
            border-radius: 12px;
            overflow: hidden;
        }
        .editor-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--editor-border);
            background: var(--editor-toolbar-bg);
        }
        .editor-title { margin: 0; font-size: 18px; font-weight: 600; color: var(--editor-accent); }
        .editor-body { padding: 24px; }
        .content-editor {
            width: 100%;
            min-height: 400px;
            padding: 16px;
            border: 1px solid var(--editor-border);
            border-radius: 8px;
            background: #fff;
            color: #333;
            font-family: 'Outfit', sans-serif;
            font-size: 14px;
            line-height: 1.6;
            outline: none;
        }
        .content-editor:focus { border-color: var(--gold); }
        .content-editor:empty:before {
            content: attr(placeholder);
            color: #999;
            pointer-events: none;
        }
        .editor-toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 16px;
            padding: 12px;
            background: var(--editor-toolbar-bg);
            border: 1px solid var(--editor-border);
            border-radius: 8px;
        }
        .toolbar-group { display: flex; gap: 4px; padding-right: 8px; border-right: 1px solid var(--editor-border); }
        .toolbar-group:last-child { border: none; }
        .toolbar-btn {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fff;
            border: 1px solid var(--editor-border);
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.2s;
        }
        .toolbar-btn:hover { background: var(--gold); color: #0a1628; border-color: var(--gold); }
        .toolbar-btn.active { background: var(--editor-accent); color: #fff; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        @media (max-width: 768px) { .form-grid { grid-template-columns: 1fr; } }
        .preview-box {
            margin-top: 16px;
            padding: 16px;
            background: #f8fafc;
            border: 1px solid var(--editor-border);
            border-radius: 8px;
            min-height: 100px;
        }
        .preview-title { font-size: 12px; color: var(--gold); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.1em; }
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
                <h1 class="header-title">News Editor</h1>
                <div class="header-actions">
                    <a href="news-admin.php" class="header-btn"><i class="bi bi-arrow-left"></i> Back</a>
                    <a href="../logout.php" class="header-btn"><i class="bi bi-box-arrow-right"></i></a>
                </div>
            </header>
            <main class="content-body">
                <div class="editor-container">
                    <div class="dashboard-header-section">
                        <h2 class="dashboard-title">Content Editor</h2>
                        <p class="dashboard-subtitle">Create and edit news articles with rich text formatting</p>
                    </div>

                        <div style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.2);border-radius:8px;padding:16px;margin-bottom:20px;color:#ef4444;">
                            <?php if (!empty($errors)): ?>
                                <?php foreach ($errors as $error): ?>
                                    <div><?= htmlspecialchars($error) ?></div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <?php if ($success): ?>
                            <div style="background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.2);border-radius:8px;padding:16px;margin-bottom:20px;color:#22c55e;">
                                <?= htmlspecialchars($success) ?>
                            </div>
                        <?php endif; ?>

                    <form method="POST" class="editor-card" enctype="multipart/form-data" onsubmit="syncContent()">
                        <div class="editor-header">
                            <h3 class="editor-title"><?= $id ? "Edit Article" : "Create New Article" ?></h3>
                        </div>

                        <div class="editor-body">
                            <div class="form-group" style="margin-bottom: 20px;">
                                <label class="form-label">Title</label>
                                <input type="text" name="title" class="form-input" value="<?= htmlspecialchars($editItem["title"] ?? "") ?>" placeholder="Enter article title" required>
                            </div>

                            <div class="form-grid" style="margin-bottom: 20px;">
                                <div class="form-group">
                                    <label class="form-label">Category</label>
                                    <select name="category" class="form-input">
                                        <?php foreach ($availableCategories as $value => $label): ?>
                                            <option value="<?= $value ?>" <?= (isset($editItem["category"]) && $editItem["category"] === $value) ? "selected" : "" ?>><?= $label ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-input">
                                        <option value="DRAFT" <?= (isset($editItem["status"]) && $editItem["status"] === "DRAFT") ? "selected" : "" ?>>Draft</option>
                                        <option value="PUBLISHED" <?= (isset($editItem["status"]) && $editItem["status"] === "PUBLISHED") ? "selected" : "" ?>>Published</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group" style="margin-bottom: 12px;">
                                <label class="form-label">Excerpt</label>
                                <textarea name="excerpt" class="form-input" rows="3" placeholder="Brief summary of the article"><?= htmlspecialchars($editItem["excerpt"] ?? "") ?></textarea>
                            </div>

                            <div class="form-group" style="margin-bottom: 12px;">
                                <label class="form-label">Tags</label>
                                <input type="text" name="tags" class="form-input" value="<?= isset($editItem["tags"]) ? htmlspecialchars(implode(", ", json_decode($editItem["tags"], true) ?? [])) : "" ?>" placeholder="Comma separated tags">
                            </div>

                            <div class="form-group" style="margin-bottom: 12px;">
                                <label class="form-label">Featured Image</label>
                                <?php if (isset($editItem["featured_image"]) && $editItem["featured_image"]): ?>
                                    <div style="margin-bottom: 8px;">
                                        <img src="../<?= htmlspecialchars($editItem["featured_image"]) ?>" alt="Current image" style="max-width: 200px; border-radius: 8px; border: 1px solid var(--editor-border);">
                                    </div>
                                    <label style="display: block; margin-bottom: 4px;">
                                        <input type="checkbox" name="remove_image" value="1"> Remove current image
                                    </label>
                                <?php endif; ?>
                                <input type="file" name="featured_image" class="form-input" accept="image/*">
                                <small style="color: #64748b;">Supported formats: JPG, PNG, GIF, WEBP (max 5MB)</small>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Content</label>
                                <div class="editor-toolbar">
                                    <div class="toolbar-group">
                                        <button type="button" class="toolbar-btn" onclick="execCmd('bold')" title="Bold"><i class="bi bi-type-bold"></i></button>
                                        <button type="button" class="toolbar-btn" onclick="execCmd('italic')" title="Italic"><i class="bi bi-type-italic"></i></button>
                                        <button type="button" class="toolbar-btn" onclick="execCmd('underline')" title="Underline"><i class="bi bi-type-underline"></i></button>
                                        <button type="button" class="toolbar-btn" onclick="execCmd('strikeThrough')" title="Strikethrough"><i class="bi bi-type-strikethrough"></i></button>
                                    </div>
                                    <div class="toolbar-group">
                                        <select onchange="execCmd('fontSize', this.value)" class="toolbar-btn" title="Font Size" style="width:60px;padding:4px;">
                                            <option value="">Size</option>
                                            <option value="1">Small</option>
                                            <option value="2">Normal</option>
                                            <option value="3">Medium</option>
                                            <option value="4">Large</option>
                                            <option value="5">XL</option>
                                            <option value="6">XXL</option>
                                            <option value="7">Huge</option>
                                        </select>
                                        <select onchange="execCmd('fontName', this.value)" class="toolbar-btn" title="Font Family" style="width:100px;padding:4px;">
                                            <option value="">Font</option>
                                            <option value="Georgia, serif">Georgia</option>
                                            <option value="Times New Roman, serif">Times</option>
                                            <option value="Arial, sans-serif">Arial</option>
                                            <option value="Courier New, monospace">Courier</option>
                                            <option value="'Cormorant Garamond', serif">Cormorant</option>
                                            <option value="'Outfit', sans-serif">Outfit</option>
                                        </select>
                                    </div>
                                    <div class="toolbar-group">
                                        <button type="button" class="toolbar-btn" onclick="execCmd('justifyLeft')" title="Align Left"><i class="bi bi-text-left"></i></button>
                                        <button type="button" class="toolbar-btn" onclick="execCmd('justifyCenter')" title="Align Center"><i class="bi bi-text-center"></i></button>
                                        <button type="button" class="toolbar-btn" onclick="execCmd('justifyRight')" title="Align Right"><i class="bi bi-text-right"></i></button>
                                    </div>
                                    <div class="toolbar-group">
                                        <button type="button" class="toolbar-btn" onclick="execCmd('formatBlock', 'h2')" title="Heading">H2</button>
                                        <button type="button" class="toolbar-btn" onclick="execCmd('formatBlock', 'h3')" title="Subheading">H3</button>
                                        <button type="button" class="toolbar-btn" onclick="execCmd('formatBlock', 'p')" title="Paragraph">P</button>
                                    </div>
                                    <div class="toolbar-group">
                                        <button type="button" class="toolbar-btn" onclick="execCmd('insertUnorderedList')" title="Bullet List"><i class="bi bi-list-ul"></i></button>
                                        <button type="button" class="toolbar-btn" onclick="execCmd('insertOrderedList')" title="Numbered List"><i class="bi bi-list-ol"></i></button>
                                    </div>
                                    <div class="toolbar-group">
                                        <button type="button" class="toolbar-btn" onclick="execCmd('foreColor', '#c9a84c')" title="Gold Color"><span style="color:#c9a84c;">A</span></button>
                                        <button type="button" class="toolbar-btn" onclick="execCmd('foreColor', '#333')" title="Dark Color"><span style="color:#333;">A</span></button>
                                    </div>
                                    <div class="toolbar-group">
                                        <button type="button" class="toolbar-btn" onclick="insertLink()" title="Insert Link"><i class="bi bi-link"></i></button>
                                    </div>
                                </div>
                                <div id="contentEditor" class="content-editor" contenteditable="true" placeholder="Write your article content here..."><?= $editItem["content"] ?? "" ?></div>
                                <textarea name="content" id="contentInput" style="display:none;"><?= htmlspecialchars($editItem["content"] ?? "") ?></textarea>
                            </div>

                            <div class="form-group" style="margin-bottom: 12px;">
                                <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                                    <input type="checkbox" name="is_featured" value="1" <?= isset($editItem["is_featured"]) && $editItem["is_featured"] ? "checked" : "" ?> style="width:18px;height:18px;accent-color:var(--gold);">
                                    <span style="font-weight:500;">Feature this article</span>
                                </label>
                                <small style="color: #64748b;">Featured articles appear prominently at the top of the homepage and the Latest News page.</small>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Publish Date & Time</label>
                                <input type="datetime-local" name="published_at" class="form-input" value="<?= isset($editItem["published_at"]) ? date("Y-m-d\TH:i", strtotime($editItem["published_at"])) : date("Y-m-d\TH:i") ?>">
                            </div>

                            <div style="display:flex;gap:12px;justify-content:flex-end;margin-top:24px;">
                                <a href="news-admin.php" class="btn btn-outline">Cancel</a>
                                <button type="submit" class="btn btn-primary"><?= $id ? "Update Article" : "Publish Article" ?></button>
                            </div>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>

    <script>
        function execCmd(cmd, value = null) {
            document.execCommand(cmd, false, value);
            syncContent();
        }
        function insertLink() {
            const url = prompt('Enter URL:');
            if (!url) return;
            document.execCommand('createLink', false, url);
            syncContent();
        }
        function syncContent() {
            const editor = document.getElementById('contentEditor');
            const hiddenInput = document.getElementById('contentInput');
            if (editor && hiddenInput) {
                hiddenInput.value = editor.innerHTML;
            }
        }
        const contentEditor = document.getElementById('contentEditor');
        if (contentEditor) {
            contentEditor.addEventListener('input', syncContent);
        }
    </script>
     <script src="../assets/js/sidebar.js"></script>
</body>
</html>
