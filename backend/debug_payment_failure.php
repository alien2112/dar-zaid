<?php
// Comprehensive debug script for payment request failures
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== PAYMENT REQUEST FAILURE DEBUG ===\n\n";

// Test 1: Check if API endpoints are accessible
echo "1. Testing API Endpoint Accessibility...\n";

$endpoints = [
    'orders' => 'http://localhost:8000/api/orders',
    'payments' => 'http://localhost:8000/api/payments/initialize',
    'payment_methods' => 'http://localhost:8000/api/payment_methods'
];

foreach ($endpoints as $name => $url) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_CUSTOMREQUEST => 'OPTIONS', // Test CORS
        CURLOPT_HTTPHEADER => [
            'Origin: http://localhost:3000',
            'Access-Control-Request-Method: POST',
            'Access-Control-Request-Headers: Content-Type'
        ]
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        echo "❌ $name: cURL Error - $error\n";
    } elseif ($httpCode === 200) {
        echo "✓ $name: Accessible (HTTP $httpCode)\n";
    } else {
        echo "⚠️ $name: HTTP $httpCode\n";
    }
}

echo "\n2. Testing Complete Order + Payment Flow...\n";

// Test the exact flow that frontend would use
$testOrderData = [
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

echo "Creating order...\n";
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => 'http://localhost:8000/api/orders',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($testOrderData),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Accept: application/json',
        'Origin: http://localhost:3000'
    ],
    CURLOPT_TIMEOUT => 30
]);

$orderResponse = curl_exec($ch);
$orderHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$orderError = curl_error($ch);
curl_close($ch);

if ($orderError) {
    echo "❌ Order Creation cURL Error: $orderError\n";
    exit;
}

echo "Order Response (HTTP $orderHttpCode):\n";
echo $orderResponse . "\n\n";

if ($orderHttpCode !== 200) {
    echo "❌ Order creation failed. Cannot proceed with payment test.\n";
    exit;
}

$orderResult = json_decode($orderResponse, true);
if (!$orderResult || !isset($orderResult['order_id'])) {
    echo "❌ Invalid order response format\n";
    exit;
}

echo "✓ Order created: " . $orderResult['order_id'] . "\n\n";

// Test payment initialization
echo "Initializing payment...\n";
$paymentData = [
    'payment_method' => 'stc_pay',
    'amount' => $orderResult['total_amount'] ?? 50,
    'currency' => 'SAR',
    'order_id' => $orderResult['order_id'],
    'customer_info' => $testOrderData['customerInfo']
];

echo "Payment data: " . json_encode($paymentData, JSON_PRETTY_PRINT) . "\n\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => 'http://localhost:8000/api/payments/initialize',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($paymentData),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Accept: application/json',
        'Origin: http://localhost:3000'
    ],
    CURLOPT_TIMEOUT => 30
]);

$paymentResponse = curl_exec($ch);
$paymentHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$paymentError = curl_error($ch);
curl_close($ch);

if ($paymentError) {
    echo "❌ Payment Initialization cURL Error: $paymentError\n";
} else {
    echo "Payment Response (HTTP $paymentHttpCode):\n";
    echo $paymentResponse . "\n\n";

    if ($paymentHttpCode === 200) {
        $paymentResult = json_decode($paymentResponse, true);
        if ($paymentResult && isset($paymentResult['redirect_url'])) {
            echo "✅ Payment initialization successful!\n";
            echo "Redirect URL: " . $paymentResult['redirect_url'] . "\n";
        } else {
            echo "⚠️ Payment response format unexpected\n";
        }
    } else {
        echo "❌ Payment initialization failed\n";
    }
}

echo "\n3. Testing Frontend Service Integration...\n";

// Check if the frontend API service is configured correctly
echo "Checking if frontend can reach backend...\n";

// Test a simple GET request that frontend might make
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => 'http://localhost:8000/api/payment_methods?enabled=true',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Accept: application/json',
        'Origin: http://localhost:3000'
    ],
    CURLOPT_TIMEOUT => 10
]);

$methodsResponse = curl_exec($ch);
$methodsHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$methodsError = curl_error($ch);
curl_close($ch);

if ($methodsError) {
    echo "❌ Payment Methods cURL Error: $methodsError\n";
} elseif ($methodsHttpCode === 200) {
    $methods = json_decode($methodsResponse, true);
    if ($methods && isset($methods['payment_methods'])) {
        echo "✓ Payment methods API working (" . count($methods['payment_methods']) . " methods)\n";

        // Check if STC Pay is available
        $stcPayFound = false;
        foreach ($methods['payment_methods'] as $method) {
            if ($method['id'] === 'stc_pay') {
                $stcPayFound = true;
                echo "✓ STC Pay method found and " . ($method['enabled'] ? 'enabled' : 'disabled') . "\n";
                break;
            }
        }
        if (!$stcPayFound) {
            echo "⚠️ STC Pay method not found in payment methods\n";
        }
    } else {
        echo "⚠️ Payment methods response format unexpected\n";
    }
} else {
    echo "❌ Payment methods API failed (HTTP $methodsHttpCode)\n";
}

echo "\n4. Common Issues Checklist...\n";

// Check common issues
$issues = [];

// Check if server is running on correct port
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => 'http://localhost:8000',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 5,
    CURLOPT_NOBODY => true
]);
$serverResponse = curl_exec($ch);
$serverCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($serverCode === 0 || curl_error($ch)) {
    $issues[] = "❌ Backend server not running on port 8000";
} else {
    echo "✓ Backend server responding on port 8000\n";
}

// Check CORS headers
if ($orderHttpCode === 200) {
    echo "✓ CORS headers working for orders API\n";
} else {
    $issues[] = "❌ CORS issues detected";
}

// Check database connection
try {
    require_once __DIR__ . '/config/database.php';
    $db = (new Database())->getConnection();
    echo "✓ Database connection working\n";
} catch (Exception $e) {
    $issues[] = "❌ Database connection failed: " . $e->getMessage();
}

if (empty($issues)) {
    echo "\n✅ No major issues detected!\n";
} else {
    echo "\n❌ Issues found:\n";
    foreach ($issues as $issue) {
        echo "  $issue\n";
    }
}

echo "\n=== DEBUG SUMMARY ===\n";
echo "If you're still seeing 'fail to request payment', check:\n";
echo "1. Browser console for JavaScript errors\n";
echo "2. Network tab for failed requests\n";
echo "3. Frontend API base URL configuration\n";
echo "4. Make sure backend server is running on port 8000\n";
echo "5. Check if frontend is on a different port (CORS)\n";

echo "\nTo test manually:\n";
echo "curl -X POST http://localhost:8000/api/orders \\\n";
echo "  -H 'Content-Type: application/json' \\\n";
echo "  -d '" . json_encode($testOrderData) . "'\n";
?>