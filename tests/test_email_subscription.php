<?php
echo "===========================================\n";
echo "   Email & Subscription Locale Test Suite   \n";
echo "===========================================\n\n";

$baseUrl = "http://localhost/SRC";

// [1] .env / config validation
echo "[1] Config validation\n";
require_once __DIR__ . '/../config/email.php';
$mail = getMailConfig();
echo "    SMTP host:      {$mail['host']}\n";
echo "    SMTP port:      {$mail['port']}\n";
echo "    Username:       {$mail['username']}\n";
echo "    Password set:   " . (strlen($mail['password']) > 0 ? 'yes' : 'no') . "\n";
echo "    From address:   {$mail['from_address']}\n";
echo "    App URL:        " . getAppUrl() . "\n";
echo "    App env:        " . getAppEnv() . "\n\n";

// [2] DB + table check
echo "[2] Database & table check\n";
require_once __DIR__ . '/../config/database.php';
$db = db();
$tables = $db->fetchAll("SHOW TABLES LIKE 'email_subscribers'");
if (empty($tables)) {
    echo "    !! email_subscribers table is MISSING\n";
    echo "    Create it before running email workflows.\n\n";
} else {
    $count = $db->fetch("SELECT COUNT(*) as c FROM email_subscribers")['c'];
    $active = $db->fetch("SELECT COUNT(*) as c FROM email_subscribers WHERE is_active = TRUE")['c'];
    echo "    Table exists. Total={$count}, Active={$active}\n\n";
}

// [3] Subscription API – subscribe
echo "[3] Subscription API (public)\n";
$endpoint = $baseUrl . "/api/email-subscribe.php";
$testEmail = "webhooktest+" . time() . "@gmail.com";
$testName  = "Test User";

$post = http_build_query([
    'action'     => 'subscribe',
    'email'      => $testEmail,
    'full_name'  => $testName,
]);
$opts = [
    'http' => [
        'method'  => 'POST',
        'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
        'content' => $post,
        'timeout' => 15,
    ],
];
$context = stream_context_create($opts);
$resp = @file_get_contents($endpoint, false, $context);
$respCode = $http_response_header[0] ?? 'HTTP/1.0 000';
echo "    Endpoint:  $endpoint\n";
echo "    HTTP:      $respCode\n";
echo "    Response:  " . ($resp ?: '(no body)') . "\n\n";

if (strpos($resp ?? '', '"success":true') !== false && !empty($tables)) {
    // verify row
    $row = $db->fetch("SELECT * FROM email_subscribers WHERE email = ?", [$testEmail]);
    echo "    DB row:    " . ($row ? 'created (id=' . $row['id'] . ')' : 'NOT FOUND') . "\n";
    echo "    is_active: " . ($row['is_active'] ?? 'n/a') . "\n\n";

    // [4] Unsubscribe token roundtrip
    echo "[4] Unsubscribe via token\n";
    $unsubUrl = $endpoint . "?action=unsubscribe&token=" . ($row['token'] ?? '');
    $ch = curl_init($unsubUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_HEADER => false,
    ]);
    $unsubResp = curl_exec($ch);
    curl_close($ch);
    $fresh = $db->fetch("SELECT is_active FROM email_subscribers WHERE email = ?", [$testEmail]);
    echo "    Token:     " . ($row['token'] ?? '') . "\n";
    echo "    Response:  " . ($unsubResp ?: '(routed redirect)') . "\n";
    echo "    is_active: " . ($fresh['is_active'] ?? 'n/a') . "\n\n";

    // [5] Reactivate (re-subscribe)
    echo "[5] Reactivation (re-subscribe)\n";
    $post2 = http_build_query([
        'action'    => 'subscribe',
        'email'     => $testEmail,
        'full_name' => $testName,
    ]);
    $opts2 = [
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => $post2,
            'timeout' => 15,
        ],
    ];
    $resp2 = @file_get_contents($endpoint, false, stream_context_create($opts2));
    $reactivated = $db->fetch("SELECT is_active FROM email_subscribers WHERE email = ?", [$testEmail]);
    echo "    Response:  " . ($resp2 ?: '(no body)') . "\n";
    echo "    is_active: " . ($reactivated['is_active'] ?? 'n/a') . "\n\n";
}

// [6] Duplicate prevention
echo "[6] Duplicate-subscribe guard\n";
$dupPost = http_build_query([
    'action'    => 'subscribe',
    'email'     => $testEmail,
    'full_name' => $testName,
]);
$optsD = [
    'http' => [
        'method'  => 'POST',
        'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
        'content' => $dupPost,
        'timeout' => 15,
    ],
];
$respD = @file_get_contents($endpoint, false, stream_context_create($optsD));
echo "    Response:  " . ($respD ?: '(no body)') . "\n\n";

// [7] SMTP live test (send to self)
echo "[7] SMTP live send (self-test)\n";
require_once __DIR__ . '/../config/functions.php';
$sendResult = sendEmail(
    $mail['username'],
    "[TEST] DHLTU SRC Mailer " . date('Y-m-d H:i'),
    "<h3>Hello from localhost</h3><p>Time: " . date('Y-m-d H:i:s') . "</p>"
);
echo "    Result:    " . ($sendResult ? 'SENT' : 'FAILED') . "\n";
if (!$sendResult) {
    echo "    Note: Inspect PHP error_log for SMTP diagnostics.\n";
}

echo "\n===========================================\n";
echo " Done.\n";
