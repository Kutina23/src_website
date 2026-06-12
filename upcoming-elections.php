<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/functions.php';
require_once __DIR__ . '/models/Elections.php';
require_once __DIR__ . '/models/SiteSettings.php';

$db           = Database::getInstance();
$electionsModel = new Elections($db);
$settingsModel = new SiteSettings($db);

$siteSettings = $settingsModel->getHeroSection();
$sessionLabel = $siteSettings['session'] ?? date('Y') . '/' . ((int)date('Y') + 1);

$upcomingElections = $electionsModel->getUpcoming();
$activeElection   = $electionsModel->getActiveElection();
$allActive        = $electionsModel->getAllActive();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Upcoming Elections — DHLTU SRC</title>
  <meta name="description" content="View upcoming SRC elections, key dates, voter eligibility, and the complete election calendar for DHLTU students.">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;0,700;1,300;1,400&family=Outfit:wght@200;300;400;500;600;700&family=Space+Mono:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
  <link rel="stylesheet" href="assets/css/main.css">
  <link rel="icon" type="image/png" href="assets/images/logo.png">
</head>
<body>
<?php include 'include/header.php'; ?>

<!-- ═══════════════════════════════════════════════════
     HERO
═══════════════════════════════════════════════════ -->
<section class="constitution-header">
  <div class="constitution-content">
    <div class="section-eyebrow">Elections · SRC <?php echo htmlspecialchars($sessionLabel); ?></div>
    <h1 class="constitution-title">Upcoming <em style="color:var(--gold-light);">Elections</em></h1>
    <p class="constitution-subtitle">Dr. Hilla Limann Technical University Student Representative Council</p>
    <p style="font-size:15px;font-weight:300;color:rgba(245,240,232,0.65);max-width:560px;margin:0 auto;">
      Stay informed about all scheduled elections, key dates, and how to participate in DHLTU's democratic governance.
    </p>
    <div class="constitution-meta" style="margin-top:32px;">
      <div class="meta-item">
        <i class="bi bi-calendar-event"></i>
        <span>Session <?php echo htmlspecialchars($sessionLabel); ?></span>
      </div>
      <div class="meta-item">
        <i class="bi bi-flag"></i>
        <span>SRC Constitution Art. VII</span>
      </div>
    </div>
  </div>
</section>

<div class="cinematic-divider"></div>

<!-- ═══════════════════════════════════════════════════
     ELECTION PROCESS — 4 STEPS
