<?php
require_once "../config/database.php";
require_once "../config/functions.php";

header("Content-Type: application/json");

$token = $_GET['token'] ?? $_POST['token'] ?? '';

if (!$token) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Token is required']);
    exit;
}

$result = unsubscribeFromEmails($token);
echo json_encode($result);
?>