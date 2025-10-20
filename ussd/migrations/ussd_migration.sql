-- FarmBridge AI USSD - Minimal Migration for shared database
-- Safe to run multiple times (IF NOT EXISTS used). Adjust names if needed.

-- 1) USSD session storage
CREATE TABLE IF NOT EXISTS ussd_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(255) UNIQUE NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    data TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_ussd_sessions_session_id (session_id),
    INDEX idx_ussd_sessions_phone (phone_number),
    INDEX idx_ussd_sessions_updated (updated_at)
);

-- 2) Farming tips (only if not present)
CREATE TABLE IF NOT EXISTS farming_tips (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    category VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_farming_tips_category (category),
    INDEX idx_farming_tips_created (created_at)
);

-- 3) Price alerts (per-user)
CREATE TABLE IF NOT EXISTS price_alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    crop_name VARCHAR(100) NOT NULL,
    target_price DECIMAL(10,2) NOT NULL,
    `condition` ENUM('above', 'below') DEFAULT 'above',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_price_alerts_user (user_id),
    INDEX idx_price_alerts_crop (crop_name),
    INDEX idx_price_alerts_active (is_active),
    CONSTRAINT fk_price_alerts_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 4) Monitoring metrics (optional operational metrics)
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
    INDEX idx_monitoring_metrics_ts (timestamp)
);

-- 5) USSD logs (optional)
CREATE TABLE IF NOT EXISTS ussd_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(255) NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    input_text TEXT,
    response_text TEXT,
    processing_time_ms INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ussd_logs_session (session_id),
    INDEX idx_ussd_logs_phone (phone_number),
    INDEX idx_ussd_logs_created (created_at)
);

-- 6) SMS logs (optional)
CREATE TABLE IF NOT EXISTS sms_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phone_number VARCHAR(20) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('sent', 'failed', 'pending') DEFAULT 'pending',
    provider_response TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_sms_logs_phone (phone_number),
    INDEX idx_sms_logs_status (status),
    INDEX idx_sms_logs_created (created_at)
);

-- 7) Ensure a usable market prices view for USSD
-- Existing table in web app: database/schema.sql defines `market_prices(commodity, market, price, date, source)`
-- USSD expects fields: crop_name, price, location, unit, updated_at
-- Create a compatibility VIEW (read-only mapping)
DROP VIEW IF EXISTS market_prices_ussd_view;
CREATE VIEW market_prices_ussd_view AS
SELECT 
    mp.id,
    mp.commodity      AS crop_name,
    mp.price          AS price,
    mp.market         AS location,
    'kg'              AS unit,
    mp.date           AS updated_at
FROM market_prices mp;

-- 8) Helpful indexes on existing core tables (no-ops if they exist)
CREATE INDEX IF NOT EXISTS idx_users_phone ON users(phone);
CREATE INDEX IF NOT EXISTS idx_users_role ON users(role);
CREATE INDEX IF NOT EXISTS idx_crops_farmer_id ON crops(farmer_id);
CREATE INDEX IF NOT EXISTS idx_crops_quantity ON crops(quantity);
CREATE INDEX IF NOT EXISTS idx_orders_buyer_id ON orders(buyer_id);
CREATE INDEX IF NOT EXISTS idx_orders_crop_id ON orders(crop_id);
CREATE INDEX IF NOT EXISTS idx_orders_status ON orders(status);

-- 9) Seed minimal farming tips if table is empty (idempotent-ish)
INSERT INTO farming_tips (title, content, category)
SELECT 'Soil Preparation', 'Prepare soil with organic matter; test pH for optimal growth.', 'SOIL_MANAGEMENT'
WHERE NOT EXISTS (SELECT 1 FROM farming_tips LIMIT 1);


