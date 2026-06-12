<?php
require_once "config/database.php";
require_once "models/Projects.php";
require_once "config/functions.php";

$model = new Projects(db());
$projects = $model->getAllActive();
$projectCount = $model->getCount();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Showcase of SRC initiatives and student-led projects at Dr. Hilla Limann Technical University. View completed, ongoing and upcoming projects.">
  <meta name="author" content="DHLTU SRC">
  <title>Projects — DHLTU Student Representative Council</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;0,700;1,300;1,400&family=Outfit:wght@200;300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
  <link rel="stylesheet" href="assets/css/main.css">
  <link rel="icon" type="image/png" href="assets/images/logo.png">
</head>
<body>

<?php include 'include/header.php'; ?>

<!-- ============================================================
     PROJECTS PAGE HERO
     ============================================================ -->
<section class="projects-page-hero" id="projects">
  <div class="projects-hero-bg"></div>
  <div class="projects-hero-inner">
    <div class="projects-hero-eyebrow">
      <i class="bi bi-folder2-open"></i> Initiatives &amp; Projects
    </div>
    <h1 class="projects-hero-title">SRC<br><span>Projects</span></h1>
    <p class="projects-hero-lead">
      A showcase of initiatives and student-led projects led by the SRC Executive Council.
      Filter by status, explore what has been delivered, and discover what is coming next.
    </p>
    <div class="projects-hero-stats">
      <div class="projects-stat">
        <span class="projects-stat-num" id="projTotal"><?php echo $projectCount; ?></span>
        <span class="projects-stat-lbl">Active<br>Projects</span>
      </div>
    </div>
  </div>
</section>

<!-- ============================================================
     PROJECTS LIST SECTION
     ============================================================ -->
<section class="proj-list-section">
  <div class="container">
    <!-- Heading -->
    <div class="section-header">
      <div class="projects-eyebrow" style="justify-content:center;">
        <i class="bi bi-grid-3x3-gap-fill"></i> All Projects
      </div>
      <h2 class="projects-section-title">Student-Led Initiatives</h2>
    </div>

    <!-- ── Project cards grid ── -->
    <div class="proj-grid" id="projGrid">

      <?php if (empty($projects)): ?>

        <div class="proj-empty" style="grid-column: 1 / -1; text-align: center; padding: 80px 40px;">
          <i class="bi bi-inbox" style="font-size: 48px; color: var(--gold); margin-bottom: 20px; display: block;"></i>
          <h3 style="margin-bottom: 12px;">No Projects Yet</h3>
          <p style="max-width: 480px; margin: 0 auto;">Projects will appear here once they have been added by the SRC administration. Please check back soon.</p>
        </div>

      <?php else: ?>

        <?php foreach ($projects as $p): ?>
        <?php
            $status        = htmlspecialchars($p['status'] ?? 'upcoming', ENT_QUOTES, 'UTF-8');
            $title         = htmlspecialchars($p['title'] ?? 'Untitled Project', ENT_QUOTES, 'UTF-8');
            $description   = htmlspecialchars($p['description'] ?? '', ENT_QUOTES, 'UTF-8');
            $category      = htmlspecialchars($p['category'] ?? 'Project', ENT_QUOTES, 'UTF-8');
            $image         = !empty($p['image_path'])
                              ? htmlspecialchars($p['image_path'], ENT_QUOTES, 'UTF-8')
                              : 'https://picsum.photos/seed/prj' . (int)$p['id'] . '/800/600';
            $badgeCls      = match ($status) {
                'upcoming'  => 'proj-upcoming',
                'ongoing'   => 'proj-ongoing',
                'completed' => 'proj-completed',
                default     => '',
            };
            $badgeLabel    = ucfirst($status);
        ?>
        <article
          class="proj-card"
          tabindex="0"
          data-id="<?php echo (int)$p['id']; ?>"
          data-title="<?php echo $title; ?>"
          data-description="<?php echo $description; ?>"
          data-image="<?php echo $image; ?>"
          data-status="<?php echo $status; ?>"
          data-category="<?php echo $category; ?>"
        >
          <div class="proj-img-wrap">
            <img src="<?php echo $image; ?>" alt="<?php echo $title; ?>" class="proj-img" loading="lazy">
            <span class="proj-badge <?php echo $badgeCls; ?>"><?php echo $badgeLabel; ?></span>
          </div>
          <div class="proj-card-body">
            <span class="proj-category">
              <i class="bi bi-tag-fill"></i> <?php echo $category; ?>
            </span>
            <h3 class="proj-title"><?php echo $title; ?></h3>
            <p class="proj-desc"><?php echo $description; ?></p>
            <div class="proj-actions">
              <button class="proj-btn" type="button">
                <i class="bi bi-eye"></i> View Details
              </button>
            </div>
          </div>
        </article>
        <?php endforeach; ?>

      <?php endif; ?>

    </div>
  </div>
