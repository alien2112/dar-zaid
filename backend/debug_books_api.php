<?php
// Debug script for books API
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing books API...\n\n";

// Simulate the request
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/api/books/1';
$_SERVER['HTTP_ORIGIN'] = 'http://localhost:3000';

try {
    require_once 'api/books.php';
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>

