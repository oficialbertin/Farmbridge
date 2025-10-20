<?php
// Lightweight Afripay config loader (env first, then optional afripay_secret.php)

function afripay_get_config(): array {
	$appId = getenv('AFRIPAY_APP_ID') ?: '';
	$appSecret = getenv('AFRIPAY_APP_SECRET') ?: '';
	$returnUrl = getenv('AFRIPAY_RETURN_URL') ?: '';
	$secretPath = __DIR__ . '/afripay_secret.php';
	if (file_exists($secretPath)) {
		try {
			$cfg = include $secretPath;
			if (is_array($cfg)) {
				$appId = $appId ?: ($cfg['AFRIPAY_APP_ID'] ?? '');
				$appSecret = $appSecret ?: ($cfg['AFRIPAY_APP_SECRET'] ?? '');
				$returnUrl = $returnUrl ?: ($cfg['AFRIPAY_RETURN_URL'] ?? '');
			}
		} catch (Throwable $e) { /* ignore */ }
	}
	return [
		'app_id' => $appId,
		'app_secret' => $appSecret,
		'return_url' => $returnUrl,
	];
}

function afripay_is_configured(array $cfg): bool {
	return !empty($cfg['app_id']) && !empty($cfg['app_secret']);
}


