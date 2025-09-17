-- Add dynamic categories table for homepage widgets
USE dar_zaid_db;

CREATE TABLE IF NOT EXISTS dynamic_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    type ENUM(
        'top_sellers',
        'recent_releases',
        'discounted',
        'featured',
        'category_based',
        'author_collection',
        'custom'
    ) NOT NULL,
    filter_criteria JSON, -- Store complex filtering rules
    display_order INT DEFAULT 0,
    max_items INT DEFAULT 4,
    is_active BOOLEAN DEFAULT TRUE,
    widget_style ENUM('grid', 'carousel', 'list', 'banner') DEFAULT 'grid',
    show_on_homepage BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Add index for performance
CREATE INDEX idx_dynamic_categories_active ON dynamic_categories (is_active, display_order);
CREATE INDEX idx_dynamic_categories_type ON dynamic_categories (type);

-- Insert sample dynamic categories based on the reference website
INSERT INTO dynamic_categories (name, slug, type, filter_criteria, display_order, max_items, widget_style, description) VALUES
('الأكثر مبيعاً', 'top-sellers', 'top_sellers',
 JSON_OBJECT('sort_by', 'sales_count', 'order', 'desc', 'min_stock', 1),
 1, 8, 'grid', 'أكثر الكتب مبيعاً في المتجر'),

('أحدث الإصدارات', 'recent-releases', 'recent_releases',
 JSON_OBJECT('sort_by', 'created_at', 'order', 'desc', 'days_limit', 30),
 2, 6, 'grid', 'أحدث الكتب المضافة للمتجر'),

('عروض وخصومات', 'discounted', 'discounted',
 JSON_OBJECT('has_discount', true, 'discount_min', 10),
 3, 6, 'grid', 'كتب بأسعار مخفضة'),

('مختارات المحررين', 'featured', 'featured',
 JSON_OBJECT('featured', true),
 4, 4, 'banner', 'اختيارات فريق التحرير'),

('كتب الأطفال', 'children-books', 'category_based',
 JSON_OBJECT('category', 'أطفال'),
 5, 8, 'grid', 'كتب مخصصة للأطفال'),

('كتب الطبخ', 'cooking-books', 'category_based',
 JSON_OBJECT('category', 'طبخ'),
 6, 6, 'grid', 'كتب فنون الطبخ والطعام'),

('العلوم والتكنولوجيا', 'science-tech', 'category_based',
 JSON_OBJECT('category', 'علوم'),
 7, 6, 'grid', 'كتب العلوم والتقنية');

-- Add sales tracking to books table
ALTER TABLE books
ADD COLUMN sales_count INT DEFAULT 0,
ADD COLUMN is_featured BOOLEAN DEFAULT FALSE,
ADD COLUMN discount_percentage DECIMAL(5,2) DEFAULT 0.00,
ADD COLUMN original_price DECIMAL(10,2) NULL;

-- Add indexes for better performance on dynamic queries
CREATE INDEX idx_books_sales_count ON books (sales_count DESC);
CREATE INDEX idx_books_featured ON books (is_featured);
CREATE INDEX idx_books_discount ON books (discount_percentage);
CREATE INDEX idx_books_category_stock ON books (category, stock_quantity);