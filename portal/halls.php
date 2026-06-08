<?php
session_start();
require_once '../config/database.php';
require_once '../config/functions.php';
require_once '../models/Halls.php';

if (!isLogged()) {
    header('Location: login.php');
    exit;
}

$currentRole = currentRole();
$currentUser = currentUser();

if (!currentUserCan('can_view_halls')) {
    header('Location: index.php');
    exit;
}

$pageTitle = 'Halls Management';
$success = $_SESSION['success'] ?? null;
$errors = $_SESSION['errors'] ?? [];
unset($_SESSION['success'], $_SESSION['errors']);

$hallsModel = new Halls(db());
$halls = $hallsModel->getAll();
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;

// Handle Excel import
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel_file'])) {
    if (!currentUserCan('can_manage_halls')) {
        $_SESSION['errors'] = ['You do not have permission to import hall members.'];
        header('Location: halls.php');
        exit;
    }
    $file = $_FILES['excel_file'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['errors'] = ['File upload failed'];
        header('Location: halls.php');
        exit;
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    // Check if file is actually a CSV renamed to .xlsx by checking magic bytes
    $fh = fopen($file['tmp_name'], 'rb');
    $magicBytes = $fh ? fread($fh, 4) : '';
    fclose($fh);
    $isRealXlsx = strpos($magicBytes, 'PK') === 0;
    
    // If it's .xlsx extension but contains CSV data, treat as CSV
    if ($ext === 'xlsx' && !$isRealXlsx) {
        $ext = 'csv';
    }
    
    if ($ext === 'xls') {
        $_SESSION['errors'] = ['Legacy .xls format not supported. Please save as .xlsx or .csv.'];
        header('Location: halls.php');
        exit;
    }
    if (!in_array($ext, ['xlsx', 'csv'])) {
        $_SESSION['errors'] = ['Invalid file type. Please upload .xlsx or .csv files.'];
        header('Location: halls.php');
        exit;
    }

    $importErrors = [];
    $imported = 0;

    if ($ext === 'csv') {
        $handle = fopen($file['tmp_name'], 'r');
        if ($handle !== false) {
            $header = fgetcsv($handle);
            if ($header === false) {
                $importErrors[] = 'The CSV file appears to be empty or invalid.';
            } else {
                while (($row = fgetcsv($handle)) !== false) {
                    $data = array_combine($header, $row);
                    $indexNumber = trim($data['Index Number'] ?? '');
                    $fullName = trim($data['Full Name'] ?? '');
                    $hallName = trim($data['Hall Name'] ?? '');

                    if (!$indexNumber || !$fullName || !$hallName) {
                        $importErrors[] = "Row skipped: Missing required fields";
                        continue;
                    }

                    $hall = $hallsModel->getByName($hallName);
                    if (!$hall) {
                        $importErrors[] = "Row for '{$fullName}': Hall '{$hallName}' not found";
                        continue;
                    }

                    $nameParts = explode(' ', $fullName, 2);
                    $firstName = $nameParts[0] ?? '';
                    $lastName = $nameParts[1] ?? '';

                    $existingUser = db()->fetch("SELECT id FROM users WHERE student_id = ?", [$indexNumber]);
                    if ($existingUser) {
                        $hallsModel->addMember($hall['id'], $existingUser['id']);
                        $imported++;
                        continue;
                    }

                    $studentRole = db()->fetch("SELECT id FROM roles WHERE name = 'STUDENT'", []);
                    if (!$studentRole) {
                        $importErrors[] = "Student role not configured";
                        continue;
                    }

                    $userId = db()->insert("users", [
                        "first_name" => $firstName,
                        "last_name" => $lastName,
                        "email" => $indexNumber . "@student.srcltu.edu.gh",
                        "student_id" => $indexNumber,
                        "role_id" => $studentRole["id"],
                        "is_active" => 1,
                        "password_hash" => hashPassword(DEFAULT_TEMP_PASSWORD),
                        "created_at" => date("Y-m-d H:i:s")
                    ]);

                    if ($userId) {
                        $hallsModel->addMember($hall['id'], $userId);
                        $imported++;
                    } else {
                        $importErrors[] = "Failed to create user for '{$fullName}' with student ID '{$indexNumber}'";
                    }
                }
            }
            fclose($handle);
        } else {
            $importErrors[] = 'Failed to open the CSV file for reading.';
        }
    } else {
        try {
            if (!class_exists('ZipArchive') || !function_exists('simplexml_load_string')) {
                $importErrors[] = 'PHP Zip or XML extension not available. Please convert .xlsx to .csv for import.';
            } else {
                $zip = new ZipArchive();
                if ($zip->open($file['tmp_name']) !== true) {
                    $importErrors[] = 'Failed to open the Excel file. Please ensure it is a valid .xlsx file.';
                } else {
                    $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
                    $sharedStrings = [];
                    if ($sharedStringsXml) {
                        $sharedStringsData = simplexml_load_string($sharedStringsXml);
                        if ($sharedStringsData) {
                            $idx = 0;
                            foreach ($sharedStringsData->t as $si) {
                                $sharedStrings[$idx] = (string)$si;
                                $idx++;
                            }
                        }
                    }

                    $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
                    if ($sheetXml === false) {
                        $sheetXml = $zip->getFromName('xl/worksheets/sheet.xml');
                    }

                    $zip->close();

                    if (!$sheetXml) {
                        $importErrors[] = 'Could not read worksheet from the Excel file.';
                    } else {
                        $sheetXml = simplexml_load_string($sheetXml);

                        $header = [];
                        $headerRowFound = false;
                        $rows = [];
                        $currentRow = -1;

                        foreach ($sheetXml->sheetData->row as $rowIndex => $rowNode) {
                            $currentRow++;
                            if ($currentRow == 0) continue;

                            $rowData = [];
                            $cellIndex = 0;
                            foreach ($rowNode->c as $cell) {
                                $cellRef = (string)$cell['r'];
                                $cellType = (string)$cell['t'];
                                $cellValue = isset($cell->v) ? (string)$cell->v : (string)$cell;

                                if ($cellType === 's' && isset($sharedStrings[(int)$cellValue])) {
                                    $cellValue = $sharedStrings[(int)$cellValue];
                                }

                                $rowData[] = $cellValue;
                                $cellIndex++;
                            }

                            if ($currentRow == 1) {
                                $header = $rowData;
                                $headerRowFound = true;
                                continue;
                            }

                            if (array_filter($rowData) === []) {
                                continue;
                            }

                            $rows[] = array_combine($header, $rowData);
                        }

                        if (!$headerRowFound) {
                            $importErrors[] = 'Could not detect header row in the Excel file. Please ensure the first row contains column headers.';
                        }

                        foreach ($rows as $rowData) {
                            if (!is_array($rowData)) continue;
                            $indexNumber = trim($rowData['Index Number'] ?? '');
                            $fullName = trim($rowData['Full Name'] ?? '');
                            $hallName = trim($rowData['Hall Name'] ?? '');

                            if (!$indexNumber || !$fullName || !$hallName) {
                                $importErrors[] = "Row skipped: Missing required fields";
                                continue;
                            }

                            $hall = $hallsModel->getByName($hallName);
                            if (!$hall) {
                                $importErrors[] = "Row for '{$fullName}': Hall '{$hallName}' not found";
                                continue;
                            }

                            $nameParts = explode(' ', $fullName, 2);
                            $firstName = $nameParts[0] ?? '';
                            $lastName = $nameParts[1] ?? '';

                            $existingUser = db()->fetch("SELECT id FROM users WHERE student_id = ?", [$indexNumber]);
                            if ($existingUser) {
                                $hallsModel->addMember($hall['id'], $existingUser['id']);
                                $imported++;
                                continue;
                            }

                            $studentRole = db()->fetch("SELECT id FROM roles WHERE name = 'STUDENT'", []);
                            if (!$studentRole) {
                                $importErrors[] = "Student role not configured";
                                continue;
                            }

                            $userId = db()->insert("users", [
                                "first_name" => $firstName,
                                "last_name" => $lastName,
                                "email" => $indexNumber . "@student.srcltu.edu.gh",
                                "student_id" => $indexNumber,
                                "role_id" => $studentRole["id"],
                                "is_active" => 1,
                                "password_hash" => hashPassword(DEFAULT_TEMP_PASSWORD),
                                "created_at" => date("Y-m-d H:i:s")
                            ]);

                            if ($userId) {
                                $hallsModel->addMember($hall['id'], $userId);
                                $imported++;
                            } else {
                                $importErrors[] = "Failed to create user for '{$fullName}' with student ID '{$indexNumber}'";
                            }
                        }
                    }
                }
            }
        } catch (Exception $e) {
            $importErrors[] = 'Failed to process the Excel file: ' . $e->getMessage();
        }
    }

    // If no rows were processed and no errors, the file likely contains no data
    if ($imported === 0 && empty($importErrors)) {
        $importErrors[] = 'The file contains no data or all rows were skipped.';
    }

    if ($imported > 0) {
        $_SESSION['success'] = "Successfully imported {$imported} member(s)";
    }
    if (!empty($importErrors)) {
        $_SESSION['errors'] = $importErrors;
    }
    header('Location: halls.php');
    exit;
}

