<?php
// Direct test of order creation with proper error handling
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/database.php';

echo "=== DIRECT ORDER CREATION TEST ===\n\n";

try {
    $database = new Database();
    $db = $database->getConnection();

    // Test data that would come from frontend
    $frontendData = [
        'customerInfo' => [
            'name' => 'أحمد محمد',
            'email' => 'ahmed@example.com',
            'phone' => '+966501234567'
        ],
        'items' => [
            [
                'id' => 1,
                'title' => 'كتاب تجريبي',
                'price' => 50.00,
                'quantity' => 1,
                'type' => 'book'
            ]
        ],
        'paymentMethod' => 'stc_pay',
        'total' => 50.00
    ];

    echo "Frontend data:\n";
    echo json_encode($frontendData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

    // Simulate the transformation that happens in orders.php
    $data = $frontendData;

    if (isset($data['customerInfo'])) {
        echo "Converting legacy format...\n";

        $customerInfo = $data['customerInfo'];
        $items = $data['items'] ?? [];
        $paymentMethod = $data['paymentMethod'] ?? null;
        $total = isset($data['total']) ? floatval($data['total']) : null;

        // Convert to new format
        $data = [
            'customer_info' => $customerInfo,
            'items' => $items,
            'payment_method' => $paymentMethod,
            'total' => $total,
            'idempotency_key' => 'legacy_' . time() . '_' . uniqid()
        ];

        echo "Converted data:\n";
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
    }

    // Check validation
    if (!$data || !isset($data['items']) || !is_array($data['items']) || empty($data['items'])) {
        echo "❌ VALIDATION FAILED\n";
        echo "Data: " . var_export($data, true) . "\n";
        echo "Items check: " . var_export($data['items'] ?? 'NOT SET', true) . "\n";
        exit;
    }

    echo "✓ Validation passed\n\n";

    // Test payment initialization with the correct data format
    echo "Testing payment initialization...\n";

    // Include the payment API functions
    require_once __DIR__ . '/services/moyasar_service.php';

    $transaction_id = 'test_txn_' . time();
    $order_id = 'test_order_' . time();

    $paymentData = [
        'amount' => $data['total'],
        'currency' => 'SAR',
        'order_id' => $order_id,
        'customer_info' => $data['customer_info'],
        'items' => $data['items']
    ];

    echo "Payment data:\n";
    echo json_encode($paymentData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

    // Test Moyasar payment initialization
    function initializeMoyasarPaymentTest($payment_method, $transaction_id, $data) {
        try {
            $moyasarService = new MoyasarService();

            $paymentData = [
                'amount' => $data['amount'],
                'currency' => $data['currency'] ?? 'SAR',
                'description' => ucfirst(str_replace('_', ' ', $payment_method)) . ' payment for order ' . ($data['order_id'] ?? 'N/A'),
                'transaction_id' => $transaction_id,
                'order_id' => $data['order_id'] ?? '',
                'payment_method' => $payment_method,
                'customer_info' => $data['customer_info'] ?? []
            ];

            $moyasarResponse = $moyasarService->createPayment($paymentData);

            return [
                'status' => 'redirect',
                'redirect_url' => '/moyasar-payment.html?transaction=' . $transaction_id .
                                 '&amount=' . $data['amount'] .
                                 '&order=' . ($data['order_id'] ?? '') .
                                 '&method=' . $payment_method,
                'transaction_id' => $transaction_id,
                'provider_transaction_id' => $moyasarResponse['payment_id'],
                'provider' => 'moyasar',
                'payment_method' => $payment_method,
                'message' => 'Redirecting to ' . ucfirst(str_replace('_', ' ', $payment_method)) . ' payment via Moyasar'
            ];

        } catch (Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
                'payment_method' => $payment_method
            ];
        }
    }

    $result = initializeMoyasarPaymentTest('stc_pay', $transaction_id, $paymentData);

    echo "Payment initialization result:\n";
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

    if ($result['status'] === 'redirect') {
        echo "✅ SUCCESS: Payment would redirect to: " . $result['redirect_url'] . "\n";
    } else {
        echo "❌ FAILED: " . ($result['error'] ?? 'Unknown error') . "\n";
    }

} catch (Exception $e) {
    echo "❌ EXCEPTION: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== END TEST ===\n";
?>