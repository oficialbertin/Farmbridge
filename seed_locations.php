<?php
// Seeds provinces, districts, sectors from a JSON file into MySQL.
// Usage examples:
//   http://localhost/FarmBridgeAI/seed_locations.php?source=local    (expects locations.json in project root)
//   http://localhost/FarmBridgeAI/seed_locations.php?source=url&url=https://raw.githubusercontent.com/jnkindi/rwanda-locations-json/master/locations.json

require_once __DIR__ . '/db.php';

function load_json(string $source): array {
	if ($source === 'local') {
		$path = __DIR__ . '/locations.json';
		if (!file_exists($path)) {
			http_response_code(400);
			die('locations.json not found. Place it in project root or use source=url.');
		}
		$json = file_get_contents($path);
	} else {
		$url = isset($_GET['url']) ? $_GET['url'] : '';
		if (!$url) {
			http_response_code(400);
			die('Missing url parameter.');
		}
		$json = @file_get_contents($url);
		if ($json === false) {
			http_response_code(400);
			die('Failed to fetch JSON from URL.');
		}
	}
	$data = json_decode($json, true);
	if (!is_array($data)) {
		http_response_code(400);
		die('Invalid JSON.');
	}
	return $data;
}

function map_province_name_to_english(string $nameRw): array {
	// Known province names in Kinyarwanda → English
	$map = [
		'Amajyaruguru' => 'Northern Province',
		'Amajyepfo' => 'Southern Province',
		'Iburasirazuba' => 'Eastern Province',
		'Iburengerazuba' => 'Western Province',
		'Umujyi wa Kigali' => 'Kigali City',
		// Common variants
		'Amajyaruguru Province' => 'Northern Province',
		'Amajyepfo Province' => 'Southern Province',
		'Iburasirazuba Province' => 'Eastern Province',
		'Iburengerazuba Province' => 'Western Province',
		'Kigali' => 'Kigali City'
	];
	$trimmed = trim($nameRw);
	if (isset($map[$trimmed])) {
		return [$map[$trimmed], $trimmed];
	}
	// If already English or unknown, use original for both
	return [$trimmed, $trimmed];
}

function upsert_location_data(mysqli $conn, array $data): void {
	$conn->begin_transaction();
	try {
		// Expecting structure:
		// { "provinces": [ { "name": "...", "districts": [ { "name": "...", "sectors": ["..."] } ] } ] }
		$provinceStmt = $conn->prepare('INSERT INTO provinces (name, name_en, name_rw) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name), name_en = VALUES(name_en), name_rw = VALUES(name_rw)');
		$districtStmt = $conn->prepare('INSERT INTO districts (province_id, name, name_en, name_rw) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name), name_en = VALUES(name_en), name_rw = VALUES(name_rw)');
		$sectorStmt   = $conn->prepare('INSERT INTO sectors (district_id, name, name_en, name_rw) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name), name_en = VALUES(name_en), name_rw = VALUES(name_rw)');

		$getProvinceId = $conn->prepare('SELECT id FROM provinces WHERE name = ?');
		$getDistrictId = $conn->prepare('SELECT id FROM districts WHERE province_id = ? AND name = ?');

		foreach ($data['provinces'] as $province) {
			$nameProvinceSource = $province['name'];
			list($nameProvinceEn, $nameProvinceRw) = map_province_name_to_english($nameProvinceSource);
			$primaryProvinceName = $nameProvinceEn; // store English as primary

			$provinceStmt->bind_param('sss', $primaryProvinceName, $nameProvinceEn, $nameProvinceRw);
			$provinceStmt->execute();

			$getProvinceId->bind_param('s', $primaryProvinceName);
			$getProvinceId->execute();
			$resP = $getProvinceId->get_result();
			$rowP = $resP->fetch_assoc();
			$provinceId = $rowP ? intval($rowP['id']) : 0;

			foreach ($province['districts'] as $district) {
				$nameDistrict = $district['name'];
				// For now, use the same for en/rw unless you have bilingual lists for districts
				$districtStmt->bind_param('isss', $provinceId, $nameDistrict, $nameDistrict, $nameDistrict);
				$districtStmt->execute();

				$getDistrictId->bind_param('is', $provinceId, $nameDistrict);
				$getDistrictId->execute();
				$resD = $getDistrictId->get_result();
				$rowD = $resD->fetch_assoc();
				$districtId = $rowD ? intval($rowD['id']) : 0;

				foreach ($district['sectors'] as $sectorName) {
					$nameSector = is_array($sectorName) ? ($sectorName['name'] ?? '') : $sectorName;
					if (!$nameSector) { continue; }
					$sectorStmt->bind_param('isss', $districtId, $nameSector, $nameSector, $nameSector);
					$sectorStmt->execute();
				}
			}
		}

		$conn->commit();
		echo 'Seeding completed successfully';
	} catch (Throwable $e) {
		$conn->rollback();
		http_response_code(500);
		echo 'Error during seeding: ' . $e->getMessage();
	}
}

$source = isset($_GET['source']) ? $_GET['source'] : 'local';
$data = load_json($source);

if (!isset($data['provinces']) || !is_array($data['provinces'])) {
	http_response_code(400);
	die('JSON must contain provinces array');
}

upsert_location_data($conn, $data);
