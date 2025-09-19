<?php
// Debug script to reproduce STC Pay order creation error
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== DEBUGGING ORDER CREATION ===\n\n";

// Test STC Pay order creation
$testData = [
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
    'total' => 50.00,
    'currency' => 'SAR'
];

echo "1. Testing with legacy format (customerInfo)...\n";
echo "Data: " . json_encode($testData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

// Simulate the API call
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['REQUEST_URI'] = '/api/orders';

// Capture the input data
$input = json_encode($testData);
file_put_contents('php://input', $input);

try {
    ob_start();
    require_once __DIR__ . '/api/orders.php';
    $output = ob_get_clean();

    echo "API Response:\n";
    echo $output . "\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
} catch (Error $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== END DEBUG ===\n";
?>