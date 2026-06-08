<?php
// Special Sessions Admin — filtered view: session_type=SPECIAL
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

$pageTitle  = "Special Sessions";
$success    = $_SESSION["success"] ?? null;
$errors     = $_SESSION["errors"]   ?? [];
unset($_SESSION["success"], $_SESSION["errors"]);
$gaModel    = new GaSessions(db());
$statusOpts = $gaModel->getStatusOptions();
$sessions   = $gaModel->getByType("SPECIAL");
$editSession= $gaModel->getById((int)($_GET["id"] ?? 0));
$_SESSION["csrf_token"] = $_SESSION["csrf_token"] ?? bin2hex(random_bytes(32));

$stsBadge = fn(string $s): array => match($s) {
    "SCHEDULED"=>["class"=>"badge-scheduled","label"=>"SCHEDULED"],
    "IN_PROGRESS"=>["class"=>"badge-inprogress","label"=>"IN PROGRESS"],
    "COMPLETED"=>["class"=>"badge-completed","label"=>"COMPLETED"],
    "CANCELLED"=>["class"=>"badge-cancelled","label"=>"CANCELLED"],
    default=>["class"=>"badge-scheduled","label"=>strtoupper($s)],
};

if ($_SERVER["REQUEST_METHOD"]==="POST") {
    $action = $_POST["action"] ?? "";
    $csrf   = $_POST["csrf_token"] ?? "";
    if (!hash_equals($_SESSION["csrf_token"] ?? "", $csrf)) {
        $_SESSION["errors"]=["Invalid CSRF token."]; header("Location: ga-special-sessions-admin.php"); exit;
    }
    if ($action==="create"||$action==="edit") {
        $data = [
            "session_type"=>"SPECIAL","title"=>trim($_POST["title"] ?? ""),
            "description"=>trim($_POST["description"] ?? ""),
            "scheduled_datetime"=>trim($_POST["scheduled_datetime"] ?? ""),
            "location"=>trim($_POST["location"] ?? ""),
            "status"=>$_POST["status"] ?? "SCHEDULED","minutes_url"=>trim($_POST["minutes_url"] ?? "")
        ];
        if (empty($data["title"])) { $_SESSION["errors"]=["Title is required."]; header("Location: ga-special-sessions-admin.php"); exit; }
        if ($action==="create") { $gaModel->create($data); $_SESSION["success"]="Special session created."; }
        else { $gaModel->update((int)$_POST["id"],$data); $_SESSION["success"]="Special session updated."; }
        header("Location: ga-special-sessions-admin.php"); exit;
    }
    elseif ($action==="delete") {
        $gaModel->delete((int)$_POST["id"]);
        $_SESSION["success"]="Special session deleted."; header("Location: ga-special-sessions-admin.php"); exit;
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
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;0,700;1,300;1,400&family=Outfit:wght@200;300;400;500;600;700&family=Space+Mono:ital@0,400;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <script>window.currentUserRole='<?php echo $currentRole; ?>';</script>
    <style>
        /* Inline minimal overrides below the shared CSS */
        .page-header { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:28px; flex-wrap:wrap; gap:16px; }
        .page-header h1 { color:#c9a84c; margin:0; font-size:2rem; }
        .page-header p  { color:#5a6a80; font-size:14px; margin-top:6px; }
        .btn-primary { background:#c9a84c; color:#060c1a; padding:10px 22px; border:none; border-radius:6px; font-weight:600; cursor:pointer; font-family:'Outfit',sans-serif; display:inline-flex; align-items:center; gap:8px; font-size:14px; }
        .btn-primary:hover { background:#b8973a; }
        .btn-ghost  { background:transparent; color:#a0b4d0; padding:8px 16px; border:1px solid rgba(160,180,208,.25); border-radius:6px; cursor:pointer; font-family:'Outfit',sans-serif; font-size:13px; }
        .btn-danger { background:#7b1a1a; color:#fff; padding:8px 16px; border:none; border-radius:6px; cursor:pointer; font-size:12px; }
        .btn-link   { background:none; border:none; color:#c9a84c; cursor:pointer; font-family:'Outfit',sans-serif; font-size:14px; }
        .btn-link:hover { text-decoration:underline; }
        .kpi-row { display:grid; grid-template-columns:repeat(auto-fill,minmax(130px,1fr)); gap:14px; margin-bottom:28px; }
        .kpi-card { background:rgba(14,20,40,.9); border:1px solid rgba(201,168,76,.12); border-radius:10px; padding:18px 16px; text-align:center; }
        .kpi-card .val { font-family:'Space Mono',monospace; font-size:1.8rem; font-weight:700; color:#c9a84c; }
        .kpi-card .lbl { font-size:10px; color:#5a6a80; text-transform:uppercase; letter-spacing:.08em; margin-top:4px; font-family:'Space Mono',monospace; }

        .ss-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(320px,1fr)); gap:18px; }
        .ss-card { background:rgba(14,20,40,.85); border:1px solid rgba(201,168,76,.1); border-radius:14px; padding:24px; }
        .ss-card-head { display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; }
        .ss-card-title { font-size:1rem; font-weight:700; color:#fff; }
        .ss-card-meta  { display:flex; flex-direction:column; gap:5px; margin-top:12px; font-size:12px; color:#7a8fa0; }
        .ss-card-desc  { font-size:13px; color:#8a9ab0; margin-top:10px; line-height:1.6; }
        .ss-card-actions { display:flex; gap:8px; flex-wrap:wrap; margin-top:16px; padding-top:14px; border-top:1px solid rgba(201,168,76,.08); }

        /* badged (shared) */
        .badge { display:inline-flex; align-items:center; gap:5px; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:600; font-family:'Space Mono',monospace; letter-spacing:.05em; }
        .badge-special     { background:rgba(201,168,76,.15); color:#c9a84c; border:1px solid rgba(201,168,76,.3); }
        .badge-scheduled   { background:rgba(74,144,226,.15); color:#6ab0ff; border:1px solid rgba(74,144,226,.3); }
        .badge-inprogress  { background:rgba(201,168,76,.15); color:#c9a84c; border:1px solid rgba(201,168,76,.3); }
        .badge-completed   { background:rgba(55,180,120,.15); color:#3eb87c; border:1px solid rgba(55,180,120,.3); }
        .badge-cancelled   { background:rgba(160,90,120,.15); color:#c06090; border:1px solid rgba(160,90,120,.3); }

        .empty-state { text-align:center; padding:60px 20px; color:#5a6a80; }
        .empty-state i { font-size:3rem; margin-bottom:16px; display:block; }

        .modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,.65); display:none; align-items:center; justify-content:center; z-index:9999; }
        .modal-overlay.open { display:flex; }
        .modal-box      { background:#0a1225; border:1px solid rgba(201,168,76,.22); border-radius:14px; width:90%; max-width:560px; max-height:90vh; overflow-y:auto; }
        .modal-header   { display:flex; align-items:center; justify-content:space-between; padding:22px 28px; border-bottom:1px solid rgba(201,168,76,.12); }
        .modal-header h2{ color:#c9a84c; margin:0; font-size:1.25rem; }
        .modal-close    { background:none; border:none; font-size:22px; color:#5a6a80; cursor:pointer; }
        .form-group     { margin-bottom:18px; }
        .form-group label{ display:block; font-size:12px; font-family:'Space Mono',monospace; color:#7a8fa0; letter-spacing:.08em; text-transform:uppercase; margin-bottom:6px; }
        .form-group input,.form-group textarea,.form-group select{ width:100%; background:rgba(6,12,26,.9); color:#d0d8e4; border:1px solid rgba(160,180,208,.18); border-radius:8px; padding:11px 14px; font-family:'Outfit',sans-serif; font-size:14px; box-sizing:border-box; }
        .form-row { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
        .modal-actions { display:flex; gap:10px; justify-content:flex-end; padding:0 28px 28px; }
        .modal-body   { padding:28px; }
        .alert        { padding:14px 20px; border-radius:8px; margin-bottom:20px; font-size:14px; display:flex; gap:10px; }
        .alert-success{ background:rgba(55,180,120,.12); border:1px solid rgba(55,180,120,.3); color:#3eb87c; }
        .alert-error  { background:rgba(220,80,60,.12);  border:1px solid rgba(220,80,60,.3);  color:#f07060; }
    </style>
<link rel="icon" type="image/png" href="../assets/images/logo.png">
</head>
<body>
    <div class="dashboard-layout">
        <div class="mobile-overlay" id="mobileOverlay"></div>
        <aside class="sidebar" id="sidebar">
            <button class="sidebar-toggle" id="sidebarToggle"><i class="bi bi-chevron-left"></i></button>
            <div class="sidebar-header"><div class="sidebar-logo">SRC</div><span class="sidebar-title">DHLTU Dashboard</span></div>
            <?php require_once "../include/nav-links.php"; $nav=new NavigationRBAC($currentRole); echo $nav->renderNavigation(); ?>
            <div class="sidebar-user">
                <div class="user-avatar"><?php echo strtoupper(substr($currentUser["first_name"],0,1).substr($currentUser["last_name"],0,1)); ?></div>
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($currentUser["first_name"]." ".$currentUser["last_name"]); ?></div>
                    <div class="user-role"><span class="role-badge <?php echo $currentRole==='PRO'?'admin':'monitor';?>"><?php echo $currentRole; ?></span></div>
                </div>
            </div>
        </aside>
        <div class="main-content">
            <header class="dashboard-header">
                <button class="mobile-toggle" id="mobileToggle"><i class="bi bi-list"></i></button>
                <h1 class="header-title"><?php echo $pageTitle; ?></h1>
                <div class="header-actions"><a href="logout.php" class="header-btn"><i class="bi bi-box-arrow-right"></i></a></div>
            </header>
            <main class="content-body"><div class="dashboard-container">
                <?php if($success):?><div class="alert alert-success"><i class="bi bi-check-circle"></i><?php echo htmlspecialchars($success);?></div><?php endif;?>
                <?php if($errors):?><div class="alert alert-error"><i class="bi bi-exclamation-triangle"></i><?php echo htmlspecialchars($errors[0]);?></div><?php endif;?>

                <div class="page-header">
                    <div>
                        <h1><i class="bi bi-lightning" style="margin-right:12px;vertical-align:middle;"></i>Special Sessions</h1>
                        <p>Manage topic-focused assemblies — proposed edits, approvals, and linked minutes / resolutions.</p>
                    </div>
                    <button class="btn-primary" onclick="openCreateModal()"><i class="bi bi-plus-lg"></i>New Session</button>
                </div>

                <div class="kpi-row">
                    <div class="kpi-card"><div class="val"><?php echo count($sessions); ?></div><div class="lbl">Total</div></div>
                    <div class="kpi-card"><div class="val" style="color:#c9a84c;"><?php echo (int)array_sum(array_column(array_filter($sessions,fn($s)=>$s['status']==='IN_PROGRESS'),'status')); ?></div><div class="lbl">In Progress</div></div>
                    <div class="kpi-card"><div class="val" style="color:#3eb87c;"><?php echo (int)array_sum(array_column(array_filter($sessions,fn($s)=>$s['status']==='COMPLETED'),'status')); ?></div><div class="lbl">Completed</div></div>
                </div>

                <div class="filter-bar">
                    <span class="badge badge-special"><i class="bi bi-lightning"></i>SPECIAL (locked)</span>
                    <span style="margin-left:auto;font-size:12px;color:#5a6a80;"><i class="bi bi-collection-play"></i> <?php echo count($sessions); ?> session<?php echo count($sessions)!==1?'s':''; ?></span>
                </div>

                <div class="ss-grid">
                <?php if(empty($sessions)): ?>
                <div class="empty-state" style="grid-column:1/-1;"><i class="bi bi-lightning-charge"></i><p>No Special Sessions yet. Create the first one.</p></div>
                <?php else: ?>
                <?php foreach($sessions as $s): ?>
                <?php $sb=$stsBadge($s["status"]); $attCount=(int)($s["attendees_count"]??0); ?>
                <div class="ss-card">
                    <div class="ss-card-head">
                        <div class="ss-card-title"><?php echo htmlspecialchars($s["title"]); ?></div>
                        <span class="badge <?php echo $sb["class"]; ?>"><?php echo $sb["label"]; ?></span>
                    </div>
                    <div class="ss-card-meta">
                        <span><i class="bi bi-clock"></i><?php echo $s["scheduled_datetime"]?date("M d, Y · H:i",strtotime($s["scheduled_datetime"])):"Unscheduled"; ?></span>
                        <?php if($s["location"]): ?><span><i class="bi bi-geo-alt"></i><?php echo htmlspecialchars($s["location"]); ?></span><?php endif; ?>
                        <span><i class="bi bi-people"></i><?php echo $attCount; ?> present</span>
                    </div>
                    <?php if($s["description"]):?><p class="ss-card-desc"><?php echo htmlspecialchars(truncate($s["description"],140)); ?></p><?php endif; ?>
                    <div class="ss-card-actions">
                        <a href="ga-attendance.php?session_id=<?php echo $s["id"];?>"   class="btn-link btn-sm"><i class="bi bi-people"></i> Attendance</a>
                        <a href="ga-minutes.php?session_id=<?php echo $s["id"];?>"        class="btn-link btn-sm"><i class="bi bi-file-earmark-pdf"></i> Minutes</a>
                        <a href="ga-resolutions.php?session_id=<?php echo $s["id"];?>"    class="btn-link btn-sm"><i class="bi bi-file-earmark-text"></i> Resolutions</a>
                        <button class="btn-link" onclick="openEditModal(<?php echo $s["id"];?>)"><i class="bi bi-pencil"></i> Edit</button>
                        <form method="POST" action="" style="display:inline"
                              onsubmit="return confirm('Delete \'<?php echo addslashes($s['title']);?>\'?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $s["id"]; ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION["csrf_token"]; ?>">
                            <button type="submit" class="btn-danger btn-sm"><i class="bi bi-trash"></i></button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
                </div>
            </div></main>
        </div>
    </div>

    <div class="modal-overlay" id="sessionModal">
        <div class="modal-box">
            <div class="modal-header">
                <h2 id="modalTitle">New Special Session</h2>
                <button class="modal-close" onclick="closeModal()"><i class="bi bi-x-lg"></i></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION["csrf_token"]; ?>">
                    <input type="hidden" name="action" id="formAction" value="create">
                    <input type="hidden" name="id"     id="formId">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" id="status" required>
                                <?php foreach($statusOpts as $so): ?><option value="<?php echo $so; ?>"><?php echo str_replace("_"," ",$so); ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Date &amp; Time</label>
                            <input type="datetime-local" name="scheduled_datetime" id="scheduled_datetime">
                        </div>
                    </div>
                    <div class="form-group"><label>Title</label><input type="text" name="title" id="title" required></div>
                    <div class="form-group"><label>Description</label><textarea name="description" id="description" rows="3"></textarea></div>
                    <div class="form-group"><label>Location</label><input type="text" name="location" id="location"></div>
                    <div class="form-group"><label>Minutes URL</label><input type="url" name="minutes_url" id="minutes_url"></div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-ghost" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn-primary"><i class="bi bi-lightning"></i>Create Session</button>
                </div>
            </form>
        </div>
    </div>
    <script src="../assets/js/sidebar.js"></script>
    <script src="../assets/js/loader-service.js"></script>
    <script>
        function openCreateModal(){ document.getElementById("sessionModal").classList.add("open"); document.getElementById("formAction").value="create"; document.getElementById("formId").value=""; document.getElementById("modalTitle").textContent="New Special Session"; }
        function openEditModal(id){ document.getElementById("sessionModal").classList.add("open"); document.getElementById("formAction").value="edit"; document.getElementById("formId").value=id; const d=window._editData[id]; if(d){document.getElementById("status").value=d.status||"SCHEDULED";document.getElementById("title").value=d.title||"";document.getElementById("description").value=d.description||"";document.getElementById("location").value=d.location||"";document.getElementById("minutes_url").value=d.minutes_url||"";if(d.scheduled_datetime)document.getElementById("scheduled_datetime").value=d.scheduled_datetime.replace(" ","T").slice(0,16);} document.getElementById("modalTitle").textContent="Edit Special Session"; }
        function closeModal(){ document.getElementById("sessionModal").classList.remove("open"); }
        document.getElementById("sessionModal").addEventListener("click",e=>{ if(e.target===this)closeModal(); });
        document.addEventListener("keydown",e=>{ if(e.key==="Escape")closeModal(); });
        window._editData={<?php if($editSession): ?><?php echo $editSession["id"];?>:<?php echo json_encode($editSession,JSON_HEX_APOS|JSON_HEX_QUOT); ?><?php endif; ?>};
    </script>
</body>
</html>
