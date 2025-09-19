<?php
require_once __DIR__ . '/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Simulate the filter_options.php endpoint
    $options = [];
    
    // Get categories from the categories table
    $categorySql = "SELECT name FROM categories ORDER BY name ASC";
    $categoryStmt = $db->prepare($categorySql);
    $categoryStmt->execute();
    $categories = [];
    
    $categoryCount = $categoryStmt->rowCount();
    echo "Found $categoryCount categories in the categories table\n";
    
    while ($row = $categoryStmt->fetch(PDO::FETCH_ASSOC)) {
        $categories[] = $row['name'];
        echo "Category: " . $row['name'] . "\n";
    }
    
    // If no categories found, use fallback from books table
    if (empty($categories)) {
        echo "No categories found in categories table, using fallback\n";
        $fallbackSql = "SELECT DISTINCT category FROM books WHERE category IS NOT NULL AND category != '' ORDER BY category ASC";
        $fallbackStmt = $db->prepare($fallbackSql);
        $fallbackStmt->execute();
        while ($row = $fallbackStmt->fetch(PDO::FETCH_ASSOC)) {
            if (!empty($row['category'])) {
                $categories[] = $row['category'];
                echo "Fallback category: " . $row['category'] . "\n";
                
                // Also insert into categories table
                try {
                    $insertSql = "INSERT IGNORE INTO categories (name) VALUES (?)";
                    $insertStmt = $db->prepare($insertSql);
                    $insertStmt->execute([$row['category']]);
                    echo "Added category to categories table: " . $row['category'] . "\n";
                } catch (Exception $e) {
                    echo "Error adding category: " . $e->getMessage() . "\n";
                }
            }
        }
    }
    
    $options['categories'] = $categories;
    
    echo "\nFinal categories array:\n";
    print_r($options['categories']);
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
