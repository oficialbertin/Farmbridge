<?php
require 'db.php';
require_once 'product_synonyms.php';
header('Content-Type: application/json');

$commodity = isset($_GET['commodity']) ? $conn->real_escape_string($_GET['commodity']) : '';
if (!$commodity) {
    echo json_encode(['error' => 'No commodity specified']);
    exit;
}

// Use the enhanced ProductMatcher for intelligent matching
$matcher = new ProductMatcher();
$match_result = $matcher->findProduct($commodity);

if ($match_result) {
    $base_commodity = $match_result['product'];
    $confidence = $match_result['confidence'];
    $match_type = $match_result['match_type'];
} else {
    // Fallback to simple matching
    $base_commodity = strtolower($commodity);
    $confidence = 0.5;
    $match_type = 'fallback';
}

// Search for the commodity in the database
$sql = "SELECT price, source, market, date FROM market_prices 
        WHERE LOWER(commodity) = ? OR LOWER(commodity) LIKE ? 
        ORDER BY date DESC, price DESC LIMIT 1";
$stmt = $conn->prepare($sql);
$search_term = "%$base_commodity%";
$stmt->bind_param("ss", $base_commodity, $search_term);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $row = $result->fetch_assoc()) {
    echo json_encode([
        'price' => $row['price'], 
        'source' => $row['source'],
        'market' => $row['market'],
        'date' => $row['date'],
        'matched_product' => $base_commodity,
        'confidence' => $confidence,
        'match_type' => $match_type,
        'original_query' => $commodity
    ]);
} else {
    // Fallback: provide estimated prices for common crops
    $estimated_prices = [
        'onion' => 950,
        'tomato' => 1200,
        'potato' => 800,
        'maize' => 450,
        'cassava' => 300,
        'banana' => 600,
        'bean' => 2200,
        'rice' => 1800,
        'carrot' => 750,
        'cabbage' => 600,
        'spinach' => 400,
        'lettuce' => 500,
        'eggplant' => 650,
        'cucumber' => 550,
        'bell pepper' => 850,
        'green beans' => 700,
        'cauliflower' => 900,
        'broccoli' => 1100,
        'garlic' => 1500,
        'ginger' => 1800,
        'apple' => 1200,
        'orange' => 800,
        'mango' => 700,
        'pineapple' => 900,
        'avocado' => 500,
        'papaya' => 400,
        'watermelon' => 300,
        'passion fruit' => 1000,
        'guava' => 450,
        'lemon' => 350,
        'lime' => 400,
        'pea' => 1800,
        'lentil' => 2500,
        'chickpea' => 2000,
        'soybean' => 1600,
        'sweet potato' => 400,
        'yam' => 500,
        'taro' => 450,
        'coffee' => 3500,
        'tea' => 2800,
        'sugarcane' => 200,
        'tobacco' => 4000,
        'wheat' => 2200,
        'sorghum' => 380,
    ];
    
    if (isset($estimated_prices[$base_commodity])) {
        echo json_encode([
            'price' => $estimated_prices[$base_commodity], 
            'source' => 'Estimated (Rwanda Market Average)',
            'market' => 'Kigali',
            'date' => date('Y-m-d'),
            'matched_product' => $base_commodity,
            'confidence' => $confidence,
            'match_type' => $match_type,
            'original_query' => $commodity
        ]);
    } else {
        echo json_encode([
            'price' => null, 
            'source' => null, 
            'market' => null, 
            'date' => null,
            'matched_product' => $base_commodity,
            'confidence' => $confidence,
            'match_type' => $match_type,
            'original_query' => $commodity
        ]);
    }
} 