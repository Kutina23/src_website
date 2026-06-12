<?php
require_once 'config/database.php';
require_once 'models/Clubs.php';
require_once 'models/SiteSettings.php';

$db = Database::getInstance();
$clubsModel = new Clubs($db);
$settingsModel = new SiteSettings($db);

// Fetch all active clubs
$clubs = $clubsModel->getAllActive();
$siteSettings = $settingsModel->getHeroSection();
$sessionLabel = $siteSettings['session'] ?? date('Y') . '/' . ((int)date('Y') + 1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Clubs & Societies - DHLTU SRC</title>
<link rel="stylesheet" href="assets/css/main.css">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;0,700;1,300;1,400&family=Outfit:wght@200;300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<link rel="icon" type="image/png" href="assets/images/logo.png">
</head>
<body>

<?php include 'include/header.php'; ?>

<!-- Page Header -->
<div class="clubs-page-header">
  <h1 class="clubs-page-title">Clubs &amp; <em>Societies</em></h1>
  <p class="clubs-page-desc">Discover, join, or register your club. Our campus hosts over 20 active clubs spanning technology, arts, sports, and more.</p>
  
  <div class="clubs-tabs">
    <button class="clubs-tab active" data-tab="all-clubs">All Clubs</button>
    <button class="clubs-tab" data-tab="register-club">Register New Club</button>
  </div>
</div>

<!-- All Clubs Grid -->
<div class="clubs-grid active" id="all-clubs">
  <?php foreach ($clubs as $club): 
    $imageSeed = strtolower(str_replace([' ', '&', '/'], '-', $club['name']));
    $presidentName = trim(($club['president_first'] ?? '') . ' ' . ($club['president_last'] ?? ''));
  ?>
  <div class="club-card">
    <div class="club-face">
      <?php if ($club['logo_path']): ?>
        <img src="<?php echo htmlspecialchars($club['logo_path']); ?>" alt="<?php echo htmlspecialchars($club['name']); ?> logo">
      <?php else: ?>
        <img src="https://picsum.photos/seed/<?php echo $imageSeed; ?>/400/300" alt="<?php echo htmlspecialchars($club['name']); ?>">
      <?php endif; ?>
      <span class="club-badge"><?php echo htmlspecialchars($club['category'] ?: 'General'); ?></span>
      <span class="club-status" style="z-index:2;"></span>
    </div>
    <div class="club-info">
      <h3 class="club-name"><?php echo htmlspecialchars($club['name']); ?></h3>
      <div class="club-president">President: <?php echo $presidentName ?: 'Vacant'; ?></div>
      <div class="club-meta">
        <span class="club-members"><?php echo number_format($club['member_count']); ?> Members</span>
        <span class="club-category">Active</span>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
  
  <!-- Registration Card -->
  <div class="club-card new-club-card">
    <div class="new-club-icon">
      <i class="bi bi-plus-lg"></i>
    </div>
    <div class="new-club-title">Register Your Club</div>
    <div class="new-club-desc">Have an idea for a new club? Start the registration process here.</div>
    <button class="clubs-tab" style="padding: 8px 24px; font-size: 12px;" data-tab="register-club">Start Registration</button>
  </div>
</div>

<!-- Registration Form Section -->
<div class="register-section" id="register-club">
  <div class="register-container">
    <h2 class="clubs-page-title" style="font-size: 36px; margin-bottom: 30px;">Register <em>New Club</em></h2>
    
    <?php include 'include/club-registration-form.php'; ?>
  </div>
</div>

<?php include 'include/footer.php'; ?>

<script>
  (function() {
    const tabs = document.querySelectorAll('.clubs-tab');
    const allClubs = document.getElementById('all-clubs');
    const registerClub = document.getElementById('register-club');
    
    function switchTab(tabId) {
      tabs.forEach(tab => tab.classList.toggle('active', tab.dataset.tab === tabId));
      allClubs.classList.toggle('active', tabId === 'all-clubs');
      registerClub.classList.toggle('active', tabId === 'register-club');
    }
    
    tabs.forEach(tab => {
      tab.addEventListener('click', () => switchTab(tab.dataset.tab));
    });
  })();
</script>

</body>
</html>