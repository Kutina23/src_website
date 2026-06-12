<?php
require_once 'config/database.php';
require_once 'config/functions.php';
require_once 'models/Halls.php';

$db = Database::getInstance();
$hallsModel = new Halls($db);

$halls = $hallsModel->getAll();
$searchResult = null;
$searchQuery = $_GET['query'] ?? '';
$searchSuggestions = [];

if (!empty($searchQuery)) {
    $searchResult = $hallsModel->searchMember($searchQuery);
}

// Get search suggestions for autocomplete (limited to 10)
if (isset($_GET['suggest'])) {
    $suggestQuery = trim($_GET['suggest']);
    if (strlen($suggestQuery) >= 2) {
        $sql = "SELECT u.first_name, u.last_name, u.student_id, h.name as hall_name
                FROM hall_members hm
                JOIN users u ON hm.user_id = u.id
                JOIN halls h ON hm.hall_id = h.id
                WHERE u.student_id LIKE ? OR CONCAT(u.first_name, ' ', u.last_name) LIKE ?
                LIMIT 10";
        $searchSuggestions = $db->fetchAll($sql, ["%{$suggestQuery}%", "%{$suggestQuery}%"]) ?: [];
    }
    header('Content-Type: application/json');
    echo json_encode($searchSuggestions);
    exit;
}

$hallColors = [
    'CUSTODIAN HALL' => ['primary' => '#8B6F47', 'secondary' => '#A0826D', 'icon' => 'fa-key'],
    'INTEGRITY HALL' => ['primary' => '#6366F1', 'secondary' => '#818CF8', 'icon' => 'fa-handshake'],
    'LOYALTY HALL' => ['primary' => '#EF4444', 'secondary' => '#F87171', 'icon' => 'fa-shield-alt'],
    'PACESETTERS HALL' => ['primary' => '#F97316', 'secondary' => '#FB923C', 'icon' => 'fa-bolt'],
    'ROYALS HALL' => ['primary' => '#A855F7', 'secondary' => '#C084FC', 'icon' => 'fa-crown']
];

// Helper functions
function hexToRgb($hex, $opacity = 1) {
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    }
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    return "rgba($r, $g, $b, $opacity)";
}

function getHallTagline($hallName) {
    $taglines = [
        'CUSTODIAN HALL' => '"Guardians of Excellence"',
        'INTEGRITY HALL' => '"Integrity is Our Foundation"',
        'LOYALTY HALL' => '"Faithful to the End"',
        'PACESETTERS HALL' => '"Leading the Way"',
        'ROYALS HALL' => '"Royal Heritage, Bright Future"'
    ];
    return $taglines[$hallName] ?? '"Our Hall"';
}

