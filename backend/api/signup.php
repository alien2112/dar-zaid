<?php
require_once '../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . (isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '*'));
header('Vary: Origin');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'OPTIONS') { http_response_code(200); exit(); }
if ($method !== 'POST') { http_response_code(405); echo json_encode(['error' => 'Method not allowed']); exit(); }

$database = new Database();
$db = $database->getConnection();

$raw = file_get_contents('php://input');
if ($raw === false) { http_response_code(400); echo json_encode(['error' => 'طلب غير صالح']); exit(); }
$data = json_decode($raw, true);
if (!is_array($data)) { http_response_code(400); echo json_encode(['error' => 'JSON غير صالح']); exit(); }
$name = trim($data['name'] ?? '');
$email = strtolower(trim($data['email'] ?? ''));
$password = $data['password'] ?? '';

if ($name === '' || $email === '' || $password === '') {
    http_response_code(400);
    echo json_encode(['error' => 'الاسم والبريد وكلمة المرور مطلوبة']);
    exit();
}

try {
    // Check existing user
    $chk = $db->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    $chk->execute([':email' => $email]);
    if ($chk->fetch(PDO::FETCH_ASSOC)) {
        http_response_code(409);
        echo json_encode(['error' => 'البريد مستخدم مسبقاً']);
        exit();
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $db->prepare('INSERT INTO users (name, email, password, role, status) VALUES (:name, :email, :password, :role, :status)');
    $stmt->execute([
        ':name' => $name,
        ':email' => $email,
        ':password' => $hash,
        ':role' => 'user',
        ':status' => 'active',
    ]);
    $userId = (int)$db->lastInsertId();

    // Issue cookie
    $secret = getenv('JWT_SECRET') ?: 'dev_secret_change_me';
    $payload = base64_encode(json_encode(['uid' => $userId, 'role' => 'user', 'exp' => time() + 60*60*24*7]));
    $sig = hash_hmac('sha256', $payload, $secret);
    $token = $payload . '.' . $sig;
    setcookie('auth_token', $token, [
        'expires' => time() + 60*60*24*7,
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    echo json_encode([
        'success' => true,
        'user' => [ 'id' => $userId, 'name' => $name, 'email' => $email, 'role' => 'user' ]
    ], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()]);
}

?>


