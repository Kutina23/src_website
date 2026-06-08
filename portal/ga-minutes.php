<?php
// GA Minutes Management Admin — CRUD for ga_minutes table
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Expires: Thu, 01 Jan 1970 00:00:00 GMT");
require_once "../config/database.php";
require_once "../config/functions.php";
require_once "../models/GaMinutes.php";
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

$pageTitle  = "GA Minutes";
$success    = $_SESSION["success"] ?? null;
$errors     = $_SESSION["errors"]   ?? [];
unset($_SESSION["success"], $_SESSION["errors"]);

$mmModel    = new GaMinutes(db());
$gsModel    = new GaSessions(db());
$allSessions= $gsModel->getAll(["status"=>"COMPLETED"]);
$statusOpts = $mmModel->getStatusOptions();
$stats      = $mmModel->getStats();
$allUsers   = $gsModel->getAllUsersForAttendance();

$filters = [];
$filterSession = $_GET["session_id"] ?? "";
$filterStatus  = $_GET["status"]     ?? "";
$search        = trim($_GET["search"] ?? "");
if ($filterSession) $filters["session_id"] = (int)$filterSession;
if ($filterStatus)  $filters["status"]      = $filterStatus;
if ($search)        $filters["search"]      = $search;
$minutes = $mmModel->getAll($filters);

$editMinute = null;
if (($_GET["action"] ?? "") === "edit") {
    $editMinute = $mmModel->getById((int)($_GET["id"] ?? 0));
}

$_SESSION["csrf_token"] = $_SESSION["csrf_token"] ?? bin2hex(random_bytes(32));

$statusBadge = fn($s) => match($s) {
    "DRAFT"    =>["class"=>"badge-inprogress","label"=>"DRAFT"],
    "PUBLISHED"=>["class"=>"badge-scheduled","label"=>"PUBLISHED"],
    "RATIFIED" =>["class"=>"badge-completed","label"=>"RATIFIED"],
    "ARCHIVED" =>["class"=>"badge-cancelled","label"=>"ARCHIVED"],
};