function getHallMotto($hallName) {
    $motto = [
        'CUSTODIAN HALL' => '"Guardians of Excellence"',
        'INTEGRITY HALL' => '"Integrity is Our Foundation"',
        'LOYALTY HALL' => '"Loyalty above all"',
        'PACESETTERS HALL' => '"Leading the Way"',
        'ROYALS HALL' => '"Royal Heritage, Bright Future"'
    ];
    return $motto[$hallName] ?? '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>The Halls | DHLTU SRC</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/main.css">
  <link rel="icon" type="image/png" href="assets/images/logo.png">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
  <?php include 'include/header.php'; ?>

  <main>
    <!-- HERO SECTION -->
    <section id="halls-hero" class="section">
      <div class="hero-bg-orbs">
        <div class="hero-orb orb-1"></div>
        <div class="hero-orb orb-2"></div>
        <div class="hero-orb orb-3"></div>
      </div>
      <div class="halls-hero-content">
        <div class="halls-eyebrow">Discover Your Identity</div>
        <h1 class="halls-title">
          <span class="halls-title-accent">SEARCH THE HALL</span>
          <span class="halls-title-main">YOU BELONG</span>
        </h1>
        <p class="halls-subtitle">Find your hall affiliation by entering your index number or name</p>
        
        <!-- Search Form -->
        <div class="hall-search-container">
          <form method="GET" action="halls.php" class="hall-search-form">
            <input type="text" name="query" class="hall-search-input" placeholder="Enter Index Number (e.g. HLTU/22/001) or Full Name" value="<?php echo htmlspecialchars($searchQuery); ?>" required>
            <button type="submit" class="hall-search-btn"><i class="fas fa-search"></i></button>
          </form>
        </div>
      </div>
    </section>

    <div class="cinematic-divider"></div>

    <?php if ($searchResult): ?>
    <!-- Search Result Section -->
    <section id="hall-result" class="section">
      <div class="section-header">
        <div class="result-eyebrow">Search Result</div>
        <h2 class="section-title">Your Hall Assignment</h2>
      </div>
      <div class="member-result-card">
<div class="member-avatar-large">
           <?php echo strtoupper(substr($searchResult['first_name'], 0, 1) . substr($searchResult['last_name'], 0, 1)); ?>
         </div>
        <div class="member-details">
          <h3 class="member-name"><?php echo htmlspecialchars($searchResult['first_name'] . ' ' . $searchResult['last_name']); ?></h3>
          <p class="member-index"><?php echo htmlspecialchars($searchResult['student_id']); ?></p>
          <div class="member-hall-badge" style="background: <?php echo $hallColors[$searchResult['hall_name']]['primary'] ?? 'var(--gold)'; ?>;">
            <?php echo strtoupper(htmlspecialchars($searchResult['hall_name'])); ?>
          </div>
          <p class="member-president">President: <?php echo htmlspecialchars($searchResult['president_name'] ?? 'To be assigned'); ?></p>
        </div>
      </div>
    </section>
    <div class="cinematic-divider"></div>
    <?php endif; ?>

    <!-- HALLS SECTION - Compact Cards -->
    <section id="halls-grid" class="section">
      <div class="section-header">
        <div class="halls-section-eyebrow">THE FOUR HALLS</div>
        <h2 class="section-title">Choose Your Path</h2>
        <p class="section-body">Each hall embodies a core virtue. Search above to find your assigned hall.</p>
      </div>
      <div class="halls-container compact">
        <?php foreach ($halls as $index => $hall): 
          $hallName = strtoupper($hall['name']);
          $color = $hallColors[$hallName] ?? ['primary' => 'var(--gold)', 'secondary' => 'var(--gold-light)', 'icon' => 'fa-star'];
          $delay = ($index % 4) + 1;
        ?>
        <div class="hall-card reveal delay-<?php echo $delay; ?> compact">
          <div class="hall-accent-bar" style="background: linear-gradient(90deg, <?php echo $color['primary']; ?>, <?php echo $color['secondary']; ?>);"></div>
          <div class="hall-icon" style="color: <?php echo $color['primary']; ?>; background: rgba(<?php echo hexToRgb($color['primary'], 0.15); ?>);">
            <i class="fas <?php echo $color['icon']; ?>"></i>
          </div>
          <h3 class="hall-name"><?php echo htmlspecialchars($hallName); ?></h3>
          <p class="hall-tagline"><?php echo htmlspecialchars(getHallTagline($hallName)); ?></p>
        </div>
        <?php endforeach; ?>
      </div>
    </section>

    <div class="cinematic-divider"></div>

    <!-- HALLS LEADERS SECTION -->
    <section id="halls-leaders" class="section">
      <div class="section-header">
        <div class="leaders-eyebrow">Hall Leadership</div>
        <h2 class="section-title">Meet Your Wardens</h2>
        <p class="section-body">The guardians who guide each hall with wisdom and dedication.</p>
      </div>
      <div class="leaders-container">
        <?php foreach ($halls as $index => $hall): 
          $hallName = strtoupper($hall['name']);
          $color = $hallColors[$hallName] ?? ['primary' => 'var(--gold)'];
          $initials = !empty($hall['president_name']) ? strtoupper(substr($hall['president_name'], 0, 1)) : $hallName[0];
          $delayClass = match($index % 3) {
            0 => 'reveal-left',
            1 => 'reveal',
            2 => 'reveal-right'
          };
        ?>
        <div class="leader-card <?php echo $delayClass; ?>">
          <div class="leader-avatar-wrapper">
            <?php if (!empty($hall['president_image'])): ?>
              <img src="<?php echo htmlspecialchars($hall['president_image']); ?>" alt="<?php echo htmlspecialchars($hall['president_name']); ?>" class="leader-image">
            <?php else: ?>
              <div class="leader-initials"><?php echo $initials; ?></div>
            <?php endif; ?>
          </div>
          <div class="leader-info">
            <h4 class="leader-name"><?php echo htmlspecialchars($hall['president_name'] ?? 'Position Vacant'); ?></h4>
            <p class="leader-role"><?php echo htmlspecialchars($hallName); ?> President</p>
            <p class="leader-motto"><?php echo htmlspecialchars(getHallMotto($hallName)); ?></p>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </section>

  </main>

  <?php include 'include/footer.php'; ?>

  <script>
    const observerOptions = {
      threshold: 0.1,
      rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('visible');
        }
      });
    }, observerOptions);

    document.querySelectorAll('.reveal, .reveal-left, .reveal-right')
      .forEach(el => observer.observe(el));

    let lastScrollY = window.scrollY;
    window.addEventListener('scroll', () => {
      if (window.scrollY > lastScrollY && window.scrollY > 100) {
        document.querySelector('header')?.classList.add('hidden');
      } else {
        document.querySelector('header')?.classList.remove('hidden');
      }
      lastScrollY = window.scrollY;
    });

