<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/functions.php';
require_once __DIR__ . '/models/Council.php';
require_once __DIR__ . '/models/SiteSettings.php';
require_once __DIR__ . '/models/Elections.php';

$db            = Database::getInstance();
$settingsModel = new SiteSettings($db);
$electionsModel = new Elections($db);
$councilModel  = new Council($db);

$sessionLabel = $settingsModel->getHeroSection()['session'] ?? date('Y') . '/' . ((int)date('Y') + 1);

// ── Electoral Commission Chairperson (SRC EC) ──
$ecChair       = $councilModel->getByPosition('SRC EC');
$deputyEC      = $councilModel->getByPosition('Deputy EC');

// ── Upcoming elections summary ──
$upcoming      = $electionsModel->getUpcoming();
$active        = $electionsModel->getActiveElection();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Electoral Commission — DHLTU SRC</title>
  <meta name="description" content="Meet the SRC Electoral Commission and Deputy EC. Contact the election administrators for DHLTU student elections.">
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
     HERRO
═══════════════════════════════════════════════════ -->
<section class="constitution-header">
  <div class="constitution-content">
    <div class="section-eyebrow">Elections · SRC <?php echo htmlspecialchars($sessionLabel); ?></div>
    <h1 class="constitution-title">Electoral <em style="color:var(--gold-light);">Commission</em></h1>
    <p class="constitution-subtitle">Executor of Free, Fair &amp; Transparent Student Elections</p>
    <div class="constitution-meta">
      <div class="meta-item">
        <i class="bi bi-flag"></i>
        <span>SRC Constitution, Article VII</span>
      </div>
      <div class="meta-item">
        <i class="bi bi-calendar-event"></i>
        <span>Session <?php echo htmlspecialchars($sessionLabel); ?></span>
      </div>
      <div class="meta-item">
        <i class="bi bi-building"></i>
        <span>Dr. Hilla Limann Technical University</span>
      </div>
    </div>
  </div>
</section>

<div class="cinematic-divider"></div>

<!-- ═══════════════════════════════════════════════════
     MANDATE &amp; OVERSIGHT
═══════════════════════════════════════════════════ -->
<section class="section" id="mandate">
  <div class="container">
    <div class="section-header">
      <div class="section-eyebrow" style="--content:'01';">About the Commission</div>
      <h2 class="section-title">Mandate &amp; <em>Oversight</em></h2>
    </div>
    <div class="info-section" style="max-width:950px;margin:60px auto 0;display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:24px;">

      <div class="info-card reveal delay-1">
        <div class="info-card-icon"><i class="bi bi-book-half"></i></div>
        <h4>Constitutional Mandate</h4>
        <p>Article VII of the SRC Constitution establishes the Electoral Commission as the independent statutory body responsible for administering all SRC elections — electoral, faculty, and hall representative positions.</p>
      </div>

      <div class="info-card reveal delay-2">
        <div class="info-card-icon"><i class="bi bi-shield-lock"></i></div>
        <h4>Independent &amp; Non-Partisan</h4>
        <p>Members of the Electoral Commission must resign from any partisan political position before their appointment. No candidate may hold office in the Commission during an election cycle in which they are running.</p>
      </div>

      <div class="info-card reveal delay-3">
        <div class="info-card-icon"><i class="bi bi-diagram-3"></i></div>
        <h4>Composition</h4>
        <p>The Commission consists of a Chairperson, a Deputy Chairperson, and two additional members appointed by the SRC President and confirmed by the General Assembly.</p>
      </div>

      <div class="info-card reveal delay-4">
        <div class="info-card-icon"><i class="bi bi-gavel"></i></div>
        <h4>Judicial Powers</h4>
        <p>The Commission adjudicates election disputes, issues binding rulings on eligibility challenges, and may disqualify candidates who breach the election rules or code of conduct.</p>
      </div>

      <div class="info-card reveal delay-5">
        <div class="info-card-icon"><i class="bi bi-journal-text"></i></div>
        <h4>Electoral Roll Management</h4>
        <p>The Commission compiles, maintains, and publishes the official voter register. Any corrections or omissions must be adjudicated within 3 working days of publication.</p>
      </div>

      <div class="info-card reveal delay-6">
        <div class="info-card-icon"><i class="bi bi-pencil-square"></i></div>
        <h4>Candidate Vetting</h4>
        <p>All nomination forms are scrutinised before the candidate list is declared. Grounds for disqualification include: active academic probation, outstanding dues, or prior election law violations.</p>
      </div>

    </div>
  </div>
