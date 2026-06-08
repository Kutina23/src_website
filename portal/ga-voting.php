<?php
// GA Voting Sessions Admin — CRUD for ga_voting table
session_start();
require_once "../config/database.php";
require_once "../config/functions.php";
require_once "../models/GaVoting.php";
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

$pageTitle = "GA Voting";
$success   = $_SESSION["success"] ?? null;
$errors    = $_SESSION["errors"]   ?? [];
unset($_SESSION["success"], $_SESSION["errors"]);

$vvModel    = new GaVoting(db());
$gsModel    = new GaSessions(db());
$allSessions= $gsModel->getAll();
$stats      = $vvModel->getStats();
$allUsers   = $vvModel->getAllUsers();
$statusOpts = ["OPEN","CLOSED"];
$resultOpts = ["PASSED","REJECTED"];
$voteTypeOpts = $vvModel->getVoteTypeOptions();

$filterSession = $_GET["session_id"] ?? "";
$filterStatus  = $_GET["status"]     ?? "";
$filterResult  = $_GET["result"]     ?? "";
$search        = trim($_GET["search"] ?? "");
$filters = [];
if ($filterSession) $filters["session_id"]   = (int)$filterSession;
if ($filterStatus)  $filters["status"]       = $filterStatus;
if ($filterResult)  $filters["result"]       = $filterResult;
if ($search)        $filters["search"]       = $search;
$votings = $vvModel->getAll($filters);

$editVoting = null;
if (($_GET["action"]??"")==="edit") {
    $editVoting = $vvModel->getById((int)($_GET["id"]??0));
}

$_SESSION["csrf_token"] = $_SESSION["csrf_token"] ?? bin2hex(random_bytes(32));

$stBadge = fn($s) => match($s) {
    "OPEN"   =>["label"=>"OPEN"   ,"c"=>"rgba(74,144,226,.15)" ,"bc"=>"rgba(74,144,226,.3)" ,"col"=>"#6ab0ff"],
    "CLOSED" =>["label"=>"CLOSED" ,"c"=>"rgba(100,110,130,.15)","bc"=>"rgba(100,110,130,.3)","col"=>"#8a9ab0"],
};
$rsBadge = fn($r) => match($r) {
    "PENDING"  =>["label"=>"PENDING" ,"c"=>"rgba(201,168,76,.15)" ,"bc"=>"rgba(201,168,76,.3)" ,"col"=>"#c9a84c"],
    "PASSED"  =>["label"=>"PASSED" ,"c"=>"rgba(55,180,120,.15)" ,"bc"=>"rgba(55,180,120,.3)" ,"col"=>"#3eb87c"],
    "REJECTED"=>["label"=>"REJECTED","c"=>"rgba(220,80,60,.15)"  ,"bc"=>"rgba(220,80,60,.3)"  ,"col"=>"#f07060"],
    default   =>["label"=>"—"      ,"c"=>"rgba(100,110,130,.15)","bc"=>"rgba(100,110,130,.3)","col"=>"#8a9ab0"],
};
$vtBadge = fn($t) => match($t) {
    "SIMPLE_MAJORITY"=>["c"=>"rgba(74,144,226,.15)" ,"bc"=>"rgba(74,144,226,.3)" ,"col"=>"#6ab0ff"],
    "TWO_THIRDS"     =>["c"=>"rgba(201,168,76,.15)","bc"=>"rgba(201,168,76,.3)" ,"col"=>"#c9a84c"],
    "UNANIMOUS"      =>["c"=>"rgba(160,90,120,.15)" ,"bc"=>"rgba(160,90,120,.3)" ,"col"=>"#c06090"],
};

