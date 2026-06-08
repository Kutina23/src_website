<?php
session_start();
require_once "../config/database.php";
require_once "../config/functions.php";
require_once "../models/ExecutiveMembers.php";
require_once "../models/GaSessions.php";

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

$pageTitle = "Executive Members";
$success = $_SESSION["success"] ?? null;
$errors = $_SESSION["errors"] ?? [];
unset($_SESSION["success"], $_SESSION["errors"]);

$execModel = new ExecutiveMembers(db());
$members = $execModel->getAll();
$positions = $execModel->getAvailablePositions();
$availableUsers = $execModel->getAvailableUsers();
$departments = $execModel->getDepartments();

$gaModel              = new GaSessions(db());
$upcomingAgmSession   = $gaModel->getUpcomingSessionForAgenda();
$agendaPdfUrl         = $upcomingAgmSession["minutes_url"] ?? "";
$agendaPdfUrl         = preg_match('#^uploads/#', $agendaPdfUrl)
    ? "../" . ltrim($agendaPdfUrl, "/")
    : $agendaPdfUrl;
// Guard: if URL is empty or doesn't look like a file, treat as missing
$agendaExists         = $agendaPdfUrl !== "" && preg_match('/\.(pdf|PDF)$/', $agendaPdfUrl);

// Handle CRUD operations
$action = $_GET["action"] ?? "list";
$id = $_GET["id"] ?? null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $db = db();
    // Try to add missing columns if they don't exist
    $columnsToAdd = [
        ['bio', 'TEXT'],
        ['linkedin', 'VARCHAR(255)'],
        ['facebook', 'VARCHAR(255)'],
        ['tiktok', 'VARCHAR(255)']
    ];
    foreach ($columnsToAdd as [$columnName, $columnType]) {
        try {
            $db->execute("ALTER TABLE users ADD COLUMN `$columnName` $columnType NULL");
        } catch (PDOException $e) {
            // Ignore if column already exists
            if (strpos($e->getMessage(), 'Duplicate column name') === false) {
                // Re-throw if it's a different error
                throw $e;
            }
        }
    }
    $action = $_POST["action"] ?? $_GET["action"] ?? null;
    $id = $_POST["id"] ?? $_GET["id"] ?? null;

    $position = $_POST["position"] ?? "";
    $userName = trim($_POST["user_name"] ?? "");
    $phoneNumber = trim($_POST["phone_number"] ?? "");
    $staffId = trim($_POST["staff_id"] ?? "");
    $officeLocation = trim($_POST["office_location"] ?? "");
    $officeHours = trim($_POST["office_hours"] ?? "");
    $appointmentInfo = trim($_POST["appointment_info"] ?? "");
    $keyResponsibilities = trim($_POST["key_responsibilities"] ?? "");
     $bio = trim($_POST["bio"] ?? "");
     $email = trim($_POST["email"] ?? "");
     $linkedin = trim($_POST["linkedin"] ?? "");
     $facebook = trim($_POST["facebook"] ?? "");
     $tiktok = trim($_POST["tiktok"] ?? "");
     $userId = null;
    if ($userName !== "") {
        $parts = explode(" ", $userName, 2);
        $first = $parts[0];
        $last = $parts[1] ?? "";
        $user = db()->fetch("SELECT id FROM users WHERE first_name = ? AND last_name = ?", [$first, $last]);
        if ($user) {
            $userId = $user["id"];
            // Update user profile fields if provided
            $updateData = [];
            if ($phoneNumber !== "") {
                $updateData["phone"] = $phoneNumber;
            }
            if ($staffId !== "") {
                $updateData["staff_id"] = $staffId;
            }
            if ($officeLocation !== "") {
                $updateData["office_location"] = $officeLocation;
            }
            if ($officeHours !== "") {
                $updateData["office_hours"] = $officeHours;
            }
            if ($appointmentInfo !== "") {
                $updateData["appointment_info"] = $appointmentInfo;
            }
            if ($keyResponsibilities !== "") {
                $updateData["key_responsibilities"] = $keyResponsibilities;
            }
            
             if ($bio !== "") {
                 $updateData["bio"] = $bio;
             }
             if ($email !== "") {
                 $updateData["email"] = $email;
             }
             if ($linkedin !== "") {
                 $updateData["linkedin"] = $linkedin;
             }
             if ($facebook !== "") {
                 $updateData["facebook"] = $facebook;
             }
             if ($tiktok !== "") {
                 $updateData["tiktok"] = $tiktok;
             }

             if (!empty($updateData)) {
                $setClause = implode(", ", array_map(fn($k) => "$k = ?", array_keys($updateData)));
                $values = array_values($updateData);
                $values[] = $userId;
                db()->execute("UPDATE users SET $setClause WHERE id = ?", $values);
            }
        } else {
               $studentId = "STU" . str_pad(db()->fetch("SELECT COALESCE(MAX(CAST(SUBSTRING(student_id, 4) AS UNSIGNED)), 0) + 1 AS next FROM users WHERE student_id LIKE 'STU%'")["next"], 6, "0", STR_PAD_LEFT);
                $userId = db()->insert("users", [
                    "student_id" => $studentId,
                    "first_name" => $first,
                    "last_name" => $last,
                    "email" => $email ?: strtolower(str_replace(" ", ".", $userName)) . "@dlo.edu.gh",
                    "phone" => $phoneNumber,
                    "staff_id" => $staffId,
                    "office_location" => $officeLocation,
                    "office_hours" => $officeHours,
                    "appointment_info" => $appointmentInfo,
                    "key_responsibilities" => $keyResponsibilities,
                    "bio" => $bio,
                    "linkedin" => $linkedin,
                    "facebook" => $facebook,
                    "tiktok" => $tiktok,
                    "password_hash" => password_hash("password123", PASSWORD_DEFAULT),
                    "role_id" => 5,
                    "is_active" => 1
                ]);
        }
    }
    $departmentId = $_POST["department_id"] ?: null;
    $termStart = $_POST["term_start"] ?? date("Y-m-d");
    $termEnd = $_POST["term_end"] ?: null;
    $displayOrder = (int)($_POST["display_order"] ?? 0);
    $isActive = isset($_POST["is_active"]) ? 1 : 0;

    $data = [
        "user_id" => $userId,
        "position" => $position,
        "department_id" => $departmentId,
        "term_start" => $termStart,
        "term_end" => $termEnd,
        "display_order" => $displayOrder,
        "is_active" => $isActive
    ];

    // Handle image upload
    if (isset($_FILES["profile_image"]) && $_FILES["profile_image"]["error"] === UPLOAD_ERR_OK) {
        $uploadDir = "../uploads/executive/";
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        
        $fileExt = pathinfo($_FILES["profile_image"]["name"], PATHINFO_EXTENSION);
        $fileName = "exec_" . time() . "_" . uniqid() . "." . $fileExt;
        $filePath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $filePath)) {
            $mediaId = db()->insert("media", [
                "file_name" => $_FILES["profile_image"]["name"],
                "file_path" => "uploads/executive/" . $fileName,
                "file_type" => "IMAGE",
                "mime_type" => mime_content_type($filePath),
                "file_size" => $_FILES["profile_image"]["size"],
                "alt_text" => $_POST["alt_text"] ?? "",
                "uploaded_by" => $_SESSION["user_id"]
            ]);
            $data["profile_image_id"] = $mediaId;
        }
    }

    if ($action === "create") {
        $execModel->create($data);
        $_SESSION["success"] = "Executive member created successfully";
    } elseif ($action === "edit" && $id) {
        $execModel->update($id, $data);
        $_SESSION["success"] = "Executive member updated successfully";
    }
    
    header("Location: council.php");
    exit;
}

