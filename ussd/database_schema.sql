-- FarmBridge AI USSD Application Database Schema
-- Additional tables required for USSD functionality

-- USSD Sessions table
CREATE TABLE IF NOT EXISTS ussd_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(255) UNIQUE NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    data TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_session_id (session_id),
    INDEX idx_phone_number (phone_number),
    INDEX idx_updated_at (updated_at)
);

-- Market Prices table
CREATE TABLE IF NOT EXISTS market_prices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    crop_name VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    location VARCHAR(100) NOT NULL,
    unit VARCHAR(20) DEFAULT 'kg',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_crop_name (crop_name),
    INDEX idx_location (location),
    INDEX idx_created_at (created_at)
);

-- Farming Tips table
CREATE TABLE IF NOT EXISTS farming_tips (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    category VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_created_at (created_at)
);

-- Price Alerts table
CREATE TABLE IF NOT EXISTS price_alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    crop_name VARCHAR(100) NOT NULL,
    target_price DECIMAL(10,2) NOT NULL,
    condition ENUM('above', 'below') DEFAULT 'above',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_crop_name (crop_name),
    INDEX idx_is_active (is_active)
);

-- Monitoring Metrics table
CREATE TABLE IF NOT EXISTS monitoring_metrics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    active_sessions INT DEFAULT 0,
    expired_sessions INT DEFAULT 0,
    total_users INT DEFAULT 0,
    farmers INT DEFAULT 0,
    buyers INT DEFAULT 0,
    new_users_24h INT DEFAULT 0,
    total_products INT DEFAULT 0,
    total_quantity DECIMAL(15,2) DEFAULT 0,
    available_products INT DEFAULT 0,
    total_orders INT DEFAULT 0,
    total_value DECIMAL(15,2) DEFAULT 0,
    completed_orders INT DEFAULT 0,
    orders_24h INT DEFAULT 0,
    total_price_updates INT DEFAULT 0,
    crops_tracked INT DEFAULT 0,
    updates_24h INT DEFAULT 0,
    total_tips INT DEFAULT 0,
    categories INT DEFAULT 0,
    tips_24h INT DEFAULT 0,
    INDEX idx_timestamp (timestamp)
);

-- User Preferences table
CREATE TABLE IF NOT EXISTS user_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    preferences JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_preferences (user_id)
);

-- SMS Logs table
CREATE TABLE IF NOT EXISTS sms_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phone_number VARCHAR(20) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('sent', 'failed', 'pending') DEFAULT 'pending',
    response TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_phone_number (phone_number),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
);

-- USSD Logs table
CREATE TABLE IF NOT EXISTS ussd_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(255) NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    input_text TEXT,
    response_text TEXT,
    processing_time DECIMAL(10,4),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_session_id (session_id),
    INDEX idx_phone_number (phone_number),
    INDEX idx_created_at (created_at)
);

-- Error Logs table
CREATE TABLE IF NOT EXISTS error_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    error_type VARCHAR(100) NOT NULL,
    error_message TEXT NOT NULL,
    error_file VARCHAR(255),
    error_line INT,
    stack_trace TEXT,
    user_id INT,
    session_id VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_error_type (error_type),
    INDEX idx_user_id (user_id),
    INDEX idx_session_id (session_id),
    INDEX idx_created_at (created_at)
);

-- System Settings table
CREATE TABLE IF NOT EXISTS system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_setting_key (setting_key)
);

-- Insert default system settings
INSERT INTO system_settings (setting_key, setting_value, description) VALUES
('ussd_service_code', '*384*123#', 'USSD service code'),
('session_timeout', '300', 'Session timeout in seconds'),
('max_retry_attempts', '3', 'Maximum retry attempts'),
('sms_enabled', 'true', 'Enable SMS notifications'),
('market_prices_enabled', 'true', 'Enable market prices feature'),
('farming_tips_enabled', 'true', 'Enable farming tips feature'),
('price_alerts_enabled', 'true', 'Enable price alerts feature'),
('monitoring_enabled', 'true', 'Enable system monitoring'),
('log_level', 'info', 'Logging level'),
('backup_enabled', 'true', 'Enable automatic backups'),
('backup_frequency', 'daily', 'Backup frequency'),
('backup_retention_days', '30', 'Backup retention period in days')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

