<?php
session_start();
require_once "../config/database.php";
require_once "../config/functions.php";
require_once "../models/Clubs.php";

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

$pageTitle = "Club Presidents";
$success = $_SESSION["success"] ?? null;
$errors = $_SESSION["errors"] ?? [];
unset($_SESSION["success"], $_SESSION["errors"]);

$clubModel = new Clubs(db());
$clubs = $clubModel->getAll();
$allUsers = $clubModel->getAllUsers();

// Handle assign president
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "assign_president") {
    $clubId = (int)($_POST["club_id"] ?? 0);
    $userId = (int)($_POST["user_id"] ?? 0);
    if ($clubId > 0 && $userId > 0) {
        $clubModel->assignPresident($clubId, $userId);
        $_SESSION["success"] = "President assigned successfully";
    }
    header("Location: club-presidents.php");
    exit;
}

// Handle remove president
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "remove_president") {
    $clubId = (int)($_POST["club_id"] ?? 0);
    $userId = (int)($_POST["user_id"] ?? 0);
    if ($clubId > 0 && $userId > 0) {
        $clubModel->removePresident($clubId, $userId);
        $_SESSION["success"] = "President assignment removed";
    }
    header("Location: club-presidents.php");
    exit;
}

// Build lookup of existing presidents
$presidentMap = [];
foreach ($clubs as $club) {
    $president = $clubModel->getActivePresident($club["id"]);
    $presidentMap[$club["id"]] = $president;
}

// Enrich clubs data
foreach ($clubs as &$club) {
    $club["active_president"] = $presidentMap[$club["id"]] ?? null;
}
unset($club);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> | DHLTU SRC</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;0,700;1,300;1,400&family=Outfit:wght@200;300;400;500;600;700&display=swap" rel="stylesheet">
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
                <h1 class="header-title">Club Presidents</h1>
                <div class="header-actions">
                    <a href="../logout.php" class="header-btn"><i class="bi bi-box-arrow-right"></i></a>
                </div>
            </header>
            <main class="content-body">
                <div class="dashboard-container">
                    <div class="dashboard-header-section">
                        <h2 class="dashboard-title">Club Presidents Management</h2>
                        <p class="dashboard-subtitle">Assign and manage presidents for each registered club</p>
                    </div>

                    <?php if ($success): ?>
                        <div style="background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.2);border-radius:8px;padding:16px;margin-bottom:20px;color:#22c55e;"><?php echo htmlspecialchars($success); ?></div>
                    <?php endif; ?>

                    <div class="table-container">
                        <?php if (empty($clubs)): ?>
                            <div class="empty-state">
                                <div class="empty-icon"><i class="bi bi-person-badge"></i></div>
                                <h3 class="empty-title">No clubs found</h3>
                                <p class="empty-text">Create clubs first before assigning presidents</p>
                            </div>
                        <?php else: ?>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Club</th>
                                        <th>Category</th>
                                        <th>Current President</th>
                                        <th>Email</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($clubs as $index => $club): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td>
                                            <div class="user-name"><?php echo htmlspecialchars($club["name"]); ?></div>
                                            <div class="user-email"><?php echo htmlspecialchars($club["description"] ? truncate($club["description"], 50) : ""); ?></div>
                                        </td>
                                        <td><span class="badge badge-role"><?php echo htmlspecialchars($club["category"] ?? "—"); ?></span></td>
                                        <td>
                                            <?php if ($club["active_president"]): ?>
                                                <div class="user-info-cell">
                                                    <div class="user-avatar-table" style="background:linear-gradient(135deg, #C9A84C, #E8C97A);margin-right:10px;">
                                                        <?php echo strtoupper(substr($club["active_president"]["first_name"], 0, 1) . substr($club["active_president"]["last_name"], 0, 1)); ?>
                                                    </div>
                                                    <div>
                                                        <div class="user-name"><?php echo htmlspecialchars($club["active_president"]["first_name"] . " " . $club["active_president"]["last_name"]); ?></div>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <span style="color:#9ca3af;font-style:italic;">Not assigned</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $club["active_president"] ? htmlspecialchars($club["active_president"]["email"]) : "—"; ?></td>
                                        <td>
                                            <?php if ($club["active_president"]): ?>
                                                <form method="POST" style="display:inline;" onsubmit="return confirm('Remove current president assignment?');">
                                                    <input type="hidden" name="action" value="remove_president">
                                                    <input type="hidden" name="club_id" value="<?php echo $club["id"]; ?>">
                                                    <input type="hidden" name="user_id" value="<?php echo $club["active_president"]["user_id"]; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger" style="padding:4px 8px;" title="Unassign"><i class="bi bi-person-x"></i></button>
                                                </form>
                                                &nbsp;
                                            <?php endif; ?>
                                            <button type="button" class="btn btn-sm btn-primary" style="padding:4px 8px;" onclick="openAssignModal(<?php echo $club["id"]; ?>, '<?php echo htmlspecialchars($club["name"], ENT_QUOTES); ?>')" title="Assign President"><i class="bi bi-person-plus"></i></button>
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

    <!-- Assign President Modal -->
    <div id="assignModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center;">
        <div style="background:#fff;border-radius:12px;max-width:480px;width:90%;">
            <div style="padding:24px;border-bottom:1px solid #eee;display:flex;justify-content:space-between;align-items:center;">
                <h3 style="margin:0;">Assign President</h3>
                <button onclick="closeAssignModal()" style="background:none;border:none;font-size:24px;cursor:pointer;">&times;</button>
            </div>
            <form method="POST" style="padding:24px;">
                <input type="hidden" name="action" value="assign_president">
                <input type="hidden" name="club_id" id="modal_club_id" value="">
                <div style="margin-bottom:16px;">
                    <strong id="modal_club_name" style="font-size:1.1rem;"></strong>
                </div>
                <div class="form-group">
                    <label class="form-label">Select User *</label>
                    <select name="user_id" class="form-select" required>
                        <option value="">— Select User —</option>
                        <?php foreach ($allUsers as $u): ?>
                            <option value="<?php echo $u["id"]; ?>">
                                <?php echo htmlspecialchars($u["first_name"] . " " . $u["last_name"] . " (" . $u["email"] . ")"); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="display:flex;gap:12px;justify-content:flex-end;margin-top:20px;">
                    <button type="button" onclick="closeAssignModal()" class="btn btn-outline">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-person-check"></i> Assign</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../assets/js/sidebar.js"></script>
    <script>
        function openAssignModal(clubId, clubName) {
            document.getElementById("modal_club_id").value = clubId;
            document.getElementById("modal_club_name").textContent = clubName;
            document.getElementById("assignModal").style.display = "flex";
        }
        function closeAssignModal() { document.getElementById("assignModal").style.display = "none"; }
        document.getElementById("assignModal").addEventListener("click", function(e) {
            if (e.target === this) closeAssignModal();
        });
    </script>
</body>
</html>
