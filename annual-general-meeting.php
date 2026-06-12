<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/functions.php';
require_once __DIR__ . '/models/GaSessions.php';

$gaModel = new GaSessions(db());

// ── Upcoming AGM ──
$upcoming = db()->fetch(
    "SELECT * FROM ga_sessions
     WHERE session_type = 'ANNUAL' AND status IN ('SCHEDULED','IN_PROGRESS')
     ORDER BY scheduled_datetime ASC LIMIT 1"
);
$hasUpcoming = (bool)$upcoming;

$agmSessions = $gaModel->getByType('ANNUAL', 20);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Annual General Meeting — DHLTU SRC</title>
  <meta name="description" content="Annual General Meeting (AGM) of DHLTU SRC. View upcoming sessions, past meeting archive, and download official minutes for each assembly.">
  <meta name="keywords" content="DHLTU SRC AGM, Annual General Meeting, SRC Annual Meeting, Student Congress, General Assembly">
  <meta name="author" content="DHLTU SRC">
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
    <div class="section-eyebrow" style="--content: '01';">General Assembly · Annual Meeting</div>
    <h1 class="constitution-title">Annual General Meeting</h1>
    <p class="constitution-subtitle">Dr. Hilla Limann Technical University SRC · 2024 / 2025 Session</p>
    <div class="constitution-meta">
      <div class="meta-item"><i class="bi bi-calendar-event"></i><span>Next AGM — June 15, 2025 · 10:00 AM</span></div>
      <div class="meta-item"><i class="bi bi-geo-alt"></i><span>University Auditorium</span></div>
      <div class="meta-item"><i class="bi bi-people"></i><span>All Students &amp; Council Members</span></div>
    </div>
  </div>
</section>

<div class="cinematic-divider"></div>

<!-- ═══════════════════════════════════════════════════
     UPCOMING AGM CARD
═══════════════════════════════════════════════════ -->
<section class="section" id="upcoming">
  <div class="container">
    <div class="section-header">
      <div class="section-eyebrow" style="--content: '02';">Upcoming Session</div>
      <h2 class="section-title">Next Annual General Meeting</h2>
    </div>

    <?php if ($hasUpcoming): ?>
      <?php $u = $upcoming; ?>
      <div class="card" style="margin-bottom:20px;">
        <div class="constitution-meta" style="margin-bottom:24px;">
          <div class="meta-item"><i class="bi bi-calendar-event"></i><span><?= formatDate($u['scheduled_datetime'],'F j, Y') ?></span></div>
          <div class="meta-item"><i class="bi bi-clock"></i><span><?= formatDate($u['scheduled_datetime'],'g:i A') ?></span></div>
          <div class="meta-item"><i class="bi bi-geo-alt"></i><span><?= htmlspecialchars($u['location'] ?? 'TBA') ?></span></div>
          <div class="meta-item"><i class="bi bi-badge-tm"></i><span><em style="color:var(--gold);">Status: <?= htmlspecialchars($u['status']) ?></em></span></div>
        </div>
        <p style="margin-bottom:32px;">
          <?= htmlspecialchars($u['description'] ?? 'The Annual General Meeting is the highest decision-making body of the SRC. All students are encouraged to attend, participate in debates, and exercise their vote.') ?>
        </p>

        <?php
          // Normalise: admin portal saves ../uploads/agendas/…
          // Strip the ../ prefix so the browser loads from the correct path.
          $agendaRaw  = str_replace('../', '', $u['minutes_url'] ?? '');
          $agendaShow = htmlspecialchars($agendaRaw);
          $agendaJs   = htmlspecialchars(addslashes($agendaRaw));
        ?>
        <?php if ($agendaRaw): ?>
        <div style="display:flex;gap:16px;justify-content:flex-start;flex-wrap:wrap;margin-top:32px;">

          <!-- Agenda toggle button — URL stored in data-url, no escaping needed -->
          <button type="button" class="btn-primary"
            onclick="toggleAgendaPreview(this)"
            data-agenda-url="<?= $agendaShow ?>">
            <i class="bi bi-file-earmark-pdf"></i>
            <span id="agendaBtnLabel">Agenda</span>
          </button>

          <!-- Hidden inline PDF preview -->
          <div id="agendaPreviewBox" style="display:none;margin-top:28px;width:100%;border:1px solid rgba(201,168,76,0.18);border-radius:12px;overflow:hidden;background:#0a1225;">
            <div style="padding:14px 20px;background:rgba(201,168,76,0.06);border-bottom:1px solid rgba(201,168,76,0.18);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
              <span style="font-family:'Space Mono',monospace;font-size:11px;letter-spacing:0.15em;text-transform:uppercase;color:var(--gold);">
                <i class="bi bi-file-earmark-pdf"></i> Agenda Preview
              </span>
              <a href="<?= $agendaShow ?>" class="btn btn-sm btn-outline" target="_blank" rel="noopener"
                 style="padding:6px 16px;font-size:11px;">
                <i class="bi bi-download"></i> Download PDF
              </a>
            </div>
            <iframe id="agendaIframe" src="" style="width:100%;height:640px;border:none;display:block;" title="Agenda PDF Preview"></iframe>
          </div>

          <!-- Add to Calendar -->
          <a href="meeting-minutes.php" class="btn-ghost"
             style="background:transparent;color:#a0b4d0;padding:8px 16px;border:1px solid rgba(160,180,208,.25);border-radius:6px;cursor:pointer;font-family:'Outfit',sans-serif;font-size:13px;text-decoration:none;display:inline-flex;align-items:center;gap:8px;vertical-align:middle;">
            <i class="bi bi-calendar-plus"></i> Add to Calendar
          </a>

        </div>
        <?php endif; ?>

      </div>
    <?php else: ?>
    <div class="empty-state"><i class="bi bi-calendar-x"></i><p>No upcoming Annual General Meeting is currently scheduled.</p><p style="font-size:13px;margin-top:8px;">Check back soon or follow our announcements for updates.</p></div>
    <?php endif; ?>
  </div>