</section>

<div class="cinematic-divider"></div>

<!-- ═══════════════════════════════════════════════════
     ELECTION RESPONSIBILITIES
═══════════════════════════════════════════════════ -->
<section class="section" style="background:var(--navy-mid);">
  <div class="container">
    <div class="section-header">
      <div class="section-eyebrow" style="--content:'02';">What the EC Does</div>
      <h2 class="section-title">Key <em>Responsibilities</em></h2>
    </div>
    <div class="election-steps" style="margin-top:60px;max-width:820px;margin-left:auto;margin-right:auto;">

      <div class="election-step reveal">
        <div class="step-num"><i class="bi bi-calendar-check" style="font-size:20px;"></i></div>
        <div class="step-content">
          <div class="step-title">Publish the Election Calendar</div>
          <div class="step-desc">Announce all key dates — nomination opening, campaigning period, voting window, and result declaration — at least 14 days before each election cycle begins.</div>
        </div>
      </div>

      <div class="election-step reveal">
        <div class="step-num"><i class="bi bi-list-check" style="font-size:20px;"></i></div>
        <div class="step-content">
          <div class="step-title">Voter Registration &amp; Register Publication</div>
          <div class="step-desc">Compile the official voter list from university registrar records and publish it for public inspection. Resolve eligibility disputes within 3 working days of appeal.</div>
        </div>
      </div>

      <div class="election-step reveal">
        <div class="step-num"><i class="bi bi-person-check" style="font-size:20px;"></i></div>
        <div class="step-content">
          <div class="step-title">Candidate Nomination &amp; Vetting</div>
          <div class="step-desc">Receive, verify, and adjudicate nomination forms. Publish the definitive list of eligible candidates and reject disqualifying submissions with formal written reasons.</div>
        </div>
      </div>

      <div class="election-step reveal">
        <div class="step-num"><i class="bi bi-megaphone" style="font-size:20px;"></i></div>
        <div class="step-content">
          <div class="step-title">Campaign Period Management</div>
          <div class="step-desc">Issue the Campaign Code of Conduct, monitor adherence, adjudicate campaign violations, and level penalties — including cautions, public warnings, or disqualification.</div>
        </div>
      </div>

      <div class="election-step reveal">
        <div class="step-num"><i class="bi bi-box-seam" style="font-size:20px;"></i></div>
        <div class="step-content">
          <div class="step-title">Secure Ballot Configuration</div>
          <div class="step-desc">Acquire and verify the digital ballot system, configure positions, candidate ordering, and ballot papers before the secure pre-seal date.</div>
        </div>
      </div>

      <div class="election-step reveal">
        <div class="step-num"><i class="bi bi-bar-chart" style="font-size:20px;"></i></div>
        <div class="step-content">
          <div class="step-title">Result Compilation &amp; Declaration</div>
          <div class="step-desc">Supervise vote counting, tally results, and declare the outcome within 48 hours of the polls closing. All results are certified before publication on the SRC platform.</div>
        </div>
      </div>

    </div>
  </div>
</section>

<div class="cinematic-divider"></div>

<!-- ═══════════════════════════════════════════════════
     ELECTORAL COMMISSION CHAIR
