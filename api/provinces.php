<?php
require_once __DIR__ . '/../db.php';
header('Content-Type: application/json');

$lang = isset($_GET['lang']) ? strtolower($_GET['lang']) : 'en';
$col = 'name';
if ($lang === 'en') { $col = 'name_en'; }
if ($lang === 'rw') { $col = 'name_rw'; }
// Fallback to name if bilingual columns are NULL

try {
	$query = "SELECT id, COALESCE($col, name) AS name FROM provinces ORDER BY name";
	$result = $conn->query($query);
	$rows = [];
	while ($row = $result->fetch_assoc()) { $rows[] = $row; }
	echo json_encode($rows);
} catch (Throwable $e) {
	http_response_code(500);
	echo json_encode(['error' => $e->getMessage()]);
}
