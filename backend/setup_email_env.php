<?php
/**
 * Email Environment Setup Script
 *
 * This script helps set up the Gmail app password for the email system
 */

echo "🔧 Gmail Email System Setup for Dar Zaid\n";
echo "=====================================\n\n";

echo "This script will help you set up the Gmail app password.\n\n";

echo "📋 Prerequisites:\n";
echo "1. Gmail account: Dar.zaid.2022@gmail.com\n";
echo "2. 2-Factor Authentication enabled on the account\n";
echo "3. App password generated from Google Account settings\n\n";

echo "📝 Steps to get Gmail App Password:\n";
echo "1. Go to: https://myaccount.google.com/apppasswords\n";
echo "2. Sign in with Dar.zaid.2022@gmail.com\n";
echo "3. Select 'Mail' as the app\n";
echo "4. Select 'Other' as device and enter 'Dar Zaid Website'\n";
echo "5. Copy the 16-character password\n\n";

// Check if running interactively
if (php_sapi_name() === 'cli') {
    echo "📝 Enter your Gmail App Password (16 characters): ";
    $handle = fopen("php://stdin", "r");
    $appPassword = trim(fgets($handle));
    fclose($handle);

    if (strlen($appPassword) === 16) {
        // Create .env file
        $envContent = "# Gmail Email Configuration\n";
        $envContent .= "GMAIL_APP_PASSWORD=" . $appPassword . "\n";
        $envContent .= "EMAIL_DEBUG=false\n";
        $envContent .= "EMAIL_TEST_MODE=false\n";
        $envContent .= "ADMIN_EMAIL=Dar.zaid.2022@gmail.com\n";

        $envFile = __DIR__ . '/../.env';

        if (file_put_contents($envFile, $envContent)) {
            echo "\n✅ App password saved to .env file!\n";
            echo "📄 Location: " . $envFile . "\n\n";

            // Set environment variable for current session
            putenv("GMAIL_APP_PASSWORD=" . $appPassword);

            echo "🧪 Running email test...\n";
            echo "========================\n";

            // Run test
            include __DIR__ . '/test_gmail_service.php';

        } else {
            echo "\n❌ Failed to save .env file!\n";
            echo "Please manually set the environment variable:\n";
            echo "GMAIL_APP_PASSWORD=" . $appPassword . "\n";
        }

    } else {
        echo "\n❌ Invalid app password length!\n";
        echo "App passwords should be exactly 16 characters.\n";
        echo "Please check and try again.\n";
    }
} else {
    echo "💡 To complete setup, run this script from command line:\n";
    echo "php " . __FILE__ . "\n\n";

    echo "Or manually set the environment variable:\n";
    echo "GMAIL_APP_PASSWORD=your-16-character-password\n\n";
}

echo "📚 For more information, see: GMAIL_EMAIL_SETUP.md\n";
?>