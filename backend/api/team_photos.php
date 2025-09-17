<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : 'http://localhost:3000';
header('Access-Control-Allow-Origin: ' . $origin);
header('Vary: Origin');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { 
    http_response_code(200); 
    exit(); 
}

$database = new Database();
$db = $database->getConnection();

// Ensure table exists
$db->exec("CREATE TABLE IF NOT EXISTS team_photos (
  id INT PRIMARY KEY AUTO_INCREMENT,
  title VARCHAR(255) NOT NULL,
  description TEXT NULL,
  image_url VARCHAR(500) NOT NULL,
  display_order INT DEFAULT 0,
  is_active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    try {
        // Check if admin is requesting all photos (including inactive)
        $isAdmin = isset($_GET['admin']) && $_GET['admin'] === 'true';
        $whereClause = $isAdmin ? '' : 'WHERE is_active = 1';

        $query = "SELECT * FROM team_photos $whereClause ORDER BY display_order, id";
        $stmt = $db->prepare($query);
        $stmt->execute();

        $photos = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $photo = [
                'id' => (int)$row['id'],
                'title' => $row['title'],
                'description' => $row['description'] ?? '',
                'image_url' => $row['image_url'],
                'display_order' => (int)$row['display_order'],
                'is_active' => (bool)$row['is_active'],
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at']
            ];
            $photos[] = $photo;
        }

        echo json_encode(['team_photos' => $photos], JSON_UNESCAPED_UNICODE);
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }

} elseif ($method === 'POST') {
    // Create new team photo
    try {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$data || empty($data['title']) || empty($data['image_url'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Title and image URL are required'], JSON_UNESCAPED_UNICODE);
            exit();
        }

        $sql = 'INSERT INTO team_photos (title, description, image_url, display_order, is_active)
                VALUES (:title, :description, :image_url, :display_order, :is_active)';

        $stmt = $db->prepare($sql);
        $stmt->execute([
            'title' => $data['title'],
            'description' => $data['description'] ?? '',
            'image_url' => $data['image_url'],
            'display_order' => $data['display_order'] ?? 0,
            'is_active' => $data['is_active'] ?? true
        ]);

        $id = (int)$db->lastInsertId();

        http_response_code(201);
        echo json_encode(['id' => $id, 'message' => 'Team photo created successfully'], JSON_UNESCAPED_UNICODE);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }

} elseif ($method === 'PUT') {
    // Update team photo
    $request_uri = $_SERVER['REQUEST_URI'];
    $id = (int)basename($request_uri);

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid team photo ID'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    try {
        $data = json_decode(file_get_contents('php://input'), true) ?: [];
        $fields = [];
        $params = ['id' => $id];

        $allowedFields = ['title', 'description', 'image_url', 'display_order', 'is_active'];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "$field = :$field";
                $params[$field] = $data[$field];
            }
        }

        if (empty($fields)) {
            http_response_code(400);
            echo json_encode(['error' => 'No fields to update'], JSON_UNESCAPED_UNICODE);
            exit();
        }

        $sql = 'UPDATE team_photos SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        if ($stmt->rowCount() > 0) {
            http_response_code(200);
            echo json_encode(['message' => 'Team photo updated successfully'], JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Team photo not found'], JSON_UNESCAPED_UNICODE);
        }

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }

} elseif ($method === 'DELETE') {
    // Delete team photo
    $request_uri = $_SERVER['REQUEST_URI'];
    $id = (int)basename($request_uri);

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid team photo ID'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    try {
        $stmt = $db->prepare('DELETE FROM team_photos WHERE id = :id');
        $stmt->execute(['id' => $id]);

        if ($stmt->rowCount() > 0) {
            http_response_code(200);
            echo json_encode(['message' => 'Team photo deleted successfully'], JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Team photo not found'], JSON_UNESCAPED_UNICODE);
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