═══════════════════════════════════════════════════ -->
<section class="section" id="election-cycle">
  <div class="container">
    <div class="section-header">
      <div class="section-eyebrow" style="--content: '02';">How It Works</div>
      <h2 class="section-title">The Election <em>Process</em></h2>
      <p style="font-size:15px;font-weight:300;color:var(--text-muted);max-width:480px;margin:0 auto;">
        Our six-step election cycle is designed to be transparent, fair, and accessible for every eligible student.
      </p>
    </div>

    <div class="election-steps" style="margin-top:60px;">

      <div class="election-step reveal">
        <div class="step-num">01</div>
        <div class="step-content">
          <div class="step-title">Announcement of Election Schedule</div>
          <div class="step-desc">The Electoral Commission publishes the official election calendar, including nomination opening and closing dates, campaign period, and voting dates. This notice is posted on the SRC notice board and website at least <strong style="color:var(--gold)">14 days</strong> before nominations open.</div>
        </div>
      </div>

      <div class="election-step reveal">
        <div class="step-num">02</div>
        <div class="step-content">
          <div class="step-title">Voter Eligibility Verification</div>
          <div class="step-desc">All currently registered and financially cleared students are automatically eligible. The Electoral Commission publishes the voter register for public inspection. Any student not on the list may submit a written complaint within <strong style="color:var(--gold)">3 working days</strong>.</div>
        </div>
      </div>

      <div class="election-step reveal">
        <div class="step-num">03</div>
        <div class="step-content">
          <div class="step-title">Candidate Nomination</div>
          <div class="step-desc">Eligible students submit nomination forms with at least <strong style="color:var(--gold)">50 signatures</strong> from fellow students. The Electoral Commission vets all candidates to confirm eligibility before the official candidate list is released.</div>
        </div>
      </div>

      <div class="election-step reveal">
        <div class="step-num">04</div>
        <div class="step-content">
          <div class="step-title">Election Campaign</div>
          <div class="step-desc">A formal campaign period of <strong style="color:var(--gold)">7 days</strong> follows, during which candidates may campaign across campus. Campaign rules, spending limits, and code of conduct are strictly enforced by the Electoral Commission.</div>
        </div>
      </div>

      <div class="election-step reveal">
        <div class="step-num">05</div>
        <div class="step-content">
          <div class="step-title">Digital Voting</div>
          <div class="step-desc">Voting is conducted through a secure online portal. Each eligible student receives a unique voter code via their institutional email. One vote per student, verified identity, encrypted ballot — a <strong style="color:var(--gold)">fully auditable digital process</strong>.</div>
        </div>
      </div>

      <div class="election-step reveal">
        <div class="step-num">06</div>
        <div class="step-content">
          <div class="step-title">Result Declaration</div>
          <div class="step-desc">Results are announced within <strong style="color:var(--gold)">48 hours</strong> of the close of polls, published on the SRC website, notice boards, and communicated to all candidates. A petition window of <strong style="color:var(--gold)">5 days</strong> applies for formal challenges.</div>
        </div>
      </div>

    </div>
  </div>
</section>

<div class="cinematic-divider"></div>

<!-- ═══════════════════════════════════════════════════
     ELIGIBILITY
═══════════════════════════════════════════════════ -->
<section class="section" id="eligibility" style="background:var(--navy-mid);">
  <div class="container">
    <div style="max-width:800px;margin:0 auto;">
      <div class="section-header">
        <div class="section-eyebrow" style="--content: '03';">Who Can Vote</div>
        <h2 class="section-title">Voter <em>Eligibility</em></h2>
      </div>

      <div class="info-section" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:24px;margin-top:60px;">

        <div class="info-card reveal delay-1">
          <div class="info-card-icon"><i class="bi bi-mortarboard-fill"></i></div>
          <h4>Enrolled Students</h4>
          <p>All students currently registered for the academic session are automatically eligible to vote and run for office.</p>
        </div>

        <div class="info-card reveal delay-2">
          <div class="info-card-icon"><i class="bi bi-check2-circle"></i></div>
          <h4>Financially Cleared</h4>
          <p>Students with no outstanding SRC dues or university fees for the current academic session are eligible to cast their vote.</p>
        </div>

        <div class="info-card reveal delay-3">
          <div class="info-card-icon"><i class="bi bi-envelope-check"></i></div>
          <h4>Valid Student ID</h4>
          <p>A valid DHLTU student identity card or digital student e-ID must be presented during the registration verification phase.</p>
        </div>

        <div class="info-card reveal delay-4">
          <div class="info-card-icon"><i class="bi bi-people-fill"></i></div>
          <h4>Faculty Representation</h4>
          <p>Voting is organised by faculty and school — each department elects its own executives who then sit on the main SRC Council.</p>
        </div>

      </div>
    </div>
  </div>
</section>

<div class="cinematic-divider"></div>

<!-- ═══════════════════════════════════════════════════
     UPCOMING ELECTIONS LIST
