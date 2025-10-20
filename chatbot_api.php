<?php
if (!defined('CHATBOT_LIBRARY_ONLY')) {
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
		echo json_encode(['ok' => true]);
    exit(0);
	}
}

require_once 'ai_service_bridge.php';
if (session_status() === PHP_SESSION_NONE) {
    require 'session_helper.php';
}

class FarmBridgeChatbot {
    private $conn;
    
	public function __construct($conn = null) {
        $this->conn = $conn;
    }
    
    // Lightweight helpers for variation
    private function pick(array $options) {
        return $options[array_rand($options)];
    }
    private function joinBullets(array $lines): string {
        // Randomize order sometimes
        if (count($lines) > 1 && mt_rand(0, 1) === 1) { shuffle($lines); }
        $bullet = $this->pick(['•', '–', '∙']);
        return implode("\n", array_map(fn($l) => $bullet . ' ' . $l, $lines));
    }

	private function callOpenAIChat(string $message): ?string {
		// 1) Try environment
		$apiKey = getenv('OPENAI_API_KEY') ?: getenv('OPENAI_KEY') ?: '';
		$model = getenv('OPENAI_MODEL') ?: '';
		$endpoint = getenv('OPENAI_API_BASE') ?: '';

		// 2) Optional local secret file (not committed): openai_secret.php returning an array
		// Example file content:
		// <?php return [ 'OPENAI_API_KEY' => 'sk-...', 'OPENAI_MODEL' => 'gpt-4o-mini', 'OPENAI_API_BASE' => 'https://api.openai.com/v1/chat/completions' ];
		$secretPath = __DIR__ . '/openai_secret.php';
		if (file_exists($secretPath)) {
			try {
				$cfg = include $secretPath;
				if (is_array($cfg)) {
					$apiKey = $apiKey ?: ($cfg['OPENAI_API_KEY'] ?? '');
					$model = $model ?: ($cfg['OPENAI_MODEL'] ?? '');
					$endpoint = $endpoint ?: ($cfg['OPENAI_API_BASE'] ?? '');
				}
			} catch (\Throwable $e) { /* ignore */ }
		}

		if (!$apiKey) { return null; }
		if (!$model) { $model = 'gpt-4o-mini'; }
		if (!$endpoint) { $endpoint = 'https://api.openai.com/v1/chat/completions'; }

		$system = "You are FarmBridge AI, a helpful assistant for farmers in Rwanda."
			. " Provide concise, structured answers about crop prices, market trends,"
			. " planting advice, safety/escrow, platform usage, and seasonal/weather tips."
			. " Prefer Rwandan context and RWF prices when relevant. If you are unsure, ask a short clarifying question.";

		$payload = [
			"model" => $model,
			"messages" => [
				["role" => "system", "content" => $system],
				["role" => "user", "content" => (string)$message]
			],
			"temperature" => 0.2,
			"max_tokens" => 400
		];

		$headers = [
			"Content-Type: application/json",
			"Authorization: Bearer " . $apiKey
		];

		$ctx = stream_context_create([
			'http' => [
				'method' => 'POST',
				'header' => implode("\r\n", $headers) . "\r\n",
				'content' => json_encode($payload),
				'timeout' => 8
			]
		]);
		$raw = @file_get_contents($endpoint, false, $ctx);
		if (!$raw) { return null; }
		$data = json_decode($raw, true);
		if (!isset($data['choices'][0]['message']['content'])) { return null; }
		return trim((string)$data['choices'][0]['message']['content']);
	}
    
	private function welcome(): string {
		$greetings = [
			"🤖 Hi there! I’m FarmBridge AI.",
			"🤖 Hello! FarmBridge AI at your service.",
			"🤖 Muraho! This is FarmBridge AI.",
		];
		$time = (int)date('G');
		$timeofday = ($time < 12 ? 'Good morning' : ($time < 18 ? 'Good afternoon' : 'Good evening'));
		$name = isset($_SESSION['name']) ? (' ' . trim((string)$_SESSION['name'])) : '';
		$headline = $greetings[array_rand($greetings)] . ' ' . $timeofday . $name . '!';
		return $headline . "\n\nHow can I help you today?\n\n• 🌾 Crop prices and availability\n• 📊 Market trends and demand forecasts\n• 🌱 Farming advice and seasonal tips\n• 💡 Platform help and guidance\n\nJust type your question, like: ‘price of tomatoes in Kigali’ or ‘best crop to plant now’.";
	}

	private function shortWelcome(): string {
		$greetings = [
			"🤖 Hi there! I’m FarmBridge AI.",
			"🤖 Hello! FarmBridge AI at your service.",
			"🤖 Muraho! This is FarmBridge AI.",
		];
		$time = (int)date('G');
		$timeofday = ($time < 12 ? 'Good morning' : ($time < 18 ? 'Good afternoon' : 'Good evening'));
		$name = isset($_SESSION['name']) ? (' ' . trim((string)$_SESSION['name'])) : '';
		$headline = $greetings[array_rand($greetings)] . ' ' . $timeofday . $name . '!';
		return $headline . "\n\nHow can I help you today? Ask me anything about farming, markets, or the platform.";
	}

	private function greetingResponse(): string {
		$welcomed = !empty($_SESSION['welcomed_once']);
		if (!$welcomed) {
			$_SESSION['welcomed_once'] = 1;
			$_SESSION['welcomed_at'] = date('Y-m-d H:i:s');
			return $this->welcome();
		}
		return $this->shortWelcome();
	}
	
	public function processMessage($message) {
		$message = strtolower(trim((string)$message));
		if ($message === '') {
			return $this->greetingResponse();
		}
        if ($this->isGreeting($message)) {
			return $this->greetingResponse();
        }
        $aiResponse = $this->tryAIModels($message);
        if ($aiResponse !== false) {
            return $aiResponse;
        }
        if (strpos($message, 'price') !== false || strpos($message, 'cost') !== false) {
            return $this->handlePriceQuery($message);
		} elseif (strpos($message, 'safe') !== false || strpos($message, 'secure') !== false || strpos($message, 'trust') !== false || strpos($message, 'escrow') !== false || strpos($message, 'refund') !== false || strpos($message, 'receive') !== false) {
            return $this->handleSafetyQuery($message);
		} elseif (strpos($message, 'how your platform') !== false || strpos($message, 'how the platform') !== false || strpos($message, 'how does your platform') !== false || strpos($message, 'how it works') !== false || strpos($message, 'how do you work') !== false) {
            return $this->handleHowPlatformWorks();
		} elseif ((strpos($message, 'plant') !== false || strpos($message, 'grow') !== false || strpos($message, 'sow') !== false || strpos($message, 'advise') !== false || strpos($message, 'recommend') !== false) && (strpos($message, 'marketplace') !== false || strpos($message, 'market') !== false)) {
            return $this->handlePlantingAdviceQuery($message);
		} elseif (strpos($message, 'demand') !== false || strpos($message, 'forecast') !== false) {
            return $this->handleDemandQuery($message);
		} elseif (strpos($message, 'crop') !== false) {
            return $this->handleCropQuery($message);
		} elseif (strpos($message, 'market') !== false || strpos($message, 'trend') !== false || strpos($message, 'show me') !== false) {
            return $this->handleMarketQuery($message);
		} elseif (strpos($message, 'help') !== false || strpos($message, 'how') !== false) {
            return $this->handleHelpQuery($message);
		} elseif (strpos($message, 'weather') !== false || strpos($message, 'season') !== false) {
            return $this->handleWeatherQuery($message);
        }
		return $this->greetingResponse();
    }
    
