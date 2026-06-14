<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/functions.php';

$submitted = false;
$token     = '';
$error     = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_complaint'])) {
    $subject  = trim($_POST['subject'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $priority = trim($_POST['priority'] ?? '');
    $desc     = trim($_POST['description'] ?? '');

    if ($subject === '' || $category === '' || $priority === '' || $desc === '') {
        $error = 'Please fill in all required fields.';
    } else {
        $token = strtoupper('TKN-' . bin2hex(random_bytes(6)));
        try {
            db()->insert('complaints', [
                'complaint_token' => $token,
                'subject'         => $subject,
                'description'     => $desc,
                'category'        => $category,
                'priority'        => $priority,
                'status'          => 'OPEN'
            ]);
            $submitted = true;
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
  <title>Submit a Complaint — DHLTU SRC Student Portal</title>
  <meta name="description" content="Submit an anonymous complaint to the DHLTU SRC. Academic issues, welfare concerns, hostel matters — your voice will be heard.">
  <meta name="keywords" content="DHLTU SRC Complaint, Student Complaint Form, HLTU Grievance, Anonymous Student Complaint">
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
    <div class="complaint-hero-eyebrow">Student Portal &middot; Complaint Desk</div>
    <h1 class="complaint-hero-title">Submit a <em>Complaint</em></h1>
    <p class="complaint-hero-desc">
      Your voice matters. Submit any academic, welfare, or campus concern — completely
      <strong>anonymous</strong>. The SRC PRO will review and escalate your case to the appropriate officer.
    </p>
  </div>
</section>

<div class="cinematic-divider"></div>

<!-- ══════════════════════════════════════════════════════
     COMPLAINT FORM SECTION
══════════════════════════════════════════════════════ -->
<section class="complaint-form-section" id="submit-complaint">
  <div class="container">

    <a href="index.php#portal" class="back-link-board"><i class="bi bi-arrow-left"></i> Back to Home</a>

    <!-- Steps indicator -->
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

    <!-- ── STEP 1: Complaint Form ── -->
    <div id="step-form">

      <!-- Track existing complaint -->
      <div class="track-form-card" id="track-card">
        <p class="complaint-hero-eyebrow complaint-hero-eyebrow-spaced">Track a Complaint</p>
        <div class="form-group">
          <label>Tracking Token</label>
          <input type="text" id="trackTokenInput" placeholder="e.g. TKN-XXXXXXXXX">
        </div>
        <button type="button" class="btn-submit btn-full-width" onclick="trackComplaint()">Track My Complaint</button>
      </div>

      <!-- Main complaint form card -->
      <div class="complaint-form-card">
        <div class="anon-badge">
          <i class="bi bi-incognito"></i>
          Your identity remains completely anonymous — no login or name required
        </div>

        <form id="complaintForm" novalidate method="POST" action="">

          <!-- Subject -->
          <div class="form-group">
            <label>Subject <span class="required">*</span></label>
            <input type="text" id="subject" name="subject"
                   placeholder="Brief summary of your complaint"
                   maxlength="200" required value="<?php echo isset($_POST['subject']) ? htmlspecialchars($_POST['subject']) : ''; ?>">
          </div>

          <!-- Category + Priority — two-column grid -->
          <div class="form-row-2col">
            <div class="form-group">
              <label>Category <span class="required">*</span></label>
              <select id="category" name="category" required>
                <option value="">Select a category</option>
                <option value="Academic Affairs"    <?php echo (isset($_POST['category']) && $_POST['category'] === 'Academic Affairs')    ? 'selected' : ''; ?>>Academic Affairs</option>
                <option value="Welfare &amp; Support" <?php echo (isset($_POST['category']) && $_POST['category'] === 'Welfare & Support') ? 'selected' : ''; ?>>Welfare &amp; Support</option>
                <option value="Elections"            <?php echo (isset($_POST['category']) && $_POST['category'] === 'Elections')            ? 'selected' : ''; ?>>Elections</option>
                <option value="Clubs &amp; Societies" <?php echo (isset($_POST['category']) && $_POST['category'] === 'Clubs & Societies')   ? 'selected' : ''; ?>>Clubs &amp; Societies</option>
                <option value="Financial Matters"    <?php echo (isset($_POST['category']) && $_POST['category'] === 'Financial Matters')    ? 'selected' : ''; ?>>Financial Matters</option>
                <option value="Hostel &amp; Accommodation" <?php echo (isset($_POST['category']) && $_POST['category'] === 'Hostel & Accommodation') ? 'selected' : ''; ?>>Hostel &amp; Accommodation</option>
                <option value="Health Services"      <?php echo (isset($_POST['category']) && $_POST['category'] === 'Health Services')      ? 'selected' : ''; ?>>Health Services</option>
                <option value="Disciplinary"         <?php echo (isset($_POST['category']) && $_POST['category'] === 'Disciplinary')         ? 'selected' : ''; ?>>Disciplinary</option>
                <option value="Infrastructure"       <?php echo (isset($_POST['category']) && $_POST['category'] === 'Infrastructure')       ? 'selected' : ''; ?>>Infrastructure</option>
                <option value="General"              <?php echo (isset($_POST['category']) && $_POST['category'] === 'General')              ? 'selected' : ''; ?>>General</option>
              </select>
            </div>

            <div class="form-group">
              <label>Priority <span class="required">*</span></label>
              <div class="priority-group">
                <div class="priority-option">
                  <input type="radio" name="priority" id="p-low" value="LOW"    <?php echo (!isset($_POST['priority']) || (isset($_POST['priority']) && $_POST['priority'] === 'LOW'))    ? 'checked' : ''; ?>>
                  <label for="p-low" class="priority-low"><i class="bi bi-flag"></i> Low</label>
                </div>
                <div class="priority-option">
                  <input type="radio" name="priority" id="p-med" value="MEDIUM" <?php echo (isset($_POST['priority']) && $_POST['priority'] === 'MEDIUM') ? 'checked' : ''; ?>>
                  <label for="p-med" class="priority-med"><i class="bi bi-exclamation-circle"></i> Medium</label>
                </div>
                <div class="priority-option">
                  <input type="radio" name="priority" id="p-high" value="HIGH"   <?php echo (isset($_POST['priority']) && $_POST['priority'] === 'HIGH')   ? 'checked' : ''; ?>>
                  <label for="p-high" class="priority-high"><i class="bi bi-exclamation-triangle"></i> High</label>
                </div>
                <div class="priority-option">
                  <input type="radio" name="priority" id="p-urgent" value="URGENT" <?php echo (isset($_POST['priority']) && $_POST['priority'] === 'URGENT') ? 'checked' : ''; ?>>
                  <label for="p-urgent" class="priority-urgent"><i class="bi bi-fire"></i> Urgent</label>
                </div>
              </div>
            </div>

          </div>

          <!-- Description -->
          <div class="form-group">
            <label>Full Description <span class="required">*</span></label>
            <textarea id="description" name="description"
                      placeholder="Describe your concern in as much detail as possible. Include date, time, location, and any relevant individuals when applicable."
                      required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
          </div>

          <!-- Error banner -->
          <?php if ($error): ?>
            <div class="alert alert-error" style="background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);color:#ef4444;padding:12px 16px;border-radius:8px;margin-bottom:16px;font-size:14px;"><?php echo htmlspecialchars($error); ?></div>
          <?php endif; ?>

          <!-- Submit row -->
          <div class="form-submit-row">
            <span class="form-submit-hint"><i class="bi bi-shield-check"></i> No personal data is stored. All submissions are anonymous.</span>
            <button type="submit" class="btn-submit" name="submit_complaint" value="1" id="submitBtn">Submit Complaint &rarr;</button>
          </div>

        </form>
      </div>

      <!-- Info strip -->
      <div class="complaint-info-strip">
        <div class="info-strip-item">
          <div class="info-strip-icon"><i class="bi bi-incognito"></i></div>
          <div><div class="info-strip-title">100% Anonymous</div><div class="info-strip-desc">No name, student ID, or contact info is collected. Your identity is never linked to your submission.</div></div>
        </div>
        <div class="info-strip-item">
          <div class="info-strip-icon"><i class="bi bi-key"></i></div>
          <div><div class="info-strip-title">Unique Tracking Token</div><div class="info-strip-desc">Every complaint receives a unique token on submission. Use it to check the status of your case at any time.</div></div>
        </div>
        <div class="info-strip-item">
          <div class="info-strip-icon"><i class="bi bi-speedometer2"></i></div>
          <div><div class="info-strip-title">Fast Response</div><div class="info-strip-desc">Cases are triaged by PRO within 24–48 hours. High-priority submissions are prioritised immediately.</div></div>
        </div>
      </div>
    </div>

    <!-- ── TOKEN POPUP MODAL ── -->
    <div id="tokenModal" style="display:<?php echo $submitted ? 'flex' : 'none'; ?>;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:2000;align-items:center;justify-content:center;">
      <div style="background:#fff;border-radius:18px;max-width:440px;width:92%;box-shadow:0 30px 70px rgba(0,0,0,.3);text-align:center;padding:32px 28px 28px;">
        <div style="width:60px;height:60px;border-radius:50%;background:rgba(34,197,94,.1);display:flex;align-items:center;justify-content:center;margin:0 auto 18px;">
          <i class="bi bi-check-lg" style="font-size:28px;color:#22c55e;"></i>
        </div>
        <h3 style="font-family:var(--font-display);font-size:22px;margin:0 0 8px;color:#000;">Complaint Submitted</h3>
        <p style="font-size:14px;color:#000;margin:0 0 20px;line-height:1.6;">
          Your complaint has been received. Save your tracking token below — you will need it to track your case.
        </p>
        <div style="background:rgba(201,168,76,.06);border:1px solid rgba(201,168,76,.25);border-radius:12px;padding:16px 18px;margin-bottom:20px;">
          <div style="font-size:10px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.08em;margin-bottom:6px;">Your Tracking Token</div>
          <div id="confirmTokenPopup" style="font-family:'Space Mono',monospace;font-size:18px;font-weight:700;color:var(--gold);letter-spacing:.04em;word-break:break-all;"><?php echo $submitted ? htmlspecialchars($token) : '—'; ?></div>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;justify-content:center;">
          <button onclick="copyTokenPopup()" class="btn-submit" style="margin-bottom:0;min-width:160px;">
            <i class="bi bi-clipboard"></i> Copy Token
          </button>
          <button onclick="closeTokenPopup()" class="btn-outline" style="margin-bottom:0;min-width:140px;">
            Done
          </button>
        </div>
      </div>
    </div>

    <!-- ── CONFIRMATION (fallback below form, hidden when modal shown) ── -->
    <div class="confirm-section" id="step-confirm" style="display:none;">
      <div class="confirm-icon"><i class="bi bi-check-lg"></i></div>
      <h2 class="confirm-title">Complaint<br>Submitted</h2>
      <p class="confirm-width">
        Your complaint has been successfully submitted and forwarded to the SRC Secretariat.
        Save your tracking token to check the status of your case at any time.
      </p>
      <p class="confirm-uppercase">Your Tracking Token</p>
      <div class="confirm-token" id="confirmToken"><?php echo $submitted ? htmlspecialchars($token) : '—'; ?></div>
      <div class="confirm-actions">
        <button class="btn-submit" id="copyTokenBtn" onclick="copyToken()">Copy Token <i class="bi bi-clipboard"></i></button>
        <a href="index.php#portal" class="btn-outline btn-full-width">Back to Home</a>
      </div>
    </div>

  </div>
</section>

<!-- ══════════════════════════════════════════════════════
     JAVASCRIPT
══════════════════════════════════════════════════════ -->


<?php include __DIR__ . '/include/footer.php'; ?>

<script>
function copyTokenPopup() {
    const token = document.getElementById('confirmTokenPopup').textContent;
    const btn = event.target.closest('button');
    const original = btn.innerHTML;
    navigator.clipboard.writeText(token).then(() => {
        btn.innerHTML = 'Copied! <i class="bi bi-check2"></i>';
        setTimeout(() => { btn.innerHTML = original; }, 2000);
    }).catch(() => {
        alert('Copy failed. Please copy manually: ' + token);
    });
}
function closeTokenPopup() {
    document.getElementById('tokenModal').style.display = 'none';
    document.getElementById('step-confirm').style.display = 'block';
}
</script>

</body>
</html>