// Smart Search - Autocomplete
    const searchInput = document.querySelector('.hall-search-input');
    const searchContainer = document.querySelector('.hall-search-container');
    const suggestionsContainer = document.createElement('div');
    suggestionsContainer.className = 'search-suggestions';
    document.body.appendChild(suggestionsContainer);

    function positionSuggestions() {
      const rect = searchInput.getBoundingClientRect();
      suggestionsContainer.style.top = rect.bottom + 'px';
      suggestionsContainer.style.left = rect.left + 'px';
      suggestionsContainer.style.width = rect.width + 'px';
    }

    searchInput.addEventListener('focus', positionSuggestions);

    let searchTimeout;
    searchInput.addEventListener('input', function() {
      clearTimeout(searchTimeout);
      const query = this.value.trim();
      
      if (query.length < 2) {
        suggestionsContainer.innerHTML = '';
        return;
      }

      searchTimeout = setTimeout(() => {
        fetch('halls.php?suggest=' + encodeURIComponent(query))
          .then(response => response.json())
          .then(data => {
            positionSuggestions();
            if (data.length > 0) {
              suggestionsContainer.innerHTML = data.map(item => `
                <div class="suggestion-item" data-student-id="${item.student_id}" data-name="${item.first_name} ${item.last_name}" data-hall="${item.hall_name}">
                  <div class="suggestion-content">
                    <span class="suggestion-name">${item.first_name} ${item.last_name}</span>
                    <span class="suggestion-index">${item.student_id}</span>
                    <span class="suggestion-hall">${item.hall_name}</span>
                  </div>
                </div>
              `).join('');
              
              document.querySelectorAll('.suggestion-item').forEach(item => {
                item.addEventListener('click', () => {
                  const studentId = item.dataset.studentId;
                  window.location.href = 'halls.php?query=' + encodeURIComponent(studentId);
                });
              });
            } else {
              suggestionsContainer.innerHTML = `
                <div class="suggestion-item no-results">
                  <div class="suggestion-content">
                    <span class="suggestion-name">No results found</span>
                    <span class="suggestion-index" style="color: var(--text-muted);">"${query}" not in any hall</span>
                  </div>
                </div>
              `;
            }
          })
          .catch(() => {
            positionSuggestions();
            suggestionsContainer.innerHTML = `
              <div class="suggestion-item no-results">
                <div class="suggestion-content">
                  <span class="suggestion-name">Search error</span>
                  <span class="suggestion-index" style="color: var(--text-muted);">Please try again</span>
                </div>
              </div>
            `;
          });
      }, 300);
    });

    document.addEventListener('click', (e) => {
      if (!searchInput.contains(e.target) && !suggestionsContainer.contains(e.target)) {
        suggestionsContainer.innerHTML = '';
      }
    });
    window.addEventListener('scroll', positionSuggestions);
    window.addEventListener('resize', positionSuggestions);

    // ── Mobile Menu Toggle
    const mobileToggle = document.querySelector('.mobile-toggle');
    const navList = document.querySelector('.nav-list');
    const heroSection = document.getElementById('halls-hero');

    // Show toggle on mobile screens (≤900px)
    if (mobileToggle && window.innerWidth <= 900) {
      mobileToggle.style.display = 'flex';
    }

    // Toggle mobile menu
    if (mobileToggle && navList) {
      mobileToggle.addEventListener('click', () => {
        const isOpen = navList.classList.toggle('active');
        mobileToggle.classList.toggle('active', isOpen);
        mobileToggle.setAttribute('aria-expanded', String(isOpen));
      });
    }

    document.querySelectorAll('.nav-link[data-dropdown]').forEach((link) => {
      link.addEventListener('click', (e) => {
        e.preventDefault();

        const navItem = link.closest('.nav-item');
        const dropdown = navItem?.querySelector('.dropdown');
        if (!navItem || !dropdown) return;

        const willOpen = !navItem.classList.contains('open');

        document.querySelectorAll('.nav-item.open').forEach((item) => {
          item.classList.remove('open');
          item.querySelector('.nav-link[data-dropdown]')?.setAttribute('aria-expanded', 'false');
          item.querySelector('.dropdown')?.classList.remove('open');
        });

        navItem.classList.toggle('open', willOpen);
        dropdown.classList.toggle('open', willOpen);
        link.setAttribute('aria-expanded', String(willOpen));
      });
    });

    document.querySelectorAll('.dropdown-item').forEach((item) => {
      item.addEventListener('click', () => {
        document.querySelectorAll('.nav-item.open').forEach((navItem) => {
          navItem.classList.remove('open');
          navItem.querySelector('.nav-link[data-dropdown]')?.setAttribute('aria-expanded', 'false');
          navItem.querySelector('.dropdown')?.classList.remove('open');
        });
        navList?.classList.remove('active');
        mobileToggle?.classList.remove('active');
        mobileToggle?.setAttribute('aria-expanded', 'false');
      });
    });

    // Close mobile menu when clicking outside
    document.addEventListener('click', (e) => {
      if (mobileToggle && navList && !mobileToggle.contains(e.target) && !navList.contains(e.target)) {
        mobileToggle.classList.remove('active');
        navList.classList.remove('active');
        mobileToggle.setAttribute('aria-expanded', 'false');
      }

      if (!e.target.closest('.nav-item')) {
        document.querySelectorAll('.nav-item.open').forEach((navItem) => {
          navItem.classList.remove('open');
          navItem.querySelector('.nav-link[data-dropdown]')?.setAttribute('aria-expanded', 'false');
          navItem.querySelector('.dropdown')?.classList.remove('open');
        });
      }
    });

    // Auto-close menu/drawer when scrolling past hero
    if (heroSection && mobileToggle && navList) {
      const heroObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (!entry.isIntersecting) {
            navList.classList.remove('active');
            mobileToggle.classList.remove('active');
          }
        });
      }, { threshold: 0.05 });
      heroObserver.observe(heroSection);
    }
   </script>
</body>
</html>