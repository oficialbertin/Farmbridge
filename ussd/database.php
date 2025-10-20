<?php
require_once __DIR__ . '/util.php';

class Database {
    private $connection;
    private $host;
    private $db;
    private $user;
    private $pass;
    private $tableColumnsCache = [];

    public function __construct() {
        $this->host = Util::$host;
        $this->db = Util::$db;
        $this->user = Util::$user;
        $this->pass = Util::$pass;
        $this->connect();
    }

    private function connect() {
        try {
            $this->connection = new PDO(
                "mysql:host={$this->host};dbname={$this->db};charset=utf8mb4",
                $this->user,
                $this->pass,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Database connection failed");
        }
    }

    private function getTableColumns($tableName) {
        if (isset($this->tableColumnsCache[$tableName])) {
            return $this->tableColumnsCache[$tableName];
        }
        try {
            $stmt = $this->connection->prepare("SHOW COLUMNS FROM `{$tableName}`");
            $stmt->execute();
            $cols = $stmt->fetchAll();
            $names = [];
            foreach ($cols as $col) { $names[] = $col['Field']; }
            $this->tableColumnsCache[$tableName] = $names;
            return $names;
        } catch (PDOException $e) {
            return [];
        }
    }

    public function getConnection() {
        return $this->connection;
    }

    // User Management Methods
    public function registerUser($phone, $name, $email, $role, $province, $district, $sector) {
        try {
            $cols = $this->getTableColumns('users');
            $fields = ['phone','name','role'];
            $values = [$phone, $name, $role];
            if (in_array('email', $cols, true)) { $fields[] = 'email'; $values[] = $email ?: null; }
            if (in_array('province', $cols, true)) { $fields[] = 'province'; $values[] = $province; }
            if (in_array('district', $cols, true)) { $fields[] = 'district'; $values[] = $district; }
            if (in_array('sector', $cols, true)) { $fields[] = 'sector'; $values[] = $sector; }
            if (in_array('password', $cols, true)) { $fields[] = 'password'; $values[] = password_hash(bin2hex(random_bytes(8)), PASSWORD_BCRYPT); }
            if (in_array('created_at', $cols, true)) { $fields[] = 'created_at'; $values[] = date('Y-m-d H:i:s'); }
            if (in_array('updated_at', $cols, true)) { $fields[] = 'updated_at'; $values[] = date('Y-m-d H:i:s'); }
            $sql = 'INSERT INTO users (' . implode(',', $fields) . ') VALUES (' . rtrim(str_repeat('?,', count($values)), ',') . ')';
            $stmt = $this->connection->prepare($sql);
            $result = $stmt->execute($values);
            if ($result) { return $this->connection->lastInsertId(); }
            return false;
        } catch (PDOException $e) {
            error_log("User registration failed: " . $e->getMessage());
            return false;
        }
    }

    public function getUserByPhone($phone) {
        try {
            $stmt = $this->connection->prepare("SELECT * FROM users WHERE phone = ?");
            $stmt->execute([$phone]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Get user by phone failed: " . $e->getMessage());
            return false;
        }
    }

    public function updateUserProfile($userId, $name, $email, $province, $district, $sector) {
        try {
            $stmt = $this->connection->prepare("
                UPDATE users 
                SET name = ?, email = ?, province = ?, district = ?, sector = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            return $stmt->execute([$name, $email, $province, $district, $sector, $userId]);
        } catch (PDOException $e) {
            error_log("Update user profile failed: " . $e->getMessage());
            return false;
        }
    }

    public function checkUserExists($phone) {
        try {
            $stmt = $this->connection->prepare("SELECT COUNT(*) FROM users WHERE phone = ?");
            $stmt->execute([$phone]);
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("Check user exists failed: " . $e->getMessage());
            return false;
        }
    }

    // Product Management Methods
    public function addProduct($farmerId, $name, $description, $quantity, $price, $category, $image = null) {
        try {
            $stmt = $this->connection->prepare("
                INSERT INTO crops (farmer_id, name, description, quantity, price, category, image, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            
            $result = $stmt->execute([$farmerId, $name, $description, $quantity, $price, $category, $image]);
            
            if ($result) {
                return $this->connection->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            error_log("Add product failed: " . $e->getMessage());
            return false;
        }
    }

    public function getProductsByFarmer($farmerId) {
        try {
            $stmt = $this->connection->prepare("
                SELECT c.*, u.name as farmer_name, u.phone as farmer_phone 
                FROM crops c 
                JOIN users u ON c.farmer_id = u.id 
                WHERE c.farmer_id = ? AND c.quantity > 0 
                ORDER BY c.created_at DESC
            ");
            $stmt->execute([$farmerId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get products by farmer failed: " . $e->getMessage());
            return false;
        }
    }

    public function getAllAvailableProducts($limit = 20, $offset = 0) {
        try {
            $stmt = $this->connection->prepare("
                SELECT c.*, u.name as farmer_name, u.phone as farmer_phone, u.province, u.district 
                FROM crops c 
                JOIN users u ON c.farmer_id = u.id 
                WHERE c.quantity > 0 
                ORDER BY c.created_at DESC 
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$limit, $offset]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get all available products failed: " . $e->getMessage());
            return false;
        }
    }

    public function getProductById($productId) {
        try {
            $stmt = $this->connection->prepare("
                SELECT c.*, u.name as farmer_name, u.phone as farmer_phone, u.province, u.district 
                FROM crops c 
                JOIN users u ON c.farmer_id = u.id 
                WHERE c.id = ?
            ");
            $stmt->execute([$productId]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Get product by ID failed: " . $e->getMessage());
            return false;
        }
    }

    public function updateProductQuantity($productId, $newQuantity) {
        try {
            $stmt = $this->connection->prepare("
                UPDATE crops 
                SET quantity = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            return $stmt->execute([$newQuantity, $productId]);
        } catch (PDOException $e) {
            error_log("Update product quantity failed: " . $e->getMessage());
            return false;
        }
    }

    public function deleteProduct($productId) {
        try {
            $stmt = $this->connection->prepare("DELETE FROM crops WHERE id = ?");
            return $stmt->execute([$productId]);
        } catch (PDOException $e) {
            error_log("Delete product failed: " . $e->getMessage());
            return false;
        }
    }

    // Order Management Methods
    public function createOrder($buyerId, $farmerId, $productId, $quantity, $totalPrice, $status = 'pending') {
        try {
            $stmt = $this->connection->prepare("
                INSERT INTO orders (buyer_id, farmer_id, product_id, quantity, total_price, status, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            
            $result = $stmt->execute([$buyerId, $farmerId, $productId, $quantity, $totalPrice, $status]);
            
            if ($result) {
                return $this->connection->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            error_log("Create order failed: " . $e->getMessage());
            return false;
        }
    }

    public function getOrdersByBuyer($buyerId) {
        try {
            $stmt = $this->connection->prepare("
                SELECT o.*, c.name as product_name, u.name as farmer_name, u.phone as farmer_phone 
                FROM orders o 
                JOIN crops c ON o.product_id = c.id 
                JOIN users u ON o.farmer_id = u.id 
                WHERE o.buyer_id = ? 
                ORDER BY o.created_at DESC
            ");
            $stmt->execute([$buyerId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get orders by buyer failed: " . $e->getMessage());
            return false;
        }
    }

    public function getOrdersByFarmer($farmerId) {
        try {
            $stmt = $this->connection->prepare("
                SELECT o.*, c.name as product_name, u.name as buyer_name, u.phone as buyer_phone 
                FROM orders o 
                JOIN crops c ON o.product_id = c.id 
                JOIN users u ON o.buyer_id = u.id 
                WHERE o.farmer_id = ? 
                ORDER BY o.created_at DESC
            ");
            $stmt->execute([$farmerId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get orders by farmer failed: " . $e->getMessage());
            return false;
        }
    }

    public function updateOrderStatus($orderId, $status) {
        try {
            $stmt = $this->connection->prepare("
                UPDATE orders 
                SET status = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            return $stmt->execute([$status, $orderId]);
        } catch (PDOException $e) {
            error_log("Update order status failed: " . $e->getMessage());
            return false;
        }
    }

    // Market Price Methods
    public function getMarketPrices($crop = null, $limit = 50) {
        try {
            if ($crop) {
                $stmt = $this->connection->prepare("
                    SELECT * FROM market_prices 
                    WHERE crop_name = ? 
                    ORDER BY updated_at DESC 
                    LIMIT ?
                ");
                $stmt->execute([$crop, $limit]);
            } else {
                $stmt = $this->connection->prepare("
                    SELECT * FROM market_prices 
                    ORDER BY updated_at DESC 
                    LIMIT ?
                ");
                $stmt->execute([$limit]);
            }
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get market prices failed: " . $e->getMessage());
            return false;
        }
    }

    public function addMarketPrice($cropName, $price, $location, $unit = 'kg') {
        try {
            $stmt = $this->connection->prepare("
                INSERT INTO market_prices (crop_name, price, location, unit, created_at, updated_at) 
                VALUES (?, ?, ?, ?, NOW(), NOW())
            ");
            
            $result = $stmt->execute([$cropName, $price, $location, $unit]);
            
            if ($result) {
                return $this->connection->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            error_log("Add market price failed: " . $e->getMessage());
            return false;
        }
    }

    // Farming Tips Methods
    public function getFarmingTips($category = null, $limit = 10) {
        try {
            if ($category) {
                $stmt = $this->connection->prepare("
                    SELECT * FROM farming_tips 
                    WHERE category = ? 
                    ORDER BY created_at DESC 
                    LIMIT ?
                ");
                $stmt->execute([$category, $limit]);
            } else {
                $stmt = $this->connection->prepare("
                    SELECT * FROM farming_tips 
                    ORDER BY created_at DESC 
                    LIMIT ?
                ");
                $stmt->execute([$limit]);
            }
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get farming tips failed: " . $e->getMessage());
            return false;
        }
    }

    public function addFarmingTip($title, $content, $category) {
        try {
            $stmt = $this->connection->prepare("
                INSERT INTO farming_tips (title, content, category, created_at, updated_at) 
                VALUES (?, ?, ?, NOW(), NOW())
            ");
            
            $result = $stmt->execute([$title, $content, $category]);
            
            if ($result) {
                return $this->connection->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            error_log("Add farming tip failed: " . $e->getMessage());
            return false;
        }
    }

    // Payment Methods
    public function createPayment($orderId, $amount, $method = 'mobile_money', $status = 'pending') {
        try {
            $stmt = $this->connection->prepare("
                INSERT INTO payments (order_id, amount, method, status, created_at, updated_at) 
                VALUES (?, ?, ?, ?, NOW(), NOW())
            ");
            
            $result = $stmt->execute([$orderId, $amount, $method, $status]);
            
            if ($result) {
                return $this->connection->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            error_log("Create payment failed: " . $e->getMessage());
            return false;
        }
    }

    public function updatePaymentStatus($paymentId, $status) {
        try {
            $stmt = $this->connection->prepare("
                UPDATE payments 
                SET status = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            return $stmt->execute([$status, $paymentId]);
        } catch (PDOException $e) {
            error_log("Update payment status failed: " . $e->getMessage());
            return false;
        }
    }

    // Dispute Methods
    public function createDispute($orderId, $userId, $issue, $description) {
        try {
            $stmt = $this->connection->prepare("
                INSERT INTO disputes (order_id, user_id, issue, description, status, created_at, updated_at) 
                VALUES (?, ?, ?, ?, 'pending', NOW(), NOW())
            ");
            
            $result = $stmt->execute([$orderId, $userId, $issue, $description]);
            
            if ($result) {
                return $this->connection->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            error_log("Create dispute failed: " . $e->getMessage());
            return false;
        }
    }

    public function getDisputesByUser($userId) {
        try {
            $stmt = $this->connection->prepare("
                SELECT d.*, o.id as order_id, c.name as product_name 
                FROM disputes d 
                JOIN orders o ON d.order_id = o.id 
                JOIN crops c ON o.product_id = c.id 
                WHERE d.user_id = ? 
                ORDER BY d.created_at DESC
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get disputes by user failed: " . $e->getMessage());
            return false;
        }
    }

    // Session Management Methods
    public function createSession($sessionId, $phoneNumber, $data = null) {
        try {
            $stmt = $this->connection->prepare("
                INSERT INTO ussd_sessions (session_id, phone_number, data, created_at, updated_at) 
                VALUES (?, ?, ?, NOW(), NOW())
            ");
            
            $result = $stmt->execute([$sessionId, $phoneNumber, $data]);
            
            if ($result) {
                return $this->connection->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            error_log("Create session failed: " . $e->getMessage());
            return false;
        }
    }

    public function getSession($sessionId) {
        try {
            $stmt = $this->connection->prepare("SELECT * FROM ussd_sessions WHERE session_id = ?");
            $stmt->execute([$sessionId]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Get session failed: " . $e->getMessage());
            return false;
        }
    }

    public function updateSession($sessionId, $data) {
        try {
            $stmt = $this->connection->prepare("
                UPDATE ussd_sessions 
                SET data = ?, updated_at = NOW() 
                WHERE session_id = ?
            ");
            return $stmt->execute([$data, $sessionId]);
        } catch (PDOException $e) {
            error_log("Update session failed: " . $e->getMessage());
            return false;
        }
    }

    public function deleteSession($sessionId) {
        try {
            $stmt = $this->connection->prepare("DELETE FROM ussd_sessions WHERE session_id = ?");
            return $stmt->execute([$sessionId]);
        } catch (PDOException $e) {
            error_log("Delete session failed: " . $e->getMessage());
            return false;
        }
    }

    public function cleanupExpiredSessions() {
        try {
            $stmt = $this->connection->prepare("
                DELETE FROM ussd_sessions 
                WHERE updated_at < DATE_SUB(NOW(), INTERVAL ? SECOND)
            ");
            return $stmt->execute([Util::$SESSION_TIMEOUT]);
        } catch (PDOException $e) {
            error_log("Cleanup expired sessions failed: " . $e->getMessage());
            return false;
        }
    }

    // Statistics Methods
    public function getUserStats($userId) {
        try {
            $user = $this->getUserByPhone($userId);
            if (!$user) return false;

            $stats = ['user' => $user];

            if ($user['role'] === 'farmer') {
                $stmt = $this->connection->prepare("
                    SELECT COUNT(*) as total_products, SUM(quantity) as total_quantity 
                    FROM crops 
                    WHERE farmer_id = ?
                ");
                $stmt->execute([$user['id']]);
                $stats['products'] = $stmt->fetch();

                $stmt = $this->connection->prepare("
                    SELECT COUNT(*) as total_orders, SUM(total_price) as total_earnings 
                    FROM orders 
                    WHERE farmer_id = ? AND status = 'delivered'
                ");
                $stmt->execute([$user['id']]);
                $stats['orders'] = $stmt->fetch();
            } else {
                $stmt = $this->connection->prepare("
                    SELECT COUNT(*) as total_orders, SUM(total_price) as total_spent 
                    FROM orders 
                    WHERE buyer_id = ?
                ");
                $stmt->execute([$user['id']]);
                $stats['orders'] = $stmt->fetch();
            }

            return $stats;
        } catch (PDOException $e) {
            error_log("Get user stats failed: " . $e->getMessage());
            return false;
        }
    }

    // Search Methods
    public function searchProducts($query, $limit = 20) {
        try {
            $stmt = $this->connection->prepare("
                SELECT c.*, u.name as farmer_name, u.phone as farmer_phone, u.province, u.district 
                FROM crops c 
                JOIN users u ON c.farmer_id = u.id 
                WHERE (c.name LIKE ? OR c.description LIKE ?) AND c.quantity > 0 
                ORDER BY c.created_at DESC 
                LIMIT ?
            ");
            $searchTerm = "%$query%";
            $stmt->execute([$searchTerm, $searchTerm, $limit]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Search products failed: " . $e->getMessage());
            return false;
        }
    }

    public function getProductsByCategory($category, $limit = 20) {
        try {
            $stmt = $this->connection->prepare("
                SELECT c.*, u.name as farmer_name, u.phone as farmer_phone, u.province, u.district 
                FROM crops c 
                JOIN users u ON c.farmer_id = u.id 
                WHERE c.category = ? AND c.quantity > 0 
                ORDER BY c.created_at DESC 
                LIMIT ?
            ");
            $stmt->execute([$category, $limit]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get products by category failed: " . $e->getMessage());
            return false;
        }
    }

    public function getProductsByLocation($province, $district = null, $limit = 20) {
        try {
            if ($district) {
                $stmt = $this->connection->prepare("
                    SELECT c.*, u.name as farmer_name, u.phone as farmer_phone, u.province, u.district 
                    FROM crops c 
                    JOIN users u ON c.farmer_id = u.id 
                    WHERE u.province = ? AND u.district = ? AND c.quantity > 0 
                    ORDER BY c.created_at DESC 
                    LIMIT ?
                ");
                $stmt->execute([$province, $district, $limit]);
            } else {
                $stmt = $this->connection->prepare("
                    SELECT c.*, u.name as farmer_name, u.phone as farmer_phone, u.province, u.district 
                    FROM crops c 
                    JOIN users u ON c.farmer_id = u.id 
                    WHERE u.province = ? AND c.quantity > 0 
                    ORDER BY c.created_at DESC 
                    LIMIT ?
                ");
                $stmt->execute([$province, $limit]);
            }
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get products by location failed: " . $e->getMessage());
            return false;
        }
    }

    // Close connection
    public function close() {
        $this->connection = null;
    }

    // Destructor
    public function __destruct() {
        $this->close();
    }
}
?>
