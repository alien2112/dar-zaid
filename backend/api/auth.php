<?php
// Include centralized CORS configuration
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'POST') {
    $data = json_decode(file_get_contents("php://input"));
    
    if (!empty($data->email) && !empty($data->password)) {
        try {
            $query = "SELECT * FROM users WHERE email = :email AND status = 'active'";
            $stmt = $db->prepare($query);
            $stmt->execute([':email' => $data->email]);
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($data->password, $user['password'])) {
                // Update last login
                $updateQuery = "UPDATE users SET last_login = NOW() WHERE id = :id";
                $updateStmt = $db->prepare($updateQuery);
                $updateStmt->execute([':id' => $user['id']]);
                
                // Create a signed session token (simple HMAC)
                $secret = getenv('JWT_SECRET') ?: 'dev_secret_change_me';
                $payload = base64_encode(json_encode([
                    'uid' => (int)$user['id'],
                    'role' => $user['role'],
                    'exp' => time() + 60*60*24*7
                ]));
                $sig = hash_hmac('sha256', $payload, $secret);
                $token = $payload . '.' . $sig;

                // Set HttpOnly cookie
                $cookieParams = [
                    'expires' => time() + 60*60*24*7,
                    'path' => '/',
                    'domain' => '',
                    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
                    'httponly' => true,
                    'samesite' => 'Lax'
                ];
                setcookie('auth_token', $token, $cookieParams);

                // Return minimal user info (no token in body)
                echo json_encode([
                    'success' => true,
                    'message' => 'تم تسجيل الدخول بنجاح',
                    'user' => [
                        'id' => (int)$user['id'],
                        'name' => $user['name'],
                        'email' => $user['email'],
                        'role' => $user['role']
                    ]
                ], JSON_UNESCAPED_UNICODE);
            } else {
                http_response_code(401);
                $response = [
                    'success' => false,
                    'message' => 'بيانات تسجيل الدخول غير صحيحة'
                ];
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
            }
        } catch(PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'يرجى إدخال البريد الإلكتروني وكلمة المرور'], JSON_UNESCAPED_UNICODE);
    }
} else {
    http_response_code(405);
    echo json_encode(['message' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
}
?>
