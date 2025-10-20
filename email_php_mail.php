<?php
// PHP mail() function implementation as fallback
// This uses the server's local mail system

function send_email_php_mail(string $to, string $subject, string $htmlBody): array {
    $config = get_smtp_config();
    $result = ['success' => false, 'error' => ''];
    
    // Validate configuration
    if (empty($config['from_email'])) {
        $result['error'] = 'From email not configured.';
        return $result;
    }
    
    // Set headers for HTML email
    $headers = [
        'From: ' . $config['from_name'] . ' <' . $config['from_email'] . '>',
        'Reply-To: ' . $config['from_email'],
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'X-Mailer: PHP/' . phpversion()
    ];
    
    $headers_string = implode("\r\n", $headers);
    
    // Try to send email
    if (mail($to, $subject, $htmlBody, $headers_string)) {
        $result['success'] = true;
    } else {
        $result['error'] = 'PHP mail() function failed. Check server mail configuration.';
    }
    
    return $result;
}

function send_test_email_php_mail(string $to): array {
    $subject = 'FarmBridge AI - PHP Mail Test';
    $htmlBody = '
    <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #2e7d32;">🎉 PHP Mail Test Successful!</h2>
        <p>Congratulations! Your server\'s PHP mail() function is working properly.</p>
        
        <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <h4>Test Details:</h4>
            <ul>
                <li><strong>Sent to:</strong> ' . htmlspecialchars($to) . '</li>
                <li><strong>Sent at:</strong> ' . date('Y-m-d H:i:s') . '</li>
                <li><strong>From:</strong> FarmBridge AI</li>
                <li><strong>Method:</strong> PHP mail() function</li>
                <li><strong>Status:</strong> ✅ Working</li>
            </ul>
        </div>
        
        <p>This email was sent using the server\'s built-in mail system instead of SMTP.</p>
        
        <hr style="margin: 30px 0; border: none; border-top: 1px solid #eee;">
        <p style="color: #666; font-size: 12px;">FarmBridge AI - Connecting Farmers & Buyers in Rwanda</p>
    </div>';
    
    return send_email_php_mail($to, $subject, $htmlBody);
}

// Check if mail function is available
function is_mail_available(): bool {
    return function_exists('mail') && !ini_get('mail.add_x_header');
}
?>
