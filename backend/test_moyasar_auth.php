<?php
echo "=== MOYASAR AUTHENTICATION TEST ===\n\n";

$secretKey = 'sk_test_pwQmBCR5kYjugS';
$publishableKey = 'pk_test_qZnixqTQq5sH3EvvsXhgrw426KEUsoAdF93QaJXC';

// Test different authentication methods
echo "Testing different auth methods:\n";

// Method 1: Standard Basic Auth with secret key
$auth1 = base64_encode($secretKey . ':');
echo "Method 1 (secret:): " . substr($auth1, 0, 20) . "...\n";

// Method 2: Bearer token with secret key
$auth2 = $secretKey;
echo "Method 2 (bearer): " . substr($auth2, 0, 20) . "...\n";

// Method 3: Basic auth with publishable key (sometimes used for public endpoints)
$auth3 = base64_encode($publishableKey . ':');
echo "Method 3 (pub:): " . substr($auth3, 0, 20) . "...\n";

echo "Testing credentials:\n";
echo "Secret Key: " . substr($secretKey, 0, 15) . "...\n";
echo "Publishable Key: " . substr($publishableKey, 0, 15) . "...\n\n";

// Test different authentication methods
$authMethods = [
    'Basic with Secret' => ['Authorization: Basic ' . base64_encode($secretKey . ':')],
    'Bearer with Secret' => ['Authorization: Bearer ' . $secretKey],
    'Basic with Publishable' => ['Authorization: Basic ' . base64_encode($publishableKey . ':')],
];

foreach ($authMethods as $methodName => $headers) {
    echo "\n--- Testing $methodName ---\n";

    $ch = curl_init();

    $fullHeaders = array_merge($headers, ['Content-Type: application/json']);

    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://api.moyasar.com/v1/payments',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $fullHeaders,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false, // For testing only
        CURLOPT_SSL_VERIFYHOST => false  // For testing only
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    echo "HTTP Status Code: $httpCode\n";

    if ($error) {
        echo "Curl Error: $error\n";
    } else {
        $decoded = json_decode($response, true);

        if ($httpCode === 200) {
            echo "✓ Authentication successful!\n";
            echo "Number of payments returned: " . count($decoded['payments'] ?? []) . "\n";
            break; // Stop on first successful method
        } elseif ($httpCode === 401) {
            echo "❌ Authentication failed\n";
            if (isset($decoded['message'])) {
                echo "Error: " . $decoded['message'] . "\n";
            }
        } else {
            echo "Unexpected response code: $httpCode\n";
            echo "Response: " . substr($response, 0, 200) . "\n";
        }
    }
}

echo "\n=== END TEST ===\n";
?>