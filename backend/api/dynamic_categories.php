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
$db->exec("CREATE TABLE IF NOT EXISTS dynamic_categories (
  id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  type VARCHAR(50) NOT NULL DEFAULT 'top_sellers',
  description TEXT NULL,
  max_items INT DEFAULT 4,
  widget_style VARCHAR(20) DEFAULT 'grid',
  show_on_homepage TINYINT(1) DEFAULT 1,
  is_active TINYINT(1) DEFAULT 1,
  filter_criteria JSON NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
  $stmt = $db->query('SELECT * FROM dynamic_categories ORDER BY id DESC');
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  echo json_encode(['categories' => $rows], JSON_UNESCAPED_UNICODE); exit();
}

if ($method === 'POST') {
  $data = json_decode(file_get_contents('php://input'), true);
  if (!$data || empty($data['name'])) { http_response_code(400); echo json_encode(['error'=>'Invalid payload']); exit(); }
  $stmt = $db->prepare('INSERT INTO dynamic_categories (name,type,description,max_items,widget_style,show_on_homepage,is_active,filter_criteria) VALUES (:name,:type,:description,:max_items,:widget_style,:show_on_homepage,:is_active,:filter_criteria)');
  $stmt->execute([
    ':name'=>$data['name'], ':type'=>$data['type']??'top_sellers', ':description'=>$data['description']??null, ':max_items'=>intval($data['max_items']??4),
    ':widget_style'=>$data['widget_style']??'grid', ':show_on_homepage'=>!empty($data['show_on_homepage'])?1:0, ':is_active'=>!empty($data['is_active'])?1:0,
    ':filter_criteria'=> isset($data['filter_criteria']) ? json_encode($data['filter_criteria'], JSON_UNESCAPED_UNICODE) : null
  ]);
  echo json_encode(['id'=>$db->lastInsertId()]); exit();
}

if ($method === 'PUT') {
  $id = (int)basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
  if ($id<=0) { http_response_code(400); echo json_encode(['error'=>'Invalid ID']); exit(); }
  $data = json_decode(file_get_contents('php://input'), true) ?: [];
  $fields=[]; $params=[':id'=>$id];
  foreach(['name','type','description','max_items','widget_style','show_on_homepage','is_active','filter_criteria'] as $f){
    if(array_key_exists($f,$data)){
      $val = $data[$f];
      if($f==='max_items'){ $val=intval($val); }
      if(in_array($f,['show_on_homepage','is_active'])){ $val = !empty($val)?1:0; }
      if($f==='filter_criteria' && is_array($val)){ $val=json_encode($val, JSON_UNESCAPED_UNICODE); }
      $fields[] = "$f = :$f"; $params[":$f"] = $val;
    }
  }
  if (empty($fields)) { http_response_code(400); echo json_encode(['error'=>'No fields']); exit(); }
  $sql = 'UPDATE dynamic_categories SET '.implode(',',$fields).' WHERE id = :id';
  $stmt=$db->prepare($sql); $stmt->execute($params); echo json_encode(['success'=>true]); exit();
}