    private function tryAIModels($message) {
    // Enhanced: log all AI intent results and handle low-confidence fallback
    $logFile = __DIR__ . '/chatbot_ai_log.txt';
    try {
        $aiBase = getenv('AI_BASE_URL') ?: 'http://127.0.0.1:8000';
        $url = $aiBase . '/predict_intent';
        $payload = json_encode(['message' => $message]);
        $opts = [ 'http' => [ 'method' => 'POST', 'header' => "Content-Type: application/json\r\n", 'content' => $payload, 'timeout' => 3 ] ];
        $ctx = stream_context_create($opts);
        $raw = @file_get_contents($url, false, $ctx);
        if ($raw) {
            $data = json_decode($raw, true);
            $label = $data['label'] ?? null;
            $conf = (float)($data['confidence'] ?? 0);
            // Log all predictions
            @file_put_contents($logFile, date('Y-m-d H:i:s') . ",AI," . json_encode(['msg'=>$message,'label'=>$label,'conf'=>$conf]) . "\n", FILE_APPEND);
            if ($label && $conf >= 0.6) {
                switch ($label) {
                    case 'price': return $this->handlePriceQuery($message);
                    case 'market': return $this->handleMarketQuery($message);
                    case 'advice': return $this->handlePlantingAdviceQuery($message);
                    case 'how_platform': return $this->handleHowPlatformWorks();
                    case 'safety': return $this->handleSafetyQuery($message);
                    case 'demand': return $this->handleDemandQuery($message);
                    case 'weather': return $this->handleWeatherQuery($message);
                    case 'greeting': return $this->greetingResponse();
                }
            } elseif ($label && $conf > 0) {
                // Low confidence fallback: ask for clarification
                return "🤖 Sorry, I didn't quite understand. Are you asking about crop prices, market trends, farming advice, or something else? Please rephrase or choose a topic, and I'll do my best to help!";
            }
        }
    } catch (\Throwable $e) {
        @file_put_contents($logFile, date('Y-m-d H:i:s') . ",AI_ERROR," . json_encode(['msg'=>$message,'error'=>$e->getMessage()]) . "\n", FILE_APPEND);
    }

    try {
        $bridge = new AIServiceBridge();
        $res = $bridge->classifyIntent($message);
        if (!empty($res['success']) && !empty($res['intent'])) {
            @file_put_contents($logFile, date('Y-m-d H:i:s') . ",BRIDGE," . json_encode(['msg'=>$message,'intent'=>$res['intent']]) . "\n", FILE_APPEND);
            switch ($res['intent']) {
                case 'price': return $this->handlePriceQuery($message);
                case 'market': return $this->handleMarketQuery($message);
                case 'advice': return $this->handlePlantingAdviceQuery($message);
                case 'how_platform': return $this->handleHowPlatformWorks();
                case 'safety': return $this->handleSafetyQuery($message);
                case 'demand': return $this->handleDemandQuery($message);
                case 'weather': return $this->handleWeatherQuery($message);
                case 'greeting': return $this->greetingResponse();
            }
        }
    } catch (\Throwable $e) {
        @file_put_contents($logFile, date('Y-m-d H:i:s') . ",BRIDGE_ERROR," . json_encode(['msg'=>$message,'error'=>$e->getMessage()]) . "\n", FILE_APPEND);
    }

    // As a final fallback, try OpenAI if configured
    try {
        $openaiResponse = $this->callOpenAIChat($message);
        if ($openaiResponse) {
            @file_put_contents($logFile, date('Y-m-d H:i:s') . ",OPENAI," . json_encode(['msg'=>$message]) . "\n", FILE_APPEND);
            return $openaiResponse;
        }
    } catch (\Throwable $e) {
        @file_put_contents($logFile, date('Y-m-d H:i:s') . ",OPENAI_ERROR," . json_encode(['msg'=>$message,'error'=>$e->getMessage()]) . "\n", FILE_APPEND);
    }

    // Log ambiguous/unhandled queries
    @file_put_contents($logFile, date('Y-m-d H:i:s') . ",FALLBACK," . json_encode(['msg'=>$message]) . "\n", FILE_APPEND);
    return false;
}
    
    private function handlePriceQuery($message) {
        $crops = $this->getAvailableCrops();

        // Resolve commodity and optional location from the user's query
        $resolved = $this->resolveCommodityFromQuery($message, $crops);
        $commodity = $resolved['commodity'] ?? null;
        $category = $resolved['category'] ?? null;
        $matchedCrop = $resolved['matchedCrop'] ?? null; // row from crops table if exists
        $location = $this->detectLocationFromQuery($message);
        $timeframe = $this->parseTimeframeFromQuery($message); // latest | today | this_week | last_week | this_month | last_month | next_month

        // Use previous conversational context ONLY if no specific commodity/category is mentioned
        $last = $this->getLastContext();
        if (!$commodity && !$category && !empty($last['commodity'])) { 
            $commodity = $last['commodity']; 
        }
        if (!$location && !empty($last['location'])) { 
            $location = $last['location']; 
        }

        // Handle category-based queries
        if ($category) {
            return $this->handleCategoryPriceQuery($category, $location);
        }

        if (!$commodity) {
            $reminder = '';
            if (!empty($last['commodity'])) {
                $reminder = "\n(Using your last crop: '" . $last['commodity'] . "'. To change, ask about another crop.)";
            }
            return "I can help you with crop prices! Please specify which crop you're interested in (e.g., 'What's the price of tomatoes?' or 'How much do potatoes cost?').\n\nAvailable crops: " . implode(', ', array_column($crops, 'name')) . $reminder;
        }

        // If user asked for next month's prices, provide a forecast
        if ($timeframe === 'next_month') {
            $forecast = $this->forecastPriceForNextMonth($commodity, $location);
            if ($forecast) {
                $titleCommodity = ucwords($commodity);
                $titleLoc = $location ? (" — " . $location) : "";
                $resp = "🔮 Forecast for Next Month: {$titleCommodity}{$titleLoc}\n\n";
                $resp .= "• Estimated average price: Rwf " . number_format($forecast['price']) . " per kg\n";
                if (!empty($forecast['basis'])) {
                    $resp .= "• Basis: " . $forecast['basis'] . "\n";
                }
                $resp .= "\n💡 Note: This is a forecast based on recent trends; actual market prices may vary.";
                $this->setLastContext($commodity, $location);
                return $resp;
            }
        }

        // Internal metrics
        $internalLines = [];
        if ($matchedCrop) {
            $internalLines[] = "• Current listing: Rwf " . number_format($matchedCrop['price']) . " per {$matchedCrop['unit']} (" . number_format($matchedCrop['quantity']) . " {$matchedCrop['unit']} available)";
            $avgPrice = $this->getAveragePrice($matchedCrop['id']);
            if ($avgPrice > 0) {
                $internalLines[] = "• Average sale price (30d): Rwf " . number_format($avgPrice) . " per kg";
            }
        }
        // Internal market average (7d), optionally by location
        $days = 7;
        $platformAvg = 0;
        if ($timeframe === 'today') {
            $days = 1;
            $platformAvg = $this->getInternalMarketAverage($commodity, 1, $location);
        } elseif ($timeframe === 'this_week') {
            $days = 7;
            $platformAvg = $this->getInternalMarketAverage($commodity, 7, $location);
        } elseif ($timeframe === 'last_week') {
            $days = 7;
            $platformAvg = $this->getInternalMarketAverageShifted($commodity, 7, 7, $location);
        } elseif ($timeframe === 'this_month') {
            $days = 30;
            $platformAvg = $this->getInternalMarketAverage($commodity, 30, $location);
        } elseif ($timeframe === 'last_month') {
            $days = 30;
            $platformAvg = $this->getInternalMarketAverageShifted($commodity, 30, 30, $location);
        } else { // latest/default
            $days = 7;
            $platformAvg = $this->getInternalMarketAverage($commodity, 7, $location);
        }
        if ($platformAvg > 0) {
            $locLabel = $location ? (" in " . $location) : "";
            $internalLines[] = "• Market average (" . $this->labelForTimeframe($timeframe, $days) . "{$locLabel}): Rwf " . number_format($platformAvg) . " per kg";
        }

		// Aggregated AI price via Python service
		$aggLines = [];
		try {
			$aiService = new AIServiceBridge();
			$agg = $aiService->getPricePrediction($commodity);
			if (!empty($agg['success'])) {
				$d = $agg['data'];
				if (isset($d['aggregated_price'])) {
					$aggLines[] = "• Aggregated multi-source price: Rwf " . number_format((float)$d['aggregated_price']) . " per kg";
				}
				if (isset($d['confidence'])) {
					$aggLines[] = "• Confidence: " . (string)$d['confidence'] . "; Sources: " . (string)($d['source_count'] ?? 0);
				}
			}
		} catch (\Throwable $e) { /* ignore */ }

        // External lookups (WFP + FAOSTAT)
        $externalLines = [];
        $wfp = $this->fetchWfpRwandaStructured($commodity, $location);
        if ($wfp) {
            $extLoc = $wfp['market'] ? (" at " . $wfp['market']) : '';
            $externalLines[] = "• WFP/Humdata latest: Rwf " . number_format((float)$wfp['price']) . "{$extLoc}" . ($wfp['date'] ? " (".$wfp['date'].")" : "");
        }
        $fao = $this->fetchFaostatRwandaStructured($commodity);
        if ($fao) {
            $externalLines[] = "• FAOSTAT producer price: Rwf " . number_format((float)$fao['price']) . ($fao['year'] ? " (".$fao['year'].")" : "");
        }

        // Compose structured response
        $titleCommodity = ucwords($commodity);
        $titleLoc = $location ? (" — " . $location) : "";
        $response = "💰 Price for {$titleCommodity}{$titleLoc}\n\n";
        $explanation = [];
        if (!empty($internalLines)) {
            $response .= "Internal (platform)\n" . implode("\n", $internalLines) . "\n";
            $explanation[] = "Internal prices are based on recent listings and platform sales.";
        } else {
            $response .= "Internal (platform)\n• No active listings found for this item.\n";
        }
        if (!empty($aggLines)) {
            $response .= "\nAI Aggregation\n" . implode("\n", $aggLines) . "\n";
            $explanation[] = "AI aggregation combines multiple sources and applies statistical analysis for reliability.";
        }
        if (!empty($externalLines)) {
            $response .= (empty($internalLines) && empty($aggLines) ? "" : "\n") . "External sources\n" . implode("\n", $externalLines) . "\n";
            $explanation[] = "External sources include WFP/Humdata and FAOSTAT, which provide official market and producer prices.";
        } else {
            $regionalPrice = $this->getRegionalPriceEstimate($commodity, $location);
            if ($regionalPrice) {
                $response .= (empty($internalLines) && empty($aggLines) ? "" : "\n") . "External sources\n• Regional estimate: Rwf " . number_format($regionalPrice) . " per kg (based on local market data)\n";
                $explanation[] = "Regional estimates are based on typical prices from local Rwandan markets.";
            } else {
                $generic = $this->duckduckgoInstantAnswer($titleCommodity . ' price Rwanda ' . ($location ?: ''));
                $response .= (empty($internalLines) && empty($aggLines) ? "" : "\n") . "External sources\n" . ($generic ? ('• ' . $generic . "\n") : "• No recent external entries found.\n");
                $explanation[] = "Fallback answers use web search for general info if no data is found.";
            }
        }
        if (!empty($explanation)) {
            $response .= "\nℹ️ How this was calculated: " . implode(' ', $explanation) . "\n";
        }
        $response .= "\n💡 Tip: Prices vary by quality, season and market." . (!empty($wfp['source']) ? "\nSource: " . $wfp['source'] : "");
        $this->setLastContext($commodity, $location);
        return $response;
    }
    
