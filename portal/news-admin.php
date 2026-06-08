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

$pageTitle = "News Management";
$success = $_SESSION["success"] ?? null;
unset($_SESSION["success"], $_SESSION["errors"]);

$newsModel = new News(db());
$newsItems = $newsModel->getAll();

$action = $_GET["action"] ?? "list";
$id = $_GET["id"] ?? null;

if ($action === "delete" && $id) {
    // Get the news item to delete the image file
    $item = $newsModel->getById($id);
    if ($item && !empty($item["featured_image"]) && file_exists("../" . $item["featured_image"])) {
        unlink("../" . $item["featured_image"]);
    }
    $newsModel->delete($id);
    $_SESSION["success"] = "News article deleted successfully";
    header("Location: news-admin.php");
    exit;
}

if ($action === "toggle_featured" && $id) {
    $item = $newsModel->getById($id);
    if ($item) {
        $newStatus = $item["is_featured"] ? 0 : 1;
        $newsModel->update($id, ["is_featured" => $newStatus]);
    }
    header("Location: news-admin.php");
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
    <style>
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-published { background: rgba(34,197,94,0.15); color: #22c55e; }
        .status-draft { background: rgba(138,155,184,0.15); color: #8a9bb8; }
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
                <h1 class="header-title">News Management</h1>
                <div class="header-actions"><a href="../logout.php" class="header-btn"><i class="bi bi-box-arrow-right"></i></a></div>
            </header>
            <main class="content-body">
                <div class="dashboard-container">
                    <div class="dashboard-header-section">
                        <h2 class="dashboard-title">Content Management</h2>
                        <p class="dashboard-subtitle">Manage news articles, announcements, and press releases</p>
                    </div>

                    <?php if ($success): ?>
                        <div style="background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.2);border-radius:8px;padding:16px;margin-bottom:20px;color:#22c55e;"><?php echo $success; ?></div>
                    <?php endif; ?>

                    <div class="table-container">
                        <div style="padding:16px 24px;border-bottom:1px solid rgba(138,155,184,0.1);display:flex;justify-content:space-between;align-items:center;">
                            <h3 style="margin:0;">All Articles</h3>
                            <a href="news-update.php" class="btn btn-primary"><i class="bi bi-plus"></i> New Article</a>
                        </div>

                        <?php if (empty($newsItems)): ?>
                            <div class="empty-state">
                                <div class="empty-icon"><i class="bi bi-newspaper"></i></div>
                                <h3 class="empty-title">No articles found</h3>
                                <p class="empty-text">Click "New Article" to create your first news article</p>
                            </div>
                        <?php else: ?>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Image</th>
                                        <th>Title</th>
                                        <th>Category</th>
                                        <th>Status</th>
                                        <th>Featured</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($newsItems as $index => $item): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td>
                                            <?php if (!empty($item["featured_image"])): ?>
                                                <img src="../<?= htmlspecialchars($item["featured_image"]) ?>" alt="Preview" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                                            <?php else: ?>
                                                <span style="color: #94a3b8; font-size: 12px;">No image</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><strong><?php echo htmlspecialchars($item["title"]); ?></strong></td>
                                        <td><span class="badge badge-role"><?php echo htmlspecialchars($item["category"]); ?></span></td>
                                        <td><span class="status-badge status-<?php echo strtolower($item["status"]); ?>"><?php echo $item["status"]; ?></span></td>
                                        <td><span class="featured-badge" style="display:inline-block;padding:4px 10px;border-radius:4px;font-size:11px;font-weight:600;text-transform:uppercase;<?= !empty($item['is_featured']) ? 'background:rgba(201,168,76,0.2);color:#c9a84c;' : 'background:rgba(138,155,184,0.15);color:#8a9bb8;' ?>"><?= !empty($item['is_featured']) ? 'Yes' : 'No' ?></span></td>
                                        <td><a href="?action=toggle_featured&id=<?php echo $item["id"]; ?>" class="btn btn-sm btn-outline" style="padding:4px 8px;text-decoration:none;" onclick="return confirm('Toggle featured status?')" title="Toggle Featured"><i class="bi bi-star<?= !empty($item['is_featured']) ? '-fill' : '' ?>"></i></a></td>
                                        <td><?php echo date("d M Y", strtotime($item["published_at"])); ?></td>
                                        <td>
                                            <a href="news-update.php?id=<?php echo $item["id"]; ?>" class="btn btn-sm btn-outline" style="padding:4px 8px;"><i class="bi bi-pencil"></i></a>
                                            <a href="?action=delete&id=<?php echo $item["id"]; ?>" class="btn btn-sm btn-danger" style="padding:4px 8px;" onclick="return confirm('Delete this article?')"><i class="bi bi-trash"></i></a>
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
</body>
</html>