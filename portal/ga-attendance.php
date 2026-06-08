<?php
// GA Attendance Management — Dynamic Admin
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

$pageTitle = "GA Attendance";
$gaModel  = new GaSessions(db());

$activeSessionId = isset($_GET["session_id"]) ? (int)$_GET["session_id"] : null;
$error          = null;
$success        = null;

// ── Save attendance (bulk) ─────────────────────────────────
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "save_attendance") {
    $csrf  = $_POST["csrf_token"] ?? "";
    if (!hash_equals($_SESSION["csrf_token"] ?? "", $csrf)) {
        $error = "Invalid CSRF token.";
    } else {
        $sessionId = (int)($_POST["session_id"] ?? 0);
        $present   = $_POST["present"] ?? []; // array of user_ids
        $allUsers  = $gaModel->getAllUsersForAttendance();

        foreach ($allUsers as $u) {
            if (in_array((string)$u["id"], $present)) {
                $gaModel->markPresent($sessionId, $u["id"]);
            } else {
                $gaModel->markAbsent($sessionId, $u["id"]);
            }
        }

        logActivity("update_ga_attendance", $_SESSION["user_id"] ?? null, [
            "session_id"    => $sessionId,
            "present_count" => count($present)
        ]);
        $success = "Attendance saved. " . count($present) . " attendee" . (count($present) !== 1 ? "s" : "") . " recorded.";
    }
}

// ── Toggle single attendance via GET (ajax-friendly) ───────
if (isset($_GET["toggle"]) && $activeSessionId) {
    $uid = (int)$_GET["uid"];
    $current = $gaModel->hasAttendance($activeSessionId, $uid);
    if ($current === null) {
        $gaModel->markPresent($activeSessionId, $uid);
        $_SESSION["success"] = "Attendance recorded.";
    } elseif ($current) {
        $gaModel->markAbsent($activeSessionId, $uid);
        $_SESSION["success"] = "Marked absent.";
    } else {
        $gaModel->markPresent($activeSessionId, $uid);
        $_SESSION["success"] = "Marked present.";
    }
    header("Location: ga-attendance.php?session_id=" . $activeSessionId);
    exit;
}

$_SESSION["csrf_token"] = $_SESSION["csrf_token"] ?? bin2hex(random_bytes(32));

// ── Load session data ──────────────────────────────────────
$sessions    = $gaModel->getAll(["status" => "SCHEDULED"]);
$allSessions = $gaModel->getAll();
$allUsers    = $gaModel->getAllUsersForAttendance();
$attendanceList = [];

$sessionMeta = null;
$attStats = null;
if ($activeSessionId) {
    $sessionMeta  = $gaModel->getById($activeSessionId);
    $attendanceList = $gaModel->getAttendanceBySession($activeSessionId);
    $attStats  = $gaModel->getAttendanceStats($activeSessionId);

    // Build quick-lookup map
    $attMap = [];
    foreach ($attendanceList as $a) {
        $attMap[$a["user_id"]] = (bool)$a["attended"];
    }
} else {
    // Build empty map
    $attMap = [];
    foreach ($allUsers as $u) {
        $attMap[$u["id"]] = false;
    }
}

// Which user IDs are marked present (for the form)
$presentIds = array_keys(array_filter($attMap, fn($v) => $v));
$presentSet = array_flip($presentIds);

$roleBadgeClass = ["PRO" => "admin", "PRESIDENT" => "president", "DIRECTOR ICT" => "ict", "DEAN" => "dean", "STUDENT" => "student"];

