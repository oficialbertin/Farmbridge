<?php
// Simplified SMTP implementation for Gmail
// This bypasses some of the complex TLS issues

function send_email_simple_smtp(string $to, string $subject, string $htmlBody): array {
    $config = get_smtp_config();
    $result = ['success' => false, 'error' => ''];
    
    // Validate configuration
    if (empty($config['username']) || empty($config['password']) || empty($config['from_email'])) {
        $result['error'] = 'SMTP configuration incomplete.';
        return $result;
    }
    
    // Try multiple SMTP configurations
    $configs = [
        // Gmail with TLS
        [
            'host' => 'smtp.gmail.com',
            'port' => 587,
            'secure' => 'tls'
        ],
        // Gmail with SSL
        [
            'host' => 'smtp.gmail.com',
            'port' => 465,
            'secure' => 'ssl'
        ],
        // Gmail without encryption (port 25)
        [
            'host' => 'smtp.gmail.com',
            'port' => 25,
            'secure' => 'none'
        ]
    ];
    
    foreach ($configs as $smtp_config) {
        $test_config = array_merge($config, $smtp_config);
        $result = try_smtp_send($test_config, $to, $subject, $htmlBody);
        
        if ($result['success']) {
            return $result;
        }
    }
    
    return $result;
}

function try_smtp_send(array $config, string $to, string $subject, string $htmlBody): array {
    $result = ['success' => false, 'error' => ''];
    
    // Create socket
    $context = stream_context_create();
    if ($config['secure'] === 'ssl') {
        $socket = stream_socket_client("ssl://{$config['host']}:{$config['port']}", $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context);
    } else {
        $socket = fsockopen($config['host'], $config['port'], $errno, $errstr, 30);
    }
    
    if (!$socket) {
        $result['error'] = "Connection failed: $errstr ($errno)";
        return $result;
    }
    
    try {
        // Read initial response
        $response = fgets($socket, 512);
        if (substr($response, 0, 3) !== '220') {
            throw new Exception("SMTP error: $response");
        }
        
        // Send EHLO and read all responses
        fputs($socket, "EHLO " . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "\r\n");
        
        // Read all EHLO responses (Gmail sends multiple lines)
        $ehlo_responses = [];
        while (true) {
            $response = fgets($socket, 512);
            $ehlo_responses[] = trim($response);
            if (substr($response, 3, 1) === ' ') {
                break; // Last line of EHLO response
            }
        }
        
        // Handle TLS for non-SSL connections
        if ($config['secure'] === 'tls') {
            fputs($socket, "STARTTLS\r\n");
            $response = fgets($socket, 512);
            
            if (substr($response, 0, 3) === '220' || substr($response, 0, 3) === '250') {
                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new Exception("TLS encryption failed");
                }
                
                // Send EHLO again after TLS and read all responses
                fputs($socket, "EHLO " . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "\r\n");
                while (true) {
                    $response = fgets($socket, 512);
                    if (substr($response, 3, 1) === ' ') {
                        break; // Last line of EHLO response
                    }
                }
            }
        }
        
        // Authenticate
        fputs($socket, "AUTH LOGIN\r\n");
        $response = fgets($socket, 512);
        if (substr($response, 0, 3) !== '334') {
            throw new Exception("AUTH LOGIN failed: $response");
        }
        
        // Send username
        fputs($socket, base64_encode($config['username']) . "\r\n");
        $response = fgets($socket, 512);
        if (substr($response, 0, 3) !== '334') {
            throw new Exception("Username failed: $response");
        }
        
        // Send password
        fputs($socket, base64_encode($config['password']) . "\r\n");
        $response = fgets($socket, 512);
        if (substr($response, 0, 3) !== '235') {
            throw new Exception("Password failed: $response");
        }
        
        // Send email
        fputs($socket, "MAIL FROM: <" . $config['from_email'] . ">\r\n");
        $response = fgets($socket, 512);
        if (substr($response, 0, 3) !== '250') {
            throw new Exception("MAIL FROM failed: $response");
        }
        
        fputs($socket, "RCPT TO: <$to>\r\n");
        $response = fgets($socket, 512);
        if (substr($response, 0, 3) !== '250') {
            throw new Exception("RCPT TO failed: $response");
        }
        
        fputs($socket, "DATA\r\n");
        $response = fgets($socket, 512);
        if (substr($response, 0, 3) !== '354') {
            throw new Exception("DATA failed: $response");
        }
        
        // Send email content
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
            throw new Exception("Email send failed: $response");
        }
        
        fputs($socket, "QUIT\r\n");
        fclose($socket);
        
        $result['success'] = true;
        
    } catch (Exception $e) {
        fclose($socket);
        $result['error'] = $e->getMessage();
    }
    
    return $result;
}

function send_test_email_simple_smtp(string $to): array {
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
    
    return send_email_simple_smtp($to, $subject, $htmlBody);
}
?>
