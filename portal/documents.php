<?php
session_start();
require_once "../config/database.php";
require_once "../config/functions.php";
require_once "../models/DocumentRequests.php";

if (!isLogged()) {
    header("Location: login.php");
    exit;
}

$currentRole = currentRole();
$currentUser = currentUser();

if ($currentRole !== "PRO") {
    header("Location: index.php");
    exit;
}

$pageTitle   = "Document Request Management";
$success     = $_SESSION["success"] ?? null;
unset($_SESSION["success"]);

$model      = new DocumentRequests(db());
$statusOpts = ["PENDING", "PROCESSING", "READY", "COLLECTED", "REJECTED"];

$filter      = strtoupper($_GET["filter"] ?? "");
$filter      = in_array($filter, $statusOpts) ? $filter : "ALL";
$typeFilter  = trim($_GET["doc_type"] ?? "");
$searchQuery = trim($_GET["search"] ?? "");

$filters = [];
if ($filter !== "ALL")       $filters["status"]        = $filter;
if ($typeFilter !== "")      $filters["document_type"] = $typeFilter;
if ($searchQuery !== "")     $filters["search"]        = $searchQuery;

$requests   = $model->getAll($filters);
$docTypes   = $model->getDocumentTypes();

$total           = $model->countAll();
$totalPending    = $model->countPending();
$totalProcessing = $model->countProcessing();
$totalReady      = $model->countReady();

// ── POST handlers ───────────────────────────────────────
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    if ($action === "update_status") {
        $id     = (int)($_POST["doc_request_id"] ?? 0);
        $status = strtoupper(trim($_POST["status"] ?? ""));
        $remarks = trim($_POST["remarks"] ?? "");
        if ($id > 0 && in_array($status, $statusOpts)) {
            $model->updateStatus($id, $status, $remarks ?: null);
            logActivity("doc_request_status_updated", $_SESSION["user_id"], ["doc_request_id" => $id, "new_status" => $status]);
            $_SESSION["success"] = "Status updated to " . ucwords(strtolower(str_replace("_", " ", $status)));
        }
        header("Location: documents.php?" . http_build_query($_GET));
        exit;
    }

    if ($action === "process") {
        $id = (int)($_POST["doc_request_id"] ?? 0);
        if ($id > 0) {
            $model->processRequest($id, $_SESSION["user_id"]);
            logActivity("doc_request_processing_started", $_SESSION["user_id"], ["doc_request_id" => $id]);
            $_SESSION["success"] = "Marked as Processing";
        }
        header("Location: documents.php?" . http_build_query($_GET));
        exit;
    }

    if ($action === "mark_ready") {
        $id = (int)($_POST["doc_request_id"] ?? 0);
        if ($id > 0) {
            $model->markReady($id);
            logActivity("doc_request_ready", $_SESSION["user_id"], ["doc_request_id" => $id]);
            $_SESSION["success"] = "Marked as Ready for Collection";
        }
        header("Location: documents.php?" . http_build_query($_GET));
        exit;
    }

    if ($action === "mark_collected") {
        $id = (int)($_POST["doc_request_id"] ?? 0);
        if ($id > 0) {
            $model->markCollected($id);
            logActivity("doc_request_collected", $_SESSION["user_id"], ["doc_request_id" => $id]);
            $_SESSION["success"] = "Marked as Collected";
        }
        header("Location: documents.php?" . http_build_query($_GET));
        exit;
    }

    if ($action === "reject") {
        $id     = (int)($_POST["doc_request_id"] ?? 0);
        $reason = trim($_POST["rejection_reason"] ?? "");
        if ($id > 0 && $reason !== "") {
            $model->rejectRequest($id, $reason);
            logActivity("doc_request_rejected", $_SESSION["user_id"], ["doc_request_id" => $id, "reason" => $reason]);
            $_SESSION["success"] = "Document request rejected";
        }
        header("Location: documents.php?" . http_build_query($_GET));
        exit;
    }

    if ($action === "block_request") {
        $id     = (int)($_POST["doc_request_id"] ?? 0);
        $reason = trim($_POST["block_reason"] ?? "");
        if ($id > 0 && $reason !== "") {
            $model->blockRequest($id, $reason);
            logActivity("doc_request_blocked", $_SESSION["user_id"], ["doc_request_id" => $id]);
            $_SESSION["success"] = "Document request blocked";
        }
        header("Location: documents.php?" . http_build_query($_GET));
        exit;
    }

    if ($action === "unblock_request") {
        $id = (int)($_POST["doc_request_id"] ?? 0);
        if ($id > 0) {
            $model->unblockRequest($id);
            logActivity("doc_request_unblocked", $_SESSION["user_id"], ["doc_request_id" => $id]);
            $_SESSION["success"] = "Document request unblocked";
        }
        header("Location: documents.php?" . http_build_query($_GET));
        exit;
    }

    if ($action === "delete_request") {
        $id = (int)($_POST["doc_request_id"] ?? 0);
        if ($id > 0) {
            $model->deleteRequest($id);
            logActivity("doc_request_deleted", $_SESSION["user_id"], ["doc_request_id" => $id]);
            $_SESSION["success"] = "Document request deleted permanently";
        }
        header("Location: documents.php?" . http_build_query($_GET));
        exit;
    }
}