</section>

<!-- ============================================================
     PROJECT DETAIL MODAL
     ============================================================ -->
<div class="proj-modal-overlay" id="projModal">
  <div class="proj-modal" role="dialog" aria-modal="true" aria-labelledby="projModalTitle">
    <button class="proj-modal-close" aria-label="Close" id="projModalClose">
      <i class="bi bi-x-lg"></i>
    </button>
    <div class="proj-modal-body" id="projModalBody">
      <!-- Content injected by JS -->
    </div>
  </div>
</div>

<?php include 'include/footer.php'; ?>

<style>
  /* ── CSS Custom Properties (align with :root in main.css) ── */
  :root {
    --gold:         #C9A84C;
    --gold-light:   #E8C97A;
    --gold-dark:    #8B6914;
    --navy:         #0A1628;
    --navy-mid:     #0F2040;
    --navy-light:   #1A3060;
    --cream:        #F5F0E8;
    --text-muted:   #8A9BB8;
    --transition-fast: 0.3s ease;
  }

  /* ── Page body offset — account for the fixed top-bar + main-nav ── */
  main { padding-top: 0; }

  /* ══════════════════════════════════════
     PROJECTS PAGE HERO
  ══════════════════════════════════════ */
  .projects-page-hero {
    position: relative;
    min-height: 480px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, var(--navy-mid) 0%, var(--navy) 100%);
    padding: 160px 40px 100px;
    overflow: hidden;
    text-align: center;
  }
  .projects-page-hero::before {
    content: '';
    position: absolute; inset: 0;
    background: radial-gradient(ellipse at 50% 30%, rgba(201,168,76,0.10) 0%, transparent 70%);
  }
  .projects-page-hero::after {
    content: '✦';
    position: absolute;
    bottom: -10px; left: 50%;
    transform: translateX(-50%);
    font-size: 12px; color: var(--gold);
    background: var(--navy); padding: 0 14px;
  }
  .projects-hero-inner { position: relative; z-index: 1; max-width: 720px; }
  .projects-hero-eyebrow {
    font-family: 'Space Mono', monospace;
    font-size: 10px; letter-spacing: 0.25em; text-transform: uppercase;
    color: var(--gold);
    display: inline-flex; align-items: center; gap: 10px;
    margin-bottom: 28px;
  }
  .projects-hero-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: clamp(48px, 6vw, 84px);
    font-weight: 300; line-height: 0.92; color: var(--cream);
    margin-bottom: 28px;
  }
  .projects-hero-title span {
    font-weight: 700; font-style: italic; color: var(--gold-light);
    display: block;
  }
  .projects-hero-lead {
    font-size: 15px; font-weight: 300; line-height: 1.9;
    color: var(--text-muted);
    max-width: 560px;
    margin: 0 auto 48px;
  }
  .projects-hero-stats {
    display: flex; gap: 40px;
    padding-top: 32px;
    border-top: 1px solid rgba(201,168,76,0.12);
    margin: 0 auto;
    justify-content: center;
  }
  .projects-stat { text-align: center; }
  .projects-stat-num {
    font-family: 'Cormorant Garamond', serif;
    font-size: 42px; font-weight: 700; color: var(--gold-light); line-height: 1;
  }
  .projects-stat-lbl {
    font-family: 'Space Mono', monospace;
    font-size: 9px; letter-spacing: 0.18em; text-transform: uppercase;
    color: var(--text-muted); margin-top: 8px; line-height: 1.6;
  }

  /* ── section eyebrow used by the list header ── */
  .projects-eyebrow {
    font-family: 'Space Mono', monospace;
    font-size: 10px; letter-spacing: 0.25em; text-transform: uppercase;
    color: var(--gold);
    display: inline-flex; align-items: center; gap: 12px;
    margin-bottom: 16px;
  }
  .projects-eyebrow::before { content: '02'; color: rgba(201,168,76,0.3); font-size: 9px; }

  /* ══════════════════════════════════════
     PROJECTS LIST SECTION
  ══════════════════════════════════════ */
  .proj-list-section { padding: 80px 40px 120px; background: var(--navy); }

  /* Anchor offset for the page section id */
  #projects { scroll-margin-top: 55px; }

  /* ── Section title ── */
  .projects-section-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: clamp(32px, 4vw, 52px);
    font-weight: 300; color: var(--cream);
    margin: 0;
  }

  /* ── Project grid ── */
  .proj-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 28px;
    margin-top: 60px;
  }

  /* ── Individual card ── */
  .proj-card {
    background: var(--navy-mid);
    border: 1px solid rgba(201,168,76,0.12);
    border-radius: 2px;
    overflow: hidden;
    display: flex; flex-direction: column;
    transition: all 0.4s cubic-bezier(0.16,1,0.3,1);
    cursor: default;
  }
  .proj-card:hover {
    border-color: rgba(201,168,76,0.30);
    transform: translateY(-6px);
    box-shadow: 0 20px 60px rgba(0,0,0,0.35);
  }

  /* ── Image area ── */
  .proj-img-wrap {
    position: relative;
    height: 210px;
    overflow: hidden;
  }
  .proj-img {
    width: 100%; height: 100%;
    object-fit: cover;
    display: block;
    transition: transform 0.6s cubic-bezier(0.16,1,0.3,1);
  }
  .proj-card:hover .proj-img { transform: scale(1.06); }

  /* ── Status badge on image ── */
  .proj-badge {
    position: absolute; top: 16px; left: 16px;
    font-family: 'Space Mono', monospace;
    font-size: 9px; font-weight: 700;
    letter-spacing: 0.12em; text-transform: uppercase;
    padding: 5px 12px;
    border-radius: 2px;
  }

  .proj-completed {
    color: #8A9BB8;
    background: rgba(138,155,184,0.15);
    border: 1px solid rgba(138,155,184,0.28);
  }
  .proj-ongoing {
    color: var(--gold);
    background: rgba(201,168,76,0.12);
    border: 1px solid rgba(201,168,76,0.30);
  }
  .proj-upcoming {
    color: var(--gold-light);
    background: rgba(232,201,122,0.10);
    border: 1px solid rgba(232,201,122,0.28);
  }

  /* ── Card body ── */
  .proj-card-body { padding: 24px; flex: 1; display: flex; flex-direction: column; }

  .proj-category {
    font-family: 'Space Mono', monospace;
    font-size: 9px; letter-spacing: 0.15em; text-transform: uppercase;
    color: var(--gold); margin-bottom: 12px;
    display: inline-flex; align-items: center; gap: 6px;
  }
  .proj-category i { font-size: 10px; }

  .proj-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 21px; font-weight: 600; color: var(--cream);
    line-height: 1.2; margin-bottom: 12px;
  }

  .proj-desc {
    font-size: 13px; font-weight: 300; color: var(--text-muted);
    line-height: 1.75; margin-bottom: 24px; flex: 1;
  }

  /* ── Action button ── */
  .proj-actions { margin-top: auto; }
  .proj-btn {
    font-size: 11px; font-weight: 600; letter-spacing: 0.1em; text-transform: uppercase;
    color: var(--gold);
    background: transparent;
    border: 1px solid rgba(201,168,76,0.30);
    padding: 10px 22px;
    cursor: pointer;
    display: inline-flex; align-items: center; gap: 8px;
    transition: all var(--transition-fast);
    font-family: 'Outfit', sans-serif;
    border-radius: 2px;
  }
  .proj-btn:hover {
    color: var(--navy);
    background: linear-gradient(135deg, var(--gold-light), var(--gold));
    border-color: transparent;
  }

  /* ── Empty state ── */
  .proj-empty {
    background: rgba(201,168,76,0.04);
    border: 1px dashed rgba(201,168,76,0.18);
    border-radius: 4px;
  }

  /* ══════════════════════════════════════
     PROJECT DETAIL MODAL
  ══════════════════════════════════════ */
  .proj-modal-overlay {
    position: fixed; inset: 0;
    background: rgba(5,10,22,0.85);
    z-index: 9999;
    display: flex; align-items: center; justify-content: center;
    padding: 40px 20px;
    opacity: 0; pointer-events: none;
    transition: opacity 0.3s ease;
    backdrop-filter: blur(6px);
  }
  .proj-modal-overlay.open { opacity: 1; pointer-events: all; }

  .proj-modal {
    background: var(--navy-mid);
    border: 1px solid rgba(201,168,76,0.20);
    border-radius: 4px;
    max-width: 680px;
    width: 100%;
    max-height: 85vh;
    overflow-y: auto;
    position: relative;
    box-shadow: 0 40px 80px rgba(0,0,0,0.50);
    transform: translateY(20px);
    transition: transform 0.3s ease;
  }
  .proj-modal-overlay.open .proj-modal { transform: translateY(0); }

  .proj-modal-close {
    position: absolute; top: 16px; right: 16px;
    width: 36px; height: 36px;
    background: rgba(201,168,76,0.08);
    border: 1px solid rgba(201,168,76,0.20);
    border-radius: 50%;
    color: var(--gold); font-size: 16px;
    cursor: pointer; display: flex;
    align-items: center; justify-content: center;
    transition: all var(--transition-fast);
    z-index: 10;
  }
  .proj-modal-close:hover { background: rgba(201,168,76,0.18); border-color: var(--gold); }

  .proj-modal-img {
    width: 100%; height: 260px;
    object-fit: cover; display: block;
  }
  .proj-modal-body { padding: 36px; }
  .proj-modal-badge {
    display: inline-block;
    font-family: 'Space Mono', monospace;
    font-size: 9px; font-weight: 700; letter-spacing: 0.15em; text-transform: uppercase;
    padding: 5px 14px; border-radius: 2px; margin-bottom: 16px;
  }
  .proj-modal-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 30px; font-weight: 600; color: var(--cream);
    margin-bottom: 12px; line-height: 1.15;
  }
  .proj-modal-category {
    font-family: 'Space Mono', monospace;
    font-size: 9px; letter-spacing: 0.15em; text-transform: uppercase;
    color: var(--gold); margin-bottom: 20px;
  }
  .proj-modal-desc {
    font-size: 14px; font-weight: 300; color: var(--text-muted);
    line-height: 1.85; margin: 0 0 32px;
  }
  .proj-modal-meta-row {
    display: flex; gap: 32px;
    padding-top: 20px;
    border-top: 1px solid rgba(201,168,76,0.10);
  }
  .proj-modal-meta-item { font-size: 12px; }
  .proj-modal-meta-label {
    font-family: 'Space Mono', monospace;
    font-size: 9px; letter-spacing: 0.15em; text-transform: uppercase;
    color: var(--text-muted); margin-bottom: 4px;
  }
  .proj-modal-meta-value { color: var(--cream); }

  /* ══════════════════════════════════════
     RESPONSIVE
  ══════════════════════════════════════ */
  @media (max-width: 1100px) {
    .proj-grid { grid-template-columns: repeat(2, 1fr); }
  }
  @media (max-width: 768px) {
    .projects-page-hero { min-height: 360px; padding: 130px 20px 80px; }
    .proj-list-section { padding: 60px 20px 100px; }
    .proj-grid { grid-template-columns: 1fr; }
    .projects-hero-stats { gap: 24px; }
    .projects-hero-title { font-size: clamp(40px, 10vw, 64px); }
    .proj-modal { margin: 20px auto; max-height: calc(100vh - 40px); }
    .proj-modal-body { padding: 24px; }
  }
  @media (max-width: 480px) {
    .projects-hero-stats { flex-direction: column; align-items: center; gap: 16px; }
    .projects-stat-num { font-size: 36px; }
  }
