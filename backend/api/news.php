<?php
header('Content-Type: application/json');
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '*';
header('Access-Control-Allow-Origin: ' . $origin);
header('Vary: Origin');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

include_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($method == 'GET') {
    try {
        $onlyPublished = isset($_GET['published']) ? (bool)$_GET['published'] : true;
        $where = $onlyPublished ? "WHERE status = 'published'" : '';
        $query = "SELECT * FROM news_releases $where ORDER BY published_date DESC, id DESC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        $news = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $item = [
                'id' => (int)$row['id'],
                'title' => $row['title'],
                'content' => $row['content'],
                'type' => $row['type'],
                'date' => $row['published_date'],
                'image' => $row['image_url'] ?? '/images/news-placeholder.jpg',
                'featured' => (bool)$row['featured'],
                'status' => $row['status'],
                'views' => (int)($row['views'] ?? 0)
            ];
            $news[] = $item;
        }
        
        echo json_encode(['news' => $news], JSON_UNESCAPED_UNICODE);
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
} elseif ($method === 'POST') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data || empty($data['title'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing title']);
            exit();
        }
        $sql = 'INSERT INTO news_releases (title, content, type, published_date, image_url, featured, status) VALUES (:title,:content,:type,:date,:image,:featured,:status)';
        $stmt = $db->prepare($sql);
        $stmt->execute([
            'title' => $data['title'],
            'content' => $data['content'] ?? '',
            'type' => $data['type'] ?? 'news',
            'date' => $data['date'] ?? date('Y-m-d'),
            'image' => $data['image'] ?? null,
            'featured' => !empty($data['featured']) ? 1 : 0,
            'status' => $data['status'] ?? 'published',
        ]);
        http_response_code(201);
        echo json_encode(['id' => (int)$db->lastInsertId()]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
} elseif ($method === 'PUT') {
    $id = (int)basename($_SERVER['REQUEST_URI']);
    if ($id <= 0) { http_response_code(400); echo json_encode(['error' => 'Invalid ID']); exit(); }
    try {
        $data = json_decode(file_get_contents('php://input'), true) ?: [];
        $fields = [];
        $params = ['id' => $id];
        foreach (['title','content','type','published_date','image_url','featured','status'] as $f) {
            if (array_key_exists($f, $data)) {
                $fields[] = "$f = :$f";
                $params[$f] = $f === 'featured' ? (!empty($data[$f]) ? 1 : 0) : $data[$f];
            }
        }
        if (empty($fields)) { http_response_code(400); echo json_encode(['error' => 'No fields to update']); exit(); }
        $sql = 'UPDATE news_releases SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        http_response_code(200);
        echo json_encode(['message' => 'News updated']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
} elseif ($method === 'DELETE') {
    $id = (int)basename($_SERVER['REQUEST_URI']);
    if ($id <= 0) { http_response_code(400); echo json_encode(['error' => 'Invalid ID']); exit(); }
    try {
        $stmt = $db->prepare('DELETE FROM news_releases WHERE id = :id');
        $stmt->execute(['id' => $id]);
        if ($stmt->rowCount() > 0) { http_response_code(200); echo json_encode(['message' => 'News deleted']); }
        else { http_response_code(404); echo json_encode(['error' => 'Not found']); }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
} else {
    http_response_code(405);
    echo json_encode(['message' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
}
?>
