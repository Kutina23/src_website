<?php
require_once '../config/database.php';
require_once '../models/Clubs.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $db = Database::getInstance();
    $clubsModel = new Clubs($db);
    
    // Validate required fields
    $required = ['club_name', 'category', 'president_name', 'president_student_id', 'contact_email', 'initial_members'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Field '$field' is required");
        }
    }
    
    // Validate email
    if (!filter_var($_POST['contact_email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Invalid email format");
    }
    
    // Validate initial members minimum
    if ((int)$_POST['initial_members'] < 10) {
        throw new Exception("Initial members must be at least 10");
    }
    
    // Handle file upload — save logo into media table +store relative path in clubs
    $logoPath = null;
    $mediaId = null;
    if (isset($_FILES['club_logo']) && $_FILES['club_logo']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['club_logo']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("File upload failed");
        }

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $detectedType = finfo_file($finfo, $_FILES['club_logo']['tmp_name']);
        finfo_close($finfo);

        if (!in_array($detectedType, $allowedTypes)) {
            throw new Exception("Invalid file type. Only JPG, PNG, GIF, WEBP allowed");
        }

        $ext = pathinfo($_FILES['club_logo']['name'], PATHINFO_EXTENSION);
        $uploadDir = realpath(__DIR__ . '/../uploads/clubs');
        if ($uploadDir === false) { $uploadDir = __DIR__ . '/../uploads/clubs'; }
        if (!is_dir($uploadDir)) { mkdir($uploadDir, 0755, true); }

        // 1. Insert into media table
        $fileName = "club_" . time() . "_" . uniqid() . "." . $ext;
        $filePath = $uploadDir . DIRECTORY_SEPARATOR . $fileName;
        if (!move_uploaded_file($_FILES['club_logo']['tmp_name'], $filePath)) {
            throw new Exception("Failed to save uploaded file");
        }
        $mediaId = $db->insert('media', [
            'file_name'   => $_FILES['club_logo']['name'],
            'file_path'   => 'uploads/clubs/' . $fileName,
            'file_type'   => 'IMAGE',
            'mime_type'   => $detectedType,
            'file_size'   => $_FILES['club_logo']['size'],
            'alt_text'    => trim($_POST['club_name']) . ' logo',
            'description' => trim($_POST['description'] ?? ''),
            'is_active'   => 1,
            'uploaded_by' => null    // unauthenticated submission
        ]);

        // 2. Store media path in clubs.logo_path
        $logoPath = 'uploads/clubs/' . $fileName;
    }
    
    // Prepare data for registration
    $data = [
        'name' => trim($_POST['club_name']),
        'category' => trim($_POST['category']),
        'president_name' => trim($_POST['president_name']),
        'president_student_id' => trim($_POST['president_student_id']),
        'contact_email' => trim($_POST['contact_email']),
        'contact_phone' => trim($_POST['contact_phone'] ?? ''),
        'initial_members' => (int)$_POST['initial_members'],
        'description' => trim($_POST['description'] ?? ''),
        'logo_path' => $logoPath
    ];
    
    $clubId = $clubsModel->registerClub($data);
    
    if ($clubId) {
        echo json_encode([
            'success' => true,
            'message' => 'Club registration submitted successfully',
            'club_id' => $clubId
        ]);
    } else {
        throw new Exception("Failed to register club");
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>