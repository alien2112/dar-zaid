<?php
require_once __DIR__ . '/config/database.php';

header('Content-Type: application/json');
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '*';
header('Access-Control-Allow-Origin: ' . $origin);
header('Vary: Origin');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { 
    http_response_code(200); 
    exit(); 
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get book of the week with detailed info
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
        $book = [
            'featured_id' => (int)$result['featured_id'],
            'id' => (int)$result['id'],
            'title' => $result['title'],
            'author' => $result['author'],
            'price' => (float)$result['price'],
            'image_url' => $result['image_url'],
            'description' => $result['description'],
            'category_id' => (int)$result['category_id'],
            'category_name' => $result['category_name'],
            'stock_quantity' => (int)$result['stock_quantity'],
            'is_featured' => (bool)$result['is_featured'],
            'featured_type' => $result['featured_type'],
            'start_date' => $result['start_date'],
            'end_date' => $result['end_date']
        ];
        
        echo json_encode([
            'book_of_week' => $book,
            'debug_info' => [
                'raw_stock_quantity' => $result['stock_quantity'],
                'stock_quantity_type' => gettype($result['stock_quantity']),
                'stock_quantity_int' => (int)$result['stock_quantity'],
                'is_greater_than_zero' => (int)$result['stock_quantity'] > 0
            ]
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(['book_of_week' => null], JSON_UNESCAPED_UNICODE);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>

