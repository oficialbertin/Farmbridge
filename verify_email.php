<?php
// Manual email verification page - works when email sending fails
require 'db.php';
require 'email_verification_helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$message = '';
$message_type = 'info';
$verification_url = '';

$token = $_GET['token'] ?? '';

if (empty($token)) {
    $message = 'No verification token provided.';
    $message_type = 'danger';
} else {
    // Check if this is a manual verification
    $manual_file = __DIR__ . '/pending_verifications.json';
    if (file_exists($manual_file)) {
        $pending = json_decode(file_get_contents($manual_file), true) ?: [];
        
        foreach ($pending as $index => $verification) {
            if ($verification['token'] === $token && $verification['status'] === 'pending') {
                // Mark as verified
                $pending[$index]['status'] = 'verified';
                $pending[$index]['verified_at'] = date('Y-m-d H:i:s');
                file_put_contents($manual_file, json_encode($pending, JSON_PRETTY_PRINT));
                
                // Update database
                if (verify_email_token($conn, $token)) {
                    $message = 'Your email address has been successfully verified! You can now log in.';
                    $message_type = 'success';
                } else {
                    $message = 'Email verification completed, but there was an issue updating your account. Please contact support.';
                    $message_type = 'warning';
                }
                break;
            }
        }
        
        if ($message === '') {
            $message = 'Invalid or expired verification token.';
            $message_type = 'danger';
        }
    } else {
        // Try normal verification
        if (verify_email_token($conn, $token)) {
            $message = 'Your email address has been successfully verified! You can now log in.';
            $message_type = 'success';
        } else {
            $message = 'Invalid or expired verification token. Please try registering again or contact support.';
            $message_type = 'danger';
        }
    }
}

include 'header.php';
?>
<main class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-<?= $message_type ?> text-white">
                    <h4 class="mb-0"><i class="bi bi-info-circle"></i> Email Verification</h4>
                </div>
                <div class="card-body">
                    <div class="alert alert-<?= $message_type ?>"><?= htmlspecialchars($message) ?></div>
                    
                    <?php if ($message_type === 'success'): ?>
                        <div class="text-center mt-4">
                            <a href="login.php" class="btn btn-primary btn-lg">
                                <i class="bi bi-box-arrow-in-right"></i> Go to Login
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="text-center mt-4">
                            <a href="register.php" class="btn btn-outline-primary me-2">
                                <i class="bi bi-person-plus"></i> Try Registration Again
                            </a>
                            <a href="login.php" class="btn btn-outline-secondary">
                                <i class="bi bi-box-arrow-in-right"></i> Go to Login
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>
<?php include 'footer.php'; ?>