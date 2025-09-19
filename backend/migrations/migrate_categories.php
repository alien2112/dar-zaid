<?php
require_once __DIR__ . '/../config/database.php';

try {
    echo "Starting migration...\n";
    if (!isset($db)) {
        echo "Creating database connection...\n";
        $database = new Database();
        $db = $database->getConnection();
    }
    echo "Database connection established.\n";

    // Start transaction
    $db->beginTransaction();

    // 1. Ensure categories table exists
    $db->exec("CREATE TABLE IF NOT EXISTS categories (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 2. Add category_id column to books if it doesn't exist
    // First check if column exists
    $columnCheckStmt = $db->query("SHOW COLUMNS FROM books LIKE 'category_id'");
    $columnExists = $columnCheckStmt->rowCount() > 0;
    
    if (!$columnExists) {
        $db->exec("ALTER TABLE books ADD COLUMN category_id INT");
        echo "Added category_id column to books table.\n";
        
        // Add foreign key constraint
        $db->exec("ALTER TABLE books ADD CONSTRAINT fk_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL");
        echo "Added foreign key constraint.\n";
    } else {
        echo "category_id column already exists.\n";
    }

    // 3. Get distinct categories from books
    echo "Fetching existing categories...\n";
    $stmt = $db->query("SELECT DISTINCT category FROM books WHERE category IS NOT NULL AND category != ''");
    $existingCategories = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Found " . count($existingCategories) . " categories.\n";

    // 4. Insert categories and update books
    foreach ($existingCategories as $categoryName) {
        // Insert category if it doesn't exist
        $stmt = $db->prepare("INSERT IGNORE INTO categories (name) VALUES (:name)");
        $stmt->execute(['name' => $categoryName]);

        // Get category ID
        $stmt = $db->prepare("SELECT id FROM categories WHERE name = :name");
        $stmt->execute(['name' => $categoryName]);
        $categoryId = $stmt->fetchColumn();

        if ($categoryId) {
            // Update books with this category
            $stmt = $db->prepare("UPDATE books SET category_id = :category_id WHERE category = :category");
            $stmt->execute([
                'category_id' => $categoryId,
                'category' => $categoryName
            ]);
        }
    }

    // Commit transaction
    $db->commit();

    echo "Categories migration completed successfully.\n";
    echo "Migrated categories: " . implode(", ", $existingCategories) . "\n";

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo "Error during migration: " . $e->getMessage() . "\n";
    exit(1);
}
?>