// Handle delete
if ($action === "delete" && $id) {
    $execModel->delete($id);
    $_SESSION["success"] = "Executive member deleted successfully";
    header("Location: council.php");
    exit;
}

// Get member for editing
$editMember = null;
if ($action === "edit" && $id) {
    $editMember = $execModel->getById($id);
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
     <style>
       .modal-overlay { display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center; }
       .modal-wrap { background:var(--navy);border:1px solid rgba(201,168,76,0.2);border-radius:12px;max-width:500px;width:90%;max-height:90vh;overflow-y:auto; }
       .modal-wrap--center { background:#fff;border-radius:12px;max-width:600px;width:90%;max-height:90vh;overflow-y:auto; }
       .modal-header-custom { padding:24px;border-bottom:1px solid rgba(201,168,76,0.1);display:flex;justify-content:space-between;align-items:center; }
       .modal-header-custom h3 { margin:0; }
       .modal-header-close { background:none;border:none;font-size:24px;cursor:pointer;color:var(--text-muted); }
       .modal-header-close:hover { background:rgba(255,255,255,0.05);color:var(--cream); }
       .modal-form-wrap { padding:24px; }
       .modal-actions { display:flex;gap:12px;justify-content:flex-end;margin-top:20px; }
      </style>
      <style>
         .user-phone {
           font-size: 14px;
           color: var(--dashboard-text-muted);
           margin-top: 4px;
           display: block;
         }
         .user-staff-id {
           font-size: 13px;
           color: var(--dashboard-text-muted);
           margin-top: 2px;
           display: block;
           font-family: monospace;
         }
         .user-office-info {
           font-size: 13px;
           color: var(--dashboard-text-muted);
           margin-top: 2px;
           display: block;
         }
         .user-office-location,
         .user-office-hours,
         .user-appointment {
           display: inline-block;
           margin-right: 8px;
         }
         .user-office-location::before {
           content: "📍 ";
         }
         .user-office-hours::before {
           content: "⏰ ";
         }
         .user-appointment::before {
           content: "📅 ";
         }
         .user-key-responsibilities {
           font-size: 13px;
           color: var(--dashboard-text);
           margin-top: 4px;
           display: block;
           font-style: italic;
         }
       </style>
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
                <h1 class="header-title">Executive Members</h1>
                <div class="header-actions"><a href="../logout.php" class="header-btn"><i class="bi bi-box-arrow-right"></i></a></div>
            </header>
            <main class="content-body">
                <div class="dashboard-container">
                    <div class="dashboard-header-section">
                        <h2 class="dashboard-title">Executive Members Management</h2>
                        <p class="dashboard-subtitle">Manage SRC Executive Council members with profile image uploads</p>
                    </div>

                    <?php if ($success): ?>
                        <div style="background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.2);border-radius:8px;padding:16px;margin-bottom:20px;color:#22c55e;"><?php echo $success; ?></div>
                    <?php endif; ?>

                    <div class="table-container">
                        <div style="padding:16px 24px;border-bottom:1px solid rgba(138,155,184,0.1);display:flex;justify-content:space-between;align-items:center;">
                            <h3 style="margin:0;">Council Members</h3>
                            <button type="button" class="btn btn-primary" onclick="openModal()"><i class="bi bi-plus"></i> Add Member</button>
                        </div>
                        
                        <?php if (empty($members)): ?>
                            <div class="empty-state">
                                <div class="empty-icon"><i class="bi bi-person-gear"></i></div>
                                <h3 class="empty-title">No executive members found</h3>
                                <p class="empty-text">Click "Add Member" to create the first executive member</p>
                            </div>
                        <?php else: ?>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Member</th>
                                        <th>Position</th>
                                        <th>Department</th>
                                        <th>Term</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($members as $index => $member): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td>
                                            <div class="user-info-cell">
                                                <?php if ($member["profile_image_path"]): ?>
                                                    <img src="../<?php echo htmlspecialchars($member["profile_image_path"]); ?>" style="width:40px;height:40px;border-radius:50%;object-fit:cover;margin-right:10px;">
                                                <?php else: ?>
                                                    <div class="user-avatar-table" style="background:linear-gradient(135deg, #C9A84C, #E8C97A);margin-right:10px;">
                                                        <?php echo strtoupper(substr($member["first_name"], 0, 1) . substr($member["last_name"], 0, 1)); ?>
                                                    </div>
                                                <?php endif; ?>
                                                 <div>
                                                     <div class="user-name"><?php echo htmlspecialchars($member["first_name"] . " " . $member["last_name"]); ?></div>
                                                     <div class="user-email"><?php echo htmlspecialchars($member["email"]); ?></div>
                                                     <?php if (!empty($member["phone"])): ?>
                                                     <div class="user-phone"><?php echo htmlspecialchars($member["phone"]); ?></div>
                                                     <?php endif; ?>
                                                     <?php if (!empty($member["staff_id"])): ?>
                                                     <div class="user-staff-id"><?php echo htmlspecialchars($member["staff_id"]); ?></div>
                                                     <?php endif; ?>
                                                     <?php if (!empty($member["office_location"]) || !empty($member["office_hours"]) || !empty($member["appointment_info"])): ?>
                                                     <div class="user-office-info">
                                                         <?php if (!empty($member["office_location"])): ?>
                                                         <span class="user-office-location"><?php echo htmlspecialchars($member["office_location"]); ?></span>
                                                         <?php endif; ?>
                                                         <?php if (!empty($member["office_hours"])): ?>
                                                         <span class="user-office-hours"><?php echo htmlspecialchars($member["office_hours"]); ?></span>
                                                         <?php endif; ?>
                                                         <?php if (!empty($member["appointment_info"])): ?>
                                                         <span class="user-appointment"><?php echo htmlspecialchars($member["appointment_info"]); ?></span>
                                                         <?php endif; ?>
                                                     </div>
                                                     <?php endif; ?>
                                                     <?php if (!empty($member["key_responsibilities"])): ?>
                                                     <div class="user-key-responsibilities"><?php echo htmlspecialchars($member["key_responsibilities"]); ?></div>
                                                     <?php endif; ?>
                                                 </div>
                                            </div>
                                        </td>
                                        <td><span class="badge badge-role"><?php echo htmlspecialchars($member["position"]); ?></span></td>
                                        <td><?php echo htmlspecialchars($member["department"] ?? "�"); ?></td>
                                        <td><?php echo date("M Y", strtotime($member["term_start"])); ?> - <?php echo $member["term_end"] ? date("M Y", strtotime($member["term_end"])) : "Present"; ?></td>
                                        <td><?php echo $member["is_active"] ? '<span class="badge badge-active">Active</span>' : '<span class="badge">Inactive</span>'; ?></td>
                                        <td>
                                            <a href="?action=edit&id=<?php echo $member["id"]; ?>" class="btn btn-sm btn-outline" style="padding:4px 8px;"><i class="bi bi-pencil"></i></a>
                                            <a href="?action=delete&id=<?php echo $member['id']; ?>" class="btn btn-sm btn-danger" style="padding:4px 8px;" onclick="return confirm('Are you sure?')"><i class="bi bi-trash"></i></a>
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
    <div id="memberModal" class="modal-overlay">
        <div class="modal-wrap modal-wrap--center">
            <div class="modal-header-custom">
                <h3><?php echo $editMember ? "Edit" : "Add"; ?> Executive Member</h3>
                <button type="button" class="modal-header-close" onclick="closeModal()">&times;</button>
            </div>
            <form method="POST" enctype="multipart/form-data" class="modal-form-wrap">
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($editMember["id"] ?? ""); ?>">
                <input type="hidden" name="action" value="<?php echo $editMember ? "edit" : "create"; ?>">
                 <div class="form-group">
                     <label class="form-label">User *</label>
                     <input type="text" name="user_name" class="form-input" placeholder="Enter user full name" required
                            value="<?php echo $editMember ? htmlspecialchars(($editMember["first_name"] ?? "") . " " . ($editMember["last_name"] ?? "")) : ""; ?>">
                 </div>
                  <div class="form-group">
                      <label class="form-label">Phone Number</label>
                      <input type="tel" name="phone_number" class="form-input" placeholder="Enter phone number"
                             value="<?php echo $editMember ? htmlspecialchars($editMember["phone"] ?? "") : ""; ?>">
                  </div>
                  <div class="form-group">
                      <label class="form-label">Staff ID</label>
                      <input type="text" name="staff_id" class="form-input" placeholder="Enter staff ID (e.g., STF-0001)"
                             value="<?php echo $editMember ? htmlspecialchars($editMember["staff_id"] ?? "") : ""; ?>">
                  </div>
                  <div class="form-group">
                      <label class="form-label">Office Location</label>
                      <input type="text" name="office_location" class="form-input" placeholder="Enter office location (e.g., Building A, Room 201)"
                             value="<?php echo $editMember ? htmlspecialchars($editMember["office_location"] ?? "") : ""; ?>">
                  </div>
                  <div class="form-group">
                      <label class="form-label">Office Hours</label>
                      <input type="text" name="office_hours" class="form-input" placeholder="Enter office hours (e.g., Mon-Fri 09:00-17:00)"
                             value="<?php echo $editMember ? htmlspecialchars($editMember["office_hours"] ?? "") : ""; ?>">
                  </div>
                  <div class="form-group">
                      <label class="form-label">Appointment Info</label>
                      <input type="text" name="appointment_info" class="form-input" placeholder="Enter appointment requirements (e.g., Appointment Required)"
                             value="<?php echo $editMember ? htmlspecialchars($editMember["appointment_info"] ?? "") : ""; ?>">
                  </div>
                  <div class="form-group">
                      <label class="form-label">Key Responsibilities</label>
                      <textarea name="key_responsibilities" class="form-input" placeholder="Enter key responsibilities" rows="3"><?php echo htmlspecialchars($editMember["key_responsibilities"] ?? ""); ?></textarea>
                  </div>
                  <!-- Bio -->
                  <div class="form-group">
                      <label class="form-label">Bio</label>
                      <textarea name="bio" class="form-input" placeholder="Enter bio" rows="3"><?php echo htmlspecialchars($editMember["bio"] ?? ""); ?></textarea>
                  </div>
                  <!-- Email -->
                  <div class="form-group">
                      <label class="form-label">Email</label>
                      <input type="email" name="email" class="form-input" placeholder="Enter email" value="<?php echo htmlspecialchars($editMember["email"] ?? ""); ?>">
                  </div>
                  <!-- Social Media Links -->
                  <div class="form-group">
                      <label class="form-label">LinkedIn</label>
                      <input type="text" name="linkedin" class="form-input" placeholder="Enter LinkedIn profile" value="<?php echo htmlspecialchars($editMember["linkedin"] ?? ""); ?>">
                  </div>
                  <div class="form-group">
                      <label class="form-label">Facebook</label>
                      <input type="text" name="facebook" class="form-input" placeholder="Enter Facebook profile" value="<?php echo htmlspecialchars($editMember["facebook"] ?? ""); ?>">
                  </div>
                  <div class="form-group">
                      <label class="form-label">Tiktok</label>
                      <input type="text" name="tiktok" class="form-input" placeholder="Enter Tiktok profile" value="<?php echo htmlspecialchars($editMember["tiktok"] ?? ""); ?>">
                  </div>
                  <div class="form-group">
                      <label class="form-label">Position *</label>
                     <select name="position" class="form-input" required>
                         <?php foreach ($positions as $pos): ?>
                             <option value="<?php echo $pos; ?>" <?php echo (isset($editMember["position"]) && $editMember["position"] == $pos) ? "selected" : ""; ?>><?php echo $pos; ?></option>
                         <?php endforeach; ?>
                     </select>
                 </div>
                <div class="form-group">
                    <label class="form-label">Department</label>
                    <select name="department_id" class="form-input">
                        <?php foreach ($departments as $d): ?>
                            <option value="<?php echo $d["id"]; ?>" <?php echo (isset($editMember["department_id"]) && $editMember["department_id"] == $d["id"]) ? "selected" : ""; ?>><?php echo htmlspecialchars($d["name"]); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Term Start Date</label>
                    <input type="date" name="term_start" class="form-input" value="<?php echo htmlspecialchars($editMember["term_start"] ?? date("Y-m-d")); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Term End Date</label>
                    <input type="date" name="term_end" class="form-input" value="<?php echo htmlspecialchars($editMember["term_end"] ?? ""); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Profile Image</label>
                    <input type="file" name="profile_image" class="form-input" accept="image/*">
                    <?php if (!empty($editMember["profile_image_path"])): ?>
                <img src="../<?php echo htmlspecialchars($editMember["profile_image_path"]); ?>" style="height:60px;object-fit:cover;border-radius:4px;margin-top:8px;">
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label class="form-label">Alt Text</label>
                    <input type="text" name="alt_text" class="form-input" placeholder="Image description for accessibility" value="<?php echo htmlspecialchars($editMember["alt_text"] ?? ""); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Display Order</label>
                    <input type="number" name="display_order" class="form-input" value="<?php echo htmlspecialchars($editMember["display_order"] ?? "0"); ?>" min="0">
                </div>
                <div class="form-group form-check">
                    <input type="checkbox" name="is_active" value="1" <?php echo (!isset($editMember["is_active"]) || $editMember["is_active"]) ? "checked" : ""; ?>>
                    <label>Active Member</label>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary"><?php echo $editMember ? "Update" : "Create"; ?> Member</button>
                </div>
            </form>
        </div>
    </div>

<script>
<?php if ($editMember): ?>
    document.addEventListener('DOMContentLoaded', function() { openModal(); });
<?php endif; ?>

    function openModal() {
        var modal = document.getElementById('memberModal');
        if (modal) { modal.style.display = 'flex'; }
    }
    function closeModal() {
        var modal = document.getElementById('memberModal');
        if (modal) { modal.style.display = 'none'; }
    }
</script>
<script src="../assets/js/sidebar.js"></script>
    <script src="../assets/js/loader-service.js"></script>
</body>
</html>
