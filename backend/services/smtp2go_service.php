<?php
require_once __DIR__ . '/../config/database.php';

class SMTP2GOService {
    private $apiKey;
    private $fromEmail = 'Dar.zaid.2022@gmail.com';
    private $fromName = 'دار زيد';
    private $db;

    public function __construct() {
        // Get API key from environment or config
        $this->apiKey = getenv('SMTP2GO_API_KEY') ?: '';

        $database = new Database();
        $this->db = $database->getConnection();
    }

    /**
     * Send email using SMTP2GO API
     */
    public function sendEmail($to, $subject, $htmlContent, $textContent = null) {
        if (empty($this->apiKey)) {
            error_log('SMTP2GO API key not configured');
            return false;
        }

        $data = [
            'api_key' => $this->apiKey,
            'to' => [$to],
            'sender' => $this->fromEmail,
            'subject' => $subject,
            'html_body' => $htmlContent,
            'text_body' => $textContent ?: strip_tags($htmlContent)
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.smtp2go.com/v3/email/send');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'X-Smtp2go-Api-Key: ' . $this->apiKey
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            $responseData = json_decode($response, true);
            return isset($responseData['data']['succeeded']) && $responseData['data']['succeeded'] > 0;
        } else {
            error_log('SMTP2GO API error: ' . $response);
            return false;
        }
    }

    /**
     * Send verification code for signup/login
     */
    public function sendVerificationCode($email, $code, $purpose = 'verification') {
        $subject = $purpose === 'signup' ? 'كود التحقق - تفعيل الحساب | دار زيد' : 'كود التحقق - تسجيل الدخول | دار زيد';
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
        $subject = 'مرحباً بك في دار زيد 🎉';
        $htmlContent = $this->getWelcomeEmailTemplate($name);
        $textContent = "مرحباً بك $name في دار زيد!\n\nنحن سعداء لانضمامك إلى عائلة دار زيد. يمكنك الآن تصفح مجموعتنا الواسعة من الكتب والاستفادة من خدماتنا المميزة.\n\nشكراً لاختيارك دار زيد!";

        return $this->sendEmail($email, $subject, $htmlContent, $textContent);
    }

    /**
     * Send password reset email
     */
    public function sendPasswordResetEmail($email, $resetToken) {
        $subject = 'إعادة تعيين كلمة المرور | دار زيد';
        $htmlContent = $this->getPasswordResetTemplate($resetToken);
        $textContent = "تم طلب إعادة تعيين كلمة المرور لحسابك.\n\nالرمز المميز: $resetToken\n\nهذا الرمز صالح لمدة 1 ساعة.\n\nإذا لم تطلب إعادة تعيين كلمة المرور، يرجى تجاهل هذه الرسالة.";

        return $this->sendEmail($email, $subject, $htmlContent, $textContent);
    }

    /**
     * Send order confirmation email
     */
    public function sendOrderConfirmation($orderData) {
        $customerEmail = $orderData['customer_info']['email'];
        $subject = 'تأكيد الطلب #' . $orderData['order_id'] . ' | دار زيد';
        $htmlContent = $this->getOrderConfirmationTemplate($orderData);
        $textContent = $this->getOrderConfirmationText($orderData);

        return $this->sendEmail($customerEmail, $subject, $htmlContent, $textContent);
    }

    /**
     * Send order status update email
     */
    public function sendOrderStatusUpdate($orderData, $newStatus) {
        $customerEmail = $orderData['customer_info']['email'];
        $subject = 'تحديث حالة الطلب #' . $orderData['order_id'] . ' | دار زيد';
        $htmlContent = $this->getOrderStatusUpdateTemplate($orderData, $newStatus);
        $textContent = "تم تحديث حالة طلبك #{$orderData['order_id']} إلى: $newStatus\n\nشكراً لاختيارك دار زيد!";

        return $this->sendEmail($customerEmail, $subject, $htmlContent, $textContent);
    }

    /**
     * Send contact form notification to admin
     */
    public function sendContactNotification($contactData) {
        $adminEmail = 'Dar.zaid.2022@gmail.com'; // Send to company email
        $subject = 'رسالة جديدة من صفحة الاتصال | دار زيد';
        $htmlContent = $this->getContactNotificationTemplate($contactData);
        $textContent = $this->getContactNotificationText($contactData);

        return $this->sendEmail($adminEmail, $subject, $htmlContent, $textContent);
    }

