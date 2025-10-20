<?php
// Working SMTP email helper using PHPMailer
// This will properly handle SMTP connections

// Check if PHPMailer is available, if not, we'll include a basic version
if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    // For now, we'll use a simple SMTP implementation
    // In production, you should install PHPMailer via Composer: composer require phpmailer/phpmailer
}

function get_smtp_config(): array {
    // Check if we're on production server
    $is_production = in_array($_SERVER['HTTP_HOST'] ?? '', [
        'web.farmbridgeai.rw',
        'www.farmbridgeai.rw',
        'www.farmbridge.rw',
        'farmbridge.rw'
    ]);
    
    if ($is_production && file_exists(__DIR__ . '/email_production.php')) {
        $config = include 'email_production.php';
        return [
            'host' => $config['smtp_host'] ?? 'smtp.gmail.com',
            'port' => (int)($config['smtp_port'] ?? 587),
            'username' => $config['smtp_username'] ?? '',
            'password' => $config['smtp_password'] ?? '',
            'from_email' => $config['from_email'] ?? '',
            'from_name' => $config['from_name'] ?? 'FarmBridge AI',
            'secure' => $config['smtp_secure'] ?? 'tls'
        ];
    }
    
    // Fallback to local configuration
    $config_file = __DIR__ . '/email_secret.php';
    
    if (file_exists($config_file)) {
        $config = include $config_file;
        return [
            'host' => $config['smtp_host'] ?? 'smtp.gmail.com',
            'port' => (int)($config['smtp_port'] ?? 587),
            'username' => $config['smtp_username'] ?? '',
            'password' => $config['smtp_password'] ?? '',
            'from_email' => $config['from_email'] ?? '',
            'from_name' => $config['from_name'] ?? 'FarmBridge AI',
            'secure' => $config['smtp_secure'] ?? 'tls'
        ];
    }
    
    return [
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'username' => '',
        'password' => '',
        'from_email' => '',
        'from_name' => 'FarmBridge AI',
        'secure' => 'tls'
    ];
}

function send_email_smtp_working(string $to, string $subject, string $htmlBody): array {
    $config = get_smtp_config();
    $result = ['success' => false, 'error' => ''];
    
    // Validate configuration
    if (empty($config['username']) || empty($config['password']) || empty($config['from_email'])) {
        $result['error'] = 'SMTP configuration incomplete. Please check email_secret.php file.';
        return $result;
    }
    
    // Try with TLS first, then fallback to non-TLS if it fails
    $result = send_email_with_tls($config, $to, $subject, $htmlBody);
    if (!$result['success'] && $config['secure'] === 'tls') {
        // Try without TLS as fallback
        $config_no_tls = $config;
        $config_no_tls['secure'] = 'none';
        $config_no_tls['port'] = 25; // Port 25 often works without TLS
        $result = send_email_with_tls($config_no_tls, $to, $subject, $htmlBody);
        if (!$result['success']) {
            $result['error'] = 'TLS failed, and non-TLS also failed: ' . $result['error'];
        } else {
            $result['error'] = 'Sent without TLS (TLS failed): ' . $result['error'];
        }
    }
    
    return $result;
}

