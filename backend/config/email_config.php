<?php
/**
 * Email Configuration for Dar Zaid
 *
 * This file contains email settings and configuration
 */

class EmailConfig {
    // Gmail SMTP Configuration
    const SMTP_HOST = 'smtp.gmail.com';
    const SMTP_PORT = 587;
    const SMTP_USERNAME = 'Dar.zaid.2022@gmail.com';
    const FROM_EMAIL = 'Dar.zaid.2022@gmail.com';
    const FROM_NAME = 'دار زيد';
    const REPLY_TO = 'Dar.zaid.2022@gmail.com';

    // Admin Email
    const ADMIN_EMAIL = 'Dar.zaid.2022@gmail.com';

    // Email Templates Configuration
    const TEMPLATES_PATH = __DIR__ . '/../templates/emails/';

    // Verification Code Settings
    const VERIFICATION_CODE_LENGTH = 6;
    const VERIFICATION_CODE_EXPIRY = 10; // minutes
    const MAX_VERIFICATION_ATTEMPTS = 3;

    // Password Reset Settings
    const PASSWORD_RESET_EXPIRY = 60; // minutes

    // Rate Limiting
    const MAX_EMAILS_PER_HOUR = 20;
    const MAX_EMAILS_PER_DAY = 100;

    // Email Queue Settings
    const USE_EMAIL_QUEUE = false; // Set to true for high volume
    const QUEUE_BATCH_SIZE = 10;

    /**
     * Get environment variables or default values
     */
    public static function getConfig() {
        return [
            'smtp_password' => getenv('GMAIL_APP_PASSWORD') ?: '',
            'debug_mode' => getenv('EMAIL_DEBUG') === 'true',
            'test_mode' => getenv('EMAIL_TEST_MODE') === 'true',
            'test_email' => getenv('EMAIL_TEST_RECIPIENT') ?: 'test@example.com'
        ];
    }

    /**
     * Email template mappings
     */
    public static function getTemplates() {
        return [
            'verification' => 'verification_code.html',
            'welcome' => 'welcome.html',
            'password_reset' => 'password_reset.html',
            'order_confirmation' => 'order_confirmation.html',
            'order_status_update' => 'order_status_update.html',
            'contact_notification' => 'contact_notification.html',
            'contact_confirmation' => 'contact_confirmation.html'
        ];
    }

    /**
     * Email subject templates
     */
    public static function getSubjects() {
        return [
            'verification_signup' => 'كود التحقق - تفعيل الحساب | دار زيد',
            'verification_login' => 'كود التحقق - تسجيل الدخول | دار زيد',
            'welcome' => 'مرحباً بك في دار زيد 🎉',
            'password_reset' => 'إعادة تعيين كلمة المرور | دار زيد',
            'order_confirmation' => 'تأكيد الطلب #{order_id} | دار زيد',
            'order_status_update' => 'تحديث حالة الطلب #{order_id} | دار زيد',
            'contact_notification' => 'رسالة جديدة من صفحة الاتصال | دار زيد',
            'contact_confirmation' => 'تم استلام رسالتك | دار زيد'
        ];
    }
}

/**
 * Email Rate Limiter
 */
class EmailRateLimit {
    private static $db;

