<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/functions.php';

$trackToken   = '';
$typeTab      = '';
$typeLabel    = '';
$resultHTML   = '';
$tracked      = false;
$complainHtml = '';
$docHtml      = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['trackToken'])) {
    $trackToken = strtoupper(trim($_POST['trackToken'] ?? ''));
    if ($trackToken !== '') {

        // ── Complaints ────────────────────────────────────
        $complaint = db()->fetch(
            "SELECT subject, status, category, priority, created_at, description, resolution, resolved_at, is_blocked, block_reason
             FROM complaints WHERE complaint_token = ?",
            [$trackToken]
        );

        if ($complaint) {
            $tracked = true;
            $stMap = [
                'OPEN'        => ['color' => 'var(--gold)',        'dot' => 'W', 'label' => 'Open'],
                'IN_PROGRESS' => ['color' => 'var(--gold-light)',  'dot' => 'W', 'label' => 'In Progress'],
                'RESOLVED'    => ['color' => 'var(--green-accent)', 'dot' => '✓', 'label' => 'Resolved'],
                'CLOSED'      => ['color' => 'var(--text-muted)',  'dot' => '✕', 'label' => 'Closed'],
            ];
            $s    = $stMap[$complaint['status']] ?? ['color' => 'var(--text-muted)', 'dot' => '?', 'label' => $complaint['status']];
            $prioMap = ['LOW' => 'var(--green-accent)', 'MEDIUM' => 'var(--gold)', 'HIGH' => 'var(--gold-light)', 'URGENT' => 'var(--accent-red)'];
            $cmpType = 'complaint';
            $typeTab = 'complaint';
        }

        // ── Document requests ─────────────────────────────
        if (!$complaint) {
$req = db()->fetch(
                "SELECT dr.*, u.first_name, u.last_name, u.student_id, u.email
                 FROM document_requests dr
                 LEFT JOIN users u ON dr.user_id = u.id
                 WHERE dr.request_token = ?",
                [$trackToken]
            );

            if ($req) {
                $tracked = true;
                $sMap = [
                    'PENDING'    => ['color' => 'var(--gold)',        'dot' => 'W', 'label' => 'Pending',   'step' => 0],
                    'PROCESSING' => ['color' => 'var(--gold-light)',  'dot' => '◷', 'label' => 'Processing','step' => 1],
                    'READY'      => ['color' => 'var(--green-accent)', 'dot' => '✓', 'label' => 'Ready for Collection', 'step' => 2],
                    'COLLECTED'  => ['color' => 'var(--green-accent)', 'dot' => '✓', 'label' => 'Collected',  'step' => 3],
                    'REJECTED'   => ['color' => 'var(--accent-red)',  'dot' => '✕', 'label' => 'Rejected',   'step' => -1],
                ];
                $s   = $sMap[$req['status']] ?? ['color' => 'var(--text-muted)', 'dot' => '?', 'label' => $req['status'], 'step' => 0];
                $cmpType = 'document';
                $typeTab = 'document';
            }
        }
    }
}

