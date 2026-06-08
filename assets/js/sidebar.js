// Sidebar Toggle and RBAC Controller
class SidebarController {
    constructor() {
        this.sidebar = document.querySelector('.sidebar');
        this.mainContent = document.querySelector('.main-content');
        this.toggleBtn = document.getElementById('sidebarToggle');
        this.mobileToggle = document.getElementById('mobileToggle');
        this.mobileOverlay = document.getElementById('mobileOverlay');
        this.isDesktop = window.innerWidth >= 768;
        
        this.init();
    }

    init() {
        this.bindEvents();
        this.checkScreenSize();
        this.loadUserPreferences();
    }

    bindEvents() {
        if (this.toggleBtn) {
            this.toggleBtn.addEventListener('click', () => this.toggleSidebar());
        }

        if (this.mobileToggle) {
            this.mobileToggle.addEventListener('click', () => this.toggleMobileSidebar());
        }

        if (this.mobileOverlay) {
            this.mobileOverlay.addEventListener('click', () => this.closeMobileSidebar());
        }

        window.addEventListener('resize', () => this.handleResize());

        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.sidebar?.classList.contains('active')) {
                this.closeMobileSidebar();
            }
        });
    }

    toggleSidebar() {
        if (!this.sidebar) return;
        
        this.sidebar.classList.toggle('collapsed');
        this.saveUserPreferences(!this.sidebar.classList.contains('collapsed'));
        
        // Dispatch event for other components
        window.dispatchEvent(new CustomEvent('sidebarToggle', {
            detail: { collapsed: this.sidebar.classList.contains('collapsed') }
        }));
    }

    toggleMobileSidebar() {
        if (!this.sidebar) return;
        
        this.sidebar.classList.toggle('active');
        this.mobileOverlay?.classList.toggle('active');
        document.body.style.overflow = this.sidebar.classList.contains('active') ? 'hidden' : '';
    }

    closeMobileSidebar() {
        if (!this.sidebar) return;
        
        this.sidebar.classList.remove('active');
        this.mobileOverlay?.classList.remove('active');
        document.body.style.overflow = '';
    }

    handleResize() {
        const wasDesktop = this.isDesktop;
        this.isDesktop = window.innerWidth >= 768;

        if (wasDesktop !== this.isDesktop) {
            if (this.isDesktop) {
                this.sidebar?.classList.remove('active');
                this.mobileOverlay?.classList.remove('active');
                document.body.style.overflow = '';
            }
        }
    }

    saveUserPreferences(expanded) {
        try {
            localStorage.setItem('sidebar-expanded', expanded.toString());
        } catch (e) {
            console.warn('Could not save sidebar preference');
        }
    }

    loadUserPreferences() {
        try {
            const expanded = localStorage.getItem('sidebar-expanded');
            if (expanded === 'false' && this.isDesktop) {
                this.sidebar?.classList.add('collapsed');
            }
        } catch (e) {
            console.warn('Could not load sidebar preference');
        }
    }

    checkScreenSize() {
        this.handleResize();
    }
}

// RBAC Navigation Controller
class RBACController {
    constructor(userRole) {
        this.userRole = userRole;
        this.permissions = this.getPermissions();
    }

    getPermissions() {
        const perms = {
            'PRO': { level: 'admin', canCreate: true, canEdit: true, canDelete: true, canViewAll: true },
            'PRESIDENT': { level: 'monitor', canCreate: false, canEdit: false, canDelete: false, canViewAll: true },
            'DIRECTOR ICT': { level: 'monitor', canCreate: false, canEdit: false, canDelete: false, canViewAll: true },
            'DEAN': { level: 'monitor', canCreate: false, canEdit: false, canDelete: false, canViewAll: false },
            'STUDENT': { level: 'student', canCreate: true, canEdit: true, canDelete: false, canViewAll: false }
        };
        return perms[this.userRole] || perms['STUDENT'];
    }

    can(permission) {
        return this.permissions[permission] ?? false;
    }

    getRoleBadgeClass() {
        const classes = {
            'admin': 'role-badge admin',
            'monitor': 'role-badge monitor',
            'student': 'role-badge student'
        };
        return classes[this.permissions.level] || classes['student'];
    }

    getRoleLabel() {
        return this.userRole;
    }

    filterNavigation() {
        const navLinks = document.querySelectorAll('.nav-link[data-permission]');
        navLinks.forEach(link => {
            const requiredPermission = link.dataset.permission;
            if (!this.can(requiredPermission)) {
                link.style.display = 'none';
            }
        });
    }
}

// Initialize on DOM Ready
document.addEventListener('DOMContentLoaded', () => {
    window.sidebarController = new SidebarController();
    
    // Initialize RBAC if user data is available
    if (window.currentUserRole) {
        window.rbacController = new RBACController(window.currentUserRole);
        window.rbacController.filterNavigation();
    }
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { SidebarController, RBACController };
}