-- Insert sample farming tips
INSERT INTO farming_tips (title, content, category) VALUES
('Maize Planting Season', 'Plant maize during the long rainy season (March-May) for best results. Ensure soil is well-drained and fertile.', 'SEASONAL'),
('Bean Pest Control', 'Use neem oil spray to control bean pests naturally. Apply every 7-10 days during flowering stage.', 'PEST_CONTROL'),
('Soil Preparation', 'Prepare soil by tilling and adding organic matter. Test soil pH and adjust if necessary for optimal crop growth.', 'SOIL_MANAGEMENT'),
('Water Conservation', 'Use drip irrigation to conserve water and ensure efficient delivery to plant roots. Mulch around plants to retain moisture.', 'WATER_MANAGEMENT'),
('Crop Rotation Benefits', 'Rotate crops annually to prevent soil depletion and reduce pest and disease problems. Follow legumes with cereals.', 'CROP_ROTATION'),
('Fertilizer Application', 'Apply fertilizers based on soil test results. Use organic fertilizers when possible for sustainable farming.', 'FERTILIZATION'),
('Potato Planting Tips', 'Plant potatoes in well-drained soil with good organic matter. Space plants 30cm apart for optimal growth.', 'SEASONAL'),
('Tomato Disease Prevention', 'Prevent tomato diseases by ensuring good air circulation, proper spacing, and avoiding overhead watering.', 'PEST_CONTROL'),
('Rice Water Management', 'Maintain consistent water levels in rice fields. Flood fields during early growth stages and drain before harvest.', 'WATER_MANAGEMENT'),
('Cassava Harvesting', 'Harvest cassava when leaves start yellowing. Use sharp tools to avoid damaging the tubers.', 'SEASONAL')
ON DUPLICATE KEY UPDATE content = VALUES(content);

-- Insert sample market prices
INSERT INTO market_prices (crop_name, price, location, unit) VALUES
('Maize', 450, 'Kigali', 'kg'),
('Beans', 800, 'Kigali', 'kg'),
('Potatoes', 300, 'Kigali', 'kg'),
('Rice', 1200, 'Kigali', 'kg'),
('Maize', 420, 'Northern Province', 'kg'),
('Beans', 750, 'Northern Province', 'kg'),
('Potatoes', 280, 'Northern Province', 'kg'),
('Rice', 1150, 'Northern Province', 'kg'),
('Maize', 480, 'Southern Province', 'kg'),
('Beans', 820, 'Southern Province', 'kg'),
('Potatoes', 320, 'Southern Province', 'kg'),
('Rice', 1250, 'Southern Province', 'kg'),
('Maize', 460, 'Eastern Province', 'kg'),
('Beans', 790, 'Eastern Province', 'kg'),
('Potatoes', 310, 'Eastern Province', 'kg'),
('Rice', 1180, 'Eastern Province', 'kg'),
('Maize', 440, 'Western Province', 'kg'),
('Beans', 770, 'Western Province', 'kg'),
('Potatoes', 290, 'Western Province', 'kg'),
('Rice', 1220, 'Western Province', 'kg')
ON DUPLICATE KEY UPDATE price = VALUES(price);

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_crops_farmer_id ON crops(farmer_id);
CREATE INDEX IF NOT EXISTS idx_crops_category ON crops(category);
CREATE INDEX IF NOT EXISTS idx_crops_quantity ON crops(quantity);
CREATE INDEX IF NOT EXISTS idx_crops_price ON crops(price);
CREATE INDEX IF NOT EXISTS idx_crops_created_at ON crops(created_at);

