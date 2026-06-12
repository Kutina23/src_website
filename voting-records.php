<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/functions.php';
require_once __DIR__ . '/models/GaVoting.php';
require_once __DIR__ . '/models/GaSessions.php';

$gaModel     = new GaSessions(db());
$votingModel = new GaVoting(db());

$deviceId = $_SESSION['device_id'] ?? '';
if (empty($deviceId)) {
    $deviceId = bin2hex(random_bytes(16));
    $_SESSION['device_id'] = $deviceId;
}

$allRecords = [];
try {
    $allRecords = $gaModel->getVotingRecordsForPage([], 50);
} catch (Throwable $e) {
    $allRecords = [];
}

// Preload which records the current device already voted on
$votedOn = [];
try {
    foreach (array_column($allRecords, 'id') as $recId) {
        $vr = $votingModel->hasDeviceVoted((int)$recId, $deviceId);
        if ($vr) {
            $votedOn[(int)$recId] = $vr['choice'];
        }
    }
} catch (Throwable $e) { /* table may not exist yet */ }

$votesStats = [];
try {
    $votesStats = $votingModel->getStats();
} catch (Throwable $e) {
    $votesStats = ['total' => 0, 'open' => 0, 'closed' => 0, 'passed' => 0, 'rejected' => 0];
}

$resBadge = fn(string $cat) => match($cat) {
    'RESOLUTION' =>["c"=>"rgba(74,144,226,.15)" ,"bc"=>"rgba(74,144,226,.3)"  ,"col"=>"#6ab0ff"],
    'MOTION'    =>["c"=>"rgba(201,168,76,.15)","bc"=>"rgba(201,168,76,.3)" ,"col"=>"#c9a84c"],
    'AMENDMENT' =>["c"=>"rgba(160,90,120,.15)","bc"=>"rgba(160,90,120,.3)" ,"col"=>"#c06090"],
    'DECLARATION'=>["c"=>"rgba(55,180,120,.15)","bc"=>"rgba(55,180,120,.3)","col"=>"#3eb87c"],
    default     =>["c"=>"rgba(100,110,130,.15)","bc"=>"rgba(100,110,130,.3)","col"=>"#8a9ab0"],
};
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Voting Records — DHLTU SRC</title>
  <meta name="description" content="Voting records from DHLTU SRC meetings, elections, and general assemblies.">
  <meta name="keywords" content="DHLTU SRC Voting, Election Results, Motion Votes, SRC Voting Records">
  <meta name="author" content="DHLTU SRC">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,,0,400;0,600,0,700;1,300,1,400&family=Outfit:wght@200;300;400;500;600;700&family=Space+Mono:ital@0,400;0,700;1,400&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
  <link rel="stylesheet" href="assets/css/main.css">
  <style>
    .vote-btn{
      padding:5px 14px;border:1px solid;border-radius:4px;cursor:pointer;
      font-family:'Space Mono',monospace;font-size:11px;font-weight:700;background:none;
      transition:opacity .15s,transform .05s;
    }
    .vote-btn:active{transform:scale(.95);}
    .vote-btn:disabled{opacity:.5;cursor:not-allowed;}
    .vote-btn.vote-yes{color:var(--green-accent);border-color:rgba(55,180,120,.35);}
    .vote-btn.vote-no {color:var(--accent-red);  border-color:rgba(220,80,60,.35);}
    .vote-btn.vote-yes:hover{background:rgba(55,180,120,.1);}
    .vote-btn.vote-no:hover {background:rgba(220,80,60,.1);}

    /* ── Mobile responsive cards ────────────────────────── */
    @media (max-width: 768px) {
      .section{padding-top:48px;padding-bottom:48px;}
      .section-header{margin-bottom:20px;}
      .section-title{font-size:1.5rem;}
      .section-eyebrow{font-size:.75rem;}
      .info-card-icon i{font-size:1.6rem;}
    }

    @media (max-width: 960px) {
      /* Hide table header, wrap in scroll container */
      .rec-table-wrap{margin:0 -2rem;padding:0;overflow-x:auto;-webkit-overflow-scrolling:touch;scrollbar-width:thin;}
      #rec-table thead{display:none;}
      #rec-table,#rec-table tbody,#rec-table tr,#rec-table td{display:block;width:100%!important;border:none!important;}
      #rec-table tr{border-bottom:1px solid rgba(255,255,255,.06)!important;margin-bottom:12px;}
      #rec-table td{
        padding:.7rem .9rem!important;
        border-bottom:1px solid rgba(255,255,255,.04)!important;
        position:relative;
        padding-left:6.5rem!important;
        white-space:normal!important;
      }
      #rec-table td::before{
        content:attr(data-label);
        position:absolute;left:.9rem;top:.75rem;
        font-family:'Space Mono',monospace;font-size:9px;letter-spacing:.08em;text-transform:uppercase;color:var(--gold);
        display:block;font-weight:700;
      }
      td.col-date::before  {content:'DATE';}
      td.col-session::before {content:'SESSION';}
      td.col-topic::before   {content:'TOPIC';}
      td.col-votes::before   {content:'VOTES';}
      td.col-cast::before    {content:'YOUR VOTE';}
      td.col-result::before  {content:'RESULT';}
    }

    @media (max-width: 960px) and (hover:none) {
      /* Touch devices: larger tap targets, no hover effects */
      .vote-btn{
        min-height:44px;min-width:60px;font-size:13px;
        padding:8px 18px;border-radius:8px;
      }
    }
  </style>
