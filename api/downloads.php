<?php
require_once "../config/database.php";
require_once "../models/Downloads.php";

header("Content-Type: application/json");

$db = Database::getInstance();
$model = new Downloads($db);

$action = $_GET["action"] ?? "";
$id = (int)($_GET["id"] ?? 0);

if ($action === "download" && $id > 0) {
    $model->incrementDownload($id);
    echo json_encode(["success" => true]);
} else {
    http_response_code(400);
    echo json_encode(["error" => "Invalid request"]);
}
?>
