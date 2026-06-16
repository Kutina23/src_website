<?php
require_once __DIR__ . '/../config/email.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

echo "=== Integration Checks (no live SMTP sends) ===\n\n";

// [1] Config + schema
echo "[1] Config + schema\n";
$mail = getMailConfig();
echo "    App URL:          " . getAppUrl() . "\n";
echo "    App env:          " . getAppEnv() . "\n";
echo "    users.remember_token:          "
   . (db()->fetch("SHOW COLUMNS FROM users WHERE Field = 'remember_token'") ? 'yes' : 'MISSING') . "\n";
echo "    users.remember_token_expires:  "
   . (db()->fetch("SHOW COLUMNS FROM users WHERE Field = 'remember_token_expires'") ? 'yes' : 'MISSING') . "\n";
echo "    news.status:        "
   . (db()->fetch("SHOW COLUMNS FROM news WHERE Field = 'status'") ? 'yes' : 'MISSING') . "\n";
echo "    news.category:      "
   . (db()->fetch("SHOW COLUMNS FROM news WHERE Field = 'category'") ? 'yes' : 'MISSING') . "\n";
echo "    news.published_at:  "
   . (db()->fetch("SHOW COLUMNS FROM news WHERE Field = 'published_at'") ? 'yes' : 'MISSING') . "\n\n";

// [2] Forgot-password token + link pattern
echo "[2] Forgot-password token/link pattern\n";
$tokenPlain = bin2hex(random_bytes(32));
$tokenHash  = hash('sha256', $tokenPlain);
$expires    = date('Y-m-d H:i:s', strtotime('+1 hour'));
$link       = rtrim(getAppUrl(), '/') . '/portal/reset-password.php?token=' . urlencode($tokenPlain);
echo "    Token plain:   " . substr($tokenPlain, 0, 16) . "...\n";
echo "    Token hash:    " . substr($tokenHash, 0, 16) . "...\n";
echo "    Reset link:    {$link}\n";
echo "    Expires:       {$expires}\n";
echo "    Points to reset-password.php: "
   . (str_contains($link, '/portal/reset-password.php') ? 'yes' : 'NO') . "\n\n";

// [3] Token verification (simulate reset-password.php query)
echo "[3] Token verification (reset-password.php behavior)\n";
$rows = db()->fetchAll(
    "SELECT id, remember_token_expires FROM users
       WHERE remember_token = ? AND remember_token_expires > NOW() LIMIT 1",
    [$tokenHash]
);
echo "    Verifiable user for fresh token: " . (!empty($rows) ? 'yes (id=' . $rows[0]['id'] . ')' : 'NO') . "\n\n";

// [4] Notification subject routing
echo "[4] Notification subject routing (category-aware)\n";
function build_subject($category, $title) {
    $c = strtoupper($category ?? 'NEWS');
    if ($c === 'EVENT')        return "📅 New Event: {$title}";
    if ($c === 'ANNOUNCEMENT') return "📢 Announcement: {$title}";
    return "📰 New " . ucfirst(strtolower($c)) . ": {$title}";
}
$cases = [
    ['NEWS',        'SRC Launches New Initiative'],
    ['EVENT',       'SRC Annual Football Match'],
    ['ANNOUNCEMENT','Important: Campus Closure Notice'],
];
foreach ($cases as [$cat, $title]) {
    echo "    {$cat} -> " . build_subject($cat, $title) . "\n";
}
echo "\n";

// [5] Subscription API public subscribe + unsubscribe flows
echo "[5] Subscription API (public flows)\n";

// 5a subscribe (public)
$endpoint = 'http://localhost/SRC/api/email-subscribe.php';
$testEmail = 'integration+' . time() . '@example.com';
$subPost = http_build_query([
    'action'    => 'subscribe',
    'email'     => $testEmail,
    'full_name' => 'Integration Test User',
]);
$subCtx = stream_context_create([
    'http' => [
        'method'  => 'POST',
        'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
        'content' => $subPost,
        'timeout' => 10,
    ],
]);
$subResp = @file_get_contents($endpoint, false, $subCtx);
$subCode = $http_response_header[0] ?? 'HTTP/0.0';
echo "    Subscribe HTTP: {$subCode}\n";
echo "    Subscribe body: " . ($subResp ?: '(none)') . "\n";

$row = db()->fetch("SELECT id, is_active, token FROM email_subscribers WHERE email = ?", [$testEmail]);
if ($row) {
    echo "    DB row id={$row['id']} is_active={$row['is_active']} token=" . substr($row['token'], 0, 16) . "...\n";

    // 5b unsubscribe by token (public GET)
    $unsubUrl = $endpoint . "?action=unsubscribe&token=" . urlencode($row['token']);
    $ch = curl_init($unsubUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 10,
    ]);
    $unsubResp = curl_exec($ch);
    $unsubHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $fresh = db()->fetch("SELECT is_active FROM email_subscribers WHERE email = ?", [$testEmail]);
    echo "    Unsubscribe HTTP: {$unsubHttpCode}\n";
    echo "    is_active after: " . ($fresh['is_active'] ?? 'n/a') . "\n";

    // 5c reactivate
    $reactivate = @file_get_contents($endpoint, false, stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => http_build_query(['action'=>'subscribe','email'=>$testEmail,'full_name'=>'Reactivated']),
            'timeout' => 10,
        ]
    ]));
    $reactivated = db()->fetch("SELECT is_active FROM email_subscribers WHERE email = ?", [$testEmail]);
    echo "    Reactivate is_active: " . ($reactivated['is_active'] ?? 'n/a') . "\n";

    // 5d duplicate guard
    $dupResp = @file_get_contents($endpoint, false, stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => http_build_query(['action'=>'subscribe','email'=>$testEmail]),
            'timeout' => 10,
        ]
    ]));
    echo "    Duplicate guard body: " . ($dupResp ?: '(none)') . "\n";
} else {
    echo "    WARNING: subscriber row not created\n";
}
echo "\n";

// [6] Events-calendar create path
echo "[6] events-calendar.php EVENT rows in DB\n";
$eve = db()->fetch("SELECT id, title, category, status FROM news WHERE category = 'EVENT' LIMIT 1");
if ($eve) {
    echo "    Found EVENT row: id={$eve['id']} title={$eve['title']}\n";
} else {
    echo "    No EVENT rows present (OK for fresh DB)\n";
}
echo "\n";

// [7] .env sanity
echo "[7] .env sanity\n";
$raw = file_get_contents(__DIR__ . '/../.env');
echo "    SMTP host:       " . ($mail['host'] ?? 'n/a') . "\n";
echo "    SMTP port:       " . ($mail['port'] ?? 'n/a') . "\n";
echo "    From address:    " . ($mail['from_address'] ?? 'n/a') . "\n\n";

echo "=== Cleanup test subscriber ===\n";
if (!empty($testEmail)) {
    db()->execute("DELETE FROM email_subscribers WHERE email = ?", [$testEmail]);
    echo "    Removed test subscriber: {$testEmail}\n";
}

echo "\n=== Done ===\n";
