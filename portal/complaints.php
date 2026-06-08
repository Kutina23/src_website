<?php
session_start();
require_once "../config/database.php";
require_once "../config/functions.php";
require_once "../models/Complaints.php";

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

$pageTitle   = "Complaint Management";
$success     = $_SESSION["success"] ?? null;
unset($_SESSION["success"]);

$model       = new Complaints(db());
$statusOpts  = ["ALL", "OPEN", "IN_PROGRESS", "RESOLVED", "CLOSED"];
$priorities  = ["LOW", "MEDIUM", "HIGH", "URGENT"];

$filter      = strtoupper($_GET["filter"] ?? "");
$filter      = in_array($filter, $statusOpts) ? $filter : "ALL";
$catFilter   = trim($_GET["category"] ?? "");
$prioFilter  = trim($_GET["priority"] ?? "");
$searchQuery = trim($_GET["search"] ?? "");

$filters = [];
if ($filter !== "ALL")          $filters["status"]   = $filter;
if ($catFilter !== "")          $filters["category"] = $catFilter;
if ($prioFilter !== "")         $filters["priority"] = $prioFilter;
if ($searchQuery !== "")        $filters["search"]   = $searchQuery;

$complaints  = $model->getAll($filters);
$categories  = $model->getCategories();
$staffList   = $model->getStaffMembers();

$total           = $model->countAll();
$totalOpen       = $model->countOpen();
$totalInProgress = $model->countInProgress();
$totalResolved   = $model->countByStatus("RESOLVED");
$totalClosed     = $model->countByStatus("CLOSED");
$totalUnassigned = $model->countUnassigned();

// ── POST handlers ────────────────────────────────────────
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $action = $_POST["action"] ?? "";

    if ($action === "update_status") {
        $id     = (int)($_POST["complaint_id"] ?? 0);
        $status = strtoupper(trim($_POST["status"] ?? ""));
        $res    = trim($_POST["resolution"] ?? "");
        if ($id > 0) {
            $model->updateStatus($id, $status, $res ?: null);
            logActivity("complaint_status_updated", $_SESSION["user_id"], ["complaint_id" => $id, "new_status" => $status]);
            $_SESSION["success"] = "Status updated to " . ucwords(strtolower(str_replace("_", " ", $status)));
        }
        header("Location: complaints.php?" . http_build_query($_GET));
        exit;
    }

    if ($action === "assign") {
        $id    = (int)($_POST["complaint_id"] ?? 0);
        $staff = (int)($_POST["assigned_to"] ?? 0);
        if ($id > 0 && $staff > 0) {
            $model->assign($id, $staff, trim($_POST["assignment_note"] ?? ""));
            logActivity("complaint_assigned", $_SESSION["user_id"], ["complaint_id" => $id, "assigned_to" => $staff]);
            $_SESSION["success"] = "Complaint #{$id} assigned successfully";
        }
        header("Location: complaints.php?" . http_build_query($_GET));
        exit;
    }

    if ($action === "unassign") {
        $id = (int)($_POST["complaint_id"] ?? 0);
        if ($id > 0) {
            db()->update("complaints", ["assigned_to" => null, "updated_at" => date("Y-m-d H:i:s")], ["id" => $id]);
            logActivity("complaint_unassigned", $_SESSION["user_id"], ["complaint_id" => $id]);
            $_SESSION["success"] = "Complaint #{$id} unassigned";
        }
        header("Location: complaints.php?" . http_build_query($_GET));
        exit;
    }

    if ($action === "set_priority") {
        $id      = (int)($_POST["complaint_id"] ?? 0);
        $newPrio = strtoupper(trim($_POST["priority"] ?? ""));
        if ($id > 0 && in_array($newPrio, $priorities)) {
            $model->updatePriority($id, $newPrio);
            logActivity("complaint_priority_updated", $_SESSION["user_id"], ["complaint_id" => $id, "new_priority" => $newPrio]);
            $_SESSION["success"] = "Priority updated to {$newPrio}";
        }
        header("Location: complaints.php?" . http_build_query($_GET));
        exit;
    }
}

