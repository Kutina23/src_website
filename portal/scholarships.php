<?php
session_start();
require_once '../config/database.php';
require_once '../config/functions.php';
require_once '../models/Scholarship.php';

if (!isLogged()) { header('Location: login.php'); exit; }

$currentRole = currentRole();
if ($currentRole !== 'PRO') { header('Location: dashboard.php'); exit; }

$pageTitle = 'Manage Scholarships';
$db = Database::getInstance();
$scholarshipModel = new Scholarship($db);

// Handle GET delete
$action = $_GET['action'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'delete' && isset($_GET['id'])) {
    $scholarshipModel->delete((int)$_GET['id']);
    $_SESSION['success'] = 'Scholarship deleted.';
    header('Location: scholarships.php');
    exit;
}

// Handle POST (create / edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $deadline = $_POST['deadline'] ?? null;
    $deadline = $deadline ? date("Y-m-d", strtotime($deadline)) : null;
    $data = [
        'title'         => trim($_POST['title'] ?? ''),
        'type'          => $_POST['type'] ?? 'Other',
        'description'   => trim($_POST['description'] ?? ''),
        'amount'        => trim($_POST['amount'] ?? ''),
        'eligibility'   => trim($_POST['eligibility'] ?? ''),
        'deadline'      => $deadline,
        'external_link' => trim($_POST['external_link'] ?? ''),
        'status'        => $_POST['status'] ?? 'active',
    ];

    if ($id > 0) {
        $scholarshipModel->update($id, $data);
        $_SESSION['success'] = 'Scholarship updated.';
    } else {
        $scholarshipModel->create($data);
        $_SESSION['success'] = 'Scholarship created.';
    }
    header('Location: scholarships.php');
    exit;
}

// Fetch all scholarships
$allScholarships = $scholarshipModel->getAll();

$errors  = $_SESSION['errors']  ?? [];
$success = $_SESSION['success'] ?? '';
unset($_SESSION['errors'], $_SESSION['success']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $pageTitle; ?> | DHLTU SRC Admin</title>
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
    <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar"><i class="bi bi-chevron-left"></i></button>
    <div class="sidebar-header">
      <div class="sidebar-logo">SRC</div>
      <span class="sidebar-title">DHLTU Dashboard</span>
    </div>
    <?php require_once '../include/nav-links.php'; $nav = new NavigationRBAC($currentRole); echo $nav->renderNavigation(); ?>
    <div class="sidebar-user">
      <div class="user-avatar"><?php echo strtoupper(substr(currentUser()['first_name'],0,1).substr(currentUser()['last_name'],0,1)); ?></div>
      <div class="user-info">
        <div class="user-name"><?php echo htmlspecialchars(currentUser()['first_name'].' '.currentUser()['last_name']); ?></div>
        <div class="user-role"><span class="role-badge admin"><?php echo $currentRole; ?></span></div>
      </div>
    </div>
  </aside>

  <div class="main-content">
    <header class="dashboard-header">
      <button class="mobile-toggle" id="mobileToggle" aria-label="Open menu"><i class="bi bi-list"></i></button>
      <h1 class="header-title">Scholarships Management</h1>
      <div class="header-actions">
        <a href="scholarships-update.php" class="header-btn" title="Add Scholarship" style="background:var(--dashboard-primary);color:#0a1628;"><i class="bi bi-plus"></i></a>
        <a href="../logout.php" class="header-btn" aria-label="Logout"><i class="bi bi-box-arrow-right"></i></a>
      </div>
    </header>

    <main class="content-body">
      <div class="dashboard-container">
        <div class="dashboard-header-section">
          <h2 class="dashboard-title">Manage Scholarship Opportunities</h2>
          <p class="dashboard-subtitle">Create and manage available scholarships for students</p>
        </div>

        <?php if (!empty($errors)): ?>
        <div style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.2);border-radius:8px;padding:16px;margin-bottom:20px;">
          <ul style="margin:0;padding-left:20px;color:#ef4444;">
            <?php foreach ((array)$errors as $err): ?><li><?php echo htmlspecialchars($err); ?></li><?php endforeach; ?>
          </ul>
        </div>
        <?php endif; ?>
        <?php if ($success): ?>
        <div style="background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.2);border-radius:8px;padding:16px;margin-bottom:20px;color:#22c55e;"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <div class="table-container">
          <table class="table">
<thead>
                <tr>
                  <th>#</th>
                  <th>Scholarship</th>
                  <th>Type</th>
                  <th>Amount</th>
                  <th>Deadline</th>
                  <th>External Link</th>
                  <th>Status</th>
                  <th style="text-align:right;">Actions</th>
                </tr>
              </thead>
<tbody>
              <?php foreach ($allScholarships as $i => $s): ?>
                <tr>
                  <td><?php echo $i + 1; ?></td>
                  <td>
                    <div style="font-weight:600;color:var(--dashboard-text);"><?php echo htmlspecialchars($s['title']); ?></div>
                  </td>
                  <td><span class="badge"><?php echo htmlspecialchars($s['type'] ?? 'Other'); ?></span></td>
                  <td><?php echo htmlspecialchars($s['amount'] ?? '—'); ?></td>
                  <td><?php echo $s['deadline'] ? date('M j, Y', strtotime($s['deadline'])) : '—'; ?></td>
                  <td>
                    <?php if (!empty($s['external_link'])): ?>
                      <a href="<?php echo htmlspecialchars($s['external_link']); ?>" target="_blank" rel="noopener" style="color:var(--gold);font-size:12px;"><?php echo htmlspecialchars($s['external_link']); ?></a>
                    <?php else: ?> — <?php endif; ?>
                  </td>
                  <td><span class="badge badge-<?php echo $s['status']=='active'?'active':($s['status']=='inactive'?'inactive':'expired'); ?>"><?php echo ucfirst($s['status']); ?></span></td>
                  <td>
                    <div style="display:flex;gap:8px;justify-content:flex-end;">
                      <a href="scholarships-update.php?id=<?php echo $s['id']; ?>" class="header-btn" title="Edit"><i class="bi bi-pencil"></i></a>
                      <a href="scholarships.php?action=delete&id=<?php echo $s['id']; ?>" class="btn btn-danger" title="Delete" onclick="return confirm('Delete this scholarship?')"><i class="bi bi-trash"></i></a>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
              <?php if (empty($allScholarships)): ?>
                <tr><td colspan="8" style="text-align:center;padding:40px;color:var(--dashboard-text-muted);">No scholarships yet. Click <strong>+</strong> to create the first one.</td></tr>
              <?php endif; ?>
              </tbody>
</table>
        </div>
      </div>
    </main>
  </div>
</div>

<script src="../assets/js/sidebar.js"></script>
</body>
</html>