<?php
session_start();
require_once '../../config/database.php';
require_once '../../config/functions.php';
require_once '../../models/Staff.php';

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

$userId = $_GET['id'] ?? null;
if (!$userId) {
    header('Location: index.php');
    exit;
}

$user = db()->fetch("
    SELECT u.*, r.name as role
    FROM users u
    JOIN roles r ON u.role_id = r.id
    WHERE u.id = ?
", [$userId]);
if (!$user) {
    header('Location: index.php');
    exit;
}

// Get profile image path for DEAN users
$user['profile_image_path'] = null;
if ($user['role'] === 'DEAN') {
    $staffModel = new Staff(db());
    $profileImages = $staffModel->getProfileImages($userId);
    if (!empty($profileImages) && is_array($profileImages)) {
        $user['profile_image_path'] = $profileImages[0]['file_path'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = sanitize($_POST['first_name'] ?? '');
    $lastName = sanitize($_POST['last_name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $studentId = sanitize($_POST['student_id'] ?? '');
    $bio = sanitize($_POST['bio'] ?? '');
    $keyResponsibilities = $_POST['key_responsibilities'] ?? [];
    if ($studentId === '') {
        $studentId = null;
    }
    $role = sanitize($_POST['role'] ?? '');
    $status = sanitize($_POST['status'] ?? 'active');
    
    // Handle profile image upload for Dean
    $mediaId = null;
    $errors = [];
    if ($role === 'DEAN' && isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../../uploads/dean/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileExt = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
        $allowedExt = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($fileExt, $allowedExt)) {
            $fileName = 'dean_' . time() . '_' . uniqid() . '.' . $fileExt;
            $filePath = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $filePath)) {
                $mediaId = db()->insert("media", [
                    "file_name" => $_FILES['profile_image']['name'],
                    "file_path" => "uploads/dean/" . $fileName,
                    "file_type" => "IMAGE",
                    "mime_type" => mime_content_type($filePath),
                    "file_size" => $_FILES['profile_image']['size'],
                    "alt_text" => '',
                    "uploaded_by" => $_SESSION['user_id']
                ]);
            }
        } else {
            $errors[] = 'Invalid file type. Only JPG, JPEG, PNG, GIF are allowed.';
        }
    }

    if (!$firstName) $errors[] = 'First name is required';
    if (!$lastName) $errors[] = 'Last name is required';
    if (!$email) $errors[] = 'Email is required';
    if (!$role) $errors[] = 'Role is required';
    
    $existingUser = db()->fetch("SELECT id FROM users WHERE email = ? AND id != ?", [$email, $userId]);
    if ($existingUser) $errors[] = 'Email already exists';
    
    if (empty($errors)) {
        $responsibilityLines = explode("\n", trim($keyResponsibilities));
        $responsibilities = array_filter(array_map('trim', $responsibilityLines));
        $responsibilitiesJson = !empty($responsibilities) ? json_encode(array_values($responsibilities)) : null;
        db()->execute("
            UPDATE users SET first_name = ?, last_name = ?, email = ?, student_id = ?, bio = ?, key_responsibilities = ?, role_id = (SELECT id FROM roles WHERE name = ?), is_active = ?
            WHERE id = ?
        ", [$firstName, $lastName, $email, $studentId, $bio, $responsibilitiesJson, $role, $status === 'active' ? 1 : 0, $userId]);
        
        logActivity('update_user', $_SESSION['user_id'], ['user_id' => $userId, 'email' => $email]);
        
        // If role is DEAN, update staff record and handle profile image
        if ($role === 'DEAN') {
            $staffModel = new Staff(db());
            
            // Generate a staff ID (e.g., DEAN0001)
            $staffId = 'DEAN' . str_pad($userId, 4, '0', STR_PAD_LEFT);
            
            // Check if staff record exists
            $existingStaff = db()->fetch("SELECT user_id FROM staff WHERE user_id = ?", [$userId]);
            
            if ($existingStaff) {
                // Update existing staff record
                $staffModel->update($userId, [
                    "position" => 'Dean of Students',
                    "staff_id" => $staffId,
                    "office_location" => '',
                    "office_hours" => '',
                    "appointment_required" => 0
                ]);
            } else {
                // Create new staff record
                $staffModel->create([
                    "user_id" => $userId,
                    "position" => 'Dean of Students',
                    "staff_id" => $staffId,
                    "office_location" => '',
                    "office_hours" => '',
                    "appointment_required" => 0
                ]);
            }
            
            // Assign profile image if uploaded
            if ($mediaId) {
                $staffModel->assignProfileImage($userId, $mediaId);
            }
        }
        
        $_SESSION['success'] = 'User updated successfully';
        header('Location: index.php');
        exit;
    } else {
        $_SESSION['errors'] = $errors;
    }
}

$roles = ['PRO', 'PRESIDENT', 'DIRECTOR ICT', 'DEAN', 'STUDENT'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User | DHLTU SRC</title>
    
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
                <span class="sidebar-title">HLTU Dashboard</span>
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
                <h1 class="header-title">Edit User</h1>
                <div class="header-actions">
                    <a href="index.php" class="header-btn" title="Back to Users">
                        <i class="bi bi-arrow-left"></i>
                    </a>
                    <a href="../logout.php" class="header-btn" aria-label="Logout">
                        <i class="bi bi-box-arrow-right"></i>
                    </a>
                </div>
            </header>

            <main class="content-body">
                <div class="dashboard-container">
                    <div class="dashboard-header-section">
                        <h2 class="dashboard-title">Edit User Account</h2>
                        <p class="dashboard-subtitle">Update user information and settings</p>
                    </div>

                    <?php if (!empty($_SESSION['errors'])): ?>
                        <div style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.2);border-radius:8px;padding:16px;margin-bottom:20px;">
                            <ul style="margin:0;padding-left:20px;color:#ef4444;">
                                <?php foreach ($_SESSION['errors'] as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php unset($_SESSION['errors']); ?>
                    <?php endif; ?>

                    <div class="table-container" style="max-width:600px;">
                         <form method="POST" enctype="multipart/form-data" style="padding:24px;">
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">First Name *</label>
                                    <input type="text" name="first_name" class="form-input" placeholder="John" required value="<?php echo $_POST['first_name'] ?? $user['first_name']; ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Last Name *</label>
                                    <input type="text" name="last_name" class="form-input" placeholder="Doe" required value="<?php echo $_POST['last_name'] ?? $user['last_name']; ?>">
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Email *</label>
                                <input type="email" name="email" class="form-input" placeholder="john.doe@student.srcltu.edu.gh" required value="<?php echo $_POST['email'] ?? $user['email']; ?>">
                            </div>

                            <div class="form-group">
                                <label class="form-label">Student ID</label>
                                <input type="text" name="student_id" class="form-input" placeholder="e.g., SRC/2024/001" value="<?php echo $_POST['student_id'] ?? $user['student_id']; ?>">
                            </div>

                            <div class="form-group">
                                <label class="form-label">Bio</label>
                                <textarea name="bio" class="form-input" rows="3" placeholder="Brief biography..."><?php echo $_POST['bio'] ?? $user['bio']; ?></textarea>
                            </div>

                             <div class="form-group">
                                 <label class="form-label">Key Responsibilities (one per line)</label>
                                 <textarea name="key_responsibilities" class="form-input" rows="4" placeholder="Lead SRC meetings and general assemblies&#10;Represent student interests to administration..."><?php 
                                     $decoded = $user['key_responsibilities'] ? json_decode($user['key_responsibilities'], true) : [];
                                     if (!is_array($decoded)) $decoded = [];
                                     echo htmlspecialchars($_POST['key_responsibilities'] ?? implode("\n", $decoded));
                                 ?></textarea>
                             </div>
                              <?php if (!empty($user['role']) && $user['role'] === 'DEAN'): ?>
                              <div class="form-group" id="dean-image-field">
                                  <label class="form-label">Profile Image (for Dean)</label>
                                  <input type="file" name="profile_image" class="form-input" accept="image/*">
                                  <?php if (!empty($user['profile_image_path'])): ?>
                                  <p>Current profile image:</p>
                                  <img src="../../<?php echo htmlspecialchars($user['profile_image_path']); ?>" style="max-height: 100px; margin-top: 10px;" alt="Current profile image">
                                  <?php endif; ?>
                              </div>
                              <?php endif; ?>

                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Role *</label>
                                    <select name="role" class="form-select" required>
                                        <option value="">Select Role</option>
                                        <?php foreach ($roles as $role): ?>
                                            <option value="<?php echo $role; ?>" <?php echo ($_POST['role'] ?? $user['role']) === $role ? 'selected' : ''; ?>><?php echo $role; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Status *</label>
                                    <select name="status" class="form-select" required>
                                        <option value="active" <?php echo (($_POST['status'] ?? ($user['is_active'] ? 'active' : 'inactive')) === 'active') ? 'selected' : ''; ?>>Active</option>
                                        <option value="inactive" <?php echo (($_POST['status'] ?? ($user['is_active'] ? 'active' : 'inactive')) === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                    </select>
                                </div>
                            </div>

                            <div class="modal-footer" style="margin:0;padding:0;justify-content:flex-start;margin-top:24px;">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check"></i>
                                    Update User
                                </button>
                                <a href="index.php" class="btn btn-secondary">
                                    Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="../../assets/js/sidebar.js"></script>
    <?php if (function_exists('alert')): ?>
    <?php echo alert()->render(); ?>
    <?php endif; ?>
    <script>
        // Function to toggle DEAN image field based on role selection
        function toggleDeanImageField() {
            const roleSelect = document.querySelector('select[name="role"]');
            const deanImageField = document.getElementById('dean-image-field');
            if (roleSelect && deanImageField) {
                if (roleSelect.value === 'DEAN') {
                    deanImageField.style.display = 'block';
                } else {
                    deanImageField.style.display = 'none';
                }
            }
        }

        // Run on DOM load and when role changes
        document.addEventListener('DOMContentLoaded', function() {
            toggleDeanImageField();
            const roleSelect = document.querySelector('select[name="role"]');
            if (roleSelect) {
                roleSelect.addEventListener('change', toggleDeanImageField);
            }
        });
    </script>
</body>
</html>
