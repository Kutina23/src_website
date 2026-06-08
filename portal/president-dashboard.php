<?php
session_start();
require_once '../config/database.php';
require_once '../config/functions.php';

if (!isLogged()) {
    header('Location: login.php');
    exit;
}

$currentRole = currentRole();
$currentUser = currentUser();

if ($currentRole !== 'PRESIDENT') {
    header('Location: index.php');
    exit;
}

$pageTitle = 'My Portal';

// ── President Personal Info ──
$presidentUser = $currentUser;
$presidentUserRow = db()->fetch("
    SELECT u.*, r.name as role_name,
           cm.position, cm.term_start, cm.term_end, cm.is_active as term_active,
           cm.profile_image_id, m.file_path as profile_image_path
    FROM users u
    JOIN roles r ON u.role_id = r.id
    LEFT JOIN council_members cm ON cm.user_id = u.id AND cm.position = 'PRESIDENT'
    LEFT JOIN media m ON cm.profile_image_id = m.id
    WHERE u.id = ?
    LIMIT 1
", [$presidentUser['id']]);

$fullName   = htmlspecialchars(($presidentUserRow['first_name'] ?? '') . ' ' . ($presidentUserRow['last_name'] ?? ''));
$email      = htmlspecialchars($presidentUserRow['email'] ?? '');
$studentId  = htmlspecialchars($presidentUserRow['student_id'] ?? '—');
$phone      = htmlspecialchars($presidentUserRow['phone'] ?? '—');
$position   = htmlspecialchars($presidentUserRow['position'] ?? 'President');
$termStart  = $presidentUserRow['term_start'] ?? '';
$termEnd    = $presidentUserRow['term_end'] ?? '';
$profileImg = $presidentUserRow['profile_image_path'] ?? null;
$termActive = (bool)($presidentUserRow['term_active'] ?? 0);

$termLabel = '';
if ($termStart && $termEnd) {
    $termLabel = date('M Y', strtotime($termStart)) . ' — ' . date('M Y', strtotime($termEnd));
} elseif ($termStart) {
    $termLabel = 'From ' . date('M Y', strtotime($termStart));
}

// ── Site quote for president hero ──
$quoteRow = db()->fetch("SELECT col_value FROM site_settings WHERE col_key = 'president_quote'");
$postfixRow = db()->fetch("SELECT col_value FROM site_settings WHERE col_key = 'president_postfix'");
$presidentQuote  = $quoteRow  ? htmlspecialchars($quoteRow['col_value'])  : '';
$presidentPostfix = $postfixRow ? htmlspecialchars($postfixRow['col_value']) : '';

// ── Stats ──
$totalGA        = db()->fetch("SELECT COUNT(*) as c FROM ga_sessions")['c'];
$upcomingGA     = db()->fetch("SELECT COUNT(*) as c FROM ga_sessions WHERE status = 'SCHEDULED'")['c'];
$totalCommittees = db()->fetch("SELECT COUNT(*) as c FROM committees c JOIN committee_members cm ON c.id = cm.committee_id WHERE cm.user_id = ?", [$presidentUser['id']])['c'];
$chairedCommittees = db()->fetch("SELECT COUNT(*) as c FROM committees WHERE chair_id = ?", [$presidentUser['id']])['c'];
$totalNews      = db()->fetch("SELECT COUNT(*) as c FROM news WHERE status = 'PUBLISHED'")['c'];
$totalClubs     = db()->fetch("SELECT COUNT(*) as c FROM clubs WHERE status = 'ACTIVE'")['c'];
$openComplaints = db()->fetch("SELECT COUNT(*) as c FROM complaints WHERE status = 'OPEN' AND assigned_to = ?", [$presidentUser['id']])['c'] ?? 0;

// Get halls and member counts
$hallsWithMembers = db()->fetchAll("
    SELECT h.id, h.name, 
           COUNT(hm.id) as member_count
    FROM halls h
    LEFT JOIN hall_members hm ON h.id = hm.hall_id
    GROUP BY h.id, h.name
    ORDER BY h.name ASC
");

// ── Recent News ──
$recentNews = db()->fetchAll("
    SELECT n.id, n.title, n.category, n.published_at,
           CONCAT(u.first_name,' ',u.last_name) as author,
           m.file_path as featured_image
    FROM news n
    JOIN users u ON n.author_id = u.id
    LEFT JOIN media m ON n.featured_media_id = m.id
    WHERE n.status = 'PUBLISHED'
    ORDER BY n.published_at DESC
    LIMIT 5
");

// ── Upcoming GA Sessions ──
$upcomingSessions = db()->fetchAll("
    SELECT id, session_type, title, scheduled_datetime, location, status
    FROM ga_sessions
    WHERE status = 'SCHEDULED'
    ORDER BY scheduled_datetime ASC
    LIMIT 4
");

// ── Committees Chaired ──
$myCommittees = db()->fetchAll("
    SELECT c.id, c.name, c.description, c.meeting_day, c.meeting_time, c.meeting_location, c.is_active,
           'CHAIR' as my_role
    FROM committees c
    WHERE c.chair_id = ?
    UNION ALL
    SELECT c.id, c.name, c.description, c.meeting_day, c.meeting_time, c.meeting_location, c.is_active,
           cm.role_type as my_role
    FROM committees c
    JOIN committee_members cm ON c.id = cm.committee_id
    WHERE cm.user_id = ? AND cm.role_type = 'MEMBER'
    ORDER BY my_role, name
", [$presidentUser['id'], $presidentUser['id']]);
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
                <h1 class="header-title">My Portal</h1>
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
                            <?php echo $termLabel ? 'Term: ' . htmlspecialchars($termLabel) : 'SRC President Portal'; ?>
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
                                <span class="stat-card-label">Committees</span>
                                <div class="stat-card-icon primary">
                                    <i class="bi bi-people"></i>
                                </div>
                            </div>
                            <div class="stat-card-value"><?php echo (int)$myCommittees ? count($myCommittees) : 0; ?></div>
                            <div class="stat-card-label"><?php echo (int)$chairedCommittees; ?> chaired</div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-card-header">
                                <span class="stat-card-label">Active Clubs</span>
                                <div class="stat-card-icon success">
                                    <i class="bi bi-collection-play"></i>
                                </div>
                            </div>
                            <div class="stat-card-value"><?php echo (int)$totalClubs; ?></div>
                            <div class="stat-card-label">Registered clubs</div>
                        </div>

                         <div class="stat-card">
                             <div class="stat-card-header">
                                 <span class="stat-card-label">News &</span>
                                 <div class="stat-card-icon warning">
                                     <i class="bi bi-newspaper"></i>
                                 </div>
                             </div>
                             <div class="stat-card-value"><?php echo (int)$totalNews; ?></div>
                             <div class="stat-card-label">Published articles</div>
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

                        <!-- ── President Info Card ── -->
                        <div class="table-container" style="padding:24px;">
                            <h3 style="padding:0 0 4px 0;margin:0;color:#C9A84C;"><i class="bi bi-person-badge-fill"></i> My Information</h3>
                            <p class="form-text" style="margin-bottom:20px;font-size:13px;color:#8A9BB8;">Personal &amp; term details</p>

                            <div style="display:flex;gap:16px;align-items:center;margin-bottom:20px;padding-bottom:20px;border-bottom:1px solid rgba(201,168,76,0.1);">
                                <?php if ($profileImg): ?>
                                    <img src="../<?php echo htmlspecialchars($profileImg); ?>"
                                         style="width:72px;height:72px;border-radius:50%;object-fit:cover;border:3px solid #C9A84C;flex-shrink:0;">
                                <?php else: ?>
                                    <div style="width:72px;height:72px;border-radius:50%;background:linear-gradient(135deg,#C9A84C,#E8C97A);display:flex;align-items:center;justify-content:center;color:#fff;font-size:28px;font-weight:700;flex-shrink:0;">
                                        <?php echo strtoupper(substr($fullName ?: 'PR', 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <div style="font-size:17px;font-weight:600;color:#C9A84C;"><?php echo $fullName ?: 'President'; ?></div>
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
                                    <i class="bi bi-calendar-range" style="color:#C9A84C;font-size:14px;min-width:16px;"></i>
                                    <span style="color:#8A9BB8;min-width:70px;">Term</span>
                                    <span style="color:#3f3e3d;font-weight:500;"><?php echo $termLabel ?: '—'; ?></span>
                                </div>
                                <?php if ($presidentPostfix): ?>
                                <div style="display:flex;align-items:center;gap:10px;font-size:13px;">
                                    <i class="bi bi-flag" style="color:#C9A84C;font-size:14px;min-width:16px;"></i>
                                    <span style="color:#8A9BB8;min-width:70px;">Session</span>
                                    <span style="color:#3f3e3d;font-weight:500;"><?php echo $presidentPostfix; ?></span>
                                </div>
                                <?php endif; ?>
                            </div>

                            <?php if ($presidentQuote): ?>
                            <div style="margin-top:20px;padding:14px 16px;background:rgba(201,168,76,0.07);border-left:3px solid #C9A84C;border-radius:4px;">
                                <p style="margin:0;font-size:13px;font-style:italic;color:#555;line-height:1.5;">
                                    "<?php echo $presidentQuote; ?>"
                                </p>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- ── President Message / Charter ── -->
                        <div class="table-container" style="padding:24px;display:flex;flex-direction:column;">
                            <h3 style="padding:0 0 4px 0;margin:0;color:#C9A84C;"><i class="bi bi-megaphone-fill"></i> President Message</h3>
                            <p class="form-text" style="margin-bottom:20px;font-size:13px;color:#8A9BB8;">A note to the student body</p>

                            <div style="flex:1;min-height:180px;background:rgba(201,168,76,0.04);border:1px solid rgba(201,168,76,0.1);border-radius:8px;padding:16px;margin-bottom:20px;display:flex;align-items:center;justify-content:center;text-align:center;">
                                <?php if ($presidentQuote): ?>
                                    <blockquote style="margin:0;font-size:14px;font-style:italic;color:#3f3e3d;line-height:1.6;">
                                        "<?php echo $presidentQuote; ?>"
                                    </blockquote>
                                <?php else: ?>
                                    <p style="margin:0;color:#8A9BB8;font-size:13px;">No president message has been set yet.</p>
                                <?php endif; ?>
                            </div>

                            <div style="display:flex;flex-direction:column;gap:10px;">
                                <?php if ($termActive): ?>
                                <button type="button" class="btn btn-primary" onclick="openMessageModal()" style="width:100%;">
                                    <i class="bi bi-pencil"></i> Edit Message
                                </button>
                                <?php endif; ?>
                                <a href="president-images.php" class="btn btn-secondary" style="text-align:center;justify-content:center;width:100%;">
                                    <i class="bi bi-images"></i> Manage Images
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- ── Chart Row: Admin quick stats ── -->
                    <div class="stats-grid" style="margin-bottom:24px;">
                        <div class="stat-card">
                            <div class="stat-card-header">
                                <span class="stat-card-label">Council Members</span>
                                <div class="stat-card-icon primary">
                                    <i class="bi bi-people"></i>
                                </div>
                            </div>
                            <div class="stat-card-value"><?php echo db()->fetch("SELECT COUNT(*) as c FROM council_members WHERE is_active = 1")['c'] ?? 0; ?></div>
                            <div class="stat-card-label">In council</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-card-header">
                                <span class="stat-card-label">Comm. Chaired</span>
                                <div class="stat-card-icon warning">
                                    <i class="bi bi-person-check"></i>
                                </div>
                            </div>
                            <div class="stat-card-value"><?php echo (int)$chairedCommittees; ?></div>
                            <div class="stat-card-label">Committee<?php echo (int)$chairedCommittees !== 1 ? 's' : ''; ?> you chair</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-card-header">
                                <span class="stat-card-label">Total Clubs</span>
                                <div class="stat-card-icon success">
                                    <i class="bi bi-collection-play"></i>
                                </div>
                            </div>
                            <div class="stat-card-value"><?php echo (int)$totalClubs; ?></div>
                            <div class="stat-card-label">Registered clubs</div>
                        </div>
                         <div class="stat-card">
                             <div class="stat-card-header">
                                 <span class="stat-card-label">Open Complaints</span>
                                 <div class="stat-card-icon info">
                                     <i class="bi bi-clipboard-check"></i>
                                 </div>
                             </div>
                             <div class="stat-card-value"><?php echo (int)$openComplaints; ?></div>
                             <div class="stat-card-label">Assigned to me</div>
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

                     <!-- ── Three-column: News, GA Sessions, Committees ── -->
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:24px;margin-bottom:24px;">

                        <!-- ── News ── -->
                        <div class="table-container" style="overflow:hidden;">
                            <div style="padding:16px 20px;border-bottom:1px solid rgba(138,155,184,0.1);display:flex;justify-content:space-between;align-items:center;">
                                <h3 style="margin:0;font-size:15px;font-weight:600;"><i class="bi bi-newspaper" style="color:#C9A84C;"></i> Latest News</h3>
                                <a href="news-admin.php" class="btn btn-sm btn-secondary" style="padding:4px 8px;font-size:11px;">View All</a>
                            </div>
                            <div style="padding:4px 0;">
                                <?php if (empty($recentNews)): ?>
                                    <div style="padding:32px 20px;text-align:center;color:#8A9BB8;font-size:13px;">
                                        No news articles yet
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($recentNews as $news): ?>
                                    <?php
                                        $catClass = match($news['category']) {
                                            'NEWS'       => 'badge-role',
                                            'EVENT'      => 'badge-active',
                                            'ANNOUNCEMENT' => 'badge-pending',
                                            default      => 'badge-role',
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

                        <!-- ── Upcoming GA Sessions ── -->
                        <div class="table-container" style="overflow:hidden;">
                            <div style="padding:16px 20px;border-bottom:1px solid rgba(138,155,184,0.1);display:flex;justify-content:space-between;align-items:center;">
                                <h3 style="margin:0;font-size:15px;font-weight:600;"><i class="bi bi-calendar-event" style="color:#C9A84C;"></i> Upcoming GA Sessions</h3>
                                <a href="ga-sessions.php" class="btn btn-sm btn-secondary" style="padding:4px 8px;font-size:11px;">View All</a>
                            </div>
                            <div style="padding:4px 0;">
                                <?php if (empty($upcomingSessions)): ?>
                                    <div style="padding:32px 20px;text-align:center;color:#8A9BB8;font-size:13px;">
                                        No upcoming sessions
                                    </div>
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

                        <!-- ── My Committees ── -->
                        <div class="table-container" style="overflow:hidden;">
                            <div style="padding:16px 20px;border-bottom:1px solid rgba(138,155,184,0.1);">
                                <h3 style="margin:0;font-size:15px;font-weight:600;"><i class="bi bi-people" style="color:#C9A84C;"></i> My Committees</h3>
                            </div>
                            <div style="padding:4px 0;">
                                <?php if (empty($myCommittees)): ?>
                                    <div style="padding:32px 20px;text-align:center;color:#8A9BB8;font-size:13px;">
                                        No committee assignments
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($myCommittees as $cmt): ?>
                                    <?php
                                        $roleTagClass = $cmt['my_role'] === 'CHAIR' ? 'badge-active' : 'badge-role';
                                        $dayLabel = $cmt['meeting_day'] ? strtoupper(substr($cmt['meeting_day'], 0, 3)) . ' ' . date('h:i A', strtotime($cmt['meeting_time'])) : 'TBD';
                                    ?>
                                        <div style="padding:12px 20px;border-bottom:1px solid rgba(201,168,76,0.06);transition:background 0.15s ease;"
                                             onmouseover="this.style.background='rgba(201,168,76,0.04)'"
                                             onmouseout="this.style.background='transparent'">
                                            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                                                <div style="font-size:13px;font-weight:600;color:#3f3e3d;">
                                                    <?php echo htmlspecialchars($cmt['name']); ?>
                                                </div>
                                                <span class="badge <?php echo $roleTagClass; ?>" style="font-size:9px;"><?php echo htmlspecialchars($cmt['my_role']); ?></span>
                                            </div>
                                            <div style="font-size:12px;color:#8A9BB8;display:flex;align-items:center;gap:6px;margin-top:2px;">
                                                <i class="bi bi-clock" style="font-size:11px;"></i>
                                                <?php echo htmlspecialchars($dayLabel); ?>
                                                <?php if ($cmt['meeting_location']): ?>
                                                <span style="margin:0 4px;">·</span>
                                                <i class="bi bi-geo-alt" style="font-size:11px;"></i>
                                                <?php echo htmlspecialchars($cmt['meeting_location']); ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- ── Quote Banner ── -->
                    <?php if ($presidentQuote): ?>
                    <div class="table-container" style="padding:28px 32px;margin-bottom:24px;background:linear-gradient(135deg,rgba(201,168,76,0.08),rgba(201,168,76,0.02));border-color:rgba(201,168,76,0.2);text-align:center;">
                        <i class="bi bi-quote" style="font-size:28px;color:#C9A84C;opacity:0.6;"></i>
                        <p style="margin:12px 0 8px;font-size:17px;font-style:italic;color:#3f3e3d;line-height:1.5;max-width:700px;margin-left:auto;margin-right:auto;">
                            "<?php echo $presidentQuote; ?>"
                        </p>
                        <span style="font-size:12px;color:#C9A84C;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;">
                            <?php echo $fullName ?: 'President'; ?> — <?php echo $presidentPostfix; ?>
                        </span>
                    </div>
                    <?php endif; ?>

                </div>
            </main>
        </div>
    </div>

    <!-- ── Edit Message Modal ── -->
    <div id="messageModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(10,22,40,0.75);z-index:1000;align-items:center;justify-content:center;backdrop-filter:blur(4px);">
        <div style="background:#fff;border-radius:12px;max-width:560px;width:90%;max-height:90vh;overflow-y:auto;">
            <div style="padding:20px 24px;border-bottom:1px solid #eee;display:flex;justify-content:space-between;align-items:center;">
                <h3 style="margin:0;font-size:18px;font-weight:600;color:#3f3e3d;"><i class="bi bi-pencil" style="color:#C9A84C;"></i> Edit President Message</h3>
                <button onclick="closeMessageModal()" style="background:none;border:none;font-size:24px;cursor:pointer;line-height:1;color:#8A9BB8;">&times;</button>
            </div>
            <form method="POST" action="president-images.php" style="padding:24px;">
                <div class="form-group">
                    <label class="form-label" style="color:#3f3e3d;">President Quote</label>
                    <input type="text" name="president_quote" id="modalPresidentQuote" class="form-input"
                           value="<?php echo htmlspecialchars($presidentQuote); ?>" placeholder="Enter president message…">
                    <small class="form-text" style="color:#8A9BB8;">This message appears on the President profile card.</small>
                </div>
                <div class="form-group">
                    <label class="form-label" style="color:#3f3e3d;">President Postfix</label>
                    <input type="text" name="president_postfix" id="modalPresidentPostfix" class="form-input"
                           value="<?php echo htmlspecialchars($presidentPostfix); ?>" placeholder="e.g. 2025/2026">
                </div>
                <div style="display:flex;gap:12px;justify-content:flex-end;margin-top:24px;">
                    <button type="button" onclick="closeMessageModal()" class="btn btn-outline">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check2"></i> Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../assets/js/sidebar.js"></script>
    <script>
        function openMessageModal() {
            const modal = document.getElementById('messageModal');
            if (modal) { modal.style.display = 'flex'; }
        }
        function closeMessageModal() {
            document.getElementById('messageModal').style.display = 'none';
        }
        document.getElementById('messageModal').addEventListener('click', function(e) {
            if (e.target === this) closeMessageModal();
        });
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeMessageModal();
        });
    </script>
</body>
</html>
