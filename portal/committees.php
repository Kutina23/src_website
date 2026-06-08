<?php
session_start();
require_once "../config/database.php";
require_once "../config/functions.php";
require_once "../models/Committees.php";

if (!isLogged()) {
    header("Location: login.php");
    exit;
}

$currentRole = currentRole();
$currentUser = currentUser();

if (!in_array($currentRole, ["PRO", "PRESIDENT"])) {
    header("Location: index.php");
    exit;
}

$pageTitle = "Committee Management";
$success = $_SESSION["success"] ?? null;
$errors = $_SESSION["errors"] ?? [];
unset($_SESSION["success"], $_SESSION["errors"]);

$committeeModel = new Committees(db());
$memberModel = new CommitteeMembers(db());
$mandateModel = new CommitteeMandates(db());

$committees = $committeeModel->getAll();

// Handle committee CRUD operations
$action = $_GET["action"] ?? "list";
$committeeId = $_GET["committee"] ?? null;
$itemId = $_GET["id"] ?? null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? $_GET["action"] ?? null;
    $committeeId = $_POST["committee"] ?? $_GET["committee"] ?? null;
    $itemId = $_POST["id"] ?? $_GET["id"] ?? null;

    // Handle committee creation/update
    if ($action === "create_committee" || $action === "edit_committee") {
        $data = [
            "name" => $_POST["name"] ?? "",
            "slug" => $_POST["slug"] ?? "",
            "description" => $_POST["description"] ?? "",
            "establishment_clause" => $_POST["establishment_clause"] ?? "",
            "composition_clause" => $_POST["composition_clause"] ?? "",
            "functions_clause" => $_POST["functions_clause"] ?? "",
            "is_active" => isset($_POST["is_active"]) ? 1 : 0
        ];

        if ($action === "create_committee") {
            $committeeId = $committeeModel->create($data);
            
// Add leader member
             $leaderName = trim($_POST["leader_name"] ?? "");
             if ($leaderName) {
                 $memberModel->create([
                     "committee_id" => $committeeId,
                     "name" => $leaderName,
                     "department" => $_POST["leader_department"] ?? "",
                     "role_type" => "chairperson",
                     "role_order" => 1
                 ]);
             }
             
             // Add secretary member
             $secretaryName = trim($_POST["secretary_name"] ?? "");
             if ($secretaryName) {
                 $memberModel->create([
                     "committee_id" => $committeeId,
                     "name" => $secretaryName,
                     "department" => $_POST["secretary_department"] ?? "",
                     "role_type" => "secretary",
                     "role_order" => 2
                 ]);
             }
             
             // Add regular members from textarea (format: Name | Department)
             $i = 0;
             $additionalMembers = $_POST["additional_members"] ?? "";
             $lines = explode("\n", $additionalMembers);
             foreach ($lines as $line) {
                 $line = trim($line);
                 if (strpos($line, "|") !== false) {
                     $parts = explode("|", $line, 2);
                     $memberName = trim($parts[0]);
                     $memberDept = trim($parts[1] ?? "");
                     if ($memberName) {
                         $memberModel->create([
                             "committee_id" => $committeeId,
                             "name" => $memberName,
                             "department" => $memberDept,
                             "role_type" => "member",
                             "role_order" => 3 + $i
                         ]);
                         $i++;
                     }
                 }
             }
            
            $_SESSION["success"] = "Committee created successfully";
        } elseif ($action === "edit_committee" && $committeeId) {
            $committeeModel->update($committeeId, $data);
            $_SESSION["success"] = "Committee updated successfully";
        }
        header("Location: committees.php");
        exit;
    }

    // Handle member creation/update
    if ($action === "create_member" || $action === "edit_member") {
        $data = [
            "committee_id" => $committeeId,
            "name" => $_POST["name"] ?? "",
            "department" => $_POST["department"] ?? "",
            "role_type" => $_POST["role_type"] ?? "member",
            "role_order" => (int)($_POST["role_order"] ?? 0),
            "display_order" => (int)($_POST["display_order"] ?? 0),
            "is_active" => isset($_POST["is_active"]) ? 1 : 0
        ];

        if ($action === "create_member") {
            $memberModel->create($data);
            $_SESSION["success"] = "Committee member added successfully";
        } elseif ($action === "edit_member" && $itemId) {
            $memberModel->update($committeeId, $itemId, $data);
            $_SESSION["success"] = "Committee member updated successfully";
        }
        header("Location: committees.php?committee=$committeeId");
        exit;
    }

    // Handle mandate creation/update
    if ($action === "create_mandate" || $action === "edit_mandate") {
        $data = [
            "committee_id" => $committeeId,
            "title" => $_POST["title"] ?? "",
            "description" => $_POST["description"] ?? "",
            "mandate_order" => (int)($_POST["mandate_order"] ?? 0)
        ];

        if ($action === "create_mandate") {
            $mandateModel->create($data);
            $_SESSION["success"] = "Mandate added successfully";
        } elseif ($action === "edit_mandate" && $itemId) {
            $mandateModel->update($itemId, $data);
            $_SESSION["success"] = "Mandate updated successfully";
        }
        header("Location: committees.php?committee=$committeeId");
        exit;
    }
}

