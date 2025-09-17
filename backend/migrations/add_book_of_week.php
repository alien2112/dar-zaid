<?php
require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    // Check if featured_book table exists
    $result = $db->query('SHOW TABLES LIKE "featured_book"');

    if ($result->rowCount() > 0) {
        echo "Table featured_book already exists\n";
    } else {
        echo "Creating featured_book table...\n";

        // Create the table for book of the week
        $createTable = "CREATE TABLE featured_book (
            id INT AUTO_INCREMENT PRIMARY KEY,
            book_id INT NOT NULL,
            featured_type ENUM('book_of_week', 'featured', 'special') DEFAULT 'book_of_week',
            start_date DATE NOT NULL,
            end_date DATE NULL,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
            INDEX idx_book_id (book_id),
            INDEX idx_featured_type (featured_type),
            INDEX idx_is_active (is_active),
            INDEX idx_start_date (start_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $db->exec($createTable);
        echo "Table created successfully\n";
    }

    // Check if we need to add is_featured column to books table
    $result = $db->query("SHOW COLUMNS FROM books LIKE 'is_featured'");

    if ($result->rowCount() == 0) {
        echo "Adding is_featured column to books table...\n";
        $db->exec("ALTER TABLE books ADD COLUMN is_featured BOOLEAN DEFAULT FALSE");
        echo "Column added successfully\n";
    } else {
        echo "is_featured column already exists in books table\n";
    }

    echo "Book of the Week migration completed successfully\n";

} catch(PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
?>