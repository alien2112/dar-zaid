<?php
require_once __DIR__ . '/../config/database.php';

class PHPMailService {
    private $fromEmail = 'Dar.zaid.2022@gmail.com';
    private $fromName = 'دار زيد';
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /**
     * Send email using PHP's mail() function
     */
    public function sendEmail($to, $subject, $htmlContent, $textContent = null) {
        // Basic headers
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: {$this->fromName} <{$this->fromEmail}>\r\n";
        $headers .= "Reply-To: {$this->fromEmail}\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

        // Use HTML content or text content
        $body = $htmlContent ?: $textContent;

        // Send email
        return mail($to, $subject, $body, $headers);
    }

    /**
     * Send verification code for signup/login
     */
    public function sendVerificationCode($email, $code, $purpose = 'verification') {
        $subject = $purpose === 'signup' ? 'كود التحقق - تفعيل الحساب | دار زيد' : 'كود التحقق - تسجيل الدخول | دار زيد';
        $htmlContent = $this->getSimpleVerificationTemplate($code, $purpose);
        $textContent = "كود التحقق الخاص بك هو: $code\n\nهذا الكود صالح لمدة 10 دقائق.\n\nشكراً لاستخدامك دار زيد";

        // Store verification code in database
        $this->storeVerificationCode($email, $code, $purpose);

        return $this->sendEmail($email, $subject, $htmlContent, $textContent);
    }

    /**
     * Send signup welcome email
     */
    public function sendWelcomeEmail($email, $name) {
        $subject = 'مرحباً بك في دار زيد';
        $htmlContent = $this->getSimpleWelcomeTemplate($name);
        $textContent = "مرحباً بك $name في دار زيد!\n\nنحن سعداء لانضمامك إلى عائلة دار زيد.\n\nشكراً لاختيارك دار زيد!";

        return $this->sendEmail($email, $subject, $htmlContent, $textContent);
    }

    /**
     * Send password reset email
     */
    public function sendPasswordResetEmail($email, $resetToken) {
        $subject = 'إعادة تعيين كلمة المرور | دار زيد';
        $resetUrl = "https://darzaid.com/reset-password?token=" . urlencode($resetToken);
        $htmlContent = $this->getSimplePasswordResetTemplate($resetUrl);
        $textContent = "تم طلب إعادة تعيين كلمة المرور.\n\nالرابط: $resetUrl\n\nهذا الرابط صالح لمدة 1 ساعة.";

        return $this->sendEmail($email, $subject, $htmlContent, $textContent);
    }

    /**
     * Send order confirmation email
     */
    public function sendOrderConfirmation($orderData) {
        $customerEmail = $orderData['customer_info']['email'];
        $subject = 'تأكيد الطلب #' . $orderData['order_id'] . ' | دار زيد';
        $htmlContent = $this->getSimpleOrderTemplate($orderData);

        $textContent = "تم تأكيد طلبك #{$orderData['order_id']}\n";
        $textContent .= "المجموع الكلي: {$orderData['total_amount']} ريال\n";
        $textContent .= "شكراً لاختيارك دار زيد!";

        return $this->sendEmail($customerEmail, $subject, $htmlContent, $textContent);
    }

    /**
     * Send order status update email
     */
    public function sendOrderStatusUpdate($orderData, $newStatus) {
        $customerEmail = $orderData['customer_info']['email'];
        $subject = 'تحديث حالة الطلب #' . $orderData['order_id'] . ' | دار زيد';
        $htmlContent = $this->getSimpleStatusUpdateTemplate($orderData, $newStatus);
        $textContent = "تم تحديث حالة طلبك #{$orderData['order_id']} إلى: $newStatus";

        return $this->sendEmail($customerEmail, $subject, $htmlContent, $textContent);
    }