    private function handleCategoryPriceQuery(string $category, ?string $location = null): string
    {
        $categories = $this->getCategoryMap();
        $categoryItems = $categories[$category] ?? [];
        
        if (empty($categoryItems)) {
            return "I don't have price information for that category yet. Please ask about specific crops like 'tomato price' or 'maize cost'.";
        }
        
        $titleCategory = ucwords($category);
        $titleLoc = $location ? (" — " . $location) : "";
        $response = "🍎 {$titleCategory} Prices{$titleLoc}\n\n";
        
        $itemCount = 0;
        foreach ($categoryItems as $item) {
            $regionalPrice = $this->getRegionalPriceEstimate($item, $location);
            if ($regionalPrice) {
                $response .= "• **" . ucwords($item) . "**: Rwf " . number_format($regionalPrice) . " per kg\n";
                $itemCount++;
            }
        }
        
        if ($itemCount == 0) {
            $response .= "• No price data available for {$category} at the moment.\n";
        }
        
        $response .= "\n💡 **Tips for {$titleCategory}:**\n";
        
        // Add category-specific tips
        $tips = [
            'fruits' => "• Best to buy seasonal fruits for better prices\n• Check for ripeness and quality\n• Store properly to extend shelf life",
            'vegetables' => "• Fresh vegetables are usually cheaper in local markets\n• Buy in season for best prices\n• Consider organic options for premium quality",
            'grains' => "• Bulk purchases often get better rates\n• Check for moisture content and quality\n• Store in dry, cool conditions",
            'tubers' => "• Root crops are generally stable in price\n• Buy from local farmers for freshness\n• Check for damage and rot",
            'legumes' => "• High protein content makes them good value\n• Dried legumes store well\n• Check for insect damage",
        ];
        
        $response .= $tips[$category] ?? "• Prices vary by season, quality, and market location\n• Buy from local farmers when possible\n• Check for freshness and quality";
            
            return $response;
    }
    
    private function getRegionalPriceEstimate(string $commodity, ?string $location = null): ?float
    {
        // Regional price estimates based on typical Rwandan market prices
        $regionalPrices = [
            'maize' => ['base' => 450, 'kigali' => 480, 'musanze' => 420, 'huye' => 460],
            'tomato' => ['base' => 1200, 'kigali' => 1300, 'musanze' => 1100, 'huye' => 1150],
            'potato' => ['base' => 800, 'kigali' => 850, 'musanze' => 750, 'huye' => 780],
            'banana' => ['base' => 600, 'kigali' => 650, 'musanze' => 580, 'huye' => 620],
            'rice' => ['base' => 1800, 'kigali' => 1900, 'musanze' => 1750, 'huye' => 1850],
            'bean' => ['base' => 2200, 'kigali' => 2300, 'musanze' => 2100, 'huye' => 2250],
            'cassava' => ['base' => 300, 'kigali' => 320, 'musanze' => 280, 'huye' => 290],
            'onion' => ['base' => 900, 'kigali' => 950, 'musanze' => 850, 'huye' => 880],
            'cabbage' => ['base' => 400, 'kigali' => 420, 'musanze' => 380, 'huye' => 390],
            'pineapple' => ['base' => 800, 'kigali' => 850, 'musanze' => 750, 'huye' => 780],
            'apple' => ['base' => 1500, 'kigali' => 1600, 'musanze' => 1400, 'huye' => 1450],
            // Additional fruits
            'orange' => ['base' => 700, 'kigali' => 750, 'musanze' => 680, 'huye' => 720],
            'mango' => ['base' => 500, 'kigali' => 550, 'musanze' => 480, 'huye' => 520],
            'papaya' => ['base' => 400, 'kigali' => 450, 'musanze' => 380, 'huye' => 420],
            'avocado' => ['base' => 600, 'kigali' => 650, 'musanze' => 580, 'huye' => 620],
            'passion fruit' => ['base' => 800, 'kigali' => 850, 'musanze' => 780, 'huye' => 820],
            'guava' => ['base' => 400, 'kigali' => 450, 'musanze' => 380, 'huye' => 420],
            // Additional vegetables
            'carrot' => ['base' => 600, 'kigali' => 650, 'musanze' => 580, 'huye' => 620],
            'lettuce' => ['base' => 300, 'kigali' => 320, 'musanze' => 280, 'huye' => 290],
            'spinach' => ['base' => 200, 'kigali' => 220, 'musanze' => 180, 'huye' => 190],
            'kale' => ['base' => 150, 'kigali' => 170, 'musanze' => 140, 'huye' => 160],
            'eggplant' => ['base' => 500, 'kigali' => 550, 'musanze' => 480, 'huye' => 520],
            'pepper' => ['base' => 800, 'kigali' => 850, 'musanze' => 780, 'huye' => 820],
            // Additional grains and tubers
            'sweet potato' => ['base' => 400, 'kigali' => 420, 'musanze' => 380, 'huye' => 390],
            'yam' => ['base' => 500, 'kigali' => 550, 'musanze' => 480, 'huye' => 520],
            'taro' => ['base' => 350, 'kigali' => 370, 'musanze' => 330, 'huye' => 340],
            'sorghum' => ['base' => 400, 'kigali' => 420, 'musanze' => 380, 'huye' => 390],
            'millet' => ['base' => 500, 'kigali' => 550, 'musanze' => 480, 'huye' => 520],
            'wheat' => ['base' => 600, 'kigali' => 650, 'musanze' => 580, 'huye' => 620],
            // Additional legumes
            'pea' => ['base' => 800, 'kigali' => 850, 'musanze' => 780, 'huye' => 820],
            'lentil' => ['base' => 1200, 'kigali' => 1300, 'musanze' => 1150, 'huye' => 1250],
            'chickpea' => ['base' => 1000, 'kigali' => 1100, 'musanze' => 950, 'huye' => 1050],
            'cowpea' => ['base' => 900, 'kigali' => 950, 'musanze' => 850, 'huye' => 880],
        ];
        
        $commodityLower = strtolower($commodity);
        if (!isset($regionalPrices[$commodityLower])) {
            return null;
        }
        
        $prices = $regionalPrices[$commodityLower];
        if ($location) {
            $locationLower = strtolower($location);
            return $prices[$locationLower] ?? $prices['base'];
        }
        
        return $prices['base'];
    }
    
