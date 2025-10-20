<?php
require_once 'util.php';
require_once 'database.php';
require_once 'sms.php';

class FarmingTips {
    private $db;
    private $sms;

    public function __construct() {
        $this->db = new Database();
        $this->sms = new Sms();
    }

    // Get farming tips
    public function getFarmingTips($category = null, $limit = 10) {
        return $this->db->getFarmingTips($category, $limit);
    }

    // Add farming tip
    public function addFarmingTip($title, $content, $category) {
        try {
            // Validate inputs
            if (!$this->validateFarmingTipData($title, $content, $category)) {
                return false;
            }

            // Add farming tip to database
            $tipId = $this->db->addFarmingTip($title, $content, $category);
            
            if ($tipId) {
                // Send tip notification to farmers
                $this->notifyFarmingTip($title, $content, $category);
                
                return $tipId;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Add farming tip error: " . $e->getMessage());
            return false;
        }
    }

    // Validate farming tip data
    private function validateFarmingTipData($title, $content, $category) {
        // Validate title
        if (empty($title) || strlen($title) < 5) {
            return false;
        }

        // Validate content
        if (empty($content) || strlen($content) < 20) {
            return false;
        }

        // Validate category
        if (empty($category)) {
            return false;
        }

        return true;
    }

    // Get farming tip by ID
    public function getFarmingTipById($tipId) {
        try {
            $stmt = $this->db->getConnection()->prepare("SELECT * FROM farming_tips WHERE id = ?");
            $stmt->execute([$tipId]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Get farming tip by ID failed: " . $e->getMessage());
            return false;
        }
    }

    // Get farming tips by category
    public function getFarmingTipsByCategory($category, $limit = 10) {
        try {
            $stmt = $this->db->getConnection()->prepare("
                SELECT * FROM farming_tips 
                WHERE category = ? 
                ORDER BY created_at DESC 
                LIMIT ?
            ");
            $stmt->execute([$category, $limit]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get farming tips by category failed: " . $e->getMessage());
            return false;
        }
    }

    // Get seasonal farming tips
    public function getSeasonalFarmingTips($season = null) {
        try {
            if (!$season) {
                $season = $this->getCurrentSeason();
            }

            $stmt = $this->db->getConnection()->prepare("
                SELECT * FROM farming_tips 
                WHERE category = 'SEASONAL' AND content LIKE ? 
                ORDER BY created_at DESC 
                LIMIT 10
            ");
            $seasonPattern = "%$season%";
            $stmt->execute([$seasonPattern]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get seasonal farming tips failed: " . $e->getMessage());
            return false;
        }
    }

    // Get current season
    private function getCurrentSeason() {
        $month = date('n');
        
        if (in_array($month, [12, 1, 2])) {
            return 'dry';
        } elseif (in_array($month, [3, 4, 5])) {
            return 'long_rainy';
        } elseif (in_array($month, [6, 7, 8])) {
            return 'short_dry';
        } else {
            return 'short_rainy';
        }
    }

    // Get daily farming tip
    public function getDailyFarmingTip() {
        try {
            $stmt = $this->db->getConnection()->prepare("
                SELECT * FROM farming_tips 
                WHERE DATE(created_at) = CURDATE() 
                ORDER BY RAND() 
                LIMIT 1
            ");
            $stmt->execute();
            $tip = $stmt->fetch();
            
            if (!$tip) {
                // If no tip for today, get a random tip
                $stmt = $this->db->getConnection()->prepare("
                    SELECT * FROM farming_tips 
                    ORDER BY RAND() 
                    LIMIT 1
                ");
                $stmt->execute();
                $tip = $stmt->fetch();
            }
            
            return $tip;
        } catch (PDOException $e) {
            error_log("Get daily farming tip failed: " . $e->getMessage());
            return false;
        }
    }

    // Get farming tips by crop
    public function getFarmingTipsByCrop($cropName, $limit = 10) {
        try {
            $stmt = $this->db->getConnection()->prepare("
                SELECT * FROM farming_tips 
                WHERE content LIKE ? OR title LIKE ? 
                ORDER BY created_at DESC 
                LIMIT ?
            ");
            $cropPattern = "%$cropName%";
            $stmt->execute([$cropPattern, $cropPattern, $limit]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get farming tips by crop failed: " . $e->getMessage());
            return false;
        }
    }

    // Get farming tips by location
    public function getFarmingTipsByLocation($province, $limit = 10) {
        try {
            $stmt = $this->db->getConnection()->prepare("
                SELECT * FROM farming_tips 
                WHERE content LIKE ? OR title LIKE ? 
                ORDER BY created_at DESC 
                LIMIT ?
            ");
            $locationPattern = "%$province%";
            $stmt->execute([$locationPattern, $locationPattern, $limit]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get farming tips by location failed: " . $e->getMessage());
            return false;
        }
    }

    // Get farming tips statistics
    public function getFarmingTipsStats() {
        try {
            $stmt = $this->db->getConnection()->prepare("
                SELECT 
                    COUNT(*) as total_tips,
                    COUNT(DISTINCT category) as categories_count,
                    AVG(LENGTH(content)) as average_content_length,
                    MAX(created_at) as last_tip_date
                FROM farming_tips
            ");
            $stmt->execute();
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Get farming tips stats failed: " . $e->getMessage());
            return false;
        }
    }

    // Get farming tips by category statistics
    public function getFarmingTipsByCategoryStats() {
        try {
            $stmt = $this->db->getConnection()->prepare("
                SELECT 
                    category,
                    COUNT(*) as tips_count,
                    MAX(created_at) as last_tip_date
                FROM farming_tips 
                GROUP BY category 
                ORDER BY tips_count DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get farming tips by category stats failed: " . $e->getMessage());
            return false;
        }
    }

    // Search farming tips
    public function searchFarmingTips($query, $limit = 20) {
        try {
            $stmt = $this->db->getConnection()->prepare("
                SELECT * FROM farming_tips 
                WHERE title LIKE ? OR content LIKE ? OR category LIKE ? 
                ORDER BY created_at DESC 
                LIMIT ?
            ");
            $searchTerm = "%$query%";
            $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $limit]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Search farming tips failed: " . $e->getMessage());
            return false;
        }
    }

    // Get farming tips by date range
    public function getFarmingTipsByDateRange($startDate, $endDate) {
        try {
            $stmt = $this->db->getConnection()->prepare("
                SELECT * FROM farming_tips 
                WHERE created_at BETWEEN ? AND ? 
                ORDER BY created_at DESC
            ");
            $stmt->execute([$startDate, $endDate]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get farming tips by date range failed: " . $e->getMessage());
            return false;
        }
    }

    // Get farming tips categories
    public function getFarmingTipsCategories() {
        try {
            $stmt = $this->db->getConnection()->prepare("
                SELECT DISTINCT category 
                FROM farming_tips 
                WHERE category IS NOT NULL AND category != '' 
                ORDER BY category
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            error_log("Get farming tips categories failed: " . $e->getMessage());
            return [];
        }
    }

    // Get farming tips summary
    public function getFarmingTipsSummary() {
        try {
            $stmt = $this->db->getConnection()->prepare("
                SELECT 
                    COUNT(*) as total_tips,
                    COUNT(DISTINCT category) as categories_count,
                    COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as tips_this_week,
                    COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as tips_this_month
                FROM farming_tips
            ");
            $stmt->execute();
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Get farming tips summary failed: " . $e->getMessage());
            return false;
        }
    }

    // Get farming tips dashboard
    public function getFarmingTipsDashboard() {
        try {
            $dashboard = [
                'summary' => $this->getFarmingTipsSummary(),
                'categories' => $this->getFarmingTipsByCategoryStats(),
                'recent_tips' => $this->getFarmingTips(null, 5),
                'daily_tip' => $this->getDailyFarmingTip(),
                'seasonal_tips' => $this->getSeasonalFarmingTips()
            ];
            
            return $dashboard;
        } catch (Exception $e) {
            error_log("Get farming tips dashboard failed: " . $e->getMessage());
            return false;
        }
    }

    // Notify farming tip
    private function notifyFarmingTip($title, $content, $category) {
        try {
            // Get all farmers
            $stmt = $this->db->getConnection()->prepare("
                SELECT phone, name FROM users WHERE role = 'farmer'
            ");
            $stmt->execute();
            $farmers = $stmt->fetchAll();

            // Send SMS notifications
            foreach ($farmers as $farmer) {
                $this->sms->sendFarmingTipSMS(
                    $farmer['phone'],
                    $title,
                    $content
                );
            }
        } catch (Exception $e) {
            error_log("Notify farming tip failed: " . $e->getMessage());
        }
    }

    // Get farming tips by user preferences
    public function getFarmingTipsByUserPreferences($userId) {
        try {
            // Get user preferences
            $stmt = $this->db->getConnection()->prepare("
                SELECT preferences FROM users WHERE id = ?
            ");
            $stmt->execute([$userId]);
            $result = $stmt->fetch();
            
            if (!$result || !$result['preferences']) {
                return $this->getFarmingTips(null, 10);
            }
            
            $preferences = json_decode($result['preferences'], true);
            $preferredCategories = $preferences['farming_tips_categories'] ?? [];
            
            if (empty($preferredCategories)) {
                return $this->getFarmingTips(null, 10);
            }
            
            // Get tips based on preferences
            $placeholders = str_repeat('?,', count($preferredCategories) - 1) . '?';
            $stmt = $this->db->getConnection()->prepare("
                SELECT * FROM farming_tips 
                WHERE category IN ($placeholders) 
                ORDER BY created_at DESC 
                LIMIT 10
            ");
            $stmt->execute($preferredCategories);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get farming tips by user preferences failed: " . $e->getMessage());
            return $this->getFarmingTips(null, 10);
        }
    }

    // Get farming tips by weather conditions
    public function getFarmingTipsByWeatherConditions($weatherCondition) {
        try {
            $stmt = $this->db->getConnection()->prepare("
                SELECT * FROM farming_tips 
                WHERE content LIKE ? OR title LIKE ? 
                ORDER BY created_at DESC 
                LIMIT 10
            ");
            $weatherPattern = "%$weatherCondition%";
            $stmt->execute([$weatherPattern, $weatherPattern]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get farming tips by weather conditions failed: " . $e->getMessage());
            return false;
        }
    }

    // Get farming tips by soil type
    public function getFarmingTipsBySoilType($soilType) {
        try {
            $stmt = $this->db->getConnection()->prepare("
                SELECT * FROM farming_tips 
                WHERE content LIKE ? OR title LIKE ? 
                ORDER BY created_at DESC 
                LIMIT 10
            ");
            $soilPattern = "%$soilType%";
            $stmt->execute([$soilPattern, $soilPattern]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get farming tips by soil type failed: " . $e->getMessage());
            return false;
        }
    }

    // Get farming tips by farming method
    public function getFarmingTipsByFarmingMethod($farmingMethod) {
        try {
            $stmt = $this->db->getConnection()->prepare("
                SELECT * FROM farming_tips 
                WHERE content LIKE ? OR title LIKE ? 
                ORDER BY created_at DESC 
                LIMIT 10
            ");
            $methodPattern = "%$farmingMethod%";
            $stmt->execute([$methodPattern, $methodPattern]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get farming tips by farming method failed: " . $e->getMessage());
            return false;
        }
    }

    // Get farming tips by pest/disease
    public function getFarmingTipsByPestDisease($pestDisease) {
        try {
            $stmt = $this->db->getConnection()->prepare("
                SELECT * FROM farming_tips 
                WHERE content LIKE ? OR title LIKE ? 
                ORDER BY created_at DESC 
                LIMIT 10
            ");
            $pestPattern = "%$pestDisease%";
            $stmt->execute([$pestPattern, $pestPattern]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get farming tips by pest/disease failed: " . $e->getMessage());
            return false;
        }
    }

    // Get farming tips by irrigation method
    public function getFarmingTipsByIrrigationMethod($irrigationMethod) {
        try {
            $stmt = $this->db->getConnection()->prepare("
                SELECT * FROM farming_tips 
                WHERE content LIKE ? OR title LIKE ? 
                ORDER BY created_at DESC 
                LIMIT 10
            ");
            $irrigationPattern = "%$irrigationMethod%";
            $stmt->execute([$irrigationPattern, $irrigationPattern]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get farming tips by irrigation method failed: " . $e->getMessage());
            return false;
        }
    }

    // Get farming tips by fertilizer type
    public function getFarmingTipsByFertilizerType($fertilizerType) {
        try {
            $stmt = $this->db->getConnection()->prepare("
                SELECT * FROM farming_tips 
                WHERE content LIKE ? OR title LIKE ? 
                ORDER BY created_at DESC 
                LIMIT 10
            ");
            $fertilizerPattern = "%$fertilizerType%";
            $stmt->execute([$fertilizerPattern, $fertilizerPattern]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get farming tips by fertilizer type failed: " . $e->getMessage());
            return false;
        }
    }

    // Get farming tips by crop rotation
    public function getFarmingTipsByCropRotation($cropRotation) {
        try {
            $stmt = $this->db->getConnection()->prepare("
                SELECT * FROM farming_tips 
                WHERE content LIKE ? OR title LIKE ? 
                ORDER BY created_at DESC 
                LIMIT 10
            ");
            $rotationPattern = "%$cropRotation%";
            $stmt->execute([$rotationPattern, $rotationPattern]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get farming tips by crop rotation failed: " . $e->getMessage());
            return false;
        }
    }

    // Get farming tips by harvesting method
    public function getFarmingTipsByHarvestingMethod($harvestingMethod) {
        try {
            $stmt = $this->db->getConnection()->prepare("
                SELECT * FROM farming_tips 
                WHERE content LIKE ? OR title LIKE ? 
                ORDER BY created_at DESC 
                LIMIT 10
            ");
            $harvestingPattern = "%$harvestingMethod%";
            $stmt->execute([$harvestingPattern, $harvestingPattern]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get farming tips by harvesting method failed: " . $e->getMessage());
            return false;
        }
    }

    // Get farming tips by storage method
    public function getFarmingTipsByStorageMethod($storageMethod) {
        try {
            $stmt = $this->db->getConnection()->prepare("
                SELECT * FROM farming_tips 
                WHERE content LIKE ? OR title LIKE ? 
                ORDER BY created_at DESC 
                LIMIT 10
            ");
            $storagePattern = "%$storageMethod%";
            $stmt->execute([$storagePattern, $storagePattern]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get farming tips by storage method failed: " . $e->getMessage());
            return false;
        }
    }

    // Get farming tips by marketing strategy
    public function getFarmingTipsByMarketingStrategy($marketingStrategy) {
        try {
            $stmt = $this->db->getConnection()->prepare("
                SELECT * FROM farming_tips 
                WHERE content LIKE ? OR title LIKE ? 
                ORDER BY created_at DESC 
                LIMIT 10
            ");
            $marketingPattern = "%$marketingStrategy%";
            $stmt->execute([$marketingPattern, $marketingPattern]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get farming tips by marketing strategy failed: " . $e->getMessage());
            return false;
        }
    }
}
?>
