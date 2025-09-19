<?php
/**
 * SMTP2GO Email Setup Script for Dar Zaid
 *
 * This script helps set up SMTP2GO email service
 */

echo "🚀 SMTP2GO Email Setup for Dar Zaid\n";
echo "===================================\n\n";

echo "SMTP2GO is a reliable email service that works globally!\n";
echo "✅ Free for up to 1,000 emails per month\n";
echo "✅ Works in all countries and regions\n";
echo "✅ High deliverability rates\n";
echo "✅ Easy to set up\n\n";

echo "📋 Setup Steps:\n";
echo "1. Create free account at https://www.smtp2go.com/\n";
echo "2. Verify your email address\n";
echo "3. Go to Settings → API Keys in the dashboard\n";
echo "4. Create a new API key\n";
echo "5. Copy the API key and enter it below\n\n";

// Check if running interactively
if (php_sapi_name() === 'cli') {
    echo "📝 Enter your SMTP2GO API Key: ";
    $handle = fopen("php://stdin", "r");
    $apiKey = trim(fgets($handle));
    fclose($handle);

    if (!empty($apiKey) && strlen($apiKey) > 10) {
        // Create .env file or update existing one
        $envFile = __DIR__ . '/../.env';
        $envContent = '';

        // Read existing .env file if it exists
        if (file_exists($envFile)) {
            $envContent = file_get_contents($envFile);
        }

        // Add or update SMTP2GO API key
        if (strpos($envContent, 'SMTP2GO_API_KEY') !== false) {
            // Replace existing key
            $envContent = preg_replace('/SMTP2GO_API_KEY=.*/', 'SMTP2GO_API_KEY=' . $apiKey, $envContent);
        } else {
            // Add new key
            if (!empty($envContent) && !str_ends_with($envContent, "\n")) {
                $envContent .= "\n";
            }
            $envContent .= "# SMTP2GO Email Configuration\n";
            $envContent .= "SMTP2GO_API_KEY=" . $apiKey . "\n";
        }

        if (file_put_contents($envFile, $envContent)) {
            echo "\n✅ SMTP2GO API key saved to .env file!\n";
            echo "📄 Location: " . $envFile . "\n\n";

            // Set environment variable for current session
            putenv("SMTP2GO_API_KEY=" . $apiKey);

            echo "🧪 Testing email configuration...\n";
            echo "================================\n";

            // Run test
            include __DIR__ . '/test_universal_email.php';

        } else {
            echo "\n❌ Failed to save .env file!\n";
            echo "Please manually set the environment variable:\n";
            echo "SMTP2GO_API_KEY=" . $apiKey . "\n\n";

            echo "Windows Command Prompt:\n";
            echo "set SMTP2GO_API_KEY=" . $apiKey . "\n\n";

            echo "Windows PowerShell:\n";
            echo '$env:SMTP2GO_API_KEY="' . $apiKey . '"' . "\n\n";

            echo "Linux/Mac:\n";
            echo "export SMTP2GO_API_KEY=" . $apiKey . "\n";
        }

    } else {
        echo "\n❌ Invalid API key!\n";
        echo "Please check your API key and try again.\n";
        echo "API keys are usually long alphanumeric strings.\n";
    }
} else {
    echo "💡 To complete setup, run this script from command line:\n";
    echo "php " . __FILE__ . "\n\n";

    echo "Or manually set the environment variable:\n";
    echo "SMTP2GO_API_KEY=your-api-key-here\n\n";
}

echo "\n📚 Additional Resources:\n";
echo "• Complete setup guide: EMAIL_SETUP_GUIDE.md\n";
echo "• SMTP2GO documentation: https://www.smtp2go.com/docs\n";
echo "• Test email system: php test_universal_email.php\n";
echo "• Send test email: php test_universal_email.php test-send your-email@example.com\n\n";

echo "🎉 Once configured, your email system will support:\n";
echo "✉️  Signup verification codes\n";
echo "✉️  Welcome emails for new users\n";
echo "✉️  Order confirmations and updates\n";
echo "✉️  Contact form notifications\n";
echo "✉️  Password reset functionality\n";
?>