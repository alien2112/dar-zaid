<?php
// Router for PHP built-in server
// This file handles routing for development server

$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);

// Remove query string
$path = strtok($path, '?');

// Handle API routes
if (strpos($path, '/api/') === 0) {
    // Set CORS headers for all API requests (dynamic origin, allow credentials)
    $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
    if (!empty($origin)) {
        header("Access-Control-Allow-Origin: $origin");
        header("Vary: Origin");
    } else {
        // Fallback for non-CORS requests
        header("Access-Control-Allow-Origin: http://localhost:3000");
        header("Vary: Origin");
    }
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
    header("Content-Type: application/json; charset=UTF-8");
    
    // Handle preflight OPTIONS request
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        http_response_code(200);
        exit(0);
    }
    
    // Route to API handler
    include_once __DIR__ . '/api/index.php';
    return;
}

// For static files and uploads, let the server handle them
if (file_exists(__DIR__ . $path)) {
    return false; // Let the server handle static files
}

// Serve uploaded files under /backend/uploads/* when using built-in server
if (strpos($path, '/backend/uploads/') === 0) {
    $fullPath = __DIR__ . str_replace('/backend', '', $path);
    if (file_exists($fullPath)) {
        return false;
    }
}

// Default fallback
http_response_code(404);
echo json_encode(['error' => 'Not found'], JSON_UNESCAPED_UNICODE);
?>
