<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include centralized CORS configuration
require_once __DIR__ . '/../config/cors.php';

include_once __DIR__ . '/../config/database.php';

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
    case 'team_photos':
        include 'team_photos.php';
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
    case 'settings':
        include 'settings.php';
        break;
    case 'payments':
        include 'payments.php';
        break;
    case 'payment_methods':
        include 'payment_methods.php';
        break;
    case 'custom_filters':
        include 'custom_filters.php';
        break;
    case 'filter_options':
        include 'filter_options.php';
        break;
    case 'formspree_proxy':
        include 'formspree_proxy.php';
        break;
    default:
        http_response_code(404);
        echo json_encode(['message' => 'Endpoint not found']);
}
?>
