<?php
session_start();
require_once "../config/database.php";
require_once "../config/functions.php";
require_once "../models/Scholarship.php";

if (!isLogged()) {
    header("Location: login.php");
    exit;
}

$currentRole = currentRole();
$currentUser = currentUser();

if ($currentRole !== 'PRO') {
    header("Location: index.php");
    exit;
}

$pageTitle = "Scholarship Editor";
$errors = [];
$success = null;

$scholarshipModel = new Scholarship(db());
$scholarshipTypes = $scholarshipModel->getAllTypes();

$action = $_GET["action"] ?? "create";
$id = $_GET["id"] ?? null;

$editScholarship = null;
if ($id) {
    $editScholarship = $scholarshipModel->getById((int)$id);
    if (!$editScholarship) {
        $_SESSION["errors"] = ["Scholarship not found"];
        header("Location: scholarships.php");
        exit;
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $status = $_POST["status"] ?? "active";
    $deadline = $_POST["deadline"] ?? null;
    $deadline = $deadline ? date("Y-m-d", strtotime($deadline)) : null;

    $data = [
        "title"         => trim($_POST["title"] ?? ""),
        "type"          => $_POST["type"] ?? "Other",
        "description"   => trim($_POST["description"] ?? ""),
        "amount"        => trim($_POST["amount"] ?? ""),
        "eligibility"   => trim($_POST["eligibility"] ?? ""),
        "deadline"      => $deadline,
        "external_link" => trim($_POST["external_link"] ?? ""),
        "status"        => $status,
    ];

    if (empty($data["title"])) {
        $errors[] = "Scholarship title is required";
    }

    if (empty($errors)) {
        if ($id) {
            $scholarshipModel->update($id, $data);
            $_SESSION["success"] = "Scholarship updated successfully";
            header("Location: scholarships.php");
            exit;
        } else {
            $scholarshipModel->create($data);
            $_SESSION["success"] = "Scholarship created successfully";
            header("Location: scholarships.php");
            exit;
        }
    }
}

$statuses = ['active', 'inactive', 'expired'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> | DHLTU SRC Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;0,700;1,300;1,400;1,600;1,700&family=Outfit:wght@200;300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="icon" type="image/png" href="../assets/images/logo.png">
    <style>
        .editor-container { max-width: 800px; margin: 0 auto; }
        .form-section { margin-bottom: 24px; }
        .form-section h3 { margin: 0 0 16px 0; color: var(--dashboard-text); font-size: 16px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        @media (max-width: 768px) { .form-row { grid-template-columns: 1fr; } }
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
                <h1 class="header-title">Scholarship Editor</h1>
                <div class="header-actions">
                    <a href="scholarships.php" class="header-btn"><i class="bi bi-arrow-left"></i> Back</a>
                    <a href="../logout.php" class="header-btn"><i class="bi bi-box-arrow-right"></i></a>
                </div>
            </header>
            <main class="content-body">
                <div class="editor-container">
                    <div class="dashboard-header-section">
                        <h2 class="dashboard-title"><?php echo $editScholarship ? 'Edit' : 'Create'; ?> Scholarship</h2>
                        <p class="dashboard-subtitle">Manage scholarship opportunities for students</p>
                    </div>

                    <?php if (!empty($errors)): ?>
                        <div style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.2);border-radius:8px;padding:16px;margin-bottom:20px;color:#ef4444;">
                            <?php foreach ($errors as $err): ?><div><?php echo htmlspecialchars($err); ?></div><?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div style="background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.2);border-radius:8px;padding:16px;margin-bottom:20px;color:#22c55e;"><?php echo $success; ?></div>
                    <?php endif; ?>

                    <form method="POST" class="table-container" style="padding:24px;">
                        <input type="hidden" name="id" value="<?php echo (int)($editScholarship['id'] ?? 0); ?>">

                        <div class="form-section">
                            <h3>Basic Information</h3>
                            <div class="form-group">
                                <label class="form-label">Scholarship Title *</label>
                                <input type="text" name="title" class="form-input" required value="<?php echo htmlspecialchars($editScholarship['title'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label class="form-label">Type</label>
                                <select name="type" class="form-input">
                                    <?php foreach ($scholarshipTypes as $type): ?>
                                        <option value="<?php echo $type; ?>" <?php echo ($editScholarship['type'] ?? '') === $type ? 'selected' : ''; ?>><?php echo $type; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-input" rows="3"><?php echo htmlspecialchars($editScholarship['description'] ?? ''); ?></textarea>
                            </div>
                        </div>

                        <div class="form-section">
                            <h3>Financial & Eligibility</h3>
                            <div class="form-group">
                                <label class="form-label">Amount</label>
                                <input type="text" name="amount" class="form-input" placeholder="e.g. GH₵ 2,000 or Full tuition" value="<?php echo htmlspecialchars($editScholarship['amount'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label class="form-label">Eligibility Criteria</label>
                                <textarea name="eligibility" class="form-input" rows="3" placeholder="Enter eligibility requirements..."><?php echo htmlspecialchars($editScholarship['eligibility'] ?? ''); ?></textarea>
                            </div>
                        </div>

<div class="form-section">
                             <h3>Schedule & Status</h3>
                             <div class="form-row">
                                 <div class="form-group">
                                     <label class="form-label">Deadline</label>
                                     <input type="date" name="deadline" class="form-input" value="<?php echo htmlspecialchars($editScholarship['deadline'] ?? ''); ?>">
                                 </div>
                                 <div class="form-group">
                                     <label class="form-label">Status</label>
                                     <select name="status" class="form-input">
                                         <?php foreach ($statuses as $status): ?>
                                             <option value="<?php echo $status; ?>" <?php echo ($editScholarship['status'] ?? '') === $status ? 'selected' : ''; ?>><?php echo ucfirst($status); ?></option>
                                         <?php endforeach; ?>
                                     </select>
                                 </div>
                             </div>
                         </div>

                         <div class="form-section">
                             <h3>External Link</h3>
                             <div class="form-group">
                                 <label class="form-label">External Application URL</label>
                                 <input type="url" name="external_link" class="form-input" placeholder="https://example.com/apply" value="<?php echo htmlspecialchars($editScholarship['external_link'] ?? ''); ?>">
                                 <small style="color:var(--text-muted);margin-top:4px;display:block;">Link to third-party application site (optional)</small>
                             </div>
                         </div>

                         <div style="display:flex;gap:12px;justify-content:flex-end;margin-top:24px;">
                            <a href="scholarships.php" class="btn btn-outline">Cancel</a>
                            <button type="submit" class="btn btn-primary"><?php echo $editScholarship ? 'Update' : 'Create'; ?> Scholarship</button>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>

    <script src="../assets/js/sidebar.js"></script>
</body>
</html>