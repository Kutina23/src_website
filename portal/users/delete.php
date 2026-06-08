<?php
session_start();
require_once '../../config/database.php';
require_once '../../config/functions.php';

if (!isLogged()) {
    header('Location: ../login.php');
    exit;
}

$currentRole = currentRole();

if ($currentRole !== 'PRO') {
    header('Location: ../index.php');
    exit;
}

$userId = $_GET['id'] ?? null;
if (!$userId) {
    header('Location: index.php');
    exit;
}

$user = db()->fetch("SELECT * FROM users WHERE id = ?", [$userId]);
if (!$user) {
    header('Location: index.php');
    exit;
}

// Prevent deleting self or last admin
if ($userId == $_SESSION['user_id']) {
    $_SESSION['errors'] = ['Cannot delete your own account'];
    header('Location: index.php');
    exit;
}

$adminCount = db()->fetch("SELECT COUNT(*) as c FROM users u JOIN roles r ON u.role_id = r.id WHERE r.name IN ('PRO', 'PRESIDENT', 'DIRECTOR ICT', 'DEAN')", [])['c'];
if (in_array($user['role'] ?? '', ['PRO', 'PRESIDENT', 'DIRECTOR ICT', 'DEAN']) && $adminCount <= 1) {
    $_SESSION['errors'] = ['Cannot delete the last admin user'];
    header('Location: index.php');
    exit;
}

db()->execute("DELETE FROM audit_logs WHERE user_id = ?", [$userId]);
db()->execute("DELETE FROM ga_attendance WHERE user_id = ?", [$userId]);
db()->execute("DELETE FROM ga_vote_records WHERE user_id = ?", [$userId]);
db()->execute("UPDATE council_members SET profile_image_id = NULL WHERE profile_image_id IN (SELECT id FROM media WHERE uploaded_by = ?)", [$userId]);
db()->execute("DELETE FROM media WHERE uploaded_by = ?", [$userId]);
db()->execute("DELETE FROM news WHERE author_id = ?", [$userId]);
db()->execute("UPDATE clubs SET president_id = NULL, advisor_id = NULL WHERE president_id = ? OR advisor_id = ?", [$userId, $userId]);
db()->execute("DELETE FROM club_presidents WHERE user_id = ?", [$userId]);
db()->execute("DELETE FROM club_members WHERE user_id = ?", [$userId]);
db()->execute("DELETE FROM staff WHERE user_id = ?", [$userId]);
db()->execute("DELETE FROM dean_images WHERE user_id = ?", [$userId]);
db()->execute("DELETE FROM president_images WHERE user_id = ?", [$userId]);
db()->execute("DELETE FROM committee_members WHERE user_id = ?", [$userId]);
db()->execute("UPDATE committees SET chair_id = NULL WHERE chair_id = ?", [$userId]);
db()->execute("DELETE FROM council_members WHERE user_id = ?", [$userId]);
db()->execute("DELETE FROM ga_resolutions WHERE proposer_id = ? OR seconded_by = ?", [$userId, $userId]);
db()->execute("UPDATE ga_voting SET opened_by = NULL, closed_by = NULL WHERE opened_by = ? OR closed_by = ?", [$userId, $userId]);
db()->execute("DELETE FROM document_requests WHERE user_id = ?", [$userId]);
db()->execute("DELETE FROM constitution WHERE uploaded_by = ?", [$userId]);
db()->execute("DELETE FROM users WHERE id = ?", [$userId]);
logActivity('delete_user', $_SESSION['user_id'], ['user_id' => $userId, 'email' => $user['email']]);

$_SESSION['success'] = 'User deleted successfully';
header('Location: index.php');
exit;