    private function handleDemandQuery($message) {
        $forecasts = $this->getDemandForecasts();
        
        if (empty($forecasts)) {
            return "📊 **Demand Forecast**\n\nCurrently, there's insufficient data to generate demand forecasts. As more sales data becomes available, I'll be able to provide accurate predictions for crop demand.";
        }
        
        $response = "📊 **Demand Forecast (Next Week)**\n\n";
        foreach ($forecasts as $forecast) {
            $response .= "• **{$forecast['crop_name']}**: Expected demand of " . number_format($forecast['forecast_value']) . " kg\n";
        }
        $response .= "\n💡 *These forecasts are based on recent sales patterns and market trends.*";
        
        return $response;
    }
    
    private function handleCropQuery($message) {
        $crops = $this->getAvailableCrops();
        
        if (empty($crops)) {
            return "🌱 **Available Crops**\n\nCurrently, there are no crops listed for sale. Farmers can list their crops through the 'List New Crop' feature.";
        }
        
        $response = "🌱 **Available Crops**\n\n";
        foreach ($crops as $crop) {
            $response .= "• **{$crop['name']}** - " . number_format($crop['quantity']) . " {$crop['unit']} at Rwf " . number_format($crop['price']) . "/{$crop['unit']}\n";
        }
        $response .= "\n💡 *You can place orders for any of these crops through the platform.*";
        
        return $response;
    }
    
    private function handleMarketQuery($message) {
		// Use our enhanced AI system for better market data
		$aiService = new AIServiceBridge();

		$intro = $this->pick([
			"📈 **Current Market Prices & Trends**",
			"📊 **Live Market Snapshot**",
			"📉 **Market Update**"
		]);
		$response = $intro . "\n\n";

		// Attempt to build a market snapshot using AI aggregation for popular crops
		$popularCrops = ['tomato', 'maize', 'rice', 'potato', 'banana', 'bean'];
		$marketSnap = [];
		foreach ($popularCrops as $crop) {
			try {
				$priceData = $aiService->getPricePrediction($crop);
				if (!empty($priceData['success']) && !empty($priceData['data'])) {
					$data = $priceData['data'];
					$marketSnap[] = [
						'crop' => ucfirst($crop),
						'price' => (float)($data['aggregated_price'] ?? $data['mean_price'] ?? 0),
						'confidence' => (float)($data['confidence'] ?? 0),
						'sources' => (int)($data['source_count'] ?? 0)
					];
				}
			} catch (\Throwable $e) {
				// ignore AI errors and fallback
			}
		}

		if (!empty($marketSnap)) {
			$response .= $this->pick([
				"**Top Agricultural Commodities:**\n",
				"**Key Crops Today:**\n",
				"**Headline Prices:**\n"
			]);
			foreach ($marketSnap as $item) {
				if ($item['price'] > 0) {
					$response .= "• **{$item['crop']}**: Rwf " . number_format($item['price']) . " per kg";
					if ($item['confidence'] > 0.7) { $response .= " (High confidence)"; }
					$response .= "\n";
				}
			}
			$response .= "\n" . $this->pick(["**Market Insights:**\n", "**Notes:**\n", "**What this means:**\n"]);
			$response .= $this->joinBullets([
				"Data aggregated from multiple sources",
				"Prices updated in near real-time",
				"Seasonal factors considered"
			]) . "\n";
			$response .= "\nℹ️ Prices shown above are calculated using live AI aggregation and platform data, considering market trends and seasonality.";
		} else {
			// Fallback to internal market trends if AI data is unavailable
			$trends = $this->getMarketTrends();
			if (empty($trends)) {
				return "📈 **Market Trends**\n\nMarket data is currently being collected. Check back soon for price trends and market insights.";
			}
			$response .= "**Recent Market Prices (7d):**\n";
			foreach ($trends as $row) {
				$crop = ucfirst($row['commodity']);
				$price = (float)$row['price'];
				$market = $row['market'];
				$response .= "• **{$crop}**: Rwf " . number_format($price) . "/kg at {$market}\n";
			}
			$response .= "\nℹ️ Prices above reflect recent listings and market entries in the last week.";
		}

		// Profitability tips
		$response .= "\n\n**Profitability Tips**\n";
		$response .= "• High-value crops: Tomatoes, Beans, Rice (good margins)\n";
		$response .= "• Low-risk crops: Cassava, Sweet Potato (stable demand)\n";
		$response .= "• Quick harvest: Lettuce, Spinach (30–45 days)\n";
		$response .= "• Long-term: Maize, Beans (3–4 months)\n\n";

		// Top recommendations using current season
		$season = $this->getCurrentSeason((int)date('n'));
		$seasonalCrops = $this->getSeasonalCrops($season);
		$top = [];
		foreach ($marketSnap as $s) {
			if (!empty($s['price'])) { $top[] = $s['crop']; }
			if (count($top) >= 3) break;
		}
		if (count($top) < 3) {
			foreach ($seasonalCrops as $c) {
				$u = ucwords($c);
				if (!in_array($u, $top, true)) { $top[] = $u; }
				if (count($top) >= 3) break;
			}
		}
		$response .= "**Top Recommendations for " . ucwords(str_replace('_',' ', $season)) . "**\n";
		$rank = 1;
		$seasonalLower = array_map('strtolower', $seasonalCrops);
		foreach ($top as $c) {
			$reason = in_array(strtolower($c), $seasonalLower, true) ? 'in-season & supported by market' : 'market-supported';
			$response .= ($rank++) . ". **" . $c . "** - " . $reason . "\n";
		}

		$response .= "\n💡 Tips: Consider soil, water, and local market preferences. Start small and scale with confirmed demand.";
		return $response;
    }
    
    private function getCurrentSeason($month): string {
        if ($month >= 3 && $month <= 5) return 'short_rainy';
        if ($month >= 9 && $month <= 11) return 'long_rainy';
        // Remaining months considered dry season in this simplified model
        return 'dry';
    }
    
    private function getSeasonalCrops($season): array {
        $seasonalCrops = [
            'short_rainy' => ['tomato', 'pepper', 'lettuce', 'spinach', 'kale'],
            'long_rainy' => ['maize', 'bean', 'rice', 'cassava'],
            'dry' => ['cassava', 'sweet potato', 'drought-resistant varieties']
        ];
        return $seasonalCrops[$season] ?? ['maize', 'bean'];
    }
    
