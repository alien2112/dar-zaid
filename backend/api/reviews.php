<?php
// Include centralized CORS configuration
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];

// GET /api/reviews?book_id=123  -> list reviews for book
// POST /api/reviews { book_id, user_id, rating, comment }

if ($method === 'GET') {
    $bookId = isset($_GET['book_id']) ? intval($_GET['book_id']) : 0;
    if ($bookId <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'book_id is required']);
        exit();
    }
    try {
        $stmt = $db->prepare('SELECT r.id, r.rating, r.comment, r.created_at, u.name as user_name
                               FROM reviews r JOIN users u ON r.user_id = u.id
                               WHERE r.book_id = :book_id ORDER BY r.created_at DESC');
        $stmt->execute(['book_id' => $bookId]);
        $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $avgStmt = $db->prepare('SELECT AVG(rating) as avg_rating, COUNT(*) as count FROM reviews WHERE book_id = :book_id');
        $avgStmt->execute(['book_id' => $bookId]);
        $stats = $avgStmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'reviews' => $reviews,
            'average' => $stats && $stats['avg_rating'] !== null ? round((float)$stats['avg_rating'], 1) : 0,
            'count' => (int)($stats['count'] ?? 0)
        ], JSON_UNESCAPED_UNICODE);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
} elseif ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $bookId = intval($data['book_id'] ?? 0);
    $userId = intval($data['user_id'] ?? 0);
    $rating = intval($data['rating'] ?? 0);
    $comment = trim($data['comment'] ?? '');

    if ($bookId <= 0 || $userId <= 0 || $rating < 1 || $rating > 5) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid payload']);
        exit();
    }

    try {
        // Enforce verified purchase: user must have a paid/completed order containing this book
        $vpStmt = $db->prepare('SELECT oi.id
                                 FROM order_items oi
                                 JOIN orders o ON oi.order_id = o.id
                                 WHERE o.user_id = :user_id AND oi.book_id = :book_id AND o.status IN (\'paid\', \'completed\')
                                 LIMIT 1');
        $vpStmt->execute(['user_id' => $userId, 'book_id' => $bookId]);
        $hasPurchase = $vpStmt->fetch(PDO::FETCH_ASSOC);
        if (!$hasPurchase) {
            http_response_code(403);
            echo json_encode(['error' => 'Only verified purchasers can leave a review']);
            exit();
        }

        $stmt = $db->prepare('INSERT INTO reviews (book_id, user_id, rating, comment) VALUES (:book_id, :user_id, :rating, :comment)');
        $stmt->execute([
            'book_id' => $bookId,
            'user_id' => $userId,
            'rating' => $rating,
            'comment' => $comment
        ]);

        http_response_code(201);
        echo json_encode(['id' => $db->lastInsertId()]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>