// Build lookup maps for the complaint array
$compMap = [];
foreach ($complaints as $c) {
    $compMap[(int)$c["id"]] = $c;
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
                <h1 class="header-title">Complaint Management</h1>
                <div class="header-actions"><a href="../logout.php" class="header-btn"><i class="bi bi-box-arrow-right"></i></a></div>
            </header>
            <main class="content-body">
                <div class="dashboard-container">

                    <div class="dashboard-header-section">
                        <h2 class="dashboard-title">Complaints Desk</h2>
                        <p class="dashboard-subtitle">Track and manage all grievance submissions — view, assign, and update status</p>
                    </div>

                    <?php if ($success): ?>
                        <div style="background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.2);border-radius:8px;padding:14px 18px;margin-bottom:20px;color:#22c55e;">
                            <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                        </div>
                    <?php endif; ?>

                    <!-- Stats -->
                    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:10px;margin-bottom:22px;">
                        <?php $stats = ["ALL"=>$total,"OPEN"=>$totalOpen,"IN_PROGRESS"=>$totalInProgress,"RESOLVED"=>$totalResolved,"CLOSED"=>$totalClosed]; ?>
                        <?php foreach ($stats as $s=>$cnt): ?>
                        <a href="?filter=<?php echo $s; ?>&category=<?php echo urlencode($catFilter); ?>&priority=<?php echo urlencode($prioFilter); ?>&search=<?php echo urlencode($searchQuery); ?>"
                           style="text-align:center;padding:14px 10px;border-radius:10px;border:1px solid rgba(201,168,76,0.18);background:rgba(201,168,76,0.04);text-decoration:none;color:inherit;display:block;<?php echo ($filter===$s) ? 'border-color:var(--gold);background:rgba(201,168,76,0.11);' : ''; ?>">
                            <div style="font-size:20px;font-weight:700;color:var(--gold);"><?php echo $cnt; ?></div>
                            <div style="font-size:10px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;margin-top:2px;">
                                <?php echo $s==="ALL" ? "Total" : str_replace("_", " ", ucwords(strtolower($s))); ?>
                            </div>
                        </a>
                        <?php endforeach; ?>
                        <div style="text-align:center;padding:14px 10px;border-radius:10px;border:1px solid rgba(239,68,68,0.18);background:rgba(239,68,68,0.04);">
                            <div style="font-size:20px;font-weight:700;color:var(--accent-red);"><?php echo $totalUnassigned; ?></div>
                            <div style="font-size:10px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;margin-top:2px;">Unassigned</div>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div style="background:var(--card-bg);border:1px solid rgba(138,155,184,0.1);border-radius:12px;padding:18px;margin-bottom:18px;">
                        <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
                            <div class="form-group" style="flex:1;min-width:180px;margin:0;">
                                <label class="form-label">Search</label>
                                <input type="text" name="search" class="form-input" placeholder="Token, subject, description…" value="<?php echo htmlspecialchars($searchQuery); ?>">
                            </div>
                            <div class="form-group" style="margin:0;min-width:150px;">
                                <label class="form-label">Category</label>
                                <select name="category" class="form-input">
                                    <option value="">All</option>
                                    <?php foreach ($categories as $c): ?>
                                        <option value="<?php echo htmlspecialchars($c); ?>" <?php echo $catFilter === $c ? "selected" : ""; ?>><?php echo htmlspecialchars($c); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group" style="margin:0;min-width:130px;">
                                <label class="form-label">Priority</label>
                                <select name="priority" class="form-input">
                                    <option value="">All</option>
                                    <?php foreach ($priorities as $p): ?>
                                        <option value="<?php echo $p; ?>" <?php echo $prioFilter === $p ? "selected" : ""; ?>><?php echo $p; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div style="display:flex;gap:8px;margin-bottom:0;">
                                <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Apply</button>
                                <a href="?filter=<?php echo $filter; ?>" class="btn btn-outline">Clear</a>
                            </div>
                        </form>
                    </div>

                    <!-- Status tabs -->
                    <div style="display:flex;gap:6px;margin-bottom:18px;flex-wrap:wrap;">
                        <?php $tabLabels = ["ALL"=>"All","OPEN"=>"Open","IN_PROGRESS"=>"In Progress","RESOLVED"=>"Resolved","CLOSED"=>"Closed"]; ?>
                        <?php foreach ($tabLabels as $t=>$label): ?>
                            <a href="?filter=<?php echo $t; ?>&category=<?php echo urlencode($catFilter); ?>&priority=<?php echo urlencode($prioFilter); ?>&search=<?php echo urlencode($searchQuery); ?>"
                               class="btn <?php echo ($filter===$t?'btn-primary':'btn-outline'); ?>" style="padding:5px 13px;font-size:13px;">
                                <?php echo $label; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>

                    <!-- Table -->
                    <div class="table-container">
                        <?php if (empty($complaints)): ?>
                            <div class="empty-state">
                                <div class="empty-icon"><i class="bi bi-clipboard-check"></i></div>
                                <h3 class="empty-title">No complaints found</h3>
                                <p class="empty-text">
                                    <?php if ($filter!=="ALL" || $catFilter || $prioFilter || $searchQuery): ?>
                                        No complaints match the current filters.
                                    <?php else: ?>
                                        No complaints have been submitted yet.
                                    <?php endif; ?>
                                </p>
                            </div>
                        <?php else: ?>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Token</th>
                                        <th>Subject &amp; Description</th>
                                        <th>Category</th>
                                        <th>Priority</th>
                                        <th>Status</th>
                                        <th>Assigned To</th>
                                        <th>Submitted</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($complaints as $idx => $c): ?>
                                    <?php
                                        $st   = strtoupper($c["status"]);
                                        $prio = strtoupper($c["priority"]);
                                        $stCls = ($st==="OPEN" ? "" : ($st==="IN_PROGRESS" ? "badge-active" : ($st==="RESOLVED" ? "badge-active" : "badge-inactive")));
                                        $prioCls = $prio==="LOW" ? "" : ($prio==="MEDIUM" ? "" : ($prio==="HIGH" ? "badge-active" : "badge-inactive"));
                                    ?>
                                    <tr>
                                        <td><?php echo $idx+1; ?></td>
                                        <td>
                                            <code style="font-size:11px;background:rgba(201,168,76,0.1);padding:3px 8px;border-radius:5px;color:var(--gold);white-space:nowrap;">
                                                <?php echo htmlspecialchars($c["complaint_token"]); ?>
                                            </code>
                                        </td>
                                        <td>
                                            <div class="user-name"><?php echo htmlspecialchars(truncate($c["subject"], 60)); ?></div>
                                            <div class="user-email"><?php echo htmlspecialchars(truncate($c["description"], 80)); ?></div>
                                            <?php if ($c["resolution"]): ?>
                                                <div style="font-size:11px;color:#22c55e;margin-top:2px;"><i class="bi bi-check2-all"></i> Has resolution</div>
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="badge badge-role"><?php echo htmlspecialchars($c["category"] ?? "—"); ?></span></td>
                                        <td>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="set_priority">
                                                <input type="hidden" name="complaint_id" value="<?php echo (int)$c["id"]; ?>">
                                                <?php
                                                    $optPrio = $prio==="HIGH" ? "LOW" : ($prio==="URGENT" ? "HIGH" : ($prio==="LOW" ? "MEDIUM" : "LOW"));
                                                ?>
                                                <input type="hidden" name="priority" value="<?php echo $optPrio; ?>">
                                                <button type="submit"
                                                        style="font-size:11px;padding:3px 7px;border:1px solid <?php echo $prio==='URGENT'?'var(--accent-red)':($prio==='HIGH'?'var(--gold-light)':'var(--gold)'); ?>;border-radius:6px;background:transparent;color:<?php echo $prio==='URGENT'?'var(--accent-red)':($prio==='HIGH'?'var(--gold-light)':'var(--gold)'); ?>;cursor:pointer;">
                                                    <?php echo $prio; ?>
                                                </button>
                                            </form>
                                        </td>
                                        <td><span class="badge <?php echo $stCls; ?>"><?php echo str_replace("_", " ", $st); ?></span></td>
                                        <td>
                                            <?php if ($c["assigned_to"]): ?>
                                                <span class="user-email"><?php echo htmlspecialchars(trim(($c["assigned_first"] ?? "") . " " . ($c["assigned_last"] ?? ""))); ?></span>
                                            <?php else: ?>
                                                <span style="font-size:12px;color:var(--text-muted);">Unassigned</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo timeAgo($c["created_at"]); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline" style="padding:4px 9px;"
                                                    onclick="openDetail(<?php echo (int)$c['id']; ?>)" title="View / Update">
                                                <i class="bi bi-eye-fill"></i>
                                            </button>
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

    <!-- Detail Modal skeleton ─ JS fills dialog-content inner HTML ── -->
    <div id="detailModal"
         style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:1100;align-items:flex-start;justify-content:center;overflow-y:auto;padding:40px 16px;"
         onclick="if(event.target===this)closeDetail()">
        <div id="dialog-content" style="background:#fff;border-radius:14px;max-width:700px;width:100%;margin-top:40px;box-shadow:0 25px 60px rgba(0,0,0,0.25);"><!-- injected by JS --></div>
    </div>

    <!-- JS data arrays ─ placed OUTSIDE the modal container ── -->
    <script>
    // PHP → JS lookup map: complaint id → complaint fields
    const COMPLAINT_DATA = {};
    </script>
    <?php foreach ($complaints as $c): ?>
    <script>
    COMPLAINT_DATA[<?php echo (int)$c["id"]; ?>] = {
        id:               <?php echo (int)$c["id"]; ?>,
        complaint_token:  <?php echo json_encode($c["complaint_token"]); ?>,
        subject:          <?php echo json_encode($c["subject"]); ?>,
        description:      <?php echo json_encode($c["description"]); ?>,
        category:         <?php echo json_encode($c["category"] ?? ""); ?>,
        priority:         <?php echo json_encode($c["priority"]); ?>,
        status:           <?php echo json_encode($c["status"]); ?>,
        resolution:       <?php echo json_encode($c["resolution"] ?? ""); ?>,
        assigned_to:      <?php echo json_encode($c["assigned_to"] ?? null); ?>,
        assigned_first:   <?php echo json_encode($c["assigned_first"] ?? ""); ?>,
        assigned_last:    <?php echo json_encode($c["assigned_last"] ?? ""); ?>,
        created_at:       <?php echo json_encode($c["created_at"]); ?>,
        resolved_at:      <?php echo json_encode($c["resolved_at"] ?? null); ?>
    };
    </script>
    <?php endforeach; ?>

    <script>
    const STAFF_LIST = <?php echo json_encode($staffList); ?>;
    const STATUSES   = ['OPEN','IN_PROGRESS','RESOLVED','CLOSED'];

    function priorityPill(p) {
        var co = p==='URGENT' ? 'var(--accent-red)' : (p==='HIGH' ? 'var(--gold-light)' : (p==='LOW' ? 'var(--green-accent)' : 'var(--gold)'));
        return '<span style="color:' + co + ';font-weight:600;font-size:12px;">' + p + '</span>';
    }
    function statusPill(s) {
        var cls = s==='IN_PROGRESS' ? 'badge-active' : (s==='RESOLVED' ? 'badge-active' : (s==='CLOSED' ? 'badge-inactive' : ''));
        return '<span class="badge ' + cls + '">' + s.replace('_',' ') + '</span>';
    }
    function staffName(c) {
        if (!c.assigned_to) return '<span style="font-size:12px;color:var(--text-muted);">Unassigned</span>';
        return htmlEsc(c.assigned_first) + ' ' + htmlEsc(c.assigned_last);
    }
    function staffOptions(selectedStaffId) {
        var opts = '<option value="">— Select staff member —</option>';
        STAFF_LIST.forEach(function(s){
            var sel = String(s.id) === String(selectedStaffId) ? ' selected' : '';
            opts += '<option value="' + s.id + '"' + sel + '>' + s.first_name + ' ' + s.last_name + ' (' + s.role + ')</option>';
        });
        return opts;
    }
    function htmlEsc(s) { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

    function openDetail(id) {
        var c = COMPLAINT_DATA[id];
        if (!c) return;
        var dateStr = c.created_at ? new Date(c.created_at).toLocaleString() : '—';
        var resAt   = c.resolved_at ? new Date(c.resolved_at).toLocaleString() : null;
        var prioPill= priorityPill(c.priority);

        var html =
            '<div style="padding:20px 24px;border-bottom:1px solid #eee;display:flex;justify-content:space-between;align-items:center;">' +
            '  <h3 style="margin:0;font-family:var(--font-display);">Complaint Details</h3>' +
            '  <button onclick="closeModal()" style="background:none;border:none;font-size:24px;cursor:pointer;color:#999;line-height:1;">&times;</button>' +
            '</div>' +
            '<div style="padding:24px;">' +

            // Token badge
            '<div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:8px;padding:12px 16px;background:rgba(201,168,76,0.06);border-radius:10px;border:1px solid rgba(201,168,76,0.15);margin-bottom:20px;">' +
            '  <div>' +
            '    <div style="font-size:10px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px;">Tracking Token</div>' +
            '    <code style="font-size:13px;color:var(--gold);">' + htmlEsc(c.complaint_token) + '</code>' +
            '  </div>' +
            '  <div>' +
            '    <div style="font-size:10px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px;">Submitted</div>' +
            '    <span style="font-size:13px;">' + dateStr + '</span>' +
            '  </div>' +
            '</div>' +

            // Subject
            '<div style="margin-bottom:18px;">' +
            '  <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px;">Subject</div>' +
            '  <div style="font-size:16px;font-weight:600;">' + htmlEsc(c.subject) + '</div>' +
            '</div>' +

            // Category / Priority / Status / Assigned
            '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:12px;margin-bottom:18px;">' +
            '  <div>' +
            '    <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px;">Category</div>' +
            '    <span class="badge badge-role">' + htmlEsc(c.category || '—') + '</span>' +
            '  </div>' +
            '  <div>' +
            '    <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px;">Priority</div>' +
            '    ' + prioPill +
            '  </div>' +
            '  <div>' +
            '    <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px;">Status</div>' +
            '    ' + statusPill(c.status) +
            '  </div>' +
            '  <div>' +
            '    <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px;">Assigned To</div>' +
            '    ' + staffName(c) +
            '  </div>' +
            '</div>' +

            // Description
            '<div style="margin-bottom:18px;">' +
            '  <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:6px;">Full Description</div>' +
            '  <div style="padding:14px 16px;background:rgba(201,168,76,0.04);border-radius:10px;border:1px solid rgba(201,168,76,0.1);white-space:pre-wrap;line-height:1.65;font-size:14px;">' + htmlEsc(c.description) + '</div>' +
            '</div>' +

            // Resolution (if any)
            (c.resolution ?
            '<div style="margin-bottom:18px;">' +
            '  <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:6px;">Resolution Notes</div>' +
            '  <div style="padding:14px 16px;background:rgba(34,197,94,0.05);border-radius:10px;border:1px solid rgba(34,197,94,0.15);white-space:pre-wrap;line-height:1.65;font-size:14px;color:#16a34a;">' + htmlEsc(c.resolution) + '</div>' +
            '</div>' : '') +

            // Resolved-at timestamp
            (resAt ?
            '<div style="margin-bottom:18px;">' +
            '  <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px;">Resolved At</div>' +
            '  <div style="font-size:14px;">' + resAt + '</div>' +
            '</div>' : '') +

            // ── Status update form ──
            '<form method="POST" style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;padding-top:18px;border-top:1px solid rgba(201,168,76,0.12);margin-bottom:0;">' +
            '  <input type="hidden" name="action" value="update_status">' +
            '  <input type="hidden" name="complaint_id" value="' + c.id + '">' +

            '  <div class="form-group" style="margin:0;min-width:170px;flex:1;">' +
            '    <label class="form-label">Update Status</label>' +
            '    <select name="status" class="form-input">' +
                 STATUSES.map(function(s){
                    return '<option value="' + s + '"' + (c.status===s ? ' selected' : '') + '>' + s.replace('_',' ') + '</option>';
                 }).join('') +
            '    </select>' +
            '  </div>' +

            '  <div class="form-group" style="margin:0;flex:2;min-width:200px;">' +
            '    <label class="form-label">Resolution / Notes</label>' +
            '    <textarea name="resolution" class="form-input" rows="2" placeholder="Add resolution notes…">' + htmlEsc(c.resolution || '') + '</textarea>' +
            '  </div>' +

            '  <button type="submit" class="btn btn-primary" style="margin-bottom:0;">Save Status</button>' +

            '  <a href="track.php?mode=complaint&token=' + htmlEsc(c.complaint_token) + '" target="_blank"' +
            '     class="btn btn-outline" style="margin-bottom:0;" title="View public tracking page">' +
            '    <i class="bi bi-box-arrow-up-right"></i> Public Page' +
            '  </a>' +
            '</form>' +

            // ── Assignment form ──
            '<form method="POST" style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;margin-top:14px;padding:16px;background:rgba(138,155,184,0.04);border-radius:10px;border:1px solid rgba(138,155,184,0.1);">' +
            '  <input type="hidden" name="action" value="assign">' +
            '  <input type="hidden" name="complaint_id" value="' + c.id + '">' +

            '  <div class="form-group" style="margin:0;flex:2;min-width:180px;">' +
            '    <label class="form-label">Assign To</label>' +
            '    ' + staffOptions(c.assigned_to) +
            '  </div>' +

            '  <div class="form-group" style="margin:0;flex:2;min-width:180px;">' +
            '    <label class="form-label">Assignment Note</label>' +
            '    <input type="text" name="assignment_note" class="form-input" placeholder="Optional note…">' +
            '  </div>' +

            '  <button type="submit" class="btn btn-primary" style="margin-bottom:0;"><i class="bi bi-person-check"></i> Assign</button>' +

            (c.assigned_to ?
            '  <button type="submit" name="action" value="unassign" class="btn btn-outline"' +
            '          style="margin-bottom:0;color:var(--accent-red);border-color:var(--accent-red);"><i class="bi bi-person-dash"></i> Unassign</button>' : '') +

            '</form>' +

            '</div>';   // close <div style="padding:24px">

        document.getElementById('dialog-content').innerHTML = html;
        document.getElementById('detailModal').style.display = 'flex';
    }

    function closeModal() {
        document.getElementById('detailModal').style.display = 'none';
    }
    </script>

    <script src="../assets/js/sidebar.js"></script>
    <script src="../assets/js/loader-service.js"></script>
</body>
</html>