═══════════════════════════════════════════════════ -->
<section class="section" id="commissioner">
  <div class="container">
    <div class="section-header">
      <div class="section-eyebrow" style="--content:'03';">Your EC</div>
      <h2 class="section-title">Meet the <em>Electoral Commission</em></h2>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:32px;max-width:900px;margin:60px auto 0;">

      <!-- Chairperson -->
      <div class="info-card reveal delay-1" style="text-align:center;padding:40px 32px;background:var(--navy-mid);border:1px solid rgba(201,168,76,0.12);border-radius:4px;transition:all 0.5s cubic-bezier(0.16,1,0.3,1);position:relative;overflow:hidden;"
        onmouseover="this.style.borderColor='rgba(201,168,76,0.35)';this.style.transform='translateY(-4px)'"
        onmouseout="this.style.borderColor='rgba(201,168,76,0.12)';this.style.transform='translateY(0)'">
        <div style="position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,transparent,var(--gold),transparent);"></div>
        <div style="width:96px;height:96px;border-radius:50%;background:linear-gradient(135deg,rgba(201,168,76,0.15),rgba(201,168,76,0.05));border:1px solid rgba(201,168,76,0.25);display:flex;align-items:center;justify-content:center;margin:0 auto 20px;font-family:'Cormorant Garamond',serif;font-size:32px;font-weight:700;color:var(--gold);">
          <?php
            if ($ecChair) {
              $initials = strtoupper(substr($ecChair['first_name'], 0, 1) . substr($ecChair['last_name'], 0, 1));
              echo $initials;
            } else {
              echo 'EC';
            }
          ?>
        </div>
        <div style="font-family:'Space Mono',monospace;font-size:9px;letter-spacing:0.2em;text-transform:uppercase;color:var(--gold);margin-bottom:8px;">
          Chairperson
        </div>
        <h4 style="font-size:20px;font-weight:600;color:var(--cream);margin-bottom:4px;letter-spacing:0.02em;">
          <?php echo $ecChair
            ? htmlspecialchars($ecChair['first_name'] . ' ' . $ecChair['last_name'])
            : 'To Be Announced'; ?>
        </h4>
        <div style="font-size:12px;color:var(--text-muted);margin-bottom:20px;">
          Electoral Commission Chair
        </div>
        <?php if ($ecChair): ?>
        <a href="mailto:<?php echo htmlspecialchars($ecChair['email']); ?>" style="display:inline-flex;align-items:center;gap:8px;font-size:12px;color:var(--gold);transition:gap 0.3s ease;" onmouseover="this.style.gap='14px'" onmouseout="this.style.gap='8px'">
          <i class="bi bi-envelope"></i> <?php echo htmlspecialchars($ecChair['email']); ?>
        </a>
        <div style="margin-top:12px;font-size:11px;color:var(--text-muted);">
          <?php echo !empty($ecChair['phone']) ? '<i class="bi bi-telephone" style="color:var(--gold);font-size:12px;"></i> ' . htmlspecialchars($ecChair['phone']) : ''; ?>
        </div>
        <?php endif; ?>
      </div>

      <!-- Deputy EC -->
      <div class="info-card reveal delay-2" style="text-align:center;padding:40px 32px;background:var(--navy-mid);border:1px solid rgba(201,168,76,0.12);border-radius:4px;transition:all 0.5s cubic-bezier(0.16,1,0.3,1);position:relative;overflow:hidden;"
        onmouseover="this.style.borderColor='rgba(201,168,76,0.35)';this.style.transform='translateY(-4px)'"
        onmouseout="this.style.borderColor='rgba(201,168,76,0.12)';this.style.transform='translateY(0)'">
        <div style="position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,transparent,var(--gold),transparent);"></div>
        <div style="width:96px;height:96px;border-radius:50%;background:linear-gradient(135deg,rgba(201,168,76,0.15),rgba(201,168,76,0.05));border:1px solid rgba(201,168,76,0.25);display:flex;align-items:center;justify-content:center;margin:0 auto 20px;font-family:'Cormorant Garamond',serif;font-size:32px;font-weight:700;color:var(--gold);">
          <?php
            if ($deputyEC) {
              $initials = strtoupper(substr($deputyEC['first_name'], 0, 1) . substr($deputyEC['last_name'], 0, 1));
              echo $initials;
            } else {
              echo 'EC';
            }
          ?>
        </div>
        <div style="font-family:'Space Mono',monospace;font-size:9px;letter-spacing:0.2em;text-transform:uppercase;color:var(--gold);margin-bottom:8px;">
          Deputy Chairperson
        </div>
        <h4 style="font-size:20px;font-weight:600;color:var(--cream);margin-bottom:4px;letter-spacing:0.02em;">
          <?php echo $deputyEC
            ? htmlspecialchars($deputyEC['first_name'] . ' ' . $deputyEC['last_name'])
            : 'To Be Announced'; ?>
        </h4>
        <div style="font-size:12px;color:var(--text-muted);margin-bottom:20px;">
          Deputy Electoral Commission
        </div>
        <?php if ($deputyEC): ?>
        <a href="mailto:<?php echo htmlspecialchars($deputyEC['email']); ?>" style="display:inline-flex;align-items:center;gap:8px;font-size:12px;color:var(--gold);transition:gap 0.3s ease;" onmouseover="this.style.gap='14px'" onmouseout="this.style.gap='8px'">
          <i class="bi bi-envelope"></i> <?php echo htmlspecialchars($deputyEC['email']); ?>
        </a>
        <div style="margin-top:12px;font-size:11px;color:var(--text-muted);">
          <?php echo !empty($deputyEC['phone']) ? '<i class="bi bi-telephone" style="color:var(--gold);font-size:12px;"></i> ' . htmlspecialchars($deputyEC['phone']) : ''; ?>
        </div>
        <?php endif; ?>
      </div>

    </div>

    <!-- Contact the full EC via SRC email -->
    <div style="text-align:center;margin-top:64px;">
      <p style="font-size:15px;font-weight:300;color:var(--text-muted);margin-bottom:24px;">
        Have an election question, eligibility concern, or nomination query?
      </p>
      <div style="display:flex;gap:16px;justify-content:center;flex-wrap:wrap;">
        <a href="mailto:src@hltu.edu.gh?subject=Electoral%20Commission%20Enquiry" class="btn-primary">
          Email the EC
        </a>
        <a href="index.php#contact" class="btn-outline">General Contact</a>
      </div>
    </div>

  </div>