<link rel="icon" type="image/png" href="assets/images/logo.png">
</head>
<body>
<?php include __DIR__ . '/include/header.php'; ?>

<!-- HERO -->
<section class="constitution-header">
  <div class="constitution-content">
    <div class="section-eyebrow style="--content: '01';">Archive</div>
    <h1 class="constitution-title">Voting Records</h1>
    <p class="constitution-subtitle">Transparent record of all votes conducted during SRC meetings and elections</p>
  </div>
</section>

<div class="cinematic-divider"></div>

<!-- VOTING RECORDS SECTION -->
<section class="section" id="records">
  <div class="container">
    <div class="section-header">
      <div class="section-eyebrow style="--content: '02';">Archive</div>
      <h2 class="section-title">Recent Voting Records</h2>
    </div>

    <div class="card" style="overflow:hidden;">
      <div class="rec-table-wrap">
      <table id="rec-table" style="width:100%;border-collapse:collapse;">
        <thead>
          <tr style="border-bottom:1px solid rgba(201,168,76,0.2);">
            <th class="col-date"    style="text-align:left;padding:12px 16px;font-family:'Space Mono',monospace;font-size:11px;letter-spacing:.1em;text-transform:uppercase;color:var(--gold);">Date</th>
            <th class="col-session" style="text-align:left;padding:12px 16px;font-family:'Space Mono',monospace;font-size:11px;letter-spacing:.1em;text-transform:uppercase;color:var(--gold);">Session</th>
            <th class="col-topic"   style="text-align:left;padding:12px 16px;font-family:'Space Mono',monospace;font-size:11px;letter-spacing:.1em;text-transform:uppercase;color:var(--gold);">Topic</th>
            <th class="col-votes"   style="text-align:left;padding:12px 16px;font-family:'Space Mono',monospace;font-size:11px;letter-spacing:.1em;text-transform:uppercase;color:var(--gold);">Votes</th>
            <th class="col-cast"    style="text-align:left;padding:12px 16px;font-family:'Space Mono',monospace;font-size:11px;letter-spacing:.1em;text-transform:uppercase;color:var(--gold);width:180px;">Cast Your Vote</th>
            <th class="col-result"  style="text-align:left;padding:12px 16px;font-family:'Space Mono',monospace;font-size:11px;letter-spacing:.1em;text-transform:uppercase;color:var(--gold);">Result</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($allRecords)): ?>
          <tr><td class="col-date" data-label="—" colspan="6" style="padding:32px;text-align:center;color:var(--text-muted);">No voting records available yet.</td></tr>
          <?php else: foreach ($allRecords as $vr):
            $vId     = (int)($vr['id'] ?? 0);
            $vDate   = formatDate(strval($vr['closed_at'] ?? $vr['opened_at'] ?? ''), 'M j, Y');
            $vType   = htmlspecialchars($vr['session_type'] ?? 'VOTE');
            $vTitle  = htmlspecialchars($vr['title']   ?? 'Untitled');
            $totalVoted  = $totVoted = (int)($vr['total_voted'] ?? 0);
            $eligible    = (int)($vr['total_eligible'] ?? 0);
            $approved    = [];
            try { $approved = $votingModel->getApprovedCounts($vId); } catch (Throwable $e) {}
            $yes         = $approved['yes']     ?? 0;
            $no          = $approved['no']      ?? 0;
            $abstain     = $approved['abstain'] ?? 0;
            $totVoted    = $approved['total']   ?? 0;
            $result      = htmlspecialchars(strval($vr['result'] ?? 'PENDING'));
            $status      = strval($vr['status'] ?? 'OPEN');
            $myChoice    = $votedOn[$vId] ?? null;
            $canVote     = in_array($status, ['OPEN', 'PENDING'], true);
            $voteCountDisplay = $eligible > 0 ? "$totVoted / $eligible" : ($totVoted ? "$totVoted" : '—');
            $resultC  = match($result) {
                'PASSED'  => 'var(--green-accent)',
                'REJECTED'=> 'var(--accent-red)',
                default   => 'var(--gold)',
            };
          ?>
          <tr style="border-bottom:1px solid rgba(255,255,255,.04);">
            <td class="col-date"  data-label="Date"   style="padding:16px;color:var(--text-muted);white-space:nowrap;"><?php echo $vDate; ?></td>
            <td class="col-session" data-label="Session" style="padding:16px;font-size:12px;font-family:'Space Mono',monospace;color:var(--gold-light);white-space:nowrap;"><?php echo $vType; ?></td>
            <td class="col-topic"  data-label="Topic"  style="padding:16px;white-space:normal;"><?php echo $vTitle; ?></td>
            <td class="col-votes"  data-label="Votes"  style="padding:16px;font-family:'Space Mono',monospace;font-size:12px;white-space:nowrap;">
              <span style="color:var(--gold);"><?php echo $voteCountDisplay; ?></span>
              <?php if ($votedOn): ?><span style="font-size:11px;color:var(--text-muted);">cast</span><?php endif; ?>
            </td>
            <td class="col-cast"   data-label="Your Vote" style="padding:16px;">
              <span class="vote-controls" data-voting-id="<?php echo $vId; ?>" data-device-id="<?php echo htmlspecialchars($deviceId); ?>">
              <?php if (!$canVote): ?>
                <span style="font-size:11px;color:var(--text-muted);">—</span>
              <?php elseif ($myChoice !== null): ?>
                <span style="display:inline-flex;align-items:center;gap:4px;padding:4px 10px;border-radius:4px;font-size:12px;font-family:'Space Mono',monospace;font-weight:600;
                  background:<?php echo $myChoice==='YES'?'rgba(55,180,120,.2)':(($myChoice==='NO')?'rgba(220,80,60,.2)':'rgba(201,168,76,.2)'); ?>;
                  color:<?php echo $myChoice==='YES'?'var(--green-accent)':(($myChoice==='NO')?'var(--accent-red)':'var(--gold)'); ?>
                ">✓ Voted <?php echo strtoupper($myChoice); ?></span>
              <?php else: ?>
                <button class="vote-btn vote-yes"  data-voting-id="<?php echo $vId; ?>" data-choice="YES">✓ YES</button>
                <button class="vote-btn vote-no"   data-voting-id="<?php echo $vId; ?>" data-choice="NO">✗ NO</button>
              <?php endif; ?>
              </span>
              <span class="vote-status-msg" id="vote-msg-<?php echo $vId; ?>" style="display:none;"></span>
            </td>
            <td class="col-result" data-label="Result" style="padding:16px;color:<?php echo $resultC; ?>;font-weight:600;white-space:nowrap;"><?php echo $result; ?></td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
      </div>
    </div>
  </div>
