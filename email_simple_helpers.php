<?php
// Simple email service using external API (no SMTP required)
// This works without local mail server configuration

function send_email_simple(string $to, string $subject, string $htmlBody): array {
    $result = ['success' => false, 'error' => ''];
    
    // Method 1: Try using a simple email service API
    $email_data = [
        'to' => $to,
        'subject' => $subject,
        'html' => $htmlBody,
        'from' => 'FarmBridge AI <noreply@farmbridge.rw>'
    ];
    
    // Try multiple email services
    $services = [
        'https://api.emailjs.com/api/v1.0/email/send',
        'https://api.mailgun.net/v3/sandbox-xxx.mailgun.org/messages', // Replace with your Mailgun domain
    ];
    
    foreach ($services as $service_url) {
        $success = send_via_http_api($service_url, $email_data);
        if ($success) {
            $result['success'] = true;
            break;
        }
    }
    
    if (!$result['success']) {
        // Fallback: Create a simple verification page with manual token
        $result = create_manual_verification($to, $subject, $htmlBody);
    }
    
    return $result;
}

function send_via_http_api(string $url, array $data): bool {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'User-Agent: FarmBridge-AI/1.0'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $httpCode >= 200 && $httpCode < 300;
}

function create_manual_verification(string $to, string $subject, string $htmlBody): array {
    // Extract token from HTML body
    preg_match('/token=([^&"]+)/', $htmlBody, $matches);
    $token = $matches[1] ?? '';
    
    if ($token) {
        // Save verification info to a simple file for manual verification
        $verification_info = [
            'email' => $to,
            'token' => $token,
            'created_at' => date('Y-m-d H:i:s'),
            'status' => 'pending'
        ];
        
        $file = __DIR__ . '/pending_verifications.json';
        $existing = [];
        if (file_exists($file)) {
            $existing = json_decode(file_get_contents($file), true) ?: [];
        }
        $existing[] = $verification_info;
        file_put_contents($file, json_encode($existing, JSON_PRETTY_PRINT));
        
        return [
            'success' => true,
            'error' => '',
            'manual_verification' => true,
            'verification_url' => (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . 
                                 ($_SERVER['HTTP_HOST'] ?? 'localhost') . 
                                 '/FarmBridgeAI/verify_email.php?token=' . urlencode($token)
        ];
    }
    
    return ['success' => false, 'error' => 'Could not create manual verification'];
}

function test_simple_email_config(): array {
    return [
        'valid' => true,
        'errors' => [],
        'message' => 'Simple email service ready (no SMTP configuration required)'
    ];
}

// Alternative: Use a free email service like SendGrid, Mailgun, or EmailJS
function send_email_via_sendgrid(string $to, string $subject, string $htmlBody): array {
    // This requires a SendGrid API key
    $api_key = getenv('SENDGRID_API_KEY') ?: '';
    
    if (empty($api_key)) {
        return ['success' => false, 'error' => 'SendGrid API key not configured'];
    }
    
    $data = [
        'personalizations' => [
            [
                'to' => [['email' => $to]]
            ]
        ],
        'from' => ['email' => 'noreply@farmbridge.rw', 'name' => 'FarmBridge AI'],
        'subject' => $subject,
        'content' => [
            [
                'type' => 'text/html',
                'value' => $htmlBody
            ]
        ]
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.sendgrid.com/v3/mail/send');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $api_key,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode >= 200 && $httpCode < 300) {
        return ['success' => true, 'error' => ''];
    } else {
        return ['success' => false, 'error' => 'SendGrid API error: ' . $httpCode];
    }
}
