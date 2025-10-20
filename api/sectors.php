<?php
require_once __DIR__ . '/../db.php';
header('Content-Type: application/json');

$district_id = isset($_GET['district_id']) ? intval($_GET['district_id']) : 0;
$lang = isset($_GET['lang']) ? strtolower($_GET['lang']) : 'en';
if ($district_id <= 0) {
	http_response_code(400);
	echo json_encode(['error' => 'district_id is required']);
	exit;
}

$col = 'name';
if ($lang === 'en') { $col = 'name_en'; }
if ($lang === 'rw') { $col = 'name_rw'; }

try {
	$stmt = $conn->prepare("SELECT id, COALESCE($col, name) AS name FROM sectors WHERE district_id = ? ORDER BY name");
	$stmt->bind_param('i', $district_id);
	$stmt->execute();
	$res = $stmt->get_result();
	$rows = [];
	while ($row = $res->fetch_assoc()) { $rows[] = $row; }
	echo json_encode($rows);
} catch (Throwable $e) {
	http_response_code(500);
	echo json_encode(['error' => $e->getMessage()]);
}
