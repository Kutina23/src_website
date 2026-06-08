<?php
require_once "config/database.php";
require_once "config/functions.php";
require_once "models/Constitution.php";

$model = new Constitution(db());
$constitution = $model->getActive();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SRC Constitution — DHLTU Student Representative Council</title>

  <!-- SEO Meta Tags -->
  <meta name="description" content="Official constitution of the Student Representative Council at Dr. Hilla Limann Technical University. Download or preview the SRC constitution document.">
  <meta name="keywords" content="DHLTU SRC Constitution, Student Representative Council Constitution, HLTU SRC Document">
  <meta name="author" content="DHLTU SRC">

  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;0,700;1,300;1,400&family=Outfit:wght@200;300;400;500;600;700&family=Space+Mono:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

  <!-- CSS -->
  <link rel="stylesheet" href="assets/css/main.css">
<link rel="icon" type="image/png" href="assets/images/logo.png">
</head>
<body>
<?php include 'include/header.php'; ?>

<?php if (!$constitution): ?>
<header class="constitution-header">
    <div class="constitution-content">
      <h1 class="constitution-title">Constitution Unavailable</h1>
      <p style="color:var(--text-muted);">The constitution has not been uploaded yet. Please check back later.</p>
    </div>
  </header>
<?php include 'include/footer.php'; ?>
</body>
</html>
<?php return; ?>
<?php endif; ?>

<!-- Header Section -->
<header class="constitution-header">
    <div class="constitution-content">
      <h1 class="constitution-title"><?php echo htmlspecialchars($constitution['title']); ?></h1>

      <div class="constitution-meta">
        <div class="meta-item">
          <i class="bi bi-building"></i>
          <span>Dr. Hilla Limann Technical University</span>
        </div>
        <div class="meta-item">
          <i class="bi bi-calendar"></i>
          <span>Last Updated: <?php echo date('Y', strtotime($constitution['created_at'])); ?></span>
        </div>
        <div class="meta-item">
          <i class="bi bi-file-earmark-text"></i>
          <span>PDF Document - Version <?php echo htmlspecialchars($constitution['version']); ?></span>
        </div>
      </div>
    </div>
  </header>

  <!-- PDF Preview Section -->
  <section class="section" style="padding-top: 60px;">
    <div class="container">
      <div class="pdf-preview-container">
        <div class="pdf-preview-header">
          <div class="pdf-preview-title">
            <i class="bi bi-file-earmark-pdf"></i>
            <?php echo htmlspecialchars($constitution['original_filename']); ?>
          </div>
          <div class="pdf-preview-actions">
           
            <a href="<?php echo $constitution['file_path']; ?>" class="btn-primary" download>
              <i class="bi bi-download"></i> Download PDF
            </a>
          </div>
        </div>

        <!-- PDF Preview Frame -->
        <iframe
          src="<?php echo $constitution['file_path']; ?>#toolbar=0&navpanes=0&scrollbar=0"
          class="pdf-preview-frame"
          title="SRC Constitution Preview">
          <p>Your browser does not support PDF viewing. Please download the document to view it.</p>
        </iframe>
      </div>

      <!-- Download Section -->
      <div class="download-section">
        <h3>Download the Full Constitution</h3>
        <p>Get the complete SRC constitution document in PDF format for offline reading and reference.</p>
        <a href="<?php echo $constitution['file_path']; ?>" class="btn-primary" download>
          <i class="bi bi-download"></i> Download Constitution (PDF)
        </a>
      </div>

      <!-- Information Cards -->
      <div class="info-section">
        <div class="info-card">
          <div class="info-card-icon">
            <i class="bi bi-info-circle"></i>
          </div>
          <h4>Document Overview</h4>
          <p>The SRC Constitution outlines the structure, powers, and responsibilities of the Student Representative Council at DHLTU.</p>
        </div>

        <div class="info-card">
          <div class="info-card-icon">
            <i class="bi bi-shield-check"></i>
          </div>
          <h4>Student Rights</h4>
          <p>This document guarantees and protects the rights of all students, ensuring fair representation and democratic governance.</p>
        </div>

        <div class="info-card">
          <div class="info-card-icon">
            <i class="bi bi-gear"></i>
          </div>
          <h4>Governance Structure</h4>
          <p>Details the organizational structure, election processes, and operational procedures of the SRC.</p>
        </div>

        <div class="info-card">
          <div class="info-card-icon">
            <i class="bi bi-question-circle"></i>
          </div>
          <h4>Need Clarification?</h4>
          <p>Contact the SRC Secretariat for any questions regarding the constitution or its provisions.</p>
        </div>
      </div>
    </div>
  </section>

<?php include 'include/footer.php'; ?>
<script>
    // Fallback for browsers that don't support inline PDF viewing
    const pdfFrame = document.querySelector('.pdf-preview-frame');
    if (pdfFrame) {
      pdfFrame.addEventListener('error', function() {
        pdfFrame.style.display = 'none';
        const fallback = document.createElement('div');
        fallback.style.padding = '60px 40px';
        fallback.style.textAlign = 'center';
        fallback.innerHTML = `
          <i class="bi bi-file-earmark-pdf" style="font-size: 48px; color: var(--accent-red); margin-bottom: 20px; display: block;"></i>
          <h3 style="margin-bottom: 12px;">PDF Preview Not Available</h3>
          <p style="margin-bottom: 24px;">Your browser doesn't support inline PDF viewing. Please download the document to view it.</p>
          <a href="<?php echo $constitution['file_path']; ?>" class="btn-primary" download>
            <i class="bi bi-download"></i> Download PDF
          </a>
        `;
        pdfFrame.parentNode.insertBefore(fallback, pdfFrame.nextSibling);
      });
    }

    document.addEventListener('DOMContentLoaded', function() {
      // Mobile Menu Toggle
      var mobileToggle = document.querySelector('.mobile-toggle');
      var navList = document.querySelector('.nav-list');

      if (mobileToggle && window.innerWidth <= 900) {
        mobileToggle.style.display = 'flex';
      }

      if (mobileToggle && navList) {
        mobileToggle.addEventListener('click', function () {
          mobileToggle.classList.toggle('active');
          navList.classList.toggle('active');
        });
      }

      // Close mobile menu when clicking outside
      document.addEventListener('click', function (e) {
        if (mobileToggle && navList && !mobileToggle.contains(e.target) && !navList.contains(e.target)) {
          mobileToggle.classList.remove('active');
          navList.classList.remove('active');
        }
      });

      // Nav link dropdown toggle on mobile
      document.querySelectorAll('.nav-link').forEach(function (link) {
        link.addEventListener('click', function (e) {
          var navItem = link.parentElement;
          var dropdown = navItem.querySelector('.dropdown');

          if (dropdown && window.innerWidth <= 768) {
            e.preventDefault();
            document.querySelectorAll('.dropdown.open').forEach(function (d) {
              if (d !== dropdown) {
                d.classList.remove('open');
                d.closest('.nav-item')?.classList.remove('open');
              }
            });
            dropdown.classList.toggle('open');
            navItem.classList.toggle('open');
          } else {
            if (mobileToggle && navList) {
              mobileToggle.classList.remove('active');
              navList.classList.remove('active');
              document.querySelectorAll('.dropdown.open').forEach(function (d) { d.classList.remove('open'); });
              document.querySelectorAll('.nav-item.open').forEach(function (n) { n.classList.remove('open'); });
            }
          }
        });
      });
    });
  </script>
</body>
</html>