═══════════════════════════════════════════════════ -->
<section class="section" id="upcoming-elections">
  <div class="container">
    <div class="section-header">
      <div class="section-eyebrow" style="--content: '04';">Key Dates</div>
      <h2 class="section-title">Upcoming <em>Elections</em></h2>
    </div>

    <?php if (!empty($upcomingElections)): ?>
      <div class="ga-sessions-grid" style="margin-top:60px;">

        <?php foreach ($upcomingElections as $e): ?>
        <div class="ga-session-card reveal">
          <div class="session-card-topbar">
            <span class="session-type-badge" style="color:var(--gold);background:rgba(201,168,76,0.10);border:1px solid rgba(201,168,76,0.30);">
              <i class="bi bi-calendar3"></i> Upcoming
            </span>
            <span class="status-scheduled" style="color:var(--green-accent);background:rgba(30,107,74,0.12);border:1px solid rgba(30,107,74,0.30);">
              Scheduled
            </span>
          </div>

          <div class="session-card-year">
            <?php echo htmlspecialchars($e['title'] ?: ucwords(str_replace('_', ' ', $e['position']))); ?>
          </div>
          <div class="session-card-meta">
            <div class="session-meta-item">
              <i class="bi bi-calendar2-week"></i>
              <span><?php echo !empty($e['election_date']) ? formatDate($e['election_date'], 'l, F j, Y') : 'Date TBA'; ?></span>
            </div>
            <div class="session-meta-item">
              <i class="bi bi-clock"></i>
              <span>
                <?php
                  echo !empty($e['start_time'])
                    ? formatDateTime(($e['election_date'] ?? date('Y-m-d')) . ' ' . $e['start_time'], 'g:i A')
                    : 'Time TBA';
                ?>
              </span>
            </div>
          </div>
          <?php if (!empty($e['location'])): ?>
          <div class="session-card-meta" style="margin-top:4px;">
            <div class="session-meta-item">
              <i class="bi bi-geo-alt"></i>
              <span><?php echo htmlspecialchars($e['location']); ?></span>
            </div>
          </div>
          <?php endif; ?>

          <div class="session-card-actions" style="margin-top:auto;">
            <a href="#" class="session-btn session-btn-primary">
              <i class="bi bi-calendar-check"></i> Mark as Interested
            </a>
          </div>
        </div>
        <?php endforeach; ?>

      </div>
    <?php else: ?>
      <div style="text-align:center;padding:80px 20px;background:var(--navy);border:1px solid rgba(201,168,76,0.08);">
        <i class="bi bi-calendar-x" style="font-size:48px;color:rgba(201,168,76,0.2);display:block;margin-bottom:20px;"></i>
        <p style="font-size:16px;color:var(--text-muted);">No upcoming elections are currently scheduled.</p>
        <p style="font-size:13px;color:var(--text-muted);margin-top:8px;">Check back soon or contact the Electoral Commission for latest updates.</p>
      </div>
    <?php endif; ?>

  </div>
</section>

<div class="cinematic-divider"></div>

<!-- ═══════════════════════════════════════════════════
     ALL ELECTIONS HISTORY
