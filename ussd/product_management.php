<?php
require_once 'util.php';
require_once 'database.php';
require_once 'sms.php';

class ProductManagement {
    private $db;
    private $sms;

    public function __construct() {
        $this->db = new Database();
        $this->sms = new Sms();
    }

    // Add new product
    public function addProduct($farmerId, $name, $description, $quantity, $price, $category, $image = null) {
        try {
            // Validate inputs
            if (!$this->validateProductData($name, $description, $quantity, $price, $category)) {
                return false;
            }

            // Add product to database
            $productId = $this->db->addProduct($farmerId, $name, $description, $quantity, $price, $category, $image);
            
            if ($productId) {
                // Get farmer details for SMS
                $farmer = $this->db->getUserByPhone($farmerId);
                if ($farmer) {
                    $this->sms->sendProductListedSMS(
                        $farmer['phone'],
                        $farmer['name'],
                        $name,
                        $quantity,
                        $price
                    );
                }
                
                return $productId;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Add product error: " . $e->getMessage());
            return false;
        }
    }

    // Validate product data
    private function validateProductData($name, $description, $quantity, $price, $category) {
        // Validate name
        if (empty($name) || strlen($name) < 2) {
            return false;
        }

        // Validate description
        if (empty($description) || strlen($description) < 10) {
            return false;
        }

        // Validate quantity
        if (!is_numeric($quantity) || $quantity < Util::$MIN_QUANTITY || $quantity > Util::$MAX_QUANTITY) {
            return false;
        }

        // Validate price
        if (!is_numeric($price) || $price < Util::$MIN_PRICE || $price > Util::$MAX_PRICE) {
            return false;
        }

        // Validate category
        if (empty($category)) {
            return false;
        }

        return true;
    }

    // Get products by farmer
    public function getProductsByFarmer($farmerId) {
        return $this->db->getProductsByFarmer($farmerId);
    }

    // Get all available products
    public function getAllAvailableProducts($limit = 20, $offset = 0) {
        return $this->db->getAllAvailableProducts($limit, $offset);
    }

    // Get product by ID
    public function getProductById($productId) {
        return $this->db->getProductById($productId);
    }

    // Update product quantity
    public function updateProductQuantity($productId, $newQuantity) {
        try {
            if (!is_numeric($newQuantity) || $newQuantity < 0) {
                return false;
            }

            $result = $this->db->updateProductQuantity($productId, $newQuantity);
            
            if ($result && $newQuantity < 10) {
                // Send low stock alert
                $product = $this->getProductById($productId);
                if ($product) {
                    $farmer = $this->db->getUserByPhone($product['farmer_id']);
                    if ($farmer) {
                        $this->sms->sendLowStockAlertSMS(
                            $farmer['phone'],
                            $farmer['name'],
                            $product['name'],
                            $newQuantity
                        );
                    }
                }
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Update product quantity error: " . $e->getMessage());
            return false;
        }
    }

    // Delete product
    public function deleteProduct($productId) {
        return $this->db->deleteProduct($productId);
    }

    // Search products
    public function searchProducts($query, $limit = 20) {
        return $this->db->searchProducts($query, $limit);
    }

    // Get products by category
    public function getProductsByCategory($category, $limit = 20) {
        return $this->db->getProductsByCategory($category, $limit);
    }

    // Get products by location
    public function getProductsByLocation($province, $district = null, $limit = 20) {
        return $this->db->getProductsByLocation($province, $district, $limit);
    }

    // Create order
    public function createOrder($buyerId, $farmerId, $productId, $quantity, $totalPrice) {
        try {
            // Validate inputs
            if (!$this->validateOrderData($buyerId, $farmerId, $productId, $quantity, $totalPrice)) {
                return false;
            }

            // Check if product has enough quantity
            $product = $this->getProductById($productId);
            if (!$product || $product['quantity'] < $quantity) {
                return false;
            }

            // Create order
            $orderId = $this->db->createOrder($buyerId, $farmerId, $productId, $quantity, $totalPrice);
            
            if ($orderId) {
                // Update product quantity
                $newQuantity = $product['quantity'] - $quantity;
                $this->updateProductQuantity($productId, $newQuantity);

                // Send notifications
                $buyer = $this->db->getUserByPhone($buyerId);
                $farmer = $this->db->getUserByPhone($farmerId);
                
                if ($buyer) {
                    $this->sms->sendOrderConfirmationSMS(
                        $buyer['phone'],
                        $buyer['name'],
                        $product['name'],
                        $farmer['name'],
                        $quantity,
                        $totalPrice
                    );
                }
                
                if ($farmer) {
                    $this->sms->sendOrderNotificationToFarmerSMS(
                        $farmer['phone'],
                        $farmer['name'],
                        $product['name'],
                        $buyer['name'],
                        $quantity,
                        $totalPrice
                    );
                }
                
                return $orderId;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Create order error: " . $e->getMessage());
            return false;
        }
    }

    // Validate order data
    private function validateOrderData($buyerId, $farmerId, $productId, $quantity, $totalPrice) {
        // Validate buyer
        if (!$this->db->getUserByPhone($buyerId)) {
            return false;
        }

        // Validate farmer
        if (!$this->db->getUserByPhone($farmerId)) {
            return false;
        }

        // Validate product
        if (!$this->getProductById($productId)) {
            return false;
        }

        // Validate quantity
        if (!is_numeric($quantity) || $quantity < 1) {
            return false;
        }

        // Validate total price
        if (!is_numeric($totalPrice) || $totalPrice < 0) {
            return false;
        }

        return true;
    }

    // Get orders by buyer
    public function getOrdersByBuyer($buyerId) {
        return $this->db->getOrdersByBuyer($buyerId);
    }

    // Get orders by farmer
    public function getOrdersByFarmer($farmerId) {
        return $this->db->getOrdersByFarmer($farmerId);
    }

    // Update order status
    public function updateOrderStatus($orderId, $status) {
        try {
            if (!in_array($status, array_values(Util::$ORDER_STATUS))) {
                return false;
            }

            $result = $this->db->updateOrderStatus($orderId, $status);
            
            if ($result) {
                // Get order details for notification
                $order = $this->getOrderById($orderId);
                if ($order) {
                    $buyer = $this->db->getUserByPhone($order['buyer_id']);
                    $farmer = $this->db->getUserByPhone($order['farmer_id']);
                    
                    if ($status === 'delivered' && $buyer) {
                        $this->sms->sendPaymentConfirmationSMS(
                            $buyer['phone'],
                            $orderId,
                            $order['total_price']
                        );
                    }
                }
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Update order status error: " . $e->getMessage());
            return false;
        }
    }

    // Get order by ID
    public function getOrderById($orderId) {
        try {
            $stmt = $this->db->getConnection()->prepare("
                SELECT o.*, c.name as product_name, 
                       ub.name as buyer_name, ub.phone as buyer_phone,
                       uf.name as farmer_name, uf.phone as farmer_phone 
                FROM orders o 
                JOIN crops c ON o.product_id = c.id 
                JOIN users ub ON o.buyer_id = ub.id 
                JOIN users uf ON o.farmer_id = uf.id 
                WHERE o.id = ?
            ");
            $stmt->execute([$orderId]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Get order by ID failed: " . $e->getMessage());
            return false;
        }
    }

    // Get product statistics
    public function getProductStats($farmerId = null) {
        try {
            if ($farmerId) {
                $stmt = $this->db->getConnection()->prepare("
                    SELECT 
                        COUNT(*) as total_products,
                        SUM(quantity) as total_quantity,
                        AVG(price) as average_price,
                        SUM(CASE WHEN quantity > 0 THEN 1 ELSE 0 END) as available_products
                    FROM crops 
                    WHERE farmer_id = ?
                ");
                $stmt->execute([$farmerId]);
            } else {
                $stmt = $this->db->getConnection()->prepare("
                    SELECT 
                        COUNT(*) as total_products,
                        SUM(quantity) as total_quantity,
                        AVG(price) as average_price,
                        SUM(CASE WHEN quantity > 0 THEN 1 ELSE 0 END) as available_products
                    FROM crops
                ");
                $stmt->execute();
            }
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Get product stats failed: " . $e->getMessage());
            return false;
        }
    }

    // Get order statistics
    public function getOrderStats($userId = null, $role = null) {
        try {
            if ($userId && $role) {
                if ($role === 'farmer') {
                    $stmt = $this->db->getConnection()->prepare("
                        SELECT 
                            COUNT(*) as total_orders,
                            SUM(total_price) as total_earnings,
                            AVG(total_price) as average_order_value,
                            SUM(CASE WHEN status = 'delivered' THEN total_price ELSE 0 END) as completed_earnings
                        FROM orders 
                        WHERE farmer_id = ?
                    ");
                } else {
                    $stmt = $this->db->getConnection()->prepare("
                        SELECT 
                            COUNT(*) as total_orders,
                            SUM(total_price) as total_spent,
                            AVG(total_price) as average_order_value,
                            SUM(CASE WHEN status = 'delivered' THEN total_price ELSE 0 END) as completed_spent
                        FROM orders 
                        WHERE buyer_id = ?
                    ");
                }
                $stmt->execute([$userId]);
            } else {
                $stmt = $this->db->getConnection()->prepare("
                    SELECT 
                        COUNT(*) as total_orders,
                        SUM(total_price) as total_value,
                        AVG(total_price) as average_order_value,
                        SUM(CASE WHEN status = 'delivered' THEN total_price ELSE 0 END) as completed_value
                    FROM orders
                ");
                $stmt->execute();
            }
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Get order stats failed: " . $e->getMessage());
            return false;
        }
    }

    // Get popular products
    public function getPopularProducts($limit = 10) {
        try {
            $stmt = $this->db->getConnection()->prepare("
                SELECT c.*, u.name as farmer_name, u.phone as farmer_phone,
                       COUNT(o.id) as order_count, SUM(o.quantity) as total_ordered
                FROM crops c 
                JOIN users u ON c.farmer_id = u.id 
                LEFT JOIN orders o ON c.id = o.product_id 
                WHERE c.quantity > 0 
                GROUP BY c.id 
                ORDER BY order_count DESC, total_ordered DESC 
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get popular products failed: " . $e->getMessage());
            return false;
        }
    }

    // Get recent products
    public function getRecentProducts($limit = 10) {
        try {
            $stmt = $this->db->getConnection()->prepare("
                SELECT c.*, u.name as farmer_name, u.phone as farmer_phone, u.province, u.district 
                FROM crops c 
                JOIN users u ON c.farmer_id = u.id 
                WHERE c.quantity > 0 
                ORDER BY c.created_at DESC 
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get recent products failed: " . $e->getMessage());
            return false;
        }
    }

    // Get products by price range
    public function getProductsByPriceRange($minPrice, $maxPrice, $limit = 20) {
        try {
            $stmt = $this->db->getConnection()->prepare("
                SELECT c.*, u.name as farmer_name, u.phone as farmer_phone, u.province, u.district 
                FROM crops c 
                JOIN users u ON c.farmer_id = u.id 
                WHERE c.quantity > 0 AND c.price BETWEEN ? AND ? 
                ORDER BY c.price ASC 
                LIMIT ?
            ");
            $stmt->execute([$minPrice, $maxPrice, $limit]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get products by price range failed: " . $e->getMessage());
            return false;
        }
    }

    // Get farmer's product performance
    public function getFarmerProductPerformance($farmerId) {
        try {
            $stmt = $this->db->getConnection()->prepare("
                SELECT 
                    c.name as product_name,
                    c.quantity as current_quantity,
                    c.price,
                    COUNT(o.id) as total_orders,
                    SUM(o.quantity) as total_sold,
                    SUM(o.total_price) as total_revenue
                FROM crops c 
                LEFT JOIN orders o ON c.id = o.product_id 
                WHERE c.farmer_id = ? 
                GROUP BY c.id 
                ORDER BY total_revenue DESC
            ");
            $stmt->execute([$farmerId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get farmer product performance failed: " . $e->getMessage());
            return false;
        }
    }

    // Get buyer's purchase history
    public function getBuyerPurchaseHistory($buyerId) {
        try {
            $stmt = $this->db->getConnection()->prepare("
                SELECT 
                    o.*,
                    c.name as product_name,
                    u.name as farmer_name,
                    u.phone as farmer_phone,
                    u.province,
                    u.district
                FROM orders o 
                JOIN crops c ON o.product_id = c.id 
                JOIN users u ON o.farmer_id = u.id 
                WHERE o.buyer_id = ? 
                ORDER BY o.created_at DESC
            ");
            $stmt->execute([$buyerId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get buyer purchase history failed: " . $e->getMessage());
            return false;
        }
    }

    // Create dispute
    public function createDispute($orderId, $userId, $issue, $description) {
        try {
            // Validate inputs
            if (empty($issue) || empty($description)) {
                return false;
            }

            // Check if order exists
            $order = $this->getOrderById($orderId);
            if (!$order) {
                return false;
            }

            // Create dispute
            $disputeId = $this->db->createDispute($orderId, $userId, $issue, $description);
            
            if ($disputeId) {
                // Send dispute notification
                $user = $this->db->getUserByPhone($userId);
                if ($user) {
                    $this->sms->sendDisputeNotificationSMS(
                        $user['phone'],
                        $disputeId,
                        $issue
                    );
                }
                
                return $disputeId;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Create dispute error: " . $e->getMessage());
            return false;
        }
    }

    // Get disputes by user
    public function getDisputesByUser($userId) {
        return $this->db->getDisputesByUser($userId);
    }

    // Get product categories
    public function getProductCategories() {
        try {
            $stmt = $this->db->getConnection()->prepare("
                SELECT DISTINCT category 
                FROM crops 
                WHERE category IS NOT NULL AND category != '' 
                ORDER BY category
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            error_log("Get product categories failed: " . $e->getMessage());
            return [];
        }
    }

    // Get product summary
    public function getProductSummary() {
        try {
            $stmt = $this->db->getConnection()->prepare("
                SELECT 
                    COUNT(*) as total_products,
                    SUM(quantity) as total_quantity,
                    AVG(price) as average_price,
                    MIN(price) as min_price,
                    MAX(price) as max_price,
                    SUM(CASE WHEN quantity > 0 THEN 1 ELSE 0 END) as available_products
                FROM crops
            ");
            $stmt->execute();
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Get product summary failed: " . $e->getMessage());
            return false;
        }
    }
}
?>