CREATE INDEX IF NOT EXISTS idx_orders_buyer_id ON orders(buyer_id);
CREATE INDEX IF NOT EXISTS idx_orders_farmer_id ON orders(farmer_id);
CREATE INDEX IF NOT EXISTS idx_orders_product_id ON orders(product_id);
CREATE INDEX IF NOT EXISTS idx_orders_status ON orders(status);
CREATE INDEX IF NOT EXISTS idx_orders_created_at ON orders(created_at);

CREATE INDEX IF NOT EXISTS idx_users_role ON users(role);
CREATE INDEX IF NOT EXISTS idx_users_province ON users(province);
CREATE INDEX IF NOT EXISTS idx_users_district ON users(district);
CREATE INDEX IF NOT EXISTS idx_users_created_at ON users(created_at);

-- Create views for common queries
CREATE OR REPLACE VIEW active_sessions AS
SELECT 
    session_id,
    phone_number,
    created_at,
    updated_at,
    TIMESTAMPDIFF(SECOND, updated_at, NOW()) as seconds_since_update
FROM ussd_sessions 
WHERE updated_at > DATE_SUB(NOW(), INTERVAL 300 SECOND);

CREATE OR REPLACE VIEW recent_orders AS
SELECT 
    o.*,
    c.name as product_name,
    ub.name as buyer_name,
    uf.name as farmer_name
FROM orders o
JOIN crops c ON o.product_id = c.id
JOIN users ub ON o.buyer_id = ub.id
JOIN users uf ON o.farmer_id = uf.id
WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
ORDER BY o.created_at DESC;

CREATE OR REPLACE VIEW available_products AS
SELECT 
    c.*,
    u.name as farmer_name,
    u.phone as farmer_phone,
    u.province,
    u.district
FROM crops c
JOIN users u ON c.farmer_id = u.id
WHERE c.quantity > 0
ORDER BY c.created_at DESC;

CREATE OR REPLACE VIEW market_price_summary AS
SELECT 
    crop_name,
    AVG(price) as average_price,
    MIN(price) as min_price,
    MAX(price) as max_price,
    COUNT(*) as price_updates,
    MAX(updated_at) as last_update
FROM market_prices
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY crop_name
ORDER BY average_price DESC;

-- Create stored procedures for common operations
DELIMITER //

CREATE PROCEDURE IF NOT EXISTS CleanupExpiredSessions()
BEGIN
    DELETE FROM ussd_sessions 
    WHERE updated_at < DATE_SUB(NOW(), INTERVAL 300 SECOND);
    
    SELECT ROW_COUNT() as deleted_sessions;
END //

CREATE PROCEDURE IF NOT EXISTS GetUserStats(IN user_id INT)
BEGIN
    SELECT 
        u.*,
        COUNT(DISTINCT c.id) as total_products,
        SUM(c.quantity) as total_quantity,
        COUNT(DISTINCT o.id) as total_orders,
        SUM(CASE WHEN o.status = 'delivered' THEN o.total_price ELSE 0 END) as total_earnings
    FROM users u
    LEFT JOIN crops c ON u.id = c.farmer_id
    LEFT JOIN orders o ON u.id = o.farmer_id OR u.id = o.buyer_id
    WHERE u.id = user_id
    GROUP BY u.id;
END //

CREATE PROCEDURE IF NOT EXISTS GetMarketPriceTrends(IN crop_name VARCHAR(100), IN days INT)
BEGIN
    SELECT 
        DATE(created_at) as date,
        AVG(price) as average_price,
        MIN(price) as min_price,
        MAX(price) as max_price,
        COUNT(*) as price_updates
    FROM market_prices 
    WHERE crop_name = crop_name 
    AND created_at >= DATE_SUB(NOW(), INTERVAL days DAY)
    GROUP BY DATE(created_at) 
    ORDER BY date DESC;
END //

DELIMITER ;

-- Grant permissions (adjust as needed for your setup)
-- GRANT SELECT, INSERT, UPDATE, DELETE ON farmbridge_ai.* TO 'ussd_user'@'localhost';
-- FLUSH PRIVILEGES;
