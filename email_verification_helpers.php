<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/email_helpers.php';

function email_verification_ensure_table(mysqli $conn): void {
    $conn->query("CREATE TABLE IF NOT EXISTS email_verifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(191) NOT NULL,
        token VARCHAR(32) NOT NULL,
        expires_at TIMESTAMP NOT NULL,
        verified_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_email_token (email, token),
        INDEX idx_expires (expires_at)
    )");
}

function generate_verification_token(): string {
    return bin2hex(random_bytes(16));
}

function send_verification_email(string $email, string $token): array {
    require_once __DIR__ . '/email_simple_helpers.php';
    
    $verification_url = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . 
                       ($_SERVER['HTTP_HOST'] ?? 'localhost') . 
                       '/FarmBridgeAI/verify_email.php?token=' . urlencode($token);
    
    $subject = 'Verify Your FarmBridge AI Account';
    $html_body = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
        <h2 style='color: #2e7d32;'>Welcome to FarmBridge AI!</h2>
        <p>Thank you for registering. Please verify your email address by clicking the button below:</p>
        <div style='text-align: center; margin: 30px 0;'>
            <a href='{$verification_url}' 
               style='background: #2e7d32; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block;'>
                Verify Email Address
            </a>
        </div>
        <p>Or copy and paste this link in your browser:</p>
        <p style='word-break: break-all; color: #666;'>{$verification_url}</p>
        <p>This verification link will expire in 24 hours.</p>
        <p>If you didn't create this account, please ignore this email.</p>
        <hr style='margin: 30px 0; border: none; border-top: 1px solid #eee;'>
        <p style='color: #666; font-size: 12px;'>FarmBridge AI - Connecting Farmers & Buyers in Rwanda</p>
    </div>";
    
    return send_email_simple($email, $subject, $html_body);
}

function create_verification_record(mysqli $conn, string $email): string {
    email_verification_ensure_table($conn);
    
    // Clean up expired tokens
    $conn->query("DELETE FROM email_verifications WHERE expires_at < NOW()");
    
    $token = generate_verification_token();
    $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));
    
    $stmt = $conn->prepare("INSERT INTO email_verifications (email, token, expires_at) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $email, $token, $expires_at);
    $stmt->execute();
    
    return $token;
}

function verify_email_token(mysqli $conn, string $token): ?string {
    email_verification_ensure_table($conn);
    
    $stmt = $conn->prepare("SELECT email FROM email_verifications WHERE token = ? AND expires_at > NOW() AND verified_at IS NULL");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Mark as verified
        $stmt = $conn->prepare("UPDATE email_verifications SET verified_at = NOW() WHERE token = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        
        return $row['email'];
    }
    
    return null;
}

function is_email_verified(mysqli $conn, string $email): bool {
    email_verification_ensure_table($conn);
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM email_verifications WHERE email = ? AND verified_at IS NOT NULL");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return (int)$row['count'] > 0;
}

function validate_real_email(string $email): bool {
    // Basic format check
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    
    // Extract domain
    $domain = substr(strrchr($email, "@"), 1);
    
    // Check for common fake email patterns
    $fake_patterns = [
        '10minutemail.com', 'guerrillamail.com', 'mailinator.com', 
        'tempmail.org', 'throwaway.email', 'yopmail.com',
        'temp-mail.org', 'getnada.com', 'maildrop.cc'
    ];
    
    if (in_array(strtolower($domain), $fake_patterns)) {
        return false;
    }
    
    // Check if domain has MX record (basic check)
    if (!checkdnsrr($domain, 'MX')) {
        return false;
    }
    
    return true;
}

