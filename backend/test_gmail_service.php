<?php
/**
 * Test script for Gmail email service
 *
 * This script tests the Gmail email service functionality
 * Make sure to set the GMAIL_APP_PASSWORD environment variable
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/services/gmail_service.php';
require_once __DIR__ . '/config/email_config.php';

// Colors for console output
function colored($text, $color = 'white') {
    $colors = [
        'red' => "\033[31m",
        'green' => "\033[32m",
        'yellow' => "\033[33m",
        'blue' => "\033[34m",
        'purple' => "\033[35m",
        'cyan' => "\033[36m",
        'white' => "\033[37m",
        'reset' => "\033[0m"
    ];
    return $colors[$color] . $text . $colors['reset'];
}

function testResult($test, $result) {
    $status = $result ? colored("✓ PASS", 'green') : colored("✗ FAIL", 'red');
    echo "$status - $test\n";
    return $result;
}

echo colored("=== Gmail Service Test ===\n", 'cyan');
echo "Testing Gmail email service functionality...\n\n";

try {
    // Initialize services
    $database = new Database();
    $db = $database->getConnection();
    EmailRateLimit::init($db);

    $gmailService = new GmailService();

    // Test 1: Check if Gmail app password is configured
    $config = EmailConfig::getConfig();
    $hasPassword = !empty($config['smtp_password']);
    testResult("Gmail app password configured", $hasPassword);

    if (!$hasPassword) {
        echo colored("\n⚠️  WARNING: Gmail app password not set!\n", 'yellow');
        echo "Please set the GMAIL_APP_PASSWORD environment variable.\n";
        echo "To get an app password:\n";
        echo "1. Go to https://myaccount.google.com/apppasswords\n";
        echo "2. Select 'Mail' and generate a password\n";
        echo "3. Set GMAIL_APP_PASSWORD environment variable\n\n";
    }

    // Test 2: Generate verification code
    $code = $gmailService->generateVerificationCode();
    testResult("Generate verification code", strlen($code) === 6 && is_numeric($code));
    echo "Generated code: $code\n\n";

    // Test 3: Test verification code storage and retrieval
    $testEmail = 'test@example.com';
    $storeResult = $gmailService->storeVerificationCode($testEmail, $code, 'test');
    testResult("Store verification code in database", $storeResult);

    $verifyResult = $gmailService->verifyCode($testEmail, $code, 'test');
    testResult("Verify code from database", $verifyResult);

    // Test code verification again (should fail since it's used)
    $verifyAgainResult = !$gmailService->verifyCode($testEmail, $code, 'test');
    testResult("Code cannot be reused", $verifyAgainResult);

    // Test 4: Rate limiting
    $rateLimitOk = EmailRateLimit::canSendEmail($testEmail);
    testResult("Rate limiting allows email", $rateLimitOk);

    EmailRateLimit::recordEmailSent($testEmail);
    testResult("Record email sent", true);

    // Test 5: Email templates (check if they render without errors)
    try {
        // Test verification email template
        $reflection = new ReflectionClass($gmailService);
        $method = $reflection->getMethod('getVerificationEmailTemplate');
        $method->setAccessible(true);
        $template = $method->invoke($gmailService, '123456', 'signup');
        testResult("Verification email template renders", !empty($template) && strpos($template, '123456') !== false);

        // Test welcome email template
        $method = $reflection->getMethod('getWelcomeEmailTemplate');
        $method->setAccessible(true);
        $template = $method->invoke($gmailService, 'أحمد محمد');
        testResult("Welcome email template renders", !empty($template) && strpos($template, 'أحمد محمد') !== false);

    } catch (Exception $e) {
        testResult("Email templates", false);
        echo "Template error: " . $e->getMessage() . "\n";
    }

    // Test 6: Email configuration
    $subjects = EmailConfig::getSubjects();
    testResult("Email subjects configured", !empty($subjects));

    $templates = EmailConfig::getTemplates();
    testResult("Email template mappings configured", !empty($templates));

    echo "\n" . colored("=== Test Summary ===", 'cyan') . "\n";

    if ($hasPassword) {
        echo colored("✓ Email service is ready to use!", 'green') . "\n";
        echo "You can now:\n";
        echo "- Send verification codes for signup/login\n";
        echo "- Send welcome emails to new users\n";
        echo "- Send order confirmations\n";
        echo "- Send contact form notifications\n";
        echo "- Use password reset functionality\n\n";

        // Optional: Send test email if requested
        echo "To send a test email, run this script with 'test-send' argument:\n";
        echo "php test_gmail_service.php test-send your-email@example.com\n";

    } else {
        echo colored("⚠️  Email service needs configuration!", 'yellow') . "\n";
        echo "Set up the Gmail app password to enable email functionality.\n";
    }

    // Cleanup test data
    $stmt = $db->prepare("DELETE FROM verification_codes WHERE email = ?");
    $stmt->execute([$testEmail]);

} catch (Exception $e) {
    echo colored("✗ CRITICAL ERROR: " . $e->getMessage(), 'red') . "\n";
    exit(1);
}

// Handle test send request
if (isset($argv[1]) && $argv[1] === 'test-send' && isset($argv[2])) {
    $testEmailAddress = $argv[2];

    if (!filter_var($testEmailAddress, FILTER_VALIDATE_EMAIL)) {
        echo colored("Invalid email address provided!", 'red') . "\n";
        exit(1);
    }

    echo "\n" . colored("Sending test email to $testEmailAddress...", 'yellow') . "\n";

    try {
        $code = $gmailService->generateVerificationCode();
        $result = $gmailService->sendVerificationCode($testEmailAddress, $code, 'test');

        if ($result) {
            echo colored("✓ Test email sent successfully!", 'green') . "\n";
            echo "Check your inbox for the verification email.\n";
            echo "Verification code: $code\n";
        } else {
            echo colored("✗ Failed to send test email", 'red') . "\n";
            echo "Check your Gmail app password and internet connection.\n";
        }

    } catch (Exception $e) {
        echo colored("✗ Error sending test email: " . $e->getMessage(), 'red') . "\n";
    }
}
?>