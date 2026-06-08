<?php
/**
 * Newsletter Management API
 * Send newsletters and announcements to email subscribers
 * 
 * Requires: PRO or PRESIDENT role
 */

require_once "../config/database.php";
require_once "../config/functions.php";
require_once "../models/EmailSubscription.php";

header("Content-Type: application/json");

// Check authentication and authorization
if (!isLogged() || !currentUserCan('can_manage_news')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

if ($method === 'POST') {
    $action = $input['action'] ?? '';
    
    if ($action === 'send-newsletter') {
        $subject = $input['subject'] ?? '';
        $title = $input['title'] ?? '';
        $content = $input['content'] ?? '';
        $contentType = $input['content_type'] ?? 'html';
        
        if (empty($subject) || empty($content)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Subject and content are required'
            ]);
            exit;
        }
        
        // Use the generic announcement email function
        $result = sendAnnouncementEmailNotification($subject, $title ?: $subject, $content, []);
        echo json_encode($result);
        
    } elseif ($action === 'send-news-notification') {
        $newsId = $input['news_id'] ?? null;
        
        if (!$newsId) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'news_id is required'
            ]);
            exit;
        }
        
        require_once "../models/News.php";
        $newsModel = new News(db());
        $news = $newsModel->getById($newsId);
        
        if (!$news) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'News article not found'
            ]);
            exit;
        }
        
        $result = sendNewsEmailNotification($news);
        echo json_encode($result);
        
    } elseif ($action === 'send-ga-notification') {
        $gaSessionId = $input['ga_session_id'] ?? null;
        
        if (!$gaSessionId) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'ga_session_id is required'
            ]);
            exit;
        }
        
        require_once "../models/GaSessions.php";
        $gaModel = new GaSessions(db());
        $gaSession = $gaModel->getById($gaSessionId);
        
        if (!$gaSession) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'GA session not found'
            ]);
            exit;
        }
        
        $result = sendGaSessionEmailNotification($gaSession);
        echo json_encode($result);
        
    } elseif ($action === 'get-subscribers') {
        $subscribers = getSubscribers();
        echo json_encode([
            'success' => true,
            'total' => count($subscribers),
            'subscribers' => $subscribers
        ]);
        
    } elseif ($action === 'get-stats') {
        $emailModel = new EmailSubscription(db());
        $stats = [
            'total_active' => $emailModel->count(),
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        echo json_encode([
            'success' => true,
            'data' => $stats
        ]);
        
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>