function roleClass($role) {
    global $roleBadgeClass;
    return $roleBadgeClass[$role] ?? "";
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
    <script>window.currentUserRole = '<?php echo $currentRole; ?>';</script>
    <style>
        .page-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:28px; flex-wrap:wrap; gap:16px; }
        .page-header h1 { color:#c9a84c; margin:0; font-size:2rem; }

        /* Session selector */
        .session-selector { background:rgba(14,20,40,.85); border:1px solid rgba(201,168,76,.12); border-radius:12px; padding:22px 24px; margin-bottom:24px; }
        .session-selector label { display:block; font-size:11px; font-family:'Space Mono',monospace; color:#7a8fa0; letter-spacing:.1em; text-transform:uppercase; margin-bottom:10px; }
        .session-selector select { width:100%; background:rgba(6,12,26,.9); color:#e0e8f0; border:1px solid rgba(160,180,208,.18); border-radius:8px; padding:12px 16px; font-family:'Outfit',sans-serif; font-size:15px; }
        .session-selector select:focus { outline:none; border-color:#c9a84c; }

        /* Session meta */
        .session-meta-row { display:flex; gap:20px; flex-wrap:wrap; margin-top:16px; }
        .meta-chip { display:flex; align-items:center; gap:6px; background:rgba(6,12,26,.7); padding:6px 14px; border-radius:20px; font-size:13px; color:#8a9ab0; }

        /* Attendance stats */
        .att-stats { display:grid; grid-template-columns:repeat(auto-fill, minmax(130px,1fr)); gap:14px; margin-bottom:28px; }
        .att-stat   { background:rgba(14,20,40,.9); border:1px solid rgba(201,168,76,.12); border-radius:10px; padding:18px 16px; text-align:center; }
        .att-stat .val { font-family:'Space Mono',monospace; font-size:1.8rem; font-weight:700; color:#fff; }
        .att-stat .lbl { font-size:10px; color:#5a6a80; text-transform:uppercase; letter-spacing:.08em; margin-top:4px; font-family:'Space Mono',monospace; }
        .att-stat.stat-present .val { color:#3eb87c; }
        .att-stat.stat-absent  .val { color:#f07060; }
        .att-stat.stat-quorum  .val { color:#c9a84c; }
        .quorum-bar-wrap { margin-bottom:28px; background:rgba(14,20,40,.85); border:1px solid rgba(201,168,76,.12); border-radius:12px; padding:20px 24px; }
        .quorum-bar-row  { display:flex; align-items:center; gap:14px; margin-top:10px; }
        .quorum-track    { flex:1; height:10px; background:rgba(6,12,26,.9); border-radius:10px; overflow:hidden; }
        .quorum-fill     { height:100%; border-radius:10px; background:#c9a84c; transition:width .4s; }
        .quorum-fill.met { background:#3eb87c; }
        .quorum-label    { font-family:'Space Mono',monospace; font-size:12px; color:#5a6a80; min-width:130px; text-align:right; }

        /* User attendance rows */
        .att-list { display:flex; flex-direction:column; gap:2px; }
        .att-row  { display:flex; align-items:center; gap:14px; padding:12px 18px; background:rgba(14,20,40,.7); border-bottom:1px solid rgba(255,255,255,.03); border-radius:7px; transition:background .15s; }
        .att-row:hover  { background:rgba(201,168,76,.04); }
        .att-row.present { background:rgba(55,180,120,.04); }
        .att-avatar      { width:36px; height:36px; border-radius:50%; background:rgba(201,168,76,.15); display:flex; align-items:center; justify-content:center; font-family:'Outfit',sans-serif; font-weight:700; font-size:13px; color:#c9a84c; flex-shrink:0; }
        .att-row.present .att-avatar    { background:rgba(55,180,120,.15); color:#3eb87c; }
        .att-user-info   { flex:1; min-width:0; }
        .att-user-name   { font-weight:600; color:#fff; font-size:14px; }
        .att-user-sub    { font-size:12px; color:#5a6a80; margin-top:2px; }
        .role-badge-sm { display:inline-flex; padding:2px 8px; border-radius:12px; font-size:10px; font-family:'Space Mono',monospace; letter-spacing:.05em; margin-right:8px; }
        .role-badge-sm.admin     { background:rgba(220,200,40,.15); color:#c9a84c; }
        .role-badge-sm.president { background:rgba(74,144,226,.15); color:#6ab0ff; }
        .role-badge-sm.ict       { background:rgba(201,168,76,.15); color:#c9a84c; }
        .role-badge-sm.dean      { background:rgba(160,90,120,.15); color:#c06090; }
        .role-badge-sm.student   { background:rgba(55,180,120,.15); color:#3eb87c; }

        /* Toggle switch */
        .toggle-wrap { display:flex; align-items:center; gap:8px; flex-shrink:0; }
        .toggle { position:relative; width:48px; height:26px; display:inline-block; cursor:pointer; }
        .toggle input { opacity:0; width:0; height:0; }
        .toggle-slider { position:absolute; inset:0; background:rgba(30,40,65,.9); border:1px solid rgba(160,180,208,.2); border-radius:26px; transition:.3s; }
        .toggle-slider::before { content:""; position:absolute; width:20px; height:20px; left:2px; bottom:2px; background:#4a5f80; border-radius:50%; transition:.3s; }
        .toggle input:checked + .toggle-slider { background:rgba(55,180,120,.2); border-color:#3eb87c; }
        .toggle input:checked + .toggle-slider::before { transform:translateX(22px); background:#3eb87c; }
        .att-label { font-size:11px; font-family:'Space Mono',monospace; color:#5a6a80; letter-spacing:.06em; }

        .empty-state { text-align:center; padding:60px 20px; color:#5a6a80; }
        .empty-state i   { font-size:3rem; margin-bottom:16px; display:block; }

        .alert { padding:14px 20px; border-radius:8px; margin-bottom:20px; font-size:14px; display:flex; align-items:center; gap:10px; }
        .alert-success { background:rgba(55,180,120,.12); border:1px solid rgba(55,180,120,.3); color:#3eb87c; }
        .alert-error   { background:rgba(220,80,60,.12);  border:1px solid rgba(220,80,60,.3);  color:#f07060; }

        .toast { position:fixed; bottom:20px; right:20px; background:#1a2240; border:1px solid rgba(201,168,76,.3); border-radius:10px; padding:14px 22px; color:#c9a84c; font-size:14px; z-index:99999; box-shadow:0 8px 32px rgba(0,0,0,.5); animation:slideUp .25s ease-out; }
        @keyframes slideUp { from { transform:translateY(16px); opacity:0; } to { transform:translateY(0); opacity:1; } }
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
                    <?php if ($error): ?>
                        <div class="alert alert-error"><i class="bi bi-exclamation-triangle"></i><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success"><i class="bi bi-check-circle"></i><?php echo htmlspecialchars($success); ?></div>
                    <?php endif; ?>
                    <?php if ($_SESSION["success"] ?? null): ?>
                        <div class="alert alert-success"><i class="bi bi-check-circle"></i><?php echo htmlspecialchars($_SESSION["success"]); unset($_SESSION["success"]); ?></div>
                    <?php endif; ?>

                    <!-- Header -->
                    <div class="page-header">
                        <div>
                            <h1>
                                <i class="bi bi-check-square" style="margin-right:12px; vertical-align:middle;"></i>
                                GA Attendance
                            </h1>
                            <p style="color:#5a6a80; font-size:14px; margin-top:6px;">
                                Track attendance per GA session. Mark members present or absent &amp; monitor quorum in real time.
                            </p>
                        </div>
                        <?php if ($activeSessionId): ?>
                        <form method="POST" action="ga-attendance.php"
                              style="display:inline-block;"
                              onsubmit="return confirm('Save all attendance records for this session?');">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION["csrf_token"]; ?>">
                            <input type="hidden" name="action"      value="save_attendance">
                            <input type="hidden" name="session_id"  value="<?php echo $activeSessionId; ?>">
                            <button type="submit" class="btn-primary">
                                <i class="bi bi-save"></i> Save All Changes
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>

                    <!-- Session selector -->
                    <div class="session-selector">
                        <label for="sessionList">Select a GA Session</label>
                        <select id="sessionList" onchange="window.location='ga-attendance.php?session_id='+encodeURIComponent(this.value)">
                            <option value="">— Choose a session —</option>
                            <?php foreach ($allSessions as $s): ?>
                                <?php
                                    $optLabel = sprintf(
                                        "%s · %s · %s",
                                        $s["title"],
                                        $s["session_type"],
                                        $s["scheduled_datetime"]
                                            ? date("M d, Y", strtotime($s["scheduled_datetime"]))
                                            : "Unscheduled"
                                    );
                                ?>
                                <option value="<?php echo $s["id"]; ?>" <?php echo $activeSessionId === (int)$s["id"] ? "selected" : ""; ?>>
                                    <?php echo htmlspecialchars($optLabel); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <?php if ($sessionMeta): ?>
                        <div class="session-meta-row">
                            <span class="meta-chip">
                                <i class="bi bi-<?php echo $sessionMeta["session_type"] === "ANNUAL" ? "calendar-event" : ($sessionMeta["session_type"] === "EMERGENCY" ? "broadcast" : "lightning"); ?>"></i>
                                <?php echo $sessionMeta["session_type"]; ?>
                            </span>
                            <span class="meta-chip">
                                <i class="bi bi-clock"></i>
                                <?php echo $sessionMeta["scheduled_datetime"] ? date("M d, Y \a\t h:i A", strtotime($sessionMeta["scheduled_datetime"])) : "Not scheduled"; ?>
                            </span>
                            <span class="meta-chip">
                                <i class="bi bi-geo-alt"></i>
                                <?php echo $sessionMeta["location"] ? htmlspecialchars($sessionMeta["location"]) : "No location"; ?>
                            </span>
                            <span class="meta-chip">
                                <i class="bi bi-info-circle"></i>
                                <?php echo str_replace("_", " ", $sessionMeta["status"]); ?>
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- ── No session selected ── -->
                    <?php if (!$activeSessionId || !$sessionMeta): ?>
                    <div class="empty-state">
                        <i class="bi bi-calendar2-check"></i>
                        <p>Select a GA session from the dropdown above to record and manage attendance.</p>
                    </div>

                    <!-- ── Session selected ── -->
                    <?php else: ?>

                    <!-- Attendance stats -->
                    <div class="att-stats">
                        <div class="att-stat stat-present">
                            <div class="val"><i class="bi bi-check-lg" style="margin-right:6px;"></i><?php echo (int)($attStats["present"] ?? 0); ?></div>
                            <div class="lbl">Present</div>
                        </div>
                        <div class="att-stat stat-absent">
                            <div class="val"><i class="bi bi-x-lg"      style="margin-right:6px;"></i><?php echo (int)($attStats["absent"] ?? 0); ?></div>
                            <div class="lbl">Absent</div>
                        </div>
                        <div class="att-stat">
                            <div class="val"><?php echo (int)($attStats["total_invited"] ?? 0); ?></div>
                            <div class="lbl">Total Invited</div>
                        </div>
                        <div class="att-stat">
                            <div class="val"><?php echo (float)($attStats["attendance_pct"] ?? 0); ?>%</div>
                            <div class="lbl">Attendance Rate</div>
                        </div>
                        <div class="att-stat stat-quorum">
                            <div class="val">
                                <i class="bi bi-<?php echo ($attStats["quorum_met"] ?? false) ? "shield-check" : "shield-exclamation"; ?>" style="margin-right:6px;"></i>
                                <?php echo ($attStats["quorum_met"] ?? false) ? "YES" : "NO"; ?>
                            </div>
                            <div class="lbl">Quorum Met<br>(≥25% active users)</div>
                        </div>
                    </div>

                    <!-- Quorum bar -->
                    <div class="quorum-bar-wrap">
                        <label style="font-size:11px; font-family:'Space Mono',monospace; color:#7a8fa0; letter-spacing:.1em; text-transform:uppercase;">
                            Attendance Progress (<?php echo (int)($attStats["present"] ?? 0); ?> / <?php echo (int)($attStats["total_users"] ?? 1); ?> active users)
                        </label>
                        <div class="quorum-bar-row">
                            <div class="quorum-track">
                                <div class="quorum-fill <?php echo ($attStats["quorum_met"] ?? false) ? "met" : ""; ?>"
                                     style="width: <?php echo min(($attStats["attendance_pct"] ?? 0), 100); ?>%;"></div>
                            </div>
                            <span class="quorum-label">
                                <i class="bi bi-<?php echo ($attStats["quorum_met"] ?? false) ? "check2" : "x"; ?>"></i>
                                <?php echo ($attStats["quorum_met"] ?? false) ? "Quorum Reached" : "Below Quorum"; ?>
                            </span>
                        </div>
                    </div>

                    <!-- Bulk controls -->
                    <div style="display:flex; gap:10px; flex-wrap:wrap; margin-bottom:18px; align-items:center;">
                        <button class="btn-ghost btn-sm" onclick="setAll(true)">
                            <i class="bi bi-check-all"></i> Mark All Present
                        </button>
                        <button class="btn-ghost btn-sm" onclick="setAll(false)">
                            <i class="bi bi-x-all"></i> Mark All Absent
                        </button>
                        <button class="btn-ghost btn-sm" onclick="setByRole('PRO', true)">
                            <i class="bi bi-person-badge"></i> PRO Only
                        </button>
                        <button class="btn-ghost btn-sm" onclick="setByRole('PRESIDENT', true)">
                            <i class="bi bi-person-vcard"></i> President
                        </button>
                        <span style="font-size:12px; color:#5a6a80; margin-left:auto;">
                            <i class="bi bi-info-circle"></i> Toggle individual records live, then click <strong>Save All Changes</strong> to persist.
                        </span>
                    </div>

                    <form method="POST" action="ga-attendance.php?session_id=<?php echo $activeSessionId; ?>" id="attForm">
                        <input type="hidden" name="csrf_token"  value="<?php echo $_SESSION["csrf_token"]; ?>">
                        <input type="hidden" name="action"      value="save_attendance">
                        <input type="hidden" name="session_id"  value="<?php echo $activeSessionId; ?>">

                        <div class="card">
                        <div class="info-section">
                            <div class="att-list" style="gap:2px;">
                            <?php foreach ($allUsers as $u): ?>
                                <?php
                                    $isPresent     = !empty($presentSet[(int)$u["id"]]);
                                    $roleName      = $u["role"] ?? "STUDENT";
                                    $roleClass     = roleClass(str_replace(" ", "", $roleName));
                                    $initial       = strtoupper(substr($u["first_name"], 0, 1) . substr($u["last_name"], 0, 1));
                                    $dept          = $u["department"] ?? "";
                                ?>
                                <div class="att-row <?php echo $isPresent ? "present" : ""; ?>"
                                     id="att-row-<?php echo $u["id"]; ?>">
                                    <div class="att-avatar"><?php echo $initial; ?></div>
                                    <div class="att-user-info">
                                        <div class="att-user-name">
                                            <span class="role-badge-sm <?php echo $roleClass; ?>">
                                                <?php echo $roleName; ?>
                                            </span>
                                            <?php echo htmlspecialchars($u["first_name"] . " " . $u["last_name"]); ?>
                                        </div>
                                        <div class="att-user-sub">
                                            <?php echo $dept ? htmlspecialchars($dept) : ""; ?>
                                            <?php echo $dept ? " · " : ""; ?>
                                            <?php echo htmlspecialchars($u["email"] ?? ""); ?>
                                        </div>
                                    </div>
                                    <div class="toggle-wrap">
                                        <label class="toggle">
                                            <input type="checkbox" name="present[]" value="<?php echo $u["id"]; ?>"
                                                   <?php echo $isPresent ? "checked" : ""; ?>
                                                   onchange="toggleRow(<?php echo $u["id"]; ?>, this.checked)">
                                            <span class="toggle-slider"></span>
                                        </label>
                                        <span class="att-label" id="att-label-<?php echo $u["id"]; ?>">
                                            <?php echo $isPresent ? "Present" : "Absent"; ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            </div>
                        </div>
                        </div>
                    </form>
                    <?php endif; ?>

                </div>
            </main>
        </div>
    </div>

    <script src="../assets/js/sidebar.js"></script>
    <script src="../assets/js/loader-service.js"></script>

    <script>
        function toggleRow(userId, isPresent) {
            const row   = document.getElementById("att-row-" + userId);
            const label = document.getElementById("att-label-" + userId);
            if (!row) return;
            if (isPresent) {
                row.classList.add("present");
                label.textContent = "Present";
            } else {
                row.classList.remove("present");
                label.textContent = "Absent";
            }
        }

        function setAll(present) {
            const checkboxes = document.querySelectorAll('input[name="present[]"]');
            checkboxes.forEach(cb => {
                cb.checked = present;
                toggleRow(parseInt(cb.value), present);
            });
            // Flash feedback
            const rowEl = document.querySelectorAll(".att-row");
            if (present) {
                rowEl.forEach(r => r.style.borderRadius = "7px");
            }
        }

        function setByRole(roleName, present) {
            document.querySelectorAll('.att-row').forEach(row => {
                const roleBadge = row.querySelector('.role-badge-sm');
                const isMatch  = roleBadge && roleBadge.textContent.trim() === roleName;
                const cb       = row.querySelector('input[name="present[]"]');
                if (cb && isMatch) {
                    cb.checked = present;
                    toggleRow(parseInt(cb.value), present);
                }
            });
        }

        function showToast(msg) {
            const t = document.createElement("div");
            t.className = "toast";
            t.textContent = msg;
            document.body.appendChild(t);
            setTimeout(() => t.remove(), 2400);
        }
    </script>
</body>
</html>
