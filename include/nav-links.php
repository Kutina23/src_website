<?php
// Navigation Links with Role-Based Access Control
// Roles: PRO (admin), PRESIDENT, DIRECTOR ICT, DEAN, STUDENT

class NavigationRBAC {
    private $userRole;
    private $permissions;
    private $basePath;

    public function __construct($role) {
        $this->userRole = $role;
        $this->permissions = $this->getPermissions();
        $this->basePath = $this->getBasePath();
    }

    private function getBasePath() {
        $path = $_SERVER['PHP_SELF'] ?? '';
        if (strpos($path, '/users/') !== false) {
            return '../';
        }
        return '';
    }

    private function getPermissions() {
        $perms = [
            'PRO' => [
                'can_view_dashboard' => true, 'can_manage_users' => true,
                'can_manage_complaints' => true, 'can_manage_documents' => true,
                'can_manage_clubs' => true, 'can_manage_elections' => true,
                'can_manage_ga' => true, 'can_manage_news' => true,
                'can_manage_committees' => true, 'can_view_reports' => true,
                'can_manage_settings' => true, 'can_manage_president_images' => true,
                'can_manage_dean_images' => true, 'can_manage_gallery' => true,
                'can_manage_services' => true, 'can_manage_scholarships' => true,
                'can_manage_halls' => true
            ],
            'PRESIDENT' => [
                'can_view_dashboard' => true, 'can_manage_users' => false,
                'can_manage_complaints' => false, 'can_manage_documents' => false,
                'can_manage_clubs' => false, 'can_manage_elections' => false,
                'can_manage_ga' => true, 'can_manage_news' => false,
                'can_manage_committees' => true, 'can_view_reports' => true,
                'can_manage_settings' => false
            ],
            'DIRECTOR ICT' => [
                'can_view_dashboard' => true, 'can_manage_users' => false,
                'can_manage_complaints' => false, 'can_manage_documents' => false,
                'can_manage_clubs' => false, 'can_manage_elections' => false,
                'can_manage_ga' => true, 'can_manage_news' => false,
                'can_manage_committees' => false, 'can_view_reports' => true,
                'can_manage_settings' => false
            ],
            'DEAN' => [
                'can_view_dashboard' => true, 'can_manage_users' => false,
                'can_manage_complaints' => false, 'can_manage_documents' => false,
                'can_manage_clubs' => false, 'can_manage_elections' => false,
                'can_manage_ga' => true, 'can_manage_news' => false,
                'can_manage_committees' => false, 'can_view_reports' => true,
                'can_manage_settings' => false
            ],
            'STUDENT' => [
                'can_view_dashboard' => true, 'can_manage_users' => false,
                'can_manage_complaints' => true, 'can_manage_documents' => true,
                'can_manage_clubs' => false, 'can_manage_elections' => true,
                'can_manage_ga' => false, 'can_manage_news' => false,
                'can_manage_committees' => false, 'can_view_reports' => false,
                'can_manage_settings' => false
            ],
        ];
        return $perms[$this->userRole] ?? $perms['STUDENT'];
    }

    public function can($permission) {
        return $this->permissions[$permission] ?? false;
    }

