<?php
require_once __DIR__ . '/../config/database.php';

class SendGridService {
    private $apiKey;
    private $fromEmail;
    private $fromName;
    private $db;

    public function __construct() {
        $this->apiKey = getenv('SENDGRID_API_KEY') ?: '';
        $this->fromEmail = getenv('SENDGRID_FROM_EMAIL') ?: 'noreply@darzaid.com';
        $this->fromName = getenv('SENDGRID_FROM_NAME') ?: 'دار زيد';
        
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /**
     * Send email using SendGrid API
     */
    public function sendEmail($to, $subject, $htmlContent, $textContent = null) {
        if (empty($this->apiKey)) {
            error_log('SendGrid API key not configured');
            return false;
        }

        $data = [
            'personalizations' => [
                [
                    'to' => [
                        ['email' => $to]
                    ],
                    'subject' => $subject
                ]
            ],
            'from' => [
                'email' => $this->fromEmail,
                'name' => $this->fromName
            ],
            'content' => [
                [
                    'type' => 'text/html',
                    'value' => $htmlContent
                ]
            ]
        ];

        // Add text content if provided
        if ($textContent) {
            $data['content'][] = [
                'type' => 'text/plain',
                'value' => $textContent
            ];
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.sendgrid.com/v3/mail/send');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            return true;
        } else {
            error_log('SendGrid API error: ' . $response);
            return false;
        }
    }

    /**
     * Send verification code for login
     */
    public function sendVerificationCode($email, $code) {
        $subject = 'كود التحقق - دار زيد';
        $htmlContent = $this->getVerificationEmailTemplate($code);
        $textContent = "كود التحقق الخاص بك هو: $code\n\nهذا الكود صالح لمدة 10 دقائق.\n\nشكراً لاستخدامك دار زيد";

        return $this->sendEmail($email, $subject, $htmlContent, $textContent);
    }

    /**
     * Send order confirmation email
     */
    public function sendOrderConfirmation($orderData) {
        $customerEmail = $orderData['customer_info']['email'];
        $subject = 'تأكيد الطلب - دار زيد';
        $htmlContent = $this->getOrderConfirmationTemplate($orderData);
        $textContent = $this->getOrderConfirmationText($orderData);

        return $this->sendEmail($customerEmail, $subject, $htmlContent, $textContent);
    }

    /**
     * Send contact form notification to admin
     */
    public function sendContactNotification($contactData) {
        $adminEmail = getenv('ADMIN_EMAIL') ?: 'admin@darzaid.com';
        $subject = 'رسالة جديدة من صفحة الاتصال - دار زيد';
        $htmlContent = $this->getContactNotificationTemplate($contactData);
        $textContent = $this->getContactNotificationText($contactData);

        return $this->sendEmail($adminEmail, $subject, $htmlContent, $textContent);
    }

    /**
     * Send contact form confirmation to customer
     */
    public function sendContactConfirmation($contactData) {
        $customerEmail = $contactData['email'];
        $subject = 'تم استلام رسالتك - دار زيد';
        $htmlContent = $this->getContactConfirmationTemplate($contactData);
        $textContent = "شكراً لك على تواصلك معنا. سنرد عليك في أقرب وقت ممكن.\n\nتفاصيل رسالتك:\nالاسم: {$contactData['name']}\nالموضوع: {$contactData['subject']}\nالرسالة: {$contactData['message']}";

        return $this->sendEmail($customerEmail, $subject, $htmlContent, $textContent);
    }

    /**
     * Store verification code in database
     */
    public function storeVerificationCode($email, $code) {
        try {
            // Create verification_codes table if it doesn't exist
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS verification_codes (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    email VARCHAR(255) NOT NULL,
                    code VARCHAR(6) NOT NULL,
                    expires_at TIMESTAMP NOT NULL,
                    used BOOLEAN DEFAULT FALSE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_email (email),
                    INDEX idx_code (code),
                    INDEX idx_expires (expires_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");

            // Clean up expired codes
            $this->db->exec("DELETE FROM verification_codes WHERE expires_at < NOW()");

            // Insert new code
            $stmt = $this->db->prepare("
                INSERT INTO verification_codes (email, code, expires_at) 
                VALUES (:email, :code, DATE_ADD(NOW(), INTERVAL 10 MINUTE))
            ");
            
            return $stmt->execute([
                'email' => $email,
                'code' => $code
            ]);
        } catch (PDOException $e) {
            error_log('Database error storing verification code: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Verify code from database
     */
    public function verifyCode($email, $code) {
        try {
            $stmt = $this->db->prepare("
                SELECT id FROM verification_codes 
                WHERE email = :email AND code = :code AND expires_at > NOW() AND used = FALSE
                ORDER BY created_at DESC LIMIT 1
            ");
            
            $stmt->execute(['email' => $email, 'code' => $code]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result) {
                // Mark code as used
                $updateStmt = $this->db->prepare("
                    UPDATE verification_codes SET used = TRUE WHERE id = :id
                ");
                $updateStmt->execute(['id' => $result['id']]);
                return true;
            }
            
            return false;
        } catch (PDOException $e) {
            error_log('Database error verifying code: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate verification email template
     */
    private function getVerificationEmailTemplate($code) {
        return "
        <!DOCTYPE html>
        <html dir='rtl' lang='ar'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>كود التحقق</title>
            <style>
                body { font-family: 'Arial', sans-serif; margin: 0; padding: 0; background-color: #f8fafc; }
                .container { max-width: 600px; margin: 0 auto; background: white; }
                .header { background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); color: white; padding: 2rem; text-align: center; }
                .content { padding: 2rem; }
                .code-box { background: #f1f5f9; border: 2px dashed #3b82f6; border-radius: 8px; padding: 1.5rem; text-align: center; margin: 1.5rem 0; }
                .code { font-size: 2rem; font-weight: bold; color: #1e3a8a; letter-spacing: 0.5rem; }
                .footer { background: #f8fafc; padding: 1rem; text-align: center; color: #64748b; font-size: 0.9rem; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🔐 كود التحقق</h1>
                    <p>مرحباً بك في دار زيد</p>
                </div>
                <div class='content'>
                    <h2>كود التحقق الخاص بك</h2>
                    <p>استخدم الكود التالي لإكمال تسجيل الدخول:</p>
                    <div class='code-box'>
                        <div class='code'>$code</div>
                    </div>
                    <p><strong>ملاحظة:</strong> هذا الكود صالح لمدة 10 دقائق فقط.</p>
                    <p>إذا لم تطلب هذا الكود، يرجى تجاهل هذه الرسالة.</p>
                </div>
                <div class='footer'>
                    <p>© 2024 دار زيد. جميع الحقوق محفوظة.</p>
                </div>
            </div>
        </body>
        </html>";
    }

    /**
     * Generate order confirmation email template
     */
    private function getOrderConfirmationTemplate($orderData) {
        $itemsHtml = '';
        foreach ($orderData['items'] as $item) {
            $itemsHtml .= "
                <tr>
                    <td style='padding: 0.75rem; border-bottom: 1px solid #e2e8f0;'>{$item['title']}</td>
                    <td style='padding: 0.75rem; border-bottom: 1px solid #e2e8f0; text-align: center;'>{$item['quantity']}</td>
                    <td style='padding: 0.75rem; border-bottom: 1px solid #e2e8f0; text-align: center;'>{$item['price']} ريال</td>
                    <td style='padding: 0.75rem; border-bottom: 1px solid #e2e8f0; text-align: center;'>{$item['total']} ريال</td>
                </tr>";
        }

        return "
        <!DOCTYPE html>
        <html dir='rtl' lang='ar'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>تأكيد الطلب</title>
            <style>
                body { font-family: 'Arial', sans-serif; margin: 0; padding: 0; background-color: #f8fafc; }
                .container { max-width: 600px; margin: 0 auto; background: white; }
                .header { background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); color: white; padding: 2rem; text-align: center; }
                .content { padding: 2rem; }
                .order-info { background: #f1f5f9; padding: 1.5rem; border-radius: 8px; margin: 1rem 0; }
                .items-table { width: 100%; border-collapse: collapse; margin: 1rem 0; }
                .items-table th { background: #e2e8f0; padding: 0.75rem; text-align: right; font-weight: bold; }
                .total-section { background: #f8fafc; padding: 1rem; border-radius: 8px; margin: 1rem 0; }
                .footer { background: #f8fafc; padding: 1rem; text-align: center; color: #64748b; font-size: 0.9rem; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>✅ تم تأكيد طلبك</h1>
                    <p>شكراً لك لاختيارك دار زيد</p>
                </div>
                <div class='content'>
                    <div class='order-info'>
                        <h3>معلومات الطلب</h3>
                        <p><strong>رقم الطلب:</strong> {$orderData['order_id']}</p>
                        <p><strong>تاريخ الطلب:</strong> " . date('Y-m-d H:i') . "</p>
                        <p><strong>الحالة:</strong> {$orderData['status']}</p>
                    </div>
                    
                    <h3>تفاصيل الطلب</h3>
                    <table class='items-table'>
                        <thead>
                            <tr>
                                <th>المنتج</th>
                                <th>الكمية</th>
                                <th>السعر</th>
                                <th>المجموع</th>
                            </tr>
                        </thead>
                        <tbody>
                            $itemsHtml
                        </tbody>
                    </table>
                    
                    <div class='total-section'>
                        <p><strong>المجموع الفرعي:</strong> {$orderData['subtotal']} ريال</p>
                        <p><strong>تكلفة الشحن:</strong> {$orderData['shipping_cost']} ريال</p>
                        <p><strong>الضريبة:</strong> {$orderData['tax_amount']} ريال</p>
                        <p><strong>المجموع الكلي:</strong> {$orderData['total_amount']} ريال</p>
                    </div>
                    
                    <p>سنقوم بتحديثك بحالة الطلب عبر البريد الإلكتروني.</p>
                </div>
                <div class='footer'>
                    <p>© 2024 دار زيد. جميع الحقوق محفوظة.</p>
                </div>
            </div>
        </body>
        </html>";
    }

    /**
     * Generate order confirmation text version
     */
    private function getOrderConfirmationText($orderData) {
        $text = "تم تأكيد طلبك بنجاح!\n\n";
        $text .= "رقم الطلب: {$orderData['order_id']}\n";
        $text .= "تاريخ الطلب: " . date('Y-m-d H:i') . "\n";
        $text .= "الحالة: {$orderData['status']}\n\n";
        $text .= "تفاصيل الطلب:\n";
        
        foreach ($orderData['items'] as $item) {
            $text .= "- {$item['title']} × {$item['quantity']} = {$item['total']} ريال\n";
        }
        
        $text .= "\nالمجموع الفرعي: {$orderData['subtotal']} ريال\n";
        $text .= "تكلفة الشحن: {$orderData['shipping_cost']} ريال\n";
        $text .= "الضريبة: {$orderData['tax_amount']} ريال\n";
        $text .= "المجموع الكلي: {$orderData['total_amount']} ريال\n\n";
        $text .= "شكراً لك لاختيارك دار زيد!";
        
        return $text;
    }

    /**
     * Generate contact notification template
     */
    private function getContactNotificationTemplate($contactData) {
        return "
        <!DOCTYPE html>
        <html dir='rtl' lang='ar'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>رسالة جديدة من صفحة الاتصال</title>
            <style>
                body { font-family: 'Arial', sans-serif; margin: 0; padding: 0; background-color: #f8fafc; }
                .container { max-width: 600px; margin: 0 auto; background: white; }
                .header { background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%); color: white; padding: 2rem; text-align: center; }
                .content { padding: 2rem; }
                .contact-info { background: #f1f5f9; padding: 1.5rem; border-radius: 8px; margin: 1rem 0; }
                .message-box { background: #f8fafc; padding: 1.5rem; border-radius: 8px; border-right: 4px solid #3b82f6; }
                .footer { background: #f8fafc; padding: 1rem; text-align: center; color: #64748b; font-size: 0.9rem; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>📧 رسالة جديدة</h1>
                    <p>تم استلام رسالة من صفحة الاتصال</p>
                </div>
                <div class='content'>
                    <div class='contact-info'>
                        <h3>معلومات المرسل</h3>
                        <p><strong>الاسم:</strong> {$contactData['name']}</p>
                        <p><strong>البريد الإلكتروني:</strong> {$contactData['email']}</p>
                        <p><strong>الهاتف:</strong> " . ($contactData['phone'] ?: 'غير محدد') . "</p>
                        <p><strong>الموضوع:</strong> " . ($contactData['subject'] ?: 'غير محدد') . "</p>
                    </div>
                    
                    <div class='message-box'>
                        <h3>نص الرسالة</h3>
                        <p>" . nl2br(htmlspecialchars($contactData['message'])) . "</p>
                    </div>
                    
                    <p><strong>وقت الإرسال:</strong> " . date('Y-m-d H:i:s') . "</p>
                </div>
                <div class='footer'>
                    <p>© 2024 دار زيد. جميع الحقوق محفوظة.</p>
                </div>
            </div>
        </body>
        </html>";
    }

    /**
     * Generate contact notification text version
     */
    private function getContactNotificationText($contactData) {
        $text = "رسالة جديدة من صفحة الاتصال\n\n";
        $text .= "الاسم: {$contactData['name']}\n";
        $text .= "البريد الإلكتروني: {$contactData['email']}\n";
        $text .= "الهاتف: " . ($contactData['phone'] ?: 'غير محدد') . "\n";
        $text .= "الموضوع: " . ($contactData['subject'] ?: 'غير محدد') . "\n\n";
        $text .= "نص الرسالة:\n";
        $text .= $contactData['message'] . "\n\n";
        $text .= "وقت الإرسال: " . date('Y-m-d H:i:s');
        
        return $text;
    }

    /**
     * Generate contact confirmation template
     */
    private function getContactConfirmationTemplate($contactData) {
        return "
        <!DOCTYPE html>
        <html dir='rtl' lang='ar'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>تم استلام رسالتك</title>
            <style>
                body { font-family: 'Arial', sans-serif; margin: 0; padding: 0; background-color: #f8fafc; }
                .container { max-width: 600px; margin: 0 auto; background: white; }
                .header { background: linear-gradient(135deg, #059669 0%, #10b981 100%); color: white; padding: 2rem; text-align: center; }
                .content { padding: 2rem; }
                .message-summary { background: #f1f5f9; padding: 1.5rem; border-radius: 8px; margin: 1rem 0; }
                .footer { background: #f8fafc; padding: 1rem; text-align: center; color: #64748b; font-size: 0.9rem; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>✅ تم استلام رسالتك</h1>
                    <p>شكراً لك على تواصلك معنا</p>
                </div>
                <div class='content'>
                    <p>عزيزي/عزيزتي {$contactData['name']}،</p>
                    <p>تم استلام رسالتك بنجاح وسنرد عليك في أقرب وقت ممكن.</p>
                    
                    <div class='message-summary'>
                        <h3>ملخص رسالتك</h3>
                        <p><strong>الموضوع:</strong> " . ($contactData['subject'] ?: 'غير محدد') . "</p>
                        <p><strong>الرسالة:</strong></p>
                        <p>" . nl2br(htmlspecialchars($contactData['message'])) . "</p>
                    </div>
                    
                    <p>نقدر وقتك واهتمامك بدار زيد.</p>
                </div>
                <div class='footer'>
                    <p>© 2024 دار زيد. جميع الحقوق محفوظة.</p>
                </div>
            </div>
        </body>
        </html>";
    }
}
?>






