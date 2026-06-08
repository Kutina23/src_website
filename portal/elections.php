<?php
// Portal — Elections Information Management
session_start();
require_once "../config/database.php";
require_once "../config/functions.php";
require_once "../models/Elections.php";

if (!isLogged()) {
    header("Location: login.php");
    exit;
}

$currentRole = currentRole();
$currentUser = currentUser();

if (!in_array($currentRole, ["PRO", "PRESIDENT", "DIRECTOR ICT", "DEAN"])) {
    header("Location: ../index.php");
    exit;
}

$pageTitle = "Elections Management";
$success  = $_SESSION["success"]  ?? null;
$errors   = $_SESSION["errors"]   ?? [];
unset($_SESSION["success"], $_SESSION["errors"]);

$electionsModel = new Elections(db());
$electionStatusOptions = $electionsModel->getStatusOptions();
$positionOptions       = $electionsModel->getPositionOptions();
$stats                 = $electionsModel->getStats();

// ── Filters ──
$filterStatus  = $_GET["status"]    ?? "";
$filterPost    = $_GET["position"]  ?? "";
$search        = trim($_GET["search"] ?? "");

$filters = [];
if ($filterStatus) $filters["status"]     = $filterStatus;
if ($filterPost)   $filters["position"]   = $filterPost;
if ($search)       $filters["search"]     = $search;

// Allow viewing inactive records only via explicit flag
$filters["include_inactive"] = isset($_GET["include_inactive"]);

$allRows = $electionsModel->getAll($filters);

// ── CRUD handlers ─────────────────────────────────────────────────────────
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    require_once "../config/validations.php";
    $action  = $_POST["action"]  ?? "";
    $csrf    = $_POST["csrf_token"] ?? "";

    if (!hash_equals($_SESSION["csrf_token"] ?? "", $csrf)) {
        $_SESSION["errors"] = ["Invalid CSRF token."];
        header("Location: elections.php?" . http_build_query($_GET));
        exit;
    }

    $title          = trim($_POST["title"]          ?? "");
    $description    = trim($_POST["description"]    ?? "");
    $position       = trim($_POST["position"]       ?? "");
    $election_date  = trim($_POST["election_date"]  ?? "");
    $start_time     = trim($_POST["start_time"]     ?? "");
    $end_time       = trim($_POST["end_time"]       ?? "");
    $location       = trim($_POST["location"]       ?? "");
    $status         = $_POST["status"]              ?? "UPCOMING";

    if ($action === "create" || $action === "edit") {
        if (empty($title)) {
            $_SESSION["errors"] = ["Election title is required."];
        } elseif (!in_array($status, $electionStatusOptions, true)) {
            $_SESSION["errors"] = ["Invalid status."];
        } else {
            $data = compact("title", "description", "position", "election_date", "start_time", "end_time", "location", "status");

            // Validate scheduling conflict for elections
            if ($location && $election_date && $start_time) {
                $conflictErrors = validateEventScheduling([
                    'event_location' => $location,
                    'event_date' => $election_date,
                    'event_start_time' => $start_time,
                    'exclude_event_id' => $action === "edit" ? (int)($_POST["id"] ?? 0) : null
                ]);

                if (!empty($conflictErrors)) {
                    $_SESSION["errors"] = $conflictErrors;
                }
            }

            if (empty($_SESSION["errors"])) {
                if ($action === "create") {
                    $electionsModel->create($data);
                    $_SESSION["success"] = "Election created successfully.";
                } else {
                    $id = (int)($_POST["id"] ?? 0);
                    if ($electionsModel->getById($id)) {
                        $electionsModel->update($id, $data);
                        $_SESSION["success"] = "Election updated successfully.";
                    } else {
                        $_SESSION["errors"] = ["Election not found."];
                    }
                }
                header("Location: elections.php?" . http_build_query($_GET));
                exit;
            } else {
                header("Location: elections.php?" . http_build_query($_GET));
                exit;
            }
        }
    } elseif ($action === "delete") {
        $id = (int)($_POST["id"] ?? 0);
        if ($electionsModel->getById($id)) {
            $electionsModel->softDelete($id);
            $_SESSION["success"] = "Election archived successfully.";
        }
        header("Location: elections.php?" . http_build_query($_GET));
        exit;
    } elseif ($action === "restore") {
        $id = (int)($_POST["id"] ?? 0);
        $electionsModel->update($id, ["is_active" => 1]);
        $_SESSION["success"] = "Election restored successfully.";
        header("Location: elections.php?" . http_build_query($_GET));
        exit;
    }
}

