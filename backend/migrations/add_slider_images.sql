-- Migration to add slider images table
-- Run this script to add slider image management functionality

-- Create slider_images table if it doesn't exist
CREATE TABLE IF NOT EXISTS slider_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    image_url TEXT NOT NULL,
    alt_text VARCHAR(255),
    link_url TEXT,
    description TEXT,
    display_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_display_order (display_order),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default slider images if they don't exist
INSERT IGNORE INTO slider_images (id, title, image_url, alt_text, display_order, is_active) VALUES
(1, 'ترحيب بالزوار', '/images/slider/1.jpg', 'مرحباً بكم في دار زيد للنشر والتوزيع', 1, TRUE),
(2, 'مجموعة الكتب', '/images/slider/2.jpg', 'اكتشف مجموعتنا الواسعة من الكتب', 2, TRUE),
(3, 'خدمات النشر', '/images/slider/3.jpg', 'نقدم أفضل خدمات النشر والتوزيع', 3, TRUE);