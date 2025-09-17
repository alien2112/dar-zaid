<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '*';
header('Access-Control-Allow-Origin: ' . $origin);
header('Vary: Origin');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'OPTIONS') { http_response_code(200); exit(); }

if ($method == 'GET') {
    try {
        // Check if admin is requesting all posts (including drafts)
        $isAdmin = isset($_GET['admin']) && $_GET['admin'] === 'true';
        $whereClause = $isAdmin ? '' : "WHERE status = 'published'";

        $query = "SELECT * FROM blog_posts $whereClause ORDER BY published_date DESC";
        $stmt = $db->prepare($query);
        $stmt->execute();

        $posts = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $post = [
                'id' => (int)$row['id'],
                'title' => $row['title'],
                'content' => $row['content'],
                'excerpt' => $row['excerpt'] ?? substr(strip_tags($row['content']), 0, 200) . '...',
                'author' => $row['author'],
                'date' => $row['published_date'],
                'image' => $row['image_url'] ?? '/images/blog-placeholder.jpg',
                'views' => (int)($row['views'] ?? 0),
                'status' => $row['status'],
                'createdAt' => $row['created_at'],
                'updatedAt' => $row['updated_at']
            ];
            $posts[] = $post;
        }

        echo json_encode(['posts' => $posts], JSON_UNESCAPED_UNICODE);
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }

} elseif ($method === 'POST') {
    // Create new blog post
    try {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$data || empty($data['title']) || empty($data['content'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Title and content are required'], JSON_UNESCAPED_UNICODE);
            exit();
        }

        $sql = 'INSERT INTO blog_posts (title, content, author, image_url, status)
                VALUES (:title, :content, :author, :image_url, :status)';

        $stmt = $db->prepare($sql);
        $stmt->execute([
            'title' => $data['title'],
            'content' => $data['content'],
            'author' => $data['author'] ?? 'إدارة الموقع',
            'image_url' => $data['image'] ?? null,
            'status' => $data['status'] ?? 'published'
        ]);

        $id = (int)$db->lastInsertId();

        http_response_code(201);
        echo json_encode(['id' => $id, 'message' => 'Blog post created successfully'], JSON_UNESCAPED_UNICODE);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }

} elseif ($method === 'PUT') {
    // Update blog post
    $request_uri = $_SERVER['REQUEST_URI'];
    $id = (int)basename($request_uri);

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid post ID'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    try {
        $data = json_decode(file_get_contents('php://input'), true) ?: [];
        $fields = [];
        $params = ['id' => $id];

        $allowedFields = ['title', 'content', 'author', 'image_url', 'status'];

        foreach ($allowedFields as $field) {
            $dataKey = $field === 'image_url' ? 'image' : $field;

            if (array_key_exists($dataKey, $data)) {
                $fields[] = "$field = :$field";
                $params[$field] = $data[$dataKey];
            }
        }

        if (empty($fields)) {
            http_response_code(400);
            echo json_encode(['error' => 'No fields to update'], JSON_UNESCAPED_UNICODE);
            exit();
        }

        $sql = 'UPDATE blog_posts SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        if ($stmt->rowCount() > 0) {
            http_response_code(200);
            echo json_encode(['message' => 'Blog post updated successfully'], JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Blog post not found'], JSON_UNESCAPED_UNICODE);
        }

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }

} elseif ($method === 'DELETE') {
    // Delete blog post
    $request_uri = $_SERVER['REQUEST_URI'];
    $id = (int)basename($request_uri);

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid post ID'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    try {
        $stmt = $db->prepare('DELETE FROM blog_posts WHERE id = :id');
        $stmt->execute(['id' => $id]);

        if ($stmt->rowCount() > 0) {
            http_response_code(200);
            echo json_encode(['message' => 'Blog post deleted successfully'], JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Blog post not found'], JSON_UNESCAPED_UNICODE);
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
