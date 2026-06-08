<?php
session_start();
require_once '../config/database.php';
require_once '../config/functions.php';

if (!isLogged()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$currentRole = currentRole();
if ($currentRole !== 'PRO') {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

$query = $_GET['q'] ?? '';
if (empty($query) || strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

// Smart search: split search term into words
$searchTerms = explode(' ', trim($query));
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

if (empty($searchConditions)) {
    echo json_encode([]);
    exit;
}

$where = '(' . implode(' OR ', $searchConditions) . ')';

// Get top 10 matching users
$users = db()->fetchAll("
    SELECT CONCAT(first_name, ' ', last_name) as name, email, student_id
    FROM users
    WHERE {$where}
    ORDER BY first_name ASC, last_name ASC
    LIMIT 10
", $searchParams);

$suggestions = [];
foreach ($users as $user) {
    $displayText = $user['name'];
    if ($user['student_id']) {
        $displayText .= ' (' . $user['student_id'] . ')';
    }
    
    $suggestions[] = [
        'name' => $user['name'],
        'email' => $user['email'],
        'student_id' => $user['student_id'],
        'display' => $displayText
    ];
}

header('Content-Type: application/json');
echo json_encode($suggestions);
