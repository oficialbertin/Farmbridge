<?php
require_once 'util.php';
require_once 'database.php';
require_once 'sms.php';

class UserManagement {
    private $db;
    private $sms;

    public function __construct() {
        $this->db = new Database();
        $this->sms = new Sms();
    }

    // Complete user registration
    public function completeRegistration($phone, $name, $email, $role, $province, $district, $sector = '') {
        try {
            // Validate inputs
            if (!$this->validateRegistrationData($phone, $name, $email, $role, $province, $district)) {
                return false;
            }

            // Check if user already exists
            if ($this->db->checkUserExists($phone)) {
                return false; // User already exists
            }

            // Register user
            $userId = $this->db->registerUser($phone, $name, $email, $role, $province, $district, $sector);
            
            if ($userId) {
                // Send welcome SMS
                $this->sms->sendRegistrationSMS($phone, $name, $role);
                
                return $userId;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("User registration error: " . $e->getMessage());
            return false;
        }
    }

    // Validate registration data
    private function validateRegistrationData($phone, $name, $email, $role, $province, $district) {
        // Validate phone number
        if (!Util::isValidPhoneNumber($phone)) {
            return false;
        }

        // Validate name
        if (empty($name) || strlen($name) < 2) {
            return false;
        }

        // Validate email
        if (!Util::isValidEmail($email)) {
            return false;
        }

        // Validate role
        if (!in_array($role, array_values(Util::$USER_ROLES))) {
            return false;
        }

        // Validate province
        if (!array_key_exists($province, Util::$PROVINCES)) {
            return false;
        }

        // Validate district
        if (!in_array($district, Util::$DISTRICTS[$province])) {
            return false;
        }

        return true;
    }

    // Get user by phone
    public function getUserByPhone($phone) {
        return $this->db->getUserByPhone($phone);
    }

    // Update user profile
    public function updateUserProfile($userId, $name, $email, $province, $district, $sector) {
        try {
            // Validate inputs
            if (empty($name) || strlen($name) < 2) {
                return false;
            }

            if (!Util::isValidEmail($email)) {
                return false;
            }

            if (!array_key_exists($province, Util::$PROVINCES)) {
                return false;
            }

            if (!in_array($district, Util::$DISTRICTS[$province])) {
                return false;
            }

            // Update profile
            $result = $this->db->updateUserProfile($userId, $name, $email, $province, $district, $sector);
            
            if ($result) {
                // Send confirmation SMS
                $user = $this->db->getUserByPhone($userId);
                if ($user) {
                    $this->sms->sendAccountUpdateSMS($user['phone'], $user['name'], 'profile');
                }
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Update user profile error: " . $e->getMessage());
            return false;
        }
    }

    // Get user statistics
    public function getUserStats($userId) {
        return $this->db->getUserStats($userId);
    }

    // Check if user exists
    public function userExists($phone) {
        return $this->db->checkUserExists($phone);
    }

    // Get user by ID
    public function getUserById($userId) {
        try {
            $stmt = $this->db->getConnection()->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Get user by ID failed: " . $e->getMessage());
            return false;
        }
    }

    // Get all users (for admin purposes)
    public function getAllUsers($limit = 50, $offset = 0) {
        try {
            $stmt = $this->db->getConnection()->prepare("
                SELECT * FROM users 
                ORDER BY created_at DESC 
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$limit, $offset]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get all users failed: " . $e->getMessage());
            return false;
        }
    }

    // Get users by role
    public function getUsersByRole($role, $limit = 50, $offset = 0) {
        try {
            $stmt = $this->db->getConnection()->prepare("
                SELECT * FROM users 
                WHERE role = ? 
                ORDER BY created_at DESC 
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$role, $limit, $offset]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get users by role failed: " . $e->getMessage());
            return false;
        }
    }

    // Get users by location
    public function getUsersByLocation($province, $district = null, $limit = 50, $offset = 0) {
        try {
            if ($district) {
                $stmt = $this->db->getConnection()->prepare("
                    SELECT * FROM users 
                    WHERE province = ? AND district = ? 
                    ORDER BY created_at DESC 
                    LIMIT ? OFFSET ?
                ");
                $stmt->execute([$province, $district, $limit, $offset]);
            } else {
                $stmt = $this->db->getConnection()->prepare("
                    SELECT * FROM users 
                    WHERE province = ? 
                    ORDER BY created_at DESC 
                    LIMIT ? OFFSET ?
                ");
                $stmt->execute([$province, $limit, $offset]);
            }
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get users by location failed: " . $e->getMessage());
            return false;
        }
    }

    // Search users
    public function searchUsers($query, $limit = 50) {
        try {
            $stmt = $this->db->getConnection()->prepare("
                SELECT * FROM users 
                WHERE name LIKE ? OR phone LIKE ? OR email LIKE ? 
                ORDER BY created_at DESC 
                LIMIT ?
            ");
            $searchTerm = "%$query%";
            $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $limit]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Search users failed: " . $e->getMessage());
            return false;
        }
    }

    // Get user activity summary
    public function getUserActivitySummary($userId) {
        try {
            $user = $this->getUserById($userId);
            if (!$user) {
                return false;
            }

            $summary = [
                'user' => $user,
                'total_orders' => 0,
                'total_products' => 0,
                'total_earnings' => 0,
                'total_spent' => 0,
                'recent_activity' => []
            ];

            if ($user['role'] === 'farmer') {
                // Get farmer statistics
                $stmt = $this->db->getConnection()->prepare("
                    SELECT COUNT(*) as total_products, SUM(quantity) as total_quantity 
                    FROM crops 
                    WHERE farmer_id = ?
                ");
                $stmt->execute([$userId]);
                $productStats = $stmt->fetch();
                $summary['total_products'] = $productStats['total_products'] ?? 0;

                $stmt = $this->db->getConnection()->prepare("
                    SELECT COUNT(*) as total_orders, SUM(total_price) as total_earnings 
                    FROM orders 
                    WHERE farmer_id = ? AND status = 'delivered'
                ");
                $stmt->execute([$userId]);
                $orderStats = $stmt->fetch();
                $summary['total_orders'] = $orderStats['total_orders'] ?? 0;
                $summary['total_earnings'] = $orderStats['total_earnings'] ?? 0;
            } else {
                // Get buyer statistics
                $stmt = $this->db->getConnection()->prepare("
                    SELECT COUNT(*) as total_orders, SUM(total_price) as total_spent 
                    FROM orders 
                    WHERE buyer_id = ?
                ");
                $stmt->execute([$userId]);
                $orderStats = $stmt->fetch();
                $summary['total_orders'] = $orderStats['total_orders'] ?? 0;
                $summary['total_spent'] = $orderStats['total_spent'] ?? 0;
            }

            // Get recent activity
            $stmt = $this->db->getConnection()->prepare("
                SELECT 'order' as type, created_at, total_price as amount 
                FROM orders 
                WHERE (farmer_id = ? OR buyer_id = ?) 
                ORDER BY created_at DESC 
                LIMIT 5
            ");
            $stmt->execute([$userId, $userId]);
            $summary['recent_activity'] = $stmt->fetchAll();

            return $summary;
        } catch (PDOException $e) {
            error_log("Get user activity summary failed: " . $e->getMessage());
            return false;
        }
    }

    // Send welcome back SMS
    public function sendWelcomeBackSMS($phone) {
        $user = $this->getUserByPhone($phone);
        if ($user) {
            $this->sms->sendWelcomeBackSMS($phone, $user['name']);
            return true;
        }
        return false;
    }

    // Get user registration summary
    public function getRegistrationSummary() {
        try {
            $stmt = $this->db->getConnection()->prepare("
                SELECT 
                    COUNT(*) as total_users,
                    SUM(CASE WHEN role = 'farmer' THEN 1 ELSE 0 END) as farmers,
                    SUM(CASE WHEN role = 'buyer' THEN 1 ELSE 0 END) as buyers,
                    COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as new_users_week,
                    COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as new_users_month
                FROM users
            ");
            $stmt->execute();
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Get registration summary failed: " . $e->getMessage());
            return false;
        }
    }

    // Get users by registration date range
    public function getUsersByDateRange($startDate, $endDate) {
        try {
            $stmt = $this->db->getConnection()->prepare("
                SELECT * FROM users 
                WHERE created_at BETWEEN ? AND ? 
                ORDER BY created_at DESC
            ");
            $stmt->execute([$startDate, $endDate]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get users by date range failed: " . $e->getMessage());
            return false;
        }
    }

    // Deactivate user account
    public function deactivateUser($userId) {
        try {
            $stmt = $this->db->getConnection()->prepare("
                UPDATE users 
                SET status = 'inactive', updated_at = NOW() 
                WHERE id = ?
            ");
            return $stmt->execute([$userId]);
        } catch (PDOException $e) {
            error_log("Deactivate user failed: " . $e->getMessage());
            return false;
        }
    }

    // Reactivate user account
    public function reactivateUser($userId) {
        try {
            $stmt = $this->db->getConnection()->prepare("
                UPDATE users 
                SET status = 'active', updated_at = NOW() 
                WHERE id = ?
            ");
            return $stmt->execute([$userId]);
        } catch (PDOException $e) {
            error_log("Reactivate user failed: " . $e->getMessage());
            return false;
        }
    }

    // Get user preferences
    public function getUserPreferences($userId) {
        try {
            $stmt = $this->db->getConnection()->prepare("
                SELECT preferences FROM users 
                WHERE id = ?
            ");
            $stmt->execute([$userId]);
            $result = $stmt->fetch();
            return $result ? json_decode($result['preferences'], true) : [];
        } catch (PDOException $e) {
            error_log("Get user preferences failed: " . $e->getMessage());
            return [];
        }
    }

    // Update user preferences
    public function updateUserPreferences($userId, $preferences) {
        try {
            $stmt = $this->db->getConnection()->prepare("
                UPDATE users 
                SET preferences = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            return $stmt->execute([json_encode($preferences), $userId]);
        } catch (PDOException $e) {
            error_log("Update user preferences failed: " . $e->getMessage());
            return false;
        }
    }

    // Validate user session
    public function validateUserSession($phone, $sessionId) {
        try {
            $user = $this->getUserByPhone($phone);
            if (!$user) {
                return false;
            }

            $session = $this->db->getSession($sessionId);
            if (!$session) {
                return false;
            }

            // Check if session is expired
            $sessionAge = time() - strtotime($session['updated_at']);
            if ($sessionAge > Util::$SESSION_TIMEOUT) {
                $this->db->deleteSession($sessionId);
                return false;
            }

            return true;
        } catch (Exception $e) {
            error_log("Validate user session failed: " . $e->getMessage());
            return false;
        }
    }

    // Get user dashboard data
    public function getUserDashboard($userId) {
        try {
            $user = $this->getUserById($userId);
            if (!$user) {
                return false;
            }

            $dashboard = [
                'user' => $user,
                'stats' => $this->getUserStats($userId),
                'recent_orders' => [],
                'recent_products' => [],
                'notifications' => []
            ];

            if ($user['role'] === 'farmer') {
                // Get recent products
                $products = $this->db->getProductsByFarmer($userId);
                $dashboard['recent_products'] = array_slice($products, 0, 5);

                // Get recent orders
                $orders = $this->db->getOrdersByFarmer($userId);
                $dashboard['recent_orders'] = array_slice($orders, 0, 5);
            } else {
                // Get recent orders
                $orders = $this->db->getOrdersByBuyer($userId);
                $dashboard['recent_orders'] = array_slice($orders, 0, 5);
            }

            return $dashboard;
        } catch (Exception $e) {
            error_log("Get user dashboard failed: " . $e->getMessage());
            return false;
        }
    }
}
?>
