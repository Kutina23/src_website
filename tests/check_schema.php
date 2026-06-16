<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
foreach (['users','ga_sessions','news'] as $table) {
    echo "$table:\n";
    $q = db()->query("DESCRIBE $table");
    foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $c) {
        echo "  {$c['Field']} {$c['Type']}\n";
    }
}
echo "\n";
$hasToken = db()->fetch("SHOW COLUMNS FROM users WHERE Field = 'remember_token'");
echo "users.remember_token exists: " . ($hasToken ? 'yes' : 'no') . "\n";
$hasTokenExpires = db()->fetch("SHOW COLUMNS FROM users WHERE Field = 'remember_token_expires'");
echo "users.remember_token_expires exists: " . ($hasTokenExpires ? 'yes' : 'no') . "\n";