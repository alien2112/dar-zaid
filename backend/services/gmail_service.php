<?php
require_once __DIR__ . '/../config/database.php';

class GmailService {
    private $smtpHost = 'smtp.gmail.com';
    private $smtpPort = 587;
    private $smtpUsername = 'Dar.zaid.2022@gmail.com';
    private $smtpPassword;
    private $fromEmail = 'Dar.zaid.2022@gmail.com';
    private $fromName = 'دار زيد';
    private $db;

    public function __construct() {
        // Get app password from environment or config
        $this->smtpPassword = getenv('GMAIL_APP_PASSWORD') ?: '';

        $database = new Database();
        $this->db = $database->getConnection();
    }

    /**
     * Send email using Gmail SMTP
     */
    public function sendEmail($to, $subject, $htmlContent, $textContent = null) {
        if (empty($this->smtpPassword)) {
            error_log('Gmail app password not configured');
            return false;
        }

        // Create email headers
        $boundary = uniqid('np');

        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "From: {$this->fromName} <{$this->fromEmail}>\r\n";
        $headers .= "Reply-To: {$this->fromEmail}\r\n";
        $headers .= "Content-Type: multipart/alternative; boundary=\"$boundary\"\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

        // Create email body
        $body = "--$boundary\r\n";

        // Add text version
        if ($textContent) {
            $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
            $body .= $textContent . "\r\n";
            $body .= "--$boundary\r\n";
        }

        // Add HTML version
        $body .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $body .= $htmlContent . "\r\n";
        $body .= "--$boundary--\r\n";

        // Use PHPMailer-like approach with sockets
        return $this->sendViaSMTP($to, $subject, $body, $headers);
    }

