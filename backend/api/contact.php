<?php
$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'POST') {
    $data = json_decode(file_get_contents("php://input"));
    
    if (!empty($data->name) && !empty($data->email) && !empty($data->message)) {
        try {
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
