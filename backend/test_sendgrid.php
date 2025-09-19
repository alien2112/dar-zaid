<?php
require_once __DIR__ . '/services/sendgrid_service.php';

// Test SendGrid integration
echo "Testing SendGrid Integration...\n\n";

// Check if SendGrid is configured
$apiKey = getenv('SENDGRID_API_KEY');
if (empty($apiKey)) {
    echo "❌ SENDGRID_API_KEY not configured\n";
    echo "Please set the environment variable: export SENDGRID_API_KEY=your_api_key\n";
    exit(1);
}

echo "✅ SendGrid API Key found\n";

$sendGridService = new SendGridService();

// Test email sending
$testEmail = 'test@example.com';
$testCode = '123456';

echo "Testing verification code email...\n";
$result = $sendGridService->sendVerificationCode($testEmail, $testCode);

if ($result) {
    echo "✅ Verification email sent successfully\n";
} else {
    echo "❌ Failed to send verification email\n";
}

// Test contact notification
$contactData = [
    'name' => 'Test User',
    'email' => 'test@example.com',
    'phone' => '+966501234567',
    'subject' => 'Test Subject',
    'message' => 'This is a test message'
];

echo "Testing contact notification email...\n";
$result = $sendGridService->sendContactNotification($contactData);

if ($result) {
    echo "✅ Contact notification email sent successfully\n";
} else {
    echo "❌ Failed to send contact notification email\n";
}

// Test order confirmation
$orderData = [
    'order_id' => 'test_order_123',
    'customer_info' => ['email' => 'test@example.com', 'name' => 'Test User'],
    'items' => [
        ['title' => 'Test Book', 'quantity' => 1, 'price' => 50.00, 'total' => 50.00]
    ],
    'subtotal' => 50.00,
    'shipping_cost' => 10.00,
    'tax_amount' => 5.00,
    'total_amount' => 65.00,
    'status' => 'pending'
];

echo "Testing order confirmation email...\n";
$result = $sendGridService->sendOrderConfirmation($orderData);

if ($result) {
    echo "✅ Order confirmation email sent successfully\n";
} else {
    echo "❌ Failed to send order confirmation email\n";
}

echo "\n🎉 SendGrid integration test completed!\n";
echo "Check your email inbox for the test messages.\n";
?>