</section>

<div class="cinematic-divider"></div>

<!-- ═══════════════════════════════════════════════════
     ELECTION CODE OF CONDUCT
═══════════════════════════════════════════════════ -->
<section class="section" style="background:var(--navy-mid);">
  <div class="container">
    <div class="section-header">
      <div class="section-eyebrow" style="--content:'04';">Rules &amp; Regulations</div>
      <h2 class="section-title">Election Code of <em>Conduct</em></h2>
    </div>
    <div class="info-section" style="max-width:900px;margin:60px auto 0;display:flex;flex-direction:column;gap:16px;">

      <div style="display:flex;gap:20px;align-items:flex-start;padding:24px 28px;background:var(--navy);border:1px solid rgba(201,168,76,0.08);border-radius:4px;transition:border-color 0.3s ease;" class="reveal delay-1" onmouseover="this.style.borderColor='rgba(201,168,76,0.2)'" onmouseout="this.style.borderColor='rgba(201,168,76,0.08)'">
        <div style="min-width:36px;height:36px;border-radius:50%;background:rgba(201,168,76,0.1);border:1px solid rgba(201,168,76,0.2);display:flex;align-items:center;justify-content:center;color:var(--gold);font-size:16px;font-weight:700;flex-shrink:0;">1</div>
        <div>
          <div style="font-size:15px;font-weight:600;color:var(--cream);margin-bottom:8px;">No Academic or Administrative Interference</div>
          <div style="font-size:13px;color:var(--text-muted);line-height:1.8;">University faculties and administration must remain strictly neutral. No staff member may endorse, canvass for, or assist any candidate during the election period.</div>
        </div>
      </div>

      <div style="display:flex;gap:20px;align-items:flex-start;padding:24px 28px;background:var(--navy);border:1px solid rgba(201,168,76,0.08);border-radius:4px;transition:border-color 0.3s ease;" class="reveal delay-2" onmouseover="this.style.borderColor='rgba(201,168,76,0.2)'" onmouseout="this.style.borderColor='rgba(201,168,76,0.08)'">
        <div style="min-width:36px;height:36px;border-radius:50%;background:rgba(201,168,76,0.1);border:1px solid rgba(201,168,76,0.2);display:flex;align-items:center;justify-content:center;color:var(--gold);font-size:16px;font-weight:700;flex-shrink:0;">2</div>
        <div>
          <div style="font-size:15px;font-weight:600;color:var(--cream);margin-bottom:8px;">Campaign Spending Cap</div>
          <div style="font-size:13px;color:var(--text-muted);line-height:1.8;">Candidates may not spend more than <strong style="color:var(--gold);">GH₵ 500.00</strong> on their campaign. All expenses must be recorded and submitted to the Electoral Commission within 48 hours after the polls close.</div>
        </div>
      </div>

      <div style="display:flex;gap:20px;align-items:flex-start;padding:24px 28px;background:var(--navy);border:1px solid rgba(201,168,76,0.08);border-radius:4px;transition:border-color 0.3s ease;" class="reveal delay-3" onmouseover="this.style.borderColor='rgba(201,168,76,0.2)'" onmouseout="this.style.borderColor='rgba(201,168,76,0.08)'">
        <div style="min-width:36px;height:36px;border-radius:50%;background:rgba(201,168,76,0.1);border:1px solid rgba(201,168,76,0.2);display:flex;align-items:center;justify-content:center;color:var(--gold);font-size:16px;font-weight:700;flex-shrink:0;">3</div>
        <div>
          <div style="font-size:15px;font-weight:600;color:var(--cream);margin-bottom:8px;">One-Vote-Per-Student</div>
          <div style="font-size:13px;color:var(--text-muted);line-height:1.8;">Each eligible student has exactly one vote. Multiple voting, vote-buying, and ballot stuffing are criminal offences under the SRC Constitution and will be reported to university authorities immediately.</div>
        </div>
      </div>

      <div style="display:flex;gap:20px;align-items:flex-start;padding:24px 28px;background:var(--navy);border:1px solid rgba(201,168,76,0.08);border-radius:4px;transition:border-color 0.3s ease;" class="reveal delay-4" onmouseover="this.style.borderColor='rgba(201,168,76,0.2)'" onmouseout="this.style.borderColor='rgba(201,168,76,0.08)'">
        <div style="min-width:36px;height:36px;border-radius:50%;background:rgba(201,168,76,0.1);border:1px solid rgba(201,168,76,0.2);display:flex;align-items:center;justify-content:center;color:var(--gold);font-size:16px;font-weight:700;flex-shrink:0;">4</div>
        <div>
          <div style="font-size:15px;font-weight:600;color:var(--cream);margin-bottom:8px;">No Electronic Vote Interference</div>
          <div style="font-size:13px;color:var(--text-muted);line-height:1.8;">Unauthorised access to the voting portal, vote manipulation software, or social media impersonation of the Electoral Commission carries a lifetime campaigning ban.</div>
        </div>
      </div>

      <div style="display:flex;gap:20px;align-items:flex-start;padding:24px 28px;background:var(--navy);border:1px solid rgba(201,168,76,0.08);border-radius:4px;transition:border-color 0.3s ease;" class="reveal delay-5" onmouseover="this.style.borderColor='rgba(201,168,76,0.2)'" onmouseout="this.style.borderColor='rgba(201,168,76,0.08)'">
        <div style="min-width:36px;height:36px;border-radius:50%;background:rgba(201,168,76,0.1);border:1px solid rgba(201,168,76,0.2);display:flex;align-items:center;justify-content:center;color:var(--gold);font-size:16px;font-weight:700;flex-shrink:0;">5</div>
        <div>
          <div style="font-size:15px;font-weight:600;color:var(--cream);margin-bottom:8px;">Objections &amp; Recounts</div>
          <div style="font-size:13px;color:var(--text-muted);line-height:1.8;">Any candidate or registered voter may file a written objection within <strong style="color:var(--gold);">5 working days</strong> of result declaration. The Electoral Commission will investigate and report within 7 further days. A recount requires a petition signed by at least 200 eligible voters.</div>
        </div>
      </div>

    </div>
  </div>
