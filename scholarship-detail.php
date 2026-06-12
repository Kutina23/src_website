<?php
// DHLTU SRC — Scholarship Details Page
// File: scholarship-detail.php
// Purpose: Displays detailed scholarship information with application link

$sid = $_GET['id'] ?? '';

// Scholarship data (in real app, this would come from database)
$scholarships = [
  'government' => [
    'title' => 'Government Scholarship',
    'category' => 'Government',
    'deadline' => '15 September 2026',
    'description' => 'Financial support for brilliant but needy students pursuing diploma and degree programs. This scholarship covers tuition fees and provides a monthly stipend for academic materials and personal expenses.',
    'amount' => 'Full tuition + GHS 500 monthly stipend',
    'eligibility' => [
      'Must be a Ghanaian citizen',
      'Minimum CGPA of 2.5 or equivalent',
      'Demonstrated financial need',
      'Must be enrolled in a diploma or degree program',
      'Good academic standing with no disciplinary issues'
    ],
    'documents' => [
      'Completed application form',
      'Academic transcripts',
      'Proof of financial need (parent/guardian income statement)',
      'Recommendation letter from Head of Department',
      'Copy of student ID card'
    ],
    'external_url' => 'https://www.moes.gov.gh/scholarships'
  ],
  'academic' => [
    'title' => 'Academic Excellence Scholarship',
    'category' => 'Academic',
    'deadline' => '30 October 2026',
    'description' => 'Scholarship opportunities awarded to students with outstanding academic performance. Recognizes and rewards consistent high achievers across all departments.',
    'amount' => 'GHS 2,000 - GHS 5,000 (one-time)',
    'eligibility' => [
      'Minimum CGPA of 3.5 or equivalent',
      'Must have completed at least one academic year',
      'No outstanding fees or disciplinary records',
      'Enrolled in any accredited program at DHLTU'
    ],
    'documents' => [
      'Completed application form',
      'Official academic transcript',
      'Dean\'s recommendation letter',
      'Personal statement (500 words max)'
    ],
    'external_url' => 'https://www.dhlotu.edu.gh/src/academic-scholarship'
  ],
  'international' => [
    'title' => 'International Study Grant',
    'category' => 'International',
    'deadline' => '20 November 2026',
    'description' => 'Support program for students interested in international academic opportunities and exchange programs. Provides funding for study abroad and collaborative degree programs.',
    'amount' => 'Up to GHS 15,000 for approved programs',
    'eligibility' => [
      'Minimum CGPA of 3.0 or equivalent',
      'Must be in good academic standing',
      'Accepted into an approved international program',
      'Demonstrated English proficiency (if applicable)'
    ],
    'documents' => [
      'Completed application form',
      'Academic transcript',
      'Letter of acceptance from host institution',
      'Study plan and budget breakdown',
      'Two academic reference letters'
    ],
    'external_url' => 'https://www.dhlotu.edu.gh/international-programs'
  ]
];

