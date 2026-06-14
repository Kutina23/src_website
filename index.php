<?php
require_once 'config/database.php';
require_once 'config/functions.php';
require_once 'models/Council.php';
require_once 'models/News.php';
require_once 'models/Clubs.php';
require_once 'models/Gallery.php';
require_once 'models/Elections.php';
require_once 'models/Services.php';
require_once 'models/SiteSettings.php';
require_once 'models/Projects.php';

$db = Database::getInstance();
$councilModel = new Council($db);
$newsModel = new News($db);
$clubsModel = new Clubs($db);
$galleryModel = new Gallery($db);
$electionsModel = new Elections($db);
$servicesModel  = new Services($db);
$projectsModel  = new Projects($db);
$settingsModel  = new SiteSettings($db);

// Fetch dynamic data
$councilMembers = $councilModel->getAllActive();
$latestNews = $newsModel->getLatest(5);
$featuredNews = $newsModel->getFeatured();
$clubs = $clubsModel->getAllActive(8);
$galleryImages = $galleryModel->getImagesBySection('GALLERY', 5);
$galleryVideos = $galleryModel->getVideosBySection('VIDEOS', 5);
$siteSettings = $settingsModel->getHeroSection();
$aboutSettings = $settingsModel->getAboutSection();
$contactSettings = $settingsModel->getContactInfo();

// Fetch election dynamic data
$upcomingElections = $electionsModel->getUpcoming();
$activeElection    = $electionsModel->getActiveElection();
$allActiveElections = $electionsModel->getAllActive();
$electionStats     = $electionsModel->getStats();
$allEligibility    = $electionsModel->getEligibilityByElection($activeElection['id'] ?? 0);

// Fetch services dynamically
$allServices = $servicesModel->getAllActive(['order_by' => 'display_order ASC, created_at ASC']);

// Fetch SRC projects (up to 6 on homepage)
$srcProjects = $projectsModel->getAllActive([], 6);

// Fetch dean and president data dynamically
$deanImages = $galleryModel->getDeanImages(100); // Get up to 100 hero images for carousel
$presidentImage = $galleryModel->getPresidentImage();
$deanMember = $councilModel->getByPosition('DEAN');
$presidentMember = $councilModel->getByPosition('PRESIDENT');
$deanName = $settingsModel->getDeanName();
// Fall back to council member DB record if no site_settings key exists yet
if (empty($deanName) && $deanMember) {
    $deanName = trim($deanMember['first_name'] . ' ' . $deanMember['last_name']);
}
// Final fallback
if (empty($deanName)) {
    $deanName = 'Akosua Boatemaa Frimpong';
}

// Use first image as fallback for backwards compatibility
$deanImageSrc  = !empty($deanImages[0]) ? $deanImages[0]['file_path'] : 'https://picsum.photos/seed/dean/800/600';
$presidentImageSrc = $presidentImage ? $presidentImage['file_path'] : 'https://picsum.photos/seed/president/800/600';
// Get dynamic text settings
$deanTitle = $settingsModel->getDeanTitle();
$deanSubtitle = $settingsModel->getDeanSubtitle();
// Fall back to council member DB record if no site_settings key exists yet
// Dean name: from site_settings (upsert'd via admin) → council DB as secondary → final fallback
$presidentPostfix = $settingsModel->getPresidentPostfix();
// Set president name with fallback
$presidentName  = $presidentMember  ? trim($presidentMember['first_name'] . ' ' . $presidentMember['last_name']) : 'SRC President';

if (empty($presidentPostfix)) {
    $presidentPostfix = '2024/' . (int)date('Y')+1; // final fallback
}

// Local fallback collections when DB returns no gallery rows
$fallbackGallery = [
  ['file_path' => 'https://picsum.photos/seed/dean/800/600',       'alt_text' => 'SRC General Assembly', 'caption' => 'Governance'],
  ['file_path' => 'assets/images/logo.webp',                        'alt_text' => 'Cultural Night',        'caption' => 'Culture'],
  ['file_path' => 'assets/images/logo.webp',                        'alt_text' => 'Graduation Day',        'caption' => 'Academics'],
  ['file_path' => 'https://picsum.photos/seed/president/800/600',  'alt_text' => 'Sports Day',            'caption' => 'Sports'],
  ['file_path' => 'https://picsum.photos/seed/dean/800/600',       'alt_text' => 'SRC Elections',         'caption' => 'Democracy'],
  ];
// Footer Session Year: use council term if available, else settings
$sessionLabel = $siteSettings['session'] ?? date('Y') . '/' . ((int)date('Y') + 1);
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>DHLTU SRC — Student Representative Council | Dr. Hilla Limann Technical University</title>

<!-- SEO Meta Tags -->
<meta name="description" content="Official website of the Student Representative Council (SRC) at Dr. Hilla Limann Technical University. Championing student rights, fostering excellence, and building a vibrant campus community.">
<meta name="keywords" content="DHLTU SRC, Student Representative Council, Dr. Hilla Limann Technical University, SRC Ghana, Student Leadership, Academic Affairs, Student Welfare, Campus Life">
<meta name="author" content="DHLTU SRC">
<meta name="robots" content="index, follow">
<meta name="language" content="English">
<meta name="application-name" content="DHLTU SRC">
<meta name="theme-color" content="#0A1628">

<!-- Open Graph / Facebook -->
<meta property="og:type" content="website">
<meta property="og:locale" content="en_GH">
<meta property="og:site_name" content="DHLTU SRC">
<meta property="og:url" content="https://hltu.edu.gh/src/">
<meta property="og:title" content="DHLTU SRC — Student Representative Council">
<meta property="og:description" content="Official website of the Student Representative Council at Dr. Hilla Limann Technical University. Championing student rights and fostering excellence.">
<meta property="og:image" content="https://hltu.edu.gh/src/assets/images/og-image.jpg">
<meta property="og:image:alt" content="DHLTU SRC official website banner">

<!-- Twitter -->
<meta property="twitter:card" content="summary_large_image">
<meta property="twitter:site" content="@dhltu_src">
<meta property="twitter:creator" content="@dhltu_src">
<meta property="twitter:url" content="https://src2025.com/">
<meta property="twitter:title" content="DHLTU SRC — Student Representative Council">
<meta property="twitter:description" content="Official website of the Student Representative Council at Dr. Hilla Limann Technical University.">
<meta property="twitter:image" content="https://src2025.com/assets/images/twitter-image.jpg">
<meta property="twitter:image:alt" content="DHLTU SRC official website banner">

<!-- Canonical URL -->
<link rel="canonical" href="https://src2025.com/">

<!-- Favicon -->
<link rel="icon" type="image/png" href="assets/images/logo.png">
<link rel="apple-touch-icon" href="assets/images/apple-touch-icon.png">

<!-- Structured Data (JSON-LD) -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Organization",
  "name": "DHLTU SRC - Student Representative Council",
  "url": "https://src2025.com/",
  "logo": "https://src2025.com/assets/images/logo.png",
  "description": "Official Student Representative Council of Dr. Hilla Limann Technical University, championing student rights and fostering excellence.",
  "address": {
    "@type": "PostalAddress",
    "streetAddress": "Main Campus",
    "addressLocality": "Wa",
    "addressCountry": "Ghana"
  },
  "contactPoint": {
    "@type": "ContactPoint",
    "telephone": "+233 (0)v 393-XXX-XXX",
    "email": "src@hltu.edu.gh",
    "contactType": "customer service"
  },
  "sameAs": [
    "https://twitter.com/dhltu_src",
    "https://facebook.com/dhltu.src",
    "https://linkedin.com/company/dhltu-src"
  ]
}
</script>

<link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;0,700;1,300;1,400&family=Outfit:wght@200;300;400;500;600;700&family=Space+Mono:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" media="print" onload="this.media='all'">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" media="print" onload="this.media='all'">
<link rel="stylesheet" href="assets/css/main.css">



</head>
<body>

