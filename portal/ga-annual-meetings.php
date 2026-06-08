<?php
// Annual GA Meetings Admin — filtered view of ga_sessions where session_type=ANNUAL
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

$pageTitle = "Annual GA Meetings";
$success  = $_SESSION["success"] ?? null;
$errors   = $_SESSION["errors"] ?? [];
unset($_SESSION["success"], $_SESSION["errors"]);

$gaModel     = new GaSessions(db());
$statusOpts  = $gaModel->getStatusOptions();
$stats       = $gaModel->getStats();
$allUsers    = $gaModel->getAllUsersForAttendance();

$sessions   = $gaModel->getByType("ANNUAL");
$filterSt   = $_GET["status"] ?? "";
$search     = trim($_GET ["search"] ?? "");
$filters    = [];
if ($filterSt)                   $filters["status"] =  $filterSt;
if ($search)                     $filters["search"] = $search;
$yearList   = array_unique(array_map(fn($s) => $s["scheduled_datetime"] ? date("Y", strtotime($s["scheduled_datetime"])) : null,  array_filter($sessions, fn($r) => $r["scheduled_datetime"] ?? false)));

$editSession = null;
if ($_GET["action"] ?? "" === "edit") {
    $editSession = $gaModel->getById((int)($_GET["id"] ?? 0));
}

$_SESSION["csrf_token"] = $_SESSION["csrf_token"] ?? bin2hex(random_bytes(32));

$typeBadge = fn(string $type): array => match($type) {
    "ANNUAL"    => ["label"=>"ANNUAL",   "class"=>"badge-annual"],
    "EMERGENCY" => ["label"=>"EMERGENCY","class"=>"badge-emergency"],
    "SPECIAL"   => ["label"=>"SPECIAL",  "class"=>"badge-special"],
    default     => ["label"=>strtoupper($type), "class"=>"badge-special"],
};
$stsBadge = fn(string $s): array => match($s) {
    "SCHEDULED"   => ["label"=>"SCHEDULED",  "class"=>"badge-scheduled"],
    "IN_PROGRESS" => ["label"=>"IN PROGRESS", "class"=>"badge-inprogress"],
    "COMPLETED"   => ["label"=>"COMPLETED",   "class"=>"badge-completed"],
    "CANCELLED"   => ["label"=>"CANCELLED",   "class"=>"badge-cancelled"],
    default       => ["label"=>strtoupper($s), "class"=>"badge-scheduled"],
};

// Upload folder for agenda PDFs
$agendaUploadDir = __DIR__ . "/../uploads/agendas/";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";
    $csrf   = $_POST["csrf_token"] ?? "";
    if (!hash_equals($_SESSION["csrf_token"] ?? "", $csrf)) {
        $_SESSION["errors"] = ["Invalid CSRF token."];
        header("Location: ga-annual-meetings.php");
        exit;
    }

    if ($action === "create" || $action === "edit") {
        $minutesUrl = trim($_POST["minutes_url"] ?? "");

        if (!empty($_FILES["agenda_file"]["name"]) && $_FILES["agenda_file"]["error"] === UPLOAD_ERR_OK) {
            if (!is_dir($agendaUploadDir)) mkdir($agendaUploadDir, 0755, true);
            $ext  = strtolower(pathinfo($_FILES["agenda_file"]["name"], PATHINFO_EXTENSION));
            if ($ext !== "pdf") {
                $_SESSION["errors"] = ["Only PDF files are allowed for agenda uploads."];
                header("Location: ga-annual-meetings.php");
                exit;
            }
            $fname = "agenda_" . time() . "_" . uniqid() . ".pdf";
            $dest  = $agendaUploadDir . $fname;
            if (!move_uploaded_file($_FILES["agenda_file"]["tmp_name"], $dest)) {
                $_SESSION["errors"] = ["File upload failed."];
                header("Location: ga-annual-meetings.php");
                exit;
            }
            $minutesUrl = "../uploads/agendas/$fname";
        }

        $data = [
            "session_type"       => "ANNUAL",
            "title"              => trim($_POST["title"] ?? ""),
            "description"        => trim($_POST["description"] ?? ""),
            "scheduled_datetime" => trim($_POST["scheduled_datetime"] ?? ""),
            "location"           => trim($_POST["location"] ?? ""),
            "status"             => $_POST["status"] ?? "SCHEDULED",
            "minutes_url"        => $minutesUrl,
        ];
        if (empty($data["title"])) {
            $_SESSION["errors"] = ["Session title is required."];
            header("Location: ga-annual-meetings.php");
            exit;
        }
        if ($action === "create") {
            $gaModel->create($data);
            $_SESSION["success"] = "Annual GA created.";
        } else {
            $gaModel->update((int)$_POST["id"], $data);
            $_SESSION["success"] = "Annual GA updated.";
        }
        header("Location: ga-annual-meetings.php");
        exit;
    }
    if ($action === "delete") {
        $gaModel->delete((int)$_POST["id"]);
        $_SESSION["success"] = "Annual GA deleted.";
        header("Location: ga-annual-meetings.php");
        exit;
    }
}

