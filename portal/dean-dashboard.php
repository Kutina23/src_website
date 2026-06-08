<?php
session_start();
require_once '../config/database.php';
require_once '../config/functions.php';

if (!isLogged()) {
    header('Location: login.php');
    exit;
}

$currentRole = currentRole();
$currentUser  = currentUser();

if ($currentRole !== 'DEAN') {
    header('Location: index.php');
    exit;
}

$pageTitle = 'Dean Management Portal';

// ── Dean Personal Info ──
$deanUser = $currentUser;
$deanRow = db()->fetch("
    SELECT u.*, r.name as role_name,
           cm.position, cm.term_start, cm.term_end, cm.is_active as term_active,
           cm.profile_image_id, m.file_path as profile_image_path
    FROM users u
    JOIN roles r ON u.role_id = r.id
    LEFT JOIN council_members cm ON cm.user_id = u.id AND cm.position = 'DEAN'
    LEFT JOIN media m ON cm.profile_image_id = m.id
    WHERE u.id = ?
    LIMIT 1
", [$deanUser['id']]);

$fullName   = htmlspecialchars(($deanRow['first_name'] ?? '') . ' ' . ($deanRow['last_name'] ?? ''));
$email      = htmlspecialchars($deanRow['email'] ?? '');
$studentId  = htmlspecialchars($deanRow['student_id'] ?? '—');
$phone      = htmlspecialchars($deanRow['phone'] ?? '—');
$position   = htmlspecialchars($deanRow['position'] ?? 'Dean of Students');
$termStart  = $deanRow['term_start'] ?? '';
$termEnd    = $deanRow['term_end'] ?? '';
$profileImg = $deanRow['profile_image_path'] ?? null;
$termActive = (bool)($deanRow['term_active'] ?? 0);

$termLabel  = '';
if ($termStart && $termEnd) {
    $termLabel = date('M Y', strtotime($termStart)) . ' \u2014 ' . date('M Y', strtotime($termEnd));
} elseif ($termStart) {
    $termLabel = 'From ' . date('M Y', strtotime($termStart));
}

// ── Stats ──
$totalGA       = db()->fetch("SELECT COUNT(*) as c FROM ga_sessions")['c'] ?? 0;
$upcomingGA    = db()->fetch("SELECT COUNT(*) as c FROM ga_sessions WHERE status = 'SCHEDULED'")['c'] ?? 0;
$totalReports  = db()->fetch("SELECT COUNT(*) as c FROM ga_resolutions")['c'] ?? 0;
$publishedNews = db()->fetch("SELECT COUNT(*) as c FROM news WHERE status = 'PUBLISHED'")['c'] ?? 0;
$activeClubs   = db()->fetch("SELECT COUNT(*) as c FROM clubs WHERE status = 'ACTIVE'")['c'] ?? 0;

// Get halls and member counts
$hallsWithMembers = db()->fetchAll("
    SELECT h.id, h.name, 
           COUNT(hm.id) as member_count
    FROM halls h
    LEFT JOIN hall_members hm ON h.id = hm.hall_id
    GROUP BY h.id, h.name
    ORDER BY h.name ASC
");

// ── Upcoming GA Sessions ──
$upcomingSessions = db()->fetchAll("
    SELECT id, session_type, title, scheduled_datetime, location, status
    FROM ga_sessions
    WHERE status = 'SCHEDULED'
    ORDER BY scheduled_datetime ASC
    LIMIT 4
");

// ── Recent News ──
$recentNews = db()->fetchAll("
    SELECT n.id, n.title, n.category, n.published_at,
           CONCAT(u.first_name,' ',u.last_name) as author
    FROM news n
    JOIN users u ON n.author_id = u.id
    WHERE n.status = 'PUBLISHED'
    ORDER BY n.published_at DESC
    LIMIT 5
");

// ── Recent Resolutions ──
$recentResolutions = db()->fetchAll("
    SELECT r.id, r.resolution_no, r.title, r.category, r.status,
           r.vote_for, r.vote_against, r.vote_abstain,
           s.session_type, s.title as session_title
    FROM ga_resolutions r
    JOIN ga_sessions s ON r.session_id = s.id
    ORDER BY r.created_at DESC
    LIMIT 5
");

// ── Open Complaints ──
$openComplaints = db()->fetch("SELECT COUNT(*) as c FROM complaints WHERE status = 'OPEN'")['c'] ?? 0;

// ── Council Members count ──
$totalCouncil = db()->fetch("SELECT COUNT(*) as c FROM council_members WHERE is_active = 1")['c'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> | DHLTU SRC</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300,0,400,0,600,0,700;1,300,1,400,1,600,1,700&family=Outfit:wght@200;300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <script>
        window.currentUserRole = '<?php echo $currentRole; ?>';
    </script>
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
            require_once '../include/nav-links.php';
            $nav = new NavigationRBAC($currentRole);
            echo $nav->renderNavigation();
            ?>

            <div class="sidebar-user">
                <div class="user-avatar"><?php echo strtoupper(substr($currentUser['first_name'], 0, 1) . substr($currentUser['last_name'], 0, 1)); ?></div>
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']); ?></div>
                    <div class="user-role"><span class="role-badge monitor"><?php echo $currentRole; ?></span></div>
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
                    <a href="../logout.php" class="header-btn" aria-label="Logout">
                        <i class="bi bi-box-arrow-right"></i>
                    </a>
                </div>
            </header>

            <main class="content-body">
                <div class="dashboard-container">

                    <!-- ── Welcome Banner ── -->
                    <div class="dashboard-header-section">
                        <h2 class="dashboard-title">Welcome, <?php echo htmlspecialchars($currentUser['first_name']); ?>!</h2>
                        <p class="dashboard-subtitle">
                            <?php echo $termLabel ? 'Term: ' . htmlspecialchars($termLabel) : 'Student Affairs &amp; Academic Oversight'; ?>
                        </p>
                    </div>

                    <!-- ── Stats Grid ── -->
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-card-header">
                                <span class="stat-card-label">GA Sessions</span>
                                <div class="stat-card-icon info">
                                    <i class="bi bi-calendar-event"></i>
                                </div>
                            </div>
                            <div class="stat-card-value"><?php echo (int)$totalGA; ?></div>
                            <div class="stat-card-label"><?php echo (int)$upcomingGA; ?> upcoming</div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-card-header">
                                <span class="stat-card-label">Resolutions</span>
                                <div class="stat-card-icon primary">
                                    <i class="bi bi-file-earmark-text"></i>
                                </div>
                            </div>
                            <div class="stat-card-value"><?php echo (int)$totalReports; ?></div>
                            <div class="stat-card-label">Total on record</div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-card-header">
                                <span class="stat-card-label">Active Clubs</span>
                                <div class="stat-card-icon success">
                                    <i class="bi bi-collection-play"></i>
                                </div>
                            </div>
                            <div class="stat-card-value"><?php echo (int)$activeClubs; ?></div>
                            <div class="stat-card-label">Registered clubs</div>
                        </div>

                         <div class="stat-card">
                             <div class="stat-card-header">
                                 <span class="stat-card-label">Open Complaints</span>
                                 <div class="stat-card-icon warning">
                                     <i class="bi bi-clipboard"></i>
                                 </div>
                             </div>
                             <div class="stat-card-value"><?php echo (int)$openComplaints; ?></div>
                             <div class="stat-card-label">Pending resolution</div>
                         </div>

                          <!-- Halls Stat Card -->
                          <div class="stat-card">
                              <div class="stat-card-header">
                                  <span class="stat-card-label">Halls</span>
                                  <div class="stat-card-icon success">
                                      <i class="bi bi-buildings"></i>
                                  </div>
                              </div>
                              <div class="stat-card-value"><?php echo count($hallsWithMembers); ?></div>
                              <div class="stat-card-label">Total halls</div>
                          </div>
                    </div>

                    <!-- ── Two-Column Layout ── -->
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:24px;">

                        <!-- ── Dean Info Card ── -->
                        <div class="table-container" style="padding:24px;">
                            <h3 style="padding:0 0 4px 0;margin:0;color:#C9A84C;"><i class="bi bi-person-badge-fill"></i> My Information</h3>
                            <p class="form-text" style="margin-bottom:20px;font-size:13px;color:#8A9BB8;">Personal profile &amp; term details</p>

                            <div style="display:flex;gap:16px;align-items:center;margin-bottom:20px;padding-bottom:20px;border-bottom:1px solid rgba(201,168,76,0.1);">
                                <?php if ($profileImg): ?>
                                    <img src="../<?php echo htmlspecialchars($profileImg); ?>"
                                         style="width:72px;height:72px;border-radius:50%;object-fit:cover;border:3px solid #C9A84C;flex-shrink:0;">
                                <?php else: ?>
                                    <div style="width:72px;height:72px;border-radius:50%;background:linear-gradient(135deg,#C9A84C,#E8C97A);display:flex;align-items:center;justify-content:center;color:#fff;font-size:28px;font-weight:700;flex-shrink:0;">
                                        <?php echo strtoupper(substr($fullName ?: 'DN', 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <div style="font-size:17px;font-weight:600;color:#C9A84C;"><?php echo $fullName ?: 'Dean'; ?></div>
                                    <div style="font-size:13px;color:#8A9BB8;margin-top:2px;"><?php echo $position; ?></div>
                                    <div style="margin-top:4px;">
                                        <span class="badge <?php echo $termActive ? 'badge-active' : 'badge-inactive'; ?>">
                                            <?php echo $termActive ? 'Active Term' : 'Inactive'; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div style="display:flex;flex-direction:column;gap:12px;">
                                <div style="display:flex;align-items:center;gap:10px;font-size:13px;">
                                    <i class="bi bi-envelope" style="color:#C9A84C;font-size:14px;min-width:16px;"></i>
                                    <span style="color:#8A9BB8;min-width:70px;">Email</span>
                                    <span style="color:#3f3e3d;font-weight:500;"><?php echo $email ?: '—'; ?></span>
                                </div>
                                <div style="display:flex;align-items:center;gap:10px;font-size:13px;">
                                    <i class="bi bi-person-vcard" style="color:#C9A84C;font-size:14px;min-width:16px;"></i>
                                    <span style="color:#8A9BB8;min-width:70px;">Student ID</span>
                                    <span style="color:#3f3e3d;font-weight:500;"><?php echo $studentId; ?></span>
                                </div>
                                <div style="display:flex;align-items:center;gap:10px;font-size:13px;">
                                    <i class="bi bi-phone" style="color:#C9A84C;font-size:14px;min-width:16px;"></i>
                                    <span style="color:#8A9BB8;min-width:70px;">Phone</span>
                                    <span style="color:#3f3e3d;font-weight:500;"><?php echo $phone; ?></span>
                                </div>
                                <div style="display:flex;align-items:center;gap:10px;font-size:13px;">
                                    <i class="bi bi-calendar-range" style="color:#C9A84C;font-size:14px;min-width:16px;"></i>
                                    <span style="color:#8A9BB8;min-width:70px;">Term</span>
                                    <span style="color:#3f3e3d;font-weight:500;"><?php echo $termLabel ?: '—'; ?></span>
                                </div>
                            </div>

                            <div style="margin-top:20px;padding:14px 16px;background:rgba(201,168,76,0.07);border-left:3px solid #C9A84C;border-radius:4px;">
                                <p style="margin:0;font-size:13px;font-style:italic;color:#555;line-height:1.5;">
                                    Oversight of student welfare, academic discipline, and day-to-day student affairs.
                                </p>
                            </div>
                        </div>

                        <!-- ── Quick Access ── -->
                        <div class="table-container" style="padding:24px;display:flex;flex-direction:column;">
                            <h3 style="padding:0 0 4px 0;margin:0;color:#C9A84C;"><i class="bi bi-tools"></i> Quick Access</h3>
                            <p class="form-text" style="margin-bottom:20px;font-size:13px;color:#8A9BB8;">Shortcuts to managed areas</p>

                            <div style="flex:1;display:flex;flex-direction:column;gap:10px;">
                                <a href="ga-sessions.php" class="btn btn-primary" style="width:100%;justify-content:center;text-decoration:none;">
                                    <i class="bi bi-calendar-event"></i> GA Sessions
                                </a>
                                <a href="ga-minutes.php" class="btn btn-secondary" style="width:100%;justify-content:center;text-decoration:none;">
                                    <i class="bi bi-file-earmark-pdf"></i> GA Minutes
                                </a>
                                <a href="ga-resolutions.php" class="btn btn-secondary" style="width:100%;justify-content:center;text-decoration:none;">
                                    <i class="bi bi-file-earmark-text"></i> Resolutions &amp; Motions
                                </a>
                                <a href="reports.php" class="btn btn-secondary" style="width:100%;justify-content:center;text-decoration:none;">
                                    <i class="bi bi-bar-chart"></i> Analytics &amp; Reports
                                </a>
                                 <a href="council.php" class="btn btn-secondary" style="width:100%;justify-content:center;text-decoration:none;">
                                     <i class="bi bi-people"></i> Council Members
                                 </a>
                             </div>
                         </div>

                         <!-- ── Halls Overview ── -->
                         <div class="table-container" style="margin-bottom:24px;">
                             <div style="padding:16px 20px;border-bottom:1px solid rgba(138,155,184,0.1);display:flex;justify-content:space-between;align-items:center;">
                                 <h3 style="margin:0;font-size:15px;font-weight:600;color:#3f3e3d;"><i class="bi bi-buildings"></i> Halls Overview</h3>
                                 <a href="../halls.php" class="btn btn-sm btn-secondary" style="padding:4px 8px;font-size:11px;text-decoration:none;">View All Halls</a>
                             </div>
                             <div style="padding:4px 0;">
                                 <?php if (empty($hallsWithMembers)): ?>
                                     <div style="padding:32px 20px;text-align:center;color:#8A9BB8;font-size:13px;">No halls found</div>
                                 <?php else: ?>
                                     <?php foreach ($hallsWithMembers as $hall): ?>
                                         <div style="padding:12px 20px;border-bottom:1px solid rgba(201,168,76,0.06);transition:background 0.15s ease;"
                                              onmouseover="this.style.background='rgba(201,168,76,0.04)'"
                                              onmouseout="this.style.background='transparent'">
                                             <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
                                                 <span style="font-size:11px;color:#8A9BB8;">Members</span>
                                                 <span class="badge badge-active" style="font-size:9px;"><?php echo (int)$hall['member_count']; ?></span>
                                             </div>
                                             <div style="font-size:13px;font-weight:500;color:#3f3e3d;line-height:1.4;">
                                                 <?php echo htmlspecialchars($hall['name']); ?>
                                             </div>
                                         </div>
                                     <?php endforeach; ?>
                                 <?php endif; ?>
                             </div>
                         </div>

                     <!-- ── Upcoming GA Sessions ── -->
                    <div class="table-container" style="margin-bottom:24px;">
                        <div style="padding:16px 20px;border-bottom:1px solid rgba(138,155,184,0.1);display:flex;justify-content:space-between;align-items:center;">
                            <h3 style="margin:0;font-size:15px;font-weight:600;color:#3f3e3d;"><i class="bi bi-calendar-event" style="color:#C9A84C;"></i> Upcoming GA Sessions</h3>
                            <a href="ga-sessions.php" class="btn btn-sm btn-secondary" style="padding:4px 8px;font-size:11px;text-decoration:none;">View All</a>
                        </div>
                        <div style="padding:4px 0;">
                            <?php if (empty($upcomingSessions)): ?>
                                <div style="padding:32px 20px;text-align:center;color:#8A9BB8;font-size:13px;">No upcoming sessions</div>
                            <?php else: ?>
                                <?php foreach ($upcomingSessions as $session): ?>
                                    <?php
                                        $iLabel = match($session['session_type']) {
                                            'ANNUAL'     => 'Annual',
                                            'EMERGENCY'  => 'Emergency',
                                            'SPECIAL'    => 'Special',
                                            default      => ucfirst(strtolower($session['session_type'])),
                                        };
                                        $iClass = match($session['session_type']) {
                                            'ANNUAL'     => 'badge-active',
                                            'EMERGENCY'  => 'badge-pending',
                                            'SPECIAL'    => 'badge-role',
                                            default      => 'badge-role',
                                        };
                                        $when  = formatDateTime($session['scheduled_datetime'], 'M d  h:i A');
                                    ?>
                                    <div style="padding:12px 20px;border-bottom:1px solid rgba(201,168,76,0.06);transition:background 0.15s ease;"
                                         onmouseover="this.style.background='rgba(201,168,76,0.04)'"
                                         onmouseout="this.style.background='transparent'">
                                        <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
                                            <span class="badge <?php echo $iClass; ?>" style="font-size:9px;"><?php echo $iLabel; ?></span>
                                            <span style="font-size:11px;color:#8A9BB8;"><?php echo $when; ?></span>
                                        </div>
                                        <div style="font-size:13px;font-weight:500;color:#3f3e3d;line-height:1.4;">
                                            <?php echo htmlspecialchars($session['title']); ?>
                                        </div>
                                        <div style="font-size:11px;color:#8A9BB8;margin-top:2px;">
                                            <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($session['location'] ?: 'TBD'); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- ── Two-column: News & Resolutions ── -->
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:24px;">

                        <!-- ── Recent News ── -->
                        <div class="table-container" style="overflow:hidden;">
                            <div style="padding:16px 20px;border-bottom:1px solid rgba(138,155,184,0.1);display:flex;justify-content:space-between;align-items:center;">
                                <h3 style="margin:0;font-size:15px;font-weight:600;"><i class="bi bi-newspaper" style="color:#C9A84C;"></i> Latest News</h3>
                                <a href="reports.php" class="btn btn-sm btn-secondary" style="padding:4px 8px;font-size:11px;text-decoration:none;">View All</a>
                            </div>
                            <div style="padding:4px 0;">
                                <?php if (empty($recentNews)): ?>
                                    <div style="padding:32px 20px;text-align:center;color:#8A9BB8;font-size:13px;">No articles yet</div>
                                <?php else: ?>
                                    <?php foreach ($recentNews as $news): ?>
                                        <?php
                                            $catClass = match($news['category']) {
                                                'NEWS'        => 'badge-role',
                                                'EVENT'       => 'badge-active',
                                                'ANNOUNCEMENT'=> 'badge-pending',
                                                default       => 'badge-role',
                                            };
                                            $published = formatDate($news['published_at'], 'M d, Y');
                                        ?>
                                        <div style="padding:12px 20px;border-bottom:1px solid rgba(201,168,76,0.06);transition:background 0.15s ease;cursor:default;"
                                             onmouseover="this.style.background='rgba(201,168,76,0.04)'"
                                             onmouseout="this.style.background='transparent'">
                                            <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
                                                <span class="badge <?php echo $catClass; ?>" style="font-size:9px;"><?php echo htmlspecialchars($news['category'] ?? 'NEWS'); ?></span>
                                                <span style="font-size:11px;color:#8A9BB8;"><?php echo $published; ?></span>
                                            </div>
                                            <div style="font-size:13px;font-weight:500;color:#3f3e3d;line-height:1.4;">
                                                <?php echo htmlspecialchars($news['title']); ?>
                                            </div>
                                            <div style="font-size:11px;color:#8A9BB8;margin-top:2px;"><?php echo htmlspecialchars($news['author']); ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- ── Recent Resolutions ── -->
                        <div class="table-container" style="overflow:hidden;">
                            <div style="padding:16px 20px;border-bottom:1px solid rgba(138,155,184,0.1);display:flex;justify-content:space-between;align-items:center;">
                                <h3 style="margin:0;font-size:15px;font-weight:600;"><i class="bi bi-file-earmark-text" style="color:#C9A84C;"></i> Recent Resolutions</h3>
                                <a href="ga-resolutions.php" class="btn btn-sm btn-secondary" style="padding:4px 8px;font-size:11px;text-decoration:none;">View All</a>
                            </div>
                            <div style="padding:4px 0;">
                                <?php if (empty($recentResolutions)): ?>
                                    <div style="padding:32px 20px;text-align:center;color:#8A9BB8;font-size:13px;">No resolutions on record</div>
                                <?php else: ?>
                                    <?php foreach ($recentResolutions as $res): ?>
                                        <?php
                                            $rStatusClass = match($res['status']) {
                                                'PASSED'   => 'badge-active',
                                                'REJECTED' => 'badge-inactive',
                                                'PENDING'  => 'badge-pending',
                                                'TABLED'   => 'badge-inactive',
                                                'WITHDRAWN'=> 'badge-inactive',
                                                default    => 'badge-role',
                                            };
                                            $rCatClass = match($res['category']) {
                                                'RESOLUTION' => 'badge-role',
                                                'MOTION'     => 'badge-active',
                                                'AMENDMENT'  => 'badge-pending',
                                                'DECLARATION'=> 'badge-role',
                                                default      => 'badge-role',
                                            };
                                        ?>
                                        <div style="padding:12px 20px;border-bottom:1px solid rgba(201,168,76,0.06);transition:background 0.15s ease;"
                                             onmouseover="this.style.background='rgba(201,168,76,0.04)'"
                                             onmouseout="this.style.background='transparent'">
                                            <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
                                                <span class="badge <?php echo $rCatClass; ?>" style="font-size:9px;"><?php echo htmlspecialchars($res['category']); ?></span>
                                                <span style="font-size:9px;color:#C9A84C;font-weight:600;"><?php echo htmlspecialchars($res['resolution_no']); ?></span>
                                                <span style="font-size:11px;color:#8A9BB8;"><?php echo htmlspecialchars($res['session_type'] ?? ''); ?></span>
                                            </div>
                                            <div style="font-size:13px;font-weight:500;color:#3f3e3d;line-height:1.4;">
                                                <?php echo htmlspecialchars($res['title']); ?>
                                            </div>
                                            <div style="display:flex;align-items:center;gap:12px;margin-top:4px;font-size:11px;color:#8A9BB8;">
                                                <span><i class="bi bi-hand-thumbs-up" style="margin-right:2px;"></i><?php echo (int)$res['vote_for']; ?></span>
                                                <span><i class="bi bi-hand-thumbs-down" style="margin-right:2px;"></i><?php echo (int)$res['vote_against']; ?></span>
                                                <span class="badge <?php echo $rStatusClass; ?>" style="font-size:9px;"><?php echo htmlspecialchars($res['status']); ?></span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- ── Quick Stat Grid ── -->
                    <div class="stats-grid">
                        <a href="ga-sessions.php" class="text-decoration-none">
                        <div class="stat-card" style="cursor:pointer;">
                            <div class="stat-card-header">
                                <span class="stat-card-label">Manage GA</span>
                                <div class="stat-card-icon info">
                                    <i class="bi bi-calendar-check"></i>
                                </div>
                            </div>
                            <div class="stat-card-value"><?php echo (int)$upcomingGA; ?></div>
                            <div class="stat-card-label">Sessions to administer</div>
                        </div>
                        </a>
                        <div class="stat-card">
                            <div class="stat-card-header">
                                <span class="stat-card-label">Council</span>
                                <div class="stat-card-icon primary">
                                    <i class="bi bi-people"></i>
                                </div>
                            </div>
                            <div class="stat-card-value"><?php echo (int)$totalCouncil; ?></div>
                            <div class="stat-card-label">Members in council</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-card-header">
                                <span class="stat-card-label">Reports</span>
                                <div class="stat-card-icon warning">
                                    <i class="bi bi-file-earmark-break"></i>
                                </div>
                            </div>
                            <div class="stat-card-value"><?php echo (int)$totalReports; ?></div>
                            <div class="stat-card-label">Total resolutions</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-card-header">
                                <span class="stat-card-label">Clubs</span>
                                <div class="stat-card-icon success">
                                    <i class="bi bi-collection-play"></i>
                                </div>
                            </div>
                            <div class="stat-card-value"><?php echo (int)$activeClubs; ?></div>
                            <div class="stat-card-label">Registered clubs</div>
                        </div>
                    </div>

                </div>
            </main>
        </div>
    </div>

    <script src="../assets/js/sidebar.js"></script>
</body>
</html>
