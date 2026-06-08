<?php
// GA Resolutions & Motions Admin — CRUD for ga_resolutions table
session_start();
require_once "../config/database.php";
require_once "../config/functions.php";
require_once "../models/GaResolutions.php";
require_once "../models/GaSessions.php";

if (!isLogged()) {
    header("Location: login.php");
    exit;
}
$currentRole = currentRole();
$currentUser = currentUser();
if (!currentUserCan("can_manage_ga") || !in_array($currentRole,["PRO","PRESIDENT","DIRECTOR ICT","DEAN"])) {
    header("Location: index.php"); exit;
}

$pageTitle  = "Resolutions & Motions";
$success    = $_SESSION["success"] ?? null;
$errors     = $_SESSION["errors"]   ?? [];
unset($_SESSION["success"], $_SESSION["errors"]);
$rvModel    = new GaResolutions(db());
$gsModel    = new GaSessions(db());
$allSessions= $gsModel->getAll();
$stats      = $rvModel->getStats();
$catOpts    = $rvModel->getCategoryOptions();
$allUsers   = $rvModel->getAllUsers();
$statusOpts = ["PASSED","REJECTED","PENDING","TABLED","WITHDRAWN"];

$filterSession = $_GET["session_id"] ?? "";
$filterCat     = $_GET["category"] ?? "";
$filterStatus  = $_GET["status"]     ?? "";
$search        = trim($_GET["search"] ?? "");
$filters = [];
if ($filterSession)    $filters["session_id"] = (int)$filterSession;
if ($filterCat)        $filters["category"]  = $filterCat;
if ($filterStatus)     $filters["status"]    = $filterStatus;
if ($search)           $filters["search"]    = $search;
$resolutions = $rvModel->getAll($filters);

$editRes = null;
if (($_GET["action"]??"")==="edit") {
    $editRes = $rvModel->getById((int)($_GET["id"]??0));
}

$_SESSION["csrf_token"] = $_SESSION["csrf_token"] ?? bin2hex(random_bytes(32));

$resBadge = fn($cat) => match($cat) {
    "RESOLUTION"=> ["c"=>"rgba(74,144,226,.15)" ,"b"=>"rgba(74,144,226,.3)", "col"=>"#6ab0ff", "ico"=>"bi-file-earmark"],
    "MOTION"   => ["c"=>"rgba(201,168,76,.15)", "b"=>"rgba(201,168,76,.3)", "col"=>"#c9a84c", "ico"=>"bi-hand-index"],
    "AMENDMENT"=> ["c"=>"rgba(160,90,120,.15)", "b"=>"rgba(160,90,120,.3)", "col"=>"#c06090", "ico"=>"bi-file-earmark-arrow-up"],
    "DECLARATION"=>["c"=>"rgba(55,180,120,.15)","b"=>"rgba(55,180,120,.3)","col"=>"#3eb87c","ico"=>"bi-megaphone"],
};
$stBadge = fn($s) => match($s) {
    "PASSED"  =>["label"=>"PASSED", "c"=>"rgba(55,180,120,.15)"  ,"bc"=>"rgba(55,180,120,.3)"  ,"col"=>"#3eb87c"],
    "REJECTED"=>["label"=>"REJECTED","c"=>"rgba(220,80,60,.15)"   ,"bc"=>"rgba(220,80,60,.3)"   ,"col"=>"#f07060"],
    "PENDING" =>["label"=>"PENDING", "c"=>"rgba(201,168,76,.15)"  ,"bc"=>"rgba(201,168,76,.3)"  ,"col"=>"#c9a84c"],
    "TABLED"  =>["label"=>"TABLED",  "c"=>"rgba(160,90,120,.15)"  ,"bc"=>"rgba(160,90,120,.3)"  ,"col"=>"#c06090"],
    "WITHDRAWN"=>["label"=>"WITHDRAWN","c"=>"rgba(100,110,130,.15)","bc"=>"rgba(100,110,130,.3)","col"=>"#8a9ab0"],
};

