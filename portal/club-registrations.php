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

$pageTitle = "Club Registrations";
$success = $_SESSION["success"] ?? null;
$errors = $_SESSION["errors"] ?? [];
unset($_SESSION["success"], $_SESSION["errors"]);

$clubModel = new Clubs(db());

// Handle approve
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "approve") {
    $regId = (int)($_POST["id"] ?? 0);
    if ($regId > 0) {
        $clubModel->approveRegistration($regId);
        $_SESSION["success"] = "Club registration approved and club is now active";
    }
    header("Location: club-registrations.php");
    exit;
}

// Handle reject
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "reject") {
    $regId = (int)($_POST["id"] ?? 0);
    $reason = trim($_POST["rejection_reason"] ?? "");
    if ($regId > 0 && $reason !== "") {
        $clubModel->rejectRegistration($regId, $reason);
        $_SESSION["success"] = "Club registration rejected";
    }
    header("Location: club-registrations.php");
    exit;
}

$filter = $_GET["filter"] ?? "pending";
$registrations = $clubModel->getRegistrations($filter === "all" ? null : strtoupper($filter));
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
                <h1 class="header-title">Club Registrations</h1>
                <div class="header-actions">
                    <a href="../logout.php" class="header-btn"><i class="bi bi-box-arrow-right"></i></a>
                </div>
            </header>
            <main class="content-body">
                <div class="dashboard-container">
                    <div class="dashboard-header-section">
                        <h2 class="dashboard-title">Club Registration Reviews</h2>
                        <p class="dashboard-subtitle">Approve or reject club registration submissions from students</p>
                    </div>

                    <?php if ($success): ?>
                        <div style="background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.2);border-radius:8px;padding:16px;margin-bottom:20px;color:#22c55e;"><?php echo htmlspecialchars($success); ?></div>
                    <?php endif; ?>

                    <!-- Filter tabs -->
                    <div style="display:flex;gap:8px;margin-bottom:24px;flex-wrap:wrap;">
                        <a href="?filter=pending" class="btn <?php echo $filter === 'pending' ? 'btn-primary' : 'btn-outline'; ?>">
                            <i class="bi bi-clock"></i> Pending
                        </a>
                        <a href="?filter=approved" class="btn <?php echo $filter === 'approved' ? 'btn-primary' : 'btn-outline'; ?>">
                            <i class="bi bi-check-circle"></i> Approved
                        </a>
                        <a href="?filter=rejected" class="btn <?php echo $filter === 'rejected' ? 'btn-primary' : 'btn-outline'; ?>">
                            <i class="bi bi-x-circle"></i> Rejected
                        </a>
                        <a href="?filter=all" class="btn <?php echo $filter === 'all' ? 'btn-primary' : 'btn-outline'; ?>">
                            <i class="bi bi-files"></i> All
                        </a>
                    </div>

                    <div class="table-container">
                        <?php if (empty($registrations)): ?>
                            <div class="empty-state">
                                <div class="empty-icon"><i class="bi bi-clipboard-check"></i></div>
                                <h3 class="empty-title">No registrations found</h3>
                                <p class="empty-text">
                                    <?php if ($filter === 'pending'): ?>
                                        All pending registrations have been processed.
                                    <?php else: ?>
                                        No registrations match this filter.
                                    <?php endif; ?>
                                </p>
                            </div>
                        <?php else: ?>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Club</th>
                                        <th>President</th>
                                        <th>Contact</th>
                                        <th>Category</th>
                                        <th>Logo</th>
                                        <th>Members</th>
                                        <th>Status</th>
                                        <th>Submitted</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($registrations as $index => $reg): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td>
                                            <div class="user-name"><?php echo htmlspecialchars($reg["club_name"] ?? ""); ?></div>
                                            <div class="user-email"><?php echo htmlspecialchars(truncate($reg["president_student_id"] ?? "", 30)); ?></div>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($reg["president_name"]); ?>
                                        </td>
                                        <td>
                                            <div><?php echo htmlspecialchars($reg["contact_email"]); ?></div>
                                            <div class="user-email"><?php echo htmlspecialchars($reg["contact_phone"] ?? "—"); ?></div>
                                        </td>
                                        <td><span class="badge badge-role"><?php echo htmlspecialchars($reg["category"] ?? "—"); ?></span></td>
                                        <td>
                                            <?php if ($reg["logo_path"]): ?>
                                                <img src="../<?php echo htmlspecialchars($reg["logo_path"]); ?>" style="width:40px;height:40px;border-radius:8px;object-fit:cover;">
                                            <?php else: ?>
                                                <span style="color:#9ca3af;font-size:12px;">No logo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo (int)($reg["initial_members"] ?? 0); ?></td>
                                        <td>
                                            <?php
                                                $s = strtoupper($reg["status"] ?? "PENDING");
                                                $sc = $s === "APPROVED" ? "badge-active" : ($s === "REJECTED" ? "" : "");
                                            ?>
                                            <span class="badge <?php echo $sc; ?>"><?php echo $s; ?></span>
                                        </td>
                                        <td><?php echo formatDate($reg["submitted_at"]); ?></td>
                                        <td>
                                            <?php if ($s === "PENDING"): ?>
                                                <!-- Approve -->
                                                <form method="POST" style="display:inline;" onsubmit="return confirm('Approve this club registration? The club will become active.');">
                                                    <input type="hidden" name="action" value="approve">
                                                    <input type="hidden" name="id" value="<?php echo $reg["id"]; ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline" style="padding:4px 8px;color:#22c55e;border-color:#22c55e;" title="Approve"><i class="bi bi-check-lg"></i></button>
                                                </form>
                                                &nbsp;
                                                <!-- Reject -->
                                                <button type="button" class="btn btn-sm btn-outline" style="padding:4px 8px;color:#ef4444;border-color:#ef4444;" onclick="openRejectModal(<?php echo $reg['id']; ?>, '<?php echo htmlspecialchars($reg['club_name'], ENT_QUOTES); ?>')" title="Reject"><i class="bi bi-x-lg"></i></button>
                                            <?php else: ?>
                                                <span style="font-size:12px;color:#9ca3af;">Processed</span>
                                            <?php endif; ?>
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

    <!-- Reject Reason Modal -->
    <div id="rejectModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center;">
        <div style="background:#fff;border-radius:12px;max-width:480px;width:90%;">
            <div style="padding:24px;border-bottom:1px solid #eee;">
                <h3 style="margin:0;">Reject Registration</h3>
            </div>
            <form method="POST" style="padding:24px;">
                <input type="hidden" name="action" value="reject">
                <input type="hidden" name="id" id="reject_reg_id" value="">
                <p id="reject_club_name" style="margin-bottom:16px;"></p>
                <div class="form-group">
                    <label class="form-label">Rejection Reason *</label>
                    <textarea name="rejection_reason" class="form-input" rows="3" placeholder="Explain why this registration was rejected..." required></textarea>
                </div>
                <div style="display:flex;gap:12px;justify-content:flex-end;margin-top:16px;">
                    <button type="button" onclick="closeRejectModal()" class="btn btn-outline">Cancel</button>
                    <button type="submit" class="btn btn-danger"><i class="bi bi-x-circle"></i> Reject</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../assets/js/sidebar.js"></script>
    <script>
        function openRejectModal(regId, clubName) {
            document.getElementById('reject_reg_id').value = regId;
            document.getElementById('reject_club_name').innerHTML =
                '<strong>' + clubName + '</strong> will remain inactive.';
            document.getElementById('rejectModal').style.display = 'flex';
        }
        function closeRejectModal() { document.getElementById('rejectModal').style.display = 'none'; }
        document.getElementById('rejectModal').addEventListener('click', function(e) {
            if (e.target === this) closeRejectModal();
        });
    </script>
</body>
</html>
