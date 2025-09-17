<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// CORS Headers - Allow all origins for development
// Dynamic CORS with credentials support
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '*';
header("Access-Control-Allow-Origin: $origin");
header("Vary: Origin");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=UTF-8");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

// Get the request URI and parse it
$request = $_SERVER['REQUEST_URI'];
$path = parse_url($request, PHP_URL_PATH);

// Remove base path if running from subdirectory
// Support both /api/* and /backend/api/* base paths
$basePaths = ['/api/', '/backend/api/'];
foreach ($basePaths as $basePath) {
    if (strpos($path, $basePath) === 0) {
        $path = substr($path, strlen($basePath));
        break;
    }
}

// Split path into segments
$pathSegments = array_filter(explode('/', $path));
$endpoint = $pathSegments[0] ?? '';

switch($endpoint) {
    case 'packages':
        include 'packages.php';
        break;
    case 'books':
        include 'books.php';
        break;
    case 'books_import':
        include 'books_import.php';
        break;
    case 'blog':
        include 'blog.php';
        break;
    case 'news':
        include 'news.php';
        break;
    case 'contact':
        include 'contact.php';
        break;
    case 'auth':
        include 'auth.php';
        break;
    case 'signup':
        include 'signup.php';
        break;
    case 'categories':
        include 'categories.php';
        break;
    case 'moving_bar':
        include 'moving_bar.php';
        break;
    case 'slider':
        include 'slider.php';
        break;
    case 'dynamic_categories':
        include 'dynamic_categories.php';
        break;
    case 'book_of_week':
        include 'book_of_week.php';
        break;
    case 'upload':
        include 'upload.php';
        break;
    case 'dev_seed':
        include 'dev_seed.php';
        break;
    case 'reviews':
        include 'reviews.php';
        break;
    case 'orders':
        include 'orders.php';
        break;
    default:
        http_response_code(404);
        echo json_encode(['message' => 'Endpoint not found']);
}
?>