// Handle hall CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!currentUserCan('can_manage_halls')) {
        $_SESSION['errors'] = ['You do not have permission to manage halls.'];
        header('Location: halls.php');
        exit;
    }
    $action = $_POST['action'] ?? $_GET['action'] ?? null;
    $id = $_POST['id'] ?? $_GET['id'] ?? null;

    $name = trim($_POST['name'] ?? '');
    $presidentName = trim($_POST['president_name'] ?? '');
    $presidentImage = null;

    // Handle president image upload
    if (isset($_FILES['president_image']) && $_FILES['president_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/halls/presidents/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $fileExt = strtolower(pathinfo($_FILES['president_image']['name'], PATHINFO_EXTENSION));
        $allowedExt = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($fileExt, $allowedExt)) {
            $fileName = 'president_' . time() . '_' . uniqid() . '.' . $fileExt;
            $filePath = $uploadDir . $fileName;
            if (move_uploaded_file($_FILES['president_image']['tmp_name'], $filePath)) {
                $presidentImage = 'uploads/halls/presidents/' . $fileName;
            }
        }
    }

    $data = [
        'name' => $name,
        'president_name' => $presidentName
    ];
    if ($presidentImage) {
        $data['president_image'] = $presidentImage;
    }

    if ($action === 'create') {
        if (!$name) {
            $_SESSION['errors'] = ['Hall name is required'];
        } else {
            $hallsModel->create($data);
            $_SESSION['success'] = 'Hall created successfully';
        }
    } elseif ($action === 'edit' && $id) {
        $hallsModel->update((int)$id, $data);
        $_SESSION['success'] = 'Hall updated successfully';
    }

    header('Location: halls.php');
    exit;
}
if ($action === 'delete' && $id) {
    if (!currentUserCan('can_manage_halls')) {
        $_SESSION['errors'] = ['You do not have permission to delete halls.'];
        header('Location: halls.php');
        exit;
    }
    $memberCount = $hallsModel->getMemberCount($id);
    if ($memberCount > 0) {
        $_SESSION['errors'] = ['Cannot delete hall with existing members. Remove all members first.'];
    } else {
        $hallsModel->delete((int)$id);
        $_SESSION['success'] = 'Hall deleted successfully';
    }
    header('Location: halls.php');
    exit;
}

