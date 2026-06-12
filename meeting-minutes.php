<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/functions.php';
require_once __DIR__ . '/models/GaSessions.php';

$gaModel = new GaSessions(db());

try {
    $allMinutes = $gaModel->getMinutesForPage([]);
} catch (Throwable $e) {
    $allMinutes = [];
}
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Meeting Minutes — DHLTU SRC</title>
  <meta name="description" content="Meeting minutes archive from DHLTU SRC. Access records from AGMs, EGAs, and committee meetings.">
  <meta name="keywords" content="DHLTU SRC Minutes, Meeting Minutes, AGM Minutes, EGA Minutes, Committee Minutes">
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
    <div class="section-eyebrow style="--content: '01';">Official Records</div>
    <h1 class="constitution-title">Meeting Minutes</h1>
    <p class="constitution-subtitle">Official minutes from SRC meetings, conventions, and committee sessions</p>
  </div>
</section>

<div class="cinematic-divider"></div>

<!-- MINUTES ARCHIVE SECTION -->
<section class="section" id="archive">
  <div class="container">
    <div class="section-header">
      <div class="section-eyebrow style="--content: '02';">Archive</div>
      <h2 class="section-title">Available Minutes</h2>
    </div>

<div class="card">
       <div class="table-responsive">
         <table style="width:100%;border-collapse:collapse;">
           <thead>
             <tr style="border-bottom:1px solid rgba(201,168,76,0.2);">
               <th style="text-align:left;padding:12px 16px;font-family:'Space Mono',monospace;font-size:11px;letter-spacing:.1em;text-transform:uppercase;color:var(--gold);">Date</th>
               <th style="text-align:left;padding:12px 16px;font-family:'Space Mono',monospace;font-size:11px;letter-spacing:.1em;text-transform:uppercase;color:var(--gold);">Meeting</th>
               <th style="text-align:left;padding:12px 16px;font-family:'Space Mono',monospace;font-size:11px;letter-spacing:.1em;text-transform:uppercase;color:var(--gold);">Type</th>
               <th style="text-align:left;padding:12px 16px;font-family:'Space Mono',monospace;font-size:11px;letter-spacing:.1em;text-transform:uppercase;color:var(--gold);">Status</th>
               <th style="text-align:left;padding:12px 16px;font-family:'Space Mono',monospace;font-size:11px;letter-spacing:.1em;text-transform:uppercase;color:var(--gold);">Download</th>
             </tr>
           </thead>
           <tbody>
          <?php if (empty($allMinutes)): ?>
          <tr><td colspan="5" style="padding:32px;text-align:center;color:var(--text-muted);">No meeting minutes have been added yet.</td></tr>
          <?php else: foreach ($allMinutes as $m):
            $mDate    = formatDate(strval($m['uploaded_at'] ?? $m['scheduled_datetime'] ?? ''), 'M j, Y');
            $mTitle   = htmlspecialchars($m['meeting_title'] ?? 'Untitled Meeting');
            $mType    = htmlspecialchars($m['session_type'] ?? 'General');
            $mStatus  = htmlspecialchars(strval($m['status'] ?? 'PUBLISHED'));
            $mFilePath= !empty($m['file_path']) ? htmlspecialchars(preg_replace('#^\.\./#', '', $m['file_path'])) : '#';
            $mOrig    = htmlspecialchars(strval($m['original_name'] ?? $m['meeting_title'] ?? 'Minutes'));
            $mSizeKB  = (int)($m['file_size'] ?? 0);
            $sizeLabel= $mSizeKB > 0 ? round($mSizeKB / 1024, 1) . ' KB' : '';
            $statusC  = (stripos($mStatus, 'ratified') !== false || stripos($mStatus, 'published') !== false) ? 'var(--green-accent)' : 'var(--gold)';
          ?>
          <tr style="border-bottom:1px solid rgba(255,255,255,.04);">
            <td style="padding:16px;color:var(--text-muted);"><?php echo $mDate; ?></td>
            <td style="padding:16px;"><?php echo $mTitle; ?></td>
            <td style="padding:16px;font-size:12px;font-family:'Space Mono',monospace;color:var(--gold-light);"><?php echo $mType; ?></td>
            <td style="padding:16px;color:<?php echo $statusC; ?>;"><?php echo $mStatus; ?></td>
            <td style="padding:16px;">
              <a href="<?php echo $mFilePath; ?>" style="color:var(--gold);" <?php echo $mFilePath === '#' ? 'aria-disabled="true"' : ''; ?>>
                <i class="bi bi-download"></i>
                <?php echo $mOrig . ($sizeLabel ? " ({$sizeLabel})" : ''); ?>
              </a>
            </td>
          </tr>
          <?php endforeach; endif; ?>
</tbody>
         </table>
       </div>
     </div>
   </div>
 </section>

<div class="cinematic-divider"></div>

<!-- SEARCH SECTION -->
<section class="section" id="search" style="background:var(--navy-mid);">
  <div class="container">
    <div class="section-header">
      <div class="section-eyebrow style="--content: '03';">Search</div>
      <h2 class="section-title">Find Minutes by Date or Topic</h2>
    </div>

    <div class="card" style="max-width:600px;margin:0 auto;">
      <div class="form-group">
        <label for="searchInput">Search Minutes</label>
        <input type="text" id="searchInput" placeholder="e.g. AGM 2024, Hostel, Finance...">
      </div>
      <div class="form-group" style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
        <div>
          <label for="dateFrom">From Date</label>
          <input type="date" id="dateFrom">
        </div>
        <div>
          <label for="dateTo">To Date</label>
          <input type="date" id="dateTo">
        </div>
      </div>
      <button class="btn-primary" style="width:100%;"><i class="bi bi-search"></i> Search Minutes</button>
    </div>
  </div>
</section>



<?php include __DIR__ . '/include/footer.php'; ?>

</body>
</html>
