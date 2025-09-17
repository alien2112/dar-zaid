<?php
require_once '../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . (isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '*'));
header('Vary: Origin');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'OPTIONS') { http_response_code(200); exit(); }
if ($method !== 'POST') { http_response_code(405); echo json_encode(['error' => 'Method not allowed']); exit(); }

$database = new Database();
$db = $database->getConnection();

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data) || !isset($data['rows']) || !is_array($data['rows'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid payload. Expect { rows: [...] }']);
    exit();
}

$rows = $data['rows'];
$inserted = 0;
$errors = [];

$stmt = $db->prepare('INSERT INTO books (title, author, description, price, category, image_url, isbn, published_date, stock_quantity) VALUES (:title, :author, :description, :price, :category, :image_url, :isbn, :published_date, :stock_quantity)');

foreach ($rows as $i => $row) {
    $title = trim($row['title'] ?? '');
    $author = trim($row['author'] ?? '');
    $price = isset($row['price']) ? floatval($row['price']) : null;
    if ($title === '' || $author === '' || $price === null) {
        $errors[] = ['index' => $i, 'error' => 'Missing required fields (title, author, price)'];
        continue;
    }
    try {
        $stmt->execute([
            ':title' => $title,
            ':author' => $author,
            ':description' => $row['description'] ?? '',
            ':price' => $price,
            ':category' => $row['category'] ?? null,
            ':image_url' => $row['image_url'] ?? null,
            ':isbn' => $row['isbn'] ?? null,
            ':published_date' => $row['published_date'] ?? null,
            ':stock_quantity' => isset($row['stock_quantity']) ? intval($row['stock_quantity']) : 0,
        ]);
        $inserted++;
    } catch (PDOException $e) {
        $errors[] = ['index' => $i, 'error' => $e->getMessage()];
    }
}

echo json_encode(['success' => true, 'inserted' => $inserted, 'errors' => $errors], JSON_UNESCAPED_UNICODE);

?>