<div class="cursor" id="cursor"></div>
<div class="cursor-ring" id="cursorRing"></div>
<div class="scroll-progress"><div class="scroll-progress-bar" id="scrollBar"></div></div>
<button class="back-top" id="backTop" aria-label="Back to top" onclick="window.scrollTo({top:0,behavior:'smooth'})" style="cursor:pointer;">↑</button>

<?php include 'include/header.php'; ?>


<!-- ══════════════════════════════════
      SECTION 1 — HERO
══════════════════════════════════ -->
<section class="section" id="hero">
<div class="hero-bg-grid" aria-hidden="true"></div>
<div class="hero-orb hero-orb-1" aria-hidden="true"></div>
<div class="hero-orb hero-orb-2" aria-hidden="true"></div>

  <div class="hero-left">
    <div class="hero-tag reveal">Empowering Students Since 1992</div>
    <h1 class="hero-title kinetic-title ml1">
      <span class="text-wrapper">
        <span class="line line1"></span>
        <span class="hero-word">Shaping</span>
        <span class="hero-word hero-word-gold">Tomorrow's</span>
        <span class="hero-word">Leaders</span>
        <span class="line line2"></span>
      </span>
    </h1>
    <p class="hero-title-sub reveal delay-2">Student Representative Council <br> Dr. Hilla Limann Technical University</p>
  <p class="hero-desc reveal delay-3">
    A unified digital platform for the Student Representative Council of Dr. Hilla Limann Technical University — managing elections, welfare, clubs, events, and student advocacy with transparency and excellence.
  </p>
   
  </div>

  <div class="hero-right reveal-right delay-2">
    <div class="hero-visual">
      <!-- Automated Image Carousel -->
      <div class="hero-carousel">
        <div class="carousel-track">
           <?php if (!empty($deanImages)): ?>
             <?php foreach ($deanImages as $index => $image): ?>
               <div class="carousel-slide<?php echo $index === 0 ? ' active' : ''; ?>" style="background-image: url('<?php echo htmlspecialchars($image['file_path']); ?>');">
                 <div class="carousel-content">
                   <div class="carousel-caption">
                     <!-- Dean information removed -->
                   </div>
                 </div>
               </div>
             <?php endforeach; ?>
            <?php else: ?>
              <!-- Fallback single image -->
              <div class="carousel-slide active" style="background-image: url('https://picsum.photos/seed/dean/800/600');">
                <div class="carousel-content">
                  <div class="carousel-caption">
                    <!-- Dean information removed -->
                  </div>
                </div>
              </div>
            <?php endif; ?>
        </div>
        <!-- Carousel Navigation -->
        <div class="carousel-nav">
          <button class="carousel-btn carousel-prev" aria-label="Previous slide">
            <i class="bi bi-chevron-left"></i>
          </button>
          <button class="carousel-btn carousel-next" aria-label="Next slide">
            <i class="bi bi-chevron-right"></i>
          </button>
        </div>
        <!-- Carousel Indicators -->
        <div class="carousel-indicators">
          <?php if (!empty($deanImages)): ?>
            <?php foreach ($deanImages as $index => $image): ?>
              <div class="carousel-indicator<?php echo $index === 0 ? ' active' : ''; ?>" data-index="<?php echo $index; ?>"></div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
      
      <div class="about-pattern" aria-hidden="true"></div>
      <div class="hero-corner tl" aria-hidden="true"></div>
      <div class="hero-corner tr" aria-hidden="true"></div>
      <div class="hero-corner bl" aria-hidden="true"></div>
      <div class="hero-corner br" aria-hidden="true"></div>
    </div>
    <!-- Floating Badge Above Image -->
    <div class="hero-floating-badge">
      <div class="badge-dot" aria-hidden="true"></div>
      <div class="badge-text">
        <strong>Portal Active</strong>
        <span><?php echo $siteSettings['session']; ?> Academic Year</span>
      </div>
    </div>
  </div>
</section>

<div class="cinematic-divider"></div>

<!-- ══════════════════════════════════
     SECTION 2 — ABOUT / MISSION
══════════════════════════════════ -->
<section class="section" id="about">
  <div class="reveal-left">
    <div class="about-eyebrow">About the SRC</div>
    <h2 class="section-title">Our <em>Mission</em><br>& Purpose</h2>
    <p class="section-body">
      The Student Representative Council (SRC) of Dr. Hilla Limann Technical University is the supreme governing body of students, committed to advocating for student rights, fostering a vibrant campus culture, and building a bridge between students and university administration.
    </p>
    <p class="section-body">
      Through this management system, we bring transparency, efficiency, and digital innovation to every layer of student governance — from elections to welfare, from clubs to complaints resolution.
    </p>
    <div class="about-values">
      <div class="value-item reveal delay-1">
        <div class="value-icon"><i class="bi bi-balance"></i></div>
        <div class="value-title">Integrity</div>
        <div class="value-desc">Upholding the highest ethical standards in all SRC activities.</div>
      </div>
      <div class="value-item reveal delay-2">
        <div class="value-icon"><i class="bi bi-megaphone"></i></div>
        <div class="value-title">Advocacy</div>
        <div class="value-desc">Championing student rights, welfare, and academic needs.</div>
      </div>
      <div class="value-item reveal delay-3">
        <div class="value-icon"><i class="bi bi-people"></i></div>
        <div class="value-title">Inclusivity</div>
        <div class="value-desc">Every student represented, every voice heard equally.</div>
      </div>
      <div class="value-item reveal delay-4">
        <div class="value-icon"><i class="bi bi-rocket"></i></div>
        <div class="value-title">Innovation</div>
        <div class="value-desc">Leveraging technology for a smarter student experience.</div>
      </div>
    </div>
  </div>

  <div class="about-visual reveal-right delay-2">
    <div class="about-img-main" style="position:relative;overflow:hidden;">
      <!-- President Image as Background -->
      <img src="<?php echo htmlspecialchars($presidentImageSrc); ?>" alt="SRC President" loading="lazy" style="position:absolute;top:0;left:0;width:100%;height:100%;object-fit:cover;">
      
      
    </div>
     <div class="about-quote-card">
       <p class="quote-text"><?php echo htmlspecialchars($settingsModel->getSetting('president_quote', 'The SRC exists to serve — with every policy, every decision, every action focused on student wellbeing.')); ?></p>
       <div class="quote-attr">— SRC President, <?php echo htmlspecialchars($presidentPostfix); ?></div>
     </div>
  </div>
</section>

<div class="cinematic-divider"></div>

<!-- ══════════════════════════════════
     SECTION 3 — SERVICES
══════════════════════════════════ -->
<section class="section" id="services">
  <div class="services-header">
    <div class="services-eyebrow">What We Offer</div>
    <h2 class="section-title reveal">Core <em>Services</em><br>&amp; Functions</h2>
  </div>
  <div class="services-grid">
    <?php foreach ($allServices as $index => $svc): ?>
    <div class="service-card reveal delay-<?php echo ($index % 3) + 1; ?>">
      <div class="service-num"><?php echo sprintf('%02d', $index + 1); ?></div>
      <div class="service-icon-wrap"><i class="bi <?php echo htmlspecialchars($svc['icon'] ?: 'bi-star'); ?>"></i></div>
      <div class="service-title"><?php echo htmlspecialchars($svc['title']); ?></div>
      <div class="service-desc"><?php echo htmlspecialchars($svc['description'] ?: ''); ?></div>
      <a href="services.php" class="service-link">Explore</a>
    </div>
    <?php endforeach; ?>
    <?php if (empty($allServices)): ?>
    <div style="grid-column:1/-1;text-align:center;padding:40px;color:var(--text-muted);">No services available</div>
    <?php endif; ?>
  </div>
</section>

<div class="cinematic-divider"></div>

<!-- ══════════════════════════════════
      SECTION 4 — EXECUTIVE COUNCIL
