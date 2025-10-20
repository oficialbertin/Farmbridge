<?php
session_start();
require_once 'sms_config.php';
require_once 'sms_parse.php';

$result = null;
$error = null;
$success = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Initialize SMS with credentials
    hdev_sms::api_id('HDEV-23f7706f-1482-421c-84f8-be9137b708b4-ID');
    hdev_sms::api_key('HDEV-22e1623d-840c-4280-947a-1e88f2a043c1-KEY');
    
    if ($action === 'send_sms') {
        $sender_id = $_POST['sender_id'] ?? SMS_SENDER_ID;
        $phone = $_POST['phone'] ?? '';
        $message = $_POST['message'] ?? '';
        
        // Validate inputs
        if (empty($phone) || empty($message)) {
            $error = "Phone number and message are required!";
        } else {
            // Check if SMS is enabled
            if (!SMS_ENABLED) {
                $error = "SMS is currently disabled. Please enable it in sms_config.php and add your API credentials.";
            } else {
                // Send SMS
                $result = hdev_sms::send($sender_id, $phone, $message);
                
                if (isset($result->status) && $result->status === 'success') {
                    $success = "SMS sent successfully!";
                } else {
                    $error = "Failed to send SMS: " . ($result->message ?? 'Unknown error');
                }
            }
        }
    } elseif ($action === 'topup') {
        $phone = $_POST['topup_phone'] ?? '';
        $amount = $_POST['amount'] ?? '';
        
        // Validate inputs
        if (empty($phone) || empty($amount)) {
            $error = "Phone number and amount are required!";
        } else {
            // Check if SMS is enabled
            if (!SMS_ENABLED) {
                $error = "SMS is currently disabled. Please enable it in sms_config.php and add your API credentials.";
            } else {
                // Initiate top-up
                $result = hdev_sms::topup($phone, $amount);
                
                if (isset($result->status) && $result->status === 'success') {
                    $success = "Top-up initiated successfully! Transaction Ref: " . ($result->tx_ref ?? 'N/A');
                } else {
                    $error = "Failed to initiate top-up: " . ($result->message ?? 'Unknown error');
                }
            }
        }
    } elseif ($action === 'check_topup') {
        $tx_ref = $_POST['tx_ref'] ?? '';
        
        // Validate input
        if (empty($tx_ref)) {
            $error = "Transaction reference is required!";
        } else {
            // Check if SMS is enabled
            if (!SMS_ENABLED) {
                $error = "SMS is currently disabled. Please enable it in sms_config.php and add your API credentials.";
            } else {
                // Check top-up status
                $result = hdev_sms::get_topup($tx_ref);
                
                if (isset($result->status)) {
                    $success = "Top-up status retrieved successfully!";
                } else {
                    $error = "Failed to retrieve top-up status: " . ($result->message ?? 'Unknown error');
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMS Gateway Test - FarmBridgeAI</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            text-align: center;
            color: white;
            margin-bottom: 30px;
        }
        
        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        
        .header p {
            font-size: 1.1em;
            opacity: 0.9;
        }
        
        .config-status {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
            margin-left: 10px;
        }
        
        .status-enabled {
            background: #10b981;
            color: white;
        }
        
        .status-disabled {
            background: #ef4444;
            color: white;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }
        
        .alert-warning {
            background: #fef3c7;
            color: #92400e;
            border-left: 4px solid #f59e0b;
        }
        
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .card h2 {
            color: #667eea;
            margin-bottom: 20px;
            font-size: 1.5em;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #374151;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .result-box {
            background: #f9fafb;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
        }
        
        .result-box h3 {
            color: #374151;
            margin-bottom: 10px;
        }
        
        .result-box pre {
            background: #1f2937;
            color: #10b981;
            padding: 15px;
            border-radius: 6px;
            overflow-x: auto;
            font-size: 13px;
            line-height: 1.5;
        }
        
        .info-box {
            background: #eff6ff;
            border-left: 4px solid #3b82f6;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
        }
        
        .info-box h4 {
            color: #1e40af;
            margin-bottom: 10px;
        }
        
        .info-box ul {
            margin-left: 20px;
            color: #1e3a8a;
        }
        
        .info-box li {
            margin-bottom: 5px;
        }
        
        .char-counter {
            text-align: right;
            font-size: 12px;
            color: #6b7280;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📱 SMS Gateway Test</h1>
            <p>HDEV SMS Gateway Integration for FarmBridgeAI</p>
        </div>
        
        <!-- Configuration Status -->
        <div class="config-status">
            <h3>
                Configuration Status: 
                <?php if (SMS_ENABLED): ?>
                    <span class="status-badge status-enabled">✓ ENABLED</span>
                <?php else: ?>
                    <span class="status-badge status-disabled">✗ DISABLED</span>
                <?php endif; ?>
            </h3>
            <p style="margin-top: 10px; color: #6b7280;">
                <strong>API ID:</strong> <?php echo SMS_API_ID === 'Your_API_ID_Here' ? '⚠️ Not configured' : '✓ Configured'; ?><br>
                <strong>API Key:</strong> <?php echo SMS_API_KEY === 'Your_API_Key_Here' ? '⚠️ Not configured' : '✓ Configured'; ?><br>
                <strong>Sender ID:</strong> <?php echo SMS_SENDER_ID; ?>
            </p>
        </div>
        
        <?php if (!SMS_ENABLED || SMS_API_ID === 'Your_API_ID_Here'): ?>
        <div class="alert alert-warning">
            <strong>⚠️ Setup Required:</strong> Please configure your SMS credentials in <code>sms_config.php</code> before testing.
            <ol style="margin-top: 10px; margin-left: 20px;">
                <li>Get your API credentials from <a href="https://sms-api.hdev.rw" target="_blank" style="color: #92400e; font-weight: bold;">https://sms-api.hdev.rw</a></li>
                <li>Update <code>SMS_API_ID</code> and <code>SMS_API_KEY</code> in <code>sms_config.php</code></li>
                <li>Set <code>SMS_ENABLED</code> to <code>true</code></li>
            </ol>
        </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
        <div class="alert alert-success">
            ✓ <?php echo htmlspecialchars($success); ?>
        </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="alert alert-error">
            ✗ <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>
        
        <div class="grid">
            <!-- Send SMS Card -->
            <div class="card">
                <h2>📤 Send SMS</h2>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="send_sms">
                    
                    <div class="form-group">
                        <label for="sender_id">Sender ID:</label>
                        <input type="text" id="sender_id" name="sender_id" value="<?php echo SMS_SENDER_ID; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number:</label>
                        <input type="tel" id="phone" name="phone" placeholder="250788123456" required>
                        <small style="color: #6b7280;">Format: 250788123456 (Rwanda)</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="message">Message:</label>
                        <textarea id="message" name="message" placeholder="Enter your message here..." required oninput="updateCharCount()"></textarea>
                        <div class="char-counter">
                            <span id="charCount">0</span> / 160 characters
                        </div>
                    </div>
                    
                    <button type="submit" class="btn">Send SMS</button>
                </form>
                
                <div class="info-box">
                    <h4>💡 SMS Best Practices:</h4>
                    <ul>
                        <li>Keep messages under 160 characters</li>
                        <li>Use clear and concise language</li>
                        <li>Include sender identification</li>
                        <li>Verify phone number format</li>
                    </ul>
                </div>
            </div>
            
            <!-- Top-up Card -->
            <div class="card">
                <h2>💰 Account Top-up</h2>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="topup">
                    
                    <div class="form-group">
                        <label for="topup_phone">Phone Number:</label>
                        <input type="tel" id="topup_phone" name="topup_phone" placeholder="250788123456" required>
                        <small style="color: #6b7280;">Mobile Money number for payment</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="amount">Amount (RWF):</label>
                        <input type="number" id="amount" name="amount" placeholder="1000" min="100" required>
                    </div>
                    
                    <button type="submit" class="btn">Initiate Top-up</button>
                </form>
                
                <div class="info-box">
                    <h4>💡 Top-up Information:</h4>
                    <ul>
                        <li>Minimum amount: 100 RWF</li>
                        <li>Payment via Mobile Money</li>
                        <li>Instant credit after payment</li>
                        <li>Save transaction reference</li>
                    </ul>
                </div>
            </div>
            
            <!-- Check Top-up Status Card -->
            <div class="card">
                <h2>🔍 Check Top-up Status</h2>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="check_topup">
                    
                    <div class="form-group">
                        <label for="tx_ref">Transaction Reference:</label>
                        <input type="text" id="tx_ref" name="tx_ref" placeholder="HDEVSMS-1234567890" required>
                        <small style="color: #6b7280;">Enter the transaction reference from top-up</small>
                    </div>
                    
                    <button type="submit" class="btn">Check Status</button>
                </form>
                
                <div class="info-box">
                    <h4>💡 Status Check:</h4>
                    <ul>
                        <li>Verify payment completion</li>
                        <li>Check credit balance</li>
                        <li>Transaction history</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <?php if ($result): ?>
        <div class="card">
            <div class="result-box">
                <h3>📊 API Response:</h3>
                <pre><?php echo json_encode($result, JSON_PRETTY_PRINT); ?></pre>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Documentation Card -->
        <div class="card">
            <h2>📚 Integration Guide</h2>
            <div style="line-height: 1.8; color: #374151;">
                <h3 style="color: #667eea; margin-top: 15px;">Quick Start:</h3>
                <pre style="background: #f9fafb; padding: 15px; border-radius: 8px; overflow-x: auto;">
// Include the SMS library
require_once 'sms_config.php';
require_once 'sms_parse.php';

// Initialize with credentials
hdev_sms::api_id(SMS_API_ID);
hdev_sms::api_key(SMS_API_KEY);

// Send SMS
$result = hdev_sms::send(
    SMS_SENDER_ID,           // Sender ID
    '250788123456',          // Phone number
    'Your message here'      // Message
);

// Check result
if ($result->status === 'success') {
    echo "SMS sent successfully!";
}
                </pre>
                
                <h3 style="color: #667eea; margin-top: 20px;">Use Cases for FarmBridgeAI:</h3>
                <ul style="margin-left: 20px;">
                    <li><strong>Registration:</strong> Welcome messages and account verification</li>
                    <li><strong>Order Notifications:</strong> New order alerts to farmers</li>
                    <li><strong>Payment Confirmations:</strong> Payment received notifications</li>
                    <li><strong>Order Status Updates:</strong> Shipping and delivery updates</li>
                    <li><strong>Dispute Alerts:</strong> Notify parties about disputes</li>
                    <li><strong>Price Alerts:</strong> Market price updates</li>
                    <li><strong>Reminders:</strong> Payment reminders and deadlines</li>
                </ul>
            </div>
        </div>
    </div>
    
    <script>
        function updateCharCount() {
            const message = document.getElementById('message').value;
            const charCount = document.getElementById('charCount');
            charCount.textContent = message.length;
            
            // Change color based on length
            if (message.length > 160) {
                charCount.style.color = '#ef4444';
            } else if (message.length > 140) {
                charCount.style.color = '#f59e0b';
            } else {
                charCount.style.color = '#10b981';
            }
        }
    </script>
</body>
</html>