</section>

<div class="cinematic-divider"></div>

<!-- ═══════════════════════════════════════════════════
     ELECTION TIMELINE GUIDE
═══════════════════════════════════════════════════ -->
<section class="section" style="background:var(--navy);">
  <div class="container">
    <div class="section-header">
      <div class="section-eyebrow" style="--content:'05';">At a Glance</div>
      <h2 class="section-title">Election <em>Timeline</em></h2>
    </div>

    <div style="max-width:800px;margin:60px auto 0;">
      <?php
        $timeline = [
          ['day' => 'Day -21', 'label' => 'Notice of Election', 'body' => 'Electoral Commission publishes the formal Notice of Election in campus bulletin, website, and faculty notice boards.'],
          ['day' => 'Day -14', 'label' => 'Nominations Open', 'body' => 'Nomination forms become available at the SRC Secretariat. Candidates must submit forms directly to the EC Chairperson before the closing deadline.'],
          ['day' => 'Day -10', 'label' => 'Voter Register Released', 'body' => 'The EC publishes the provisional voter list online and in physical locations. Eligible students have 3 days to lodge corrections or objections in writing.'],
          ['day' => 'Day -07', 'label' => 'Candidates Verified', 'body' => 'Final list of verified candidates is published. All candidates receive their official campaign materials and voter-codes.'],
          ['day' => 'Day -05', 'label' => 'Campaigning Opens', 'body' => 'The formal campaign period begins. All campaign materials must be approved by the EC before placement on campus or social media.'],
          ['day' => 'Day 00',  'label' => 'Voting Opens', 'body' => 'Students log in to the SRC voting portal using their institutional email and unique voter code. Polls close at precisely midnight on the final voting day.'],
          ['day' => 'Day +02', 'label' => 'Results Declared', 'body' => 'The EC certifies and publishes the official results on the SRC website, campus notice boards, and social media within 48 hours of poll close.'],
          ['day' => 'Day +05', 'label' => 'Appeal Window Closes', 'body' => 'All election petitions must be received by the EC in writing. Unresolved petitions are forwarded to the Student Judiciary within 3 working days.'],
        ];
      ?>

      <?php foreach ($timeline as $i => $row): ?>
      <div class="reveal <?php echo 'delay-' . ($i + 1); ?>" style="display:grid;grid-template-columns:90px 1fr;gap:24px;align-items:start;margin-bottom:0;">
        <div style="padding:16px 0;text-align:right;border-right:1px solid rgba(201,168,76,0.15);padding-right:24px;">
          <div style="font-family:'Cormorant Garamond',serif;font-size:20px;font-weight:700;color:var(--gold-light);"><?php echo $row['day']; ?></div>
        </div>
        <div style="padding:14px 0 28px 8px;">
          <div style="font-size:15px;font-weight:600;color:var(--cream);margin-bottom:6px;"><?php echo $row['label']; ?></div>
          <div style="font-size:13px;color:var(--text-muted);line-height:1.7;"><?php echo $row['body']; ?></div>
        </div>
      </div>
      <?php endforeach; ?>

    </div>
  </div>
