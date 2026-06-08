<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/functions.php';

$submitted = false;
$refNum    = '';
$error     = '';

// Pre-fill from logged-in user if available
$userId    = $_SESSION['user_id']    ?? null;
$userName  = $_SESSION['first_name'] ?? '';
$userEmail = $_SESSION['email']     ?? '';
$userRole  = $_SESSION['role']      ?? '';
$studentId = '';

if ($userId) {
    $user = db()->fetch(
        "SELECT first_name, last_name, email, student_id, role_id, r.name AS role
         FROM users u
         JOIN roles r ON r.id = u.role_id
         WHERE u.id = ?",
        [$userId]
    );
    if ($user) {
        $userName  = $user['first_name'] . ' ' . $user['last_name'];
        $userEmail = $user['email'];
        $userRole  = $user['role'];
        $studentId = $user['student_id'] ?? '';
    }
}

// Handle POST submission from any user (logged-in or guest field entry)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_request'])) {
    $postStudentId = trim($_POST['studentId'] ?? '');
    $postFullName  = trim($_POST['fullName']  ?? '');
    $postEmail     = trim($_POST['email']     ?? '');
    $postPhone     = trim($_POST['phone']     ?? '');
    $docType       = trim($_POST['documentType'] ?? '');
    $purpose       = trim($_POST['purpose']   ?? '');
    $remarks       = trim($_POST['remarks']   ?? '');

    if ($postStudentId === '' || $postFullName === '' || $postEmail === '' || $docType === '' || $purpose === '') {
        $error = 'Please fill in all required fields.';
    } else {
        $refNum = strtoupper('REQ-' . bin2hex(random_bytes(6)));
        // Insert document request for all users (logged-in or guest)
        try {
            $insertData = [
                'request_token' => $refNum,
                'document_type' => $docType,
                'purpose'       => $purpose,
                'remarks'       => $remarks !== '' ? $remarks : null,
                'status'        => 'PENDING'
            ];
            if ($userId) {
                $insertData['user_id'] = $userId;
            }
            db()->insert('document_requests', $insertData);
            $submitted = true;
            $studentId = $postStudentId;
            $userName  = $postFullName;
            $userEmail = $postEmail;
        } catch (Exception $e) {
            $error = 'Submission failed. Please try again later.';
        }
    }
}
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Document Request — DHLTU SRC Student Portal</title>
  <meta name="description" content="Request official documents from DHLTU SRC. Transcripts, certificates, recommendation letters, and more — all in one place.">
  <meta name="keywords" content="DHLTU SRC Document Request, Student Transcript, Certificate, Recommendation Letter, HLTU">
  <meta name="author" content="DHLTU SRC">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;0,700;1,300;1,400&family=Outfit:wght@200;300;400;500;600;700&family=Space+Mono:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
  <link rel="stylesheet" href="assets/css/main.css">
<link rel="icon" type="image/png" href="assets/images/logo.png">
</head>
<body>
<?php include __DIR__ . '/include/header.php'; ?>

<!-- ══════════════════════════════════════════════════════
     HERO
══════════════════════════════════════════════════════ -->
<section class="complaint-hero">
  <div class="complaint-hero-content">
    <div class="complaint-hero-eyebrow">Student Portal &middot; Document Request</div>
    <h1 class="complaint-hero-title">Request a <em>Document</em></h1>
    <p class="complaint-hero-desc">
      Need an official transcript, certificate, or letter from the university?
      Submit your request here and the SRC secretary will process it promptly.
    </p>
  </div>
</section>

<div class="cinematic-divider"></div>

<!-- ══════════════════════════════════════════════════════
     DOCUMENT REQUEST FORM SECTION
