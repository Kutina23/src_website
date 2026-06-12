<?php
require_once 'config/database.php';
require_once 'models/Committees.php';

$db = Database::getInstance();
$committeeModel = new Committees($db);
$memberModel = new CommitteeMembers($db);

$committee = $committeeModel->getBySlug('Finance & Audit Committee');
$leadership = $memberModel->getLeadership($committee['id'] ?? null);
$members = $memberModel->getMembers($committee['id'] ?? null);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($committee['name'] ?? 'Finance Audit Committee'); ?> — DHLTU SRC</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;0,700;1,300,400&family=Outfit:wght@200;300;400;500;600;700&family=Space+Mono:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
  <link rel="stylesheet" href="assets/css/main.css">
  <link rel="icon" type="image/png" href="assets/images/logo.png">
</head>
<body>
<?php include 'include/header.php'; ?>

  <main class="section" style="padding-top: 160px; min-height: 80vh;">
    <div class="container">
      <a href="index.php#committees" class="back-link" style="text-decoration:none; color:var(--gold); font-family:'Space Mono'; font-size:12px; display:inline-flex; align-items:center; gap:8px; margin-bottom:24px;">
        <i class="bi bi-arrow-left"></i> Back to Main Portal
      </a>
      <header class="section-header" style="margin-bottom: 40px;">
        <span class="section-eyebrow" style="letter-spacing:0.15em; color:var(--gold); font-family:'Space Mono'; font-size:11px;">FINANCIAL OVERSIGHT & ACCOUNTABILITY</span>
        <h1 style="font-family:'Cormorant Garamond'; font-size:clamp(32px, 5vw, 48px); color:var(--cream);">Finance <span style="color:var(--gold-light); font-style:italic;">Audit Committee</span></h1>
        <p style="max-width: 700px; margin: 20px auto 0 auto; color:var(--text-muted); line-height:1.6;"><?php echo htmlspecialchars($committee['description'] ?? 'Ensuring transparent financial management through regular audits, budget reviews, and fiscal policy recommendations to uphold accountability.'); ?></p>
      </header>
      <div style="width:100%; height:1px; background:linear-gradient(90deg, transparent, rgba(201,168,76,0.3), transparent); margin-bottom: 50px;"></div>

      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 40px;">
        <div>
          <h3 style="font-family:'Space Mono'; font-size: 16px; text-transform: uppercase; color: var(--gold-light); margin-bottom: 24px; letter-spacing: 0.1em;"><i class="bi bi-shield-shaded" style="color:var(--gold); margin-right:8px;"></i> Committee Leadership</h3>
          <div style="display: flex; flex-direction: column; gap: 16px; margin-bottom: 32px;">
            <?php if (empty($leadership)): ?>
              <p style="color: var(--text-muted); font-size: 14px;">Leadership information not yet assigned.</p>
            <?php else: ?>
              <?php foreach ($leadership as $member): ?>
              <div class="card" style="padding: 20px; display: flex; align-items: center; gap: 20px; background:rgba(15,32,64,0.5); border:1px solid <?php echo $member['role_type'] === 'chairperson' ? 'var(--gold)' : 'rgba(201,168,76,0.15)'; ?>;">
                <div style="width: 48px; height: 48px; border: 1px solid var(--gold); display:flex; align-items:center; justify-content:center; color:var(--gold); font-size:18px; border-radius:4px;"><i class="bi <?php echo $member['role_type'] === 'chairperson' ? 'bi-star-fill' : 'bi-shield-check'; ?>"></i></div>
                <div>
                  <span style="font-size: 10px; font-family:'Space Mono'; text-transform: uppercase; color: <?php echo $member['role_type'] === 'chairperson' ? 'var(--gold)' : 'var(--text-muted)'; ?>; font-weight:600;"><?php echo ucfirst($member['role_type'] ?? 'Leadership'); ?></span>
                  <h4 style="font-size: 16px; font-weight: 500; color: var(--cream); margin: 2px 0 0 0;"><?php echo htmlspecialchars($member['name'] ?? 'N/A'); ?></h4>
                  <p style="font-size: 12px; color: var(--text-muted); margin:4px 0 0 0;"><?php echo htmlspecialchars($member['department'] ?? ''); ?></p>
                </div>
              </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>

          <h3 style="font-family:'Space Mono'; font-size: 16px; text-transform: uppercase; color: var(--gold-light); margin-bottom: 24px; letter-spacing: 0.1em;"><i class="bi bi-people" style="color:var(--gold); margin-right:8px;"></i> Committee Members</h3>
          <div style="display: flex; flex-direction: column; gap: 16px;">
            <?php if (empty($members)): ?>
              <p style="color: var(--text-muted); font-size: 14px;">No members currently assigned.</p>
            <?php else: ?>
              <?php foreach ($members as $member): ?>
              <div class="card" style="padding: 20px; display: flex; align-items: center; gap: 20px; background:rgba(15,32,64,0.5); border:1px solid rgba(201,168,76,0.15);">
                <div style="width: 48px; height: 48px; border: 1px solid rgba(201,168,76,0.4); display:flex; align-items:center; justify-content:center; color:var(--text-muted); font-size:18px; border-radius:4px;"><i class="bi bi-person"></i></div>
                <div>
                  <span style="font-size: 10px; font-family:'Space Mono'; text-transform: uppercase; color: var(--text-muted);">Committee Member</span>
                  <h4 style="font-size: 16px; font-weight: 500; color: var(--cream); margin: 2px 0 0 0;"><?php echo htmlspecialchars($member['name'] ?? 'N/A'); ?></h4>
                  <p style="font-size: 12px; color: var(--text-muted); margin:4px 0 0 0;"><?php echo htmlspecialchars($member['department'] ?? ''); ?></p>
                </div>
              </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
        <div>
          <h3 style="font-family:'Space Mono'; font-size: 16px; text-transform: uppercase; color: var(--gold-light); margin-bottom: 24px; border-bottom: 1px solid rgba(201,168,76,0.15); padding-bottom: 8px;"><i class="bi bi-list-task" style="color:var(--gold); margin-right:8px;"></i> Core Mandate & Duties</h3>
          <div style="display: flex; flex-direction: column; gap: 14px;">
            <div class="info-card" style="padding: 20px; background: rgba(26,48,96,0.3); border-left: 3px solid var(--gold);">
              <h4 style="font-size: 15px; margin: 0 0 6px 0; color: var(--gold-light);">Budget Review & Allocation</h4>
              <p style="font-size: 13px; margin: 0; color: var(--text-muted); line-height: 1.5;">Analyzing departmental budget proposals, ensuring equitable resource distribution, and recommending adjustments based on institutional priorities.</p>
            </div>
            <div class="info-card" style="padding: 20px; background: rgba(26,48,96,0.3); border-left: 3px solid var(--gold);">
              <h4 style="font-size: 15px; margin: 0 0 6px 0; color: var(--gold-light);">Expenditure Tracking & Reporting</h4>
              <p style="font-size: 13px; margin: 0; color: var(--text-muted); line-height: 1.5;">Monitoring all SRC expenses, verifying receipts, and publishing monthly financial statements for transparency.</p>
            </div>
            <div class="info-card" style="padding: 20px; background: rgba(26,48,96,0.3); border-left: 3px solid var(--gold);">
              <h4 style="font-size: 15px; margin: 0 0 6px 0; color: var(--gold-light);">Internal Audit & Compliance</h4>
              <p style="font-size: 13px; margin: 0; color: var(--text-muted); line-height: 1.5;">Conducting surprise audits, reviewing financial procedures, and ensuring adherence to financial regulations and policies.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>

<?php include 'include/footer.php'; ?>
  
</body>
</html>