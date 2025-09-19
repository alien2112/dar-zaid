<?php
require_once __DIR__ . '/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check categories table
    $stmt = $db->query("SELECT * FROM categories");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Categories in the categories table:\n";
    foreach ($categories as $category) {
        echo "ID: " . $category['id'] . ", Name: " . $category['name'] . "\n";
    }
    
    // Check if books have category_id set
    $stmt = $db->query("SELECT id, title, category, category_id FROM books LIMIT 10");
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nBooks with their category information:\n";
    foreach ($books as $book) {
        echo "Book ID: " . $book['id'] . ", Title: " . $book['title'] . 
             ", Category: " . $book['category'] . ", Category ID: " . 
             ($book['category_id'] ? $book['category_id'] : 'NULL') . "\n";
    }
    
    // Update books that have category but no category_id
    // Use COLLATE to handle character set issues
    $stmt = $db->query("SELECT b.id, b.category, c.id as cat_id 
                       FROM books b 
                       JOIN categories c ON b.category COLLATE utf8mb4_general_ci = c.name COLLATE utf8mb4_general_ci
                       WHERE b.category_id IS NULL");
    $booksToUpdate = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nBooks that need category_id update:\n";
    foreach ($booksToUpdate as $book) {
        echo "Book ID: " . $book['id'] . ", Category: " . $book['category'] . 
             ", Will set category_id to: " . $book['cat_id'] . "\n";
        
        $updateStmt = $db->prepare("UPDATE books SET category_id = ? WHERE id = ?");
        $updateStmt->execute([$book['cat_id'], $book['id']]);
    }
    
    echo "\nUpdate completed.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
