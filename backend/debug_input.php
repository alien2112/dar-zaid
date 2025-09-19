<?php
// Debug the actual input being received
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== INPUT DEBUG ===\n\n";

// Check what's being sent
$input = file_get_contents('php://input');
echo "Raw Input: " . var_export($input, true) . "\n\n";

$decoded = json_decode($input, true);
echo "Decoded JSON: " . var_export($decoded, true) . "\n\n";

// Test the conversion logic
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
    'total' => 50.00
];

echo "Test data structure:\n";
echo json_encode($testData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

// Simulate the conversion logic
$data = $testData;

if (isset($data['customerInfo'])) {
    echo "Converting legacy format...\n";

    $customerInfo = $data['customerInfo'];
    $items = $data['items'] ?? [];
    $paymentMethod = $data['paymentMethod'] ?? null;
    $total = isset($data['total']) ? floatval($data['total']) : null;

    echo "Customer Info: " . json_encode($customerInfo, JSON_UNESCAPED_UNICODE) . "\n";
    echo "Items: " . json_encode($items, JSON_UNESCAPED_UNICODE) . "\n";
    echo "Payment Method: " . $paymentMethod . "\n";
    echo "Total: " . $total . "\n\n";

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
    echo "❌ VALIDATION FAILED: Items are required\n";
    echo "Data exists: " . (isset($data) ? 'YES' : 'NO') . "\n";
    echo "Items key exists: " . (isset($data['items']) ? 'YES' : 'NO') . "\n";
    echo "Items is array: " . (isset($data['items']) && is_array($data['items']) ? 'YES' : 'NO') . "\n";
    echo "Items not empty: " . (isset($data['items']) && !empty($data['items']) ? 'YES' : 'NO') . "\n";
} else {
    echo "✓ Validation passed\n";
}

echo "\n=== END DEBUG ===\n";
?>