if ($method === 'DELETE') {
  $id = (int)basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
  if ($id<=0) { http_response_code(400); echo json_encode(['error'=>'Invalid ID']); exit(); }
  $stmt=$db->prepare('DELETE FROM dynamic_categories WHERE id = :id'); $stmt->execute([':id'=>$id]); echo json_encode(['success'=>true]); exit();
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

if ($method === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Helper function to get books based on dynamic category criteria
function getBooksForDynamicCategory($db, $category) {
    $criteria = json_decode($category['filter_criteria'], true);
    $maxItems = (int)$category['max_items'];

    $where = [];
    $params = [];
    $orderBy = 'ORDER BY created_at DESC';

    // Base condition: only show books with stock
    $where[] = 'stock_quantity > 0';

    switch ($category['type']) {
        case 'top_sellers':
            $orderBy = 'ORDER BY sales_count DESC, created_at DESC';
            if (isset($criteria['min_stock'])) {
                $where[] = 'stock_quantity >= :min_stock';
                $params['min_stock'] = $criteria['min_stock'];
            }
            break;

        case 'recent_releases':
            $orderBy = 'ORDER BY created_at DESC';
            if (isset($criteria['days_limit'])) {
                $where[] = 'created_at >= DATE_SUB(NOW(), INTERVAL :days_limit DAY)';
                $params['days_limit'] = $criteria['days_limit'];
            }
            break;

        case 'discounted':
            $where[] = 'discount_percentage > 0';
            if (isset($criteria['discount_min'])) {
                $where[] = 'discount_percentage >= :discount_min';
                $params['discount_min'] = $criteria['discount_min'];
            }
            $orderBy = 'ORDER BY discount_percentage DESC, created_at DESC';
            break;

        case 'featured':
            $where[] = 'is_featured = 1';
            $orderBy = 'ORDER BY created_at DESC';
            break;

        case 'category_based':
            if (isset($criteria['category'])) {
                $where[] = 'category = :category';
                $params['category'] = $criteria['category'];
            }
            break;

        case 'author_collection':
            if (isset($criteria['author'])) {
                $where[] = 'author = :author';
                $params['author'] = $criteria['author'];
            }
            break;
    }

    $whereSql = 'WHERE ' . implode(' AND ', $where);
    $sql = "SELECT id, title, author, description, price, original_price, discount_percentage,
                   category, image_url, isbn, stock_quantity, sales_count, is_featured, created_at
            FROM books $whereSql $orderBy LIMIT :max_items";

    $stmt = $db->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue(':' . $key, $value);
    }
    $stmt->bindValue(':max_items', $maxItems, PDO::PARAM_INT);
    $stmt->execute();

    $books = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $books[] = [
            'id' => (int)$row['id'],
            'title' => $row['title'],
            'author' => $row['author'],
            'description' => $row['description'],
            'price' => (float)$row['price'],
            'original_price' => $row['original_price'] ? (float)$row['original_price'] : null,
            'discount_percentage' => (float)$row['discount_percentage'],
            'category' => $row['category'],
            'image_url' => $row['image_url'] ?? '/images/book-placeholder.jpg',
            'isbn' => $row['isbn'],
            'stock_quantity' => (int)$row['stock_quantity'],
            'sales_count' => (int)$row['sales_count'],
            'is_featured' => (bool)$row['is_featured'],
            'created_at' => $row['created_at']
        ];
    }

    return $books;
}