$yearCounts = [];
foreach ($sessions as $s) {
    $yr = $s["scheduled_datetime"] ? date("Y", strtotime($s["scheduled_datetime"])) : "TBD";
    $yearCounts[$yr] = ($yearCounts[$yr] ?? 0) + 1;
}
ksort($yearCounts);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> | DHLTU SRC</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;0,700;1,300;1,400&family=Outfit:wght@200;300;400;500;600;700&family=Space+Mono:ital@0,400;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <script>window.currentUserRole = '<?php echo $currentRole; ?>';</script>
    <style>
        /* Shared styles from ga-sessions.php */
        .page-header { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:28px; flex-wrap:wrap; gap:16px; }
        .page-header h1 { color:#c9a84c; margin:0; font-size:2rem; }
        .btn-primary { background:#c9a84c; color:#060c1a; padding:10px 22px; border:none; border-radius:6px; font-weight:600; cursor:pointer; font-family:'Outfit',sans-serif; display:inline-flex; align-items:center; gap:8px; font-size:14px; }
        .btn-primary:hover { background:#b8973a; }
        .btn-ghost  { background:transparent; color:#a0b4d0; padding:8px 16px; border:1px solid rgba(160,180,208,.25); border-radius:6px; font-weight:500; cursor:pointer; font-family:'Outfit',sans-serif; font-size:13px; }
        .btn-ghost:hover { border-color:#c9a84c; color:#c9a84c; }
        .btn-danger { background:#7b1a1a; color:#fff; padding:8px 16px; border:none; border-radius:6px; font-weight:500; cursor:pointer; font-family:'Outfit',sans-serif; font-size:12px; }
        .btn-danger:hover { background:#900e0e; }
        .btn-link  { background:none; border:none; color:#c9a84c; cursor:pointer; font-family:'Outfit',sans-serif; font-size:14px; }
        .btn-link:hover { text-decoration:underline; }

        .filter-bar { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:24px; background:rgba(14,20,40,.8); border:1px solid rgba(201,168,76,.08); border-radius:10px; padding:16px 20px; align-items:center; }
        .filter-bar input, .filter-bar select { background:rgba(6,12,26,.8); color:#e0e8f0; border:1px solid rgba(160,180,208,.18); border-radius:6px; padding:8px 12px; font-family:'Outfit',sans-serif; font-size:13px; }
        .filter-bar input:focus, .filter-bar select:focus { outline:none; border-color:#c9a84c; }

        /* ─── Timeline view ─── */
        .timeline { position:relative; padding-left:36px; }
        .timeline::before { content:''; position:absolute; left:12px; top:0; bottom:0; width:2px; background:rgba(201,168,76,.15); }
        .tl-item  { position:relative; margin-bottom:36px; }
        .tl-dot   { position:absolute; left:-28px; top:8px; width:14px; height:14px; border-radius:50%; background:#c9a84c; border:2px solid #0a1225; z-index:1; }
        .tl-dot.past { background:#3eb87c; }
        .tl-dot.cancelled { background:#c06090; }
        .tl-card  { background:rgba(14,20,40,.85); border:1px solid rgba(201,168,76,.12); border-radius:12px; padding:22px 26px; }
        .tl-card-head { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:12px; flex-wrap:wrap; gap:10px; }
        .tl-year { font-family:'Space Mono',monospace; font-size:10px; letter-spacing:.15em; text-transform:uppercase; color:#c9a84c; margin-bottom:4px; }
        .tl-title { font-size:1.15rem; font-weight:700; color:#fff; }
        .tl-meta  { display:flex; gap:14px; flex-wrap:wrap; margin-top:10px; }
        .tl-chip  { display:flex; align-items:center; gap:5px; font-size:12px; color:#7a8fa0; }
        .tl-body  { font-size:13px; color:#8a9ab0; margin-top:8px; line-height:1.6; }
        .tl-actions { display:flex; gap:8px; flex-wrap:wrap; margin-top:16px; }

        .badge { display:inline-flex; align-items:center; gap:5px; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:600; font-family:'Space Mono',monospace; letter-spacing:.05em; }
        .badge-annual    { background:rgba(74,144,226,.15); color:#6ab0ff; border:1px solid rgba(74,144,226,.3); }
        .badge-emergency { background:rgba(220,80,60,.15); color:#f07060; border:1px solid rgba(220,80,60,.3); }
        .badge-special   { background:rgba(201,168,76,.15); color:#c9a84c; border:1px solid rgba(201,168,76,.3); }
        .badge-scheduled   { background:rgba(74,144,226,.15); color:#6ab0ff; border:1px solid rgba(74,144,226,.3); }
        .badge-inprogress  { background:rgba(201,168,76,.15); color:#c9a84c;  border:1px solid rgba(201,168,76,.3); }
        .badge-completed   { background:rgba(55,180,120,.15); color:#3eb87c; border:1px solid rgba(55,180,120,.3); }
        .badge-cancelled   { background:rgba(160,90,120,.15); color:#c06090; border:1px solid rgba(160,90,120,.3); }

        /* Modal — same as ga-sessions.php */
        .modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,.65); display:none; align-items:center; justify-content:center; z-index:9999; backdrop-filter:blur(3px); }
        .modal-overlay.open { display:flex; }
        .modal-box { background:#0a1225; border:1px solid rgba(201,168,76,.22); border-radius:14px; width:90%; max-width:640px; max-height:90vh; overflow-y:auto; }
        .modal-header { display:flex; align-items:center; justify-content:space-between; padding:22px 28px; border-bottom:1px solid rgba(201,168,76,.12); }
        .modal-header h2 { color:#c9a84c; margin:0; font-size:1.25rem; }
        .modal-close  { background:none; border:none; font-size:22px; color:#5a6a80; cursor:pointer; }
        .modal-close:hover { color:#fff; }
        .form-group    { margin-bottom:18px; }
        .form-group label { display:block; font-size:12px; font-family:'Space Mono',monospace; color:#7a8fa0; letter-spacing:.08em; text-transform:uppercase; margin-bottom:6px; }
        .form-group input, .form-group textarea, .form-group select { width:100%; background:rgba(6,12,26,.9); color:#d0d8e4; border:1px solid rgba(160,180,208,.18); border-radius:8px; padding:11px 14px; font-family:'Outfit',sans-serif; font-size:14px; box-sizing:border-box; }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus { outline:none; border-color:#c9a84c; }
        .form-group textarea { min-height:80px; resize:vertical; }
        .form-row { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
        .modal-actions { display:flex; gap:10px; justify-content:flex-end; padding:0 28px 28px; }
        .modal-body   { padding:28px; }

        .empty-state { text-align:center; padding:60px 20px; color:#5a6a80; }
        .empty-state i { font-size:3rem; margin-bottom:16px; display:block; }

        .alert { padding:14px 20px; border-radius:8px; margin-bottom:20px; font-size:14px; display:flex; align-items:center; gap:10px; }
        .alert-success { background:rgba(55,180,120,.12); border:1px solid rgba(55,180,120,.3); color:#3eb87c; }
        .alert-error   { background:rgba(220,80,60,.12);  border:1px solid rgba(220,80,60,.3);  color:#f07060; }

        .kpi-row { display:grid; grid-template-columns:repeat(auto-fill,minmax(130px,1fr)); gap:14px; margin-bottom:28px; }
        .kpi-card { background:rgba(14,20,40,.9); border:1px solid rgba(201,168,76,.12); border-radius:10px; padding:18px 16px; text-align:center; }
        .kpi-card .val { font-family:'Space Mono',monospace; font-size:1.8rem; font-weight:700; color:#c9a84c; }
        .kpi-card .lbl { font-size:10px; color:#5a6a80; text-transform:uppercase; letter-spacing:.08em; margin-top:4px; font-family:'Space Mono',monospace; }

        .file-upload { border:2px dashed rgba(160,180,208,.25); border-radius:10px; padding:20px; text-align:center; cursor:pointer; transition:border-color .2s; position:relative; }
        .file-upload:hover { border-color:#c9a84c; }
        .file-upload input[type="file"] { position:absolute; inset:0; opacity:0; cursor:pointer; }
        .file-upload i { font-size:1.8rem; color:#5a6a80; }
        .file-upload p { margin:6px 0 0; font-size:13px; color:#8a9ab0; font-family:'Outfit',sans-serif; }
        .file-upload .fname { font-size:12px; color:#3eb87c; margin-top:6px; word-break:break-all; font-family:'Space Mono',monospace; }
        .file-upload.has-file { border-color:#3eb87c; background:rgba(55,180,120,.04); }
    </style>
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
            <?php require_once "../include/nav-links.php"; $nav=new NavigationRBAC($currentRole); echo $nav->renderNavigation(); ?>
            <div class="sidebar-user">
                <div class="user-avatar"><?php echo strtoupper(substr($currentUser["first_name"],0,1).substr($currentUser["last_name"],0,1)); ?></div>
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($currentUser["first_name"]." ".$currentUser["last_name"]); ?></div>
                    <div class="user-role">
                        <span class="role-badge <?php echo $currentRole==='PRO'?'admin':'monitor'; ?>"><?php echo $currentRole; ?></span>
                    </div>
                </div>
            </div>
        </aside>

        <div class="main-content">
            <header class="dashboard-header">
                <button class="mobile-toggle" id="mobileToggle"><i class="bi bi-list"></i></button>
                <h1 class="header-title"><?php echo $pageTitle; ?></h1>
                <div class="header-actions">
                    <a href="logout.php" class="header-btn"><i class="bi bi-box-arrow-right"></i></a>
                </div>
            </header>
            <main class="content-body">
            <div class="dashboard-container">

                <!-- Alerts -->
                <?php if ($success): ?><div class="alert alert-success"><i class="bi bi-check-circle"></i><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
                <?php if ($errors):   ?><div class="alert alert-error">  <i class="bi bi-exclamation-triangle"></i><?php echo htmlspecialchars($errors[0]); ?></div><?php endif; ?>

                <!-- Page header -->
                <div class="page-header">
                    <div>
                        <h1><i class="bi bi-calendar-check" style="margin-right:12px;vertical-align:middle;"></i>Annual GA Meetings</h1>
                        <p style="color:#5a6a80;font-size:14px;margin-top:6px;">
                            Manage all Annual General Meeting sessions — schedule, edit statuses, and track attendance.
                        </p>
                    </div>
                    <button class="btn-primary" onclick="openCreateModal()"><i class="bi bi-plus-lg"></i>New AGM</button>
                </div>

                <!-- KPI row -->
                <div class="kpi-row">
                    <div class="kpi-card"><div class="val"><?php echo count($sessions); ?></div><div class="lbl">Total AGMs</div></div>
                    <div class="kpi-card"><div class="val" style="color:#6ab0ff;"><?php echo (int)array_sum(array_column(array_filter($sessions, fn($s)=>$s['status']==='SCHEDULED'), 'status')); ?></div><div class="lbl">Scheduled</div></div>
                    <div class="kpi-card"><div class="val" style="color:#c9a84c;"><?php echo (int)array_sum(array_column(array_filter($sessions, fn($s)=>$s['status']==='IN_PROGRESS'), 'status')); ?></div><div class="lbl">In Progress</div></div>
                    <div class="kpi-card"><div class="val" style="color:#3eb87c;"><?php echo (int)array_sum(array_column(array_filter($sessions, fn($s)=>$s['status']==='COMPLETED'), 'status')); ?></div><div class="lbl">Completed</div></div>
                    <div class="kpi-card"><div class="val"><?php echo count($yearCounts); ?></div><div class="lbl">Years</div></div>
                </div>

                <!-- Filters -->
                <div class="filter-bar">
                    <span style="font-size:12px;color:#5a6a80;font-family:'Space Mono',monospace;text-transform:uppercase;letter-spacing:.1em;">Session Type:</span>
                    <span class="badge badge-annual"><i class="bi bi-collection-play"></i>ANNUAL (locked)</span>
                    <input type="text" name="search" placeholder="Search AGM…" value="<?php echo htmlspecialchars($search); ?>"
                           onchange="window.location='?search='+encodeURIComponent(this.value)+'&status=<?php echo urlencode($filterSt); ?>'"/>
                    <select name="status" onchange="window.location='?status='+encodeURIComponent(this.value)+'&search=<?php echo urlencode($search); ?>'">
                        <option value="">All Statuses</option>
                        <?php foreach ($statusOpts as $so): ?>
                            <option value="<?php echo $so; ?>" <?php echo $filterSt===$so?'selected':''; ?>><?php echo str_replace('_',' ',$so); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <span style="margin-left:auto;font-size:12px;color:#5a6a80;">
                        <i class="bi bi-calendar2-event"></i> <?php echo count($sessions); ?> session<?php echo count($sessions)!==1?'s':''; ?>
                    </span>
                </div>

                <!-- ── Timeline ── -->
                <div class="card"><div style="padding:20px 24px;">
                <?php if (empty($sessions)): ?>
                    <div class="empty-state"><i class="bi bi-calendar-x"></i><p>No AGM sessions found. Create your first annual meeting below.</p></div>
                <?php else: ?>
                <div class="timeline">
                <?php foreach ($sessions as $s): ?>
                    <?php
                        $yr        = $s["scheduled_datetime"] ? date("Y", strtotime($s["scheduled_datetime"])) : "—";
                        $sched     = $s["scheduled_datetime"] ? date("M d, Y \\a\\t H:i", strtotime($s["scheduled_datetime"])) : "Unscheduled";
                        $attCount  = (int)($s["attendees_count"] ?? 0);
                        $tb        = $typeBadge($s["session_type"]);
                        $sb        = $stsBadge($s["status"]);
                        $dotClass  = match($s["status"]) { 'COMPLETED'=>'past','CANCELLED'=>'cancelled',default=>'' };
                    ?>
                    <div class="tl-item">
                        <div class="tl-dot <?php echo $dotClass; ?>"></div>
                        <div class="tl-card">
                            <div class="tl-card-head">
                                <div>
                                    <div class="tl-year"><?php echo $yr; ?> AGM</div>
                                    <div class="tl-title"><?php echo htmlspecialchars($s["title"]); ?></div>
                                </div>
                                <div style="display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end;">
                                    <span class="badge <?php echo $tb["class"]; ?>"><i class="bi bi-collection-play"></i><?php echo $tb["label"]; ?></span>
                                    <span class="badge <?php echo $sb["class"]; ?>"><?php echo str_replace("_"," ",$s["status"]); ?></span>
                                </div>
                            </div>
                            <div class="tl-meta">
                                <span class="tl-chip"><i class="bi bi-clock"></i><?php echo $sched; ?></span>
                                <?php if ($s["location"]): ?>
                                    <span class="tl-chip"><i class="bi bi-geo-alt"></i><?php echo htmlspecialchars($s["location"]); ?></span>
                                <?php endif; ?>
                                <span class="tl-chip"><i class="bi bi-people"></i><?php echo $attCount; ?> attendee<?php echo $attCount!==1?'s':''; ?></span>
                                <?php if ($s["scheduled_datetime"]): ?>
                                    <span class="tl-chip">
                                        <?php echo date("d M Y", strtotime($s["scheduled_datetime"])); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <?php if ($s["description"]): ?>
                                <p class="tl-body"><?php echo htmlspecialchars(truncate($s["description"], 200)); ?></p>
                            <?php endif; ?>
                            <div class="tl-actions">
                                <?php if(!empty($s["minutes_url"])): ?>
                                <a href="../<?php echo htmlspecialchars($s["minutes_url"]); ?>" class="btn-link" title="Agenda PDF">
                                    <i class="bi bi-file-earmark-pdf"></i> Agenda
                                </a>
                                <?php endif; ?>
                                <a href="ga-attendance.php?session_id=<?php echo $s["id"]; ?>" class="btn-link">
                                    <i class="bi bi-people"></i> Attendance
                                </a>
                                <a href="ga-minutes.php?session_id=<?php echo $s["id"]; ?>" class="btn-link">
                                    <i class="bi bi-file-earmark-pdf"></i> Minutes
                                </a>
                                <a href="ga-resolutions.php?session_id=<?php echo $s["id"]; ?>" class="btn-link">
                                    <i class="bi bi-file-earmark-text"></i> Resolutions
                                </a>
                                <button class="btn-link" onclick="openEditModal(<?php echo $s["id"]; ?>)"><i class="bi bi-pencil"></i> Edit</button>
                                <form method="POST" action="" style="display:inline"
                                      onsubmit="return confirm('Delete \'<?php echo addslashes($s['title']); ?>\'? All minutes, attendance, and resolutions records will be removed.');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $s["id"]; ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION["csrf_token"]; ?>">
                                    <button type="submit" class="btn-danger btn-sm"><i class="bi bi-trash"></i></button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>
                <?php endif; ?>
                </div></div>

            </div>
            </main>
        </div>
    </div>

    <!-- ── CREATE / EDIT MODAL ──────────────────────────────── -->
    <div class="modal-overlay" id="sessionModal">
        <div class="modal-box">
            <div class="modal-header">
                <h2 id="modalTitle">New Annual GA Meeting</h2>
                <button class="modal-close" onclick="closeModal()"><i class="bi bi-x-lg"></i></button>
            </div>
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION["csrf_token"]; ?>">
                    <input type="hidden" name="action" id="formAction" value="create">
                    <input type="hidden" name="id"     id="formId">

                    <div class="form-row">
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" id="status" required>
                                <?php foreach (["SCHEDULED","IN_PROGRESS","COMPLETED","CANCELLED"] as $so): ?>
                                    <option value="<?php echo $so; ?>"><?php echo str_replace("_"," ",$so); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Date &amp; Time</label>
                            <input type="datetime-local" name="scheduled_datetime" id="scheduled_datetime">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Session Title</label>
                        <input type="text" name="title" id="title" required placeholder="e.g. 24th Annual General Meeting">
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" id="description" rows="3" placeholder="Brief description, agenda highlights…"></textarea>
                    </div>

                    <div class="form-group">
                        <label>Location</label>
                        <input type="text" name="location" id="location" placeholder="e.g. University Auditorium">
                    </div>

                    <div class="form-group">
                        <label>Agenda PDF</label>
                        <label class="file-upload" id="agendaUpload">
                            <i class="bi bi-file-earmark-pdf"></i>
                            <p>Click or drag agenda PDF here</p>
                            <div id="agendaFileName" class="fname"></div>
                            <input type="file" name="agenda_file" id="agenda_file" accept=".pdf,application/pdf"
                                   onchange="document.getElementById('agendaFileName').textContent=this.files[0]?.name||'';">
                        </label>
                    </div>

                    <div class="form-group">
                        <label>Minutes URL (optional — enter external URL or leave blank)</label>
                        <input type="url" name="minutes_url" id="minutes_url" placeholder="https://…">
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-ghost" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn-primary"><i class="bi bi-plus-lg"></i>Create AGM</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../assets/js/sidebar.js"></script>
    <script src="../assets/js/loader-service.js"></script>
    <script>
        function openCreateModal() {
            document.getElementById("sessionModal").classList.add("open");
            document.getElementById("formAction").value="create"; document.getElementById("formId").value="";
            document.getElementById("modalTitle").textContent="New Annual GA Meeting";
            document.getElementById("agenda_file").value = "";
            document.getElementById("agendaFileName").textContent = "";
            document.getElementById("agendaUpload").classList.remove("has-file");
        }
        function openEditModal(id) {
            document.getElementById("sessionModal").classList.add("open");
            document.getElementById("formAction").value="edit"; document.getElementById("formId").value=id;
            const d=window._editData[id]; if(d){
                document.getElementById("status").value=d.status||"SCHEDULED";
                document.getElementById("title").value=d.title||"";
                document.getElementById("description").value=d.description||"";
                document.getElementById("location").value=d.location||"";
                document.getElementById("minutes_url").value=d.minutes_url||"";
                if(d.scheduled_datetime) document.getElementById("scheduled_datetime").value=d.scheduled_datetime.replace(" ","T").slice(0,16);
                document.getElementById("agenda_file").value = "";
                document.getElementById("agendaFileName").textContent = "";
                document.getElementById("agendaUpload").classList.remove("has-file");
            }
            document.getElementById("modalTitle").textContent="Edit Annual GA Meeting";
        }
        function closeModal(){ document.getElementById("sessionModal").classList.remove("open"); }
        document.getElementById("sessionModal").addEventListener("click",e=>{ if(e.target===this)closeModal(); });
        document.addEventListener("keydown",e=>{ if(e.key==="Escape")closeModal(); });
        window._editData={ <?php if($editSession): ?><?php echo $editSession['id']; ?>:<?php echo json_encode($editSession, JSON_HEX_APOS|JSON_HEX_QUOT); ?><?php endif; ?> };

        (function(){
            var dz=document.getElementById("agendaUpload");
            if(!dz)return;
            dz.addEventListener("dragover",function(e){e.preventDefault(); dz.style.borderColor="#c9a84c";});
            dz.addEventListener("dragleave",function(){ dz.style.borderColor=""; });
            dz.addEventListener("drop",function(e){
                e.preventDefault(); dz.style.borderColor="";
                var dt=e.dataTransfer; if(dt&&dt.files.length){
                    document.getElementById("agenda_file").files=dt.files;
                    document.getElementById("agendaFileName").textContent=dt.files[0].name;
                    dz.classList.add("has-file");
                }
            });
        })();
    </script>
</body>
</html>
