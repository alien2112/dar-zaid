<?php
require_once '../config/database.php';

header('Content-Type: application/json');
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '*';
header('Access-Control-Allow-Origin: ' . $origin);
header('Vary: Origin');
header('Access-Control-Allow-Methods: GET, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'OPTIONS') { http_response_code(200); exit(); }

$database = new Database();
$db = $database->getConnection();

if ($method === 'GET') {
    try {
        $stmt = $db->query('SELECT * FROM moving_bar ORDER BY id DESC LIMIT 1');
        $movingBar = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$movingBar) {
            $movingBar = ['text' => 'مرحباً بكم في دار زيد للنشر والتوزيع'];
        }
        echo json_encode($movingBar, JSON_UNESCAPED_UNICODE);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
} elseif ($method === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data['text'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Text is required'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    try {
        // Try to update first
        $stmt = $db->prepare('UPDATE moving_bar SET text = :text WHERE id = 1');
        $stmt->execute(['text' => $data['text']]);

        if ($stmt->rowCount() === 0) {
            // If no rows were updated, insert new row
            $stmt = $db->prepare('INSERT INTO moving_bar (id, text) VALUES (1, :text)');
            $stmt->execute(['text' => $data['text']]);
        }

        http_response_code(200);
        echo json_encode(['message' => 'Moving bar text updated successfully'], JSON_UNESCAPED_UNICODE);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
}
?>