-- Add tables for comprehensive reporting system
USE dar_zaid_db;

-- Orders table for tracking book sales
CREATE TABLE IF NOT EXISTS orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    payment_method VARCHAR(50),
    payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
    shipping_address TEXT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Order items for tracking individual book sales
CREATE TABLE IF NOT EXISTS order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    book_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    price_per_item DECIMAL(10, 2) NOT NULL,
    total_price DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
);

-- Package purchases table
CREATE TABLE IF NOT EXISTS package_purchases (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    package_id INT NOT NULL,
    amount_paid DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'active', 'completed', 'cancelled') DEFAULT 'pending',
    start_date DATE,
    completion_date DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (package_id) REFERENCES publishing_packages(id) ON DELETE CASCADE
);

-- Add image slider table
CREATE TABLE IF NOT EXISTS slider_images (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255),
    subtitle VARCHAR(255),
    image_url VARCHAR(500) NOT NULL,
    link_url VARCHAR(500),
    button_text VARCHAR(100),
    display_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Add book of the week functionality
ALTER TABLE books
ADD COLUMN is_book_of_week BOOLEAN DEFAULT FALSE;

-- Add is_chosen column if it doesn't exist
ALTER TABLE books
ADD COLUMN IF NOT EXISTS is_chosen BOOLEAN DEFAULT FALSE;

-- Create indexes for better performance
CREATE INDEX idx_orders_user_id ON orders (user_id);
CREATE INDEX idx_orders_status ON orders (status);
CREATE INDEX idx_orders_created_at ON orders (created_at);
CREATE INDEX idx_order_items_order_id ON order_items (order_id);
CREATE INDEX idx_order_items_book_id ON order_items (book_id);
CREATE INDEX idx_package_purchases_user_id ON package_purchases (user_id);
CREATE INDEX idx_package_purchases_package_id ON package_purchases (package_id);
CREATE INDEX idx_package_purchases_status ON package_purchases (status);
CREATE INDEX idx_slider_images_active ON slider_images (is_active, display_order);
CREATE INDEX idx_books_book_of_week ON books (is_book_of_week);

-- Insert sample slider images
INSERT INTO slider_images (title, subtitle, image_url, link_url, button_text, display_order) VALUES
('مرحباً بكم في دار زيد', 'اكتشف عالماً من المعرفة والإبداع', 'https://placehold.co/1920x600/1e3a8a/ffffff?text=دار+زيد+للنشر', '/bookstore', 'تصفح الكتب', 1),
('أحدث الإصدارات', 'كتب جديدة تصل يومياً', 'https://placehold.co/1920x600/2563eb/ffffff?text=أحدث+الإصدارات', '/bookstore?filter=recent', 'اكتشف الجديد', 2),
('عروض خاصة', 'خصومات تصل إلى 50%', 'https://placehold.co/1920x600/dc2626/ffffff?text=عروض+خاصة', '/bookstore?filter=discounted', 'تسوق الآن', 3);

-- Insert sample orders for testing (assuming we have users and books)
-- Note: This will only work if there are existing users and books
INSERT IGNORE INTO orders (user_id, order_number, total_amount, status, payment_status) VALUES
(1, 'ORD-2024-001', 150.00, 'delivered', 'paid'),
(1, 'ORD-2024-002', 85.50, 'delivered', 'paid'),
(2, 'ORD-2024-003', 220.00, 'shipped', 'paid'),
(3, 'ORD-2024-004', 95.00, 'processing', 'paid');

-- Insert sample order items
INSERT IGNORE INTO order_items (order_id, book_id, quantity, price_per_item, total_price) VALUES
(1, 1, 2, 45.00, 90.00),
(1, 2, 1, 60.00, 60.00),
(2, 3, 1, 55.00, 55.00),
(2, 4, 1, 30.50, 30.50);

-- Insert sample package purchases
INSERT IGNORE INTO package_purchases (user_id, package_id, amount_paid, status) VALUES
(1, 1, 500.00, 'active'),
(2, 2, 750.00, 'completed'),
(3, 1, 500.00, 'pending');