    private function getHighDemandCrops(): array {
        // Analyze market data to determine high demand crops
		if (!$this->conn) return ['Tomato', 'Maize', 'Bean', 'Rice', 'Cassava'];
        $stmt = $this->conn->prepare("
            SELECT commodity, COUNT(*) as demand_count 
            FROM market_prices 
            WHERE date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
            GROUP BY commodity 
            ORDER BY demand_count DESC 
            LIMIT 5
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        $highDemand = [];
        while ($row = $result->fetch_assoc()) {
            $highDemand[] = ucfirst($row['commodity']);
        }
        
        // Fallback if no data
        if (empty($highDemand)) {
            $highDemand = ['Tomato', 'Maize', 'Bean', 'Rice', 'Cassava'];
        }
        
        return $highDemand;
    }
    
    private function getStablePriceCrops(): string {
        return "Cassava, Sweet Potato, Maize";
    }
    
    private function getTopRecommendations($season, $highDemandCrops): array {
        $recommendations = [
            [
                'crop' => 'Tomato',
                'reason' => 'High demand, good profit margins, suitable for current season'
            ],
            [
                'crop' => 'Maize',
                'reason' => 'Stable market, good for food security, suitable for rainy season'
            ],
            [
                'crop' => 'Beans',
                'reason' => 'High protein value, good market demand, suitable for current season'
            ]
        ];
        
        // Adjust based on season
        if ($season === 'dry') {
            $recommendations = [
                [
                    'crop' => 'Cassava',
                    'reason' => 'Drought-resistant, stable demand, good for dry season'
                ],
                [
                    'crop' => 'Sweet Potato',
                    'reason' => 'Drought-tolerant, nutritious, suitable for current conditions'
                ],
                [
                    'crop' => 'Drought-resistant Maize',
                    'reason' => 'Special varieties for dry conditions, stable market'
                ]
            ];
        }
        
        return $recommendations;
    }
    
    private function handleWeatherQuery($message) {
        return "🌤️ **Seasonal Farming Advice**\n\n" .
               "**Current Season Tips:**\n" .
               "• **Dry Season** (June-August): Focus on drought-resistant crops like cassava, sweet potatoes\n" .
               "• **Rainy Season** (March-May, September-November): Ideal for maize, beans, vegetables\n\n" .
               "**Recommended Crops by Season:**\n" .
               "• **Short Rainy Season**: Tomatoes, peppers, leafy greens\n" .
               "• **Long Rainy Season**: Maize, beans, Irish potatoes\n" .
               "• **Dry Season**: Cassava, sweet potatoes, drought-resistant varieties\n\n" .
               "💡 *Always check local weather forecasts and soil conditions before planting.*";
    }

	private function handleSafetyQuery($message) {
		$response = "🔒 **Safety & Trust on FarmBridge**\n\n";
		$response .= $this->joinBullets([
			"Payments can use escrow so funds release only after delivery",
			"Verified sellers and optional ID checks help reduce fraud",
			"Receipts and order tracking provide transparency",
			"Dispute resolution and refunds supported for eligible cases"
		]);
		$response .= "\n\n💡 Tip: Chat inside the platform and avoid off-platform payments.";
		return $response;
	}

	private function handleHowPlatformWorks() {
		$steps = [
			"Browse available crops or ask me for prices",
			"Place an order and choose delivery or pickup",
			"Pay securely (escrow optional)",
			"Farmer confirms and fulfills the order",
			"Release payment after you confirm delivery"
		];
		return "🛠️ **How FarmBridge Works**\n\n" . $this->joinBullets($steps) . "\n\nNeed help with any step? Just ask!";
	}

	private function handlePlantingAdviceQuery($message) {
		$season = $this->getCurrentSeason((int)date('n'));
		$seasonal = $this->getSeasonalCrops($season);
		$intro = $this->pick([
			"🌱 **Planting Advice**",
			"🌿 **What to Plant**",
			"🌾 **Recommendations**"
		]);
		$response = $intro . "\n\n";
		$response .= "Best for this season (" . ucwords(str_replace('_',' ', $season)) . "):\n";
		foreach ($seasonal as $c) { $response .= "• **" . ucwords($c) . "**\n"; }
		$response .= "\nTips:\n";
		$response .= $this->joinBullets([
			"Choose varieties suited to local climate",
			"Check soil fertility and irrigate during dry spells",
			"Start small, validate local demand, then scale"
		]);
		return $response;
	}

	private function handleHelpQuery($message) {
		$examples = [
			"price of tomatoes in Kigali",
			"market trends for maize",
			"what should I plant this season?",
			"how does your platform work?",
			"is payment safe?"
		];
		return "❓ **How I Can Help**\n\nAsk about prices, market trends, planting advice, safety, or weather.\nTry: " . $this->joinBullets($examples);
	}
    
    private function handleGeneralQuery($message) {
		// Try to use AI intent classification for better responses
		$aiService = new AIServiceBridge();
		$intentResult = $aiService->classifyIntent($message);
		
		if ($intentResult['success']) {
			$intent = $intentResult['intent'];
			
			// Route based on AI classification
			switch ($intent) {
				case 'price':
					return $this->handlePriceQuery($message);
				case 'market':
					return $this->handleMarketQuery($message);
				case 'advice':
					return $this->handlePlantingAdviceQuery($message);
				case 'how_platform':
					return $this->handleHowPlatformWorks();
				case 'safety':
					return $this->handleSafetyQuery($message);
				case 'demand':
					return $this->handleDemandQuery($message);
				case 'weather':
					return $this->handleWeatherQuery($message);
			}
		}
		
        // Try external agricultural sources first (WFP/FAOSTAT), then generic web lookup
        $external = $this->tryExternalSources($message);
        if ($external) {
            return $external;
        }

        $generalResponses = [
            "I'm here to help with all your farming and crop-related questions! Try asking about prices, market trends, or farming advice.",
            "Welcome to FarmBridge AI! I can provide information about crops, prices, market trends, and farming best practices.",
            "I'm your farming assistant! Ask me about crop prices, demand forecasts, market trends, or seasonal farming advice.",
            "Need help with farming decisions? I can assist with price information, market insights, and crop recommendations.",
            "Hello! I'm here to support your farming journey. Ask me anything about crops, markets, or agricultural practices."
        ];
        return $generalResponses[array_rand($generalResponses)];
    }
    
    // Database helper methods
    private function getAvailableCrops() {
		if (!$this->conn) return [];
        $stmt = $this->conn->prepare("SELECT id, name, quantity, unit, price FROM crops WHERE status = 'available' ORDER BY listed_at DESC LIMIT 10");
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    private function getAveragePrice($cropId) {
		if (!$this->conn) return 0;
        $stmt = $this->conn->prepare("SELECT AVG(price) as avg_price FROM crop_sales WHERE crop_id = ? AND sale_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
        $stmt->bind_param("i", $cropId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['avg_price'] ?? 0;
    }
    
    private function getMarketPrice($cropName) {
		if (!$this->conn) return 0;
        $stmt = $this->conn->prepare("SELECT AVG(price) as avg_price FROM market_prices WHERE commodity LIKE ? AND date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
        $searchTerm = "%$cropName%";
        $stmt->bind_param("s", $searchTerm);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['avg_price'] ?? 0;
    }

    private function getInternalMarketAverage(string $commodity, int $days, ?string $location = null)
    {
		if (!$this->conn) return 0;
        // First, ensure we have some sample market data
        $this->ensureSampleMarketData();
        
        if ($location) {
            $stmt = $this->conn->prepare(
                "SELECT AVG(price) as avg_price FROM market_prices WHERE commodity LIKE ? AND market LIKE ? AND date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)"
            );
            $commodityLike = "%$commodity%";
            $marketLike = "%$location%";
            $stmt->bind_param("ssi", $commodityLike, $marketLike, $days);
        } else {
            $stmt = $this->conn->prepare(
                "SELECT AVG(price) as avg_price FROM market_prices WHERE commodity LIKE ? AND date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)"
            );
            $commodityLike = "%$commodity%";
            $stmt->bind_param("si", $commodityLike, $days);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return (float)($row['avg_price'] ?? 0);
    }

    private function getInternalMarketAverageShifted(string $commodity, int $days, int $shiftDays, ?string $location = null): float
    {
		if (!$this->conn) return 0;
        if ($location) {
            $stmt = $this->conn->prepare(
                "SELECT AVG(price) as avg_price FROM market_prices WHERE commodity LIKE ? AND market LIKE ? AND date >= DATE_SUB(CURDATE(), INTERVAL ? DAY) - INTERVAL ? DAY AND date < DATE_SUB(CURDATE(), INTERVAL ? DAY)"
            );
            $commodityLike = "%$commodity%";
            $marketLike = "%$location%";
            $stmt->bind_param("ssiii", $commodityLike, $marketLike, $shiftDays + $days, $shiftDays, $shiftDays);
        } else {
            $stmt = $this->conn->prepare(
                "SELECT AVG(price) as avg_price FROM market_prices WHERE commodity LIKE ? AND date >= DATE_SUB(CURDATE(), INTERVAL ? DAY) - INTERVAL ? DAY AND date < DATE_SUB(CURDATE(), INTERVAL ? DAY)"
            );
            $commodityLike = "%$commodity%";
            $stmt->bind_param("sii", $commodityLike, $shiftDays + $days, $shiftDays);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return (float)($row['avg_price'] ?? 0);
    }
    
    private function ensureSampleMarketData()
    {
		if (!$this->conn) return;
        // Check if we have recent market data, if not, insert sample data
        $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM market_prices WHERE date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row['count'] == 0) {
            $this->insertSampleMarketData();
        }
    }
    
    private function insertSampleMarketData()
    {
		if (!$this->conn) return;
        $sampleData = [
            ['commodity' => 'maize', 'market' => 'Kigali', 'price' => 450, 'date' => date('Y-m-d', strtotime('-1 day')), 'source' => 'Local Market'],
            ['commodity' => 'maize', 'market' => 'Musanze', 'price' => 420, 'date' => date('Y-m-d', strtotime('-2 days')), 'source' => 'Local Market'],
            ['commodity' => 'maize', 'market' => 'Huye', 'price' => 480, 'date' => date('Y-m-d', strtotime('-3 days')), 'source' => 'Local Market'],
            ['commodity' => 'tomato', 'market' => 'Kigali', 'price' => 1200, 'date' => date('Y-m-d', strtotime('-1 day')), 'source' => 'Local Market'],
            ['commodity' => 'tomato', 'market' => 'Musanze', 'price' => 1100, 'date' => date('Y-m-d', strtotime('-2 days')), 'source' => 'Local Market'],
            ['commodity' => 'potato', 'market' => 'Kigali', 'price' => 800, 'date' => date('Y-m-d', strtotime('-1 day')), 'source' => 'Local Market'],
            ['commodity' => 'potato', 'market' => 'Musanze', 'price' => 750, 'date' => date('Y-m-d', strtotime('-2 days')), 'source' => 'Local Market'],
            ['commodity' => 'banana', 'market' => 'Kigali', 'price' => 600, 'date' => date('Y-m-d', strtotime('-1 day')), 'source' => 'Local Market'],
            ['commodity' => 'banana', 'market' => 'Huye', 'price' => 550, 'date' => date('Y-m-d', strtotime('-2 days')), 'source' => 'Local Market'],
            ['commodity' => 'rice', 'market' => 'Kigali', 'price' => 1800, 'date' => date('Y-m-d', strtotime('-1 day')), 'source' => 'Local Market'],
            ['commodity' => 'rice', 'market' => 'Nyagatare', 'price' => 1700, 'date' => date('Y-m-d', strtotime('-2 days')), 'source' => 'Local Market'],
            ['commodity' => 'bean', 'market' => 'Kigali', 'price' => 2200, 'date' => date('Y-m-d', strtotime('-1 day')), 'source' => 'Local Market'],
            ['commodity' => 'bean', 'market' => 'Musanze', 'price' => 2100, 'date' => date('Y-m-d', strtotime('-2 days')), 'source' => 'Local Market'],
            ['commodity' => 'cassava', 'market' => 'Kigali', 'price' => 300, 'date' => date('Y-m-d', strtotime('-1 day')), 'source' => 'Local Market'],
            ['commodity' => 'cassava', 'market' => 'Huye', 'price' => 280, 'date' => date('Y-m-d', strtotime('-2 days')), 'source' => 'Local Market'],
        ];
        
        $stmt = $this->conn->prepare("INSERT INTO market_prices (commodity, market, price, date, source) VALUES (?, ?, ?, ?, ?)");
        foreach ($sampleData as $data) {
            $stmt->bind_param("ssdss", $data['commodity'], $data['market'], $data['price'], $data['date'], $data['source']);
            $stmt->execute();
        }
        
        // Also insert sample crop sales data for average price calculations
        $this->insertSampleCropSalesData();
    }
    
    private function insertSampleCropSalesData()
    {
		if (!$this->conn) return;
        // Check if we have crop sales data
        $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM crop_sales WHERE sale_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row['count'] == 0) {
            // Get available crops for sample sales data
            $crops = $this->getAvailableCrops();
            if (!empty($crops)) {
                $sampleSalesData = [
                    ['crop_id' => $crops[0]['id'], 'farmer_id' => 1, 'buyer_id' => 2, 'quantity' => 50, 'price' => $crops[0]['price'], 'sale_date' => date('Y-m-d', strtotime('-5 days'))],
                    ['crop_id' => $crops[0]['id'], 'farmer_id' => 1, 'buyer_id' => 3, 'quantity' => 30, 'price' => $crops[0]['price'] * 0.95, 'sale_date' => date('Y-m-d', strtotime('-10 days'))],
                ];
                
                $stmt = $this->conn->prepare("INSERT INTO crop_sales (crop_id, farmer_id, buyer_id, quantity, price, sale_date) VALUES (?, ?, ?, ?, ?, ?)");
                foreach ($sampleSalesData as $data) {
                    $stmt->bind_param("iiiids", $data['crop_id'], $data['farmer_id'], $data['buyer_id'], $data['quantity'], $data['price'], $data['sale_date']);
                    $stmt->execute();
                }
            }
        }
    }
    
    private function getDemandForecasts() {
		if (!$this->conn) return [];
        $stmt = $this->conn->prepare("SELECT crop_name, forecast_value FROM demand_forecast WHERE period = 'next_week' ORDER BY forecast_value DESC LIMIT 5");
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    private function getMarketTrends() {
		if (!$this->conn) return [];
        $stmt = $this->conn->prepare("SELECT commodity, price, market FROM market_prices WHERE date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) ORDER BY date DESC LIMIT 5");
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // -------- External data integrations ---------
    private function tryExternalSources(string $query)
    {
        // 1) Try WFP / Humdata for Rwanda food prices (public datasets often expose CSV/JSON)
        $wfp = $this->fetchWfpRwanda($query);
        if ($wfp) return $wfp;

        // 2) Try FAOSTAT producer prices via Humdata
        $faostat = $this->fetchFaostatRwanda($query);
        if ($faostat) return $faostat;

        // 3) Generic knowledge via DuckDuckGo Instant Answer (free)
        $duck = $this->duckduckgoInstantAnswer($query);
        if ($duck) return $duck;

        return false;
    }

    private function fetchJson(string $url)
    {
        $ctx = stream_context_create([
            'http' => [
                'timeout' => 6,
                'ignore_errors' => true,
                'header' => "User-Agent: FarmBridgeBot/1.0\r\n"
            ]
        ]);
        $raw = @file_get_contents($url, false, $ctx);
        if (!$raw) return false;
        $data = json_decode($raw, true);
        return $data ?: false;
    }

    private function fetchCsv(string $url)
    {
        $ctx = stream_context_create([
            'http' => [
                'timeout' => 6,
                'ignore_errors' => true,
                'header' => "User-Agent: FarmBridgeBot/1.0\r\n"
            ]
        ]);
        $raw = @file_get_contents($url, false, $ctx);
        if (!$raw) return false;
        $rows = array_map('str_getcsv', preg_split("/\r\n|\n|\r/", trim($raw)));
        if (!$rows || count($rows) < 2) return false;
        $header = array_map('trim', $rows[0]);
        $data = [];
        for ($i = 1; $i < count($rows); $i++) {
            if (count($rows[$i]) !== count($header)) continue;
            $data[] = array_combine($header, $rows[$i]);
        }
        return $data;
    }

    // ----------------- External structured helpers -----------------
    private function fetchWfpRwandaStructured(string $commodity, ?string $location = null)
    {
        $candidateUrls = [
            // WFP VAM Food Prices - Rwanda (if available)
            'https://data.humdata.org/dataset/wfp-food-prices-for-rwanda/resource/download/rwanda_food_prices.csv',
            // Alternative: WFP Global Food Prices API endpoint
            'https://api.humdata.org/v1/datasets/wfp-food-prices-rwanda/data.csv',
        ];
        $synonyms = $this->getSynonymsMap();
        $aliases = array_unique(array_merge([$commodity], $synonyms[$commodity] ?? []));
        foreach ($candidateUrls as $url) {
            $rows = str_ends_with($url, '.csv') ? $this->fetchCsv($url) : $this->fetchJson($url);
            if (!$rows) continue;
            $best = null;
            foreach ($rows as $row) {
                $item = strtolower(trim($row['commodity'] ?? $row['item'] ?? ''));
                if (!$item) continue;
                $matchesCommodity = false;
                foreach ($aliases as $alias) {
                    if ($alias && preg_match('/\\b' . preg_quote(strtolower($alias), '/') . '\\b/', $item)) {
                        $matchesCommodity = true; break;
                    }
                }
                if (!$matchesCommodity) continue;
                if ($location) {
                    $marketVal = strtolower(trim($row['market'] ?? $row['location'] ?? ''));
                    if ($marketVal && strpos($marketVal, strtolower($location)) === false) {
                        // keep scanning; allow non-matching rows in case no exact market record exists
                    }
                }
                $price = $row['price'] ?? $row['value'] ?? null;
                if (!$price) continue;
                $date = $row['date'] ?? $row['period'] ?? '';
                $market = $row['market'] ?? $row['location'] ?? '';
                $recordTs = $date ? strtotime($date) : 0;
                if (!$best || $recordTs > ($best['ts'] ?? 0)) {
                    $best = [
                        'price' => (float)$price,
                        'date' => $date,
                        'market' => $market,
                        'ts' => $recordTs,
                        'source' => $url,
                    ];
                }
            }
            if ($best) { unset($best['ts']); return $best; }
        }
        return null;
    }

    private function fetchFaostatRwandaStructured(string $commodity)
    {
        $candidateUrls = [
            // FAOSTAT Producer Prices - Rwanda
            'https://data.humdata.org/dataset/faostat-producer-prices-rwanda/resource/download/rwanda_producer_prices.csv',
            // Alternative: FAOSTAT API endpoint
            'https://api.fao.org/faostat/api/v1/en/data/PPRICE?area=180&element=5532&item=' . urlencode($commodity) . '&format=json',
        ];
        $synonyms = $this->getSynonymsMap();
        $aliases = array_unique(array_merge([$commodity], $synonyms[$commodity] ?? []));
        foreach ($candidateUrls as $url) {
            $rows = str_ends_with($url, '.csv') ? $this->fetchCsv($url) : $this->fetchJson($url);
            if (!$rows) continue;
            $best = null;
            foreach ($rows as $row) {
                $item = strtolower(trim($row['commodity'] ?? $row['item'] ?? ''));
                if (!$item) continue;
                $matchesCommodity = false;
                foreach ($aliases as $alias) {
                    if ($alias && preg_match('/\\b' . preg_quote(strtolower($alias), '/') . '\\b/', $item)) { $matchesCommodity = true; break; }
                }
                if (!$matchesCommodity) continue;
                $price = $row['price'] ?? $row['value'] ?? null;
                if (!$price) continue;
                $year = $row['year'] ?? '';
                $yearTs = $year ? strtotime($year.'-01-01') : 0;
                if (!$best || $yearTs > ($best['ts'] ?? 0)) {
                    $best = [ 'price' => (float)$price, 'year' => $year, 'ts' => $yearTs, 'source' => $url ];
                }
            }
            if ($best) { unset($best['ts']); return $best; }
        }
        return null;
    }

    // Try WFP / Humdata (replace with the exact resource URL you choose)
    private function fetchWfpRwanda(string $query)
    {
        // Official Humdata/HDX WFP Rwanda food prices CSV/JSON endpoints
        $candidateUrls = [
            // WFP VAM Food Prices - Rwanda
            'https://data.humdata.org/dataset/wfp-food-prices-for-rwanda/resource/download/rwanda_food_prices.csv',
            // Alternative: WFP Global Food Prices API endpoint
            'https://api.humdata.org/v1/datasets/wfp-food-prices-rwanda/data.csv',
        ];
        foreach ($candidateUrls as $url) {
            $data = str_ends_with($url, '.csv') ? $this->fetchCsv($url) : $this->fetchJson($url);
            if (!$data) continue;
            // naive match: look for a commodity mentioned in the query
            $q = strtolower($query);
            foreach ($data as $row) {
                $commodity = strtolower(($row['commodity'] ?? $row['item'] ?? ''));
                if ($commodity && strpos($q, $commodity) !== false) {
                    $price = $row['price'] ?? $row['value'] ?? null;
                    $market = $row['market'] ?? $row['location'] ?? 'market';
                    $date = $row['date'] ?? $row['period'] ?? '';
                    if ($price) {
                        return "📈 External (WFP/Humdata): {$commodity} ≈ RWF " . number_format((float)$price, 0) . " at {$market} (" . $date . ")";
                    }
                }
            }
        }
        return false;
    }

    private function fetchFaostatRwanda(string $query)
    {
        // Official Humdata/HDX FAOSTAT Rwanda producer prices CSV/JSON endpoints
        $candidateUrls = [
            // FAOSTAT Producer Prices - Rwanda
            'https://data.humdata.org/dataset/faostat-producer-prices-rwanda/resource/download/rwanda_producer_prices.csv',
            // Alternative: FAOSTAT API endpoint
            'https://api.fao.org/faostat/api/v1/en/data/PPRICE?area=180&element=5532&item=' . urlencode($query) . '&format=json',
        ];
        foreach ($candidateUrls as $url) {
            $data = str_ends_with($url, '.csv') ? $this->fetchCsv($url) : $this->fetchJson($url);
            if (!$data) continue;
            $q = strtolower($query);
            foreach ($data as $row) {
                $commodity = strtolower(($row['commodity'] ?? $row['item'] ?? ''));
                if ($commodity && strpos($q, $commodity) !== false) {
                    $price = $row['price'] ?? $row['value'] ?? null;
                    $year = $row['year'] ?? '';
                    if ($price) {
                        return "📊 External (FAOSTAT): Producer price for {$commodity} ≈ RWF " . number_format((float)$price, 0) . ($year ? " (".$year.")" : "");
                    }
                }
            }
        }
        return false;
    }

    private function duckduckgoInstantAnswer(string $query)
    {
        $url = 'https://api.duckduckgo.com/?q=' . urlencode($query) . '&format=json&no_redirect=1&no_html=1';
        $data = $this->fetchJson($url);
        if (!$data) return false;
        if (!empty($data['AbstractText'])) return $data['AbstractText'];
        if (!empty($data['Answer'])) return $data['Answer'];
        if (!empty($data['RelatedTopics'][0]['Text'])) return $data['RelatedTopics'][0]['Text'];
        return false;
    }

    // ----------------- NLP helpers -----------------
    private function isGreeting(string $message): bool
    {
        $greet = ['hi', 'hello', 'hey', 'muraho', 'greetings', 'good morning', 'good afternoon', 'good evening'];
        foreach ($greet as $g) {
            if (preg_match('/\b' . preg_quote($g, '/') . '\b/i', $message)) { return true; }
        }
        return false;
    }

    private function getSynonymsMap(): array
    {
        // Use the enhanced ProductMatcher for comprehensive synonym handling
        require_once 'product_synonyms.php';
        $matcher = new ProductMatcher();
        
        $synonyms_map = [];
        foreach ($matcher->getAllProducts() as $product) {
            $product_synonyms = $matcher->getSynonyms($product);
            $all_terms = [];
            
            // Combine all synonym categories
            foreach ($product_synonyms as $category => $terms) {
                $all_terms = array_merge($all_terms, $terms);
            }
            
            $synonyms_map[$product] = $all_terms;
        }
        
        return $synonyms_map;
    }

    private function resolveCommodityFromQuery(string $message, array $availableCrops): array
    {
        $synonyms = $this->getSynonymsMap();
        $categories = $this->getCategoryMap();
        
        // Build candidate canonical list from synonyms and available crops
        $canonicals = array_unique(array_merge(
            array_keys($synonyms),
            array_map(function($c){ return strtolower($c['name']); }, $availableCrops)
        ));

        $foundCanonical = null;
        foreach ($canonicals as $canonical) {
            $aliases = array_unique(array_merge([$canonical], $synonyms[$canonical] ?? []));
            foreach ($aliases as $alias) {
                if ($alias && preg_match('/\b' . preg_quote(strtolower($alias), '/') . '\b/', $message)) {
                    $foundCanonical = $canonical; break 2;
                }
            }
        }

        // If we found a specific commodity, prioritize it over category
        if ($foundCanonical) {
            $matchedCrop = null;
            foreach ($availableCrops as $crop) {
                if (strcasecmp($crop['name'], $foundCanonical) === 0) { $matchedCrop = $crop; break; }
                // Also match against synonyms
                foreach ($synonyms[$foundCanonical] ?? [] as $alias) {
                    if (strcasecmp($crop['name'], $alias) === 0) { $matchedCrop = $crop; break 2; }
                }
            }
            return ['commodity' => $foundCanonical, 'matchedCrop' => $matchedCrop];
        }

        // Only check for category if no specific commodity was found
        $category = $this->detectCategory($message);
        if ($category) {
            return ['category' => $category, 'commodity' => null, 'matchedCrop' => null];
        }

        return ['commodity' => null, 'matchedCrop' => null];
    }
    
    private function detectCategory(string $message): ?string
    {
        $categories = [
            'fruits' => ['fruit', 'fruits', 'mizabibu', 'imbuto'],
            'vegetables' => ['vegetable', 'vegetables', 'imboga', 'imboga zose'],
            'grains' => ['grain', 'grains', 'cereal', 'cereals', 'uburo'],
            'tubers' => ['tuber', 'tubers', 'root', 'roots', 'root crop', 'root crops'],
            'legumes' => ['legume', 'legumes', 'bean', 'beans', 'pulse', 'pulses'],
        ];
        
        $messageLower = strtolower($message);
        foreach ($categories as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if (preg_match('/\b' . preg_quote($keyword, '/') . '\b/', $messageLower)) {
                    return $category;
                }
            }
        }
        return null;
    }
    
    private function getCategoryMap(): array
    {
        return [
            'fruits' => ['apple', 'banana', 'pineapple', 'orange', 'mango', 'papaya', 'avocado', 'passion fruit', 'guava'],
            'vegetables' => ['tomato', 'onion', 'cabbage', 'carrot', 'lettuce', 'spinach', 'kale', 'eggplant', 'pepper'],
            'grains' => ['maize', 'rice', 'wheat', 'sorghum', 'millet'],
            'tubers' => ['potato', 'cassava', 'sweet potato', 'yam', 'taro'],
            'legumes' => ['bean', 'pea', 'lentil', 'chickpea', 'cowpea'],
        ];
    }

    private function parseTimeframeFromQuery(string $message): string
    {
        $m = strtolower($message);
        if (preg_match('/\b(next\s+month)\b/', $m)) return 'next_month';
        if (preg_match('/\b(today|now)\b/', $m)) return 'today';
        if (preg_match('/\b(this\s+week)\b/', $m)) return 'this_week';
        if (preg_match('/\b(last\s+week)\b/', $m)) return 'last_week';
        if (preg_match('/\b(this\s+month)\b/', $m)) return 'this_month';
        if (preg_match('/\b(last\s+month)\b/', $m)) return 'last_month';
        return 'latest';
    }

    private function labelForTimeframe(string $timeframe, int $days): string
    {
        switch ($timeframe) {
            case 'today': return 'today';
            case 'this_week': return '7d';
            case 'last_week': return '7d';
            case 'this_month': return '30d';
            case 'last_month': return '30d';
            default: return $days . 'd';
        }
    }

    private function forecastPriceForNextMonth(string $commodity, ?string $location = null): ?array
    {
		if (!$this->conn) return null;
        $recentAvg = $this->getInternalMarketAverage($commodity, 30, $location);
        $prevAvg = $this->getInternalMarketAverageShifted($commodity, 30, 30, $location);
        if ($recentAvg <= 0 && $prevAvg <= 0) {
            $est = $this->getRegionalPriceEstimate($commodity, $location);
            return $est ? ['price' => $est, 'basis' => 'regional baseline'] : null;
        }
        if ($prevAvg <= 0) {
            return ['price' => $recentAvg, 'basis' => 'recent 30-day average'];
        }
        $growth = ($recentAvg - $prevAvg) / max($prevAvg, 1);
        $growth = max(min($growth, 0.25), -0.25);
        $forecast = $recentAvg * (1 + $growth);
        return [
            'price' => $forecast,
            'basis' => 'trend from prior 30d vs recent 30d'
        ];
    }

    private function detectLocationFromQuery(string $message): ?string
    {
        $markets = ['kigali','musanze','huye','nyagatare','rubavu','rusizi','muhanga','bugesera','rwamagana','gicumbi','kamonyi'];
        foreach ($markets as $m) {
            if (preg_match('/\b' . preg_quote($m, '/') . '\b/i', $message)) { return ucwords($m); }
        }
        return null;
    }

    private function getLastContext(): array
    {
        return [
            'commodity' => $_SESSION['last_commodity'] ?? null,
            'location' => $_SESSION['last_location'] ?? null,
        ];
    }

    private function setLastContext(?string $commodity, ?string $location): void
    {
        if ($commodity) $_SESSION['last_commodity'] = $commodity;
        if ($location) $_SESSION['last_location'] = $location;
    }
}

if (!defined('CHATBOT_LIBRARY_ONLY')) {
	$method = $_SERVER['REQUEST_METHOD'];
	if ($method === 'POST' || $method === 'GET') {
		$raw = file_get_contents('php://input');
		$input = json_decode($raw, true);
		$message = '';
		if (is_array($input) && isset($input['message'])) {
			$message = $input['message'];
		} elseif (isset($_POST['message'])) {
			$message = $_POST['message'];
		} elseif (isset($_GET['message'])) {
			$message = $_GET['message'];
		} elseif (isset($_REQUEST['message'])) {
			$message = $_REQUEST['message'];
		}
		try {
			$chatbot = new FarmBridgeChatbot(null);
        $response = $chatbot->processMessage($message);
        echo json_encode([
            'success' => true,
            'response' => $response,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
		} catch (Throwable $e) {
			$fallback = (new FarmBridgeChatbot(null))->processMessage('');
        echo json_encode([
				'success' => true,
				'response' => $fallback,
				'note' => 'fallback',
				'timestamp' => date('Y-m-d H:i:s')
			]);
		}
		exit;
	}
	echo json_encode(['success' => true, 'response' => (new FarmBridgeChatbot(null))->processMessage('')]);
	exit;
}
?> 