══════════════════════════════════ -->
<section class="section" id="council">
  <div class="council-header">
    <div class="council-eyebrow reveal">Executive Council</div>
    <h2 class="section-title reveal delay-1">Meet Your <em>Representatives</em></h2>
    <p class="section-body reveal delay-2" style="max-width:600px;">The elected executives who serve as the voice and driving force of DHLTU's student body for the <?php echo $siteSettings['session']; ?> academic year.</p>
  </div>
  <div class="council-carousel">
    <div class="council-carousel-track">
    <?php foreach ($councilMembers as $index => $member): 
      $initials = strtoupper(substr($member['first_name'], 0, 1) . substr($member['last_name'], 0, 1));
      $imageSrc = !empty($member['profile_image_path']) ? $member['profile_image_path'] : 'https://picsum.photos/seed/president-avatar/400/500';
    ?>
    <div class="council-card reveal delay-<?php echo $index + 1; ?>" data-index="<?php echo $index; ?>">
      <div class="council-avatar" data-initials="<?php echo $initials; ?>">
        <img src="<?php echo $imageSrc; ?>" alt="<?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?>" loading="lazy" class="active" onerror="this.src='https://picsum.photos/seed/member-<?php echo $index; ?>/400/500';this.onerror=null;">
        <div class="council-overlay"></div>
        <div class="council-avatar-inner"><?php echo $initials; ?></div>
      </div>
      <div class="council-info">
        <div class="council-role"><?php echo htmlspecialchars($member['position'] ?: $member['role']); ?></div>
        <div class="council-name"><?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?></div>
      </div>
    </div>
    <?php endforeach; ?>
    </div><!-- /council-carousel-track -->
    
  </section>

<div class="cinematic-divider"></div>

<!-- ══════════════════════════════════
      SECTION 5 — SRC PROJECTS
══════════════════════════════════ -->
<section class="section" id="projects">
  <div class="services-header">
    <div class="projects-eyebrow reveal">SRC Projects</div>
    <h2 class="section-title reveal delay-1">SRC <em>Projects</em></h2>
  </div>
  <div class="services-grid">
     <?php foreach ($srcProjects as $index => $project): ?>
       <div class="service-card reveal delay-<?php echo ($index % 3) + 1; ?>">
         <div class="service-num"><?php echo sprintf('%02d', $index + 1); ?></div>
         <?php if (!empty($project['image_path'])): ?>
           <div class="project-image-wrap">
              <img src="<?php echo htmlspecialchars($project['image_path']); ?>" alt="<?php echo htmlspecialchars($project['title']); ?>" loading="lazy">
           </div>
         <?php else: ?>
           <div class="service-icon-wrap">
             <i class="bi bi-folder2-open"></i>
           </div>
         <?php endif; ?>
         <div class="service-title"><?php echo htmlspecialchars($project['title']); ?></div>
         <div class="service-status">
           <span class="badge badge-<?php echo $project['status']==='ongoing'?'active':($project['status']==='upcoming'?'info':''); ?>">
             <?php echo ucfirst($project['status']); ?>
           </span>
         </div>
         <div class="service-desc"><?php echo htmlspecialchars($project['description'] ?: ''); ?></div>
       </div>
     <?php endforeach; ?>
  </div>
  <div class="services-cell" style="grid-column:-1/1;text-align:center;padding:36px 0 12px;">
    <a href="projects.php" class="btn btn-primary">View All Projects</a>
  </div>
</section>

<div class="cinematic-divider"></div>

<!-- ══════════════════════════════════
      SECTION 6 — NEWS & EVENTS
══════════════════════════════════ -->
<section class="section" id="news">
  <div class="news-eyebrow reveal">Latest Updates</div>
  <h2 class="section-title reveal delay-1">News &amp; <em>Announcements</em></h2>
  <div style="height:48px;"></div>
  <div class="news-layout">
    <div class="news-featured reveal-left delay-1" onclick="location.href='news-detail.php?id=<?= $featuredNews ? $featuredNews['id'] : '' ?>'" style="cursor:pointer;">
      <div class="news-featured-bg" style="<?php if ($featuredNews && !empty($featuredNews['featured_image'])): ?>background: url('<?= htmlspecialchars($featuredNews['featured_image']) ?>') center/cover no-repeat, radial-gradient(ellipse at 70% 20%, rgba(201,168,76,0.1), transparent 60%), linear-gradient(to bottom, transparent 20%, rgba(10,22,40,0.85) 40%, rgba(10,22,40,0.95));<?php endif; ?>"></div>
      <div class="news-featured-tag">Featured</div>
      
    </div>
    <div class="news-list reveal-right delay-2">
      <?php foreach ($latestNews as $news): 
        $date = new DateTime($news['published_at']);
      ?>
      <div class="news-item" onclick="location.href='news-detail.php?id=<?= $news['id'] ?>'" style="cursor:pointer;">
        <div class="news-item-date"><span><?php echo $date->format('d'); ?></span><?php echo $date->format('M'); ?></div>
        <div>
          <div class="news-item-title"><?php echo htmlspecialchars($news['title']); ?></div>
      <div class="news-item-cat"><?php echo htmlspecialchars($news['category']); ?></div>
        </div>
      </div>
    <?php endforeach; ?>
    </div>
  </div>
  </div>
</section>

<div class="cinematic-divider"></div>



<!-- ══════════════════════════════════
     SECTION 10 — GALLERY / EVENTS
══════════════════════════════════ -->
<section class="section" id="gallery">
  <div class="gallery-header">
    <div class="gallery-eyebrow reveal">Campus Moments</div>
    <h2 class="section-title reveal delay-1">Events <em>Gallery</em></h2>
    <p class="section-body reveal delay-2" style="max-width:500px;margin:0 auto;">Capturing the spirit, energy, and excellence of HLTU student life throughout the academic year.</p>
  </div>
  
  <!-- Gallery Tabs -->
  <div class="gallery-tabs reveal delay-3">
    <button class="gallery-tab active" data-tab="images">Images</button>
    <button class="gallery-tab" data-tab="videos">Videos</button>
  </div>
  
<!-- Images Gallery -->
   <div class="gallery-content active" id="images-gallery">
     <div class="gallery-grid">
       <?php 
       $displayImages = !empty($galleryImages) ? $galleryImages : $fallbackGallery;
       foreach ($displayImages as $index => $image): ?>
       <div class="gallery-item reveal delay-<?php echo min($index + 1, 5); ?>">
          <img src="<?php echo htmlspecialchars($image['file_path']); ?>" alt="<?php echo htmlspecialchars($image['alt_text']); ?>" loading="lazy" class="gallery-image active">
         <div class="gallery-overlay">
           <div class="gallery-overlay-tag"><?php echo htmlspecialchars($image['caption'] ?: 'Gallery'); ?></div>
           <div class="gallery-overlay-title"><?php echo htmlspecialchars($image['alt_text'] ?: 'Campus Moment'); ?></div>
         </div>
       </div>
       <?php endforeach; ?>
     </div>
   </div>
  
<!-- Videos Gallery -->
   <div class="gallery-content" id="videos-gallery">
     <div class="gallery-grid">
        <?php 
        $displayVideos = !empty($galleryVideos) ? $galleryVideos : [];
        foreach ($displayVideos as $index => $video): ?>
        <div class="gallery-item video-item reveal delay-<?php echo min($index + 1, 5); ?>">
          <video muted loop>
            <source src="<?php echo htmlspecialchars($video['file_path']); ?>" type="video/mp4">
          </video>
          <div class="play-button">▶</div>
          <div class="gallery-overlay">
            <div class="gallery-overlay-tag">Event</div>
            <div class="gallery-overlay-title"><?php echo htmlspecialchars($video['caption'] ?: 'Video'); ?></div>
          </div>
        </div>
         <?php endforeach; ?>
         <?php if (empty($displayVideos)): ?>
         <div style="grid-column:1/-1;text-align:center;padding:40px;color:var(--text-muted);">No videos available</div>
         <?php endif; ?>
     </div>
    </div>
  </section>

 <!-- ══════════════════════════════════
      LIGHTBOX — Gallery Image/Video Preview
 ══════════════════════════════════ -->
 <div class="lightbox-overlay" id="galleryLightbox">
   <div class="lightbox-shutter" id="lightboxShutter"></div>
   <button class="lightbox-close" id="lightboxClose" aria-label="Close"><i class="bi bi-x"></i></button>
   <button class="lightbox-nav lightbox-prev" id="lightboxPrev" aria-label="Previous"><i class="bi bi-chevron-left"></i></button>
   <button class="lightbox-nav lightbox-next" id="lightboxNext" aria-label="Next"><i class="bi bi-chevron-right"></i></button>
   <div class="lightbox-content" id="lightboxContent"></div>
   <div class="lightbox-caption" id="lightboxCaption"></div>
   <div class="lightbox-counter" id="lightboxCounter"></div>
 </div>

 <div class="cinematic-divider"></div>

