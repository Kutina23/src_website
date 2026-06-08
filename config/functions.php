<?php
define('DEFAULT_TEMP_PASSWORD', 'srcltu@2026');
require_once __DIR__ . '/email.php';

function sanitize($input) {
    return trim(htmlspecialchars($input, ENT_QUOTES, 'UTF-8'));
}

function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

function formatDate($date, $format = 'M d, Y') {
    if (!$date) return '';
    return date($format, strtotime($date));
}

function formatDateTime($datetime, $format = 'M d, Y h:i A') {
    if (!$datetime) return '';
    return date($format, strtotime($datetime));
}

function timeAgo($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;
    
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . ' minutes ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    return formatDate($datetime);
}

function truncate($text, $length = 100) {
    if (strlen($text) <= $length) return $text;
    return substr($text, 0, $length) . '...';
}

function escapeJson($value) {
    return json_encode($value, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
}

function redirect($url) {
    header("Location: {$url}");
    exit;
}

function isLogged() {
    return isset($_SESSION['user_id']);
}

function currentUser() {
    if (!isLogged()) return null;
    return db()->fetch('SELECT * FROM users WHERE id = ?', [$_SESSION['user_id']]);
}

function currentRole() {
    if (!isLogged()) return null;
    return $_SESSION['role'] ?? null;
}

function currentUserCan(string $permission): bool {
    if (!isLogged()) return false;
    $role = currentRole();
    $perms = [
        'PRO' => [
            'can_view_dashboard' => true, 'can_manage_users' => true,
            'can_manage_complaints' => true, 'can_manage_documents' => true,
            'can_manage_clubs' => true, 'can_manage_elections' => true,
            'can_manage_ga' => true, 'can_manage_news' => true,
            'can_manage_committees' => true, 'can_view_reports' => true,
            'can_manage_settings' => true, 'can_manage_president_images' => true,
            'can_manage_dean_images' => true, 'can_manage_gallery' => true,
            'can_manage_departments' => true, 'can_manage_halls' => true,
            'can_view_halls' => true,
        ],
        'PRESIDENT' => [
            'can_view_dashboard' => true, 'can_manage_users' => false,
            'can_manage_complaints' => false, 'can_manage_documents' => false,
            'can_manage_clubs' => false, 'can_manage_elections' => false,
            'can_manage_ga' => true, 'can_manage_news' => false,
            'can_manage_committees' => true, 'can_view_reports' => true,
            'can_manage_settings' => false,
            'can_manage_halls' => false,
            'can_view_halls' => true,
        ],
        'DIRECTOR ICT' => [
            'can_view_dashboard' => true, 'can_manage_users' => false,
            'can_manage_complaints' => false, 'can_manage_documents' => false,
            'can_manage_clubs' => false, 'can_manage_elections' => false,
            'can_manage_ga' => true, 'can_manage_news' => false,
            'can_manage_committees' => false, 'can_view_reports' => true,
            'can_manage_settings' => false,
            'can_manage_halls' => false,
            'can_view_halls' => true,
        ],
        'DEAN' => [
            'can_view_dashboard' => true, 'can_manage_users' => false,
            'can_manage_complaints' => false, 'can_manage_documents' => false,
            'can_manage_clubs' => false, 'can_manage_elections' => false,
            'can_manage_ga' => true, 'can_manage_news' => false,
            'can_manage_committees' => false, 'can_view_reports' => true,
            'can_manage_settings' => false,
            'can_manage_halls' => false,
            'can_view_halls' => true,
        ],
        'STUDENT' => [
            'can_view_dashboard' => true, 'can_manage_users' => false,
            'can_manage_complaints' => true, 'can_manage_documents' => true,
            'can_manage_clubs' => false, 'can_manage_elections' => true,
            'can_manage_ga' => false, 'can_manage_news' => false,
            'can_manage_committees' => false, 'can_view_reports' => false,
            'can_manage_settings' => false,
            'can_manage_halls' => false,
            'can_view_halls' => false,
        ],
    ];
    return $perms[$role][$permission] ?? false;
}

function asset($path) {
    return "assets/{$path}";
}

function url($path = '') {
    $base = $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']);
    return "{$base}/{$path}";
}

function paginate($total, $perPage = 10, $current = 1) {
    $totalPages = ceil($total / $perPage);
    $offset = ($current - 1) * $perPage;
    return [
        'total' => $total,
        'per_page' => $perPage,
        'current_page' => $current,
        'total_pages' => $totalPages,
        'offset' => $offset,
        'has_more' => $current < $totalPages
    ];
}

function uploadFile($file, $directory = 'uploads/', $allowed = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx']) {
    if (!isset($file['name']) || $file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) {
        return false;
    }
    
    $filename = uniqid() . '.' . $ext;
    $destination = $directory . $filename;
    
    if (!is_dir($directory)) {
        mkdir($directory, 0755, true);
    }
    
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return $filename;
    }
    return false;
}