    public static function init($database) {
        self::$db = $database;

        // Create rate limit table
        self::$db->exec("
            CREATE TABLE IF NOT EXISTS email_rate_limits (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(255) NOT NULL,
                sent_count INT DEFAULT 0,
                hour_window TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                day_window DATE DEFAULT (CURRENT_DATE),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_email (email),
                INDEX idx_hour (hour_window),
                INDEX idx_day (day_window)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    public static function canSendEmail($email) {
        if (!self::$db) return true;

        try {
            // Clean up old records
            self::$db->exec("
                DELETE FROM email_rate_limits
                WHERE hour_window < DATE_SUB(NOW(), INTERVAL 1 HOUR)
                AND day_window < CURDATE()
            ");

            // Check hourly limit
            $stmt = self::$db->prepare("
                SELECT sent_count FROM email_rate_limits
                WHERE email = :email
                AND hour_window >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ");
            $stmt->execute(['email' => $email]);
            $hourlyCount = $stmt->fetchColumn() ?: 0;

            if ($hourlyCount >= EmailConfig::MAX_EMAILS_PER_HOUR) {
                return false;
            }

            // Check daily limit
            $stmt = self::$db->prepare("
                SELECT SUM(sent_count) FROM email_rate_limits
                WHERE email = :email
                AND day_window = CURDATE()
            ");
            $stmt->execute(['email' => $email]);
            $dailyCount = $stmt->fetchColumn() ?: 0;

            if ($dailyCount >= EmailConfig::MAX_EMAILS_PER_DAY) {
                return false;
            }

            return true;

        } catch (PDOException $e) {
            error_log('Rate limit check error: ' . $e->getMessage());
            return true; // Allow on error
        }
    }

    public static function recordEmailSent($email) {
        if (!self::$db) return;

        try {
            $stmt = self::$db->prepare("
                INSERT INTO email_rate_limits (email, sent_count, hour_window, day_window)
                VALUES (:email, 1, NOW(), CURDATE())
                ON DUPLICATE KEY UPDATE
                sent_count = sent_count + 1,
                updated_at = NOW()
            ");
            $stmt->execute(['email' => $email]);

        } catch (PDOException $e) {
            error_log('Rate limit record error: ' . $e->getMessage());
        }
    }
}

/**
 * Email Queue for handling high volume
 */
class EmailQueue {
    private static $db;

    public static function init($database) {
        self::$db = $database;

        // Create email queue table
        self::$db->exec("
            CREATE TABLE IF NOT EXISTS email_queue (
                id INT AUTO_INCREMENT PRIMARY KEY,
                to_email VARCHAR(255) NOT NULL,
                subject TEXT NOT NULL,
                html_content LONGTEXT NOT NULL,
                text_content TEXT,
                priority INT DEFAULT 5,
                max_attempts INT DEFAULT 3,
                attempts INT DEFAULT 0,
                status ENUM('pending', 'processing', 'sent', 'failed') DEFAULT 'pending',
                scheduled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                sent_at TIMESTAMP NULL,
                error_message TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_status (status),
                INDEX idx_scheduled (scheduled_at),
                INDEX idx_priority (priority)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    public static function addToQueue($to, $subject, $htmlContent, $textContent = null, $priority = 5) {
        if (!self::$db) return false;

        try {
            $stmt = self::$db->prepare("
                INSERT INTO email_queue (to_email, subject, html_content, text_content, priority)
                VALUES (:to, :subject, :html, :text, :priority)
            ");

            return $stmt->execute([
                'to' => $to,
                'subject' => $subject,
                'html' => $htmlContent,
                'text' => $textContent,
                'priority' => $priority
            ]);

        } catch (PDOException $e) {
            error_log('Email queue error: ' . $e->getMessage());
            return false;
        }
    }

    public static function processBatch($batchSize = null) {
        if (!self::$db) return 0;

        $batchSize = $batchSize ?: EmailConfig::QUEUE_BATCH_SIZE;
        $processed = 0;

        try {
            // Get pending emails
            $stmt = self::$db->prepare("
                SELECT * FROM email_queue
                WHERE status = 'pending'
                AND scheduled_at <= NOW()
                AND attempts < max_attempts
                ORDER BY priority DESC, created_at ASC
                LIMIT :limit
            ");
            $stmt->bindValue(':limit', $batchSize, PDO::PARAM_INT);
            $stmt->execute();

            $emails = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($emails as $email) {
                // Mark as processing
                self::updateEmailStatus($email['id'], 'processing');

                // Try to send
                $gmailService = new GmailService();
                $success = $gmailService->sendEmail(
                    $email['to_email'],
                    $email['subject'],
                    $email['html_content'],
                    $email['text_content']
                );

                if ($success) {
                    self::updateEmailStatus($email['id'], 'sent');
                    $processed++;
                } else {
                    self::incrementAttempts($email['id']);
                }
            }

        } catch (PDOException $e) {
            error_log('Email queue processing error: ' . $e->getMessage());
        }

        return $processed;
    }

    private static function updateEmailStatus($id, $status, $errorMessage = null) {
        $stmt = self::$db->prepare("
            UPDATE email_queue
            SET status = :status, error_message = :error, sent_at = IF(:status = 'sent', NOW(), sent_at)
            WHERE id = :id
        ");
        $stmt->execute([
            'id' => $id,
            'status' => $status,
            'error' => $errorMessage
        ]);
    }

    private static function incrementAttempts($id) {
        $stmt = self::$db->prepare("
            UPDATE email_queue
            SET attempts = attempts + 1,
                status = IF(attempts + 1 >= max_attempts, 'failed', 'pending')
            WHERE id = :id
        ");
        $stmt->execute(['id' => $id]);
    }
}
?>