<!-- ══════════════════════════════════
     SECTION 7 — STUDENT PORTAL
══════════════════════════════════ -->
<section class="section" id="portal">
  <div class="portal-inner">
    <div>
      <div class="portal-eyebrow reveal">Digital Infrastructure</div>
      <h2 class="section-title reveal delay-1">The <em>Student</em><br>Portal</h2>
      <p class="section-body reveal delay-2">A centralised digital hub where every student can access SRC services, track requests, pay dues, and engage with their representative government — anytime, anywhere.</p>
      <div class="portal-features">
        <div class="portal-feature reveal delay-2">
          <div class="portal-feat-icon"><i class="bi bi-clipboard"></i></div>
          <div>
            <div class="portal-feat-title">Complaint & Request Management</div>
            <div class="portal-feat-desc">Submit and track academic or welfare complaints with real-time status updates from SRC officers.</div>
          </div>
        </div>
      </div>
    </div>
    <div class="portal-mockup reveal-right delay-2">
      <div class="mockup-bar">
        <div class="mockup-dot"></div>
        <div class="mockup-dot"></div>
        <div class="mockup-dot"></div>
        <div class="mockup-url">src.hltu.edu.gh/dashboard</div>
      </div>
      <div class="mockup-body">
        <div class="mockup-header">
          <div class="mockup-title">SRC Dashboard — 2024/25</div>
          <div class="mockup-badge">Live</div>
        </div>
        <div class="mockup-cards">
          <div class="mockup-card">
            <div class="mockup-card-val">4.2K</div>
            <div class="mockup-card-lbl">Students</div>
          </div>
          <div class="mockup-card">
            <div class="mockup-card-val">127</div>
            <div class="mockup-card-lbl">Open Cases</div>
          </div>
          <div class="mockup-card">
            <div class="mockup-card-val">98%</div>
            <div class="mockup-card-lbl">Resolved</div>
          </div>
        </div>
        <div style="font-size:10px;letter-spacing:0.12em;text-transform:uppercase;color:var(--text-muted);margin-bottom:8px;">Monthly Complaints Resolved</div>
        <div class="mockup-bar-chart" id="barChart">
          <div class="bar-col" style="height:40%"></div>
          <div class="bar-col" style="height:65%"></div>
          <div class="bar-col" style="height:55%"></div>
          <div class="bar-col" style="height:80%"></div>
          <div class="bar-col" style="height:70%"></div>
          <div class="bar-col" style="height:90%"></div>
          <div class="bar-col" style="height:75%"></div>
        </div>
        <div style="display:flex;justify-content:space-between;margin-top:6px;">
          <span style="font-size:9px;color:var(--text-muted)">Nov</span>
          <span style="font-size:9px;color:var(--text-muted)">Dec</span>
          <span style="font-size:9px;color:var(--text-muted)">Jan</span>
          <span style="font-size:9px;color:var(--text-muted)">Feb</span>
          <span style="font-size:9px;color:var(--text-muted)">Mar</span>
          <span style="font-size:9px;color:var(--text-muted)">Apr</span>
          <span style="font-size:9px;color:var(--text-muted)">May</span>
        </div>
      </div>
    </div>
  </div>
</section>

<div class="cinematic-divider"></div>

<!-- ══════════════════════════════════
     SECTION 7 — ELECTIONS
