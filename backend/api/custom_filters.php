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

// Create custom_filters table if it doesn't exist
try {
    $createTableSql = "CREATE TABLE IF NOT EXISTS custom_filters (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        type ENUM('select', 'range') NOT NULL,
        field_name VARCHAR(100) NOT NULL,
        options JSON,
        unit VARCHAR(20),
        is_active BOOLEAN DEFAULT TRUE,
        sort_order INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $db->exec($createTableSql);
} catch (PDOException $e) {
    // Table might already exist, continue
}

if ($method == 'GET') {
    try {
        $sql = "SELECT * FROM custom_filters WHERE is_active = TRUE ORDER BY sort_order ASC, created_at ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        
        $filters = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $filters[] = [
                'id' => (int)$row['id'],
                'name' => $row['name'],
                'type' => $row['type'],
                'field_name' => $row['field_name'],
                'options' => $row['options'] ? json_decode($row['options'], true) : null,
                'unit' => $row['unit'],
                'is_active' => (bool)$row['is_active'],
                'sort_order' => (int)$row['sort_order']
            ];
        }
        
        echo json_encode($filters, JSON_UNESCAPED_UNICODE);
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
} elseif ($method === 'POST') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data || empty($data['name']) || empty($data['type']) || empty($data['field_name'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields: name, type, field_name']);
            exit();
        }
        
        $sql = 'INSERT INTO custom_filters (name, type, field_name, options, unit, sort_order) 
                VALUES (:name, :type, :field_name, :options, :unit, :sort_order)';
        $stmt = $db->prepare($sql);
        
        $executeParams = [
            'name' => $data['name'],
            'type' => $data['type'],
            'field_name' => $data['field_name'],
            'options' => isset($data['options']) ? json_encode($data['options']) : null,
            'unit' => $data['unit'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0
        ];
        
        $stmt->execute($executeParams);
        $id = (int)$db->lastInsertId();
        
        http_response_code(201);
        echo json_encode(['id' => $id, 'message' => 'Custom filter created'], JSON_UNESCAPED_UNICODE);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
} elseif ($method === 'PUT') {
    $request_uri = $_SERVER['REQUEST_URI'];
    $id = (int)basename($request_uri);
    
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid filter ID']);
        exit();
    }
    
    try {
        $data = json_decode(file_get_contents('php://input'), true) ?: [];
        $fields = [];
        $params = ['id' => $id];
        
        foreach (['name', 'type', 'field_name', 'unit', 'sort_order', 'is_active'] as $f) {
            if (array_key_exists($f, $data)) {
                $fields[] = "$f = :$f";
                $params[$f] = $f === 'sort_order' ? (int)$data[$f] : ($f === 'is_active' ? (bool)$data[$f] : $data[$f]);
            }
        }
        
        if (array_key_exists('options', $data)) {
            $fields[] = "options = :options";
            $params['options'] = json_encode($data['options']);
        }
        
        if (empty($fields)) {
            http_response_code(400);
            echo json_encode(['error' => 'No fields to update']);
            exit();
        }
        
        $sql = 'UPDATE custom_filters SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        http_response_code(200);
        echo json_encode(['message' => 'Custom filter updated'], JSON_UNESCAPED_UNICODE);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
} elseif ($method === 'DELETE') {
    $request_uri = $_SERVER['REQUEST_URI'];
    $id = (int)basename($request_uri);
    
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid filter ID']);
        exit();
    }
    
    try {
        // Soft delete by setting is_active to false
        $stmt = $db->prepare('UPDATE custom_filters SET is_active = FALSE WHERE id = :id');
        $stmt->execute(['id' => $id]);
        
        if ($stmt->rowCount() > 0) {
            http_response_code(200);
            echo json_encode(['message' => 'Custom filter deleted']);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Filter not found']);
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
