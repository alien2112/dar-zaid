<?php
// Test payment initialization without order creation
echo "=== PAYMENT INITIALIZATION TEST ===\n\n";

require_once __DIR__ . '/services/moyasar_service.php';

// Test all Moyasar payment methods directly
$paymentMethods = [
    'stc_pay' => 'STC Pay',
    'visa' => 'Visa Card',
    'mastercard' => 'Mastercard',
    'mada' => 'Mada (Saudi)',
    'apple_pay' => 'Apple Pay'
];

foreach ($paymentMethods as $method => $name) {
    echo "Testing $name ($method)...\n";

    try {
        // Test the unified Moyasar payment function directly
        require_once __DIR__ . '/api/payments.php';

        $transaction_id = 'test_txn_' . $method . '_' . time();
        $test_data = [
            'amount' => 75.00,
            'currency' => 'SAR',
            'order_id' => 'test_order_' . $method . '_' . time(),
            'customer_info' => [
                'name' => 'أحمد محمد',
                'email' => 'ahmed@example.com',
                'phone' => '+966501234567'
            ]
        ];

        $result = initializeMoyasarPayment($method, $transaction_id, $test_data);

        echo "✓ $name: " . $result['status'] . "\n";
        echo "  Provider: " . $result['provider'] . "\n";
        echo "  Redirect: " . $result['redirect_url'] . "\n";
        echo "  Message: " . $result['message'] . "\n\n";

    } catch (Exception $e) {
        echo "❌ $name: " . $e->getMessage() . "\n\n";
    }
}

echo "=== FRONTEND INTEGRATION SUMMARY ===\n\n";

echo "Your payment system is now configured to use Moyasar for:\n\n";

echo "📱 **STC Pay**: Digital wallet popular in Saudi Arabia\n";
echo "   - Processed through Moyasar's STC Pay integration\n";
echo "   - Instant payment confirmation\n\n";

echo "💳 **Card Payments**: All major cards supported\n";
echo "   - Visa: International and local cards\n";
echo "   - Mastercard: International and local cards\n";
echo "   - Mada: Saudi domestic debit network\n";
echo "   - All processed through Moyasar's secure gateway\n\n";

echo "🍎 **Apple Pay**: Tokenized wallet payments\n";
echo "   - For iOS Safari and supported devices\n";
echo "   - Processed through Moyasar's Apple Pay integration\n\n";

echo "**Payment Flow:**\n";
echo "1. User selects payment method in checkout\n";
echo "2. Frontend calls `/api/orders` to create order\n";
echo "3. Frontend calls `/api/payments/initialize` for payment\n";
echo "4. User redirected to `/moyasar-payment.html` with payment form\n";
echo "5. Moyasar processes payment and sends webhook\n";
echo "6. System updates order status automatically\n\n";

echo "**Test URLs:**\n";
echo "- Order API: http://localhost:8000/api/orders\n";
echo "- Payment API: http://localhost:8000/api/payments/initialize\n";
echo "- Payment Form: http://localhost:8000/moyasar-payment.html\n";
echo "- Webhook: http://localhost:8000/api/payments/callback/moyasar\n\n";

echo "**Note**: Currently using mock mode due to test credentials.\n";
echo "For production, update credentials in backend/config/moyasar_config.php\n";
?>