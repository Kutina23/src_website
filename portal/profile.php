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

// A logged-in user may only ever edit their own profile on this page
$profileUserId = (int)$_SESSION['user_id'];

$pageTitle = 'My Profile';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = sanitize($_POST['first_name'] ?? '');
    $lastName  = sanitize($_POST['last_name']  ?? '');
    $email     = sanitize($_POST['email']       ?? '');
    $bio       = sanitize($_POST['bio'] ?? '');
    $keyResponsibilities = $_POST['key_responsibilities'] ?? [];
    $newPass   = trim($_POST['new_password']   ?? '');
    $confirm   = trim($_POST['confirm_password'] ?? '');

    $errors = [];

    if (!$firstName)       $errors[] = 'First name is required';
    if (!$lastName)        $errors[] = 'Last name is required';
    if (!$email)           $errors[] = 'Email is required';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email address';

    // Only validate uniqueness if email actually changed
    if (!empty($email)) {
        $existing = db()->fetch("SELECT id FROM users WHERE email = ? AND id != ?", [$email, $profileUserId]);
        if ($existing) $errors[] = 'Email is already registered to another account';
    }

    // Password change — only checked if at least one field is filled
    if ($newPass !== '' || $confirm !== '') {
        if ($newPass !== $confirm)                    $errors[] = 'New password and confirm password do not match';
        if (strlen($newPass) < 8)                     $errors[] = 'Password must be at least 8 characters';
        if (!preg_match('/[A-Z]/', $newPass))         $errors[] = 'Password must contain at least one uppercase letter';
        if (!preg_match('/[a-z]/', $newPass))         $errors[] = 'Password must contain at least one lowercase letter';
        if (!preg_match('/[0-9]/', $newPass))         $errors[] = 'Password must contain at least one number';
    }

    if (empty($errors)) {
        $responsibilityLines = explode("\n", trim($keyResponsibilities));
        $responsibilities = array_filter(array_map('trim', $responsibilityLines));
        $responsibilitiesJson = !empty($responsibilities) ? json_encode(array_values($responsibilities)) : null;
        
        if ($newPass !== '') {
            db()->execute(
                "UPDATE users SET first_name = ?, last_name = ?, email = ?, bio = ?, key_responsibilities = ?, password_hash = ? WHERE id = ?",
                [$firstName, $lastName, $email, $bio, $responsibilitiesJson, hashPassword($newPass), $profileUserId]
            );
            logActivity('change_credentials', $profileUserId, ['field' => 'password']);
        } else {
            db()->execute(
                "UPDATE users SET first_name = ?, last_name = ?, email = ?, bio = ?, key_responsibilities = ? WHERE id = ?",
                [$firstName, $lastName, $email, $bio, $responsibilitiesJson, $profileUserId]
            );
            logActivity('change_credentials', $profileUserId, ['field' => 'profile']);
        }

        // Refresh session so sidebar / header shows updated name immediately
        $_SESSION['first_name'] = $firstName;
        $_SESSION['last_name']  = $lastName;
        $_SESSION['email']      = $email;

        $_SESSION['success'] = 'Account credentials updated successfully';
        header('Location: profile.php');
        exit;
    } else {
        $_SESSION['errors'] = $errors;
    }
}