$reqMap = [];
foreach ($requests as $r) {
    $reqMap[(int)$r["id"]] = $r;
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
</head>
<body>
    <div class="dashboard-layout">
        <div class="mobile-overlay" id="mobileOverlay"></div>
        <aside class="sidebar" id="sidebar">
            <button class="sidebar-toggle" id="sidebarToggle"><i class="bi bi-chevron-left"></i></button>
            <div class="sidebar-header">
                <div class="sidebar-logo">SRC</div>
                <span class="sidebar-title">DHLTU Admin</span>
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
                <h1 class="header-title">Document Requests</h1>
                <div class="header-actions"><a href="../logout.php" class="header-btn"><i class="bi bi-box-arrow-right"></i></a></div>
            </header>
            <main class="content-body">
                <div class="dashboard-container">

                    <div class="dashboard-header-section">
                        <h2 class="dashboard-title">Document Request Desk</h2>
                        <p class="dashboard-subtitle">Through-stages processing for student document requests — view, process, approve or reject</p>
                    </div>

                    <?php if ($success): ?>
                        <div style="background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.2);border-radius:8px;padding:14px 18px;margin-bottom:20px;color:#22c55e;">
                            <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                        </div>
                    <?php endif; ?>

                    <!-- Pipeline stats -->
                    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:10px;margin-bottom:22px;">
                        <a href="?filter=ALL&doc_type=<?php echo urlencode($typeFilter); ?>&search=<?php echo urlencode($searchQuery); ?>"
                           style="text-align:center;padding:14px 10px;border-radius:10px;border:1px solid rgba(201,168,76,0.18);background:rgba(201,168,76,0.04);text-decoration:none;color:inherit;display:block;<?php echo ($filter==='ALL')?'border-color:var(--gold);background:rgba(201,168,76,0.11);':''; ?>">
                            <div style="font-size:20px;font-weight:700;color:var(--gold);"><?php echo $total; ?></div>
                            <div style="font-size:10px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;margin-top:2px;">Total</div>
                        </a>
                        <?php $ds = [["PENDING",$totalPending],["PROCESSING",$totalProcessing],["READY",$totalReady ]]; ?>
                        <?php foreach ($ds as [$s,$cnt]): ?>
                        <a href="?filter=<?php echo $s; ?>&doc_type=<?php echo urlencode($typeFilter); ?>&search=<?php echo urlencode($searchQuery); ?>"
                           style="text-align:center;padding:14px 10px;border-radius:10px;border:1px solid rgba(201,168,76,0.18);background:rgba(201,168,76,0.04);text-decoration:none;color:inherit;display:block;<?php echo ($filter===$s)?'border-color:var(--gold);background:rgba(201,168,76,0.11);':''; ?>">
                            <div style="font-size:20px;font-weight:700;color:var(--gold);"><?php echo $cnt; ?></div>
                            <div style="font-size:10px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;margin-top:2px;">
                                <?php echo $s==='PENDING'?'Pending':($s==='PROCESSING'?'Processing':'Ready'); ?>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>

                    <!-- Filters bar -->
                    <div style="background:var(--card-bg);border:1px solid rgba(138,155,184,0.1);border-radius:12px;padding:18px;margin-bottom:18px;">
                        <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
                            <div class="form-group" style="flex:1;min-width:180px;margin:0;">
                                <label class="form-label">Search</label>
                                <input type="text" name="search" class="form-input" placeholder="Ref #, student name, student ID, type…" value="<?php echo htmlspecialchars($searchQuery); ?>">
                            </div>
                            <div class="form-group" style="margin:0;min-width:180px;">
                                <label class="form-label">Document Type</label>
                                <select name="doc_type" class="form-input">
                                    <option value="">All types</option>
                                    <?php foreach ($docTypes as $dt): ?>
                                        <option value="<?php echo htmlspecialchars($dt); ?>" <?php echo $typeFilter === $dt ? "selected" : ""; ?>><?php echo htmlspecialchars($dt); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div style="display:flex;gap:8px;margin-bottom:0;">
                                <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Filter</button>
                                <a href="?filter=ALL" class="btn btn-outline">Reset</a>
                            </div>
                        </form>
                    </div>

                    <!-- Status tabs -->
                    <div style="display:flex;gap:6px;margin-bottom:18px;flex-wrap:wrap;">
                        <?php $allOpts = ["ALL"=>"All","PENDING"=>"Pending","PROCESSING"=>"Processing","READY"=>"Ready","COLLECTED"=>"Collected","REJECTED"=>"Rejected"]; ?>
                        <?php foreach ($allOpts as $t=>$label): ?>
                            <a href="?filter=<?php echo $t; ?>&doc_type=<?php echo urlencode($typeFilter); ?>&search=<?php echo urlencode($searchQuery); ?>"
                               class="btn <?php echo ($filter===$t?'btn-primary':'btn-outline'); ?>" style="padding:5px 13px;font-size:13px;">
                                <?php echo $label; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>

                    <!-- Table -->
                    <div class="table-container">
                        <?php if (empty($requests)): ?>
                            <div class="empty-state">
                                <div class="empty-icon"><i class="bi bi-file-earmark-plus"></i></div>
                                <h3 class="empty-title">No requests found</h3>
                                <p class="empty-text">
                                    <?php if ($filter!=="ALL" || $typeFilter || $searchQuery): ?>
                                        No requests match the current filters.
                                    <?php else: ?>
                                        No document requests have been submitted yet.
                                    <?php endif; ?>
                                </p>
                            </div>
                        <?php else: ?>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Ref #</th>
                                        <th>Student</th>
                                        <th>Document Type</th>
                                        <th>Purpose</th>
                                        <th>Status</th>
                                        <th>Requested</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($requests as $idx => $r): ?>
                                    <?php
                                            $st     = strtoupper($r["status"]);
                                        if      ($st === "READY"      || $st === "COLLECTED") $stCls   = "badge-active";
                                        elseif  ($st === "REJECTED")                          $stCls   = "badge-inactive";
                                        else                                                  $stCls   = "";

                                        if      ($st === "READY"      || $st === "COLLECTED") $stColor = "var(--green-accent)";
                                        elseif  ($st === "REJECTED")                          $stColor = "var(--accent-red)";
                                        else                                                  $stColor = "var(--gold)";
                                        ?>
                                    <tr>
                                        <td><?php echo $idx+1; ?></td>
                                        <td>
                                            <code style="font-size:11px;background:rgba(201,168,76,0.1);padding:3px 8px;border-radius:5px;color:var(--gold);white-space:nowrap;">
                                                <?php echo htmlspecialchars($r["request_token"]); ?>
                                            </code>
                                        </td>
                                        <td>
                                            <div class="user-name">
                                                <?php echo htmlspecialchars(trim((($r["first_name"]??"") . " " . ($r["last_name"]??"")) ?: ($r["guest_full_name"] ?? "—"))); ?>
                                            </div>
<div class="user-email">
                                                 <?php echo htmlspecialchars($r["student_id"] ?? $r["guest_student_id"] ?? $r["guest_email"] ?? "—"); ?>
                                             </div>
                                        </td>
                                        <td><span class="badge badge-role"><?php echo htmlspecialchars($r["document_type"]); ?></span></td>
                                        <td><span class="user-email"><?php echo htmlspecialchars(truncate($r["purpose"] ?? "", 55)); ?></span></td>
                                        <td><span class="badge <?php echo $stCls; ?>"><?php echo str_replace("_"," ",$st); ?></span></td>
                                        <td><?php echo timeAgo($r["requested_at"]); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline" style="padding:4px 9px;" onclick="openDetail(<?php echo (int)$r['id']; ?>)" title="View / Update">
                                                <i class="bi bi-eye-fill"></i>
                                            </button>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this request permanently?');">
                                                <input type="hidden" name="action" value="delete_request">
                                                <input type="hidden" name="doc_request_id" value="<?php echo (int)$r['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline" style="padding:4px 9px;color:var(--accent-red);border-color:var(--accent-red);" title="Delete">
                                                    <i class="bi bi-trash-fill"></i>
                                                </button>
                                            </form>
                                            <?php if (!empty($r["is_blocked"])): ?>
                                                <button class="btn btn-sm btn-outline" style="padding:4px 9px;color:var(--gold);border-color:var(--gold);" onclick="openUnblockModal(<?php echo (int)$r['id']; ?>)" title="Unblock">
                                                    <i class="bi bi-unlock-fill"></i>
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-outline" style="padding:4px 9px;color:#ef4444;border-color:#ef4444;" onclick="openBlockModal(<?php echo (int)$r['id']; ?>)" title="Block">
                                                    <i class="bi bi-ban-fill"></i>
                                                </button>
                                            <?php endif; ?>
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

    <!-- Detail Modal -->
    <div id="detailModal"
         style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:1100;align-items:flex-start;justify-content:center;overflow-y:auto;padding:40px 16px;"
         onclick="if(event.target===this)closeDetail()">
        <div style="background:#fff;border-radius:14px;max-width:720px;width:100%;margin-top:32px;box-shadow:0 25px 60px rgba(0,0,0,0.25);">
            <div id="dm-content"><!-- filled by JS --></div>
        </div>
    </div>

    <!-- Block Reason Modal -->
    <div id="blockModal"
         style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:1200;align-items:center;justify-content:center;"
         onclick="if(event.target===this)closeBlockModal()">
        <div style="background:#fff;border-radius:14px;max-width:480px;width:92%;box-shadow:0 25px 60px rgba(0,0,0,0.25);">
            <div style="padding:18px 22px;border-bottom:1px solid #eee;display:flex;align-items:center;gap:10px;">
                <div style="width:36px;height:36px;border-radius:50%;background:rgba(239,68,68,0.1);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="bi bi-shield-slash-fill" style="color:var(--accent-red);font-size:17px;"></i>
                </div>
                <h3 style="margin:0;font-family:var(--font-display);font-size:17px;">Block Document Request</h3>
            </div>
            <div style="padding:20px 22px;">
                <p style="margin:0 0 14px;font-size:14px;color:var(--text-secondary);line-height:1.6;">
                    Enter the reason the requester will see when tracking. This message should explain why the request has been blocked.
                </p>
                <textarea id="blockReasonText" class="form-input" rows="4" placeholder="e.g. This request was found to contain false documentation and violates the portal’s terms of use." style="width:100%;resize:vertical;font-size:14px;"></textarea>
                <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:18px;">
                    <button onclick="closeBlockModal()" class="btn btn-outline" style="margin-bottom:0;">Cancel</button>
                    <button onclick="submitBlockRequest()" class="btn btn-primary" style="margin-bottom:0;background:var(--accent-red);border-color:var(--accent-red);">
                        <i class="bi bi-slash-circle"></i> Block Request
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ── JS inline: data maps from PHP ──────────────────── -->
    <script>
    const REQUEST_DATA = {};
    </script>
    <?php foreach ($requests as $r): ?>
    <script>
    REQUEST_DATA[<?php echo (int)$r["id"]; ?>] = {
        id:             <?php echo (int)$r["id"]; ?>,
        request_token:  <?php echo json_encode($r["request_token"]); ?>,
        document_type:  <?php echo json_encode($r["document_type"]); ?>,
        purpose:        <?php echo json_encode($r["purpose"] ?? ""); ?>,
        remarks:        <?php echo json_encode($r["remarks"] ?? ""); ?>,
        status:         <?php echo json_encode($r["status"]); ?>,
        student_id:     <?php echo json_encode($r["student_id"] ?? $r["guest_student_id"] ?? ""); ?>,
        first_name:     <?php echo json_encode($r["first_name"] ?? ""); ?>,
        last_name:      <?php echo json_encode($r["last_name"] ?? ""); ?>,
        email:          <?php echo json_encode($r["email"] ?? $r["guest_email"] ?? ""); ?>,
        role:           <?php echo json_encode($r["role"] ?? ""); ?>,
        requested_at:   <?php echo json_encode($r["requested_at"]); ?>,
        processed_at:   <?php echo json_encode($r["processed_at"] ?? null); ?>,
        collected_at:   <?php echo json_encode($r["collected_at"] ?? null); ?>,
        rejection_reason:<?php echo json_encode($r["remarks"] ?? ""); ?>,
        rejection_remark:<?php echo json_encode($r["remarks"] ?? ""); ?>,
        processed_first: <?php echo json_encode($r["processed_first"] ?? ""); ?>,
        processed_last:  <?php echo json_encode($r["processed_last"] ?? ""); ?>,
        is_blocked:       <?php echo json_encode($r["is_blocked"] ?? false); ?>,
        block_reason:     <?php echo json_encode($r["block_reason"] ?? ""); ?>
    };
    </script>
    <?php endforeach; ?>

    <script>
    const STAT_OPTS   = ['ALL','PENDING','PROCESSING','READY','COLLECTED','REJECTED'];
    const STATUSES    = STAT_OPTS.filter(function(s){ return s!=='ALL'; });

    function statusPill(s) {
        var cls = s==='PROCESSING'? ''
            : (s==='READY'      ? 'badge-active'
            : (s==='COLLECTED'  ? 'badge-active'
            : (s==='REJECTED'   ? 'badge-inactive'
            : '')));
        var co  = s==='REJECTED'   ? 'var(--accent-red)'
            : (s==='READY'       ? 'var(--green-accent)'
            : (s==='COLLECTED'   ? 'var(--green-accent)' : 'var(--gold)'));
        return '<span class="badge ' + cls + '" style="color:' + co + ';border-color:currentColor;">' + s.replace('_',' ') + '</span>';
    }

    const DOC_TYPES = <?php echo json_encode($docTypes); ?>;

    var tmpl =
'<div style="background:#fff;border-radius:14px;max-width:720px;width:100%;margin-top:32px;box-shadow:0 25px 60px rgba(0,0,0,0.25);">' +
'<div style="padding:20px 24px;border-bottom:1px solid #eee;display:flex;justify-content:space-between;align-items:center;">' +
'  <h3 style="margin:0;font-family:var(--font-display);">Document Request Detail</h3>' +
'  <button onclick="closeDetail()" style="background:none;border:none;font-size:24px;cursor:pointer;color:#999;line-height:1;">&times;</button>' +
'</div>' +
'<div style="padding:24px;">' +

// Token + submitted
'<div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:8px;padding:12px 16px;background:rgba(201,168,76,0.06);border-radius:10px;border:1px solid rgba(201,168,76,0.15);margin-bottom:20px;">' +
'  <div>' +
'    <div style="font-size:10px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px;">Reference Number</div>' +
'    <code id="dm-token" style="font-size:13px;color:var(--gold);"></code>' +
'  </div>' +
'  <div>' +
'    <div style="font-size:10px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px;">Requested</div>' +
'    <span id="dm-requested-at" style="font-size:13px;"></span>' +
'  </div>' +
'</div>' +

// Student info
'<div style="margin-bottom:18px;">' +
'  <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px;">Student</div>' +
'  <div id="dm-student-name" style="font-size:16px;font-weight:600;"></div>' +
'  <div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:4px;">' +
'    <span class="user-email">ID: <span id="dm-student-id"></span></span>' +
'    <span class="user-email" id="dm-student-email"></span>' +
'  </div>' +
'</div>' +

// Document type / Status / Purpose
'<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:12px;margin-bottom:18px;">' +
'  <div>' +
'    <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px;">Document</div>' +
'    <span class="badge badge-role" id="dm-doc-type"></span>' +
'  </div>' +
'  <div>' +
'    <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px;">Status</div>' +
'    <span id="dm-status"></span>' +
'  </div>' +
'  <div>' +
'    <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px;">Processed By</div>' +
'    <span id="dm-processed-by" style="font-size:12px;color:var(--text-muted);"></span>' +
'  </div>' +
'</div>' +

// Purpose
'<div style="margin-bottom:18px;">' +
'  <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px;">Purpose</div>' +
'  <div style="padding:14px 16px;background:rgba(201,168,76,0.04);border-radius:10px;border:1px solid rgba(201,168,76,0.1);white-space:pre-wrap;line-height:1.6;font-size:14px;" id="dm-purpose"></div>' +
'</div>' +

// Remarks / rejection reason
'<div style="margin-bottom:18px;display:none;" id="dm-remarks-wrap">' +
'  <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px;">' +
'    <span id="dm-remarks-label"></span>' +
'  </div>' +
'  <div style="padding:14px 16px;background:rgba(239,68,68,0.05);border-radius:10px;border:1px solid rgba(239,68,68,0.15);white-space:pre-wrap;line-height:1.6;font-size:14px;color:#ef4444;" id="dm-remarks"></div>' +
'</div>' +

// ── Lifecycle toolbar ──────────────────────────────────
'<div id="dm-lifecycle" style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:18px;"></div>' +

// ── Status update form ──
'<form method="POST" id="dm-status-form" style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;padding-top:18px;border-top:1px solid rgba(201,168,76,0.12);">' +
'  <input type="hidden" name="action" value="update_status">' +
'  <input type="hidden" name="doc_request_id" id="dm-doc-id">' +
'  <div class="form-group" style="margin:0;min-width:170px;flex:1;">' +
'    <label class="form-label">Update Status</label>' +
'    <select name="status" class="form-input" id="dm-status-select"></select>' +
'  </div>' +
'  <div class="form-group" style="margin:0;flex:2;min-width:200px;">' +
'    <label class="form-label">Remarks</label>' +
'    <textarea name="remarks" class="form-input" rows="2" placeholder="Add remarks…" id="dm-rem-text"></textarea>' +
'  </div>' +
'  <button type="submit" class="btn btn-primary" style="margin-bottom:0;">Save Status</button>' +
'</form>';

    function closeDetail() {
        document.getElementById('detailModal').style.display = 'none';
    }

    function lifecycleHtml(c) {
        var st = c.status;
        var html = '';
        if (st === 'PENDING') {
            html = '<form method="POST" style="display:inline;"><input type="hidden" name="action" value="process"><input type="hidden" name="doc_request_id" value="' + c.id + '"><button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-gear"></i> Start Processing</button></form>';
        } else if (st === 'PROCESSING') {
            html = '<form method="POST" style="display:inline;"><input type="hidden" name="action" value="mark_ready"><input type="hidden" name="doc_request_id" value="' + c.id + '"><button type="submit" class="btn btn-sm btn-outline" style="color:var(--green-accent);border-color:var(--green-accent);"><i class="bi bi-check2"></i> Ready for Collection</button></form>';
        } else if (st === 'READY') {
            html
            = '<form method="POST" style="display:inline;"><input type="hidden" name="action" value="mark_collected"><input type="hidden" name="doc_request_id" value="' + c.id + '"><button type="submit" class="btn btn-sm btn-outline" style="color:var(--green-accent);border-color:var(--green-accent);"><i class="bi bi-bag-check"></i> Mark Collected</button></form>';
        } else if (st === 'COLLECTED') {
            html = '<span style="font-size:12px;color:var(--green-accent);padding:6px 12px;border-radius:6px;border:1px solid rgba(34,197,94,0.3);background:rgba(34,197,94,0.06);"><i class="bi bi-play-circle"></i> Lifecycle complete</span>';
        } else if (st === 'REJECTED') {
            html = '<span style="font-size:12px;color:var(--accent-red);padding:6px 12px;border-radius:6px;border:1px solid rgba(239,68,68,0.3);background:rgba(239,68,68,0.06);"><i class="bi bi-x-circle"></i> Request rejected</span>';
        }
        if (st !== 'COLLECTED' && st !== 'REJECTED') {
            html += '<button class="btn btn-sm btn-outline" style="padding:4px 9px;color:var(--accent-red);border-color:var(--accent-red);" onclick="rejectFromDetail(' + c.id + ',\'' + c.request_token.replace(/'/g, "\\'") + '\')" title="Reject"><i class="bi bi-x-lg"></i> Reject</button>';
        }
        return html;
    }

    function rejectFromDetail(id, token) {
        if (!confirm('Reject this document request for <strong>' + token + '</strong>? A reason is required.')) return;
        var reason = prompt('Rejection reason:');
        if (!reason || !reason.trim()) return;
        var f = document.createElement('form');
        f.method = 'POST'; f.action = '';
        ['action=reject','doc_request_id='+id,'rejection_reason='+encodeURIComponent(reason.trim())].forEach(function(p){
            var i = document.createElement('input'); i.type='hidden'; i.name=p.split('=')[0]; i.value=p.split('=')[1]; f.appendChild(i);
        });
        document.body.appendChild(f); f.submit();
    }

    function openDetail(id) {
        var c = REQUEST_DATA[id];
        if (!c) return;
        var rAt = c.requested_at ? new Date(c.requested_at).toLocaleString() : '—';
        var pAt = c.processed_at ? new Date(c.processed_at).toLocaleString() : null;
        var cAt = c.collected_at ? new Date(c.collected_at).toLocaleString() : null;

        var roleHtml = c.role ? ' <span style="font-size:11px;color:var(--text-muted);background:rgba(201,168,76,0.1);padding:1px 6px;border-radius:4px;">' + c.role + '</span>' : '';

        // status options
        var statusOpts = STATUSES.map(function(s) {
            return '<option value="' + s + '"' + (c.status===s ? ' selected' : '') + '>' + s.replace('_',' ') + '</option>';
        }).join('');

        var stPillHtml = statusPill(c.status);

        var html = tmpl
            .replace('id="dm-token"',            'id="dm-token"')
            .replace('</code>',                  '</code>')
            .replace('<span id="dm-requested-at"', '<span id="dm-requested-at"')
            .replace('id="dm-student-name"',      'id="dm-student-name"')
            .replace('id="dm-student-id">',       'id="dm-student-id">')
            .replace('id="dm-student-email"',     'id="dm-student-email"')
            .replace('id="dm-doc-type">',         'id="dm-doc-type">')
            .replace('<span id="dm-status">',     '<span id="dm-status">')
            .replace('id="dm-remarks-wrap"',      'id="dm-remarks-wrap"')
            .replace('id="dm-remarks-label">',    'id="dm-remarks-label">')
            .replace('id="dm-remarks">',          'id="dm-remarks">')
            .replace('id="dm-lifecycle">',        'id="dm-lifecycle">')
            .replace('id="dm-doc-id">',           'id="dm-doc-id">')
            .replace('id="dm-status-select">',    'id="dm-status-select">')
            .replace('id="dm-rem-text">',         'id="dm-rem-text">');

        // Fill data
        // Handle guest vs registered user display
        var displayName = ((c.first_name || '') + ' ' + (c.last_name || '')).trim() || c.guest_full_name || '—';
        var displayStudentId = c.student_id || c.guest_student_id || '—';
        var displayEmail = c.email || c.guest_email || '—';

        html = html
            .replace('id="dm-token"',             'id="dm-token"')   // no-op: filled by next step
            .replace('>—</code>',                 '>' + c.request_token + '</code>')
            .replace('id="dm-requested-at">—</span>', 'id="dm-requested-at">' + rAt + '</span>')
            .replace('id="dm-student-name"></div>',  'id="dm-student-name">' + displayName + roleHtml + '</div>')
            .replace('id="dm-student-id">',       'id="dm-student-id">' + displayStudentId + '</span>')
            .replace('id="dm-student-email">',     'id="dm-student-email">' + displayEmail + '</span>')
            .replace('id="dm-doc-type"></span>',     'id="dm-doc-type">' + c.document_type + '</span>')
            .replace('id="dm-status">',              'id="dm-status">' + stPillHtml + '</span>')

            // Purpose  — target "id="dm-purpose"></div>" before any step can consume the >
            .replace('id="dm-purpose"></div>',      'id="dm-purpose">' + (c.purpose || '—') + '</div>');

        // Remarks / rejection reason
        var remarksContent = '';
        if (c.remarks && c.remarks.trim()) {
            var lbl = (c.status === 'REJECTED') ? 'Rejection Reason' : 'Remarks';
            remarksContent = '<div>' +
                '<div style="font-size:10px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px;">' + lbl + '</div>' +
                '<div style="padding:14px 16px;background:rgba(239,68,68,0.04);border-radius:10px;border:1px solid rgba(239,68,68,0.12);white-space:pre-wrap;line-height:1.6;font-size:14px;">' + c.remarks + '</div>' +
                '</div>';
        }
        html = html.replace(
            '<div style="margin-bottom:18px;display:none;" id="dm-remarks-wrap">',
            (remarksContent ? '<div style="margin-bottom:18px;" id="dm-remarks-wrap">' : '<div style="display:none;" id="dm-remarks-wrap">')
        );

        // Lifecycle toolbar
        html = html.replace(
            '<div id="dm-lifecycle"></div>',
            '<div id="dm-lifecycle">' + lifecycleHtml(c) + '</div>'
        );

        // Hidden field for doc_request_id
        html = html.replace(
            'id="dm-doc-id">',
            'id="dm-doc-id" value="' + c.id + '">'
        );

        // Status select options
        html = html.replace(
            'id="dm-status-select"></select>',
            'id="dm-status-select">' + statusOpts + '</select>'
        );

        // Remarks textarea pre-fill
        html = html.replace(
            'id="dm-rem-text"></textarea>',
            'id="dm-rem-text">' + (c.remarks || '') + '</textarea>'
        );

        document.getElementById('detailModal').innerHTML = html;
        document.getElementById('detailModal').style.display = 'flex';
    }

    function HTML_esc(s) {
        if (!s) return '';
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function openBlockModal(id) {
        var modal = document.getElementById('blockModal');
        if (!modal) return;
        modal.dataset.docRequestId = id;
        modal.style.display = 'flex';
        setTimeout(function() {
            var ta = document.getElementById('blockReasonText');
            if (ta) { ta.value = ''; ta.focus(); }
        }, 50);
    }
    function closeBlockModal() {
        var modal = document.getElementById('blockModal');
        if (modal) modal.style.display = 'none';
    }
    function submitBlockRequest() {
        var id = parseInt(document.getElementById('blockModal').dataset.docRequestId, 10);
        var ta = document.getElementById('blockReasonText');
        var reason = ta ? ta.value : '';
        if (!reason.trim()) { if (ta) ta.focus(); return; }
        var f = document.createElement('form');
        f.method = 'POST'; f.action = '';
        var actionInput = document.createElement('input'); actionInput.type='hidden'; actionInput.name='action'; actionInput.value='block_request'; f.appendChild(actionInput);
        var idInput = document.createElement('input'); idInput.type='hidden'; idInput.name='doc_request_id'; idInput.value=id; f.appendChild(idInput);
        var reasonInput = document.createElement('input'); reasonInput.type='hidden'; reasonInput.name='block_reason'; reasonInput.value=reason; f.appendChild(reasonInput);
        document.body.appendChild(f); f.submit();
    }

    function openUnblockModal(id) {
        if (!confirm('Remove the block on this document request? The requester will regain access to tracking.')) return;
        var f = document.createElement('form');
        f.method = 'POST'; f.action = '';
        ['action=unblock_request','doc_request_id='+id].forEach(function(p){
            var i = document.createElement('input'); i.type='hidden'; i.name=p.split('=')[0]; i.value=p.split('=')[1]; f.appendChild(i);
        });
        document.body.appendChild(f); f.submit();
    }

    function closeDetail() { document.getElementById('detailModal').style.display = 'none'; }
    </script>

    <script src="../assets/js/sidebar.js"></script>
</body>
</html>
