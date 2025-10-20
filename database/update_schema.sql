-- Administrative divisions: provinces, districts, sectors, addresses

CREATE TABLE IF NOT EXISTS provinces (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    name_en VARCHAR(100) NULL,
    name_rw VARCHAR(100) NULL
);

CREATE TABLE IF NOT EXISTS districts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    province_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    name_en VARCHAR(100) NULL,
    name_rw VARCHAR(100) NULL,
    UNIQUE (province_id, name),
    INDEX idx_districts_province_id (province_id),
    FOREIGN KEY (province_id) REFERENCES provinces(id) ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS sectors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    district_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    name_en VARCHAR(100) NULL,
    name_rw VARCHAR(100) NULL,
    UNIQUE (district_id, name),
    INDEX idx_sectors_district_id (district_id),
    FOREIGN KEY (district_id) REFERENCES districts(id) ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS addresses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    province_id INT NOT NULL,
    district_id INT NOT NULL,
    sector_id INT NOT NULL,
    details VARCHAR(255) NULL,
    INDEX idx_addresses_province (province_id),
    INDEX idx_addresses_district (district_id),
    INDEX idx_addresses_sector (sector_id),
    FOREIGN KEY (province_id) REFERENCES provinces(id) ON DELETE RESTRICT,
    FOREIGN KEY (district_id) REFERENCES districts(id) ON DELETE RESTRICT,
    FOREIGN KEY (sector_id) REFERENCES sectors(id) ON DELETE RESTRICT
);

-- Optional: link addresses to existing tables (run only once you confirm columns don't already exist)
-- ALTER TABLE users ADD COLUMN address_id INT NULL, ADD CONSTRAINT fk_users_address FOREIGN KEY (address_id) REFERENCES addresses(id);
-- ALTER TABLE orders ADD COLUMN delivery_address_id INT NULL, ADD CONSTRAINT fk_orders_delivery_address FOREIGN KEY (delivery_address_id) REFERENCES addresses(id);

 -- FarmBridge AI Rwanda - Database Update Queries
-- Run these queries to update your existing database for the new payment and delivery system

-- 1. Update CROPS table to add harvest type fields
ALTER TABLE crops 
ADD COLUMN harvest_type ENUM('in_stock', 'future') DEFAULT 'in_stock' AFTER status,
ADD COLUMN estimated_harvest_date DATE NULL AFTER harvest_type;

-- 2. Update ORDERS table to add payment and delivery fields
ALTER TABLE orders 
ADD COLUMN delivery_option ENUM('buyer', 'farmer') DEFAULT 'buyer' AFTER total,
ADD COLUMN delivery_fee DECIMAL(10,2) DEFAULT 0.00 AFTER delivery_option,
ADD COLUMN delivery_status ENUM('pending', 'farmer_confirmed', 'out_for_delivery', 'delivered', 'completed') DEFAULT 'pending' AFTER delivery_fee,
ADD COLUMN escrow_status ENUM('pending', 'released', 'disputed') DEFAULT 'pending' AFTER delivery_status,
ADD COLUMN harvest_status ENUM('not_harvested', 'harvesting', 'harvested') DEFAULT 'not_harvested' AFTER escrow_status,
ADD COLUMN estimated_delivery_date DATE NULL AFTER harvest_status,
ADD COLUMN confirmation_buyer BOOLEAN DEFAULT FALSE AFTER estimated_delivery_date,
ADD COLUMN confirmation_farmer BOOLEAN DEFAULT FALSE AFTER confirmation_buyer,
ADD COLUMN dispute_flag BOOLEAN DEFAULT FALSE AFTER confirmation_farmer,
ADD COLUMN buyer_notes TEXT AFTER dispute_flag,
ADD COLUMN farmer_notes TEXT AFTER buyer_notes,
ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at;

-- 3. Update PAYMENTS table to add escrow fields
ALTER TABLE payments 
ADD COLUMN payment_type ENUM('escrow', 'release', 'refund') DEFAULT 'escrow' AFTER amount,
ADD COLUMN released_at TIMESTAMP NULL AFTER paid_at,
ADD COLUMN released_by INT NULL AFTER released_at,
ADD FOREIGN KEY (released_by) REFERENCES users(id);

-- 4. Create DISPUTES table
CREATE TABLE disputes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    raised_by INT NOT NULL,
    raised_by_role ENUM('buyer', 'farmer') NOT NULL,
    reason TEXT NOT NULL,
    status ENUM('open', 'under_review', 'resolved', 'closed') DEFAULT 'open',
    resolution TEXT,
    resolved_by INT NULL,
    resolved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (raised_by) REFERENCES users(id),
    FOREIGN KEY (resolved_by) REFERENCES users(id)
);

-- 5. Create ORDER_STATUS_HISTORY table
CREATE TABLE order_status_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    status VARCHAR(50) NOT NULL,
    notes TEXT,
    changed_by INT NOT NULL,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (changed_by) REFERENCES users(id)
);

-- 6. Update existing orders to have proper escrow status
UPDATE orders SET escrow_status = 'pending' WHERE status = 'pending';
UPDATE orders SET escrow_status = 'released' WHERE status = 'completed';

-- 7. Update existing payments to have proper payment type
UPDATE payments SET payment_type = 'escrow' WHERE status = 'success';