══════════════════════════════════ -->
<section class="section" id="elections">
  <div class="elections-grid">

    <?php
    // Use the active election if available, otherwise the first upcoming election for the left-column intro
    $featuredElection = $activeElection ?: (!empty($upcomingElections) ? $upcomingElections[0] : null);
    $eligibilityItems = [];
    if ($activeElection) {
        $eligibilityItems = $allEligibility;
    }
    if (empty($eligibilityItems)) {
        $eligibilityItems = [
            ['criteria_text' => 'All enrolled and financially cleared students are automatically eligible to vote.'],
            ['criteria_text' => 'A valid DHLTU student identity card is required at verification.'],
            ['criteria_text' => 'Eligibility lists are published by the Electoral Commission at least 3 days before voting opens.'],
            ['criteria_text' => 'Faculty and department representatives vote in their respective pools.'],
        ];
    }
    $descriptionText = $featuredElection['description'] ?? ''
        ?: 'Our transparent, digital election system ensures every student vote counts. From registration to results — a clean, auditable process that builds trust and strengthens democracy on campus.';
    ?>
    <div>
      <div class="elections-eyebrow reveal">Democratic Governance</div>
      <h2 class="section-title reveal delay-1"><em>Elections</em><br>Management</h2>
      <p class="section-body reveal delay-2"><?php echo htmlspecialchars($descriptionText); ?></p>

      <div class="election-steps">
        <?php
        $stepTitles  = ['Voter Registration', 'Candidate Nomination', 'Digital Voting', 'Transparent Results'];
        $stepDescs   = [
            'All enrolled students automatically eligible. Confirm details via student portal before voting window opens.',
            'Submit nomination forms, vetting by Electoral Commission, and approved candidates publish manifestos.',
            'Secure, anonymous online ballot with one-vote-per-student verification and real-time participation tracking.',
            'Instant, publicly verifiable results with full vote tallies published for complete accountability.',
        ];
        foreach (array_keys($stepTitles) as $i):
        ?>
        <div class="election-step reveal delay-<?php echo $i + 2; ?>">
          <div class="step-num"><?php echo sprintf('%02d', $i + 1); ?></div>
          <div class="step-content">
            <div class="step-title"><?php echo htmlspecialchars($stepTitles[$i]); ?></div>
            <div class="step-desc"><?php echo htmlspecialchars($stepDescs[$i]); ?></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="election-visual reveal-right delay-2">
      <?php if ($activeElection): ?>
        <div class="election-vis-title">Live: <?php echo htmlspecialchars($activeElection['title'] ?? 'Election'); ?></div>
        <div class="vote-bars">
          <div class="vote-bar-item">
            <div class="vote-bar-header">
              <span class="vote-bar-name">Voting in Progress</span>
              <span class="vote-bar-pct">Live</span>
            </div>
            <div class="vote-bar-track"><div class="vote-bar-fill" data-width="100%" style="width:0%;background:linear-gradient(90deg,var(--gold-dark),var(--gold))"></div></div>
          </div>
        </div>
        <div style="margin-top:32px;padding-top:24px;border-top:1px solid rgba(201,168,76,0.1);">
          <div style="font-size:10px;letter-spacing:0.15em;text-transform:uppercase;color:var(--text-muted);margin-bottom:12px;">Election Cycle Stats</div>
          <div style="display:flex;gap:20px;flex-wrap:wrap;margin-bottom:16px;">
            <div>
              <div style="font-family:'Cormorant Garamond',serif;font-size:28px;font-weight:700;color:var(--gold-light);"><?php echo (int)($electionStats['total'] ?? 0); ?></div>
              <div style="font-size:10px;letter-spacing:0.1em;text-transform:uppercase;color:var(--text-muted);">Total Elections</div>
            </div>
            <div>
              <div style="font-family:'Cormorant Garamond',serif;font-size:28px;font-weight:700;color:var(--gold-light);"><?php echo (int)($electionStats['upcoming'] ?? 0); ?></div>
              <div style="font-size:10px;letter-spacing:0.1em;text-transform:uppercase;color:var(--text-muted);">Upcoming</div>
            </div>
            <div>
              <div style="font-family:'Cormorant Garamond',serif;font-size:28px;font-weight:700;color:#22c55e;"><?php echo (int)($electionStats['ongoing'] ?? 0); ?></div>
              <div style="font-size:10px;letter-spacing:0.1em;text-transform:uppercase;color:var(--text-muted);">Active</div>
            </div>
            <div>
              <div style="font-family:'Cormorant Garamond',serif;font-size:28px;font-weight:700;color:#8A9BB8;"><?php echo (int)($electionStats['completed'] ?? 0); ?></div>
              <div style="font-size:10px;letter-spacing:0.1em;text-transform:uppercase;color:var(--text-muted);">Completed</div>
            </div>
          </div>
          <a href="upcoming-elections.php" class="btn-outline" style="font-size:12px;padding:8px 20px;"><i class="bi bi-calendar-check"></i> View Full Schedule</a>
        </div>
          <?php if (!empty($activeElection['election_date'])): ?>
          <div style="font-size:12px;color:var(--text-muted);">
            <i class="bi bi-calendar2"></i> <?php echo formatDate($activeElection['election_date'], 'l, F j, Y'); ?>
            <?php if (!empty($activeElection['start_time'])): ?>· <?php echo formatDateTime($activeElection['election_date'] . ' ' . $activeElection['start_time'], 'g:i A'); ?> &ndash; <?php echo formatDateTime($activeElection['election_date'] . ' ' . $activeElection['end_time'], 'g:i A'); endif; ?>
          </div>
          <?php endif; ?>
          <?php if (!empty($activeElection['location'])): ?>
          <div style="font-size:12px;color:var(--text-muted);margin-top:4px;"><i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($activeElection['location']); ?></div>
          <?php endif; ?>
        </div>
      <?php elseif ($featuredElection): ?>
        <div class="election-vis-title"><?php echo htmlspecialchars($featuredElection['title'] ?: 'Upcoming Election'); ?></div>
        <div class="vote-bars">
          <div class="vote-bar-item">
            <div class="vote-bar-header">
              <span class="vote-bar-name">Upcoming</span>
              <span class="vote-bar-pct">—</span>
            </div>
            <div class="vote-bar-track"><div class="vote-bar-fill" data-width="0%" style="width:0%;background:rgba(201,168,76,0.25)"></div></div>
          </div>
        </div>
        <div style="margin-top:32px;padding-top:24px;border-top:1px solid rgba(201,168,76,0.1);">
          <div style="font-size:10px;letter-spacing:0.15em;text-transform:uppercase;color:var(--text-muted);margin-bottom:12px;">Schedule</div>
          <div class="status-upcoming" style="display:inline-flex;align-items:center;gap:6px;padding:6px 14px;border-radius:4px;font-size:13px;color:var(--gold);background:rgba(201,168,76,0.08);border:1px solid rgba(201,168,76,0.2);margin-bottom:12px;">
            <i class="bi bi-bell"></i> Upcoming
          </div>
          <?php if (!empty($featuredElection['election_date'])): ?>
          <div style="font-size:12px;color:var(--text-muted);">
            <i class="bi bi-calendar2"></i> <?php echo formatDate($featuredElection['election_date'], 'l, F j, Y'); ?>
            <?php if (!empty($featuredElection['start_time'])): ?>· <?php echo formatDateTime($featuredElection['election_date'] . ' ' . $featuredElection['start_time'], 'g:i A'); ?> &ndash; <?php echo formatDateTime($featuredElection['election_date'] . ' ' . $featuredElection['end_time'], 'g:i A'); endif; ?>
          </div>
          <?php else: ?>
          <div style="font-size:12px;color:var(--text-muted);">Date to be announced</div>
          <?php endif; ?>
        </div>
      <?php else: ?>
        <div class="election-vis-title">Election Schedule</div>
        <div class="vote-bars">
          <div class="vote-bar-item">
            <div class="vote-bar-header">
              <span class="vote-bar-name">No Active Election</span>
              <span class="vote-bar-pct">TBD</span>
            </div>
            <div class="vote-bar-track"><div class="vote-bar-fill" data-width="0%" style="width:0%;background:rgba(201,168,76,0.25)"></div></div>
          </div>
        </div>
        <div style="margin-top:32px;padding-top:24px;border-top:1px solid rgba(201,168,76,0.08);">
          <div style="font-size:12px;color:var(--text-muted);line-height:1.8;">No elections are currently scheduled. Check back soon or contact the Electoral Commission for latest updates.</div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<div class="cinematic-divider"></div>

<!-- ══════════════════════════════════
      SECTION 8 — CLUBS & SOCIETIES
══════════════════════════════════ -->
<section class="section" id="clubs">
  <div class="clubs-header">
    <div>
      <div class="clubs-eyebrow reveal">Campus Life</div>
      <h2 class="section-title reveal delay-1">Clubs &amp; <em>Societies</em></h2>
    </div>
    <a href="clubs.php" class="btn-outline reveal delay-2">View All Clubs</a>
  </div>
  <div class="clubs-grid">
    <?php foreach ($clubs as $index => $club): 
      $icons = ['bi-laptop', 'bi-trophy', 'bi-palette', 'bi-globe', 'bi-book', 'bi-music-note', 'bi-newspaper', 'bi-bank'];
      $icon = $icons[$index % count($icons)];
    ?>
    <div class="club-card reveal delay-<?php echo $index + 1; ?>">
      <div class="club-emoji">
        <?php if (!empty($club['logo_path'])): ?>
          <img src="<?php echo htmlspecialchars($club['logo_path']); ?>" alt="<?php echo htmlspecialchars($club['name']); ?> logo" loading="lazy">
        <?php else: ?>
          <i class="bi <?php echo $icon; ?>"></i>
        <?php endif; ?>
      </div>
      <div class="club-name"><?php echo htmlspecialchars($club['name']); ?></div>
      <div class="club-members"><?php echo number_format($club['member_count']); ?> Members</div>
      <div class="club-tag"><?php echo htmlspecialchars($club['category'] ?: 'General'); ?></div>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<div class="cinematic-divider"></div>

<!-- ══════════════════════════════════
     SECTION 9 — WELFARE & SUPPORT
══════════════════════════════════ -->
<section class="section" id="welfare">
  <div class="reveal-left">
    <div class="welfare-eyebrow">Student Support</div>
    <h2 class="section-title">Welfare &amp; <em>Care</em></h2>
    <p class="section-body">Your wellbeing is our foremost responsibility. The SRC Welfare Desk provides support across health, mental wellness, emergency situations, and financial hardship — no student is left behind.</p>
    <div class="welfare-cards">
      <div class="welfare-card reveal delay-1">
        <div class="welfare-icon"><i class="bi bi-heart-pulse"></i></div>
        <div>
          <div class="welfare-title">Health & Medical Aid</div>
          <div class="welfare-desc">Coordination with campus clinic, health insurance guidance, and medical emergency support protocols.</div>
        </div>
      </div>
      <div class="welfare-card reveal delay-2">
        <div class="welfare-icon"><i class="bi bi-emoji-smile"></i></div>
        <div>
          <div class="welfare-title">Mental Health Support</div>
          <div class="welfare-desc">Confidential counselling sessions, peer support networks, and mental wellness awareness programmes.</div>
        </div>
      </div>
      <div class="welfare-card reveal delay-3">
        <div class="welfare-icon"><i class="bi bi-cash-stack"></i></div>
        <div>
          <div class="welfare-title">Emergency Financial Aid</div>
          <div class="welfare-desc">Hardship fund applications, scholarship referrals, and bursary coordination for students in need.</div>
        </div>
      </div>
      <div class="welfare-card reveal delay-4">
        <div class="welfare-icon"><i class="bi bi-house-heart"></i></div>
        <div>
          <div class="welfare-title">Accommodation Advocacy</div>
          <div class="welfare-desc">Representing student concerns on hall conditions, allocation processes, and residential policy.</div>
        </div>
      </div>
    </div>
  </div>
  <div class="reveal-right delay-3">
    <div class="welfare-hotline">
      <div class="hotline-label">SRC Welfare Hotline — Available 24/7</div>
      <div class="hotline-desc">Reach our welfare desk officers directly for urgent student support needs and emergency assistance.</div>
      <a href="#" class="btn-primary">Contact Welfare Desk</a>
    </div>
    <div style="margin-top:24px;padding:32px;border:1px solid rgba(201,168,76,0.1);background:rgba(201,168,76,0.02);">
      <div style="font-size:10px;letter-spacing:0.15em;text-transform:uppercase;color:var(--text-muted);margin-bottom:20px;">Welfare Cases This Semester</div>
      <div style="display:flex;gap:24px;flex-wrap:wrap;">
        <div>
          <div style="font-family:'Cormorant Garamond',serif;font-size:32px;font-weight:700;color:var(--gold-light);">247</div>
          <div style="font-size:10px;letter-spacing:0.1em;text-transform:uppercase;color:var(--text-muted);">Cases Received</div>
        </div>
        <div>
          <div style="font-family:'Cormorant Garamond',serif;font-size:32px;font-weight:700;color:#22c55e;">231</div>
          <div style="font-size:10px;letter-spacing:0.1em;text-transform:uppercase;color:var(--text-muted);">Resolved</div>
        </div>
        <div>
          <div style="font-family:'Cormorant Garamond',serif;font-size:32px;font-weight:700;color:#febc2e;">16</div>
          <div style="font-size:10px;letter-spacing:0.1em;text-transform:uppercase;color:var(--text-muted);">Pending</div>
        </div>
      </div>
    </div>
  </div>
