-- FarmBridge AI Rwanda Database Schema

-- USERS TABLE
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE,
    phone VARCHAR(20) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('farmer', 'buyer', 'admin') NOT NULL DEFAULT 'farmer',
    profile_pic VARCHAR(255), -- NEW: profile picture path, nullable
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- CROPS TABLE
CREATE TABLE crops (
    id INT AUTO_INCREMENT PRIMARY KEY,
    farmer_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    quantity INT NOT NULL,
    unit VARCHAR(20) DEFAULT 'kg',
    price DECIMAL(10,2) NOT NULL,
    image VARCHAR(255), -- NEW: crop image path, nullable
    status ENUM('available', 'sold', 'pending') DEFAULT 'available',
    harvest_type ENUM('in_stock', 'future') DEFAULT 'in_stock', -- NEW: harvest type
    estimated_harvest_date DATE NULL, -- NEW: estimated harvest date for future crops
    listed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (farmer_id) REFERENCES users(id)
);

-- ORDERS TABLE (UPDATED with payment and delivery fields)
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    buyer_id INT NOT NULL,
    crop_id INT NOT NULL,
    quantity INT NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    delivery_option ENUM('buyer', 'farmer') DEFAULT 'buyer', -- NEW: who handles delivery
    delivery_fee DECIMAL(10,2) DEFAULT 0.00, -- NEW: delivery fee if farmer handles
    delivery_status ENUM('pending', 'farmer_confirmed', 'out_for_delivery', 'delivered', 'completed') DEFAULT 'pending', -- NEW: delivery status
    escrow_status ENUM('pending', 'released', 'disputed') DEFAULT 'pending', -- NEW: escrow status
    harvest_status ENUM('not_harvested', 'harvesting', 'harvested') DEFAULT 'not_harvested', -- NEW: harvest status
    estimated_delivery_date DATE NULL, -- NEW: estimated delivery date
    confirmation_buyer BOOLEAN DEFAULT FALSE, -- NEW: buyer confirms delivery
    confirmation_farmer BOOLEAN DEFAULT FALSE, -- NEW: farmer confirms delivery
    dispute_flag BOOLEAN DEFAULT FALSE, -- NEW: dispute flag
    buyer_notes TEXT, -- NEW: buyer notes during checkout
    farmer_notes TEXT, -- NEW: farmer notes
    status ENUM('pending', 'paid', 'cancelled', 'completed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (buyer_id) REFERENCES users(id),
    FOREIGN KEY (crop_id) REFERENCES crops(id)
);

-- PAYMENTS TABLE (UPDATED for escrow system)
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    momo_ref VARCHAR(100),
    amount DECIMAL(10,2) NOT NULL,
    payment_type ENUM('escrow', 'release', 'refund') DEFAULT 'escrow', -- NEW: payment type
    status ENUM('pending', 'success', 'failed', 'released') DEFAULT 'pending', -- NEW: added 'released' status
    paid_at TIMESTAMP NULL,
    released_at TIMESTAMP NULL, -- NEW: when escrow was released
    released_by INT NULL, -- NEW: who released the payment (admin_id)
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (released_by) REFERENCES users(id)
);

-- CROP SALES TABLE (for AI data collection)
CREATE TABLE crop_sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    crop_id INT NOT NULL,
    farmer_id INT NOT NULL,
    buyer_id INT NOT NULL,
    quantity INT NOT NULL,
    location VARCHAR(100),
    sale_date DATE,
    price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (crop_id) REFERENCES crops(id),
    FOREIGN KEY (farmer_id) REFERENCES users(id),
    FOREIGN KEY (buyer_id) REFERENCES users(id)
);

-- MARKET PRICES TABLE (for external and local market price data)
CREATE TABLE market_prices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    commodity VARCHAR(100),
    market VARCHAR(100),
    price DECIMAL(10,2),
    date DATE,
    source VARCHAR(100)
);

-- DISPUTES TABLE (NEW: for handling order disputes)
CREATE TABLE disputes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    raised_by INT NOT NULL, -- buyer_id or farmer_id
    raised_by_role ENUM('buyer', 'farmer') NOT NULL,
    reason TEXT NOT NULL,
    status ENUM('open', 'under_review', 'resolved', 'closed') DEFAULT 'open',
    resolution TEXT,
    resolved_by INT NULL, -- admin_id
    resolved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (raised_by) REFERENCES users(id),
    FOREIGN KEY (resolved_by) REFERENCES users(id)
);

-- ORDER STATUS HISTORY TABLE (NEW: for tracking order status changes)
CREATE TABLE order_status_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    status VARCHAR(50) NOT NULL,
    notes TEXT,
    changed_by INT NOT NULL, -- user_id who made the change
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (changed_by) REFERENCES users(id)
);

-- DEMAND FORECAST TABLE (for AI-predicted demand)
CREATE TABLE demand_forecast (
    id INT AUTO_INCREMENT PRIMARY KEY,
    crop_name VARCHAR(100),
    forecast_value INT,
    period VARCHAR(50), -- e.g., 'next_week', 'next_month'
    date_generated DATE
); 