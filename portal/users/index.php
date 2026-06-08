<?php
session_start();
require_once '../../config/database.php';
require_once '../../config/functions.php';

if (!isLogged()) {
    header('Location: ../login.php');
    exit;
}

$currentRole = currentRole();
$currentUser = currentUser();

if ($currentRole !== 'PRO') {
    header('Location: ../index.php');
    exit;
}

$pageTitle = 'Users Management';

// Get users with pagination
$page = $_GET['page'] ?? 1;
$search = $_GET['search'] ?? '';
$roleFilter = $_GET['role'] ?? '';
$statusFilter = $_GET['status'] ?? '';

$where = '1=1';
$params = [];

if ($search) {
    // Smart search: split search term into words and search across fields
    $searchTerms = explode(' ', trim($search));
    $searchConditions = [];
    $searchParams = [];
    
    foreach ($searchTerms as $term) {
        if (!empty($term)) {
            $searchConditions[] = '(first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR student_id LIKE ?)';
            $searchParams[] = "%{$term}%";
            $searchParams[] = "%{$term}%";
            $searchParams[] = "%{$term}%";
            $searchParams[] = "%{$term}%";
        }
    }
    
    if (!empty($searchConditions)) {
        $where .= ' AND (' . implode(' OR ', $searchConditions) . ')';
        $params = array_merge($params, $searchParams);
    }
}

if ($roleFilter) {
    $where .= ' AND r.name = ?';
    $params[] = $roleFilter;
}

if ($statusFilter) {
    $where .= ' AND is_active = ?';
    $params[] = ($statusFilter === 'active') ? 1 : 0;
}

$totalUsers = db()->fetch("SELECT COUNT(*) as count FROM users u JOIN roles r ON u.role_id = r.id WHERE {$where}", $params)['count'];
$pagination = paginate($totalUsers, 15, $page);

