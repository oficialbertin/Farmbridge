<?php
// Simple email helper: tries PHP mail() and always logs to file.

function email_get_config(): array {
	$cfgPath = __DIR__ . '/email_secret.php';
	$cfg = [
		'host' => getenv('SMTP_HOST') ?: 'smtp.gmail.com',
		'port' => (int)(getenv('SMTP_PORT') ?: 587),
		'username' => getenv('SMTP_USERNAME') ?: '',
		'password' => getenv('SMTP_PASSWORD') ?: '',
		'from_email' => getenv('SMTP_FROM_EMAIL') ?: '',
		'from_name' => getenv('SMTP_FROM_NAME') ?: 'FarmBridge AI',
	];
	if (file_exists($cfgPath)) {
		try { $fileCfg = include $cfgPath; if (is_array($fileCfg)) { $cfg = array_merge($cfg, $fileCfg); } } catch (Throwable $e) {}
	}
	return $cfg;
}

function send_email(string $to, string $subject, string $htmlBody, string $textBody = ''): bool {
	$cfg = email_get_config();
	
	// Log the attempt
	$log_entry = [
		'timestamp' => date('Y-m-d H:i:s'),
		'to' => $to,
		'subject' => $subject,
		'from_email' => $cfg['from_email'],
		'from_name' => $cfg['from_name']
	];
	
	// Check if we have required config
	if (empty($cfg['from_email'])) {
		$log_entry['error'] = 'No from_email configured';
		@file_put_contents(__DIR__ . '/email_log.txt', json_encode($log_entry) . "\n", FILE_APPEND);
		return false;
	}
	
	$headers = "MIME-Version: 1.0\r\n" .
		"Content-type:text/html;charset=UTF-8\r\n" .
		"From: " . ($cfg['from_name'] ?: 'FarmBridge AI') . " <" . $cfg['from_email'] . ">\r\n" .
		"Reply-To: " . $cfg['from_email'] . "\r\n" .
		"X-Mailer: PHP/" . phpversion() . "\r\n";
	
	$ok = @mail($to, $subject, $htmlBody, $headers);
	
	$log_entry['success'] = $ok;
	if (!$ok) {
		$log_entry['error'] = error_get_last()['message'] ?? 'Unknown mail error';
	}
	
	@file_put_contents(__DIR__ . '/email_log.txt', json_encode($log_entry) . "\n", FILE_APPEND);
	return $ok;
}


