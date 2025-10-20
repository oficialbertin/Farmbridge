<?php
require_once 'util.php';
require_once 'database.php';
require_once 'sms.php';

class MarketPrices {
    private $db;
    private $sms;

    public function __construct() {
        $this->db = new Database();
        $this->sms = new Sms();
    }

    // Get market prices
    public function getMarketPrices($crop = null, $limit = 50) {
        return $this->db->getMarketPrices($crop, $limit);
    }

    // Add market price
    public function addMarketPrice($cropName, $price, $location, $unit = 'kg') {
        try {
            // Validate inputs
            if (!$this->validateMarketPriceData($cropName, $price, $location, $unit)) {
                return false;
            }

            // Add market price to database
            $priceId = $this->db->addMarketPrice($cropName, $price, $location, $unit);
            
            if ($priceId) {
                // Send price update SMS to subscribed users
                $this->notifyPriceUpdate($cropName, $price, $location);
                
                return $priceId;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Add market price error: " . $e->getMessage());
            return false;
        }
    }

    // Validate market price data
    private function validateMarketPriceData($cropName, $price, $location, $unit) {
        // Validate crop name
        if (empty($cropName) || strlen($cropName) < 2) {
            return false;
        }

        // Validate price
        if (!is_numeric($price) || $price < 0) {
            return false;
        }

        // Validate location
        if (empty($location) || strlen($location) < 2) {
            return false;
        }

        // Validate unit
        if (empty($unit)) {
            return false;
        }

        return true;
    }

    // Get current market price for a specific crop
    public function getCurrentMarketPrice($cropName, $location = null) {
        try {
            if ($location) {
                $stmt = $this->db->getConnection()->prepare("
                    SELECT * FROM market_prices 
                    WHERE crop_name = ? AND location = ? 
                    ORDER BY updated_at DESC 
                    LIMIT 1
                ");
                $stmt->execute([$cropName, $location]);
            } else {
                $stmt = $this->db->getConnection()->prepare("
                    SELECT * FROM market_prices 
                    WHERE crop_name = ? 
                    ORDER BY updated_at DESC 
                    LIMIT 1
                ");
                $stmt->execute([$cropName]);
            }
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Get current market price failed: " . $e->getMessage());
            return false;
        }
    }

    // Get market price trends
    public function getMarketPriceTrends($cropName, $days = 30) {
        try {
            $stmt = $this->db->getConnection()->prepare("
                SELECT 
                    DATE(created_at) as date,
                    AVG(price) as average_price,
                    MIN(price) as min_price,
                    MAX(price) as max_price,
                    COUNT(*) as price_updates
                FROM market_prices 
                WHERE crop_name = ? AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY DATE(created_at) 
                ORDER BY date DESC
            ");
            $stmt->execute([$cropName, $days]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get market price trends failed: " . $e->getMessage());
            return false;
        }
    }

    // Get market price by location
    public function getMarketPricesByLocation($location, $limit = 20) {
        try {
            $stmt = $this->db->getConnection()->prepare("
                SELECT * FROM market_prices 
                WHERE location = ? 
                ORDER BY updated_at DESC 
                LIMIT ?
            ");
            $stmt->execute([$location, $limit]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get market prices by location failed: " . $e->getMessage());
            return false;
        }
    }

    // Get market price statistics
    public function getMarketPriceStats($cropName = null) {
        try {
            if ($cropName) {
                $stmt = $this->db->getConnection()->prepare("
                    SELECT 
                        COUNT(*) as total_updates,
                        AVG(price) as average_price,
                        MIN(price) as min_price,
                        MAX(price) as max_price,
                        COUNT(DISTINCT location) as locations_count
                    FROM market_prices 
                    WHERE crop_name = ?
                ");
                $stmt->execute([$cropName]);
            } else {
                $stmt = $this->db->getConnection()->prepare("
                    SELECT 
                        COUNT(*) as total_updates,
                        AVG(price) as average_price,
                        MIN(price) as min_price,
                        MAX(price) as max_price,
                        COUNT(DISTINCT crop_name) as crops_count,
                        COUNT(DISTINCT location) as locations_count
                    FROM market_prices
                ");
                $stmt->execute();
            }
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Get market price stats failed: " . $e->getMessage());
            return false;
        }
    }

    // Get price comparison between locations
    public function getPriceComparison($cropName, $locations) {
        try {
            $placeholders = str_repeat('?,', count($locations) - 1) . '?';
            $stmt = $this->db->getConnection()->prepare("
                SELECT 
                    location,
                    AVG(price) as average_price,
                    MIN(price) as min_price,
                    MAX(price) as max_price,
                    COUNT(*) as updates_count
                FROM market_prices 
                WHERE crop_name = ? AND location IN ($placeholders)
                GROUP BY location 
                ORDER BY average_price DESC
            ");
            $params = array_merge([$cropName], $locations);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get price comparison failed: " . $e->getMessage());
            return false;
        }
    }

    // Get seasonal price analysis
    public function getSeasonalPriceAnalysis($cropName, $year = null) {
        try {
            if (!$year) {
                $year = date('Y');
            }

            $stmt = $this->db->getConnection()->prepare("
                SELECT 
                    MONTH(created_at) as month,
                    AVG(price) as average_price,
                    MIN(price) as min_price,
                    MAX(price) as max_price,
                    COUNT(*) as updates_count
                FROM market_prices 
                WHERE crop_name = ? AND YEAR(created_at) = ?
                GROUP BY MONTH(created_at) 
                ORDER BY month
            ");
            $stmt->execute([$cropName, $year]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get seasonal price analysis failed: " . $e->getMessage());
            return false;
        }
    }

    // Get price alerts
    public function getPriceAlerts($cropName, $threshold, $direction = 'above') {
        try {
            if ($direction === 'above') {
                $stmt = $this->db->getConnection()->prepare("
                    SELECT * FROM market_prices 
                    WHERE crop_name = ? AND price > ? 
                    ORDER BY created_at DESC 
                    LIMIT 10
                ");
            } else {
                $stmt = $this->db->getConnection()->prepare("
                    SELECT * FROM market_prices 
                    WHERE crop_name = ? AND price < ? 
                    ORDER BY created_at DESC 
                    LIMIT 10
                ");
            }
            $stmt->execute([$cropName, $threshold]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get price alerts failed: " . $e->getMessage());
            return false;
        }
    }

    // Get market price summary
    public function getMarketPriceSummary() {
        try {
            $stmt = $this->db->getConnection()->prepare("
                SELECT 
                    crop_name,
                    AVG(price) as average_price,
                    MIN(price) as min_price,
                    MAX(price) as max_price,
                    COUNT(*) as updates_count,
                    MAX(updated_at) as last_update
                FROM market_prices 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                GROUP BY crop_name 
                ORDER BY average_price DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get market price summary failed: " . $e->getMessage());
            return false;
        }
    }

    // Get price volatility
    public function getPriceVolatility($cropName, $days = 30) {
        try {
            $stmt = $this->db->getConnection()->prepare("
                SELECT 
                    AVG(price) as average_price,
                    STDDEV(price) as price_volatility,
                    MIN(price) as min_price,
                    MAX(price) as max_price,
                    COUNT(*) as updates_count
                FROM market_prices 
                WHERE crop_name = ? AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            ");
            $stmt->execute([$cropName, $days]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Get price volatility failed: " . $e->getMessage());
            return false;
        }
    }

    // Get market price recommendations
    public function getMarketPriceRecommendations($cropName, $userLocation = null) {
        try {
            $recommendations = [];
            
            // Get current price
            $currentPrice = $this->getCurrentMarketPrice($cropName, $userLocation);
            if (!$currentPrice) {
                return $recommendations;
            }

            // Get price trends
            $trends = $this->getMarketPriceTrends($cropName, 7);
            if ($trends && count($trends) >= 2) {
                $recentTrend = $trends[0];
                $previousTrend = $trends[1];
                
                if ($recentTrend['average_price'] > $previousTrend['average_price']) {
                    $recommendations[] = "Price is rising. Consider selling soon.";
                } elseif ($recentTrend['average_price'] < $previousTrend['average_price']) {
                    $recommendations[] = "Price is falling. Consider waiting for better prices.";
                } else {
                    $recommendations[] = "Price is stable. Good time for trading.";
                }
            }

            // Get price comparison
            if ($userLocation) {
                $comparison = $this->getPriceComparison($cropName, ['Kigali', 'Northern Province', 'Southern Province', 'Eastern Province', 'Western Province']);
                if ($comparison) {
                    $userLocationPrice = null;
                    foreach ($comparison as $comp) {
                        if ($comp['location'] === $userLocation) {
                            $userLocationPrice = $comp['average_price'];
                            break;
                        }
                    }
                    
                    if ($userLocationPrice) {
                        $highestPrice = max(array_column($comparison, 'average_price'));
                        $lowestPrice = min(array_column($comparison, 'average_price'));
                        
                        if ($userLocationPrice >= $highestPrice * 0.95) {
                            $recommendations[] = "Your location has high prices. Good for selling.";
                        } elseif ($userLocationPrice <= $lowestPrice * 1.05) {
                            $recommendations[] = "Your location has low prices. Good for buying.";
                        }
                    }
                }
            }

            return $recommendations;
        } catch (Exception $e) {
            error_log("Get market price recommendations failed: " . $e->getMessage());
            return [];
        }
    }

    // Notify price update
    private function notifyPriceUpdate($cropName, $price, $location) {
        try {
            // Get users who might be interested in this crop
            $stmt = $this->db->getConnection()->prepare("
                SELECT DISTINCT u.phone, u.name 
                FROM users u 
                JOIN crops c ON u.id = c.farmer_id 
                WHERE c.name LIKE ? OR c.category LIKE ?
            ");
            $cropPattern = "%$cropName%";
            $stmt->execute([$cropPattern, $cropPattern]);
            $users = $stmt->fetchAll();

            // Send SMS notifications
            foreach ($users as $user) {
                $this->sms->sendMarketPriceSMS(
                    $user['phone'],
                    $cropName,
                    $price,
                    $location
                );
            }
        } catch (Exception $e) {
            error_log("Notify price update failed: " . $e->getMessage());
        }
    }

    // Get market price history
    public function getMarketPriceHistory($cropName, $location = null, $limit = 50) {
        try {
            if ($location) {
                $stmt = $this->db->getConnection()->prepare("
                    SELECT * FROM market_prices 
                    WHERE crop_name = ? AND location = ? 
                    ORDER BY created_at DESC 
                    LIMIT ?
                ");
                $stmt->execute([$cropName, $location, $limit]);
            } else {
                $stmt = $this->db->getConnection()->prepare("
                    SELECT * FROM market_prices 
                    WHERE crop_name = ? 
                    ORDER BY created_at DESC 
                    LIMIT ?
                ");
                $stmt->execute([$cropName, $limit]);
            }
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get market price history failed: " . $e->getMessage());
            return false;
        }
    }

    // Get market price forecast (simple trend-based)
    public function getMarketPriceForecast($cropName, $days = 7) {
        try {
            // Get recent price trends
            $trends = $this->getMarketPriceTrends($cropName, 30);
            if (!$trends || count($trends) < 3) {
                return false;
            }

            // Calculate simple moving average
            $recentPrices = array_slice($trends, 0, 7);
            $averagePrice = array_sum(array_column($recentPrices, 'average_price')) / count($recentPrices);
            
            // Calculate trend direction
            $firstPrice = $recentPrices[count($recentPrices) - 1]['average_price'];
            $lastPrice = $recentPrices[0]['average_price'];
            $trendDirection = ($lastPrice - $firstPrice) / count($recentPrices);
            
            // Generate forecast
            $forecast = [];
            for ($i = 1; $i <= $days; $i++) {
                $forecastPrice = $lastPrice + ($trendDirection * $i);
                $forecast[] = [
                    'day' => $i,
                    'predicted_price' => max(0, $forecastPrice),
                    'confidence' => max(0.1, 1 - ($i * 0.1)) // Decreasing confidence
                ];
            }
            
            return $forecast;
        } catch (Exception $e) {
            error_log("Get market price forecast failed: " . $e->getMessage());
            return false;
        }
    }

    // Get market price alerts for users
    public function getMarketPriceAlerts($userId) {
        try {
            $stmt = $this->db->getConnection()->prepare("
                SELECT 
                    pa.*,
                    mp.crop_name,
                    mp.price as current_price,
                    mp.location
                FROM price_alerts pa
                JOIN market_prices mp ON pa.crop_name = mp.crop_name
                WHERE pa.user_id = ? AND pa.is_active = 1
                ORDER BY pa.created_at DESC
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get market price alerts failed: " . $e->getMessage());
            return false;
        }
    }

    // Create price alert
    public function createPriceAlert($userId, $cropName, $targetPrice, $condition = 'above') {
        try {
            $stmt = $this->db->getConnection()->prepare("
                INSERT INTO price_alerts (user_id, crop_name, target_price, condition, is_active, created_at) 
                VALUES (?, ?, ?, ?, 1, NOW())
            ");
            return $stmt->execute([$userId, $cropName, $targetPrice, $condition]);
        } catch (PDOException $e) {
            error_log("Create price alert failed: " . $e->getMessage());
            return false;
        }
    }

    // Get market price dashboard data
    public function getMarketPriceDashboard() {
        try {
            $dashboard = [
                'summary' => $this->getMarketPriceSummary(),
                'trending_crops' => $this->getTrendingCrops(),
                'price_changes' => $this->getPriceChanges(),
                'locations' => $this->getActiveLocations()
            ];
            
            return $dashboard;
        } catch (Exception $e) {
            error_log("Get market price dashboard failed: " . $e->getMessage());
            return false;
        }
    }

    // Get trending crops
    private function getTrendingCrops($limit = 10) {
        try {
            $stmt = $this->db->getConnection()->prepare("
                SELECT 
                    crop_name,
                    COUNT(*) as updates_count,
                    AVG(price) as average_price,
                    MAX(updated_at) as last_update
                FROM market_prices 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                GROUP BY crop_name 
                ORDER BY updates_count DESC 
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get trending crops failed: " . $e->getMessage());
            return [];
        }
    }

    // Get price changes
    private function getPriceChanges($limit = 10) {
        try {
            $stmt = $this->db->getConnection()->prepare("
                SELECT 
                    crop_name,
                    location,
                    price,
                    created_at,
                    LAG(price) OVER (PARTITION BY crop_name, location ORDER BY created_at) as previous_price
                FROM market_prices 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)
                ORDER BY created_at DESC 
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get price changes failed: " . $e->getMessage());
            return [];
        }
    }

    // Get active locations
    private function getActiveLocations($limit = 10) {
        try {
            $stmt = $this->db->getConnection()->prepare("
                SELECT 
                    location,
                    COUNT(*) as updates_count,
                    MAX(updated_at) as last_update
                FROM market_prices 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                GROUP BY location 
                ORDER BY updates_count DESC 
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get active locations failed: " . $e->getMessage());
            return [];
        }
    }
}
?>
