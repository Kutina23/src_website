<?php
require_once "../config/database.php";
require_once "../config/functions.php";

header("Content-Type: application/json");

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

if ($method === 'POST') {
    $action = $input['action'] ?? $_POST['action'] ?? '';
    
    if ($action === 'subscribe') {
        $email = $input['email'] ?? $_POST['email'] ?? '';
        $fullName = $input['full_name'] ?? $_POST['full_name'] ?? null;
        
        $result = subscribeToEmails($email, $fullName);
        echo json_encode($result);
    } elseif ($action === 'unsubscribe') {
        $token = $input['token'] ?? $_POST['token'] ?? $_GET['token'] ?? '';
        $result = unsubscribeFromEmails($token);
        echo json_encode($result);
    } elseif ($action === 'get-stats' && currentUserCan('can_manage_news')) {
        // Get subscription stats for admin
        $count = getSubscriberCount();
        echo json_encode([
            'success' => true,
            'total_subscribers' => $count,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} elseif ($method === 'GET') {
    if (isset($_GET['action']) && $_GET['action'] === 'unsubscribe' && isset($_GET['token'])) {
        $token = $_GET['token'];
        $result = unsubscribeFromEmails($token);
        
        // Redirect to a confirmation page
        header("Location: ../index.php?email-unsubscribed=true");
        exit;
    } elseif (isset($_GET['token'])) {
        // Legacy support - treat token as unsubscribe
        $token = $_GET['token'];
        $result = unsubscribeFromEmails($token);
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>