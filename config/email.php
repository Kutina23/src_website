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

function smtpSend($smtp, $command) {
    fwrite($smtp, $command . "\r\n");
}

function smtpReadLine($smtp) {
    $line = @fgets($smtp);
    if ($line === false) {
        error_log("SMTP read failed (command: " . trim($command ?? '') . ")");
        return null;
    }
    return rtrim($line, "\r\n");
}

function smtpReadResponse($smtp, $commandLabel) {
    $response = '';
    $start = time();
    $timeout = 15;
    while ((time() - $start) < $timeout) {
        $line = smtpReadLine($smtp);
        if ($line === null) {
            error_log("SMTP: Connection closed waiting for response after: $commandLabel");
            return null;
        }
        $response .= $line;
        $code = (int)substr($line, 0, 3);
        if (!isset($line[3]) || $line[3] !== '-') {
            break;
        }
    }
    return $response;
}

function sendEmailSMTP($to, $subject, $body, $headers = []) {
    $config = getMailConfig();
    
    if (empty($config['username']) || empty($config['password'])) {
        error_log('Gmail SMTP credentials not configured. Add MAIL_USERNAME and MAIL_PASSWORD to .env file');
        return false;
    }
    
    $envelopeFrom = !empty($config['username']) ? $config['username'] : $config['from_address'];
    
    $defaultHeaders = [
        'From' => $config['from_address'],
        'Sender' => $envelopeFrom,
        'Reply-To' => $config['reply_to'],
        'MIME-Version' => '1.0',
        'X-Mailer' => 'PHP/' . phpversion(),
        'Date' => date('D, d M Y H:i:s T'),
        'Message-ID' => '<' . uniqid() . '.' . hash('sha256', $to . $subject . microtime()) . '@gmail.com>',
        'Content-Type' => 'text/html; charset=UTF-8',
    ];
    $headers = array_merge($defaultHeaders, $headers);
    
    $headerString = '';
    foreach ($headers as $key => $value) {
        $headerString .= "{$key}: {$value}\r\n";
    }
    
    $host = $config['host'];
    $port = $config['port'];
    $encryption = $config['encryption'];
    
    $smtp = @fsockopen($host, $port, $errno, $errstr, 15);
    if (!$smtp) {
        error_log("SMTP connection failed: $errstr ($errno)");
        return false;
    }
    
    stream_set_blocking($smtp, 1);
    stream_set_timeout($smtp, 30);
    
    // Read initial banner
    $line = smtpReadLine($smtp);
    if ($line === null) {
        error_log("SMTP: No initial banner from server");
        @fclose($smtp);
        return false;
    }
    $bannerCode = (int)substr($line, 0, 3);
    if ($bannerCode !== 220) {
        error_log("SMTP: Unexpected banner: {$line}");
        @fclose($smtp);
        return false;
    }
    
    // EHLO
    smtpSend($smtp, "EHLO localhost");
    $ehloResponse = smtpReadResponse($smtp, 'EHLO');
    if (!$ehloResponse) {
        error_log('SMTP EHLO failed');
        @fclose($smtp);
        return false;
    }
    
    // STARTTLS
    if ($encryption === 'tls') {
        smtpSend($smtp, "STARTTLS");
        $starttlsLine = smtpReadLine($smtp);
        if ($starttlsLine === null || (int)substr($starttlsLine, 0, 3) !== 220) {
            error_log('SMTP STARTTLS failed: ' . trim($starttlsLine));
            @fclose($smtp);
            return false;
        }
        
        $tlsOk = stream_socket_enable_crypto($smtp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        if (!$tlsOk) {
            error_log('SMTP TLS handshake failed after STARTTLS');
            @fclose($smtp);
            return false;
        }
        
        smtpSend($smtp, "EHLO localhost");
        $ehloResponse = smtpReadResponse($smtp, 'EHLO (post-TLS)');
        if (!$ehloResponse) {
            error_log('SMTP EHLO after TLS failed');
            @fclose($smtp);
            return false;
        }
    }
    
    // AUTH LOGIN
    smtpSend($smtp, "AUTH LOGIN");
    $authLine = smtpReadLine($smtp);
    if ($authLine === null || (int)substr($authLine, 0, 3) !== 334) {
        error_log('SMTP AUTH LOGIN not accepted: ' . trim($authLine));
        @fclose($smtp);
        return false;
    }
    
    smtpSend($smtp, base64_encode($config['username']));
    $userLine = smtpReadLine($smtp);
    if ($userLine === null || (int)substr($userLine, 0, 3) !== 334) {
        error_log('SMTP username not accepted: ' . trim($userLine));
        @fclose($smtp);
        return false;
    }
    
    smtpSend($smtp, base64_encode($config['password']));
    $passLine = smtpReadLine($smtp);
    if ($passLine === null || (int)substr($passLine, 0, 3) !== 235) {
        error_log('SMTP authentication failed: ' . trim($passLine));
        @fclose($smtp);
        return false;
    }
    
    // MAIL FROM — must match the authenticated Gmail account or it will be silently dropped
    $envelopeFrom = !empty($config['username']) ? $config['username'] : $config['from_address'];
    smtpSend($smtp, "MAIL FROM: <{$envelopeFrom}>");
    $mailLine = smtpReadLine($smtp);
    if ($mailLine === null || (int)substr($mailLine, 0, 3) !== 250) {
        error_log('SMTP MAIL FROM rejected: ' . trim($mailLine));
        @fclose($smtp);
        return false;
    }
    
    // RCPT TO
    smtpSend($smtp, "RCPT TO: <{$to}>");
    $rcptLine = smtpReadLine($smtp);
    if ($rcptLine === null || (int)substr($rcptLine, 0, 3) !== 250) {
        error_log('SMTP RCPT TO rejected: ' . trim($rcptLine));
        @fclose($smtp);
        return false;
    }
    
    // DATA
    smtpSend($smtp, "DATA");
    $dataLine = smtpReadLine($smtp);
    if ($dataLine === null || (int)substr($dataLine, 0, 3) !== 354) {
        error_log('SMTP DATA not accepted: ' . trim($dataLine));
        @fclose($smtp);
        return false;
    }
    
    // Message
    $message = "To: {$to}\r\n";
    $message .= "Subject: {$subject}\r\n";
    $message .= $headerString;
    $message .= "\r\n{$body}\r\n.";
    
    smtpSend($smtp, $message);
    $finalLine = smtpReadLine($smtp);
    
    @fclose($smtp);
    
    if ($finalLine === null || (int)substr($finalLine, 0, 3) !== 250) {
        error_log('SMTP message rejected: ' . trim($finalLine));
        return false;
    }
    
    return true;
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