<?php
// Test moving_bar API directly
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set CORS headers
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '*';
header('Access-Control-Allow-Origin: ' . $origin);
header('Vary: Origin');
header('Access-Control-Allow-Methods: GET, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json');

try {
    require_once 'backend/config/database.php';
    
    $database = new Database();
    $db = $database->getConnection();
    
    $stmt = $db->query('SELECT * FROM moving_bar ORDER BY id DESC LIMIT 1');
    $movingBar = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$movingBar) {
        $movingBar = ['text' => 'مرحباً بكم في دار زيد للنشر والتوزيع'];
    }
    
    echo json_encode($movingBar, JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>