══════════════════════════════════════════════════════ -->
<section class="doc-form-section" id="submit-request">
  <div class="container">

    <a href="index.php#portal" class="back-link-board"><i class="bi bi-arrow-left"></i> Back to Home</a>

    <!-- Step indicator -->
    <div class="form-steps">
      <div class="form-step active" id="step1-indicator">
        <div class="form-step-num">1</div>
        <span class="form-step-label">Details</span>
      </div>
      <div class="form-step-line" id="line-1"></div>
      <div class="form-step" id="step2-indicator">
        <div class="form-step-num">2</div>
        <span class="form-step-label">Review</span>
      </div>
      <div class="form-step-line" id="line-2"></div>
      <div class="form-step" id="step3-indicator">
        <div class="form-step-num">3</div>
        <span class="form-step-label">Submitted</span>
      </div>
    </div>

    <!-- ── STEP 1: DOCUMENT REQUEST FORM ── -->
    <form id="documentRequestForm" method="POST" action="" novalidate>

      <!-- Identity block -->
      <p class="form-section-heading complaint-hero-eyebrow">Step 1 — Your Details</p>

      <div class="form-row-2col">
        <div class="form-group">
          <label>Student ID <span class="required">*</span></label>
          <input type="text" id="studentId" name="studentId"
                 placeholder="e.g. SRC/2024/00123" maxlength="50" required
                 value="<?php echo htmlspecialchars($studentId); ?>">
        </div>
        <div class="form-group">
          <label>Full Name <span class="required">*</span></label>
          <input type="text" id="fullName" name="fullName"
                 placeholder="Surname / First Name / Other Names" maxlength="120" required
                 value="<?php echo htmlspecialchars($userName); ?>">
        </div>
      </div>

      <div class="form-row-2col">
        <div class="form-group">
          <label>Email Address <span class="required">*</span></label>
          <input type="email" id="email" name="email"
                 placeholder="your.email@example.com" maxlength="120" required
                 value="<?php echo htmlspecialchars($userEmail); ?>">
        </div>
        <div class="form-group">
          <label>Phone Number</label>
          <input type="tel" id="phone" name="phone"
                 placeholder="+233 XX XXX XXXX" maxlength="30"
                 value="<?php echo htmlspecialchars(trim($_POST['phone'] ?? '')); ?>">
        </div>
      </div>

      <!-- Document block -->
      <p class="form-section-heading complaint-hero-eyebrow">Step 2 — Document Details</p>

      <div class="form-group">
        <label>Document Type <span class="required">*</span></label>
        <select id="documentType" name="documentType" required>
          <option value="">Select document type</option>
          <option value="Academic Transcript"      <?php echo (isset($_POST['documentType']) && $_POST['documentType'] === 'Academic Transcript')      ? 'selected' : ''; ?>>Academic Transcript</option>
          <option value="Certificate of Enrolment" <?php echo (isset($_POST['documentType']) && $_POST['documentType'] === 'Certificate of Enrolment') ? 'selected' : ''; ?>>Certificate of Enrolment</option>
          <option value="Recommendation Letter"    <?php echo (isset($_POST['documentType']) && $_POST['documentType'] === 'Recommendation Letter')    ? 'selected' : ''; ?>>Recommendation Letter</option>
          <option value="Graduation Clearance"     <?php echo (isset($_POST['documentType']) && $_POST['documentType'] === 'Graduation Clearance')     ? 'selected' : ''; ?>>Graduation Clearance</option>
          <option value="Hostel Allocation Letter" <?php echo (isset($_POST['documentType']) && $_POST['documentType'] === 'Hostel Allocation Letter') ? 'selected' : ''; ?>>Hostel Allocation Letter</option>
          <option value="Financial Clearance"      <?php echo (isset($_POST['documentType']) && $_POST['documentType'] === 'Financial Clearance')      ? 'selected' : ''; ?>>Financial Clearance</option>
          <option value="Exam Permit"              <?php echo (isset($_POST['documentType']) && $_POST['documentType'] === 'Exam Permit')              ? 'selected' : ''; ?>>Exam Permit</option>
          <option value="ID Card Replacement"      <?php echo (isset($_POST['documentType']) && $_POST['documentType'] === 'ID Card Replacement')      ? 'selected' : ''; ?>>ID Card Replacement</option>
          <option value="Other"                    <?php echo (isset($_POST['documentType']) && $_POST['documentType'] === 'Other')                    ? 'selected' : ''; ?>>Other</option>
        </select>
      </div>

      <div class="form-group">
        <label>Purpose <span class="required">*</span></label>
        <textarea id="purpose" name="purpose" required
                  placeholder="State the reason for this document request — e.g. visa application, scholarship, employment, further studies, etc."><?php echo htmlspecialchars(trim($_POST['purpose'] ?? '')); ?></textarea>
      </div>

      <div class="form-group">
        <label>Additional Remarks</label>
        <textarea id="remarks" name="remarks" rows="3"
                  placeholder="Any extra details the secretariat should know before processing your request…"><?php echo htmlspecialchars(trim($_POST['remarks'] ?? '')); ?></textarea>
      </div>

      <!-- Error banner -->
      <?php if ($error): ?>
        <div class="alert alert-error" style="background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);color:#ef4444;padding:12px 16px;border-radius:8px;margin-bottom:16px;font-size:14px;"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>

      <!-- Submit row -->
      <div class="form-submit-row">
        <span class="form-submit-hint"><i class="bi bi-shield-check"></i> Your details are used only for processing this request.</span>
        <button type="submit" class="btn-submit" name="submit_request" value="1" id="submitBtn">Submit Request &rarr;</button>
      </div>
    </form>

    <!-- ── CONFIRMATION ── -->
    <div class="confirm-section" id="step-confirm" style="display: <?php echo $submitted ? 'block' : 'none'; ?>;">
      <div class="confirm-icon"><i class="bi bi-check-lg"></i></div>
      <h2 class="confirm-title">Request<br>Submitted</h2>
      <p class="confirm-width">
        Your document request has been received and forwarded to the SRC Secretariat.
        It is currently marked as <strong>Pending</strong> and will be reviewed shortly.
        Keep your reference number to track progress.
      </p>
      <p class="confirm-uppercase">Your Reference Number</p>
      <div class="confirm-token" id="confirmToken"><?php echo $submitted ? htmlspecialchars($refNum) : '—'; ?></div>
      <div class="confirm-actions">
        <button class="btn-submit" id="copyTokenBtn" onclick="copyToken()">Copy Reference <i class="bi bi-clipboard"></i></button>
        <a href="index.php#portal" class="btn-outline btn-full-width">Back to Home</a>
      </div>
    </div>

  </div>