$scholarship = $scholarships[$sid] ?? null;
if (!$scholarship) {
  header('Location: scholarships.php');
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="<?php echo htmlspecialchars($scholarship['title']); ?> - Scholarship details and application information for DHLTU students.">
  <title><?php echo htmlspecialchars($scholarship['title']); ?> - Scholarships</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;0,700;1,300,400&family=Outfit:wght@200;300;400;500;600;700&family=Space+Mono:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
  <link rel="stylesheet" href="assets/css/main.css">
  <link rel="icon" type="image/png" href="assets/images/logo.png">
</head>
<body>

<?php include 'include/header.php'; ?>

<!-- ============================================================
     SCHOLARSHIP DETAIL HERO
     ============================================================ -->
<section class="scholarship-detail-hero">
  <div class="scholarship-detail-bg"></div>
  <div class="container">
    <a href="scholarships.php" class="back-link reveal">
      <i class="bi bi-arrow-left"></i> Back to Scholarships
    </a>
    <div class="scholarship-detail-eyebrow">
      <i class="bi bi-mortarboard"></i> <?php echo htmlspecialchars($scholarship['category']); ?> Scholarship
    </div>
    <h1 class="scholarship-detail-title"><?php echo htmlspecialchars($scholarship['title']); ?></h1>
    <div class="scholarship-meta">
      <span class="scholarship-deadline-badge">
        <i class="bi bi-calendar2"></i> Deadline: <?php echo htmlspecialchars($scholarship['deadline']); ?>
      </span>
    </div>
  </div>
</section>

<div class="cinematic-divider"></div>

<!-- ============================================================
     SCHOLARSHIP DETAIL CONTENT
     ============================================================ -->
<section class="scholarship-detail-section">
  <div class="container">
    <div class="scholarship-detail-layout">
      <!-- Main Content -->
      <div class="scholarship-detail-main">
        <div class="scholarship-amount-card reveal delay-1">
          <div class="amount-icon">
            <i class="bi bi-cash-stack"></i>
          </div>
          <div>
            <div class="amount-label">Scholarship Value</div>
            <div class="amount-value"><?php echo htmlspecialchars($scholarship['amount']); ?></div>
          </div>
        </div>

        <div class="scholarship-content-card reveal delay-2">
          <h2 class="scholarship-content-title">About This Scholarship</h2>
          <p class="scholarship-description"><?php echo htmlspecialchars($scholarship['description']); ?></p>
        </div>

        <div class="scholarship-content-card reveal delay-3">
          <h2 class="scholarship-content-title">
            <i class="bi bi-check-circle"></i> Eligibility Requirements
          </h2>
          <ul class="requirements-list">
            <?php foreach ($scholarship['eligibility'] as $req): ?>
            <li><?php echo htmlspecialchars($req); ?></li>
            <?php endforeach; ?>
          </ul>
        </div>

        <div class="scholarship-content-card reveal delay-4">
          <h2 class="scholarship-content-title">
            <i class="bi bi-file-earmark-text"></i> Required Documents
          </h2>
          <ul class="documents-list">
            <?php foreach ($scholarship['documents'] as $doc): ?>
            <li><?php echo htmlspecialchars($doc); ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>

      <!-- Sidebar -->
      <div class="scholarship-detail-sidebar">
        <div class="sidebar-card reveal delay-2">
          <h3>Quick Actions</h3>
          <a href="<?php echo htmlspecialchars($scholarship['external_url']); ?>" target="_blank" rel="noopener" class="btn-apply">
            <i class="bi bi-box-arrow-up-right"></i> Apply Now
          </a>
          <a href="scholarships.php" class="btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> View Other Scholarships
          </a>
        </div>

        <div class="sidebar-card reveal delay-3">
          <h3>Application Tips</h3>
          <ul class="tips-list">
            <li>Read all requirements carefully before applying</li>
            <li>Prepare documents early to avoid last-minute rush</li>
            <li>Submit applications before the deadline</li>
            <li>Contact SRC office for assistance if needed</li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</section>

<?php include 'include/footer.php'; ?>

<style>
  :root {
    --gold:         #C9A84C;
    --gold-light:   #E8C97A;
    --gold-dark:    #8B6914;
    --navy:         #0A1628;
    --navy-mid:     #0F2040;
    --navy-light:   #1A3060;
    --cream:        #F5F0E8;
    --text-muted:   #8A9BB8;
  }

  .scholarship-detail-hero {
    background: linear-gradient(135deg, var(--navy-mid) 0%, var(--navy) 100%);
    padding: 180px 40px 80px;
    position: relative;
  }
  .scholarship-detail-hero::before {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(ellipse at 50% 30%, rgba(201,168,76,0.10) 0%, transparent 70%);
  }
  .scholarship-detail-hero .container {
    position: relative;
    z-index: 1;
    max-width: 800px;
  }
  .back-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: var(--gold);
    text-decoration: none;
    margin-bottom: 32px;
    opacity: 0;
    transform: translateY(20px);
  }
  .back-link.visible {
    opacity: 1;
    transform: translateY(0);
  }
  .scholarship-detail-eyebrow {
    font-family: 'Space Mono', monospace;
    font-size: 10px;
    letter-spacing: 0.25em;
    text-transform: uppercase;
    color: var(--gold);
    display: inline-flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 20px;
  }
  .scholarship-detail-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: clamp(36px, 5vw, 56px);
    font-weight: 300;
    line-height: 1.1;
    color: var(--cream);
    margin-bottom: 24px;
  }
  .scholarship-deadline-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-family: 'Space Mono', monospace;
    font-size: 11px;
    letter-spacing: 0.15em;
    text-transform: uppercase;
    color: var(--gold);
    background: rgba(201,168,76,0.12);
    border: 1px solid rgba(201,168,76,0.30);
    padding: 10px 20px;
    border-radius: 2px;
  }

  .scholarship-detail-section {
    padding: 80px 40px 120px;
    background: var(--navy);
  }
  .scholarship-detail-layout {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 40px;
  }
  .scholarship-detail-main {
    display: flex;
    flex-direction: column;
    gap: 28px;
  }
  .scholarship-amount-card,
  .scholarship-content-card {
    background: var(--navy-mid);
    border: 1px solid rgba(201,168,76,0.12);
    border-radius: 2px;
    padding: 32px;
    opacity: 0;
    transform: translateY(30px);
    transition: all 0.6s ease;
  }
  .scholarship-amount-card.visible,
  .scholarship-content-card.visible {
    opacity: 1;
    transform: translateY(0);
  }
  .scholarship-content-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 24px;
    font-weight: 600;
    color: var(--cream);
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 12px;
  }
  .scholarship-content-title i {
    color: var(--gold);
    font-size: 20px;
  }
  .scholarship-description {
    font-size: 14px;
    font-weight: 300;
    color: var(--text-muted);
    line-height: 1.85;
    margin: 0;
  }
  .requirements-list,
  .documents-list {
    list-style: none;
    padding: 0;
    margin: 0;
  }
  .requirements-list li,
  .documents-list li {
    padding: 12px 0;
    border-bottom: 1px solid rgba(201,168,76,0.08);
    color: var(--text-muted);
    display: flex;
    align-items: flex-start;
    gap: 10px;
  }
  .requirements-list li::before,
  .documents-list li::before {
    content: '✓';
    color: var(--gold);
    font-weight: bold;
    flex-shrink: 0;
  }
  .requirements-list li:last-child,
  .documents-list li:last-child {
    border-bottom: none;
  }

  .amount-icon {
    width: 60px;
    height: 60px;
    background: rgba(201,168,76,0.15);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 20px;
  }
  .amount-icon i {
    font-size: 28px;
    color: var(--gold);
  }
  .amount-label {
    font-family: 'Space Mono', monospace;
    font-size: 10px;
    letter-spacing: 0.15em;
    text-transform: uppercase;
    color: var(--text-muted);
    margin-bottom: 4px;
  }
  .amount-value {
    font-family: 'Cormorant Garamond', serif;
    font-size: 24px;
    font-weight: 600;
    color: var(--gold-light);
  }

  /* Sidebar */
  .sidebar-card {
    background: var(--navy-mid);
    border: 1px solid rgba(201,168,76,0.12);
    border-radius: 2px;
    padding: 28px;
    margin-bottom: 28px;
    opacity: 0;
    transform: translateY(30px);
  }
  .sidebar-card.visible {
    opacity: 1;
    transform: translateY(0);
  }
  .sidebar-card h3 {
    font-family: 'Cormorant Garamond', serif;
    font-size: 18px;
    font-weight: 600;
    color: var(--cream);
    margin-bottom: 20px;
  }
  .btn-apply {
    display: block;
    width: 100%;
    text-align: center;
    padding: 14px 20px;
    background: linear-gradient(135deg, var(--gold), var(--gold-dark));
    color: var(--navy);
    font-family: 'Space Mono', monospace;
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.15em;
    text-transform: uppercase;
    text-decoration: none;
    border-radius: 2px;
    margin-bottom: 12px;
    transition: all 0.3s ease;
  }
  .btn-apply:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(201,168,76,0.3);
  }
  .btn-outline-secondary {
    display: block;
    width: 100%;
    text-align: center;
    padding: 12px 20px;
    background: transparent;
    color: var(--gold);
    font-family: 'Space Mono', monospace;
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.15em;
    text-transform: uppercase;
    text-decoration: none;
    border: 1px solid rgba(201,168,76,0.3);
    border-radius: 2px;
    transition: all 0.3s ease;
  }
  .btn-outline-secondary:hover {
    background: rgba(201,168,76,0.1);
  }
  .tips-list {
    list-style: none;
    padding: 0;
    margin: 0;
  }
  .tips-list li {
    padding: 10px 0;
    border-bottom: 1px solid rgba(201,168,76,0.06);
    color: var(--text-muted);
    font-size: 13px;
    line-height: 1.7;
  }
  .tips-list li:last-child {
    border-bottom: none;
  }

  @media (max-width: 900px) {
    .scholarship-detail-layout {
      grid-template-columns: 1fr;
    }
    .scholarship-detail-hero {
      padding: 140px 20px 60px;
    }
    .scholarship-detail-section {
      padding: 60px 20px 100px;
    }
  }
</style>



</body>
</html>