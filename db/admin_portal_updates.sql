USE tailor_db;

-- Enhance Orders table for the 30% advance flow
ALTER TABLE orders 
ADD COLUMN budget DECIMAL(10,2) AFTER service_type,
ADD COLUMN location_details TEXT AFTER budget,
ADD COLUMN expected_delivery DATE AFTER location_details,
ADD COLUMN preferred_tailors TEXT AFTER tailor_id, -- Store as JSON
ADD COLUMN total_price DECIMAL(10,2) DEFAULT 0.00,
ADD COLUMN advance_payment_amount DECIMAL(10,2) DEFAULT 0.00,
ADD COLUMN payment_status ENUM('Pending', '30% Paid', 'Fully Paid') DEFAULT 'Pending',
ADD COLUMN status ENUM('Order Placed', 'Under Review', 'Price Updated', 'Tailor Selected', 'In Progress', 'Completed') DEFAULT 'Order Placed' AFTER notes;

-- Create Payments table
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    amount DECIMAL(10,2) NOT NULL,
    payment_type ENUM('Advance', 'Balance', 'Full') NOT NULL,
    transaction_id VARCHAR(100),
    payment_method VARCHAR(50),
    status ENUM('Pending', 'Completed', 'Failed') DEFAULT 'Completed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

-- Create Notifications table
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_type ENUM('Customer', 'Tailor', 'Admin') NOT NULL,
    user_email VARCHAR(100), -- Identify recipient
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