</section>

<!-- ══════════════════════════════════════════════════════
     JAVASCRIPT
══════════════════════════════════════════════════════ -->
<script>
  function copyToken() {
    var token = document.getElementById('confirmToken').textContent;
    navigator.clipboard.writeText(token).then(function() {
      var btn  = document.getElementById('copyTokenBtn');
      var orig = btn.innerHTML;
      btn.innerHTML = '<i class="bi bi-check2"></i> Copied!';
      setTimeout(function() { btn.innerHTML = orig; }, 2000);
    });
  }

  window.setStep = function(n) {
    [1, 2, 3].forEach(function(i) {
      var step = document.getElementById('step' + i + '-indicator');
      var line = document.getElementById('line-' + i);
      var cls  = 'form-step';
      if (i < n)  cls += ' done';
      if (i === n) cls += ' active';
      step.className = cls;
      if (line) line.className = 'form-step-line' + (i < n ? ' done' : '');
    });
  };

  (function() {
    var mobileToggle = document.querySelector('.mobile-toggle');
    var navList      = document.querySelector('.nav-list');
    if (!mobileToggle || !navList) return;
    mobileToggle.addEventListener('click', function() {
      mobileToggle.classList.toggle('active');
      navList.classList.toggle('active');
    });
    document.addEventListener('click', function(e) {
      if (!mobileToggle.contains(e.target) && !navList.contains(e.target)) {
        mobileToggle.classList.remove('active'); navList.classList.remove('active');
      }
    });
    document.querySelectorAll('.nav-link').forEach(function(link) {
      link.addEventListener('click', function() {
        mobileToggle.classList.remove('active'); navList.classList.remove('active');
      });
    });
  })();
</script>

<?php include __DIR__ . '/include/footer.php'; ?>

</body>
</html>