function logActivity($action, $userId = null, $details = []) {
    db()->execute('INSERT INTO audit_logs (user_id, action, new_values, ip_address, created_at) VALUES (?, ?, ?, ?, NOW())', [
        $userId ?? $_SESSION['user_id'] ?? null,
        $action,
        json_encode($details),
        $_SERVER['REMOTE_ADDR'] ?? ''
    ]);
}

function getEnumValues($table, $column) {
    $stmt = db()->query("SHOW COLUMNS FROM {$table} LIKE '{$column}'");
    $row = $stmt->fetch();
    if ($row && preg_match('/^enum\((.*)\)$/', $row['Type'], $matches)) {
        $values = str_getcsv($matches[1], ',', "'");
        return $values;
    }
    return [];
}

/**
 * Convert an integer year to its ordinal form (e.g. 23 -> "23rd").
 */
function ordinalSuffix(int $n): string {
    if ($n % 100 >= 11 && $n % 100 <= 13) return $n . 'th';
    return match($n % 10) {
        1  => $n . 'st',
        2  => $n . 'nd',
        3  => $n . 'rd',
        default => $n . 'th',
    };
}

function subscribeToEmails($email, $fullName = null) {
    $email = strtolower(trim($email));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Invalid email address'];
    }
    
    require_once __DIR__ . '/../models/EmailSubscription.php';
    $model = new EmailSubscription(db());
    
    $existing = $model->getByEmail($email);
    if ($existing) {
        if ($existing['is_active']) {
            return ['success' => false, 'message' => 'Email already subscribed'];
        }
        $model->subscribe($email, $fullName);
        return ['success' => true, 'message' => 'Subscription reactivated'];
    }
    
    $model->subscribe($email, $fullName);
    return ['success' => true, 'message' => 'Successfully subscribed to announcements'];
}

function unsubscribeFromEmails($token) {
    if (!$token) {
        return ['success' => false, 'message' => 'Invalid token'];
    }
    
    require_once __DIR__ . '/../models/EmailSubscription.php';
    $model = new EmailSubscription(db());
    
    $subscriber = $model->getByToken($token);
    if (!$subscriber) {
        return ['success' => false, 'message' => 'Invalid or expired token'];
    }
    
    if (!$subscriber['is_active']) {
        return ['success' => false, 'message' => 'Already unsubscribed'];
    }
    
    $model->unsubscribe($token);
    return ['success' => true, 'message' => 'Successfully unsubscribed'];
}

function getSubscribers() {
    require_once __DIR__ . '/../models/EmailSubscription.php';
    $model = new EmailSubscription(db());
    return $model->getAllActive();
}

function getSubscriberCount() {
    require_once __DIR__ . '/../models/EmailSubscription.php';
    $model = new EmailSubscription(db());
    return $model->count();
}