</section>

<div class="cinematic-divider"></div>

<!-- ═══════════════════════════════════════════════════
     CTA
═══════════════════════════════════════════════════ -->
<section class="section" id="ec-cta" style="background:var(--navy-mid);">
  <div style="max-width:700px;margin:0 auto;text-align:center;">
    <h2 style="font-family:'Cormorant Garamond',serif;font-size:clamp(32px,4vw,52px);font-weight:300;line-height:1.1;color:var(--cream);margin-bottom:20px;">
      Have an Election <em style="color:var(--gold-light);">Question?</em>
    </h2>
    <p style="font-size:15px;font-weight:300;line-height:1.9;color:rgba(245,240,232,0.65);max-width:480px;margin:0 auto 48px;">
      Reach out to the Electoral Commission directly, or explore all upcoming elections and key dates.
    </p>
    <div style="display:flex;gap:16px;justify-content:center;flex-wrap:wrap;">
      <a href="mailto:src@hltu.edu.gh?subject=Electoral%20Commission%20Enquiry" class="btn-primary">
        <i class="bi bi-envelope"></i> Email EC
      </a>
      <a href="upcoming-elections.php" class="btn-outline">
        <i class="bi bi-calendar3"></i> View Upcoming Elections
      </a>
    </div>
  </div>
