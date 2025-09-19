<?php
/**
 * Download CA certificates for SSL verification
 * This script downloads the latest CA bundle from curl.se
 */

echo "Downloading CA certificates...\n";

$cacertUrl = 'https://curl.se/ca/cacert.pem';
$cacertPath = __DIR__ . '/config/cacert.pem';

// Create config directory if it doesn't exist
if (!file_exists(dirname($cacertPath))) {
    mkdir(dirname($cacertPath), 0755, true);
}

// Download the CA certificate bundle
$context = stream_context_create([
    'http' => [
        'timeout' => 60,
        'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
    ],
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false
    ]
]);

$cacertContent = file_get_contents($cacertUrl, false, $context);

if ($cacertContent === false) {
    echo "Failed to download CA certificates.\n";
    echo "You can manually download from: $cacertUrl\n";
    echo "And save it as: $cacertPath\n";
    exit(1);
}

// Save the CA bundle
if (file_put_contents($cacertPath, $cacertContent) === false) {
    echo "Failed to save CA certificates to: $cacertPath\n";
    exit(1);
}

echo "✓ CA certificates downloaded successfully to: $cacertPath\n";
echo "File size: " . number_format(filesize($cacertPath)) . " bytes\n";
echo "You can now run your Moyasar tests with proper SSL verification.\n";
?>