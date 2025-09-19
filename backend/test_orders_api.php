<?php
// Test the orders API endpoint directly
echo "=== ORDERS API TEST ===\n\n";

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

// Use cURL to test the API endpoint
$ch = curl_init();

curl_setopt_array($ch, [
    CURLOPT_URL => 'http://localhost:8000/api/orders',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($testData),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Accept: application/json'
    ],
    CURLOPT_TIMEOUT => 30
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Status Code: $httpCode\n";

if ($error) {
    echo "cURL Error: $error\n";
} else {
    echo "Response:\n";
    if ($httpCode === 200) {
        $decoded = json_decode($response, true);
        if ($decoded) {
            echo json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        } else {
            echo "Raw response: $response\n";
        }
    } else {
        echo "Error response: $response\n";
    }
}

echo "\n=== END TEST ===\n";
?>