<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/sendgrid_service.php';

header('Content-Type: application/json');
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '*';
header('Access-Control-Allow-Origin: ' . $origin);
header('Vary: Origin');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Create enhanced tables for order management
createOrderTables($db);

$request_uri = $_SERVER['REQUEST_URI'];
$path_parts = explode('/', trim(parse_url($request_uri, PHP_URL_PATH), '/'));
$endpoint = $path_parts[count($path_parts) - 1] ?? '';

switch ($method) {
    case 'POST':
        if ($endpoint === 'create' || empty($endpoint) || $endpoint === 'orders') {
            handleOrderCreation($db);
        } elseif ($endpoint === 'update-status') {
            handleOrderStatusUpdate($db);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found'], JSON_UNESCAPED_UNICODE);
        }
        break;

    case 'GET':
        if ($endpoint === 'list' || empty($endpoint) || $endpoint === 'orders') {
            handleOrderList($db);
        } elseif (strpos($endpoint, 'details') === 0) {
            $order_id = $_GET['order_id'] ?? '';
            handleOrderDetails($db, $order_id);
        } elseif ($endpoint === 'track') {
            $order_id = $_GET['order_id'] ?? '';
            handleOrderTracking($db, $order_id);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found'], JSON_UNESCAPED_UNICODE);
        }
        break;

    case 'PUT':
        if ($endpoint === 'cancel') {
            handleOrderCancellation($db);
        } elseif ($endpoint === 'ship') {
            handleOrderShipment($db);
        } elseif ($endpoint === 'complete') {
            handleOrderCompletion($db);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found'], JSON_UNESCAPED_UNICODE);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
}

function createOrderTables($db) {
    // Enhanced orders table with race condition protection
    $db->exec("CREATE TABLE IF NOT EXISTS orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id VARCHAR(255) UNIQUE NOT NULL,
        customer_id INT,
        customer_info JSON NOT NULL,
        items JSON NOT NULL,
        shipping_address JSON,
        billing_address JSON,
        subtotal DECIMAL(10, 2) NOT NULL,
        shipping_cost DECIMAL(10, 2) DEFAULT 0,
        tax_amount DECIMAL(10, 2) DEFAULT 0,
        total_amount DECIMAL(10, 2) NOT NULL,
        currency VARCHAR(3) DEFAULT 'SAR',
        status ENUM('pending', 'paid', 'processing', 'shipped', 'completed', 'cancelled') DEFAULT 'pending',
        payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
        idempotency_key VARCHAR(255) UNIQUE,
        stock_reserved BOOLEAN DEFAULT TRUE,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_order_id (order_id),
        INDEX idx_customer_id (customer_id),
        INDEX idx_status (status),
        INDEX idx_payment_status (payment_status),
        INDEX idx_idempotency_key (idempotency_key)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Order status history table
    $db->exec("CREATE TABLE IF NOT EXISTS order_status_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id VARCHAR(255) NOT NULL,
        old_status VARCHAR(50),
        new_status VARCHAR(50) NOT NULL,
        notes TEXT,
        changed_by VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_order_id (order_id),
        INDEX idx_status (new_status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Legacy order_items table for backward compatibility
    $db->exec("CREATE TABLE IF NOT EXISTS order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        book_id INT NOT NULL,
        quantity INT NOT NULL,
        price DECIMAL(10, 2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_order_id (order_id),
        INDEX idx_book_id (book_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function handleOrderCreation($db) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);

        // Handle both new format and legacy format
        if (isset($data['customerInfo'])) {
            // Legacy format
            $customerInfo = $data['customerInfo'];
            $items = $data['items'] ?? [];
            $paymentMethod = $data['paymentMethod'] ?? null;
            $total = isset($data['total']) ? floatval($data['total']) : null;

            // Convert to new format
            $data = [
                'customer_info' => $customerInfo,
                'items' => $items,
                'payment_method' => $paymentMethod,
                'total' => $total,
                'idempotency_key' => 'legacy_' . time() . '_' . uniqid()
            ];
        }

        if (!$data || !isset($data['items']) || !is_array($data['items']) || empty($data['items'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Items are required'], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Generate idempotency key if not provided
        $idempotency_key = $data['idempotency_key'] ?? 'order_' . time() . '_' . uniqid();
        $data['idempotency_key'] = $idempotency_key;

        // Use the enhanced createOrderWithTransaction function from payments.php
        $result = createOrderWithTransaction($db, $data);

        // Handle legacy response format
        if (isset($data['paymentMethod'])) {
            echo json_encode([
                'success' => true,
                'order' => [
                    'id' => $result['order_id'],
                    'status' => $result['status'],
                    'total' => $result['total']
                ],
                'order_id' => $result['order_id'],
                'message' => 'Order created successfully'
            ], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode([
                'status' => 'success',
                'order_id' => $result['order_id'],
                'order_status' => $result['status'],
                'total_amount' => $result['total'],
                'message' => 'Order created successfully'
            ], JSON_UNESCAPED_UNICODE);
        }

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Order creation error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
}

// Import functions from payments.php for order creation
function createOrderWithTransaction($db, $data) {
    $order_id = $data['order_id'] ?? 'order_' . time() . '_' . uniqid();
    $idempotency_key = $data['idempotency_key'] ?? null;

    // Start database transaction
    $db->beginTransaction();

    try {
        // Check for idempotency - if order with this key exists, return existing order
        if ($idempotency_key) {
            $stmt = $db->prepare('SELECT order_id, status FROM orders WHERE idempotency_key = :key FOR UPDATE');
            $stmt->execute(['key' => $idempotency_key]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                $db->commit();
                return ['order_id' => $existing['order_id'], 'status' => $existing['status'], 'existing' => true];
            }
        }

        // Validate and calculate totals server-side
        $calculated_subtotal = 0;
        $validated_items = [];

        if (isset($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $item) {
                $item_id = (int)($item['id'] ?? 0);
                $quantity = (int)($item['quantity'] ?? 1);
                $type = $item['type'] ?? 'book';

                if ($item_id <= 0 || $quantity <= 0) {
                    throw new Exception('Invalid item ID or quantity');
                }

                if ($type === 'package') {
                    // Validate publishing package, no stock to reserve
                    $pkgStmt = $db->prepare('SELECT id, name AS title, price, currency FROM publishing_packages WHERE id = :id');
                    $pkgStmt->execute(['id' => $item_id]);
                    $pkg = $pkgStmt->fetch(PDO::FETCH_ASSOC);
                    if (!$pkg) {
                        throw new Exception('Package not found: ' . $item_id);
                    }
                    $item_total = (float)$pkg['price'] * $quantity;
                    $calculated_subtotal += $item_total;
                    $validated_items[] = [
                        'id' => $item_id,
                        'type' => 'package',
                        'title' => $pkg['title'],
                        'price' => (float)$pkg['price'],
                        'quantity' => $quantity,
                        'total' => $item_total
                    ];
                } else {
                    // Book flow with stock locking
                    $bookStmt = $db->prepare('SELECT id, title, price, stock_quantity FROM books WHERE id = :id FOR UPDATE');
                    $bookStmt->execute(['id' => $item_id]);
                    $book = $bookStmt->fetch(PDO::FETCH_ASSOC);

                    if (!$book) {
                        throw new Exception('Book not found: ' . $item_id);
                    }

                    if ($book['stock_quantity'] < $quantity) {
                        throw new Exception('Insufficient stock for book: ' . $book['title'] . '. Available: ' . $book['stock_quantity']);
                    }

                    $item_total = (float)$book['price'] * $quantity;
                    $calculated_subtotal += $item_total;

                    $validated_items[] = [
                        'id' => $item_id,
                        'type' => 'book',
                        'title' => $book['title'],
                        'price' => (float)$book['price'],
                        'quantity' => $quantity,
                        'total' => $item_total
                    ];

                    // Reserve stock (reduce immediately during order creation)
                    $updateStmt = $db->prepare('UPDATE books SET stock_quantity = stock_quantity - :quantity WHERE id = :id');
                    $updateStmt->execute(['quantity' => $quantity, 'id' => $item_id]);
                }
            }
        }

        $shipping_cost = (float)($data['shipping_cost'] ?? 0);
        $tax_amount = (float)($data['tax_amount'] ?? 0);
        $calculated_total = $calculated_subtotal + $shipping_cost + $tax_amount;

        // Validate client-provided total matches server calculation (if provided)
        $client_total = (float)($data['total'] ?? $calculated_total);
        if (abs($calculated_total - $client_total) > 0.01) {
            // Allow small discrepancies for legacy compatibility
            if (abs($calculated_total - $client_total) > 1.0) {
                throw new Exception('Total amount mismatch. Expected: ' . $calculated_total . ', Received: ' . $client_total);
            }
        }

        // Create order record
        $stmt = $db->prepare(
            'INSERT INTO orders (order_id, customer_info, items, shipping_address, billing_address,
                               subtotal, shipping_cost, tax_amount, total_amount, currency, status, payment_status, idempotency_key)
             VALUES (:order_id, :customer_info, :items, :shipping_address, :billing_address,
                     :subtotal, :shipping_cost, :tax_amount, :total_amount, :currency, :status, :payment_status, :idempotency_key)'
        );

        $stmt->execute([
            'order_id' => $order_id,
            'customer_info' => json_encode($data['customer_info'] ?? []),
            'items' => json_encode($validated_items),
            'shipping_address' => json_encode($data['shipping_address'] ?? []),
            'billing_address' => json_encode($data['billing_address'] ?? []),
            'subtotal' => $calculated_subtotal,
            'shipping_cost' => $shipping_cost,
            'tax_amount' => $tax_amount,
            'total_amount' => $calculated_total,
            'currency' => $data['currency'] ?? 'SAR',
            'status' => 'pending',
            'payment_status' => 'pending',
            'idempotency_key' => $idempotency_key
        ]);

        $db->commit();
        
        // Send order confirmation email
        try {
            $sendGridService = new SendGridService();
            $orderData = [
                'order_id' => $order_id,
                'customer_info' => $data['customer_info'] ?? [],
                'items' => $validated_items,
                'subtotal' => $calculated_subtotal,
                'shipping_cost' => $shipping_cost,
                'tax_amount' => $tax_amount,
                'total_amount' => $calculated_total,
                'status' => 'pending'
            ];
            $sendGridService->sendOrderConfirmation($orderData);
        } catch (Exception $e) {
            // Log error but don't fail the order creation
            error_log('Failed to send order confirmation email: ' . $e->getMessage());
        }
        
        return ['order_id' => $order_id, 'status' => 'pending', 'total' => $calculated_total];

    } catch (Exception $e) {
        $db->rollback();
        throw $e;
    }
}

function handleOrderList($db) {
    try {
        $customer_id = $_GET['customer_id'] ?? null;
        $status = $_GET['status'] ?? null;
        $payment_status = $_GET['payment_status'] ?? null;
        $limit = (int)($_GET['limit'] ?? 20);
        $offset = (int)($_GET['offset'] ?? 0);
        $search = $_GET['search'] ?? '';

        $where = [];
        $params = [];

        if ($customer_id) {
            $where[] = 'JSON_EXTRACT(customer_info, "$.user_id") = :customer_id';
            $params['customer_id'] = $customer_id;
        }

        if ($status) {
            $where[] = 'status = :status';
            $params['status'] = $status;
        }

        if ($payment_status) {
            $where[] = 'payment_status = :payment_status';
            $params['payment_status'] = $payment_status;
        }

        if ($search) {
            $where[] = '(order_id LIKE :search OR JSON_EXTRACT(customer_info, "$.name") LIKE :search OR JSON_EXTRACT(customer_info, "$.email") LIKE :search)';
            $params['search'] = '%' . $search . '%';
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        // Count total
        $countStmt = $db->prepare("SELECT COUNT(*) as total FROM orders $whereClause");
        foreach ($params as $key => $value) {
            $countStmt->bindValue(':' . $key, $value);
        }
        $countStmt->execute();
        $total = (int)$countStmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Get orders
        $stmt = $db->prepare(
            "SELECT order_id, customer_info, subtotal, shipping_cost, tax_amount, total_amount,
                    currency, status, payment_status, created_at, updated_at
             FROM orders $whereClause
             ORDER BY created_at DESC
             LIMIT :limit OFFSET :offset"
        );

        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Decode customer info for each order and format numbers
        foreach ($orders as &$order) {
            $order['customer_info'] = json_decode($order['customer_info'], true);
            $order['subtotal'] = (float)$order['subtotal'];
            $order['shipping_cost'] = (float)$order['shipping_cost'];
            $order['tax_amount'] = (float)$order['tax_amount'];
            $order['total_amount'] = (float)$order['total_amount'];
        }

        echo json_encode([
            'orders' => $orders,
            'total' => $total,
            'page' => floor($offset / $limit) + 1,
            'limit' => $limit
        ], JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Order list error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
}

function handleOrderStatusUpdate($db) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        $order_id = $data['order_id'] ?? '';
        $new_status = $data['status'] ?? '';
        $notes = $data['notes'] ?? '';

        if (!$order_id || !$new_status) {
            http_response_code(400);
            echo json_encode(['error' => 'Order ID and status are required'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $valid_statuses = ['pending', 'paid', 'processing', 'shipped', 'completed', 'cancelled'];
        if (!in_array($new_status, $valid_statuses)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid status'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $db->beginTransaction();

        try {
            // Get current order status
            $currentStmt = $db->prepare('SELECT status, payment_status, items FROM orders WHERE order_id = :id FOR UPDATE');
            $currentStmt->execute(['id' => $order_id]);
            $currentOrder = $currentStmt->fetch(PDO::FETCH_ASSOC);

            if (!$currentOrder) {
                $db->rollback();
                http_response_code(404);
                echo json_encode(['error' => 'Order not found'], JSON_UNESCAPED_UNICODE);
                return;
            }

            // Handle cancellation - restore stock
            if ($new_status === 'cancelled' && $currentOrder['status'] !== 'cancelled') {
                $items = json_decode($currentOrder['items'], true);
                foreach ($items as $item) {
                    $restoreStmt = $db->prepare(
                        'UPDATE books SET stock_quantity = stock_quantity + :quantity WHERE id = :id'
                    );
                    $restoreStmt->execute([
                        'quantity' => $item['quantity'],
                        'id' => $item['id']
                    ]);
                }
            }

            // Update order status
            $updateStmt = $db->prepare(
                'UPDATE orders SET status = :status, notes = :notes, updated_at = CURRENT_TIMESTAMP WHERE order_id = :id'
            );
            $updateStmt->execute([
                'status' => $new_status,
                'notes' => $notes,
                'id' => $order_id
            ]);

            // Create status history record
            createOrderStatusHistory($db, $order_id, $currentOrder['status'], $new_status, $notes);

            $db->commit();

            echo json_encode([
                'status' => 'success',
                'order_id' => $order_id,
                'new_status' => $new_status,
                'message' => 'Order status updated successfully'
            ], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Status update error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
}

function createOrderStatusHistory($db, $order_id, $old_status, $new_status, $notes) {
    $stmt = $db->prepare(
        'INSERT INTO order_status_history (order_id, old_status, new_status, notes, changed_by)
         VALUES (:order_id, :old_status, :new_status, :notes, :changed_by)'
    );

    $stmt->execute([
        'order_id' => $order_id,
        'old_status' => $old_status,
        'new_status' => $new_status,
        'notes' => $notes,
        'changed_by' => 'system' // In production, use actual user ID
    ]);
}

function handleOrderDetails($db, $order_id) {
    try {
        if (!$order_id) {
            http_response_code(400);
            echo json_encode(['error' => 'Order ID is required'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $stmt = $db->prepare('SELECT * FROM orders WHERE order_id = :id');
        $stmt->execute(['id' => $order_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            http_response_code(404);
            echo json_encode(['error' => 'Order not found'], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Get order status history
        $historyStmt = $db->prepare(
            'SELECT * FROM order_status_history WHERE order_id = :id ORDER BY created_at ASC'
        );
        $historyStmt->execute(['id' => $order_id]);
        $history = $historyStmt->fetchAll(PDO::FETCH_ASSOC);

        // Decode JSON fields
        $order['customer_info'] = json_decode($order['customer_info'], true);
        $order['items'] = json_decode($order['items'], true);
        $order['shipping_address'] = json_decode($order['shipping_address'], true);
        $order['billing_address'] = json_decode($order['billing_address'], true);

        // Convert numeric fields
        $order['subtotal'] = (float)$order['subtotal'];
        $order['shipping_cost'] = (float)$order['shipping_cost'];
        $order['tax_amount'] = (float)$order['tax_amount'];
        $order['total_amount'] = (float)$order['total_amount'];

        echo json_encode([
            'order' => $order,
            'status_history' => $history
        ], JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Order details error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
}

function handleOrderTracking($db, $order_id) {
    try {
        if (!$order_id) {
            http_response_code(400);
            echo json_encode(['error' => 'Order ID is required'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $stmt = $db->prepare(
            'SELECT order_id, status, payment_status, created_at, updated_at FROM orders WHERE order_id = :id'
        );
        $stmt->execute(['id' => $order_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            http_response_code(404);
            echo json_encode(['error' => 'Order not found'], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Get status history for tracking
        $historyStmt = $db->prepare(
            'SELECT new_status, notes, created_at FROM order_status_history
             WHERE order_id = :id ORDER BY created_at ASC'
        );
        $historyStmt->execute(['id' => $order_id]);
        $tracking = $historyStmt->fetchAll(PDO::FETCH_ASSOC);

        // Create tracking timeline
        $timeline = [];
        $statuses = ['pending', 'paid', 'processing', 'shipped', 'completed'];

        foreach ($statuses as $status) {
            $tracking_item = [
                'status' => $status,
                'completed' => false,
                'date' => null,
                'notes' => null
            ];

            foreach ($tracking as $track) {
                if ($track['new_status'] === $status) {
                    $tracking_item['completed'] = true;
                    $tracking_item['date'] = $track['created_at'];
                    $tracking_item['notes'] = $track['notes'];
                    break;
                }
            }

            $timeline[] = $tracking_item;
        }

        echo json_encode([
            'order_id' => $order['order_id'],
            'current_status' => $order['status'],
            'payment_status' => $order['payment_status'],
            'timeline' => $timeline,
            'last_updated' => $order['updated_at']
        ], JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Order tracking error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
}

function handleOrderCancellation($db) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        $order_id = $data['order_id'] ?? '';
        $reason = $data['reason'] ?? 'Order cancelled';

        if (!$order_id) {
            http_response_code(400);
            echo json_encode(['error' => 'Order ID is required'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $status_data = [
            'order_id' => $order_id,
            'status' => 'cancelled',
            'notes' => $reason
        ];

        // Call the status update function directly
        $original_input = file_get_contents('php://input');
        file_put_contents('php://input', json_encode($status_data));
        handleOrderStatusUpdate($db);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Order cancellation error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
}

function handleOrderShipment($db) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        $order_id = $data['order_id'] ?? '';
        $tracking_number = $data['tracking_number'] ?? '';
        $carrier = $data['carrier'] ?? '';

        if (!$order_id) {
            http_response_code(400);
            echo json_encode(['error' => 'Order ID is required'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $notes = "Order shipped";
        if ($tracking_number) {
            $notes .= " - Tracking: $tracking_number";
        }
        if ($carrier) {
            $notes .= " via $carrier";
        }

        $status_data = [
            'order_id' => $order_id,
            'status' => 'shipped',
            'notes' => $notes
        ];

        // Update order status to shipped
        $db->beginTransaction();
        try {
            $updateStmt = $db->prepare(
                'UPDATE orders SET status = :status, notes = :notes, updated_at = CURRENT_TIMESTAMP WHERE order_id = :id'
            );
            $updateStmt->execute([
                'status' => 'shipped',
                'notes' => $notes,
                'id' => $order_id
            ]);

            createOrderStatusHistory($db, $order_id, 'processing', 'shipped', $notes);
            $db->commit();

            echo json_encode([
                'status' => 'success',
                'order_id' => $order_id,
                'message' => 'Order marked as shipped'
            ], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Order shipment error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
}

function handleOrderCompletion($db) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        $order_id = $data['order_id'] ?? '';

        if (!$order_id) {
            http_response_code(400);
            echo json_encode(['error' => 'Order ID is required'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $db->beginTransaction();
        try {
            $updateStmt = $db->prepare(
                'UPDATE orders SET status = :status, notes = :notes, updated_at = CURRENT_TIMESTAMP WHERE order_id = :id'
            );
            $updateStmt->execute([
                'status' => 'completed',
                'notes' => 'Order completed successfully',
                'id' => $order_id
            ]);

            createOrderStatusHistory($db, $order_id, 'shipped', 'completed', 'Order completed successfully');
            $db->commit();

            echo json_encode([
                'status' => 'success',
                'order_id' => $order_id,
                'message' => 'Order marked as completed'
            ], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Order completion error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
}
?>
