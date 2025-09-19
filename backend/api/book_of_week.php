
<?php
// Include centralized CORS configuration
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];

$database = new Database();
$db = $database->getConnection();

if ($method == 'GET') {
    try {
        // Get current book of the week
        $query = "SELECT
                    fb.id as featured_id,
                    fb.featured_type,
                    fb.start_date,
                    fb.end_date,
                    fb.is_active,
                    b.id,
                    b.title,
                    b.author,
                    b.price,
                    b.image_url,
                    b.description,
                    b.category_id,
                    b.stock_quantity,
                    b.is_featured,
                    COALESCE(c.name, 'غير مصنف') as category_name
                  FROM featured_book fb
                  JOIN books b ON fb.book_id = b.id
                  LEFT JOIN categories c ON b.category_id = c.id
                  WHERE fb.featured_type = 'book_of_week'
                  AND fb.is_active = TRUE
                  AND (fb.end_date IS NULL OR fb.end_date >= CURDATE())
                  ORDER BY fb.created_at DESC
                  LIMIT 1";

        $stmt = $db->prepare($query);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $book = [
                'featured_id' => (int)$result['featured_id'],
                'id' => (int)$result['id'],
                'title' => $result['title'],
                'author' => $result['author'],
                'price' => (float)$result['price'],
                'image_url' => $result['image_url'],
                'description' => $result['description'],
                'category_id' => (int)$result['category_id'],
                'category_name' => $result['category_name'],
                'stock_quantity' => (int)$result['stock_quantity'],
                'is_featured' => (bool)$result['is_featured'],
                'featured_type' => $result['featured_type'],
                'start_date' => $result['start_date'],
                'end_date' => $result['end_date']
            ];
            echo json_encode(['book_of_week' => $book], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['book_of_week' => null], JSON_UNESCAPED_UNICODE);
        }
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }

} elseif ($method === 'POST') {
    // Set new book of the week
    try {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$data || !isset($data['book_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Book ID is required'], JSON_UNESCAPED_UNICODE);
            exit();
        }

        $book_id = (int)$data['book_id'];
        $start_date = $data['start_date'] ?? date('Y-m-d');
        $end_date = $data['end_date'] ?? null;

        // Check if book exists
        $checkBook = $db->prepare('SELECT id FROM books WHERE id = ?');
        $checkBook->execute([$book_id]);
        if (!$checkBook->fetch()) {
            http_response_code(404);
            echo json_encode(['error' => 'Book not found'], JSON_UNESCAPED_UNICODE);
            exit();
        }

        // Start transaction
        $db->beginTransaction();

        // Deactivate current book of the week
        $deactivate = $db->prepare('UPDATE featured_book SET is_active = FALSE WHERE featured_type = "book_of_week" AND is_active = TRUE');
        $deactivate->execute();

        // Update books table - remove featured status from all books
        $unfeaturedAll = $db->prepare('UPDATE books SET is_featured = FALSE WHERE is_featured = TRUE');
        $unfeaturedAll->execute();

        // Add new book of the week
        $sql = 'INSERT INTO featured_book (book_id, featured_type, start_date, end_date, is_active)
                VALUES (:book_id, :featured_type, :start_date, :end_date, :is_active)';

        $stmt = $db->prepare($sql);
        $stmt->execute([
            'book_id' => $book_id,
            'featured_type' => 'book_of_week',
            'start_date' => $start_date,
            'end_date' => $end_date,
            'is_active' => true
        ]);

        // Update the book to be featured
        $updateBook = $db->prepare('UPDATE books SET is_featured = TRUE WHERE id = ?');
        $updateBook->execute([$book_id]);

        $featured_id = (int)$db->lastInsertId();

        $db->commit();

        http_response_code(201);
        echo json_encode([
            'featured_id' => $featured_id,
            'message' => 'Book of the week set successfully'
        ], JSON_UNESCAPED_UNICODE);

    } catch (PDOException $e) {
        $db->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }

} elseif ($method === 'PUT') {
    // Update book of the week
    $request_uri = $_SERVER['REQUEST_URI'];
    $id = (int)basename($request_uri);

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid featured book ID'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    try {
        $data = json_decode(file_get_contents('php://input'), true) ?: [];
        $fields = [];
        $params = ['id' => $id];

        $allowedFields = ['start_date', 'end_date', 'is_active'];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "$field = :$field";
                $params[$field] = $data[$field];
            }
        }

        if (empty($fields)) {
            http_response_code(400);
            echo json_encode(['error' => 'No fields to update'], JSON_UNESCAPED_UNICODE);
            exit();
        }

        $sql = 'UPDATE featured_book SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        if ($stmt->rowCount() > 0) {
            http_response_code(200);
            echo json_encode(['message' => 'Book of the week updated successfully'], JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Featured book not found'], JSON_UNESCAPED_UNICODE);
        }

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }

} elseif ($method === 'DELETE') {
    // Remove current book of the week
    try {
        $db->beginTransaction();

        // Deactivate current book of the week
        $deactivate = $db->prepare('UPDATE featured_book SET is_active = FALSE WHERE featured_type = "book_of_week" AND is_active = TRUE');
        $deactivate->execute();

        // Remove featured status from books
        $unfeatured = $db->prepare('UPDATE books SET is_featured = FALSE WHERE is_featured = TRUE');
        $unfeatured->execute();

        $db->commit();

        http_response_code(200);
        echo json_encode(['message' => 'Book of the week removed successfully'], JSON_UNESCAPED_UNICODE);

    } catch (PDOException $e) {
        $db->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }

} else {
    http_response_code(405);
    echo json_encode(['message' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
}
?>