    /**
     * Send contact form notification to admin
     */
    public function sendContactNotification($contactData) {
        $adminEmail = 'Dar.zaid.2022@gmail.com';
        $subject = 'رسالة جديدة من صفحة الاتصال | دار زيد';
        $htmlContent = $this->getSimpleContactNotificationTemplate($contactData);

        $textContent = "رسالة جديدة:\n";
        $textContent .= "الاسم: {$contactData['name']}\n";
        $textContent .= "البريد: {$contactData['email']}\n";
        $textContent .= "الرسالة: {$contactData['message']}";

        return $this->sendEmail($adminEmail, $subject, $htmlContent, $textContent);
    }

    /**
     * Send contact form confirmation to customer
     */
    public function sendContactConfirmation($contactData) {
        $customerEmail = $contactData['email'];
        $subject = 'تم استلام رسالتك | دار زيد';
        $htmlContent = $this->getSimpleContactConfirmationTemplate($contactData);
        $textContent = "شكراً لك على تواصلك معنا. سنرد عليك قريباً.";

        return $this->sendEmail($customerEmail, $subject, $htmlContent, $textContent);
    }

    /**
     * Store verification code in database
     */
    public function storeVerificationCode($email, $code, $purpose = 'verification') {
        try {
            // Create table if not exists
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS verification_codes (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    email VARCHAR(255) NOT NULL,
                    code VARCHAR(6) NOT NULL,
                    purpose VARCHAR(50) DEFAULT 'verification',
                    expires_at TIMESTAMP NOT NULL,
                    used BOOLEAN DEFAULT FALSE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");

            // Clean expired codes
            $this->db->exec("DELETE FROM verification_codes WHERE expires_at < NOW()");

            // Insert new code
            $stmt = $this->db->prepare("
                INSERT INTO verification_codes (email, code, purpose, expires_at)
                VALUES (:email, :code, :purpose, DATE_ADD(NOW(), INTERVAL 10 MINUTE))
            ");

            return $stmt->execute([
                'email' => $email,
                'code' => $code,
                'purpose' => $purpose
            ]);
        } catch (PDOException $e) {
            error_log('Database error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Verify code from database
     */
    public function verifyCode($email, $code, $purpose = 'verification') {
        try {
            $stmt = $this->db->prepare("
                SELECT id FROM verification_codes
                WHERE email = :email AND code = :code AND purpose = :purpose
                AND expires_at > NOW() AND used = FALSE
                LIMIT 1
            ");

            $stmt->execute(['email' => $email, 'code' => $code, 'purpose' => $purpose]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result) {
                // Mark as used
                $updateStmt = $this->db->prepare("UPDATE verification_codes SET used = TRUE WHERE id = :id");
                $updateStmt->execute(['id' => $result['id']]);
                return true;
            }

            return false;
        } catch (PDOException $e) {
            error_log('Database error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate verification code
     */
    public function generateVerificationCode() {
        return sprintf('%06d', mt_rand(100000, 999999));
    }

    // Simple HTML templates for PHP mail

    private function getSimpleVerificationTemplate($code, $purpose) {
        $title = $purpose === 'signup' ? 'تفعيل الحساب' : 'تسجيل الدخول';
        return "
        <html dir='rtl'>
        <head><meta charset='UTF-8'></head>
        <body style='font-family: Arial; text-align: center; padding: 20px;'>
            <h2 style='color: #1e3a8a;'>$title - دار زيد</h2>
            <div style='background: #f0f9ff; padding: 20px; border-radius: 10px; margin: 20px 0;'>
                <h1 style='color: #1e3a8a; font-size: 2em; letter-spacing: 5px;'>$code</h1>
            </div>
            <p>هذا الكود صالح لمدة 10 دقائق فقط.</p>
            <p style='color: #666;'>© 2024 دار زيد</p>
        </body>
        </html>";
    }

    private function getSimpleWelcomeTemplate($name) {
        return "
        <html dir='rtl'>
        <head><meta charset='UTF-8'></head>
        <body style='font-family: Arial; text-align: center; padding: 20px;'>
            <h2 style='color: #10b981;'>مرحباً بك في دار زيد</h2>
            <p>عزيز/ة <strong>$name</strong></p>
            <p>نحن سعداء لانضمامك إلى عائلة دار زيد!</p>
            <div style='background: #f0fdf4; padding: 15px; border-radius: 10px; margin: 20px 0;'>
                <p>يمكنك الآن تصفح مجموعتنا الواسعة من الكتب والاستفادة من خدماتنا.</p>
            </div>
            <p style='color: #666;'>© 2024 دار زيد</p>
        </body>
        </html>";
    }

    private function getSimplePasswordResetTemplate($resetUrl) {
        return "
        <html dir='rtl'>
        <head><meta charset='UTF-8'></head>
        <body style='font-family: Arial; text-align: center; padding: 20px;'>
            <h2 style='color: #dc2626;'>إعادة تعيين كلمة المرور</h2>
            <p>تم طلب إعادة تعيين كلمة المرور لحسابك.</p>
            <div style='background: #fef2f2; padding: 20px; border-radius: 10px; margin: 20px 0;'>
                <a href='$resetUrl' style='color: white; background: #dc2626; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>إعادة تعيين كلمة المرور</a>
            </div>
            <p>هذا الرابط صالح لمدة ساعة واحدة.</p>
            <p style='color: #666;'>© 2024 دار زيد</p>
        </body>
        </html>";
    }

    private function getSimpleOrderTemplate($orderData) {
        return "
        <html dir='rtl'>
        <head><meta charset='UTF-8'></head>
        <body style='font-family: Arial; padding: 20px;'>
            <h2 style='color: #10b981;'>تأكيد الطلب #{$orderData['order_id']}</h2>
            <div style='background: #f0fdf4; padding: 15px; border-radius: 10px; margin: 20px 0;'>
                <p><strong>المجموع الكلي:</strong> {$orderData['total_amount']} ريال</p>
                <p><strong>الحالة:</strong> {$orderData['status']}</p>
            </div>
            <p>شكراً لاختيارك دار زيد!</p>
            <p style='color: #666;'>© 2024 دار زيد</p>
        </body>
        </html>";
    }

    private function getSimpleStatusUpdateTemplate($orderData, $newStatus) {
        return "
        <html dir='rtl'>
        <head><meta charset='UTF-8'></head>
        <body style='font-family: Arial; text-align: center; padding: 20px;'>
            <h2 style='color: #3b82f6;'>تحديث حالة الطلب</h2>
            <div style='background: #f0f9ff; padding: 15px; border-radius: 10px; margin: 20px 0;'>
                <p><strong>رقم الطلب:</strong> #{$orderData['order_id']}</p>
                <p><strong>الحالة الجديدة:</strong> $newStatus</p>
            </div>
            <p style='color: #666;'>© 2024 دار زيد</p>
        </body>
        </html>";
    }

    private function getSimpleContactNotificationTemplate($contactData) {
        return "
        <html dir='rtl'>
        <head><meta charset='UTF-8'></head>
        <body style='font-family: Arial; padding: 20px;'>
            <h2 style='color: #dc2626;'>رسالة جديدة من صفحة الاتصال</h2>
            <div style='background: #f9f9f9; padding: 15px; border-radius: 10px; margin: 20px 0;'>
                <p><strong>الاسم:</strong> {$contactData['name']}</p>
                <p><strong>البريد:</strong> {$contactData['email']}</p>
                <p><strong>الرسالة:</strong></p>
                <p>{$contactData['message']}</p>
            </div>
            <p style='color: #666;'>© 2024 دار زيد</p>
        </body>
        </html>";
    }

    private function getSimpleContactConfirmationTemplate($contactData) {
        return "
        <html dir='rtl'>
        <head><meta charset='UTF-8'></head>
        <body style='font-family: Arial; text-align: center; padding: 20px;'>
            <h2 style='color: #10b981;'>تم استلام رسالتك</h2>
            <p>عزيز/ة <strong>{$contactData['name']}</strong></p>
            <div style='background: #f0fdf4; padding: 15px; border-radius: 10px; margin: 20px 0;'>
                <p>تم استلام رسالتك بنجاح وسنرد عليك في أقرب وقت ممكن.</p>
            </div>
            <p style='color: #666;'>© 2024 دار زيد</p>
        </body>
        </html>";
    }
}
?>