<?php
require_once '../config/database.php';

header('Content-Type: application/json');
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '*';
header('Access-Control-Allow-Origin: ' . $origin);
header('Vary: Origin');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($method == 'GET') {
    try {
        if (isset($_GET['chosen'])) {
            $sql = "SELECT * FROM books WHERE is_chosen = TRUE ORDER BY created_at DESC";
            $stmt = $db->prepare($sql);
            $stmt->execute();
            $items = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $items[] = [
                    'id' => (int)$row['id'],
                    'title' => $row['title'],
                    'author' => $row['author'],
                    'description' => $row['description'],
                    'price' => (float)$row['price'],
                    'category' => $row['category'],
                    'image_url' => $row['image_url'] ?? '/images/book-placeholder.jpg',
                    'isbn' => $row['isbn'],
                    'stock_quantity' => (int)$row['stock_quantity'],
                    'is_chosen' => (bool)$row['is_chosen'],
                ];
            }
            echo json_encode([
                'items' => $items,
            ], JSON_UNESCAPED_UNICODE);
            exit();
        }
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = isset($_GET['limit']) ? max(1, min(100, intval($_GET['limit']))) : 12;
        $offset = ($page - 1) * $limit;
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $category = isset($_GET['category']) ? trim($_GET['category']) : '';

        $where = [];
        $params = [];
        if ($search !== '') {
            $where[] = '(LOWER(title) LIKE :search OR LOWER(author) LIKE :search OR isbn LIKE :searchExact)';
            $params['search'] = '%' . strtolower($search) . '%';
            $params['searchExact'] = '%' . $search . '%';
        }
        if ($category !== '') {
            $where[] = 'category = :category';
            $params['category'] = $category;
        }
        $whereSql = count($where) ? ('WHERE ' . implode(' AND ', $where)) : '';

        // Count total
        $countSql = "SELECT COUNT(*) as total FROM books $whereSql";
        $countStmt = $db->prepare($countSql);
        $countStmt->execute($params);
        $total = (int)$countStmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Fetch page
        $sql = "SELECT * FROM books $whereSql ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue(':' . $k, $v);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $items = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $items[] = [
                'id' => (int)$row['id'],
                'title' => $row['title'],
                'author' => $row['author'],
                'description' => $row['description'],
                'price' => (float)$row['price'],
                'category' => $row['category'],
                'image_url' => $row['image_url'] ?? '/images/book-placeholder.jpg',
                'isbn' => $row['isbn'],
                'stock_quantity' => (int)$row['stock_quantity'],
                'is_chosen' => (bool)$row['is_chosen'],
            ];
        }

        echo json_encode([
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'limit' => $limit
        ], JSON_UNESCAPED_UNICODE);
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
} elseif ($method === 'POST') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data || empty($data['title']) || empty($data['author']) || !isset($data['price'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            exit();
        }

        $sql = 'INSERT INTO books (title, author, description, price, category, image_url, stock_quantity, isbn, published_date, is_chosen) 
                VALUES (:title, :author, :description, :price, :category, :image_url, :stock_quantity, :isbn, :published_date, :is_chosen)';
        $stmt = $db->prepare($sql);
        $stmt->execute([
            'title' => $data['title'],
            'author' => $data['author'],
            'description' => $data['description'] ?? '',
            'price' => (float)$data['price'],
            'category' => $data['category'] ?? null,
            'image_url' => $data['image_url'] ?? null,
            'stock_quantity' => isset($data['stock_quantity']) ? (int)$data['stock_quantity'] : 0,
            'isbn' => $data['isbn'] ?? null,
            'published_date' => $data['published_date'] ?? null,
            'is_chosen' => isset($data['is_chosen']) ? (bool)$data['is_chosen'] : false,
        ]);
        $id = (int)$db->lastInsertId();

        http_response_code(201);
        echo json_encode(['id' => $id] + $data, JSON_UNESCAPED_UNICODE);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
} elseif ($method === 'PUT') {
    $request_uri = $_SERVER['REQUEST_URI'];
    $id = (int)basename($request_uri);
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid book ID']);
        exit();
    }
    try {
        $data = json_decode(file_get_contents('php://input'), true) ?: [];
        $fields = [];
        $params = ['id' => $id];
        foreach (['title','author','description','price','category','image_url','stock_quantity','isbn','published_date', 'is_chosen'] as $f) {
            if (array_key_exists($f, $data)) {
                $fields[] = "$f = :$f";
                $params[$f] = $f === 'price' ? (float)$data[$f] : ($f === 'stock_quantity' ? (int)$data[$f] : ($f === 'is_chosen' ? (bool)$data[$f] : $data[$f]));
            }
        }
        if (empty($fields)) {
            http_response_code(400);
            echo json_encode(['error' => 'No fields to update']);
            exit();
        }
        $sql = 'UPDATE books SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        http_response_code(200);
        echo json_encode(['message' => 'Book updated']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
} elseif ($method === 'DELETE') {
    $request_uri = $_SERVER['REQUEST_URI'];
    $id = (int)basename($request_uri);
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid book ID']);
        exit();
    }
    try {
        $stmt = $db->prepare('DELETE FROM books WHERE id = :id');
        $stmt->execute(['id' => $id]);
        if ($stmt->rowCount() > 0) {
            http_response_code(200);
            echo json_encode(['message' => 'Book deleted']);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Book not found']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
} else {
    http_response_code(405);
    echo json_encode(['message' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
}
?>