// ── POST ────────────────────────────────────────────────────
if ($_SERVER["REQUEST_METHOD"]==="POST") {
    $action = $_POST["action"] ?? "";
    $csrf   = $_POST["csrf_token"] ?? "";
    if (!hash_equals($_SESSION["csrf_token"] ?? "",$csrf)) {
        $_SESSION["errors"]=["Invalid CSRF token."]; header("Location: ga-resolutions.php"); exit;
    }
    if ($action==="create"||$action==="edit") {
        $data = [
            "session_id"  =>(int)($_POST["session_id"]??0),
            "resolution_no"=>trim($_POST["resolution_no"]??""),
            "title"        =>trim($_POST["title"]??""),
            "body"         =>trim($_POST["body"]??""),
            "category"     =>$_POST["category"]??"RESOLUTION",
            "status"       =>$_POST["status"]??"PENDING",
            "vote_for"     =>(int)($_POST["vote_for"]??0),
            "vote_against" =>(int)($_POST["vote_against"]??0),
            "vote_abstain" =>(int)($_POST["vote_abstain"]??0),
            "proposer_id"  =>!empty($_POST["proposer_id"])?(int)$_POST["proposer_id"]:null,
            "seconded_by"  =>!empty($_POST["seconded_by"])?(int)$_POST["seconded_by"]:null,
        ];
        if (empty($data["title"])||empty($data["resolution_no"])) { $_SESSION["errors"]=["Title and Resolution Number are required."]; header("Location: ga-resolutions.php"); exit; }
        if ($action==="create") { $id=$rvModel->create($data); logActivity("create_ga_resolution",$currentUser["id"]??null,["res_id"=>$id,"title"=>$data["title"]]); $_SESSION["success"]="Resolution added."; }
        else { $rvModel->update((int)$_POST["id"],$data); $_SESSION["success"]="Resolution updated."; }
        header("Location: ga-resolutions.php"); exit;
    }
    elseif ($action==="delete") {
        logActivity("delete_ga_resolution",$currentUser["id"]??null,["res_id"=>(int)$_POST["id"]]);
        $rvModel->delete((int)$_POST["id"]); $_SESSION["success"]="Resolution deleted.";
        header("Location: ga-resolutions.php"); exit;
    }
    // Quick vote action
    elseif ($action==="quick_vote") {
        $resId=(int)$_POST["id"];
        $vF=(int)($_POST["vote_for"]??0); $vA=(int)($_POST["vote_against"]??0); $vAb=(int)($_POST["vote_abstain"]??0);
        $rvModel->recordVote($resId,$vF,$vA,$vAb);
        $newStatus = ($vF==0&&$vA==0&&$vAb==0) ? "PENDING" : (($vF>$vA) ? "PASSED" : "REJECTED");
        $rvModel->update((int)$resId,["status"=>$newStatus]);
        logActivity("vote_ga_resolution",$currentUser["id"]??null,["res_id"=>$resId,"new_status"=>$newStatus]);
        $_SESSION["success"]="Vote record updated. Status set to $newStatus.";
        header("Location: ga-resolutions.php"); exit;
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
        .page-header { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:28px; flex-wrap:wrap; gap:16px; }
        .page-header h1 { color:#c9a84c; margin:0; font-size:2rem; }
        .page-header p  { color:#5a6a80; font-size:14px; margin-top:6px; }
        .btn-primary { background:#c9a84c; color:#060c1a; padding:10px 22px; border:none; border-radius:6px; font-weight:600; cursor:pointer; font-family:'Outfit',sans-serif; display:inline-flex; align-items:center; gap:8px; font-size:14px; }
        .btn-primary:hover { background:#b8973a; }
        .btn-ghost  { background:transparent; color:#a0b4d0; padding:8px 16px; border:1px solid rgba(160,180,208,.25); border-radius:6px; cursor:pointer; font-family:'Outfit',sans-serif; font-size:13px; }
        .btn-danger { background:#7b1a1a; color:#fff; padding:8px 16px; border:none; border-radius:6px; cursor:pointer; font-size:12px; }
        .btn-success{ background:#1a4a2a; color:#3eb87c; padding:6px 14px; border:1px solid rgba(55,180,120,.3); border-radius:6px; font-size:12px; cursor:pointer; }
        .btn-success:hover { background:#1f5533; }
        .btn-link   { background:none; border:none; color:#c9a84c; cursor:pointer; font-family:'Outfit',sans-serif; font-size:14px; }
        .btn-link:hover { text-decoration:underline; }

        .kpi-row { display:grid; grid-template-columns:repeat(auto-fill,minmax(120px,1fr)); gap:12px; margin-bottom:28px; }
        .kpi-card { background:rgba(14,20,40,.9); border:1px solid rgba(201,168,76,.12); border-radius:10px; padding:16px; text-align:center; }
        .kpi-card .val { font-family:'Space Mono',monospace; font-size:1.6rem; font-weight:700; color:#c9a84c; }
        .kpi-card .lbl { font-size:10px; color:#5a6a80; text-transform:uppercase; letter-spacing:.08em; margin-top:4px; font-family:'Space Mono',monospace; }

        .filter-bar { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:24px; background:rgba(14,20,40,.8); border:1px solid rgba(201,168,76,.08); border-radius:10px; padding:16px 20px; }
        .filter-bar input,.filter-bar select { background:rgba(6,12,26,.8); color:#e0e8f0; border:1px solid rgba(160,180,208,.18); border-radius:6px; padding:8px 12px; font-family:'Outfit',sans-serif; font-size:13px; }

        .badge { display:inline-flex; align-items:center; gap:5px; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:600; font-family:'Space Mono',monospace; }
        .empty-state { text-align:center; padding:60px 20px; color:#5a6a80; }
        .empty-state i { font-size:3rem; margin-bottom:16px; display:block; }
        .alert { padding:14px 20px; border-radius:8px; margin-bottom:20px; font-size:14px; display:flex; gap:10px; }
        .alert-success { background:rgba(55,180,120,.12); border:1px solid rgba(55,180,120,.3); color:#3eb87c; }
        .alert-error   { background:rgba(220,80,60,.12);  border:1px solid rgba(220,80,60,.3);  color:#f07060; }

        /* Resolution cards */
        .res-list { display:flex; flex-direction:column; gap:0; background:rgba(14,20,40,.85); border:1px solid rgba(201,168,76,.1); border-radius:12px; overflow:hidden; }
        .res-card { padding:20px 24px; border-bottom:1px solid rgba(255,255,255,.04); display:grid; grid-template-columns:1fr auto auto; gap:14px; align-items:start; }
        .res-card:last-child { border-bottom:none; }
        .res-card:hover { background:rgba(201,168,76,.04); }
        .res-head  { display:flex; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:6px; }
        .res-no    { font-family:'Space Mono',monospace; font-size:12px; color:#c9a84c; letter-spacing:.05em; }
        .res-toggled-body-expanded { display:none; margin-top:12px; font-size:13px; color:#8a9ab0; line-height:1.7; }
        .res-card.show-body .res-toggled-body-expanded { display:block; }
        .res-actions { display:flex; gap:6px; flex-wrap:wrap; justify-content:flex-end; margin-top:6px; }

        /* Modal */
        .modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,.65); display:none; align-items:center; justify-content:center; z-index:9999; }
        .modal-overlay.open { display:flex; }
        .modal-box { background:#0a1225; border:1px solid rgba(201,168,76,.22); border-radius:14px; width:90%; max-width:700px; max-height:90vh; overflow-y:auto; }
        .modal-header { display:flex; align-items:center; justify-content:space-between; padding:22px 28px; border-bottom:1px solid rgba(201,168,76,.12); }
        .modal-header h2{ color:#c9a84c; margin:0; font-size:1.25rem; }
        .modal-close  { background:none; border:none; font-size:22px; color:#5a6a80; cursor:pointer; }
        .form-group   { margin-bottom:18px; }
        .form-row { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
        .form-group label { display:block; font-size:12px; font-family:'Space Mono',monospace; color:#7a8fa0; letter-spacing:.08em; text-transform:uppercase; margin-bottom:6px; }
        .form-group input, .form-group textarea, .form-group select { width:100%; background:rgba(6,12,26,.9); color:#d0d8e4; border:1px solid rgba(160,180,208,.18); border-radius:8px; padding:11px 14px; font-family:'Outfit',sans-serif; font-size:14px; box-sizing:border-box; }
        .form-group input:focus, .form-group textarea:focus { outline:none; border-color:#c9a84c; }
        .form-group textarea { min-height:80px; resize:vertical; }
        .modal-actions { display:flex; gap:10px; justify-content:flex-end; padding:0 28px 28px; }
        .modal-body   { padding:28px; }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .page-header h1 { font-size:1.5rem; }
            .page-header { flex-direction:column; align-items:flex-start; }
            .page-header .btn-primary { width:100%; justify-content:center; }
            .kpi-row { grid-template-columns:repeat(2,1fr); }
            .filter-bar { flex-direction:column; align-items:stretch; }
            .filter-bar input, .filter-bar select { width:100%; min-width:auto; box-sizing:border-box; }
            .res-card { grid-template-columns:1fr; }
            .res-actions { justify-content:flex-start; }
            .form-row { grid-template-columns:1fr; }
            .modal-box { width:95%; max-width:95%; }
            .dashboard-container { padding:16px; }
            .content-body { padding:16px; }
        }

        @media (max-width: 480px) {
            .page-header h1 { font-size:1.25rem; }
            .kpi-row { grid-template-columns:1fr 1fr; }
            .res-card { padding:16px 20px; }
            .header-title { font-size:1.1rem; }
        }
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
                        <h1><i class="bi bi-file-earmark-text" style="margin-right:12px;vertical-align:middle;"></i>Resolutions &amp; Motions</h1>
                        <p>Track all resolutions and motions adopted at GA sessions &mdash; voting results, proposer, and full text.</p>
                    </div>
                    <button class="btn-primary" onclick="openCreateModal()"><i class="bi bi-plus-lg"></i>Add Resolution</button>
                </div>

                <div class="kpi-row">
                    <div class="kpi-card"><div class="val"><?php echo (int)$stats["total"]; ?></div><div class="lbl">Total</div></div>
                    <div class="kpi-card"><div class="val" style="color:#3eb87c;"><?php echo (int)$stats["passed"]; ?></div><div class="lbl">Passed</div></div>
                    <div class="kpi-card"><div class="val" style="color:#c9a84c;"><?php echo (int)$stats["pending"]; ?></div><div class="lbl">Pending</div></div>
                    <div class="kpi-card"><div class="val" style="color:#f07060;"><?php echo (int)$stats["rejected"]; ?></div><div class="lbl">Rejected</div></div>
                </div>

                <div class="filter-bar">
                    <select name="session_id" onchange="window.location='?session_id='+encodeURIComponent(this.value)+'&category=<?php echo urlencode($filterCat);?>&status=<?php echo urlencode($filterStatus);?>&search=<?php echo urlencode($search);?>'">
                        <option value="">All Sessions</option>
                        <?php foreach($allSessions as $s): ?>
                            <option value="<?php echo $s["id"]; ?>" <?php echo $filterSession==(int)$s["id"]?"selected":""; ?>><?php echo htmlspecialchars($s["title"]); ?> (<?php echo $s["session_type"]; ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <select name="category" onchange="window.location='?category='+encodeURIComponent(this.value)+'&session_id=<?php echo urlencode($filterSession);?>&status=<?php echo urlencode($filterStatus);?>&search=<?php echo urlencode($search);?>'">
                        <option value="">All Types</option>
                        <?php foreach($catOpts as $c): ?><option value="<?php echo $c; ?>" <?php echo $filterCat==$c?"selected":""; ?>><?php echo $c; ?></option><?php endforeach; ?>
                    </select>
                    <select name="status" onchange="window.location='?status='+encodeURIComponent(this.value)+'&session_id=<?php echo urlencode($filterSession);?>&category=<?php echo urlencode($filterCat);?>&search=<?php echo urlencode($search);?>'">
                        <option value="">All Statuses</option>
                        <?php foreach($statusOpts as $st): ?><option value="<?php echo $st; ?>" <?php echo $filterStatus==$st?"selected":""; ?>><?php echo $st; ?></option><?php endforeach; ?>
                    </select>
                    <input type="text" placeholder="Search resolutions…" value="<?php echo htmlspecialchars($search); ?>"
                           onchange="window.location='?search='+encodeURIComponent(this.value)+'&session_id=<?php echo urlencode($filterSession);?>&category=<?php echo urlencode($filterCat);?>&status=<?php echo urlencode($filterStatus);?>'"/>
                </div>

                <div class="res-list">
                <div style="display:grid;grid-template-columns:1fr auto auto;gap:14px;padding:14px 24px;font-family:'Space Mono',monospace;font-size:10px;letter-spacing:.1em;text-transform:uppercase;color:#c9a84c;background:rgba(201,168,76,.04);border-bottom:1px solid rgba(201,168,76,.12);">
                    <span>Resolution</span><span style="min-width:130px;text-align:center;">Votes</span><span style="min-width:150px;text-align:right;">Actions</span>
                </div>
                <?php if(empty($resolutions)): ?>
                <div class="empty-state" style="padding:40px;"><i class="bi bi-file-earmark-text"></i><p>No resolutions found. Add the first one.</p></div>
                <?php else: ?>
                <?php foreach($resolutions as $r): ?>
                    <?php $rb=$resBadge($r["category"]); $sb=$stBadge($r["status"]); ?>
                    <div class="res-card" id="res-<?php echo $r["id"]; ?>">
                        <div>
                            <div class="res-head">
                                <span class="res-no">#<?php echo htmlspecialchars($r["resolution_no"]); ?></span>
                                <span class="badge" style="background:<?php echo $rb["c"]; ?>;color:<?php echo $rb["col"]; ?>;border:1px solid <?php echo $rb["b"]; ?>;">
                                    <i class="bi <?php echo $rb["ico"]; ?>"></i><?php echo $r["category"]; ?>
                                </span>
                                <span class="badge" style="background:<?php echo $sb["c"]; ?>;color:<?php echo $sb["col"]; ?>;border:1px solid <?php echo $sb["bc"]; ?>;">
                                    <?php echo $sb["label"]; ?>
                                </span>
                            </div>
                            <div style="font-weight:600;color:#fff;font-size:15px;"><?php echo htmlspecialchars($r["title"]); ?></div>
                            <div style="font-size:12px;color:#5a6a80;margin-top:4px;font-family:'Space Mono',monospace;">
                                <?php echo htmlspecialchars($r["session_title"] ?? ""); ?> (<?php echo $r["session_type"] ?? ""; ?>)
                                <?php if($r["proposer_first"]): ?>
                                    · Proposed by <span style="color:#8a9ab0;"><?php echo htmlspecialchars($r["proposer_first"]." ".$r["proposer_last"]); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="res-toggled-body-expanded"><?php echo htmlspecialchars($r["body"]); ?></div>
                            <button onclick="this.parentElement.parentElement.classList.toggle('show-body')"
                                    style="margin-top:10px;background:none;border:none;color:#c9a84c;cursor:pointer;font-family:'Outfit',sans-serif;font-size:12px;padding:0;">
                                <i class="bi bi-arrows-expand"></i> Toggle full text
                            </button>
                            <div class="res-actions">
                                <form method="POST" action="" style="display:inline;display:flex;align-items:center;gap:8px;margin-top:6px;">
                                    <input type="hidden" name="action" value="quick_vote">
                                    <input type="hidden" name="id" value="<?php echo $r["id"]; ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION["csrf_token"]; ?>">
                                    <label style="font-size:11px;color:#7a8fa0;font-family:'Space Mono',monospace;">YE:</label>
                                    <input type="number" name="vote_for" value="<?php echo (int)($r["vote_for"]??0); ?>" min="0" style="width:55px;background:rgba(6,12,26,.8);color:#3eb87c;border:1px solid rgba(55,180,120,.3);border-radius:6px;padding:5px 8px;font-size:12px;font-family:'Space Mono',monospace;">
                                    <label style="font-size:11px;color:#7a8fa0;font-family:'Space Mono',monospace;">NO:</label>
                                    <input type="number" name="vote_against" value="<?php echo (int)($r["vote_against"]??0); ?>" min="0" style="width:55px;background:rgba(6,12,26,.8);color:#f07060;border:1px solid rgba(220,80,60,.3);border-radius:6px;padding:5px 8px;font-size:12px;font-family:'Space Mono',monospace;">
                                    <label style="font-size:11px;color:#7a8fa0;font-family:'Space Mono',monospace;">AB:</label>
                                    <input type="number" name="vote_abstain" value="<?php echo (int)($r["vote_abstain"]??0); ?>" min="0" style="width:55px;background:rgba(6,12,26,.8);color:#c9a84c;border:1px solid rgba(201,168,76,.3);border-radius:6px;padding:5px 8px;font-size:12px;font-family:'Space Mono',monospace;">
                                    <button type="submit" class="btn-success btn-sm" title="Save votes"><i class="bi bi-check-lg"></i></button>
                                </form>
                                <a href="ga-voting.php?resolution_id=<?php echo $r["id"]; ?>" class="btn-link" style="font-size:12px;" title="Manage in Voting page"><i class="bi bi-card-checklist"></i> Vote</a>
                                <button class="btn-link" onclick="openEditModal(<?php echo $r["id"];?>)"><i class="bi bi-pencil"></i></button>
                                <form method="POST" action="" style="display:inline" onsubmit="return confirm('Delete resolution #<?php echo $r['resolution_no']; ?>?');">
                                    <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?php echo $r["id"]; ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION["csrf_token"]; ?>">
                                    <button type="submit" class="btn-danger btn-sm"><i class="bi bi-trash"></i></button>
                                </form>
                            </div>
                        </div>
                        <div style="text-align:right;white-space:nowrap;">
                            <div style="font-size:11px;font-family:'Space Mono',monospace;color:#5a6a80;margin-bottom:6px;">
                                YES: <span style="color:#3eb87c;font-weight:700;"><?php echo (int)($r["vote_for"]??0); ?></span> &nbsp;
                                NO:  <span style="color:#f07060;font-weight:700;"><?php echo (int)($r["vote_against"]??0); ?></span> &nbsp;
                                AB:  <span style="color:#c9a84c;font-weight:700;"><?php echo (int)($r["vote_abstain"]??0); ?></span>
                            </div>
                            <span class="badge" style="background:<?php echo $sb["c"]; ?>;color:<?php echo $sb["col"]; ?>;border:1px solid <?php echo $sb["bc"]; ?>;"><?php echo $sb["label"]; ?></span>
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
                <h2 id="modalTitle"><?php echo $editRes?"Edit Resolution":"Add Resolution / Motion"; ?></h2>
                <button class="modal-close" onclick="closeModal()"><i class="bi bi-x-lg"></i></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION["csrf_token"]; ?>">
                    <input type="hidden" name="action" id="formAction" value="<?php echo $editRes?"edit":"create"; ?>">
                    <input type="hidden" name="id"     id="formId" value="<?php echo $editRes?$editRes["id"]:""; ?>">
                    <div class="form-row">
                        <div class="form-group">
                            <label>GA Session</label>
                            <select name="session_id" id="session_id" required>
                                <option value="">— Select session —</option>
                                <?php foreach($allSessions as $s): ?>
                                    <option value="<?php echo $s["id"]; ?>" <?php echo ($editRes&&$editRes["session_id"]==$s["id"])?"selected":($filterSession==(int)$s["id"]?"selected":""); ?>>
                                        <?php echo htmlspecialchars($s["title"]); ?> (<?php echo $s["session_type"]; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Type</label>
                            <select name="category" id="category">
                                <?php foreach($catOpts as $c): ?><option value="<?php echo $c; ?>" <?php echo ($editRes&&$editRes["category"]==$c)?"selected":""; ?>><?php echo $c; ?></option><?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Res. / Mot. Number</label>
                            <input type="text" name="resolution_no" id="resolution_no" required placeholder="e.g. R-001/2025" value="<?php echo $editRes?htmlspecialchars($editRes["resolution_no"]):""; ?>">
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" id="status">
                                <?php foreach($statusOpts as $st): ?><option value="<?php echo $st; ?>" <?php echo ($editRes&&$editRes["status"]==$st)?"selected":""; ?>><?php echo $st; ?></option><?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Title</label>
                        <input type="text" name="title" id="title" required placeholder="Short title…" value="<?php echo $editRes?htmlspecialchars($editRes["title"]):""; ?>">
                    </div>
                    <div class="form-group">
                        <label>Full Text</label>
                        <textarea name="body" id="body" rows="6" placeholder="Full text of the resolution or motion…"><?php echo $editRes?htmlspecialchars($editRes["body"]):""; ?></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Proposer</label>
                            <select name="proposer_id">
                                <option value="">— Select proposer —</option>
                                <?php foreach($allUsers as $u): ?>
                                    <option value="<?php echo $u["id"]; ?>" <?php echo ($editRes&&$editRes["proposer_id"]==$u["id"])?"selected":""; ?>>
                                        <?php echo htmlspecialchars($u["first_name"]." ".$u["last_name"]); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Seconded By</label>
                            <select name="seconded_by">
                                <option value="">— Select —</option>
                                <?php foreach($allUsers as $u): ?>
                                    <option value="<?php echo $u["id"]; ?>" <?php echo ($editRes&&$editRes["seconded_by"]==$u["id"])?"selected":""; ?>>
                                        <?php echo htmlspecialchars($u["first_name"]." ".$u["last_name"]); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;">
                        <div class="form-group">
                            <label style="color:#3eb87c;">Vote For</label>
                            <input type="number" name="vote_for" min="0" value="<?php echo $editRes?(int)($editRes["vote_for"]??0):0; ?>" style="border-color:rgba(55,180,120,.3);">
                        </div>
                        <div class="form-group">
                            <label style="color:#f07060;">Vote Against</label>
                            <input type="number" name="vote_against" min="0" value="<?php echo $editRes?(int)($editRes["vote_against"]??0):0; ?>" style="border-color:rgba(220,80,60,.3);">
                        </div>
                        <div class="form-group">
                            <label style="color:#c9a84c;">Abstain</label>
                            <input type="number" name="vote_abstain" min="0" value="<?php echo $editRes?(int)($editRes["vote_abstain"]??0):0; ?>" style="border-color:rgba(201,168,76,.3);">
                        </div>
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-ghost" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn-primary"><i class="bi bi-<?php echo $editRes?'check':'plus'; ?>-lg"></i><?php echo $editRes?"Update":"Add Resolution"; ?></button>
                </div>
            </form>
        </div>
    </div>
    <script src="../assets/js/sidebar.js"></script>
    <script src="../assets/js/loader-service.js"></script>
    <script>
        function openCreateModal(){
            closeModal(); setTimeout(()=>{
                document.getElementById("sessionModal").classList.add("open");
                document.getElementById("formAction").value="create"; document.getElementById("formId").value="";
                document.getElementById("modalTitle").textContent="Add Resolution / Motion";
                ["resolution_no","title","body"].forEach(id=>{ const el=document.getElementById(id); if(el)el.value=""; });
                document.getElementById("session_id").value="<?php echo (int)$filterSession; ?>";
            },50);
        }
        function openEditModal(id){
            document.getElementById("sessionModal").classList.add("open"); document.getElementById("formAction").value="edit"; document.getElementById("formId").value=id;
            const d=window._editData[id]; if(d){
                document.getElementById("session_id").value=d.session_id||""; document.getElementById("resolution_no").value=d.resolution_no||"";
                document.getElementById("title").value=d.title||""; document.getElementById("body").value=d.body||"";
                document.getElementById("category").value=d.category||"RESOLUTION"; document.getElementById("status").value=d.status||"PENDING";
            }
            document.getElementById("modalTitle").textContent="Edit Resolution / Motion";
        }
        function closeModal(){ document.getElementById("sessionModal").classList.remove("open"); }
        document.getElementById("sessionModal").addEventListener("click",e=>{ if(e.target===this)closeModal(); });
        document.addEventListener("keydown",e=>{ if(e.key==="Escape")closeModal(); });
        window._editData={<?php if($editRes): ?><?php echo $editRes["id"];?>:<?php echo json_encode($editRes,JSON_HEX_APOS|JSON_HEX_QUOT); ?><?php endif; ?>};
    </script>
</body>
</html>
