<?php

function loadEnv() {
    static $loaded = false;
    if ($loaded) return;
    
    $envFile = __DIR__ . '/../.env';
    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue;
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
    }
    $loaded = true;
}

loadEnv();

function getMailConfig() {
    return [
        'host' => getenv('MAIL_HOST') ?: ($_ENV['MAIL_HOST'] ?? 'smtp.gmail.com'),
        'port' => getenv('MAIL_PORT') ?: ($_ENV['MAIL_PORT'] ?? 587),
        'username' => getenv('MAIL_USERNAME') ?: ($_ENV['MAIL_USERNAME'] ?? ''),
        'password' => getenv('MAIL_PASSWORD') ?: ($_ENV['MAIL_PASSWORD'] ?? ''),
        'encryption' => getenv('MAIL_ENCRYPTION') ?: ($_ENV['MAIL_ENCRYPTION'] ?? 'tls'),
        'from_address' => getenv('MAIL_FROM_ADDRESS') ?: ($_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@src.com'),
        'from_name' => getenv('MAIL_FROM_NAME') ?: ($_ENV['MAIL_FROM_NAME'] ?? 'DHLTU SRC'),
        'reply_to' => getenv('MAIL_REPLY_TO') ?: ($_ENV['MAIL_REPLY_TO'] ?? 'info@src.com'),
    ];
}

function getDbConfig() {
    return [
        'host' => getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? 'localhost'),
        'dbname' => getenv('DB_NAME') ?: ($_ENV['DB_NAME'] ?? 'dhltusrc_db'),
        'username' => getenv('DB_USER') ?: ($_ENV['DB_USER'] ?? 'root'),
        'password' => getenv('DB_PASS') ?: ($_ENV['DB_PASS'] ?? ''),
    ];
}

function getAppUrl() {
    return rtrim(getenv('APP_URL') ?: ($_ENV['APP_URL'] ?? ''), '/');
}

function getAppEnv() {
    return getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'local');
}

function getAppBaseUrl() {
    $url = getAppUrl();
    return $url ? rtrim($url, '/') : '';
}

function sendEmailSMTP($to, $subject, $body, $headers = []) {
    $config = getMailConfig();
    
    if (empty($config['username']) || empty($config['password'])) {
        error_log('Gmail SMTP credentials not configured. Add MAIL_USERNAME and MAIL_PASSWORD to .env file');
        return false;
    }
    
    $defaultHeaders = [
        'From' => $config['from_address'],
        'Content-Type' => 'text/html; charset=UTF-8',
        'Reply-To' => $config['reply_to']
    ];
    $headers = array_merge($defaultHeaders, $headers);
    
    $headerString = '';
    foreach ($headers as $key => $value) {
        $headerString .= "{$key}: {$value}\r\n";
    }
    
    $host = $config['host'];
    $port = $config['port'];
    
    $smtp = @fsockopen($host, $port, $errno, $errstr, 15);
    if (!$smtp) {
        error_log("SMTP connection failed: $errstr ($errno)");
        return false;
    }
    
    stream_set_timeout($smtp, 15);
    
    $response = @fgets($smtp);
    if ($response === false || substr($response, 0, 3) !== '220') {
        @fclose($smtp);
        error_log('SMTP server not responding');
        return false;
    }
    
    @fwrite($smtp, "EHLO localhost\r\n");
    $response = '';
    while (($line = @fgets($smtp)) !== false && isset($line[3]) && $line[3] == '-') {
        $response .= $line;
    }
    
    if ($config['encryption'] === 'tls') {
        @fwrite($smtp, "STARTTLS\r\n");
        $starttlsResponse = @fgets($smtp);
        if ($starttlsResponse === false || substr($starttlsResponse, 0, 3) !== '220') {
            @fclose($smtp);
            error_log('SMTP STARTTLS failed');
            return false;
        }
        $tlsOk = @stream_socket_enable_crypto($smtp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        if (!$tlsOk) {
            @fclose($smtp);
            error_log('SMTP TLS handshake failed');
            return false;
        }
        @fwrite($smtp, "EHLO localhost\r\n");
        $line = '';
        while (($line = @fgets($smtp)) !== false && strlen($line) > 3 && $line[3] == '-') {}
    }
    
    @fwrite($smtp, "AUTH LOGIN\r\n");
    $authResponse = @fgets($smtp);
    if ($authResponse === false) {
        @fclose($smtp);
        error_log('SMTP AUTH not accepted');
        return false;
    }
    
    @fwrite($smtp, base64_encode($config['username']) . "\r\n");
    @fgets($smtp);
    @fwrite($smtp, base64_encode($config['password']) . "\r\n");
    $authResponse = @fgets($smtp);
    
    if ($authResponse === false || substr($authResponse, 0, 3) !== '235') {
        @fclose($smtp);
        error_log('SMTP authentication failed');
        return false;
    }
    
    @fwrite($smtp, "MAIL FROM: <{$config['from_address']}>\r\n");
    @fgets($smtp);
    @fwrite($smtp, "RCPT TO: <{$to}>\r\n");
    @fgets($smtp);
    @fwrite($smtp, "DATA\r\n");
    @fgets($smtp);
    
    $message = "To: {$to}\r\n";
    $message .= "Subject: {$subject}\r\n";
    $message .= $headerString;
    $message .= "\r\n{$body}\r\n.";
    
    @fwrite($smtp, $message);
    $dataResponse = @fgets($smtp);
    @fwrite($smtp, "QUIT\r\n");
    @fclose($smtp);
    
    return $dataResponse !== false && substr($dataResponse, 0, 3) === '250';
}

function sendEmail($to, $subject, $body, $headers = []) {
    $config = getMailConfig();
    
    if (!empty($config['username']) && !empty($config['password'])) {
        return sendEmailSMTP($to, $subject, $body, $headers);
    }
    
    $defaultHeaders = [
        'From' => $config['from_address'] ?? 'noreply@src.com',
        'Content-Type' => 'text/html; charset=UTF-8'
    ];
    $headers = array_merge($defaultHeaders, $headers);
    $headerString = '';
    foreach ($headers as $key => $value) {
        $headerString .= "{$key}: {$value}\r\n";
    }
    
    return mail($to, $subject, $body, $headerString);
}
?>