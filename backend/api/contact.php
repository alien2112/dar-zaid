<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/sendgrid_service.php';

// CORS headers
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

if ($method == 'POST') {
    $data = json_decode(file_get_contents("php://input"));
    
    if (!empty($data->name) && !empty($data->email) && !empty($data->message)) {
        try {
            // Create contact_messages table if it doesn't exist
            $db->exec("
                CREATE TABLE IF NOT EXISTS contact_messages (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    email VARCHAR(255) NOT NULL,
                    phone VARCHAR(50),
                    subject VARCHAR(500),
                    message TEXT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_email (email),
                    INDEX idx_created_at (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");

            $query = "INSERT INTO contact_messages (name, email, phone, subject, message) VALUES (:name, :email, :phone, :subject, :message)";
            $stmt = $db->prepare($query);
            
            $result = $stmt->execute([
                ':name' => $data->name,
                ':email' => $data->email,
                ':phone' => $data->phone ?? null,
                ':subject' => $data->subject ?? null,
                ':message' => $data->message
            ]);
            
            if ($result) {
                // Send email notifications
                try {
                    $sendGridService = new SendGridService();
                    
                    $contactData = [
                        'name' => $data->name,
                        'email' => $data->email,
                        'phone' => $data->phone ?? '',
                        'subject' => $data->subject ?? '',
                        'message' => $data->message
                    ];
                    
                    // Send notification to admin
                    $sendGridService->sendContactNotification($contactData);
                    
                    // Send confirmation to customer
                    $sendGridService->sendContactConfirmation($contactData);
                    
                } catch (Exception $e) {
                    // Log error but don't fail the contact form submission
                    error_log('Failed to send contact emails: ' . $e->getMessage());
                }
                
                $response = [
                    'success' => true,
                    'message' => 'تم إرسال رسالتك بنجاح. سنتواصل معك قريباً.'
                ];
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'فشل في حفظ الرسالة'], JSON_UNESCAPED_UNICODE);
            }
        } catch(PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'يرجى ملء جميع الحقول المطلوبة'], JSON_UNESCAPED_UNICODE);
    }
} else {
    http_response_code(405);
    echo json_encode(['message' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
}
?>