</section>

<div class="cinematic-divider"></div>

<!-- STATISTICAL SUMMARY SECTION -->
<section class="section" id="stats" style="background:var(--navy-mid);">
  <div class="container">
    <div class="section-header">
      <div class="section-eyebrow style="--content: '03';">Statistics</div>
      <h2 class="section-title">Session Overview</h2>
    </div>

    <div class="info-section">
      <div class="info-card">
        <div class="info-card-icon"><i class="bi bi-people"></i></div>
        <h4>Total Votes Cast</h4>
        <p style="font-size:36px;font-weight:700;color:var(--gold);"><?php echo number_format((int)(($votesStats['open'] + $votesStats['closed'] + $votesStats['passed'] + $votesStats['rejected']) ?: ($votesStats['total'] ?? 0))); ?></p>
      </div>
      <div class="info-card">
        <div class="info-card-icon"><i class="bi bi-calendar-check"></i></div>
        <h4>Voting Sessions</h4>
        <p style="font-size:36px;font-weight:700;color:var(--gold);"><?php echo (int)($votesStats['total'] ?? 0); ?></p>
      </div>
      <div class="info-card">
        <div class="info-card-icon"><i class="bi bi-check-circle"></i></div>
        <h4>Motions Passed</h4>
        <p style="font-size:36px;font-weight:700;color:var(--gold);"><?php echo (int)($votesStats['passed'] ?? 0); ?></p>
      </div>
      <div class="info-card">
        <div class="info-card-icon"><i class="bi bi-x-circle"></i></div>
        <h4>Motions Rejected</h4>
        <p style="font-size:36px;font-weight:700;color:var(--accent-red);"><?php echo (int)($votesStats['rejected'] ?? 0); ?></p>
      </div>
    </div>
  </div>
</section>



<?php include __DIR__ . '/include/footer.php'; ?>

</body>
</html>
