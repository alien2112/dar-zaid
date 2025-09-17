<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if books table exists and has data
    $stmt = $db->query("SELECT COUNT(*) as count FROM books");
    $bookCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "Total books in database: " . $bookCount . "\n";
    
    // Get all books with their categories
    $stmt = $db->query("SELECT id, title, category FROM books ORDER BY id");
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Books in database:\n";
    foreach ($books as $book) {
        echo "ID: {$book['id']}, Title: {$book['title']}, Category: {$book['category']}\n";
    }
    
    // Get distinct categories
    $stmt = $db->query("SELECT DISTINCT category FROM books WHERE category IS NOT NULL AND category != '' ORDER BY category ASC");
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Distinct categories: " . json_encode($categories, JSON_UNESCAPED_UNICODE) . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
