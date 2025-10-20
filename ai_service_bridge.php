<?php
/**
 * FarmBridge AI Service Bridge
 * PHP Integration for AI Price Prediction and Intent Classification
 */

class AIServiceBridge {
    private $pythonPath;
    private $databasePath;
    private static $cache = [];
    private static $cacheTtlSeconds = 120; // 2 minutes
    
    public function __construct() {
        $this->pythonPath = $this->resolvePythonPath();
        $this->databasePath = __DIR__ . "/database/";
    }

    private function resolvePythonPath() {
        $envPython = getenv('AI_PYTHON_PATH');
        if ($envPython && trim($envPython) !== '') {
            return $envPython;
        }
        $candidates = [
            'C:\\Users\\user\\AppData\\Local\\Programs\\Python\\Python312\\python.exe',
            'C:\\Python312\\python.exe',
            'C:\\Program Files\\Python312\\python.exe',
            'C:\\Program Files (x86)\\Python312\\python.exe',
        ];
        foreach ($candidates as $p) {
            if (file_exists($p)) return '"' . $p . '"';
        }
        return 'python';
    }
    
    /**
     * Get price prediction for a crop
     */
    public function getPricePrediction($crop, $date = null) {
        try {
            $cacheKey = 'price_' . strtolower(trim((string)$crop));
            $now = time();
            if (isset(self::$cache[$cacheKey]) && ($now - self::$cache[$cacheKey]['ts']) < self::$cacheTtlSeconds) {
                return [ 'success' => true, 'data' => self::$cache[$cacheKey]['data'] ];
            }
            $scriptPath = $this->databasePath . 'price_cli.py';
            $command = "{$this->pythonPath} \"{$scriptPath}\" aggregate --crop " . escapeshellarg($crop) . " 2>&1";
            $output = shell_exec($command);
            $result = json_decode($output, true);
            if ($result && !isset($result['error'])) {
                self::$cache[$cacheKey] = ['ts' => $now, 'data' => $result];
                return [ 'success' => true, 'data' => $result ];
            }
            return [ 'success' => false, 'error' => $result['error'] ?? (trim($output) ?: 'Unknown error') ];
        } catch (Exception $e) {
            return [ 'success' => false, 'error' => $e->getMessage() ];
        }
    }

    /**
     * Get price trend for a crop
     */
    public function getPriceTrend($crop, $days = 30) {
        try {
            $key = 'trend_' . strtolower(trim((string)$crop)) . '_' . (int)$days;
            $now = time();
            if (isset(self::$cache[$key]) && ($now - self::$cache[$key]['ts']) < self::$cacheTtlSeconds) {
                return [ 'success' => true, 'data' => self::$cache[$key]['data'] ];
            }
            $scriptPath = $this->databasePath . 'price_cli.py';
            $command = "{$this->pythonPath} \"{$scriptPath}\" trend --crop " . escapeshellarg($crop) . " --days " . (int)$days . " 2>&1";
            $output = shell_exec($command);
            $result = json_decode($output, true);
            if ($result && !isset($result['error'])) {
                self::$cache[$key] = ['ts' => $now, 'data' => $result];
                return [ 'success' => true, 'data' => $result ];
            }
            return [ 'success' => false, 'error' => $result['error'] ?? (trim($output) ?: 'Unknown error') ];
        } catch (Exception $e) {
            return [ 'success' => false, 'error' => $e->getMessage() ];
        }
    }

    /**
     * Classify user intent
     */
    public function classifyIntent($message) {
        try {
            $escapedMessage = escapeshellarg($message);
            $scriptPath = $this->databasePath . 'ai_enhanced_training.py';
            $command = "{$this->pythonPath} \"{$scriptPath}\" --predict $escapedMessage";
            $output = shell_exec($command);
            $intent = trim($output);
            if ($intent && !empty($intent)) {
                return [ 'success' => true, 'intent' => $intent, 'message' => $message ];
            } else {
                return [ 'success' => false, 'error' => 'Could not classify intent' ];
            }
        } catch (Exception $e) {
            return [ 'success' => false, 'error' => $e->getMessage() ];
        }
    }

    /**
     * Get comprehensive price analysis
     */
    public function getComprehensivePriceAnalysis($crop) {
        $priceData = $this->getPricePrediction($crop);
        $trendData = $this->getPriceTrend($crop, 30);
        if (!$priceData['success'] || !$trendData['success']) {
            return [ 'success' => false, 'error' => 'Failed to get price data' ];
        }
        $analysis = [
            'crop' => $crop,
            'current_price' => $priceData['data'],
            'trend' => $trendData['data'],
            'recommendations' => $this->generateRecommendations($priceData['data'], $trendData['data']),
            'timestamp' => date('Y-m-d H:i:s')
        ];
        return [ 'success' => true, 'data' => $analysis ];
    }
    
    /**
     * Generate recommendations based on price data
     */
    private function generateRecommendations($priceData, $trendData) {
        $recommendations = [];
        if (isset($priceData['confidence']) && $priceData['confidence'] > 0.8) {
            $recommendations[] = "High confidence price data available from multiple sources";
        }
        if (isset($trendData['trend_direction'])) {
            if ($trendData['trend_direction'] === 'increasing') {
                $recommendations[] = "Prices are trending upward - consider selling soon";
            } elseif ($trendData['trend_direction'] === 'decreasing') {
                $recommendations[] = "Prices are trending downward - consider holding or buying";
            } else {
                $recommendations[] = "Prices are stable - monitor for changes";
            }
        }
        if (isset($priceData['source_count']) && $priceData['source_count'] > 3) {
            $recommendations[] = "Price data verified from {$priceData['source_count']} sources";
        }
        return $recommendations;
    }
}

// API Endpoints for AJAX calls
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    $aiService = new AIServiceBridge();
    
    // If no action but message provided, route to chatbot for compatibility
    if (!$action && isset($_POST['message'])) {
        require_once __DIR__ . '/chatbot_api.php';
        $bot = new FarmBridgeChatbot(null);
        $resp = $bot->processMessage($_POST['message']);
        echo json_encode(['success' => true, 'response' => $resp]);
        exit;
    }

    switch ($action) {
        case 'get_price':
            $crop = $_POST['crop'] ?? '';
            if ($crop) {
                echo json_encode($aiService->getPricePrediction($crop));
            } else {
                echo json_encode(['success' => false, 'error' => 'Crop parameter required']);
            }
            break;
            
        case 'get_trend':
            $crop = $_POST['crop'] ?? '';
            $days = $_POST['days'] ?? 30;
            if ($crop) {
                echo json_encode($aiService->getPriceTrend($crop, $days));
            } else {
                echo json_encode(['success' => false, 'error' => 'Crop parameter required']);
            }
            break;
            
        case 'classify_intent':
            $message = $_POST['message'] ?? '';
            if ($message) {
                echo json_encode($aiService->classifyIntent($message));
            } else {
                echo json_encode(['success' => false, 'error' => 'Message parameter required']);
            }
            break;

        case 'chat':
            require_once __DIR__ . '/chatbot_api.php';
            $msg = $_POST['message'] ?? '';
            $bot = new FarmBridgeChatbot(null);
            $resp = $bot->processMessage($msg);
            echo json_encode(['success' => true, 'response' => $resp]);
            break;
            
        case 'comprehensive_analysis':
            $crop = $_POST['crop'] ?? '';
            if ($crop) {
                echo json_encode($aiService->getComprehensivePriceAnalysis($crop));
            } else {
                echo json_encode(['success' => false, 'error' => 'Crop parameter required']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
    
    exit;
}
?>