// Handle delete operations
if ($action === "delete_committee" && $committeeId) {
    $committeeModel->delete($committeeId);
    $_SESSION["success"] = "Committee deleted successfully";
    header("Location: committees.php");
    exit;
}

if ($action === "delete_member" && $itemId) {
    $memberModel->delete($itemId);
    $_SESSION["success"] = "Committee member deleted successfully";
    header("Location: committees.php?committee=$committeeId");
    exit;
}

if ($action === "delete_mandate" && $itemId) {
    $mandateModel->delete($itemId);
    $_SESSION["success"] = "Mandate deleted successfully";
    header("Location: committees.php?committee=$committeeId");
    exit;
}

// Get data for editing
$editCommittee = null;
$editMember = null;
$editMandate = null;
$committeeMembers = [];
$committeeMandates = [];

if ($committeeId && $action === "edit_committee") {
    $editCommittee = $committeeModel->getById($committeeId);
    $committeeMembers = $memberModel->getByCommittee($committeeId);
    $committeeMandates = $mandateModel->getByCommittee($committeeId);
    
    // Get leadership members for pre-population
    $chair = array_filter($committeeMembers, fn($m) => $m["role_type"] === "chairperson");
    $sec = array_filter($committeeMembers, fn($m) => $m["role_type"] === "secretary");
    $regularMembers = array_filter($committeeMembers, fn($m) => $m["role_type"] === "member");
    
    $editCommittee["leader_name"] = reset($chair)["name"] ?? "";
    $editCommittee["leader_department"] = reset($chair)["department"] ?? "";
    $editCommittee["secretary_name"] = reset($sec)["name"] ?? "";
    $editCommittee["secretary_department"] = reset($sec)["department"] ?? "";
    
    // Format additional members for textarea
    $memberLines = [];
    foreach ($regularMembers as $m) {
        $memberLines[] = $m["name"] . " | " . ($m["department"] ?? "");
    }
    $editCommittee["additional_members"] = implode("\n", $memberLines);
} elseif ($committeeId) {
    $committeeMembers = $memberModel->getByCommittee($committeeId);
    $committeeMandates = $mandateModel->getByCommittee($committeeId);
}

