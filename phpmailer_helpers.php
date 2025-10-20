<?php
// Simple PHPMailer wrapper for better email delivery
// This uses the built-in mail() function but with better error handling

function send_email_phpmailer(string $to, string $subject, string $htmlBody, string $textBody = ''): array {
    require_once __DIR__ . '/email_helpers.php';
    
    $cfg = email_get_config();
    $result = ['success' => false, 'error' => ''];
    
    // Validate configuration
    if (empty($cfg['from_email'])) {
        $result['error'] = 'No from_email configured';
        return $result;
    }
    
    if (empty($cfg['from_name'])) {
        $cfg['from_name'] = 'FarmBridge AI';
    }
    
    // Prepare headers
    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . $cfg['from_name'] . ' <' . $cfg['from_email'] . '>',
        'Reply-To: ' . $cfg['from_email'],
        'X-Mailer: FarmBridge AI PHP/' . phpversion(),
        'X-Priority: 3'
    ];
    
    // Try to send email
    $success = @mail($to, $subject, $htmlBody, implode("\r\n", $headers));
    
    if ($success) {
        $result['success'] = true;
    } else {
        $last_error = error_get_last();
        $result['error'] = $last_error ? $last_error['message'] : 'Unknown mail error';
    }
    
    // Log the attempt
    $log_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'to' => $to,
        'subject' => $subject,
        'from_email' => $cfg['from_email'],
        'success' => $success,
        'error' => $result['error']
    ];
    
    @file_put_contents(__DIR__ . '/email_log.txt', json_encode($log_entry) . "\n", FILE_APPEND);
    
    return $result;
}

function test_email_config(): array {
    $cfg = email_get_config();
    $result = ['valid' => false, 'errors' => []];
    
    if (empty($cfg['from_email'])) {
        $result['errors'][] = 'from_email is not configured';
    } elseif (!filter_var($cfg['from_email'], FILTER_VALIDATE_EMAIL)) {
        $result['errors'][] = 'from_email is not a valid email address';
    }
    
    if (empty($cfg['from_name'])) {
        $result['errors'][] = 'from_name is not configured';
    }
    
    // Check if mail function is available
    if (!function_exists('mail')) {
        $result['errors'][] = 'PHP mail() function is not available';
    }
    
    $result['valid'] = empty($result['errors']);
    return $result;
}
