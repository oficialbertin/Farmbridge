<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMS Webhook Test - FarmBridgeAI</title>
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
            max-width: 900px;
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
        
        .card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .card h2 {
            color: #667eea;
            margin-bottom: 15px;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
            margin-left: 10px;
        }
        
        .status-online {
            background: #10b981;
            color: white;
        }
        
        .status-offline {
            background: #ef4444;
            color: white;
        }
        
        .code-block {
            background: #1f2937;
            color: #10b981;
            padding: 15px;
            border-radius: 8px;
            overflow-x: auto;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            margin: 10px 0;
        }
        
        .info-box {
            background: #eff6ff;
            border-left: 4px solid #3b82f6;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }
        
        .warning-box {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }
        
        .success-box {
            background: #d1fae5;
            border-left: 4px solid #10b981;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }
        
        .btn {
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .step {
            margin: 20px 0;
            padding-left: 30px;
            position: relative;
        }
        
        .step::before {
            content: "✓";
            position: absolute;
            left: 0;
            top: 0;
            background: #10b981;
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        .log-viewer {
            background: #f9fafb;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            padding: 15px;
            max-height: 400px;
            overflow-y: auto;
            font-family: 'Courier New', monospace;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔗 SMS Webhook Test</h1>
            <p>HDEV SMS Gateway Callback Configuration</p>
        </div>
        
        <!-- Webhook Status -->
        <div class="card">
            <h2>Webhook Status</h2>
            <?php
            $webhook_url = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/FarmBridgeAI/sms_callback.php';
            
            // Test if webhook is accessible
            $ch = curl_init($webhook_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            $is_online = ($http_code === 200);
            ?>
            
            <p>
                <strong>Webhook Endpoint:</strong> 
                <span class="status-badge <?php echo $is_online ? 'status-online' : 'status-offline'; ?>">
                    <?php echo $is_online ? '✓ ONLINE' : '✗ OFFLINE'; ?>
                </span>
            </p>
            
            <div class="code-block">
                <?php echo htmlspecialchars($webhook_url); ?>
            </div>
            
            <?php if ($is_online): ?>
            <div class="success-box">
                <strong>✓ Webhook is accessible!</strong> The endpoint is responding correctly.
            </div>
            <?php else: ?>
            <div class="warning-box">
                <strong>⚠️ Webhook is not accessible!</strong> Make sure your web server is running.
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Ngrok Setup Instructions -->
        <div class="card">
            <h2>📡 Ngrok Setup Instructions</h2>
            
            <div class="warning-box">
                <strong>⚠️ Important:</strong> Your ngrok URL <code>475b0069b9d5.ngrok-free.app</code> is getting 404 errors because the webhook endpoint wasn't configured properly.
            </div>
            
            <div class="step">
                <h3>Step 1: Start Ngrok</h3>
                <p>Open a terminal and run:</p>
                <div class="code-block">
                    ngrok http 80
                </div>
                <p>Or if using XAMPP on a different port:</p>
                <div class="code-block">
                    ngrok http localhost:80
                </div>
            </div>
            
            <div class="step">
                <h3>Step 2: Copy Your Ngrok URL</h3>
                <p>Ngrok will display a URL like:</p>
                <div class="code-block">
                    https://abc123def456.ngrok-free.app
                </div>
                <p>Copy this URL (it changes each time you restart ngrok)</p>
            </div>
            
            <div class="step">
                <h3>Step 3: Configure HDEV SMS Dashboard</h3>
                <p>Login to <a href="https://sms-api.hdev.rw" target="_blank" style="color: #667eea; font-weight: bold;">https://sms-api.hdev.rw</a></p>
                <p>Set your callback URL to:</p>
                <div class="code-block">
                    https://YOUR-NGROK-URL.ngrok-free.app/FarmBridgeAI/sms_callback.php
                </div>
                <p><strong>Example:</strong></p>
                <div class="code-block">
                    https://475b0069b9d5.ngrok-free.app/FarmBridgeAI/sms_callback.php
                </div>
            </div>
            
            <div class="step">
                <h3>Step 4: Test the Webhook</h3>
                <p>Send a test SMS and check the callback log below</p>
            </div>
            
            <div class="info-box">
                <h4>💡 Pro Tips:</h4>
                <ul style="margin-left: 20px; margin-top: 10px;">
                    <li>Use <code>ngrok http 80 --region eu</code> for better performance in Europe/Africa</li>
                    <li>Get a permanent ngrok URL with a paid account to avoid changing it</li>
                    <li>Make sure XAMPP Apache is running before starting ngrok</li>
                    <li>The callback endpoint must be publicly accessible</li>
                </ul>
            </div>
        </div>
        
        <!-- Test Webhook -->
        <div class="card">
            <h2>🧪 Test Webhook</h2>
            <p>Click the button below to send a test request to your webhook:</p>
            
            <form method="POST" action="">
                <input type="hidden" name="test_webhook" value="1">
                <button type="submit" class="btn">Send Test Request</button>
            </form>
            
            <?php
            if (isset($_POST['test_webhook'])) {
                $test_data = [
                    'type' => 'delivery_report',
                    'status' => 'delivered',
                    'message_id' => 'TEST-' . time(),
                    'phone' => '250788123456',
                    'delivery_status' => 'success'
                ];
                
                $ch = curl_init($webhook_url);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($test_data));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $response = curl_exec($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if ($http_code === 200) {
                    echo '<div class="success-box">';
                    echo '<strong>✓ Test Successful!</strong><br>';
                    echo '<strong>Response:</strong><br>';
                    echo '<pre>' . htmlspecialchars($response) . '</pre>';
                    echo '</div>';
                } else {
                    echo '<div class="warning-box">';
                    echo '<strong>⚠️ Test Failed!</strong><br>';
                    echo '<strong>HTTP Code:</strong> ' . $http_code . '<br>';
                    echo '<strong>Response:</strong> ' . htmlspecialchars($response);
                    echo '</div>';
                }
            }
            ?>
        </div>
        
        <!-- Callback Log Viewer -->
        <div class="card">
            <h2>📋 Recent Callback Log</h2>
            <p>Last 10 webhook requests:</p>
            
            <?php
            $log_file = __DIR__ . '/sms_callback_log.txt';
            if (file_exists($log_file)) {
                $log_content = file_get_contents($log_file);
                $log_entries = explode('---', $log_content);
                $recent_entries = array_slice(array_reverse($log_entries), 0, 10);
                
                echo '<div class="log-viewer">';
                foreach ($recent_entries as $entry) {
                    if (trim($entry)) {
                        echo htmlspecialchars($entry) . "\n---\n";
                    }
                }
                echo '</div>';
                
                echo '<br><a href="?clear_log=1" class="btn">Clear Log</a>';
                
                if (isset($_GET['clear_log'])) {
                    file_put_contents($log_file, '');
                    echo '<script>window.location.href = window.location.pathname;</script>';
                }
            } else {
                echo '<div class="info-box">No callback log found yet. The log will be created when the first callback is received.</div>';
            }
            ?>
        </div>
        
        <!-- Troubleshooting -->
        <div class="card">
            <h2>🔧 Troubleshooting</h2>
            
            <h3 style="color: #667eea; margin-top: 15px;">Error: "The endpoint is offline. ERR_NGROK_3200"</h3>
            <ul style="margin-left: 20px; line-height: 1.8;">
                <li><strong>Cause:</strong> Ngrok is not running or the URL has changed</li>
                <li><strong>Solution:</strong> Restart ngrok and update the callback URL in HDEV SMS dashboard</li>
            </ul>
            
            <h3 style="color: #667eea; margin-top: 15px;">Error: "404 Not Found"</h3>
            <ul style="margin-left: 20px; line-height: 1.8;">
                <li><strong>Cause:</strong> The callback endpoint path is incorrect</li>
                <li><strong>Solution:</strong> Make sure the URL ends with <code>/FarmBridgeAI/sms_callback.php</code></li>
            </ul>
            
            <h3 style="color: #667eea; margin-top: 15px;">Ngrok URL keeps changing</h3>
            <ul style="margin-left: 20px; line-height: 1.8;">
                <li><strong>Cause:</strong> Free ngrok URLs change on restart</li>
                <li><strong>Solution:</strong> Upgrade to ngrok paid plan for a permanent URL, or use a VPS/cloud server</li>
            </ul>
        </div>
        
        <!-- Quick Links -->
        <div class="card">
            <h2>🔗 Quick Links</h2>
            <a href="test_sms.php" class="btn">Test SMS Sending</a>
            <a href="sms_callback.php" class="btn" target="_blank">View Webhook Endpoint</a>
            <a href="https://sms-api.hdev.rw" class="btn" target="_blank">HDEV SMS Dashboard</a>
            <a href="https://ngrok.com/download" class="btn" target="_blank">Download Ngrok</a>
        </div>
    </div>
</body>
</html>