    /**
     * Send contact form confirmation to customer
     */
    public function sendContactConfirmation($contactData) {
        $customerEmail = $contactData['email'];
        $subject = 'تم استلام رسالتك | دار زيد';
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
                body {
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                    margin: 0;
                    padding: 0;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
                .container {
                    max-width: 600px;
                    margin: 20px;
                    background: white;
                    border-radius: 20px;
                    overflow: hidden;
                    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
                }
                .header {
                    background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
                    color: white;
                    padding: 3rem 2rem;
                    text-align: center;
                    position: relative;
                }
                .header::before {
                    content: '';
                    position: absolute;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background: url('data:image/svg+xml,<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 100 100\"><circle cx=\"50\" cy=\"50\" r=\"2\" fill=\"white\" opacity=\"0.1\"/><circle cx=\"20\" cy=\"20\" r=\"1\" fill=\"white\" opacity=\"0.1\"/><circle cx=\"80\" cy=\"30\" r=\"1.5\" fill=\"white\" opacity=\"0.1\"/></svg>');
                }
                .content { padding: 3rem 2rem; }
                .code-box {
                    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
                    border: 3px dashed #3b82f6;
                    border-radius: 15px;
                    padding: 2.5rem;
                    text-align: center;
                    margin: 2rem 0;
                    position: relative;
                }
                .code {
                    font-size: 3rem;
                    font-weight: bold;
                    color: #1e3a8a;
                    letter-spacing: 0.8rem;
                    text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
                    font-family: 'Courier New', monospace;
                }
                .footer {
                    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
                    padding: 2rem;
                    text-align: center;
                    color: #64748b;
                    font-size: 0.9rem;
                }
                .logo {
                    width: 80px;
                    height: 80px;
                    margin: 0 auto 1.5rem;
                    background: rgba(255,255,255,0.2);
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 2.5rem;
                    backdrop-filter: blur(10px);
                }
                .warning {
                    background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
                    border: 2px solid #fecaca;
                    border-radius: 12px;
                    padding: 1.5rem;
                    margin: 2rem 0;
                    color: #dc2626;
                    text-align: center;
                }
                .highlight {
                    background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
                    padding: 1rem;
                    border-radius: 8px;
                    margin: 1rem 0;
                    border-right: 4px solid #3b82f6;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <div class='logo'>📚</div>
                    <h1>🔐 $title</h1>
                    <p style='font-size: 1.2rem; margin: 0; opacity: 0.9;'>مرحباً بك في دار زيد</p>
                </div>
                <div class='content'>
                    <h2 style='color: #1e3a8a; margin-bottom: 1rem;'>كود التحقق الخاص بك</h2>
                    <p style='font-size: 1.1rem; line-height: 1.6; color: #374151;'>$message</p>
                    <div class='code-box'>
                        <div class='code'>$code</div>
                        <p style='margin-top: 1rem; color: #64748b; font-size: 0.9rem;'>انسخ هذا الكود وأدخله في الموقع</p>
                    </div>
                    <div class='warning'>
                        <p style='margin: 0;'><strong>⚠️ مهم:</strong> هذا الكود صالح لمدة 10 دقائق فقط.</p>
                    </div>
                    <div class='highlight'>
                        <p style='margin: 0; color: #1e40af;'><strong>💡 ملاحظة:</strong> إذا لم تطلب هذا الكود، يرجى تجاهل هذه الرسالة وإبلاغنا فوراً.</p>
                    </div>
                    <p style='text-align: center; margin-top: 2rem;'>للحصول على المساعدة، تواصل معنا على: <strong>Dar.zaid.2022@gmail.com</strong></p>
                </div>
                <div class='footer'>
                    <p style='margin: 0 0 0.5rem 0;'><strong>© 2024 دار زيد. جميع الحقوق محفوظة.</strong></p>
                    <p style='margin: 0;'>هذه رسالة تلقائية، يرجى عدم الرد عليها.</p>
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
                body {
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                    margin: 0;
                    padding: 0;
                    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
                    min-height: 100vh;
                }
                .container {
                    max-width: 600px;
                    margin: 0 auto;
                    background: white;
                    border-radius: 20px;
                    overflow: hidden;
                    box-shadow: 0 20px 40px rgba(0,0,0,0.15);
                    margin-top: 2rem;
                    margin-bottom: 2rem;
                }
                .header {
                    background: linear-gradient(135deg, #059669 0%, #10b981 100%);
                    color: white;
                    padding: 3rem 2rem;
                    text-align: center;
                }
                .content { padding: 3rem 2rem; }
                .welcome-box {
                    background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
                    border-radius: 15px;
                    padding: 2.5rem;
                    margin: 2rem 0;
                    text-align: center;
                    border: 2px solid #10b981;
                }
                .features {
                    display: grid;
                    gap: 1.5rem;
                    margin: 2rem 0;
                }
                .feature {
                    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
                    padding: 1.5rem;
                    border-radius: 12px;
                    border-right: 4px solid #10b981;
                    transition: transform 0.3s ease;
                }
                .footer {
                    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
                    padding: 2rem;
                    text-align: center;
                    color: #64748b;
                    font-size: 0.9rem;
                }
                .logo {
                    width: 100px;
                    height: 100px;
                    margin: 0 auto 1.5rem;
                    background: rgba(255,255,255,0.2);
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 3rem;
                }
                .cta-button {
                    display: inline-block;
                    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
                    color: white;
                    padding: 1rem 2rem;
                    text-decoration: none;
                    border-radius: 25px;
                    font-weight: bold;
                    margin: 1rem 0;
                    transition: transform 0.3s ease;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <div class='logo'>📚</div>
                    <h1 style='font-size: 2.5rem; margin: 0 0 1rem 0;'>🎉 أهلاً وسهلاً!</h1>
                    <p style='font-size: 1.3rem; margin: 0; opacity: 0.9;'>مرحباً بك في دار زيد</p>
                </div>
                <div class='content'>
                    <div class='welcome-box'>
                        <h2 style='color: #059669; font-size: 2rem; margin: 0 0 1rem 0;'>عزيز/ة $name</h2>
                        <p style='font-size: 1.2rem; margin: 0; color: #047857;'>نحن سعداء جداً لانضمامك إلى عائلة دار زيد!</p>
                    </div>

                    <h3 style='color: #1e3a8a; text-align: center; font-size: 1.5rem;'>ماذا يمكنك فعله الآن:</h3>
                    <div class='features'>
                        <div class='feature'>
                            <h4 style='color: #059669; margin: 0 0 0.5rem 0;'>📖 تصفح الكتب</h4>
                            <p style='margin: 0; color: #374151;'>اكتشف مجموعتنا الواسعة من الكتب في مختلف المجالات والتخصصات</p>
                        </div>
                        <div class='feature'>
                            <h4 style='color: #059669; margin: 0 0 0.5rem 0;'>🛒 التسوق الآمن</h4>
                            <p style='margin: 0; color: #374151;'>استمتع بتجربة تسوق آمنة وسهلة مع خيارات دفع متعددة</p>
                        </div>
                        <div class='feature'>
                            <h4 style='color: #059669; margin: 0 0 0.5rem 0;'>🚚 التوصيل السريع</h4>
                            <p style='margin: 0; color: #374151;'>احصل على كتبك المفضلة بسرعة وأمان إلى باب منزلك</p>
                        </div>
                        <div class='feature'>
                            <h4 style='color: #059669; margin: 0 0 0.5rem 0;'>⭐ العروض الخاصة</h4>
                            <p style='margin: 0; color: #374151;'>استفد من العروض والخصومات الحصرية للأعضاء</p>
                        </div>
                    </div>

                    <div style='text-align: center; margin: 2rem 0;'>
                        <p style='font-size: 1.1rem; color: #374151;'>إذا كان لديك أي استفسارات، لا تتردد في التواصل معنا!</p>
                        <p style='color: #64748b;'>📧 Dar.zaid.2022@gmail.com</p>
                    </div>
                </div>
                <div class='footer'>
                    <p style='margin: 0 0 0.5rem 0;'><strong>© 2024 دار زيد. جميع الحقوق محفوظة.</strong></p>
                    <p style='margin: 0;'>نحن هنا لخدمتك دائماً 🤝</p>
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
                body {
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                    margin: 0;
                    padding: 0;
                    background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);
                    min-height: 100vh;
                }
                .container {
                    max-width: 600px;
                    margin: 0 auto;
                    background: white;
                    border-radius: 20px;
                    overflow: hidden;
                    box-shadow: 0 20px 40px rgba(0,0,0,0.15);
                    margin-top: 2rem;
                    margin-bottom: 2rem;
                }
                .header {
                    background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);
                    color: white;
                    padding: 3rem 2rem;
                    text-align: center;
                }
                .content { padding: 3rem 2rem; }
                .reset-box {
                    background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
                    border: 3px solid #fecaca;
                    border-radius: 15px;
                    padding: 2.5rem;
                    text-align: center;
                    margin: 2rem 0;
                }
                .reset-button {
                    display: inline-block;
                    background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);
                    color: white;
                    padding: 1.2rem 2.5rem;
                    text-decoration: none;
                    border-radius: 25px;
                    font-weight: bold;
                    margin: 1.5rem 0;
                    font-size: 1.1rem;
                    transition: transform 0.3s ease;
                }
                .footer {
                    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
                    padding: 2rem;
                    text-align: center;
                    color: #64748b;
                    font-size: 0.9rem;
                }
                .warning {
                    background: linear-gradient(135deg, #fffbeb 0%, #fed7aa 100%);
                    border: 2px solid #fed7aa;
                    border-radius: 12px;
                    padding: 1.5rem;
                    margin: 2rem 0;
                    color: #92400e;
                    text-align: center;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1 style='font-size: 2.5rem; margin: 0 0 1rem 0;'>🔒 إعادة تعيين كلمة المرور</h1>
                    <p style='font-size: 1.2rem; margin: 0; opacity: 0.9;'>دار زيد</p>
                </div>
                <div class='content'>
                    <h2 style='color: #dc2626; margin-bottom: 1rem;'>طلب إعادة تعيين كلمة المرور</h2>
                    <p style='font-size: 1.1rem; line-height: 1.6; color: #374151;'>تم طلب إعادة تعيين كلمة المرور لحسابك في دار زيد.</p>

                    <div class='reset-box'>
                        <p style='margin: 0 0 1.5rem 0; color: #7f1d1d; font-size: 1.1rem;'>اضغط على الزر أدناه لإعادة تعيين كلمة المرور:</p>
                        <a href='$resetUrl' class='reset-button'>إعادة تعيين كلمة المرور</a>
                        <p style='margin: 1.5rem 0 0 0; font-size: 0.9rem; color: #6b7280;'>أو انسخ الرابط التالي:<br><small>$resetUrl</small></p>
                    </div>

                    <div class='warning'>
                        <p style='margin: 0; font-weight: bold;'><strong>⚠️ مهم:</strong> هذا الرابط صالح لمدة ساعة واحدة فقط.</p>
                    </div>

                    <p style='text-align: center; color: #64748b; font-style: italic;'>إذا لم تطلب إعادة تعيين كلمة المرور، يرجى تجاهل هذه الرسالة.</p>
                </div>
                <div class='footer'>
                    <p style='margin: 0;'><strong>© 2024 دار زيد. جميع الحقوق محفوظة.</strong></p>
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
                <tr style='border-bottom: 1px solid #e2e8f0;'>
                    <td style='padding: 1rem; text-align: right;'>{$item['title']}</td>
                    <td style='padding: 1rem; text-align: center;'>{$item['quantity']}</td>
                    <td style='padding: 1rem; text-align: center;'>{$item['price']} ريال</td>
                    <td style='padding: 1rem; text-align: center; font-weight: bold;'>{$item['total']} ريال</td>
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
                body {
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                    margin: 0;
                    padding: 0;
                    background: linear-gradient(135deg, #059669 0%, #10b981 100%);
                    min-height: 100vh;
                }
                .container {
                    max-width: 700px;
                    margin: 0 auto;
                    background: white;
                    border-radius: 20px;
                    overflow: hidden;
                    box-shadow: 0 20px 40px rgba(0,0,0,0.15);
                    margin-top: 2rem;
                    margin-bottom: 2rem;
                }
                .header {
                    background: linear-gradient(135deg, #059669 0%, #10b981 100%);
                    color: white;
                    padding: 3rem 2rem;
                    text-align: center;
                }
                .content { padding: 3rem 2rem; }
                .order-info {
                    background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
                    padding: 2rem;
                    border-radius: 15px;
                    margin: 2rem 0;
                    border: 2px solid #10b981;
                }
                .items-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 2rem 0;
                    border-radius: 15px;
                    overflow: hidden;
                    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                }
                .items-table th {
                    background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
                    color: white;
                    padding: 1.2rem;
                    text-align: center;
                    font-weight: bold;
                }
                .total-section {
                    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
                    padding: 2rem;
                    border-radius: 15px;
                    margin: 2rem 0;
                    border-right: 5px solid #10b981;
                }
                .footer {
                    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
                    padding: 2rem;
                    text-align: center;
                    color: #64748b;
                    font-size: 0.9rem;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1 style='font-size: 2.5rem; margin: 0 0 1rem 0;'>✅ تم تأكيد طلبك</h1>
                    <p style='font-size: 1.3rem; margin: 0; opacity: 0.9;'>شكراً لك لاختيارك دار زيد</p>
                </div>
                <div class='content'>
                    <div class='order-info'>
                        <h3 style='color: #059669; margin: 0 0 1.5rem 0; font-size: 1.5rem;'>معلومات الطلب</h3>
                        <p style='margin: 0.5rem 0; font-size: 1.1rem;'><strong>رقم الطلب:</strong> <span style='color: #1e3a8a;'>#{$orderData['order_id']}</span></p>
                        <p style='margin: 0.5rem 0; font-size: 1.1rem;'><strong>تاريخ الطلب:</strong> " . date('Y-m-d H:i') . "</p>
                        <p style='margin: 0.5rem 0; font-size: 1.1rem;'><strong>الحالة:</strong> <span style='color: #059669; font-weight: bold;'>{$orderData['status']}</span></p>
                    </div>

                    <h3 style='color: #1e3a8a; font-size: 1.5rem;'>تفاصيل الطلب</h3>
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
                        <h4 style='color: #1e3a8a; margin: 0 0 1rem 0; font-size: 1.3rem;'>ملخص الفاتورة</h4>
                        <p style='margin: 0.5rem 0; font-size: 1.1rem;'><strong>المجموع الفرعي:</strong> {$orderData['subtotal']} ريال</p>
                        <p style='margin: 0.5rem 0; font-size: 1.1rem;'><strong>تكلفة الشحن:</strong> {$orderData['shipping_cost']} ريال</p>
                        <p style='margin: 0.5rem 0; font-size: 1.1rem;'><strong>الضريبة:</strong> {$orderData['tax_amount']} ريال</p>
                        <hr style='border: none; height: 2px; background: #10b981; margin: 1rem 0;'>
                        <p style='margin: 0; font-size: 1.4rem; color: #059669; font-weight: bold;'>المجموع الكلي: {$orderData['total_amount']} ريال</p>
                    </div>

                    <div style='background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); padding: 1.5rem; border-radius: 12px; margin: 2rem 0; border-right: 4px solid #3b82f6;'>
                        <p style='margin: 0; color: #1e40af; font-weight: bold;'>سنقوم بمعالجة طلبك وإرسال تحديثات حالة الطلب عبر البريد الإلكتروني.</p>
                    </div>

                    <p style='text-align: center; color: #64748b;'>إذا كان لديك أي استفسارات، تواصل معنا على: <strong>Dar.zaid.2022@gmail.com</strong></p>
                </div>
                <div class='footer'>
                    <p style='margin: 0;'><strong>© 2024 دار زيد. جميع الحقوق محفوظة.</strong></p>
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
        $statusColor = ($newStatus === 'delivered') ? '#059669' : (($newStatus === 'cancelled') ? '#dc2626' : '#3b82f6');

        return "
        <!DOCTYPE html>
        <html dir='rtl' lang='ar'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>تحديث حالة الطلب</title>
            <style>
                body {
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                    margin: 0;
                    padding: 0;
                    background: linear-gradient(135deg, #3b82f6 0%, #1e3a8a 100%);
                    min-height: 100vh;
                }
                .container {
                    max-width: 600px;
                    margin: 0 auto;
                    background: white;
                    border-radius: 20px;
                    overflow: hidden;
                    box-shadow: 0 20px 40px rgba(0,0,0,0.15);
                    margin-top: 2rem;
                    margin-bottom: 2rem;
                }
                .header {
                    background: linear-gradient(135deg, #3b82f6 0%, #1e3a8a 100%);
                    color: white;
                    padding: 3rem 2rem;
                    text-align: center;
                }
                .content { padding: 3rem 2rem; }
                .status-box {
                    background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
                    border: 3px solid $statusColor;
                    border-radius: 15px;
                    padding: 2.5rem;
                    text-align: center;
                    margin: 2rem 0;
                }
                .footer {
                    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
                    padding: 2rem;
                    text-align: center;
                    color: #64748b;
                    font-size: 0.9rem;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1 style='font-size: 2.5rem; margin: 0 0 1rem 0;'>📦 تحديث حالة الطلب</h1>
                    <p style='font-size: 1.2rem; margin: 0; opacity: 0.9;'>دار زيد</p>
                </div>
                <div class='content'>
                    <div class='status-box'>
                        <h2 style='color: $statusColor; margin: 0 0 1rem 0; font-size: 1.8rem;'>$statusMessage</h2>
                        <p style='margin: 0.5rem 0; font-size: 1.2rem;'><strong>رقم الطلب:</strong> <span style='color: #1e3a8a;'>#{$orderData['order_id']}</span></p>
                        <p style='margin: 0.5rem 0; font-size: 1.2rem;'><strong>الحالة الجديدة:</strong> <span style='color: $statusColor; font-weight: bold;'>$newStatus</span></p>
                    </div>

                    <div style='background: linear-gradient(135d
                        <p style='margin: 0; color: #1e40af;'>شكراً لاختيارك دار زيد!</p>
                    </div>
                    <p style='text-align: center; color: #64748b;'>إذا كان لديك أي استفسارات، تواصل معنا على: <strong>Dar.zaid.2022@gmail.com</strong></p>
                </div>
                <div class='footer'>
                    <p style='margin: 0;'><strong>© 2024 دار زيد. جميع الحقوق محفوظة.</strong></p>
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
                body {
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                    margin: 0;
                    padding: 0;
                    background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);
                    min-height: 100vh;
                }
                .container {
                    max-width: 600px;
                    margin: 0 auto;
                    background: white;
                    border-radius: 20px;
                    overflow: hidden;
                    box-shadow: 0 20px 40px rgba(0,0,0,0.15);
                    margin-top: 2rem;
                    margin-bottom: 2rem;
                }
                .header {
                    background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);
                    color: white;
                    padding: 3rem 2rem;
                    text-align: center;
                }
                .content { padding: 3rem 2rem; }
                .contact-info {
                    background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
                    padding: 2rem;
                    border-radius: 15px;
                    margin: 2rem 0;
                    border: 2px solid #10b981;
                }
                .message-box {
                    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
                    padding: 2rem;
                    border-radius: 15px;
                    border-right: 5px solid #3b82f6;
                    margin: 2rem 0;
                }
                .footer {
                    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
                    padding: 2rem;
                    text-align: center;
                    color: #64748b;
                    font-size: 0.9rem;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1 style='font-size: 2.5rem; margin: 0 0 1rem 0;'>📧 رسالة جديدة</h1>
                    <p style='font-size: 1.2rem; margin: 0; opacity: 0.9;'>تم استلام رسالة من صفحة الاتصال</p>
                </div>
                <div class='content'>
                    <div class='contact-info'>
                        <h3 style='color: #059669; margin: 0 0 1.5rem 0; font-size: 1.5rem;'>معلومات المرسل</h3>
                        <p style='margin: 0.5rem 0; font-size: 1.1rem;'><strong>الاسم:</strong> {$contactData['name']}</p>
                        <p style='margin: 0.5rem 0; font-size: 1.1rem;'><strong>البريد الإلكتروني:</strong> {$contactData['email']}</p>
                        <p style='margin: 0.5rem 0; font-size: 1.1rem;'><strong>الهاتف:</strong> " . ($contactData['phone'] ?? 'غير محدد') . "</p>
                        <p style='margin: 0.5rem 0; font-size: 1.1rem;'><strong>الموضوع:</strong> " . ($contactData['subject'] ?? 'غير محدد') . "</p>
                    </div>

                    <div class='message-box'>
                        <h3 style='color: #1e3a8a; margin: 0 0 1rem 0; font-size: 1.3rem;'>نص الرسالة</h3>
                        <p style='margin: 0; font-size: 1.1rem; line-height: 1.6; color: #374151;'>" . nl2br(htmlspecialchars($contactData['message'])) . "</p>
                    </div>

                    <p style='text-align: center; color: #64748b; font-style: italic;'><strong>وقت الإرسال:</strong> " . date('Y-m-d H:i:s') . "</p>
                </div>
                <div class='footer'>
                    <p style='margin: 0;'><strong>© 2024 دار زيد. جميع الحقوق محفوظة.</strong></p>
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
                body {
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                    margin: 0;
                    padding: 0;
                    background: linear-gradient(135deg, #059669 0%, #10b981 100%);
                    min-height: 100vh;
                }
                .container {
                    max-width: 600px;
                    margin: 0 auto;
                    background: white;
                    border-radius: 20px;
                    overflow: hidden;
                    box-shadow: 0 20px 40px rgba(0,0,0,0.15);
                    margin-top: 2rem;
                    margin-bottom: 2rem;
                }
                .header {
                    background: linear-gradient(135deg, #059669 0%, #10b981 100%);
                    color: white;
                    padding: 3rem 2rem;
                    text-align: center;
                }
                .content { padding: 3rem 2rem; }
                .message-summary {
                    background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
                    padding: 2rem;
                    border-radius: 15px;
                    margin: 2rem 0;
                    border: 2px solid #10b981;
                }
                .footer {
                    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
                    padding: 2rem;
                    text-align: center;
                    color: #64748b;
                    font-size: 0.9rem;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1 style='font-size: 2.5rem; margin: 0 0 1rem 0;'>✅ تم استلام رسالتك</h1>
                    <p style='font-size: 1.2rem; margin: 0; opacity: 0.9;'>شكراً لك على تواصلك معنا</p>
                </div>
                <div class='content'>
                    <p style='font-size: 1.2rem; color: #374151; margin-bottom: 1.5rem;'>عزيز/ة <strong>{$contactData['name']}</strong>،</p>
                    <p style='font-size: 1.1rem; color: #374151; line-height: 1.6;'>تم استلام رسالتك بنجاح وسنرد عليك في أقرب وقت ممكن.</p>

                    <div class='message-summary'>
                        <h3 style='color: #059669; margin: 0 0 1.5rem 0; font-size: 1.3rem;'>ملخص رسالتك</h3>
                        <p style='margin: 0.5rem 0; font-size: 1.1rem;'><strong>الموضوع:</strong> " . ($contactData['subject'] ?? 'غير محدد') . "</p>
                        <p style='margin: 1rem 0 0.5rem 0; font-size: 1.1rem;'><strong>الرسالة:</strong></p>
                        <p style='margin: 0; padding: 1rem; background: rgba(255,255,255,0.5); border-radius: 8px; font-size: 1rem; line-height: 1.6;'>" . nl2br(htmlspecialchars($contactData['message'])) . "</p>
                    </div>

                    <p style='font-size: 1.1rem; color: #374151; text-align: center;'>نقدر وقتك واهتمامك بدار زيد.</p>
                    <p style='text-align: center; color: #64748b;'>للاستفسارات العاجلة، يمكنك التواصل معنا على: <strong>Dar.zaid.2022@gmail.com</strong></p>
                </div>
                <div class='footer'>
                    <p style='margin: 0;'><strong>© 2024 دار زيد. جميع الحقوق محفوظة.</strong></p>
                </div>
            </div>
        </body>
        </html>";
    }
}
?>