<?php
header('Content-Type: text/plain; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { echo "OK"; exit; }

require_once __DIR__ . '/ai_service_bridge.php';
require_once __DIR__ . '/chatbot_api.php'; // reuse FarmBridgeChatbot class definition

try {
	$raw = file_get_contents('php://input');
	$input = json_decode($raw, true);
	$message = '';
	if (is_array($input) && isset($input['message'])) { $message = $input['message']; }
	elseif (isset($_POST['message'])) { $message = $_POST['message']; }
	elseif (isset($_GET['message'])) { $message = $_GET['message']; }
	$bot = new FarmBridgeChatbot(null);
	echo $bot->processMessage($message);
} catch (Throwable $e) {
	echo "🤖 Welcome to FarmBridge AI Assistant!\n\nI can help you with:\n• 🌾 Crop prices and availability\n• 📊 Market trends and demand forecasts\n• 🌱 Farming advice and seasonal tips\n• 💡 Platform help and guidance\n\nJust ask me anything about farming, crops, or the platform!";
}