$users = db()->fetchAll("
    SELECT u.*, r.name as role
    FROM users u
    JOIN roles r ON u.role_id = r.id
    WHERE {$where}
    ORDER BY u.created_at DESC
    LIMIT {$pagination['offset']}, {$pagination['per_page']}
", $params);

$roles = ['PRO', 'PRESIDENT', 'DIRECTOR ICT', 'DEAN', 'STUDENT'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> | DHLTU SRC</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;0,700;1,300,400&family=Outfit:wght@200;300;400;500;600;700&family=Space+Mono:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <link rel="stylesheet" href="../../assets/css/alerts.css">
    
    <script>
        window.currentUserRole = '<?php echo $currentRole; ?>';
    </script>
<link rel="icon" type="image/png" href="../../assets/images/logo.png">
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
            require_once '../../include/nav-links.php';
            $nav = new NavigationRBAC($currentRole);
            echo $nav->renderNavigation();
            ?>

            <div class="sidebar-user">
                <div class="user-avatar"><?php echo strtoupper(substr($currentUser['first_name'], 0, 1) . substr($currentUser['last_name'], 0, 1)); ?></div>
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']); ?></div>
                    <div class="user-role"><span class="role-badge admin"><?php echo $currentRole; ?></span></div>
                </div>
            </div>
        </aside>

        <div class="main-content">
            <header class="dashboard-header">
                <button class="mobile-toggle" id="mobileToggle" aria-label="Open menu">
                    <i class="bi bi-list"></i>
                </button>
                <h1 class="header-title">Users Management</h1>
                <div class="header-actions">
                    <a href="create.php" class="header-btn" title="Add User" style="background:var(--dashboard-primary);color:#0a1628;">
                        <i class="bi bi-plus"></i>
                    </a>
                    <a href="../logout.php" class="header-btn" aria-label="Logout">
                        <i class="bi bi-box-arrow-right"></i>
                    </a>
                </div>
            </header>

            <main class="content-body">
                <div class="dashboard-container">
                    <div class="dashboard-header-section">
                        <h2 class="dashboard-title">Manage Users</h2>
                        <p class="dashboard-subtitle">Create, edit, and manage user accounts</p>
                    </div>

                    <?php if (!empty($_SESSION['success'])): ?>
                        <div style="background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.2);border-radius:8px;padding:16px;margin-bottom:20px;color:#22c55e;display:flex;align-items:center;gap:8px;">
                            <i class="bi bi-check-circle-fill"></i>
                            <?php echo $_SESSION['success']; ?>
                        </div>
                        <?php unset($_SESSION['success']); ?>
                    <?php endif; ?>

                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-card-header">
                                <span class="stat-card-label">Total Users</span>
                                <div class="stat-card-icon primary">
                                    <i class="bi bi-people"></i>
                                </div>
                            </div>
                            <div class="stat-card-value"><?php echo $totalUsers; ?></div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-card-header">
                                <span class="stat-card-label">Active Users</span>
                                <div class="stat-card-icon success">
                                    <i class="bi bi-check-circle"></i>
                                </div>
                            </div>
                            <div class="stat-card-value"><?php echo db()->fetch("SELECT COUNT(*) as c FROM users WHERE is_active = 1", [])['c']; ?></div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-card-header">
                                <span class="stat-card-label">Student Users</span>
                                <div class="stat-card-icon info">
                                    <i class="bi bi-mortarboard"></i>
                                </div>
                            </div>
<div class="stat-card-value"><?php echo db()->fetch("SELECT COUNT(*) as c FROM users u JOIN roles r ON u.role_id = r.id WHERE r.name = 'STUDENT'", [])['c']; ?></div>
                         </div>
                         <div class="stat-card">
                             <div class="stat-card-header">
                                 <span class="stat-card-label">Admin Users</span>
                                 <div class="stat-card-icon warning">
                                     <i class="bi bi-shield"></i>
                                 </div>
                             </div>
                             <div class="stat-card-value"><?php echo db()->fetch("SELECT COUNT(*) as c FROM users u JOIN roles r ON u.role_id = r.id WHERE r.name IN ('PRO', 'PRESIDENT', 'DIRECTOR ICT', 'DEAN')", [])['c']; ?></div>
                         </div>
                     </div>

                     <div class="actions-bar">
                        <div class="actions-left">
                            <form method="GET" style="display:flex;gap:8px;">
                                <div class="search-box-wrapper">
                                    <div class="search-box">
                                        <i class="bi bi-search"></i>
                                        <input type="text" name="search" placeholder="Search users..." value="<?php echo htmlspecialchars($search); ?>" id="userSearchInput" autocomplete="off">
                                    </div>
                                    <div id="searchSuggestions" class="search-suggestions" style="display:none;"></div>
                                </div>
                                <select name="role" class="form-select" onchange="this.form.submit()" style="width:auto;">
                                    <option value="">All Roles</option>
                                    <?php foreach ($roles as $role): ?>
                                        <option value="<?php echo $role; ?>" <?php echo $roleFilter === $role ? 'selected' : ''; ?>><?php echo $role; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <select name="status" class="form-select" onchange="this.form.submit()" style="width:auto;">
                                    <option value="">All Status</option>
                                    <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                </select>
<?php if ($statusFilter): ?>
                <a href="index.php" class="header-btn" title="Clear filters">
                    <i class="bi bi-x"></i>
                </a>
                <?php endif; ?>
            </form>
        </div>
                        <div class="actions-right">
                            <a href="create.php" class="btn btn-primary">
                                <i class="bi bi-plus"></i>
                                Add User
                            </a>
                        </div>
                    </div>

                    <div class="table-container">
                        <?php if (empty($users)): ?>
                            <div class="empty-state">
                                <div class="empty-icon">
                                    <i class="bi bi-people"></i>
                                </div>
                                <h3 class="empty-title">No users found</h3>
                                <p class="empty-text">Try adjusting your search or filters</p>
                            </div>
                        <?php else: ?>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>User</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $index => $user): ?>
                                        <tr>
                                            <td><?php echo $pagination['offset'] + $index + 1; ?></td>
                                            <td>
                                                <div class="user-info-cell">
                                                    <div class="user-avatar-table">
                                                        <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                                                    </div>
                                                    <div>
                                                        <div class="user-name"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></div>
                                                        <div class="user-email">ID: <?php echo $user['student_id'] ?? 'N/A'; ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td><span class="badge badge-role"><?php echo $user['role']; ?></span></td>
                                            <td><span class="badge badge-<?php echo $user['is_active'] ? 'active' : 'inactive'; ?>"><?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?></span></td>
                                            <td><?php echo formatDate($user['created_at'], 'M d, Y'); ?></td>
                                            <td>
                                                <div style="display:flex;gap:8px;">
                                                    <a href="edit.php?id=<?php echo $user['id']; ?>" class="header-btn" title="Edit">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <a href="reset-password-user.php?id=<?php echo $user['id']; ?>" class="header-btn" title="Reset Password" style="background:var(--gold-color,#C9A84C);color:#0a1628;">
                                                        <i class="bi bi-lock"></i>
                                                    </a>
                                                    <a href="delete.php?id=<?php echo $user['id']; ?>" class="btn btn-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this user?')">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>

                            <?php if ($pagination['total_pages'] > 1): ?>
                                <div class="pagination">
                                    <?php
                                    $currentPage = $pagination['current_page'];
                                    $totalPages = $pagination['total_pages'];
                                    $queryString = ($search ? '&search=' . urlencode($search) : '') . 
                                                   ($roleFilter ? '&role=' . urlencode($roleFilter) : '') . 
                                                   ($statusFilter ? '&status=' . urlencode($statusFilter) : '');
                                    
                                    // Previous button
                                    if ($currentPage > 1): ?>
                                        <a href="?page=<?php echo $currentPage - 1; ?><?php echo $queryString; ?>" class="pagination-btn">
                                            <i class="bi bi-chevron-left"></i> Previous
                                        </a>
                                    <?php endif; ?>

                                    <?php
                                    // Calculate range of pages to show
                                    $range = 2; // Show 2 pages on each side of current
                                    $startPage = max(1, $currentPage - $range);
                                    $endPage = min($totalPages, $currentPage + $range);

                                    // Always show first page
                                    if ($startPage > 1): ?>
                                        <a href="?page=1<?php echo $queryString; ?>" class="pagination-btn">1</a>
                                        <?php if ($startPage > 2): ?>
                                            <span class="pagination-ellipsis">...</span>
                                        <?php endif; ?>
                                    <?php endif; ?>

                                    <?php
                                    // Show range of pages
                                    for ($i = $startPage; $i <= $endPage; $i++): ?>
                                        <a href="?page=<?php echo $i; ?><?php echo $queryString; ?>" 
                                           class="pagination-btn <?php echo $i === $currentPage ? 'active' : ''; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    <?php endfor; ?>

                                    <?php
                                    // Always show last page
                                    if ($endPage < $totalPages): ?>
                                        <?php if ($endPage < $totalPages - 1): ?>
                                            <span class="pagination-ellipsis">...</span>
                                        <?php endif; ?>
                                        <a href="?page=<?php echo $totalPages; ?><?php echo $queryString; ?>" class="pagination-btn">
                                            <?php echo $totalPages; ?>
                                        </a>
                                    <?php endif; ?>

                                    <?php
                                    // Next button
                                    if ($currentPage < $totalPages): ?>
                                        <a href="?page=<?php echo $currentPage + 1; ?><?php echo $queryString; ?>" class="pagination-btn">
                                            Next <i class="bi bi-chevron-right"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="../../assets/js/sidebar.js"></script>
    <script src="../../assets/js/loader-service.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('userSearchInput');
        const suggestionsContainer = document.getElementById('searchSuggestions');
        let searchTimeout;

        searchInput.addEventListener('input', function() {
            const query = this.value.trim();
            
            clearTimeout(searchTimeout);
            
            if (query.length < 2) {
                suggestionsContainer.style.display = 'none';
                return;
            }
            
            searchTimeout = setTimeout(function() {
                fetch('../../api/users-search-suggestions.php?q=' + encodeURIComponent(query))
                    .then(response => response.json())
                    .then(data => {
                        if (data.length === 0) {
                            suggestionsContainer.style.display = 'none';
                            return;
                        }
                        
                        suggestionsContainer.innerHTML = '';
                        data.forEach(user => {
                            const div = document.createElement('div');
                            div.className = 'suggestion-item';
                            div.innerHTML = `
                                <div class="suggestion-name">${escapeHtml(user.display)}</div>
                                <div class="suggestion-email">${escapeHtml(user.email)}</div>
                            `;
                            div.addEventListener('click', function() {
                                searchInput.value = user.name;
                                suggestionsContainer.style.display = 'none';
                            });
                            suggestionsContainer.appendChild(div);
                        });
                        
                        suggestionsContainer.style.display = 'block';
                    })
                    .catch(error => console.error('Search error:', error));
            }, 300);
        });
        
        // Close suggestions when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.search-box-wrapper')) {
                suggestionsContainer.style.display = 'none';
            }
        });
        
        // Helper function to escape HTML
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    });
    </script>
    <?php if (function_exists('alert')): ?>
    <?php echo alert()->render(); ?>
    <?php endif; ?>
</body>
</html>