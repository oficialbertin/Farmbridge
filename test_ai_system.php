<?php 

// Test 1: Intent Classification (via PHP bridge)
echo "<h3>1. Testing Intent Classification</h3>";
require_once __DIR__ . '/ai_service_bridge.php';
$aiService = new AIServiceBridge();
$test_queries = [
	"what is the price of tomatoes",
	"show me market trends",
	"how does your platform work",
	"is payment safe",
	"hello there"
];

foreach ($test_queries as $query) {
	$res = $aiService->classifyIntent($query);
	if (!empty($res['success'])) {
		$intent = htmlspecialchars($res['intent']);
		echo "<p><strong>Query:</strong> \"" . htmlspecialchars($query) . "\" → <strong>Intent:</strong> $intent</p>";
	} else {
		$err = htmlspecialchars($res['error'] ?? 'Unknown error');
		echo "<p><strong>Query:</strong> \"" . htmlspecialchars($query) . "\" → <strong>Error:</strong> $err</p>";
	}
}

// Test 2: Price Aggregation (via PHP bridge)
echo "<h3>2. Testing Price Aggregation</h3>";
$test_crops = ["tomato", "maize", "rice"];

foreach ($test_crops as $crop) {
	echo "<h4>Testing crop: " . htmlspecialchars($crop) . "</h4>";
	$res = $aiService->getPricePrediction($crop);
	if (!empty($res['success'])) {
		$data = $res['data'];
		$price = $data['aggregated_price'] ?? ($data['mean_price'] ?? null);
		if ($price !== null) {
			echo "<p><strong>Aggregated Price:</strong> " . number_format((float)$price, 2) . " RWF</p>";
		} else {
			echo "<p><strong>Aggregated Price:</strong> N/A</p>";
		}
		if (isset($data['confidence'])) echo "<p><strong>Confidence:</strong> " . htmlspecialchars((string)$data['confidence']) . "</p>";
		if (isset($data['source_count'])) echo "<p><strong>Sources:</strong> " . htmlspecialchars((string)$data['source_count']) . "</p>";
		echo "<p><em>" . htmlspecialchars($data['message'] ?? 'OK') . "</em></p>";
	} else {
		echo "<p style='color: red;'>Error getting price data: " . htmlspecialchars($res['error'] ?? 'Unknown error') . "</p>";
	}
}
?>