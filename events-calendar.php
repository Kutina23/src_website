<?php
session_start();
require_once 'config/database.php';
require_once 'config/validations.php';
require_once 'models/News.php';
require_once 'models/Elections.php';

$db = Database::getInstance();
$newsModel   = new News($db);
$elecModel   = new Elections($db);

$page_title  = "Events Calendar";
$current_page = "events";
$submitError = '';
$submitSuccess = '';

// ── Handle public event submission (POST-Redirect-GET to prevent re-submission) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_event'])) {
    $eventTitle    = trim($_POST['event_title']      ?? '');
    $eventDate     = trim($_POST['event_date']       ?? '');
    $eventExcerpt  = trim($_POST['event_excerpt']    ?? '');
    $eventContent  = trim($_POST['event_content']    ?? '');
    $eventLocation = trim($_POST['event_location']   ?? '');
    $eventStart    = trim($_POST['event_start_time'] ?? '');
    $agreedTerms   = isset($_POST['agreed_terms']) ? true : false;

    if (!$eventTitle || !$eventDate || !$agreedTerms) {
        $submitError = 'Please fill in all required fields and agree to the terms.';
    } else {
        // Convert time format for validation (form sends HH:MM, excerpt stores g:i A)
        $validationTime = $eventStart;
        if ($eventStart && preg_match('/^\d{2}:\d{2}$/', $eventStart)) {
            $validationTime = date('g:i A', strtotime($eventStart));
        }
        
        $conflictErrors = validateEventScheduling([
            'event_location' => $eventLocation,
            'event_date' => $eventDate,
            'event_start_time' => $validationTime
        ]);

        if (!empty($conflictErrors)) {
            $submitError = $conflictErrors[0];
        } else {
            try {
                $metaParts = [];
                if ($eventStart)   $metaParts[] = '🕐 ' . date('g:i A', strtotime($eventStart));
                if ($eventLocation) $metaParts[] = '📍 ' . $eventLocation;
                $metaLine  = implode(' · ', $metaParts);
                $fullExcerpt = $metaLine ? ($metaLine . "\n" . $eventExcerpt) : $eventExcerpt;
                $fullContent = $eventContent ?: $eventExcerpt;

                $insertData = [
                    'title'          => $eventTitle,
                    'content'        => $fullContent,
                    'excerpt'        => $fullExcerpt,
                    'category'       => 'EVENT',
                    'status'         => 'PUBLISHED',
                    'published_at'   => $eventDate . ' 00:00:00',
                    'is_featured'    => 0,
                    'tags'           => null,
                    'featured_image' => null,
                    'media_type'     => 'IMAGE',
                    'author_id'      => null,
                ];

                $inserted = (bool)$newsModel->create($insertData);

                if ($inserted) {
                    // PRG: store success message in session and redirect away from POST
                    $redirectMonth = isset($_POST['_month']) ? (int)$_POST['_month'] : $month;
                    $redirectYear  = isset($_POST['_year'])  ? (int)$_POST['_year']  : $year;
                    $_SESSION['calendar_submit_success'] = 'Your event has been submitted successfully and is now visible on the calendar!';
                    header("Location: events-calendar.php?month=$redirectMonth&year=$redirectYear");
                    exit;
                }
                // Insert failed — fall through to GET with error visible
                $submitError = 'Failed to submit the event. Please try again.';
            } catch (Exception $e) {
                $submitError = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

// ── On GET: read flash success message from session (set by PRG redirect above) ──
if (empty($submitError)) {
    $submitSuccess = $_SESSION['calendar_submit_success'] ?? '';
    unset($_SESSION['calendar_submit_success']);
}

// ── Calendar date context ──────────────────────────────────────────
$year    = isset($_GET['year'])  ? (int)$_GET['year']  : (int)date('Y');
$month   = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');
if ($month < 1)  { $month = 12; $year--; }
if ($month > 12) { $month = 1;  $year++; }
$calDate = new DateTime("$year-$month-01");
$current_month_label = $calDate->format('F Y');
$today   = new DateTime();
$firstDayDow = (int)$calDate->format('w');           // 0 = Sun
$daysInMonth = (int)$calDate->format('t');

$prev = (clone $calDate)->modify("first day of last month");
$next = (clone $calDate)->modify("first day of next month");

// ── DB sources ─────────────────────────────────────────────────────
$eventNews = [];
try { $eventNews = $newsModel->getByCategory('EVENT', 200); } catch (Exception $e) { $eventNews = []; }

$upcomingElections = [];
$activeElections   = [];
try { $upcomingElections = $elecModel->getUpcoming(); } catch (Exception $e) {}
try { $activeElections   = $elecModel->getAllActive(); }    catch (Exception $e) {}

$monthlyElections = array_filter($upcomingElections, fn($e) =>
    (int)date('n', strtotime($e['election_date'])) === $month
        && (int)date('Y', strtotime($e['election_date'])) === $year
);

// ── Build the calendar grid ────────────────────────────────────────
$dows = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
$gridEvents = [];

foreach ($monthlyElections as $el) {
    $d = (int)date('j', strtotime($el['election_date']));
    $label = substr($el['title'], 0, 28) . (strlen($el['title']) > 28 ? '…' : '');
    $gridEvents[$d][] = ['label'=>$label.' ✓','color'=>'gold'];
}

foreach ($eventNews as $ev) {
    $evMonth = (int)date('n', strtotime($ev['published_at']));
    $evYear  = (int)date('Y', strtotime($ev['published_at']));
    if ($evMonth !== $month || $evYear !== $year) continue;
    $d = (int)date('j', strtotime($ev['published_at']));
    $label = substr($ev['title'], 0, 28) . (strlen($ev['title']) > 28 ? '…' : '');
    $gridEvents[$d][] = ['label'=>$label,'color'=>'blue'];
}

$isTodayFn = fn(int $d): bool => $d === (int)$today->format('j')
    && $month === (int)$today->format('n')
    && $year  === (int)$today->format('Y');

// Extract location+time meta lines from an EVENT excerpt
function event_meta(string $excerpt, ?string $sortKey = null): array {
    $meta  = ['📍' => '', '🕐' => ''];
    $clean = $excerpt;
    if (preg_match('/(?:📍|\[location\])\s*(.+?)(?:\n|$)/i', $excerpt, $m)) {
        $meta['📍'] = trim(str_replace(['📍','[location]'], '', $m[1]));
        $clean = str_replace($m[0], '', $excerpt);
    }
    if (preg_match('/(?:🕐|\[time\])\s*(.+?)(?:\n|$)/i', $excerpt, $m)) {
        $meta['🕐'] = trim(str_replace(['🕐','[time]'], '', $m[1]));
        $clean = str_replace($m[0], '', $excerpt);
    }
    return [$meta, trim($clean)];
}

// ── Sidebar event list ─────────────────────────────────────────────
$sidebarRaws = [];

foreach ($upcomingElections as $el) {
    $sidebarRaws[] = [
        'source'  => 'election',
        'day'     => date('j', strtotime($el['election_date'])),
        'month'   => strtoupper(date('M', strtotime($el['election_date']))),
        'tag'     => 'Elections',
        'tagClass'=> '',
        'tagColor'=> 'gold',
        'name'    => $el['title'],
        'time'    => (!empty($el['start_time']) ? date('g:i A', strtotime($el['start_time'])) : 'TBC')
                     . ' · ' . ($el['location'] ?? 'TBC'),
        'type'    => 'upcoming',
        'sortKey' => strtotime($el['election_date']),
    ];
}
foreach ($eventNews as $ev) {
    $meta['📍'] = '';
    $meta['🕐'] = '';
    if (strtoupper($ev['category']) === 'EVENT') {
        [$_meta, $_clean] = event_meta((string)($ev['excerpt'] ?? ''));
        $meta = $_meta;
        $ev['excerpt'] = $_clean;
    }
    $sidebarRaws[] = [
        'source'   => 'news',
        'day'      => date('j', strtotime($ev['published_at'])),
        'month'    => strtoupper(date('M', strtotime($ev['published_at']))),
        'tag'      => strtoupper($ev['category']),
        'tagClass' => '',
        'tagColor' => strtoupper($ev['category']) === 'EVENT' ? 'blue' : 'gold',
        'name'     => $ev['title'],
        'excerpt'  => str_replace(PHP_EOL, ' · ', wordwrap((string)($ev['excerpt'] ?? ''), 80, PHP_EOL)),
        'time'     => ($meta['🕐'] ?: date('M d, Y', strtotime($ev['published_at'])))
                     . ($meta['📍'] ? ' · ' . $meta['📍'] : ''),
        'type'     => 'news',
        'sortKey'  => strtotime($ev['published_at']),
    ];
}

usort($sidebarRaws, fn($a,$b) => $a['sortKey'] <=> $b['sortKey']);
$sidebarEvents = array_slice($sidebarRaws, 0, 8);
$cardEvents    = array_slice($sidebarRaws, 0, 4);

// ── Category legend counts ─────────────────────────────────────────
$catCounts = [];
foreach ($eventNews as $ev) {
    $c = strtoupper($ev['category']);
    $catCounts[$c] = ($catCounts[$c] ?? 0) + 1;
}
$catCounts['ELECTIONS'] = count($upcomingElections);
$legendMap = [
    'EVENT'        => ['Events','blue'],
    'NEWS'         => ['SRC Governance','gold'],
    'ANNOUNCEMENT' => ['Announcements','gold'],
    'GOVERNANCE'   => ['Governance','blue'],
    'ACADEMIC'     => ['Academic','blue'],
    'WELFARE'      => ['Welfare & Health','green'],
    'SPORTS'       => ['Sports & Recreation','red'],
];
$legendEntries = [];
foreach ($catCounts as $cat => $cnt) {
    $labelAndColor = $legendMap[strtoupper($cat)] ?? null;
    if ($labelAndColor) {
        $legendEntries[] = ['count'=>$cnt,'cat'=>$cat,'label'=>$labelAndColor[0],'color'=>$labelAndColor[1]];
    }
}
if (count($upcomingElections) > 0) {
    array_unshift($legendEntries, ['count'=>count($upcomingElections),'cat'=>'ELECTIONS','label'=>'Elections','color'=>'gold']);
}
if (empty($legendEntries)) {
    $legendEntries = [
        ['count'=>0,'cat'=>'EVENT','label'=>'Events','color'=>'blue'],
        ['count'=>0,'cat'=>'ACADEMIC','label'=>'Academic','color'=>'blue'],
        ['count'=>0,'cat'=>'WELFARE','label'=>'Welfare','color'=>'green'],
        ['count'=>0,'cat'=>'SPORTS','label'=>'Sports','color'=>'red'],
    ];
}
function totalEventCount() {
    global $eventNews, $upcomingElections;
    return count($eventNews) + count($upcomingElections);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($page_title) ?> — DHLTU SRC</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;0,700;1,300,1,400&family=Outfit:wght@300;400;500;600&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="assets/css/main.css" />
  <style>
    :root {
      --navy: #0a1628;
      --navy-mid: #0f2040;
      --navy-light: #152a50;
      --gold: #c9a84c;
      --gold-light: #e2c170;
      --gold-dark: #a07830;
      --cream: #f5f0e8;
      --white: #ffffff;
      --text-muted: rgba(245,240,232,0.45);
      --transition-fast: 0.2s ease;
      --transition-med: 0.35s ease;
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { background: var(--navy); color: var(--cream); font-family: 'Outfit', sans-serif; min-height: 100vh; }

    /* PAGE HEADER */
    .page-header {
      padding: 160px 80px 80px;
      background: linear-gradient(160deg, var(--navy-mid), var(--navy));
      position: relative; overflow: hidden;
      border-bottom: 1px solid rgba(201,168,76,0.12);
    }
    .page-header::before {
      content: '';
      position: absolute; inset: 0;
      background-image:
        linear-gradient(rgba(201,168,76,0.03) 1px, transparent 1px),
        linear-gradient(90deg, rgba(201,168,76,0.03) 1px, transparent 1px);
      background-size: 60px 60px;
    }
    .page-header-orb {
      position: absolute; top: -80px; right: -80px;
      width: 500px; height: 500px;
      background: radial-gradient(circle, rgba(201,168,76,0.08), transparent 70%);
      border-radius: 50%; pointer-events: none;
    }
    .page-eyebrow {
      font-family: 'Space Mono', monospace; font-size: 10px;
      letter-spacing: 0.25em; text-transform: uppercase; color: var(--gold);
      margin-bottom: 20px; display: flex; align-items: center; gap: 12px;
      position: relative; z-index: 1;
    }
    .page-eyebrow::before { content: ''; width: 30px; height: 1px; background: var(--gold); }
    .page-title {
      font-family: 'Cormorant Garamond', serif;
      font-size: clamp(42px, 5vw, 72px);
      font-weight: 300; line-height: 1; color: var(--cream);
      margin-bottom: 16px; position: relative; z-index: 1;
    }
    .page-title em { font-style: italic; color: var(--gold-light); }
    .page-subtitle {
      font-size: 15px; font-weight: 300; line-height: 1.8;
      color: rgba(245,240,232,0.55); max-width: 520px;
      position: relative; z-index: 1;
    }

    /* MAIN LAYOUT */
    .calendar-layout {
      display: grid;
      grid-template-columns: 1fr 340px;
      gap: 0;
      min-height: calc(100vh - 320px);
    }

    /* CALENDAR SECTION */
    .calendar-main { padding: 60px 60px 60px 80px; border-right: 1px solid rgba(201,168,76,0.08); }

    .cal-nav {
      display: flex; align-items: center; justify-content: space-between;
      margin-bottom: 40px; gap: 16px; flex-wrap: wrap;
    }
    .cal-month {
      font-family: 'Cormorant Garamond', serif;
      font-size: 32px; font-weight: 300; color: var(--cream);
    }
    .cal-today-btn {
      font-family: 'Outfit', sans-serif;
      font-size: 10px; letter-spacing: 0.18em; text-transform: uppercase;
      color: var(--gold); border: 1px solid rgba(201,168,76,0.25);
      padding: 5px 14px; text-decoration: none; white-space: nowrap;
      transition: all var(--transition-fast); cursor: pointer;
    }
    .cal-today-btn:hover { border-color: var(--gold); background: rgba(201,168,76,0.08); }
    .cal-nav-btns { display: flex; gap: 8px; }
    .cal-nav-btn {
      width: 36px; height: 36px;
      background: transparent;
      border: 1px solid rgba(201,168,76,0.2);
      color: var(--gold); font-size: 16px;
      cursor: pointer; display: flex; align-items: center; justify-content: center;
      transition: all var(--transition-fast);
    }
    .cal-nav-btn:hover { border-color: var(--gold); background: rgba(201,168,76,0.08); }

    .cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 2px; }
    .cal-dow {
      text-align: center; padding: 12px 4px;
      font-family: 'Space Mono', monospace; font-size: 9px;
      letter-spacing: 0.12em; text-transform: uppercase; color: var(--text-muted);
    }
    .cal-cell {
      min-height: 90px; padding: 10px 8px;
      border: 1px solid rgba(201,168,76,0.06);
      background: rgba(255,255,255,0.01);
      position: relative; cursor: pointer;
      transition: all var(--transition-fast);
    }
    .cal-cell:hover { border-color: rgba(201,168,76,0.2); background: rgba(201,168,76,0.03); }
    .cal-cell.other-month { opacity: 0.3; }
    .cal-cell.today { border-color: rgba(201,168,76,0.4); background: rgba(201,168,76,0.05); }
    .cal-cell.has-event { border-left: 2px solid var(--gold); }
    .cal-cell.selected { border-color: var(--gold); background: rgba(201,168,76,0.08); }
    .cal-day {
      font-family: 'Space Mono', monospace; font-size: 12px;
      color: var(--text-muted); margin-bottom: 6px; display: block;
    }
    .cal-cell.today .cal-day { color: var(--gold); font-weight: 700; }
    .cal-event-dot {
      display: block; width: 100%;
      padding: 2px 4px;
      font-size: 9px; color: var(--navy);
      background: var(--gold); margin-bottom: 2px;
      white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .cal-event-dot.blue  { background: #4a9eff; }
    .cal-event-dot.green { background: #22c55e; }
    .cal-event-dot.red   { background: #ef4444; }

    /* SIDEBAR */
    .calendar-sidebar { padding: 60px 40px 60px 40px; background: var(--navy-mid); }
    .sidebar-title {
      font-family: 'Space Mono', monospace; font-size: 10px;
      letter-spacing: 0.2em; text-transform: uppercase; color: var(--gold);
      margin-bottom: 28px; padding-bottom: 16px;
      border-bottom: 1px solid rgba(201,168,76,0.12);
    }

    .event-list { display: flex; flex-direction: column; gap: 0; }
    .event-item {
      padding: 20px 0;
      border-bottom: 1px solid rgba(201,168,76,0.08);
      display: flex; gap: 16px; align-items: flex-start;
      cursor: pointer; transition: padding var(--transition-fast);
      text-decoration: none;
      color: inherit;
    }
    .event-item:hover { padding-left: 6px; }
    .event-date-block {
      flex-shrink: 0; width: 48px;
      text-align: center;
      border: 1px solid rgba(201,168,76,0.2);
      padding: 6px 4px;
    }
    .event-date-day {
      font-family: 'Cormorant Garamond', serif;
      font-size: 28px; font-weight: 700; color: var(--gold-light);
      line-height: 1; display: block;
    }
    .event-date-month {
      font-family: 'Space Mono', monospace;
      font-size: 8px; letter-spacing: 0.1em; text-transform: uppercase;
      color: var(--text-muted); display: block; margin-top: 2px;
    }
    .event-info { flex: 1; }
    .event-tag {
      display: inline-block;
      font-size: 9px; letter-spacing: 0.12em; text-transform: uppercase;
      color: var(--navy); background: var(--gold);
      padding: 2px 8px; margin-bottom: 6px;
    }
    .event-tag.blue  { background: #4a9eff; }
    .event-tag.green { background: #22c55e; }
    .event-tag.red   { background: #ef4444; }
    .event-name { font-size: 13px; font-weight: 500; color: var(--cream); margin-bottom: 4px; line-height: 1.4; }
    .event-time { font-size: 11px; color: var(--text-muted); }

    .sidebar-divider { margin: 32px 0; height: 1px; background: rgba(201,168,76,0.1); }
    .sidebar-subtitle {
      font-family: 'Space Mono', monospace; font-size: 10px;
      letter-spacing: 0.2em; text-transform: uppercase; color: var(--gold);
      margin-bottom: 20px;
    }
    .category-legend { display: flex; flex-direction: column; gap: 12px; }
    .legend-item { display: flex; align-items: center; gap: 12px; }
    .legend-dot { width: 10px; height: 10px; border-radius: 2px; flex-shrink: 0; }
    .legend-dot.gold  { background: var(--gold); }
    .legend-dot.blue  { background: #4a9eff; }
    .legend-dot.green { background: #22c55e; }
    .legend-dot.red   { background: #ef4444; }
    .legend-label { font-size: 12px; color: var(--text-muted); }

    /* UPCOMING EVENTS STRIP */
    .upcoming-strip { padding: 60px 80px; background: var(--navy); border-top: 1px solid rgba(201,168,76,0.08); }
    .section-label {
      font-family: 'Space Mono', monospace; font-size: 10px;
      letter-spacing: 0.2em; text-transform: uppercase;
      color: var(--gold); margin-bottom: 32px;
      display: flex; align-items: center; gap: 10px;
    }
    .section-label::after { content: ''; flex: 1; height: 1px; background: rgba(201,168,76,0.15); }
    .upcoming-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; }
    .upcoming-card {
      border: 1px solid rgba(201,168,76,0.1);
      background: rgba(255,255,255,0.01);
      padding: 28px 24px;
      text-decoration: none;
      transition: all var(--transition-med);
      display: block;
    }
    .upcoming-card:hover { border-color: rgba(201,168,76,0.3); transform: translateY(-4px); }
    .upcoming-card-date {
      font-family: 'Space Mono', monospace; font-size: 10px;
      letter-spacing: 0.12em; text-transform: uppercase; color: var(--gold);
      margin-bottom: 12px;
    }
    .upcoming-card-title { font-size: 15px; font-weight: 500; color: var(--cream); margin-bottom: 8px; line-height: 1.4; }
    .upcoming-card-loc { font-size: 12px; color: var(--text-muted); display: flex; align-items: center; gap: 6px; }
    .upcoming-card-loc::before { content: '\1F4CD'; font-size: 10px; }

    /* NO DATA */
    .no-data { padding: 60px 0; text-align: center; color: var(--text-muted); }
    .no-data-title {
      font-family: 'Cormorant Garamond', serif;
      font-size: 24px; color: var(--cream); margin-bottom: 8px;
    }

    /* SUBMIT EVENT BUTTON (sidebar) */
    .add-event-btn, .cal-today-btn {
      display: inline-block; margin-top: 20px;
      font-family: 'Outfit', sans-serif;
      font-size: 10px; letter-spacing: 0.18em; text-transform: uppercase;
      color: var(--cream); border: 1px solid var(--gold);
      padding: 8px 18px; text-decoration: none; cursor: pointer;
      background: transparent; transition: all var(--transition-fast);
    }
    .add-event-btn:hover, .cal-today-btn:hover {
      background: var(--gold); color: var(--navy);
    }

    /* SUBMIT EVENT MODAL */
    .modal-overlay {
      display: none; position: fixed; inset: 0;
      background: rgba(5, 12, 30, 0.88);
      backdrop-filter: blur(6px);
      z-index: 9999; align-items: center; justify-content: center;
    }
    .modal-overlay.open { display: flex; }
    .modal-box {
      background: var(--navy-mid);
      border: 1px solid rgba(201,168,76,0.2);
      border-radius: 2px;
      width: 90%; max-width: 580px;
      max-height: 90vh; overflow-y: auto;
      padding: 48px 44px 40px;
      position: relative;
    }
    .modal-close {
      position: absolute; top: 18px; right: 18px;
      background: none; border: none;
      color: var(--text-muted); font-size: 20px; cursor: pointer;
      line-height: 1;
    }
    .modal-close:hover { color: var(--cream); }
    .modal-title {
      font-family: 'Cormorant Garamond', serif;
      font-size: 30px; font-weight: 300; color: var(--cream);
      margin-bottom: 6px;
    }
    .modal-subtitle {
      font-size: 12px; color: var(--text-muted); margin-bottom: 28px;
    }
    .form-group { margin-bottom: 18px; }
    .form-label {
      display: block;
      font-family: 'Space Mono', monospace;
      font-size: 9px; letter-spacing: 0.18em; text-transform: uppercase;
      color: var(--gold); margin-bottom: 8px;
    }
    .form-label .required { color: #ef4444; margin-left: 3px; }
    .form-input, .form-textarea {
      width: 100%; padding: 10px 14px;
      background: rgba(0,0,0,0.25);
      border: 1px solid rgba(201,168,76,0.15);
      color: var(--cream); font-family: 'Outfit', sans-serif; font-size: 14px;
      border-radius: 0; outline: none;
      transition: border-color var(--transition-fast);
    }
    .form-input:focus, .form-textarea:focus { border-color: var(--gold); }
    .form-textarea { resize: vertical; min-height: 100px; }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    .form-check {
      display: flex; align-items: flex-start; gap: 10px;
      font-size: 12px; color: var(--text-muted); line-height: 1.6;
    }
    .form-check input[type=checkbox] {
      accent-color: var(--gold); margin-top: 3px; flex-shrink: 0;
    }
    .form-error {
      background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.25);
      color: #fca5a5; padding: 10px 14px; font-size: 13px; margin-bottom: 18px;
    }
    .form-success {
      background: rgba(34,197,94,0.1); border: 1px solid rgba(34,197,94,0.25);
      color: #86efac; padding: 10px 14px; font-size: 13px; margin-bottom: 18px;
    }
    .form-submit {
      width: 100%; padding: 13px 20px;
      background: var(--gold); color: var(--navy);
      font-family: 'Outfit', sans-serif; font-size: 13px;
      font-weight: 600; letter-spacing: 0.1em; text-transform: uppercase;
      border: none; cursor: pointer;
      transition: background var(--transition-fast);
    }
    .form-submit:hover { background: var(--gold-light); }


    @media (max-width: 1100px) {
      .calendar-layout { grid-template-columns: 1fr; }
      .calendar-main { padding: 40px; border-right: none; border-bottom: 1px solid rgba(201,168,76,0.08); }
      .calendar-sidebar { padding: 40px; }
      .upcoming-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 640px) {
      .page-header, .upcoming-strip, footer { padding-left: 20px; padding-right: 20px; }
      .calendar-main, .calendar-sidebar { padding: 24px 20px; }
      .upcoming-grid { grid-template-columns: 1fr; }
      .cal-nav { flex-direction: column; align-items: flex-start; gap: 12px; }
      footer { flex-direction: column; gap: 12px; }
    }
  </style>
<link rel="icon" type="image/png" href="assets/images/logo.png">
</head>
<body>

  <div class="cursor" id="cursor"></div>
  <div class="cursor-ring" id="cursorRing"></div>

  <?php include 'include/header.php'; ?>

  <!-- Page Header -->
  <div class="page-header">
    <div class="page-header-orb"></div>
    <div class="page-eyebrow">DHLTU SRC — Campus Calendar</div>
    <h1 class="page-title"><em>Mark</em> Your Calendar</h1>
    <p class="page-subtitle">All upcoming SRC elections, events, and key dates for the <?= $year ?>/<?= $year + 1 ?> academic year.</p>
  </div>

  <!-- Calendar + Sidebar -->
  <div class="calendar-layout">
    <div class="calendar-main">
      <div class="cal-nav">
        <a href="?month=<?= $prev->format('n') ?>&amp;year=<?= $prev->format('Y') ?>" class="cal-month">&lsaquo; <?= htmlspecialchars($prev->format('F Y')) ?></a>
        <a href="?month=<?= $today->format('n') ?>&amp;year=<?= $today->format('Y') ?>" class="cal-today-btn" title="Jump to today">Today</a>
        <a href="?month=<?= $next->format('n') ?>&amp;year=<?= $next->format('Y') ?>" class="cal-month"><?= htmlspecialchars($next->format('F Y')) ?> &rsaquo;</a>
        <div class="cal-nav-btns">
          <a href="?month=<?= $prev->format('n') ?>&amp;year=<?= $prev->format('Y') ?>" class="cal-nav-btn" title="Previous Month">&#8249;</a>
          <a href="?month=<?= $next->format('n') ?>&amp;year=<?= $next->format('Y') ?>" class="cal-nav-btn" title="Next Month">&#8250;</a>
        </div>
      </div>

      <div class="cal-grid">
        <?php foreach ($dows as $dow): ?>
          <div class="cal-dow"><?= $dow ?></div>
        <?php endforeach; ?>

        <?php
        // Leading empty cells for days before the 1st of the current viewed month
        $firstDayDow = (int)$calDate->format('w');
        $prevMonth  = (clone $calDate)->modify('first day of last month');
        $prevDaysInMonth = (int)$prevMonth->format('t');
        for ($i = 0; $i < $firstDayDow; $i++) {
            $prevDay = $prevDaysInMonth - $firstDayDow + $i + 1;
            echo '<div class="cal-cell other-month"><span class="cal-day">' . $prevDay . '</span></div>';
        }
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $cls = 'cal-cell';
            if ($isTodayFn($d)) $cls .= ' today';
            if (isset($gridEvents[$d]) && count($gridEvents[$d]) > 0) $cls .= ' has-event';

            // Selected day via session click (stored in $_SESSION would go here; plain-JS state for now)
            $sel = '';
        ?>
          <div class="<?= $cls . ' ' . $sel ?>"
               onclick="this.classList.toggle('selected')">
            <span class="cal-day"><?= $d ?></span>
            <?php foreach (($gridEvents[$d] ?? []) as $ev): ?>
              <span class="cal-event-dot <?= htmlspecialchars($ev['color']) ?>">
                <?= htmlspecialchars($ev['label']) ?>
              </span>
            <?php endforeach; ?>
          </div>
        <?php } ?>

        <?php
        $totalCells    = $firstDayDow + $daysInMonth;
        $trailingNeeded = (7 - ($totalCells % 7)) % 7;
        for ($i = 1; $i <= $trailingNeeded; $i++):
        ?>
          <div class="cal-cell other-month"><span class="cal-day"><?= $i ?></span></div>
        <?php endfor; ?>
      </div>
    </div>

    <!-- Sidebar -->
    <div class="calendar-sidebar">
      <div class="sidebar-title">Upcoming Events — <?= htmlspecialchars($calDate->format('F Y')) ?></div>

      <?php if (empty($sidebarEvents)): ?>
      <div class="no-data">
        <p>No events scheduled this month yet.</p>
      </div>
      <?php else: ?>
      <div class="event-list">
        <?php foreach ($sidebarEvents as $ev): ?>
        <a href="#" class="event-item" onclick="return false;">
          <div class="event-date-block">
            <span class="event-date-day"><?= $ev['day'] ?></span>
            <span class="event-date-month"><?= $ev['month'] ?></span>
          </div>
          <div class="event-info">
            <span class="event-tag <?= $ev['tagClass'] ?>"
                  style="<?= !$ev['tagClass'] ? 'background:' . htmlspecialchars($ev['tagColor']) . ';' : '' ?>">
              <?= htmlspecialchars($ev['tag']) ?>
            </span>
            <div class="event-name"><?= htmlspecialchars($ev['name']) ?></div>
            <div class="event-time" data-time="<?= htmlspecialchars($ev['time']) ?>"><?= htmlspecialchars($ev['time']) ?></div>
            <?php if (!empty($ev['excerpt'])): ?>
            <div style="font-size:10.5px;color:rgba(245,240,232,.28);margin-top:5px;line-height:1.5;overflow:hidden;text-overflow:ellipsis;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;"><?= htmlspecialchars($ev['excerpt']) ?></div>
            <?php endif; ?>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <div class="sidebar-divider"></div>

      <div class="sidebar-subtitle">Event Categories</div>
      <div class="category-legend">
        <?php foreach ($legendEntries as $leg): ?>
        <div class="legend-item">
          <span class="legend-dot <?= htmlspecialchars(strtolower($leg['color'])) ?>"></span>
          <span class="legend-label">
            <?= htmlspecialchars($leg['label']) ?>
            <?= $leg['count'] ? '<small>(' . (int)$leg['count'] . ')</small>' : '' ?>
          </span>
        </div>
        <?php endforeach; ?>
      </div>

      <a href="#" class="add-event-btn" onclick="return false;">+ Submit an Event</a>
    </div>
  </div>

  <!-- All Upcoming Events Strip -->
  <section class="upcoming-strip">
    <div class="section-label">All Upcoming Events — <?= totalEventCount() ?> Recorded</div>

    <?php if (empty($cardEvents)): ?>
    <div class="no-data" style="padding:40px 0;">
      <div class="no-data-title">No Upcoming Events</div>
      <p>There are no upcoming events on record. New events will appear here as soon as they are published.</p>
    </div>
    <?php else: ?>
    <div class="upcoming-grid">
      <?php foreach ($cardEvents as $ev):
        $evMonth = $ev['source'] === 'election'
                 ? strtoupper(date('M', strtotime($sidebarRaws[array_search($ev, $cardEvents)]['sortKey'] ?? time())))
                 : $ev['month'];
        // Re-derive safe month display
        $dispDate = $ev['source'] === 'election'
            ? date('d M Y', strtotime($sidebarRaws[array_search($ev, array_values($sidebarRaws))]['sortKey'] ?? time()))
            : $ev['month'] . ' ' . $ev['day'] . ', ' . date('Y');
      ?>
      <a href="#" class="upcoming-card" onclick="return false;">
        <div class="upcoming-card-date"><?= date('d M Y', max((int)$ev['sortKey'], strtotime('2000-01-01'))) ?></div>
        <div class="upcoming-card-title"><?= htmlspecialchars($ev['name']) ?></div>
        <div class="upcoming-card-loc"><?= htmlspecialchars($ev['time']) ?></div>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </section>

  <!-- Submit Event Modal -->
  <div class="modal-overlay" id="submitEventModal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
    <div class="modal-box">
      <button class="modal-close" id="closeEventModal" aria-label="Close">&times;</button>
      <h2 class="modal-title" id="modalTitle">Submit an Event</h2>
      <p class="modal-subtitle">Fill in the details below. Your event will appear on the calendar for all to see.</p>

      <?php if ($submitError): ?>
      <div class="form-error"><?= htmlspecialchars($submitError) ?></div>
      <?php endif; ?>
      <?php if ($submitSuccess): ?>
      <div class="form-success"><?= htmlspecialchars($submitSuccess) ?></div>
      <?php endif; ?>

      <form method="POST" action="events-calendar.php" id="submitEventForm">
        <input type="hidden" name="submit_event" value="1">
        <input type="hidden" name="_month" value="<?= (int)$month ?>">
        <input type="hidden" name="_year"  value="<?= (int)$year ?>">

        <div class="form-group">
          <label for="event_title" class="form-label">Event Title <span class="required">*</span></label>
          <input type="text" id="event_title" name="event_title" class="form-input" placeholder="e.g. Annual General Assembly" required maxlength="200">
        </div>

        <div class="form-group">
          <label for="event_date" class="form-label">Event Date <span class="required">*</span></label>
          <input type="date" id="event_date" name="event_date" class="form-input" required>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="event_start_time" class="form-label">Start Time</label>
            <input type="time" id="event_start_time" name="event_start_time" class="form-input">
          </div>
          <div class="form-group">
            <label for="event_location" class="form-label">Location</label>
            <input type="text" id="event_location" name="event_location" class="form-input" placeholder="e.g. Main Hall">
          </div>
        </div>

        <div class="form-group">
          <label for="event_excerpt" class="form-label">Short Description <span class="required">*</span></label>
          <textarea id="event_excerpt" name="event_excerpt" class="form-textarea" required maxlength="500" placeholder="A brief headline summary for the calendar…"></textarea>
        </div>

        <div class="form-group">
          <label for="event_content" class="form-label">Full Event Details</label>
          <textarea id="event_content" name="event_content" class="form-textarea" style="min-height:150px;" placeholder="Optional — full description including agenda, speakers, or how to join…"></textarea>
        </div>

        <div class="form-group">
          <label class="form-check">
            <input type="checkbox" name="agreed_terms" value="1" required>
            I confirm this event is accurate and I have permission to publish it publicly.
          </label>
        </div>

        <button type="submit" class="form-submit">Publish Event</button>
      </form>
    </div>
  </div>

  

    <?php include 'include/footer.php'; ?>

    <!-- Success toast for submitted events -->
    <?php if ($submitSuccess): ?>
    <div id="submitToast" style="position:fixed;bottom:32px;left:50%;transform:translateX(-50%);background:#166534;color:#ecfccb;padding:12px 28px;font-size:13px;letter-spacing:0.1em;font-family:'Outfit',sans-serif;z-index:9999;border:1px solid rgba(34,197,94,.35);border-radius:0;display:flex;align-items:center;gap:12px;min-width:300px;">&#10004; <?= htmlspecialchars($submitSuccess) ?></div>
    <script>setTimeout(function(){var t=document.getElementById('submitToast');if(t)t.style.display='none';},6000);</script>
    <?php endif; ?>

</html>
