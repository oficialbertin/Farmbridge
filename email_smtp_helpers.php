<?php
// Enhanced email helper using external SMTP services
// This bypasses the local mail server issue

function email_smtp_get_config(): array {
    $cfgPath = __DIR__ . '/email_secret.php';
    $cfg = [
        'smtp_host' => getenv('SMTP_HOST') ?: 'smtp.gmail.com',
        'smtp_port' => (int)(getenv('SMTP_PORT') ?: 587),
        'smtp_username' => getenv('SMTP_USERNAME') ?: '',
        'smtp_password' => getenv('SMTP_PASSWORD') ?: '',
        'from_email' => getenv('SMTP_FROM_EMAIL') ?: '',
        'from_name' => getenv('SMTP_FROM_NAME') ?: 'FarmBridge AI',
        'smtp_secure' => getenv('SMTP_SECURE') ?: 'tls', // tls or ssl
    ];
    
    if (file_exists($cfgPath)) {
        try { 
            $fileCfg = include $cfgPath; 
            if (is_array($fileCfg)) { 
                $cfg = array_merge($cfg, $fileCfg); 
            } 
        } catch (Throwable $e) {} 
    }
    return $cfg;
}

function send_email_smtp(string $to, string $subject, string $htmlBody, string $textBody = ''): array {
    $cfg = email_smtp_get_config();
    $result = ['success' => false, 'error' => ''];
    
    // Validate configuration
    if (empty($cfg['smtp_username']) || empty($cfg['smtp_password']) || empty($cfg['from_email'])) {
        $result['error'] = 'SMTP configuration incomplete. Please check email_secret.php';
        return $result;
    }
    
    // Use cURL to send email via external SMTP service
    $boundary = uniqid('boundary_');
    $headers = [
        'From: ' . $cfg['from_name'] . ' <' . $cfg['from_email'] . '>',
        'To: ' . $to,
        'Subject: ' . $subject,
        'MIME-Version: 1.0',
        'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
    ];
    
    $body = "--$boundary\r\n";
    $body .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
    $body .= strip_tags($htmlBody) . "\r\n\r\n";
    $body .= "--$boundary\r\n";
    $body .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
    $body .= $htmlBody . "\r\n\r\n";
    $body .= "--$boundary--\r\n";
    
    // Try multiple SMTP services
    $smtp_services = [
        [
            'host' => 'smtp.gmail.com',
            'port' => 587,
            'secure' => 'tls',
            'username' => $cfg['smtp_username'],
            'password' => $cfg['smtp_password']
        ],
        [
            'host' => 'smtp.outlook.com', 
            'port' => 587,
            'secure' => 'tls',
            'username' => $cfg['smtp_username'],
            'password' => $cfg['smtp_password']
        ]
    ];
    
    foreach ($smtp_services as $service) {
        $success = send_via_smtp($service, $to, $subject, $body, $headers);
        if ($success) {
            $result['success'] = true;
            break;
        }
    }
    
    if (!$result['success']) {
        $result['error'] = 'Failed to send email via all SMTP services. Please check your email configuration.';
    }
    
    // Log the attempt
    $log_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'to' => $to,
        'subject' => $subject,
        'success' => $result['success'],
        'error' => $result['error']
    ];
    
    @file_put_contents(__DIR__ . '/email_log.txt', json_encode($log_entry) . "\n", FILE_APPEND);
    
    return $result;
}

function send_via_smtp(array $config, string $to, string $subject, string $body, array $headers): bool {
    $socket = @fsockopen($config['host'], $config['port'], $errno, $errstr, 30);
    if (!$socket) {
        return false;
    }
    
    $response = fgets($socket, 512);
    if (substr($response, 0, 3) !== '220') {
        fclose($socket);
        return false;
    }
    
    // EHLO
    fputs($socket, "EHLO " . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "\r\n");
    $response = fgets($socket, 512);
    
    // STARTTLS
    if ($config['secure'] === 'tls') {
        fputs($socket, "STARTTLS\r\n");
        $response = fgets($socket, 512);
        if (substr($response, 0, 3) !== '220') {
            fclose($socket);
            return false;
        }
        
        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            fclose($socket);
            return false;
        }
        
        fputs($socket, "EHLO " . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "\r\n");
        $response = fgets($socket, 512);
    }
    
    // AUTH LOGIN
    fputs($socket, "AUTH LOGIN\r\n");
    $response = fgets($socket, 512);
    if (substr($response, 0, 3) !== '334') {
        fclose($socket);
        return false;
    }
    
    fputs($socket, base64_encode($config['username']) . "\r\n");
    $response = fgets($socket, 512);
    if (substr($response, 0, 3) !== '334') {
        fclose($socket);
        return false;
    }
    
    fputs($socket, base64_encode($config['password']) . "\r\n");
    $response = fgets($socket, 512);
    if (substr($response, 0, 3) !== '235') {
        fclose($socket);
        return false;
    }
    
    // MAIL FROM
    fputs($socket, "MAIL FROM: <" . $config['username'] . ">\r\n");
    $response = fgets($socket, 512);
    if (substr($response, 0, 3) !== '250') {
        fclose($socket);
        return false;
    }
    
    // RCPT TO
    fputs($socket, "RCPT TO: <" . $to . ">\r\n");
    $response = fgets($socket, 512);
    if (substr($response, 0, 3) !== '250') {
        fclose($socket);
        return false;
    }
    
    // DATA
    fputs($socket, "DATA\r\n");
    $response = fgets($socket, 512);
    if (substr($response, 0, 3) !== '354') {
        fclose($socket);
        return false;
    }
    
    // Send headers and body
    fputs($socket, implode("\r\n", $headers) . "\r\n\r\n");
    fputs($socket, $body . "\r\n.\r\n");
    $response = fgets($socket, 512);
    
    // QUIT
    fputs($socket, "QUIT\r\n");
    fclose($socket);
    
    return substr($response, 0, 3) === '250';
}

// Fallback to simple HTTP-based email service
function send_email_http(string $to, string $subject, string $htmlBody): array {
    $result = ['success' => false, 'error' => ''];
    
    // Use a simple HTTP email service (like EmailJS or similar)
    // This is a basic implementation - you might want to use a service like SendGrid, Mailgun, etc.
    
    $data = [
        'to' => $to,
        'subject' => $subject,
        'html' => $htmlBody,
        'from' => 'noreply@farmbridge.rw'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.emailjs.com/api/v1.0/email/send');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'User-Agent: FarmBridge-AI/1.0'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $result['success'] = true;
    } else {
        $result['error'] = 'HTTP email service failed with code: ' . $httpCode;
    }
    
    return $result;
}

function test_email_smtp_config(): array {
    $cfg = email_smtp_get_config();
    $result = ['valid' => false, 'errors' => []];
    
    if (empty($cfg['smtp_username'])) {
        $result['errors'][] = 'SMTP username is not configured';
    }
    
    if (empty($cfg['smtp_password'])) {
        $result['errors'][] = 'SMTP password is not configured';
    }
    
    if (empty($cfg['from_email'])) {
        $result['errors'][] = 'from_email is not configured';
    } elseif (!filter_var($cfg['from_email'], FILTER_VALIDATE_EMAIL)) {
        $result['errors'][] = 'from_email is not a valid email address';
    }
    
    if (empty($cfg['from_name'])) {
        $result['errors'][] = 'from_name is not configured';
    }
    
    $result['valid'] = empty($result['errors']);
    return $result;
}