    public function renderNavigation() {
        ob_start();
        ?>
        <nav class="sidebar-nav">

            <!-- Dashboard -->
            <div class="nav-section">
                <div class="nav-header">Dashboard</div>
                <a href="<?php echo $this->basePath; ?>dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="bi bi-grid-1x2"></i>
                    <span>Overview</span>
                </a>
                <?php if ($this->userRole === 'PRESIDENT'): ?>
                <a href="<?php echo $this->basePath; ?>president-dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'president-dashboard.php' ? 'active' : ''; ?>">
                    <i class="bi bi-person-badge-fill"></i>
                    <span>My Portal</span>
                </a>
                <?php endif; ?>
                <?php if ($this->userRole === 'DIRECTOR ICT'): ?>
                <a href="<?php echo $this->basePath; ?>director-ict-dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'director-ict-dashboard.php' ? 'active' : ''; ?>">
                    <i class="bi bi-gear-fill"></i>
                    <span>My Portal</span>
                </a>
                <?php endif; ?>
                <?php if ($this->userRole === 'DEAN'): ?>
                <a href="<?php echo $this->basePath; ?>dean-dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'dean-dashboard.php' ? 'active' : ''; ?>">
                    <i class="bi bi-mortarboard-fill"></i>
                    <span>My Portal</span>
                </a>
                <?php endif; ?>
                <!-- My Profile — visible to every logged-in user -->
                <a href="<?php echo $this->basePath; ?>profile.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'active' : ''; ?>">
                    <i class="bi bi-person-gear"></i>
                    <span>My Profile</span>
                </a>
            </div>

            <!-- Portal -->
            <?php if ($this->can('can_manage_complaints') || $this->can('can_manage_documents') || $this->userRole === 'STUDENT'): ?>
            <div class="nav-section">
                <div class="nav-header">Portal</div>
                <?php if ($this->can('can_manage_complaints')): ?>
                <a href="<?php echo $this->basePath; ?>complaints.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'complaints.php' ? 'active' : ''; ?>">
                    <i class="bi bi-clipboard"></i>
                    <span>Complaints</span>
                </a>
                <?php endif; ?>
                <?php if ($this->can('can_manage_documents') || $this->userRole === 'STUDENT'): ?>
                <a href="<?php echo $this->basePath; ?>documents.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'documents.php' ? 'active' : ''; ?>">
                    <i class="bi bi-file-earmark-text"></i>
                    <span>Documents</span>
                </a>
                <?php endif; ?>
                <?php if ($this->can('can_manage_elections') || $this->userRole === 'STUDENT'): ?>
                <a href="<?php echo $this->basePath; ?>elections.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'elections.php' ? 'active' : ''; ?>">
                    <i class="bi bi-card-checklist"></i>
                    <span>Elections</span>
                </a>
                <?php endif; ?>
                
            </div>
            <?php endif; ?>

            <!-- Projects — visible to PRO, PRESIDENT, DIRECTOR ICT, DEAN -->
        <?php if ($this->userRole !== 'STUDENT'): ?>
        <div class="nav-section">
            <div class="nav-header">Projects</div>
            <a href="<?php echo $this->basePath; ?>projects.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'projects.php' ? 'active' : ''; ?>">
                <i class="bi bi-folder2-open"></i>
                <span>SRC Projects</span>
            </a>
        </div>
        <?php endif; ?>

        <!-- Scholarships — PRO only -->
        <?php if ($this->userRole === 'PRO'): ?>
        <div class="nav-section">
            <div class="nav-header">Student Support</div>
            <a href="<?php echo $this->basePath; ?>scholarships.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'scholarships.php' || basename($_SERVER['PHP_SELF']) === 'scholarships-update.php' ? 'active' : ''; ?>">
                <i class="bi bi-mortarboard"></i>
                <span>Scholarships</span>
            </a>
        </div>
        <?php endif; ?>

        <!-- Administration -->
        <?php if ($this->can('can_manage_users')): ?>
            <div class="nav-section">
                <div class="nav-header">Administration</div>
                <a href="<?php echo $this->basePath; ?>users/index.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) === 'index.php' && strpos($_SERVER['PHP_SELF'], '/users/') !== false) ? 'active' : ''; ?>">
                    <i class="bi bi-people"></i>
                    <span>Users</span>
                </a>
                <a href="<?php echo $this->basePath; ?>departments.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'departments.php' ? 'active' : ''; ?>">
                    <i class="bi bi-building"></i>
                    <span>Departments</span>
                </a>
                <a href="<?php echo $this->basePath; ?>halls.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'halls.php' ? 'active' : ''; ?>">
                    <i class="bi bi-building-fill"></i>
                    <span>Halls</span>
                </a>
                <a href="<?php echo $this->basePath; ?>audit.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'audit.php' ? 'active' : ''; ?>">
                    <i class="bi bi-shield-check"></i>
                    <span>Audit Logs</span>
                </a>
            </div>
        <?php endif; ?>

        <!-- Organizations -->
        <?php if ($this->can('can_manage_clubs')): ?>
        <div class="nav-section">
            <div class="nav-header">Organizations</div>
            <a href="<?php echo $this->basePath; ?>clubs.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'clubs.php' ? 'active' : ''; ?>">
                <i class="bi bi-collection"></i>
                <span>Clubs & Societies</span>
            </a>
            <a href="<?php echo $this->basePath; ?>club-members.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'club-members.php' ? 'active' : ''; ?>">
                <i class="bi bi-people"></i>
                <span>Club Members</span>
            </a>
            <a href="<?php echo $this->basePath; ?>club-presidents.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'club-presidents.php' ? 'active' : ''; ?>">
                <i class="bi bi-person-badge"></i>
                <span>Club Presidents</span>
            </a>
        </div>
        <?php endif; ?>

        <!-- Services -->
            <?php if ($this->can('can_manage_services')): ?>
            <div class="nav-section">
                <div class="nav-header">Services</div>
                <a href="<?php echo $this->basePath; ?>services.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'services.php' ? 'active' : ''; ?>">
                    <i class="bi bi-collection-play"></i>
                    <span>Manage Services</span>
                </a>
            </div>
            <?php endif; ?>

            <!-- Media Gallery -->
            <?php if ($this->can('can_manage_gallery')): ?>
            <div class="nav-section">
                <div class="nav-header">Media</div>
                <a href="<?php echo $this->basePath; ?>gallery.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'gallery.php' ? 'active' : ''; ?>">
                    <i class="bi bi-images"></i>
                    <span>Media Gallery</span>
                </a>
            </div>
            <?php endif; ?>

            <!-- Constitution -->
            <?php if ($this->can('can_manage_settings')): ?>
            <div class="nav-section">
                <div class="nav-header">Documents</div>
                <a href="<?php echo $this->basePath; ?>constitution-upload.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'constitution-upload.php' ? 'active' : ''; ?>">
                    <i class="bi bi-file-earmark-pdf"></i>
                    <span>Constitution</span>
                </a>
                <a href="<?php echo $this->basePath; ?>downloads.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'downloads.php' ? 'active' : ''; ?>">
                    <i class="bi bi-folder2-open"></i>
                    <span>Downloads</span>
                </a>
            </div>
            <?php endif; ?>

            <!-- System Settings -->
            <?php if ($this->can('can_manage_settings')): ?>
            <div class="nav-section">
                <div class="nav-header">System</div>
                <a href="<?php echo $this->basePath; ?>settings.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'settings.php' ? 'active' : ''; ?>">
                    <i class="bi bi-gear"></i>
                    <span>Settings</span>
                </a>
            </div>
            <?php endif; ?>

            <!-- Council -->
            <?php if ($this->can('can_manage_committees')): ?>
            <div class="nav-section">
                <div class="nav-header">Council</div>
                <a href="<?php echo $this->basePath; ?>council.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'council.php' ? 'active' : ''; ?>">
                    <i class="bi bi-person-gear"></i>
                    <span>Executive Members</span>
                </a>
                <a href="<?php echo $this->basePath; ?>committees.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'committees.php' ? 'active' : ''; ?>">
                    <i class="bi bi-people"></i>
                    <span>Committees</span>
                </a>
            </div>
            <?php endif; ?>

            <!-- Assembly — Sessions -->
            <?php if ($this->can('can_manage_ga')): ?>
            <div class="nav-section">
                <div class="nav-header">Assembly — Sessions</div>
                <a href="<?php echo $this->basePath; ?>ga-sessions.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'ga-sessions.php' ? 'active' : ''; ?>">
                    <i class="bi bi-calendar-event"></i>
                    <span>All GA Sessions</span>
                </a>
                <a href="<?php echo $this->basePath; ?>ga-annual-meetings.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'ga-annual-meetings.php' ? 'active' : ''; ?>">
                    <i class="bi bi-calendar-check"></i>
                    <span>Annual GA Meetings</span>
                </a>
                <a href="<?php echo $this->basePath; ?>ga-emergency-meetings.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'ga-emergency-meetings.php' ? 'active' : ''; ?>">
                    <i class="bi bi-broadcast"></i>
                    <span>Emergency GA Meetings</span>
                </a>
                <a href="<?php echo $this->basePath; ?>ga-special-sessions-admin.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'ga-special-sessions-admin.php' ? 'active' : ''; ?>">
                    <i class="bi bi-lightning"></i>
                    <span>Special Sessions</span>
                </a>
            </div>

            <!-- Assembly — Content -->
            <div class="nav-section">
                <div class="nav-header">Assembly — Content</div>
                <a href="<?php echo $this->basePath; ?>ga-minutes.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'ga-minutes.php' ? 'active' : ''; ?>">
                    <i class="bi bi-file-earmark-pdf"></i>
                    <span>Minutes</span>
                </a>
                <a href="<?php echo $this->basePath; ?>ga-resolutions.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'ga-resolutions.php' ? 'active' : ''; ?>">
                    <i class="bi bi-file-earmark-text"></i>
                    <span>Resolutions &amp; Motions</span>
                </a>
                <a href="<?php echo $this->basePath; ?>ga-voting.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'ga-voting.php' ? 'active' : ''; ?>">
                    <i class="bi bi-card-checklist"></i>
                    <span>Voting Records</span>
                </a>
                <a href="<?php echo $this->basePath; ?>ga-attendance.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'ga-attendance.php' ? 'active' : ''; ?>">
                    <i class="bi bi-check-square"></i>
                    <span>Attendance</span>
                </a>
</div>
            <?php endif; ?>

            <!-- News & Announcements -->
            <?php if ($this->can('can_manage_news')): ?>
             <div class="nav-section">
                 <div class="nav-header">Communication</div>
                 <a href="<?php echo $this->basePath; ?>news-admin.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'news-admin.php' ? 'active' : ''; ?>">
                     <i class="bi bi-newspaper"></i>
                     <span>News & Announcements</span>
                 </a>
                 <a href="<?php echo $this->basePath; ?>email-subscriptions.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'email-subscriptions.php' ? 'active' : ''; ?>">
                     <i class="bi bi-envelope-at"></i>
                     <span>Email Subscriptions</span>
                 </a>
                 <a href="<?php echo $this->basePath; ?>contact-messages.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'contact-messages.php' ? 'active' : ''; ?>">
                     <i class="bi bi-envelope-paper"></i>
                     <span>Contact Messages</span>
                 </a>
             </div>
            <?php endif; ?>

            <!-- Leadership Media -->
            <?php if ($this->can('can_manage_president_images') || $this->can('can_manage_dean_images')): ?>
            <div class="nav-section">
                <div class="nav-header">Leadership Media</div>
                <?php if ($this->can('can_manage_president_images')): ?>
                <a href="<?php echo $this->basePath; ?>president-images.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'president-images.php' ? 'active' : ''; ?>">
                    <i class="bi bi-person-badge"></i>
                    <span>President Images</span>
                </a>
                <?php endif; ?>
                <?php if ($this->can('can_manage_dean_images')): ?>
                <a href="<?php echo $this->basePath; ?>dean-images.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'dean-images.php' ? 'active' : ''; ?>">
                    <i class="bi bi-person-badge"></i>
                    <span>Hero Images</span>
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Reports / Analytics -->
            <?php if ($this->can('can_view_reports')): ?>
            <div class="nav-section">
                <div class="nav-header">Reports</div>
                <a href="<?php echo $this->basePath; ?>reports.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'reports.php' ? 'active' : ''; ?>">
                    <i class="bi bi-bar-chart"></i>
                    <span>Analytics</span>
                </a>
            </div>
            <?php endif; ?>
        </nav>
        <?php
        return ob_get_clean();
    }
}
