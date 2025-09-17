<?php
// Simple image upload handler
// Saves files under backend/uploads and returns a public URL path

// CORS and JSON headers
header('Access-Control-Allow-Origin: ' . (isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : 'http://localhost:3000'));
header('Vary: Origin');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

if (!isset($_FILES['image'])) {
    http_response_code(400);
    echo json_encode(['error' => 'No image file uploaded. Use form field name "image".']);
    exit();
}

$file = $_FILES['image'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'Upload failed', 'code' => $file['error']]);
    exit();
}

// Validate mime type
$allowedMime = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($file['tmp_name']);

if (!isset($allowedMime[$mime])) {
    http_response_code(400);
    echo json_encode(['error' => 'Unsupported file type']);
    exit();
}

// Prepare upload directory
$uploadDir = dirname(__DIR__) . '/uploads'; // backend/uploads
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0775, true);
}

// Generate safe filename
$ext = $allowedMime[$mime];
$base = bin2hex(random_bytes(8));
$filename = $base . '.' . $ext;
$destPath = $uploadDir . '/' . $filename;

if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to move uploaded file']);
    exit();
}

// Public URL path relative to web root. Assuming backend is served at /backend
$publicPath = '/backend/uploads/' . $filename;
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : ((isset($_SERVER['REQUEST_SCHEME'])?$_SERVER['REQUEST_SCHEME']:'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost:8000'));
$absoluteUrl = rtrim($origin, '/') . $publicPath;

http_response_code(201);
echo json_encode(['url' => $absoluteUrl, 'path' => $publicPath]);
?>