    /**
     * Send email via SMTP socket connection
     */
    private function sendViaSMTP($to, $subject, $body, $headers) {
        $socket = fsockopen($this->smtpHost, $this->smtpPort, $errno, $errstr, 30);

        if (!$socket) {
            error_log("SMTP connection failed: $errstr ($errno)");
            return false;
        }

        try {
            // Read initial response
            $response = fgets($socket, 512);
            if (substr($response, 0, 3) != '220') {
                throw new Exception("SMTP server not ready: $response");
            }

            // EHLO
            fputs($socket, "EHLO " . $_SERVER['HTTP_HOST'] . "\r\n");
            $response = fgets($socket, 512);

            // STARTTLS
            fputs($socket, "STARTTLS\r\n");
            $response = fgets($socket, 512);
            if (substr($response, 0, 3) != '220') {
                throw new Exception("STARTTLS failed: $response");
            }

            // Enable crypto
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new Exception("Failed to enable TLS encryption");
            }

            // EHLO again after TLS
            fputs($socket, "EHLO " . $_SERVER['HTTP_HOST'] . "\r\n");
            $response = fgets($socket, 512);

            // AUTH LOGIN
            fputs($socket, "AUTH LOGIN\r\n");
            $response = fgets($socket, 512);
            if (substr($response, 0, 3) != '334') {
                throw new Exception("AUTH LOGIN failed: $response");
            }

            // Send username
            fputs($socket, base64_encode($this->smtpUsername) . "\r\n");
            $response = fgets($socket, 512);
            if (substr($response, 0, 3) != '334') {
                throw new Exception("Username authentication failed: $response");
            }

            // Send password
            fputs($socket, base64_encode($this->smtpPassword) . "\r\n");
            $response = fgets($socket, 512);
            if (substr($response, 0, 3) != '235') {
                throw new Exception("Password authentication failed: $response");
            }

            // MAIL FROM
            fputs($socket, "MAIL FROM: <{$this->fromEmail}>\r\n");
            $response = fgets($socket, 512);
            if (substr($response, 0, 3) != '250') {
                throw new Exception("MAIL FROM failed: $response");
            }

            // RCPT TO
            fputs($socket, "RCPT TO: <$to>\r\n");
            $response = fgets($socket, 512);
            if (substr($response, 0, 3) != '250') {
                throw new Exception("RCPT TO failed: $response");
            }

            // DATA
            fputs($socket, "DATA\r\n");
            $response = fgets($socket, 512);
            if (substr($response, 0, 3) != '354') {
                throw new Exception("DATA command failed: $response");
            }

            // Send email content
            $emailContent = "To: $to\r\n";
            $emailContent .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
            $emailContent .= $headers . "\r\n";
            $emailContent .= $body;
            $emailContent .= "\r\n.\r\n";

            fputs($socket, $emailContent);
            $response = fgets($socket, 512);
            if (substr($response, 0, 3) != '250') {
                throw new Exception("Email sending failed: $response");
            }

            // QUIT
            fputs($socket, "QUIT\r\n");
            fclose($socket);

            return true;

        } catch (Exception $e) {
            error_log("SMTP Error: " . $e->getMessage());
            fclose($socket);
            return false;
        }
    }

    /**
     * Send verification code for signup/login
     */
    public function sendVerificationCode($email, $code, $purpose = 'verification') {
        $subject = $purpose === 'signup' ? 'كود التحقق - تفعيل الحساب' : 'كود التحقق - تسجيل الدخول';
        $htmlContent = $this->getVerificationEmailTemplate($code, $purpose);
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
        $htmlContent = $this->getWelcomeEmailTemplate($name);
        $textContent = "مرحباً بك $name في دار زيد!\n\nنحن سعداء لانضمامك إلى عائلة دار زيد. يمكنك الآن تصفح مجموعتنا الواسعة من الكتب والاستفادة من خدماتنا المميزة.\n\nشكراً لاختيارك دار زيد!";

        return $this->sendEmail($email, $subject, $htmlContent, $textContent);
    }

    /**
     * Send password reset email
     */
    public function sendPasswordResetEmail($email, $resetToken) {
        $subject = 'إعادة تعيين كلمة المرور - دار زيد';
        $htmlContent = $this->getPasswordResetTemplate($resetToken);
        $textContent = "تم طلب إعادة تعيين كلمة المرور لحسابك.\n\nالرمز المميز: $resetToken\n\nهذا الرمز صالح لمدة 1 ساعة.\n\nإذا لم تطلب إعادة تعيين كلمة المرور، يرجى تجاهل هذه الرسالة.";

        return $this->sendEmail($email, $subject, $htmlContent, $textContent);
    }

    /**
     * Send order confirmation email
     */
    public function sendOrderConfirmation($orderData) {
        $customerEmail = $orderData['customer_info']['email'];
        $subject = 'تأكيد الطلب #' . $orderData['order_id'] . ' - دار زيد';
        $htmlContent = $this->getOrderConfirmationTemplate($orderData);
        $textContent = $this->getOrderConfirmationText($orderData);

        return $this->sendEmail($customerEmail, $subject, $htmlContent, $textContent);
    }

    /**
     * Send order status update email
     */
    public function sendOrderStatusUpdate($orderData, $newStatus) {
        $customerEmail = $orderData['customer_info']['email'];
        $subject = 'تحديث حالة الطلب #' . $orderData['order_id'] . ' - دار زيد';
        $htmlContent = $this->getOrderStatusUpdateTemplate($orderData, $newStatus);
        $textContent = "تم تحديث حالة طلبك #{$orderData['order_id']} إلى: $newStatus\n\nشكراً لاختيارك دار زيد!";

        return $this->sendEmail($customerEmail, $subject, $htmlContent, $textContent);
    }

    /**
     * Send contact form notification to admin
     */
    public function sendContactNotification($contactData) {
        $adminEmail = 'Dar.zaid.2022@gmail.com'; // Send to company email
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
    public function storeVerificationCode($email, $code, $purpose = 'verification') {
        try {
            // Create verification_codes table if it doesn't exist
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS verification_codes (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    email VARCHAR(255) NOT NULL,
                    code VARCHAR(6) NOT NULL,
                    purpose VARCHAR(50) DEFAULT 'verification',
                    expires_at TIMESTAMP NOT NULL,
                    used BOOLEAN DEFAULT FALSE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_email (email),
                    INDEX idx_code (code),
                    INDEX idx_expires (expires_at),
                    INDEX idx_purpose (purpose)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");

            // Clean up expired codes
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
            error_log('Database error storing verification code: ' . $e->getMessage());
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
                ORDER BY created_at DESC LIMIT 1
            ");

            $stmt->execute([
                'email' => $email,
                'code' => $code,
                'purpose' => $purpose
            ]);
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
     * Generate random verification code
     */
    public function generateVerificationCode() {
        return sprintf('%06d', mt_rand(100000, 999999));
    }

    /**
     * Clean up expired verification codes
     */
    public function cleanupExpiredCodes() {
        try {
            $stmt = $this->db->prepare("DELETE FROM verification_codes WHERE expires_at < NOW()");
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('Database error cleaning up codes: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate verification email template
     */
    private function getVerificationEmailTemplate($code, $purpose = 'verification') {
        $title = $purpose === 'signup' ? 'تفعيل الحساب' : 'تسجيل الدخول';
        $message = $purpose === 'signup' ? 'استخدم الكود التالي لتفعيل حسابك الجديد:' : 'استخدم الكود التالي لإكمال تسجيل الدخول:';

        return "
        <!DOCTYPE html>
        <html dir='rtl' lang='ar'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>كود التحقق</title>
            <style>
                body { font-family: 'Arial', sans-serif; margin: 0; padding: 0; background-color: #f8fafc; }
                .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
                .header { background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); color: white; padding: 2rem; text-align: center; }
                .content { padding: 2rem; }
                .code-box { background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%); border: 2px dashed #3b82f6; border-radius: 12px; padding: 2rem; text-align: center; margin: 2rem 0; }
                .code { font-size: 2.5rem; font-weight: bold; color: #1e3a8a; letter-spacing: 0.5rem; text-shadow: 2px 2px 4px rgba(0,0,0,0.1); }
                .footer { background: #f8fafc; padding: 1.5rem; text-align: center; color: #64748b; font-size: 0.9rem; }
                .logo { width: 60px; height: 60px; margin: 0 auto 1rem; background: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; }
                .warning { background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 1rem; margin: 1rem 0; color: #dc2626; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <div class='logo'>📚</div>
                    <h1>🔐 $title</h1>
                    <p>مرحباً بك في دار زيد</p>
                </div>
                <div class='content'>
                    <h2>كود التحقق الخاص بك</h2>
                    <p>$message</p>
                    <div class='code-box'>
                        <div class='code'>$code</div>
                    </div>
                    <div class='warning'>
                        <p><strong>⚠️ مهم:</strong> هذا الكود صالح لمدة 10 دقائق فقط.</p>
                    </div>
                    <p>إذا لم تطلب هذا الكود، يرجى تجاهل هذه الرسالة وإبلاغنا فوراً.</p>
                    <p>للحصول على المساعدة، تواصل معنا على: <strong>Dar.zaid.2022@gmail.com</strong></p>
                </div>
                <div class='footer'>
                    <p>© 2024 دار زيد. جميع الحقوق محفوظة.</p>
                    <p>هذه رسالة تلقائية، يرجى عدم الرد عليها.</p>
                </div>
            </div>
        </body>
        </html>";
    }

    /**
     * Generate welcome email template
     */
    private function getWelcomeEmailTemplate($name) {
        return "
        <!DOCTYPE html>
        <html dir='rtl' lang='ar'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>مرحباً بك في دار زيد</title>
            <style>
                body { font-family: 'Arial', sans-serif; margin: 0; padding: 0; background-color: #f8fafc; }
                .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
                .header { background: linear-gradient(135deg, #059669 0%, #10b981 100%); color: white; padding: 2rem; text-align: center; }
                .content { padding: 2rem; }
                .welcome-box { background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%); border-radius: 12px; padding: 2rem; margin: 1.5rem 0; text-align: center; }
                .features { display: grid; gap: 1rem; margin: 2rem 0; }
                .feature { background: #f8fafc; padding: 1rem; border-radius: 8px; border-right: 4px solid #10b981; }
                .footer { background: #f8fafc; padding: 1.5rem; text-align: center; color: #64748b; font-size: 0.9rem; }
                .logo { width: 80px; height: 80px; margin: 0 auto 1rem; background: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 3rem; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <div class='logo'>📚</div>
                    <h1>🎉 مرحباً بك!</h1>
                    <p>أهلاً وسهلاً في دار زيد</p>
                </div>
                <div class='content'>
                    <div class='welcome-box'>
                        <h2>عزيز/ة $name</h2>
                        <p>نحن سعداء جداً لانضمامك إلى عائلة دار زيد!</p>
                    </div>

                    <h3>ماذا يمكنك فعله الآن:</h3>
                    <div class='features'>
                        <div class='feature'>
                            <h4>📖 تصفح الكتب</h4>
                            <p>اكتشف مجموعتنا الواسعة من الكتب في مختلف المجالات</p>
                        </div>
                        <div class='feature'>
                            <h4>🛒 التسوق الآمن</h4>
                            <p>استمتع بتجربة تسوق آمنة وسهلة مع خيارات دفع متعددة</p>
                        </div>
                        <div class='feature'>
                            <h4>🚚 التوصيل السريع</h4>
                            <p>احصل على كتبك المفضلة بسرعة وأمان</p>
                        </div>
                        <div class='feature'>
                            <h4>⭐ العروض الخاصة</h4>
                            <p>استفد من العروض والخصومات الحصرية للأعضاء</p>
                        </div>
                    </div>

                    <p>إذا كان لديك أي استفسارات، لا تتردد في التواصل معنا!</p>
                </div>
                <div class='footer'>
                    <p>© 2024 دار زيد. جميع الحقوق محفوظة.</p>
                    <p>تواصل معنا: Dar.zaid.2022@gmail.com</p>
                </div>
            </div>
        </body>
        </html>";
    }

    /**
     * Generate password reset email template
     */
    private function getPasswordResetTemplate($resetToken) {
        $resetUrl = "https://darzaid.com/reset-password?token=" . urlencode($resetToken);

        return "
        <!DOCTYPE html>
        <html dir='rtl' lang='ar'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>إعادة تعيين كلمة المرور</title>
            <style>
                body { font-family: 'Arial', sans-serif; margin: 0; padding: 0; background-color: #f8fafc; }
                .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
                .header { background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%); color: white; padding: 2rem; text-align: center; }
                .content { padding: 2rem; }
                .reset-box { background: #fef2f2; border: 2px solid #fecaca; border-radius: 12px; padding: 2rem; text-align: center; margin: 2rem 0; }
                .reset-button { display: inline-block; background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%); color: white; padding: 1rem 2rem; text-decoration: none; border-radius: 8px; font-weight: bold; margin: 1rem 0; }
                .footer { background: #f8fafc; padding: 1.5rem; text-align: center; color: #64748b; font-size: 0.9rem; }
                .warning { background: #fffbeb; border: 1px solid #fed7aa; border-radius: 8px; padding: 1rem; margin: 1rem 0; color: #92400e; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🔒 إعادة تعيين كلمة المرور</h1>
                    <p>دار زيد</p>
                </div>
                <div class='content'>
                    <h2>طلب إعادة تعيين كلمة المرور</h2>
                    <p>تم طلب إعادة تعيين كلمة المرور لحسابك في دار زيد.</p>

                    <div class='reset-box'>
                        <p>اضغط على الزر أدناه لإعادة تعيين كلمة المرور:</p>
                        <a href='$resetUrl' class='reset-button'>إعادة تعيين كلمة المرور</a>
                        <p><small>أو انسخ الرابط التالي: $resetUrl</small></p>
                    </div>

                    <div class='warning'>
                        <p><strong>⚠️ مهم:</strong> هذا الرابط صالح لمدة ساعة واحدة فقط.</p>
                    </div>

                    <p>إذا لم تطلب إعادة تعيين كلمة المرور، يرجى تجاهل هذه الرسالة.</p>
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
                .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
                .header { background: linear-gradient(135deg, #059669 0%, #10b981 100%); color: white; padding: 2rem; text-align: center; }
                .content { padding: 2rem; }
                .order-info { background: #f1f5f9; padding: 1.5rem; border-radius: 8px; margin: 1rem 0; }
                .items-table { width: 100%; border-collapse: collapse; margin: 1rem 0; }
                .items-table th { background: #e2e8f0; padding: 0.75rem; text-align: right; font-weight: bold; }
                .total-section { background: #f8fafc; padding: 1rem; border-radius: 8px; margin: 1rem 0; border-right: 4px solid #10b981; }
                .footer { background: #f8fafc; padding: 1.5rem; text-align: center; color: #64748b; font-size: 0.9rem; }
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
                        <p><strong>رقم الطلب:</strong> #{$orderData['order_id']}</p>
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
                        <p style='font-size: 1.2rem; color: #059669;'><strong>المجموع الكلي:</strong> {$orderData['total_amount']} ريال</p>
                    </div>

                    <p>سنقوم بمعالجة طلبك وإرسال تحديثات حالة الطلب عبر البريد الإلكتروني.</p>
                    <p>إذا كان لديك أي استفسارات، تواصل معنا على: <strong>Dar.zaid.2022@gmail.com</strong></p>
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
        $text .= "رقم الطلب: #{$orderData['order_id']}\n";
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
     * Generate order status update template
     */
    private function getOrderStatusUpdateTemplate($orderData, $newStatus) {
        $statusMessages = [
            'processing' => 'جاري تحضير طلبك',
            'shipped' => 'تم شحن طلبك',
            'delivered' => 'تم تسليم طلبك',
            'cancelled' => 'تم إلغاء طلبك'
        ];

        $statusMessage = $statusMessages[$newStatus] ?? 'تم تحديث حالة طلبك';

        return "
        <!DOCTYPE html>
        <html dir='rtl' lang='ar'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>تحديث حالة الطلب</title>
            <style>
                body { font-family: 'Arial', sans-serif; margin: 0; padding: 0; background-color: #f8fafc; }
                .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
                .header { background: linear-gradient(135deg, #3b82f6 0%, #1e3a8a 100%); color: white; padding: 2rem; text-align: center; }
                .content { padding: 2rem; }
                .status-box { background: #f0f9ff; border: 2px solid #3b82f6; border-radius: 12px; padding: 2rem; text-align: center; margin: 2rem 0; }
                .footer { background: #f8fafc; padding: 1.5rem; text-align: center; color: #64748b; font-size: 0.9rem; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>📦 تحديث حالة الطلب</h1>
                    <p>دار زيد</p>
                </div>
                <div class='content'>
                    <div class='status-box'>
                        <h2>$statusMessage</h2>
                        <p><strong>رقم الطلب:</strong> #{$orderData['order_id']}</p>
                        <p><strong>الحالة الجديدة:</strong> $newStatus</p>
                    </div>

                    <p>شكراً لاختيارك دار زيد!</p>
                    <p>إذا كان لديك أي استفسارات، تواصل معنا على: <strong>Dar.zaid.2022@gmail.com</strong></p>
                </div>
                <div class='footer'>
                    <p>© 2024 دار زيد. جميع الحقوق محفوظة.</p>
                </div>
            </div>
        </body>
        </html>";
    }

    /**
     * Generate contact notification template for admin
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
                .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
                .header { background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%); color: white; padding: 2rem; text-align: center; }
                .content { padding: 2rem; }
                .contact-info { background: #f1f5f9; padding: 1.5rem; border-radius: 8px; margin: 1rem 0; }
                .message-box { background: #f8fafc; padding: 1.5rem; border-radius: 8px; border-right: 4px solid #3b82f6; margin: 1rem 0; }
                .footer { background: #f8fafc; padding: 1.5rem; text-align: center; color: #64748b; font-size: 0.9rem; }
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
                        <p><strong>الهاتف:</strong> " . ($contactData['phone'] ?? 'غير محدد') . "</p>
                        <p><strong>الموضوع:</strong> " . ($contactData['subject'] ?? 'غير محدد') . "</p>
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
        $text .= "الهاتف: " . ($contactData['phone'] ?? 'غير محدد') . "\n";
        $text .= "الموضوع: " . ($contactData['subject'] ?? 'غير محدد') . "\n\n";
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
                .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
                .header { background: linear-gradient(135deg, #059669 0%, #10b981 100%); color: white; padding: 2rem; text-align: center; }
                .content { padding: 2rem; }
                .message-summary { background: #f1f5f9; padding: 1.5rem; border-radius: 8px; margin: 1rem 0; }
                .footer { background: #f8fafc; padding: 1.5rem; text-align: center; color: #64748b; font-size: 0.9rem; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>✅ تم استلام رسالتك</h1>
                    <p>شكراً لك على تواصلك معنا</p>
                </div>
                <div class='content'>
                    <p>عزيز/ة {$contactData['name']}،</p>
                    <p>تم استلام رسالتك بنجاح وسنرد عليك في أقرب وقت ممكن.</p>

                    <div class='message-summary'>
                        <h3>ملخص رسالتك</h3>
                        <p><strong>الموضوع:</strong> " . ($contactData['subject'] ?? 'غير محدد') . "</p>
                        <p><strong>الرسالة:</strong></p>
                        <p>" . nl2br(htmlspecialchars($contactData['message'])) . "</p>
                    </div>

                    <p>نقدر وقتك واهتمامك بدار زيد.</p>
                    <p>للاستفسارات العاجلة، يمكنك التواصل معنا على: <strong>Dar.zaid.2022@gmail.com</strong></p>
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