if ($action === "edit" && $itemId) {
    if (isset($_GET["type"]) && $_GET["type"] === "member") {
        $editMember = $memberModel->getById($itemId);
    } elseif (isset($_GET["type"]) && $_GET["type"] === "mandate") {
        $editMandate = $mandateModel->getById($itemId);
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
            <?php require_once "../include/nav-links.php"; $nav = new NavigationRBAC($currentRole); echo $nav->renderNavigation(); ?>
            <div class="sidebar-user">
                <div class="user-avatar"><?php echo strtoupper(substr($currentUser["first_name"], 0, 1) . substr($currentUser["last_name"], 0, 1)); ?></div>
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($currentUser["first_name"] . " " . $currentUser["last_name"]); ?></div>
                    <div class="user-role"><span class="role-badge admin"><?php echo $currentRole; ?></span></div>
</div>
      </div>

    <!-- Committee Modal -->
        </aside>
        <div class="main-content">
            <header class="dashboard-header">
                <button class="mobile-toggle" id="mobileToggle"><i class="bi bi-list"></i></button>
                <h1 class="header-title">Committee Management</h1>
                <div class="header-actions"><a href="../logout.php" class="header-btn"><i class="bi bi-box-arrow-right"></i></a></div>
            </header>
            <main class="content-body">
                <div class="dashboard-container">
                    <div class="dashboard-header-section">
                        <h2 class="dashboard-title">Committees</h2>
                        <p class="dashboard-subtitle">Manage SRC committee structure and members</p>
                    </div>

                    <?php if ($success): ?>
                        <div style="background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.2);border-radius:8px;padding:16px;margin-bottom:20px;color:#22c55e;"><?php echo $success; ?></div>
                    <?php endif; ?>

                    <div class="table-container">
                        <div style="padding:16px 24px;border-bottom:1px solid rgba(138,155,184,0.1);display:flex;justify-content:space-between;align-items:center;">
                            <h3 style="margin:0;">All Committees</h3>
                            <button type="button" class="btn btn-primary" onclick="openCommitteeModal()"><i class="bi bi-plus"></i> Add Committee</button>
                        </div>

                        <?php if (empty($committees)): ?>
                            <div class="empty-state">
                                <div class="empty-icon"><i class="bi bi-people"></i></div>
                                <h3 class="empty-title">No committees found</h3>
                                <p class="empty-text">Click "Add Committee" to create the first committee</p>
                            </div>
                        <?php else: ?>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Committee</th>
                                        <th>Members</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($committees as $index => $committee): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td>
                                            <div><strong><?php echo htmlspecialchars($committee["name"]); ?></strong></div>
                                            <small style="color:var(--text-muted);"><?php echo htmlspecialchars($committee["description"] ?? ""); ?></small>
                                        </td>
                                        <td><?php echo $committee["member_count"] ?? 0; ?> members</td>
                                        <td><?php echo $committee["is_active"] ? '<span class="badge badge-active">Active</span>' : '<span class="badge">Inactive</span>'; ?></td>
                                        <td>
                                            <a href="?committee=<?php echo $committee["id"]; ?>" class="btn btn-sm btn-primary" style="padding:4px 8px;"><i class="bi bi-eye"></i></a>
                                            <a href="?action=edit_committee&committee=<?php echo $committee["id"]; ?>" class="btn btn-sm btn-outline" style="padding:4px 8px;"><i class="bi bi-pencil"></i></a>
                                            <a href="?action=delete_committee&committee=<?php echo $committee["id"]; ?>" class="btn btn-sm btn-danger" style="padding:4px 8px;" onclick="return confirm('Are you sure?')"><i class="bi bi-trash"></i></a>
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

    <!-- Committee Modal -->
    <div id="committeeModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center;">
        <div style="background:#fff;border-radius:12px;max-width:640px;width:90%;max-height:90vh;overflow-y:auto;">
            <div style="padding:24px;border-bottom:1px solid #eee;display:flex;justify-content:space-between;align-items:center;">
                <h3 style="margin:0;"><?php echo $editCommittee ? "Edit" : "Add"; ?> Committee</h3>
                <button onclick="closeCommitteeModal()" style="background:none;border:none;font-size:24px;cursor:pointer;">&times;</button>
            </div>
            <form method="POST" style="padding:24px;">
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($editCommittee["id"] ?? ""); ?>">
                <input type="hidden" name="action" value="<?php echo $editCommittee ? "edit_committee" : "create_committee"; ?>">

                <div class="form-group">
                    <label class="form-label">Committee Name *</label>
                    <input type="text" name="name" class="form-input" placeholder="e.g. Academic Committee" required value="<?php echo htmlspecialchars($editCommittee["name"] ?? ""); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Slug</label>
                    <input type="text" name="slug" class="form-input" placeholder="e.g. academic-committee" value="<?php echo htmlspecialchars($editCommittee["slug"] ?? ""); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-input" rows="3" placeholder="Brief description of the committee"><?php echo htmlspecialchars($editCommittee["description"] ?? ""); ?></textarea>
                </div>

                <hr style="margin:20px 0;border:none;border-top:1px solid #eee;">
                <h4 style="margin:0 0 16px 0;color:#333;">Leadership</h4>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Chairperson Name</label>
                        <input type="text" name="leader_name" class="form-input" placeholder="Full name" value="<?php echo htmlspecialchars($editCommittee["leader_name"] ?? ""); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Department/Faculty</label>
                        <input type="text" name="leader_department" class="form-input" placeholder="e.g. Computer Science" value="<?php echo htmlspecialchars($editCommittee["leader_department"] ?? ""); ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Secretary Name</label>
                        <input type="text" name="secretary_name" class="form-input" placeholder="Full name" value="<?php echo htmlspecialchars($editCommittee["secretary_name"] ?? ""); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Department/Faculty</label>
                        <input type="text" name="secretary_department" class="form-input" placeholder="e.g. Engineering" value="<?php echo htmlspecialchars($editCommittee["secretary_department"] ?? ""); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Additional Members (one per line, format: Name | Department)</label>
                    <textarea name="additional_members" class="form-input" rows="3" placeholder="John Doe | Computer Science&#10;Jane Smith | Mathematics"><?php echo htmlspecialchars($editCommittee["additional_members"] ?? ""); ?></textarea>
                </div>

                <hr style="margin:20px 0;border:none;border-top:1px solid #eee;">
                <h4 style="margin:0 0 16px 0;color:#333;">Constitutional Clauses</h4>

                <div class="form-group">
                    <label class="form-label">Establishment Clause</label>
                    <textarea name="establishment_clause" class="form-input" rows="2" placeholder="Constitutional basis for establishment"><?php echo htmlspecialchars($editCommittee["establishment_clause"] ?? ""); ?></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Composition Clause</label>
                    <textarea name="composition_clause" class="form-input" rows="2" placeholder="Membership composition rules"><?php echo htmlspecialchars($editCommittee["composition_clause"] ?? ""); ?></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Functions Clause</label>
                    <textarea name="functions_clause" class="form-input" rows="2" placeholder="Committee functions and responsibilities"><?php echo htmlspecialchars($editCommittee["functions_clause"] ?? ""); ?></textarea>
                </div>

                <div class="form-group">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                        <input type="checkbox" name="is_active" value="1" <?php echo (isset($editCommittee["is_active"]) && $editCommittee["is_active"]) ? "checked" : ""; ?>>
                        <span>Active</span>
                    </label>
                </div>

                <div style="display:flex;gap:12px;justify-content:flex-end;margin-top:20px;">
                    <button type="button" onclick="closeCommitteeModal()" class="btn btn-outline">Cancel</button>
                    <button type="submit" class="btn btn-primary"><?php echo $editCommittee ? "Update" : "Create"; ?> Committee</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Committee Details Modal -->
    <?php if ($committeeId && !$editCommittee): 
        $viewCommittee = $committeeModel->getById($committeeId);
        $viewMembers = $memberModel->getByCommittee($committeeId);
    ?>
    <div id="detailsModal" style="display:flex;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center;">
        <div style="background:#fff;border-radius:12px;max-width:700px;width:90%;max-height:90vh;overflow-y:auto;">
            <div style="padding:24px;border-bottom:1px solid #eee;display:flex;justify-content:space-between;align-items:center;">
                <h3 style="margin:0;"><?php echo htmlspecialchars($viewCommittee["name"] ?? "Committee Details"); ?></h3>
                <button onclick="closeDetailsModal()" style="background:none;border:none;font-size:24px;cursor:pointer;">&times;</button>
            </div>
            <div style="padding:24px;">
                <div style="margin-bottom:20px;">
                    <p style="color:#666;margin:0 0 8px 0;"><strong>Description:</strong></p>
                    <p style="margin:0;"><?php echo htmlspecialchars($viewCommittee["description"] ?? "No description provided."); ?></p>
                </div>

                <h4 style="margin:0 0 12px 0;color:#333;border-bottom:1px solid #eee;padding-bottom:8px;">Leadership</h4>
                <div style="margin-bottom:20px;">
                    <?php 
                    $chairperson = array_filter($viewMembers, fn($m) => $m["role_type"] === "chairperson");
                    $secretary = array_filter($viewMembers, fn($m) => $m["role_type"] === "secretary");
                    $members = array_filter($viewMembers, fn($m) => $m["role_type"] === "member");
                    $chair = reset($chairperson);
                    $sec = reset($secretary);
                    ?>
                    <div style="display:flex;gap:20px;flex-wrap:wrap;">
                        <div style="flex:1;min-width:200px;">
                            <p style="color:#666;margin:0 0 4px 0;font-size:13px;">Chairperson</p>
                            <p style="margin:0;font-weight:600;"><?php echo htmlspecialchars($chair["name"] ?? "Not assigned"); ?></p>
                            <?php if (!empty($chair["department"])): ?><small style="color:#888;"><?php echo htmlspecialchars($chair["department"]); ?></small><?php endif; ?>
                        </div>
                        <div style="flex:1;min-width:200px;">
                            <p style="color:#666;margin:0 0 4px 0;font-size:13px;">Secretary</p>
                            <p style="margin:0;font-weight:600;"><?php echo htmlspecialchars($sec["name"] ?? "Not assigned"); ?></p>
                            <?php if (!empty($sec["department"])): ?><small style="color:#888;"><?php echo htmlspecialchars($sec["department"]); ?></small><?php endif; ?>
                        </div>
                    </div>
                </div>

                <h4 style="margin:0 0 12px 0;color:#333;border-bottom:1px solid #eee;padding-bottom:8px;">Committee Members</h4>
                <?php if (empty($members)): ?>
                    <p style="color:#888;">No members assigned.</p>
                <?php else: ?>
                    <div style="display:flex;flex-direction:column;gap:10px;">
                        <?php foreach ($members as $member): ?>
                        <div style="display:flex;justify-content:space-between;align-items:center;padding:10px;background:#f9fafb;border-radius:6px;">
                            <div>
                                <strong><?php echo htmlspecialchars($member["name"]); ?></strong>
                                <?php if (!empty($member["department"])): ?><br><small style="color:#666;"><?php echo htmlspecialchars($member["department"]); ?></small><?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div style="display:flex;justify-content:flex-end;margin-top:20px;">
                    <button type="button" onclick="closeDetailsModal()" class="btn btn-outline">Close</button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script src="../assets/js/sidebar.js"></script>
    <script>
        function openCommitteeModal() { document.getElementById("committeeModal").style.display = "flex"; }
        function closeCommitteeModal() { document.getElementById("committeeModal").style.display = "none"; }
        function closeDetailsModal() { document.getElementById("detailsModal").style.display = "none"; }
        document.getElementById("committeeModal").addEventListener("click", function(e) {
            if (e.target === this) closeCommitteeModal();
        });
        <?php if ($editCommittee && $action === "edit_committee"): ?>
        document.addEventListener("DOMContentLoaded", function() { openCommitteeModal(); });
        <?php endif; ?>
    </script>
</body>
</html>