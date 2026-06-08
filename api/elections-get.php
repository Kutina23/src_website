<?php
require_once "../config/database.php";
require_once "../config/functions.php";
require_once "../models/Elections.php";

header("Content-Type: application/json");

if (!isLogged() || !in_array(currentRole(), ["PRO", "PRESIDENT", "DIRECTOR ICT", "DEAN"])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    exit;
}

$electionsModel = new Elections(db());
$election = $electionsModel->getById($id);

if (!$election) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Election not found']);
    exit;
}

echo json_encode($election);
?>