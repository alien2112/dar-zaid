<?php
// Test PUT request for books API
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing PUT request for books API...\n\n";

// Simulate the request
$_SERVER['REQUEST_METHOD'] = 'PUT';
$_SERVER['REQUEST_URI'] = '/api/books/1';
$_SERVER['HTTP_ORIGIN'] = 'http://localhost:3000';

// Simulate JSON input
$testData = [
    'title' => 'Updated Test Book',
    'author' => 'Updated Author',
    'description' => 'Updated description',
    'price' => 29.99,
    'category' => 'History', // This should be converted to category_id
    'publisher' => 'Test Publisher',
    'stock_quantity' => 20,
    'isbn' => '978-1234567890'
];

// Mock the input stream
$jsonData = json_encode($testData);
$tempFile = tmpfile();
fwrite($tempFile, $jsonData);
rewind($tempFile);

// Override php://input
$originalInput = 'php://input';
$GLOBALS['mock_input'] = $jsonData;

// Create a custom stream wrapper to mock php://input
class MockInputStream {
    public function stream_open($path, $mode, $options, &$opened_path) {
        return true;
    }
    
    public function stream_read($count) {
        global $mock_input;
        $data = $mock_input;
        $mock_input = '';
        return $data;
    }
    
    public function stream_eof() {
        return true;
    }
}

stream_wrapper_register('mock', 'MockInputStream');

// Override file_get_contents for php://input
function file_get_contents($filename) {
    if ($filename === 'php://input') {
        global $mock_input;
        return $mock_input;
    }
    return \file_get_contents($filename);
}

try {
    ob_start();
    require_once 'api/books.php';
    $output = ob_get_clean();
    echo "Output: " . $output . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>

