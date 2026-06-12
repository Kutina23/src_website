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

$pageTitle = "Clubs Management";
$success = $_SESSION["success"] ?? null;
$errors = $_SESSION["errors"] ?? [];
unset($_SESSION["success"], $_SESSION["errors"]);

$clubModel = new Clubs(db());
$allUsers = $clubModel->getAllUsers();
$clubs = $clubModel->getAll();
$categories = $clubModel->getCategories();
$stats = [
    "active" => $clubModel->countActive(),
    "inactive" => $clubModel->countInactive(),
    "suspended" => $clubModel->countSuspended(),
    "pending_registrations" => $clubModel->countPendingRegistrations()
];

// Handle CRUD operations
$action = $_GET["action"] ?? "list";
$id = $_GET["id"] ?? null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? $_GET["action"] ?? null;
    $id = $_POST["id"] ?? $_GET["id"] ?? null;

    $name = trim($_POST["name"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $category = $_POST["category"] ?? "";
    $status = $_POST["status"] ?? "ACTIVE";
    $presidentId = !empty($_POST["president_id"]) ? (int)$_POST["president_id"] : null;
    $advisorId = !empty($_POST["advisor_id"]) ? (int)$_POST["advisor_id"] : null;
    $foundedDate = $_POST["founded_date"] ?: null;
    $meetingDay = $_POST["meeting_day"] ?? "";
    $meetingTime = $_POST["meeting_time"] ?? "";
    $meetingLocation = $_POST["meeting_location"] ?? "";
    $logoPath = null;

    // Handle image upload
    if (isset($_FILES["logo"]) && $_FILES["logo"]["error"] === UPLOAD_ERR_OK) {
        $uploadDir = "../uploads/clubs/";
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $fileExt = pathinfo($_FILES["logo"]["name"], PATHINFO_EXTENSION);
        $fileName = "club_" . time() . "_" . uniqid() . "." . $fileExt;
        $filePath = $uploadDir . $fileName;
        if (move_uploaded_file($_FILES["logo"]["tmp_name"], $filePath)) {
            $logoPath = "uploads/clubs/" . $fileName;
        }
    }

    $data = [
        "name" => $name,
        "description" => $description ?: null,
        "president_id" => $presidentId,
        "advisor_id" => $advisorId,
        "category" => $category ?: null,
        "status" => $status,
        "founded_date" => $foundedDate,
        "meeting_day" => $meetingDay ?: null,
        "meeting_time" => $meetingTime ?: null,
        "meeting_location" => $meetingLocation ?: null
    ];

    if ($logoPath) {
        $data["logo_path"] = $logoPath;
    }

    if ($action === "create") {
        $clubModel->create($data);
        $_SESSION["success"] = "Club created successfully";
    } elseif ($action === "edit" && $id) {
        $clubModel->update((int)$id, $data);
        $_SESSION["success"] = "Club updated successfully";
    }

    header("Location: clubs.php");
    exit;
}

// Handle delete
if ($action === "delete" && $id) {
    $club = $clubModel->getById($id);
    if ($club["member_count"] > 0) {
        $_SESSION["errors"] = ["Cannot delete club with existing members. Remove all members first."];
    } else {
        $clubModel->delete((int)$id);
        $_SESSION["success"] = "Club deleted successfully";
    }
    header("Location: clubs.php");
    exit;
}

// Get club for editing
$editClub = null;
if ($action === "edit" && $id) {
    $editClub = $clubModel->getById($id);
}

// Build category options
$categoryOptions = array_unique(array_merge(
    $categories,
    ["Technology", "Sports", "Arts & Culture", "Academics", "Music", "Media", "Governance", "Sustainability", "Other"]
));
sort($categoryOptions);

// Build status options
$statusOptions = ["ACTIVE", "INACTIVE", "SUSPENDED"];
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
                <h1 class="header-title">Clubs &amp; Societies</h1>
                <div class="header-actions">
                    <a href="../logout.php" class="header-btn"><i class="bi bi-box-arrow-right"></i></a>
                </div>
            </header>
            <main class="content-body">
                <div class="dashboard-container">
                    <div class="dashboard-header-section">
                        <h2 class="dashboard-title">Clubs &amp; Societies Management</h2>
                        <p class="dashboard-subtitle">Register, approve and manage student clubs and societies</p>
                    </div>

                    <?php if ($success): ?>
                        <div style="background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.2);border-radius:8px;padding:16px;margin-bottom:20px;color:#22c55e;"><?php echo htmlspecialchars($success); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($errors)): ?>
                        <div style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.2);border-radius:8px;padding:16px;margin-bottom:20px;color:#ef4444;">
                            <?php foreach ($errors as $error): echo htmlspecialchars($error) . "<br>"; endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Stats -->
    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon"><i class="bi bi-collection"></i></div>
                            <div class="stat-info">
                                <div class="stat-value"><?php echo count($clubs); ?></div>
                                <div class="stat-label">Total Clubs</div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon" style="background:rgba(34,197,94,0.15);color:#22c55e;"><i class="bi bi-check-circle"></i></div>
                            <div class="stat-info">
                                <div class="stat-value"><?php echo $stats["active"]; ?></div>
                                <div class="stat-label">Active</div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon" style="background:rgba(251,191,36,0.15);color:#f59e0b;"><i class="bi bi-pause-circle"></i></div>
                            <div class="stat-info">
                                <div class="stat-value"><?php echo $stats["inactive"]; ?></div>
                                <div class="stat-label">Inactive</div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon" style="background:rgba(239,68,68,0.15);color:#ef4444;"><i class="bi bi-x-circle"></i></div>
                            <div class="stat-info">
                                <div class="stat-value"><?php echo $stats["suspended"]; ?></div>
                                <div class="stat-label">Suspended</div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <a href="club-registrations.php" style="text-decoration:none;display:block;">
                                <div class="stat-icon" style="background:rgba(59,130,246,0.15);color:#3b82f6;">
                                    <i class="bi bi-clipboard-check"></i>
                                    <?php if ($stats["pending_registrations"] > 0): ?>
                                        <span style="position:absolute;top:-4px;right:-4px;background:#ef4444;color:#fff;border-radius:50%;width:20px;height:20px;font-size:11px;display:flex;align-items:center;justify-content:center;"><?php echo $stats["pending_registrations"]; ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="stat-info">
                                    <div class="stat-value"><?php echo $stats["pending_registrations"]; ?></div>
                                    <div class="stat-label">Pending Registrations</div>
                                </div>
                            </a>
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="table-container">
                        <div style="padding:16px 24px;border-bottom:1px solid rgba(138,155,184,0.1);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
                            <h3 style="margin:0;">Clubs &amp; Societies</h3>
                            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                                <a href="club-registrations.php" class="btn btn-outline">
                                    <i class="bi bi-clipboard-check"></i> Registrations
                                    <?php if ($stats["pending_registrations"] > 0): ?>
                                        <span class="badge" style="background:#ef4444;color:#fff;border-radius:50%;width:18px;height:18px;display:inline-flex;align-items:center;justify-content:center;font-size:11px;margin-left:4px;vertical-align:top;"><?php echo $stats["pending_registrations"]; ?></span>
                                    <?php endif; ?>
                                </a>
                                <a href="club-members.php" class="btn btn-outline"><i class="bi bi-people"></i> Members</a>
                                <a href="club-presidents.php" class="btn btn-outline"><i class="bi bi-person-badge"></i> Presidents</a>
                                <button type="button" class="btn btn-primary" onclick="openModal()"><i class="bi bi-plus"></i> Add Club</button>
                            </div>
                        </div>
                        </div>

                        <?php if (empty($clubs)): ?>
                            <div class="empty-state">
                                <div class="empty-icon"><i class="bi bi-collection"></i></div>
                                <h3 class="empty-title">No clubs found</h3>
                                <p class="empty-text">Click "Add Club" to register the first club</p>
                            </div>
                        <?php else: ?>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Club</th>
                                        <th>Category</th>
                                        <th>President</th>
                                        <th>Advisor</th>
                                        <th>Members</th>
                                        <th>Meeting</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($clubs as $index => $club): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td>
                                            <div class="user-info-cell">
                                                <?php if ($club["logo_path"]): ?>
                                                    <img src="../<?php echo htmlspecialchars($club["logo_path"]); ?>" style="width:40px;height:40px;border-radius:8px;object-fit:cover;margin-right:10px;">
                                                <?php else: ?>
                                                    <div class="user-avatar-table" style="background:linear-gradient(135deg, #C9A84C, #E8C97A);margin-right:10px;border-radius:8px;">
                                                        <?php echo strtoupper(substr($club["name"], 0, 2)); ?>
                                                    </div>
                                                <?php endif; ?>
                                                <div>
                                                    <div class="user-name"><?php echo htmlspecialchars($club["name"]); ?></div>
                                                    <div class="user-email"><?php echo htmlspecialchars($club["description"] ? truncate($club["description"], 60) : "No description"); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><span class="badge badge-role"><?php echo htmlspecialchars($club["category"] ?? "—"); ?></span></td>
                                        <td><?php echo $club["president_first"] ? htmlspecialchars($club["president_first"] . " " . $club["president_last"]) : "—"; ?></td>
                                        <td><?php echo $club["advisor_first"] ? htmlspecialchars($club["advisor_first"] . " " . $club["advisor_last"]) : "—"; ?></td>
                                        <td><?php echo (int)$club["member_count"]; ?></td>
                                        <td><?php echo $club["meeting_day"] ? htmlspecialchars($club["meeting_day"] . " " . substr($club["meeting_time"], 0, 5)) : "—"; ?></td>
                                        <td>
                                            <?php $statusClass = $club["status"] === "ACTIVE" ? "badge-active" : ($club["status"] === "SUSPENDED" ? "badge" : ""); ?>
                                            <span class="badge <?php echo $statusClass; ?>"><?php echo htmlspecialchars($club["status"]); ?></span>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline" style="padding:4px 8px;" onclick="openEditModal(this)" data-club="<?php echo htmlspecialchars(json_encode($club), ENT_QUOTES); ?>" title="Edit"><i class="bi bi-pencil"></i></button>
                                            <a href="?action=delete&id=<?php echo $club["id"]; ?>" class="btn btn-sm btn-danger" style="padding:4px 8px;" onclick="return confirm('Are you sure you want to delete this club?')"><i class="bi bi-trash"></i></a>
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

    <!-- Modal -->
    <div id="clubModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center;">
        <div style="background:#fff;border-radius:12px;max-width:640px;width:90%;max-height:90vh;overflow-y:auto;">
            <div style="padding:24px;border-bottom:1px solid #eee;display:flex;justify-content:space-between;align-items:center;">
                <h3 style="margin:0;" data-modal-title><?php echo $editClub ? "Edit" : "Add"; ?> Club</h3>
                <button onclick="closeModal()" style="background:none;border:none;font-size:24px;cursor:pointer;">&times;</button>
            </div>
            <form method="POST" enctype="multipart/form-data" style="padding:24px;">
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($editClub["id"] ?? ""); ?>">
                <input type="hidden" name="action" value="<?php echo $editClub ? "edit" : "create"; ?>">

                <div class="form-group">
                    <label class="form-label">Club Name *</label>
                    <input type="text" name="name" class="form-input" placeholder="e.g. Computer Science Club" required value="<?php echo htmlspecialchars($editClub["name"] ?? ""); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-input" rows="3" placeholder="Brief description of the club"><?php echo htmlspecialchars($editClub["description"] ?? ""); ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Category</label>
                        <select name="category" class="form-select">
                            <option value="">— Select Category —</option>
                            <?php foreach ($categoryOptions as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo (isset($editClub["category"]) && $editClub["category"] == $cat) ? "selected" : ""; ?>><?php echo htmlspecialchars($cat); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <?php foreach ($statusOptions as $s): ?>
                                <option value="<?php echo $s; ?>" <?php echo (isset($editClub["status"]) && $editClub["status"] == $s) ? "selected" : ""; ?>><?php echo $s; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Club President</label>
                        <select name="president_id" class="form-select">
                            <option value="">— Unassigned —</option>
                            <?php foreach ($allUsers as $u): ?>
                                <option value="<?php echo $u["id"]; ?>" <?php echo (isset($editClub["president_id"]) && $editClub["president_id"] == $u["id"]) ? "selected" : ""; ?>>
                                    <?php echo htmlspecialchars($u["first_name"] . " " . $u["last_name"] . " (" . $u["email"] . ")"); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Faculty Advisor</label>
                        <select name="advisor_id" class="form-select">
                            <option value="">— Unassigned —</option>
                            <?php foreach ($allUsers as $u): ?>
                                <option value="<?php echo $u["id"]; ?>" <?php echo (isset($editClub["advisor_id"]) && $editClub["advisor_id"] == $u["id"]) ? "selected" : ""; ?>>
                                    <?php echo htmlspecialchars($u["first_name"] . " " . $u["last_name"] . " (" . $u["email"] . ")"); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Founded Date</label>
                        <input type="date" name="founded_date" class="form-input" value="<?php echo htmlspecialchars($editClub["founded_date"] ?? ""); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Logo</label>
                        <input type="file" name="logo" class="form-input" accept="image/*">
                        <?php if (!empty($editClub["logo_path"])): ?>
                            <img src="../<?php echo htmlspecialchars($editClub["logo_path"]); ?>" style="height:48px;object-fit:cover;border-radius:4px;margin-top:8px;">
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Meeting Day</label>
                        <select name="meeting_day" class="form-select">
                            <option value="">— None —</option>
                            <?php $days = ["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday"]; foreach ($days as $d): ?>
                                <option value="<?php echo $d; ?>" <?php echo (isset($editClub["meeting_day"]) && $editClub["meeting_day"] == $d) ? "selected" : ""; ?>><?php echo $d; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Meeting Time</label>
                        <input type="time" name="meeting_time" class="form-input" value="<?php echo htmlspecialchars($editClub["meeting_time"] ?? ""); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Meeting Location</label>
                    <input type="text" name="meeting_location" class="form-input" placeholder="e.g. Lab Block A, Room 203" value="<?php echo htmlspecialchars($editClub["meeting_location"] ?? ""); ?>">
                </div>

                <div style="display:flex;gap:12px;justify-content:flex-end;margin-top:20px;">
                    <button type="button" onclick="closeModal()" class="btn btn-outline">Cancel</button>
                    <button type="submit" class="btn btn-primary" data-modal-submit><?php echo $editClub ? "Update" : "Create"; ?> Club</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../assets/js/sidebar.js"></script>
    <script src="../assets/js/loader-service.js"></script>
    <script>
        function resetClubForm() {
            const modal = document.getElementById('clubModal');
            const form = modal.querySelector('form');
            form.querySelector('input[name="id"]').value = '';
            form.querySelector('input[name="action"]').value = 'create';
            form.querySelector('input[name="name"]').value = '';
            form.querySelector('textarea[name="description"]').value = '';
            form.querySelector('select[name="category"]').value = '';
            form.querySelector('select[name="status"]').value = 'ACTIVE';
            form.querySelector('select[name="president_id"]').value = '';
            form.querySelector('select[name="advisor_id"]').value = '';
            form.querySelector('input[name="founded_date"]').value = '';
            form.querySelector('select[name="meeting_day"]').value = '';
            form.querySelector('input[name="meeting_time"]').value = '';
            form.querySelector('input[name="meeting_location"]').value = '';
            form.querySelector('input[name="logo"]').value = '';
            modal.querySelector('[data-modal-title]').textContent = 'Add Club';
            modal.querySelector('[data-modal-submit]').textContent = 'Create Club';
        }

        function openModal() {
            resetClubForm();
            document.getElementById('clubModal').style.display = 'flex';
        }

        function openEditModal(button) {
            const club = JSON.parse(button.getAttribute('data-club'));
            const modal = document.getElementById('clubModal');
            const form = modal.querySelector('form');

            form.querySelector('input[name="id"]').value = club.id;
            form.querySelector('input[name="action"]').value = 'edit';
            form.querySelector('input[name="name"]').value = club.name || '';
            form.querySelector('textarea[name="description"]').value = club.description || '';
            form.querySelector('select[name="category"]').value = club.category || '';
            form.querySelector('select[name="status"]').value = club.status || 'ACTIVE';
            form.querySelector('select[name="president_id"]').value = club.president_id || '';
            form.querySelector('select[name="advisor_id"]').value = club.advisor_id || '';
            form.querySelector('input[name="founded_date"]').value = club.founded_date || '';
            form.querySelector('select[name="meeting_day"]').value = club.meeting_day || '';
            form.querySelector('input[name="meeting_time"]').value = club.meeting_time || '';
            form.querySelector('input[name="meeting_location"]').value = club.meeting_location || '';
            modal.querySelector('[data-modal-title]').textContent = 'Edit Club';
            modal.querySelector('[data-modal-submit]').textContent = 'Update Club';
            modal.style.display = 'flex';
        }

        function closeModal() { document.getElementById('clubModal').style.display = 'none'; }
        document.getElementById('clubModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });

        <?php if ($editClub): ?>
        document.getElementById('clubModal').style.display = 'flex';
        <?php endif; ?>
    </script>
</body>
</html>
