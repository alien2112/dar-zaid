<?php
// Include centralized CORS configuration
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'GET') {
    try {
        // Check if admin is requesting all packages (including inactive)
        $isAdmin = isset($_GET['admin']) && $_GET['admin'] === 'true';
        $whereClause = $isAdmin ? '' : 'WHERE is_active = 1';

        $query = "SELECT * FROM publishing_packages $whereClause ORDER BY display_order, id";
        $stmt = $db->prepare($query);
        $stmt->execute();

        $packages = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $package = [
                'id' => (int)$row['id'],
                'name' => $row['name'],
                'price' => (float)$row['price'],
                'currency' => $row['currency'],
                'authorShare' => $row['author_share'],
                'freeCopies' => (int)$row['free_copies'],
                'description' => $row['description'],
                'specifications' => json_decode($row['specifications'], true),
                'services' => json_decode($row['services'], true),
                'additionalServices' => json_decode($row['additional_services'], true),
                'additionalOffers' => $row['additional_offers'],
                'isActive' => (bool)$row['is_active'],
                'displayOrder' => (int)$row['display_order'],
                'createdAt' => $row['created_at'],
                'updatedAt' => $row['updated_at']
            ];
            $packages[] = $package;
        }

        echo json_encode(['packages' => $packages], JSON_UNESCAPED_UNICODE);
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }

} elseif ($method === 'POST') {
    // Create new package
    try {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$data || empty($data['name']) || !isset($data['price'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Name and price are required'], JSON_UNESCAPED_UNICODE);
            exit();
        }

        $sql = 'INSERT INTO publishing_packages (name, price, currency, author_share, free_copies, description,
                specifications, services, additional_services, additional_offers, is_active, display_order)
                VALUES (:name, :price, :currency, :author_share, :free_copies, :description,
                :specifications, :services, :additional_services, :additional_offers, :is_active, :display_order)';

        $stmt = $db->prepare($sql);
        $stmt->execute([
            'name' => $data['name'],
            'price' => (float)$data['price'],
            'currency' => $data['currency'] ?? 'ريال',
            'author_share' => $data['authorShare'] ?? '70%',
            'free_copies' => $data['freeCopies'] ?? 20,
            'description' => $data['description'] ?? '',
            'specifications' => json_encode($data['specifications'] ?? []),
            'services' => json_encode($data['services'] ?? []),
            'additional_services' => json_encode($data['additionalServices'] ?? []),
            'additional_offers' => $data['additionalOffers'] ?? '',
            'is_active' => $data['isActive'] ?? true,
            'display_order' => $data['displayOrder'] ?? 0
        ]);

        $id = (int)$db->lastInsertId();

        http_response_code(201);
        echo json_encode(['id' => $id, 'message' => 'Package created successfully'], JSON_UNESCAPED_UNICODE);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }

} elseif ($method === 'PUT') {
    // Update package
    $request_uri = $_SERVER['REQUEST_URI'];
    $id = (int)basename($request_uri);

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid package ID'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    try {
        $data = json_decode(file_get_contents('php://input'), true) ?: [];
        $fields = [];
        $params = ['id' => $id];

        $allowedFields = ['name', 'price', 'currency', 'author_share', 'free_copies', 'description',
                         'specifications', 'services', 'additional_services', 'additional_offers',
                         'is_active', 'display_order'];

        foreach ($allowedFields as $field) {
            $dataKey = $field;
            if ($field === 'author_share') $dataKey = 'authorShare';
            elseif ($field === 'free_copies') $dataKey = 'freeCopies';
            elseif ($field === 'additional_services') $dataKey = 'additionalServices';
            elseif ($field === 'additional_offers') $dataKey = 'additionalOffers';
            elseif ($field === 'is_active') $dataKey = 'isActive';
            elseif ($field === 'display_order') $dataKey = 'displayOrder';

            if (array_key_exists($dataKey, $data)) {
                if (in_array($field, ['specifications', 'services', 'additional_services'])) {
                    $fields[] = "$field = :$field";
                    $params[$field] = json_encode($data[$dataKey]);
                } else {
                    $fields[] = "$field = :$field";
                    $params[$field] = $data[$dataKey];
                }
            }
        }

        if (empty($fields)) {
            http_response_code(400);
            echo json_encode(['error' => 'No fields to update'], JSON_UNESCAPED_UNICODE);
            exit();
        }

        $sql = 'UPDATE publishing_packages SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        if ($stmt->rowCount() > 0) {
            http_response_code(200);
            echo json_encode(['message' => 'Package updated successfully'], JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Package not found'], JSON_UNESCAPED_UNICODE);
        }

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }

} elseif ($method === 'DELETE') {
    // Delete package
    $request_uri = $_SERVER['REQUEST_URI'];
    $id = (int)basename($request_uri);

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid package ID'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    try {
        $stmt = $db->prepare('DELETE FROM publishing_packages WHERE id = :id');
        $stmt->execute(['id' => $id]);

        if ($stmt->rowCount() > 0) {
            http_response_code(200);
            echo json_encode(['message' => 'Package deleted successfully'], JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Package not found'], JSON_UNESCAPED_UNICODE);
        }

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }

} else {
    http_response_code(405);
    echo json_encode(['message' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
}
?>
