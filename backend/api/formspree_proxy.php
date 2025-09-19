<?php
// Include centralized CORS configuration
require_once __DIR__ . '/../config/cors.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'POST') {
    $data = json_decode(file_get_contents("php://input"));
    
    if (!empty($data->name) && !empty($data->email) && !empty($data->message)) {
        try {
            // Prepare form data for Formspree
            $formData = [
                'name' => $data->name,
                'email' => $data->email,
                'phone' => $data->phone ?? '',
                'subject' => $data->subject ?? 'رسالة جديدة من نموذج الاتصال',
                'message' => $data->message,
                '_subject' => $data->subject ?? 'رسالة جديدة من نموذج الاتصال',
                '_replyto' => $data->email,
            ];
            
            // Send to Formspree
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://formspree.io/f/xqadrpjy');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($formData));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false); // Don't follow redirects
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/x-www-form-urlencoded',
                'User-Agent: DarZaid-Website/1.0'
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                throw new Exception('CURL Error: ' . $error);
            }
            
            // Check if the response indicates success (200 or 302)
            if ($httpCode == 200 || $httpCode == 302) {
                $response = [
                    'success' => true,
                    'message' => 'تم إرسال رسالتك بنجاح. شكراً لتواصلك معنا!'
                ];
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
            } else {
                throw new Exception('Formspree returned HTTP ' . $httpCode);
            }
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'فشل في إرسال الرسالة: ' . $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
    } else {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'يرجى ملء جميع الحقول المطلوبة'
        ], JSON_UNESCAPED_UNICODE);
    }
} else {
    http_response_code(405);
    echo json_encode(['message' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
}
?>
