<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/email_config.php';

class UniversalEmailService {
    private $emailProvider;
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();

        // Initialize rate limiting
        EmailRateLimit::init($this->db);

        // Determine which email provider to use
        $this->emailProvider = $this->getEmailProvider();
    }

    /**
     * Determine the best available email provider
     */
    private function getEmailProvider() {
        // Check for SMTP2GO API key (recommended for global usage)
        $smtp2goKey = getenv('SMTP2GO_API_KEY');
        if (!empty($smtp2goKey)) {
            require_once __DIR__ . '/smtp2go_service.php';
            return new SMTP2GOService();
        }

        // Check for Gmail app password (for regions where it's available)
        $gmailPassword = getenv('GMAIL_APP_PASSWORD');
        if (!empty($gmailPassword)) {
            require_once __DIR__ . '/gmail_service.php';
            return new GmailService();
        }

        // Fallback to PHP mail() function (basic, not recommended for production)
        require_once __DIR__ . '/php_mail_service.php';
        return new PHPMailService();
    }

    /**
     * Send verification code for signup/login
     */
    public function sendVerificationCode($email, $code, $purpose = 'verification') {
        // Check rate limiting
        if (!EmailRateLimit::canSendEmail($email)) {
            error_log("Rate limit exceeded for email: $email");
            return false;
        }

        $result = $this->emailProvider->sendVerificationCode($email, $code, $purpose);

        if ($result) {
            EmailRateLimit::recordEmailSent($email);
        }

        return $result;
    }

    /**
     * Send welcome email to new users
     */
    public function sendWelcomeEmail($email, $name) {
        return $this->emailProvider->sendWelcomeEmail($email, $name);
    }

    /**
     * Send password reset email
     */
    public function sendPasswordResetEmail($email, $resetToken) {
        // Check rate limiting
        if (!EmailRateLimit::canSendEmail($email)) {
            error_log("Rate limit exceeded for email: $email");
            return false;
        }

        $result = $this->emailProvider->sendPasswordResetEmail($email, $resetToken);

        if ($result) {
            EmailRateLimit::recordEmailSent($email);
        }

        return $result;
    }

    /**
     * Send order confirmation email
     */
    public function sendOrderConfirmation($orderData) {
        return $this->emailProvider->sendOrderConfirmation($orderData);
    }

    /**
     * Send order status update email
     */
    public function sendOrderStatusUpdate($orderData, $newStatus) {
        return $this->emailProvider->sendOrderStatusUpdate($orderData, $newStatus);
    }

    /**
     * Send contact form notification to admin
     */
    public function sendContactNotification($contactData) {
        return $this->emailProvider->sendContactNotification($contactData);
    }

    /**
     * Send contact form confirmation to customer
     */
    public function sendContactConfirmation($contactData) {
        // Check rate limiting
        if (!EmailRateLimit::canSendEmail($contactData['email'])) {
            error_log("Rate limit exceeded for email: " . $contactData['email']);
            return false;
        }

        $result = $this->emailProvider->sendContactConfirmation($contactData);

        if ($result) {
            EmailRateLimit::recordEmailSent($contactData['email']);
        }

        return $result;
    }

    /**
     * Store verification code in database
     */
    public function storeVerificationCode($email, $code, $purpose = 'verification') {
        return $this->emailProvider->storeVerificationCode($email, $code, $purpose);
    }

    /**
     * Verify code from database
     */
    public function verifyCode($email, $code, $purpose = 'verification') {
        return $this->emailProvider->verifyCode($email, $code, $purpose);
    }

    /**
     * Generate random verification code
     */
    public function generateVerificationCode() {
        return $this->emailProvider->generateVerificationCode();
    }

    /**
     * Get information about the current email provider
     */
    public function getProviderInfo() {
        $providerClass = get_class($this->emailProvider);

        switch ($providerClass) {
            case 'SMTP2GOService':
                return [
                    'name' => 'SMTP2GO',
                    'description' => 'Global email delivery service (Recommended)',
                    'reliability' => 'High',
                    'setup_difficulty' => 'Easy',
                    'monthly_limit' => '1,000 emails free'
                ];

            case 'GmailService':
                return [
                    'name' => 'Gmail SMTP',
                    'description' => 'Direct Gmail SMTP connection',
                    'reliability' => 'High',
                    'setup_difficulty' => 'Medium',
                    'monthly_limit' => 'Very high'
                ];

            case 'PHPMailService':
                return [
                    'name' => 'PHP Mail',
                    'description' => 'Basic PHP mail() function (Fallback)',
                    'reliability' => 'Low',
                    'setup_difficulty' => 'None',
                    'monthly_limit' => 'Server dependent'
                ];

            default:
                return [
                    'name' => 'Unknown',
                    'description' => 'Unknown email provider',
                    'reliability' => 'Unknown',
                    'setup_difficulty' => 'Unknown',
                    'monthly_limit' => 'Unknown'
                ];
        }
    }

    /**
     * Test email functionality
     */
    public function testEmailSystem($testEmail = null) {
        $results = [];

        // Test 1: Provider detection
        $provider = $this->getProviderInfo();
        $results['provider'] = $provider;
        $results['provider_available'] = !empty($this->emailProvider);

        // Test 2: Code generation
        $code = $this->generateVerificationCode();
        $results['code_generation'] = [
            'success' => strlen($code) === 6 && is_numeric($code),
            'code' => $code
        ];

        // Test 3: Database operations
        if ($testEmail) {
            $storeResult = $this->storeVerificationCode($testEmail, $code, 'test');
            $verifyResult = $this->verifyCode($testEmail, $code, 'test');

            $results['database'] = [
                'store' => $storeResult,
                'verify' => $verifyResult,
                'reuse_prevention' => !$this->verifyCode($testEmail, $code, 'test')
            ];
        }

        // Test 4: Rate limiting
        $results['rate_limiting'] = [
            'can_send' => EmailRateLimit::canSendEmail($testEmail ?? 'test@example.com'),
            'system_active' => true
        ];

        return $results;
    }

    /**
     * Send test email if configured
     */
    public function sendTestEmail($testEmail) {
        if (!filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $code = $this->generateVerificationCode();
        return $this->sendVerificationCode($testEmail, $code, 'test');
    }
}
?>