function statusBadgeEl($stMap, $status) {
    $s   = $stMap[$status] ?? ['color' => 'var(--text-muted)', 'dot' => '?', 'label' => $status];
    return '<span style="display:inline-flex;align-items:center;gap:6px;padding:5px 14px;border-radius:99px;font-size:13px;font-weight:600;background:'
         . ($s['color']==='var(--gold)'          ? 'rgba(201,168,76,0.12)'
           : ($s['color']==='var(--gold-light)'  ? 'rgba(201,168,76,0.18)'
           : ($s['color']==='var(--green-accent)' ? 'rgba(34,197,94,0.12)'
           : ($s['color']==='var(--accent-red)'  ? 'rgba(239,68,68,0.12)'
           : 'rgba(0,0,0,0.05)'))))
         . ';color:' . $s['color'] . ';border:1px solid ' . $s['color'] . '35;">'
         . '<span style="width:24px;height:24px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;background:'
         . ($s['color']==='var(--accent-red)' ? 'rgba(239,68,68,0.12)' : ($s['color']==='var(--green-accent)' ? 'rgba(34,197,94,0.12)' : 'rgba(201,168,76,0.12)'))
         . ';">' . $s['dot'] . '</span>'
         . '<span>' . $s['label'] . '</span></span>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Submission — DHLTU SRC Student Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;0,700;1,300;1,400&family=Outfit:wght@200;300;400;500;600;700&family=Space+Mono:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/main.css">
<link rel="icon" type="image/png" href="assets/images/logo.png">
</head>
<body>
<?php include __DIR__ . '/include/header.php'; ?>

<!-- ══════════════════════════════════════════════════════
     TRACKING HERO
══════════════════════════════════════════════════════ -->
<section class="track-hero">
    <div class="track-hero-content">
        <div class="complaint-hero-eyebrow">Student Portal &middot; Track</div>
        <h1 class="complaint-hero-title">Track Your <em>Submission</em></h1>
        <p class="complaint-hero-desc">
            Enter your tracking number to check the status and progress of your complaint
            or document request in real time.
        </p>
    </div>
</section>

<div class="cinematic-divider"></div>

<!-- ══════════════════════════════════════════════════════
     TRACKING FORM
══════════════════════════════════════════════════════ -->
<section class="track-section">
    <div class="container">

        <!-- Type tabs -->
        <div style="display:flex;justify-content:center;gap:6px;margin-bottom:28px;flex-wrap:wrap;">
            <span class="track-tab <?php echo (!$typeTab || $typeTab==='complaint') ? 'track-tab-active' : ''; ?>"
                  style="<?php echo (!$typeTab || $typeTab==='complaint') ? 'border-color:var(--gold);background:rgba(201,168,76,0.1);color:var(--gold);' : '' ?>">
                <i class="bi bi-clipboard"></i> Complaint Tracking
            </span>
        </div>

        <div class="track-form-card active">

            <!-- Hint -->
            <div style="display:flex;gap:8px;align-items:flex-start;margin-bottom:20px;padding:14px 16px;background:rgba(201,168,76,0.05);border-radius:10px;border:1px solid rgba(201,168,76,0.12);">
                <i class="bi bi-info-circle" style="color:var(--gold);margin-top:2px;font-size:16px;"></i>
                <div style="font-size:14px;color:var(--text-secondary);line-height:1.55;">
                    Both <strong>complaints</strong> and <strong>document requests</strong> use the same tracking form.
                    Your token starts with <code style="font-size:12px;background:rgba(201,168,76,0.12);padding:1px 6px;border-radius:4px;color:var(--gold);">TKN-</code>
                    for complaints and <code style="font-size:12px;background:rgba(201,168,76,0.12);padding:1px 6px;border-radius:4px;color:var(--gold);">REQ-</code>
                    for document requests.
                    <span style="display:block;font-size:12px;margin-top:4px;color:var(--text-muted);">e.g. <code style="font-size:11px;color:var(--gold);">TKN-A1B2C3D4E5F</code> or <code style="font-size:11px;color:var(--gold);">REQ-XYZ789012</code></span>
                </div>
            </div>

            <form method="POST" action="" novalidate>
                <div style="display:flex;gap:12px;flex-wrap:wrap;">
                    <div class="form-group" style="flex:1;min-width:260px;margin:0;">
                        <label for="trackToken" style="display:block;font-size:13px;font-weight:500;margin-bottom:6px;color:var(--text-secondary);">
                            Tracking Token
                        </label>
                        <input type="text"
                               id="trackToken"
                               name="trackToken"
                               class="track-input"
                               placeholder="e.g. TKN-A1B2C3D4E5F"
                               value="<?php echo htmlspecialchars($trackToken); ?>"
                               required
                               autocomplete="off"
                               spellcheck="false"
                               autofocus>
                    </div>
                    <button type="submit" class="btn-submit" style="min-width:140px;align-self:flex-end;margin-bottom:2px;" id="trackBtn">
                        <i class="bi bi-search"></i> Track
                    </button>
                </div>
            </form>
        </div>

        <!-- ── RESULTS ────────────────────────────────────── -->
        <?php if ($tracked): ?>

            <!-- ══ COMPLAINT RESULT ══════════════════════════ -->
            <?php if (($typeTab ?? '') === 'complaint'): ?>
                <div class="track-result-card">
                    <div class="track-result-header">
                        <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                            <code style="font-size:13px;background:rgba(201,168,76,0.12);padding:5px 10px;border-radius:8px;color:var(--gold);font-weight:600;">
                                <i class="bi bi-clipboard"></i> <?php echo htmlspecialchars($trackToken); ?>
                            </code>
                            <span style="font-size:12px;color:var(--text-muted);">Complaint</span>
                        </div>
                        <?php echo statusBadgeEl($stMap, $complaint['status']); ?>
                    </div>

                    <?php if (!empty($complaint['is_blocked'])): ?>
                    <div style="padding:20px 22px;background:rgba(239,68,68,0.06);border:1px solid rgba(239,68,68,0.25);border-radius:12px;margin-bottom:22px;display:flex;gap:14px;align-items:flex-start;">
                        <div style="width:42px;height:42px;border-radius:50%;background:rgba(239,68,68,0.12);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="bi bi-shield-slash-fill" style="color:var(--accent-red);font-size:20px;"></i>
                        </div>
                        <div>
                            <div style="font-weight:700;color:var(--accent-red);font-size:15px;margin-bottom:4px;">This Complaint Has Been Blocked</div>
                            <div style="font-size:13px;color:var(--text-secondary);line-height:1.6;"><?php echo htmlspecialchars(urldecode($complaint['block_reason'] ?? 'No reason provided.')); ?></div>
                            <div style="font-size:11px;color:var(--text-muted);margin-top:6px;">You can no longer update or track this submission.</div>
                        </div>
                    </div>
                    <?php else: ?>

                    <!-- Complaints 4-step lifecycle -->
                    <div class="track-lifecycle">
                        <?php
                            $lifecycle = [
                                ['label' => 'Submitted', 'icon' => 'bi-send',   'desc'  => 'Grievance received by the SRC Secretariat'],
                                ['label' => 'In Progress', 'icon' => 'bi-gear', 'desc'  => 'Under investigation by assigned officer'],
                                ['label' => 'Resolved', 'icon' => 'bi-check-circle', 'desc' => 'Issue has been addressed'],
                                ['label' => 'Closed',    'icon' => 'bi-box-seam','desc'  => 'Case officially closed'],
                            ];
                            $stMap2 = ['OPEN' => 0, 'IN_PROGRESS' => 1, 'RESOLVED' => 2, 'CLOSED' => 3];
                            $activeStep = $stMap2[$complaint['status']] ?? 0;
                            foreach ($lifecycle as $i => $step):
                        ?>
                            <div>
                                <div class="lc-step <?php echo $i <= $activeStep ? 'lc-active' : ($i===$activeStep+1 ? 'lc-next' : 'lc-inactive'); ?>">
                                    <div class="lc-step-icon <?php echo $i <= $activeStep ? 'lc-done' : ''; ?>">
                                        <i class="bi <?php echo $step['icon']; ?>"></i>
                                    </div>
                                    <div class="lc-step-label"><?php echo $step['label']; ?></div>
                                </div>
                                <?php if ($i < count($lifecycle)-1): ?><div class="lc-line <?php echo $activeStep > $i ? 'lc-line-done' : ''; ?>"></div><?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Metadata cards -->
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;margin-bottom:22px;">
                        <div class="tc-meta">
                            <div class="tc-meta-label">Subject</div>
                            <div class="tc-meta-value"><?php echo htmlspecialchars($complaint['subject']); ?></div>
                        </div>
                        <div class="tc-meta">
                            <div class="tc-meta-label">Category</div>
                            <div><span class="badge badge-role"><?php echo htmlspecialchars($complaint['category'] ?? "—"); ?></span></div>
                        </div>
                        <div class="tc-meta">
                            <div class="tc-meta-label">Priority</div>
                            <div style="color:<?php
                                echo $complaint['priority']==='URGENT'?'var(--accent-red)':
                                    ($complaint['priority']==='HIGH'?'var(--gold-light)':
                                    ($complaint['priority']==='LOW'?'var(--green-accent)':'var(--gold)'));
                                ?>;font-weight:700;font-size:14px;">
                                <?php echo htmlspecialchars($complaint['priority']); ?>
                            </div>
                        </div>
                        <div class="tc-meta">
                            <div class="tc-meta-label">Submitted</div>
                            <div class="tc-meta-value"><?php echo formatDateTime($complaint['created_at']); ?></div>
                        </div>
                    </div>

                    <!-- Description -->
                    <div style="margin-bottom:0;">
                        <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:7px;font-weight:500;">
                            Description
                        </div>
                        <div style="padding:16px 18px;background:rgba(201,168,76,0.04);border-radius:10px;border:1px solid rgba(201,168,76,0.1);line-height:1.65;font-size:14px;color:var(--text-secondary);white-space:pre-wrap;">
                            <?php echo htmlspecialchars($complaint['description']); ?>
                        </div>
                    </div>

                    <!-- Resolution -->
                    <?php if (!empty($complaint['resolution'])): ?>
                        <div style="margin-top:18px;">
                            <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:7px;font-weight:500;">
                                <i class="bi bi-check2-all"></i> Resolution
                            </div>
                            <div style="padding:16px 18px;background:rgba(34,197,94,0.05);border-radius:10px;border:1px solid rgba(34,197,94,0.15);line-height:1.65;font-size:14px;color:#16a34a;white-space:pre-wrap;">
                                <?php echo htmlspecialchars($complaint['resolution']); ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($complaint['resolved_at']): ?>
                        <div style="font-size:12px;color:var(--text-muted);margin-top:12px;">
                            <i class="bi bi-calendar-event"></i> Resolved <?php echo formatDateTime($complaint['resolved_at']); ?>
                        </div>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- ══ DOCUMENT RESULT ═══════════════════════════ -->
            <?php if (($typeTab ?? '') === 'document'): ?>
                <div class="track-result-card">
                    <div class="track-result-header">
                        <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                            <code style="font-size:13px;background:rgba(201,168,76,0.12);padding:5px 10px;border-radius:8px;color:var(--gold);font-weight:600;">
                                <i class="bi bi-file-earmark-text"></i> <?php echo htmlspecialchars($trackToken); ?>
                            </code>
                            <span style="font-size:12px;color:var(--text-muted);">Document Request</span>
                        </div>
                        <?php echo statusBadgeEl($sMap, $req['status']); ?>
                    </div>

                    <?php if (!empty($req['is_blocked'])): ?>
                    <div style="padding:20px 22px;background:rgba(239,68,68,0.06);border:1px solid rgba(239,68,68,0.25);border-radius:12px;margin-bottom:22px;display:flex;gap:14px;align-items:flex-start;">
                        <div style="width:42px;height:42px;border-radius:50%;background:rgba(239,68,68,0.12);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="bi bi-shield-slash-fill" style="color:var(--accent-red);font-size:20px;"></i>
                        </div>
                        <div>
                            <div style="font-weight:700;color:var(--accent-red);font-size:15px;margin-bottom:4px;">This Document Request Has Been Blocked</div>
                            <div style="font-size:13px;color:var(--text-secondary);line-height:1.6;"><?php echo htmlspecialchars(urldecode($req['block_reason'] ?? 'No reason provided.')); ?></div>
                            <div style="font-size:11px;color:var(--text-muted);margin-top:6px;">You can no longer track or update this request.</div>
                        </div>
                    </div>
                    <?php else: ?>

                    <!-- Document 4-stage pipeline -->
                    <div class="track-lifecycle">
                        <?php
                            $docLifecycle = [
                                ['label' => 'Pending',    'icon' => 'bi-clock-history', 'desc' => 'Request submitted and queued'],
                                ['label' => 'Processing', 'icon' => 'bi-gear',          'desc' => 'Being prepared by the secretariat'],
                                ['label' => 'Ready',      'icon' => 'bi-check-circle',  'desc' => 'Available for collection'],
                                ['label' => 'Collected',  'icon' => 'bi-bag-check',      'desc' => 'Document collected by student'],
                            ];
                            $rlMap = ['PENDING'=>0,'PROCESSING'=>1,'READY'=>2,'COLLECTED'=>3,'REJECTED'=>-1];
                            $activeDocStep = $rlMap[$req['status']] ?? 0;

                            foreach ($docLifecycle as $i => $step):
                        ?>
                            <div>
                                <div class="lc-step <?php echo ($req['status']==='REJECTED') ? 'lc-rejected' : (($activeDocStep >= $i || $activeDocStep === -1) ? ($activeDocStep===-1 ? 'lc-rejected-step' : 'lc-active') : ($activeDocStep === $i-1 ? 'lc-next' : 'lc-inactive')); ?>">
                                    <div class="lc-step-icon <?php echo ($activeDocStep > $i || ($activeDocStep === -1 && $i > 0)) ? 'lc-done' : ''; ?>">
                                        <i class="bi <?php echo $step['icon']; ?>"></i>
                                    </div>
                                    <div class="lc-step-label"><?php echo $step['label']; ?></div>
                                    <div class="lc-step-desc"><?php echo $step['desc']; ?></div>
                                </div>
                                <?php if ($i < count($docLifecycle)-1): ?>
                                <div class="lc-line <?php echo ($activeDocStep > $i || ($activeDocStep >= $i && $activeDocStep !== -1)) ? 'lc-line-done' : ''; ?>"></div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Rejection notice -->
                    <?php if ($req['status'] === 'REJECTED'): ?>
                        <div style="padding:18px 20px;background:rgba(239,68,68,0.06);border:1px solid rgba(239,68,68,0.2);border-radius:12px;margin-bottom:20px;display:flex;gap:12px;align-items:flex-start;">
                            <i class="bi bi-x-octagon" style="color:var(--accent-red);font-size:20px;margin-top:2px;"></i>
                            <div>
                                <div style="font-weight:600;color:var(--accent-red);margin-bottom:4px;">Request Rejected</div>
                                <div style="font-size:14px;color:var(--text-secondary);line-height:1.6;white-space:pre-wrap;">
                                    <?php echo htmlspecialchars($req['remarks'] ?? 'Request has been rejected by the secretariat. Please contact the SRC office for further information.'); ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Metadata -->
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;margin-bottom:22px;">
                        <div class="tc-meta">
                            <div class="tc-meta-label">Document Type</div>
                            <div><span class="badge badge-role"><?php echo htmlspecialchars($req['document_type']); ?></span></div>
                        </div>
                        <div class="tc-meta">
                            <div class="tc-meta-label">Applicant</div>
                            <div class="tc-meta-value">
                                <?php
                                    $applicantName = trim(($req['first_name'] ?? '') . ' ' . ($req['last_name'] ?? ''));
                                    if (!$applicantName) $applicantName = $req['guest_full_name'] ?? '—';
                                    echo htmlspecialchars($applicantName);
                                ?>
                            </div>
                        </div>
                        <div class="tc-meta">
                            <div class="tc-meta-label">Student ID</div>
                            <div class="tc-meta-value">
                                <?php
                                    $sid = $req['student_id'] ?? $req['guest_student_id'] ?? '—';
                                    echo htmlspecialchars($sid);
                                ?>
                            </div>
                        </div>
                        <div class="tc-meta">
                            <div class="tc-meta-label">Requested</div>
                            <div class="tc-meta-value"><?php echo formatDateTime($req['requested_at']); ?></div>
                        </div>
                    </div>

                    <!-- Purpose -->
                    <div style="margin-bottom:18px;">
                        <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:7px;font-weight:500;">Purpose</div>
                        <div style="padding:16px 18px;background:rgba(201,168,76,0.04);border-radius:10px;border:1px solid rgba(201,168,76,0.1);line-height:1.65;font-size:14px;color:var(--text-secondary);white-space:pre-wrap;">
                            <?php echo htmlspecialchars($req['purpose']); ?>
                        </div>
                    </div>

                    <!-- Processed by -->
                    <?php if ($req['processed_at']): ?>
                    <div style="font-size:12px;color:var(--text-muted);margin-bottom:0;">
                        <i class="bi bi-person-check"></i>
                        Processed <?php echo timeAgo($req['processed_at']); ?>
                        <?php if (!empty($req['processed_first'])): ?>
                            by <?php echo htmlspecialchars($req['processed_first'] . " " . $req['processed_last']); ?>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Collected -->
                    <?php if ($req['status'] === 'COLLECTED' && $req['collected_at']): ?>
                    <div style="font-size:12px;color:var(--green-accent);margin-top:8px;">
                        <i class="bi bi-bag-check"></i> Collected <?php echo timeAgo($req['collected_at']); ?>
                    </div>
                    <?php endif; ?>

                    <!-- Queue summary -->
                    <div style="margin-top:20px;padding:14px 16px;background:rgba(138,155,184,0.05);border-radius:10px;border:1px solid rgba(138,155,184,0.12);font-size:13px;color:var(--text-muted);">
                        <i class="bi bi-question-circle"></i>
                        <strong>What happens next?</strong>
                        <?php if ($req['status'] === 'PENDING'): ?>
                            The secretariat will process your request within 3 to 5 working days. You will be notified when it is ready for collection.
                        <?php elseif ($req['status'] === 'PROCESSING'): ?>
                            Your document is being prepared. Once ready, the status will change to <strong>Ready for Collection</strong> and you will be instructed where to collect it.
                        <?php elseif ($req['status'] === 'READY'): ?>
                            Your document is ready for collection at the SRC office. Please bring your student ID when collecting.
                        <?php elseif ($req['status'] === 'COLLECTED'): ?>
                            Your document has been successfully collected. Thank you for using the SRC document request service.
                        <?php else: ?>
                            If you have questions, please contact the SRC Secretariat.
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- ══ NOT FOUND ════════════════════════════════ -->
            <?php if ($trackToken !== '' && $typeTab === ''): ?>
                <div class="track-not-found">
                    <div class="track-nf-icon"><i class="bi bi-search"></i></div>
                    <h2>Token Not Found</h2>
                    <p>The tracking token <strong><?php echo htmlspecialchars($trackToken); ?></strong> does not exist in our records.</p>
                    <ul style="font-size:14px;color:var(--text-muted);margin:16px 0 0;text-align:left;max-width:400px;margin-left:auto;margin-right:auto;">
                        <li>Double-check the token entered above</li>
                        <li>Tokens start with <code style="font-size:12px;color:var(--gold);">TKN-</code> for complaints or <code style="font-size:12px;color:var(--gold);">REQ-</code> for document requests</li>
                        <li>If the token was copied, ensure there are no extra spaces</li>
                    </ul>
                    <p style="margin-top:20px;font-size:13px;color:var(--text-muted);">
                        Still need help? <a href="index.php#portal" style="color:var(--gold);text-decoration:none;">Return to Portal</a> or contact the SRC Secretariat.
                    </p>
                </div>
            <?php endif; ?>

        <?php endif; ?>
    </div>
</section>

<!-- ══════════════════════════════════════════════════════
      HOW IT WORKS — always visible
═════════════════════════════════════════════════════ -->
<section class="section" style="padding:60px 0;margin-top:20px;">
    <div class="container">
        <h2 class="section-heading" style="text-align:center;margin-bottom:8px;">How Tracking Works</h2>
        <p class="section-sub" style="text-align:center;margin-bottom:42px;">Fully anonymous. No personal data is ever exposed.</p>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:20px;margin-top:32px;">
            <div class="how-card">
                <div class="how-icon"><i class="bi bi-file-earmark-plus"></i></div>
                <h3 class="how-title">Submit</h3>
                <p class="how-desc">File your complaint or document request from the public forms. No login, no name required.</p>
            </div>
            <div class="how-card">
                <div class="how-icon how-icon-mid"><i class="bi bi-key"></i></div>
                <h3 class="how-title">Receive Token</h3>
                <p class="how-desc">Save your unique tracking token immediately after submitting. This is the only way to access your record.</p>
            </div>
            <div class="how-card">
                <div class="how-icon how-icon-mid"><i class="bi bi-search"></i></div>
                <h3 class="how-title">Track Here</h3>
                <p class="how-desc">Return to this page anytime and enter your token — no account, no fingerprint, no biography needed.</p>
            </div>
            <div class="how-card">
                <div class="how-icon how-icon-right"><i class="bi bi-bell"></i></div>
                <h3 class="how-title">Get Updates</h3>
                <p class="how-desc">Check back whenever you like to see the current stage: submitted, processing, resolved, or collected.</p>
            </div>
        </div>
    </div>
</section>

<style>
/* ── Tracking page specific ────────────────────────────── */
.track-hero {
    margin-top: 122px;
    padding: 56px 0 32px;
    background: linear-gradient(175deg, rgba(201,168,76,0.07) 0%, rgba(201,168,76,0.02) 100%);
    border-bottom: 1px solid rgba(201,168,76,0.1);
}
.track-hero-content { max-width: 660px; margin-left: auto; margin-right: auto; text-align: center; }
.complaint-hero-eyebrow-spaced { margin-bottom: 22px; }
.track-form-card {
    max-width: 580px; margin: 0 auto;
    background: var(--card-bg); border: 1px solid rgba(138,155,184,0.12);
    border-radius: 16px; padding: 28px;
}
.track-input {
    width: 100%; padding: 14px 18px;
    background: rgba(201,168,76,0.05);
    border: 2px solid rgba(201,168,76,0.2);
    border-radius: 12px; font-size: 16px; font-family: var(--font-mono, 'Space Mono', monospace);
    color: var(--text-primary); text-transform: uppercase;
    transition: border-color .2s, box-shadow .2s;
    outline: none;
}
.track-input:focus {
    border-color: var(--gold);
    box-shadow: 0 0 0 4px rgba(201,168,76,0.1);
}
.track-input::placeholder {
    color: var(--text-muted); text-transform: uppercase;
}
.track-input:disabled,
.track-input[readonly] {
    opacity: 1; cursor: text;
}

/* ── Result card ──────────────────────────────────────── */
.track-result-card {
    max-width: 720px; margin: 0 auto; margin-top: 20px;
    background: var(--card-bg); border: 1px solid rgba(201,168,76,0.18);
    border-radius: 16px; padding: 28px;
    box-shadow: 0 8px 40px rgba(0,0,0,0.06);
}
.track-result-header {
    display:flex; justify-content:space-between; align-items:center;
    flex-wrap:wrap; gap:12px;
    padding-bottom:20px; border-bottom:1px solid rgba(201,168,76,0.1); margin-bottom:22px;
}

/* ── Lifecycle / Pipeline ─────────────────────────────── */
.track-lifecycle { display:flex; gap:0; margin:24px 0 0; padding:0 8px; overflow-x:auto; }
.track-lifecycle > div { display:flex; flex-direction:column; align-items:center; min-width:80px; flex:1; }
.lc-step { display:flex; flex-direction:column; align-items:center; gap:8px; width:100%; }
.lc-step-icon {
    width:44px; height:44px; border-radius:50%;
    border:2px solid rgba(138,155,184,0.25); background:rgba(138,155,184,0.07);
    display:flex; align-items:center; justify-content:center;
    font-size:17px; color:var(--text-muted); transition:all .3s;
}
.lc-step-label {
    font-size:11px; font-weight:600; text-transform:uppercase;
    letter-spacing:.06em; color:var(--text-muted); white-space:nowrap; transition:color .3s;
}
.lc-step-desc { display:none; font-size:10px; color:var(--text-muted); text-align:center; }
@media(min-width:600px) { .lc-step-desc { display:block; max-width:90px; } }

.lc-line {
    flex:1; min-width:20px; height:2px;
    background: rgba(138,155,184,0.18);
    margin-bottom:24px; margin-top:-1px; transition:background .3s;
}

/* lc-active — the current/final step */
.lc-active .lc-step-icon {
    border-color: var(--gold); background:rgba(201,168,76,0.12); color:var(--gold);
}
.lc-active .lc-step-label { color:var(--gold); }
/* current-only dimming of non-active steps */
.lc-line-done { background: var(--gold) !important; }
.lc-done { border-color:var(--green-accent) !important; background:rgba(34,197,94,0.12) !important; color:var(--green-accent) !important; }

.lc-next .lc-step-icon { border-color:rgba(201,168,76,0.35); color:rgba(201,168,76,0.5); }
.lc-next .lc-step-label { color:rgba(201,168,76,0.55); }
.lc-inactive .lc-step-icon { border-color:rgba(138,155,184,0.18); color:rgba(138,155,184,0.25); }
.lc-inactive .lc-step-label { color:rgba(138,155,184,0.35); }

/* lc-rejected branch — all steps dim red at idx 0 */
.lc-rejected-step .lc-step-icon { opacity:.3; }
.lc-rejected-step .lc-step-label { opacity:.5; text-decoration:line-through; color:var(--text-muted); }

/* ── Meta card grid ───────────────────────────────────── */
.tc-meta {
    background: rgba(201,168,76,0.04); border-radius:10px;
    padding: 14px 16px; border:1px solid rgba(201,168,76,0.1);
}
.tc-meta-label { font-size:10px; color:var(--text-muted); text-transform:uppercase; letter-spacing:.06em; margin-bottom:4px; font-weight:500; }
.tc-meta-value { font-size:14px; font-weight:600; }

/* ── Not found ────────────────────────────────────────── */
.track-not-found {
    max-width:540px; margin: 0 auto; margin-top:20px; text-align:center;
}
.track-nf-icon {
    width:72px; height:72px; border-radius:50%;
    background:rgba(239,68,68,0.1); border:1px solid rgba(239,68,68,0.2);
    display:flex; align-items:center; justify-content:center;
    font-size:32px; color:var(--accent-red); margin:0 auto 20px;
}
.track-not-found h2 { font-family:var(--font-display); font-size:26px; margin-bottom:10px; }
.track-not-found p { color:var(--text-muted); font-size:15px; }
.track-not-found a:hover { text-decoration:underline; }

/* ── How it works ─────────────────────────────────────── */
.how-card {
    background:var(--card-bg); border:1px solid rgba(138,155,184,0.12);
    border-radius:14px; padding:24px; text-align:center; transition:border-color .2s, box-shadow .2s;
}
.how-card:hover {
    border-color:var(--gold); box-shadow:0 8px 30px rgba(201,168,76,0.08);
}
.how-icon {
    width:52px;height:52px; border-radius:50%;
    background:rgba(201,168,76,0.12); border:2px solid rgba(201,168,76,0.2);
    display:flex;align-items:center;justify-content:center;
    font-size:22px; color:var(--gold); margin:0 auto 16px;
}
.how-icon-mid { border-color:rgba(201,168,76,0.18); color:var(--gold); background:rgba(201,168,76,0.08); }
.how-icon-right { border-color:rgba(201,168,76,0.18); color:var(--gold); background:rgba(201,168,76,0.08); }
.how-title {
    font-family:var(--font-display); font-size:18px; font-weight:700;
    margin-bottom:6px; color:var(--gold);
}
.how-desc { font-size:13.5px; color:var(--text-muted); line-height:1.65; }

/* ── Badge base (fallback if not in global CSS) ──────── */
.badge {
    display:inline-block; padding:4px 10px; border-radius:99px;
    font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:.04em;
}
.badge-role { background:rgba(201,168,76,0.12); color:var(--gold); border:1px solid rgba(201,168,76,0.25); }
.badge-active { background:rgba(34,197,94,0.12); color:var(--green-accent); border:1px solid rgba(34,197,94,0.25); }
.badge-inactive { background:rgba(100,116,139,0.12); color:var(--text-muted); border:1px solid rgba(100,116,139,0.22); }

/* ── Responsive ───────────────────────────────────────── */
@media(max-width:600px) {
    .track-hero { padding: 36px 0 24px; }
    .track-form-card { padding: 20px; }
    .track-result-card { padding: 20px; }
    .track-lifecycle { overflow-x: scroll; -webkit-overflow-scrolling: touch; }
}
</style>

<?php include __DIR__ . '/include/footer.php'; ?>
</body>
</html>
