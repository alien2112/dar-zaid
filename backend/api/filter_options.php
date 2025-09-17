<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '*';
header('Access-Control-Allow-Origin: ' . $origin);
header('Vary: Origin');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

$method = $_SERVER['REQUEST_METHOD'];

// Ensure database connection exists
if (!isset($db) || !$db) {
    try {
        $database = new Database();
        $db = $database->getConnection();
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database connection failed'], JSON_UNESCAPED_UNICODE);
        exit();
    }
}

if ($method === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($method == 'GET') {
    try {
        $options = [];
        
        // Get categories
        $categorySql = "SELECT DISTINCT category FROM books WHERE category IS NOT NULL AND category != '' ORDER BY category ASC";
        $categoryStmt = $db->prepare($categorySql);
        $categoryStmt->execute();
        $categories = [];
        while ($row = $categoryStmt->fetch(PDO::FETCH_ASSOC)) {
            $categories[] = $row['category'];
        }
        $options['categories'] = $categories;
        
        // Get authors
        $authorSql = "SELECT DISTINCT author FROM books WHERE author IS NOT NULL AND author != '' ORDER BY author ASC";
        $authorStmt = $db->prepare($authorSql);
        $authorStmt->execute();
        $authors = [];
        while ($row = $authorStmt->fetch(PDO::FETCH_ASSOC)) {
            $authors[] = $row['author'];
        }
        $options['authors'] = $authors;
        
        // Get publishers (if publisher field exists)
        try {
            $publisherSql = "SELECT DISTINCT publisher FROM books WHERE publisher IS NOT NULL AND publisher != '' ORDER BY publisher ASC";
            $publisherStmt = $db->prepare($publisherSql);
            $publisherStmt->execute();
            $publishers = [];
            while ($row = $publisherStmt->fetch(PDO::FETCH_ASSOC)) {
                $publishers[] = $row['publisher'];
            }
            $options['publishers'] = $publishers;
        } catch (PDOException $e) {
            // Publisher field doesn't exist, return empty array
            $options['publishers'] = [];
        }
        
        // Get price range
        $priceSql = "SELECT MIN(price) as min_price, MAX(price) as max_price FROM books WHERE price > 0";
        $priceStmt = $db->prepare($priceSql);
        $priceStmt->execute();
        $priceRow = $priceStmt->fetch(PDO::FETCH_ASSOC);
        $options['priceRange'] = [
            'min' => (float)$priceRow['min_price'],
            'max' => (float)$priceRow['max_price']
        ];
        
        echo json_encode($options, JSON_UNESCAPED_UNICODE);
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
} else {
    http_response_code(405);
    echo json_encode(['message' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
}
?>