function sendAnnouncementEmail($subject, $body, $contentType = 'html') {
    require_once __DIR__ . '/../models/EmailSubscription.php';
    $model = new EmailSubscription(db());
    return $model->sendNewsletter($subject, $body, $contentType);
}

/**
 * Send email notification when news is published
 * Auto-called when a news item is published to subscribers
 * 
 * @param array $news News article data (id, title, excerpt, category, featured_image)
 * @return array Result with sent/failed counts
 */
function sendNewsEmailNotification($news) {
    require_once __DIR__ . '/../models/EmailSubscription.php';
    
    if (!$news || !$news['id']) {
        return ['success' => false, 'message' => 'Invalid news data'];
    }

    $emailModel = new EmailSubscription(db());
    $subscribers = $emailModel->getAllActive();

    if (empty($subscribers)) {
        return ['success' => true, 'sent' => 0, 'failed' => 0, 'message' => 'No active subscribers'];
    }

    $baseUrl = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $baseUrl .= '://' . $_SERVER['HTTP_HOST'];
    $newsUrl = rtrim($baseUrl, '/') . '/news-detail.php?id=' . $news['id'];

    $subject = "📰 New News: " . $news['title'];
    $excerpt = substr($news['excerpt'] ?? $news['content'] ?? '', 0, 200);
    
    $body = "<div style='font-family: Arial, sans-serif; color: #333; line-height: 1.6;'>";
    $body .= "<h2 style='color: #2c3e50;'>" . htmlspecialchars($news['title']) . "</h2>";
    $body .= "<p style='color: #666;'><small>";
    $body .= "Category: <strong>" . htmlspecialchars($news['category'] ?? 'News') . "</strong>";
    $body .= " | Published: " . date('M d, Y', strtotime($news['published_at'] ?? 'now'));
    $body .= "</small></p>";
    
    if (!empty($news['featured_image'])) {
        $body .= "<img src='" . $baseUrl . "/" . htmlspecialchars($news['featured_image']) . "' ";
        $body .= "alt='" . htmlspecialchars($news['title']) . "' style='max-width: 100%; height: auto; margin: 15px 0;'>";
    }
    
    $body .= "<p>" . nl2br(htmlspecialchars($excerpt)) . "</p>";
    $body .= "<p><a href='{$newsUrl}' style='display: inline-block; padding: 10px 20px; background-color: #3498db; color: white; text-decoration: none; border-radius: 4px;'>Read Full Article</a></p>";
    $body .= "<hr style='margin: 30px 0; border: none; border-top: 1px solid #ddd;'>";
    $body .= "<p style='font-size: 12px; color: #999;'>You received this email because you subscribed to SRC announcements. ";
    $body .= "<a href='{UNSUBSCRIBE_LINK}' style='color: #3498db;'>Unsubscribe</a></p>";
    $body .= "</div>";

    return $emailModel->sendNewsletter($subject, $body, 'html');
}

/**
 * Send email notification for GA Session
 * Auto-called when a GA session is scheduled
 * 
 * @param array $gaSession GA session data (id, title, description, scheduled_datetime, location)
 * @return array Result with sent/failed counts
 */
