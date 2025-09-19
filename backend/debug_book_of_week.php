<?php
require_once __DIR__ . '/config/database.php';

header('Content-Type: application/json');
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : 'http://localhost:3000';
header('Access-Control-Allow-Origin: ' . $origin);
header('Vary: Origin');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { 
    http_response_code(200); 
    exit(); 
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "=== DEBUGGING BOOK OF THE WEEK ===\n\n";
    
    // Check if featured_book table exists
    $tables = $db->query("SHOW TABLES LIKE 'featured_book'")->fetchAll();
    if (empty($tables)) {
        echo "❌ featured_book table does not exist!\n";
        echo "Creating featured_book table...\n";
        
        $db->exec("CREATE TABLE IF NOT EXISTS featured_book (
            id INT AUTO_INCREMENT PRIMARY KEY,
            book_id INT NOT NULL,
            featured_type ENUM('book_of_week', 'featured', 'bestseller') DEFAULT 'book_of_week',
            start_date DATE DEFAULT (CURRENT_DATE),
            end_date DATE NULL,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_featured_book FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        echo "✅ featured_book table created!\n\n";
    } else {
        echo "✅ featured_book table exists!\n\n";
    }
    
    // Check current book of the week
    echo "=== CURRENT BOOK OF THE WEEK ===\n";
    $query = "SELECT
                fb.id as featured_id,
                fb.featured_type,
                fb.start_date,
                fb.end_date,
                fb.is_active,
                b.id,
                b.title,
                b.author,
                b.price,
                b.image_url,
                b.description,
                b.category_id,
                b.stock_quantity,
                b.is_featured,
                COALESCE(c.name, 'غير مصنف') as category_name
              FROM featured_book fb
              JOIN books b ON fb.book_id = b.id
              LEFT JOIN categories c ON b.category_id = c.id
              WHERE fb.featured_type = 'book_of_week'
              AND fb.is_active = TRUE
              AND (fb.end_date IS NULL OR fb.end_date >= CURDATE())
              ORDER BY fb.created_at DESC
              LIMIT 1";

    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        echo "✅ Found book of the week:\n";
        echo "  - ID: " . $result['id'] . "\n";
        echo "  - Title: " . $result['title'] . "\n";
        echo "  - Author: " . $result['author'] . "\n";
        echo "  - Price: " . $result['price'] . "\n";
        echo "  - Stock Quantity: " . $result['stock_quantity'] . "\n";
        echo "  - Category: " . $result['category_name'] . "\n";
        echo "  - Is Active: " . ($result['is_active'] ? 'Yes' : 'No') . "\n";
        echo "  - Start Date: " . $result['start_date'] . "\n";
        echo "  - End Date: " . ($result['end_date'] ?: 'NULL') . "\n";
    } else {
        echo "❌ No active book of the week found!\n";
        
        // Check if there are any books at all
        $booksQuery = "SELECT id, title, author, price, stock_quantity FROM books LIMIT 5";
        $booksStmt = $db->query($booksQuery);
        $books = $booksStmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "\n=== AVAILABLE BOOKS ===\n";
        if (empty($books)) {
            echo "❌ No books found in database!\n";
        } else {
            echo "Found " . count($books) . " books:\n";
            foreach ($books as $book) {
                echo "  - ID: " . $book['id'] . ", Title: " . $book['title'] . 
                     ", Author: " . $book['author'] . ", Price: " . $book['price'] . 
                     ", Stock: " . $book['stock_quantity'] . "\n";
            }
        }
    }
    
    // Check all featured_book entries
    echo "\n=== ALL FEATURED BOOK ENTRIES ===\n";
    $allFeaturedQuery = "SELECT fb.*, b.title, b.stock_quantity 
                        FROM featured_book fb 
                        LEFT JOIN books b ON fb.book_id = b.id 
                        ORDER BY fb.created_at DESC";
    $allFeaturedStmt = $db->query($allFeaturedQuery);
    $allFeatured = $allFeaturedStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($allFeatured)) {
        echo "❌ No featured book entries found!\n";
    } else {
        foreach ($allFeatured as $entry) {
            echo "  - Featured ID: " . $entry['id'] . 
                 ", Book ID: " . $entry['book_id'] . 
                 ", Title: " . ($entry['title'] ?: 'BOOK NOT FOUND') . 
                 ", Type: " . $entry['featured_type'] . 
                 ", Active: " . ($entry['is_active'] ? 'Yes' : 'No') . 
                 ", Stock: " . ($entry['stock_quantity'] ?: 'N/A') . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>