</section>

<div class="cinematic-divider"></div>


 <!-- ══════════════════════════════════
       SECTION 11 — CONTACT
══════════════════════════════════ -->
<section class="section" id="contact">
  <div class="reveal-left">
    <div class="contact-eyebrow">Get In Touch</div>
    <h2 class="section-title">Connect <em>With Us</em></h2>
    <p class="section-body">Our offices are open to all students. Reach out through any of the channels below, or visit us directly at the SRC secretariat on campus.</p>
    <div class="contact-info">
      <div class="contact-item">
        <div class="contact-icon"><i class="bi bi-geo-alt"></i></div>
        <div>
          <div class="contact-label">Location</div>
          <div class="contact-value"><?php echo $contactSettings['location']; ?></div>
        </div>
      </div>
      <div class="contact-item">
        <div class="contact-icon"><i class="bi bi-envelope"></i></div>
        <div>
          <div class="contact-label">Email</div>
          <div class="contact-value"><?php echo $contactSettings['email']; ?></div>
        </div>
      </div>
      <div class="contact-item">
        <div class="contact-icon"><i class="bi bi-telephone"></i></div>
        <div>
          <div class="contact-label">Phone</div>
          <div class="contact-value"><?php echo $contactSettings['phone']; ?></div>
        </div>
      </div>
      <div class="contact-item">
        <div class="contact-icon"><i class="bi bi-clock"></i></div>
        <div>
          <div class="contact-label">Office Hours</div>
          <div class="contact-value"><?php echo $contactSettings['hours']; ?></div>
        </div>
      </div>
    </div>
  </div>

  <div class="reveal-right delay-2">
    <div style="font-size:10px;letter-spacing:0.15em;text-transform:uppercase;color:var(--text-muted);margin-bottom:24px;">Send A Message</div>
    <div class="contact-form">
      <div class="form-row">
        <div class="form-field">
          <label>Full Name</label>
          <input type="text" placeholder="Your full name">
        </div>
        <div class="form-field">
          <label>Student ID</label>
          <input type="text" placeholder="e.g. HLTU/22/0001">
        </div>
      </div>
      <div class="form-field">
        <label>Email Address</label>
        <input type="email" placeholder="yourname@hltu.edu.gh">
      </div>
      <div class="form-field">
        <label>Subject / Category</label>
        <select>
          <option value="">Select a category</option>
          <option>Academic Affairs</option>
          <option>Welfare & Support</option>
          <option>Elections</option>
          <option>Clubs & Societies</option>
          <option>Financial Matters</option>
          <option>General Enquiry</option>
        </select>
      </div>
      <div class="form-field">
        <label>Message</label>
        <textarea placeholder="Describe your concern or message in detail..."></textarea>
      </div>
      <button class="form-submit">Submit Message →</button>
    </div>
  </div>
</section>

<div class="cinematic-divider" aria-hidden="true"></div>

<?php include 'include/footer.php'; ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/animejs/2.0.2/anime.min.js" defer></script>

<script>
  // ── Kinetic Typography - Subtle Character Animation
  function initKineticTypography() {
    const kineticTexts = document.querySelectorAll('.kinetic-text');
    
    kineticTexts.forEach(element => {
      const text = element.getAttribute('data-text');
      element.textContent = ''; // Clear original text
      
      // Wrap each character in a span
      text.split('').forEach((char, index) => {
        const span = document.createElement('span');
        span.textContent = char;
        span.style.display = 'inline-block';
        element.appendChild(span);
      });
      
      // Add animation classes with delays
      const spans = element.querySelectorAll('span');
      spans.forEach((span, index) => {
        if (element.classList.contains('kinetic-in')) {
          // "Tomorrow's" - slide in from bottom
          span.style.animationDelay = `${index * 0.15}s`;
        } else if (element.classList.contains('kinetic-out')) {
          // "Shaping" - slide out to top
          span.style.animationDelay = `${index * 0.08}s`;
        } else if (element.classList.contains('kinetic-static')) {
          // "Leaders" - fade in
          span.style.animationDelay = `${index * 0.12}s`;
        }
      });
    });
    
    // Start animations when hero section is visible
    const heroSection = document.getElementById('hero');
    if (heroSection) {
      const heroObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            // Trigger animations
            document.querySelector('.kinetic-in')?.classList.add('active');
            setTimeout(() => {
              document.querySelector('.kinetic-out')?.classList.add('active');
              setTimeout(() => {
                document.querySelector('.kinetic-static')?.classList.add('active');
              }, 300);
            }, 800);
          }
        });
}, { threshold: 0.5 });
      
      heroObserver.observe(heroSection);
    }
  }
   
  // Initialize kinetic typography
  initKineticTypography();
  
  function initHeroAnimeText() {
    if (!window.anime) return;

    requestAnimationFrame(function() {
      // Wrap every letter in a span
      var textWrapper = document.querySelector('.ml1 .hero-word');
      if (!textWrapper) return;

      document.querySelectorAll('.ml1 .hero-word').forEach(function(word) {
        word.innerHTML = word.textContent.replace(/\S/g, "<span class='letter'>$&</span>");
      });

      anime.timeline({loop: true})
        .add({
          targets: '.ml1',
          opacity: 1,
          duration: 1
        })
        .add({
          targets: '.ml1 .hero-word .letter',
          scale: [0.3,1],
          opacity: [0,1],
          translateZ: 0,
          easing: "easeOutExpo",
          duration: 600,
          delay: (el, i) => 70 * (i+1)
        }).add({
          targets: '.ml1 .line',
          scaleX: [0,1],
          opacity: [0.5,1],
          easing: "easeOutExpo",
          duration: 700,
          offset: '-=875',
          delay: (el, i, l) => 80 * (l - i)
        }).add({
          targets: '.ml1',
          opacity: 0,
          duration: 1000,
          easing: "easeOutExpo",
          delay: 1000
        });
    });
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initHeroAnimeText);
  } else {
    initHeroAnimeText();
  }

  // Initialize council carousel
  function initCouncilCarousel() {
    const councilCards = document.querySelectorAll('.council-card');
    if (!councilCards.length) return;
    
    let currentCardIndex = 1;
    const CARD_W = 260;
    const CARD_GAP = 62;
    const WARD_WIDTH = 260;
    
    function positionCouncilCards() {
      const centerX = window.innerWidth / 2 - WARD_WIDTH / 2;
      const stepPx = WARD_WIDTH + CARD_GAP;
      const maxTail = councilCards.length - 1;
      const t = Math.ceil(maxTail / 2);
      const leftCnt = maxTail - t;
      
      councilCards.forEach((card, index) => {
        let left, opacity;
        
        if (index === currentCardIndex) {
          left = centerX;
          opacity = 1;
          card.classList.add('active');
          card.classList.remove('inactive');
        } else {
          const relIdx = (index - currentCardIndex + councilCards.length) % councilCards.length;
          const isRight = relIdx <= t;
          const slot = isRight ? relIdx - 1 : relIdx - t - 1;
          const count = isRight ? t : leftCnt;
          const side = isRight ? 1 : -1;
          
          left = centerX + side * (slot + 1) * stepPx;
          opacity = Math.max(0.22, 1 - slot * 0.09);
          card.classList.remove('active');
          card.classList.add('inactive');
        }
        
        card.style.left = left + 'px';
        card.style.opacity = opacity;
      });
    }
    
    // Auto-rotate cards every 10 seconds
    function rotateCards() {
      currentCardIndex = (currentCardIndex + 1) % councilCards.length;
      positionCouncilCards();
    }
    
positionCouncilCards();
    window.councilInterval = setInterval(rotateCards, 10000);
  }
  initCouncilCarousel();

