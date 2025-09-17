<?php
require_once '../config/database.php';

header('Content-Type: application/json');
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : 'http://localhost:3000';
header('Access-Control-Allow-Origin: ' . $origin);
header('Vary: Origin');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

$database = new Database();
$db = $database->getConnection();

// Ensure table exists
$db->exec("CREATE TABLE IF NOT EXISTS slider_images (
  id INT PRIMARY KEY AUTO_INCREMENT,
  title VARCHAR(255) NOT NULL,
  subtitle VARCHAR(255) NULL,
  image_url VARCHAR(500) NOT NULL,
  link_url VARCHAR(500) NULL,
  button_text VARCHAR(100) NULL,
  display_order INT DEFAULT 0,
  is_active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
  $admin = isset($_GET['admin']);
  $sql = 'SELECT * FROM slider_images' . ($admin ? '' : ' WHERE is_active = 1') . ' ORDER BY display_order, id';
  $stmt = $db->query($sql);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  echo json_encode(['sliders' => $rows], JSON_UNESCAPED_UNICODE); exit();
}

if ($method === 'POST') {
  $data = json_decode(file_get_contents('php://input'), true);
  if (!$data || empty($data['title']) || empty($data['image_url'])) { http_response_code(400); echo json_encode(['error'=>'Invalid payload']); exit(); }
  $stmt = $db->prepare('INSERT INTO slider_images (title, subtitle, image_url, link_url, button_text, display_order, is_active) VALUES (:title,:subtitle,:image_url,:link_url,:button_text,:display_order,:is_active)');
  $stmt->execute([
    ':title'=>$data['title'], ':subtitle'=>$data['subtitle']??null, ':image_url'=>$data['image_url'], ':link_url'=>$data['link_url']??null,
    ':button_text'=>$data['button_text']??null, ':display_order'=>intval($data['display_order']??0), ':is_active'=>!empty($data['is_active'])?1:0
  ]);
  echo json_encode(['id'=>$db->lastInsertId()]); exit();
}

if ($method === 'PUT') {
  $id = (int)basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
  if ($id<=0) { http_response_code(400); echo json_encode(['error'=>'Invalid ID']); exit(); }
  $data = json_decode(file_get_contents('php://input'), true) ?: [];
  $fields=[]; $params=[':id'=>$id];
  foreach(['title','subtitle','image_url','link_url','button_text','display_order','is_active'] as $f){
    if(array_key_exists($f,$data)){ $fields[] = "$f = :$f"; $params[":$f"] = ($f==='display_order'?intval($data[$f]):($f==='is_active'?(int)!empty($data[$f]):$data[$f])); }
  }
  if (empty($fields)) { http_response_code(400); echo json_encode(['error'=>'No fields']); exit(); }
  $sql = 'UPDATE slider_images SET '.implode(',',$fields).' WHERE id = :id';
  $stmt = $db->prepare($sql); $stmt->execute($params); echo json_encode(['success'=>true]); exit();
}

if ($method === 'DELETE') {
  $id = (int)basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
  if ($id<=0) { http_response_code(400); echo json_encode(['error'=>'Invalid ID']); exit(); }
  $stmt = $db->prepare('DELETE FROM slider_images WHERE id = :id'); $stmt->execute([':id'=>$id]); echo json_encode(['success'=>true]); exit();
}

http_response_code(405); echo json_encode(['error'=>'Method not allowed']);
?>

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
if ($method === 'OPTIONS') { http_response_code(200); exit(); }

$database = new Database();
$db = $database->getConnection();

if ($method == 'GET') {
    try {
        // Check if admin is requesting all sliders (including inactive)
        $isAdmin = isset($_GET['admin']) && $_GET['admin'] === 'true';
        $whereClause = $isAdmin ? '' : 'WHERE is_active = 1';

        $query = "SELECT * FROM slider_images $whereClause ORDER BY display_order, id";
        $stmt = $db->prepare($query);
        $stmt->execute();

        $sliders = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $slider = [
                'id' => (int)$row['id'],
                'title' => $row['title'],
                'subtitle' => $row['subtitle'] ?? '',
                'image_url' => $row['image_url'],
                'link_url' => $row['link_url'] ?? '',
                'button_text' => $row['button_text'] ?? '',
                'display_order' => (int)$row['display_order'],
                'is_active' => (bool)$row['is_active'],
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at']
            ];
            $sliders[] = $slider;
        }

        echo json_encode(['sliders' => $sliders], JSON_UNESCAPED_UNICODE);
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }

} elseif ($method === 'POST') {
    // Create new slider image
    try {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$data || empty($data['title']) || empty($data['image_url'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Title and image URL are required'], JSON_UNESCAPED_UNICODE);
            exit();
        }

        $sql = 'INSERT INTO slider_images (title, subtitle, image_url, link_url, button_text, display_order, is_active)
                VALUES (:title, :subtitle, :image_url, :link_url, :button_text, :display_order, :is_active)';

        $stmt = $db->prepare($sql);
        $stmt->execute([
            'title' => $data['title'],
            'subtitle' => $data['subtitle'] ?? '',
            'image_url' => $data['image_url'],
            'link_url' => $data['link_url'] ?? '',
            'button_text' => $data['button_text'] ?? '',
            'display_order' => $data['display_order'] ?? 0,
            'is_active' => $data['is_active'] ?? true
        ]);

        $id = (int)$db->lastInsertId();

        http_response_code(201);
        echo json_encode(['id' => $id, 'message' => 'Slider image created successfully'], JSON_UNESCAPED_UNICODE);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }

} elseif ($method === 'PUT') {
    // Update slider image
    $request_uri = $_SERVER['REQUEST_URI'];
    $id = (int)basename($request_uri);

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid slider ID'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    try {
        $data = json_decode(file_get_contents('php://input'), true) ?: [];
        $fields = [];
        $params = ['id' => $id];

        $allowedFields = ['title', 'subtitle', 'image_url', 'link_url', 'button_text', 'display_order', 'is_active'];

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

        $sql = 'UPDATE slider_images SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        if ($stmt->rowCount() > 0) {
            http_response_code(200);
            echo json_encode(['message' => 'Slider image updated successfully'], JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Slider image not found'], JSON_UNESCAPED_UNICODE);
        }

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }

} elseif ($method === 'DELETE') {
    // Delete slider image
    $request_uri = $_SERVER['REQUEST_URI'];
    $id = (int)basename($request_uri);

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid slider ID'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    try {
        $stmt = $db->prepare('DELETE FROM slider_images WHERE id = :id');
        $stmt->execute(['id' => $id]);

        if ($stmt->rowCount() > 0) {
            http_response_code(200);
            echo json_encode(['message' => 'Slider image deleted successfully'], JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Slider image not found'], JSON_UNESCAPED_UNICODE);
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