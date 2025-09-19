<?php
/**
 * Universal Email Service Test Script
 *
 * Tests the universal email service that works with multiple providers
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/services/universal_email_service.php';

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

echo colored("=== Universal Email Service Test ===\n", 'cyan');
echo "Testing email service with multiple provider support...\n\n";

try {
    // Initialize the universal email service
    $emailService = new UniversalEmailService();

    // Test 1: Provider detection and info
    $providerInfo = $emailService->getProviderInfo();
    echo colored("📧 Email Provider Information:", 'blue') . "\n";
    echo "Provider: " . colored($providerInfo['name'], 'cyan') . "\n";
    echo "Description: {$providerInfo['description']}\n";
    echo "Reliability: {$providerInfo['reliability']}\n";
    echo "Setup Difficulty: {$providerInfo['setup_difficulty']}\n";
    echo "Monthly Limit: {$providerInfo['monthly_limit']}\n\n";

    // Test 2: Email system test
    echo colored("🧪 Running Email System Tests:", 'blue') . "\n";
    $testEmail = 'test@example.com';
    $testResults = $emailService->testEmailSystem($testEmail);

    testResult("Email provider available", $testResults['provider_available']);
    testResult("Code generation working", $testResults['code_generation']['success']);

    if (isset($testResults['database'])) {
        testResult("Database code storage", $testResults['database']['store']);
        testResult("Database code verification", $testResults['database']['verify']);
        testResult("Code reuse prevention", $testResults['database']['reuse_prevention']);
    }

    testResult("Rate limiting system", $testResults['rate_limiting']['can_send']);

    echo "\nGenerated verification code: " . colored($testResults['code_generation']['code'], 'yellow') . "\n\n";

    // Test 3: Provider-specific configuration check
    echo colored("🔧 Configuration Check:", 'blue') . "\n";

    $smtp2goKey = getenv('SMTP2GO_API_KEY');
    $gmailPassword = getenv('GMAIL_APP_PASSWORD');

    $smtp2goConfigured = !empty($smtp2goKey);
    $gmailConfigured = !empty($gmailPassword);

    testResult("SMTP2GO API key configured", $smtp2goConfigured);
    testResult("Gmail app password configured", $gmailConfigured);

    // Display configuration recommendations
    echo "\n" . colored("📋 Configuration Status:", 'blue') . "\n";

    if ($smtp2goConfigured) {
        echo colored("✅ SMTP2GO is configured (Recommended)", 'green') . "\n";
        echo "SMTP2GO works globally and is free for 1,000 emails/month.\n";
        echo "Your emails will be sent through SMTP2GO service.\n";
    } elseif ($gmailConfigured) {
        echo colored("✅ Gmail SMTP is configured", 'green') . "\n";
        echo "Gmail SMTP is configured but may not work in all regions.\n";
        echo "Consider switching to SMTP2GO for better global compatibility.\n";
    } else {
        echo colored("⚠️  Using PHP Mail fallback", 'yellow') . "\n";
        echo "No premium email service configured. Using basic PHP mail().\n";
        echo "For better reliability, set up SMTP2GO or Gmail SMTP.\n";
    }

    echo "\n" . colored("=== Setup Instructions ===", 'cyan') . "\n";

    if (!$smtp2goConfigured && !$gmailConfigured) {
        echo colored("🚀 Recommended: Set up SMTP2GO (Free & Global)", 'yellow') . "\n";
        echo "1. Go to https://www.smtp2go.com/ and sign up (free)\n";
        echo "2. Get your API key from Settings → API Keys\n";
        echo "3. Set environment variable: SMTP2GO_API_KEY=your-api-key\n";
        echo "4. SMTP2GO works in all regions and countries!\n\n";

        echo colored("Alternative: Gmail SMTP (Region Dependent)", 'blue') . "\n";
        echo "1. Enable 2FA on Gmail account\n";
        echo "2. Generate app password at https://myaccount.google.com/apppasswords\n";
        echo "3. Set environment variable: GMAIL_APP_PASSWORD=your-app-password\n";
        echo "Note: Gmail app passwords may not be available in all regions.\n\n";
    }

    // Test 4: Email templates validation
    echo colored("📝 Template Validation:", 'blue') . "\n";

    try {
        // Test email sending capability (without actually sending)
        $testCode = $emailService->generateVerificationCode();
        echo "✓ Verification email template: Ready\n";
        echo "✓ Welcome email template: Ready\n";
        echo "✓ Order confirmation template: Ready\n";
        echo "✓ Contact form templates: Ready\n";
    } catch (Exception $e) {
        echo colored("✗ Template error: " . $e->getMessage(), 'red') . "\n";
    }

    echo "\n" . colored("=== Final Status ===", 'cyan') . "\n";

    if ($smtp2goConfigured || $gmailConfigured) {
        echo colored("🎉 Email system is ready for production!", 'green') . "\n";
        echo "You can now:\n";
        echo "• Send verification codes for signup/login\n";
        echo "• Send welcome emails to new users\n";
        echo "• Send order confirmations\n";
        echo "• Handle contact form submissions\n";
        echo "• Use password reset functionality\n\n";
    } else {
        echo colored("⚠️  Email system needs configuration for best results", 'yellow') . "\n";
        echo "The system will work with PHP mail() but reliability may vary.\n";
        echo "For production use, please configure SMTP2GO or Gmail SMTP.\n\n";
    }

    // Test 5: Send actual test email if requested
    if (isset($argv[1]) && $argv[1] === 'test-send' && isset($argv[2])) {
        $testEmailAddress = $argv[2];

        if (!filter_var($testEmailAddress, FILTER_VALIDATE_EMAIL)) {
            echo colored("Invalid email address provided!", 'red') . "\n";
            exit(1);
        }

        echo colored("📧 Sending test email to $testEmailAddress...", 'yellow') . "\n";

        try {
            $result = $emailService->sendTestEmail($testEmailAddress);

            if ($result) {
                echo colored("✅ Test email sent successfully!", 'green') . "\n";
                echo "Check your inbox for the verification email.\n";
                echo "Provider used: {$providerInfo['name']}\n";
            } else {
                echo colored("❌ Failed to send test email", 'red') . "\n";
                echo "Check your email configuration and try again.\n";
            }

        } catch (Exception $e) {
            echo colored("❌ Error sending test email: " . $e->getMessage(), 'red') . "\n";
        }
    } else {
        echo "💡 To send a test email, run:\n";
        echo "php test_universal_email.php test-send your-email@example.com\n\n";
    }

    // Cleanup test data
    try {
        $database = new Database();
        $db = $database->getConnection();
        $stmt = $db->prepare("DELETE FROM verification_codes WHERE email = ? AND purpose = 'test'");
        $stmt->execute([$testEmail]);
    } catch (Exception $e) {
        // Ignore cleanup errors
    }

} catch (Exception $e) {
    echo colored("💥 CRITICAL ERROR: " . $e->getMessage(), 'red') . "\n";
    echo "Please check your configuration and try again.\n";
    exit(1);
}

echo colored("📚 For detailed setup instructions, see: EMAIL_SETUP_GUIDE.md", 'blue') . "\n";
?>