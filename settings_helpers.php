<?php
require_once __DIR__ . '/db.php';

function settings_ensure_table(mysqli $conn): void {
	$conn->query("CREATE TABLE IF NOT EXISTS settings (\n\t`key` VARCHAR(191) PRIMARY KEY,\n\t`value` TEXT NOT NULL,\n\t`updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP\n)");
}

function settings_get(string $key, $default = null) {
	global $conn; if (!isset($conn) || !$conn instanceof mysqli) { return $default; }
	settings_ensure_table($conn);
	$stmt = $conn->prepare("SELECT value FROM settings WHERE `key`=?");
	$stmt->bind_param("s", $key);
	$stmt->execute();
	$res = $stmt->get_result();
	$row = $res->fetch_assoc();
	if (!$row) return $default;
	return json_decode($row['value'], true) ?? $row['value'];
}

function settings_set(string $key, $value): bool {
	global $conn; if (!isset($conn) || !$conn instanceof mysqli) { return false; }
	settings_ensure_table($conn);
	$encoded = is_string($value) ? $value : json_encode($value);
	$stmt = $conn->prepare("INSERT INTO settings(`key`,`value`) VALUES(?,?) ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)");
	$stmt->bind_param("ss", $key, $encoded);
	return $stmt->execute();
}

function settings_get_bool(string $key, bool $default = false): bool {
	$val = settings_get($key, $default);
	if (is_bool($val)) return $val;
	if (is_string($val)) { return in_array(strtolower($val), ['1','true','yes','on'], true); }
	return (bool)$val;
}

function settings_get_float(string $key, float $default = 0.0): float {
	$val = settings_get($key, $default);
	return (float)$val;
}

function settings_get_int(string $key, int $default = 0): int {
	$val = settings_get($key, $default);
	return (int)$val;
}