function send_email_with_tls(array $config, string $to, string $subject, string $htmlBody): array {
    $result = ['success' => false, 'error' => ''];
    
    // Use socket connection for SMTP
    $socket = fsockopen($config['host'], $config['port'], $errno, $errstr, 30);
    
    if (!$socket) {
        $result['error'] = "Could not connect to SMTP server: $errstr ($errno)";
        return $result;
    }
    
    try {
        // Read initial response
        $response = fgets($socket, 512);
        if (substr($response, 0, 3) !== '220') {
            throw new Exception("SMTP server error: $response");
        }
        
        // Send EHLO
        fputs($socket, "EHLO " . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "\r\n");
        $response = fgets($socket, 512);
        
        // Start TLS if required
        if ($config['secure'] === 'tls') {
            fputs($socket, "STARTTLS\r\n");
            $response = fgets($socket, 512);
            
            // Some servers return 220 for STARTTLS, others return 250
            if (substr($response, 0, 3) !== '220' && substr($response, 0, 3) !== '250') {
                throw new Exception("STARTTLS failed: $response");
            }
            
            // Enable crypto with multiple methods for better compatibility
            $crypto_methods = [
                STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT,
                STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT,
                STREAM_CRYPTO_METHOD_TLSv1_0_CLIENT,
                STREAM_CRYPTO_METHOD_TLS_CLIENT
            ];
            
            $crypto_enabled = false;
            foreach ($crypto_methods as $method) {
                if (stream_socket_enable_crypto($socket, true, $method)) {
                    $crypto_enabled = true;
                    break;
                }
            }
            
            if (!$crypto_enabled) {
                // If TLS fails, try without TLS (some servers have TLS issues)
                throw new Exception("TLS failed, trying without encryption");
            }
            
            // Send EHLO again after TLS
            fputs($socket, "EHLO " . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "\r\n");
            $response = fgets($socket, 512);
        }
        
        // Authenticate - Gmail requires specific handling
        fputs($socket, "AUTH LOGIN\r\n");
        $response = fgets($socket, 512);
        if (substr($response, 0, 3) !== '334') {
            throw new Exception("AUTH LOGIN failed: $response");
        }
        
        // Send username (base64 encoded)
        $username_encoded = base64_encode($config['username']);
        fputs($socket, $username_encoded . "\r\n");
        $response = fgets($socket, 512);
        if (substr($response, 0, 3) !== '334') {
            throw new Exception("Username authentication failed: $response");
        }
        
        // Send password (base64 encoded) - Gmail App Password
        $password_encoded = base64_encode($config['password']);
        fputs($socket, $password_encoded . "\r\n");
        $response = fgets($socket, 512);
        
        // Gmail might return 535 for authentication failure
        if (substr($response, 0, 3) !== '235') {
            throw new Exception("Password authentication failed: $response (Check your Gmail App Password)");
        }
        
        // Send MAIL FROM
        fputs($socket, "MAIL FROM: <" . $config['from_email'] . ">\r\n");
        $response = fgets($socket, 512);
        if (substr($response, 0, 3) !== '250') {
            throw new Exception("MAIL FROM failed: $response");
        }
        
        // Send RCPT TO
        fputs($socket, "RCPT TO: <$to>\r\n");
        $response = fgets($socket, 512);
        if (substr($response, 0, 3) !== '250') {
            throw new Exception("RCPT TO failed: $response");
        }
        
        // Send DATA
        fputs($socket, "DATA\r\n");
        $response = fgets($socket, 512);
        if (substr($response, 0, 3) !== '354') {
            throw new Exception("DATA command failed: $response");
        }
        
        // Send email headers and body
        $email_data = "From: " . $config['from_name'] . " <" . $config['from_email'] . ">\r\n";
        $email_data .= "To: $to\r\n";
        $email_data .= "Subject: $subject\r\n";
        $email_data .= "MIME-Version: 1.0\r\n";
        $email_data .= "Content-Type: text/html; charset=UTF-8\r\n";
        $email_data .= "\r\n";
        $email_data .= $htmlBody . "\r\n";
        $email_data .= ".\r\n";
        
        fputs($socket, $email_data);
        $response = fgets($socket, 512);
        if (substr($response, 0, 3) !== '250') {
            throw new Exception("Email sending failed: $response");
        }
        
        // Send QUIT
        fputs($socket, "QUIT\r\n");
        fclose($socket);
        
        $result['success'] = true;
        
    } catch (Exception $e) {
        fclose($socket);
        $result['error'] = $e->getMessage();
    }
    
    return $result;
}

function test_smtp_connection(): array {
    $config = get_smtp_config();
    $result = ['success' => false, 'error' => '', 'config' => $config];
    
    if (empty($config['username']) || empty($config['password']) || empty($config['from_email'])) {
        $result['error'] = 'SMTP configuration incomplete. Please check email_secret.php file.';
        return $result;
    }
    
    // Test connection
    $socket = fsockopen($config['host'], $config['port'], $errno, $errstr, 10);
    
    if (!$socket) {
        $result['error'] = "Could not connect to SMTP server: $errstr ($errno)";
        return $result;
    }
    
    // Read initial response
    $response = fgets($socket, 512);
    fclose($socket);
    
    if (substr($response, 0, 3) === '220') {
        $result['success'] = true;
        $result['error'] = '';
    } else {
        $result['error'] = "SMTP server error: $response";
    }
    
    return $result;
}

function send_test_email_smtp(string $to): array {
    $subject = 'FarmBridge AI - SMTP Test Email';
    $htmlBody = '
    <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #2e7d32;">🎉 SMTP Email Test Successful!</h2>
        <p>Congratulations! Your SMTP email configuration is working properly.</p>
        
        <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <h4>Test Details:</h4>
            <ul>
                <li><strong>Sent to:</strong> ' . htmlspecialchars($to) . '</li>
                <li><strong>Sent at:</strong> ' . date('Y-m-d H:i:s') . '</li>
                <li><strong>From:</strong> FarmBridge AI</li>
                <li><strong>Status:</strong> ✅ Working</li>
            </ul>
        </div>
        
        <p>You can now use email verification and notifications throughout the platform.</p>
        
        <hr style="margin: 30px 0; border: none; border-top: 1px solid #eee;">
        <p style="color: #666; font-size: 12px;">FarmBridge AI - Connecting Farmers & Buyers in Rwanda</p>
    </div>';
    
    return send_email_smtp_working($to, $subject, $htmlBody);
}
