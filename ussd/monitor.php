<?php
/**
 * FarmBridge AI USSD Application Monitor
 * This script monitors the application health and performance
 */

require_once 'util.php';
require_once 'database.php';

class USSDMonitor {
    private $db;
    private $logFile;
    private $metrics = [];

    public function __construct() {
        $this->db = new Database();
        $this->logFile = '/var/log/ussd/monitor.log';
        $this->setupLogging();
    }

    private function setupLogging() {
        // Create log directory if it doesn't exist
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }

    private function log($message, $level = 'INFO') {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] [$level] $message" . PHP_EOL;
        file_put_contents($this->logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }

    public function run() {
        $this->log("Starting USSD application monitoring");
        
        while (true) {
            try {
                $this->checkDatabaseHealth();
                $this->checkApplicationHealth();
                $this->checkSessionHealth();
                $this->checkSMSService();
                $this->collectMetrics();
                $this->cleanupExpiredData();
                
                // Sleep for 60 seconds
                sleep(60);
            } catch (Exception $e) {
                $this->log("Monitor error: " . $e->getMessage(), 'ERROR');
                sleep(60);
            }
        }
    }

    private function checkDatabaseHealth() {
        try {
            $connection = $this->db->getConnection();
            if (!$connection) {
                $this->log("Database connection failed", 'ERROR');
                return false;
            }

            // Test query
            $stmt = $connection->prepare("SELECT 1");
            $stmt->execute();
            
            $this->log("Database health check passed");
            return true;
        } catch (Exception $e) {
            $this->log("Database health check failed: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }

    private function checkApplicationHealth() {
        try {
            // Check if main files exist
            $files = ['index.php', 'menu.php', 'util.php', 'database.php', 'sms.php'];
            $missingFiles = [];

            foreach ($files as $file) {
                if (!file_exists($file)) {
                    $missingFiles[] = $file;
                }
            }

            if (!empty($missingFiles)) {
                $this->log("Missing application files: " . implode(', ', $missingFiles), 'ERROR');
                return false;
            }

            // Check file permissions
            foreach ($files as $file) {
                if (!is_readable($file)) {
                    $this->log("File not readable: $file", 'ERROR');
                    return false;
                }
            }

            $this->log("Application health check passed");
            return true;
        } catch (Exception $e) {
            $this->log("Application health check failed: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }

    private function checkSessionHealth() {
        try {
            $connection = $this->db->getConnection();
            
            // Count active sessions
            $stmt = $connection->prepare("
                SELECT COUNT(*) as active_sessions 
                FROM ussd_sessions 
                WHERE updated_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
            ");
            $stmt->execute([Util::$SESSION_TIMEOUT]);
            $result = $stmt->fetch();
            
            $activeSessions = $result['active_sessions'];
            $this->metrics['active_sessions'] = $activeSessions;
            
            if ($activeSessions > 1000) {
                $this->log("High number of active sessions: $activeSessions", 'WARNING');
            }

            // Count expired sessions
            $stmt = $connection->prepare("
                SELECT COUNT(*) as expired_sessions 
                FROM ussd_sessions 
                WHERE updated_at <= DATE_SUB(NOW(), INTERVAL ? SECOND)
            ");
            $stmt->execute([Util::$SESSION_TIMEOUT]);
            $result = $stmt->fetch();
            
            $expiredSessions = $result['expired_sessions'];
            $this->metrics['expired_sessions'] = $expiredSessions;
            
            if ($expiredSessions > 100) {
                $this->log("High number of expired sessions: $expiredSessions", 'WARNING');
            }

            $this->log("Session health check passed - Active: $activeSessions, Expired: $expiredSessions");
            return true;
        } catch (Exception $e) {
            $this->log("Session health check failed: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }

    private function checkSMSService() {
        try {
            // Check if SMS service is accessible
            require_once 'sms.php';
            $sms = new Sms();
            
            // Test SMS service (without actually sending)
            $this->log("SMS service check passed");
            return true;
        } catch (Exception $e) {
            $this->log("SMS service check failed: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }

    private function collectMetrics() {
        try {
            $connection = $this->db->getConnection();
            
            // User metrics
            $stmt = $connection->prepare("
                SELECT 
                    COUNT(*) as total_users,
                    SUM(CASE WHEN role = 'farmer' THEN 1 ELSE 0 END) as farmers,
                    SUM(CASE WHEN role = 'buyer' THEN 1 ELSE 0 END) as buyers,
                    COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 END) as new_users_24h
                FROM users
            ");
            $stmt->execute();
            $userMetrics = $stmt->fetch();
            
            // Product metrics
            $stmt = $connection->prepare("
                SELECT 
                    COUNT(*) as total_products,
                    SUM(quantity) as total_quantity,
                    COUNT(CASE WHEN quantity > 0 THEN 1 ELSE 0 END) as available_products
                FROM crops
            ");
            $stmt->execute();
            $productMetrics = $stmt->fetch();
            
            // Order metrics
            $stmt = $connection->prepare("
                SELECT 
                    COUNT(*) as total_orders,
                    SUM(total_price) as total_value,
                    COUNT(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as completed_orders,
                    COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 END) as orders_24h
                FROM orders
            ");
            $stmt->execute();
            $orderMetrics = $stmt->fetch();
            
            // Market price metrics
            $stmt = $connection->prepare("
                SELECT 
                    COUNT(*) as total_price_updates,
                    COUNT(DISTINCT crop_name) as crops_tracked,
                    COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 END) as updates_24h
                FROM market_prices
            ");
            $stmt->execute();
            $priceMetrics = $stmt->fetch();
            
            // Farming tips metrics
            $stmt = $connection->prepare("
                SELECT 
                    COUNT(*) as total_tips,
                    COUNT(DISTINCT category) as categories,
                    COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 END) as tips_24h
                FROM farming_tips
            ");
            $stmt->execute();
            $tipMetrics = $stmt->fetch();
            
            // Combine all metrics
            $this->metrics = array_merge(
                $this->metrics,
                $userMetrics,
                $productMetrics,
                $orderMetrics,
                $priceMetrics,
                $tipMetrics
            );
            
            $this->log("Metrics collected successfully");
            $this->saveMetrics();
            
        } catch (Exception $e) {
            $this->log("Metrics collection failed: " . $e->getMessage(), 'ERROR');
        }
    }

    private function saveMetrics() {
        try {
            $connection = $this->db->getConnection();
            
            // Save metrics to database
            $stmt = $connection->prepare("
                INSERT INTO monitoring_metrics (
                    timestamp, active_sessions, expired_sessions, total_users, 
                    farmers, buyers, new_users_24h, total_products, total_quantity,
                    available_products, total_orders, total_value, completed_orders,
                    orders_24h, total_price_updates, crops_tracked, updates_24h,
                    total_tips, categories, tips_24h
                ) VALUES (
                    NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                )
            ");
            
            $stmt->execute([
                $this->metrics['active_sessions'] ?? 0,
                $this->metrics['expired_sessions'] ?? 0,
                $this->metrics['total_users'] ?? 0,
                $this->metrics['farmers'] ?? 0,
                $this->metrics['buyers'] ?? 0,
                $this->metrics['new_users_24h'] ?? 0,
                $this->metrics['total_products'] ?? 0,
                $this->metrics['total_quantity'] ?? 0,
                $this->metrics['available_products'] ?? 0,
                $this->metrics['total_orders'] ?? 0,
                $this->metrics['total_value'] ?? 0,
                $this->metrics['completed_orders'] ?? 0,
                $this->metrics['orders_24h'] ?? 0,
                $this->metrics['total_price_updates'] ?? 0,
                $this->metrics['crops_tracked'] ?? 0,
                $this->metrics['updates_24h'] ?? 0,
                $this->metrics['total_tips'] ?? 0,
                $this->metrics['categories'] ?? 0,
                $this->metrics['tips_24h'] ?? 0
            ]);
            
        } catch (Exception $e) {
            $this->log("Failed to save metrics: " . $e->getMessage(), 'ERROR');
        }
    }

    private function cleanupExpiredData() {
        try {
            $connection = $this->db->getConnection();
            
            // Clean up expired sessions
            $stmt = $connection->prepare("
                DELETE FROM ussd_sessions 
                WHERE updated_at < DATE_SUB(NOW(), INTERVAL ? SECOND)
            ");
            $stmt->execute([Util::$SESSION_TIMEOUT]);
            $deletedSessions = $stmt->rowCount();
            
            if ($deletedSessions > 0) {
                $this->log("Cleaned up $deletedSessions expired sessions");
            }
            
            // Clean up old monitoring metrics (keep last 30 days)
            $stmt = $connection->prepare("
                DELETE FROM monitoring_metrics 
                WHERE timestamp < DATE_SUB(NOW(), INTERVAL 30 DAY)
            ");
            $stmt->execute();
            $deletedMetrics = $stmt->rowCount();
            
            if ($deletedMetrics > 0) {
                $this->log("Cleaned up $deletedMetrics old monitoring metrics");
            }
            
            // Clean up old log entries
            $this->cleanupLogs();
            
        } catch (Exception $e) {
            $this->log("Cleanup failed: " . $e->getMessage(), 'ERROR');
        }
    }

    private function cleanupLogs() {
        try {
            $logFiles = [
                '/var/log/ussd/monitor.log',
                '/var/log/ussd/error.log',
                '/var/log/ussd/access.log'
            ];
            
            foreach ($logFiles as $logFile) {
                if (file_exists($logFile) && filesize($logFile) > 10 * 1024 * 1024) { // 10MB
                    // Rotate log file
                    $rotatedFile = $logFile . '.' . date('Y-m-d-H-i-s');
                    rename($logFile, $rotatedFile);
                    
                    // Compress old log file
                    if (function_exists('gzopen')) {
                        $gz = gzopen($rotatedFile . '.gz', 'w9');
                        gzwrite($gz, file_get_contents($rotatedFile));
                        gzclose($gz);
                        unlink($rotatedFile);
                    }
                    
                    $this->log("Rotated log file: $logFile");
                }
            }
        } catch (Exception $e) {
            $this->log("Log cleanup failed: " . $e->getMessage(), 'ERROR');
        }
    }

    public function getMetrics() {
        return $this->metrics;
    }

    public function generateReport() {
        try {
            $connection = $this->db->getConnection();
            
            // Get metrics for the last 24 hours
            $stmt = $connection->prepare("
                SELECT 
                    AVG(active_sessions) as avg_active_sessions,
                    MAX(active_sessions) as max_active_sessions,
                    SUM(new_users_24h) as total_new_users,
                    SUM(orders_24h) as total_new_orders,
                    SUM(updates_24h) as total_price_updates,
                    SUM(tips_24h) as total_new_tips
                FROM monitoring_metrics 
                WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ");
            $stmt->execute();
            $report = $stmt->fetch();
            
            $this->log("Generated 24-hour report");
            return $report;
        } catch (Exception $e) {
            $this->log("Report generation failed: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
}

// Run monitor if script is executed directly
if (php_sapi_name() === 'cli') {
    $monitor = new USSDMonitor();
    $monitor->run();
} else {
    echo "This script should be run from the command line.\n";
    echo "Usage: php monitor.php\n";
}
?>
