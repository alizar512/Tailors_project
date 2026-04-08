-- Silah Tailors Project: Full Deployment Schema
-- Run this on your Railway MySQL instance

-- 1. Base Tables
CREATE TABLE IF NOT EXISTS tailors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    username VARCHAR(50) UNIQUE,
    tagline VARCHAR(255),
    description TEXT,
    location VARCHAR(100),
    address TEXT,
    skills TEXT,
    instagram_link VARCHAR(255),
    profile_image VARCHAR(255),
    phone VARCHAR(20),
    email VARCHAR(100),
    password VARCHAR(255),
    password_reset_required TINYINT(1) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    profile_completed TINYINT(1) NOT NULL DEFAULT 0,
    price_range_min DECIMAL(10,2),
    price_range_max DECIMAL(10,2),
    experience_years INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_name VARCHAR(100) NOT NULL,
    customer_email VARCHAR(100) NOT NULL,
    customer_phone VARCHAR(20),
    tailor_id INT,
    service_type VARCHAR(100),
    service_items TEXT,
    budget DECIMAL(10,2),
    total_price DECIMAL(10,2) DEFAULT 0.00,
    advance_payment_amount DECIMAL(10,2) DEFAULT 0.00,
    location_details TEXT,
    expected_delivery VARCHAR(50),
    measurements TEXT,
    reference_image VARCHAR(255),
    notes TEXT,
    status VARCHAR(50) DEFAULT 'Order Placed',
    payment_status VARCHAR(30) DEFAULT 'Pending',
    payment_proof_image VARCHAR(255),
    payment_submitted_at TIMESTAMP NULL,
    payment_confirmed_at TIMESTAMP NULL,
    balance_payment_status VARCHAR(30) DEFAULT 'Pending',
    balance_payment_proof_image VARCHAR(255),
    balance_payment_submitted_at TIMESTAMP NULL,
    balance_payment_confirmed_at TIMESTAMP NULL,
    chat_token VARCHAR(64),
    order_number VARCHAR(20),
    tailor_offer_price DECIMAL(10,2),
    tailor_offer_notes TEXT,
    is_own_clothing TINYINT(1) DEFAULT 0,
    cargo_company VARCHAR(100) DEFAULT NULL,
    cargo_tracking_number VARCHAR(100) DEFAULT NULL,
    cargo_receipt_image VARCHAR(255) DEFAULT NULL,
    shipped_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tailor_id) REFERENCES tailors(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS portfolio_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tailor_id INT,
    image_url VARCHAR(255) NOT NULL,
    description VARCHAR(255),
    FOREIGN KEY (tailor_id) REFERENCES tailors(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS portfolio_videos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tailor_id INT,
    video_url VARCHAR(255) NOT NULL,
    description VARCHAR(255),
    FOREIGN KEY (tailor_id) REFERENCES tailors(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_type ENUM('Customer', 'Tailor', 'Admin') NOT NULL,
    user_email VARCHAR(100),
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('order', 'tailor', 'system') DEFAULT 'system',
    link VARCHAR(255),
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS order_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    sender_type ENUM('customer', 'tailor', 'admin') NOT NULL,
    sender_name VARCHAR(100),
    sender_email VARCHAR(100),
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS cities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    country VARCHAR(80) NOT NULL DEFAULT 'Pakistan',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_cities_name_country (name, country)
);

-- 2. Initial Data
INSERT IGNORE INTO admins (username, email, password) VALUES ('admin', 'admin@silah.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

INSERT IGNORE INTO cities (name, country, is_active) VALUES
('Karachi', 'Pakistan', 1), ('Lahore', 'Pakistan', 1), ('Islamabad', 'Pakistan', 1), 
('Rawalpindi', 'Pakistan', 1), ('Faisalabad', 'Pakistan', 1), ('Multan', 'Pakistan', 1), 
('Peshawar', 'Pakistan', 1), ('Quetta', 'Pakistan', 1), ('Sialkot', 'Pakistan', 1);

INSERT IGNORE INTO tailors (name, tagline, description, location, experience_years, is_active) VALUES
('Ahmed Al-Farsi', 'Master of Bespoke Suits', 'Specializing in Italian cuts.', 'Karachi', 15, 1),
('Sarah Jenkins', 'Elegant Bridal Wear', 'Dream dresses with detail.', 'Lahore', 10, 1);