// ── POST Handlers ──────────────────────────────────────────
if ($_SERVER["REQUEST_METHOD"]==="POST") {
    $action = $_POST["action"] ?? "";
    $csrf   = $_POST["csrf_token"] ?? "";
    if (!hash_equals($_SESSION["csrf_token"] ?? "", $csrf)) {
        $_SESSION["errors"]=["Invalid CSRF token."]; header("Location: ga-minutes.php"); exit;
    }

        if ($action==="create" || $action==="edit") {
        $filePath   = null; $origName=""; $fileSize=0; $mime="application/pdf";
        if (isset($_FILES["minutes_file"]) && $_FILES["minutes_file"]["error"]===UPLOAD_ERR_OK) {
            $uploadDir = "../uploads/ga_minutes/";
            if (!is_dir($uploadDir)) mkdir($uploadDir,0755,true);
            $ext = strtolower(pathinfo($_FILES["minutes_file"]["name"], PATHINFO_EXTENSION));
            $allowed=["pdf","doc","docx","txt"];
            if (!in_array($ext,$allowed)) { $_SESSION["errors"]=["Only PDF, DOC, DOCX, TXT allowed."]; header("Location: ga-minutes.php"); exit; }
            $origName = $_FILES["minutes_file"]["name"];
            $fname   = "ga_minutes_".time()."_".uniqid().".".$ext;
            $filePath= $uploadDir.$fname;
            $fileSize= (int)$_FILES["minutes_file"]["size"];
            if (!move_uploaded_file($_FILES["minutes_file"]["tmp_name"],$filePath)) {
                $_SESSION["errors"]=["File upload failed."]; header("Location: ga-minutes.php"); exit;
            }
        }

        $data = [
            "session_id"    =>(int)($_POST["session_id"] ?? 0),
            "meeting_title" =>trim($_POST["meeting_title"] ?? ""),
            "description"   =>trim($_POST["description"] ?? ""),
            "status"        =>$_POST["status"] ?? "DRAFT",
            "uploaded_by"   =>!empty($_POST["uploaded_by"])?(int)$_POST["uploaded_by"]:null,
        ];
        if ($filePath) { $data["file_path"]=$filePath; $data["original_name"]=$origName; $data["file_size"]=$fileSize; $data["mime_type"]=$mime; }

        if (empty($data["meeting_title"])) { $_SESSION["errors"]=["Meeting title is required."]; header("Location: ga-minutes.php"); exit; }
        if ($action==="create") { $mmModel->create($data); $_SESSION["success"]="Minutes added."; }
        else { $mmModel->update((int)$_POST["id"],$data); $_SESSION["success"]="Minutes updated."; }
        header("Location: ga-minutes.php"); exit;
    }
    elseif ($action==="delete") {
        $mmModel->delete((int)$_POST["id"]);
        $_SESSION["success"]="Minutes deleted."; header("Location: ga-minutes.php"); exit;
    }
    // publish
    elseif ($action==="publish") {
        $mmModel->update((int)$_POST["id"],["status"=>"PUBLISHED"]);
        $_SESSION["success"]="Minutes published."; header("Location: ga-minutes.php"); exit;
    }
    // ratify
    elseif ($action==="ratify") {
        $mmModel->update((int)$_POST["id"],["status"=>"RATIFIED","uploaded_by"=>$_SESSION["user_id"]]);
        $_SESSION["success"]="Minutes ratified."; header("Location: ga-minutes.php"); exit;
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
        .btn-success{ background:#1a4a2a; color:#3eb87c; padding:8px 16px; border:1px solid rgba(55,180,120,.3); border-radius:6px; font-size:12px; cursor:pointer; }
        .btn-success:hover { background:#1f5533; }

        .kpi-row { display:grid; grid-template-columns:repeat(auto-fill,minmax(120px,1fr)); gap:12px; margin-bottom:28px; }
        .kpi-card { background:rgba(14,20,40,.9); border:1px solid rgba(201,168,76,.12); border-radius:10px; padding:16px; text-align:center; }
        .kpi-card .val { font-family:'Space Mono',monospace; font-size:1.6rem; font-weight:700; color:#c9a84c; }
        .kpi-card .lbl { font-size:10px; color:#5a6a80; text-transform:uppercase; letter-spacing:.08em; margin-top:4px; font-family:'Space Mono',monospace; }

        .filter-bar { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:24px; background:rgba(14,20,40,.8); border:1px solid rgba(201,168,76,.08); border-radius:10px; padding:16px 20px; }
        .filter-bar input,.filter-bar select { background:rgba(6,12,26,.8); color:#e0e8f0; border:1px solid rgba(160,180,208,.18); border-radius:6px; padding:8px 12px; font-family:'Outfit',sans-serif; font-size:13px; }

        /* List rows */
        .min-list { display:flex; flex-direction:column; gap:0; background:rgba(14,20,40,.85); border:1px solid rgba(201,168,76,.1); border-radius:12px; overflow:hidden; }
        .min-row  { display:grid; grid-template-columns:1fr 110px 100px 90px 1fr; align-items:center; gap:10px; padding:14px 18px; border-bottom:1px solid rgba(255,255,255,.04); }
        .min-row:last-child { border-bottom:none; }
        .min-row:hover { background:rgba(201,168,76,.04); }
        .min-row-head { font-family:'Space Mono',monospace; font-size:10px; letter-spacing:.1em; text-transform:uppercase; color:#c9a84c; background:rgba(201,168,76,.04); }
        .min-title  { font-weight:600; color:#fff; font-size:14px; }
        .min-sess   { font-size:12px; color:#7a8fa0; margin-top:2px; }
        .min-author { font-size:12px; color:#8a9ab0; }

        .badge { display:inline-flex; align-items:center; gap:5px; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:600; font-family:'Space Mono',monospace; }
        .badge-inprogress { background:rgba(201,168,76,.15); color:#c9a84c; border:1px solid rgba(201,168,76,.3); }
        .badge-scheduled  { background:rgba(74,144,226,.15); color:#6ab0ff; border:1px solid rgba(74,144,226,.3); }
        .badge-completed  { background:rgba(55,180,120,.15); color:#3eb87c; border:1px solid rgba(55,180,120,.3); }
        .badge-cancelled  { background:rgba(160,90,120,.15); color:#c06090; border:1px solid rgba(160,90,120,.3); }

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
        .file-upload { border:2px dashed rgba(160,180,208,.25); border-radius:10px; padding:20px; text-align:center; cursor:pointer; transition:border-color .2s; }
        .file-upload:hover { border-color:#c9a84c; }
        .file-upload.has-file { border-color:#3eb87c; background:rgba(55,180,120,.04); }
        .file-upload i   { font-size:2rem; color:#5a6a80; }
        .file-upload p   { margin:8px 0 0; font-size:13px; color:#8a9ab0; }
        .file-upload .fname { font-size:12px; color:#3eb87c; margin-top:6px; word-break:break-all; }
        .modal-actions { display:flex; gap:10px; justify-content:flex-end; padding:0 28px 28px; }
        .modal-body   { padding:28px; }

        @media(max-width:640px){
            .min-row { grid-template-columns:1fr; gap:4px; }
            .min-row:nth-child(5) { grid-template-columns:1fr 1fr; }
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
                        <h1><i class="bi bi-file-earmark-pdf" style="margin-right:12px;vertical-align:middle;"></i>GA Minutes</h1>
                        <p>Upload, publish and ratify official minutes from every GA session.</p>
                    </div>
                    <button class="btn-primary" onclick="openCreateModal()"><i class="bi bi-upload"></i> Upload Minutes</button>
                </div>

                <div class="kpi-row">
                    <div class="kpi-card"><div class="val"><?php echo (int)$stats["total"]; ?></div><div class="lbl">Total</div></div>
                    <div class="kpi-card"><div class="val"><?php echo (int)$stats["draft"]; ?></div><div class="lbl">Draft</div></div>
                    <div class="kpi-card"><div class="val"><?php echo (int)$stats["published"]; ?></div><div class="lbl">Published</div></div>
                    <div class="kpi-card"><div class="val" style="color:#3eb87c;"><?php echo (int)$stats["ratified"]; ?></div><div class="lbl">Ratified</div></div>
                </div>

                <div class="filter-bar">
                    <select name="session_id" onchange="window.location='?session_id='+encodeURIComponent(this.value)+'&status=<?php echo urlencode($filterStatus); ?>&search=<?php echo urlencode($search); ?>'">
                        <option value="">All Sessions</option>
                        <?php foreach($allSessions as $s): ?>
                            <option value="<?php echo $s["id"]; ?>" <?php echo ($filterSession==(int)$s["id"])?"selected":""; ?>><?php echo htmlspecialchars($s["title"]); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="status" onchange="window.location='?status='+encodeURIComponent(this.value)+'&session_id=<?php echo (int)$filterSession; ?>&search=<?php echo urlencode($search); ?>'">
                        <option value="">All Statuses</option>
                        <?php foreach($statusOpts as $st): ?><option value="<?php echo $st; ?>" <?php echo $filterStatus===$st?"selected":""; ?>><?php echo $st; ?></option><?php endforeach; ?>
                    </select>
                    <input type="text" placeholder="Search meetings…" value="<?php echo htmlspecialchars($search); ?>"
                            onchange="window.location='?search='+encodeURIComponent(this.value)+'&session_id=<?php echo urlencode($filterSession); ?>&status=<?php echo urlencode($filterStatus); ?>'"/>
                </div>

                <div class="min-list">
                <div class="min-row min-row-head">
                    <span>Meeting / File</span><span>Status</span><span>Session</span><span>Uploaded By</span><span>Actions</span>
                </div>
                <?php if(empty($minutes)): ?>
                <div class="empty-state" style="padding:40px;"><i class="bi bi-file-earmark-pdf"></i><p>No minutes records found. Upload your first minutes to get started.</p></div>
                <?php else: ?>
                <?php foreach($minutes as $m): ?>
                <?php $sb=$statusBadge($m["status"]); $authorName=$m["uploaded_by_first"]?$m["uploaded_by_first"]." ".$m["uploaded_by_last"]:"—"; $uploadDate=$m["uploaded_at"]?date("d M Y",strtotime($m["uploaded_at"])):"—"; ?>
                <div class="min-row">
                    <div>
                        <div class="min-title">
                            <?php if(!empty($m["file_path"])): ?>
                                <a href="../<?php echo htmlspecialchars($m["file_path"]); ?>" target="_blank" style="color:#c9a84c;"><i class="bi bi-file-earmark-pdf" style="margin-right:6px;"></i></a>
                            <?php else: ?><i class="bi bi-file-earmark" style="margin-right:6px;color:#5a6a80;"></i><?php endif; ?>
                            <?php echo htmlspecialchars($m["meeting_title"]); ?>
                            <?php if($m["original_name"]): ?>
                                <span style="font-size:11px;color:#5a6a80;font-family:'Space Mono',monospace;">· <?php echo htmlspecialchars(truncate($m["original_name"],30)); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="min-sess"><?php echo $uploadDate; ?> — <?php echo htmlspecialchars($m["session_title"]); ?></div>
                        <?php if($m["description"]): ?><div class="min-author"><?php echo htmlspecialchars(truncate($m["description"],80)); ?></div><?php endif; ?>
                    </div>
                    <div><span class="badge <?php echo $sb["class"]; ?>"><?php echo $sb["label"]; ?></span></div>
                    <div style="font-size:12px;color:#7a8fa0;"><?php echo htmlspecialchars(truncate($m["session_title"],20)); ?></div>
                    <div style="font-size:12px;color:#8a9ab0;"><?php echo htmlspecialchars($authorName); ?></div>
                    <div style="display:flex;gap:6px;flex-wrap:wrap;justify-content:flex-end;">
                        <a href="../<?php echo htmlspecialchars($m["file_path"]); ?>" target="_blank" style="font-size:12px;color:#6ab0ff;text-decoration:none;" class="btn-link"><i class="bi bi-download"></i> Download</a>
                        <?php if($m["status"]==="DRAFT"): ?>
                            <form method="POST" action="" style="display:inline">
                                <input type="hidden" name="action" value="publish"><input type="hidden" name="id" value="<?php echo $m["id"]; ?>">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION["csrf_token"]; ?>">
                                <button type="submit" class="btn-success btn-sm" title="Publish">Publish</button>
                            </form>
                        <?php endif; ?>
                        <?php if($m["status"]==="PUBLISHED"||$m["status"]==="DRAFT"): ?>
                            <form method="POST" action="" style="display:inline">
                                <input type="hidden" name="action" value="ratify"><input type="hidden" name="id" value="<?php echo $m["id"]; ?>">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION["csrf_token"]; ?>">
                                <button type="submit" class="btn-primary" style="padding:5px 10px;font-size:11px;background:#3eb87c;color:#fff;" title="Ratify"><i class="bi bi-check2-circle"></i></button>
                            </form>
                        <?php endif; ?>
                        <button class="btn-link" onclick="openEditModal(<?php echo $m["id"];?>)"><i class="bi bi-pencil"></i></button>
                        <form method="POST" action="" style="display:inline" onsubmit="return confirm('Delete these minutes?');">
                            <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?php echo $m["id"]; ?>">
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
                <h2 id="modalTitle"><?php echo $editMinute?"Edit Minutes":"Upload Minutes"; ?></h2>
                <button class="modal-close" onclick="closeModal()"><i class="bi bi-x-lg"></i></button>
            </div>
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION["csrf_token"]; ?>">
                    <input type="hidden" name="action" id="formAction" value="<?php echo $editMinute?"edit":"create"; ?>">
                    <input type="hidden" name="id"     id="formId" value="<?php echo $editMinute?$editMinute["id"]:""; ?>">

                    <div class="form-row">
                        <div class="form-group">
                            <label>GA Session</label>
                            <select name="session_id" id="session_id" required>
                                <option value="">— Select session —</option>
                                <?php foreach($allSessions as $s): ?>
                                    <option value="<?php echo $s["id"]; ?>" <?php echo ($editMinute && $editMinute["session_id"]==$s["id"])?"selected":($filterSession==(int)$s["id"]?"selected":""); ?>>
                                        <?php echo htmlspecialchars($s["title"]); ?> (<?php echo $s["session_type"]; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" id="status">
                                <?php foreach($statusOpts as $st): ?><option value="<?php echo $st; ?>" <?php echo ($editMinute && $editMinute["status"]==$st)?"selected":""; ?>><?php echo $st; ?></option><?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Meeting Title</label>
                        <input type="text" name="meeting_title" id="meeting_title" required
                               placeholder="e.g. 22nd AGM Minutes" value="<?php echo $editMinute?htmlspecialchars($editMinute["meeting_title"]):""; ?>">
                    </div>

                    <div class="form-group">
                        <label>Description / Notes</label>
                        <textarea name="description" id="description" rows="3"><?php echo $editMinute?htmlspecialchars($editMinute["description"]??""):""; ?></textarea>
                    </div>

                    <?php if($editMinute && !empty($editMinute["file_path"])): ?>
                    <div class="form-group">
                        <label>Current File</label>
                        <div style="background:rgba(6,12,26,.9);border:1px solid rgba(160,180,208,.18);border-radius:8px;padding:12px;font-size:13px;display:flex;align-items:center;gap:10px;">
                            <i class="bi bi-file-earmark-pdf" style="color:#c9a84c;"></i>
                            <a href="../<?php echo htmlspecialchars($editMinute["file_path"]); ?>" target="_blank" style="color:#6ab0ff;">><?php echo htmlspecialchars($editMinute["original_name"]); ?></a>
                            <span style="color:#5a6a80;font-family:'Space Mono',monospace;font-size:11px;">(<?php echo round($editMinute["file_size"]/1024); ?> KB)</span>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label>
                            <?php echo $editMinute?"Replace File (optional)":"Upload PDF / Document"; ?>
                        </label>
                        <label class="file-upload" id="fileUpload">
                            <i class="bi bi-upload"></i>
                            <p><?php echo $editMinute?"Click to replace file":"Click or drag file here"; ?></p>
                            <div id="fileName" class="fname"></div>
                            <input type="file" name="minutes_file" id="minutes_file" accept=".pdf,.doc,.docx,.txt" style="display:none"
                                   onchange="document.getElementById('fileName').textContent=this.files[0]?.name||'';">
                        </label>
                    </div>

                    <div class="form-group">
                        <label>Uploaded By</label>
                        <select name="uploaded_by">
                            <option value="">— Select uploader —</option>
                            <?php foreach($allUsers as $u): ?>
                                <option value="<?php echo $u["id"]; ?>" <?php echo ($editMinute && $editMinute["uploaded_by"]==$u["id"])?"selected":""; ?>>
                                    <?php echo htmlspecialchars($u["first_name"]." ".$u["last_name"]." (".$u["role"].")"); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-ghost" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn-primary"><i class="bi bi-<?php echo $editMinute?'check':'upload'; ?>-lg"></i><?php echo $editMinute?"Update":"Upload Minutes"; ?></button>
                </div>
            </form>
        </div>
    </div>
    <script src="../assets/js/sidebar.js"></script>
    <script src="../assets/js/loader-service.js"></script>
    <script>
        // File upload input helpers
        const fu = document.getElementById("fileUpload");
        if (fu) {
            fu.addEventListener("click", () => document.getElementById("minutes_file")?.click());
            fu.addEventListener("dragover", e => { e.preventDefault(); fu.style.borderColor = "#c9a84c"; });
            fu.addEventListener("dragleave", () => { fu.style.borderColor = ""; });
            fu.addEventListener("drop", e => {
                e.preventDefault();
                fu.style.borderColor = "";
                const dt = e.dataTransfer;
                if (dt?.files?.length) {
                    document.getElementById("minutes_file").files = dt.files;
                    document.getElementById("fileName").textContent = dt.files[0].name;
                    fu.classList.add("has-file");
                }
            });
        }

        // Modal helpers
        function openCreateModal() {
            closeModal();
            setTimeout(() => {
                document.getElementById("sessionModal").classList.add("open");
                document.getElementById("formAction").value = "create";
                document.getElementById("formId").value = "";
                document.getElementById("modalTitle").textContent = "Upload Minutes";
                document.getElementById("meeting_title").value = "";
                document.getElementById("description").value = "";
                document.getElementById("session_id").value = "<?php echo (int)$filterSession; ?>";
                document.getElementById("fileName").textContent = "";
                document.getElementById("fileUpload").classList.remove("has-file");
            }, 50);
        }

        function openEditModal(id) {
            document.getElementById("sessionModal").classList.add("open");
            document.getElementById("formAction").value = "edit";
            document.getElementById("formId").value = id;
            const d = window._editData?.[id];
            if (d) {
                document.getElementById("session_id").value    = d.session_id      || "";
                document.getElementById("meeting_title").value = d.meeting_title   || "";
                document.getElementById("description").value   = d.description     || "";
                document.getElementById("status").value        = d.status          || "DRAFT";
            }
            document.getElementById("modalTitle").textContent = "Edit Minutes";
        }

        function closeModal() {
            document.getElementById("sessionModal").classList.remove("open");
        }

        // Close modal on outside click
        document.getElementById("sessionModal").addEventListener("click", e => {
            if (e.target === this) closeModal();
        });

        // Close modal on Escape key
        document.addEventListener("keydown", e => {
            if (e.key === "Escape") closeModal();
        });

        // Seed edit data (only when editing an existing record)
        window._editData = {
            <?php if ($editMinute): ?>
            <?php echo $editMinute["id"]; ?>: <?php echo json_encode($editMinute, JSON_HEX_APOS | JSON_HEX_QUOT); ?>
            <?php endif; ?>
        };
    </script>
</body>
</html>