</style>



<script>
    (() => {
        const modalOverlay = document.getElementById('projModal');
        const modalBody = document.getElementById('projModalBody');
        const modalClose = document.getElementById('projModalClose');

        if (!modalOverlay || !modalBody || !modalClose) return;

        const escapeHtml = (value) => String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');

        const statusClass = {
            upcoming: 'proj-upcoming',
            ongoing: 'proj-ongoing',
            completed: 'proj-completed'
        };

        const openProjectModal = (card) => {
            const title = card.dataset.title || 'Untitled Project';
            const category = card.dataset.category || 'Project';
            const status = card.dataset.status || 'upcoming';
            const description = card.dataset.description || 'No description available.';
            const image = card.dataset.image || 'https://picsum.photos/seed/project/800/600';

            modalBody.innerHTML = `
                <img src="${escapeHtml(image)}" alt="${escapeHtml(title)}" class="proj-modal-img" loading="lazy">
                <div style="padding: 36px;">
                    <span class="proj-modal-badge ${statusClass[status] || ''}">${escapeHtml(status.charAt(0).toUpperCase() + status.slice(1))}</span>
                    <h2 class="proj-modal-title" id="projModalTitle">${escapeHtml(title)}</h2>
                    <p class="proj-modal-category"><i class="bi bi-tag-fill"></i> ${escapeHtml(category)}</p>
                    <p class="proj-modal-desc">${escapeHtml(description)}</p>
                    <div class="proj-modal-meta-row">
                        <div class="proj-modal-meta-item">
                            <div class="proj-modal-meta-label">Status</div>
                            <div class="proj-modal-meta-value">${escapeHtml(status)}</div>
                        </div>
                        <div class="proj-modal-meta-item">
                            <div class="proj-modal-meta-label">Category</div>
                            <div class="proj-modal-meta-value">${escapeHtml(category)}</div>
                        </div>
                    </div>
                </div>
            `;

            modalOverlay.classList.add('open');
            modalOverlay.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
            modalClose.focus();
        };

        const closeProjectModal = () => {
            modalOverlay.classList.remove('open');
            modalOverlay.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
        };

        document.querySelectorAll('.proj-card').forEach((card) => {
            const button = card.querySelector('.proj-btn');

            if (button) {
                button.addEventListener('click', () => openProjectModal(card));
            }

            card.addEventListener('keydown', (event) => {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    openProjectModal(card);
                }
            });
        });

        modalClose.addEventListener('click', closeProjectModal);

        modalOverlay.addEventListener('click', (event) => {
            if (event.target === modalOverlay) closeProjectModal();
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') closeProjectModal();
        });
    })();
</script>

</body>
</html>
