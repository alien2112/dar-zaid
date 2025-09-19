<?php
require_once __DIR__ . '/services/moyasar_service.php';
require_once __DIR__ . '/config/database.php';

echo "=== MOYASAR TEST INTEGRATION ===\n\n";

try {
    // Test 1: Initialize Moyasar Service
    echo "1. Testing Moyasar Service Initialization...\n";
    $moyasarService = new MoyasarService();
    echo "✓ Moyasar service initialized successfully\n\n";

    // Test 2: Test Configuration
    echo "2. Testing Configuration...\n";
    echo "API Base URL: " . MoyasarConfig::API_BASE_URL . "\n";
    echo "Currency: " . MoyasarConfig::CURRENCY . "\n";
    echo "Publishable Key: " . substr(MoyasarConfig::PUBLISHABLE_KEY, 0, 20) . "...\n";
    echo "✓ Configuration loaded successfully\n\n";

    // Test 3: Create Test Payment
    echo "3. Creating Test Payment...\n";
    $testPaymentData = [
        'amount' => 100.00,
        'currency' => 'SAR',
        'description' => 'Test payment for integration',
        'transaction_id' => 'test_' . time(),
        'order_id' => 'order_test_' . time(),
        'payment_method' => 'visa',
        'customer_info' => [
            'name' => 'أحمد محمد',
            'email' => 'test@example.com'
        ]
    ];

    $paymentResult = $moyasarService->createPayment($testPaymentData);
    echo "✓ Payment created successfully\n";
    echo "Payment ID: " . $paymentResult['payment_id'] . "\n";
    echo "Status: " . $paymentResult['payment_status'] . "\n\n";

    // Test 4: Retrieve Payment
    echo "4. Retrieving Payment Details...\n";
    $paymentDetails = $moyasarService->getPayment($paymentResult['payment_id']);
    echo "✓ Payment details retrieved successfully\n";
    echo "Amount: " . ($paymentDetails['amount'] / 100) . " " . $paymentDetails['currency'] . "\n";
    echo "Status: " . $paymentDetails['status'] . "\n\n";

    // Test 5: Test Webhook Processing
    echo "5. Testing Webhook Processing...\n";
    $mockWebhookData = [
        'id' => $paymentResult['payment_id'],
        'status' => 'paid',
        'amount' => 10000, // 100.00 SAR in halalas
        'currency' => 'SAR',
        'metadata' => [
            'transaction_id' => $testPaymentData['transaction_id'],
            'order_id' => $testPaymentData['order_id']
        ]
    ];

    $processedWebhook = $moyasarService->processWebhook($mockWebhookData);
    echo "✓ Webhook processing successful\n";
    echo "Processed Status: " . $processedWebhook['status'] . "\n";
    echo "Transaction ID: " . $processedWebhook['transaction_id'] . "\n\n";

    echo "=== ALL TESTS PASSED ✓ ===\n";
    echo "Your Moyasar integration is ready for testing!\n\n";

    echo "Next steps:\n";
    echo "1. Test payment flow through your website\n";
    echo "2. Test webhook endpoints with Moyasar webhooks\n";
    echo "3. Test different payment methods (Visa, Mastercard, STC Pay)\n";
    echo "4. Set up webhook URL in Moyasar dashboard:\n";
    echo "   Webhook URL: https://yourdomain.com/api/payments/callback/moyasar\n\n";

} catch (Exception $e) {
    echo "❌ TEST FAILED: " . $e->getMessage() . "\n";
    echo "Please check your configuration and try again.\n";
}
?>