// ── Gallery Tabs and Media Rotation
  function initGallery() {
    // Tab switching
    const tabs = document.querySelectorAll('.gallery-tab');
    const contents = document.querySelectorAll('.gallery-content');
    
    tabs.forEach(tab => {
      tab.addEventListener('click', () => {
        const targetTab = tab.getAttribute('data-tab');
        
        // Update active states
        tabs.forEach(t => t.classList.remove('active'));
        contents.forEach(c => c.classList.remove('active'));
        
        tab.classList.add('active');
        document.getElementById(`${targetTab}-gallery`).classList.add('active');
      });
    });
    
    // Image rotation for image gallery
    const imageItems = document.querySelectorAll('#images-gallery .gallery-item');
    if (!imageItems.length) {
      const galleryGrid = document.querySelector('#images-gallery .gallery-grid');
      if (galleryGrid) {
        galleryGrid.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:40px;color:var(--text-muted);">No gallery images available</div>';
      }
    } else {
      imageItems.forEach(item => {
        const images = item.querySelectorAll('.gallery-image');
        if (images.length > 0) {
          let currentImageIndex = 0;
          
          // Rotate images every 5 seconds
          const imageInterval = setInterval(() => {
            images[currentImageIndex].classList.remove('active');
            currentImageIndex = (currentImageIndex + 1) % images.length;
            images[currentImageIndex].classList.add('active');
          }, 5000);
          
          // Store interval for cleanup
          item.dataset.imageInterval = imageInterval;
        }
      });
    }
    
    // Video play/pause functionality
    const videoItems = document.querySelectorAll('.video-item');
    videoItems.forEach(item => {
      const video = item.querySelector('video');
      const playButton = item.querySelector('.play-button');
      
      if (video && playButton) {
        playButton.addEventListener('click', () => {
          if (video.paused) {
            video.play();
            playButton.style.display = 'none';
          } else {
            video.pause();
            playButton.style.display = 'flex';
          }
        });
        
        video.addEventListener('click', () => {
          if (video.paused) {
            video.play();
            playButton.style.display = 'none';
          } else {
            video.pause();
            playButton.style.display = 'flex';
          }
        });
        
        video.addEventListener('ended', () => {
          playButton.style.display = 'flex';
        });
      }
    });
  }
  initGallery();

  // ── Gallery Lightbox (image & video full-preview)
  (() => {
      const overlay  = document.getElementById('galleryLightbox');
      if (!overlay) return;
      const shutter  = document.getElementById('lightboxShutter');
      const closeBtn = document.getElementById('lightboxClose');
      const prevBtn  = document.getElementById('lightboxPrev');
      const nextBtn  = document.getElementById('lightboxNext');
      const content  = document.getElementById('lightboxContent');
      const caption  = document.getElementById('lightboxCaption');
      const counter  = document.getElementById('lightboxCounter');

      // Collect all gallery items across both tabs in DOM order
      function allItems() {
        return Array.from(document.querySelectorAll('#images-gallery .gallery-item, #videos-gallery .gallery-item, .gallery-item'));
      }

      let currentIndex = 0;

      function open(index) {
        currentIndex = index;
        render();
        // Single-RAF shutter: capture next-paint before fading shutter out
        shutter.style.transition = 'none';
        shutter.style.opacity = '1';
        requestAnimationFrame(() => {
          shutter.style.transition = 'opacity 0.35s ease';
          shutter.style.opacity = '0';
        });
        overlay.classList.add('open');
        document.body.style.overflow = 'hidden';
      }

      function close() {
        shutter.style.transition = 'opacity 0.22s ease';
        shutter.style.opacity = '1';
        setTimeout(() => {
          overlay.classList.remove('open');
          document.body.style.overflow = '';
          if (content) content.innerHTML = '';
        }, 240);
      }

      function go(dir) {
        const items = allItems().filter(Boolean);
        if (!items.length) return;
        currentIndex = (currentIndex + dir + items.length) % items.length;
        render();
      }

        function render() {
          const items = allItems().filter(Boolean);
          const item  = items[currentIndex];
          if (!item) return;

          // ── Image item ──────────────────────────────────────────────────────
          if (!item.classList.contains('video-item')) {
            const rawSrc  = item.dataset.src || item.getAttribute('src');
            const imgSrc  = rawSrc
                         || item.querySelector('.gallery-image.active')?.src
                         || item.querySelector('.gallery-image')?.src
                         || item.querySelector('img')?.src;
            const altText = item.dataset.alt
                         || item.querySelector('img')?.alt
                         || item.querySelector('.gallery-overlay-title')?.textContent
                         || '';
            if (imgSrc) {
              const el = document.createElement('img');
              el.src       = imgSrc;
              el.alt       = altText;
              el.draggable = false;
              el.style.cssText = 'max-width:max(80vw,400px);max-height:80vh;object-fit:contain;display:block;';
              content.innerHTML = '';
              content.appendChild(el);
            }
            caption.textContent = altText;
            return;
          }

          // ── Video item ─────────────────────────────────────────────────────
          // Gallery markup is <video muted loop><source src="file.mp4"></video>
          // — .src on <video> is "" because the URL lives on the <source> child
          const srcEl   = item.querySelector('video source');          // <source> child
          const videoEl = item.querySelector('video');                 // <video> element
          const vidSrc  = srcEl
                        ? (srcEl.getAttribute('src') || srcEl.src || '')
                        : (videoEl ? videoEl.getAttribute('src') || videoEl.src : '');
          const altText = item.querySelector('.gallery-overlay-title')?.textContent || '';

          // Build the lightbox video element
          content.innerHTML = '';
          const lvid = document.createElement('video');
          lvid.src         = vidSrc;
          lvid.controls    = true;
          lvid.playsInline = true;
          lvid.setAttribute('playsinline', '');
          // Start muted so Chromium autoplay policy never blocks the first frame
          lvid.muted      = true;
          lvid.style.cssText = 'max-width:80vw;max-height:75vh;display:block;border-radius:4px;box-shadow:0 20px 60px rgba(0,0,0,.6);';
          content.appendChild(lvid);
          caption.textContent = altText;

          // Play synchronously inside the user-click event so the browser
          // treats it as a user gesture; if it still rejects, controls stay visible
          lvid.currentTime = 0;
          lvid.play().catch(err => {
            console.warn('Lightbox video autoplay blocked:', err.message || err);
          });

          // Unmute after first successful play attempt so audio comes through
          function tryUnmute() {
            if (lvid.muted) { lvid.muted = false; console.log('Lightbox video unmuted'); }
          }
          lvid.addEventListener('playing', tryUnmute, { once: true });
          // Fallback timer in case 'playing' never fires (silent error)
          setTimeout(tryUnmute, 800);

          // If the browser reports a codec/MIME error, show it in the caption
          lvid.addEventListener('error', () => {
            const err = lvid.error;
            const msgs = { 1:'Aborted', 2:'Network Error', 3:'Decode Error', 4:'Source Unsupported' };
            caption.textContent = 'Video error: ' + (msgs[err?.code] || err?.message || 'Unknown');
          });
        }

      // Wire click → lightbox on every gallery item (image AND video)
      const items = allItems();
      items.forEach((item, i) => { item.addEventListener('click', () => open(i)); });

      // Prevent clicks on media inside the overlay from closing it
      content?.addEventListener('click', e => e.stopPropagation());

      closeBtn.addEventListener('click', close);
      prevBtn.addEventListener('click', (e) => { e.stopPropagation(); go(-1); });
      nextBtn.addEventListener('click', (e) => { e.stopPropagation(); go(1); });
      overlay.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') close();
        if (e.key === 'ArrowLeft')  go(-1);
        if (e.key === 'ArrowRight') go(1);
      });
      overlay.addEventListener('click', (e) => {
        if (e.target === overlay || e.target === shutter) close();
      });
      document.addEventListener('keydown', (e) => {
        if (!overlay.classList.contains('open')) return;
        if (e.key === 'Escape') close();
        if (e.key === 'ArrowLeft')  go(-1);
        if (e.key === 'ArrowRight') go(1);
      });

      overlay.addEventListener('click', (e) => {
        if (e.target === content || e.target === caption || e.target === counter) return;
        close();
      });
    })();

   // ── Custom Cursor
  const cursor = document.getElementById('cursor');
  const ring = document.getElementById('cursorRing');
  let mx = 0, my = 0, rx = 0, ry = 0;
  document.addEventListener('mousemove', e => { mx = e.clientX; my = e.clientY; cursor.style.left = mx+'px'; cursor.style.top = my+'px'; });
  function animRing() { rx += (mx-rx)*0.12; ry += (my-ry)*0.12; ring.style.left = rx+'px'; ring.style.top = ry+'px'; requestAnimationFrame(animRing); }
  animRing();

  // ── Scroll Progress & Header Hide/Show
  const bar = document.getElementById('scrollBar');
  const header = document.querySelector('header');
  let lastScrollY = window.scrollY;
  
  window.addEventListener('scroll', () => {
    const h = document.body.scrollHeight - window.innerHeight;
    bar.style.width = (window.scrollY/h*100)+'%';
    document.getElementById('backTop').classList.toggle('visible', window.scrollY > 400);
    
    // Hide header on scroll down, show on scroll up
    const mobileNav = document.getElementById('navList');
    const menuOpen = mobileNav?.classList.contains('active');
    if (!menuOpen && window.scrollY > lastScrollY && window.scrollY > 100) {
      header.classList.add('hidden');
    } else {
      header.classList.remove('hidden');
    }
    lastScrollY = window.scrollY;
    
    // animate vote bars
    animateVoteBars();
  });

  // ── Scroll Reveal
  const revealEls = document.querySelectorAll('.reveal, .reveal-left, .reveal-right');
  const io = new IntersectionObserver((entries) => {
    entries.forEach(e => { if(e.isIntersecting) e.target.classList.add('visible'); });
  }, { threshold: 0.1 });
  revealEls.forEach(el => io.observe(el));

  // ── Vote bars animation
  let voteBarsAnimated = false;
  function animateVoteBars() {
    if (voteBarsAnimated) return;
    const section = document.getElementById('elections');
    if (!section) return;
    const rect = section.getBoundingClientRect();
    if (rect.top < window.innerHeight * 0.8) {
      document.querySelectorAll('.vote-bar-fill').forEach(el => {
        el.style.width = el.getAttribute('data-width');
      });
      voteBarsAnimated = true;
    }
  }

  // ── Live date in top bar
  const dateEl = document.querySelector('.top-bar-date');
  if (dateEl) {
    const now = new Date();
    dateEl.textContent = now.toLocaleDateString('en-GB', { day:'2-digit', month:'short', year:'numeric' });
  }

   // ── Smooth scroll for nav links
   document.querySelectorAll('a[href^="#"]').forEach(a => {
     a.addEventListener('click', e => {
       const id = a.getAttribute('href');
       if (!id || id === '#' || id === '#!') return;
       const target = document.querySelector(id);
       if (target) { e.preventDefault(); target.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
     });
   });

// ── Email Subscription Form Handler
   document.getElementById('subscribe-form')?.addEventListener('submit', async function(e) {
     e.preventDefault();
     const email = document.getElementById('subscribe-email').value;
     const name = document.getElementById('subscribe-name').value;
     const resultDiv = document.getElementById('subscribe-result');
     
     try {
       const response = await fetch('api/email-subscribe.php', {
         method: 'POST',
         headers: { 'Content-Type': 'application/json' },
         body: JSON.stringify({ action: 'subscribe', email, full_name: name })
       });
       const data = await response.json();
       resultDiv.textContent = data.message;
       resultDiv.style.color = data.success ? '#22c55e' : '#ef4444';
       if (data.success) {
         document.getElementById('subscribe-form').reset();
       }
     } catch (err) {
       resultDiv.textContent = 'Subscription failed. Please try again.';
       resultDiv.style.color = '#ef4444';
     }
   });

    // ── Hero Carousel Functionality
    const heroCarouselTrack = document.querySelector('.carousel-track');
    const heroCarouselSlides = document.querySelectorAll('.carousel-slide');
    const heroCarouselPrevBtn = document.querySelector('.carousel-prev');
    const heroCarouselNextBtn = document.querySelector('.carousel-next');
    const heroCarouselIndicators = document.querySelectorAll('.carousel-indicator');
    
    if (heroCarouselTrack && heroCarouselSlides.length > 0) {
        let currentIndex = 0;
        
        // Update carousel position
        function updateCarouselPosition() {
            heroCarouselTrack.style.transform = `translateX(-${currentIndex * 100}%)`;
            
            // Update active slide
            heroCarouselSlides.forEach((slide, index) => {
                slide.classList.toggle('active', index === currentIndex);
            });
            
            // Update indicators
            heroCarouselIndicators.forEach((indicator, index) => {
                indicator.classList.toggle('active', index === currentIndex);
            });
        }
        
        // Next slide
        function nextSlide() {
            currentIndex = (currentIndex + 1) % heroCarouselSlides.length;
            updateCarouselPosition();
        }
        
        // Previous slide
        function prevSlide() {
            currentIndex = (currentIndex - 1 + heroCarouselSlides.length) % heroCarouselSlides.length;
            updateCarouselPosition();
        }
        
        // Event listeners for navigation buttons
        if (heroCarouselNextBtn) {
            heroCarouselNextBtn.addEventListener('click', () => {
                nextSlide();
                resetAutoSlide();
            });
        }
        
        if (heroCarouselPrevBtn) {
            heroCarouselPrevBtn.addEventListener('click', () => {
                prevSlide();
                resetAutoSlide();
            });
        }
        
        // Event listeners for indicators
        heroCarouselIndicators.forEach((indicator, index) => {
            indicator.addEventListener('click', () => {
                currentIndex = index;
                updateCarouselPosition();
                resetAutoSlide();
            });
        });
        
        // Auto slide functionality
        let autoSlideInterval;
        
        function startAutoSlide() {
            autoSlideInterval = setInterval(nextSlide, 5000); // Change slide every 5 seconds
        }
        
        function resetAutoSlide() {
            clearInterval(autoSlideInterval);
            startAutoSlide();
        }
        
        // Start auto slide
        startAutoSlide();
        
// Pause on hover
        const heroCarousel = document.querySelector('.hero-carousel');
        if (heroCarousel) {
            heroCarousel.addEventListener('mouseenter', () => {
                clearInterval(autoSlideInterval);
            });
            
            heroCarousel.addEventListener('mouseleave', () => {
                startAutoSlide();
            });
        }
    }

    // ── Mobile Header Dropdown
    
</script>

</body>
</html>