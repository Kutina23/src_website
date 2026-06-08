<?php
// GA Sessions Management — Dynamic Admin
session_start();
require_once "../config/database.php";
require_once "../config/functions.php";
require_once "../models/GaSessions.php";

if (!isLogged()) {
    header("Location: login.php");
    exit;
}

$currentRole = currentRole();
$currentUser = currentUser();

if (!in_array($currentRole, ["PRO", "PRESIDENT", "DIRECTOR ICT", "DEAN"])) {
    header("Location: index.php");
    exit;
}

$pageTitle = "GA Sessions Management";
$success = $_SESSION["success"] ?? null;
$errors = $_SESSION["errors"] ?? [];
unset($_SESSION["success"], $_SESSION["errors"]);

$gaModel = new GaSessions(db());
$sessionTypes = $gaModel->getSessionTypes();
$statusOptions = $gaModel->getStatusOptions();
$stats = $gaModel->getStats();

$filterType   = $_GET["session_type"] ?? "";
$filterStatus = $_GET["status"] ?? "";
$search       = trim($_GET["search"] ?? "");

$filters = [];
if ($filterType)   $filters["session_type"] = $filterType;
if ($filterStatus) $filters["status"]     = $filterStatus;
if ($search)       $filters["search"]     = $search;

$sessions = $gaModel->getAll($filters);

// ── Handle Create / Update / Delete ─────────────────────────
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    require_once "../config/validations.php";
    $action = $_POST["action"] ?? "";
    $csrf   = $_POST["csrf_token"] ?? "";
    if (!hash_equals($_SESSION["csrf_token"] ?? "", $csrf)) {
        $_SESSION["errors"] = ["Invalid CSRF token."];
        header("Location: ga-sessions.php");
        exit;
    }

    if ($action === "create" || $action === "edit") {
        $data = [
            "session_type"       => $_POST["session_type"] ?? "ANNUAL",
            "title"              => trim($_POST["title"] ?? ""),
            "description"        => trim($_POST["description"] ?? ""),
            "scheduled_datetime" => trim($_POST["scheduled_datetime"] ?? ""),
            "location"           => trim($_POST["location"] ?? ""),
            "status"             => $_POST["status"] ?? "SCHEDULED",
            "minutes_url"        => trim($_POST["minutes_url"] ?? "")
        ];

if (empty($data["title"])) {
            $_SESSION["errors"] = ["Session title is required."];
        } elseif (!in_array($data["session_type"], $sessionTypes)) {
            $_SESSION["errors"] = ["Invalid session type."];
        } elseif (!in_array($data["status"], $statusOptions)) {
            $_SESSION["errors"] = ["Invalid status."];
        } else {
            // Validate scheduling conflict for GA sessions
            $location = $data["location"] ?? "";
            $datetime = $data["scheduled_datetime"] ?? "";

            if ($location && $datetime) {
                $eventDate = date('Y-m-d', strtotime($datetime));
                $startTime = date('H:i', strtotime($datetime));

                $conflictErrors = validateEventScheduling([
                    'event_location' => $location,
                    'event_date' => $eventDate,
                    'event_start_time' => $startTime,
                    'exclude_event_id' => $action === "edit" ? (int)($_POST["id"] ?? 0) : null
                ]);

                if (!empty($conflictErrors)) {
                    $_SESSION["errors"] = $conflictErrors;
                }
            }

            if (empty($_SESSION["errors"])) {
                if ($action === "create") {
                    $gaModel->create($data);
                    $_SESSION["success"] = "GA session created successfully.";
                    header("Location: ga-sessions.php");
                    exit;
                } else {
                    $id = (int)($_POST["id"] ?? 0);
                    if ($gaModel->getById($id)) {
                        $gaModel->update($id, $data);
                        $_SESSION["success"] = "GA session updated successfully.";
                        header("Location: ga-sessions.php");
                        exit;
                    }
                    $_SESSION["errors"] = ["Session not found."];
                }
            } else {
                header("Location: ga-sessions.php");
                exit;
            }
        }
    } elseif ($action === "delete") {
        $id = (int)($_POST["id"] ?? 0);
        if ($gaModel->getById($id)) {
            $gaModel->delete($id);
            $_SESSION["success"] = "GA session deleted successfully.";
        }
        header("Location: ga-sessions.php");
        exit;
    }
}

