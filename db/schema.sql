CREATE DATABASE IF NOT EXISTS tailor_db;
USE tailor_db;

CREATE TABLE IF NOT EXISTS services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2),
    image_url VARCHAR(255),
    category VARCHAR(50) -- e.g., 'men', 'women', 'bridal', 'alteration'
);

CREATE TABLE IF NOT EXISTS testimonials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_name VARCHAR(100) NOT NULL,
    rating INT DEFAULT 5,
    review TEXT,
    image_url VARCHAR(255)
);

CREATE TABLE IF NOT EXISTS contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
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
    portfolio_link TEXT,
    portfolio_videos TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS tailors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    username VARCHAR(50) UNIQUE,
    tagline VARCHAR(255),
    description TEXT,
    location VARCHAR(100),
    address TEXT,
    skills TEXT,
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

CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('order', 'tailor', 'system') DEFAULT 'system',
    link VARCHAR(255),
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert Dummy Data for Services
INSERT INTO services (title, description, price, image_url, category) VALUES
('Men''s Custom Suits', 'Bespoke suits tailored to perfection with premium fabrics.', 80.00, 'assets/men-suit.jpg', 'men'),
('Women''s Designer Dresses', 'Elegant and stylish dresses for every occasion.', 45.00, 'assets/women-dress.jpg', 'women'),
('Bridal Wear Stitching', 'Exquisite bridal gowns with intricate detailing.', 150.00, 'assets/bridal.jpg', 'bridal'),
('Alterations & Adjustments', 'Perfect fit adjustments for your existing wardrobe.', 15.00, 'assets/alterations.jpg', 'alteration'),
('Uniform Stitching', 'Durable and professional uniforms for schools and offices.', 30.00, 'assets/uniform.jpg', 'uniform'),
('Kids Wear', 'Comfortable and trendy outfits for the little ones.', 25.00, 'assets/kids.jpg', 'kids');

-- Insert Dummy Data for Testimonials
INSERT INTO testimonials (customer_name, rating, review, image_url) VALUES
('John Doe', 5, 'Absolutely the best tailoring service I have ever used. The fit is perfect!', 'assets/client1.jpg'),
('Sarah Smith', 5, 'My wedding dress was a dream come true. Thank you for the amazing work!', 'assets/client2.jpg'),
('Michael Brown', 4, 'Great service and quick turnaround. Highly recommended.', 'assets/client3.jpg');