</section>

<?php include 'include/footer.php'; ?>

<!-- Scroll Reveal -->
<script>
  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.reveal').forEach(function (el) {
      el.style.opacity = '0';
      el.style.transform = 'translateY(40px)';
      el.style.transition = 'opacity 0.9s cubic-bezier(0.16,1,0.3,1), transform 0.9s cubic-bezier(0.16,1,0.3,1)';
    });

    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          var el = entry.target;
          var delay = 0;
          if (el.classList.contains('delay-1')) delay = 150;
          else if (el.classList.contains('delay-2')) delay = 300;
          else if (el.classList.contains('delay-3')) delay = 450;
          else if (el.classList.contains('delay-4')) delay = 600;
          else if (el.classList.contains('delay-5')) delay = 750;
          else if (el.classList.contains('delay-6')) delay = 900;
          setTimeout(function () {
            el.style.opacity = '1';
            el.style.transform = 'translateY(0)';
          }, delay);
          observer.unobserve(el);
        }
      });
    }, { threshold: 0.1 });

    document.querySelectorAll('.reveal').forEach(function (el) { observer.observe(el); });
  });
 </script>
 <script>
  // Mobile menu toggle (outside DOMContentLoaded for immediate execution)
  var mobileToggle = document.querySelector('.mobile-toggle');
  var navList = document.querySelector('.nav-list');
  
  if (mobileToggle && window.innerWidth <= 900) {
    mobileToggle.style.display = 'flex';
  }
  
  if (mobileToggle && navList) {
    mobileToggle.addEventListener('click', function () {
      mobileToggle.classList.toggle('active');
      navList.classList.toggle('active');
    });
  }
  
  // Close mobile menu when clicking outside
  document.addEventListener('click', function (e) {
    if (mobileToggle && navList && !mobileToggle.contains(e.target) && !navList.contains(e.target)) {
      mobileToggle.classList.remove('active');
      navList.classList.remove('active');
    }
  });
  
  // Nav link dropdown toggle on mobile
  document.querySelectorAll('.nav-link').forEach(function (link) {
    link.addEventListener('click', function (e) {
      var navItem = link.parentElement;
      var dropdown = navItem ? navItem.querySelector('.dropdown') : null;
      
      if (dropdown && window.innerWidth <= 768) {
        e.preventDefault();
        document.querySelectorAll('.dropdown.open').forEach(function (d) {
          if (d !== dropdown) {
            d.classList.remove('open');
            d.closest('.nav-item')?.classList.remove('open');
          }
        });
        dropdown.classList.toggle('open');
        navItem?.classList.toggle('open');
      } else {
        if (mobileToggle && navList) {
          mobileToggle.classList.remove('active');
          navList.classList.remove('active');
          document.querySelectorAll('.dropdown.open').forEach(function (d) { d.classList.remove('open'); });
          document.querySelectorAll('.nav-item.open').forEach(function (n) { n.classList.remove('open'); });
        }
      }
    });
  });
 </script>

</body>
</html>