// ── Fetch for Edit Modal ────────────────────────────────────
$editSession = null;
if ($_GET["action"] ?? "" === "edit") {
    $id = (int)($_GET["id"] ?? 0);
    $editSession = $gaModel->getById($id);
}

$_SESSION["csrf_token"] = $_SESSION["csrf_token"] ?? bin2hex(random_bytes(32));

$gaTypeBadge = [
    "ANNUAL"    => "badge-annual",
    "EMERGENCY" => "badge-emergency",
    "SPECIAL"   => "badge-special",
];

$gaStatusBadge = [
    "SCHEDULED"   => "badge-scheduled",
    "IN_PROGRESS" => "badge-inprogress",
    "COMPLETED"   => "badge-completed",
    "CANCELLED"   => "badge-cancelled",
];
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
    <script>window.currentUserRole = '<?php echo $currentRole; ?>';</script>
<style>
        .page-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:28px; flex-wrap:wrap; gap:16px; }
        .page-header h1 { color:#c9a84c; margin:0; font-size:2rem; }
        
        /* Desktop responsive */
        @media (max-width: 768px) {
            .page-header h1 { font-size:1.5rem; }
            .page-header { flex-direction:column; align-items:flex-start; }
            .stats-row { grid-template-columns:repeat(2,1fr); }
            .filter-bar { flex-direction:column; align-items:stretch; }
            .filter-bar input, .filter-bar select { min-width:auto; width:100%; box-sizing:border-box; }
            .card { overflow-x:auto; -webkit-overflow-scrolling:touch; }
            .card table { min-width:800px; }
            .form-row { grid-template-columns:1fr; }
            .modal-box { width:95%; max-width:95%; }
            .dashboard-container { padding:16px; }
            .content-body { padding:16px; }
            .page-header .btn-primary { width:100%; justify-content:center; }
            .filter-bar span[style*="margin-left:auto"] { margin-left:0 !important; white-space:normal; }
        }
        
        @media (max-width: 480px) {
            .stats-row { grid-template-columns:1fr 1fr; }
            .page-header h1 { font-size:1.25rem; }
            .header-title { font-size:1.1rem; }
        }
        @media (max-width: 480px) {
            .stats-row { grid-template-columns:1fr 1fr; }
            .page-header .btn-primary { width:100%; }
            .filter-bar span[style*="margin-left:auto"] { margin-left:0; }
        }
        .btn-primary { background:#c9a84c; color:#060c1a; padding:10px 22px; border:none; border-radius:6px; font-weight:600; cursor:pointer; font-family:'Outfit',sans-serif; display:inline-flex; align-items:center; gap:8px; font-size:14px; transition:background .2s; }
        .btn-primary:hover { background:#b8973a; }
        .btn-ghost  { background:transparent; color:#a0b4d0; padding:10px 22px; border:1px solid rgba(160,180,208,.25); border-radius:6px; font-weight:500; cursor:pointer; font-family:'Outfit',sans-serif; display:inline-flex; align-items:center; gap:8px; font-size:14px; }
        .btn-ghost:hover { border-color:#c9a84c; color:#c9a84c; }
        .btn-danger { background:#7b1a1a; color:#fff; padding:8px 16px; border:none; border-radius:6px; font-weight:500; cursor:pointer; font-family:'Outfit',sans-serif; font-size:13px; }
        .btn-danger:hover { background:#900e0e; }
        .btn-sm { padding:5px 12px; font-size:12px; }
        .btn-link { background:none; border:none; color:#c9a84c; cursor:pointer; font-family:'Outfit',sans-serif; font-size:14px; padding:4px; }
        .btn-link:hover { text-decoration:underline; }

        /* Stats row */
        .stats-row { display:grid; grid-template-columns:repeat(auto-fill, minmax(140px,1fr)); gap:14px; margin-bottom:28px; }
        .stat-mini { background:rgba(14,20,40,.9); border:1px solid rgba(201,168,76,.12); border-radius:10px; padding:18px 16px; text-align:center; }
        .stat-mini .val { font-family:'Space Mono',monospace; font-size:1.6rem; color:#c9a84c; font-weight:700; }
        .stat-mini .lbl { font-size:11px; color:#5a6a80; text-transform:uppercase; letter-spacing:.08em; margin-top:4px; font-family:'Space Mono',monospace; }

        /* Filters bar */
        .filter-bar { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:24px; background:rgba(14,20,40,.8); border:1px solid rgba(201,168,76,.08); border-radius:10px; padding:16px 20px; }
        .filter-bar input[type="text"], .filter-bar select { background:rgba(6,12,26,.8); color:#e0e8f0; border:1px solid rgba(160,180,208,.18); border-radius:6px; padding:8px 12px; font-family:'Outfit',sans-serif; font-size:14px; min-width:200px; }
        .filter-bar select { min-width:150px; cursor:pointer; }
        .filter-bar input[type="text"]:focus, .filter-bar select:focus { outline:none; border-color:#c9a84c; }

        /* Table */
        .card { background:rgba(14,20,40,.85); border:1px solid rgba(201,168,76,.1); border-radius:12px; overflow:hidden; }
        .card table { width:100%; border-collapse:collapse; }
        .card table th { font-family:'Space Mono',monospace; font-size:10.5px; letter-spacing:.1em; text-transform:uppercase; color:#c9a84c; text-align:left; padding:14px 18px; border-bottom:1px solid rgba(201,168,76,.12); background:rgba(201,168,76,.04); }
        .card table td { padding:14px 18px; border-bottom:1px solid rgba(255,255,255,.04); vertical-align:middle; font-size:14px; color:#d0d8e4; }
        .card table tr:last-child td { border-bottom:none; }
        .card table tr:hover td { background:rgba(201,168,76,.03); }

        .cell-info .ga-title { font-weight:600; color:#fff; font-size:15px; }
        .cell-info .ga-datetime { font-size:12px; color:#7a8fa0; margin-top:3px; }
        .cell-info .ga-location { font-size:12px; color:#5a6a80; }
        .cell-info .ga-desc   { font-size:13px; color:#8a9ab0; margin-top:6px; max-width:320px; }

        /* Badges */
        .badge { display:inline-flex; align-items:center; gap:5px; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:600; font-family:'Space Mono',monospace; letter-spacing:.05em; }
        .badge-annual    { background:rgba(74,144,226,.15); color:#6ab0ff; border:1px solid rgba(74,144,226,.3); }
        .badge-emergency { background:rgba(220,80,60,.15); color:#f07060; border:1px solid rgba(220,80,60,.3); }
        .badge-special   { background:rgba(201,168,76,.15); color: #c9a84c; border:1px solid rgba(201,168,76,.3); }
        .badge-scheduled   { background:rgba(74,144,226,.15); color:#6ab0ff; border:1px solid rgba(74,144,226,.3); }
        .badge-inprogress  { background:rgba(201,168,76,.15); color:#c9a84c; border:1px solid rgba(201,168,76,.3); }
        .badge-completed   { background:rgba(55,180,120,.15); color:#3eb87c; border:1px solid rgba(55,180,120,.3); }
        .badge-cancelled   { background:rgba(160,90,120,.15); color:#c06090; border:1px solid rgba(160,90,120,.3); }

        /* Modal */
        .modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,.65); display:none; align-items:center; justify-content:center; z-index:9999; backdrop-filter:blur(3px); }
        .modal-overlay.open { display:flex; }
        .modal-box { background:#0a1225; border:1px solid rgba(201,168,76,.22); border-radius:14px; width:90%; max-width:640px; max-height:90vh; overflow-y:auto; box-shadow:0 24px 80px rgba(0,0,0,.6); }
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
        .empty-state i   { font-size:3rem; margin-bottom:16px; display:block; }
        .empty-state p   { font-size:15px; }

        /* Alert */
        .alert { padding:14px 20px; border-radius:8px; margin-bottom:20px; font-size:14px; display:flex; align-items:center; gap:10px; }
        .alert-success { background:rgba(55,180,120,.12); border:1px solid rgba(55,180,120,.3); color:#3eb87c; }
        .alert-error   { background:rgba(220,80,60,.12);  border:1px solid rgba(220,80,60,.3);  color:#f07060; }

        /* Count badge on nav */
        .count-pill  { background:rgba(201,168,76,.15); color:#c9a84c; padding:2px 8px; border-radius:20px; font-size:11px; font-family:'Space Mono',monospace; }
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
                            <?php echo $currentRole; ?>
                        </span>
                    </div>
                </div>
            </div>
        </aside>

        <div class="main-content">
            <header class="dashboard-header">
                <button class="mobile-toggle" id="mobileToggle" aria-label="Open menu">
                    <i class="bi bi-list"></i>
                </button>
                <h1 class="header-title"><?php echo $pageTitle; ?></h1>
                <div class="header-actions">
                    <a href="logout.php" class="header-btn" aria-label="Logout">
                        <i class="bi bi-box-arrow-right"></i>
                    </a>
                </div>
            </header>

            <main class="content-body">
                <div class="dashboard-container">

                    <!-- Alerts -->
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($errors): ?>
                        <div class="alert alert-error">
                            <i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($errors[0]); ?>
                        </div>
                    <?php endif; ?>

                    <!-- Page header -->
                    <div class="page-header">
                        <div>
                            <h1>
                                <i class="bi bi-people" style="margin-right:12px; vertical-align:middle;"></i>
                                GA Sessions Management
                            </h1>
                            <p style="color:#5a6a80; font-size:14px; margin-top:6px;">
                                Manage General Assembly sessions &mdash; Annual, Emergency &amp; Special &mdash;
                                and track their status dynamically.
                            </p>
                        </div>
                        <button class="btn-primary" onclick="openCreateModal()">
                            <i class="bi bi-plus-lg"></i> New Session
                        </button>
                    </div>

                    <!-- Stats row -->
                    <div class="stats-row">
                        <div class="stat-mini">
                            <div class="val"><?php echo (int)$stats["total"]; ?></div>
                            <div class="lbl">Total Sessions</div>
                        </div>
                        <div class="stat-mini">
                            <div class="val"><?php echo (int)$stats["scheduled"]; ?></div>
                            <div class="lbl">Scheduled</div>
                        </div>
                        <div class="stat-mini">
                            <div class="val"><?php echo (int)$stats["in_progress"]; ?></div>
                            <div class="lbl">In Progress</div>
                        </div>
                        <div class="stat-mini">
                            <div class="val"><?php echo (int)$stats["completed"]; ?></div>
                            <div class="lbl">Completed</div>
                        </div>
                        <div class="stat-mini">
                            <div class="val" style="color:#6ab0ff;"><?php echo (int)$stats["annual"]; ?></div>
                            <div class="lbl">Annual</div>
                        </div>
                        <div class="stat-mini">
                            <div class="val" style="color:#f07060;"><?php echo (int)$stats["emergency"]; ?></div>
                            <div class="lbl">Emergency</div>
                        </div>
                        <div class="stat-mini">
                            <div class="val" style="color:var(--gold,#c9a84c);"><?php echo (int)$stats["special"]; ?></div>
                            <div class="lbl">Special</div>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="filter-bar">
                        <?php $q = htmlspecialchars($search); ?>
                        <input type="text" name="search" placeholder="Search sessions..." value="<?php echo $q; ?>"
                                onchange="window.location='ga-sessions.php?session_type=<?php echo urlencode($filterType); ?>&amp;status=<?php echo urlencode($filterStatus); ?>&amp;search='+encodeURIComponent(this.value)" />
                        <select name="session_type" onchange="window.location='ga-sessions.php?session_type='+encodeURIComponent(this.value)+'&amp;status=<?php echo urlencode($filterStatus); ?>&amp;search=<?php echo urlencode($search); ?>'">
                            <option value="">All Types</option>
                            <?php foreach ($sessionTypes as $st): ?>
                                <option value="<?php echo $st; ?>" <?php echo $filterType === $st ? "selected" : ""; ?>><?php echo $st; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select name="status" onchange="window.location='ga-sessions.php?session_type=<?php echo urlencode($filterType); ?>&amp;status='+encodeURIComponent(this.value)+'&amp;search=<?php echo urlencode($search); ?>'">
                            <option value="">All Statuses</option>
                            <?php foreach ($statusOptions as $so): ?>
                                <option value="<?php echo $so; ?>" <?php echo $filterStatus === $so ? "selected" : ""; ?>><?php echo str_replace("_", " ", $so); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <span style="margin-left:auto; color:#5a6a80; font-size:13px; align-self:center;">
                            <i class="bi bi-list-ul"></i> <?php echo count($sessions); ?> session<?php echo count($sessions) !== 1 ? "s" : ""; ?> found
                        </span>
                    </div>

                    <!-- Sessions table -->
                    <div class="card">
                        <?php if (empty($sessions)): ?>
                            <div class="empty-state">
                                <i class="bi bi-calendar-x"></i>
                                <p>No GA sessions found. Create your first session to get started.</p>
                            </div>
                        <?php else: ?>
                        <div style="overflow-x:auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Session</th>
                                    <th style="text-align:center;">Type</th>
                                    <th style="text-align:center;">Status</th>
                                    <th style="text-align:center;">Scheduled</th>
                                    <th style="text-align:center;">Attendance</th>
                                    <th style="text-align:center;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($sessions as $s): ?>
                                <?php
                                    $typeBadge    = $gaTypeBadge[$s["session_type"]]     ?? "badge-special";
                                    $statusBadge  = $gaStatusBadge[$s["status"]]         ?? "";
                                    $attCount     = (int)($s["attendees_count"] ?? 0);
                                    $raw          = $s["scheduled_datetime"];
                                    $schedDisplay = $raw ? date("M d, Y \a\t h:i A", strtotime($raw)) : "Not set";
                                    $sessionNumber = $stats["total"] - array_search($s, $sessions);
                                ?>
                                <tr>
                                    <td class="cell-info">
                                        <div class="ga-title"><?php echo htmlspecialchars($s["title"]); ?></div>
                                        <?php if ($s["description"]): ?>
                                            <div class="ga-desc"><?php echo htmlspecialchars(truncate($s["description"], 90)); ?></div>
                                        <?php endif; ?>
                                        <div class="ga-datetime">ID #<?php echo $s["id"]; ?></div>
                                    </td>
                                    <td style="text-align:center;">
                                        <span class="badge <?php echo $typeBadge; ?>">
                                            <i class="bi bi-<?php echo $s["session_type"] === "ANNUAL" ? "calendar-event" : ($s["session_type"] === "EMERGENCY" ? "broadcast" : "lightning"); ?>"></i>
                                            <?php echo $s["session_type"]; ?>
                                        </span>
                                    </td>
                                    <td style="text-align:center;">
                                        <span class="badge <?php echo $statusBadge; ?>">
                                            <?php echo str_replace("_", " ", $s["status"]); ?>
                                        </span>
                                    </td>
                                    <td style="text-align:center;">
                                        <span style="font-size:13px; color:#a0b4d0;">
                                            <?php echo $schedDisplay; ?>
                                        </span>
                                        <?php if ($s["location"]): ?>
                                            <div style="font-size:12px; color:#5a6a80;">
                                                <i class="bi bi-geo-alt" style="margin-right:3px;"></i><?php echo htmlspecialchars($s["location"]); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align:center;">
                                        <div style="font-size:14px; font-weight:600; color:#c9a84c;">
                                            <i class="bi bi-people" style="margin-right:4px;"></i><?php echo $attCount; ?> present
                                        </div>
                                    </td>
                                    <td style="text-align:center; white-space:nowrap;">
                                        <a href="ga-attendance.php?session_id=<?php echo $s["id"]; ?>"
                                           class="btn-link" title="Manage Attendance"
                                           style="display:inline-block; margin-right:8px;">
                                            <i class="bi bi-check-square"></i> Attendance
                                        </a>
                                        <button class="btn-link" onclick="openEditModal(<?php echo $s["id"]; ?>)" title="Edit">
                                            <i class="bi bi-pencil"></i> Edit
                                        </button>
                                        <form method="POST" action="ga-sessions.php" style="display:inline;"
                                              onsubmit="return confirm('Delete session \'<?php echo addslashes($s['title']); ?>\'? Attendance records will also be removed.');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $s["id"]; ?>">
                                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION["csrf_token"]; ?>">
                                            <button type="submit" class="btn-danger btn-sm" style="margin-left:4px;">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        </div>
                        <?php endif; ?>
                    </div>

                </div>
            </main>
        </div>
    </div>

    <!-- ── CREATE / EDIT MODAL ─────────────────────────────── -->
    <div class="modal-overlay" id="sessionModal">
        <div class="modal-box">
            <div class="modal-header">
                <h2 id="modalTitle">New GA Session</h2>
                <button class="modal-close" onclick="closeModal()"><i class="bi bi-x-lg"></i></button>
            </div>
            <form method="POST" action="ga-sessions.php">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION["csrf_token"]; ?>">
                    <input type="hidden" name="action" id="formAction" value="create">
                    <input type="hidden" name="id"     id="formId">

                    <div class="form-row">
                        <div class="form-group">
                            <label for="session_type">Session Type</label>
                            <select name="session_type" id="session_type" required>
                                <?php foreach ($sessionTypes as $st): ?>
                                    <option value="<?php echo $st; ?>"><?php echo $st; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select name="status" id="status" required>
                                <?php foreach ($statusOptions as $so): ?>
                                    <option value="<?php echo $so; ?>"><?php echo str_replace("_", " ", $so); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="title">Session Title</label>
                        <input type="text" name="title" id="title" required placeholder="e.g. 24th Annual General Meeting">
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea name="description" id="description" rows="3" placeholder="Brief description of the session purpose, agenda, or resolutions..."></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="scheduled_datetime">Date &amp; Time</label>
                            <input type="datetime-local" name="scheduled_datetime" id="scheduled_datetime">
                        </div>
                        <div class="form-group">
                            <label for="location">Location</label>
                            <input type="text" name="location" id="location" placeholder="e.g. University Auditorium">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="minutes_url">Minutes URL</label>
                        <input type="url" name="minutes_url" id="minutes_url" placeholder="https://...">
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-ghost" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn-primary" id="modalSubmitBtn">
                        <i class="bi bi-<?php echo $editSession ? 'check' : 'plus'; ?>-lg"></i>
                        <?php echo $editSession ? "Update Session" : "Create Session"; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="../assets/js/sidebar.js"></script>
    <script src="../assets/js/loader-service.js"></script>

    <script>
        // ── Modal helpers ────────────────────────────────────
        function openCreateModal() {
            document.getElementById("sessionModal").classList.add("open");
            document.getElementById("formAction").value = "create";
            document.getElementById("formId").value = "";
            document.getElementById("modalTitle").textContent = "New GA Session";
            document.getElementById("modalSubmitBtn").innerHTML = '<i class="bi bi-plus-lg"></i> Create Session';
        }

        function openEditModal(id) {
            document.getElementById("sessionModal").classList.add("open");
            document.getElementById("formAction").value = "edit";
            document.getElementById("formId").value = id;
            let data = window._editData[id];
            if (data) {
                document.getElementById("session_type").value       = data.session_type        || "";
                document.getElementById("status").value             = data.status              || "SCHEDULED";
                document.getElementById("title").value              = data.title               || "";
                document.getElementById("description").value        = data.description         || "";
                document.getElementById("location").value           = data.location            || "";
                document.getElementById("minutes_url").value        = data.minutes_url         || "";
                if (data.scheduled_datetime) {
                    const dt = data.scheduled_datetime.replace(" ", "T").slice(0, 16);
                    document.getElementById("scheduled_datetime").value = dt;
                } else {
                    document.getElementById("scheduled_datetime").value = "";
                }
            }
            document.getElementById("modalTitle").textContent = "Edit GA Session";
            document.getElementById("modalSubmitBtn").innerHTML = '<i class="bi bi-check-lg"></i> Update Session';
        }

        function closeModal() {
            document.getElementById("sessionModal").classList.remove("open");
        }

        document.getElementById("sessionModal").addEventListener("click", function(e) {
            if (e.target === this) closeModal();
        });

        document.addEventListener("keydown", function(e) {
            if (e.key === "Escape") closeModal();
        });

        // ── Inject edit data ──────────────────────────────────
        window._editData = {
            <?php if ($editSession): ?>
            <?php echo $editSession["id"]; ?>: <?php echo json_encode($editSession, JSON_HEX_APOS | JSON_HEX_QUOT); ?>
            <?php endif; ?>
        };
    </script>
</body>
</html>