</section>

<div class="cinematic-divider"></div>

<!-- ═══════════════════════════════════════════════════
     AGM SESSIONS GRID
═══════════════════════════════════════════════════ -->
<section class="section" id="sessions-grid" style="background:var(--navy-mid);">
  <div class="container">
    <div class="section-header">
      <div class="section-eyebrow" style="--content: '03';">All Sessions</div>
      <h2 class="section-title">Annual General Meetings</h2>
    </div>
    <div class="info-section">
    <?php if (empty($agmSessions)): ?>
      <div class="empty-state" style="grid-column:1/-1;"><i class="bi bi-calendar-event"></i><p>No AGM sessions have been added yet.</p></div>
    <?php else: foreach ($agmSessions as $row):
      $rowYear     = (int)formatDate($row['scheduled_datetime'],'Y');
      $sessionLabel= ordinalSuffix($rowYear) . ' AGM';
      $dtRow       = formatDate($row['scheduled_datetime'],'M j, Y');
      $timeRow     = formatDate($row['scheduled_datetime'],'g:i A');
      $statusSlug  = strtolower(str_replace('_','_',$row['status'] ?? 'COMPLETED'));
      $abstract    = htmlspecialchars(
          $row['description']
          ?: sprintf('%s Annual General Meeting covering %s.', ordinalSuffix($rowYear), strtolower(str_replace('-',' ',$row['session_type'])))
      );
      $minutesLabel= 'Minutes';
      if (!empty($row['minutes_file_path'])) { $minutesLabel = htmlspecialchars($row['minutes_meeting_title'] ?: 'Minutes'); }
    ?>
    <div class="ga-session-card">
      <div class="session-card-topbar">
        <span class="session-type-badge session-type-annual">ANNUAL</span>
        <span class="session-status-badge status-<?= $statusSlug ?>"><?= htmlspecialchars($row['status']) ?></span>
      </div>
      <span class="session-card-year"><?= $sessionLabel ?></span>
      <div class="session-card-meta">
        <span class="session-meta-item"><i class="bi bi-calendar-event"></i> <?= $dtRow ?></span>
        <span class="session-meta-item"><i class="bi bi-clock"></i> <?= $timeRow ?></span>
        <span class="session-meta-item"><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($row['location'] ?? '') ?></span>
      </div>
      <p class="session-card-abstract"><?= $abstract ?></p>
      <div class="session-card-actions">
        <?php if (!empty($row["minutes_url"])):
          $agendaGridHref = str_replace('../','',$row["minutes_url"]);
        ?>
        <a href="<?= htmlspecialchars($agendaGridHref) ?>" class="session-btn session-btn-primary" title="<?= $minutesLabel ?>">
          <i class="bi bi-file-earmark-pdf"></i> <?= $minutesLabel ?>
        </a>
        <?php endif; ?>
        <a href="resolutions.php" class="session-btn session-btn-outline"><i class="bi bi-file-earmark-text"></i> Resolutions</a>
      </div>
    </div>
    <?php endforeach; endif; ?>
    </div><!-- /info-section -->
  </div>
</section>

<div class="cinematic-divider"></div>

<!-- ═══════════════════════════════════════════════════
     QUICK RESOURCES
═══════════════════════════════════════════════════ -->
<section class="section" id="ga-resources">
  <div class="container">
    <div class="section-header">
      <div class="section-eyebrow" style="--content: '04';">Quick Access</div>
      <h2 class="section-title">AGM Resources</h2>
    </div>
    <div class="info-section">
      <div class="info-card">
        <div class="info-card-icon"><i class="bi bi-file-earmark-pdf"></i></div>
        <h4>Minutes Archive</h4>
        <p>Download official minutes from every AGM session. Minutes are ratified at the following ordinary meeting.</p>
        <a href="meeting-minutes.php" style="font-size:13px;margin-top:12px;display:inline-block;">View Archive &rarr;</a>
      </div>
      <div class="info-card">
        <div class="info-card-icon"><i class="bi bi-file-earmark-text"></i></div>
        <h4>Resolutions &amp; Motions</h4>
        <p>Browse all AGM resolutions and student motions. Tracks what was debated and which motions were carried.</p>
        <a href="resolutions.php" style="font-size:13px;margin-top:12px;display:inline-block;">View Resolutions &rarr;</a>
      </div>
      <div class="info-card">
        <div class="info-card-icon"><i class="bi bi-people"></i></div>
        <h4>Attendance Records</h4>
        <p>Quorum and attendance records for each AGM session. Managed through the ga_attendance table.</p>
        <a href="#" style="font-size:13px;margin-top:12px;display:inline-block;">View Attendance &rarr;</a>
      </div>
      <div class="info-card">
        <div class="info-card-icon"><i class="bi bi-cone-striped"></i></div>
        <h4>Emergency GA &amp; Special Sessions</h4>
        <p>Urgent and topic-focused assemblies convened outside the annual calendar.</p>
        <a href="emergency-ga.php" style="font-size:13px;margin-top:12px;display:inline-block;">View Other Assemblies &rarr;</a>
      </div>
    </div>
  </div>
</section>

<?php include 'include/footer.php'; ?>


</body></html>