// ── POST Handlers ──────────────────────────────────────────
if ($_SERVER["REQUEST_METHOD"]==="POST") {
    $action = $_POST["action"] ?? "";
    $csrf   = $_POST["csrf_token"] ?? "";
    if (!hash_equals($_SESSION["csrf_token"] ?? "", $csrf)) {
        $_SESSION["errors"]=["Invalid CSRF token."]; header("Location: ga-voting.php"); exit;
    }

    if ($action==="create" || $action==="edit") {
        $data = [
            "session_id"     => (int)($_POST["session_id"]??0),
            "title"          => trim($_POST["title"]??""),
            "description"    => trim($_POST["description"]??""),
            "vote_type"      => $_POST["vote_type"] ?? "SIMPLE_MAJORITY",
            "status"         => $_POST["status"] ?? "OPEN",
            "total_eligible" => (int)($_POST["total_eligible"]??0),
            "opened_by"      => !empty($_POST["opened_by"])?(int)$_POST["opened_by"]:null,
        ];
        if (empty($data["title"])) { $_SESSION["errors"]=["Voting title is required."]; header("Location: ga-voting.php"); exit; }
        if ($action==="create") { $id=$vvModel->create($data); $_SESSION["success"]="Voting session created."; }
        else { $vvModel->update((int)$_POST["id"],$data); $_SESSION["success"]="Voting session updated."; }
        header("Location: ga-voting.php"); exit;
    }
    elseif ($action==="delete") {
        $vvModel->delete((int)$_POST["id"]); $_SESSION["success"]="Voting session deleted.";
        header("Location: ga-voting.php"); exit;
    }
    // open voting
    elseif ($action==="open") {
        $vvModel->openVoting((int)$_POST["id"], $_SESSION["user_id"] ?? null);
        $_SESSION["success"]="Voting opened."; header("Location: ga-voting.php"); exit;
    }
    // close voting
    elseif ($action==="close") {
        $vvModel->closeVoting((int)$_POST["id"], $_SESSION["user_id"] ?? null);
        $_SESSION["success"]="Voting closed."; header("Location: ga-voting.php"); exit;
    }
    // approve a pending vote (public device-vote)
    elseif ($action==="approve") {
        $vvModel->approveVote((int)($_POST["record_id"] ?? 0));
        $votingId = (int)($_POST["voting_id"] ?? 0);
        if ($votingId) $vvModel->recalcVotes($votingId);
        $_SESSION["success"]="Vote approved."; header("Location: ga-voting.php".(($filterSession||$filterStatus||$filterResult||$search)?"?".http_build_query(compact('filterSession','filterStatus','filterResult','search')):""));
        exit;
    }
    // reject a pending vote
    elseif ($action==="reject") {
        $vvModel->rejectVote((int)($_POST["record_id"] ?? 0));
        $votingId = (int)($_POST["voting_id"] ?? 0);
        if ($votingId) $vvModel->recalcVotes($votingId);
        $_SESSION["success"]="Vote rejected."; header("Location: ga-voting.php".(($filterSession||$filterStatus||$filterResult||$search)?"?".http_build_query(compact('filterSession','filterStatus','filterResult','search')):""));
        exit;
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
        .btn-link   { background:none; border:none; color:#c9a84c; cursor:pointer; font-family:'Outfit',sans-serif; font-size:14px; }
        .btn-link:hover { text-decoration:underline; }
        .btn-success{ background:#1a4a2a; color:#3eb87c; padding:6px 14px; border:1px solid rgba(55,180,120,.3); border-radius:6px; font-size:12px; cursor:pointer; }
        .btn-success:hover { background:#1f5533; }
        .btn-info   { background:#0a2a4a; color:#6ab0ff; padding:6px 14px; border:1px solid rgba(74,144,226,.3); border-radius:6px; font-size:12px; cursor:pointer; }
        .btn-info:hover   { background:#0e3058; }

        .kpi-row { display:grid; grid-template-columns:repeat(auto-fill,minmax(110px,1fr)); gap:12px; margin-bottom:28px; }
        .kpi-card { background:rgba(14,20,40,.9); border:1px solid rgba(201,168,76,.12); border-radius:10px; padding:16px; text-align:center; }
        .kpi-card .val { font-family:'Space Mono',monospace; font-size:1.6rem; font-weight:700; color:#c9a84c; }
        .kpi-card .lbl { font-size:10px; color:#5a6a80; text-transform:uppercase; letter-spacing:.08em; margin-top:4px; font-family:'Space Mono',monospace; }
        .kpi-card .val.open    { color:#6ab0ff; }
        .kpi-card .val.closed  { color:#8a9ab0; }
        .kpi-card .val.passed  { color:#3eb87c; }
        .kpi-card .val.rejected { color:#f07060; }

        .filter-bar { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:24px; background:rgba(14,20,40,.8); border:1px solid rgba(201,168,76,.08); border-radius:10px; padding:16px 20px; }
        .filter-bar input,.filter-bar select { background:rgba(6,12,26,.8); color:#e0e8f0; border:1px solid rgba(160,180,208,.18); border-radius:6px; padding:8px 12px; font-family:'Outfit',sans-serif; font-size:13px; }

        /* Vote rows */
        .vot-list { display:flex; flex-direction:column; gap:0; background:rgba(14,20,40,.85); border:1px solid rgba(201,168,76,.1); border-radius:12px; overflow:hidden; }
        .vot-row  { display:grid; grid-template-columns:1fr 120px 110px 110px 110px 1fr; align-items:center; gap:10px; padding:14px 18px; border-bottom:1px solid rgba(255,255,255,.04); }
        .vot-row:last-child { border-bottom:none; }
        .vot-row:hover { background:rgba(201,168,76,.04); }
        .vot-row-head { font-family:'Space Mono',monospace; font-size:10px; letter-spacing:.1em; text-transform:uppercase; color:#c9a84c; background:rgba(201,168,76,.04); }
        .vot-title  { font-weight:600; color:#fff; font-size:14px; }
        .vot-sess   { font-size:12px; color:#7a8fa0; margin-top:2px; }
        .vot-desc   { font-size:12px; color:#5a6a80; padding-top:3px; }

        .badge { display:inline-flex; align-items:center; gap:5px; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:600; font-family:'Space Mono',monospace; }
        .badge-blue    { background:rgba(74,144,226,.15);  color:#6ab0ff; border:1px solid rgba(74,144,226,.3); }
        .badge-gray    { background:rgba(100,110,130,.15); color:#8a9ab0; border:1px solid rgba(100,110,130,.3); }
        .badge-green   { background:rgba(55,180,120,.15);  color:#3eb87c; border:1px solid rgba(55,180,120,.3); }
        .badge-red     { background:rgba(220,80,60,.15);   color:#f07060; border:1px solid rgba(220,80,60,.3); }

        .vote-bar { display:flex; gap:3px; height:6px; border-radius:3px; overflow:hidden; margin-top:6px; }
        .vote-bar .vyes { background:#3eb87c; }
        .vote-bar .vno  { background:#f07060; }
        .vote-bar .vabs { background:#c9a84c; }

        .empty-state { text-align:center; padding:60px 20px; color:#5a6a80; }
        .empty-state i { font-size:3rem; margin-bottom:16px; display:block; }
        .alert { padding:14px 20px; border-radius:8px; margin-bottom:20px; font-size:14px; display:flex; gap:10px; }
        .alert-success { background:rgba(55,180,120,.12); border:1px solid rgba(55,180,120,.3); color:#3eb87c; }
        .alert-error   { background:rgba(220,80,60,.12);  border:1px solid rgba(220,80,60,.3);  color:#f07060; }

        /* Modal */
        .modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,.65); display:none; align-items:center; justify-content:center; z-index:9999; }
        .modal-overlay.open { display:flex; }
        .modal-box { background:#0a1225; border:1px solid rgba(201,168,76,.22); border-radius:14px; width:90%; max-width:620px; max-height:90vh; overflow-y:auto; }
        .modal-header { display:flex; align-items:center; justify-content:space-between; padding:22px 28px; border-bottom:1px solid rgba(201,168,76,.12); }
        .modal-header h2{ color:#c9a84c; margin:0; font-size:1.25rem; }
        .modal-close  { background:none; border:none; font-size:22px; color:#5a6a80; cursor:pointer; }
        .form-group   { margin-bottom:18px; }
        .form-row { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
        .form-group label { display:block; font-size:12px; font-family:'Space Mono',monospace; color:#7a8fa0; letter-spacing:.08em; text-transform:uppercase; margin-bottom:6px; }
        .form-group input, .form-group textarea, .form-group select { width:100%; background:rgba(6,12,26,.9); color:#d0d8e4; border:1px solid rgba(160,180,208,.18); border-radius:8px; padding:11px 14px; font-family:'Outfit',sans-serif; font-size:14px; box-sizing:border-box; }
        .form-group input:focus, .form-group textarea:focus { outline:none; border-color:#c9a84c; }
        .form-group textarea { min-height:70px; resize:vertical; }
        .stat-count-wrap { display:grid; grid-template-columns:1fr 1fr 1fr; gap:10px; margin-bottom:18px; }
        .stat-count-wrap .stat-cell { text-align:center; background:rgba(6,12,26,.7); border:1px solid rgba(160,180,208,.12); border-radius:8px; padding:10px; }
        .stat-count-wrap .stat-cell .sv { font-family:'Space Mono',monospace; font-size:1.3rem; font-weight:700; }
        .stat-count-wrap .stat-cell.yes .sv { color:#3eb87c; }
        .stat-count-wrap .stat-cell.no  .sv { color:#f07060; }
        .stat-count-wrap .stat-cell.abs  .sv { color:#c9a84c; }
        .stat-count-wrap .stat-cell .sl { font-size:10px; color:#5a6a80; text-transform:uppercase; margin-top:3px; font-family:'Space Mono',monospace; }
        .modal-actions { display:flex; gap:10px; justify-content:flex-end; padding:0 28px 28px; }
        .modal-body   { padding:28px; }

        @media(max-width:640px){
            .vot-row { grid-template-columns:1fr; gap:4px; }
            .vot-row .act-cell { text-align:left; }
            .vot-row .vt-cell { text-align:left; }
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
                        <h1><i class="bi bi-card-checklist" style="margin-right:12px;vertical-align:middle;"></i>GA Voting</h1>
                        <p>Manage voting sessions — open, close, and track results for all GA ballot items.</p>
                    </div>
                    <button class="btn-primary" onclick="openCreateModal()"><i class="bi bi-plus-lg"></i> New Voting</button>
                </div>

                <div class="kpi-row">
                    <div class="kpi-card"><div class="val"><?php echo (int)$stats["total"]; ?></div><div class="lbl">Total</div></div>
                    <div class="kpi-card"><div class="val open"><?php echo (int)$stats["open"]; ?></div><div class="lbl">Open</div></div>
                    <div class="kpi-card"><div class="val closed"><?php echo (int)$stats["closed"]; ?></div><div class="lbl">Closed</div></div>
                    <div class="kpi-card"><div class="val passed"><?php echo (int)$stats["passed"]; ?></div><div class="lbl">Passed</div></div>
                    <div class="kpi-card"><div class="val rejected"><?php echo (int)$stats["rejected"]; ?></div><div class="lbl">Rejected</div></div>
                </div>

                <div class="filter-bar">
                    <select name="session_id" onchange="window.location='?session_id='+encodeURIComponent(this.value)+'&status=<?php echo urlencode($filterStatus); ?>&result=<?php echo urlencode($filterResult); ?>&search=<?php echo urlencode($search); ?>'">
                        <option value="">All Sessions</option>
                        <?php foreach($allSessions as $s): ?>
                            <option value="<?php echo $s["id"]; ?>" <?php echo $filterSession==(int)$s["id"]?"selected":""; ?>><?php echo htmlspecialchars($s["title"]); ?> (<?php echo $s["session_type"]; ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <select name="status" onchange="window.location='?status='+encodeURIComponent(this.value)+'&session_id=<?php echo (int)$filterSession; ?>&result=<?php echo urlencode($filterResult); ?>&search=<?php echo urlencode($search); ?>'">
                        <option value="">All States</option>
                        <?php foreach($statusOpts as $st): ?><option value="<?php echo $st; ?>" <?php echo $filterStatus===$st?"selected":""; ?>><?php echo $st; ?></option><?php endforeach; ?>
                    </select>
                    <select name="result" onchange="window.location='?result='+encodeURIComponent(this.value)+'&session_id=<?php echo (int)$filterSession; ?>&status=<?php echo urlencode($filterStatus); ?>&search=<?php echo urlencode($search); ?>'">
                        <option value="">All Results</option>
                        <?php foreach($resultOpts as $r): ?><option value="<?php echo $r; ?>" <?php echo $filterResult===$r?"selected":""; ?>><?php echo $r; ?></option><?php endforeach; ?>
                    </select>
                    <input type="text" placeholder="Search voting…" value="<?php echo htmlspecialchars($search); ?>"
                            onchange="window.location='?search='+encodeURIComponent(this.value)+'&session_id=<?php echo urlencode($filterSession); ?>&status=<?php echo urlencode($filterStatus); ?>&result=<?php echo urlencode($filterResult); ?>'"/>
                </div>

                <div class="vot-list">
                <div class="vot-row vot-row-head">
                    <span>Voting / Session</span><span>Type</span><span>Status</span><span>Result</span><span>Votes</span><span>Actions</span>
                </div>
                <?php if(empty($votings)): ?>
                <div class="empty-state" style="padding:40px;"><i class="bi bi-card-checklist"></i><p>No voting records found. Create your first voting session.</p></div>
                <?php else: ?>
                <?php foreach($votings as $v): ?>
                <?php $sb=$stBadge($v["status"]); $rb=$v["result"]?$rsBadge($v["result"]):null; $tc=$v["total_eligible"]?((int)$v["total_eligible"]):0; $tcStr=$tc?" / $tc eligible":""; $vb=$vtBadge($v["vote_type"]);
                $approved=$vvModel->getApprovedCounts($v["id"]);
                $ye=$approved["yes"]; $no=$approved["no"]; $ab=$approved["abstain"]; $totVoted=$approved["total"];
                $tSc=($totVoted>0)?$totVoted:1; ?>
                <div class="vot-row">
                    <div>
                        <div class="vot-title"><?php echo htmlspecialchars($v["title"]); ?></div>
                        <div class="vot-sess">
                            <?php echo htmlspecialchars($v["session_title"] ?? ""); ?> (<?php echo $v["session_type"] ?? ""; ?>)
                            <?php if($v["opened_by_first"]): ?>&nbsp;&middot;&nbsp;by <?php echo htmlspecialchars($v["opened_by_first"]." ".$v["opened_by_last"]); ?><?php endif; ?>
                        </div>
                        <?php if($v["description"]): ?><div class="vot-desc"><?php echo htmlspecialchars(truncate($v["description"],80)); ?></div><?php endif; ?>
                    </div>
                    <div class="vt-cell">
                        <span class="badge" style="background:<?php echo $vb["c"]; ?>;color:<?php echo $vb["col"]; ?>;border:1px solid <?php echo $vb["bc"]; ?>;">
                            <?php echo htmlspecialchars($v["vote_type"] ?? "SIMPLE_MAJORITY"); ?>
                        </span>
                    </div>
                    <div><span class="badge <?php echo $v["status"]==="OPEN"?"badge-blue":"badge-gray"; ?>"><?php echo $sb["label"]; ?></span></div>
                    <div><?php if($rb): ?><span class="badge <?php echo $v["result"]==="PASSED"?"badge-green":"badge-red"; ?>"><?php echo $rb["label"]; ?></span><?php else: ?><span style="color:#5a6a80;font-size:12px;">—</span><?php endif; ?></div>
                    <div>
                        <?php if($totVoted>0): ?>
                        <div style="font-family:'Space Mono',monospace;font-size:12px;color:#8a9ab0;">
                            YES <span style="color:#3eb87c;"><?php echo $ye; ?></span> &nbsp;
                            NO  <span style="color:#f07060;"><?php echo $no; ?></span> &nbsp;
                            AB  <span style="color:#c9a84c;"><?php echo $ab; ?></span>&nbsp;<span style="color:#5a6a80;">(<?php echo $totVoted; ?><?php echo $tcStr; ?>)</span>
                        </div>
                        <div class="vote-bar">
                            <div class="vyes" style="width:<?php echo round($ye/$tSc*100); ?>%"></div>
                            <div class="vno"  style="width:<?php echo round($no/$tSc*100); ?>%"></div>
                            <div class="vabs" style="width:<?php echo round($ab/$tSc*100); ?>%"></div>
                        </div>
                        <?php else: ?>
                        <span style="font-size:12px;color:#5a6a80;">No votes cast</span>
                        <?php endif; ?>
                    </div>
                    <?php
                        // Pending votes requiring admin approval
                        $pendingRows = [];
                        try { $pendingRows = $vvModel->getPendingVotes($v["id"]); } catch (Throwable $e) {}
                    ?>
                    <?php if($pendingRows): ?>
                    <div style="display:flex;flex-direction:column;gap:4px;align-items:flex-end;">
                        <span style="font-size:11px;color:var(--gold);font-family:'Space Mono',monospace;letter-spacing:.1em;">PENDING APPROVAL</span>
                        <?php foreach($pendingRows as $pv): ?>
                        <div style="background:rgba(201,168,76,.08);border:1px solid rgba(201,168,76,.25);border-radius:6px;padding:6px 10px;display:flex;align-items:center;gap:10px;white-space:nowrap;font-size:12px;width:100%;box-sizing:border-box;">
                            <span style="color:var(--text-muted);flex:1;overflow:hidden;text-overflow:ellipsis;">
                                <?php echo htmlspecialchars(($pv["first_name"] ?? '').' '.($pv["last_name"] ?? '')); ?>
                            </span>
                            <span style="font-family:'Space Mono',monospace;font-weight:700;font-size:11px;
                                color:<?php echo ($pv["choice"]==='YES'?'var(--green-accent)':(($pv["choice"]==='NO')?'var(--accent-red)':'var(--gold)')); ?>;">
                                <?php echo $pv["choice"]; ?>
                            </span>
                            <form method="POST" action="" style="display:inline;margin:0;" onsubmit="return confirm('Approve vote for <?php echo htmlspecialchars(addslashes(($pv["first_name"]??'').' '.($pv["last_name"]??''))); ?>? This will count toward the official tally.');">
                                <input type="hidden" name="action" value="approve">
                                <input type="hidden" name="record_id" value="<?php echo $pv["id"]; ?>">
                                <input type="hidden" name="voting_id" value="<?php echo $v["id"]; ?>">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION["csrf_token"]; ?>">
                                <button type="submit" class="btn-success btn-sm" title="Approve"><i class="bi bi-check-lg"></i></button>
                            </form>
                            <form method="POST" action="" style="display:inline;margin:0;" onsubmit="return confirm('Reject vote for <?php echo htmlspecialchars(addslashes(($pv["first_name"]??'').' '.($pv["last_name"]??''))); ?>?');">
                                <input type="hidden" name="action" value="reject">
                                <input type="hidden" name="record_id" value="<?php echo $pv["id"]; ?>">
                                <input type="hidden" name="voting_id" value="<?php echo $v["id"]; ?>">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION["csrf_token"]; ?>">
                                <button type="submit" class="btn-danger btn-sm" title="Reject"><i class="bi bi-x-lg"></i></button>
                            </form>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    </div>
                    <div class="act-cell" style="display:flex;gap:6px;flex-wrap:wrap;justify-content:flex-end;">
                        <?php if($v["status"]==="OPEN"): ?>
                            <form method="POST" action="" style="display:inline">
                                <input type="hidden" name="action" value="open"><input type="hidden" name="id" value="<?php echo $v["id"]; ?>">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION["csrf_token"]; ?>">
                                <button type="submit" class="btn-success btn-sm" title="Open"><i class="bi bi-check2-circle"></i> Open</button>
                            </form>
                        <?php endif; ?>
                        <?php if($v["status"]==="OPEN"||$v["status"]==="CLOSED"): ?>
                            <form method="POST" action="" style="display:inline">
                                <input type="hidden" name="action" value="close"><input type="hidden" name="id" value="<?php echo $v["id"]; ?>">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION["csrf_token"]; ?>">
                                <button type="submit" class="btn-info btn-sm" title="Close"><i class="bi bi-stop-circle"></i> Close</button>
                            </form>
                        <?php endif; ?>
                        <button class="btn-link" onclick="openEditModal(<?php echo $v["id"];?>)"><i class="bi bi-pencil"></i></button>
                        <form method="POST" action="" style="display:inline" onsubmit="return confirm('Delete voting \'<?php echo htmlspecialchars(addslashes($v["title"])); ?>\'?');">
                            <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?php echo $v["id"]; ?>">
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
                <h2 id="modalTitle"><?php echo $editVoting?"Edit Voting":"New Voting Session"; ?></h2>
                <button class="modal-close" onclick="closeModal()"><i class="bi bi-x-lg"></i></button>
            </div>
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION["csrf_token"]; ?>">
                    <input type="hidden" name="action" id="formAction" value="<?php echo $editVoting?"edit":"create"; ?>">
                    <input type="hidden" name="id"     id="formId" value="<?php echo $editVoting?$editVoting["id"]:""; ?>">

                    <div class="form-row">
                        <div class="form-group">
                            <label>GA Session</label>
                            <select name="session_id" id="session_id" required>
                                <option value="">— Select session —</option>
                                <?php foreach($allSessions as $s): ?>
                                    <option value="<?php echo $s["id"]; ?>" <?php echo ($editVoting && $editVoting["session_id"]==$s["id"])?"selected":($filterSession==(int)$s["id"]?"selected":""); ?>>
                                        <?php echo htmlspecialchars($s["title"]); ?> (<?php echo $s["session_type"]; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" id="status">
                                <?php foreach($statusOpts as $st): ?><option value="<?php echo $st; ?>" <?php echo ($editVoting && $editVoting["status"]==$st)?"selected":""; ?>><?php echo $st; ?></option><?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Voting Title</label>
                        <input type="text" name="title" id="title" required placeholder="e.g. Election of Senate President" value="<?php echo $editVoting?htmlspecialchars($editVoting["title"]):""; ?>">
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" id="description" rows="3"><?php echo $editVoting?htmlspecialchars($editVoting["description"]??""):""; ?></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Threshold Type</label>
                            <select name="vote_type" id="vote_type">
                                <?php foreach($voteTypeOpts as $vt): ?><option value="<?php echo $vt; ?>" <?php echo ($editVoting && $editVoting["vote_type"]==$vt)?"selected":""; ?>><?php echo $vt; ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Eligible Voters</label>
                            <input type="number" name="total_eligible" id="total_eligible" min="0" placeholder="0" value="<?php echo $editVoting?(int)($editVoting["total_eligible"]??0):""; ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Opened By</label>
                        <select name="opened_by" id="opened_by">
                            <option value="">— Select —</option>
                            <?php foreach($allUsers as $u): ?>
                                <option value="<?php echo $u["id"]; ?>" <?php echo ($editVoting && $editVoting["opened_by"]==$u["id"])?"selected":""; ?>>
                                    <?php echo htmlspecialchars($u["first_name"]." ".$u["last_name"]." (".$u["role"].")"); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-ghost" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn-primary"><i class="bi bi-<?php echo $editVoting?'check':'plus'; ?>-lg"></i><?php echo $editVoting?"Update":"Create Voting"; ?></button>
                </div>
            </form>
        </div>
    </div>
    <script src="../assets/js/sidebar.js"></script>
    <script src="../assets/js/loader-service.js"></script>
    <script>
        const fu=null; // no file upload in this page — reserved for future use

        function openCreateModal(){
            closeModal(); setTimeout(()=>{
                document.getElementById("sessionModal").classList.add("open");
                document.getElementById("formAction").value="create";
                document.getElementById("formId").value="";
                document.getElementById("modalTitle").textContent="New Voting Session";
                document.getElementById("title").value="";
                document.getElementById("description").value="";
                document.getElementById("vote_type").value="SIMPLE_MAJORITY";
                document.getElementById("status").value="OPEN";
                document.getElementById("total_eligible").value="";
                document.getElementById("session_id").value="<?php echo (int)$filterSession; ?>";
                document.getElementById("opened_by").value="";
            },50);
        }
        function openEditModal(id){
            document.getElementById("sessionModal").classList.add("open");
            document.getElementById("formAction").value="edit";
            document.getElementById("formId").value=id;
            const d=window._editData?.[id]; if(d){
                document.getElementById("session_id").value=d.session_id||"";
                document.getElementById("title").value=d.title||"";
                document.getElementById("description").value=d.description||"";
                document.getElementById("vote_type").value=d.vote_type||"SIMPLE_MAJORITY";
                document.getElementById("status").value=d.status||"OPEN";
                document.getElementById("total_eligible").value=d.total_eligible||"";
                document.getElementById("opened_by").value=d.opened_by||"";
            }
            document.getElementById("modalTitle").textContent="Edit Voting";
        }
        function closeModal(){ document.getElementById("sessionModal").classList.remove("open"); }
        document.getElementById("sessionModal").addEventListener("click",e=>{ if(e.target===this)closeModal(); });
        document.addEventListener("keydown",e=>{ if(e.key==="Escape")closeModal(); });
        window._editData={<?php if($editVoting): ?><?php echo $editVoting["id"];?>:<?php echo json_encode($editVoting,JSON_HEX_APOS|JSON_HEX_QUOT); ?><?php endif; ?>};
    </script>
</body>
</html>
