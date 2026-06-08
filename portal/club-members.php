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

$pageTitle = "Club Members";
$success = $_SESSION["success"] ?? null;
$errors = $_SESSION["errors"] ?? [];
unset($_SESSION["success"], $_SESSION["errors"]);

$clubModel = new Clubs(db());
$clubs = $clubModel->getAll();
$selectedClubId = isset($_GET["club_id"]) ? (int)$_GET["club_id"] : ($clubs[0]["id"] ?? null);
$view = $_GET["view"] ?? "list"; // list | manage

// Handle add member
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "add_member" && $selectedClubId) {
    $userId = (int)($_POST["user_id"] ?? 0);
    $role = $_POST["role"] ?? "MEMBER";

    if ($userId > 0) {
        $existing = db()->fetch("SELECT * FROM club_members WHERE club_id = ? AND user_id = ?", [$selectedClubId, $userId]);
        if ($existing) {
            $clubModel->updateMemberRole($selectedClubId, $userId, $role);
            $_SESSION["success"] = "Member role updated successfully";
        } else {
            $clubModel->addMember($selectedClubId, $userId, $role);
            $_SESSION["success"] = "Member added successfully";
        }
    }
    header("Location: club-members.php?club_id=" . $selectedClubId . "&view=manage");
    exit;
}

// Handle remove member
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "remove_member" && $selectedClubId) {
    $userId = (int)($_POST["user_id"] ?? 0);
    if ($userId > 0) {
        $clubModel->removeMember($selectedClubId, $userId);
        $_SESSION["success"] = "Member removed successfully";
    }
    header("Location: club-members.php?club_id=" . $selectedClubId . "&view=manage");
    exit;
}

$selectedClub = $selectedClubId ? $clubModel->getById($selectedClubId) : null;
$members = $selectedClubId ? $clubModel->getMembers($selectedClubId) : [];
$allUsers = $clubModel->getAllUsers();
$memberRoles = $clubModel->getMemberRoles();
$availableUsers = array_filter($allUsers, function($u) use ($members, $selectedClubId) {
    if (!$selectedClubId) return false;
    foreach ($members as $m) {
        if ($m["user_id"] == $u["id"]) return false;
    }
    return true;
});
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
                <h1 class="header-title">Club Members</h1>
                <div class="header-actions">
                    <a href="../logout.php" class="header-btn"><i class="bi bi-box-arrow-right"></i></a>
                </div>
            </header>
            <main class="content-body">
                <div class="dashboard-container">
                    <div class="dashboard-header-section">
                        <h2 class="dashboard-title">Manage Club Members</h2>
                        <p class="dashboard-subtitle">Add, remove and assign roles to club members</p>
                    </div>

                    <?php if ($success): ?>
                        <div style="background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.2);border-radius:8px;padding:16px;margin-bottom:20px;color:#22c55e;"><?php echo htmlspecialchars($success); ?></div>
                    <?php endif; ?>

                    <!-- Club Selector -->
                    <div style="margin-bottom:24px;">
                        <label class="form-label" style="margin-bottom:8px;">Select Club</label>
                        <div style="display:flex;gap:8px;flex-wrap:wrap;">
                            <?php foreach ($clubs as $c): ?>
                                <a href="?club_id=<?php echo $c["id"]; ?>" class="btn <?php echo $selectedClubId == $c["id"] ? 'btn-primary' : 'btn-outline'; ?>">
                                    <?php echo htmlspecialchars($c["name"]); ?>
                                    <span class="badge" style="margin-left:6px;"><?php echo (int)$c["member_count"]; ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <?php if (!$selectedClub): ?>
                        <div class="empty-state">
                            <div class="empty-icon"><i class="bi bi-people"></i></div>
                            <h3 class="empty-title">No club selected</h3>
                            <p class="empty-text">Select a club from above to manage its members</p>
                        </div>
                    <?php else: ?>

                    <div class="dashboard-header-section" style="margin-bottom:16px;">
                        <h3 class="dashboard-title" style="font-size:1.1rem;">
                            <?php echo htmlspecialchars($selectedClub["name"]); ?>
                            <span class="badge badge-role" style="margin-left:8px;"><?php echo (int)$selectedClub["member_count"]; ?> members</span>
                        </h3>
                    </div>

                    <!-- Add Member Form -->
                    <div class="table-container" style="margin-bottom:24px;">
                        <div style="padding:16px 24px;border-bottom:1px solid rgba(138,155,184,0.1);">
                            <h3 style="margin:0;">Add Member</h3>
                        </div>
                        <div style="padding:20px 24px;">
                            <form method="POST" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
                                <input type="hidden" name="action" value="add_member">
                                <div class="form-group" style="flex:1;min-width:200px;">
                                    <label class="form-label">User</label>
                                    <select name="user_id" class="form-select" required>
                                        <option value="">— Select User —</option>
                                        <?php foreach ($availableUsers as $u): ?>
                                            <option value="<?php echo $u["id"]; ?>">
                                                <?php echo htmlspecialchars($u["first_name"] . " " . $u["last_name"] . " — " . $u["email"]); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Role</label>
                                    <select name="role" class="form-select">
                                        <?php foreach ($memberRoles as $r): ?>
                                            <option value="<?php echo $r; ?>"><?php echo $r; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary"><i class="bi bi-person-plus"></i> Add</button>
                            </form>
                        </div>
                    </div>

                    <!-- Members Table -->
                    <div class="table-container">
                        <div style="padding:16px 24px;border-bottom:1px solid rgba(138,155,184,0.1);">
                            <h3 style="margin:0;">Current Members</h3>
                        </div>
                        <?php if (empty($members)): ?>
                            <div class="empty-state">
                                <div class="empty-icon"><i class="bi bi-people"></i></div>
                                <h3 class="empty-title">No members yet</h3>
                                <p class="empty-text">Add the first member using the form above</p>
                            </div>
                        <?php else: ?>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Member</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Joined</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($members as $index => $m): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td>
                                            <div class="user-info-cell">
                                                <div class="user-avatar-table" style="background:linear-gradient(135deg, #C9A84C, #E8C97A);margin-right:10px;">
                                                    <?php echo strtoupper(substr($m["first_name"], 0, 1) . substr($m["last_name"], 0, 1)); ?>
                                                </div>
                                                <div>
                                                    <div class="user-name"><?php echo htmlspecialchars($m["first_name"] . " " . $m["last_name"]); ?></div>
                                                    <div class="user-email"><?php echo htmlspecialchars($m["student_id"] ?? "—"); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($m["email"]); ?></td>
                                        <td><span class="badge badge-role"><?php echo htmlspecialchars($m["role"]); ?></span></td>
                                        <td><?php echo formatDate($m["joined_date"]); ?></td>
                                        <td>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Remove this member?');">
                                                <input type="hidden" name="action" value="remove_member">
                                                <input type="hidden" name="user_id" value="<?php echo $m["user_id"]; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger" style="padding:4px 8px;"><i class="bi bi-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>
    <script src="../assets/js/sidebar.js"></script>
</body>
</html>
