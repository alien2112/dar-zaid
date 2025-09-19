<?php
// Include centralized CORS configuration
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';

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