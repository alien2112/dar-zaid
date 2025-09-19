<?php
/**
 * CORS Test Script
 * This script tests the CORS configuration
 */

// Include the CORS configuration
require_once __DIR__ . '/config/cors.php';

// Test data
$test_data = [
    'message' => 'CORS test successful',
    'timestamp' => date('Y-m-d H:i:s'),
    'origin' => $_SERVER['HTTP_ORIGIN'] ?? 'No origin header',
    'method' => $_SERVER['REQUEST_METHOD'],
    'headers' => [
        'Access-Control-Allow-Origin' => $_SERVER['HTTP_ACCESS_CONTROL_ALLOW_ORIGIN'] ?? 'Not set',
        'Access-Control-Allow-Methods' => $_SERVER['HTTP_ACCESS_CONTROL_ALLOW_METHODS'] ?? 'Not set',
        'Access-Control-Allow-Headers' => $_SERVER['HTTP_ACCESS_CONTROL_ALLOW_HEADERS'] ?? 'Not set',
        'Access-Control-Allow-Credentials' => $_SERVER['HTTP_ACCESS_CONTROL_ALLOW_CREDENTIALS'] ?? 'Not set'
    ]
];

// Return test data
echo json_encode($test_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