// ── Fetch for edit modal ──────────────────────────────────────────────────
$editElection = null;
if ($_GET["action"] ?? "" === "edit") {
    $id = (int)($_GET["id"] ?? 0);
    $editElection = $electionsModel->getById($id);
}

// ── CSRF token ──
$_SESSION["csrf_token"] = $_SESSION["csrf_token"] ?? bin2hex(random_bytes(32));

// ── Badge helpers ──
function badgeClass(string $status): string {
    return match ($status) {
        'UPCOMING'  => 'badge-upcoming',
        'ONGOING'   => 'badge-ongoing',
        'COMPLETED' => 'badge-completed',
        'CANCELLED' => 'badge-cancelled',
        default     => '',
    };
}
function badgeIcon(string $status): string {
    return match ($status) {
        'UPCOMING'  => 'calendar-event',
        'ONGOING'   => 'broadcast',
        'COMPLETED' => 'check2-circle',
        'CANCELLED' => 'x-circle',
        default     => 'circle',
    };
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
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;0,700;1,300;1,400&family=Outfit:wght@200;300;400;500;600;700&family=Space+Mono:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
  <link rel="stylesheet" href="../assets/css/sidebar.css">
  <link rel="stylesheet" href="../assets/css/dashboard.css">
  <style>
    .page-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:28px; flex-wrap:wrap; gap:16px; }
    .page-header h1 { color:#c9a84c; margin:0; font-size:2rem; }

    /* Stats row */
    .stats-row    { display:grid; grid-template-columns:repeat(auto-fill, minmax(140px,1fr)); gap:14px; margin-bottom:28px; }
    .stat-mini    { background:rgba(14,20,40,.9); border:1px solid rgba(201,168,76,.12); border-radius:10px; padding:18px 16px; text-align:center; }
    .stat-mini .val { font-family:'Space Mono',monospace; font-size:1.6rem; color:#c9a84c; font-weight:700; }
    .stat-mini .lbl { font-size:11px; color:#5a6a80; text-transform:uppercase; letter-spacing:.08em; margin-top:4px; font-family:'Space Mono',monospace; }

    /* Filter bar */
    .filter-bar { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:24px; background:rgba(14,20,40,.8); border:1px solid rgba(201,168,76,.08); border-radius:10px; padding:16px 20px; align-items:center; }
    .filter-bar input[type="text"], .filter-bar select { background:rgba(6,12,26,.8); color:#e0e8f0; border:1px solid rgba(160,180,208,.18); border-radius:6px; padding:8px 12px; font-family:'Outfit',sans-serif; font-size:14px; }
    .filter-bar select { min-width:150px; cursor:pointer; }
    .filter-bar input[type="text"]:focus, .filter-bar select:focus { outline:none; border-color:#c9a84c; }

    /* Card / table */
    .card { background:rgba(14,20,40,.85); border:1px solid rgba(201,168,76,.1); border-radius:12px; overflow:hidden; }
    .card table { width:100%; border-collapse:collapse; }
    .card table th { font-family:'Space Mono',monospace; font-size:10.5px; letter-spacing:.1em; text-transform:uppercase; color:#c9a84c; text-align:left; padding:14px 18px; border-bottom:1px solid rgba(201,168,76,.12); background:rgba(201,168,76,.04); }
    .card table td { padding:14px 18px; border-bottom:1px solid rgba(255,255,255,.04); vertical-align:middle; font-size:14px; color:#d0d8e4; }
    .card table tr:last-child td { border-bottom:none; }
    .card table tr:hover td { background:rgba(201,168,76,.03); }

    .cell-info .e-title { font-weight:600; color:#fff; font-size:15px; }
    .cell-info .e-meta  { font-size:12px; color:#5a6a80; margin-top:3px; }

    /* Badges */
    .badge { display:inline-flex; align-items:center; gap:5px; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:600; font-family:'Space Mono',monospace; letter-spacing:.04em; }
    .badge-upcoming  { background:rgba(74,144,226,.15); color:#6ab0ff; border:1px solid rgba(74,144,226,.3); }
    .badge-ongoing   { background:rgba(201,168,76,.15); color:#c9a84c; border:1px solid rgba(201,168,76,.3); }
    .badge-completed { background:rgba(55,180,120,.15); color:#3eb87c; border:1px solid rgba(55,180,120,.3); }
    .badge-cancelled { background:rgba(160,90,120,.15); color:#c06090; border:1px solid rgba(160,90,120,.3); }

    /* Buttons */
    .btn-primary { background:#c9a84c; color:#060c1a; padding:10px 22px; border:none; border-radius:6px; font-weight:600; cursor:pointer; font-family:'Outfit',sans-serif; display:inline-flex; align-items:center; gap:8px; font-size:14px; transition:background .2s; text-decoration:none; }
    .btn-primary:hover { background:#b8973a; }
    .btn-ghost  { background:transparent; color:#a0b4d0; padding:10px 22px; border:1px solid rgba(160,180,208,.25); border-radius:6px; font-weight:500; cursor:pointer; font-family:'Outfit',sans-serif; font-size:14px; }
    .btn-ghost:hover { border-color:#c9a84c; color:#c9a84c; }
    .btn-danger { background:#7b1a1a; color:#fff; padding:8px 16px; border:none; border-radius:6px; font-weight:500; cursor:pointer; font-family:'Outfit',sans-serif; font-size:13px; }
    .btn-danger:hover { background:#900e0e; }
    .btn-sm     { padding:5px 12px; font-size:12px; }
    .btn-link   { background:none; border:none; color:#c9a84c; cursor:pointer; font-family:'Outfit',sans-serif; font-size:14px; padding:4px; }
    .btn-link:hover { text-decoration:underline; }

    /* Modal */
    .modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,.65); display:none; align-items:center; justify-content:center; z-index:9999; backdrop-filter:blur(3px); }
    .modal-overlay.open { display:flex; }
    .modal-box { background:#0a1225; border:1px solid rgba(201,168,76,.22); border-radius:14px; width:90%; max-width:660px; max-height:90vh; overflow-y:auto; box-shadow:0 24px 80px rgba(0,0,0,.6); }
    .modal-header { display:flex; align-items:center; justify-content:space-between; padding:22px 28px; border-bottom:1px solid rgba(201,168,76,.12); }
    .modal-header h2 { color:#c9a84c; margin:0; font-size:1.25rem; }
    .modal-close  { background:none; border:none; font-size:22px; color:#5a6a80; cursor:pointer; }
    .modal-close:hover { color:#fff; }
    .modal-body   { padding:28px; }
    .form-group    { margin-bottom:18px; }
    .form-group label { display:block; font-size:12px; font-family:'Space Mono',monospace; color:#7a8fa0; letter-spacing:.08em; text-transform:uppercase; margin-bottom:6px; }
    .form-group input, .form-group textarea, .form-group select { width:100%; background:rgba(6,12,26,.9); color:#d0d8e4; border:1px solid rgba(160,180,208,.18); border-radius:8px; padding:11px 14px; font-family:'Outfit',sans-serif; font-size:14px; box-sizing:border-box; }
    .form-group input:focus, .form-group textarea:focus, .form-group select:focus { outline:none; border-color:#c9a84c; }
    .form-group textarea { min-height:80px; resize:vertical; }
    .form-row { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
    .modal-actions { display:flex; gap:10px; justify-content:flex-end; padding:0 28px 28px; }

    .empty-state { text-align:center; padding:60px 20px; color:#5a6a80; }
    .empty-state i { font-size:3rem; margin-bottom:16px; display:block; }
    .empty-state p { font-size:15px; }

    .alert { padding:14px 20px; border-radius:8px; margin-bottom:20px; font-size:14px; display:flex; align-items:center; gap:10px; }
    .alert-success { background:rgba(55,180,120,.12); border:1px solid rgba(55,180,120,.3); color:#3eb87c; }
    .alert-error   { background:rgba(220,80,60,.12);  border:1px solid rgba(220,80,60,.3);  color:#f07060; }

    /* Position pill */
    .pos-pill { background:rgba(74,144,226,.1); color:#6ab0ff; border:1px solid rgba(74,144,226,.2); padding:1px 8px; border-radius:20px; font-size:11px; font-weight:500; }

    @media (max-width: 768px) {
      .page-header { flex-direction:column; align-items:flex-start; }
      .page-header h1 { font-size:1.5rem; }
      .stats-row { grid-template-columns:repeat(2,1fr); }
      .filter-bar { flex-direction:column; }
      .filter-bar input, .filter-bar select { width:100%; box-sizing:border-box; min-width:auto; }
      .card table { min-width:760px; }
      .form-row { grid-template-columns:1fr; }
      .modal-box { width:95%; max-width:95%; }
    }
    @media (max-width: 480px) {
      .stats-row { grid-template-columns:1fr 1fr; }
    }
  </style>
  <link rel="icon" type="image/png" href="../assets/images/logo.png">
</head>
<body>
  <div class="dashboard-layout">
    <div class="mobile-overlay" id="mobileOverlay"></div>

    <aside class="sidebar" id="sidebar">
      <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
        <i class="bi bi-chevron-left"></i>
      </button>

      <div class="sidebar-header">
        <div class="sidebar-logo">SRC</div>
        <span class="sidebar-title">DHLTU Dashboard</span>
      </div>

      <?php
        require_once "../include/nav-links.php";
        $nav = new NavigationRBAC($currentRole);
        echo $nav->renderNavigation();
      ?>

      <div class="sidebar-user">
        <div class="user-avatar"><?php echo strtoupper(substr($currentUser["first_name"], 0, 1) . substr($currentUser["last_name"], 0, 1)); ?></div>
        <div class="user-info">
          <div class="user-name"><?php echo htmlspecialchars($currentUser["first_name"] . " " . $currentUser["last_name"]); ?></div>
          <div class="user-role">
            <span class="role-badge <?php echo $currentRole === "PRO" ? "admin" : "monitor"; ?>">
              <?php echo htmlspecialchars($currentRole); ?>
            </span>
          </div>
        </div>
      </div>
    </aside>

    <div class="main-content">
      <header class="dashboard-header">
        <button class="mobile-toggle" id="mobileToggle" aria-label="Open menu"><i class="bi bi-list"></i></button>
        <h1 class="header-title"><i class="bi bi-card-checklist" style="margin-right:10px; vertical-align:middle;"></i><?php echo $pageTitle; ?></h1>
        <div class="header-actions">
          <a href="../logout.php" class="header-btn" aria-label="Logout"><i class="bi bi-box-arrow-right"></i></a>
        </div>
      </header>

      <main class="content-body">
        <div class="dashboard-container">

          <!-- ── Alerts ── -->
          <?php if ($success): ?>
            <div class="alert alert-success"><i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($success); ?></div>
          <?php endif; ?>
          <?php if ($errors): ?>
            <div class="alert alert-error"><i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($errors[0]); ?></div>
          <?php endif; ?>

          <!-- ── Page header ── -->
          <div class="page-header">
            <div>
              <h1><i class="bi bi-check2-ballot" style="margin-right:12px; vertical-align:middle;"></i>Elections Management</h1>
              <p style="color:#5a6a80; font-size:14px; margin-top:6px;">
                Create, update, and manage all SRC election records — past, present, and upcoming.
              </p>
            </div>
            <button class="btn-primary" onclick="openCreateModal()"><i class="bi bi-plus-lg"></i> New Election</button>
          </div>

          <!-- ── Stats row ── -->
          <div class="stats-row">
            <div class="stat-mini">
              <div class="val"><?php echo (int)$stats["total"]; ?></div>
              <div class="lbl">Total Elections</div>
            </div>
            <div class="stat-mini">
              <div class="val" style="color:#6ab0ff;"><?php echo (int)$stats["upcoming"]; ?></div>
              <div class="lbl">Upcoming</div>
            </div>
            <div class="stat-mini">
              <div class="val" style="color:#c9a84c;"><?php echo (int)$stats["ongoing"]; ?></div>
              <div class="lbl">Ongoing</div>
            </div>
            <div class="stat-mini">
              <div class="val" style="color:#3eb87c;"><?php echo (int)$stats["completed"]; ?></div>
              <div class="lbl">Completed</div>
            </div>
            <div class="stat-mini">
              <div class="val" style="color:#c06090;"><?php echo (int)$stats["cancelled"]; ?></div>
              <div class="lbl">Cancelled</div>
            </div>
          </div>

          <!-- ── Filters ── -->
          <div class="filter-bar">
            <input type="text" name="search" id="searchInput"
                   placeholder="Search elections by title, position, location..."
                   value="<?php echo htmlspecialchars($search); ?>"
                   onkeydown="if(event.key==='Enter'){filterGo()}" />
            <select name="status" id="statusFilter" onchange="filterGo()">
              <option value="">All Statuses</option>
              <?php foreach ($electionStatusOptions as $s): ?>
                <option value="<?php echo $s; ?>" <?php echo $filterStatus === $s ? "selected" : ""; ?>>
                  <?php echo $s; ?>
                </option>
              <?php endforeach; ?>
            </select>
            <select name="position" id="posFilter" onchange="filterGo()">
              <option value="">All Positions</option>
              <?php foreach ($positionOptions as $p): ?>
                <option value="<?php echo htmlspecialchars($p); ?>" <?php echo $filterPost === $p ? "selected" : ""; ?>>
                  <?php echo htmlspecialchars($p); ?>
                </option>
              <?php endforeach; ?>
            </select>
            <label style="display:flex;align-items:center;gap:6px;font-size:12px;color:#5a6a80;cursor:pointer;margin-left:auto;">
              <input type="checkbox" id="includeInactive" <?php echo isset($_GET["include_inactive"]) ? "checked" : ""; ?> onchange="filterGo()" style="accent-color:#c9a84c;"> Include Inactive
            </label>
          </div>

          <!-- ── Election table ── -->
          <div class="card">
            <?php if (empty($allRows)): ?>
              <div class="empty-state">
                <i class="bi bi-card-checklist"></i>
                <p>No elections found matching your filters.</p>
              </div>
            <?php else: ?>
            <div style="overflow-x:auto;">
            <table>
              <thead>
                <tr>
                  <th style="width:48px;">#</th>
                  <th>Election</th>
                  <th style="text-align:center;">Position</th>
                  <th style="text-align:center;">Date &amp; Time</th>
                  <th style="text-align:center;">Location</th>
                  <th style="text-align:center;">Status</th>
                  <th style="text-align:center;">Actions</th>
                </tr>
              </thead>
              <tbody>
              <?php foreach ($allRows as $idx => $e): ?>
                <?php
                  $bc  = badgeClass($e["status"]);
                  $bic = badgeIcon($e["status"]);
                  $dt  = trim(($e["election_date"] ?? ""));
                  $st  = trim(($e["start_time"]   ?? ""));
                  $et  = trim(($e["end_time"]     ?? ""));
                  $dtDisplay = $dt
                    ? ($st ? date("M d, Y · g:i A", strtotime($dt . " " . $st)) . ($et ? " – " . date("g:i A", strtotime($dt . " " . $et)) : "")
                       : date("M d, Y", strtotime($dt)))
                    : "<span style='color:#5a6a80;'>Not set</span>";
                ?>
                <tr>
                  <td style="font-family:'Space Mono',monospace;font-size:11px;color:#5a6a80;"><?php echo $idx + 1; ?></td>
                  <td class="cell-info">
                    <div class="e-title"><?php echo htmlspecialchars($e["title"]); ?></div>
                    <?php if ($e["description"]): ?>
                      <div class="e-meta"><?php echo htmlspecialchars(truncate($e["description"], 80)); ?></div>
                    <?php endif; ?>
                    <div class="e-meta">ID #<?php echo $e["id"]; ?>
                      <?php if (!$e["is_active"]): ?>
                        <span style="color:#c06090;">· Archived</span>
                      <?php endif; ?>
                    </div>
                  </td>
                  <td style="text-align:center;">
                    <span class="pos-pill"><?php echo htmlspecialchars($e["position"]); ?></span>
                  </td>
                  <td style="text-align:center;font-size:12px;color:#a0b4d0;">
                    <?php echo $dtDisplay; ?>
                  </td>
                  <td style="text-align:center;font-size:12px;color:#5a6a80;">
                    <?php echo $e["location"] ? htmlspecialchars($e["location"]) : "—"; ?>
                  </td>
                  <td style="text-align:center;">
                    <span class="badge <?php echo $bc; ?>">
                      <i class="bi bi-<?php echo $bic; ?>"></i><?php echo $e["status"]; ?>
                    </span>
                  </td>
                  <td style="text-align:center; white-space:nowrap;">
                    <button class="btn-link" onclick="openEditModal(<?php echo $e['id']; ?>)" title="Edit">
                      <i class="bi bi-pencil"></i> Edit
                    </button>
                    <?php if ($e["is_active"]): ?>
                      <form method="POST" action="elections.php?<?php echo http_build_query($_GET); ?>"
                            style="display:inline;"
                            onsubmit="return confirm('Archive \'<?php echo addslashes($e['title']); ?>\'?');">
                        <input type="hidden" name="action"   value="delete">
                        <input type="hidden" name="id"       value="<?php echo $e['id']; ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <button type="submit" class="btn-danger btn-sm" style="margin-left:4px;" title="Archive">
                          <i class="bi bi-archive"></i>
                        </button>
                      </form>
                    <?php else: ?>
                      <form method="POST" action="elections.php?<?php echo http_build_query($_GET); ?>"
                            style="display:inline;">
                        <input type="hidden" name="action"     value="restore">
                        <input type="hidden" name="id"         value="<?php echo $e['id']; ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <button type="submit" class="btn-link"
                                title="Restore"
                                style="color:#3eb87c;margin-left:4px;">
                          <i class="bi bi-arrow-counterclockwise"></i> Restore
                        </button>
                      </form>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
            </div>
            <?php endif; ?>
          </div>

        </div><!-- /dashboard-container -->
      </main>
    </div><!-- /main-content -->
  </div><!-- /dashboard-layout -->

  <!-- ── CREATE / EDIT MODAL ──────────────────────────────────────────── -->
  <div class="modal-overlay" id="electionModal">
    <div class="modal-box">
      <div class="modal-header">
        <h2 id="modalTitle">New Election</h2>
        <button class="modal-close" onclick="closeModal()"><i class="bi bi-x-lg"></i></button>
      </div>
      <form method="POST" action="elections.php?<?php echo http_build_query($_GET); ?>" id="electionForm">
        <div class="modal-body">
          <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
          <input type="hidden" name="action"    id="formAction" value="create">
          <input type="hidden" name="id"        id="formId">

          <div class="form-row">
            <div class="form-group">
              <label for="title">Election Title <span style="color:#c0392b;">*</span></label>
              <input type="text" name="title" id="title" required
                     placeholder="e.g. SRC Executive Elections 2026">
            </div>
            <div class="form-group">
              <label for="position">Position</label>
              <select name="position" id="position">
                <option value="">Select position…</option>
                <?php foreach ($positionOptions as $p): ?>
                  <option value="<?php echo htmlspecialchars($p); ?>"><?php echo htmlspecialchars($p); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label for="description">Description</label>
            <textarea name="description" id="description" rows="3"
                      placeholder="Brief description of the election purpose, eligibility criteria, etc."></textarea>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="election_date">Election Date</label>
              <input type="date" name="election_date" id="election_date">
            </div>
            <div class="form-group">
              <label for="status">Status</label>
              <select name="status" id="status" required>
                <?php foreach ($electionStatusOptions as $s): ?>
                  <option value="<?php echo $s; ?>"><?php echo $s; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="start_time">Start Time</label>
              <input type="time" name="start_time" id="start_time">
            </div>
            <div class="form-group">
              <label for="end_time">End Time</label>
              <input type="time" name="end_time" id="end_time">
            </div>
          </div>

          <div class="form-group">
            <label for="location">Location / Venue</label>
            <input type="text" name="location" id="location" placeholder="e.g. Main Auditorium & Online Portal">
          </div>
        </div>
        <div class="modal-actions">
          <button type="button" class="btn-ghost" onclick="closeModal()">Cancel</button>
          <button type="submit" class="btn-primary" id="modalSubmitBtn">
            <i class="bi bi-check-lg"></i> &nbsp;Save Election
          </button>
        </div>
      </form>
    </div>
  </div>

<script src="../assets/js/sidebar.js"></script>
  <script src="../assets/js/loader-service.js"></script>
  <script>
    function openCreateModal() {
      document.getElementById('modalTitle').textContent = 'New Election';
      document.getElementById('formAction').value = 'create';
      document.getElementById('formId').value = '';
      document.getElementById('electionForm').reset();
      document.getElementById('electionModal').classList.add('open');
    }

    function openEditModal(id) {
      fetch('api/elections-get.php?id=' + id)
        .then(response => response.json())
        .then(data => {
          if (data) {
            document.getElementById('modalTitle').textContent = 'Edit Election';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('formId').value = data.id;
            document.getElementById('title').value = data.title || '';
            document.getElementById('description').value = data.description || '';
            document.getElementById('position').value = data.position || '';
            document.getElementById('election_date').value = data.election_date || '';
            document.getElementById('start_time').value = data.start_time || '';
            document.getElementById('end_time').value = data.end_time || '';
            document.getElementById('location').value = data.location || '';
            document.getElementById('status').value = data.status || 'UPCOMING';
            document.getElementById('electionModal').classList.add('open');
          }
        })
        .catch(error => console.error('Error loading election:', error));
    }

    function closeModal() {
      document.getElementById('electionModal').classList.remove('open');
    }

    function filterGo() {
      const params = new URLSearchParams({
        search: document.getElementById('searchInput').value,
        status: document.getElementById('statusFilter').value,
        position: document.getElementById('posFilter').value,
        include_inactive: document.getElementById('includeInactive').checked ? 1 : ''
      });
      window.location.search = params.toString();
    }

    document.addEventListener('DOMContentLoaded', function() {
      const modal = document.getElementById('electionModal');
      if (modal) {
        modal.addEventListener('click', function(e) {
          if (e.target === modal) closeModal();
        });
      }
    });
  </script>
</body>
</html>