═══════════════════════════════════════════════════ -->
<section class="section" id="election-history" style="background:var(--navy-mid);">
  <div class="container">
    <div class="section-header">
      <div class="section-eyebrow" style="--content: '04';">Archive</div>
      <h2 class="section-title">Past <em>Elections</em></h2>
      <p style="font-size:14px;font-weight:300;color:var(--text-muted);margin-top:8px;">
        A record of completed election cycles. Full results are published after each election.
      </p>
    </div>

    <?php
      $pastElections = array_filter($allActive, function ($e) {
        return in_array(($e['status'] ?? ''), ['COMPLETED', 'CLOSED'], true);
      });
      if (empty($pastElections)) {
        // Fallback: show completed records from getAllActive
        $pastElections = array_slice($allActive, count($allActive) > 3 ? 3 : 0);
      }
    ?>

    <?php if (!empty($pastElections)): ?>
      <div class="info-section" style="max-width:900px;margin:60px auto 0;display:flex;flex-direction:column;gap:16px;">

        <?php foreach ($pastElections as $e): ?>
        <div style="display:grid;grid-template-columns:auto 1fr auto;gap:24px;align-items:center;padding:24px 28px;background:var(--navy);border:1px solid rgba(201,168,76,0.08);transition:border-color 0.3s ease;" class="reveal" onmouseover="this.style.borderColor='rgba(201,168,76,0.25)'" onmouseout="this.style.borderColor='rgba(201,168,76,0.08)'">
          <div style="text-align:center;min-width:56px;">
            <div style="font-family:'Cormorant Garamond',serif;font-size:28px;font-weight:700;color:var(--gold-light);line-height:1;">
              <?php echo !empty($e['election_date']) ? date('d', strtotime($e['election_date'])) : '--'; ?>
            </div>
            <div style="font-size:10px;letter-spacing:0.15em;text-transform:uppercase;color:var(--text-muted);margin-top:2px;">
              <?php echo !empty($e['election_date']) ? date('M', strtotime($e['election_date'])) : ''; ?>
              <?php echo !empty($e['election_date']) ? date('Y', strtotime($e['election_date'])) : ''; ?>
            </div>
          </div>
          <div>
            <div style="font-size:16px;font-weight:600;color:var(--cream);margin-bottom:4px;">
              <?php echo htmlspecialchars($e['title'] ?: ucwords(str_replace('_', ' ', $e['position']))); ?>
            </div>
            <div style="font-size:12px;color:var(--text-muted);">
              <?php echo !empty($e['location']) ? '<i class="bi bi-geo-alt" style="color:var(--gold);font-size:12px;"></i> ' . htmlspecialchars($e['location']) : ''; ?>
            </div>
          </div>
          <div>
            <span class="session-type-badge"
              style="<?php
                if (($e['status'] ?? '') === 'ONGOING') {
                    echo 'color:#febc2e;background:rgba(254,188,46,0.1);border:1px solid rgba(254,188,46,0.28);';
                } else {
                    echo 'color:var(--text-muted);background:rgba(138,155,184,0.08);border:1px solid rgba(138,155,184,0.18);';
                }
              ?>">
              <?php echo htmlspecialchars($e['status'] ?? 'Completed'); ?>
            </span>
          </div>
        </div>
        <?php endforeach; ?>

      </div>
    <?php else: ?>
      <p style="text-align:center;color:var(--text-muted);padding:60px 20px;">No completed election records on file yet.</p>
    <?php endif; ?>

  </div>
</section>

<div class="cinematic-divider"></div>

<!-- ═══════════════════════════════════════════════════
     QUICK LINKS / CTA
═══════════════════════════════════════════════════ -->
<section class="section" id="elections-cta" style="background:var(--navy);">
  <div style="max-width:700px;margin:0 auto;text-align:center;">
    <h2 style="font-family:'Cormorant Garamond',serif;font-size:clamp(32px,4vw,52px);font-weight:300;line-height:1.1;color:var(--cream);margin-bottom:20px;">
      Know Your <em style="color:var(--gold-light);">Elections</em>
    </h2>
    <p style="font-size:15px;font-weight:300;line-height:1.9;color:rgba(245,240,232,0.65);max-width:480px;margin:0 auto 48px;">
      Every election cycle is managed and supervised by the Electoral Commission. Meet them below.
    </p>
    <div style="display:flex;gap:16px;justify-content:center;flex-wrap:wrap;">
      <a href="electoral-commission.php" class="btn-primary">
        <i class="bi bi-people-fill"></i> Meet the Electoral Commission
      </a>
      <a href="index.php#contact" class="btn-outline">Contact Us</a>
    </div>
  </div>
</section>

<?php include 'include/footer.php'; ?>

<!-- Scroll Reveal -->
<script>
  const revealEls = document.querySelectorAll('.reveal, .reveal-left, .reveal-right');
  const io = new IntersectionObserver((entries) => {
    entries.forEach(e => { if(e.isIntersecting) e.target.classList.add('visible'); });
  }, { threshold: 0.1 });
  revealEls.forEach(el => io.observe(el));
</script>
</body>
</html>