$user = currentUser();
$errors = $_SESSION['errors'] ?? [];
unset($_SESSION['errors']);
$success = $_SESSION['success'] ?? '';
unset($_SESSION['success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> | DHLTU SRC</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;0,700;1,300,1,400&family=Outfit:wght@200;300;400;500;600;700&family=Space+Mono:ital@0,400;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/alerts.css">

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
                    <div class="user-name"><?php echo htmlspecialchars(($currentUser['first_name'] ?? '') . ' ' . ($currentUser['last_name'] ?? '')); ?></div>
                    <div class="user-role"><span class="role-badge <?php echo $currentRole === 'PRO' ? 'admin' : ($currentRole === 'STUDENT' ? 'student' : 'monitor'); ?>"><?php echo $currentRole; ?></span></div>
                </div>
            </div>
        </aside>

        <div class="main-content">
            <header class="dashboard-header">
                <button class="mobile-toggle" id="mobileToggle" aria-label="Open menu">
                    <i class="bi bi-list"></i>
                </button>
                <h1 class="header-title">My Profile</h1>
                <div class="header-actions">
                    <a href="logout.php" class="header-btn" aria-label="Logout">
                        <i class="bi bi-box-arrow-right"></i>
                    </a>
                </div>
            </header>

            <main class="content-body">
                <div class="dashboard-container">
                    <div class="dashboard-header-section">
                        <h2 class="dashboard-title">Account Credentials</h2>
                        <p class="dashboard-subtitle">Update your name, email, and password</p>
                    </div>

                    <?php if ($success): ?>
                    <div style="background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.2);border-radius:8px;padding:14px 18px;margin-bottom:24px;color:#22c55e;font-size:14px;display:flex;align-items:center;gap:10px;">
                        <i class="bi bi-check-circle" style="font-size:18px;flex-shrink:0;"></i><?php echo htmlspecialchars($success); ?>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($errors)): ?>
                    <div style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.2);border-radius:8px;padding:14px 18px;margin-bottom:24px;color:#ef4444;font-size:14px;display:flex;align-items:center;gap:10px;">
                        <i class="bi bi-exclamation-circle" style="font-size:18px;flex-shrink:0;"></i>
                        <ul style="margin:0;padding-left:0;list-style:none;">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>

                    <!-- Profile Card -->
                    <div class="profile-card" style="background:var(--dashboard-card-bg);border:1px solid var(--dashboard-border);border-radius:16px;overflow:hidden;margin-bottom:28px;">
                        <!-- Card Banner -->
                        <div style="height:120px;background:linear-gradient(135deg,rgba(201,168,76,0.35),rgba(201,168,76,0.08));position:relative;">
                            <div style="position:absolute;inset:0;background:radial-gradient(ellipse at 70% 50%,rgba(201,168,76,0.15),transparent 60%);"></div>
                        </div>
                        <div style="padding:0 32px 32px;position:relative;">
                            <!-- Avatar overlaps banner -->
                            <div style="position:absolute;top:-50px;left:32px;">
                                <div style="width:100px;height:100px;border-radius:50%;background:linear-gradient(135deg,var(--dashboard-primary),var(--dashboard-primary-light));border:4px solid rgba(255,255,255,0.05);display:flex;align-items:center;justify-content:center;font-family:'Cormorant Garamond',serif;font-size:34px;font-weight:700;color:#0a1628;box-shadow:0 4px 20px rgba(0,0,0,0.3);">
                                    <?php echo strtoupper(substr($user['first_name'],0,1) . substr($user['last_name'],0,1)); ?>
                                </div>
                            </div>

                            <!-- Role badge top-right -->
                            <div style="float:right;margin-top:12px;">
                                <span class="badge badge-role"><?php echo htmlspecialchars($currentRole); ?></span>
                            </div>

                            <div style="padding-top:60px;">
                                 <div style="font-family:'Cormorant Garamond',serif;font-size:26px;font-weight:700;color:var(--dashboard-text);">
                                     <?php echo htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')); ?>
                                 </div>
                                 <div style="font-size:14px;color:var(--dashboard-text-muted);margin-top:4px;">
                                     <?php echo htmlspecialchars($user['email'] ?? ''); ?>
                                 </div>
                                <div style="margin-top:16px;display:flex;gap:10px;flex-wrap:wrap;">
                                    <div style="background:rgba(201,   168,76,0.1);border:1px solid rgba(201,168,76,0.18);border-radius:8px;padding:6px 14px;font-size:12px;color:var(--dashboard-primary-light);">
                                        <i class="bi bi-person"></i> <?php echo htmlspecialchars($currentRole); ?> Portal
                                    </div>
                                    <div style="background:rgba(201,168,76,0.1);border:1px solid rgba(201,168,76,0.18);border-radius:8px;padding:6px 14px;font-size:12px;color:var(--dashboard-primary-light);">
                                        <i class="bi bi-calendar-arrow-down"></i> Joined <?php echo formatDate($user['created_at']); ?>
                                    </div>
                                     <div style="background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.2);border-radius:8px;padding:6px 14px;font-size:12px;color:#22c55e;">
                                         <i class="bi bi-shield-check"></i> <?php echo $user['is_active'] ? 'Account Active' : 'Account Inactive'; ?>
                                     </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Credentials Form -->
                    <div class="profile-card" style="background:var(--dashboard-card-bg);border:1px solid var(--dashboard-border);border-radius:16px;overflow:hidden;margin-bottom:28px;">
                        <div style="padding:20px 28px;border-bottom:1px solid var(--dashboard-border);display:flex;align-items:center;gap:10px;">
                            <div style="width:36px;height:36px;border-radius:10px;background:rgba(201,168,76,0.15);display:flex;align-items:center;justify-content:center;color:var(--dashboard-primary);font-size:18px;"><i class="bi bi-person-lines-fill"></i></div>
                            <div>
                                <div style="font-family:'Cormorant Garamond',serif;font-size:18px;font-weight:600;color:var(--dashboard-text);">Personal Information</div>
                                <div style="font-size:12px;color:var(--dashboard-text-muted);">Update your name and email address</div>
                            </div>
                        </div>
                        <div style="padding:28px;">
                            <form method="POST" autocomplete="off">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">First Name</label>
                                         <input type="text" name="first_name" class="form-input"
                                                placeholder="John" required
                                                value="<?php echo htmlspecialchars($_POST['first_name'] ?? $user['first_name'] ?? ''); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Last Name</label>
                                         <input type="text" name="last_name" class="form-input"
                                                placeholder="Doe" required
                                                value="<?php echo htmlspecialchars($_POST['last_name'] ?? $user['last_name'] ?? ''); ?>">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Email Address</label>
                                     <input type="email" name="email" class="form-input"
                                            placeholder="you@hltu.edu.gh" required
                                            value="<?php echo htmlspecialchars($_POST['email'] ?? $user['email'] ?? ''); ?>">
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Bio</label>
                                    <textarea name="bio" class="form-input" rows="3" placeholder="Brief biography..."><?php echo htmlspecialchars($_POST['bio'] ?? $user['bio'] ?? ''); ?></textarea>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Key Responsibilities (one per line)</label>
                                     <textarea name="key_responsibilities" class="form-input" rows="4" placeholder="Lead SRC meetings and general assemblies&#10;Represent student interests to administration..."><?php 
                                         $decoded = $user['key_responsibilities'] ? json_decode($user['key_responsibilities'], true) : [];
                                         if (!is_array($decoded)) $decoded = [];
                                         echo htmlspecialchars($_POST['key_responsibilities'] ?? implode("\n", $decoded) ?? '');
                                     ?></textarea>
                                </div>

                                <div style="padding-top:8px;border-top:1px solid var(--dashboard-border);margin-top:24px;padding-top:28px;">
                                    <div style="font-family:'Cormorant Garamond',serif;font-size:18px;font-weight:600;color:var(--dashboard-text);margin-bottom:4px;">Change Password</div>
                                    <div style="font-size:12px;color:var(--dashboard-text-muted);margin-bottom:24px;">Leave both fields blank to keep your current password</div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">New Password</label>
                                        <div class="password-wrapper" style="position:relative;">
                                            <input type="password" name="new_password" id="newPassword" class="form-input"
                                                   placeholder="At least 8 characters">
                                            <button type="button" class="toggle-password" id="toggleNewPassword" aria-label="Toggle password visibility" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--dashboard-text-muted);cursor:pointer;font-size:18px;">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Confirm Password</label>
                                        <div class="password-wrapper" style="position:relative;">
                                            <input type="password" name="confirm_password" id="confirmPassword" class="form-input"
                                                   placeholder="Repeat new password">
                                            <button type="button" class="toggle-password" id="toggleConfirmPassword" aria-label="Toggle password visibility" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--dashboard-text-muted);cursor:pointer;font-size:18px;">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div style="display:flex;gap:12px;justify-content:flex-end;margin-top:32px;">
                                    <a href="dashboard.php" class="btn btn-secondary">
                                        <i class="bi bi-arrow-left"></i> Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-check-lg"></i> Save Changes
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="../assets/js/sidebar.js"></script>
    <?php if (function_exists('alert')): ?>
    <?php echo alert()->render(); ?>
    <?php endif; ?>

    <script>
        // Toggle password visibility
        function attachToggle(btnId, inputId) {
            const btn     = document.getElementById(btnId);
            const input   = document.getElementById(inputId);
            if (!btn || !input) return;
            btn.addEventListener('click', function () {
                const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                input.setAttribute('type', type);
                this.querySelector('i').classList.toggle('bi-eye');
                this.querySelector('i').classList.toggle('bi-eye-slash');
            });
        }
        attachToggle('toggleNewPassword',    'newPassword');
        attachToggle('toggleConfirmPassword','confirmPassword');
    </script>
</body>
</html>
