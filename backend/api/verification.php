<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/sendgrid_service.php';

// CORS and cookies
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . (isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '*'));
header('Vary: Origin');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'OPTIONS') { 
    http_response_code(200); 
    exit(); 
}

$request_uri = $_SERVER['REQUEST_URI'];
$path_parts = explode('/', trim(parse_url($request_uri, PHP_URL_PATH), '/'));
$endpoint = $path_parts[count($path_parts) - 1] ?? '';

if ($method == 'POST') {
    $data = json_decode(file_get_contents("php://input"));
    
    if ($endpoint === 'send-code') {
        handleSendVerificationCode($data);
    } elseif ($endpoint === 'verify-code') {
        handleVerifyCode($data);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint not found'], JSON_UNESCAPED_UNICODE);
    }
} else {
    http_response_code(405);
    echo json_encode(['message' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
}

function handleSendVerificationCode($data) {
    if (empty($data->email)) {
        http_response_code(400);
        echo json_encode(['error' => 'البريد الإلكتروني مطلوب'], JSON_UNESCAPED_UNICODE);
        return;
    }

    $email = trim($data->email);
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['error' => 'البريد الإلكتروني غير صحيح'], JSON_UNESCAPED_UNICODE);
        return;
    }

    try {
        $sendGridService = new SendGridService();
        
        // Generate 6-digit verification code
        $code = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        
        // Store code in database
        if (!$sendGridService->storeVerificationCode($email, $code)) {
            http_response_code(500);
            echo json_encode(['error' => 'فشل في حفظ كود التحقق'], JSON_UNESCAPED_UNICODE);
            return;
        }
        
        // Send verification email
        if (!$sendGridService->sendVerificationCode($email, $code)) {
            http_response_code(500);
            echo json_encode(['error' => 'فشل في إرسال كود التحقق'], JSON_UNESCAPED_UNICODE);
            return;
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'تم إرسال كود التحقق إلى بريدك الإلكتروني'
        ], JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'حدث خطأ في إرسال كود التحقق: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
}

function handleVerifyCode($data) {
    if (empty($data->email) || empty($data->code)) {
        http_response_code(400);
        echo json_encode(['error' => 'البريد الإلكتروني وكود التحقق مطلوبان'], JSON_UNESCAPED_UNICODE);
        return;
    }

    $email = trim($data->email);
    $code = trim($data->code);
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['error' => 'البريد الإلكتروني غير صحيح'], JSON_UNESCAPED_UNICODE);
        return;
    }

    try {
        $sendGridService = new SendGridService();
        
        // Verify code
        if (!$sendGridService->verifyCode($email, $code)) {
            http_response_code(400);
            echo json_encode(['error' => 'كود التحقق غير صحيح أو منتهي الصلاحية'], JSON_UNESCAPED_UNICODE);
            return;
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'تم التحقق بنجاح'
        ], JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'حدث خطأ في التحقق: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
}
?>
