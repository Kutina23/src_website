<?php
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../models/ContactMessages.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$name = trim($data['name'] ?? '');
$studentId = trim($data['student_id'] ?? '');
$email = trim($data['email'] ?? '');
$category = trim($data['category'] ?? '');
$message = trim($data['message'] ?? '');

if (!$name || !$email || !$message) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'All required fields must be filled']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid email address']);
    exit;
}

$db = Database::getInstance();
$contactModel = new ContactMessages($db);

$contactModel->create([
    'full_name' => $name,
    'student_id' => $studentId ?: null,
    'email' => $email,
    'category' => $category,
    'message' => $message
]);

echo json_encode(['success' => true, 'message' => 'Message sent successfully']);