function sendGaSessionEmailNotification($gaSession) {
    require_once __DIR__ . '/../models/EmailSubscription.php';
    
    if (!$gaSession || !$gaSession['id']) {
        return ['success' => false, 'message' => 'Invalid GA session data'];
    }

    $emailModel = new EmailSubscription(db());
    $subscribers = $emailModel->getAllActive();

    if (empty($subscribers)) {
        return ['success' => true, 'sent' => 0, 'failed' => 0, 'message' => 'No active subscribers'];
    }

    $baseUrl = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $baseUrl .= '://' . $_SERVER['HTTP_HOST'];
    $gaUrl = rtrim($baseUrl, '/') . '/special-sessions.php';

    $sessionType = $gaSession['session_type'] ?? 'GENERAL ASSEMBLY';
    $dateTime = date('M d, Y \a\t g:i A', strtotime($gaSession['scheduled_datetime']));

    $subject = "📅 " . strtoupper($sessionType) . " - " . $gaSession['title'];
    
    $body = "<div style='font-family: Arial, sans-serif; color: #333; line-height: 1.6;'>";
    $body .= "<h2 style='color: #2c3e50;'>📅 " . htmlspecialchars($gaSession['title']) . "</h2>";
    $body .= "<div style='background-color: #f5f5f5; padding: 15px; border-left: 4px solid #e74c3c; margin: 20px 0;'>";
    $body .= "<p style='margin: 5px 0;'><strong>Date & Time:</strong> {$dateTime}</p>";
    
    if (!empty($gaSession['location'])) {
        $body .= "<p style='margin: 5px 0;'><strong>Location:</strong> " . htmlspecialchars($gaSession['location']) . "</p>";
    }
    
    $body .= "<p style='margin: 5px 0;'><strong>Type:</strong> " . htmlspecialchars($sessionType) . "</p>";
    $body .= "</div>";
    
    if (!empty($gaSession['description'])) {
        $body .= "<h3 style='color: #2c3e50; margin-top: 20px;'>Details</h3>";
        $body .= "<p>" . nl2br(htmlspecialchars($gaSession['description'])) . "</p>";
    }

    $body .= "<p style='margin-top: 20px;'>";
    $body .= "<a href='{$gaUrl}' style='display: inline-block; padding: 10px 20px; background-color: #e74c3c; color: white; text-decoration: none; border-radius: 4px;'>View Details</a>";
    $body .= "</p>";
    $body .= "<hr style='margin: 30px 0; border: none; border-top: 1px solid #ddd;'>";
    $body .= "<p style='font-size: 12px; color: #999;'>You received this email because you subscribed to SRC announcements. ";
    $body .= "<a href='{UNSUBSCRIBE_LINK}' style='color: #3498db;'>Unsubscribe</a></p>";
    $body .= "</div>";

    return $emailModel->sendNewsletter($subject, $body, 'html');
}

/**
 * Send email notification for events/announcements
 * Generic function for custom announcements
 * 
 * @param string $subject Email subject
 * @param string $title Display title
 * @param string $content Main content HTML
 * @param array $metadata Optional metadata (event_date, location, etc.)
 * @return array Result with sent/failed counts
 */
function sendAnnouncementEmailNotification($subject, $title, $content, $metadata = []) {
    require_once __DIR__ . '/../models/EmailSubscription.php';
    
    $emailModel = new EmailSubscription(db());
    $subscribers = $emailModel->getAllActive();

    if (empty($subscribers)) {
        return ['success' => true, 'sent' => 0, 'failed' => 0, 'message' => 'No active subscribers'];
    }

    $body = "<div style='font-family: Arial, sans-serif; color: #333; line-height: 1.6;'>";
    $body .= "<h2 style='color: #2c3e50;'>" . htmlspecialchars($title) . "</h2>";
    
    if (!empty($metadata)) {
        $body .= "<div style='background-color: #f5f5f5; padding: 15px; border-left: 4px solid #3498db; margin: 20px 0;'>";
        foreach ($metadata as $key => $value) {
            $label = ucfirst(str_replace('_', ' ', $key));
            $body .= "<p style='margin: 5px 0;'><strong>{$label}:</strong> " . htmlspecialchars($value) . "</p>";
        }
        $body .= "</div>";
    }
    
    $body .= "<div style='margin: 20px 0;'>" . $content . "</div>";
    $body .= "<hr style='margin: 30px 0; border: none; border-top: 1px solid #ddd;'>";
    $body .= "<p style='font-size: 12px; color: #999;'>You received this email because you subscribed to SRC announcements. ";
    $body .= "<a href='{UNSUBSCRIBE_LINK}' style='color: #3498db;'>Unsubscribe</a></p>";
    $body .= "</div>";

    return $emailModel->sendNewsletter($subject, $body, 'html');
}

?>