$editHall = null;
if ($action === 'edit' && $id) {
    if (currentUserCan('can_manage_halls')) {
        $editHall = $hallsModel->getById($id);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> | DHLTU SRC</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;0,700;1,300,400&family=Outfit:wght@200;300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <script>window.currentUserRole = '<?php echo $currentRole; ?>';</script>
    <link rel="icon" type="image/png" href="../assets/images/logo.png">
</head>
<body>
    <div class="dashboard-layout">
        <div class="mobile-overlay" id="mobileOverlay"></div>

        <aside class="sidebar" id="sidebar">
            <button class="sidebar-toggle" id="sidebarToggle"><i class="bi bi-chevron-left"></i></button>
            <div class="sidebar-header">
                <div class="sidebar-logo">SRC</div>
                <span class="sidebar-title">DHLTU Dashboard</span>
            </div>
            <?php require_once '../include/nav-links.php'; $nav = new NavigationRBAC($currentRole); echo $nav->renderNavigation(); ?>
            <div class="sidebar-user">
                <div class="user-avatar"><?php echo strtoupper(substr($currentUser['first_name'], 0, 1) . substr($currentUser['last_name'], 0, 1)); ?></div>
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']); ?></div>
                    <div class="user-role"><span class="role-badge admin"><?php echo $currentRole; ?></span></div>
                </div>
            </div>
        </aside>

        <div class="main-content">
            <header class="dashboard-header">
                <button class="mobile-toggle" id="mobileToggle"><i class="bi bi-list"></i></button>
                <h1 class="header-title">Halls</h1>
                <div class="header-actions">
                    <a href="../logout.php" class="header-btn"><i class="bi bi-box-arrow-right"></i></a>
                </div>
            </header>

            <main class="content-body">
                <div class="dashboard-container">
                    <div class="dashboard-header-section">
                        <h2 class="dashboard-title">Halls Management</h2>
                        <p class="dashboard-subtitle">Manage student halls and bulk member registration</p>
                    </div>

                    <?php if ($success): ?>
                        <div style="background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.2);border-radius:8px;padding:16px;margin-bottom:20px;color:#22c55e;">
                            <?php echo htmlspecialchars($success); ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($errors)): ?>
                        <div style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.2);border-radius:8px;padding:16px;margin-bottom:20px;color:#ef4444;">
                            <?php foreach ($errors as $error): echo htmlspecialchars($error) . '<br>'; endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Stats -->
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon"><i class="bi bi-building"></i></div>
                            <div class="stat-info">
                                <div class="stat-value"><?php echo count($halls); ?></div>
                                <div class="stat-label">Total Halls</div>
                            </div>
                        </div>
                        <?php if (!empty($halls)): ?>
                            <?php $totalMembers = array_sum(array_column($halls, 'member_count')); ?>
                            <div class="stat-card">
                                <div class="stat-icon" style="background:rgba(34,197,94,0.15);color:#22c55e;"><i class="bi bi-people"></i></div>
                                <div class="stat-info">
                                    <div class="stat-value"><?php echo $totalMembers; ?></div>
                                    <div class="stat-label">Total Members</div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Hall Import Modal Trigger -->
                    <div class="table-container" style="margin-bottom:24px;">
                        <div style="padding:16px 24px;border-bottom:1px solid rgba(138,155,184,0.1);display:flex;justify-content:space-between;align-items:center;">
                            <h3 style="margin:0;">Bulk Member Import</h3>
                             <?php if (currentUserCan('can_manage_halls')): ?>
                             <button type="button" class="btn btn-primary" onclick="openImportModal()">
                                 <i class="bi bi-upload"></i> Upload Excel
                             </button>
                             <?php endif; ?>
                        </div>
                        <div style="padding:20px 24px;">
                            <p style="margin:0;color:#64748b;font-size:14px;">
                                Download the template and fill in members with columns: <strong>S/N, Index Number, Full Name, Hall Name</strong>.
                                Members will be automatically associated with their respective halls.
                            </p>
                            <a href="hall-members-template.csv" class="btn btn-outline" style="margin-top:12px;">
                                <i class="bi bi-download"></i> Download Template
                            </a>
                        </div>
                    </div>

                    <!-- Halls Table -->
                    <div class="table-container">
                        <div style="padding:16px 24px;border-bottom:1px solid rgba(138,155,184,0.1);display:flex;justify-content:space-between;align-items:center;">
                            <h3 style="margin:0;">Halls</h3>
                             <?php if (currentUserCan('can_manage_halls')): ?>
                             <button type="button" class="btn btn-primary" onclick="openModal()">
                                 <i class="bi bi-plus"></i> Add Hall
                             </button>
                             <?php endif; ?>
                        </div>
                        <?php if (empty($halls)): ?>
                            <div class="empty-state">
                                <div class="empty-icon"><i class="bi bi-building"></i></div>
                                <h3 class="empty-title">No halls found</h3>
                                <p class="empty-text">Click "Add Hall" to register the first hall</p>
                            </div>
                        <?php else: ?>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Hall</th>
                                        <th>President</th>
                                        <th>Members</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($halls as $index => $hall): ?>
                                        <tr>
                                            <td><?php echo $index + 1; ?></td>
                                            <td>
                                                <div class="user-info-cell">
                                                    <?php if ($hall['president_image']): ?>
                                                        <img src="../<?php echo htmlspecialchars($hall['president_image']); ?>" style="width:40px;height:40px;border-radius:50%;object-fit:cover;margin-right:10px;">
                                                    <?php else: ?>
                                                        <div class="user-avatar-table" style="background:linear-gradient(135deg, #3b82f6, #60a5fa);margin-right:10px;">
                                                            <?php echo strtoupper(substr($hall['name'], 0, 2)); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div>
                                                        <div class="user-name"><?php echo htmlspecialchars($hall['name']); ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($hall['president_name'] ?: '—'); ?></td>
                                            <td><?php echo (int)$hall['member_count']; ?></td>
                                            <td><?php echo formatDate($hall['created_at']); ?></td>
                                            <td>
                                                 <?php if (currentUserCan('can_manage_halls')): ?>
                                                 <a href="?action=edit&id=<?php echo $hall['id']; ?>" class="btn btn-sm btn-outline" style="padding:4px 8px;">
                                                     <i class="bi bi-pencil"></i>
                                                 </a>
                                                 <?php endif; ?>
                                                 <?php if (currentUserCan('can_manage_halls')): ?>
                                                 <a href="?action=delete&id=<?php echo $hall['id']; ?>" class="btn btn-sm btn-danger" style="padding:4px 8px;" onclick="return confirm('Are you sure you want to delete this hall?')">
                                                     <i class="bi bi-trash"></i>
                                                 </a>
                                                 <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Hall Modal -->
    <div id="hallModal" style="display:<?php echo ($action === 'edit' && $editHall) ? 'flex' : 'none'; ?>;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center;">
        <div style="background:#fff;border-radius:12px;max-width:500px;width:90%;max-height:90vh;overflow-y:auto;">
            <div style="padding:24px;border-bottom:1px solid #eee;display:flex;justify-content:space-between;align-items:center;">
                <h3 style="margin:0;"><?php echo $editHall ? 'Edit' : 'Add'; ?> Hall</h3>
                <button onclick="closeModal()" style="background:none;border:none;font-size:24px;cursor:pointer;">&times;</button>
            </div>
            <form method="POST" enctype="multipart/form-data" style="padding:24px;">
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($editHall['id'] ?? ''); ?>">
                <input type="hidden" name="action" value="<?php echo $editHall ? 'edit' : 'create'; ?>">

                <div class="form-group">
                    <label class="form-label">Hall Name *</label>
                    <input type="text" name="name" class="form-input" placeholder="e.g. Unity Hall" required value="<?php echo htmlspecialchars($editHall['name'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Hall President Name *</label>
                    <input type="text" name="president_name" class="form-input" placeholder="e.g. John Doe" required value="<?php echo htmlspecialchars($editHall['president_name'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">President Profile Image</label>
                    <input type="file" name="president_image" class="form-input" accept="image/*">
                    <?php if (!empty($editHall['president_image'])): ?>
                        <img src="../<?php echo htmlspecialchars($editHall['president_image']); ?>" style="height:60px;border-radius:50%;object-fit:cover;margin-top:8px;">
                    <?php endif; ?>
                </div>

                <div style="display:flex;gap:12px;justify-content:flex-end;margin-top:20px;">
                    <button type="button" onclick="closeModal()" class="btn btn-outline">Cancel</button>
                    <button type="submit" class="btn btn-primary"><?php echo $editHall ? 'Update' : 'Create'; ?> Hall</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Import Modal -->
    <div id="importModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center;">
        <div style="background:#fff;border-radius:12px;max-width:500px;width:90%;max-height:90vh;overflow-y:auto;">
            <div style="padding:24px;border-bottom:1px solid #eee;display:flex;justify-content:space-between;align-items:center;">
                <h3 style="margin:0;">Bulk Member Import</h3>
                <button onclick="closeImportModal()" style="background:none;border:none;font-size:24px;cursor:pointer;">&times;</button>
            </div>
            <form method="POST" enctype="multipart/form-data" style="padding:24px;">
                <div class="form-group">
                    <label class="form-label">Excel File (.xlsx, .csv) *</label>
                    <input type="file" name="excel_file" class="form-input" accept=".xlsx,.csv" required>
                    <small style="color:#64748b;display:block;margin-top:8px;">
                        Required columns: S/N, Index Number, Full Name, Hall Name
                    </small>
                </div>
                <div style="display:flex;gap:12px;justify-content:flex-end;margin-top:20px;">
                    <button type="button" onclick="closeImportModal()" class="btn btn-outline">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-upload"></i> Import Members</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../assets/js/sidebar.js"></script>
    <script>
        function openModal() { document.getElementById('hallModal').style.display = 'flex'; }
        function closeModal() { document.getElementById('hallModal').style.display = 'none'; }
        function openImportModal() { document.getElementById('importModal').style.display = 'flex'; }
        function closeImportModal() { document.getElementById('importModal').style.display = 'none'; }

        document.getElementById('hallModal').addEventListener('click', function(e) { if (e.target === this) closeModal(); });
        document.getElementById('importModal').addEventListener('click', function(e) { if (e.target === this) closeImportModal(); });
    </script>
</body>
</html>