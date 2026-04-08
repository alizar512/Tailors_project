USE tailor_db;

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

CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_name VARCHAR(100) NOT NULL,
    customer_email VARCHAR(100) NOT NULL,
    customer_phone VARCHAR(20),
    tailor_id INT,
    service_type VARCHAR(100),
    measurements TEXT,
    reference_image VARCHAR(255),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tailor_id) REFERENCES tailors(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS tailor_applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    profile_image VARCHAR(255),
    phone VARCHAR(20),
    location VARCHAR(100),
    address TEXT NOT NULL,
    experience_years INT,
    specialization VARCHAR(255),
    price_range_min DECIMAL(10,2),
    instagram_link VARCHAR(255),
    portfolio_link TEXT, -- Store multiple image paths as JSON
    portfolio_videos TEXT, -- Store multiple video paths as JSON
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Default Admin (password: admin123)
INSERT IGNORE INTO admins (username, email, password) VALUES ('admin', 'admin@silah.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('order', 'tailor', 'system') DEFAULT 'system',
    link VARCHAR(255),
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS tailor_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tailor_id INT NOT NULL,
    customer_name VARCHAR(100) NOT NULL,
    customer_email VARCHAR(100) NOT NULL,
    customer_phone VARCHAR(30),
    customer_address TEXT,
    message TEXT NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tailor_id) REFERENCES tailors(id) ON DELETE CASCADE
);


-- Insert Dummy Data for Tailors
INSERT INTO tailors (name, tagline, description, location, profile_image, price_range_min, price_range_max, experience_years) VALUES
('Ahmed Al-Farsi', 'Master of Bespoke Suits', 'Specializing in Italian cuts and premium fabrics.', 'Downtown, Dubai', 'images/stock/unsplash_1596609548086-85bbf8ddb6b9.jpg', 150.00, 500.00, 15),
('Sarah Jenkins', 'Elegant Bridal & Evening Wear', 'Turning your dream dress into reality with exquisite detail.', 'New York, USA', 'images/stock/unsplash_1524504388940-b1c1722653e1.jpg', 200.00, 1500.00, 10),
('Raj Patel', 'Traditional & Modern Fusion', 'Expert in ethnic wear and modern alterations.', 'London, UK', 'images/stock/unsplash_1607346256330-dee7af15f7c5.jpg', 50.00, 300.00, 20);

-- Insert Dummy Portfolio Images
INSERT INTO portfolio_images (tailor_id, image_url, description) VALUES
(1, 'images/stock/unsplash_1594938298603-c8148c4dae35.jpg', 'Custom Grey Suit'),
(1, 'images/stock/unsplash_1507679799987-c73779587ccf.jpg', 'Business Attire'),
(2, 'images/stock/unsplash_1595777457583-95e059d581b8.jpg', 'Evening Gown'),
(3, 'images/stock/unsplash_1585487000160-6ebcfceb0d03.jpg', 'Traditional Sherwani');

CREATE TABLE IF NOT EXISTS cities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    country VARCHAR(80) NOT NULL DEFAULT 'Pakistan',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_cities_name_country (name, country),
    INDEX idx_cities_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO cities (name, country, is_active) VALUES
('Karachi', 'Pakistan', 1),
('Lahore', 'Pakistan', 1),
('Islamabad', 'Pakistan', 1),
('Rawalpindi', 'Pakistan', 1),
('Faisalabad', 'Pakistan', 1),
('Multan', 'Pakistan', 1),
('Peshawar', 'Pakistan', 1),
('Quetta', 'Pakistan', 1),
('Sialkot', 'Pakistan', 1),
('Gujranwala', 'Pakistan', 1),
('Hyderabad', 'Pakistan', 1),
('Sukkur', 'Pakistan', 1),
('Bahawalpur', 'Pakistan', 1),
('Sargodha', 'Pakistan', 1),
('Gujrat', 'Pakistan', 1),
('Abbottabad', 'Pakistan', 1),
('Mardan', 'Pakistan', 1),
('Swat', 'Pakistan', 1),
('Dera Ghazi Khan', 'Pakistan', 1),
('Rahim Yar Khan', 'Pakistan', 1),
('Sheikhupura', 'Pakistan', 1),
('Okara', 'Pakistan', 1),
('Kasur', 'Pakistan', 1),
('Wah Cantt', 'Pakistan', 1),
('Jhelum', 'Pakistan', 1),
('Attock', 'Pakistan', 1),
('Chakwal', 'Pakistan', 1),
('Sahiwal', 'Pakistan', 1),
('Chiniot', 'Pakistan', 1),
('Jhang', 'Pakistan', 1),
('Nowshera', 'Pakistan', 1),
('Kohat', 'Pakistan', 1),
('Larkana', 'Pakistan', 1),
('Mirpur Khas', 'Pakistan', 1),
('Nawabshah', 'Pakistan', 1),
('Gwadar', 'Pakistan', 1),
('Khuzdar', 'Pakistan', 1);
