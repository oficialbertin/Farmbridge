<?php
require 'db.php';
require 'email_smtp_working.php';
require 'email_simple_smtp.php';
require 'email_php_mail.php';
require 'session_helper.php';

// Session already started by session_helper.php

// Only admins can test email
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$test_result = '';
$test_email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $test_email = trim($_POST['test_email'] ?? '');
    
    if ($test_email && filter_var($test_email, FILTER_VALIDATE_EMAIL)) {
        $result = ['success' => false, 'error' => ''];
        $method_used = '';
        
        // Try multiple email methods in order of preference
        if (!$result['success'] && is_mail_available()) {
            $result = send_test_email_php_mail($test_email);
            if ($result['success']) $method_used = 'PHP mail() function';
        }
        
        if (!$result['success']) {
            $result = send_test_email_simple_smtp($test_email);
            if ($result['success']) $method_used = 'Simplified SMTP';
        }
        
        if (!$result['success']) {
            $result = send_test_email_smtp($test_email);
            if ($result['success']) $method_used = 'Complex SMTP';
        }
        
        if ($result['success']) {
            $test_result = '<div class="alert alert-success">
                <i class="bi bi-check-circle"></i> <strong>Email sent successfully!</strong><br>
                Test email sent to ' . htmlspecialchars($test_email) . ' using ' . $method_used . '. Check your inbox.
            </div>';
        } else {
            $test_result = '<div class="alert alert-danger">
                <i class="bi bi-x-circle"></i> <strong>All email methods failed:</strong><br>
                ' . htmlspecialchars($result['error']) . '<br><br>
                <strong>Troubleshooting:</strong><br>
                • Check if your server allows outbound email<br>
                • Verify Gmail App Password is correct<br>
                • Contact your hosting provider about email restrictions
            </div>';
        }
    } else {
        $test_result = '<div class="alert alert-danger">Please provide a valid email address.</div>';
    }
}

// Test SMTP connection
$connection_test = test_smtp_connection();
$config = get_smtp_config();

include 'header.php';
?>
<main class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white">
                    <h2 class="mb-0"><i class="bi bi-envelope-check"></i> SMTP Email Configuration Test</h2>
                </div>
                <div class="card-body">
                    
                    <!-- Configuration Status -->
                    <div class="mb-4">
                        <h4><i class="bi bi-gear"></i> Current SMTP Configuration</h4>
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-sm">
                                    <tr><td><strong>SMTP Host:</strong></td><td><?= htmlspecialchars($config['host']) ?></td></tr>
                                    <tr><td><strong>SMTP Port:</strong></td><td><?= htmlspecialchars($config['port']) ?></td></tr>
                                    <tr><td><strong>Security:</strong></td><td><?= strtoupper($config['secure']) ?></td></tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-sm">
                                    <tr><td><strong>Username:</strong></td><td><?= htmlspecialchars($config['username']) ?></td></tr>
                                    <tr><td><strong>From Email:</strong></td><td><?= htmlspecialchars($config['from_email']) ?></td></tr>
                                    <tr><td><strong>From Name:</strong></td><td><?= htmlspecialchars($config['from_name']) ?></td></tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Connection Test -->
                    <div class="mb-4">
                        <h4><i class="bi bi-wifi"></i> Connection Test</h4>
                        <?php if ($connection_test['success']): ?>
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle"></i> <strong>SMTP Connection Successful!</strong><br>
                                Successfully connected to <?= htmlspecialchars($config['host']) ?>:<?= htmlspecialchars($config['port']) ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-danger">
                                <i class="bi bi-x-circle"></i> <strong>SMTP Connection Failed:</strong><br>
                                <?= htmlspecialchars($connection_test['error']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Test Email Form -->
                    <div class="mb-4">
                        <h4><i class="bi bi-send"></i> Send Test Email</h4>
                        <form method="post" class="d-flex gap-2">
                            <input type="email" name="test_email" class="form-control" 
                                   placeholder="Enter email address to test" 
                                   value="<?= htmlspecialchars($test_email) ?>" required>
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-send"></i> Send Test Email
                            </button>
                        </form>
                        <?= $test_result ?>
                    </div>
                    
                    <!-- Configuration Help -->
                    <div class="alert alert-info">
                        <h5><i class="bi bi-info-circle"></i> SMTP Configuration Help</h5>
                        <p><strong>Current Configuration File:</strong> <code>email_secret.php</code></p>
                        
                        <h6>For Gmail (Recommended):</h6>
                        <ol>
                            <li>Enable 2-factor authentication on your Google account</li>
                            <li>Generate an App Password: Google Account → Security → App passwords</li>
                            <li>Update <code>email_secret.php</code> with your Gmail credentials</li>
                        </ol>
                        
                        <h6>For Other Email Providers:</h6>
                        <ul>
                            <li><strong>Outlook/Hotmail:</strong> smtp-mail.outlook.com:587</li>
                            <li><strong>Yahoo:</strong> smtp.mail.yahoo.com:587</li>
                            <li><strong>Custom SMTP:</strong> Contact your email provider for settings</li>
                        </ul>
                        
                        <div class="mt-3">
                            <a href="admin.php" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left"></i> Back to Admin Dashboard
                            </a>
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>
    </div>
</main>
<?php include 'footer.php'; ?>