if ($method === 'GET') {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $pathParts = explode('/', trim($path, '/'));

    // Check if requesting books for a specific category: /api/dynamic_categories/{id}/books
    if (count($pathParts) >= 3 && end($pathParts) === 'books') {
        $categoryId = (int)$pathParts[count($pathParts) - 2];

        try {
            // Get the category
            $stmt = $db->prepare('SELECT * FROM dynamic_categories WHERE id = :id AND is_active = 1');
            $stmt->execute(['id' => $categoryId]);
            $category = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$category) {
                http_response_code(404);
                echo json_encode(['error' => 'Category not found'], JSON_UNESCAPED_UNICODE);
                exit();
            }

            $books = getBooksForDynamicCategory($db, $category);

            echo json_encode([
                'category' => [
                    'id' => (int)$category['id'],
                    'name' => $category['name'],
                    'slug' => $category['slug'],
                    'description' => $category['description'],
                    'type' => $category['type'],
                    'widget_style' => $category['widget_style']
                ],
                'books' => $books
            ], JSON_UNESCAPED_UNICODE);

        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }

    } else {
        // Get all active dynamic categories with their books
        try {
            $stmt = $db->prepare(
                'SELECT * FROM dynamic_categories
                 WHERE is_active = 1 AND show_on_homepage = 1
                 ORDER BY display_order ASC, created_at DESC'
            );
            $stmt->execute();

            $categories = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $books = getBooksForDynamicCategory($db, $row);

                // Only include category if it has books
                if (!empty($books)) {
                    $categories[] = [
                        'id' => (int)$row['id'],
                        'name' => $row['name'],
                        'slug' => $row['slug'],
                        'description' => $row['description'],
                        'type' => $row['type'],
                        'widget_style' => $row['widget_style'],
                        'display_order' => (int)$row['display_order'],
                        'max_items' => (int)$row['max_items'],
                        'books' => $books
                    ];
                }
            }

            echo json_encode(['categories' => $categories], JSON_UNESCAPED_UNICODE);

        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
    }

} elseif ($method === 'POST') {
    // Create new dynamic category (admin only)
    try {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$data || empty($data['name']) || empty($data['type'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Name and type are required'], JSON_UNESCAPED_UNICODE);
            exit();
        }

        // Generate slug from name
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-',
               transliterator_transliterate('Any-Latin; Latin-ASCII', $data['name']))));

        $sql = 'INSERT INTO dynamic_categories (name, slug, description, type, filter_criteria,
                display_order, max_items, widget_style, show_on_homepage)
                VALUES (:name, :slug, :description, :type, :filter_criteria,
                :display_order, :max_items, :widget_style, :show_on_homepage)';

        $stmt = $db->prepare($sql);
        $stmt->execute([
            'name' => $data['name'],
            'slug' => $slug,
            'description' => $data['description'] ?? '',
            'type' => $data['type'],
            'filter_criteria' => json_encode($data['filter_criteria'] ?? []),
            'display_order' => $data['display_order'] ?? 0,
            'max_items' => $data['max_items'] ?? 4,
            'widget_style' => $data['widget_style'] ?? 'grid',
            'show_on_homepage' => $data['show_on_homepage'] ?? true
        ]);

        $id = (int)$db->lastInsertId();

        http_response_code(201);
        echo json_encode(['id' => $id, 'message' => 'Dynamic category created'], JSON_UNESCAPED_UNICODE);

    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            http_response_code(400);
            echo json_encode(['error' => 'Category with this slug already exists'], JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Database error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
    }

} elseif ($method === 'PUT') {
    // Update dynamic category
    $request_uri = $_SERVER['REQUEST_URI'];
    $id = (int)basename($request_uri);

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid category ID'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    try {
        $data = json_decode(file_get_contents('php://input'), true) ?: [];
        $fields = [];
        $params = ['id' => $id];

        $allowedFields = ['name', 'description', 'type', 'filter_criteria',
                         'display_order', 'max_items', 'is_active', 'widget_style', 'show_on_homepage'];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                if ($field === 'filter_criteria') {
                    $fields[] = "$field = :$field";
                    $params[$field] = json_encode($data[$field]);
                } else {
                    $fields[] = "$field = :$field";
                    $params[$field] = $data[$field];
                }
            }
        }

        if (empty($fields)) {
            http_response_code(400);
            echo json_encode(['error' => 'No fields to update'], JSON_UNESCAPED_UNICODE);
            exit();
        }

        $sql = 'UPDATE dynamic_categories SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        if ($stmt->rowCount() > 0) {
            http_response_code(200);
            echo json_encode(['message' => 'Dynamic category updated'], JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Category not found'], JSON_UNESCAPED_UNICODE);
        }

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }

} elseif ($method === 'DELETE') {
    // Delete dynamic category
    $request_uri = $_SERVER['REQUEST_URI'];
    $id = (int)basename($request_uri);

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid category ID'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    try {
        $stmt = $db->prepare('DELETE FROM dynamic_categories WHERE id = :id');
        $stmt->execute(['id' => $id]);

        if ($stmt->rowCount() > 0) {
            http_response_code(200);
            echo json_encode(['message' => 'Dynamic category deleted'], JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Category not found'], JSON_UNESCAPED_UNICODE);
        }

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }

} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
}
?>