<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/functions.php';
require_once __DIR__ . '/models/GaSessions.php';

$gaModel = new GaSessions(db());

try {
    $passedRes = $gaModel->getResolutionsForPage([]);
} catch (Throwable $e) {
    $passedRes = [];
}

try {
    $allVotes = $gaModel->getVotingRecordsForPage([], 20);
} catch (Throwable $e) {
    $allVotes = [];
}
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Resolutions &amp; Motions â€” DHLTU SRC</title>
  <meta name="description" content="Resolutions and motions passed by DHLTU SRC. Browse the archive of student initiatives and policy decisions.">
  <meta name="keywords" content="DHLTU SRC Resolutions, Motions, Policy Decisions, Student Initiatives">
  <meta name="author" content="DHLTU SRC">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,,0,400;0,600,0,700;1,300,1,400&family=Outfit:wght@200;300;400;500;600;700&family=Space+Mono:ital@0,400;0,700;1,400&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
  <link rel="stylesheet" href="assets/css/main.css">
<link rel="icon" type="image/png" href="assets/images/logo.png">
</head>
<body>
<?php include __DIR__ . '/include/header.php'; ?>

<!-- HERO -->
<section class="constitution-header">
  <div class="constitution-content">
    <div class="section-eyebrow style="--content: '01';">Policy Archive</div>
    <h1 class="constitution-title">Resolutions &amp; Motions</h1>
    <p class="constitution-subtitle">Official resolutions and motions adopted by the Student Representative Council</p>
  </div>
</section>

<div class="cinematic-divider"></div>

<!-- RESOLUTIONS SECTION -->
<section class="section" id="resolutions">
  <div class="container">
    <div class="section-header">
      <div class="section-eyebrow style="--content: '02';">Archive</div>
      <h2 class="section-title">Adopted Resolutions</h2>
    </div>

    <div class="info-section">
      <?php if (empty($passedRes)): ?>
      <div class="empty-state" style="grid-column: 1/-1;">
        <i class="bi bi-file-earmark-text"></i>
        <p>No passed resolutions have been recorded yet.</p>
      </div>
      <?php else: foreach ($passedRes as $row):
        $rNo      = htmlspecialchars(strval($row['resolution_no'] ?? ''));
        $rTitle   = htmlspecialchars(strval($row['title'] ?? 'Untitled'));
        $rExcerpt = htmlspecialchars(truncate(strval($row['body'] ?? ''), 140));
        $cat      = htmlspecialchars(strval($row['category'] ?? ''));
        $session  = htmlspecialchars(strval($row['session_title'] ?? ''));
        $dtRow    = formatDate(strval($row['created_at'] ?? $row['scheduled_datetime'] ?? ''), 'M j, Y');
      ?>
      <div class="info-card">
        <div class="info-card-icon">
          <i class="bi bi-file-earmark-text"></i>
        </div>
        <h4><?php echo $rNo ? $rNo . ' &mdash; ' : ''; ?><?php echo $rTitle; ?></h4>
        <p><?php echo $rExcerpt; ?></p>
        <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-top:12px;">
          <span class="badge" style="background:rgba(201,168,76,.12);color:#c9a84c;font-size:10px;">
            <?php echo $cat ?: 'RESOLUTION'; ?>
          </span>
          <?php if ($session): ?>
          <span style="font-size:11px;color:var(--text-muted);font-family:'Space Mono',monospace;">
            <?php echo $session; ?>, <?php echo $dtRow; ?>
          </span>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; endif; ?>
    </div>
  </div>
</section>

<div class="cinematic-divider"></div>

<!-- MOTIONS SECTION -->
<section class="section" id="motions" style="background:var(--navy-mid);">
  <div class="container">
    <div class="section-header">
      <div class="section-eyebrow style="--content: '03';">Motions</div>
      <h2 class="section-title">Recent Motion Votes</h2>
    </div>

<div class="card">
       <div class="table-responsive">
         <table style="width:100%;border-collapse:collapse;">
           <thead>
             <tr style="border-bottom:1px solid rgba(201,168,76,0.2);">
               <th style="text-align:left;padding:12px 16px;font-family:'Space Mono',monospace;font-size:11px;letter-spacing:.1em;text-transform:uppercase;color:var(--gold);">Motion</th>
               <th style="text-align:left;padding:12px 16px;font-family:'Space Mono',monospace;font-size:11px;letter-spacing:.1em;text-transform:uppercase;color:var(--gold);">Session</th>
               <th style="text-align:left;padding:12px 16px;font-family:'Space Mono',monospace;font-size:11px;letter-spacing:.1em;text-transform:uppercase;color:var(--gold);">Title</th>
               <th style="text-align:left;padding:12px 16px;font-family:'Space Mono',monospace;font-size:11px;letter-spacing:.1em;text-transform:uppercase;color:var(--gold);">Status</th>
               <th style="text-align:left;padding:12px 16px;font-family:'Space Mono',monospace;font-size:11px;letter-spacing:.1em;text-transform:uppercase;color:var(--gold);">Date</th>
             </tr>
           </thead>
           <tbody>
          <?php if (empty($allVotes)): ?>
          <tr><td colspan="5" style="padding:32px;text-align:center;color:var(--text-muted);">No motion votes have been recorded yet.</td></tr>
          <?php else: foreach ($allVotes as $vr):
            $vNo     = htmlspecialchars(strval($vr['id'] ?? ''));
            $vTitle  = htmlspecialchars(strval($vr['title'] ?? 'Untitled'));
            $vSession= htmlspecialchars(strval($vr['session_title'] ?? 'N/A'));
            $vResult = htmlspecialchars(strval($vr['result'] ?? 'PENDING'));
            $vClosed = formatDate(strval($vr['closed_at'] ?? $vr['opened_at'] ?? ''), 'M j, Y');
          ?>
          <tr style="border-bottom:1px solid rgba(255,255,255,.04);">
            <td style="padding:16px;color:var(--gold);font-family:'Space Mono',monospace;font-size:12px;">#<?php echo $vNo; ?></td>
            <td style="padding:16px;font-size:12px;color:var(--text-muted);"><?php echo $vSession; ?></td>
            <td style="padding:16px;"><?php echo $vTitle; ?></td>
            <td style="padding:16px;"><span style="color:<?php echo $vResult === 'PASSED' ? 'var(--green-accent)' : 'var(--accent-red)'; ?>;"><?php echo $vResult; ?></span></td>
            <td style="padding:16px;color:var(--text-muted);"><?php echo $vClosed; ?></td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
</table>
       </div>
     </div>
   </div>
 </section>



<?php include __DIR__ . '/include/footer.php'; ?>

</body>
</html>

